<?php
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../../helpers/view.php';
$conceptosPendiente = ['Documento', 'Pago', 'Firma', 'Aprobacion'];

if (!function_exists('pendientesViewLog')) {
  function pendientesViewLog(string $mensaje, array $contexto = []): void
  {
    $directorioLogs = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($directorioLogs)) {
      @mkdir($directorioLogs, 0777, true);
    }

    $rutaLog = $directorioLogs . DIRECTORY_SEPARATOR . 'pendientes_view.log';
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;

    if (!empty($contexto)) {
      $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $linea .= PHP_EOL;
    @file_put_contents($rutaLog, $linea, FILE_APPEND);
  }
}

pendientesViewLog('Inicio vista Pendientes', [
  'rolUsuario' => $rolUsuario ?? null,
  'pendientes' => isset($pendientes) && is_array($pendientes) ? count($pendientes) : null,
  'pendientesCreados' => isset($pendientesCreados) && is_array($pendientesCreados) ? count($pendientesCreados) : null,
]);

$puedeAdministrarPendientes = ($rolUsuario ?? 0) === 1;
$puedeAdministrarFormularios = in_array((int) ($rolUsuario ?? 0), [1, 12], true);
$puedeVerSeguimientoPendientes = in_array((int) ($rolUsuario ?? 0), [1, 12], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pendientes</title>
  <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/usuarios.css">
  <style>
    .document-link {
      color: var(--azul-principal);
      text-decoration: none;
      font-weight: 600;
    }
    .document-link:hover {
      color: #0a3b6e;
    }
    .document-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }
    .document-actions.vertical {
      flex-direction: column;
      align-items: stretch;
    }
    .document-chip {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid #c8d9ea;
      background: linear-gradient(180deg, #ffffff 0%, #f5faff 100%);
      color: #134074;
      text-decoration: none;
      font-size: 13px;
      font-weight: 700;
      line-height: 1;
      box-shadow: 0 8px 20px rgba(19, 64, 116, 0.08);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .document-chip:hover {
      color: #0d325c;
      border-color: #9dc0e2;
      box-shadow: 0 12px 24px rgba(19, 64, 116, 0.14);
      transform: translateY(-1px);
    }
    .document-chip i {
      font-size: 14px;
    }
    .document-chip.file {
      background: linear-gradient(180deg, #ffffff 0%, #eef7ff 100%);
    }
    .document-chip.link {
      background: linear-gradient(180deg, #ffffff 0%, #f3fbf6 100%);
      border-color: #c7e3d0;
      color: #15603a;
    }
    .document-chip.link:hover {
      color: #10482c;
      border-color: #9fd0b0;
    }
    .document-chip.sign {
      justify-content: center;
      background: linear-gradient(180deg, #ffffff 0%, #eef4ff 100%);
      border-color: #b8cdf5;
      color: #214d9b;
    }
    .observacion-pendiente,
    .form-control,
    .form-select {
      border-radius: 10px;
      border: 1px solid var(--gris-borde);
    }
    .observacion-pendiente {
      min-width: 220px;
      resize: vertical;
    }
    .empty-state {
      border: 1px dashed #b7c7db;
      border-radius: 16px;
      background-color: #fff;
    }
    .meta-pendiente {
      font-size: 12px;
      color: #6b7280;
    }
    .thead-modern th {
      white-space: nowrap;
    }
    .roles-box {
      max-height: 220px;
      overflow: auto;
      border: 1px solid var(--gris-borde);
      border-radius: 12px;
      padding: 12px;
      background: #fff;
    }
    .usuarios-box {
      max-height: 260px;
      overflow: auto;
      border: 1px solid var(--gris-borde);
      border-radius: 12px;
      padding: 12px;
      background: #fff;
    }
    .filtros-usuarios {
      display: grid;
      grid-template-columns: 1.4fr 1fr;
      gap: 10px;
      margin-bottom: 10px;
    }
    .usuario-item.oculto-por-filtro {
      display: none;
    }
    @media (max-width: 768px) {
      .filtros-usuarios {
        grid-template-columns: 1fr;
      }
    }
    .estado-bloqueado {
      background-color: #eef2f7 !important;
      color: #667085 !important;
      border: 1px solid #d0d5dd;
    }
    .hint-card {
      background: #f8fbff;
      border: 1px solid #d7e6f5;
      border-radius: 12px;
      padding: 12px 14px;
      color: #35516e;
      font-size: 13px;
    }
    .documento-opcion {
      display: none;
    }
    .concepto-personalizado {
      display: none;
    }
    .badge-resumen {
      font-size: 12px;
      font-weight: 600;
      border-radius: 999px;
      padding: 6px 10px;
    }
    .badge-aceptado {
      background: #e7f8ee;
      color: #137a3b;
    }
    .badge-rechazado {
      background: #fdecec;
      color: #b42318;
    }
    .badge-pendiente {
      background: #eef2ff;
      color: #344054;
    }
    .tabla-seguimiento td,
    .tabla-seguimiento th {
      vertical-align: middle;
    }
    .acciones-gerente {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .bloque-asignacion {
      display: none;
    }
    .badge-nueva-funcion {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-left: 8px;
      padding: 4px 10px;
      border-radius: 999px;
      background: #fff4d6;
      color: #9a6700;
      font-size: 11px;
      font-weight: 700;
      border: 1px solid #f3d27a;
      box-shadow: 0 6px 18px rgba(154, 103, 0, 0.12);
      transition: opacity .35s ease, transform .35s ease;
    }
    .badge-nueva-funcion.oculto {
      opacity: 0;
      transform: translateY(-6px);
      pointer-events: none;
    }
  </style>
</head>
<body>
  <div class="container-fluid mt-4">
    <?php if ($puedeAdministrarPendientes || $puedeAdministrarFormularios): ?>
      <?php pendientesViewLog('Render seccion gestion gerente'); ?>
      <?php ob_start(); ?>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="hint-card mb-0">
          El gerente puede crear pendientes con archivo, link o ambos y asignarlos a uno o varios roles.
          Los usuarios no podran confirmarlos hasta abrir el documento.
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <?php if ($puedeAdministrarFormularios): ?>
            <a class="btn btn-outline-primary" href="FormulariosController.php">
              <i class="bi bi-ui-checks-grid"></i> Formularios
            </a>
          <?php endif; ?>
          <?php if ($puedeAdministrarPendientes): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearPendiente">
              <i class="fas fa-plus me-1"></i> Nuevo pendiente
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php
      $bodyGestionPendientes = ob_get_clean();

      echo component('section-card', [
        'title' => 'Gestion de Pendientes',
        'icon' => 'fas fa-plus-circle',
        'headerAside' => '<span class="text-white small">Disponible para roles autorizados</span>',
        'body' => $bodyGestionPendientes,
      ]);
      ?>
    <?php endif; ?>

    <?php if ($puedeVerSeguimientoPendientes): ?>
      <?php pendientesViewLog('Render seguimiento gerente'); ?>
      <?php ob_start(); ?>
      <?php if (empty($pendientesCreados)): ?>
        <?= component('empty-state', [
          'title' => 'Aun no has creado pendientes',
          'message' => 'Cuando crees uno, aqui podras ver quien lo acepto, rechazo o sigue pendiente.',
        ]) ?>
      <?php else: ?>
        <div class="accordion" id="accordionPendientesCreados">
          <?php foreach ($pendientesCreados as $indice => $pendienteCreado): ?>
            <?php pendientesViewLog('Render pendiente creado', ['indice' => $indice, 'pendienteId' => $pendienteCreado['id'] ?? null]); ?>
            <?php $collapseId = 'pendienteCreado_' . (int) $pendienteCreado['id']; ?>
            <div class="accordion-item mb-3 border rounded-3 overflow-hidden">
                  <h2 class="accordion-header" id="heading_<?= $collapseId ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" aria-controls="<?= $collapseId ?>">
                      <div class="w-100 pe-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                          <div>
                            <div class="fw-semibold"><?= htmlspecialchars($pendienteCreado['titulo']) ?></div>
                            <div class="meta-pendiente">
                              Creado el <?= htmlspecialchars($pendienteCreado['fecha_creacion']) ?>
                              | Por <?= htmlspecialchars($pendienteCreado['creador_nombre'] ?? 'Sin nombre') ?>
                              <?php if (!empty($pendienteCreado['descripcion'])): ?>
                                | <?= htmlspecialchars($pendienteCreado['descripcion']) ?>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="d-flex flex-wrap gap-2">
                            <?= component('summary-badge', ['tone' => 'pendiente', 'label' => 'Asignados', 'value' => (int) $pendienteCreado['total_asignados']]) ?>
                            <?= component('summary-badge', ['tone' => 'aceptado', 'label' => 'Aceptados', 'value' => (int) $pendienteCreado['total_aceptados']]) ?>
                            <?= component('summary-badge', ['tone' => 'rechazado', 'label' => 'Rechazados', 'value' => (int) $pendienteCreado['total_rechazados']]) ?>
                            <?= component('summary-badge', ['tone' => 'pendiente', 'label' => 'Pendientes', 'value' => (int) $pendienteCreado['total_pendientes']]) ?>
                            <?php if (!empty($pendienteCreado['es_formulario'])): ?>
                              <?= component('summary-badge', ['tone' => 'aceptado', 'label' => 'Formularios', 'value' => (int) $pendienteCreado['total_formularios_respondidos']]) ?>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </button>
                  </h2>
                  <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?= $collapseId ?>" data-bs-parent="#accordionPendientesCreados">
                    <div class="accordion-body">
                      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="document-actions">
                          <?php if (!empty($pendienteCreado['documento_archivo'])): ?>
                            <a class="document-chip file" href="<?= htmlspecialchars($pendienteCreado['documento_archivo']) ?>" target="_blank" rel="noopener noreferrer">
                              <i class="bi bi-file-earmark-arrow-down"></i> Abrir archivo
                            </a>
                          <?php endif; ?>
                          <?php if (!empty($pendienteCreado['documento_link'])): ?>
                            <a class="document-chip link" href="<?= htmlspecialchars($pendienteCreado['documento_link']) ?>" target="_blank" rel="noopener noreferrer">
                              <i class="bi bi-link-45deg"></i> Abrir link
                            </a>
                          <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                          <span class="meta-pendiente">
                            Roles: <?= htmlspecialchars(implode(', ', $pendienteCreado['roles_nombres'])) ?>
                          </span>
                          <span class="meta-pendiente">
                            Contrato: <?= htmlspecialchars(!empty($pendienteCreado['tipos_contrato']) ? implode(', ', $pendienteCreado['tipos_contrato']) : 'Todos') ?>
                          </span>
                          <span class="meta-pendiente">
                            Firma: <?= htmlspecialchars(($pendienteCreado['modo_firma'] ?? 'individual') === 'multiple' ? 'Multiple' : 'Individual') ?>
                          </span>
                          <span class="meta-pendiente">
                            Estado general: <?= (int) $pendienteCreado['estado'] === 1 ? 'Activo' : 'Inactivo' ?>
                          </span>
                          <?php if (!empty($pendienteCreado['es_formulario'])): ?>
                            <a
                              class="document-link"
                              href="FormulariosController.php?accion=respuestas&formulario_id=<?= (int) ($pendienteCreado['formulario_id'] ?? 0) ?>"
                              target="_blank"
                              rel="noopener noreferrer"
                            >
                              <i class="bi bi-ui-checks-grid"></i> Ver respuestas
                            </a>
                          <?php endif; ?>
                        </div>
                      </div>

                      <?php if ($puedeAdministrarPendientes): ?>
                        <div class="acciones-gerente mb-3">
                          <button
                            type="button"
                            class="btn btn-outline-primary btn-sm btn-editar-pendiente"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditarPendiente"
                            data-pendiente-id="<?= (int) $pendienteCreado['id'] ?>"
                            data-concepto="<?= htmlspecialchars(in_array($pendienteCreado['titulo'], $conceptosPendiente, true) ? $pendienteCreado['titulo'] : 'Otro') ?>"
                            data-titulo="<?= htmlspecialchars(in_array($pendienteCreado['titulo'], $conceptosPendiente, true) ? '' : $pendienteCreado['titulo']) ?>"
                            data-descripcion="<?= htmlspecialchars($pendienteCreado['descripcion']) ?>"
                            data-documento-archivo="<?= htmlspecialchars($pendienteCreado['documento_archivo'] ?? '') ?>"
                            data-documento-link="<?= htmlspecialchars($pendienteCreado['documento_link'] ?? '') ?>"
                            data-roles='<?= htmlspecialchars(json_encode($pendienteCreado['roles_ids']), ENT_QUOTES, 'UTF-8') ?>'
                            data-tipos-contrato='<?= htmlspecialchars(json_encode($pendienteCreado['tipos_contrato']), ENT_QUOTES, 'UTF-8') ?>'
                            data-modo-asignacion="<?= htmlspecialchars($pendienteCreado['modo_asignacion']) ?>"
                            data-modo-firma="<?= htmlspecialchars($pendienteCreado['modo_firma'] ?? 'individual') ?>"
                            data-usuario-objetivo-id="<?= (int) $pendienteCreado['usuario_objetivo_id'] ?>"
                            data-usuarios-objetivo-ids='<?= htmlspecialchars(json_encode($pendienteCreado['usuarios_objetivo_ids'] ?? []), ENT_QUOTES, 'UTF-8') ?>'
                          >
                            <i class="bi bi-pencil-square"></i> Editar
                          </button>
                          <button
                            type="button"
                            class="btn btn-outline-danger btn-sm btn-eliminar-pendiente"
                            data-pendiente-id="<?= (int) $pendienteCreado['id'] ?>"
                          >
                            <i class="bi bi-trash"></i> Eliminar
                          </button>
                        </div>
                      <?php endif; ?>

                      <div class="table-responsive">
                        <table class="table table-bordered table-hover tabla-seguimiento">
                          <thead class="thead-modern">
                            <tr>
                              <th>Usuario</th>
                              <th>Rol</th>
                              <th>Documento abierto</th>
                              <?php if (!empty($pendienteCreado['es_formulario'])): ?>
                                <th>Formulario</th>
                              <?php endif; ?>
                              <th>PDF firmado</th>
                              <th>Estado</th>
                              <th>Fecha confirmacion</th>
                              <th>Observacion</th>
                              <?php if ($puedeAdministrarPendientes): ?>
                                <th>Accion</th>
                              <?php endif; ?>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($pendienteCreado['usuarios'] as $usuarioPendiente): ?>
                              <?php pendientesViewLog('Render usuario seguimiento', ['pendienteId' => $pendienteCreado['id'] ?? null, 'usuarioId' => $usuarioPendiente['usuario_id'] ?? null]); ?>
                              <tr>
                                <td><?= htmlspecialchars($usuarioPendiente['usuario_nombre']) ?></td>
                                <td><?= htmlspecialchars($usuarioPendiente['rol_nombre']) ?></td>
                                <td>
                                  <?php if ((int) $usuarioPendiente['documento_abierto'] === 1): ?>
                                    <?= component('summary-badge', ['tone' => 'aceptado', 'label' => 'Estado', 'value' => 'Si']) ?>
                                    <?php if ($usuarioPendiente['fecha_documento_abierto'] !== ''): ?>
                                      <div class="meta-pendiente mt-1"><?= htmlspecialchars($usuarioPendiente['fecha_documento_abierto']) ?></div>
                                    <?php endif; ?>
                                  <?php else: ?>
                                    <?= component('summary-badge', ['tone' => 'pendiente', 'label' => 'Estado', 'value' => 'No']) ?>
                                  <?php endif; ?>
                                </td>
                                <?php if (!empty($pendienteCreado['es_formulario'])): ?>
                                  <td>
                                    <?php if ((int) ($usuarioPendiente['formulario_respondido'] ?? 0) === 1): ?>
                                      <?= component('summary-badge', ['tone' => 'aceptado', 'label' => 'Estado', 'value' => 'Respondido']) ?>
                                      <?php if (!empty($usuarioPendiente['formulario_fecha_respuesta'])): ?>
                                        <div class="meta-pendiente mt-1"><?= htmlspecialchars($usuarioPendiente['formulario_fecha_respuesta']) ?></div>
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <?= component('summary-badge', ['tone' => 'pendiente', 'label' => 'Estado', 'value' => 'Sin responder']) ?>
                                    <?php endif; ?>
                                  </td>
                                <?php endif; ?>
                                <td>
                                  <?php if ($usuarioPendiente['pdf_firmado_ruta'] !== ''): ?>
                                    <a
                                      class="document-link"
                                      href="<?= htmlspecialchars($usuarioPendiente['pdf_firmado_ruta']) ?>"
                                      target="_blank"
                                      rel="noopener noreferrer"
                                    >
                                      <i class="bi bi-file-earmark-check"></i> Ver PDF firmado
                                    </a>
                                  <?php elseif ($usuarioPendiente['firma_ruta'] !== ''): ?>
                                    <span class="meta-pendiente">Firma capturada, PDF pendiente</span>
                                  <?php else: ?>
                                    <span class="text-muted">Sin firma</span>
                                  <?php endif; ?>
                                </td>
                                <td>
                                  <?php if ($usuarioPendiente['estado'] === 'Aceptado'): ?>
                                    <?= component('summary-badge', ['tone' => 'aceptado', 'label' => 'Resultado', 'value' => 'Aceptado']) ?>
                                  <?php elseif ($usuarioPendiente['estado'] === 'Rechazado'): ?>
                                    <?= component('summary-badge', ['tone' => 'rechazado', 'label' => 'Resultado', 'value' => 'Rechazado']) ?>
                                  <?php else: ?>
                                    <?= component('summary-badge', ['tone' => 'pendiente', 'label' => 'Resultado', 'value' => 'Pendiente']) ?>
                                  <?php endif; ?>
                                </td>
                                <td>
                                  <?= $usuarioPendiente['fecha_confirmacion'] !== '' ? htmlspecialchars($usuarioPendiente['fecha_confirmacion']) : '<span class="text-muted">Sin confirmar</span>' ?>
                                </td>
                                <td>
                                  <?= $usuarioPendiente['observacion'] !== '' ? htmlspecialchars($usuarioPendiente['observacion']) : '<span class="text-muted">Sin observacion</span>' ?>
                                </td>
                                <?php if ($puedeAdministrarPendientes): ?>
                                  <td class="text-center">
                                    <button
                                      type="button"
                                      class="btn btn-outline-danger btn-sm btn-eliminar-usuario-pendiente"
                                      data-pendiente-id="<?= (int) $pendienteCreado['id'] ?>"
                                      data-usuario-id="<?= (int) $usuarioPendiente['usuario_id'] ?>"
                                      data-usuario-nombre="<?= htmlspecialchars($usuarioPendiente['usuario_nombre']) ?>"
                                    >
                                      <i class="bi bi-person-x"></i> Quitar
                                    </button>
                                  </td>
                                <?php endif; ?>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php
      $bodySeguimientoPendientes = ob_get_clean();

      echo component('section-card', [
        'title' => 'Seguimiento de Pendientes Creados',
        'icon' => 'fas fa-clipboard-check',
        'headerAside' => '<span class="text-white small">Vista de gerente</span>',
        'body' => $bodySeguimientoPendientes,
      ]);
      ?>
    <?php endif; ?>

    <?php ob_start(); ?>
    <?php pendientesViewLog('Render mis pendientes'); ?>
    <?php if (empty($pendientes)): ?>
      <?= component('empty-state', [
        'title' => 'No tienes pendientes por validar',
        'message' => 'Aqui apareceran los pendientes de pagos y los pendientes personalizados que te asignen.',
        'className' => 'empty-state p-5 text-center',
      ]) ?>
    <?php else: ?>
      <div class="table-responsive">
        <table id="tablaPendientes" class="table table-hover table-bordered align-middle text-center">
              <thead class="thead-modern">
                <tr>
                  <th>Del</th>
                  <th>Al</th>
                  <th>Concepto</th>
                  <th>Documento</th>
                  <th>Observaciones</th>
                  <th>Validar</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendientes as $pendiente): ?>
                  <?php pendientesViewLog('Render fila pendiente', ['registro' => $pendiente['registro'] ?? null, 'pendienteUsuarioId' => $pendiente['pendiente_usuario_id'] ?? null]); ?>
                  <tr
                    data-registro="<?= htmlspecialchars($pendiente['registro']) ?>"
                    data-fecha-inicio="<?= htmlspecialchars($pendiente['fecha_inicio']) ?>"
                    data-tipo="<?= htmlspecialchars($pendiente['tipo']) ?>"
                    data-pendiente-usuario-id="<?= (int) ($pendiente['pendiente_usuario_id'] ?? 0) ?>"
                    data-puede-confirmar="<?= (int) ($pendiente['puede_confirmar'] ?? 1) ?>"
                    data-requiere-firma="<?= (int) ($pendiente['requiere_firma'] ?? 0) ?>"
                    data-firmado="<?= (int) ($pendiente['firmado'] ?? 0) ?>"
                  >
                    <td><?= htmlspecialchars($pendiente['fecha_inicio']) ?></td>
                    <td><?= htmlspecialchars($pendiente['fecha_fin']) ?></td>
                    <td>
                      <div><?= htmlspecialchars($pendiente['concepto']) ?></div>
                      <div class="meta-pendiente"><?= htmlspecialchars($pendiente['detalle'] ?? ucfirst($pendiente['registro'])) ?></div>
                      <?php if (($pendiente['registro'] ?? '') === 'personalizado' && (int) ($pendiente['requiere_firma'] ?? 0) === 1): ?>
                        <div class="meta-pendiente">Firma <?= htmlspecialchars(($pendiente['modo_firma'] ?? 'individual') === 'multiple' ? 'multiple' : 'individual') ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (($pendiente['registro'] ?? '') === 'personalizado'): ?>
                        <?php if ((int) ($pendiente['requiere_firma'] ?? 0) === 1): ?>
                          <div class="document-actions vertical">
                            <button
                              type="button"
                              class="document-chip sign abrir-documento-firmable"
                              data-pendiente-usuario-id="<?= (int) ($pendiente['pendiente_usuario_id'] ?? 0) ?>"
                            >
                              <i class="bi bi-pen"></i> Revisar y firmar PDF
                            </button>
                            <?php if (!empty($pendiente['documento_link'])): ?>
                              <a
                                class="document-chip link abrir-documento-pendiente"
                                href="<?= htmlspecialchars($pendiente['documento_link']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                data-pendiente-usuario-id="<?= (int) ($pendiente['pendiente_usuario_id'] ?? 0) ?>"
                              >
                                <i class="bi bi-link-45deg"></i> Abrir link
                              </a>
                            <?php endif; ?>
                          </div>
                        <?php else: ?>
                          <div class="document-actions vertical">
                            <?php if (!empty($pendiente['documento_archivo'])): ?>
                              <a
                                class="document-chip file abrir-documento-pendiente"
                                href="<?= htmlspecialchars($pendiente['documento_archivo']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                data-pendiente-usuario-id="<?= (int) ($pendiente['pendiente_usuario_id'] ?? 0) ?>"
                              >
                                <i class="bi bi-file-earmark-arrow-down"></i> Abrir archivo
                              </a>
                            <?php endif; ?>
                            <?php if (!empty($pendiente['documento_link'])): ?>
                              <a
                                class="document-chip link abrir-documento-pendiente"
                                href="<?= htmlspecialchars($pendiente['documento_link']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                data-pendiente-usuario-id="<?= (int) ($pendiente['pendiente_usuario_id'] ?? 0) ?>"
                              >
                                <i class="bi bi-link-45deg"></i> Abrir link
                              </a>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      <?php else: ?>
                        <a class="document-link" href="../../<?= htmlspecialchars($pendiente['documento']) ?>" target="_blank" rel="noopener noreferrer">
                          <i class="bi bi-box-arrow-up-right"></i> Abrir
                        </a>
                      <?php endif; ?>
                    </td>
                    <td>
                      <textarea
                        class="form-control observacion-pendiente"
                        rows="2"
                        placeholder="Escribe aqui una observacion si vas a rechazar"
                      ><?= htmlspecialchars($pendiente['observacion']) ?></textarea>
                    </td>
                    <td>
                      <?php
                        $puedeConfirmar = (int) ($pendiente['puede_confirmar'] ?? 1) === 1;
                        $claseEstado = $puedeConfirmar ? 'estado-pendiente' : 'estado-bloqueado';
                        $requiereFirma = (int) ($pendiente['requiere_firma'] ?? 0) === 1;
                        $mensajeBloqueo = $requiereFirma
                          ? 'Debes abrir y firmar primero el PDF antes de confirmar'
                          : 'Debes revisar primero el documento o link antes de confirmar';
                        $textoBloqueo = $puedeConfirmar ? '' : 'title="' . htmlspecialchars($mensajeBloqueo, ENT_QUOTES, 'UTF-8') . '"';
                      ?>
                      <select class="form-select form-select-sm cambiar-campo validar-pendiente <?= $claseEstado ?>" <?= $textoBloqueo ?>>
                        <option value="">Seleccione...</option>
                        <option value="Si">SI</option>
                        <option value="no">NO</option>
                      </select>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
      </div>
    <?php endif; ?>
    <?php
    $bodyMisPendientes = ob_get_clean();

    echo component('section-card', [
      'title' => 'Mis Pendientes',
      'icon' => 'fas fa-list-check',
      'headerAside' => '
        <div class="d-flex align-items-center gap-2">
          <span class="text-white small">' . htmlspecialchars($nombreUsuario) . '</span>
          <button class="btn btn-light btn-sm" type="button" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Volver
          </button>
        </div>
      ',
      'body' => $bodyMisPendientes,
    ]);
    ?>
  </div>

  <?php if ($puedeAdministrarPendientes): ?>
    <div class="modal fade" id="modalCrearPendiente" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-plus-circle me-2"></i>Crear pendiente
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form id="formCrearPendiente" enctype="multipart/form-data">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="concepto" class="form-label">Concepto</label>
                  <select class="form-select" id="concepto" name="concepto" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($conceptosPendiente as $conceptoPendiente): ?>
                      <option value="<?= htmlspecialchars($conceptoPendiente) ?>"><?= htmlspecialchars($conceptoPendiente) ?></option>
                    <?php endforeach; ?>
                    <option value="Otro">Otro</option>
                  </select>
                </div>

                <div class="col-md-6 concepto-personalizado" id="grupoConceptoPersonalizado">
                  <label for="titulo" class="form-label">Especifica el concepto</label>
                  <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Escribe el concepto">
                </div>

                <div class="col-md-6" id="grupoDocumentoArchivo" style="display: block;">
                  <label for="documento" class="form-label">Archivo</label>
                  <input type="file" class="form-control" id="documento" name="documento" accept=".pdf,.png,.jpg,.jpeg,.webp">
                </div>

                <div class="col-md-6" id="grupoDocumentoLink" style="display: block;">
                  <label for="documento_link" class="form-label">Link</label>
                  <input type="url" class="form-control" id="documento_link" name="documento_link" placeholder="https://...">
                  <div class="meta-pendiente mt-1">Puedes cargar solo archivo, solo link o ambos.</div>
                </div>

                <div class="col-12">
                  <label for="descripcion" class="form-label">Descripcion</label>
                  <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Explica brevemente que se debe revisar o confirmar"></textarea>
                </div>

                <div class="col-md-6">
                  <label for="modo_firma" class="form-label">
                    Modo de firma
                    <span class="badge-nueva-funcion" id="badgeModoFirmaCrear">
                      <i class="bi bi-stars"></i> Nueva funcion: permite firma individual o varias firmas en un mismo PDF
                    </span>
                  </label>
                  <select class="form-select" id="modo_firma" name="modo_firma">
                    <option value="individual" selected>Individual</option>
                    <option value="multiple">Multiple</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Como funciona</label>
                  <div class="hint-card" id="hintModoFirmaCrear">
                    Cada usuario firma su propia constancia y confirma con la logica actual.
                  </div>
                </div>

                <div class="col-md-4">
                  <label for="modo_asignacion" class="form-label">Asignar por</label>
                  <select class="form-select" id="modo_asignacion" name="modo_asignacion" required>
                    <option value="filtros" selected>Roles y contrato</option>
                    <option value="usuario">Usuario especifico</option>
                  </select>
                </div>

                <div class="col-md-8 bloque-asignacion" id="bloqueUsuarioEspecifico">
                  <label class="form-label">Usuarios especificos</label>
                  <div class="filtros-usuarios">
                    <input type="text" class="form-control" id="filtro_usuarios_texto" placeholder="Buscar por nombre o rol">
                    <select class="form-select" id="filtro_usuarios_rol">
                      <option value="">Todos los roles</option>
                      <?php foreach ($roles as $rol): ?>
                        <option value="<?= htmlspecialchars(mb_strtolower(trim((string) $rol['rol_nombre']), 'UTF-8')) ?>">
                          <?= htmlspecialchars($rol['rol_nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="usuarios-box">
                    <div class="row">
                      <?php foreach ($usuariosAsignables as $usuarioAsignable): ?>
                        <?php
                        $nombreUsuarioFiltro = trim((string) ($usuarioAsignable['usu_nombre'] ?? ''));
                        $rolUsuarioFiltro = trim((string) ($usuarioAsignable['rol_nombre'] ?? 'Sin rol'));
                        $textoFiltroUsuario = mb_strtolower($nombreUsuarioFiltro . ' ' . $rolUsuarioFiltro, 'UTF-8');
                        $rolFiltroUsuario = mb_strtolower($rolUsuarioFiltro, 'UTF-8');
                        ?>
                        <div
                          class="col-12 mb-2 usuario-item"
                          data-filtro-texto="<?= htmlspecialchars($textoFiltroUsuario, ENT_QUOTES, 'UTF-8') ?>"
                          data-filtro-rol="<?= htmlspecialchars($rolFiltroUsuario, ENT_QUOTES, 'UTF-8') ?>"
                        >
                          <div class="form-check">
                            <input
                              class="form-check-input usuario-especifico-check"
                              type="checkbox"
                              name="usuarios_especificos_ids[]"
                              value="<?= (int) $usuarioAsignable['idusuarios'] ?>"
                              id="usuario_especifico_<?= (int) $usuarioAsignable['idusuarios'] ?>"
                            >
                            <label class="form-check-label" for="usuario_especifico_<?= (int) $usuarioAsignable['idusuarios'] ?>">
                              <?= htmlspecialchars($usuarioAsignable['usu_nombre']) ?>
                              <span class="meta-pendiente">/ <?= htmlspecialchars(($usuarioAsignable['rol_nombre'] ?? 'Sin rol') . ' / ' . ($usuarioAsignable['usu_tipocontrato'] ?? 'Sin contrato')) ?></span>
                            </label>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <div class="col-md-8 bloque-asignacion" id="bloqueRolesContratoCrear" style="display: block;">
                  <label class="form-label">Roles que veran este pendiente</label>
                  <div class="roles-box">
                    <div class="row">
                      <?php foreach ($roles as $rol): ?>
                        <div class="col-md-6 mb-2">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="<?= (int) $rol['idroles'] ?>" id="rol_<?= (int) $rol['idroles'] ?>">
                            <label class="form-check-label" for="rol_<?= (int) $rol['idroles'] ?>">
                              <?= htmlspecialchars($rol['rol_nombre']) ?>
                            </label>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <div class="col-md-4 bloque-asignacion" id="bloqueTiposContratoCrear" style="display: block;">
                  <label class="form-label">Tipo de contrato</label>
                  <div class="roles-box">
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="checkbox" name="tipos_contrato[]" value="Empresa" id="tipo_contrato_empresa">
                      <label class="form-check-label" for="tipo_contrato_empresa">Empresa</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="tipos_contrato[]" value="Prestacion de servicios" id="tipo_contrato_prestacion">
                      <label class="form-check-label" for="tipo_contrato_prestacion">Prestacion de servicios</label>
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <label class="form-label">Regla de validacion</label>
                  <div class="hint-card">
                    El usuario asignado no podra confirmar el pendiente hasta abrir el documento por lo menos una vez.
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" form="formCrearPendiente" class="btn btn-primary">
              <i class="fas fa-save me-1"></i> Crear pendiente
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalEditarPendiente" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-pen-to-square me-2"></i>Editar pendiente
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form id="formEditarPendiente" enctype="multipart/form-data">
              <input type="hidden" id="editar_pendiente_id" name="pendiente_id">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="editar_concepto" class="form-label">Concepto</label>
                  <select class="form-select" id="editar_concepto" name="concepto" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($conceptosPendiente as $conceptoPendiente): ?>
                      <option value="<?= htmlspecialchars($conceptoPendiente) ?>"><?= htmlspecialchars($conceptoPendiente) ?></option>
                    <?php endforeach; ?>
                    <option value="Otro">Otro</option>
                  </select>
                </div>

                <div class="col-md-6 concepto-personalizado" id="grupoEditarConceptoPersonalizado">
                  <label for="editar_titulo" class="form-label">Especifica el concepto</label>
                  <input type="text" class="form-control" id="editar_titulo" name="titulo" placeholder="Escribe el concepto">
                </div>

                <div class="col-md-6" id="grupoEditarDocumentoArchivo">
                  <label for="editar_documento" class="form-label">Archivo</label>
                  <input type="file" class="form-control" id="editar_documento" name="documento" accept=".pdf,.png,.jpg,.jpeg,.webp">
                  <div class="meta-pendiente mt-1" id="textoEditarDocumentoArchivo">Si no subes uno nuevo, se conserva el archivo actual.</div>
                </div>

                <div class="col-md-6" id="grupoEditarDocumentoLink">
                  <label for="editar_documento_link" class="form-label">Link</label>
                  <input type="url" class="form-control" id="editar_documento_link" name="documento_link" placeholder="https://...">
                  <input type="hidden" id="editar_archivo_actual" value="">
                  <div class="meta-pendiente mt-1">Puedes dejar archivo, link o ambos.</div>
                </div>

                <div class="col-12">
                  <label for="editar_descripcion" class="form-label">Descripcion</label>
                  <textarea class="form-control" id="editar_descripcion" name="descripcion" rows="3" placeholder="Explica brevemente que se debe revisar o confirmar"></textarea>
                </div>

                <div class="col-md-6">
                  <label for="editar_modo_firma" class="form-label">
                    Modo de firma
                    <span class="badge-nueva-funcion" id="badgeModoFirmaEditar">
                      <i class="bi bi-stars"></i> Nueva funcion: permite firma individual o varias firmas en un mismo PDF
                    </span>
                  </label>
                  <select class="form-select" id="editar_modo_firma" name="modo_firma">
                    <option value="individual">Individual</option>
                    <option value="multiple">Multiple</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Como funciona</label>
                  <div class="hint-card" id="hintModoFirmaEditar">
                    Cada usuario firma su propia constancia y confirma con la logica actual.
                  </div>
                </div>

                <div class="col-md-4">
                  <label for="editar_modo_asignacion" class="form-label">Asignar por</label>
                  <select class="form-select" id="editar_modo_asignacion" name="modo_asignacion" required>
                    <option value="filtros">Roles y contrato</option>
                    <option value="usuario">Usuario especifico</option>
                  </select>
                </div>

                <div class="col-md-8 bloque-asignacion" id="bloqueEditarUsuarioEspecifico">
                  <label class="form-label">Usuarios especificos</label>
                  <div class="filtros-usuarios">
                    <input type="text" class="form-control" id="editar_filtro_usuarios_texto" placeholder="Buscar por nombre o rol">
                    <select class="form-select" id="editar_filtro_usuarios_rol">
                      <option value="">Todos los roles</option>
                      <?php foreach ($roles as $rol): ?>
                        <option value="<?= htmlspecialchars(mb_strtolower(trim((string) $rol['rol_nombre']), 'UTF-8')) ?>">
                          <?= htmlspecialchars($rol['rol_nombre']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="usuarios-box">
                    <div class="row">
                      <?php foreach ($usuariosAsignables as $usuarioAsignable): ?>
                        <?php
                        $nombreUsuarioFiltro = trim((string) ($usuarioAsignable['usu_nombre'] ?? ''));
                        $rolUsuarioFiltro = trim((string) ($usuarioAsignable['rol_nombre'] ?? 'Sin rol'));
                        $textoFiltroUsuario = mb_strtolower($nombreUsuarioFiltro . ' ' . $rolUsuarioFiltro, 'UTF-8');
                        $rolFiltroUsuario = mb_strtolower($rolUsuarioFiltro, 'UTF-8');
                        ?>
                        <div
                          class="col-12 mb-2 usuario-item"
                          data-filtro-texto="<?= htmlspecialchars($textoFiltroUsuario, ENT_QUOTES, 'UTF-8') ?>"
                          data-filtro-rol="<?= htmlspecialchars($rolFiltroUsuario, ENT_QUOTES, 'UTF-8') ?>"
                        >
                          <div class="form-check">
                            <input
                              class="form-check-input editar-usuario-especifico-check"
                              type="checkbox"
                              name="usuarios_especificos_ids[]"
                              value="<?= (int) $usuarioAsignable['idusuarios'] ?>"
                              id="editar_usuario_especifico_<?= (int) $usuarioAsignable['idusuarios'] ?>"
                            >
                            <label class="form-check-label" for="editar_usuario_especifico_<?= (int) $usuarioAsignable['idusuarios'] ?>">
                              <?= htmlspecialchars($usuarioAsignable['usu_nombre']) ?>
                              <span class="meta-pendiente">/ <?= htmlspecialchars(($usuarioAsignable['rol_nombre'] ?? 'Sin rol') . ' / ' . ($usuarioAsignable['usu_tipocontrato'] ?? 'Sin contrato')) ?></span>
                            </label>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <div class="col-12 bloque-asignacion" id="bloqueEditarRolesContrato" style="display: block;">
                  <label class="form-label">Roles que veran este pendiente</label>
                  <div class="roles-box">
                    <div class="row">
                      <?php foreach ($roles as $rol): ?>
                        <div class="col-md-6 mb-2">
                          <div class="form-check">
                            <input class="form-check-input editar-rol" type="checkbox" name="roles[]" value="<?= (int) $rol['idroles'] ?>" id="editar_rol_<?= (int) $rol['idroles'] ?>">
                            <label class="form-check-label" for="editar_rol_<?= (int) $rol['idroles'] ?>">
                              <?= htmlspecialchars($rol['rol_nombre']) ?>
                            </label>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <div class="col-12 bloque-asignacion" id="bloqueEditarTiposContrato" style="display: block;">
                  <label class="form-label">Tipo de contrato</label>
                  <div class="roles-box">
                    <div class="row">
                      <div class="col-md-6 mb-2">
                        <div class="form-check">
                          <input class="form-check-input editar-tipo-contrato" type="checkbox" name="tipos_contrato[]" value="Empresa" id="editar_tipo_contrato_empresa">
                          <label class="form-check-label" for="editar_tipo_contrato_empresa">Empresa</label>
                        </div>
                      </div>
                      <div class="col-md-6 mb-2">
                        <div class="form-check">
                          <input class="form-check-input editar-tipo-contrato" type="checkbox" name="tipos_contrato[]" value="Prestacion de servicios" id="editar_tipo_contrato_prestacion">
                          <label class="form-check-label" for="editar_tipo_contrato_prestacion">Prestacion de servicios</label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" form="formEditarPendiente" class="btn btn-primary">
              <i class="fas fa-save me-1"></i> Guardar cambios
            </button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    $(document).ready(function () {
      $('#tablaPendientes').DataTable({
        pageLength: 10,
        order: []
      });

      alternarConceptoPersonalizado();
      alternarModoAsignacion();
      actualizarHintModoFirma();
      alternarConceptoPersonalizadoEditar();
      alternarModoAsignacionEditar();
      actualizarHintModoFirmaEditar();
      programarOcultarBadgeNuevaFuncion('#badgeModoFirmaCrear');
      programarOcultarBadgeNuevaFuncion('#badgeModoFirmaEditar');
      inicializarFiltrosUsuarios();
    });

    function normalizarTextoFiltro(texto) {
      return (texto || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
    }

    function filtrarUsuariosLista(textoSelector, rolSelector, itemsSelector) {
      const texto = normalizarTextoFiltro($(textoSelector).val());
      const rol = normalizarTextoFiltro($(rolSelector).val());

      $(itemsSelector).each(function () {
        const $item = $(this);
        const textoItem = normalizarTextoFiltro($item.attr('data-filtro-texto'));
        const rolItem = normalizarTextoFiltro($item.attr('data-filtro-rol'));
        const coincideTexto = texto === '' || textoItem.indexOf(texto) !== -1;
        const coincideRol = rol === '' || rolItem === rol;

        $item.toggleClass('oculto-por-filtro', !(coincideTexto && coincideRol));
      });
    }

    function inicializarFiltrosUsuarios() {
      $('#filtro_usuarios_texto, #filtro_usuarios_rol').on('input change', function () {
        filtrarUsuariosLista('#filtro_usuarios_texto', '#filtro_usuarios_rol', '#bloqueUsuarioEspecifico .usuario-item');
      });

      $('#editar_filtro_usuarios_texto, #editar_filtro_usuarios_rol').on('input change', function () {
        filtrarUsuariosLista('#editar_filtro_usuarios_texto', '#editar_filtro_usuarios_rol', '#bloqueEditarUsuarioEspecifico .usuario-item');
      });
    }

    function programarOcultarBadgeNuevaFuncion(selector) {
      const $badge = $(selector);
      if ($badge.length === 0) {
        return;
      }

      $badge.removeClass('oculto');
      window.setTimeout(function () {
        $badge.addClass('oculto');
      }, 4500);
    }

    document.getElementById('modalCrearPendiente')?.addEventListener('shown.bs.modal', function () {
      programarOcultarBadgeNuevaFuncion('#badgeModoFirmaCrear');
      $('#filtro_usuarios_texto').val('');
      $('#filtro_usuarios_rol').val('');
      filtrarUsuariosLista('#filtro_usuarios_texto', '#filtro_usuarios_rol', '#bloqueUsuarioEspecifico .usuario-item');
    });

    document.getElementById('modalEditarPendiente')?.addEventListener('shown.bs.modal', function () {
      programarOcultarBadgeNuevaFuncion('#badgeModoFirmaEditar');
      filtrarUsuariosLista('#editar_filtro_usuarios_texto', '#editar_filtro_usuarios_rol', '#bloqueEditarUsuarioEspecifico .usuario-item');
    });

    function alternarConceptoPersonalizado() {
      const esOtro = $('#concepto').val() === 'Otro';
      $('#grupoConceptoPersonalizado').toggle(esOtro);
      $('#titulo').prop('required', esOtro);

      if (!esOtro) {
        $('#titulo').val('');
      }
    }

    $('#concepto').on('change', alternarConceptoPersonalizado);

    function alternarModoAsignacion() {
      const esUsuario = $('#modo_asignacion').val() === 'usuario';
      $('#bloqueUsuarioEspecifico').toggle(esUsuario);
      $('#bloqueRolesContratoCrear').toggle(!esUsuario);
      $('#bloqueTiposContratoCrear').toggle(!esUsuario);
      if (esUsuario) {
        $('#formCrearPendiente input[name="roles[]"]').prop('checked', false);
        $('#formCrearPendiente input[name="tipos_contrato[]"]').prop('checked', false);
      } else {
        $('#formCrearPendiente input[name="usuarios_especificos_ids[]"]').prop('checked', false);
        $('#filtro_usuarios_texto').val('');
        $('#filtro_usuarios_rol').val('');
        filtrarUsuariosLista('#filtro_usuarios_texto', '#filtro_usuarios_rol', '#bloqueUsuarioEspecifico .usuario-item');
      }
    }

    $('#modo_asignacion').on('change', alternarModoAsignacion);

    function actualizarHintModoFirma() {
      const esMultiple = $('#modo_firma').val() === 'multiple';
      $('#hintModoFirmaCrear').text(
        esMultiple
          ? 'Cada firma se agrega al mismo PDF acumulado para que se vean todas las firmas registradas.'
          : 'Cada usuario firma su propia constancia y confirma con la logica actual.'
      );
    }

    $('#modo_firma').on('change', actualizarHintModoFirma);

    function alternarConceptoPersonalizadoEditar() {
      const esOtro = $('#editar_concepto').val() === 'Otro';
      $('#grupoEditarConceptoPersonalizado').toggle(esOtro);
      $('#editar_titulo').prop('required', esOtro);

      if (!esOtro) {
        $('#editar_titulo').val('');
      }
    }

    $('#editar_concepto').on('change', alternarConceptoPersonalizadoEditar);

    function alternarModoAsignacionEditar() {
      const esUsuario = $('#editar_modo_asignacion').val() === 'usuario';
      $('#bloqueEditarUsuarioEspecifico').toggle(esUsuario);
      $('#bloqueEditarRolesContrato').toggle(!esUsuario);
      $('#bloqueEditarTiposContrato').toggle(!esUsuario);
      if (esUsuario) {
        $('#formEditarPendiente input[name="roles[]"]').prop('checked', false);
        $('#formEditarPendiente input[name="tipos_contrato[]"]').prop('checked', false);
      } else {
        $('#formEditarPendiente input[name="usuarios_especificos_ids[]"]').prop('checked', false);
        $('#editar_filtro_usuarios_texto').val('');
        $('#editar_filtro_usuarios_rol').val('');
        filtrarUsuariosLista('#editar_filtro_usuarios_texto', '#editar_filtro_usuarios_rol', '#bloqueEditarUsuarioEspecifico .usuario-item');
      }
    }

    $('#editar_modo_asignacion').on('change', alternarModoAsignacionEditar);

    function actualizarHintModoFirmaEditar() {
      const esMultiple = $('#editar_modo_firma').val() === 'multiple';
      $('#hintModoFirmaEditar').text(
        esMultiple
          ? 'Cada firma se agrega al mismo PDF acumulado para que se vean todas las firmas registradas.'
          : 'Cada usuario firma su propia constancia y confirma con la logica actual.'
      );
    }

    $('#editar_modo_firma').on('change', actualizarHintModoFirmaEditar);

    function aplicarClaseEstado($select, valor, bloqueado) {
      $select.removeClass('estado-pendiente estado-activo estado-inactivo estado-bloqueado');

      if (bloqueado) {
        $select.addClass('estado-bloqueado');
        return;
      }

      if (valor === 'Si') {
        $select.addClass('estado-activo');
      } else if (valor === 'no') {
        $select.addClass('estado-inactivo');
      } else {
        $select.addClass('estado-pendiente');
      }
    }

    function mostrarAlertaDocumentoPendiente() {
      Swal.fire({
        icon: 'warning',
        title: 'Debes revisar el documento',
        text: 'Primero debes revisar el documento o el link para poder validar este pendiente.'
      });
    }

    function mostrarAlertaFirmaPendiente() {
      Swal.fire({
        icon: 'warning',
        title: 'Debes firmar el PDF',
        text: 'Primero debes abrir la ventana controlada, revisar el PDF y guardar la firma para poder validar este pendiente.'
      });
    }

    function filaPuedeConfirmar($fila) {
      const valorData = $fila.data('puede-confirmar');

      if (typeof valorData !== 'undefined') {
        return String(valorData) === '1';
      }

      return String($fila.attr('data-puede-confirmar')) === '1';
    }

    function filaRequiereFirma($fila) {
      const valorData = $fila.data('requiere-firma');

      if (typeof valorData !== 'undefined') {
        return String(valorData) === '1';
      }

      return String($fila.attr('data-requiere-firma')) === '1';
    }

    $('#formCrearPendiente').on('submit', function (e) {
      e.preventDefault();

      const concepto = $('#concepto').val();
      const conceptoPersonalizado = $.trim($('#titulo').val());
      const modoAsignacion = $('#modo_asignacion').val();
      const usuariosEspecificosIds = $('#formCrearPendiente input[name="usuarios_especificos_ids[]"]:checked').map(function () {
        return $(this).val();
      }).get();
      const archivoSeleccionado = $('#documento').val();
      const linkDocumento = $.trim($('#documento_link').val());
      const cantidadTiposContrato = $('#formCrearPendiente input[name="tipos_contrato[]"]:checked').length;
      const cantidadRoles = $('#formCrearPendiente input[name="roles[]"]:checked').length;

      if (!concepto) {
        Swal.fire({
          icon: 'warning',
          title: 'Concepto requerido',
          text: 'Debes seleccionar el concepto del pendiente.'
        });
        return;
      }

      if (concepto === 'Otro' && !conceptoPersonalizado) {
        Swal.fire({
          icon: 'warning',
          title: 'Concepto requerido',
          text: 'Debes escribir el concepto personalizado.'
        });
        return;
      }

      if (!archivoSeleccionado && !linkDocumento) {
        Swal.fire({
          icon: 'warning',
          title: 'Documento requerido',
          text: 'Debes agregar un archivo, un link o ambos.'
        });
        return;
      }

      if (modoAsignacion === 'usuario' && usuariosEspecificosIds.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Usuarios requeridos',
          text: 'Debes seleccionar al menos una persona.'
        });
        return;
      }

      if (modoAsignacion !== 'usuario' && cantidadRoles === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Roles requeridos',
          text: 'Debes seleccionar al menos un rol.'
        });
        return;
      }

      if (modoAsignacion !== 'usuario' && cantidadTiposContrato === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Contrato requerido',
          text: 'Debes seleccionar al menos un tipo de contrato.'
        });
        return;
      }

      const formData = new FormData(this);
      formData.append('accion', 'crear_pendiente');

      $.ajax({
        url: 'PendientesController.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (respuesta) {
          const modalEl = document.getElementById('modalCrearPendiente');
          const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
          if (modal) {
            modal.hide();
          }
          Swal.fire({
            icon: 'success',
            title: 'Pendiente creado',
            text: respuesta.message + ' Usuarios asignados: ' + (respuesta.usuarios_asignados || 0),
          }).then(function () {
            location.reload();
          });
        },
        error: function (xhr) {
          const mensaje = xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : 'No fue posible crear el pendiente.';

          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje
          });
        }
      });
    });

    $(document).on('click', '.validar-pendiente', function (e) {
      const $select = $(this);
      const $fila = $select.closest('tr');
      const bloqueado = !filaPuedeConfirmar($fila);
      const requiereFirma = filaRequiereFirma($fila);

      if (!bloqueado) {
        return;
      }

      e.preventDefault();
      $select.val('');
      aplicarClaseEstado($select, '', true);
      if (requiereFirma) {
        mostrarAlertaFirmaPendiente();
        return;
      }

      mostrarAlertaDocumentoPendiente();
    });

    $(document).on('click', '.btn-editar-pendiente', function () {
      const $boton = $(this);
      let rolesSeleccionados = [];
      let tiposContratoSeleccionados = [];

      try {
        rolesSeleccionados = JSON.parse($boton.attr('data-roles') || '[]');
      } catch (error) {
        rolesSeleccionados = [];
      }

      try {
        tiposContratoSeleccionados = JSON.parse($boton.attr('data-tipos-contrato') || '[]');
      } catch (error) {
        tiposContratoSeleccionados = [];
      }

      $('#editar_pendiente_id').val($boton.data('pendiente-id'));
      $('#editar_concepto').val($boton.data('concepto'));
      $('#editar_titulo').val($boton.data('titulo'));
      $('#editar_descripcion').val($boton.data('descripcion'));
      $('#editar_modo_asignacion').val($boton.data('modo-asignacion'));
      $('#editar_modo_firma').val($boton.data('modo-firma') || 'individual');
      let usuariosObjetivoIds = [];
      try {
        usuariosObjetivoIds = JSON.parse($boton.attr('data-usuarios-objetivo-ids') || '[]');
      } catch (error) {
        usuariosObjetivoIds = [];
      }
      if (usuariosObjetivoIds.length === 0 && $boton.data('usuario-objetivo-id')) {
        usuariosObjetivoIds = [String($boton.data('usuario-objetivo-id'))];
      }
      $('.editar-usuario-especifico-check').prop('checked', false);
      usuariosObjetivoIds.map(String).forEach(function (usuarioId) {
        $('#editar_usuario_especifico_' + usuarioId).prop('checked', true);
      });
      $('#editar_filtro_usuarios_texto').val('');
      $('#editar_filtro_usuarios_rol').val('');
      filtrarUsuariosLista('#editar_filtro_usuarios_texto', '#editar_filtro_usuarios_rol', '#bloqueEditarUsuarioEspecifico .usuario-item');
      const documentoArchivo = $boton.attr('data-documento-archivo') || '';
      const documentoLink = $boton.attr('data-documento-link') || '';
      $('#editar_archivo_actual').val(documentoArchivo);
      $('#textoEditarDocumentoArchivo').text(
        documentoArchivo
          ? 'Ya hay un archivo cargado. Si subes uno nuevo, reemplaza el actual.'
          : 'Actualmente este pendiente no tiene archivo. Puedes adjuntar uno si lo necesitas.'
      );
      $('#editar_documento_link').val(documentoLink);
      $('#editar_documento').val('');

      $('.editar-rol').prop('checked', false);
      rolesSeleccionados.forEach(function (rolId) {
        $('#editar_rol_' + rolId).prop('checked', true);
      });

      $('.editar-tipo-contrato').prop('checked', false);
      tiposContratoSeleccionados.forEach(function (tipoContrato) {
        $('.editar-tipo-contrato[value="' + tipoContrato + '"]').prop('checked', true);
      });

      alternarConceptoPersonalizadoEditar();
      alternarModoAsignacionEditar();
      actualizarHintModoFirmaEditar();
    });

    $('#formEditarPendiente').on('submit', function (e) {
      e.preventDefault();

      const concepto = $('#editar_concepto').val();
      const conceptoPersonalizado = $.trim($('#editar_titulo').val());
      const modoAsignacion = $('#editar_modo_asignacion').val();
      const usuariosEspecificosIds = $('#formEditarPendiente input[name="usuarios_especificos_ids[]"]:checked').map(function () {
        return $(this).val();
      }).get();
      const archivoSeleccionado = $('#editar_documento').val();
      const archivoActual = $.trim($('#editar_archivo_actual').val());
      const linkDocumento = $.trim($('#editar_documento_link').val());
      const cantidadRoles = $('#formEditarPendiente input[name="roles[]"]:checked').length;
      const cantidadTiposContrato = $('#formEditarPendiente input[name="tipos_contrato[]"]:checked').length;

      if (!concepto) {
        Swal.fire({ icon: 'warning', title: 'Concepto requerido', text: 'Debes seleccionar el concepto del pendiente.' });
        return;
      }

      if (concepto === 'Otro' && !conceptoPersonalizado) {
        Swal.fire({ icon: 'warning', title: 'Concepto requerido', text: 'Debes escribir el concepto personalizado.' });
        return;
      }

      if (!archivoSeleccionado && !archivoActual && !linkDocumento) {
        Swal.fire({ icon: 'warning', title: 'Documento requerido', text: 'Debes dejar un archivo, un link o ambos.' });
        return;
      }

      if (modoAsignacion === 'usuario' && usuariosEspecificosIds.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Usuarios requeridos', text: 'Debes seleccionar al menos una persona.' });
        return;
      }

      if (modoAsignacion !== 'usuario' && cantidadRoles === 0) {
        Swal.fire({ icon: 'warning', title: 'Roles requeridos', text: 'Debes seleccionar al menos un rol.' });
        return;
      }

      if (modoAsignacion !== 'usuario' && cantidadTiposContrato === 0) {
        Swal.fire({ icon: 'warning', title: 'Contrato requerido', text: 'Debes seleccionar al menos un tipo de contrato.' });
        return;
      }

      const formData = new FormData(this);
      formData.append('accion', 'editar_pendiente');

      $.ajax({
        url: 'PendientesController.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (respuesta) {
          const modalEl = document.getElementById('modalEditarPendiente');
          const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
          if (modal) {
            modal.hide();
          }

          Swal.fire({
            icon: 'success',
            title: 'Pendiente actualizado',
            text: respuesta.message
          }).then(function () {
            location.reload();
          });
        },
        error: function (xhr) {
          const mensaje = xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : 'No fue posible actualizar el pendiente.';

          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje
          });
        }
      });
    });

    $(document).on('click', '.btn-eliminar-pendiente', function () {
      const pendienteId = $(this).data('pendiente-id');

      Swal.fire({
        icon: 'warning',
        title: 'Eliminar pendiente',
        text: 'Esta accion ocultara el pendiente para los usuarios asignados.',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar'
      }).then(function (resultado) {
        if (!resultado.isConfirmed) {
          return;
        }

        $.ajax({
          url: 'PendientesController.php',
          type: 'POST',
          dataType: 'json',
          data: {
            accion: 'eliminar_pendiente',
            pendiente_id: pendienteId
          },
          success: function (respuesta) {
            Swal.fire({
              icon: 'success',
              title: 'Pendiente eliminado',
              text: respuesta.message
            }).then(function () {
              location.reload();
            });
          },
          error: function (xhr) {
            const mensaje = xhr.responseJSON && xhr.responseJSON.message
              ? xhr.responseJSON.message
              : 'No fue posible eliminar el pendiente.';

            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: mensaje
            });
          }
        });
      });
    });

    $(document).on('click', '.btn-eliminar-usuario-pendiente', function () {
      const pendienteId = $(this).data('pendiente-id');
      const usuarioId = $(this).data('usuario-id');
      const nombreUsuario = $(this).data('usuario-nombre');

      Swal.fire({
        icon: 'warning',
        title: 'Quitar persona',
        text: 'Se retirara a ' + nombreUsuario + ' de este pendiente.',
        showCancelButton: true,
        confirmButtonText: 'Si, quitar',
        cancelButtonText: 'Cancelar'
      }).then(function (resultado) {
        if (!resultado.isConfirmed) {
          return;
        }

        $.ajax({
          url: 'PendientesController.php',
          type: 'POST',
          dataType: 'json',
          data: {
            accion: 'eliminar_usuario_pendiente',
            pendiente_id: pendienteId,
            usuario_id: usuarioId
          },
          success: function (respuesta) {
            Swal.fire({
              icon: 'success',
              title: 'Persona retirada',
              text: respuesta.message
            }).then(function () {
              location.reload();
            });
          },
          error: function (xhr) {
            const mensaje = xhr.responseJSON && xhr.responseJSON.message
              ? xhr.responseJSON.message
              : 'No fue posible retirar a la persona seleccionada.';

            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: mensaje
            });
          }
        });
      });
    });

    $(document).on('click', '.abrir-documento-pendiente', function () {
      const $link = $(this);
      const pendienteUsuarioId = $link.data('pendiente-usuario-id');
      const $fila = $link.closest('tr');
      const $select = $fila.find('.validar-pendiente');

      $.ajax({
        url: 'PendientesController.php',
        type: 'POST',
        dataType: 'json',
        data: {
          accion: 'marcar_documento_abierto',
          pendiente_usuario_id: pendienteUsuarioId
        },
        success: function (respuesta) {
          if (respuesta.success) {
            $fila.attr('data-puede-confirmar', '1');
            $fila.data('puede-confirmar', 1);
            $select.prop('disabled', false).removeAttr('title');
            aplicarClaseEstado($select, '', false);
          }
        }
      });
    });

    $(document).on('click', '.abrir-documento-firmable', function () {
      const pendienteUsuarioId = $(this).data('pendiente-usuario-id');
      const url = 'PendienteFirmaController.php?pendiente_usuario_id=' + encodeURIComponent(pendienteUsuarioId);
      window.open(url, 'pendiente_firma_' + pendienteUsuarioId, 'width=1280,height=860,scrollbars=yes,resizable=yes');
    });

    window.addEventListener('message', function (event) {
      if (!event.data || event.data.tipo !== 'pendiente_pdf_firmado') {
        return;
      }

      const pendienteUsuarioId = parseInt(event.data.pendienteUsuarioId, 10);
      if (!pendienteUsuarioId) {
        return;
      }

      const $fila = $('#tablaPendientes tbody tr').filter(function () {
        return parseInt($(this).data('pendiente-usuario-id'), 10) === pendienteUsuarioId;
      }).first();

      if ($fila.length === 0) {
        return;
      }

      const $select = $fila.find('.validar-pendiente');
      $fila.attr('data-puede-confirmar', '1');
      $fila.data('puede-confirmar', 1);
      $fila.attr('data-firmado', '1');
      $fila.data('firmado', 1);
      $select.val('Si');
      $select.prop('disabled', true).removeAttr('title');
      aplicarClaseEstado($select, 'Si', false);

      if (event.data.autoConfirmado === true) {
        Swal.fire({
          icon: 'success',
          title: 'Pendiente validado',
          text: event.data.modoFirma === 'multiple'
            ? 'Tu firma quedo registrada, el PDF acumulado fue actualizado y el pendiente ya quedo validado.'
            : 'La firma quedo registrada y el pendiente ya quedo validado.',
          timer: 1800,
          showConfirmButton: false
        });

        $fila.fadeOut(200, function () {
          if ($.fn.DataTable.isDataTable('#tablaPendientes')) {
            $('#tablaPendientes').DataTable().row($(this)).remove().draw(false);
          } else {
            $(this).remove();
          }

          if ($('#tablaPendientes tbody tr').length === 0) {
            location.reload();
          }
        });
        return;
      }

      Swal.fire({
        icon: 'success',
        title: 'PDF firmado',
        text: event.data.modoFirma === 'multiple'
          ? 'Tu firma quedo registrada y el PDF acumulado fue actualizado.'
          : 'La firma quedo registrada y ya puedes confirmar este pendiente.',
        timer: 1600,
        showConfirmButton: false
      });
    });

    $(document).on('change', '.validar-pendiente', function () {
      const $select = $(this);
      const $fila = $select.closest('tr');
      const confirmacion = $select.val();
      const observacion = $.trim($fila.find('.observacion-pendiente').val());
      const bloqueado = !filaPuedeConfirmar($fila);
      const requiereFirma = filaRequiereFirma($fila);

      aplicarClaseEstado($select, confirmacion, bloqueado);

      if (bloqueado) {
        if (requiereFirma) {
          mostrarAlertaFirmaPendiente();
        } else {
          mostrarAlertaDocumentoPendiente();
        }
        $select.val('');
        aplicarClaseEstado($select, '', true);
        return;
      }

      if (!confirmacion) {
        return;
      }

      if (confirmacion === 'no' && observacion === '') {
        Swal.fire({
          icon: 'warning',
          title: 'Observacion requerida',
          text: 'Debes escribir el motivo por el cual rechazas este pendiente.'
        });
        $select.val('');
        aplicarClaseEstado($select, '', false);
        return;
      }

      $.ajax({
        url: 'PendientesController.php',
        type: 'POST',
        dataType: 'json',
        data: {
          accion: 'gestionar_pendiente',
          registro: $fila.data('registro'),
          fecha_inicio: $fila.data('fecha-inicio'),
          tipo: $fila.data('tipo'),
          pendiente_usuario_id: $fila.data('pendiente-usuario-id'),
          confirmacion: confirmacion,
          observacion: observacion
        },
        success: function (respuesta) {
          Swal.fire({
            icon: 'success',
            title: 'Pendiente actualizado',
            text: respuesta.message,
            timer: 1300,
            showConfirmButton: false
          });

          $fila.fadeOut(200, function () {
            if ($.fn.DataTable.isDataTable('#tablaPendientes')) {
              $('#tablaPendientes').DataTable().row($(this)).remove().draw(false);
            } else {
              $(this).remove();
            }

            if ($('#tablaPendientes tbody tr').length === 0) {
              location.reload();
            }
          });
        },
        error: function (xhr) {
          const mensaje = xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : 'Ocurrio un error al actualizar el pendiente.';

          $select.val('');
          aplicarClaseEstado($select, '', false);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje
          });
        }
      });
    });
  </script>
  <?php pendientesViewLog('Fin vista Pendientes'); ?>
</body>
</html>
