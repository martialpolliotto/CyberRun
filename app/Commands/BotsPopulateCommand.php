<?php

namespace App\Commands;

use App\Services\BotService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cree N bots d'une persona donnee. Usage :
 *   php spark cyberrun:bots:populate 10 criminel
 *   php spark cyberrun:bots:populate 5 lambda
 */
class BotsPopulateCommand extends BaseCommand
{
    protected $group       = 'Cyberrun';
    protected $name        = 'cyberrun:bots:populate';
    protected $description = 'Cree N bots d\'une persona donnee (criminel|athlete|trafiquant|lambda).';
    protected $usage       = 'cyberrun:bots:populate [count] [persona]';

    public function run(array $params)
    {
        $count   = isset($params[0]) ? max(1, (int) $params[0]) : 1;
        $persona = $params[1] ?? 'lambda';

        if (! isset(BotService::PERSONAS[$persona])) {
            CLI::error('Persona inconnue. Disponibles : ' . implode(', ', array_keys(BotService::PERSONAS)));
            return;
        }

        CLI::write('Creation de ' . $count . ' bots persona ' . $persona . '...', 'yellow');
        $r = (new BotService())->populate($count, $persona);
        CLI::write($r['created'] . ' bots crees.', 'green');
        if (! empty($r['errors'])) {
            CLI::error(count($r['errors']) . ' erreurs :');
            foreach ($r['errors'] as $e) {
                CLI::error('  - ' . $e);
            }
        }
    }
}
