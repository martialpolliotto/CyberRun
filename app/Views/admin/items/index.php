<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto space-y-4">

    <div class="border border-warning/60 bg-warning/10 px-4 py-2 flex items-center gap-3">
        <span class="text-warning font-bold uppercase tracking-widest">[ ADMIN ]</span>
        <a href="/admin" class="text-warning/80 text-sm hover:text-warning transition">// retour dashboard</a>
    </div>

    <div class="flex items-end justify-between flex-wrap gap-2">
        <h1 class="text-3xl md:text-4xl font-bold text-warning">&gt; ITEMS</h1>
        <a href="/admin/items/new" class="px-4 py-2 border border-accent bg-accent text-white font-bold uppercase tracking-wider hover:bg-pink-600 transition">
            + Nouvel item
        </a>
    </div>

    <?php if (session()->has('message')): ?>
        <?= view('partials/alert', ['variant' => 'success', 'message' => session('message')]) ?>
    <?php endif ?>
    <?php if (session()->has('error')): ?>
        <?= view('partials/alert', ['variant' => 'danger', 'message' => session('error')]) ?>
    <?php endif ?>

    <div class="border border-primary/30 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-primary/10 text-primary uppercase text-xs tracking-wider">
                <tr>
                    <th class="text-left p-2">Nom</th>
                    <th class="text-left p-2">Slot</th>
                    <th class="text-left p-2">Bonus</th>
                    <th class="text-center p-2">Starter</th>
                    <th class="text-center p-2">Joueurs</th>
                    <th class="text-center p-2">Statut</th>
                    <th class="text-right p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr class="border-t border-primary/10 hover:bg-primary/5 <?= $it['discontinued'] ? 'opacity-60' : '' ?>">
                        <td class="p-2 text-white"><?= esc($it['name']) ?> <span class="text-primary/40 text-xs">(<?= esc($it['slug']) ?>)</span></td>
                        <td class="p-2 text-primary/80"><?= esc($slots[$it['slot']] ?? $it['slot']) ?></td>
                        <td class="p-2"><?= view('partials/bonus_inline', ['item' => $it]) ?></td>
                        <td class="p-2 text-center">
                            <?= $it['starter'] ? '<span class="text-success">★</span>' : '<span class="text-primary/30">·</span>' ?>
                        </td>
                        <td class="p-2 text-center text-primary/80"><?= (int) ($owners[$it['id']] ?? 0) ?></td>
                        <td class="p-2 text-center">
                            <?php if ($it['discontinued']): ?>
                                <span class="text-warning">hors-circuit</span>
                            <?php else: ?>
                                <span class="text-success">actif</span>
                            <?php endif ?>
                        </td>
                        <td class="p-2 text-right">
                            <a href="/admin/items/<?= (int) $it['id'] ?>/edit" class="text-accent hover:text-pink-300 transition">[éditer]</a>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="p-4 text-center text-primary/40 italic">Aucun item au catalogue.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
