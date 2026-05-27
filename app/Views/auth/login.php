<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mx-auto" style="max-width: 28rem;">

    <h1 class="h3 mb-4 text-center">Connexion</h1>

    <?php if (session('error') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php elseif (session('errors') !== null): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('errors')]) ?>
    <?php endif ?>

    <?php if (session('message') !== null): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= url_to('login') ?>" method="post">
                <?= csrf_field() ?>

                <?= view('partials/input', [
                    'name' => 'email', 'label' => 'Email', 'type' => 'email',
                    'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email',
                ]) ?>

                <?= view('partials/input', [
                    'name' => 'password', 'label' => 'Mot de passe', 'type' => 'password',
                    'required' => true, 'autocomplete' => 'current-password',
                ]) ?>

                <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" <?= old('remember') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="remember">Rester connecté</label>
                    </div>
                <?php endif ?>

                <?= view('partials/button', ['label' => 'Connexion']) ?>
            </form>
        </div>
    </div>

    <div class="text-center small mt-3">
        <a href="<?= url_to('register') ?>" class="text-muted">Créer un compte</a>
        <?php if (setting('Auth.allowMagicLinkLogins')): ?>
            &nbsp;·&nbsp;
            <a href="<?= url_to('magic-link') ?>" class="text-muted">Mot de passe oublié</a>
        <?php endif ?>
    </div>

</div>

<?= $this->endSection() ?>
