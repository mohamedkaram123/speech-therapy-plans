<x-app-layout title="تعديل معلم">
    <x-slot name="header"><h1 class="text-xl font-bold text-slate-800">تعديل المعلم: {{ $teacher->name }}</h1></x-slot>
    <form method="POST" action="{{ route('teachers.update', $teacher) }}" class="card card-body max-w-3xl">
        @csrf @method('PUT')
        @include('teachers._form')
    </form>
</x-app-layout>
