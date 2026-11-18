<?php

/*
 * Nastavení e-shopu Bílé zlato (demo). Vše, co se liší mezi lokálem a serverem, jde z .env.
 */
return [

    'name' => env('APP_NAME', 'Bílé zlato'),
    'tagline' => 'Porcelán z první ruky',

    // Demo: objednávka se neplatí, odejde jako nezávazná poptávka.
    'demo' => (bool) env('SHOP_DEMO', true),

    // Dokud je to demo, vyhledávače stránky neindexují (meta robots + X-Robots-Tag).
    'indexable' => (bool) env('SHOP_INDEXABLE', false),

    // Kam chodí poptávky (kopie e-mailem). Odpověď na e-mail jde rovnou zákazníkovi (reply-to).
    'inquiry_to' => env('INQUIRY_TO', 'kontakt@webtitan.cz'),

    // Prefix čísla poptávky: VB-2026-0001
    'inquiry_prefix' => env('INQUIRY_PREFIX', 'VB'),

    // Ceny v Kč včetně DPH
    'currency' => 'Kč',
    'free_shipping_from' => 2000,

    // Způsoby doručení (kód => nastavení). type: pickup_point | address | store
    'delivery' => [
        'zasilkovna' => ['label' => 'Zásilkovna – výdejní místo', 'price' => 79, 'type' => 'pickup_point', 'eta' => '1–2 pracovní dny'],
        'ppl' => ['label' => 'PPL – kurýr na adresu', 'price' => 129, 'type' => 'address', 'eta' => '1–2 pracovní dny'],
        'osobne' => ['label' => 'Osobní odběr – Praha', 'price' => 0, 'type' => 'store', 'eta' => 'do 24 hodin'],
    ],

    // Kontakt provozovatele (demo – doplní klient)
    'contact' => [
        'email' => env('SHOP_CONTACT_EMAIL', 'kontakt@webtitan.cz'),
        'phone' => env('SHOP_CONTACT_PHONE', ''),
        'address' => 'Praha',
        'hours' => 'Po–Pá 9–17',
    ],
];
