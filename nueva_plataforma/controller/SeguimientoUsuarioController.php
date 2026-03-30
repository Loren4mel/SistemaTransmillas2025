<?php
// ==================== INICIO: BUFFER DE SALIDA ====================
ob_start(); // Inicia buffer al principio
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR);

// ==================== MANEJADOR DE ERRORES PERSONALIZADO ====================
$captured_errors = []; // Almacenará los errores (warnings, notices)

set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$captured_errors) {
    $captured_errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    // No interrumpimos la ejecución, solo registramos
    return true;
});

// ==================== MANEJADOR DE EXCEPCIONES GLOBAL ====================
set_exception_handler(function ($exception) {
    error_log("Excepcion no capturada: " . $exception->getMessage());
    // Si la petición es AJAX, devolver JSON de error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        sendJsonResponse(['error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()], 500);
    } else {
        // Mostrar página de error con detalles (solo desarrollo)
        echo "<h1>Error</h1><pre>" . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "</pre>";
    }
    exit;
});

// ==================== MANEJADOR DE ERRORES FATALES ====================
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Error fatal: " . $error['message']);
        // Si es AJAX, devolver JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            ob_clean(); // Limpiar buffer antes de enviar JSON
            header('Content-Type: application/json');
            echo json_encode(['fatal_error' => $error['message'], 'file' => $error['file'], 'line' => $error['line']]);
        } else {
            echo "<h1>Error fatal</h1><pre>" . $error['message'] . " en " . $error['file'] . ":" . $error['line'] . "</pre>";
        }
        exit;
    }
});

// ==================== FUNCIÓN AUXILIAR PARA ENVIAR JSON ====================
function sendJsonResponse($data, $statusCode = 200)
{
    // Limpiar cualquier salida previa (incluyendo notices que pudieran haber quedado)
    if (ob_get_level())
        ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ==================== INCLUIR AUTENTICACIÓN (después de manejadores y buffer) ====================
require("../../login_autentica.php"); // Inicia sesión y define $id_usuario, $nivel_acceso, etc.

// ==================== INCLUIR MODELO ====================
require_once "../model/SeguimientoUsuarioModel.php";

$modelo = new SeguimientoUsuarioModel();

// --------------------------------------------------------------------
// PETICIONES AJAX PARA DATATABLE (server-side)
// --------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    try {
        // Validar y sanitizar entradas
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $search = $_POST['search']['value'] ?? '';
        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = $_POST['order'][0]['dir'] ?? 'ASC';
        $columns = $_POST['columns'] ?? [];
        $orderColumn = $columns[$orderColumnIndex]['data'] ?? 'usu_nombre';

        // Filtros personalizados
        $filtros = [
            'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
            'fecha_fin' => $_POST['fecha_fin'] ?? date('Y-m-d'),
            'sede' => $_POST['sede'] ?? '',
            'operario' => $_POST['operario'] ?? '',
            'motivo' => $_POST['motivo'] ?? '',
            'tipo_contrato' => $_POST['tipo_contrato'] ?? ''
        ];

        // Obtener datos del modelo
        $result = $modelo->getRegistrosDataTable($start, $length, $filtros, $search, $orderColumn, $orderDir);
        $totalFiltrados = $modelo->getTotalFiltrados($filtros, $search);
        // Calcular total sin filtrar (usuarios * días)
        $fecha_inicio = new DateTime($filtros['fecha_inicio']);
        $fecha_fin = new DateTime($filtros['fecha_fin']);
        $dias = $fecha_inicio->diff($fecha_fin)->days + 1;
        $totalUsuarios = $modelo->getTotalRegistros($filtros, $search);
        $totalRegistros = $totalUsuarios * $dias;

        $response = [
            "draw" => $draw,
            "recordsTotal" => $totalRegistros,
            "recordsFiltered" => $totalFiltrados,
            "data" => $result
        ];

        // Incluir errores capturados en desarrollo (para depuración)
        global $captured_errors;
        if (!empty($captured_errors)) {
            $response['debug_errors'] = $captured_errors;
        }

        sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("Error en AJAX seguimiento: " . $e->getMessage());
        sendJsonResponse([
            "draw" => $draw ?? 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => $e->getMessage(),
            "trace" => $e->getTraceAsString()
        ], 500);
    }
}

// --------------------------------------------------------------------
// PETICIONES AJAX PARA ACCIONES POST (guardar, etc.)
// --------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $accion = $_POST['accion'];
        $response = ['success' => false, 'message' => 'Acción no válida'];

        switch ($accion) {
            case 'guardar_ingreso_popup':
                $idSeguimiento = intval($_POST['id_seguimiento'] ?? 0);
                $idUsuario = intval($_POST['operario'] ?? 0);
                $fecha = $_POST['fecha'] ?? date('Y-m-d');
                $motivo = $_POST['motivo'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $zona = intval($_POST['zona'] ?? 0);
                $prueba = $_POST['prueba'] ?? 'No aplica';
                $imagen = $_FILES['imagen'] ?? null;

                $data = [
                    'operario' => $idUsuario,
                    'fecha' => $fecha,
                    'motivo' => $motivo,
                    'descripcion' => $descripcion,
                    'zona' => $zona,
                    'prueba' => $prueba
                ];

                if ($idSeguimiento > 0) {
                    $ok = $modelo->actualizarIngreso($idSeguimiento, $data, $imagen, $_SESSION['usuario_id']);
                } else {
                    $ok = $modelo->insertarIngreso($data, $imagen, $_SESSION['usuario_id']);
                }

                if ($ok) {
                    $response = ['success' => true, 'message' => 'Ingreso guardado correctamente'];
                } else {
                    $response = ['message' => 'Error al guardar en la base de datos'];
                }
                break;

            case 'guardar_zona':
                $id = intval($_POST['id']);
                $zona = intval($_POST['zona']);
                $ok = $modelo->actualizarZona($id, $zona);
                $response = ['success' => $ok, 'message' => $ok ? 'Zona actualizada' : 'Error'];
                break;

            case 'guardar_hora_almuerzo':
                $id = intval($_POST['id']);
                $hora = $_POST['hora'];
                $ok = $modelo->actualizarHoraAlmuerzo($id, $hora);
                $response = ['success' => $ok, 'message' => $ok ? 'Hora guardada' : 'Error'];
                break;

            case 'guardar_retorno_almuerzo':
                $id = intval($_POST['id']);
                $hora = $_POST['hora'];
                $ok = $modelo->actualizarRetornoAlmuerzo($id, $hora);
                $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
                break;

            case 'guardar_retorno_oficina':
                $id = intval($_POST['id']);
                $hora = $_POST['hora'];
                $ok = $modelo->actualizarRetornoOficina($id, $hora);
                $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
                break;

            case 'guardar_companero':
                $id = intval($_POST['id']);
                $companero = intval($_POST['companero']);
                $ok = $modelo->actualizarCompanero($id, $companero);
                $response = ['success' => $ok, 'message' => $ok ? 'Compañero actualizado' : 'Error'];
                break;

            case 'guardar_festivos':
                $fecha = $_POST['fecha'];
                $sede = intval($_POST['sede'] ?? 0);
                $ok = $modelo->insertarFestivos($fecha, $sede, $_SESSION['usuario_id']);
                $response = [
                    'success' => $ok,
                    'message' => $ok ? 'Días festivos agregados' : 'Error al agregar festivos'
                ];
                break;

            case 'guardar_vacaciones':
                $data = [
                    'operario' => intval($_POST['operario']),
                    'fecha_ini' => $_POST['fecha_ini'],
                    'fecha_fin' => $_POST['fecha_fin']
                ];
                $ok = $modelo->insertarVacaciones($data, $_SESSION['usuario_id']);
                $response = [
                    'success' => $ok,
                    'message' => $ok ? 'Vacaciones registradas' : 'Error al registrar vacaciones'
                ];
                break;

            case 'guardar_licencia':
                $data = [
                    'operario' => intval($_POST['operario']),
                    'fecha_ini' => $_POST['fecha_ini'],
                    'fecha_fin' => $_POST['fecha_fin'],
                    'motivo' => $_POST['motivo'],
                    'descripcion' => $_POST['descripcion']
                ];
                $ok = $modelo->insertarLicencia($data, $_SESSION['usuario_id']);
                $response = [
                    'success' => $ok,
                    'message' => $ok ? 'Licencia registrada' : 'Error al registrar licencia'
                ];
                break;

            case 'guardar_ingreso':
                $data = [
                    'operario' => intval($_POST['operario']),
                    'sede' => intval($_POST['sede']),
                    'fecha' => $_POST['fecha'],
                    'motivo' => $_POST['motivo'],
                    'descripcion' => $_POST['descripcion'],
                    'zona' => intval($_POST['zona']),
                    'prueba' => $_POST['prueba']
                ];
                $imagen = $_FILES['imagen'] ?? null;
                $ok = $modelo->insertarIngreso($data, $imagen, $_SESSION['usuario_id']);
                $response = [
                    'success' => $ok,
                    'message' => $ok ? 'Ingreso registrado correctamente' : 'Error al registrar ingreso'
                ];
                break;

            case 'guardar_cambio':
                $id_seguimiento = intval($_POST['id_seguimiento']);
                $data = [
                    'motivo' => $_POST['motivo'],
                    'descripcion' => $_POST['descripcion'],
                    'zona' => intval($_POST['zona']),
                    'prueba' => $_POST['prueba'],
                    'horas' => $_POST['horas']
                ];
                $imagen = $_FILES['imagen'] ?? null;
                $ok = $modelo->actualizarIngreso($id_seguimiento, $data, $imagen, $_SESSION['usuario_id']);
                $response = [
                    'success' => $ok,
                    'message' => $ok ? 'Cambios guardados' : 'Error al guardar cambios'
                ];
                break;

            case 'eliminar_seguimiento':
                // Solo administradores (rol 1 o 12) pueden eliminar
                if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 12])) {
                    $response = ['success' => false, 'message' => 'No tiene permiso para eliminar registros'];
                    break;
                }
                $idCombinado = $_POST['id_combinado'] ?? '';
                if (empty($idCombinado)) {
                    $response = ['success' => false, 'message' => 'ID no proporcionado'];
                    break;
                }
                $ok = $modelo->eliminarSeguimiento($idCombinado);
                $response = [
                    'success' => $ok,
                    'message' => $ok ? 'Registro eliminado correctamente' : 'Error al eliminar el registro'
                ];
                break;

            default:
                break;
        }

        // Incluir errores capturados en desarrollo
        global $captured_errors;
        if (!empty($captured_errors)) {
            $response['debug_errors'] = $captured_errors;
        }

        sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("Error en accion POST: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Error interno: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}

// --------------------------------------------------------------------
// OBTENER OPERARIOS POR SEDE (para dropdown dependiente)
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_operarios') {
    try {
        $idsede = intval($_GET['idsede'] ?? 0);
        $operarios = $modelo->getOperariosPorSede($idsede);
        sendJsonResponse($operarios);
    } catch (Exception $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

// --------------------------------------------------------------------
// OBTENER ZONAS POR SEDE (para modal de ingreso)
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_zonas') {
    try {
        $idsede = intval($_GET['idsede'] ?? 0);
        $zonas = $modelo->getZonasPorSede($idsede);
        sendJsonResponse($zonas);
    } catch (Exception $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

// --------------------------------------------------------------------
// OBTENER DEUDA DE UN OPERARIO
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_deuda') {
    try {
        $idoperario = intval($_GET['idoperario'] ?? 0);
        $deuda = $modelo->getDeudaOperario($idoperario);
        sendJsonResponse(['deuda' => $deuda]);
    } catch (Exception $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}


// --------------------------------------------------------------------
// OBTENER TODOS LOS OPERARIOS
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_all_operarios') {
    try {
        $operarios = $modelo->getTodosOperarios();
        sendJsonResponse($operarios);
    } catch (Exception $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

// --------------------------------------------------------------------
// OBTENER DETALLES PARA ELIMINAR (verificación)
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_detalles_eliminar') {
    try {
        // Solo administradores (rol 1 o 12) pueden ver detalles para eliminar
        if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 12])) {
            sendJsonResponse(['error' => 'No tiene permiso para eliminar registros'], 403);
            exit;
        }
        $idCombinado = $_GET['id_combinado'] ?? '';
        if (empty($idCombinado)) {
            sendJsonResponse(['error' => 'ID no proporcionado'], 400);
            exit;
        }
        $detalles = $modelo->getDetallesParaEliminar($idCombinado);
        sendJsonResponse($detalles);
    } catch (Exception $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

// --------------------------------------------------------------------
// OBTENER FORMULARIO POPUP (HTML)
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'form_popup') {
    error_log('form_popup triggered, tipo: ' . ($_GET['tipo'] ?? 'none'));
    try {
        $tipo = $_GET['tipo'] ?? '';
        $id = intval($_GET['id'] ?? 0);
        $param = $_GET['param'] ?? '';

        // Inicializar variables para la vista
        $idUsuario = 0;
        $idSeguimiento = 0;
        $fecha = date('Y-m-d');
        $sedePredeterminada = $_SESSION['usu_idsede'] ?? 0;

        $data = [];
        switch ($tipo) {
            case 'ingreso_manual':
                $motivos = $modelo->getMotivosIngreso('ingreso');
                $sedes = $modelo->getSedes();
                $zonas = $modelo->getZonasPorSede($sedePredeterminada);

                $idUsuario = 0;
                $sedePredeterminada = 0;
                $idSeguimiento = 0;
                $fecha = date('Y-m-d');
                $motivoSeleccionado = '';
                $descripcion = '';
                $zonaSeleccionada = 0;
                $pruebaSeleccionada = 'No aplica';
                $usuario = null;

                ob_clean(); // Limpiar buffer antes de incluir vista
                include "../view/SeguimientoUsuario/popups/ingreso.php";
                exit; // La vista ya imprime su contenido
                break;

            case 'ingreso':
                $motivos = $modelo->getMotivosIngreso('ingreso');
                $sedePredeterminada = $_SESSION['usu_idsede'] ?? 0;
                $idUsuario = 0;
                $idSeguimiento = 0;
                $fecha = date('Y-m-d');
                $motivoSeleccionado = '';
                $descripcion = '';
                $zonaSeleccionada = 0;
                $pruebaSeleccionada = 'No aplica';
                $usuario = null;
                $sedeUsuario = 0;
                $sedeNombre = '';

                if ($id > 0) {
                    $seguimiento = $modelo->getSeguimientoById($id);
                    if ($seguimiento) {
                        $idSeguimiento = $seguimiento['idseguimiento_user'];
                        $idUsuario = $seguimiento['seg_idusuario'];
                        $fecha = date('Y-m-d', strtotime($seguimiento['seg_fechaingreso']));
                        $motivoSeleccionado = $seguimiento['seg_motivo'];
                        $descripcion = $seguimiento['seg_descr'];
                        $zonaSeleccionada = $seguimiento['seg_idzona'];
                        $pruebaSeleccionada = $seguimiento['seg_alcohol'];
                        $usuario = $modelo->getOperarioById($idUsuario);
                        $sedeUsuario = $usuario['usu_idsede'] ?? 0;
                    } else {
                        $usuario = $modelo->getOperarioById($id);
                        if ($usuario) {
                            $idUsuario = $usuario['idusuarios'];
                            $sedeUsuario = $usuario['usu_idsede'];
                        } else {
                            echo "<div class='alert alert-danger'>Usuario no encontrado.</div>";
                            exit;
                        }
                    }
                } else {
                    echo "<div class='alert alert-danger'>No se proporcionó un ID válido.</div>";
                    exit;
                }

                // Si param es una fecha Y-m-d, usarla como fecha para buscar preoperacional
                if ($param && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param)) {
                    $fecha = $param;
                }

                // Si hay preoperacional validado y no hay ingreso aún, pre-fijar motivo y descripción
                if ($idUsuario && $fecha && $idSeguimiento == 0 && $modelo->tienePreoperacionalValidado($idUsuario, $fecha)) {
                    $descripcion = 'En servicio';
                    $motivoSeleccionado = 'Ingreso';
                }

                $sedeInfo = $modelo->getSedeById($sedeUsuario);
                $sedeNombre = $sedeInfo['sed_nombre'] ?? '';
                $zonas = $modelo->getZonasPorSede($sedeUsuario);

                ob_clean();
                include "../view/SeguimientoUsuario/popups/ingreso.php";
                exit;
                break;

            case 'zona':
                $data['id'] = $id;
                $data['fecha'] = $param;
                // Obtener sede del usuario del seguimiento, no del usuario logueado
                $id_sede_usuario = 0;
                $zona_seleccionada = 0;
                if ($id > 0) {
                    $seguimiento = $modelo->getSeguimientoById($id);
                    if ($seguimiento) {
                        $id_usuario = $seguimiento['seg_idusuario'];
                        $id_sede_usuario = $modelo->getSedeByUsuario($id_usuario);
                        $zona_seleccionada = $seguimiento['seg_idzona'] ?? 0;
                    }
                }
                // Si no se pudo obtener la sede, usar la del usuario logueado como fallback
                if ($id_sede_usuario <= 0) {
                    $id_sede_usuario = $_SESSION['usu_idsede'] ?? 0;
                }
                $data['zonas'] = $modelo->getZonasPorSede($id_sede_usuario);
                $data['zona_seleccionada'] = $zona_seleccionada;
                break;

            case 'hora_almuerzo':
            case 'retorno_almuerzo':
            case 'retorno_oficina':
                $data['id'] = $id;
                $data['fecha'] = $param;
                // Obtener hora actual del seguimiento
                $hora_actual = '';
                if ($id > 0) {
                    $seguimiento = $modelo->getSeguimientoById($id);
                    if ($seguimiento) {
                        switch ($tipo) {
                            case 'hora_almuerzo':
                                $hora_actual = $seguimiento['seg_horaalmuerzo'] ?? '';
                                break;
                            case 'retorno_almuerzo':
                                $hora_actual = $seguimiento['seg_horaregreso'] ?? '';
                                break;
                            case 'retorno_oficina':
                                $hora_actual = $seguimiento['seg_horaoficina'] ?? '';
                                break;
                        }
                    }
                }
                $data['hora_actual'] = $hora_actual;
                break;

            case 'companero':
            case 'trabaja_con':
                $data['id'] = $id;
                $data['param'] = $param;
                $data['operarios'] = $modelo->getTodosOperarios();
                // Obtener compañero actual si existe
                $companeroActual = 0;
                if ($id > 0) {
                    $seguimiento = $modelo->getSeguimientoById($id);
                    if ($seguimiento && !empty($seguimiento['seg_compañero'])) {
                        $companeroActual = intval($seguimiento['seg_compañero']);
                    }
                }
                $data['companero_seleccionado'] = $companeroActual;
                break;

            case 'festivos':
                $data['sedes'] = $modelo->getSedes();
                break;

            case 'vacaciones':
                // No se necesitan datos adicionales, el select se carga via JavaScript
                break;

            case 'licencias':
                $data['motivosLicencia'] = $modelo->getMotivosLicencia();
                break;
        }

        // Para los casos que no son 'ingreso*', extraer variables y cargar vista
        if (!in_array($tipo, ['ingreso_manual', 'ingreso'])) {
            if (isset($_GET['debug'])) { echo '<!-- ' . print_r($data, true) . ' -->'; }
            extract($data);
            ob_clean();
            include "../view/SeguimientoUsuario/popups/$tipo.php";
            $html = ob_get_clean();
            echo $html;
            exit;
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error al cargar el popup: " . $e->getMessage() . "</div>";
        exit;
    }
}

// --------------------------------------------------------------------
// SERVIR DOCUMENTO (ver documento)
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'ver_documento') {
    try {
        $id = intval($_GET['id'] ?? 0);
        $sql = "SELECT doc_ruta FROM documentos WHERE iddocumentos = ?";
        $stmt = $modelo->getDB()->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $ruta = $row['doc_ruta'];
            if (file_exists($ruta)) {
                header('Content-Type: ' . mime_content_type($ruta));
                header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
                readfile($ruta);
                exit;
            }
        }
        http_response_code(404);
        echo "Archivo no encontrado";
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error al cargar documento: " . $e->getMessage();
        exit;
    }
}

// --------------------------------------------------------------------
// MOSTRAR LA VISTA (index)
// --------------------------------------------------------------------
try {
    // Obtener datos para los filtros
    $sedes = $modelo->getSedes();
    $motivos = $modelo->getMotivosIngreso();
    $motivosLicencia = $modelo->getMotivosLicencia();
    $tiposContrato = $modelo->getTiposContrato();

    // Limpiar buffer y mostrar la vista principal
    ob_clean(); // Elimina cualquier salida previa (notices) antes de incluir la vista
    include "../view/SeguimientoUsuario/index.php";
} catch (Exception $e) {
    // Si falla la carga de la vista, mostrar error controlado
    echo "<h1>Error al cargar la página</h1>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    exit;
}