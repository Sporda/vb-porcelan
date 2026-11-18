<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/** Oblíbené produkty v session (seznam id, nejnovější první). */
class Favorites
{
    public const SESSION_KEY = 'shop.favorites';

    public const MAX = 100;

    /** @return list<int> */
    public function ids(): array
    {
        $ids = session()->get(self::SESSION_KEY, []);

        return is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /** Přepne produkt; vrací true, pokud je teď v oblíbených. */
    public function toggle(Product $product): bool
    {
        $ids = $this->ids();
        if (in_array($product->id, $ids, true)) {
            session()->put(self::SESSION_KEY, array_values(array_diff($ids, [$product->id])));

            return false;
        }
        array_unshift($ids, $product->id);
        session()->put(self::SESSION_KEY, array_slice($ids, 0, self::MAX));

        return true;
    }

    /** @return Collection<int, Product> */
    public function products(): Collection
    {
        $ids = $this->ids();
        if ($ids === []) {
            return collect();
        }
        $products = Product::with('collection')->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $products->get($id))->filter()->values();
    }
}
