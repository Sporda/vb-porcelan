<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Společná data katalogu pro hlavičku, úvod a výpisy (v rámci jednoho požadavku se počítají jednou).
 */
class Catalog
{
    /** Výprodej = sleva aspoň tolik procent z doporučené ceny. */
    public const SALE_PERCENT = 40;

    private ?Collection $categories = null;

    private ?array $stats = null;

    /** Kategorie s počtem produktů, podle pořadí. Při chybě DB (stránka 500) prázdný seznam. */
    public function categories(): Collection
    {
        if ($this->categories !== null) {
            return $this->categories;
        }

        try {
            return $this->categories = Category::query()->withCount('products')->orderBy('sort')->orderBy('name')->get();
        } catch (Throwable) {
            return $this->categories = collect();
        }
    }

    /** Podmínka „sleva aspoň $percent %“ (stejné zaokrouhlení dolů jako Product::discountPercent()). */
    public static function whereDiscountAtLeast(Builder $q, int $percent): Builder
    {
        return $q->whereNotNull('rrp')->where('rrp', '>', 0)->whereRaw('(rrp - price) * 100 >= ? * rrp', [$percent]);
    }

    public static function whereAvailable(Builder $q): Builder
    {
        return $q->whereIn('stock', ['in_stock', 'last_pieces']);
    }

    /** Souhrnná čísla pro úvod a texty (počty, nejvyšší a průměrná sleva). */
    public function stats(): array
    {
        if ($this->stats !== null) {
            return $this->stats;
        }

        try {
            $products = Product::query()->get(['id', 'price', 'rrp', 'stock', 'product_collection_id']);
            $discounts = $products->map(fn (Product $p) => $p->discountPercent())->filter();

            return $this->stats = [
                'products' => $products->count(),
                'available' => $products->whereIn('stock', ['in_stock', 'last_pieces'])->count(),
                'collections' => $products->pluck('product_collection_id')->filter()->unique()->count(),
                'max_discount' => (int) ($discounts->max() ?? 0),
                'avg_discount' => (int) round((float) ($discounts->avg() ?? 0)),
                'sale' => $discounts->filter(fn ($d) => $d >= self::SALE_PERCENT)->count(),
            ];
        } catch (Throwable) {
            return $this->stats = ['products' => 0, 'available' => 0, 'collections' => 0, 'max_discount' => 0, 'avg_discount' => 0, 'sale' => 0];
        }
    }

    /** Kolekce s počtem produktů a nejnižší cenou (jen ty, které něco obsahují). */
    public function collections(): Collection
    {
        return ProductCollection::query()
            ->withCount('products')
            ->withMin('products', 'price')
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->filter(fn (ProductCollection $c) => $c->products_count > 0)
            ->values();
    }
}
