<?php

namespace Tests\Feature\Shop;

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Services\Cart;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class CheckoutTest extends ShopTestCase
{
    public function test_empty_cart_redirects_to_cart(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('cart.show'));
        $this->post(route('checkout.store'), $this->validCheckout())->assertRedirect(route('cart.show'));
        $this->assertSame(0, Inquiry::count());
    }

    public function test_checkout_page_shows_summary_and_notice(): void
    {
        $this->addToCart($this->product('newmoon-miska-13'), '1');
        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Nic teď neplatíte')
            ->assertSee('Miska 13 cm')
            ->assertSee('name="website"', false)
            ->assertSee('Zásilkovna');
    }

    public function test_validation_errors_are_czech(): void
    {
        $this->addToCart($this->product('newmoon-miska-13'), '1');

        $this->from(route('checkout.show'))
            ->post(route('checkout.store'), ['delivery' => 'ppl', 'email' => 'spatne', 'phone' => '12'])
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasErrors(['customer_name', 'email', 'phone', 'street', 'city', 'zip', 'consent']);

        $errors = session('errors')->getBag('default');
        $this->assertSame('Napište prosím jméno a příjmení.', $errors->first('customer_name'));
        $this->assertStringContainsString('jmeno@domena.cz', $errors->first('email'));
        $this->assertStringContainsString('obchodními podmínkami', $errors->first('consent'));

        // Zásilkovna chce výdejní místo, ne adresu
        $this->post(route('checkout.store'), $this->validCheckout(['pickup_point' => '']))
            ->assertSessionHasErrors('pickup_point')
            ->assertSessionDoesntHaveErrors(['street', 'city', 'zip']);

        // neznámý způsob dopravy, špatné IČO a PSČ
        $this->post(route('checkout.store'), $this->validCheckout(['delivery' => 'drak', 'ic' => '123']))
            ->assertSessionHasErrors(['delivery', 'ic']);
        $this->post(route('checkout.store'), $this->validCheckout(['delivery' => 'ppl', 'street' => 'Dlouhá 5', 'city' => 'Praha', 'zip' => '1100']))
            ->assertSessionHasErrors('zip');

        $this->assertSame(0, Inquiry::count());
    }

    public function test_successful_checkout_creates_inquiry_sends_mail_and_clears_cart(): void
    {
        Mail::fake();
        config(['shop.inquiry_to' => 'poptavky@example.test']);

        $plate = $this->product('royal-melky-talir-27');
        $bowl = $this->product('newmoon-miska-13');
        $this->addToCart($plate, '6', 1);   // 2 090 Kč (balení)
        $this->addToCart($bowl, '1', 2);    // 2 × 299 Kč

        // cena v DB se mezitím změní – poptávka musí vzít aktuální cenu
        $bowl->forceFill(['options' => [['code' => '1', 'label' => '1 ks', 'units' => 1, 'price' => 289, 'rrp' => 540], ['code' => '4', 'label' => 'Sada 4 ks', 'units' => 4, 'price' => 1090, 'rrp' => 2160]]])->save();

        $response = $this->post(route('checkout.store'), $this->validCheckout([
            'delivery' => 'ppl', 'pickup_point' => 'nemá se uložit',
            'street' => 'Dlouhá 5', 'city' => 'Praha', 'zip' => '11000',
            'company' => 'Kavárna s.r.o.', 'ic' => '12345678',
            'price' => 1, 'items_total' => 1,
        ]));

        $inquiry = Inquiry::with('items')->sole();
        $this->assertMatchesRegularExpression('/^VB-\d{4}-0001$/', $inquiry->number);
        $this->assertSame('new', $inquiry->status);
        $this->assertSame(2090 + 2 * 289, $inquiry->items_total);
        $this->assertSame(0, $inquiry->delivery_price); // nad 2 000 Kč zdarma
        $this->assertSame($inquiry->items_total, $inquiry->total);
        $this->assertSame(3790 + 2 * 540, $inquiry->rrp_total);
        $this->assertSame('PPL – kurýr na adresu', $inquiry->delivery_label);
        $this->assertNull($inquiry->pickup_point);
        $this->assertSame('110 00', $inquiry->zip);
        $this->assertSame('12345678', $inquiry->ic);
        $this->assertSame(64, strlen($inquiry->ip_hash));
        $this->assertSame('sent', $inquiry->mail_status);

        $this->assertCount(2, $inquiry->items);
        $first = $inquiry->items->firstWhere('product_id', $plate->id);
        $this->assertSame('Mělký talíř 27 cm', $first->product_name);
        $this->assertSame('Royal', $first->collection_name);
        $this->assertSame('Sada 6 ks', $first->option_label);
        $this->assertSame(2090, $first->unit_price);
        $this->assertSame(3790, $first->rrp);
        $this->assertSame(2090, $first->line_total);
        $this->assertStringStartsWith('https://images.pexels.com/', $first->image_url);
        $second = $inquiry->items->firstWhere('product_id', $bowl->id);
        $this->assertSame([289, 2, 578], [$second->unit_price, $second->quantity, $second->line_total]);

        Mail::assertSent(InquiryReceived::class, function (InquiryReceived $mail) use ($inquiry) {
            return $mail->hasTo('poptavky@example.test')
                && $mail->hasReplyTo('jana@example.com')
                && $mail->inquiry->is($inquiry);
        });

        // přesměrování na podepsanou adresu poděkování
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/objednavka/odeslano/'.$inquiry->number, $location);
        $this->assertStringContainsString('signature=', $location);
        $this->get($location)->assertOk()->assertSee($inquiry->number)->assertSee('Dlouhá 5');

        // košík je prázdný
        $this->assertSame([], session(Cart::SESSION_KEY));
    }

    public function test_shipping_is_charged_below_the_limit(): void
    {
        Mail::fake();
        $this->addToCart($this->product('newmoon-miska-13'), '1');
        $this->post(route('checkout.store'), $this->validCheckout());

        $inquiry = Inquiry::sole();
        $this->assertSame(299, $inquiry->items_total);
        $this->assertSame(79, $inquiry->delivery_price);
        $this->assertSame(378, $inquiry->total);
        $this->assertSame('Praha 2, Vinohradská 10', $inquiry->pickup_point);
        $this->assertNull($inquiry->street);
    }

    public function test_inquiry_number_increments(): void
    {
        Mail::fake();
        foreach ([1, 2] as $n) {
            $this->addToCart($this->product('newmoon-miska-13'), '1');
            $this->post(route('checkout.store'), $this->validCheckout());
        }
        $this->assertSame(['0001', '0002'], Inquiry::orderBy('id')->pluck('number')->map(fn ($n) => substr($n, -4))->all());
    }

    public function test_thank_you_page_requires_signature(): void
    {
        Mail::fake();
        $this->addToCart($this->product('newmoon-miska-13'), '1');
        $this->post(route('checkout.store'), $this->validCheckout());
        $inquiry = Inquiry::sole();

        $this->get('/objednavka/odeslano/'.$inquiry->number)->assertForbidden();
        $this->get('/objednavka/odeslano/'.$inquiry->number.'?signature=podvrh')->assertForbidden();
        $this->get(URL::signedRoute('checkout.thanks', ['inquiry' => $inquiry->number]))->assertOk();
    }

    public function test_honeypot_pretends_success_but_stores_nothing(): void
    {
        Mail::fake();
        $this->addToCart($this->product('newmoon-miska-13'), '1');

        $this->post(route('checkout.store'), $this->validCheckout(['website' => 'http://spam.example']))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');

        $this->assertSame(0, Inquiry::count());
        Mail::assertNothingSent();
    }

    public function test_customer_sees_success_even_if_mail_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP nejede'));
        $this->addToCart($this->product('newmoon-miska-13'), '1');

        $response = $this->post(route('checkout.store'), $this->validCheckout());

        $inquiry = Inquiry::sole();
        $this->assertSame('failed', $inquiry->mail_status);
        $this->assertStringContainsString('/objednavka/odeslano/', $response->headers->get('Location'));
    }

    public function test_checkout_is_throttled(): void
    {
        Mail::fake();
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('checkout.store'), ['delivery' => 'ppl'])->assertStatus(302);
        }
        $this->post(route('checkout.store'), ['delivery' => 'ppl'])
            ->assertStatus(429)
            ->assertSee('Příliš mnoho pokusů');
    }
}
