<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
.cr-wiki-content { line-height: 1.7; }
.cr-wiki-content h3 { margin-top: 1.5rem; font-size: 1.15rem; font-weight: 600; }
.cr-wiki-content h4 { margin-top: 1rem; font-size: 1rem; font-weight: 600; color: #495057; }
.cr-wiki-content table { width: 100%; margin: 0.75rem 0; font-size: 0.9rem; border-collapse: collapse; }
.cr-wiki-content table th, .cr-wiki-content table td { padding: 0.4rem 0.6rem; border: 1px solid #dee2e6; vertical-align: top; }
.cr-wiki-content table th { background: #f8f9fa; font-weight: 600; text-align: left; }
.cr-wiki-content code { background: #f1f3f5; padding: 0.1rem 0.3rem; border-radius: 3px; font-size: 0.85em; }
.cr-wiki-content pre { background: #212529; color: #f8f9fa; padding: 0.75rem; border-radius: 4px; overflow-x: auto; font-size: 0.85rem; }
.cr-wiki-content pre code { background: transparent; color: inherit; padding: 0; }
.cr-wiki-content blockquote { border-left: 3px solid #adb5bd; padding-left: 1rem; color: #6c757d; margin: 0.75rem 0; }
.cr-wiki-content ul, .cr-wiki-content ol { padding-left: 1.5rem; }
.cr-wiki-content li { margin: 0.15rem 0; }
.cr-wiki-content hr { margin: 1.5rem 0; }
.cr-wiki-content a { color: #212529; text-decoration: underline; }
.cr-wiki-content strong { font-weight: 600; }
</style>

<div class="mx-auto d-flex gap-3" style="max-width: 72rem;">

    <!-- Sommaire colonne gauche -->
    <aside class="d-none d-lg-block" style="width: 16rem; flex-shrink: 0;">
        <div class="card sticky-top" style="top: 4rem;">
            <div class="card-header bg-light small text-uppercase fw-semibold">Sommaire</div>
            <ul class="list-group list-group-flush small">
                <?php foreach ($sections as $s): ?>
                    <li class="list-group-item p-0">
                        <a href="/wiki/<?= esc($s['slug'], 'attr') ?>"
                           class="d-block px-3 py-1 text-decoration-none <?= $s['slug'] === $section['slug'] ? 'fw-bold bg-light text-dark' : 'text-muted' ?>">
                            <span class="font-monospace"><?= esc($s['number']) ?>.</span>
                            <?= esc($s['title']) ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </aside>

    <!-- Contenu -->
    <article class="flex-grow-1" style="min-width: 0;">

        <div class="small mb-2">
            <a href="/wiki" class="text-muted text-decoration-none">‹ Tous les sujets</a>
            <?php if ($is_admin): ?>
                <span class="badge bg-dark ms-2" title="Admin : sections masquees visibles">admin view</span>
            <?php endif ?>
        </div>

        <h1 class="h3 mb-3">
            <span class="text-muted font-monospace small me-2"><?= esc($section['number']) ?>.</span>
            <?= esc($section['title']) ?>
        </h1>

        <div class="cr-wiki-content">
            <?= $html ?>
        </div>

        <!-- Nav prev/next -->
        <?php
            $idx = null;
            foreach ($sections as $i => $s) {
                if ($s['slug'] === $section['slug']) { $idx = $i; break; }
            }
            $prev = $idx !== null && $idx > 0 ? $sections[$idx - 1] : null;
            $next = $idx !== null && $idx < count($sections) - 1 ? $sections[$idx + 1] : null;
        ?>
        <hr class="my-4">
        <div class="d-flex justify-content-between small">
            <?php if ($prev !== null): ?>
                <a href="/wiki/<?= esc($prev['slug'], 'attr') ?>" class="text-dark text-decoration-none">
                    ‹ <?= esc($prev['title']) ?>
                </a>
            <?php else: ?><span></span><?php endif ?>
            <?php if ($next !== null): ?>
                <a href="/wiki/<?= esc($next['slug'], 'attr') ?>" class="text-dark text-decoration-none">
                    <?= esc($next['title']) ?> ›
                </a>
            <?php else: ?><span></span><?php endif ?>
        </div>

    </article>
</div>

<?= $this->endSection() ?>
