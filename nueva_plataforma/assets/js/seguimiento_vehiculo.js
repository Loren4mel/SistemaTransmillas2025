/**
 * Seguimiento de Vehículos - Lógica de la interfaz
 *
 * Maneja DataTable, filtros, modales, Select2, y operaciones AJAX
 */
(function () {
    'use strict';

    var dirPage = window.dirPage || window.location.pathname;
    var hasAdminPermission = window.hasAdminPermission || false;
    $.fn.dataTable.ext.errMode = 'none';

    var tabla;

    // ==================== HELPERS ====================

    function mostrarExito(mensaje) {
        Swal.fire({ title: 'Éxito', text: mensaje, icon: 'success', timer: 3000, showConfirmButton: false });
    }

    function mostrarError(mensaje) {
        Swal.fire({ title: 'Error', text: mensaje, icon: 'error', confirmButtonText: 'Aceptar' });
    }

    function getSelect2Defaults(placeholder) {
        return {
            placeholder: placeholder || 'Seleccione',
            allowClear: true,
            width: '100%',
            dropdownParent: null
        };
    }

    function initSelect2($select, placeholder) {
        if (!$select.length) return;
        if ($select.data('select2')) {
            $select.select2('destroy');
        }
        var opts = getSelect2Defaults(placeholder);
        opts.dropdownParent = $select.closest('.modal');
        $select.select2(opts);
    }

    function cargarConductores(selectId, sedeId) {
        var $select = $(selectId);
        if (!$select.length) return;
        var params = sedeId && sedeId > 0
            ? { accion: 'get_conductores', idsede: sedeId }
            : { accion: 'get_conductores' };
        $.get(dirPage, params, function (data) {
            var options = '<option value="">Todos</option>';
            if (Array.isArray(data)) {
                data.forEach(function (c) {
                    options += '<option value="' + c.idusuarios + '">' + c.usu_nombre + '</option>';
                });
            }
            $select.html(options);
        });
    }

    /** Carga contenido AJAX en el modal genérico */
    function cargarPopup(tipo, id, titulo) {
        $('#popupModal .modal-title').text(titulo || ('Editando: ' + tipo));
        $('#popupModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
        $('#popupModal').modal('show');

        $.get(dirPage, { accion: 'form_popup', tipo: tipo, id: id })
            .done(function (html) {
                $('#popupModalBody').html(html);
            })
            .fail(function () {
                $('#popupModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
    }

    // ==================== DATATABLE ====================

    function initDataTable() {
        tabla = $('#tablaVehiculos').DataTable({
            order: [],
            stripeClasses: [],
            processing: true,
            serverSide: true,
            ajax: {
                url: dirPage,
                type: 'POST',
                data: function (d) {
                    d.ajax = true;
                    d.propiedad = $('#propiedad').val();
                    d.estado_general = $('#estado_general').val();
                    d.sede = $('#sede').val();
                    d.conductor = $('#conductor').val();
                    d.search = { value: $('#search_placa').val(), regex: false };
                },
                error: function (xhr) {
                    console.error('Error AJAX DataTable:', xhr);
                    Swal.fire({ title: 'Error', text: 'Error cargando tabla.', icon: 'error' });
                }
            },
            columns: [
                { data: 'alerta_html', orderable: false },
                { data: 'veh_placa' },
                { data: 'veh_tipo', render: function (d, t, r) { return (r.veh_tipo || '') + ' / ' + (r.veh_tipov || ''); } },
                { data: 'veh_marca', render: function (d, t, r) { return (r.veh_marca || '-') + ' / ' + (r.veh_modelo || '-'); } },
                { data: 'estado_general_badge' },
                { data: 'conductor_nombre' },
                { data: 'kilometraje_actual_fmt' },
                { data: 'ultimo_preop_link' },
                { data: 'alerta_soat' },
                { data: 'alerta_tecno' },
                { data: 'alerta_mantenimiento' },
                { data: 'cambio_aceite_html' },
                { data: 'comparendos_html' },
                { data: 'propiedad' },
                { data: 'acciones_html', orderable: false }
            ],
            columnDefs: [
                { targets: '_all', className: 'text-center' }
            ],
            createdRow: function (row, data) {
                var bgColor = data.row_color;
                var textColor = data.row_text_color;
                if (bgColor) {
                    $(row).css('background-color', bgColor);
                    $(row).find('td').css('background-color', bgColor);
                }
                if (textColor) {
                    $(row).css('color', textColor);
                    $(row).find('td').css('color', textColor);
                }
            },
            scrollX: true,
            pageLength: 25,
            fixedHeader: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    }

    function recargarTabla() {
        tabla.ajax.reload();
    }

    // ==================== ACCIONES DE FILA ====================

    function abrirCambiarEstado(idVehiculo) {
        if (!hasAdminPermission) { mostrarError('Acción solo para administradores'); return; }
        cargarPopup('cambiar_estado', idVehiculo, 'Cambiar Estado del Vehículo');
    }

    function abrirRegistroEvento(idVehiculo) {
        if (!hasAdminPermission) { mostrarError('Acción solo para administradores'); return; }
        cargarPopup('registro_evento', idVehiculo, 'Registrar Evento');
    }

    function abrirHistorialKm(idVehiculo) {
        cargarPopup('historial_kilometraje', idVehiculo, 'Historial de Kilometraje');
    }

    function abrirComparendos(idVehiculo) {
        cargarPopup('comparendos', idVehiculo, 'Comparendos');
    }

    function abrirDetalleVehiculo(idVehiculo) {
        cargarPopup('detalle_vehiculo', idVehiculo, 'Detalle del Vehículo');
    }

    function abrirValidacionPreopVehiculo(url) {
        var ventana = window.open(url, '_blank', 'width=800,height=600,scrollbars=yes');
        var timer = setInterval(function () {
            if (ventana.closed) {
                clearInterval(timer);
                tabla.ajax.reload();
            }
        }, 500);
    }

    // ==================== GUARDAR FORMULARIOS ====================

    function enviarFormVehiculo($form, modalId) {
        $.ajax({
            url: dirPage,
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    if (modalId) $(modalId).modal('hide');
                    tabla.ajax.reload();
                    if (res.message) mostrarExito(res.message);
                } else {
                    mostrarError(res.message || 'Error al guardar');
                }
            },
            error: function () {
                mostrarError('Error de comunicación con el servidor');
            }
        });
    }

    function guardarCambiarEstado(event) {
        event.preventDefault();
        var obs = $('#formCambiarEstado [name="observaciones"]').val().trim();
        if (!obs) {
            mostrarError('Las observaciones son obligatorias para cambiar el estado');
            return false;
        }
        enviarFormVehiculo($('#formCambiarEstado'), '#popupModal');
        return false;
    }

    function guardarRegistroEvento(event) {
        event.preventDefault();
        enviarFormVehiculo($('#formRegistroEvento'), '#popupModal');
        return false;
    }

    // ==================== EVENTOS ====================

    function bindEvents() {
        // Sede cambia → actualizar select de conductores
        $('#sede').on('change', function () {
            cargarConductores('#conductor', $(this).val());
        });

        // Carga inicial de conductores
        cargarConductores('#conductor', $('#sede').val());

        // Hover: dropdowns de alerta
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
        }
    }

    // ==================== INICIALIZACIÓN ====================

    function init() {
        initDataTable();
        bindEvents();
    }

    // Exponer funciones globales
    window.recargarTabla = recargarTabla;
    window.abrirCambiarEstado = abrirCambiarEstado;
    window.abrirRegistroEvento = abrirRegistroEvento;
    window.abrirHistorialKm = abrirHistorialKm;
    window.abrirComparendos = abrirComparendos;
    window.abrirDetalleVehiculo = abrirDetalleVehiculo;
    window.abrirValidacionPreopVehiculo = abrirValidacionPreopVehiculo;
    window.guardarCambiarEstado = guardarCambiarEstado;
    window.guardarRegistroEvento = guardarRegistroEvento;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
