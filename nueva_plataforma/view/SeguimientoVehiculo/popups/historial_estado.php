<?php
/**
 * Popup: Historial de Estado del Vehículo
 *
 * Muestra la última observación no-preoperacional y el historial
 * completo de eventos con filtros de fecha y tipo.
 *
 * @var array $vehiculo   Datos del vehículo
 * @var array|null $ultimaObs Última observación no-preoperacional
 * @var array $eventos    Lista de eventos del historial
 * @var string $desdeDefecto Fecha inicio por defecto (Y-m-d)
 * @var string $hastaDefecto Fecha fin por defecto (Y-m-d)
 */

$appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
$labelTipo = [
    'PREOPERACIONAL' => 'Preoperacional',
    'MANTENIMIENTO' => 'Mantenimiento',
    'TRASLADO_ADMIN' => 'Traslado Admin',
    'POST_SINIESTRO' => 'Post Siniestro',
    'REVISION_SST' => 'Revisión SST',
    'OTRO' => 'Otro',
];

$badgeClase = [
    'OPTIMO' => 'estado-optimo',
    'CON_NOVEDADES' => 'estado-novedades',
    'FUERA_DE_SERVICIO' => 'estado-fuera-servicio',
];
?>

<h6><i class="fas fa-history"></i> Historial de Estado — <?= htmlspecialchars($vehiculo['veh_placa']) ?></h6>
<hr>

<!-- Card: Última Observación No-Preoperacional -->
<div style="background:#f0f2f5; border-radius:12px; padding:16px; margin-bottom:20px;">
    <div style="font-weight:700; font-size:14px; margin-bottom:10px; color:var(--azul-principal, #0c4582);">
        📋 Última Observación
    </div>
    <?php if ($ultimaObs): ?>
        <?php
            $estadoObs = $ultimaObs['estado_general'] ?? 'OPTIMO';
            $claseObs = $badgeClase[$estadoObs] ?? 'bg-secondary';
            $fechaObs = date('d-m-Y H:i', strtotime($ultimaObs['fecha_registro'] ?? ''));
            $kmObs = number_format((int)($ultimaObs['kilometraje'] ?? 0), 0, ',', '.');
        ?>
        <div style="background:#fff; border-radius:8px; padding:14px;">
            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:8px;">
                <span class="<?= $claseObs ?>" style="display:inline-block; border-radius:20px; padding:4px 12px; font-weight:600; font-size:12px;">
                    <?= htmlspecialchars($estadoObs) ?>
                </span>
                <span style="font-size:12px; color:#888;"><?= $fechaObs ?></span>
            </div>
            <p style="margin:0 0 8px 0; color:#333; font-style:italic;">
                "<?= nl2br(htmlspecialchars($ultimaObs['observaciones'] ?? 'Sin observaciones')) ?>"
            </p>
            <div style="font-size:12px; color:#888;">
                <strong>Registrado por:</strong> <?= htmlspecialchars($ultimaObs['responsable_nombre'] ?? '—') ?> &nbsp;|&nbsp;
                <strong>Conductor:</strong> <?= htmlspecialchars($ultimaObs['conductor_nombre'] ?? '—') ?> &nbsp;|&nbsp;
                <strong>Tipo:</strong> <?= htmlspecialchars($labelTipo[$ultimaObs['tipo_evento']] ?? $ultimaObs['tipo_evento']) ?> &nbsp;|&nbsp;
                <strong>Km:</strong> <?= $kmObs ?> km
            </div>
        </div>
    <?php else: ?>
        <div style="background:#fff; border-radius:8px; padding:14px; color:#888; text-align:center;">
            Sin eventos no-preoperacionales registrados.
        </div>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div style="display:flex; gap:12px; align-items:end; margin-bottom:16px; flex-wrap:wrap;">
    <div>
        <label style="font-size:12px; font-weight:600; color:#555;">Desde</label>
        <input type="date" id="filtroDesdeEstado" class="form-control form-control-sm" value="<?= $desdeDefecto ?>" style="width:140px;">
    </div>
    <div>
        <label style="font-size:12px; font-weight:600; color:#555;">Hasta</label>
        <input type="date" id="filtroHastaEstado" class="form-control form-control-sm" value="<?= $hastaDefecto ?>" style="width:140px;">
    </div>
    <div>
        <label style="font-size:12px; font-weight:600; color:#555;">Tipo de Evento</label>
        <select id="filtroTipoEstado" class="form-select form-select-sm" style="width:160px;">
            <option value="Todos">Todos</option>
            <option value="REVISION_SST">Revisión SST</option>
            <option value="MANTENIMIENTO">Mantenimiento</option>
            <option value="PREOPERACIONAL">Preoperacional</option>
            <option value="TRASLADO_ADMIN">Traslado Admin</option>
            <option value="POST_SINIESTRO">Post Siniestro</option>
            <option value="OTRO">Otro</option>
        </select>
    </div>
    <button class="btn btn-sm btn-primary" onclick="filtrarHistorialEstado(<?= $vehiculo['idvehiculos'] ?>)">
        <i class="fas fa-search"></i> Filtrar
    </button>
</div>

<!-- Tabla de historial -->
<div id="historialEstadoTabla">
    <?php if (empty($eventos)): ?>
        <div class="alert alert-info">Sin eventos registrados en este período.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover" style="font-size:12px;">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Conductor</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Observación</th>
                        <th>Km</th>
                        <th>Foto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $ev): ?>
                        <?php
                            $estadoEv = $ev['estado_general'] ?? 'OPTIMO';
                            $claseEv = $badgeClase[$estadoEv] ?? 'bg-secondary';
                            $tipoEv = $labelTipo[$ev['tipo_evento']] ?? $ev['tipo_evento'];
                            $obsEv = $ev['observaciones'] ?? '';
                            $obsCorto = mb_strlen($obsEv) > 100 ? mb_substr($obsEv, 0, 100) . '…' : $obsEv;
                            $kmEv = number_format((int)($ev['kilometraje'] ?? 0), 0, ',', '.');
                            $fotoEv = !empty($ev['foto_evidencia']) || !empty($ev['img_kilometraje']);
                        ?>
                        <tr>
                            <td><?= date('d-m-Y H:i', strtotime($ev['fecha_registro'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($ev['conductor_nombre'] ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($tipoEv) ?></span></td>
                            <td><span class="<?= $claseEv ?>" style="display:inline-block; border-radius:20px; padding:2px 10px; font-weight:600; font-size:11px;"><?= htmlspecialchars($estadoEv) ?></span></td>
                            <td style="max-width:250px;" title="<?= htmlspecialchars($obsEv) ?>">
                                <?= htmlspecialchars($obsCorto) ?>
                                <?php if (mb_strlen($obsEv) > 100): ?>
                                    <br><small><a href="#" onclick="Swal.fire({title:'Observación', html:<?= json_encode(nl2br(htmlspecialchars($obsEv))) ?>, icon:'info'}); return false;">Ver más</a></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $kmEv ?></td>
                            <td class="text-center">
                                <?php if (!empty($ev['foto_evidencia'])): ?>
                                    <a href="<?= $appBasePath . '/' . htmlspecialchars($ev['foto_evidencia']) ?>" target="_blank" title="Ver foto evidencia">📷</a>
                                <?php endif; ?>
                                <?php if (!empty($ev['img_kilometraje'])): ?>
                                    <a href="<?= $appBasePath . '/' . htmlspecialchars($ev['img_kilometraje']) ?>" target="_blank" title="Ver foto odómetro">🖼️</a>
                                <?php endif; ?>
                                <?php if (!$fotoEv): ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
