# RetractPlug

![PrestaShop Version](https://img.shields.io/badge/PrestaShop-1.7%20%7C%208.x-blue.svg)
![License](https://img.shields.io/badge/License-AFL%203.0-green.svg)

**RetractPlug** est un module PrestaShop développé en PHP natif pour gérer le droit légal de rétractation client dans les 14 jours suivant la livraison d'une commande.

Le module ajoute un parcours front-office permettant au client de déclarer les produits à retourner, enregistre la demande localement, peut générer un avoir brouillon dans Dolibarr, et met à disposition un bordereau PDF de retour.

---

## Fonctionnalités

### Front-office client

- Affichage du bouton de rétractation sur les commandes livrées depuis moins de 14 jours.
- Vérification serveur de l'appartenance de la commande au client connecté.
- Blocage automatique des commandes non éligibles ou hors délai.
- Sélection des produits à retourner et des quantités concernées.
- Motif de retour obligatoire.
- Création d'une demande locale au statut `waiting_package`.
- Page de confirmation après enregistrement de la demande.
- Téléchargement d'un bordereau PDF de retour depuis le détail de commande.
- Message d'information sur le nombre de jours restants.

### Back-office PrestaShop

Le module ajoute un onglet **Rétractations & Retours** dans le menu **Commandes**.

L'administration permet de :

- Consulter les demandes de rétractation enregistrées.
- Voir la référence de commande, le client, le motif, le statut et l'ID d'avoir Dolibarr.
- Modifier le statut d'une demande.
- Supprimer une demande.
- Configurer la connexion Dolibarr.
- Activer ou désactiver la génération automatique des avoirs.
- Définir une adresse e-mail d'alerte en cas d'indisponibilité Dolibarr.
- Copier l'URL de la tâche CRON de secours.
- Utiliser des outils de diagnostic : test API Dolibarr, création d'un avoir de test, consultation et vidage des logs.
- Activer un mode debug pour un ID client précis pendant la recette.

### Statuts de retour

| Statut | Description |
| --- | --- |
| `waiting_package` | En attente du colis |
| `received` | Colis reçu |
| `refused` | Retour refusé |
| `processed` | Retour traité / remboursé |

### Synchronisation Dolibarr

Si la génération automatique est activée, RetractPlug crée un document Dolibarr de type avoir :

- endpoint Dolibarr utilisé : `/invoices`
- type : `2`
- statut initial : `0` (brouillon)
- lignes négatives basées sur les produits retournés
- taux de TVA repris depuis les lignes de commande PrestaShop

Si la génération automatique est désactivée, la demande reste enregistrée localement et aucun appel Dolibarr n'est envoyé. Ce mode est utile pour la recette front-office.

### Secours CRON

Une URL CRON sécurisée par jeton est générée dans la configuration du module.

Elle permet de relancer la synchronisation des demandes qui n'ont pas encore d'ID d'avoir Dolibarr :

```text
https://votre-boutique.com/modules/retractplug/cron.php?token=JETON_GENERE
```

Le traitement récupère jusqu'à 10 demandes en attente par exécution.

### E-mail d'alerte

Une adresse e-mail de secours peut être configurée depuis le back-office. Un bouton de test permet de valider l'envoi via le système de mails natif de PrestaShop.

Le template utilisé est disponible dans :

```text
mails/fr/alert_backup.html
```

### Bordereau PDF

Après la création d'une demande, le client peut télécharger un bordereau PDF de retour.

Le PDF est généré avec le générateur PDF natif de PrestaShop à partir des templates du module :

```text
views/templates/pdf/
```

---

## Architecture du projet

```text
retractplug/
├── classes/
│   └── RetractRequest.php
│
├── controllers/
│   ├── admin/
│   │   └── AdminRetractRequestsController.php
│   │
│   └── front/
│       ├── form.php
│       └── pdf.php
│
├── documentations/
│   ├── Note de cadrage module presta (retractation).md
│   └── Note de cadrage module presta (retractation).pdf
│
├── mails/
│   └── fr/
│       └── alert_backup.html
│
├── sql/
│   ├── install.sql
│   └── uninstall.sql
│
├── src/
│   ├── Model/
│   │   └── HTMLTemplateBordereauReturn.php
│   │
│   └── Service/
│       └── DolibarrApiClient.php
│
├── views/
│   ├── css/
│   │   └── retractplug.css
│   │
│   └── templates/
│       ├── admin/
│       │   └── configure.tpl
│       │
│       ├── front/
│       │   ├── form.tpl
│       │   └── success.tpl
│       │
│       ├── hook/
│       │   ├── order_detail.tpl
│       │   └── order_detail_return.tpl
│       │
│       └── pdf/
│           ├── bordereau_content.tpl
│           ├── bordereau_footer.tpl
│           └── bordereau_header.tpl
│
├── cron.php
├── retractplug.php
├── .env
└── README.md
```

### Principaux fichiers

| Fichier | Description |
| --- | --- |
| `retractplug.php` | Point d'entrée du module, installation, hooks et configuration |
| `classes/RetractRequest.php` | Modèle ObjectModel des demandes de rétractation |
| `controllers/front/form.php` | Parcours client de création d'une demande |
| `controllers/front/pdf.php` | Téléchargement sécurisé du bordereau PDF |
| `controllers/admin/AdminRetractRequestsController.php` | Liste, édition, configuration et outils de diagnostic back-office |
| `src/Service/DolibarrApiClient.php` | Client REST Dolibarr |
| `src/Model/HTMLTemplateBordereauReturn.php` | Template PHP utilisé pour générer le PDF |
| `cron.php` | Synchronisation asynchrone des demandes non envoyées à Dolibarr |
| `sql/install.sql` | Création de la table locale des demandes |
| `sql/uninstall.sql` | Suppression des données du module |

---

## Installation

### 1. Installer le module

Copier le dossier `retractplug` dans le dossier PrestaShop :

```text
/modules/
```

Installer ensuite le module depuis le back-office PrestaShop.

L'installation crée la table :

```text
ps_retractplug_requests
```

Le préfixe réel dépend de la configuration de votre boutique.

### 2. Activer l'API REST Dolibarr

Dans Dolibarr, activer le module API REST :

```text
Accueil > Configuration > Modules > API / Web Services
```

Générer ensuite une clé API sur l'utilisateur Dolibarr utilisé pour les appels API.

### 3. Configurer RetractPlug

Depuis le back-office PrestaShop :

```text
Commandes > Rétractations & Retours
```

Renseigner :

- URL de l'API REST Dolibarr, par exemple `https://votre-domaine.com/api/index.php`
- clé API `DOLAPIKEY`
- activation ou non de la génération automatique des avoirs
- ID client de debug, uniquement en recette
- e-mail d'alerte de secours

### 4. Configurer le CRON de secours

Copier l'URL CRON affichée dans la configuration du module et l'ajouter à votre planificateur de tâches.

Exemple de fréquence :

```text
*/15 * * * *
```

---

## Sécurité

RetractPlug revérifie toutes les actions côté serveur :

- le client doit être connecté ;
- la commande doit appartenir au client connecté ;
- la commande doit avoir un historique de livraison ;
- le délai de 14 jours est contrôlé côté PHP ;
- l'accès au PDF est limité au propriétaire de la demande ;
- l'URL CRON est protégée par un jeton stocké en configuration ;
- l'adresse e-mail d'alerte est validée avant enregistrement.

Les appels Dolibarr sont journalisés dans :

```text
var/logs/retractplug.log
```

---

## Prérequis

- PrestaShop 1.7.x ou 8.x
- PHP 7.4+
- Extension PHP cURL activée
- API REST Dolibarr activée
- Clé API Dolibarr valide
- Configuration e-mail PrestaShop fonctionnelle pour les alertes de secours

---

## Mode recette

Le champ **ID Client autorisé pour la Recette (Debug)** permet de forcer l'affichage du parcours de rétractation pour un compte client précis, même si les commandes ne respectent pas les conditions normales de livraison et de délai.

Ce paramètre doit rester vide en production.

---

## Auteur

**Hugo BOHARD**

- Chef de projet
- Développeur principal

---

## Licence

Ce projet est distribué sous licence **MIT License**.

https://opensource.org/license/mit
