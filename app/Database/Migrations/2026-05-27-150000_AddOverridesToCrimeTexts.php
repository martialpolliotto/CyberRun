<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Permet a chaque variante de texte d'avoir ses propres valeurs (recompenses,
 * destination critique, duree) qui surchargent celles du crime parent.
 *
 * Toutes les colonnes ajoutees sont NULL par defaut = "utilise la valeur du crime".
 * Si une variante remplit la valeur, elle prend le pas. CrimeModel::attempt() lit
 * en priorite la variante, fallback au crime.
 *
 * Cela permet d'avoir des narrations differenciees : "tu trouves 2 pieces" donne
 * peu, "tu trouves un portefeuille bien garni" donne beaucoup. Idem pour les
 * critiques : "la secu te chope" prison 5min, "le proprio te tabasse" hopital 20min.
 */
class AddOverridesToCrimeTexts extends Migration
{
    public function up()
    {
        $this->forge->addColumn('crime_texts', [
            'reward_credits_min'    => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'text'],
            'reward_credits_max'    => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'reward_credits_min'],
            'reward_xp'             => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'reward_credits_max'],
            'reward_category_xp'    => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'reward_xp'],
            'critical_destination'  => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true, 'after' => 'reward_category_xp'],
            'critical_minutes_min'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'critical_destination'],
            'critical_minutes_max'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'critical_minutes_min'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('crime_texts', 'reward_credits_min');
        $this->forge->dropColumn('crime_texts', 'reward_credits_max');
        $this->forge->dropColumn('crime_texts', 'reward_xp');
        $this->forge->dropColumn('crime_texts', 'reward_category_xp');
        $this->forge->dropColumn('crime_texts', 'critical_destination');
        $this->forge->dropColumn('crime_texts', 'critical_minutes_min');
        $this->forge->dropColumn('crime_texts', 'critical_minutes_max');
    }
}
