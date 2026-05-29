<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Refonte du systeme jobs facon Torn :
 *  - Stats de job (Tech / Endurance / Charisme) : 3 nouveaux compteurs sur players.
 *    Chaque session de Travail booste 2 stats specifiques au job (mapping stat_1/stat_2).
 *  - Salaire passe d'horaire a quotidien. Renomme hourly_salary -> daily_salary.
 *    Le tick cron paie a une heure fixe (game_setting job_salary_payout_hour).
 *  - Valeurs des salaires recalibrees pour le quotidien (100 a 6400 / jour).
 *  - Mapping stat_1/stat_2 sur chaque job ajoute.
 */
class ReshapeJobsStatsAndDailySalary extends Migration
{
    public function up()
    {
        // 1. ADD stats job sur players (3 compteurs globaux qui persistent meme apres changement de job).
        $this->forge->addColumn('players', [
            'job_stat_tech'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'job_xp'],
            'job_stat_endurance' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'job_stat_tech'],
            'job_stat_charisme'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'job_stat_endurance'],
        ]);

        // 2. ADD stat_1/stat_2 sur jobs (les 2 job_stats qu'un travail booste).
        $this->forge->addColumn('jobs', [
            'stat_1' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true, 'after' => 'primary_stat'],
            'stat_2' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true, 'after' => 'stat_1'],
        ]);

        // 3. Renomme hourly_salary -> daily_salary sur job_positions + recalibre les valeurs.
        // Utilise raw SQL car forge->modifyColumn ne gere pas le rename proprement.
        $this->db->query('ALTER TABLE job_positions CHANGE COLUMN hourly_salary daily_salary INT UNSIGNED NOT NULL DEFAULT 0');

        // 4. Recalibre les salaires : barème quotidien progressif.
        $newSalaries = [1 => 100, 2 => 200, 3 => 400, 4 => 800, 5 => 1600, 6 => 3200, 7 => 6400];
        foreach ($newSalaries as $rank => $salary) {
            $this->db->table('job_positions')->where('rank', $rank)->update(['daily_salary' => $salary]);
        }

        // 5. Mapping stat_1 / stat_2 par job (alignement cyberpunk).
        $statMap = [
            'ripperdoc'   => ['tech',      'endurance'],
            'decker'      => ['tech',      'charisme'],
            'corpo-guard' => ['endurance', 'charisme'],
            'courier'     => ['endurance', 'tech'],
            'net-runner'  => ['tech',      'charisme'],
            'mercenary'   => ['endurance', 'charisme'],
            'fixer-aide'  => ['charisme',  'tech'],
        ];
        foreach ($statMap as $slug => [$s1, $s2]) {
            $this->db->table('jobs')->where('slug', $slug)->update([
                'stat_1' => $s1,
                'stat_2' => $s2,
            ]);
        }

        // 6. Game setting : heure de paiement quotidien (0-23, defaut 8h).
        $this->db->table('game_settings')->insert([
            'setting_key' => 'job_salary_payout_hour',
            'value'       => '8',
            'label'       => 'Jobs : heure de paie quotidienne',
            'description' => 'Heure (0-23) a laquelle les salaires sont payes chaque jour via le cron.',
            'type'        => 'int',
            'category'    => 'jobs',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->db->table('game_settings')->where('setting_key', 'job_salary_payout_hour')->delete();
        $this->db->query('ALTER TABLE job_positions CHANGE COLUMN daily_salary hourly_salary INT UNSIGNED NOT NULL DEFAULT 0');
        $this->forge->dropColumn('jobs', 'stat_1');
        $this->forge->dropColumn('jobs', 'stat_2');
        $this->forge->dropColumn('players', 'job_stat_tech');
        $this->forge->dropColumn('players', 'job_stat_endurance');
        $this->forge->dropColumn('players', 'job_stat_charisme');
    }
}
