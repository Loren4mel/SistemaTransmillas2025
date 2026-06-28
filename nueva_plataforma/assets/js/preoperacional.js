/**
 * Preoperacional - Lógica del formulario de preoperacional
 *
 * Maneja el envío del formulario, precarga de datos y validaciones
 * Incluye soporte para subida de fotos en inspección inicial
 * Soporte para checkboxes de vehículo con observaciones
 */

(function () {
    'use strict';

    /**
     * Wrapper alrededor de Swal.fire con relay vía postMessage
     * cuando se ejecuta dentro de un iframe.
     *
     * Los modales se renderizan en el viewport COMPLETO de la
     * ventana padre, eliminando el bucle de retroalimentacion
     * del ResizeObserver y el backdrop gris limitado al iframe.
     *
     * Cuando NO esta en un iframe, usa Swal.fire directamente.
     *
     * @param {Object} config - Configuración estándar de SweetAlert2
     * @returns {Promise} - Misma promesa que retorna Swal.fire
     */

    // --- Infraestructura de mensajeria ---
    var _msgId = 0;
    var _pending = {};      // msgId -> { resolve, reject, didClose }
    var _isInIframe = (window.parent && window.parent !== window);

    // Rompe el bucle ResizeObserver: body { min-height: 100vh }
    // dentro del iframe usa la altura DEL iframe como referencia,
    // asi que cada vez que ajustarIframePreoperacional aumenta la
    // altura, 100vh crece, el body crece, y el ciclo se repite.
    if (_isInIframe) {
        document.body.style.minHeight = 'auto';
    }

    /**
     * Recibe resultados de modales despachados desde el padre
     */
    window.addEventListener('message', function (e) {
        var d = e.data;
        if (!d || d.type !== 'SWAL_RESULT') return;
        var entry = _pending[d.msgId];
        if (!entry) return;
        delete _pending[d.msgId];
        // Ejecutar didClose en el lado del iframe ANTES de resolver
        if (entry.didClose) {
            try { entry.didClose(); } catch (err) {}
        }
        entry.resolve(d.result || {});
    });

    function swalAlert(config) {
        // --- Ruta directa (sin iframe) ---
        if (!_isInIframe) {
            return Swal.fire(config);
        }

        // --- Ruta iframe: serializar y enviar al padre ---
        var msgId = ++_msgId;
        var didCloseFn = config.didClose;   // preservar para ejecutar localmente
        var safeConfig = Object.assign({}, config);

        // Eliminar callbacks no serializables
        delete safeConfig.didOpen;
        delete safeConfig.didClose;
        delete safeConfig.willOpen;
        delete safeConfig.willClose;
        delete safeConfig.didRender;
        delete safeConfig.didDestroy;
        delete safeConfig.inputValidator;
        delete safeConfig.preConfirm;
        delete safeConfig.returnInputValueOnDeny;
        delete safeConfig.heightAuto;       // irrelevante en el padre

        // Simplificar customClass a solo strings
        if (safeConfig.customClass && typeof safeConfig.customClass === 'object') {
            var cc = {};
            for (var k in safeConfig.customClass) {
                if (typeof safeConfig.customClass[k] === 'string') {
                    cc[k] = safeConfig.customClass[k];
                }
            }
            safeConfig.customClass = cc;
        }

        // ¿Es solo un indicador de carga o un toast? →
        // no se necesita respuesta del usuario.
        var esFireAndForget = (config._iframeLoading === true) ||
                              (config.showConfirmButton === false && !config.didClose);

        if (esFireAndForget) {
            // Despachar sin registrar Promise pendiente
            window.parent.postMessage({
                type: 'SWAL_FIRE',
                msgId: msgId,
                config: safeConfig
            }, '*');
            return Promise.resolve({ isConfirmed: false, isDismissed: true });
        }

        // Modal bloqueante: registrar Promise y esperar SWAL_RESULT
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

            // Timeout de seguridad: 120 s
            setTimeout(function () {
                if (_pending[msgId]) {
                    delete _pending[msgId];
                    resolve({ isConfirmed: false, isDismissed: true });
                }
            }, 120000);
        });
    }

    // Coordenadas de ubicación obtenidas del navegador
    var ubicacionCoords = null;
    var mapaInstancia = null;

    // Estado del vehículo seleccionado para cambio voluntario (cache local, sin submit hasta guardar)
    var _vehiculoSeleccionado = {
        id: null,
        files: {},
        observaciones: ''
    };

    /**
     * Renderiza un mapa Leaflet ligero en el contenedor indicado
     * Optimizado para mobile: sin controles de zoom, gestos táctiles nativos
     */
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

            marker.on('dragend', function() {
                var pos = marker.getLatLng();
                ubicacionCoords = { lat: pos.lat, lng: pos.lng };
                marker.setPopupContent('Lat: ' + pos.lat.toFixed(6) + '<br>Lng: ' + pos.lng.toFixed(6))
                    .openPopup();
                // Actualizar el texto de coordenadas debajo del mapa
                var coordsText = document.querySelector('.ubicacion-coords-text');
                if (coordsText) {
                    coordsText.innerHTML = '<i class="fas fa-map-marker-alt"></i>' +
                        '<strong>Coordenadas:</strong> ' +
                        pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6);
                }
            });
        } else {
            marker.bindPopup('Lat: ' + lat.toFixed(6) + '<br>Lng: ' + lng.toFixed(6)).openPopup();
        }

        // Corregir tamaño en mobile después del renderizado
        setTimeout(function() { map.invalidateSize(); }, 200);

        return map;
    }

    /**
     * Intenta obtener la ubicación del dispositivo mediante geolocalización
     * Al obtenerla, renderiza un mapa Leaflet con la posición
     */
    function obtenerUbicacion() {
        var container = document.getElementById('ubicacion_container');
        if (!container) return Promise.resolve(null); // Legacy format: no location needed

        container.innerHTML = '<div class="ubicacion-status ubicacion-cargando">' +
            '<i class="fas fa-spinner fa-spin"></i> Obteniendo ubicación...' +
            '</div>';

        if (!navigator.geolocation) {
            container.innerHTML = '<div class="ubicacion-status ubicacion-error">' +
                '<i class="fas fa-exclamation-triangle"></i> Geolocalización no soportada por el navegador' +
                '</div>';
            return Promise.reject(new Error('Geolocalización no soportada'));
        }

        return new Promise(function(resolve, reject) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    ubicacionCoords = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    // Reemplazar el contenedor con el mapa + texto de coordenadas
                    container.innerHTML =
                        '<div id="mapa_ubicacion" class="mapa-ubicacion"></div>' +
                        '<div class="ubicacion-coords-text">' +
                            '<i class="fas fa-map-marker-alt"></i>' +
                            '<strong>Coordenadas:</strong> ' +
                            ubicacionCoords.lat.toFixed(6) + ', ' + ubicacionCoords.lng.toFixed(6) +
                        '</div>';

                    mapaInstancia = renderizarMapa(
                        ubicacionCoords.lat,
                        ubicacionCoords.lng,
                        'mapa_ubicacion',
                        true
                    );

                    resolve(ubicacionCoords);
                },
                function(error) {
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
                        '<i class="fas fa-exclamation-triangle"></i> ' + msg +
                        '</div>';
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });
    }

    /**
     * Valida que se haya obtenido la ubicación antes de guardar
     * (obligatorio para registrar el preoperacional)
     */
    function validarUbicacion() {
        // En modo validación no se requiere obtener nueva ubicación
        if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
            return true;
        }

        // Legacy format: no location container, no location required
        if (!document.getElementById('ubicacion_container')) {
            return true;
        }

        if (!ubicacionCoords) {
            swalAlert({
                title: 'Ubicación requerida',
                html: 'Debe permitir el acceso a la ubicación GPS para registrar el preoperacional.<br><br>' +
                      'Si el mensaje de permiso no aparece, verifique la configuración de ubicación de su navegador.',
                icon: 'warning',
                confirmButtonText: 'Reintentar'
            }).then(function() {
                obtenerUbicacion();
            });
            return false;
        }
        return true;
    }

    /**
     * Configura la exclusividad para checkboxes binarios (SÍ/NO)
     * Solo un checkbox por grupo puede estar marcado
     */
    function initBinaryCheckboxes() {
        document.addEventListener('change', function(e) {
            // Manejar checkboxes binarios (SÍ/NO)
            if (e.target.classList.contains('checkbox-binary')) {
                var groupName = e.target.getAttribute('data-binary-group');
                var checkboxesInGroup = document.querySelectorAll('.checkbox-binary[data-binary-group="' + groupName + '"]');
                var isChecked = e.target.checked;
                var value = e.target.value;
                var checkboxName = e.target.getAttribute('data-name');

                // Si se marca un checkbox, desmarcar el otro en el mismo grupo
                if (isChecked) {
                    for (var i = 0; i < checkboxesInGroup.length; i++) {
                        var cb = checkboxesInGroup[i];
                        if (cb !== e.target) {
                            cb.checked = false;
                            // Ocultar fila de foto si el checkbox desmarcado era NO (valor 2)
                            if (cb.value === '2') {
                                var photoRow = document.getElementById(checkboxName + '_photo_row');
                                if (photoRow) {
                                    photoRow.style.display = 'none';
                                }
                            }
                        }
                    }

                    // Si se marca NO (valor 2), mostrar campo de foto si existe
                    if (value === '2') {
                        var photoRow = document.getElementById(checkboxName + '_photo_row');
                        if (photoRow) {
                            photoRow.style.display = '';
                        }
                    } else if (value === '1') {
                        // Si se marca SÍ (valor 1), ocultar campo de foto si existe
                        var photoRow = document.getElementById(checkboxName + '_photo_row');
                        if (photoRow) {
                            photoRow.style.display = 'none';
                        }
                    }
                } else {
                    // Si se desmarca un checkbox, verificar si ningún checkbox está marcado
                    var anyChecked = false;
                    for (var i = 0; i < checkboxesInGroup.length; i++) {
                        if (checkboxesInGroup[i].checked) {
                            anyChecked = true;
                            break;
                        }
                    }
                    // Si ningún checkbox está marcado, ocultar foto (si estaba visible por NO)
                    if (!anyChecked && value === '2') {
                        var photoRow = document.getElementById(checkboxName + '_photo_row');
                        if (photoRow) {
                            photoRow.style.display = 'none';
                        }
                    }
                }
            }
        });
    }

    /**
     * Convierte los valores de los inputs seleccionados a JSON
     * y los almacena en el campo hidden "data"
     * Soporta radio buttons, checkboxes binarios (SÍ/NO) y checkboxes de vehículo
     */
    function asignar() {
        var conver = '';
        var chkdatos = document.getElementsByClassName("obtener");

        for (var i = 0; i < chkdatos.length; i++) {
            var input = chkdatos[i];

            // Para checkboxes binarios (SÍ/NO) - preguntas personales
            if (input.type === 'checkbox' && input.classList.contains('checkbox-binary')) {
                var checkboxName = input.getAttribute('data-name');
                var binaryGroup = input.getAttribute('data-binary-group');

                // Solo procesar si está marcado (un checkbox por grupo debe estar marcado)
                if (input.checked) {
                    var value = input.value; // 1 para SÍ, 2 para NO
                    conver = conver + '"' + checkboxName + '":' + '"' + value + '",';
                }
            }
            else if (input.type === 'radio' && input.checked) {
                // Radio buttons tradicionales
                conver = conver + '"' + input.name + '":' + '"' + input.value + '",';
            }
        }

        // Eliminar última coma si hay datos
        if (conver.length > 0) {
            var data = conver.substring(0, conver.length - 1);
            data = '{' + data + '}';
        } else {
            var data = '{}';
        }

        document.getElementById("data").value = data;
        return true;
    }

    /**
     * Valida que los campos de foto requeridos tengan archivos seleccionados
     * cuando se selecciona NO (checkbox de valor 2) o cuando checkbox único no está marcado
     */
    function validarFotosRequeridas() {
        var errores = [];

        // 1. Validar checkboxes binarios (SÍ/NO) - solo cuando se selecciona NO (valor 2)
        var binaryCheckboxes = document.querySelectorAll('.checkbox-binary');
        for (var i = 0; i < binaryCheckboxes.length; i++) {
            var checkbox = binaryCheckboxes[i];
            if (checkbox.checked && checkbox.value === '2') { // Solo si está marcado como NO
                var checkboxName = checkbox.getAttribute('data-name');
                var fileInput = document.getElementById(checkboxName + '_foto');
                if (fileInput) {
                    var photoRow = document.getElementById(checkboxName + '_photo_row');
                    if (photoRow && photoRow.style.display !== 'none') {
                        if (!fileInput.files || fileInput.files.length === 0) {
                            var questionRow = document.getElementById(checkboxName + '_row');
                            var questionText = checkboxName;
                            if (questionRow) {
                                var questionTextElem = questionRow.querySelector('.question-text');
                                if (questionTextElem) {
                                    questionText = questionTextElem.textContent.trim();
                                }
                            }
                            errores.push('Debe subir una fotografía para: "' + questionText + '"');
                        }
                    }
                }
            }
        }

        if (errores.length > 0) {
            var errorHtml = '<strong>Debe subir fotografías para las siguientes observaciones:</strong><br><br>';
            for (var k = 0; k < errores.length; k++) {
                errorHtml += '• ' + errores[k] + '<br>';
            }

            swalAlert({
                title: 'Fotos requeridas',
                html: errorHtml,
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }

        // También validar fotos de preguntas vehiculares
        return validarFotosVehiculo();
    }

    /**
     * Valida que las preguntas de vehículo respondidas con NO tengan foto asociada.
     * La foto es obligatoria para cualquier NO en sección vehículo.
     */
    function validarFotosVehiculo() {
        var errores = [];
        var preguntasVehiculo = [
            'inspec_1', 'luces_1', 'cabina_1', 'cabina_2',
            'seguridad_1', 'seguridad_2', 'seguridad_3', 'seguridad_4', 'seguridad_5',
            'seguridad_6', 'seguridad_7', 'seguridad_8', 'seguridad_9', 'seguridad_10',
            'indicador_1', 'indicador_2', 'indicador_3', 'indicador_4',
            'llanta_1', 'llanta_2'
        ];

        for (var i = 0; i < preguntasVehiculo.length; i++) {
            var codigo = preguntasVehiculo[i];
            var checkboxNo = document.querySelector('.checkbox-binary[data-name="' + codigo + '"][value="2"]');
            if (!checkboxNo || !checkboxNo.checked) continue;

            var photoRow = document.getElementById(codigo + '_photo_row');
            if (photoRow && photoRow.style.display !== 'none') {
                var fileInput = document.getElementById(codigo + '_foto');
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    // En modo validación, verificar si ya existe foto guardada
                    var fotoGuardada = photoRow.querySelector('img');
                    if (!fotoGuardada) {
                        var questionRow = document.getElementById(codigo + '_row');
                        var questionText = codigo;
                        if (questionRow) {
                            var questionTextElem = questionRow.querySelector('.question-text');
                            if (questionTextElem) {
                                questionText = questionTextElem.textContent.trim();
                            }
                        }
                        errores.push('Debe subir una fotografía para: "' + questionText + '"');
                    }
                }
            }
        }

        if (errores.length > 0) {
            var errorHtml = '<strong>Debe subir fotografías para los siguientes problemas reportados:</strong><br><br>';
            for (var k = 0; k < errores.length; k++) {
                errorHtml += '• ' + errores[k] + '<br>';
            }

            swalAlert({
                title: 'Fotos requeridas',
                html: errorHtml,
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }

        return true;
    }

    /**
     * Valida que en cada grupo de checkboxes binarios (SÍ/NO) se haya seleccionado una opción
     */
    function validarCheckboxesBinarios() {
        var errores = [];
        var grupos = {};

        // Recopilar todos los grupos de checkboxes binarios
        var binaryCheckboxes = document.querySelectorAll('.checkbox-binary');
        for (var i = 0; i < binaryCheckboxes.length; i++) {
            var checkbox = binaryCheckboxes[i];
            var groupName = checkbox.getAttribute('data-binary-group');
            if (!grupos[groupName]) {
                grupos[groupName] = [];
            }
            grupos[groupName].push(checkbox);
        }

        // Verificar cada grupo
        for (var groupName in grupos) {
            if (grupos.hasOwnProperty(groupName)) {
                var checkboxes = grupos[groupName];
                var algunoMarcado = false;
                for (var j = 0; j < checkboxes.length; j++) {
                    if (checkboxes[j].checked) {
                        algunoMarcado = true;
                        break;
                    }
                }
                if (!algunoMarcado) {
                    // Obtener texto de la pregunta para el mensaje de error
                    var questionRow = document.getElementById(groupName + '_row');
                    var questionText = groupName;
                    if (questionRow) {
                        var questionTextElem = questionRow.querySelector('.question-text');
                        if (questionTextElem) {
                            questionText = questionTextElem.textContent.trim();
                        }
                    }
                    errores.push('Debe seleccionar SÍ o NO para: "' + questionText + '"');
                }
            }
        }

        if (errores.length > 0) {
            var errorHtml = '<strong>Debe responder las siguientes preguntas:</strong><br><br>';
            for (var k = 0; k < errores.length; k++) {
                errorHtml += '• ' + errores[k] + '<br>';
            }

            swalAlert({
                title: 'Preguntas pendientes',
                html: errorHtml,
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }

        return true;
    }

    /**
     * Valida que se haya proporcionado una firma
     */
    function validarFirma() {
        // En modo validación no se necesita capturar nueva firma
        if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
            return true;
        }

        var firmaInput = document.getElementById('firma_preoperacional');
        if (!firmaInput) {
            // Formato legado: no tiene campo de firma, no requiere validación
            return true;
        }

        // Sincronizar canvas -> hidden input antes de validar
        // (el listener submit del canvas se ejecuta después de esta validación)
        var canvas = document.getElementById('signatureCanvas');
        if (canvas && canvas.getAttribute('data-has-signature') === 'true') {
            firmaInput.value = canvas.toDataURL('image/png');
        }

        var firmaValor = firmaInput.value.trim();

        if (!firmaValor) {
            swalAlert({
                title: 'Firma requerida',
                text: 'Debe firmar en el canvas antes de guardar el preoperacional.',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
            return false;
        }

        return true;
    }

    /**
     * Formatea el campo de kilometraje con separadores de miles (puntos)
     * Solo permite números, los puntos son netamente visuales
     */
    function initKilometrajeFormatting() {
        var kmInput = document.getElementById('kilometraje');
        if (kmInput) {
            kmInput.addEventListener('input', function(e) {
                // Guardar posición del cursor
                var start = this.selectionStart;
                var end = this.selectionEnd;

                // Eliminar todo lo que no sea dígito
                var raw = this.value.replace(/\./g, '').replace(/\D/g, '');

                if (raw.length > 0) {
                    // Formatear con separadores de miles (formato español: 1.234.567)
                    var formatted = parseInt(raw, 10).toLocaleString('es-CO');
                    this.value = formatted;

                    // Ajustar posición del cursor después del formateo
                    var diff = this.value.length - raw.length;
                    try {
                        this.setSelectionRange(start + diff, end + diff);
                    } catch (e) {}
                } else {
                    this.value = '';
                }

                // Resetear bandera de alerta cuando el usuario modifica el valor
                window._kmWarningAcknowledged = false;
                var warningsContainer = document.getElementById('km-warnings');
                if (warningsContainer) {
                    warningsContainer.style.display = 'none';
                    warningsContainer.innerHTML = '';
                }
                this.style.borderColor = '';
                this.style.boxShadow = '';
            });
        }
    }

    /**
     * Valida el kilometraje con alertas sugestivas (Fase 1)
     * - Si km < último registrado → alerta
     * - Si km > último + 300 → alerta (excepto reseteo odómetro)
     * - Reseteo de odómetro (km ≤ 300 y último cerca de 999...9) → permitido sin alerta
     * - Usuario puede re-intentar después de ver la alerta
     */
    function validarKilometraje() {
        if (typeof MOSTRAR_SECCION_VEHICULO !== 'undefined' && !MOSTRAR_SECCION_VEHICULO) return true;
        var input = document.getElementById('kilometraje');
        if (!input || input.readOnly) return true;

        var raw = input.value.replace(/\./g, '').trim();
        if (!raw || parseInt(raw, 10) <= 0) {
            mostrarAlertaKm('Debe ingresar el kilometraje actual del vehículo.');
            return false;
        }

        var nuevoKm = parseInt(raw, 10);
        var ultimoKm = typeof ULTIMO_KM !== 'undefined' ? parseInt(ULTIMO_KM, 10) : 0;
        if (ultimoKm <= 0) return true; // Sin referencia, no validar

        // Si el usuario ya vio la alerta y vuelve a guardar, permitir
        if (window._kmWarningAcknowledged === true) {
            return true;
        }

        // Detectar reseteo de odómetro
        if (esReseteoOdometro(ultimoKm, nuevoKm)) {
            return true;
        }

        // Acumular alertas
        var alertas = [];
        if (nuevoKm < ultimoKm) {
            alertas.push(
                'El kilometraje ingresado (<strong>' + nuevoKm.toLocaleString('es-CO') + '</strong> km) ' +
                'es <strong>menor</strong> al último registrado (<strong>' + ultimoKm.toLocaleString('es-CO') + '</strong> km).'
            );
        }
        if (nuevoKm > (ultimoKm + 300)) {
            alertas.push(
                'El kilometraje ingresado (<strong>' + nuevoKm.toLocaleString('es-CO') + '</strong> km) ' +
                '<strong>excede el límite diario de 300 km</strong> (último registro: <strong>' + ultimoKm.toLocaleString('es-CO') + '</strong> km).'
            );
        }

        if (alertas.length > 0) {
            mostrarAlertasKm(alertas, input);
            return false;
        }

        return true;
    }

    /**
     * Detecta si el valor ingresado corresponde a un reseteo de odómetro
     * (odómetro llegó a 999...9 y volvió a 0-300)
     */
    function esReseteoOdometro(ultimoKm, nuevoKm) {
        if (nuevoKm > 300) return false;
        if (ultimoKm <= 0) return false;

        var digitos = Math.floor(Math.log10(ultimoKm)) + 1;
        var maxOdometro = Math.pow(10, digitos) - 1; // 9...9
        var umbralCercania = 500;

        return (ultimoKm >= maxOdometro - umbralCercania);
    }

    /**
     * Muestra alertas inline en la tarjeta de kilometraje y hace scroll
     */
    function mostrarAlertasKm(alertas, input) {
        var kmCard = input.closest('.preop-card');
        if (!kmCard) return;

        // Scroll suave a la tarjeta
        kmCard.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Construir HTML de alertas
        var warningsContainer = document.getElementById('km-warnings');
        if (!warningsContainer) return;

        var html = '<div class="alert alert-warning" style="padding:12px 14px; border-radius:8px; border-left:4px solid #e67e22; background:#fff8e1;">';
        html += '<div style="display:flex; align-items:flex-start; gap:10px;">';
        html += '<i class="fas fa-exclamation-triangle" style="color:#e67e22; font-size:18px; margin-top:2px;"></i>';
        html += '<div style="flex:1;">';
        html += '<strong style="font-size:14px; color:#856404;">Verifique el kilometraje ingresado</strong>';
        html += '<ul style="margin:6px 0 0 0; padding-left:18px; font-size:13px; color:#856404;">';
        for (var i = 0; i < alertas.length; i++) {
            html += '<li style="margin-bottom:4px;">' + alertas[i] + '</li>';
        }
        html += '</ul>';
        html += '<p style="margin:8px 0 0 0; font-size:12px; color:#856404;">';
        html += '<i class="fas fa-info-circle"></i> Si confirma que el valor es correcto, haga clic en <strong>"Guardar"</strong> nuevamente para continuar.';
        html += '</p>';
        html += '</div></div></div>';

        warningsContainer.innerHTML = html;
        warningsContainer.style.display = 'block';

        // Establecer bandera para permitir re-intento
        window._kmWarningAcknowledged = true;

        // Resaltar temporalmente el input
        input.style.borderColor = '#e67e22';
        input.style.boxShadow = '0 0 0 2px rgba(230,126,34,0.25)';
    }

    /**
     * Muestra alerta simple de error requerido en kilometraje
     */
    function mostrarAlertaKm(mensaje) {
        var input = document.getElementById('kilometraje');
        if (input) {
            resaltarTarjeta(input, mensaje);
        }
        swalAlert({
            title: 'Kilometraje requerido',
            text: mensaje,
            icon: 'warning',
            confirmButtonText: 'Aceptar'
        });
    }

    /**
     * Valida que se haya subido una imagen/foto del kilometraje
     */
    /**
     * Resalta una tarjeta del formulario con animación y muestra mensaje de error
     */
    function resaltarTarjeta(elemento, mensaje) {
        if (!elemento) return;
        var card = elemento.closest('.preop-card');
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Quitar error previo si existe
            var prevError = card.querySelector('.card-validation-error');
            if (prevError) prevError.remove();

            // Mostrar mensaje de error dentro de la tarjeta si hay texto
            if (mensaje) {
                var body = card.querySelector('.preop-card-body');
                if (body) {
                    var errorEl = document.createElement('div');
                    errorEl.className = 'card-validation-error';
                    errorEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + mensaje;
                    body.insertBefore(errorEl, body.firstChild);
                }
            }

            card.classList.add('validation-error-card');
            setTimeout(function () {
                card.classList.remove('validation-error-card');
                var err = card.querySelector('.card-validation-error');
                if (err) err.remove();
            }, 4000);
        } else {
            elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function validarImagenKilometraje() {
        // Solo requerido cuando hay sección de vehículo (carro/moto)
        if (typeof MOSTRAR_SECCION_VEHICULO !== 'undefined' && !MOSTRAR_SECCION_VEHICULO) return true;
        var fileInput = document.querySelector('input[name="imagen_kilometraje"]');
        if (!fileInput || fileInput.disabled) return true;

        // Si ya existe imagen previa guardada en el servidor, permitir continuar sin nueva subida
        var preImgKilo = document.getElementById('pre_img_kilo_existente');
        if (preImgKilo && preImgKilo.value === '1') return true;

        if (!fileInput.files || fileInput.files.length === 0) {
            resaltarTarjeta(fileInput, 'Debe subir una foto del kilometraje del vehículo.');
            swalAlert({
                title: 'Imagen de kilometraje requerida',
                text: 'Debe subir una foto del kilometraje del vehículo antes de guardar.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }
        return true;
    }

    /**
     * Determina si un checkbox de conductor tiene respuesta negativa
     * (contraria a la esperada)
     */
    function isRespuestaNegativaConductor(checkbox) {
        var expected = checkbox.getAttribute('data-expected');
        if (!expected) return false; // No es pregunta de conductor
        return checkbox.checked && checkbox.value !== expected;
    }

    /**
     * Actualiza la visibilidad del warning para una pregunta específica
     */
    function actualizarWarningConductor(checkboxName) {
        var warningEl = document.getElementById(checkboxName + '_warning');
        if (!warningEl) return;

        // Buscar el checkbox marcado en este grupo
        var checkboxes = document.querySelectorAll('.checkbox-binary[data-binary-group="' + checkboxName + '"]');
        var hayNegativa = false;
        for (var i = 0; i < checkboxes.length; i++) {
            if (isRespuestaNegativaConductor(checkboxes[i])) {
                hayNegativa = true;
                break;
            }
        }

        warningEl.style.display = hayNegativa ? '' : 'none';
    }

    /**
     * Actualiza el banner de bloqueo de una sección de conductor
     */
    function actualizarBannerConductor(sectionClass) {
        var banner = document.getElementById(sectionClass + '_block_banner');
        if (!banner) return;

        // Buscar todas las preguntas con data-expected en esta sección
        var sectionCard = document.querySelector('.preop-card.' + sectionClass);
        if (!sectionCard) return;

        var checkboxes = sectionCard.querySelectorAll('.checkbox-binary[data-expected]');
        var hayNegativa = false;
        for (var i = 0; i < checkboxes.length; i++) {
            if (isRespuestaNegativaConductor(checkboxes[i])) {
                hayNegativa = true;
                break;
            }
        }

        banner.style.display = hayNegativa ? '' : 'none';
    }

    /**
     * Actualiza todos los warnings y banners de conductor
     */
    function actualizarTodosWarningsConductor() {
        var secciones = ['conductor', 'vehiculo-propio'];
        for (var s = 0; s < secciones.length; s++) {
            actualizarBannerConductor(secciones[s]);
        }

        // Actualizar warnings individuales
        var checkboxes = document.querySelectorAll('.checkbox-binary[data-expected]');
        var procesados = {};
        for (var i = 0; i < checkboxes.length; i++) {
            var name = checkboxes[i].getAttribute('data-name');
            if (!procesados[name]) {
                procesados[name] = true;
                actualizarWarningConductor(name);
            }
        }
    }

    /**
     * Valida que no haya respuestas negativas en preguntas del conductor
     * Retorna true si todo OK, false si hay respuestas que bloquean
     */
    function validarRespuestasConductor() {
        var checkboxes = document.querySelectorAll('.checkbox-binary[data-expected]');
        var preguntasNegativas = [];

        var procesados = {};
        for (var i = 0; i < checkboxes.length; i++) {
            var checkbox = checkboxes[i];
            var name = checkbox.getAttribute('data-name');
            if (procesados[name]) continue;
            procesados[name] = true;

            if (isRespuestaNegativaConductor(checkbox)) {
                var questionRow = document.getElementById(name + '_row');
                var questionText = name;
                if (questionRow) {
                    var questionTextElem = questionRow.querySelector('.question-text');
                    if (questionTextElem) {
                        questionText = questionTextElem.textContent.trim();
                    }
                }
                preguntasNegativas.push(questionText);
            }
        }

        if (preguntasNegativas.length > 0) {
            var errorHtml = '<strong>No se puede realizar el preoperacional si presenta complicaciones.</strong><br><br>';
            errorHtml += '<em>Por favor, comuníquese con el jefe de operaciones / oficina.</em><br><br>';
            errorHtml += '<strong>Preguntas con respuesta negativa:</strong><br>';
            for (var k = 0; k < preguntasNegativas.length; k++) {
                errorHtml += '• ' + preguntasNegativas[k] + '<br>';
            }

            swalAlert({
                title: 'Preoperacional bloqueado',
                html: errorHtml,
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            return false;
        }

        return true;
    }

    /**
     * Configura listeners para actualizar warnings de conductor en tiempo real
     */
    function initDriverWarnings() {
        // Precarga: mostrar warnings que apliquen al cargar la página
        actualizarTodosWarningsConductor();
        actualizarBotonGuardar();

        // Escuchar cambios en checkboxes con data-expected
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('checkbox-binary') && e.target.getAttribute('data-expected')) {
                var name = e.target.getAttribute('data-name');
                actualizarWarningConductor(name);

                // Actualizar banner de la sección correspondiente
                var secciones = ['conductor', 'vehiculo-propio'];
                for (var s = 0; s < secciones.length; s++) {
                    actualizarBannerConductor(secciones[s]);
                }

                // Actualizar estado del botón de guardar
                actualizarBotonGuardar();
            }
        });
    }

    /**
     * Verifica si hay respuestas negativas en preguntas del conductor
     * Retorna true si hay al menos una
     */
    function hayRespuestaNegativa() {
        var checkboxes = document.querySelectorAll('.checkbox-binary[data-expected]');
        var procesados = {};
        for (var i = 0; i < checkboxes.length; i++) {
            var name = checkboxes[i].getAttribute('data-name');
            if (procesados[name]) continue;
            procesados[name] = true;
            if (isRespuestaNegativaConductor(checkboxes[i])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica si hay documentos del vehículo vencidos
     */
    function hayDocumentosVencidos() {
        var bloquearEl = document.getElementById('vehiculo_bloquear');
        return bloquearEl && bloquearEl.value === '1';
    }

    /**
     * Muestra una alerta grande si hay documentos del vehículo vencidos.
     * Permite continuar con el guardado después de que el usuario reconozca la advertencia.
     */
    async function validarDocumentosVehiculo() {
        if (hayDocumentosVencidos()) {
            // Construir lista de alertas para mostrar en el modal
            var alertasEl = document.getElementById('vehiculo_alertas');
            var listaAlertas = '';
            if (alertasEl && alertasEl.value) {
                try {
                    var alertas = JSON.parse(alertasEl.value);
                    for (var i = 0; i < alertas.length; i++) {
                        var a = alertas[i];
                        var texto = '';
                        if (a.dias < 0) {
                            texto = a.nombre + ': <span style="color:#dc3545;font-weight:bold;">EXPIRADA hace ' + Math.abs(a.dias) + ' días</span>';
                        } else if (a.dias <= 7) {
                            texto = a.nombre + ': <span style="color:#dc3545;">expira en ' + a.dias + ' días</span>';
                        } else if (a.dias <= 30) {
                            texto = a.nombre + ': <span style="color:#ff9800;">expira en ' + a.dias + ' días</span>';
                        } else {
                            texto = a.nombre + ': ' + a.fecha + ' (' + a.dias + ' días)';
                        }
                        listaAlertas += '<li style="margin-bottom:6px;">' + texto + '</li>';
                    }
                } catch(e) {}
            }

            await swalAlert({
                title: '<span style="font-size:1.4em;">ATENCIÓN: Documentos del vehículo vencidos</span>',
                html: '<div style="font-size:1.05em; line-height:1.7; text-align:left;">' +
                    '<p style="margin-bottom:12px;">Se han detectado <strong>documentos vencidos</strong> del vehículo (licencia, seguro o tecnicomecánica).</p>' +
                    '<p style="margin-bottom:12px;">El preoperacional <strong>se registrará de todos modos</strong>, pero es indispensable que actualice los documentos cuanto antes.</p>' +
                    (listaAlertas ? '<div style="background:#fffbf0; border:1px solid #ff9800; border-radius:10px; padding:14px 18px; margin:12px 0;">' +
                        '<strong style="color:#7a5200;">Documentos con alerta:</strong>' +
                        '<ul style="margin:8px 0 0 0; padding-left:18px; list-style:none;">' + listaAlertas + '</ul></div>' : '') +
                    '<p style="margin-top:12px; color:#dc3545;"><em>Comuníquese con el jefe de operaciones para actualizar los documentos.</em></p>' +
                    '</div>',
                icon: 'warning',
                confirmButtonText: 'Entendido, continuar de todos modos',
                confirmButtonColor: '#ff9800',
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: '42em',
                customClass: {
                    title: 'swal-title-large',
                    popup: 'swal-popup-large'
                }
            });
        }
    }

    /**
     * Actualiza el estado del botón de guardar:
     * - Se deshabilita solo si hay respuestas negativas del conductor.
     * - Si hay documentos vencidos, se muestra una advertencia debajo del botón pero se permite guardar.
     */
    function actualizarBotonGuardar() {
        var btn = document.getElementById('btnGuardar');
        if (!btn) return;

        // En modo solo-lectura, el botón no existe (oculto por PHP)
        // pero si se llegara a llamar, salir inmediatamente
        if (typeof ES_SOLO_LECTURA !== 'undefined' && ES_SOLO_LECTURA) return;

        // No modificar si está bloqueado por novedad vehicular
        var container = document.getElementById('vehiculoSectionsContainer');
        if (container && container.classList.contains('vehiculo-section-blocked')) return;

        // En modo validación no se bloquea
        if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
            btn.disabled = false;
            btn.title = '';
            return;
        }

        var bloquearPorConductor = hayRespuestaNegativa();
        var alertaVencidos = hayDocumentosVencidos();

        if (bloquearPorConductor) {
            btn.disabled = true;
            btn.title = 'No se puede guardar: hay respuestas que impiden realizar el preoperacional';
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';

            var alertaEl = document.getElementById('btnGuardarAlerta');
            if (!alertaEl) {
                alertaEl = document.createElement('div');
                alertaEl.id = 'btnGuardarAlerta';
                alertaEl.className = 'btn-guardar-alerta';
                btn.parentNode.insertBefore(alertaEl, btn.nextSibling);
            }
            if (alertaVencidos) {
                alertaEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' +
                    'Hay documentos del vehículo vencidos y respuestas que impiden realizar el preoperacional. ' +
                    'Comuníquese con el jefe de operaciones.';
            } else {
                alertaEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' +
                    'Por favor, comuníquese con el jefe de operaciones / oficina, ' +
                    'actualmente no se puede realizar el preoperacional si presenta complicaciones.';
            }
            alertaEl.style.display = '';
        } else if (alertaVencidos) {
            // Documentos vencidos: botón habilitado, pero con advertencia visible
            btn.disabled = false;
            btn.title = 'Hay documentos del vehículo vencidos. Puede guardar, pero debe actualizarlos cuanto antes.';
            btn.style.opacity = '';
            btn.style.cursor = '';

            var alertaEl = document.getElementById('btnGuardarAlerta');
            if (!alertaEl) {
                alertaEl = document.createElement('div');
                alertaEl.id = 'btnGuardarAlerta';
                alertaEl.className = 'btn-guardar-alerta btn-guardar-alerta-vencidos';
                btn.parentNode.insertBefore(alertaEl, btn.nextSibling);
            }
            alertaEl.className = 'btn-guardar-alerta btn-guardar-alerta-vencidos';
            alertaEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' +
                'Hay documentos del vehículo vencidos. Puede guardar el preoperacional, ' +
                'pero debe actualizar los documentos cuanto antes.';
            alertaEl.style.display = '';
        } else {
            btn.disabled = false;
            btn.title = '';
            btn.style.opacity = '';
            btn.style.cursor = '';

            var alertaEl = document.getElementById('btnGuardarAlerta');
            if (alertaEl) {
                alertaEl.style.display = 'none';
            }
        }
    }

    /**
     * Deshabilita todos los campos del formulario en modo validación
     * para evitar modificaciones, excepto el textarea de nota de validación
     */
    function disableFormInValidationMode() {
        if (typeof ES_VALIDACION === 'undefined' || !ES_VALIDACION) {
            return;
        }

        // Checkboxes y radios: el servidor ya los envía con disabled.
        // Solo impedir que el foco del teclado caiga sobre ellos.
        // El CSS de .modo-validacion se encarga del aspecto visual
        // (respuestas SI con fondo verde, NO con fondo rojo, opciones
        // no seleccionadas en segundo plano pero legibles).
        var inputs = document.querySelectorAll('.obtener');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].setAttribute('tabindex', '-1');
        }

        // Poner readonly en campos de texto, textareas y número (excepto validation notes)
        var textInputs = document.querySelectorAll('input[type="text"], input[type="number"], textarea');
        for (var j = 0; j < textInputs.length; j++) {
            var el = textInputs[j];
            if (el.type !== 'hidden'
                && el.id !== 'desc_validacion'
                && el.id !== 'observaciones_validacion'
                && el.name !== 'accion_correctiva'
                && el.name !== 'responsable') {
                el.readOnly = true;
            }
        }

        // Deshabilitar inputs de archivo (excepto los de entrega de vehículo en validación)
        var fileInputs = document.querySelectorAll('input[type="file"]');
        for (var k = 0; k < fileInputs.length; k++) {
            fileInputs[k].disabled = true;
        }
    }

    /**
     * Determina si el vehículo actual está FUERA_DE_SERVICIO.
     * @returns {boolean}
     */
    function esVehiculoFueraDeServicio() {
        if (typeof ESTADO_GENERAL_VEHICULO !== 'undefined' && ESTADO_GENERAL_VEHICULO === 'FUERA_DE_SERVICIO') {
            return true;
        }
        var panel = document.getElementById('novedadPanel');
        if (!panel) return false;
        return panel.getAttribute('data-estado') === 'FUERA_DE_SERVICIO';
    }

    /**
     * Verifica el estado del vehículo asignado al cargar la página.
     * Si hay novedad, el panel ya está renderizado desde PHP.
     * Si el vehículo está FUERA_DE_SERVICIO, auto-expande el formulario de novedad.
     */
    function verificarEstadoVehiculo() {
        var idVehiculo = document.getElementById('idvehiculo');
        if (!idVehiculo || !idVehiculo.value || parseInt(idVehiculo.value) <= 0) return;

        var novedadPanel = document.getElementById('novedadPanel');
        if (novedadPanel) {
            var estado = novedadPanel.getAttribute('data-estado');

            if (estado === 'FUERA_DE_SERVICIO') {
                // Auto-expandir el formulario de novedad
                var actionsDiv = document.getElementById('novedadActions');
                var inlineForm = document.getElementById('novedadInlineForm');
                if (actionsDiv) actionsDiv.style.display = 'none';
                if (inlineForm) inlineForm.style.display = '';

                // Pre-seleccionar "No" si existe
                var radioNo = document.querySelector('input[name="puede_operar"][value="no"]');
                if (radioNo && !radioNo.checked) {
                    radioNo.checked = true;
                    radioNo.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Mostrar secciones condicionales
                var secciones = ['novedadObservacionGroup', 'novedadVehiculoGroup',
                                 'novedadFotoGeneralGroup', 'novedadSalidaPhotosGroup'];
                for (var s = 0; s < secciones.length; s++) {
                    var el = document.getElementById(secciones[s]);
                    if (el) el.style.display = '';
                }

                // Ocultar secciones de vehículo inmediatamente
                // (solo para FUERA_DE_SERVICIO, porque el formulario se auto-expande)
                if (typeof ES_VALIDACION === 'undefined' || !ES_VALIDACION) {
                    bloquearSeccionesVehiculo();
                }
            }

            initBotonReportarNovedad();
        }
    }

    /**
     * Bloquea las secciones de vehículo y el botón de guardar
     * cuando hay una novedad vehicular activa sin reportar.
     * Las secciones de usuario (personal/conductor/etc.) permanecen editables.
     */
    function bloquearSeccionesVehiculo() {
        var container = document.getElementById('vehiculoSectionsContainer');
        var novedadInfo = document.getElementById('vehiculoNovedadActivaInfo');
        var noVehicleInfo = document.getElementById('vehiculoNoSeleccionadoInfo');

        // Ocultar secciones de vehículo
        if (container) {
            container.style.display = 'none';
        }

        // Mostrar mensaje de novedad activa
        if (novedadInfo) {
            novedadInfo.style.display = '';
        }

        // Ocultar info de no-vehículo (si estuviera visible)
        if (noVehicleInfo) {
            noVehicleInfo.style.display = 'none';
        }

        // Deshabilitar el botón de guardar principal
        var btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.title = 'Debe reportar la novedad del vehículo antes de guardar.';
            btnGuardar.style.opacity = '0.5';
            btnGuardar.style.cursor = 'not-allowed';

            // Mostrar alerta de novedad debajo del botón
            var alertaEl = document.getElementById('btnGuardarAlerta');
            if (!alertaEl) {
                alertaEl = document.createElement('div');
                alertaEl.id = 'btnGuardarAlerta';
                alertaEl.className = 'btn-guardar-alerta';
                btnGuardar.parentNode.insertBefore(alertaEl, btnGuardar.nextSibling);
            }
            alertaEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' +
                'El vehículo presenta novedades. Debe reportar la novedad antes de continuar con el preoperacional.';
            alertaEl.style.display = '';
        }
    }

    /**
     * Desbloquea las secciones de vehículo y el botón de guardar
     * después de reportar una novedad (SÍ o NO con nuevo vehículo).
     */
    function desbloquearSeccionesVehiculo() {
        var container = document.getElementById('vehiculoSectionsContainer');
        var novedadInfo = document.getElementById('vehiculoNovedadActivaInfo');
        var noVehicleInfo = document.getElementById('vehiculoNoSeleccionadoInfo');

        // Mostrar secciones de vehículo
        if (container) {
            container.style.display = '';
        }

        // Ocultar mensaje de novedad activa
        if (novedadInfo) {
            novedadInfo.style.display = 'none';
        }

        // Ocultar info de no-vehículo
        if (noVehicleInfo) {
            noVehicleInfo.style.display = 'none';
        }

        // Reactivar botón de guardar
        var btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.title = '';
            btnGuardar.style.opacity = '';
            btnGuardar.style.cursor = '';

            // Ocultar alerta de novedad
            var alertaEl = document.getElementById('btnGuardarAlerta');
            if (alertaEl) {
                alertaEl.style.display = 'none';
            }
        }

        // Restaurar el flag para los validadores JS
        MOSTRAR_SECCION_VEHICULO = true;
    }

    /**
     * Oculta las secciones de vehículo y muestra el mensaje informativo
     * cuando el usuario reporta NO y no selecciona un vehículo alternativo.
     */
    function ocultarSeccionesVehiculo() {
        var container = document.getElementById('vehiculoSectionsContainer');
        var infoEl = document.getElementById('vehiculoNoSeleccionadoInfo');
        var novedadInfo = document.getElementById('vehiculoNovedadActivaInfo');

        // Ocultar secciones de vehículo
        if (container) {
            container.style.display = 'none';
        }

        // Mostrar info de no-vehículo
        if (infoEl) infoEl.style.display = '';

        // Ocultar mensaje de novedad activa (si estuviera visible)
        if (novedadInfo) {
            novedadInfo.style.display = 'none';
        }

        // Reactivar botón de guardar (solo aplica a secciones de usuario)
        var btnGuardar = document.getElementById('btnGuardar');
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.title = '';
            btnGuardar.style.opacity = '';
            btnGuardar.style.cursor = '';

            // Ocultar alerta de novedad
            var alertaEl = document.getElementById('btnGuardarAlerta');
            if (alertaEl) {
                alertaEl.style.display = 'none';
            }
        }

        // Actualizar el hidden de idvehiculo y idvehiculo_seleccionado a 0
        var idvField = document.querySelector('input[name="idvehiculo"]');
        if (idvField) idvField.value = '0';
        var selField = document.getElementById('idvehiculo_seleccionado');
        if (selField) selField.value = '0';

        // Informar a los validadores JS que ya no hay sección de vehículo
        MOSTRAR_SECCION_VEHICULO = false;
    }

    /**
     * Inicializa el botón de "Reportar Novedad" — expande el formulario inline
     */
    function initBotonReportarNovedad() {
        var btn = document.getElementById('btnReportarNovedad');
        if (!btn) return;

        btn.addEventListener('click', function() {
            // Ocultar el botón y mostrar el formulario inline
            document.getElementById('novedadActions').style.display = 'none';
            document.getElementById('novedadInlineForm').style.display = '';

            // Bloquear secciones de vehículo mientras se reporta la novedad
            if (typeof ES_VALIDACION === 'undefined' || !ES_VALIDACION) {
                bloquearSeccionesVehiculo();
            }
        });

        // Cancelar: ocultar formulario y mostrar botón de nuevo
        var btnCancelar = document.getElementById('btnCancelarNovedad');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                document.getElementById('novedadInlineForm').style.display = 'none';
                document.getElementById('novedadActions').style.display = '';
                // Resetear el formulario
                var radios = document.getElementsByName('puede_operar');
                for (var i = 0; i < radios.length; i++) radios[i].checked = false;
                document.getElementById('novedadObservacion').value = '';
                document.getElementById('novedadVehiculoSelect').value = '';
                document.getElementById('novedadObservacionGroup').style.display = 'none';
                document.getElementById('novedadVehiculoGroup').style.display = 'none';
                // Resetear inputs file
                var fileInputs = document.querySelectorAll('#novedadInlineForm input[type="file"]');
                for (var j = 0; j < fileInputs.length; j++) fileInputs[j].value = '';
                // Ocultar secciones de foto
                var fotoGen = document.getElementById('novedadFotoGeneralGroup');
                var salidaGrp = document.getElementById('novedadSalidaPhotosGroup');
                var entradaGrp = document.getElementById('novedadEntradaPhotosGroup');
                if (fotoGen) fotoGen.style.display = 'none';
                if (salidaGrp) salidaGrp.style.display = 'none';
                if (entradaGrp) entradaGrp.style.display = 'none';
                // Desbloquear secciones de vehículo al cancelar
                desbloquearSeccionesVehiculo();
            });
        }

        // Radios SÍ/NO: mostrar/ocultar campos condicionales
        var radios = document.getElementsByName('puede_operar');
        for (var i = 0; i < radios.length; i++) {
            radios[i].addEventListener('change', function() {
                var obsGroup  = document.getElementById('novedadObservacionGroup');
                var vehGroup  = document.getElementById('novedadVehiculoGroup');
                var fotoGen   = document.getElementById('novedadFotoGeneralGroup');
                var salidaGrp = document.getElementById('novedadSalidaPhotosGroup');
                var entradaGrp = document.getElementById('novedadEntradaPhotosGroup');

                // Foto general siempre visible al seleccionar cualquier radio
                if (fotoGen) fotoGen.style.display = '';

                if (this.value === 'no') {
                    if (obsGroup)  obsGroup.style.display = '';
                    if (vehGroup)  vehGroup.style.display = '';
                    if (salidaGrp) salidaGrp.style.display = '';
                    // Entrada depende del dropdown
                    var selectVehiculo = document.getElementById('novedadVehiculoSelect');
                    if (entradaGrp && selectVehiculo) {
                        entradaGrp.style.display = (selectVehiculo.value !== '') ? '' : 'none';
                    }
                } else {
                    // SÍ: mostrar campo de observaciones para describir la novedad
                    if (obsGroup)  obsGroup.style.display = '';
                    if (vehGroup)  vehGroup.style.display = 'none';
                    if (salidaGrp) salidaGrp.style.display = 'none';
                    if (entradaGrp) entradaGrp.style.display = 'none';
                }
            });
        }

        // Dropdown de vehículo: mostrar fotos de entrada si NO está seleccionado y hay vehículo
        var selectVehiculo = document.getElementById('novedadVehiculoSelect');
        if (selectVehiculo) {
            selectVehiculo.addEventListener('change', function() {
                var esNo = document.querySelector('input[name="puede_operar"]:checked');
                var esNoValue = esNo ? esNo.value === 'no' : false;
                var entradaGrp = document.getElementById('novedadEntradaPhotosGroup');
                if (entradaGrp) {
                    entradaGrp.style.display = (esNoValue && this.value !== '') ? '' : 'none';
                }
            });
        }

        // Guardar novedad desde el formulario inline
        var btnGuardar = document.getElementById('btnGuardarNovedad');
        if (btnGuardar) {
            btnGuardar.addEventListener('click', function() {
                var seleccion = document.querySelector('input[name="puede_operar"]:checked');
                if (!seleccion) {
                    swalAlert({ title: 'Seleccione una opción', text: 'Debe indicar si el vehículo puede ser operado.', icon: 'warning', confirmButtonText: 'Aceptar' });
                    return;
                }
                var panel = document.getElementById('novedadPanel');
                var idVehiculo = panel ? panel.getAttribute('data-idvehiculo') : '0';
                var datos = {
                    puede_ser_operado: seleccion.value,
                    observaciones: document.getElementById('novedadObservacion') ? document.getElementById('novedadObservacion').value : ''
                };
                guardarNovedad(idVehiculo, datos);
            });
        }
    }

    /**
     * Inicializa el botón de "Asignar Vehículo" en el panel cuando
     * el usuario no tiene vehículo asignado.
     */
    function initBotonAsignarVehiculo() {
        var btn = document.getElementById('btnAsignarVehiculo');
        if (!btn) return;

        btn.addEventListener('click', function() {
            var select = document.getElementById('asignarVehiculoSelect');
            var idVehiculo = select ? select.value : '';

            if (!idVehiculo) {
                swalAlert({
                    title: 'Seleccione un vehículo',
                    text: 'Debe seleccionar un vehículo de la lista.',
                    icon: 'warning',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            var formData = new FormData();
            formData.append('accion', 'asignar_vehiculo_inicial');
            formData.append('idvehiculo', idVehiculo);
            formData.append('id_usuario', document.getElementById('user') ? document.getElementById('user').value : '');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Asignando...';

            fetch((window.APP_BASE_URL ? window.APP_BASE_URL + '/controller/PreoperacionalController.php' : window.location.pathname), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    swalAlert({
                        title: 'Vehículo asignado',
                        text: data.message + ' La página se recargará.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check"></i> Asignar Vehículo';
                    swalAlert({
                        title: 'Error',
                        text: data.message || 'Error al asignar el vehículo.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Asignar Vehículo';
                swalAlert({
                    title: 'Error',
                    text: 'Error de comunicación con el servidor.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        });
    }

    /**
     * Inicializa el sistema de múltiples fotos en novedad vehicular.
     * Permite agregar campos de foto dinámicamente con preview.
     */
    function initNovedadMultiPhoto() {
        var container = document.getElementById('novedadMultiPhotoContainer');
        if (!container) return;

        // Delegación de eventos para el botón de agregar
        var btnAgregar = document.getElementById('btnAgregarFotoNovedad');
        if (!btnAgregar) return;

        btnAgregar.addEventListener('click', function() {
            var items = container.querySelectorAll('.photo-item');
            var nuevoIndex = items.length + 1;
            var template = items[0].cloneNode(true);

            // Limpiar el input y preview
            var input = template.querySelector('.novedad-photo-input');
            if (input) {
                input.value = '';
                input.removeAttribute('data-required-photo');
            }
            var preview = template.querySelector('.photo-preview');
            if (preview) preview.innerHTML = '';

            // Actualizar label
            var label = template.querySelector('.photo-label');
            if (label) {
                var labelText = label.innerHTML.replace(/\d+/, nuevoIndex);
                label.innerHTML = labelText;
            }

            // Agregar botón de eliminar
            var deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-sm btn-outline-danger mt-1';
            deleteBtn.innerHTML = '<i class="fas fa-times"></i> Quitar foto';
            deleteBtn.addEventListener('click', function() {
                template.remove();
                // Renumerar labels
                var remaining = container.querySelectorAll('.photo-item');
                remaining.forEach(function(item, idx) {
                    var lbl = item.querySelector('.photo-label');
                    if (lbl) {
                        lbl.innerHTML = lbl.innerHTML.replace(/\d+/, idx + 1);
                    }
                });
            });
            template.querySelector('.photo-upload-container').appendChild(deleteBtn);

            container.appendChild(template);
        });

        // Preview de imagen al seleccionar archivo (delegado)
        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('novedad-photo-input')) {
                var preview = e.target.closest('.photo-item').querySelector('.photo-preview');
                if (preview && e.target.files && e.target.files[0]) {
                    preview.innerHTML = '';
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(e.target.files[0]);
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '120px';
                    img.style.borderRadius = '6px';
                    img.style.border = '1px solid rgba(0,0,0,0.1)';
                    preview.appendChild(img);
                }
            }
        });

        // Preview para el primer input también
        var firstInput = container.querySelector('.novedad-photo-input');
        if (firstInput) {
            firstInput.addEventListener('change', function(e) {
                var preview = e.target.closest('.photo-item').querySelector('.photo-preview');
                if (preview && e.target.files && e.target.files[0]) {
                    preview.innerHTML = '';
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(e.target.files[0]);
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '120px';
                    img.style.borderRadius = '6px';
                    img.style.border = '1px solid rgba(0,0,0,0.1)';
                    preview.appendChild(img);
                }
            });
        }
    }

    /**
     * Envía el reporte de novedad al servidor desde el formulario inline
     */
    function guardarNovedad(idVehiculo, datos) {
        var seleccion = document.querySelector('input[name="puede_operar"]:checked');
        if (!seleccion) return;

        // --- VALIDACIÓN DE FOTOS MÚLTIPLES ---
        // Al menos una foto de evidencia requerida
        var fotosEvidencia = document.querySelectorAll('.novedad-photo-input');
        var tieneFoto = false;
        for (var fi = 0; fi < fotosEvidencia.length; fi++) {
            if (fotosEvidencia[fi].files && fotosEvidencia[fi].files.length > 0) {
                tieneFoto = true;
                break;
            }
        }
        if (!tieneFoto) {
            swalAlert({ title: 'Foto requerida', text: 'Debe subir al menos una foto de evidencia general.', icon: 'warning', confirmButtonText: 'Aceptar' });
            var panel = document.getElementById('novedadPanel');
            if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // --- BUILD FORMDATA ---
        var formData = new FormData();
        formData.append('accion', 'reportar_novedad');
        formData.append('idvehiculo_actual', idVehiculo);
        formData.append('puede_ser_operado', datos.puede_ser_operado);
        formData.append('observaciones', datos.observaciones);
        formData.append('id_usuario', document.getElementById('user') ? document.getElementById('user').value : '');

        // Append fotos múltiples de evidencia
        var todosLosInputsFoto = document.querySelectorAll('.novedad-photo-input');
        for (var fi = 0; fi < todosLosInputsFoto.length; fi++) {
            if (todosLosInputsFoto[fi].files && todosLosInputsFoto[fi].files.length > 0) {
                formData.append('novedad_fotos[]', todosLosInputsFoto[fi].files[0]);
            }
        }

        var btnGuardar = document.getElementById('btnGuardarNovedad');
        var btnCancelar = document.getElementById('btnCancelarNovedad');
        if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }
        if (btnCancelar) btnCancelar.disabled = true;

        fetch((window.APP_BASE_URL ? window.APP_BASE_URL + '/controller/PreoperacionalController.php' : window.location.pathname), {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar Novedad'; }
            if (btnCancelar) btnCancelar.disabled = false;

            if (data.success) {
                if (data.idvehiculo_final && parseInt(data.idvehiculo_final) > 0) {
                    // SÍ → el vehículo se mantiene: desbloquear secciones normalmente
                    var inlineForm = document.getElementById('novedadInlineForm');
                    if (inlineForm) inlineForm.style.display = 'none';
                    var actionsDiv = document.getElementById('novedadActions');
                    if (actionsDiv) actionsDiv.style.display = '';

                    var idvField = document.querySelector('input[name="idvehiculo"]');
                    if (idvField) idvField.value = data.idvehiculo_final;
                    var selField = document.getElementById('idvehiculo_seleccionado');
                    if (selField) selField.value = data.idvehiculo_final;
                    desbloquearSeccionesVehiculo();

                    swalAlert({ title: 'Novedad registrada', text: data.message, icon: 'success', confirmButtonText: 'Aceptar', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                } else {
                    // FUERA_DE_SERVICIO sin vehículo → recargar para reflejar nuevo estado
                    swalAlert({ title: 'Vehículo fuera de servicio', text: data.message + ' La página se recargará.', icon: 'info', confirmButtonText: 'Aceptar' }).then(function() { location.reload(); });
                }
            } else {
                swalAlert({ title: 'Error', text: data.message || 'Error al procesar la novedad.', icon: 'error', confirmButtonText: 'Aceptar' });
            }
        })
        .catch(function() {
            if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.innerHTML = '<i class="fas fa-save"></i> Guardar Novedad'; }
            if (btnCancelar) btnCancelar.disabled = false;
            swalAlert({ title: 'Error', text: 'Error de comunicación con el servidor.', icon: 'error', confirmButtonText: 'Aceptar' });
        });
    }

    /**
     * Valida que se hayan subido las 4 fotos de entrega en modo validación
     */
    /**
     * Maneja el envío del formulario
     */
    function handleSubmitForm() {
        var form = document.getElementById('formPreoperacional');
        if (!form) {
            return;
        }
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            // En modo solo-lectura, no se puede enviar el formulario
            if (typeof ES_SOLO_LECTURA !== 'undefined' && ES_SOLO_LECTURA) return;

            // Defensa: eliminar el atributo required de cualquier input que esté
            // disabled u oculto. Un input required+disabled+hidden provoca el error
            // "An invalid form control with name='...' is not focusable" del navegador.
            // Esto puede ocurrir cuando las secciones de vehículo se ocultaron vía JS
            // (por ej. después de reportar FUERA_DE_SERVICIO sin vehículo alternativo)
            // pero el DOM aún contiene los inputs con required del renderizado inicial.
            var allRequired = form.querySelectorAll('[required]');
            for (var ri = 0; ri < allRequired.length; ri++) {
                var el = allRequired[ri];
                if (el.disabled || el.offsetParent === null) {
                    el.removeAttribute('required');
                }
            }

            var esValidacion = (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION);

            // Defensa: si el vehículo está FUERA_DE_SERVICIO y las secciones siguen ocultas,
            // el usuario no ha resuelto la novedad — bloquear envío
            if (!esValidacion && esVehiculoFueraDeServicio()) {
                var container = document.getElementById('vehiculoSectionsContainer');
                var novedadInfo = document.getElementById('vehiculoNovedadActivaInfo');
                if (container && container.style.display === 'none' && novedadInfo && novedadInfo.style.display !== 'none') {
                    await swalAlert({
                        title: 'Vehículo Fuera de Servicio',
                        html: '<div style="text-align:left; font-size:1.05em;">' +
                            '<p><strong>El vehículo asignado se encuentra <span style="color:#dc3545;">FUERA DE SERVICIO</span>.</strong></p>' +
                            '<p>Debe <strong>reportar la novedad</strong> y seleccionar otro vehículo antes de continuar con el preoperacional.</p>' +
                            '</div>',
                        icon: 'warning',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#dc3545',
                        allowOutsideClick: false,
                        width: '38em'
                    });
                    return;
                }
            }

            // En modo validación, omitir validaciones de checkboxes, fotos, firma y ubicación
            if (!esValidacion) {
                // Mostrar advertencia si hay documentos del vehículo vencidos (permite continuar)
                await validarDocumentosVehiculo();

                // Validar respuestas negativas del conductor (bloquea el preoperacional)
                if (!validarRespuestasConductor()) {
                    return;
                }

                // Validar que se hayan respondedido todos los checkboxes binarios
                if (!validarCheckboxesBinarios()) {
                    return;
                }

                // Validar fotos requeridas
                if (!validarFotosRequeridas()) {
                    return;
                }

                // Validar firma
                if (!validarFirma()) {
                    return;
                }

                // Validar kilometraje
                if (!validarKilometraje()) {
                    return;
                }

                // Validar imagen de kilometraje
                if (!validarImagenKilometraje()) {
                    return;
                }

                // Validar ubicación GPS (obligatorio)
                if (!validarUbicacion()) {
                    return;
                }
            }

            if (!asignar()) {
                return;
            }

            // Agregar coordenadas de ubicación al JSON de datos
            if (ubicacionCoords) {
                var dataField = document.getElementById('data');
                try {
                    var dataObj = JSON.parse(dataField.value);
                    dataObj.ubicacion = ubicacionCoords.lat.toFixed(6) + ',' + ubicacionCoords.lng.toFixed(6);
                    dataField.value = JSON.stringify(dataObj);
                } catch (e) {
                    // Si falla el parseo, el JSON seguirá sin ubicación
                }
            }

            // Limpiar separadores de miles (puntos) del kilometraje antes de enviar
            var kmField = document.getElementById('kilometraje');
            if (kmField) {
                kmField.value = kmField.value.replace(/\./g, '');
            }

            var formData = new FormData(this);
            formData.append('accion', 'guardar');
            formData.append('ajax', '1');

            // Si hay un vehículo seleccionado (cambio voluntario), incluir datos cacheados
            if (_vehiculoSeleccionado && _vehiculoSeleccionado.id) {
                formData.append('idvehiculo_seleccionado', _vehiculoSeleccionado.id);
                formData.append('seleccion_observaciones', _vehiculoSeleccionado.observaciones || '');

                // Adjuntar fotos cacheadas (File objects)
                if (_vehiculoSeleccionado.files) {
                    Object.keys(_vehiculoSeleccionado.files).forEach(function(key) {
                        formData.append(key, _vehiculoSeleccionado.files[key]);
                    });
                }
            }

            var btn = document.getElementById('btnGuardar');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }

            swalAlert({
                title: 'Guardando preoperacional',
                text: 'Por favor espere un momento.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                _iframeLoading: true
            });

            fetch((window.APP_BASE_URL ? window.APP_BASE_URL + '/controller/PreoperacionalController.php' : window.location.pathname), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save me-2"></i> Guardar';
                    }

                    if (data.success) {
                        if (window.parent && window.parent !== window) {
                            swalAlert({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: data.message || 'Preoperacional guardado.',
                                showConfirmButton: false,
                                timer: 1200,
                                timerProgressBar: true,
                                didClose: function () {
                                    if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
                                        try {
                                            window.parent.location.reload();
                                        } catch (e) {
                                            window.location.reload();
                                        }
                                        return;
                                    }

                                    if (typeof URL_REDIRECT !== 'undefined' && URL_REDIRECT) {
                                        var redirectUrlIframe = new URL(URL_REDIRECT, window.location.href).href;
                                        try {
                                            window.parent.location.href = redirectUrlIframe;
                                        } catch (e) {
                                            window.location.href = redirectUrlIframe;
                                        }
                                    } else {
                                        window.location.reload();
                                    }
                                }
                            });
                            return;
                        }

                        swalAlert({
                            title: 'Éxito',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(function (result) {
                            if (!result.isConfirmed) {
                                return;
                            }

                            if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
                                window.close();
                            } else if (typeof URL_REDIRECT !== 'undefined' && URL_REDIRECT) {
                                var redirectUrl = new URL(URL_REDIRECT, window.location.href).href;

                                if (window.parent && window.parent !== window) {
                                    window.parent.location.href = redirectUrl;
                                } else {
                                    window.location.href = redirectUrl;
                                }
                            } else {
                                window.close();
                            }
                        });
                    } else {
                        swalAlert({
                            title: 'Error',
                            text: data.message || 'Ocurrió un error al guardar.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            allowOutsideClick: false
                        });
                    }
                })
                .catch(function (error) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save me-2"></i> Guardar';
                    }

                    swalAlert({
                        title: 'Error',
                        text: 'Error de comunicación con el servidor.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false
                    });
                });
        });
    }

    /**
     * Precarga los datos guardados en el formulario
     */
    function cargarDatosPrecarga() {
        var estadoInput = document.getElementById("estado");
        var userInput = document.getElementById("user");
        var fechaInput = document.getElementById("fecha");
        var campoInput = document.getElementById("campo");

        if (!estadoInput || !userInput || !fechaInput || !campoInput) return;

        var valida = estadoInput.value;
        var iduser = userInput.value;
        var fecha = fechaInput.value;
        var campo = campoInput.value;

        if (valida !== 'ingresado' && valida !== 'covid19') return;
        if (!campo) return;

        $.ajax({
            url: (window.APP_BASE_URL ? window.APP_BASE_URL + '/controller/PreoperacionalController.php' : window.location.pathname),
            type: "POST",
            data: { user: iduser, fecha: fecha, campo: campo, accion: "buscarDatos" },
            dataType: "json"
        }).done(function (respuesta) {
            if (respuesta != null && respuesta !== '') {
                for (var name in respuesta) {
                    if (!respuesta.hasOwnProperty(name)) continue;
                    var value = respuesta[name];
                    var inputs = document.getElementsByName(name);

                    if (!inputs || inputs.length === 0) continue;

                    for (var b = 0; b < inputs.length; b++) {
                        var input = inputs[b];
                        if (input.value == value) {
                            input.checked = true;

                            // Mostrar fila de foto si aplica (NO = valor 2)
                            if (value === '2') {
                                var photoRow = document.getElementById(name + '_photo_row');
                                if (photoRow) {
                                    photoRow.style.display = '';
                                }
                            }
                        }
                    }
                }
                // Actualizar warnings de conductor tras precarga
                actualizarTodosWarningsConductor();
            }
        });
    }

    /**
     * Inicializa el canvas de firma a trazo
     * Permite dibujar con mouse o touch
     */
    function initSignatureCanvas() {
        var canvas = document.getElementById('signatureCanvas');
        if (!canvas) {
            return;
        }

        var ctx = canvas.getContext('2d');
        var isDrawing = false;
        var lastX = 0;
        var lastY = 0;
        var hasSignature = false;

        // Configuración del trazo
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        /**
         * Obtiene las coordenadas relativas al canvas
         */
        function getCoordinates(e) {
            var rect = canvas.getBoundingClientRect();
            var scaleX = canvas.width / rect.width;
            var scaleY = canvas.height / rect.height;

            if (e.type.includes('touch')) {
                var touch = e.touches[0];
                return {
                    x: (touch.clientX - rect.left) * scaleX,
                    y: (touch.clientY - rect.top) * scaleY
                };
            } else {
                return {
                    x: (e.clientX - rect.left) * scaleX,
                    y: (e.clientY - rect.top) * scaleY
                };
            }
        }

        /**
         * Inicia el dibujo
         */
        function startDrawing(e) {
            isDrawing = true;
            hasSignature = true;
            canvas.setAttribute('data-has-signature', 'true');
            var coords = getCoordinates(e);
            lastX = coords.x;
            lastY = coords.y;
            e.preventDefault();
        }

        /**
         * Dibuja la línea
         */
        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();

            var coords = getCoordinates(e);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(coords.x, coords.y);
            ctx.stroke();

            lastX = coords.x;
            lastY = coords.y;
        }

        /**
         * Detiene el dibujo
         */
        function stopDrawing() {
            isDrawing = false;
        }

        /**
         * Limpia el canvas
         */
        function clearCanvas() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            canvas.removeAttribute('data-has-signature');
            var hiddenInput = document.getElementById('firma_preoperacional');
            if (hiddenInput) {
                hiddenInput.value = '';
            }
        }

        // Event listeners para mouse
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        // Event listeners para touch (dispositivos móviles)
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);
        canvas.addEventListener('touchcancel', stopDrawing);

        // Botón de limpiar firma
        var btnClear = document.getElementById('btnClearSignature');
        if (btnClear) {
            btnClear.addEventListener('click', clearCanvas);
        }

        // Guardar la firma como base64 antes de enviar el formulario
        var form = document.getElementById('formPreoperacional');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (hasSignature) {
                    var dataURL = canvas.toDataURL('image/png');
                    var hiddenInput = document.getElementById('firma_preoperacional');
                    if (hiddenInput) {
                        hiddenInput.value = dataURL;
                    }
                } else {
                    var hiddenInput = document.getElementById('firma_preoperacional');
                    if (hiddenInput) {
                        hiddenInput.value = '';
                    }
                }
            });
        }
    }


    /**
     * Inicializa el botón de "Seleccionar otro vehículo para hoy".
     * Muestra el panel de selección; la selección se cachea en
     * _vehiculoSeleccionado y se envía junto con el preoperacional.
     */
    function initBotonSeleccionDiaria() {
        var btn = document.getElementById('btnSeleccionarVehiculoDiario');
        if (!btn) return;

        btn.addEventListener('click', function() {
            var selectContainer = document.getElementById('vehiculoDiarioSelectContainer');
            if (selectContainer) {
                selectContainer.style.display = '';
                selectContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    /**
     * Inicializa el panel de selección diaria de vehículo.
     * En lugar de enviar al servidor inmediatamente, cachea la selección
     * en _vehiculoSeleccionado para enviarla junto con el preoperacional.
     * Al seleccionar, obtiene datos del vehículo vía AJAX y actualiza
     * la tarjeta de info, kilometraje y alertas de documentos.
     */
    function initSubmitSeleccionDiaria() {
        var btnConfirmar = document.getElementById('btnGuardarSeleccionDiaria');
        var btnCancelar = document.getElementById('btnCancelarSeleccionDiaria');
        var container = document.getElementById('vehiculoDiarioSelectContainer');

        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', function() {
                var select = document.getElementById('diariaVehiculoSelect');
                if (!select) {
                    swalAlert({ title: 'Error', text: 'No hay vehículos disponibles para seleccionar.', icon: 'error', confirmButtonText: 'Aceptar' });
                    return;
                }
                var idVehiculo = select.value;
                if (!idVehiculo) {
                    swalAlert({ title: 'Seleccione un vehículo', text: 'Debe elegir un vehículo de la lista.', icon: 'warning', confirmButtonText: 'Aceptar' });
                    return;
                }

                // Mostrar indicador de carga en el botón
                btnConfirmar.disabled = true;
                btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';

                // Cachear archivos de foto y observaciones
                var fotos = ['diaria_frente', 'diaria_trasera', 'diaria_lateral_izq', 'diaria_lateral_der'];
                _vehiculoSeleccionado.files = {};
                fotos.forEach(function(nombre) {
                    var input = document.querySelector('input[name="' + nombre + '"]');
                    if (input && input.files && input.files[0]) {
                        _vehiculoSeleccionado.files[nombre] = input.files[0];
                    }
                });
                var obs = document.getElementById('diariaObservacion');
                _vehiculoSeleccionado.observaciones = obs && obs.value.trim() ? obs.value.trim() : '';

                // Obtener datos completos del vehículo seleccionado para actualizar la UI
                var formData = new FormData();
                formData.append('accion', 'obtener_datos_vehiculo');
                formData.append('idvehiculo', idVehiculo);

                fetch((window.APP_BASE_URL ? window.APP_BASE_URL + '/controller/PreoperacionalController.php' : window.location.pathname), {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Seleccionar vehículo para hoy';

                    if (!data.success || !data.data) {
                        swalAlert({ title: 'Error', text: 'No se pudieron obtener los datos del vehículo.', icon: 'error', confirmButtonText: 'Aceptar' });
                        return;
                    }

                    var v = data.data;

                    // Cachear el ID del vehículo seleccionado (solo si la carga fue exitosa)
                    _vehiculoSeleccionado.id = idVehiculo;

                    // Actualizar los hidden fields
                    // NOTA: NO se actualiza input[name="idvehiculo"] — ese debe conservar
                    // el vehículo ORIGINAL para que el servidor detecte el cambio.
                    // Solo se actualiza idvehiculo_seleccionado.
                    var selField = document.getElementById('idvehiculo_seleccionado');
                    if (selField) selField.value = idVehiculo;

                    // ---- Actualizar tarjeta de info del vehículo ----
                    var vehicleCard = document.querySelector('.preop-card.vehicle-info');
                    if (vehicleCard) {
                        var grid = vehicleCard.querySelector('.vehicle-info-grid');
                        if (grid) {
                            // Reconstruir grid con datos del vehículo seleccionado
                            var campos = {
                                'PLACA': v.veh_placa || '',
                                'MARCA': v.veh_marca || '',
                                'MODELO': v.veh_modelo || '',
                                'KM': v.veh_kilactual || '',
                                'SEGURO VENCE': v.veh_fechaseguro || '',
                                'TECNOMECÁNICA VENCE': v.veh_fechategnomecanica || ''
                            };
                            grid.innerHTML = '';
                            Object.keys(campos).forEach(function(label) {
                                if (campos[label]) {
                                    var div = document.createElement('div');
                                    div.className = 'vehicle-info-item';
                                    div.innerHTML = '<strong>' + label + '</strong><span>' + campos[label] + '</span>';
                                    grid.appendChild(div);
                                }
                            });
                        }

                        // Actualizar clase de severidad en la tarjeta
                        var sev = v.max_severity || 0;
                        vehicleCard.className = vehicleCard.className.replace(/severity-\S*/g, '').trim();
                        if (sev >= 3) vehicleCard.classList.add('severity-expired');
                        else if (sev >= 2) vehicleCard.classList.add('severity-critical');
                        else if (sev >= 1) vehicleCard.classList.add('severity-warning');
                    }

                    // ---- Reemplazar alertas de documentos con las del nuevo vehículo ----
                    var docsPanel = document.querySelector('.vehiculo-docs-panel');
                    if (docsPanel && v.alertas_html) {
                        docsPanel.outerHTML = v.alertas_html;
                    } else if (docsPanel && !v.alertas_html) {
                        docsPanel.style.display = 'none';
                    }

                    // ---- Actualizar ULTIMO_KM para validación de kilometraje ----
                    if (v.veh_kilactual) {
                        var kmInt = parseInt(v.veh_kilactual, 10);
                        ULTIMO_KM = kmInt;
                        var kmInput = document.getElementById('kilometraje');
                        if (kmInput) {
                            kmInput.placeholder = 'Último: ' + kmInt.toLocaleString('es-CO') + ' km';
                        }
                        // Actualizar el texto "Último km registrado" en la tarjeta de kilometraje
                        var lastKmInfo = document.querySelector('.last-km-info strong');
                        if (lastKmInfo) {
                            lastKmInfo.textContent = kmInt.toLocaleString('es-CO');
                        }
                    }

                    // ---- Actualizar badge de cambio temporal y ocultar panel ----
                    var option = select.options[select.selectedIndex];
                    var placa = v.veh_placa || 'N/A';
                    if (option) {
                        var textParts = option.text.split(' - ');
                        placa = textParts[1] || placa;
                    }
                    var novedadPanel = document.getElementById('novedadPanel');
                    if (novedadPanel) {
                        var existingBadge = novedadPanel.querySelector('.cambio-temp-badge');
                        if (!existingBadge) {
                            var badge = document.createElement('div');
                            badge.className = 'cambio-temp-badge';
                            badge.innerHTML = '<span style="display:inline-block;background:#e3f2fd;color:#1565c0;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;margin-top:8px;"><i class="fas fa-exchange-alt"></i> Vehículo cambiado temporalmente a ' + placa + '</span>';
                            novedadPanel.querySelector('.preop-card-body').appendChild(badge);
                        }
                    }

                    // Ocultar el panel de selección
                    if (container) container.style.display = 'none';

                    swalAlert({
                        title: 'Vehículo seleccionado',
                        text: 'Complete el formulario y guarde para registrar el preoperacional.',
                        icon: 'success',
                        confirmButtonText: 'Continuar',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500
                    });
                })
                .catch(function() {
                    btnConfirmar.disabled = false;
                    btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Seleccionar vehículo para hoy';
                    swalAlert({ title: 'Error', text: 'Error de comunicación al obtener datos del vehículo.', icon: 'error', confirmButtonText: 'Aceptar' });
                });
            });
        }

        if (btnCancelar && container) {
            btnCancelar.addEventListener('click', function() {
                container.style.display = 'none';
            });
        }
    }

    /**
     * Inicialización cuando el DOM está listo
     */
    function init() {
        try {
            // Inicializar bandera de alerta de kilometraje
            window._kmWarningAcknowledged = false;
            initBinaryCheckboxes();
            initKilometrajeFormatting();
            initDriverWarnings();
            handleSubmitForm();
            cargarDatosPrecarga();
            verificarEstadoVehiculo();
            initBotonAsignarVehiculo();
            initNovedadMultiPhoto();
            initBotonSeleccionDiaria();
            initSubmitSeleccionDiaria();

            var esLegado = !document.getElementById('ubicacion_container');

            if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
                disableFormInValidationMode();
                // Modo validación: renderizar mapa estático con coordenadas guardadas
                if (!esLegado && typeof UBICACION_GUARDADA !== 'undefined' && UBICACION_GUARDADA) {
                    var partes = UBICACION_GUARDADA.split(',');
                    if (partes.length === 2) {
                        var lat = parseFloat(partes[0]);
                        var lng = parseFloat(partes[1]);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            // Las coordenadas se almacenan para posible referencia
                            ubicacionCoords = { lat: lat, lng: lng };
                            renderizarMapa(lat, lng, 'mapa_ubicacion', false);
                        }
                    }
                }
            } else if (!esLegado) {
                initSignatureCanvas();
                // Iniciar detección de ubicación (obligatorio para nuevo registro)
                obtenerUbicacion();
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
