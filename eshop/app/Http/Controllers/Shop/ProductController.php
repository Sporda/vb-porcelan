<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart;
use App\Services\Favorites;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product, Favorites $favorites, Cart $cart): View
    {
        $product->load('collection', 'category');

        // Doplňte sadu: nejdřív stejná kolekce, pak stejná kategorie, nakonec cokoli se slevou.
        $related = Product::with('collection')
            ->where('id', '!=', $product->id)
            ->where(fn ($q) => $q->where('product_collection_id', $product->product_collection_id)->orWhere('category_id', $product->category_id))
            ->get()
            ->sortBy(fn (Product $p) => [$p->product_collection_id === $product->product_collection_id ? 0 : 1, $p->sort, $p->id])
            ->take(4)
            ->values();
        if ($related->count() < 4) {
            $related = $related->concat(
                Product::with('collection')->whereNotIn('id', $related->pluck('id')->push($product->id))
                    ->orderByDesc('is_featured')->orderBy('sort')->take(4 - $related->count())->get()
            );
        }

        // Obrázek k příběhu kolekce: obrázek kolekce, pokud není v galerii produktu.
        $galleryUrls = collect($product->images ?? [])->pluck('url')->map(fn ($u) => strtok((string) $u, '?'))->all();
        $storyImage = null;
        $collectionImage = $product->collection?->image_url;
        if ($collectionImage && ! in_array(strtok($collectionImage, '?'), $galleryUrls, true)) {
            $storyImage = ['url' => $collectionImage, 'alt' => 'Porcelán z kolekce '.$product->collection->name];
        } elseif ($product->product_collection_id) {
            // jinak první fotka jiného kusu stejné kolekce, která v galerii není
            $storyImage = Product::where('product_collection_id', $product->product_collection_id)
                ->where('id', '!=', $product->id)->orderBy('sort')->get()
                ->flatMap(fn (Product $p) => $p->images ?? [])
                ->first(fn ($img) => ! in_array(strtok((string) ($img['url'] ?? ''), '?'), $galleryUrls, true));
        }

        $options = $product->options ?? [];
        $selected = $cart->normalizeOption($product, (string) request()->query('baleni', $options[0]['code'] ?? ''));

        return view('shop.product', [
            'product' => $product,
            'related' => $related,
            'storyImage' => $storyImage,
            'isFavorite' => $favorites->has($product->id),
            'selectedOption' => $selected,
        ]);
    }
}
