<?php
require("../../login_autentica.php");
require_once __DIR__ . "/../model/PendientesModel.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function pendienteFirmaLog(string $mensaje, array $contexto = []): void
{
    $directorioLogs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($directorioLogs)) {
        @mkdir($directorioLogs, 0777, true);
    }

    $rutaLog = $directorioLogs . DIRECTORY_SEPARATOR . 'pendiente_firma_controller.log';
    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;

    if (!empty($contexto)) {
        $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $linea .= PHP_EOL;
    @file_put_contents($rutaLog, $linea, FILE_APPEND);
}

pendienteFirmaLog('Inicio controller', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'accion' => $_POST['accion'] ?? '',
    'pendiente_usuario_id_get' => $_GET['pendiente_usuario_id'] ?? null,
    'pendiente_usuario_id_post' => $_POST['pendiente_usuario_id'] ?? null,
    'session_usuario_id' => $_SESSION['usuario_id'] ?? null,
]);

try {
    $modelo = new PendientesModel();
    $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
    pendienteFirmaLog('Modelo instanciado y sesion leida', ['idUsuario' => $idUsuario]);

    if ($idUsuario <= 0) {
        pendienteFirmaLog('Sesion no valida');
        http_response_code(401);
        echo "Sesion no valida";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');

        $accion = $_POST['accion'] ?? '';
        if (!in_array($accion, ['guardar_firma_pendiente', 'guardar_firma_prima'], true)) {
            pendienteFirmaLog('Accion POST invalida', ['accion' => $accion]);
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'La accion solicitada no es valida.',
            ]);
            exit;
        }

        $firma = trim((string) ($_POST['firma'] ?? ''));
        if ($accion === 'guardar_firma_prima') {
            $primaId = (int) ($_POST['prima_id'] ?? 0);
            pendienteFirmaLog('POST guardar_firma_prima', [
                'primaId' => $primaId,
                'firma_length' => strlen($firma),
            ]);

            if ($primaId <= 0 || $firma === '') {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Faltan datos para guardar la firma de prima.',
                ]);
                exit;
            }

            $resultado = $modelo->guardarFirmaPrima($primaId, $idUsuario, $firma);
            pendienteFirmaLog('Resultado guardar_firma_prima', $resultado);
            if (!$resultado['success']) {
                http_response_code(422);
            }

            echo json_encode($resultado);
            exit;
        }

        $pendienteUsuarioId = (int) ($_POST['pendiente_usuario_id'] ?? 0);
        pendienteFirmaLog('POST guardar_firma_pendiente', [
            'pendienteUsuarioId' => $pendienteUsuarioId,
            'firma_length' => strlen($firma),
        ]);

        if ($pendienteUsuarioId <= 0 || $firma === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Faltan datos para guardar la firma.',
            ]);
            exit;
        }

        $resultado = $modelo->guardarFirmaPendiente($pendienteUsuarioId, $idUsuario, $firma);
        pendienteFirmaLog('Resultado guardar_firma_pendiente', $resultado);
        if (!$resultado['success']) {
            http_response_code(422);
        }

        echo json_encode($resultado);
        exit;
    }

    $primaId = (int) ($_GET['prima_id'] ?? 0);
    if ($primaId > 0) {
        pendienteFirmaLog('GET vista firma prima', ['primaId' => $primaId]);
        $pendiente = $modelo->obtenerPrimaParaFirma($primaId, $idUsuario);

        if ($pendiente === null) {
            pendienteFirmaLog('Prima no encontrada para firma');
            http_response_code(404);
            echo "La prima solicitada no existe.";
            exit;
        }

        $pendienteUsuarioId = 0;
        $firmaAccion = 'guardar_firma_prima';
        $firmaIdCampo = 'prima_id';
        $firmaIdValor = $primaId;
        $firmaPostUrl = 'PendienteFirmaController.php';
        $firmaPostMessageId = 'primaId';
        pendienteFirmaLog('Incluyendo vista firma_pdf para prima');
        include __DIR__ . "/../view/Pendientes/firma_pdf.php";
        exit;
    }

    $pendienteUsuarioId = (int) ($_GET['pendiente_usuario_id'] ?? 0);
    pendienteFirmaLog('GET vista firma', ['pendienteUsuarioId' => $pendienteUsuarioId]);
    $pendiente = $modelo->obtenerPendienteUsuarioParaFirma($pendienteUsuarioId, $idUsuario);

    if ($pendiente === null) {
        pendienteFirmaLog('Pendiente no encontrado');
        http_response_code(404);
        echo "El pendiente solicitado no existe.";
        exit;
    }

    if ((int) ($pendiente['requiere_firma'] ?? 0) !== 1) {
        pendienteFirmaLog('Pendiente no firmable', ['requiere_firma' => $pendiente['requiere_firma'] ?? null]);
        http_response_code(422);
        echo "Este pendiente no corresponde a un PDF firmable.";
        exit;
    }

    pendienteFirmaLog('Incluyendo vista firma_pdf');
    include __DIR__ . "/../view/Pendientes/firma_pdf.php";
} catch (Throwable $e) {
    pendienteFirmaLog('EXCEPCION', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo "Error en PendienteFirmaController: " . $e->getMessage();
    exit;
}
