# CyberRun — Instructions Claude

Cyberpunk browser game type Torn. CodeIgniter 4 + MariaDB en Docker, vue Bootstrap 5 noir/blanc, HTMX + Alpine.js pour les interactions client.

## Stack

- **PHP 8.2** / CodeIgniter 4.7
- **MariaDB 11** (container `cyberrun_db`)
- **Apache** (container `cyberrun_web`, port 8090)
- **phpMyAdmin** (port 8091)
- **Cron tick** : 1× par minute via container `cyberrun_cron` qui lance `php spark cyberrun:tick`
- Front : Bootstrap 5.3 + Bootstrap Icons + HTMX 2.0.3 + Alpine.js 3.14 (tous CDN)
- Auth : Shield

## URLs & commandes

- App : http://localhost:8090
- phpMyAdmin : http://localhost:8091
- Repo : `~/dev/cyberrun` côté WSL, accessible aussi `\\wsl.localhost\Ubuntu-22.04\home\martial\dev\cyberrun\`
- Commandes via WSL :
  - `wsl -d Ubuntu-22.04 -- bash -c 'cd ~/dev/cyberrun && <cmd>'`
  - Migrate : `docker compose exec -T web php /var/www/html/spark migrate`
  - Tick manuel : `docker compose exec -T web php /var/www/html/spark cyberrun:tick`
  - Lint PHP : `php -l <file>` (PHP 8.2 dispo côté WSL host)

## Patterns importants

### Atomicité crédits

Toujours via `PlayerModel::debitAtomic($playerId, $amount): bool` (avec guard `WHERE credits >= ?` + check `affectedRows`) et `PlayerModel::creditUnconditional($playerId, $amount): void`. Pas de `RawSql('credits - X')` inline. Wrap dans `transStart()/transComplete()` quand combiné avec d'autres écritures.

### TOCTOU / races

Le pattern `find() → check PHP → update()` est interdit pour les mutations atomiques. Toujours `UPDATE ... WHERE <invariant>` + lire `affectedRows`. Sinon, `SELECT ... FOR UPDATE` pour sérialiser (utilisé dans ChatService::send par exemple).

### HTMX

- `BaseController::isHtmx()` détecte `HX-Request: true`
- `BaseController::htmxRedirect($url)` retourne 204 + `HX-Redirect` header
- Si critique/post-redirect, faire `session()->setFlashdata('error', $msg)` avant `htmxRedirect` pour porter le message
- CSRF : `regenerate=false` en config Security.php pour permettre les soumissions HTMX répétées. Filter `csrf` actif globalement, `chat/send` exempté.

### Tick cron

`TickCommand` :
- Regen Life/NRG/NRV
- Salary payout daily (sous `GET_LOCK('cyberrun_tick_salary')` pour serialiser)
- Auto-promotion jobs
- BotService::tickAll (actions auto des bots)
- Chat prune (garde N dernières par channel)

### Game settings

Table `game_settings` (key/value typé). Lire via `model(GameSettingModel::class)->get('key', $default)`. Edit via `/admin/game-settings`. Catégories : `bazaar`, `chat`, `combat`, `crime`, `faction`, `jail`, `job`.

### Helpers globaux

Autoloadés via `BaseController::initController` (`$this->helpers = ['player', 'time']`) :
- `relative_short($datetime)` — "5s", "12m", "3h", "2d", "1w"
- `resolve_username($playerId)` — pseudo via join users, fallback 'inconnu'

### i18n

Logs activité via `lang('Log.key', $params)`. Clés dans `app/Language/{fr,en}/Log.php`. Le rendu dans `app/Views/logs/index.php` esc() les params et linkify `target`/`author`.

### Conventions

- Bootstrap noir/blanc, jamais de couleurs vives sauf badges status
- Layout `max-width: 1380px` centré, sidebar 280px + main
- Inline form atomique de préférence à modal (sauf transfer credits / bounty placement)
- Channel chat : voir `ChatService::PUBLIC_CHANNELS` const = source unique
- Mute chat = `player_mutes`, séparé de `player_relations.enemy`

## Mémo gameplay

- **Stats joueur** : Life (= HP en BDD), NRG, NRV. Regen 5 Life / 2 NRG / 1 NRV par tick.
- **XP** : caché en front à terme (note utilisateur), profile/leaderboards/messages.
- **Stats combat** : force, blindage, réflexes, hack — cachées sur fiche autres joueurs.
- **Stats job** : tech, endurance, charisme — visibles, payées daily à heure configurable.
- **Crimes** : par catégories, nerve cost, variantes texte success/fail/critical.
- **Combat V2** : turn-based, attaque/garde/fuite, 25 nerve à l'init, mug/hospitalize/leave post-victoire.
- **Bazaar** : P2P listings, fee sink 5%.
- **Factions** : create 100k¢ + niv 5, respect via crimes/hospitalize members, treasury via donations.
- **Bounties** : sur la tête d'un joueur, claim auto sur hospitalize, refund si cancel.
- **Chat** : widget bottom-right floating, channels global/trade/débutants/company/faction. Polling 3s HTMX.

## Préférences user

- Réponses concises, focus sur l'action
- Ne JAMAIS push sans demander explicitement à chaque fois
- Commits avec messages descriptifs (Co-Authored-By Claude)
- Toujours valider en navigateur côté user, pas auto-test
