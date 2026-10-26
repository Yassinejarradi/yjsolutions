<?php

namespace App\Http\Requests\Settings;

use App\Support\DocumentCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'invoice_prefix' => ['required', 'alpha', 'max:10', 'different:quote_prefix'],
            'quote_prefix' => ['required', 'alpha', 'max:10'],
            'default_vat_rate' => ['required', 'integer', Rule::in(DocumentCalculator::VAT_RATES)],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'quote_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'quote_payment_terms' => ['nullable', 'string', 'max:255'],
            'quote_completion_delay' => ['nullable', 'string', 'max:255'],
            'invoice_notes' => ['nullable', 'string', 'max:2000'],
            'quote_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invoice_prefix' => mb_strtoupper(trim((string) $this->input('invoice_prefix'))),
            'quote_prefix' => mb_strtoupper(trim((string) $this->input('quote_prefix'))),
            'quote_payment_terms' => (string) $this->input('quote_payment_terms'),
            'quote_completion_delay' => (string) $this->input('quote_completion_delay'),
        ]);
    }
}
