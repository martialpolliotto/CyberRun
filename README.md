# CyberRun

Jeu navigateur PvP cyberpunk en français, dans la lignée des MMO textuels type Torn.com.

## Stack

- **Backend** : PHP 8.2 / CodeIgniter 4
- **Front** : HTMX + Alpine.js + Tailwind CSS (CDN au démarrage)
- **DB** : MariaDB 11
- **Infra dev** : Docker (Apache + mod_php, MariaDB, phpMyAdmin)
- **Hébergement prod** : VPS

## Installation locale

### Prérequis

- Docker + Docker Compose
- Git

### Démarrage

```bash
git clone https://github.com/martialpolliotto/CyberRun.git
cd CyberRun
cp env .env   # puis ajuster (voir section .env ci-dessous)
docker compose up -d --build
docker compose exec web composer install
```

### URLs locales

| Service     | URL                       |
|-------------|---------------------------|
| Application | http://localhost:8090     |
| phpMyAdmin  | http://localhost:8091     |
| MariaDB     | `localhost:33060` (host) ou `db:3306` (réseau Docker) |

### Credentials BDD (dev local uniquement)

- Base : `cyberrun`
- User : `cyberrun` / `cyberrun_dev`
- Root : `cyberrun_root`

### .env

Le `.env` doit pointer vers le service Docker `db` :

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8090/'

database.default.hostname = db
database.default.database = cyberrun
database.default.username = cyberrun
database.default.password = cyberrun_dev
database.default.DBDriver = MySQLi
database.default.port = 3306
```

### Commandes utiles

```bash
docker compose logs -f web        # suivre les logs Apache/PHP
docker compose exec web bash      # shell dans le container web
docker compose exec web php spark migrate   # lancer les migrations
docker compose down               # arrêter le stack
docker compose down -v            # arrêter + supprimer les données BDD
```

## Périmètre MVP

Boucle minimale jouable :

- Inscription / connexion
- Profil joueur (HP, énergie, nerve, crédits, XP, niveau, 4 stats)
- Gym : entraînement des 4 stats (Force, Blindage, Réflexes, Hack)
- Combat PvP instantané
- Cyberclinique (récupération HP)
- Top joueurs
- Chat global

**Hors MVP** (post-launch) : items, marché, factions, missions, monétisation.

## Licence

Projet privé pour le moment.
