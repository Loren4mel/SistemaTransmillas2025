<?php
if (function_exists('component_log')) {
  component_log('Inicio section-card', [
    'title' => $title ?? null,
    'icon' => $icon ?? null,
  ]);
}

$title = $title ?? '';
$icon = $icon ?? '';
$headerAside = $headerAside ?? '';
$body = $body ?? '';
$cardClass = trim($cardClass ?? 'card shadow p-3 mb-4 bg-body rounded');
$headerClass = trim($headerClass ?? 'card-header mi-header d-flex align-items-center justify-content-between');
$bodyClass = trim($bodyClass ?? 'card-body');
?>
<div class="<?= htmlspecialchars($cardClass) ?>">
  <div class="<?= htmlspecialchars($headerClass) ?>">
    <h3 class="mb-0">
      <?php if ($icon !== ''): ?>
        <i class="<?= htmlspecialchars($icon) ?> me-2"></i>
      <?php endif; ?>
      <?= htmlspecialchars($title) ?>
    </h3>
    <?= $headerAside ?>
  </div>

  <div class="<?= htmlspecialchars($bodyClass) ?>">
    <?= $body ?>
  </div>
</div>
<?php
if (function_exists('component_log')) {
  component_log('Fin section-card', ['title' => $title]);
}
?>
