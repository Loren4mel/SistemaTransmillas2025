<?php
date_default_timezone_set('America/Bogota');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Respuestas - <?= htmlspecialchars($formulario['for_titulo'] ?? 'Formulario') ?></title>
  <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background: #f4f7fb;
    }
    .page-shell {
      max-width: 1180px;
      margin: 0 auto;
      padding: 24px 14px 40px;
    }
    .panel {
      background: #fff;
      border: 1px solid #d9e3ef;
      border-radius: 8px;
      box-shadow: 0 10px 24px rgba(17, 47, 83, 0.08);
      overflow: hidden;
    }
    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 18px 20px;
      border-bottom: 1px solid #e5edf5;
      background: #0b4a8b;
      color: #fff;
    }
    .panel-body {
      padding: 20px;
    }
    .meta-text {
      color: #667085;
      font-size: 13px;
    }
    .answer-list {
      display: grid;
      gap: 12px;
    }
    .answer-item {
      border: 1px solid #d9e3ef;
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
    }
    .answer-button {
      width: 100%;
      border: 0;
      background: #fbfdff;
      padding: 14px 16px;
      text-align: left;
    }
    .answer-button:hover {
      background: #f2f7fc;
    }
    .answer-detail {
      padding: 16px;
      border-top: 1px solid #e8eef5;
    }
    .qa-row {
      display: grid;
      grid-template-columns: minmax(180px, 35%) 1fr;
      gap: 14px;
      padding: 12px 0;
      border-bottom: 1px solid #eef2f6;
    }
    .qa-row:last-child {
      border-bottom: 0;
    }
    .question-text {
      font-weight: 600;
      color: #1f2937;
    }
    .answer-text {
      color: #344054;
      overflow-wrap: anywhere;
      white-space: pre-wrap;
    }
    @media (max-width: 768px) {
      .panel-header,
      .qa-row {
        display: block;
      }
      .qa-row {
        padding: 14px 0;
      }
      .answer-text {
        margin-top: 6px;
      }
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
      <div>
        <h1 class="h3 mb-1">Respuestas del formulario</h1>
        <div class="meta-text"><?= htmlspecialchars($formulario['for_titulo'] ?? '') ?></div>
      </div>
      <a class="btn btn-outline-secondary" href="FormulariosController.php">
        <i class="bi bi-arrow-left"></i> Formularios
      </a>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <div class="fw-semibold"><?= htmlspecialchars($formulario['for_titulo'] ?? '') ?></div>
          <div class="small opacity-75">
            Creado por <?= htmlspecialchars($formulario['creador_nombre'] ?? 'Usuario no disponible') ?>
          </div>
        </div>
        <div class="text-md-end">
          <div class="fw-semibold"><?= count($respuestas ?? []) ?></div>
          <div class="small opacity-75">respuestas registradas</div>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($respuestas)): ?>
          <div class="text-center py-5">
            <i class="bi bi-chat-left-text display-5 text-secondary"></i>
            <h2 class="h5 mt-3">Aun no hay respuestas</h2>
            <p class="meta-text mb-0">Cuando alguien responda este formulario, aparecera aqui.</p>
          </div>
        <?php else: ?>
          <div class="answer-list">
            <?php foreach ($respuestas as $indice => $respuesta): ?>
              <?php
                $collapseId = 'respuesta_' . (int) $respuesta['id'];
                $identificacion = trim((string) ($respuesta['usuario_identificacion'] ?? ''));
              ?>
              <div class="answer-item">
                <button
                  class="answer-button"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#<?= htmlspecialchars($collapseId) ?>"
                  aria-expanded="<?= $indice === 0 ? 'true' : 'false' ?>"
                  aria-controls="<?= htmlspecialchars($collapseId) ?>"
                >
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                      <div class="fw-semibold">
                        <?= htmlspecialchars($respuesta['usuario_nombre'] ?? 'Usuario no disponible') ?>
                      </div>
                      <div class="meta-text">
                        <?= $identificacion !== '' ? 'Documento: ' . htmlspecialchars($identificacion) . ' | ' : '' ?>
                        Usuario ID: <?= (int) ($respuesta['usuario_id'] ?? 0) ?>
                        <?php if (!empty($respuesta['pendiente_usuario_id'])): ?>
                          | Pendiente usuario ID: <?= (int) $respuesta['pendiente_usuario_id'] ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="text-md-end">
                      <div class="fw-semibold"><?= htmlspecialchars($respuesta['res_fecha'] ?? '') ?></div>
                      <div class="meta-text">Respuesta #<?= (int) $respuesta['id'] ?></div>
                    </div>
                  </div>
                </button>
                <div id="<?= htmlspecialchars($collapseId) ?>" class="collapse <?= $indice === 0 ? 'show' : '' ?>">
                  <div class="answer-detail">
                    <?php if (empty($respuesta['detalles'])): ?>
                      <div class="meta-text">Esta respuesta no tiene detalle guardado.</div>
                    <?php else: ?>
                      <?php foreach ($respuesta['detalles'] as $detalle): ?>
                        <div class="qa-row">
                          <div class="question-text"><?= htmlspecialchars($detalle['pregunta'] ?? '') ?></div>
                          <div class="answer-text"><?= htmlspecialchars($detalle['valor'] ?? 'Sin respuesta') ?></div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
