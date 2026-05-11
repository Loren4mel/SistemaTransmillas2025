<?php
require("../../login_autentica.php");
require_once __DIR__ . "/../model/PendientesModel.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function pendientesControllerLog(string $mensaje, array $contexto = []): void
{
    $directorioLogs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($directorioLogs)) {
        @mkdir($directorioLogs, 0777, true);
    }

    $rutaLog = $directorioLogs . DIRECTORY_SEPARATOR . 'pendientes_controller.log';
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;

    if (!empty($contexto)) {
        $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $linea .= PHP_EOL;
    @file_put_contents($rutaLog, $linea, FILE_APPEND);
}

pendientesControllerLog('Inicio controller', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'accion' => $_POST['accion'] ?? '',
    'session_usuario_id' => $_SESSION['usuario_id'] ?? null,
    'session_usuario_rol' => $_SESSION['usuario_rol'] ?? null,
]);

try {
    pendientesControllerLog('Antes de instanciar modelo');
    $modelo = new PendientesModel();
    pendientesControllerLog('Modelo instanciado');

    $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
    $nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
    $rolUsuario = (int) ($_SESSION['usuario_rol'] ?? 0);
    $puedeAdministrarPendientes = $rolUsuario === 1;
    $puedeVerSeguimientoPendientes = in_array($rolUsuario, [1, 12], true);

    pendientesControllerLog('Sesion leida', [
        'idUsuario' => $idUsuario,
        'nombreUsuario' => $nombreUsuario,
        'rolUsuario' => $rolUsuario,
    ]);

    if ($idUsuario <= 0) {
        pendientesControllerLog('Sesion no valida');
        http_response_code(401);
        echo "Sesion no valida";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';
        pendientesControllerLog('Procesando POST', ['accion' => $accion]);

        if ($accion === 'marcar_documento_abierto') {
            header('Content-Type: application/json; charset=utf-8');

            $pendienteUsuarioId = (int) ($_POST['pendiente_usuario_id'] ?? 0);
            pendientesControllerLog('POST marcar_documento_abierto', ['pendienteUsuarioId' => $pendienteUsuarioId]);
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

            if (!$puedeAdministrarPendientes) {
                pendientesControllerLog('Crear pendiente denegado por rol', ['rolUsuario' => $rolUsuario]);
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo el gerente puede crear pendientes.',
                ]);
                exit;
            }

            $resultado = $modelo->crearPendiente($_POST, $_FILES['documento'] ?? [], $idUsuario);
            pendientesControllerLog('Resultado crear_pendiente', $resultado);
            if (!$resultado['success']) {
                http_response_code(422);
            }

            echo json_encode($resultado);
            exit;
        }

        if ($accion === 'editar_pendiente') {
            header('Content-Type: application/json; charset=utf-8');

            if (!$puedeAdministrarPendientes) {
                pendientesControllerLog('Editar pendiente denegado por rol', ['rolUsuario' => $rolUsuario]);
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo el gerente puede editar pendientes.',
                ]);
                exit;
            }

            $pendienteId = (int) ($_POST['pendiente_id'] ?? 0);
            pendientesControllerLog('POST editar_pendiente', ['pendienteId' => $pendienteId]);
            $resultado = $modelo->actualizarPendiente($pendienteId, $_POST, $_FILES['documento'] ?? [], $idUsuario);
            pendientesControllerLog('Resultado editar_pendiente', $resultado);
            if (!$resultado['success']) {
                http_response_code(422);
            }

            echo json_encode($resultado);
            exit;
        }

        if ($accion === 'eliminar_pendiente') {
            header('Content-Type: application/json; charset=utf-8');

            if (!$puedeAdministrarPendientes) {
                pendientesControllerLog('Eliminar pendiente denegado por rol', ['rolUsuario' => $rolUsuario]);
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo el gerente puede eliminar pendientes.',
                ]);
                exit;
            }

            $pendienteId = (int) ($_POST['pendiente_id'] ?? 0);
            pendientesControllerLog('POST eliminar_pendiente', ['pendienteId' => $pendienteId]);
            $resultado = $modelo->eliminarPendiente($pendienteId, $idUsuario);
            pendientesControllerLog('Resultado eliminar_pendiente', $resultado);
            if (!$resultado['success']) {
                http_response_code(422);
            }

            echo json_encode($resultado);
            exit;
        }

        if ($accion === 'eliminar_usuario_pendiente') {
            header('Content-Type: application/json; charset=utf-8');

            if (!$puedeAdministrarPendientes) {
                pendientesControllerLog('Eliminar usuario pendiente denegado por rol', ['rolUsuario' => $rolUsuario]);
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo el gerente puede modificar usuarios asignados.',
                ]);
                exit;
            }

            $pendienteId = (int) ($_POST['pendiente_id'] ?? 0);
            $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
            pendientesControllerLog('POST eliminar_usuario_pendiente', [
                'pendienteId' => $pendienteId,
                'usuarioId' => $usuarioId,
            ]);
            $resultado = $modelo->eliminarUsuarioPendiente($pendienteId, $usuarioId, $idUsuario);
            pendientesControllerLog('Resultado eliminar_usuario_pendiente', $resultado);
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

            pendientesControllerLog('POST gestionar_pendiente', [
                'registro' => $registro,
                'fechaInicio' => $fechaInicio,
                'tipo' => $tipo,
                'confirmacion' => $confirmacion,
            ]);

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
                pendientesControllerLog('Resultado gestionar nomina', ['success' => $resultado]);
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
                pendientesControllerLog('Resultado gestionar prima', ['success' => $resultado]);
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
                pendientesControllerLog('Gestionar personalizado', ['pendienteUsuarioId' => $pendienteUsuarioId]);
                $respuesta = $modelo->confirmarPendientePersonalizado($pendienteUsuarioId, $idUsuario, $confirmacion, $observacion);
                pendientesControllerLog('Resultado gestionar personalizado', $respuesta);

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

    pendientesControllerLog('Antes de cargar datos de vista');
    $roles = $puedeAdministrarPendientes ? $modelo->obtenerRoles() : [];
    $usuariosAsignables = $puedeAdministrarPendientes ? $modelo->obtenerUsuariosAsignables() : [];
    $pendientes = $modelo->obtenerPendientesUsuario($idUsuario, $rolUsuario);
    $pendientesCreados = $puedeVerSeguimientoPendientes ? $modelo->obtenerPendientesCreadosPorGerente($idUsuario) : [];
    pendientesControllerLog('Datos de vista cargados', [
        'roles' => count($roles),
        'usuariosAsignables' => count($usuariosAsignables),
        'pendientes' => count($pendientes),
        'pendientesCreados' => count($pendientesCreados),
    ]);

    pendientesControllerLog('Antes de incluir vista');
    include __DIR__ . "/../view/Pendientes/index.php";
    pendientesControllerLog('Vista incluida');
} catch (Throwable $e) {
    pendientesControllerLog('EXCEPCION', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ocurrio un error interno al procesar pendientes.',
            'debug' => $e->getMessage(),
        ]);
        exit;
    }

    http_response_code(500);
    echo '<div style="font-family:Arial,sans-serif;padding:24px">';
    echo '<h2>Error al cargar Pendientes</h2>';
    echo '<p><strong>Detalle:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><strong>Linea:</strong> ' . (int) $e->getLine() . '</p>';
    echo '</div>';
    exit;
}
