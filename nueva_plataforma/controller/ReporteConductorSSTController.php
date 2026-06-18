<?php
/**
 * ReporteConductorSSTController - Controlador del modulo de reportes SST de conductores
 *
 * Maneja las peticiones HTTP relacionadas con el reporte de accidentes y comparendos
 * por parte de conductores, y coordina la interaccion entre el modelo y la vista.
 */

// ==================== INICIALIZACION ====================
ob_start();

// ==================== MANEJO DE ERRORES ====================
require_once __DIR__ . '/../helpers/ErrorHandler.php';
ErrorHandler::setup();

// ==================== AUTENTICACION ====================
require("../../login_autentica.php");

// ==================== MODELO ====================
require_once __DIR__ . '/../model/ReporteConductorSSTModel.php';
$model = new ReporteConductorSSTModel();

// ==================== RUTEADOR DE PETICIONES ====================
handleRequest($model);

// ==================== FUNCIONES ====================

/**
 * Determina el tipo de peticion y la maneja apropiadamente
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function handleRequest($model)
{
    // Peticion AJAX POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        handleAjaxRequest($model);
        return;
    }

    // Peticion AJAX GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
        handleAjaxGetRequest($model);
        return;
    }

    // Carga de la vista principal
    loadView($model);
}

/**
 * Carga la vista principal del reporte conductor SST
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function loadView($model)
{
    $idUsuario = (int) $_SESSION['usuario_id'];
    $modo = (isset($_GET['modo']) && $_GET['modo'] === 'momento') ? 'momento' : 'semanal';
    $tipoFiltro = $_GET['tipo'] ?? null;
    $nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Conductor';

    // Calcular la semana actual
    $semana = $model->calcularSemana(date('Y-m-d'));

    // Determinar que tipos mostrar
    if ($modo === 'momento' && $tipoFiltro !== null && in_array($tipoFiltro, ReporteConductorSSTModel::TIPOS_VALIDOS, true)) {
        $tiposAMostrar = [$tipoFiltro];
    } else {
        $tiposAMostrar = ReporteConductorSSTModel::TIPOS_VALIDOS;
    }

    // Verificar si debe mostrarse el formulario semanal
    $mostrarFormularioSemanal = ($modo === 'semanal') ? $model->debeMostrarFormularioSemanal($idUsuario) : false;

    // Calcular ruta base de la aplicación (mismo patrón que PreoperacionalController)
    $appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));

    ob_clean();
    require_once __DIR__ . '/../view/ReporteConductorSST/index.php';
}

/**
 * Maneja las peticiones AJAX POST
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function handleAjaxRequest($model)
{
    try {
        $accion = $_POST['accion'];

        switch ($accion) {
            case 'guardar':
                guardar($model);
                break;

            case 'verificar':
                verificarSemana($model);
                break;

            default:
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => "Accion '$accion' no reconocida"
                ], 400);
                break;
        }
    } catch (Exception $e) {
        error_log("ReporteConductorSSTController: Error en accion POST: " . $e->getMessage());
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Error interno: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Maneja las peticiones AJAX GET
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function handleAjaxGetRequest($model)
{
    try {
        $ajax = $_GET['ajax'];

        switch ($ajax) {
            case 'consulta':
                consultar($model);
                break;

            case 'detalle':
                detalle($model);
                break;

            default:
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => "Accion GET '$ajax' no reconocida"
                ], 400);
                break;
        }
    } catch (Exception $e) {
        error_log("ReporteConductorSSTController: Error en accion GET: " . $e->getMessage());
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Error interno: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Guarda uno o mas reportes SST del conductor
 *
 * Flujo completo de validacion e insercion:
 * 1. Valida ubicacion GPS
 * 2. Valida tipo_evento
 * 3. Decodifica y valida reportes JSON
 * 4. Verifica ventana semanal si aplica
 * 5. Valida cada reporte individual (tipo, observacion, archivos)
 * 6. Inserta en lote via transaccion
 * 7. Procesa archivos adjuntos
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function guardar($model)
{
    $idUsuario = (int) $_SESSION['usuario_id'];

    // --- 1. Validar ubicacion ---
    $ubicacion = $_POST['ubicacion'] ?? '';
    if (empty($ubicacion)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'La ubicacion es requerida (coordenadas GPS)'
        ], 400);
    }

    // Validar formato de coordenadas: lat,lng con decimales
    if (!preg_match('/^-?\d{1,3}\.\d+,-?\d{1,3}\.\d+$/', $ubicacion)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Formato de ubicacion invalido. Use: latitud,longitud (ej: 4.7109,-74.0721)'
        ], 400);
    }

    // --- 2. Validar tipo_evento ---
    $tipoEvento = $_POST['tipo_evento'] ?? 'semanal';
    if (!in_array($tipoEvento, ReporteConductorSSTModel::TIPOS_EVENTO_VALIDOS, true)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Tipo de evento no valido: ' . $tipoEvento
        ], 400);
    }

    // --- 3. Decodificar y validar reportes JSON ---
    $reportes = json_decode($_POST['reportes'] ?? '[]', true);
    if (!is_array($reportes) || empty($reportes)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'No se recibieron reportes para guardar'
        ], 400);
    }

    // --- 4. Verificar ventana semanal si aplica ---
    if ($tipoEvento === 'semanal') {
        if (!$model->debeMostrarFormularioSemanal($idUsuario)) {
            ErrorHandler::sendJsonResponse([
                'success' => false,
                'message' => 'Ya has enviado tu reporte semanal. Solo puedes enviar uno por semana.',
                'bloqueado' => true
            ], 409);
        }
    }

    // --- 5. Validar cada reporte individual ---
    foreach ($reportes as $index => $reporte) {
        $tipo = $reporte['tipo'] ?? '';
        $respuesta = $reporte['respuesta'] ?? '';
        $observacion = $reporte['observacion'] ?? '';

        // Validar tipo de reporte
        if (!in_array($tipo, ReporteConductorSSTModel::TIPOS_VALIDOS, true)) {
            ErrorHandler::sendJsonResponse([
                'success' => false,
                'message' => "Tipo de reporte no valido en indice $index: '$tipo'"
            ], 400);
        }

        // Si la respuesta es "si" (hubo incidente), validar que tenga observacion
        if ($respuesta === '1' || $respuesta === 'si') {
            if (empty(trim($observacion))) {
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => "La observacion es requerida cuando se reporta un $tipo (indice $index)"
                ], 400);
            }
        }

        // Validar que existan archivos subidos para este tipo
        $fileKey = 'archivos_' . $tipo;
        if (!isset($_FILES[$fileKey]) || empty($_FILES[$fileKey]['name'][0])) {
            ErrorHandler::sendJsonResponse([
                'success' => false,
                'message' => "Se requiere al menos un archivo de evidencia para '$tipo' (indice $index)"
            ], 400);
        }
    }

    // --- 6. Construir datos de reportes e insertar en lote ---
    $datosReportes = [];
    foreach ($reportes as $reporte) {
        $tipo = $reporte['tipo'];
        $respuesta = $reporte['respuesta'];
        $observacion = $reporte['observacion'] ?? '';

        $datosReportes[] = [
            'id_usuario'  => $idUsuario,
            'id_vehiculo' => null, // Por ahora no se asocia a vehiculo
            'fecha'       => date('Y-m-d'),
            'tipo'        => $tipo,
            'tipo_evento' => $tipoEvento,
            'respuesta'   => ($respuesta === '1' || $respuesta === 'si') ? 'si' : 'no',
            'observacion' => $observacion,
            'ubicacion'   => $ubicacion,
        ];
    }

    $idsInsertados = $model->guardarReportesBatch($datosReportes);
    if ($idsInsertados === false) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Error al guardar los reportes en la base de datos'
        ], 500);
    }

    // --- 7. Procesar archivos adjuntos ---
    $archivosGuardados = 0;
    foreach ($reportes as $index => $reporte) {
        $tipo = $reporte['tipo'];
        $idReporte = $idsInsertados[$index];
        $version = ReporteConductorSSTModel::TIPO_TO_VERSION[$tipo] ?? 1;

        $fileKey = 'archivos_' . $tipo;
        if (!isset($_FILES[$fileKey]) || empty($_FILES[$fileKey]['name'][0])) {
            continue;
        }

        $files = $_FILES[$fileKey];
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name'     => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                'type'     => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error'    => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size'     => is_array($files['size']) ? $files['size'][$i] : $files['size'],
            ];

            // Validar error de subida
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Validar tamano maximo
            if ($file['size'] > ReporteConductorSSTModel::MAX_FILE_SIZE) {
                continue;
            }

            // Validar tipo MIME con finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, ReporteConductorSSTModel::ALLOWED_MIME_TYPES, true)) {
                continue;
            }

            // Guardar archivo
            $idArchivo = $model->guardarArchivo($file, $idReporte, $version);
            if ($idArchivo !== false) {
                $archivosGuardados++;
            }
        }
    }

    // --- 8. Respuesta exitosa ---
    ErrorHandler::sendJsonResponse([
        'success'           => true,
        'message'           => 'Reportes guardados exitosamente',
        'ids'               => $idsInsertados,
        'archivos_guardados' => $archivosGuardados,
    ]);
}

/**
 * Verifica si el usuario tiene pendiente el reporte semanal
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function verificarSemana($model)
{
    $idUsuario = (int) $_SESSION['usuario_id'];
    $pendiente = $model->debeMostrarFormularioSemanal($idUsuario);

    // Obtener la fecha del ultimo reporte para informacion
    $ultimoReporte = $model->obtenerUltimoReporteSemanal($idUsuario);
    $ultimaFecha = $ultimoReporte ? $ultimoReporte['fecha'] : null;

    ErrorHandler::sendJsonResponse([
        'success'      => true,
        'pendiente'    => $pendiente,
        'ultima_fecha' => $ultimaFecha,
    ]);
}

/**
 * Consulta reportes SST con filtros opcionales
 *
 * Endpoint para consultas desde Seguimiento Vehicular y panel administrativo.
 * Acepta parametros GET: id_vehiculo, id_usuario, fecha_desde, fecha_hasta, tipo, estado
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function consultar($model)
{
    $filtros = [];

    // Construir filtros desde parametros GET
    if (!empty($_GET['id_vehiculo'])) {
        $filtros['id_vehiculo'] = (int) $_GET['id_vehiculo'];
    }
    if (!empty($_GET['id_usuario'])) {
        $filtros['id_usuario'] = (int) $_GET['id_usuario'];
    }
    if (!empty($_GET['fecha_desde'])) {
        $filtros['fecha_desde'] = $_GET['fecha_desde'];
    }
    if (!empty($_GET['fecha_hasta'])) {
        $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
    }
    if (!empty($_GET['tipo'])) {
        $filtros['tipo'] = $_GET['tipo'];
    }
    if (!empty($_GET['estado'])) {
        $filtros['estado'] = $_GET['estado'];
    }

    // Obtener reportes
    $reportes = $model->obtenerReportes($filtros);

    // Enriquecer cada reporte con sus archivos asociados
    foreach ($reportes as &$reporte) {
        $reporte['archivos'] = $model->obtenerArchivos($reporte['id']);
    }
    unset($reporte);

    ErrorHandler::sendJsonResponse([
        'success' => true,
        'data'    => $reportes,
        'total'   => count($reportes),
    ]);
}

/**
 * Obtiene el detalle de un reporte SST por su ID
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function detalle($model)
{
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'ID de reporte invalido'
        ], 400);
    }

    $reporte = $model->obtenerReportePorId($id);

    if ($reporte === null) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Reporte no encontrado'
        ], 404);
    }

    // Enriquecer con archivos asociados
    $reporte['archivos'] = $model->obtenerArchivos($reporte['id']);

    ErrorHandler::sendJsonResponse([
        'success' => true,
        'data'    => $reporte,
    ]);
}

/**
 * =============================================================================
 * INTEGRACIÓN PENDIENTE — Flujo semanal forzado (PreoperacionalController):
 *
 * Para forzar el Reporte antes del Preoperacional, agregar en
 * PreoperacionalController::loadView(), después de resolver $idUsuario:
 *
 *   $reporteModel = new ReporteConductorSSTModel();
 *   if ($esConductor && $reporteModel->debeMostrarFormularioSemanal($idUsuario)) {
 *       $redirectUrl = "ReporteConductorSSTController?redirect=preoperacional";
 *       header("Location: $redirectUrl");
 *       exit;
 *   }
 *
 * Estado actual: NO IMPLEMENTADO — se requiere conexión manual.
 * =============================================================================
 *
 * INTEGRACIÓN PENDIENTE — Botón "Reportar en el momento" (Menú principal):
 *
 * Agregar en el menú/dashboard del conductor:
 *   <a href="ReporteConductorSSTController?modo=momento">
 *       🚨 Reportar Incidente SST
 *   </a>
 *
 * Estado actual: NO IMPLEMENTADO — acceso directo por URL por ahora.
 * =============================================================================
 *
 * INTEGRACIÓN PENDIENTE — Consulta desde Seguimiento Vehicular:
 *
 * En seguimiento_vehiculo.js o en el popup de detalle, consumir:
 *   GET ReporteConductorSSTController?ajax=consulta&id_vehiculo=X
 *
 * La respuesta incluye todos los reportes del vehículo con sus archivos.
 * Renderizar como tabla o timeline en el popup de detalle.
 *
 * Estado actual: NO IMPLEMENTADO — endpoints listos, falta UI en SegVehiculo.
 * =============================================================================
 *
 * FUTURO — Subida de video:
 *
 * Para habilitar video, agregar al formulario HTML:
 *   <input type="file" accept="video/*" capture="environment" multiple>
 *   <label>🎥 Grabar video de evidencia</label>
 *
 * Y ajustar la validación de tipos MIME en PHP para aceptar video/mp4, video/webm.
 * La tabla documentos ya soporta cualquier tipo de archivo (doc_ruta es VARCHAR).
 *
 * Estado actual: NO IMPLEMENTADO.
 * =============================================================================
 */
