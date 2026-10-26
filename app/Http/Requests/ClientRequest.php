<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création et modification d'un client. Seule la raison sociale est obligatoire.
 */
class ClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ice' => ['nullable', 'digits:15', Rule::unique(Client::class)->ignore($this->route('client'))],
            'if_number' => ['nullable', 'string', 'max:30'],
            'rc' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'raison sociale'];
    }

    public function messages(): array
    {
        return [
            'ice.unique' => 'Un autre client a déjà cet ICE.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // L'ICE est souvent saisi avec des espaces : on les retire.
        if ($this->filled('ice')) {
            $this->merge(['ice' => preg_replace('/\s+/', '', $this->input('ice'))]);
        }
    }

    /**
     * Données à enregistrer. Les champs texte vidés deviennent une chaîne vide (colonnes non nullables) ;
     * l'ICE, le délai de paiement et les notes restent null.
     */
    public function clientData(): array
    {
        $nullable = ['ice', 'payment_terms_days', 'notes'];

        return collect($this->validated())
            ->map(fn ($value, $key) => in_array($key, $nullable, true) ? $value : ($value ?? ''))
            ->all();
    }
}
