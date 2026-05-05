<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-md mx-auto mt-8 space-y-4">

    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-accent">&gt; LIEN_MAGIQUE</h1>
        <p class="text-primary/60 text-sm mt-1">// On t'envoie un lien d'accès par email.</p>
    </div>

    <?php if (session('error') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <?php
        $form = '<form action="' . url_to('magic-link') . '" method="post" class="space-y-4">'
              . csrf_field();
    ?>
    <?= view('partials/bloc', [
        'title'   => 'EMAIL_DE_RECUPERATION',
        'variant' => 'primary',
        'slot'    => $form
            . view('partials/input', [
                'name' => 'email', 'label' => 'Email', 'type' => 'email',
                'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email',
            ])
            . view('partials/button', ['label' => 'Envoyer le lien', 'variant' => 'accent'])
            . '</form>',
    ]) ?>

    <div class="text-center text-sm text-primary/60">
        <a href="<?= url_to('login') ?>" class="hover:text-accent transition">[ Retour connexion ]</a>
    </div>

</div>

<?= $this->endSection() ?>
