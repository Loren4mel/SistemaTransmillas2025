<?php
// Variables esperadas:
// $idUsuario, $idSeguimiento, $fecha, $motivos (array), $zonas, $sedePredeterminada, $sedes (para manual)
// $motivoSeleccionado, $descripcion, $zonaSeleccionada, $pruebaSeleccionada, $usuario (opcional)
$fecha = $fecha ?? date('Y-m-d');
$motivoSeleccionado = $motivoSeleccionado ?? '';
$descripcion = $descripcion ?? '';
$zonaSeleccionada = $zonaSeleccionada ?? 0;
$pruebaSeleccionada = $pruebaSeleccionada ?? 'No aplica';
?>
<form id="popupForm" method="post" enctype="multipart/form-data">
    <input type="hidden" name="accion" value="guardar_ingreso_popup">
    <input type="hidden" name="id_seguimiento" value="<?= $idSeguimiento ?? 0 ?>">

    <?php if (isset($idUsuario) && $idUsuario > 0): ?>
        <!-- Modo edición: operario fijo -->
        <input type="hidden" name="operario" value="<?= $idUsuario ?>">
        <div class="mb-3">
            <label class="form-label">Operario</label>
            <p class="form-control-plaintext"><?= htmlspecialchars($usuario['usu_nombre'] ?? '') ?></p>
        </div>
        <div class="mb-3">
            <label class="form-label">Sede</label>
            <p class="form-control-plaintext"><?= htmlspecialchars($sedeNombre) ?></p>
        </div>
    <?php else: ?>
        <!-- Modo manual: seleccionar sede y operario -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="ing_sede" class="form-label">Sede</label>
                <select name="sede" id="ing_sede" class="form-select" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($sedes as $s): ?>
                        <option value="<?= $s['idsedes'] ?>" <?= $s['idsedes'] == $sedePredeterminada ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['sed_nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="ing_operario" class="form-label">Operario</label>
                <select name="operario" id="ing_operario" class="form-select" required>
                    <option value="">Primero seleccione sede</option>
                </select>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="fecha" class="form-label">Fecha de ingreso</label>
            <input type="date" name="fecha" id="fecha" class="form-control" value="<?= $fecha ?>" required>
        </div>
        <div class="col-md-6">
            <label for="motivo" class="form-label">Motivo</label>
            <select name="motivo" id="motivo" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($motivos as $key => $value): ?>
                    <option value="<?= $key ?>" <?= $key == $motivoSeleccionado ? 'selected' : '' ?>><?= $value ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="zona" class="form-label">Zona de trabajo</label>
            <select name="zona" id="ing_zona" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($zonas as $z): ?>
                    <option value="<?= $z['idzonatrabajo'] ?>" <?= $z['idzonatrabajo'] == $zonaSeleccionada ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z['zon_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="prueba" class="form-label">Prueba de alcohol</label>
            <select name="prueba" id="prueba" class="form-select" required>
                <option value="No aplica" <?= $pruebaSeleccionada == 'No aplica' ? 'selected' : '' ?>>No aplica</option>
                <option value="Negativo" <?= $pruebaSeleccionada == 'Negativo' ? 'selected' : '' ?>>Negativo</option>
                <option value="Positivo" <?= $pruebaSeleccionada == 'Positivo' ? 'selected' : '' ?>>Positivo</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" id="descripcion" class="form-control"
            rows="2"><?= htmlspecialchars($descripcion) ?></textarea>
    </div>

    <div class="mb-3">
        <label for="imagen" class="form-label">Imagen (opcional)</label>
        <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
</form>