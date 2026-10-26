# Projet : JY Solutions Gestion – application web de facturation

## Contexte
JY Solutions est une petite société de conseil (SARL) basée à Casablanca, créée par un consultant retraité qui travaille encore pour son ancienne entreprise et pour quelques autres clients. Aujourd'hui, les factures et les devis sont faits à la main dans Word. Je veux une application web professionnelle, simple à utiliser pour une personne non technique, pour gérer les clients, les devis, les factures et les paiements.

L'interface est **entièrement en français**. La monnaie est le **dirham (MAD)**. Les nombres s'affichent au format marocain/français (`12 345,67 MAD`) et les dates au format `JJ/MM/AAAA`.

Ce dossier est vide, à part `logo.png` (le logo de la société). Construis le projet ici.

## Façon de travailler
1. **Commence par un plan, sans écrire de code** : architecture, schéma de base de données, liste des écrans et ordre de développement. Pose-moi tes questions s'il manque quelque chose, puis attends ma validation.
2. Développe ensuite **phase par phase** (voir plus bas). À la fin de chaque phase, l'application doit tourner, les tests doivent passer, et tu me donnes un court résumé de ce qui est fait et de la façon de le tester.
3. Fais un commit git à la fin de chaque phase, avec un message clair.
4. Écris un `README.md` : installation, lancement, sauvegarde et restauration de la base, mise en production.

## Stack technique
- **Laravel 11** (PHP 8.2+), architecture MVC classique, code propre et commenté. Je connais Laravel et c'est moi qui maintiendrai l'application.
- **Blade + Livewire 3 + Tailwind CSS** pour l'interface (pas de SPA séparée).
- **MySQL 8** en production, **SQLite** acceptée en local.
- Authentification avec **Laravel Breeze**, sans inscription publique : les utilisateurs sont créés par l'administrateur.
- PDF avec **barryvdh/laravel-dompdf** (ou Browsershot si le rendu l'exige).
- Export Word avec **phpoffice/phpword** (fichier .docx compatible Word 2007).
- Tests avec **Pest** ou PHPUnit.
- Un `docker-compose.yml` optionnel pour lancer l'application facilement.

## Données de la société (à mettre dans un seeder et modifiables dans « Paramètres »)
- Raison sociale : JY SOLUTIONS
- Adresse : N° 276 Bd. Ibn Tachefine, 3ème Étage – Casablanca
- Téléphone : 0661 38 63 48
- Email : JYsolution66@gmail.com
- ICE : 004038025000064
- RC : 748069
- IF : 73385534
- TP (Patente) : 31905602
- CNSS : 7217245
- Banque, agence et RIB : à saisir dans les paramètres
- Logo : `logo.png`

## Identité visuelle
- Bleu principal `#0057D0`, rouge d'accent `#E30613`, texte `#1F2937`, gris `#6B7280`.
- Police Calibri, avec Carlito / Arial en secours.
- L'interface doit être sobre et professionnelle : sidebar à gauche, beaucoup d'espace, grandes polices lisibles (l'utilisateur principal a plus de 60 ans), boutons clairs avec libellés en toutes lettres (pas seulement des icônes).
- Responsive : l'application doit rester utilisable sur tablette et téléphone.

## Modèle du PDF (facture et devis)
Le PDF doit reproduire exactement cette mise en page, sur A4 avec des marges correctes (environ 1,8 cm, 3 cm en bas pour le pied de page) :
- **En-tête** : logo à gauche ; à droite le titre « FACTURE » ou « DEVIS » en gros et en bleu, puis « N° de facture : FAC2026-001 » (le numéro en rouge), puis « Solde exigible » (ou « Total TTC » pour un devis) avec le montant en gros.
- Un trait rouge horizontal sous l'en-tête.
- **Bloc client** à gauche : « Facturer à » (ou « Devis établi pour »), nom du client en gras, adresse, ICE du client.
- **Bloc dates** à droite :
  - facture : Date de facture, Date d'échéance, N° Bon de commande ;
  - devis : Date du devis, Valable jusqu'au, Réf. demande.
- **Tableau des lignes** avec en-tête bleu et texte blanc : #, Article & Description, Quantité, Prix Unitaire, Montant. Le tableau continue sur plusieurs pages si besoin, avec l'en-tête répété.
- **Totaux** alignés à droite : Total HT, TVA (taux), Total TTC, et une dernière ligne sur fond bleu « Solde exigible » (facture) ou « Net à payer TTC » (devis).
- Phrase : « Arrêtée la présente facture à la somme de : *[montant en lettres]* » (« Arrêté le présent devis… » pour un devis).
- Facture : ligne « Paiement par virement bancaire : Banque – Agence – RIB ».
- Devis : validité de l'offre, modalités de paiement, délai de réalisation, et deux zones de signature (« Bon pour accord – Client » et « JY Solutions – Cachet et signature »).
- **Pied de page sur chaque page**, une seule fois : trait rouge, puis raison sociale, adresse, ICE, RC, IF, TP, CNSS, téléphone, email, et « Page X / Y ».

## Phases de développement

### Phase 1 – Base
- Authentification, rôles `admin` et `utilisateur`.
- Page **Paramètres** : informations société, logo, coordonnées bancaires, taux de TVA par défaut, préfixes de numérotation, délai de paiement par défaut (30 jours), validité par défaut des devis (30 jours), conditions générales et mentions par défaut.
- Mise en page générale (sidebar, en-tête, messages de confirmation).

### Phase 2 – Clients et catalogue
- **Clients** : raison sociale, ICE, IF, RC, adresse, ville, email, téléphone, contact principal, notes, délai de paiement propre au client. Recherche et filtre. La fiche client affiche ses devis, factures, total facturé et reste à payer.
- **Catalogue de prestations** : désignation, description, unité (jour, heure, forfait, mois, unité), prix unitaire HT, taux de TVA. On peut choisir une prestation du catalogue dans une ligne de devis/facture, ou écrire une ligne libre.

### Phase 3 – Devis
- Création et modification d'un devis avec un **éditeur de lignes fluide** (point essentiel) :
  - bouton « + Ajouter une ligne » ;
  - suppression d'une ligne en un clic ;
  - réorganisation par glisser-déposer ou flèches haut/bas ;
  - duplication d'une ligne ;
  - calcul en direct du montant de chaque ligne, du Total HT, de la TVA et du TTC ;
  - montant en lettres en français, mis à jour automatiquement.
- Une remise optionnelle par ligne ou globale (en % ou en montant).
- Statuts : Brouillon, Envoyé, Accepté, Refusé, Expiré (automatique après la date de validité).
- Actions : télécharger le PDF, exporter en Word, dupliquer, **convertir en facture** en un clic.

### Phase 4 – Factures et paiements
- Même éditeur de lignes que pour les devis.
- Statuts : Brouillon, Émise, Partiellement payée, Payée, En retard (automatique après l'échéance).
- **Paiements** : enregistrer un ou plusieurs paiements par facture (date, montant, mode : virement, chèque, espèces, effet ; référence). Le solde exigible se met à jour.
- **Avoirs** : pour corriger une facture émise, on crée un avoir lié à la facture (numérotation propre AV2026-001), avec son propre PDF.
- PDF, export Word, envoi par email au client avec le PDF en pièce jointe (configuration SMTP dans `.env`).

### Phase 5 – Tableau de bord et exports
- **Tableau de bord** :
  - chiffre d'affaires du mois et de l'année ;
  - montant total impayé ;
  - liste des factures en retard ;
  - devis en attente de réponse ;
  - graphique du CA mensuel sur 12 mois ;
  - top clients.
- **Rapports** : liste des factures par période avec totaux HT / TVA / TTC (utile pour la déclaration de TVA et le comptable), export Excel (maatwebsite/excel) et CSV.
- Export ZIP de tous les PDF d'une période.
- Journal d'activité (qui a créé, modifié, émis, supprimé quoi et quand).
- Commande de **sauvegarde** de la base (`php artisan backup:run` avec spatie/laravel-backup), planifiable chaque jour.

## Règles métier importantes (à respecter et à tester)
- **Numérotation** : séquentielle, sans trou, par année et par type : `FAC2026-001`, `DEV2026-001`, `AV2026-001`. Le numéro définitif est attribué **au moment de l'émission**, pas au brouillon, dans une transaction avec verrouillage pour éviter les doublons.
- **Une facture émise ne peut plus être modifiée ni supprimée.** On ne peut corriger qu'avec un avoir. Seuls les brouillons sont modifiables et supprimables.
- **Montants** : utiliser des décimales exactes, jamais de float. Stocker en `DECIMAL(12,2)` ou en centimes (entiers) et arrondir à 2 décimales par ligne puis sur les totaux.
- **TVA** : taux disponibles 20 %, 14 %, 10 %, 7 % et 0 % (exonéré). Le taux peut être différent selon la ligne ; le récapitulatif affiche alors un total de TVA par taux.
- Les informations du client et de la société sont **copiées dans la facture au moment de l'émission** (snapshot), pour que modifier un client plus tard ne change pas une ancienne facture.
- **Montant en lettres en français**, avec les règles correctes : « vingt et un », « soixante et onze », « quatre-vingts », « quatre-vingt-un », « deux cents », « deux cent mille », « mille », « un million ». Le résultat inclut les dirhams et les centimes, par exemple : « Cent sept mille deux cent trente-cinq dirhams et trente-cinq centimes TTC ». Écrire des tests unitaires pour tous ces cas.
- Mentions obligatoires présentes sur la facture : identité et ICE du vendeur, IF, RC, TP, ICE du client, numéro, date, détail des prestations, HT, taux et montant de TVA, TTC.

## Qualité
- Validation des formulaires avec messages d'erreur clairs en français.
- Protection des routes (policies), CSRF, mots de passe hachés.
- Tests au minimum pour :
  - les calculs ;
  - le montant en lettres ;
  - la numérotation (y compris en cas de création simultanée) ;
  - la conversion devis → facture ;
  - les paiements et statuts ;
  - l'interdiction de modifier une facture émise.
- Seeders de démonstration (3 clients, 5 prestations, quelques devis et factures) activables séparément des données réelles.

## Mise en production
Prévois un guide de déploiement dans le README pour un VPS (Nginx + PHP-FPM + MySQL, HTTPS avec Let's Encrypt), et une variante pour un hébergement mutualisé si c'est possible.
