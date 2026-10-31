# Installer JY Solutions Gestion sur le PC de papa

Résultat : une icône « JY Solutions » sur le bureau. Un double-clic ouvre l'application dans une fenêtre à part (Edge, sans barre d'adresse). Rien d'autre n'est installé sur son PC : pas de XAMPP, Composer, Node ni Git.

Comment ça marche :
- PHP « portable » est dans `C:\JYSolutions\php`.
- La base de données est le fichier `C:\JYSolutions\database\database.sqlite`.
- Le petit serveur ne répond qu'à ce PC, à l'adresse `127.0.0.1:8765`.

Durée : environ 20 minutes.

---

## A. Sur mon PC : préparer la clé USB

- [ ] **1. Faire le paquet.** Le dépôt doit être à jour et les tests verts (`php artisan test`). Puis :

  ```powershell
  powershell -ExecutionPolicy Bypass -File deploy\build-package.ps1 -Zip
  ```

  Résultat : le dossier `dist\JYSolutions\` et `dist\JYSolutions-AAAA-MM-JJ.zip`. Le message « Des modifications ne sont pas commitées » veut dire que le paquet contient du code qui n'est pas encore commité.

- [ ] **2. Télécharger PHP 8.4.** Version **NTS x64**, en zip, sur <https://windows.php.net/download/>. La version testée est `php-8.4.27-1-nts-Win32-vs17-x64.zip` ; le détail est dans `dist\JYSolutions\php\README.txt`. Une 8.4 plus récente convient aussi. Pas la version « Thread Safe ».

- [ ] **3. Télécharger le programme Microsoft dont PHP a besoin.** « Visual C++ Redistributable » x64 : <https://aka.ms/vs/17/release/vc_redist.x64.exe>.

- [ ] **4. Copier sur la clé USB** :
  - `JYSolutions-AAAA-MM-JJ.zip` (ou le dossier `dist\JYSolutions`) ;
  - le zip de PHP ;
  - `vc_redist.x64.exe`.

## B. Sur le PC de papa

Toutes ces étapes se font **dans la session Windows de papa** : le raccourci du bureau et la sauvegarde automatique sont liés à cette session.

- [ ] **5. Vérifier Windows.** Windows 10 ou 11, 64 bits, avec Microsoft Edge (toujours présent).

- [ ] **6. Installer le programme Microsoft.** Double-cliquer sur `vc_redist.x64.exe`, puis « Installer ». S'il dit « déjà installé » ou propose « Réparer », on peut fermer.

- [ ] **7. Copier l'application dans `C:\JYSolutions`.**
  - Décompresser le zip pour obtenir `C:\JYSolutions\artisan`, `C:\JYSolutions\install.bat`, etc. Attention à ne pas créer `C:\JYSolutions\JYSolutions\...`.
  - Ne **pas** la mettre dans `Program Files` : il faut y écrire.
  - Ne **pas** la mettre dans OneDrive, le Bureau ou Documents : OneDrive synchronise la base pendant qu'elle est utilisée.

- [ ] **8. Ajouter PHP.**
  - Décompresser le zip de PHP **directement** dans `C:\JYSolutions\php`. Il faut obtenir `C:\JYSolutions\php\php.exe`, à côté de `php.ini` et `README.txt`, qui sont déjà là.
  - Ne pas remplacer `php.ini` : le zip de PHP n'en contient pas, donc rien n'est écrasé.

- [ ] **9. Lancer l'installation.** Double-cliquer sur `C:\JYSolutions\install.bat`.
  - Si Windows affiche « Windows a protégé votre ordinateur », cliquer sur « Informations complémentaires », puis « Exécuter quand même ».
  - Les étapes 1 à 6 se déroulent seules : réglage de PHP, `.env`, base de données, informations de la société, lien des fichiers, optimisation.
  - À l'étape 7, taper le **nom complet** de papa, puis son **adresse email** (c'est son identifiant de connexion), puis un **mot de passe** d'au moins 8 caractères. Le mot de passe ne s'affiche pas pendant la frappe, c'est normal.
  - À la fin, le message « Installation terminée » s'affiche et l'icône **JY Solutions** apparaît sur le bureau.
  - En cas d'erreur, le message dit quoi faire. Les deux cas les plus fréquents :
    - « php.exe introuvable » : revoir l'étape 8 ;
    - « Visual C++ » : revoir l'étape 6.

- [ ] **10. Activer la sauvegarde automatique.** Double-cliquer sur `C:\JYSolutions\install-backup-task.bat`.
  - Une sauvegarde se fera chaque jour à 12:00, et 2 minutes après chaque ouverture de session.
  - Si le PC était éteint à 12:00, elle se fait dès qu'il est rallumé.
  - Pas besoin d'être administrateur.

- [ ] **11. Premier essai.**
  1. Double-cliquer sur l'icône **JY Solutions**. La fenêtre s'ouvre en quelques secondes.
  2. Se connecter. Cocher « Rester connecté sur cet appareil » pour que papa n'ait pas à retaper son mot de passe.
  3. Dans **Paramètres**, vérifier :
     - les informations de la société, le logo et le RIB ;
     - les mentions des devis et factures.
  4. Créer un client et un devis d'essai, ouvrir le PDF, puis supprimer le brouillon d'essai.
  5. Fermer la fenêtre, puis double-cliquer de nouveau sur l'icône : elle doit se rouvrir aussitôt, sans démarrer un second serveur.

- [ ] **12. Premier essai de sauvegarde.**
  1. Double-cliquer sur `C:\JYSolutions\backup.bat`. Il affiche « Sauvegarde créée : ... ».
  2. Vérifier que le fichier `sauvegarde-AAAA-MM-JJ-HHMMSS.zip` existe dans le dossier des sauvegardes :
     - si OneDrive est configuré : `OneDrive\JY Solutions Backups` (une copie part ainsi automatiquement dans le cloud) ;
     - sinon : `Documents\JY Solutions Backups`.
  3. Les mêmes sauvegardes apparaissent dans l'application, sous **Paramètres › Sauvegardes**.

- [ ] **13. Confort (facultatif).**
  - Clic droit sur l'icône du bureau, puis « Épingler à la barre des tâches ».
  - Dans l'application, **Mon profil › Taille du texte** permet de choisir « Grand ».

## C. Au quotidien

- **Ouvrir :** double-clic sur l'icône. Fermer la fenêtre ne coupe pas le serveur : il reste discret en arrière-plan et s'arrête quand on éteint le PC.
- **Arrêter le serveur :** rarement utile. Double-cliquer sur `C:\JYSolutions\stop.bat` : il n'arrête que le PHP qui écoute sur le port 8765.
- **Changer le dossier des sauvegardes :** ouvrir `C:\JYSolutions\backup.bat` avec le Bloc-notes et modifier la ligne `set "DOSSIER="`, en haut du fichier. Par exemple : `set "DOSSIER=D:\Sauvegardes JY Solutions"`.
  - Le nombre de sauvegardes gardées se règle à la ligne `set "GARDER=30"`.
  - Pour que **Paramètres › Sauvegardes** affiche le nouveau dossier, mettre le même chemin dans la ligne `BACKUP_PATH='...'` de `C:\JYSolutions\.env`. Lancer ensuite `php\php.exe artisan optimize` depuis le dossier `C:\JYSolutions`.

## D. Mettre à jour (nouvelle version)

1. Sur mon PC : `deploy\build-package.ps1 -Zip`, puis copier le zip sur la clé USB.
2. Sur le PC de papa : décompresser le zip dans un **autre** dossier, par exemple `C:\JYSolutions-nouveau`.
3. Double-cliquer sur `C:\JYSolutions-nouveau\update.bat`, celui du **nouveau** paquet, puis valider le dossier proposé, `C:\JYSolutions`. Le script :
   1. arrête l'application ;
   2. fait une **sauvegarde** ;
   3. remplace le code ;
   4. met à jour la base ;
   5. optimise.

   Il ne touche **jamais** à `.env`, à `database\database.sqlite`, à `storage\` (logos, journaux) ni à `php\`.
4. Double-cliquer sur l'icône pour vérifier, puis supprimer `C:\JYSolutions-nouveau`.

## E. En cas de problème

| Problème | Solution |
|---|---|
| « JY Solutions n'a pas pu démarrer » | Redémarrer le PC et réessayer. Sinon, ouvrir une invite de commandes dans `C:\JYSolutions` et taper `php\php.exe -v` : une erreur Visual C++ renvoie à l'étape 6. |
| La fenêtre affiche « Server Error » | Lire la fin du dernier fichier de `C:\JYSolutions\storage\logs\`. Puis lancer `php\php.exe artisan optimize` depuis `C:\JYSolutions`. |
| Mot de passe oublié | Ouvrir une invite de commandes dans `C:\JYSolutions` et taper `php\php.exe artisan user:password adresse@email.ma`. |
| Ajouter un compte | Dans `C:\JYSolutions` : `php\php.exe artisan user:create`. |
| Le port 8765 est pris par un autre programme | `stop.bat` le signale sans rien arrêter. Changer `PORT` dans `JY Solutions.vbs`, `APP_URL` dans `.env` et `-Port` dans `stop.bat`, puis lancer `php\php.exe artisan optimize`. |
| Windows demande l'autorisation du pare-feu pour PHP | « Annuler » suffit : l'application n'est utilisée que depuis ce PC. |

**Restaurer une sauvegarde** (PC remplacé, base abîmée) :
1. Réinstaller selon la partie B.
2. Lancer `stop.bat`.
3. Ouvrir le `.zip` de la sauvegarde et remplacer `C:\JYSolutions\database\database.sqlite` par le `database.sqlite` du zip.
4. Copier le contenu de son dossier `fichiers` dans `C:\JYSolutions\storage\app\private` (logos), et celui de `public`, s'il existe, dans `C:\JYSolutions\storage\app\public`.
5. Double-cliquer sur l'icône.

Le fichier `LISEZMOI.txt` de chaque sauvegarde reprend ces étapes.

## F. Ce que contient le paquet

| Fichier | Rôle |
|---|---|
| `install.bat` | Installation, une seule fois. Refuse de tourner si l'application est déjà installée (`.env` présent). |
| `JY Solutions.vbs` | Le lanceur ouvert par l'icône. Démarre `php artisan serve` caché si le port 8765 ne répond pas (15 s au plus), puis ouvre Edge `--app`. Un verrou évite deux serveurs si l'on double-clique plusieurs fois. |
| `stop.bat` | Arrête le serveur : le `php.exe` qui écoute sur le port 8765 et son `artisan serve`, rien d'autre. |
| `backup.bat` | Sauvegarde en `.zip` daté : la base, copiée par SQLite (`VACUUM INTO`) même si l'application est ouverte, plus `storage\app\private` (logos) et `storage\app\public`. Garde les 30 dernières. |
| `install-backup-task.bat` | Crée la tâche planifiée « JY Solutions - Sauvegarde ». `/supprimer` la retire. |
| `update.bat` | Mise à jour, depuis le nouveau paquet (voir D). |
| `.env.production` | Réglages de production. `install.bat` le **déplace** en `.env`. Il ne doit jamais rester à côté de `.env` : avec `APP_ENV=production`, Laravel le lirait à la place de `.env` en mettant la configuration en cache, et l'application se retrouverait sans clé de chiffrement. |
| `php\README.txt`, `php\php.ini` | Version de PHP à télécharger et extensions activées. |
| `outils\` | Scripts appelés par les fichiers ci-dessus : réglage de PHP, icône et raccourci, arrêt, tâche planifiée, lancement caché. |
| `VERSION.txt` | Date du paquet et commit. |
