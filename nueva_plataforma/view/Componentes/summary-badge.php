<?php
$tone = $tone ?? 'pendiente';
$label = $label ?? '';
$value = $value ?? '';
?>
<span class="badge-resumen badge-<?= htmlspecialchars($tone) ?>">
  <?= htmlspecialchars($label) ?>: <?= htmlspecialchars((string) $value) ?>
</span>
