<?= $this->extend('layouts/main') ?>

<?php helper('number'); ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 64rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <h1 class="h3 mb-3">Tweak persos</h1>
    <p class="text-muted small mb-3">
        Ajuste tes stats directement (debug / playtest). Les valeurs sont clampées : 0 minimum, max pour Life/NRG/NRV.
    </p>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php foreach ($fields_by_group as $group => $fieldKeys): ?>
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold"><?= esc($group_labels[$group] ?? $group) ?></div>
            <div class="table-responsive">
                <table class="table table-borderless mb-0 align-middle small">
                    <tbody>
                        <?php foreach ($fieldKeys as $field): ?>
                            <?php $cfg = $fields[$field]; ?>
                            <tr>
                                <td style="width: 12rem;">
                                    <strong><?= esc($cfg['label']) ?></strong>
                                    <div class="text-muted small font-monospace"><?= esc($field) ?></div>
                                </td>
                                <td style="width: 9rem;" class="font-monospace fw-bold">
                                    <?= number_format((int) ($me[$field] ?? 0)) ?>
                                    <?php if ($cfg['max_field'] !== null): ?>
                                        <span class="text-muted">/ <?= number_format((int) ($me[$cfg['max_field']] ?? 0)) ?></span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <?php
                                            $deltas = [-100, -10, -1, 1, 10, 100];
                                        ?>
                                        <?php foreach ($deltas as $d): ?>
                                            <form method="post" action="/admin/player-tools/adjust" class="m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="field"  value="<?= esc($field) ?>">
                                                <input type="hidden" name="action" value="delta">
                                                <input type="hidden" name="delta"  value="<?= (int) $d ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-dark font-monospace"><?= $d > 0 ? '+' . $d : $d ?></button>
                                            </form>
                                        <?php endforeach ?>
                                        <form method="post" action="/admin/player-tools/adjust" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="field"  value="<?= esc($field) ?>">
                                            <input type="hidden" name="action" value="zero">
                                            <button type="submit" class="btn btn-sm btn-outline-dark">0</button>
                                        </form>
                                        <?php if ($cfg['max_field'] !== null): ?>
                                            <form method="post" action="/admin/player-tools/adjust" class="m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="field"  value="<?= esc($field) ?>">
                                                <input type="hidden" name="action" value="max">
                                                <button type="submit" class="btn btn-sm btn-dark">max</button>
                                            </form>
                                        <?php endif ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach ?>

</div>

<?= $this->endSection() ?>
