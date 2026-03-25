<form id="formVacaciones">
    <input type="hidden" name="accion" value="guardar_vacaciones">
    <div class="mb-3">
        <label>Operario</label>
        <select name="operario" id="vac_operario" class="form-select" required></select>
    </div>
    <div class="row">
        <div class="col-md-6">
            <label>Fecha inicio</label>
            <input type="date" name="fecha_ini" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label>Fecha fin</label>
            <input type="date" name="fecha_fin" class="form-control" required>
        </div>
    </div>
</form>