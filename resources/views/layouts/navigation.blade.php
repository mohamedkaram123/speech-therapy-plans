@php
    $user = auth()->user();
    $isSupervisor = $user->isSupervisor();
    $links = $isSupervisor
        ? [
            ['dashboard', 'لوحة التحكم', 'dashboard'],
            ['teachers.index', 'المعلمون', 'teachers.*'],
            ['students.index', 'الطلاب', 'students.*'],
            ['plans.index', 'الخطط', 'plans.*'],
            ['evaluations.index', 'التقييمات', 'evaluations.*'],
            ['goals-bank.index', 'بنك الأهداف', 'goals-bank.*'],
            ['tracking.index', 'كشف الاستلام', 'tracking.*'],
        ]
        : [
            ['dashboard', 'لوحة التحكم', 'dashboard'],
            ['students.index', 'طلابي', 'students.*'],
            ['plans.index', 'خططي', 'plans.*'],
            ['evaluations.index', 'تقييماتي', 'evaluations.*'],
            ['goals-bank.index', 'بنك الأهداف', 'goals-bank.*'],
        ];
@endphp

<nav x-data="{ open: false, menu: false }" class="app-nav no-print">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-4 sm:gap-6">
                <a href="{{ route('dashboard') }}" class="nav-brand flex items-center gap-2">
                    <x-application-logo class="h-9 w-9" />
                    <span class="hidden md:flex flex-col leading-tight">
                        <span class="font-bold text-sm {{ $isSupervisor ? 'text-white' : 'text-slate-800' }}">{{ config('app.name') }}</span>
                        @if($isSupervisor)
                            <span class="text-[10px] tracking-wide text-amber-300/90">لوحة إدارة المشرف</span>
                        @else
                            <span class="text-[10px] tracking-wide text-slate-500">لوحة المعلمة</span>
                        @endif
                    </span>
                </a>

                <span class="role-chip hidden sm:inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold">
                    {{ $isSupervisor ? 'مشرف' : 'معلمة' }}
                </span>

                <div class="hidden lg:flex items-center gap-1">
                    @foreach ($links as [$route, $label, $pattern])
                        <x-nav-link :href="route($route)" :active="request()->routeIs($pattern)" :supervisor="$isSupervisor">{{ $label }}</x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden lg:flex items-center relative" @click.outside="menu = false">
                <button type="button" @click="menu = ! menu"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm {{ $isSupervisor ? 'text-slate-100 hover:bg-white/10' : 'text-slate-700 hover:bg-slate-100' }}">
                    <span class="nav-avatar flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">{{ mb_substr($user->name, 0, 1) }}</span>
                    <span class="text-start leading-tight">
                        <span class="block font-semibold">{{ $user->name }}</span>
                        <span class="block text-xs {{ $isSupervisor ? 'text-amber-200/80' : 'text-slate-500' }}">{{ $user->role->label() }}</span>
                    </span>
                </button>
                <div x-show="menu" x-cloak x-transition class="absolute end-0 top-full mt-1 w-48 rounded-lg border border-slate-200 bg-white shadow-lg py-1 z-30">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">الملف الشخصي</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="block w-full text-start px-4 py-2 text-sm text-rose-700 hover:bg-slate-50">تسجيل الخروج</button>
                    </form>
                </div>
            </div>

            <div class="flex items-center lg:hidden">
                <button @click="open = ! open"
                        class="p-2 rounded-lg {{ $isSupervisor ? 'text-slate-200 hover:bg-white/10' : 'text-slate-600 hover:bg-slate-100' }}"
                        aria-label="القائمة">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="! open" stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="open" x-cloak stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak class="lg:hidden border-t {{ $isSupervisor ? 'border-white/10 bg-slate-900' : 'border-slate-200 bg-white' }}">
        <div class="py-2">
            @foreach ($links as [$route, $label, $pattern])
                <x-responsive-nav-link :href="route($route)" :active="request()->routeIs($pattern)" :supervisor="$isSupervisor">{{ $label }}</x-responsive-nav-link>
            @endforeach
        </div>
        <div class="py-3 border-t {{ $isSupervisor ? 'border-white/10' : 'border-slate-200' }}">
            <div class="px-4 mb-2">
                <div class="font-semibold {{ $isSupervisor ? 'text-white' : 'text-slate-800' }}">{{ $user->name }}</div>
                <div class="text-sm {{ $isSupervisor ? 'text-amber-200/80' : 'text-slate-500' }}">{{ $user->role->label() }} &middot; <span dir="ltr">{{ $user->email }}</span></div>
            </div>
            <x-responsive-nav-link :href="route('profile.edit')" :supervisor="$isSupervisor">الملف الشخصي</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="block w-full px-4 py-2.5 text-start text-base text-rose-400 hover:bg-white/5">تسجيل الخروج</button>
            </form>
        </div>
    </div>
</nav>
