<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crée un compte utilisateur depuis le terminal (utile pour le tout premier compte).
 *
 *   php artisan user:create
 *   php artisan user:create --name="Jamal Y." --email=jamal@example.com
 */
class CreateUser extends Command
{
    protected $signature = 'user:create {--name= : Nom complet} {--email= : Adresse email (identifiant de connexion)}';

    protected $description = 'Créer un utilisateur';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nom complet', required: true);
        $email = mb_strtolower($this->option('email') ?: text('Adresse email', required: true));
        $plain = password('Mot de passe (8 caractères minimum)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $plain],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::min(8)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create(['name' => $name, 'email' => $email, 'password' => $plain]);

        $this->info("Utilisateur créé : {$email}");

        return self::SUCCESS;
    }
}
