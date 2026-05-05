<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-md mx-auto mt-8 space-y-4">

    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-accent">&gt; ACCESS_TERMINAL</h1>
        <p class="text-primary/60 text-sm mt-1">// Identifie-toi pour rejoindre la grille.</p>
    </div>

    <?php if (session('error') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php elseif (session('errors') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('errors')]) ?>
    <?php endif ?>

    <?php if (session('message') !== null): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>

    <?php
        $form = '<form action="' . url_to('login') . '" method="post" class="space-y-4">'
              . csrf_field();
    ?>
    <?= view('partials/bloc', [
        'title'   => 'CONNEXION',
        'variant' => 'primary',
        'slot'    => $form
            . view('partials/input', [
                'name' => 'email', 'label' => 'Email', 'type' => 'email',
                'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email',
            ])
            . view('partials/input', [
                'name' => 'password', 'label' => 'Mot de passe', 'type' => 'password',
                'required' => true, 'autocomplete' => 'current-password',
            ])
            . (setting('Auth.sessionConfig')['allowRemembering']
                ? '<label class="flex items-center gap-2 text-sm text-primary/80 cursor-pointer">'
                . '<input type="checkbox" name="remember" class="accent-accent"' . (old('remember') ? ' checked' : '') . '>'
                . 'Rester connecté</label>'
                : '')
            . view('partials/button', ['label' => 'Connexion', 'variant' => 'accent'])
            . '</form>',
    ]) ?>

    <div class="text-center text-sm space-x-2 text-primary/60">
        <a href="<?= url_to('register') ?>" class="hover:text-accent transition">[ Créer un compte ]</a>
        <?php if (setting('Auth.allowMagicLinkLogins')): ?>
            <span class="text-primary/30">|</span>
            <a href="<?= url_to('magic-link') ?>" class="hover:text-accent transition">[ Mot de passe oublié ]</a>
        <?php endif ?>
    </div>

</div>

<?= $this->endSection() ?>
