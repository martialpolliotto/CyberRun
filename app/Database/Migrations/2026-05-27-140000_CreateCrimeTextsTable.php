<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Remplace les colonnes mono-texte (success_text, fail_text, critical_text) par
 * une table dediee crime_texts qui supporte N variantes par crime x issue.
 *
 * Chaque tentative pioche au hasard une variante du bon outcome (pickRandom).
 * Cela evite la repetition cote joueur.
 *
 * Migration des donnees existantes (1 ligne par texte non-null) avant de droper
 * les 3 colonnes d'origine.
 */
class CreateCrimeTextsTable extends Migration
{
    public function up()
    {
        // ---- crime_texts ----
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'crime_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            // outcome = 'success' | 'fail' | 'critical'
            'outcome'    => ['type' => 'VARCHAR', 'constraint' => 16],
            'text'       => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['crime_id', 'outcome']);
        $this->forge->addForeignKey('crime_id', 'crimes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('crime_texts');

        // ---- Migration des donnees : 1 ligne par (crime_id, outcome) si la colonne d'origine n'est pas vide.
        $now    = date('Y-m-d H:i:s');
        $crimes = $this->db->table('crimes')
            ->select('id, success_text, fail_text, critical_text')
            ->get()->getResultArray();

        $rows = [];
        foreach ($crimes as $c) {
            foreach (['success', 'fail', 'critical'] as $outcome) {
                $col = $outcome . '_text';
                if (! empty($c[$col]) && trim((string) $c[$col]) !== '') {
                    $rows[] = [
                        'crime_id'   => (int) $c['id'],
                        'outcome'    => $outcome,
                        'text'       => (string) $c[$col],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        if ($rows !== []) {
            $this->db->table('crime_texts')->insertBatch($rows);
        }

        // ---- DROP des colonnes devenues redondantes ----
        $this->forge->dropColumn('crimes', 'success_text');
        $this->forge->dropColumn('crimes', 'fail_text');
        $this->forge->dropColumn('crimes', 'critical_text');
    }

    public function down()
    {
        // On recree les colonnes vides (irreversible pour le contenu, c'est ok pour un down).
        $this->forge->addColumn('crimes', [
            'success_text'  => ['type' => 'TEXT', 'null' => true, 'after' => 'description'],
            'fail_text'     => ['type' => 'TEXT', 'null' => true, 'after' => 'success_text'],
            'critical_text' => ['type' => 'TEXT', 'null' => true, 'after' => 'fail_text'],
        ]);
        $this->forge->dropTable('crime_texts');
    }
}
