<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Services\Catalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Výběry na úvodu (záložky fungují i bez JS přes ?vyber=…). */
    public const PICKS = [
        'bestsellery' => 'Bestsellery',
        'novinky' => 'Novinky',
        'posledni-kusy' => 'Poslední kusy',
    ];

    /** Pevné fotky úvodu (Pexels id): hero, detail v hero, pás „Proč tak levně“, novinky e-mailem. */
    public const FIXED_PHOTOS = [18977900, 34788168, 8066048, 7191403, 20075994];

    public function __invoke(Request $request, Catalog $catalog): View
    {
        $pick = (string) $request->query('vyber', 'bestsellery');
        $pick = array_key_exists($pick, self::PICKS) ? $pick : 'bestsellery';

        $all = Product::with('collection', 'category')->orderBy('sort')->orderBy('id')->get();
        $categories = $catalog->categories()->filter(fn ($c) => $c->products_count > 0)->values();

        // Tři kolekce na úvodu: Royal, Afina, NewMoon (jinak první tři podle pořadí).
        $collections = $catalog->collections();
        $featured = collect(['royal', 'afina', 'newmoon'])
            ->map(fn ($slug) => $collections->firstWhere('slug', $slug))->filter()->values();
        if ($featured->count() < 3) {
            $featured = $collections->take(3)->values();
        }

        // Fotky, které už úvod ukazuje jinde (hero, kategorie, kolekce, novinky) – karty s nimi jdou ve výběru až na konec.
        $base = fn (?string $url) => $url ? strtok($url, '?') : null;
        $used = collect(self::FIXED_PHOTOS)
            ->map(fn ($id) => "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg")
            ->concat($categories->pluck('image_url')->map($base))
            ->concat($featured->pluck('image_url')->map($base))
            ->filter()->unique()->all();
        $repeat = fn (Product $p) => in_array($base($p->mainImage()['url'] ?? null), $used, true) ? 1 : 0;

        $picks = [
            'bestsellery' => $all->sortBy(fn (Product $p) => [$repeat($p), $p->badge === 'bestseller' ? 0 : 1, $p->is_featured ? 0 : 1, $p->sort, $p->id])->take(8)->values(),
            'novinky' => $all->sortBy(fn (Product $p) => [$repeat($p), $p->badge === 'novinka' ? 0 : 1, -($p->created_at?->getTimestamp() ?? 0), -$p->id])->take(8)->values(),
            'posledni-kusy' => $all->sortBy(fn (Product $p) => [
                $repeat($p), ($p->stock === 'last_pieces' || $p->badge === 'posledni-kusy') ? 0 : 1, -($p->discountPercent() ?? 0), $p->id,
            ])->take(8)->values(),
        ];

        // Příklad úspory v pásu „Proč tak levně“: Anmut Gold talíř, jinak produkt s nejvyšší slevou.
        $example = $all->firstWhere('slug', 'anmut-gold-jidelni-talir-27')
            ?? $all->sortByDesc(fn (Product $p) => $p->discountPercent() ?? 0)->first();

        return view('shop.home', [
            'stats' => $catalog->stats(),
            'categories' => $categories,
            'saleImage' => $all->first(fn (Product $p) => ($p->discountPercent() ?? 0) >= Catalog::SALE_PERCENT)?->mainImage()['url'] ?? null,
            'featuredCollections' => $featured,
            'collectionsCount' => $collections->count(),
            'picks' => $picks,
            'pick' => $pick,
            'example' => $example,
        ]);
    }
}
