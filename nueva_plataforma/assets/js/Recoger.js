// Cambiar formulario según acción
document.getElementById('tipoAccion').addEventListener('change', function(){
  const v = this.value;
  document.getElementById('formRecogido').style.display   = (v === 'recogido')   ? 'block' : 'none';
  document.getElementById('formNoRecogido').style.display = (v === 'norecogido') ? 'block' : 'none';
});

// ====== Mostrar / ocultar bloque "De Contado" según tipo de pago ======
function actualizarBloqueContado() {
  const tipo = parseInt(document.getElementById('param8').value || 0);
  const bloque = document.getElementById('bloqueContado');
  const bloqueCredito = document.getElementById('bloqueCredito');
  const campo = document.getElementById('ser_peso'); // <-- tu campo dentro del bloque

  if (tipo === 1) {
    bloque.style.display = 'block';
    campo.setAttribute('required', 'required');   // vuelve el campo obligatorio
  } else {
    bloque.style.display = 'none';
    campo.removeAttribute('required');            // deja de ser obligatorio
  }

  if (tipo === 2) {
    bloqueCredito.style.display = 'block';
  } else {
    bloqueCredito.style.display = 'none';
  }

  if (tipo !== 1) {
    campo.removeAttribute('required');
  }
}

document.getElementById('param8').addEventListener('change', actualizarBloqueContado);

// Cargar datos del servicio al entrar
async function cargarServicio() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('idServicio');
  const prekilo = params.get('precioskiloini');

  if (!id) return;

  // 🔧 Helpers seguros
  function setValue(id, value) {
    const el = document.getElementById(id);
    if (!el) {
      console.warn('⚠️ No existe:', id);
      return;
    }
    el.value = value ?? '';
  }

  function setChecked(id, value) {
    const el = document.getElementById(id);
    if (!el) {
      console.warn('⚠️ No existe:', id);
      return;
    }
    el.checked = (value == 1);
  }

  try {
    const resp = await fetch(`../controller/RecogerController.php?accion=buscarRecogida&id=${id}`);

    console.log('📡 status:', resp.status);

    if (!resp.ok) {
      throw new Error('Error HTTP: ' + resp.status);
    }

    const text = await resp.text();
    console.log('📦 RESPUESTA RAW:', text);

    let s;
    try {
      s = JSON.parse(text);
    } catch (err) {
      console.error('❌ JSON inválido:', err);
      throw new Error('La respuesta NO es JSON válido');
    }

    console.log('✅ JSON:', s);

    if (!s || Object.keys(s).length === 0) {
      console.warn('⚠️ Servicio vacío');
      return;
    }

    window._servicioRecogida = s;

    // 🔹 Datos básicos
    setValue('idservicio', id);
    setValue('precioinicialkilos', prekilo);

    // 🔹 Campos principales
    setValue('ser_prioridad', s.ser_prioridad);
    setValue('ser_piezas', s.ser_piezas);
    setValue('param19', s.ser_verificado);
    setValue('ser_paquetedescripcion', s.ser_paquetedescripcion);
    setValue('ser_valorprestamo', s.ser_valorprestamo);
    setValue('ser_valorseguro', s.ser_valorseguro);
    setValue('ser_devolverreci', s.ser_devolverreci);
    setValue('ser_guiare', s.ser_consecutivo); // ✔ corregido
    setValue('ser_estado', s.ser_estado);
    setValue('ser_valorabono', s.ser_valorabono);
    setValue('ser_peso', s.ser_peso);
    setValue('ser_volumen', s.ser_volumen);

    // 🔹 Params del sistema
    setValue('param15', s.ser_prioridad);
    setValue('param26', s.ser_descripcion);
    setValue('param27', s.ser_consecutivo);
    setValue('param11', s.ser_valor);
    setValue('param13', s.cli_idciudad);
    setValue('param9', s.ser_ciudadentrega);
    setValue('param18', s.ser_idresponsable);

    // 🔹 Campos opcionales (no siempre existen)
    setValue('param112', s.ser_valor);
    setValue('credito', s.rel_nom_credito);
    setValue('param34', s.gui_tiposervicio);
    setValue('param21', s.ser_tipopaq);

    // 🔹 Checkbox (IMPORTANTE)
    setChecked('rel_nom_credito', s.rel_nom_credito);

    // 🔹 Reglas de tipo de pago
    if (typeof aplicarReglasTipoPago === 'function') {
      aplicarReglasTipoPago(s);
    } else {
      console.warn('⚠️ aplicarReglasTipoPago no existe');
    }

  } catch (e) {
    console.error('🔥 ERROR COMPLETO:', e);
    alert("Error cargando el servicio para recogida: " + e.message);
  }
}

function normalizarTelefonoRemitente(telefono) {
  const limpio = String(telefono ?? '').trim();
  if (!limpio) return '';

  if (limpio.startsWith('+')) {
    return limpio;
  }

  const soloDigitos = limpio.replace(/\D/g, '');
  if (!soloDigitos) return '';

  if (soloDigitos.startsWith('57')) {
    return `+${soloDigitos}`;
  }

  return `+57${soloDigitos}`;
}

function llenarDatosQuienEntregaDesdeRemitente() {
  const servicio = window._servicioRecogida || {};
  const nombreRemitente = String(
    servicio.cli_nombre ??
    servicio.remitente_nombre ??
    servicio.ser_remitente ??
    ''
  ).trim();
  const telefonoRemitente = normalizarTelefonoRemitente(
    servicio.cli_telefono ??
    servicio.remitente_telefono ??
    servicio.ser_telefonocontacto ??
    ''
  );

  const campoNombre = document.getElementById('param82');
  const campoTelefono = document.getElementById('param85');

  if (campoNombre && nombreRemitente) {
    campoNombre.value = nombreRemitente;
    campoNombre.dispatchEvent(new Event('keyup', { bubbles: true }));
  }

  if (campoTelefono && telefonoRemitente) {
    campoTelefono.value = telefonoRemitente;
  }
}

// Cargar SELECT Tipo (tabla tipo)
async function cargarTipos() {
  try {
    const resp = await fetch('../controller/RecogerController.php?accion=listarTipos');
    const tipos = await resp.json();

    const select = document.getElementById('param21');
    const valorInicial = select.getAttribute('data-value') || '';

    select.innerHTML = '<option value="">Seleccione...</option>';

    tipos.forEach(t => {
      const op = document.createElement('option');
      op.value = t.tip_nombre;
      op.textContent = t.tip_nombre;
      select.appendChild(op);
    });

    // Si ya tenemos tipo desde el servicio, lo intentará colocar
    if (window._tipoDesdeServicio) {
      select.value = window._tipoDesdeServicio;
    }

  } catch (e) {
    console.error(e);
    alert("Error cargando tipos.");
  }
}

// Cargar SELECT Método de pago (tipospagos) para Contado
async function cargarMetodosPago() {
  try {
    const resp = await fetch('../controller/RecogerController.php?accion=listarMetodosPago');
    const metodos = await resp.json();

    const select = document.getElementById('param30');
    select.innerHTML = '<option value="">Seleccione...</option>';

    metodos.forEach(m => {
      // Se espera que el controlador ya devuelva:
      // idtipospagos, pag_numerocuenta, pag_nombre
      const value = `${m.id}`;
      const op = document.createElement('option');
      op.value = value;
      op.textContent = m.pag_nombre;
      select.appendChild(op);
    });

  } catch (e) {
    console.error(e);
    alert("Error cargando métodos de pago.");
  }
}

// Cargar SELECT credito  
async function cargarCreditos() {
  try {
    const resp = await fetch('../controller/RecogerController.php?accion=listarCreditos');
    const creditos = await resp.json();

    const select = document.getElementById('credito');

    // Obtengo el valor que debe quedar seleccionado (si existe)
    const relNomCredito = document.getElementById('rel_nom_credito') 
                           ? document.getElementById('rel_nom_credito').value 
                           : "";

    console.log('Credito que llega '+relNomCredito );                       
    select.innerHTML = '<option value="">Seleccione...</option>';

    creditos.forEach(c => {
      const op = document.createElement('option');
      op.value = c.cre_nombre;
      op.textContent = c.cre_nombre;

      // Si coincide con el valor que se está editando → seleccionar
      // if (relNomCredito !== "" && relNomCredito === c.cre_nombre) {
      //   op.selected = true;
      // }

      select.appendChild(op);
    });

  } catch (e) {
    console.error(e);
    alert("Error cargando Creditos.");
  }
}

// Lógica de tipo de pago
function aplicarReglasTipoPago(s) {
  const clasificacion = parseInt(s.ser_clasificacion);
  const select = document.getElementById('param8');

  if (clasificacion === 2) {
      // crédito forzado
      select.innerHTML = '<option value="2" selected>Crédito</option>';
      
      // select.readonly = true;
      document.getElementById("param8").style.pointerEvents = "none";
  } else {
      // opciones normales
      select.innerHTML = `
        <option value="">Seleccione...</option>
        <option value="1">Contado</option>
        <option value="2">Crédito</option>
        <option value="3">Al Cobro</option>
      `;
      select.disabled = false;
      if (s.ser_clasificacion) {
        select.value = s.ser_clasificacion;
      }
  }

  actualizarBloqueContado();
}

// GUARDAR RECOGIDO
async function guardarRecogido() {

    const form = document.getElementById('formRecogido');
    if (!form) {
        console.error('No existe el formulario formRecogido');
        Swal.fire({
            icon: 'error',
            title: 'Error interno',
            text: 'No se encontró el formulario de recogida.',
        });
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = new FormData(form);

    try {

        // ⏳ Mostrar carga
        Swal.fire({
            title: 'Guardando...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const resp = await fetch("../controller/RecogerController.php", {
            method: "POST",
            body: data
        });

        const r = await resp.json();
        
        Swal.close(); // ❗ Cerrar “cargando”
        

          if (r.ok) {
              Swal.fire({
                  icon: 'success',
                  title: 'Recogida guardada',
                  html: 'La recogida se guardó correctamente con número de guía <b>' + r.numeroGuia + '</b>',
                  showConfirmButton: true,
                  confirmButtonText: 'Cerrar'
              }).then(() => {

                  // 🟢 Recargar TODO el sitio (incluye cerrar popup e iframe)
                  window.parent.location.reload();

              });
          } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: r.msg || 'No se pudo guardar la recogida.',
            });
          }

    } catch (e) {
        console.error(e);
        Swal.close();

        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'Error al enviar la recogida.',
        });
    }
}



// GUARDAR NO RECOGIDO
async function guardarNoRecogido() {
  if (document.getElementById('tipoAccion').value !== 'norecogido') {
    alert("Debe seleccionar la acción NO RECOGIDO.");
    return;
  }

  const id = document.getElementById('idservicio').value;
  if (!id) {
    alert("No hay servicio cargado.");
    return;
  }

  const motivo = document.getElementById('motivo_no_recogido').value.trim();
  const fotoInput = document.getElementById('foto_evidencia');
  const nombre_usu = document.getElementById('nombre').value.trim();

  if (!motivo) {
    alert("Debe escribir un motivo.");
    return;
  }

  if (!fotoInput.files[0]) {
    alert("Debe adjuntar una foto evidencia.");
    return;
  }

  const data = new FormData();
  data.append("accion", "guardarNoRecogido");
  data.append("idservicio", id);
  data.append("motivo", motivo);
  data.append("nombre", nombre_usu);
  data.append("foto_evidencia", fotoInput.files[0]);

  try {
    const resp = await fetch("../controller/RecogerController.php", {
      method: "POST",
      body: data
    });
    const r = await resp.json();
    if (r.ok) {
      alert("Guardado como NO RECOGIDO correctamente.");
    } else {
      alert("No se pudo guardar NO RECOGIDO: " + (r.msg || ""));
    }
  } catch (e) {
    console.error(e);
    alert("Error al enviar NO RECOGIDO.");
  }
}

// Cargar el servicio y los selects al cargar la página
document.addEventListener("DOMContentLoaded", () => {
  cargarServicio();
  cargarTipos();
  cargarMetodosPago();
  cargarCreditos();
  consultarEstadoFirma();
});

// Obtener la hora actual en formato HH:MM
const ahora = new Date();
const horas  = String(ahora.getHours()).padStart(2, '0');
const minutos = String(ahora.getMinutes()).padStart(2, '0');

document.getElementById("param7").value = `${horas}:${minutos}`;

// Exponer funciones globales
window.guardarRecogido = guardarRecogido;
window.guardarNoRecogido = guardarNoRecogido;
window.llenarDatosQuienEntregaDesdeRemitente = llenarDatosQuienEntregaDesdeRemitente;



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

  fetch(`../controller/RecogerController.php?accion=consultarEstadoFirma&id=${ID_SERVICIO}`, {
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
      } else {
        setEstadoFirma("Esperando firma...", "text-warning", "fa-clock");
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
  return;

  pollingFirmaId = setInterval(() => {
    consultarEstadoFirma();
  }, 10000);
}




// === Auto listeners for Peso & Volumen ===
document.addEventListener("DOMContentLoaded", () => {
  const peso = document.getElementById("ser_peso");
  const volumen = document.getElementById("ser_volumen");
  if (peso) peso.addEventListener("input", calcularValorAutomatico);
  if (volumen) volumen.addEventListener("input", calcularValorAutomatico);
});

// ============================
// CALCULAR VALOR AUTOMÁTICO
// ============================
async function calcularValorAutomatico() {
    const peso = document.getElementById("ser_peso") ? document.getElementById("ser_peso").value || 0 : 0;
    const volumen = document.getElementById("ser_volumen") ? document.getElementById("ser_volumen").value || 0 : 0;
    const ciudadOri = document.getElementById("param13") ? document.getElementById("param13").value : "";
    const ciudadDes = document.getElementById("param9") ? document.getElementById("param9").value : "";
    const tipoServ = document.getElementById("param34") ? document.getElementById("param34").value : "";
    const pordeclarado = document.getElementById("ser_valorseguro") ? document.getElementById("ser_valorseguro").value : 0;
    const tipoPago = document.getElementById("param8") ? document.getElementById("param8").value : 0;

    const rel_nom_credito = document.getElementById("rel_nom_credito")
    ? document.getElementById("rel_nom_credito").value
    : "";

    const tipocliente = rel_nom_credito ? 1 : 0;


    console.log("calcularValorAutomatico()", {peso, volumen, ciudadOri, ciudadDes, tipoServ,rel_nom_credito,tipocliente});

        // ===============================
    // 🚫 SI tipoServ ES 1000 → SALIR
    // ===============================
    if (tipoServ == 1000) {
        console.warn("Servicio 1000 → No se ejecuta cálculo.");
        return; // 🔥 importante: se termina aquí
    }

    // if (!tipoServ || (!peso && !volumen)) {
    //     return;
    // }

    const fd = new FormData();
    fd.append("accion", "calcularValorTotal");
    fd.append("peso", peso);
    fd.append("volumen", volumen);
    fd.append("ciudadOri", ciudadOri);
    fd.append("ciudadDes", ciudadDes);
    fd.append("tipoServ", tipoServ);

    fd.append("pordeclarado", pordeclarado);
    fd.append("tipoPago", tipoPago);
    // fd.append("abono", param55);
    // fd.append("porprestamo", param77);
      // fd.append("precioinicialkilos", param53);
      fd.append("rel_nom_credito", rel_nom_credito);
      fd.append("tipocliente", tipocliente);




    try {
        const resp = await fetch("../controller/RecogerController.php", {
            method: "POST",
            body: fd
        });
        const data = await resp.json();
        console.log("Respuesta calcularValorTotal:", data);
        if (data.ok && document.getElementById("param112")) {
            document.getElementById("param112").value = data.total;
            document.getElementById("param11").value = data.valorsinseguro;
        }
    } catch (e) {
        console.error("Error en calcularValorAutomatico:", e);
    }
}

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

    if (!tipopago || !String(tipopago).trim()) {
      Swal.fire("Falta tipo de pago", "Debe seleccionar el tipo de pago antes de enviar la firma", "warning");
      document.getElementById("param8").focus();
      return;
    }

    const fd = new FormData();
    fd.append("accion", "enviarLinkFirma");
    fd.append("idservicio", idservicio);
    fd.append("nombre", nombre);
    fd.append("telefono", telefono);
    fd.append("tipopago", tipopago);

    fetch("../controller/RecogerController.php", {
      method: "POST",
      body: fd
    })
    .then(r => r.json())
    .then(resp => {
      if (!resp.ok) {
        Swal.fire("Error", resp.mensaje || "No se pudo enviar", "error");
        return;
      }

      Swal.fire("Enviado", "Link reenviado por WhatsApp ✔", "success");
      // 🚫 NO cerramos el modal, se queda abierto
      setEstadoFirma("Firma enviada. Use el botÃ³n Verificar firma para validar el estado.", "text-info", "fa-paper-plane");
    });
  }); 

  document.getElementById("btnVerificarFirma").addEventListener("click", function () {
    setEstadoFirma("Validando firma...", "text-info", "fa-spinner");
    consultarEstadoFirma();
  });

  //Capturar Ubicacion
    function enviarFormulario()  {
    
         if (document.getElementById('tipoAccion').value !== 'recogido') {
        Swal.fire({
            icon: 'warning',
            title: 'Acción requerida',
            text: 'Debe seleccionar la acción RECOGIDO.',
        });
        return;
    }

    const id = document.getElementById('idservicio').value;
    if (!id) {
        Swal.fire({
            icon: 'error',
            title: 'Sin servicio',
            text: 'No hay servicio cargado.',
        });
        return;
    }

    const form = document.getElementById('formRecogido');

    // ✅ Validación nativa del navegador
    if (!form.reportValidity()) {
        return; // Si algo falta, no sigue
    }

          // Validar nombre
      if (!validarNombreCompleto()) {
        alert('Verifique el nombre de quien entrega.');
        $('#param82').focus();
        return;
      }

    mostrarCargando("Obteniendo ubicación GPS...", "Espere un momento");

    if (!navigator.geolocation) {
        console.warn("GPS no disponible");
        guardarRecogido();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function (pos) {
        document.getElementById("latitud").value = pos.coords.latitude;
        document.getElementById("longitud").value = pos.coords.longitude;
        document.getElementById("precision_gps").value = pos.coords.accuracy;

        console.log("📍 Ubicación capturada", pos.coords);

        guardarRecogido();
        },
        function (err) {
        console.warn("⚠️ No se pudo obtener GPS", err);
        guardarRecogido();
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
