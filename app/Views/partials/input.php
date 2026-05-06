<?php
/**
 * Input texte/email/password style cyberpunk.
 *
 * @var string $name
 * @var string $label
 * @var string|null $type           text|email|password|... (def: text)
 * @var string|null $value          (def: vide → utilise old($name))
 * @var bool|null   $required       (def: false)
 * @var string|null $autocomplete
 * @var string|null $inputmode
 * @var string|null $placeholder
 */

$type        = $type        ?? 'text';
$value       = $value       ?? old($name);
$required    = ! empty($required);
$autocomplete = $autocomplete ?? null;
$inputmode   = $inputmode   ?? null;
$placeholder = $placeholder ?? null;
?>
<label class="block">
    <span class="block text-xs text-primary/70 uppercase tracking-wider mb-1"><?= esc($label) ?></span>
    <input
        type="<?= esc($type) ?>"
        name="<?= esc($name) ?>"
        value="<?= esc($value ?? '') ?>"
        <?= $required ? 'required' : '' ?>
        <?php if ($autocomplete !== null): ?>autocomplete="<?= esc($autocomplete) ?>"<?php endif ?>
        <?php if ($inputmode   !== null): ?>inputmode="<?= esc($inputmode) ?>"<?php endif ?>
        <?php if ($placeholder !== null): ?>placeholder="<?= esc($placeholder) ?>"<?php endif ?>
        class="w-full bg-surface-alt border border-line text-primary px-3 py-2 focus:border-accent focus:ring-1 focus:ring-accent focus:outline-none placeholder:text-muted/60 transition rounded"
    >
</label>
