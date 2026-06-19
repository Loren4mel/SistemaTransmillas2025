/**
 * Reporte Conductor SST - Lógica del formulario de reporte SST
 *
 * Maneja:
 *   - Geolocalización con mapa Leaflet
 *   - Toggle Sí/No para secciones de accidente y comparendo
 *   - Paneles condicionales con observación + subida de archivos
 *   - Selector de gravedad con radio buttons y tabla informativa
 *   - Guía de observación colapsable
 *   - Validación de formulario
 *   - Envío AJAX con FormData
 *
 * SweetAlert2 disponible como Swal. Leaflet como L.
 */

(function () {
    'use strict';

    // --------------------------------------------------------------------
    // Utilidad: SweetAlert2 con soporte de iframe
    // (mismo patrón que preoperacional.js)
    // --------------------------------------------------------------------
    var _msgId = 0;
    var _pending = {};
    var _isInIframe = (window.parent && window.parent !== window);

    if (_isInIframe) {
        document.body.style.minHeight = 'auto';
    }

    window.addEventListener('message', function (e) {
        var d = e.data;
        if (!d || d.type !== 'SWAL_RESULT') return;
        var entry = _pending[d.msgId];
        if (!entry) return;
        delete _pending[d.msgId];
        if (entry.didClose) {
            try { entry.didClose(); } catch (err) {}
        }
        entry.resolve(d.result || {});
    });

    function swalAlert(config) {
        if (!_isInIframe) {
            return Swal.fire(config);
        }

        var msgId = ++_msgId;
        var didCloseFn = config.didClose;
        var safeConfig = Object.assign({}, config);

        delete safeConfig.didOpen;
        delete safeConfig.didClose;
        delete safeConfig.willOpen;
        delete safeConfig.willClose;
        delete safeConfig.didRender;
        delete safeConfig.didDestroy;
        delete safeConfig.inputValidator;
        delete safeConfig.preConfirm;
        delete safeConfig.returnInputValueOnDeny;
        delete safeConfig.heightAuto;

        if (safeConfig.customClass && typeof safeConfig.customClass === 'object') {
            var cc = {};
            for (var k in safeConfig.customClass) {
                if (typeof safeConfig.customClass[k] === 'string') {
                    cc[k] = safeConfig.customClass[k];
                }
            }
            safeConfig.customClass = cc;
        }

        var esFireAndForget = (config._iframeLoading === true) ||
            (config.showConfirmButton === false && !config.didClose);

        if (esFireAndForget) {
            window.parent.postMessage({
                type: 'SWAL_FIRE',
                msgId: msgId,
                config: safeConfig
            }, '*');
            return Promise.resolve({ isConfirmed: false, isDismissed: true });
        }

        return new Promise(function (resolve, reject) {
            _pending[msgId] = {
                resolve: resolve,
                reject: reject,
                didClose: didCloseFn || null
            };

            window.parent.postMessage({
                type: 'SWAL_FIRE',
                msgId: msgId,
                config: safeConfig
            }, '*');

            setTimeout(function () {
                if (_pending[msgId]) {
                    delete _pending[msgId];
                    resolve({ isConfirmed: false, isDismissed: true });
                }
            }, 120000);
        });
    }

    // --------------------------------------------------------------------
    // 1. Variables de estado
    // --------------------------------------------------------------------
    var ubicacionCoords = null;
    var mapaInstancia = null;
    var archivosPorTipo = { 'accidente': [], 'comparendo': [] };
    var TIPOS = ['accidente', 'comparendo'];

    // --------------------------------------------------------------------
    // 2. renderizarMapa(lat, lng, divId, editable)
    // --------------------------------------------------------------------
    function renderizarMapa(lat, lng, divId, editable) {
        var div = document.getElementById(divId);
        if (!div) return null;

        var map = L.map(divId, {
            center: [lat, lng],
            zoom: 16,
            zoomControl: true,
            attributionControl: true,
            scrollWheelZoom: editable,
            dragging: editable,
            touchZoom: editable,
            doubleClickZoom: editable,
            tap: editable
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        var marker = L.marker([lat, lng], {
            draggable: editable
        }).addTo(map);

        if (editable) {
            var popupText = 'Lat: ' + lat.toFixed(6) + '<br>Lng: ' + lng.toFixed(6);
            marker.bindPopup(popupText).openPopup();

            marker.on('dragend', function () {
                var pos = marker.getLatLng();
                ubicacionCoords = { lat: pos.lat, lng: pos.lng };
                marker.setPopupContent('Lat: ' + pos.lat.toFixed(6) + '<br>Lng: ' + pos.lng.toFixed(6))
                    .openPopup();
                actualizarCoordsTexto(pos.lat, pos.lng);
            });
        } else {
            marker.bindPopup('Lat: ' + lat.toFixed(6) + '<br>Lng: ' + lng.toFixed(6)).openPopup();
        }

        setTimeout(function () { map.invalidateSize(); }, 200);

        return map;
    }

    // --------------------------------------------------------------------
    // 3. actualizarCoordsTexto(lat, lng)
    // --------------------------------------------------------------------
    function actualizarCoordsTexto(lat, lng) {
        var coordsText = document.querySelector('.rcsst-coords-text');
        if (coordsText) {
            coordsText.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }
    }

    // --------------------------------------------------------------------
    // 4. obtenerUbicacion()
    // --------------------------------------------------------------------
    function obtenerUbicacion() {
        var container = document.getElementById('rcsst_mapa_container');
        if (!container) return;

        container.innerHTML = '<div class="ubicacion-status ubicacion-cargando">' +
            '<i class="fas fa-spinner fa-spin me-2"></i> Obteniendo ubicación...' +
            '</div>';

        if (!navigator.geolocation) {
            container.innerHTML = '<div class="ubicacion-status ubicacion-error">' +
                '<i class="fas fa-exclamation-triangle me-2"></i> Geolocalización no soportada por el navegador' +
                '</div>';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                ubicacionCoords = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                container.innerHTML = '<div id="rcsst_mapa" class="mapa-ubicacion"></div>';

                mapaInstancia = renderizarMapa(
                    ubicacionCoords.lat,
                    ubicacionCoords.lng,
                    'rcsst_mapa',
                    true
                );
            },
            function (error) {
                var msg;
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        msg = 'Permiso de ubicación denegado. Debe permitir el acceso a la ubicación para continuar.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        msg = 'Ubicación no disponible. Verifique que el GPS esté activado.';
                        break;
                    case error.TIMEOUT:
                        msg = 'Tiempo de espera agotado al obtener la ubicación.';
                        break;
                    default:
                        msg = 'Error al obtener la ubicación.';
                }
                container.innerHTML = '<div class="ubicacion-status ubicacion-error">' +
                    '<i class="fas fa-exclamation-triangle me-2"></i> ' + msg +
                    '</div>';
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    // --------------------------------------------------------------------
    // 5. initRadios()
    // --------------------------------------------------------------------
    function initRadios() {
        for (var t = 0; t < TIPOS.length; t++) {
            var tipo = TIPOS[t];
            var radios = document.querySelectorAll('.rcsst-radio-' + tipo);

            for (var r = 0; r < radios.length; r++) {
                radios[r].addEventListener('click', function () {
                    var tipoPadre = this.className.match(/rcsst-radio-(\w+)/)[1];

                    // Remover clase active de todos los radios de este tipo
                    var grupo = document.querySelectorAll('.rcsst-radio-' + tipoPadre);
                    for (var i = 0; i < grupo.length; i++) {
                        grupo[i].classList.remove('rcsst-active');
                    }

                    // Agregar clase active al clickeado
                    this.classList.add('rcsst-active');

                    // Actualizar hidden input
                    var valor = this.getAttribute('data-valor');
                    var hiddenInput = document.getElementById('rcsst_respuesta_' + tipoPadre);
                    if (hiddenInput) {
                        hiddenInput.value = (valor === 'si') ? '1' : '0';
                    }

                    // Toggle panel y sin-novedad
                    var panel = document.getElementById('rcsst_panel_' + tipoPadre);
                    var sinNovedad = document.getElementById('rcsst_sin_novedad_' + tipoPadre);

                    if (valor === 'si') {
                        if (panel) { panel.classList.add('rcsst-visible'); }
                        if (sinNovedad) { sinNovedad.classList.remove('rcsst-visible'); }
                    } else {
                        if (panel) { panel.classList.remove('rcsst-visible'); }
                        if (sinNovedad) { sinNovedad.classList.add('rcsst-visible'); }
                        // Resetear gravedad si cambia a No
                        resetGravedad(tipoPadre);
                    }
                });
            }
        }
    }

    // --------------------------------------------------------------------
    // 5b. initGravedad() — Selector de gravedad con radio buttons
    // --------------------------------------------------------------------
    function initGravedad() {
        for (var t = 0; t < TIPOS.length; t++) {
            (function (tipo) {
                var radiosContainer = document.getElementById('rcsst_gravedad_radios_' + tipo);
                if (!radiosContainer) return;

                var gravedadRadios = radiosContainer.querySelectorAll('.rcsst-gravedad-radio');
                for (var r = 0; r < gravedadRadios.length; r++) {
                    gravedadRadios[r].addEventListener('click', function () {
                        var gravedad = this.getAttribute('data-gravedad');

                        // Quitar clase activa de todos los radios de gravedad de este tipo
                        var grupo = radiosContainer.querySelectorAll('.rcsst-gravedad-radio');
                        for (var i = 0; i < grupo.length; i++) {
                            grupo[i].classList.remove('rcsst-gravedad-active');
                        }

                        // Activar el seleccionado
                        this.classList.add('rcsst-gravedad-active');

                        // Guardar valor en hidden input
                        var hiddenGravedad = document.getElementById('rcsst_gravedad_' + tipo);
                        if (hiddenGravedad) {
                            hiddenGravedad.value = gravedad;
                        }
                    });
                }
            })(TIPOS[t]);
        }
    }

    // --------------------------------------------------------------------
    // 5c. resetGravedad(tipo) — Resetea el selector de gravedad
    // --------------------------------------------------------------------
    function resetGravedad(tipo) {
        var hiddenGravedad = document.getElementById('rcsst_gravedad_' + tipo);
        if (hiddenGravedad) {
            hiddenGravedad.value = '';
        }
        var radiosContainer = document.getElementById('rcsst_gravedad_radios_' + tipo);
        if (radiosContainer) {
            var grupos = radiosContainer.querySelectorAll('.rcsst-gravedad-radio');
            for (var i = 0; i < grupos.length; i++) {
                grupos[i].classList.remove('rcsst-gravedad-active');
            }
        }
    }

    // --------------------------------------------------------------------
    // 5d. initInfoToggles() — Toggle para tablas informativas y guías
    // --------------------------------------------------------------------
    function initInfoToggles() {
        for (var t = 0; t < TIPOS.length; t++) {
            (function (tipo) {
                // Toggle tabla de gravedad
                var toggleBtn = document.getElementById('rcsst_info_toggle_' + tipo);
                var infoTable = document.getElementById('rcsst_info_table_' + tipo);
                if (toggleBtn && infoTable) {
                    toggleBtn.addEventListener('click', function () {
                        var isVisible = infoTable.style.display !== 'none';
                        infoTable.style.display = isVisible ? 'none' : 'block';
                        var chevron = toggleBtn.querySelector('.rcsst-info-chevron');
                        if (chevron) {
                            chevron.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
                        }
                    });
                }

                // Toggle guía de observación
                var guiaToggle = document.getElementById('rcsst_guia_toggle_' + tipo);
                var guiaList = document.getElementById('rcsst_guia_list_' + tipo);
                if (guiaToggle && guiaList) {
                    guiaToggle.addEventListener('click', function () {
                        var isVisible = guiaList.style.display !== 'none';
                        guiaList.style.display = isVisible ? 'none' : 'block';
                        var chevron = guiaToggle.querySelector('.rcsst-info-chevron');
                        if (chevron) {
                            chevron.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
                        }
                    });
                }
            })(TIPOS[t]);
        }
    }

    // --------------------------------------------------------------------
    // 6. initFileUploads()
    // --------------------------------------------------------------------
    function initFileUploads() {
        for (var t = 0; t < TIPOS.length; t++) {
            (function (tipo) {
                var dropzone = document.getElementById('rcsst_dropzone_' + tipo);
                var fileInput = document.getElementById('rcsst_input_' + tipo);

                if (!dropzone || !fileInput) return;

                // Click en dropzone abre el file input
                dropzone.addEventListener('click', function () {
                    fileInput.click();
                });

                // Input onchange
                fileInput.addEventListener('change', function () {
                    if (this.files && this.files.length > 0) {
                        agregarArchivos(tipo, this.files);
                        this.value = '';
                    }
                });

                // Drag & drop
                dropzone.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.add('rcsst-dragover');
                });

                dropzone.addEventListener('dragleave', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.remove('rcsst-dragover');
                });

                dropzone.addEventListener('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.remove('rcsst-dragover');
                    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        agregarArchivos(tipo, e.dataTransfer.files);
                    }
                });
            })(TIPOS[t]);
        }
    }

    // --------------------------------------------------------------------
    // 7. agregarArchivos(tipo, fileList)
    // --------------------------------------------------------------------
    function agregarArchivos(tipo, fileList) {
        var previewContainer = document.getElementById('rcsst_preview_' + tipo);
        var filesArray = Array.prototype.slice.call(fileList);

        for (var i = 0; i < filesArray.length; i++) {
            var file = filesArray[i];
            archivosPorTipo[tipo].push(file);
            if (previewContainer) {
                renderizarPreviewItem(tipo, file, previewContainer);
            }
        }
    }

    // --------------------------------------------------------------------
    // 8. renderizarPreviewItem(tipo, file, container)
    // --------------------------------------------------------------------
    function renderizarPreviewItem(tipo, file, container) {
        var isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
        var isImage = file.type.match(/^image\/(jpeg|png|gif|webp)$/);

        var row = document.createElement('div');
        row.className = 'rcsst-file-row';

        // Icono
        var iconEl = document.createElement('div');
        iconEl.className = 'rcsst-file-icon';

        if (isImage) {
            var thumb = document.createElement('img');
            thumb.src = URL.createObjectURL(file);
            thumb.alt = file.name;
            thumb.className = 'rcsst-file-thumb';
            iconEl.appendChild(thumb);
            row._objectUrl = thumb.src;
        } else if (isPdf) {
            iconEl.innerHTML = '<i class="fas fa-file-pdf"></i>';
            iconEl.classList.add('rcsst-file-icon--pdf');
        } else {
            iconEl.innerHTML = '<i class="fas fa-file"></i>';
            iconEl.classList.add('rcsst-file-icon--generic');
        }

        row.appendChild(iconEl);

        // Nombre
        var nameEl = document.createElement('span');
        nameEl.className = 'rcsst-file-name';
        nameEl.textContent = file.name;
        nameEl.title = file.name;
        row.appendChild(nameEl);

        // Tamaño
        var sizeEl = document.createElement('span');
        sizeEl.className = 'rcsst-file-size';
        sizeEl.textContent = formatoTamaño(file.size);
        row.appendChild(sizeEl);

        // Botón eliminar
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'rcsst-file-remove-btn';
        removeBtn.title = 'Quitar archivo';
        removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
        removeBtn.addEventListener('click', function () {
            if (row._objectUrl) {
                URL.revokeObjectURL(row._objectUrl);
            }
            var idx = archivosPorTipo[tipo].indexOf(file);
            if (idx !== -1) {
                archivosPorTipo[tipo].splice(idx, 1);
            }
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';
            setTimeout(function () {
                container.removeChild(row);
            }, 200);
        });
        row.appendChild(removeBtn);

        container.appendChild(row);
    }

    // --------------------------------------------------------------------
    // 8b. formatoTamaño(bytes)
    // --------------------------------------------------------------------
    function formatoTamaño(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + ' MB';
        }
        return (bytes / 1024).toFixed(0) + ' KB';
    }

    // --------------------------------------------------------------------
    // 9. validarUbicacion()
    // --------------------------------------------------------------------
    function validarUbicacion() {
        if (!ubicacionCoords) {
            swalAlert({
                title: 'Ubicación requerida',
                html: 'Debe permitir el acceso a la ubicación GPS para registrar el reporte.<br><br>' +
                    'Si el mensaje de permiso no aparece, verifique la configuración de ubicación de su navegador.',
                icon: 'warning',
                confirmButtonText: 'Reintentar'
            }).then(function () {
                obtenerUbicacion();
            });
            return false;
        }
        return true;
    }

    // --------------------------------------------------------------------
    // 10. validarFormulario()
    // --------------------------------------------------------------------
    function validarFormulario() {
        // 1. Validar ubicación
        if (!validarUbicacion()) {
            return false;
        }

        // 2. Para cada tipo donde respuesta == 1 (Sí)
        for (var t = 0; t < TIPOS.length; t++) {
            var tipo = TIPOS[t];
            var hiddenInput = document.getElementById('rcsst_respuesta_' + tipo);
            var respuesta = hiddenInput ? hiddenInput.value : '0';

            if (respuesta === '1') {
                var labelTipo = (tipo === 'accidente') ? 'Accidente' : 'Comparendo';

                // Validar gravedad seleccionada
                var hiddenGravedad = document.getElementById('rcsst_gravedad_' + tipo);
                var gravedadVal = hiddenGravedad ? hiddenGravedad.value : '';
                if (!gravedadVal || gravedadVal === '') {
                    swalAlert({
                        title: 'Gravedad requerida',
                        text: 'Debe seleccionar el nivel de gravedad en la sección de ' + labelTipo + '.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return false;
                }

                // Validar observación no vacía
                var obsTextarea = document.getElementById('rcsst_obs_' + tipo);
                if (!obsTextarea || !obsTextarea.value.trim()) {
                    swalAlert({
                        title: 'Campo requerido',
                        text: 'Debe describir lo sucedido en la sección de ' + labelTipo + '.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return false;
                }

                // Validar al menos un archivo
                if (archivosPorTipo[tipo].length === 0) {
                    swalAlert({
                        title: 'Evidencia requerida',
                        text: 'Debe adjuntar al menos una evidencia en la sección de ' + labelTipo + '.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return false;
                }

                // Validar tamaño de cada archivo
                for (var f = 0; f < archivosPorTipo[tipo].length; f++) {
                    var file = archivosPorTipo[tipo][f];
                    var maxSize = 10 * 1024 * 1024;
                    if (file.size > maxSize) {
                        swalAlert({
                            title: 'Archivo demasiado grande',
                            text: 'El archivo "' + file.name + '" supera el límite de 10 MB.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                        return false;
                    }
                }
            }
        }

        return true;
    }

    // --------------------------------------------------------------------
    // 11. enviarFormulario()
    // --------------------------------------------------------------------
    function enviarFormulario() {
        if (!validarFormulario()) return;

        var btn = document.getElementById('rcsst_btn_submit');
        if (!btn) return;

        // Deshabilitar botón y mostrar spinner
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        // Construir reportes JSON
        var reportes = [];
        for (var t = 0; t < TIPOS.length; t++) {
            var tipo = TIPOS[t];
            var hiddenInput = document.getElementById('rcsst_respuesta_' + tipo);
            var obsTextarea = document.getElementById('rcsst_obs_' + tipo);
            var hiddenGravedad = document.getElementById('rcsst_gravedad_' + tipo);

            var reporteItem = {
                tipo: tipo,
                respuesta: hiddenInput ? parseInt(hiddenInput.value, 10) : 0,
                observacion: obsTextarea ? obsTextarea.value : ''
            };

            // Incluir gravedad solo si la respuesta es Sí
            if (reporteItem.respuesta === 1 && hiddenGravedad && hiddenGravedad.value) {
                reporteItem.gravedad = parseInt(hiddenGravedad.value, 10);
            }

            reportes.push(reporteItem);
        }

        // Construir FormData
        var formData = new FormData();
        formData.append('reportes', JSON.stringify(reportes));
        formData.append('ubicacion', ubicacionCoords.lat.toFixed(6) + ',' + ubicacionCoords.lng.toFixed(6));

        var tipoEventoInput = document.getElementById('rcsst_tipo_evento');
        formData.append('tipo_evento', tipoEventoInput ? tipoEventoInput.value : 'semanal');
        formData.append('accion', 'guardar');

        // Adjuntar archivos por tipo
        for (var tipo2 in archivosPorTipo) {
            if (archivosPorTipo.hasOwnProperty(tipo2)) {
                var archivos = archivosPorTipo[tipo2];
                for (var a = 0; a < archivos.length; a++) {
                    formData.append('archivos_' + tipo2 + '[]', archivos[a]);
                }
            }
        }

        // Enviar por fetch
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    swalAlert({
                        title: 'Éxito',
                        text: 'Reporte enviado correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            /**
                             * INTEGRACIÓN PENDIENTE — Redirección al Preoperacional:
                             *
                             * Si el parámetro ?redirect=preoperacional está presente,
                             * redirigir a la URL del preoperacional:
                             *
                             *   var urlParams = new URLSearchParams(window.location.search);
                             *   var redirect = urlParams.get('redirect');
                             *   if (redirect === 'preoperacional') {
                             *       window.location.href = 'PreoperacionalController.php';
                             *   } else {
                             *       window.location.reload();
                             *   }
                             *
                             * Estado actual: recarga la página.
                             */
                            window.location.reload();
                        }
                    });
                } else {
                    swalAlert({
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al enviar el reporte.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false
                    });
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Enviar Reporte SST';
                    }
                }
            })
            .catch(function (error) {
                swalAlert({
                    title: 'Error',
                    text: 'Error de comunicación con el servidor.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false
                });
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Enviar Reporte SST';
                }
            });
    }

    // --------------------------------------------------------------------
    // 12. init — Punto de entrada al cargar el DOM
    // --------------------------------------------------------------------
    function init() {
        try {
            obtenerUbicacion();
            initRadios();
            initGravedad();
            initInfoToggles();
            initFileUploads();

            var btnSubmit = document.getElementById('rcsst_btn_submit');
            if (btnSubmit) {
                btnSubmit.addEventListener('click', enviarFormulario);
            }
        } catch (error) {
            // Error silencioso en producción
        }
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
