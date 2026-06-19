<?php
/**
 * Popup: Consulta SST - Reportes de conductor
 *
 * Variables disponibles desde el controlador:
 *   $vehiculo    - array|null Datos del vehiculo si se abrio desde una fila especifica
 *   $semana      - array ['inicio' => 'Y-m-d', 'fin' => 'Y-m-d'] Semana actual
 *   $conductores - array Lista de conductores activos [idusuarios, usu_nombre]
 */
$preSeleccionado = $vehiculo ? ($vehiculo['conductor_id'] ?? null) : null;
$prePlaca = $vehiculo['veh_placa'] ?? '';
$preIdVehiculo = $vehiculo['idvehiculos'] ?? '';

// Formatear semana actual para el input type="week" (necesita YYYY-Www)
$semanaInput = date('Y-\WW', strtotime($semana['inicio']));
?>

<div class="consulta-sst-container">
    <!-- Barra de filtros común -->
    <div class="row g-3 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-bold">Semana</label>
            <input type="week" id="sstSemanaInput" class="form-control"
                   value="<?= $semanaInput ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Conductor</label>
            <select id="sstConductorSelect" class="form-select">
                <option value="">Todos los conductores</option>
                <?php foreach ($conductores as $c): ?>
                    <option value="<?= $c['idusuarios'] ?>"
                        <?= ($c['idusuarios'] == $preSeleccionado) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['usu_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="button" id="sstBuscarBtn" class="btn btn-primary w-100">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
        <div class="col-md-3 text-end">
            <input type="hidden" id="sstPreVehiculoId" value="<?= $preIdVehiculo ?>">
            <input type="hidden" id="sstPreConductorId" value="<?= $preSeleccionado ?>">
        </div>
    </div>

    <!-- Pestañas -->
    <ul class="nav nav-tabs mb-3" id="sstTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sstResumenTab" data-bs-toggle="tab"
                    data-bs-target="#sstResumenPane" type="button" role="tab">
                <i class="fas fa-table me-1"></i> Resumen Semanal
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sstHistorialTab" data-bs-toggle="tab"
                    data-bs-target="#sstHistorialPane" type="button" role="tab">
                <i class="fas fa-history me-1"></i> Historial de Reportes
            </button>
        </li>
    </ul>

    <div class="tab-content" id="sstTabsContent">
        <!-- Pestaña 1: Resumen Semanal -->
        <div class="tab-pane fade show active" id="sstResumenPane" role="tabpanel">
            <div id="sstResumenLoading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando resumen semanal...</p>
            </div>
            <div id="sstResumenContent" style="display:none;">
                <div id="sstResumenStats" class="mb-3"></div>
                <div class="table-responsive">
                    <table id="sstResumenTable" class="table table-sm table-hover align-middle"
                           style="width:100%; font-size:13px;">
                        <thead class="table-light">
                            <tr>
                                <th>Conductor</th>
                                <th>Placa</th>
                                <th class="text-center">Accidente <br><small class="text-muted">(Obligatorio)</small></th>
                                <th class="text-center">Comparendo <br><small class="text-muted">(Obligatorio)</small></th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="sstResumenBody"></tbody>
                    </table>
                </div>
                <div id="sstResumenEmpty" style="display:none;" class="alert alert-info text-center">
                    No se encontraron conductores activos.
                </div>
            </div>
        </div>

        <!-- Pestaña 2: Historial de Reportes -->
        <div class="tab-pane fade" id="sstHistorialPane" role="tabpanel">
            <!-- Filtros adicionales para historial -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <label class="form-label small">Tipo de Reporte</label>
                    <select id="sstHistTipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="accidente">Accidente</option>
                        <option value="comparendo">Comparendo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Tipo de Evento</label>
                    <select id="sstHistEvento" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="semanal">Semanal</option>
                        <option value="momento">Momento</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">&nbsp;</label>
                    <button type="button" id="sstHistBuscarBtn" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </div>
            <div id="sstHistorialLoading" class="text-center py-4">
                <div class="spinner-border text-secondary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando historial...</p>
            </div>
            <div id="sstHistorialContent" style="display:none;">
                <div class="table-responsive">
                    <table id="sstHistorialTable" class="table table-sm table-hover align-middle"
                           style="width:100%; font-size:12px;">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Conductor</th>
                                <th>Placa</th>
                                <th>Tipo</th>
                                <th>Evento</th>
                                <th class="text-center">Gravedad</th>
                                <th class="text-center">Respuesta</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Archivos</th>
                            </tr>
                        </thead>
                        <tbody id="sstHistorialBody"></tbody>
                    </table>
                </div>
                <div id="sstHistorialEmpty" style="display:none;" class="alert alert-info text-center">
                    No se encontraron reportes para los filtros seleccionados.
                </div>
            </div>
        </div>
    </div>
</div>
