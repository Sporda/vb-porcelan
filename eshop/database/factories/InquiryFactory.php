<?php

namespace Database\Factories;

use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Poptávka pro testy a lokální ukázková data (na serveru se nepoužívá – faker je jen v require-dev).
 * Model nemá HasFactory, proto: InquiryFactory::new()->withItems(3)->create().
 * Když má poptávka položky (withItems() nebo has()), součty a doprava se dopočítají z nich.
 *
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    private const PICKUP_POINTS = [
        'Z-BOX Praha 7 – Holešovice, Dělnická',
        'Zásilkovna Praha 3 – Žižkov, Seifertova',
        'Z-BOX Brno – Královo Pole, Palackého třída',
        'Zásilkovna Olomouc – Hodolany, Pavelčákova',
        'Z-BOX Plzeň – Slovany, Koterovská',
    ];

    private const NOTES = [
        'Prosím o zabalení jako dárek, je to k výročí.',
        'Bylo by možné doručit až po 15. prosinci? Do té doby nejsme doma.',
        'Máte ke stejné kolekci i hluboké talíře? Případně bych přiobjednala.',
        'Hodilo by se mi vyzvednutí v sobotu dopoledne.',
        'Prosím o fakturu na firmu, údaje jsou vyplněné.',
    ];

    public function definition(): array
    {
        $cz = fake('cs_CZ');
        $female = $cz->boolean(60);
        $first = $female ? $cz->firstNameFemale() : $cz->firstNameMale();
        $last = $female ? $cz->lastNameFemale() : $cz->lastNameMale();
        $method = $cz->randomElement(['zasilkovna', 'zasilkovna', 'ppl', 'osobne']);
        $itemsTotal = $cz->numberBetween(4, 60) * 100 - 10;

        return [
            'number' => sprintf('%s-%s-%04d', config('shop.inquiry_prefix', 'VB'), now()->format('Y'), $cz->unique()->numberBetween(1, 9999)),
            'status' => 'new',
            'customer_name' => $first.' '.$last,
            'email' => Str::slug($first.' '.$last, '.').'@example.com',
            'phone' => '+420 '.$cz->numerify('7## ### ###'),
            'company' => null,
            'ic' => null,
            ...self::deliveryAttributes($method, $itemsTotal),
            'note' => $cz->boolean(35) ? $cz->randomElement(self::NOTES) : null,
            'items_total' => $itemsTotal,
            'rrp_total' => (int) round($itemsTotal * 1.75, -1),
            'internal_note' => null,
            'mail_status' => 'sent',
            'mail_info' => fn (array $a) => $a['mail_status'] === 'sent'
                ? '→ '.config('shop.inquiry_to').' · <'.Str::lower(Str::random(24)).'@resend.dev>'
                : null,
            'mail_sent_at' => fn (array $a) => $a['mail_status'] === 'sent'
                ? Carbon::parse($a['created_at'] ?? now())->addSeconds(2)
                : null,
            'ip_hash' => hash('sha256', $cz->ipv4()),
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'created_at' => now(),
            'updated_at' => fn (array $a) => $a['created_at'],
        ];
    }

    public function configure(): static
    {
        // Položky přidané přes has() → dopočítat součty
        return $this->afterCreating(function (Inquiry $inquiry) {
            if ($inquiry->items()->exists()) {
                self::recalculate($inquiry);
            }
        });
    }

    /** Přidá 1–4 (nebo $count) položek a dopočítá součty. */
    public function withItems(?int $count = null): static
    {
        return $this->afterCreating(function (Inquiry $inquiry) use ($count) {
            InquiryItemFactory::new()
                ->count($count ?? fake()->numberBetween(1, 4))
                ->forInquiry($inquiry)
                ->create();
            self::recalculate($inquiry);
        });
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function processing(): static
    {
        return $this->status('processing');
    }

    public function confirmed(): static
    {
        return $this->status('confirmed');
    }

    public function cancelled(): static
    {
        return $this->status('cancelled');
    }

    public function mailFailed(string $error = 'Connection could not be established with host "smtp.resend.com:587": stream_socket_client(): Unable to connect (Connection timed out)'): static
    {
        return $this->state(fn () => ['mail_status' => 'failed', 'mail_info' => $error, 'mail_sent_at' => null]);
    }

    public function mailPending(): static
    {
        return $this->state(fn () => ['mail_status' => 'pending', 'mail_info' => null, 'mail_sent_at' => null]);
    }

    public function delivery(string $method): static
    {
        return $this->state(fn (array $a) => self::deliveryAttributes($method, (int) ($a['items_total'] ?? 0)));
    }

    public function company(string $name = 'Kavárna U Zlatého talíře s.r.o.', string $ic = '27082440'): static
    {
        return $this->state(fn () => ['company' => $name, 'ic' => $ic]);
    }

    /** Součty z položek; doprava zdarma od config('shop.free_shipping_from'). */
    public static function recalculate(Inquiry $inquiry): void
    {
        $items = $inquiry->items()->get();
        $itemsTotal = (int) $items->sum('line_total');
        $rrpTotal = (int) $items->sum(fn ($i) => ($i->rrp ?: $i->unit_price) * $i->quantity);
        $delivery = self::deliveryAttributes($inquiry->delivery_method, $itemsTotal);

        $inquiry->forceFill([
            'items_total' => $itemsTotal,
            'rrp_total' => $rrpTotal,
            'delivery_price' => $delivery['delivery_price'],
            'total' => $itemsTotal + $delivery['delivery_price'],
        ])->saveQuietly();
    }

    private static function deliveryAttributes(string $method, int $itemsTotal): array
    {
        $d = config('shop.delivery.'.$method) ?? ['label' => $method, 'price' => 0, 'type' => 'address'];
        $cz = fake('cs_CZ');
        $free = $itemsTotal >= (int) config('shop.free_shipping_from', 2000);
        $price = $free ? 0 : (int) $d['price'];
        $address = $d['type'] === 'address';

        return [
            'delivery_method' => $method,
            'delivery_label' => $d['label'],
            'delivery_price' => $price,
            'pickup_point' => $d['type'] === 'pickup_point' ? $cz->randomElement(self::PICKUP_POINTS) : null,
            'street' => $address ? $cz->streetName().' '.$cz->buildingNumber() : null,
            'city' => $address ? $cz->city() : null,
            'zip' => $address ? $cz->postcode() : null,
            'country' => 'CZ',
            'total' => $itemsTotal + $price,
        ];
    }
}
