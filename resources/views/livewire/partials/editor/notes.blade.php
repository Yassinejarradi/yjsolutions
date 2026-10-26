{{-- Mentions imprimées en petit (facultatif). --}}
        <div @class(['mt-2', 'hidden' => ! $layout['show']['notes']])>
            <label for="notes" class="text-sm text-muted">Mentions (facultatif)</label>
            <textarea id="notes" rows="1" wire:model.live.debounce.800ms="notes" x-init="$nextTick(() => autosize($el))" @input="autosize($el)"
                      placeholder="Texte imprimé en petit sous les conditions" class="doc-input text-sm text-muted"></textarea>
        </div>
