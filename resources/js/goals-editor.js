/**
 * Alpine component for the therapy-plan goals editor.
 * Loads goals from GET /goals-bank/search.json, keeps the plan's goals in memory,
 * and saves everything as one JSON payload (PUT plans/{plan}).
 */
export default function goalsEditor(config) {
    let uid = 0;
    const withUid = (g) => ({
        uid: ++uid,
        bank_goal_id: g.bank_goal_id ?? null,
        domain_name: g.domain_name ?? '',
        level: g.level ?? null,
        subcategory: g.subcategory ?? null,
        printed_code: g.printed_code ?? null,
        goal_text: g.goal_text ?? '',
        training_elements: g.training_elements ?? null,
        generalization: g.generalization ?? null,
        teacher_note: g.teacher_note ?? '',
        open: false,
    });

    return {
        plan: { ...config.plan },
        goals: (config.goals || []).map(withUid),
        domains: config.domains || [],

        filters: { q: '', domain_id: '', level: '', subcategory: '' },
        facets: { levels: [], subcategories: [] },
        results: [],
        meta: { current_page: 1, last_page: 1, total: 0 },
        loading: false,
        loadError: '',
        showDetails: true,

        tab: 'plan', // mobile tab: plan | bank
        saving: false,
        submitting: false,
        dirty: false,
        savedAt: null,
        message: '',
        errors: [],
        debounce: null,
        snapshot: '',

        init() {
            this.loadBank(1);
            this.snapshot = JSON.stringify(this.payload());
            const check = () => (this.dirty = JSON.stringify(this.payload()) !== this.snapshot);
            this.$watch('goals', check);
            this.$watch('plan', check);
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

        // ---- bank browsing -------------------------------------------------
        onDomainChange() {
            this.filters.level = '';
            this.filters.subcategory = '';
            this.loadBank(1);
        },
        onLevelChange() {
            this.filters.subcategory = '';
            this.loadBank(1);
        },
        onSearchInput() {
            clearTimeout(this.debounce);
            this.debounce = setTimeout(() => this.loadBank(1), 350);
        },
        resetFilters() {
            this.filters = { q: '', domain_id: '', level: '', subcategory: '' };
            this.loadBank(1);
        },
        async loadBank(page = 1) {
            this.loading = true;
            this.loadError = '';
            const params = new URLSearchParams({ page });
            Object.entries(this.filters).forEach(([k, v]) => v && params.set(k, v));
            try {
                const res = await fetch(`${config.searchUrl}?${params}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('bad status');
                const json = await res.json();
                this.results = json.data;
                this.meta = json.meta;
                this.facets = json.facets;
            } catch (e) {
                this.loadError = 'تعذر تحميل بنك الأهداف. حاول مرة أخرى.';
            } finally {
                this.loading = false;
            }
        },

        // ---- plan goals ----------------------------------------------------
        inPlan(bankId) {
            return this.goals.some((g) => g.bank_goal_id === bankId);
        },
        addFromBank(goal) {
            if (this.inPlan(goal.id)) return;
            this.goals.push(
                withUid({
                    bank_goal_id: goal.id,
                    domain_name: goal.domain_name,
                    level: goal.level,
                    subcategory: goal.subcategory,
                    printed_code: goal.printed_code,
                    goal_text: goal.goal_text,
                    training_elements: goal.training_elements,
                    generalization: goal.generalization,
                })
            );
            this.message = 'تمت إضافة الهدف إلى الخطة.';
        },
        addCustom() {
            const g = withUid({ domain_name: '' });
            g.open = true;
            this.goals.push(g);
            this.tab = 'plan';
        },
        remove(i) {
            if (confirm('إزالة هذا الهدف من الخطة؟')) this.goals.splice(i, 1);
        },
        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.goals.length) return;
            const [g] = this.goals.splice(i, 1);
            this.goals.splice(j, 0, g);
        },
        get domainsInPlan() {
            return [...new Set(this.goals.map((g) => g.domain_name).filter(Boolean))];
        },

        // ---- saving --------------------------------------------------------
        payload() {
            const plan = {};
            Object.entries(this.plan).forEach(([k, v]) => {
                if (v !== null && v !== '') plan[k] = v;
            });
            return {
                ...plan,
                goals: this.goals.map((g) => ({
                    bank_goal_id: g.bank_goal_id,
                    domain_name: g.domain_name || null,
                    level: g.level,
                    subcategory: g.subcategory,
                    printed_code: g.printed_code,
                    goal_text: g.goal_text,
                    training_elements: g.training_elements,
                    generalization: g.generalization,
                    teacher_note: g.teacher_note || null,
                })),
            };
        },
        async request(url, method, body) {
            const res = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body ? JSON.stringify(body) : undefined,
            });
            let json = {};
            try {
                json = await res.json();
            } catch (e) {}
            return { ok: res.ok, status: res.status, json };
        },
        collectErrors(json) {
            if (json.errors) return Object.values(json.errors).flat();
            return [json.message || 'حدث خطأ غير متوقع.'];
        },
        async save() {
            this.errors = [];
            this.message = '';
            this.saving = true;
            try {
                const { ok, json } = await this.request(config.saveUrl, 'PUT', this.payload());
                if (!ok) {
                    this.errors = this.collectErrors(json);
                    return false;
                }
                this.snapshot = JSON.stringify(this.payload());
                this.dirty = false;
                this.savedAt = json.saved_at;
                this.message = json.message;
                return true;
            } catch (e) {
                this.errors = ['تعذر الاتصال بالخادم. تحقق من الاتصال وحاول مرة أخرى.'];
                return false;
            } finally {
                this.saving = false;
            }
        },
        async submit() {
            if (this.goals.length < 1) {
                this.errors = ['يجب إضافة هدف واحد على الأقل قبل إرسال الخطة للمراجعة.'];
                return;
            }
            if (!confirm('سيتم حفظ المسودة وإرسال الخطة للمراجعة. هل تريد المتابعة؟')) return;
            this.submitting = true;
            try {
                if (!(await this.save())) return;
                const { ok, json } = await this.request(config.submitUrl, 'POST');
                if (!ok) {
                    this.errors = this.collectErrors(json);
                    return;
                }
                this.dirty = false;
                window.location.href = json.redirect;
            } catch (e) {
                this.errors = ['تعذر الاتصال بالخادم.'];
            } finally {
                this.submitting = false;
            }
        },
    };
}
