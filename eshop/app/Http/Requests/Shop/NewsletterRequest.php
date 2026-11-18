<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterRequest extends FormRequest
{
    protected $errorBag = 'newsletter';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return parent::getRedirectUrl().'#novinky';
    }
}
