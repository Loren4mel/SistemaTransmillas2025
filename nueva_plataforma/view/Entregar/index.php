<?php
// Seguridad básica similar a otros módulos (comentado como lo dejaste)
// if (!isset($_POST['sede']) || !isset($_POST['acceso']) || !isset($_POST['usuario'])) {
//     echo "<script>
//             alert('No tiene acceso a esta página');
//             window.close();
//           </script>";
//     exit;
// }

require("../../login_autentica.php");
$sede= $_SESSION['usu_idsede'];
$usuario= $_SESSION['usuario_id'];
$nombre=$_SESSION['usuario_nombre'];
$acceso=$_SESSION['usuario_rol'];
$precioinicialkilos=$_SESSION['precioinicial'];



date_default_timezone_set('America/Bogota');

// $sede    = $_GET['sede']  ?? '';
// $acceso  = $_GET['acceso'] ?? '';
// $usuario = $_GET['usuario'] ?? '';
// $nombre = $_GET['nombre'] ?? '';
$porcobrar = $_GET['porcobrar'] ?? '';


// 🔹 ID del servicio llega por GET como idServicio
$idservicio = isset($_GET['idServicio']) ? $_GET['idServicio'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Entregar Servicio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    .mi-header {
      background-color: #00458D;
      color: white;
    }
    .section-title {
      background-color: #01468c;
      color: #fff;
      padding: 0.5rem 0.75rem;
      font-weight: 600;
      border-radius: 0.25rem;
      margin-bottom: 1rem;
    }
    .readonly-input {
      background-color: #f8f9fa;
    }
    .error {
      color: red;
      font-size: 0.85rem;
      display: none;
    }
    .label-strong {
      font-weight: 600;
    }
    .firma-panel {
      border: 1px solid #dee2e6;
      border-radius: 0.5rem;
      padding: 1rem;
      background: #f8f9fa;
      position: relative;
    }
    .firma-estado {
      min-height: 31px;
    }
    .firma-guia-btn {
      border: 0;
      background: transparent;
      color: #0d6efd;
      font-size: 0.9rem;
      padding: 0;
    }
    .firma-tour-activo {
      box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.22);
      border-radius: 0.375rem;
    }
    .firma-aviso-guia {
      display: none;
      border: 1px solid #b6d4fe;
      border-left: 4px solid #0d6efd;
      border-radius: 0.375rem;
      background: #eef5ff;
      color: #084298;
      padding: 0.75rem;
      margin-bottom: 1rem;
      transition: opacity 0.35s ease;
    }
    .firma-aviso-guia.mostrar {
      display: flex;
    }
    .firma-aviso-guia.ocultando {
      opacity: 0;
    }
  </style>
</head>
<body>
  <!-- Barra superior (comentada como la dejaste) -->
  <!-- <nav class="navbar navbar-expand-lg topbar" style="background:#00458D;color:white;">
    <div class="container-fluid">
      <button class="btn btn-light" onclick="history.back()">⬅ Volver</button>
      <span class="navbar-text ms-3">Módulo: Entregar</span>
    </div>
  </nav> -->

  <div class="container-fluid mt-4">
    <div class="card shadow p-3 mb-4 bg-body rounded">
      <div class="card-header text-center mi-header">
        <h3 class="mb-0">Entrega de Servicio</h3>
      </div>

      <div class="card-body">

        <!-- Ya no hay buscador, porque el idServicio llega por GET -->

        <hr>

        <!-- SELECTOR DE ACCIÓN -->
        <div class="row mb-4">
          <div class="col-md-4">
            <label class="form-label fw-bold">Seleccione acción</label>
            <select id="tipoAccion" class="form-select">
              <option value="">Seleccione...</option>
              <option value="entregar">ENTREGAR</option>
              <option value="noentregado">NO ENTREGADO</option>
            </select>
          </div>
        </div>

        <!-- ===================== FORMULARIO NO ENTREGADO ====================== -->
        <div id="formNoEntregado" style="display:none;">

          <div class="section-title">No entregado</div>

          <div class="row mb-3">
            <div class="col-md-8">
              <label class="form-label fw-bold">Motivo *</label>
              <input type="text" id="motivo_noentrega" class="form-control" placeholder="Escriba el motivo">
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-8">
              <label class="form-label fw-bold">Foto evidencia *</label>
              <input type="file" id="foto_evidencia" accept="image/*" class="form-control">
            </div>
          </div>

          <button type="button" id="btnGuardarNoEntregar" onclick="guardarNoEntregar()"
                  class="btn btn-danger">
            <i class="bi bi-x-circle"></i> Guardar No Entregado
          </button>


          <hr>
        </div>

        <!-- ===================== FORMULARIO PRINCIPAL ENTREGAR ====================== -->
        <form id="formEntregar" enctype="multipart/form-data" style="display:none;">

          <!-- Necesario para el controlador -->
          <input type="hidden" name="accion" value="guardarEntrega">
          <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>">
          <input type="hidden" name="idservicio" id="idservicio" value="<?php echo htmlspecialchars($idservicio); ?>">

          <!-- ========== SECCIÓN: DATOS DEL SERVICIO (equivalente a "Datos") ========== -->
          <div class="section-title">Datos del servicio</div>

          <div class="row mb-3">
            <?php if ($acceso == 1): ?>
            <div class="col-md-4">
              <label for="fecha_entrega" class="form-label label-strong">Fecha Entrega</label>
              <input type="date" id="fecha_entrega" name="fecha_entrega" class="form-control" 
                     value="<?php echo date('Y-m-d'); ?>">
            </div>
            <?php endif; ?>

            <div class="col-md-4">
              <label class="form-label label-strong">Número de piezas</label>
              <input type="number" name="ser_piezas" id="ser_piezas" class="form-control readonly-input" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label label-strong">Dice contener</label>
              <input type="text" name="ser_paquetedescripcion" id="ser_paquetedescripcion" 
                     class="form-control readonly-input" readonly>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label label-strong">Devolver Recibido</label>
              <input type="text" id="ser_devolverreci_text" class="form-control readonly-input" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label label-strong">Tipo Pago</label>
              <input type="text" id="tipo_pago" class="form-control readonly-input" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label label-strong">Peso</label>
              <input type="text" name="ser_peso" id="ser_peso" class="form-control readonly-input" readonly>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label label-strong">Vr Declarado</label>
              <input type="text" name="ser_valorseguro" id="ser_valorseguro" 
                     class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label label-strong">Vr Flete</label>
              <input type="text" name="ser_valor" id="ser_valor" class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label label-strong">Guía</label>
              <input type="text" name="ser_guiare" id="ser_guiare" class="form-control readonly-input" readonly>
            </div>
          </div>

          <!-- ========== SECCIÓN: TOTALES (equivalente a "TOTALES") ========== -->
          <div class="section-title mt-4">Totales</div>

          <div class="row mb-3">
            <div class="col-md-3">
              <label class="form-label label-strong">Valor de Préstamo</label>
              <input type="text" id="valor_prestamo" name="valor_prestamo" 
                     class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label label-strong">% Vr Declarado</label>
              <input type="text" id="porc_vr_declarado" name="porc_vr_declarado" 
                     class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label label-strong">Cobro x Préstamo</label>
              <input type="text" id="cobro_prestamo" name="cobro_prestamo" 
                     class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label label-strong">Vr Flete</label>
              <input type="text" id="vr_flete_tot" name="vr_flete_tot" 
                     class="form-control readonly-input" readonly>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-3">
              <label class="form-label label-strong">Abono</label>
              <input type="text" id="abono" name="abono" class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label label-strong">TOTAL PRÉSTAMO</label>
              <input type="text" id="total_prestamo" name="total_prestamo" 
                     class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label label-strong">TOTAL FLETE</label>
              <input type="text" id="total_flete" name="total_flete" 
                     class="form-control readonly-input" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label label-strong">TOTAL</label>
              <input type="text" id="total_final" name="param12" 
                     class="form-control readonly-input" readonly>
            </div>
          </div>

          <div class="row mb-3" id="rowDevolucion" style="display:none;">
            <div class="col-md-3">
              <label class="form-label label-strong">DEVOLUCIÓN</label>
              <input type="text" id="devolucion" name="param19" 
                     class="form-control readonly-input" readonly>
            </div>
          </div>

          <!-- ========== SECCIÓN: MÉTODO DE PAGO (solo si Al Cobro o pendiente cobrar) ========== -->
          <div class="section-title mt-4">Pago</div>

          <div id="bloqueMetodoPago" style="display:none;">
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label label-strong">Método de pago</label>
                <select name="param30" id="metodo_pago" class="form-select">
                  <option value="">Seleccione...</option><option value="1||Efectivo">Efectivo</option>
                  <option value="2|457800098420|DAVIVIENDA  AHORROS DAVIPLATA">DAVIVIENDA  AHORROS DAVIPLATA</option>
                  <option value="4|26400000710|BANCOLOMBIA CORRIENTE  NEQUI">BANCOLOMBIA CORRIENTE  NEQUI</option>
                  <!-- Aquí puedes cargar dinámicamente desde PHP/BD si quieres -->
                </select>
                
              </div>
              <div class="col-md-6">
                <label class="form-label label-strong">Referencias bancarias</label>
                <div class="d-flex gap-2 flex-wrap mb-3">
                  <button type="button" class="btn btn-outline-primary btn-sm" data-banco="bancolombia" onclick="toggleImagenBanco('bancolombia')">Bancolombia</button>
                  <button type="button" class="btn btn-outline-danger btn-sm" data-banco="davivienda" onclick="toggleImagenBanco('davivienda')">Davivienda</button>
                </div>
                <div id="visorImagenBanco" class="card border-0 shadow-sm mb-3" style="display:none; max-width:420px;">
                  <div class="card-body">
                    <label id="tituloImagenBanco" class="form-label label-strong mb-2">Imagen bancaria</label>
                    <img id="imagenBancoPreview" src="" alt="Referencia bancaria" class="img-fluid rounded border" />
                  </div>
                </div>
                <label class="form-label label-strong">Imagen transacción</label>
                <input type="file" name="img_transaccion" id="img_transaccion"
                       class="form-control" accept="image/*">
              </div>
            </div>
          </div>

          <!-- Si no es al cobro, enviamos 0 en param30 -->
          <input type="hidden" name="param30" id="param30_hidden" value="0">

          <!-- ========== SECCIÓN: DATOS QUIEN RECIBE ========== -->
          <div class="section-title mt-4">Datos de quien recibe</div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label label-strong">Foto (*)</label>
              <input type="file" name="param87" id="param87" class="form-control" 
                     accept="image/*" capture="environment" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label label-strong" for="param82">Nombre completo</label>
              <input type="text" id="param82" name="param82" class="form-control" required>
              <p id="errorNombre" class="error">
                Debe ingresar nombre y apellido.
              </p>
            </div>
            <div class="col-md-6">
              <label class="form-label label-strong" for="param85">Teléfono WhatsApp</label>
              <input type="text" id="param85" name="param85" class="form-control" value="+57">
            </div>
          </div>

          <!-- ========== FIRMA O SELLO ========== -->
          <div class="section-title mt-4">Firma de entrega</div>

          <div class="firma-panel">
            <input type="hidden" id="idservicio_firma">
            <input type="hidden" id="link_firma_entrega">

            <div id="avisoGuiaFirma" class="firma-aviso-guia align-items-start gap-2" role="status" aria-live="polite">
              <i class="fas fa-circle-info mt-1"></i>
              <div>
                <strong>Nueva forma de firmar:</strong>
                completa los datos, envía el link al WhatsApp y luego verifica la firma antes de guardar.
              </div>
              <button type="button" id="btnCerrarAvisoGuiaFirma" class="btn-close ms-auto" aria-label="Cerrar"></button>
            </div>

            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">

              <button type="button" id="btnGuiaFirma" class="firma-guia-btn">
                <i class="fas fa-circle-question me-1"></i> Ayuda
              </button>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <button class="btn btn-outline-primary w-100" id="btnOpcionFirma" type="button">
                  <i class="fas fa-paper-plane me-1"></i> Enviar link de firma
                </button>
              </div>
              <div class="col-md-6">
                <button class="btn btn-outline-secondary w-100" id="btnOpcionSello" type="button">
                  <i class="fas fa-stamp me-1"></i> Subir sello
                </button>
              </div>
            </div>

            <div class="firma-estado d-flex align-items-center gap-2 flex-wrap mb-3">
              <small id="estadoFirma" class="text-warning">
                <i class="fas fa-clock me-1"></i> Esperando firma...
              </small>
              <button type="button" id="btnVerificarFirma" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-rotate-right me-1"></i> Verificar firma
              </button>
            </div>

            <div id="bloqueSello" class="d-none">
              <hr>
              <div class="mb-2">
                <label class="form-label label-strong" for="imagen_sello">Subir sello</label>
                <input type="file" id="imagen_sello" class="form-control" accept="image/*">
              </div>
              <button class="btn btn-dark" id="btnGuardarSello" type="button">
                Guardar sello
              </button>
            </div>
          </div>

          <!-- ========== CAMPOS OCULTOS QUE TENÍAS EN TU CÓDIGO ========== -->
          <input type="hidden" name="param9"  id="param9">   <!-- ser_ciudadentrega -->
          <input type="hidden" name="param22" id="param22">  <!-- cli_idciudad -->
          <input type="hidden" name="param10" id="param10">  <!-- tipo pago/clasificación -->
          <input type="hidden" name="param11" id="param11">  <!-- ser_guiare -->
          <input type="hidden" name="iduserentrega" id="iduserentrega"> <!-- ser_idusuarioguia -->
          <input type="hidden" name="param20" id="param20">  <!-- kiliostotal -->
          <input type="hidden" name="param21" id="param21">  <!-- gui_tiposervicio -->
          <input type="hidden" name="ser_pendientecobrar" id="ser_pendientecobrar">


          <input type="hidden" name="id_usuario" id="id_usuario" value="<?echo$usuario;?>">  <!-- gui_tiposervicio -->
          <input type="hidden" name="id_sedes" id="id_sedes" value="<?echo$sede;?>">  <!-- gui_tiposervicio -->
          <input type="hidden" name="id_nombre" id="id_nombre" value="<?echo$nombre;?>">  <!-- gui_tiposervicio -->
          <input type="hidden" name="cambios" id="cambios" value="<?echo$porcobrar;?>">  <!-- gui_tiposervicio -->
          <input type="hidden" name="param114" id="param114" value="">  <!-- gui_tiposervicio -->




          <!-- BOTÓN GUARDAR -->
          <div class="mt-4">
            <button type="button" id="btnGuardar" class="btn btn-success" onclick="guardarEntregar()">
              <i class="bi bi-save"></i> Guardar
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    const GUIA_FIRMA_KEY = "guiaFirmaEntregaVistaV1";
    let guiaFirmaTimeouts = [];
    let popoverGuiaFirmaActual = null;

    function localStorageSeguro() {
      try {
        return window.localStorage;
      } catch (e) {
        return null;
      }
    }

    function limpiarGuiaFirma() {
      guiaFirmaTimeouts.forEach(timeoutId => clearTimeout(timeoutId));
      guiaFirmaTimeouts = [];

      if (popoverGuiaFirmaActual) {
        popoverGuiaFirmaActual.hide();
        popoverGuiaFirmaActual.dispose();
        popoverGuiaFirmaActual = null;
      }

      document.querySelectorAll(".firma-tour-activo").forEach(el => {
        el.classList.remove("firma-tour-activo");
      });
    }

    function ocultarAvisoGuiaFirma() {
      const aviso = document.getElementById("avisoGuiaFirma");
      if (!aviso) return;

      aviso.classList.add("ocultando");
      guiaFirmaTimeouts.push(setTimeout(() => {
        aviso.classList.remove("mostrar", "ocultando");
      }, 350));
    }

    function mostrarAvisoGuiaFirma() {
      const aviso = document.getElementById("avisoGuiaFirma");
      if (!aviso) return;

      aviso.classList.remove("ocultando");
      aviso.classList.add("mostrar");

      guiaFirmaTimeouts.push(setTimeout(ocultarAvisoGuiaFirma, 8500));
    }

    function mostrarPasoGuiaFirma(paso, siguiente) {
      const objetivo = document.getElementById(paso.id);
      if (!objetivo || typeof bootstrap === "undefined") {
        siguiente();
        return;
      }

      if (popoverGuiaFirmaActual) {
        popoverGuiaFirmaActual.hide();
        popoverGuiaFirmaActual.dispose();
      }

      document.querySelectorAll(".firma-tour-activo").forEach(el => {
        el.classList.remove("firma-tour-activo");
      });

      objetivo.classList.add("firma-tour-activo");
      popoverGuiaFirmaActual = new bootstrap.Popover(objetivo, {
        title: paso.titulo,
        content: paso.texto,
        placement: paso.ubicacion || "top",
        trigger: "manual",
        container: "body"
      });

      popoverGuiaFirmaActual.show();

      guiaFirmaTimeouts.push(setTimeout(() => {
        objetivo.classList.remove("firma-tour-activo");
        if (popoverGuiaFirmaActual) {
          popoverGuiaFirmaActual.hide();
          popoverGuiaFirmaActual.dispose();
          popoverGuiaFirmaActual = null;
        }
        siguiente();
      }, paso.duracion || 5200));
    }

    function iniciarGuiaFirma(forzar = false) {
      if ($('#tipoAccion').val() !== 'entregar') return;

      const storage = localStorageSeguro();
      if (!forzar && storage?.getItem(GUIA_FIRMA_KEY) === "1") return;

      limpiarGuiaFirma();
      mostrarAvisoGuiaFirma();

      if (!forzar) {
        storage?.setItem(GUIA_FIRMA_KEY, "1");
      }

      document.querySelector(".firma-panel")?.scrollIntoView({
        behavior: "smooth",
        block: "center"
      });

      const pasos = [
        {
          id: "btnOpcionFirma",
          titulo: "1. Enviar link",
          texto: "Después de escribir nombre y WhatsApp, este botón envía el link para que el cliente firme desde su celular.",
          ubicacion: "top"
        },
        {
          id: "btnVerificarFirma",
          titulo: "2. Verificar",
          texto: "Cuando el cliente avise que firmó, valida aquí. El estado cambiará a firmado si ya quedó registrado.",
          ubicacion: "top"
        },
        {
          id: "btnOpcionSello",
          titulo: "3. Sello como respaldo",
          texto: "Si no pueden firmar por link, puedes subir una imagen del sello y guardar la entrega con ese soporte.",
          ubicacion: "top"
        }
      ];

      let indice = 0;
      const siguiente = () => {
        if (indice >= pasos.length) {
          limpiarGuiaFirma();
          return;
        }
        mostrarPasoGuiaFirma(pasos[indice], () => {
          indice += 1;
          siguiente();
        });
      };

      guiaFirmaTimeouts.push(setTimeout(siguiente, 700));
    }

    // ========================= SELECT ACCIÓN ==========================
    $('#tipoAccion').on('change', function() {
      let accion = $(this).val();

      if (accion === "entregar") {
        $('#formEntregar').show();
        $('#formNoEntregado').hide();
        setTimeout(() => iniciarGuiaFirma(false), 350);
      } else if (accion === "noentregado") {
        $('#formEntregar').hide();
        $('#formNoEntregado').show();
        limpiarGuiaFirma();
      } else {
        $('#formEntregar').hide();
        $('#formNoEntregado').hide();
        limpiarGuiaFirma();
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
        dataType: 'json',
        success: function (servicio) {
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

          // Preparar datos para enviar el link de firma.
          const tipoPagoFirma = servicio.tipo_pago || servicio.ser_clasificacion || '';
          const linkFirma = `${servicio.idservicios}&accion=guardarFirmaEntrega&tipo_pago=${encodeURIComponent(tipoPagoFirma)}`;
          $('#idservicio_firma').val(servicio.idservicios);
          $('#link_firma_entrega').val(linkFirma);
          setEstadoFirma("Esperando firma...", "text-warning", "fa-clock");

          // Opcional: mensaje
          // alert('Servicio cargado. Ahora seleccione la acción (Entregar / No Entregado).');
        },
        error: function () {
          alert('Error al cargar los datos del servicio.');
        }
      });
    }

    // Al cargar la página, traemos el servicio automáticamente usando el idServicio del GET
    $(document).ready(function () {
      const idServicioPHP = <?php echo json_encode($idservicio); ?>;
      if (idServicioPHP) {
        cargarServicio(idServicioPHP);
      } else {
        alert('No se recibió el parámetro idServicio en la URL.');
      }

      document.getElementById("btnGuiaFirma")?.addEventListener("click", function () {
        iniciarGuiaFirma(true);
      });

      document.getElementById("btnCerrarAvisoGuiaFirma")?.addEventListener("click", function () {
        ocultarAvisoGuiaFirma();
      });
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

    let consultaEnCursoFirma = false;

    function setEstadoFirma(texto, clase = "text-warning", icono = "fa-clock") {
      const el = document.getElementById("estadoFirma");
      if (!el) return;

      el.className = clase;
      el.innerHTML = `<i class="fas ${icono} me-1"></i> ${texto}`;
    }

    async function consultarEstadoFirma(mostrarAlertaSinServicio = true) {
      const idservicio = document.getElementById("idservicio_firma")?.value || document.getElementById("idservicio")?.value || "";
      if (!idservicio) {
        setEstadoFirma("No hay servicio para validar", "text-danger", "fa-circle-exclamation");
        if (mostrarAlertaSinServicio) {
          Swal.fire("Sin servicio", "Primero debes cargar el servicio para poder verificar la firma.", "warning");
        }
        return false;
      }

      if (consultaEnCursoFirma) return false;

      consultaEnCursoFirma = true;

      try {
        const r = await fetch(`../controller/EntregarController.php?accion=consultarEstadoFirma&id=${encodeURIComponent(idservicio)}`, {
          method: "GET"
        });
        const raw = await r.text();
        const limpio = (raw || "").replace(/^\uFEFF/, "").trim();

        if (!r.ok) {
          throw new Error(`HTTP ${r.status}: ${limpio}`);
        }

        const resp = JSON.parse(limpio);

        if (resp && resp.ok && resp.firmada === true) {
          setEstadoFirma("Ya está firmada", "text-success", "fa-check-circle");
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

    const bloqueSello = document.getElementById("bloqueSello");

    document.getElementById("btnOpcionFirma")?.addEventListener("click", function () {
      limpiarGuiaFirma();
      bloqueSello?.classList.add("d-none");
      const idservicio = document.getElementById("idservicio_firma")?.value || document.getElementById("idservicio")?.value || "";
      const nombre = document.getElementById("param82")?.value.trim() || "";
      const telefono = document.getElementById("param85")?.value.trim() || "";
      const link = document.getElementById("link_firma_entrega")?.value || "";

      if (!idservicio) {
        Swal.fire("Sin servicio", "No hay un servicio cargado para enviar firma.", "warning");
        return;
      }

      if (!validarNombreCompleto()) {
        Swal.fire("Nombre incompleto", "Debe ingresar nombre y apellido de quien entrega.", "warning");
        document.getElementById("param82")?.focus();
        return;
      }

      if (!telefono) {
        Swal.fire("Falta teléfono", "Debe ingresar el WhatsApp", "warning");
        document.getElementById("param85")?.focus();
        return;
      }

      const fd = new FormData();
      fd.append("accion", "enviarLinkFirma");
      fd.append("idservicio", idservicio);
      fd.append("nombre", nombre);
      fd.append("telefono", telefono);
      fd.append("link", link);

      fetch("../controller/EntregarController.php", {
        method: "POST",
        body: fd
      })
      .then(r => r.json())
      .then(resp => {
        if (!resp.ok) {
          Swal.fire("Error", resp.mensaje || "No se pudo enviar", "error");
          return;
        }

        setEstadoFirma("Esperando firma...", "text-warning", "fa-clock");
        Swal.fire("Enviado", "Link enviado por WhatsApp", "success");
      })
      .catch(err => {
        console.error(err);
        Swal.fire("Error", "No se pudo enviar el link de firma.", "error");
      });
    });

    document.getElementById("btnOpcionSello")?.addEventListener("click", () => {
      limpiarGuiaFirma();
      bloqueSello?.classList.remove("d-none");
    });

    document.getElementById("btnVerificarFirma")?.addEventListener("click", function () {
      limpiarGuiaFirma();
      setEstadoFirma("Validando firma...", "text-info", "fa-spinner");
      consultarEstadoFirma();
    });

    document.getElementById("btnGuardarSello")?.addEventListener("click", function () {
      const idservicio = document.getElementById("idservicio_firma")?.value || document.getElementById("idservicio")?.value || "";
      const fileInput = document.getElementById("imagen_sello");
      const archivo = fileInput?.files?.[0];

      if (!idservicio) {
        Swal.fire("Sin servicio", "No hay un servicio cargado para guardar el sello.", "warning");
        return;
      }

      if (!archivo) {
        Swal.fire("Falta imagen", "Debe subir el sello", "warning");
        return;
      }

      const reader = new FileReader();

      reader.onload = function (e) {
        const fd = new FormData();
        fd.append("accion", "guardarSello");
        fd.append("idservicio", idservicio);
        fd.append("firmaBase64", e.target.result);

        fetch("../controller/EntregarController.php", {
          method: "POST",
          body: fd
        })
        .then(r => r.json())
        .then(resp => {
          if (!resp.ok) {
            Swal.fire("Error", resp.mensaje || "No se pudo guardar", "error");
            return;
          }

          setEstadoFirma("Ya está firmada", "text-success", "fa-check-circle");
          Swal.fire("Sello guardado", "El sello quedó registrado correctamente.", "success");
        })
        .catch(err => {
          console.error(err);
          Swal.fire("Error", "No se pudo guardar el sello.", "error");
        });
      };

      reader.readAsDataURL(archivo);
    });

    // ============================ GUARDAR ENTREGA ============================
    function guardarEntregar() {
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
  </script>
</body>
</html>






