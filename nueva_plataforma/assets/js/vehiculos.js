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

    // ✅ UN SOLO evento para guardar
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
        datos.append("guardar_vehiculo", true); // ✅ coincide con el controlador

        Swal.fire({
            title: 'Guardando...',
            text: 'Subiendo información e imágenes al servidor',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'nueva_plataforma/controller/VehiculosController.php',
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
        url: 'nueva_plataforma/controller/VehiculosController.php',
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
                url: 'nueva_plataforma/controller/VehiculosController.php',
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