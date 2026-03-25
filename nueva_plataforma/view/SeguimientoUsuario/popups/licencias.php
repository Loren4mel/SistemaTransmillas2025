<form id="formLicencias">
    <input type="hidden" name="accion" value="guardar_licencia">
    <div class="row g-3">
        <div class="col-md-6">
            <label>Operario</label>
            <select name="operario" id="lic_operario" class="form-select" required></select>
        </div>
        <div class="col-md-3">
            <label>Fecha inicio</label>
            <input type="date" name="fecha_ini" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label>Fecha fin</label>
            <input type="date" name="fecha_fin" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label>Motivo</label>
            <select name="motivo" class="form-select" required>
                <?php foreach ($motivosLicencia as $m): ?>
                    <option value="<?= $m['mot_nombre'] ?>"><?= htmlspecialchars($m['mot_nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label>Descripción</label>
            <textarea name="descripcion" class="form-control" rows="2"></textarea>
        </div>
    </div>
</form>