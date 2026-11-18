<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Catalog;
use Illuminate\View\View;

/** Informační stránky (obsah je v šablonách resources/views/shop/pages). */
class PageController extends Controller
{
    public function why(Catalog $catalog): View
    {
        return view('shop.pages.why', ['stats' => $catalog->stats()]);
    }

    public function about(Catalog $catalog): View
    {
        return view('shop.pages.about', ['stats' => $catalog->stats()]);
    }

    public function delivery(): View
    {
        return view('shop.pages.delivery', ['delivery' => config('shop.delivery', [])]);
    }

    public function contact(): View
    {
        return view('shop.pages.contact', ['contact' => config('shop.contact', [])]);
    }

    public function terms(): View
    {
        return view('shop.pages.terms');
    }

    public function privacy(): View
    {
        return view('shop.pages.privacy');
    }

    public function returns(): View
    {
        return view('shop.pages.returns');
    }
}
