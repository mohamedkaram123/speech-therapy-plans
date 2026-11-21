<?php

namespace App\Models;

use Database\Factories\BankGoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BankGoal extends Model
{
    /** @use HasFactory<BankGoalFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'goal_domain_id',
        'level',
        'subcategory',
        'printed_code',
        'goal_text',
        'training_elements',
        'generalization',
        'fingerprint',
        'needs_review',
        'review_reason',
    ];

    protected function casts(): array
    {
        return ['needs_review' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (BankGoal $goal) {
            $goal->uuid ??= (string) Str::uuid();
        });
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(GoalDomain::class, 'goal_domain_id');
    }

    public function planGoals(): HasMany
    {
        return $this->hasMany(TherapyPlanGoal::class);
    }
}
