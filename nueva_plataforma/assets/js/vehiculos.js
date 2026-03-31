
$(document).ready(function () {
    const tabla = $('#tablaVehiculos').DataTable({
        ajax: {
            url: 'nueva_plataforma/controller/VehiculosController.php',
            type: 'POST',
            data: function (d) {
                d.ajax = true;
                d.tipodevehiculo = $('#filtrotipodevehiculo').val();
                d.estado = $('#filtroestado').val();
            },
            dataSrc: ''
        },
        columns: [
            { data: 'veh_tipo' },
            { data: 'veh_marca' },
            { data: 'veh_placa' },
            { data: 'veh_modelo' },
            { data: 'usu_nombre' },
            { data: 'veh_fechaseguro' },
            { data: 'veh_fechategnomecanica' },
            { data: 'veh_fechamantenimiento' },
            { data: 'veh_kilactual' },
            { data: 'veh_calkmcambioaceite' },
            { data: 'veh_img_anverso' },
            { data: 'veh_img_reverso' },
            // 🔁 Interactivo: agregar dispositivo
            /*
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
            <button 
              class="btn btn-sm btn-info"
              title="Dispositivos"
              onclick="verDispositivos(${row.idusuarios})">
              <i class="fas fa-laptop"></i>
            </button>
          `;
                }
            },
            */
            // 🔁 Interactivo: usu_filtro → Ver en sistema
            {
                data: 'veh_estado',
                render: function (data, type, row) {
                    const clase = data == 1 ? 'estado-activo' : 'estado-inactivo';
                    return `
            <select class="form-select form-select-sm cambiar-campo ${clase}"
                    data-id="${row.idvehiculos}"
                    data-campo="veh_estado">
              <option value="1" ${data == 1 ? 'selected' : ''}>Activo</option>
              <option value="0" ${data == 0 ? 'selected' : ''}>Inactivo</option>
            </select>
          `;
                }
            },

            /*
            {
                data: 'usu_ver_nomina',
                render: function (data, type, row) {
                    const clase = data == 1 ? 'estado-activo' : 'estado-inactivo';
                    return `
            <select class="form-select form-select-sm cambiar-campo ${clase}"
                    data-id="${row.idusuarios}"
                    data-campo="usu_ver_nomina">
              <option value="1" ${data == 1 ? 'selected' : ''}>Activo</option>
              <option value="0" ${data == 0 ? 'selected' : ''}>Inactivo</option>
            </select>
          `;
                }
            },
            {
                data: 'usu_estado',
                render: function (data, type, row) {
                    const clase = data == 1 ? 'estado-activo' : 'estado-inactivo';
                    return `
            <select class="form-select form-select-sm cambiar-campo ${clase}"
                    data-id="${row.idusuarios}"
                    data-campo="usu_estado">
              <option value="1" ${data == 1 ? 'selected' : ''}>Activo</option>
              <option value="0" ${data == 0 ? 'selected' : ''}>Inactivo</option>
            </select>
          `;
                }
            },
            */

            // 🔁 Interactivo: Editar
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
            <a href="../../cambio_admin.php?id_param=${row.idvehiculos}&tabla=Vehiculo&condecion=" 
              class="btn btn-sm btn-outline-primary" title="Editar" target="_blank">
              <i class="fas fa-edit"></i>
            </a>
          `;
                }
            },

            // 🔁 Interactivo: Eliminar
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
            <button class="btn btn-sm btn-danger eliminar-vehiculo"
                    title="Eliminar"
                    data-id="${row.idvehiculos}">
              <i class="fas fa-trash-alt"></i>
            </button>
          `;
                }
            }
        ]
    });

    // 🔁 Filtros: recargar tabla al cambiar
    $('#filtrotipodevehiculo, #filtroestado').on('change', function () {
        tabla.ajax.reload();
    });
});

// 🔁 Detectar cambios en cualquier campo editable
$('#tablaVehiculos tbody').on('change', '.cambiar-campo', function () {
    const id = $(this).data('id');
    const campo = $(this).data('campo');
    const valor = $(this).val();

    // if(id == "usu_estado" and valor==0){
    //   alert('Está apunto de desactivar al usuario, recuerde colocar fecha de finalizacion en la hoja de vida si aun no lo ha hecho');

    // }

    // Enviar actualización al servidor
    $.ajax({
        url: '/nueva_plataforma/controller/VehiculosController.php',
        type: 'POST',
        data: {
            actualizar_campo: true,
            id: id,
            campo: campo,
            valor: valor
        },
        success: function (res) {
            $('#tablaVehiculos').DataTable().ajax.reload(null, false);
        },
        error: function () {
            alert("Hubo un error al actualizar.");
        }
    });
});

// 🔁 Eliminar vehículo
$('#tablaVehiculos tbody').on('click', '.eliminar-vehiculo', function () {
    const id = $(this).data('id');

    if (confirm('¿Estás seguro de que deseas eliminar este vehículo?')) {
        $.ajax({
            url: '/nueva_plataforma/controller/VehiculosController.php',
            type: 'POST',
            data: {
                eliminar_vehiculo: true,
                id: id
            },
            success: function (res) {
                $('#tablaVehiculos').DataTable().ajax.reload(null, false);
            },
            error: function () {
                alert('Error al eliminar el vehículo.');
            }
        });
    }
});

/*
// 1. Referencias a los elementos
const formVehiculo = document.getElementById('formVehiculo');
const btnGuardar = document.getElementById('btnGuardarVehiculo');
const myModalEl = document.getElementById('modalVehiculo');
const myModal = new bootstrap.Modal(myModalEl);

// 2. Evento de clic para guardar
btnGuardar.addEventListener('click', async () => {
    
    // Creamos un objeto con los datos del formulario
    const formData = new FormData(formVehiculo);
    
    // Agregamos una acción 
    formData.append('accion', 'registrar');

    try {
        
        const response = await fetch('controladores/VehiculosController.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert('¡Vehículo guardado con éxito!');
            formVehiculo.reset(); // Limpia los campos
            myModal.hide();       // Cierra el modal
            // Aquí podrías llamar a una función para recargar tu tabla de vehículos
            // listarVehiculos(); 
        } else {
            alert('Error: ' + result.message);
        }

    } catch (error) {
        console.error('Error en la petición:', error);
        alert('Hubo un fallo al conectar con el servidor.');
    }
});

*/
/* Funciones para gestionar dispositivos asociados a un usuario
function verDispositivos(idUsuario) {

    window.usuarioDispositivoActual = idUsuario;

    $.ajax({
        url: '/nueva_plataforma/controller/VehiculosController.php',
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
*/

/* Funciones para autorizar o bloquear dispositivos asociados a un usuario

function autorizarDispositivo(idDispositivo) {

    if (!confirm('¿Autorizar este dispositivo?')) return;

    $.ajax({
        url: '/nueva_plataforma/controller/VehiculosController.php',
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
        url: '/nueva_plataforma/controller/VehiculosController.php',
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
*/
