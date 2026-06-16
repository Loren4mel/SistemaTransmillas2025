<h6>Historial de Kilometraje — <?= htmlspecialchars($vehiculo['veh_placa']) ?></h6>
<hr>

<?php if (empty($historial)): ?>
    <div class="alert alert-warning">No hay registros de kilometraje para este vehículo.</div>
<?php else: ?>
    <table class="table table-sm table-hover">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Fuente</th>
                <th>Kilometraje</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historial as $h): ?>
            <tr>
                <td><?= date('d-m-Y H:i', strtotime($h['fecha'])) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($h['fuente']) ?></span></td>
                <td><strong><?= number_format((int)($h['kilometraje'] ?? 0), 0, ',', '.') ?> km</strong></td>
                <td><?= htmlspecialchars($h['detalle'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
