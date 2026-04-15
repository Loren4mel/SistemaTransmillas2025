<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <!-- 🔥 ESTO ES OBLIGATORIO PARA RESPONSIVE -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="../../images/Logo Google Nuevo.png">

  <title>Nuevo Envío</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Tu CSS -->
  <link rel="stylesheet" href="../assets/css/RecogidasMovil.css">

</head>

<body>

<div class="container-fluid mt-3 px-2 px-md-4">
  <div class="card shadow rounded">

        <!-- HEADER -->
    <div class="card-header mi-header d-flex justify-content-between align-items-center px-3 py-2">
      <h5 class="mb-0 d-flex align-items-center gap-2 text-white">
        <i class="fas fa-truck"></i> Registro de Recogida
      </h5>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-light btn-icon" data-bs-toggle="modal" data-bs-target="#modalAyuda">
          <i class="fas fa-circle-question text-warning"></i>
        </button>
        <button class="btn btn-light btn-icon" onclick="history.back()">
          <i class="fas fa-arrow-left"></i>
        </button>
      </div>
    </div>

    <div class="card-body">
      <form id="form1" name="form1" method="POST" enctype="multipart/form-data">

        <!-- ================= REMITENTE ================= -->
        <h5 class="text-primary">Remitente</h5>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label>CC / NIT</label>
            <input type="text" id="param1" name="param1" class="form-control">
          </div>

          <div class="col-12 col-md-4 input-con-spinner">
            <label>Teléfono</label>
            <input type="text" id="param2" name="param2" class="form-control">
            <div id="spinner-remitente" class="spinner-busqueda"></div>
          </div>

          <div class="col-12 col-md-4">
            <label>Remitente</label>
            <input type="hidden" id="param61" name="param61">
            <input type="text" id="param6" name="param6" class="form-control mt-1">
          </div>

          <div class="col-12 col-md-4">
            <label>Ciudad</label>
            <select id="param4" name="param4" class="form-select">
              <option value="">Seleccione...</option>
              <?php foreach ($ciudadesR as $ciudad): ?>
                <option value="<?= $ciudad['idciudades'] ?>"><?= $ciudad['ciu_nombre'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label">Dirección (*)</label>
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-2">
                <select id="param5" name="param5" class="form-select" required>
                  <option value="">Tipo vía...</option>
                  <?php foreach ($direcciones as $direccion): ?>
                    <option value="<?= $direccion['dir_nombre'] ?>"><?= $direccion['dir_nombre'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-4 col-md-1" id="wrapDir1R"><input type="text" id="dir1R" name="dir1R" class="form-control" required></div>
              <div class="col-2 col-md-1 text-center" id="sepDirHashR">#</div>
              <div class="col-4 col-md-1" id="wrapDir2R"><input type="text" id="dir2R" name="dir2R" class="form-control" required></div>
              <div class="col-2 col-md-1 text-center" id="sepDirGuionR">-</div>
              <div class="col-4 col-md-1" id="wrapDir3R"><input type="text" id="dir3R" name="dir3R" class="form-control" required></div>

              <div class="col-12 col-md-5" id="wrapComplementoR">
                <label>Lugar</label>
                <select id="param19" name="selectComplemento" class="form-select" required>
                  <option value="">Seleccione...</option>
                  <?php foreach ($lugares as $lugar): ?>
                    <option value="<?= $lugar['lug_nombre'] ?>"><?= $lugar['lug_nombre'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div id="camposComplemento" class="col-12"></div>
              <input type="hidden" name="complemento_detalle_final" id="complementoFinal">
            </div>
          </div>

          <div class="col-12 col-md-4">
            <label>Barrio</label>
            <input type="text" id="param23" name="param23" class="form-control">
          </div>
        </div>

        <hr>

        <!-- ================= DESTINATARIO ================= -->
        <h5 class="text-primary">Destinatario</h5>

        <div class="row g-3">
          <div class="col-12 col-md-4 input-con-spinner">
            <label>Teléfono</label>
            <input type="text" id="param8" name="param8" class="form-control">
            <div id="spinner-destinatario" class="spinner-busqueda"></div>
          </div>

          <div class="col-12 col-md-4">
            <label>Nombre</label>
            <input type="text" id="param9" name="param9" class="form-control">
          </div>

          <div class="col-12 col-md-4">
            <label>Ciudad</label>
            <select id="param11" name="param11" class="form-select">
              <option value="">Seleccione...</option>
              <?php foreach ($ciudades as $ciudad): ?>
                <option value="<?= $ciudad['idciudades'] ?>"><?= $ciudad['ciu_nombre'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-8">
            <label class="form-label">Dirección (*)</label>
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md-2">
                <select id="param10" name="param10" class="form-select" required>
                  <option value="">Tipo vía...</option>
                  <?php foreach ($direcciones as $direccion): ?>
                    <option value="<?= $direccion['dir_nombre'] ?>"><?= $direccion['dir_nombre'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-4 col-md-1"><input type="text" id="dir1D" name="dir1D" class="form-control" required></div>
              <div class="col-2 col-md-1 text-center">#</div>
              <div class="col-4 col-md-1"><input type="text" id="dir2D" name="dir2D" class="form-control" required></div>
              <div class="col-2 col-md-1 text-center">-</div>
              <div class="col-4 col-md-1"><input type="text" id="dir3D" name="dir3D" class="form-control" required></div>

              <div class="col-12 col-md-5">
                <label>Lugar</label>
                <select id="param21" name="param21" class="form-select" required>
                  <option value="">Seleccione...</option>
                  <?php foreach ($lugares as $lugar): ?>
                    <option value="<?= $lugar['lug_nombre'] ?>"><?= $lugar['lug_nombre'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div id="camposComplementoD" class="col-12"></div>
              <input type="hidden" id="complementoFinalD" name="complemento_detalle_finalD">
            </div>
          </div>

          <div class="col-12 col-md-4">
            <label>Barrio</label>
            <input type="text" id="param24" name="param24" class="form-control">
          </div>

        

        <hr>

        <!-- ================= SERVICIO ================= -->
        <h5 class="text-primary">Servicio</h5>

        <div class="row g-3">
          <div class="col-12 col-md-3">
            <label>Tipo paquete (*)</label>
            <select id="param12" name="param12" class="form-select" required>
               <option>Seleccione...</option>
                <option value="Paquete Pequeño">Paquete Peque&ntilde;o</option>
                <option value="Paquete Mediano">Paquete Mediano</option>
                <option value="Paquete Grande">Paquete Grande</option>
            </select>
          </div>

          <div class="col-12 col-md-3">
            <label>Contiene (*)</label>
            <input type="text" id="param13" name="param13" class="form-control" required>
          </div>

          <!-- <div class="col-12 col-md-3"> -->
            <!-- <label># Guía</label> -->
            <input type="hidden" id="param16" name="param16" class="form-control" readonly>
          <!-- </div> -->

          <div class="col-12 col-md-3">
            <label># Piezas (*)</label>
            <input type="number" id="param29" name="param29" class="form-control" min="1" required>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <!-- <div class="col-12 col-md-3"> -->
            <!-- <label>Abono</label> -->
            <input type="hidden" id="param17" name="param17" class="form-control" >
          <!-- </div> -->

          <div class="col-12 col-md-3">
            <label>Seguro</label>
            <input 
              type="number" 
              id="param18" 
              name="param18" 
              class="form-control" 
              value="100000"
              min="100000"
              step="1"
              onblur="validarMinimo(this)"
            >
          </div>

          <div class="col-12 col-md-3">
            <label>Peso KG</label>
            <input type="number" id="param26" name="param26" class="form-control">
          </div>

          <div class="col-12 col-md-3">
            <label>Volumen</label>
            <input type="number" id="param27" name="param27" class="form-control">
          </div>
        </div>

        <div class="row g-3 mt-1">
          <!-- <div class="col-12 col-md-4"> -->
            <!-- <label>¿Servicio con Retorno?</label> -->
            <!-- <select id="param25" name="param25" class="form-select">
            <option value="no">No</option>
            <option value="si">Si</option>
            </select> -->
            <input type="hidden" id="param25" name="param25" class="form-control">


          <!-- </div> -->


          <div class="col-12 col-md-4">
            <label>Tipo Pago (*)</label>
            <select id="param28" name="param28" class="form-select" required>
            <option value="">Seleccione...</option>
            <option value="1">Contado</option>
            <option value="2">Crédito</option>
            <option value="3">Al Cobro</option>
            </select>
          </div>
          <!-- ===== CONTADO ===== -->
            <div id="bloque-contado" class="row g-3 mt-2 d-none">
            <div class="col-12 col-md-6">
                <label>Método de Pago (*)</label>
                <select id="metodo_pago" name="metodo_pago" class="form-select" >
                <option value="">Seleccione...</option>
                <option value="efectivo">Efectivo</option>
                <option value="DV">DAVIVIENDA  AHORROS DAVIPLATA</option>
                <option value="NQ">BANCOLOMBIA CORRIENTE  NEQUI</option>
                </select>
                <div id="visorImagenPago" class="card border-0 shadow-sm mt-3 d-none" style="max-width:420px;">
                  <div class="card-body">
                    <label id="tituloImagenPago" class="form-label fw-bold mb-2">Imagen de pago</label>
                    <img id="imagenPagoPreview" src="" alt="Referencia del método de pago" class="img-fluid rounded border" />
                  </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label>Imagen transacción</label>
                <input type="file" id="imagen_transaccion" name="imagen_transaccion" class="form-control">
            </div>
            </div>

            <!-- ===== CREDITO ===== -->
            <div id="bloque-credito" class="row g-3 mt-2 d-none">
            <div class="col-12">
                <label>Cliente (*)</label>
                <select id="cliente_credito" name="cliente_credito" class="form-select">
                <option value="">Seleccione...</option>
                <!-- aquí cargas tus clientes -->
                </select>
            </div>
            </div>

          <div class="col-12 col-md-4">
            <label>Tipo de Servicio (*)</label>
            <div id="respuesta" class="border rounded p-2"></div>
          </div>

        </div>

        <hr>

        <!-- ================= QUIEN RECIBE ================= -->
        <h5 class="text-primary">Datos quien Entrega</h5>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label>Foto (*)</label>
           <input 
              type="file" 
              id="param91" 
              name="param91" 
              class="form-control" 
              accept="image/*"
              capture="environment"
              required
            >
          </div>

          <div class="col-12 col-md-4">
            <label>Nombre y apellido(*)</label>
            <input type="text" id="param92" name="param92" class="form-control" required>
          </div>

          <div class="col-12 col-md-4">
            <label>WhatsApp (*)</label>
            <input type="text" id="param93" name="param93" class="form-control" required>
          </div>
        </div>

        <hr>

        <!-- ================= VALOR ================= -->
        <h5 class="text-primary">Valor</h5>
        <input type="number" id="param111" name="param111" class="form-control mb-3" value="0">

        <!-- HIDDEN -->
        <input type="hidden" name="param15" value="Envio Oficina">
        <input type="hidden" id="id_param" name="id_param">
        <input type="hidden" id="id_param0" name="id_param0">
        <input type="hidden" id="id_param1" name="id_param1" value="0">
        <input type="hidden" id="id_param2" name="id_param2">
        <input type="hidden" name="id_usuario" value="<?= $_SESSION['usuario_id'] ?>">
        <!-- <input type="hidden" name="variableunica" value="<?= $variableunica ?>"> -->
        <input type="hidden" id="param113" name="param113">
        <input type="hidden" id="valorSinSeguro" name="valorSinSeguro">
        <input type="hidden" id="latitud" name="latitud">
        <input type="hidden" id="longitud" name="longitud">
        <input type="hidden" id="precision_gps" name="precision_gps">

        <!-- BOTON -->
        <div class="text-end mt-4">
          <button type="submit" class="btn btn-success btn-lg px-4">
            <i class="fas fa-save"></i> Guardar
          </button>
        </div>

      </form>
    </div>
  </div>
</div>


<!-- MODAL ACCIONES POST-GUARDADO -->
<div class="modal fade" id="modalFirma" tabindex="-1" aria-labelledby="modalFirmaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="modalFirmaLabel">
          <i class="fas fa-circle-check me-2"></i>Acciones del servicio
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="idservicio_firma">
        <input type="hidden" id="link">

        <div class="modal-firma-intro">
          El servicio fue guardado correctamente. Selecciona como deseas continuar.
        </div>


        <!-- BOTONES DE OPCIÓN -->
        <div class="d-grid gap-2 mb-3 modal-firma-opciones">
          <button class="btn btn-outline-primary" id="btnOpcionFirma" type="button">
            📲 Enviar link de firma
          </button>
          <button class="btn btn-outline-secondary" id="btnOpcionSello" type="button">
            🖋️ Subir sello
          </button>
        </div>

        <!-- ===== BLOQUE FIRMA ===== -->
        <div id="bloqueFirma" class="d-none modal-firma-panel">
          <hr>
          <div class="mb-2">
            <label>Nombre receptor</label>
            <input type="text" id="nombre_receptor" class="form-control">
          </div>

          <div class="mb-2">
            <label>Teléfono WhatsApp</label>
            <input type="text" id="telefono_receptor" class="form-control">
          </div>

          <button class="btn btn-success w-100" id="btnEnviarFirma" type="button">
            Reenviar link
          </button>
        </div>

        <!-- ===== BLOQUE SELLO ===== -->
        <div id="bloqueSello" class="d-none modal-firma-panel">
          <hr>
          <input type="hidden" id="idservicio_sello">

          <div class="mb-2">
            <label>Subir sello</label>
            <input type="file" id="imagen_sello" class="form-control" accept="image/*">
          </div>

          <button class="btn btn-dark w-100" id="btnGuardarSello" type="button">
            Guardar sello
          </button>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" id="btnFinalizar" type="button">
          Finalizar
        </button>
      </div>

    </div>
  </div>
</div>

<!-- MODAL AYUDA -->
<div class="modal fade" id="modalAyuda" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-warning">
        <h5 class="modal-title">
          <i class="fas fa-circle-question"></i> ¿Cómo registrar una recogida?
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">

        <div class="ratio ratio-16x9">
          <iframe 
            id="videoAyuda"
            src="https://www.youtube.com/embed/ID_DEL_VIDEO"
            title="Video de ayuda"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
          </iframe>
        </div>

        <p class="mt-3 text-muted">
          Mira este video para aprender cómo llenar el formulario correctamente.
        </p>

      </div>

    </div>
  </div>
</div>



<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="../assets/js/RecogidasMovil.js"></script>

</body>
</html>
