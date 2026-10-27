# JY Solutions Gestion – mémoire du projet

Application de devis et factures pour JY SOLUTIONS, petite société de conseil (SARL, Casablanca).
Utilisateur principal : plus de 60 ans, non technique → grandes polices, libellés en toutes lettres, peu d'étapes.
Interface **entièrement en français**, montants en **MAD**, format `12 345,67`, dates `JJ/MM/AAAA`.

Référence : [docs/PLAN.md](docs/PLAN.md) (périmètre validé, phases). Le cahier des charges [docs/PROMPT_JY_Solutions_App.md](docs/PROMPT_JY_Solutions_App.md) est l'origine, mais **en cas de contradiction, PLAN.md l'emporte**.

## Stack et commandes

Laravel 12, Livewire 4, Blade, Tailwind 3, Pest, brick/math. SQLite en local, MySQL 8 en production.
Design : police Inter (paquet npm, aucun appel externe), couleurs et ombres dans `tailwind.config.js`, composants `.btn-*`, `.card`, `.field-*` dans `resources/css/app.css`, et `x-avatar`, `x-badge`, `x-flash`. Les documents (feuille A4, PDF, Word) gardent Calibri / Carlito.
Composants Livewire **en classe** (`app/Livewire` + `resources/views/livewire`, réglé dans `config/livewire.php`), pas en fichier unique.
Après avoir ajouté des classes Tailwind dans une vue : `npm run build`, sinon le CSS publié ne les contient pas. Tailwind ne lit en PHP que `app/Enums` (couleurs des statuts).

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

- `QuoteEditor` et `InvoiceEditor` partagent tout : traits `EditsDocumentHeader` (client, dates), `EditsDocumentLines` (lignes, calculs, auto-complétion), `EditsDocumentLayout`, et les vues `livewire/partials/editor/*`. Modifier le partial, pas l'un des deux éditeurs. JS Alpine dans `resources/js/document-editor.js`.
- Factures : statuts enregistrés brouillon / émise / annulée ; payée, partielle et en retard sont calculés (`Invoice::displayStatus()` et scope `withDisplayStatus`). `amount_paid` ne change que par `recordPayment()` / `removePayment()`.
- Écran en lecture, PDF et Word lisent tous `App\Support\DocumentSheet` : ne jamais formater un montant ailleurs.
- Mise en page (logo, textes, blocs, société) : `App\Support\DocumentLayout` + trait `EditsDocumentLayout` + panneau `livewire/partials/layout-panel`. Tout nouveau texte imprimé doit passer par `labels`, sinon il n'est pas modifiable.
- Les lignes ont `wire:key` = clé + position : sinon, après un déplacement, les champs liés par position affichent l'ancienne valeur.
- PhpWord : échappement activé et largeurs en twips entiers, sinon Word refuse d'ouvrir le fichier.

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
- Captures : base SQLite jetable (variables `DB_CONNECTION`/`DB_DATABASE`), serveur `php -S` lancé **depuis `public/`** avec le routeur `vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`. Captures ordinateur, tablette et téléphone.
- `php artisan tinker fichier.php` attend une saisie : toujours ajouter `< /dev/null`.
- `npm run build` peut rester bloqué s'il est interrompu : vérifier les `node.exe`/`esbuild.exe` restants et n'arrêter que les siens (par PID). `node node_modules/vite/bin/vite.js build` évite l'enveloppe npm.
- Vérifier un .docx en l'ouvrant avec Word (COM PowerShell `Word.Application`, puis `Quit()`).
