<?php

namespace App\Livewire;

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Quote;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des devis : recherche, filtres par statut, client et période. Filtres gardés dans l'adresse.
 */
class QuoteList extends Component
{
    use WithPagination;

    /** Périodes proposées. « dates » affiche les champs Du / Au. */
    public const PERIODS = [
        '' => 'Toutes les dates',
        'mois' => 'Ce mois-ci',
        'mois-dernier' => 'Le mois dernier',
        'annee' => 'Cette année',
        'annee-derniere' => 'L’année dernière',
        'dates' => 'Entre deux dates…',
    ];

    #[Url(as: 'recherche', except: '')]
    public string $search = '';

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(as: 'client', except: '')]
    public string $clientId = '';

    #[Url(as: 'periode', except: '')]
    public string $period = '';

    #[Url(as: 'du', except: '')]
    public string $from = '';

    #[Url(as: 'au', except: '')]
    public string $to = '';

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

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function periodRange(): array
    {
        $now = today();

        return match ($this->period) {
            'mois' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'mois-dernier' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'annee' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'annee-derniere' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'dates' => [self::parseDate($this->from), self::parseDate($this->to)],
            default => [null, null],
        };
    }

    private static function parseDate(string $value): ?Carbon
    {
        $date = DateTime::createFromFormat('!d/m/Y', trim($value));

        return $date && ! DateTime::getLastErrors() ? Carbon::instance($date) : null;
    }
}
