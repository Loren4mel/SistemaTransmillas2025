<?php
require("../../login_autentica.php");
require_once __DIR__ . "/../model/PendientesModel.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$modelo = new PendientesModel();
$idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);

if ($idUsuario <= 0) {
    http_response_code(401);
    echo "Sesion no valida";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $accion = $_POST['accion'] ?? '';
    if ($accion !== 'guardar_firma_pendiente') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'La accion solicitada no es valida.',
        ]);
        exit;
    }

    $pendienteUsuarioId = (int) ($_POST['pendiente_usuario_id'] ?? 0);
    $firma = trim((string) ($_POST['firma'] ?? ''));

    if ($pendienteUsuarioId <= 0 || $firma === '') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos para guardar la firma.',
        ]);
        exit;
    }

    $resultado = $modelo->guardarFirmaPendiente($pendienteUsuarioId, $idUsuario, $firma);
    if (!$resultado['success']) {
        http_response_code(422);
    }

    echo json_encode($resultado);
    exit;
}

$pendienteUsuarioId = (int) ($_GET['pendiente_usuario_id'] ?? 0);
$pendiente = $modelo->obtenerPendienteUsuarioParaFirma($pendienteUsuarioId, $idUsuario);

if ($pendiente === null) {
    http_response_code(404);
    echo "El pendiente solicitado no existe.";
    exit;
}

if ((int) ($pendiente['requiere_firma'] ?? 0) !== 1) {
    http_response_code(422);
    echo "Este pendiente no corresponde a un PDF firmable.";
    exit;
}

include __DIR__ . "/../view/Pendientes/firma_pdf.php";
