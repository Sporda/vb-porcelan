<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\View\Fmt;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Výpis produktů s filtry v GET parametrech (funguje bez JS):
 *   kolekce[]=slug, typ[]=slug (kategorie), cena_od, cena_do, skladem=1, sleva=30|40, razeni=…, strana=N
 */
class ProductListing
{
    public const PER_PAGE = 12;

    public const SORTS = [
        'doporucene' => 'Doporučené',
        'nejlevnejsi' => 'Nejlevnější',
        'nejdrazsi' => 'Nejdražší',
        'sleva' => 'Největší sleva',
        'novinky' => 'Novinky',
    ];

    public const DISCOUNTS = [30, Catalog::SALE_PERCENT];

    /**
     * @param  Closure(Builder): void|null  $context  omezení na kategorii / kolekci / výprodej / hledání
     * @param  array{types?: bool, collections?: bool}  $facets  které skupiny filtrů nabídnout
     */
    public function build(Request $request, ?Closure $context = null, array $facets = []): array
    {
        $facets += ['types' => true, 'collections' => true];

        $base = Product::query();
        if ($context) {
            $context($base);
        }

        // --- vstupy ---
        $selCollections = $facets['collections'] ? $this->slugs($request->query('kolekce')) : [];
        $selTypes = $facets['types'] ? $this->slugs($request->query('typ')) : [];
        $priceFrom = $this->int($request->query('cena_od'));
        $priceTo = $this->int($request->query('cena_do'));
        if ($priceFrom !== null && $priceTo !== null && $priceFrom > $priceTo) {
            [$priceFrom, $priceTo] = [$priceTo, $priceFrom];
        }
        $inStock = $request->boolean('skladem');
        $discount = (int) $request->query('sleva');
        $discount = in_array($discount, self::DISCOUNTS, true) ? $discount : null;
        $sort = (string) $request->query('razeni', 'doporucene');
        $sort = array_key_exists($sort, self::SORTS) ? $sort : 'doporucene';

        // --- nabídka filtrů (počty v rámci kontextu) ---
        $collectionFacet = collect();
        if ($facets['collections']) {
            $counts = (clone $base)->whereNotNull('product_collection_id')
                ->selectRaw('product_collection_id, count(*) as n')->groupBy('product_collection_id')
                ->pluck('n', 'product_collection_id');
            $collectionFacet = ProductCollection::whereIn('id', $counts->keys())->orderBy('sort')->orderBy('name')->get()
                ->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name, 'count' => (int) $counts[$c->id], 'on' => in_array($c->slug, $selCollections, true)]);
        }
        $typeFacet = collect();
        if ($facets['types']) {
            $counts = (clone $base)->selectRaw('category_id, count(*) as n')->groupBy('category_id')->pluck('n', 'category_id');
            $typeFacet = Category::whereIn('id', $counts->keys())->orderBy('sort')->get()
                ->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name, 'count' => (int) $counts[$c->id], 'on' => in_array($c->slug, $selTypes, true)]);
        }
        $bounds = ['min' => (int) ((clone $base)->min('price') ?? 0), 'max' => (int) ((clone $base)->max('price') ?? 0)];
        $availableCount = Catalog::whereAvailable(clone $base)->count();
        $discountCounts = [];
        foreach (self::DISCOUNTS as $d) {
            $discountCounts[$d] = Catalog::whereDiscountAtLeast(clone $base, $d)->count();
        }

        // --- filtrování ---
        $q = (clone $base)->with('collection', 'category');
        if ($selCollections) {
            $q->whereHas('collection', fn ($c) => $c->whereIn('slug', $selCollections));
        }
        if ($selTypes) {
            $q->whereHas('category', fn ($c) => $c->whereIn('slug', $selTypes));
        }
        if ($priceFrom !== null) {
            $q->where('price', '>=', $priceFrom);
        }
        if ($priceTo !== null) {
            $q->where('price', '<=', $priceTo);
        }
        if ($inStock) {
            Catalog::whereAvailable($q);
        }
        if ($discount) {
            Catalog::whereDiscountAtLeast($q, $discount);
        }

        match ($sort) {
            'nejlevnejsi' => $q->orderBy('price')->orderBy('id'),
            'nejdrazsi' => $q->orderByDesc('price')->orderBy('id'),
            'sleva' => $q->orderByRaw('CASE WHEN rrp > 0 THEN (rrp - price) * 1.0 / rrp ELSE 0 END DESC')->orderBy('price'),
            'novinky' => $q->orderByRaw("CASE WHEN badge = 'novinka' THEN 0 ELSE 1 END")->orderByDesc('created_at')->orderByDesc('id'),
            default => $q->orderByDesc('is_featured')->orderBy('sort')->orderBy('id'),
        };

        $products = $q->paginate(self::PER_PAGE, ['*'], 'strana')->withQueryString();

        // --- aktivní filtry jako štítky s odkazem na odebrání ---
        $chips = [];
        foreach ($collectionFacet->where('on', true) as $c) {
            $chips[] = ['label' => $c['name'], 'url' => $this->urlWithout($request, 'kolekce', $c['slug'])];
        }
        foreach ($typeFacet->where('on', true) as $c) {
            $chips[] = ['label' => $c['name'], 'url' => $this->urlWithout($request, 'typ', $c['slug'])];
        }
        if ($priceFrom !== null) {
            $chips[] = ['label' => 'Od '.Fmt::price($priceFrom), 'url' => $this->urlWithout($request, 'cena_od')];
        }
        if ($priceTo !== null) {
            $chips[] = ['label' => 'Do '.Fmt::price($priceTo), 'url' => $this->urlWithout($request, 'cena_do')];
        }
        if ($inStock) {
            $chips[] = ['label' => 'Skladem', 'url' => $this->urlWithout($request, 'skladem')];
        }
        if ($discount) {
            $chips[] = ['label' => 'Sleva od '.$discount.Fmt::NBSP.'%', 'url' => $this->urlWithout($request, 'sleva')];
        }

        $keep = array_filter(['q' => $request->query('q')]);

        return [
            'products' => $products,
            'sort' => $sort,
            'sorts' => self::SORTS,
            'filters' => [
                'collections' => $collectionFacet,
                'types' => $typeFacet,
                'price_from' => $priceFrom,
                'price_to' => $priceTo,
                'bounds' => $bounds,
                'in_stock' => $inStock,
                'available_count' => $availableCount,
                'discount' => $discount,
                'discount_counts' => $discountCounts,
            ],
            'chips' => $chips,
            'clearUrl' => $request->url().($keep ? '?'.http_build_query($keep) : ''),
            'keep' => $keep,
        ];
    }

    /** @return list<string> */
    private function slugs(mixed $value): array
    {
        $values = is_array($value) ? $value : (is_string($value) && $value !== '' ? [$value] : []);

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => is_string($v) && preg_match('/^[a-z0-9-]{1,80}$/', $v) ? $v : null,
            $values,
        ))));
    }

    private function int(mixed $value): ?int
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : min(1_000_000, (int) $digits);
    }

    private function urlWithout(Request $request, string $param, ?string $value = null): string
    {
        $query = $request->query();
        unset($query['strana']);
        if ($value !== null && isset($query[$param]) && is_array($query[$param])) {
            $query[$param] = array_values(array_diff($query[$param], [$value]));
            if ($query[$param] === []) {
                unset($query[$param]);
            }
        } else {
            unset($query[$param]);
        }

        return $request->url().($query ? '?'.http_build_query($query) : '');
    }
}
