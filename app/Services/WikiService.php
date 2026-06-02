<?php

namespace App\Services;

use Parsedown;

/**
 * Wiki gameplay genere a partir de docs/GAMEPLAY.md.
 *
 * Source unique : on garde la doc dans GAMEPLAY.md (versionnee git) et le wiki in-game
 * se met a jour automatiquement quand on ajoute des sections. Pas de doublon a maintenir.
 *
 * Format attendu :
 * - chaque section commence par un H2 "## N. Titre" (ex: "## 1. Tes ressources")
 * - tout ce qui precede le premier H2 est ignore (intro generale)
 * - lignes encadrees par <!-- HIDE --> ... <!-- /HIDE --> sont stripees pour les
 *   joueurs non-admin (servira a cacher les stats / params internes a terme)
 */
class WikiService
{
    private const SOURCE_PATH = ROOTPATH . 'docs/GAMEPLAY.md';

    /** Cache memoire pour ne pas re-parser le fichier a chaque appel dans une meme requete. */
    private static ?array $sections = null;

    /**
     * Liste toutes les sections : [{slug, number, title, body_md}].
     *
     * @return array<int, array{slug:string, number:string, title:string, body_md:string}>
     */
    public function listSections(): array
    {
        if (self::$sections !== null) return self::$sections;

        $raw = @file_get_contents(self::SOURCE_PATH);
        if ($raw === false) return self::$sections = [];

        // Split par H2 "## N. Title". On capture le numero+titre comme separateur.
        $pattern = '/^## ([\d]+)\. (.+)$/m';
        $matches = [];
        preg_match_all($pattern, $raw, $matches, PREG_OFFSET_CAPTURE);

        $out = [];
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $headingStart = (int) $matches[0][$i][1];
            $bodyStart    = $headingStart + strlen($matches[0][$i][0]);
            $bodyEnd      = $i + 1 < $count ? (int) $matches[0][$i + 1][1] : strlen($raw);
            $body         = trim(substr($raw, $bodyStart, $bodyEnd - $bodyStart));

            $number = (string) $matches[1][$i][0];
            $title  = trim((string) $matches[2][$i][0]);
            $out[]  = [
                'slug'    => $this->slugify($title),
                'number'  => $number,
                'title'   => $title,
                'body_md' => $body,
            ];
        }

        return self::$sections = $out;
    }

    /** Recupere une section par son slug. */
    public function findSection(string $slug): ?array
    {
        foreach ($this->listSections() as $s) {
            if ($s['slug'] === $slug) return $s;
        }
        return null;
    }

    /**
     * Rend le markdown d'une section en HTML.
     * $isAdmin = true affiche tout, false strip les blocs <!-- HIDE -->...<!-- /HIDE -->.
     */
    public function renderMarkdown(string $md, bool $isAdmin = false): string
    {
        if (! $isAdmin) {
            $md = preg_replace('/<!--\s*HIDE\s*-->.*?<!--\s*\/HIDE\s*-->/s', '', $md) ?? $md;
        }
        $parser = new Parsedown();
        $parser->setSafeMode(true); // pas de HTML brut autorise
        return $parser->text($md);
    }

    /** "Tes ressources" -> "tes-ressources". ASCII only. */
    private function slugify(string $title): string
    {
        $s = mb_strtolower($title, 'UTF-8');
        // Translit basique des accents francais.
        $s = strtr($s, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            'ñ' => 'n',
        ]);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
        return trim($s, '-');
    }
}
