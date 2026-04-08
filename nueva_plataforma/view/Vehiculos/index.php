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
<link rel="stylesheet" href="../../assets/css/vehiculos.css">



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
                <h5 class="modal-title"> Agregar Vehículo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="formVehiculo" enctype="multipart/form-data">
                    <div class="row">
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Tipo Vehículo</label>
                            <select id="filtrotipodevehiculo" name="filtrotipodevehiculo" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="Moto">Moto</option>
                            <option value="Carro">Carro</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Marca</label>
                            <input type="text" class="form-control" name="marca" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Placa</label>
                            <input type="text" class="form-control" name="placa" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Modelo</label>
                            <input type="text" class="form-control" name="modelo" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Color</label>
                            <input type="text" class="form-control" name="color" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Tipo</label>
                            <input type="text" class="form-control" name="tipo" placeholder="">
                        </div>


                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Dueño</label>
                            <select id="Dueños" class="form-control">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($Dueños as $dueño): ?>
                            <option value="<?= $dueño['iddueños'] ?>"><?= $dueño['due_nombre'] ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅Fecha Seguro (SOAT)</label>
                            <input type="date" id="add_FechaSeguro" value="<?= date('Y-m-d') ?>" class="form-control" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷Foto Seguro (SOAT)</label>
                            <input type="file" id="add_FotoSeguro" name="veh_foto_seguro" class="form-control" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅Fecha Tecnomecanica</label>
                            <input type="date" id="add_FechaTecnomecanica" value="<?= date('Y-m-d') ?>" class="form-control" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷Foto Tecnomecanica</label>
                            <input type="file" id="add_FotoTecnomecanica" name="veh_foto_tecnomecanica" class="form-control" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅Fecha Cambio de aceite</label>
                            <input type="date" id="add_FechaCambioAceite" value="<?= date('Y-m-d') ?>" class="form-control" />
                        </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Actual</label>
                            <input type="text" class="form-control" name="km_actual" placeholder="">
                    </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Cambio Aceite</label>
                            <input type="text" class="form-control" name="km_cambio_aceite" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Numero Chasis</label>
                            <input type="text" class="form-control" name="numero_chasis" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Numero Motor</label>
                            <input type="text" class="form-control" name="numero_motor" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Numero Cilindraje</label>
                            <input type="text" class="form-control" name="numero_cilindraje" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Uso del Vehículo</label>
                            <input type="text" class="form-control" name="uso_vehiculo" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Actual</label>
                            <input type="text" class="form-control" name="km_actual" placeholder="">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Estado</label>
                            <select id="filtroestado" name="filtroestado" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="Inactivo">Inactivo</option>
                            <option value="Activo">Activo</option>
                            </select>
                        </div>
                        
                      <div class="row mb-3">
                        <div class="col-md-12 mb-3">
                            <label for="veh_especificaciones" class="form-label text-secondary font-weight-bold">Especificaciones Técnicas
                            </label>
                        <textarea 
                            class="form-control" 
                            id="veh_especificaciones" 
                            name="veh_especificaciones" 
                            rows="3" 
                            placeholder="Ingrese detalles técnicos, motor, estado general..."></textarea>
                      </div>

                      <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷Foto Tarjeta Propiedad (Frente)</label>
                            <input type="file" id="add_FotoTarjetaPropiedadFrente" name="veh_foto_tarjeta_propiedad_frente" class="form-control" />
                      </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷Foto Tarjeta Propiedad (Respaldo)</label>
                            <input type="file" id="add_FotoTarjetaPropiedadRespaldo" name="veh_foto_tarjeta_propiedad_respaldo" class="form-control" />
                        </div>

                    </div>
                
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="btnGuardar">
                    Guardar
                </button>
            </div>
                </form>
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
   

<script src="../../assets/js/vehiculos.js"></script>



</body>
</html>
