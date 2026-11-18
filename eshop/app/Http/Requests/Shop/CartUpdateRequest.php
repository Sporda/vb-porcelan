<?php

namespace App\Http\Requests\Shop;

use App\Services\Cart;
use Illuminate\Foundation\Http\FormRequest;

class CartUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:80'],
            'qty' => ['required', 'integer', 'min:0', 'max:'.Cart::MAX_QTY],
        ];
    }

    public function messages(): array
    {
        return [
            'qty.max' => 'Jedné položky lze objednat nejvýš :max kusů. Pro větší množství nám napište.',
        ];
    }
}
