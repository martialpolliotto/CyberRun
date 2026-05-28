<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Infra de configuration generique : table game_settings (clef/valeur typee + meta).
 * Vise a centraliser les parametres ajustables du jeu (couts, multipliers, durees...)
 * pour les editer via /admin/game-settings sans toucher au code.
 *
 * Cle = identifiant code (snake_case), value = string brute (cast au runtime selon type).
 * category = regroupement visuel dans l'admin.
 *
 * Seed initial : parametres du bust/bail (faire sortir un joueur de prison).
 *  - bust_nerve_cost           : nerve par tentative
 *  - bust_fail_critical_min/max : minutes en prison si echec
 *  - bail_coefficient          : multiplicateur du cout (cout = niveau cible * minutes * coef)
 */
class CreateGameSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'setting_key' => ['type' => 'VARCHAR', 'constraint' => 80],
            'value'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'label'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'type'        => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'string'],
            'category'    => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'general'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('setting_key');
        $this->forge->addKey('category');
        $this->forge->createTable('game_settings');

        $now = date('Y-m-d H:i:s');
        $rows = [
            [
                'setting_key' => 'bust_nerve_cost', 'value' => '5',
                'label' => 'Bust : coût en nerve', 'type' => 'int', 'category' => 'jail',
                'description' => 'Nerve consommée à chaque tentative de bust (faire évader un détenu).',
            ],
            [
                'setting_key' => 'bust_fail_critical_min', 'value' => '60',
                'label' => 'Bust : minutes prison min sur échec', 'type' => 'int', 'category' => 'jail',
                'description' => 'Durée minimale de prison pour le bustant qui rate. Roll uniforme entre min et max.',
            ],
            [
                'setting_key' => 'bust_fail_critical_max', 'value' => '180',
                'label' => 'Bust : minutes prison max sur échec', 'type' => 'int', 'category' => 'jail',
                'description' => 'Durée maximale de prison pour le bustant qui rate.',
            ],
            [
                'setting_key' => 'bust_difficulty_multiplier', 'value' => '50',
                'label' => 'Bust : multiplicateur de difficulté', 'type' => 'int', 'category' => 'jail',
                'description' => 'Multiplie le ratio power/difficulty pour donner le % de succès final (avant clamp 5-95).',
            ],
            [
                'setting_key' => 'bail_coefficient', 'value' => '1.0',
                'label' => 'Bail : coefficient du coût', 'type' => 'float', 'category' => 'jail',
                'description' => 'Coût final = niveau cible × minutes restantes × coefficient. À 1.0 = formule de base.',
            ],
        ];
        foreach ($rows as &$r) { $r['created_at'] = $now; $r['updated_at'] = $now; }
        unset($r);
        $this->db->table('game_settings')->insertBatch($rows);
    }

    public function down()
    {
        $this->forge->dropTable('game_settings');
    }
}
