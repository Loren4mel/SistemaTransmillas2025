<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nomina</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.3.2/css/fixedHeader.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/usuarios.css">
    <style>
        body { background: #f6f8fb; }
        .mi-header {
            background-color: #00458D;
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .module-shell {
            background: white;
            border: 1px solid #dde3ea;
            border-radius: 8px;
        }
        .filters-band {
            background: #f8fafc;
            border-bottom: 1px solid #e4e9f0;
        }
        .period-pill {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 0 12px;
            border: 1px solid #d5dde8;
            border-radius: 6px;
            background: white;
            color: #26394f;
            font-weight: 600;
        }
        table.dataTable tbody td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="module-shell shadow-sm">
        <div class="mi-header d-flex align-items-center justify-content-between px-3 py-3">
            <button type="button" class="btn btn-light" onclick="history.back()">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </button>
            <h3 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Nomina</h3>
            <span class="badge text-bg-light">Modulo nuevo</span>
        </div>

        <div class="filters-band px-3 py-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label for="anio" class="form-label">Ano</label>
                    <input type="number" id="anio" class="form-control" value="<?= htmlspecialchars((string) $anioActual) ?>" min="2020" max="2100">
                </div>
                <div class="col-6 col-md-2">
                    <label for="mes" class="form-label">Mes</label>
                    <select id="mes" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === $mesActual ? 'selected' : '' ?>>
                                <?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="quincena" class="form-label">Quincena</label>
                    <select id="quincena" class="form-select">
                        <option value="Primera" selected>Primera</option>
                        <option value="Segunda">Segunda</option>
                        <option value="Completo">Completo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tipoContrato" class="form-label">Contrato</label>
                    <select id="tipoContrato" class="form-select">
                        <?php foreach ($tiposContrato as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo['id']) ?>" <?= $tipo['id'] === 'Empresa' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sede" class="form-label">Sede</label>
                    <select id="sede" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($sedes as $sede): ?>
                            <option value="<?= (int) $sede['idsedes'] ?>"><?= htmlspecialchars($sede['sed_nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="usuario" class="form-label">Empleado</label>
                    <select id="usuario" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= (int) $usuario['idusuarios'] ?>">
                                <?= htmlspecialchars($usuario['usu_nombre'] . ' - ' . $usuario['usu_identificacion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <span class="period-pill" id="periodoTexto">
                        <?= htmlspecialchars($periodoInicial['inicio_sin_tiempo'] . ' / ' . $periodoInicial['fin_sin_tiempo']) ?>
                    </span>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnFiltrar" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>

        <div class="p-3">
            <div class="table-responsive">
                <table id="tablaNomina" class="table table-hover table-bordered align-middle text-center w-100">
                    <thead class="table-primary">
                        <tr>
                            <th>Id</th>
                            <th>Trabajador</th>
                            <th>Contrato</th>
                            <th>Cedula</th>
                            <th>Cargo</th>
                            <th>Salario</th>
                            <th>Auxilio</th>
                            <th>Otros</th>
                            <th>Dias trabajados</th>
                            <th>Descansos</th>
                            <th>No trabajo</th>
                            <th>Incapacidades</th>
                            <th>Horas normales</th>
                            <th>Horas festivas</th>
                            <th>Sede</th>
                            <th>Inicio contrato</th>
                            <th>Termina contrato</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
const dirPage = 'NominaController.php';

function formatoMoneda(valor) {
    const numero = Number(valor || 0);
    return '$' + numero.toLocaleString('es-CO', { maximumFractionDigits: 0 });
}

const tablaNomina = $('#tablaNomina').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    ajax: {
        url: dirPage,
        type: 'POST',
        data: function (d) {
            d.ajax = 1;
            d.anio = $('#anio').val();
            d.mes = $('#mes').val();
            d.quincena = $('#quincena').val();
            d.tipo_contrato = $('#tipoContrato').val();
            d.sede = $('#sede').val();
            d.usuario = $('#usuario').val();
        },
        dataSrc: function (json) {
            if (json.periodo) {
                $('#periodoTexto').text(json.periodo.inicio_sin_tiempo + ' / ' + json.periodo.fin_sin_tiempo);
            }
            return json.data || [];
        }
    },
    columns: [
        { data: 'idusuario' },
        { data: 'trabajador' },
        { data: 'tipo_contrato' },
        { data: 'cedula' },
        { data: 'cargo', defaultContent: '' },
        { data: 'salario', render: formatoMoneda },
        { data: 'auxilio', render: formatoMoneda },
        { data: 'otros', render: formatoMoneda },
        { data: 'dias_trabajados' },
        { data: 'descansos' },
        { data: 'dias_no_trabajados' },
        { data: 'incapacidades' },
        { data: 'horas_normales' },
        { data: 'horas_festivas' },
        { data: 'sede' },
        { data: 'inicio_contrato' },
        { data: 'termina_contrato', defaultContent: '' },
        { data: 'estado', defaultContent: '' }
    ],
    order: [[1, 'asc']],
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    }
});

$('#btnFiltrar').on('click', function () {
    tablaNomina.ajax.reload();
});

$('#anio, #mes, #quincena, #tipoContrato, #sede, #usuario').on('change', function () {
    tablaNomina.ajax.reload();
});
</script>
</body>
</html>
