<?php
/**
 * Popup: Editar/Crear Pregunta
 */
$data = $data ?? [];
$seccion = $data['seccion'] ?? null;
$pregunta = $data['pregunta'] ?? null;
$esSST = ($data['slug_modulo'] ?? '') === 'sst';
$padresDisponibles = $data['padres_disponibles'] ?? [];
?>
<div class="modal fade" id="modalPregunta" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= $pregunta ? 'Editar' : 'Nueva' ?> Pregunta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formPregunta">
          <input type="hidden" name="id_pregunta" value="<?= $pregunta['id_pregunta'] ?? 0 ?>">
          <input type="hidden" name="id_seccion" value="<?= $seccion['id_seccion'] ?? 0 ?>">

          <div class="row g-2 mb-3">
            <div class="col-md-4">
              <label class="form-label">Codigo interno</label>
              <input type="text" name="codigo_interno" class="form-control"
                     value="<?= htmlspecialchars($pregunta['codigo_interno'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipo respuesta</label>
              <select name="tipo_respuesta" class="form-select" id="selTipoRespuesta">
                <option value="SI_NO" <?= ($pregunta['tipo_respuesta'] ?? '') === 'SI_NO' ? 'selected' : '' ?>>Si / No</option>
                <option value="SI_NO_NA" <?= ($pregunta['tipo_respuesta'] ?? '') === 'SI_NO_NA' ? 'selected' : '' ?>>Si / No / N/A</option>
                <?php if ($esSST): ?>
                <option value="TEXTO" <?= ($pregunta['tipo_respuesta'] ?? '') === 'TEXTO' ? 'selected' : '' ?>>Texto</option>
                <option value="TEXTO_LARGO" <?= ($pregunta['tipo_respuesta'] ?? '') === 'TEXTO_LARGO' ? 'selected' : '' ?>>Texto largo</option>
                <option value="FECHA" <?= ($pregunta['tipo_respuesta'] ?? '') === 'FECHA' ? 'selected' : '' ?>>Fecha</option>
                <option value="HORA" <?= ($pregunta['tipo_respuesta'] ?? '') === 'HORA' ? 'selected' : '' ?>>Hora</option>
                <option value="NUMERO" <?= ($pregunta['tipo_respuesta'] ?? '') === 'NUMERO' ? 'selected' : '' ?>>Numero</option>
                <option value="TELEFONO" <?= ($pregunta['tipo_respuesta'] ?? '') === 'TELEFONO' ? 'selected' : '' ?>>Telefono</option>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Orden</label>
              <input type="number" name="orden" class="form-control"
                     value="<?= (int) ($pregunta['orden'] ?? 0) ?>" min="0">
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <div class="form-check">
                <input type="checkbox" name="requerido" class="form-check-input" value="1"
                       id="chkRequerido" <?= ($pregunta['requerido'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="chkRequerido">Requerido</label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Texto de la pregunta</label>
            <textarea name="texto_pregunta" class="form-control" rows="2" required><?=
                htmlspecialchars($pregunta['texto_pregunta'] ?? '') ?></textarea>
          </div>

          <!-- Campos especificos de PREOP -->
          <div class="preop-fields bg-light p-3 rounded mb-3" <?= $esSST ? 'style="display:none"' : '' ?>>
            <strong><i class="fas fa-truck me-1"></i> Campos Preoperacional</strong>
            <div class="row g-2 mt-2">
              <div class="col-md-4">
                <label class="form-label">Respuesta esperada</label>
                <select name="respuesta_esperada" class="form-select">
                  <option value="">—</option>
                  <option value="1" <?= ($pregunta['respuesta_esperada'] ?? '') === '1' ? 'selected' : '' ?>>Si (1)</option>
                  <option value="2" <?= ($pregunta['respuesta_esperada'] ?? '') === '2' ? 'selected' : '' ?>>No (2)</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                  <input type="checkbox" name="requiere_foto_si_negativa" class="form-check-input" value="1"
                         id="chkFoto" <?= ($pregunta['requiere_foto_si_negativa'] ?? 0) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="chkFoto">Requiere foto si negativa</label>
                </div>
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                  <input type="checkbox" name="genera_bloqueo" class="form-check-input" value="1"
                         id="chkBloqueo" <?= ($pregunta['genera_bloqueo'] ?? 0) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="chkBloqueo">Genera bloqueo</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Campos especificos de SST -->
          <div class="sst-fields bg-light p-3 rounded mb-3" <?= !$esSST ? 'style="display:none"' : '' ?>>
            <strong><i class="fas fa-shield-alt me-1"></i> Campos SST</strong>
            <div class="row g-2 mt-2">
              <div class="col-md-6">
                <label class="form-label">Pregunta padre (condicional)</label>
                <select name="id_pregunta_padre" class="form-select">
                  <option value="">— Sin condicional —</option>
                  <?php foreach ($padresDisponibles as $padre): ?>
                  <option value="<?= $padre['id_pregunta'] ?>"
                    <?= ($pregunta['id_pregunta_padre'] ?? '') == $padre['id_pregunta'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($padre['codigo_interno'] . ' — ' . $padre['texto_pregunta']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Valor que activa</label>
                <select name="valor_padre" class="form-select">
                  <option value="">—</option>
                  <option value="si" <?= ($pregunta['valor_padre'] ?? '') === 'si' ? 'selected' : '' ?>>Si</option>
                  <option value="no" <?= ($pregunta['valor_padre'] ?? '') === 'no' ? 'selected' : '' ?>>No</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Placeholder</label>
                <input type="text" name="placeholder" class="form-control"
                       value="<?= htmlspecialchars($pregunta['placeholder'] ?? '') ?>">
              </div>
            </div>
            <div class="mt-2">
              <label class="form-label">Texto de ayuda</label>
              <textarea name="ayuda" class="form-control" rows="2"><?=
                  htmlspecialchars($pregunta['ayuda'] ?? '') ?></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarPregunta">Guardar</button>
      </div>
    </div>
  </div>
</div>
