<?php

namespace App\Http\Requests\Shop;

use App\Services\Cart;
use Illuminate\Foundation\Http\FormRequest;

class CartAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'option' => ['nullable', 'string', 'max:40'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:'.Cart::MAX_QTY],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Tento produkt už v nabídce není.',
            'qty.max' => 'Najednou lze přidat nejvýš :max kusů.',
        ];
    }
}
