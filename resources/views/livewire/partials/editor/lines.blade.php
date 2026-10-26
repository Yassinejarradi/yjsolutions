{{-- Lignes du document : tableau à partir de la tablette, cartes sur téléphone. --}}
@php
    use App\Support\Money;
@endphp

    {{-- Lignes --}}
    <div class="doc-lines-head text-[14px]">
        <div class="px-0.5 py-1"><x-document.label key="col_number" label="En-tête de colonne : numéro" class="doc-input-head text-center" /></div>
        <div class="px-0.5 py-1"><x-document.label key="col_description" label="En-tête de colonne : désignation" class="doc-input-head" /></div>
        <div class="px-0.5 py-1"><x-document.label key="col_quantity" label="En-tête de colonne : quantité" class="doc-input-head text-right" /></div>
        <div class="px-0.5 py-1"><x-document.label key="col_unit_price" label="En-tête de colonne : prix" class="doc-input-head text-right" /></div>
        <div class="px-0.5 py-1"><x-document.label key="col_amount" label="En-tête de colonne : montant" class="doc-input-head text-right" /></div>
        <div></div>
    </div>

    <div wire:sort="sortLine">
        @foreach ($lines as $i => $line)
            {{-- La position fait partie de la clé : une ligne déplacée est reconstruite, sinon ses champs
                 (liés par position, lines.N.…) garderaient les valeurs de son ancienne place. --}}
            <div class="doc-line" wire:key="line-{{ $line['key'] }}-{{ $i }}" wire:sort:item="{{ $line['key'] }}" data-line="{{ $line['key'] }}">
                {{-- Numéro et poignée de glisser-déposer --}}
                <div class="col-span-2 flex items-center gap-1 text-muted md:col-span-1 md:justify-center md:pt-1">
                    <button type="button" wire:sort:handle class="cursor-grab touch-none rounded px-1 text-lg leading-none hover:bg-brand-soft active:cursor-grabbing"
                            title="Glisser pour déplacer la ligne" aria-label="Déplacer la ligne {{ $i + 1 }}">⠿</button>
                    <span><span class="md:hidden">Ligne </span>{{ $i + 1 }}</span>
                </div>

                {{-- Désignation (plusieurs lignes, s'agrandit seule) avec auto-complétion --}}
                <div class="relative col-span-2 md:col-span-1" x-data="lineSuggest()" @click.outside="open = false">
                    <label class="doc-cell-label" for="desc-{{ $line['key'] }}">{{ $layout['labels']['col_description'] }}</label>
                    <textarea id="desc-{{ $line['key'] }}" rows="1" data-field="description"
                              wire:model.live.debounce.800ms="lines.{{ $i }}.description"
                              x-init="$nextTick(() => autosize($el))"
                              @input="autosize($el)" @input.debounce.250ms="lookup($el.value)" @keydown="keydown($event)"
                              placeholder="Article et description"
                              @class(['doc-input', 'is-invalid' => $errors->has("lines.$i.description")])></textarea>
                    <ul x-show="open" x-cloak
                        class="absolute left-0 z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-line bg-white py-1 text-[15px] shadow-xl md:w-[30rem]">
                        <li class="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted">Déjà utilisé</li>
                        <template x-for="(item, index) in items" :key="index">
                            <li>
                                <button type="button" @mousedown.prevent="pick(index)"
                                        :class="index === active ? 'bg-brand-soft' : ''"
                                        class="flex w-full items-start justify-between gap-3 px-3 py-2 text-left hover:bg-brand-soft">
                                    <span class="whitespace-pre-line" x-text="item.description"></span>
                                    <span class="shrink-0 font-semibold tabular-nums text-muted" x-text="item.price_label"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>

                <div>
                    <label class="doc-cell-label" for="qty-{{ $line['key'] }}">{{ $layout['labels']['col_quantity'] }}</label>
                    <input id="qty-{{ $line['key'] }}" inputmode="decimal" data-field="quantity"
                           wire:model.live.debounce.400ms="lines.{{ $i }}.quantity"
                           @class(['doc-input md:text-right tabular-nums', 'is-invalid' => $errors->has("lines.$i.quantity")])>
                </div>

                <div>
                    <label class="doc-cell-label" for="pu-{{ $line['key'] }}">{{ $layout['labels']['col_unit_price'] }}</label>
                    <input id="pu-{{ $line['key'] }}" inputmode="decimal" data-field="unit_price" placeholder="0,00"
                           wire:model.live.debounce.400ms="lines.{{ $i }}.unit_price"
                           @if ($loop->last) @keydown.tab="if (! $event.shiftKey) { $event.preventDefault(); $wire.addLine() }" @endif
                           @class(['doc-input md:text-right tabular-nums', 'is-invalid' => $errors->has("lines.$i.unit_price")])>
                </div>

                <div class="col-span-2 flex items-baseline justify-between pt-1 font-semibold tabular-nums md:col-span-1 md:block md:pt-1 md:text-right">
                    <span class="doc-cell-label">{{ $layout['labels']['col_amount'] }}</span>
                    {{ Money::format($totals->lineAmounts[$i] ?? 0) }}
                </div>

                {{-- Actions de la ligne --}}
                <div class="col-span-2 mt-2 flex flex-wrap gap-2 md:col-span-1 md:mt-0 md:grid md:grid-cols-2 md:content-start md:justify-items-end md:gap-1 md:pl-1">
                    <button type="button" class="doc-op max-md:w-auto max-md:px-3" wire:click="moveLine('{{ $line['key'] }}', -1)" @disabled($loop->first)
                            title="Monter" aria-label="Monter la ligne {{ $i + 1 }}">↑<span class="ml-1 md:hidden">Monter</span></button>
                    <button type="button" class="doc-op max-md:w-auto max-md:px-3" wire:click="moveLine('{{ $line['key'] }}', 1)" @disabled($loop->last)
                            title="Descendre" aria-label="Descendre la ligne {{ $i + 1 }}">↓<span class="ml-1 md:hidden">Descendre</span></button>
                    <button type="button" class="doc-op max-md:w-auto max-md:px-3" wire:click="duplicateLine('{{ $line['key'] }}')"
                            title="Dupliquer" aria-label="Dupliquer la ligne {{ $i + 1 }}">⧉<span class="ml-1 md:hidden">Dupliquer</span></button>
                    <button type="button" class="doc-op text-accent max-md:w-auto max-md:px-3" wire:click="removeLine('{{ $line['key'] }}')"
                            title="Supprimer" aria-label="Supprimer la ligne {{ $i + 1 }}">✕<span class="ml-1 md:hidden">Supprimer</span></button>
                </div>

                @if ($errors->has("lines.$i.quantity") || $errors->has("lines.$i.unit_price"))
                    <p class="col-span-2 text-sm font-semibold text-accent md:col-span-6 md:pl-11">
                        {{ $errors->first("lines.$i.quantity") ?: $errors->first("lines.$i.unit_price") }}
                        Exemple : 1 250,50
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    <button type="button" wire:click="addLine"
            class="mt-3 rounded-md border border-dashed border-brand bg-brand-soft px-4 py-2 font-bold text-brand hover:bg-brand hover:text-white">
        + Ajouter une ligne
    </button>
