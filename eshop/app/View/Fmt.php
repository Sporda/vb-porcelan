<?php

namespace App\View;

/**
 * České formátování pro šablony: ceny „1 290 Kč“ (nezlomitelné mezery) a tvary podle čísla
 * („1 kus, 2 kusy, 5 kusů“). V Blade: @price($n), @plural($n, 'kus', 'kusy', 'kusů').
 */
class Fmt
{
    public const NBSP = "\u{00A0}";

    public static function number(int|float $n): string
    {
        return number_format((float) $n, 0, ',', self::NBSP);
    }

    public static function price(int|float|null $n): string
    {
        return self::number((int) round((float) $n)).self::NBSP.config('shop.currency', 'Kč');
    }

    /** Tvar slova podle čísla: 1 → $one, 2–4 → $few, jinak $many. */
    public static function plural(int $n, string $one, string $few, string $many): string
    {
        $abs = abs($n);

        return match (true) {
            $abs === 1 => $one,
            $abs >= 2 && $abs <= 4 => $few,
            default => $many,
        };
    }

    /** Číslo i se slovem: „5 produktů“. */
    public static function count(int $n, string $one, string $few, string $many): string
    {
        return self::number($n).self::NBSP.self::plural($n, $one, $few, $many);
    }

    /** Pexels (a podobné) URL s jinou šířkou: …&w=600 → …&w=$w. */
    public static function img(?string $url, int $w): string
    {
        if (! $url) {
            return '';
        }
        if (preg_match('/([?&])w=\d+/', $url)) {
            return (string) preg_replace('/([?&])w=\d+/', '${1}w='.$w, $url);
        }

        return str_contains($url, 'images.pexels.com') ? $url.(str_contains($url, '?') ? '&' : '?').'w='.$w : $url;
    }

    /** URL souboru z public/ s verzí podle data změny (cache busting bez buildu). */
    public static function asset(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }

    /** srcset pro dané šířky (jen u URL, které šířku umí). */
    public static function srcset(?string $url, array $widths): string
    {
        if (! $url || ! str_contains($url, 'images.pexels.com')) {
            return '';
        }

        return implode(', ', array_map(fn (int $w) => self::img($url, $w).' '.$w.'w', $widths));
    }
}
