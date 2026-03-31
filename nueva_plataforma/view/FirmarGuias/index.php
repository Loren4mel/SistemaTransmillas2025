<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Firma de Liquidación</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    background-color: #f4f6f9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .header-modulo {
    background-color: #0b4a8b;
    color: white;
    padding: 14px 20px;
    font-size: 20px;
    font-weight: 600;
    text-align: center;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
  }

  .contenedor-principal {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 15px;
  }

  .card-firma {
    background: #ffffff;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 25px 25px 30px 25px;
    border-top: 4px solid #0b4a8b;
  }

  .titulo-seccion {
    font-size: 16px;
    font-weight: 600;
    color: #0b4a8b;
    margin-bottom: 5px;
  }

  .descripcion {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 20px;
  }

  .aviso-pago {
    border-radius: 10px;
    border: 2px solid transparent;
    padding: 16px 18px;
    margin-bottom: 20px;
  }

  .aviso-pago strong {
    display: block;
    font-size: 16px;
    margin-bottom: 6px;
  }

  .aviso-pago p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
  }

  .aviso-pago.success {
    background-color: #eaf7ef;
    border-color: #198754;
    color: #146c43;
  }

  .aviso-pago.warning {
    background-color: #fff4e5;
    border-color: #fd7e14;
    color: #9a4d00;
  }

  .aviso-pago.info {
    background-color: #e7f1ff;
    border-color: #0b4a8b;
    color: #0b4a8b;
  }

  #signature-pad {
    border: 2px dashed #bfc9d4;
    border-radius: 6px;
    width: 100%;
    height: 250px;
    background-color: #ffffff;
    cursor: crosshair;
    touch-action: none;
  }

  .acciones {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  }

  .btn-sistema {
    background-color: #0b4a8b;
    border: none;
    color: white;
    font-weight: 500;
    padding: 8px 18px;
  }

  .btn-sistema:hover {
    background-color: #083766;
  }

  .btn-secundario {
    background-color: #e9ecef;
    border: none;
    color: #495057;
    font-weight: 500;
    padding: 8px 18px;
  }

  .btn-secundario:hover {
    background-color: #dde2e6;
  }

  @media (max-width: 576px) {
    .acciones {
      flex-direction: column;
      align-items: stretch;
    }
  }


</style>
</head>



<body>

<input type="hidden" id="idServicio" value="<?= htmlspecialchars($idServicio) ?>">
<input type="hidden" id="accion" value="<?= htmlspecialchars($accionFirma) ?>">
<input type="hidden" id="sefirma" value="<?= htmlspecialchars($puedeFirmar) ?>">

<div class="header-modulo">
  Confirmación de Servicio
</div>

<div class="contenedor-principal">

  <div class="card-firma">

<?php if (!$puedeFirmar): ?>
    <div class="text-center py-4">
        <div style="font-size:18px; font-weight:600; color:#dc3545;">
            Este servicio ya fue firmado
        </div>
        <div class="text-muted mt-2">
            La firma del cliente ya se encuentra registrada y no puede modificarse.
        </div>

        <button onclick="window.close()" class="btn btn-sistema mt-4">
            Cerrar
        </button>
    </div>
<?php else: ?>
    <!-- AQUÍ VA TODO EL CANVAS Y BOTONES -->
    <?php if (!empty($mensajeAvisoPago)): ?>
    <div class="aviso-pago <?= htmlspecialchars($claseAvisoPago ?? 'info') ?>">
      <strong><?= htmlspecialchars($tituloAvisoPago ?? 'Informacion del pago') ?></strong>
      <p><?= htmlspecialchars($mensajeAvisoPago) ?></p>
    </div>
    <?php endif; ?>

    <div class="titulo-seccion">Firma del cliente</div>
    <div class="descripcion">
      Por favor, firme dentro del recuadro para finalizar y poder generar la guia soporte que llegara a su whatsapp.
    </div>

    <canvas id="signature-pad"></canvas>

    <div class="acciones">
      <button id="clear" type="button" class="btn btn-secundario">Limpiar firma</button>
      <button id="guardarFirma" type="button" class="btn btn-sistema">Guardar firma</button>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

  const puedeFirmar = <?= $puedeFirmar ? 'true' : 'false' ?>;
  const tituloAvisoPago = <?= json_encode($tituloAvisoPago ?? '') ?>;
  const mensajeAvisoPago = <?= json_encode($mensajeAvisoPago ?? '') ?>;
  const claseAvisoPago = <?= json_encode($claseAvisoPago ?? 'info') ?>;
// === Inicialización del canvas ===
const canvas = document.getElementById('signature-pad');
const ctx = canvas.getContext('2d');
let drawing = false;
// let selloBase64 = null; // Si el usuario sube un sello
let firmaRealizada = false; // 👈 NUEVA

if (puedeFirmar && mensajeAvisoPago) {
  Swal.fire({
    icon: claseAvisoPago === 'success' ? 'success' : (claseAvisoPago === 'warning' ? 'warning' : 'info'),
    title: tituloAvisoPago,
    text: mensajeAvisoPago,
    confirmButtonText: 'Entendido',
    allowOutsideClick: false
  });
}

// === FUNCIÓN PARA DIBUJAR EL TEXTO DE PAGO “QUEMADO” ===
function dibujarTextoPago() {
  ctx.save();
  ctx.globalAlpha = 0.9; // leve transparencia opcional
  ctx.font = "bold 18px 'Segoe UI'";
    ctx.fillStyle = "<?= $colorPago ?>";
    ctx.fillText("<?= addslashes($textoPago) ?>", 10, 25);
  ctx.restore();
}

// === FUNCIÓN DE REDIMENSIONADO DEL CANVAS ===
function resizeCanvas() {
  const ratio = window.devicePixelRatio || 1;
  const rect = canvas.getBoundingClientRect();
  canvas.width = rect.width * ratio;
  canvas.height = rect.height * ratio;
  ctx.scale(ratio, ratio);
  ctx.lineWidth = 2;
  ctx.lineCap = 'round';
  ctx.strokeStyle = '#000';
  dibujarTextoPago(); // 🔥 Dibuja el texto al inicializar o redimensionar
}
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

function getPos(e) {
  const rect = canvas.getBoundingClientRect();
  return e.touches
    ? { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top }
    : { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

// === Eventos de dibujo ===
canvas.addEventListener('mousedown', e => {
  drawing = true;
  firmaRealizada = true; // 👈 MARCAR FIRMA
  selloBase64 = null;
  const pos = getPos(e);
  ctx.beginPath();
  ctx.moveTo(pos.x, pos.y);
});


canvas.addEventListener('mousemove', e => {
  if (!drawing) return;
  const pos = getPos(e);
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();
});
canvas.addEventListener('mouseup', () => drawing = false);
canvas.addEventListener('mouseout', () => drawing = false);

canvas.addEventListener('touchstart', e => {
  e.preventDefault();
  drawing = true;
  firmaRealizada = true; // 👈 MARCAR FIRMA
  selloBase64 = null;
  const pos = getPos(e);
  ctx.beginPath();
  ctx.moveTo(pos.x, pos.y);
});
canvas.addEventListener('touchmove', e => {
  e.preventDefault();
  if (!drawing) return;
  const pos = getPos(e);
  ctx.lineTo(pos.x, pos.y);
  ctx.stroke();
});
canvas.addEventListener('touchend', () => drawing = false);

// === Botón limpiar ===
document.getElementById('clear').addEventListener('click', () => {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  dibujarTextoPago();
  selloBase64 = null;
  firmaRealizada = false; // 👈 RESETEAR
});

// // === Subir sello ===
// document.getElementById('uploadSeal').addEventListener('click', () => {
//   document.getElementById('sealInput').click();
// });

// document.getElementById('sealInput').addEventListener('change', e => {
//   const file = e.target.files[0];
//   if (!file) return;

//   const reader = new FileReader();
//   reader.onload = ev => {
//     const img = new Image();
//     img.onload = () => {
//       ctx.clearRect(0, 0, canvas.width, canvas.height);
//       dibujarTextoPago(); // 🔥 mantener texto al subir sello

//       const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
//       const x = (canvas.width / scale - img.width) / 2;
//       const y = (canvas.height / scale - img.height) / 2;

//       ctx.save();
//       ctx.scale(scale, scale);
//       ctx.drawImage(img, x, y);
//       ctx.restore();

//       selloBase64 = ev.target.result;
//       firmaRealizada = true; // 👈 SELLO TAMBIÉN ES FIRMA
//     };
//     img.src = ev.target.result;
//   };
//   reader.readAsDataURL(file);
// });

// === Guardar firma o sello ===
document.getElementById('guardarFirma').addEventListener('click', async () => {

  if (!puedeFirmar) {
    Swal.fire({
      icon: 'info',
      title: 'Firma ya registrada',
      text: 'Este servicio ya fue firmado previamente.'
    });
    return;
  }
  const idServicio = document.getElementById('idServicio').value;
  const accion = document.getElementById('accion').value;
    if (!firmaRealizada ) {
    Swal.fire({
      icon: 'warning',
      title: 'Firma requerida',
      text: 'Debe firmar o subir un sello antes de guardar.'
    });
    return;
  }

  const dataURL = canvas.toDataURL('image/png');



  const formData = new FormData();
  formData.append('accion', accion);
  formData.append('idServicio', idServicio);
  formData.append('firma', dataURL);

  try {
    const res = await fetch('/nueva_plataforma/controller/FirmarGuiaController.php', { 
      method: 'POST',
      body: formData 
    });

    const data = await res.json();

    // if (data.success) {
    //   alert('✅ Firma o sello guardado correctamente.');
    //   if (window.parent !== window) {
    //     window.parent.postMessage({ tipo: 'firma_guardada' }, '*');
    //   }
    //   setTimeout(() => window.close(), 800);
    // }
    if (data.success) {


      const params = new URLSearchParams(window.location.search);
      const destino = params.get("de");

      // Si hay redirección especial, se respeta
      if (destino) {
          window.location.href = "../../../inicio.php";
          return;
      }

      // Avisar a la ventana padre si existe
      if (window.parent !== window) {
          window.parent.postMessage({ tipo: 'firma_guardada' }, '*');
      }

      // SweetAlert de éxito
      Swal.fire({
          icon: 'success',
          title: 'Firma guardada',
          text: 'La firma se guardó correctamente.',
          confirmButtonText: 'Recargar',
          confirmButtonColor: '#198754',
          allowOutsideClick: false,
          allowEscapeKey: false
      }).then(() => {

          // Añade un estado al historial
          history.pushState(null, null, location.href);

          // Bloquea botón atrás
          window.onpopstate = function () {
              history.go(1);
          };

          // Recarga la página
          location.reload();

      });
    }
    else {
      Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo guardar la firma .'
      });
    }
  } catch (err) {
    console.error(err);
        Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo conectar con el servidor.'
    });
  }
});
</script>

</body>
</html>
