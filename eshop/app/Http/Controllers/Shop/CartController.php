<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CartAddRequest;
use App\Http\Requests\Shop\CartUpdateRequest;
use App\Models\Product;
use App\Services\Cart;
use App\Services\CartLine;
use App\View\Fmt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Košík. Formuláře fungují bez JS (POST + přesměrování); s hlavičkou Accept: application/json
 * vrací JSON s počty a součty (a s X-Shop-Partial: cart i nové HTML obsahu košíku).
 */
class CartController extends Controller
{
    use \App\Http\Controllers\Shop\Concerns\UsesCart;

    public function show(): View
    {
        return view('shop.cart', ['cart' => $this->cart()]);
    }

    public function add(CartAddRequest $request): JsonResponse|RedirectResponse
    {
        $product = Product::with('collection')->findOrFail($request->integer('product_id'));
        $qty = max(1, $request->integer('qty', 1));
        $line = $this->cart()->add($product, $request->input('option'), $qty);

        $name = $product->name.($line?->optionLabel() ? ' · '.$line->optionLabel() : '');
        $message = 'Přidáno do košíku: '.$name;

        if ($request->expectsJson()) {
            return $this->json($request, $message, [
                'added' => [
                    'key' => $line?->key,
                    'name' => $product->name,
                    'option' => $line?->optionLabel(),
                    'collection' => $product->collection?->name,
                    'qty' => $qty,
                    'unit_price_formatted' => Fmt::price($line?->unitPrice() ?? $product->price),
                    'image' => Fmt::img($line?->image(), 160),
                ],
            ]);
        }

        return back()->with('cart_added', $line?->key)->with('status', $message);
    }

    public function update(CartUpdateRequest $request): JsonResponse|RedirectResponse
    {
        $key = (string) $request->input('key');
        $qty = $request->integer('qty');
        $ok = $this->cart()->setQty($key, $qty);
        $message = ! $ok ? 'Tato položka už v košíku není.' : ($qty > 0 ? 'Množství jsme upravili.' : 'Položku jsme z košíku odebrali.');

        if ($request->expectsJson()) {
            return $this->json($request, $message, ['ok' => $ok], $ok ? 200 : 404);
        }

        return redirect()->route('cart.show')->with('status', $message);
    }

    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['key' => ['required', 'string', 'max:80']]);
        $ok = $this->cart()->remove((string) $request->input('key'));
        $message = $ok ? 'Položku jsme z košíku odebrali.' : 'Tato položka už v košíku není.';

        if ($request->expectsJson()) {
            return $this->json($request, $message, ['ok' => $ok], $ok ? 200 : 404);
        }

        return redirect()->route('cart.show')->with('status', $message);
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart()->clear();
        $message = 'Košík je prázdný.';

        if ($request->expectsJson()) {
            return $this->json($request, $message);
        }

        return redirect()->route('cart.show')->with('status', $message);
    }

    private function json(Request $request, string $message, array $extra = [], int $status = 200): JsonResponse
    {
        $payload = array_merge(['ok' => true, 'message' => $message, 'cart' => $this->cart()->summary()], $extra);
        $payload['cart']['items'] = $this->cart()->lines()->map(fn (CartLine $l) => [
            'key' => $l->key,
            'qty' => $l->qty,
            'line_total' => $l->lineTotal(),
            'line_total_formatted' => Fmt::price($l->lineTotal()),
        ])->values()->all();

        if ($request->header('X-Shop-Partial') === 'cart') {
            $payload['html'] = view('shop.partials.cart-content', ['cart' => $this->cart()])->render();
        }

        return response()->json($payload, $status);
    }
}
