<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\NewsletterRequest;
use Illuminate\Http\RedirectResponse;

/** Odběr novinek v ukázkové verzi: e-mail se nikam neukládá, jen poděkujeme. */
class NewsletterController extends Controller
{
    public function store(NewsletterRequest $request): RedirectResponse
    {
        return redirect()->to(url()->previous(route('home')).'#novinky')
            ->with('newsletter', 'Děkujeme. V ukázkové verzi e-shopu adresy zatím neukládáme – po spuštění obchodu vám novinky začnou chodit.');
    }
}
