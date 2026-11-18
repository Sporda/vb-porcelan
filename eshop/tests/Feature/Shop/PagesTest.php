<?php

namespace Tests\Feature\Shop;

use App\View\Fmt;
use PHPUnit\Framework\Attributes\DataProvider;

class PagesTest extends ShopTestCase
{
    public static function pages(): array
    {
        return [
            'úvod' => ['/'],
            'úvod – novinky' => ['/?vyber=novinky'],
            'všechny produkty' => ['/porcelan'],
            'druhá strana' => ['/porcelan?strana=2'],
            'filtry a řazení' => ['/porcelan?kolekce[]=royal&kolekce[]=afina&typ[]=talire&cena_od=300&cena_do=2000&skladem=1&sleva=30&razeni=nejlevnejsi'],
            'řazení podle slevy' => ['/porcelan?razeni=sleva'],
            'řazení novinky' => ['/porcelan?razeni=novinky'],
            'nesmyslné filtry' => ['/porcelan?razeni=xxx&sleva=99&cena_od=abc&kolekce=<script>'],
            'kategorie' => ['/kategorie/talire'],
            'kategorie s řazením' => ['/kategorie/hrnky-a-salky?razeni=nejdrazsi'],
            'kolekce – přehled' => ['/kolekce'],
            'kolekce' => ['/kolekce/royal'],
            'výprodej' => ['/vyprodej'],
            'produkt' => ['/produkt/chateau-blanc-sada-4-taliru'],
            'produkt s balením' => ['/produkt/royal-melky-talir-27?baleni=6'],
            'hledání – prázdné' => ['/hledat'],
            'hledání' => ['/hledat?q=šálek'],
            'hledání bez výsledků' => ['/hledat?q=nicnenajdes'],
            'košík' => ['/kosik'],
            'oblíbené' => ['/oblibene'],
            'proč tak levně' => ['/proc-tak-levne'],
            'o nás' => ['/o-nas'],
            'doprava a platba' => ['/doprava-a-platba'],
            'kontakt' => ['/kontakt'],
            'obchodní podmínky' => ['/obchodni-podminky'],
            'ochrana údajů' => ['/ochrana-osobnich-udaju'],
            'reklamace' => ['/reklamace-a-vraceni'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders(string $url): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee('<html lang="cs"', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('rel="canonical"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_unknown_slugs_return_404(): void
    {
        $this->get('/kategorie/neexistuje')->assertNotFound()->assertSee('Tuhle stránku jsme nenašli');
        $this->get('/kolekce/neexistuje')->assertNotFound();
        $this->get('/produkt/neexistuje')->assertNotFound();
        $this->get('/neexistujici-stranka')->assertNotFound();
    }

    public function test_home_shows_categories_collections_and_products(): void
    {
        $this->get('/')
            ->assertSee('Porcelán podle')
            ->assertSee('Talíře')
            ->assertSee('Royal')
            ->assertSee(route('shop.category', 'talire'), false)
            ->assertSee(route('shop.collection', 'royal'), false)
            ->assertSee(route('shop.product', 'chateau-blanc-sada-4-taliru'), false);
    }

    public function test_category_lists_only_its_products(): void
    {
        $this->get('/kategorie/dekorace')
            ->assertSee('Váza bílá, matná')
            ->assertDontSee('Mělký talíř 27 cm');
    }

    public function test_filters_and_sorting(): void
    {
        // jen Royal, od nejlevnějšího
        $html = $this->get('/porcelan?kolekce[]=royal&razeni=nejlevnejsi')->assertOk()->getContent();
        $this->assertStringNotContainsString('Château Blanc</p>', $html);
        $this->assertLessThan(strpos($html, 'Čajová konvice 1 l'), strpos($html, 'Mléčenka 0,25 l'));

        // výprodej = sleva aspoň 40 %
        $this->get('/vyprodej')->assertSee('Mělký talíř 27 cm')->assertDontSee('Váza 21 cm s reliéfem');

        // cenové rozpětí
        $this->get('/porcelan?cena_od=5000')->assertSee('Jídelní sada pro 6 osob')->assertDontSee('Miska 13 cm');
    }

    public function test_pagination_is_czech(): void
    {
        $this->get('/porcelan')->assertSee('Předchozí')->assertSee('Další')->assertSee('Načíst dalších');
    }

    public function test_search_ignores_diacritics_and_case(): void
    {
        $this->get('/hledat?q=SALEK')->assertSee('Šálek na kávu s podšálkem');
        $this->get('/hledat?q=anmut talir')->assertSee('Jídelní talíř 27 cm se zlatou linkou')->assertDontSee('Miska 13 cm');
        $this->get('/hledat?q=nicnenajdes')->assertSee('jsme nic nenašli');
    }

    public function test_product_page_has_details_and_json_ld(): void
    {
        $this->get('/produkt/chateau-blanc-sada-4-taliru')
            ->assertSee('Sada 4 jídelních talířů s reliéfem')
            ->assertSee(Fmt::price(1490))
            ->assertSee('Obsah sady')
            ->assertSee('Myčka nádobí')
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"priceCurrency":"CZK"', false);
    }

    public function test_option_preselect_from_query(): void
    {
        $this->get('/produkt/royal-melky-talir-27?baleni=6')->assertSee('data-price-now>'.Fmt::price(2090), false);
        $this->get('/produkt/royal-melky-talir-27')->assertSee('data-price-now>'.Fmt::price(379), false);
    }
}
