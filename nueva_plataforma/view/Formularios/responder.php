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
  <style>
    body {
      background: #f4f7fb;
    }
    .form-shell {
      max-width: 860px;
      margin: 0 auto;
      padding: 28px 14px 42px;
    }
    .form-panel {
      background: #fff;
      border: 1px solid #d9e3ef;
      border-radius: 8px;
      box-shadow: 0 10px 24px rgba(17, 47, 83, 0.08);
      overflow: hidden;
    }
    .form-head {
      background: #0b4a8b;
      color: #fff;
      padding: 22px;
    }
    .context-block {
      padding: 18px 22px 0;
      background: #fff;
    }
    .form-body {
      padding: 22px;
    }
    .question-block {
      padding: 16px 0;
      border-bottom: 1px solid #e8eef5;
    }
    .question-block:last-child {
      border-bottom: 0;
    }
    .context-media,
    .question-media,
    .option-media {
      display: block;
      max-width: 100%;
      border-radius: 8px;
      border: 1px solid #d9e3ef;
      background: #fff;
    }
    .context-media,
    .question-media {
      margin: 12px 0;
      max-height: 420px;
      object-fit: contain;
    }
    .context-media {
      width: 100%;
      background: #f8fafc;
    }
    .youtube-frame {
      width: 100%;
      aspect-ratio: 16 / 9;
      border: 0;
      border-radius: 8px;
      margin: 12px 0;
      background: #111827;
    }
    .youtube-unavailable {
      border: 1px dashed #b7c7db;
      border-radius: 8px;
      padding: 16px;
      color: #475467;
      background: #f8fafc;
    }
    .option-card {
      display: flex;
      gap: 12px;
      align-items: center;
      width: 100%;
      min-height: 92px;
      margin: 10px 0;
      padding: 10px 12px;
      border: 1px solid #d8e2ee;
      border-radius: 8px;
      background: #fff;
      cursor: pointer;
      transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .option-card:hover {
      border-color: #98bce2;
      background: #f8fbff;
      box-shadow: 0 8px 18px rgba(17, 47, 83, 0.08);
    }
    .option-card:has(.option-input:checked) {
      border-color: #0d6efd;
      background: #eef6ff;
      box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
    }
    .option-input {
      width: 18px;
      height: 18px;
      flex: 0 0 auto;
      margin: 0;
    }
    .option-media {
      width: 110px;
      height: 76px;
      object-fit: cover;
      flex: 0 0 auto;
    }
    .option-text {
      color: #1f2937;
      font-weight: 500;
      line-height: 1.3;
      overflow-wrap: anywhere;
    }
    @media (max-width: 576px) {
      .option-card {
        align-items: flex-start;
      }
      .option-media {
        width: 92px;
        height: 68px;
      }
    }
    .meta-text {
      color: #667085;
      font-size: 13px;
    }
  </style>
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
  <script>
    $(function () {
      $('#formResponder').on('submit', function (e) {
        e.preventDefault();

        const gruposRequeridos = {};
        $('.seleccion-multiple[data-required="1"]').each(function () {
          gruposRequeridos[$(this).data('required-group')] = true;
        });

        for (const grupo in gruposRequeridos) {
          if ($('.seleccion-multiple[data-required-group="' + grupo + '"]:checked').length === 0) {
            Swal.fire({ icon: 'warning', title: 'Respuesta requerida', text: 'Debes completar las preguntas obligatorias.' });
            return;
          }
        }

        const formData = new FormData(this);
        formData.append('accion', 'responder_formulario');

        $.ajax({
          url: 'FormularioResponderController.php',
          method: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function (response) {
            Swal.fire({
              icon: 'success',
              title: 'Respuesta enviada',
              text: response.message || 'Tu respuesta fue guardada correctamente.',
            }).then(function () {
              window.location.href = 'PendientesController.php';
            });
          },
          error: function (xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire({
              icon: 'error',
              title: 'No se pudo enviar',
              text: response.message || 'Revisa tus respuestas e intenta nuevamente.',
            });
          }
        });
      });
    });
  </script>
</body>
</html>
