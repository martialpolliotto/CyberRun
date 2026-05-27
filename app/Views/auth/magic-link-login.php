<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 28rem;">

    <h1 class="h3 mb-4 text-center">Lien magique</h1>

    <?php if (session('error') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="card">
        <div class="card-body">
            <p class="text-muted small mb-3">On t'envoie un lien d'accès par email.</p>
            <form action="<?= url_to('magic-link') ?>" method="post">
                <?= csrf_field() ?>

                <?= view('partials/input', [
                    'name' => 'email', 'label' => 'Email', 'type' => 'email',
                    'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email',
                ]) ?>

                <?= view('partials/button', ['label' => 'Envoyer le lien']) ?>
            </form>
        </div>
    </div>

    <div class="text-center small mt-3">
        <a href="<?= url_to('login') ?>" class="text-muted">Retour connexion</a>
    </div>

</div>

<?= $this->endSection() ?>
