<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    $statusBadge = static function (string $status): string {
        return match ($status) {
            'jail'     => '<span class="badge bg-dark">Prison</span>',
            'hospital' => '<span class="badge bg-secondary">Cyberclinique</span>',
            default    => '<span class="badge bg-light text-muted">Libre</span>',
        };
    };

    $now = \CodeIgniter\I18n\Time::now();
    $remaining = static function (?string $datetime) use ($now): string {
        if (empty($datetime)) return '—';
        $until = \CodeIgniter\I18n\Time::parse($datetime);
        if ($until->isBefore($now)) return '—';
        $secs = $until->getTimestamp() - $now->getTimestamp();
        $mins = (int) ceil($secs / 60);
        return $mins . ' min';
    };

    $tabs = [
        null       => 'Tous',
        'jail'     => 'En prison',
        'hospital' => 'À la cyberclinique',
    ];
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="mb-3">
        <h1 class="h3 mb-0">Joueurs</h1>
        <p class="text-muted small mb-0">Recherche par pseudo ou parcours la communauté.</p>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <ul class="nav nav-tabs mb-3 flex-wrap">
        <?php foreach ($tabs as $key => $label): ?>
            <li class="nav-item">
                <a class="nav-link <?= $status === $key ? 'active text-dark fw-bold' : 'text-muted' ?>"
                   href="<?= $key === null ? '/players' : '/players/' . esc($key) ?>">
                    <?= esc($label) ?>
                </a>
            </li>
        <?php endforeach ?>
    </ul>

    <form method="get" action="<?= $status === null ? '/players' : '/players/' . esc($status) ?>" class="mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="q" value="<?= esc($query) ?>" placeholder="Pseudo…" class="form-control">
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="">— Tri par défaut —</option>
                    <?php foreach (\App\Models\PlayerModel::PLAYER_SORTS as $k => $label): ?>
                        <option value="<?= esc($k) ?>" <?= $sort === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="lvl" class="form-select">
                    <?php foreach (\App\Models\PlayerModel::PLAYER_LEVEL_BUCKETS as $k => $cfg): ?>
                        <option value="<?= esc($k) ?>" <?= $bucket === $k ? 'selected' : '' ?>><?= esc($cfg[2]) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Filtrer</button>
                <?php if ($query !== '' || $sort !== '' || $bucket !== 'all'): ?>
                    <a href="<?= $status === null ? '/players' : '/players/' . esc($status) ?>" class="btn btn-outline-dark">×</a>
                <?php endif ?>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>Pseudo</th>
                    <th>Niveau</th>
                    <?php if ($status === 'jail' || $status === 'hospital'): ?>
                        <th>Temps restant</th>
                    <?php else: ?>
                        <th>Inscription</th>
                    <?php endif ?>
                    <th class="text-end">Status</th>
                    <?php if ($status === 'jail'): ?>
                        <th class="text-end">Actions</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $isSelf = ($me !== null && (int) $me['id'] === (int) $r['id']); ?>
                    <tr>
                        <td><a href="/u/<?= esc($r['username']) ?>" class="text-decoration-none text-dark fw-bold"><?= esc($r['username']) ?></a></td>
                        <td class="font-monospace"><?= (int) $r['level'] ?></td>
                        <?php if ($status === 'jail'): ?>
                            <td class="font-monospace"><?= esc($remaining($r['in_jail_until'])) ?></td>
                        <?php elseif ($status === 'hospital'): ?>
                            <td class="font-monospace"><?= esc($remaining($r['in_hospital_until'])) ?></td>
                        <?php else: ?>
                            <td class="small text-muted"><?= esc(\CodeIgniter\I18n\Time::parse($r['joined_at'])->toLocalizedString('d MMM yyyy')) ?></td>
                        <?php endif ?>
                        <td class="text-end"><?= $statusBadge((string) $r['_status']) ?></td>
                        <?php if ($status === 'jail'): ?>
                            <td class="text-end">
                                <?php if ($isSelf): ?>
                                    <span class="text-muted small fst-italic">toi</span>
                                <?php else: ?>
                                    <form method="post" action="/bust/<?= (int) $r['id'] ?>" class="d-inline m-0" onsubmit="return confirm('Tenter un bust ? Echec = toi en prison.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-dark">Bust <?= (int) ($r['_bust_pct'] ?? 0) ?>%</button>
                                    </form>
                                    <form method="post" action="/bail/<?= (int) $r['id'] ?>" class="d-inline m-0" onsubmit="return confirm('Payer la caution (<?= (int) ($r['_bail_cost'] ?? 0) ?> credits) ?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-dark">Bail ¢<?= number_format((int) ($r['_bail_cost'] ?? 0)) ?></button>
                                    </form>
                                <?php endif ?>
                            </td>
                        <?php endif ?>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= $status === 'jail' ? 5 : 4 ?>" class="text-center text-muted fst-italic">
                        <?php if ($status === 'jail'): ?>
                            Personne en prison actuellement.
                        <?php elseif ($status === 'hospital'): ?>
                            Personne à la cyberclinique actuellement.
                        <?php else: ?>
                            Aucun joueur trouvé.
                        <?php endif ?>
                    </td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <?php if ($pager !== null): ?>
        <div class="d-flex justify-content-center mt-3">
            <?= $pager->links() ?>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
