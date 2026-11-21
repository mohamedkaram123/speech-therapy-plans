<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EvaluationPdfController;
use App\Http\Controllers\GoalsBankController;
use App\Http\Controllers\PlanPdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TherapyPlanController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Supervisor: teachers
    Route::resource('teachers', TeacherController::class)->except(['show', 'destroy']);
    Route::patch('teachers/{teacher}/toggle', [TeacherController::class, 'toggle'])->name('teachers.toggle');

    // Students (supervisor: full CRUD, teacher: own students only — enforced by StudentPolicy)
    Route::resource('students', StudentController::class);
    Route::get('students/{student}/plans/create', [TherapyPlanController::class, 'create'])->name('plans.create');

    // Goals bank
    Route::get('goals-bank', [GoalsBankController::class, 'index'])->name('goals-bank.index');
    Route::get('goals-bank/search.json', [GoalsBankController::class, 'search'])->name('goals-bank.search');

    // Therapy plans
    Route::get('plans', [TherapyPlanController::class, 'index'])->name('plans.index');
    Route::post('plans', [TherapyPlanController::class, 'store'])->name('plans.store');
    Route::get('plans/{plan}', [TherapyPlanController::class, 'show'])->name('plans.show');
    Route::get('plans/{plan}/edit', [TherapyPlanController::class, 'edit'])->name('plans.edit');
    Route::put('plans/{plan}', [TherapyPlanController::class, 'update'])->name('plans.update');
    Route::post('plans/{plan}/submit', [TherapyPlanController::class, 'submit'])->name('plans.submit');
    Route::post('plans/{plan}/return', [TherapyPlanController::class, 'returnPlan'])->name('plans.return');
    Route::post('plans/{plan}/approve', [TherapyPlanController::class, 'approve'])->name('plans.approve');
    Route::get('plans/{plan}/pdf', PlanPdfController::class)->name('plans.pdf');

    // Evaluations
    Route::get('evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::post('plans/{plan}/evaluation', [EvaluationController::class, 'start'])->name('evaluations.start');
    Route::get('evaluations/{evaluation}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::get('evaluations/{evaluation}/edit', [EvaluationController::class, 'edit'])->name('evaluations.edit');
    Route::put('evaluations/{evaluation}', [EvaluationController::class, 'update'])->name('evaluations.update');
    Route::post('evaluations/{evaluation}/submit', [EvaluationController::class, 'submit'])->name('evaluations.submit');
    Route::post('evaluations/{evaluation}/return', [EvaluationController::class, 'returnEvaluation'])->name('evaluations.return');
    Route::post('evaluations/{evaluation}/approve', [EvaluationController::class, 'approve'])->name('evaluations.approve');
    Route::get('evaluations/{evaluation}/pdf', EvaluationPdfController::class)->name('evaluations.pdf');

    // Supervisor: plan receipt tracking sheet
    Route::get('tracking', [TrackingController::class, 'index'])->name('tracking.index');
});

require __DIR__.'/auth.php';
