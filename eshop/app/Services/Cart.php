<?php

namespace App\Services;

use App\Models\Product;
use App\View\Fmt;
use Illuminate\Support\Collection;

/**
 * Košík v session. Řádek = produkt + kód balení; v session je jen id, kód a množství,
 * ceny se vždy berou z databáze (Product::option()).
 */
class Cart
{
    public const SESSION_KEY = 'shop.cart';

    public const MAX_QTY = 20;

    public const MAX_LINES = 40;

    /** @var Collection<int, CartLine>|null */
    private ?Collection $lines = null;

    /** @return array<string, array{p:int,o:string,q:int}> */
    private function raw(): array
    {
        $raw = session()->get(self::SESSION_KEY, []);

        return is_array($raw) ? $raw : [];
    }

    private function store(array $raw): void
    {
        session()->put(self::SESSION_KEY, $raw);
        $this->lines = null;
    }

    public static function keyFor(int $productId, string $optionCode): string
    {
        return $optionCode === '' ? (string) $productId : $productId.'-'.$optionCode;
    }

    /** Platný kód balení: zadaný, pokud existuje, jinak první balení produktu (nebo '' u produktu bez balení). */
    public function normalizeOption(Product $product, ?string $code): string
    {
        $options = $product->options ?? [];
        if ($options === []) {
            return '';
        }
        foreach ($options as $o) {
            if ((string) $o['code'] === (string) $code) {
                return (string) $o['code'];
            }
        }

        return (string) $options[0]['code'];
    }

    public function add(Product $product, ?string $option, int $qty = 1): CartLine
    {
        $code = $this->normalizeOption($product, $option);
        $key = self::keyFor($product->id, $code);
        $raw = $this->raw();
        $current = $raw[$key]['q'] ?? 0;

        if (! isset($raw[$key]) && count($raw) >= self::MAX_LINES) {
            array_shift($raw);
        }

        $raw[$key] = ['p' => $product->id, 'o' => $code, 'q' => min(self::MAX_QTY, max(1, $current + max(1, $qty)))];
        $this->store($raw);

        return $this->line($key);
    }

    public function has(string $key): bool
    {
        return isset($this->raw()[$key]);
    }

    /** Nastaví množství; 0 a méně řádek odebere. Vrací false, když řádek v košíku není. */
    public function setQty(string $key, int $qty): bool
    {
        $raw = $this->raw();
        if (! isset($raw[$key])) {
            return false;
        }
        if ($qty <= 0) {
            unset($raw[$key]);
        } else {
            $raw[$key]['q'] = min(self::MAX_QTY, $qty);
        }
        $this->store($raw);

        return true;
    }

    public function remove(string $key): bool
    {
        $raw = $this->raw();
        if (! isset($raw[$key])) {
            return false;
        }
        unset($raw[$key]);
        $this->store($raw);

        return true;
    }

    public function clear(): void
    {
        $this->store([]);
    }

    /** @return Collection<int, CartLine> */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $raw = $this->raw();
        if ($raw === []) {
            return $this->lines = collect();
        }

        $ids = array_values(array_unique(array_map(fn ($r) => (int) ($r['p'] ?? 0), $raw)));
        $products = Product::with('collection', 'category')->whereIn('id', $ids)->get()->keyBy('id');

        $lines = collect();
        $clean = [];
        foreach ($raw as $key => $r) {
            $product = $products->get((int) ($r['p'] ?? 0));
            if (! $product) {
                continue; // produkt mezitím zmizel z katalogu
            }
            $code = $this->normalizeOption($product, (string) ($r['o'] ?? ''));
            $newKey = self::keyFor($product->id, $code);
            $qty = min(self::MAX_QTY, max(1, (int) ($r['q'] ?? 1)));
            $clean[$newKey] = ['p' => $product->id, 'o' => $code, 'q' => $qty];
            $lines->push(new CartLine($newKey, $product, $product->option($code), $qty));
        }

        if ($clean !== $raw) {
            session()->put(self::SESSION_KEY, $clean);
        }

        return $this->lines = $lines;
    }

    public function line(string $key): ?CartLine
    {
        return $this->lines()->first(fn (CartLine $l) => $l->key === $key);
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    /** Počet kusů (součet množství) – číslo v hlavičce. */
    public function count(): int
    {
        if ($this->raw() === []) {
            return 0;
        }

        return (int) $this->lines()->sum(fn (CartLine $l) => $l->qty);
    }

    public function itemsTotal(): int
    {
        return (int) $this->lines()->sum(fn (CartLine $l) => $l->lineTotal());
    }

    public function rrpTotal(): int
    {
        return (int) $this->lines()->sum(fn (CartLine $l) => $l->lineRrp());
    }

    public function saving(): int
    {
        return max(0, $this->rrpTotal() - $this->itemsTotal());
    }

    public function freeShippingFrom(): int
    {
        return (int) config('shop.free_shipping_from', 2000);
    }

    public function hasFreeShipping(): bool
    {
        return $this->itemsTotal() >= $this->freeShippingFrom();
    }

    public function remainingForFreeShipping(): int
    {
        return max(0, $this->freeShippingFrom() - $this->itemsTotal());
    }

    public function freeShippingProgress(): int
    {
        $from = $this->freeShippingFrom();

        return $from > 0 ? (int) min(100, floor(100 * $this->itemsTotal() / $from)) : 100;
    }

    /** Cena dopravy pro daný způsob (zdarma nad limitem). */
    public function shippingFor(string $code): int
    {
        $method = config('shop.delivery.'.$code);
        if (! $method || $this->hasFreeShipping()) {
            return 0;
        }

        return (int) $method['price'];
    }

    /** Nejlevnější placená doprava – „doprava od 79 Kč“ v košíku. */
    public function cheapestPaidShipping(): int
    {
        $prices = array_filter(array_map(fn ($m) => (int) $m['price'], config('shop.delivery', [])));

        return $prices ? min($prices) : 0;
    }

    /** Data pro JSON odpovědi (číslo v hlavičce, součty, pruh dopravy zdarma). */
    public function summary(): array
    {
        return [
            'count' => $this->count(),
            'lines' => $this->lines()->count(),
            'items_total' => $this->itemsTotal(),
            'items_total_formatted' => Fmt::price($this->itemsTotal()),
            'rrp_total' => $this->rrpTotal(),
            'saving' => $this->saving(),
            'saving_formatted' => Fmt::price($this->saving()),
            'free_shipping' => $this->hasFreeShipping(),
            'free_shipping_remaining' => $this->remainingForFreeShipping(),
            'free_shipping_remaining_formatted' => Fmt::price($this->remainingForFreeShipping()),
            'free_shipping_progress' => $this->freeShippingProgress(),
        ];
    }
}
