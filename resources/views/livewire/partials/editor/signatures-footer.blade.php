{{-- Signatures et pied de page (informations de la société). --}}
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
