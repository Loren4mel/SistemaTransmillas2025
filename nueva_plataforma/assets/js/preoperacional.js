/**
 * Preoperacional - Lógica del formulario de preoperacional
 *
 * Maneja el envío del formulario, precarga de datos y validaciones
 * Incluye soporte para subida de fotos en inspección inicial
 * Soporte para checkboxes de vehículo con observaciones
 */

(function () {
    'use strict';

    // Capturar errores globales
    window.addEventListener('error', function(e) {
        console.error('Error global en Preoperacional JS:', e.message, 'en', e.filename, 'línea', e.lineno);
    });

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
            // Para checkboxes de vehículo SÍ (OK) - checkbox único
            else if (input.type === 'checkbox' && input.classList.contains('checkbox-ok')) {
                var checkboxName = input.getAttribute('data-name');
                var value = input.checked ? '1' : '0'; // 1 = SÍ (OK), 0 = NO (no OK)
                conver = conver + '"' + checkboxName + '":' + '"' + value + '",';
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

        // 2. Validar checkboxes de vehículo único (SÍ/NO) - cuando no está marcado
        var vehicleCheckboxes = document.querySelectorAll('.checkbox-ok:not(.checkbox-binary)');
        for (var j = 0; j < vehicleCheckboxes.length; j++) {
            var checkbox = vehicleCheckboxes[j];
            var checkboxName = checkbox.getAttribute('data-name');
            var isChecked = checkbox.checked;
            var fileInput = document.getElementById(checkboxName + '_foto');
            if (fileInput) {
                var photoRow = document.getElementById(checkboxName + '_photo_row');
                if (!isChecked && photoRow && photoRow.style.display !== 'none') {
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

        if (errores.length > 0) {
            var errorHtml = '<strong>Debe subir fotografías para las siguientes observaciones:</strong><br><br>';
            for (var k = 0; k < errores.length; k++) {
                errorHtml += '• ' + errores[k] + '<br>';
            }

            Swal.fire({
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

            Swal.fire({
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

        var firmaValor = firmaInput.value.trim();

        if (!firmaValor) {
            Swal.fire({
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
     * Deshabilita todos los campos del formulario en modo validación
     * para evitar modificaciones, excepto el textarea de nota de validación
     */
    function disableFormInValidationMode() {
        if (typeof ES_VALIDACION === 'undefined' || !ES_VALIDACION) {
            return;
        }

        // Marcar checkboxes y radios con clase CSS (sin disabled para preservar estilo visual)
        var inputs = document.querySelectorAll('.obtener');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].classList.add('validation-disabled');
            inputs[i].setAttribute('tabindex', '-1');
        }

        // Poner readonly en campos de texto, textareas y número (excepto validation note)
        var textInputs = document.querySelectorAll('input[type="text"], input[type="number"], textarea');
        for (var j = 0; j < textInputs.length; j++) {
            var el = textInputs[j];
            // No deshabilitar campos ocultos ni el textarea de validación
            if (el.type !== 'hidden' && el.id !== 'desc_validacion' && el.id !== 'observaciones_validacion') {
                el.readOnly = true;
            }
        }

        // Deshabilitar inputs de archivo
        var fileInputs = document.querySelectorAll('input[type="file"]');
        for (var k = 0; k < fileInputs.length; k++) {
            fileInputs[k].disabled = true;
        }
    }

    /**
     * Maneja el envío del formulario
     */
    function handleSubmitForm() {
        var form = document.getElementById('formPreoperacional');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var esValidacion = (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION);

            // En modo validación, omitir validaciones de checkboxes, fotos y firma
            if (!esValidacion) {
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
            }

            if (!asignar()) {
                return;
            }

            var formData = new FormData(this);
            formData.append('accion', 'guardar');
            formData.append('ajax', '1');

            var btn = document.getElementById('btnGuardar');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }

            fetch(window.location.pathname, {
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
                        btn.innerHTML = 'Guardar';
                    }

                    if (data.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(function () {
                            if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
                                window.close();
                            } else if (typeof URL_REDIRECT !== 'undefined' && URL_REDIRECT) {
                                window.location.href = URL_REDIRECT;
                            } else {
                                window.close();
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Ocurrió un error al guardar.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                        if (data.debug_errors) {
                            console.warn(data.debug_errors);
                        }
                    }
                })
                .catch(function (error) {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'Guardar';
                    }

                    Swal.fire({
                        title: 'Error',
                        text: 'Error de comunicación con el servidor.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    console.error(error);
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
            url: window.location.pathname,
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
     * Inicialización cuando el DOM está listo
     */
    function init() {
        try {
            initBinaryCheckboxes();
            handleSubmitForm();
            cargarDatosPrecarga();

            // En modo validación: deshabilitar formulario y mostrar firma como imagen
            if (typeof ES_VALIDACION !== 'undefined' && ES_VALIDACION) {
                disableFormInValidationMode();
            } else {
                initSignatureCanvas();
            }
        } catch (error) {
            console.error('Preoperacional JS: Error durante la inicialización:', error);
        }
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
