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

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <!-- ✅ DataTables desde CDN -->
   

<script src="../assets/js/permisos.js"></script>



</body>
</html>
