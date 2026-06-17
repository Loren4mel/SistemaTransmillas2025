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
require_once "../model/SeguimientoVehiculoModel.php";
$modelo = new SeguimientoVehiculoModel();

// ==================== FUNCIONES DE PERMISOS Y HELPERS ====================

define('ADMIN_ROLES', [1, 12]);

function esAdmin()
{
    return in_array($_SESSION['usuario_rol'] ?? 0, ADMIN_ROLES);
}

function verificarAdmin($mensaje = 'Acción solo para administradores')
{
    if (!esAdmin()) {
        return ['success' => false, 'message' => $mensaje];
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

        $filtros = [
            'propiedad' => $_POST['propiedad'] ?? 'Empresa',
            'estado_general' => $_POST['estado_general'] ?? '',
            'sede' => $_POST['sede'] ?? '',
            'conductor' => $_POST['conductor'] ?? '',
        ];

        $totalVehiculos = $modelo->getTotalVehiculos();
        $totalFiltrados = $modelo->getTotalFiltrados($filtros, $search);
        $data = $modelo->getVehiculosDataTable($start, $length, $filtros, $search);

        $response = [
            "draw" => $draw,
            "recordsTotal" => $totalVehiculos,
            "recordsFiltered" => $totalFiltrados,
            "data" => $data
        ];

        appendDebugErrors($response);
        sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("Error en AJAX seguimiento vehiculo: " . $e->getMessage());
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

            // --- Cambiar estado general del vehículo ---
            case 'cambiar_estado':
                $error = verificarAdmin('Solo administradores pueden cambiar el estado');
                if ($error) { $response = $error; break; }

                $idVehiculo = intval($_POST['id_vehiculo'] ?? 0);
                $nuevoEstado = $_POST['estado_general'] ?? '';
                $observaciones = $_POST['observaciones'] ?? '';
                $kilometraje = intval($_POST['kilometraje'] ?? 0);

                if ($idVehiculo <= 0 || empty($nuevoEstado)) {
                    $response = ['success' => false, 'message' => 'Faltan datos obligatorios'];
                    break;
                }

                $ok = $modelo->cambiarEstado($idVehiculo, $nuevoEstado, $observaciones, $_SESSION['usuario_id'], $kilometraje);
                $response = ['success' => $ok, 'message' => $ok ? 'Estado actualizado correctamente' : 'Error al actualizar estado'];
                break;

            // --- Registrar evento ---
            case 'registrar_evento':
                $error = verificarAdmin('Solo administradores pueden registrar eventos');
                if ($error) { $response = $error; break; }

                $data = [
                    'tipo_evento' => $_POST['tipo_evento'] ?? 'OTRO',
                    'id_vehiculo' => intval($_POST['id_vehiculo'] ?? 0),
                    'id_conductor' => intval($_POST['id_conductor'] ?? 0),
                    'id_responsable' => $_SESSION['usuario_id'],
                    'estado_general' => $_POST['estado_general'] ?? 'OPTIMO',
                    'kilometraje' => intval($_POST['kilometraje'] ?? 0),
                    'observaciones' => $_POST['observaciones'] ?? '',
                    'ubicacion' => $_POST['ubicacion'] ?? '',
                ];

                $ok = $modelo->registrarEvento($data);
                $response = ['success' => $ok, 'message' => $ok ? 'Evento registrado correctamente' : 'Error al registrar evento'];
                break;
        }

        appendDebugErrors($response);
        sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("Error en accion POST seguimiento vehiculo: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
    }
}

// ==================== PETICIONES GET (auxiliares y popups) ====================

if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    try {
        switch ($accion) {
            case 'get_conductores':
                $sede = intval($_GET['idsede'] ?? 0);
                sendJsonResponse($modelo->getConductores($sede));
                break;

            case 'get_historial_km':
                $idVehiculo = intval($_GET['id_vehiculo'] ?? 0);
                if ($idVehiculo <= 0) {
                    sendJsonResponse(['error' => 'ID de vehículo no válido'], 400);
                    break;
                }
                $desde = $_GET['desde'] ?? null;
                $hasta = $_GET['hasta'] ?? null;
                $fuente = $_GET['fuente'] ?? null;
                $historial = $modelo->getHistorialKilometraje($idVehiculo, $desde, $hasta, $fuente);

                // Calcular resumen
                $resumen = ['km_actual' => null, 'km_recorridos' => null, 'promedio_diario' => null];
                if (!empty($historial)) {
                    // Ordenar por fecha ascendente para cálculos
                    $ordenado = $historial;
                    usort($ordenado, function ($a, $b) {
                        return strtotime($a['fecha'] ?? '') <=> strtotime($b['fecha'] ?? '');
                    });
                    $resumen['km_actual'] = (int) $ordenado[count($ordenado) - 1]['kilometraje'];
                    $resumen['km_recorridos'] = $resumen['km_actual'] - (int) $ordenado[0]['kilometraje'];
                    $primerFecha = strtotime($ordenado[0]['fecha'] ?? '');
                    $ultimaFecha = strtotime($ordenado[count($ordenado) - 1]['fecha'] ?? '');
                    $dias = max(1, (int) round(($ultimaFecha - $primerFecha) / 86400));
                    $resumen['promedio_diario'] = $dias > 0 ? round($resumen['km_recorridos'] / $dias) : 0;
                }

                // Formatear datos para Chart.js (orden ascendente)
                $graficoLabels = [];
                $graficoData = [];
                $graficoColores = [];
                $colorMap = ['Evento' => '#0c4582', 'Preoperacional' => '#1e7f4f', 'Cambio Aceite' => '#b54708'];
                foreach ($ordenado ?? $historial as $h) {
                    $graficoLabels[] = date('d-m-Y', strtotime($h['fecha'] ?? ''));
                    $graficoData[] = (int) ($h['kilometraje'] ?? 0);
                    $graficoColores[] = $colorMap[$h['fuente']] ?? '#0c4582';
                }

                sendJsonResponse([
                    'resumen' => $resumen,
                    'historial' => $historial,
                    'grafico' => [
                        'labels' => $graficoLabels,
                        'data' => $graficoData,
                        'colores' => $graficoColores,
                    ],
                ]);
                break;

            case 'get_historial_estado':
                $idVehiculo = intval($_GET['id_vehiculo'] ?? 0);
                if ($idVehiculo <= 0) {
                    sendJsonResponse(['error' => 'ID de vehículo no válido'], 400);
                    break;
                }
                $desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
                $hasta = $_GET['hasta'] ?? date('Y-m-d');
                $tipo = $_GET['tipo'] ?? 'Todos';
                $ultimaObs = $modelo->getUltimaObservacionNoPreop($idVehiculo);
                $eventos = $modelo->getHistorialEstado($idVehiculo, $desde, $hasta, $tipo);
                sendJsonResponse([
                    'ultima_obs' => $ultimaObs,
                    'eventos' => $eventos,
                ]);
                break;

            case 'get_comparendos':
                $idVehiculo = intval($_GET['id_vehiculo'] ?? 0);
                if ($idVehiculo <= 0) {
                    sendJsonResponse(['error' => 'ID de vehículo no válido'], 400);
                    break;
                }
                sendJsonResponse($modelo->getComparendos($idVehiculo));
                break;

            case 'get_detalle_vehiculo':
                $idVehiculo = intval($_GET['id_vehiculo'] ?? 0);
                if ($idVehiculo <= 0) {
                    sendJsonResponse(['error' => 'ID de vehículo no válido'], 400);
                    break;
                }
                sendJsonResponse($modelo->getDetalleVehiculo($idVehiculo));
                break;

            case 'form_popup':
                $tipo = $_GET['tipo'] ?? '';
                $idVehiculo = intval($_GET['id'] ?? 0);

                switch ($tipo) {
                    case 'cambiar_estado':
                        $vehiculo = $modelo->getVehiculoById($idVehiculo);
                        if (!$vehiculo) {
                            echo "<div class='alert alert-danger'>Vehículo no encontrado.</div>";
                            exit;
                        }
                        ob_clean();
                        include "../view/SeguimientoVehiculo/popups/cambiar_estado.php";
                        exit;

                    case 'registro_evento':
                        $vehiculo = $modelo->getVehiculoById($idVehiculo);
                        if (!$vehiculo) {
                            echo "<div class='alert alert-danger'>Vehículo no encontrado.</div>";
                            exit;
                        }
                        ob_clean();
                        include "../view/SeguimientoVehiculo/popups/registro_evento.php";
                        exit;

                    case 'historial_kilometraje':
                        $vehiculo = $modelo->getVehiculoById($idVehiculo);
                        $historial = $modelo->getHistorialKilometraje($idVehiculo);
                        if (!$vehiculo) {
                            echo "<div class='alert alert-danger'>Vehículo no encontrado.</div>";
                            exit;
                        }
                        ob_clean();
                        include "../view/SeguimientoVehiculo/popups/historial_kilometraje.php";
                        exit;

                    case 'comparendos':
                        $vehiculo = $modelo->getVehiculoById($idVehiculo);
                        $comparendos = $modelo->getComparendos($idVehiculo);
                        if (!$vehiculo) {
                            echo "<div class='alert alert-danger'>Vehículo no encontrado.</div>";
                            exit;
                        }
                        ob_clean();
                        include "../view/SeguimientoVehiculo/popups/comparendos.php";
                        exit;

                    case 'detalle_vehiculo':
                        $detalle = $modelo->getDetalleVehiculo($idVehiculo);
                        $vehiculo = $detalle['vehiculo'] ?? null;
                        if (!$vehiculo) {
                            echo "<div class='alert alert-danger'>Vehículo no encontrado.</div>";
                            exit;
                        }
                        ob_clean();
                        include "../view/SeguimientoVehiculo/popups/detalle_evento.php";
                        exit;

                    case 'historial_estado':
                        $vehiculo = $modelo->getVehiculoById($idVehiculo);
                        if (!$vehiculo) {
                            echo "<div class='alert alert-danger'>Vehículo no encontrado.</div>";
                            exit;
                        }
                        $desdeDefecto = date('Y-m-d', strtotime('-30 days'));
                        $hastaDefecto = date('Y-m-d');
                        $ultimaObs = $modelo->getUltimaObservacionNoPreop($idVehiculo);
                        $eventos = $modelo->getHistorialEstado($idVehiculo, $desdeDefecto, $hastaDefecto, 'Todos');
                        ob_clean();
                        include "../view/SeguimientoVehiculo/popups/historial_estado.php";
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
    $tiposPropiedad = $modelo->getTiposPropiedad();
    $conductores = $modelo->getConductores();
    ob_clean();
    // Calcular ruta base de la aplicación desde el servidor
    $appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $ajaxEndpoint = $appBasePath . '/controller/SeguimientoVehiculoController.php';

    include "../view/SeguimientoVehiculo/index.php";
} catch (Exception $e) {
    echo "<h1>Error al cargar la página</h1><pre>" . $e->getMessage() . "</pre>";
    exit;
}
