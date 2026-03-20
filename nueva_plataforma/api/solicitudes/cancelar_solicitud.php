<?php
declare(strict_types=1);

$requestId = '';
try {
    $requestId = bin2hex(random_bytes(8));
} catch (Throwable $e) {
    $requestId = uniqid('req_', true);
}

$inicio = microtime(true);
$responseSent = false;
$debugEnabled = in_array(strtolower((string)(getenv('APP_DEBUG') ?: getenv('API_DEBUG') ?: '0')), ['1', 'true', 'yes'], true);

$logDir = __DIR__ . '/../../logs';
$logFile = $logDir . '/api_solicitudes_cancelar_' . date('Y-m-d') . '.log';

$normalizarLog = static function ($valor, int $depth = 0) use (&$normalizarLog) {
    if ($depth >= 5) {
        return '[max_depth]';
    }

    if (is_array($valor)) {
        $salida = [];
        foreach ($valor as $k => $v) {
            $salida[$k] = $normalizarLog($v, $depth + 1);
        }
        return $salida;
    }

    if (is_object($valor)) {
        return $normalizarLog((array)$valor, $depth + 1);
    }

    if (is_string($valor) && strlen($valor) > 2000) {
        return substr($valor, 0, 2000) . '...[truncated]';
    }

    return $valor;
};

$log = static function (string $nivel, string $evento, array $contexto = []) use ($logDir, $logFile, $requestId, $normalizarLog): void {
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $base = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request_id' => $requestId,
        'nivel' => $nivel,
        'evento' => $evento,
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? '',
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    $linea = array_merge($base, $normalizarLog($contexto));
    $json = json_encode($linea, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return;
    }

    file_put_contents($logFile, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
};

$emitJson = static function (int $status, array $payload) use (&$responseSent): void {
    if (!headers_sent()) {
        http_response_code($status);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    $responseSent = true;
};

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Request-Id: ' . $requestId);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    if (!headers_sent()) {
        http_response_code(204);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (!headers_sent()) {
        header('Allow: POST, OPTIONS');
    }
    $emitJson(405, [
        'ok' => false,
        'mensaje' => 'Metodo no permitido',
        'request_id' => $requestId
    ]);
    exit;
}

$toInt = static function ($valor, int $default = 0): int {
    if ($valor === null || $valor === '') {
        return $default;
    }
    return (int)$valor;
};

$toStr = static function ($valor, string $default = ''): string {
    if ($valor === null) {
        return $default;
    }
    return trim((string)$valor);
};

try {
    $dbConfigPath = __DIR__ . '/../../config/database.php';
    if (!is_file($dbConfigPath)) {
        throw new RuntimeException('No existe database.php');
    }
    require_once $dbConfigPath;

    $apiToken = getenv('WHATSAPP_API_TOKEN') ?: 'MiSuperToken123';

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($authHeader === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
    }

    if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)
        || !hash_equals($apiToken, trim((string)($matches[1] ?? '')))) {
        $emitJson(401, [
            'ok' => false,
            'mensaje' => 'Token invalido',
            'request_id' => $requestId
        ]);
        exit;
    }

    $contentType = strtolower(trim((string)($_SERVER['CONTENT_TYPE'] ?? '')));
    $esJson = strpos($contentType, 'application/json') !== false;

    if ($esJson) {
        $rawBody = (string)file_get_contents('php://input');
        $input = json_decode($rawBody, true);

        if (!is_array($input)) {
            $emitJson(400, [
                'ok' => false,
                'mensaje' => 'JSON invalido',
                'request_id' => $requestId
            ]);
            exit;
        }
    } else {
        $input = is_array($_POST) ? $_POST : [];
    }

    $idServicio = $toInt($input['idservicio'] ?? ($input['id_servicio'] ?? ($input['servicio'] ?? null)), 0);
    $descripcion = $toStr($input['descripcion'] ?? '');
    $idUsuario = 1919;

    if ($idServicio <= 0) {
        $emitJson(422, [
            'ok' => false,
            'mensaje' => 'Debes enviar idservicio/id_servicio/servicio valido',
            'request_id' => $requestId
        ]);
        exit;
    }

    $db = (new Database())->connect();
    $db->begin_transaction();

    $fechatiempo = date('Y-m-d H:i:s');

    $sqlValidacion = "SELECT
                        s.ser_estado,
                        cp.cue_idoperador,
                        u.usu_nombre
                      FROM servicios s
                      LEFT JOIN cuentaspromotor cp ON cp.cue_idservicio = s.idservicios
                      LEFT JOIN usuarios u ON u.idusuarios = cp.cue_idoperador
                      WHERE s.idservicios = ?
                      LIMIT 1";
    $stmtValidacion = $db->prepare($sqlValidacion);
    if (!$stmtValidacion) {
        throw new RuntimeException('Error al preparar validacion de servicio: ' . $db->error);
    }

    $okBindValidacion = $stmtValidacion->bind_param('i', $idServicio);
    if (!$okBindValidacion) {
        throw new RuntimeException('Error bind validacion de servicio: ' . $stmtValidacion->error);
    }

    if (!$stmtValidacion->execute()) {
        throw new RuntimeException('Error ejecutando validacion de servicio: ' . $stmtValidacion->error);
    }

    $resultadoValidacion = $stmtValidacion->get_result();
    $servicioActual = $resultadoValidacion ? $resultadoValidacion->fetch_assoc() : null;
    $stmtValidacion->close();

    if (!$servicioActual) {
        $emitJson(404, [
            'ok' => false,
            'mensaje' => 'No se encontro el servicio solicitado',
            'request_id' => $requestId
        ]);
        exit;
    }

    $estadoActual = (int)($servicioActual['ser_estado'] ?? 0);
    $nombreOperador = $toStr($servicioActual['usu_nombre'] ?? '');
    $idOperador = (int)($servicioActual['cue_idoperador'] ?? 0);

    $partesDescripcion = [];
    if ($descripcion !== '') {
        $partesDescripcion[] = $descripcion;
    }
    $partesDescripcion[] = 'Cancelada por Whatsapp';
    if ($estadoActual === 3 && $idOperador > 0 && $nombreOperador !== '') {
        $partesDescripcion[] = 'asignada a ' . $nombreOperador;
    }
    $descripcion = '' . implode(' - ', $partesDescripcion);

                


    $sqlCuenta = "UPDATE cuentaspromotor
                  SET cue_fecha = ?, cue_estado = 1, cue_idoperador = 0, cue_fecharecogida = '00:00:00'
                  WHERE cue_idservicio = ?";
    $stmtCuenta = $db->prepare($sqlCuenta);
    if (!$stmtCuenta) {
        throw new RuntimeException('Error al preparar UPDATE cuentaspromotor: ' . $db->error);
    }

    $okBindCuenta = $stmtCuenta->bind_param('si', $fechatiempo, $idServicio);
    if (!$okBindCuenta) {
        throw new RuntimeException('Error bind UPDATE cuentaspromotor: ' . $stmtCuenta->error);
    }

    if (!$stmtCuenta->execute()) {
        throw new RuntimeException('Error ejecutando UPDATE cuentaspromotor: ' . $stmtCuenta->error);
    }

    $afectadasCuenta = $stmtCuenta->affected_rows;
    $stmtCuenta->close();

    $sqlServicio = "UPDATE servicios
                    SET ser_idusuarioregistro = ?, ser_fechafinal = ?, ser_estado = 1, ser_descllamada = ? 
                    WHERE idservicios = ?";
    $stmtServicio = $db->prepare($sqlServicio);
    if (!$stmtServicio) {
        throw new RuntimeException('Error al preparar UPDATE servicios: ' . $db->error);
    }

    $okBindServicio = $stmtServicio->bind_param('issi', $idUsuario, $fechatiempo, $descripcion, $idServicio);
    if (!$okBindServicio) {
        throw new RuntimeException('Error bind UPDATE servicios: ' . $stmtServicio->error);
    }

    if (!$stmtServicio->execute()) {
        throw new RuntimeException('Error ejecutando UPDATE servicios: ' . $stmtServicio->error);
    }

    $afectadasServicio = $stmtServicio->affected_rows;
    $stmtServicio->close();

    $db->commit();

    $emitJson(200, [
        'ok' => true,
        'mensaje' => 'Solicitud cancelada correctamente',
        'request_id' => $requestId,
        'resultado' => [
            'idservicio' => $idServicio,
            'idusuario' => $idUsuario,
            'fecha' => $fechatiempo,
            'afectadas_cuentaspromotor' => $afectadasCuenta,
            'afectadas_servicios' => $afectadasServicio
        ]
    ]);

    $log('info', 'solicitud.cancelada', [
        'idservicio' => $idServicio,
        'idusuario' => $idUsuario,
        'afectadas_cuentaspromotor' => $afectadasCuenta,
        'afectadas_servicios' => $afectadasServicio,
        'duracion_total_ms' => (int)round((microtime(true) - $inicio) * 1000)
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        try {
            $db->rollback();
        } catch (Throwable $rollbackError) {
            $log('error', 'transaccion.rollback_error', ['error' => $rollbackError->getMessage()]);
        }
    }

    $payload = [
        'ok' => false,
        'mensaje' => 'Error al cancelar solicitud',
        'request_id' => $requestId
    ];

    if ($debugEnabled) {
        $payload['detalle'] = [
            'error' => $e->getMessage(),
            'archivo' => $e->getFile(),
            'linea' => $e->getLine()
        ];
    }

    $emitJson(500, $payload);

    $log('error', 'solicitud.cancelar_error', [
        'error' => $e->getMessage(),
        'linea' => $e->getLine(),
        'archivo' => $e->getFile(),
        'duracion_total_ms' => (int)round((microtime(true) - $inicio) * 1000)
    ]);
}
