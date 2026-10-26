@php
    use App\Support\Money;
@endphp

<div x-data="documentAutosave()"
     @focus-line.window="setTimeout(() => document.querySelector(`[data-line='${$event.detail.key}'] [data-field=description]`)?.focus(), 60)">

    {{-- Barre d'actions : reste visible en haut de l'écran --}}
    <div class="sticky top-[4.6rem] z-20 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-line bg-desk/95 px-4 py-3 backdrop-blur sm:-mx-8 sm:px-8 lg:top-0">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded bg-white px-2.5 py-1 text-sm font-bold uppercase tracking-wide text-muted ring-1 ring-line">Brouillon</span>
            <span class="text-muted" aria-live="polite">
                <span x-show="state === 'dirty'" x-cloak>Modifications en cours…</span>
                <span x-show="state === 'saving'" x-cloak>Enregistrement…</span>
                <span x-show="state === 'error'" x-cloak class="font-semibold text-accent">Non enregistré : corrigez les champs en rouge.</span>
                <span x-show="state === 'saved' || state === 'idle'">
                    @if ($savedAt)
                        <span class="font-semibold text-green-700">✓ Enregistré à {{ $savedAt }}</span>
                    @else
                        Nouveau devis : il sera enregistré dès la première saisie.
                    @endif
                </span>
            </span>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn-secondary" @click="layoutPanel = true">Mise en page</button>
            @if ($quoteId)
                <button type="button" class="btn-secondary" @click="openAfterSave(@js(route('quotes.pdf', $quoteId)))">Aperçu PDF</button>
                <button type="button" class="btn-secondary" @click="openAfterSave(@js(route('quotes.word', $quoteId)), false)">Word</button>
            @endif
            <button type="button" class="btn-primary"
                    wire:click="send"
                    wire:confirm="Marquer ce devis comme envoyé ?&#10;&#10;Il recevra son numéro définitif et ne pourra plus être modifié (vous pourrez toujours le dupliquer)."
                    wire:loading.attr="disabled" wire:target="send">
                Marquer comme envoyé
            </button>
        </div>
    </div>

    @if ($errors->hasAny(['send', 'clientId', 'lines']))
        <div role="alert" class="mb-6 rounded-lg border-l-4 border-accent bg-red-50 px-5 py-4 text-lg text-red-900">
            @foreach (['send', 'clientId', 'lines'] as $key)
                @error($key)<p>{{ $message }}</p>@enderror
            @endforeach
        </div>
    @endif

    <p class="mx-auto mb-4 max-w-[210mm] text-muted">
        Cliquez sur n’importe quel texte pour le modifier, y compris les titres et les libellés. Le logo, les éléments affichés
        et les informations de la société se règlent dans « Mise en page ». Les montants se calculent seuls.
    </p>

    {{-- ===================== Feuille A4 ===================== --}}
    <div class="doc-sheet">
        {{-- En-tête --}}
        <div class="flex items-start justify-between gap-4">
            <div class="shrink-0">
                @if ($logoUrl)
                    <button type="button" @click="layoutPanel = true" title="Modifier ou retirer le logo"
                            class="rounded border border-dashed border-transparent p-0.5 hover:border-line focus:border-brand focus:outline-none">
                        <img src="{{ $logoUrl }}" alt="Logo" class="max-w-[28vw] sm:max-w-none" style="width: {{ $logoWidthPx }}px">
                    </button>
                @else
                    <button type="button" @click="layoutPanel = true"
                            class="rounded border border-dashed border-line px-3 py-6 text-sm font-semibold text-muted hover:border-brand hover:text-brand">
                        + Ajouter un logo
                    </button>
                @endif
            </div>
            <div class="min-w-0 flex-1 text-right">
                <x-document.label key="title" label="Titre du document" class="doc-title" />
                <p class="mt-1 flex flex-wrap items-baseline justify-end gap-1 font-bold">
                    <x-document.label key="number" label="Libellé du numéro" class="doc-input-fit text-right font-bold" />
                    <span class="font-normal italic text-muted">attribué à l’envoi</span>
                </p>
                @if ($layout['show']['amount'])
                    <x-document.label key="amount" label="Libellé du montant" class="doc-muted mt-3 text-right text-[13px]" />
                    <p class="text-[22px] font-bold tabular-nums">{{ Money::format($totals->totalTtc, true) }}</p>
                @endif
            </div>
        </div>
        <hr class="doc-rule">

        {{-- Client et dates --}}
        <div class="mb-5 flex flex-col gap-5 sm:flex-row sm:justify-between">
            <div class="min-w-0 flex-1">
                <x-document.label key="client" label="Libellé du client" class="doc-input-fit doc-muted mb-1" />
                <div x-data="clientPicker()" @click.outside="open = false" data-no-autosave>

                <div class="relative">
                    @if ($client)
                        <button type="button" @click="toggle()"
                                class="w-full rounded border border-dashed border-transparent px-1 py-0.5 text-left hover:border-line focus:border-brand focus:outline-none">
                            <span class="block text-[17px] font-bold">{{ $client->name }}</span>
                            @if ($client->fullAddress())<span class="block">{{ $client->fullAddress() }}</span>@endif
                            @if ($client->ice && $layout['show']['client_ice'])<span class="mt-1.5 block">ICE : {{ $client->ice }}</span>@endif
                            <span class="mt-1 block text-sm font-semibold text-brand">Changer de client</span>
                        </button>
                    @else
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="toggle()" @class(['btn-secondary', 'ring-2 ring-accent' => $errors->has('clientId')])>Choisir le client…</button>
                            <button type="button" wire:click="openClientForm" class="btn-secondary">+ Nouveau client</button>
                        </div>
                    @endif

                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute left-0 z-30 mt-1 w-full min-w-[17rem] rounded-lg border border-line bg-white p-2 shadow-xl sm:w-96">
                        <label for="client-search" class="sr-only">Rechercher un client</label>
                        <input id="client-search" x-ref="search" x-model="query" type="search" autocomplete="off"
                               placeholder="Rechercher un client…" class="field-input"
                               @keydown.enter.prevent="firstVisible()?.click()" @keydown.escape="open = false">
                        <ul class="mt-2 max-h-72 overflow-y-auto">
                            @forelse ($clients as $option)
                                <li wire:key="client-option-{{ $option->id }}">
                                    <button type="button" data-client-option
                                            x-show="matches(@js($option->name.' '.$option->city.' '.$option->ice))"
                                            wire:click="chooseClient({{ $option->id }})" @click="open = false; query = ''"
                                            @class(['block w-full rounded px-3 py-2 text-left hover:bg-brand-soft', 'bg-brand-soft' => $option->id === $clientId])>
                                        <span class="block font-semibold">{{ $option->name }}</span>
                                        <span class="block text-sm text-muted">{{ collect([$option->city, $option->ice ? 'ICE '.$option->ice : null])->filter()->implode(' · ') ?: ' ' }}</span>
                                    </button>
                                </li>
                            @empty
                                <li class="px-3 py-2 text-muted">Aucun client pour l’instant.</li>
                            @endforelse
                        </ul>
                        <button type="button" wire:click="openClientForm" @click="open = false"
                                class="mt-2 w-full rounded border border-dashed border-brand bg-brand-soft px-3 py-2 font-bold text-brand">
                            + Nouveau client
                        </button>
                    </div>
                </div>
                </div>
            </div>

            <div class="grid grid-cols-[auto_9.5rem] items-center gap-x-3 gap-y-1 sm:text-right">
                <x-document.label key="date" label="Libellé de la date" class="doc-input-fit sm:text-right" />
                <div>
                    <input id="issue-date" aria-label="Date" wire:model.live.blur="issueDate" placeholder="JJ/MM/AAAA" inputmode="numeric"
                           @class(['doc-input text-right text-muted', 'is-invalid' => $errors->has('issueDate')])>
                    @error('issueDate')<p class="text-right text-xs font-semibold text-accent">{{ $message }}</p>@enderror
                </div>
                <x-document.label key="due_date" label="Libellé de la date de validité" class="doc-input-fit sm:text-right" />
                <div>
                    <input id="valid-until" aria-label="Date de validité" wire:model.live.blur="validUntil" placeholder="JJ/MM/AAAA" inputmode="numeric"
                           @class(['doc-input text-right text-muted', 'is-invalid' => $errors->has('validUntil')])>
                    @error('validUntil')<p class="text-right text-xs font-semibold text-accent">{{ $message }}</p>@enderror
                </div>
                @if ($layout['show']['reference'])
                    <x-document.label key="reference" label="Libellé de la référence" class="doc-input-fit sm:text-right" />
                    <input id="reference" aria-label="Référence" wire:model.live.debounce.800ms="reference" placeholder="facultatif" class="doc-input text-right text-muted">
                @endif
            </div>
        </div>

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

        {{-- Totaux --}}
        <div class="ml-auto mt-4 w-full tabular-nums sm:w-[21rem]">
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <x-document.label key="subtotal" label="Libellé du sous-total" class="doc-input-fit" />
                <span>{{ Money::format($totals->subtotal) }}</span>
            </div>
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <label for="discount" class="flex items-center gap-1">
                    <x-document.label key="discount" label="Libellé de la remise" class="doc-input-fit" />
                    <input id="discount" wire:model.live.debounce.400ms="discountPercent" inputmode="decimal" placeholder="0"
                           @class(['doc-input w-16 text-right', 'is-invalid' => $errors->has('discountPercent')])> %
                </label>
                <span>{{ $totals->hasDiscount() ? '-'.Money::format($totals->discount) : '—' }}</span>
            </div>
            @error('discountPercent')<p class="px-2 text-sm font-semibold text-accent">{{ $message }}</p>@enderror
            <div class="flex items-center justify-between gap-2 px-2 py-1 font-semibold">
                <x-document.label key="total_ht" label="Libellé du total HT" class="doc-input-fit font-semibold" />
                <span>{{ Money::format($totals->totalHt) }}</span>
            </div>
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <label for="vat-rate" class="flex items-center gap-1">
                    <x-document.label key="vat" label="Libellé de la TVA" class="doc-input-fit" />
                    (<select id="vat-rate" wire:model.live="vatRate" class="doc-input inline w-auto py-0 pr-6">
                        @foreach (\App\Support\DocumentCalculator::VAT_RATES as $rate)
                            <option value="{{ $rate }}">{{ $rate }} %</option>
                        @endforeach
                    </select>)
                </label>
                <span>{{ Money::format($totals->vat) }}</span>
            </div>
            <div class="mt-1 flex items-center justify-between gap-2 bg-brand px-2 py-1.5 font-bold text-white">
                <x-document.label key="due" label="Libellé du net à payer" class="doc-input-fit doc-input-head font-bold" />
                <span>{{ Money::format($totals->totalTtc, true) }}</span>
            </div>
        </div>

        @if ($layout['show']['words'])
            <p class="mt-5 flex flex-wrap items-baseline gap-1">
                <x-document.label key="words" label="Libellé du montant en lettres" class="doc-input-fit font-bold" />
                <i class="text-muted">{{ $words }}</i>
            </p>
        @endif

        {{-- Conditions --}}
        <div class="mt-3 grid gap-1">
            @if ($layout['show']['validity'])
                <p class="flex flex-wrap items-baseline gap-1">
                    <x-document.label key="validity" label="Libellé de la validité" class="doc-input-fit font-bold" />
                    <span>{{ $validityDays !== null ? $validityDays.' jours, jusqu’au '.$validUntil : '—' }}</span>
                </p>
            @endif
            <div @class(['flex flex-col sm:flex-row sm:items-baseline sm:gap-1.5', 'hidden' => ! $layout['show']['payment_terms']])>
                <x-document.label key="payment_terms" label="Libellé des modalités de paiement" class="doc-input-fit shrink-0 font-bold" />
                <input id="payment-terms" aria-label="Modalités de paiement" wire:model.live.debounce.800ms="paymentTerms" placeholder="ex. 50 % à la commande, solde à la livraison" class="doc-input">
            </div>
            <div @class(['flex flex-col sm:flex-row sm:items-baseline sm:gap-1.5', 'hidden' => ! $layout['show']['completion_delay']])>
                <x-document.label key="completion_delay" label="Libellé du délai de réalisation" class="doc-input-fit shrink-0 font-bold" />
                <input id="completion-delay" aria-label="Délai de réalisation" wire:model.live.debounce.800ms="completionDelay" placeholder="ex. 3 semaines" class="doc-input">
            </div>
            <div @class(['mt-2', 'hidden' => ! $layout['show']['notes']])>
                <label for="notes" class="text-sm text-muted">Mentions (facultatif)</label>
                <textarea id="notes" rows="1" wire:model.live.debounce.800ms="notes" x-init="$nextTick(() => autosize($el))" @input="autosize($el)"
                          placeholder="Texte imprimé en petit sous les conditions" class="doc-input text-sm text-muted"></textarea>
            </div>
        </div>

        {{-- Signatures --}}
        @if ($layout['show']['client_signature'] || $layout['show']['company_signature'])
            <div class="mt-8 flex justify-between gap-4 text-center">
                <div class="w-[45%]">
                    @if ($layout['show']['client_signature'])
                        <x-document.label key="client_signature" label="Titre de la signature du client" class="text-center font-bold text-brand" />
                        <x-document.label key="client_signature_note" label="Mention sous la signature du client" class="text-center text-sm text-muted" />
                    @endif
                </div>
                <div class="w-[45%]">
                    @if ($layout['show']['company_signature'])
                        <x-document.label key="company_signature" label="Titre de la signature de la société" :placeholder="$company['name']"
                                          class="text-center font-bold text-brand placeholder:text-brand" />
                        <x-document.label key="company_signature_note" label="Mention sous la signature de la société" class="text-center text-sm text-muted" />
                    @endif
                </div>
            </div>
        @endif

        @if ($layout['show']['footer'])
            <button type="button" @click="layoutPanel = true" title="Modifier les informations de la société sur ce document"
                    class="mt-16 block w-full rounded border border-dashed border-transparent hover:border-line">
                <x-document.footer :company="$company" />
            </button>
        @endif
    </div>

    {{-- « Annuler » après la suppression d'une ligne --}}
    @if ($removedLine)
        <div wire:key="undo-{{ $removedLine['line']['key'] }}" x-data="{ show: true }" x-init="setTimeout(() => show = false, 8000)"
             x-show="show" x-transition.opacity role="status"
             class="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 items-center gap-5 rounded-lg bg-ink px-5 py-3 text-lg text-white shadow-xl">
            <span>Ligne supprimée.</span>
            <button type="button" wire:click="undoRemove" @click="show = false" class="font-bold text-white underline underline-offset-4">Annuler</button>
        </div>
    @endif

    {{-- Création rapide d'un client --}}
    @if ($showClientForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/50 p-4 sm:items-center" data-no-autosave
             @keydown.escape.window="$wire.set('showClientForm', false)">
            <form wire:submit="createClient" class="card w-full max-w-lg" role="dialog" aria-labelledby="new-client-title">
                <h2 id="new-client-title" class="card-title">Nouveau client</h2>
                <div class="space-y-4">
                    @foreach (['name' => 'Raison sociale', 'address' => 'Adresse', 'city' => 'Ville', 'ice' => 'ICE (15 chiffres)'] as $field => $label)
                        <div>
                            <label for="new-client-{{ $field }}" class="field-label">
                                {{ $label }}@if ($field === 'name')<span class="text-accent" aria-hidden="true">*</span>@endif
                            </label>
                            <input id="new-client-{{ $field }}" wire:model="newClient.{{ $field }}" class="field-input" autocomplete="off"
                                   @if ($field === 'name') autofocus @endif @if ($field === 'ice') inputmode="numeric" @endif>
                            @error("newClient.$field")<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <p class="field-help mt-3">IF, RC, contact et délai de paiement se complètent ensuite dans la fiche du client.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary">Créer et choisir ce client</button>
                    <button type="button" class="btn-secondary" wire:click="$set('showClientForm', false)">Annuler</button>
                </div>
            </form>
        </div>
    @endif

    @include('livewire.partials.layout-panel', ['documentLabel' => 'devis'])
</div>
