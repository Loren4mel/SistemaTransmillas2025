<?php
$title = $title ?? '';
$message = $message ?? '';
$className = trim($className ?? 'empty-state p-4 text-center');
?>
<div class="<?= htmlspecialchars($className) ?>">
  <h5 class="mb-2"><?= htmlspecialchars($title) ?></h5>
  <p class="text-muted mb-0"><?= htmlspecialchars($message) ?></p>
</div>
