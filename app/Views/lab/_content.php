<?php
/**
 * Partial : zone HTMX du Lab (resource bar Energy + 4 cards d'entrainement).
 * Cible swap pour /lab/train/{slug}, renvoye par le controller.
 *
 * @var array $player
 * @var int   $cost     coût NRG par session
 * @var int   $gain     points stat gagnes par session
 * @var array $statLabels  slug => label
 * @var array $statColumns slug => column name dans players
 * @var bool  $canTrain
 * @var ?string $flash_variant
 * @var ?string $flash_message
 */
helper('number');
$flash_variant = $flash_variant ?? null;
$flash_message = $flash_message ?? null;
?>
<div id="lab-content">
    <?php if ($flash_message !== null): ?>
        <?= view('partials/alert', ['variant' => $flash_variant ?? 'info', 'message' => $flash_message]) ?>
    <?php endif ?>

    <?= view('partials/resource_bar', [
        'label'   => 'Énergie disponible',
        'current' => $player['energy_current'],
        'max'     => $player['energy_max'],
        'color'   => 'energy',
    ]) ?>

    <div class="row g-3 mt-1">
        <?php foreach ($statLabels as $slug => $label): ?>
            <?php $value = (int) $player[$statColumns[$slug]]; ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="small text-muted text-uppercase"><?= esc($label) ?></div>
                                <div class="fs-3 fw-bold"><?= number_format($value) ?></div>
                            </div>
                            <div class="small text-muted">+<?= $gain ?> par session</div>
                        </div>
                        <button type="button"
                                <?= $canTrain ? '' : 'disabled' ?>
                                class="btn btn-dark w-100 cr-htmx-btn"
                                hx-post="/lab/train/<?= esc($slug, 'attr') ?>"
                                hx-headers='{"X-CSRF-TOKEN":"<?= csrf_hash() ?>"}'
                                hx-target="#lab-content"
                                hx-swap="outerHTML"
                                hx-disabled-elt="this">
                            <span class="cr-btn-text">
                                <?php if ($canTrain): ?>
                                    Entraîner (-<?= $cost ?> NRG)
                                <?php else: ?>
                                    <?= empty($player['in_hospital_until']) ? 'Énergie insuffisante' : 'En cyberclinique' ?>
                                <?php endif ?>
                            </span>
                            <span class="cr-btn-spinner spinner-border spinner-border-sm" role="status"></span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>
