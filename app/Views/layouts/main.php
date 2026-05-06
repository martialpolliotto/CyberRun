<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title><?= esc($title ?? 'CyberRun') ?></title>

    <script src="https://unpkg.com/htmx.org@2.0.3" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // ----- Theme tokens CyberRun (sober Torn-like) -----
        // Changer ces tokens = changer toute l'identite visuelle.
        // Les couleurs "role" plus bas sont liees au gameplay (HP rouge, etc.) et restent stables.
        const THEME = {
            surface:  '#f5f5f4',  // page bg (stone-100, blanc cassé)
            'surface-alt': '#ffffff',  // cartes, blocs
            primary:  '#1f2937',  // text dominant (slate-800)
            accent:   '#0369a1',  // liens / actions importantes (sky-700)
            muted:    '#6b7280',  // text secondaire (gray-500)
            line:     '#d1d5db',  // borders (gray-300)
        };
        // ----- Couleurs semantiques (role-based, stables) -----
        const ROLES = {
            hp:      '#dc2626',  // red-600
            energy:  '#0284c7',  // sky-600
            nerve:   '#ca8a04',  // yellow-600
            xp:      '#16a34a',  // green-600
            credits: '#ca8a04',
            danger:  '#dc2626',
            warning: '#d97706',  // amber-600
            success: '#16a34a',
            info:    '#0284c7',
        };

        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', '"Fira Code"', 'Consolas', 'monospace'],
                    },
                    colors: { ...THEME, ...ROLES },
                },
            },
        };
    </script>
    <style>
        body { font-family: Inter, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-surface text-primary min-h-screen">

<header class="border-b border-line bg-surface-alt sticky top-0 z-40 shadow-sm">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <a href="/" class="text-lg font-bold text-accent hover:text-sky-900 transition">
            CyberRun
        </a>
        <?= view('partials/nav') ?>
    </div>
</header>

<main class="container mx-auto px-4 py-6">
    <?= $this->renderSection('content') ?>
</main>

<footer class="border-t border-line mt-16 bg-surface-alt">
    <div class="container mx-auto px-4 py-4 text-center text-xs text-muted">
        © 2026 CyberRun — projet en cours de construction
    </div>
</footer>

</body>
</html>
