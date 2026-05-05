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
        // ----- Theme tokens CyberRun -----
        // Changer ces 4 valeurs = changer tout l'aspect visuel du site.
        // Les couleurs "role" plus bas sont liees au gameplay (HP rouge, etc.) et restent stables.
        const THEME = {
            surface: '#000000',  // fond
            primary: '#22d3ee',  // accent principal (cyan neon)
            accent:  '#ec4899',  // identite forte (pink neon, titres)
            muted:   '#155e75',  // text secondaire / borders soft
        };
        // ----- Couleurs semantiques (role-based, stables) -----
        const ROLES = {
            hp:      '#ef4444',
            energy:  '#22d3ee',
            nerve:   '#eab308',
            xp:      '#22c55e',
            credits: '#eab308',
            danger:  '#ef4444',
            warning: '#eab308',
            success: '#22c55e',
            info:    '#22d3ee',
        };

        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['"JetBrains Mono"', '"Fira Code"', 'Consolas', 'monospace'],
                    },
                    colors: { ...THEME, ...ROLES },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'JetBrains Mono', 'Fira Code', Consolas, monospace; }
        /* Effet scanlines CRT — utilise la couleur primary via CSS var pour rester themable */
        :root { --scanline-color: 34, 211, 238; }
        .scanlines::before {
            content: '';
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(0deg, rgba(var(--scanline-color), 0.03) 0px, rgba(var(--scanline-color), 0.03) 1px, transparent 1px, transparent 3px);
            pointer-events: none;
            z-index: 50;
        }
    </style>
</head>
<body class="bg-surface text-primary min-h-screen scanlines">

<header class="border-b border-primary/30 bg-surface/80 backdrop-blur sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <a href="/" class="text-xl font-bold text-accent hover:text-pink-300 transition">
            [ CyberRun ]
        </a>
        <?= view('partials/nav') ?>
    </div>
</header>

<main class="container mx-auto px-4 py-6">
    <?= $this->renderSection('content') ?>
</main>

<footer class="border-t border-primary/30 mt-16">
    <div class="container mx-auto px-4 py-4 text-center text-xs text-primary/60">
        © 2026 CyberRun — projet en cours de construction
    </div>
</footer>

</body>
</html>
