<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    public const STOCK_LABELS = [
        'in_stock' => 'Skladem',
        'last_pieces' => 'Poslední kusy',
        'on_order' => 'Na objednávku',
    ];

    public const BADGE_LABELS = [
        'bestseller' => 'Bestseller',
        'novinka' => 'Novinka',
        'posledni-kusy' => 'Poslední kusy',
    ];

    protected $fillable = [
        'category_id', 'product_collection_id', 'slug', 'name', 'subtitle', 'description',
        'price', 'rrp', 'stock', 'stock_qty', 'badge', 'dimensions', 'material',
        'dishwasher', 'microwave', 'set_contents', 'options', 'images', 'is_featured', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'rrp' => 'integer',
            'stock_qty' => 'integer',
            'dishwasher' => 'boolean',
            'microwave' => 'boolean',
            'is_featured' => 'boolean',
            'set_contents' => 'array',
            'options' => 'array',
            'images' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Vyhledávání bez ohledu na diakritiku a velikost písmen (SQLite LIKE diakritiku nezná).
        static::saving(function (Product $p) {
            $p->search_text = self::normalize(implode(' ', array_filter([
                $p->name,
                $p->subtitle,
                $p->collection?->name,
                $p->category?->name,
            ])));
        });
    }

    public static function normalize(string $s): string
    {
        return Str::lower(Str::ascii($s));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'product_collection_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Sleva proti doporučené ceně v % (zaokrouhleno dolů), nebo null. */
    public function discountPercent(): ?int
    {
        if (! $this->rrp || $this->rrp <= $this->price) {
            return null;
        }

        return (int) floor(100 * ($this->rrp - $this->price) / $this->rrp);
    }

    public function mainImage(): ?array
    {
        return $this->images[0] ?? null;
    }

    public function stockLabel(): string
    {
        return self::STOCK_LABELS[$this->stock] ?? $this->stock;
    }

    public function badgeLabel(): ?string
    {
        return $this->badge ? (self::BADGE_LABELS[$this->badge] ?? $this->badge) : null;
    }

    /** Volba balení podle kódu, nebo výchozí (1 ks za cenu produktu). */
    public function option(?string $code): array
    {
        foreach ($this->options ?? [] as $o) {
            if ((string) $o['code'] === (string) $code) {
                return $o;
            }
        }

        return ['code' => '', 'label' => null, 'units' => 1, 'price' => $this->price];
    }
}
