<h6>Comparendos — <?= htmlspecialchars($vehiculo['veh_placa']) ?></h6>
<hr>

<?php if (empty($comparendos)): ?>
    <div class="alert alert-success">No hay comparendos registrados para este vehículo.</div>
<?php else: ?>
    <table class="table table-sm table-hover">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>N° Comparendo</th>
                <th>Estado</th>
                <th>Valor</th>
                <th>Titular</th>
                <th>Conductor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comparendos as $c): ?>
            <tr>
                <td><?= date('d-m-Y', strtotime($c['com_fecha'])) ?></td>
                <td><?= htmlspecialchars($c['com_numerocompa']) ?></td>
                <td>
                    <?php if ($c['com_estado'] === 'Pagado'): ?>
                        <span class="badge bg-success">Pagado</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Pendiente</span>
                    <?php endif; ?>
                </td>
                <td>$ <?= htmlspecialchars($c['com_valor']) ?></td>
                <td><?= htmlspecialchars($c['com_titularcompa']) ?></td>
                <td><?= htmlspecialchars($c['operador_nombre'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
