<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Pokladna: kontakt, doprava, souhlas. Pole „website“ je past na roboty (honeypot) – řeší controller. */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn ($v) => is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v;

        $zip = is_string($this->zip) ? preg_replace('/\s+/', '', $this->zip) : $this->zip;
        if (is_string($zip) && preg_match('/^\d{5}$/', $zip)) {
            $zip = substr($zip, 0, 3).' '.substr($zip, 3);
        }

        $this->merge([
            'customer_name' => $clean($this->customer_name),
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'phone' => $clean($this->phone),
            'company' => $clean($this->company),
            'ic' => is_string($this->ic) ? preg_replace('/\s+/', '', $this->ic) : $this->ic,
            'pickup_point' => $clean($this->pickup_point),
            'street' => $clean($this->street),
            'city' => $clean($this->city),
            'zip' => $zip,
            'note' => is_string($this->note) ? trim($this->note) : $this->note,
        ]);
    }

    public function deliveryType(): ?string
    {
        $code = $this->input('delivery');

        return is_string($code) ? config('shop.delivery.'.$code.'.type') : null;
    }

    public function rules(): array
    {
        $pickup = fn () => $this->deliveryType() === 'pickup_point';
        $address = fn () => $this->deliveryType() === 'address';

        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:25', function (string $attr, mixed $value, \Closure $fail) {
                if (! preg_match('/^(\+\d{1,3})?\d{9}$/', str_replace(' ', '', (string) $value))) {
                    $fail(__('validation.custom.phone.regex'));
                }
            }],
            'company' => ['nullable', 'string', 'max:120'],
            'ic' => ['nullable', 'regex:/^\d{8}$/'],
            'delivery' => ['required', 'string', Rule::in(array_keys(config('shop.delivery', [])))],
            'pickup_point' => [Rule::requiredIf($pickup), 'nullable', 'string', 'max:190'],
            'street' => [Rule::requiredIf($address), 'nullable', 'string', 'max:150'],
            'city' => [Rule::requiredIf($address), 'nullable', 'string', 'max:100'],
            'zip' => [Rule::requiredIf($address), 'nullable', 'regex:/^\d{3} \d{2}$/'],
            'note' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Napište prosím jméno a příjmení.',
            'customer_name.min' => 'Napište prosím celé jméno a příjmení.',
            'email.required' => 'Napište e-mail – pošleme na něj potvrzení.',
            'phone.required' => 'Napište telefon pro dopravce.',
            'pickup_point.required' => 'Napište výdejní místo Zásilkovny (název nebo adresu).',
            'street.required' => 'Napište ulici a číslo domu.',
            'city.required' => 'Napište obec.',
            'zip.required' => 'Napište PSČ.',
        ];
    }
}
