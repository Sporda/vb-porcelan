<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Položka poptávky (testy a lokální ukázková data). Bez vazby na katalog: product_id = null,
 * název, kolekce, cena a obrázek jsou „otištěné“ jako v pokladně.
 *
 * @extends Factory<InquiryItem>
 */
class InquiryItemFactory extends Factory
{
    protected $model = InquiryItem::class;

    /** Ukázkové zboží z návrhu Šalvěj: [kolekce, název, cena, doporučená cena, obrázek Pexels] */
    public const SAMPLES = [
        ['Anmut Gold', 'Jídelní talíř 27 cm se zlatou linkou', 749, 1290, 14132973],
        ['Royal', 'Hluboký talíř 24 cm', 389, 690, 291767],
        ['Old Luxembourg', 'Snídaňová sada – šálek, podšálek, talíř', 1190, 2090, 18977904],
        ['Château Blanc', 'Sada 4 talířů s reliéfem', 1490, 2690, 18977894],
        ['Crafted Cotton', 'Hrnek, talíř a miska – set', 990, 1790, 11065504],
        ['NewMoon', 'Miska 13 cm', 299, 540, 6944482],
        ['For Me', 'Hrnek 0,4 l, sada 2 ks', 459, 820, 10816681],
        ['Clever Baking', 'Zapékací mísa, sada 3 velikostí', 1290, 2290, 3756655],
        ['Audun Fleur', 'Konvice na kávu 1,1 l', 1690, 2990, 13008735],
        ['Artesano Original', 'Sada misek a talířů, 12 dílů', 2790, 4990, 8251778],
        ['Manufacture', 'Váza bílá, 2 velikosti', 890, 1590, 8217492],
    ];

    /** Balení: [popisek, počet kusů] */
    private const OPTIONS = [[null, 1], ['1 ks', 1], ['Sada 4 ks', 4], ['Sada 6 ks', 6]];

    public function definition(): array
    {
        [$collection, $name, $price, $rrp, $photo] = fake()->randomElement(self::SAMPLES);
        [$option, $units] = fake()->randomElement(self::OPTIONS);
        $quantity = fake()->randomElement([1, 1, 1, 2, 2, 3]);
        $unit = $price * $units;

        return [
            'inquiry_id' => InquiryFactory::new(),
            'product_id' => null,
            'product_name' => $name,
            'collection_name' => $collection,
            'option_label' => $option,
            'unit_price' => $unit,
            'rrp' => $rrp * $units,
            'quantity' => $quantity,
            'line_total' => $unit * $quantity,
            'image_url' => self::photo($photo),
        ];
    }

    public static function photo(int $id, int $width = 240): string
    {
        return "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$width}";
    }

    /** Konkrétní zboží ze SAMPLES (index) – pro testy. */
    public function sample(int $index, int $quantity = 1, ?string $option = null, int $units = 1): static
    {
        [$collection, $name, $price, $rrp, $photo] = self::SAMPLES[$index];

        return $this->state(fn () => [
            'product_name' => $name,
            'collection_name' => $collection,
            'option_label' => $option,
            'unit_price' => $price * $units,
            'rrp' => $rrp * $units,
            'quantity' => $quantity,
            'line_total' => $price * $units * $quantity,
            'image_url' => self::photo($photo),
        ]);
    }

    public function forInquiry(Inquiry $inquiry): static
    {
        return $this->state(fn () => ['inquiry_id' => $inquiry->id]);
    }
}
