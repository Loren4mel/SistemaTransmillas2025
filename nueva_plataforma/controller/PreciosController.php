<?php
require_once "../model/PreciosModel.php";

$modelo = new Precios();

// 2) Obtener un registro por ID
if (isset($_POST['obtener_por_id'])) {

    $id = intval($_POST['id'] ?? 0);
    $datos = $modelo->obtenerPrecioCreditoPorId($id);
    echo json_encode($datos);
    exit;
}

// 3) Actualizar registro
if (isset($_POST['actualizar_registro'])) {

    $ok = $modelo->actualizarPrecioCredito($_POST);
    echo json_encode(['success' => $ok]);
    exit;
}

// 4) Agregar registro
if (isset($_POST['agregar_registro'])) {

    $ok = $modelo->agregarPrecioCredito($_POST);
    echo json_encode(['success' => $ok]);
    exit;
}

// 5) Validar existencia (para evitar duplicados)
if (isset($_POST['validar_existencia'])) {

    $existe = $modelo->existePrecioCredito($_POST);
    echo json_encode(['existe' => $existe]);
    exit;
}

// 6) Eliminar registro
if (isset($_POST['eliminar_usuario'])) {

    $id = intval($_POST['id'] ?? 0);
    $ok = $modelo->eliminarPrecioCredito($id);
    echo json_encode(['success' => $ok]);
    exit;
}
if (isset($_POST['buscar_referencia'])) {
    $result = $modelo->buscarReferencia($_POST['origen'], $_POST['destino'], $_POST['servicio']);
    echo json_encode($result);
    exit;
}

if (isset($_POST['validar_existencia_completa'])) {
    $existe = $modelo->existeRegistroCompleto($_POST);
    echo json_encode(['existe' => $existe]);
    exit;
}

if (isset($_POST['actualizar_campo'])) {
    $id = $_POST['id'];
    $campo = $_POST['campo'];
    $valor = $_POST['valor'];

    
    $modelo->actualizarCampo($id, $campo, $valor);
    echo json_encode(['ok' => true]);
    exit;
}





if (isset($_GET['excel'])) {

   
    

    $Origen   = $_GET['Origen'] ?? '';
    $Destino  = $_GET['Destino'] ?? '';
    $Creditos = $_GET['Creditos'] ?? '';
    $Servicio = $_GET['Servicio'] ?? '';
    $Estado   = $_GET['Estado'] ?? '';

    $datos = $modelo->obtenerPrecios(
        $Origen, $Destino, $Creditos, $Servicio, $Estado
    );

    // $nombreCredito = $modelo->obtenerNombreCredito($Creditos);
    $fecha = date('Y-m-d');
    $año = date('Y');

    // if (!empty($nombreCredito)) {
    //     $nombreCredito = preg_replace('/[^A-Za-z0-9_-]/', '_', $nombreCredito);
    //     $archivo = "Precios_Credito_{$nombreCredito}_{$fecha}.xls";
    // } else {
        $archivo = "Precios_Credito_{$año}.xls";
    // }

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=$archivo");
    header("Pragma: no-cache");
    header("Expires: 0");

    $logoUrl = "https://sistema.transmillas.com/images/logoExcelTransmillas.JPG";

    echo "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #ffffff;
        color: #2c2c2c;
    }

    .logo {
        text-align: left;
        margin-bottom: 10px;
    }

    .titulo {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #1f2937;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th {
        background-color: #e5e7eb; /* gris moderno */
        color: #111827;
        font-weight: 600;
        text-align: center;
        padding: 8px;
        border: 1px solid #d1d5db;
    }

    td {
        padding: 7px;
        border: 1px solid #e5e7eb;
        text-align: center;
        color: #374151;
    }

    tr:nth-child(even) {
        background-color: #f9fafb;
    }

    tr:hover {
        background-color: #f1f5f9;
    }
    .titulos {
        background-color: #215C98;
        color: #f1f5f9;
    }
    .precios {
        background-color: #ddfedbff;
        color: #374151;
    }
    .tituloCredito {
        background-color: #ddfedbff;
        color: #153D64;
    }

    .fila-header th {
        height: 90px;              /* un poco más que la imagen */
        vertical-align: middle;
        background-color: #ddfedbff;
        color: #153D64;
    }

    .logo-cell {
        text-align: center;
        background-color: #ddfedbff;
        color: #153D64;
    }

    .titulo-cell {
        font-size: 20px;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
        background-color: #ddfedbff;
        color: #153D64;
    }


</style>

        </style>
    </head>
    <body>




    <table>
        <tr class='fila-header'>
            <th colspan='3' class='logo-cell'>
                <img src='{$logoUrl}' style='height:79px; width:672px;'>
            </th>
            <th colspan='7' class='titulo-cell'>
                TABLA DE PRECIOS –  AÑO 2026
            </th>
        </tr>
        <tr>
            
            <th class='titulos'>Origen</th>
            <th class='titulos'>Destino</th>
            <th class='titulos'>Precio 5 Kg</th>
            <th class='titulos'>6-20 Kg</th>
            <th class='titulos'>21-50 Kg</th>
            <th class='titulos'>51-100 Kg</th>
            <th class='titulos'>101-150 Kg</th>
            <th class='titulos'>151-200 Kg</th>
            <th class='titulos'>201-250 Kg</th>
            <th class='titulos'>Servicio</th>
        </tr>
    ";

    foreach ($datos as $row) {
        echo "
        <tr>
            
            <td>{$row['ciudad_origen']}</td>
            <td>{$row['ciudad_destino']}</td>
            <td class='precios'>{$row['pre_kilo']}</td>
            <td>{$row['precio_6_20']}</td>
            <td>{$row['precio_21_50']}</td>
            <td>{$row['precio_51_100']}</td>
            <td>{$row['precio_101_150']}</td>
            <td>{$row['precio_151_200']}</td>
            <td>{$row['precio_201_250']}</td>
            <td>{$row['tip_nom']}</td>
        </tr>";
    }

    echo "
    </table>
    </body>
    </html>";

    exit;
}




if (isset($_POST['obtener_contactos'])) {
    $credito = $_POST['credito'];
    $data = $modelo->obtenerContactosPorCredito($credito);
    echo json_encode($data);
    exit;
}

//Enviar correos y Whatsapps 
if (isset($_POST['enviar_precios'])) {

    $idCredito = $_POST['credito'] ?? null;
    $correos   = $_POST['correos'] ?? [];
    $telefonos = $_POST['telefonos'] ?? [];

    if (empty($correos) && empty($telefonos)) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Debe seleccionar al menos un correo o un teléfono'
        ]);
        exit;
    }

    // 📎 VALIDAR ARCHIVO EXCEL
    if (!isset($_FILES['archivoExcel']) || $_FILES['archivoExcel']['error'] !== 0) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Debe adjuntar el archivo Excel'
        ]);
        exit;
    }

    $archivo = $_FILES['archivoExcel'];

    // 📂 DATOS DEL ARCHIVO
    $nombreOriginal = $archivo['name'];
    $tmpPath        = $archivo['tmp_name'];

    // 📁 GUARDAR ARCHIVO
    $nombreFinal = time() . '_' . $nombreOriginal;
    $rutaFinal   = __DIR__ . '/../uploads/temp/' . $nombreFinal;

    if (!move_uploaded_file($tmpPath, $rutaFinal)) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'No se pudo guardar el archivo'
        ]);
        exit;
    }

    // 🌐 URL pública SOLO para WhatsApp
    $urlArchivo = 'https://sistema.transmillas.com/temp/' . $nombreFinal;

    $resultados = [
        'correos'  => [],
        'whatsapp' => []
    ];

    // ===============================
    // 📧 ENVIAR CORREOS (ADJUNTO)
    // ===============================
    foreach ($correos as $correo) {

        $r = $modelo->enviarPreciosCorreo(
            $idCredito,
            $correo,
            $rutaFinal, // 📎 ARCHIVO ADJUNTO
            []           // ya no usamos link
        );

        $resultados['correos'][] = [
            'correo'    => $correo,
            'resultado' => $r
        ];
    }

    // ===============================
    // 📱 ENVIAR WHATSAPP (DOCUMENTO)
    // ===============================
    foreach ($telefonos as $telefono) {

        $modelo->enviarComprobanteCelular(
            $idCredito,
            $telefono,
            '',
            [
                'tipo'    => 'document',
                'archivo' => $urlArchivo,
                'nombre'  => $nombreOriginal
            ]
        );

        $resultados['whatsapp'][] = $telefono;
    }

    // 🧹 OPCIONAL: ELIMINAR ARCHIVO
    // unlink($rutaFinal);

    echo json_encode([
        'success' => true,
        'mensaje' => 'Archivo enviado correctamente',
        'detalle' => $resultados
    ]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {

    writeLog("AJAX recibido en obtenerPrecioCreditos");

    $Origen   = $_POST['Origen'] ?? '';
    $Destino  = $_POST['Destino'] ?? '';
    $Creditos = $_POST['Creditos'] ?? '';
    $Servicio = $_POST['Servicio'] ?? '';
    $Estado   = $_POST['Estado'] ?? '';

    writeLog("Parámetros: Origen=$Origen | Destino=$Destino | Creditos=$Creditos | Servicio=$Servicio | Estado=$Estado");

    try {
        $usuarios = $modelo->obtenerPrecios($Origen, $Destino, $Creditos, $Servicio, $Estado);

        writeLog("Registros encontrados: " . count($usuarios));

        echo json_encode($usuarios);

    } catch (Exception $e) {

        writeLog("ERROR: " . $e->getMessage());

        echo json_encode([
            "error" => true,
            "mensaje" => "Error al procesar la solicitud."
        ]);
    }

    exit;
}

function writeLog($mensaje, $archivo = 'obtenerPrecioCreditos.log')
{
    $ruta = __DIR__ . '/logs/';

    if (!file_exists($ruta)) {
        mkdir($ruta, 0777, true);
    }

    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

    $linea = "[$fecha][$ip] $mensaje" . PHP_EOL;

    file_put_contents($ruta . $archivo, $linea, FILE_APPEND);
}

$Ciudades = $modelo->obtenerCiudades();
$Creditos = $modelo->obtenerCreditos();
$TServicios = $modelo->tiposDeServicios();



include "../view/Precios/index.php";
