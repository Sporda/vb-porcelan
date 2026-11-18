<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Favorites;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoritesController extends Controller
{
    public function __construct(private Favorites $favorites) {}

    public function index(): View
    {
        return view('shop.favorites', ['products' => $this->favorites->products()]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $on = $this->favorites->toggle($product);
        $message = $on ? 'Uloženo do oblíbených: '.$product->name : 'Odebráno z oblíbených: '.$product->name;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'on' => $on,
                'count' => $this->favorites->count(),
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }
}
