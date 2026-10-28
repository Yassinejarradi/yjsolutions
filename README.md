# JY Solutions Gestion

Application web de devis et de facturation pour **JY SOLUTIONS** (SARL, Casablanca).
Interface en français, montants en dirhams (MAD).

- **Stack** : Laravel 12 (PHP 8.2+), Blade, Livewire 4, Tailwind CSS 3, Pest.
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
- la numérotation des devis (sans trou, attribuée à l'envoi, remise à 001 chaque année) ;
- l'éditeur de devis (lignes, auto-complétion, sauvegarde, envoi) ;
- la cohérence écran = PDF = Word (mêmes montants et mêmes textes) ;
- les factures (numérotation, date jamais antérieure à la dernière facture émise, paiements, retard, annulation, conversion d'un devis) ;
- le chiffre d'affaires du tableau de bord, les sauvegardes et les données de démonstration ;
- les clients, les paramètres, les utilisateurs et l'authentification.

## Devis et factures : PDF et Word

- PDF : [dompdf](https://github.com/dompdf/dompdf), vue [`resources/views/pdf/document.blade.php`](resources/views/pdf/document.blade.php), police Carlito (mêmes dimensions que Calibri, licence libre OFL) dans `resources/fonts/carlito/`. Le cache des polices est dans `storage/fonts/` (doit être accessible en écriture).
- Word : [PhpWord](https://github.com/PHPOffice/PHPWord), [`App\Support\Word\DocumentWord`](app/Support/Word/DocumentWord.php). L'extension PHP `gd` sert à dessiner le filigrane « BROUILLON ».

## Règles de calcul

Tous les montants sont stockés **en centimes (entiers)**. Les calculs passent par `brick/math`, jamais par des nombres à virgule flottante.
Le seul point de calcul est [`App\Support\DocumentCalculator`](app/Support/DocumentCalculator.php) :

1. montant de ligne = quantité × prix unitaire, arrondi au centime ;
2. sous-total = somme des lignes ;
3. remise globale (%) arrondie ; total HT = sous-total − remise ;
4. TVA = total HT × taux (20, 14, 10, 7 ou 0 %), arrondie ; TTC = HT + TVA.

## Données de démonstration

Pour une présentation ou des essais, **sur une base vide uniquement** (refusé en production ou si la base contient déjà des devis ou factures) :

```bash
php artisan db:seed --class=DemoSeeder
```

Six clients, une vingtaine de factures réparties sur les 12 derniers mois (payées, partiellement payées, en retard, une annulée, un brouillon) et des devis dans tous les statuts. Compte : `demo@jysolutions.ma` / `motdepasse`.

## Sauvegardes

`php artisan backup:run` crée `storage/app/backups/sauvegarde-AAAA-MM-JJ-HHMMSS.zip` contenant :

- la base : copie du fichier SQLite, ou export SQL complet pour MySQL (fait en PHP, `mysqldump` n'est pas nécessaire) ;
- les logos téléversés (`storage/app/private`) ;
- un `LISEZMOI.txt` avec les étapes de restauration.

Elle est **planifiée chaque jour à 13 h** (`routes/console.php`), à condition que le planificateur Laravel tourne (voir ci-dessous). Les 30 plus récentes sont gardées. Dans l'application : **Paramètres › Sauvegardes** pour en faire une tout de suite et les télécharger.

Réglages facultatifs dans `.env` : `BACKUP_PATH` (dossier), `BACKUP_KEEP` (nombre gardé), `BACKUP_TIME` (heure, ex. `13:00`).

> Une sauvegarde qui reste sur la même machine ne protège pas d'une panne de disque : en télécharger une régulièrement, ou faire pointer `BACKUP_PATH` vers un dossier OneDrive / Google Drive.

**Restaurer** : arrêter l'application, puis
- SQLite : remplacer `database/database.sqlite` par le `database.sqlite` du zip ;
- MySQL : `mysql -u UTILISATEUR -p NOM_BASE < database.sql` ;
- copier le dossier `fichiers/` du zip dans `storage/app/private/`.

## Mise en production

Quelle que soit la solution, la même base :

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build            # ou construire en local et envoyer public/build
cp .env.example .env               # puis régler APP_ENV=production, APP_DEBUG=false, APP_URL=https://…
php artisan key:generate
php artisan migrate --force --seed
php artisan user:create
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Pour deux utilisateurs, **SQLite suffit** (un seul fichier, sauvegarde simple). MySQL reste possible : `DB_CONNECTION=mysql` et les variables `DB_*`.

Après chaque mise à jour du code : `git pull`, `composer install --no-dev`, `npm run build`, `php artisan migrate --force`, puis les trois `…:cache`.

### A. Sur l'ordinateur du bureau (Windows, gratuit)

Utilisation depuis ce seul ordinateur (ou le réseau local).

1. Installer PHP 8.2+ (extensions `pdo_sqlite`, `mbstring`, `gd`, `zip`, `fileinfo`, `intl`) et Composer, puis faire l'installation ci-dessus.
2. Lancer l'application à l'ouverture de session : Planificateur de tâches Windows › Créer une tâche › Déclencheur « À l'ouverture de session » › Action : programme `php`, arguments `artisan serve --port=8000`, « Commencer dans » : le dossier de l'application.
3. Sauvegarde : seconde tâche, déclencheur quotidien (ex. 13 h, « Exécuter dès que possible si une exécution a été manquée »), action `php artisan backup:run`. Régler `BACKUP_PATH` vers un dossier OneDrive ou Google Drive.
4. Mettre un raccourci vers `http://localhost:8000` sur le bureau.

### B. Serveur VPS (Ubuntu 24.04, Nginx, PHP-FPM)

```bash
sudo apt install nginx php8.3-fpm php8.3-{sqlite3,mysql,mbstring,xml,gd,zip,intl,curl} composer unzip git
sudo git clone <dépôt> /var/www/jysolutions && cd /var/www/jysolutions
# … installation de base ci-dessus …
sudo chown -R www-data:www-data storage bootstrap/cache database
```

Nginx (`/etc/nginx/sites-available/jysolutions`) :

```nginx
server {
    listen 80;
    server_name gestion.exemple.ma;
    root /var/www/jysolutions/public;
    index index.php;
    client_max_body_size 5M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/jysolutions /etc/nginx/sites-enabled/ && sudo nginx -t && sudo systemctl reload nginx
sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d gestion.exemple.ma   # HTTPS
```

Planificateur (sauvegarde quotidienne) : `sudo crontab -u www-data -e`, puis

```
* * * * * cd /var/www/jysolutions && php artisan schedule:run >> /dev/null 2>&1
```

### C. Hébergement mutualisé (cPanel, Hostinger…)

Il faut PHP 8.2+, un accès SSH (pour Composer et `artisan`) et les tâches cron.

1. Envoyer le projet **en dehors** de `public_html` (ex. `~/jysolutions`), faire l'installation de base par SSH.
2. Faire pointer le domaine (ou sous-domaine) vers `~/jysolutions/public`. Si ce n'est pas possible : remplacer `public_html` par un lien symbolique, `ln -s ~/jysolutions/public ~/public_html`.
3. Activer le certificat HTTPS gratuit dans le panneau.
4. Tâche cron toutes les minutes : `cd ~/jysolutions && php artisan schedule:run >> /dev/null 2>&1`.

### Vérifications après la mise en ligne

- `APP_DEBUG=false` et adresse en `https://`.
- Se connecter, créer un devis, télécharger le PDF et le Word.
- Paramètres › Sauvegardes › « Faire une sauvegarde maintenant », puis télécharger le fichier.
- Le lendemain : vérifier qu'une sauvegarde automatique est apparue.
