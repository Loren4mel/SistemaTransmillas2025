<?php
ob_start();
ini_set('display_errors', '0');

require("../../login_autentica.php");
require_once __DIR__ . "/../model/PrimasModel.php";

$modelo = new PrimasModel();
$idUsuarioSesion = (int) ($_SESSION['usuario_id'] ?? 0);
$rolUsuario = (int) ($_SESSION['usuario_rol'] ?? 0);
$sedeUsuario = (int) ($_SESSION['usu_idsede'] ?? 0);

function primasJson(array $data, int $statusCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'detalle_dias') {
        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $fechaInicio = $_POST['fecha_inicio'] ?? '';
        $fechaFin = $_POST['fecha_fin'] ?? '';
        $grupo = $_POST['grupo'] ?? '';
        $completarPeriodo = ($_POST['completar_periodo'] ?? '0') === '1';

        $datos = $modelo->obtenerDetalleDias($idUsuario, $fechaInicio, $fechaFin, $grupo, $completarPeriodo);
        primasJson([
            'success' => true,
            'data' => $datos,
            'total' => count($datos),
        ]);
    }

    if (isset($_POST['ajax'])) {
        $draw = (int) ($_POST['draw'] ?? 0);
        $start = max(0, (int) ($_POST['start'] ?? 0));
        $length = (int) ($_POST['length'] ?? 10);
        $length = $length > 0 ? $length : 10;
        $searchValue = $_POST['search']['value'] ?? '';
        $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
        $orderDir = $_POST['order'][0]['dir'] ?? 'ASC';
        $columns = $_POST['columns'] ?? [];
        $orderColumn = $columns[$orderColumnIndex]['data'] ?? 'trabajador';

        $anio = (int) ($_POST['anio'] ?? date('Y'));
        $semestre = $_POST['semestre'] ?? 'Primera';
        $periodo = $modelo->calcularPeriodoPrima($anio, $semestre);
        $sede = $_POST['sede'] ?? '';

        if (!in_array($rolUsuario, [1, 12], true)) {
            $sede = $sedeUsuario;
        }

        $filtros = [
            'anio' => $anio,
            'periodo' => $periodo,
            'sede' => $sede,
            'usuario' => $_POST['usuario'] ?? '',
            'estado' => $_POST['estado'] ?? 'Trabajando',
            'completar_periodo' => ($_POST['completar_periodo'] ?? '0') === '1',
        ];

        $todos = $modelo->obtenerPrimas($filtros, $searchValue, $orderColumn, $orderDir);
        $data = array_slice($todos, $start, $length);
        $totalPrima = array_sum(array_map(static fn($row) => (float) ($row['total_prima'] ?? 0), $todos));

        primasJson([
            'draw' => $draw,
            'recordsTotal' => count($todos),
            'recordsFiltered' => count($todos),
            'data' => $data,
            'periodo' => $periodo,
            'total_prima' => $totalPrima,
        ]);
    }

    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $fechaFin = $_POST['fecha_fin'] ?? '';
    $semestre = $_POST['semestre'] ?? 'Primera';
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);

    if ($accion === 'confirmar_pago') {
        $confirma = ($_POST['confirma'] ?? 'no') === 'Si' ? 'Si' : 'no';
        $ok = $modelo->confirmarPago($idUsuario, $fechaInicio, $fechaFin, $semestre, $confirma);
        primasJson(['success' => $ok, 'message' => $ok ? 'Pago actualizado.' : 'No fue posible actualizar el pago.'], $ok ? 200 : 422);
    }

    if ($accion === 'liquidar_prima' || $accion === 'reliquidar_prima') {
        $resultado = $modelo->liquidarPrima($_POST, $idUsuarioSesion, $accion === 'reliquidar_prima');
        primasJson($resultado, $resultado['success'] ? 200 : 422);
    }

    if ($accion === 'eliminar_liquidacion') {
        $resultado = $modelo->eliminarLiquidacionPrima($idUsuario, $fechaInicio);
        primasJson($resultado, $resultado['success'] ? 200 : 422);
    }

    if ($accion === 'confirmar_admin') {
        $confirma = ($_POST['confirma'] ?? 'no') === 'si' ? 'si' : 'no';
        $ok = $modelo->confirmarAdmin($idUsuario, $fechaInicio, $fechaFin, $semestre, $confirma, $idUsuarioSesion);
        primasJson(['success' => $ok, 'message' => $ok ? 'Confirmacion actualizada.' : 'No fue posible confirmar.'], $ok ? 200 : 422);
    }

    if ($accion === 'guardar_desprendible') {
        $ruta = $_POST['ruta'] ?? '';
        $resultado = $modelo->enviarPrimaPendiente($idUsuario, $fechaInicio, $fechaFin, $semestre, $ruta);
        primasJson($resultado, $resultado['success'] ? 200 : 422);
    }

    if ($accion === 'cargar_comprobante') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $ids = is_array($ids) ? $ids : [];
        $resultado = $modelo->guardarComprobante($ids, $_FILES['comprobante'] ?? [], $fechaInicio, $fechaFin, $semestre);
        primasJson($resultado, $resultado['success'] ? 200 : 422);
    }
}

$anioActual = (int) date('Y');
$mesActual = (int) date('m');
$semestreActual = $mesActual <= 6 ? 'Primera' : 'Segunda';
$sedes = $modelo->getSedes($rolUsuario, $sedeUsuario);
$usuarios = $modelo->getUsuariosNomina($rolUsuario, $sedeUsuario);
$periodoInicial = $modelo->calcularPeriodoPrima($anioActual, $semestreActual);
$estadoInicial = 'Trabajando';
$estados = [
    '' => 'Todos',
    'Trabajando' => 'Trabajando',
    'Retirado' => 'Retirado',
];

if (ob_get_level() > 0) {
    ob_end_clean();
}

include __DIR__ . "/../view/Primas/index.php";
