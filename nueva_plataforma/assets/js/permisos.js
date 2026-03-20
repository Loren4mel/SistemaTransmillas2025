
const redi = "/nueva_plataforma/controller/permisosController.php";

function renderPermisoSelect(data, row, campo) {
  const valor = String(data) === '1' ? '1' : '0';

  return `
    <select class="form-select form-select-sm cambiar-campo"
            data-id="${row.idpermisos}"
            data-campo="${campo}">
      <option value="1" ${valor === '1' ? 'selected' : ''}>Si</option>
      <option value="0" ${valor === '0' ? 'selected' : ''}>No</option>
    </select>
  `;
}

$(document).ready(function () {
  const tabla = $('#tablaUsuarios').DataTable({
    ajax: {
      url: redi,
      type: 'POST',
      data: function (d) {
        d.ajax = true;
        d.rol = $('#filtroRol').val();
        d.principal = $('#filtroprincipal').val();
        d.secundario = $('#filtrosecundario').val();
      },
      dataSrc: ''
    },
    columns: [
        { data: 'rol_nombre' },
        {
          data: 'men_nombre',
          render: function (data, type, row) {
            if (!row.men_url) {
              return data;
            }

            return `
              <a href="../../${row.men_url}" target="_blank" rel="noopener noreferrer">
                ${data}
              </a>
            `;
          }
        },
        { data: 'men_predecesor' },
        {
          data: 'per_crear',
          render: function (data, type, row) {
            return renderPermisoSelect(data, row, 'per_crear');
          }
        },
        {
          data: 'per_editar',
          render: function (data, type, row) {
            return renderPermisoSelect(data, row, 'per_editar');
          }
        },
        {
          data: 'per_eliminar',
          render: function (data, type, row) {
            return renderPermisoSelect(data, row, 'per_eliminar');
          }
        },
        {
          data: 'per_consultar',
          render: function (data, type, row) {
            return renderPermisoSelect(data, row, 'per_consultar');
          }
        },
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `
            <a href="../../cambio_admin.php?id_param=${row.idmenu}&tabla=Usuario&condecion=" 
              class="btn btn-sm btn-outline-primary" title="Editar" target="_blank">
              <i class="fas fa-edit"></i>
            </a>
          `;
        }
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `
            <button class="btn btn-sm btn-danger eliminar-usuario"
                    title="Eliminar"
                    data-id="${row.idmenu}">
              <i class="fas fa-trash-alt"></i>
            </button>
          `;
        }
      }
      ]
  });

  $('#filtroRol, #filtroprincipal, #filtrosecundario').on('change', function () {
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
    url: redi,
    type: 'POST',
    dataType: 'json',
    data: {
      actualizar_campo: true,
      id: id,
      campo: campo,
      valor: valor
    },
    success: function (res) {
      if (!res || !res.ok) {
        alert("No se pudo actualizar el permiso.");
        return;
      }

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
      url: redi,
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

function verDispositivos(idUsuario) {

  window.usuarioDispositivoActual = idUsuario;

  $.ajax({
    url: redi,
    type: 'POST',
    dataType: 'json',
    data: {
      listar_dispositivos: true,
      idusuario: idUsuario
    },
    success: function (data) {

      let tbody = $('#tablaDispositivos tbody');
      tbody.html('');

      if (data.length === 0) {
        tbody.append(`
          <tr>
            <td colspan="6" class="text-muted">
              No hay dispositivos asociados
            </td>
          </tr>
        `);
      }

      data.forEach(d => {

        const estado = d.authorized == 1
          ? '<span class="badge bg-success">Autorizado</span>'
          : '<span class="badge bg-warning text-dark">Pendiente</span>';

        const accion = d.authorized == 1
          ? `<button class="btn btn-sm btn-danger" onclick="bloquearDispositivo(${d.id})">
               <i class="fas fa-ban"></i>
             </button>`
          : `<button class="btn btn-sm btn-success" onclick="autorizarDispositivo(${d.id})">
               <i class="fas fa-check"></i>
             </button>`;

        tbody.append(`
          <tr>
            <td>${d.device_name ?? 'Sin nombre'}</td>
            <td>${d.device_type ?? '-'}</td>
            <td>${d.last_login ?? '-'}</td>
            <td>${d.ip_last ?? '-'}</td>
            <td>${estado}</td>
            <td>${accion}</td>
          </tr>
        `);
      });

      $('#modalDispositivos').modal('show');
    }
  });
}
function autorizarDispositivo(idDispositivo) {

  if (!confirm('¿Autorizar este dispositivo?')) return;

  $.ajax({
    url: redi,
    type: 'POST',
    dataType: 'json',
    data: {
      autorizar_dispositivo: true,
      id: idDispositivo
    },
    success: function (res) {
      if (res.ok) {
        // recargar la lista sin cerrar el modal
        verDispositivos(window.usuarioDispositivoActual);
      }
    }
  });
}
function bloquearDispositivo(idDispositivo) {

  if (!confirm('¿Bloquear este dispositivo?')) return;

  $.ajax({
    url: redi,
    type: 'POST',
    dataType: 'json',
    data: {
      bloquear_dispositivo: true,
      id: idDispositivo
    },
    success: function (res) {
      if (res.ok) {
        // recargar la lista sin cerrar el modal
        verDispositivos(window.usuarioDispositivoActual);
      }
    }
  });
}
