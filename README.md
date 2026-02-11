# WorldCup 2026

Application web de suivi de la Coupe du Monde de Football 2026 (USA / Canada / Mexique).

## Fonctionnalités

- Consultation des matchs (liste, filtres, détail)
- Scores en temps réel (polling 5 secondes)
- Classements par groupe (A à L)
- Liste des 48 équipes participantes
- Liste des 16 stades
- Interface d'administration pour gérer les scores

## Stack Technique

| Composant | Technologie |
|-----------|-------------|
| Backend | Symfony 6.4 LTS |
| Frontend | React + Vite |
| Base de données | MySQL 8.0 |
| ORM | Doctrine |

## Installation

### Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+

### Backend

```bash
cd worldcup-backend
composer install
```

Configurer la base de données dans `.env` :
```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/worldcup2026?serverVersion=8.0"
```

Créer la base et charger les données :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

Lancer le serveur :
```bash
php -S localhost:8000 -t public/
```

### Frontend

```bash
cd worldcup-frontend
npm install
npm run dev
```

L'application est accessible sur `http://localhost:5173`

## Identifiants Admin

Les identifiants admin sont configurés dans le fichier `.env.local` du backend :
- `ADMIN_EMAIL`
- `ADMIN_PASSWORD`

## API Endpoints

### Publics

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/teams` | Liste des équipes |
| GET | `/api/teams/{id}` | Détail d'une équipe |
| GET | `/api/stadiums` | Liste des stades |
| GET | `/api/phases` | Liste des phases |
| GET | `/api/matches` | Liste des matchs |
| GET | `/api/matches/live` | Matchs en cours |
| GET | `/api/standings/{group}` | Classement d'un groupe |

### Admin (authentification requise)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/login` | Connexion |
| POST | `/api/logout` | Déconnexion |
| POST | `/api/admin/matches/{id}/start` | Démarrer un match |
| PATCH | `/api/admin/matches/{id}/score` | Modifier le score |
| POST | `/api/admin/matches/{id}/finish` | Terminer un match |

## Structure du projet

```
partiel_foot/
├── worldcup-backend/           # Backend Symfony
│   ├── src/
│   │   ├── Controller/     # Controllers API REST
│   │   ├── Entity/         # Entités Doctrine
│   │   ├── Repository/     # Requêtes personnalisées
│   │   └── Service/        # Logique métier
│
├── worldcup-frontend/      # Frontend React
│   └── src/
│       ├── components/     # Composants réutilisables
│       ├── pages/          # Pages de l'application
│       ├── hooks/          # Hooks personnalisés
│       ├── services/       # Appels API
│       └── styles/         # Fichiers CSS
```
