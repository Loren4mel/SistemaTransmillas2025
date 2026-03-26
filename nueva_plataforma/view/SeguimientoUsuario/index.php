<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Seguimiento de Usuarios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.3.2/css/fixedHeader.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/usuarios.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .noti_bubble {
            float: right;
            padding: 2px 6px;
            background-color: red;
            color: white;
            font-weight: bold;
            border-radius: 60px;
            box-shadow: 1px 1px 3px gray;
            cursor: pointer;
        }

        .noti_options {
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            padding: 10px;
            margin-top: 5px;
            z-index: 1000;
        }

        .warning-dot {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
            animation: moderateFlash 1s infinite;
        }
        .warning-yellow {
            background-color: #FFC107;
        }
        .warning-red {
            background-color: #F44336;
        }
        @keyframes moderateFlash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .table td,
        .table th {
            white-space: normal;
            word-wrap: break-word;
        }

        .dataTables_wrapper {
            overflow-x: scroll;
        }
        .dataTables_scrollBody {
            overflow-x: scroll !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="card shadow p-3 mb-4 bg-body rounded">
            <div class="card-header mi-header d-flex align-items-center justify-content-between">
                <button class="btn btn-light" onclick="history.back()">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </button>
                <h3 class="mb-0">
                    <i class="fas fa-users me-2"></i> Seguimiento de Usuarios
                </h3>
            </div>

            <div class="card-body">
                <!-- Botones de acción principales -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-info btn-lg" onclick="abrirModalFestivos()">
                            <i class="fas fa-calendar-plus"></i> + Día de descanso
                        </button>
                        <button type="button" class="btn btn-info btn-lg" onclick="abrirModalVacaciones()">
                            <i class="fas fa-umbrella-beach"></i> + Vacaciones
                        </button>
                        <button type="button" class="btn btn-info btn-lg" onclick="abrirModalLicencias()">
                            <i class="fas fa-file-medical"></i> + Licencias y permisos
                        </button>
                        <?php if ($_SESSION['usuario_rol'] == 1 || $_SESSION['usuario_rol'] == 12): ?>
                            <!-- <button type="button" class="btn btn-primary btn-lg" onclick="abrirModalIngreso()">
                                <i class="fas fa-user-plus"></i> Ingreso manual
                            </button> -->
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtros -->
                <form id="formFiltros" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label class="form-label">Fecha Inicial</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control"
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Final</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control"
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sede</label>
                        <select name="sede" id="sede" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($sedes as $s): ?>
                                <option value="<?= $s['idsedes'] ?>"><?= htmlspecialchars($s['sed_nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Operario</label>
                        <select name="operario" id="operario" class="form-select">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Motivo Ingreso</label>
                        <select name="motivo" id="motivo" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($motivos as $key => $value): ?>
                                <option value="<?= $key ?>"><?= htmlspecialchars($value) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo Contrato</label>
                        <select name="tipo_contrato" id="tipo_contrato" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($tiposContrato as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-primary" onclick="recargarTabla()">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </form>

                <!-- Celda para mostrar deuda del operario seleccionado -->
                <div id="miCelda" class="alert alert-info" style="display:none;"></div>

                <!-- Tabla de seguimiento -->
                <div class="table-responsive">
                    <table id="tablaSeguimiento" class="table table-hover table-bordered align-middle"
                        style="width:100%">
                        <thead class="thead-modern">
                            <tr>
                                <th>Operador</th>
                                <th>Preoperacional</th>
                                <th>Validación</th>
                                <th>Imagen</th>
                                <th>Ingreso?</th>
                                <th>Descripción</th>
                                <th>Fecha Ingreso</th>
                                <th>Zona Trabajo</th>
                                <th>Trabaja con</th>
                                <th>Hora Almuerzo</th>
                                <th>Retorno Almuerzo</th>
                                <th>Retorno Oficina</th>
                                <th>Hora Salida</th>
                                <th>Tipo Contrato</th>
                                <th>PLACA</th>
                                <th>Fecha Seguro</th>
                                <th>Fecha Tecno</th>
                                <th>Fecha Licencia</th>
                                <th>Cambio Aceite</th>
                                <?php if ($_SESSION['usuario_rol'] == 1 || $_SESSION['usuario_rol'] == 12): ?>
                                    <th>Eliminar</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES -->

    <!-- Modal Ingreso (SeguimientoUser) -->
    <div class="modal fade" id="modalIngreso" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar ingreso de operario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="ingresoModalBody">
                    <!-- Se cargará vía AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Festivos (día de descanso para todos) -->
    <div class="modal fade" id="modalFestivos" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar día de descanso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="festivosModalBody">
                    <!-- Cargado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-info" onclick="guardarFestivos()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Vacaciones -->
    <div class="modal fade" id="modalVacaciones" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar vacaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="vacacionesModalBody">
                    <!-- Cargado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-info" onclick="guardarVacaciones()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Licencias y permisos -->
    <div class="modal fade" id="modalLicencias" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar licencia / permiso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="licenciasModalBody">
                    <!-- Cargado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-info" onclick="guardarLicencias()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal genérico para popups -->
    <div class="modal fade" id="popupModal" tabindex="-1" aria-labelledby="popupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="popupModalLabel">Cargando...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="popupModalBody">
                    <!-- El contenido se carga vía AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Bootstrap, DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.3.2/js/dataTables.fixedHeader.min.js"></script>
    <!-- CSS de Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Variables globales
        const dirPage = <?= json_encode($ajaxEndpoint ?? '') ?> || window.location.pathname;
        $.fn.dataTable.ext.errMode = 'none';

        // --- Funciones auxiliares reutilizables ---
        function mostrarError(mensaje) {
            alert('Error: ' + mensaje);
            console.error(mensaje);
        }

        function eliminarRegistro(id, accion) {
            // Verificar que la acción sea borraseguser (por compatibilidad)
            if (accion !== 'borraseguser') {
                mostrarError('Acción de eliminación no reconocida');
                return;
            }
            // Obtener detalles para mostrar
            $.get(dirPage, { accion: 'get_detalles_eliminar', id_combinado: id }, function(detalles) {
                // Construir mensaje de confirmación
                let mensaje = '¿Está seguro de eliminar el siguiente registro?\n\n';
                if (detalles.usuario) mensaje += 'Operario: ' + detalles.usuario + '\n';
                if (detalles.fecha) mensaje += 'Fecha: ' + detalles.fecha + '\n';
                if (detalles.motivo) mensaje += 'Motivo: ' + detalles.motivo + '\n';
                mensaje += '\nEsta acción eliminará tanto el registro de seguimiento como el preoperacional asociado.';

                if (confirm(mensaje)) {
                    // Enviar solicitud de eliminación
                    $.post(dirPage, { accion: 'eliminar_seguimiento', id_combinado: id }, function(res) {
                        if (res.success) {
                            alert(res.message);
                            // Recargar la tabla
                            tabla.ajax.reload();
                        } else {
                            mostrarError(res.message || 'Error al eliminar');
                        }
                    }, 'json').fail(function() {
                        mostrarError('Error de comunicación con el servidor');
                    });
                }
            }).fail(function() {
                mostrarError('No se pudieron cargar los detalles del registro');
            });
        }

        function cargarOperarios(selectId, sedeId, textoPorDefecto = 'Seleccione') {
            if (!sedeId) {
                // Si no hay sede, limpiar select
                $(selectId).html('<option value="">' + textoPorDefecto + '</option>');
                return;
            }
            $.get(dirPage, { accion: 'get_operarios', idsede: sedeId }, function (data) {
                let options = '<option value="">' + textoPorDefecto + '</option>';
                data.forEach(op => {
                    options += `<option value="${op.idusuarios}">${op.usu_nombre}</option>`;
                });
                $(selectId).html(options);
            }).fail(function () {
                mostrarError('No se pudieron cargar los operarios');
            });
        }

        function cargarZonas(selectId, sedeId) {
            if (!sedeId) {
                $(selectId).html('<option value="">Seleccione</option>');
                return;
            }
            $.get(dirPage, { accion: 'get_zonas', idsede: sedeId }, function (data) {
                let options = '<option value="">Seleccione</option>';
                data.forEach(z => {
                    options += `<option value="${z.idzonatrabajo}">${z.zon_nombre}</option>`;
                });
                $(selectId).html(options);
            }).fail(function () {
                mostrarError('No se pudieron cargar las zonas');
            });
        }

        function guardarForm(formId, modalId, url = dirPage) {
            let $form = $(formId);
            let data = $form.serialize();
            $.post(url, data, function (res) {
                if (res.success) {
                    $(modalId).modal('hide');
                    tabla.ajax.reload();
                } else {
                    mostrarError(res.message || 'Error al guardar');
                }
            }, 'json').fail(function () {
                mostrarError('Error de comunicación con el servidor');
            });
        }
        // Función para cargar operarios en un select y aplicar Select2
        function cargarOperariosConSelect2(selector, sede = '', placeholder = 'Seleccione') {
            let url = sede ? (dirPage + '?accion=get_operarios&idsede=' + sede) : (dirPage + '?accion=get_all_operarios');
            $.get(url, function (data) {
                let $select = $(selector);
                $select.empty().append('<option value="">' + placeholder + '</option>');
                data.forEach(function (op) {
                    $select.append('<option value="' + op.idusuarios + '">' + op.usu_nombre + '</option>');
                });
                // Si ya tiene Select2, destrúyelo antes de reinicializar
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                // Inicializar Select2
                $select.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(selector).closest('.modal')
                })
            }).fail(function (xhr, status, error) {
                console.error('Error cargando operarios:', status, error);
            });
        }

        // --- Inicialización DataTable ---
        let tabla = $('#tablaSeguimiento').DataTable({
            order: [], // sin orden inicial
            stripeClasses: [],
            processing: true,
            serverSide: true,
            ajax: {
                url: dirPage,
                type: 'POST',
                data: function (d) {
                    d.ajax = true;
                    d.fecha_inicio = $('#fecha_inicio').val();
                    d.fecha_fin = $('#fecha_fin').val();
                    d.sede = $('#sede').val();
                    d.operario = $('#operario').val();
                    d.motivo = $('#motivo').val();
                    d.tipo_contrato = $('#tipo_contrato').val();
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.error('Error AJAX DataTable:', {
                        status: xhr.status,
                        textStatus: textStatus,
                        errorThrown: errorThrown,
                        responseText: xhr.responseText
                    });
                    alert('Error cargando tabla. Revisa consola y logs del servidor.');
                }
            },
            columns: [
                {
                    data: 'alerta_html',
                    render: function (data, type, row) {
                        return (row.alerta_html || '') + (row.alerta_izquierda_html || '') + ' ' + row.usu_nombre;
                    }
                },
                { data: 'preoperacional_link' },
                { data: 'validacion_link' },
                { data: 'imagen_link' },
                { data: 'ingreso_link' },
                { data: 'seg_descr' },
                { data: 'seg_fechaingreso' },
                { data: 'zona_link' },
                { data: 'companero_link' },
                { data: 'hora_almuerzo_link' },
                { data: 'retorno_almuerzo_link' },
                { data: 'retorno_oficina_link' },
                { data: 'seg_fechafinalizo' },
                { data: 'usu_tipocontrato' },
                { data: 'veh_placa' },
                { data: 'fecha_seguro_html' },
                { data: 'fecha_tecno_html' },
                { data: 'fecha_licencia_html' },
                { data: 'cambio_aceite_html' },
                <?php if ($_SESSION['usuario_rol'] == 1 || $_SESSION['usuario_rol'] == 12): ?>
                                                                                        { data: 'eliminar_html' }
                <?php endif; ?>
            ],
            columnDefs: [
                { targets: '_all', className: 'text-center', orderable: false }
            ],
            createdRow: function (row, data, dataIndex) {
                // Usar nuevos colores si existen, sino el antiguo row_color
                var bgColor = data.row_bg_color || data.row_color;
                var textColor = data.row_text_color;
                if (bgColor) {
                    // Aplica el color de fondo a la fila y a todas las celdas
                    $(row).css('background-color', bgColor);
                    $(row).find('td').css('background-color', bgColor);
                }
                // Aplicar color de texto si está definido
                if (textColor) {
                    $(row).css('color', textColor);
                    $(row).find('td').css('color', textColor);
                } else if (bgColor === '#922B21') {
                    // Compatibilidad: si el fondo es rojo oscuro antiguo, texto blanco
                    $(row).css('color', 'white');
                    $(row).find('td').css('color', 'white');
                }
            },
            scrollX: true,
            scrollY: '60vh',
            pageLength: 100,
            fixedHeader: true
        });

        // Auto-refresh cada 10 minutos 
        let refreshTimer = setInterval(function() {
            tabla.ajax.reload(null, false); // false para mantener la página actual
        }, 600000);

        // Limpiar timer al salir de la página
        $(window).on('beforeunload', function() {
            clearInterval(refreshTimer);
        });

        $('#tablaSeguimiento').on('error.dt', function (e, settings, techNote, message) {
            console.error('DataTable error.dt:', { techNote, message });
        });

        function recargarTabla() {
            tabla.ajax.reload();
        }

        // --- Eventos globales (una sola vez) ---

        // Cuando cambia la sede en filtros, actualizar selects de operarios (filtro y modales)
        $('#sede').on('change', function () {
            let sede = $(this).val();
            // Actualizar filtro de operarios
            cargarOperarios('#operario', sede, 'Todos');
            // Actualizar selects de modales (si existen en el DOM)
            cargarOperarios('#ing_operario', sede, 'Seleccione');
            cargarOperarios('#vac_operario', sede, 'Seleccione');
            cargarOperarios('#lic_operario', sede, 'Seleccione');
        });

        // Mostrar deuda al seleccionar operario en el filtro
        $('#operario').on('change', function () {
            let id = $(this).val();
            if (id) {
                $.get(dirPage, { accion: 'get_deuda', idoperario: id }, function (res) {
                    $('#miCelda').html('Debe: $ ' + res.deuda).show();
                }).fail(function () {
                    $('#miCelda').hide();
                });
            } else {
                $('#miCelda').hide();
            }
        });

        // Carga de contenido en modal de ingreso
        $('#modalIngreso').on('show.bs.modal', function () {
            $('#ingresoModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(window.location.pathname, { accion: 'form_popup', tipo: 'ingreso_manual' }, function (html) {
                $('#ingresoModalBody').html(html);
            }).fail(function () {
                $('#ingresoModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
        });

        // Cuando se carga el formulario de ingreso (vía AJAX), delegar eventos
        $(document).on('change', '#ing_sede', function () {
            let sede = $(this).val();
            cargarOperarios('#ing_operario', sede, 'Seleccione');
            cargarZonas('#ing_zona', sede);
        });

        // Carga de operarios en modales de vacaciones y licencias al abrirlos

        // Al abrir modal de festivos
        $('#modalFestivos').on('show.bs.modal', function () {
            $('#festivosModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(window.location.pathname, { accion: 'form_popup', tipo: 'festivos' }, function (html) {
                $('#festivosModalBody').html(html);
            }).fail(function () {
                $('#festivosModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
        });

        // Al abrir modal de vacaciones
        $('#modalVacaciones').on('show.bs.modal', function () {
            $('#vacacionesModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(window.location.pathname, { accion: 'form_popup', tipo: 'vacaciones' }, function (html) {
                $('#vacacionesModalBody').html(html);
                // Después de cargar el formulario, cargar operarios con Select2
                cargarOperariosConSelect2('#vac_operario', $('#sede').val(), 'Seleccione operario');
            }).fail(function () {
                $('#vacacionesModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
        });

        // Al abrir modal de licencias
        $('#modalLicencias').on('show.bs.modal', function () {
            $('#licenciasModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(window.location.pathname, { accion: 'form_popup', tipo: 'licencias' }, function (html) {
                $('#licenciasModalBody').html(html);
                // Después de cargar el formulario, cargar operarios con Select2
                cargarOperariosConSelect2('#lic_operario', $('#sede').val(), 'Seleccione operario');
            }).fail(function () {
                $('#licenciasModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
        });

        // Al cerrar modales, destruir Select2 solo si existe la instancia
        $('#modalVacaciones, #modalLicencias').on('hidden.bs.modal', function () {
            // Selecciona específicamente los selects de cada modal
            var $select = $(this).find('#vac_operario, #lic_operario');
            // Verifica que el elemento exista y tenga la instancia de Select2 activa
            if ($select.length && $select.data('select2')) {
                $select.select2('destroy');
            }
        });

        // Manejo del formulario genérico en popup
        $(document).on('submit', '#popupForm', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: window.location.pathname,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        $('#popupModal').modal('hide');
                        tabla.ajax.reload();
                    } else {
                        mostrarError(res.message || 'Error al guardar');
                    }
                },
                error: function () {
                    console.error('Error en la respuesta, respuesta:', arguments);
                    mostrarError('Error de comunicación con el servidor');
                }
            });
        });

        // Manejo de burbujas de notificación
        $(document).on('click', '.noti_bubble', function () {
            var id = $(this).data('id');
            $('.noti_options[data-id="' + id + '"]').toggle();
        });

        // --- Funciones de apertura de modales (mantenidas para compatibilidad) ---
        function abrirModalIngreso() {
            $('#modalIngreso').modal('show');
        }
        function abrirModalFestivos() {
            $('#modalFestivos').modal('show');
        }
        function abrirModalVacaciones() {
            $('#modalVacaciones').modal('show');
        }
        function abrirModalLicencias() {
            $('#modalLicencias').modal('show');
        }

        // Funciones de guardado ahora usan la función genérica
        function guardarFestivos() {
            guardarForm('#formFestivos', '#modalFestivos');
        }
        function guardarVacaciones() {
            guardarForm('#formVacaciones', '#modalVacaciones');
        }
        function guardarLicencias() {
            guardarForm('#formLicencias', '#modalLicencias');
        }

        // Función para abrir popup genérico (sin cambios)
        function abrirPopup(tipo, id, param) {
            var titulos = {
                'zona': 'Zona de trabajo',
                'companero': 'Compañero',
                'trabaja_con': 'Trabaja con',
                'hora_almuerzo': 'Hora almuerzo',
                'retorno_almuerzo': 'Retorno almuerzo',
                'retorno_oficina': 'Retorno oficina',
                'festivos': 'Festivos',
                'vacaciones': 'Vacaciones',
                'licencias': 'Licencias',
                'ingreso_manual': 'Ingreso manual',
                'ingreso': 'Ingreso'
            };
            var titulo = titulos[tipo] || ('Editando: ' + tipo);
            $('#popupModal .modal-title').text(titulo);
            $('#popupModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $('#popupModal').modal('show');

            $.get(window.location.pathname, {
                accion: 'form_popup',
                tipo: tipo,
                id: id,
                param: param
            }, function (html) {
                $('#popupModalBody').html(html);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.error('Error al cargar popup:', textStatus, errorThrown);
                $('#popupModalBody').html('<div class="alert alert-danger">Error al cargar el formulario. Ver consola.</div>');
            });
        }

        // Función guardarIngreso (específica porque usa FormData)
        function guardarIngreso() {
            let formData = new FormData(document.getElementById('formIngreso'));
            $.ajax({
                url: dirPage,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.debug_errors) {
                        console.warn('Errores de PHP capturados:', res.debug_errors);
                    }
                    alert(res.message);
                    if (res.success) {
                        $('#modalIngreso').modal('hide');
                        tabla.ajax.reload();
                    }
                },
                error: function () {
                    alert('Error en la petición');
                }
            });

            // Función para ver documentos (preoperacional, validación, etc.)
            function verDocumento(url) {
                window.open(url, '_blank');
            }
        }
    </script>
</body>

</html>