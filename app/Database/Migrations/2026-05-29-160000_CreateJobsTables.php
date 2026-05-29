<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Systeme de jobs / metiers facon Torn (City Jobs).
 *
 * - jobs           : 7 jobs publics (Ripperdoc, Decker, Garde Corpo...).
 * - job_positions  : 7 positions par job (Trainee -> Director), avec salaire et perk text.
 * - players.job_position (FK) : la position actuelle du joueur (ou null si sans emploi).
 * - players.job_xp           : XP accumulee dans le job courant (reset au changement de job).
 * - players.last_salary_at   : timestamp du dernier paiement de salaire (pour le cron).
 *
 * V1 : pas de perks fonctionnels (juste affichage du texte). A brancher en V2.
 */
class CreateJobsTables extends Migration
{
    public function up()
    {
        // ---- jobs ----
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'primary_stat'   => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true],
            'work_energy_cost' => ['type' => 'INT', 'unsigned' => true, 'default' => 10],
            'display_order'  => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('jobs');

        // ---- job_positions ----
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'job_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'rank'            => ['type' => 'INT', 'unsigned' => true, 'default' => 1],  // 1 = entree, N = top
            'name'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'xp_required'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],  // XP cumule pour atteindre cette position
            'hourly_salary'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'perk_text'       => ['type' => 'TEXT', 'null' => true],  // description du perk (pas branche en V1)
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['job_id', 'rank']);
        $this->forge->addForeignKey('job_id', 'jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('job_positions');

        // ---- ALTER players ----
        $this->forge->addColumn('players', [
            'job_position_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'job'],
            'job_xp'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'job_position_id'],
            'last_salary_at'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'job_xp'],
            'last_work_at'     => ['type' => 'DATETIME', 'null' => true, 'after' => 'last_salary_at'],
        ]);
        // Pas de FK stricte (drop d'un job sans casser le player). On gere en code.

        // ---- Seed des 7 jobs cyberpunk ----
        $now = date('Y-m-d H:i:s');
        $jobs = [
            ['slug' => 'ripperdoc',    'name' => 'Ripperdoc',          'primary_stat' => 'hack',     'work_energy_cost' => 12, 'display_order' => 1,
             'description' => 'Tu poses du chrome en arriere-cour. Les patients ne posent pas de questions, et toi non plus.'],
            ['slug' => 'decker',       'name' => 'Décker corporate',   'primary_stat' => 'hack',     'work_energy_cost' => 10, 'display_order' => 2,
             'description' => 'Hacker certifie pour une megacorp. Tu pirates pour eux, l\'air de rien.'],
            ['slug' => 'corpo-guard',  'name' => 'Garde corpo',        'primary_stat' => 'force',    'work_energy_cost' => 10, 'display_order' => 3,
             'description' => 'Securite privee corporate. Costard, oreillette, et chargeur plein.'],
            ['slug' => 'courier',      'name' => 'Coursier',           'primary_stat' => 'reflexes', 'work_energy_cost' => 8,  'display_order' => 4,
             'description' => 'Tu livres ce qu\'on te confie. Ne demande pas ce qu\'il y a dans le paquet.'],
            ['slug' => 'net-runner',   'name' => 'Net-Runner indep.',  'primary_stat' => 'hack',     'work_energy_cost' => 11, 'display_order' => 5,
             'description' => 'Mercenaire du cyberspace. Pas de patron, pas de filet.'],
            ['slug' => 'mercenary',    'name' => 'Mercenaire',         'primary_stat' => 'force',    'work_energy_cost' => 11, 'display_order' => 6,
             'description' => 'Tu prends les contrats que personne d\'autre ne veut. Question des honoraires.'],
            ['slug' => 'fixer-aide',   'name' => 'Fixer adjoint',      'primary_stat' => 'reflexes', 'work_energy_cost' => 9,  'display_order' => 7,
             'description' => 'Intermediaire pour un fixer plus gros. Tu connais qui faut, t\'es paye pour ca.'],
        ];
        foreach ($jobs as &$j) { $j['created_at'] = $now; $j['updated_at'] = $now; }
        unset($j);
        $this->db->table('jobs')->insertBatch($jobs);

        // Recupere les IDs.
        $rows = $this->db->table('jobs')->select('id, slug')->get()->getResultArray();
        $jobIds = array_column($rows, 'id', 'slug');

        // ---- 7 positions par job. Salaire qui double a peu pres a chaque palier. ----
        $positionsPerJob = [
            ['rank' => 1, 'name' => 'Stagiaire',   'xp_required' => 0,     'hourly_salary' => 30],
            ['rank' => 2, 'name' => 'Apprenti',    'xp_required' => 50,    'hourly_salary' => 60],
            ['rank' => 3, 'name' => 'Junior',      'xp_required' => 200,   'hourly_salary' => 120],
            ['rank' => 4, 'name' => 'Confirmé',    'xp_required' => 600,   'hourly_salary' => 240],
            ['rank' => 5, 'name' => 'Senior',      'xp_required' => 1500,  'hourly_salary' => 480],
            ['rank' => 6, 'name' => 'Vétéran',     'xp_required' => 4000,  'hourly_salary' => 960],
            ['rank' => 7, 'name' => 'Légende',     'xp_required' => 10000, 'hourly_salary' => 1920],
        ];

        // Perks (V1 : juste textes, pas brancher).
        $perksByJob = [
            'ripperdoc'   => ['Premiers soins +5 HP/h', '−10% temps cyberclinique', 'Soins gratuits sur soi-même', '−25% temps clinique', '+1 NRG regen', '−50% temps clinique', 'Soins instantanés en combat'],
            'decker'      => ['+1 stat Hack/jour', '+5% hit cyber-attaques', '−1 nerve sur hack ATM', '+10% drop crimes Hack', '+2 stat Hack/jour', '−50% chance overdose drogues', 'Bypass cooldown crimes Hack'],
            'corpo-guard' => ['+1 stat Force/jour', '+5% défense combat', '−1 nerve sur garde corpo crimes', '+10% damage corps-à-corps', '+2 stat Force/jour', '−50% temps prison', '+20% défense combat'],
            'courier'     => ['+1 stat Réflexes/jour', '+1 NRG max', '+5% fuite combat', '+1 NRG regen', '+2 stat Réflexes/jour', '−25% cooldowns consommables', '+25% fuite combat'],
            'net-runner'  => ['+1 stat Hack/jour', '+5% loot crimes Hack', '−1 nerve sur tous les crimes Hack', '+10% bonus succès Hack', '+2 stat Hack/jour', 'Bypass 1 cooldown drogue/jour', 'Crimes Hack gratuits en nerve'],
            'mercenary'   => ['+1 stat Force/jour', '+5% damage attaques', '−1 nerve sur attaques PvP', '+10% damage tous combats', '+2 stat Force/jour', '−50% temps prison', '+25% mug ratio'],
            'fixer-aide'  => ['+1 stat Réflexes/jour', '+5% bounty claim split', '−10% cout bail', '+10% reward missions fixers', '+2 stat Réflexes/jour', 'Accès missions exclusives', 'Bail gratuit sur tes alliés'],
        ];

        $positionRows = [];
        foreach ($jobIds as $slug => $jobId) {
            $perks = $perksByJob[$slug] ?? [];
            foreach ($positionsPerJob as $i => $pos) {
                $positionRows[] = [
                    'job_id'        => $jobId,
                    'rank'          => $pos['rank'],
                    'name'          => $pos['name'],
                    'xp_required'   => $pos['xp_required'],
                    'hourly_salary' => $pos['hourly_salary'],
                    'perk_text'     => $perks[$i] ?? null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }
        $this->db->table('job_positions')->insertBatch($positionRows);
    }

    public function down()
    {
        foreach (['job_position_id', 'job_xp', 'last_salary_at', 'last_work_at'] as $col) {
            $this->forge->dropColumn('players', $col);
        }
        $this->forge->dropTable('job_positions');
        $this->forge->dropTable('jobs');
    }
}
