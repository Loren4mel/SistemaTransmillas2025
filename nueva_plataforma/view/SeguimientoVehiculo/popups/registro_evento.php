<form id="formRegistroEvento" onsubmit="return guardarRegistroEvento(event)">
    <input type="hidden" name="accion" value="registrar_evento">
    <input type="hidden" name="id_vehiculo" value="<?= $vehiculo['idvehiculos'] ?>">

    <div class="alert alert-info">
        <strong>Vehículo:</strong> <?= htmlspecialchars($vehiculo['veh_placa']) ?> —
        <?= htmlspecialchars($vehiculo['veh_marca'] . ' ' . $vehiculo['veh_modelo']) ?>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Tipo de Evento <span class="text-danger">*</span></label>
            <select name="tipo_evento" class="form-select" required>
                <option value="PREOPERACIONAL">Preoperacional</option>
                <option value="MANTENIMIENTO">Mantenimiento</option>
                <option value="TRASLADO_ADMIN">Traslado Administrativo</option>
                <option value="POST_SINIESTRO">Post-Siniestro</option>
                <option value="REVISION_SST">Revisión SST</option>
                <option value="OTRO">Otro</option>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Estado General</label>
            <select name="estado_general" class="form-select">
                <option value="OPTIMO">Óptimo</option>
                <option value="CON_NOVEDADES">Con Novedades</option>
                <option value="FUERA_DE_SERVICIO">Fuera de Servicio</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Conductor</label>
            <select name="id_conductor" class="form-select">
                <option value="0">Sin conductor</option>
                <?php
                $conductores = $modelo->getConductores();
                foreach ($conductores as $c):
                    $sel = ($c['idusuarios'] == ($vehiculo['conductor_id'] ?? 0)) ? 'selected' : '';
                ?>
                    <option value="<?= $c['idusuarios'] ?>" <?= $sel ?>><?= htmlspecialchars($c['usu_nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Kilometraje</label>
            <input type="number" name="kilometraje" class="form-control" placeholder="Kilometraje del odómetro">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Ubicación</label>
        <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Sede principal, Taller X, Ruta...">
    </div>

    <div class="mb-3">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" class="form-control" rows="3" placeholder="Detalle del evento (opcional)"></textarea>
    </div>

    <div class="text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-info">Registrar Evento</button>
    </div>
</form>
