<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Support\Format;
use App\Http\Controllers\Admin\Support\InquirySearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateInquiryNoteRequest;
use App\Http\Requests\Admin\UpdateInquiryStatusRequest;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Services\InquiryMailer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Throwable;

/** Přehled a detail poptávek: filtr podle stavu, hledání, změna stavu, interní poznámka, nové odeslání e-mailu. */
class InquiryController extends Controller
{
    public const PER_PAGE = 25;

    public const RESEND_PER_MINUTE = 3;

    /** Záložky přehledu: klíč stavu => popisek (null = vše). */
    public const TABS = [
        '' => 'Vše',
        'new' => 'Nové',
        'processing' => 'Zpracovává se',
        'confirmed' => 'Potvrzené',
        'cancelled' => 'Zrušené',
    ];

    private const BACK_KEY = 'admin.inquiries.back';

    public function index(Request $request): View
    {
        $status = (string) $request->query('stav', '');
        $status = array_key_exists($status, Inquiry::STATUSES) ? $status : '';
        $term = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $onlyFailed = $request->query('email') === 'failed';

        $filtered = fn (): Builder => InquirySearch::apply(Inquiry::query(), $term)
            ->when($onlyFailed, fn (Builder $q) => $q->where('mail_status', 'failed'));

        $byStatus = $filtered()->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['' => (int) $byStatus->sum()];
        foreach (array_keys(Inquiry::STATUSES) as $key) {
            $counts[$key] = (int) ($byStatus[$key] ?? 0);
        }

        $inquiries = $filtered()
            ->when($status !== '', fn (Builder $q) => $q->where('status', $status))
            ->withCount('items')
            ->withSum('items as items_quantity', 'quantity')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Odkaz „Zpět na přehled“ v detailu vrátí na stejný filtr a stránku.
        $request->session()->put(self::BACK_KEY, $request->fullUrl());

        return view('admin.inquiries.index', [
            'inquiries' => $inquiries,
            'tabs' => self::TABS,
            'counts' => $counts,
            'status' => $status,
            'term' => $term,
            'onlyFailed' => $onlyFailed,
            'failedCount' => Inquiry::query()->where('mail_status', 'failed')->count(),
            'grandTotal' => Inquiry::query()->count(),
            'newCount' => Inquiry::query()->where('status', 'new')->count(),
        ]);
    }

    public function show(Request $request, Inquiry $inquiry): View
    {
        $inquiry->load('items.product');

        $older = Inquiry::query()
            ->where(fn (Builder $q) => $q->where('created_at', '<', $inquiry->created_at)
                ->orWhere(fn (Builder $q) => $q->where('created_at', $inquiry->created_at)->where('id', '<', $inquiry->id)))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->first(['id', 'number']);

        $newer = Inquiry::query()
            ->where(fn (Builder $q) => $q->where('created_at', '>', $inquiry->created_at)
                ->orWhere(fn (Builder $q) => $q->where('created_at', $inquiry->created_at)->where('id', '>', $inquiry->id)))
            ->orderBy('created_at')->orderBy('id')
            ->first(['id', 'number']);

        $back = (string) $request->session()->get(self::BACK_KEY, '');
        $listUrl = route('admin.inquiries.index');
        if (! str_starts_with($back, $listUrl)) {
            $back = $listUrl;
        }

        return view('admin.inquiries.show', [
            'inquiry' => $inquiry,
            'older' => $older,
            'newer' => $newer,
            'backUrl' => $back,
            'delivery' => config('shop.delivery.'.$inquiry->delivery_method),
            'statuses' => Inquiry::STATUSES,
            'productLinks' => $inquiry->items->mapWithKeys(fn (InquiryItem $item) => [$item->id => $this->productUrl($item)]),
            'inquiryTo' => (string) config('shop.inquiry_to'),
        ]);
    }

    /** Odkaz na produkt v obchodě – jen když obchod má route shop.product a produkt pořád existuje. */
    private function productUrl(InquiryItem $item): ?string
    {
        if (! $item->product || ! Route::has('shop.product')) {
            return null;
        }

        try {
            return route('shop.product', $item->product->slug);
        } catch (Throwable) {
            return null;
        }
    }

    public function updateStatus(UpdateInquiryStatusRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $inquiry->update(['status' => $request->validated('status')]);

        return redirect()
            ->route('admin.inquiries.show', $inquiry)
            ->with('success', 'Stav poptávky je teď „'.$inquiry->statusLabel().'“.');
    }

    public function updateNote(UpdateInquiryNoteRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $note = (string) $request->validated('internal_note');
        $inquiry->update(['internal_note' => $note === '' ? null : $note]);

        return redirect()
            ->route('admin.inquiries.show', $inquiry)
            ->withFragment('poznamka')
            ->with('flash_at', 'note')
            ->with('success', $note === '' ? 'Interní poznámka je smazaná.' : 'Interní poznámka je uložená.');
    }

    public function resendMail(Inquiry $inquiry, InquiryMailer $mailer): RedirectResponse
    {
        // Pojistka proti opakovanému klikání: nejvýš 3 odeslání téže poptávky za minutu.
        $key = 'admin-resend:'.$inquiry->id;
        if (RateLimiter::tooManyAttempts($key, self::RESEND_PER_MINUTE)) {
            return redirect()
                ->route('admin.inquiries.show', $inquiry)
                ->withFragment('email')
                ->with('flash_at', 'email')
                ->with('error', 'E-mail k této poptávce jste právě posílali. Další pokus půjde za '
                    .Format::plural(max(1, RateLimiter::availableIn($key)), 'sekundu', 'sekundy', 'sekund').'.');
        }
        RateLimiter::hit($key, 60);

        $sent = $mailer->send($inquiry);
        $inquiry->refresh();
        $to = (string) config('shop.inquiry_to');

        return redirect()
            ->route('admin.inquiries.show', $inquiry)
            ->withFragment('email')
            ->with('flash_at', 'email')
            ->with($sent ? 'success' : 'error', $sent
                ? 'E-mail s poptávkou odešel znovu na '.$to.'.'
                : 'E-mail se nepodařilo odeslat: '.($inquiry->mail_info ?: 'neznámá chyba').' Zkuste to za chvíli znovu.');
    }
}
