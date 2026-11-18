<?php

namespace App\Services;

use App\Models\Product;

/**
 * Jeden řádek košíku = produkt + zvolené balení + množství. Ceny se vždy počítají z aktuálního produktu v DB.
 */
final class CartLine
{
    public function __construct(
        public readonly string $key,
        public readonly Product $product,
        public readonly array $option,
        public readonly int $qty,
    ) {}

    public function optionCode(): string
    {
        return (string) ($this->option['code'] ?? '');
    }

    public function optionLabel(): ?string
    {
        return $this->option['label'] ?? null;
    }

    public function units(): int
    {
        return max(1, (int) ($this->option['units'] ?? 1));
    }

    public function unitPrice(): int
    {
        return (int) ($this->option['price'] ?? $this->product->price);
    }

    /** Doporučená cena za jednu položku (balení); u balení bez vlastní RRP = RRP kusu × počet kusů. */
    public function unitRrp(): ?int
    {
        if (isset($this->option['rrp'])) {
            return (int) $this->option['rrp'];
        }

        return $this->product->rrp ? $this->product->rrp * $this->units() : null;
    }

    public function lineTotal(): int
    {
        return $this->unitPrice() * $this->qty;
    }

    /** Součet doporučených cen; bez RRP se počítá prodejní cena (žádná „úspora“). */
    public function lineRrp(): int
    {
        return max($this->unitRrp() ?? $this->unitPrice(), $this->unitPrice()) * $this->qty;
    }

    public function image(): ?string
    {
        return $this->product->mainImage()['url'] ?? null;
    }

    public function collectionName(): ?string
    {
        return $this->product->collection?->name;
    }
}
