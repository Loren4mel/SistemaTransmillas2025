<?php
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../../helpers/view.php';
$conceptosPendiente = ['Documento', 'Pago', 'Firma', 'Aprobacion'];
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
  </style>
</head>
<body>
  <div class="container-fluid mt-4">
    <?php if ($rolUsuario === 1): ?>
      <?php ob_start(); ?>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="hint-card mb-0">
          El gerente puede crear pendientes con archivo o link y asignarlos a uno o varios roles.
          Los usuarios no podran confirmarlos hasta abrir el documento.
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearPendiente">
          <i class="fas fa-plus me-1"></i> Nuevo pendiente
        </button>
      </div>
      <?php
      $bodyGestionPendientes = ob_get_clean();

      echo component('section-card', [
        'title' => 'Gestion de Pendientes',
        'icon' => 'fas fa-plus-circle',
        'headerAside' => '<span class="text-white small">Solo disponible para gerente</span>',
        'body' => $bodyGestionPendientes,
      ]);
      ?>
    <?php endif; ?>

    <?php if ($rolUsuario === 1): ?>
      <?php ob_start(); ?>
      <?php if (empty($pendientesCreados)): ?>
        <?= component('empty-state', [
          'title' => 'Aun no has creado pendientes',
          'message' => 'Cuando crees uno, aqui podras ver quien lo acepto, rechazo o sigue pendiente.',
        ]) ?>
      <?php else: ?>
        <div class="accordion" id="accordionPendientesCreados">
          <?php foreach ($pendientesCreados as $indice => $pendienteCreado): ?>
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
                          </div>
                        </div>
                      </div>
                    </button>
                  </h2>
                  <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?= $collapseId ?>" data-bs-parent="#accordionPendientesCreados">
                    <div class="accordion-body">
                      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <a class="document-link" href="<?= htmlspecialchars($pendienteCreado['documento']) ?>" target="_blank" rel="noopener noreferrer">
                          <i class="bi bi-box-arrow-up-right"></i> Abrir documento
                        </a>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                          <span class="meta-pendiente">
                            Roles: <?= htmlspecialchars(implode(', ', $pendienteCreado['roles_nombres'])) ?>
                          </span>
                          <span class="meta-pendiente">
                            Contrato: <?= htmlspecialchars(!empty($pendienteCreado['tipos_contrato']) ? implode(', ', $pendienteCreado['tipos_contrato']) : 'Todos') ?>
                          </span>
                          <span class="meta-pendiente">
                            Estado general: <?= (int) $pendienteCreado['estado'] === 1 ? 'Activo' : 'Inactivo' ?>
                          </span>
                        </div>
                      </div>

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
                          data-documento="<?= htmlspecialchars($pendienteCreado['documento']) ?>"
                          data-tipo-documento="<?= htmlspecialchars(strpos($pendienteCreado['documento'], '../uploads/pendientes/') === 0 ? 'archivo' : 'link') ?>"
                          data-roles='<?= htmlspecialchars(json_encode($pendienteCreado['roles_ids']), ENT_QUOTES, 'UTF-8') ?>'
                          data-tipos-contrato='<?= htmlspecialchars(json_encode($pendienteCreado['tipos_contrato']), ENT_QUOTES, 'UTF-8') ?>'
                          data-modo-asignacion="<?= htmlspecialchars($pendienteCreado['modo_asignacion']) ?>"
                          data-usuario-objetivo-id="<?= (int) $pendienteCreado['usuario_objetivo_id'] ?>"
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

                      <div class="table-responsive">
                        <table class="table table-bordered table-hover tabla-seguimiento">
                          <thead class="thead-modern">
                            <tr>
                              <th>Usuario</th>
                              <th>Rol</th>
                              <th>Documento abierto</th>
                              <th>Estado</th>
                              <th>Fecha confirmacion</th>
                              <th>Observacion</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($pendienteCreado['usuarios'] as $usuarioPendiente): ?>
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
                  <tr
                    data-registro="<?= htmlspecialchars($pendiente['registro']) ?>"
                    data-fecha-inicio="<?= htmlspecialchars($pendiente['fecha_inicio']) ?>"
                    data-tipo="<?= htmlspecialchars($pendiente['tipo']) ?>"
                    data-pendiente-usuario-id="<?= (int) ($pendiente['pendiente_usuario_id'] ?? 0) ?>"
                    data-puede-confirmar="<?= (int) ($pendiente['puede_confirmar'] ?? 1) ?>"
                  >
                    <td><?= htmlspecialchars($pendiente['fecha_inicio']) ?></td>
                    <td><?= htmlspecialchars($pendiente['fecha_fin']) ?></td>
                    <td>
                      <div><?= htmlspecialchars($pendiente['concepto']) ?></div>
                      <div class="meta-pendiente"><?= htmlspecialchars($pendiente['detalle'] ?? ucfirst($pendiente['registro'])) ?></div>
                    </td>
                    <td>
                      <?php if (($pendiente['registro'] ?? '') === 'personalizado'): ?>
                        <a
                          class="document-link abrir-documento-pendiente"
                          href="<?= htmlspecialchars($pendiente['documento']) ?>"
                          target="_blank"
                          rel="noopener noreferrer"
                          data-pendiente-usuario-id="<?= (int) ($pendiente['pendiente_usuario_id'] ?? 0) ?>"
                        >
                          <i class="bi bi-box-arrow-up-right"></i> Abrir
                        </a>
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
                        $textoBloqueo = $puedeConfirmar ? '' : 'title="Debes revisar primero el documento o link antes de confirmar"';
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

  <?php if ($rolUsuario === 1): ?>
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

                <div class="col-md-6">
                  <label for="tipo_documento" class="form-label">Tipo de documento</label>
                  <select class="form-select" id="tipo_documento" name="tipo_documento" required>
                    <option value="archivo" selected>Archivo</option>
                    <option value="link">Link</option>
                  </select>
                </div>

                <div class="col-md-6 documento-opcion" id="grupoDocumentoArchivo" style="display: block;">
                  <label for="documento" class="form-label">Archivo</label>
                  <input type="file" class="form-control" id="documento" name="documento" accept=".pdf,.png,.jpg,.jpeg,.webp">
                </div>

                <div class="col-md-6 documento-opcion" id="grupoDocumentoLink">
                  <label for="documento_link" class="form-label">Link</label>
                  <input type="url" class="form-control" id="documento_link" name="documento_link" placeholder="https://...">
                </div>

                <div class="col-12">
                  <label for="descripcion" class="form-label">Descripcion</label>
                  <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Explica brevemente que se debe revisar o confirmar"></textarea>
                </div>

                <div class="col-md-4">
                  <label for="modo_asignacion" class="form-label">Asignar por</label>
                  <select class="form-select" id="modo_asignacion" name="modo_asignacion" required>
                    <option value="filtros" selected>Roles y contrato</option>
                    <option value="usuario">Usuario especifico</option>
                  </select>
                </div>

                <div class="col-md-8 bloque-asignacion" id="bloqueUsuarioEspecifico">
                  <label for="usuario_especifico_id" class="form-label">Usuario especifico</label>
                  <select class="form-select" id="usuario_especifico_id" name="usuario_especifico_id">
                    <option value="">Seleccione...</option>
                    <?php foreach ($usuariosAsignables as $usuarioAsignable): ?>
                      <option value="<?= (int) $usuarioAsignable['idusuarios'] ?>">
                        <?= htmlspecialchars($usuarioAsignable['usu_nombre'] . ' / ' . ($usuarioAsignable['rol_nombre'] ?? 'Sin rol') . ' / ' . ($usuarioAsignable['usu_tipocontrato'] ?? 'Sin contrato')) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
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

                <div class="col-md-6">
                  <label for="editar_tipo_documento" class="form-label">Tipo de documento</label>
                  <select class="form-select" id="editar_tipo_documento" name="tipo_documento" required>
                    <option value="archivo">Archivo</option>
                    <option value="link">Link</option>
                  </select>
                </div>

                <div class="col-md-6 documento-opcion" id="grupoEditarDocumentoArchivo">
                  <label for="editar_documento" class="form-label">Archivo</label>
                  <input type="file" class="form-control" id="editar_documento" name="documento" accept=".pdf,.png,.jpg,.jpeg,.webp">
                  <div class="meta-pendiente mt-1">Si no subes uno nuevo, se conserva el archivo actual.</div>
                </div>

                <div class="col-md-6 documento-opcion" id="grupoEditarDocumentoLink">
                  <label for="editar_documento_link" class="form-label">Link</label>
                  <input type="url" class="form-control" id="editar_documento_link" name="documento_link" placeholder="https://...">
                </div>

                <div class="col-12">
                  <label for="editar_descripcion" class="form-label">Descripcion</label>
                  <textarea class="form-control" id="editar_descripcion" name="descripcion" rows="3" placeholder="Explica brevemente que se debe revisar o confirmar"></textarea>
                </div>

                <div class="col-md-4">
                  <label for="editar_modo_asignacion" class="form-label">Asignar por</label>
                  <select class="form-select" id="editar_modo_asignacion" name="modo_asignacion" required>
                    <option value="filtros">Roles y contrato</option>
                    <option value="usuario">Usuario especifico</option>
                  </select>
                </div>

                <div class="col-md-8 bloque-asignacion" id="bloqueEditarUsuarioEspecifico">
                  <label for="editar_usuario_especifico_id" class="form-label">Usuario especifico</label>
                  <select class="form-select" id="editar_usuario_especifico_id" name="usuario_especifico_id">
                    <option value="">Seleccione...</option>
                    <?php foreach ($usuariosAsignables as $usuarioAsignable): ?>
                      <option value="<?= (int) $usuarioAsignable['idusuarios'] ?>">
                        <?= htmlspecialchars($usuarioAsignable['usu_nombre'] . ' / ' . ($usuarioAsignable['rol_nombre'] ?? 'Sin rol') . ' / ' . ($usuarioAsignable['usu_tipocontrato'] ?? 'Sin contrato')) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
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

      alternarTipoDocumento();
      alternarConceptoPersonalizado();
      alternarModoAsignacion();
      alternarTipoDocumentoEditar();
      alternarConceptoPersonalizadoEditar();
      alternarModoAsignacionEditar();
    });

    function alternarTipoDocumento() {
      const tipo = $('#tipo_documento').val();
      const esArchivo = tipo === 'archivo';

      $('#grupoDocumentoArchivo').toggle(esArchivo);
      $('#grupoDocumentoLink').toggle(!esArchivo);

      $('#documento').prop('required', esArchivo);
      $('#documento_link').prop('required', !esArchivo);

      if (esArchivo) {
        $('#documento_link').val('');
      } else {
        $('#documento').val('');
      }
    }

    $('#tipo_documento').on('change', alternarTipoDocumento);

    function alternarTipoDocumentoEditar() {
      const tipo = $('#editar_tipo_documento').val();
      const esArchivo = tipo === 'archivo';

      $('#grupoEditarDocumentoArchivo').toggle(esArchivo);
      $('#grupoEditarDocumentoLink').toggle(!esArchivo);
      $('#editar_documento_link').prop('required', !esArchivo);

      if (esArchivo) {
        $('#editar_documento_link').val('');
      } else {
        $('#editar_documento').val('');
      }
    }

    $('#editar_tipo_documento').on('change', alternarTipoDocumentoEditar);

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
      $('#usuario_especifico_id').prop('required', esUsuario);

      if (esUsuario) {
        $('#formCrearPendiente input[name="roles[]"]').prop('checked', false);
        $('#formCrearPendiente input[name="tipos_contrato[]"]').prop('checked', false);
      } else {
        $('#usuario_especifico_id').val('');
      }
    }

    $('#modo_asignacion').on('change', alternarModoAsignacion);

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
      $('#editar_usuario_especifico_id').prop('required', esUsuario);

      if (esUsuario) {
        $('#formEditarPendiente input[name="roles[]"]').prop('checked', false);
        $('#formEditarPendiente input[name="tipos_contrato[]"]').prop('checked', false);
      } else {
        $('#editar_usuario_especifico_id').val('');
      }
    }

    $('#editar_modo_asignacion').on('change', alternarModoAsignacionEditar);

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

    $('#formCrearPendiente').on('submit', function (e) {
      e.preventDefault();

      const concepto = $('#concepto').val();
      const conceptoPersonalizado = $.trim($('#titulo').val());
      const tipoDocumento = $('#tipo_documento').val();
      const modoAsignacion = $('#modo_asignacion').val();
      const usuarioEspecificoId = $('#usuario_especifico_id').val();
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

      if (tipoDocumento === 'archivo' && !archivoSeleccionado) {
        Swal.fire({
          icon: 'warning',
          title: 'Archivo requerido',
          text: 'Debes seleccionar el archivo del pendiente.'
        });
        return;
      }

      if (tipoDocumento === 'link' && !linkDocumento) {
        Swal.fire({
          icon: 'warning',
          title: 'Link requerido',
          text: 'Debes escribir el link del documento.'
        });
        return;
      }

      if (modoAsignacion === 'usuario' && !usuarioEspecificoId) {
        Swal.fire({
          icon: 'warning',
          title: 'Usuario requerido',
          text: 'Debes seleccionar el usuario especifico.'
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
      const bloqueado = String($fila.data('puede-confirmar')) !== '1';

      if (!bloqueado) {
        return;
      }

      e.preventDefault();
      $select.val('');
      aplicarClaseEstado($select, '', true);
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
      $('#editar_tipo_documento').val($boton.data('tipo-documento'));
      $('#editar_modo_asignacion').val($boton.data('modo-asignacion'));
      $('#editar_usuario_especifico_id').val($boton.data('usuario-objetivo-id'));
      $('#editar_documento_link').val($boton.data('tipo-documento') === 'link' ? $boton.data('documento') : '');
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
      alternarTipoDocumentoEditar();
      alternarModoAsignacionEditar();
    });

    $('#formEditarPendiente').on('submit', function (e) {
      e.preventDefault();

      const concepto = $('#editar_concepto').val();
      const conceptoPersonalizado = $.trim($('#editar_titulo').val());
      const tipoDocumento = $('#editar_tipo_documento').val();
      const modoAsignacion = $('#editar_modo_asignacion').val();
      const usuarioEspecificoId = $('#editar_usuario_especifico_id').val();
      const archivoSeleccionado = $('#editar_documento').val();
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

      if (tipoDocumento === 'link' && !linkDocumento) {
        Swal.fire({ icon: 'warning', title: 'Link requerido', text: 'Debes escribir el link del documento.' });
        return;
      }

      if (modoAsignacion === 'usuario' && !usuarioEspecificoId) {
        Swal.fire({ icon: 'warning', title: 'Usuario requerido', text: 'Debes seleccionar el usuario especifico.' });
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
            $select.prop('disabled', false).removeAttr('title');
            aplicarClaseEstado($select, '', false);
          }
        }
      });
    });

    $(document).on('change', '.validar-pendiente', function () {
      const $select = $(this);
      const $fila = $select.closest('tr');
      const confirmacion = $select.val();
      const observacion = $.trim($fila.find('.observacion-pendiente').val());
      const bloqueado = String($fila.data('puede-confirmar')) !== '1';

      aplicarClaseEstado($select, confirmacion, bloqueado);

      if (bloqueado) {
        mostrarAlertaDocumentoPendiente();
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
</body>
</html>
