<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/** Poptávka = objednávka bez platby. Vzniká v pokladně, spravuje se v administraci. */
class Inquiry extends Model
{
    public const STATUSES = [
        'new' => 'Nová',
        'processing' => 'Zpracovává se',
        'confirmed' => 'Potvrzená',
        'cancelled' => 'Zrušená',
    ];

    public const MAIL_STATUSES = [
        'pending' => 'Čeká na odeslání',
        'sent' => 'Odesláno',
        'failed' => 'Nepodařilo se odeslat',
    ];

    protected $fillable = [
        'number', 'status', 'customer_name', 'email', 'phone', 'company', 'ic',
        'delivery_method', 'delivery_label', 'delivery_price', 'pickup_point', 'street', 'city', 'zip', 'country',
        'note', 'items_total', 'rrp_total', 'total', 'internal_note', 'mail_status', 'mail_info', 'mail_sent_at',
        'ip_hash', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'delivery_price' => 'integer',
            'items_total' => 'integer',
            'rrp_total' => 'integer',
            'total' => 'integer',
            'mail_sent_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function mailStatusLabel(): string
    {
        return self::MAIL_STATUSES[$this->mail_status] ?? $this->mail_status;
    }

    /** Další číslo poptávky ve tvaru VB-2026-0001 (volat uvnitř transakce). */
    public static function nextNumber(): string
    {
        $prefix = config('shop.inquiry_prefix', 'VB').'-'.now()->format('Y').'-';
        $last = DB::table('inquiries')->where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
