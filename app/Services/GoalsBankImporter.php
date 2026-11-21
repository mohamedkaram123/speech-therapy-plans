<?php

namespace App\Services;

use InvalidArgumentException;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\IOFactory;

/**
 * Parses the "برنامج تنمية المهارات اللغوية" Word goals bank.
 *
 * The document body is walked in order. A paragraph preceding a table acts as a
 * header that sets the current domain / level / subcategory; each table then
 * yields goal rows (م | الأهداف | عناصر التدريب | استراتيجيات التعميم).
 * Some tables (domain 6) also contain single-cell rows that switch the
 * subcategory mid-table.
 *
 * Nothing is invented: every value comes from the document. Anything unusual
 * is flagged via needs_review / review_reason instead of being "fixed".
 */
class GoalsBankImporter
{
    public const DOMAIN_RECEPTIVE = 'اللغة الاستقبالية';
    public const DOMAIN_EXPRESSIVE = 'اللغة التعبيرية';
    public const DOMAIN_PRAGMATICS_DIALOGUE = 'البلاغة والحوار';
    public const DOMAIN_PRELANGUAGE = 'مهارات ما قبل اللغة';
    public const DOMAIN_AUDITORY_MEMORY = 'الذاكرة السمعية';
    public const DOMAIN_ARTICULATORS = 'تدريبات أعضاء النطق';
    public const DOMAIN_METALANGUAGE = 'فهم ما وراء اللغة (البراجماتيكا)';

    public const LEVEL_BEGINNER = 'مبتدئ';
    public const LEVEL_INTERMEDIATE = 'متوسط';
    public const LEVEL_ADVANCED = 'متقدم';
    public const LEVEL_IMPLICIT_GROUP = 'المجموعة الضمنية';

    /**
     * Normalized header fragment => canonical domain name.
     * Matched against normalizeArabic() output (alef/ya/ta-marbuta folded).
     */
    private const DOMAIN_PATTERNS = [
        'اللغه الاستقباليه' => self::DOMAIN_RECEPTIVE,
        'اللغه التعبيريه' => self::DOMAIN_EXPRESSIVE,
        'البلاغه والحوار' => self::DOMAIN_PRAGMATICS_DIALOGUE,
        'مهارات ما قبل اللغه' => self::DOMAIN_PRELANGUAGE,
        'الذاكره السمعيه' => self::DOMAIN_AUDITORY_MEMORY,
        'تدريبات اعضاء النطق' => self::DOMAIN_ARTICULATORS,
        'ما وراء اللغه' => self::DOMAIN_METALANGUAGE,
    ];

    /** Pre-language subcategory fragments (normalized) => canonical name. */
    private const PRELANGUAGE_SUBCATEGORIES = [
        'التواصل البصري' => 'التواصل البصري والانتباه',
        'التقليد الحركي' => 'التقليد الحركي',
        'التقليد اللفظي' => 'التقليد اللفظي',
        'الاشاره' => 'الإشارة والطلب',
        'تنفيذ الاوامر' => 'تنفيذ الأوامر',
    ];

    /** Letter in the printed code (4-أ1, 4-ب1 ...) => pre-language subcategory. */
    private const PRELANGUAGE_CODE_LETTERS = [
        'ا' => 'التواصل البصري والانتباه',
        'ب' => 'التقليد الحركي',
        'ج' => 'التقليد اللفظي',
        'د' => 'الإشارة والطلب',
        'ه' => 'تنفيذ الأوامر',
    ];

    /**
     * Numeric prefix each domain's printed codes are expected to carry.
     * (The source document numbers the pragmatics domain "4-n", although its
     * header says 7; we only accept that and flag everything else.)
     */
    private const EXPECTED_CODE_PREFIX = [
        self::DOMAIN_RECEPTIVE => ['1'],
        self::DOMAIN_EXPRESSIVE => ['2'],
        self::DOMAIN_PRAGMATICS_DIALOGUE => ['3'],
        self::DOMAIN_PRELANGUAGE => ['4'],
        self::DOMAIN_AUDITORY_MEMORY => ['5'],
        self::DOMAIN_ARTICULATORS => ['6'],
        self::DOMAIN_METALANGUAGE => ['4', '7'],
    ];

    private ?string $domain = null;
    private ?string $level = null;
    private ?string $subcategory = null;
    private bool $headerSeen = false;

    /**
     * @return array{goals: array<int,array<string,mixed>>, summary: array<string,mixed>}
     */
    public function parse(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Goals bank file not found or unreadable: {$path}");
        }

        $this->domain = $this->level = $this->subcategory = null;
        $this->headerSeen = false;

        $phpWord = IOFactory::load($path, 'Word2007');
        $goals = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Table) {
                    foreach ($this->parseTable($element) as $goal) {
                        $goals[] = $goal;
                    }
                    continue;
                }

                // Intro bullet lists etc. are ListItem*, never headers.
                if ($element instanceof AbstractContainer && ! $this->isListItem($element)) {
                    $this->applyHeader($this->cleanInline($this->elementText($element)));
                }
            }
        }

        $goals = $this->flagDuplicates($goals);

        return ['goals' => $goals, 'summary' => $this->summarize($goals)];
    }

    // ------------------------------------------------------------------
    // Headers
    // ------------------------------------------------------------------

    private function isListItem(object $element): bool
    {
        return str_contains(get_class($element), 'ListItem');
    }

    /**
     * A header paragraph is only recognised when it names a known domain.
     * Level and subcategory are reset by every header (they never leak from
     * one domain/header to the next).
     */
    private function applyHeader(string $text): void
    {
        if ($text === '' || mb_strlen($text) > 160) {
            return;
        }

        $norm = $this->normalizeArabic($text);
        $domain = null;
        foreach (self::DOMAIN_PATTERNS as $needle => $name) {
            if (str_contains($norm, $needle)) {
                $domain = $name;
                break;
            }
        }
        if ($domain === null) {
            return;
        }

        $this->headerSeen = true;
        $this->domain = $domain;
        $this->level = $this->detectLevel($norm);
        $this->subcategory = $domain === self::DOMAIN_PRELANGUAGE ? $this->detectPrelanguageSubcategory($norm) : null;
    }

    private function detectLevel(string $norm): ?string
    {
        if (str_contains($norm, 'مبتد')) { // المبتدأ / مبتدئ
            return self::LEVEL_BEGINNER;
        }
        if (str_contains($norm, 'متوسط')) {
            return self::LEVEL_INTERMEDIATE;
        }
        if (str_contains($norm, 'متقدم')) {
            return self::LEVEL_ADVANCED;
        }
        if (str_contains($norm, 'المجموعه الضمنيه')) {
            return self::LEVEL_IMPLICIT_GROUP;
        }

        return null;
    }

    private function detectPrelanguageSubcategory(string $norm): ?string
    {
        foreach (self::PRELANGUAGE_SUBCATEGORIES as $needle => $name) {
            if (str_contains($norm, $needle)) {
                return $name;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Tables
    // ------------------------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    private function parseTable(Table $table): array
    {
        $out = [];
        // Letter of an in-table subcategory row (domain 6), used to cross-check codes.
        $subLetter = null;

        foreach ($table->getRows() as $row) {
            $cells = $row->getCells();
            $texts = [];
            foreach ($cells as $cell) {
                $texts[] = $this->cellText($cell);
            }
            $nonEmpty = array_values(array_filter($texts, fn ($t) => $t !== ''));

            if ($nonEmpty === []) {
                continue; // blank row
            }

            // Column header row.
            if ($this->isColumnHeaderRow($texts)) {
                continue;
            }

            // Single-cell row => in-table subcategory title ("أ- تمارين النفخ والسحب").
            if (count($cells) === 1 || (count($nonEmpty) === 1 && $this->looksLikeSubcategoryRow($texts))) {
                [$letter, $name] = $this->splitSubcategoryTitle($nonEmpty[0]);
                $this->subcategory = $name;
                $subLetter = $letter;
                continue;
            }

            $code = $this->normalizeCode($this->flatten($texts[0] ?? ''));
            $goalText = $this->flatten($texts[1] ?? '');
            $training = $texts[2] ?? '';
            $general = $texts[3] ?? '';

            if ($goalText === '') {
                continue; // empty goal text rows are skipped by spec
            }

            $out[] = $this->buildGoal($code, $goalText, $training, $general, $subLetter);
        }

        return $out;
    }

    private function isColumnHeaderRow(array $texts): bool
    {
        $first = $this->normalizeArabic($texts[0] ?? '');
        $second = $this->normalizeArabic($texts[1] ?? '');

        return $first === 'م' && str_contains($second, 'الاهداف');
    }

    private function looksLikeSubcategoryRow(array $texts): bool
    {
        // One filled cell in a multi-cell row with no code and no goal pairing.
        return ($texts[0] ?? '') !== '' && ($texts[1] ?? '') === '' && ($texts[2] ?? '') === '';
    }

    /** @return array{0:?string,1:string} [letter, name] */
    private function splitSubcategoryTitle(string $title): array
    {
        $title = $this->flatten($title);
        if (preg_match('/^\s*(\S{1,3}?)\s*[-–:]\s*(.+)$/u', $title, $m)) {
            return [$this->foldLetter($m[1]), trim($m[2])];
        }

        return [null, $title];
    }

    /**
     * @param  array<string,mixed>  $extra
     * @return array<string,mixed>
     */
    private function buildGoal(string $code, string $goalText, string $training, string $general, ?string $subLetter): array
    {
        $reasons = [];

        if ($this->domain === null) {
            $reasons[] = 'no_domain_header';
        }

        if ($code === '') {
            $reasons[] = 'missing_code';
        } else {
            $reasons = array_merge($reasons, $this->validateCode($code, $subLetter));
        }

        if (preg_match('/(\.{3}|…)\s*$/u', $goalText)) {
            $reasons[] = 'goal_text_ends_with_ellipsis';
        }

        $goal = [
            'domain' => $this->domain,
            'level' => $this->level,
            'subcategory' => $this->subcategory,
            'printed_code' => $code !== '' ? $code : null,
            'goal_text' => $goalText,
            'training_elements' => $training !== '' ? $training : null,
            'generalization' => $general !== '' ? $general : null,
        ];
        $goal['fingerprint'] = $this->fingerprint($goal);
        $goal['needs_review'] = $reasons !== [];
        $goal['review_reason'] = $reasons !== [] ? implode(',', $reasons) : null;

        return $goal;
    }

    /** @return string[] review reasons */
    private function validateCode(string $code, ?string $subLetter): array
    {
        $reasons = [];

        if (! preg_match('/^(\d+)-(\S+)$/u', $code, $m)) {
            return ['unparsable_code'];
        }
        [, $prefix, $rest] = $m;

        $expected = self::EXPECTED_CODE_PREFIX[$this->domain] ?? null;
        if ($expected !== null && ! in_array($prefix, $expected, true)) {
            $reasons[] = 'code_prefix_mismatch';
        }

        // Letter (4-أ1, 6-ب3 ...) must agree with the subcategory when we know it.
        if (preg_match('/^(\D+)\d+$/u', $rest, $lm)) {
            $letter = $this->foldLetter($lm[1]);
            if ($this->domain === self::DOMAIN_PRELANGUAGE) {
                $expectedSub = self::PRELANGUAGE_CODE_LETTERS[$letter] ?? null;
                if ($expectedSub === null || $this->subcategory === null || $expectedSub !== $this->subcategory) {
                    $reasons[] = 'code_letter_subcategory_mismatch';
                }
            } elseif ($this->domain === self::DOMAIN_ARTICULATORS && $subLetter !== null && $letter !== $subLetter) {
                $reasons[] = 'code_letter_subcategory_mismatch';
            }
        }

        return $reasons;
    }

    /**
     * Flags repeated fingerprints and repeated (domain, code) pairs.
     *
     * @param  array<int,array<string,mixed>>  $goals
     * @return array<int,array<string,mixed>>
     */
    private function flagDuplicates(array $goals): array
    {
        $byFingerprint = [];
        $byCode = [];
        foreach ($goals as $g) {
            $byFingerprint[$g['fingerprint']] = ($byFingerprint[$g['fingerprint']] ?? 0) + 1;
            if ($g['printed_code'] !== null) {
                $k = $g['domain'].'|'.$g['printed_code'];
                $byCode[$k] = ($byCode[$k] ?? 0) + 1;
            }
        }

        foreach ($goals as &$g) {
            $reasons = $g['review_reason'] ? explode(',', $g['review_reason']) : [];
            if ($byFingerprint[$g['fingerprint']] > 1) {
                $reasons[] = 'duplicate_fingerprint';
            }
            if ($g['printed_code'] !== null && $byCode[$g['domain'].'|'.$g['printed_code']] > 1) {
                $reasons[] = 'duplicate_code_in_domain';
            }
            $g['needs_review'] = $reasons !== [];
            $g['review_reason'] = $reasons !== [] ? implode(',', array_unique($reasons)) : null;
        }
        unset($g);

        return $goals;
    }

    // ------------------------------------------------------------------
    // Summary
    // ------------------------------------------------------------------

    /**
     * @param  array<int,array<string,mixed>>  $goals
     * @return array<string,mixed>
     */
    private function summarize(array $goals): array
    {
        $byDomain = [];
        $byDomainLevel = [];
        $review = [];
        $withoutCode = 0;

        foreach ($goals as $g) {
            $d = $g['domain'] ?? '(بدون مجال)';
            $byDomain[$d] = ($byDomain[$d] ?? 0) + 1;
            $l = $g['level'] ?? '(بدون مستوى)';
            $byDomainLevel[$d][$l] = ($byDomainLevel[$d][$l] ?? 0) + 1;

            if ($g['printed_code'] === null) {
                $withoutCode++;
            }
            if ($g['needs_review']) {
                $review[] = [
                    'domain' => $g['domain'],
                    'level' => $g['level'],
                    'subcategory' => $g['subcategory'],
                    'printed_code' => $g['printed_code'],
                    'goal_text' => $g['goal_text'],
                    'fingerprint' => $g['fingerprint'],
                    'review_reason' => $g['review_reason'],
                ];
            }
        }

        return [
            'by_domain' => $byDomain,
            'by_domain_level' => $byDomainLevel,
            'total' => count($goals),
            'needs_review' => $review,
            'without_code' => $withoutCode,
        ];
    }

    // ------------------------------------------------------------------
    // Fingerprint / text helpers
    // ------------------------------------------------------------------

    /**
     * Stable sha1 over normalized domain + level + subcategory + code + goal text.
     * Normalization (diacritics, alef/ya/ta folding, whitespace) keeps it stable
     * against trivial typing differences between document revisions.
     *
     * @param  array<string,mixed>  $goal
     */
    public function fingerprint(array $goal): string
    {
        $parts = [
            $goal['domain'] ?? '',
            $goal['level'] ?? '',
            $goal['subcategory'] ?? '',
            $goal['printed_code'] ?? '',
            $goal['goal_text'] ?? '',
        ];

        return sha1(implode('|', array_map(fn ($p) => $this->normalizeArabic((string) $p), $parts)));
    }

    /** Fold Arabic orthographic variants for matching/hashing (never for storage). */
    public function normalizeArabic(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text); // tashkeel + tatweel
        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و',
            'ة' => 'ه',
        ]);
        $text = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{00A0}]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim(mb_strtolower($text));
    }

    /** Folds a code/subcategory letter (جـ -> ج, هـ -> ه, أ -> ا). */
    private function foldLetter(string $letter): string
    {
        return $this->normalizeArabic($letter);
    }

    private function normalizeCode(string $code): string
    {
        $code = preg_replace('/\s+/u', '', $code);
        $code = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $code);

        return trim($code);
    }

    private function flatten(string $text): string
    {
        return $this->cleanInline($text);
    }

    private function cleanInline(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{00A0}]/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Cell text: paragraphs/line-breaks kept as "\n", blank lines dropped,
     * inner whitespace collapsed.
     */
    private function cellText(AbstractContainer $cell): string
    {
        $lines = [];
        foreach ($cell->getElements() as $el) {
            $t = $el instanceof TextBreak ? '' : $this->elementText($el);
            foreach (preg_split('/\R/u', $t) as $line) {
                $line = $this->cleanInline($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function elementText(object $element): string
    {
        if ($element instanceof Text) {
            return $element->getText();
        }
        if ($element instanceof TextBreak) {
            return "\n";
        }
        if ($element instanceof AbstractContainer) {
            $s = '';
            foreach ($element->getElements() as $child) {
                $s .= $this->elementText($child);
            }

            return $s;
        }
        if (method_exists($element, 'getText')) {
            $t = $element->getText();

            return is_string($t) ? $t : '';
        }

        return '';
    }
}
