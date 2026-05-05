<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-md mx-auto mt-8 space-y-4">

    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-accent">&gt; INIT_NETRUNNER</h1>
        <p class="text-primary/60 text-sm mt-1">// Crée ton profil et plonge dans la grille.</p>
    </div>

    <?php if (session('error') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php elseif (session('errors') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('errors')]) ?>
    <?php endif ?>

    <?php
        $form = '<form action="' . url_to('register') . '" method="post" class="space-y-4">'
              . csrf_field();
    ?>
    <?= view('partials/bloc', [
        'title'   => 'NOUVELLE_INSCRIPTION',
        'variant' => 'primary',
        'slot'    => $form
            . view('partials/input', [
                'name' => 'username', 'label' => 'Pseudo', 'type' => 'text',
                'required' => true, 'autocomplete' => 'username',
                'placeholder' => 'ton-pseudo-de-runner',
            ])
            . view('partials/input', [
                'name' => 'email', 'label' => 'Email', 'type' => 'email',
                'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email',
            ])
            . view('partials/input', [
                'name' => 'password', 'label' => 'Mot de passe', 'type' => 'password',
                'required' => true, 'autocomplete' => 'new-password',
                'placeholder' => '8 caractères minimum',
            ])
            . view('partials/input', [
                'name' => 'password_confirm', 'label' => 'Confirmation', 'type' => 'password',
                'required' => true, 'autocomplete' => 'new-password',
            ])
            . view('partials/button', ['label' => 'Créer le compte', 'variant' => 'accent'])
            . '</form>',
    ]) ?>

    <div class="text-center text-sm text-primary/60">
        <a href="<?= url_to('login') ?>" class="hover:text-accent transition">[ Déjà inscrit ? Connexion ]</a>
    </div>

</div>

<?= $this->endSection() ?>
