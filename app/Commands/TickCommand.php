<?php

namespace App\Commands;

use App\Services\BotService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TickCommand extends BaseCommand
{
    protected $group       = 'Cyberrun';
    protected $name        = 'cyberrun:tick';
    protected $description = 'Regen tick (energie, nerve, HP) pour tous les players. A appeler une fois par minute.';

    /** Regen par tick (1 tick = 1 minute en prod). */
    public const ENERGY_REGEN_PER_TICK = 2;
    public const NERVE_REGEN_PER_TICK  = 1;
    public const HP_REGEN_PER_TICK     = 5;

    public function run(array $params)
    {
        $start = microtime(true);
        $db    = db_connect();

        $db->query(
            'UPDATE players SET energy_current = LEAST(energy_max, energy_current + ?), updated_at = NOW() WHERE energy_current < energy_max',
            [self::ENERGY_REGEN_PER_TICK],
        );
        $energyAffected = $db->affectedRows();

        $db->query(
            'UPDATE players SET nerve_current = LEAST(nerve_max, nerve_current + ?), updated_at = NOW() WHERE nerve_current < nerve_max',
            [self::NERVE_REGEN_PER_TICK],
        );
        $nerveAffected = $db->affectedRows();

        $db->query(
            'UPDATE players SET hp_current = LEAST(hp_max, hp_current + ?), updated_at = NOW() WHERE hp_current < hp_max',
            [self::HP_REGEN_PER_TICK],
        );
        $hpAffected = $db->affectedRows();

        // Paie quotidienne (salaire + XP + stats job + auto-promotion).
        // Logique : si on a depasse l'heure de paie ET qu'aucune paie n'a ete faite aujourd'hui,
        // verse a chaque employe :
        //   - daily_salary (en credits)
        //   - job_daily_xp_gain points d'XP de job
        //   - job_daily_stat_gain points dans chacune des 2 stats du job (mapping via jobs.stat_1/stat_2)
        $settings = model(\App\Models\GameSettingModel::class);
        $payoutHour = (int) $settings->get('job_salary_payout_hour', 8);
        $xpGain     = (int) $settings->get('job_daily_xp_gain',     10);
        $statGain   = (int) $settings->get('job_daily_stat_gain',   1);

        // Advisory lock : empeche 2 ticks concurrents (cron + run manuel) de double-payer.
        // Si on n'arrive pas a obtenir le lock en 0s, on saute le payout (autre process le fait).
        $lockRow = $db->query("SELECT GET_LOCK('cyberrun_tick_salary', 0) AS got")->getRowArray();
        $hasLock = ((int) ($lockRow['got'] ?? 0)) === 1;

        $salaryAffected = 0;
        if ($hasLock) {
            $db->query(
                "UPDATE players p
                 JOIN job_positions jp ON jp.id = p.job_position_id
                 JOIN jobs j           ON j.id  = jp.job_id
                 SET p.credits        = p.credits + jp.daily_salary,
                     p.job_xp         = p.job_xp + ?,
                     p.job_stat_tech      = p.job_stat_tech      + (IF(j.stat_1 = 'tech',      ?, 0) + IF(j.stat_2 = 'tech',      ?, 0)),
                     p.job_stat_endurance = p.job_stat_endurance + (IF(j.stat_1 = 'endurance', ?, 0) + IF(j.stat_2 = 'endurance', ?, 0)),
                     p.job_stat_charisme  = p.job_stat_charisme  + (IF(j.stat_1 = 'charisme',  ?, 0) + IF(j.stat_2 = 'charisme',  ?, 0)),
                     p.last_salary_at = NOW(),
                     p.updated_at     = NOW()
                 WHERE p.job_position_id IS NOT NULL
                   AND HOUR(NOW()) >= ?
                   AND (p.last_salary_at IS NULL OR DATE(p.last_salary_at) < CURDATE())",
                [$xpGain, $statGain, $statGain, $statGain, $statGain, $statGain, $statGain, $payoutHour]
            );
            $salaryAffected = $db->affectedRows();

            // Auto-promotion : pour chaque employe paye, monte au plus haut rank debloque par son XP actuel.
            $db->query(
                'UPDATE players p
                 JOIN job_positions current ON current.id = p.job_position_id
                 SET p.job_position_id = (
                    SELECT jp.id FROM job_positions jp
                    WHERE jp.job_id = current.job_id
                      AND jp.xp_required <= p.job_xp
                    ORDER BY jp.rank DESC
                    LIMIT 1
                 )
                 WHERE p.job_position_id IS NOT NULL
                   AND DATE(p.last_salary_at) = CURDATE()'
            );

            $db->query("SELECT RELEASE_LOCK('cyberrun_tick_salary')");
        }

        // Bots : font tourner leurs actions apres la regen pour qu'ils benefient des points fraichement gagnes.
        $botStats = (new BotService())->tickAll();

        // Chat prune : garde les N dernieres par channel (configurable).
        $keep = (int) $settings->get('chat_history_keep_per_channel', 500);
        $chatModel = model(\App\Models\ChatMessageModel::class);
        $chatPruned = 0;
        foreach ($chatModel->listActiveChannels() as $ch) {
            $chatPruned += $chatModel->pruneChannel($ch, $keep);
        }

        $elapsed = round((microtime(true) - $start) * 1000, 1);
        CLI::write(
            sprintf(
                '[%s] tick OK : energy +%d (%d), nerve +%d (%d), hp +%d (%d) | salaries %d | bots %d/%d acted %s | chat pruned %d — %sms',
                date('H:i:s'),
                self::ENERGY_REGEN_PER_TICK, $energyAffected,
                self::NERVE_REGEN_PER_TICK,  $nerveAffected,
                self::HP_REGEN_PER_TICK,     $hpAffected,
                $salaryAffected,
                $botStats['acted'], $botStats['ticked'],
                $botStats['by_action'] === [] ? '' : '(' . http_build_query($botStats['by_action'], '', ', ') . ')',
                $chatPruned,
                $elapsed,
            ),
            'green',
        );
    }
}
