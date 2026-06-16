<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Usuarios</title>
<!-- Bootstrap 5 CSS desde CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
thead.azul-blanco th {
  background-color: #01468c; /* Tu azul exacto */
  color: white;
}
.mi-header {
        background-color: #00458D; /* Naranja por ejemplo */
        color: white;
}

</style>
<body>
<div class="container-fluid mt-4">
  <div class="card shadow p-3 mb-4 bg-body rounded">
    <div class="card-header mi-header position-relative">
      <div class="text-end mb-2">
        <a
          href="https://bot.transmillas.com/Chat_Bot_Local/?token=transmillas_token_seguro_2026"
          class="btn btn-light btn-sm fw-semibold"
          target="_blank"
          rel="noopener noreferrer"
        >
          <i class="fa-brands fa-whatsapp me-1"></i> Enlazar WhatsApps
        </a>
      </div>
      <h3 class="mb-0 text-center">Módulo WhatsApp</h3>
    </div>

    <div class="card-body">
      <div class="row mb-3 align-items-end">
        <div class="col-md-4">
          <label for="filtroFecha" class="form-label">📅 Fecha</label>
          <input type="date" id="filtroFecha" class="form-control" />
        </div>

        <div class="col-md-4">
          <label for="filtroTipoMensaje" class="form-label">💬 Tipo de mensaje</label>
          <select id="filtroTipoMensaje" class="form-select">
            <option value="">Todos</option>
            <option value="Alertas">Alertas</option>
            <option value="ChatBot" selected>Chat Bot</option>
            <option value="ServiciosHechos">Servicios Hechos</option>
            <option value="Cotizaciones">Cotizaciones</option>
            <option value="cotizaMinima">cotizaciones minima</option>
          </select>
        </div>
      </div>

      <div class="table-responsive">
        <table id="tablaUsuarios" class="table table-hover table-bordered align-middle text-center">
          <thead class="table-primary">
            <tr>
              <th>📆 Fecha</th>
              <th>📥 Mensaje recibido</th>
              <th>📤 Mensaje enviado</th>
              <th>🆔 ID WhatsApp</th>
              <th>📞 Número</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
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
   

<script>
$(document).ready(function () {
  const tabla = $('#tablaUsuarios').DataTable({
    ajax: {
      url: '/nueva_plataforma/controller/WhatsappController.php',
      type: 'POST',
      data: function (d) {
        d.ajax = true;
        d.fecha = $('#filtroFecha').val();
        d.tipo = $('#filtroTipoMensaje').val();
      },
      dataSrc: ''
    },
    columns: [
        { data: 'fecha_hora' },
        {
            data: 'mensaje_recibido',
            render: function (data, type, row) {
            return '<textarea readonly class="form-control" rows="3" style="resize: none;">' + data + '</textarea>';
            }
        },
        {
            data: 'mensaje_enviado',
            render: function (data, type, row) {
            return '<textarea readonly class="form-control" rows="3" style="resize: none;">' + data + '</textarea>';
            }
        },
        {
            data: 'id_wa',
            render: function (data, type, row) {
            return '<textarea readonly class="form-control" rows="3" style="resize: none;">' + data + '</textarea>';
            }
        },
        { data: 'telefono_wa' }
      
 
      ]
  });

  $('#filtroFecha, #filtroTipoMensaje').on('change', function () {
    tabla.ajax.reload();
  });
});

// 🔁 Detectar cambios en cualquier campo editable
$('#tablaUsuarios tbody').on('change', '.cambiar-campo', function () {
  const id = $(this).data('id');
  const campo = $(this).data('campo');
  const valor = $(this).val();

  // if(id == "usu_estado" and valor==0){
  //   alert('Está apunto de desactivar al usuario, recuerde colocar fecha de finalizacion en la hoja de vida si aun no lo ha hecho');

  // }

  $.ajax({
    url: '/nueva_plataforma/controller/WhatsappController.php',
    type: 'POST',
    data: {
      actualizar_campo: true,
      id: id,
      campo: campo,
      valor: valor
    },
    success: function (res) {
      $('#tablaUsuarios').DataTable().ajax.reload(null, false);
    },
    error: function () {
      alert("Hubo un error al actualizar.");
    }
  });
});
$('#tablaUsuarios tbody').on('click', '.eliminar-usuario', function () {
  const id = $(this).data('id');

  if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
    $.ajax({
      url: '/nueva_plataforma/controller/WhatsappController.php',
      type: 'POST',
      data: {
        eliminar_usuario: true,
        id: id
      },
      success: function (res) {
        $('#tablaUsuarios').DataTable().ajax.reload(null, false);
      },
      error: function () {
        alert('Error al eliminar el usuario.');
      }
    });
  }
});
</script>
</body>
</html>
