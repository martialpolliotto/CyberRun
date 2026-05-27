<?= $this->extend('layouts/main') ?>

<?php
    $isEdit = $crime !== null;
    $val = static function (string $field, $default = '') use ($crime) {
        $old = old($field);
        if ($old !== null) return $old;
        if ($crime !== null && isset($crime[$field])) return $crime[$field];
        return $default;
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin/crimes" class="text-decoration-none text-dark small">retour crimes</a>
    </div>

    <h1 class="h3 mb-3"><?= $isEdit ? 'Éditer : ' . esc($crime['name']) : 'Nouveau crime' ?></h1>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="<?= $isEdit ? '/admin/crimes/' . (int) $crime['id'] . '/save' : '/admin/crimes/save' ?>">
        <?= csrf_field() ?>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Identité</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="category_id" class="form-label small">Catégorie parente</label>
                    <select id="category_id" name="category_id" required class="form-select">
                        <option value="">— choisir —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) $val('category_id', 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <?= view('partials/input', ['name' => 'slug', 'label' => 'Slug (URL, unique)', 'value' => $val('slug'), 'required' => true]) ?>
                <?= view('partials/input', ['name' => 'name', 'label' => 'Nom affiché', 'value' => $val('name'), 'required' => true]) ?>

                <div class="mb-3">
                    <label for="description" class="form-label small">Description (visible dans la liste, avant tentative)</label>
                    <textarea id="description" name="description" rows="3" class="form-control"><?= esc($val('description')) ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="min_category_xp" class="form-label small">XP catégorie minimum (palier de déblocage)</label>
                        <input id="min_category_xp" type="number" name="min_category_xp" min="0" value="<?= (int) $val('min_category_xp', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="nerve_cost" class="form-label small">Coût en nerve</label>
                        <input id="nerve_cost" type="number" name="nerve_cost" min="1" value="<?= (int) $val('nerve_cost', 1) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>


        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Probabilités</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="base_success_pct" class="form-label small">Base réussite (%)</label>
                        <input id="base_success_pct" type="number" name="base_success_pct" min="0" max="99" value="<?= (int) $val('base_success_pct', 50) ?>" class="form-control">
                        <div class="form-text">Ajusté par +stat/2 + cat_xp/10 + bonus horaire. Cap à 95%.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="critical_fail_pct" class="form-label small">Échec critique (%)</label>
                        <input id="critical_fail_pct" type="number" name="critical_fail_pct" min="0" max="99" value="<?= (int) $val('critical_fail_pct', 5) ?>" class="form-control">
                        <div class="form-text">Roll indépendant qui passe en premier.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Récompenses (réussite)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="reward_credits_min" class="form-label small">Crédits min</label>
                        <input id="reward_credits_min" type="number" name="reward_credits_min" min="0" value="<?= (int) $val('reward_credits_min', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="reward_credits_max" class="form-label small">Crédits max</label>
                        <input id="reward_credits_max" type="number" name="reward_credits_max" min="0" value="<?= (int) $val('reward_credits_max', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="reward_xp" class="form-label small">XP joueur</label>
                        <input id="reward_xp" type="number" name="reward_xp" min="0" value="<?= (int) $val('reward_xp', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="reward_category_xp" class="form-label small">XP catégorie</label>
                        <input id="reward_category_xp" type="number" name="reward_category_xp" min="0" value="<?= (int) $val('reward_category_xp', 1) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Conséquences (échec critique)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="critical_destination" class="form-label small">Destination</label>
                        <select id="critical_destination" name="critical_destination" class="form-select">
                            <option value="jail"     <?= $val('critical_destination', 'jail') === 'jail'     ? 'selected' : '' ?>>Prison</option>
                            <option value="hospital" <?= $val('critical_destination', 'jail') === 'hospital' ? 'selected' : '' ?>>Cyberclinique</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="critical_minutes_min" class="form-label small">Minutes min</label>
                        <input id="critical_minutes_min" type="number" name="critical_minutes_min" min="0" value="<?= (int) $val('critical_minutes_min', 5) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="critical_minutes_max" class="form-label small">Minutes max</label>
                        <input id="critical_minutes_max" type="number" name="critical_minutes_max" min="0" value="<?= (int) $val('critical_minutes_max', 15) ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Bonus horaire (optionnel)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="time_bonus_pct" class="form-label small">Bonus (%)</label>
                        <input id="time_bonus_pct" type="number" name="time_bonus_pct" min="0" value="<?= (int) $val('time_bonus_pct', 0) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="time_bonus_hour_start" class="form-label small">Heure début (0-23)</label>
                        <input id="time_bonus_hour_start" type="number" name="time_bonus_hour_start" min="0" max="23" value="<?= $val('time_bonus_hour_start', '') === null ? '' : esc((string) $val('time_bonus_hour_start', '')) ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="time_bonus_hour_end" class="form-label small">Heure fin (0-23)</label>
                        <input id="time_bonus_hour_end" type="number" name="time_bonus_hour_end" min="0" max="23" value="<?= $val('time_bonus_hour_end', '') === null ? '' : esc((string) $val('time_bonus_hour_end', '')) ?>" class="form-control">
                    </div>
                </div>
                <div class="form-text mt-2">Si la fenêtre est active à l'heure de la tentative, +bonus% sur le taux de réussite. Une fenêtre qui wrap minuit est supportée (ex: début=22, fin=5 ⇒ 22h-5h).</div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark w-100"><?= $isEdit ? 'Sauvegarder' : 'Créer' ?></button>
    </form>

    <?php if ($isEdit): ?>
        <?php
            $outcomeLabels = [
                'success'  => ['Réussite',        'bg-dark'],
                'fail'     => ['Échec',           'bg-secondary'],
                'critical' => ['Critique',        'bg-black'],
            ];

            // Aplatit toutes les variantes en une seule liste pour le tableau unifié.
            $allTexts = [];
            foreach (['success', 'fail', 'critical'] as $k) {
                foreach ($texts[$k] as $t) {
                    $t['outcome'] = $k;
                    $allTexts[] = $t;
                }
            }

            $truncate = static function (string $s, int $len = 80): string {
                $s = trim(preg_replace('/\s+/', ' ', $s));
                return mb_strlen($s) > $len ? mb_substr($s, 0, $len - 1) . '…' : $s;
            };

            // Formate une cellule range "min–max" en override, ou "—" si pas override.
            $fmtRange = static function ($min, $max): string {
                if ($min === null && $max === null) return '<span class="text-muted">—</span>';
                $a = $min ?? '?';
                $b = $max ?? '?';
                return '<span class="font-monospace">' . esc((string) $a) . '–' . esc((string) $b) . '</span>';
            };
            $fmtOne = static function ($v): string {
                if ($v === null) return '<span class="text-muted">—</span>';
                return '<span class="font-monospace">' . esc((string) $v) . '</span>';
            };
        ?>

        <h2 id="texts" class="h5 mt-4 mb-2">Scénarios narratifs</h2>
        <p class="small text-muted mb-3">
            Une variante par ligne. Le système pioche au hasard parmi les variantes du bon type à chaque tentative.
            Les colonnes <strong>récompenses/durée</strong> sont des <em>overrides</em> de la variante : si elles sont vides (—), le crime utilise ses valeurs par défaut
            (¢<?= (int) $crime['reward_credits_min'] ?>–<?= (int) $crime['reward_credits_max'] ?>, +<?= (int) $crime['reward_xp'] ?> XP, +<?= (int) $crime['reward_category_xp'] ?> cat, critique → <?= esc($crime['critical_destination']) ?> <?= (int) $crime['critical_minutes_min'] ?>–<?= (int) $crime['critical_minutes_max'] ?> min).
        </p>

        <div class="mb-3">
            <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modal-add-text">+ Ajouter une variante</button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white small">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Texte</th>
                        <th>Crédits</th>
                        <th>XP</th>
                        <th>XP cat.</th>
                        <th>Dest. crit.</th>
                        <th>Min. crit.</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allTexts === []): ?>
                        <tr><td colspan="8" class="text-center text-muted fst-italic">Aucune variante. Sans variante, un texte par défaut générique sera affiché.</td></tr>
                    <?php endif ?>
                    <?php foreach ($allTexts as $t): ?>
                        <?php [$label, $bg] = $outcomeLabels[$t['outcome']]; ?>
                        <tr>
                            <td><span class="badge <?= esc($bg) ?>"><?= esc($label) ?></span></td>
                            <td><?= esc($truncate((string) $t['text'])) ?></td>
                            <td><?= $fmtRange($t['reward_credits_min'] ?? null, $t['reward_credits_max'] ?? null) ?></td>
                            <td><?= $fmtOne($t['reward_xp'] ?? null) ?></td>
                            <td><?= $fmtOne($t['reward_category_xp'] ?? null) ?></td>
                            <td><?= $t['outcome'] === 'critical' ? ($t['critical_destination'] !== null ? '<span class="font-monospace">' . esc($t['critical_destination']) . '</span>' : '<span class="text-muted">—</span>') : '<span class="text-muted">·</span>' ?></td>
                            <td><?= $t['outcome'] === 'critical' ? $fmtRange($t['critical_minutes_min'] ?? null, $t['critical_minutes_max'] ?? null) : '<span class="text-muted">·</span>' ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modal-text-<?= (int) $t['id'] ?>">Modifier</button>
                                <form method="post" action="/admin/crimes/<?= (int) $crime['id'] ?>/texts/<?= (int) $t['id'] ?>/destroy" class="d-inline m-0" onsubmit="return confirm('Supprimer cette variante ?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-dark">×</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Modale d'édition par variante -->
        <?php foreach ($allTexts as $t): ?>
            <?php [$label] = $outcomeLabels[$t['outcome']]; ?>
            <div class="modal fade" id="modal-text-<?= (int) $t['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="post" action="/admin/crimes/<?= (int) $crime['id'] ?>/texts/<?= (int) $t['id'] ?>/save">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h5 class="modal-title">Variante <?= esc($label) ?> · #<?= (int) $t['id'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label small">Texte affiché au joueur</label>
                                    <textarea name="text" rows="5" class="form-control" required><?= esc($t['text']) ?></textarea>
                                </div>

                                <p class="form-text mb-2">Les champs ci-dessous sont des <strong>overrides</strong>. Laisse vide pour utiliser la valeur par défaut du crime.</p>

                                <?php if ($t['outcome'] === 'success'): ?>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label small">Crédits min</label>
                                            <input type="number" name="reward_credits_min" min="0" value="<?= $t['reward_credits_min'] !== null ? (int) $t['reward_credits_min'] : '' ?>" class="form-control" placeholder="<?= (int) $crime['reward_credits_min'] ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Crédits max</label>
                                            <input type="number" name="reward_credits_max" min="0" value="<?= $t['reward_credits_max'] !== null ? (int) $t['reward_credits_max'] : '' ?>" class="form-control" placeholder="<?= (int) $crime['reward_credits_max'] ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">XP joueur</label>
                                            <input type="number" name="reward_xp" min="0" value="<?= $t['reward_xp'] !== null ? (int) $t['reward_xp'] : '' ?>" class="form-control" placeholder="<?= (int) $crime['reward_xp'] ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">XP catégorie</label>
                                            <input type="number" name="reward_category_xp" min="0" value="<?= $t['reward_category_xp'] !== null ? (int) $t['reward_category_xp'] : '' ?>" class="form-control" placeholder="<?= (int) $crime['reward_category_xp'] ?>">
                                        </div>
                                    </div>
                                <?php elseif ($t['outcome'] === 'critical'): ?>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">Destination</label>
                                            <select name="critical_destination" class="form-select">
                                                <option value="">— défaut (<?= esc($crime['critical_destination']) ?>) —</option>
                                                <option value="jail"     <?= ($t['critical_destination'] ?? '') === 'jail' ? 'selected' : '' ?>>Prison</option>
                                                <option value="hospital" <?= ($t['critical_destination'] ?? '') === 'hospital' ? 'selected' : '' ?>>Cyberclinique</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Minutes min</label>
                                            <input type="number" name="critical_minutes_min" min="0" value="<?= $t['critical_minutes_min'] !== null ? (int) $t['critical_minutes_min'] : '' ?>" class="form-control" placeholder="<?= (int) $crime['critical_minutes_min'] ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Minutes max</label>
                                            <input type="number" name="critical_minutes_max" min="0" value="<?= $t['critical_minutes_max'] !== null ? (int) $t['critical_minutes_max'] : '' ?>" class="form-control" placeholder="<?= (int) $crime['critical_minutes_max'] ?>">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small fst-italic mb-0">L'échec simple n'a pas de récompense ni de conséquence — seul le texte est éditable.</p>
                                <?php endif ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-dark">Sauvegarder</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach ?>

        <!-- Modale d'ajout -->
        <div class="modal fade" id="modal-add-text" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="post" action="/admin/crimes/<?= (int) $crime['id'] ?>/texts/add">
                        <?= csrf_field() ?>
                        <div class="modal-header">
                            <h5 class="modal-title">Nouvelle variante</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small">Type d'issue</label>
                                <select name="outcome" required class="form-select">
                                    <option value="success">Réussite</option>
                                    <option value="fail">Échec simple</option>
                                    <option value="critical">Échec critique</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Texte affiché au joueur</label>
                                <textarea name="text" rows="5" class="form-control" required></textarea>
                            </div>

                            <p class="form-text mb-2">Les champs ci-dessous sont des overrides. Laisse vide pour utiliser les valeurs du crime parent.</p>

                            <div class="row g-2 mb-2">
                                <div class="col-md-3">
                                    <label class="form-label small">Crédits min</label>
                                    <input type="number" name="reward_credits_min" min="0" class="form-control" placeholder="<?= (int) $crime['reward_credits_min'] ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Crédits max</label>
                                    <input type="number" name="reward_credits_max" min="0" class="form-control" placeholder="<?= (int) $crime['reward_credits_max'] ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">XP joueur</label>
                                    <input type="number" name="reward_xp" min="0" class="form-control" placeholder="<?= (int) $crime['reward_xp'] ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">XP catégorie</label>
                                    <input type="number" name="reward_category_xp" min="0" class="form-control" placeholder="<?= (int) $crime['reward_category_xp'] ?>">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small">Destination critique</label>
                                    <select name="critical_destination" class="form-select">
                                        <option value="">— défaut (<?= esc($crime['critical_destination']) ?>) —</option>
                                        <option value="jail">Prison</option>
                                        <option value="hospital">Cyberclinique</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Minutes critique min</label>
                                    <input type="number" name="critical_minutes_min" min="0" class="form-control" placeholder="<?= (int) $crime['critical_minutes_min'] ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Minutes critique max</label>
                                    <input type="number" name="critical_minutes_max" min="0" class="form-control" placeholder="<?= (int) $crime['critical_minutes_max'] ?>">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-dark">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-dark mt-3">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold">Zone dangereuse</div>
            <div class="card-body">
                <form method="post" action="/admin/crimes/<?= (int) $crime['id'] ?>/destroy" onsubmit="return confirm('Supprimer définitivement ce crime ?')">
                    <?= csrf_field() ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirm_delete" name="confirm_delete" value="1" required>
                        <label class="form-check-label small" for="confirm_delete">Je confirme.</label>
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
