const urlController = '/SistemaTransmillas2025/nueva_plataforma/controller/VehiculosController.php';
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
        order: [[14, 'asc']],

        columns: [
            { data: 'veh_tipo' },
            { data: 'veh_marca' },
            { data: 'veh_placa' },
            { data: 'veh_modelo' },
            { data: 'veh_propiedad' },
            { data: 'usu_nombre' },
            { data: 'veh_fechaseguro' },
            { data: 'veh_fechategnomecanica' },
            { data: 'veh_fechamantenimiento' },
            { data: 'veh_kilactual' },              // Kilometraje Actual (NUEVO)
            { data: 'veh_kmactual_cambioaceite' },  // Km Al Cambio de Aceite (RENOMBRADO)
            { data: 'veh_calkmcambioaceite' },      // Limite Km cambio aceite (existente)

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
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    const total = row.total_comparendos ?? 0;
                    const badge = total > 0
                        ? `<span class="badge bg-danger">${total}</span>`
                        : `<span style="font-size:13px;">0</span>`;
                    return `
                        <button class="btn btn-sm btn-primary btn-ver-comparendos"
                                style="min-width:52px;"
                                title="Ver comparendos de este vehículo"
                                data-id="${row.idvehiculos}"
                                data-placa="${row.veh_placa}">
                               ${badge}
                        </button>`;
                }
            },

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
                        </select>`;
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
                        </button>`;
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
                        </button>`;
                }
            }
        ]
    });

    // Filtro dueño según propiedad del vehiculo (modal AGREGAR)
    $('#veh_propiedad').on('change', function () {
        const clasificacion = $(this).val();
        const $dueno = $('#veh_dueno');
        const $wrapper = $('#wrapper_dueno_agregar');

        if (clasificacion === 'empresa') {
            $wrapper.hide();
            $dueno.removeAttr('required');
            $dueno.val(14);
        } else {
            $wrapper.show();
            $dueno.attr('required', 'required');
            $dueno.val('');
        }
    });

    // Filtro dueño según propiedad del vehiculo (modal EDITAR)
    $('#edit_veh_propiedad').on('change', function () {
        const clasificacion = $(this).val();
        const $dueno = $('#edit_veh_dueno');
        const $wrapper = $('#wrapper_dueno_editar');

        if (clasificacion === 'empresa') {
            $wrapper.hide();
            $dueno.removeAttr('required');
            $dueno.val(14);
        } else {
            $wrapper.show();
            $dueno.val('');
        }
    });

    $('#filtrotipodevehiculo, #filtroestado').on('change', function () {
        tabla.ajax.reload();
    });

    // Guardar vehículo
    $('#btnGuardar').on('click', function (e) {
        e.preventDefault();

        const camposTexto = [
            { name: 'veh_tipo', label: 'Tipo Vehículo' },
            { name: 'veh_marca', label: 'Marca' },
            { name: 'veh_placa', label: 'Placa' },
            { name: 'veh_modelo', label: 'Modelo' },
            { name: 'veh_color', label: 'Color' },
            { name: 'veh_tipov', label: 'Tipo' },
            { name: 'veh_kilactual', label: 'Kilometraje Actual' },
            { name: 'veh_kmactual_cambioaceite', label: 'Kilometraje Actual Al Cambio de Aceite' },
            { name: 'veh_calkmcambioaceite', label: 'Km Cambio Aceite' },
            { name: 'veh_chasis', label: 'Número Chasis' },
            { name: 'veh_motor', label: 'Número Motor' },
            { name: 'veh_cilidraje', label: 'Número Cilindraje' },
            { name: 'veh_usuve', label: 'Uso del Vehículo' },
        ];

        for (const campo of camposTexto) {
            const valor = $(`[name="${campo.name}"]`).val().trim();
            if (valor === '') {
                Swal.fire('Error', `El campo "${campo.label}" es obligatorio`, 'error');
                return;
            }
        }

        if ($('select[name="veh_estado"]').val() === '') {
            Swal.fire('Error', 'Debe seleccionar un estado para el vehículo', 'error');
            return;
        }

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

        const inputSoat = $('input[name="veh_img_soat"]')[0];
        const inputTecno = $('input[name="veh_img_tecnomecanica"]')[0];
        const inputFrente = $('input[name="veh_img_anverso"]')[0];
        const inputRespaldo = $('input[name="veh_img_reverso"]')[0];

        if (!validarImagen(inputSoat, "SOAT")) return;
        if (!validarImagen(inputTecno, "Tecnomecánica")) return;
        if (!validarImagen(inputFrente, "Tarjeta Propiedad Frente")) return;
        if (!validarImagen(inputRespaldo, "Tarjeta Propiedad Respaldo")) return;

        serializarHerramientas();

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
                $('#edit_veh_propiedad').val(v.veh_propiedad).trigger('change');

                // Cargar herramientas existentes
                const listaEdit = document.getElementById('listaHerramientasEdit');
                listaEdit.innerHTML = '';
                if (v.veh_equipo_carretera) {
                    try {
                        const herramientas = JSON.parse(v.veh_equipo_carretera);
                        herramientas.forEach(h => {
                            listaEdit.appendChild(crearFilaHerramientaEdit(h.nombre, h.existe));
                        });
                    } catch (e) { }
                }

                $('#edit_veh_dueno').val(v.veh_dueño);
                $('#edit_veh_kilactual').val(v.veh_kilactual);                         // Kilometraje Actual (NUEVO)
                $('#edit_veh_kmactual_cambioaceite').val(v.veh_kmactual_cambioaceite); // Km Al Cambio de Aceite (RENOMBRADO)
                $('#edit_veh_fecha_soat').val(v.veh_fechaseguro);
                $('#edit_veh_fechategnomecanica').val(v.veh_fechategnomecanica);
                $('#edit_veh_fechamantenimiento').val(v.veh_fechamantenimiento);
                $('#edit_veh_calkmcambioaceite').val(v.veh_calkmcambioaceite);
                $('#edit_veh_chasis').val(v.veh_chasis);
                $('#edit_veh_motor').val(v.veh_motor);
                $('#edit_veh_cilidraje').val(v.veh_cilidraje);
                $('#edit_veh_usuve').val(v.veh_usuve);
                $('#edit_veh_estado').val(v.veh_estado);
                $('#edit_veh_especificaciones').val(v.veh_observaciones);

                // Vista previa de imágenes
                mostrarPreviewEditar('preview_soat', v.veh_img_soat);
                mostrarPreviewEditar('preview_tecnomecanica', v.veh_img_tecnomecanica);
                mostrarPreviewEditar('preview_anverso', v.veh_img_anverso);
                mostrarPreviewEditar('preview_reverso', v.veh_img_reverso);
                mostrarPreviewEditar('preview_actual_frente', v.veh_img_actual_frente);
                mostrarPreviewEditar('preview_actual_trasera', v.veh_img_actual_trasera);

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

    serializarHerramientasEdit();

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

// EQUIPO DE CARRETERA (modal AGREGAR)
function crearFilaHerramienta(nombre = '', existe = 'si') {
    const div = document.createElement('div');
    div.className = 'd-flex align-items-center gap-2 mb-2';
    div.innerHTML = `
        <input type="text" class="form-control form-control-sm herramienta-nombre" 
               placeholder="Nombre de la herramienta" value="${nombre}" style="flex:2">
        <select class="form-select form-select-sm herramienta-existe" style="flex:1">
            <option value="si" ${existe === 'si' ? 'selected' : ''}>✅ Activo</option>
            <option value="no" ${existe === 'no' ? 'selected' : ''}>❌ Inactivo</option>
        </select>
        <button type="button" class="btn btn-sm btn-danger btn-eliminar-herramienta">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
    div.querySelector('.btn-eliminar-herramienta').addEventListener('click', function () {
        div.remove();
    });
    return div;
}

document.getElementById('btnAgregarHerramienta').addEventListener('click', function () {
    document.getElementById('listaHerramientas').appendChild(crearFilaHerramienta());
});

// EQUIPO DE CARRETERA (modal EDITAR)
function crearFilaHerramientaEdit(nombre = '', existe = 'si') {
    const div = document.createElement('div');
    div.className = 'd-flex align-items-center gap-2 mb-2';
    div.innerHTML = `
        <input type="text" class="form-control form-control-sm herramienta-nombre-edit" 
               placeholder="Nombre de la herramienta" value="${nombre}" style="flex:2">
        <select class="form-select form-select-sm herramienta-existe-edit" style="flex:1">
            <option value="si" ${existe === 'si' ? 'selected' : ''}>✅ Activo</option>
            <option value="no" ${existe === 'no' ? 'selected' : ''}>❌ Inactivo</option>
        </select>
        <button type="button" class="btn btn-sm btn-danger btn-eliminar-herramienta-edit">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
    div.querySelector('.btn-eliminar-herramienta-edit').addEventListener('click', function () {
        div.remove();
    });
    return div;
}

document.getElementById('btnAgregarHerramientaEdit').addEventListener('click', function () {
    document.getElementById('listaHerramientasEdit').appendChild(crearFilaHerramientaEdit());
});

// SERIALIZAR JSON antes de guardar (modal AGREGAR)
function serializarHerramientas() {
    const filas = document.querySelectorAll('#listaHerramientas .d-flex');
    const herramientas = [];
    filas.forEach(fila => {
        const nombre = fila.querySelector('.herramienta-nombre').value.trim();
        const existe = fila.querySelector('.herramienta-existe').value;
        if (nombre !== '') herramientas.push({ nombre, existe });
    });
    document.getElementById('veh_equipo_carretera').value = JSON.stringify(herramientas);
}

// SERIALIZAR JSON antes de guardar (modal EDITAR)
function serializarHerramientasEdit() {
    const filas = document.querySelectorAll('#listaHerramientasEdit .d-flex');
    const herramientas = [];
    filas.forEach(fila => {
        const nombre = fila.querySelector('.herramienta-nombre-edit').value.trim();
        const existe = fila.querySelector('.herramienta-existe-edit').value;
        if (nombre !== '') herramientas.push({ nombre, existe });
    });
    document.getElementById('edit_veh_equipo_carretera').value = JSON.stringify(herramientas);
}

// EQUIPO DE CARRETERA (modal ENTREGA VEHICULO)
function crearFilaHerramientaEntrega(nombre = '', existe = 'si') {
    const div = document.createElement('div');
    div.className = 'd-flex align-items-center gap-2 mb-2';
    div.innerHTML = `
        <input type="text" class="form-control form-control-sm herramienta-nombre-entrega"
               placeholder="Nombre de la herramienta" value="${nombre}" style="flex:2">
        <select class="form-select form-select-sm herramienta-existe-entrega" style="flex:1">
            <option value="si" ${existe === 'si' ? 'selected' : ''}>✅ Activo</option>
            <option value="no" ${existe === 'no' ? 'selected' : ''}>❌ Inactivo</option>
        </select>
        <button type="button" class="btn btn-sm btn-danger btn-eliminar-herramienta-entrega">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
    div.querySelector('.btn-eliminar-herramienta-entrega').addEventListener('click', function () {
        div.remove();
    });
    return div;
}

$(document).on('click', '#btnAgregarHerramientaEntrega', function () {
    document.getElementById('listaHerramientasEntrega').appendChild(crearFilaHerramientaEntrega());
});

// SERIALIZAR JSON antes de guardar (modal ENTREGA VEHICULO)
function serializarHerramientasEntrega() {
    const filas = document.querySelectorAll('#listaHerramientasEntrega .d-flex');
    const herramientas = [];
    filas.forEach(fila => {
        const nombre = fila.querySelector('.herramienta-nombre-entrega').value.trim();
        const existe = fila.querySelector('.herramienta-existe-entrega').value;
        if (nombre !== '') herramientas.push({ nombre, existe });
    });
    document.getElementById('ent_equipo_carretera').value = JSON.stringify(herramientas);
}

// Al seleccionar vehículo → cargar su equipo automáticamente
$('#ent_vehiculo_id').on('change', function () {
    const idVehiculo = $(this).val();
    const lista = document.getElementById('listaHerramientasEntrega');
    lista.innerHTML = '';

    if (!idVehiculo) return;

    $.ajax({
        url: urlController,
        type: 'POST',
        data: { obtener_equipo_vehiculo: true, id: idVehiculo },
        success: function (res) {
            try {
                const data = typeof res === 'object' ? res : JSON.parse(res);
                if (data.equipo && data.equipo.length > 0) {
                    data.equipo.forEach(h => {
                        lista.appendChild(crearFilaHerramientaEntrega(h.nombre, h.existe));
                    });
                }
            } catch (e) {
                console.warn('No se pudo cargar el equipo del vehículo', e);
            }
        }
    });
});

// Cambiar labels según tipo de entrega
$('#ent_tipoentrega').on('change', function () {
    const tipo = $(this).val();

    if (tipo === 'inicial') {
        $('#label_recibido').html('Recibido por <span class="text-danger">*</span>');
        $('#wrapper_recibido_fijo').hide();
        $('#wrapper_recibido_conductor').show();
        $('#label_entregado').text('Entregado por');
        $('#wrapper_entregado_conductor').hide();
        $('#wrapper_entregado_fijo').show();
    } else {
        $('#label_recibido').text('Recibido por');
        $('#wrapper_recibido_fijo').show();
        $('#wrapper_recibido_conductor').hide();
        $('#label_entregado').html('Entregado <span class="text-danger">*</span>');
        $('#wrapper_entregado_conductor').show();
        $('#wrapper_entregado_fijo').hide();
    }
});

// GUARDAR ENTREGA DE VEHÍCULO
$('#btnGuardarEntrega').on('click', function () {

    const tipoEntrega = document.getElementById('ent_tipoentrega');
    const vehiculoSelect = document.getElementById('ent_vehiculo_id');
    const operadorSelect = document.getElementById('ent_idusuario');
    const fechaEntrega = $('[name="ent_fechaentrega"]').val();
    const fechaRegistro = $('[name="ent_fecharegist"]').val();
    const sedeEl = document.getElementById('ent_sede');
    const sede = sedeEl ? (sedeEl.value || '').trim() : '';

    if (!tipoEntrega.value) { Swal.fire('Error', 'Debe seleccionar el tipo de entrega', 'error'); return; }
    if (!vehiculoSelect.value) { Swal.fire('Error', 'Debe seleccionar un vehículo', 'error'); return; }
    if (!operadorSelect.value) { Swal.fire('Error', 'Debe seleccionar el conductor que entrega', 'error'); return; }
    if (!fechaEntrega) { Swal.fire('Error', 'Debe ingresar la fecha de entrega', 'error'); return; }
    if (!fechaRegistro) { Swal.fire('Error', 'Debe ingresar la fecha de registro', 'error'); return; }
    if (!sede) { Swal.fire('Error', 'Debe ingresar la sede de entrega', 'error'); return; }

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

    const inputFrente = $('input[name="ent_img_frente"]')[0];
    const inputTrasera = $('input[name="ent_img_trasera"]')[0];

    if (!validarImagen(inputFrente, "Foto Frente")) return;
    if (!validarImagen(inputTrasera, "Foto Respaldo")) return;

    const opcionVehiculo = vehiculoSelect.options[vehiculoSelect.selectedIndex];
    const textoVehiculo = opcionVehiculo.getAttribute('data-texto');

    const datos = new FormData();
    datos.append('ent_tipoentrega', tipoEntrega.value);
    datos.append('guardar_entrega', true);
    datos.append('ent_vehiculo', textoVehiculo);
    datos.append('ent_idusuario', operadorSelect.value);
    datos.append('ent_fechaentrega', fechaEntrega);
    datos.append('ent_fecharegist', fechaRegistro);
    datos.append('ent_sede', sede);
    datos.append('ent_img_frente', inputFrente.files[0]);
    datos.append('ent_img_trasera', inputTrasera.files[0]);
    serializarHerramientasEntrega();
    datos.append('ent_equipo_carretera', document.getElementById('ent_equipo_carretera').value);
    datos.append('ent_observaciones', $('[name="ent_observaciones"]').val());

    if (!firmaEntregaRealizada) {
        Swal.fire('Error', 'Debe firmar antes de guardar la entrega', 'error');
        return;
    }
    const firmaBase64 = firmaEntregaCanvas.toDataURL('image/png');
    document.getElementById('ent_firma_base64').value = firmaBase64;
    datos.append('ent_firma_base64', firmaBase64);

    Swal.fire({
        title: 'Guardando...',
        text: 'Registrando entrega del vehículo',
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
                        title: '¡Entrega registrada!',
                        text: response.mensaje,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#modalEntregaVehiculo').modal('hide');
                    document.getElementById('formEntregaVehiculo').reset();
                } else {
                    Swal.fire('Error', response.mensaje, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error en la respuesta del servidor', 'error');
            }
        },
        error: function () {
            Swal.close();
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    });
});

// FIRMA DIGITAL (modal ENTREGA)
let firmaEntregaRealizada = false;
let firmaEntregaDrawing = false;
let firmaEntregaCanvas = null;
let firmaEntregaCtx = null;

function initFirmaEntrega() {
    firmaEntregaCanvas = document.getElementById('firmaEntregaCanvas');
    if (!firmaEntregaCtx) {
        firmaEntregaCtx = firmaEntregaCanvas.getContext('2d');
    }
    resizeFirmaEntrega();
}

function resizeFirmaEntrega() {
    if (!firmaEntregaCanvas) return;
    const ratio = window.devicePixelRatio || 1;
    const rect = firmaEntregaCanvas.getBoundingClientRect();
    firmaEntregaCanvas.width = rect.width * ratio;
    firmaEntregaCanvas.height = rect.height * ratio;
    firmaEntregaCtx.scale(ratio, ratio);
    firmaEntregaCtx.lineWidth = 2;
    firmaEntregaCtx.lineCap = 'round';
    firmaEntregaCtx.strokeStyle = '#000';
}

function getPosEntrega(e) {
    const rect = firmaEntregaCanvas.getBoundingClientRect();
    return e.touches
        ? { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top }
        : { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

document.getElementById('modalEntregaVehiculo').addEventListener('shown.bs.modal', function () {
    initFirmaEntrega();
});

$(document).on('mousedown', '#firmaEntregaCanvas', function (e) {
    firmaEntregaDrawing = true;
    firmaEntregaRealizada = true;
    const pos = getPosEntrega(e.originalEvent);
    firmaEntregaCtx.beginPath();
    firmaEntregaCtx.moveTo(pos.x, pos.y);
});

$(document).on('mousemove', '#firmaEntregaCanvas', function (e) {
    if (!firmaEntregaDrawing) return;
    const pos = getPosEntrega(e.originalEvent);
    firmaEntregaCtx.lineTo(pos.x, pos.y);
    firmaEntregaCtx.stroke();
});

$(document).on('mouseup mouseout', '#firmaEntregaCanvas', function () {
    firmaEntregaDrawing = false;
});

document.getElementById('firmaEntregaCanvas').addEventListener('touchstart', function (e) {
    e.preventDefault();
    firmaEntregaDrawing = true;
    firmaEntregaRealizada = true;
    const pos = getPosEntrega(e);
    firmaEntregaCtx.beginPath();
    firmaEntregaCtx.moveTo(pos.x, pos.y);
}, { passive: false });

document.getElementById('firmaEntregaCanvas').addEventListener('touchmove', function (e) {
    e.preventDefault();
    if (!firmaEntregaDrawing) return;
    const pos = getPosEntrega(e);
    firmaEntregaCtx.lineTo(pos.x, pos.y);
    firmaEntregaCtx.stroke();
}, { passive: false });

document.getElementById('firmaEntregaCanvas').addEventListener('touchend', function () {
    firmaEntregaDrawing = false;
});

$(document).on('click', '#btnLimpiarFirmaEntrega', function () {
    if (firmaEntregaCtx && firmaEntregaCanvas) {
        firmaEntregaCtx.clearRect(0, 0, firmaEntregaCanvas.width, firmaEntregaCanvas.height);
    }
    firmaEntregaRealizada = false;
    document.getElementById('ent_firma_base64').value = '';
});

document.getElementById('modalEntregaVehiculo').addEventListener('hidden.bs.modal', function () {
    if (firmaEntregaCtx && firmaEntregaCanvas) {
        firmaEntregaCtx.clearRect(0, 0, firmaEntregaCanvas.width, firmaEntregaCanvas.height);
    }
    firmaEntregaRealizada = false;
    firmaEntregaDrawing = false;
});

// COMPARENDOS

$(document).on('change', '#com_operador_id', function () {
    const idOp = $(this).val();
    const $info = $('#infoHojaVida');
    const $texto = $('#textoHojaVida');

    if (!idOp) { $info.hide(); return; }

    $.ajax({
        url: urlController,
        type: 'POST',
        data: { obtener_comparendos_operador: true, id_operador: idOp },
        success: function (res) {
            try {
                const data = typeof res === 'object' ? res : JSON.parse(res);
                if (data.total !== undefined) {
                    const msg = data.total === 0
                        ? 'Este operador no tiene comparendos registrados.'
                        : `Este operador tiene <strong>${data.total}</strong> comparendo(s) registrado(s).`;
                    $texto.html(msg);
                    $info.show();
                } else {
                    $info.hide();
                }
            } catch (e) { $info.hide(); }
        }
    });
});

$(document).on('click', '#btnGuardarComparendo', function () {

    const operador = $('#com_operador_id').val();
    const vehiculo = $('#com_vehiculo_id').val();
    const estado = $('#com_estado').val();
    const fecha = $('#com_fecha').val();

    if (!operador) { Swal.fire('Error', 'Debe seleccionar un operador', 'error'); return; }
    if (!vehiculo) { Swal.fire('Error', 'Debe seleccionar un vehículo', 'error'); return; }
    if (!estado) { Swal.fire('Error', 'Debe seleccionar el estado del comparendo', 'error'); return; }
    if (!fecha) { Swal.fire('Error', 'Debe ingresar la fecha del comparendo', 'error'); return; }

    const inputFoto = document.getElementById('com_foto');
    if (inputFoto && inputFoto.files.length > 0) {
        const archivo = inputFoto.files[0];
        if (!/(\.jpg|\.jpeg|\.png)$/i.test(archivo.name)) {
            Swal.fire('Error', 'La foto debe ser JPG o PNG', 'error'); return;
        }
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'La foto es muy pesada (máx 5MB)', 'error'); return;
        }
    }

    const datos = new FormData(document.getElementById('formComparendo'));
    datos.append('guardar_comparendo', true);

    Swal.fire({
        title: 'Guardando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
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
                const r = typeof res === 'object' ? res : JSON.parse(res);
                if (r.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Comparendo guardado!',
                        text: r.mensaje,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#modalAgregarComparendo').modal('hide');
                    document.getElementById('formComparendo').reset();
                    $('#infoHojaVida').hide();
                    if ($.fn.DataTable.isDataTable('#tablaVehiculos')) {
                        $('#tablaVehiculos').DataTable().ajax.reload(null, false);
                    }
                } else {
                    Swal.fire('Error', r.mensaje, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error en la respuesta del servidor', 'error');
            }
        },
        error: function () {
            Swal.close();
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    });
});

$(document).on('click', '.btn-ver-comparendos', function () {
    const idVehiculo = $(this).data('id');
    const placa = $(this).data('placa');

    $('#tituloPlacaComparendo').text(placa);
    $('#cuerpoTablaComparendos').html('<tr><td colspan="6" class="text-muted">Cargando...</td></tr>');
    $('#modalVerComparendos').modal('show');

    $.ajax({
        url: urlController,
        type: 'POST',
        data: { obtener_comparendos: true, id: idVehiculo },
        success: function (res) {
            try {
                const lista = typeof res === 'object' ? res : JSON.parse(res);
                const $tbody = $('#cuerpoTablaComparendos');
                $tbody.empty();

                if (!lista || lista.length === 0) {
                    $tbody.html('<tr><td colspan="6" class="text-muted">Sin comparendos registrados</td></tr>');
                    return;
                }

                lista.forEach((c, i) => {
                    const estadoBadge = c.com_estado === 'Pagado'
                        ? '<span class="badge bg-success">✅ Pagado</span>'
                        : '<span class="badge bg-warning text-dark">⏳ Pendiente</span>';

                    const foto = c.com_foto
                        ? `<img src="/SistemaTransmillas2025/nueva_plataforma/${c.com_foto}"
                                style="height:48px;width:72px;object-fit:cover;
                                       border-radius:4px;border:1px solid #dee2e6;cursor:pointer;"
                                onclick="window.open('/SistemaTransmillas2025/nueva_plataforma/${c.com_foto}','_blank')"
                                title="Ver foto completa">`
                        : '<span class="text-muted" style="font-size:11px;">Sin foto</span>';

                    $tbody.append(`
                        <tr>
                            <td>${i + 1}</td>
                            <td>${c.operador_nombre ?? '—'}</td>
                            <td>${c.vehiculo_placa} — ${c.vehiculo_marca} ${c.vehiculo_modelo}</td>
                            <td>${estadoBadge}</td>
                            <td>${c.com_fecha}</td>
                            <td>${foto}</td>
                        </tr>
                    `);
                });

            } catch (e) {
                $('#cuerpoTablaComparendos').html('<tr><td colspan="6" class="text-danger">Error al cargar los datos</td></tr>');
            }
        },
        error: function () {
            $('#cuerpoTablaComparendos').html('<tr><td colspan="6" class="text-danger">Error de conexión</td></tr>');
        }
    });
});