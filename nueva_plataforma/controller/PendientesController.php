<?php
require("../../login_autentica.php");
require_once __DIR__ . "/../model/PendientesModel.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$modelo = new PendientesModel();
$idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
$nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
$rolUsuario = (int) ($_SESSION['usuario_rol'] ?? 0);

if ($idUsuario <= 0) {
    http_response_code(401);
    echo "Sesion no valida";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'marcar_documento_abierto') {
        header('Content-Type: application/json; charset=utf-8');

        $pendienteUsuarioId = (int) ($_POST['pendiente_usuario_id'] ?? 0);
        $resultado = $modelo->marcarDocumentoAbierto($pendienteUsuarioId, $idUsuario);

        echo json_encode([
            'success' => $resultado,
            'message' => $resultado
                ? 'Documento marcado como abierto.'
                : 'No fue posible registrar la apertura del documento.',
        ]);
        exit;
    }

    if ($accion === 'crear_pendiente') {
        header('Content-Type: application/json; charset=utf-8');

        if ($rolUsuario !== 1) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Solo el gerente puede crear pendientes.',
            ]);
            exit;
        }

        $resultado = $modelo->crearPendiente($_POST, $_FILES['documento'] ?? [], $idUsuario);
        if (!$resultado['success']) {
            http_response_code(422);
        }

        echo json_encode($resultado);
        exit;
    }

    if ($accion === 'editar_pendiente') {
        header('Content-Type: application/json; charset=utf-8');

        if ($rolUsuario !== 1) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Solo el gerente puede editar pendientes.',
            ]);
            exit;
        }

        $pendienteId = (int) ($_POST['pendiente_id'] ?? 0);
        $resultado = $modelo->actualizarPendiente($pendienteId, $_POST, $_FILES['documento'] ?? [], $idUsuario);
        if (!$resultado['success']) {
            http_response_code(422);
        }

        echo json_encode($resultado);
        exit;
    }

    if ($accion === 'eliminar_pendiente') {
        header('Content-Type: application/json; charset=utf-8');

        if ($rolUsuario !== 1) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Solo el gerente puede eliminar pendientes.',
            ]);
            exit;
        }

        $pendienteId = (int) ($_POST['pendiente_id'] ?? 0);
        $resultado = $modelo->eliminarPendiente($pendienteId, $idUsuario);
        if (!$resultado['success']) {
            http_response_code(422);
        }

        echo json_encode($resultado);
        exit;
    }

    if ($accion === 'gestionar_pendiente') {
        header('Content-Type: application/json; charset=utf-8');

        $registro = $_POST['registro'] ?? '';
        $fechaInicio = $_POST['fecha_inicio'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $confirmacion = $_POST['confirmacion'] ?? '';
        $observacion = trim($_POST['observacion'] ?? '');

        if (!in_array($confirmacion, ['Si', 'no'], true)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'La confirmacion enviada no es valida.',
            ]);
            exit;
        }

        if ($confirmacion === 'no' && $observacion === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Debes escribir una observacion para rechazar este pendiente.',
            ]);
            exit;
        }

        if ($registro === 'nomina') {
            $resultado = $modelo->confirmarNominaUsuario($idUsuario, $fechaInicio, $tipo, $confirmacion, $observacion);
            if (!$resultado) {
                http_response_code(422);
            }
            echo json_encode([
                'success' => $resultado,
                'message' => $resultado
                    ? 'Pendiente actualizado correctamente.'
                    : 'No fue posible actualizar el pendiente seleccionado.',
            ]);
            exit;
        }

        if ($registro === 'prima') {
            $resultado = $modelo->confirmarPrimaUsuario($idUsuario, $fechaInicio, $confirmacion);
            if (!$resultado) {
                http_response_code(422);
            }
            echo json_encode([
                'success' => $resultado,
                'message' => $resultado
                    ? 'Pendiente actualizado correctamente.'
                    : 'No fue posible actualizar el pendiente seleccionado.',
            ]);
            exit;
        }

        if ($registro === 'personalizado') {
            $pendienteUsuarioId = (int) ($_POST['pendiente_usuario_id'] ?? 0);
            $respuesta = $modelo->confirmarPendientePersonalizado($pendienteUsuarioId, $idUsuario, $confirmacion, $observacion);

            if (!$respuesta['success']) {
                http_response_code(422);
            }

            echo json_encode($respuesta);
            exit;
        }

        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'El tipo de pendiente no es valido.',
        ]);
        exit;
    }
}

$roles = $rolUsuario === 1 ? $modelo->obtenerRoles() : [];
$pendientes = $modelo->obtenerPendientesUsuario($idUsuario, $rolUsuario);
$pendientesCreados = $rolUsuario === 1 ? $modelo->obtenerPendientesCreadosPorGerente($idUsuario) : [];

include __DIR__ . "/../view/Pendientes/index.php";
