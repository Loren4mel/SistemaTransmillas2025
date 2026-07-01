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

    // Obtener info del conductor (cedula, placa, vehiculo asignado)
    $infoConductor = $model->obtenerInfoConductor($idUsuario);
    $cedula = $infoConductor['usu_identificacion'] ?? '—';
    $placa = $infoConductor['veh_placa'] ?? 'Sin vehículo asignado';
    $idVehiculo = $infoConductor['usu_vehiculo'] ?? null;

    // Calcular la semana actual
    $semana = $model->calcularSemana(date('Y-m-d'));

    // Determinar que tipos mostrar
    if ($modo === 'momento' && $tipoFiltro !== null && in_array($tipoFiltro, ReporteConductorSSTModel::TIPOS_VALIDOS, true)) {
        $tiposAMostrar = [$tipoFiltro];
    } else {
        $tiposAMostrar = ReporteConductorSSTModel::TIPOS_VALIDOS;
    }

    // Cargar preguntas personalizables (categorias y campos) para cada tipo
    $preguntasPorTipo = [];
    foreach ($tiposAMostrar as $tipo) {
        $preguntasPorTipo[$tipo] = $model->obtenerCategoriasYCampos($tipo);
    }

    // Verificar si debe mostrarse el formulario semanal
    $mostrarFormularioSemanal = ($modo === 'semanal') ? $model->debeMostrarFormularioSemanal($idUsuario) : false;

    // Calcular ruta base de la aplicación (mismo patrón que PreoperacionalController)
    $appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));

    // Pasar labels de gravedad completa para mostrar descripciones al conductor
    $gravedadLabels = ReporteConductorSSTModel::GRAVEDAD_LABELS;

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

            case 'validar':
                validarReporte($model);
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

            case 'resumen_semanal':
                resumenSemanal($model);
                break;

            case 'detalle_vista':
                detalleVista($model);
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

    // Obtener id_vehiculo del conductor para asociarlo al reporte
    $infoConductor = $model->obtenerInfoConductor($idUsuario);
    $idVehiculo = $infoConductor['usu_vehiculo'] ?? null;

    // Validar que el vehiculo exista realmente (evitar FK constraint violation)
    if ($idVehiculo !== null) {
        $vehiculoExiste = $model->verificarVehiculoExiste($idVehiculo);
        if (!$vehiculoExiste) {
            $idVehiculo = null;
            error_log("ReporteConductorSSTController: vehiculo $idVehiculo no existe, usando null");
        }
    }

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

        // Si la respuesta es "si" (hubo incidente), validar todos los campos requeridos
        if ($respuesta == 1 || $respuesta === 'si' || $respuesta === '1') {
            if (empty(trim($observacion))) {
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => "La observacion es requerida cuando se reporta un $tipo (indice $index)"
                ], 400);
            }

            // Validar gravedad requerida cuando respuesta es "si"
            $gravedad = $reporte['gravedad'] ?? null;
            if ($gravedad !== null) {
                $gravedad = (int) $gravedad;
                if ($tipo === 'accidente') {
                    if (!in_array($gravedad, ReporteConductorSSTModel::GRAVEDAD_ACCIDENTE, true)) {
                        ErrorHandler::sendJsonResponse([
                            'success' => false,
                            'message' => "La gravedad para $tipo debe ser 1 (Baja) o 2 (Alta) (indice $index)"
                        ], 400);
                    }
                } elseif ($tipo === 'comparendo') {
                    if (!in_array($gravedad, ReporteConductorSSTModel::GRAVEDAD_COMPARENDO, true)) {
                        ErrorHandler::sendJsonResponse([
                            'success' => false,
                            'message' => "La gravedad para $tipo debe ser un valor entre 1 y 3 (indice $index)"
                        ], 400);
                    }
                }
            } else {
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => "La gravedad es requerida cuando se reporta un $tipo (indice $index)"
                ], 400);
            }

            // Validar minimo 4 archivos adjuntos
            $fileKey = 'archivos_' . $tipo;
            if (!isset($_FILES[$fileKey]) || !isset($_FILES[$fileKey]['name']) || count(array_filter($_FILES[$fileKey]['name'])) < 4) {
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => "Se requieren minimo 4 archivos de evidencia para '$tipo' (indice $index)"
                ], 400);
            }
        }
    }

    // --- 5b. Validar firma del conductor ---
    $firmaBase64 = $_POST['firma_sst'] ?? '';
    if (empty($firmaBase64)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'La firma del conductor es requerida.'
        ], 400);
    }
    if (strpos($firmaBase64, 'data:image') !== 0) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Formato de firma inválido.'
        ], 400);
    }

    // --- 6. Construir datos de reportes e insertar en lote ---
    $datosReportes = [];
    foreach ($reportes as $reporte) {
        $tipo = $reporte['tipo'];
        $respuesta = $reporte['respuesta'];
        $observacion = $reporte['observacion'] ?? '';
        $gravedad = null;
        if (($respuesta == 1 || $respuesta === 'si' || $respuesta === '1') && isset($reporte['gravedad'])) {
            $gravedad = (int) $reporte['gravedad'];
        }

        $datosReportes[] = [
            'id_usuario'  => $idUsuario,
            'id_vehiculo' => $idVehiculo,
            'fecha'       => date('Y-m-d'),
            'tipo'        => $tipo,
            'tipo_evento' => $tipoEvento,
            'respuesta'   => ($respuesta == 1 || $respuesta === 'si' || $respuesta === '1') ? 1 : 0,
            'observacion' => $observacion,
            'ubicacion'   => $ubicacion,
            'gravedad'    => $gravedad,
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

    // --- 8. Procesar respuestas personalizables ---
    $respuestasRaw = $_POST['respuestas'] ?? '[]';
    $respuestasData = json_decode($respuestasRaw, true);
    if (is_array($respuestasData) && !empty($respuestasData)) {
        foreach ($idsInsertados as $index => $idReporte) {
            $tipo = $reportes[$index]['tipo'] ?? '';
            $respuestasTipo = $respuestasData[$tipo] ?? [];
            if (!empty($respuestasTipo)) {
                $model->guardarRespuestas($idReporte, $respuestasTipo);
            }
        }
    }

    // --- 8b. Guardar firma del conductor ---
    $firmaGuardada = false;
    $primerIdReporte = $idsInsertados[0] ?? null;
    if ($primerIdReporte !== null) {
        $idFirma = $model->guardarFirma($firmaBase64, $primerIdReporte, $idUsuario);
        if ($idFirma !== false) {
            $firmaGuardada = true;
        }
    }

    // --- 9. Respuesta exitosa ---
    ErrorHandler::sendJsonResponse([
        'success'           => true,
        'message'           => 'Reportes guardados exitosamente',
        'ids'               => $idsInsertados,
        'archivos_guardados' => $archivosGuardados,
        'firma_guardada'    => $firmaGuardada,
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
 * Obtiene el resumen semanal de cuestionarios SST por conductor
 *
 * Endpoint para consulta desde Seguimiento Vehicular.
 * Devuelve para cada conductor activo si completo los cuestionarios
 * de accidente y comparendo en la semana indicada.
 *
 * Parametros GET:
 *   - fecha_inicio (string Y-m-d): Inicio de la semana
 *   - fecha_fin (string Y-m-d): Fin de la semana
 *   - id_conductor (int|null, opcional): Filtrar por conductor
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function resumenSemanal($model)
{
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('monday this week'));
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d', strtotime('sunday this week'));
    $idConductor = !empty($_GET['id_conductor']) ? (int) $_GET['id_conductor'] : null;

    // Validar formato de fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Formato de fecha invalido. Use Y-m-d.'
        ], 400);
    }

    $resumen = $model->obtenerResumenSemanal($fechaInicio, $fechaFin, $idConductor);

    // Calcular estadisticas
    $total = count($resumen);
    $alDia = count(array_filter($resumen, function ($r) {
        return $r['accidente_completado'] && $r['comparendo_completado'];
    }));
    $incompletos = count(array_filter($resumen, function ($r) {
        return $r['accidente_completado'] || $r['comparendo_completado'];
    })) - $alDia;
    $pendientes = $total - $alDia - $incompletos;

    ErrorHandler::sendJsonResponse([
        'success'    => true,
        'data'       => $resumen,
        'total'      => $total,
        'estadisticas' => [
            'al_dia'      => $alDia,
            'incompletos' => $incompletos,
            'pendientes'  => $pendientes,
        ],
        'semana' => [
            'inicio' => $fechaInicio,
            'fin'    => $fechaFin,
        ],
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

    // Enriquecer con archivos asociados y firma
    $reporte['archivos'] = $model->obtenerArchivos($reporte['id']);
    $reporte['firma'] = $model->obtenerFirmaReporte($reporte['id']);

    ErrorHandler::sendJsonResponse([
        'success' => true,
        'data'    => $reporte,
    ]);
}

/**
 * Obtiene el detalle completo de un reporte para la vista de revisión
 *
 * Endpoint para el popup de detalle desde Seguimiento Vehicular.
 * Retorna todos los datos necesarios para mostrar el detalle y,
 * si el usuario es admin, habilitar los controles de validación.
 *
 * Parámetros GET:
 *   - id (int): ID del reporte
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function detalleVista($model)
{
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'ID de reporte inválido'
        ], 400);
    }

    $reporte = $model->obtenerReporteCompleto($id);

    if ($reporte === null) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Reporte no encontrado'
        ], 404);
    }

    // Incluir historial de seguimiento, respuestas personalizables, archivos y firma
    $reporte['seguimiento'] = $model->obtenerSeguimiento($id);
    $reporte['respuestas'] = $model->obtenerRespuestas($id);
    $reporte['archivos'] = $model->obtenerArchivos($id);
    $reporte['firma'] = $model->obtenerFirmaReporte($id);

    // Derivar campos del validador desde el ultimo registro de seguimiento
    if (!empty($reporte['seguimiento'])) {
        $ultimoSeguimiento = $reporte['seguimiento'][count($reporte['seguimiento']) - 1];
        $reporte['estado'] = $ultimoSeguimiento['estado_nuevo'];
        $reporte['validador_nombre'] = $ultimoSeguimiento['validador_nombre'];
        $reporte['comentario_validador'] = $ultimoSeguimiento['comentario'];
        $reporte['gravedad_validacion'] = $ultimoSeguimiento['gravedad_validacion'];
        $reporte['fecha_validacion'] = $ultimoSeguimiento['creado_en'];
    }

    // Determinar si el usuario actual puede validar
    $rolActual = (int) ($_SESSION['usuario_rol'] ?? 0);
    $puedeValidar = in_array($rolActual, ReporteConductorSSTModel::ROLES_VALIDADOR, true);

    ErrorHandler::sendJsonResponse([
        'success'       => true,
        'data'          => $reporte,
        'puede_validar' => $puedeValidar,
        'usuario_actual' => [
            'id'     => (int) $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'] ?? '',
        ],
    ]);
}

/**
 * Valida un reporte SST — cambia estado, registra comentario del validador y gravedad de validación
 *
 * Solo accesible por roles administradores (1, 12).
 * El comentario se guarda con prefijo automático: "NombreValidador - comentario"
 * La gravedad de validación usa la escala original (1-4 accidente, 1-3 comparendo)
 *
 * Parámetros POST:
 *   - id                   (int):    ID del reporte
 *   - estado               (string): Nuevo estado ('pendiente', 'en_proceso', 'finalizado')
 *   - comentario           (string): Comentario del validador (se prefija automáticamente)
 *   - gravedad_validacion  (int):    Nivel de gravedad según validador (escala original)
 *
 * @param ReporteConductorSSTModel $model Instancia del modelo
 */
function validarReporte($model)
{
    $rolActual = (int) ($_SESSION['usuario_rol'] ?? 0);
    if (!in_array($rolActual, ReporteConductorSSTModel::ROLES_VALIDADOR, true)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Acceso denegado. Solo administradores pueden validar reportes.'
        ], 403);
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $estado = $_POST['estado'] ?? '';
    $comentarioRaw = trim($_POST['comentario'] ?? '');
    $gravedadValidacionRaw = $_POST['gravedad_validacion'] ?? null;

    if ($id <= 0) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'ID de reporte inválido'
        ], 400);
    }

    if (!in_array($estado, ReporteConductorSSTModel::ESTADOS_VALIDOS, true)) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Estado no válido. Use: ' . implode(', ', ReporteConductorSSTModel::ESTADOS_VALIDOS)
        ], 400);
    }

    // Verificar que el reporte existe
    $reporte = $model->obtenerReportePorId($id);
    if ($reporte === null) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Reporte no encontrado'
        ], 404);
    }

    // Validar gravedad_validacion si se envió (usa escala original: 1-4 acc, 1-3 comp)
    $gravedadValidacion = null;
    if ($gravedadValidacionRaw !== null && $gravedadValidacionRaw !== '') {
        $gravedadValidacion = (int) $gravedadValidacionRaw;
        $tipoReporte = $reporte['tipo'] ?? '';
        if ($tipoReporte === 'accidente') {
            if (!in_array($gravedadValidacion, ReporteConductorSSTModel::GRAVEDAD_VAL_ACCIDENTE, true)) {
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => 'Gravedad de validación inválida para accidente. Use: ' . implode(', ', ReporteConductorSSTModel::GRAVEDAD_VAL_ACCIDENTE)
                ], 400);
            }
        } elseif ($tipoReporte === 'comparendo') {
            if (!in_array($gravedadValidacion, ReporteConductorSSTModel::GRAVEDAD_VAL_COMPARENDO, true)) {
                ErrorHandler::sendJsonResponse([
                    'success' => false,
                    'message' => 'Gravedad de validación inválida para comparendo. Use: ' . implode(', ', ReporteConductorSSTModel::GRAVEDAD_VAL_COMPARENDO)
                ], 400);
            }
        }
    }

    // Auto-prefijar comentario con nombre del validador (patrón preoperacional)
    $nombreValidador = $_SESSION['usuario_nombre'] ?? 'Sistema';
    $comentarioFinal = $nombreValidador;
    if (!empty($comentarioRaw)) {
        $comentarioFinal .= " - " . $comentarioRaw;
    }

    $idValidador = (int) $_SESSION['usuario_id'];

    $ok = $model->cambiarEstado($id, $estado, $idValidador, $comentarioFinal, $gravedadValidacion);

    if (!$ok) {
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Error al actualizar el reporte en la base de datos'
        ], 500);
    }

    ErrorHandler::sendJsonResponse([
        'success'              => true,
        'message'              => 'Reporte validado correctamente',
        'estado'               => $estado,
        'comentario'           => $comentarioFinal,
        'fecha_validacion'     => date('Y-m-d H:i:s'),
        'validador_nombre'     => $nombreValidador,
        'gravedad_validacion'  => $gravedadValidacion,
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
 * INTEGRACIÓN — Consulta desde Seguimiento Vehicular:
 *
 * ✅ IMPLEMENTADO — El endpoint ajax=consulta se consume desde el popup
 *    consulta_sst de SeguimientoVehiculo (tipo=consulta_sst en form_popup).
 *    También disponible ajax=resumen_semanal para el resumen semanal.
 *
 * Estado actual: COMPLETADO.
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
