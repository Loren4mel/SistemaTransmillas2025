<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Clientes Credito</title>
<!-- Bootstrap 5 CSS desde CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="../assets/css/usuarios.css">
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
    <div class="card-header text-center mi-header">
      <h3 class="mb-0">Clientes Credito</h3>
    </div>

    <div class="card-body">
        <div class="row mb-3 align-items-end">
            <div class="col-md-4">
                <label for="filtroFecha" class="form-label">📅 Fecha</label>
                <input type="date" id="filtroFecha" class="form-control" />
            </div>

            <div class="col-md-4">
                <label for="idciudades" class="form-label">Ciudad</label>
                <select id="idciudades" class="form-select">
                <option value="">Seleccione un cliente</option>
                
                <?php foreach ($ciudades as $ciudad): ?>
                <option value="<?= $ciudad['idciudades'] ?>"><?= $ciudad['ciu_nombre'] ?></option>
                <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <label class="form-label d-block invisible">Botón</label>
                <button class="btn btn-primary text-white w-100" data-bs-toggle="modal" data-bs-target="#modalServicioAuto">
                <i class="bi bi-plus-circle me-1"></i> Nuevo Cliente
                </button>
            </div>
        </div>

      <div class="table-responsive">
        <table id="tablaUsuarios" class="table table-hover table-bordered align-middle text-center">
          <thead class="table-primary">
            <tr>
                <th>ID Credito</th>
                <th>Nombre Del Credito</th>
                <th>Estado</th>
                <th>Habilitar</th>
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

<!-- Modal para Editar cliente-->
<div class="modal fade" id="modalServicioAuto" tabindex="-1" aria-labelledby="modalServicioAutoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form id="formEditarCliente" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalServicioAutoLabel">Destinatario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          
          <!-- CC / NIT -->
          <div class="col-md-6">
            <label class="form-label">CC / NIT</label>
            <input type="text" name="cc_nit" class="form-control">
          </div>

          <!-- Teléfonos -->
          <div class="col-md-6">
            <label class="form-label">Teléfonos (*)</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-telephone"></i></span>
              <input type="text" name="telefono" class="form-control" required>
            </div>
          </div>
            <input type="hidden" name="telefonos" class="form-control" required>
          <!-- WhatsApp -->
          <div class="col-md-6">
            <label class="form-label">WhatsApp (*)</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
              <input type="text" name="whatsapp" class="form-control" required>
            </div>
          </div>

          <!-- Ciudad -->
          <div class="col-md-6">
            <label class="form-label">Ciudad (*)</label>
            <select name="ciudad" class="form-select" required>
              <option value="">Seleccione...</option>
              <?php foreach($ciudades as $c): ?>
                <option value="<?= $c['idciudades'] ?>"><?= $c['ciu_nombre'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Nombre del cliente -->
          <div class="col-md-6">
            <label class="form-label">Nombre del Cliente</label>
            <input type="text" name="nombre_cliente" class="form-control">
          </div>

          <!-- Dirección -->
          <div class="col-md-3">
            <label class="form-label">Dirección</label>
            <select name="direccion" class="form-select">
                <option value="">Seleccione...</option>
                <option value="CALLE">CALLE</option>
                <option value="CARRERA">CARRERA</option>
                <option value="TRANSVERSAL">TRANSVERSAL</option>
                <option value="DIAGONAL">DIAGONAL</option>
                <option value="AUTOPISTA NORTE">AUTOPISTA NORTE</option>
                <option value="AUTOPISTA SUR">AUTOPISTA SUR</option>
                <option value="KILOMETRO">KILOMETRO</option>
                <option value="URBANIZACION ">URBANIZACION </option>
                <option value="AVENIDA">AVENIDA</option>
                <option value="CIUDAD">CIUDAD</option>
                <option value="AVENIDA CALLE">AVENIDA CALLE</option>
                <option value="AVENIDA CARRERA">AVENIDA CARRERA</option>
                <option value="VEREDA">VEREDA</option>
                <option value="EDIFICIO">EDIFICIO</option>
                <option value="ALMACEN">ALMACEN</option>
                <option value="CONSULTORIO">CONSULTORIO</option>
                <option value="MANZANA">MANZANA</option>
                <option value="CENTRO COMERCIAL">CENTRO COMERCIAL</option>
                <option value="OFICINA TRANSMILLAS">OFICINA TRANSMILLAS</option>
            </select>
          </div>

          
          <!-- Lugar de recogida -->
          <div class="col-md-3">
            <label class="form-label">-</label>
            <input type="text" name="restodireccion" class="form-control">
          </div>
          <!-- Lugar de recogida -->
          <div class="col-md-3">
            <label class="form-label">Lugar de Recogida</label>
            <select name="lugar_recogida" class="form-select">
                <option value="">Seleccione...</option><option value="Apartamento">Apartamento</option><option value="Local">Local</option><option value="Porteria">Porteria</option><option value="Casa">Casa</option><option value="Bloque">Bloque</option><option value="OFICINA">OFICINA</option><option value="CONJUNTO">CONJUNTO</option><option value="INTERIOR">INTERIOR</option><option value="MANZANA ">MANZANA </option><option value="TORRE">TORRE</option><option value="RECLAMA OFICINA DE TRANSMILLAS">RECLAMA OFICINA DE TRANSMILLAS</option><option value="EMPRESA">EMPRESA</option><option value="BODEGA">BODEGA</option><option value="ALMACEN">ALMACEN</option><option value="PISO">PISO</option><option value="EDIFICIO">EDIFICIO</option><option value="CONSULTORIO">CONSULTORIO</option><option value="LOTE">LOTE</option><option value="VEREDA">VEREDA</option><option value="OFICINA TRANSMILLAS">OFICINA TRANSMILLAS</option>
            </select>
          </div>

          <!-- Barrio -->
          <div class="col-md-6">
            <label class="form-label">Barrio</label>
            <input type="text" name="barrio" class="form-control">
          </div>

          <!-- Email -->
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
          </div>

          <!-- Valor autorizado -->
          <!-- <div class="col-md-3">
            <label class="form-label">Valor Autorizado</label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" name="valor_autorizado" class="form-control">
            </div>
          </div> -->

          <!-- ¿Crédito? -->
          <div class="col-md-3">
            <label class="form-label d-block">¿Crédito?</label>
            <div>
              <div class="form-check form-check-inline">
                <input type="radio" name="credito" value="SI" class="form-check-input">
                <label class="form-check-label">SI</label>
              </div>
              <div class="form-check form-check-inline">
                <input type="radio" name="credito" value="NO" class="form-check-input" checked>
                <label class="form-check-label">NO</label>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label d-block">Créditos asignado</label>
            <div id="creditoAsignado" class="form-check form-check-inline"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Seleccionar crédito</label>
            <select id="selectCredito" class="form-select">
                <option value="">Seleccione un credito</option>
                
                <?php foreach ($creditos as $credito): ?>
                <option value="<?= $credito['idcreditos'] ?>"><?= $credito['cre_nombre'] ?></option>
                <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 mt-3">
            <label class="form-label d-block">Créditos asignados</label>
            <div id="creditoSeleccionados" class="form-check form-check-inline"></div>
          </div>

          <!-- AU -->
          <div class="col-md-3">
            <label class="form-label">AU</label>
            <input type="text" name="au" class="form-control">
          </div>

          <!-- AC -->
          <div class="col-md-3">
            <label class="form-label">AC</label>
            <input type="text" name="ac" class="form-control">
          </div>

          <!-- Actividad económica -->
          <!-- <div class="col-md-3">
            <label class="form-label">Actividad económica</label>
            <input type="text" name="actividad_economica" class="form-control">
          </div> -->

          <!-- CIIU -->
          <!-- <div class="col-md-3">
            <label class="form-label">CIIU</label>
            <input type="text" name="ciiu" class="form-control">
          </div> -->

          <!-- Tipo de empresa -->
          <!-- <div class="col-md-3">
            <label class="form-label">Tipo de empresa</label>
            <select name="tipo_empresa" class="form-select">
              <option value="">Seleccione...</option>

            </select>
          </div> -->

          <!-- Régimen -->
          <!-- <div class="col-md-3">
            <label class="form-label">Régimen</label>
            <select name="regimen" class="form-select">
              <option value="">Seleccione...</option>

            </select>
          </div> -->

          <!-- Comercializadora -->
          <!-- <div class="col-md-6">
            <label class="form-label">Comercializadora</label>
            <select name="comercializadora" class="form-select">
              <option value="">Seleccione...</option>

            </select>
          </div> -->

          <!-- Producto o servicio -->
          <!-- <div class="col-md-6">
            <label class="form-label">Producto o servicio que suministra</label>
            <input type="text" name="producto_servicio" class="form-control">
          </div> -->

        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Editar</button>
      </div>
    </form>
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
    processing: true,    // Muestra "procesando..."
    serverSide: true,    // Activa server-side
    
    ajax: {
      url: '/nueva_plataforma/controller/ClientesCreditoController.php',
      type: 'POST',
      data: function (d) {
        d.fecha = $('#filtroFecha').val();
        d.ciudad = $('#idciudades').val();
        d.ajax = true; // <-- para que entre en el if
      }
    },
    columns: [
      { data: 'idcreditos' },
      { data: 'cre_nombre' },
        {
        data: 'cre_estado',
        render: function (data, type, row) {
          const clase = data == "Activo" ? 'estado-activo' : 'estado-inactivo';
          return `
            <select class="form-select form-select-sm cambiar-campo ${clase}"
                    data-id="${row.idcreditos}"
                    data-campo="usu_estado">
              <option value="Activo" ${data == "Activo" ? 'selected' : ''}>Activo</option>
              <option value="Inactivo" ${data == "Inactivo" ? 'selected' : ''}>Inactivo</option>
            </select>
          `;
        }
      },
        {
        data: 'cre_estado_final',
        render: function (data, type, row) {
          const clase = data == "Activo" ? 'estado-activo' : 'estado-inactivo';
          return `
            <select class="form-select form-select-sm cambiar-campo ${clase}"
                    data-id="${row.idcreditos}"
                    data-campo="usu_estado">
              <option value="Activo" ${data == "Activo" ? 'selected' : ''}>Activo</option>
              <option value="Inactivo" ${data == "Inactivo" ? 'selected' : ''}>Inactivo</option>
            </select>
          `;
        }
      },
     
      {
        data: 'cli_telefono',
        render: function (data) {
          const telefono = data || '';
          return `<button class="btn btn-sm btn-primary editar-usuario" data-id="${telefono}" ${telefono ? '' : 'disabled'}>
                    <i class="bi bi-pencil"></i>
                  </button>`;
        }
      },
      {
        data: 'idclientes',
        render: function (data) {
          return `<button class="btn btn-sm btn-danger eliminar-usuario" data-id="${data}">
                    <i class="bi bi-trash"></i>
                  </button>`;
        }
      }
    ]
  });

  // 🔄 Recargar cuando cambian filtros
  $('#filtroFecha, #idciudades').on('change', function () {
    tabla.ajax.reload();
  });
    // 🔍 Solo buscar al presionar Enter
  $('#tablaUsuarios_filter input')
    .off() // quitar el evento keyup que viene por defecto
    .on('keyup', function (e) {
      if (e.keyCode === 13) { // 13 = Enter
        tabla.search(this.value).draw();
      }
    });
});







let creditosSeleccionados = [];

$(document).on('click', '.editar-usuario', function () {
  let telefono = $(this).data('id');

  if (!telefono) {
    alert('Este credito no tiene un cliente asociado para editar.');
    return;
  }

  $('#formEditarCliente')[0].reset();
  $('#modalServicioAuto').modal('show');

  $.ajax({
    url: '../controller/ClientesCreditoController.php',
    type: 'GET',
    data: { accion: 'buscar_por_telefono', telefono: telefono },
    dataType: 'json',
    success: function (cliente) {
      if (cliente) {
        $('[name="cc_nit"]').val(cliente.cc_nit);
        $('[name="telefono"]').val(cliente.cli_telefono);
        $('[name="telefonos"]').val(cliente.cli_telefono);
        $('[name="whatsapp"]').val(cliente.cli_whatsap);
        $('[name="nombre_cliente"]').val(cliente.cli_nombre);
        $('[name="ciudad"]').val(cliente.cli_idciudad);
        
        $('[name="lugar_recogida"]').val(cliente.lugar_recogida_id);
        $('[name="barrio"]').val(cliente.cli_barrio);
        $('[name="email"]').val(cliente.cli_correo);
        $('[name="valor_autorizado"]').val(cliente.cli_valoraprobado);
        if (cliente.total_creditos > 0) {
            $(`[name="credito"][value="SI"]`).prop('checked', true);
            $('#creditoAsignado').html(
            '<label class="form-check-label">' + cliente.nombres_creditos + '</label>'
        );
        } else {
            $(`[name="credito"][value="NO"]`).prop('checked', true);
        }

        $('[name="au"]').val(cliente.au);
        $('[name="ac"]').val(cliente.ac);
        $('[name="ciiu"]').val(cliente.ciiu);






            if (cliente.cli_direccion) {
                let partes = cliente.cli_direccion.split('&').map(p => p.trim());

                $('[name="direccion"]').val((partes[0] || '').toUpperCase()); // Mayúsculas para el primero
                $('[name="restodireccion"]').val(partes[1] || '');
                $('[name="lugar_recogida"]').val(partes[2] || '');
                // $('[name="detalle_direccion2"]').val(partes[3] || '');
                $('[name="barrio"]').val(partes[4] || '');
            }



      } else {
        alert('No se encontró el cliente.');
      }
    },
    error: function () {
      alert('Error al buscar el cliente.');
    }
  });
});

  // Envío del formulario de edición
$(document).on('submit', '#formEditarCliente', function(e) {
    e.preventDefault();

    let formData = $(this).serializeArray(); // convierte a array de objetos
    formData.push({ name: "accion", value: "editar_cliente" });
    formData.push({ name: "creditos_asignados", value: creditosSeleccionados.join(',') });

    $.ajax({
        url: '/nueva_plataforma/controller/ClientesCreditoController.php',
        type: 'POST',
        data: $.param(formData), // convertir a string
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                alert('Cliente actualizado correctamente.');
                $('#modalServicioAuto').modal('hide');

                if ($.fn.DataTable.isDataTable('#miTablaClientes')) {
                    $('#miTablaClientes').DataTable().ajax.reload();
                }
            } else {
                alert(resp.message || 'No se pudo actualizar el cliente.');
            }
        },
        error: function() {
            alert('Error en la petición.');
        }
    });
});



    

  document.getElementById('selectCredito').addEventListener('change', function() {
    const idCredito = this.value;
    const nombreCredito = this.options[this.selectedIndex].text;

    if (idCredito && !creditosSeleccionados.includes(idCredito)) {
      // Guardar el ID en el array
      creditosSeleccionados.push(idCredito);

      // Crear la etiqueta con el nombre
      const div = document.createElement('div');
      div.className = "badge bg-primary m-1 p-2 d-inline-flex align-items-center";
      div.setAttribute("data-id", idCredito);
      div.innerHTML = `
        ${nombreCredito}
        <button type="button" class="btn-close btn-close-white ms-2" aria-label="Eliminar"></button>
      `;

      // Acción al eliminar
      div.querySelector('button').addEventListener('click', function() {
        creditosSeleccionados = creditosSeleccionados.filter(c => c !== idCredito);
        div.remove();
        console.log("Creditos:", creditosSeleccionados);
      });

      document.getElementById('creditoSeleccionados').appendChild(div);
      console.log("Creditos:", creditosSeleccionados);
    }

    // Resetear select
    this.value = "";
  });
</script>
</body>
</html>
