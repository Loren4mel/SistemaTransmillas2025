<?php if (empty($operarios)): ?>
    <div class="alert alert-warning">
        No hay operarios disponibles.
    </div>
<?php else: ?>
    <form id="popupForm" method="post">
        <input type="hidden" name="accion" value="guardar_companero">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="mb-3">
            <label for="companero" class="form-label">Compañero</label>
            <select name="companero" id="companero" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($operarios as $o): ?>
                    <option value="<?= $o['idusuarios'] ?>" <?= isset($companero_seleccionado) && $companero_seleccionado == $o['idusuarios'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($o['usu_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
<?php endif; ?>