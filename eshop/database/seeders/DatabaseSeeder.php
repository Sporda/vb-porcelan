<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Spouští se při každém nasazení (php artisan db:seed --force, bez dev balíčků – žádné factories ani Faker).
 * Každý seeder je idempotentní: jen doplní, co chybí, nic nepřepíše ani nesmaže.
 * Bez WithoutModelEvents – Product::saving() plní search_text pro vyhledávání.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        if (class_exists(AdminUserSeeder::class)) {
            $this->call(AdminUserSeeder::class);
        }
    }
}
