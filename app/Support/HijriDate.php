<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use IntlDateFormatter;

class HijriDate
{
    /**
     * Convert a Gregorian date to Hijri (Umm al-Qura) formatted Y/m/d.
     */
    public static function toHijri(?CarbonInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if (self::intlAvailable()) {
            $formatter = new IntlDateFormatter(
                'en_US@calendar=islamic-umalqura',
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                'UTC',
                IntlDateFormatter::TRADITIONAL,
                'yyyy/MM/dd'
            );

            // Use the calendar date only, independent of time zone.
            $utc = Carbon::create($date->year, $date->month, $date->day, 12, 0, 0, 'UTC');
            $result = $formatter->format($utc->getTimestamp());

            if ($result !== false) {
                return $result;
            }
        }

        return self::fallback($date);
    }

    /**
     * Display a stored Hijri string with the Arabic suffix, e.g. "1448/05/10 هـ".
     */
    public static function formatDisplay(?string $hijri): string
    {
        $hijri = trim((string) $hijri);

        return $hijri === '' ? '' : $hijri.' هـ';
    }

    public static function todayHijri(): string
    {
        return (string) self::toHijri(Carbon::today());
    }

    protected static function intlAvailable(): bool
    {
        return class_exists(IntlDateFormatter::class);
    }

    /**
     * Fallback when ext-intl is missing: the arithmetical (tabular, civil) Islamic
     * calendar. May differ from Umm al-Qura by about +/- 1 day.
     */
    protected static function fallback(CarbonInterface $date): string
    {
        $y = $date->year;
        $m = $date->month;
        $d = $date->day;

        // Gregorian -> Julian Day Number
        $a = intdiv(14 - $m, 12);
        $yy = $y + 4800 - $a;
        $mm = $m + 12 * $a - 3;
        $jdn = $d + intdiv(153 * $mm + 2, 5) + 365 * $yy + intdiv($yy, 4) - intdiv($yy, 100) + intdiv($yy, 400) - 32045;

        // JDN -> civil Islamic (epoch 1948440)
        $l = $jdn - 1948440 + 10632;
        $n = intdiv($l - 1, 10631);
        $l = $l - 10631 * $n + 354;
        $j = intdiv(10985 - $l, 5316) * intdiv(50 * $l, 17719) + intdiv($l, 5670) * intdiv(43 * $l, 15238);
        $l = $l - intdiv(30 - $j, 15) * intdiv(17719 * $j, 50) - intdiv($j, 16) * intdiv(15238 * $j, 43) + 29;
        $hm = intdiv(24 * $l, 709);
        $hd = $l - intdiv(709 * $hm, 24);
        $hy = 30 * $n + $j - 30;

        return sprintf('%04d/%02d/%02d', $hy, $hm, $hd);
    }
}
