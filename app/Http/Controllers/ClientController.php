<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Clients : la liste (composant Livewire ClientList), la fiche, le formulaire,
 * l'archivage et la suppression (seulement sans document).
 */
class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index');
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client, 'defaultTerms' => Setting::current()->payment_terms_days]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->clientData());

        return redirect()->route('clients.show', $client)->with('success', "Le client {$client->name} a été créé.");
    }

    public function show(Client $client): View
    {
        return view('clients.show', [
            'client' => $client,
            'quotes' => $client->quotes()->orderByDesc('issue_date')->orderByDesc('id')->get(),
            'invoices' => $client->invoices()->orderByDesc('issue_date')->orderByDesc('id')->get(),
        ]);
    }

    public function edit(Client $client): View
    {
        return view('clients.form', ['client' => $client, 'defaultTerms' => Setting::current()->payment_terms_days]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->clientData());

        return redirect()->route('clients.show', $client)->with('success', "Le client {$client->name} a été modifié.");
    }

    public function archive(Client $client): RedirectResponse
    {
        $client->archive();

        return redirect()->route('clients.show', $client)
            ->with('success', "Le client {$client->name} a été archivé. Il ne sera plus proposé pour les nouveaux documents.");
    }

    public function restore(Client $client): RedirectResponse
    {
        $client->restore();

        return redirect()->route('clients.show', $client)->with('success', "Le client {$client->name} est de nouveau actif.");
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->hasDocuments()) {
            return redirect()->route('clients.show', $client)
                ->with('error', "Le client {$client->name} a des devis ou des factures : il ne peut pas être supprimé, seulement archivé.");
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', "Le client {$client->name} a été supprimé.");
    }
}
