<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 48rem;">

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h1 class="h3 mb-0">Personnalisation</h1>
            <p class="text-muted small mb-0">Bio + signature + avatar visibles sur ta fiche publique.</p>
        </div>
        <a href="/profile" class="text-muted text-decoration-none small">‹ retour profil</a>
    </div>

    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <form method="post" action="/profile/save" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Avatar -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Avatar</div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <?php if (! empty($player['avatar_path'])): ?>
                        <img src="<?= esc($player['avatar_path']) ?>" alt="avatar actuel"
                             class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded border bg-light d-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px;">
                            <i class="bi bi-person fs-1 text-muted"></i>
                        </div>
                    <?php endif ?>
                    <div class="flex-grow-1">
                        <input type="file" name="avatar" accept="image/*" class="form-control">
                        <div class="form-text">Formats acceptés : <?= esc(implode(', ', $allowed_exts)) ?>. Max ~2 Mo recommandé.</div>
                        <?php if (! empty($player['avatar_path'])): ?>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="avatar_reset" id="avatar_reset" value="1">
                                <label class="form-check-label small text-muted" for="avatar_reset">Supprimer l'avatar actuel</label>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Signature</div>
            <div class="card-body">
                <input type="text" name="signature" maxlength="<?= (int) $max_sig_len ?>"
                       class="form-control"
                       value="<?= esc(old('signature') ?? $player['signature'] ?? '') ?>"
                       placeholder="Tagline courte, affichée à côté de ton pseudo">
                <div class="form-text">Max <?= (int) $max_sig_len ?> caractères. Affichée sous ton pseudo sur ta fiche publique.</div>
            </div>
        </div>

        <!-- Bio -->
        <div class="card mb-3">
            <div class="card-header bg-light small text-uppercase fw-semibold">Bio</div>
            <div class="card-body">
                <textarea name="bio" maxlength="<?= (int) $max_bio_len ?>" rows="8"
                          class="form-control"
                          placeholder="Qui tu es. Tes objectifs. Tes ennemis. Tout ce que tu veux raconter."><?= esc(old('bio') ?? $player['bio'] ?? '') ?></textarea>
                <div class="form-text">Max <?= (int) $max_bio_len ?> caractères. Les retours à la ligne sont préservés. Pas de HTML.</div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="/profile" class="btn btn-outline-dark">Annuler</a>
            <button type="submit" class="btn btn-dark">Sauvegarder</button>
        </div>
    </form>

</div>

<?= $this->endSection() ?>
