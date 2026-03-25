<form id="popupForm" method="post">
    <input type="hidden" name="accion" value="guardar_retorno_oficina">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="mb-3">
        <label for="hora" class="form-label">Retorno a oficina</label>
        <input type="time" name="hora" id="hora" class="form-control" value="<?= htmlspecialchars($hora_actual ?? '') ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
</form>