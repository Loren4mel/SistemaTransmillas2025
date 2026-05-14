<?php
date_default_timezone_set('America/Bogota');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($formulario['for_titulo']) ?></title>
  <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/formularios-responder.css">
</head>
<body>
  <div class="form-shell">
    <div class="form-panel">
      <div class="form-head">
        <h1 class="h3 mb-1"><?= htmlspecialchars($formulario['for_titulo']) ?></h1>
        <?php if (!empty($formulario['for_descripcion'])): ?>
          <div class="opacity-75"><?= htmlspecialchars($formulario['for_descripcion']) ?></div>
        <?php endif; ?>
      </div>
      <?php if (!empty($formulario['for_contexto_url'])): ?>
        <?php $contextoTipo = (string) ($formulario['for_contexto_tipo'] ?? 'imagen'); ?>
        <div class="context-block">
        <?php if ($contextoTipo === 'youtube'): ?>
          <?php $youtubeEmbedUrl = $modelo->obtenerYoutubeEmbedUrl((string) $formulario['for_contexto_url']); ?>
          <?php if ($youtubeEmbedUrl !== ''): ?>
            <iframe
              class="youtube-frame"
              src="<?= htmlspecialchars($youtubeEmbedUrl) ?>"
              title="Video de contexto"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
            ></iframe>
          <?php else: ?>
            <div class="youtube-unavailable">
              No fue posible visualizar este video. Revisa que el enlace sea de YouTube.
            </div>
          <?php endif; ?>
        <?php else: ?>
          <img class="context-media" src="<?= htmlspecialchars($formulario['for_contexto_url']) ?>" alt="Contexto del formulario">
        <?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="form-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
          <div class="meta-text">
            Usuario: <?= htmlspecialchars($nombreUsuario ?? '') ?>
          </div>
          <button class="btn btn-outline-secondary btn-sm" type="button" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Volver
          </button>
        </div>

        <form id="formResponder">
          <input type="hidden" name="form_token" value="<?= htmlspecialchars($formulario['for_token']) ?>">
          <input type="hidden" name="pendiente_usuario_id" value="<?= (int) ($pendienteUsuarioId ?? 0) ?>">

          <?php foreach ($formulario['preguntas'] as $pregunta): ?>
            <?php
              $preguntaId = (int) $pregunta['id'];
              $tipo = (string) $pregunta['pre_tipo'];
              $requerida = (int) $pregunta['pre_requerida'] === 1;
              $nombreCampo = 'respuesta[' . $preguntaId . ']';
            ?>
            <div class="question-block">
              <label class="form-label fw-semibold">
                <?= htmlspecialchars($pregunta['pre_etiqueta']) ?>
                <?php if ($requerida): ?>
                  <span class="text-danger">*</span>
                <?php endif; ?>
              </label>
              <?php if (!empty($pregunta['pre_imagen'])): ?>
                <img class="question-media" src="<?= htmlspecialchars($pregunta['pre_imagen']) ?>" alt="Imagen de apoyo">
              <?php endif; ?>

              <?php if ($tipo === 'texto_largo'): ?>
                <textarea class="form-control" name="<?= htmlspecialchars($nombreCampo) ?>" rows="4" <?= $requerida ? 'required' : '' ?>></textarea>
              <?php elseif ($tipo === 'fecha'): ?>
                <input class="form-control" type="date" name="<?= htmlspecialchars($nombreCampo) ?>" <?= $requerida ? 'required' : '' ?>>
              <?php elseif ($tipo === 'seleccion_unica'): ?>
                <?php foreach ($pregunta['opciones'] as $indice => $opcion): ?>
                  <?php $idOpcion = 'preg_' . $preguntaId . '_' . $indice; ?>
                  <label class="option-card" for="<?= htmlspecialchars($idOpcion) ?>">
                    <input class="option-input" type="radio" id="<?= htmlspecialchars($idOpcion) ?>" name="<?= htmlspecialchars($nombreCampo) ?>" value="<?= htmlspecialchars($opcion['texto']) ?>" <?= $requerida ? 'required' : '' ?>>
                    <?php if (!empty($opcion['imagen'])): ?>
                      <img class="option-media" src="<?= htmlspecialchars($opcion['imagen']) ?>" alt="<?= htmlspecialchars($opcion['texto']) ?>">
                    <?php endif; ?>
                    <span class="option-text"><?= htmlspecialchars($opcion['texto']) ?></span>
                  </label>
                <?php endforeach; ?>
              <?php elseif ($tipo === 'seleccion_multiple'): ?>
                <?php foreach ($pregunta['opciones'] as $indice => $opcion): ?>
                  <?php $idOpcion = 'preg_' . $preguntaId . '_' . $indice; ?>
                  <label class="option-card" for="<?= htmlspecialchars($idOpcion) ?>">
                    <input class="option-input seleccion-multiple" type="checkbox" id="<?= htmlspecialchars($idOpcion) ?>" name="<?= htmlspecialchars($nombreCampo) ?>[]" value="<?= htmlspecialchars($opcion['texto']) ?>" data-required-group="pregunta_<?= $preguntaId ?>" <?= $requerida ? 'data-required="1"' : '' ?>>
                    <?php if (!empty($opcion['imagen'])): ?>
                      <img class="option-media" src="<?= htmlspecialchars($opcion['imagen']) ?>" alt="<?= htmlspecialchars($opcion['texto']) ?>">
                    <?php endif; ?>
                    <span class="option-text"><?= htmlspecialchars($opcion['texto']) ?></span>
                  </label>
                <?php endforeach; ?>
              <?php else: ?>
                <input class="form-control" type="text" name="<?= htmlspecialchars($nombreCampo) ?>" <?= $requerida ? 'required' : '' ?>>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-send"></i> Enviar respuesta
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/formularios-responder.js"></script>
</body>
</html>
