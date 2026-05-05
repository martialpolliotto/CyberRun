<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-md mx-auto mt-8">
    <?= view('partials/alert', [
        'variant' => 'success',
        'message' => 'Si un compte est associé à cet email, un lien de connexion vient d\'être envoyé. Vérifie ta boîte mail.',
    ]) ?>

    <div class="text-center text-sm mt-6 text-primary/60">
        <a href="<?= url_to('login') ?>" class="hover:text-accent transition">[ Retour connexion ]</a>
    </div>
</div>

<?= $this->endSection() ?>
