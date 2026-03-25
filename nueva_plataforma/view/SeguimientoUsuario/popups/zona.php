<?php if (empty($zonas)): ?>
    <div class="alert alert-warning">
        No hay zonas disponibles para esta sede. Contacte al administrador.
    </div>
<?php else: ?>
    <form id="popupForm" method="post">
        <input type="hidden" name="accion" value="guardar_zona">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="mb-3">
            <label for="zona" class="form-label">Zona de trabajo</label>
            <select name="zona" id="zona" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($zonas as $z): ?>
                    <option value="<?= $z['idzonatrabajo'] ?>" <?= isset($zona_seleccionada) && $zona_seleccionada == $z['idzonatrabajo'] ? 'selected' : '' ?>><?= htmlspecialchars($z['zon_nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
<?php endif; ?>