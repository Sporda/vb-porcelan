<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('product_collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('subtitle')->nullable();           // krátká věta pod názvem
            $table->text('description')->nullable();
            $table->unsignedInteger('price');                  // Kč s DPH
            $table->unsignedInteger('rrp')->nullable();        // doporučená cena výrobce
            $table->string('stock')->default('in_stock');      // in_stock | last_pieces | on_order
            $table->unsignedInteger('stock_qty')->nullable();
            $table->string('badge')->nullable();               // bestseller | novinka | posledni-kusy
            $table->string('dimensions')->nullable();
            $table->string('material')->default('porcelán');
            $table->boolean('dishwasher')->default(true);
            $table->boolean('microwave')->default(true);
            $table->json('set_contents')->nullable();          // ["4× mělký talíř 27 cm", ...]
            $table->json('options')->nullable();               // balení: [{"code":"1","label":"1 ks","units":1,"price":389}, ...]
            $table->json('images');                            // [{"url":"…","alt":"…"}, ...]
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->text('search_text')->nullable();           // název + kolekce + kategorie bez diakritiky, malými písmeny
            $table->timestamps();

            $table->index(['category_id', 'sort']);
            $table->index('product_collection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_collections');
        Schema::dropIfExists('categories');
    }
};
