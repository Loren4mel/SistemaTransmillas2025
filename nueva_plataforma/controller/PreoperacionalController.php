<?php
/**
 * PreoperacionalController - Controlador del módulo preoperacional
 * 
 * Maneja las peticiones HTTP relacionadas con el preoperacional de vehículos
 * y coordina la interacción entre el servicio y la vista.
 */

// ==================== INICIALIZACIÓN ====================
ob_start();

// ==================== MANEJO DE ERRORES ====================
require_once __DIR__ . '/../helpers/ErrorHandler.php';
ErrorHandler::setup();

// ==================== AUTENTICACIÓN ====================
require("../../login_autentica.php");

// ==================== SERVICIO Y MODELO ====================
require_once __DIR__ . '/../helpers/PreoperacionalHelpers/PreoperacionalService.php';

$service = new PreoperacionalService();

// ==================== RUTEADOR DE PETICIONES ====================
handleRequest($service);

// ==================== FUNCIONES ====================

/**
 * Determina el tipo de petición y la maneja apropiadamente
 */
function handleRequest($service)
{
    // Petición AJAX POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        handleAjaxRequest($service);
        return;
    }

    // Carga de la vista principal
    loadView($service);
}

/**
 * Maneja las peticiones AJAX
 */
function handleAjaxRequest($service)
{
    try {
        $accion = $_POST['accion'];
        $response = ['success' => false, 'message' => 'Acción no válida'];

        switch ($accion) {
            case 'guardar':
                $response = $service->guardarRegistro($_POST, $_FILES);
                break;

            case 'buscarDatos':
                $response = handleBuscarDatos($service);
                break;

            default:
                $response['message'] = "Acción '$accion' no reconocida";
                break;
        }

        // Agregar errores de debug si existen
        $captured_errors = ErrorHandler::getCapturedErrors();
        if (!empty($captured_errors)) {
            $response['debug_errors'] = $captured_errors;
        }

        ErrorHandler::sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("Error en acción POST: " . $e->getMessage());
        ErrorHandler::sendJsonResponse([
            'success' => false, 
            'message' => 'Error interno: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Maneja la acción de buscar datos
 */
function handleBuscarDatos($service)
{
    if (!isset($_POST['user']) || !isset($_POST['fecha']) || !isset($_POST['campo'])) {
        ErrorHandler::sendJsonResponse(null, 400);
    }
    
    $idUser = (int) $_POST['user'];
    $fecha = $_POST['fecha'];
    $campo = $_POST['campo'];

    return $service->buscarDatosPrecarga($idUser, $fecha, $campo);
}

/**
 * Carga la vista principal del preoperacional
 */
function loadView($service)
{
    // Obtener parámetros de la URL
    $param4 = $_GET['param4'] ?? '';
    $param5 = $_GET['param5'] ?? '';
    $iduser = (int) ($_GET['iduser'] ?? $_SESSION['usuario_id'] ?? 0);
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $preoperacional = $_GET['preoperacional'] ?? '';
    $idvehiculo = isset($_GET['idvehiculo']) ? (int) $_GET['idvehiculo'] : null;

    // Determinar el modo de operación
    $esCovid = ($param4 == 'covid19');
    $esValidacion = ($preoperacional == 'validarpreoperacional' || $param5 == 'valida');

    // Obtener datos del vehículo y usuario
    $datosVehiculo = $service->obtenerDatosVehiculoYUsuario($iduser, $idvehiculo);
    if ($datosVehiculo === null) {
        $datosVehiculo = []; // Array vacío para evitar errores en la vista
    }

    $tipovehiculo = strtoupper($datosVehiculo['veh_tipo'] ?? '');
    $nivel_acceso = $_SESSION['usuario_rol'];

    // Buscar registro existente si aplica
    $registroExistente = null;
    if ($param4 == 'ingresado' || $param4 == 'covid19') {
        $registroExistente = $service->obtenerRegistroPorFecha($iduser, $fecha);
    }
    if ($preoperacional == 'validarpreoperacional' && isset($_GET['idpre'])) {
        $registroExistente = $service->obtenerRegistroPorId((int) $_GET['idpre']);
    }

    // Limpiar buffer y cargar la vista
    ob_clean();
    include __DIR__ . '/../view/Preoperacional/index.php';
}
?>