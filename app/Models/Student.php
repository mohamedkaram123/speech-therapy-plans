<?php

namespace App\Models;

use App\Enums\StudentPeriod;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'birth_date',
        'birth_date_hijri',
        'teacher_id',
        'period',
        'diagnosis',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'period' => StudentPeriod::class,
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function therapyPlans(): HasMany
    {
        return $this->hasMany(TherapyPlan::class);
    }
}
