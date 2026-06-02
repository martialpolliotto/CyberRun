# CyberRun

Jeu navigateur PvP cyberpunk en français, dans la lignée des MMO textuels type [Torn.com](https://torn.com). Économie, combat tour-par-tour, factions, crimes, jobs passifs, bazaar joueur-à-joueur, messagerie privée et chat live.

## Stack

- **Backend** : PHP 8.2 / CodeIgniter 4.7 + Shield (auth)
- **Front** : Bootstrap 5.3 noir/blanc + Bootstrap Icons + HTMX 2.0 + Alpine.js 3.14 (tous CDN)
- **DB** : MariaDB 11
- **Infra dev** : Docker Compose (Apache + mod_php, MariaDB, phpMyAdmin, cron)
- **Cron** : 1× par minute, fait la regen Life/NRG/NRV, la paie des jobs, les actions des bots, et le prune chat

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
docker compose exec web php spark migrate
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
docker compose logs -f web                       # suivre les logs Apache/PHP
docker compose exec web bash                     # shell dans le container web
docker compose exec web php spark migrate        # lancer les migrations
docker compose exec web php spark cyberrun:tick  # forcer un tick manuel
docker compose down                              # arrêter le stack
docker compose down -v                           # arrêter + supprimer les données BDD
```

## Documentation

- **[docs/GAMEPLAY.md](docs/GAMEPLAY.md)** — Mécaniques du jeu côté joueur : stats, crimes, combat, jobs, factions, bazaar, etc.
- **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** — Architecture technique : organisation du code, patterns (HTMX, atomicité DB, game settings), tick cron, i18n.
- **[CLAUDE.md](CLAUDE.md)** — Instructions et conventions pour les sessions Claude Code.

## Périmètre actuel

MVP de toutes les mécaniques principales en place :

- Inscription / connexion (Shield)
- Profil joueur (Life / NRG / NRV / crédits / XP / niveau / 4 stats combat + 3 stats job)
- Lab : entraînement des stats combat (Force, Blindage, Réflexes, Hack) contre NRG
- Crimes (catégories, variantes texte success/fail/critical, prison)
- Combat V2 tour-par-tour (attaque / garde / fuite, mug / hospitalize / leave)
- Cyberclinique + prison + bust/bail
- Jobs cyberpunk 7 métiers + 3 stats job (tech / endurance / charisme), paie passive quotidienne
- Lab (training stats combat) + Shops PNJ + consommables (boosters / drugs avec addiction et overdose)
- Inventaire avec catégories, achat/vente PNJ et bazaar P2P 5% fee sink
- Bounties (placer / annuler / claim automatique sur hospitalize)
- Factions MVP (créer / postuler / accepter / kicker / donations trésorerie / respect)
- Messagerie privée 1-to-1 + chat live widget bottom-right (channels global / trade / débutants / company / faction)
- Activity log + i18n FR/EN
- Bots indistinguables des humains, actions automatiques au tick
- Admin tools (gestion items / vendeurs / fixers / missions / crimes / settings / bots / tweak persos)

## Licence

Projet privé pour le moment.
