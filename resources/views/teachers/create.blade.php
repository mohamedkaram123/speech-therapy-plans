<x-app-layout title="إضافة معلم">
    <x-slot name="header"><h1 class="text-xl font-bold text-slate-800">إضافة معلم</h1></x-slot>
    <form method="POST" action="{{ route('teachers.store') }}" class="card card-body max-w-3xl">
        @csrf
        @include('teachers._form')
    </form>
</x-app-layout>
