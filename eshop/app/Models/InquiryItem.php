<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryItem extends Model
{
    protected $fillable = [
        'inquiry_id', 'product_id', 'product_name', 'collection_name', 'option_label',
        'unit_price', 'rrp', 'quantity', 'line_total', 'image_url',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'rrp' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
