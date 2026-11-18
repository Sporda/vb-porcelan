<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** Společný základ testů obchodu: čistá DB s ukázkovým katalogem. */
abstract class ShopTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        RateLimiter::clear('checkout|127.0.0.1');
    }

    protected function product(string $slug): Product
    {
        return Product::where('slug', $slug)->firstOrFail();
    }

    /** Přidá produkt do košíku (bez JS) a vrátí odpověď. */
    protected function addToCart(Product $product, ?string $option = null, int $qty = 1)
    {
        return $this->post(route('cart.add'), array_filter([
            'product_id' => $product->id,
            'option' => $option,
            'qty' => $qty,
        ], fn ($v) => $v !== null));
    }

    protected function validCheckout(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Jana Nováková',
            'email' => 'jana@example.com',
            'phone' => '+420 601 123 456',
            'company' => '',
            'ic' => '',
            'delivery' => 'zasilkovna',
            'pickup_point' => 'Praha 2, Vinohradská 10',
            'note' => 'Prosím zabalit jako dárek.',
            'consent' => '1',
            'website' => '',
        ], $overrides);
    }
}
