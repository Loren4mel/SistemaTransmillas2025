<?php
/**
 * Popup: Editar/Crear Version
 */
$data = $data ?? [];
$idPlantilla = (int) ($data['id_plantilla'] ?? ($_GET['id_plantilla'] ?? 0));
$version = $data['version'] ?? null;
$esNueva = !$version;
?>
<div class="modal fade" id="modalVersion" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= $esNueva ? 'Nueva' : 'Editar' ?> Version</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formVersion">
          <input type="hidden" name="id_version" value="<?= $version['id_version'] ?? 0 ?>">
          <input type="hidden" name="id_plantilla" value="<?= $idPlantilla ?>">

          <?php if ($esNueva): ?>
          <p class="text-info">
            <i class="fas fa-info-circle"></i>
            Se clonaran las secciones y preguntas de la version activa actual.
          </p>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Numero de version</label>
            <input type="text" name="numero_version" class="form-control"
                   value="<?= htmlspecialchars($version['numero_version'] ?? '') ?>"
                   placeholder="Ej: v2.0" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Notas de cambio</label>
            <textarea name="notas_cambio" class="form-control" rows="3"><?=
                htmlspecialchars($version['notas_cambio'] ?? '') ?></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarVersion">Guardar</button>
      </div>
    </div>
  </div>
</div>
