<?php

namespace App\Models;

use App\Enums\ReviewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationReview extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['evaluation_id', 'user_id', 'action', 'note'];

    protected function casts(): array
    {
        return [
            'action' => ReviewAction::class,
            'created_at' => 'datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
