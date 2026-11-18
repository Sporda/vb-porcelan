<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();                 // VB-2026-0001
            $table->string('status')->default('new');           // new | processing | confirmed | cancelled

            $table->string('customer_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('ic')->nullable();                   // IČO

            $table->string('delivery_method');                  // klíč z config('shop.delivery')
            $table->string('delivery_label');
            $table->unsignedInteger('delivery_price')->default(0);
            $table->string('pickup_point')->nullable();         // Zásilkovna: název / adresa výdejního místa
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip', 12)->nullable();
            $table->string('country', 2)->default('CZ');
            $table->text('note')->nullable();                   // poznámka zákazníka

            $table->unsignedInteger('items_total');             // součet položek (Kč)
            $table->unsignedInteger('rrp_total')->nullable();   // součet doporučených cen
            $table->unsignedInteger('total');                   // položky + doprava

            $table->text('internal_note')->nullable();          // poznámka obchodu (administrace)
            $table->string('mail_status')->default('pending');  // pending | sent | failed
            $table->text('mail_info')->nullable();              // id e-mailu nebo popis chyby
            $table->timestamp('mail_sent_at')->nullable();

            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('collection_name')->nullable();
            $table->string('option_label')->nullable();          // např. „Sada 6 ks“
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('rrp')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_items');
        Schema::dropIfExists('inquiries');
    }
};
