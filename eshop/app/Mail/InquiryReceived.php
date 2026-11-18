<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail s novou poptávkou pro obchod (config('shop.inquiry_to')). Odpověď jde rovnou zákazníkovi (reply-to).
 * Posílá se přes App\Services\InquiryMailer – z pokladny i z administrace („odeslat znovu“).
 * Šablony: resources/views/mail/inquiry.blade.php (HTML) a mail/inquiry-text.blade.php (text).
 */
class InquiryReceived extends Mailable
{
    public function __construct(public Inquiry $inquiry)
    {
        $this->inquiry->loadMissing('items');
    }

    public function envelope(): Envelope
    {
        $i = $this->inquiry;
        $total = number_format($i->total, 0, ',', ' ').' '.config('shop.currency', 'Kč');

        return new Envelope(
            replyTo: [new Address($i->email, $i->customer_name)],
            subject: "Nová poptávka {$i->number} – {$i->customer_name} – {$total}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.inquiry',
            text: 'mail.inquiry-text',
        );
    }
}
