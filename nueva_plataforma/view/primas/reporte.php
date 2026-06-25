<?php
require("../../../login_autentica.php");

$datos = [];
if (isset($_POST['datosusertabla'])) {
    $datos = json_decode((string) $_POST['datosusertabla'], true);
    if (!is_array($datos)) {
        $datos = [];
    }
}

$semestre = $_POST['param36'] ?? '';
$anio = preg_replace('/[^0-9]/', '', (string) ($_POST['param34'] ?? date('Y')));
$fechaReporte = $anio . ($semestre === 'Segunda' ? '-12-31' : '-06-30');
$ids = array_values(array_filter(array_map(static fn($fila) => $fila['id'] ?? null, $datos)));

function valorReporte($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$encabezados = [
    'id' => 'Id',
    'nombreCompleto' => 'Trabajador',
    'contrato' => 'Contrato',
    'cedula' => 'Cedula',
    'nombreCargo' => 'Cargo',
    'salario' => 'Salario',
    'auxilio' => 'Auxilio',
    'descanso' => 'Descanso',
    'NoTrabajo' => 'No trabajo',
    'SinRegistro' => 'Sin registro',
    'Incapacidad' => 'Incapacidad',
    'Vacaciones' => 'Vacaciones',
    'licenciasPermisos' => 'Licencias',
    'totalDiasPrima' => 'Total dias prima',
    'diasProyectados' => 'Dias proyectados',
    'valorDiasPrima_formateado' => 'Total prima',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Primas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f5f6fa; padding: 28px; }
        .container-fluid { max-width: 1900px; }
        .card-header { background: #00458D; color: #fff; }
        table thead th { vertical-align: middle; white-space: nowrap; }
        .prima-dias { background-color: rgb(240, 230, 170) !important; font-weight: 700; }
        .prima-valor { background-color: rgb(183, 230, 190) !important; font-weight: 700; }
        .dias-sin-registro { background-color: #f8d7da !important; color: #842029; font-weight: 700; }
        .dias-no-trabajados { color: #c1121f; font-weight: 700; }
        .custom-controls { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="custom-controls">
        <input type="file" class="form-control w-auto" id="archivo" accept="image/*,application/pdf" onchange="mostrarImagen(event)">
        <button class="btn btn-outline-primary" onclick="guardarImagenCompleta()">
            <i class="bi bi-image"></i> Guardar imagen completa
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Reporte de Primas <?= valorReporte($semestre) ?> <?= valorReporte($anio) ?></span>
            <div>
                <button class="btn btn-sm btn-light me-2" onclick="guardarImagen()" title="Guardar imagen"><i class="bi bi-camera"></i></button>
                <button class="btn btn-sm btn-light" onclick="generarPDF()" title="Generar PDF"><i class="bi bi-file-earmark-pdf-fill"></i></button>
            </div>
        </div>
        <div class="card-body table-responsive" id="tablaNomina">
            <?php if (count($datos) > 0): ?>
                <table class="table table-hover table-bordered align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($encabezados as $key => $titulo): ?>
                                <th class="<?= $key === 'totalDiasPrima' ? 'prima-dias' : ($key === 'valorDiasPrima_formateado' ? 'prima-valor' : ($key === 'SinRegistro' ? 'dias-sin-registro' : ($key === 'NoTrabajo' ? 'dias-no-trabajados' : ''))) ?>">
                                    <?= valorReporte($titulo) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $fila): ?>
                            <tr>
                                <?php foreach ($encabezados as $key => $titulo): ?>
                                    <td class="<?= $key === 'totalDiasPrima' ? 'prima-dias' : ($key === 'valorDiasPrima_formateado' ? 'prima-valor' : ($key === 'SinRegistro' ? 'dias-sin-registro' : ($key === 'NoTrabajo' ? 'dias-no-trabajados' : ''))) ?>">
                                        <?= valorReporte($fila[$key] ?? '') ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted">No se recibieron datos para el reporte.</p>
            <?php endif; ?>
        </div>

        <div class="card mt-3 shadow-sm" id="imagenCargadaContainer" style="display:none;">
            <div class="card-header">Imagen cargada</div>
            <div class="card-body text-center">
                <img id="imagenCargada" src="" alt="Imagen cargada" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function mostrarImagen(event) {
    const file = event.target.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('imagenCargada').src = e.target.result;
        document.getElementById('imagenCargadaContainer').style.display = 'block';
    };
    if (file) {
        reader.readAsDataURL(file);
    }
}

function crearContenedorCaptura() {
    const contenedor = document.createElement('div');
    contenedor.style.position = 'absolute';
    contenedor.style.top = '-9999px';
    contenedor.style.left = '-9999px';
    contenedor.style.background = '#fff';
    contenedor.style.padding = '20px';
    contenedor.appendChild(document.getElementById('tablaNomina').cloneNode(true));

    const imagenContainer = document.getElementById('imagenCargadaContainer');
    if (imagenContainer.style.display !== 'none') {
        contenedor.appendChild(imagenContainer.cloneNode(true));
    }

    document.body.appendChild(contenedor);
    return contenedor;
}

function guardarImagenCompleta() {
    const contenedor = crearContenedorCaptura();
    html2canvas(contenedor, { useCORS: true, scale: 2 }).then(canvas => {
        canvas.toBlob(function(blob) {
            const formData = new FormData();
            formData.append('imagen', blob, 'reporte_primas.png');
            formData.append('ids', JSON.stringify(<?= json_encode($ids) ?>));
            formData.append('fecha', '<?= valorReporte($fechaReporte) ?>');

            fetch('../../../primaImgReporteNomina.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.exito ? 'Imagen guardada con exito: ' + data.nombre_archivo : 'Error: ' + data.mensaje);
                document.body.removeChild(contenedor);
            })
            .catch(error => {
                console.error('Error al enviar al servidor:', error);
                document.body.removeChild(contenedor);
            });
        }, 'image/png');
    });
}

function guardarImagen() {
    html2canvas(document.getElementById('tablaNomina'), { scale: 2 }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'reporte_primas.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}

function generarPDF() {
    const { jsPDF } = window.jspdf;
    html2canvas(document.getElementById('tablaNomina'), { scale: 2 }).then(canvas => {
        const pdf = new jsPDF('l', 'mm', 'a4');
        const imgData = canvas.toDataURL('image/png');
        const width = pdf.internal.pageSize.getWidth();
        const height = (canvas.height * width) / canvas.width;
        pdf.addImage(imgData, 'PNG', 0, 8, width, height);
        pdf.save('reporte_primas.pdf');
    });
}
</script>
</body>
</html>
