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

        // Bots : font tourner leurs actions apres la regen pour qu'ils benefient des points fraichement gagnes.
        $botStats = (new BotService())->tickAll();

        $elapsed = round((microtime(true) - $start) * 1000, 1);
        CLI::write(
            sprintf(
                '[%s] tick OK : energy +%d (%d), nerve +%d (%d), hp +%d (%d) | bots %d/%d acted %s — %sms',
                date('H:i:s'),
                self::ENERGY_REGEN_PER_TICK, $energyAffected,
                self::NERVE_REGEN_PER_TICK,  $nerveAffected,
                self::HP_REGEN_PER_TICK,     $hpAffected,
                $botStats['acted'], $botStats['ticked'],
                $botStats['by_action'] === [] ? '' : '(' . http_build_query($botStats['by_action'], '', ', ') . ')',
                $elapsed,
            ),
            'green',
        );
    }
}
