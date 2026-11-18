<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\Support\Format;
use App\Models\User;
use Database\Factories\InquiryFactory;
use Database\Factories\InquiryItemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InquiryListTest extends TestCase
{
    use RefreshDatabase;

    private const NBSP = "\u{00A0}";

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_list_shows_newest_first_with_all_columns(): void
    {
        InquiryFactory::new()->state(['number' => 'VB-2025-0001', 'created_at' => Carbon::parse('2025-03-01 10:00', 'UTC')])->create();
        InquiryFactory::new()
            ->state([
                'number' => 'VB-2025-0003',
                'customer_name' => 'Eva Čermáková',
                'email' => 'eva.cermakova@example.com',
                'created_at' => Carbon::parse('2025-03-10 23:30', 'UTC'), // Praha: 11. 3. 0:30
            ])
            ->delivery('zasilkovna')
            ->has(InquiryItemFactory::new()->sample(0, 1), 'items')
            ->has(InquiryItemFactory::new()->sample(5, 1), 'items')
            ->create();
        InquiryFactory::new()->state(['number' => 'VB-2025-0002', 'created_at' => Carbon::parse('2025-03-05 10:00', 'UTC')])->create();

        $response = $this->get(route('admin.inquiries.index'))->assertOk();

        $response->assertSeeInOrder(['VB-2025-0003', 'VB-2025-0002', 'VB-2025-0001']);
        $response->assertSee('Eva Čermáková')
            ->assertSee('eva.cermakova@example.com')
            ->assertSee('2'.self::NBSP.'položky')
            ->assertSee('11.'.self::NBSP.'3.'.self::NBSP.'2025') // datum v pražském čase
            ->assertSee('0:30')
            ->assertSee(Format::money(749 + 299 + 79)) // zboží + Zásilkovna 79 Kč
            ->assertSee('Zásilkovna')
            ->assertSee('Nová')
            ->assertSee('Odesláno');
    }

    public function test_tabs_filter_by_status_and_show_counts(): void
    {
        InquiryFactory::new()->count(2)->create();
        InquiryFactory::new()->processing()->create(['customer_name' => 'Petr Zpracovávaný']);
        InquiryFactory::new()->confirmed()->create(['customer_name' => 'Olga Potvrzená']);

        $this->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertViewHas('counts', ['' => 4, 'new' => 2, 'processing' => 1, 'confirmed' => 1, 'cancelled' => 0])
            ->assertSeeInOrder(['Vše', 'Nové', 'Zpracovává se', 'Potvrzené', 'Zrušené']);

        $this->get(route('admin.inquiries.index', ['stav' => 'processing']))
            ->assertOk()
            ->assertSee('Petr Zpracovávaný')
            ->assertDontSee('Olga Potvrzená')
            ->assertSee('aria-current="page"', false);

        // neznámý stav = vše
        $this->get(route('admin.inquiries.index', ['stav' => 'shipped']))
            ->assertOk()
            ->assertSee('Petr Zpracovávaný')
            ->assertSee('Olga Potvrzená');
    }

    public function test_search_by_number_name_email_and_phone(): void
    {
        InquiryFactory::new()->create([
            'number' => 'VB-2026-0042', 'customer_name' => 'Eva Čermáková', 'email' => 'eva@example.com', 'phone' => '+420 777 123 456',
        ]);
        InquiryFactory::new()->create([
            'number' => 'VB-2026-0007', 'customer_name' => 'Tomáš Dvořák', 'email' => 'tomas.dvorak@example.org', 'phone' => '602 000 111',
        ]);

        $cases = [
            '0042' => 'Eva Čermáková',
            'cermakova' => 'Eva Čermáková',      // bez diakritiky
            'ČERMÁK' => 'Eva Čermáková',         // velká písmena s diakritikou
            'dvořák' => 'Tomáš Dvořák',
            'example.org' => 'Tomáš Dvořák',
            '777123' => 'Eva Čermáková',         // telefon bez mezer
            '602 000' => 'Tomáš Dvořák',
        ];

        foreach ($cases as $term => $expected) {
            $other = $expected === 'Eva Čermáková' ? 'Tomáš Dvořák' : 'Eva Čermáková';
            $this->get(route('admin.inquiries.index', ['q' => $term]))
                ->assertOk()
                ->assertSee($expected)
                ->assertDontSee($other);
        }

        // LIKE zástupné znaky se berou doslova
        $this->get(route('admin.inquiries.index', ['q' => '%']))->assertOk()->assertSee('Nic jsme nenašli');
    }

    public function test_search_keeps_the_status_tab_and_counts_follow_the_search(): void
    {
        InquiryFactory::new()->create(['customer_name' => 'Jana Nová']);
        InquiryFactory::new()->confirmed()->create(['customer_name' => 'Jana Potvrzená']);
        InquiryFactory::new()->confirmed()->create(['customer_name' => 'Karel Jiný']);

        $this->get(route('admin.inquiries.index', ['q' => 'jana', 'stav' => 'confirmed']))
            ->assertOk()
            ->assertSee('Jana Potvrzená')
            ->assertDontSee('Jana Nová</strong>', false)
            ->assertDontSee('Karel Jiný')
            ->assertViewHas('counts', fn (array $c) => $c[''] === 2 && $c['confirmed'] === 1 && $c['new'] === 1)
            ->assertSee('Výsledky pro „jana“', false);
    }

    public function test_failed_mail_stands_out_and_can_be_filtered(): void
    {
        InquiryFactory::new()->create(['customer_name' => 'Doručený Mail']);
        InquiryFactory::new()->mailFailed()->create(['customer_name' => 'Selhaný Mail']);

        $this->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('U 1'.self::NBSP.'poptávky se nepodařilo odeslat e-mail', false)
            ->assertSee('mail-failed', false)
            ->assertSee('Neodesláno');

        $this->get(route('admin.inquiries.index', ['email' => 'failed']))
            ->assertOk()
            ->assertSee('Selhaný Mail')
            ->assertDontSee('Doručený Mail')
            ->assertSee('Jen neodeslané e-maily');
    }

    public function test_empty_states(): void
    {
        $this->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('Zatím tu žádné poptávky nejsou');

        InquiryFactory::new()->create();

        $this->get(route('admin.inquiries.index', ['q' => 'nikdo-takovy']))
            ->assertOk()
            ->assertSee('Nic jsme nenašli')
            ->assertSee('Zobrazit všechny poptávky');
    }

    public function test_paginates_by_25_in_czech(): void
    {
        foreach (range(1, 30) as $i) {
            InquiryFactory::new()->create(['number' => sprintf('VB-2026-%04d', $i), 'created_at' => now()->subHours(40 - $i)]);
        }

        $this->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertViewHas('inquiries', fn (LengthAwarePaginator $p) => $p->count() === 25 && $p->total() === 30)
            ->assertSee('1–25 z 30')
            ->assertSee('Další strana')
            ->assertSee('VB-2026-0030')
            ->assertDontSee('VB-2026-0005');

        $this->get(route('admin.inquiries.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('26–30 z 30')
            ->assertSee('VB-2026-0005')
            ->assertDontSee('VB-2026-0030');
    }

    public function test_list_page_is_not_indexed(): void
    {
        $this->get(route('admin.inquiries.index'))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
