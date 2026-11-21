<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manage-teachers');

        $teachers = User::query()
            ->where('role', UserRole::Teacher->value)
            ->withCount(['students', 'therapyPlans'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.trim($request->string('q')).'%';
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        $this->authorize('manage-teachers');

        return view('teachers.create');
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $this->authorize('manage-teachers');

        $data = $request->validated();
        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Teacher,
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('teachers.index')->with('success', 'تمت إضافة المعلم بنجاح.');
    }

    public function edit(User $teacher): View
    {
        $this->authorize('manage-teachers');

        return view('teachers.edit', compact('teacher'));
    }

    public function update(UpdateTeacherRequest $request, User $teacher): RedirectResponse
    {
        $this->authorize('manage-teachers');

        $data = $request->validated();
        $teacher->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! empty($data['password'])) {
            $teacher->password = $data['password'];
        }
        $teacher->save();

        return redirect()->route('teachers.index')->with('success', 'تم تحديث بيانات المعلم.');
    }

    public function toggle(User $teacher): RedirectResponse
    {
        $this->authorize('manage-teachers');

        $teacher->update(['is_active' => ! $teacher->is_active]);

        return back()->with('success', $teacher->is_active ? 'تم تفعيل حساب المعلم.' : 'تم تعطيل حساب المعلم.');
    }
}
