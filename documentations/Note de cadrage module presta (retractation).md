**Note de cadrage RetractPlug**  
*Module de gestion du droit de rétractation et liaison Dolibarr*

| Date | 23 juin 2026 |
| :---- | :---- |
| Version du document | 1.0 |
| Auteur | Hugo BOHARD |
| Statut | En validation |
| Deadline | 30 juin 2026 |

# **I. Définition du projet**

*De quoi s'agit-il concrètement ?*

**RetractPlug** est un module PrestaShop qui permet aux clients d'une boutique en ligne d'exercer leur droit de rétractation directement depuis leur espace client. Le module automatise le choix des produits à retourner, génère les documents de retour nécessaires, et crée automatiquement un avoir au statut "brouillon" sur l'ERP Dolibarr pour faciliter le traitement comptable par l'administrateur.

# **II. Origine du projet / Contexte**

*Pourquoi lance-t-on ce projet maintenant ?*

La gestion des retours et du droit de rétractation (sous 14 jours après réception) est aujourd'hui une obligation légale et un point critique de l'expérience client. Actuellement, le traitement des demandes de rétractation et la répercussion comptable (création des avoirs) dans Dolibarr se font manuellement, ce qui génère des pertes de temps et des risques d'erreurs de saisie. Ce module vise à automatiser ce flux de bout en bout.

# **III. Objectifs du projet**

*Quels sont les buts à atteindre (SMART) ?*

**Objectifs Principaux (Livrables MVP)**

* Permettre au client de déclarer une rétractation en autonomie depuis son historique de commandes, sous réserve que la commande ait été reçue depuis moins de 14 jours.  
* Offrir une interface simple de sélection des produits et des quantités à retourner, accompagnée d'un motif de retour.  
* Générer automatiquement un bordereau de retour (PDF) téléchargeable par le client.  
* Synchroniser en temps réel la demande avec Dolibarr en y créant un avoir au statut "brouillon" contenant les produits concernés.

**Objectifs Techniques**

* Développer le module en respectant rigoureusement les standards de développement PrestaShop (architecture MVC, respect des hooks).  
* Consommer proprement l'API REST de Dolibarr de manière sécurisée (gestion des clés API et des timeouts).  
* Garantir la traçabilité des demandes via un historique côté administration PrestaShop.

# **IV. Périmètre du projet (Scope)**

*Ce qu'on fait (In) et ce qu'on ne fait pas (Out).*

**Inclus (MVP)**  
**Front Office :**

* Affichage d'un bouton "Demander une rétractation" sur le détail d'une commande (uniquement si le statut est "Livré" et depuis \< 14 jours).  
* Formulaire d'étape : sélection des produits, des quantités, et champ texte pour la raison du retour.  
* Génération et téléchargement du bordereau de retour client.


	**Back Office PrestaShop :**

* Tableau de bord listant les demandes de rétractation en cours (statuts : En attente de colis, Reçu, Refusé, Traité).  
* Configuration des identifiants et de l'URL de l'API Dolibarr.  
* Historique des raison du retour clients (utile pour comprendres et analyser pourquoi il le retourne)

**Liaison Dolibarr :**

* Création automatique de l'avoir en mode brouillon dès la soumission du formulaire par le client.

**Exclus (MVP2)**

- Génération automatique d'une étiquette de transport prépayée (Colissimo, Chronopost, etc.).  
- Gestion des frais de retour avancés (déduction automatique des frais de port sur l'avoir).  
- Remise en stock automatique des produits lors du passage de l'avoir à l'état validé sur Dolibarr.

**V. Contraintes**  
*Ce qui s'impose à nous.*

**Financière** : Budget "Zéro risque". Utilisation exclusive d'outils gratuits/Open Source.  
**Temporelle :** Date limite de livraison du prototype à valider collectivement.  
**Technique :** PHP vanilla côté PrestaShop, respect de la version de l'API REST Dolibarr en place.

**VI. Acteurs**  
*Qui participe ?*

| Rôle | Membres \- Responsabilité |
| :---- | :---- |
| Chef de Projet | Hugo BOHARD \- Priorisation des features, gestion de projet, planning, livrables, support et aide au développement |
| Équipe Dév | Hugo BOHARD \- Développement du module |
| Commanditaire | Nathalie DELORME \- Validation des étapes clés |
| Testeur | Hugo BOHARD \- Test fonctionnel du module (calcul des points, affichages) |

# **VII. Macro Planning (Objectif Avril)**

*Les grandes étapes.*

Cette roadmap détaille les étapes opérationnelles du projet **RetractPlug**, depuis la phase de mise en place technique jusqu’à la livraison du MVP 1 & 2  
Elle est construite dans le respect strict du périmètre validé et des contraintes temporelles définies dans la présente note de cadrage.

**Phase 1 \- Architecture, Configuration Back-Office & Initialisation API**  
*Période :*

**Objectifs :** Poser les fondations techniques du module côté PrestaShop (MVC) et valider la communication brute avec l'API REST de Dolibarr.

**Actions clés**

- **Initialisation du module** : structure réglementaire des dossiers (`/controllers`, `/views`, `/classes`), création du fichier principal `retractplus.php` et configuration de l'auto-configuration.  
- **Base de données** : création de la table SQL `ps_retractplus_requests` pour stocker et tracer les demandes locales (ID client, ID commande, produits retournés, quantités, motif, statut de la demande, et ID de l'avoir Dolibarr généré).  
- **Interface de configuration Admin** : création d'un formulaire de configuration sécurisé en Back-Office pour saisir l'URL de l'API Dolibarr et la clé d'API (Token).

**Actions clés Dolibarr (API) :**

- Établissement d'une classe de connexion (Helper API) pour gérer les requêtes cURL (ou via Guzzle) vers Dolibarr.  
- Script de test d'authentification (Ping API) pour valider la communication directement depuis le Back-Office PrestaShop.

## **Phase 2 \- Interface Client & Logique Métier (Vérification des 14 jours)**

*Période :*

**Objectifs :** Permettre au client de visualiser l'option de rétractation de manière conditionnelle et sécurisée, puis de sélectionner ses produits.

**Actions clés**

- **Hook `displayCustomerAccountOrder` :** injection du bouton "Demander une rétractation" sur la page de détail d'une commande dans l'espace client.  
- **Sécurisation serveur (PHP) :** implémentation de la logique stricte. Le bouton et le contrôleur de traitement n'acceptent la demande **que si** le statut de la commande est égal à "Livré" **et** si la date de ce changement de statut est inférieure ou égale à 14 jours par rapport à la date du jour.

**Formulaire d'étape (Interface de sélection) :**

- Création d'un contrôleur Front dédié (`RetractFormModuleFrontController`).  
- Affichage de la liste des produits de la commande avec des cases à cocher, un sélecteur de quantité dynamique (bloqué au maximum de la quantité achetée) et un champ de texte de type *textarea* pour renseigner le motif du retour.

## **Phase 3 \- Moteur de Documents & Pipeline de Soumission**

*Période :*

**Objectifs :** Traiter la soumission du formulaire, générer le bordereau de retour pour le client et envoyer les données à l'ERP.

**Actions clés Traitement & PDF :**

- Soumission du formulaire en POST avec une double vérification de sécurité (Token CSRF et vérification persistante du délai des 14 jours côté serveur).  
- Génération d'un fichier PDF (Bordereau de retour) listant les produits retournés, le motif, et les instructions de retour (adresse de l'entrepôt). Ce fichier reste téléchargeable par le client dans son espace.

**Synchronisation Dolibarr :**

- Formatage des données au format JSON requis par Dolibarr.  
- Appel à la route API Dolibarr `/factures` pour créer une facture de type avoir (`type = 2`) au statut brouillon (`status = 0`), en y associant automatiquement le client (liaison ID) et les lignes de produits correspondantes (références, quantités, et taux de TVA).  
- Enregistrement de l'ID de l'avoir renvoyé par Dolibarr dans la table `ps_retractplus_requests` pour assurer le chaînage.

## **Phase 4 – Administration, Recette & Sécurisation**

*Période :* 

**Objectifs :** Assurer la robustesse globale de l'application, polir l'interface et packager le module.

**Actions clés Back-Office :**

- Dashboard Admin : listing des demandes de rétractation reçues avec affichage de leur statut PrestaShop, du motif du client, et d'un lien direct ou du numéro de l'avoir Dolibarr lié.

**Tests de robustesse (Recette) :**

- **Tests de fraude** : tentative de forcer une demande via des requêtes directes (POST) sur une commande datant de plus de 14 jours ou appartenant à un autre client.  
- **Test de coupure réseau :** vérification du comportement du module si l'API de Dolibarr est injoignable (mise en place d'un log d'erreur propre pour éviter la page blanche au client et lui valider sa demande côté PrestaShop malgré tout).

**Packaging :**

- Nettoyage du code selon les standards PSR, rédaction du fichier `README.md` d'installation, et compression du dossier au format officiel `.zip` prêt à être téléversé.

## **Phase 5 – Livraison & Mise en Production**

*Période :*

**objectifs**

- Livrer un module prêt à l'emploi (MVP)


**Actions clés**

- Nettoyage du code : Respect des standards PSR.  
- Documentation : Rédaction d'un mini-guide d'installation et d'utilisation pour le marchand.  
- Packaging : Création de l'archive .zip finale du module.


# **VIII. Ressources**

*Les moyens à disposition.*

**Stack Technique :** Php vanilla, Maria (bdd)  
**Outils :** GitHub (Code), Asana (Tâches).

# **IX. Communication**

*Comment l'équipe échange-t-elle ?*

**Interne :** Serveur Discord pour les échanges quotidiens, partages d'écran techniques et points de blocage. WhatsApp / Asana pour le suivi général et la coordination avec le reste de l'équipe.  
**Documentation :** Documentation de l'API de rétractation et collection Postman obligatoires pour assurer la maintenance.

**X. Risques**  
*Comment l'équipe échange-t-elle ?*

| Risque | Impact | Solution |
| :---- | :---- | :---- |
| API Dolibarr indisponible au moment du clic | Majeur  | Mettre en place une file d'attente (queue) dans PrestaShop pour retenter l'envoi de l'avoir dès que l'API répond à nouveau, sans bloquer le client. |
| Fraude sur le délai des 14 jours | Critique | Effectuer la vérification de la date côté serveur (PHP) lors du traitement du formulaire, et pas seulement en masquant le bouton en CSS/JS. |
| Incohérence des prix ou taxes sur l'avoir | Critique | Récupérer les ID exacts des lignes de la commande PrestaShop et mapper fidèlement les taux de TVA dans l'appel API Dolibarr. |

# 

# **XI. Engagement et Validation Finale**

La signature du présent document de cadrage ne constitue pas une simple formalité administrative. Elle matérialise l'accord plein et entier de chaque signataire avec la vision du projet **RetractPlug**, ses objectifs techniques, ainsi que les contraintes temporelles et budgétaires exposées ci-dessus.  
En apposant votre signature ci-dessous, vous confirmez :

* **Votre adéquation** avec la stack technique retenue (PHP vanilla, Maria DB) et la méthodologie de travail.  
* **Votre compréhension** des enjeux et de la date limite impérative de livraison fixée au mois de mai.

Afin de garantir la stabilité du développement et la réussite collective du projet dans les délais impartis, la validation de ce document par l'ensemble des membres de l'équipe scelle définitivement le périmètre du projet.  
Par conséquent, une fois les signatures recueillies, **aucun retour en arrière** (changement de technologie, modification majeure du scope, ajout de fonctionnalités non-essentielles) ne sera accepté. Ce document deviendra la référence unique et indiscutable pour trancher tout arbitrage futur.

| Rôle | Nom | Date | Signature |
| :---- | :---- | :---- | :---- |
| Développeur | BOHARD Hugo | 23/06/2026 | Lu et approuvée |
| Commanditaire | DELORME Nathalie |  |  |

# 