<?php
/**
 * Vista previa: Muestra el cuestionario como lo ve el conductor
 */
$data = $data ?? [];
$preview = $data['preview'] ?? null;
$error = $data['error'] ?? '';
$esSST = ($preview['slug_modulo'] ?? '') === 'sst';
$esSeccion = isset($preview['preguntas']) && !isset($preview['secciones']);
?>
<div class="modal fade" id="modalPreview" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-eye me-1"></i> Vista previa
          <?php if ($preview): ?>
            <small class="text-muted ms-2">
              <?= htmlspecialchars($preview['nombre_base'] ?? $preview['slug_modulo'] ?? '') ?>
              — <?= htmlspecialchars($preview['numero_version'] ?? '') ?>
              <?php if (isset($preview['nombre'])): ?>
                / <?= htmlspecialchars($preview['nombre']) ?>
              <?php endif; ?>
            </small>
          <?php endif; ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
<?php if ($error): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
<?php elseif (!$preview): ?>
        <div class="alert alert-info">No hay datos para mostrar</div>
<?php elseif ($esSeccion): ?>
        <?php $preguntas = $preview['preguntas']; ?>
        <div class="preview-section mb-3">
          <h6 class="border-bottom pb-2 mb-3">
            <i class="fas fa-folder-open me-1"></i> <?= htmlspecialchars($preview['nombre']) ?>
            <?php if ($preview['descripcion'] ?? ''): ?>
              <small class="text-muted d-block"><?= htmlspecialchars($preview['descripcion']) ?></small>
            <?php endif; ?>
          </h6>
          <?php foreach ($preguntas as $p): ?>
          <div class="preview-question mb-3 p-3 bg-light rounded">
            <div class="d-flex justify-content-between align-items-start">
              <div class="flex-grow-1 me-2">
                <code class="text-muted small"><?= htmlspecialchars($p['codigo_interno']) ?></code>
                <span class="ms-1"><?= htmlspecialchars($p['texto_pregunta']) ?></span>
                <?php if ($p['requiere_foto_si_negativa']): ?><span class="badge bg-warning text-dark ms-1">📷</span><?php endif; ?>
                <?php if ($p['genera_bloqueo']): ?><span class="badge bg-danger ms-1">🚫</span><?php endif; ?>
                <?php if ($p['id_pregunta_padre']): ?><span class="badge bg-success ms-1">🔀</span><?php endif; ?>
              </div>
              <span class="badge bg-<?= in_array($p['tipo_respuesta'], ['SI_NO','SI_NO_NA']) ? 'primary' : 'secondary' ?>"><?= $p['tipo_respuesta'] ?></span>
            </div>
            <div class="mt-2">
              <?php if ($p['tipo_respuesta'] === 'SI_NO'): ?>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Si</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled checked><label class="form-check-label">No</label></div>
                <span class="text-muted small ms-2">(respuesta esperada: <?= $p['respuesta_esperada'] === '1' ? 'Si' : ($p['respuesta_esperada'] === '2' ? 'No' : '—') ?>)</span>
              <?php elseif ($p['tipo_respuesta'] === 'SI_NO_NA'): ?>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Si</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled><label class="form-check-label">No</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled checked><label class="form-check-label">N/A</label></div>
              <?php elseif (in_array($p['tipo_respuesta'], ['TEXTO','TELEFONO'])): ?>
                <input type="text" class="form-control form-control-sm" disabled placeholder="<?= htmlspecialchars($p['placeholder'] ?? 'Respuesta...') ?>">
              <?php elseif ($p['tipo_respuesta'] === 'TEXTO_LARGO'): ?>
                <textarea class="form-control form-control-sm" rows="2" disabled placeholder="<?= htmlspecialchars($p['placeholder'] ?? 'Descripcion...') ?>"></textarea>
              <?php elseif ($p['tipo_respuesta'] === 'NUMERO'): ?>
                <input type="number" class="form-control form-control-sm" disabled placeholder="<?= htmlspecialchars($p['valor_padre'] ?? '0') ?>">
              <?php elseif ($p['tipo_respuesta'] === 'FECHA'): ?>
                <input type="date" class="form-control form-control-sm" disabled>
              <?php elseif ($p['tipo_respuesta'] === 'HORA'): ?>
                <input type="time" class="form-control form-control-sm" disabled>
              <?php endif; ?>
            </div>
            <?php if ($p['ayuda'] ?? ''): ?>
              <div class="small text-muted mt-1"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($p['ayuda']) ?></div>
            <?php endif; ?>
            <?php if ($p['requiere_foto_si_negativa']): ?>
              <div class="mt-1 text-warning small"><i class="fas fa-camera"></i> Se requiere foto si la respuesta es NO</div>
            <?php endif; ?>
            <?php if ($p['genera_bloqueo']): ?>
              <div class="mt-1 text-danger small"><i class="fas fa-ban"></i> Bloquea el envio del formulario</div>
            <?php endif; ?>
            <?php if ($p['id_pregunta_padre']): ?>
              <div class="mt-1 text-success small"><i class="fas fa-code-branch"></i> Condicional: se muestra segun respuesta de pregunta padre</div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
<?php elseif (isset($preview['secciones'])): ?>
        <?php foreach ($preview['secciones'] as $sec):
          $preguntas = $sec['preguntas'] ?? []; ?>
        <div class="preview-section mb-4">
          <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-folder-open me-1"></i> <?= htmlspecialchars($sec['nombre']) ?></h6>
          <?php foreach ($preguntas as $p): ?>
          <div class="preview-question mb-3 p-3 bg-light rounded">
            <div class="d-flex justify-content-between align-items-start">
              <div><code class="text-muted small"><?= htmlspecialchars($p['codigo_interno']) ?></code> <span class="ms-1"><?= htmlspecialchars($p['texto_pregunta']) ?></span></div>
              <span class="badge bg-<?= in_array($p['tipo_respuesta'], ['SI_NO','SI_NO_NA']) ? 'primary' : 'secondary' ?>"><?= $p['tipo_respuesta'] ?></span>
            </div>
            <div class="mt-2">
              <?php if ($p['tipo_respuesta'] === 'SI_NO'): ?>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Si</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled checked><label class="form-check-label">No</label></div>
              <?php elseif ($p['tipo_respuesta'] === 'SI_NO_NA'): ?>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Si</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled><label class="form-check-label">No</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled checked><label class="form-check-label">N/A</label></div>
              <?php else: ?>
                <input type="text" class="form-control form-control-sm" disabled placeholder="<?= htmlspecialchars($p['placeholder'] ?? '') ?>">
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
<?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
      </div>
    </div>
  </div>
</div>
