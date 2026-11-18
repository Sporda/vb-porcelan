<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductCollection;
use App\Services\Catalog;
use App\Services\ProductListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Výpisy: všechny produkty, kategorie, kolekce, výprodej. Všechny sdílejí šablonu shop.listing. */
class CatalogController extends Controller
{
    public function __construct(private ProductListing $listing, private Catalog $catalog) {}

    public function index(Request $request): View
    {
        return $this->render($request, null, [
            'mode' => 'all',
            'label' => 'Obchod · Villeroy & Boch',
            'h1' => ['Všechny', 'produkty'],
            'title' => 'Všechny produkty – porcelán Villeroy & Boch',
            'lead' => 'Celá nabídka originálního porcelánu Villeroy & Boch. Všechny kusy jsou nové a nepoužité, skladové odesíláme do 24 hodin.',
            'crumbs' => [['Obchod', null]],
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        return $this->render($request, fn (Builder $q) => $q->where('category_id', $category->id), [
            'mode' => 'category',
            'category' => $category,
            'label' => 'Kategorie · Villeroy & Boch',
            'h1' => [$category->name, null],
            'title' => $category->name.' Villeroy & Boch',
            'lead' => $category->description,
            'crumbs' => [['Obchod', route('shop.index')], [$category->name, null]],
            'image' => $category->image_url,
        ], ['types' => false]);
    }

    public function collection(Request $request, ProductCollection $collection): View
    {
        return $this->render($request, fn (Builder $q) => $q->where('product_collection_id', $collection->id), [
            'mode' => 'collection',
            'collection' => $collection,
            'label' => 'Kolekce · Villeroy & Boch',
            'h1' => [$collection->name, null],
            'title' => 'Kolekce '.$collection->name.' – Villeroy & Boch',
            'lead' => $collection->description,
            'crumbs' => [['Kolekce', route('shop.collections')], [$collection->name, null]],
            'image' => $collection->image_url,
        ], ['collections' => false]);
    }

    public function sale(Request $request): View
    {
        $pct = Catalog::SALE_PERCENT;

        return $this->render($request, fn (Builder $q) => Catalog::whereDiscountAtLeast($q, $pct), [
            'mode' => 'sale',
            'label' => 'Sleva od '.$pct.' %',
            'h1' => ['Výprodej', null],
            'title' => 'Výprodej – porcelán Villeroy & Boch se slevou od '.$pct.' %',
            'lead' => 'Kusy, které stojí aspoň o '.$pct.' % méně než doporučená cena výrobce. Většinou jde o končící řady a poslední kusy ze zásilky.',
            'crumbs' => [['Obchod', route('shop.index')], ['Výprodej', null]],
        ]);
    }

    public function collections(): View
    {
        return view('shop.collections', [
            'collections' => $this->catalog->collections(),
        ]);
    }

    private function render(Request $request, ?\Closure $context, array $page, array $facets = []): View
    {
        $data = $this->listing->build($request, $context, $facets);

        return view('shop.listing', $data + [
            'page' => $page,
            'categories' => $this->catalog->categories()->filter(fn ($c) => $c->products_count > 0)->values(),
            'avgDiscount' => $this->catalog->stats()['avg_discount'],
        ]);
    }
}
