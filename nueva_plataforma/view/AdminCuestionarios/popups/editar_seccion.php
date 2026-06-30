<?php
/**
 * Popup: Editar/Crear Seccion
 */
$data = $data ?? [];
$idVersion = (int) ($data['id_version'] ?? ($_GET['id_version'] ?? 0));
$seccion = $data['seccion'] ?? null;
?>
<div class="modal fade" id="modalSeccion" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= $seccion ? 'Editar' : 'Nueva' ?> Seccion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formSeccion">
          <input type="hidden" name="id_seccion" value="<?= $seccion['id_seccion'] ?? 0 ?>">
          <input type="hidden" name="id_version" value="<?= $idVersion ?>">

          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control"
                   value="<?= htmlspecialchars($seccion['nombre'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripcion (SST)</label>
            <textarea name="descripcion" class="form-control" rows="2"><?=
                htmlspecialchars($seccion['descripcion'] ?? '') ?></textarea>
          </div>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Clase CSS</label>
              <input type="text" name="css_clase" class="form-control"
                     value="<?= htmlspecialchars($seccion['css_clase'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Orden</label>
              <input type="number" name="orden" class="form-control"
                     value="<?= (int) ($seccion['orden'] ?? 0) ?>" min="0">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarSeccion">Guardar</button>
      </div>
    </div>
  </div>
</div>
