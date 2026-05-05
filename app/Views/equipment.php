<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-end justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-accent">&gt; EQUIPEMENT</h1>
            <p class="text-primary/60 text-sm mt-1">// Gère ton chrome. Un item équipé par slot.</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-primary/60 uppercase tracking-wider">Pseudo</p>
            <p class="text-accent font-bold"><?= esc($user->username) ?></p>
        </div>
    </div>

    <!-- Flash -->
    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <!-- Stats récap (base + bonus = total) -->
    <div>
        <p class="text-xs text-primary/60 mb-2 uppercase tracking-wider">&gt; STATS_EFFECTIVES</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php
                $statLabels = ['force' => 'Force', 'blindage' => 'Blindage', 'reflexes' => 'Réflexes', 'hack' => 'Hack'];
            ?>
            <?php foreach ($statLabels as $key => $label): ?>
                <div class="border border-primary/30 bg-black/40 p-3 text-center">
                    <p class="text-primary/70 text-xs uppercase tracking-wider"><?= $label ?></p>
                    <p class="text-3xl text-white font-bold mt-1"><?= $stats['total'][$key] ?></p>
                    <p class="text-xs text-primary/60 mt-1">
                        <?= $stats['base'][$key] ?>
                        <?php if ($stats['bonus'][$key] > 0): ?>
                            <span class="text-success">+ <?= $stats['bonus'][$key] ?></span>
                        <?php endif ?>
                    </p>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Slots équipés -->
    <div>
        <p class="text-xs text-primary/60 mb-2 uppercase tracking-wider">&gt; SLOTS_ÉQUIPÉS</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($slots as $slotKey => $slotLabel): ?>
                <?php $eq = $equipped[$slotKey] ?? null; ?>
                <div class="border border-primary/30 bg-black/40 p-3">
                    <div class="flex items-baseline justify-between">
                        <p class="text-primary/70 text-xs uppercase tracking-wider"><?= $slotLabel ?></p>
                        <?php if ($eq): ?>
                            <form method="post" action="/equipment/unequip/<?= esc($slotKey) ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-xs text-danger hover:text-red-300 transition">[déséquiper]</button>
                            </form>
                        <?php endif ?>
                    </div>
                    <?php if ($eq): ?>
                        <p class="text-white font-bold mt-1"><?= esc($eq['item_name']) ?></p>
                        <p class="text-xs mt-1"><?= view('partials/bonus_inline', ['item' => $eq]) ?></p>
                        <?php if (! empty($eq['item_description'])): ?>
                            <p class="text-primary/50 text-xs italic mt-2">// <?= esc($eq['item_description']) ?></p>
                        <?php endif ?>
                    <?php else: ?>
                        <p class="text-primary/30 italic mt-1">(aucun équipé)</p>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Inventaire disponible -->
    <div>
        <p class="text-xs text-primary/60 mb-2 uppercase tracking-wider">&gt; INVENTAIRE_DISPONIBLE</p>
        <?php if (empty($available)): ?>
            <p class="text-primary/40 italic text-sm">Aucun item disponible (tout est équipé).</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($slots as $slotKey => $slotLabel): ?>
                    <?php $items = $available[$slotKey] ?? []; ?>
                    <?php if (empty($items)) continue; ?>
                    <div>
                        <p class="text-primary/60 text-xs uppercase tracking-wider mb-1"><?= $slotLabel ?></p>
                        <div class="space-y-2">
                            <?php foreach ($items as $it): ?>
                                <div class="flex items-center justify-between border border-primary/20 bg-black/30 p-2">
                                    <div>
                                        <p class="text-white text-sm font-bold"><?= esc($it['item_name']) ?></p>
                                        <p class="text-xs"><?= view('partials/bonus_inline', ['item' => $it]) ?></p>
                                    </div>
                                    <form method="post" action="/equipment/equip/<?= (int) $it['id'] ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-3 py-1 border border-accent/60 text-accent hover:bg-accent hover:text-white transition text-xs uppercase tracking-wider">
                                            Équiper
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <!-- Cache obsolète : items hors-circuit (read-only) -->
    <?php if (! empty($obsolete)): ?>
        <div>
            <p class="text-xs text-warning/80 mb-2 uppercase tracking-wider">&gt; CACHE_OBSOLÈTE <span class="text-warning/40">(items hors-circuit, ne peuvent plus être équipés)</span></p>
            <div class="space-y-2">
                <?php foreach ($obsolete as $it): ?>
                    <div class="flex items-center justify-between border border-warning/30 bg-warning/5 p-2 opacity-80">
                        <div>
                            <p class="text-warning/80 text-sm font-bold"><?= esc($it['item_name']) ?></p>
                            <p class="text-xs text-primary/50"><?= view('partials/bonus_inline', ['item' => $it]) ?></p>
                        </div>
                        <span class="text-xs text-warning/60 italic uppercase tracking-wider">hors-circuit</span>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    <?php endif ?>

</div>

<?= $this->endSection() ?>
