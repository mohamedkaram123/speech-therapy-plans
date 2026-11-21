<?php

namespace App\Models;

use App\Enums\ReviewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapyPlanReview extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['therapy_plan_id', 'user_id', 'action', 'note'];

    protected function casts(): array
    {
        return [
            'action' => ReviewAction::class,
            'created_at' => 'datetime',
        ];
    }

    public function therapyPlan(): BelongsTo
    {
        return $this->belongsTo(TherapyPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
