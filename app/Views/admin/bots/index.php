<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 80rem;">

    <div class="alert alert-dark py-2 mb-3 d-flex align-items-center gap-2">
        <span class="fw-bold text-uppercase">[ ADMIN ]</span>
        <a href="/admin" class="text-decoration-none text-dark small">retour dashboard</a>
    </div>

    <h1 class="h3 mb-3">Bots</h1>
    <p class="text-muted small mb-3">Les bots agissent automatiquement à chaque tick cron (1 min) selon leur persona. Côté joueur, ils sont indistinguables des humains.</p>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Bloc création -->
    <div class="card mb-3">
        <div class="card-header bg-light small text-uppercase fw-semibold">Créer des bots</div>
        <div class="card-body">
            <form method="post" action="/admin/bots/populate" class="d-flex gap-2 align-items-end flex-wrap">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label small">Quantité</label>
                    <input type="number" name="count" min="1" max="50" value="5" class="form-control" style="width: 6rem;">
                </div>
                <div>
                    <label class="form-label small">Persona</label>
                    <select name="persona" class="form-select" style="width: 14rem;">
                        <?php foreach ($personas as $p): ?>
                            <option value="<?= esc($p) ?>"><?= esc($p) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-dark">+ Créer</button>
            </form>
            <p class="form-text mt-2 mb-0">Chaque bot reçoit un pseudo aléatoire + un user Shield avec password inutilisable. Le hook afterInsert crée automatiquement la fiche player.</p>
        </div>
    </div>

    <!-- Stats par persona -->
    <?php if (! empty($byPersona)): ?>
        <div class="d-flex gap-2 flex-wrap mb-3 small">
            <?php foreach ($byPersona as $p => $n): ?>
                <span class="badge bg-light text-dark border"><?= esc($p) ?> : <strong><?= (int) $n ?></strong></span>
            <?php endforeach ?>
            <span class="badge bg-dark">Total : <?= count($bots) ?></span>
        </div>
    <?php endif ?>

    <!-- Liste -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white small">
            <thead class="table-light">
                <tr>
                    <th>Pseudo</th>
                    <th>Persona</th>
                    <th>Niveau</th>
                    <th>Crédits</th>
                    <th>NRG / NRV / Life</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bots as $b): ?>
                    <tr>
                        <td><a href="/u/<?= esc($b['username']) ?>" class="text-decoration-none text-dark fw-bold"><?= esc($b['username']) ?></a></td>
                        <td><?= esc($b['bot_persona']) ?></td>
                        <td class="font-monospace"><?= (int) $b['level'] ?></td>
                        <td class="font-monospace"><?= number_format((int) $b['credits']) ?></td>
                        <td class="font-monospace small text-muted">
                            <?= (int) $b['energy_current'] ?>/<?= (int) $b['energy_max'] ?> ·
                            <?= (int) $b['nerve_current'] ?>/<?= (int) $b['nerve_max'] ?> ·
                            <?= (int) $b['hp_current'] ?>/<?= (int) $b['hp_max'] ?>
                        </td>
                        <td class="small">
                            <?php if (! empty($b['in_jail_until'])): ?>
                                <span class="badge bg-dark">Prison</span>
                            <?php elseif (! empty($b['in_hospital_until'])): ?>
                                <span class="badge bg-secondary">Cyberclinique</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted">Libre</span>
                            <?php endif ?>
                        </td>
                        <td class="text-end">
                            <form method="post" action="/admin/bots/<?= (int) $b['id'] ?>/destroy" class="d-inline m-0" onsubmit="return confirm('Supprimer ce bot ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="confirm_delete" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-dark">×</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($bots)): ?>
                    <tr><td colspan="7" class="text-center text-muted fst-italic">Aucun bot. Utilise le formulaire ci-dessus pour en créer.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <?php if (! empty($bots)): ?>
        <div class="card border-dark mt-3">
            <div class="card-header bg-dark text-white small text-uppercase fw-semibold">Zone dangereuse</div>
            <div class="card-body">
                <p class="small">Suppression en masse de bots par persona, ou tous d'un coup.</p>
                <form method="post" action="/admin/bots/destroy-all" class="d-flex gap-2 align-items-end flex-wrap" onsubmit="return confirm('Supprimer tous les bots correspondants ?');">
                    <?= csrf_field() ?>
                    <div>
                        <label class="form-label small">Persona</label>
                        <select name="persona" class="form-select" style="width: 14rem;">
                            <option value="*">— tous les bots —</option>
                            <?php foreach ($personas as $p): ?>
                                <option value="<?= esc($p) ?>"><?= esc($p) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirm_delete_all" name="confirm_delete_all" value="1" required>
                        <label class="form-check-label small" for="confirm_delete_all">Je confirme</label>
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm">Supprimer</button>
                </form>
            </div>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
