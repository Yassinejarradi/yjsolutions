# Plan – JY Solutions Gestion

Plan validé le 26/10/2026, après réduction du périmètre. Le cahier des charges d'origine est dans [PROMPT_JY_Solutions_App.md](PROMPT_JY_Solutions_App.md).

## Périmètre

**Inclus**
- Connexion seule (pas d'inscription publique).
- Tableau de bord.
- Clients : liste, fiche, archivage.
- Devis et factures, avec un éditeur visuel « comme Canva ».
- PDF et export Word.
- Paramètres : société, logo, banque, numérotation, TVA par défaut, utilisateurs.
- Paiements simples : « Marquer comme payée » ou « Paiement partiel ».

**Exclu**
- Email : pas d'envoi au client ni de SMTP. Un mot de passe oublié se réinitialise avec `php artisan user:password`.
- Rôles : tout utilisateur connecté a accès à tout.
- Journal d'activité, avoirs, catalogue de prestations, exports Excel et ZIP, Docker.

## Décisions

| Sujet | Décision |
|---|---|
| Version | Laravel 12 et Livewire 4 (composants en classe : `app/Livewire` + `resources/views/livewire`) |
| Montants | Centimes (BIGINT), calculs avec `brick/math`, arrondi au plus proche (0,5 vers le haut) par ligne puis sur les totaux |
| TVA | Un seul taux par document (20 % par défaut ; 14, 10, 7 et 0 % possibles) |
| Remise | Globale, en %, facultative, appliquée avant la TVA |
| Numérotation | `FAC2026-001` et `DEV2026-001`, séquentielle sans trou, par année et par type. Attribuée à l'émission, dans une transaction avec verrou (table `sequences`) |
| Date de facture | Modifiable, mais jamais antérieure à la dernière facture émise |
| Brouillons | Sans numéro. Leur PDF porte le filigrane « BROUILLON » |
| Facture émise | Plus modifiable ni supprimable. Peut être « Annulée » : elle garde son numéro et son PDF porte le filigrane « ANNULÉE » |
| Snapshot | Les informations du client et de la société sont copiées dans le document à l'émission |
| Statuts automatiques | « En retard » et « Expiré » sont calculés à partir des dates (pas de tâche planifiée nécessaire) |
| Devis → facture | Possible depuis un devis Envoyé ou Accepté. Le devis passe à Accepté et une facture brouillon est créée |
| Clients | Un client qui a des documents est archivé, jamais supprimé |
| Auto-complétion | La désignation d'une ligne propose les lignes déjà saisies (à la place d'un catalogue) |
| Libellé du devis | « Délai de réalisation » (et non « Délai de livraison » du prototype) : ce sont des prestations de conseil |
| Délai de paiement | Propre au client s'il est rempli, sinon celui des Paramètres |
| Clients archivés | Plus proposés pour un nouveau document, mais toujours affichés sur leurs anciens documents |
| Mise en page modifiable | Tout se modifie sur le document : logo (retirer, remplacer, 3 tailles), tous les textes et libellés, blocs affichés, informations de la société. Rien n'est verrouillé, même sur les factures (choix de l'utilisateur, 26/10/2026) |
| Modèle de mise en page | Valeurs d'origine ← modèle des Paramètres (« Utiliser pour les prochains devis » depuis l'éditeur) ← changements du document. Un brouillon ne garde que ses différences ; à l'envoi, la mise en page et le logo sont figés dans le document |

## Base de données

- `users` : name, email, password.
- `settings` (une seule ligne) :
  - identité : raison sociale, adresse, téléphone, email ;
  - identifiants légaux : ICE, RC, IF, TP, CNSS ;
  - logo, banque, agence, RIB ;
  - documents : préfixes, TVA par défaut, délai de paiement, validité des devis, modalités de paiement, mentions.
- `clients` : raison sociale, ICE, IF, RC, adresse, ville, email, téléphone, contact, notes, délai de paiement, `archived_at`.
- `sequences` : type, année, dernier numéro.
- `quotes` :
  - numéro, client, date, valable jusqu'au, référence de la demande, statut ;
  - taux de TVA, % de remise, totaux ;
  - modalités, délai de réalisation, mentions, snapshots.
- `invoices` :
  - numéro, client, devis d'origine, date de facture, échéance, n° de bon de commande, statut ;
  - TVA, remise, totaux ;
  - `cancelled_at`, mentions, snapshots.
- `document_lines` (polymorphique) : position, désignation, quantité `DECIMAL(12,3)`, prix unitaire en centimes, montant en centimes.
- `payments` : facture, date, montant, mode (virement, chèque, espèces, effet), référence.

## Statuts

- **Devis** : Brouillon → Envoyé (le numéro est attribué à ce moment) → Accepté ou Refusé. Expiré est calculé après la date de validité.
- **Facture** :
  - Brouillon → Émise (le numéro est attribué à ce moment) → Partiellement payée → Payée ;
  - En retard est calculé après l'échéance, tant qu'il reste un solde ;
  - Annulée.

## Phases

1. **Base** ✅ : projet, connexion, commandes `user:create` et `user:password`, mise en page avec sidebar, Paramètres (société, logo, banque, documents, utilisateurs), cœur de calcul (`Money`, `AmountInWords`, `DocumentCalculator`) et ses tests.
2. **Clients** ✅ : liste (recherche, filtre Actifs / Archivés, tri par nom), formulaire, fiche (place prête pour devis, factures, total facturé et reste à payer), archivage ; suppression seulement sans document.
3. **Éditeur et devis** ✅ :
   - éditeur visuel A4 en Livewire (`QuoteEditor` + trait réutilisable `EditsDocumentLines`), repris du prototype ;
   - numérotation, statuts ;
   - PDF (dompdf, police Carlito, filigrane) et Word (PhpWord) ;
   - dupliquer. La conversion devis → facture est reportée en phase 4.

   Choix de réalisation :
   - un nouveau devis n'est créé en base qu'à la première saisie (pas de brouillons vides) ;
   - sauvegarde automatique 2 s après la dernière modification ; les montants affichés viennent toujours du serveur ;
   - `App\Support\DocumentSheet` est l'unique source de l'écran en lecture, du PDF et du Word (tests de cohérence) ;
   - « Validité de l'offre » est calculée à partir de « Valable jusqu'au » (pas de texte séparé à tenir à jour) ;
   - le statut « Envoyé » s'obtient par le bouton « Marquer comme envoyé » (pas d'email) ;
   - seul un brouillon peut être supprimé ; Paramètres › Devis et factures a un « Délai de réalisation » par défaut.
4. **Factures et paiements** ✅ : émission verrouillée, paiement total ou partiel, retard, annulation, PDF et Word, conversion devis → facture.

   Choix de réalisation :
   - même éditeur que les devis (traits `EditsDocumentHeader`, `EditsDocumentLines`, `EditsDocumentLayout` et partials `livewire/partials/editor`) ;
   - statuts enregistrés : brouillon, émise, annulée ; « Partiellement payée », « Payée » et « En retard » sont calculés (colonne `amount_paid` tenue à jour par les paiements) ;
   - l'échéance d'une nouvelle facture suit le délai de paiement du client ;
   - une facture avec des paiements ne peut pas être annulée (supprimer d'abord les paiements) ; un paiement ne dépasse jamais le reste à payer ;
   - le PDF montre « Déjà payé » et le solde exigible après un paiement ; « ANNULÉE » en filigrane pour une facture annulée ;
   - la signature du client n'apparaît pas par défaut sur une facture (comme le prototype) ; la mise en page des factures a son propre modèle.
5. **Tableau de bord et finitions** :
   - indicateurs et graphique du CA sur 12 mois ;
   - `backup:run` planifié chaque jour ;
   - seeders de démonstration ;
   - README de mise en production (VPS et hébergement mutualisé).
