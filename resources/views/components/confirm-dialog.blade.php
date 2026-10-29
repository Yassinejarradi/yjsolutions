{{-- Fenêtre de confirmation commune (voir resources/js/confirm-dialog.js). Placée une fois dans la mise en page. --}}
<div x-data="confirmDialog" x-show="open" x-cloak
     @confirm-action.window="show($event.detail)" @keydown.escape.window="open && cancel()"
     x-transition.opacity
     class="fixed inset-0 z-[70] flex items-end justify-center bg-navy-deep/60 p-4 backdrop-blur-sm sm:items-center"
     role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-lift" @click.outside="cancel()" x-trap.noscroll="open">
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                  :class="danger ? 'bg-red-100 text-accent' : 'bg-brand-soft text-brand'" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <div class="min-w-0">
                <h2 id="confirm-title" class="text-xl font-bold text-ink" x-text="title"></h2>
                <p id="confirm-message" class="mt-2 whitespace-pre-line text-lg text-muted" x-text="message"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" class="btn-secondary px-6 py-3 text-lg" x-ref="cancel" @click="cancel()">Non, revenir</button>
            <button type="button" class="px-6 py-3 text-lg" :class="danger ? 'btn-danger' : 'btn-primary'"
                    x-ref="ok" @click="confirm()" x-text="confirmLabel"></button>
        </div>
    </div>
</div>
