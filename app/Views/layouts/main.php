<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title><?= esc($title ?? 'CyberTown') ?></title>

    <script src="https://unpkg.com/htmx.org@2.0.3" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['"JetBrains Mono"', '"Fira Code"', 'Consolas', 'monospace'],
                    },
                    colors: {
                        neon: {
                            cyan: '#22d3ee',
                            pink: '#ec4899',
                            green: '#22c55e',
                            yellow: '#eab308',
                        },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'JetBrains Mono', 'Fira Code', Consolas, monospace; }
        .scanlines::before {
            content: '';
            position: fixed;
            inset: 0;
            background: repeating-linear-gradient(0deg, rgba(34,211,238,0.03) 0px, rgba(34,211,238,0.03) 1px, transparent 1px, transparent 3px);
            pointer-events: none;
            z-index: 50;
        }
    </style>
</head>
<body class="bg-black text-neon-cyan min-h-screen scanlines">

<header class="border-b border-neon-cyan/30 bg-black/80 backdrop-blur sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <a href="/" class="text-xl font-bold text-neon-pink hover:text-pink-300 transition">
            [ CyberTown ]
        </a>
        <nav class="space-x-4 text-sm">
            <a href="/" class="hover:text-white transition">Accueil</a>
            <span class="text-neon-cyan/40">|</span>
            <span class="text-neon-cyan/40">v0.0.1</span>
        </nav>
    </div>
</header>

<main class="container mx-auto px-4 py-6">
    <?= $this->renderSection('content') ?>
</main>

<footer class="border-t border-neon-cyan/30 mt-16">
    <div class="container mx-auto px-4 py-4 text-center text-xs text-neon-cyan/60">
        © 2026 CyberTown — projet en cours de construction
    </div>
</footer>

</body>
</html>
