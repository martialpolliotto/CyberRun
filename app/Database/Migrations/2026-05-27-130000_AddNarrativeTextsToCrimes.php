<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ajoute 3 textes narratifs par crime, un par issue possible :
 *   - success_text  : ce qu'on raconte au joueur quand la tentative reussit
 *   - fail_text     : narration de l'echec simple
 *   - critical_text : narration de l'echec critique (qui menera en prison ou a l'hopital)
 *
 * Les chiffres (credits gagnes, minutes de peine) sont concatenes apres le texte par CrimeModel::attempt.
 * Si un texte est NULL, on retombe sur les phrases par defaut.
 */
class AddNarrativeTextsToCrimes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('crimes', [
            'success_text'  => ['type' => 'TEXT', 'null' => true, 'after' => 'description'],
            'fail_text'     => ['type' => 'TEXT', 'null' => true, 'after' => 'success_text'],
            'critical_text' => ['type' => 'TEXT', 'null' => true, 'after' => 'fail_text'],
        ]);

        $texts = [
            'rc-fouille-poubelles' => [
                'success'  => 'Tu trouves un porte-monnaie oublie au fond d\'un sac. Le proprio ne reviendra pas le chercher.',
                'fail'     => 'Que des dechets et un rat mort. Tu te laves les mains trois fois et tu rentres.',
                'critical' => 'Un agent de proprete te chope en flagrant et appelle la patrouille.',
            ],
            'rc-mendicite' => [
                'success'  => 'Un cadre presse te file un billet pour que tu degages, plus un autre pour la peine. Bingo.',
                'fail'     => 'Trois heures debout, personne ne s\'arrete. Tu rentres avec la voix cassee, c\'est tout.',
                'critical' => 'La secu d\'un immeuble corpo decide de faire un exemple. Ils ne sont pas tendres.',
            ],
            'rc-vente-sauvette' => [
                'success'  => 'Tu refourges deux cyberdecks cracrades a un gamin qui pense faire une affaire. Tout le monde gagne, sauf lui.',
                'fail'     => 'Personne n\'a confiance dans ton stand monte sur cagettes. Tu remballes au crepuscule.',
                'critical' => 'NCPD descend en force, raffle generale. Le contremaitre te repere et te dose un sermon en cellule.',
            ],
            'pp-foule-metro' => [
                'success'  => 'Deux doigts dans la poche interieure d\'un costard, hop, portefeuille en main. Personne n\'a rien vu.',
                'fail'     => 'La cible bouge au mauvais moment, tu retires ta main vide. T\'esperes juste qu\'elle n\'a rien senti.',
                'critical' => 'Le mec se retourne, hurle "AU VOLEUR". Tout le quai te court apres. Tu finis menotte a un banc.',
            ],
            'pp-touriste-bourre' => [
                'success'  => 'Il croit que tu l\'aides a tenir debout. En realite tu lui delestes son portefeuille bourre de cash.',
                'fail'     => 'Il dessoule plus vite que prevu, il te chope l\'avant-bras. T\'arrives a t\'echapper sans gain.',
                'critical' => 'Ses potes sortent du bar pile a ce moment. Ils te tabassent jusqu\'a l\'arrivee des flics.',
            ],
            'pp-vol-moto' => [
                'success'  => 'Tu sautes sur la selle, kick et tu te casses dans un nuage de fumee. Le proprio hurle dans le retro.',
                'fail'     => 'L\'antivol electronique te court-circuite la main. Tu rentres en t\'inventant une excuse pour la brulure.',
                'critical' => 'A peine demarree, la moto se cabre. Tu finis dans le mur d\'en face. Les pompiers te raclent du bitume.',
            ],
            'hk-skimmer' => [
                'success'  => 'Skimmer pose, recuperation faite 24h plus tard. Une trentaine de cartes lisibles, prepayees sur le marche noir.',
                'fail'     => 'Le skimmer ne tient pas, l\'ATM le crache. Tu rentres avec un appareil 80 cred dans la poche.',
                'critical' => 'Une camera planquee t\'a capte poser le boitier. Les corpos te collent un agent de securite au cul, finis au poste.',
            ],
            'hk-intercept-nfc' => [
                'success'  => 'Tu rejoues trois autorisations volees avant qu\'on detecte le pattern. Le solde grossit en silence.',
                'fail'     => 'Ton emetteur capte mal, les autorisations sont rejetees. Soiree perdue.',
                'critical' => 'Une transaction trop grosse declenche la fraud detection. Les flics sont a ta porte avant que t\'aies eteint le materiel.',
            ],
            'hk-crack-atm' => [
                'success'  => 'Le firmware s\'effondre sous ton exploit. La machine crache des liasses comme un distributeur de bonbons.',
                'fail'     => 'L\'ATM se met en mode panique et appelle la maintenance. Tu degages avant qu\'ils n\'arrivent.',
                'critical' => 'L\'alarme silencieuse a tourne des le branchement. Trois fourgons NCPD t\'attendent dehors. Cuit.',
            ],
        ];

        foreach ($texts as $slug => $t) {
            $this->db->table('crimes')->where('slug', $slug)->update([
                'success_text'  => $t['success'],
                'fail_text'     => $t['fail'],
                'critical_text' => $t['critical'],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('crimes', 'success_text');
        $this->forge->dropColumn('crimes', 'fail_text');
        $this->forge->dropColumn('crimes', 'critical_text');
    }
}
