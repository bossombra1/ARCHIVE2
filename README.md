# 🗂️ ARCHIVE2 — Gestion Électronique de Documents (GED)

**ARCHIVE2** est une application web full-stack de **gestion électronique de documents (GED)** avec un système de sécurité multi-niveaux basé sur la hiérarchie organisationnelle.

L'application est développée avec :

* **Backend :** Laravel 13
* **Frontend :** React 19
* **Base de données :** MySQL / MariaDB
* **Authentification :** Laravel Sanctum

---

## 📋 Table des matières

* [🎯 Aperçu](#-aperçu)
* [✨ Fonctionnalités](#-fonctionnalités)
* [🏗️ Architecture](#️-architecture)
* [📜 Règles métier](#-règles-métier)
* [🚀 Installation](#-installation)
* [🎮 Utilisation](#-utilisation)
* [👤 Comptes de test](#-comptes-de-test)
* [🔒 Sécurité](#-sécurité)
* [🧪 Tests](#-tests)
* [📁 Structure du projet](#-structure-du-projet)
* [🛣️ Roadmap](#️-roadmap)
* [📝 Licence](#-licence)
* [👨‍💻 Auteur](#-auteur)
* [🙏 Remerciements](#-remerciements)

---

# 🎯 Aperçu

**ARCHIVE2** est une GED sécurisée permettant à une organisation de gérer ses documents avec un contrôle d'accès fin basé sur la hiérarchie organisationnelle :

```text
Entreprise
    │
    └── Direction
          │
          └── Département
                │
                └── Service
                      │
                      └── Utilisateur
                            │
                            └── Affectation
                                  │
                                  ├── Poste
                                  └── Périmètre
```

Chaque utilisateur voit uniquement les documents correspondant à son **périmètre d'accès**.

L'accès peut toutefois être étendu temporairement grâce à des **permissions explicites** accordées sur certains documents.

---

# ✨ Fonctionnalités

## 📁 Gestion documentaire

* ✅ Upload de documents : PDF, PNG, JPG, JPEG, GIF
* ✅ Taille maximale : **10 Mo**
* ✅ Prévisualisation inline :

  * PDF via `iframe`
  * Images affichées directement
* ✅ Téléchargement sécurisé avec token et vérification du périmètre
* ✅ Modification des documents
* ✅ Suppression avec **soft delete**
* ✅ Recherche par titre
* ✅ Filtrage par type de document
* ✅ Pagination

---

## 🔐 Sécurité documentaire

* ✅ Filtrage **100 % côté backend**
* ✅ Le frontend ne constitue pas une couche de sécurité
* ✅ Périmètre déterminé automatiquement lors de l'upload
* ✅ Périmètre basé sur l'affectation active de l'utilisateur
* ✅ Permissions temporaires
* ✅ Cibles possibles des permissions :

  * Utilisateur
  * Poste
  * Service
* ✅ Expiration automatique des permissions via `expires_at`
* ✅ Protection multi-tenant
* ✅ Refus des accès cross-company
* ✅ Protection contre les attaques **IDOR**
* ✅ Retour `404` lorsqu'un document est hors périmètre

---

## 👥 Administration

L'espace d'administration permet notamment :

* ✅ Gestion des Directions — CRUD complet
* ✅ Gestion des Départements — CRUD complet avec rattachement
* ✅ Gestion des Services — CRUD complet avec rattachement
* ✅ Gestion des Types de documents — CRUD complet
* ✅ Gestion des Utilisateurs :

  * création
  * modification
  * affectation
  * reset du mot de passe
  * activation / désactivation
* ✅ Consultation des Journaux d'audit
* ✅ Consultation des Postes / Rôles en lecture seule

---

## 🎨 Interface utilisateur

* ✅ Dashboard moderne
* ✅ Diagramme circulaire (**donut SVG**)
* ✅ Modals pour les différentes opérations
* ✅ Navbar adaptative selon le rôle
* ✅ Menu **Paramètres** réservé aux administrateurs
* ✅ Interface responsive
* ✅ Bootstrap 5
* ✅ Thème personnalisable
* ✅ Support des langues :

  * 🇫🇷 Français
  * 🇬🇧 Anglais

---

# 🏗️ Architecture

## Stack technique

| Couche           | Technologie         |
| ---------------- | ------------------- |
| Backend          | Laravel 13.17       |
| Langage backend  | PHP 8.3             |
| Authentification | Laravel Sanctum     |
| Frontend         | React 19.2          |
| Bundler          | Vite 8              |
| Routing          | React Router 6      |
| HTTP Client      | Axios               |
| Base de données  | MySQL 8.0 / MariaDB |
| Tests            | PHPUnit 12.5        |
| CSS              | Bootstrap 5         |

---

## 🔐 Authentification

L'authentification repose sur **Laravel Sanctum** avec des tokens Bearer.

```text
Utilisateur
     │
     ▼
Authentification
     │
     ▼
Laravel Sanctum
     │
     ▼
Token Bearer
     │
     ▼
API sécurisée
```

---

## 🗄️ Schéma de base de données

La structure principale repose notamment sur les relations suivantes :

```text
companies
    │
    └── directions
          │
          └── departments
                │
                └── services
                      │
                      └── affectations
                            │
                            ├── users
                            └── poste
```

Les documents et permissions sont associés à cette structure :

```text
users
  │
  └── affectations
        │
        └── poste
              │
              └── service
                    │
                    └── department
                          │
                          └── direction


documents
  │
  ├── uploaded_by
  ├── service_id
  ├── dept_id
  └── dir_id
        │
        └── document_permissions
              ├── target_type: user
              ├── target_type: poste
              └── target_type: service


journals
    └── Audit log
```

---

## 🔎 Flux de sécurité documentaire

La visibilité d'un document est déterminée selon le flux suivant :

```text
Utilisateur connecté
        │
        ▼
Affectation active
(is_active + started_at + ended_at)
        │
        ▼
Poste / Niveau
        │
        ▼
DocumentVisibilityService::scopeForUser()
        │
        ├───────────────┐
        ▼               ▼
Périmètre normal   Permissions valides
                    │
                    └── expires_at NULL ou future
        │
        ▼
Liste des documents visibles
```

---

# 📜 Règles métier

## 👔 Visibilité par poste

| Poste                     | Visibilité normale                               |
| ------------------------- | ------------------------------------------------ |
| `admin`                   | Tous les documents de l'entreprise               |
| `dg`                      | Toutes les directions de l'entreprise            |
| `directeur`               | Sa direction uniquement                          |
| `responsable_departement` | Son département + services descendants           |
| `chef_service`            | Son service uniquement                           |
| `employe`                 | Son service uniquement                           |
| `agent_temporaire`        | Son service uniquement + permissions temporaires |

---

## 🔑 Permissions documentaires

Une permission `user`, `poste` ou `service` permet uniquement :

* ✅ Voir le document
* ✅ Télécharger le document

Une permission **ne permet jamais** :

* ❌ Modifier le document
* ❌ Supprimer le document
* ❌ Accorder des permissions à d'autres utilisateurs

---

## ⏱️ Validité d'une permission

Une permission est considérée comme :

| Condition             | État                          |
| --------------------- | ----------------------------- |
| `expires_at IS NULL`  | Permission permanente         |
| `expires_at > NOW()`  | Permission valide             |
| `expires_at <= NOW()` | Permission expirée et ignorée |

---

## 📅 Affectation active

Une affectation est considérée comme active lorsque :

```text
is_active = true
AND started_at <= aujourd'hui
AND (
    ended_at IS NULL
    OR ended_at >= aujourd'hui
)
```

### ⚠️ Refus par défaut

L'accès est refusé lorsqu'il existe :

* aucune affectation active ;
* une affectation échue ;
* une affectation future ;
* plusieurs affectations actives simultanément.

---

# 🚀 Installation

## 📋 Prérequis

Avant l'installation, vérifier que les éléments suivants sont disponibles :

* PHP **8.3+**
* Composer
* Node.js **18+**
* npm
* MySQL **8.0+** ou MariaDB
* WampServer, XAMPP ou Laragon

---

## 1️⃣ Cloner le dépôt

```bash
git clone https://github.com/elielgbla/ARCHIVE2.git
cd ARCHIVE2
```

---

## 2️⃣ Installer le backend Laravel

Accéder au dossier backend :

```bash
cd backend-laravel
```

Installer les dépendances :

```bash
composer install
```

Créer le fichier `.env` :

```bash
cp .env.example .env
```

Générer la clé Laravel :

```bash
php artisan key:generate
```

---

## ⚙️ Configuration de la base de données

Modifier le fichier `.env` afin de configurer la connexion MySQL :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stockage
DB_USERNAME=root
DB_PASSWORD=
```

Configuration Sanctum :

```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,localhost:8000,127.0.0.1:8000
```

---

## 🗃️ Exécuter les migrations et seeders

```bash
php artisan migrate
```

Puis :

```bash
php artisan db:seed --class=PosteSeeder
php artisan db:seed --class=DocumentTypeSeeder
```

---

## ▶️ Démarrer le backend

```bash
php artisan serve
```

Le backend sera accessible à :

```text
http://127.0.0.1:8000
```

---

# 3️⃣ Installer le frontend React

Depuis la racine du projet :

```bash
cd ../frontend-react
```

Installer les dépendances :

```bash
npm install
```

Créer le fichier `.env` :

```bash
cp .env.example .env
```

Configurer l'URL de l'API :

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

---

## ▶️ Démarrer Vite

```bash
npm run dev
```

Le frontend sera accessible à :

```text
http://localhost:5173
```

---

# 4️⃣ ⚙️ Configuration initiale

Ouvrir :

```text
http://localhost:5173
```

Au premier lancement, l'application affiche l'écran de configuration initiale.

Les informations demandées sont :

1. **Nom de l'entreprise**
   Exemple : `DGMP`

2. **Taille de l'entreprise**

   * `small`
   * `large`

3. **Nom de l'administrateur**

4. **Email de l'administrateur**

5. **Mot de passe**

   * minimum 6 caractères

Une fois la configuration validée, l'administrateur peut se connecter avec le compte créé.

---

# 🎮 Utilisation

## 1. 📊 Tableau de bord

Après connexion, le dashboard présente notamment :

* 4 cartes de statistiques :

  * Documents
  * Types
  * Services
  * Utilisateurs
* 5 documents récents
* Diagramme circulaire de répartition par type
* Accès rapides

---

## 2. 📄 Documents

Depuis la section Documents :

### Ajouter un document

Cliquer sur **« Ajouter un document »** pour ouvrir le modal d'upload.

### Prévisualiser

Cliquer sur une ligne pour prévisualiser le document.

### Actions disponibles

Selon les droits de l'utilisateur :

* 👁 **Voir**
* ⬇ **Télécharger**
* ✎ **Modifier**
* 🗑 **Supprimer**
* 🔐 **Permissions** — disponible pour les utilisateurs autorisés à accorder des permissions

---

# 3. ⚙️ Paramètres

Le menu **Paramètres** est réservé aux administrateurs.

Il permet d'accéder aux fonctionnalités suivantes :

| Section              | Fonction                                           |
| -------------------- | -------------------------------------------------- |
| Types de documents   | Gestion des catégories                             |
| Services             | Gestion des services rattachés à un département    |
| Départements         | Gestion des départements rattachés à une direction |
| Directions           | Gestion des directions                             |
| Postes / Rôles       | Consultation des 7 postes                          |
| Utilisateurs & Accès | Gestion des utilisateurs et affectations           |
| Journaux d'audit     | Consultation des actions journalisées              |

---

# 👤 Comptes de test

Pour tester les différents rôles de l'application, charger les seeders de test :

```bash
cd backend-laravel
php artisan db:seed --class=TestDataSeeder
php artisan db:seed --class=TestUsersSeeder
```

### 🔑 Mot de passe commun

```text
password123
```

---

## Comptes disponibles

| Email                              | Rôle                   | Périmètre                |
| ---------------------------------- | ---------------------- | ------------------------ |
| `admin.test@archive2.test`         | Administrateur Système | Entreprise entière       |
| `dg@archive2.test`                 | Directeur Général      | Toutes les directions    |
| `directeur.rh@archive2.test`       | Directeur              | Direction RH             |
| `directeur.si@archive2.test`       | Directeur              | Direction SI             |
| `resp.recrutement@archive2.test`   | Resp. Département      | Dépt Recrutement         |
| `resp.developpement@archive2.test` | Resp. Département      | Dépt Développement       |
| `chef.paie@archive2.test`          | Chef de Service        | Service Paie             |
| `chef.helpdesk@archive2.test`      | Chef de Service        | Service Helpdesk         |
| `employe.paie@archive2.test`       | Employé                | Service Paie             |
| `employe.devweb@archive2.test`     | Employé                | Service Applications Web |
| `agent.helpdesk@archive2.test`     | Agent Temporaire       | Service Helpdesk         |
| `agent.accueil@archive2.test`      | Agent Temporaire       | Service Accueil          |

---

# 🧪 Scénario de test des permissions

Le scénario suivant permet de vérifier le fonctionnement des permissions temporaires.

### Étape 1 — Création du document

Se connecter avec :

```text
chef.paie@archive2.test
```

Créer ensuite un document dans le **service Paie**.

### Étape 2 — Vérification du périmètre

Se déconnecter puis se connecter avec :

```text
agent.helpdesk@archive2.test
```

Le document créé précédemment **ne doit pas être visible**.

### Étape 3 — Accorder une permission

Se reconnecter avec :

```text
chef.paie@archive2.test
```

Cliquer sur l'icône :

```text
🔐
```

à côté du document.

Ajouter ensuite une permission :

```text
Type : Utilisateur
Utilisateur : Ibrahim Sow
Email : agent.helpdesk@archive2.test
```

### Étape 4 — Vérification

Se reconnecter avec :

```text
agent.helpdesk@archive2.test
```

Le document est désormais visible. ✅

---

# 🔒 Sécurité

ARCHIVE2 applique plusieurs mécanismes de sécurité afin de protéger les documents et les données de l'organisation.

## Principes appliqués

### 🔐 Sécurité 100 % backend

Le frontend peut masquer certains boutons pour améliorer l'expérience utilisateur.

Cependant, **la sécurité réelle est assurée par Laravel**, notamment à travers :

* Policies
* Middleware
* Contrôles d'accès backend

---

### 🏢 Multi-tenant

Toutes les requêtes sont filtrées par :

```text
company_id
```

Un utilisateur ne peut donc pas accéder aux données d'une autre entreprise.

---

### 🛡️ Protection Anti-IDOR

Les endpoints `show` et `download` retournent `404` lorsqu'un document est hors du périmètre autorisé.

Cette approche permet également de limiter l'énumération des ressources.

---

### 📍 Détermination du périmètre côté backend

Le `service_id` d'un document est déterminé à partir de l'affectation active de l'utilisateur.

Il ne provient pas directement des données envoyées par le client.

---

### 🧪 Validation MIME réelle

Le serveur utilise :

```text
finfo_file()
```

pour vérifier le type MIME réel du fichier.

La sécurité ne repose donc pas uniquement sur l'extension du fichier.

---

### 🔒 Stockage privé

Les documents sont stockés dans :

```text
storage/app/private/
```

Les fichiers utilisent des noms UUID.

Le nom de fichier d'origine n'est pas utilisé comme nom de stockage.

---

### ⬇️ Téléchargement sécurisé

Le téléchargement utilise un endpoint dédié :

```text
/documents/{id}/download
```

L'accès est soumis à une vérification de Policy.

---

# 🧩 Middleware

| Middleware           | Rôle                                    |
| -------------------- | --------------------------------------- |
| `auth:sanctum`       | Authentification par token Bearer       |
| `company.configured` | Vérifie que l'entreprise est configurée |
| `poste:admin`        | Restreint l'accès aux administrateurs   |

---

# 🛡️ Policies

## `DocumentPolicy`

La `DocumentPolicy` gère notamment :

```text
view
viewAny
create
update
delete
download
grantPermission
revokePermission
```

## `DocumentPermissionPolicy`

La `DocumentPermissionPolicy` gère :

```text
viewAny
create
delete
```

---

# 🧪 Tests

Pour exécuter l'ensemble des tests :

```bash
cd backend-laravel
php artisan test
```

---

## Couverture des tests

| Fichier                         | Scénarios                                                                                               |
| ------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `DocumentAuthorizationTest`     | 7 rôles + permissions + cross-company + IDOR + soft delete + affectations problématiques — 20 scénarios |
| `DocumentPermissionTest`        | Gestion des permissions : grantors, cibles, révocation, expiration                                      |
| `DocumentUploadTest`            | Validation MIME, taille et périmètre backend                                                            |
| `DocumentVisibilityServiceTest` | Logique unitaire du service de visibilité                                                               |

---

## 🗄️ Base de données des tests

Les tests utilisent **SQLite en mémoire** :

```text
:memory:
```

La configuration est définie dans :

```text
phpunit.xml
```

Les tests n'impactent donc pas la base de données MySQL réelle.

---

# 📁 Structure du projet

```text
ARCHIVE2/
│
├── backend-laravel/
│   │
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── AuthController, DocumentController,
│   │   │   │       UserController, ...
│   │   │   │
│   │   │   ├── Middleware/
│   │   │   │   └── CheckPosteLevel,
│   │   │   │       CheckCompanyConfigured
│   │   │   │
│   │   │   └── Requests/
│   │   │       └── DocumentStoreRequest,
│   │   │           DocumentUpdateRequest, ...
│   │   │
│   │   ├── Models/
│   │   │   └── User, Document, Affectation, ...
│   │   │
│   │   ├── Policies/
│   │   │   ├── DocumentPolicy
│   │   │   └── DocumentPermissionPolicy
│   │   │
│   │   ├── Services/
│   │   │   └── DocumentVisibilityService
│   │   │
│   │   └── Providers/
│   │       └── AppServiceProvider
│   │
│   ├── config/
│   │   └── documents.php
│   │       └── Configuration upload :
│   │           disk, taille, MIME
│   │
│   ├── database/
│   │   ├── factories/
│   │   │   └── 9 factories pour les tests
│   │   │
│   │   ├── migrations/
│   │   │   └── 16 migrations
│   │   │       └── dont 2 additives pour documents
│   │   │
│   │   └── seeders/
│   │       ├── PosteSeeder
│   │       ├── DocumentTypeSeeder
│   │       ├── TestDataSeeder
│   │       └── TestUsersSeeder
│   │
│   ├── routes/
│   │   └── api.php
│   │       └── Routes API REST
│   │
│   └── tests/
│       ├── Feature/
│       │   └── 3 tests feature
│       ├── Unit/
│       │   └── 1 test unitaire
│       └── Helpers/
│           └── DocumentTestHelper trait
│
├── frontend-react/
│   │
│   ├── src/
│   │   ├── components/
│   │   │   └── Navbar, MainLayout
│   │   │
│   │   ├── context/
│   │   │   └── AppContext
│   │   │
│   │   ├── pages/
│   │   │   ├── auth/
│   │   │   │   └── LoginView
│   │   │   │
│   │   │   ├── setup/
│   │   │   │   └── SetupView
│   │   │   │
│   │   │   ├── dashboard/
│   │   │   │   └── DashboardView
│   │   │   │
│   │   │   ├── documents/
│   │   │   │   ├── DocumentView
│   │   │   │   └── TypeDocView
│   │   │   │
│   │   │   └── company/
│   │   │       ├── ServiceView
│   │   │       ├── DirectionsView
│   │   │       ├── DepartmentsView
│   │   │       ├── UsersView
│   │   │       ├── JournalsView
│   │   │       └── PostesView
│   │   │
│   │   └── services/
│   │       ├── api.js
│   │       ├── authService
│   │       ├── documentService
│   │       ├── adminService
│   │       └── ...
│   │
│   ├── .env.example
│   └── package.json
│
└── README.md
```

---

# 🛣️ Roadmap

Les fonctionnalités suivantes sont envisagées pour les prochaines évolutions :

* ⬜ Corbeille avec `restore` / `forceDelete` pour les documents soft-deleted
* ⬜ Export CSV / Excel des journaux d'audit
* ⬜ Notifications par email :

  * nouveau document
  * permission accordée
* ⬜ Recherche full-text :

  * titre
  * description
* ⬜ Versioning des documents avec historique des versions
* ⬜ Mode sombre
* ⬜ Application mobile avec React Native
* ⬜ Conversion MyISAM → InnoDB si base legacy
* ⬜ Refactor i18n avec fusion des 3 dictionnaires de traductions
* ⬜ Ajout d'un véritable router avec `ProtectedRoute` pour la redirection automatique des utilisateurs non administrateurs

---

# 📝 Licence

Ce projet est distribué sous licence **MIT**.

Pour plus d'informations, consulter le fichier :

```text
LICENSE
```

---

# 👨‍💻 Auteur

**Eliel Bbla**

**Regis Kouamé**

* GitHub : `@elielgbla`
* GitHub : `https://github.com/bossombra1`
* Dépôt : **ARCHIVE2**

---

# 🙏 Remerciements

Merci aux projets et technologies utilisés dans ARCHIVE2 :

* **Laravel Framework** — `laravel.com`
* **React** — `react.dev`
* **Bootstrap** — `getbootstrap.com`
* **React Router** — `reactrouter.com`

---

## 📌 Informations sur le document

| Information                   | Valeur                     |
| ----------------------------- | -------------------------- |
| Projet                        | ARCHIVE2                   |
| Version                       | 1.0.0                      |
| Type                          | Application web full-stack |
| Dernière génération du README | 2026-09-01                 |
| Licence                       | MIT                        |

---

> **ARCHIVE2** — Gestion Électronique de Documents sécurisée avec contrôle d'accès basé sur la hiérarchie organisationnelle.
