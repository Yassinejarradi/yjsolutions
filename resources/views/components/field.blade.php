{{--
    Champ de formulaire complet : libellé, saisie, aide et message d'erreur.
    <x-field name="ice" label="ICE" :value="$setting->ice" help="15 chiffres" />
    Pour un champ texte long : type="textarea".
--}}
@props(['name', 'label', 'value' => null, 'type' => 'text', 'help' => null, 'required' => false])

@php($id = 'field-'.str_replace(['[', ']', '.'], '-', $name))

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}
        @if ($required)<span class="text-accent" aria-hidden="true">*</span>@endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="4" @required($required)
                  {{ $attributes->except('class')->merge(['class' => 'field-input']) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @required($required)
               @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
               {{ $attributes->except('class')->merge(['class' => 'field-input']) }}>
    @endif

    @if ($help)
        <p class="field-help">{{ $help }}</p>
    @endif

    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
