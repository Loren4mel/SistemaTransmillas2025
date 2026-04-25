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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<div class="container-fluid py-4"> 

    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-sm btn-light border">Volver</button>

        <div class="d-flex gap-2">
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
              <option value="1">Activo</option>
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
                                <span class="text-danger">*</span>
                            </label>
                            <select name="veh_propiedad" id="veh_propiedad" class="form-control" required>
                                 <option value="">Seleccionar...</option>
                                 <option value="empresa">Empresa</option>
                                 <option value="propio">Propio</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Dueño 
                                <span class="text-danger">*</span>
                            </label>
                            <select name="veh_dueno" id="veh_dueno" class="form-control" required>
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
                            <label class="form-label fw-bold text-secondary">📅 Fecha Vencimiento Seguro (SOAT)</label>
                            <input type="date" name="veh_fechaseguro" value="<?= date('Y-m-d') ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Vigente Seguro (SOAT) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="veh_img_soat" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Vencimiento Tecnomecánica</label>
                            <input type="date" name="veh_fechategnomecanica" value="<?= date('Y-m-d') ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Vigente Tecnomecánica 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="veh_img_tecnomecanica" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Ultimo Cambio de Aceite</label>
                            <input type="date" name="veh_fechamantenimiento" value="<?= date('Y-m-d') ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Km Actual 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="veh_kilactual" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Intervalo Cambio Aceite km 
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

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Estado 
                                <span class="text-danger">*</span>
                            </label>
                            <select name="veh_estado" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <option value="0">Inactivo</option>
                                <option value="1">Activo</option>
                            </select>
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
                            <input type="file" name="veh_img_anverso" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tarjeta Propiedad (Respaldo) 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="veh_img_reverso" class="form-control" required>
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
                            <label class="form-label fw-bold text-secondary">Propiedad Vehículo *</label>
                            <select name="veh_propiedad" id="edit_veh_propiedad" class="form-control" required>
                               <option value="">Seleccionar...</option>
                               <option value="empresa">Empresa</option>
                               <option value="propio">Propio</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
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
                            <label class="form-label fw-bold text-secondary">📅 Fecha Seguro (SOAT)</label>
                            <input type="date" name="veh_fechaseguro" id="edit_veh_fecha_soat" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Seguro (SOAT)</label>
                            <!-- Vista previa de imagen actual -->
                            <div id="preview_soat" class="mb-1"></div>
                            <input type="file" name="veh_img_soat" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Tecnomecánica</label>
                            <input type="date" name="veh_fechategnomecanica" id="edit_veh_fechategnomecanica" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📷 Foto Tecnomecánica</label>
                            <div id="preview_tecnomecanica" class="mb-1"></div>
                            <input type="file" name="veh_img_tecnomecanica" class="form-control">
                        </div>
 
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">📅 Fecha Cambio de Aceite</label>
                            <input type="date" name="veh_fechamantenimiento" id="edit_veh_fechamantenimiento" class="form-control">
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
            <select class="form-select" id="ent_tipoentrega" name="ent_tipoentrega" required>
                <option value="">Seleccionar tipo...</option>
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
                                <option value="">Seleccionar vehículo...</option>
                                <?php foreach ($Vehiculos as $v): ?>
                                <option value="<?= $v['idvehiculos'] ?>"
                                    data-texto="<?= $v['veh_tipo'] ?> <?= $v['veh_placa'] ?> <?= $v['veh_marca'] ?> <?= $v['veh_modelo'] ?>">
                                    <?= $v['veh_placa'] ?> — <?= $v['veh_marca'] ?> <?= $v['veh_modelo'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Recibido por</label>
                            <input type="text" class="form-control bg-light" 
                             value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Sin sesión') ?>" 
                             readonly>
                            <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>    
                            Usuario actualmente logueado</small>
                            <input type="hidden" name="ent_userregistra" 
                             value="<?= htmlspecialchars($_SESSION['usuario_nombre']) ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Entregado
                                <span class="text-danger">*</span></label>
                            <select class="form-select" id="ent_idusuario" name="ent_idusuario" required>
                            <option value="">Seleccionar conductor...</option>
                                   <?php foreach ($operadoresActivos as $op): ?>
                            <option value="<?= $op['idusuarios'] ?>">
                                   <?= htmlspecialchars($op['usu_nombre']) ?>
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

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold text-secondary">Observaciones</label>
                            <textarea class="form-control" name="ent_observaciones"
                                      rows="3"
                                      placeholder="Detalles adicionales sobre la entrega..."></textarea>
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <!-- DataTables desde CDN -->
   

<script src="/SistemaTransmillas2025/nueva_plataforma/assets/js/vehiculos.js"></script>




</body>
</html>
