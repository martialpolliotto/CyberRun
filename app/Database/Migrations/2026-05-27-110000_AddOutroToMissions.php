<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ajoute un champ "outro" sur les missions : texte affiche au joueur
 * quand la mission est terminee mais pas encore reclamee chez le fixer.
 *
 * Equivalent du dialogue de fin que Duke balance dans Torn quand tu
 * rapportes une mission accomplie.
 */
class AddOutroToMissions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('missions', [
            'outro' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'brief',
            ],
        ]);

        // Outros pour les 6 missions seed du Fixer du quartier.
        $outros = [
            'qrt-01-presente-toi' => 'Bien. T\'as une gueule de runner, ca peut suivre. Garde un oeil sur ta fiche, elle te dit tout sur ton etat.',
            'qrt-02-le-lab'       => 'Le Lab c\'est ton meilleur ami. Pas de muscle, pas de respect. Reviens y des que tu as de l\'energie a depenser.',
            'qrt-03-premier-entrainement' => 'Une seance c\'est rien, mais c\'est un debut. Les vrais runners y passent des heures. Vise haut.',
            'qrt-04-tournee-marches' => 'Maintenant que tu connais les boutiques, tu sais ou trouver ton chrome. Methode : armurerie pour la puissance, ripperdoc pour les hacks, friperie pour la discretion.',
            'qrt-05-premier-achat' => 'Ton premier achat. Ca a un gout particulier, hein ? Acheter c\'est facile. Survivre apres, c\'est autre chose.',
            'qrt-06-equipe-toi'    => 'Et voila, tu es paré. Avec ca dans le dos, tu commences a ressembler a quelque chose. Reviens me voir quand t\'es pret pour les choses serieuses.',
        ];

        foreach ($outros as $slug => $outro) {
            $this->db->table('missions')->where('slug', $slug)->update(['outro' => $outro]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('missions', 'outro');
    }
}
