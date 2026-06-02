# CyberRun — Guide gameplay

Tout ce qu'il faut savoir pour jouer. Pour le côté technique (code), voir [ARCHITECTURE.md](ARCHITECTURE.md).

---

## 1. Tes ressources

Trois jauges centrales, visibles en permanence dans la sidebar gauche :

| Jauge | À quoi ça sert | Regen |
|-------|----------------|-------|
| **Life** | Tes points de vie. À 0, tu es K.O. en combat ou suite à une overdose. | +5 / min (jusqu'au max) |
| **NRG** (Énergie) | Dépensée pour t'entraîner au Lab, pour certains items et actions. | +2 / min |
| **NRV** (Nerve) | Dépensée pour les crimes, l'engagement en combat (25 NRV à l'init), le bust de prison. | +1 / min |

À côté : **Crédits** (¢ — la monnaie), **Niveau** et XP.

L'XP est cachée à terme côté front (volonté gameplay : éviter le min-max).

---

## 2. Tes stats

### Stats combat (cachées sur la fiche des autres joueurs)

- **Force** : dégâts en attaque
- **Blindage** : réduction des dégâts reçus
- **Réflexes** : esquive + chance d'évasion de prison
- **Hack** : utilisé par certains crimes et items

Tu les montes au **Lab** en dépensant de la NRG (`/lab`).

### Stats job (visibles)

- **Tech**
- **Endurance**
- **Charisme**

Tu ne les montes **pas activement** : la paie quotidienne de ton job te donne automatiquement des points dans les 2 stats associées au métier que tu exerces.

---

## 3. Les crimes

Page : `/crimes`. Catégories visibles dans la sidebar du dashboard crimes.

### Comment ça marche

Chaque crime a :
- un **coût en NRV**
- un **% de succès** estimé (basé sur ta stat dominante de catégorie + XP cumulé sur cette catégorie)
- un **% d'échec critique** (te met en prison ou cyberclinique selon le crime)
- des **récompenses** en crédits + XP joueur + XP catégorie

3 issues possibles :
- **Succès** : tu encaisses la récompense
- **Échec simple** : tu perds la NRV, rien d'autre
- **Échec critique** : prison ou cyberclinique, durée variable

Les textes de narration sont **variantes** : chaque crime a plusieurs textes possibles pour success/fail/critical, choisis aléatoirement, et certaines variantes overrident les crédits/XP de base.

### Conséquences

- **Prison** : tu es bloqué sur `/jail`, tes pages d'action te redirigent. Tu peux tenter une **évasion** (coûte de la NRV, chance basée sur Réflexes/2 plafonnée). Si tu rates, la peine s'allonge. Un autre joueur peut payer ta **caution** (bail) ou tenter un **bust** depuis ta fiche.
- **Cyberclinique** : pareil mais sur `/profile`. Tu sors à 0 automatiquement. Régen continue pendant l'hospitalisation.

---

## 4. Le Lab (entraînement)

`/lab` : tu paies de la NRG pour gagner 1 point dans la stat de combat de ton choix. C'est le seul moyen pour le joueur de monter activement ses stats combat (les ennemis vaincus ne donnent pas de stats).

Le coût NRG et le gain par session sont configurables côté admin (`game_settings`).

---

## 5. Combat PvP

### Engager un combat

Sur la fiche d'un joueur (`/u/{pseudo}`), bouton **⚔ Attaquer**. Coût : **25 NRV** à l'init. Tu ne dépenses plus rien tour par tour ensuite.

Tu ne peux pas attaquer un joueur en prison, cyberclinique, ou toi-même.

### Le combat

Système turn-based simplifié :

| Action | Disponible pour | Effet |
|--------|-----------------|-------|
| **⚔ Attaquer** | Tous | Dégâts ≈ Force – Blindage adverse (modulé par roll) |
| **🛡 Garder** | Défenseur uniquement | Réduit fortement les dégâts du prochain coup |
| **↻ Fuir** | Tous | Chance basée sur Réflexes ; si succès, combat ended |

Quand un joueur atteint 0 Life ou fuit avec succès, le combat se termine. Si tu gagnes, tu choisis ensuite :

| Post-action | Effet |
|-------------|-------|
| **💰 Mug** | Tu voles un % des crédits de la victime |
| **✚ Hospitalize** | Tu l'envoies en cyberclinique pour 10-30 minutes, et tu **claim automatiquement toutes les bounties actives** sur sa tête. Tu gagnes +5 respect pour ta faction si tu es membre. |
| **↩ Partir** | Aucun effet supplémentaire |

Les bots adverses jouent automatiquement (attaque sauf si HP < 30%, alors essaient de fuir).

---

## 6. Les jobs

7 métiers cyberpunk : **ripperdoc**, **decker**, **corpo-guard**, **fixer**, **info-broker**, **netrunner**, **enforcer**. (Liste exacte dans `app/Modules/...` — checke l'admin.)

### Mode passif (style Torn)

- Tu **postules** une fois à un job, tu deviens employé au rank 1
- **Chaque jour à une heure configurable** (par défaut 8h), tu reçois automatiquement :
  - le salaire (¢)
  - 10 XP de job
  - 1 point dans chacune des 2 stats associées au métier
- À mesure que ton **XP de job** monte, tu es **automatiquement promu** au rank supérieur quand tu débloques le seuil (salaire qui augmente)

Tu ne fais **rien** activement — il suffit de garder le job.

Quitter un job remet ton XP de job à 0.

---

## 7. Inventaire et items

`/inventory` : interface compacte avec icônes catégories en haut.

| Catégorie | Exemples |
|-----------|----------|
| Tous, Équipés, Disponibles | filtres globaux |
| **Armes** | armes principales/secondaires |
| **Protection** | bottes, combinaisons, optiques |
| **Cyberware** | cyberdecks |
| **Boosters** | consommables non-addictifs |
| **Drogues** | consommables addictifs avec risque overdose |

Pour chaque item :
- **× quantité** affichée
- Icônes d'action : **Consommer** (consommable) / **Équiper** ou **Déséquiper** (gear) / **Cash** (vendre au marchand) / **Cash-coin** (mettre sur le bazaar)

### Vendre au marchand (PNJ)

Bouton **N¢/u** dans la ligne. Vente instantanée à 50% du prix de base (configurable). Les items hors-circuit ne sont pas rachetés.

### Mettre sur le bazaar (P2P)

Bouton **cash-coin** ouvre un mini-form inline : quantité + prix unitaire. L'item est retiré de ton inventaire et apparaît sur ta fiche publique `/u/{pseudo}` dans la section "Bazaar".

---

## 8. Consommables : boosters et drogues

### Boosters

- Effet temporaire positif (+stats, regen, etc.)
- **Pas d'addiction** ni de risque overdose
- Cooldown propre à chaque item

### Drogues

- Effet plus fort que les boosters mais avec contreparties :
  - **Addiction** : monte à chaque conso. Tiers d'addiction donnent des **malus de stats** et augmentent le **risque d'overdose**
  - **Overdose** : si tu rate ton roll, tu vas en cyberclinique 30 min à plusieurs heures
- L'addiction **décay lentement** : elle baisse passivement avec le temps si tu arrêtes

---

## 9. Bazaar (marché joueur)

Ton bazaar est visible sur ta fiche publique. Les autres joueurs voient tes annonces et peuvent acheter directement.

- **Tu fixes le prix unitaire**
- **Fee 5%** sur la vente côté vendeur (anti-inflation, ça disparaît du circuit)
- **Max 50 listings** actifs simultanés par joueur
- Annulation possible à tout moment : les items reviennent dans ton inventaire

Tu peux gérer tes listings depuis `/bazaar/mine` ou directement depuis `/inventory`.

---

## 10. Bounties (primes)

`/bounties` liste les primes actives par ordre de montant.

### Placer une prime

Sur la fiche d'un joueur, bouton **☠ Prime**. Tu fixes le montant et un message optionnel. Les crédits sont débités immédiatement.

### Annuler une prime

Sur la fiche du joueur ciblé, bouton **×** à côté de **ta** prime (visible uniquement par toi). Refund total des crédits.

### Claim

Quand un joueur **hospitalise** un autre joueur lors d'un combat, **toutes les bounties actives** sur la cible sont automatiquement **claimed** par le vainqueur. Pas de pari : c'est first-come-first-served via combat.

---

## 11. Factions

Inspiré des factions Torn. MVP actuel :

### Créer une faction

- Coût : **100 000 ¢** (configurable)
- Niveau minimum : **5**
- Tu deviens **leader**

### Rejoindre une faction

- Aller sur `/factions`, choisir une faction, page publique → **Postuler**
- 1 candidature pending max
- Le leader accepte ou refuse

### Vie de faction

- **Trésorerie** : alimentée par les donations des membres
- **Respect** : +1 par crime réussi par un membre, +5 par hospitalize infligée
- **Membres** : liste, contributions individuelles visibles

### Quitter / dissoudre

- Membre : quitte librement
- Leader : ne peut quitter que s'il est seul (= dissolution). Sinon il doit kicker tout le monde d'abord (transfert de leadership à venir)

---

## 12. Messagerie et chat

### Messagerie privée (`/messages`)

Modèle Torn mail : 1-to-1, persistant, threadé.

- Inbox : liste des conversations avec badge unread
- Cliquer un thread → tu vois tout l'historique, marqué comme lu automatiquement
- Form en bas pour répondre / envoyer un nouveau message
- L'icône ✉ sur la fiche d'un joueur ouvre directement un thread avec lui

### Chat live (widget bottom-right)

Présent sur **toutes les pages** une fois connecté.

- Icône chat en bas à droite avec badge rouge si nouveaux messages quand fermé
- Click → panel float avec tabs des channels disponibles
- **Channels** : Global / Trade / Débutants / Company (placeholder) / [TAG] de ta faction
- Polling 3s (uniquement quand le panel est ouvert)
- Mentions `@pseudo` auto-linkifées
- Lien plein écran → `/chat` pour une vue large

#### Modération

- **Antiflood** : 3 couches superposées : 1 message/2s + 5 messages/10s + 10 messages/min
- **Liens externes interdits** (http://, https://, www.) : le message est rejeté
- **Censure** : mots dans la blacklist remplacés par `***`
- **Mute personnel** : tu peux mute un joueur, ses messages disparaissent de ton widget (mute ≠ enemy relation)

---

## 13. Missions et fixers

Les **fixers** sont des PNJ donneurs de missions. Visibles dans le hub Chrome City.

Chaque fixer a une **chaîne de missions** (mission 1 → mission 2 → ...). Chaque mission a :
- un **brief** narratif quand tu acceptes
- un **objectif** (commettre N crimes d'une catégorie, équiper un type d'item, visiter une page, etc.)
- un **outro** quand tu réclames
- des **récompenses** : crédits, XP, parfois un item

Tu débloques le fixer suivant en finissant le précédent.

---

## 14. Social PvP sur la fiche d'un joueur

Sur `/u/{pseudo}`, tu vois :

- **Stats combat de la cible** : cachées (juste catégorie globale visible)
- **Compteurs combat** : kills, deaths, kill streak, best kill streak, attacks won/lost
- **Status** : libre / prison / cyberclinique avec compteur
- **Bounties actives** sur sa tête
- **Bazaar du joueur** (s'il a des listings)

### Actions inline

| Bouton | Action |
|--------|--------|
| ⚔ Attaquer | démarre un combat |
| ✉ Msg | ouvre un thread de messagerie privée |
| ¢ Argent | envoyer des crédits (transfer) |
| ☠ Prime | placer une bounty |
| ◉ Espion | stub (à venir) |
| Relations | ajouter aux amis / ennemis / cibles |

---

## 15. Chrome City (hub)

`/city` : centralise les liens vers Lab, Shops, Fixers, etc. Page d'entrée naturelle après la connexion.

---

## 16. Shops PNJ

3 vendeurs PNJ avec catalogue. Acheter consomme des crédits, revendre via `/inventory` rend 50% du prix.

---

## 17. Bots

Le serveur héberge des **bots automatiques** qui :
- Ont des pseudos randomisés
- Sont indistinguables des humains côté UI (même fiche, mêmes actions, mêmes logs)
- Agissent chaque minute selon leur **persona** (crime / lab / mission / buy / idle / ...)
- Ont des plages horaires d'activité (style humains)

Donc le monde "vit" même sans joueurs humains connectés.

---

## 18. Activity log

`/log` montre **tout ce qui t'arrive** ou ce que tu fais subir aux autres. Filtres par catégorie (crime, combat, social, eco, mission, status, level) et par période (heure / jour / semaine).

Les pseudos sont cliquables (vers `/u/{pseudo}`).

---

## 19. Admin (si tu en es)

`/admin` : gestion items, vendeurs, fixers, missions, crimes (catégories + crimes + variantes texte), game settings runtime, bots (créer/peupler en masse), tweak persos (ajuster ses propres stats pour debug/playtest).

---

## Astuces

- **NRV** se régen lentement (1/min) : tes crimes sont bridés par ça plus que par la NRG
- **NRG** est le sink majeur du Lab
- **Hospitalize est plus rentable que mug** si la cible a beaucoup de bounties sur elle
- Les **boosters** sont safe : utilise-les avant un combat important
- Les **drogues** sont à risque : checke ton tier d'addiction avant de te re-droguer
- Les **bots** sont mockables comme cibles d'entraînement combat (mais ils répliquent et peuvent te coller des bounties dessus)
- L'XP de job (= avancement métier) est **séparée** de l'XP joueur (= niveau)
