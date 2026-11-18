<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\Inquiry;
use App\Services\Cart;
use App\Services\CartLine;
use App\Services\InquiryMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pokladna = nezávazná poptávka. Ceny se přepočítají z databáze, poptávka se uloží (s položkami),
 * pošle e-mailem (InquiryMailer nikdy nevyhodí výjimku) a zákazník dostane podepsaný odkaz na poděkování.
 */
class CheckoutController extends Controller
{
    /** Jak dlouho platí odkaz na stránku s poděkováním. */
    public const THANKS_TTL_HOURS = 72;

    use \App\Http\Controllers\Shop\Concerns\UsesCart;

    public function show(): View|RedirectResponse
    {
        if ($this->cart()->isEmpty()) {
            return redirect()->route('cart.show')->with('status', 'Košík je prázdný – nejdřív si vyberte porcelán.');
        }

        return view('shop.checkout', [
            'cart' => $this->cart(),
            'delivery' => config('shop.delivery', []),
        ]);
    }

    public function store(CheckoutRequest $request, InquiryMailer $mailer): RedirectResponse
    {
        if ($this->cart()->isEmpty()) {
            return redirect()->route('cart.show')->with('status', 'Košík je prázdný – nejdřív si vyberte porcelán.');
        }

        // Honeypot: vyplněné skryté pole = robot. Tváříme se, že je hotovo, ale nic neukládáme.
        if (filled($request->input('website'))) {
            return redirect()->route('home')->with('status', 'Děkujeme, poptávku jsme přijali.');
        }

        $data = $request->validated();
        $code = $data['delivery'];
        $method = config('shop.delivery.'.$code);
        $lines = $this->cart()->lines();

        $inquiry = DB::transaction(function () use ($data, $code, $method, $lines, $request) {
            $itemsTotal = (int) $lines->sum(fn (CartLine $l) => $l->lineTotal());
            $rrpTotal = (int) $lines->sum(fn (CartLine $l) => $l->lineRrp());
            $deliveryPrice = $itemsTotal >= (int) config('shop.free_shipping_from', 2000) ? 0 : (int) $method['price'];
            $type = $method['type'];

            $inquiry = Inquiry::create([
                'number' => Inquiry::nextNumber(),
                'status' => 'new',
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'company' => $data['company'] ?? null,
                'ic' => $data['ic'] ?? null,
                'delivery_method' => $code,
                'delivery_label' => $method['label'],
                'delivery_price' => $deliveryPrice,
                'pickup_point' => $type === 'pickup_point' ? ($data['pickup_point'] ?? null) : null,
                'street' => $type === 'address' ? ($data['street'] ?? null) : null,
                'city' => $type === 'address' ? ($data['city'] ?? null) : null,
                'zip' => $type === 'address' ? ($data['zip'] ?? null) : null,
                'country' => 'CZ',
                'note' => $data['note'] ?? null,
                'items_total' => $itemsTotal,
                'rrp_total' => $rrpTotal > $itemsTotal ? $rrpTotal : null,
                'total' => $itemsTotal + $deliveryPrice,
                'mail_status' => 'pending',
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent' => Str::limit((string) $request->userAgent(), 297),
            ]);

            foreach ($lines as $l) {
                $inquiry->items()->create([
                    'product_id' => $l->product->id,
                    'product_name' => $l->product->name,
                    'collection_name' => $l->collectionName(),
                    'option_label' => $l->optionLabel(),
                    'unit_price' => $l->unitPrice(),
                    'rrp' => $l->unitRrp(),
                    'quantity' => $l->qty,
                    'line_total' => $l->lineTotal(),
                    'image_url' => $l->image(),
                ]);
            }

            return $inquiry;
        });

        // E-mail až po uložení; když selže, poptávka zůstává v DB a v administraci jde poslat znovu.
        $mailer->send($inquiry);

        $this->cart()->clear();

        return redirect()->to(URL::temporarySignedRoute(
            'checkout.thanks',
            now()->addHours(self::THANKS_TTL_HOURS),
            ['inquiry' => $inquiry->number],
        ));
    }

    public function thanks(Inquiry $inquiry): View
    {
        $inquiry->load('items');

        return view('shop.thanks', ['inquiry' => $inquiry]);
    }
}
