<?php

namespace App\Controllers;

use App\Services\WikiService;

/**
 * Wiki gameplay accessible aux joueurs. Lit docs/GAMEPLAY.md a l'execution et
 * decoupe par sections H2. Pas de stockage en DB : la doc git EST le wiki.
 */
class Wiki extends BaseController
{
    public function index()
    {
        $service = new WikiService();
        return view('wiki/index', [
            'sections' => $service->listSections(),
        ]);
    }

    public function show(string $slug)
    {
        $service = new WikiService();
        $section = $service->findSection($slug);
        if ($section === null) {
            return redirect()->to('/wiki')->with('error', 'Section introuvable.');
        }

        $isAdmin = function_exists('auth') && auth()->loggedIn() && auth()->user()->inGroup('admin', 'superadmin');

        return view('wiki/show', [
            'section'  => $section,
            'sections' => $service->listSections(),
            'html'     => $service->renderMarkdown($section['body_md'], $isAdmin),
            'is_admin' => $isAdmin,
        ]);
    }
}
