<?php
/**
 * Popup: Historial de Kilometraje del Vehículo
 *
 * Muestra gráfico de tendencia (Chart.js), tarjetas de resumen,
 * filtros de fecha/fuente, y tabla de registros.
 *
 * @var array $vehiculo   Datos del vehículo
 * @var array $historial  Lista de registros de kilometraje (sin filtrar)
 */

$appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
$desdeDefecto = date('Y-m-d', strtotime('-30 days'));
$hastaDefecto = date('Y-m-d');
?>

<h6><i class="fas fa-tachometer-alt"></i> Historial de Kilometraje — <?= htmlspecialchars($vehiculo['veh_placa']) ?></h6>
<hr>

<!-- Tarjetas de resumen -->
<div style="display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap;" id="kmResumenCards">
    <div style="flex:1; min-width:140px; background:#f0f4ff; border-radius:12px; padding:14px; text-align:center;">
        <div style="font-size:11px; color:#888; text-transform:uppercase;">Km Actual</div>
        <div style="font-size:22px; font-weight:700; color:#0c4582;" id="kmResumenActual">—</div>
        <div style="font-size:11px; color:#888;">km</div>
    </div>
    <div style="flex:1; min-width:140px; background:#e8f2ec; border-radius:12px; padding:14px; text-align:center;">
        <div style="font-size:11px; color:#888; text-transform:uppercase;">Recorrido (30d)</div>
        <div style="font-size:22px; font-weight:700; color:#1e7f4f;" id="kmResumenRecorrido">—</div>
        <div style="font-size:11px; color:#888;">km en el período</div>
    </div>
    <div style="flex:1; min-width:140px; background:#fff4e5; border-radius:12px; padding:14px; text-align:center;">
        <div style="font-size:11px; color:#888; text-transform:uppercase;">Promedio Diario</div>
        <div style="font-size:22px; font-weight:700; color:#b54708;" id="kmResumenPromedio">—</div>
        <div style="font-size:11px; color:#888;">km/día</div>
    </div>
</div>

<!-- Filtros -->
<div style="display:flex; gap:12px; align-items:end; margin-bottom:16px; flex-wrap:wrap;">
    <div>
        <label style="font-size:12px; font-weight:600; color:#555;">Desde</label>
        <input type="date" id="filtroDesdeKm" class="form-control form-control-sm" value="<?= $desdeDefecto ?>" style="width:140px;">
    </div>
    <div>
        <label style="font-size:12px; font-weight:600; color:#555;">Hasta</label>
        <input type="date" id="filtroHastaKm" class="form-control form-control-sm" value="<?= $hastaDefecto ?>" style="width:140px;">
    </div>
    <div>
        <label style="font-size:12px; font-weight:600; color:#555;">Fuente</label>
        <select id="filtroFuenteKm" class="form-select form-select-sm" style="width:160px;">
            <option value="">Todas</option>
            <option value="Evento">Evento</option>
            <option value="Preoperacional">Preoperacional</option>
            <option value="Cambio Aceite">Cambio Aceite</option>
        </select>
    </div>
    <button class="btn btn-sm btn-primary" onclick="filtrarHistorialKm(<?= $vehiculo['idvehiculos'] ?>)">
        <i class="fas fa-search"></i> Filtrar
    </button>
</div>

<!-- Gráfico Chart.js -->
<div style="background:#fafbfc; border:1px solid var(--gris-borde, #e6e9f0); border-radius:12px; padding:20px; margin-bottom:16px; position:relative;">
    <div style="text-align:center; color:#888; font-size:12px; margin-bottom:10px;">📈 Tendencia de Kilometraje</div>
    <div style="position:relative; height:260px;">
        <canvas id="kmChart"></canvas>
    </div>
    <div style="display:flex; gap:16px; justify-content:center; margin-top:10px; font-size:11px;">
        <span>🔵 Evento</span>
        <span>🟢 Preoperacional</span>
        <span>🟠 Cambio Aceite</span>
    </div>
</div>
<div id="kmChartNoData" style="display:none; text-align:center; padding:40px; color:#888; background:#fafbfc; border-radius:12px; border:1px solid #e6e9f0; margin-bottom:16px;">
    Sin datos de kilometraje en este período.
</div>

<!-- Tabla de registros -->
<div id="kmHistorialTabla">
    <?php if (empty($historial)): ?>
        <div class="alert alert-warning">No hay registros de kilometraje para este vehículo.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover" style="font-size:12px;">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Fuente</th>
                        <th>Kilometraje</th>
                        <th>Detalle</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h):
                        $preopId = $h['id_preoperacional'] ?? null;
                        $userId = $h['id_usuario'] ?? null;
                        $fechaDate = $h['fecha_date'] ?? date('Y-m-d', strtotime($h['fecha'] ?? 'now'));
                        $tieneVer = $preopId && $userId;
                        $verUrl = '';
                        if ($tieneVer) {
                            $verUrl = 'PreoperacionalController.php?preoperacional=validarpreoperacional'
                                . '&idpre=' . (int)$preopId
                                . '&iduser=' . (int)$userId
                                . '&fecha=' . urlencode($fechaDate)
                                . '&idvehiculo=' . (int)$vehiculo['idvehiculos']
                                . '&param4=ingresado&param5=vista';
                        }
                    ?>
                    <tr>
                        <td><?= date('d-m-Y H:i', strtotime($h['fecha'])) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($h['fuente']) ?></span></td>
                        <td><strong><?= number_format((int)($h['kilometraje'] ?? 0), 0, ',', '.') ?> km</strong></td>
                        <td><?= htmlspecialchars($h['detalle'] ?? '') ?></td>
                        <td><?= htmlspecialchars($h['usuario_nombre'] ?? '—') ?></td>
                        <td>
                            <?php if ($tieneVer): ?>
                                <a href="#"
                                   onclick="window.open('<?= $verUrl ?>', '_blank', 'width=800,height=600,scrollbars=yes'); return false;"
                                   style="color:#0c4582; font-weight:600;">
                                    👁️ Ver
                                </a>
                            <?php else: ?>
                                <span style="color:#aaa;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
