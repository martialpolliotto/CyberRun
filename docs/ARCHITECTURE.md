# CyberRun — Architecture technique

Cette doc cible un développeur qui débarque sur le projet et veut comprendre comment c'est fait. Pour les mécaniques de jeu, voir [GAMEPLAY.md](GAMEPLAY.md). Pour les conventions Claude Code, voir [../CLAUDE.md](../CLAUDE.md).

---

## Vue d'ensemble

CodeIgniter 4.7 monolithe MVC server-rendered. Pas de SPA, pas de WebSocket. Les bouts d'interactivité utilisent **HTMX** pour les fragments AJAX et **Alpine.js** pour les états locaux.

```
Browser  ─── HTTP GET ────────► CI4 Controller → View (Bootstrap + HTMX + Alpine)
         ─── HTMX POST ───────► CI4 Controller → fragment HTML ou HX-Redirect
         ─── HTMX poll 3s ────► /chat/poll/{ch}/{lastId} → partial messages
                                       ▲
                                       │ tick chaque minute
                                CI4 spark command (TickCommand)
                                       │
                                       ▼
                                  MariaDB
```

---

## Stack & infra

| Brique | Version | Container Docker |
|--------|---------|------------------|
| PHP    | 8.2     | `cyberrun_web` (Apache + mod_php) |
| MariaDB| 11      | `cyberrun_db` |
| phpMyAdmin | 5   | `cyberrun_pma` |
| Cron   | —       | `cyberrun_cron` (lance `php spark cyberrun:tick` chaque minute) |

Pas de Redis, pas de queue, pas de worker — tout passe par MariaDB.

Front livré 100% CDN : Bootstrap 5.3 + Bootstrap Icons + HTMX 2.0.3 + Alpine.js 3.14. Pas de bundler.

---

## Organisation du code

```
app/
├── Commands/        Spark commands (TickCommand)
├── Config/          Routes, Filters, Security, Database, Auth (Shield)
├── Controllers/     1 controller par domaine + Admin/ pour le backoffice
│   ├── BaseController        helpers globaux (isHtmx, htmxRedirect, me, requireMe, resolveUsername)
│   └── Admin/                gestion items, vendors, fixers, missions, crimes, settings, bots, PlayerTools
├── Database/Migrations/      historiquement append-only, jamais éditées rétroactivement
├── Filters/         FreeFilter (bloque /pages quand prison/cyberclinique)
├── Helpers/         time_helper, player_helper (autoloadés via BaseController)
├── Language/        fr/ et en/ pour Log.php + Validation
├── Models/          1 model par table principale, returnType=array, useTimestamps=true
├── Services/        logique métier complexe (BotService, ChatService, CombatService, ActivityLogger)
└── Views/           1 dossier par domaine + partials/ + layouts/
```

---

## Patterns transversaux

### 1. Atomicité des crédits (et plus largement, des mutations)

Tout ce qui modifie `players.credits` passe par 2 helpers sur `PlayerModel` :

```php
$playerModel->debitAtomic($playerId, $amount);     // retourne false si solde insuffisant
$playerModel->creditUnconditional($playerId, $amount);
```

`debitAtomic` fait un `UPDATE players SET credits = credits - ? WHERE id = ? AND credits >= ?` puis check `affectedRows()`. C'est ce check-et-update en 1 requête qui empêche les **races TOCTOU** (Time Of Check To Time Of Use) où 2 requêtes parallèles passaient toutes les deux le check PHP avant qu'aucune n'écrive.

Le même pattern s'applique aux autres invariants (stock bazaar, quantity inventory, status bounty, etc.) : préférer `UPDATE ... WHERE <invariant>` à `find → check → update`.

Pour les opérations qui ne tiennent pas dans un seul UPDATE (ex: serializer N requêtes par joueur), utiliser **`SELECT ... FOR UPDATE`** pour poser un row lock le temps de la transaction (cf. `ChatService::send` pour l'antiflood).

### 2. Transactions

Utiliser `db_connect()->transStart()` + `transComplete()` pour grouper des écritures qui doivent être atomiques. Les helpers comme `debitAtomic` ne wrappent PAS leur propre transaction : le caller décide du scope.

Exemple complet :

```php
$db = db_connect();
$db->transStart();
if (! $playerModel->debitAtomic($buyerId, $total)) {
    $db->transRollback();
    return ['ok' => false, 'message' => 'Solde insuffisant.'];
}
$playerModel->creditUnconditional($sellerId, $net);
// ... autres écritures
$db->transComplete();
```

### 3. HTMX

| Helper | Rôle |
|--------|------|
| `BaseController::isHtmx()` | détecte `HX-Request: true` |
| `BaseController::htmxRedirect($url)` | renvoie 204 + `HX-Redirect: <url>` header (force un full navigate client-side) |

Pattern flash + redirect :

```php
session()->setFlashdata('error', $msg);
return $this->htmxRedirect('/jail');
```

CSRF :
- `Filters.php` applique `csrf` globalement en `before`
- `Security.php` : `regenerate = false` pour ne pas casser les formulaires HTMX qui resoumetent le même token
- Exemption pour `chat/send` (form persistant côté client)

### 4. Game settings (configuration runtime)

Table `game_settings` (key / value / type / category). Lire :

```php
$cost = (int) model(GameSettingModel::class)->get('faction_create_cost', 100000);
```

Éditer via l'admin `/admin/game-settings`. Catégories actuelles : `bazaar`, `chat`, `combat`, `crime`, `faction`, `jail`, `job`.

C'est la seule source de paramétrage runtime — éviter les constantes PHP pour tout ce qui pourrait avoir besoin d'être tweaké en prod.

### 5. Helpers globaux autoloadés

`BaseController::initController` autoload `player` et `time`. Donc partout dans les controllers + views :

```php
relative_short($datetime);     // "5s", "12m", "3h", "2d", "1w"
resolve_username($playerId);   // pseudo via join users, fallback 'inconnu'
```

Les services (non-Controller) doivent appeler `helper('player')` ou `helper('time')` explicitement.

### 6. Routes & filters

```php
$routes->group('', ['filter' => 'session'], ...);                  // page d'accueil + login
$routes->group('', ['filter' => ['session', 'free']], ...);        // pages action : bloquées si prison/clinique
$routes->group('admin', ['filter' => ['session', 'group:admin']]); // backoffice
```

`FreeFilter` redirige vers `/jail` ou `/profile` si le joueur est en prison ou cyberclinique.

### 7. Activity log + i18n

Activity log via `ActivityLogger::log($playerId, $category, $key, $params, $targetId, $refId)`. La row stocke `action_key` (ex: `Log.crime_success`) + `_params` JSON. Le rendu côté view utilise `lang($action_key, $params)` qui interpole les `{placeholder}`.

Clés dans `app/Language/{fr,en}/Log.php`. Les params `target` et `author` sont auto-linkifiés vers `/u/{username}` au rendu (cf `app/Views/logs/index.php`).

### 8. Bots

`BotService::tickAll()` est appelé chaque tick. Pour chaque bot dans une plage horaire active, on choisit une action selon son persona (crime / lab / mission / buy / idle…). Les bots passent par les **mêmes models** que les humains pour éviter la divergence (`PlayerModel::consume`, `CrimeModel::attempt`, etc.).

Côté autres joueurs, un bot est **indistinguable** d'un humain : il a un user Shield avec password inutilisable, des stats randomisées, une activité log, etc.

### 9. Convention de view

- Bootstrap 5 noir/blanc, jamais de couleur vive sauf status badges (`bg-secondary` pour hospital, `bg-dark` pour jail/équipé)
- Layout `max-width: 1380px` centré, sidebar 280px à gauche + main
- Alpine `x-data` au niveau le plus local possible (ne pas mettre `x-data` sur `<body>`)
- HTMX `hx-target` doit toujours pointer un ID existant et stable
- Une vue trop longue se découpe en partials (`partials/X.php`)

### 10. Channels chat

`ChatService::PUBLIC_CHANNELS` est la source unique des channels ouverts à tous. Ajouter un channel = ajouter une ligne à cette constante. Les channels scoped (`faction-{id}`, `company-{id}` à venir) sont gérés via regex dans `canPostToChannel` / `visibleChannels`.

Mute personnel : table `player_mutes` séparée (distincte de `player_relations.enemy` pour ne pas overloader la sémantique).

---

## TickCommand (cron 1×/min)

Ordre des étapes dans `app/Commands/TickCommand.php` :

1. **Regen** : `UPDATE players SET energy/nerve/hp_current = LEAST(max, current + N)`
2. **Salary** (sous `GET_LOCK('cyberrun_tick_salary')` pour serialiser) : daily payout si `HOUR(NOW()) >= job_salary_payout_hour` et `DATE(last_salary_at) < CURDATE()`. Inclut +XP job, +stats job (mapping via `jobs.stat_1/stat_2`)
3. **Auto-promotion** : `job_position_id` mis à jour au plus haut rank débloqué par `job_xp`
4. **Bots** : `BotService::tickAll()` (actions automatiques)
5. **Chat prune** : pour chaque channel actif, garde les N dernières lignes (config `chat_history_keep_per_channel`)

---

## Services principaux

| Service | Rôle |
|---------|------|
| `ActivityLogger` | écrit dans `activity_logs` avec clé i18n + params |
| `BotService` | sélection persona-driven d'actions au tick |
| `ChatService` | validation/antiflood/censure/blocage liens/registry channels |
| `CombatService` | turn-based : initiate / takeTurn / resolveTurn / postAction (mug/hospitalize/leave) |

Les services ne sont **pas** des classes anémiques : ils encapsulent la logique métier et préservent les invariants.

---

## Models principaux

Tous étendent `CodeIgniter\Model`, `returnType=array`, `useTimestamps=true`. Pattern : `find()` retourne `?array`, les méthodes métier renvoient `array{ok:bool, message:string, ...}`.

| Model | Notes |
|-------|-------|
| `PlayerModel` | hub central : credits, XP, stats, jail, hospital, consume, train, addiction, transfer, payBail, debitAtomic, creditUnconditional |
| `PlayerItemModel` | inventaire : ensureStarterKit, equip/unequip, **addStackable** (utilisé par bazaar, crime drops futurs, etc.) |
| `BazaarListingModel` | listFromInventory / unlist / buy. Atomicité via guard `WHERE quantity >= ?` |
| `BountyModel` | place / cancel / claim (atomique via guard `WHERE status='active'`) |
| `ChatMessageModel` | latest / fetchSince / recentSendCountsMulti (1 query pour 3 fenêtres antiflood) / pruneChannel |
| `CrimeModel` | attempt (transactionnelle, rewards + mission tracking + faction respect tous dans la transaction) |
| `FactionModel` / `FactionMemberModel` / `FactionApplicationModel` | create, apply, accept (avec re-check `FOR UPDATE` du candidat), kick, leave, donate, addRespect |
| `MessageModel` | send / inbox listThreads / thread / markThreadRead |
| `PlayerMuteModel` | mute / unmute / mutedIdsFor (séparé des relations) |
| `MissionModel` | trackEvent / recheckThresholdsForPlayer / claim |

---

## Migrations

- **Append-only** : on ajoute une migration, on ne modifie pas les anciennes
- Une migration = un changement cohérent + sa documentation au début du fichier (commentaire docblock)
- `down()` doit toujours être implémenté (utilisé en dev pour rollback rapide)
- Les seed/insert de référence (game_settings, etc.) se font dans la migration qui crée la table

Migrations notables :
- `_CreateGameSettings.php` : infra config runtime
- `_CreateCombatTables.php` : système combat V2
- `_CreateJobsTables.php` : jobs cyberpunk
- `_CreateFactionsTables.php` : factions + 5 settings
- `_CreateMessagesTable.php` : messagerie 1-to-1
- `_CreateChatTables.php` : chat + 9 settings
- `_CreateBazaarTables.php` : listings + 2 settings
- `_CreatePlayerMutesTable.php` : mute chat séparé

---

## Sécurité

- **CSRF** activé globalement via `Filters.php`, token rotation désactivée (`regenerate=false`) pour HTMX, exemption pour `chat/send`
- **SQL injection** : tous les `RawSql` injectent uniquement des entiers castés (`(int)$amount`), jamais de string utilisateur. Tous les paramètres user passent par `?` placeholders ou builder.
- **XSS** : `esc()` systématique sur tout output. Les rares HTML injectés (liens dans logs/chat) sont construits côté serveur.
- **Auth** : Shield. Filter `session` global sur toutes les pages player, `group:admin` pour `/admin`.
- **Open redirect** : `Bazaar::safeReturnTo` whitelist les chemins internes uniquement (pas de `//evil.com`).

Trous restants (à fixer) :
- Pas de rate limiting sur les routes critiques (sauf chat antiflood)
- Pas de validation honeypot sur les formulaires register/login
- CSRF token rotation désactivée (compromise sécurité ↔ HTMX)

---

## Conventions de code

- `protected $returnType = 'array'` sur tous les models
- Fonctions privées en `lowerCamelCase`, méthodes publiques pareil
- Constantes en `SCREAMING_SNAKE_CASE`
- Pas de docblock juste pour répéter le nom de la méthode ; en ajouter quand le "pourquoi" est non-obvie
- 1 message d'erreur user-friendly par cas (jamais des stack traces dans la réponse)
- Préférer des helpers (PlayerModel::debitAtomic, PlayerItemModel::addStackable) à du code inline répété
- Pas de seed via fixtures externes : tout dans les migrations

---

## Roadmap technique (court terme)

- Centraliser le mapping HP↔Life dans un service `StatLabels` ou des langfiles
- Sortir le CSS/JS du chat widget en assets externes (cachables)
- Découper la vue `inventory/index.php` (300+ lignes) en partials
- Profiler les pages lourdes (sidebar.php + chat_widget.php → ~3 findByUserId par page)
- Ajouter `members_count` vu/dérivé COUNT pour réduire la drift
- Companies (feature future, channel chat déjà en placeholder)
- Faction wars
