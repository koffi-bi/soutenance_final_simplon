# 🚌 Système intelligent de réservation de cars interurbains

**Plateforme de réservation de transport interurbain avec assistant conversationnel basé sur l'intelligence artificielle.**

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7.5-EF4223?logo=codeigniter&logoColor=white)](https://codeigniter.com/)
[![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![AI](https://img.shields.io/badge/AI-Groq-8A2BE2)](https://groq.com/)
[![API](https://img.shields.io/badge/API-REST-green)](https://restfulapi.net/)

## 📌 Présentation du projet

Ce projet consiste à développer une plateforme numérique intelligente dédiée à la réservation de cars de transport interurbain.

L'objectif est de simplifier la recherche de voyages, la consultation des horaires et des tarifs, la sélection des sièges et la réservation de billets, tout en offrant une expérience utilisateur améliorée grâce à un assistant conversationnel basé sur l'intelligence artificielle.

La solution est conçue autour d'une architecture API REST permettant de séparer le backend de ses futures interfaces web et mobiles.

Le projet est actuellement développé dans le cadre d'un parcours de formation chez **Simplon Côte d'Ivoire**.

## 🎯 Objectifs

- Digitaliser le processus de réservation de transport interurbain.
- Faciliter la recherche de voyages et la consultation des disponibilités.
- Sécuriser les opérations de réservation et de gestion des comptes.
- Automatiser la génération des billets électroniques.
- Intégrer un assistant IA capable d'accompagner les voyageurs.
- Fournir des indicateurs de suivi aux administrateurs et aux compagnies de transport.

## ✨ Fonctionnalités principales

### 👤 Gestion des utilisateurs et authentification
- Inscription et connexion des voyageurs.
- Authentification sécurisée avec CodeIgniter Shield.
- Gestion des rôles et des permissions.
- Protection des routes API nécessitant une authentification.

### 🚌 Recherche de voyages
- Consultation publique des voyages disponibles.
- Recherche par ville de départ et d'arrivée.
- Filtrage par date de départ.
- Consultation des horaires, tarifs et compagnies.
- Consultation du nombre de sièges disponibles.

La consultation des voyages est accessible sans connexion. L'authentification est exigée au moment de la réservation.

### 🎫 Réservation et gestion des sièges
- Sélection des sièges disponibles.
- Création de réservations temporaires.
- Blocage temporaire des sièges pendant 15 minutes.
- Calcul automatique du montant de la réservation.
- Consultation de l'historique des réservations.
- Consultation et annulation des réservations selon leur statut.

### 💳 Paiement simulé
- Création de tentatives de paiement.
- Simulation de paiements par Mobile Money, carte bancaire ou espèces.
- Gestion des statuts de paiement.
- Confirmation automatique de la réservation après simulation réussie.

**Important :** les paiements sont actuellement simulés. Aucune transaction financière réelle n'est effectuée.

### 📄 Billets électroniques
- Génération automatique d'un billet après confirmation du paiement simulé.
- Consultation des billets.
- Téléchargement du billet au format PDF.
- Informations sur le voyageur, le trajet, les sièges et le paiement.

### 🤖 Assistant conversationnel IA
- Intégration de l'API Groq.
- Assistance conversationnelle aux voyageurs.
- Accès contrôlé aux informations pertinentes de la plateforme.
- Consultation contextuelle des voyages, réservations et billets de l'utilisateur authentifié.

### 📊 Tableaux de bord
- Tableau de bord administrateur.
- Tableau de bord des compagnies.
- Statistiques sur les utilisateurs, les voyages et les réservations.
- Suivi des paiements, des revenus enregistrés et des billets.

## 🛠️ Technologies utilisées

| Technologie | Utilisation |
|---|---|
| PHP 8.2 | Langage backend |
| CodeIgniter 4.7.5 | Framework backend |
| CodeIgniter Shield | Authentification et autorisations |
| MySQL / MariaDB | Gestion de la base de données |
| Groq API | Intelligence artificielle conversationnelle |
| Composer | Gestion des dépendances PHP |
| Swagger UI / OpenAPI | Documentation et test des API |
| Git / GitHub | Gestion du code source |

**Frontend prévu :** React pour l'interface web, avec une possibilité d'évolution vers une application mobile.

## 🏗️ Architecture du projet

Le projet repose sur une architecture backend API REST organisée autour des contrôleurs, modèles et services.

```text
soutenance-final_bd/
│
├── app/
│   ├── Config/
│   │   ├── Auth.php
│   │   ├── AuthGroups.php
│   │   ├── Cors.php
│   │   └── Routes.php
│   │
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ChatbotController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ReservationController.php
│   │   │   ├── TicketController.php
│   │   │   └── TripController.php
│   │   ├── CityController.php
│   │   └── CompanyController.php
│   │
│   ├── Database/
│   │   └── Migrations/
│   │
│   ├── Models/
│   ├── Services/
│   │   ├── AI/
│   │   ├── Payments/
│   │   ├── DashboardService.php
│   │   └── TicketService.php
│   │
│   └── Exceptions/
│
├── public/
│   └── docs/
│       ├── index.html
│       └── openapi.yaml
│
├── writable/
├── tests/
├── composer.json
├── composer.lock
└── README.md
```

## ⚙️ Installation et configuration

### 1. Prérequis

- PHP 8.2 ou supérieur compatible avec les dépendances du projet.
- Composer.
- MySQL ou MariaDB.
- Git.
- XAMPP ou un environnement PHP équivalent.

### 2. Cloner le projet

```bash
git clone https://github.com/koffi-bi/soutenance_final_simplon.git
```

```bash
cd soutenance_final_simplon
```

### 3. Installer les dépendances

```bash
composer install
```

### 4. Configurer les variables d'environnement

Créer un fichier `.env` à partir du fichier `env` fourni par CodeIgniter, puis renseigner les paramètres adaptés à ton environnement.

Exemple indicatif :

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = reservation_cars
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306

GROQ_API_KEY = 'VOTRE_CLE_API_GROQ'
```

Ne jamais publier le fichier `.env` ni les clés API sur GitHub.

### 5. Exécuter les migrations

Après avoir créé la base de données et configuré la connexion :

```bash
php spark migrate
```

### 6. Lancer le serveur local

```bash
php spark serve
```

L'application sera accessible à :

`http://localhost:8080`

## 📚 Documentation de l'API

La documentation interactive est disponible via Swagger UI :

`http://localhost:8080/docs/`

Le fichier OpenAPI est situé dans :

`public/docs/openapi.yaml`

### Principales routes API

| Méthode | Endpoint | Description |
|---|---|---|
| GET | `/api/cities` | Liste des villes |
| GET | `/api/companies` | Liste des compagnies |
| GET | `/api/trips` | Recherche des voyages |
| GET | `/api/trips/{id}` | Détails d'un voyage |
| POST | `/api/auth/register` | Inscription |
| POST | `/api/auth/login` | Connexion |
| GET | `/api/auth/me` | Profil authentifié |
| POST | `/api/reservations` | Création d'une réservation |
| GET | `/api/reservations` | Historique des réservations |
| POST | `/api/payments` | Création d'un paiement simulé |
| POST | `/api/payments/simulate` | Simulation du paiement |
| GET | `/api/tickets` | Liste des billets |
| GET | `/api/tickets/{id}/pdf` | Téléchargement du billet PDF |
| POST | `/api/chatbot/message` | Communication avec l'assistant IA |
| GET | `/api/admin/dashboard` | Statistiques administrateur |
| GET | `/api/company/dashboard` | Statistiques compagnie |

Les routes protégées nécessitent un jeton d'authentification Bearer, conformément aux permissions définies dans l'application.

## 🔐 Sécurité

Le backend intègre plusieurs mécanismes de sécurité :

- Authentification avec CodeIgniter Shield.
- Contrôle d'accès basé sur les rôles et permissions.
- Protection des données personnelles des voyageurs.
- Vérification de la propriété des réservations et billets.
- Validation des données reçues par les API.
- Contrôle de disponibilité des sièges côté serveur.
- Gestion des expirations de réservations temporaires.

## 🚀 Perspectives d'évolution

- Développement de l'interface web avec React.
- Adaptation de l'application aux terminaux mobiles.
- Déploiement du backend et de MySQL sur Railway.
- Intégration de véritables passerelles de paiement Mobile Money.
- Amélioration des fonctionnalités de fidélisation.
- Extension progressive du service à d'autres destinations en Afrique.

## 👨‍💻 Auteur

**Régis Yoh**

Technicien Supérieur en Informatique – Développement d'Applications

Apprenant chez **Simplon Côte d'Ivoire**

- GitHub : [@koffi-bi](https://github.com/koffi-bi)
- Dépôt du projet : [soutenance_final_simplon](https://github.com/koffi-bi/soutenance_final_simplon)

---

*Projet réalisé dans le cadre d'une soutenance de formation, avec une orientation vers la digitalisation du transport interurbain et l'intégration de l'intelligence artificielle.*

