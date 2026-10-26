# JY Solutions Gestion

Application web de devis et de facturation pour **JY SOLUTIONS** (SARL, Casablanca).
Interface en français, montants en dirhams (MAD).

- **Stack** : Laravel 12 (PHP 8.2+), Blade, Livewire 3, Tailwind CSS 3, Pest.
- **Base de données** : SQLite en local, MySQL 8 en production.
- **Plan et décisions** : [docs/PLAN.md](docs/PLAN.md). Le prototype d'origine de l'éditeur est dans [docs/prototype.html](docs/prototype.html).

## Installation (poste de développement)

Prérequis : PHP 8.2+ (extensions `pdo_sqlite`, `pdo_mysql`, `mbstring`, `gd`, `zip`), Composer 2, Node.js 20+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite        # Windows : type nul > database\database.sqlite
php artisan migrate --seed            # crée les tables et les informations de la société
php artisan user:create               # crée le premier compte (nom, email, mot de passe)
npm run build
```

## Lancement

```bash
php artisan serve
```

Puis ouvrir http://localhost:8000 et se connecter avec le compte créé.

Pendant le développement, `composer run dev` lance en même temps le serveur, la file d'attente, les logs et Vite (rechargement automatique des styles).

## Comptes utilisateurs

Il n'y a ni inscription publique ni email. Tous les comptes ont accès à tout.

| Besoin | Comment |
|---|---|
| Créer un compte | Paramètres › Utilisateurs › « + Nouvel utilisateur », ou `php artisan user:create` |
| Mot de passe oublié | `php artisan user:password adresse@email.ma` (sur le serveur) |
| Changer son propre mot de passe | Mon profil |

## Données de la société

`php artisan db:seed` (ou `migrate --seed`) remplit les informations de JY Solutions : raison sociale, adresse, ICE, RC, IF, TP, CNSS.
Le seeder ne remplace que les champs vides : il peut être relancé sans écraser ce qui a été modifié dans **Paramètres**.

La banque, l'agence et le RIB se saisissent dans Paramètres › Société. Le logo par défaut est `public/logo.png`. Un logo téléversé est rangé dans `storage/app/private/settings/`.

## Tests

```bash
php artisan test        # ou ./vendor/bin/pest
```

Les tests couvrent notamment :

- le montant en lettres (« vingt et un », « quatre-vingts », « deux cent mille », « un million de dirhams »…) ;
- les calculs exacts en centimes (arrondi par ligne, remise, TVA) ;
- les paramètres, les utilisateurs et l'authentification.

## Règles de calcul

Tous les montants sont stockés **en centimes (entiers)**. Les calculs passent par `brick/math`, jamais par des nombres à virgule flottante.
Le seul point de calcul est [`App\Support\DocumentCalculator`](app/Support/DocumentCalculator.php) :

1. montant de ligne = quantité × prix unitaire, arrondi au centime ;
2. sous-total = somme des lignes ;
3. remise globale (%) arrondie ; total HT = sous-total − remise ;
4. TVA = total HT × taux (20, 14, 10, 7 ou 0 %), arrondie ; TTC = HT + TVA.

## Sauvegarde et restauration de la base

*La commande de sauvegarde automatique (`php artisan backup:run`) arrive en phase 5. En attendant :*

- **SQLite (local)** : copier le fichier `database/database.sqlite`.
- **MySQL** : `mysqldump -u UTILISATEUR -p NOM_BASE > sauvegarde.sql`, puis pour restaurer : `mysql -u UTILISATEUR -p NOM_BASE < sauvegarde.sql`.

## Mise en production

*Le guide complet (VPS Nginx + PHP-FPM + MySQL + HTTPS, et hébergement mutualisé) sera rédigé en phase 5.*
