<?php

namespace App\Livewire;

use App\Enums\QuoteStatus;
use App\Livewire\Concerns\FiltersByPeriod;
use App\Models\Client;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des devis : recherche, filtres par statut, client et période. Filtres gardés dans l'adresse.
 */
class QuoteList extends Component
{
    use FiltersByPeriod, WithPagination;

    #[Url(as: 'recherche', except: '')]
    public string $search = '';

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(as: 'client', except: '')]
    public string $clientId = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'clientId', 'period', 'from', 'to');
        $this->resetPage();
    }

    public function render(): View
    {
        $query = $this->filteredQuery();

        return view('livewire.quote-list', [
            'quotes' => (clone $query)->with('client')->orderByDesc('issue_date')->orderByDesc('id')->paginate(25),
            'count' => (clone $query)->count(),
            'total' => (int) (clone $query)->sum('total_ttc'),
            'clients' => Client::query()->whereHas('quotes')->orderBy('name')->get(['id', 'name']),
            'statuses' => QuoteStatus::cases(),
            'periods' => self::periods(),
            'filtered' => $this->search !== '' || $this->status !== '' || $this->clientId !== '' || $this->period !== '',
        ]);
    }

    private function filteredQuery(): Builder
    {
        $search = trim($this->search);
        [$start, $end] = $this->periodRange();

        return Quote::query()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('number', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))))
            ->when(QuoteStatus::tryFrom($this->status), fn (Builder $q, QuoteStatus $status) => $q->withDisplayStatus($status))
            ->when($this->clientId !== '', fn (Builder $q) => $q->where('client_id', (int) $this->clientId))
            ->when($start, fn (Builder $q) => $q->whereDate('issue_date', '>=', $start))
            ->when($end, fn (Builder $q) => $q->whereDate('issue_date', '<=', $end));
    }
}
