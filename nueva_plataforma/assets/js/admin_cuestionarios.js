/**
 * AdminCuestionarios - JS de administracion de cuestionarios
 *
 * Maneja la carga de plantillas, versiones, secciones y preguntas
 * mediante peticiones AJAX al controlador.
 */
(function($) {
    'use strict';

    let moduloActual = 'preop';

    // ==================== INICIALIZACION ====================

    function init() {
        moduloActual = $('#cuestionariosTabs .nav-link.active').data('modulo') || 'preop';
        cargarPlantillas(moduloActual);

        $('#cuestionariosTabs .nav-link').on('shown.bs.tab', function() {
            moduloActual = $(this).data('modulo');
            cargarPlantillas(moduloActual);
        });
    }

    // ==================== PLANTILLAS ====================

    function cargarPlantillas(modulo) {
        var $list = $('#plantillas-list-' + modulo);
        $list.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>');

        $.post('', { accion: 'listar_plantillas', slug_modulo: modulo }, function(res) {
            if (!res.success || !res.data.length) {
                $list.html('<div class="alert alert-info text-center">No hay plantillas. Crea una nueva.</div>');
                return;
            }
            var html = '<div class="list-group">';
            res.data.forEach(function(p) {
                var creador = p.creador_nombre ? ' por ' + escapeHtml(p.creador_nombre) : '';
                var fecha = p.fecha_creacion ? ' ' + p.fecha_creacion.substring(0, 10) : '';
                var bloqueada = parseInt(p.versiones_bloqueadas || 0) > 0;
                var btnClass = bloqueada ? 'btn-outline-secondary disabled' : 'btn-outline-primary';
                var trashClass = bloqueada ? 'btn-outline-secondary disabled' : 'btn-outline-danger';
                var titleBloq = bloqueada ? ' title="🔒 Tiene versiones ACTIVAS/HISTORICAS"' : '';
                html += '<div class="list-group-item list-group-item-action plantilla-item' + (bloqueada ? ' text-muted' : '') + '" data-id="' + p.id_plantilla + '">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                        '<div><strong>' + escapeHtml(p.nombre_base) + '</strong>' +
                        ' <span class="badge bg-' + (p.estado ? 'success' : 'secondary') + ' ms-1">' +
                        (p.estado ? 'Activo' : 'Inactivo') + '</span>' +
                        (bloqueada ? ' <span class="badge bg-secondary">🔒</span>' : '') +
                        ' <small class="text-muted">Creado' + fecha + creador + '</small></div>' +
                        '<div>' +
                        '<button class="btn btn-sm ' + btnClass + ' btn-editar-plantilla me-1" data-id="' + p.id_plantilla + '"' + titleBloq + '><i class="fas fa-edit"></i></button>' +
                        '<button class="btn btn-sm ' + trashClass + ' btn-eliminar-plantilla" data-id="' + p.id_plantilla + '"' + titleBloq + '><i class="fas fa-trash"></i></button>' +
                        '</div></div>' +
                        '<div class="mt-2 pequenas-acciones">' +
                        '<button class="btn btn-sm btn-link btn-ver-versiones" data-id="' + p.id_plantilla + '"><i class="fas fa-code-branch"></i> Versiones</button>' +
                        '</div>' +
                        '<div class="versiones-container mt-2" id="versiones-' + p.id_plantilla + '" style="display:none;"></div>' +
                        '</div>';
            });
            html += '</div>';
            $list.html(html);
        }, 'json');
    }

    // ==================== VERSIONES ====================

    $(document).on('click', '.btn-ver-versiones', function() {
        var id = $(this).data('id');
        var $container = $('#versiones-' + id);
        var $btn = $(this);

        if ($container.is(':visible')) {
            $container.slideUp();
            $btn.html('<i class="fas fa-code-branch"></i> Versiones');
            return;
        }

        $container.slideDown();
        $btn.html('<i class="fas fa-code-branch"></i> Ocultar versiones');
        $container.html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm"></div></div>');

        $.post('', { accion: 'listar_versiones', id_plantilla: id }, function(res) {
            if (!res.success) {
                $container.html('<div class="alert alert-warning">Error al cargar versiones</div>');
                return;
            }
            var html = '<div class="ms-3 border-start ps-3">';
            res.data.forEach(function(v) {
                var badge = v.estado === 'ACTIVA' ? 'success' : (v.estado === 'HISTORICA' ? 'secondary' : 'warning');
                var creador = v.creador_nombre ? ' por ' + escapeHtml(v.creador_nombre) : '';
                var notas = v.notas_cambio ? '<br><small class="text-muted"><i class="fas fa-comment"></i> ' + escapeHtml(v.notas_cambio) + '</small>' : '';
                html += '<div class="mb-2 p-2 bg-light rounded version-item" data-id="' + v.id_version + '">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                        '<span><strong>' + escapeHtml(v.numero_version) + '</strong> ' +
                        '<span class="badge bg-' + badge + '">' + v.estado + '</span>' +
                        ' <small class="text-muted">' + (v.fecha_creacion ? v.fecha_creacion.substring(0, 10) : '') + creador + '</small></span>' +
                        '<div class="btn-group btn-group-sm">' +
                        (v.estado !== 'ACTIVA' ? '<button class="btn btn-outline-success btn-activar-version" data-id="' + v.id_version + '">Activar</button>' : '') +
                        '<button class="btn btn-outline-info btn-sm btn-preview-version" data-id="' + v.id_version + '" title="Vista previa"><i class="fas fa-eye"></i></button>' +
                        '</div></div>' +
                        notas +
                        '<div class="mt-1">' +
                        '<button class="btn btn-sm btn-link btn-ver-secciones" data-id="' + v.id_version + '"><i class="fas fa-folder"></i> Secciones</button>' +
                        '</div>' +
                        '<div class="secciones-container mt-1" id="secciones-' + v.id_version + '" style="display:none;"></div>' +
                        '</div>';
            });
            html += '<button class="btn btn-sm btn-outline-success btn-nueva-version mt-1" data-plantilla="' + id + '"><i class="fas fa-plus"></i> Nueva Version</button>';
            html += '</div>';
            $container.html(html);
        }, 'json');
    });

    // ==================== SECCIONES ====================

    $(document).on('click', '.btn-ver-secciones', function() {
        var id = $(this).data('id');
        var $container = $('#secciones-' + id);
        var $btn = $(this);
        var isVisible = $container.is(':visible');

        $container.slideToggle();
        $btn.html(isVisible ? '<i class="fas fa-folder"></i> Secciones' : '<i class="fas fa-folder-open"></i> Secciones');

        if (!isVisible) {
            $container.html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm"></div></div>');
            $.post('', { accion: 'listar_secciones', id_version: id }, function(res) {
                if (!res.success || !res.data.length) {
                    $container.html('<div class="text-muted small ps-2">Sin secciones. <button class="btn btn-sm btn-link btn-nueva-seccion" data-version="' + id + '">Crear primera</button></div>');
                    return;
                }
                var html = '<div class="ms-3 border-start ps-3">';
                res.data.forEach(function(s) {
                    var editable = s.version_estado === 'BORRADOR';
                    var badgeClass = s.version_estado === 'ACTIVA' ? 'success' : (s.version_estado === 'HISTORICA' ? 'secondary' : 'warning');
                    html += '<div class="mb-2 p-2 bg-white rounded section-item" data-id="' + s.id_seccion + '">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                            '<span><strong>' + escapeHtml(s.nombre) + '</strong> ' +
                            '<span class="badge bg-' + badgeClass + ' mockup-badge">' + (s.version_estado || '') + '</span>' +
                            ' <small class="text-muted">(orden: ' + s.orden + ')</small></span>' +
                            '<div class="btn-group btn-group-sm">' +
                            (!editable ? '<button class="btn btn-outline-secondary btn-sm disabled" title="🔒 Version ' + s.version_estado + '"><i class="fas fa-edit"></i></button>' :
                             '<button class="btn btn-outline-primary btn-sm btn-editar-seccion" data-id="' + s.id_seccion + '"><i class="fas fa-edit"></i></button>') +
                            (!editable ? '<button class="btn btn-outline-secondary btn-sm disabled" title="🔒 Version ' + s.version_estado + '"><i class="fas fa-trash"></i></button>' :
                             '<button class="btn btn-outline-danger btn-sm btn-eliminar-seccion" data-id="' + s.id_seccion + '"><i class="fas fa-trash"></i></button>') +
                            '<button class="btn btn-outline-info btn-sm btn-preview-seccion" data-id="' + s.id_seccion + '" title="Vista previa"><i class="fas fa-eye"></i></button>' +
                            '</div></div>' +
                            '<div class="mt-1">' +
                            '<button class="btn btn-sm btn-link btn-ver-preguntas" data-id="' + s.id_seccion + '"><i class="fas fa-list"></i> Preguntas</button>' +
                            '</div>' +
                            '<div class="preguntas-container mt-1" id="preguntas-' + s.id_seccion + '" style="display:none;"></div>' +
                            '</div>';
                });
                var borrador = res.data.length > 0 ? (res.data[0].version_estado === 'BORRADOR') : false;
                html += (borrador ? '<button class="btn btn-sm btn-outline-success btn-nueva-seccion mt-1" data-version="' + id + '"><i class="fas fa-plus"></i> Nueva Seccion</button>' :
                         '<button class="btn btn-sm btn-outline-secondary mt-1 disabled" disabled><i class="fas fa-plus"></i> Nueva Seccion</button>');
                html += '</div>';
                $container.html(html);
            }, 'json');
        }
    });

    // ==================== PREGUNTAS ====================

    $(document).on('click', '.btn-ver-preguntas', function() {
        var id = $(this).data('id');
        var $container = $('#preguntas-' + id);
        var $btn = $(this);
        var isVisible = $container.is(':visible');

        $container.slideToggle();
        $btn.html(isVisible ? '<i class="fas fa-list"></i> Preguntas' : '<i class="fas fa-list"></i> Preguntas');

        if (!isVisible) {
            $container.html('<div class="text-center py-2"><div class="spinner-border spinner-border-sm"></div></div>');
            $.post('', { accion: 'listar_preguntas', id_seccion: id }, function(res) {
                if (!res.success || !res.data.length) {
                    $container.html('<div class="text-muted small ps-2">Sin preguntas. <button class="btn btn-sm btn-link btn-nueva-pregunta" data-seccion="' + id + '">Crear primera</button></div>');
                    return;
                }
                var html = '<div class="ms-3 border-start ps-3 small">';
                var editable = res.data.length > 0 && res.data[0].version_estado === 'BORRADOR';
                res.data.forEach(function(p, idx) {
                    var tipoBadge = p.tipo_respuesta === 'SI_NO' ? 'primary' : (p.tipo_respuesta === 'SI_NO_NA' ? 'info' : 'secondary');
                    html += '<div class="mb-1 p-1 bg-white rounded d-flex justify-content-between align-items-center">' +
                            '<div><span class="text-muted">' + (idx + 1) + '.</span> ' +
                            '<code>' + escapeHtml(p.codigo_interno) + '</code> ' +
                            '<span>' + escapeHtml(p.texto_pregunta).substring(0, 80) + '</span> ' +
                            '<span class="badge bg-' + tipoBadge + '">' + p.tipo_respuesta + '</span>' +
                            (p.requiere_foto_si_negativa ? ' <span class="badge bg-warning text-dark">📷</span>' : '') +
                            (p.genera_bloqueo ? ' <span class="badge bg-danger">🚫</span>' : '') +
                            (p.id_pregunta_padre ? ' <span class="badge bg-success">🔀</span>' : '') +
                            '</div>' +
                            '<div class="btn-group btn-group-sm">' +
                            (!editable ? '<button class="btn btn-outline-secondary btn-sm disabled" title="🔒 Version ' + p.version_estado + '"><i class="fas fa-edit"></i></button>' :
                             '<button class="btn btn-outline-primary btn-sm btn-editar-pregunta" data-id="' + p.id_pregunta + '"><i class="fas fa-edit"></i></button>') +
                            (!editable ? '<button class="btn btn-outline-secondary btn-sm disabled" title="🔒 Version ' + p.version_estado + '"><i class="fas fa-trash"></i></button>' :
                             '<button class="btn btn-outline-danger btn-sm btn-eliminar-pregunta" data-id="' + p.id_pregunta + '"><i class="fas fa-trash"></i></button>') +
                            '</div></div>';
                });
                html += (editable ? '<button class="btn btn-sm btn-outline-success btn-nueva-pregunta mt-1" data-seccion="' + id + '"><i class="fas fa-plus"></i> Nueva Pregunta</button>' : '');
                html += '</div>';
                $container.html(html);
            }, 'json');
        }
    });

    // ==================== CRUD: PLANTILLAS ====================

    $(document).on('click', '.btn-nueva-plantilla', function() {
        var modulo = $(this).data('modulo');
        $.get('', { modo: modulo, popup: 'plantilla' }, function(html) {
            $('#popup-plantilla-container').html(html);
            $('#modalPlantilla').modal('show');
        });
    });

    $(document).on('click', '.btn-editar-plantilla:not(.disabled)', function() {
        var id = $(this).data('id');
        $.post('', { accion: 'listar_plantillas' }, function(res) {
            if (res.success) {
                var data = res.data.find(function(p) { return p.id_plantilla == id; });
                if (data) {
                    $.get('', { modo: data.slug_modulo, popup: 'plantilla', id_plantilla: data.id_plantilla }, function(html) {
                        $('#popup-plantilla-container').html(html);
                        var form = $('#formPlantilla');
                        form.find('[name="id_plantilla"]').val(data.id_plantilla);
                        form.find('[name="nombre_base"]').val(data.nombre_base);
                        form.find('[name="tipo_destinatario"]').val(data.tipo_destinatario);
                        form.find('[name="aplica_a_roles"]').val(data.aplica_a_roles || '');
                        form.find('[name="aplica_a_tipo_vehiculo"]').val(data.aplica_a_tipo_vehiculo || '');
                        if (data.es_default) form.find('[name="es_default"]').prop('checked', true);
                        $('#modalPlantilla').modal('show');
                    });
                }
            }
        }, 'json');
    });

    $(document).on('click', '#btnGuardarPlantilla', function() {
        var data = $('#formPlantilla').serializeArray();
        data.push({ name: 'accion', value: 'guardar_plantilla' });
        $.post('', data, function(res) {
            if (res.success) {
                $('#modalPlantilla').modal('hide');
                cargarPlantillas(moduloActual);
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-eliminar-plantilla:not(.disabled)', function() {
        if (!confirm('Eliminar esta plantilla?')) return;
        var id = $(this).data('id');
        $.post('', { accion: 'eliminar_plantilla', id_plantilla: id }, function(res) {
            if (res.success) cargarPlantillas(moduloActual);
            else alert('Error: ' + res.message);
        }, 'json');
    });

    // ==================== CRUD: VERSIONES ====================

    $(document).on('click', '.btn-nueva-version', function() {
        var idPlantilla = $(this).data('plantilla');
        $.get('', { popup: 'version', id_plantilla: idPlantilla }, function(html) {
            $('#popup-version-container').html(html);
            $('#modalVersion').modal('show');
        });
    });

    $(document).on('click', '#btnGuardarVersion', function() {
        var data = $('#formVersion').serializeArray();
        data.push({ name: 'accion', value: 'guardar_version' });
        $.post('', data, function(res) {
            if (res.success) {
                $('#modalVersion').modal('hide');
                var idPlantilla = $('#formVersion [name="id_plantilla"]').val();
                $('.btn-ver-versiones[data-id="' + idPlantilla + '"]').click();
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-activar-version', function() {
        if (!confirm('Activar esta version? La actual pasara a historica.')) return;
        var id = $(this).data('id');
        $.post('', { accion: 'activar_version', id_version: id }, function(res) {
            if (res.success) location.reload();
            else alert('Error: ' + res.message);
        }, 'json');
    });

    // ==================== CRUD: SECCIONES ====================

    $(document).on('click', '.btn-nueva-seccion', function() {
        var idVersion = $(this).data('version');
        $.get('', { popup: 'seccion', id_version: idVersion }, function(html) {
            $('#popup-seccion-container').html(html);
            $('#modalSeccion').modal('show');
        });
    });

    $(document).on('click', '.btn-editar-seccion:not(.disabled)', function() {
        var id = $(this).data('id');
        $.get('', { popup: 'seccion', id_seccion: id }, function(html) {
            $('#popup-seccion-container').html(html);
            $.post('', { accion: 'listar_secciones', id_version: 0 }, function() {}, 'json');
            $('#modalSeccion').modal('show');
        });
    });

    $(document).on('click', '#btnGuardarSeccion', function() {
        var data = $('#formSeccion').serializeArray();
        data.push({ name: 'accion', value: 'guardar_seccion' });
        $.post('', data, function(res) {
            if (res.success) {
                $('#modalSeccion').modal('hide');
                var idVersion = $('#formSeccion [name="id_version"]').val();
                $('.btn-ver-secciones[data-id="' + idVersion + '"]').click();
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-eliminar-seccion:not(.disabled)', function() {
        if (!confirm('Eliminar esta seccion y todas sus preguntas?')) return;
        var id = $(this).data('id');
        $.post('', { accion: 'eliminar_seccion', id_seccion: id }, function(res) {
            if (res.success) location.reload();
            else alert('Error: ' + res.message);
        }, 'json');
    });

    // ==================== CRUD: PREGUNTAS ====================

    $(document).on('click', '.btn-nueva-pregunta', function() {
        var idSeccion = $(this).data('seccion');
        $.get('', { popup: 'pregunta', id_seccion: idSeccion }, function(html) {
            $('#popup-pregunta-container').html(html);
            $('#modalPregunta').modal('show');
        });
    });

    $(document).on('click', '.btn-editar-pregunta:not(.disabled)', function() {
        var id = $(this).data('id');
        var $pregContainer = $(this).closest('.preguntas-container');
        var $seccionItem = $(this).closest('.section-item');
        var idSeccion = $seccionItem.find('.btn-ver-preguntas').data('id');

        $.post('', { accion: 'listar_preguntas', id_seccion: idSeccion }, function(res) {
            if (res.success) {
                var p = res.data.find(function(q) { return q.id_pregunta == id; });
                if (p) {
                    $.get('', { popup: 'pregunta', id_seccion: p.id_seccion }, function(html) {
                        $('#popup-pregunta-container').html(html);
                        var form = $('#formPregunta');
                        form.find('[name="id_pregunta"]').val(p.id_pregunta);
                        form.find('[name="id_seccion"]').val(p.id_seccion);
                        form.find('[name="codigo_interno"]').val(p.codigo_interno);
                        form.find('[name="texto_pregunta"]').val(p.texto_pregunta);
                        form.find('[name="tipo_respuesta"]').val(p.tipo_respuesta);
                        form.find('[name="orden"]').val(p.orden);
                        if (p.requerido) form.find('[name="requerido"]').prop('checked', true);
                        form.find('[name="respuesta_esperada"]').val(p.respuesta_esperada || '');
                        if (p.requiere_foto_si_negativa) form.find('[name="requiere_foto_si_negativa"]').prop('checked', true);
                        if (p.genera_bloqueo) form.find('[name="genera_bloqueo"]').prop('checked', true);
                        form.find('[name="id_pregunta_padre"]').val(p.id_pregunta_padre || '');
                        form.find('[name="valor_padre"]').val(p.valor_padre || '');
                        form.find('[name="placeholder"]').val(p.placeholder || '');
                        form.find('[name="ayuda"]').val(p.ayuda || '');
                        $('#modalPregunta').modal('show');
                    });
                }
            }
        }, 'json');
    });

    $(document).on('click', '#btnGuardarPregunta', function() {
        var data = $('#formPregunta').serializeArray();
        data.push({ name: 'accion', value: 'guardar_pregunta' });
        $.post('', data, function(res) {
            if (res.success) {
                $('#modalPregunta').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-eliminar-pregunta:not(.disabled)', function() {
        if (!confirm('Eliminar esta pregunta?')) return;
        var id = $(this).data('id');
        $.post('', { accion: 'eliminar_pregunta', id_pregunta: id }, function(res) {
            if (res.success) location.reload();
            else alert('Error: ' + res.message);
        }, 'json');
    });

    // ==================== HELPERS ====================

    // ==================== VISTA PREVIA ====================

    $(document).on('click', '.btn-preview-seccion', function() {
        var id = $(this).data('id');
        $.get('', { popup: 'preview', id_seccion: id }, function(html) {
            $('#popup-pregunta-container').html(html);
            $('#modalPreview').modal('show');
        });
    });

    $(document).on('click', '.btn-preview-version', function() {
        var id = $(this).data('id');
        $.get('', { popup: 'preview', id_version: id }, function(html) {
            $('#popup-pregunta-container').html(html);
            $('#modalPreview').modal('show');
        });
    });

    function escapeHtml(str) {
        if (!str) return '';
        return $('<span>').text(str).html();
    }

    $(document).ready(init);

})(jQuery);
