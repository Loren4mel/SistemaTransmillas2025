//const urlController = '/SistemaTransmillas2025/nueva_plataforma/controller/VehiculosController.php';

// DESPUÉS — detecta automáticamente el entorno
const baseUrl = window.location.hostname === 'localhost'
    ? '/SistemaTransmillas2025/nueva_plataforma'
    : '/nueva_plataforma';

const urlController = `${baseUrl}/controller/VehiculosController.php`;

function calcularDiasRestantes(fechaStr) {
    if (!fechaStr || fechaStr === '0000-00-00') return null;
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const partes = fechaStr.split('-');
    if (partes.length !== 3) return null;
    const fecha = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    if (isNaN(fecha.getTime())) return null;
    const diff = fecha - hoy;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function generarBadgeDias(dias) {
    if (dias === null) return '';

    let color, texto;

    if (dias < 0) {
        color = '#c0392b'; // rojo — vencido
        texto = `Venció hace ${Math.abs(dias)} día${Math.abs(dias) !== 1 ? 's' : ''}`;
    } else if (dias === 0) {
        color = '#c0392b'; // rojo — vence hoy
        texto = 'Vence hoy';
    } else if (dias <= 30) {
        color = '#c0392b'; // rojo — menos de 30 días
        texto = `Faltan ${dias} día${dias !== 1 ? 's' : ''}`;
    } else {
        color = '#27ae60'; // verde — más de 30 días
        texto = `Faltan ${dias} días`;
    }

    return `<span style="background:${color}; color:#fff;
        font-size:11px; font-weight:bold; padding:3px 7px; border-radius:5px;
        display:inline-block; white-space:nowrap; margin-top:3px;">
        ${texto}
    </span>`;
}

$(document).ready(function () {
    const tabla = $('#tablaVehiculos').DataTable({
        ajax: {
            url: urlController,
            type: 'POST',
            data: function (d) {
                d.ajax = true;
                d.tipodevehiculo = $('#filtrotipodevehiculo').val();
                d.estado = $('#filtroestado').val();
                d.propiedadvehiculo = $('#filtropropiedad').val();
            },

            dataSrc: ''
        },

        order: [[14, 'asc']],

        columns: [
            { data: 'veh_tipo' },
            { data: 'veh_marca' },

            {
                data: 'veh_placa',
                render: function (data, type, row) {
                    const camposFaltantes = [];

                    if (!row.veh_tipo) camposFaltantes.push('Tipo Vehículo');
                    if (!row.veh_marca) camposFaltantes.push('Marca');
                    if (!row.veh_placa) camposFaltantes.push('Placa');
                    if (!row.veh_modelo) camposFaltantes.push('Modelo');
                    if (!row.veh_color) camposFaltantes.push('Color');
                    if (!row.veh_tipov) camposFaltantes.push('Tipo');
                    if (!row.veh_kilactual) camposFaltantes.push('Kilometraje Actual');
                    if (!row.veh_fechaseguro || row.veh_fechaseguro === '0000-00-00')
                        camposFaltantes.push('Fecha Seguro SOAT');
                    if (!row.veh_img_soat) camposFaltantes.push('Foto SOAT');
                    if (!row.veh_fechategnomecanica || row.veh_fechategnomecanica === '0000-00-00')
                        camposFaltantes.push('Fecha Tecnomecánica');
                    if (!row.veh_img_tecnomecanica) camposFaltantes.push('Foto Tecnomecánica');
                    if (!row.veh_fechamantenimiento || row.veh_fechamantenimiento === '0000-00-00')
                        camposFaltantes.push('Fecha Cambio Aceite');
                    if (!row.veh_kmactual_cambioaceite) camposFaltantes.push('Km Actual Al Cambio Aceite');
                    if (!row.veh_calkmcambioaceite) camposFaltantes.push('Límite Km Cambio Aceite');
                    if (!row.veh_chasis) camposFaltantes.push('Número Chasis');
                    if (!row.veh_motor) camposFaltantes.push('Número Motor');
                    if (!row.veh_cilidraje) camposFaltantes.push('Número Cilindraje');
                    if (!row.veh_usuve) camposFaltantes.push('Uso del Vehículo');
                    if (!row.veh_img_actual_frente) camposFaltantes.push('Foto Actual Frente');
                    if (!row.veh_img_actual_trasera) camposFaltantes.push('Foto Actual Respaldo');
                    if (!row.veh_img_anverso) camposFaltantes.push('Foto Tarjeta Propiedad Frente');
                    if (!row.veh_img_reverso) camposFaltantes.push('Foto Tarjeta Propiedad Respaldo');

                    const icono = camposFaltantes.length > 0
                        ? `<i class="fas fa-exclamation-triangle text-warning ms-1 btn-info-incompleta" 
                  style="cursor:pointer; font-size:13px;"
                  title="Información incompleta"
                  data-campos='${JSON.stringify(camposFaltantes)}'
                  data-placa="${data}"></i>`
                        : '';

                    return `${data} ${icono}`;
                }
            },

            { data: 'veh_modelo' },
            { data: 'veh_propiedad' },
            { data: 'usu_nombre' },

            {
                data: 'veh_fechaseguro',
                render: function (data, type, row) {
                    const diasRestantes = calcularDiasRestantes(data);
                    const badge = generarBadgeDias(diasRestantes);
                    return `<button type="button"
            class="btn btn-sm btn-link btn-editar-soat p-0"
            style="text-decoration:none; color:#1a3a5c; font-weight:600;"
            title="Clic para actualizar SOAT"
            data-id="${row.idvehiculos}"
            data-fecha="${data ?? ''}"
            data-imgsoat="${row.veh_img_soat ?? ''}">
            ${data ?? 'Sin fecha'} <i class="fas fa-pen" style="font-size:10px; opacity:0.6;"></i>
        </button>
        ${badge}`;
                }
            },

            {
                data: 'veh_fechategnomecanica',
                render: function (data, type, row) {
                    const diasRestantes = calcularDiasRestantes(data);
                    const badge = generarBadgeDias(diasRestantes);
                    return `<button type="button"
            class="btn btn-sm btn-link btn-editar-tecnomecanica p-0"
            style="text-decoration:none; color:#1a3a5c; font-weight:600;"
            title="Clic para actualizar Tecnomecánica"
            data-id="${row.idvehiculos}"
            data-fecha="${data ?? ''}"
            data-imgtecno="${row.veh_img_tecnomecanica ?? ''}">
            ${data ?? 'Sin fecha'} <i class="fas fa-pen" style="font-size:10px; opacity:0.6;"></i>
        </button>
        ${badge}`;
                }
            },

            {
                data: 'veh_fechamantenimiento',
                render: function (data, type, row) {
                    return `<button type="button" 
                    class="btn btn-sm btn-link btn-editar-aceite p-0"
                    style="text-decoration:none; color:#1a3a5c; font-weight:600;"
                    title="Clic para actualizar Cambio de Aceite"
                    data-id="${row.idvehiculos}"
                    data-fecha="${data ?? ''}"
                    data-kilactual="${row.veh_kilactual ?? ''}"
                    data-kmcambio="${row.veh_kmactual_cambioaceite ?? ''}"
                    data-limite="${row.veh_calkmcambioaceite ?? ''}">
                    ${data ?? 'Sin fecha'} <i class="fas fa-pen" style="font-size:10px; opacity:0.6;"></i>
                </button>`;
                }
            },

            { data: 'veh_kilactual' },
            { data: 'veh_kmactual_cambioaceite' },

            {
                data: null,
                render: function (data, type, row) {
                    const limpiarKm = v => parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0;
                    const kmActual = limpiarKm(row.veh_kilactual);
                    const kmAlCambio = limpiarKm(row.veh_kmactual_cambioaceite);
                    const limite = limpiarKm(row.veh_calkmcambioaceite);

                    if (!limite || !kmAlCambio) {
                        return '<span class="text-muted" style="font-size:12px;">Sin datos</span>';
                    }

                    // Km recorridos desde el último cambio
                    const kmRecorridos = kmActual - kmAlCambio;
                    // Km restantes para el próximo cambio
                    const kmRestantes = limite - kmRecorridos;

                    const recorridosStr = kmRecorridos.toLocaleString('es-CO');
                    const limiteStr = limite.toLocaleString('es-CO');

                    let color, icono, badge;

                    if (kmRestantes <= 500) {
                        color = '#c0392b';
                        icono = '';
                        badge = `<span style="background:#c0392b; color:#fff; font-size:11px; font-weight:bold;
              padding:4px 8px; border-radius:5px; display:inline-block; white-space:nowrap;">
                Faltan ${kmRestantes.toLocaleString('es-CO')} km
             </span>`;
                    } else {
                        color = '#27ae60';
                        icono = '';
                        badge = `<span style="background:#27ae60; color:#fff; font-size:11px; font-weight:bold;
              padding:4px 8px; border-radius:5px; display:inline-block; white-space:nowrap;">
                Faltan ${kmRestantes.toLocaleString('es-CO')} km
             </span>`;
                    }

                    return `
            <div style="text-align:center; line-height:1.6;">
                <div style="font-size:13px; font-weight:600; color:${color};">
                    ${icono} ${recorridosStr} / ${limiteStr} km
                </div>
                ${badge}
            </div>`;
                }
            },


            {
                data: 'veh_img_anverso',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    if (!data) return '<span class="text-muted" style="font-size:11px;">Sin imagen</span>';
                    const ruta = `${baseUrl}/` + data;
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
                    const ruta = `${baseUrl}/` + data;
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
                    const total = parseInt(row.total_comparendos ?? 0);
                    const pendientes = parseInt(row.comparendos_pendientes ?? 0);
                    let badge;
                    if (pendientes > 0) {
                        badge = `<span class="badge bg-danger">${total}</span>`;
                    } else if (total > 0) {
                        badge = `<span class="badge bg-success">${total}</span>`;
                    } else {
                        badge = `<span style="font-size:13px;">0</span>`;
                    }
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

            // Columna Historial de Conductores
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
            <button class="btn btn-sm btn-outline-info btn-ver-historial"
                    title="Ver historial de conductores"
                    data-id="${row.idvehiculos}"
                    data-placa="${row.veh_placa}">
                <i class="fas fa-history"></i>
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
        ],
        createdRow: function (row, data) {

            // 🔴 Rojo claro — comparendos pendientes
            if (parseInt(data.comparendos_pendientes) > 0) {
                $(row).attr('style', 'background-color: #fdd1d1 !important;');
                $(row).find('td').attr('style', 'background-color: #fdd1d1 !important;');
            }

            //Alerta preventiva aceite por KM
            const limpiarKm = v => {
                if (!v) return 0;
                // Elimina puntos de miles, reemplaza coma decimal
                return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0;
            };

            const kmActual = limpiarKm(data.veh_kilactual);
            const kmAlCambio = limpiarKm(data.veh_kmactual_cambioaceite);
            const limiteKm = limpiarKm(data.veh_calkmcambioaceite);

            // DEBUG — quita esta línea después de confirmar
            console.log(`[${data.veh_placa}] kmActual=${kmActual} kmAlCambio=${kmAlCambio} limiteKm=${limiteKm} recorridos=${kmActual - kmAlCambio} restantes=${limiteKm - (kmActual - kmAlCambio)}`);

            if (limiteKm > 0 && kmAlCambio > 0) {
                const kmRecorridos = kmActual - kmAlCambio;
                const kmRestantes = limiteKm - kmRecorridos;
            }
        }
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

    // Recargar tabla al cambiar filtros
    $('#filtrotipodevehiculo, #filtroestado, #filtropropiedad').on('change', function () {
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
            { name: 'veh_fechaseguro', label: 'Fecha Seguro SOAT' },
            { name: 'veh_fechategnomecanica', label: 'Fecha Tecnomecánica' },
            { name: 'veh_fechamantenimiento', label: 'Fecha Mantenimiento' },
            { name: 'veh_kmactual_cambioaceite', label: 'Kilometraje Actual Al Cambio de Aceite' },
            { name: 'veh_calkmcambioaceite', label: 'Km Cambio Aceite' },
            { name: 'veh_chasis', label: 'Número Chasis' },
            { name: 'veh_motor', label: 'Número Motor' },
            { name: 'veh_cilidraje', label: 'Número Cilindraje' },
            { name: 'veh_usuve', label: 'Uso del Vehículo' },
        ];

        const camposFecha = [
            { name: 'veh_fechaseguro', label: 'Fecha Vencimiento Seguro (SOAT)' },
            { name: 'veh_fechategnomecanica', label: 'Fecha Vencimiento Tecnomecánica' },
            { name: 'veh_fechamantenimiento', label: 'Fecha Último Cambio de Aceite' }
        ];

        for (const campo of camposFecha) {
            const valor = $(`[name="${campo.name}"]`).val();
            if (!valor || valor === '' || valor === '0000-00-00') {
                Swal.fire('Error', `El campo "${campo.label}" es obligatorio`, 'error');
                return;
            }
        }

        for (const campo of camposTexto) {
            const valor = $(`[name="${campo.name}"]`).val().trim();
            if (valor === '') {
                Swal.fire('Error', `El campo "${campo.label}" es obligatorio`, 'error');
                return;
            }
        }

        const camposFotos = [
            { name: 'veh_img_soat', label: 'Foto Vigente Seguro (SOAT)' },
            { name: 'veh_img_tecnomecanica', label: 'Foto Vigente Tecnomecánica' },
            { name: 'veh_img_actual_frente', label: 'Foto Actual del Vehículo (Frente)' },
            { name: 'veh_img_actual_trasera', label: 'Foto Actual del Vehículo (Respaldo)' },
            { name: 'veh_img_anverso', label: 'Foto Tarjeta Propiedad (Frente)' },
            { name: 'veh_img_reverso', label: 'Foto Tarjeta Propiedad (Respaldo)' }
        ];

        for (const campo of camposFotos) {
            const input = $(`input[name="${campo.name}"]`)[0];
            if (!input || !input.files || input.files.length === 0) {
                Swal.fire('Error', `El campo "${campo.label}" es obligatorio`, 'error');
                return;
            }
        }

        if ($('select[name="veh_estado"]').val() === '') {
            Swal.fire('Error', 'Debe seleccionar un estado para el vehículo', 'error');
            return;
        }

        // Validar imágenes antes de enviar
        const validarImagen = (input, nombreCampo) => {
            if (input && input.files.length > 0) {
                const archivo = input.files[0];
                const extensiones = /(\.jpg|\.jpeg|\.png|\.pdf)$/i;
                if (!extensiones.exec(archivo.name)) {
                    Swal.fire("Error", `El archivo en ${nombreCampo} debe ser JPG, PNG o PDF`, "error");
                    return false;
                }
                if (archivo.size > 5 * 1024 * 1024) {
                    Swal.fire("Error", `La foto de ${nombreCampo} es muy pesada (máx 5MB)`, "error");
                    return false;
                }
            }
            return true;
        };

        // Validar cada imagen individualmente para mostrar errores específicos
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

                        recargarSelectsVehiculos();

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

// Cambiar estado u otro campo directamente desde la tabla
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

// Eliminar vehículo con confirmación
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
                $('#edit_veh_calkmcambioaceite').val(v.veh_calkmcambioaceite);
                $('#edit_veh_chasis').val(v.veh_chasis);
                $('#edit_veh_motor').val(v.veh_motor);
                $('#edit_veh_cilidraje').val(v.veh_cilidraje);
                $('#edit_veh_usuve').val(v.veh_usuve);
                $('#edit_veh_estado').val(v.veh_estado);
                $('#edit_veh_especificaciones').val(v.veh_observaciones);

                // Vista previa de imágenes

                mostrarPreviewEditar('preview_anverso', v.veh_img_anverso, 'veh_img_anverso');
                mostrarPreviewEditar('preview_reverso', v.veh_img_reverso, 'veh_img_reverso');
                mostrarPreviewEditar('preview_actual_frente', v.veh_img_actual_frente, 'veh_img_actual_frente');
                mostrarPreviewEditar('preview_actual_trasera', v.veh_img_actual_trasera, 'veh_img_actual_trasera');

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

function mostrarPreviewEditar(containerId, ruta, campoImagen) {
    const div = document.getElementById(containerId);
    $(`#formEditarVehiculo input[name="eliminar_${campoImagen}"]`).val('0');
    if (ruta) {
        const urlCompleta = `${baseUrl}/` + ruta;
        div.innerHTML = `
            <a href="${urlCompleta}" target="_blank" title="Ver imagen completa">
                <img src="${urlCompleta}" 
                    style="max-height:60px; border-radius:4px; border:1px solid #dee2e6; 
                           margin-bottom:4px; cursor:pointer;"
                    onerror="this.style.display='none'">
            </a>
            <button type="button"
                    class="btn btn-sm btn-outline-danger btn-eliminar-img-editar ms-2"
                    data-campo="${campoImagen}"
                    data-preview="${containerId}">
                Eliminar
            </button>
            <small class="text-muted d-block">
                <a href="${urlCompleta}" target="_blank">🔍 Ver imagen actual</a> 
                — Sube una nueva para reemplazar
            </small>`;
    } else {
        div.innerHTML = '<small class="text-muted">Sin imagen</small>';
    }
}

// EDITAR VEHÍCULO - Guardar cambios
$(document).on('click', '.btn-eliminar-img-editar', function () {
    const campoImagen = $(this).data('campo');
    const previewId = $(this).data('preview');

    $(`#formEditarVehiculo input[name="eliminar_${campoImagen}"]`).val('1');
    $(`#formEditarVehiculo input[name="${campoImagen}"]`).val('');
    $(`#${previewId}`).html('<small class="text-danger">Imagen marcada para eliminar. Guarda los cambios para confirmar.</small>');
});

$('#formEditarVehiculo input[type="file"]').on('change', function () {
    if (this.files && this.files.length > 0) {
        $(`#formEditarVehiculo input[name="eliminar_${this.name}"]`).val('0');
    }
});

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
function crearFilaHerramientaEntrega(nombre = '', existe = 'si', fotoExistente = '') {
    const div = document.createElement('div');
    div.className = 'd-flex align-items-center gap-2 mb-2 herramienta-entrega-row';
    const checked = (existe === 'si') ? 'checked' : '';

    div.innerHTML = `
        <input type="text" class="form-control form-control-sm herramienta-nombre-entrega"
               placeholder="Nombre de la herramienta" value="${nombre}" style="flex:2">
        <div class="form-check form-switch d-flex align-items-center gap-2 mb-0" style="flex:1; min-width:120px;">
            <input class="form-check-input herramienta-existe-entrega" type="checkbox"
                   role="switch" style="width:2.5em; height:1.4em; cursor:pointer;" ${checked}>
            <label class="form-check-label herramienta-check-label mb-0">
                ${existe === 'si' ? '✅' : '❌'}
            </label>
        </div>
        <div class="d-flex align-items-center gap-1" style="flex:0 0 auto;">
            <label class="btn btn-sm btn-outline-secondary mb-0" title="Adjuntar foto a esta herramienta" style="cursor:pointer;">
                <i class="fas fa-camera"></i>
                <input type="file" class="herramienta-foto-entrega d-none" accept=".jpg,.jpeg,.png">
            </label>
            <div class="herramienta-foto-preview" style="width:36px; height:36px;">
                ${fotoExistente
            ? `<img src="${baseUrl}/${fotoExistente}" 
                           style="width:36px;height:36px;object-fit:cover;border-radius:4px;
                                  border:1px solid #dee2e6;cursor:pointer;"
                           onclick="window.open('${baseUrl}/${fotoExistente}','_blank')">`
            : ''}
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-danger btn-eliminar-herramienta-entrega">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;

    // Toggle label al cambiar switch
    const cb = div.querySelector('.herramienta-existe-entrega');
    const lbl = div.querySelector('.herramienta-check-label');
    cb.addEventListener('change', function () {
        lbl.textContent = this.checked ? '✅' : '❌';
    });

    // Preview de foto al seleccionar
    const inputFoto = div.querySelector('.herramienta-foto-entrega');
    const preview = div.querySelector('.herramienta-foto-preview');
    inputFoto.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const url = URL.createObjectURL(this.files[0]);
            preview.innerHTML = `<img src="${url}" 
                style="width:36px;height:36px;object-fit:cover;border-radius:4px;
                       border:1px solid #dee2e6;cursor:pointer;"
                onclick="window.open('${url}','_blank')">`;
        } else {
            preview.innerHTML = '';
        }
    });

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
    const filas = document.querySelectorAll('#listaHerramientasEntrega .herramienta-entrega-row');
    const herramientas = [];
    const formData = window._entregaFormData || null; // se usa abajo en el click

    filas.forEach((fila, idx) => {
        const nombreEl = fila.querySelector('.herramienta-nombre-entrega');
        const checkEl = fila.querySelector('.herramienta-existe-entrega');
        if (!nombreEl || !checkEl) return;
        const nombre = nombreEl.value.trim();
        const existe = checkEl.checked ? 'si' : 'no';
        if (nombre !== '') {
            herramientas.push({ nombre, existe, foto_key: `ent_herramienta_foto_${idx}` });
        }
    });

    document.getElementById('ent_equipo_carretera').value = JSON.stringify(herramientas);
    return herramientas;
}

// Al seleccionar vehículo cargar su equipo automáticamente
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
    const fechaEntrega = $('[name="ent_fechaentrega"]').val();
    const fechaRegistro = $('[name="ent_fecharegist"]').val();
    const sedeEl = document.getElementById('ent_sede');
    const sede = sedeEl ? (sedeEl.value || '').trim() : '';

    if (!tipoEntrega.value) { Swal.fire('Error', 'Debe seleccionar el tipo de entrega', 'error'); return; }
    if (!vehiculoSelect.value) { Swal.fire('Error', 'Debe seleccionar un vehículo', 'error'); return; }

    if (tipoEntrega.value === 'final') {
        // Entrega final: conductor entrega el vehículo a la empresa = conductor obligatorio
        const operadorSelect = document.getElementById('ent_idusuario');
        if (!operadorSelect.value) {
            Swal.fire('Error', 'Debe seleccionar el conductor que entrega', 'error');
            return;
        }
    } else if (tipoEntrega.value === 'inicial') {
        // Entrega inicial: empresa entrega al conductor = quien recibe es obligatorio
        const conductorRecibe = document.getElementById('ent_idusuario_recibe');
        if (!conductorRecibe.value) {
            Swal.fire('Error', 'Debe seleccionar el conductor que recibe el vehículo', 'error');
            return;
        }
    }

    if (!fechaEntrega) { Swal.fire('Error', 'Debe ingresar la fecha de entrega', 'error'); return; }
    if (!fechaRegistro) { Swal.fire('Error', 'Debe ingresar la fecha de registro', 'error'); return; }
    if (!sede) { Swal.fire('Error', 'Debe ingresar la sede de entrega', 'error'); return; }

    const validarImagen = (input, nombreCampo) => {
        if (input && input.files.length > 0) {
            const archivo = input.files[0];
            const extensiones = /(\.jpg|\.jpeg|\.png|\.pdf)$/i;
            if (!extensiones.exec(archivo.name)) {
                Swal.fire("Error", `El archivo en ${nombreCampo} debe ser JPG, PNG o PDF`, "error");
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
    const inputTrasera = $('input[name="ent_img_respaldo"]')[0];

    if (!validarImagen(inputFrente, "Foto Frente")) return;
    if (!validarImagen(inputTrasera, "Foto Respaldo")) return;

    const opcionVehiculo = vehiculoSelect.options[vehiculoSelect.selectedIndex];
    const textoVehiculo = opcionVehiculo.getAttribute('data-texto');

    const datos = new FormData();
    datos.append('ent_vehiculo_id', vehiculoSelect.value);
    datos.append('ent_tipoentrega', tipoEntrega.value);
    datos.append('guardar_entrega', true);
    datos.append('ent_vehiculo', textoVehiculo);
    datos.append('ent_fechaentrega', fechaEntrega);
    datos.append('ent_fecharegist', fechaRegistro);
    datos.append('ent_sede', sede);
    datos.append('ent_observaciones', $('[name="ent_observaciones"]').val());

    if (tipoEntrega.value === 'inicial') {
        // Quien recibe es el conductor seleccionado
        const conductorRecibe = document.getElementById('ent_idusuario_recibe');
        datos.append('ent_idusuario', conductorRecibe.value);
    } else {
        // Quien entrega es el conductor seleccionado
        const operadorSelect = document.getElementById('ent_idusuario');
        datos.append('ent_idusuario', operadorSelect.value);
    }

    if (inputFrente && inputFrente.files.length > 0) datos.append('ent_img_frente', inputFrente.files[0]);
    if (inputTrasera && inputTrasera.files.length > 0) datos.append('ent_img_respaldo', inputTrasera.files[0]);

    serializarHerramientasEntrega();
    datos.append('ent_equipo_carretera', document.getElementById('ent_equipo_carretera').value);

    // Adjuntar fotos individuales de herramientas
    const filasHerramienta = document.querySelectorAll('#listaHerramientasEntrega .herramienta-entrega-row');
    filasHerramienta.forEach((fila, idx) => {
        const inputFoto = fila.querySelector('.herramienta-foto-entrega');
        if (inputFoto && inputFoto.files && inputFoto.files[0]) {
            datos.append(`ent_herramienta_foto_${idx}`, inputFoto.files[0]);
        }
    });

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
    // Limpiar herramientas al cerrar
    document.getElementById('listaHerramientasEntrega').innerHTML = '';
    document.getElementById('ent_equipo_carretera').value = '';
    // Limpiar selects
    document.getElementById('ent_tipoentrega').value = '';
    document.getElementById('ent_vehiculo_id').value = '';

    if (firmaEntregaCtx && firmaEntregaCanvas) {
        firmaEntregaCtx.clearRect(0, 0, firmaEntregaCanvas.width, firmaEntregaCanvas.height);
    }
    firmaEntregaRealizada = false;
    firmaEntregaDrawing = false;
});

// COMPARENDOS - Al seleccionar operador mostrar info de hoja de vida
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

// GUARDAR COMPARENDO
$(document).on('click', '#btnGuardarComparendo', function () {

    const operador = $('#com_operador_id').val();
    const vehiculo = $('#com_vehiculo_id').val();
    const estado = $('#com_estado').val();
    const fecha = $('#com_fecha').val();
    const valor = $('#com_valor').val().trim();
    const numero = $('#com_numerocompa').val().trim();
    const titular = $('#com_titularcompa').val();

    if (!operador) { Swal.fire('Error', 'Debe seleccionar un operador', 'error'); return; }
    if (!vehiculo) { Swal.fire('Error', 'Debe seleccionar un vehículo', 'error'); return; }
    if (!estado) { Swal.fire('Error', 'Debe seleccionar el estado del comparendo', 'error'); return; }
    if (!fecha) { Swal.fire('Error', 'Debe ingresar la fecha del comparendo', 'error'); return; }
    if (!valor) { Swal.fire('Error', 'Debe ingresar el valor del comparendo', 'error'); return; }
    if (!numero) { Swal.fire('Error', 'Debe ingresar el número del comparendo', 'error'); return; }
    if (!titular) { Swal.fire('Error', 'Debe seleccionar el titular del comparendo', 'error'); return; }

    const inputFoto = document.getElementById('com_foto');
    if (inputFoto && inputFoto.files.length > 0) {
        const archivo = inputFoto.files[0];
        if (!/(\.jpg|\.jpeg|\.png|\.pdf)$/i.test(archivo.name)) {
            Swal.fire('Error', 'El archivo debe ser JPG, PNG o PDF', 'error'); return;
        }
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'La foto es muy pesada (máx 5MB)', 'error'); return;
        }
    }

    const inputFotoCurso = document.getElementById('com_foto_curso');
    if (inputFotoCurso && inputFotoCurso.files.length > 0) {
        const archivo = inputFotoCurso.files[0];
        if (!/(\.jpg|\.jpeg|\.png|\.pdf)$/i.test(archivo.name)) {
            Swal.fire('Error', 'El archivo debe ser JPG, PNG o PDF', 'error'); return;
        }
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'La foto del curso es muy pesada (máx 5MB)', 'error'); return;
        }
    }

    const datos = new FormData(document.getElementById('formComparendo'));
    const valorRaw = $('#com_valor').val().trim().replace(/\./g, '').replace(',', '.');
    datos.set('com_valor', valorRaw);
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

// VER COMPARENDOS - Al hacer clic en el botón de un vehículo, mostrar modal con tabla de comparendos asociados
$(document).on('click', '.btn-ver-comparendos', function () {
    const idVehiculo = $(this).data('id');
    const placa = $(this).data('placa');

    window._idVehiculoComparendos = idVehiculo;

    $('#tituloPlacaComparendo').text(placa);
    $('#cuerpoTablaComparendos').html('<tr><td colspan="10" class="text-muted">Cargando...</td></tr>');
    $('#modalVerComparendos').modal('show');

    $.ajax({
        url: urlController,
        type: 'POST',
        data: { obtener_comparendos: true, id: idVehiculo },
        success: function (res) {
            recargarTablaComparendos(res);
        },
        error: function () {
            $('#cuerpoTablaComparendos').html('<tr><td colspan="10" class="text-danger">Error de conexión</td></tr>');
        }
    });
});

//FUNCIÓN REUTILIZABLE PARA PINTAR LA TABLA DE COMPARENDOS
function recargarTablaComparendos(res) {
    try {
        const lista = typeof res === 'object' ? res : JSON.parse(res);
        const $tbody = $('#cuerpoTablaComparendos');
        $tbody.empty();

        if (!lista || lista.length === 0) {
            $tbody.html('<tr><td colspan="10" class="text-muted">Sin comparendos registrados</td></tr>');
            return;
        }

        lista.forEach((c, i) => {
            const estadoBadge = c.com_estado === 'Pagado'
                ? `<span class="badge bg-success btn-editar-comparendo"
            style="cursor:pointer;"
            data-id="${c.idcomparendos}"
            data-estado="${c.com_estado}"
            data-valor="${c.com_valor}"
            data-numero="${c.com_numerocompa ?? ''}"
            data-titular="${c.com_titularcompa ?? ''}"
            data-foto="${c.com_foto ?? ''}"
            data-fotocurso="${c.com_foto_curso ?? ''}"
            title="Clic para editar comparendo">
            Pagado &nbsp;<i class="fas fa-pen" style="font-size:10px;opacity:0.85;"></i>
        </span>`
                : `<span class="badge bg-warning text-dark btn-editar-comparendo"
            style="cursor:pointer;"
            data-id="${c.idcomparendos}"
            data-estado="${c.com_estado}"
            data-valor="${c.com_valor}"
            data-numero="${c.com_numerocompa ?? ''}"
            data-titular="${c.com_titularcompa ?? ''}"
            data-foto="${c.com_foto ?? ''}"
            data-fotocurso="${c.com_foto_curso ?? ''}"
            title="Clic para editar comparendo">
            Pendiente &nbsp;<i class="fas fa-pen" style="font-size:10px;opacity:0.85;"></i>
        </span>`;

            const foto = c.com_foto
                ? c.com_foto.toLowerCase().endsWith('.pdf')
                    ? `<a href="${baseUrl}/${c.com_foto}" target="_blank" class="btn btn-sm btn-outline-danger">
               <i class="fas fa-file-pdf"></i> Ver PDF
           </a>`
                    : `<img src="${baseUrl}/${c.com_foto}"
                style="height:48px;width:72px;object-fit:cover;
                       border-radius:4px;border:1px solid #dee2e6;cursor:pointer;"
                onclick="window.open('${baseUrl}/${c.com_foto}','_blank')"
                title="Ver foto completa">`
                : '<span class="text-muted" style="font-size:11px;">Sin foto</span>';

            const fotoCurso = c.com_foto_curso
                ? c.com_foto_curso.toLowerCase().endsWith('.pdf')
                    ? `<a href="${baseUrl}/${c.com_foto_curso}" target="_blank" class="btn btn-sm btn-outline-danger">
               <i class="fas fa-file-pdf"></i> Ver PDF
           </a>`
                    : `<img src="${baseUrl}/${c.com_foto_curso}"
                style="height:48px;width:72px;object-fit:cover;
                       border-radius:4px;border:1px solid #dee2e6;cursor:pointer;"
                onclick="window.open('${baseUrl}/${c.com_foto_curso}','_blank')"
                title="Ver foto curso">`
                : '<span class="text-muted" style="font-size:11px;">Sin foto</span>';

            $tbody.append(`
                <tr>
                    <td>${i + 1}</td>
                    <td>${c.operador_nombre ?? '—'}</td>
                    <td>${c.vehiculo_placa} — ${c.vehiculo_marca} ${c.vehiculo_modelo}</td>
                    <td>${estadoBadge}</td>
                    <td>${c.com_fecha}</td>
                    <td>${c.com_valor ? '$' + Number(c.com_valor).toLocaleString('es-CO') : '—'}</td>
                    <td>${c.com_numerocompa ?? '—'}</td>
                    <td>${c.com_titularcompa ?? '—'}</td>
                    <td>${foto}</td>
                    <td>${fotoCurso}</td>
                </tr>
            `);
        });

    } catch (e) {
        $('#cuerpoTablaComparendos').html('<tr><td colspan="10" class="text-danger">Error al cargar los datos</td></tr>');
    }
}

// ABRIR MODAL EDITAR AL HACER CLIC EN EL BADGE DE ESTADO
$(document).on('click', '.btn-editar-comparendo', function () {
    const id = $(this).data('id');
    const estado = $(this).data('estado');
    const valor = $(this).data('valor');
    const numero = $(this).data('numero');
    const titular = $(this).data('titular');
    const foto = $(this).data('foto');
    const fotoCurso = $(this).data('fotocurso');

    $('#edit_com_id').val(id);
    $('#edit_com_estado').val(estado);
    $('#edit_com_valor').val(valor ? Number(valor).toLocaleString('es-CO') : '');
    $('#edit_com_numerocompa').val(numero);
    $('#edit_com_titularcompa').val(titular);


    // Preview foto comparendo
    const $prevFoto = $('#preview_edit_com_foto');
    if (foto) {
        const url = `${baseUrl}/` + foto;
        const esPdf = foto.toLowerCase().endsWith('.pdf');
        $prevFoto.html(esPdf
            ? `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-danger mb-1">
               <i class="fas fa-file-pdf"></i> Ver PDF actual
           </a><small class="text-muted d-block">Sube uno nuevo para reemplazar</small>`
            : `<a href="${url}" target="_blank">
               <img src="${url}" style="max-height:60px;border-radius:4px;border:1px solid #dee2e6;margin-bottom:4px;">
           </a>
           <small class="text-muted d-block">
               <a href="${url}" target="_blank">🔍 Ver imagen actual</a> — Sube una nueva para reemplazar
           </small>`
        );
    } else {
        $prevFoto.html('<small class="text-muted">Sin foto actual</small>');
    }

    // Preview foto curso
    const $prevCurso = $('#preview_edit_com_foto_curso');
    if (fotoCurso) {
        const url2 = `${baseUrl}/` + fotoCurso;
        const esPdf2 = fotoCurso.toLowerCase().endsWith('.pdf');
        $prevCurso.html(esPdf2
            ? `<a href="${url2}" target="_blank" class="btn btn-sm btn-outline-danger mb-1">
               <i class="fas fa-file-pdf"></i> Ver PDF actual
           </a><small class="text-muted d-block">Sube uno nuevo para reemplazar</small>`
            : `<a href="${url2}" target="_blank">
               <img src="${url2}" style="max-height:60px;border-radius:4px;border:1px solid #dee2e6;margin-bottom:4px;">
           </a>
           <small class="text-muted d-block">
               <a href="${url2}" target="_blank">🔍 Ver imagen actual</a> — Sube una nueva para reemplazar
           </small>`
        );
    } else {
        $prevCurso.html('<small class="text-muted">Sin foto de curso actual</small>');
    }

    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarComparendo'));
    modalEditar.show();
});

// GUARDAR CAMBIOS DEL COMPARENDO 
$(document).on('click', '#btnGuardarEditarComparendo', function () {
    const estado = $('#edit_com_estado').val();
    const valor = $('#edit_com_valor').val().trim();
    const valorLimpio = valor.replace(/\./g, '').replace(',', '.');
    const numero = $('#edit_com_numerocompa').val().trim();
    const titular = $('#edit_com_titularcompa').val().trim();

    if (!estado) { Swal.fire('Error', 'Debe seleccionar un estado', 'error'); return; }
    if (!valor) { Swal.fire('Error', 'Debe ingresar el valor del comparendo', 'error'); return; }
    if (!numero) { Swal.fire('Error', 'Debe ingresar el número del comparendo', 'error'); return; }
    if (!titular) { Swal.fire('Error', 'Debe ingresar el titular del comparendo', 'error'); return; }

    const validarImg = (inputEl, nombre) => {
        if (inputEl && inputEl.files.length > 0) {
            const f = inputEl.files[0];
            if (!/(\.jpg|\.jpeg|\.png|\.pdf)$/i.test(f.name)) {
                Swal.fire('Error', 'El archivo debe ser JPG, PNG o PDF', 'error'); return;
            }
            if (f.size > 5 * 1024 * 1024) {
                Swal.fire('Error', `La foto de ${nombre} es muy pesada (máx 5MB)`, 'error');
                return false;
            }
        }
        return true;
    };

    if (!validarImg(document.getElementById('edit_com_foto'), 'Comparendo')) return;
    if (!validarImg(document.getElementById('edit_com_foto_curso'), 'Curso')) return;

    const datos = new FormData(document.getElementById('formEditarComparendo'));
    datos.set('com_valor', valorLimpio);
    datos.append('actualizar_comparendo', true);

    Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

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
                    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: r.mensaje, timer: 2000, showConfirmButton: false });

                    // Cerrar modal edición
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarComparendo')).hide();

                    // Recargar filas del modal de ver comparendos
                    if (window._idVehiculoComparendos) {
                        $.ajax({
                            url: urlController,
                            type: 'POST',
                            data: { obtener_comparendos: true, id: window._idVehiculoComparendos },
                            success: function (res2) { recargarTablaComparendos(res2); }
                        });
                    }

                    // Refrescar badge en tabla principal
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

// MODAL INFO INCOMPLETA
$(document).on('click', '.btn-info-incompleta', function () {
    const placa = $(this).data('placa');
    const campos = $(this).data('campos');

    $('#placaInfoIncompleta').text(placa);

    const $lista = $('#listaCamposFaltantes');
    $lista.empty();

    campos.forEach(function (campo) {
        $lista.append(`
            <li class="list-group-item py-2 d-flex align-items-center gap-2">
                <i class="fas fa-times-circle text-danger" style="font-size:13px;"></i>
                <span style="font-size:13px;">${campo}</span>
            </li>
        `);
    });

    new bootstrap.Modal(document.getElementById('modalInfoIncompleta')).show();
});

// HISTORIAL DE CONDUCTORES 

let _historialVehiculoId = null;
let _historialVehiculoPlaca = null;

// Abrir modal — carga inmediata sin filtros
$(document).on('click', '.btn-ver-historial', function () {
    _historialVehiculoId = $(this).data('id');
    _historialVehiculoPlaca = $(this).data('placa');

    $('#tituloPlacaHistorial').text(_historialVehiculoPlaca);
    $('#historial_total_registros').text('');
    $('#cuerpoTablaHistorial').html(
        '<tr><td colspan="13" class="text-muted">Cargando...</td></tr>'
    );
    $('#modalHistorialConductores').modal('show');

    cargarHistorial(_historialVehiculoId);
});

// AJAX al controller
function cargarHistorial(idVehiculo) {
    $.ajax({
        url: urlController,
        type: 'POST',
        data: { obtener_historial_conductores: true, id: idVehiculo },
        success: function (res) { renderizarHistorial(res); },
        error: function () {
            $('#cuerpoTablaHistorial').html(
                '<tr><td colspan="13" class="text-danger">Error de conexión</td></tr>'
            );
        }
    });
}

// Construir filas de la tabla
function renderizarHistorial(res) {
    const $tbody = $('#cuerpoTablaHistorial');
    $tbody.empty();

    let lista;
    try {
        lista = typeof res === 'object' ? res : JSON.parse(res);
    } catch (e) {
        $tbody.html('<tr><td colspan="13" class="text-danger">Error al procesar los datos</td></tr>');
        return;
    }

    if (!lista || lista.length === 0) {
        $('#historial_total_registros').text('');
        $tbody.html(
            `<tr><td colspan="13" class="text-muted py-3">
                <i class="fas fa-inbox me-1"></i> Sin entregas registradas para este vehículo.
             </td></tr>`
        );
        return;
    }

    $('#historial_total_registros').html(
        `<i class="fas fa-list me-1"></i> ${lista.length} registro(s)`
    );

    lista.forEach(function (e, i) {

        //Badge tipo entrega
        const tipoBadge = e.ent_tipoentrega === 'inicial'
            ? `<span class="badge bg-primary px-2 py-1">
                   <i class="fas fa-arrow-right me-1"></i>Inicial
               </span>`
            : `<span class="badge bg-warning text-dark px-2 py-1">
                   <i class="fas fa-arrow-left me-1"></i>Final
               </span>`;

        //Conductor
        const nombreConductor = e.conductor_nombre || '—';
        const conductorLabel = `<span class="fw-semibold">${nombreConductor}</span>`;

        //Recibido por
        const recibidoPor = `<span style="font-size:12px;">${e.ent_userregistra || '—'}</span>`;

        //Fecha Inicio (ent_fechaentrega — cuando el conductor recibe el vehículo)
        const fechaInicio = e.ent_tipoentrega === 'inicial'
            ? `<span class="fw-semibold text-primary">${e.ent_fechaentrega || '—'}</span>`
            : '<span class="text-muted">—</span>';

        //Fecha Final (ent_fecharegista — cuando el conductor devuelve el vehículo)
        const fechaFinal = e.ent_tipoentrega === 'final'
            ? `<span class="fw-semibold text-warning">${e.ent_fecharegista || '—'}</span>`
            : '<span class="text-muted">—</span>';
        //Checklist equipo
        let equipoHtml = '<span class="text-muted" style="font-size:11px;">Sin equipo</span>';
        if (e.ent_equipo_carretera) {
            try {
                const equipo = JSON.parse(e.ent_equipo_carretera);
                if (equipo && equipo.length > 0) {
                    equipoHtml = `
                        <div style="text-align:left; min-width:160px;">
                            <div class="px-2 py-1 mb-1 rounded" style="background-color:#1a3a5c;">
                                <small class="text-white fw-bold">
                                    <i class="fas fa-toolbox me-1"></i> Equipo
                                </small>
                            </div>
                            <ul class="list-unstyled mb-0 ps-1">
                                ${equipo.map(h => `
    <li class="d-flex align-items-center gap-2 py-1 border-bottom"
        style="font-size:12px;">
        <span>${h.existe === 'si' ? '✅' : '❌'}</span>
        <span class="${h.existe !== 'si' ? 'text-muted text-decoration-line-through' : ''}" style="flex:1;">
            ${h.nombre}
        </span>
        ${h.foto
                            ? `<a href="${baseUrl}/${h.foto}" target="_blank">
                <img src="${baseUrl}/${h.foto}"
                     style="width:28px;height:28px;object-fit:cover;border-radius:3px;
                            border:1px solid #dee2e6;" title="Ver foto de ${h.nombre}">
               </a>`
                            : ''}
    </li>`).join('')}
                            </ul>
                        </div>`;
                }
            } catch (_) { }
        }

        //Foto frente
        const fotoFrente = e.ent_img_frente
            ? `<img src="${baseUrl}/${e.ent_img_frente}"
                   style="height:48px;width:72px;object-fit:cover;border-radius:4px;
                          border:1px solid #dee2e6;cursor:pointer;"
                   onclick="window.open('${baseUrl}/${e.ent_img_frente}','_blank')"
                   title="Ver foto frente">`
            : '<span class="text-muted" style="font-size:11px;">Sin foto</span>';

        //Foto respaldo
        const fotoRespaldo = e.ent_img_trasera
            ? `<img src="${baseUrl}/${e.ent_img_trasera}"
                   style="height:48px;width:72px;object-fit:cover;border-radius:4px;
                          border:1px solid #dee2e6;cursor:pointer;"
                   onclick="window.open('${baseUrl}/${e.ent_img_trasera}','_blank')"
                   title="Ver foto respaldo">`
            : '<span class="text-muted" style="font-size:11px;">Sin foto</span>';

        //Firma
        const firma = e.ent_firma
            ? `<img src="${baseUrl}/${e.ent_firma}"
                   style="height:40px;max-width:90px;object-fit:contain;border-radius:4px;
                          border:1px solid #dee2e6;cursor:pointer;background:#fff;"
                   onclick="window.open('${baseUrl}/${e.ent_firma}','_blank')"
                   title="Ver firma">`
            : '<span class="text-muted" style="font-size:11px;">Sin firma</span>';

        //Observaciones
        const observaciones = e.ent_observaciones
            ? `<span style="font-size:12px;text-align:left;display:block;">
                   ${e.ent_observaciones}
               </span>`
            : '<span class="text-muted">—</span>';

        // Sede   
        const sede = e.ent_sede
            ? `<span style="font-size:12px;">${e.ent_sede}</span>`
            : '<span class="text-muted">—</span>';

        $tbody.append(`
            <tr>
                <td class="fw-bold text-muted">${i + 1}</td>
                <td>${tipoBadge}</td>
                <td>${conductorLabel}</td>
                <td>${recibidoPor}</td>
                <td>${fechaInicio}</td>
                <td>${fechaFinal}</td>
                <td>${sede}</td>
                <td>${equipoHtml}</td>
                <td>${fotoFrente}</td>
                <td>${fotoRespaldo}</td>
                <td>${firma}</td>
                <td style="max-width:160px;">${observaciones}</td>
                <td>
    <button class="btn btn-sm btn-outline-primary btn-editar-entrega"
        title="Editar entrega"
        data-id="${e.identregavehiculo}"
        data-tipoentrega="${e.ent_tipoentrega}"
        data-fechaentrega="${e.ent_fechaentrega}"
        data-fecharegist="${e.ent_fecharegistra ?? ''}"
        data-sede="${e.ent_sede ?? ''}"
        data-observaciones="${(e.ent_observaciones ?? '').replace(/"/g, '&quot;')}"
        data-conductor="${e.conductor_nombre ?? ''}"
        data-userregistra="${e.ent_userregistra ?? ''}"
        data-imgfrente="${e.ent_img_frente ?? ''}"
        data-imgrespaldo="${e.ent_img_trasera ?? ''}"
        data-equipo="${(e.ent_equipo_carretera ?? '').replace(/"/g, '&quot;')}">
        <i class="fas fa-edit"></i>
    </button>
</td>
<td>
    <button class="btn btn-sm btn-danger btn-eliminar-entrega"
        title="Eliminar entrega"
        data-id="${e.identregavehiculo}">
        <i class="fas fa-trash-alt"></i>
    </button>
</td>
            </tr>
        `);
    });
}

//Modal editar cambio de aceite
$(document).on('click', '#tablaVehiculos tbody .btn-editar-aceite', function () {
    document.getElementById('aceite_veh_id').value = $(this).data('id');
    document.getElementById('aceite_fecha').value = $(this).data('fecha');
    document.getElementById('aceite_kilactual').value = $(this).data('kilactual');
    document.getElementById('aceite_kmcambio').value = $(this).data('kmcambio');
    document.getElementById('aceite_limite').value = $(this).data('limite');
    new bootstrap.Modal(document.getElementById('modalEditarAceite')).show();
});

// Guardar cambio de aceite
$(document).on('click', '#btnGuardarAceite', function () {
    const id = $('#aceite_veh_id').val();
    const fecha = $('#aceite_fecha').val();
    const kilactual = $('#aceite_kilactual').val().trim();
    const kmcambio = $('#aceite_kmcambio').val().trim();
    const limite = $('#aceite_limite').val().trim();

    if (!fecha) { Swal.fire('Error', 'Ingrese la fecha de cambio de aceite', 'error'); return; }
    if (!kilactual) { Swal.fire('Error', 'Ingrese el kilometraje actual', 'error'); return; }
    if (!kmcambio) { Swal.fire('Error', 'Ingrese el km actual al cambio de aceite', 'error'); return; }
    if (!limite) { Swal.fire('Error', 'Ingrese el límite de km', 'error'); return; }

    Swal.fire({
        title: 'Guardando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: urlController,
        type: 'POST',
        data: {
            actualizar_aceite: true,
            id: id,
            veh_kilactual: kilactual,
            veh_fechamantenimiento: fecha,
            veh_kmactual_cambioaceite: kmcambio,
            veh_calkmcambioaceite: limite
        },
        success: function (res) {
            Swal.close();
            try {
                const r = typeof res === 'object' ? res : JSON.parse(res);
                if (r.success) {
                    Swal.fire({
                        icon: 'success', title: '¡Actualizado!',
                        text: r.mensaje, timer: 2000, showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(
                        document.getElementById('modalEditarAceite')).hide();
                    $('#tablaVehiculos').DataTable().ajax.reload(null, false);
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

//Modal editar SOAT
$(document).on('click', '#tablaVehiculos tbody .btn-editar-soat', function () {
    document.getElementById('soat_veh_id').value = $(this).data('id');
    document.getElementById('soat_veh_fechaseguro').value = $(this).data('fecha');

    // Preview imagen actual
    const imgSoat = $(this).data('imgsoat');
    const $preview = $('#preview_soat_modal');
    if (imgSoat) {
        const url = `${baseUrl}/` + imgSoat;
        const esPdf = imgSoat.toLowerCase().endsWith('.pdf');
        $preview.html(esPdf
            ? `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-danger mb-1">
               <i class="fas fa-file-pdf"></i> Ver PDF
           </a>
           <small class="text-muted d-block">Sube uno nuevo para reemplazar</small>`
            : `<a href="${url}" target="_blank">
               <img src="${url}" style="max-height:60px; border-radius:4px; 
               border:1px solid #dee2e6; margin-bottom:4px;">
           </a>
           <small class="text-muted d-block">
               <a href="${url}" target="_blank">🔍 Ver imagen actual</a> — Sube una nueva para reemplazar
           </small>`
        );
    } else {
        $preview.html('<small class="text-muted">Sin imagen</small>');
    }

    new bootstrap.Modal(document.getElementById('modalEditarSoat')).show();
});

// Guardar SOAT
$(document).on('click', '#btnGuardarSoat', function () {
    const id = $('#soat_veh_id').val();
    const fecha = $('#soat_veh_fechaseguro').val();

    if (!fecha) { Swal.fire('Error', 'Ingrese la fecha del SOAT', 'error'); return; }

    const inputFoto = $('#formEditarSoat input[name="veh_img_soat"]')[0];
    if (inputFoto && inputFoto.files.length > 0) {
        const archivo = inputFoto.files[0];
        if (!/(\.jpg|\.jpeg|\.png|\.pdf)$/i.test(archivo.name)) {
            Swal.fire('Error', 'El archivo debe ser JPG, PNG o PDF', 'error'); return;
        }
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'La foto es muy pesada (máx 5MB)', 'error'); return;
        }
    }

    const datos = new FormData();
    datos.append('actualizar_soat', true);
    datos.append('id', id);
    datos.append('veh_fechaseguro', fecha);
    if (inputFoto && inputFoto.files.length > 0) {
        datos.append('veh_img_soat', inputFoto.files[0]);
    }

    Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

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
                    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: r.mensaje, timer: 2000, showConfirmButton: false });
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarSoat')).hide();
                    $('#tablaVehiculos').DataTable().ajax.reload(null, false);
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

// Modal editar Tecnomecánica
$(document).on('click', '#tablaVehiculos tbody .btn-editar-tecnomecanica', function () {
    document.getElementById('tecnomecanica_veh_id').value = $(this).data('id');
    document.getElementById('tecnomecanica_veh_fechatecnomecanica').value = $(this).data('fecha');

    const imgTecno = $(this).data('imgtecno');
    const $preview = $('#preview_tecnomecanica_modal');

    if (imgTecno) {
        const url = `${baseUrl}/` + imgTecno;
        const esPdf = imgTecno.toLowerCase().endsWith('.pdf');
        $preview.html(esPdf
            ? `<a href="${url}" target="_blank" class="btn btn-sm btn-outline-danger mb-1">
               <i class="fas fa-file-pdf"></i> Ver PDF
           </a>
           <small class="text-muted d-block"></small>`
            : `<a href="${url}" target="_blank">
               <img src="${url}" style="max-height:60px; border-radius:4px; 
               border:1px solid #dee2e6; margin-bottom:4px;">
           </a>
           <small class="text-muted d-block">
               <a href="${url}" target="_blank">🔍 Ver imagen actual</a> — Sube una nueva para reemplazar
           </small>`
        );
    } else {
        $preview.html('<small class="text-muted">Sin imagen</small>');
    }

    new bootstrap.Modal(document.getElementById('modalEditarTecnomecanica')).show();
});

// Guardar Tecnomecánica
$(document).on('click', '#btnGuardarTecnomecanica', function () {
    const id = $('#tecnomecanica_veh_id').val();
    const fecha = $('#tecnomecanica_veh_fechatecnomecanica').val();

    if (!fecha) { Swal.fire('Error', 'Ingrese la fecha de la Tecnomecánica', 'error'); return; }

    const inputFoto = $('#formEditarTecnomecanica input[name="veh_img_tecnomecanica"]')[0];
    if (inputFoto && inputFoto.files.length > 0) {
        const archivo = inputFoto.files[0];
        if (!/(\.jpg|\.jpeg|\.png|\.pdf)$/i.test(archivo.name)) {
            Swal.fire('Error', 'El archivo debe ser JPG, PNG o PDF', 'error'); return;
        }
        if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'La foto es muy pesada (máx 5MB)', 'error'); return;
        }
    }

    const datos = new FormData();
    datos.append('actualizar_tecnomecanica', true);
    datos.append('id', id);
    datos.append('veh_fechategnomecanica', fecha);
    if (inputFoto && inputFoto.files.length > 0) {
        datos.append('veh_img_tecnomecanica', inputFoto.files[0]);
    }

    Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

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
                    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: r.mensaje, timer: 2000, showConfirmButton: false });
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarTecnomecanica')).hide();
                    $('#tablaVehiculos').DataTable().ajax.reload(null, false);
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

// Recargar selects de vehículos sin recargar la página
function recargarSelectsVehiculos() {
    $.ajax({
        url: urlController,
        type: 'POST',
        data: { obtener_vehiculos_select: true },
        success: function (res) {
            try {
                const lista = typeof res === 'object' ? res : JSON.parse(res);

                // Selects que usan vehículos
                const selectIds = ['#ent_vehiculo_id', '#com_vehiculo_id'];

                selectIds.forEach(function (selectId) {
                    const $select = $(selectId);
                    if (!$select.length) return;

                    const valorActual = $select.val();
                    $select.find('option:not(:first)').remove();

                    lista.forEach(function (v) {
                        // com_vehiculo_id solo muestra empresa + activos
                        if (selectId === '#com_vehiculo_id') {
                            if (v.veh_estado != 1) return;
                            if (v.veh_propiedad !== 'empresa') return;
                        }

                        const texto = `${v.veh_placa} — ${v.veh_marca} ${v.veh_modelo}`;
                        const dataTexto = `${v.veh_tipo} ${v.veh_placa} ${v.veh_marca} ${v.veh_modelo}`;

                        $select.append(
                            $('<option>', {
                                value: v.idvehiculos,
                                text: texto,
                                'data-texto': dataTexto
                            })
                        );
                    });

                    // Restaurar selección previa si aún existe
                    if (valorActual) $select.val(valorActual);
                });

            } catch (e) {
                console.warn('No se pudieron recargar los selects de vehículos', e);
            }
        }
    });
}

let _editEntregaData = null;
let _editEntregaTipo = '';

document.getElementById('modalEditarEntrega').addEventListener('shown.bs.modal', function () {
    if (!_editEntregaData) return;
    const d = _editEntregaData;
    _editEntregaData = null;

    $('#edit_ent_tipoentrega').val(d.tipoentrega);
    $('#edit_ent_fechaentrega').val(d.fechaentrega);
    $('#edit_ent_fecharegistra').val(d.fecharegistra);

    if (d.tipoentrega === 'final') {
        $('#edit_label_fecha').text('📅 Fecha Final');
    } else {
        $('#edit_label_fecha').text('📅 Fecha Inicio');
    }
});

$(document).on('click', '.btn-editar-entrega', function () {
    const id = $(this).data('id');
    const tipoentrega = $(this).data('tipoentrega');
    const fechaentrega = $(this).data('fechaentrega');
    const fecharegistra = $(this).data('fecharegistra');
    const sede = $(this).data('sede');
    const observaciones = $(this).data('observaciones');
    const conductor = $(this).data('conductor');
    const userregistra = $(this).data('userregistra');
    const imgfrente = $(this).data('imgfrente');
    const imgrespaldo = $(this).data('imgrespaldo');

    // Leer equipo con .attr() para evitar caché de jQuery
    const equipoRaw = $(this).attr('data-equipo') || '';
    let equipo = [];
    try { equipo = JSON.parse(equipoRaw); } catch (e) { equipo = []; }

    _editEntregaData = { tipoentrega, fechaentrega, fecharegistra };
    _editEntregaTipo = tipoentrega;

    // Campos básicos
    $('#edit_ent_id').val(id);
    $('#edit_ent_sede').val(sede);
    $('#edit_ent_observaciones').val(observaciones);

    // Labels y valores conductor/recibido según tipo
    if (tipoentrega === 'inicial') {
        $('#edit_label_conductor').text('Conductor (recibe)');
        $('#edit_label_recibido_entregado').text('Entregado por');
    } else {
        $('#edit_label_conductor').text('Conductor (entrega)');
        $('#edit_label_recibido_entregado').text('Recibido por');
    }
    $('#edit_ent_conductor').val(conductor || '—');
    $('#edit_ent_userregistra').val(userregistra || '—');

    // Preview fotos
    $('#preview_edit_ent_frente').html(imgfrente
        ? `<a href="${baseUrl}/${imgfrente}" target="_blank">
               <img src="${baseUrl}/${imgfrente}"
                    style="max-height:60px;border-radius:4px;border:1px solid #dee2e6;">
           </a>
           <small class="text-muted d-block">Sube una nueva para reemplazar</small>`
        : '<small class="text-muted">Sin imagen</small>');

    $('#preview_edit_ent_respaldo').html(imgrespaldo
        ? `<a href="${baseUrl}/${imgrespaldo}" target="_blank">
               <img src="${baseUrl}/${imgrespaldo}"
                    style="max-height:60px;border-radius:4px;border:1px solid #dee2e6;">
           </a>
           <small class="text-muted d-block">Sube una nueva para reemplazar</small>`
        : '<small class="text-muted">Sin imagen</small>');

    // Cargar herramientas
    const lista = document.getElementById('listaHerramientasEditEntrega');
    lista.innerHTML = '';
    if (Array.isArray(equipo) && equipo.length > 0) {
        equipo.forEach(h => {
            lista.appendChild(crearFilaHerramientaEditEntrega(
                h.nombre, h.existe, h.foto || ''
            ));
        });
    }

    // Abrir modal y asignar tipo + fechas DESPUÉS de que Bootstrap lo active
    const modalEl = document.getElementById('modalEditarEntrega');
    modalEl._editData = { tipoentrega, fechaentrega };

    modalEl.addEventListener('show.bs.modal', function onShow() {
        modalEl.removeEventListener('show.bs.modal', onShow);
        const d = modalEl._editData;

        $('#edit_ent_tipoentrega').val(d.tipoentrega);

        if (d.tipoentrega === 'final') {
            $('#wrap_edit_fecha_inicio').hide();
            $('#wrap_edit_fecha_final').show();
            $('#edit_ent_fechafinal').val(d.fechaentrega);
            $('#edit_ent_fechaentrega').val(d.fechaentrega);
        } else {
            $('#wrap_edit_fecha_inicio').show();
            $('#wrap_edit_fecha_final').hide();
            $('#edit_ent_fechaentrega').val(d.fechaentrega);
        }
    });

    new bootstrap.Modal(modalEl).show();
});

// Agregar herramienta en modal editar entrega
$(document).on('click', '#btnAgregarHerramientaEditEntrega', function () {
    document.getElementById('listaHerramientasEditEntrega')
        .appendChild(crearFilaHerramientaEditEntrega());
});

// Crear fila de herramienta para modal editar entrega
function crearFilaHerramientaEditEntrega(nombre = '', existe = 'si', fotoExistente = '') {
    const div = document.createElement('div');
    div.className = 'd-flex align-items-center gap-2 mb-2 herramienta-editentrega-row';
    const checked = (existe === 'si') ? 'checked' : '';

    div.innerHTML = `
        <input type="text" class="form-control form-control-sm herramienta-nombre-editentrega"
               placeholder="Nombre de la herramienta" value="${nombre}" style="flex:2">
        <div class="form-check form-switch d-flex align-items-center gap-2 mb-0" 
             style="flex:1; min-width:120px;">
            <input class="form-check-input herramienta-existe-editentrega" type="checkbox"
                   role="switch" style="width:2.5em; height:1.4em; cursor:pointer;" ${checked}>
            <label class="form-check-label herramienta-check-label-editentrega mb-0">
                ${existe === 'si' ? '✅' : '❌'}
            </label>
        </div>
        <div class="d-flex align-items-center gap-1" style="flex:0 0 auto;">
            <label class="btn btn-sm btn-outline-secondary mb-0" 
                   title="Adjuntar foto" style="cursor:pointer;">
                <i class="fas fa-camera"></i>
                <input type="file" class="herramienta-foto-editentrega d-none" 
                       accept=".jpg,.jpeg,.png">
            </label>
            <div class="herramienta-foto-preview-editentrega" style="width:36px;height:36px;">
                ${fotoExistente
            ? `<img src="${baseUrl}/${fotoExistente}"
                           style="width:36px;height:36px;object-fit:cover;border-radius:4px;
                                  border:1px solid #dee2e6;cursor:pointer;"
                           onclick="window.open('${baseUrl}/${fotoExistente}','_blank')">`
            : ''}
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-danger btn-eliminar-herramienta-editentrega">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;

    const cb = div.querySelector('.herramienta-existe-editentrega');
    const lbl = div.querySelector('.herramienta-check-label-editentrega');
    cb.addEventListener('change', function () {
        lbl.textContent = this.checked ? '✅' : '❌';
    });

    const inputFoto = div.querySelector('.herramienta-foto-editentrega');
    const preview = div.querySelector('.herramienta-foto-preview-editentrega');
    inputFoto.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const url = URL.createObjectURL(this.files[0]);
            preview.innerHTML = `<img src="${url}"
                style="width:36px;height:36px;object-fit:cover;border-radius:4px;
                       border:1px solid #dee2e6;cursor:pointer;"
                onclick="window.open('${url}','_blank')">`;
        } else {
            preview.innerHTML = '';
        }
    });

    div.querySelector('.btn-eliminar-herramienta-editentrega').addEventListener('click', function () {
        div.remove();
    });

    return div;
}

// Serializar herramientas del modal editar entrega
function serializarHerramientasEditEntrega() {
    const filas = document.querySelectorAll(
        '#listaHerramientasEditEntrega .herramienta-editentrega-row'
    );
    const herramientas = [];
    filas.forEach((fila) => {
        const nombre = fila.querySelector('.herramienta-nombre-editentrega').value.trim();
        const existe = fila.querySelector('.herramienta-existe-editentrega').checked
            ? 'si' : 'no';
        if (nombre === '') return;

        const obj = { nombre, existe };

        // ✅ Preservar foto existente si no se sube una nueva
        const preview = fila.querySelector('.herramienta-foto-preview-editentrega img');
        const inputFoto = fila.querySelector('.herramienta-foto-editentrega');
        const tieneNuevaFoto = inputFoto && inputFoto.files && inputFoto.files.length > 0;

        if (!tieneNuevaFoto && preview) {
            // Extraer ruta relativa del src (quitar baseUrl)
            const src = preview.getAttribute('src') || '';
            const ruta = src.replace(baseUrl + '/', '');
            if (ruta) obj.foto = ruta;
        }

        herramientas.push(obj);
    });
    document.getElementById('edit_ent_equipo_carretera').value =
        JSON.stringify(herramientas);
}

// GUARDAR edición de entrega
$(document).on('click', '#btnGuardarEditarEntrega', function () {
    const fecha = $('#edit_ent_fechaentrega').val();
    const fecharegistra = $('#edit_ent_fecharegistra').val();
    const sede = $('#edit_ent_sede').val().trim();
    const id = $('#edit_ent_id').val();
    const tipo = $('#edit_ent_tipoentrega').val() || _editEntregaTipo;

    // TEMPORAL — ver qué tiene cada campo
    console.log('=== DATOS EDITAR ENTREGA ===');
    console.log('id:', id);
    console.log('tipo:', tipo);
    console.log('fecha entrega:', fecha);
    console.log('fecha registra:', fecharegistra);
    console.log('sede:', sede);

    if (!fecha) { Swal.fire('Error', 'Ingrese la fecha de entrega', 'error'); return; }
    if (!sede) { Swal.fire('Error', 'Seleccione la sede', 'error'); return; }

    serializarHerramientasEditEntrega();

    const datos = new FormData(document.getElementById('formEditarEntrega'));

    // ✅ Forzar valores que jQuery asignó después de abrir el modal
    datos.set('ent_tipoentrega', tipo);
    datos.set('ent_fechaentrega', fecha);
    datos.set('ent_fecharegistra', fecharegistra);

    datos.append('actualizar_entrega', true);

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
                        icon: 'success', title: '¡Actualizado!', text: r.mensaje,
                        timer: 2000, showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(
                        document.getElementById('modalEditarEntrega')).hide();
                    if (_historialVehiculoId) cargarHistorial(_historialVehiculoId);
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

//BOTON ELIMINAR ENTREGA
$(document).on('click', '.btn-eliminar-entrega', function () {
    const id = $(this).data('id');

    Swal.fire({
        title: '¿Eliminar entrega?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: urlController,
                type: 'POST',
                data: { eliminar_entrega: true, id: id },
                success: function (res) {
                    try {
                        const r = typeof res === 'object' ? res : JSON.parse(res);
                        if (r.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: r.mensaje,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            if (_historialVehiculoId) cargarHistorial(_historialVehiculoId);
                        } else {
                            Swal.fire('Error', r.mensaje, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Error en la respuesta del servidor', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                }
            });
        }
    });
});

// Limpiar modal al cerrar
document.getElementById('modalEditarEntrega').addEventListener('hidden.bs.modal', function () {
    document.getElementById('listaHerramientasEditEntrega').innerHTML = '';
    document.getElementById('edit_ent_equipo_carretera').value = '';
    document.getElementById('formEditarEntrega').reset();
});
