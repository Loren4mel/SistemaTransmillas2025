<?php
ob_start();
ini_set('display_errors', '0');

require("../../login_autentica.php");
require_once "../model/NominaModel.php";

$modelo = new NominaModel();

function nominaJson($data, int $statusCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $draw = (int) ($_POST['draw'] ?? 0);
    $start = (int) ($_POST['start'] ?? 0);
    $length = (int) ($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 1;
    $orderDir = $_POST['order'][0]['dir'] ?? 'ASC';
    $columns = $_POST['columns'] ?? [];
    $orderColumn = $columns[$orderColumnIndex]['data'] ?? 'trabajador';

    $anio = (int) ($_POST['anio'] ?? date('Y'));
    $mes = (int) ($_POST['mes'] ?? date('m'));
    $quincena = $_POST['quincena'] ?? 'Primera';
    $periodo = $modelo->calcularPeriodo($anio, $mes, $quincena);

    $filtros = [
        'anio' => $anio,
        'periodo' => $periodo,
        'sede' => $_POST['sede'] ?? '',
        'usuario' => $_POST['usuario'] ?? '',
        'tipo_contrato' => $_POST['tipo_contrato'] ?? 'Empresa',
    ];

    $totalRegistros = $modelo->contarEmpleados($filtros);
    $totalFiltrados = $modelo->contarEmpleados($filtros, $searchValue);
    $empleados = $modelo->obtenerEmpleadosPaginados($start, $length, $filtros, $searchValue, $orderColumn, $orderDir);

    nominaJson([
        'draw' => $draw,
        'recordsTotal' => $totalRegistros,
        'recordsFiltered' => $totalFiltrados,
        'data' => $empleados,
        'periodo' => $periodo,
    ]);
}

$anioActual = (int) date('Y');
$mesActual = (int) date('m');
$sedes = $modelo->getSedes();
$usuarios = $modelo->getUsuariosNomina();
$tiposContrato = $modelo->getTiposContrato();
$periodoInicial = $modelo->calcularPeriodo($anioActual, $mesActual, 'Primera');

if (ob_get_level() > 0) {
    ob_end_clean();
}

include "../view/Nomina/index.php";
