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

    $formatoEncuesta = 'nuevo'; // Por defecto: nuevo formato

    // Buscar registro existente si aplica
    $registroExistente = null;

    if ($param4 == 'ingresado' || $param4 == 'covid19') {
        $registroExistente = $service->obtenerRegistroPorFecha($iduser, $fecha);
    }

    // Si estamos en modo legado, buscar el registro más reciente si no hay para la fecha
    if ($formatoEncuesta === 'legado' && $registroExistente === null) {
        $registroExistente = $service->obtenerUltimoRegistro($iduser);
    }

    if ($preoperacional == 'validarpreoperacional' && isset($_GET['idpre'])) {
        $registroExistente = $service->obtenerRegistroPorId((int) $_GET['idpre']);

        // Detectar el formato de la encuesta existente para validación
        if ($registroExistente && !empty($registroExistente['preencuesta'])) {
            $formatoEncuesta = $service->detectarFormato($registroExistente['preencuesta']);
        }
    }

    // En modo validación, cargar la firma del operario como imagen (no editable)
    $firmaDataUri = null;
    if ($esValidacion && $registroExistente && !empty($registroExistente['idpreoperacinal'])) {
        $firmaDoc = $service->obtenerDocumentoFirma($registroExistente['idpreoperacinal']);
        if ($firmaDoc && !empty($firmaDoc['doc_ruta']) && file_exists($firmaDoc['doc_ruta'])) {
            $firmaContent = file_get_contents($firmaDoc['doc_ruta']);
            if ($firmaContent !== false) {
                $extension = strtolower(pathinfo($firmaDoc['doc_ruta'], PATHINFO_EXTENSION));
                $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';
                $firmaDataUri = 'data:' . $mimeType . ';base64,' . base64_encode($firmaContent);
            }
        }
    }

    // Determinar qué secciones mostrar basadas en el rol y tipo de vehículo
    require_once __DIR__ . '/../helpers/PreoperacionalHelpers/PreoperacionalNuevaEncuestaViewHelper.php';

    // Determinar si es conductor (CARRO)
    $esConductor = PreoperacionalNuevaEncuestaViewHelper::esConductor($nivel_acceso, $tipovehiculo);

    // NUEVO FORMATO: Solo secciones basadas en rol (SIN COVID, SIN FATIGA)
    // Cuando es conductor o vehículo propio, NO se muestran preguntas administrativas
    $esVehiculoPropio = PreoperacionalNuevaEncuestaViewHelper::tieneVehiculoPropio($tipovehiculo);
    $mostrarSecciones = [
        'administrativo' => !$esConductor && !$esVehiculoPropio && PreoperacionalNuevaEncuestaViewHelper::esPersonalAdministrativo($nivel_acceso),
        'conductor' => $esConductor,
        'vehiculo_propio' => $esVehiculoPropio,
        'auxiliar_carga' => PreoperacionalNuevaEncuestaViewHelper::esAuxiliarCarga($nivel_acceso),
        'preoperacional_vehiculo' => ($tipovehiculo === 'CARRO'),
        'preoperacional_moto' => ($tipovehiculo === 'MOTO')
    ];

    // ==================== MODO PRUEBA ====================
    // Sobrescribir secciones según el caso de prueba para validar responsive
    $casoPrueba = $_GET['caso_prueba'] ?? '';
    if (!empty($casoPrueba) && !$esValidacion) {
        $tipovehiculo = strtoupper($_GET['tipo_vehiculo'] ?? $tipovehiculo);
        switch ($casoPrueba) {
            case 'administrativo':
                $mostrarSecciones = [
                    'administrativo' => true,
                    'conductor' => false,
                    'vehiculo_propio' => false,
                    'auxiliar_carga' => false,
                    'preoperacional_vehiculo' => false,
                    'preoperacional_moto' => false
                ];
                $formatoEncuesta = 'nuevo';
                break;
            case 'conductor':
                $mostrarSecciones = [
                    'administrativo' => false,
                    'conductor' => true,
                    'vehiculo_propio' => false,
                    'auxiliar_carga' => false,
                    'preoperacional_vehiculo' => ($tipovehiculo === 'CARRO'),
                    'preoperacional_moto' => false
                ];
                $formatoEncuesta = 'nuevo';
                break;
            case 'moto':
                $mostrarSecciones = [
                    'administrativo' => false,
                    'conductor' => false,
                    'vehiculo_propio' => true,
                    'auxiliar_carga' => false,
                    'preoperacional_vehiculo' => false,
                    'preoperacional_moto' => ($tipovehiculo === 'MOTO')
                ];
                $formatoEncuesta = 'nuevo';
                break;
            case 'auxiliar':
                $mostrarSecciones = [
                    'administrativo' => false,
                    'conductor' => false,
                    'vehiculo_propio' => false,
                    'auxiliar_carga' => true,
                    'preoperacional_vehiculo' => false,
                    'preoperacional_moto' => false
                ];
                $formatoEncuesta = 'nuevo';
                break;
            case 'legado':
                $mostrarSecciones = [
                    'administrativo' => false,
                    'conductor' => false,
                    'vehiculo_propio' => false,
                    'auxiliar_carga' => false,
                    'preoperacional_vehiculo' => false,
                    'preoperacional_moto' => false
                ];
                $formatoEncuesta = 'legado';
                break;
        }
    }

    // // En modo prueba, inyectar datos de vehículo ficticios si no hay datos reales
    // // para poder verificar el comportamiento responsive de la sección de datos del vehículo
    // if (!empty($casoPrueba) && empty($datosVehiculo)) {
    //     $datosVehiculo = [
    //         'veh_placa' => 'ABC-123',
    //         'veh_marca' => 'TOYOTA',
    //         'veh_modelo' => '2023',
    //         'veh_kilactual' => '85,420',
    //         'veh_tipo' => $tipovehiculo,
    //         'usu_nombre' => 'JUAN PÉREZ',
    //         'usu_identificacion' => '10.123.456',
    //         'usu_licencia' => 'Categoría B1',
    //         'usu_fechalicencia' => '2026-12-15'
    //     ];
    // }

    // Limpiar buffer y cargar la vista
    ob_clean();
    include __DIR__ . '/../view/Preoperacional/index.php';
}
?>