{{-- Relevé de compte d'un client (dompdf : mise en page en tableaux, police Carlito comme les devis et factures). --}}
@php
    use App\Support\DocumentSheet;
    use App\Support\Money;

    $font = fn (string $file) => str_replace('\\', '/', resource_path('fonts/carlito/'.$file));
    $client = $statement->client;
    $c = $statement->company;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $statement->fileName() }}</title>
<style>
    @font-face { font-family: 'Carlito'; font-weight: normal; src: url('{{ $font('Carlito-Regular.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Carlito'; font-weight: bold; src: url('{{ $font('Carlito-Bold.ttf') }}') format('truetype'); }

    @page { size: A4; margin: 1.8cm 1.8cm 3cm 1.8cm; }
    body { margin: 0; font-family: 'Carlito', Calibri, Arial, sans-serif; font-size: 10.5pt; color: #1F2937; line-height: 1.25; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 0; vertical-align: top; }
    .muted { color: #6B7280; }
    .right { text-align: right; }
    .footer { position: fixed; left: 0; right: 0; bottom: -2.2cm; height: 1.5cm;
              border-top: 1.5pt solid #E30613; padding-top: 5pt; text-align: center; font-size: 8pt; color: #6B7280; line-height: 1.45; }
    .footer b { color: #0057D0; }
    h1 { margin: 0; color: #0057D0; font-size: 24pt; letter-spacing: 1pt; }
    .rule { border-top: 2pt solid #E30613; margin: 8pt 0 12pt; height: 0; }
    .client-name { font-size: 12.5pt; font-weight: bold; margin: 2pt 0; }
    .items { margin-top: 14pt; }
    .items th { background: #0057D0; color: #FFFFFF; font-weight: bold; padding: 5pt 6pt; text-align: right; }
    .items th.left, .items td.left { text-align: left; }
    .items td { padding: 4pt 6pt; border-bottom: 0.75pt solid #D9DEE7; text-align: right; }
    .items tr { page-break-inside: avoid; }
    .late { color: #E30613; font-weight: bold; }
    .paid { color: #15803D; }
    .totals { margin-top: 10pt; width: 8.5cm; margin-left: auto; }
    .totals td { padding: 3pt 6pt; }
    .totals tr.due td { background: #0057D0; color: #FFFFFF; font-weight: bold; font-size: 12pt; padding: 6pt; }
</style>
</head>
<body>
    <div class="footer">
        <b>{{ DocumentSheet::companyFooterTitle($c) }}</b>
        @foreach (DocumentSheet::companyFooterLines($c) as $line)
            <br>{{ $line }}
        @endforeach
    </div>

    <table>
        <tr>
            <td><img src="{{ $statement->logoDataUri }}" alt="" style="width: 4cm;"></td>
            <td class="right">
                <h1>RELEVÉ DE COMPTE</h1>
                <div style="margin-top: 4pt;">Arrêté au <b>{{ today()->format('d/m/Y') }}</b></div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <table>
        <tr>
            <td style="width: 55%;">
                <div class="muted">Client</div>
                <div class="client-name">{{ $client->name }}</div>
                @if ($client->fullAddress())<div>{!! nl2br(e($client->fullAddress())) !!}</div>@endif
                @if ($client->ice)<div>ICE : {{ $client->ice }}</div>@endif
            </td>
            <td class="right">
                <div class="muted">Solde dû</div>
                <div style="font-size: 17pt; font-weight: bold;">{{ Money::format($statement->balance, true) }}</div>
            </td>
        </tr>
    </table>

    @if ($statement->rows === [])
        <p class="muted" style="margin-top: 16pt;">Aucune facture émise pour ce client.</p>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th class="left">Facture</th>
                    <th class="left">Date</th>
                    <th class="left">Échéance</th>
                    <th>Montant TTC</th>
                    <th>Payé</th>
                    <th>Reste à payer</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($statement->rows as $row)
                    <tr>
                        <td class="left"><b>{{ $row['number'] }}</b></td>
                        <td class="left">{{ $row['date'] }}</td>
                        <td class="left {{ $row['late'] ? 'late' : '' }}">{{ $row['due'] }}{{ $row['late'] ? ' (échue)' : '' }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['paid'] }}</td>
                        <td class="{{ $row['balance'] === 'Payée' ? 'paid' : ($row['late'] ? 'late' : '') }}">{{ $row['balance'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Total facturé</td><td class="right">{{ Money::format($statement->total, true) }}</td></tr>
            <tr><td>Total payé</td><td class="right">{{ Money::format($statement->paid, true) }}</td></tr>
            <tr class="due"><td>Solde dû</td><td class="right">{{ Money::format($statement->balance, true) }}</td></tr>
        </table>

        @if ($c['bank_line'])
            <p style="margin-top: 18pt;"><b>Paiement par virement bancaire :</b><br>{{ $c['bank_line'] }}</p>
        @endif
    @endif
</body>
</html>
