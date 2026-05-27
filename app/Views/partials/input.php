<?php
/**
 * Input texte/email/password (Bootstrap).
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
$id          = 'inp_' . preg_replace('/[^a-z0-9]/i', '_', $name);
?>
<div class="mb-3">
    <label for="<?= esc($id) ?>" class="form-label small"><?= esc($label) ?></label>
    <input
        id="<?= esc($id) ?>"
        type="<?= esc($type) ?>"
        name="<?= esc($name) ?>"
        value="<?= esc($value ?? '') ?>"
        <?= $required ? 'required' : '' ?>
        <?php if ($autocomplete !== null): ?>autocomplete="<?= esc($autocomplete) ?>"<?php endif ?>
        <?php if ($inputmode   !== null): ?>inputmode="<?= esc($inputmode) ?>"<?php endif ?>
        <?php if ($placeholder !== null): ?>placeholder="<?= esc($placeholder) ?>"<?php endif ?>
        class="form-control"
    >
</div>
