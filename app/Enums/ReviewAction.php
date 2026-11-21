<?php

namespace App\Enums;

enum ReviewAction: string
{
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'إرسال للمراجعة',
            self::Returned => 'إعادة للتعديل',
            self::Approved => 'اعتماد',
        };
    }
}
