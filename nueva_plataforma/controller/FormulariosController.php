<?php
require("../../login_autentica.php");
require_once __DIR__ . "/../model/FormulariosModel.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $modelo = new FormulariosModel();
    $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
    $nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
    $rolUsuario = (int) ($_SESSION['usuario_rol'] ?? 0);
    $puedeAdministrar = in_array($rolUsuario, [1, 4, 12], true);

    if ($idUsuario <= 0) {
        http_response_code(401);
        echo "Sesion no valida";
        exit;
    }

    if (!$puedeAdministrar) {
        http_response_code(403);
        echo "No tienes permisos para administrar formularios.";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'crear_formulario') {
            $resultado = $modelo->crearFormulario($_POST, $_FILES, $idUsuario);
            if (!$resultado['success']) {
                http_response_code(422);
            }
            echo json_encode($resultado);
            exit;
        }

        if ($accion === 'eliminar_formulario') {
            $formularioId = (int) ($_POST['formulario_id'] ?? 0);
            $resultado = $modelo->eliminarFormulario($formularioId);
            if (!$resultado['success']) {
                http_response_code(422);
            }
            echo json_encode($resultado);
            exit;
        }

        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'La accion solicitada no es valida.',
        ]);
        exit;
    }

    $accion = (string) ($_GET['accion'] ?? '');
    if ($accion === 'respuestas') {
        $formularioId = (int) ($_GET['formulario_id'] ?? 0);
        $formulario = $modelo->obtenerFormularioPorId($formularioId);

        if ($formulario === null) {
            http_response_code(404);
            echo "El formulario solicitado no existe o no esta activo.";
            exit;
        }

        $respuestas = $modelo->obtenerRespuestasFormulario($formularioId);
        include __DIR__ . "/../view/Formularios/respuestas.php";
        exit;
    }

    $formularios = $modelo->obtenerFormularios();
    include __DIR__ . "/../view/Formularios/index.php";
} catch (Throwable $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ocurrio un error interno al procesar formularios.',
            'debug' => $e->getMessage(),
        ]);
        exit;
    }

    http_response_code(500);
    echo '<div style="font-family:Arial,sans-serif;padding:24px">';
    echo '<h2>Error al cargar Formularios</h2>';
    echo '<p><strong>Detalle:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div>';
    exit;
}
