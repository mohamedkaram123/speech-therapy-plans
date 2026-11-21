<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">الملف الشخصي</h1>
    </x-slot>

    <div class="space-y-6 max-w-3xl">
        <div class="card card-body">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card card-body">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
