<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Permisos</title>
<!-- Bootstrap 5 CSS desde CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/permisos.css">



<body>
<div class="container-fluid mt-4">
  <div class="card shadow p-3 mb-4 bg-body rounded">
  <div class="card-header mi-header d-flex align-items-center justify-content-between">
    <h3 class="mb-0">
      <i class="fas fa-users me-2"></i> Permisos
    </h3>
  </div>
    <div class="card-body">
      <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearPermiso">
          <i class="fas fa-plus me-1"></i> Nuevo permiso
        </button>
      </div>

      <div class="modal fade" id="modalCrearPermiso" tabindex="-1" aria-labelledby="modalCrearPermisoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalCrearPermisoLabel">Crear permiso</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCrearPermiso">
              <div class="modal-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="crearRol" class="form-label">Rol</label>
                    <select id="crearRol" name="roles_idroles" class="form-control" required>
                      <option value="">Seleccionar...</option>
                      <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['idroles'] ?>"><?= $rol['rol_nombre'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label for="crearMenu" class="form-label">Item menu</label>
                    <select id="crearMenu" name="menu_idmenu" class="form-control" required>
                      <option value="">Seleccionar...</option>
                      <?php foreach ($principales as $principal): ?>
                        <option value="<?= $principal['idmenu'] ?>"><?= $principal['men_nombre'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="crearPermCrear" class="form-label">Crear</label>
                    <select id="crearPermCrear" name="per_crear" class="form-control">
                      <option value="1">Si</option>
                      <option value="0" selected>No</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="crearPermEditar" class="form-label">Editar</label>
                    <select id="crearPermEditar" name="per_editar" class="form-control">
                      <option value="1">Si</option>
                      <option value="0" selected>No</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="crearPermEliminar" class="form-label">Eliminar</label>
                    <select id="crearPermEliminar" name="per_eliminar" class="form-control">
                      <option value="1">Si</option>
                      <option value="0" selected>No</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="crearPermConsultar" class="form-label">Visibilidad en menu</label>
                    <select id="crearPermConsultar" name="per_consultar" class="form-control">
                      <option value="1" selected>Si</option>
                      <option value="0">No</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i> Guardar permiso
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="modal fade" id="modalEditarPermiso" tabindex="-1" aria-labelledby="modalEditarPermisoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalEditarPermisoLabel">Editar permiso</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarPermiso">
              <input type="hidden" id="editarIdPermiso" name="idpermisos">
              <div class="modal-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="editarRol" class="form-label">Rol</label>
                    <select id="editarRol" name="roles_idroles" class="form-control" required>
                      <option value="">Seleccionar...</option>
                      <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['idroles'] ?>"><?= $rol['rol_nombre'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label for="editarMenu" class="form-label">Item menu</label>
                    <select id="editarMenu" name="menu_idmenu" class="form-control" required>
                      <option value="">Seleccionar...</option>
                      <?php foreach ($principales as $principal): ?>
                        <option value="<?= $principal['idmenu'] ?>"><?= $principal['men_nombre'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="editarPermCrear" class="form-label">Crear</label>
                    <select id="editarPermCrear" name="per_crear" class="form-control">
                      <option value="1">Si</option>
                      <option value="0">No</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="editarPermEditar" class="form-label">Editar</label>
                    <select id="editarPermEditar" name="per_editar" class="form-control">
                      <option value="1">Si</option>
                      <option value="0">No</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="editarPermEliminar" class="form-label">Eliminar</label>
                    <select id="editarPermEliminar" name="per_eliminar" class="form-control">
                      <option value="1">Si</option>
                      <option value="0">No</option>
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label for="editarPermConsultar" class="form-label">Visibilidad en menu</label>
                    <select id="editarPermConsultar" name="per_consultar" class="form-control">
                      <option value="1">Si</option>
                      <option value="0">No</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i> Actualizar permiso
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="row mb-3 align-items-end">
          <div class="col-md-4">
            <label for="rol">Rol</label>
            <select id="filtroRol" class="form-control">
              <option value="">Seleccionar...</option>
              <?php foreach ($roles as $rol): ?>
                <option value="<?= $rol['idroles'] ?>"><?= $rol['rol_nombre'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label for="principal">Item Menu</label>
            <select id="filtroprincipal" class="form-control">
              <option value="">Seleccionar...</option>
              <?php foreach ($principales as $principal): ?>
                <option value="<?= $principal['idmenu'] ?>"><?= $principal['men_nombre'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>


          
      </div>

      <div class="table-responsive">
        <table id="tablaUsuarios" class="table table-hover table-bordered align-middle text-center">
          <thead class="thead-modern">
            <tr>
                
                <th>Rol</th>
                <th>Item Menu</th>
                <th>Pertenece</th>
                <th>Crea</th>
                <th>Edita</th>
                <th>Elimina</th>
                <th>Visibilidad en menu</th>
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

<!-- MODAL DISPOSITIVOS -->
<div class="modal fade" id="modalDispositivos" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-laptop"></i> Dispositivos del usuario
        </h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm text-center" id="tablaDispositivos">
            <thead class="table-light">
              <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Último acceso</th>
                <th>IP</th>
                <th>Estado</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>




<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <!-- ✅ DataTables desde CDN -->
   

<script src="../assets/js/permisos.js"></script>



</body>
</html>
