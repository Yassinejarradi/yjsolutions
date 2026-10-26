{{--
    Document en lecture seule à l'écran (devis envoyé, et facture émise en phase 4).
    Mêmes données que le PDF et l'export Word : App\Support\DocumentSheet.
--}}
@props(['sheet'])

<div class="doc-sheet">
    @if ($sheet->watermark)
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden" aria-hidden="true">
            <span class="-rotate-[35deg] select-none text-[90px] font-bold tracking-widest text-accent/10 sm:text-[130px]">{{ $sheet->watermark }}</span>
        </div>
    @endif

    <div class="flex items-start justify-between gap-4">
        <img src="{{ route('logo') }}" alt="{{ $sheet->company['name'] ?? '' }}" class="w-20 shrink-0 sm:w-[105px]">
        <div class="text-right">
            <h2 class="doc-title">{{ $sheet->title }}</h2>
            @if ($sheet->number)
                <p class="mt-1 font-bold">{{ $sheet->numberLabel }} <span class="text-accent">{{ $sheet->number }}</span></p>
            @endif
            <p class="doc-muted mt-3 text-[13px]">{{ $sheet->amountLabel }}</p>
            <p class="text-[22px] font-bold tabular-nums">{{ $sheet->amount }}</p>
        </div>
    </div>
    <hr class="doc-rule">

    <div class="mb-5 flex flex-col gap-5 sm:flex-row sm:justify-between">
        <div class="min-w-0">
            <p class="doc-muted mb-1">{{ $sheet->clientLabel }}</p>
            <p class="text-[17px] font-bold">{{ $sheet->client['name'] }}</p>
            @if ($sheet->client['address'])<p>{{ $sheet->client['address'] }}</p>@endif
            @if ($sheet->client['ice'])<p class="mt-1.5">ICE : {{ $sheet->client['ice'] }}</p>@endif
        </div>
        <dl class="grid grid-cols-[auto_auto] content-start gap-x-3 gap-y-1 sm:text-right">
            @foreach ($sheet->dates as [$label, $value])
                <dt>{{ $label }}</dt>
                <dd class="text-muted">{{ $value ?: '—' }}</dd>
            @endforeach
        </dl>
    </div>

    <div class="doc-lines-head text-[14px]" aria-hidden="true">
        <div class="px-1.5 py-2 text-center">#</div>
        <div class="px-1.5 py-2">Article &amp; Description</div>
        <div class="px-1.5 py-2 text-right">Quantité</div>
        <div class="px-1.5 py-2 text-right">Prix Unitaire</div>
        <div class="col-span-2 px-1.5 py-2 text-right">Montant</div>
    </div>
    @foreach ($sheet->lines as $line)
        <div class="doc-line">
            <div class="col-span-2 text-muted md:col-span-1 md:text-center"><span class="md:hidden">Ligne </span>{{ $line['number'] }}</div>
            <div class="col-span-2 whitespace-pre-line md:col-span-1">{{ $line['description'] }}</div>
            <div class="md:text-right"><span class="doc-cell-label">Quantité</span>{{ $line['quantity'] }}</div>
            <div class="md:text-right"><span class="doc-cell-label">Prix unitaire HT</span>{{ $line['unit_price'] }}</div>
            <div class="col-span-2 flex items-baseline justify-between font-semibold tabular-nums md:block md:text-right">
                <span class="doc-cell-label">Montant</span>{{ $line['amount'] }}
            </div>
        </div>
    @endforeach

    <div class="ml-auto mt-4 w-full tabular-nums sm:w-[21rem]">
        @foreach ($sheet->totalRows as $row)
            <div @class([
                'flex justify-between gap-2 px-2 py-1',
                'font-bold' => ($row[2] ?? '') === 'bold',
                'mt-1 bg-brand py-1.5 font-bold text-white' => ($row[2] ?? '') === 'due',
            ])>
                <span>{{ $row[0] }}</span><span>{{ $row[1] }}</span>
            </div>
        @endforeach
    </div>

    <p class="mt-5"><b>{{ $sheet->wordsLabel }}</b> <i class="text-muted">{{ $sheet->words }}</i></p>

    <div class="mt-3 grid gap-1">
        @foreach ($sheet->terms as [$label, $text])
            <p><b>{{ $label }}</b> {{ $text }}</p>
        @endforeach
        @if ($sheet->notes)
            <p class="mt-2 whitespace-pre-line text-sm text-muted">{{ $sheet->notes }}</p>
        @endif
    </div>

    <div class="mt-8 flex justify-between text-center font-bold text-brand">
        <div class="w-[45%]">
            @if ($sheet->clientSignature){{ $sheet->clientSignature }}<small class="block font-normal text-muted">(Date, cachet et signature)</small>@endif
        </div>
        <div class="w-[45%]">{{ $sheet->company['name'] ?? '' }}<small class="block font-normal text-muted">(Cachet et signature)</small></div>
    </div>

    <x-document.footer :company="$sheet->company" class="mt-16" />
</div>
