<x-guest-layout>
    <h1 class="text-lg font-bold text-slate-800 mb-4">تسجيل الدخول</h1>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="البريد الإلكتروني" />
            <x-text-input id="email" class="mt-1" type="email" name="email" dir="ltr" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="كلمة المرور" />
            <x-text-input id="password" class="mt-1" type="password" name="password" dir="ltr" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-teal-700 shadow-sm focus:ring-teal-600" name="remember">
            <span class="text-sm text-slate-600">تذكرني</span>
        </label>

        <x-primary-button class="w-full">تسجيل الدخول</x-primary-button>

        <p class="text-xs text-slate-500 text-center">الحسابات تُنشأ من قبل المشرف فقط. للمساعدة تواصل مع المشرف.</p>
    </form>
</x-guest-layout>
