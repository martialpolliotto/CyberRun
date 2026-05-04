# CyberTown

Jeu navigateur PvP cyberpunk en français, dans la lignée des MMO textuels type Torn.com.

## Stack

- **Backend** : PHP 8.2+ / CodeIgniter 4
- **Front** : HTMX + Alpine.js + Tailwind CSS (via CDN au démarrage)
- **DB** : MySQL 8
- **Hébergement** : VPS

## Installation locale (dev)

```bash
git clone https://github.com/martialpolliotto/CyberTown.git
cd cybertown
composer install
cp env .env
# Editer .env : CI_ENVIRONMENT=development, database, baseURL...
php spark serve
```

Ouvrir <http://localhost:8080>.

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
