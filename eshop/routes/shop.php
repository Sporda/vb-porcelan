<?php

use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\FavoritesController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\NewsletterController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\SearchController;
use Illuminate\Support\Facades\Route;

// Obchod: úvod, kategorie, produkt, hledání, košík, oblíbené, poptávka, informační stránky.

Route::get('/', HomeController::class)->name('home');

// Katalog
Route::get('/porcelan', [CatalogController::class, 'index'])->name('shop.index');
Route::get('/kategorie/{category:slug}', [CatalogController::class, 'category'])->name('shop.category');
Route::get('/kolekce', [CatalogController::class, 'collections'])->name('shop.collections');
Route::get('/kolekce/{collection:slug}', [CatalogController::class, 'collection'])->name('shop.collection');
Route::get('/vyprodej', [CatalogController::class, 'sale'])->name('shop.sale');
Route::get('/produkt/{product:slug}', [ProductController::class, 'show'])->name('shop.product');
Route::get('/hledat', SearchController::class)->name('shop.search');

// Košík (session)
Route::get('/kosik', [CartController::class, 'show'])->name('cart.show');
Route::post('/kosik/pridat', [CartController::class, 'add'])->name('cart.add');
Route::post('/kosik/upravit', [CartController::class, 'update'])->name('cart.update');
Route::post('/kosik/odebrat', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/kosik/vyprazdnit', [CartController::class, 'clear'])->name('cart.clear');

// Oblíbené (session)
Route::get('/oblibene', [FavoritesController::class, 'index'])->name('favorites.index');
Route::post('/oblibene/{product:slug}', [FavoritesController::class, 'toggle'])->name('favorites.toggle');

// Poptávka (pokladna bez platby)
Route::get('/objednavka', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/objednavka', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('/objednavka/odeslano/{inquiry:number}', [CheckoutController::class, 'thanks'])->middleware('signed')->name('checkout.thanks');

// Novinky e-mailem (ukázka – nic se neukládá)
Route::post('/novinky', [NewsletterController::class, 'store'])->middleware('throttle:10,1')->name('newsletter.store');

// Informační stránky
Route::get('/proc-tak-levne', [PageController::class, 'why'])->name('pages.why');
Route::get('/o-nas', [PageController::class, 'about'])->name('pages.about');
Route::get('/doprava-a-platba', [PageController::class, 'delivery'])->name('pages.delivery');
Route::get('/kontakt', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/obchodni-podminky', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/ochrana-osobnich-udaju', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/reklamace-a-vraceni', [PageController::class, 'returns'])->name('pages.returns');
