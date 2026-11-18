<?php

namespace App\Providers;

use App\Services\Cart;
use App\Services\Catalog;
use App\Services\Favorites;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Katalog (kategorie do hlavičky) se v rámci požadavku načte jednou. Cart a Favorites se nesdílí –
        // jejich data žijí v session, takže každá instance vidí aktuální stav (i v testech, kde běží víc požadavků v jedné aplikaci).
        $this->app->scoped(Catalog::class);
    }

    public function boot(): void
    {
        // Pokladna: 5 odeslání za 10 minut z jedné IP adresy.
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinutes(10, 5)->by('checkout|'.$request->ip()));

        // České formátování v šablonách: @price(1290) → „1 290 Kč“, @plural(5, 'kus', 'kusy', 'kusů') → „kusů“
        Blade::directive('price', fn (string $e) => "<?php echo e(\\App\\View\\Fmt::price($e)); ?>");
        Blade::directive('plural', fn (string $e) => "<?php echo e(\\App\\View\\Fmt::plural($e)); ?>");

        // Hlavička a patička obchodu: kategorie, počty v košíku a v oblíbených.
        View::composer('layouts.shop', function ($view) {
            $catalog = app(Catalog::class);
            try {
                $cartCount = app(Cart::class)->count();
                $favCount = app(Favorites::class)->count();
            } catch (Throwable) {
                $cartCount = $favCount = 0;
            }
            $view->with([
                'navCategories' => $catalog->categories(),
                'cartCount' => $cartCount,
                'favCount' => $favCount,
            ]);
        });
    }
}
