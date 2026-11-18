<?php

namespace App\Services;

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Pošle e-mail s poptávkou na config('shop.inquiry_to') a zapíše výsledek k poptávce
 * (mail_status sent|failed, mail_info, mail_sent_at). Chyba e-mailu nikdy nevyhodí výjimku –
 * poptávka je už uložená v databázi a v administraci jde odeslat znovu.
 */
class InquiryMailer
{
    public function send(Inquiry $inquiry): bool
    {
        $to = (string) config('shop.inquiry_to');

        try {
            $sent = Mail::to($to)->send(new InquiryReceived($inquiry));
            $inquiry->forceFill([
                'mail_status' => 'sent',
                'mail_info' => mb_substr('→ '.$to.($sent?->getMessageId() ? ' · '.$sent->getMessageId() : ''), 0, 500),
                'mail_sent_at' => now(),
            ])->save();

            return true;
        } catch (Throwable $e) {
            Log::error('inquiry mail failed', ['inquiry' => $inquiry->number, 'error' => $e->getMessage()]);
            $inquiry->forceFill([
                'mail_status' => 'failed',
                'mail_info' => mb_substr($e->getMessage(), 0, 500),
            ])->save();

            return false;
        }
    }
}
