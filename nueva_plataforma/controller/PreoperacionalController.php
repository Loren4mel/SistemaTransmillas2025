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

    // ==================== MODO PRUEBA ====================
    // Permitir sobrescribir parámetros para pruebas
    $casoPrueba = $_GET['caso_prueba'] ?? '';
    $tipoVehiculoPrueba = $_GET['tipo_vehiculo'] ?? '';

    // Inicializar formatoEncuesta antes del switch
    $formatoEncuesta = 'nuevo'; // Por defecto: nuevo formato

    if (!empty($casoPrueba)) {
        // Sobrescribir valores según el caso de prueba seleccionado
        switch ($casoPrueba) {
            case 'administrativo':
                // Simular rol administrativo (asumimos rol = 1)
                $nivel_acceso = 1;
                $tipovehiculo = ''; // Sin vehículo específico
                break;
            case 'conductor':
                // Simular conductor de carro
                $nivel_acceso = 3; // Rol conductor
                $tipovehiculo = 'CARRO';
                break;
            case 'moto':
                // Simular vehículo propio (moto)
                $nivel_acceso = 3;
                $tipovehiculo = 'MOTO';
                break;
            case 'auxiliar':
                // Simular auxiliar de carga
                $nivel_acceso = 5; // Rol auxiliar
                $tipovehiculo = '';
                break;
            case 'legado':
                // Forzar formato legado para pruebas
                $formatoEncuesta = 'legado';
                $nivel_acceso = 3;
                // Usar el tipo de vehículo si se especifica, sino CARRO por defecto
                if (!empty($tipoVehiculoPrueba)) {
                    $tipovehiculo = strtoupper($tipoVehiculoPrueba);
                } else {
                    $tipovehiculo = 'CARRO';
                }
                break;
        }
    }
    // ==================== FIN MODO PRUEBA ====================

    // Buscar registro existente si aplica
    $registroExistente = null;

    if ($param4 == 'ingresado' || $param4 == 'covid19') {
        $registroExistente = $service->obtenerRegistroPorFecha($iduser, $fecha);
    }

    // Si estamos en modo legado (por parámetro o por caso de prueba), buscar el registro más reciente
    if ($formatoEncuesta === 'legado' && $registroExistente === null) {
        $registroExistente = $service->obtenerRegistroPorFecha($iduser, $fecha);
        // Si no hay registro para la fecha, intentar obtener el último registro del usuario
        if ($registroExistente === null) {
            $registroExistente = $service->obtenerUltimoRegistro($iduser);
        }
    }

    if ($preoperacional == 'validarpreoperacional' && isset($_GET['idpre'])) {
        $registroExistente = $service->obtenerRegistroPorId((int) $_GET['idpre']);

        // Detectar el formato de la encuesta existente para validación
        if ($registroExistente && !empty($registroExistente['preencuesta'])) {
            require_once __DIR__ . '/../helpers/PreoperacionalHelpers/PreoperacionalNuevaEncuestaViewHelper.php';
            $formatoEncuesta = PreoperacionalNuevaEncuestaViewHelper::detectarFormato($registroExistente['preencuesta']);
        }
    }

    // Determinar qué secciones mostrar basadas en el rol y tipo de vehículo
    require_once __DIR__ . '/../helpers/PreoperacionalHelpers/PreoperacionalNuevaEncuestaViewHelper.php';

    // Determinar si es conductor (CARRO)
    $esConductor = PreoperacionalNuevaEncuestaViewHelper::esConductor($nivel_acceso, $tipovehiculo);

    // NUEVO FORMATO: Solo secciones basadas en rol (SIN COVID, SIN FATIGA)
    // Cuando es conductor, NO se muestran preguntas administrativas
    $mostrarSecciones = [
        'administrativo' => !$esConductor && PreoperacionalNuevaEncuestaViewHelper::esPersonalAdministrativo($nivel_acceso),
        'conductor' => $esConductor,
        'vehiculo_propio' => PreoperacionalNuevaEncuestaViewHelper::tieneVehiculoPropio($tipovehiculo),
        'auxiliar_carga' => PreoperacionalNuevaEncuestaViewHelper::esAuxiliarCarga($nivel_acceso),
        'preoperacional_vehiculo' => ($tipovehiculo === 'CARRO'),
        'preoperacional_moto' => ($tipovehiculo === 'MOTO')
    ];

    // Limpiar buffer y cargar la vista
    ob_clean();
    include __DIR__ . '/../view/Preoperacional/index.php';
}
?>