<?php

namespace App\Http\Controllers\Shop\Concerns;

use App\Services\Cart;
use Illuminate\Http\Request;

/**
 * Košík pro aktuální požadavek. Laravel si instanci controlleru drží na routě (v testech i přes víc požadavků),
 * proto se košík s načtenými produkty nesmí pamatovat v konstruktoru – jen v rámci jednoho požadavku.
 */
trait UsesCart
{
    private ?Cart $cartInstance = null;

    private ?Request $cartRequest = null;

    protected function cart(): Cart
    {
        if ($this->cartInstance === null || $this->cartRequest !== request()) {
            $this->cartInstance = app(Cart::class);
            $this->cartRequest = request();
        }

        return $this->cartInstance;
    }
}
