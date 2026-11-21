<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /** Supervisors see every student; teachers see their own (the query is scoped by the controller). */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Student $student): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isSupervisor()
            || ($user->isTeacher() && (int) $student->teacher_id === (int) $user->id);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isSupervisor();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->is_active && $user->isSupervisor();
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->is_active && $user->isSupervisor();
    }

    public function restore(User $user, Student $student): bool
    {
        return $this->delete($user, $student);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return false;
    }
}
