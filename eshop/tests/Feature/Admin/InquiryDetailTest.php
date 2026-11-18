<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\Support\Format;
use App\Mail\InquiryReceived;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Database\Factories\InquiryFactory;
use Database\Factories\InquiryItemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class InquiryDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function inquiry(array $attributes = []): Inquiry
    {
        return InquiryFactory::new()
            ->delivery('ppl')
            ->company('Bistro Na Kopečku s.r.o.', '08415267')
            ->has(InquiryItemFactory::new()->sample(0, 2, 'Sada 6 ks', 6), 'items') // 2 × 4 494 Kč
            ->has(InquiryItemFactory::new()->sample(5, 3), 'items')                 // 3 × 299 Kč
            ->create(array_merge([
                'number' => 'VB-2026-0001',
                'customer_name' => 'Kateřina Dvořáková',
                'email' => 'katerina@example.com',
                'phone' => '+420 734 218 905',
                'note' => "Prosím zabalit jako dárek.\nDěkuji!",
            ], $attributes));
    }

    public function test_detail_shows_customer_delivery_items_and_totals(): void
    {
        $inquiry = $this->inquiry();
        $itemsTotal = 2 * 749 * 6 + 3 * 299;
        $rrpTotal = 2 * 1290 * 6 + 3 * 540;

        $this->assertSame($itemsTotal, $inquiry->fresh()->items_total);

        $this->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('VB-2026-0001')
            ->assertSee('Kateřina Dvořáková')
            ->assertSee('href="mailto:katerina@example.com"', false)
            ->assertSee('href="tel:+420734218905"', false)
            ->assertSee('Bistro Na Kopečku s.r.o.')
            ->assertSee('08415267')
            ->assertSee('PPL – kurýr na adresu')
            ->assertSee($inquiry->street)
            ->assertSee('Prosím zabalit jako dárek.<br />', false)
            ->assertSeeInOrder(['Anmut Gold', 'Jídelní talíř 27 cm se zlatou linkou', 'Sada 6 ks', Format::money(749 * 6), Format::money(749 * 12)])
            ->assertSee('Miska 13 cm')
            ->assertSee('pexels-photo-14132973.jpeg', false)
            ->assertSee(Format::money($itemsTotal))
            ->assertSee('−'.Format::money($rrpTotal - $itemsTotal))
            ->assertSee('zdarma') // PPL od 2 000 Kč zdarma
            ->assertSee('Nová')
            ->assertSee('E-mail odeslán')
            ->assertSee('href="mailto:katerina@example.com?subject=Popt%C3%A1vka%20VB-2026-0001"', false)
            ->assertSee('Odpovědět zákazníkovi');
    }

    public function test_dates_are_shown_in_prague_time(): void
    {
        $inquiry = $this->inquiry(['created_at' => Carbon::parse('2025-07-01 22:15', 'UTC')]);

        $this->get(route('admin.inquiries.show', $inquiry))
            ->assertSee('Přijato 2.'."\u{00A0}".'7.'."\u{00A0}".'2025 v 0:15');
    }

    public function test_unknown_number_is_404(): void
    {
        $this->get('/admin/poptavky/VB-2026-9999')->assertNotFound();
    }

    public function test_product_link_only_when_the_product_still_exists(): void
    {
        $category = Category::query()->create(['slug' => 'talire', 'name' => 'Talíře']);
        $product = Product::query()->create([
            'category_id' => $category->id, 'slug' => 'jidelni-talir-anmut-gold', 'name' => 'Jídelní talíř 27 cm se zlatou linkou',
            'price' => 749, 'rrp' => 1290, 'images' => [],
        ]);

        $inquiry = InquiryFactory::new()
            ->has(InquiryItemFactory::new()->sample(0)->state(['product_id' => $product->id]), 'items')
            ->has(InquiryItemFactory::new()->sample(5), 'items') // bez produktu
            ->create();

        $html = $this->get(route('admin.inquiries.show', $inquiry))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('shop.product', 'jidelni-talir-anmut-gold').'"', $html);
        $this->assertStringNotContainsString('>Miska 13 cm<span', $html);
        $this->assertStringContainsString('Miska 13 cm', $html);
    }

    public function test_previous_and_next_inquiry_links(): void
    {
        $old = InquiryFactory::new()->create(['number' => 'VB-2026-0001', 'created_at' => now()->subDays(3)]);
        $mid = InquiryFactory::new()->create(['number' => 'VB-2026-0002', 'created_at' => now()->subDays(2)]);
        $new = InquiryFactory::new()->create(['number' => 'VB-2026-0003', 'created_at' => now()->subDay()]);

        $this->get(route('admin.inquiries.show', $mid))
            ->assertSee('href="'.route('admin.inquiries.show', $new).'"', false)
            ->assertSee('href="'.route('admin.inquiries.show', $old).'"', false)
            ->assertSee('Novější poptávka VB-2026-0003')
            ->assertSee('Starší poptávka VB-2026-0001');

        $this->get(route('admin.inquiries.show', $new))->assertDontSee('Novější poptávka');
    }

    public function test_status_can_be_changed(): void
    {
        $inquiry = $this->inquiry();

        $this->from(route('admin.inquiries.show', $inquiry))
            ->patch(route('admin.inquiries.status', $inquiry), ['status' => 'confirmed'])
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('success', 'Stav poptávky je teď „Potvrzená“.');

        $this->assertSame('confirmed', $inquiry->fresh()->status);
        $this->get(route('admin.inquiries.show', $inquiry))->assertSee('Stav poptávky je teď „Potvrzená“.');
    }

    public function test_status_is_validated(): void
    {
        $inquiry = $this->inquiry();

        foreach (['shipped', '', ['new']] as $bad) {
            $this->from(route('admin.inquiries.show', $inquiry))
                ->patch(route('admin.inquiries.status', $inquiry), ['status' => $bad])
                ->assertRedirect(route('admin.inquiries.show', $inquiry))
                ->assertSessionHasErrorsIn('status', ['status']);
        }

        $this->assertSame('new', $inquiry->fresh()->status);

        // chyba se ukáže u formuláře stavu
        $this->from(route('admin.inquiries.show', $inquiry))
            ->followingRedirects()
            ->patch(route('admin.inquiries.status', $inquiry), ['status' => 'shipped'])
            ->assertOk()
            ->assertSee('Vyberte jeden ze stavů v nabídce.')
            ->assertSee('id="status-error"', false);
    }

    public function test_note_error_is_shown_under_the_textarea_with_the_text_kept(): void
    {
        $inquiry = $this->inquiry();

        $this->from(route('admin.inquiries.show', $inquiry))
            ->followingRedirects()
            ->patch(route('admin.inquiries.note', $inquiry), ['internal_note' => str_repeat('b', 5001)])
            ->assertOk()
            ->assertSee('Poznámka může mít nejvýš 5 000 znaků.')
            ->assertSee(str_repeat('b', 5001).'</textarea>', false);
    }

    public function test_internal_note_is_saved_trimmed_and_can_be_cleared(): void
    {
        $inquiry = $this->inquiry();

        $this->from(route('admin.inquiries.show', $inquiry))
            ->patch(route('admin.inquiries.note', $inquiry), ['internal_note' => "  Volala 18. 11.\r\nChce fakturu.  "])
            ->assertRedirect(route('admin.inquiries.show', $inquiry).'#poznamka')
            ->assertSessionHas('success', 'Interní poznámka je uložená.');
        $this->assertSame("Volala 18. 11.\nChce fakturu.", $inquiry->fresh()->internal_note);

        $this->get(route('admin.inquiries.show', $inquiry))->assertSee('Volala 18. 11.');

        $this->patch(route('admin.inquiries.note', $inquiry), ['internal_note' => '   '])
            ->assertSessionHas('success', 'Interní poznámka je smazaná.');
        $this->assertNull($inquiry->fresh()->internal_note);
    }

    public function test_internal_note_has_5000_characters_at_most(): void
    {
        $inquiry = $this->inquiry();

        $this->patch(route('admin.inquiries.note', $inquiry), ['internal_note' => str_repeat('ž', 5000)])
            ->assertSessionHasNoErrors();
        $this->assertSame(5000, mb_strlen($inquiry->fresh()->internal_note));

        $this->from(route('admin.inquiries.show', $inquiry))
            ->patch(route('admin.inquiries.note', $inquiry), ['internal_note' => str_repeat('a', 5001)])
            ->assertRedirect(route('admin.inquiries.show', $inquiry).'#poznamka')
            ->assertSessionHasErrorsIn('note', ['internal_note' => 'Poznámka může mít nejvýš 5 000 znaků.']);
        $this->assertSame(5000, mb_strlen($inquiry->fresh()->internal_note));
    }

    public function test_resend_sends_inquiry_mail_to_the_shop_and_updates_the_log(): void
    {
        Mail::fake();
        config(['shop.inquiry_to' => 'obchod@example.test']);
        $inquiry = InquiryFactory::new()->withItems(2)->mailFailed('Timeout')->create();

        $this->post(route('admin.inquiries.resend', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry).'#email')
            ->assertSessionHas('success', 'E-mail s poptávkou odešel znovu na obchod@example.test.');

        Mail::assertSent(InquiryReceived::class, fn (InquiryReceived $mail) => $mail->hasTo('obchod@example.test')
            && $mail->inquiry->is($inquiry)
            && $mail->hasReplyTo($inquiry->email));
        Mail::assertSentCount(1);

        $fresh = $inquiry->fresh();
        $this->assertSame('sent', $fresh->mail_status);
        $this->assertNotNull($fresh->mail_sent_at);
        $this->assertStringContainsString('obchod@example.test', $fresh->mail_info);

        $this->get(route('admin.inquiries.show', $inquiry))
            ->assertSee('E-mail s poptávkou odešel znovu na obchod@example.test.')
            ->assertSee('E-mail odeslán');
    }

    public function test_resend_failure_is_reported_and_logged_on_the_inquiry(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP server neodpovídá'));
        Log::shouldReceive('error')->once();
        $inquiry = InquiryFactory::new()->create();

        $this->post(route('admin.inquiries.resend', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry).'#email')
            ->assertSessionHas('error', 'E-mail se nepodařilo odeslat: SMTP server neodpovídá Zkuste to za chvíli znovu.');

        $this->assertSame('failed', $inquiry->fresh()->mail_status);
        $this->assertSame('SMTP server neodpovídá', $inquiry->fresh()->mail_info);
    }

    public function test_resend_is_limited_to_three_per_minute(): void
    {
        Mail::fake();
        $inquiry = InquiryFactory::new()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.inquiries.resend', $inquiry))->assertSessionHas('success');
        }
        $this->post(route('admin.inquiries.resend', $inquiry))->assertSessionHas('error');

        Mail::assertSentCount(3);
    }

    public function test_failed_mail_is_highlighted_on_the_detail(): void
    {
        $inquiry = InquiryFactory::new()->mailFailed('Expected response code 250 but got 550')->create();

        $this->get(route('admin.inquiries.show', $inquiry))
            ->assertSee('E-mail se nepodařilo odeslat')
            ->assertSee('Expected response code 250 but got 550')
            ->assertSee('panel-danger', false);
    }
}
