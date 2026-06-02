<?= $this->extend('layouts/main') ?>

<?php
$categoryIcon = [
    'crime'   => '◈',
    'train'   => '↑',
    'eco'     => '¢',
    'social'  => '☷',
    'mission' => '✦',
    'status'  => '!',
    'level'   => '★',
];

/**
 * Construit la phrase finale a partir de la clef i18n + params.
 * Linkifie author/target vers /u/<username>.
 */
$renderLine = static function (array $row): string {
    $params  = $row['_params'] ?? [];
    $linkable = ['target', 'author'];
    $clean = [];
    foreach ($params as $k => $v) {
        if (in_array($k, $linkable, true) && is_string($v) && $v !== '') {
            $clean[$k] = '<a href="/u/' . esc($v, 'attr') . '" class="text-dark fw-semibold">' . esc($v) . '</a>';
        } else {
            $clean[$k] = esc((string) $v);
        }
    }
    return lang($row['action_key'], $clean);
};
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <h1 class="h3 mb-3">Logs d'activité — tous les joueurs</h1>

    <!-- Filtres -->
    <form method="get" action="/admin/logs" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control"
                       placeholder="Pseudo (auteur ou cible)…"
                       value="<?= esc($username ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="cat" class="form-select">
                    <option value="">— Toutes catégories —</option>
                    <?php foreach (\App\Models\ActivityLogModel::CATEGORIES as $k => $label): ?>
                        <option value="<?= esc($k) ?>" <?= $category === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="period" class="form-select">
                    <option value="">— Toutes périodes —</option>
                    <option value="hour" <?= $period === 'hour' ? 'selected' : '' ?>>Dernière heure</option>
                    <option value="day"  <?= $period === 'day'  ? 'selected' : '' ?>>Dernier jour</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Dernière semaine</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Filtrer</button>
                <?php if ($category !== null || $period !== null || ! empty($username)): ?>
                    <a href="/admin/logs" class="btn btn-outline-dark">×</a>
                <?php endif ?>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Activité globale</span>
            <span class="text-muted"><?= count($rows) ?> entrée<?= count($rows) > 1 ? 's' : '' ?> affichée<?= count($rows) > 1 ? 's' : '' ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 8rem;">Date</th>
                        <th style="width: 2rem;"></th>
                        <th style="width: 8rem;">Auteur</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="4" class="text-center text-muted fst-italic">Aucune entrée pour ces filtres.</td></tr>
                    <?php endif ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="text-muted font-monospace small"><?= esc(substr((string) $row['created_at'], 0, 19)) ?></td>
                            <td class="text-muted text-center"><?= esc($categoryIcon[$row['category']] ?? '·') ?></td>
                            <td>
                                <?php if (! empty($row['author_username'])): ?>
                                    <a href="/u/<?= esc($row['author_username']) ?>" class="text-dark text-decoration-none fw-semibold"><?= esc($row['author_username']) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td><?= $renderLine($row) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pager !== null): ?>
        <div class="d-flex justify-content-center mt-3">
            <?= $pager->links() ?>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
