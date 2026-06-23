<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Primas</title>
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
        .prima-total {
            background: #ecfdf3;
            color: #05603a;
            border: 1px solid #abefc6;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 700;
        }
        .estado-usuario {
            min-width: 125px;
        }
        .acciones-prima {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            justify-content: center;
            min-width: 160px;
        }
        .acciones-prima .btn {
            white-space: nowrap;
            padding-left: 8px;
            padding-right: 8px;
        }
        .prima-estado-mini {
            margin-top: 6px;
            font-size: 11px;
            line-height: 1.25;
            color: #667085;
        }
        .estado-firma-prima {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #0f8f3d;
            font-weight: 700;
        }
        .estado-firma-prima .bi {
            font-size: 16px;
        }
        .scroll-primas-top {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            height: 16px;
            margin-bottom: 8px;
            border: 1px solid #dde3ea;
            border-radius: 6px;
            background: #f8fafc;
        }
        .scroll-primas-top-inner {
            height: 1px;
        }
        .check-col {
            width: 42px;
        }
        .projection-check {
            border: 1px solid #d5dde8;
            border-radius: 6px;
            background: #fff;
            min-height: 38px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge-proyeccion {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 7px;
            border-radius: 999px;
            background: #fff4d6;
            color: #9a6700;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #f3d27a;
        }
        .btn-detalle-dias {
            border: 0;
            background: transparent;
            color: #0d6efd;
            font-weight: 700;
            padding: 0;
            text-decoration: underline;
            cursor: pointer;
        }
        .prima-dias .btn-detalle-dias,
        .prima-valor .btn-detalle-dias {
            color: inherit;
        }
        #tablaPrimas th.prima-dias,
        #tablaPrimas td.prima-dias {
            background-color: rgb(240, 230, 170) !important;
            color: #4a3b00;
            font-weight: 700;
        }
        #tablaPrimas th.prima-valor,
        #tablaPrimas td.prima-valor {
            background-color: rgb(183, 230, 190) !important;
            color: #064d1e;
            font-weight: 700;
        }
        #tablaPrimas th.dias-no-trabajados,
        #tablaPrimas td.dias-no-trabajados {
            color: #c1121f !important;
            font-weight: 700;
        }
        #tablaPrimas th.dias-sin-registro,
        #tablaPrimas td.dias-sin-registro {
            background-color: #f8d7da !important;
            color: #842029 !important;
            font-weight: 700;
        }
        #tablaPrimas tbody tr:hover td.prima-dias {
            background-color: rgb(236, 222, 139) !important;
        }
        #tablaPrimas tbody tr:hover td.prima-valor {
            background-color: rgb(164, 220, 174) !important;
        }
        #tablaPrimas tbody tr:hover td.dias-sin-registro {
            background-color: #f1bfc5 !important;
        }
        #tablaPrimas tbody tr.contrato-terminado > td {
            background-color: #fdecec !important;
            color: #7a271a;
        }
        #tablaPrimas tbody tr.contrato-terminado:hover > td {
            background-color: #f9d5d5 !important;
        }
        #tablaPrimas tbody tr.contrato-terminado > td.prima-dias {
            background-color: #f3d79f !important;
        }
        #tablaPrimas tbody tr.contrato-terminado > td.prima-valor {
            background-color: #bfe4c6 !important;
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
            <h3 class="mb-0"><i class="bi bi-cash-stack me-2"></i> Primas</h3>
            <span class="badge text-bg-light">Modulo nuevo</span>
        </div>

        <div class="filters-band px-3 py-3">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label for="anio" class="form-label">Ano</label>
                    <input type="number" id="anio" class="form-control" value="<?= htmlspecialchars((string) $anioActual) ?>" min="2024" max="2027">
                </div>
                <div class="col-6 col-md-2">
                    <label for="semestre" class="form-label">Semestre</label>
                    <select id="semestre" class="form-select">
                        <option value="Primera" <?= $semestreActual === 'Primera' ? 'selected' : '' ?>>Primero</option>
                        <option value="Segunda" <?= $semestreActual === 'Segunda' ? 'selected' : '' ?>>Segundo</option>
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
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Operario</label>
                    <select id="usuario" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= (int) $usuario['idusuarios'] ?>">
                                <?= htmlspecialchars($usuario['usu_nombre'] . ' - ' . $usuario['usu_identificacion']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select id="estado" class="form-select">
                        <?php foreach ($estados as $valorEstado => $textoEstado): ?>
                            <option value="<?= htmlspecialchars($valorEstado) ?>" <?= $valorEstado === $estadoInicial ? 'selected' : '' ?>>
                                <?= htmlspecialchars($textoEstado) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" id="btnFiltrar" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Proyeccion</label>
                    <label class="projection-check">
                        <input type="checkbox" id="completarPeriodo" value="1">
                        Completar dias hasta fin prima
                    </label>
                </div>
                <div class="col-md-3">
                    <span class="period-pill" id="periodoTexto">
                        <?= htmlspecialchars($periodoInicial['inicio_sin_tiempo'] . ' / ' . $periodoInicial['fin_sin_tiempo']) ?>
                    </span>
                </div>
                <div class="col-md-3">
                    <span class="prima-total" id="totalPrimas">Total primas: $0</span>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                        <input type="file" id="comprobante" class="form-control" style="max-width:260px" accept=".jpg,.jpeg,.png,.pdf,.webp">
                        <button type="button" id="btnCargarComprobante" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-1"></i> Cargar comprobante
                        </button>
                        <button type="button" id="btnExcelPago" class="btn btn-success">
                            <i class="fas fa-file-excel me-1"></i> Excel pagos
                        </button>
                        <button type="button" id="btnReporte" class="btn btn-outline-secondary">
                            Reporte de nomina
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3">
            <div id="scrollPrimasTop" class="scroll-primas-top" aria-label="Desplazamiento horizontal superior">
                <div id="scrollPrimasTopInner" class="scroll-primas-top-inner"></div>
            </div>
            <div class="table-responsive">
                <table id="tablaPrimas" class="table table-hover table-bordered align-middle text-center w-100">
                    <thead class="table-primary">
                        <tr>
                            <th>Id</th>
                            <th>Trabajador</th>
                            <th>Contrato</th>
                            <th>Cedula</th>
                            <th>Cargo</th>
                            <th>Salario mes</th>
                            <th>Auxilio</th>
                            <th>Ingresos</th>
                            <th>Descanso</th>
                            <th class="dias-no-trabajados">Dias no trabajados</th>
                            <th class="dias-sin-registro">Dias sin registro</th>
                            <th>Incapacidad empresa</th>
                            <th>Vacaciones</th>
                            <th>Licencias</th>
                            <th class="prima-dias">Total dias prima</th>
                            <th class="prima-valor">Total prima</th>
                            <th>Desprendible</th>
                            <th>Confirmado usuario</th>
                            <th>Pagado</th>
                            <th>Comprobante</th>
                            <th>Inicio contrato</th>
                            <th>Termina contrato</th>
                            <th class="check-col">
                                <input type="checkbox" id="checkTodos" title="Seleccionar todos">
                            </th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalleDias" tabindex="-1" aria-labelledby="modalDetalleDiasLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleDiasLabel">Detalle de dias</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="fw-bold" id="detalleDiasTrabajador"></div>
                    <small class="text-muted" id="detalleDiasPeriodo"></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Motivo</th>
                                <th>Descripcion</th>
                                <th>Horas</th>
                            </tr>
                        </thead>
                        <tbody id="detalleDiasBody">
                            <tr><td colspan="4" class="text-center text-muted">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <span class="me-auto text-muted" id="detalleDiasTotal"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
const dirPage = 'PrimasController.php';
let periodoActual = <?= json_encode($periodoInicial) ?>;
let seleccionados = new Map();

function formatoMoneda(valor) {
    const numero = Number(valor || 0);
    return '$' + numero.toLocaleString('es-CO', { maximumFractionDigits: 0 });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function datosFiltro() {
    return {
        anio: $('#anio').val(),
        semestre: $('#semestre').val(),
        sede: $('#sede').val(),
        usuario: $('#usuario').val(),
        estado: $('#estado').val(),
        completar_periodo: $('#completarPeriodo').is(':checked') ? '1' : '0'
    };
}

function refrescarChecks() {
    $('.check-prima').each(function () {
        const id = Number($(this).val());
        this.checked = seleccionados.has(id);
    });
}

function postAccion(data, onSuccess) {
    $.ajax({
        url: dirPage,
        type: 'POST',
        data,
        dataType: 'json'
    }).done(function (response) {
        if (!response.success) {
            alert(response.message || 'No fue posible completar la accion.');
            return;
        }
        if (typeof onSuccess === 'function') {
            onSuccess(response);
        }
    }).fail(function (xhr) {
        const msg = xhr.responseJSON?.message || xhr.responseText || 'No fue posible completar la accion.';
        alert(msg);
    });
}

function obtenerFilasSalida(callback) {
    if (seleccionados.size > 0) {
        callback([...seleccionados.values()]);
        return;
    }

    const request = Object.assign({
        ajax: 1,
        draw: 1,
        start: 0,
        length: 10000,
        search: { value: tablaPrimas.search() },
        order: [{ column: 1, dir: 'asc' }],
        columns: tablaPrimas.settings()[0].aoColumns.map(col => ({ data: col.data || '' }))
    }, datosFiltro());

    $.ajax({
        url: dirPage,
        type: 'POST',
        data: request,
        dataType: 'json'
    }).done(function (json) {
        callback(json.data || []);
    }).fail(function () {
        alert('No fue posible preparar los datos de salida.');
    });
}

function enviarPostNuevaVentana(action, campos) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.target = '_blank';

    Object.entries(campos).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    form.remove();
}

function botonDetalleDias(row, grupo, valor, etiqueta) {
    const numero = Number(valor || 0);
    if (numero <= 0) {
        return '0';
    }

    return `<button type="button" class="btn-detalle-dias" data-id="${row.idusuario}" data-grupo="${grupo}" data-etiqueta="${escapeHtml(etiqueta)}">${numero}</button>`;
}

function configurarScrollSuperiorPrimas() {
    const $scrollTop = $('#scrollPrimasTop');
    const $scrollTopInner = $('#scrollPrimasTopInner');
    const $scrollBody = $('#tablaPrimas_wrapper .dataTables_scrollBody');

    if (!$scrollBody.length) {
        return;
    }

    const scrollBody = $scrollBody.get(0);
    const anchoContenido = scrollBody.scrollWidth;
    const anchoVisible = $scrollBody.innerWidth();

    $scrollTopInner.width(anchoContenido);
    $scrollTop.toggle(anchoContenido > anchoVisible + 1);

    $scrollTop.off('scroll.primas').on('scroll.primas', function () {
        scrollBody.scrollLeft = this.scrollLeft;
    });

    $scrollBody.off('scroll.primasTop').on('scroll.primasTop', function () {
        $scrollTop.scrollLeft(this.scrollLeft);
    });

    $scrollTop.scrollLeft(scrollBody.scrollLeft);
}

const tablaPrimas = $('#tablaPrimas').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    pageLength: 100,
    lengthMenu: [[100, 200, 500, -1], [100, 200, 500, 'Todos']],
    ajax: {
        url: dirPage,
        type: 'POST',
        data: function (d) {
            Object.assign(d, datosFiltro());
            d.ajax = 1;
        },
        dataSrc: function (json) {
            if (json.periodo) {
                periodoActual = json.periodo;
                $('#periodoTexto').text(json.periodo.inicio_sin_tiempo + ' / ' + json.periodo.fin_sin_tiempo);
            }
            $('#totalPrimas').text('Total primas: ' + formatoMoneda(json.total_prima || 0));
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
        { data: null, render: row => botonDetalleDias(row, 'ingresos', row.ingresos, 'Ingresos') },
        { data: null, render: row => botonDetalleDias(row, 'descanso', row.descanso, 'Descanso') },
        { data: null, className: 'dias-no-trabajados', render: row => botonDetalleDias(row, 'dias_no_trabajados', row.dias_no_trabajados, 'Dias no trabajados') },
        { data: null, className: 'dias-sin-registro', render: row => botonDetalleDias(row, 'sin_registro', row.dias_sin_registro, 'Dias sin registro') },
        { data: null, render: row => botonDetalleDias(row, 'incapacidad_empresa', row.incapacidad_empresa, 'Incapacidad empresa') },
        { data: null, render: row => botonDetalleDias(row, 'vacaciones', row.vacaciones, 'Vacaciones') },
        { data: null, render: row => botonDetalleDias(row, 'licencias', row.licencias, 'Licencias') },
        {
            data: null,
            className: 'prima-dias',
            render: function (row) {
                const dias = botonDetalleDias(row, 'total', row.total_dias_prima, 'Total dias prima');
                const proyectados = Number(row.dias_proyectados || 0);
                if (proyectados <= 0) {
                    return dias;
                }
                return `${dias}<br><span class="badge-proyeccion">+${proyectados} proyectados</span>`;
            }
        },
        { data: 'total_prima', className: 'prima-valor', render: formatoMoneda },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function (row) {
                const liquidadaInfo = row.liquidada && row.fecha_liquidacion
                    ? `<small class="d-block text-muted">Liquidada: ${escapeHtml(row.fecha_liquidacion)}</small>`
                    : '';

                if (!row.liquidada) {
                    return `
                        <div class="acciones-prima">
                            <button type="button" class="btn btn-sm btn-warning btn-liquidar-prima" data-id="${row.idusuario}">
                                Liquidar prima
                            </button>
                        </div>
                    `;
                }

                return `
                    <div class="acciones-prima">
                        <a class="btn btn-sm btn-outline-primary" href="${escapeHtml(row.ruta_desprendible)}" target="_blank">Ver</a>
                        <button type="button" class="btn btn-sm btn-primary btn-guardar-desprendible" data-id="${row.idusuario}">
                            Enviar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning btn-reliquidar-prima" data-id="${row.idusuario}">
                            Reliquidar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-liquidacion" data-id="${row.idusuario}">
                            Eliminar
                        </button>
                    </div>
                    <div class="prima-estado-mini">
                        ${liquidadaInfo}
                    </div>
                `;
            }
        },
        {
            data: 'confirmado_usuario',
            defaultContent: 'Pendiente',
            render: function (value, type, row) {
                const firmado = Boolean(row.firma_ruta || row.pdf_firmado_ruta);
                const texto = escapeHtml(value || 'Pendiente');
                if (!firmado) {
                    return texto;
                }
                return `<span class="estado-firma-prima"><i class="bi bi-check-circle-fill"></i>${texto}</span>`;
            }
        },
        {
            data: null,
            render: function (row) {
                const si = row.pago_confirmado === 'Si' ? 'selected' : '';
                const no = row.pago_confirmado !== 'Si' ? 'selected' : '';
                const color = row.pago_confirmado === 'Si' ? '#28B463' : '#8B0000';
                return `
                    <select class="form-select form-select-sm estado-usuario select-pago" data-id="${row.idusuario}" style="background:${color};color:#fff">
                        <option value="no" ${no}>NO</option>
                        <option value="Si" ${si}>SI</option>
                    </select>
                `;
            }
        },
        {
            data: 'comprobante',
            orderable: false,
            searchable: false,
            render: function (value) {
                if (!value) {
                    return '<span class="text-muted">Cargar</span>';
                }
                return `<a href="../../img_nomina/primas/${escapeHtml(value)}" target="_blank">Ver</a>`;
            }
        },
        { data: 'inicio_contrato' },
        { data: 'termina_contrato', defaultContent: '' },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function (row) {
                return `<input type="checkbox" class="check-prima" value="${row.idusuario}">`;
            }
        }
    ],
    order: [[1, 'asc']],
    rowCallback: function (row, data) {
        const fechaTermino = String(data.termina_contrato || '').trim();
        const contratoTerminado = fechaTermino !== '' && fechaTermino !== '0000-00-00' && fechaTermino !== '0000-00-00 00:00:00';
        $(row).toggleClass('contrato-terminado', contratoTerminado);
    },
    drawCallback: function () {
        refrescarChecks();
        configurarScrollSuperiorPrimas();
    },
    initComplete: configurarScrollSuperiorPrimas,
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    }
});

$(window).on('resize', configurarScrollSuperiorPrimas);

$('#btnFiltrar, #anio, #semestre, #sede, #usuario, #estado, #completarPeriodo').on('click change', function (event) {
    if (event.type === 'click' && this.id !== 'btnFiltrar') {
        return;
    }
    seleccionados.clear();
    $('#checkTodos').prop('checked', false);
    tablaPrimas.ajax.reload();
});

$('#tablaPrimas').on('change', '.check-prima', function () {
    const row = tablaPrimas.row($(this).closest('tr')).data();
    if (!row) {
        return;
    }
    if (this.checked) {
        seleccionados.set(Number(row.idusuario), row);
    } else {
        seleccionados.delete(Number(row.idusuario));
    }
});

$('#checkTodos').on('change', function () {
    const checked = this.checked;
    tablaPrimas.rows({ page: 'current' }).every(function () {
        const row = this.data();
        if (checked) {
            seleccionados.set(Number(row.idusuario), row);
        } else {
            seleccionados.delete(Number(row.idusuario));
        }
    });
    refrescarChecks();
});

$('#tablaPrimas').on('change', '.select-pago', function () {
    const row = tablaPrimas.row($(this).closest('tr')).data();
    const select = this;
    postAccion({
        accion: 'confirmar_pago',
        id_usuario: row.idusuario,
        fecha_inicio: row.fecha_inicio_prima,
        fecha_fin: row.fecha_fin_prima,
        semestre: row.semestre,
        confirma: $(select).val()
    }, function () {
        select.style.backgroundColor = $(select).val() === 'Si' ? '#28B463' : '#8B0000';
    });
});

$('#tablaPrimas').on('click', '.btn-detalle-dias', function () {
    const row = tablaPrimas.row($(this).closest('tr')).data();
    if (!row) {
        return;
    }

    const grupo = $(this).data('grupo');
    const etiqueta = $(this).data('etiqueta');
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleDias'));

    $('#modalDetalleDiasLabel').text('Detalle de ' + etiqueta);
    $('#detalleDiasTrabajador').text(row.trabajador + ' - ' + row.cedula);
    $('#detalleDiasPeriodo').text(row.fecha_inicio_calculo + ' / ' + row.fecha_fin_calculo);
    $('#detalleDiasTotal').text('');
    $('#detalleDiasBody').html('<tr><td colspan="4" class="text-center text-muted">Cargando...</td></tr>');
    modal.show();

    $.ajax({
        url: dirPage,
        type: 'POST',
        dataType: 'json',
        data: {
            accion: 'detalle_dias',
            id_usuario: row.idusuario,
            fecha_inicio: row.fecha_inicio_calculo,
            fecha_fin: row.fecha_fin_calculo,
            grupo: grupo,
            completar_periodo: $('#completarPeriodo').is(':checked') ? '1' : '0'
        }
    }).done(function (response) {
        const datos = response.data || [];
        $('#detalleDiasTotal').text('Total registros: ' + datos.length);

        if (datos.length === 0) {
            $('#detalleDiasBody').html('<tr><td colspan="4" class="text-center text-muted">No hay registros para esta columna.</td></tr>');
            return;
        }

        const html = datos.map(item => `
            <tr>
                <td>${escapeHtml(item.fecha)}</td>
                <td>${escapeHtml(item.motivo)}</td>
                <td>${escapeHtml(item.descripcion)}</td>
                <td>${escapeHtml(item.horas)}</td>
            </tr>
        `).join('');
        $('#detalleDiasBody').html(html);
    }).fail(function () {
        $('#detalleDiasBody').html('<tr><td colspan="4" class="text-center text-danger">No fue posible cargar el detalle.</td></tr>');
    });
});

function datosLiquidacion(row, accion) {
    return {
        accion: accion,
        id_usuario: row.idusuario,
        fecha_inicio: row.fecha_inicio_prima,
        fecha_fin: row.fecha_fin_prima,
        semestre: row.semestre,
        ruta_desprendible: row.ruta_desprendible_generada || row.ruta_desprendible,
        total_dias_prima: row.total_dias_prima,
        total_dias_prima_real: row.total_dias_prima_real,
        dias_proyectados: row.dias_proyectados,
        total_prima: row.total_prima,
        salario: row.salario,
        auxilio: row.auxilio
    };
}

$('#tablaPrimas').on('click', '.btn-liquidar-prima', function () {
    const row = tablaPrimas.row($(this).closest('tr')).data();
    if (!row) {
        return;
    }

    const texto = Number(row.dias_proyectados || 0) > 0
        ? 'Esta liquidacion incluye dias proyectados. Deseas liquidar esta prima?'
        : 'Deseas liquidar esta prima con el calculo actual?';

    if (!confirm(texto)) {
        return;
    }

    postAccion(datosLiquidacion(row, 'liquidar_prima'), function (response) {
        alert(response.message || 'Prima liquidada correctamente.');
        tablaPrimas.ajax.reload(null, false);
    });
});

$('#tablaPrimas').on('click', '.btn-reliquidar-prima', function () {
    const row = tablaPrimas.row($(this).closest('tr')).data();
    if (!row) {
        return;
    }

    if (!confirm('Deseas reemplazar la liquidacion guardada con el calculo actual?')) {
        return;
    }

    postAccion(datosLiquidacion(row, 'reliquidar_prima'), function (response) {
        alert(response.message || 'Prima reliquidada correctamente.');
        tablaPrimas.ajax.reload(null, false);
    });
});

$('#tablaPrimas').on('click', '.btn-eliminar-liquidacion', function () {
    const row = tablaPrimas.row($(this).closest('tr')).data();
    if (!row) {
        return;
    }

    if (!confirm('Deseas eliminar esta liquidacion de prima? Esta accion quitara el registro guardado.')) {
        return;
    }

    postAccion({
        accion: 'eliminar_liquidacion',
        id_usuario: row.idusuario,
        fecha_inicio: row.fecha_inicio_prima
    }, function (response) {
        alert(response.message || 'Liquidacion eliminada correctamente.');
        tablaPrimas.ajax.reload(null, false);
    });
});

$('#tablaPrimas').on('click', '.btn-guardar-desprendible', function () {
    const row = tablaPrimas.row($(this).closest('tr')).data();
    if (!confirm('Deseas enviar este desprendible de prima a Pendientes del usuario?')) {
        return;
    }

    postAccion({
        accion: 'guardar_desprendible',
        id_usuario: row.idusuario,
        fecha_inicio: row.fecha_inicio_prima,
        fecha_fin: row.fecha_fin_prima,
        semestre: row.semestre,
        ruta: row.ruta_desprendible_generada || row.ruta_desprendible
    }, function (response) {
        alert(response.message || 'Prima enviada a pendientes correctamente.');
        tablaPrimas.ajax.reload(null, false);
    });
});

$('#btnCargarComprobante').on('click', function () {
    if (seleccionados.size === 0) {
        alert('Selecciona al menos un empleado.');
        return;
    }

    const archivo = document.getElementById('comprobante').files[0];
    if (!archivo) {
        alert('Selecciona el comprobante.');
        return;
    }

    const formData = new FormData();
    formData.append('accion', 'cargar_comprobante');
    formData.append('comprobante', archivo);
    formData.append('ids', JSON.stringify([...seleccionados.keys()]));
    formData.append('fecha_inicio', periodoActual.inicio);
    formData.append('fecha_fin', periodoActual.fin);
    formData.append('semestre', periodoActual.semestre);

    $.ajax({
        url: dirPage,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json'
    }).done(function (response) {
        alert(response.message || 'Comprobante cargado correctamente.');
        if (response.success) {
            seleccionados.clear();
            $('#comprobante').val('');
            $('#checkTodos').prop('checked', false);
            tablaPrimas.ajax.reload(null, false);
        }
    }).fail(function (xhr) {
        alert(xhr.responseJSON?.message || 'No fue posible cargar el comprobante.');
    });
});

$('#btnExcelPago').on('click', function () {
    obtenerFilasSalida(function (filas) {
        const datosPago = filas.map(row => {
            const partesNombre = String(row.trabajador || '').trim().split(/\s+/);
            const mitad = Math.max(1, Math.ceil(partesNombre.length / 2));
            return {
                id: row.idusuario,
                cedula: row.cedula,
                nombre: partesNombre.slice(0, mitad).join(' '),
                apellido: partesNombre.slice(mitad).join(' '),
                codigo_banco: row.codigo_banco,
                tipo_cuenta: row.tipo_cuenta,
                cuenta: row.cuenta,
                valor_pago: Number(row.total_prima || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 })
            };
        });

        enviarPostNuevaVentana('../view/Primas/excel_pagos.php', {
            anio: $('#anio').val(),
            semestre: $('#semestre').val(),
            datos_pago: JSON.stringify(datosPago)
        });
    });
});

$('#btnReporte').on('click', function () {
    obtenerFilasSalida(function (filas) {
        const datos = filas.map(row => ({
            id: row.idusuario,
            nombreCompleto: row.trabajador,
            contrato: row.tipo_contrato,
            cedula: row.cedula,
            nombreCargo: row.cargo,
            salario: row.salario,
            auxilio: row.auxilio,
            descanso: row.descanso,
            NoTrabajo: row.dias_no_trabajados,
            SinRegistro: row.dias_sin_registro,
            Incapacidad: row.incapacidad_empresa,
            Vacaciones: row.vacaciones,
            licenciasPermisos: row.licencias,
            totalDiasPrima: row.total_dias_prima,
            diasProyectados: row.dias_proyectados || 0,
            valorDiasPrima_formateado: Number(row.total_prima || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 })
        }));

        enviarPostNuevaVentana('../view/Primas/reporte.php', {
            param33: periodoActual.inicio_sin_tiempo,
            param34: $('#anio').val(),
            param35: $('#sede').val(),
            param36: $('#semestre').val(),
            datosusertabla: JSON.stringify(datos)
        });
    });
});
</script>
</body>
</html>
