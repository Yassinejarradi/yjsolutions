<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;

/**
 * Réinitialise le mot de passe d'un utilisateur (il n'y a pas d'envoi d'email).
 *
 *   php artisan user:password jamal@example.com
 */
class ResetUserPassword extends Command
{
    protected $signature = 'user:password {email : Adresse email de l’utilisateur}';

    protected $description = 'Réinitialiser le mot de passe d’un utilisateur';

    public function handle(): int
    {
        $user = User::where('email', mb_strtolower($this->argument('email')))->first();

        if (! $user) {
            $this->error('Aucun utilisateur avec cette adresse email.');

            return self::FAILURE;
        }

        $plain = password('Nouveau mot de passe (8 caractères minimum)', required: true);

        $validator = Validator::make(['password' => $plain], ['password' => ['required', Password::min(8)]]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        $user->update(['password' => $plain]);

        $this->info("Mot de passe modifié pour {$user->email}.");

        return self::SUCCESS;
    }
}
