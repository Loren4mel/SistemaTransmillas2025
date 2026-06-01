<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label fw-bold">Fecha Inicio</label>
        <input type="date" id="ne_fecha_inicio" class="form-control"
            value="<?= date('Y-m-d', strtotime('-14 days')) ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold">Fecha Fin</label>
        <input type="date" id="ne_fecha_fin" class="form-control"
            value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button type="button" class="btn btn-primary w-100" onclick="buscarNuevosEmpleados()">
            <i class="fas fa-search me-1"></i> Buscar
        </button>
    </div>
</div>

<div id="ne_resultados" class="table-responsive mt-3" style="display:none;">
    <hr>
    <h6 class="text-muted mb-2">Resultados: <span id="ne_total">0</span> empleados encontrados</h6>
    <table class="table table-bordered table-hover table-sm align-middle" style="width:100%">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Identificación</th>
                <th>Fecha Ingreso</th>
                <th>Días</th>
                <th>Tipo Contrato</th>
                <th>Sede</th>
                <th>Cargo</th>
                <th>Estado</th>
                <th>Alertas</th>
            </tr>
        </thead>
        <tbody id="ne_tbody"></tbody>
    </table>
</div>

<div id="ne_sin_resultados" class="alert alert-info mt-3" style="display:none;">
    <i class="fas fa-info-circle me-1"></i> No se encontraron empleados con fecha de ingreso en el rango seleccionado.
</div>

<div id="ne_error" class="alert alert-danger mt-3" style="display:none;"></div>

<script>
function buscarNuevosEmpleados() {
    var fechaInicio = $('#ne_fecha_inicio').val();
    var fechaFin = $('#ne_fecha_fin').val();

    if (!fechaInicio || !fechaFin) {
        $('#ne_error').text('Ambas fechas son obligatorias').show();
        $('#ne_resultados, #ne_sin_resultados').hide();
        return;
    }
    if (fechaFin < fechaInicio) {
        $('#ne_error').text('La fecha fin no puede ser anterior a la fecha inicio').show();
        $('#ne_resultados, #ne_sin_resultados').hide();
        return;
    }

    $('#ne_error').hide();
    $('#ne_resultados, #ne_sin_resultados').hide();
    $('#ne_tbody').html('<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-pulse"></i> Buscando...</td></tr>');
    $('#ne_resultados').show();

    $.get(window.dirPage, {
        accion: 'buscar_nuevos_empleados',
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    })
    .done(function (data) {
        if (data.error) {
            $('#ne_error').text(data.error).show();
            $('#ne_resultados, #ne_sin_resultados').hide();
            return;
        }
        if (!Array.isArray(data) || data.length === 0) {
            $('#ne_resultados').hide();
            $('#ne_sin_resultados').show();
            return;
        }
        var rows = '';
        data.forEach(function (emp) {
            var alertaClass = emp.alertas_texto ? 'text-danger fw-bold' : 'text-success';
            var alertaTexto = emp.alertas_texto || 'Ninguna';
            rows += '<tr>' +
                '<td>' + (emp.usu_nombre || '') + '</td>' +
                '<td>' + (emp.usu_identificacion || '') + '</td>' +
                '<td>' + (emp.hoj_fechaingreso_fmt || '') + '</td>' +
                '<td class="text-center">' + (emp.dias_desde_ingreso !== null ? emp.dias_desde_ingreso : '') + '</td>' +
                '<td>' + (emp.usu_tipocontrato || '') + '</td>' +
                '<td>' + (emp.sed_nombre || '') + '</td>' +
                '<td>' + (emp.hoj_cargo || '') + '</td>' +
                '<td class="' + (emp.hoj_estado_activo ? 'text-success fw-bold' : 'text-danger fw-bold') + '">' + (emp.hoj_estado_fmt || '') + '</td>' +
                '<td class="' + alertaClass + '">' + alertaTexto + '</td>' +
                '</tr>';
        });
        document.getElementById('ne_tbody').innerHTML = rows;
        $('#ne_total').text(data.length);
        $('#ne_resultados').show();
        $('#ne_sin_resultados').hide();
    })
    .fail(function () {
        $('#ne_error').text('Error de comunicación con el servidor').show();
        $('#ne_resultados, #ne_sin_resultados').hide();
    });
}
</script>
