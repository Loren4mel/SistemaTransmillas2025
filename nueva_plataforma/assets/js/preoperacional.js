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
     * Maneja el cambio en checkboxes de vehículo (checkbox único SÍ/NO)
     * Lógica:
     * - Si checkbox SÍ (OK) está marcado: ocultar campo de foto
     * - Si checkbox SÍ (OK) NO está marcado: mostrar campo de foto (si aplica)
     */
    function initCheckboxObservers() {
        // Delegación de eventos para checkboxes dinámicos
        document.addEventListener('change', function(e) {
            // Manejar checkbox SÍ (OK) único (vehículos)
            if (e.target.classList.contains('checkbox-ok') && !e.target.classList.contains('checkbox-binary')) {
                var checkboxName = e.target.getAttribute('data-name');
                var isChecked = e.target.checked;

                // Mostrar/ocultar campo de foto si existe
                var photoRow = document.getElementById(checkboxName + '_photo_row');
                if (photoRow) {
                    photoRow.style.display = isChecked ? 'none' : '';
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
     * Maneja el envío del formulario
     */
    function handleSubmitForm() {
        var form = document.getElementById('formPreoperacional');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validar que se hayan respondido todos los checkboxes binarios
            if (!validarCheckboxesBinarios()) {
                return;
            }

            // Validar fotos requeridas
            if (!validarFotosRequeridas()) {
                return;
            }

            if (!asignar()) return;

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
                            window.location.href = 'inicio.php?bandera=1';
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
     * Precarga los datos guardados y resalta las diferencias
     */
    function cargarDatosPrecarga() {
        var estadoInput = document.getElementById("estado");
        var userInput = document.getElementById("user");
        var fechaInput = document.getElementById("fecha");
        var campoInput = document.getElementById("campo");
        var param3Input = document.getElementById("param3");

        if (!estadoInput || !userInput || !fechaInput || !campoInput) return;

        var valida = estadoInput.value;
        var iduser = userInput.value;
        var fecha = fechaInput.value;
        var campo = campoInput.value;
        var tipovehiculo = param3Input ? param3Input.value : '0';

        if (valida !== 'ingresado' && valida !== 'covid19') return;
        if (!campo) return;

        // Valores esperados para moto (si la respuesta es diferente, se resalta)
        var valoresmoto = {};
        valoresmoto['llantas1'] = '2';
        valoresmoto['llantas2'] = '2';
        valoresmoto['llantas4'] = '2';
        valoresmoto['llantas5'] = '1';
        valoresmoto['llantas6'] = '1';
        valoresmoto['transmision1'] = '2';
        valoresmoto['transmision2'] = '2';
        valoresmoto['Luces1'] = '1';
        valoresmoto['Luces2'] = '1';
        valoresmoto['Luces3'] = '1';
        valoresmoto['fugas1'] = '2';
        valoresmoto['fugas2'] = '2';
        valoresmoto['fugas3'] = '2';
        valoresmoto['fugas4'] = '2';
        valoresmoto['fugas5'] = '2';
        valoresmoto['fugas6'] = '2';
        valoresmoto['mandos1'] = '2';
        valoresmoto['mandos2'] = '1';
        valoresmoto['entorno1'] = '1';
        valoresmoto['entorno2'] = '2';
        valoresmoto['entorno3'] = '1';
        valoresmoto['elementos1'] = '1';
        valoresmoto['elementos2'] = '1';
        valoresmoto['elementos3'] = '1';
        valoresmoto['elementos4'] = '1';
        valoresmoto['elementos5'] = '1';
        valoresmoto['elementos6'] = '1';

        $.ajax({
            url: window.location.pathname,
            type: "POST",
            data: { user: iduser, fecha: fecha, campo: campo, accion: "buscarDatos" },
            dataType: "json"
        }).done(function (respuesta) {
            if (respuesta != null && respuesta !== '') {
                for (var i in respuesta) {
                    var value = respuesta[i];
                    var tamano = document.getElementsByName(i);

                    for (b = 0; b < tamano.length; b++) {
                        var valor = tamano[b].value;
                        if (valor == value) {
                            tamano[b].checked = true;

                            // Colorear fila si la respuesta no es la esperada
                            if (tipovehiculo == 'MOTO') {
                                if (value != valoresmoto[i]) {
                                    var row = document.getElementById(i + '0');
                                    if (row) row.style.backgroundColor = "#e4605e";
                                }
                            } else if (tipovehiculo == 'CARRO') {
                                if (value != '1') {
                                    var row = document.getElementById(i + '0');
                                    if (row) row.style.backgroundColor = "#e4605e";
                                }
                            } else {
                                // Modo COVID: pintar rojo si respuesta es SI (1) en primeras 7
                                if (valida == 'covid19' && i.substr(0, 6) == 'covid1' && value == '1') {
                                    var row = document.getElementById(i + '0');
                                    if (row) row.style.backgroundColor = "#e4605e";
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
        if (!canvas) return;

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
                }
            });
        }
    }

    /**
     * Inicialización cuando el DOM está listo
     */
    function init() {
        initBinaryCheckboxes();    // Para checkboxes binarios (SÍ/NO)
        initCheckboxObservers();   // Para checkboxes de vehículo único
        handleSubmitForm();
        cargarDatosPrecarga();
        initSignatureCanvas();
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
