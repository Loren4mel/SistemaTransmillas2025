<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Firma de Pendiente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    html, body {
      height: 100%;
      margin: 0;
      background: #eef3f8;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .layout-firma {
      display: grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(340px, 420px);
      gap: 18px;
      min-height: 100vh;
      padding: 18px;
    }
    .panel {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 14px 40px rgba(15, 23, 42, 0.12);
      overflow: hidden;
    }
    .panel-pdf {
      display: flex;
      flex-direction: column;
      min-height: calc(100vh - 36px);
    }
    .panel-firma {
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .encabezado {
      padding: 18px 22px;
      border-bottom: 1px solid #e5e7eb;
    }
    .encabezado h1 {
      font-size: 22px;
      margin: 0 0 6px;
      color: #0f172a;
    }
    .encabezado p,
    .texto-ayuda {
      margin: 0;
      color: #64748b;
      font-size: 14px;
    }
    .visor-pdf {
      flex: 1;
      min-height: 0;
      background: #dfe7ef;
    }
    .visor-pdf iframe {
      width: 100%;
      height: 100%;
      border: 0;
    }
    .badge-estado {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      background: #fff7ed;
      color: #9a3412;
    }
    .badge-estado.firmado {
      background: #ecfdf3;
      color: #166534;
    }
    #signature-pad {
      width: 100%;
      height: 240px;
      border: 2px dashed #94a3b8;
      border-radius: 16px;
      background: #fff;
      cursor: crosshair;
      touch-action: none;
    }
    .acciones {
      display: grid;
      gap: 10px;
    }
    @media (max-width: 991px) {
      .layout-firma {
        grid-template-columns: 1fr;
      }
      .panel-pdf {
        min-height: 60vh;
      }
    }
  </style>
</head>
<body>
  <div class="layout-firma">
    <section class="panel panel-pdf">
      <div class="encabezado">
        <h1><?= htmlspecialchars($pendiente['pen_titulo'] ?? 'Documento pendiente') ?></h1>
        <p><?= htmlspecialchars($pendiente['pen_descripcion'] ?? 'Revisa el PDF y firma en el panel lateral.') ?></p>
      </div>
      <div class="visor-pdf">
        <iframe src="<?= htmlspecialchars($pendiente['pen_documento']) ?>#toolbar=1&navpanes=0"></iframe>
      </div>
    </section>

    <aside class="panel panel-firma">
      <div>
        <div class="badge-estado <?= !empty($pendiente['pu_firma_ruta']) ? 'firmado' : '' ?>" id="estadoFirma">
          <?= !empty($pendiente['pu_firma_ruta']) ? 'PDF firmado' : 'Pendiente por firmar' ?>
        </div>
      </div>

      <div>
        <h2 class="h5 mb-2">Firma del usuario</h2>
        <p class="texto-ayuda">La firma queda asociada al pendiente y habilita la confirmacion desde la pantalla principal.</p>
      </div>

      <canvas id="signature-pad"></canvas>

      <div class="acciones">
        <button type="button" id="btnLimpiar" class="btn btn-outline-secondary">Borrar firma</button>
        <button type="button" id="btnGuardarFirma" class="btn btn-primary">Guardar firma</button>
        <button type="button" id="btnCerrar" class="btn btn-light border">Cerrar ventana</button>
      </div>
    </aside>
  </div>

  <script>
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    const estadoFirma = document.getElementById('estadoFirma');
    let drawing = false;
    let hasDrawn = false;

    function resizeCanvas() {
      const ratio = window.devicePixelRatio || 1;
      const rect = canvas.getBoundingClientRect();
      canvas.width = rect.width * ratio;
      canvas.height = rect.height * ratio;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.scale(ratio, ratio);
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.strokeStyle = '#0f172a';
      if (!hasDrawn) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
      }
    }

    function getPos(event) {
      const rect = canvas.getBoundingClientRect();
      if (event.touches && event.touches.length > 0) {
        return {
          x: event.touches[0].clientX - rect.left,
          y: event.touches[0].clientY - rect.top
        };
      }
      return {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top
      };
    }

    function startDrawing(event) {
      drawing = true;
      hasDrawn = true;
      const pos = getPos(event);
      ctx.beginPath();
      ctx.moveTo(pos.x, pos.y);
    }

    function draw(event) {
      if (!drawing) {
        return;
      }
      const pos = getPos(event);
      ctx.lineTo(pos.x, pos.y);
      ctx.stroke();
    }

    function stopDrawing() {
      drawing = false;
    }

    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseleave', stopDrawing);

    canvas.addEventListener('touchstart', function (event) {
      event.preventDefault();
      startDrawing(event);
    }, { passive: false });

    canvas.addEventListener('touchmove', function (event) {
      event.preventDefault();
      draw(event);
    }, { passive: false });

    canvas.addEventListener('touchend', stopDrawing);

    document.getElementById('btnLimpiar').addEventListener('click', function () {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      hasDrawn = false;
    });

    document.getElementById('btnGuardarFirma').addEventListener('click', async function () {
      if (!hasDrawn) {
        alert('Debes firmar dentro del recuadro antes de guardar.');
        return;
      }

      const formData = new FormData();
      formData.append('accion', 'guardar_firma_pendiente');
      formData.append('pendiente_usuario_id', '<?= (int) $pendienteUsuarioId ?>');
      formData.append('firma', canvas.toDataURL('image/png'));

      try {
        const respuesta = await fetch('PendienteFirmaController.php', {
          method: 'POST',
          body: formData
        });

        const data = await respuesta.json();
        if (!respuesta.ok || !data.success) {
          alert(data.message || 'No fue posible guardar la firma.');
          return;
        }

        estadoFirma.textContent = 'PDF firmado';
        estadoFirma.classList.add('firmado');

        if (window.opener && !window.opener.closed) {
          window.opener.postMessage({
            tipo: 'pendiente_pdf_firmado',
            pendienteUsuarioId: <?= (int) $pendienteUsuarioId ?>
          }, '*');
        }

        alert(data.message || 'Firma guardada correctamente.');
      } catch (error) {
        alert('Ocurrio un error al guardar la firma.');
      }
    });

    document.getElementById('btnCerrar').addEventListener('click', function () {
      window.close();
    });

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();
  </script>
</body>
</html>
