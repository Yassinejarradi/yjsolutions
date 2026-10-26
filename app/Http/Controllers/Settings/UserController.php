<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Paramètres > Utilisateurs. Pas de rôles : tout utilisateur connecté a accès à tout.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('settings.users.index', ['users' => User::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('settings.users.form', ['user' => new User]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()->route('settings.users.index')->with('success', "L’utilisateur {$user->name} a été créé.");
    }

    public function edit(User $user): View
    {
        return view('settings.users.form', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Mot de passe laissé vide : on garde l'ancien.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('settings.users.index')->with('success', "L’utilisateur {$user->name} a été modifié.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('settings.users.index')->with('success', "L’utilisateur {$user->name} a été supprimé.");
    }
}
