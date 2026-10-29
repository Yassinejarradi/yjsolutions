<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * « Mon profil » : nom, email, mot de passe et taille du texte de l'utilisateur connecté.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('profile.edit')->with('success', 'Votre profil a été enregistré.');
    }

    public function updateTextSize(Request $request): RedirectResponse
    {
        $validated = $request->validate(['text_size' => ['required', Rule::in(array_keys(User::TEXT_SIZES))]]);
        $request->user()->update($validated);

        return redirect()->route('profile.edit')->with('success', 'La taille du texte a été changée.');
    }
}
