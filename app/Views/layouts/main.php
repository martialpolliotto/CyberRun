<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'CyberRun') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://unpkg.com/htmx.org@2.0.3" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
</head>
<body class="bg-white text-dark">

<header class="border-bottom bg-white sticky-top">
    <div class="container py-2 d-flex justify-content-between align-items-center">
        <a href="/" class="fs-5 fw-bold text-dark text-decoration-none">CyberRun</a>
        <?= view('partials/nav') ?>
    </div>
</header>

<?= view('partials/status_strip') ?>

<main class="container py-4">
    <?= $this->renderSection('content') ?>
</main>

<footer class="border-top mt-5 bg-light">
    <div class="container py-3 text-center small text-muted">
        © 2026 CyberRun — projet en cours de construction
    </div>
</footer>

</body>
</html>
