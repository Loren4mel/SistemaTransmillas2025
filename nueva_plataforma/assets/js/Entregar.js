    // ========================= SELECT ACCIÓN ==========================
    $('#tipoAccion').on('change', function() {
      let accion = $(this).val();

      if (accion === "entregar") {
        $('#formEntregar').show();
        $('#formNoEntregado').hide();
      } else if (accion === "noentregado") {
        $('#formEntregar').hide();
        $('#formNoEntregado').show();
      } else {
        $('#formEntregar').hide();
        $('#formNoEntregado').hide();
      }
    });

    const imagenesBancos = {
      bancolombia: {
        titulo: 'Bancolombia',
        src: '../../images/PagoBancolombiaLlave.png'
      },
      davivienda: {
        titulo: 'Davivienda',
        src: '../../images/daviplata.png'
      }
    };

    let bancoVisibleActual = null;

    function toggleImagenBanco(canal) {
      const config = imagenesBancos[canal];
      const visor = document.getElementById('visorImagenBanco');
      const imagen = document.getElementById('imagenBancoPreview');
      const titulo = document.getElementById('tituloImagenBanco');

      if (!config || !visor || !imagen || !titulo) return;

      if (bancoVisibleActual === canal && visor.style.display !== 'none') {
        ocultarImagenBanco();
        return;
      }

      titulo.textContent = 'Imagen bancaria - ' + config.titulo;
      imagen.src = config.src;
      imagen.alt = config.titulo;
      visor.style.display = 'block';
      bancoVisibleActual = canal;

      document.querySelectorAll('[data-banco]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.banco === canal);
      });
    }

    function ocultarImagenBanco() {
      const visor = document.getElementById('visorImagenBanco');
      const imagen = document.getElementById('imagenBancoPreview');

      if (visor) visor.style.display = 'none';
      if (imagen) imagen.src = '';
      bancoVisibleActual = null;

      document.querySelectorAll('[data-banco]').forEach(btn => {
        btn.classList.remove('active');
      });
    }

    // ============================ CARGAR SERVICIO POR ID (GET) ============================
    function cargarServicio(id) {
      if (!id) {
        alert('No se recibió el ID de servicio (idServicio).');
        return;
      }

    $.ajax({
  url: '../controller/EntregarController.php',
  type: 'GET',
  data: { accion: 'buscarEntrega', id: id },
  dataType: 'text', // <- importante
  success: function (raw) {
    try {
      const limpio = (raw || '').replace(/^\uFEFF/, '').trim(); // quita BOM
      const servicio = JSON.parse(limpio);

      if (!servicio) {
        alert('No se encontró el servicio.');
        return;
      }

 // Rellenar campos principales (según SELECT del modelo)
          $('#idservicio').val(servicio.idservicios);
          $('#ser_piezas').val(servicio.ser_piezas);
          $('#ser_paquetedescripcion').val(servicio.ser_paquetedescripcion);
          $('#ser_peso').val(servicio.ser_peso);
          $('#ser_valorseguro').val(servicio.ser_valorseguro);
          $('#ser_valor').val(servicio.ser_valor);
          $('#ser_guiare').val(servicio.ser_guiare);

          // Devolver Recibido: 1 -> SI, otro -> NO
          let devol = (servicio.ser_devolverreci == 1) ? 'SI' : 'NO';
          $('#ser_devolverreci_text').val(devol);

          // Tipo Pago (texto mapeado en backend idealmente)
          $('#tipo_pago').val(servicio.tipo_pago || servicio.ser_clasificacion);

          // Guardar en ocultos varios que usabas
          $('#param9').val(servicio.ser_ciudadentrega);
          $('#param22').val(servicio.cli_idciudad);
          $('#param10').val(servicio.ser_clasificacion);
          $('#param11').val(servicio.ser_guiare);
          $('#iduserentrega').val(servicio.ser_idusuarioguia);
          $('#param21').val(servicio.gui_tiposervicio);
          $('#ser_pendientecobrar').val(servicio.ser_pendientecobrar);

          // Valores préstamo / abono
          $('#valor_prestamo').val(servicio.ser_valorprestamo_format || servicio.ser_valorprestamo);
          $('#abono').val(servicio.ser_valorabono_format || servicio.ser_valorabono);

          // Totales (ideal que vengan calculados del backend)
          $('#porc_vr_declarado').val(servicio.seguro || '');
          $('#cobro_prestamo').val(servicio.porprestamo || '');
          $('#vr_flete_tot').val(servicio.vr_flete || servicio.ser_valor);
          $('#total_prestamo').val(servicio.total_prestamo || '');
          $('#total_flete').val(servicio.total_flete || '');
          $('#total_final').val(servicio.total_final || '');
          $('#devolucion').val(servicio.devolucion || '');

          // Mostrar campo devolución si aplica
          if (servicio.total_final !== undefined && parseInt(servicio.total_final) < 1) {
            $('#rowDevolucion').show();
          } else {
            $('#rowDevolucion').hide();
          }

          // Kilos total = peso + volumen (como en tu código)
          let pesoNum = parseFloat(servicio.ser_peso || 0);
          let volNum  = parseFloat(servicio.ser_volumen || 0);
          let kiliostotal = pesoNum + volNum;
          $('#param20').val(kiliostotal);

          // Mostrar / ocultar bloque método de pago
          if ((servicio.tipo_pago && servicio.tipo_pago === 'Al Cobro') || servicio.ser_pendientecobrar == 1) {
            $('#bloqueMetodoPago').show();
            $('#param30_hidden').val(''); // lo manejamos por select
          } else {
            $('#bloqueMetodoPago').hide();
            $('#param30_hidden').val('0');
            $('#metodo_pago').val('');
            ocultarImagenBanco();
          }

          // Armar URL del iframe de firma
          const tipoPagoUrl = encodeURIComponent(servicio.tipo_pago || servicio.ser_clasificacion || '');
          const para        = encodeURIComponent(servicio.idservicios);
          const urlFirma    = `/nueva_plataforma/view/recogerEntregar/firmar.php?para=${para}&accion=guardarFirmaEntrega&tipo_pago=${tipoPagoUrl}`;
          $('#iframeFirma').attr('src', urlFirma);
    } catch (e) {
      console.error('JSON inválido buscarEntrega:', e, raw);
      alert('Respuesta inválida del servidor en buscarEntrega');
    }
  },
  error: function (xhr, status, err) {
    console.error('AJAX buscarEntrega ERROR', { status, err, code: xhr.status, body: xhr.responseText });
    alert('Error al cargar los datos del servicio.');
  }
});
    }



    // Al cargar la página, traemos el servicio automáticamente usando el idServicio del GET
    $(document).ready(function () {
      const idServicioPHP = ID_SERVICIO;
      if (idServicioPHP) {
        cargarServicio(idServicioPHP);
      } else {
        alert('No se recibió el parámetro idServicio en la URL.');
      }
    });

    // ============================ VALIDAR NOMBRE COMPLETO ============================
    function validarNombreCompleto() {
      const nombre = $('#param82').val().trim();
      const partes = nombre.split(' ').filter(p => p.length > 0);
      if (partes.length < 2) {
        $('#errorNombre').show();
        return false;
      }
      $('#errorNombre').hide();
      return true;
    }

    $('#param82').on('blur keyup', validarNombreCompleto);

    // ============================ GUARDAR ENTREGA ============================
    function guardarEntregar() {


      const form = document.getElementById('formEntregar');
      let data = new FormData(form);

      // Si usamos el select de método de pago, enviarlo como param30
      if ($('#bloqueMetodoPago').is(':visible')) {
        data.set('param30', $('#metodo_pago').val());
      } else {
        data.set('param30', $('#param30_hidden').val() || '0');
      }

        // Mostrar loader mientras se procesa
        Swal.fire({
            title: "Procesando...",
            text: "Por favor espera...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('../controller/EntregarController.php', {
            method: 'POST',
            body: data
        })
        .then(res => res.json())
        .then(resp => {

            if (resp && resp.ok) {

                Swal.fire({
                    icon: "success",
                    title: "Correcto",
                    text: "Entrega guardada correctamente.",
                    confirmButtonText: "Aceptar",
                    confirmButtonColor: "#28a745",
                    timer: 2500
                }).then(() => {
                    // Recargar toda la página madre
                    window.parent.location.reload();
                });

            } else {

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: resp.msg || "No se pudo guardar la entrega.",
                    confirmButtonText: "Cerrar",
                    confirmButtonColor: "#d33"
                });

            }

        })
        .catch(err => {
            console.error("JSON FAIL:", err);

            Swal.fire({
                icon: "error",
                title: "Error en el servidor",
                text: "No se pudo procesar la respuesta.",
                confirmButtonColor: "#d33"
            });
        });

    }

    // ============================ GUARDAR NO ENTREGADO ============================
    function guardarNoEntregar() {
      if (!$('#idservicio').val()) {
        alert("No hay servicio cargado (idservicio vacío).");
        return;
      }

      if ($('#tipoAccion').val() !== 'noentregado') {
        alert('Debe seleccionar la acción NO ENTREGADO para usar este formulario.');
        return;
      }

      const motivo = $('#motivo_noentrega').val().trim();
      const evidenciaInput = $('#foto_evidencia')[0];
      const evidencia = evidenciaInput.files[0];

   

      if (!motivo) {
        alert("Debe escribir un motivo.");
        return;
      }

      if (!evidencia) {
        alert("Debe adjuntar una foto evidencia.");
        return;
      }

      let data = new FormData();
      data.append("accion", "guardarNoEntregar");
      data.append("idservicio", $('#idservicio').val());
      data.append("motivo", motivo);
      data.append("usuario", "<?php echo $usuario; ?>");
      data.append("id_nombre", "<?php echo $nombre; ?>");
      data.append("acceso", "<?php echo $acceso; ?>");


      data.append("foto_evidencia", evidencia);

        Swal.fire({
                title: "Procesando...",
                text: "Por favor espera...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

        fetch("../controller/EntregarController.php", { 
            method: "POST",
            body: data
        })
        .then(r => r.json())
        .then(resp => {

            console.log(resp);

            if (resp.ok) {

                Swal.fire({
                    icon: "success",
                    title: "¡Guardado!",
                    text: "El servicio fue marcado como NO ENTREGADO correctamente.",
                    confirmButtonText: "Aceptar",
                    confirmButtonColor: "#28a745",
                    timer: 2000
                }).then(() => {
                    // Recargar toda la página madre
                    window.parent.location.reload();
                });

            } else {

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: resp.error || "No se pudo guardar el registro.",
                    confirmButtonText: "Cerrar",
                    confirmButtonColor: "#d33"
                });

            }
        })
        .catch(err => {
            console.error("JSON FAIL:", err);

            Swal.fire({
                icon: "error",
                title: "Error en el servidor",
                text: "No se pudo procesar la respuesta.",
                confirmButtonColor: "#d33"
            });
        });
    }

    // Exponer funciones al scope global (para los onclick)
    window.guardarEntregar = guardarEntregar;
    window.guardarNoEntregar = guardarNoEntregar;

// =================================
// VAlIDAR FIRMA EN TR
// =================================

let pollingFirmaId = null;
let consultaEnCursoFirma = false;

function setEstadoFirma(texto, clase = "text-warning", icono = "fa-clock") {
  const el = document.getElementById("estadoFirma");
  if (!el) return;

  el.className = clase;
  el.innerHTML = `<i class="fas ${icono} me-1"></i> ${texto}`;
}

function detenerPollingFirma() {
  if (pollingFirmaId) {
    clearInterval(pollingFirmaId);
    pollingFirmaId = null;
  }
}

function consultarEstadoFirma() {
  if (consultaEnCursoFirma) return;
  consultaEnCursoFirma = true;

  fetch(`../controller/EntregarController.php?accion=consultarEstadoFirma&id=${ID_SERVICIO}`, {
    method: "GET"
  })
    .then(r => r.text())
    .then(raw => {
      const limpio = (raw || "").replace(/^\uFEFF/, "").trim(); // quita BOM
      return JSON.parse(limpio);
    })
    .then(resp => {
      if (resp && resp.ok && resp.firmada === true) {
        setEstadoFirma("Ya está firmada", "text-success", "fa-check-circle");
        detenerPollingFirma();
      }
    })
    .catch(err => {
      console.error("Error consultando estado de firma:", err);
    })
    .finally(() => {
      consultaEnCursoFirma = false;
    });
}

function iniciarPollingFirma() {
  if (pollingFirmaId) return; // evita múltiples intervalos

  setEstadoFirma("Validando firma en tiempo real...", "text-info", "fa-spinner");
  consultarEstadoFirma(); // inmediato

  pollingFirmaId = setInterval(() => {
    consultarEstadoFirma();
  }, 10000);
}

window.addEventListener("beforeunload", detenerPollingFirma);
    //enviar firma
    document.getElementById("btnEnviarFirma").addEventListener("click", function () {
      const idservicio = ID_SERVICIO; // 🔥 directo desde PHP
      const nombre = document.getElementById("param82").value;
      const telefono = document.getElementById("param85").value;
      const tipopago = document.getElementById("param8").value;


    const telefonoLimpio = (telefono || "").trim();
    const soloPrefijoColombia = telefonoLimpio.replace(/\s+/g, "") === "+57";

    if (!telefonoLimpio || soloPrefijoColombia) {
      Swal.fire("Falta teléfono", "Debe ingresar el WhatsApp", "warning");
      return;
    }

      const fd = new FormData();
      fd.append("accion", "enviarLinkFirma");
      fd.append("idservicio", idservicio);
      fd.append("nombre", nombre);
      fd.append("telefono", telefono);
      fd.append("tipopago", tipopago);

    fetch("../controller/EntregarController.php", {
      method: "POST",
      body: fd
    })
    .then(r => r.text())
    .then(raw => {
      const limpio = (raw || "").replace(/^\uFEFF/, "").trim(); // quita BOM
      return JSON.parse(limpio);
    })
    .then(resp => {
      if (!resp.ok) {
        Swal.fire("Error", resp.mensaje || "No se pudo enviar", "error");
        return;
      }

      Swal.fire("Enviado", resp.mensaje || "Link reenviado por WhatsApp ✔", "success");
      iniciarPollingFirma(); // si ya lo agregaste
    })
    .catch(err => {
      console.error("Error enviar firma:", err);
      Swal.fire("Error", "Respuesta inválida del servidor", "error");
    });
    });

    //Capturar Ubicacion
    function enviarFormulario() {
          // Validar acción seleccionada
      if ($('#tipoAccion').val() !== 'entregar') {
        alert('Debe seleccionar la acción ENTREGAR para usar este formulario.');
        return;
      }

      // Validar que haya servicio cargado
      if (!$('#idservicio').val()) {
        alert('No hay servicio cargado (idservicio vacío).');
        return;
      }

      // Validar nombre
      if (!validarNombreCompleto()) {
        alert('Verifique el nombre de quien entrega.');
        $('#param82').focus();
        return;
      }

      // Si hay bloque de método de pago visible, validar select
      if ($('#bloqueMetodoPago').is(':visible')) {
        if (!$('#metodo_pago').val()) {
          alert('Seleccione un método de pago.');
          $('#metodo_pago').focus();
          return;
        }
      }

    mostrarCargando("Obteniendo ubicación GPS...", "Espere un momento");

    if (!navigator.geolocation) {
        console.warn("GPS no disponible");
        guardarEntregar();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function (pos) {
        document.getElementById("latitud").value = pos.coords.latitude;
        document.getElementById("longitud").value = pos.coords.longitude;
        document.getElementById("precision_gps").value = pos.coords.accuracy;

        console.log("📍 Ubicación capturada", pos.coords);

        guardarEntregar();
        },
        function (err) {
        console.warn("⚠️ No se pudo obtener GPS", err);
        guardarEntregar();
        },
        {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
        }
    );
    }
    function mostrarCargando(titulo = "Procesando...", texto = "Por favor espere") {
    Swal.fire({
        title: titulo,
        text: texto,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
        Swal.showLoading();
        }
    });
    }

    function cerrarCargando() {
    Swal.close();
    }
