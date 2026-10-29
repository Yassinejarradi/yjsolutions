@php
    use App\Enums\QuoteStatus;

    $status = $quote->displayStatus();
    $canDecide = $quote->status === QuoteStatus::Sent;
@endphp

<x-app-layout :title="'Devis '.$quote->number">
    <x-slot name="header">
        <div>
            <a href="{{ route('quotes.index') }}" class="btn-link">← Tous les devis</a>
            <h1 class="mt-2 flex flex-wrap items-center gap-3 text-2xl font-bold">
                Devis {{ $quote->number }}
                <x-badge :status="$status" size="lg" />
            </h1>
            <p class="mt-1 text-lg text-muted">
                @if ($quote->client)
                    <a href="{{ route('clients.show', $quote->client) }}" class="btn-link">{{ $quote->clientDetails()['name'] }}</a> ·
                @endif
                envoyé le {{ $quote->sent_at->format('d/m/Y') }}
                @if ($quote->decided_at) · {{ mb_strtolower($quote->status->label()) }} le {{ $quote->decided_at->format('d/m/Y') }}@endif
            </p>
        </div>
    </x-slot>

    <div class="mb-6 flex flex-wrap gap-2">
        @if ($quote->invoice)
            <a href="{{ route('invoices.show', $quote->invoice) }}" class="btn-primary">
                Voir la facture {{ $quote->invoice->number ?? '(brouillon)' }}
            </a>
        @elseif ($quote->canBeInvoiced())
            <form method="POST" action="{{ route('quotes.invoice', $quote) }}"
                  data-confirm-title="Facturer ce devis maintenant ?"
                  data-confirm="Une facture de {{ \App\Support\Money::format($quote->total_ttc, true) }} TTC sera émise aujourd’hui, avec son numéro définitif. Elle ne pourra plus être modifiée (seulement annulée)."
                  data-confirm-label="Oui, émettre la facture">
                @csrf
                <button type="submit" class="btn-primary">Facturer ce devis</button>
            </form>
            <form method="POST" action="{{ route('quotes.convert', $quote) }}">
                @csrf
                <button type="submit" class="btn-secondary" title="Crée une facture brouillon, modifiable avant de l’émettre">Préparer la facture (brouillon)</button>
            </form>
        @endif
        <a href="{{ route('quotes.pdf', $quote) }}" target="_blank" class="btn-secondary">Voir le PDF</a>
        <a href="{{ route('quotes.word', $quote) }}" class="btn-secondary">Télécharger Word</a>
        <form method="POST" action="{{ route('quotes.duplicate', $quote) }}">
            @csrf
            <button type="submit" class="btn-secondary">Dupliquer</button>
        </form>

        @if ($canDecide)
            <form method="POST" action="{{ route('quotes.accept', $quote) }}"
                  data-confirm-title="Le client a accepté ce devis ?" data-confirm="Le devis passera à « Accepté ». Vous pourrez ensuite le facturer." data-confirm-label="Oui, il a accepté">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary border-green-600 text-green-800">✓ Le client a accepté</button>
            </form>
            <form method="POST" action="{{ route('quotes.refuse', $quote) }}"
                  data-confirm-title="Le client a refusé ce devis ?" data-confirm="Le devis passera à « Refusé ». Vous pourrez toujours le dupliquer." data-confirm-label="Oui, il a refusé">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary text-accent">✕ Le client a refusé</button>
            </form>
        @endif
    </div>

    <p class="mx-auto mb-4 max-w-[210mm] text-muted">
        Ce devis est envoyé : il n’est plus modifiable. Pour une nouvelle version, utilisez « Dupliquer ».
    </p>

    <x-document.sheet :sheet="$sheet" />
</x-app-layout>
