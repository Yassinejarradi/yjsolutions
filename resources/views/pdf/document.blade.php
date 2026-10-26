{{--
    PDF d'un devis (et d'une facture en phase 4), rendu par dompdf.
    Mise en page de docs/prototype.html : A4, marges 1,8 cm et 3 cm en bas.
    Le pied de page est « fixed » (répété sur chaque page) ; « Page X / Y » est ajouté par App\Support\Pdf\DocumentPdf.
    dompdf ne gère pas flexbox : la mise en page utilise des tableaux.
--}}
@php
    $font = fn (string $file) => str_replace('\\', '/', resource_path('fonts/carlito/'.$file));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $sheet->fileName }}</title>
<style>
    @font-face { font-family: 'Carlito'; font-weight: normal; font-style: normal; src: url('{{ $font('Carlito-Regular.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Carlito'; font-weight: bold; font-style: normal; src: url('{{ $font('Carlito-Bold.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Carlito'; font-weight: normal; font-style: italic; src: url('{{ $font('Carlito-Italic.ttf') }}') format('truetype'); }
    @font-face { font-family: 'Carlito'; font-weight: bold; font-style: italic; src: url('{{ $font('Carlito-BoldItalic.ttf') }}') format('truetype'); }

    @page { size: A4; margin: 1.8cm 1.8cm 3cm 1.8cm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Carlito', Calibri, Arial, sans-serif; font-size: 10.5pt; color: #1F2937; line-height: 1.2; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 0; vertical-align: top; }
    .muted { color: #6B7280; }
    .right { text-align: right; }

    /* Filigrane (brouillon) : semi-transparent, le texte reste lisible dessous. */
    .watermark { position: fixed; top: 9cm; left: -1cm; width: 19.4cm; text-align: center;
                 font-size: 96pt; font-weight: bold; color: #E30613; opacity: 0.12; transform: rotate(-35deg); letter-spacing: 4pt; }

    /* Pied de page répété sur chaque page, dans la marge du bas. */
    .footer { position: fixed; left: 0; right: 0; bottom: -2.2cm; height: 1.5cm;
              border-top: 1.5pt solid #E30613; padding-top: 5pt; text-align: center; font-size: 8pt; color: #6B7280; line-height: 1.45; }
    .footer b { color: #0057D0; }

    .head td.title { text-align: right; }
    .head h1 { margin: 0; color: #0057D0; font-size: 28pt; letter-spacing: 1.5pt; line-height: 1.1; }
    .head .num { margin-top: 3pt; font-weight: bold; }
    .head .num span { color: #E30613; }
    .head .lab { margin-top: 6pt; font-size: 9pt; color: #6B7280; }
    .head .big { font-size: 17pt; font-weight: bold; }
    .rule { border-top: 2pt solid #E30613; margin: 6pt 0 11pt; height: 0; }

    .parties td.client { width: 58%; }
    .parties .client-name { font-size: 12.5pt; font-weight: bold; margin: 1pt 0; }
    .meta td { padding: 1.5pt 0 1.5pt 10pt; text-align: right; white-space: nowrap; }
    .meta td.value { white-space: normal; width: 3.4cm; }
    .meta td.value { color: #6B7280; }

    .items { margin-top: 11pt; }
    .items th { background: #0057D0; color: #FFFFFF; font-weight: bold; padding: 5pt 6pt; text-align: right; }
    .items th.n, .items td.n { text-align: center; }
    .items th.desc { text-align: left; }
    .items td { padding: 4pt 6pt; border-bottom: 0.75pt solid #D9DEE7; text-align: right; }
    .items td.desc { text-align: left; }
    .items td.n { color: #6B7280; }
    .items td.amount { font-weight: bold; }
    .items tr { page-break-inside: avoid; }

    .totals-wrap { margin-top: 8pt; page-break-inside: avoid; }
    .totals td { padding: 2pt 7pt; text-align: right; }
    .totals td.label { text-align: left; }
    .totals tr.bold td { font-weight: bold; }
    .totals tr.due td { background: #0057D0; color: #FFFFFF; font-weight: bold; }

    .words { margin: 12pt 0 6pt; }
    .words i { color: #6B7280; }
    .terms p { margin: 2pt 0; }
    .notes { margin-top: 10pt; font-size: 9pt; color: #6B7280; }
    .signatures { margin-top: 14pt; page-break-inside: avoid; }
    .signatures td { width: 50%; text-align: center; color: #0057D0; font-weight: bold; height: 1.6cm; } /* le cachet déborde dans l'espace libre dessous */
    .signatures small { display: block; color: #6B7280; font-weight: normal; font-size: 8.5pt; }
</style>
</head>
<body>
@if ($sheet->watermark)
    <div class="watermark">{{ $sheet->watermark }}</div>
@endif

@if ($sheet->showFooter)
    <div class="footer">
        <b>{{ $sheet->footerTitle() }}</b>
        @foreach ($sheet->footerLines() as $line)
            <br>{{ $line }}
        @endforeach
    </div>
@endif

<table class="head">
    <tr>
        <td class="logo">
            @if ($sheet->logoPath)<img src="{{ $sheet->logoDataUri() }}" alt="" style="width: {{ $sheet->logoWidthCm }}cm">@endif
        </td>
        <td class="title">
            <h1>{{ $sheet->title }}</h1>
            @if ($sheet->number)
                <div class="num">{{ $sheet->numberLabel }} <span>{{ $sheet->number }}</span></div>
            @endif
            @if ($sheet->amountLabel !== null)
                <div class="lab">{{ $sheet->amountLabel }}</div>
                <div class="big">{{ $sheet->amount }}</div>
            @endif
        </td>
    </tr>
</table>
<div class="rule"></div>

<table class="parties">
    <tr>
        <td class="client">
            <div class="muted">{{ $sheet->clientLabel }}</div>
            <div class="client-name">{{ $sheet->client['name'] }}</div>
            @if ($sheet->client['address'])<div>{{ $sheet->client['address'] }}</div>@endif
            @if ($sheet->client['ice'])<div style="margin-top: 5pt">ICE : {{ $sheet->client['ice'] }}</div>@endif
        </td>
        <td>
            <table class="meta">
                @foreach ($sheet->dates as [$label, $value])
                    <tr><td>{{ $label }}</td><td class="value">{{ $value }}</td></tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th class="n" style="width: 0.9cm">{{ $sheet->columns['number'] }}</th>
            <th class="desc">{{ $sheet->columns['description'] }}</th>
            <th style="width: 2cm">{{ $sheet->columns['quantity'] }}</th>
            <th style="width: 2.8cm">{{ $sheet->columns['unit_price'] }}</th>
            <th style="width: 3cm">{{ $sheet->columns['amount'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sheet->lines as $line)
            <tr>
                <td class="n">{{ $line['number'] }}</td>
                <td class="desc">{!! nl2br(e($line['description'])) !!}</td>
                <td>{{ $line['quantity'] }}</td>
                <td>{{ $line['unit_price'] }}</td>
                <td class="amount">{{ $line['amount'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals-wrap">
    <tr>
        <td></td>
        <td style="width: 8.2cm">
            <table class="totals">
                @foreach ($sheet->totalRows as $row)
                    <tr class="{{ $row[2] ?? '' }}"><td class="label">{{ $row[0] }}</td><td>{{ $row[1] }}</td></tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>

@if ($sheet->words !== null)
    <p class="words"><b>{{ $sheet->wordsLabel }}</b> <i>{{ $sheet->words }}</i></p>
@endif

<div class="terms">
    @foreach ($sheet->terms as [$label, $text])
        <p><b>{{ $label }}</b> {{ $text }}</p>
    @endforeach
</div>

@if ($sheet->notes)
    <div class="notes">{!! nl2br(e($sheet->notes)) !!}</div>
@endif

@if ($sheet->clientSignature || $sheet->companySignature)
    <table class="signatures">
        <tr>
            <td>@if ($sheet->clientSignature){{ $sheet->clientSignature[0] }}<small>{{ $sheet->clientSignature[1] }}</small>@endif</td>
            <td>@if ($sheet->companySignature){{ $sheet->companySignature[0] }}<small>{{ $sheet->companySignature[1] }}</small>@endif</td>
        </tr>
    </table>
@endif
</body>
</html>
