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
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .container-firma {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    height: 100vh;
    padding: 2rem 1rem;
  }

  h3 {
    font-weight: 600;
    color: #2c3e50;
    text-align: center;
    margin-bottom: 1rem;
  }

  p {
    text-align: center;
    color: #6c757d;
  }

  #signature-pad {
    border: 2px dashed #6c757d;
    border-radius: 10px;
    width: 100%;
    max-width: 600px;
    height: 260px;
    background-color: #fff;
    cursor: crosshair;
    touch-action: none;
    object-fit: contain;
  }

  .btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
  }

  .btn-primary:hover {
    background: linear-gradient(135deg, #0056b3, #004094);
  }

  .buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
    max-width: 400px;
    margin-top: 1.5rem;
  }

  @media (max-width: 576px) {
    #signature-pad {
      height: 200px;
    }
  }
</style>
</head>

<?php 
$tipoPago = $_GET['tipo_pago'] ?? "";
$textoPago = "";
$colorPago = "";

if (strtolower($tipoPago) == "al cobro" or strtolower($tipoPago) == "3") {
    $textoPago = "Esta guía NO está paga";
    $colorPago = "#e74c3c";
} elseif (strtolower($tipoPago) == "credito" or strtolower($tipoPago) == "2") {
    $textoPago = "Esta guía NO está paga";
    $colorPago = "#e74c3c";
} elseif (strtolower($tipoPago) == "contado" or strtolower($tipoPago) == "1") {
    $textoPago = "Esta guía SÍ está paga";
    $colorPago = "#2ecc71";
}
?>

<body>

<div class="container-firma">
  <div>
    <h3>✍️ Firma o 📸 Sello</h3>
    <p>Firme dentro del recuadro o suba una imagen de su sello.</p>
  </div>

  <canvas id="signature-pad"></canvas>

  <input type="hidden" id="idServicio" value="<?php echo $_GET['para']; ?>">
  <input type="hidden" id="accion" value="<?php echo $_GET['accion']; ?>">

  <div class="buttons">
    <button id="clear" type="button" class="btn btn-outline-secondary w-100">🧽 Borrar</button>
    <button id="uploadSeal" type="button" class="btn btn-warning w-100">📸 Subir Sello</button>
    <input type="file" id="sealInput" accept="image/*" style="display:none;">
    <button id="guardarFirma" type="button" class="btn btn-success w-100">💾 Guardar Firma o Sello</button>
  </div>
</div>

<script>
// === Inicialización del canvas ===
const canvas = document.getElementById('signature-pad');
const ctx = canvas.getContext('2d');
let drawing = false;
let selloBase64 = null; // Si el usuario sube un sello

// === FUNCIÓN PARA DIBUJAR EL TEXTO DE PAGO “QUEMADO” ===
function dibujarTextoPago() {
  ctx.save();
  ctx.globalAlpha = 0.9; // leve transparencia opcional
  ctx.font = "bold 18px 'Segoe UI'";
  ctx.fillStyle = "<?php echo $colorPago; ?>";
  ctx.fillText("<?php echo addslashes($textoPago); ?>", 10, 25);
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
  selloBase64 = null; // Si empieza a dibujar, se borra sello previo
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
  dibujarTextoPago(); // 🔥 Redibuja el texto después de limpiar
  selloBase64 = null;
});

// === Subir sello ===
document.getElementById('uploadSeal').addEventListener('click', () => {
  document.getElementById('sealInput').click();
});

document.getElementById('sealInput').addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = ev => {
    const img = new Image();
    img.onload = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      dibujarTextoPago(); // 🔥 mantener texto al subir sello

      const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
      const x = (canvas.width / scale - img.width) / 2;
      const y = (canvas.height / scale - img.height) / 2;

      ctx.save();
      ctx.scale(scale, scale);
      ctx.drawImage(img, x, y);
      ctx.restore();

      selloBase64 = ev.target.result;
    };
    img.src = ev.target.result;
  };
  reader.readAsDataURL(file);
});

// === Guardar firma o sello ===
document.getElementById('guardarFirma').addEventListener('click', async () => {
  const idServicio = document.getElementById('idServicio').value;
  const accion = document.getElementById('accion').value;

  const dataURL = selloBase64 || canvas.toDataURL('image/png');

  if (!dataURL || dataURL === 'data:,') {
    alert('⚠️ Debe firmar o subir un sello antes de guardar.');
    return;
  }

  const formData = new FormData();
  formData.append('accion', accion);
  formData.append('idServicio', idServicio);
  formData.append('firma', dataURL);
  formData.append('origenFirma', selloBase64 ? 'sello' : 'firma');

  try {
    const res = await fetch('/nueva_plataforma/controller/recogerEntregarController.php', { 
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

    // Leer parámetros GET
    const params = new URLSearchParams(window.location.search);
    const destino = params.get("de"); // ejemplo: ?destino=algo

    // Si existe un parámetro GET -> redirigir
    if (destino) {
        window.location.href = "../../../inicio.php";
        return; // detener ejecución
    }

    // Si NO existe -> ejecutar tu código original
    alert('✅ Firma o sello guardado correctamente.');

    if (window.parent !== window) {
        window.parent.postMessage({ tipo: 'firma_guardada' }, '*');
    }

    setTimeout(() => window.close(), 800);
    }
    else {
      alert('❌ Error al guardar la firma o sello.');
    }
  } catch (err) {
    console.error(err);
    alert('⚠️ Error de conexión al guardar.');
  }
});
</script>

</body>
</html>
