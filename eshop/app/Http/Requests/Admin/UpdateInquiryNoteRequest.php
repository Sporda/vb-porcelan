<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInquiryNoteRequest extends FormRequest
{
    public const MAX = 5000;

    protected $errorBag = 'note';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Jednotné konce řádků, ať délka sedí s počítadlem v prohlížeči.
        $note = str_replace(["\r\n", "\r"], "\n", (string) $this->input('internal_note', ''));
        $this->merge(['internal_note' => trim($note)]);
    }

    public function rules(): array
    {
        return [
            'internal_note' => ['nullable', 'string', 'max:'.self::MAX],
        ];
    }

    /** Po chybě zpátky rovnou k formuláři poznámky. */
    protected function getRedirectUrl(): string
    {
        return strtok(parent::getRedirectUrl(), '#').'#poznamka';
    }

    public function messages(): array
    {
        return [
            'internal_note.string' => 'Poznámka musí být text.',
            'internal_note.max' => 'Poznámka může mít nejvýš 5 000 znaků.',
        ];
    }
}
