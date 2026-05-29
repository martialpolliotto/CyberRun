<?= $this->extend('layouts/main') ?>

<?php
    $mins = intdiv($seconds_left, 60);
    $secs = $seconds_left % 60;
?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 40rem;">

    <h1 class="h3 mb-3 text-center">Prison</h1>

    <div class="card mb-3">
        <div class="card-body text-center">
            <p class="text-muted small text-uppercase mb-1">Temps restant</p>
            <div class="display-4 fw-bold font-monospace"
                 data-jail-countdown
                 data-seconds-left="<?= (int) $seconds_left ?>">
                <?= sprintf('%02d:%02d', $mins, $secs) ?>
            </div>
            <p class="text-muted small mt-2 mb-0">Tu sors automatiquement à zéro. La regen Life / NRG / NRV continue.</p>
        </div>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="card">
        <div class="card-header bg-light small text-uppercase fw-semibold">Tentative d'évasion</div>
        <div class="card-body">
            <ul class="small mb-3 ps-3">
                <li>Coût : <strong><?= (int) $escape_cost ?> nerve</strong></li>
                <li>Chance estimée : <strong><?= (int) $escape_pct ?>%</strong> (base + réflexes/2, max <?= (int) \App\Models\PlayerModel::ESCAPE_MAX_PCT ?>%)</li>
                <li>Si échec : <strong>+<?= (int) $escape_penalty ?> minutes</strong> au compteur</li>
            </ul>
            <form method="post" action="/jail/escape" class="m-0">
                <?= csrf_field() ?>
                <button type="submit"
                        <?= (int) $player['nerve_current'] < (int) $escape_cost ? 'disabled' : '' ?>
                        class="btn btn-dark w-100">
                    <?= (int) $player['nerve_current'] < (int) $escape_cost ? 'Nerve insuffisante' : 'Tenter une évasion' ?>
                </button>
            </form>
        </div>
    </div>

</div>

<script>
(function () {
    const el = document.querySelector('[data-jail-countdown]');
    if (! el) return;
    let left = parseInt(el.dataset.secondsLeft, 10);
    function tick() {
        if (left <= 0) {
            window.location.reload();
            return;
        }
        const m = Math.floor(left / 60), s = left % 60;
        el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        left--;
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

<?= $this->endSection() ?>
