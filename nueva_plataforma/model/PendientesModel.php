<?php
require_once "../config/database.php";

class PendientesModel
{
    private mysqli $db;
    private string $uploadDir;
    private string $uploadUrlBase = '../uploads/pendientes/';
    private string $logFile;

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pendientes';
        $this->logFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'pendientes.log';
        $this->asegurarEstructura();
    }

    public function obtenerPendientesUsuario(int $idUsuario, int $rolUsuario = 0): array
    {
        $pendientes = [];

        if ($rolUsuario > 0) {
            $tipoContratoUsuario = $this->obtenerTipoContratoUsuario($idUsuario);
            $this->sincronizarPendientesPorRol($idUsuario, $rolUsuario, $tipoContratoUsuario);
        }

        $sqlNomina = "SELECT
                nom_id,
                nom_fecha_inicio,
                nom_fecha_fin,
                nom_tipo_pago,
                nom_cuentaCobro,
                nom_motivoObser
            FROM nomina
            WHERE nom_id_usu = ?
              AND (nom_confirmaUsu = '' OR nom_confirmaUsu = 'no' OR nom_confirmaUsu IS NULL)
              AND nom_cuentaCobro IS NOT NULL
              AND nom_cuentaCobro <> ''
            ORDER BY nom_fecha_inicio DESC";

        $stmtNomina = $this->db->prepare($sqlNomina);
        $stmtNomina->bind_param("i", $idUsuario);
        $stmtNomina->execute();
        $resultNomina = $stmtNomina->get_result();

        while ($row = $resultNomina->fetch_assoc()) {
            $pendientes[] = [
                'registro' => 'nomina',
                'id' => (int) $row['nom_id'],
                'fecha_inicio' => $row['nom_fecha_inicio'],
                'fecha_fin' => $row['nom_fecha_fin'],
                'tipo' => $row['nom_tipo_pago'],
                'concepto' => $this->mapearConceptoNomina($row['nom_tipo_pago']),
                'documento' => $row['nom_cuentaCobro'],
                'observacion' => $row['nom_motivoObser'] ?? '',
                'documento_abierto' => 1,
                'puede_confirmar' => 1,
                'detalle' => 'Pendiente de nomina',
            ];
        }
        $stmtNomina->close();

        $sqlPrimas = "SELECT
                idprimas,
                pri_fecha_inicio,
                pri_fecha_fin,
                pri_semestre,
                pri_docprima
            FROM primas
            WHERE pri_idusu = ?
              AND (pri_confirmaUsus = '' OR pri_confirmaUsus = 'no' OR pri_confirmaUsus IS NULL)
              AND pri_docprima IS NOT NULL
              AND pri_docprima <> ''
            ORDER BY pri_fecha_inicio DESC";

        $stmtPrimas = $this->db->prepare($sqlPrimas);
        $stmtPrimas->bind_param("i", $idUsuario);
        $stmtPrimas->execute();
        $resultPrimas = $stmtPrimas->get_result();

        while ($row = $resultPrimas->fetch_assoc()) {
            $pendientes[] = [
                'registro' => 'prima',
                'id' => (int) $row['idprimas'],
                'fecha_inicio' => $row['pri_fecha_inicio'],
                'fecha_fin' => $row['pri_fecha_fin'],
                'tipo' => 'prima',
                'concepto' => 'Liquidacion prima',
                'documento' => $row['pri_docprima'],
                'observacion' => '',
                'documento_abierto' => 1,
                'puede_confirmar' => 1,
                'detalle' => 'Pendiente de prima',
            ];
        }
        $stmtPrimas->close();

        $sqlPersonalizados = "SELECT
                pu.id AS pendiente_usuario_id,
                pu.pu_observacion,
                pu.pu_documento_abierto,
                pc.id AS pendiente_id,
                pc.pen_titulo,
                pc.pen_descripcion,
                pc.pen_documento,
                pc.pen_fecha_creacion
            FROM pendientes_creados_usuarios pu
            INNER JOIN pendientes_creados pc ON pc.id = pu.pendiente_id
            WHERE pu.usuario_id = ?
              AND pc.pen_estado = 1
              AND (pu.pu_confirmacion IS NULL OR pu.pu_confirmacion = '' OR pu.pu_confirmacion = 'no')
            ORDER BY pc.pen_fecha_creacion DESC";

        $stmtPersonalizados = $this->db->prepare($sqlPersonalizados);
        $stmtPersonalizados->bind_param("i", $idUsuario);
        $stmtPersonalizados->execute();
        $resultPersonalizados = $stmtPersonalizados->get_result();

        while ($row = $resultPersonalizados->fetch_assoc()) {
            $pendientes[] = [
                'registro' => 'personalizado',
                'id' => (int) $row['pendiente_id'],
                'pendiente_usuario_id' => (int) $row['pendiente_usuario_id'],
                'fecha_inicio' => $row['pen_fecha_creacion'],
                'fecha_fin' => $row['pen_fecha_creacion'],
                'tipo' => 'personalizado',
                'concepto' => $row['pen_titulo'],
                'detalle' => $row['pen_descripcion'] ?? '',
                'documento' => $row['pen_documento'],
                'observacion' => $row['pu_observacion'] ?? '',
                'documento_abierto' => (int) $row['pu_documento_abierto'],
                'puede_confirmar' => (int) $row['pu_documento_abierto'],
            ];
        }
        $stmtPersonalizados->close();

        usort($pendientes, static function (array $a, array $b): int {
            return strcmp($b['fecha_inicio'], $a['fecha_inicio']);
        });

        return $pendientes;
    }

    public function obtenerRoles(): array
    {
        $sql = "SELECT idroles, rol_nombre FROM roles ORDER BY rol_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerPendientesCreadosPorGerente(int $creadorId): array
    {
        $pendientes = [];

        $sqlPendientes = "SELECT
                    id,
                    pen_titulo,
                    pen_descripcion,
                    pen_documento,
                    pen_fecha_creacion,
                    pen_estado
                FROM pendientes_creados
                WHERE pen_creado_por = ?
                  AND pen_estado = 1
                ORDER BY pen_fecha_creacion DESC";

        $stmtPendientes = $this->db->prepare($sqlPendientes);
        $stmtPendientes->bind_param("i", $creadorId);
        $stmtPendientes->execute();
        $resultPendientes = $stmtPendientes->get_result();

        while ($row = $resultPendientes->fetch_assoc()) {
            $pendienteId = (int) $row['id'];
            $pendientes[$pendienteId] = [
                'id' => $pendienteId,
                'titulo' => $row['pen_titulo'],
                'descripcion' => $row['pen_descripcion'] ?? '',
                'documento' => $row['pen_documento'],
                'fecha_creacion' => $row['pen_fecha_creacion'],
                'estado' => (int) $row['pen_estado'],
                'total_asignados' => 0,
                'total_aceptados' => 0,
                'total_rechazados' => 0,
                'total_pendientes' => 0,
                'roles_ids' => [],
                'roles_nombres' => [],
                'tipos_contrato' => [],
                'usuarios' => [],
            ];
        }

        $stmtPendientes->close();

        if (empty($pendientes)) {
            return [];
        }

        $idsPendientes = array_keys($pendientes);
        $marcadores = implode(',', array_fill(0, count($idsPendientes), '?'));
        $tipos = str_repeat('i', count($idsPendientes));

        $sqlRoles = "SELECT
                        pr.pendiente_id,
                        pr.rol_id,
                        r.rol_nombre
                    FROM pendientes_creados_roles pr
                    INNER JOIN roles r ON r.idroles = pr.rol_id
                    WHERE pr.pendiente_id IN ($marcadores)
                    ORDER BY r.rol_nombre ASC";

        $stmtRoles = $this->db->prepare($sqlRoles);
        $stmtRoles->bind_param($tipos, ...$idsPendientes);
        $stmtRoles->execute();
        $resultRoles = $stmtRoles->get_result();

        while ($row = $resultRoles->fetch_assoc()) {
            $pendienteId = (int) $row['pendiente_id'];
            if (!isset($pendientes[$pendienteId])) {
                continue;
            }

            $pendientes[$pendienteId]['roles_ids'][] = (int) $row['rol_id'];
            $pendientes[$pendienteId]['roles_nombres'][] = $row['rol_nombre'];
        }

        $stmtRoles->close();

        $sqlTiposContrato = "SELECT pendiente_id, tipo_contrato
                             FROM pendientes_creados_tipos_contrato
                             WHERE pendiente_id IN ($marcadores)
                             ORDER BY tipo_contrato ASC";

        $stmtTiposContrato = $this->db->prepare($sqlTiposContrato);
        $stmtTiposContrato->bind_param($tipos, ...$idsPendientes);
        $stmtTiposContrato->execute();
        $resultTiposContrato = $stmtTiposContrato->get_result();

        while ($row = $resultTiposContrato->fetch_assoc()) {
            $pendienteId = (int) $row['pendiente_id'];
            if (!isset($pendientes[$pendienteId])) {
                continue;
            }

            $pendientes[$pendienteId]['tipos_contrato'][] = $row['tipo_contrato'];
        }

        $stmtTiposContrato->close();

        $sqlUsuarios = "SELECT
                    pu.pendiente_id,
                    u.idusuarios AS usuario_id,
                    u.usu_nombre,
                    r.rol_nombre,
                    pu.pu_confirmacion,
                    pu.pu_observacion,
                    pu.pu_documento_abierto,
                    pu.pu_fecha_documento_abierto,
                    pu.pu_fecha_confirmacion
                FROM pendientes_creados_usuarios pu
                LEFT JOIN usuarios u ON u.idusuarios = pu.usuario_id
                LEFT JOIN roles r ON r.idroles = pu.rol_id
                WHERE pu.pendiente_id IN ($marcadores)
                ORDER BY u.usu_nombre ASC";

        $stmtUsuarios = $this->db->prepare($sqlUsuarios);
        $stmtUsuarios->bind_param($tipos, ...$idsPendientes);
        $stmtUsuarios->execute();
        $result = $stmtUsuarios->get_result();

        while ($row = $result->fetch_assoc()) {
            $pendienteId = (int) $row['pendiente_id'];
            if (!empty($row['usuario_id'])) {
                $confirmacion = (string) ($row['pu_confirmacion'] ?? '');
                $estadoUsuario = 'Pendiente';

                if ($confirmacion === 'Si') {
                    $estadoUsuario = 'Aceptado';
                    $pendientes[$pendienteId]['total_aceptados']++;
                } elseif ($confirmacion === 'no') {
                    $estadoUsuario = 'Rechazado';
                    $pendientes[$pendienteId]['total_rechazados']++;
                } else {
                    $pendientes[$pendienteId]['total_pendientes']++;
                }

                $pendientes[$pendienteId]['total_asignados']++;
                $pendientes[$pendienteId]['usuarios'][] = [
                    'usuario_id' => (int) $row['usuario_id'],
                    'usuario_nombre' => $row['usu_nombre'] ?? 'Sin nombre',
                    'rol_nombre' => $row['rol_nombre'] ?? 'Sin rol',
                    'estado' => $estadoUsuario,
                    'confirmacion' => $confirmacion,
                    'documento_abierto' => (int) ($row['pu_documento_abierto'] ?? 0),
                    'fecha_documento_abierto' => $row['pu_fecha_documento_abierto'] ?? '',
                    'fecha_confirmacion' => $row['pu_fecha_confirmacion'] ?? '',
                    'observacion' => $row['pu_observacion'] ?? '',
                ];
            }
        }

        $stmtUsuarios->close();

        return array_values($pendientes);
    }

    public function actualizarPendiente(int $pendienteId, array $data, array $file, int $creadorId): array
    {
        $pendienteActual = $this->obtenerPendientePorId($pendienteId, $creadorId);
        if ($pendienteActual === null) {
            return ['success' => false, 'message' => 'El pendiente no existe o no puedes editarlo.'];
        }

        $concepto = trim($data['concepto'] ?? '');
        $tituloPersonalizado = trim($data['titulo'] ?? '');
        $titulo = $concepto === 'Otro' ? $tituloPersonalizado : $concepto;
        $descripcion = trim($data['descripcion'] ?? '');
        $linkDocumento = trim($data['documento_link'] ?? '');
        $tipoDocumento = trim($data['tipo_documento'] ?? '');
        $roles = $data['roles'] ?? [];
        $tiposContrato = $this->normalizarTiposContrato($data['tipos_contrato'] ?? []);

        if ($titulo === '') {
            return ['success' => false, 'message' => 'Debes seleccionar o escribir el concepto del pendiente.'];
        }

        if (!is_array($roles) || empty($roles)) {
            return ['success' => false, 'message' => 'Debes seleccionar al menos un rol.'];
        }

        $roles = array_values(array_unique(array_map('intval', $roles)));
        $roles = array_filter($roles, static function ($rol) {
            return $rol > 0;
        });

        if (empty($roles)) {
            return ['success' => false, 'message' => 'Los roles seleccionados no son validos.'];
        }

        if (empty($tiposContrato)) {
            return ['success' => false, 'message' => 'Debes seleccionar al menos un tipo de contrato.'];
        }

        $rutaDocumento = $this->resolverDocumentoPendiente($tipoDocumento, $linkDocumento, $file, $pendienteActual['pen_documento']);
        if ($rutaDocumento === null) {
            return ['success' => false, 'message' => 'Debes definir un documento valido para el pendiente.'];
        }

        $this->db->begin_transaction();

        try {
            $sqlPendiente = "UPDATE pendientes_creados
                SET pen_titulo = ?,
                    pen_descripcion = ?,
                    pen_documento = ?
                WHERE id = ?
                  AND pen_creado_por = ?";

            $stmtPendiente = $this->db->prepare($sqlPendiente);
            $stmtPendiente->bind_param("sssii", $titulo, $descripcion, $rutaDocumento, $pendienteId, $creadorId);
            $stmtPendiente->execute();
            $stmtPendiente->close();

            $stmtDeleteRoles = $this->db->prepare("DELETE FROM pendientes_creados_roles WHERE pendiente_id = ?");
            $stmtDeleteRoles->bind_param("i", $pendienteId);
            $stmtDeleteRoles->execute();
            $stmtDeleteRoles->close();

            $stmtRol = $this->db->prepare("INSERT INTO pendientes_creados_roles (pendiente_id, rol_id) VALUES (?, ?)");
            foreach ($roles as $rolId) {
                $stmtRol->bind_param("ii", $pendienteId, $rolId);
                $stmtRol->execute();
            }
            $stmtRol->close();

            $stmtDeleteTipos = $this->db->prepare("DELETE FROM pendientes_creados_tipos_contrato WHERE pendiente_id = ?");
            $stmtDeleteTipos->bind_param("i", $pendienteId);
            $stmtDeleteTipos->execute();
            $stmtDeleteTipos->close();

            $this->guardarTiposContratoPendiente($pendienteId, $tiposContrato);

            $this->eliminarUsuariosFueraDeRoles($pendienteId, $roles);
            $this->eliminarUsuariosFueraDeTiposContrato($pendienteId, $tiposContrato);

            $usuariosAsignados = $this->obtenerUsuariosPorRolesYContrato($roles, $tiposContrato);
            $stmtUsuario = $this->db->prepare("INSERT INTO pendientes_creados_usuarios
                (pendiente_id, usuario_id, rol_id, pu_documento_abierto, pu_fecha_asignacion)
                VALUES (?, ?, ?, 0, NOW())
                ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id)");

            foreach ($usuariosAsignados as $usuario) {
                $usuarioId = (int) $usuario['idusuarios'];
                $rolId = (int) $usuario['roles_idroles'];
                $stmtUsuario->bind_param("iii", $pendienteId, $usuarioId, $rolId);
                $stmtUsuario->execute();
            }
            $stmtUsuario->close();

            $this->db->commit();

            return ['success' => true, 'message' => 'Pendiente actualizado correctamente.'];
        } catch (Throwable $e) {
            $this->db->rollback();
            $this->log("actualizarPendiente ERROR", ['error' => $e->getMessage(), 'pendienteId' => $pendienteId]);
            return ['success' => false, 'message' => 'No fue posible actualizar el pendiente.'];
        }
    }

    public function eliminarPendiente(int $pendienteId, int $creadorId): array
    {
        $sql = "UPDATE pendientes_creados
                SET pen_estado = 0
                WHERE id = ?
                  AND pen_creado_por = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $pendienteId, $creadorId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return [
            'success' => $ok,
            'message' => $ok
                ? 'Pendiente eliminado correctamente.'
                : 'No fue posible eliminar el pendiente seleccionado.',
        ];
    }

    public function crearPendiente(array $data, array $file, int $creadorId): array
    {
        $concepto = trim($data['concepto'] ?? '');
        $tituloPersonalizado = trim($data['titulo'] ?? '');
        $titulo = $concepto === 'Otro' ? $tituloPersonalizado : $concepto;
        $descripcion = trim($data['descripcion'] ?? '');
        $linkDocumento = trim($data['documento_link'] ?? '');
        $roles = $data['roles'] ?? [];
        $tiposContrato = $this->normalizarTiposContrato($data['tipos_contrato'] ?? []);
        $tieneArchivo = (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK);
        $tieneLink = $linkDocumento !== '';

        $this->log("Inicio crearPendiente", [
            'creadorId' => $creadorId,
            'concepto' => $concepto,
            'titulo' => $titulo,
            'roles' => $roles,
            'tipos_contrato' => $tiposContrato,
            'file_error' => $file['error'] ?? null,
            'file_name' => $file['name'] ?? null,
            'link_documento' => $linkDocumento,
        ]);

        if ($titulo === '') {
            $this->log("crearPendiente abortado: concepto vacio");
            return ['success' => false, 'message' => 'Debes seleccionar o escribir el concepto del pendiente.'];
        }

        if (!is_array($roles) || empty($roles)) {
            $this->log("crearPendiente abortado: sin roles");
            return ['success' => false, 'message' => 'Debes seleccionar al menos un rol.'];
        }

        $roles = array_values(array_unique(array_map('intval', $roles)));
        $roles = array_filter($roles, static function ($rol) {
            return $rol > 0;
        });

        if (empty($roles)) {
            $this->log("crearPendiente abortado: roles invalidos");
            return ['success' => false, 'message' => 'Los roles seleccionados no son validos.'];
        }

        if (empty($tiposContrato)) {
            $this->log("crearPendiente abortado: tipos de contrato invalidos");
            return ['success' => false, 'message' => 'Debes seleccionar al menos un tipo de contrato.'];
        }

        if (!$tieneArchivo && !$tieneLink) {
            $this->log("crearPendiente abortado: sin documento");
            return ['success' => false, 'message' => 'Debes cargar un archivo o escribir un link para el pendiente.'];
        }

        if ($tieneArchivo && $tieneLink) {
            $this->log("crearPendiente abortado: archivo y link simultaneos");
            return ['success' => false, 'message' => 'Debes elegir solo una opcion: archivo o link.'];
        }

        if ($tieneLink && !$this->esLinkDocumentoValido($linkDocumento)) {
            $this->log("crearPendiente abortado: link invalido", ['link_documento' => $linkDocumento]);
            return ['success' => false, 'message' => 'El link del documento no es valido.'];
        }

        if ($tieneArchivo) {
            $rutaDocumento = $this->guardarDocumentoPendiente($file);
            if ($rutaDocumento === null) {
                $this->log("crearPendiente abortado: no se pudo guardar documento");
                return ['success' => false, 'message' => 'No fue posible guardar el documento.'];
            }
        } else {
            $rutaDocumento = $linkDocumento;
        }

        $this->db->begin_transaction();

        try {
            $sqlPendiente = "INSERT INTO pendientes_creados
                (pen_titulo, pen_descripcion, pen_documento, pen_creado_por, pen_fecha_creacion, pen_estado)
                VALUES (?, ?, ?, ?, NOW(), 1)";

            $stmtPendiente = $this->db->prepare($sqlPendiente);
            if (!$stmtPendiente) {
                throw new Exception('Error preparando INSERT pendientes_creados: ' . $this->db->error);
            }
            $stmtPendiente->bind_param("sssi", $titulo, $descripcion, $rutaDocumento, $creadorId);
            $stmtPendiente->execute();
            $pendienteId = (int) $stmtPendiente->insert_id;
            $stmtPendiente->close();

            $stmtRol = $this->db->prepare("INSERT INTO pendientes_creados_roles (pendiente_id, rol_id) VALUES (?, ?)");
            if (!$stmtRol) {
                throw new Exception('Error preparando INSERT pendientes_creados_roles: ' . $this->db->error);
            }
            foreach ($roles as $rolId) {
                $stmtRol->bind_param("ii", $pendienteId, $rolId);
                $stmtRol->execute();
            }
            $stmtRol->close();

            $this->guardarTiposContratoPendiente($pendienteId, $tiposContrato);

            $usuariosAsignados = $this->obtenerUsuariosPorRolesYContrato($roles, $tiposContrato);
            $stmtUsuario = $this->db->prepare("INSERT INTO pendientes_creados_usuarios
                (pendiente_id, usuario_id, rol_id, pu_documento_abierto, pu_fecha_asignacion)
                VALUES (?, ?, ?, 0, NOW())
                ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id)");
            if (!$stmtUsuario) {
                throw new Exception('Error preparando INSERT pendientes_creados_usuarios: ' . $this->db->error);
            }

            foreach ($usuariosAsignados as $usuario) {
                $usuarioId = (int) $usuario['idusuarios'];
                $rolId = (int) $usuario['roles_idroles'];
                $stmtUsuario->bind_param("iii", $pendienteId, $usuarioId, $rolId);
                $stmtUsuario->execute();
            }
            $stmtUsuario->close();

            $this->db->commit();
            $this->log("crearPendiente OK", [
                'pendienteId' => $pendienteId,
                'usuarios_asignados' => count($usuariosAsignados),
                'rutaDocumento' => $rutaDocumento,
            ]);

            return [
                'success' => true,
                'message' => 'Pendiente creado correctamente.',
                'usuarios_asignados' => count($usuariosAsignados),
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            $this->log("crearPendiente ERROR", ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Ocurrio un error al crear el pendiente.'];
        }
    }

    public function marcarDocumentoAbierto(int $pendienteUsuarioId, int $idUsuario): bool
    {
        $sql = "UPDATE pendientes_creados_usuarios
                SET pu_documento_abierto = 1,
                    pu_fecha_documento_abierto = NOW()
                WHERE id = ?
                  AND usuario_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $pendienteUsuarioId, $idUsuario);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0 || $this->yaTieneDocumentoAbierto($pendienteUsuarioId, $idUsuario);
        $stmt->close();

        return $ok;
    }

    public function confirmarPendientePersonalizado(int $pendienteUsuarioId, int $idUsuario, string $confirmacion, string $observacion): array
    {
        $sqlValida = "SELECT pu_documento_abierto
                      FROM pendientes_creados_usuarios
                      WHERE id = ?
                        AND usuario_id = ?
                      LIMIT 1";

        $stmtValida = $this->db->prepare($sqlValida);
        $stmtValida->bind_param("ii", $pendienteUsuarioId, $idUsuario);
        $stmtValida->execute();
        $resultado = $stmtValida->get_result();
        $fila = $resultado->fetch_assoc();
        $stmtValida->close();

        if (!$fila) {
            return ['success' => false, 'message' => 'El pendiente no existe para este usuario.'];
        }

        if ((int) $fila['pu_documento_abierto'] !== 1) {
            return ['success' => false, 'message' => 'Debes abrir el documento antes de confirmar este pendiente.'];
        }

        $sql = "UPDATE pendientes_creados_usuarios
                SET pu_confirmacion = ?,
                    pu_observacion = ?,
                    pu_fecha_confirmacion = NOW()
                WHERE id = ?
                  AND usuario_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssii", $confirmacion, $observacion, $pendienteUsuarioId, $idUsuario);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return [
            'success' => $ok,
            'message' => $ok
                ? 'Pendiente actualizado correctamente.'
                : 'No fue posible actualizar el pendiente seleccionado.',
        ];
    }

    public function confirmarNominaUsuario(int $idUsuario, string $fechaInicio, string $tipo, string $confirmacion, string $observacion): bool
    {
        $sql = "UPDATE nomina
                SET nom_confirmaUsu = ?,
                    nom_motivoObser = ?,
                    nom_fechaconfirmaUsus = NOW()
                WHERE nom_id_usu = ?
                  AND nom_fecha_inicio = ?
                  AND nom_tipo_pago = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssiss", $confirmacion, $observacion, $idUsuario, $fechaInicio, $tipo);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();

        return $ok;
    }

    public function confirmarPrimaUsuario(int $idUsuario, string $fechaInicio, string $confirmacion): bool
    {
        $sqlSelect = "SELECT pri_docprima
                      FROM primas
                      WHERE pri_idusu = ?
                        AND pri_fecha_inicio = ?
                      LIMIT 1";

        $stmtSelect = $this->db->prepare($sqlSelect);
        $stmtSelect->bind_param("is", $idUsuario, $fechaInicio);
        $stmtSelect->execute();
        $result = $stmtSelect->get_result();
        $prima = $result->fetch_assoc();
        $stmtSelect->close();

        if (!$prima) {
            return false;
        }

        $marca = 'Validado el ' . date('Y-m-d H:i:s');
        $rutaDocumento = (string) ($prima['pri_docprima'] ?? '');
        if ($rutaDocumento !== '' && stripos($rutaDocumento, 'confirmado=') === false) {
            $rutaDocumento .= '&confirmado=' . urlencode($marca);
        }

        $sqlUpdate = "UPDATE primas
                      SET pri_confirmaUsus = ?,
                          pri_fechaconfirmausu = NOW(),
                          pri_docprima = ?
                      WHERE pri_idusu = ?
                        AND pri_fecha_inicio = ?";

        $stmtUpdate = $this->db->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssis", $confirmacion, $rutaDocumento, $idUsuario, $fechaInicio);
        $stmtUpdate->execute();
        $ok = $stmtUpdate->affected_rows > 0;
        $stmtUpdate->close();

        return $ok;
    }

    private function asegurarEstructura(): void
    {
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0777, true);
        }

        $sqlPendientes = "CREATE TABLE IF NOT EXISTS pendientes_creados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pen_titulo VARCHAR(255) NOT NULL,
            pen_descripcion TEXT NULL,
            pen_documento VARCHAR(255) NOT NULL,
            pen_creado_por INT NOT NULL,
            pen_fecha_creacion DATETIME NOT NULL,
            pen_estado TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $sqlRoles = "CREATE TABLE IF NOT EXISTS pendientes_creados_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pendiente_id INT NOT NULL,
            rol_id INT NOT NULL,
            UNIQUE KEY unique_pendiente_rol (pendiente_id, rol_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $sqlTiposContrato = "CREATE TABLE IF NOT EXISTS pendientes_creados_tipos_contrato (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pendiente_id INT NOT NULL,
            tipo_contrato VARCHAR(100) NOT NULL,
            UNIQUE KEY unique_pendiente_tipo_contrato (pendiente_id, tipo_contrato)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $sqlUsuarios = "CREATE TABLE IF NOT EXISTS pendientes_creados_usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pendiente_id INT NOT NULL,
            usuario_id INT NOT NULL,
            rol_id INT NOT NULL,
            pu_confirmacion VARCHAR(10) NULL,
            pu_observacion TEXT NULL,
            pu_documento_abierto TINYINT(1) NOT NULL DEFAULT 0,
            pu_fecha_documento_abierto DATETIME NULL,
            pu_fecha_confirmacion DATETIME NULL,
            pu_fecha_asignacion DATETIME NOT NULL,
            UNIQUE KEY unique_pendiente_usuario (pendiente_id, usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query($sqlPendientes);
        $this->db->query($sqlRoles);
        $this->db->query($sqlTiposContrato);
        $this->db->query($sqlUsuarios);
    }

    private function obtenerUsuariosPorRolesYContrato(array $roles, array $tiposContrato): array
    {
        $marcadoresRoles = implode(',', array_fill(0, count($roles), '?'));
        $marcadoresContrato = implode(',', array_fill(0, count($tiposContrato), '?'));
        $tipos = str_repeat('i', count($roles)) . str_repeat('s', count($tiposContrato));

        $sql = "SELECT idusuarios, roles_idroles
                FROM usuarios
                WHERE usu_estado = 1
                  AND roles_idroles IN ($marcadoresRoles)
                  AND usu_tipocontrato IN ($marcadoresContrato)";

        $stmt = $this->db->prepare($sql);
        $parametros = array_merge($roles, $tiposContrato);
        $stmt->bind_param($tipos, ...$parametros);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuarios = $resultado->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $usuarios;
    }

    private function sincronizarPendientesPorRol(int $idUsuario, int $rolUsuario, string $tipoContratoUsuario): void
    {
        $sql = "INSERT INTO pendientes_creados_usuarios
                    (pendiente_id, usuario_id, rol_id, pu_documento_abierto, pu_fecha_asignacion)
                SELECT
                    pc.id,
                    ?,
                    ?,
                    0,
                    NOW()
                FROM pendientes_creados pc
                INNER JOIN pendientes_creados_roles pr ON pr.pendiente_id = pc.id
                LEFT JOIN pendientes_creados_usuarios pu
                    ON pu.pendiente_id = pc.id
                   AND pu.usuario_id = ?
                LEFT JOIN pendientes_creados_tipos_contrato ptc
                    ON ptc.pendiente_id = pc.id
                WHERE pr.rol_id = ?
                  AND pc.pen_estado = 1
                  AND (ptc.tipo_contrato IS NULL OR ptc.tipo_contrato = ?)
                  AND pu.id IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiiis", $idUsuario, $rolUsuario, $idUsuario, $rolUsuario, $tipoContratoUsuario);
        $stmt->execute();
        $stmt->close();
    }

    private function obtenerPendientePorId(int $pendienteId, int $creadorId): ?array
    {
        $sql = "SELECT id, pen_documento
                FROM pendientes_creados
                WHERE id = ?
                  AND pen_creado_por = ?
                  AND pen_estado = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $pendienteId, $creadorId);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $resultado ?: null;
    }

    private function eliminarUsuariosFueraDeRoles(int $pendienteId, array $roles): void
    {
        $marcadores = implode(',', array_fill(0, count($roles), '?'));
        $tipos = 'i' . str_repeat('i', count($roles));

        $sql = "DELETE FROM pendientes_creados_usuarios
                WHERE pendiente_id = ?
                  AND rol_id NOT IN ($marcadores)";

        $stmt = $this->db->prepare($sql);
        $parametros = array_merge([$pendienteId], $roles);
        $stmt->bind_param($tipos, ...$parametros);
        $stmt->execute();
        $stmt->close();
    }

    private function eliminarUsuariosFueraDeTiposContrato(int $pendienteId, array $tiposContrato): void
    {
        $marcadores = implode(',', array_fill(0, count($tiposContrato), '?'));
        $tipos = 'i' . str_repeat('s', count($tiposContrato));

        $sql = "DELETE pu
                FROM pendientes_creados_usuarios pu
                INNER JOIN usuarios u ON u.idusuarios = pu.usuario_id
                WHERE pu.pendiente_id = ?
                  AND u.usu_tipocontrato NOT IN ($marcadores)";

        $stmt = $this->db->prepare($sql);
        $parametros = array_merge([$pendienteId], $tiposContrato);
        $stmt->bind_param($tipos, ...$parametros);
        $stmt->execute();
        $stmt->close();
    }

    private function guardarTiposContratoPendiente(int $pendienteId, array $tiposContrato): void
    {
        $stmt = $this->db->prepare("INSERT INTO pendientes_creados_tipos_contrato (pendiente_id, tipo_contrato) VALUES (?, ?)");
        foreach ($tiposContrato as $tipoContrato) {
            $stmt->bind_param("is", $pendienteId, $tipoContrato);
            $stmt->execute();
        }
        $stmt->close();
    }

    private function normalizarTiposContrato($tiposContrato): array
    {
        if (!is_array($tiposContrato)) {
            return [];
        }

        $permitidos = ['Empresa', 'Prestacion de servicios'];
        $tiposContrato = array_values(array_unique(array_map('trim', $tiposContrato)));

        return array_values(array_filter($tiposContrato, static function ($tipo) use ($permitidos) {
            return in_array($tipo, $permitidos, true);
        }));
    }

    private function obtenerTipoContratoUsuario(int $idUsuario): string
    {
        $sql = "SELECT usu_tipocontrato FROM usuarios WHERE idusuarios = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (string) ($resultado['usu_tipocontrato'] ?? '');
    }

    private function resolverDocumentoPendiente(string $tipoDocumento, string $linkDocumento, array $file, string $documentoActual): ?string
    {
        if ($tipoDocumento === 'link') {
            return $this->esLinkDocumentoValido($linkDocumento) ? $linkDocumento : null;
        }

        if ($tipoDocumento === 'archivo') {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                return $this->guardarDocumentoPendiente($file);
            }

            if ($documentoActual !== '' && $this->esDocumentoInterno($documentoActual)) {
                return $documentoActual;
            }
        }

        return null;
    }

    private function guardarDocumentoPendiente(array $file): ?string
    {
        $nombreOriginal = $file['name'] ?? '';
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $permitidas = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];

        if (!in_array($extension, $permitidas, true)) {
            $this->log("guardarDocumentoPendiente extension invalida", ['extension' => $extension]);
            return null;
        }

        $nombreFinal = 'pendiente_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = $this->uploadDir . DIRECTORY_SEPARATOR . $nombreFinal;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            $this->log("guardarDocumentoPendiente fallo move_uploaded_file", [
                'tmp_name' => $file['tmp_name'] ?? null,
                'destino' => $destino,
            ]);
            return null;
        }

        return $this->uploadUrlBase . $nombreFinal;
    }

    private function esLinkDocumentoValido(string $link): bool
    {
        if ($link === '') {
            return false;
        }

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return false;
        }

        $esquema = strtolower((string) parse_url($link, PHP_URL_SCHEME));
        return in_array($esquema, ['http', 'https'], true);
    }

    private function esDocumentoInterno(string $ruta): bool
    {
        return $ruta !== '' && strpos($ruta, $this->uploadUrlBase) === 0;
    }

    private function log(string $mensaje, array $contexto = []): void
    {
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;
        if (!empty($contexto)) {
            $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        }
        $linea .= PHP_EOL;
        @file_put_contents($this->logFile, $linea, FILE_APPEND);
    }

    private function yaTieneDocumentoAbierto(int $pendienteUsuarioId, int $idUsuario): bool
    {
        $sql = "SELECT pu_documento_abierto
                FROM pendientes_creados_usuarios
                WHERE id = ?
                  AND usuario_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $pendienteUsuarioId, $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($resultado['pu_documento_abierto']) && (int) $resultado['pu_documento_abierto'] === 1;
    }

    private function mapearConceptoNomina(string $tipo): string
    {
        switch ($tipo) {
            case 'Basico':
                return 'Salario basico devengado';
            case 'Otros':
                return 'Otros devengos';
            default:
                return $tipo !== '' ? $tipo : 'Pago de nomina';
        }
    }
}
