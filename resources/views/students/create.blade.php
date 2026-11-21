<x-app-layout title="إضافة طالب">
    <x-slot name="header"><h1 class="text-xl font-bold text-slate-800">إضافة طالب</h1></x-slot>
    <form method="POST" action="{{ route('students.store') }}" class="card card-body max-w-3xl">
        @csrf
        @include('students._form')
    </form>
</x-app-layout>
