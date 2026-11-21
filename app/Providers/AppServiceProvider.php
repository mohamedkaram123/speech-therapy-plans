<?php

namespace App\Providers;

use App\Models\BankGoal;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\TherapyPlan;
use App\Policies\BankGoalPolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TherapyPlanPolicy;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(TherapyPlan::class, TherapyPlanPolicy::class);
        Gate::policy(Evaluation::class, EvaluationPolicy::class);
        Gate::policy(BankGoal::class, BankGoalPolicy::class);

        // Supervisor-only areas that have no model of their own.
        Gate::define('manage-teachers', fn (User $u) => $u->is_active && $u->isSupervisor());
        Gate::define('view-tracking', fn (User $u) => $u->is_active && $u->isSupervisor());

        // Route parameter names: plan, evaluation, student, teacher.
        Route::model('plan', TherapyPlan::class);
        Route::model('evaluation', Evaluation::class);
        Route::model('student', Student::class);
        Route::bind('teacher', fn (string $value) => User::query()
            ->where('role', UserRole::Teacher->value)->findOrFail($value));
    }
}
