<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Catalog;
use App\Services\ProductListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Hledání v products.search_text (bez diakritiky, malými písmeny – Product::normalize). */
class SearchController extends Controller
{
    public function __invoke(Request $request, ProductListing $listing, Catalog $catalog): View
    {
        $q = Str::limit(trim((string) $request->query('q', '')), 80, '');
        $words = collect(preg_split('/\s+/', Product::normalize($q)) ?: [])
            ->map(fn ($w) => trim(str_replace(['%', '_', '\\'], '', $w), " \t\n\r\0\x0B.,;:!?\"'()[]"))
            ->filter(fn ($w) => mb_strlen($w) >= 2)
            ->unique()->take(6)->values();

        $page = [
            'mode' => 'search',
            'label' => 'Hledání',
            'h1' => $q !== '' ? ['Hledáte', '„'.$q.'“'] : ['Hledat', 'v obchodě'],
            'title' => $q !== '' ? 'Hledání: '.$q : 'Hledat v obchodě',
            'lead' => null,
            'crumbs' => [['Hledání', null]],
            'query' => $q,
        ];

        $categories = $catalog->categories()->filter(fn ($c) => $c->products_count > 0)->values();

        if ($words->isEmpty()) {
            return view('shop.listing', [
                'page' => $page + ['empty_query' => true],
                'products' => null,
                'categories' => $categories,
                'avgDiscount' => $catalog->stats()['avg_discount'],
            ]);
        }

        $data = $listing->build($request, function (Builder $b) use ($words) {
            foreach ($words as $w) {
                $b->where('search_text', 'like', '%'.$w.'%');
            }
        });

        return view('shop.listing', $data + [
            'page' => $page,
            'categories' => $categories,
            'avgDiscount' => $catalog->stats()['avg_discount'],
        ]);
    }
}
