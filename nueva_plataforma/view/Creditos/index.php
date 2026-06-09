<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Creditos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/usuarios.css">
  <style>
    .mi-header {
      background-color: #00458D;
      color: white;
    }
  </style>
</head>
<body>
<div class="container-fluid mt-4">
  <div class="card shadow p-3 mb-4 bg-body rounded">
    <div class="card-header text-center mi-header">
      <h3 class="mb-0">Creditos</h3>
    </div>

    <div class="card-body">
      <div class="row mb-3 align-items-end">
        <div class="col-md-8">
          <label class="form-label d-block invisible">Accion</label>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCredito">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Credito
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table id="tablaCreditos" class="table table-hover table-bordered align-middle text-center">
          <thead class="table-primary">
            <tr>
              <th>ID Credito</th>
              <th>Nombre Del Credito</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCredito" tabindex="-1" aria-labelledby="modalCreditoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formCredito" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCreditoLabel">Nuevo Credito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombre del credito</label>
          <input type="text" name="cre_nombre" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
  const tabla = $('#tablaCreditos').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '/nueva_plataforma/controller/CreditosController.php',
      type: 'POST',
      data: function (d) {
        d.ajax = true;
      }
    },
    columns: [
      { data: 'idcreditos' },
      { data: 'cre_nombre' },
      {
        data: 'cre_estado',
        render: function (data, type, row) {
          const clase = data === 'Activo' ? 'estado-activo' : 'estado-inactivo';
          return `
            <select class="form-select form-select-sm cambiar-campo cambiar-estado ${clase}"
                    data-id="${row.idcreditos}">
              <option value="Activo" ${data === 'Activo' ? 'selected' : ''}>Activo</option>
              <option value="Inactivo" ${data === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
            </select>
          `;
        }
      }
    ]
  });

  $('#tablaCreditos_filter input')
    .off()
    .on('keyup', function (e) {
      if (e.keyCode === 13) {
        tabla.search(this.value).draw();
      }
    });

  $('#modalCredito').on('show.bs.modal', function () {
    $('#formCredito')[0].reset();
  });

  $('#formCredito').on('submit', function (e) {
    e.preventDefault();

    const formData = $(this).serializeArray();
    formData.push({ name: 'accion', value: 'crear_credito' });

    $.ajax({
      url: '/nueva_plataforma/controller/CreditosController.php',
      type: 'POST',
      data: $.param(formData),
      dataType: 'json',
      success: function (resp) {
        if (resp.success) {
          alert(resp.message || 'Credito creado correctamente.');
          $('#modalCredito').modal('hide');
          tabla.ajax.reload(null, false);
        } else {
          alert(resp.message || 'No se pudo crear el credito.');
        }
      },
      error: function () {
        alert('Error en la peticion.');
      }
    });
  });

  $(document).on('change', '.cambiar-estado', function () {
    const select = $(this);
    const estado = select.val();
    const idCredito = select.data('id');

    $.ajax({
      url: '/nueva_plataforma/controller/CreditosController.php',
      type: 'POST',
      dataType: 'json',
      data: {
        accion: 'actualizar_estado',
        idcreditos: idCredito,
        estado: estado
      },
      success: function (resp) {
        if (resp.success) {
          select
            .removeClass('estado-activo estado-inactivo')
            .addClass(estado === 'Activo' ? 'estado-activo' : 'estado-inactivo');
        } else {
          alert(resp.message || 'No se pudo actualizar el estado.');
          tabla.ajax.reload(null, false);
        }
      },
      error: function () {
        alert('Error al actualizar el estado.');
        tabla.ajax.reload(null, false);
      }
    });
  });
});
</script>
</body>
</html>
