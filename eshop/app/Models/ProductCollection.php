<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kolekce Villeroy & Boch (Royal, NewMoon, Anmut Gold…). Tabulka product_collections. */
class ProductCollection extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'image_url', 'sort'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
