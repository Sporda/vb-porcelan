<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Dokud je e-shop demo (SHOP_INDEXABLE=false), a vždy v administraci: X-Robots-Tag: noindex, nofollow. */
class RobotsTag
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('shop.indexable') || $request->is('admin', 'admin/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
