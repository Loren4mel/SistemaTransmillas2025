<?php
require("../../login_autentica.php");
require_once __DIR__ . "/../model/FormulariosModel.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $modelo = new FormulariosModel();
    $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
    $nombreUsuario = $_SESSION['usuario_nombre'] ?? '';

    if ($idUsuario <= 0) {
        http_response_code(401);
        echo "Sesion no valida";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $accion = $_POST['accion'] ?? '';

        if ($accion !== 'responder_formulario') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'La accion solicitada no es valida.',
            ]);
            exit;
        }

        $token = (string) ($_POST['form_token'] ?? '');
        $pendienteUsuarioId = isset($_POST['pendiente_usuario_id']) ? (int) $_POST['pendiente_usuario_id'] : null;
        $respuestas = is_array($_POST['respuesta'] ?? null) ? $_POST['respuesta'] : [];
        $resultado = $modelo->guardarRespuesta($token, $idUsuario, $respuestas, $pendienteUsuarioId);

        if (!$resultado['success']) {
            http_response_code(422);
        }

        echo json_encode($resultado);
        exit;
    }

    $token = (string) ($_GET['form'] ?? '');
    $pendienteUsuarioId = isset($_GET['pendiente_usuario_id']) ? (int) $_GET['pendiente_usuario_id'] : 0;
    $formulario = $modelo->obtenerFormularioPorToken($token);

    if ($formulario === null) {
        http_response_code(404);
        echo "El formulario solicitado no existe o no esta activo.";
        exit;
    }

    include __DIR__ . "/../view/Formularios/responder.php";
} catch (Throwable $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ocurrio un error interno al responder el formulario.',
            'debug' => $e->getMessage(),
        ]);
        exit;
    }

    http_response_code(500);
    echo '<div style="font-family:Arial,sans-serif;padding:24px">';
    echo '<h2>Error al cargar formulario</h2>';
    echo '<p><strong>Detalle:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div>';
    exit;
}
