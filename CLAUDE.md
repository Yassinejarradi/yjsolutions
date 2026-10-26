# JY Solutions Gestion – mémoire du projet

Application de devis et factures pour JY SOLUTIONS, petite société de conseil (SARL, Casablanca).
Utilisateur principal : plus de 60 ans, non technique → grandes polices, libellés en toutes lettres, peu d'étapes.
Interface **entièrement en français**, montants en **MAD**, format `12 345,67`, dates `JJ/MM/AAAA`.

Référence : [docs/PLAN.md](docs/PLAN.md) (périmètre validé, phases). Le cahier des charges [docs/PROMPT_JY_Solutions_App.md](docs/PROMPT_JY_Solutions_App.md) est l'origine, mais **en cas de contradiction, PLAN.md l'emporte**.

## Stack et commandes

Laravel 12, Livewire 3, Blade, Tailwind 3, Pest, brick/math. SQLite en local, MySQL 8 en production.

```bash
php artisan serve                          # http://localhost:8000
php artisan test
php artisan user:create                    # --name= --email=
php artisan user:password adresse@email.ma
```

## Périmètre simplifié

- Connexion seule, pas de rôles.
- Pas d'email du tout.
- Pas d'avoirs : une facture émise peut seulement être « Annulée ».
- Pas de catalogue : la désignation propose en auto-complétion les lignes déjà saisies.
- Pas de journal d'activité, pas d'exports Excel ou ZIP, pas de Docker.
- Un seul taux de TVA par document + remise globale en %.
- Paiements simples : payée / partiellement payée.

## Éditeur « comme Canva »

Le document A4 s'édite directement à l'écran, sur le modèle de [docs/prototype.html](docs/prototype.html) : on clique sur un texte pour le modifier, lignes ajoutées/déplacées/supprimées sur la feuille, totaux et montant en lettres en direct.

## Règles métier

- Numérotation sans trou (`FAC2026-001`, `DEV2026-001`), attribuée à l'émission, en transaction avec verrou.
- Facture émise non modifiable (ni supprimable).
- Montants en centimes (entiers), jamais de float ; un seul point de calcul : `App\Support\DocumentCalculator`.
- Montant en lettres (`App\Support\AmountInWords`) couvert par des tests.
- PDF des brouillons avec filigrane « BROUILLON » (« ANNULÉE » pour une facture annulée).

## Règles de travail

- Travailler phase par phase. À la fin de chaque phase : tests, captures ordinateur et téléphone, commit, push, résumé.
- Ne pas commencer une phase sans le feu vert de l'utilisateur.
- Pour arrêter un serveur de test : n'arrêter que le processus lancé soi-même (par son PID), **jamais tous les php.exe**.
