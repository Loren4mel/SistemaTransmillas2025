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
     * Maneja el cambio en checkboxes de vehículo
     * Lógica:
     * - Si checkbox SÍ (OK) está marcado: ocultar campo de foto
     * - Si checkbox SÍ (OK) NO está marcado: mostrar campo de foto (si aplica)
     */
    function initCheckboxObservers() {
        // Delegación de eventos para checkboxes dinámicos
        document.addEventListener('change', function(e) {
            // Manejar checkbox SÍ (OK)
            if (e.target.classList.contains('checkbox-ok')) {
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
     * Soporta tanto radio buttons como checkboxes
     */
    function asignar() {
        var conver = '';
        var chkdatos = document.getElementsByClassName("obtener");

        for (let i = 0; i < chkdatos.length; i++) {
            // Para checkboxes de vehículo SÍ (OK)
            if (chkdatos[i].type === 'checkbox' && chkdatos[i].classList.contains('checkbox-ok')) {
                var checkboxName = chkdatos[i].getAttribute('data-name');
                var value = chkdatos[i].checked ? '1' : '0'; // 1 = SÍ (OK), 0 = NO (no OK)
                conver = conver + '"' + checkboxName + '":' + '"' + value + '",';
            }
            else if (chkdatos[i].type === 'radio' && chkdatos[i].checked) {
                // Radio buttons tradicionales
                conver = conver + '"' + chkdatos[i].name + '":' + '"' + chkdatos[i].value + '",';
            }
        }

        var data = conver.substring(0, conver.length - 1);
        data = '{' + data + '}';
        document.getElementById("data").value = data;
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
     * Inicialización cuando el DOM está listo
     */
    function init() {
        initCheckboxObservers();
        handleSubmitForm();
        cargarDatosPrecarga();
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
