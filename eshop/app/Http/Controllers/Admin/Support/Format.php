<?php

namespace App\Http\Controllers\Admin\Support;

use Carbon\CarbonInterface;

/**
 * Formátování pro administraci: částky v Kč, data v pražském čase, české tvary slov.
 * Volá se z Blade šablon admin/** (@use).
 */
final class Format
{
    public const TIMEZONE = 'Europe/Prague';

    private const NBSP = "\u{00A0}";

    /** 1290 → „1 290 Kč“ (nezalomitelné mezery). */
    public static function money(?int $amount): string
    {
        return number_format((int) $amount, 0, ',', self::NBSP).self::NBSP.config('shop.currency', 'Kč');
    }

    /** Cena dopravy: 0 → „zdarma“. */
    public static function shipping(?int $amount): string
    {
        return (int) $amount === 0 ? 'zdarma' : self::money($amount);
    }

    public static function local(?CarbonInterface $date): ?CarbonInterface
    {
        return $date?->copy()->setTimezone(self::TIMEZONE);
    }

    /** „18. 11. 2026“ */
    public static function date(?CarbonInterface $date): string
    {
        return $date ? self::local($date)->format('j.'.self::NBSP.'n.'.self::NBSP.'Y') : '';
    }

    /** „14:05“ */
    public static function time(?CarbonInterface $date): string
    {
        return $date ? self::local($date)->format('G:i') : '';
    }

    /** „18. 11. 2026 v 14:05“ */
    public static function dateTime(?CarbonInterface $date): string
    {
        return $date ? self::date($date).' v '.self::time($date) : '';
    }

    /** „Dnes“, „Včera“, jinak datum – den podle pražského času. */
    public static function day(?CarbonInterface $date): string
    {
        if (! $date) {
            return '';
        }

        $local = self::local($date)->startOfDay();
        $today = now(self::TIMEZONE)->startOfDay();

        if ($local->equalTo($today)) {
            return 'Dnes';
        }

        if ($local->equalTo($today->copy()->subDay())) {
            return 'Včera';
        }

        return self::date($date);
    }

    /** České tvary: plural(3, 'položka', 'položky', 'položek') → „3 položky“. */
    public static function plural(int $count, string $one, string $few, string $many, bool $withNumber = true): string
    {
        $word = match (true) {
            $count === 1 => $one,
            $count >= 2 && $count <= 4 => $few,
            default => $many,
        };

        return $withNumber ? number_format($count, 0, ',', self::NBSP).self::NBSP.$word : $word;
    }

    /** Odkaz tel: bez mezer, s předvolbou. */
    public static function telHref(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', $phone) ?? '';

        return 'tel:'.$digits;
    }

    /** mailto: s předmětem „Poptávka VB-2026-0001“ (mezery jako %20, ne +). */
    public static function replyHref(string $email, string $number): string
    {
        return 'mailto:'.$email.'?subject='.rawurlencode('Poptávka '.$number);
    }

    /** „Zásilkovna – výdejní místo“ → „Zásilkovna“ (do úzkého sloupce). */
    public static function deliveryShort(?string $label): string
    {
        return trim(explode(' – ', (string) $label)[0]);
    }

    public static function country(?string $code): string
    {
        return match (strtoupper((string) $code)) {
            'CZ', '' => 'Česko',
            'SK' => 'Slovensko',
            'DE' => 'Německo',
            'AT' => 'Rakousko',
            'PL' => 'Polsko',
            default => strtoupper((string) $code),
        };
    }

    /** Obrázek položky: absolutní URL nechá, relativní cestu převede přes asset(). */
    public static function imageUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        return preg_match('#^(https?:)?//|^/|^data:#', $url) === 1 ? $url : asset($url);
    }

    /** Sleva v % proti doporučené ceně (zaokrouhleno dolů), nebo null. */
    public static function discountPercent(?int $price, ?int $rrp): ?int
    {
        if (! $rrp || ! $price || $rrp <= $price) {
            return null;
        }

        return (int) floor(100 * ($rrp - $price) / $rrp);
    }
}
