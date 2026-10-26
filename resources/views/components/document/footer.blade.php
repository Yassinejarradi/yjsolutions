{{-- Pied de page des documents à l'écran : même texte que le PDF et l'export Word. --}}
@props(['company'])

<div {{ $attributes->merge(['class' => 'border-t-[1.5px] border-accent pt-3 text-center text-[12px] leading-relaxed text-muted']) }}>
    <b class="text-brand">{{ \App\Support\DocumentSheet::companyFooterTitle($company) }}</b>
    @foreach (\App\Support\DocumentSheet::companyFooterLines($company) as $line)
        <br>{{ $line }}
    @endforeach
</div>
