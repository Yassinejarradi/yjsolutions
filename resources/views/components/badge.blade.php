{{--
    Badge de statut (devis, facture) : pastille colorée + libellé.
    <x-badge :status="$quote->displayStatus()" />   ou   size="lg" dans un titre.
--}}
@props(['status', 'size' => 'md'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full font-semibold ring-1 ring-inset '
    .($size === 'lg' ? 'px-3 py-1 text-base ' : 'px-2.5 py-0.5 text-sm ').$status->badgeClasses()]) }}>
    <span class="h-2 w-2 rounded-full {{ $status->dotClass() }}" aria-hidden="true"></span>
    {{ $status->label() }}
</span>
