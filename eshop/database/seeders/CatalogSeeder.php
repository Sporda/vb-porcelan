<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Database\Seeder;

/**
 * Ukázkový katalog: 7 kategorií, 12 kolekcí Villeroy & Boch, 35 produktů.
 * Idempotentní: záznam se podle slugu jen doplní, když chybí – existující data nikdy nepřepíše ani nesmaže
 * (spouští se při každém nasazení). Bez factories a Fakeru (na serveru nejsou dev balíčky).
 * Fotografie jsou ilustrační (Pexels), URL s parametrem w= – šablony si z nich skládají srcset.
 */
class CatalogSeeder extends Seeder
{
    /** Pexels URL dané fotografie (šířka 1200 px; šablony ji mění přes Fmt::img). */
    private static function img(int $id): string
    {
        return "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w=1200";
    }

    /** Ověřené fotografie: id => [alt, ohnisko ořezu]. */
    private const PHOTOS = [
        18977900 => ['Stoh bílých talířů s reliéfním okrajem, hluboký talíř a šálek na lněném ubrusu', '50% 45%'],
        18977902 => ['Stoh reliéfních talířů se šálkem a druhý šálek na lněném ubrusu', '50% 55%'],
        18977895 => ['Stoh bílých reliéfních talířů a šálek na lněném ubrusu u stěny', '40% 50%'],
        18977894 => ['Bílé talíře s reliéfním okrajem na lněném ubrusu', '50% 50%'],
        18977899 => ['Stoh talířů s reliéfním okrajem, šálek s podšálkem a talíř s šedým dekorem', '50% 45%'],
        18977904 => ['Stoh talířů s reliéfním okrajem a jemným šedým květinovým dekorem, nahoře šálek', '50% 40%'],
        18977901 => ['Detail stohu reliéfních talířů se šálkem a talířem s šedým dekorem', '50% 40%'],
        30859052 => ['Bílý šálek s podšálkem, mělký a hluboký talíř shora na světlém podkladu', '50% 50%'],
        291767 => ['Prostřený stůl s bílým hlubokým talířem s reliéfem a příborem', '35% 45%'],
        4083770 => ['Prostřené místo s bílými talíři, ubrouskem se zlatým kroužkem a příborem', '50% 50%'],
        28059310 => ['Prostřené místo s talíři se zlatým okrajem na dřevěném stole', '40% 50%'],
        16545885 => ['Bílý šálek se zlatou linkou na stohu podšálků', '45% 50%'],
        20075994 => ['Tři porcelánové šálky s podšálky: reliéfní, se zlatým ornamentem a se zlatou linkou', '50% 55%'],
        33352382 => ['Dva moka šálky se zlatým uchem a zlatým dekorem na podšálcích', '50% 70%'],
        11170803 => ['Bílá cukřenka s víčkem, reliéfní šálek a váza se suchými větvičkami', '40% 75%'],
        11170807 => ['Bílá váza se sušenými větvičkami na talířcích s vlnitým okrajem', '50% 50%'],
        6863362 => ['Bílá miska na talířku s reliéfním okrajem na mramorovém stolku, pohled shora', '70% 60%'],
        31391514 => ['Bílý šálek s podšálkem shora v ostrém slunečním světle', '50% 50%'],
        29625333 => ['Bílý kónický šálek s podšálkem na černém lesklém podkladu', '50% 45%'],
        10816681 => ['Bílé válcové hrnky a vysoký hrnek', '50% 60%'],
        10816678 => ['Bílá miska s ouškem a reliéfem, hrnek a vysoký džbán na podnosu', '50% 75%'],
        34788168 => ['Šálek s podšálkem s květinovým dekorem a zlatou linkou na prostřeném stole', '65% 65%'],
        3268625 => ['Naskládané šálky s podšálky s růžovým květinovým dekorem', '30% 50%'],
        34788195 => ['Prostřený stůl k čaji s květovanými šálky se zlatou linkou', '50% 60%'],
        6944482 => ['Bílá miska organického tvaru na kamenné desce', '50% 75%'],
        6083568 => ['Bílá mléčenka na skládaných zapékacích miskách, misky a talíře ve slunci', '55% 30%'],
        3756655 => ['Tři bílé obdélníkové zapékací mísy s úchyty', '50% 55%'],
        7993285 => ['Bílý porcelán v policích: džbán, omáčníky, mléčenka, misky a stohy talířů', '50% 60%'],
        8536923 => ['Prostřený stůl s bílou polévkovou mísou, omáčníkem a hlubokými talíři', '50% 65%'],
        4459292 => ['Bílé obdélníkové servírovací tácy různých velikostí a malé misky', '45% 40%'],
        38294502 => ['Krémová polévková mísa s reliéfem a zlatou linkou, vedle odložené víko', '40% 60%'],
        38294498 => ['Detail víka porcelánové mísy se zlatými linkami a ozdobným úchytem', '50% 45%'],
        18784559 => ['Bílá dělená servírovací miska s dřevěným úchytem u okna', '40% 60%'],
        685527 => ['Bílý espresso šálek s podšálkem na tmavém dřevěném stole', '40% 50%'],
        129207 => ['Espresso šálek s podšálkem shora na černé ploše', '50% 55%'],
        24346972 => ['Bílá oválná čajová konvice na dřevěném stole', '50% 55%'],
        18421962 => ['Bílá baňatá čajová konvice na prostřeném stole', '35% 35%'],
        36111898 => ['Bílá čajová konvice, šálek s podšálkem a porcelánová lžička', '45% 45%'],
        28772744 => ['Porcelánová konvice na kávu s barokním reliéfním okrajem', '50% 55%'],
        4963895 => ['Bílá válcová mléčenka na dřevěném tácku', '55% 55%'],
        6974459 => ['Bílá čajová konvice se zlatou linkou na prostřeném stolku', '40% 50%'],
        6970495 => ['Čajový servis: bílá konvice, mléčenka, cukřenka a šálky se zlatou linkou', '50% 45%'],
        8217492 => ['Matné bílé vázy a žebrovaná vázička se sušenou trávou na podnosu', '55% 72%'],
        14608171 => ['Tři matné bílé porcelánové vázy na sytě červeném pozadí', '50% 60%'],
        38094623 => ['Bílé porcelánové vázy, reliéfní šálek a mléčenka na červeném ubrusu', '50% 60%'],
        5040767 => ['Tři miniaturní matné džbánky, v jednom větvička sušené květiny', '50% 70%'],
        11507900 => ['Bílá váza a mělký talíř se sušenými květy na lněné látce, pohled shora', '60% 45%'],
    ];

    private static function photos(int ...$ids): array
    {
        return array_map(fn (int $id) => [
            'url' => self::img($id),
            'alt' => self::PHOTOS[$id][0],
            'focus' => self::PHOTOS[$id][1],
        ], $ids);
    }

    public function run(): void
    {
        $categories = [
            ['talire', 'Talíře', 'Mělké, hluboké i dezertní talíře z originálního porcelánu Villeroy & Boch, jednotlivě i v sadách.', 18977904],
            ['hrnky-a-salky', 'Hrnky a šálky', 'Šálky s podšálky, hrnky na každý den i sady pro hosty.', 31391514],
            ['misky', 'Misky', 'Misky na polévku, müsli i dezerty. Všechny do myčky, většina i do mikrovlnné trouby.', 6863362],
            ['servirovani', 'Servírování', 'Mísy, tácy, omáčníky a zapékací mísy, které jdou z kuchyně rovnou na stůl.', 7993285],
            ['jidelni-sety', 'Jídelní sety', 'Sady talířů pro čtyři i šest osob – levněji, než kdybyste kupovali kusy zvlášť.', 18977902],
            ['kava-a-caj', 'Káva a čaj', 'Konvice, espresso a moka šálky, cukřenky a mléčenky.', 36111898],
            ['dekorace', 'Dekorace', 'Porcelánové vázy a džbánky, matné i lesklé.', 11507900],
        ];
        foreach ($categories as $i => [$slug, $name, $description, $photo]) {
            Category::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'image_url' => self::img($photo),
                'sort' => ($i + 1) * 10,
            ]);
        }

        $collections = [
            ['royal', 'Royal', 'Hladký bílý porcelán bez dekoru. Mělké a hluboké talíře, misky i šálky, které spolu ladí a snesou každodenní provoz.', 30859052],
            ['afina', 'Afina', 'Šálky, cukřenky a dezertní talíře s jemným reliéfem. Ladí k sobě i k hladkému bílému porcelánu.', 11170803],
            ['newmoon', 'NewMoon', 'Misky a džbánky organických tvarů, jako by vznikly na hrnčířském kruhu. Pevný porcelán do myčky.', 6083568],
            ['anmut-gold', 'Anmut Gold', 'Tenkostěnný kostní porcelán se zlatou linkou na okraji. Na sváteční stůl i na nedělní oběd.', 16545885],
            ['old-luxembourg', 'Old Luxembourg', 'Drobný květinový dekor a reliéfní okraj. Klasika, kterou Villeroy & Boch vyrábí od 18. století.', 18977901],
            ['chateau-blanc', 'Château Blanc', 'Bílé talíře s plastickým reliéfem na okraji. Působí slavnostně a přitom se snadno kombinují s hladkým porcelánem.', 18977895],
            ['artesano-original', 'Artesano Original', 'Bílý porcelán s reliéfním okrajem a široká nabídka servírovacích kusů – omáčníky, mísy i misky do sady.', 6863362],
            ['for-me', 'For Me', 'Moderní tvary pro každý den: hrnky, misky a servírovací kusy, které se dobře stohují.', 10816681],
            ['coffee-passion', 'Coffee Passion', 'Šálky na espresso, cappuccino i lungo. Silné stěny drží teplotu kávy.', 685527],
            ['clever-baking', 'Clever Baking', 'Zapékací mísy a misky z porcelánu, které jdou z trouby rovnou na stůl.', 3756655],
            ['audun-fleur', 'Audun Fleur', 'Květinové dekory na bílém porcelánu. Každý kus má jiný motiv, dohromady ladí.', 34788168],
            ['manufacture', 'Manufacture', 'Matné bílé vázy s jemným žebrováním, které vypadají jako ručně točené.', 8217492],
        ];
        foreach ($collections as $i => [$slug, $name, $description, $photo]) {
            ProductCollection::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'image_url' => self::img($photo),
                'sort' => ($i + 1) * 10,
            ]);
        }

        $cat = Category::pluck('id', 'slug');
        $col = ProductCollection::pluck('id', 'slug');

        foreach ($this->products() as $i => $p) {
            if (Product::where('slug', $p['slug'])->exists()) {
                continue;
            }
            $categoryId = $cat[$p['category']] ?? null;
            if (! $categoryId) {
                continue; // kategorii mezitím někdo smazal – produkt nedoplňujeme
            }
            $product = new Product;
            $product->fill([
                'category_id' => $categoryId,
                'product_collection_id' => $col[$p['collection']] ?? null,
                'slug' => $p['slug'],
                'name' => $p['name'],
                'subtitle' => $p['subtitle'],
                'description' => $p['description'],
                'price' => $p['price'],
                'rrp' => $p['rrp'],
                'stock' => $p['stock'] ?? 'in_stock',
                'stock_qty' => $p['stock_qty'] ?? null,
                'badge' => $p['badge'] ?? null,
                'dimensions' => $p['dimensions'] ?? null,
                'material' => $p['material'] ?? 'porcelán',
                'dishwasher' => $p['dishwasher'] ?? true,
                'microwave' => $p['microwave'] ?? true,
                'set_contents' => $p['set_contents'] ?? null,
                'options' => $p['options'] ?? null,
                'images' => $p['images'],
                'is_featured' => $p['featured'] ?? false,
                'sort' => ($i + 1) * 10,
            ]);
            $product->save(); // saving() doplní search_text
        }
    }

    /** Balení: [kód, popisek, kusů, cena, doporučená cena]. */
    private static function packs(array ...$packs): array
    {
        return array_map(fn (array $o) => [
            'code' => (string) $o[0],
            'label' => $o[1],
            'units' => $o[2],
            'price' => $o[3],
            'rrp' => $o[4],
        ], $packs);
    }

    private function products(): array
    {
        return [
            // --- Talíře ---
            [
                'slug' => 'royal-melky-talir-27', 'category' => 'talire', 'collection' => 'royal',
                'name' => 'Mělký talíř 27 cm', 'subtitle' => 'Čistý bílý tvar, který snese každodenní provoz.',
                'description' => "Jídelní talíř z kolekce Royal bez dekoru, s jemně zvednutým okrajem. Hodí se k hladkým i reliéfním řadám, takže sadu můžete kdykoli doplnit.\n\nPorcelán je pálený při vysoké teplotě, proto je tvrdý a odolný proti oštípnutí.",
                'price' => 379, 'rrp' => 690, 'badge' => 'bestseller', 'featured' => true, 'stock_qty' => 48,
                'dimensions' => 'Ø 27 cm, výška 2,5 cm',
                'options' => self::packs(['1', '1 ks', 1, 379, 690], ['6', 'Sada 6 ks', 6, 2090, 3790]),
                'images' => self::photos(30859052, 291767, 4083770),
            ],
            [
                'slug' => 'royal-hluboky-talir-24', 'category' => 'talire', 'collection' => 'royal',
                'name' => 'Hluboký talíř 24 cm', 'subtitle' => 'Na polévku, těstoviny i salát.',
                'description' => 'Hluboký talíř z kolekce Royal s širokým okrajem. Pojme i vydatnou porci polévky a dobře se stohuje s mělkými talíři stejné řady.',
                'price' => 369, 'rrp' => 590, 'stock_qty' => 36,
                'dimensions' => 'Ø 24 cm, výška 4 cm',
                'options' => self::packs(['1', '1 ks', 1, 369, 590], ['6', 'Sada 6 ks', 6, 1990, 3290]),
                'images' => self::photos(291767, 30859052),
            ],
            [
                'slug' => 'anmut-gold-jidelni-talir-27', 'category' => 'talire', 'collection' => 'anmut-gold',
                'name' => 'Jídelní talíř 27 cm se zlatou linkou', 'subtitle' => 'Kostní porcelán s ručně nanášenou zlatou linkou.',
                'description' => "Talíř z kostního porcelánu Anmut Gold je lehký a tenkostěnný, a přesto pevný. Zlatá linka na okraji dodá stolu sváteční ráz.\n\nKvůli zlatému dekoru nepatří do mikrovlnné trouby.",
                'price' => 749, 'rrp' => 1290, 'badge' => 'bestseller', 'featured' => true, 'stock_qty' => 20,
                'dimensions' => 'Ø 27 cm, výška 2 cm', 'material' => 'kostní porcelán (bone china)', 'microwave' => false,
                'options' => self::packs(['1', '1 ks', 1, 749, 1290], ['4', 'Sada 4 ks', 4, 2790, 4790]),
                'images' => self::photos(4083770, 28059310, 16545885),
            ],
            [
                'slug' => 'chateau-blanc-sada-4-taliru', 'category' => 'talire', 'collection' => 'chateau-blanc',
                'name' => 'Sada 4 jídelních talířů s reliéfem', 'subtitle' => 'Jemně tvarovaný okraj, který na stole chytá světlo.',
                'description' => 'Sada čtyř jídelních talířů z kolekce Château Blanc. Reliéfní okraj inspirovaný francouzskou klasikou dodá stolu slavnostní ráz, a přitom zůstává decentní – talíře snadno zkombinujete s hladkým bílým porcelánem.',
                'price' => 1490, 'rrp' => 2690, 'badge' => 'bestseller', 'featured' => true, 'stock_qty' => 14,
                'dimensions' => 'Ø 27 cm, výška 2,5 cm',
                'set_contents' => ['4× jídelní talíř Ø 27 cm'],
                'images' => self::photos(18977894, 18977899, 18977902, 18977895),
            ],
            [
                'slug' => 'old-luxembourg-dezertni-talir-21', 'category' => 'talire', 'collection' => 'old-luxembourg',
                'name' => 'Dezertní talíř 21 cm', 'subtitle' => 'Drobné šedomodré květy a reliéfní okraj.',
                'description' => 'Dezertní talíř s klasickým dekorem Old Luxembourg. Hodí se ke koláči, předkrmu i jako podtalíř pod misku.',
                'price' => 489, 'rrp' => 790, 'stock_qty' => 22,
                'dimensions' => 'Ø 21 cm, výška 2 cm',
                'options' => self::packs(['1', '1 ks', 1, 489, 790], ['6', 'Sada 6 ks', 6, 2690, 4390]),
                'images' => self::photos(18977904, 18977901),
            ],
            [
                'slug' => 'afina-dezertni-talir-22', 'category' => 'talire', 'collection' => 'afina',
                'name' => 'Dezertní talíř 22 cm s reliéfem', 'subtitle' => 'Vlnitý okraj k šálkům a cukřence Afina.',
                'description' => 'Menší talíř s vlnitým reliéfním okrajem. Doplní kávový servis Afina, ale stejně dobře poslouží jako talířek na chléb nebo předkrm.',
                'price' => 329, 'rrp' => 490, 'stock_qty' => 30,
                'dimensions' => 'Ø 22 cm, výška 2 cm',
                'images' => self::photos(11170807, 6863362),
            ],

            // --- Hrnky a šálky ---
            [
                'slug' => 'afina-salek-na-kavu-s-podsalkem', 'category' => 'hrnky-a-salky', 'collection' => 'afina',
                'name' => 'Šálek na kávu s podšálkem', 'subtitle' => 'Reliéfní šálek na 0,2 l kávy.',
                'description' => 'Šálek s podšálkem z kolekce Afina s jemným reliéfem. Objem 0,2 l stačí na kávu s mlékem i na čaj.',
                'price' => 429, 'rrp' => 760, 'badge' => 'bestseller', 'featured' => true, 'stock_qty' => 40,
                'dimensions' => 'šálek 0,2 l, podšálek Ø 15 cm',
                'options' => self::packs(['1', '1 ks', 1, 429, 760], ['2', 'Sada 2 ks', 2, 819, 1390], ['6', 'Sada 6 ks', 6, 2290, 3990]),
                'images' => self::photos(20075994, 11170803, 31391514),
            ],
            [
                'slug' => 'royal-salek-na-kavu-s-podsalkem', 'category' => 'hrnky-a-salky', 'collection' => 'royal',
                'name' => 'Šálek na kávu s podšálkem 0,2 l', 'subtitle' => 'Kónický tvar bez dekoru.',
                'description' => 'Hladký bílý šálek z kolekce Royal. Kónický tvar dobře drží pěnu cappuccina a šálek se pohodlně drží i bez ucha za prsty.',
                'price' => 369, 'rrp' => 590, 'stock_qty' => 26,
                'dimensions' => 'šálek 0,2 l, podšálek Ø 14 cm',
                'options' => self::packs(['1', '1 ks', 1, 369, 590], ['6', 'Sada 6 ks', 6, 1990, 3290]),
                'images' => self::photos(29625333, 31391514),
            ],
            [
                'slug' => 'for-me-hrnek-04-l', 'category' => 'hrnky-a-salky', 'collection' => 'for-me',
                'name' => 'Hrnek 0,4 l', 'subtitle' => 'Velký hrnek na ranní kávu nebo čaj.',
                'description' => 'Válcový hrnek z kolekce For Me s pohodlným uchem. Stěny jsou silnější, takže nápoj déle drží teplotu.',
                'price' => 269, 'rrp' => 420, 'stock_qty' => 60,
                'dimensions' => 'objem 0,4 l, výška 10 cm',
                'options' => self::packs(['1', '1 ks', 1, 269, 420], ['2', 'Sada 2 ks', 2, 499, 790]),
                'images' => self::photos(10816681, 10816678),
            ],
            [
                'slug' => 'anmut-gold-cajovy-salek-s-podsalkem', 'category' => 'hrnky-a-salky', 'collection' => 'anmut-gold',
                'name' => 'Čajový šálek s podšálkem', 'subtitle' => 'Tenký kostní porcelán a zlatá linka.',
                'description' => 'Široký čajový šálek z kolekce Anmut Gold. Kostní porcelán je průsvitný a lehký, zlatá linka lemuje okraj šálku i podšálku. Do mikrovlnné trouby nepatří.',
                'price' => 790, 'rrp' => 1190, 'stock_qty' => 12,
                'dimensions' => 'šálek 0,2 l, podšálek Ø 16 cm', 'material' => 'kostní porcelán (bone china)', 'microwave' => false,
                'images' => self::photos(16545885, 20075994),
            ],
            [
                'slug' => 'audun-fleur-cajovy-salek-s-podsalkem', 'category' => 'hrnky-a-salky', 'collection' => 'audun-fleur',
                'name' => 'Čajový šálek s podšálkem, květinový dekor', 'subtitle' => 'Každý šálek s jiným květem.',
                'description' => 'Šálek s podšálkem z kolekce Audun Fleur. Květinové motivy se v kolekci liší kus od kusu, takže sada šálků vypadá jako sbírka.',
                'price' => 459, 'rrp' => 690, 'stock' => 'on_order',
                'dimensions' => 'šálek 0,23 l, podšálek Ø 16 cm', 'microwave' => false,
                'images' => self::photos(34788168, 3268625, 34788195),
            ],

            // --- Misky ---
            [
                'slug' => 'newmoon-miska-13', 'category' => 'misky', 'collection' => 'newmoon',
                'name' => 'Miska 13 cm', 'subtitle' => 'Organický tvar na müsli, polévku i dip.',
                'description' => 'Miska z kolekce NewMoon s nepravidelným okrajem, jako by vznikla na hrnčířském kruhu. Je z pevného porcelánu a patří do myčky i do mikrovlnné trouby.',
                'price' => 299, 'rrp' => 540, 'badge' => 'bestseller', 'featured' => true, 'stock_qty' => 44,
                'dimensions' => 'Ø 13 cm, objem 0,45 l',
                'options' => self::packs(['1', '1 ks', 1, 299, 540], ['4', 'Sada 4 ks', 4, 1090, 1890]),
                'images' => self::photos(6944482, 6083568),
            ],
            [
                'slug' => 'afina-miska-na-musli-15', 'category' => 'misky', 'collection' => 'afina',
                'name' => 'Miska na müsli 15 cm', 'subtitle' => 'Reliéfní miska k dezertním talířům Afina.',
                'description' => 'Hlubší miska s jemným reliéfem. Na snídani, na polévku i na ovoce; postavená na dezertním talíři Afina tvoří hezký dvojdílný set.',
                'price' => 289, 'rrp' => 450, 'stock_qty' => 28,
                'dimensions' => 'Ø 15 cm, objem 0,6 l',
                'images' => self::photos(6863362, 11170807),
            ],
            [
                'slug' => 'for-me-miska-s-ouskem', 'category' => 'misky', 'collection' => 'for-me',
                'name' => 'Miska na polévku s ouškem 0,45 l', 'subtitle' => 'Polévka se z ní dá i pít.',
                'description' => 'Miska s jedním ouškem z kolekce For Me. Na polévku, kakao nebo ovesnou kaši – ouško se drží i u horkého obsahu.',
                'price' => 279, 'rrp' => 450, 'stock_qty' => 18,
                'dimensions' => 'Ø 12 cm, objem 0,45 l',
                'images' => self::photos(10816678, 10816681),
            ],
            [
                'slug' => 'clever-baking-zapekaci-misky-sada-4', 'category' => 'misky', 'collection' => 'clever-baking',
                'name' => 'Zapékací misky 10 cm, sada 4 ks', 'subtitle' => 'Na crème brûlée, suflé i ragú.',
                'description' => 'Kulaté misky s vroubkovanou stěnou. Z trouby jdou rovnou na stůl a dobře se stohují.',
                'price' => 590, 'rrp' => 890, 'stock_qty' => 15,
                'dimensions' => 'Ø 10 cm, objem 0,2 l',
                'set_contents' => ['4× zapékací miska Ø 10 cm'],
                'images' => self::photos(6083568, 7993285),
            ],

            // --- Servírování ---
            [
                'slug' => 'clever-baking-zapekaci-misy-sada-3', 'category' => 'servirovani', 'collection' => 'clever-baking',
                'name' => 'Zapékací mísy, sada 3 velikostí', 'subtitle' => 'Malá, střední a velká mísa s úchyty.',
                'description' => 'Tři obdélníkové zapékací mísy s úchyty, které do sebe zapadnou a v kuchyňské skříňce zaberou místo jedné. Na lasagne, gratin i pečenou zeleninu.',
                'price' => 1290, 'rrp' => 2290, 'stock_qty' => 9,
                'dimensions' => '22 × 15 cm, 28 × 19 cm, 34 × 23 cm',
                'set_contents' => ['1× mísa 22 × 15 cm', '1× mísa 28 × 19 cm', '1× mísa 34 × 23 cm'],
                'images' => self::photos(3756655, 6083568),
            ],
            [
                'slug' => 'royal-servirovaci-tacy-sada-3', 'category' => 'servirovani', 'collection' => 'royal',
                'name' => 'Obdélníkové servírovací tácy, sada 3 ks', 'subtitle' => 'Na sýry, sushi i dezerty.',
                'description' => 'Tři ploché tácy v různých velikostech. Na stole vytvoří jednotnou linii a po večeři se zasunou do sebe.',
                'price' => 1090, 'rrp' => 1690, 'badge' => 'novinka', 'stock_qty' => 11,
                'dimensions' => '20 × 10 cm, 28 × 14 cm, 36 × 18 cm',
                'set_contents' => ['1× tác 20 × 10 cm', '1× tác 28 × 14 cm', '1× tác 36 × 18 cm'],
                'images' => self::photos(4459292, 7993285),
            ],
            [
                'slug' => 'artesano-original-omacnik', 'category' => 'servirovani', 'collection' => 'artesano-original',
                'name' => 'Omáčník 0,4 l', 'subtitle' => 'K nedělní pečeni i ke svíčkové.',
                'description' => 'Omáčník s reliéfním okrajem z kolekce Artesano Original. Pohodlné ucho a hubička, ze které omáčka nekape.',
                'price' => 690, 'rrp' => 1090, 'stock_qty' => 10,
                'dimensions' => 'objem 0,4 l, délka 20 cm',
                'images' => self::photos(8536923, 7993285),
            ],
            [
                'slug' => 'anmut-gold-polevkova-misa', 'category' => 'servirovani', 'collection' => 'anmut-gold',
                'name' => 'Polévková mísa s víkem 2,3 l', 'subtitle' => 'Terina se zlatou linkou na sváteční stůl.',
                'description' => 'Polévková mísa s víkem a ozdobným úchytem. Pojme polévku pro šest osob a víko udrží teplotu, než se všichni posadí. Do mikrovlnné trouby nepatří.',
                'price' => 2490, 'rrp' => 3990, 'stock' => 'last_pieces', 'stock_qty' => 2, 'badge' => 'posledni-kusy',
                'dimensions' => 'objem 2,3 l, Ø 24 cm', 'material' => 'kostní porcelán (bone china)', 'microwave' => false,
                'images' => self::photos(38294502, 38294498, 8536923),
            ],
            [
                'slug' => 'for-me-delena-servirovaci-miska', 'category' => 'servirovani', 'collection' => 'for-me',
                'name' => 'Dělená servírovací miska s úchytem', 'subtitle' => 'Tři přihrádky na oříšky, olivy a dipy.',
                'description' => 'Servírovací miska rozdělená na tři části, s dřevěným úchytem uprostřed. Úchyt se před mytím odšroubuje.',
                'price' => 790, 'rrp' => 1190, 'badge' => 'novinka', 'stock_qty' => 16, 'microwave' => false,
                'dimensions' => 'Ø 25 cm, výška s úchytem 14 cm', 'material' => 'porcelán, úchyt z akáciového dřeva',
                'images' => self::photos(18784559, 4459292),
            ],

            // --- Jídelní sety ---
            [
                'slug' => 'royal-jidelni-sada-12-dilu', 'category' => 'jidelni-sety', 'collection' => 'royal',
                'name' => 'Jídelní sada pro 4 osoby, 12 dílů', 'subtitle' => 'Mělké, hluboké a dezertní talíře v jedné krabici.',
                'description' => 'Základ bílého stolu pro čtyři: mělké, hluboké i dezertní talíře z kolekce Royal. Sadu lze kdykoli doplnit jednotlivými kusy.',
                'price' => 2990, 'rrp' => 5290, 'featured' => true, 'stock_qty' => 8,
                'dimensions' => 'talíře Ø 27, 24 a 21 cm',
                'set_contents' => ['4× mělký talíř Ø 27 cm', '4× hluboký talíř Ø 24 cm', '4× dezertní talíř Ø 21 cm'],
                'images' => self::photos(18977895, 30859052, 291767),
            ],
            [
                'slug' => 'chateau-blanc-jidelni-sada-18-dilu', 'category' => 'jidelni-sety', 'collection' => 'chateau-blanc',
                'name' => 'Jídelní sada pro 6 osob, 18 dílů', 'subtitle' => 'Reliéfní talíře pro celou rodinu.',
                'description' => 'Osmnáct talířů s reliéfním okrajem Château Blanc: na hlavní jídlo, polévku i dezert pro šest osob.',
                'price' => 5490, 'rrp' => 8490, 'stock_qty' => 5,
                'dimensions' => 'talíře Ø 27, 23 a 21 cm',
                'set_contents' => ['6× mělký talíř Ø 27 cm', '6× hluboký talíř Ø 23 cm', '6× dezertní talíř Ø 21 cm'],
                'images' => self::photos(18977902, 18977900, 18977894),
            ],
            [
                'slug' => 'artesano-original-sada-misek-a-taliru', 'category' => 'jidelni-sety', 'collection' => 'artesano-original',
                'name' => 'Sada misek a talířů, 12 dílů', 'subtitle' => 'Talíře a misky s reliéfním okrajem pro čtyři.',
                'description' => 'Mělké a dezertní talíře doplněné miskami na polévku nebo müsli. Reliéfní okraj Artesano Original ladí s omáčníkem a servírovacími mísami stejné kolekce.',
                'price' => 2790, 'rrp' => 4990, 'badge' => 'novinka', 'stock_qty' => 7,
                'dimensions' => 'talíře Ø 27 a 22 cm, misky Ø 15 cm',
                'set_contents' => ['4× mělký talíř Ø 27 cm', '4× dezertní talíř Ø 22 cm', '4× miska Ø 15 cm'],
                'images' => self::photos(7993285, 6863362, 18977895),
            ],
            [
                'slug' => 'old-luxembourg-snidanova-sada', 'category' => 'jidelni-sety', 'collection' => 'old-luxembourg',
                'name' => 'Snídaňová sada – šálek, podšálek, talíř', 'subtitle' => 'Tři kusy s dekorem drobných květů.',
                'description' => 'Šálek s podšálkem a snídaňový talíř s dekorem Old Luxembourg. Hezký dárek, který jde později doplnit o další kusy.',
                'price' => 1190, 'rrp' => 2090, 'stock_qty' => 9,
                'dimensions' => 'šálek 0,2 l, talíř Ø 21 cm',
                'set_contents' => ['1× šálek na kávu 0,2 l', '1× podšálek Ø 15 cm', '1× snídaňový talíř Ø 21 cm'],
                'images' => self::photos(18977899, 18977904),
            ],
            [
                'slug' => 'anmut-gold-jidelni-sada-18-dilu', 'category' => 'jidelni-sety', 'collection' => 'anmut-gold',
                'name' => 'Jídelní sada pro 6 osob, 18 dílů, zlatá linka', 'subtitle' => 'Kostní porcelán na sváteční stůl.',
                'description' => 'Kompletní jídelní sada Anmut Gold pro šest osob. Kostní porcelán se zlatou linkou je lehký, ale pevný; do myčky ano, do mikrovlnné trouby ne.',
                'price' => 7990, 'rrp' => 13990, 'stock' => 'last_pieces', 'stock_qty' => 2, 'badge' => 'posledni-kusy',
                'dimensions' => 'talíře Ø 27, 24 a 22 cm', 'material' => 'kostní porcelán (bone china)', 'microwave' => false,
                'set_contents' => ['6× jídelní talíř Ø 27 cm', '6× hluboký talíř Ø 24 cm', '6× dezertní talíř Ø 22 cm'],
                'images' => self::photos(28059310, 4083770, 16545885),
            ],

            // --- Káva a čaj ---
            [
                'slug' => 'coffee-passion-espresso-salek-2-ks', 'category' => 'kava-a-caj', 'collection' => 'coffee-passion',
                'name' => 'Espresso šálek s podšálkem, 2 ks', 'subtitle' => 'Silná stěna, která drží teplotu.',
                'description' => 'Dva espresso šálky s podšálky z kolekce Coffee Passion. Silné dno a stěny šálek předehřejí a espresso v nich tak rychle nevychladne.',
                'price' => 549, 'rrp' => 990, 'badge' => 'novinka', 'stock_qty' => 24,
                'dimensions' => 'šálek 0,08 l, podšálek Ø 12 cm',
                'set_contents' => ['2× espresso šálek 0,08 l', '2× podšálek Ø 12 cm'],
                'images' => self::photos(685527, 129207),
            ],
            [
                'slug' => 'anmut-gold-moka-salek-2-ks', 'category' => 'kava-a-caj', 'collection' => 'anmut-gold',
                'name' => 'Moka šálek s podšálkem, 2 ks', 'subtitle' => 'Malé šálky se zlatým uchem.',
                'description' => 'Dva moka šálky se zlatým uchem a zlatým dekorem na podšálku. Na espresso nebo turka po večeři. Do mikrovlnné trouby nepatří.',
                'price' => 890, 'rrp' => 1390, 'stock_qty' => 6,
                'dimensions' => 'šálek 0,1 l, podšálek Ø 12 cm', 'material' => 'kostní porcelán (bone china)', 'microwave' => false,
                'set_contents' => ['2× moka šálek 0,1 l', '2× podšálek Ø 12 cm'],
                'images' => self::photos(33352382, 20075994),
            ],
            [
                'slug' => 'royal-cajova-konvice-1-l', 'category' => 'kava-a-caj', 'collection' => 'royal',
                'name' => 'Čajová konvice 1 l', 'subtitle' => 'Na čtyři šálky čaje.',
                'description' => 'Bílá čajová konvice z kolekce Royal s víčkem, které při nalévání nevypadne. Široké hrdlo se snadno myje.',
                'price' => 1290, 'rrp' => 1990, 'stock_qty' => 12,
                'dimensions' => 'objem 1 l, výška 14 cm',
                'images' => self::photos(24346972, 18421962, 36111898),
            ],
            [
                'slug' => 'anmut-gold-cajova-konvice', 'category' => 'kava-a-caj', 'collection' => 'anmut-gold',
                'name' => 'Čajová konvice 1,1 l se zlatou linkou', 'subtitle' => 'K šálkům Anmut Gold.',
                'description' => 'Konvice z kostního porcelánu se zlatou linkou na víčku i výlevce. Doplní čajové šálky Anmut Gold. Do mikrovlnné trouby nepatří.',
                'price' => 2290, 'rrp' => 3490, 'stock' => 'on_order',
                'dimensions' => 'objem 1,1 l, výška 15 cm', 'material' => 'kostní porcelán (bone china)', 'microwave' => false,
                'images' => self::photos(6974459, 6970495),
            ],
            [
                'slug' => 'old-luxembourg-konvice-na-kavu', 'category' => 'kava-a-caj', 'collection' => 'old-luxembourg',
                'name' => 'Konvice na kávu 1,2 l', 'subtitle' => 'Barokní tvar s reliéfem.',
                'description' => 'Vysoká konvice na kávu s reliéfním okrajem víčka. Na rodinné snídaně i odpolední kávu pro šest.',
                'price' => 1690, 'rrp' => 2990, 'stock' => 'last_pieces', 'stock_qty' => 1, 'badge' => 'posledni-kusy',
                'dimensions' => 'objem 1,2 l, výška 24 cm',
                'images' => self::photos(28772744, 18977904),
            ],
            [
                'slug' => 'afina-cukrenka-s-vickem', 'category' => 'kava-a-caj', 'collection' => 'afina',
                'name' => 'Cukřenka s víčkem', 'subtitle' => 'Reliéf k šálkům Afina.',
                'description' => 'Cukřenka s víčkem a reliéfem kolekce Afina. Víčko dobře dosedne, takže cukr nevlhne.',
                'price' => 499, 'rrp' => 790, 'stock_qty' => 14,
                'dimensions' => 'objem 0,3 l, výška 11 cm',
                'images' => self::photos(11170803, 11170807),
            ],
            [
                'slug' => 'royal-mlecenka-025-l', 'category' => 'kava-a-caj', 'collection' => 'royal',
                'name' => 'Mléčenka 0,25 l', 'subtitle' => 'Na mléko ke kávě i na smetanu.',
                'description' => 'Válcová mléčenka bez ucha, která se drží celou dlaní. Hubička nekape.',
                'price' => 329, 'rrp' => 490, 'stock_qty' => 20,
                'dimensions' => 'objem 0,25 l, výška 9 cm',
                'images' => self::photos(4963895, 6083568),
            ],

            // --- Dekorace ---
            [
                'slug' => 'manufacture-vaza-bila', 'category' => 'dekorace', 'collection' => 'manufacture',
                'name' => 'Váza bílá, matná', 'subtitle' => 'Dvě velikosti, jemné žebrování.',
                'description' => 'Matná bílá váza s jemným žebrováním. Menší velikost se hodí na sušené květiny, větší na větve.',
                'price' => 890, 'rrp' => 1590, 'stock_qty' => 10, 'microwave' => false,
                'dimensions' => 'výška 18 cm nebo 28 cm',
                'options' => self::packs(['18', 'Výška 18 cm', 1, 890, 1590], ['28', 'Výška 28 cm', 1, 1390, 2390]),
                'images' => self::photos(8217492, 14608171),
            ],
            [
                'slug' => 'newmoon-dzbanek-a-mini-vazy', 'category' => 'dekorace', 'collection' => 'newmoon',
                'name' => 'Džbánek a 2 mini vázy', 'subtitle' => 'Malé tvary na okenní parapet.',
                'description' => 'Trojice drobných nádob z kolekce NewMoon – džbánek a dvě mini vázy na jednotlivé květy nebo větvičky.',
                'price' => 790, 'rrp' => 1190, 'stock_qty' => 13, 'microwave' => false,
                'dimensions' => 'výška 8–12 cm',
                'set_contents' => ['1× džbánek 12 cm', '2× mini váza 8 cm'],
                'images' => self::photos(5040767, 38094623),
            ],
            [
                'slug' => 'afina-vaza-21', 'category' => 'dekorace', 'collection' => 'afina',
                'name' => 'Váza 21 cm s reliéfem', 'subtitle' => 'K sušeným květům i čerstvé kytici.',
                'description' => 'Lesklá bílá váza s reliéfem kolekce Afina. Úzké hrdlo drží i řídkou kytici.',
                'price' => 790, 'rrp' => 1090, 'stock_qty' => 8, 'microwave' => false,
                'dimensions' => 'výška 21 cm, Ø hrdla 6 cm',
                'images' => self::photos(11507900, 11170807),
            ],
        ];
    }
}
