<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Systeme de crimes facon Torn.
 *
 * - crime_categories      : ex "Recherche de cash" / "Pickpocket" / "Hack ATM"
 * - crimes                : multiples par categorie, debloques par paliers d'XP categorie
 * - player_crime_progress : XP par categorie pour chaque joueur
 *
 * Chaque tentative produit 3 issues possibles :
 *   - reussite       : credits + XP joueur + XP categorie
 *   - echec simple   : rien (nerve depensee = seule penalite)
 *   - echec critique : hopital OU prison selon le crime
 *
 * Le calcul du taux de reussite combine plusieurs facteurs :
 *   base + (primary_stat / 2) + (category_xp / 10) + time_bonus + drug_bonus (V2)
 * cape a 95%, RNG fait le reste.
 *
 * Ajoute aussi in_jail_until sur players (in_hospital_until existe deja).
 */
class CreateCrimesTables extends Migration
{
    public function up()
    {
        // ---- ALTER players : ajout in_jail_until ----
        $this->forge->addColumn('players', [
            'in_jail_until' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'in_hospital_until',
            ],
        ]);

        // ---- crime_categories ----
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'  => ['type' => 'TEXT', 'null' => true],
            // null = pas de stat dominante (ex: "Recherche de cash"). Sinon une des 4 stats.
            'primary_stat' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],
            'display_order'=> ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('crime_categories');

        // ---- crimes ----
        $this->forge->addField([
            'id'                     => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'category_id'            => ['type' => 'BIGINT', 'unsigned' => true],
            'slug'                   => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'                   => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'            => ['type' => 'TEXT', 'null' => true],
            // Cout en nerve. La nerve est le rate limiter principal (pas de cooldown per-crime).
            'nerve_cost'             => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            // XP de categorie minimum pour debloquer ce crime.
            'min_category_xp'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            // Probas (en %).
            'base_success_pct'       => ['type' => 'INT', 'unsigned' => true, 'default' => 50],
            'critical_fail_pct'      => ['type' => 'INT', 'unsigned' => true, 'default' => 5],
            // Recompenses.
            'reward_credits_min'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reward_credits_max'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reward_xp'              => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reward_category_xp'     => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            // Consequences de l'echec critique : destination + duree (roll uniforme min/max minutes).
            'critical_destination'   => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'jail'],
            'critical_minutes_min'   => ['type' => 'INT', 'unsigned' => true, 'default' => 5],
            'critical_minutes_max'   => ['type' => 'INT', 'unsigned' => true, 'default' => 15],
            // Bonus horaire optionnel. Si time_bonus_hour_start/end != null et heure courante dans la fenetre, +time_bonus_pct sur success.
            // Fenetre peut "wrapper" minuit : start=22, end=5 = 22h-5h.
            'time_bonus_pct'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'time_bonus_hour_start'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'time_bonus_hour_end'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['category_id', 'min_category_xp']);
        $this->forge->addForeignKey('category_id', 'crime_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('crimes');

        // ---- player_crime_progress ----
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'category_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'xp'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'attempts'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'successes'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['player_id', 'category_id']);
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'crime_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_crime_progress');

        // ---- Seed : 3 categories x 3 crimes ----
        $now = date('Y-m-d H:i:s');

        $cats = [
            [
                'slug' => 'recherche-cash', 'name' => 'Recherche de cash',
                'description' => 'Fouiller, mendier, glaner. Pas glorieux mais ca rapporte quelques pieces sans trop de risques.',
                'primary_stat' => null, 'display_order' => 1,
            ],
            [
                'slug' => 'pickpocket', 'name' => 'Pickpocket',
                'description' => 'Voler a la sauvette dans la foule. Plus tu as de reflexes, plus tes doigts sont rapides.',
                'primary_stat' => 'reflexes', 'display_order' => 2,
            ],
            [
                'slug' => 'hack', 'name' => 'Hack ATM',
                'description' => 'Cracker des distributeurs, intercepter des paiements sans contact. Ton skill de hack fait toute la difference.',
                'primary_stat' => 'hack', 'display_order' => 3,
            ],
        ];
        foreach ($cats as &$c) { $c['created_at'] = $now; $c['updated_at'] = $now; }
        unset($c);
        $this->db->table('crime_categories')->insertBatch($cats);

        // Recupere les IDs assignes pour faire les FK.
        $catRows = $this->db->table('crime_categories')->select('id, slug')->get()->getResultArray();
        $catId   = array_column($catRows, 'id', 'slug');

        $crimes = [
            // ---- Recherche de cash ----
            [
                'category_id' => $catId['recherche-cash'], 'slug' => 'rc-fouille-poubelles',
                'name' => 'Fouiller les poubelles', 'description' => 'Plonger les bras dedans, esperer trouver de la monnaie oubliee. Personne ne te remarquera.',
                'nerve_cost' => 2, 'min_category_xp' => 0,
                'base_success_pct' => 65, 'critical_fail_pct' => 3,
                'reward_credits_min' => 3, 'reward_credits_max' => 12,
                'reward_xp' => 2, 'reward_category_xp' => 1,
                'critical_destination' => 'jail', 'critical_minutes_min' => 2, 'critical_minutes_max' => 5,
            ],
            [
                'category_id' => $catId['recherche-cash'], 'slug' => 'rc-mendicite',
                'name' => 'Faire la manche', 'description' => 'Aux carrefours, devant les corpos. Plus tu insistes, plus tu te fais virer par la securite.',
                'nerve_cost' => 4, 'min_category_xp' => 25,
                'base_success_pct' => 55, 'critical_fail_pct' => 6,
                'reward_credits_min' => 10, 'reward_credits_max' => 35,
                'reward_xp' => 5, 'reward_category_xp' => 2,
                'critical_destination' => 'jail', 'critical_minutes_min' => 5, 'critical_minutes_max' => 15,
            ],
            [
                'category_id' => $catId['recherche-cash'], 'slug' => 'rc-vente-sauvette',
                'name' => 'Vente a la sauvette', 'description' => 'Vendre de la pacotille recuperee. Surveille les NCPD, ils n\'aiment pas la concurrence du marche noir.',
                'nerve_cost' => 7, 'min_category_xp' => 100,
                'base_success_pct' => 45, 'critical_fail_pct' => 10,
                'reward_credits_min' => 30, 'reward_credits_max' => 90,
                'reward_xp' => 12, 'reward_category_xp' => 4,
                'critical_destination' => 'jail', 'critical_minutes_min' => 15, 'critical_minutes_max' => 30,
            ],

            // ---- Pickpocket ----
            [
                'category_id' => $catId['pickpocket'], 'slug' => 'pp-foule-metro',
                'name' => 'Pickpocket dans la foule du metro', 'description' => 'Heure de pointe, gens distraits. Ton bras se glisse dans une poche, deux secondes max.',
                'nerve_cost' => 4, 'min_category_xp' => 0,
                'base_success_pct' => 50, 'critical_fail_pct' => 8,
                'reward_credits_min' => 15, 'reward_credits_max' => 45,
                'reward_xp' => 6, 'reward_category_xp' => 2,
                'critical_destination' => 'jail', 'critical_minutes_min' => 10, 'critical_minutes_max' => 25,
            ],
            [
                'category_id' => $catId['pickpocket'], 'slug' => 'pp-touriste-bourre',
                'name' => 'Detrousser un touriste bourre', 'description' => 'Quartier des bars, 3h du matin. Une cible facile, mais si ses potes te voient...',
                'nerve_cost' => 6, 'min_category_xp' => 50,
                'base_success_pct' => 48, 'critical_fail_pct' => 12,
                'reward_credits_min' => 40, 'reward_credits_max' => 120,
                'reward_xp' => 12, 'reward_category_xp' => 3,
                'critical_destination' => 'jail', 'critical_minutes_min' => 15, 'critical_minutes_max' => 30,
                'time_bonus_pct' => 12, 'time_bonus_hour_start' => 22, 'time_bonus_hour_end' => 5,
            ],
            [
                'category_id' => $catId['pickpocket'], 'slug' => 'pp-vol-moto',
                'name' => 'Voler une moto a l\'arrache', 'description' => 'Foncer, demarrer en force, esperer ne pas finir aplati contre un mur. Spectaculaire mais dangereux.',
                'nerve_cost' => 10, 'min_category_xp' => 200,
                'base_success_pct' => 42, 'critical_fail_pct' => 18,
                'reward_credits_min' => 120, 'reward_credits_max' => 300,
                'reward_xp' => 25, 'reward_category_xp' => 6,
                'critical_destination' => 'hospital', 'critical_minutes_min' => 20, 'critical_minutes_max' => 45,
            ],

            // ---- Hack ATM ----
            [
                'category_id' => $catId['hack'], 'slug' => 'hk-skimmer',
                'name' => 'Poser un skimmer sur un ATM', 'description' => 'Pose discrete, recuperation 24h plus tard. Peu de risques mais le rendement est moyen.',
                'nerve_cost' => 5, 'min_category_xp' => 0,
                'base_success_pct' => 58, 'critical_fail_pct' => 5,
                'reward_credits_min' => 25, 'reward_credits_max' => 70,
                'reward_xp' => 8, 'reward_category_xp' => 2,
                'critical_destination' => 'jail', 'critical_minutes_min' => 10, 'critical_minutes_max' => 25,
            ],
            [
                'category_id' => $catId['hack'], 'slug' => 'hk-intercept-nfc',
                'name' => 'Intercepter des paiements NFC', 'description' => 'Avec un emetteur custom, tu rejoues des autorisations de paiement. Faut savoir scripter.',
                'nerve_cost' => 8, 'min_category_xp' => 75,
                'base_success_pct' => 48, 'critical_fail_pct' => 10,
                'reward_credits_min' => 70, 'reward_credits_max' => 180,
                'reward_xp' => 18, 'reward_category_xp' => 4,
                'critical_destination' => 'jail', 'critical_minutes_min' => 20, 'critical_minutes_max' => 40,
            ],
            [
                'category_id' => $catId['hack'], 'slug' => 'hk-crack-atm',
                'name' => 'Cracker un ATM en direct', 'description' => 'Branche ton deck sur le port maintenance, exploit zero-day, et tu vides la machine en 3 minutes. Si t\'es repere, c\'est cuit.',
                'nerve_cost' => 13, 'min_category_xp' => 250,
                'base_success_pct' => 40, 'critical_fail_pct' => 15,
                'reward_credits_min' => 200, 'reward_credits_max' => 500,
                'reward_xp' => 35, 'reward_category_xp' => 8,
                'critical_destination' => 'jail', 'critical_minutes_min' => 30, 'critical_minutes_max' => 60,
            ],
        ];

        foreach ($crimes as &$c) {
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
            // Defaults pour les colonnes optionnelles non explicitement set.
            $c['time_bonus_pct']        = $c['time_bonus_pct']        ?? 0;
            $c['time_bonus_hour_start'] = $c['time_bonus_hour_start'] ?? null;
            $c['time_bonus_hour_end']   = $c['time_bonus_hour_end']   ?? null;
        }
        unset($c);
        $this->db->table('crimes')->insertBatch($crimes);
    }

    public function down()
    {
        $this->forge->dropTable('player_crime_progress');
        $this->forge->dropTable('crimes');
        $this->forge->dropTable('crime_categories');
        $this->forge->dropColumn('players', 'in_jail_until');
    }
}
