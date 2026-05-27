<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 28rem;">

    <h1 class="h3 mb-4 text-center">Inscription</h1>

    <?php if (session('error') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php elseif (session('errors') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('errors')]) ?>
    <?php endif ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= url_to('register') ?>" method="post">
                <?= csrf_field() ?>

                <?= view('partials/input', [
                    'name' => 'username', 'label' => 'Pseudo', 'type' => 'text',
                    'required' => true, 'autocomplete' => 'username',
                ]) ?>

                <?= view('partials/input', [
                    'name' => 'email', 'label' => 'Email', 'type' => 'email',
                    'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email',
                ]) ?>

                <?= view('partials/input', [
                    'name' => 'password', 'label' => 'Mot de passe', 'type' => 'password',
                    'required' => true, 'autocomplete' => 'new-password',
                    'placeholder' => '8 caractères minimum',
                ]) ?>

                <?= view('partials/input', [
                    'name' => 'password_confirm', 'label' => 'Confirmation', 'type' => 'password',
                    'required' => true, 'autocomplete' => 'new-password',
                ]) ?>

                <?= view('partials/button', ['label' => 'Créer le compte']) ?>
            </form>
        </div>
    </div>

    <div class="text-center small mt-3">
        <a href="<?= url_to('login') ?>" class="text-muted">Déjà inscrit ? Connexion</a>
    </div>

</div>

<?= $this->endSection() ?>
