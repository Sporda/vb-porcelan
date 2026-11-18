<?php

namespace App\Http\Requests\Admin;

use App\Models\Inquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInquiryStatusRequest extends FormRequest
{
    /** Chyby formuláře stavu mají vlastní „tašku“, aby se nemíchaly s poznámkou. */
    protected $errorBag = 'status';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(Inquiry::STATUSES))],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vyberte stav poptávky.',
            'status.string' => 'Vyberte jeden ze stavů v nabídce.',
            'status.in' => 'Vyberte jeden ze stavů v nabídce.',
        ];
    }
}
