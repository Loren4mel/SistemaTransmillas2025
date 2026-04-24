<?php
// view/Recoger/index.php
// Este archivo se incluye desde RecogerController.php?accion=vista&idServicio=...
$sede    = $_GET['sede']  ?? '';
$acceso  = $_GET['acceso'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$nombre = $_GET['nombre'] ?? '';
$idServicio = isset($_GET['idServicio']) ? (int)$_GET['idServicio'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Recoger Servicio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <style>
    .mi-header { background:#00458D; color:white; }
    .section-title { background:#01468C; color:#fff; padding:.5rem .75rem; font-weight:600; margin-bottom:1rem; border-radius:.25rem; }
    .readonly-input { background:#f2f2f2; }
    .error { color:red; font-size:.85rem; display:none; }
  </style>
</head>
<body>
<div class="container-fluid mt-4">
  <div class="card shadow p-3 mb-4 bg-body rounded">
    <div class="card-header text-center mi-header">
      <h3 class="mb-0">Recoger Servicio</h3>
    </div>

    <div class="card-body">

      <!-- SELECTOR DE ACCIÓN -->
      <div class="row mb-4">
        <div class="col-md-4">
          <label class="form-label fw-bold">Seleccione acción</label>
          <select id="tipoAccion" class="form-select">
            <option value="">Seleccione...</option>
            <option value="recogido">RECOGIDO</option>
            <option value="norecogido">NO RECOGIDO</option>
          </select>
        </div>
      </div>

      <!-- ====================== FORMULARIO NO RECOGIDO ========================= -->
      <div id="formNoRecogido" style="display:none;">
        <div class="section-title">No Recogido</div>

        <div class="row mb-3">
          <div class="col-md-8">
            <label class="form-label fw-bold">Motivo *</label>
            <input type="text" id="motivo_no_recogido" class="form-control" placeholder="Escriba el motivo" />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-8">
            <label class="form-label fw-bold">Foto evidencia *</label>
            <input type="file" id="foto_evidencia" class="form-control" accept="image/*" />
          </div>
        </div>

        <button type="button" class="btn btn-danger" onclick="guardarNoRecogido()">
          <i class="fa fa-times"></i> Guardar No Recogido
        </button>
        <hr>
      </div>

      <!-- ====================== FORMULARIO RECOGIDO ========================= -->
      <form id="formRecogido" enctype="multipart/form-data" style="display:none;">
        <input type="hidden" name="accion" value="guardarRecogido" />
        <input type="hidden" name="idservicio" id="idservicio" />
        <input type="hidden" name="precioinicialkilos" id="precioinicialkilos" />
        
        <!-- param27 (planilla) oculto: se llena con ser_consecutivo -->
        <input type="hidden" name="param27" id="param27" />
        <input type="hidden" name="param13" id="param13" />
        <input type="hidden" name="param17" id="param17"  value="0"/>
        <input type="hidden" name="param18" id="param18"  />
        <input type="hidden" name="param11" id="param11"  />
        <input type="hidden" name="param9" id="param9"  />
        <input type="hidden" name="param53" id="param53"  />
        <input type="hidden" id="rel_nom_credito" name="rel_nom_credito">
        <input type="hidden" name="param34" id="param34"  />
        <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>">
        <input type="hidden" name="sede" value="<?php echo htmlspecialchars($sede); ?>">
        <input type="hidden" name="nombre" id="nombre"  value="<?php echo htmlspecialchars($nombre); ?>">
        <input type="hidden" name="acceso" value="<?php echo htmlspecialchars($acceso); ?>">

        <input type="hidden" id="latitud" name="latitud">
        <input type="hidden" id="longitud" name="longitud">
        <input type="hidden" id="precision_gps" name="precision_gps">

        




        

        <div class="section-title">Datos del servicio</div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Fecha Recogida</label>
            <input type="date" id="fecha_recogida" name="param28" class="form-control"
                   value="<?php echo date('Y-m-d'); ?>" />
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Servicio(*)</label>
            <input type="text" id="ser_prioridad" class="form-control readonly-input" readonly />
            <!-- Texto servicio para backend -->
            <input type="hidden" id="param15" name="param15" />
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Número de piezas</label>
            <input type="number" id="ser_piezas" name="param2" class="form-control" min="1" required />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">¿Verificado? (*)</label>
            <select id="param19" name="param19" class="form-select" required>
                <option value="">Seleccione...</option>
                <option value="0">No</option>
                <option value="1">Si</option>

            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Tipo(*)</label>
            <!-- param21: se llena con SELECT desde tabla tipo -->
            <select id="param21" name="param21" class="form-select" required>
              <option value="">Cargando tipos...</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Dice contener</label>
            <input type="text" id="ser_paquetedescripcion" name="param3" class="form-control" />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Valor Préstamo</label>
            <input type="text" id="ser_valorprestamo" name="param16"
                   class="form-control readonly-input" readonly />
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Seguro(*)</label>
            <input type="text" id="ser_valorseguro" name="param6" class="form-control" required/>
          </div>

        <div class="col-md-4">
            <label class="form-label fw-bold">Hora Recogida</label>
            <input type="time" id="param7" name="param7" class="form-control" />
        </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Devolver Recibido(*)</label>
                <select id="ser_devolverreci" name="param29" class="form-select">
                <option value="">Seleccione...</option>
                <option value="0">No</option>
                <option value="1">Si</option>

                </select>
            </div>

          <div class="col-md-4">
            <label class="form-label fw-bold"># Guía</label>
            <input type="text" id="ser_guiare" name="param25" class="form-control readonly-input" readonly />
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Estado Paquete(*)</label>
            <!-- param26: estado paquete (editable) -->
            <input type="text" id="param26" name="param26" class="form-control" required />
            <!-- ser_estado queda solo interno en hidden si lo necesitas -->
            <input type="hidden" id="ser_estado" />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Abono</label>
            <input type="text" id="ser_valorabono" name="param54"
                   class="form-control readonly-input" readonly />
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Peso</label>
            <input type="number" id="ser_peso" name="param10" class="form-control" step="0.1" />
          </div>

          <div class="col-md-4">
            <label class="form-label fw-bold">Volumen</label>
            <input type="number" id="ser_volumen" name="param20" class="form-control" step="0.1" />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">VALOR TOTAL</label>
            <input type="text" id="param112" name="param112"
                   class="form-control readonly-input" readonly />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Tipo de Pago(*)</label>
            <!-- param8, con comportamiento especial para Crédito (2) -->
            <select id="param8" name="param8" class="form-select" required>
              <option value="">Seleccione...</option>
              <option value="1">Contado</option>
              <option value="2">Crédito</option>
              <option value="3">Al Cobro</option>
              <!-- <option value="4">Pendiente x Cobrar</option> -->
            </select>
          </div>
        </div>

        <!-- =================== BLOQUE "DE CONTADO" (solo si param8 = 1) =================== -->
        <div id="bloqueContado" style="display:none;">
          <div class="section-title mt-4">De Contado</div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Método de Pago</label>
              <!-- param30: idtipospagos|cuenta|nombre -->
              <select id="param30" name="param30" class="form-select">
                <option value="">Cargando métodos...</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Imagen transacción</label>
              <!-- param40: imagen de transacción -->
              <small class="text-muted d-block mb-3">La imagen cambia automáticamente según el método de pago seleccionado.</small>
              <div id="visorImagenPago" class="card border-0 shadow-sm mb-3" style="display:none; max-width:420px;">
                <div class="card-body">
                  <label id="tituloImagenPago" class="form-label fw-bold mb-2">Imagen de pago</label>
                  <img id="imagenPagoPreview" src="" alt="Imagen del medio de pago" class="img-fluid rounded border" />
                </div>
              </div>
              <input type="file" id="param40" name="param40" class="form-control" accept="image/*" />
            </div>
          </div>
        </div>
        
        <!-- =================== BLOQUE "DE CREDITO" (solo si param8 = 1) =================== -->
        <div id="bloqueCredito" style="display:none;">
          <div class="section-title mt-4">Credito</div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Credito</label>
              <!-- param30: idtipospagos|cuenta|nombre -->
              <select id="credito" name="credito" class="form-select">
                <option value="">Cargando creditos...</option>
              </select>
            </div>

          </div>
        </div>

        <div class="section-title mt-4">Datos quien entrega</div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Foto (*)</label>
            <input type="file" id="param87" name="param87" accept="image/*"
                   capture="environment" class="form-control" required />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Nombre completo</label>
            <input type="text" id="param82" name="param82" class="form-control" required/>
            <p id="errorNombre" class="error">Debe ingresar nombre y apellido.</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Teléfono</label>
            <input type="text" id="param85" name="param85" class="form-control" value="+57" required/>
          </div>
        </div>

        

        <!-- BOTONES FIRMA / SELLO -->
          <div class="row mt-4">
            <div class="col-md-6 mb-2 mb-md-0">
              <button type="button"
                      id="btnEnviarFirma"
                      class="btn btn-success w-100 py-2 fw-bold">
                <i class="fas fa-signature me-2"></i> Enviar firma
              </button>
            </div>
            <div class="col-md-6">
              <button type="button"
                      id="btnMostrarSello"
                      class="btn btn-outline-success w-100 py-2 fw-bold">
                <i class="fas fa-stamp me-2"></i> Sello
              </button>
            </div>
            <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
              <small id="estadoFirma" class="text-warning">
                <i class="fas fa-clock me-1"></i> Esperando firma...
              </small>
              <button type="button"
                      id="btnVerificarFirma"
                      class="btn btn-sm btn-outline-primary">
                <i class="fas fa-rotate-right me-1"></i> Verificar
              </button>
            </div>
          </div>

          <div id="bloqueSello" class="row mt-3" style="display:none;">
            <div class="col-md-6">
              <label class="form-label label-strong" for="img_sello">Imagen del sello</label>
              <input type="file" id="img_sello" name="img_sello" class="form-control" accept="image/*">
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <button type="button" id="btnSubirSello" class="btn btn-success w-100">
                <i class="bi bi-upload me-2"></i> Subir sello
              </button>
            </div>
          </div>

        <!-- <div class="section-title mt-4">Firma Recogida</div>
        <div style="width:100%;height:60vh;">
          <iframe id="iframeFirma" src="" style="width:100%;height:100%;border:0;"></iframe>
        </div> -->


        <button type="button" class="btn btn-success mt-4" onclick="enviarFormulario()">
          <i class="fa fa-save"></i> Guardar
        </button>
      </form>

    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/Recoger.js"></script>
<script>

  
    const ID_SERVICIO = <?= (int)$idServicio ?>;

    document.addEventListener('DOMContentLoaded', function () {
      const btnMostrarSello = document.getElementById('btnMostrarSello');
      const bloqueSello = document.getElementById('bloqueSello');
      const btnSubirSello = document.getElementById('btnSubirSello');
      const imgSello = document.getElementById('img_sello');

      if (btnMostrarSello && bloqueSello) {
        btnMostrarSello.addEventListener('click', function () {
          bloqueSello.style.display = (bloqueSello.style.display === 'none' || bloqueSello.style.display === '')
            ? 'flex'
            : 'none';

          if (bloqueSello.style.display === 'flex' && typeof window.llenarDatosQuienEntregaDesdeRemitente === 'function') {
            window.llenarDatosQuienEntregaDesdeRemitente();
          }
        });
      }

      // if (btnSubirSello && imgSello) {
      //   btnSubirSello.addEventListener('click', function () {
      //     if (!imgSello.files || imgSello.files.length === 0) {
      //       Swal.fire('Atención', 'Selecciona una imagen de sello primero.', 'warning');
      //       return;
      //     }
      //     Swal.fire('Listo', 'Imagen de sello seleccionada correctamente.', 'success');
      //   });
      // }
    });


    //Guardar sello
document.getElementById("btnSubirSello").addEventListener("click", function () {
  const idservicio = ID_SERVICIO;
  const fileInput = document.getElementById("img_sello");
  const archivo = fileInput.files[0];

  if (!archivo) {
    Swal.fire("Falta imagen", "Debe subir el sello", "warning");
    return;
  }

  if (!archivo.type || !archivo.type.startsWith("image/")) {
    Swal.fire("Archivo inválido", "El sello debe ser una imagen.", "warning");
    return;
  }

  if (archivo.size > 25 * 1024 * 1024) {
    Swal.fire("Imagen muy pesada", "El sello no debe superar 25 MB.", "warning");
    return;
  }

  Swal.fire({
    title: "Guardando sello...",
    text: "Por favor espera.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  comprimirImagenSelloRecogida(archivo)
  .then(function (base64) {
    const fd = new FormData();
    fd.append("accion", "guardarSello");
    fd.append("idservicio", idservicio);
    fd.append("firmaBase64", base64);

    fetch("../controller/RecogerController.php", {
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
      setEstadoFirma("Ya está firmada", "text-success", "fa-check-circle");
    });
  })
  .catch(function (err) {
    console.error(err);
    Swal.fire("Error", "No se pudo preparar la imagen del sello.", "error");
  });
});

function comprimirImagenSelloRecogida(archivo) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = function (e) {
      const img = new Image();

      img.onload = function () {
        const maxDimension = 1600;
        const ratio = Math.min(maxDimension / img.width, maxDimension / img.height, 1);
        const ancho = Math.max(1, Math.round(img.width * ratio));
        const alto = Math.max(1, Math.round(img.height * ratio));
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        canvas.width = ancho;
        canvas.height = alto;
        ctx.drawImage(img, 0, 0, ancho, alto);

        resolve(canvas.toDataURL("image/jpeg", 0.82));
      };

      img.onerror = reject;
      img.src = e.target.result;
    };

    reader.onerror = reject;
    reader.readAsDataURL(archivo);
  });
}

</script>
</body>
</html>


