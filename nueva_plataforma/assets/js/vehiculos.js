
const urlController = '../controller/VehiculosController.php';
$(document).ready(function () {
    const tabla = $('#tablaVehiculos').DataTable({
        ajax: {
            url: urlController,
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
            //Vista previa de imágenes
            {
                data: 'veh_img_anverso',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    if (!data) return '<span class="text-muted" style="font-size:11px;">Sin imagen</span>';

                    const ruta = '/SistemaTransmillas2025/nueva_plataforma/' + data;

                    return `<img src="${ruta}" 
                    style="height:50px; width:75px; object-fit:cover; 
                           border-radius:4px; border:1px solid #dee2e6; cursor:pointer;"
                    onclick="window.open('${ruta}', '_blank')"
                    title="Ver imagen completa">`;
                }
            },
            {
                data: 'veh_img_reverso',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    if (!data) return '<span class="text-muted" style="font-size:11px;">Sin imagen</span>';

                    const ruta = '/SistemaTransmillas2025/nueva_plataforma/' + data;

                    return `<img src="${ruta}" 
                    style="height:50px; width:75px; object-fit:cover; 
                           border-radius:4px; border:1px solid #dee2e6; cursor:pointer;"
                    onclick="window.open('${ruta}', '_blank')"
                    title="Ver imagen completa">`;
                }
            },

            {
                data: 'veh_estado',
                render: function (data, type, row) {
                    if (data == 1) {
                        return '<span class="badge bg-success">Activo</span>';
                    } else {
                        return '<span class="badge bg-danger">Inactivo</span>';
                    }
                }
            },

            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-primary btn-editar-modal" 
                                title="Editar" data-id="${row.idvehiculos}">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-danger eliminar-vehiculo"
                                title="Eliminar" data-id="${row.idvehiculos}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    `;
                }
            }
        ]
    });

    $('#filtrotipodevehiculo, #filtroestado').on('change', function () {
        tabla.ajax.reload();
    });

    //Un solo evento para guardar
    $('#btnGuardar').on('click', function (e) {
        e.preventDefault();

        // Validar Placa
        const placa = $('input[name="veh_placa"]').val().trim();
        if (placa === "") {
            Swal.fire("Error", "La placa es obligatoria", "error");
            return;
        }

        // Validar Dueño — usando name del select
        if ($('select[name="veh_dueno"]').val() === "") {
            Swal.fire("Error", "Debe seleccionar un dueño para el vehículo", "error");
            return;
        }

        // Validar archivos — usando name en lugar de id
        const validarImagen = (input, nombreCampo) => {
            if (input && input.files.length > 0) {
                const archivo = input.files[0];
                const extensiones = /(\.jpg|\.jpeg|\.png)$/i;
                if (!extensiones.exec(archivo.name)) {
                    Swal.fire("Error", `El archivo en ${nombreCampo} debe ser JPG o PNG`, "error");
                    return false;
                }
                if (archivo.size > 5 * 1024 * 1024) {
                    Swal.fire("Error", `La foto de ${nombreCampo} es muy pesada (máx 5MB)`, "error");
                    return false;
                }
            }
            return true;
        };

        // Seleccionamos por name ya que no tienen id
        const inputSoat = $('input[name="veh_img_soat"]')[0];
        const inputTecno = $('input[name="veh_img_tecnomecanica"]')[0];
        const inputFrente = $('input[name="veh_img_anverso"]')[0];
        const inputRespaldo = $('input[name="veh_img_reverso"]')[0];

        if (!validarImagen(inputSoat, "SOAT")) return;
        if (!validarImagen(inputTecno, "Tecnomecánica")) return;
        if (!validarImagen(inputFrente, "Tarjeta Propiedad Frente")) return;
        if (!validarImagen(inputRespaldo, "Tarjeta Propiedad Respaldo")) return;

        const formulario = document.getElementById('formVehiculo');
        let datos = new FormData(formulario);
        datos.append("guardar_vehiculo", true);

        Swal.fire({
            title: 'Guardando...',
            text: 'Subiendo información e imágenes al servidor',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: urlController,
            type: 'POST',
            data: datos,
            cache: false,
            contentType: false,
            processData: false,
            success: function (res) {
                Swal.close();
                try {
                    const response = typeof res === 'object' ? res : JSON.parse(res);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.mensaje,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#modalVehiculo').modal('hide');
                        formulario.reset();
                        if ($.fn.DataTable.isDataTable('#tablaVehiculos')) {
                            $('#tablaVehiculos').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        Swal.fire("Error", response.mensaje, "error");
                    }
                } catch (e) {
                    console.error("Respuesta no válida:", res);
                    Swal.fire("Error", "Error en el formato de respuesta del servidor.", "error");
                }
            },
            error: function () {
                Swal.close();
                Swal.fire("Error", "No se pudo conectar con el servidor", "error");
            }
        });
    });
});

// Cambiar estado
$('#tablaVehiculos tbody').on('change', '.cambiar-campo', function () {
    const id = $(this).data('id');
    const campo = $(this).data('campo');
    const valor = $(this).val();

    $.ajax({
        url: urlController,
        type: 'POST',
        data: { actualizar_campo: true, id, campo, valor },
        success: function () {
            $('#tablaVehiculos').DataTable().ajax.reload(null, false);
        },
        error: function () {
            alert("Hubo un error al actualizar.");
        }
    });
});

// Eliminar vehículo
$('#tablaVehiculos tbody').on('click', '.eliminar-vehiculo', function () {
    const id = $(this).data('id');

    Swal.fire({
        title: '¿Eliminar vehículo?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: urlController,
                type: 'POST',
                data: { eliminar_vehiculo: true, id },
                success: function () {
                    $('#tablaVehiculos').DataTable().ajax.reload(null, false);
                },
                error: function () {
                    alert('Error al eliminar el vehículo.');
                }
            });
        }
    });
});

// EDITAR VEHÍCULO - Abrir modal con datos
$('#tablaVehiculos tbody').on('click', '.btn-editar-modal', function () {
    const id = $(this).data('id');

    $.ajax({
        url: urlController,
        type: 'POST',
        data: { obtener_vehiculo: true, id: id },
        success: function (res) {
            try {
                const v = typeof res === 'object' ? res : JSON.parse(res);

                $('#edit_veh_id').val(v.idvehiculos);
                $('#edit_veh_tipo').val(v.veh_tipo);
                $('#edit_veh_marca').val(v.veh_marca);
                $('#edit_veh_placa').val(v.veh_placa);
                $('#edit_veh_modelo').val(v.veh_modelo);
                $('#edit_veh_color').val(v.veh_color);
                $('#edit_veh_tipov').val(v.veh_tipov);
                $('#edit_veh_dueno').val(v.veh_dueño);
                $('#edit_veh_fechaseguro').val(v.veh_fechaseguro);
                $('#edit_veh_fechategnomecanica').val(v.veh_fechategnomecanica);
                $('#edit_veh_fechamantenimiento').val(v.veh_fechamantenimiento);
                $('#edit_veh_kilactual').val(v.veh_kilactual);
                $('#edit_veh_calkmcambioaceite').val(v.veh_calkmcambioaceite);
                $('#edit_veh_chasis').val(v.veh_chasis);
                $('#edit_veh_motor').val(v.veh_motor);
                $('#edit_veh_cilidraje').val(v.veh_cilidraje);
                $('#edit_veh_usuve').val(v.veh_usuve);
                $('#edit_veh_estado').val(v.veh_estado);
                $('#edit_veh_especificaciones').val(v.veh_observaciones);

                // Previews de imágenes
                mostrarPreviewEditar('preview_soat', v.veh_img_soat);
                mostrarPreviewEditar('preview_tecnomecanica', v.veh_img_tecnomecanica);
                mostrarPreviewEditar('preview_anverso', v.veh_img_anverso);
                mostrarPreviewEditar('preview_reverso', v.veh_img_reverso);

                $('#modalEditarVehiculo').modal('show');

            } catch (e) {
                Swal.fire("Error", "No se pudieron cargar los datos del vehículo", "error");
            }
        },
        error: function () {
            Swal.fire("Error", "No se pudo conectar con el servidor", "error");
        }
    });
});

function mostrarPreviewEditar(containerId, ruta) {
    const div = document.getElementById(containerId);
    if (ruta) {
        const urlCompleta = '/SistemaTransmillas2025/nueva_plataforma/' + ruta;
        div.innerHTML = `
            <a href="${urlCompleta}" target="_blank" title="Ver imagen completa">
                <img src="${urlCompleta}" 
                    style="max-height:60px; border-radius:4px; border:1px solid #dee2e6; 
                           margin-bottom:4px; cursor:pointer;"
                    onerror="this.style.display='none'">
            </a>
            <small class="text-muted d-block">
                <a href="${urlCompleta}" target="_blank">🔍 Ver imagen actual</a> 
                — Sube una nueva para reemplazar
            </small>`;
    } else {
        div.innerHTML = '<small class="text-muted">Sin imagen</small>';
    }
}

// EDITAR VEHÍCULO - Guardar cambios
$('#btnActualizar').on('click', function (e) {
    e.preventDefault();

    const formulario = document.getElementById('formEditarVehiculo');
    const datos = new FormData(formulario);
    datos.append('actualizar_vehiculo', true);

    Swal.fire({
        title: 'Actualizando...',
        text: 'Guardando cambios en el servidor',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: urlController,
        type: 'POST',
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        success: function (res) {
            Swal.close();
            try {
                const response = typeof res === 'object' ? res : JSON.parse(res);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: response.mensaje,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#modalEditarVehiculo').modal('hide');
                    $('#tablaVehiculos').DataTable().ajax.reload(null, false);
                } else {
                    Swal.fire("Error", response.mensaje, "error");
                }
            } catch (e) {
                Swal.fire("Error", "Error en la respuesta del servidor", "error");
            }
        },
        error: function () {
            Swal.close();
            Swal.fire("Error", "No se pudo conectar con el servidor", "error");
        }
    });
});

