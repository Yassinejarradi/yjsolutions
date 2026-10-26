{{--
    Texte de la mise en page modifiable directement sur la feuille (titre, libellés, en-têtes…).
    <x-document.label key="title" class="doc-title" />  →  $layout['labels']['title']
--}}
@props(['key', 'label' => 'Texte modifiable', 'placeholder' => null])

<input type="text" wire:model.live.debounce.800ms="layout.labels.{{ $key }}"
       aria-label="{{ $label }}" title="Cliquez pour modifier ce texte" placeholder="{{ $placeholder }}"
       {{ $attributes->merge(['class' => 'doc-input']) }}>
