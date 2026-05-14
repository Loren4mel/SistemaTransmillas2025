/**
 * Seguimiento de Usuarios - Logica de la interfaz de seguimiento
 *
 * Maneja DataTable, filtros, modales, Select2, y operaciones AJAX
 */
(function () {
    'use strict';

    // Variables globales accesibles desde el HTML
    var dirPage = window.dirPage || window.location.pathname;
    var hasDeletePermission = window.hasDeletePermission || false;
    $.fn.dataTable.ext.errMode = 'none';

    // --- Estado interno ---
    var tabla;
    var refreshTimer;

    // ==================== HELPERS REUTILIZABLES ====================

    /** Select2 defaults — evita 5 copias identicas del mismo config */
    function getSelect2Defaults(placeholder) {
        return {
            placeholder: placeholder || 'Seleccione',
            allowClear: true,
            width: '100%',
            dropdownParent: null, // se asigna por instancia
            dropdownCssClass: 'select2-large-dropdown',
            dropdownAutoWidth: true,
            dropdownCss: { 'max-height': '70vh', 'min-height': '200px', 'overflow': 'hidden' }
        };
    }

    /** Inicializa Select2 sobre un <select> con los defaults del proyecto */
    function initSelect2($select, placeholder) {
        if (!$select.length) return;
        if ($select.data('select2')) {
            $select.select2('destroy');
        }
        var opts = getSelect2Defaults(placeholder);
        opts.dropdownParent = $select.closest('.modal');
        $select.select2(opts);
    }

    /** Toast de exito (usa clases CSS, no inline styles) */
    function mostrarExito(mensaje) {
        var container = document.getElementById('toastContainer');
        if (!container) return;
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 99999; max-width: 350px; display: block;';

        var toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML =
            '<div class="toast-notification-content">' + mensaje + '</div>' +
            '<button type="button" class="toast-notification-close" aria-label="Cerrar">&times;</button>';

        container.appendChild(toast);

        function removeToast() {
            toast.classList.add('fade-out');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 500);
        }

        toast.querySelector('.toast-notification-close').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            removeToast();
        });

        setTimeout(removeToast, 5000);
    }

    function mostrarError(mensaje) {
        Swal.fire({ title: 'Error', text: mensaje, icon: 'error', confirmButtonText: 'Aceptar' });
    }

    /** Carga contenido AJAX en un modal — elimina 4 copias del mismo patron */
    function cargarModalBody(modalSelector, bodySelector, tipo, extraData, onDone) {
        $(modalSelector).on('show.bs.modal', function () {
            $(bodySelector).html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
            $.get(dirPage, $.extend({ accion: 'form_popup', tipo: tipo }, extraData || {}))
                .done(function (html) {
                    $(bodySelector).html(html);
                    if (onDone) setTimeout(onDone, 50);
                })
                .fail(function () {
                    $(bodySelector).html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
                });
        });
    }

    /** Envio AJAX unificado para formularios (serialize y FormData) */
    function enviarForm($form, opciones) {
        var defaults = {
            url: dirPage,
            dataType: 'json',
            onSuccess: function () {}
        };
        var opt = $.extend({}, defaults, opciones);
        var usaFormData = opt.formData === true;
        var data = usaFormData ? new FormData($form[0]) : $form.serialize();

        $.ajax({
            url: opt.url,
            type: 'POST',
            data: data,
            processData: !usaFormData,
            contentType: usaFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
            dataType: opt.dataType,
            success: function (res) {
                if (res.success) {
                    if (opt.modalId) $(opt.modalId).modal('hide');
                    if (tabla) tabla.ajax.reload();
                    if (res.message) mostrarExito(res.message);
                    if (opt.onSuccess) opt.onSuccess(res);
                } else {
                    mostrarError(res.message || 'Error al guardar');
                }
            },
            error: function () {
                mostrarError('Error de comunicacion con el servidor');
            }
        });
    }

    // ==================== CARGA DE OPERARIOS Y ZONAS ====================

    /** Carga operarios en un <select> (con o sin Select2).
     *  Si no se especifica sede, carga todos los operarios. */
    function cargarOperarios(selectId, sedeId, placeholder, useSelect2) {
        placeholder = placeholder || 'Seleccione';
        var $select = $(selectId);
        if (!$select.length) return;

        var params = (sedeId && sedeId > 0)
            ? { accion: 'get_operarios', idsede: sedeId }
            : { accion: 'get_all_operarios' };

        $.get(dirPage, params, function (data) {
            var options = '<option value="">' + placeholder + '</option>';
            if (Array.isArray(data)) {
                data.forEach(function (op) {
                    options += '<option value="' + op.idusuarios + '">' + op.usu_nombre + '</option>';
                });
            }
            $select.html(options);
            if (useSelect2) initSelect2($select, placeholder);
        }).fail(function () {
            mostrarError('No se pudieron cargar los operarios');
        });
    }

    /** Carga zonas en un <select> (con o sin Select2) */
    function cargarZonas(selectId, sedeId, placeholder, useSelect2) {
        placeholder = placeholder || 'Seleccione';
        var $select = $(selectId);
        if (!$select.length) return;

        if (!sedeId) {
            $select.html('<option value="">' + placeholder + '</option>');
            if (useSelect2) initSelect2($select, placeholder);
            return;
        }

        $.get(dirPage, { accion: 'get_zonas', idsede: sedeId }, function (data) {
            var options = '<option value="">' + placeholder + '</option>';
            if (Array.isArray(data)) {
                data.forEach(function (z) {
                    options += '<option value="' + z.idzonatrabajo + '">' + z.zon_nombre + '</option>';
                });
            }
            $select.html(options);
            if (useSelect2) initSelect2($select, placeholder);
        }).fail(function () {
            mostrarError('No se pudieron cargar las zonas de trabajo');
        });
    }

    // ==================== DATATABLE ====================

    function initDataTable() {
        var columns = [
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

        if (hasDeletePermission) {
            columns.push({ data: 'eliminar_html' });
        }

        tabla = $('#tablaSeguimiento').DataTable({
            order: [],
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
                    console.error('Error AJAX DataTable:', { status: xhr.status, textStatus: textStatus, errorThrown: errorThrown, responseText: xhr.responseText });
                    Swal.fire({ title: 'Error', text: 'Error cargando tabla. Revisa consola y logs del servidor.', icon: 'error', confirmButtonText: 'Aceptar' });
                }
            },
            columns: columns,
            columnDefs: [
                { targets: '_all', className: 'text-center', orderable: false }
            ],
            createdRow: function (row, data) {
                var bgColor = data.row_bg_color || data.row_color;
                var textColor = data.row_text_color;
                if (bgColor) {
                    $(row).css('background-color', bgColor);
                    $(row).find('td').css('background-color', bgColor);
                }
                if (textColor) {
                    $(row).css('color', textColor);
                    $(row).find('td').css('color', textColor);
                } else if (bgColor === '#922B21') {
                    $(row).css('color', 'white');
                    $(row).find('td').css('color', 'white');
                }
            },
            scrollX: true,
            pageLength: 50,
            fixedHeader: true
        });

        refreshTimer = setInterval(function () {
            tabla.ajax.reload(null, false);
        }, 600000);

        $(window).on('beforeunload', function () {
            clearInterval(refreshTimer);
        });

        $('#tablaSeguimiento').on('error.dt', function (e, settings, techNote, message) {
            console.error('DataTable error.dt:', { techNote: techNote, message: message });
        });
    }

    function recargarTabla() {
        tabla.ajax.reload();
    }

    // ==================== EVENTOS GLOBALES ====================

    function bindEvents() {
        // Sede cambia en filtro → actualizar selects de operarios
        $('#sede').on('change', function () {
            var sede = $(this).val();
            cargarOperarios('#operario', sede, 'Todos', false);
            cargarOperarios('#ing_operario', sede, 'Seleccione', true);
            cargarOperarios('#vac_operario', sede, 'Seleccione', true);
            cargarOperarios('#lic_operario', sede, 'Seleccione', true);
        });

        // Carga inicial del select de operarios del filtro
        cargarOperarios('#operario', $('#sede').val(), 'Todos', false);

        // Deuda al seleccionar operario
        $('#operario').on('change', function () {
            var id = $(this).val();
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

        // --- Carga de modales principales ---
        cargarModalBody('#modalFestivos', '#festivosModalBody', 'festivos');

        cargarModalBody('#modalVacaciones', '#vacacionesModalBody', 'vacaciones', {}, function () {
            cargarOperarios('#vac_operario', $('#sede').val(), 'Seleccione operario', true);
        });

        cargarModalBody('#modalLicencias', '#licenciasModalBody', 'licencias', {}, function () {
            cargarOperarios('#lic_operario', $('#sede').val(), 'Seleccione operario', true);
        });

        // Select2 cambia con sede en modal de ingreso manual
        $(document).on('change', '#ing_sede', function () {
            var sede = $(this).val();
            cargarOperarios('#ing_operario', sede, 'Seleccione operario', true);
            cargarZonas('#ing_zona', sede, 'Seleccione zona', true);
        });

        // Destruir Select2 al cerrar modales
        $('#modalVacaciones, #modalLicencias, #popupModal').on('hidden.bs.modal', function () {
            $(this).find('select').each(function () {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
            });
        });

        // --- Formularios ---
        // Formulario de popup generico
        $(document).on('submit', '#popupForm', function (e) {
            e.preventDefault();
            enviarForm($(this), { formData: true, modalId: '#popupModal' });
        });

        // --- Hover: burbujas de notificacion (ARL, etc.) ---
        $(document).on('mouseenter', '.noti_bubble', function () {
            var $bubble = $(this);
            var id = $bubble.data('id');
            var $dropdown = $('.noti_options[data-id="' + id + '"]');
            if (!$dropdown.length) return;
            var rect = $bubble[0].getBoundingClientRect();
            $dropdown.data('_parentBubble', $bubble).addClass('noti-open');
            $dropdown.appendTo('body').css({
                position: 'fixed', left: rect.left, top: rect.bottom,
                display: 'block', zIndex: 99999
            });
        });

        $(document).on('mouseleave', '.noti_bubble', function (e) {
            if ($(e.relatedTarget).closest('.noti_options.noti-open').length) return;
            cerrarNotiDropdown($(this));
        });

        $(document).on('mouseleave', '.noti_options.noti-open', function (e) {
            var $dropdown = $(this);
            var $bubble = $dropdown.data('_parentBubble');
            if ($bubble && $.contains($bubble[0], e.relatedTarget)) return;
            cerrarNotiDropdown($bubble);
        });

        function cerrarNotiDropdown($bubble) {
            if (!$bubble || !$bubble.length) return;
            var id = $bubble.data('id');
            var $dropdown = $('.noti_options.noti-open[data-id="' + id + '"]');
            if ($dropdown.length) {
                $dropdown.removeClass('noti-open').css({ position: '', left: '', top: '', display: 'none', zIndex: '' });
                $dropdown.insertAfter($bubble);
            }
        }

        // --- Hover: dropdown de alertas (documentos vencidos) ---
        $(document).on('mouseenter', '.alerta-wrapper', function () {
            var $wrapper = $(this);
            var $dropdown = $wrapper.find('.alerta-dropdown');
            if (!$dropdown.length) return;
            var rect = $wrapper[0].getBoundingClientRect();
            $dropdown.data('_parentWrapper', $wrapper).addClass('alerta-open');
            $dropdown.appendTo('body').css({
                position: 'fixed', left: rect.left, top: rect.bottom,
                display: 'block', zIndex: 99999
            });
            $('.warning-dot').css('animation-play-state', 'paused');
        });

        $(document).on('mouseleave', '.alerta-wrapper', function (e) {
            if ($(e.relatedTarget).closest('.alerta-dropdown.alerta-open').length) return;
            cerrarAlertaDropdown($(this));
        });

        $(document).on('mouseleave', '.alerta-dropdown.alerta-open', function (e) {
            var $dropdown = $(this);
            var $wrapper = $dropdown.data('_parentWrapper');
            if ($wrapper && $.contains($wrapper[0], e.relatedTarget)) return;
            cerrarAlertaDropdown($wrapper);
        });

        function cerrarAlertaDropdown($wrapper) {
            if (!$wrapper || !$wrapper.length) return;
            var $dropdown = $('.alerta-dropdown.alerta-open').filter(function () {
                return $(this).data('_parentWrapper') && $(this).data('_parentWrapper').is($wrapper);
            });
            if ($dropdown.length) {
                $dropdown.removeClass('alerta-open').css({ position: '', left: '', top: '', display: '', zIndex: '' });
                $dropdown.appendTo($wrapper);
            }
            $('.warning-dot').css('animation-play-state', '');
        }
    }

    // ==================== FUNCIONES PUBLICAS (window) ====================

    function eliminarRegistro(id, accion) {
        if (accion !== 'borraseguser') {
            mostrarError('Accion de eliminacion no reconocida');
            return;
        }
        $.get(dirPage, { accion: 'get_detalles_eliminar', id_combinado: id }, function (detalles) {
            var mensaje = '<strong>Esta seguro de eliminar el siguiente registro?</strong><br><br>';
            if (detalles.usuario) mensaje += '<b>Operario:</b> ' + detalles.usuario + '<br>';
            if (detalles.fecha) mensaje += '<b>Fecha:</b> ' + detalles.fecha + '<br>';
            if (detalles.motivo) mensaje += '<b>Motivo:</b> ' + detalles.motivo + '<br>';
            mensaje += '<br><small>Esta accion eliminara tanto el registro de seguimiento como el preoperacional asociado.</small>';

            Swal.fire({
                title: 'Confirmar eliminacion',
                html: mensaje,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.post(dirPage, { accion: 'eliminar_seguimiento', id_combinado: id }, function (res) {
                        if (res.success) {
                            tabla.ajax.reload();
                            if (res.message) mostrarExito(res.message);
                        } else {
                            mostrarError(res.message || 'Error al eliminar');
                        }
                    }, 'json').fail(function () {
                        mostrarError('Error de comunicacion con el servidor');
                    });
                }
            });
        }).fail(function () {
            mostrarError('No se pudieron cargar los detalles del registro');
        });
    }

    function abrirModalFestivos() { $('#modalFestivos').modal('show'); }
    function abrirModalVacaciones() { $('#modalVacaciones').modal('show'); }
    function abrirModalLicencias() { $('#modalLicencias').modal('show'); }

    function guardarFestivos() {
        var fecha = $('#formFestivos [name="fecha"]').val();
        if (!fecha) {
            mostrarError('La fecha es obligatoria');
            return;
        }
        enviarForm($('#formFestivos'), { modalId: '#modalFestivos' });
    }
    function guardarVacaciones() {
        var ini = $('#formVacaciones [name="fecha_ini"]').val();
        var fin = $('#formVacaciones [name="fecha_fin"]').val();
        if (!ini || !fin) {
            mostrarError('Ambas fechas son obligatorias');
            return;
        }
        if (fin < ini) {
            mostrarError('La fecha fin no puede ser anterior a la fecha inicio');
            return;
        }
        enviarForm($('#formVacaciones'), { modalId: '#modalVacaciones' });
    }
    function guardarLicencias() {
        var ini = $('#formLicencias [name="fecha_ini"]').val();
        var fin = $('#formLicencias [name="fecha_fin"]').val();
        if (!ini || !fin) {
            mostrarError('Ambas fechas son obligatorias');
            return;
        }
        if (fin < ini) {
            mostrarError('La fecha fin no puede ser anterior a la fecha inicio');
            return;
        }
        enviarForm($('#formLicencias'), { modalId: '#modalLicencias' });
    }
    /** Motivos que NO representan inicio real de jornada y no requieren zona */
    var motivosSinZona = [
        'No trabajo', 'Sancionado', 'Incapacidad', 'Se devolvio',
        'Positivo Covid', 'Cancelacion contrato', 'Abandono de puesto',
        'Vacaciones', 'descanso', 'descanso no remunerado',
        'dia con sancion', 'Festivo en vacaciones'
    ];

    function motivoRequiereZona(motivo) {
        return motivosSinZona.indexOf(motivo) === -1;
    }

    // --- Toggle de horas y zona en popup de ingreso ---
    function initHorasFieldPopup() {
        var $popupForm = $('#popupForm');
        if (!$popupForm.length) return;
        var $motivo = $popupForm.find('#motivo');
        var $horasContainer = $popupForm.find('#horas_container');
        var $horasSelect = $popupForm.find('#horas');
        var $zonaSelect = $popupForm.find('#ing_zona');
        var $zonaCol = $zonaSelect.closest('.col-md-6');
        if (!$motivo.length) return;

        function toggleFields() {
            var motivo = $motivo.val();
            // Horas
            if ($horasContainer.length && $horasSelect.length) {
                var showHoras = motivo === 'IngresoHoras';
                $horasContainer.toggleClass('d-none', !showHoras);
                $horasSelect.prop('required', showHoras);
                if (!showHoras) $horasSelect.val('');
            }
            // Zona
            if ($zonaSelect.length) {
                var requiereZona = motivoRequiereZona(motivo);
                $zonaSelect.prop('required', requiereZona);
                var $noRequerida = $popupForm.find('.zona-no-requerida');
                if (!requiereZona) {
                    $zonaSelect.val('');
                    $zonaCol.addClass('text-muted');
                    if ($noRequerida.length) $noRequerida.show();
                } else {
                    $zonaCol.removeClass('text-muted');
                    if ($noRequerida.length) $noRequerida.hide();
                }
            }
        }

        toggleFields();

        $motivo.off('.popupFields').on('change.popupFields select2:select.popupFields select2:unselect.popupFields', toggleFields);
    }

    // --- Popup generico ---
    function abrirPopup(tipo, id, param) {
        var titulos = {
            'zona': 'Zona de trabajo', 'trabaja_con': 'Trabaja con',
            'hora_almuerzo': 'Hora almuerzo', 'retorno_almuerzo': 'Retorno almuerzo',
            'retorno_oficina': 'Retorno oficina', 'festivos': 'Festivos',
            'vacaciones': 'Vacaciones', 'licencias': 'Licencias',
            'ingreso': 'Ingreso'
        };
        $('#popupModal .modal-title').text(titulos[tipo] || ('Editando: ' + tipo));
        $('#popupModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
        $('#popupModal').modal('show');

        $.get(dirPage, { accion: 'form_popup', tipo: tipo, id: id, param: param }, function (html) {
            $('#popupModalBody').html(html);
            setTimeout(function () {
                $('#popupModalBody').find('select').each(function () {
                    var $sel = $(this);
                    if (!$sel.data('select2') && $sel.is('select')) {
                        initSelect2($sel, 'Seleccione');
                    }
                });
                initHorasFieldPopup();
            }, 100);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Error al cargar popup:', textStatus, errorThrown);
            $('#popupModalBody').html('<div class="alert alert-danger">Error al cargar el formulario. Ver consola.</div>');
        });
    }

    function verDocumento(url) {
        window.open(url, '_blank');
    }

    function abrirValidacionPreoperacional(url) {
        var ventana = window.open(url, '_blank', 'width=800,height=600,scrollbars=yes');
        var timer = setInterval(function () {
            if (ventana.closed) {
                clearInterval(timer);
                tabla.ajax.reload();
            }
        }, 500);
    }

    // ==================== INICIALIZACION ====================

    function init() {
        initDataTable();
        bindEvents();
    }

    // Exponer funciones globales
    window.eliminarRegistro = eliminarRegistro;
    window.recargarTabla = recargarTabla;
    window.abrirModalFestivos = abrirModalFestivos;
    window.abrirModalVacaciones = abrirModalVacaciones;
    window.abrirModalLicencias = abrirModalLicencias;
    window.guardarFestivos = guardarFestivos;
    window.guardarVacaciones = guardarVacaciones;
    window.guardarLicencias = guardarLicencias;
    window.abrirPopup = abrirPopup;
    window.verDocumento = verDocumento;
    window.abrirValidacionPreoperacional = abrirValidacionPreoperacional;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
