@php
    use App\Support\Money;
    use App\Support\PaymentReminder;

    $sections = [
        ['title' => 'En retard', 'invoices' => $overdue, 'empty' => 'Aucune facture en retard.', 'tone' => 'text-accent'],
        ['title' => 'À échéance dans les '.PaymentReminder::DAYS_AHEAD.' prochains jours', 'invoices' => $upcoming, 'empty' => 'Aucune échéance cette semaine.', 'tone' => 'text-amber-700'],
    ];
@endphp

<x-app-layout title="À relancer">
    <p class="mb-6 max-w-3xl text-muted">
        Les factures à payer dont l’échéance est passée ou proche. « WhatsApp » ouvre un message de relance déjà rédigé :
        relisez-le, puis envoyez-le vous-même. Quand le client a payé, ouvrez la facture et cliquez sur « Marquer comme payée ».
    </p>

    @foreach ($sections as $section)
        <h2 class="mb-3 flex items-center gap-2 text-lg font-bold">
            {{ $section['title'] }}
            <span class="rounded-full bg-white px-2.5 py-0.5 text-sm ring-1 ring-line {{ $section['tone'] }}">{{ $section['invoices']->count() }}</span>
        </h2>

        <div class="card mb-8 overflow-hidden p-0 sm:p-0">
            @if ($section['invoices']->isEmpty())
                <p class="px-5 py-6 text-center text-muted">{{ $section['empty'] }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($section['invoices'] as $invoice)
                        @php
                            $days = (int) abs(today()->diffInDays($invoice->due_date));
                            $late = $invoice->due_date->lt(today());
                            $tel = PaymentReminder::telLink($invoice->client?->phone);
                        @endphp
                        <li class="flex flex-col gap-3 px-5 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-accent hover:underline">{{ $invoice->number }}</a>
                                    · {{ $invoice->clientDetails()['name'] }}
                                </p>
                                <p class="text-muted">
                                    Reste à payer <span class="font-semibold text-ink tabular-nums">{{ Money::format($invoice->balance(), true) }}</span>
                                    · échéance {{ $invoice->due_date->format('d/m/Y') }}
                                    <span @class(['font-semibold', 'text-accent' => $late, 'text-amber-700' => ! $late])>
                                        @if ($late)
                                            ({{ $days }} jour{{ $days > 1 ? 's' : '' }} de retard)
                                        @elseif ($days === 0)
                                            (aujourd’hui)
                                        @else
                                            (dans {{ $days }} jour{{ $days > 1 ? 's' : '' }})
                                        @endif
                                    </span>
                                </p>
                                <p class="text-sm text-muted">
                                    {{ collect([$invoice->client?->contact_name, $invoice->client?->phone])->filter()->implode(' · ') ?: 'Pas de téléphone sur la fiche du client' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($tel)
                                    <a href="{{ $tel }}" class="btn-secondary">Appeler</a>
                                @endif
                                <a href="{{ PaymentReminder::whatsappLink($invoice) }}" target="_blank" rel="noopener" class="btn bg-[#25D366] text-white shadow-sm hover:bg-[#1DA851]">WhatsApp</a>
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary">Voir la facture</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</x-app-layout>
