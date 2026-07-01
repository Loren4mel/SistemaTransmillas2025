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

            case 'seleccionar_vehiculo_diario':
                $response = handleSeleccionarVehiculoDiario($service);
                break;

            case 'obtener_datos_vehiculo':
                $idV = (int) ($_POST['idvehiculo'] ?? 0);
                $dataV = $idV > 0 ? $service->obtenerDatosVehiculoCompleto($idV) : null;
                $response = ['success' => $dataV !== null, 'data' => $dataV];
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
 * Busca vehículos disponibles sin conductor asignado.
 *
 * Aplica las reglas de disponibilidad:
 * 1. Solo vehículos de la empresa (veh_propiedad = 'empresa');
 *    vacío o 'propio' = personal (excluidos)
 * 2. Sin PREOPERACIONAL hoy de otro usuario
 * 3. Solo vehículos activos (veh_estado = 1)
 */
function handleBuscarVehiculosDisponibles($service)
{
    $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
    $vehiculos = $service->obtenerVehiculosDisponibles($idUsuario);
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
 * Procesa la selección diaria de un vehículo distinto al permanente.
 * El usuario sube fotos del estado del vehículo y se registra en
 * seguimiento_vehiculo como ASIGNACION_VEHICULO.
 * usu_vehiculo NO se modifica.
 */
function handleSeleccionarVehiculoDiario($service)
{
    $idVehiculo = (int) ($_POST['idvehiculo'] ?? 0);
    $idUsuario = (int) ($_POST['id_usuario'] ?? $_SESSION['usuario_id'] ?? 0);

    if ($idVehiculo <= 0) {
        return ['success' => false, 'message' => 'Debe seleccionar un vehículo.'];
    }

    return $service->procesarSeleccionVehiculoDiario($idUsuario, $idVehiculo, $_FILES);
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
    $esValidacion = ($preoperacional == 'validarpreoperacional' || $param5 == 'valida' || $param5 == 'vista');
    $esSoloLectura = ($param5 == 'vista');

    // Validación de preoperacional: solo administradores (roles 1, 12) pueden
    // acceder a la página de validación, alineado con el sistema legacy.
    // Modo vista (solo-lectura): cualquier usuario autenticado puede ver.
    if ($esValidacion && !$esSoloLectura) {
        $rol = $_SESSION['usuario_rol'] ?? 0;
        if (!in_array($rol, [1, 12])) {
            http_response_code(403);
            echo "<h2>Acceso denegado</h2><p>Solo administradores pueden validar registros preoperacionales.</p>";
            exit;
        }
    }

    // Resolver vehículo para hoy (solo fuera de validación/vista):
    // 1. Si hay ASIGNACION_VEHICULO para hoy → usar ese
    // 2. Si usu_vehiculo está disponible → usar ese
    // 3. Si no → null (sin vehículo)
    // NOTA: usar date('Y-m-d') para la asignación diaria, NO $fecha (que puede venir
    // de la URL con una fecha diferente). La ASIGNACION_VEHICULO siempre se crea
    // con CURRENT_TIMESTAMP, por lo que debe buscarse con la fecha real de hoy.
    if (!isset($_GET['idvehiculo']) && !$esValidacion && !$esSoloLectura) {
        $idvehiculo = $service->obtenerVehiculoParaUsuarioHoy($iduser, date('Y-m-d'));
    }

    // Obtener datos del vehículo y usuario
    $datosVehiculo = $service->obtenerDatosVehiculoYUsuario($iduser, $idvehiculo);
    if ($datosVehiculo === null) {
        $datosVehiculo = []; // Array vacío para evitar errores en la vista
    }

    // $tipovehiculo: primario del vehículo asignado; fallback al perfil del usuario.
    // Si el conductor reportó una novedad y quedó sin vehículo, el JOIN vehiculos
    // no devuelve fila, pero el usuario sigue siendo conductor en su perfil.
    $tipovehiculo = strtoupper($datosVehiculo['veh_tipo'] ?? '');
    if (empty($tipovehiculo)) {
        $tipovehiculo = strtoupper($service->obtenerTipoVehiculoUsuario($iduser) ?? '');
    }
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

        // Detectar el formato de la encuesta existente para validación.
        // Solo para registros legacy (no relacionales); los relacionales siempre son 'nuevo'.
        if ($registroExistente && empty($registroExistente['id_version']) && !empty($registroExistente['preencuesta'])) {
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

        // Derivar iduser desde el registro original cuando no viene en la URL.
        // Esto es esencial cuando se accede desde seguimientoVehiculo (historial de estado)
        // donde el enlace solo incluye idpre sin iduser, y el fallback a $_SESSION
        // apuntaría al admin que no tiene vehículo asignado.
        if ($registroExistente && empty($_GET['iduser']) && !empty($registroExistente['preidusuario'])) {
            $iduser = (int) $registroExistente['preidusuario'];
        }

        // Derivar idvehiculo desde el registro original cuando no viene en la URL.
        if ($registroExistente && empty($_GET['idvehiculo']) && !empty($registroExistente['prevehiculo'])) {
            $idvehiculo = (int) $registroExistente['prevehiculo'];
        }

        // Re-consultar datos del vehículo con iduser e idvehiculo corregidos.
        // Necesario cuando se accede desde seguimientoVehiculo sin estos parámetros
        // y fueron derivados del registro original en los bloques anteriores.
        if ($registroExistente && (empty($_GET['iduser']) || empty($_GET['idvehiculo']))) {
            $datosVehiculo = $service->obtenerDatosVehiculoYUsuario($iduser, $idvehiculo);
            if ($datosVehiculo === null) {
                $datosVehiculo = [];
            }

            // Recalcular tipovehiculo desde los datos recién consultados
            $tipovehiculo = strtoupper($datosVehiculo['veh_tipo'] ?? '');
            if (empty($tipovehiculo)) {
                $tipovehiculo = strtoupper($service->obtenerTipoVehiculoUsuario($iduser) ?? '');
            }

            // Recalcular estado de documentos del vehículo
            $estadoDocumentos = !empty($datosVehiculo)
                ? $service->getEstadoDocumentosVehiculo($datosVehiculo)
                : ['alertas' => [], 'expired' => false, 'max_severity' => 0, 'bloquear' => false];
            $alertasVehiculoHtml = $service->generarHtmlAlertasVehiculo($estadoDocumentos);
            $alertaSeveridadVehiculo = $estadoDocumentos['max_severity'] ?? 0;
            $mostrarAlertaVencidos = $estadoDocumentos['expired'] && !$esValidacion;
        }
    }

    // Cross-reference: si pre_kilrecorridos está vacío o es 0, rescatar el valor real
    // desde seguimiento_vehiculo (que preserva el kilometraje original del conductor).
    // Durante la validación, el input disabled no envía datos y pre_kilrecorridos se
    // sobrescribe a 0; el valor real permanece en seguimiento_vehiculo.kilometraje.
    if ($registroExistente && empty($registroExistente['pre_kilrecorridos'])) {
        $db = (new Database())->connect();
        $sqlKm = "SELECT kilometraje, img_kilometraje FROM seguimiento_vehiculo
                  WHERE id_preoperacional = ? AND tipo_evento = 'PREOPERACIONAL' LIMIT 1";
        $stmt = $db->prepare($sqlKm);
        $stmt->bind_param("i", $registroExistente['idpreoperacinal']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['kilometraje'])) {
                $registroExistente['pre_kilrecorridos'] = $row['kilometraje'];
            }
            if (!empty($row['img_kilometraje']) && empty($registroExistente['pre_img_kilo'])) {
                $registroExistente['pre_img_kilo'] = $row['img_kilometraje'];
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
        // IMPORTANTE: usar obtenerTodasRespuestas en vez de obtenerRespuestasVersion.
        // El registro almacena solo un id_version (el de vehículo), pero las respuestas
        // viven en múltiples versiones (vehículo + usuario). Si filtramos por una sola
        // versión, las preguntas personales del usuario no se cargarían.
        $valoresEncuestaRelacional = $service->obtenerTodasRespuestas(
            $registroExistente['idpreoperacinal']
        );
        $idVersionActiva = $registroExistente['id_version'];
        $esRegistroRelacional = true;
        // Cuando el registro usa esquema relacional, siempre es formato nuevo.
        // detectarFormato() no puede identificar el formato desde preencuesta
        // porque la migración movió las respuestas a preop_respuestas y
        // preencuesta ahora solo almacena metadata (ubicacion, doc IDs).
        $formatoEncuesta = 'nuevo';

        // --- Cargar metadata desde columnas dedicadas (fuente primaria) ---
        // pre_ubicacion y pre_firma existen como columnas en la tabla;
        // son la fuente de verdad para registros del esquema relacional.

        if (!empty($registroExistente['pre_ubicacion'])) {
            $valoresEncuestaRelacional['ubicacion'] = $registroExistente['pre_ubicacion'];
        }
        if (!empty($registroExistente['pre_firma'])) {
            $valoresEncuestaRelacional['firma_documento_id'] = (int) $registroExistente['pre_firma'];
        }

        // Documentos de inspección (v3) y temperatura (v2): consultar tabla documentos.
        $metadatosDocs = $service->obtenerDocumentosPorPreoperacional($registroExistente['idpreoperacinal']);
        foreach ($metadatosDocs as $clave => $valor) {
            if (empty($valoresEncuestaRelacional[$clave])) {
                $valoresEncuestaRelacional[$clave] = $valor;
            }
        }

        // Fallback: si algún campo de metadata no se encontró en columnas dedicadas
        // ni en documentos, intentar rescatar del JSON legacy de preencuesta.
        if (!empty($registroExistente['preencuesta'])) {
            $metadatosLegado = json_decode($registroExistente['preencuesta'], true) ?? [];
            foreach (['ubicacion', 'firma_documento_id', 'inspeccion_documento_id', 'temperatura_documento_id'] as $clave) {
                if (isset($metadatosLegado[$clave]) && empty($valoresEncuestaRelacional[$clave])) {
                    $valoresEncuestaRelacional[$clave] = $metadatosLegado[$clave];
                }
            }
        }
    }

    // Detectar si el preoperacional tuvo cambio voluntario de vehículo (todos los formatos)
    $datosCambioVehiculo = null;
    $fotosCambioVehiculo = [];
    if ($registroExistente && !empty($registroExistente['preencuesta'])) {
        $metadatosLegado = json_decode($registroExistente['preencuesta'], true) ?? [];
        $detalles = $metadatosLegado['detalles'] ?? [];
        if (($metadatosLegado['tipo_dato'] ?? '') === 'cambio_voluntario' && !empty($detalles)) {
            // Resolver placas de los vehículos involucrados en el cambio
            $idOrig = $detalles['vehiculo_original'] ?? 0;
            $idSel = $detalles['vehiculo_seleccionado'] ?? 0;
            $placaOrig = '';
            $placaSel = '';
            if ($idOrig > 0) {
                $vModel = new VehiculosModel();
                $vData = $vModel->obtenerVehiculoPorId($idOrig);
                $placaOrig = $vData['veh_placa'] ?? '';
            }
            if ($idSel > 0) {
                $vModel = new VehiculosModel();
                $vData = $vModel->obtenerVehiculoPorId($idSel);
                $placaSel = $vData['veh_placa'] ?? '';
            }
            $datosCambioVehiculo = [
                'vehiculo_original' => ($placaOrig ? $placaOrig . ' ' : '') . '(id:' . $idOrig . ')',
                'vehiculo_seleccionado' => ($placaSel ? $placaSel . ' ' : '') . '(id:' . $idSel . ')',
                'descripcion' => $detalles['descripcion'] ?? '',
                'observaciones' => $detalles['observaciones_cambio'] ?? '',
            ];
            // Obtener fotos del cambio y convertir rutas absolutas a URLs
            if (!empty($registroExistente['idpreoperacinal'])) {
                $fotosCambioVehiculo = $service->obtenerFotosCambioVehiculo($registroExistente['idpreoperacinal']);
                // Convertir rutas absolutas del servidor a URLs accesibles desde el navegador
                $diskProjectRoot = realpath(dirname(__DIR__, 2)); // ej: C:\xampp\htdocs\...\SistemaTransmillas2025
                $urlProjectRoot = dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))); // ej: /SistemaTransmillas2025
                foreach ($fotosCambioVehiculo as &$foto) {
                    $rutaRelativa = str_replace($diskProjectRoot, '', $foto['doc_ruta']);
                    $foto['doc_url'] = $urlProjectRoot . str_replace('\\', '/', $rutaRelativa);
                }
                unset($foto);
            }
        }
    }

    // Para nuevos registros, siempre resolver la versión activa
    if (!$idVersionActiva) {
        $versionesActivas = $service->resolverVersionesAplicables($tipovehiculo, $nivel_acceso);
        $versionPrincipal = $versionesActivas['version_vehiculo'] ?? $versionesActivas['version_usuario'] ?? null;
        $idVersionActiva = $versionPrincipal ? $versionPrincipal['id_version'] : null;
    }

    // ==================== VERIFICACIÓN DE NOVEDAD VEHICULAR ====================
    require_once __DIR__ . '/../helpers/PreoperacionalHelpers/PreoperacionalNovedadHelper.php';
    $novedadHelper = new PreoperacionalNovedadHelper($service);

    // Siempre cargar vehículos disponibles (incluso sin vehículo asignado)
    $vehiculosDisponibles = $service->obtenerVehiculosDisponibles($iduser);
    $novedadVehiculo = null;

    $tieneVehiculoAsignado = !empty($datosVehiculo) && isset($datosVehiculo['idvehiculos']) && $datosVehiculo['idvehiculos'] > 0;

    if ($tieneVehiculoAsignado) {
        $novedadVehiculo = $novedadHelper->verificarNovedadVehiculo($datosVehiculo['idvehiculos']);
    }

    // Inicializar valores seguros para JS cuando no hay vehículo
    if ($novedadVehiculo === null) {
        $novedadVehiculo = [
            'tieneNovedad' => false,
            'esNovedadReportada' => false,
            'estado_general' => 'OPTIMO',
            'observaciones' => '',
            'ultimoSeguimiento' => null
        ];
    }

    // Determinar qué secciones mostrar basadas en el rol y tipo de vehículo
    require_once __DIR__ . '/../helpers/PreoperacionalHelpers/Views/PreoperacionalNuevaEncuestaViewHelper.php';

    // En modo validación/vista, las secciones se determinan a partir del registro
    // ORIGINAL (el conductor que llenó el preop), no del rol de quien está viendo.
    if ($esValidacion && $registroExistente && !empty($registroExistente['preidusuario'])) {
        $rolParaSecciones = $service->obtenerRolUsuario((int) $registroExistente['preidusuario']) ?? 0;
        $tipoVehiculoParaSecciones = strtoupper($registroExistente['pretipovehiculo'] ?? '');
        // En validación/vista, el vehículo SIEMPRE estuvo asignado al momento del registro
        $tieneVehiculoParaSecciones = !empty($tipoVehiculoParaSecciones);
    } else {
        $rolParaSecciones = $nivel_acceso;
        $tipoVehiculoParaSecciones = $tipovehiculo;
        $tieneVehiculoParaSecciones = $tieneVehiculoAsignado;
    }

    // Verificar si el rol del usuario ORIGINAL está autorizado para operaciones vehiculares
    $esRolVehicularAutorizado = PreoperacionalNuevaEncuestaViewHelper::esRolVehicularAutorizado($rolParaSecciones);

    // Determinar si es conductor (CARRO) — solo si el rol está autorizado
    $esConductor = $esRolVehicularAutorizado && PreoperacionalNuevaEncuestaViewHelper::esConductor($tipoVehiculoParaSecciones);

    // NUEVO FORMATO: Solo secciones basadas en rol (SIN COVID, SIN FATIGA)
    // Cuando es conductor o vehículo propio, NO se muestran preguntas administrativas
    $esVehiculoPropio = $esRolVehicularAutorizado && PreoperacionalNuevaEncuestaViewHelper::tieneVehiculoPropio($tipoVehiculoParaSecciones);
    $mostrarSecciones = [
        'administrativo' => !$esConductor && !$esVehiculoPropio && PreoperacionalNuevaEncuestaViewHelper::esPersonalAdministrativo($rolParaSecciones),
        'conductor' => $esConductor,
        'vehiculo_propio' => $esVehiculoPropio,
        'auxiliar_carga' => PreoperacionalNuevaEncuestaViewHelper::esAuxiliarCarga($rolParaSecciones),
        // Las secciones de inspección del vehículo solo se muestran si el
        // usuario ORIGINAL tenía un vehículo asignado al momento del registro.
        'preoperacional_vehiculo' => ($tipoVehiculoParaSecciones === 'CARRO') && $tieneVehiculoParaSecciones && $esRolVehicularAutorizado,
        'preoperacional_moto' => ($tipoVehiculoParaSecciones === 'MOTO') && $tieneVehiculoParaSecciones && $esRolVehicularAutorizado
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

    // ==================== SELECCIÓN DIARIA DE VEHÍCULO ====================
    // [ELIMINADO] entregavehiculo: la selección del vehículo del día se resuelve
    // al inicio de loadView() vía obtenerVehiculoParaUsuarioHoy().
    $entregasPendientes = ['final' => null, 'inicial' => null, 'seguimiento' => null];

    // Limpiar buffer y cargar la vista
    // Calcular ruta base de la aplicación desde el servidor
    // Ej: /SistemaTransmillas2025/nueva_plataforma
    $appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    ob_clean();
    include __DIR__ . '/../view/Preoperacional/index.php';
}
?>