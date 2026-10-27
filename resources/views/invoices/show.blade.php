@php
    use App\Enums\InvoiceStatus;
    use App\Support\Money;

    $status = $invoice->displayStatus();
    $open = $invoice->isOpen();
    $balance = $invoice->balance();
@endphp

<x-app-layout :title="'Facture '.$invoice->number">
    <x-slot name="header">
        <div>
            <a href="{{ route('invoices.index') }}" class="btn-link">← Toutes les factures</a>
            <h1 class="mt-2 flex flex-wrap items-center gap-3 text-3xl font-bold">
                Facture {{ $invoice->number }}
                <x-badge :status="$status" size="lg" />
            </h1>
            <p class="mt-1 text-lg text-muted">
                @if ($invoice->client)
                    <a href="{{ route('clients.show', $invoice->client) }}" class="btn-link">{{ $invoice->clientDetails()['name'] }}</a> ·
                @endif
                émise le {{ $invoice->issued_at->format('d/m/Y') }} · échéance {{ $invoice->due_date->format('d/m/Y') }}
                @if ($invoice->quote) · devis <a href="{{ route('quotes.show', $invoice->quote) }}" class="btn-link">{{ $invoice->quote->number }}</a>@endif
                @if ($invoice->cancelled_at) · annulée le {{ $invoice->cancelled_at->format('d/m/Y') }}@endif
            </p>
        </div>
    </x-slot>

    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn-primary">Voir le PDF</a>
        <a href="{{ route('invoices.word', $invoice) }}" class="btn-secondary">Télécharger Word</a>
        <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}">
            @csrf
            <button type="submit" class="btn-secondary">Dupliquer</button>
        </form>
        @if ($open && $invoice->amount_paid === 0)
            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}"
                  onsubmit="return confirm(@js('Annuler la facture '.$invoice->number.' ?\n\nElle garde son numéro et reste dans la liste ; son PDF portera la mention « ANNULÉE ». Cette action est définitive.'))">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary text-accent">Annuler la facture</button>
            </form>
        @endif
    </div>

    {{-- Paiements --}}
    @unless ($invoice->status === InvoiceStatus::Cancelled)
        <section class="card mb-8" x-data="{ form: @js($errors->any()) }">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-muted">Total TTC</p>
                    <p class="text-2xl font-bold tabular-nums">{{ Money::format($invoice->total_ttc, true) }}</p>
                </div>
                <div>
                    <p class="text-muted">Déjà payé</p>
                    <p class="text-2xl font-bold tabular-nums">{{ Money::format($invoice->amount_paid, true) }}</p>
                </div>
                <div>
                    <p class="text-muted">Reste à payer</p>
                    <p @class(['text-2xl font-bold tabular-nums', 'text-accent' => $balance > 0, 'text-green-700' => $balance === 0])>
                        {{ $balance === 0 ? 'Payée ✓' : Money::format($balance, true) }}
                    </p>
                </div>
            </div>

            @if ($invoice->payments->isNotEmpty())
                <h2 class="mb-2 mt-6 text-lg font-bold">Paiements reçus</h2>
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($invoice->payments as $payment)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <span>
                                <span class="font-semibold">{{ $payment->paid_on->format('d/m/Y') }}</span>
                                · {{ $payment->method->label() }}
                                @if ($payment->reference) · <span class="text-muted">{{ $payment->reference }}</span>@endif
                            </span>
                            <span class="flex items-center gap-4">
                                <span class="font-semibold tabular-nums">{{ Money::format($payment->amount, true) }}</span>
                                <form method="POST" action="{{ route('invoices.payments.destroy', [$invoice, $payment]) }}"
                                      onsubmit="return confirm('Supprimer ce paiement (saisi par erreur) ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-link text-sm text-accent">Supprimer</button>
                                </form>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($open && $balance > 0)
                <div class="mt-6 flex flex-wrap gap-3" x-show="! form">
                    <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}"
                          onsubmit="return confirm(@js('Enregistrer un paiement de '.Money::format($balance, true).' aujourd’hui (virement) ? La facture sera payée.'))">
                        @csrf
                        <input type="hidden" name="paid_on" value="{{ today()->format('d/m/Y') }}">
                        <input type="hidden" name="amount" value="{{ Money::toInput($balance) }}">
                        <input type="hidden" name="method" value="virement">
                        <button type="submit" class="btn-primary">✓ Marquer comme payée</button>
                    </form>
                    <button type="button" class="btn-secondary" @click="form = true">Paiement partiel ou autre mode…</button>
                </div>

                <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" x-show="form" x-cloak class="mt-6 space-y-5">
                    @csrf
                    <h2 class="text-lg font-bold">Enregistrer un paiement</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="amount" label="Montant (MAD)" :value="Money::format($balance)" required inputmode="decimal"
                                 help="Reste à payer : {{ Money::format($balance, true) }}" />
                        <x-field name="paid_on" label="Date du paiement" :value="today()->format('d/m/Y')" required inputmode="numeric" help="JJ/MM/AAAA" />
                        <div>
                            <label for="field-method" class="field-label">Mode de paiement</label>
                            <select id="field-method" name="method" class="field-input">
                                @foreach ($methods as $method)
                                    <option value="{{ $method->value }}" @selected(old('method', 'virement') === $method->value)>{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-field name="reference" label="Référence (facultatif)" help="N° de chèque, de virement…" />
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="btn-primary">Enregistrer le paiement</button>
                        <button type="button" class="btn-secondary" @click="form = false">Annuler</button>
                    </div>
                </form>
            @endif
        </section>
    @endunless

    <p class="mx-auto mb-4 max-w-[210mm] text-muted">
        @if ($invoice->status === InvoiceStatus::Cancelled)
            Cette facture est annulée : elle garde son numéro mais n’est plus due.
        @else
            Cette facture est émise : elle n’est plus modifiable. Pour une nouvelle version, utilisez « Dupliquer ».
        @endif
    </p>

    <x-document.sheet :sheet="$sheet" />
</x-app-layout>
