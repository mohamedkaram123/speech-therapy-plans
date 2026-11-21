<?php

namespace App\Http\Requests\Concerns;

/**
 * Shared helpers for form requests: Arabic-Indic digit normalisation
 * (so ١٤٤٧/٠٣/١٥ validates like 1447/03/15) and a Hijri date rule.
 */
trait NormalizesInput
{
    /** Hijri date such as 1447/03/15 or 1447-3-15. */
    protected const HIJRI_REGEX = '/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/';

    protected function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    /** @param list<string> $keys */
    protected function normalizeDigitsFor(array $keys): void
    {
        $merge = [];
        foreach ($keys as $key) {
            $v = $this->input($key);
            if (is_string($v)) {
                $merge[$key] = trim($this->normalizeDigits($v));
            }
        }
        $this->merge($merge);
    }

    protected function hijriToSortable(?string $value): ?int
    {
        if ($value === null || ! preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $value, $m)) {
            return null;
        }

        return (int) sprintf('%04d%02d%02d', $m[1], $m[2], $m[3]);
    }
}
