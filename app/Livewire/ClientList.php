<?php

namespace App\Livewire;

use App\Models\Client;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des clients : recherche en direct, filtre Actifs / Archivés, tri par nom.
 * Les choix sont gardés dans l'adresse (?recherche=…&statut=archives&tri=desc).
 */
class ClientList extends Component
{
    use WithPagination;

    #[Url(as: 'recherche', except: '')]
    public string $search = '';

    /** « actifs » ou « archives » */
    #[Url(as: 'statut', except: 'actifs')]
    public string $status = 'actifs';

    /** « asc » (A → Z) ou « desc » (Z → A) */
    #[Url(as: 'tri', except: 'asc')]
    public string $sort = 'asc';

    public function updated(string $property): void
    {
        // Nouvelle recherche ou nouveau filtre : retour à la première page.
        $this->resetPage();
    }

    public function toggleSort(): void
    {
        $this->sort = $this->sort === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function render(): View
    {
        $archived = $this->status === 'archives';

        $clients = Client::query()
            ->when($archived, fn ($q) => $q->archived(), fn ($q) => $q->active())
            ->search($this->search)
            ->orderBy('name', $this->sort === 'desc' ? 'desc' : 'asc')
            ->paginate(25);

        return view('livewire.client-list', [
            'clients' => $clients,
            'archived' => $archived,
            'archivedCount' => Client::archived()->count(),
        ]);
    }
}
