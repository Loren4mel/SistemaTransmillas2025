<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vehiculos</title>
<!-- Bootstrap 5 CSS desde CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/vehiculos.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  
<div class="d-flex justify-content-between align-items-center mb-3">
    <button class="btn btn-sm btn-light border">Volver</button>
    
    <button type="button" class="btn btn-primary" id="btnNuevo" 
        data-bs-toggle="modal" 
        data-bs-target="#modalVehiculo">
    <i class="fas fa-plus me-1"></i> Agregar Vehículo
    </button>
</div>  
<div class="card shadow p-3 mb-4 bg-body rounded">
  <div class="card-header mi-header d-flex align-items-center justify-content-between">
    <h3 class="mb-0">
      <i class="fas fa-car me-2"></i> Vehiculos
    </h3>
  </div>
    <div class="card-body">
      <div class="row mb-3 align-items-end">
          <div class="col-md-4">
            <label for="filtrotipodevehiculo">Tipo de Vehiculo</label>
            <select id="filtrotipodevehiculo" name="filtrotipodevehiculo" class="form-control">
              <option value="">Seleccionar...</option>
              <option value="Moto">Moto</option>
              <option value="Carro">Carro</option>
            </select>
          </div>

          <div class="col-md-4">
            <label for="filtroestado">Estado</label>
            <select id="filtroestado" name="filtroestado" class="form-control">
              <option value="">Seleccionar...</option>
              <option value="Inactivo">Inactivo</option>
              <option value="Activo">Activo</option>
            </select>
          </div>
      </div>

      <div class="table-responsive">
        <table id="tablaVehiculos" class="table table-hover table-bordered align-middle text-center">
          <thead class="thead-modern">
            <tr>
                <th>Tipo Vehiculo</th>
                <th>Marca</th>
                <th>Placa</th>
                <th>Modelo</th>
                <th>Dueño</th>
                <th>Fecha Seguro</th>
                <th>Fecha Tecnomecánica</th>
                <th>Fecha Cambio Aceite</th>
                <th>Km Actual</th>
                <th>Km Cambio Aceite</th>
                <th>Tarjeta de Propiedad (Frente)</th>
                <th>Tarjeta de Propiedad (Respaldo)</th>
                <th>Estado</th>
                <th>Editar</th>
                <th>Eliminar</th>

            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
      
<!-- MODAL AGREGAR VEHÍCULO -->
<div class="modal fade" id="modalVehiculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">Agregar Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="formVehiculo" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Tipo Vehículo</label>
                            <select name="veh_tipo" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Moto">Moto</option>
                                <option value="Carro">Carro</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Marca</label>
                            <input type="text" class="form-control" name="veh_marca" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Placa</label>
                            <input type="text" class="form-control" name="veh_placa" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Modelo</label>
                            <input type="text" class="form-control" name="veh_modelo" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Color</label>
                            <input type="text" class="form-control" name="veh_color" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Tipo</label>
                            <input type="text" class="form-control" name="veh_tipov" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Dueño</label>
                            <select name="veh_dueno" class="form-control">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($Dueños as $dueño): ?>
                                    <option value="<?= $dueño['iddueños'] ?>"><?= $dueño['due_nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Seguro (SOAT)</label>
                            <input type="date" name="veh_fecha_soat" value="<?= date('Y-m-d') ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Seguro (SOAT)</label>
                            <input type="file" name="veh_img_soat" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Tecnomecánica</label>
                            <input type="date" name="veh_fecha_tecnomecanica" value="<?= date('Y-m-d') ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tecnomecánica</label>
                            <input type="file" name="veh_img_tecnomecanica" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Cambio de Aceite</label>
                            <input type="date" name="veh_fecha_aceite" value="<?= date('Y-m-d') ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Actual</label>
                            <input type="text" class="form-control" name="veh_kilactual" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Cambio Aceite</label>
                            <input type="text" class="form-control" name="veh_calkmcambioaceite" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Chasis</label>
                            <input type="text" class="form-control" name="veh_chasis" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Motor</label>
                            <input type="text" class="form-control" name="veh_motor" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Cilindraje</label>
                            <input type="text" class="form-control" name="veh_cilidraje" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Uso del Vehículo</label>
                            <input type="text" class="form-control" name="veh_usuve">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Estado</label>
                            <select name="veh_estado" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="0">Inactivo</option>
                                <option value="1">Activo</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Especificaciones Técnicas</label>
                            <textarea class="form-control" name="veh_especificaciones" rows="3"
                                placeholder="Ingrese detalles técnicos, motor, estado general..."></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Frente)</label>
                            <input type="file" name="veh_img_anverso" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Respaldo)</label>
                            <input type="file" name="veh_img_reverso" class="form-control">
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formVehiculo" class="btn btn-primary" id="btnGuardar">Guardar</button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL EDITAR VEHÍCULO -->
<div class="modal fade" id="modalEditarVehiculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
 
            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">Editar Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
 
            <div class="modal-body">
                <form id="formEditarVehiculo" enctype="multipart/form-data">
                    <!-- Campo oculto con el ID del vehículo a editar -->
                    <input type="hidden" name="veh_id" id="edit_veh_id">
 
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Tipo Vehículo</label>
                            <select name="veh_tipo" id="edit_veh_tipo" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Moto">Moto</option>
                                <option value="Carro">Carro</option>
                            </select>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Marca</label>
                            <input type="text" class="form-control" name="veh_marca" id="edit_veh_marca" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Placa</label>
                            <input type="text" class="form-control" name="veh_placa" id="edit_veh_placa" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Modelo</label>
                            <input type="text" class="form-control" name="veh_modelo" id="edit_veh_modelo" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Color</label>
                            <input type="text" class="form-control" name="veh_color" id="edit_veh_color" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Tipo</label>
                            <input type="text" class="form-control" name="veh_tipov" id="edit_veh_tipov" required>
                        </div>
 
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Dueño</label>
                            <select name="veh_dueno" id="edit_veh_dueno" class="form-control">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($Dueños as $dueño): ?>
                                    <option value="<?= $dueño['iddueños'] ?>"><?= $dueño['due_nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Seguro (SOAT)</label>
                            <input type="date" name="veh_fecha_soat" id="edit_veh_fecha_soat" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Seguro (SOAT)</label>
                            <!-- Vista previa de imagen actual -->
                            <div id="preview_soat" class="mb-1"></div>
                            <input type="file" name="veh_img_soat" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Tecnomecánica</label>
                            <input type="date" name="veh_fecha_tecnomecanica" id="edit_veh_fecha_tecnomecanica" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tecnomecánica</label>
                            <div id="preview_tecnomecanica" class="mb-1"></div>
                            <input type="file" name="veh_img_tecnomecanica" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Cambio de Aceite</label>
                            <input type="date" name="veh_fecha_aceite" id="edit_veh_fecha_aceite" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Actual</label>
                            <input type="text" class="form-control" name="veh_kilactual" id="edit_veh_kilactual" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Cambio Aceite</label>
                            <input type="text" class="form-control" name="veh_calkmcambioaceite" id="edit_veh_calkmcambioaceite" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Chasis</label>
                            <input type="text" class="form-control" name="veh_chasis" id="edit_veh_chasis" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Motor</label>
                            <input type="text" class="form-control" name="veh_motor" id="edit_veh_motor" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Cilindraje</label>
                            <input type="text" class="form-control" name="veh_cilidraje" id="edit_veh_cilidraje" required>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Uso del Vehículo</label>
                            <input type="text" class="form-control" name="veh_usuve" id="edit_veh_usuve">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Estado</label>
                            <select name="veh_estado" id="edit_veh_estado" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="0">Inactivo</option>
                                <option value="1">Activo</option>
                            </select>
                        </div>
 
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Especificaciones Técnicas</label>
                            <textarea class="form-control" name="veh_especificaciones" id="edit_veh_especificaciones" rows="3"
                                placeholder="Ingrese detalles técnicos, motor, estado general..."></textarea>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Frente)</label>
                            <div id="preview_anverso" class="mb-1"></div>
                            <input type="file" name="veh_img_anverso" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Respaldo)</label>
                            <div id="preview_reverso" class="mb-1"></div>
                            <input type="file" name="veh_img_reverso" class="form-control">
                        </div>
                    </div>
 
                </form>
            </div>
 
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formEditarVehiculo" class="btn btn-primary" id="btnActualizar">Actualizar</button>
            </div>
 
        </div>
    </div>
</div>


<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <!-- ✅ DataTables desde CDN -->
   

<script src="../assets/js/vehiculos.js"></script>



</body>
</html>
