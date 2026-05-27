<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 28rem;">
    <?= view('partials/alert', [
        'variant' => 'success',
        'message' => 'Si un compte est associé à cet email, un lien de connexion vient d\'être envoyé. Vérifie ta boîte mail.',
    ]) ?>

    <div class="text-center small mt-3">
        <a href="<?= url_to('login') ?>" class="text-muted">Retour connexion</a>
    </div>
</div>

<?= $this->endSection() ?>
