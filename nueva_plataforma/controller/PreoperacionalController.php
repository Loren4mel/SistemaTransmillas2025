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
require_once __DIR__ . '/../helpers/PreoperacionalHelpers/Services/PreoperacionalService.php';

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
                // Validación de preoperacional: solo administradores (roles 1, 12)
                // pueden validar registros, alineado con el sistema legacy (consulta_prevalidar.php).
                $idPre = (int) ($_POST['id_preoperacional'] ?? $_POST['param11'] ?? 0);
                if ($idPre > 0) {
                    $rol = $_SESSION['usuario_rol'] ?? 0;
                    if (!in_array($rol, [1, 12])) {
                        $response = ['success' => false, 'message' => 'Solo administradores pueden validar registros preoperacionales'];
                        break;
                    }
                }
                $response = $service->guardarRegistro($_POST, $_FILES);
                break;

            case 'buscarDatos':
                $response = handleBuscarDatos($service);
                break;

            case 'verificar_estado_vehiculo':
                $response = handleVerificarEstadoVehiculo($service);
                break;

            case 'reportar_novedad':
                $response = handleReportarNovedad($service);
                break;

            case 'buscar_vehiculos_disponibles':
                $response = handleBuscarVehiculosDisponibles($service);
                break;

            case 'asignar_vehiculo_inicial':
                $response = handleAsignarVehiculoInicial($service);
                break;

            default:
                $response['message'] = "Acción '$accion' no reconocida";
                break;
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
 * Verifica el estado actual del vehículo asignado al usuario
 */
function handleVerificarEstadoVehiculo($service)
{
    $idVehiculo = isset($_POST['idvehiculo']) ? (int) $_POST['idvehiculo'] : 0;
    if ($idVehiculo <= 0) {
        return ['success' => false, 'message' => 'No se encontró vehículo asignado.'];
    }

    $estado = $service->obtenerEstadoVehiculo($idVehiculo);
    return ['success' => true, 'data' => $estado];
}

/**
 * Procesa el reporte de novedad del vehículo
 */
function handleReportarNovedad($service)
{
    $idVehiculoActual = (int) ($_POST['idvehiculo_actual'] ?? 0);
    $idUsuario = (int) ($_POST['id_usuario'] ?? $_SESSION['usuario_id'] ?? 0);
    $puedeSerOperado = $_POST['puede_ser_operado'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $idVehiculoNuevo = (int) ($_POST['idvehiculo_nuevo'] ?? 0);

    if ($idVehiculoActual <= 0) {
        return ['success' => false, 'message' => 'Vehículo no especificado.'];
    }

    $datos = [
        'idvehiculo_actual' => $idVehiculoActual,
        'id_usuario' => $idUsuario,
        'puede_ser_operado' => $puedeSerOperado,
        'observaciones' => $observaciones,
        'idvehiculo_nuevo' => $idVehiculoNuevo
    ];

    return $service->procesarReporteNovedad($datos, $_FILES);
}

/**
 * Busca vehículos disponibles sin conductor asignado
 */
function handleBuscarVehiculosDisponibles($service)
{
    $vehiculos = $service->obtenerVehiculosDisponibles();
    return ['success' => true, 'data' => $vehiculos];
}

/**
 * Asigna un vehículo a un usuario en el flujo inicial
 * (cuando el usuario no tiene vehículo asignado)
 */
function handleAsignarVehiculoInicial($service)
{
    $idVehiculo = (int) ($_POST['idvehiculo'] ?? 0);
    $idUsuario = (int) ($_POST['id_usuario'] ?? $_SESSION['usuario_id'] ?? 0);

    if ($idVehiculo <= 0) {
        return ['success' => false, 'message' => 'Debe seleccionar un vehículo.'];
    }

    return $service->asignarVehiculoInicial($idVehiculo, $idUsuario);
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

    // Validación de preoperacional: solo administradores (roles 1, 12) pueden
    // acceder a la página de validación, alineado con el sistema legacy.
    if ($esValidacion) {
        $rol = $_SESSION['usuario_rol'] ?? 0;
        if (!in_array($rol, [1, 12])) {
            http_response_code(403);
            echo "<h2>Acceso denegado</h2><p>Solo administradores pueden validar registros preoperacionales.</p>";
            exit;
        }
    }

    // Obtener datos del vehículo y usuario
    $datosVehiculo = $service->obtenerDatosVehiculoYUsuario($iduser, $idvehiculo);
    if ($datosVehiculo === null) {
        $datosVehiculo = []; // Array vacío para evitar errores en la vista
    }

    $tipovehiculo = strtoupper($datosVehiculo['veh_tipo'] ?? '');
    $nivel_acceso = $_SESSION['usuario_rol'];

    $formatoEncuesta = 'nuevo';

    // Calcular estado de documentos del vehículo (alertas de expiración)
    $estadoDocumentos = !empty($datosVehiculo) ? $service->getEstadoDocumentosVehiculo($datosVehiculo) : ['alertas' => [], 'expired' => false, 'max_severity' => 0, 'bloquear' => false];
    $alertasVehiculoHtml = $service->generarHtmlAlertasVehiculo($estadoDocumentos);
    $alertaSeveridadVehiculo = $estadoDocumentos['max_severity'] ?? 0;
    $mostrarAlertaVencidos = $estadoDocumentos['expired'] && !$esValidacion;

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

        // Detectar param4 desde el preestado del registro (defensa: si no vino en URL)
        if ($registroExistente && empty($_GET['param4'])) {
            if (in_array($registroExistente['preestado'], ['covid19', 'Validado Covid19'])) {
                $param4 = 'covid19';
            } elseif (!empty($registroExistente['preestado'])) {
                $param4 = 'ingresado';
            }
        }
    }

    // Re-evaluar esCovid: param4 pudo ser auto-detectado del registro
    $esCovid = ($param4 == 'covid19');

    // En modo validación, asegurar param5=valida (necesario para mostrar implementos en legado)
    if ($esValidacion && empty($param5)) {
        $param5 = 'valida';
    }

    // Fallback de tipovehiculo: si no se encontró por query actual, usar el del registro original
    if (empty($tipovehiculo) && $registroExistente && !empty($registroExistente['pretipovehiculo'])) {
        $tipovehiculo = strtoupper($registroExistente['pretipovehiculo']);
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

    // ==================== ESQUEMA RELACIONAL: Resolución de versiones ====================
    $idVersionActiva = null;

    // Cargar respuestas desde preop_respuestas si el registro usa esquema relacional
    $valoresEncuestaRelacional = [];
    $esRegistroRelacional = false;
    if ($registroExistente && !empty($registroExistente['id_version'])) {
        $valoresEncuestaRelacional = $service->obtenerRespuestasVersion(
            $registroExistente['idpreoperacinal'],
            $registroExistente['id_version']
        );
        $idVersionActiva = $registroExistente['id_version'];
        $esRegistroRelacional = true;
    }

    // Para nuevos registros, siempre resolver la versión activa
    if (!$idVersionActiva) {
        $versionesActivas = $service->resolverVersionesAplicables($tipovehiculo, $nivel_acceso);
        $versionPrincipal = $versionesActivas['version_vehiculo'] ?? $versionesActivas['version_usuario'] ?? null;
        $idVersionActiva = $versionPrincipal ? $versionPrincipal['id_version'] : null;
    }

    // ==================== VERIFICACIÓN DE NOVEDAD VEHICULAR ====================
    require_once __DIR__ . '/../helpers/PreoperacionalHelpers/PreoperacionalNovedadHelper.php';
    $novedadHelper = new PreoperacionalNovedadHelper();

    // Siempre cargar vehículos disponibles (incluso sin vehículo asignado)
    $vehiculosDisponibles = $novedadHelper->obtenerVehiculosDisponibles();
    $novedadVehiculo = null;

    $tieneVehiculoAsignado = !empty($datosVehiculo) && isset($datosVehiculo['idvehiculos']) && $datosVehiculo['idvehiculos'] > 0;

    if ($tieneVehiculoAsignado) {
        $novedadVehiculo = $novedadHelper->verificarNovedadVehiculo($datosVehiculo['idvehiculos']);
    }

    // Inicializar valores seguros para JS cuando no hay vehículo
    if ($novedadVehiculo === null) {
        $novedadVehiculo = [
            'tieneNovedad' => false,
            'estado_general' => 'OPTIMO',
            'observaciones' => '',
            'ultimoSeguimiento' => null
        ];
    }

    // Determinar qué secciones mostrar basadas en el rol y tipo de vehículo
    require_once __DIR__ . '/../helpers/PreoperacionalHelpers/Views/PreoperacionalNuevaEncuestaViewHelper.php';

    // Determinar si es conductor (CARRO)
    $esConductor = PreoperacionalNuevaEncuestaViewHelper::esConductor($tipovehiculo);

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

    // Fallback: si ningún cuestionario de rol aplica, usar preguntas administrativas por defecto
    $tieneSeccionPersonal = $mostrarSecciones['administrativo']
        || $mostrarSecciones['conductor']
        || $mostrarSecciones['vehiculo_propio']
        || $mostrarSecciones['auxiliar_carga'];
    if (!$tieneSeccionPersonal) {
        $mostrarSecciones['administrativo'] = true;
    }

    // ==================== FIN DE SECCIONES POR ROL ====================

    // Limpiar buffer y cargar la vista
    // Calcular ruta base de la aplicación desde el servidor
    // Ej: /SistemaTransmillas2025/nueva_plataforma
    $appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    ob_clean();
    include __DIR__ . '/../view/Preoperacional/index.php';
}
?>