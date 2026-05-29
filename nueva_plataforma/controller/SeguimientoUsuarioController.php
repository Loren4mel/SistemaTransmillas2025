<?php
// ==================== INICIO: BUFFER DE SALIDA ====================
ob_start();

// ==================== GESTIÓN DE ERRORES ====================
require_once __DIR__ . '/../helpers/ErrorHandler.php';
ErrorHandler::setup();

// ==================== FUNCIÓN AUXILIAR PARA ENVIAR JSON ====================
function sendJsonResponse($data, $statusCode = 200)
{
    ErrorHandler::sendJsonResponse($data, $statusCode);
}

// ==================== INCLUIR AUTENTICACIÓN ====================
require("../../login_autentica.php");

// ==================== INCLUIR MODELO ====================
require_once "../model/SeguimientoUsuarioModel.php";
$modelo = new SeguimientoUsuarioModel();

// ==================== FUNCIONES DE PERMISOS Y HELPERS ====================

define('ADMIN_ROLES', [1, 12]);

function esAdmin()
{
    return in_array($_SESSION['usuario_rol'] ?? 0, ADMIN_ROLES);
}

function puedeModificarSeguimiento($idSeguimiento)
{
    global $modelo;
    if (esAdmin()) {
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

function verificarPermiso($idSeguimiento, $mensaje = 'Permiso denegado')
{
    if (!puedeModificarSeguimiento($idSeguimiento)) {
        return ['success' => false, 'message' => $mensaje];
    }
    return null;
}

function verificarAdmin($mensaje = 'Acción solo para administradores')
{
    if (!esAdmin()) {
        return ['success' => false, 'message' => $mensaje];
    }
    return null;
}

function validarRangoFechas($fechaIni, $fechaFin)
{
    if (empty($fechaIni) || empty($fechaFin)) {
        return 'Ambas fechas son obligatorias';
    }
    if ($fechaFin < $fechaIni) {
        return 'La fecha fin no puede ser anterior a la fecha inicio';
    }
    return null;
}

function appendDebugErrors(&$response)
{
    $captured_errors = ErrorHandler::getCapturedErrors();
    if (!empty($captured_errors) && ($_SERVER['SERVER_NAME'] ?? '') === 'localhost') {
        $response['debug_errors'] = $captured_errors;
    }
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

        appendDebugErrors($response);
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

            // --- Guardar ingreso (unificado: popup, manual, y edición) ---
            case 'guardar_ingreso_popup':
            case 'guardar_ingreso':
            case 'guardar_cambio':
                $idSeguimiento = intval($_POST['id_seguimiento'] ?? 0);
                $esActualizacion = ($idSeguimiento > 0);

                if ($esActualizacion) {
                    $error = verificarPermiso($idSeguimiento);
                    if ($error) { $response = $error; break; }
                }

                $data = [
                    'operario'    => intval($_POST['operario'] ?? 0),
                    'fecha'       => $_POST['fecha'] ?? date('Y-m-d'),
                    'motivo'      => $_POST['motivo'] ?? '',
                    'descripcion' => $_POST['descripcion'] ?? '',
                    'zona'        => intval($_POST['zona'] ?? 0),
                    'prueba'      => $_POST['prueba'] ?? 'No aplica',
                    'horas'       => $_POST['horas'] ?? ''
                ];
                $imagen = $_FILES['imagen'] ?? null;

                if ($esActualizacion) {
                    $dataActualizar = [
                        'motivo'      => $data['motivo'],
                        'descripcion' => $data['descripcion'],
                        'zona'        => $data['zona'],
                        'prueba'      => $data['prueba'],
                        'horas'       => $data['horas']
                    ];
                    $ok = $modelo->actualizarIngreso($idSeguimiento, $dataActualizar, $imagen, $_SESSION['usuario_id']);
                } else {
                    $ok = $modelo->insertarIngreso($data, $imagen, $_SESSION['usuario_id']);
                }

                $response = ['success' => $ok, 'message' => $ok ? 'Ingreso guardado correctamente' : 'Error al guardar'];
                break;

            // --- Guardar zona ---
            case 'guardar_zona':
                $id = intval($_POST['id']);
                $error = verificarPermiso($id);
                if ($error) { $response = $error; break; }
                $ok = $modelo->actualizarZona($id, intval($_POST['zona']));
                $response = ['success' => $ok, 'message' => $ok ? 'Zona actualizada' : 'Error'];
                break;

            // --- Guardar hora almuerzo ---
            case 'guardar_hora_almuerzo':
                $id = intval($_POST['id']);
                $error = verificarPermiso($id);
                if ($error) { $response = $error; break; }
                $ok = $modelo->actualizarHoraAlmuerzo($id, $_POST['hora']);
                $response = ['success' => $ok, 'message' => $ok ? 'Hora guardada' : 'Error'];
                break;

            // --- Guardar retorno almuerzo ---
            case 'guardar_retorno_almuerzo':
                $id = intval($_POST['id']);
                $error = verificarPermiso($id);
                if ($error) { $response = $error; break; }
                $ok = $modelo->actualizarRetornoAlmuerzo($id, $_POST['hora']);
                $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
                break;

            // --- Guardar retorno oficina ---
            case 'guardar_retorno_oficina':
                $id = intval($_POST['id']);
                $error = verificarPermiso($id);
                if ($error) { $response = $error; break; }
                $ok = $modelo->actualizarRetornoOficina($id, $_POST['hora']);
                $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
                break;

            // --- Guardar compañero ---
            case 'guardar_companero':
                $id = intval($_POST['id']);
                $error = verificarPermiso($id);
                if ($error) { $response = $error; break; }
                $ok = $modelo->actualizarCompanero($id, intval($_POST['companero']));
                $response = ['success' => $ok, 'message' => $ok ? 'Compañero actualizado' : 'Error'];
                break;

            // --- Guardar festivos ---
            case 'guardar_festivos':
                $error = verificarAdmin();
                if ($error) { $response = $error; break; }
                if (empty($_POST['fecha'])) {
                    $response = ['success' => false, 'message' => 'La fecha es obligatoria'];
                    break;
                }
                $ok = $modelo->insertarFestivos($_POST['fecha'], intval($_POST['sede'] ?? 0), $_SESSION['usuario_id']);
                $response = ['success' => $ok, 'message' => $ok ? 'Días festivos agregados' : 'Error al agregar festivos'];
                break;

            // --- Guardar vacaciones ---
            case 'guardar_vacaciones':
                $fechaIni = $_POST['fecha_ini'] ?? '';
                $fechaFin = $_POST['fecha_fin'] ?? '';
                $errorFecha = validarRangoFechas($fechaIni, $fechaFin);
                if ($errorFecha) { $response = ['success' => false, 'message' => $errorFecha]; break; }
                $ok = $modelo->insertarVacaciones([
                    'operario' => intval($_POST['operario']),
                    'fecha_ini' => $fechaIni,
                    'fecha_fin' => $fechaFin
                ], $_SESSION['usuario_id']);
                $response = ['success' => $ok, 'message' => $ok ? 'Vacaciones registradas' : 'Error al registrar vacaciones'];
                break;

            // --- Guardar licencia ---
            case 'guardar_licencia':
                $fechaLicIni = $_POST['fecha_ini'] ?? '';
                $fechaLicFin = $_POST['fecha_fin'] ?? '';
                $errorFecha = validarRangoFechas($fechaLicIni, $fechaLicFin);
                if ($errorFecha) { $response = ['success' => false, 'message' => $errorFecha]; break; }
                $ok = $modelo->insertarLicencia([
                    'operario' => intval($_POST['operario']),
                    'fecha_ini' => $fechaLicIni,
                    'fecha_fin' => $fechaLicFin,
                    'motivo' => $_POST['motivo'],
                    'descripcion' => $_POST['descripcion']
                ], $_SESSION['usuario_id']);
                $response = ['success' => $ok, 'message' => $ok ? 'Licencia registrada' : 'Error al registrar licencia'];
                break;

            // --- Eliminar seguimiento ---
            case 'eliminar_seguimiento':
                $error = verificarAdmin('No tiene permiso para eliminar registros');
                if ($error) { $response = $error; break; }
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

        appendDebugErrors($response);
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
                sendJsonResponse($modelo->getOperariosPorSede(intval($_GET['idsede'] ?? 0)));
                break;

            case 'get_zonas':
                sendJsonResponse($modelo->getZonasPorSede(intval($_GET['idsede'] ?? 0)));
                break;

            case 'get_deuda':
                sendJsonResponse(['deuda' => $modelo->getDeudaOperario(intval($_GET['idoperario'] ?? 0))]);
                break;

            case 'get_all_operarios':
                sendJsonResponse($modelo->getTodosOperarios());
                break;

            case 'get_detalles_eliminar':
                if (!esAdmin()) {
                    sendJsonResponse(['error' => 'No tiene permiso'], 403);
                }
                sendJsonResponse($modelo->getDetallesParaEliminar($_GET['id_combinado'] ?? ''));
                break;

            case 'buscar_nuevos_empleados':
                $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-14 days'));
                $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
                $errorFecha = validarRangoFechas($fechaInicio, $fechaFin);
                if ($errorFecha) {
                    sendJsonResponse(['error' => $errorFecha], 400);
                    break;
                }
                $empleados = $modelo->buscarNuevosEmpleados($fechaInicio, $fechaFin);
                // Enriquecer cada fila con datos calculados
                $hoy = new DateTime();
                foreach ($empleados as &$emp) {
                    if (!empty($emp['hoj_fechaingreso'])) {
                        $fechaIng = new DateTime($emp['hoj_fechaingreso']);
                        $emp['dias_desde_ingreso'] = (int)$hoy->diff($fechaIng)->days;
                        $emp['hoj_fechaingreso_fmt'] = $fechaIng->format('d-m-Y');
                    } else {
                        $emp['dias_desde_ingreso'] = null;
                        $emp['hoj_fechaingreso_fmt'] = '';
                    }
                    // Formatear estado
                    $estado = $emp['hoj_estado'] ?? '';
                    $emp['hoj_estado_fmt'] = $estado ?: 'Sin registro';
                    $emp['hoj_estado_activo'] = ($estado === 'Activo');
                    // Construir texto de alertas
                    $alertas = [];
                    if (!empty($emp['falta_cuenta'])) $alertas[] = 'Falta cuenta bancaria';
                    if (!empty($emp['falta_arl']) && $emp['usu_tipocontrato'] === 'Empresa') $alertas[] = 'Falta ARL';
                    $emp['alertas_texto'] = implode(', ', $alertas);
                }
                sendJsonResponse($empleados);
                break;

            case 'ver_documento':
                $id = intval($_GET['id'] ?? 0);
                $ruta = $modelo->getRutaDocumento($id);
                if ($ruta) {
                    $projectRoot = realpath(__DIR__ . '/../../');
                    $candidates = [$ruta];
                    if ($projectRoot) {
                        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . ltrim($ruta, '/\\');
                    }
                    foreach ($candidates as $filePath) {
                        if (file_exists($filePath)) {
                            header('Content-Type: ' . mime_content_type($filePath));
                            header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
                            readfile($filePath);
                            exit;
                        }
                    }
                }
                http_response_code(404);
                echo "Archivo no encontrado";
                exit;

            case 'form_popup':
                $tipo = $_GET['tipo'] ?? '';
                $id = intval($_GET['id'] ?? 0);
                $param = $_GET['param'] ?? '';

                $idUsuario = 0;
                $idSeguimiento = 0;
                $fecha = date('Y-m-d');
                $sedePredeterminada = $_SESSION['usu_idsede'] ?? 0;

                $data = [];
                switch ($tipo) {
                    case 'ingreso':
                        $motivos = $modelo->getMotivosIngreso();
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
                        $horasSeleccionada = '';

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
                                $horasSeleccionada = $seguimiento['seg_horas_trabajadas'] ?? '';
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
                                $id_sede_usuario = $modelo->getSedeByUsuario($seguimiento['seg_idusuario']);
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
                        $hora_actual = date('H:i');
                        if ($id > 0) {
                            $seguimiento = $modelo->getSeguimientoById($id);
                            if ($seguimiento) {
                                $campos = [
                                    'hora_almuerzo' => 'seg_horaalmuerzo',
                                    'retorno_almuerzo' => 'seg_horaregreso',
                                    'retorno_oficina' => 'seg_horaoficina'
                                ];
                                $dbValue = $seguimiento[$campos[$tipo]] ?? '';
                                if ($dbValue !== '' && $dbValue !== null) {
                                    $hora_actual = $dbValue;
                                }
                            }
                        }
                        $data['hora_actual'] = $hora_actual;
                        break;

                    case 'trabaja_con':
                        $data['id'] = $id;
                        $data['param'] = $param;
                        $idsede = 0;
                        $companeroActual = 0;
                        if ($id > 0) {
                            $seguimiento = $modelo->getSeguimientoById($id);
                            if ($seguimiento) {
                                $idsede = $modelo->getSedeByUsuario($seguimiento['seg_idusuario']);
                                if (!empty($seguimiento['seg_compañero'])) {
                                    $companeroActual = intval($seguimiento['seg_compañero']);
                                }
                            }
                        }
                        $data['operarios'] = $idsede > 0 ? $modelo->getOperariosPorSede($idsede) : $modelo->getTodosOperarios();
                        $data['companero_seleccionado'] = $companeroActual;
                        break;

                    case 'nuevos_empleados':
                        break;

                    case 'festivos':
                        $data['sedes'] = $modelo->getSedes();
                        break;

                    case 'vacaciones':
                        break;

                    case 'licencias':
                        $data['motivosLicencia'] = $modelo->getMotivosLicencia();
                        break;
                }

                if ($tipo !== 'ingreso') {
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
