<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class CompanySettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'ice' => ['required', 'digits:15'],
            'rc' => ['nullable', 'string', 'max:30'],
            'if_number' => ['nullable', 'string', 'max:30'],
            'tp' => ['nullable', 'string', 'max:30'],
            'cnss' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'rib' => ['nullable', 'string', 'max:40'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // L'ICE est souvent saisi avec des espaces : on les retire.
        if ($this->filled('ice')) {
            $this->merge(['ice' => preg_replace('/\s+/', '', $this->input('ice'))]);
        }
    }
}
