<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Returned = 'returned';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::PendingReview => 'بانتظار المراجعة',
            self::Returned => 'معاد للتعديل',
            self::Approved => 'معتمد',
        };
    }
}
