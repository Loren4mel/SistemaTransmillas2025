/**
 * Seguimiento de Usuarios - Lógica de la interfaz de seguimiento
 *
 * Maneja DataTable, filtros, modales, Select2, y operaciones AJAX
 * Última modificación: 2026-04-20 - Agregado sistema de notificaciones toast
 */

(function () {
    'use strict';

    // Variables globales accesibles desde el HTML
    const dirPage = window.dirPage || window.location.pathname;
    const hasDeletePermission = window.hasDeletePermission || false;
    $.fn.dataTable.ext.errMode = 'none';

    // --- Variables de estado interno ---
    let tabla; // DataTable instance
    let refreshTimer; // Timer para auto-refresh

    // --- Funciones auxiliares reutilizables ---
    function mostrarError(mensaje) {
        Swal.fire({
            title: 'Error',
            text: mensaje,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        console.error(mensaje);
    }

    function mostrarExito(mensaje) {
        const container = document.getElementById('toastContainer');
        if (!container) {
            console.warn('Contenedor de toasts no encontrado');
            return;
        }
        // Asegurar estilos del contenedor
        container.style.cssText = 'position: fixed !important; top: 20px !important; right: 20px !important; z-index: 99999 !important; max-width: 350px !important; display: block !important;';

        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.cssText = 'background-color: #d4edda !important; border-color: #c3e6cb !important; color: #155724 !important; border-radius: 0.25rem !important; padding: 1rem !important; margin-bottom: 1rem !important; display: flex !important; align-items: center !important; justify-content: space-between !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; position: relative !important;';
        toast.innerHTML = `
            <div class="toast-notification-content">${mensaje}</div>
            <button type="button" class="toast-notification-close" aria-label="Cerrar" style="background: none !important; border: none !important; color: #155724 !important; font-size: 1.5rem !important; cursor: pointer !important; line-height: 1 !important; padding: 0 !important; width: 24px !important; height: 24px !important; display: flex !important; align-items: center !important; justify-content: center !important; position: relative !important; z-index: 999999 !important; flex-shrink: 0 !important;">&times;</button>
        `;

        container.appendChild(toast);

        // Configurar cierre manual
        const closeBtn = toast.querySelector('.toast-notification-close');
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toast.classList.add('fade-out');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 500);
        });

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('fade-out');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 500);
            }
        }, 5000);
    }

    function eliminarRegistro(id, accion) {
        // Verificar que la acción sea borraseguser (por compatibilidad)
        if (accion !== 'borraseguser') {
            mostrarError('Acción de eliminación no reconocida');
            return;
        }
        // Obtener detalles para mostrar
        $.get(dirPage, { accion: 'get_detalles_eliminar', id_combinado: id }, function (detalles) {
            // Construir mensaje de confirmación
            let mensaje = '<strong>¿Está seguro de eliminar el siguiente registro?</strong><br><br>';
            if (detalles.usuario) mensaje += '<b>Operario:</b> ' + detalles.usuario + '<br>';
            if (detalles.fecha) mensaje += '<b>Fecha:</b> ' + detalles.fecha + '<br>';
            if (detalles.motivo) mensaje += '<b>Motivo:</b> ' + detalles.motivo + '<br>';
            mensaje += '<br><small>Esta acción eliminará tanto el registro de seguimiento como el preoperacional asociado.</small>';

            Swal.fire({
                title: 'Confirmar eliminación',
                html: mensaje,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Enviar solicitud de eliminación
                    $.post(dirPage, { accion: 'eliminar_seguimiento', id_combinado: id }, function (res) {
                        if (res.success) {
                            // Recargar la tabla
                            tabla.ajax.reload();
                            if (res.message) {
                                mostrarExito(res.message);
                            }
                        } else {
                            mostrarError(res.message || 'Error al eliminar');
                        }
                    }, 'json').fail(function () {
                        mostrarError('Error de comunicación con el servidor');
                    });
                }
            });
        }).fail(function () {
            mostrarError('No se pudieron cargar los detalles del registro');
        });
    }

    function cargarOperarios(selectId, sedeId, textoPorDefecto = 'Seleccione') {
        if (!sedeId) {
            // Si no hay sede, limpiar select
            $(selectId).html('<option value="">' + textoPorDefecto + '</option>');
            return;
        }
        $.get(dirPage, { accion: 'get_operarios', idsede: sedeId }, function (data) {
            let options = '<option value="">' + textoPorDefecto + '</option>';
            data.forEach(op => {
                options += `<option value="${op.idusuarios}">${op.usu_nombre}</option>`;
            });
            $(selectId).html(options);
        }).fail(function () {
            mostrarError('No se pudieron cargar los operarios');
        });
    }

    function cargarZonas(selectId, sedeId) {
        if (!sedeId) {
            $(selectId).html('<option value="">Seleccione</option>');
            return;
        }
        $.get(dirPage, { accion: 'get_zonas', idsede: sedeId }, function (data) {
            let options = '<option value="">Seleccione</option>';
            data.forEach(z => {
                options += `<option value="${z.idzonatrabajo}">${z.zon_nombre}</option>`;
            });
            $(selectId).html(options);
        }).fail(function () {
            mostrarError('No se pudieron cargar las zonas');
        });
    }

    function guardarForm(formId, modalId, url = dirPage) {
        let $form = $(formId);
        let data = $form.serialize();
        $.post(url, data, function (res) {
            if (res.success) {
                $(modalId).modal('hide');
                tabla.ajax.reload();
                if (res.message) {
                    mostrarExito(res.message);
                }
            } else {
                mostrarError(res.message || 'Error al guardar');
            }
        }, 'json').fail(function () {
            mostrarError('Error de comunicación con el servidor');
        });
    }

    // Función para cargar operarios con Select2
    function cargarOperariosConSelect2(selector, sede, placeholder = 'Seleccione operario') {
        let url = sede ? `${dirPage}?accion=get_operarios&idsede=${encodeURIComponent(sede)}` : `${dirPage}?accion=get_all_operarios`;

        $.ajax({
            url: url,
            dataType: 'json',
            success: function (data) {
                let $select = $(selector);
                // Limpiar y agregar opción por defecto
                $select.empty().append('<option value="">' + placeholder + '</option>');

                if (Array.isArray(data)) {
                    data.forEach(function (op) {
                        $select.append(`<option value="${op.idusuarios}">${op.usu_nombre}</option>`);
                    });
                }

                // Destruir Select2 si ya existe
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                // Inicializar Select2
                $select.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $select.closest('.modal'),
                    dropdownCssClass: 'select2-large-dropdown',
                    dropdownAutoWidth: true,
                    dropdownCss: { 'max-height': '70vh', 'min-height': '200px', 'overflow': 'hidden' }
                });
            },
            error: function (xhr, status, error) {
                console.error('Error cargando operarios:', status, error);
                mostrarError('No se pudieron cargar los operarios');
            }
        });
    }

    // Función para cargar zonas con Select2
    function cargarZonasConSelect2(selector, sedeId, placeholder = 'Seleccione zona') {
        if (!sedeId) {
            $(selector).empty().append('<option value="">' + placeholder + '</option>');
            if ($(selector).data('select2')) {
                $(selector).select2('destroy');
            }
            $(selector).select2({
                placeholder: placeholder,
                allowClear: true,
                width: '100%',
                dropdownParent: $(selector).closest('.modal'),
                dropdownCssClass: 'select2-large-dropdown',
                dropdownAutoWidth: true,
                dropdownCss: { 'max-height': '70vh', 'min-height': '200px', 'overflow': 'hidden' }
            });
            return;
        }

        $.ajax({
            url: dirPage,
            data: { accion: 'get_zonas', idsede: sedeId },
            dataType: 'json',
            success: function (data) {
                let $select = $(selector);
                $select.empty().append('<option value="">' + placeholder + '</option>');
                if (Array.isArray(data)) {
                    data.forEach(z => {
                        $select.append(`<option value="${z.idzonatrabajo}">${z.zon_nombre}</option>`);
                    });
                }
                // Destruir y reinicializar Select2
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $select.closest('.modal'),
                    dropdownCssClass: 'select2-large-dropdown',
                    dropdownAutoWidth: true,
                    dropdownCss: { 'max-height': '70vh', 'min-height': '200px', 'overflow': 'hidden' }
                });
            },
            error: function () {
                mostrarError('No se pudieron cargar las zonas de trabajo');
            }
        });
    }

    // --- Inicialización DataTable ---
    function initDataTable() {
        // Definir columnas dinámicamente según permiso de eliminación
        let columns = [
            {
                data: 'alerta_html',
                render: function (data, type, row) {
                    return (row.alerta_html || '') + (row.alerta_izquierda_html || '') + ' ' + row.usu_nombre;
                }
            },
            { data: 'preoperacional_link' },
            { data: 'validacion_link' },
            { data: 'imagen_link' },
            { data: 'ingreso_link' },
            { data: 'seg_descr' },
            { data: 'seg_fechaingreso' },
            { data: 'zona_link' },
            { data: 'companero_link' },
            { data: 'hora_almuerzo_link' },
            { data: 'retorno_almuerzo_link' },
            { data: 'retorno_oficina_link' },
            { data: 'seg_fechafinalizo' },
            { data: 'usu_tipocontrato' },
            { data: 'veh_placa' },
            { data: 'fecha_seguro_html' },
            { data: 'fecha_tecno_html' },
            { data: 'fecha_licencia_html' },
            { data: 'cambio_aceite_html' }
        ];

        // Agregar columna de eliminar solo si tiene permiso
        if (hasDeletePermission) {
            columns.push({ data: 'eliminar_html' });
        }

        tabla = $('#tablaSeguimiento').DataTable({
            order: [], // sin orden inicial
            stripeClasses: [],
            processing: true,
            serverSide: true,
            ajax: {
                url: dirPage,
                type: 'POST',
                data: function (d) {
                    d.ajax = true;
                    d.fecha_inicio = $('#fecha_inicio').val();
                    d.fecha_fin = $('#fecha_fin').val();
                    d.sede = $('#sede').val();
                    d.operario = $('#operario').val();
                    d.motivo = $('#motivo').val();
                    d.tipo_contrato = $('#tipo_contrato').val();
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.error('Error AJAX DataTable:', {
                        status: xhr.status,
                        textStatus: textStatus,
                        errorThrown: errorThrown,
                        responseText: xhr.responseText
                    });
                    Swal.fire({ title: 'Error', text: 'Error cargando tabla. Revisa consola y logs del servidor.', icon: 'error', confirmButtonText: 'Aceptar' });
                }
            },
            columns: columns,
            columnDefs: [
                { targets: '_all', className: 'text-center', orderable: false }
            ],
            createdRow: function (row, data, dataIndex) {
                // Usar nuevos colores si existen, sino el antiguo row_color
                var bgColor = data.row_bg_color || data.row_color;
                var textColor = data.row_text_color;
                if (bgColor) {
                    // Aplica el color de fondo a la fila y a todas las celdas
                    $(row).css('background-color', bgColor);
                    $(row).find('td').css('background-color', bgColor);
                }
                // Aplicar color de texto si está definido
                if (textColor) {
                    $(row).css('color', textColor);
                    $(row).find('td').css('color', textColor);
                } else if (bgColor === '#922B21') {
                    // Compatibilidad: si el fondo es rojo oscuro antiguo, texto blanco
                    $(row).css('color', 'white');
                    $(row).find('td').css('color', 'white');
                }
            },
            scrollX: true,
            pageLength: 50,
            fixedHeader: true
        });

        // Auto-refresh cada 10 minutos
        refreshTimer = setInterval(function () {
            tabla.ajax.reload(null, false); // false para mantener la página actual
        }, 600000);

        // Limpiar timer al salir de la página
        $(window).on('beforeunload', function () {
            clearInterval(refreshTimer);
        });

        $('#tablaSeguimiento').on('error.dt', function (e, settings, techNote, message) {
            console.error('DataTable error.dt:', { techNote, message });
        });
    }

    function recargarTabla() {
        tabla.ajax.reload();
    }

    // --- Eventos globales (una sola vez) ---
    function bindEvents() {
        // Cuando cambia la sede en filtros, actualizar selects de operarios (filtro y modales)
        $('#sede').on('change', function () {
            let sede = $(this).val();
            // Actualizar filtro de operarios
            cargarOperarios('#operario', sede, 'Todos');
            // Actualizar selects de modales (si existen en el DOM)
            cargarOperarios('#ing_operario', sede, 'Seleccione');
            cargarOperarios('#vac_operario', sede, 'Seleccione');
            cargarOperarios('#lic_operario', sede, 'Seleccione');
        });

        // Mostrar deuda al seleccionar operario en el filtro
        $('#operario').on('change', function () {
            let id = $(this).val();
            if (id) {
                $.get(dirPage, { accion: 'get_deuda', idoperario: id }, function (res) {
                    $('#miCelda').html('Debe: $ ' + res.deuda).show();
                }).fail(function () {
                    $('#miCelda').hide();
                });
            } else {
                $('#miCelda').hide();
            }
        });

        // Carga de contenido en modal de ingreso
        $('#modalIngreso').on('show.bs.modal', function () {
            $('#ingresoModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(window.location.pathname, { accion: 'form_popup', tipo: 'ingreso_manual' }, function (html) {
                $('#ingresoModalBody').html(html);
                // Inicializar Select2 en los campos de operario y zona
                setTimeout(function () {
                    let sedeInicial = $('#ing_sede').val();
                    cargarOperariosConSelect2('#ing_operario', sedeInicial, 'Seleccione operario');
                    cargarZonasConSelect2('#ing_zona', sedeInicial, 'Seleccione zona');
                    // Inicializar campo de horas si existe
                    initHorasFieldPopup();
                }, 50);
            }).fail(function () {
                $('#ingresoModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
        });

        // Cuando se carga el formulario de ingreso (vía AJAX), delegar eventos
        $(document).on('change', '#ing_sede', function () {
            let sede = $(this).val();
            cargarOperariosConSelect2('#ing_operario', sede, 'Seleccione operario');
            cargarZonasConSelect2('#ing_zona', sede, 'Seleccione zona');
        });

        // Carga de operarios en modales de vacaciones y licencias al abrirlos

        // Al abrir modal de festivos
        $('#modalFestivos').on('show.bs.modal', function () {
            $('#festivosModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(window.location.pathname, { accion: 'form_popup', tipo: 'festivos' }, function (html) {
                $('#festivosModalBody').html(html);
            }).fail(function () {
                $('#festivosModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
        });

        // Al abrir modal de vacaciones
        $('#modalVacaciones').on('show.bs.modal', function () {
            $('#vacacionesModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(dirPage, { accion: 'form_popup', tipo: 'vacaciones' })
                .done(function (html) {
                    $('#vacacionesModalBody').html(html);
                    // Pequeño retraso para asegurar que el DOM esté actualizado
                    setTimeout(function () {
                        cargarOperariosConSelect2('#vac_operario', $('#sede').val(), 'Seleccione operario');
                    }, 50);
                })
                .fail(function () {
                    $('#vacacionesModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
                });
        });

        // Al abrir modal de licencias
        $('#modalLicencias').on('show.bs.modal', function () {
            $('#licenciasModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(dirPage, { accion: 'form_popup', tipo: 'licencias' })
                .done(function (html) {
                    $('#licenciasModalBody').html(html);
                    setTimeout(function () {
                        cargarOperariosConSelect2('#lic_operario', $('#sede').val(), 'Seleccione operario');
                    }, 50);
                })
                .fail(function () {
                    $('#licenciasModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
                });
        });

        // Al cerrar modales, destruir Select2 solo si existe la instancia
        $('#modalVacaciones, #modalLicencias, #modalIngreso, #popupModal').on('hidden.bs.modal', function () {
            $(this).find('select').each(function () {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
            });
        });

        // Manejo del formulario genérico en popup
        $(document).on('submit', '#popupForm', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: window.location.pathname,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        $('#popupModal').modal('hide');
                        tabla.ajax.reload();
                        if (res.message) {
                            mostrarExito(res.message);
                        }
                    } else {
                        mostrarError(res.message || 'Error al guardar');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Error en la respuesta:', textStatus, errorThrown, jqXHR.responseText);
                    mostrarError('Error de comunicación con el servidor');
                }
            });
        });

        // Manejo de burbujas de notificación
        $(document).on('click', '.noti_bubble', function () {
            var id = $(this).data('id');
            $('.noti_options[data-id="' + id + '"]').toggle();
        });

        // Forzar altura del dropdown Select2 al abrirse
        $(document).on('select2:open', function(e) {
            const dropdown = $('.select2-dropdown.select2-large-dropdown');
            if (dropdown.length) {
                // Aplicar estilos directamente para asegurar altura
                dropdown.css({
                    'height': '500px',
                    'max-height': '70vh',
                    'min-height': '200px',
                    'overflow': 'hidden'
                });
                // Asegurar que los resultados tengan scroll
                const results = dropdown.find('.select2-results');
                if (results.length) {
                    results.css({
                        'height': 'calc(100% - 40px)',
                        'overflow-y': 'auto',
                        'max-height': 'none'
                    });
                }
            }
        });
    }

    // --- Funciones de apertura de modales ----
    function abrirModalIngreso() {
        $('#modalIngreso').modal('show');
    }
    function abrirModalFestivos() {
        $('#modalFestivos').modal('show');
    }
    function abrirModalVacaciones() {
        $('#modalVacaciones').modal('show');
    }
    function abrirModalLicencias() {
        $('#modalLicencias').modal('show');
    }

    // Funciones de guardado ahora usan la función genérica
    function guardarFestivos() {
        guardarForm('#formFestivos', '#modalFestivos');
    }
    function guardarVacaciones() {
        guardarForm('#formVacaciones', '#modalVacaciones');
    }
    function guardarLicencias() {
        guardarForm('#formLicencias', '#modalLicencias');
    }

    // Función helper para inicializar campo de horas en popup de ingreso
    function initHorasFieldPopup() {
        const $popupForm = $('#popupForm');
        if (!$popupForm.length) return;
        const $motivo = $popupForm.find('#motivo');
        const $horasContainer = $popupForm.find('#horas_container');
        const $horasSelect = $popupForm.find('#horas');
        if (!$motivo.length || !$horasContainer.length || !$horasSelect.length) return;

        function toggleHorasField(event) {
            const motivoValue = $motivo.val();
            const show = motivoValue === 'IngresoHoras';
            if (show) {
                $horasContainer.removeClass('d-none');
                $horasSelect.prop('required', true);
            } else {
                $horasContainer.addClass('d-none');
                $horasSelect.prop('required', false);
                $horasSelect.val('');
            }
        }

        // Estado inicial
        toggleHorasField();

        // Configurar eventos según presencia de Select2
        const hasSelect2 = $motivo.data('select2');
        if (hasSelect2) {
            $motivo.off('select2:select.horas select2:unselect.horas change.horas');
            $motivo.on('select2:select.horas', toggleHorasField);
            $motivo.on('select2:unselect.horas', toggleHorasField);
            $motivo.on('change.horas', toggleHorasField);
        } else {
            $motivo.off('change.horas');
            $motivo.on('change.horas', toggleHorasField);
        }
    }

    // Función para abrir popup genérico (sin cambios)
    function abrirPopup(tipo, id, param) {
        var titulos = {
            'zona': 'Zona de trabajo',
            'companero': 'Compañero',
            'trabaja_con': 'Trabaja con',
            'hora_almuerzo': 'Hora almuerzo',
            'retorno_almuerzo': 'Retorno almuerzo',
            'retorno_oficina': 'Retorno oficina',
            'festivos': 'Festivos',
            'vacaciones': 'Vacaciones',
            'licencias': 'Licencias',
            'ingreso_manual': 'Ingreso manual',
            'ingreso': 'Ingreso'
        };
        var titulo = titulos[tipo] || ('Editando: ' + tipo);
        $('#popupModal .modal-title').text(titulo);
        $('#popupModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
        $('#popupModal').modal('show');

        $.get(window.location.pathname, {
            accion: 'form_popup',
            tipo: tipo,
            id: id,
            param: param
        }, function (html) {
            $('#popupModalBody').html(html);
            // Inicializar Select2 en selects relevantes después de cargar el HTML
            setTimeout(function() {
                // IDs de selects que deben usar Select2
                var selectIds = ['zona', 'ing_zona', 'ing_operario', 'companero', 'vac_operario', 'lic_operario'];
                selectIds.forEach(function(id) {
                    var $select = $('#' + id);
                    if ($select.length && !$select.data('select2')) {
                        $select.select2({
                            placeholder: 'Seleccione',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: $select.closest('.modal'),
                            dropdownCssClass: 'select2-large-dropdown',
                            dropdownAutoWidth: true,
                            dropdownCss: { 'max-height': '70vh', 'min-height': '200px', 'overflow': 'hidden' }
                        });
                    }
                });

                // Inicializar campo de horas si existe
                initHorasFieldPopup();
            }, 100);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Error al cargar popup:', textStatus, errorThrown);
            $('#popupModalBody').html('<div class="alert alert-danger">Error al cargar el formulario. Ver consola.</div>');
        });
    }

    // Función guardarIngreso (específica porque usa FormData)
    function guardarIngreso() {
        let formData = new FormData(document.getElementById('formIngreso'));
        $.ajax({
            url: dirPage,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#modalIngreso').modal('hide');
                    tabla.ajax.reload();
                    if (res.message) {
                        mostrarExito(res.message);
                    }
                } else {
                    mostrarError(res.message || 'Error al guardar');
                }
            },
            error: function () {
                Swal.fire({ title: 'Error', text: 'Error en la petición', icon: 'error', confirmButtonText: 'Aceptar' });
            }
        });
    }

    // Función para ver documentos (preoperacional, validación, etc.)
    function verDocumento(url) {
        window.open(url, '_blank');
    }

    // Función para abrir ventana de validación preoperacional y recargar tabla al cerrar
    function abrirValidacionPreoperacional(url) {
        var ventana = window.open(url, '_blank', 'width=800,height=600,scrollbars=yes');
        var timer = setInterval(function () {
            if (ventana.closed) {
                clearInterval(timer);
                tabla.ajax.reload();
            }
        }, 500);
    }

    // --- Inicialización principal ---
    function init() {
        initDataTable();
        bindEvents();
    }

    // Exponer funciones globales necesarias para el HTML
    window.eliminarRegistro = eliminarRegistro;
    window.recargarTabla = recargarTabla;
    window.abrirModalIngreso = abrirModalIngreso;
    window.abrirModalFestivos = abrirModalFestivos;
    window.abrirModalVacaciones = abrirModalVacaciones;
    window.abrirModalLicencias = abrirModalLicencias;
    window.guardarFestivos = guardarFestivos;
    window.guardarVacaciones = guardarVacaciones;
    window.guardarLicencias = guardarLicencias;
    window.guardarIngreso = guardarIngreso;
    window.abrirPopup = abrirPopup;
    window.verDocumento = verDocumento;
    window.abrirValidacionPreoperacional = abrirValidacionPreoperacional;

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();