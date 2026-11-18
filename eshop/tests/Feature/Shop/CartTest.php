<?php

namespace Tests\Feature\Shop;

use App\Services\Cart;
use App\View\Fmt;

class CartTest extends ShopTestCase
{
    public function test_add_without_js_redirects_back_with_flash(): void
    {
        $p = $this->product('chateau-blanc-sada-4-taliru');

        $this->from(route('shop.product', $p))
            ->post(route('cart.add'), ['product_id' => $p->id, 'qty' => 2])
            ->assertRedirect(route('shop.product', $p))
            ->assertSessionHas('status');

        $this->assertSame([(string) $p->id => ['p' => $p->id, 'o' => '', 'q' => 2]], session(Cart::SESSION_KEY));
        $this->get(route('cart.show'))->assertSee($p->name)->assertSee(Fmt::price(2980));
    }

    public function test_add_returns_json_with_counts(): void
    {
        $p = $this->product('royal-melky-talir-27');

        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'option' => '6', 'qty' => 2])
            ->assertOk()
            ->assertJsonPath('cart.count', 2)
            ->assertJsonPath('cart.items_total', 4180)
            ->assertJsonPath('added.option', 'Sada 6 ks')
            ->assertJsonPath('added.qty', 2);

        // stejný řádek se sčítá, jiné balení je nový řádek
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'option' => '6'])->assertJsonPath('cart.count', 3);
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'option' => '1'])
            ->assertJsonPath('cart.count', 4)
            ->assertJsonPath('cart.lines', 2)
            ->assertJsonPath('cart.items_total', 3 * 2090 + 379);
    }

    public function test_option_prices_come_from_database_and_unknown_option_falls_back(): void
    {
        $p = $this->product('royal-melky-talir-27');

        // neexistující kód balení → první balení (1 ks za 379 Kč), cenu z požadavku nebereme
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'option' => 'xxx', 'price' => 1])
            ->assertJsonPath('cart.items_total', 379);

        // produkt bez balení
        $v = $this->product('afina-vaza-21');
        $this->postJson(route('cart.add'), ['product_id' => $v->id, 'option' => '6'])
            ->assertJsonPath('cart.items_total', 379 + 790);

        // změna ceny v DB se projeví v košíku
        $p->forceFill(['options' => [['code' => '1', 'label' => '1 ks', 'units' => 1, 'price' => 300, 'rrp' => 690]]])->save();
        $this->getJson(route('cart.show'));
        $this->assertSame(300 + 790, app(Cart::class)->itemsTotal());
    }

    public function test_add_validation(): void
    {
        $this->postJson(route('cart.add'), ['product_id' => 999999])->assertStatus(422)->assertJsonValidationErrors('product_id');
        $p = $this->product('royal-melky-talir-27');
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'qty' => 50])->assertStatus(422)->assertJsonValidationErrors('qty');
        $this->postJson(route('cart.add'), ['product_id' => $p->id, 'qty' => 0])->assertStatus(422);
    }

    public function test_update_remove_and_clear(): void
    {
        $a = $this->product('royal-melky-talir-27');
        $b = $this->product('newmoon-miska-13');
        $this->addToCart($a, '6');
        $this->addToCart($b, '1', 2);

        $this->postJson(route('cart.update'), ['key' => $a->id.'-6', 'qty' => 3])
            ->assertOk()->assertJsonPath('cart.count', 5)->assertJsonPath('ok', true);

        // s hlavičkou X-Shop-Partial vrací i HTML obsahu košíku
        $this->postJson(route('cart.update'), ['key' => $b->id.'-1', 'qty' => 1], ['X-Shop-Partial' => 'cart'])
            ->assertOk()->assertJsonPath('cart.count', 4)->assertJsonStructure(['html']);

        // qty 0 = odebrat
        $this->postJson(route('cart.update'), ['key' => $b->id.'-1', 'qty' => 0])->assertJsonPath('cart.count', 3);

        // neznámý řádek
        $this->postJson(route('cart.update'), ['key' => 'nic', 'qty' => 1])->assertNotFound()->assertJsonPath('ok', false);

        $this->postJson(route('cart.remove'), ['key' => $a->id.'-6'])->assertOk()->assertJsonPath('cart.count', 0);

        $this->addToCart($b);
        $this->post(route('cart.clear'))->assertRedirect(route('cart.show'));
        $this->get(route('cart.show'))->assertSee('Košík je prázdný');
    }

    public function test_update_without_js(): void
    {
        $a = $this->product('newmoon-miska-13');
        $this->addToCart($a, '4');
        $this->post(route('cart.update'), ['key' => $a->id.'-4', 'qty' => 2])->assertRedirect(route('cart.show'));
        $this->get(route('cart.show'))->assertSee(Fmt::price(2180));
        $this->post(route('cart.remove'), ['key' => $a->id.'-4'])->assertRedirect(route('cart.show'));
        $this->get(route('cart.show'))->assertSee('Košík je prázdný');
    }

    public function test_free_shipping_bar(): void
    {
        $this->addToCart($this->product('newmoon-miska-13'), '1');
        $this->get(route('cart.show'))->assertSee('Do dopravy zdarma zbývá')->assertSee(Fmt::price(2000 - 299));

        $this->addToCart($this->product('royal-jidelni-sada-12-dilu'));
        $this->get(route('cart.show'))->assertSee('Máte dopravu zdarma');
    }

    public function test_header_badge_shows_count(): void
    {
        $this->addToCart($this->product('newmoon-miska-13'), '1', 3);
        $this->get('/')->assertSee('aria-label="Košík, 3 kusy"', false);
    }

    public function test_favorites_toggle(): void
    {
        $p = $this->product('afina-vaza-21');

        $this->postJson(route('favorites.toggle', $p))->assertOk()->assertJson(['on' => true, 'count' => 1]);
        $this->get(route('favorites.index'))->assertSee($p->name);
        $this->postJson(route('favorites.toggle', $p))->assertJson(['on' => false, 'count' => 0]);
        $this->get(route('favorites.index'))->assertSee('Zatím tu nic není');

        $this->from('/')->post(route('favorites.toggle', $p))->assertRedirect('/');
        $this->get('/oblibene')->assertSee($p->name);
    }

    public function test_newsletter_stores_nothing_and_says_so(): void
    {
        $this->from('/')->post(route('newsletter.store'), ['email' => 'neplatny'])
            ->assertSessionHasErrorsIn('newsletter', 'email');

        $this->from('/')->post(route('newsletter.store'), ['email' => 'jana@example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletter');
    }
}
