<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CrimeCategoryModel;
use App\Models\CrimeModel;
use App\Models\CrimeTextModel;

class Crimes extends BaseController
{
    public function index()
    {
        $crimes = model(CrimeModel::class)
            ->select('crimes.*, crime_categories.name AS category_name, crime_categories.slug AS category_slug')
            ->join('crime_categories', 'crime_categories.id = crimes.category_id', 'inner')
            ->orderBy('crime_categories.display_order')
            ->orderBy('crimes.min_category_xp')
            ->findAll();

        return view('admin/crimes/index', ['crimes' => $crimes]);
    }

    public function new()
    {
        return view('admin/crimes/form', [
            'crime'      => null,
            'categories' => model(CrimeCategoryModel::class)->listAll(),
        ]);
    }

    public function edit(int $id)
    {
        $c = model(CrimeModel::class)->find($id);
        if ($c === null) {
            return redirect()->to('/admin/crimes')->with('error', 'Crime introuvable.');
        }
        return view('admin/crimes/form', [
            'crime'      => $c,
            'categories' => model(CrimeCategoryModel::class)->listAll(),
            'texts'      => model(CrimeTextModel::class)->listGroupedForCrime($id),
        ]);
    }

    public function save(?int $id = null)
    {
        $model    = model(CrimeModel::class);
        $existing = $id ? $model->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to('/admin/crimes')->with('error', 'Crime introuvable.');
        }

        $dest = (string) $this->request->getPost('critical_destination');
        if (! in_array($dest, CrimeModel::CRITICAL_DESTINATIONS, true)) {
            $dest = 'jail';
        }

        $hourStart = $this->request->getPost('time_bonus_hour_start');
        $hourEnd   = $this->request->getPost('time_bonus_hour_end');

        $data = [
            'category_id'           => (int) $this->request->getPost('category_id'),
            'slug'                  => trim($this->request->getPost('slug') ?? ''),
            'name'                  => trim($this->request->getPost('name') ?? ''),
            'description'           => trim($this->request->getPost('description') ?? '') ?: null,
            'nerve_cost'            => max(1, (int) $this->request->getPost('nerve_cost')),
            'min_category_xp'       => max(0, (int) $this->request->getPost('min_category_xp')),
            'base_success_pct'      => max(0, min(99, (int) $this->request->getPost('base_success_pct'))),
            'critical_fail_pct'     => max(0, min(99, (int) $this->request->getPost('critical_fail_pct'))),
            'reward_credits_min'    => max(0, (int) $this->request->getPost('reward_credits_min')),
            'reward_credits_max'    => max(0, (int) $this->request->getPost('reward_credits_max')),
            'reward_xp'             => max(0, (int) $this->request->getPost('reward_xp')),
            'reward_category_xp'    => max(0, (int) $this->request->getPost('reward_category_xp')),
            'critical_destination'  => $dest,
            'critical_minutes_min'  => max(0, (int) $this->request->getPost('critical_minutes_min')),
            'critical_minutes_max'  => max(0, (int) $this->request->getPost('critical_minutes_max')),
            'time_bonus_pct'        => max(0, (int) $this->request->getPost('time_bonus_pct')),
            'time_bonus_hour_start' => ($hourStart === '' || $hourStart === null) ? null : max(0, min(23, (int) $hourStart)),
            'time_bonus_hour_end'   => ($hourEnd === '' || $hourEnd === null) ? null : max(0, min(23, (int) $hourEnd)),
        ];

        if ($data['slug'] === '' || $data['name'] === '' || $data['category_id'] <= 0) {
            return redirect()->back()->withInput()->with('error', 'Slug, nom et categorie sont obligatoires.');
        }
        if ($data['reward_credits_max'] < $data['reward_credits_min']) {
            $data['reward_credits_max'] = $data['reward_credits_min'];
        }
        if ($data['critical_minutes_max'] < $data['critical_minutes_min']) {
            $data['critical_minutes_max'] = $data['critical_minutes_min'];
        }
        $slugCheck = $model->where('slug', $data['slug']);
        if ($id !== null) { $slugCheck->where('id !=', $id); }
        if ($slugCheck->countAllResults() > 0) {
            return redirect()->back()->withInput()->with('error', 'Slug deja utilise.');
        }

        if ($id === null) {
            $model->insert($data);
            $id = $model->getInsertID();
        } else {
            $model->update($id, $data);
        }
        return redirect()->to('/admin/crimes/' . $id . '/edit')->with('message', 'Crime sauvegarde.');
    }

    public function destroy(int $id)
    {
        $model = model(CrimeModel::class);
        $c = $model->find($id);
        if ($c === null) {
            return redirect()->to('/admin/crimes')->with('error', 'Crime introuvable.');
        }
        if (! $this->request->getPost('confirm_delete')) {
            return redirect()->back()->with('error', 'Confirmation requise.');
        }
        $model->delete($id);
        return redirect()->to('/admin/crimes')->with('message', 'Crime supprime.');
    }

    /** Ajoute une variante de texte (outcome = success|fail|critical) pour un crime. */
    public function addText(int $id)
    {
        $crime = model(CrimeModel::class)->find($id);
        if ($crime === null) {
            return redirect()->to('/admin/crimes')->with('error', 'Crime introuvable.');
        }

        $outcome = (string) $this->request->getPost('outcome');
        $text    = trim((string) $this->request->getPost('text'));

        if (! in_array($outcome, CrimeTextModel::VALID_OUTCOMES, true)) {
            return redirect()->back()->with('error', 'Outcome invalide.');
        }
        if ($text === '') {
            return redirect()->back()->with('error', 'Texte vide, rien a ajouter.');
        }

        $data = $this->extractTextFields(['text' => $text, 'outcome' => $outcome]);
        $data['crime_id'] = $id;

        model(CrimeTextModel::class)->insert($data);

        return redirect()->to('/admin/crimes/' . $id . '/edit#texts')
            ->with('message', 'Variante "' . esc($outcome) . '" ajoutee.');
    }

    /** Met a jour une variante existante : texte + overrides eventuels. */
    public function updateText(int $id, int $textId)
    {
        $textModel = model(CrimeTextModel::class);
        $text      = $textModel->find($textId);
        if ($text === null || (int) $text['crime_id'] !== $id) {
            return redirect()->to('/admin/crimes/' . $id . '/edit')->with('error', 'Variante introuvable.');
        }

        $newText = trim((string) $this->request->getPost('text'));
        if ($newText === '') {
            return redirect()->to('/admin/crimes/' . $id . '/edit#texts')->with('error', 'Texte vide, modification refusee.');
        }

        $data = $this->extractTextFields([
            'text'    => $newText,
            'outcome' => (string) $text['outcome'],
        ]);

        $textModel->update($textId, $data);
        return redirect()->to('/admin/crimes/' . $id . '/edit#texts')
            ->with('message', 'Variante mise a jour.');
    }

    /**
     * Helper : extrait les champs texte + overrides du POST.
     * Un champ vide -> NULL (fallback aux valeurs du crime parent au runtime).
     *
     * @param array<string, mixed> $base
     * @return array<string, mixed>
     */
    private function extractTextFields(array $base): array
    {
        $nullIfEmpty = fn ($v) => ($v === '' || $v === null) ? null : $v;
        $intOrNull   = fn ($v) => ($v === '' || $v === null) ? null : max(0, (int) $v);

        $dest = $this->request->getPost('critical_destination');
        $destClean = in_array($dest, CrimeModel::CRITICAL_DESTINATIONS, true) ? $dest : null;

        return array_merge($base, [
            'reward_credits_min'   => $intOrNull($this->request->getPost('reward_credits_min')),
            'reward_credits_max'   => $intOrNull($this->request->getPost('reward_credits_max')),
            'reward_xp'            => $intOrNull($this->request->getPost('reward_xp')),
            'reward_category_xp'   => $intOrNull($this->request->getPost('reward_category_xp')),
            'critical_destination' => $nullIfEmpty($destClean),
            'critical_minutes_min' => $intOrNull($this->request->getPost('critical_minutes_min')),
            'critical_minutes_max' => $intOrNull($this->request->getPost('critical_minutes_max')),
        ]);
    }

    /** Supprime une variante de texte. */
    public function deleteText(int $id, int $textId)
    {
        $textModel = model(CrimeTextModel::class);
        $text      = $textModel->find($textId);
        if ($text === null || (int) $text['crime_id'] !== $id) {
            return redirect()->to('/admin/crimes/' . $id . '/edit')->with('error', 'Variante introuvable.');
        }

        $textModel->delete($textId);
        return redirect()->to('/admin/crimes/' . $id . '/edit#texts')
            ->with('message', 'Variante supprimee.');
    }
}
