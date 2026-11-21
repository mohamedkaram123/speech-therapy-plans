<x-app-layout title="تعديل طالب">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-800">تعديل الطالب: {{ $student->full_name }}</h1>
        <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('حذف هذا الطالب؟ الخطط السابقة تبقى محفوظة.')">
            @csrf @method('DELETE')
            <button class="btn-danger btn-sm">حذف الطالب</button>
        </form>
    </x-slot>
    <form method="POST" action="{{ route('students.update', $student) }}" class="card card-body max-w-3xl">
        @csrf @method('PUT')
        @include('students._form')
    </form>
</x-app-layout>
