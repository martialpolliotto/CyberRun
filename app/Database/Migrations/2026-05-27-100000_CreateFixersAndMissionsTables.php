<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tables du systeme de missions facon Torn (Duke & cie).
 *
 * - fixers              : les PNJ donneurs de missions (Le Fixer du quartier, puis d'autres)
 * - missions            : chaque mission appartient a un fixer, ordonnee dans la chaine
 * - player_missions     : etat de chaque mission pour chaque joueur (in_progress / completed / claimed)
 *
 * Modele : 1 objectif par mission. Trois categories d'objectif :
 *   - compteur d'evenement (train_stat, buy_item, equip_slot, visit_page, spend_credits)
 *   - seuil de stat       (reach_stat avec target = slug stat)
 *   - seuil de niveau     (reach_level)
 *
 * objective_target stocke le filtre (slug stat, slug vendor, slot, slug page, item id, ...).
 *  '*' = match large (n'importe quel stat / item / vendor).
 *
 * Unlock du fixer N : toutes les missions des fixers d'unlock_order < N sont claimed.
 */
class CreateFixersAndMissionsTables extends Migration
{
    public function up()
    {
        // ---- fixers ----
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'tagline'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'image_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'unlock_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('unlock_order');
        $this->forge->createTable('fixers');

        // ---- missions ----
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'fixer_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'brief'            => ['type' => 'TEXT', 'null' => true],
            'mission_order'    => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'objective_type'   => ['type' => 'VARCHAR', 'constraint' => 32],
            'objective_target' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => '*'],
            'objective_count'  => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'reward_credits'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reward_xp'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reward_item_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['fixer_id', 'mission_order']);
        $this->forge->addForeignKey('fixer_id', 'fixers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reward_item_id', 'items', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('missions');

        // ---- player_missions ----
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'player_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'mission_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'in_progress'],
            'progress'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'started_at'   => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'claimed_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['player_id', 'mission_id']);
        $this->forge->addKey(['player_id', 'status']);
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_missions');

        // ---- Seed : Fixer #1 + 6 missions tutoriel ----
        $now = date('Y-m-d H:i:s');

        $this->db->table('fixers')->insert([
            'slug'         => 'fixer-quartier',
            'name'         => 'Le Fixer du quartier',
            'tagline'      => 'Tu veux survivre dans le coin ? Suis mes conseils.',
            'description'  => 'Vieux briscard qui a vu passer trois generations de runners. Il connait chaque ruelle, chaque marchand, chaque combine du quartier. Si tu debarques, c\'est lui qu\'on t\'envoie voir en premier. Pas tres bavard, mais ce qu\'il dit vaut de l\'or.',
            'image_path'   => null,
            'unlock_order' => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        $fixerId = (int) $this->db->insertID();

        $missions = [
            [
                'slug' => 'qrt-01-presente-toi',
                'name' => 'Presente-toi',
                'brief' => 'Avant de bosser ensemble, montre-moi a quoi tu ressembles. Va consulter ta fiche perso au moins une fois.',
                'mission_order' => 1,
                'objective_type' => 'visit_page', 'objective_target' => 'profile', 'objective_count' => 1,
                'reward_credits' => 25, 'reward_xp' => 10,
            ],
            [
                'slug' => 'qrt-02-le-lab',
                'name' => 'Au Lab',
                'brief' => 'Le Lab c\'est l\'endroit ou tu forges ton chrome. Fais-y un tour pour voir ce que tu peux entrainer.',
                'mission_order' => 2,
                'objective_type' => 'visit_page', 'objective_target' => 'lab', 'objective_count' => 1,
                'reward_credits' => 25, 'reward_xp' => 10,
            ],
            [
                'slug' => 'qrt-03-premier-entrainement',
                'name' => 'Premier entrainement',
                'brief' => 'Tu vas pas survivre longtemps avec ces stats. Entraine n\'importe quelle competence au Lab.',
                'mission_order' => 3,
                'objective_type' => 'train_stat', 'objective_target' => '*', 'objective_count' => 1,
                'reward_credits' => 50, 'reward_xp' => 25,
            ],
            [
                'slug' => 'qrt-04-tournee-marches',
                'name' => 'Tournee des marches',
                'brief' => 'Tu connais les 3 marchands du coin ? Va voir le panneau des marches.',
                'mission_order' => 4,
                'objective_type' => 'visit_page', 'objective_target' => 'shops', 'objective_count' => 1,
                'reward_credits' => 50, 'reward_xp' => 25,
            ],
            [
                'slug' => 'qrt-05-premier-achat',
                'name' => 'Premier achat',
                'brief' => 'Met de cote tes credits pour acheter ton premier equipement, peu importe lequel ou chez quel marchand.',
                'mission_order' => 5,
                'objective_type' => 'buy_item', 'objective_target' => '*', 'objective_count' => 1,
                'reward_credits' => 100, 'reward_xp' => 50,
            ],
            [
                'slug' => 'qrt-06-equipe-toi',
                'name' => 'Equipe-toi',
                'brief' => 'Avoir le gear c\'est bien, le porter c\'est mieux. Equipe au moins un item depuis ta page Equipement.',
                'mission_order' => 6,
                'objective_type' => 'equip_slot', 'objective_target' => '*', 'objective_count' => 1,
                'reward_credits' => 150, 'reward_xp' => 75,
            ],
        ];

        foreach ($missions as &$m) {
            $m['fixer_id']   = $fixerId;
            $m['created_at'] = $now;
            $m['updated_at'] = $now;
        }
        unset($m);

        $this->db->table('missions')->insertBatch($missions);
    }

    public function down()
    {
        $this->forge->dropTable('player_missions');
        $this->forge->dropTable('missions');
        $this->forge->dropTable('fixers');
    }
}
