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
    var kmChartInstance = null; // Instancia de Chart.js para kilometraje

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
    function cargarPopup(tipo, id, titulo, onLoaded) {
        $('#popupModal .modal-title').text(titulo || ('Editando: ' + tipo));
        $('#popupModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
        $('#popupModal').modal('show');

        $.get(dirPage, { accion: 'form_popup', tipo: tipo, id: id })
            .done(function (html) {
                $('#popupModalBody').html(html);
                if (typeof onLoaded === 'function') {
                    onLoaded();
                }
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
                    d.fecha = $('#fecha_consulta').val();
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
                // Columna 4 (índice 4): Estado General — valor crudo, no badge HTML
                { data: 'estado_general', render: function (d) { return d; } },
                { data: 'registro_dia_html', orderable: false },
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

                // === CAMBIO 1: Full-cell background para Estado General ===
                var estadoCell = $(row).find('td').eq(4);
                var estado = data.estado_general;
                if (estado === 'OPTIMO') {
                    estadoCell.css({ 'background-color': '#e8f2ec', 'color': '#1e7f4f', 'font-weight': '700' });
                } else if (estado === 'CON_NOVEDADES') {
                    estadoCell.css({ 'background-color': '#fff4e5', 'color': '#b54708', 'font-weight': '700' });
                } else if (estado === 'FUERA_DE_SERVICIO') {
                    estadoCell.css({ 'background-color': '#fdecec', 'color': '#b42318', 'font-weight': '700' });
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
        // Pasar callback para filtrar/iniciar gráfico después de que el contenido HTML
        // esté en el DOM, garantizando que los inputs de fecha existan al leer sus valores.
        cargarPopup('historial_kilometraje', idVehiculo, 'Historial de Kilometraje', function () {
            filtrarHistorialKm(idVehiculo);
        });
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

    // === CAMBIO 2: Historial de Estado ===

    /** Abre el popup de historial de estado desde la celda de estado general */
    function abrirHistorialEstado(idVehiculo) {
        cargarPopup('historial_estado', idVehiculo, 'Historial de Estado');
    }

    /** Re-filtra el historial de estado vía AJAX */
    function filtrarHistorialEstado(idVehiculo) {
        var desde = $('#filtroDesdeEstado').val();
        var hasta = $('#filtroHastaEstado').val();
        var tipo = $('#filtroTipoEstado').val();

        if (desde && hasta && desde > hasta) {
            mostrarError('La fecha "Desde" debe ser menor o igual a "Hasta"');
            return;
        }

        $.get(dirPage, {
            accion: 'get_historial_estado',
            id_vehiculo: idVehiculo,
            desde: desde,
            hasta: hasta,
            tipo: tipo
        })
        .done(function (data) {
            renderHistorialEstado(data);
        })
        .fail(function () {
            mostrarError('Error al cargar el historial de estado');
        });
    }

    /** Renderiza la tabla de historial de estado */
    function renderHistorialEstado(data) {
        var ultimaObs = data.ultima_obs;
        var eventos = data.eventos;
        var labelTipo = {
            'PREOPERACIONAL': 'Preoperacional',
            'MANTENIMIENTO': 'Mantenimiento',
            'TRASLADO_ADMIN': 'Traslado Admin',
            'POST_SINIESTRO': 'Post Siniestro',
            'REVISION_SST': 'Revisión SST',
            'OTRO': 'Otro'
        };

        // Actualizar card de última observación
        var cardHtml = '<div style="font-weight:700; font-size:14px; margin-bottom:10px; color:#0c4582;">📋 Última Observación</div>';
        if (ultimaObs) {
            var claseObs = getEstadoBadgeClass(ultimaObs.estado_general || 'OPTIMO');
            var fechaObs = formatFecha(ultimaObs.fecha_registro, true);
            var kmObs = formatKm(ultimaObs.kilometraje || 0);
            var obs = (ultimaObs.observaciones || 'Sin observaciones').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            cardHtml += '<div style="background:#fff; border-radius:8px; padding:14px;">' +
                '<div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:8px;">' +
                '<span class="' + claseObs + '" style="display:inline-block; border-radius:20px; padding:4px 12px; font-weight:600; font-size:12px;">' +
                escHtml(ultimaObs.estado_general || 'OPTIMO') + '</span>' +
                '<span style="font-size:12px; color:#888;">' + fechaObs + '</span></div>' +
                '<p style="margin:0 0 8px 0; color:#333; font-style:italic;">"' + obs.replace(/\n/g, '<br>') + '"</p>' +
                '<div style="font-size:12px; color:#888;">' +
                '<strong>Registrado por:</strong> ' + escHtml(ultimaObs.responsable_nombre || '—') + ' &nbsp;|&nbsp;' +
                '<strong>Conductor:</strong> ' + escHtml(ultimaObs.conductor_nombre || '—') + ' &nbsp;|&nbsp;' +
                '<strong>Tipo:</strong> ' + escHtml(labelTipo[ultimaObs.tipo_evento] || ultimaObs.tipo_evento) + ' &nbsp;|&nbsp;' +
                '<strong>Km:</strong> ' + kmObs + ' km</div></div>';
        } else {
            cardHtml += '<div style="background:#fff; border-radius:8px; padding:14px; color:#888; text-align:center;">Sin eventos no-preoperacionales registrados.</div>';
        }
        // Reemplazar solo el contenido de la card (primer div después del hr)
        $('#popupModalBody').find('div[style*="background:#f0f2f5"]').first().html(cardHtml);

        // Actualizar tabla
        if (!eventos || eventos.length === 0) {
            $('#historialEstadoTabla').html('<div class="alert alert-info">Sin eventos registrados en este período.</div>');
        } else {
            var tbody = '';
            for (var i = 0; i < eventos.length; i++) {
                var ev = eventos[i];
                var claseEv = getEstadoBadgeClass(ev.estado_general || 'OPTIMO');
                var tipoEv = labelTipo[ev.tipo_evento] || ev.tipo_evento;
                var obsEv = ev.observaciones || '';
                var obsCorto = obsEv.length > 100 ? obsEv.substring(0, 100) + '…' : obsEv;
                var kmEv = formatKm(ev.kilometraje || 0);
                var fechaEv = formatFecha(ev.fecha_registro, true);
                var conductorEv = ev.conductor_nombre || '—';
                var obsEscaped = escHtml(obsEv).replace(/\n/g, '\\n');

                var baseUrl = (window.APP_BASE_URL ? window.APP_BASE_URL + '/' : '');
                var fotoHtml = '—';
                if (ev.foto_evidencia && ev.img_kilometraje) {
                    fotoHtml = '<a href="' + baseUrl + escHtml(ev.foto_evidencia) + '" target="_blank" title="Foto evidencia">📷</a> ' +
                               '<a href="' + baseUrl + escHtml(ev.img_kilometraje) + '" target="_blank" title="Foto odómetro">🖼️</a>';
                } else if (ev.foto_evidencia) {
                    fotoHtml = '<a href="' + baseUrl + escHtml(ev.foto_evidencia) + '" target="_blank" title="Foto evidencia">📷</a>';
                } else if (ev.img_kilometraje) {
                    fotoHtml = '<a href="' + baseUrl + escHtml(ev.img_kilometraje) + '" target="_blank" title="Foto odómetro">🖼️</a>';
                }

                tbody += '<tr>' +
                    '<td>' + fechaEv + '</td>' +
                    '<td>' + escHtml(conductorEv) + '</td>' +
                    '<td><span class="badge bg-secondary">' + escHtml(tipoEv) + '</span></td>' +
                    '<td><span class="' + claseEv + '" style="display:inline-block; border-radius:20px; padding:2px 10px; font-weight:600; font-size:11px;">' + escHtml(ev.estado_general || 'OPTIMO') + '</span></td>' +
                    '<td style="max-width:250px;" title="' + escHtml(obsEv) + '">' + escHtml(obsCorto);
                if (obsEv.length > 100) {
                    tbody += '<br><small><a href="#" class="ver-mas-obs" data-obs="' + escAttr(obsEv) + '">Ver más</a></small>';
                }
                tbody += '</td><td class="text-center">' + kmEv + '</td><td class="text-center">' + fotoHtml + '</td></tr>';
            }
            $('#historialEstadoTabla').html(
                '<div class="table-responsive"><table class="table table-sm table-hover" style="font-size:12px;">' +
                '<thead><tr><th>Fecha</th><th>Conductor</th><th>Tipo</th><th>Estado</th><th>Observación</th><th>Km</th><th>Foto</th></tr></thead>' +
                '<tbody>' + tbody + '</tbody></table></div>'
            );
        }
    }

    // ==================== HELPERS DE FORMATEO ====================

    function formatKm(km) {
        var n = parseInt(km, 10) || 0;
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatFecha(fechaStr, conHora) {
        if (!fechaStr) return '—';
        var d = new Date(fechaStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return fechaStr;
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var yyyy = d.getFullYear();
        if (conHora) {
            var hh = String(d.getHours()).padStart(2, '0');
            var min = String(d.getMinutes()).padStart(2, '0');
            return dd + '-' + mm + '-' + yyyy + ' ' + hh + ':' + min;
        }
        return dd + '-' + mm + '-' + yyyy;
    }

    function getEstadoBadgeClass(estado) {
        if (estado === 'OPTIMO') return 'estado-optimo';
        if (estado === 'CON_NOVEDADES') return 'estado-novedades';
        if (estado === 'FUERA_DE_SERVICIO') return 'estado-fuera-servicio';
        return 'bg-secondary';
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/\n/g, '&#10;');
    }

    // === CAMBIO 3: Historial de Kilometraje con gráfico ===

    /** Re-filtra el historial de kilometraje y actualiza gráfico + tabla + resumen */
    function filtrarHistorialKm(idVehiculo) {
        var desde = $('#filtroDesdeKm').val();
        var hasta = $('#filtroHastaKm').val();
        var fuente = $('#filtroFuenteKm').val();

        if (desde && hasta && desde > hasta) {
            mostrarError('La fecha "Desde" debe ser menor o igual a "Hasta"');
            return;
        }

        $.get(dirPage, {
            accion: 'get_historial_km',
            id_vehiculo: idVehiculo,
            desde: desde || null,
            hasta: hasta || null,
            fuente: fuente || null
        })
        .done(function (data) {
            actualizarResumenKm(data.resumen);
            actualizarGraficoKm(data.grafico);
            actualizarTablaKm(data.historial);
        })
        .fail(function () {
            mostrarError('Error al cargar el historial de kilometraje');
        });
    }

    /** Actualiza las tarjetas de resumen */
    function actualizarResumenKm(resumen) {
        if (resumen.km_actual !== null && resumen.km_actual !== undefined) {
            $('#kmResumenActual').text(formatKm(resumen.km_actual));
        } else {
            $('#kmResumenActual').text('—');
        }
        if (resumen.km_recorridos !== null && resumen.km_recorridos !== undefined) {
            var signo = resumen.km_recorridos >= 0 ? '+' : '';
            $('#kmResumenRecorrido').text(signo + formatKm(resumen.km_recorridos));
        } else {
            $('#kmResumenRecorrido').text('—');
        }
        if (resumen.promedio_diario !== null && resumen.promedio_diario !== undefined) {
            $('#kmResumenPromedio').text(formatKm(resumen.promedio_diario));
        } else {
            $('#kmResumenPromedio').text('—');
        }
    }

    /** Crea o actualiza el gráfico de Chart.js */
    function actualizarGraficoKm(grafico) {
        // Destruir instancia previa si existe
        if (kmChartInstance) {
            kmChartInstance.destroy();
            kmChartInstance = null;
        }

        var canvas = document.getElementById('kmChart');
        if (!canvas) return;

        var ctx = canvas.getContext('2d');

        if (!grafico || !grafico.data || grafico.data.length === 0) {
            $('#kmChart').hide();
            $('#kmChartNoData').show();
            return;
        }

        $('#kmChart').show();
        $('#kmChartNoData').hide();

        if (grafico.data.length < 2) {
            // Mostrar aviso de datos insuficientes
            $('#kmChartNoData').text('Datos insuficientes para tendencia (1 solo registro).').show();
            $('#kmChart').hide();
            return;
        }

        kmChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: grafico.labels,
                datasets: [{
                    label: 'Kilometraje',
                    data: grafico.data,
                    borderColor: '#0c4582',
                    backgroundColor: 'rgba(12, 69, 130, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: grafico.colores,
                    pointBorderColor: grafico.colores,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, color: '#888' }
                    },
                    y: {
                        grid: { color: '#e6e9f0' },
                        ticks: {
                            font: { size: 10 },
                            color: '#888',
                            callback: function (value) {
                                return formatKm(value);
                            }
                        }
                    }
                }
            }
        });
    }

    /** Actualiza la tabla de historial */
    function actualizarTablaKm(historial) {
        if (!historial || historial.length === 0) {
            $('#kmHistorialTabla').html('<div class="alert alert-info">Sin registros de kilometraje en este período.</div>');
            return;
        }

        var tbody = '';
        for (var i = 0; i < historial.length; i++) {
            var h = historial[i];
            var verLink = '—';
            if (h.id_preoperacional && h.id_usuario) {
                var fechaDate = h.fecha_date || h.fecha.split(' ')[0];
                var verUrl = '../controller/PreoperacionalController.php' +
                    '?preoperacional=validarpreoperacional' +
                    '&idpre=' + encodeURIComponent(h.id_preoperacional) +
                    '&iduser=' + encodeURIComponent(h.id_usuario) +
                    '&fecha=' + encodeURIComponent(fechaDate) +
                    '&idvehiculo=' + encodeURIComponent(h.idvehiculo || '') +
                    '&param4=ingresado&param5=vista';
                verLink = '<a href="#" onclick="window.open(\'' + escAttr(verUrl) + '\', \'_blank\', \'width=800,height=600,scrollbars=yes\'); return false;" style="color:#0c4582;font-weight:600;">👁️ Ver</a>';
            }

            tbody += '<tr>' +
                '<td>' + formatFecha(h.fecha, true) + '</td>' +
                '<td><span class="badge bg-secondary">' + escHtml(h.fuente) + '</span></td>' +
                '<td><strong>' + formatKm(h.kilometraje || 0) + ' km</strong></td>' +
                '<td>' + escHtml(h.detalle || '') + '</td>' +
                '<td>' + escHtml(h.usuario_nombre || '—') + '</td>' +
                '<td>' + verLink + '</td>' +
                '</tr>';
        }

        $('#kmHistorialTabla').html(
            '<div class="table-responsive"><table class="table table-sm table-hover" style="font-size:12px;">' +
            '<thead><tr>' +
            '<th>Fecha</th><th>Fuente</th><th>Kilometraje</th><th>Detalle</th><th>Usuario</th><th>Acción</th>' +
            '</tr></thead>' +
            '<tbody>' + tbody + '</tbody></table></div>'
        );
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
        // Fecha cambia → recargar tabla
        $('#fecha_consulta').on('change', recargarTabla);

        // Sede cambia → actualizar select de conductores
        $('#sede').on('change', function () {
            cargarConductores('#conductor', $(this).val());
        });

        // Carga inicial de conductores
        cargarConductores('#conductor', $('#sede').val());

        // Click en celda de estado general → abre historial de estado
        $('#tablaVehiculos').on('click', 'td:nth-child(5)', function () {
            var data = tabla.row($(this).closest('tr')).data();
            if (data && data.idvehiculos) {
                abrirHistorialEstado(data.idvehiculos);
            }
        });

        // Click en "Ver más" de observaciones
        $(document).on('click', '.ver-mas-obs', function (e) {
            e.preventDefault();
            var obs = $(this).data('obs');
            Swal.fire({
                title: 'Observación',
                html: obs.replace(/&#10;/g, '<br>').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'"),
                icon: 'info'
            });
        });

        // Limpiar instancia de Chart.js al cerrar el modal
        $('#popupModal').on('hidden.bs.modal', function () {
            if (kmChartInstance) {
                kmChartInstance.destroy();
                kmChartInstance = null;
            }
            // Limpiar handlers de shown para evitar duplicados
            $('#popupModal').off('shown.bs.modal.kmChart');
        });

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
    window.abrirHistorialEstado = abrirHistorialEstado;
    window.filtrarHistorialEstado = filtrarHistorialEstado;
    window.filtrarHistorialKm = filtrarHistorialKm;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
