<?php
// ==================== INICIO: BUFFER DE SALIDA ====================
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR);

$captured_errors = [];

set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$captured_errors) {
    $captured_errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    return true;
});

set_exception_handler(function ($exception) {
    error_log("Excepcion no capturada: " . $exception->getMessage());
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        sendJsonResponse(['error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()], 500);
    } else {
        echo "<h1>Error</h1><pre>" . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "</pre>";
    }
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Error fatal: " . $error['message']);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['fatal_error' => $error['message'], 'file' => $error['file'], 'line' => $error['line']]);
        } else {
            echo "<h1>Error fatal</h1><pre>" . $error['message'] . " en " . $error['file'] . ":" . $error['line'] . "</pre>";
        }
        exit;
    }
});

function sendJsonResponse($data, $statusCode = 200)
{
    if (ob_get_level())
        ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ==================== AUTENTICACIÓN ====================
require("../../login_autentica.php");

// ==================== MODELO ====================
require_once "../model/PreoperacionalModel.php";

$modelo = new PreoperacionalModel();

// --------------------------------------------------------------------
// PETICIONES AJAX
// --------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $accion = $_POST['accion'];
        $response = ['success' => false, 'message' => 'Acción no válida'];

        switch ($accion) {
            case 'guardar':
                $estado = $_POST['estado'] ?? '';
                $dataJson = $_POST['data'] ?? '';
                $idVehiculo = !empty($_POST['param1']) ? (int) $_POST['param1'] : null;
                $tipoVehiculo = $_POST['param2'] ?? '';
                $idUsuario = (int) ($_POST['user'] ?? $_SESSION['usuario_id']);
                $fechaHora = date('Y-m-d H:i:s');
                $observaciones = $_POST['param7'] ?? '';
                $accionCorrectiva = $_POST['param8'] ?? '';
                $responsable = $_POST['param9'] ?? '';
                $temperatura = $_POST['param19'] ?? '';
                $kilometraje = (int) ($_POST['param12'] ?? 0);
                $limpiomaleta = $_POST['param21'] ?? '';
                $descValidada = $_POST['param10'] ?? '';
                $idPre = (int) ($_POST['param11'] ?? 0);

                // Procesar imagen de kilometraje
                $imagenKilo = '';
                if (isset($_FILES['param30']) && $_FILES['param30']['error'] === UPLOAD_ERR_OK) {
                    $nombreArchivo = date("Y-m-d-H-i-s") . "_" . $_FILES['param30']['name'];
                    $ruta = "./preoperacional/" . $nombreArchivo;
                    if (move_uploaded_file($_FILES['param30']['tmp_name'], $ruta)) {
                        $imagenKilo = $ruta;
                    }
                }

                if ($idPre > 0) {
                    // Actualización (validación)
                    $datosActualizar = [
                        'prefechavalidacion' => date('Y-m-d H:i:s'),
                        'predatosvalidados' => $dataJson,
                        'pre_descvalidada' => $descValidada,
                        'pre_iduservalida' => $_SESSION['usuario_id'],
                        'preestado' => ($estado == 'covid19') ? 'Validado Covid19' : 'Validado',
                        'pre_correctiva' => $accionCorrectiva,
                        'pre_responsable' => $responsable,
                        'pre_temperatura' => $temperatura,
                        'pre_kilrecorridos' => $kilometraje
                    ];
                    $ok = $modelo->actualizarPreoperacional($idPre, $datosActualizar);
                    if ($ok && $kilometraje > 0 && $idVehiculo > 0) {
                        $modelo->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
                    }
                    if ($imagenKilo) {
                        $modelo->actualizarImagenKilo($idPre, $imagenKilo);
                    }
                } else {
                    // Nuevo registro
                    $datosInsert = [
                        'prevehiculo' => $idVehiculo,
                        'pretipovehiculo' => $tipoVehiculo,
                        'prefechaingreso' => $fechaHora,
                        'preidusuario' => $idUsuario,
                        'preencuesta' => $dataJson,
                        'pre_obsevaciones' => $observaciones,
                        'pre_correctiva' => $accionCorrectiva,
                        'pre_responsable' => $responsable,
                        'pre_temperatura' => $temperatura,
                        'pre_kilrecorridos' => $kilometraje,
                        'pre_limpiomaleta' => $limpiomaleta,
                        'pre_img_kilo' => $imagenKilo,
                        'preestado' => ($estado == 'covid19') ? 'covid19' : 'pendiente'
                    ];
                    $idInsertado = $modelo->insertarPreoperacional($datosInsert);
                    if ($idInsertado) {
                        // Imagen de temperatura
                        if (isset($_FILES['param20']) && $_FILES['param20']['error'] === UPLOAD_ERR_OK) {
                            $modelo->guardarImagen($_FILES['param20'], $idInsertado, 2);
                        }
                        if ($kilometraje > 0 && $idVehiculo > 0) {
                            $modelo->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
                        }
                    }
                }

                $response = ['success' => true, 'message' => 'Preoperacional guardado correctamente'];
                break;

            case 'buscarDatos':
                if (!isset($_POST['user']) || !isset($_POST['fecha']) || !isset($_POST['campo'])) {
                    sendJsonResponse(null, 400);
                }
                $iduser = (int) $_POST['user'];
                $fecha = $_POST['fecha'];
                $campo = $_POST['campo'];

                if ($campo !== 'preencuesta') {
                    sendJsonResponse(null);
                }

                $data = $modelo->obtenerDatosParaPrecarga($iduser, $fecha, $campo);
                sendJsonResponse($data);
                break;

            default:
                break;
        }

        global $captured_errors;
        if (!empty($captured_errors)) {
            $response['debug_errors'] = $captured_errors;
        }

        sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("Error en acción POST: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

// --------------------------------------------------------------------
// CARGA DE LA VISTA PRINCIPAL (si no es AJAX)
// --------------------------------------------------------------------
$param4 = $_GET['param4'] ?? '';
$param5 = $_GET['param5'] ?? '';
$iduser = (int) ($_GET['iduser'] ?? $_SESSION['usuario_id'] ?? 0);
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$preoperacional = $_GET['preoperacional'] ?? '';
$idvehiculo = isset($_GET['idvehiculo']) ? (int) $_GET['idvehiculo'] : null;

$esCovid = ($param4 == 'covid19');
$esValidacion = ($preoperacional == 'validarpreoperacional' || $param5 == 'valida');

// Obtener datos del vehículo solo si es necesario
$datosVehiculo = null;
if (!$esCovid || $idvehiculo !== null) {
    $datosVehiculo = $modelo->obtenerDatosVehiculoYUsuario($iduser, $idvehiculo);
}
if ($datosVehiculo === null) {
    $datosVehiculo = []; // array vacío para evitar errores en la vista
}

$tipovehiculo = strtoupper($datosVehiculo['veh_tipo'] ?? '');
$nivel_acceso = $_SESSION['usuario_rol'];

$registroExistente = null;
if ($param4 == 'ingresado' || $param4 == 'covid19') {
    $registroExistente = $modelo->obtenerRegistroPorFecha($iduser, $fecha);
}
if ($preoperacional == 'validarpreoperacional' && isset($_GET['idpre'])) {
    $registroExistente = $modelo->obtenerRegistroPorId((int) $_GET['idpre']);
}

// Limpiar buffer y mostrar la vista
ob_clean();
include "../view/Preoperacional/index.php";
?>