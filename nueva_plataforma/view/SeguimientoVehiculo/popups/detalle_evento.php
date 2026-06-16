<?php
$v = $detalle['vehiculo'];
$historialKm = $detalle['historial_km'] ?? [];
$comparendosList = $detalle['comparendos'] ?? [];
$entregas = $detalle['entregas'] ?? [];
$revisiones = $detalle['revisiones'] ?? [];
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-truck"></i> Datos del Vehículo</h6>
        <table class="table table-sm table-bordered">
            <tr><th>Placa</th><td><?= htmlspecialchars($v['veh_placa']) ?></td></tr>
            <tr><th>Tipo</th><td><?= htmlspecialchars(($v['veh_tipo'] ?? '') . ' / ' . ($v['veh_tipov'] ?? '')) ?></td></tr>
            <tr><th>Marca</th><td><?= htmlspecialchars($v['veh_marca'] ?? '') ?></td></tr>
            <tr><th>Modelo</th><td><?= htmlspecialchars($v['veh_modelo'] ?? '') ?></td></tr>
            <tr><th>Color</th><td><?= htmlspecialchars($v['veh_color'] ?? '') ?></td></tr>
            <tr><th>Chasis</th><td><?= htmlspecialchars($v['veh_chasis'] ?? '') ?></td></tr>
            <tr><th>Motor</th><td><?= htmlspecialchars($v['veh_motor'] ?? '') ?></td></tr>
            <tr><th>Cilindraje</th><td><?= htmlspecialchars($v['veh_cilidraje'] ?? '') ?></td></tr>
            <tr><th>Propietario</th><td><?= htmlspecialchars($v['veh_dueño'] ?? '') ?></td></tr>
            <tr><th>Propiedad</th><td><?= htmlspecialchars($v['veh_propiedad'] ?? '') ?></td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6><i class="fas fa-id-card"></i> Conductor y Documentos</h6>
        <table class="table table-sm table-bordered">
            <tr><th>Conductor</th><td><?= htmlspecialchars($v['conductor_nombre'] ?? 'Sin asignar') ?></td></tr>
            <tr><th>Sede</th><td><?= htmlspecialchars($v['sede_conductor'] ?? '—') ?></td></tr>
            <tr><th>SOAT</th><td><?= !empty($v['veh_fechaseguro']) ? date('d-m-Y', strtotime($v['veh_fechaseguro'])) : '—' ?></td></tr>
            <tr><th>Tecnomecánica</th><td><?= !empty($v['veh_fechategnomecanica']) ? date('d-m-Y', strtotime($v['veh_fechategnomecanica'])) : '—' ?></td></tr>
            <tr><th>Mantenimiento</th><td><?= !empty($v['veh_fechamantenimiento']) ? date('d-m-Y', strtotime($v['veh_fechamantenimiento'])) : '—' ?></td></tr>
            <tr><th>Kilometraje</th><td><?= number_format((int)($v['veh_kilactual'] ?? 0), 0, ',', '.') ?> km</td></tr>
            <tr><th>Cambio Aceite c/km</th><td><?= htmlspecialchars($v['veh_aceitekil'] ?? '—') ?></td></tr>
            <tr><th>Último Cambio Aceite</th><td><?= htmlspecialchars($v['veh_kmalcambaceite'] ?? '—') ?> km</td></tr>
            <tr><th>Equipo Carretera</th><td><?= nl2br(htmlspecialchars($v['veh_equipo_carretera'] ?? '—')) ?></td></tr>
        </table>
    </div>
</div>

<hr>
<h6><i class="fas fa-history"></i> Últimos Registros de Kilometraje</h6>
<?php if (empty($historialKm)): ?>
    <p class="text-muted">Sin registros.</p>
<?php else: ?>
    <table class="table table-sm table-hover">
        <thead class="table-dark"><tr><th>Fecha</th><th>Fuente</th><th>Km</th><th>Detalle</th></tr></thead>
        <tbody>
            <?php foreach (array_slice($historialKm, 0, 10) as $h): ?>
            <tr>
                <td><?= date('d-m-Y H:i', strtotime($h['fecha'])) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($h['fuente']) ?></span></td>
                <td><?= number_format((int)($h['kilometraje'] ?? 0), 0, ',', '.') ?> km</td>
                <td><?= htmlspecialchars($h['detalle'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr>
<h6><i class="fas fa-clipboard-check"></i> Últimas Revisiones</h6>
<?php if (empty($revisiones)): ?>
    <p class="text-muted">Sin revisiones registradas.</p>
<?php else: ?>
    <table class="table table-sm table-hover">
        <thead class="table-dark"><tr><th>Fecha</th><th>Ingreso</th><th>Usuario</th><th>Registrado por</th></tr></thead>
        <tbody>
            <?php foreach ($revisiones as $r): ?>
            <tr>
                <td><?= date('d-m-Y', strtotime($r['rev_fecha'])) ?></td>
                <td><?= date('d-m-Y', strtotime($r['rev_ingreso'])) ?></td>
                <td><?= htmlspecialchars($r['rev_usuvehiculo']) ?></td>
                <td><?= htmlspecialchars($r['rev_usuregistra']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr>
<h6><i class="fas fa-handshake"></i> Últimas Entregas</h6>
<?php if (empty($entregas)): ?>
    <p class="text-muted">Sin entregas registradas.</p>
<?php else: ?>
    <table class="table table-sm table-hover">
        <thead class="table-dark"><tr><th>Fecha</th><th>Tipo</th><th>Usuario</th><th>Sede</th></tr></thead>
        <tbody>
            <?php foreach ($entregas as $e): ?>
            <tr>
                <td><?= date('d-m-Y', strtotime($e['ent_fechaentrega'])) ?></td>
                <td><?= $e['ent_tipoentrega'] === 'inicial' ? 'Empresa → Conductor' : 'Conductor → Empresa' ?></td>
                <td><?= htmlspecialchars($e['ent_idusuario'] ?? '') ?></td>
                <td><?= htmlspecialchars($e['ent_sede'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
