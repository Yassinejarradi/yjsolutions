<?php

namespace App\Livewire;

use App\Enums\InvoiceStatus;
use App\Livewire\Concerns\FiltersByPeriod;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des factures : recherche, filtres par statut (y compris En retard, Payée…), client et période.
 */
class InvoiceList extends Component
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
        $billed = (clone $query)->billed();

        return view('livewire.invoice-list', [
            'invoices' => (clone $query)->with('client')->orderByDesc('issue_date')->orderByDesc('id')->paginate(25),
            'count' => (clone $query)->count(),
            'total' => (int) (clone $billed)->sum('total_ttc'),
            'outstanding' => (int) (clone $billed)->sum(DB::raw('total_ttc - amount_paid')),
            'clients' => Client::query()->whereHas('invoices')->orderBy('name')->get(['id', 'name']),
            'statuses' => InvoiceStatus::cases(),
            'periods' => self::periods(),
            'filtered' => $this->search !== '' || $this->status !== '' || $this->clientId !== '' || $this->period !== '',
        ]);
    }

    private function filteredQuery(): Builder
    {
        $search = trim($this->search);
        [$start, $end] = $this->periodRange();

        return Invoice::query()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('number', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhereHas('client', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))))
            ->when(InvoiceStatus::tryFrom($this->status), fn (Builder $q, InvoiceStatus $status) => $q->withDisplayStatus($status))
            ->when($this->clientId !== '', fn (Builder $q) => $q->where('client_id', (int) $this->clientId))
            ->when($start, fn (Builder $q) => $q->whereDate('issue_date', '>=', $start))
            ->when($end, fn (Builder $q) => $q->whereDate('issue_date', '<=', $end));
    }
}
