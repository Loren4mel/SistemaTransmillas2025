<?php
// ==================== INICIO: BUFFER DE SALIDA ====================
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR);

// ==================== MANEJADOR DE ERRORES PERSONALIZADO ====================
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

// ==================== MANEJADOR DE EXCEPCIONES GLOBAL ====================
set_exception_handler(function ($exception) {
    error_log("Excepcion no capturada: " . $exception->getMessage());
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        sendJsonResponse(['error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()], 500);
    } else {
        echo "<h1>Error</h1><pre>" . $exception->getMessage() . "\n" . $exception->getTraceAsString() . "</pre>";
    }
    exit;
});

// ==================== MANEJADOR DE ERRORES FATALES ====================
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

// ==================== FUNCIÓN AUXILIAR PARA ENVIAR JSON ====================
function sendJsonResponse($data, $statusCode = 200)
{
    if (ob_get_level())
        ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ==================== INCLUIR AUTENTICACIÓN ====================
require("../../login_autentica.php");

// ==================== INCLUIR MODELO ====================
require_once "../model/SeguimientoUsuarioModel.php";
$modelo = new SeguimientoUsuarioModel();

// ==================== FUNCIONES DE PERMISOS ====================

function puedeModificarSeguimiento($idSeguimiento)
{
    global $modelo;
    if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 12])) {
        return true;
    }
    $sql = "SELECT u.usu_idsede FROM seguimiento_user s
            INNER JOIN usuarios u ON u.idusuarios = s.seg_idusuario
            WHERE s.idseguimiento_user = ?";
    $stmt = $modelo->getDB()->prepare($sql);
    $stmt->bind_param('i', $idSeguimiento);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $sedeOperario = $row['usu_idsede'] ?? 0;
    $sedeUsuario = $_SESSION['usu_idsede'] ?? 0;
    return ($sedeOperario == $sedeUsuario);
}

// ==================== PETICIONES AJAX PARA DATATABLE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    try {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $search = $_POST['search']['value'] ?? '';
        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = $_POST['order'][0]['dir'] ?? 'ASC';
        $columns = $_POST['columns'] ?? [];
        $orderColumn = $columns[$orderColumnIndex]['data'] ?? 'usu_nombre';

        $filtros = [
            'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
            'fecha_fin' => $_POST['fecha_fin'] ?? date('Y-m-d'),
            'sede' => $_POST['sede'] ?? '',
            'operario' => $_POST['operario'] ?? '',
            'motivo' => $_POST['motivo'] ?? '',
            'tipo_contrato' => $_POST['tipo_contrato'] ?? ''
        ];

        $result = $modelo->getRegistrosDataTable($start, $length, $filtros, $search, $orderColumn, $orderDir);
        $totalFiltrados = $modelo->getTotalFiltrados($filtros, $search);
        $totalRegistros = $modelo->getTotalRegistros();

        $response = [
            "draw" => $draw,
            "recordsTotal" => $totalRegistros,
            "recordsFiltered" => $totalFiltrados,
            "data" => $result
        ];

        global $captured_errors;
        if (!empty($captured_errors) && ($_SERVER['SERVER_NAME'] ?? '') === 'localhost') {
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
            "error" => $e->getMessage()
        ], 500);
    }
}

// ==================== PETICIONES AJAX PARA ACCIONES POST ====================
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
                    if (!puedeModificarSeguimiento($idSeguimiento)) {
                        $response = ['success' => false, 'message' => 'No tiene permiso para modificar este registro'];
                        break;
                    }
                    $ok = $modelo->actualizarIngreso($idSeguimiento, $data, $imagen, $_SESSION['usuario_id']);
                } else {
                    $ok = $modelo->insertarIngreso($data, $imagen, $_SESSION['usuario_id']);
                }

                $response = ['success' => $ok, 'message' => $ok ? 'Ingreso guardado correctamente' : 'Error al guardar'];
                break;

            case 'guardar_zona':
                $id = intval($_POST['id']);
                $zona = intval($_POST['zona']);
                if (!puedeModificarSeguimiento($id)) {
                    $response = ['success' => false, 'message' => 'Permiso denegado'];
                    break;
                }
                $ok = $modelo->actualizarZona($id, $zona);
                $response = ['success' => $ok, 'message' => $ok ? 'Zona actualizada' : 'Error'];
                break;

            case 'guardar_hora_almuerzo':
                $id = intval($_POST['id']);
                $hora = $_POST['hora'];
                if (!puedeModificarSeguimiento($id)) {
                    $response = ['success' => false, 'message' => 'Permiso denegado'];
                    break;
                }
                $ok = $modelo->actualizarHoraAlmuerzo($id, $hora);
                $response = ['success' => $ok, 'message' => $ok ? 'Hora guardada' : 'Error'];
                break;

            case 'guardar_retorno_almuerzo':
                $id = intval($_POST['id']);
                $hora = $_POST['hora'];
                if (!puedeModificarSeguimiento($id)) {
                    $response = ['success' => false, 'message' => 'Permiso denegado'];
                    break;
                }
                $ok = $modelo->actualizarRetornoAlmuerzo($id, $hora);
                $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
                break;

            case 'guardar_retorno_oficina':
                $id = intval($_POST['id']);
                $hora = $_POST['hora'];
                if (!puedeModificarSeguimiento($id)) {
                    $response = ['success' => false, 'message' => 'Permiso denegado'];
                    break;
                }
                $ok = $modelo->actualizarRetornoOficina($id, $hora);
                $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
                break;

            case 'guardar_companero':
                $id = intval($_POST['id']);
                $companero = intval($_POST['companero']);
                if (!puedeModificarSeguimiento($id)) {
                    $response = ['success' => false, 'message' => 'Permiso denegado'];
                    break;
                }
                $ok = $modelo->actualizarCompanero($id, $companero);
                $response = ['success' => $ok, 'message' => $ok ? 'Compañero actualizado' : 'Error'];
                break;

            case 'guardar_festivos':
                if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 12])) {
                    $response = ['success' => false, 'message' => 'Acción solo para administradores'];
                    break;
                }
                $fecha = $_POST['fecha'];
                $sede = intval($_POST['sede'] ?? 0);
                $ok = $modelo->insertarFestivos($fecha, $sede, $_SESSION['usuario_id']);
                $response = ['success' => $ok, 'message' => $ok ? 'Días festivos agregados' : 'Error al agregar festivos'];
                break;

            case 'guardar_vacaciones':
                $data = [
                    'operario' => intval($_POST['operario']),
                    'fecha_ini' => $_POST['fecha_ini'],
                    'fecha_fin' => $_POST['fecha_fin']
                ];
                $ok = $modelo->insertarVacaciones($data, $_SESSION['usuario_id']);
                $response = ['success' => $ok, 'message' => $ok ? 'Vacaciones registradas' : 'Error al registrar vacaciones'];
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
                $response = ['success' => $ok, 'message' => $ok ? 'Licencia registrada' : 'Error al registrar licencia'];
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
                $response = ['success' => $ok, 'message' => $ok ? 'Ingreso registrado' : 'Error al registrar ingreso'];
                break;

            case 'guardar_cambio':
                $idSeguimiento = intval($_POST['id_seguimiento']);
                if (!puedeModificarSeguimiento($idSeguimiento)) {
                    $response = ['success' => false, 'message' => 'Permiso denegado'];
                    break;
                }
                $data = [
                    'motivo' => $_POST['motivo'],
                    'descripcion' => $_POST['descripcion'],
                    'zona' => intval($_POST['zona']),
                    'prueba' => $_POST['prueba'],
                    'horas' => $_POST['horas']
                ];
                $imagen = $_FILES['imagen'] ?? null;
                $ok = $modelo->actualizarIngreso($idSeguimiento, $data, $imagen, $_SESSION['usuario_id']);
                $response = ['success' => $ok, 'message' => $ok ? 'Cambios guardados' : 'Error al guardar cambios'];
                break;

            case 'eliminar_seguimiento':
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
                $response = ['success' => $ok, 'message' => $ok ? 'Registro eliminado' : 'Error al eliminar'];
                break;

            default:
                break;
        }

        global $captured_errors;
        if (!empty($captured_errors) && ($_SERVER['SERVER_NAME'] ?? '') === 'localhost') {
            $response['debug_errors'] = $captured_errors;
        }

        sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("Error en accion POST: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

// ==================== PETICIONES GET (auxiliares y popups) ====================

if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    try {
        switch ($accion) {
            case 'get_operarios':
                $idsede = intval($_GET['idsede'] ?? 0);
                sendJsonResponse($modelo->getOperariosPorSede($idsede));
                break;

            case 'get_zonas':
                $idsede = intval($_GET['idsede'] ?? 0);
                sendJsonResponse($modelo->getZonasPorSede($idsede));
                break;

            case 'get_deuda':
                $idoperario = intval($_GET['idoperario'] ?? 0);
                sendJsonResponse(['deuda' => $modelo->getDeudaOperario($idoperario)]);
                break;

            case 'get_all_operarios':
                sendJsonResponse($modelo->getTodosOperarios());
                break;

            case 'get_detalles_eliminar':
                if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 12])) {
                    sendJsonResponse(['error' => 'No tiene permiso'], 403);
                }
                $idCombinado = $_GET['id_combinado'] ?? '';
                sendJsonResponse($modelo->getDetallesParaEliminar($idCombinado));
                break;

            case 'ver_documento':
                $id = intval($_GET['id'] ?? 0);
                $sql = "SELECT doc_ruta FROM documentos WHERE iddocumentos = ?";
                $stmt = $modelo->getDB()->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if ($row && file_exists($row['doc_ruta'])) {
                    header('Content-Type: ' . mime_content_type($row['doc_ruta']));
                    header('Content-Disposition: inline; filename="' . basename($row['doc_ruta']) . '"');
                    readfile($row['doc_ruta']);
                    exit;
                }
                http_response_code(404);
                echo "Archivo no encontrado";
                exit;

            case 'form_popup':
                error_log('form_popup triggered, tipo: ' . ($_GET['tipo'] ?? 'none'));
                $tipo = $_GET['tipo'] ?? '';
                $id = intval($_GET['id'] ?? 0);
                $param = $_GET['param'] ?? '';

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
                        ob_clean();
                        include "../view/SeguimientoUsuario/popups/ingreso.php";
                        exit;

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

                        if ($param && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param)) {
                            $fecha = $param;
                        }

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

                    case 'zona':
                        $data['id'] = $id;
                        $data['fecha'] = $param;
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
                        // No se necesitan datos adicionales
                        break;

                    case 'licencias':
                        $data['motivosLicencia'] = $modelo->getMotivosLicencia();
                        break;
                }

                if (!in_array($tipo, ['ingreso_manual', 'ingreso'])) {
                    extract($data);
                    ob_clean();
                    include "../view/SeguimientoUsuario/popups/$tipo.php";
                    $html = ob_get_clean();
                    echo $html;
                    exit;
                }
                break;
        }
    } catch (Exception $e) {
        if (isset($_GET['accion']) && $_GET['accion'] === 'form_popup') {
            echo "<div class='alert alert-danger'>Error al cargar el popup: " . $e->getMessage() . "</div>";
        } else {
            sendJsonResponse(['error' => $e->getMessage()], 500);
        }
        exit;
    }
}

// ==================== MOSTRAR VISTA PRINCIPAL ====================
try {
    $sedes = $modelo->getSedes();
    $motivos = $modelo->getMotivosIngreso();
    $tiposContrato = $modelo->getTiposContrato();
    ob_clean();
    include "../view/SeguimientoUsuario/index.php";
} catch (Exception $e) {
    echo "<h1>Error al cargar la página</h1><pre>" . $e->getMessage() . "</pre>";
    exit;
}