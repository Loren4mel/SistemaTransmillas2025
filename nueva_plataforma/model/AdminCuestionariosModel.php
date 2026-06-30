<?php
/**
 * AdminCuestionariosModel - Modelo de administracion unificada de cuestionarios
 *
 * Gestiona plantillas, versiones, secciones y preguntas tanto de Preoperacional
 * como de SST (y futuros modulos) a traves del esquema unificado cuestionarios_*.
 */

require_once __DIR__ . "/../config/database.php";

class AdminCuestionariosModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    // ==================== PLANTILLAS ====================

    /**
     * Obtiene todas las plantillas, opcionalmente filtradas por modulo.
     * @param string|null $modulo 'preop', 'sst' o null para todas
     * @return array
     */
    public function getPlantillas($modulo = null)
    {
        $sql = "SELECT cp.*, u.usu_nombre AS creador_nombre,
                       (SELECT COUNT(*) FROM `cuestionarios_versiones` cv2
                        WHERE cv2.`id_plantilla` = cp.`id_plantilla`
                          AND cv2.`estado` IN ('ACTIVA','HISTORICA')) AS versiones_bloqueadas
                FROM `cuestionarios_plantillas` cp
                LEFT JOIN `usuarios` u ON u.`idusuarios` = cp.`creado_por`";
        if ($modulo) {
            $sql .= " WHERE cp.`slug_modulo` = ?";
            return $this->executeAll($sql, "s", [$modulo]);
        }
        $sql .= " ORDER BY cp.`fecha_creacion` DESC";
        return $this->executeAll($sql, "", []);
    }

    /**
     * Obtiene una plantilla por ID.
     * @param int $id
     * @return array|null
     */
    public function getPlantilla($id)
    {
        $sql = "SELECT cp.*, u.usu_nombre AS creador_nombre
                FROM `cuestionarios_plantillas` cp
                LEFT JOIN `usuarios` u ON u.`idusuarios` = cp.`creado_por`
                WHERE cp.`id_plantilla` = ?";
        return $this->executeQuery($sql, "i", [$id]);
    }

    /**
     * Guarda (inserta o actualiza) una plantilla.
     * @param array $data
     * @return int ID insertado o 0 si es actualizacion
     */
    public function savePlantilla($data)
    {
        $id = (int) ($data['id_plantilla'] ?? 0);
        $nombre = $data['nombre_base'] ?? '';
        $modulo = $data['slug_modulo'] ?? 'preop';
        $tipoDest = $data['tipo_destinatario'] ?? 'USUARIO';
        $roles = $data['aplica_a_roles'] ?? null;
        $tipoVeh = $data['aplica_a_tipo_vehiculo'] ?? null;
        $esDefault = (int) ($data['es_default'] ?? 0);
        $estado = (int) ($data['estado'] ?? 1);
        $sstTipo = $data['sst_tipo'] ?? null;
        $creadoPor = (int) ($data['creado_por'] ?? $_SESSION['usuario_id']);
        $actualizadoPor = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($id > 0) {
            $sql = "UPDATE `cuestionarios_plantillas` SET
                    `nombre_base` = ?, `slug_modulo` = ?, `tipo_destinatario` = ?,
                    `aplica_a_roles` = ?, `aplica_a_tipo_vehiculo` = ?,
                    `es_default` = ?, `estado` = ?, `sst_tipo` = ?,
                    `actualizado_por` = ?, `fecha_actualizacion` = NOW()
                    WHERE `id_plantilla` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sssssiisii", $nombre, $modulo, $tipoDest, $roles, $tipoVeh, $esDefault, $estado, $sstTipo, $actualizadoPor, $id);
            $stmt->execute();
            return 0;
        }

        $sql = "INSERT INTO `cuestionarios_plantillas`
                (`nombre_base`, `slug_modulo`, `tipo_destinatario`, `aplica_a_roles`,
                 `aplica_a_tipo_vehiculo`, `es_default`, `estado`, `sst_tipo`, `creado_por`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssssiisi", $nombre, $modulo, $tipoDest, $roles, $tipoVeh, $esDefault, $estado, $sstTipo, $creadoPor);
        $stmt->execute();
        return $stmt->insert_id;
    }

    /**
     * Elimina una plantilla (solo si no tiene versiones).
     * @param int $id
     * @return bool
     */
    public function deletePlantilla($id)
    {
        $sql = "DELETE FROM `cuestionarios_plantillas` WHERE `id_plantilla` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // ==================== VERSIONES ====================

    /**
     * Obtiene las versiones de una plantilla.
     * @param int $idPlantilla
     * @return array
     */
    public function getVersiones($idPlantilla)
    {
        $sql = "SELECT cv.*, u.usu_nombre AS creador_nombre
                FROM `cuestionarios_versiones` cv
                LEFT JOIN `usuarios` u ON u.`idusuarios` = cv.`creado_por`
                WHERE cv.`id_plantilla` = ? ORDER BY cv.`fecha_creacion` DESC";
        return $this->executeAll($sql, "i", [$idPlantilla]);
    }

    /**
     * Verifica si una plantilla tiene versiones no modificables (ACTIVA o HISTORICA).
     * @param int $idPlantilla
     * @return bool true si tiene alguna version ACTIVA o HISTORICA
     */
    public function plantillaTieneVersionesBloqueadas($idPlantilla)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `cuestionarios_versiones`
                WHERE `id_plantilla` = ? AND `estado` IN ('ACTIVA','HISTORICA')
                LIMIT 1";
        $row = $this->executeQuery($sql, "i", [$idPlantilla]);
        return $row && (int)$row['total'] > 0;
    }

    /**
     * Obtiene la version ACTIVA de una plantilla.
     * @param int $idPlantilla
     * @return array|null
     */
    public function getVersionActiva($idPlantilla)
    {
        $sql = "SELECT cv.*, u.usu_nombre AS creador_nombre
                FROM `cuestionarios_versiones` cv
                LEFT JOIN `usuarios` u ON u.`idusuarios` = cv.`creado_por`
                WHERE cv.`id_plantilla` = ? AND cv.`estado` = 'ACTIVA' LIMIT 1";
        return $this->executeQuery($sql, "i", [$idPlantilla]);
    }

    /**
     * Guarda una nueva version (clona desde la activa si existe).
     * @param array $data
     * @return int ID insertado
     */
    public function saveVersion($data)
    {
        $idPlantilla = (int) ($data['id_plantilla'] ?? 0);
        $numeroVersion = $data['numero_version'] ?? 'v1.0';
        $notas = $data['notas_cambio'] ?? '';
        $creadoPor = (int) ($data['creado_por'] ?? $_SESSION['usuario_id']);
        $versionActiva = $this->getVersionActiva($idPlantilla);

        $sql = "INSERT INTO `cuestionarios_versiones`
                (`id_plantilla`, `numero_version`, `fecha_vigencia_inicio`,
                 `estado`, `creado_por`, `notas_cambio`)
                VALUES (?, ?, NOW(), 'BORRADOR', ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("issi", $idPlantilla, $numeroVersion, $creadoPor, $notas);
        $stmt->execute();
        $newVersionId = $stmt->insert_id;

        if ($versionActiva) {
            $seccionesViejas = $this->getSecciones($versionActiva['id_version']);
            foreach ($seccionesViejas as $sec) {
                $sqlSec = "INSERT INTO `cuestionarios_secciones`
                           (`id_version`, `nombre`, `descripcion`, `css_clase`, `orden`)
                           VALUES (?, ?, ?, ?, ?)";
                $stmtSec = $this->db->prepare($sqlSec);
                $stmtSec->bind_param("isssi", $newVersionId, $sec['nombre'], $sec['descripcion'], $sec['css_clase'], $sec['orden']);
                $stmtSec->execute();
                $newSecId = $stmtSec->insert_id;

                $preguntas = $this->getPreguntas($sec['id_seccion']);
                foreach ($preguntas as $p) {
                    $sqlP = "INSERT INTO `cuestionarios_preguntas`
                             (`id_seccion`, `codigo_interno`, `texto_pregunta`, `tipo_respuesta`,
                              `requerido`, `respuesta_esperada`, `requiere_foto_si_negativa`,
                              `genera_bloqueo`, `id_pregunta_padre`, `placeholder`, `ayuda`,
                              `orden`, `estado`)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmtP = $this->db->prepare($sqlP);
                    $stmtP->bind_param("isssisiiisssi", $newSecId, $p['codigo_interno'], $p['texto_pregunta'],
                        $p['tipo_respuesta'], $p['requerido'], $p['respuesta_esperada'],
                        $p['requiere_foto_si_negativa'], $p['genera_bloqueo'],
                        $p['id_pregunta_padre'], $p['placeholder'], $p['ayuda'],
                        $p['orden'], $p['estado']);
                    $stmtP->execute();
                }
            }
        }
        return $newVersionId;
    }

    /**
     * Activa una version (la marcada pasa a ACTIVA, las demas a HISTORICA).
     * @param int $idVersion
     */
    public function activarVersion($idVersion)
    {
        $version = $this->executeQuery(
            "SELECT `id_plantilla` FROM `cuestionarios_versiones` WHERE `id_version` = ?",
            "i", [$idVersion]
        );
        if (!$version) return;
        $idPlantilla = (int) $version['id_plantilla'];

        $sqlDeactivate = "UPDATE `cuestionarios_versiones` SET
                          `estado` = 'HISTORICA', `fecha_vigencia_fin` = NOW()
                          WHERE `id_plantilla` = ? AND `estado` = 'ACTIVA'";
        $stmt = $this->db->prepare($sqlDeactivate);
        $stmt->bind_param("i", $idPlantilla);
        $stmt->execute();

        $sqlActivate = "UPDATE `cuestionarios_versiones` SET
                        `estado` = 'ACTIVA', `fecha_vigencia_fin` = NULL
                        WHERE `id_version` = ?";
        $stmt = $this->db->prepare($sqlActivate);
        $stmt->bind_param("i", $idVersion);
        $stmt->execute();
    }

    // ==================== SECCIONES ====================

    /**
     * Obtiene las secciones de una version.
     * @param int $idVersion
     * @return array
     */
    public function getSecciones($idVersion)
    {
        $sql = "SELECT cs.*, cv.`estado` AS `version_estado`
                FROM `cuestionarios_secciones` cs
                INNER JOIN `cuestionarios_versiones` cv ON cv.`id_version` = cs.`id_version`
                WHERE cs.`id_version` = ? ORDER BY cs.`orden` ASC";
        return $this->executeAll($sql, "i", [$idVersion]);
    }

    /**
     * Obtiene una seccion por ID.
     * @param int $idSeccion
     * @return array|null
     */
    public function getSeccionPorId($idSeccion)
    {
        $sql = "SELECT * FROM `cuestionarios_secciones` WHERE `id_seccion` = ?";
        return $this->executeQuery($sql, "i", [$idSeccion]);
    }

    /**
     * Guarda (inserta o actualiza) una seccion.
     * @param array $data
     * @return int
     */
    public function saveSeccion($data)
    {
        $id = (int) ($data['id_seccion'] ?? 0);
        $idVersion = (int) ($data['id_version'] ?? 0);
        $nombre = $data['nombre'] ?? '';
        $descripcion = $data['descripcion'] ?? null;
        $cssClase = $data['css_clase'] ?? null;
        $orden = (int) ($data['orden'] ?? 0);

        if ($id > 0) {
            $sql = "UPDATE `cuestionarios_secciones` SET
                    `nombre` = ?, `descripcion` = ?, `css_clase` = ?, `orden` = ?
                    WHERE `id_seccion` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sssii", $nombre, $descripcion, $cssClase, $orden, $id);
            $stmt->execute();
            return 0;
        }
        $sql = "INSERT INTO `cuestionarios_secciones`
                (`id_version`, `nombre`, `descripcion`, `css_clase`, `orden`)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssi", $idVersion, $nombre, $descripcion, $cssClase, $orden);
        $stmt->execute();
        return $stmt->insert_id;
    }

    /**
     * Elimina una seccion (cascade elimina sus preguntas).
     * @param int $id
     * @return bool
     */
    public function deleteSeccion($id)
    {
        $sql = "DELETE FROM `cuestionarios_secciones` WHERE `id_seccion` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Reordena secciones.
     * @param int $idVersion
     * @param array $ordenes [id_seccion => orden, ...]
     */
    public function reordenarSecciones($idVersion, $ordenes)
    {
        $sql = "UPDATE `cuestionarios_secciones` SET `orden` = ? WHERE `id_seccion` = ? AND `id_version` = ?";
        $stmt = $this->db->prepare($sql);
        foreach ($ordenes as $idSeccion => $orden) {
            $stmt->bind_param("iii", $orden, $idSeccion, $idVersion);
            $stmt->execute();
        }
    }

    // ==================== PREGUNTAS ====================

    /**
     * Obtiene las preguntas de una seccion.
     * @param int $idSeccion
     * @return array
     */
    public function getPreguntas($idSeccion)
    {
        $sql = "SELECT cp.*, cv.`estado` AS `version_estado`
                FROM `cuestionarios_preguntas` cp
                INNER JOIN `cuestionarios_secciones` cs ON cs.`id_seccion` = cp.`id_seccion`
                INNER JOIN `cuestionarios_versiones` cv ON cv.`id_version` = cs.`id_version`
                WHERE cp.`id_seccion` = ? ORDER BY cp.`orden` ASC";
        return $this->executeAll($sql, "i", [$idSeccion]);
    }

    /**
     * Obtiene una pregunta por ID.
     * @param int $id
     * @return array|null
     */
    public function getPregunta($id)
    {
        $sql = "SELECT * FROM `cuestionarios_preguntas` WHERE `id_pregunta` = ?";
        return $this->executeQuery($sql, "i", [$id]);
    }

    /**
     * Guarda (inserta o actualiza) una pregunta.
     * @param array $data
     * @return int
     */
    public function savePregunta($data)
    {
        $id = (int) ($data['id_pregunta'] ?? 0);
        $idSeccion = (int) ($data['id_seccion'] ?? 0);
        $codigo = $data['codigo_interno'] ?? '';
        $texto = $data['texto_pregunta'] ?? '';
        $tipoResp = $data['tipo_respuesta'] ?? 'SI_NO';
        $requerido = (int) ($data['requerido'] ?? 1);
        $respEsperada = $data['respuesta_esperada'] ?? null;
        $requiereFoto = (int) ($data['requiere_foto_si_negativa'] ?? 0);
        $generaBloqueo = (int) ($data['genera_bloqueo'] ?? 0);
        $idPadre = !empty($data['id_pregunta_padre']) ? (int) $data['id_pregunta_padre'] : null;
        $valorPadre = $data['valor_padre'] ?? null;
        $placeholder = $data['placeholder'] ?? null;
        $ayuda = $data['ayuda'] ?? null;
        $orden = (int) ($data['orden'] ?? 0);
        $estado = (int) ($data['estado'] ?? 1);

        if ($id > 0) {
            $sql = "UPDATE `cuestionarios_preguntas` SET
                    `id_seccion` = ?, `codigo_interno` = ?, `texto_pregunta` = ?,
                    `tipo_respuesta` = ?, `requerido` = ?,
                    `respuesta_esperada` = ?, `requiere_foto_si_negativa` = ?,
                    `genera_bloqueo` = ?, `id_pregunta_padre` = ?, `valor_padre` = ?,
                    `placeholder` = ?, `ayuda` = ?, `orden` = ?, `estado` = ?
                    WHERE `id_pregunta` = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isssiiisssssiii",
                $idSeccion, $codigo, $texto, $tipoResp, $requerido,
                $respEsperada, $requiereFoto, $generaBloqueo,
                $idPadre, $valorPadre, $placeholder, $ayuda,
                $orden, $estado, $id);
            $stmt->execute();
            return 0;
        }

        $sql = "INSERT INTO `cuestionarios_preguntas`
                (`id_seccion`, `codigo_interno`, `texto_pregunta`, `tipo_respuesta`,
                 `requerido`, `respuesta_esperada`, `requiere_foto_si_negativa`,
                 `genera_bloqueo`, `id_pregunta_padre`, `valor_padre`,
                 `placeholder`, `ayuda`, `orden`, `estado`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isssiiisssssii",
            $idSeccion, $codigo, $texto, $tipoResp, $requerido,
            $respEsperada, $requiereFoto, $generaBloqueo,
            $idPadre, $valorPadre, $placeholder, $ayuda,
            $orden, $estado);
        $stmt->execute();
        return $stmt->insert_id;
    }

    /**
     * Elimina una pregunta.
     * @param int $id
     * @return bool
     */
    public function deletePregunta($id)
    {
        $sql = "DELETE FROM `cuestionarios_preguntas` WHERE `id_pregunta` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Reordena preguntas de una seccion.
     * @param int $idSeccion
     * @param array $ordenes [id_pregunta => orden, ...]
     */
    public function reordenarPreguntas($idSeccion, $ordenes)
    {
        $sql = "UPDATE `cuestionarios_preguntas` SET `orden` = ? WHERE `id_pregunta` = ? AND `id_seccion` = ?";
        $stmt = $this->db->prepare($sql);
        foreach ($ordenes as $idPregunta => $orden) {
            $stmt->bind_param("iii", $orden, $idPregunta, $idSeccion);
            $stmt->execute();
        }
    }

    /**
     * Obtiene preguntas SI_NO disponibles para ser padre condicional (SST).
     * @param int $idSeccion
     * @return array
     */
    public function getPreguntasDisponiblesParaPadre($idSeccion)
    {
        $sql = "SELECT `id_pregunta`, `codigo_interno`, `texto_pregunta`
                FROM `cuestionarios_preguntas`
                WHERE `id_seccion` = ?
                  AND `tipo_respuesta` = 'SI_NO'
                  AND `estado` = 1
                ORDER BY `orden` ASC";
        return $this->executeAll($sql, "i", [$idSeccion]);
    }

    /**
     * Obtiene el modulo (preop/sst) al que pertenece una seccion.
     * @param int $idSeccion
     * @return string|null
     */
    public function getModuloPorSeccion($idSeccion)
    {
        $sql = "SELECT cp.`slug_modulo`
                FROM `cuestionarios_secciones` cs
                INNER JOIN `cuestionarios_versiones` cv ON cv.`id_version` = cs.`id_version`
                INNER JOIN `cuestionarios_plantillas` cp ON cp.`id_plantilla` = cv.`id_plantilla`
                WHERE cs.`id_seccion` = ?
                LIMIT 1";
        $row = $this->executeQuery($sql, "i", [$idSeccion]);
        return $row ? $row['slug_modulo'] : null;
    }

    /**
     * Obtiene el estado de la version a la que pertenece una pregunta.
     * @param int $idPregunta
     * @return string|null 'ACTIVA', 'HISTORICA', 'BORRADOR' o null
     */
    public function getEstadoVersionPorPregunta($idPregunta)
    {
        $sql = "SELECT cv.`estado`
                FROM `cuestionarios_preguntas` cp
                INNER JOIN `cuestionarios_secciones` cs ON cs.`id_seccion` = cp.`id_seccion`
                INNER JOIN `cuestionarios_versiones` cv ON cv.`id_version` = cs.`id_version`
                WHERE cp.`id_pregunta` = ?
                LIMIT 1";
        $row = $this->executeQuery($sql, "i", [$idPregunta]);
        return $row ? $row['estado'] : null;
    }

    /**
     * Obtiene el estado de la version a la que pertenece una seccion.
     * @param int $idSeccion
     * @return string|null
     */
    public function getEstadoVersionPorSeccion($idSeccion)
    {
        $sql = "SELECT cv.`estado`
                FROM `cuestionarios_secciones` cs
                INNER JOIN `cuestionarios_versiones` cv ON cv.`id_version` = cs.`id_version`
                WHERE cs.`id_seccion` = ?
                LIMIT 1";
        $row = $this->executeQuery($sql, "i", [$idSeccion]);
        return $row ? $row['estado'] : null;
    }

    /**
     * Obtiene el estado de la version por ID directo.
     * @param int $idVersion
     * @return string|null
     */
    public function getEstadoVersion($idVersion)
    {
        $sql = "SELECT `estado` FROM `cuestionarios_versiones` WHERE `id_version` = ?";
        $row = $this->executeQuery($sql, "i", [$idVersion]);
        return $row ? $row['estado'] : null;
    }

    /**
     * Obtiene datos completos para la vista previa de una seccion.
     * Devuelve la seccion con sus preguntas y metadatos de version.
     * @param int $idSeccion
     * @return array|null
     */
    public function getPreviewSeccion($idSeccion)
    {
        $sql = "SELECT cs.*, cv.`numero_version`, cv.`estado` AS `version_estado`,
                       cp.`nombre_base`, cp.`slug_modulo`
                FROM `cuestionarios_secciones` cs
                INNER JOIN `cuestionarios_versiones` cv ON cv.`id_version` = cs.`id_version`
                INNER JOIN `cuestionarios_plantillas` cp ON cp.`id_plantilla` = cv.`id_plantilla`
                WHERE cs.`id_seccion` = ?
                LIMIT 1";
        $seccion = $this->executeQuery($sql, "i", [$idSeccion]);
        if (!$seccion) return null;

        $seccion['preguntas'] = $this->getPreguntas($idSeccion);
        return $seccion;
    }

    /**
     * Obtiene datos completos para la vista previa de una version completa.
     * @param int $idVersion
     * @return array|null
     */
    public function getPreviewVersion($idVersion)
    {
        $sql = "SELECT cv.*, cp.`nombre_base`, cp.`slug_modulo`
                FROM `cuestionarios_versiones` cv
                INNER JOIN `cuestionarios_plantillas` cp ON cp.`id_plantilla` = cv.`id_plantilla`
                WHERE cv.`id_version` = ?
                LIMIT 1";
        $version = $this->executeQuery($sql, "i", [$idVersion]);
        if (!$version) return null;

        $version['secciones'] = $this->getSecciones($idVersion);
        foreach ($version['secciones'] as &$sec) {
            $sec['preguntas'] = $this->getPreguntas($sec['id_seccion']);
        }
        return $version;
    }

    // ==================== HELPERS PRIVADOS ====================

    /**
     * Ejecuta una consulta con parametros y devuelve una sola fila.
     */
    private function executeQuery($sql, $types, $params)
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("AdminCuestionariosModel: executeQuery - Error: " . $this->db->error);
            return null;
        }
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Ejecuta una consulta con parametros y devuelve multiples filas.
     */
    private function executeAll($sql, $types, $params)
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("AdminCuestionariosModel: executeAll - Error: " . $this->db->error);
            return [];
        }
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}
