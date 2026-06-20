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

    /** Abre el popup de consulta SST */
    function abrirConsultaSST(idVehiculo) {
        var params = { accion: 'form_popup', tipo: 'consulta_sst' };
        if (idVehiculo) {
            params.id = idVehiculo;
        }
        $('#popupModal .modal-title').text('Consulta SST');
        $('#popupModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</div>');
        $('#popupModal').modal('show');

        $.get(dirPage, params)
            .done(function (html) {
                $('#popupModalBody').html(html);
                // Inicializar Select2 para conductor
                initSelect2($('#sstConductorSelect'), 'Todos los conductores');
                // Bindear eventos del popup
                initConsultaSST();
                // Cargar datos de la primera pestaña
                cargarResumenSemanal();
                // Marcar el modal como mas ancho para las tablas
                $('#popupModal .modal-dialog').addClass('modal-xl');
            })
            .fail(function () {
                $('#popupModalBody').html('<div class="alert alert-danger">Error al cargar el formulario.</div>');
            });
    }

    /** Carga el resumen semanal via AJAX */
    function cargarResumenSemanal() {
        var semanaInput = $('#sstSemanaInput').val(); // Formato: YYYY-Www
        var conductorId = $('#sstConductorSelect').val() || '';
        var fechaInicio, fechaFin;

        if (semanaInput) {
            // Convertir YYYY-Www a fecha lunes de esa semana
            var parts = semanaInput.split('-W');
            var year = parseInt(parts[0], 10);
            var week = parseInt(parts[1], 10);
            // Calcular el lunes de esa semana ISO
            var simple = new Date(year, 0, 1 + (week - 1) * 7);
            var dayOfWeek = simple.getDay();
            var ISOweekStart = simple;
            if (dayOfWeek <= 4) {
                ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
            } else {
                ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
            }
            fechaInicio = formatDateYMD(ISOweekStart);
            var fechaFinDate = new Date(ISOweekStart);
            fechaFinDate.setDate(fechaFinDate.getDate() + 6);
            fechaFin = formatDateYMD(fechaFinDate);
        } else {
            // Fallback: semana actual
            var now = new Date();
            var diaSem = now.getDay() || 7; // dom=7
            var lunes = new Date(now);
            lunes.setDate(now.getDate() - (diaSem - 1));
            fechaInicio = formatDateYMD(lunes);
            var domingo = new Date(lunes);
            domingo.setDate(lunes.getDate() + 6);
            fechaFin = formatDateYMD(domingo);
        }

        // Mostrar loading
        $('#sstResumenLoading').show();
        $('#sstResumenContent').hide();

        var sstEndpoint = (window.APP_BASE_URL || '') + '/controller/ReporteConductorSSTController.php';
        var params = {
            ajax: 'resumen_semanal',
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin
        };
        if (conductorId) {
            params.id_conductor = conductorId;
        }

        $.get(sstEndpoint, params)
            .done(function (resp) {
                if (!resp.success) {
                    $('#sstResumenLoading').html('<div class="alert alert-danger">' +
                        escHtml(resp.message || 'Error al cargar resumen') + '</div>');
                    return;
                }
                renderResumenSemanal(resp);
                $('#sstResumenLoading').hide();
                $('#sstResumenContent').show();
            })
            .fail(function () {
                $('#sstResumenLoading').html('<div class="alert alert-danger">Error de conexion al cargar resumen.</div>');
            });
    }

    /** Formatea un objeto Date a Y-m-d */
    function formatDateYMD(d) {
        var yyyy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd;
    }

    /** Renderiza la tabla de resumen semanal */
    function renderResumenSemanal(resp) {
        var data = resp.data || [];
        var stats = resp.estadisticas || {};
        var semana = resp.semana || {};

        // Actualizar titulo del modal
        var titulo = 'Consulta SST';
        if (semana.inicio && semana.fin) {
            titulo += ' - Semana del ' + formatFecha(semana.inicio) + ' al ' + formatFecha(semana.fin);
        }
        $('#popupModal .modal-title').text(titulo);

        // Contadores
        var statsHtml = '<div class="d-flex flex-wrap gap-3">';
        statsHtml += '<span class="badge fs-6" style="background-color:#e8f2ec;color:#1e7f4f;padding:8px 16px;">' +
            '<i class="fas fa-check-circle me-1"></i> Al dia: <strong>' + (stats.al_dia || 0) + '</strong></span>';
        statsHtml += '<span class="badge fs-6" style="background-color:#fff4e5;color:#b54708;padding:8px 16px;">' +
            '<i class="fas fa-exclamation-triangle me-1"></i> Incompletos: <strong>' + (stats.incompletos || 0) + '</strong></span>';
        statsHtml += '<span class="badge fs-6" style="background-color:#fdecec;color:#b42318;padding:8px 16px;">' +
            '<i class="fas fa-times-circle me-1"></i> Pendientes: <strong>' + (stats.pendientes || 0) + '</strong></span>';
        statsHtml += '<span class="badge fs-6" style="background-color:#dbeafe;color:#1e40af;padding:8px 16px;">' +
            '<i class="fas fa-users me-1"></i> Total: <strong>' + (resp.total || 0) + '</strong></span>';
        statsHtml += '</div>';
        $('#sstResumenStats').html(statsHtml);

        // Tabla
        if (!data.length) {
            $('#sstResumenTable').hide();
            $('#sstResumenEmpty').show();
            return;
        }
        $('#sstResumenTable').show();
        $('#sstResumenEmpty').hide();

        var tbody = '';
        for (var i = 0; i < data.length; i++) {
            var r = data[i];
            var accOk = parseInt(r.accidente_completado) === 1;
            var compOk = parseInt(r.comparendo_completado) === 1;
            var ambosOk = accOk && compOk;
            var ningunoOk = !accOk && !compOk;

            var estadoLabel, estadoColor, rowBg;
            if (ambosOk) {
                estadoLabel = 'Al dia';
                estadoColor = '#e8f2ec';
                rowBg = 'background-color:#f6fdf9;';
            } else if (ningunoOk) {
                estadoLabel = 'Pendiente';
                estadoColor = '#fdecec';
                rowBg = 'background-color:#fffafb;';
            } else {
                estadoLabel = 'Incompleto';
                estadoColor = '#fff4e5';
                rowBg = 'background-color:#fffefa;';
            }

            var accBadge = accOk
                ? '<span style="color:#1e7f4f;font-size:16px;">&#10004;</span>'
                : '<span style="color:#b42318;font-size:16px;">&#10060;</span>';
            var compBadge = compOk
                ? '<span style="color:#1e7f4f;font-size:16px;">&#10004;</span>'
                : '<span style="color:#b42318;font-size:16px;">&#10060;</span>';

            tbody += '<tr style="' + rowBg + 'cursor:pointer;" ' +
                'onclick="irAHistorialDesdeResumen(' + r.idusuarios + ',\'' +
                escAttr(r.usu_nombre) + '\')" ' +
                'title="Click para ver historial de ' + escAttr(r.usu_nombre) + '">' +
                '<td><strong>' + escHtml(r.usu_nombre) + '</strong></td>' +
                '<td><span style="font-family:monospace;background:#e9ecef;padding:2px 8px;border-radius:4px;">' +
                escHtml(r.veh_placa || '—') + '</span></td>' +
                '<td class="text-center">' + accBadge + '</td>' +
                '<td class="text-center">' + compBadge + '</td>' +
                '<td class="text-center"><span style="background-color:' + estadoColor +
                ';padding:4px 12px;border-radius:14px;font-weight:600;font-size:12px;">' +
                estadoLabel + '</span></td>' +
                '</tr>';
        }
        $('#sstResumenBody').html(tbody);
    }

    /** Navega de resumen semanal a historial con datos del conductor */
    function irAHistorialDesdeResumen(idConductor, nombre) {
        // Cambiar a pestaña historial
        $('#sstHistorialTab').tab('show');
        // Pre-seleccionar conductor y cargar
        $('#sstConductorSelect').val(idConductor).trigger('change');
        // Cargar historial
        cargarHistorialSST();
    }

    /** Carga el historial de reportes via AJAX */
    function cargarHistorialSST() {
        var semanaInput = $('#sstSemanaInput').val();
        var conductorId = $('#sstConductorSelect').val() || '';
        var tipo = $('#sstHistTipo').val() || '';
        var tipoEvento = $('#sstHistEvento').val() || '';
        var preVehiculoId = $('#sstPreVehiculoId').val() || '';

        // Calcular fechas desde el input week (mismo calculo que resumen)
        var fechaDesde, fechaHasta;
        if (semanaInput) {
            var parts = semanaInput.split('-W');
            var year = parseInt(parts[0], 10);
            var week = parseInt(parts[1], 10);
            var simple = new Date(year, 0, 1 + (week - 1) * 7);
            var dayOfWeek = simple.getDay();
            var ISOweekStart = simple;
            if (dayOfWeek <= 4) {
                ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
            } else {
                ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
            }
            fechaDesde = formatDateYMD(ISOweekStart);
            var fechaFinDate = new Date(ISOweekStart);
            fechaFinDate.setDate(fechaFinDate.getDate() + 6);
            fechaHasta = formatDateYMD(fechaFinDate);
        } else {
            // Si no hay semana, usar rango amplio (ultimos 90 dias)
            var hoy = new Date();
            fechaHasta = formatDateYMD(hoy);
            var hace90 = new Date(hoy);
            hace90.setDate(hace90.getDate() - 90);
            fechaDesde = formatDateYMD(hace90);
        }

        $('#sstHistorialLoading').show();
        $('#sstHistorialContent').hide();

        var sstEndpoint = (window.APP_BASE_URL || '') + '/controller/ReporteConductorSSTController.php';
        var params = {
            ajax: 'consulta',
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta
        };
        if (preVehiculoId) {
            params.id_vehiculo = preVehiculoId;
        }
        if (conductorId) {
            params.id_usuario = conductorId;
        }
        if (tipo) {
            params.tipo = tipo;
        }

        $.get(sstEndpoint, params)
            .done(function (resp) {
                if (!resp.success) {
                    $('#sstHistorialLoading').html('<div class="alert alert-danger">' +
                        escHtml(resp.message || 'Error al cargar historial') + '</div>');
                    return;
                }
                var datos = resp.data || [];
                // Filtrar por tipo_evento en cliente si es necesario
                if (tipoEvento) {
                    datos = datos.filter(function (r) { return r.tipo_evento === tipoEvento; });
                }
                renderHistorialSST(datos);
                $('#sstHistorialLoading').hide();
                $('#sstHistorialContent').show();
            })
            .fail(function () {
                $('#sstHistorialLoading').html('<div class="alert alert-danger">Error de conexion al cargar historial.</div>');
            });
    }

    /** Renderiza la tabla de historial de reportes */
    function renderHistorialSST(reportes) {
        if (!reportes || reportes.length === 0) {
            $('#sstHistorialTable').hide();
            $('#sstHistorialEmpty').show();
            return;
        }
        $('#sstHistorialTable').show();
        $('#sstHistorialEmpty').hide();

        // Mapeo de gravedad — unificado para ambos tipos
        var gravedadLabels = {
            1: { label: 'Leve / Normal', color: '#1e7f4f' },
            2: { label: 'Moderado / Media', color: '#b54708' },
            3: { label: 'Grave / Alta', color: '#b42318' },
            4: { label: 'Crítico', color: '#7f1d1d' }
        };
        var estadoBadge = {
            'pendiente': '<span style="background-color:#fff4e5;color:#b54708;padding:3px 10px;border-radius:12px;font-weight:600;font-size:11px;">⏳ Pendiente</span>',
            'revisado': '<span style="background-color:#e3f2fd;color:#0d47a1;padding:3px 10px;border-radius:12px;font-weight:600;font-size:11px;">✅ Revisado</span>',
            'resuelto': '<span style="background-color:#e8f5e9;color:#1b5e20;padding:3px 10px;border-radius:12px;font-weight:600;font-size:11px;">✔️ Resuelto</span>'
        };
        var tipoBadge = {
            'accidente': '<span style="background-color:#fdecec;color:#b42318;padding:3px 10px;border-radius:12px;font-weight:600;font-size:11px;">🚨 Accidente</span>',
            'comparendo': '<span style="background-color:#fff4e5;color:#b54708;padding:3px 10px;border-radius:12px;font-weight:600;font-size:11px;">📋 Comparendo</span>'
        };

        var baseUrl = (window.APP_BASE_URL || '');
        var tbody = '';
        for (var i = 0; i < reportes.length; i++) {
            var r = reportes[i];
            var gNum = parseInt(r.gravedad, 10);
            var grav = gravedadLabels[gNum] || { label: (r.gravedad || '—'), color: '#888' };
            var respuestaBadge = (r.respuesta === 'si')
                ? '<span style="color:#c62828;font-weight:700;">⚠️ Sí</span>'
                : '<span style="color:#2e7d32;">✅ No</span>';

            // Archivos — mostrar miniaturas para imágenes
            var archivosHtml = '—';
            if (r.archivos && r.archivos.length > 0) {
                archivosHtml = '<div style="display:flex;gap:4px;flex-wrap:wrap;">';
                for (var a = 0; a < Math.min(r.archivos.length, 4); a++) {
                    var arch = r.archivos[a];
                    var ruta = arch.doc_ruta || '';
                    var esImagen = /\.(jpg|jpeg|png|gif|webp)$/i.test(ruta);
                    var urlArchivo = baseUrl + '/' + ruta.replace(/^\/+/, '');
                    if (esImagen) {
                        archivosHtml += '<a href="' + escHtml(urlArchivo) + '" target="_blank" ' +
                            'title="' + escHtml(arch.doc_nombre || 'Imagen') + '">' +
                            '<img src="' + escHtml(urlArchivo) + '" ' +
                            'style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid #e0e0e0;" ' +
                            'loading="lazy"></a>';
                    } else {
                        archivosHtml += '<a href="' + escHtml(urlArchivo) + '" target="_blank" ' +
                            'title="' + escHtml(arch.doc_nombre || 'Archivo') + '" ' +
                            'style="display:inline-flex;align-items:center;justify-content:center;' +
                            'width:36px;height:36px;border-radius:6px;background:#f5f5f5;' +
                            'text-decoration:none;font-size:16px;">📄</a>';
                    }
                }
                if (r.archivos.length > 4) {
                    archivosHtml += '<span style="font-size:10px;color:#999;align-self:center;">+' + (r.archivos.length - 4) + '</span>';
                }
                archivosHtml += '</div>';
            }

            tbody += '<tr>' +
                '<td>' + formatFecha(r.fecha) + '</td>' +
                '<td>' + escHtml(r.usu_nombre || '—') + '</td>' +
                '<td><span style="font-family:monospace;font-size:11px;">' + escHtml(r.veh_placa || '—') + '</span></td>' +
                '<td>' + (tipoBadge[r.tipo] || escHtml(r.tipo)) + '</td>' +
                '<td>' + escHtml(r.tipo_evento === 'semanal' ? '📅 Semanal' : '⚡ Momento') + '</td>' +
                '<td class="text-center"><span style="color:' + grav.color + ';font-weight:600;font-size:11px;">' + grav.label + '</span></td>' +
                '<td class="text-center">' + respuestaBadge + '</td>' +
                '<td class="text-center">' + (estadoBadge[r.estado] || escHtml(r.estado)) + '</td>' +
                '<td class="text-center">' + archivosHtml + '</td>' +
                '<td class="text-center">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary sst-ver-detalle" ' +
                        'data-id="' + r.id + '" title="Ver detalle" ' +
                        'style="padding:2px 8px;font-size:11px;">' +
                        '<i class="fas fa-eye"></i> Ver' +
                    '</button>' +
                '</td>' +
                '</tr>';
        }
        $('#sstHistorialBody').html(tbody);

        // Bind click en botones "Ver"
        $('#sstHistorialBody').find('.sst-ver-detalle').off('click').on('click', function () {
            var idReporte = $(this).data('id');
            cargarDetalleSST(idReporte);
        });
    }

    /** Carga y muestra el popup de detalle de un reporte SST */
    function cargarDetalleSST(id) {
        var sstEndpoint = (window.APP_BASE_URL || '') + '/controller/ReporteConductorSSTController.php';

        // Mostrar loading
        Swal.fire({
            title: 'Cargando detalle...',
            html: '<div class="spinner-border text-primary" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: function () {
                Swal.showLoading();
            }
        });

        $.get(sstEndpoint, { ajax: 'detalle_vista', id: id })
            .done(function (resp) {
                if (!resp.success) {
                    Swal.fire({
                        title: 'Error',
                        text: resp.message || 'Error al cargar el detalle',
                        icon: 'error'
                    });
                    return;
                }

                var d = resp.data;
                var puedeValidar = resp.puede_validar;
                var usuarioNombre = (resp.usuario_actual && resp.usuario_actual.nombre) || '';

                var html = buildDetalleHTML(d, puedeValidar, usuarioNombre);

                Swal.fire({
                    title: 'Detalle del Reporte SST',
                    html: html,
                    width: '800px',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'sst-detalle-swal'
                    },
                    didOpen: function () {
                        $('#sstValidarBtn').on('click', function () {
                            validarReporteSST(id);
                        });
                    }
                });
            })
            .fail(function () {
                Swal.fire({
                    title: 'Error',
                    text: 'Error de conexión al cargar el detalle',
                    icon: 'error'
                });
            });
    }

    /** Construye el HTML del detalle del reporte */
    function buildDetalleHTML(d, puedeValidar, usuarioNombre) {
        var estado = d.estado || 'pendiente';
        var estadoClase = {
            'pendiente': 'sst-estado--pendiente',
            'revisado': 'sst-estado--revisado',
            'resuelto': 'sst-estado--resuelto'
        }[estado] || 'sst-estado--pendiente';
        var estadoLabel = {
            'pendiente': '⏳ Pendiente',
            'revisado': '✅ Revisado',
            'resuelto': '✔️ Resuelto'
        }[estado] || 'Pendiente';

        var gravLabels = {
            1: { text: 'Leve / Normal', color: '#1e7f4f' },
            2: { text: 'Moderado / Media', color: '#b54708' },
            3: { text: 'Grave / Alta', color: '#b42318' },
            4: { text: 'Crítico', color: '#7f1d1d' }
        };
        var gNum = parseInt(d.gravedad, 10);
        var grav = gravLabels[gNum] || { text: d.gravedad || '—', color: '#888' };

        var tipoBadge = d.tipo === 'accidente'
            ? '<span class="sst-badge sst-badge--accidente">🚨 Accidente</span>'
            : '<span class="sst-badge sst-badge--comparendo">📋 Comparendo</span>';

        var respuestaHtml = (d.respuesta === 'si')
            ? '<span style="color:#c62828;font-weight:700;">⚠️ Sí</span>'
            : '<span style="color:#2e7d32;">✅ No</span>';

        var baseUrl = (window.APP_BASE_URL || '');

        // Archivos
        var archivos = d.archivos || [];
        var archivosHtml = '';
        if (archivos.length === 0) {
            archivosHtml = '<p class="text-muted small">Sin archivos adjuntos.</p>';
        } else {
            archivosHtml = '<div class="sst-evidencia-grid">';
            for (var i = 0; i < archivos.length; i++) {
                var a = archivos[i];
                var ruta = a.doc_ruta || '';
                var nombre = a.doc_nombre || 'Archivo';
                var esImagen = /\.(jpg|jpeg|png|gif|webp)$/i.test(ruta);
                var esPDF = /\.pdf$/i.test(ruta);
                var urlArchivo = baseUrl + '/' + ruta.replace(/^\/+/, '');
                if (esImagen) {
                    archivosHtml += '<div class="sst-evidencia-item">' +
                        '<a href="' + escHtml(urlArchivo) + '" target="_blank" class="sst-evidencia-link" title="' + escHtml(nombre) + '">' +
                        '<img src="' + escHtml(urlArchivo) + '" alt="' + escHtml(nombre) + '" class="sst-evidencia-thumb" loading="lazy">' +
                        '</a></div>';
                } else if (esPDF) {
                    archivosHtml += '<div class="sst-evidencia-item">' +
                        '<a href="' + escHtml(urlArchivo) + '" target="_blank" class="sst-evidencia-link sst-evidencia-link--pdf" title="' + escHtml(nombre) + '">' +
                        '<div class="sst-evidencia-pdf-icon">📄</div><span class="sst-evidencia-pdf-label">PDF</span>' +
                        '</a></div>';
                } else {
                    archivosHtml += '<div class="sst-evidencia-item">' +
                        '<a href="' + escHtml(urlArchivo) + '" target="_blank" class="sst-evidencia-link sst-evidencia-link--generic" title="' + escHtml(nombre) + '">' +
                        '<div class="sst-evidencia-generic-icon">📎</div>' +
                        '</a></div>';
                }
            }
            archivosHtml += '</div>';
        }

        // Historial de validación
        var validacionHtml = '';
        if (d.comentario_validador || d.validador_nombre) {
            validacionHtml = '<div class="sst-detalle-section">' +
                '<h4 class="sst-section-title"><i class="fas fa-history"></i> Historial de Validación</h4>' +
                '<div class="sst-validacion-historial">' +
                '<div class="sst-validacion-entry">' +
                '<div class="sst-validacion-header">' +
                '<span class="sst-validacion-user"><i class="fas fa-user-check"></i> ' + escHtml(d.validador_nombre || 'Sistema') + '</span>';
            if (d.fecha_validacion) {
                validacionHtml += '<span class="sst-validacion-fecha"><i class="far fa-clock"></i> ' + escHtml(d.fecha_validacion) + '</span>';
            }
            validacionHtml += '</div>';
            if (d.comentario_validador && d.comentario_validador.trim()) {
                validacionHtml += '<div class="sst-validacion-comentario">' + escHtml(d.comentario_validador).replace(/\n/g, '<br>') + '</div>';
            }
            validacionHtml += '</div></div></div>';
        }

        // Panel de validación
        var panelValidacionHtml = '';
        if (puedeValidar) {
            var selPendiente = estado === 'pendiente' ? ' selected' : '';
            var selRevisado = estado === 'revisado' ? ' selected' : '';
            var selResuelto = estado === 'resuelto' ? ' selected' : '';
            panelValidacionHtml = '<div class="sst-detalle-section sst-validacion-panel">' +
                '<h4 class="sst-section-title"><i class="fas fa-check-double"></i> Validar Reporte</h4>' +
                '<div class="sst-validacion-form">' +
                '<div class="sst-form-group">' +
                '<label class="sst-form-label">Cambiar estado a:</label>' +
                '<select id="sstValidarEstado" class="sst-form-select">' +
                '<option value="pendiente"' + selPendiente + '>⏳ Pendiente</option>' +
                '<option value="revisado"' + selRevisado + '>✅ Revisado</option>' +
                '<option value="resuelto"' + selResuelto + '>✔️ Resuelto</option>' +
                '</select></div>' +
                '<div class="sst-form-group">' +
                '<label class="sst-form-label">Comentario de validación: ' +
                '<small class="text-muted">(Tu nombre <strong>' + escHtml(usuarioNombre) + '</strong> se añadirá automáticamente)</small></label>' +
                '<textarea id="sstValidarComentario" class="sst-form-textarea" rows="3" ' +
                'placeholder="Describa el resultado de la revisión..."></textarea></div>' +
                '<button type="button" id="sstValidarBtn" class="sst-btn-validar">' +
                '<i class="fas fa-save"></i> Guardar Validación</button>' +
                '</div></div>';
        }

        // Ensamblar HTML completo
        return '<div class="sst-detalle-container">' +
            '<div class="sst-detalle-header">' +
            '<div class="sst-detalle-tipo">' + tipoBadge +
            '<span class="sst-detalle-fecha"><i class="far fa-calendar-alt"></i> ' + formatFecha(d.fecha) + '</span></div>' +
            '<div class="sst-detalle-estado"><span class="sst-estado ' + estadoClase + '">' + estadoLabel + '</span></div>' +
            '</div>' +

            '<div class="sst-detalle-section">' +
            '<h4 class="sst-section-title"><i class="fas fa-user"></i> Conductor</h4>' +
            '<div class="sst-info-grid">' +
            '<div class="sst-info-item"><span class="sst-info-label">Nombre</span><span class="sst-info-value">' + escHtml(d.conductor_nombre || '—') + '</span></div>' +
            '<div class="sst-info-item"><span class="sst-info-label">Cédula</span><span class="sst-info-value">' + escHtml(d.conductor_cedula || '—') + '</span></div>' +
            '<div class="sst-info-item"><span class="sst-info-label">Vehículo</span><span class="sst-info-value sst-placa-badge">' + escHtml(d.veh_placa || '—') + '</span></div>' +
            '</div></div>' +

            '<div class="sst-detalle-section">' +
            '<h4 class="sst-section-title"><i class="fas fa-clipboard-list"></i> Detalle del Reporte</h4>' +
            '<div class="sst-info-grid sst-info-grid--4col">' +
            '<div class="sst-info-item"><span class="sst-info-label">Tipo de Evento</span><span class="sst-info-value">' + (d.tipo_evento === 'semanal' ? '📅 Semanal' : '⚡ En el momento') + '</span></div>' +
            '<div class="sst-info-item"><span class="sst-info-label">¿Ocurrió?</span><span class="sst-info-value">' + respuestaHtml + '</span></div>' +
            '<div class="sst-info-item"><span class="sst-info-label">Gravedad</span><span class="sst-info-value"><span style="color:' + grav.color + ';font-weight:600;">' + grav.text + '</span></span></div>' +
            '<div class="sst-info-item"><span class="sst-info-label">Ubicación GPS</span><span class="sst-info-value" style="font-family:monospace;font-size:12px;">' + escHtml(d.ubicacion || '—') + '</span></div>' +
            '</div></div>' +

            (d.observacion && d.observacion.trim() ?
            '<div class="sst-detalle-section">' +
            '<h4 class="sst-section-title"><i class="fas fa-comment-alt"></i> Observación del Conductor</h4>' +
            '<div class="sst-observacion-box">' + escHtml(d.observacion).replace(/\n/g, '<br>') + '</div></div>' : '') +

            '<div class="sst-detalle-section">' +
            '<h4 class="sst-section-title"><i class="fas fa-paperclip"></i> Evidencia (' + archivos.length + ' archivo' + (archivos.length !== 1 ? 's' : '') + ')</h4>' +
            archivosHtml + '</div>' +

            validacionHtml +
            panelValidacionHtml +
            '</div>';
    }

    /** Envía la validación de un reporte SST */
    function validarReporteSST(id) {
        var sstEndpoint = (window.APP_BASE_URL || '') + '/controller/ReporteConductorSSTController.php';
        var estado = $('#sstValidarEstado').val();
        var comentario = $('#sstValidarComentario').val().trim();

        if (!estado) {
            Swal.fire({ title: 'Atención', text: 'Seleccione un estado', icon: 'warning' });
            return;
        }

        var btn = $('#sstValidarBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.post(sstEndpoint, {
            accion: 'validar',
            id: id,
            estado: estado,
            comentario: comentario
        })
        .done(function (resp) {
            if (!resp.success) {
                Swal.fire({ title: 'Error', text: resp.message || 'Error al validar', icon: 'error' });
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Validación');
                return;
            }
            Swal.fire({
                title: 'Validación guardada',
                text: 'El reporte ha sido actualizado correctamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            setTimeout(function () {
                Swal.close();
                cargarHistorialSST();
            }, 1500);
        })
        .fail(function () {
            Swal.fire({ title: 'Error', text: 'Error de conexión al validar', icon: 'error' });
            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Validación');
        });
    }

    /** Inicializa el popup de consulta SST despues de cargar el HTML */
    function initConsultaSST() {
        // Boton Buscar en la barra principal
        $('#sstBuscarBtn').off('click').on('click', function () {
            cargarResumenSemanal();
            cargarHistorialSST();
        });

        // Boton Filtrar en la pestaña historial
        $('#sstHistBuscarBtn').off('click').on('click', function () {
            cargarHistorialSST();
        });

        // Cambio de semana recarga resumen
        $('#sstSemanaInput').off('change').on('change', function () {
            cargarResumenSemanal();
        });

        // Cambio de pestaña: cargar historial si se navega a esa pestaña
        $('#sstHistorialTab').off('shown.bs.tab').on('shown.bs.tab', function () {
            cargarHistorialSST();
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
                    // Si la búsqueda nativa de DataTable tiene valor, usarla; si no, usar el input personalizado
                    if (!d.search.value) {
                        d.search.value = $('#search_placa').val();
                    }
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
                { data: 'conductor_html' },
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
        // Sincronizar el input de búsqueda personalizado al buscador nativo de DataTable
        tabla.search($('#search_placa').val());
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

        // Click en placa → abre consulta SST para ese vehiculo
        $('#tablaVehiculos').on('click', 'td:nth-child(2)', function () {
            var data = tabla.row($(this).closest('tr')).data();
            if (data && data.idvehiculos) {
                abrirConsultaSST(data.idvehiculos);
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
            // Restaurar tamanio del modal
            $('#popupModal .modal-dialog').removeClass('modal-xl');
        });

        // Click en badge de vehículo duplicado → popup con información completa
        $(document).on('click', '.badge-duplicado', function (e) {
            e.stopPropagation();
            var $badge = $(this);
            var vehiculoId = $badge.data('vehiculo-id');
            var vehiculoPlaca = $badge.data('vehiculo-placa');
            var duplicadoTotal = $badge.data('duplicado-total');
            var duplicadoActivos = $badge.data('duplicado-activos') || 0;
            var duplicadoInactivos = $badge.data('duplicado-inactivos') || 0;
            var duplicadoInfo = $badge.data('duplicado-info');

            // Construir lista HTML de usuarios (solo activos)
            var usuariosHtml = '';
            if (Array.isArray(duplicadoInfo)) {
                duplicadoInfo.forEach(function (u) {
                    var esActivo = u.indexOf('(Activo)') !== -1;
                    if (!esActivo) return;
                    usuariosHtml += '<li style="padding:6px 0; color:#1e7f4f; font-size:14px; border-bottom:1px solid #f3f4f6;">'
                        + '🟢 ' + escHtml(u) + '</li>';
                });
            }

            // Mensaje principal: informar a todos los usuarios activos
            var mensajeInactivos = '';
            if (duplicadoInactivos > 0) {
                mensajeInactivos = '<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:12px 16px; margin-bottom:16px;">'
                    + '<i class="fas fa-exclamation-triangle" style="color:#d97706;"></i> '
                    + '<strong style="color:#92400e;">Hay ' + duplicadoInactivos + ' conductor(es) inactivo(s) que aún tienen asignado el vehículo</strong>'
                    + '</div>';
            }

            // Resumen de la situación
            var resumenHtml = '<div style="display:flex; gap:16px; margin-bottom:12px; text-align:center;">'
                + '<div style="flex:1; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:10px;">'
                + '<div style="font-size:20px; font-weight:700; color:#1e7f4f;">' + duplicadoActivos + '</div>'
                + '<div style="font-size:12px; color:#555;">Conductor(es) activo(s)</div></div>'
                + '<div style="flex:1; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:10px;">'
                + '<div style="font-size:20px; font-weight:700; color:#6b7280;">' + duplicadoInactivos + '</div>'
                + '<div style="font-size:12px; color:#555;">Inactivo(s)</div></div>'
                + '</div>';

            Swal.fire({
                title: '<i class="fas fa-exclamation-triangle" style="color:#d97706;"></i> Vehículo con Múltiples Registros',
                html: '<div style="text-align:left; font-size:14px;">'
                    + '<p style="margin-bottom:12px;"><strong>Placa:</strong> ' + escHtml(vehiculoPlaca) + '</p>'
                    + mensajeInactivos
                    + '<p style="margin-bottom:8px;"><strong>Este vehículo aparece registrado a <span style="color:#d97706;">' + duplicadoTotal + ' usuarios</span> diferentes:</strong></p>'
                    + resumenHtml
                    + '<ul style="list-style:none; margin:0; padding:8px 12px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px;">'
                    + usuariosHtml
                    + '</ul>'
                    + '<p style="margin-top:12px; color:#0c4582; font-size:13px;">'
                    + '<i class="fas fa-bullhorn"></i> <strong>Se debe informar a todos los usuarios activos</strong> sobre esta situación para coordinar la depuración de la asignación del vehículo.</p>'
                    + '</div>',
                icon: 'warning',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d97706',
                customClass: {
                    popup: 'text-left'
                }
            });
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
    window.abrirConsultaSST = abrirConsultaSST;
    window.initConsultaSST = initConsultaSST;
    window.cargarResumenSemanal = cargarResumenSemanal;
    window.cargarHistorialSST = cargarHistorialSST;
    window.cargarDetalleSST = cargarDetalleSST;
    window.validarReporteSST = validarReporteSST;
    window.irAHistorialDesdeResumen = irAHistorialDesdeResumen;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
