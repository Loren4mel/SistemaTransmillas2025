<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Seguimiento de Vehículos</title>
    <link rel="shortcut icon" href="<?= $appBasePath ?>/../images/Logo Google Nuevo.png">
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Ruta base de la aplicación -->
    <script>
        window.APP_BASE_URL = "<?= $appBasePath ?>";
    </script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Estilos pastel compartidos con SeguimientoUsuario -->
    <link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/usuarios.css">
    <style>
        /* === Puntos de alerta y dropdowns (específicos de vehículos) === */
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
        .warning-orange { background-color: #F0AD4E; }
        .warning-red { background-color: #D9534F; }
        @keyframes moderateFlash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.2; }
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
            border: 1px solid var(--gris-borde);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.10);
            padding: 12px 16px;
            left: 0;
            top: 100%;
            margin-top: 4px;
            min-width: 280px;
            white-space: nowrap;
            z-index: 99999;
        }
        #tablaVehiculos td { overflow: visible !important; }
        .badge { font-size: 0.8rem; }

        /* === Estados de vehículo (pastel) === */
        .estado-optimo {
            background-color: #e8f2ec !important;
            color: #1e7f4f !important;
            border: 1px solid #b7dfc7;
            border-radius: 20px;
            padding: 6px 14px;
            font-weight: 600;
        }
        .estado-novedades {
            background-color: #fff4e5 !important;
            color: #b54708 !important;
            border: 1px solid #f6d7a7;
            border-radius: 20px;
            padding: 6px 14px;
            font-weight: 600;
        }
        .estado-fuera-servicio {
            background-color: #fdecec !important;
            color: #b42318 !important;
            border: 1px solid #f5b5b5;
            border-radius: 20px;
            padding: 6px 14px;
            font-weight: 600;
        }

        /* Celda de estado general clickeable */
        #tablaVehiculos td:nth-child(5) {
            cursor: pointer;
            transition: filter 0.2s ease;
        }
        #tablaVehiculos td:nth-child(5):hover {
            filter: brightness(0.93);
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
                    <i class="fas fa-truck me-2"></i> Seguimiento de Vehículos
                </h3>
                <div></div>
            </div>

            <div class="card-body">
                <!-- Filtros -->
                <form id="formFiltros" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label class="form-label">Propiedad</label>
                        <select name="propiedad" id="propiedad" class="form-select">
                            <option value="Empresa" selected>Empresa</option>
                            <option value="Personal">Personal</option>
                            <option value="Todos">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estado General</label>
                        <select name="estado_general" id="estado_general" class="form-select">
                            <option value="">Todos</option>
                            <option value="OPTIMO">Óptimo</option>
                            <option value="CON_NOVEDADES">Con Novedades</option>
                            <option value="FUERA_DE_SERVICIO">Fuera de Servicio</option>
                        </select>
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
                        <label class="form-label">Conductor</label>
                        <select name="conductor" id="conductor" class="form-select">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Consulta</label>
                        <input type="date" name="fecha_consulta" id="fecha_consulta" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Buscar Placa</label>
                        <input type="text" name="search_placa" id="search_placa" class="form-control" placeholder="Placa, marca...">
                    </div>
                    <div class="col-md-2 text-end d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" onclick="recargarTabla()">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </form>

                <!-- Tabla de vehículos -->
                <div class="table-responsive">
                    <table id="tablaVehiculos" class="table table-hover table-bordered align-middle" style="width:100%">
                        <thead class="thead-modern">
                            <tr>
                                <th>Alertas</th>
                                <th>Placa</th>
                                <th>Tipo</th>
                                <th>Marca / Modelo</th>
                                <th>Estado General</th>
                                <th>Registro del Día</th>
                                <th>Conductor</th>
                                <th>Kilometraje</th>
                                <th>Último Preop.</th>
                                <th>SOAT</th>
                                <th>Tecnomecánica</th>
                                <th>Mantenimiento</th>
                                <th>Cambio Aceite</th>
                                <th>Comparendos</th>
                                <th>Propiedad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
                    <!-- Contenido cargado vía AJAX -->
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        window.dirPage = <?= json_encode($ajaxEndpoint ?? '') ?> || window.location.pathname;
        window.hasAdminPermission = <?= (in_array($_SESSION['usuario_rol'] ?? 0, [1, 12])) ? 'true' : 'false' ?>;
    </script>

    <script src="<?= $appBasePath ?>/assets/js/seguimiento_vehiculo.js?v=<?= time() ?>"></script>
</body>

</html>
