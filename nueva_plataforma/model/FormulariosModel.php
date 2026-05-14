<?php
require_once "../config/database.php";

class FormulariosModel
{
    private mysqli $db;
    private array $columnasCache = [];
    private string $uploadDir;
    private string $uploadUrlBase = '../uploads/formularios/';

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'formularios';
        $this->asegurarEstructura();
    }

    public function obtenerFormularios(): array
    {
        $sql = "SELECT
                    f.id,
                    f.for_titulo,
                    f.for_descripcion,
                    f.for_contexto_tipo,
                    f.for_contexto_url,
                    f.for_token,
                    f.for_creado_por,
                    f.for_fecha_creacion,
                    f.for_estado,
                    COALESCE(u.usu_nombre, 'Usuario no disponible') AS creador_nombre,
                    COALESCE(p.total_preguntas, 0) AS total_preguntas,
                    COALESCE(r.total_respuestas, 0) AS total_respuestas
                FROM formularios f
                LEFT JOIN usuarios u ON u.idusuarios = f.for_creado_por
                LEFT JOIN (
                    SELECT formulario_id, COUNT(id) AS total_preguntas
                    FROM formularios_preguntas
                    WHERE pre_estado = 1
                    GROUP BY formulario_id
                ) p ON p.formulario_id = f.id
                LEFT JOIN (
                    SELECT formulario_id, COUNT(id) AS total_respuestas
                    FROM formularios_respuestas
                    GROUP BY formulario_id
                ) r ON r.formulario_id = f.id
                WHERE f.for_estado = 1
                ORDER BY f.for_fecha_creacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $resultado;
    }

    public function obtenerFormularioPorId(int $formularioId): ?array
    {
        if ($formularioId <= 0) {
            return null;
        }

        $sql = "SELECT
                    f.id,
                    f.for_titulo,
                    f.for_descripcion,
                    f.for_token,
                    f.for_creado_por,
                    f.for_fecha_creacion,
                    f.for_estado,
                    COALESCE(u.usu_nombre, 'Usuario no disponible') AS creador_nombre
                FROM formularios f
                LEFT JOIN usuarios u ON u.idusuarios = f.for_creado_por
                WHERE f.id = ?
                  AND f.for_estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $formularioId);
        $stmt->execute();
        $formulario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $formulario ?: null;
    }

    public function obtenerRespuestasFormulario(int $formularioId): array
    {
        if ($formularioId <= 0) {
            return [];
        }

        $sql = "SELECT
                    r.id,
                    r.formulario_id,
                    r.usuario_id,
                    r.pendiente_usuario_id,
                    r.res_fecha,
                    COALESCE(u.usu_nombre, 'Usuario no disponible') AS usuario_nombre,
                    COALESCE(u.usu_identificacion, '') AS usuario_identificacion
                FROM formularios_respuestas r
                LEFT JOIN usuarios u ON u.idusuarios = r.usuario_id
                WHERE r.formulario_id = ?
                ORDER BY r.res_fecha DESC, r.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $formularioId);
        $stmt->execute();
        $respuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($respuestas)) {
            return [];
        }

        $ids = array_map(static fn($respuesta) => (int) $respuesta['id'], $respuestas);
        $detallesPorRespuesta = $this->obtenerDetallesRespuestas($ids);

        foreach ($respuestas as &$respuesta) {
            $respuestaId = (int) $respuesta['id'];
            $respuesta['detalles'] = $detallesPorRespuesta[$respuestaId] ?? [];
        }
        unset($respuesta);

        return $respuestas;
    }

    public function crearFormulario(array $data, array $files, int $creadorId): array
    {
        $titulo = trim((string) ($data['titulo'] ?? ''));
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        $contextoTipo = $this->normalizarContextoTipo((string) ($data['contexto_tipo'] ?? 'ninguno'));
        $contextoYoutubeUrl = trim((string) ($data['contexto_youtube_url'] ?? ''));
        $etiquetas = $data['pregunta_etiqueta'] ?? [];
        $tipos = $data['pregunta_tipo'] ?? [];
        $requeridas = $data['pregunta_requerida'] ?? [];
        $opcionesTexto = $data['pregunta_opciones_texto'] ?? [];

        if ($titulo === '') {
            return ['success' => false, 'message' => 'Debes escribir el titulo del formulario.'];
        }

        $contextoUrl = '';
        if ($contextoTipo === 'imagen') {
            $contextoUrl = $this->guardarArchivoImagen($files['contexto_imagen'] ?? null);
            if ($contextoUrl === null) {
                return ['success' => false, 'message' => 'No fue posible guardar la imagen de contexto.'];
            }
        } elseif ($contextoTipo === 'youtube') {
            $contextoUrl = $this->normalizarYoutubeUrl($contextoYoutubeUrl);
            if ($contextoUrl === '') {
                return ['success' => false, 'message' => 'El link de YouTube no es valido.'];
            }
        }

        $preguntas = $this->normalizarPreguntas($etiquetas, $tipos, $requeridas, $opcionesTexto, $files);
        if (empty($preguntas)) {
            return ['success' => false, 'message' => 'Debes agregar al menos una pregunta.'];
        }

        $this->db->begin_transaction();

        try {
            $token = $this->generarTokenFormulario();
            $sqlFormulario = "INSERT INTO formularios
                    (for_titulo, for_descripcion, for_contexto_tipo, for_contexto_url, for_token, for_creado_por, for_fecha_creacion, for_estado)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)";

            $stmtFormulario = $this->db->prepare($sqlFormulario);
            $stmtFormulario->bind_param("sssssi", $titulo, $descripcion, $contextoTipo, $contextoUrl, $token, $creadorId);
            $stmtFormulario->execute();
            $formularioId = (int) $stmtFormulario->insert_id;
            $stmtFormulario->close();

            $sqlPregunta = "INSERT INTO formularios_preguntas
                    (formulario_id, pre_etiqueta, pre_tipo, pre_requerida, pre_imagen, pre_opciones_json, pre_orden, pre_estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";

            $stmtPregunta = $this->db->prepare($sqlPregunta);
            foreach ($preguntas as $indice => $pregunta) {
                $orden = $indice + 1;
                $opcionesJson = json_encode($pregunta['opciones'], JSON_UNESCAPED_UNICODE);
                $stmtPregunta->bind_param(
                    "ississi",
                    $formularioId,
                    $pregunta['etiqueta'],
                    $pregunta['tipo'],
                    $pregunta['requerida'],
                    $pregunta['imagen'],
                    $opcionesJson,
                    $orden
                );
                $stmtPregunta->execute();
            }
            $stmtPregunta->close();

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Formulario creado correctamente.',
                'formulario_id' => $formularioId,
                'token' => $token,
                'link' => '../controller/FormularioResponderController.php?form=' . rawurlencode($token),
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'No fue posible crear el formulario.'];
        }
    }

    public function eliminarFormulario(int $formularioId): array
    {
        if ($formularioId <= 0) {
            return ['success' => false, 'message' => 'El formulario seleccionado no es valido.'];
        }

        $sql = "UPDATE formularios
                SET for_estado = 0
                WHERE id = ?
                  AND for_estado = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $formularioId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return [
            'success' => $ok,
            'message' => $ok
                ? 'Formulario eliminado correctamente.'
                : 'No fue posible eliminar el formulario.',
        ];
    }

    public function obtenerFormularioPorToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $sqlFormulario = "SELECT
                    id,
                    for_titulo,
                    for_descripcion,
                    for_contexto_tipo,
                    for_contexto_url,
                    for_token
                FROM formularios
                WHERE for_token = ?
                  AND for_estado = 1
                LIMIT 1";

        $stmtFormulario = $this->db->prepare($sqlFormulario);
        $stmtFormulario->bind_param("s", $token);
        $stmtFormulario->execute();
        $formulario = $stmtFormulario->get_result()->fetch_assoc();
        $stmtFormulario->close();

        if (!$formulario) {
            return null;
        }

        $sqlPreguntas = "SELECT
                    id,
                    pre_etiqueta,
                    pre_tipo,
                    pre_requerida,
                    pre_imagen,
                    pre_opciones_json,
                    pre_orden
                FROM formularios_preguntas
                WHERE formulario_id = ?
                  AND pre_estado = 1
                ORDER BY pre_orden ASC, id ASC";

        $formularioId = (int) $formulario['id'];
        $stmtPreguntas = $this->db->prepare($sqlPreguntas);
        $stmtPreguntas->bind_param("i", $formularioId);
        $stmtPreguntas->execute();
        $resultadoPreguntas = $stmtPreguntas->get_result();

        $preguntas = [];
        while ($pregunta = $resultadoPreguntas->fetch_assoc()) {
            $opciones = json_decode((string) ($pregunta['pre_opciones_json'] ?? '[]'), true);
            $pregunta['opciones'] = $this->normalizarOpcionesGuardadas(is_array($opciones) ? $opciones : []);
            $preguntas[] = $pregunta;
        }
        $stmtPreguntas->close();

        $formulario['preguntas'] = $preguntas;
        return $formulario;
    }

    public function guardarRespuesta(string $token, int $usuarioId, array $respuestas, ?int $pendienteUsuarioId = null): array
    {
        $formulario = $this->obtenerFormularioPorToken($token);
        if ($formulario === null) {
            return ['success' => false, 'message' => 'El formulario solicitado no existe o no esta activo.'];
        }

        if (empty($formulario['preguntas'])) {
            return ['success' => false, 'message' => 'Este formulario no tiene preguntas activas.'];
        }

        $valores = [];
        foreach ($formulario['preguntas'] as $pregunta) {
            $preguntaId = (int) $pregunta['id'];
            $tipo = (string) $pregunta['pre_tipo'];
            $valorCrudo = $respuestas[$preguntaId] ?? null;
            $valor = $this->normalizarValorRespuesta($tipo, $valorCrudo);

            if ((int) $pregunta['pre_requerida'] === 1 && $valor === '') {
                return [
                    'success' => false,
                    'message' => 'Debes responder la pregunta: ' . $pregunta['pre_etiqueta'],
                ];
            }

            $valores[$preguntaId] = $valor;
        }

        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $formularioId = (int) $formulario['id'];
        $pendienteId = $pendienteUsuarioId !== null && $pendienteUsuarioId > 0 ? $pendienteUsuarioId : null;

        $this->db->begin_transaction();

        try {
            $sqlRespuesta = "INSERT INTO formularios_respuestas
                    (formulario_id, usuario_id, pendiente_usuario_id, res_fecha, res_ip, res_user_agent)
                VALUES (?, ?, ?, NOW(), ?, ?)";

            $stmtRespuesta = $this->db->prepare($sqlRespuesta);
            $stmtRespuesta->bind_param("iiiss", $formularioId, $usuarioId, $pendienteId, $ip, $userAgent);
            $stmtRespuesta->execute();
            $respuestaId = (int) $stmtRespuesta->insert_id;
            $stmtRespuesta->close();

            $sqlDetalle = "INSERT INTO formularios_respuestas_detalle
                    (respuesta_id, pregunta_id, det_valor)
                VALUES (?, ?, ?)";

            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($valores as $preguntaId => $valor) {
                $stmtDetalle->bind_param("iis", $respuestaId, $preguntaId, $valor);
                $stmtDetalle->execute();
            }
            $stmtDetalle->close();

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Respuesta enviada correctamente.',
                'respuesta_id' => $respuestaId,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'No fue posible guardar la respuesta.'];
        }
    }

    private function normalizarPreguntas(array $etiquetas, array $tipos, array $requeridas, array $opcionesTexto, array $files): array
    {
        $preguntas = [];
        $tiposPermitidos = ['texto_corto', 'texto_largo', 'seleccion_unica', 'seleccion_multiple', 'fecha'];

        foreach ($etiquetas as $indice => $etiquetaCruda) {
            $etiqueta = trim((string) $etiquetaCruda);
            if ($etiqueta === '') {
                continue;
            }

            $tipo = (string) ($tipos[$indice] ?? 'texto_corto');
            if (!in_array($tipo, $tiposPermitidos, true)) {
                $tipo = 'texto_corto';
            }

            $opcionesPregunta = [];
            if (in_array($tipo, ['seleccion_unica', 'seleccion_multiple'], true)) {
                $textos = is_array($opcionesTexto[$indice] ?? null) ? $opcionesTexto[$indice] : [];
                foreach ($textos as $opcionIndice => $opcionTexto) {
                    $valor = trim((string) $opcionTexto);
                    if ($valor !== '') {
                        $imagenOpcion = $this->guardarArchivoImagenDesdeLista($files['pregunta_opciones_imagen'] ?? null, $indice, (int) $opcionIndice) ?? '';
                        $opcionesPregunta[] = [
                            'texto' => $valor,
                            'imagen' => $imagenOpcion,
                        ];
                    }
                }

                if (empty($opcionesPregunta)) {
                    continue;
                }
            }

            $preguntas[] = [
                'etiqueta' => $etiqueta,
                'tipo' => $tipo,
                'requerida' => ((string) ($requeridas[$indice] ?? '0')) === '1' ? 1 : 0,
                'imagen' => $this->guardarArchivoImagenDesdeLista($files['pregunta_imagen'] ?? null, $indice) ?? '',
                'opciones' => $opcionesPregunta,
            ];
        }

        return $preguntas;
    }

    private function obtenerDetallesRespuestas(array $respuestaIds): array
    {
        $respuestaIds = array_values(array_filter(array_map('intval', $respuestaIds), static fn($id) => $id > 0));
        if (empty($respuestaIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($respuestaIds), '?'));
        $sql = "SELECT
                    d.respuesta_id,
                    d.pregunta_id,
                    d.det_valor,
                    p.pre_etiqueta,
                    p.pre_tipo,
                    p.pre_orden
                FROM formularios_respuestas_detalle d
                LEFT JOIN formularios_preguntas p ON p.id = d.pregunta_id
                WHERE d.respuesta_id IN ($placeholders)
                ORDER BY d.respuesta_id ASC, p.pre_orden ASC, p.id ASC";

        $stmt = $this->db->prepare($sql);
        $tipos = str_repeat('i', count($respuestaIds));
        $stmt->bind_param($tipos, ...$respuestaIds);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $detalles = [];
        while ($detalle = $resultado->fetch_assoc()) {
            $respuestaId = (int) $detalle['respuesta_id'];
            $detalles[$respuestaId][] = [
                'pregunta_id' => (int) $detalle['pregunta_id'],
                'pregunta' => $detalle['pre_etiqueta'] ?: ('Pregunta ' . (int) $detalle['pregunta_id']),
                'tipo' => (string) ($detalle['pre_tipo'] ?? ''),
                'valor' => $this->formatearValorRespuesta((string) ($detalle['det_valor'] ?? '')),
            ];
        }
        $stmt->close();

        return $detalles;
    }

    private function formatearValorRespuesta(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return 'Sin respuesta';
        }

        $json = json_decode($valor, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $valores = array_values(array_filter(array_map(static fn($item) => trim((string) $item), $json), static fn($item) => $item !== ''));
            return empty($valores) ? 'Sin respuesta' : implode(', ', $valores);
        }

        return $valor;
    }

    private function normalizarOpcionesGuardadas(array $opciones): array
    {
        $normalizadas = [];
        foreach ($opciones as $opcion) {
            if (is_array($opcion)) {
                $texto = trim((string) ($opcion['texto'] ?? ''));
                if ($texto !== '') {
                    $normalizadas[] = [
                        'texto' => $texto,
                        'imagen' => trim((string) ($opcion['imagen'] ?? '')),
                    ];
                }
                continue;
            }

            $texto = trim((string) $opcion);
            if ($texto !== '') {
                $normalizadas[] = [
                    'texto' => $texto,
                    'imagen' => '',
                ];
            }
        }

        return $normalizadas;
    }

    private function normalizarContextoTipo(string $tipo): string
    {
        return in_array($tipo, ['imagen', 'youtube'], true) ? $tipo : 'ninguno';
    }

    private function normalizarYoutubeUrl(string $url): string
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (strpos($host, 'youtube.com') === false && strpos($host, 'youtu.be') === false) {
            return '';
        }

        return $url;
    }

    public function obtenerYoutubeEmbedUrl(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $videoId = '';
        if (strpos($host, 'youtu.be') !== false) {
            $videoId = explode('/', $path)[0] ?? '';
        } elseif (strpos($path, 'embed/') !== false) {
            $partes = explode('embed/', $path);
            $videoId = $partes[1] ?? '';
        } elseif (strpos($path, 'shorts/') !== false) {
            $partes = explode('shorts/', $path);
            $videoId = $partes[1] ?? '';
        } elseif (strpos($path, 'live/') !== false) {
            $partes = explode('live/', $path);
            $videoId = $partes[1] ?? '';
        } else {
            $videoId = (string) ($query['v'] ?? '');
        }

        $videoId = explode('/', $videoId)[0] ?? '';
        $videoId = explode('?', $videoId)[0] ?? '';
        $videoId = explode('&', $videoId)[0] ?? '';
        $videoId = preg_replace('/[^A-Za-z0-9_-]/', '', $videoId);
        return $videoId !== '' ? 'https://www.youtube.com/embed/' . $videoId : '';
    }

    private function guardarArchivoImagen(?array $file): ?string
    {
        if (empty($file) || (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
            return '';
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($extension, $permitidas, true)) {
            return null;
        }

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
        }

        $nombre = 'form_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destino = $this->uploadDir . DIRECTORY_SEPARATOR . $nombre;
        if (!move_uploaded_file((string) $file['tmp_name'], $destino)) {
            return null;
        }

        return $this->uploadUrlBase . $nombre;
    }

    private function guardarArchivoImagenDesdeLista(?array $file, int $indice, ?int $subindice = null): ?string
    {
        if (empty($file) || !isset($file['name'][$indice])) {
            return '';
        }

        if ($subindice === null) {
            $archivo = [
                'name' => $file['name'][$indice] ?? '',
                'type' => $file['type'][$indice] ?? '',
                'tmp_name' => $file['tmp_name'][$indice] ?? '',
                'error' => $file['error'][$indice] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file['size'][$indice] ?? 0,
            ];
            return $this->guardarArchivoImagen($archivo);
        }

        if (!isset($file['name'][$indice][$subindice])) {
            return '';
        }

        $archivo = [
            'name' => $file['name'][$indice][$subindice] ?? '',
            'type' => $file['type'][$indice][$subindice] ?? '',
            'tmp_name' => $file['tmp_name'][$indice][$subindice] ?? '',
            'error' => $file['error'][$indice][$subindice] ?? UPLOAD_ERR_NO_FILE,
            'size' => $file['size'][$indice][$subindice] ?? 0,
        ];

        return $this->guardarArchivoImagen($archivo);
    }

    private function normalizarValorRespuesta(string $tipo, $valorCrudo): string
    {
        if (is_array($valorCrudo)) {
            $valores = [];
            foreach ($valorCrudo as $valor) {
                $texto = trim((string) $valor);
                if ($texto !== '') {
                    $valores[] = $texto;
                }
            }

            return json_encode($valores, JSON_UNESCAPED_UNICODE);
        }

        $valor = trim((string) ($valorCrudo ?? ''));
        if ($tipo === 'fecha' && $valor !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return '';
        }

        return $valor;
    }

    private function generarTokenFormulario(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
            $stmt = $this->db->prepare("SELECT id FROM formularios WHERE for_token = ? LIMIT 1");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $existe = $stmt->get_result()->num_rows > 0;
            $stmt->close();
        } while ($existe);

        return $token;
    }

    private function asegurarEstructura(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS formularios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            for_titulo VARCHAR(180) NOT NULL,
            for_descripcion TEXT NULL,
            for_contexto_tipo VARCHAR(20) NOT NULL DEFAULT 'ninguno',
            for_contexto_url VARCHAR(255) NULL,
            for_token VARCHAR(64) NOT NULL,
            for_creado_por INT NOT NULL,
            for_fecha_creacion DATETIME NOT NULL,
            for_estado TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uq_formularios_token (for_token),
            KEY idx_formularios_creador (for_creado_por)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS formularios_preguntas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            formulario_id INT NOT NULL,
            pre_etiqueta VARCHAR(255) NOT NULL,
            pre_tipo VARCHAR(40) NOT NULL,
            pre_requerida TINYINT(1) NOT NULL DEFAULT 0,
            pre_imagen VARCHAR(255) NULL,
            pre_opciones_json TEXT NULL,
            pre_orden INT NOT NULL DEFAULT 1,
            pre_estado TINYINT(1) NOT NULL DEFAULT 1,
            KEY idx_formularios_preguntas_formulario (formulario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS formularios_respuestas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            formulario_id INT NOT NULL,
            usuario_id INT NULL,
            pendiente_usuario_id INT NULL,
            res_fecha DATETIME NOT NULL,
            res_ip VARCHAR(45) NULL,
            res_user_agent VARCHAR(255) NULL,
            KEY idx_formularios_respuestas_formulario (formulario_id),
            KEY idx_formularios_respuestas_pendiente_usuario (pendiente_usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS formularios_respuestas_detalle (
            id INT AUTO_INCREMENT PRIMARY KEY,
            respuesta_id INT NOT NULL,
            pregunta_id INT NOT NULL,
            det_valor TEXT NULL,
            KEY idx_formularios_respuestas_detalle_respuesta (respuesta_id),
            KEY idx_formularios_respuestas_detalle_pregunta (pregunta_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->agregarColumnaSiNoExiste('formularios', 'for_contexto_tipo', "ALTER TABLE formularios ADD COLUMN for_contexto_tipo VARCHAR(20) NOT NULL DEFAULT 'ninguno' AFTER for_descripcion");
        $this->agregarColumnaSiNoExiste('formularios', 'for_contexto_url', "ALTER TABLE formularios ADD COLUMN for_contexto_url VARCHAR(255) NULL AFTER for_contexto_tipo");
        $this->agregarColumnaSiNoExiste('formularios_preguntas', 'pre_imagen', "ALTER TABLE formularios_preguntas ADD COLUMN pre_imagen VARCHAR(255) NULL AFTER pre_requerida");
    }

    private function agregarColumnaSiNoExiste(string $tabla, string $columna, string $sql): void
    {
        if (!$this->columnaExiste($tabla, $columna)) {
            $this->db->query($sql);
            $this->columnasCache[$tabla][$columna] = true;
        }
    }

    private function columnaExiste(string $tabla, string $columna): bool
    {
        if (isset($this->columnasCache[$tabla][$columna])) {
            return $this->columnasCache[$tabla][$columna];
        }

        $tablaSegura = $this->db->real_escape_string($tabla);
        $columnaSegura = $this->db->real_escape_string($columna);
        $result = $this->db->query("SHOW COLUMNS FROM `{$tablaSegura}` LIKE '{$columnaSegura}'");
        $existe = $result && $result->num_rows > 0;
        $this->columnasCache[$tabla][$columna] = $existe;

        return $existe;
    }
}
