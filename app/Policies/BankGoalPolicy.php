<?php

namespace App\Policies;

use App\Models\BankGoal;
use App\Models\User;

class BankGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, BankGoal $goal): bool
    {
        return $user->is_active;
    }

    /** Importing / editing / reviewing the bank is supervisor-only. */
    public function import(User $user): bool
    {
        return $user->is_active && $user->isSupervisor();
    }

    public function create(User $user): bool
    {
        return $this->import($user);
    }

    public function update(User $user, BankGoal $goal): bool
    {
        return $this->import($user);
    }

    public function delete(User $user, BankGoal $goal): bool
    {
        return $this->import($user);
    }
}
