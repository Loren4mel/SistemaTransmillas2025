<form id="formFestivos">
    <input type="hidden" name="accion" value="guardar_festivos">
    <div class="mb-3">
        <label>Fecha</label>
        <input type="date" name="fecha" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Sede (opcional, si se quiere restringir)</label>
        <select name="sede" class="form-select">
            <option value="">Todas las sedes</option>
            <?php foreach ($sedes as $s): ?>
                <option value="<?= $s['idsedes'] ?>"><?= htmlspecialchars($s['sed_nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>