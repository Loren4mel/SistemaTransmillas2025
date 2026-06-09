<?php
require_once "../model/CreditosModel.php";

$modelo = new CreditosModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';

    $totalRegistros = $modelo->contarCreditos();
    $totalFiltrados = $modelo->contarCreditos($searchValue);
    $creditos = $modelo->obtenerCreditosPaginado($start, $length, $searchValue);

    header('Content-Type: application/json');
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRegistros,
        "recordsFiltered" => $totalFiltrados,
        "data" => $creditos
    ]);
    exit;
}

if (isset($_POST['accion']) && $_POST['accion'] === 'crear_credito') {
    $resultado = $modelo->crearCredito($_POST['cre_nombre'] ?? '');

    header('Content-Type: application/json');
    echo json_encode($resultado);
    exit;
}

if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_estado') {
    $resultado = $modelo->actualizarEstadoCredito(
        $_POST['idcreditos'] ?? 0,
        $_POST['estado'] ?? ''
    );

    header('Content-Type: application/json');
    echo json_encode($resultado);
    exit;
}

include "../view/Creditos/index.php";
