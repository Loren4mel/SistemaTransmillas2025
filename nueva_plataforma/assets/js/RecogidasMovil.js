let estructuraCamposDest  = {
    "APARTAMENTO": [
      { label: "NUMERO APARTAMENTO", name: "apartamento_num", type: "number" },
      { label: "MANZANA", name: "manzanaD" },
      { label: "BLOQUE", name: "bloqueD" },
      { label: "UNIDAD", name: "unidadD" },
      { label: "INTERIOR", name: "interiorD" },
      { label: "TORRE", name: "torreD" }
    ],
     "OFICINA": [
        { label: "NUMERO OFICINA", name: "oficina_num", type: "number" },
        { label: "EDIFICIO", name: "edificio", type: "text" },
        { label: "MANZANA", name: "manzana" },
        { label: "BLOQU", name: "bloque" },
        { label: "UNIDAD", name: "unidadD" },
        { label: "INTERIOR", name: "interiorD" },
        { label: "TORRE", name: "torreD" }
    ],
    // "LOCAL": [
    //     { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
    //     { label: "PISO", name: "piso" },
    //     { label: "SOTANO", name: "sotanobloque" },
    //     { label: "LOCAL CALLE", name: "LOCALCALLE" }

    // ],
    "CASA": [
      { label: "NUMERO DE CASA", name: "casa_num", type: "number" },
      { label: "CONJUNTO", name: "conjunto", type: "text" }
    ],
    "EMPRESA": [
        { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
        { label: "PISO", name: "piso" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "localcalle" }

    ],
    "CONSULTORIO": [
        { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
        { label: "PISO", name: "piso" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "localcalle" }

    ],
    "CENTRO EMPRESARIAL": [
        { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
        { label: "PISO", name: "piso" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "localcalle" }

    ],
    "CENTRO COMERCIAL": [
        { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
        { label: "PISO", name: "piso" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "LOCALCALLE" }

    ]
  };

  let estructuraCamposRemitente  = {
    "APARTAMENTO": [
      { label: "NUMERO APARTAMENTO", name: "apartamento_num", type: "number" },
      { label: "MANZANA", name: "manzanaD" },
      { label: "BLOQUE", name: "bloqueD" },
      { label: "UNIDAD", name: "unidadD" },
      { label: "INTERIOR", name: "interiorD" },
      { label: "TORRE", name: "torreD" }
    ],
     "OFICINA": [
        { label: "NUMERO OFICINA", name: "oficina_num", type: "number" },
        { label: "EDIFICIO", name: "edificio", type: "text" },
        { label: "MANZANA", name: "manzana" },
        { label: "BLOQU", name: "bloque" },
        { label: "UNIDAD", name: "unidadD" },
        { label: "INTERIOR", name: "interiorD" },
        { label: "TORRE", name: "torreD" }
    ],
    // "LOCAL": [
    //     { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
    //     { label: "PISO", name: "piso" },
    //     { label: "SOTANO", name: "sotanobloque" },
    //     { label: "LOCAL CALLE", name: "LOCALCALLE" }

    // ],
    "CASA": [
      { label: "NUMERO DE CASA", name: "casa_num", type: "number" },
      { label: "CONJUNTO", name: "conjunto", type: "text" }
    ],
    "EMPRESA": [
        { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
        { label: "PISO", name: "piso" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "LOCALCALLE" }

    ],
    "CONSULTORIO": [
        { label: "NUMERO CONSULTORIO", name: "consultorio_num", type: "number" },
        { label: "PISO", name: "piso" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "localcalle" }

    ],
    "CENTRO EMPRESARIAL": [
        { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
        { label: "BODEGA", name: "BODEGA" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "localcalle" }

    ],
    "CENTRO COMERCIAL": [
        { label: "NUMERO DE LOCAL", name: "local_num", type: "number" },
        { label: "PISO", name: "piso" },
        { label: "SOTANO", name: "sotanobloque" },
        { label: "LOCAL CALLE", name: "localcalle" }

    ]
  };

  function crearCampoCheckConNumero(campo, construirTextoFinal, sufijo) {
  const wrapper = document.createElement("div");
  wrapper.classList.add("d-flex", "align-items-center", "mb-2", "gap-2");

  const check = document.createElement("input");
  check.type = "checkbox";
  check.classList.add("form-check-input");
  check.id = campo.name + "_check_" + sufijo;

  const label = document.createElement("label");
  label.textContent = campo.label;
  label.setAttribute("for", check.id);
  label.classList.add("form-check-label", "me-2");

  const input = document.createElement("input");
  input.type = "text";
  input.name = campo.name + "_" + sufijo;
  input.classList.add("form-control");
  input.style.maxWidth = "120px";
  input.disabled = true;

check.addEventListener("change", function () {
  const activo = this.checked;

  input.disabled = !activo;
  input.required = activo;   // 🔥 aquí lo hacemos obligatorio
  input.value = activo ? input.value : "";

  construirTextoFinal();
});

  input.addEventListener("input", construirTextoFinal);

  wrapper.appendChild(check);
  wrapper.appendChild(label);
  wrapper.appendChild(input);

  return wrapper;
}


function construirTextoFinalRem() {
  const select = document.getElementById("param19");
  const campoFinal = document.getElementById("complementoFinal");
  if (!select || !campoFinal) return;

  const tipo = select.value;
  const campos = estructuraCamposRemitente[tipo];
  if (!campos) return;

  const partes = [];

  campos.forEach(campo => {
    const check = document.getElementById(campo.name + "_check_R");
    const input = document.querySelector(`[name="${campo.name}_R"]`);
    if (check?.checked && input?.value.trim() !== "") {
      partes.push(`${campo.label} ${input.value.trim()}`);
    }
  });

  campoFinal.value = partes.join(" & ");
}

function construirTextoFinalDest() {
  const select = document.getElementById("param21");
  const campoFinal = document.getElementById("complementoFinalD");
  if (!select || !campoFinal) return;

  const tipo = select.value;
  const campos = estructuraCamposDest[tipo];
  if (!campos) return;

  const partes = [];

  campos.forEach(campo => {
    const check = document.getElementById(campo.name + "_check_D");
    const input = document.querySelector(`[name="${campo.name}_D"]`);
    if (check?.checked && input?.value.trim() !== "") {
      partes.push(`${campo.label} ${input.value.trim()}`);
    }
  });

  campoFinal.value = partes.join(" & ");
}





document.addEventListener("DOMContentLoaded", function () {

  /* =========================
     UTILIDADES
  ========================== */
  function setValue(id, value = "") {
    const el = document.getElementById(id);
    if (el) el.value = value;
  }

  /* =========================
     BLOQUES TIPO PAGO
  ========================== */
  const tipoPago = document.getElementById("param28");
  const bloqueContado = document.getElementById("bloque-contado");
  const bloqueCredito = document.getElementById("bloque-credito");
  const creditoSelect = document.getElementById("cliente_credito");
  const metodoPago = document.getElementById("metodo_pago");
  const visorImagenPago = document.getElementById("visorImagenPago");
  const imagenPagoPreview = document.getElementById("imagenPagoPreview");
  const tituloImagenPago = document.getElementById("tituloImagenPago");

  const imagenesPago = {
    DV: {
      titulo: "Davivienda",
      src: "../../images/daviplata.png"
    },
    NQ: {
      titulo: "Bancolombia",
      src: "../../images/PagoBancolombiaLlave.png"
    }
  };

  function ocultarImagenPago() {
    visorImagenPago?.classList.add("d-none");
    if (imagenPagoPreview) imagenPagoPreview.src = "";
  }

  function actualizarImagenPago() {
    const config = imagenesPago[metodoPago?.value || ""];

    if (!config || !visorImagenPago || !imagenPagoPreview || !tituloImagenPago) {
      ocultarImagenPago();
      actualizarRequeridoImagenTransaccion();
      return;
    }

    tituloImagenPago.textContent = "Imagen de pago - " + config.titulo;
    imagenPagoPreview.src = config.src;
    imagenPagoPreview.alt = config.titulo;
    visorImagenPago.classList.remove("d-none");
    actualizarRequeridoImagenTransaccion();
  }

  function metodoPagoRequiereTransaccion() {
    const valor = metodoPago?.value || "";
    return valor === "DV" || valor === "NQ";
  }

  function actualizarRequeridoImagenTransaccion() {
    const input = document.getElementById("imagen_transaccion");
    const ayuda = document.getElementById("ayuda_imagen_transaccion");
    const requiere = !bloqueContado?.classList.contains("d-none") && metodoPagoRequiereTransaccion();

    if (!input) return requiere;

    input.required = requiere;
    input.classList.remove("is-invalid");
    ayuda?.classList.toggle("d-none", !requiere);

    return requiere;
  }

  function manejarTipoPago() {
    const valor = tipoPago.value;

    // oculta ambos
    bloqueContado?.classList.add("d-none");
    bloqueCredito?.classList.add("d-none");
    ocultarImagenPago();
    if (metodoPago) metodoPago.value = "";
    const imagenTransaccion = document.getElementById("imagen_transaccion");
    if (imagenTransaccion) imagenTransaccion.value = "";

    if (valor === "1") bloqueContado?.classList.remove("d-none");
    if (valor === "2") bloqueCredito?.classList.remove("d-none");
    actualizarRequeridoImagenTransaccion();
  }

  metodoPago?.addEventListener("change", actualizarImagenPago);
  document.getElementById("imagen_transaccion")?.addEventListener("change", function () {
    this.classList.remove("is-invalid");
  });

  /* =========================
     AUTOCOMPLETE REMITENTE
  ========================== */
  const inputDocumento = document.getElementById("param1");
  const inputTelefonoRem = document.getElementById("param2");
  let timeoutRem = null;

    function cargarCliente(data) {
    setValue("param6", data.cli_nombre || "");
    setValue("param2", data.cli_telefono || "");
    setValue("param3", data.cli_email || "");
    setValue("param4", data.cli_idciudad || "");

    if (data.cli_direccion) {
        const dir = data.cli_direccion.split("&");

        // 👉 Procesar la PRIMERA parte de la dirección
        if (dir[1]) {
        let base = dir[1];
        let parteHash = "";
        let parteGuion = "";

        // Separar por #
        if (base.includes("#")) {
            const partes = base.split("#");
            base = partes[0].trim();        // Antes del #
            parteHash = partes[1] || "";    // Después del #
        }

        // Separar por - (puede estar en parteHash o en base si no hubo #)
        if (parteHash.includes("-")) {
            const subPartes = parteHash.split("-");
            parteHash = subPartes[0].trim();   // Entre # y -
            parteGuion = subPartes[1] || "";   // Después del -
        } else if (!dir[1].includes("#") && base.includes("-")) {
            const subPartes = base.split("-");
            base = subPartes[0].trim();        // Antes del -
            parteGuion = subPartes[1] || "";   // Después del -
        }

        setValue("dir1R", base);        // Calle o parte principal
        setValue("dir2R", parteHash);   // Número después de #
        setValue("dir3R", parteGuion);  // Complemento después de -
        }

        // Resto de la dirección igual que antes
        

        setValue("param5", dir[0] || "");
        document.getElementById("param5")?.dispatchEvent(new Event("change"));
        setValue("param19", dir[2] || "");
        setValue("dir_complemento_detalle", dir[3] || "");

        // 🔥 NUEVO — aplicar datos de complemento dinámico
        const extras = dir.slice(5); // Todo lo que viene después del conjunto
        aplicarDatosComplemento("param19", "camposComplemento", estructuraCamposRemitente, extras, "R", construirTextoFinalRem);
        setValue("param23", dir[4] || "");
        
    }

    setValue("id_param", data.idclientes || "");
    setValue("id_param2", data.idclientesdir || "");
    setValue("id_param1", 1);
    }

  function limpiarCliente() {
    setValue("param6", "");
    setValue("param3", "");
    setValue("param4", "");
    setValue("param23", "");
    setValue("id_param", 0);
    setValue("id_param1", 0);
    // id_param2 lo puedes limpiar si quieres:
    // setValue("id_param2", "");
  }

function buscarCliente(tipo, valor) {
  if (!valor || valor.length < 7) return;

  mostrarSpinner("spinner-remitente");

  fetch("../../buscarclientesotros.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ vlores: valor, tipo })
  })
  .then(res => res.json())
  .then(data => {
    ocultarSpinner("spinner-remitente");

    if (!data) { limpiarCliente(); return; }
    cargarCliente(data);
    cargarCreditos();
    intentarConsultarServicio();
  })
  .catch(() => {
    ocultarSpinner("spinner-remitente");
    limpiarCliente();
  });
}
  function debounceBuscarCliente(tipo, input) {
    clearTimeout(timeoutRem);
    timeoutRem = setTimeout(() => {
      buscarCliente(tipo, input.value.trim());
    }, 600);
  }

  inputDocumento?.addEventListener("keyup", () => debounceBuscarCliente("documento", inputDocumento));
  // inputTelefonoRem?.addEventListener("keyup", () => debounceBuscarCliente("telefono", inputTelefonoRem));
  inputTelefonoRem?.addEventListener("blur", function () {
  const valor = this.value.trim();
  if (valor.length >= 8) {
    buscarCliente("telefono", valor);
  }
});
  

  /* =========================
     AUTOCOMPLETE DESTINATARIO
  ========================== */
  const inputTelefonoDest = document.getElementById("param8");
  let timeoutDest = null;

function cargarDestinatario(data) {
  setValue("param9", data.cli_nombre || "");
  setValue("param11", data.cli_idciudad || "");

if (data.cli_direccion) {
        const dir = data.cli_direccion.split("&");

        // 👉 Procesar la PRIMERA parte de la dirección
        if (dir[1]) {
        let base = dir[1];
        let parteHash = "";
        let parteGuion = "";

        // Separar por #
        if (base.includes("#")) {
            const partes = base.split("#");
            base = partes[0].trim();        // Antes del #
            parteHash = partes[1] || "";    // Después del #
        }

        // Separar por - (puede estar en parteHash o en base si no hubo #)
        if (parteHash.includes("-")) {
            const subPartes = parteHash.split("-");
            parteHash = subPartes[0].trim();   // Entre # y -
            parteGuion = subPartes[1] || "";   // Después del -
        } else if (!dir[1].includes("#") && base.includes("-")) {
            const subPartes = base.split("-");
            base = subPartes[0].trim();        // Antes del -
            parteGuion = subPartes[1] || "";   // Después del -
        }

        setValue("dir1D", base);        // Calle o parte principal
        setValue("dir2D", parteHash);   // Número después de #
        setValue("dir3D", parteGuion);  // Complemento después de -
        }


    setValue("param10", dir[0] || "");
    document.getElementById("param10")?.dispatchEvent(new Event("change"));
    setValue("param21", dir[2] || "");
    setValue("dir_complemento_detalleD", dir[3] || "");

    // 🔥 NUEVO
    const extras = dir.slice(5);
    aplicarDatosComplemento("param21", "camposComplementoD", estructuraCamposDest, extras, "D", construirTextoFinalDest);
    setValue("param24", dir[4] || "");
  }

  setValue("id_param0", data.idclientesdir || "");
}

  function limpiarDestinatario() {
    ["param9","param10","param101","param21","param22","param24","param11","id_param0"]
      .forEach(id => setValue(id, ""));
  }

  function buscarDestinatario(valor) {
    if (!valor || valor.length < 7) return;

    fetch("../../buscarclientesotros.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ vlores: valor, tipo: "telefono" })
    })
    .then(res => res.json())
    .then(data => {
      if (!data) { limpiarDestinatario(); return; }
      cargarDestinatario(data);
      cargarCreditos();
      intentarConsultarServicio();
    })
    .catch(() => limpiarDestinatario());
  }

  function debounceBuscarDest() {
    clearTimeout(timeoutDest);
    timeoutDest = setTimeout(() => {
      buscarDestinatario(inputTelefonoDest.value.trim());
    }, 600);
  }

  inputTelefonoDest?.addEventListener("keyup", debounceBuscarDest);

  /* =========================
     CARGAR CREDITOS (solo si crédito)
  ========================== */
function cargarCreditos() {
  if (!tipoPago || tipoPago.value !== "2") return; // ✅ ahora sí es crédito
  if (!creditoSelect) return;

  const telRem = document.getElementById("param2")?.value?.trim() || "";
  const telDes = document.getElementById("param8")?.value?.trim() || "";
  if (!telRem && !telDes) return;

  fetch("../controller/RecogidasMovilController.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      accion: "obtenerCreditos",
      telremitente: telRem,
      teldestino: telDes
    })
  })
  .then(r => r.json())
  .then(data => {
    creditoSelect.innerHTML = `<option value="">Seleccione...</option>`;

    if (!data.ok) {
      creditoSelect.innerHTML += `<option value="">Sin créditos disponibles</option>`;
      return;
    }

    data.creditos.forEach(c => {
      creditoSelect.innerHTML += `<option value="${c.idcreditos}">${c.cre_nombre}</option>`;
    });
  });
}

  /* =========================
     CONSULTA TIPO SERVICIO (respeta regla crédito)
  ========================== */
  function intentarConsultarServicio() {

  console.log("🟢 [1] Entró a intentarConsultarServicio");

  const origen  = document.getElementById("param4")?.value || "";
  const destino = document.getElementById("param11")?.value || "";
  const pago    = tipoPago?.value || "";
  const credito = creditoSelect?.value || "";

  console.log("🟡 [2] Valores actuales:", {
    origen,
    destino,
    pago,
    credito
  });

  // 🔴 VALIDACIÓN BASE
  if (!origen || !destino || !pago) {
    console.warn("⛔ [3] SALE → faltan datos básicos", {
      origenVacio: !origen,
      destinoVacio: !destino,
      pagoVacio: !pago
    });
    return;
  }

  // 🔴 CASO CRÉDITO SIN CRÉDITO
  if (pago === "2" && !credito) {
    console.warn("⛔ [4] SALE → es crédito pero NO hay crédito seleccionado");
    document.getElementById("respuesta").innerHTML =
      `<span class="text-warning">Seleccione un crédito</span>`;
    setValue("param113", "");
    return;
  }

  // 🟢 SI LLEGA AQUÍ, DEBE CONSULTAR
  console.log("✅ [5] OK → llamando consultarTipoServicio()");
  consultarTipoServicio(origen, destino, pago, credito);
}


function consultarTipoServicio(origen, destino, pago, credito) {

  const respuesta = document.getElementById("respuesta");
  if (!respuesta) return;

  respuesta.innerHTML = `<span class="text-muted">Consultando servicios...</span>`;

  fetch("../controller/RecogidasMovilController.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      accion: "obtenerTipoServicio",
      ciudad_origen: origen,
      ciudad_destino: destino,
      tipo_pago: pago,
      credito_id: credito
    })
  })
  .then(r => r.json())
  .then(data => {

    if (!data.ok) {
      respuesta.innerHTML = `<span class="text-danger">${data.mensaje}</span>`;
      setValue("param113", "");
      return;
    }

    let html = `
      <select id="select_tipo_servicio" class="form-select">
        <option value="">Seleccione tipo de servicio</option>
    `;

    data.servicios.forEach(s => {
      html += `<option value="${s.id}">${s.nombre}</option>`;
    });

    html += `</select>`;
    respuesta.innerHTML = html;

    const selectServicio = document.getElementById("select_tipo_servicio");
    selectServicio.addEventListener("change", function () {
      setValue("param113", this.value);
      calcularValorAutomatico(); // 🔥 AQUÍ SÍ
    });

  })
  .catch(err => {
    console.error("Error consultando servicios:", err);
    respuesta.innerHTML = `<span class="text-danger">Error consultando servicios</span>`;
  });
}

  /* =========================
     EVENTOS PARA SERVICIO
  ========================== */
//   document.getElementById("param4")?.addEventListener("change", intentarConsultarServicio);
//   document.getElementById("param11")?.addEventListener("change", intentarConsultarServicio);

    // Tipo de pago → decide TODO
    tipoPago?.addEventListener("change", () => {
    manejarTipoPago();
    cargarCreditos();
    console.log('selecciono tipo de pago');

    // si NO es crédito, aquí sí se consulta
    if (tipoPago.value !== "credito") {
        intentarConsultarServicio();
        console.log('pago diferente a credito');
    }
    });

    // Crédito → recién aquí se consulta
    creditoSelect?.addEventListener("change", () => {
    console.log('se selecciono el credito');
    intentarConsultarServicio();
    });

});

async function calcularValorAutomatico() {

  const peso       = document.getElementById("param26")?.value || 0;
  const volumen    = document.getElementById("param27")?.value || 0;
  const ciudadOri  = document.getElementById("param4")?.value || "";
  const ciudadDes  = document.getElementById("param11")?.value || "";
  const tipoServ   = document.getElementById("param113")?.value || "";
  const seguro     = document.getElementById("param18")?.value || 0;
  const tipoPago   = document.getElementById("param28")?.value || "";

  const creditoSel = document.getElementById("cliente_credito");
  const rel_nom_credito = creditoSel ? creditoSel.value : "";
  const tipocliente = rel_nom_credito ? 1 : 0;

  console.log("🧮 calcularValorAutomatico()", {
    peso, volumen, ciudadOri, ciudadDes,
    tipoServ, tipoPago, rel_nom_credito, tipocliente
  });

  // 🚫 SIN SERVICIO NO SE CALCULA
  if (!tipoServ) {
    console.warn("⛔ No hay tipo de servicio");
    return;
  }

  // 🚫 Servicio especial 1000
  if (tipoServ == 1000) {
    console.warn("⛔ Servicio 1000 → no se calcula");
    return;
  }

  // 🚫 Sin peso ni volumen
  if (!peso && !volumen) {
    console.warn("⛔ Sin peso ni volumen");
    return;
  }

  const fd = new FormData();
  fd.append("accion", "calcularValorTotal");
  fd.append("peso", peso);
  fd.append("volumen", volumen);
  fd.append("ciudadOri", ciudadOri);
  fd.append("ciudadDes", ciudadDes);
  fd.append("tipoServ", tipoServ);
  fd.append("pordeclarado", seguro);
  fd.append("tipoPago", tipoPago);
  fd.append("rel_nom_credito", rel_nom_credito);
  fd.append("tipocliente", tipocliente);

  try {
    const resp = await fetch("../controller/RecogidasMovilController.php", {
      method: "POST",
      body: fd
    });

    const data = await resp.json();
    console.log("💰 Respuesta cálculo:", data);

    if (data.ok) {
      if (document.getElementById("param111"))
        document.getElementById("param111").value = data.total;
        document.getElementById("valorSinSeguro").value = data.valorsinseguro;
      
    }

  } catch (e) {
    console.error("💥 Error calcularValorAutomatico:", e);
  }
}

// selectServicio.addEventListener("change", function () {
//   setValue("select_tipo_servicio", this.value);
//   calcularValorAutomatico(); // 🔥 AQUÍ
// });
document.getElementById("param26")?.addEventListener("input", calcularValorAutomatico);
document.getElementById("param27")?.addEventListener("input", calcularValorAutomatico);
document.getElementById("param18")?.addEventListener("input", calcularValorAutomatico);


// document.getElementById("form1").addEventListener("submit", function (e) {
//   e.preventDefault();

//   const form = this;
//   const fd = new FormData(form);
//   fd.append("accion", "guardarRecogida");

//   fetch("../controller/RecogidasMovilController.php", {
//     method: "POST",
//     body: fd
//   })
//   .then(r => r.json())
//   .then(data => {
//     console.log("GUARDAR:", data);

//     if (!data.ok) {
//       alert(data.mensaje || "Error al guardar");
//       return;
//     }

//     // ✅ ALERTA DE CONFIRMACIÓN
//     alert("Guía creada correctamente: " + data.guia);

//     // ⏭️ DESPUÉS DE CERRAR ALERTA → ABRIR MODAL
//     document.getElementById("idservicio_firma").value = data.idservicio;
//     document.getElementById("nombre_receptor").value = fd.get("param9") || "";
//     document.getElementById("telefono_receptor").value = fd.get("param8") || "";

//     const modal = new bootstrap.Modal(
//       document.getElementById("modalFirma"),
//       {
//         backdrop: "static",
//         keyboard: false
//       }
//     );

//     modal.show();
//   })
//   .catch(err => {
//     console.error(err);
//     alert("Error de servidor");
//   });
// });
document.getElementById("form1").addEventListener("submit", function (e) {
  e.preventDefault();

  mostrarCargando("Obteniendo ubicación GPS...", "Espere un momento");

  if (!navigator.geolocation) {
    console.warn("GPS no disponible");
    enviarFormulario();
    return;
  }

  navigator.geolocation.getCurrentPosition(
    function (pos) {
      document.getElementById("latitud").value = pos.coords.latitude;
      document.getElementById("longitud").value = pos.coords.longitude;
      document.getElementById("precision_gps").value = pos.coords.accuracy;

      console.log("📍 Ubicación capturada", pos.coords);

      enviarFormulario();
    },
    function (err) {
      console.warn("⚠️ No se pudo obtener GPS", err);
      enviarFormulario();
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    }
  );
});

function enviarFormulario() {
 const nombreRecibe = document.getElementById("param92")?.value || "";

  if (!nombreCompletoValido(nombreRecibe)) {
    Swal.fire({
      icon: "warning",
      title: "Nombre incompleto",
      text: "Debe ingresar nombre y apellido de quien recibe"
    });
    return; // 🚫 NO ENVÍA EL FORMULARIO
  }
  const imagenTransaccion = document.getElementById("imagen_transaccion");
  if (actualizarRequeridoImagenTransaccion() && (!imagenTransaccion.files || imagenTransaccion.files.length === 0)) {
    Swal.fire({
      icon: "warning",
      title: "Falta imagen",
      text: "Debe adjuntar la imagen de la transaccion para Davivienda o Bancolombia."
    });
    imagenTransaccion.classList.add("is-invalid");
    imagenTransaccion.focus();
    return;
  }

  mostrarCargando("Guardando información...", "No cierres la ventana");

  const form = document.getElementById("form1");
  const fd = new FormData(form);
  fd.append("accion", "guardarRecogida");

  fetch("../controller/RecogidasMovilController.php", {
    method: "POST",
    body: fd
  })
  .then(r => r.json())
  .then(data => {
    cerrarCargando(); // 🔥 cerramos loader

    console.log("GUARDAR:", data);

    if (!data.ok) {
      Swal.fire("Error", data.mensaje || "Error al guardar", "error");
      return;
    }

    Swal.fire({
      icon: "success",
      title: "Guía creada por favor revise que el cliente ingrese al link y firme posteriormente puede dar click en finalizar.",
      text: "Guía Nº: " + data.guia,
      confirmButtonText: "Continuar"
    });

    document.getElementById("idservicio_firma").value = data.idservicio;
    document.getElementById("nombre_receptor").value = fd.get("param92") || "";
    document.getElementById("telefono_receptor").value = fd.get("param93") || "";
    document.getElementById("link").value = data.link;


    const modal = new bootstrap.Modal(
      document.getElementById("modalFirma"),
      { backdrop: "static", keyboard: false }
    );

    setEstadoFirma("Esperando firma...", "text-warning", "fa-clock");
    modal.show();

  })
  .catch(err => {
    cerrarCargando();
    console.error(err);
    Swal.fire("Error de servidor", "Intente nuevamente", "error");
  });
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

document.getElementById("btnFinalizarLegacy")?.addEventListener("click", function () {

  // 🔒 cerrar modal
  const modalEl = document.getElementById("modalFirma");
  const modal = bootstrap.Modal.getInstance(modalEl);
  modal.hide();

  // 🧹 limpiar formulario principal
  // const form = document.getElementById("form1");
  // form.reset();

  // // 🧹 limpiar campos ocultos / auxiliares
  // document.getElementById("idservicio_firma").value = "";
  // document.getElementById("nombre_receptor").value = "";
  // document.getElementById("telefono_receptor").value = "";

  console.log("Formulario limpio, listo para nueva recogida");
});


function mostrarSpinner(id) {
  const sp = document.getElementById(id);
  if (sp) sp.style.display = "block";
}

function ocultarSpinner(id) {
  const sp = document.getElementById(id);
  if (sp) sp.style.display = "none";
}


function nombreCompletoValido(nombre) {
  if (!nombre) return false;

  // Quita espacios dobles y extremos
  nombre = nombre.trim().replace(/\s+/g, " ");

  // Debe tener al menos dos palabras
  const partes = nombre.split(" ");
  return partes.length >= 2;
}

let consultaEnCursoFirma = false;

function setEstadoFirma(texto, clase = "text-warning", icono = "fa-clock") {
  const el = document.getElementById("estadoFirma");
  if (!el) return;

  el.className = clase;
  el.innerHTML = `<i class="fas ${icono} me-1"></i> ${texto}`;
}

async function consultarEstadoFirma(mostrarAlertaSinServicio = true) {
  const idservicio = document.getElementById("idservicio_firma")?.value || "";
  if (!idservicio) {
    setEstadoFirma("No hay servicio para validar", "text-danger", "fa-circle-exclamation");
    if (mostrarAlertaSinServicio) {
      Swal.fire("Sin servicio", "Primero debes guardar la recogida para poder verificar la firma.", "warning");
    }
    return false;
  }

  if (consultaEnCursoFirma) return false;

  consultaEnCursoFirma = true;

  try {
    const r = await fetch(`../controller/RecogidasMovilController.php?accion=consultarEstadoFirma&id=${encodeURIComponent(idservicio)}`, {
      method: "GET"
    });
    const raw = await r.text();
    const limpio = (raw || "").replace(/^\uFEFF/, "").trim();

    if (!r.ok) {
      throw new Error(`HTTP ${r.status}: ${limpio}`);
    }

    const resp = JSON.parse(limpio);

    if (resp && resp.ok && resp.firmada === true) {
      setEstadoFirma("Ya esta firmada", "text-success", "fa-check-circle");
      return true;
    }

    setEstadoFirma("Esperando firma...", "text-warning", "fa-clock");
    return false;
  } catch (err) {
    console.error("Error consultando estado de firma:", err);
    setEstadoFirma("No se pudo validar la firma", "text-danger", "fa-circle-exclamation");
    return false;
  } finally {
    consultaEnCursoFirma = false;
  }
}

const bloqueFirma = document.getElementById("bloqueFirma");
const bloqueSello = document.getElementById("bloqueSello");

document.getElementById("btnOpcionFirma").addEventListener("click", () => {
  bloqueFirma.classList.remove("d-none");
  bloqueSello.classList.add("d-none");
});

document.getElementById("btnOpcionSello").addEventListener("click", () => {
  bloqueSello.classList.remove("d-none");
  bloqueFirma.classList.add("d-none");
});
//Reenviar link 
document.getElementById("btnEnviarFirma").addEventListener("click", function () {
  const idservicio = document.getElementById("idservicio_firma").value;
  const nombre = document.getElementById("nombre_receptor").value;
  const telefono = document.getElementById("telefono_receptor").value;
  const link = document.getElementById("link").value;



  if (!telefono) {
    Swal.fire("Falta teléfono", "Debe ingresar el WhatsApp", "warning");
    return;
  }

  const fd = new FormData();
  fd.append("accion", "enviarLinkFirma");
  fd.append("idservicio", idservicio);
  fd.append("nombre", nombre);
  fd.append("telefono", telefono);
   fd.append("link", link);

  fetch("../controller/RecogidasMovilController.php", {
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
  });
});
//Verificar firma
document.getElementById("btnVerificarFirma").addEventListener("click", function () {
  setEstadoFirma("Validando firma...", "text-info", "fa-spinner");
  consultarEstadoFirma();
});

//Guardar sello
document.getElementById("btnGuardarSello").addEventListener("click", function () {
  const idservicio = document.getElementById("idservicio_firma").value;
  const fileInput = document.getElementById("imagen_sello");
  const archivo = fileInput.files[0];

  if (!archivo) {
    Swal.fire("Falta imagen", "Debe subir el sello", "warning");
    return;
  }

  const reader = new FileReader();

  reader.onload = function (e) {
    const base64 = e.target.result;

    const fd = new FormData();
    fd.append("accion", "guardarSello");
    fd.append("idservicio", idservicio);
    fd.append("firmaBase64", base64);

    fetch("../controller/RecogidasMovilController.php", {
      method: "POST",
      body: fd
    })
    .then(r => r.json())
    .then(resp => {
      if (!resp.ok) {
        Swal.fire("Error", resp.mensaje || "No se pudo guardar", "error");
        return;
      }

      Swal.fire("Sello guardado", "Proceso finalizado ✔", "success");
      finalizarProceso();
    });
  };

  reader.readAsDataURL(archivo);
});

//Finalizar 

async function finalizarProceso() {
  const btnFinalizar = document.getElementById("btnFinalizar");
  if (btnFinalizar) {
    btnFinalizar.disabled = true;
    btnFinalizar.textContent = "Verificando...";
  }

  setEstadoFirma("Validando firma...", "text-info", "fa-spinner");
  const firmada = await consultarEstadoFirma(false);

  if (!firmada) {
    if (btnFinalizar) {
      btnFinalizar.disabled = false;
      btnFinalizar.textContent = "Finalizar";
    }

    Swal.fire({
      icon: "warning",
      title: "Firma pendiente",
      text: "El servicio todavia no esta firmado. Verifica la firma o sube el sello antes de finalizar."
    });
    return;
  }

  const modalEl = document.getElementById("modalFirma");
  const modal = bootstrap.Modal.getInstance(modalEl);
  modal.hide();

  document.getElementById("form1").reset();

  document.getElementById("idservicio_firma").value = "";
  document.getElementById("nombre_receptor").value = "";
  document.getElementById("telefono_receptor").value = "";
  document.getElementById("imagen_sello").value = "";
  if (btnFinalizar) {
    btnFinalizar.disabled = false;
    btnFinalizar.textContent = "Finalizar";
  }
  setEstadoFirma("Esperando firma...", "text-warning", "fa-clock");

  bloqueFirma.classList.add("d-none");
  bloqueSello.classList.add("d-none");

  window.location.assign("/inicio.php");
}

document.getElementById("btnFinalizar").addEventListener("click", finalizarProceso);

// Para rellenar direccion completa 
function aplicarDatosComplemento(tipoSelectId, contenedorId, estructuraCampos, datosExtras, sufijo, construirTextoFinal) {
  const select = document.getElementById(tipoSelectId);
  const contenedor = document.getElementById(contenedorId);
  if (!select || !contenedor) return;

  const tipo = select.value;
  const campos = estructuraCampos[tipo];
  if (!campos) return;

  contenedor.innerHTML = "";

  campos.forEach(campo => {
    contenedor.appendChild(
      crearCampoCheckConNumero(campo, construirTextoFinal, sufijo)
    );
  });

  datosExtras.forEach(texto => {
    const limpio = texto.trim().toUpperCase();

    campos.forEach(campo => {
      if (limpio.startsWith(campo.label)) {
        const valor = limpio.replace(campo.label, "").trim();

        const check = document.getElementById(campo.name + "_check_" + sufijo);
        const input = document.querySelector(`[name="${campo.name}_${sufijo}"]`);

        if (check && input) {
          check.checked = true;
          input.disabled = false;
          input.value = valor;
        }
      }
    });
  });

  // 🔥 ESTA LÍNEA ERA LA QUE FALTABA
  if (construirTextoFinal) construirTextoFinal();
}







document.addEventListener("DOMContentLoaded", function () {
  const select = document.getElementById("param19");
  const contenedor = document.getElementById("camposComplemento");
  const campoFinal = document.getElementById("complementoFinal");

    // function construirTextoFinalRem() {
    //   const partes = [];
    //   const tipo = document.getElementById("param19").value;
    //   const campos = estructuraCamposRemitente[tipo];
    //   if (!campos) return;

    //   campos.forEach(campo => {
    //     const check = document.getElementById(campo.name + "_check_R");
    //     const input = document.querySelector(`[name="${campo.name}_R"]`);
    //     if (check?.checked && input.value.trim() !== "") {
    //       partes.push(`${campo.label} ${input.value.trim()}`);
    //     }
    //   });

    //   document.getElementById("complementoFinal").value = partes.join(" & ");
    // }

    select.addEventListener("change", function () {
      contenedor.innerHTML = "";
      campoFinal.value = "";

      const tipo = this.value;
      if (!estructuraCamposRemitente[tipo]) return;

      estructuraCamposRemitente[tipo].forEach(campo => {
        contenedor.appendChild(
          crearCampoCheckConNumero(campo, construirTextoFinalRem, "R")
        );
      });
    });
});




document.addEventListener("DOMContentLoaded", function () {
  const select = document.getElementById("param21");
  const contenedor = document.getElementById("camposComplementoD");
  const campoFinal = document.getElementById("complementoFinalD");

  // function construirTextoFinalDest() {
  //   const partes = [];
  //   const tipo = document.getElementById("param21").value;
  //   const campos = estructuraCamposDest[tipo];
  //   if (!campos) return;

  //   campos.forEach(campo => {
  //     const check = document.getElementById(campo.name + "_check_D");
  //     const input = document.querySelector(`[name="${campo.name}_D"]`);
  //     if (check?.checked && input.value.trim() !== "") {
  //       partes.push(`${campo.label} ${input.value.trim()}`);
  //     }
  //   });

  //   document.getElementById("complementoFinalD").value = partes.join(" & ");
  // }


  select.addEventListener("change", function () {
    contenedor.innerHTML = "";
    campoFinal.value = "";

    const tipo = this.value;
    if (!estructuraCamposDest[tipo]) return;

    estructuraCamposDest[tipo].forEach(campo => {
      contenedor.appendChild(
        crearCampoCheckConNumero(campo, construirTextoFinalDest, "D")
      );
    });
  });
});




function validarMinimo(input) {
  if (input.value === "") return; // permite borrar mientras escribe
  
  if (parseInt(input.value) < 100000) {
    input.value = 100000;
  }
}

//Todo en mayuscula 
document.addEventListener("DOMContentLoaded", function () {
  const formulario = document.getElementById("form1");

  formulario.addEventListener("input", function (e) {
    const el = e.target;

    if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {

      const excluir = ["email", "password", "number", "date", "datetime-local", "hidden", "checkbox", "file"];

      if (!excluir.includes(el.type)) {

        // SOLO si soporta selección de texto
        if (typeof el.selectionStart === "number") {
          const inicio = el.selectionStart;
          const fin = el.selectionEnd;

          el.value = el.value.toUpperCase();
          el.setSelectionRange(inicio, fin);
        } else {
          el.value = el.value.toUpperCase();
        }
      }
    }
  });
});


//Para OFICINA TRANSMILLAS 
document.addEventListener("DOMContentLoaded", function () {

  const tipoVia = document.getElementById("param5");
  if (!tipoVia) return;
  const dir1R = document.getElementById("dir1R");
  const dir2R = document.getElementById("dir2R");
  const dir3R = document.getElementById("dir3R");
  const selectComplemento = document.getElementById("param19");
  const contenedorComplemento = document.getElementById("camposComplemento");
  const campoFinalComplemento = document.getElementById("complementoFinal");

  const camposDireccion = [dir1R, dir2R, dir3R, selectComplemento];
  const bloquesOcultables = [
    document.getElementById("wrapDir1R"),
    document.getElementById("sepDirHashR"),
    document.getElementById("wrapDir2R"),
    document.getElementById("sepDirGuionR"),
    document.getElementById("wrapDir3R"),
    document.getElementById("wrapComplementoR"),
    contenedorComplemento
  ];

  function limpiarDerivadosComplemento() {
    if (contenedorComplemento) contenedorComplemento.innerHTML = "";
    if (campoFinalComplemento) campoFinalComplemento.value = "";
    if (selectComplemento) selectComplemento.value = "";
  }

  function actualizarRequeridos() {
    const valor = tipoVia.value.trim().toUpperCase();

    if (valor === "OFICINA TRANSMILLAS") {
      camposDireccion.forEach(campo => {
        if (!campo) return;
        campo.removeAttribute("required");
        campo.value = "";
      });

      limpiarDerivadosComplemento();
      bloquesOcultables.forEach(bloque => bloque?.classList.add("d-none"));
    } else {
      camposDireccion.forEach(campo => {
        if (campo) campo.setAttribute("required", "required");
      });

      bloquesOcultables.forEach(bloque => bloque?.classList.remove("d-none"));
    }
  }

  // Ejecutar al cambiar
  tipoVia.addEventListener("change", actualizarRequeridos);

  // Ejecutar si ya viene preseleccionado
  actualizarRequeridos();

});

// Para OFICINA TRANSMILLAS en destinatario
document.addEventListener("DOMContentLoaded", function () {

  const tipoVia = document.getElementById("param10");
  if (!tipoVia) return;
  const dir1D = document.getElementById("dir1D");
  const dir2D = document.getElementById("dir2D");
  const dir3D = document.getElementById("dir3D");
  const selectComplemento = document.getElementById("param21");
  const contenedorComplemento = document.getElementById("camposComplementoD");
  const campoFinalComplemento = document.getElementById("complementoFinalD");

  const camposDireccion = [dir1D, dir2D, dir3D, selectComplemento];
  const bloquesOcultables = [
    document.getElementById("wrapDir1D"),
    document.getElementById("sepDirHashD"),
    document.getElementById("wrapDir2D"),
    document.getElementById("sepDirGuionD"),
    document.getElementById("wrapDir3D"),
    document.getElementById("wrapComplementoD"),
    contenedorComplemento
  ];

  function limpiarDerivadosComplemento() {
    if (contenedorComplemento) contenedorComplemento.innerHTML = "";
    if (campoFinalComplemento) campoFinalComplemento.value = "";
    if (selectComplemento) selectComplemento.value = "";
  }

  function actualizarRequeridos() {
    const valor = tipoVia.value.trim().toUpperCase();

    if (valor === "OFICINA TRANSMILLAS") {
      camposDireccion.forEach(campo => {
        if (!campo) return;
        campo.removeAttribute("required");
        campo.value = "";
      });

      limpiarDerivadosComplemento();
      bloquesOcultables.forEach(bloque => bloque?.classList.add("d-none"));
    } else {
      camposDireccion.forEach(campo => {
        if (campo) campo.setAttribute("required", "required");
      });

      bloquesOcultables.forEach(bloque => bloque?.classList.remove("d-none"));
    }
  }

  tipoVia.addEventListener("change", actualizarRequeridos);
  actualizarRequeridos();

});

// Si es contado peso es obligatorio 
document.addEventListener("DOMContentLoaded", function () {

  const tipoPago = document.getElementById("param28");
  const pesoKg   = document.getElementById("param26");

  function actualizarPesoObligatorio() {
    if (!tipoPago || !pesoKg) return;

    if (tipoPago.value === "1") { // Contado
      pesoKg.setAttribute("required", "required");
    } else {
      pesoKg.removeAttribute("required");
    }
  }

  // Cuando cambie el tipo de pago
  tipoPago.addEventListener("change", actualizarPesoObligatorio);

  // Por si el formulario carga con valor ya seleccionado
  actualizarPesoObligatorio();

});

//Si pone peso alertar de posible volumen
document.addEventListener("DOMContentLoaded", function () {

  const pesoKg = document.getElementById("param26");

  if (!pesoKg) return;

  pesoKg.addEventListener("blur", function () {
    const valor = parseFloat(pesoKg.value);

    if (!isNaN(valor) && valor > 0) {
      Swal.fire({
        icon: 'info',
        title: 'Verificación de volumen',
        text: 'Por favor verifica si este servicio también tiene volumen.',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#0d6efd'
      });
    }
  });

});

