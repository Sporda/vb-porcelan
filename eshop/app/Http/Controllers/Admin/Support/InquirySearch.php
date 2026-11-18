<?php

namespace App\Http\Controllers\Admin\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use PDO;

/**
 * Hledání v poptávkách podle čísla, jména, firmy, e-mailu a telefonu.
 * V SQLite bez ohledu na diakritiku a velikost písmen („novak“ najde „Nováková“, „čermák“ i „Čermák“)
 * a telefon podle číslic („777123“ najde „+420 777 123 456“).
 */
final class InquirySearch
{
    private const FOLD = 'admin_fold';

    private const DIGITS = 'admin_digits';

    public static function apply(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $folded = self::fold($term);
        $digits = preg_replace('/\D/', '', $term) ?? '';
        $phoneLike = strlen($digits) >= 3 && preg_match('/^[\d\s+()\/.-]+$/', $term) === 1;

        if (self::registerFunctions($query->getQuery()->getConnection())) {
            $like = '%'.self::escapeLike($folded).'%';

            return $query->where(function (Builder $w) use ($like, $digits, $phoneLike) {
                foreach (['number', 'customer_name', 'email', 'company'] as $column) {
                    $w->orWhereRaw(self::FOLD."({$column}) LIKE ? ESCAPE '\\'", [$like]);
                }
                if ($phoneLike) {
                    $w->orWhereRaw(self::DIGITS.'(phone) LIKE ?', ['%'.$digits.'%']);
                }
            });
        }

        // Jiná databáze než SQLite: obyčejné LIKE.
        $like = '%'.self::escapeLike($term).'%';

        return $query->where(function (Builder $w) use ($like, $digits, $phoneLike) {
            foreach (['number', 'customer_name', 'email', 'company', 'phone'] as $column) {
                $w->orWhere($column, 'like', $like);
            }
            if ($phoneLike) {
                $w->orWhere('phone', 'like', '%'.$digits.'%');
            }
        });
    }

    public static function fold(?string $value): string
    {
        return Str::lower(Str::ascii((string) $value));
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** Zaregistruje pomocné SQL funkce v SQLite (jednou na spojení). */
    private static function registerFunctions(ConnectionInterface $connection): bool
    {
        if (! $connection instanceof Connection || $connection->getDriverName() !== 'sqlite') {
            return false;
        }

        $pdo = $connection->getPdo();
        $fold = fn ($value) => self::fold($value === null ? '' : (string) $value);
        $digits = fn ($value) => preg_replace('/\D/', '', (string) $value) ?? '';

        // PHP 8.4+: Pdo\Sqlite::createFunction, PHP 8.3: PDO::sqliteCreateFunction
        if (method_exists($pdo, 'createFunction')) {
            $pdo->createFunction(self::FOLD, $fold, 1);
            $pdo->createFunction(self::DIGITS, $digits, 1);

            return true;
        }

        if (method_exists($pdo, 'sqliteCreateFunction')) {
            /** @var PDO $pdo */
            $pdo->sqliteCreateFunction(self::FOLD, $fold, 1);
            $pdo->sqliteCreateFunction(self::DIGITS, $digits, 1);

            return true;
        }

        return false;
    }
}
