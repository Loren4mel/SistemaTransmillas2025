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
<link rel="stylesheet" href="/SistemaTransmillas2025/nueva_plataforma/assets/css/vehiculos.css">
<link rel="shortcut icon" href="/SistemaTransmillas2025/images/Logo Google Nuevo.png">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<div class="container-fluid py-4"> 

    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-sm btn-light border" onclick="history.back()">Volver</button>

        <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" id="btnNuevoComparendo"
        data-bs-toggle="modal"
        data-bs-target="#modalAgregarComparendo">
        <i class="fas fa-plus me-1"></i>Agregar Comparendo
        </button>    
        <button type="button" class="btn btn-primary" id="btnEntrega" 
                data-bs-toggle="modal" 
                data-bs-target="#modalEntregaVehiculo">
            <i class="fas fa-plus me-1"></i> Registrar Entrega
        </button>
        
        <button type="button" class="btn btn-primary" id="btnNuevo" 
                data-bs-toggle="modal" 
                data-bs-target="#modalVehiculo">
            <i class="fas fa-plus me-1"></i> Agregar Vehículo
        </button>
        </div>
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
              <option value="0">Inactivo</option>
              <option value="1" selected >Activo</option>
            </select>
        </div>

        <div class="col-md-4">
            <label for="filtropropiedad">Propiedad Vehículo</label>
            <select id="filtropropiedad" name="filtropropiedad" class="form-control">
              <option value="">Seleccionar...</option>
              <option value="empresa" selected>Empresa</option>
              <option value="propio">Propio</option>
            </select>
        </div>
    </div>

      <!-- Tabla de Vehículos -->
      <div class="table-responsive">
        <table id="tablaVehiculos" class="table table-hover table-bordered align-middle text-center">
          <thead class="thead-modern">
            <tr>
                <th>Tipo Vehiculo</th>
                <th>Marca</th>
                <th>Placa</th>
                <th>Modelo</th>
                <th>Propiedad Vehiculo</th>
                <th>Dueño</th>
                <th>Fecha Vencimiento del Seguro</th>
                <th>Fecha Vencimiento Tecnomecánica</th>
                <th>Fecha Ultimo Cambio Aceite</th>
                <th>Kilometraje Actual</th>
                <th>Km Al Cambio de Aceite</th>
                <th>Limite Km cambio aceite</th>
                <th>Tarjeta de Propiedad (Frente)</th>
                <th>Tarjeta de Propiedad (Respaldo)</th>
                <th>Revision Comparendos</th>
                <th>Comparendos</th>
                <th>Historial Entrega</th>
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
                            <label class="form-label fw-bold text-secondary">Tipo Vehículo
                                <span class="text-danger">*</span>
                            </label>
                            <select name="veh_tipo" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Moto">Moto</option>
                                <option value="Carro">Carro</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Marca 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_marca" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Placa 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_placa" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Modelo 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_modelo" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Color 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_color" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Tipo 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_tipov" required>
                        </div>
    
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Propiedad Vehiculo 
                            </label>
                            <select name="veh_propiedad" id="veh_propiedad" class="form-control">
                                 <option value="">Seleccionar...</option>
                                 <option value="empresa">Empresa</option>
                                 <option value="propio">Propio</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3" id="wrapper_dueno_agregar">
                            <label class="form-label fw-bold text-secondary">Dueño 
                            </label>
                            <select name="veh_dueno" id="veh_dueno" class="form-control">
                                 <option value="">Seleccionar...</option>
                                    <?php foreach ($Dueños as $dueño): ?>
                                 <option value="<?= $dueño['iddueños'] ?>" 
                                 data-nombre="<?= strtolower($dueño['due_nombre']) ?>">
                                 <?= $dueño['due_nombre'] ?>
                                 </option>
                                    <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Kilometraje Actual
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_kilactual" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Vencimiento Seguro (SOAT)</label>
                            <span class="text-danger">*</span>
                            <input type="date" name="veh_fechaseguro" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Vigente Seguro (SOAT) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="veh_img_soat" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Vencimiento Tecnomecánica</label>
                            <span class="text-danger">*</span>
                            <input type="date" name="veh_fechategnomecanica" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Vigente Tecnomecánica 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="veh_img_tecnomecanica" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Ultimo Cambio de Aceite</label>
                            <span class="text-danger">*</span>
                            <input type="date" name="veh_fechamantenimiento" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Kilometraje Actual Al Cambio de Aceite
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_kmactual_cambioaceite" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Limite Km cambio aceite 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_calkmcambioaceite" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Chasis 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_chasis" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Motor 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_motor" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Cilindraje 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_cilidraje" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Uso del Vehículo 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_usuve" required>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Estado 
                                <span class="text-danger">*</span>
                            </label>
                            <select name="veh_estado" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="0">Inactivo</option>
                                <option value="1">Activo</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto actual del vehiculo (Frente) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="veh_img_actual_frente" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto actual del vehiculo (Respaldo) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="veh_img_actual_trasera" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>

                        <!-- SECCIÓN EQUIPO DE CARRETERA -->
                        <div class="col-md-12 mb-3">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded"
                             style="background-color: #1a3a5c;">
                            <span class="text-white fw-bold">
                                <i class="fas fa-toolbox me-2"></i> Equipo de Prevención y Seguridad Vial
                            </span>
                            <button type="button" class="btn btn-sm btn-light" id="btnAgregarHerramienta">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>

                        <div id="listaHerramientas" class="mt-2">
                        </div>

                        <!-- Campo oculto donde se guarda el JSON -->
                        <input type="hidden" name="veh_equipo_carretera" id="veh_equipo_carretera">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Especificaciones Técnicas</label>
                            <textarea class="form-control" name="veh_especificaciones" rows="3"
                                placeholder="Ingrese detalles técnicos, motor, estado general..."></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
    <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Frente) 
        <span class="text-danger">*</span>
    </label>
    <input type="file" name="veh_img_anverso" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
</div>

<div class="col-md-6 mb-3">
    <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Respaldo) 
        <span class="text-danger">*</span>
    </label>
    <input type="file" name="veh_img_reverso" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
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
                            <label class="form-label fw-bold text-secondary">Propiedad Vehículo </label>
                            <select name="veh_propiedad" id="edit_veh_propiedad" class="form-control">
                               <option value="">Seleccionar...</option>
                               <option value="empresa">Empresa</option>
                               <option value="propio">Propio</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3" id="wrapper_dueno_editar">
                            <label class="form-label fw-bold text-secondary">Dueño</label>
                            <select name="veh_dueno" id="edit_veh_dueno" class="form-control">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($Dueños as $dueño): ?>
                                <option value="<?= $dueño['iddueños'] ?>"
                                data-nombre="<?= strtolower($dueño['due_nombre']) ?>">
                                <?= $dueño['due_nombre'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
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
 
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Estado</label>
                            <select name="veh_estado" id="edit_veh_estado" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="0">Inactivo</option>
                                <option value="1">Activo</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto actual del vehiculo (Frente)</label>
                            <div id="preview_actual_frente" class="mb-1"></div>   
                            <input type="file" name="veh_img_actual_frente" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto actual del vehiculo (Respaldo)</label>
                            <div id="preview_actual_trasera" class="mb-1"></div>
                            <input type="file" name="veh_img_actual_trasera" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                        <!-- SECCIÓN EQUIPO DE CARRETERA (EDITAR) -->
                        <div class="col-md-12 mb-3">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded"
                             style="background-color: #1e4d78;">
                            <span class="text-white fw-bold">
                               <i class="fas fa-toolbox me-2"></i> Equipo de Prevención y Seguridad Vial
                            </span>
                            <button type="button" class="btn btn-sm btn-light" id="btnAgregarHerramientaEdit">
                               <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>

                        <div id="listaHerramientasEdit" class="mt-2">
                        </div>

                        <input type="hidden" name="veh_equipo_carretera" id="edit_veh_equipo_carretera">
                        </div>
 
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Especificaciones Técnicas</label>
                            <textarea class="form-control" name="veh_especificaciones" id="edit_veh_especificaciones" rows="3"
                                placeholder="Ingrese detalles técnicos, motor, estado general..."></textarea>
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Frente)</label>
                            <div id="preview_anverso" class="mb-1"></div>
                            <input type="file" name="veh_img_anverso" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Respaldo)</label>
                            <div id="preview_reverso" class="mb-1"></div>
                            <input type="file" name="veh_img_reverso" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
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

<!-- MODAL REGISTRAR ENTREGA DE VEHÍCULO -->
<div class="modal fade" id="modalEntregaVehiculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">Registrar Entrega de Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" 
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="col-md-12 mb-3 px-3">
            <label class="form-label fw-bold text-secondary">
            Tipo de Entrega <span class="text-danger">*</span>
            </label>
            <select class="form-control" id="ent_tipoentrega" name="ent_tipoentrega" required>
                <option value="">Seleccionar...</option>
                <option value="inicial">Inicial </option>
                <option value="final">Final </option>
            </select>
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Inicial:</strong> cuando el conductor recibe el vehículo al vincularse. 
                <strong>Final:</strong> cuando lo devuelve al retirarse.
            </small>
            </div>

            <div class="modal-body">
                <form id="formEntregaVehiculo">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Vehículo / Placa 
                                <span class="text-danger">*</span></label>
                            <select name="ent_vehiculo_id" id="ent_vehiculo_id" 
                                    class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($Vehiculos as $v): ?>
                                <option value="<?= $v['idvehiculos'] ?>"
                                    data-texto="<?= $v['veh_tipo'] ?> <?= $v['veh_placa'] ?> <?= $v['veh_marca'] ?> <?= $v['veh_modelo'] ?>">
                                    <?= $v['veh_placa'] ?> — <?= $v['veh_marca'] ?> <?= $v['veh_modelo'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label id="label_recibido" class="form-label fw-bold text-secondary">Recibido por</label>
                        
                        <div id="wrapper_recibido_fijo">
                            <input type="text" class="form-control bg-light" 
                             value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Sin sesión') ?>" readonly>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>Usuario actualmente logueado
                            </small>
                            <input type="hidden" name="ent_userregistra" 
                             value="<?= htmlspecialchars($_SESSION['usuario_nombre']) ?>">
                        </div>

                        <div id="wrapper_recibido_conductor" style="display:none;">
                            <select class="form-control" id="ent_idusuario_recibe" name="ent_idusuario_recibe">
                            <option value="">Seleccionar conductor...</option>
                                <?php foreach ($operadoresActivos as $op): ?>
                            <option value="<?= $op['idusuarios'] ?>">
                            <?= htmlspecialchars($op['usu_nombre']) ?>
                            </option>
                               <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                        <div class="col-md-6 mb-3">
                        <label id="label_entregado" class="form-label fw-bold text-secondary">
                        Entregado <span class="text-danger">*</span>
                        </label>

                        <div id="wrapper_entregado_conductor">
                            <select class="form-control" id="ent_idusuario" name="ent_idusuario">
                            <option value="">Seleccionar conductor...</option>
                            <?php foreach ($operadoresActivos as $op): ?>
                            <option value="<?= $op['idusuarios'] ?>">
                            <?= htmlspecialchars($op['usu_nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="wrapper_entregado_fijo" style="display:none;">
                            <input type="text" class="form-control bg-light" 
                                value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Sin sesión') ?>" readonly>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>Usuario actualmente logueado
                            </small>
                        </div>
                    </div>
                    
                        <div class="col-md-6 mb-3" id="wrapper_sede_agregar">
                            <label class="form-label fw-bold text-secondary">Sede
                                <span class="text-danger">*</span></label>
                            <select name="ent_sede" id="ent_sede" class="form-control" required>
                                   <option value="">Seleccionar...</option>
                                   <?php foreach ($Sedes as $sede): ?>
                                   <option value="<?= $sede['sed_nombre'] ?>">
                                   <?= htmlspecialchars($sede['sed_nombre']) ?>
                                   </option>
                                   <?php endforeach; ?>
                            </select>   
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Entrega Vehículo 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control"
                                   name="ent_fechaentrega"
                                   value="<?= date('Y-m-d') ?>"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Registro 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control"
                                   name="ent_fecharegist"
                                   value="<?= date('Y-m-d') ?>"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto actual del vehiculo (Frente) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="ent_img_frente" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto actual del vehiculo (Respaldo) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="ent_img_respaldo" class="form-control" required>
                        </div>

                        <div class="col-md-12 mb-3 px-3">
                            <label class="form-label fw-bold text-secondary">
                                <i class="fas fa-video me-1"></i> Video del vehículo
                                <span class="text-muted fw-normal" style="font-size:12px;">
                                (máx. 3 minutos)
                                </span>
                            </label>
                                <input type="file" 
                                       id="ent_video_file" 
                                       name="ent_video_file" 
                                       class="form-control" 
                                       accept="video/*">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 id="ent_video_progress_bar" 
                                 role="progressbar" style="width:0%"></div>
                            </div>
                        </div>
                        <div id="ent_video_preview" class="mt-2" style="display:none;">
                            <video controls style="max-width:100%; border-radius:6px; border:1px solid #dee2e6; max-height:200px;">
                                <source id="ent_video_preview_src" src="" type="video/mp4">
                            </video>
                            <small class="text-muted d-block mt-1" id="ent_video_url_text"></small>
                        </div>
                            <input type="hidden" id="ent_video_url" name="ent_video_url">
                        </div>

                        <!-- SECCIÓN EQUIPO DE CARRETERA -->
                        <div class="col-md-12 mb-3">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded"
                             style="background-color: #1a3a5c;">
                            <span class="text-white fw-bold">
                                <i class="fas fa-toolbox me-2"></i> Equipo de Prevención y Seguridad Vial
                            </span>
                            <button type="button" class="btn btn-sm btn-light" id="btnAgregarHerramientaEntrega">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>

                        <div id="listaHerramientasEntrega" class="mt-2">
                        </div>

                        <input type="hidden" name="ent_equipo_carretera" id="ent_equipo_carretera">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Observaciones</label>
                            <textarea class="form-control" name="ent_observaciones"
                                      rows="3"
                                      placeholder="Detalles adicionales sobre la entrega..."></textarea>
                        </div>

                        <!-- FIRMA DIGITAL -->
                        <div class="col-md-12 mb-3">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded"
                             style="background-color: #1a3a5c;">
                            <span class="text-white fw-bold">
                            <i class="fas fa-signature me-2"></i> Firma del Conductor
                            </span>
                        <button type="button" class="btn btn-sm btn-light" id="btnLimpiarFirmaEntrega">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                        </div>
                            <small class="text-muted ms-1">Firme dentro del recuadro para confirmar la entrega.</small>
                            <canvas id="firmaEntregaCanvas" 
                                    style="border: 2px dashed #bfc9d4; border-radius: 6px; width: 100%; 
                                    height: 200px; background: #fff; cursor: crosshair; touch-action: none; display:block;">
                            </canvas>
                        <input type="hidden" id="ent_firma_base64" name="ent_firma_base64">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" 
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEntrega">
                     Guardar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL AGREGAR COMPARENDO -->
<div class="modal fade" id="modalAgregarComparendo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title"> Agregar Comparendo</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="formComparendo" enctype="multipart/form-data">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Operador (Recibido por) <span class="text-danger">*</span>
                            </label>
                            <select name="com_operador_id" id="com_operador_id"
                                    class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($operadoresActivos as $op): ?>
                                <option value="<?= $op['idusuarios'] ?>">
                                    <?= htmlspecialchars($op['usu_nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Vehículo <span class="text-danger">*</span>
                            </label>
                            <select name="com_vehiculo_id" id="com_vehiculo_id"
                                    class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($Vehiculos as $v): 
                                     if ($v['veh_estado'] != 1) continue;
                                     if ($v['veh_propiedad'] !== 'empresa') continue;
                                ?>
                                <option value="<?= $v['idvehiculos'] ?>">
                                <?= $v['veh_placa'] ?> — <?= $v['veh_marca'] ?> <?= $v['veh_modelo'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Estado Comparendo <span class="text-danger">*</span>
                            </label>
                            <select name="com_estado" id="com_estado"
                                    class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Pagado">Pagado</option>
                                <option value="Pendiente">Pendiente</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📅 Fecha Comparendo <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="com_fecha" id="com_fecha"
                                   class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Valor Comparendo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="com_valor" id="com_valor" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Número Comparendo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="com_numerocompa" id="com_numerocompa" required>
                            <small class="text-muted">
                                 <i class="fas fa-info-circle me-1"></i>
                                 Ingrese el numero que identifica el comparendo
                            </small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Titular Comparendo <span class="text-danger">*</span>
                            </label>
                            <select name="com_titularcompa" id="com_titularcompa"
                                    class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Operador">Operador</option>
                                <option value="Empresa">Empresa</option>
                            </select>
                        </div>


                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📷 Foto Comparendo
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="com_foto" id="com_foto"
                                   class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div> 

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📷 Foto Curso de sensibilización vial
                            </label>
                            <input type="file" name="com_foto_curso" id="com_foto_curso"
                                   class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                        <div class="col-md-12 mb-2">
                            <div class="alert alert-info py-2 px-3 mb-0" id="infoHojaVida" style="display:none;">
                                <i class="fas fa-id-card me-2"></i>
                                <span id="textoHojaVida"></span>
                            </div>
                        </div>

                    </div>  
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarComparendo">
                    Guardar 
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL CARGAR REVISIÓN DE COMPARENDOS -->
<div class="modal fade" id="modalCargarRevision" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Cargar Revisión de Comparendos
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formCargarRevision" enctype="multipart/form-data">
                    <input type="hidden" id="rev_vehiculo_id" name="rev_vehiculo_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">
                            Vehículo
                        </label>
                        <input type="text" id="rev_vehiculo_texto"
                               class="form-control bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">
                            📅 Fecha de Consulta <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="rev_fecha_consulta"
                               id="rev_fecha_consulta"
                               class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">
                            📷 Evidencia / Pantallazo <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="rev_evidencia"
                               id="rev_evidencia"
                               class="form-control"
                               accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Sube el pantallazo de la consulta en SIMIT u otra entidad.
                        </small>
                    </div>

                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary"
                        id="btnGuardarRevision">
                        Guardar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL HISTORIAL DE REVISIONES -->
<div class="modal fade" id="modalHistorialRevisiones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Historial de Revisiones —
                    <span id="tituloPlacaRevision" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="thead-modern">
                            <tr>
                                <th>#</th>
                                <th>Fecha Consulta</th>
                                <th>Evidencia</th>
                                <th>Usuario</th>
                                <th>Fecha Registro</th>   
                                <th>Eliminar</th>                             
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaRevisiones">
                            <tr>
                                <td colspan="6" class="text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL VER COMPARENDOS DEL VEHÍCULO -->
<div class="modal fade" id="modalVerComparendos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Comparendos del Vehículo: <span id="tituloPlacaComparendo"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tablaComparendos"
                           class="table table-hover table-bordered align-middle text-center">
                        <thead class="thead-modern">
                            <tr>
                                <th>#</th>
                                <th>Operador</th>
                                <th>Vehículo</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Valor</th>
                                <th>Número</th>
                                <th>Titular Comparendo</th>
                                <th>Foto Comparendo</th>
                                <th>Foto Curso</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaComparendos">
                            <tr>
                                <td colspan="6" class="text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR COMPARENDO -->
<div class="modal fade" id="modalEditarComparendo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Editar Comparendo
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="formEditarComparendo" enctype="multipart/form-data">
                    <input type="hidden" id="edit_com_id" name="com_id">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Estado Comparendo 
                            </label>
                            <select name="com_estado" id="edit_com_estado" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Pagado">Pagado</option>
                                <option value="Pendiente">Pendiente</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Valor Comparendo 
                            </label>
                            <input type="text" class="form-control" name="com_valor"
                                   id="edit_com_valor" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Numero Comparendo 
                            </label>
                            <input type="text" class="form-control" name="com_numerocompa"
                                   id="edit_com_numerocompa" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Titular Comparendo 
                            </label>
                            <select name="com_titularcompa" id="edit_com_titularcompa"
                                    class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="Operador">Operador</option>
                                <option value="Empresa">Empresa</option>
                            </select>
                        </div>  

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📷 Foto Comparendo
                            </label>
                            <div id="preview_edit_com_foto" class="mb-2"></div>
                            <input type="file" name="com_foto" id="edit_com_foto"
                                   class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📷 Foto Curso de Sensibilización Vial
                            </label>
                            <div id="preview_edit_com_foto_curso" class="mb-2"></div>
                            <input type="file" name="com_foto_curso" id="edit_com_foto_curso"
                                   class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEditarComparendo">
                        Guardar Cambios
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL INFORMACION INCOMPLETA -->
<div class="modal fade" id="modalInfoIncompleta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header mi-header text-white">
                <h6 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Campos faltantes - <span id="placaInfoIncompleta"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white" 
                        data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush" id="listaCamposFaltantes"></ul>
            </div>
            <div class="modal-footer bg-light py-2">
                <small class="text-muted">Complete la información editando el vehículo</small>
            </div>
        </div>
    </div>
</div>

<!-- MODAL HISTORIAL DE CONDUCTORES POR VEHÍCULO -->
<div class="modal fade" id="modalHistorialConductores" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Historial de Conductores -
                    <span id="tituloPlacaHistorial" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted"></small>
                    <span id="historial_total_registros" class="badge bg-secondary fs-6"></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="thead-modern">
                            <tr>
                                <th>#</th>
                                <th>Tipo Entrega</th>
                                <th>Conductor</th>
                                <th>Recibido por</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Final</th>
                                <th>Sede</th>
                                <th>Equipo de Prevención y Seguridad Vial</th>
                                <th>Foto Frente</th>
                                <th>Foto Respaldo</th>
                                <th>Firma</th>
                                <th>Video</th>
                                <th>Observaciones</th>
                                <th>Editar</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaHistorial">
                            <tr>
                                <td colspan="13" class="text-muted py-3">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>          
                </div>

            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR ENTREGA -->
<div class="modal fade" id="modalEditarEntrega" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i> Editar Entrega
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formEditarEntrega" enctype="multipart/form-data">
                    <input type="hidden" id="edit_ent_id" name="ent_id">

                    <div class="row">

                        <!-- TIPO DE ENTREGA -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Tipo de Entrega
                            </label>
                            <select class="form-control" id="edit_ent_tipoentrega" name="ent_tipoentrega">
                                <option value="">Seleccionar...</option>
                                <option value="inicial">Inicial</option>
                                <option value="final">Final</option> 
                            </select>
                        </div>

                        <!-- CONDUCTOR -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary"
                                   id="edit_label_conductor">
                                Conductor
                            </label>
                            <input type="text" id="edit_ent_conductor"
                                   class="form-control bg-light" readonly>
                        </div>

                        <!-- RECIBIDO POR / ENTREGADO POR -->
                        <div class="col-md-6 mb-3">
                            <label id="edit_label_recibido_entregado"
                                   class="form-label fw-bold text-secondary">
                                Recibido por
                            </label>
                            <input type="text" id="edit_ent_userregistra"
                                   class="form-control bg-light" readonly>
                        </div>

                        <!-- FECHA ENTREGA -->
                        <div class="col-md-6 mb-3" id="wrap_edit_fecha_inicio">
                            <label class="form-label fw-bold text-secondary" id="edit_label_fecha">
                                📅 Fecha Entrega
                            </label>
                            <input type="date" id="edit_ent_fechaentrega"
                                   name="ent_fechaentrega" class="form-control">
                        </div>

                        <!-- FECHA REGISTRO -->
                        <div class="col-md-6 mb-3" id="wrap_edit_fecha_final">
                            <label class="form-label fw-bold text-secondary">
                               📅 Fecha Registro
                            </label>
                            <input type="date" id="edit_ent_fecharegistra"
                                   name="ent_fecharegistra" class="form-control">
                        </div>

                        <!-- SEDE -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Sede
                            </label>
                            <select id="edit_ent_sede" name="ent_sede"
                                    class="form-control">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($Sedes as $sede): ?>
                                <option value="<?= $sede['sed_nombre'] ?>">
                                    <?= htmlspecialchars($sede['sed_nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- FOTOS -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📷 Foto actual del vehículo (Frente)
                            </label>
                            <div id="preview_edit_ent_frente" class="mb-2"></div>
                            <input type="file" name="ent_img_frente"
                                   class="form-control" accept=".jpg,.jpeg,.png">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📷 Foto actual del vehículo (Respaldo)
                            </label>
                            <div id="preview_edit_ent_respaldo" class="mb-2"></div>
                            <input type="file" name="ent_img_respaldo"
                                   class="form-control" accept=".jpg,.jpeg,.png">
                        </div>

                        <!-- EQUIPO -->
                        <div class="col-md-12 mb-3">
                            <div class="d-flex align-items-center justify-content-between
                                        px-3 py-2 rounded"
                                 style="background-color: #1a3a5c;">
                                <span class="text-white fw-bold">
                                    <i class="fas fa-toolbox me-2"></i>
                                    Equipo de Prevención y Seguridad Vial
                                </span>
                                <button type="button" class="btn btn-sm btn-light"
                                        id="btnAgregarHerramientaEditEntrega">
                                    <i class="fas fa-plus"></i> Agregar
                                </button>
                            </div>
                            <div id="listaHerramientasEditEntrega" class="mt-2"></div>
                            <input type="hidden" name="ent_equipo_carretera"
                                   id="edit_ent_equipo_carretera">
                        </div>

                        <!-- OBSERVACIONES -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Observaciones
                            </label>
                            <textarea id="edit_ent_observaciones" name="ent_observaciones"
                                      class="form-control" rows="3"
                                      placeholder="Detalles adicionales sobre la entrega...">
                            </textarea>
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarEditarEntrega"
                        class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Actualizar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL EDITAR FECHA ULTIMO CAMBIO DE ACEITE -->
<div class="modal fade" id="modalEditarAceite" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Actualizar Cambio de Aceite
                </h5>
                <button type="button" class="btn-close btn-close-white" 
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formEditarAceite">
                    <input type="hidden" id="aceite_veh_id">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Kilometraje Actual
                            </label>
                            <input type="text" class="form-control" 
                                   id="aceite_kilactual" name="veh_kilactual">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                📅 Fecha Cambio de Aceite
                            </label>
                            <input type="date" class="form-control" 
                                   id="aceite_fecha" name="veh_fechamantenimiento">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Km Actual Al Cambio de Aceite
                            </label>
                            <input type="text" class="form-control" 
                                   id="aceite_kmcambio" name="veh_kmactual_cambioaceite">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">
                                Limite Km cambio aceite
                            </label>
                            <input type="text" class="form-control" 
                                   id="aceite_limite" name="veh_calkmcambioaceite">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" 
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" 
                        id="btnGuardarAceite">
                         Guardar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL EDITAR SEGURO SOAT -->
<div class="modal fade" id="modalEditarSoat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Actualizar Seguro SOAT
                </h5>
                <button type="button" class="btn-close btn-close-white" 
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formEditarSoat">
                    <input type="hidden" id="soat_veh_id">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Vencimiento Seguro (SOAT)</label>
                            <input type="date" name="veh_fechaseguro" id="soat_veh_fechaseguro" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Comprobante de Pago Seguro (SOAT)</label>
                            <div id="preview_soat_modal" class="mb-1"></div>
                            <input type="file" name="veh_img_soat" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" 
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" 
                        id="btnGuardarSoat">
                         Guardar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL EDITAR TECNOMECANICA -->
<div class="modal fade" id="modalEditarTecnomecanica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header mi-header text-white">
                <h5 class="modal-title">
                    Actualizar Tecnomecánica
                </h5>
                <button type="button" class="btn-close btn-close-white" 
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formEditarTecnomecanica">
                    <input type="hidden" id="tecnomecanica_veh_id">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Vencimiento Tecnomecánica</label>
                            <input type="date" name="veh_fechategnomecanica" id="tecnomecanica_veh_fechatecnomecanica" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Comprobante de Pago Tecnomecánica</label>
                            <div id="preview_tecnomecanica_modal" class="mb-1"></div>
                            <input type="file" name="veh_img_tecnomecanica" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" 
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" 
                        id="btnGuardarTecnomecanica">
                         Guardar
                </button>
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
<script src="/SistemaTransmillas2025/nueva_plataforma/assets/js/vehiculos.js"></script>

</body>
</html>