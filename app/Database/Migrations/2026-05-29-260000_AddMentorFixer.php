<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Onboarding niveau 2 : nouveau fixer "Le Mentor" debloque apres le fixer-quartier.
 *
 * Le fixer-quartier (unlock_order=1, 6 missions) couvrait profil + lab + entrainement
 * + shops + achat + equip. Le Mentor (unlock_order=2) force la decouverte du reste :
 * crimes, jobs, bazaar, chat, factions, wiki. Une fois ces 12 missions cleared, le
 * nouveau joueur a touche a TOUTES les mecaniques cles.
 */
class AddMentorFixer extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // Cree le fixer.
        $this->db->table('fixers')->insert([
            'slug'         => 'le-mentor',
            'name'         => 'Le Mentor',
            'tagline'      => 'Quand t\'as fait le tour du quartier, viens. J\'ai mieux a te montrer.',
            'description'  => 'Un vieux briscard qui a tout vu. Il prend les rookies qui ont passe les bases et leur montre les vrais coups : les crimes qui paient, le job stable, le marche P2P, la communaute. Apres lui, tu n\'es plus un debutant.',
            'image_path'   => null,
            'unlock_order' => 2,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        $fixerId = $this->db->insertID();

        // 6 missions : decouverte progressive des mecaniques cles.
        $missions = [
            [
                'slug'             => 'mnt-01-passe-en-mode-crime',
                'name'             => 'Passe en mode crime',
                'mission_order'    => 1,
                'brief'            => 'OK rookie. Tu as appris a t\'equiper, maintenant tu vas gagner ta croute. Va voir le catalogue des crimes du quartier, choisis-en un qui te plait.',
                'outro'            => 'Bien. Tu as vu qu\'il y a plusieurs categories : Force, Hack, Reflexes, Charisme. Ta categorie dominante depend de tes stats. Investis avant de tenter du gros.',
                'objective_type'   => 'visit_page',
                'objective_target' => 'crimes',
                'objective_count'  => 1,
                'reward_credits'   => 100,
                'reward_xp'        => 25,
                'reward_item_id'   => null,
            ],
            [
                'slug'             => 'mnt-02-premier-vrai-casse',
                'name'             => 'Premier vrai casse',
                'mission_order'    => 2,
                'brief'            => 'Maintenant tente-le. Peu importe lequel. La premiere fois ca peut foirer, t\'es nouveau. Mais c\'est en sentant l\'adrenaline que tu apprends.',
                'outro'            => 'Tu vois ? Reussi ou pas, tu sais maintenant ce que ca fait. Chaque crime a un coût en nerve, un % de reussite, et un risque d\'echec critique qui te mene en prison. Vise les % eleves au debut.',
                'objective_type'   => 'commit_crime',
                'objective_target' => '*',
                'objective_count'  => 1,
                'reward_credits'   => 200,
                'reward_xp'        => 50,
                'reward_item_id'   => null,
            ],
            [
                'slug'             => 'mnt-03-un-vrai-boulot',
                'name'             => 'Un vrai boulot',
                'mission_order'    => 3,
                'brief'            => 'Les crimes c\'est bien, mais ca paie pas le loyer si tu rates. Faut un job stable a cote. Va voir les annonces, choisis-en un.',
                'outro'            => 'Bien. Le salaire tombe chaque jour a heure fixe. Tu gagnes aussi des points de stats job (Tech / Endurance / Charisme) qui te permettent de monter en rang. C\'est ton revenu passif.',
                'objective_type'   => 'visit_page',
                'objective_target' => 'jobs',
                'objective_count'  => 1,
                'reward_credits'   => 200,
                'reward_xp'        => 50,
                'reward_item_id'   => null,
            ],
            [
                'slug'             => 'mnt-04-decouvre-le-bazaar',
                'name'             => 'Decouvre le bazaar',
                'mission_order'    => 4,
                'brief'            => 'Les vendeurs PNJ c\'est limite, leur stock est fixe. Mais entre joueurs il y a un vrai marche. Va voir le bazaar : tu pourras vendre tes loots et acheter aux autres.',
                'outro'            => 'Note : la maison prend 5%% de fee sur les ventes pour eviter l\'inflation, mais c\'est largement compense par les prix libres. Les bons coups sont la.',
                'objective_type'   => 'visit_page',
                'objective_target' => 'bazaar',
                'objective_count'  => 1,
                'reward_credits'   => 250,
                'reward_xp'        => 75,
                'reward_item_id'   => null,
            ],
            [
                'slug'             => 'mnt-05-causeons-un-peu',
                'name'             => 'Causeons un peu',
                'mission_order'    => 5,
                'brief'            => 'T\'es pas seul rookie. Y\'a plein d\'autres comme toi. Va voir le chat live (icone en bas a droite, ou page dediee). Le canal Debutants est la pour t\'aider, le Trade pour speculer.',
                'outro'            => 'Le chat a 4 channels publics : Global, Trade, Debutants, Company. Plus un channel prive par faction si tu en rejoins une. Tu peux mute les emmerdeurs.',
                'objective_type'   => 'visit_page',
                'objective_target' => 'chat',
                'objective_count'  => 1,
                'reward_credits'   => 300,
                'reward_xp'        => 100,
                'reward_item_id'   => null,
            ],
            [
                'slug'             => 'mnt-06-rejoins-le-gang',
                'name'             => 'Considere rejoindre un gang',
                'mission_order'    => 6,
                'brief'            => 'Pour finir : les factions. Un gang = de la protection, du respect, une treasury commune. Va voir la liste, vise une faction de ton niveau. Tu peux aussi en fonder une plus tard.',
                'outro'            => 'Tu as boucle la boucle rookie. Tu connais tous les outils. Maintenant c\'est ta partie. Bon courage.',
                'objective_type'   => 'visit_page',
                'objective_target' => 'factions',
                'objective_count'  => 1,
                'reward_credits'   => 500,
                'reward_xp'        => 150,
                'reward_item_id'   => null,
            ],
        ];

        foreach ($missions as $m) {
            $m['fixer_id']   = $fixerId;
            $m['created_at'] = $now;
            $m['updated_at'] = $now;
            $this->db->table('missions')->insert($m);
        }
    }

    public function down()
    {
        $row = $this->db->table('fixers')->where('slug', 'le-mentor')->get()->getRowArray();
        if ($row !== null) {
            $this->db->table('missions')->where('fixer_id', $row['id'])->delete();
            $this->db->table('fixers')->where('id', $row['id'])->delete();
        }
    }
}
