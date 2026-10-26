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
| Version | Laravel 12 et Livewire 3 |
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

1. **Base** : projet, connexion, commandes `user:create` et `user:password`, mise en page avec sidebar, Paramètres (société, logo, banque, documents, utilisateurs), cœur de calcul (`Money`, `AmountInWords`, `DocumentCalculator`) et ses tests.
2. **Clients** : liste avec recherche, fiche, archivage.
3. **Éditeur et devis** :
   - éditeur visuel A4 en Livewire, repris du prototype ;
   - numérotation, statuts ;
   - PDF (dompdf, police Carlito, filigrane) et Word (PhpWord) ;
   - dupliquer, convertir en facture.
4. **Factures et paiements** : émission verrouillée, paiement total ou partiel, retard, annulation, PDF et Word.
5. **Tableau de bord et finitions** :
   - indicateurs et graphique du CA sur 12 mois ;
   - `backup:run` planifié chaque jour ;
   - seeders de démonstration ;
   - README de mise en production (VPS et hébergement mutualisé).
