<form id="formCambiarEstado" onsubmit="return guardarCambiarEstado(event)">
    <input type="hidden" name="accion" value="cambiar_estado">
    <input type="hidden" name="id_vehiculo" value="<?= $vehiculo['idvehiculos'] ?>">

    <div class="alert alert-info">
        <strong>Vehículo:</strong> <?= htmlspecialchars($vehiculo['veh_placa']) ?> —
        <?= htmlspecialchars($vehiculo['veh_marca'] . ' ' . $vehiculo['veh_modelo']) ?>
        <?php if (!empty($vehiculo['conductor_nombre'])): ?>
            | <strong>Conductor:</strong> <?= htmlspecialchars($vehiculo['conductor_nombre']) ?>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label">Nuevo Estado General <span class="text-danger">*</span></label>
        <select name="estado_general" id="cambiar_estado_general" class="form-select" required>
            <option value="OPTIMO">ÓPTIMO — Vehículo en condiciones normales</option>
            <option value="CON_NOVEDADES">CON NOVEDADES — Requiere revisión o mantenimiento</option>
            <option value="FUERA_DE_SERVICIO">FUERA DE SERVICIO — No operativo</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Kilometraje Actual</label>
        <input type="number" name="kilometraje" class="form-control" placeholder="Kilometraje del odómetro"
               value="<?= htmlspecialchars($vehiculo['veh_kilactual'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Observaciones <span class="text-danger">*</span></label>
        <textarea name="observaciones" class="form-control" rows="3" required
                  placeholder="Describa el motivo del cambio de estado"></textarea>
    </div>

    <div class="text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning">Cambiar Estado</button>
    </div>
</form>
