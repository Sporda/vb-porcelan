<?php

use Illuminate\Support\Facades\Route;

// Obchod: úvod, kategorie, produkt, hledání, košík, oblíbené, poptávka, informační stránky.

Route::get('/', function () {
    return view('welcome');
})->name('home');
