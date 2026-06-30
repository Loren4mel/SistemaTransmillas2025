<?php
/**
 * Popup: Editar/Crear Plantilla
 */
$data = $data ?? [];
$modulo = $data['slug_modulo'] ?? ($_GET['modulo'] ?? 'preop');
$id = (int) ($data['id_plantilla'] ?? 0);
$esSST = ($modulo === 'sst');
$creador = $data['creador_nombre'] ?? '';
$fechaCreacion = $data['fecha_creacion'] ?? '';
?>
<div class="modal fade" id="modalPlantilla" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= $id ? 'Editar' : 'Nueva' ?> Plantilla</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <?php if ($id && $creador): ?>
      <div class="modal-body py-1 text-muted small border-bottom">
        Creado <?= $fechaCreacion ? date('d/m/Y', strtotime($fechaCreacion)) : '' ?> por <?= htmlspecialchars($creador) ?>
      </div>
      <?php endif; ?>
      <div class="modal-body">
        <form id="formPlantilla">
          <input type="hidden" name="id_plantilla" value="<?= $id ?>">
          <input type="hidden" name="slug_modulo" value="<?= $modulo ?>">

          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre_base" class="form-control"
                   value="<?= htmlspecialchars($data['nombre_base'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipo destinatario</label>
            <select name="tipo_destinatario" class="form-select">
              <option value="USUARIO" <?= ($data['tipo_destinatario'] ?? '') === 'USUARIO' ? 'selected' : '' ?>>Usuario</option>
              <option value="VEHICULO" <?= ($data['tipo_destinatario'] ?? '') === 'VEHICULO' ? 'selected' : '' ?>>Vehiculo</option>
              <option value="CONDUCTOR" <?= ($data['tipo_destinatario'] ?? '') === 'CONDUCTOR' ? 'selected' : '' ?>>Conductor</option>
            </select>
          </div>

          <?php if (!$esSST): ?>
          <div class="mb-3">
            <label class="form-label">Aplica a roles (IDs separados por coma)</label>
            <input type="text" name="aplica_a_roles" class="form-control"
                   value="<?= htmlspecialchars($data['aplica_a_roles'] ?? '') ?>"
                   placeholder="Ej: 1,2,3">
          </div>
          <div class="mb-3">
            <label class="form-label">Aplica a tipo vehiculo</label>
            <select name="aplica_a_tipo_vehiculo" class="form-select">
              <option value="">Todos</option>
              <option value="CARRO" <?= ($data['aplica_a_tipo_vehiculo'] ?? '') === 'CARRO' ? 'selected' : '' ?>>Carro</option>
              <option value="MOTO" <?= ($data['aplica_a_tipo_vehiculo'] ?? '') === 'MOTO' ? 'selected' : '' ?>>Moto</option>
            </select>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" name="es_default" class="form-check-input" value="1"
                   id="chkDefault" <?= ($data['es_default'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label" for="chkDefault">Plantilla por defecto</label>
          </div>
          <?php else: ?>
          <input type="hidden" name="tipo_destinatario" value="CONDUCTOR">
          <?php endif; ?>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarPlantilla">Guardar</button>
      </div>
    </div>
  </div>
</div>
