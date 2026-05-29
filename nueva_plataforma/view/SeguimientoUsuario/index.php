<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Seguimiento de Usuarios</title>
    <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.3.2/css/fixedHeader.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .noti_bubble_blue {
            background-color: #2196F3 !important;
        }

        /* Notificaciones toast */
        .toast-notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            max-width: 350px;
        }

        .toast-notification {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .toast-notification.fade-out {
            opacity: 0 !important;
        }

        .toast-notification-content {
            flex-grow: 1;
            padding-right: 1rem;
        }

        .toast-notification-close {
            background: none;
            border: none;
            color: #155724;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-notification-close:hover {
            color: #0c4128;
        }

        .noti_options {
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            padding: 10px;
        }

        .alerta-wrapper {
            display: inline-block;
            padding: 8px 10px;
            margin: -6px -4px;
            cursor: default;
        }

        .alerta-dropdown {
            display: none;
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.25);
            padding: 10px 14px;
            left: 0;
            top: 100%;
            margin-top: 4px;
            min-width: 320px;
            white-space: nowrap;
        }

        /* Evitar que la celda de la tabla recorte el dropdown */
        #tablaSeguimiento td {
            overflow: visible !important;
        }

        .warning-dot {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
            animation: moderateFlash 1s infinite;
            border: 2px solid #333;
        }

        .warning-orange {
            background-color: #FF9800;
        }

        .warning-yellow {
            background-color: #FFEB3B;
        }

        .warning-red {
            background-color: #F44336;
        }

        @keyframes moderateFlash {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.2;
            }
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

        /* Select2 dropdown más alto */
        .select2-dropdown.select2-large-dropdown {
            height: 500px !important;
            max-height: 70vh !important;
            min-height: 200px !important;
            overflow: hidden !important;
        }
        .select2-dropdown.select2-large-dropdown .select2-results {
            height: calc(100% - 40px) !important; /* Restar altura del search */
            overflow-y: auto !important;
            max-height: none !important;
        }
        .select2-dropdown.select2-large-dropdown .select2-search {
            height: 40px !important;
            flex-shrink: 0 !important;
        }
        .select2-dropdown.select2-large-dropdown .select2-results__options {
            max-height: none !important;
        }
        /* Asegurar que no haya límites de altura en elementos internos */
        .select2-large-dropdown .select2-results__options,
        .select2-large-dropdown .select2-results {
            max-height: none !important;
            height: auto !important;
        }
    </style>
</head>

<body>
    <!-- Contenedor para notificaciones toast -->
    <div id="toastContainer" class="toast-notification-container"></div>

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
                        <button type="button" class="btn btn-lg" onclick="abrirModalNuevosEmpleados()" style="background-color: #2196F3; color: white;">
                            <i class="fas fa-user-plus"></i> Nuevos Empleados
                        </button>
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

    <!-- Modal Nuevos Empleados (búsqueda por rango de fecha de ingreso) -->
    <div class="modal fade" id="modalNuevosEmpleados" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Nuevos Empleados por Fecha de Ingreso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="nuevosEmpleadosModalBody">
                    <!-- Cargado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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

    <!-- Variables globales necesarias para el JS externo -->
    <script>
        window.dirPage = <?= json_encode($ajaxEndpoint ?? '') ?> || window.location.pathname;
        window.hasDeletePermission = <?= ($_SESSION['usuario_rol'] == 1 || $_SESSION['usuario_rol'] == 12) ? 'true' : 'false' ?>;
    </script>

    <!-- JavaScript del módulo de seguimiento de usuarios -->
    <script src="../assets/js/seguimiento.js?v=<?= filemtime(__DIR__ . '/../../assets/js/seguimiento.js') ?>"></script>
</body>

</html>