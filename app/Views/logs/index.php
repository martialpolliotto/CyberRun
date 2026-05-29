<?= $this->extend('layouts/main') ?>

<?php
    // Icone (texte) par categorie. On reste sobre, juste 1-2 caracteres.
    $categoryIcon = [
        'crime'   => '◈',
        'train'   => '↑',
        'eco'     => '¢',
        'social'  => '☷',
        'mission' => '✦',
        'status'  => '!',
        'level'   => '★',
    ];

    $now = \CodeIgniter\I18n\Time::now();

    /**
     * Formate un timestamp en distance relative compacte (1m, 4h, 2d, 1w).
     */
    $rel = static function (string $datetime) use ($now): string {
        $t       = \CodeIgniter\I18n\Time::parse($datetime);
        $seconds = max(1, $now->getTimestamp() - $t->getTimestamp());
        if ($seconds < 60)        return $seconds . 's';
        if ($seconds < 3600)      return intdiv($seconds, 60) . 'm';
        if ($seconds < 86400)     return intdiv($seconds, 3600) . 'h';
        if ($seconds < 7 * 86400) return intdiv($seconds, 86400) . 'd';
        return intdiv($seconds, 7 * 86400) . 'w';
    };

    /**
     * Construit la phrase finale à partir de la clef i18n + params.
     * - Le params 'target' / 'author' devient un <a href> vers /u/<username>.
     * Tous les params sont esc() avant injection (sauf les liens <a> qu'on fabrique nous-meme).
     */
    $renderLine = static function (array $row): string {
        $params = $row['_params'] ?? [];
        // Replace target/author par des liens si on a le username.
        $linkable = ['target', 'author'];
        $clean = [];
        foreach ($params as $k => $v) {
            if (in_array($k, $linkable, true) && is_string($v) && $v !== '') {
                $clean[$k] = '<a href="/u/' . esc($v, 'attr') . '" class="text-dark fw-semibold">' . esc($v) . '</a>';
            } else {
                $clean[$k] = esc((string) $v);
            }
        }
        // lang() supporte les placeholders {key} via le 2e arg.
        return lang($row['action_key'], $clean);
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0">Log d'activité</h1>
            <p class="text-muted small mb-0">Tout ce qui t'arrive (ou ce que tu fais subir aux autres).</p>
        </div>
    </div>

    <!-- Filtres -->
    <form method="get" action="/log" class="mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <select name="cat" class="form-select">
                    <option value="">— Toutes catégories —</option>
                    <?php foreach (\App\Models\ActivityLogModel::CATEGORIES as $k => $label): ?>
                        <option value="<?= esc($k) ?>" <?= $category === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-5">
                <select name="period" class="form-select">
                    <option value="">— Toutes périodes —</option>
                    <option value="hour" <?= $period === 'hour' ? 'selected' : '' ?>>Dernière heure</option>
                    <option value="day"  <?= $period === 'day'  ? 'selected' : '' ?>>Dernier jour</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Dernière semaine</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Filtrer</button>
                <?php if ($category !== null || $period !== null): ?>
                    <a href="/log" class="btn btn-outline-dark">×</a>
                <?php endif ?>
            </div>
        </div>
    </form>

    <!-- Liste -->
    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
            <span>Activité</span>
            <span class="text-muted"><?= count($rows) ?> entrée<?= count($rows) > 1 ? 's' : '' ?></span>
        </div>
        <ul class="list-group list-group-flush small">
            <?php if (empty($rows)): ?>
                <li class="list-group-item text-muted fst-italic text-center">Aucune activité pour ces filtres.</li>
            <?php endif ?>
            <?php foreach ($rows as $row): ?>
                <li class="list-group-item d-flex gap-3 align-items-start">
                    <span class="text-muted font-monospace small" style="width: 3rem; flex-shrink: 0; text-align: right;"><?= esc($rel($row['created_at'])) ?></span>
                    <span class="text-muted text-center" style="width: 1.5rem; flex-shrink: 0;"><?= esc($categoryIcon[$row['category']] ?? '·') ?></span>
                    <span class="flex-grow-1"><?= $renderLine($row) ?></span>
                </li>
            <?php endforeach ?>
        </ul>
    </div>

    <?php if ($pager !== null): ?>
        <div class="d-flex justify-content-center mt-3">
            <?= $pager->links() ?>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
