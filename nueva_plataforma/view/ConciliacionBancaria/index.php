<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conciliacion bancaria</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/conciliacion-bancaria.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container-fluid py-4 conciliacion-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Conciliacion bancaria</h1>
            <p class="text-muted mb-0">Movimientos, guias y facturas de Davivienda y Bancolombia en un solo modulo.</p>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="history.back()">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header conciliacion-header">
            <ul class="nav nav-pills banco-tabs" id="bancoTabs" role="tablist">
                <?php foreach ($bancos as $key => $nombre): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $key === 'davivienda' ? 'active' : '' ?>"
                                type="button"
                                data-banco="<?= htmlspecialchars($key) ?>">
                            <?= htmlspecialchars($nombre) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <ul class="nav nav-tabs estado-tabs" id="estadoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" type="button" data-estado="pendientes">
                            Pendientes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" data-estado="facturas">
                            Historico facturas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" data-estado="guias">
                            Historico guias
                        </button>
                    </li>
                </ul>

                <div class="acciones-pendientes d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="btnAsignarFacturas">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Actualizar factura
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAsignarGuias">
                        <i class="fas fa-route me-1"></i> Actualizar guia
                    </button>
                </div>
            </div>

            <div class="row g-3 conciliacion-sections">
                <div class="col-12">
                    <div class="section-panel">
                        <button class="section-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMovimientos" aria-expanded="true" aria-controls="collapseMovimientos">
                            <span>
                                <strong id="tituloMovimientos">Movimientos pendientes</strong>
                                <small class="text-muted">Seleccione uno o varios registros para cruzarlos.</small>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="collapse show" id="collapseMovimientos">
                            <div class="table-responsive section-body">
                                <table id="tablaMovimientos" class="table table-hover table-bordered align-middle w-100">
                                    <thead>
                                    <tr>
                                        <th class="text-center">Sel.</th>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Descripcion</th>
                                        <th>Documento</th>
                                        <th>Canal</th>
                                        <th>Referencia</th>
                                        <th>Nit / Ref. 2</th>
                                        <th>Valor</th>
                                        <th>Factura</th>
                                        <th>Guia</th>
                                        <th>Confirmar</th>
                                        <th>Remover</th>
                                        <th>Eliminar</th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 pendientes-only">
                    <div class="section-panel">
                        <button class="section-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGuias" aria-expanded="false" aria-controls="collapseGuias">
                            <span>
                                <strong>Guias sin cruzar</strong>
                                <small class="text-muted">Pagos de cuenta pendientes por verificar.</small>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="collapse" id="collapseGuias">
                            <div class="table-responsive section-body">
                                <table id="tablaGuias" class="table table-hover table-bordered align-middle w-100">
                                    <thead>
                                    <tr>
                                        <th class="text-center">Sel.</th>
                                        <th>Guia</th>
                                        <th>Fecha</th>
                                        <th>Operador</th>
                                        <th>Valor</th>
                                        <th>Foto</th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 pendientes-only">
                    <div class="section-panel">
                        <button class="section-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFacturas" aria-expanded="false" aria-controls="collapseFacturas">
                            <span>
                                <strong>Facturas sin cruzar</strong>
                                <small class="text-muted">Facturas en estado facturado y pago pendiente.</small>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="collapse" id="collapseFacturas">
                            <div class="table-responsive section-body">
                                <table id="tablaFacturas" class="table table-hover table-bordered align-middle w-100">
                                    <thead>
                                    <tr>
                                        <th class="text-center">Sel.</th>
                                        <th>Factura</th>
                                        <th>Fecha</th>
                                        <th>Credito</th>
                                        <th>Nit</th>
                                        <th>Valor</th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header conciliacion-header">
                <h5 class="modal-title">Confirmar transferencia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formConfirmarPago" enctype="multipart/form-data">
                    <input type="hidden" id="confirmarGuias" name="guias">

                    <div class="mb-3">
                        <label class="form-label">Guia(s)</label>
                        <input type="text" class="form-control" id="confirmarGuiasTexto" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Numero de transaccion</label>
                        <input type="text" class="form-control" name="numero_transaccion" id="numeroTransaccion" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Valor confirmado</label>
                        <input type="text" class="form-control" name="valor_confirmado" id="valorConfirmado" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Comprobante</label>
                        <input type="file" class="form-control" name="comprobante" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarConfirmacion">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="../assets/js/conciliacion-bancaria.js"></script>
</body>
</html>
