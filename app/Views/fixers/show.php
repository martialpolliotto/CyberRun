<?= $this->extend('layouts/main') ?>

<?php
    helper('number');

    $typeLabel = static function (string $type, string $target, int $count): string {
        $statLabel = static fn(string $s) => match ($s) {
            'force' => 'Force', 'blindage' => 'Blindage', 'reflexes' => 'Réflexes', 'hack' => 'Hack',
            default => $s,
        };
        $pageLabel = static fn(string $p) => match ($p) {
            'profile' => 'ta fiche profil', 'lab' => 'le Lab', 'shops' => 'les marchés',
            'equipment' => 'la page Équipement', 'fixers' => 'la page Fixers',
            default => $p,
        };
        return match ($type) {
            'visit_page'    => 'Visiter ' . $pageLabel($target),
            'train_stat'    => $target === '*' ? 'Entraîner ' . $count . ' fois (n\'importe quelle stat)' : 'Entraîner ' . $statLabel($target) . ' ' . $count . ' fois',
            'reach_stat'    => 'Atteindre ' . $count . ' en ' . $statLabel($target),
            'reach_level'   => 'Atteindre le niveau ' . $count,
            'buy_item'      => $target === '*' ? 'Acheter ' . $count . ' item(s)' : 'Acheter ' . $count . ' fois chez ' . $target,
            'equip_slot'    => $target === '*' ? 'Équiper ' . $count . ' item(s)' : 'Équiper le slot ' . $target,
            'spend_credits' => 'Dépenser ' . number_format($count) . ' crédits cumulés',
            default         => $type,
        };
    };
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 56rem;">

    <div class="small mb-3">
        <a href="/fixers" class="text-muted text-decoration-none">← Tous les fixers</a>
    </div>

    <!-- Header fixer -->
    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row gap-3">
            <?php if (! empty($fixer['image_path'])): ?>
                <img src="<?= esc($fixer['image_path']) ?>" alt="<?= esc($fixer['name']) ?>"
                     class="object-fit-cover bg-light border" style="width: 10rem; height: 10rem;">
            <?php else: ?>
                <div class="bg-light border d-flex align-items-center justify-content-center text-muted small text-uppercase" style="width: 10rem; height: 10rem;">
                    portrait
                </div>
            <?php endif ?>
            <div class="flex-grow-1">
                <h1 class="h3 mb-1"><?= esc($fixer['name']) ?></h1>
                <?php if (! empty($fixer['tagline'])): ?>
                    <p class="fst-italic mb-2">« <?= esc($fixer['tagline']) ?> »</p>
                <?php endif ?>
                <?php if (! empty($fixer['description'])): ?>
                    <p class="small mb-0"><?= esc($fixer['description']) ?></p>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Mission courante -->
    <?php if ($current === null): ?>
        <div class="card mb-3">
            <div class="card-body text-center text-muted">
                <p class="mb-0 fst-italic">Tu as terminé toutes les missions de <?= esc($fixer['name']) ?>. Reviens plus tard, ou va voir un autre fixer.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold d-flex justify-content-between">
                <span>Mission #<?= (int) $current['mission_order'] ?></span>
                <span class="text-muted">Récompense : ¢<?= number_format((int) $current['reward_credits']) ?> · <?= (int) $current['reward_xp'] ?> XP</span>
            </div>
            <div class="card-body">
                <h2 class="h5 mb-2"><?= esc($current['name']) ?></h2>
                <?php if (! empty($current['brief'])): ?>
                    <p class="mb-3"><?= nl2br(esc($current['brief'])) ?></p>
                <?php endif ?>

                <div class="small text-muted text-uppercase mb-1">Objectif</div>
                <p class="mb-3">
                    <?= esc($typeLabel((string) $current['objective_type'], (string) $current['objective_target'], (int) $current['objective_count'])) ?>
                </p>

                <?php if ($current['player_status'] === null): ?>
                    <form method="post" action="/fixers/accept/<?= (int) $current['id'] ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">Accepter la mission</button>
                    </form>
                <?php elseif ($current['player_status'] === 'in_progress'): ?>
                    <?php
                        $progress = (int) $current['player_progress'];
                        $target   = (int) $current['objective_count'];
                        $pct      = (int) round(($progress / max(1, $target)) * 100);
                    ?>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted text-uppercase">Progression</span>
                        <span class="font-monospace"><?= $progress ?> / <?= $target ?></span>
                    </div>
                    <div class="progress cr-bar-notched" style="height: 8px;">
                        <div class="progress-bar cr-bar-mission" style="width: <?= $pct ?>%"></div>
                    </div>
                <?php elseif ($current['player_status'] === 'completed'): ?>
                    <?php if (! empty($current['outro'])): ?>
                        <div class="bg-light border-start border-dark border-3 p-3 mb-3 fst-italic">
                            « <?= nl2br(esc($current['outro'])) ?> »
                        </div>
                    <?php endif ?>
                    <form method="post" action="/fixers/claim/<?= (int) $current['id'] ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-dark">Réclamer la récompense</button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    <?php endif ?>

    <!-- Historique missions claimed -->
    <?php if (! empty($claimed)): ?>
        <h2 class="small text-uppercase text-muted mb-2">Missions accomplies</h2>
        <ul class="list-group">
            <?php foreach ($claimed as $m): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <span class="text-muted small me-2">#<?= (int) $m['mission_order'] ?></span>
                        <?= esc($m['name']) ?>
                    </span>
                    <span class="small text-muted">¢<?= number_format((int) $m['reward_credits']) ?> · <?= (int) $m['reward_xp'] ?> XP</span>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
