<?php

namespace App\Http\Controllers;

use App\Enums\StudentPeriod;
use App\Enums\UserRole;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Student;
use App\Models\User;
use App\Support\HijriDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);
        $user = $request->user();

        $students = Student::query()
            ->with(['teacher', 'therapyPlans' => fn ($q) => $q->latest('id')])
            ->when($user->isTeacher(), fn ($q) => $q->where('teacher_id', $user->id))
            ->when($user->isSupervisor() && $request->filled('teacher_id'), fn ($q) => $q->where('teacher_id', $request->integer('teacher_id')))
            ->when($request->filled('period') && StudentPeriod::tryFrom((string) $request->input('period')),
                fn ($q) => $q->where('period', $request->input('period')))
            ->when($request->filled('q'), fn ($q) => $q->where('full_name', 'like', '%'.trim($request->string('q')).'%'))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'teachers' => $user->isSupervisor() ? $this->teachers() : collect(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);

        return view('students.create', ['student' => new Student, 'teachers' => $this->teachers()]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        Student::create($this->prepared($request->validated()));

        return redirect()->route('students.index')->with('success', 'تمت إضافة الطالب بنجاح.');
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        $student->load(['teacher', 'therapyPlans' => fn ($q) => $q->with('evaluation')->latest('id')]);

        return view('students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        return view('students.edit', ['student' => $student, 'teachers' => $this->teachers($student->teacher_id)]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $student->update($this->prepared($request->validated()));

        return redirect()->route('students.index')->with('success', 'تم تحديث بيانات الطالب.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->delete();

        return redirect()->route('students.index')->with('success', 'تم حذف الطالب (الخطط السابقة محفوظة).');
    }

    /** Fills the Hijri birth date from the Gregorian one when only that was entered. */
    private function prepared(array $data): array
    {
        if (empty($data['birth_date_hijri']) && ! empty($data['birth_date'])) {
            $data['birth_date_hijri'] = HijriDate::toHijri(\Illuminate\Support\Carbon::parse($data['birth_date']));
        }

        return $data;
    }

    private function teachers(?int $includeId = null)
    {
        return User::query()
            ->where('role', UserRole::Teacher->value)
            ->where(fn ($q) => $q->where('is_active', true)->when($includeId, fn ($q) => $q->orWhere('id', $includeId)))
            ->orderBy('name')
            ->get();
    }
}
