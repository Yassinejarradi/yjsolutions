PHP POUR JY SOLUTIONS GESTION
=============================

Ce dossier doit contenir PHP « portable » (aucune installation, rien d'autre à mettre sur le PC).

1. Version à télécharger

   PHP 8.4, version « Non Thread Safe » (NTS), 64 bits (x64), fichier .zip, sur
   https://windows.php.net/download/

   Fichier testé : php-8.4.27-1-nts-Win32-vs17-x64.zip
   https://windows.php.net/downloads/releases/php-8.4.27-1-nts-Win32-vs17-x64.zip
   SHA-256 : dbae154a0a89791f36c3d48e68bae797878333e7d6cb5721e74617de59b63ab7
   Une version 8.4 plus récente (8.4.28, 8.4.29...) convient aussi. Pas la version « Thread Safe ».

   Pourquoi 8.4 et pas 8.2 (la version de XAMPP sur le PC de développement) :
   - même version majeure (8), et l'application est testée avec les deux ;
   - avec PHP 8.4, Laravel ouvre les transactions SQLite en mode IMMEDIATE : l'attribution des numéros
     de facture ne peut jamais échouer avec « database is locked » (avec 8.2 elle peut échouer, sans
     jamais créer de doublon ni de trou) ;
   - PHP 8.2 n'a plus de correctifs de sécurité après le 31/12/2026, PHP 8.4 en a jusqu'à fin 2028.

2. Où le décompresser

   Décompresser le contenu du zip DIRECTEMENT dans ce dossier php\ :
   php\php.exe doit exister (pas php\php-8.4.27...\php.exe).
   Garder le php.ini déjà présent ici (le zip de PHP n'en contient pas, il ne l'écrase donc pas).

3. Programme Microsoft nécessaire

   PHP a besoin de « Microsoft Visual C++ Redistributable 2015-2022 » 64 bits (x64) :
   https://aka.ms/vs/17/release/vc_redist.x64.exe
   Sans lui, php.exe ne démarre pas (erreur VCRUNTIME140.dll ou code -1073741515).

4. Extensions activées dans php.ini (déjà fait)

   extension=pdo_sqlite   base de données SQLite
   extension=sqlite3      base de données SQLite
   extension=mbstring     textes avec accents (Laravel, dompdf, PhpWord)
   extension=fileinfo     type des fichiers envoyés (logo)
   extension=openssl      chiffrement des sessions et des cookies (Laravel)
   extension=gd           images dans les PDF (dompdf) et les fichiers Word (PhpWord)
   extension=zip          fichiers Word .docx (PhpWord) et sauvegardes .zip
   extension=intl         formats français (Laravel)
   extension=curl         utilisé par certaines bibliothèques de Laravel
   zend_extension=opcache rapidité

   Déjà incluses dans php.exe, rien à activer : dom, xml, xmlwriter, xmlreader, libxml, simplexml
   (dompdf et PhpWord), ctype, tokenizer, iconv, bcmath, filter, session.

   install.bat écrit le chemin complet du dossier php\ext dans php.ini (ligne extension_dir),
   puis vérifie que toutes ces extensions se chargent.

5. Vérifier à la main (facultatif)

   php\php.exe -v      affiche « PHP 8.4... (cli) ... NTS Visual C++ 2022 x64 »
   php\php.exe -m      liste les extensions chargées
