<?php
// Seguridad básica similar a otros módulos (comentado como lo dejaste)
// if (!isset($_POST['sede']) || !isset($_POST['acceso']) || !isset($_POST['usuario'])) {
//     echo "<script>
//             alert('No tiene acceso a esta página');
//             window.close();
//           </script>";
//     exit;
// }

// require("../../login_autentica.php");
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


// ID del servicio llega por GET como idServicio
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
  </style>
</head>
<body>
  <!-- Barra superior (comentada como la dejaste) -->
  <!-- <nav class="navbar navbar-expand-lg topbar" style="background:#00458D;color:white;">
    <div class="container-fluid">
      <button class="btn btn-light" onclick="history.back()">Volver</button>
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

          <!-- ========== SECCIÓN: DATOS QUIEN ENTREGA ========== -->
          <div class="section-title mt-4">Datos de quien entrega</div>

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
            <div class="mt-2">
              <small id="estadoFirma" class="text-warning">
                <i class="fas fa-clock me-1"></i> Esperando firma...
              </small>
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
          <!-- ========== IFRAME PARA FIRMA ========== -->
          <!-- <div class="section-title mt-4">Firma de entrega</div>

          <div style="width: 100%; height: 60vh;">
            <iframe 
              id="iframeFirma"
              src=""
              style="width: 100%; height: 100%; border: 0;"
              allowfullscreen
              loading="lazy">
            </iframe>
          </div> -->

          <!-- ========== CAMPOS OCULTOS QUE TENÍAS EN TU CÓDIGO ========== -->
          <input type="hidden" name="param8" id="param8"  />
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

          <input type="hidden" id="latitud" name="latitud">
          <input type="hidden" id="longitud" name="longitud">
          <input type="hidden" id="precision_gps" name="precision_gps">



          <!-- BOTÓN GUARDAR -->
          <div class="mt-4">
            <button type="button" id="btnGuardar" class="btn btn-success" onclick="enviarFormulario()">
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
  <script src="../assets/js/Entregar.js"></script>

  <script>
    const ID_SERVICIO = <?= (int)$idservicio ?>;

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

  const reader = new FileReader();

  reader.onload = function (e) {
    const base64 = e.target.result;

    const fd = new FormData();
    fd.append("accion", "guardarSello");
    fd.append("idservicio", idservicio);
    fd.append("firmaBase64", base64);

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

      Swal.fire("Sello guardado", "Proceso finalizado", "success");
      finalizarProceso();
    });
  };

  reader.readAsDataURL(archivo);
});

  </script>
</body>
</html>
