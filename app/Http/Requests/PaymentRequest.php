<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Paiement d'une facture : date (JJ/MM/AAAA), montant (« 1 250,50 »), mode et référence.
 * Le montant ne peut pas dépasser le reste à payer.
 */
class PaymentRequest extends FormRequest
{
    public function rules(): array
    {
        $balance = $this->route('invoice')->balance();

        return [
            'paid_on' => ['required', 'date_format:d/m/Y', 'before_or_equal:today'],
            'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) use ($balance) {
                $cents = Money::parse((string) $value);

                if ($cents === null || $cents <= 0) {
                    $fail('Indiquez un montant positif, par exemple 1 250,50.');
                } elseif ($cents > $balance) {
                    $fail('Le montant dépasse le reste à payer ('.Money::format($balance, true).').');
                }
            }],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'paid_on.date_format' => 'Date au format JJ/MM/AAAA.',
            'paid_on.before_or_equal' => 'La date du paiement ne peut pas être dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return ['paid_on' => 'date du paiement', 'amount' => 'montant', 'method' => 'mode de paiement'];
    }

    public function paidOn(): Carbon
    {
        return Carbon::createFromFormat('!d/m/Y', $this->validated('paid_on'));
    }

    public function amountInCents(): int
    {
        return Money::parse($this->validated('amount'));
    }
}
