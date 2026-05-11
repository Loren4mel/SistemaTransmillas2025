<?php
date_default_timezone_set('America/Bogota');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Formularios</title>
  <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/usuarios.css">
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
      border-radius: 8px 8px 0 0;
    }
    .panel-body {
      padding: 20px;
    }
    .question-row {
      border: 1px solid #d9e3ef;
      border-radius: 8px;
      padding: 14px;
      background: #fbfdff;
    }
    .question-row + .question-row {
      margin-top: 12px;
    }
    .options-box {
      display: none;
    }
    .option-row {
      display: grid;
      grid-template-columns: minmax(180px, 1fr) minmax(180px, 280px) auto;
      gap: 10px;
      align-items: center;
      margin-bottom: 10px;
    }
    .context-box {
      display: none;
      border: 1px solid #d9e3ef;
      border-radius: 8px;
      padding: 12px;
      background: #fbfdff;
    }
    @media (max-width: 768px) {
      .option-row {
        grid-template-columns: 1fr;
      }
    }
    .copy-link {
      min-width: 240px;
      max-width: 420px;
    }
    .meta-text {
      color: #667085;
      font-size: 13px;
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
      <div>
        <h1 class="h3 mb-1">Formularios internos</h1>
        <div class="meta-text">Crea formularios que luego podras enlazar desde pendientes.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="PendientesController.php">
          <i class="bi bi-arrow-left"></i> Pendientes
        </a>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalFormulario">
          <i class="fas fa-plus me-1"></i> Nuevo formulario
        </button>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <div class="fw-semibold">Formularios creados</div>
          <div class="small opacity-75">Disponibles para todos los usuarios con acceso al modulo</div>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($formularios)): ?>
          <div class="text-center py-5">
            <i class="bi bi-ui-checks-grid display-5 text-secondary"></i>
            <h2 class="h5 mt-3">Aun no hay formularios</h2>
            <p class="meta-text mb-0">Crea el primero y copia su link para usarlo en un pendiente.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Formulario</th>
                  <th>Preguntas</th>
                  <th>Respuestas</th>
                  <th>Creador</th>
                  <th>Creado</th>
                  <th>Link</th>
                  <th class="text-center">Accion</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($formularios as $formulario): ?>
                  <?php
                    $linkFormulario = '../controller/FormularioResponderController.php?form=' . rawurlencode((string) $formulario['for_token']);
                    $linkAbsoluto = '/nueva_plataforma/controller/FormularioResponderController.php?form=' . rawurlencode((string) $formulario['for_token']);
                  ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($formulario['for_titulo']) ?></div>
                      <?php if (!empty($formulario['for_descripcion'])): ?>
                        <div class="meta-text"><?= htmlspecialchars($formulario['for_descripcion']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?= (int) $formulario['total_preguntas'] ?></td>
                    <td><?= (int) ($formulario['total_respuestas'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($formulario['creador_nombre'] ?? 'Usuario no disponible') ?></td>
                    <td><?= htmlspecialchars($formulario['for_fecha_creacion']) ?></td>
                    <td>
                      <div class="input-group input-group-sm copy-link">
                        <input type="text" class="form-control input-link-formulario" value="<?= htmlspecialchars($linkAbsoluto) ?>" readonly>
                        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($linkFormulario) ?>" target="_blank" rel="noopener noreferrer">
                          <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <button class="btn btn-outline-secondary btn-copiar-link" type="button">
                          <i class="bi bi-clipboard"></i>
                        </button>
                      </div>
                    </td>
                    <td class="text-center">
                      <a
                        class="btn btn-outline-info btn-sm"
                        href="FormulariosController.php?accion=respuestas&formulario_id=<?= (int) $formulario['id'] ?>"
                        title="Ver respuestas"
                      >
                        <i class="bi bi-chat-left-text"></i>
                      </a>
                      <button
                        class="btn btn-outline-danger btn-sm btn-eliminar-formulario"
                        type="button"
                        data-formulario-id="<?= (int) $formulario['id'] ?>"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalFormulario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">
            <i class="bi bi-ui-checks-grid me-2"></i>Crear formulario
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <form id="formCrearFormulario">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="titulo">Titulo</label>
                <input class="form-control" type="text" id="titulo" name="titulo" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="descripcion">Descripcion</label>
                <input class="form-control" type="text" id="descripcion" name="descripcion">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="contexto_tipo">Contexto inicial</label>
                <select class="form-select" id="contexto_tipo" name="contexto_tipo">
                  <option value="ninguno" selected>Sin contexto</option>
                  <option value="imagen">Imagen</option>
                  <option value="youtube">Video de YouTube</option>
                </select>
              </div>
              <div class="col-md-8 context-box" id="contextoImagenBox">
                <label class="form-label" for="contexto_imagen">Imagen de contexto</label>
                <input class="form-control" type="file" id="contexto_imagen" name="contexto_imagen" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
              </div>
              <div class="col-md-8 context-box" id="contextoYoutubeBox">
                <label class="form-label" for="contexto_youtube_url">Link de YouTube</label>
                <input class="form-control" type="url" id="contexto_youtube_url" name="contexto_youtube_url" placeholder="https://www.youtube.com/watch?v=...">
              </div>
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="form-label mb-0">Preguntas</label>
                  <button class="btn btn-outline-primary btn-sm" type="button" id="btnAgregarPregunta">
                    <i class="bi bi-plus-lg"></i> Agregar pregunta
                  </button>
                </div>
                <div id="contenedorPreguntas"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" form="formCrearFormulario" class="btn btn-primary">
            <i class="bi bi-save"></i> Guardar formulario
          </button>
        </div>
      </div>
    </div>
  </div>

  <template id="templatePregunta">
    <div class="question-row">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Pregunta</label>
          <input class="form-control" type="text" name="pregunta_etiqueta[]" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo</label>
          <select class="form-select select-tipo-pregunta" name="pregunta_tipo[]">
            <option value="texto_corto">Texto corto</option>
            <option value="texto_largo">Texto largo</option>
            <option value="seleccion_unica">Seleccion unica</option>
            <option value="seleccion_multiple">Seleccion multiple</option>
            <option value="fecha">Fecha</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Requerida</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="pregunta_requerida[]" checked>
            <label class="form-check-label">Si</label>
          </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-outline-danger w-100 btn-quitar-pregunta" type="button">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        <div class="col-md-6">
          <label class="form-label">Imagen de apoyo</label>
          <input class="form-control pregunta-imagen" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
        </div>
        <div class="col-12 options-box">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0">Opciones de respuesta</label>
            <button class="btn btn-outline-primary btn-sm btn-agregar-opcion" type="button">
              <i class="bi bi-plus-lg"></i> Agregar opcion
            </button>
          </div>
          <div class="options-list"></div>
        </div>
      </div>
    </div>
  </template>

  <template id="templateOpcion">
    <div class="option-row">
      <input class="form-control opcion-texto" type="text" placeholder="Texto de la opcion">
      <input class="form-control opcion-imagen" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
      <button class="btn btn-outline-danger btn-quitar-opcion" type="button">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
  </template>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    $(function () {
      function agregarPregunta() {
        const template = document.getElementById('templatePregunta');
        const nodo = template.content.cloneNode(true);
        $('#contenedorPreguntas').append(nodo);
      }

      function agregarOpcion($fila) {
        const template = document.getElementById('templateOpcion');
        const nodo = template.content.cloneNode(true);
        $fila.find('.options-list').append(nodo);
      }

      function actualizarOpciones($fila) {
        const tipo = $fila.find('.select-tipo-pregunta').val();
        const requiereOpciones = tipo === 'seleccion_unica' || tipo === 'seleccion_multiple';
        $fila.find('.options-box').toggle(requiereOpciones);
        if (requiereOpciones && $fila.find('.option-row').length === 0) {
          agregarOpcion($fila);
          agregarOpcion($fila);
        }
      }

      function actualizarContexto() {
        const tipo = $('#contexto_tipo').val();
        $('#contextoImagenBox').toggle(tipo === 'imagen');
        $('#contextoYoutubeBox').toggle(tipo === 'youtube');
        $('#contexto_imagen').prop('required', tipo === 'imagen');
        $('#contexto_youtube_url').prop('required', tipo === 'youtube');
      }

      agregarPregunta();
      actualizarContexto();

      $('#btnAgregarPregunta').on('click', agregarPregunta);
      $('#contexto_tipo').on('change', actualizarContexto);

      $('#contenedorPreguntas').on('change', '.select-tipo-pregunta', function () {
        actualizarOpciones($(this).closest('.question-row'));
      });

      $('#contenedorPreguntas').on('click', '.btn-agregar-opcion', function () {
        agregarOpcion($(this).closest('.question-row'));
      });

      $('#contenedorPreguntas').on('click', '.btn-quitar-opcion', function () {
        const $lista = $(this).closest('.options-list');
        if ($lista.find('.option-row').length <= 1) {
          Swal.fire({ icon: 'info', title: 'Opcion requerida', text: 'Deja al menos una opcion para esta pregunta.' });
          return;
        }
        $(this).closest('.option-row').remove();
      });

      $('#contenedorPreguntas').on('click', '.btn-quitar-pregunta', function () {
        if ($('.question-row').length <= 1) {
          Swal.fire({ icon: 'info', title: 'Pregunta requerida', text: 'El formulario necesita al menos una pregunta.' });
          return;
        }
        $(this).closest('.question-row').remove();
      });

      $('#formCrearFormulario').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('accion', 'crear_formulario');
        formData.append('titulo', $('#titulo').val());
        formData.append('descripcion', $('#descripcion').val());
        formData.append('contexto_tipo', $('#contexto_tipo').val());
        formData.append('contexto_youtube_url', $('#contexto_youtube_url').val());

        const contextoImagen = $('#contexto_imagen').get(0).files[0];
        if (contextoImagen) {
          formData.append('contexto_imagen', contextoImagen);
        }

        $('.question-row').each(function (preguntaIndex) {
          const $fila = $(this);
          formData.append('pregunta_etiqueta[]', $fila.find('input[name="pregunta_etiqueta[]"]').val());
          formData.append('pregunta_tipo[]', $fila.find('select[name="pregunta_tipo[]"]').val());
          formData.append('pregunta_requerida[]', $fila.find('input[name="pregunta_requerida[]"]').is(':checked') ? '1' : '0');

          const imagenPregunta = $fila.find('.pregunta-imagen').get(0).files[0];
          if (imagenPregunta) {
            formData.append('pregunta_imagen[' + preguntaIndex + ']', imagenPregunta);
          }

          $fila.find('.option-row').each(function (opcionIndex) {
            const $opcion = $(this);
            formData.append('pregunta_opciones_texto[' + preguntaIndex + '][]', $opcion.find('.opcion-texto').val());
            const imagenOpcion = $opcion.find('.opcion-imagen').get(0).files[0];
            if (imagenOpcion) {
              formData.append('pregunta_opciones_imagen[' + preguntaIndex + '][' + opcionIndex + ']', imagenOpcion);
            }
          });
        });

        $.ajax({
          url: 'FormulariosController.php',
          method: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function (response) {
            Swal.fire({
              icon: 'success',
              title: 'Formulario creado',
              text: response.message || 'El formulario fue creado correctamente.',
            }).then(function () {
              window.location.reload();
            });
          },
          error: function (xhr) {
            const response = xhr.responseJSON || {};
            Swal.fire({
              icon: 'error',
              title: 'No se pudo crear',
              text: response.message || 'Revisa los datos e intenta nuevamente.',
            });
          }
        });
      });

      $('.btn-copiar-link').on('click', function () {
        const input = $(this).closest('.input-group').find('.input-link-formulario').get(0);
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        Swal.fire({ icon: 'success', title: 'Link copiado', timer: 1200, showConfirmButton: false });
      });

      $('.btn-eliminar-formulario').on('click', function () {
        const formularioId = $(this).data('formulario-id');
        Swal.fire({
          icon: 'warning',
          title: 'Eliminar formulario',
          text: 'El formulario dejara de estar disponible para nuevos pendientes.',
          showCancelButton: true,
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar'
        }).then(function (result) {
          if (!result.isConfirmed) {
            return;
          }

          $.ajax({
            url: 'FormulariosController.php',
            method: 'POST',
            dataType: 'json',
            data: {
              accion: 'eliminar_formulario',
              formulario_id: formularioId
            },
            success: function (response) {
              Swal.fire({
                icon: 'success',
                title: 'Formulario eliminado',
                text: response.message || 'Formulario eliminado correctamente.'
              }).then(function () {
                window.location.reload();
              });
            },
            error: function (xhr) {
              const response = xhr.responseJSON || {};
              Swal.fire({
                icon: 'error',
                title: 'No se pudo eliminar',
                text: response.message || 'Intenta nuevamente.'
              });
            }
          });
        });
      });
    });
  </script>
</body>
</html>
