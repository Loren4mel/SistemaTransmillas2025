<?php
/**
 * PreoperacionalModel - Modelo para la gestión de preoperacionales
 * 
 * Maneja todas las operaciones de base de datos relacionadas con
 * los registros de preoperacional de vehículos.
 */

require_once __DIR__ . "/../config/database.php";

class PreoperacionalModel
{
    private $db;

    /**
     * Constructor - Inicializa la conexión a base de datos
     */
    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    // ==================== CONSULTAS ====================

    /**
     * Obtiene el tipo de vehículo del perfil del usuario (usu_tipovehiculo).
     * Útil cuando el usuario no tiene vehículo asignado pero su perfil
     * indica que es conductor (Carro) o motociclista (Moto).
     *
     * @param int $idUsuario
     * @return string|null 'Carro', 'Moto' o null
     */
    public function obtenerTipoVehiculoUsuario($idUsuario)
    {
        $sql = "SELECT usu_tipovehiculo FROM usuarios WHERE idusuarios = ? LIMIT 1";
        $row = $this->executeQuery($sql, "i", [$idUsuario]);
        return $row ? ($row['usu_tipovehiculo'] ?: null) : null;
    }

    /**
     * Obtiene el rol de un usuario por ID.
     *
     * @param int $idUsuario
     * @return int|null Rol del usuario o null si no existe
     */
    public function obtenerRolUsuario($idUsuario)
    {
        $sql = "SELECT roles_idroles FROM usuarios WHERE idusuarios = ? LIMIT 1";
        $row = $this->executeQuery($sql, "i", [$idUsuario]);
        return $row ? (int) ($row['roles_idroles'] ?? 0) : null;
    }

    /**
     * Obtiene los datos del vehículo y usuario.
     * Si $idVehiculo es null, devuelve el primer vehículo asignado al usuario.
     * 
     * @param int $idUsuario ID del usuario
     * @param int|null $idVehiculo ID del vehículo (opcional)
     * @return array|null Datos del vehículo y usuario o null si no existe
     */
    public function obtenerDatosVehiculoYUsuario($idUsuario, $idVehiculo = null)
    {
        // Si se especifica un idVehiculo positivo explícitamente, buscarlo directamente
        // sin exigir que el usuario actual lo tenga asignado (importante para
        // el modo vista de preoperacionales históricos).
        if (!empty($idVehiculo) && $idVehiculo > 0) {
            $sql = "SELECT v.idvehiculos, v.veh_tipo, v.veh_placa, v.veh_marca, v.veh_modelo, v.veh_kilactual,
                           v.veh_fechaseguro, v.veh_fechategnomecanica,
                           u.usu_nombre, u.usu_identificacion, u.usu_licencia, u.usu_fechalicencia
                    FROM vehiculos v
                    LEFT JOIN usuarios u ON u.usu_vehiculo = v.idvehiculos AND u.idusuarios = ?
                    WHERE v.idvehiculos = ?
                    LIMIT 1";
            return $this->executeQuery($sql, "ii", [$idUsuario, $idVehiculo]);
        }

        $sql = "SELECT v.idvehiculos, v.veh_tipo, v.veh_placa, v.veh_marca, v.veh_modelo, v.veh_kilactual,
                       v.veh_fechaseguro, v.veh_fechategnomecanica,
                       u.usu_nombre, u.usu_identificacion, u.usu_licencia, u.usu_fechalicencia
                FROM vehiculos v
                INNER JOIN usuarios u ON u.usu_vehiculo = v.idvehiculos
                WHERE u.idusuarios = ?
                LIMIT 1";

        return $this->executeQuery($sql, "i", [$idUsuario]);
    }

    /**
     * Obtiene los datos de preencuesta para precarga
     * 
     * @param int $idUsuario ID del usuario
     * @param string $fecha Fecha del registro (Y-m-d)
     * @param string $campo Campo a obtener (solo 'preencuesta' permitido)
     * @return array|null Datos decodificados o null
     */
    public function obtenerDatosParaPrecarga($idUsuario, $fecha, $campo)
    {
        if ($campo !== 'preencuesta') {
            return null;
        }
        
        $sql = "SELECT $campo FROM `pre-operacional`
                WHERE preidusuario = ?
                AND DATE(prefechaingreso) = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $idUsuario, $fecha);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        
        if ($row && $row[0]) {
            return json_decode($row[0], true);
        }
        
        return null;
    }

    /**
     * Obtiene un registro de preoperacional por fecha
     * 
     * @param int $idUsuario ID del usuario
     * @param string $fecha Fecha del registro (Y-m-d)
     * @return array|null Datos del registro o null
     */
    public function obtenerRegistroPorFecha($idUsuario, $fecha)
    {
        $sql = "SELECT * FROM `pre-operacional`
                WHERE preidusuario = ?
                AND DATE(prefechaingreso) = ?";
        
        return $this->executeQuery($sql, "is", [$idUsuario, $fecha]);
    }

    /**
     * Obtiene un registro de preoperacional por ID
     *
     * @param int $idPreoperacional ID del registro
     * @return array|null Datos del registro o null
     */
    public function obtenerRegistroPorId($idPreoperacional)
    {
        $sql = "SELECT * FROM `pre-operacional`
                WHERE idpreoperacinal = ?";

        return $this->executeQuery($sql, "i", [$idPreoperacional]);
    }

    /**
     * Obtiene el último registro de preoperacional de un usuario
     *
     * @param int $idUsuario ID del usuario
     * @return array|null Datos del registro o null
     */
    public function obtenerUltimoRegistro($idUsuario)
    {
        $sql = "SELECT * FROM `pre-operacional`
                WHERE preidusuario = ?
                ORDER BY prefechaingreso DESC
                LIMIT 1";

        return $this->executeQuery($sql, "i", [$idUsuario]);
    }

    // ==================== INSERCIÓN Y ACTUALIZACIÓN ====================

    /**
     * Inserta un nuevo registro de preoperacional
     * 
     * @param array $datos Datos del preoperacional
     * @return int|false ID del registro insertado o false en caso de error
     */
    public function insertarPreoperacional($datos)
    {
        $sql = "INSERT INTO `pre-operacional`
                (prevehiculo, pretipovehiculo, prefechaingreso, preidusuario, preencuesta,
                 pre_obsevaciones, pre_correctiva, pre_responsable, pre_temperatura, pre_kilrecorridos,
                 pre_limpiomaleta, pre_img_kilo, preestado, pre_ubicacion, pre_firma)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $vehiculo = $datos['prevehiculo'] ?? 0;
        $preUbicacion = $datos['pre_ubicacion'] ?? null;
        $preFirma = $datos['pre_firma'] ?? null;

        $stmt->bind_param(
            "ississsssiisssi",
            $vehiculo,
            $datos['pretipovehiculo'],
            $datos['prefechaingreso'],
            $datos['preidusuario'],
            $datos['preencuesta'],
            $datos['pre_obsevaciones'],
            $datos['pre_correctiva'],
            $datos['pre_responsable'],
            $datos['pre_temperatura'],
            $datos['pre_kilrecorridos'],
            $datos['pre_limpiomaleta'],
            $datos['pre_img_kilo'],
            $datos['preestado'],
            $preUbicacion,
            $preFirma
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        
        return false;
    }

    /**
     * Actualiza un registro de preoperacional existente
     * 
     * @param int $id ID del registro
     * @param array $datos Datos a actualizar
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function actualizarPreoperacional($id, $datos)
    {
        $sql = "UPDATE `pre-operacional` SET
                prefechavalidacion = ?,
                predatosvalidados = ?,
                pre_descvalidada = ?,
                pre_iduservalida = ?,
                preestado = ?,
                pre_correctiva = ?,
                pre_responsable = ?,
                pre_temperatura = ?,
                pre_kilrecorridos = ?
                WHERE idpreoperacinal = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sssisssssi",
            $datos['prefechavalidacion'],
            $datos['predatosvalidados'],
            $datos['pre_descvalidada'],
            $datos['pre_iduservalida'],
            $datos['preestado'],
            $datos['pre_correctiva'],
            $datos['pre_responsable'],
            $datos['pre_temperatura'],
            $datos['pre_kilrecorridos'],
            $id
        );
        
        return $stmt->execute();
    }

    /**
     * Actualiza campos específicos de un registro de preoperacional
     *
     * @param int $id ID del registro
     * @param array $campos Array asociativo de campos a actualizar [campo => valor]
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function actualizarCamposPreoperacional($id, $campos)
    {
        if (empty($campos)) {
            error_log("PreoperacionalModel: actualizarCamposPreoperacional - Array de campos vacío");
            return false;
        }

        $columnasPermitidas = [
            'preencuesta', 'predatosvalidados', 'pre_descvalidada', 'preestado',
            'pre_correctiva', 'pre_responsable', 'pre_temperatura', 'pre_kilrecorridos',
            'pre_limpiomaleta', 'pre_img_kilo', 'pre_obsevaciones',
            'prefechavalidacion', 'pre_iduservalida', 'id_version',
            'pre_ubicacion', 'pre_firma'
        ];

        $setParts = [];
        $types = '';
        $values = [];

        foreach ($campos as $campo => $valor) {
            if (!in_array($campo, $columnasPermitidas, true)) {
                error_log("PreoperacionalModel: actualizarCamposPreoperacional - Columna no permitida: $campo");
                continue;
            }
            $setParts[] = "$campo = ?";
            // Determinar tipo basado en el valor
            if (is_int($valor)) {
                $types .= 'i';
            } elseif (is_float($valor)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $valor;
        }

        $values[] = $id; // ID al final
        $types .= 'i';

        $sql = "UPDATE `pre-operacional` SET " . implode(', ', $setParts) . " WHERE idpreoperacinal = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("PreoperacionalModel: actualizarCamposPreoperacional - Error preparando SQL: " . $this->db->error);
            return false;
        }

        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();

        if (!$result) {
            error_log("PreoperacionalModel: actualizarCamposPreoperacional - Error ejecutando: " . $stmt->error);
        }

        return $result;
    }

    /**
     * Actualiza el kilometraje del vehículo y calcula KM restantes para cambio de aceite
     * 
     * @param int $idVehiculo ID del vehículo
     * @param int $kilometraje Kilometraje actual
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function actualizarKilometrajeVehiculo($idVehiculo, $kilometraje)
    {
        // Obtener kilometraje anterior y faltante para cambio de aceite
        $sql = "SELECT veh_kilactual, veh_faltaparacambioaceite FROM vehiculos WHERE idvehiculos = ?";
        $row = $this->executeQuery($sql, "i", [$idVehiculo]);
        
        if (!$row) {
            return false;
        }

        $kmAnterior = (int) $row['veh_kilactual'];
        $kmRestanteAceite = (int) $row['veh_faltaparacambioaceite'];
        $kmRecorridos = $kilometraje - $kmAnterior;
        $nuevoRestante = $kmRestanteAceite - $kmRecorridos;

        $sqlUpdate = "UPDATE vehiculos SET veh_kilactual = ?, veh_restankmaceite = ?, veh_faltaparacambioaceite = ? WHERE idvehiculos = ?";
        $stmtUpdate = $this->db->prepare($sqlUpdate);
        
        return $stmtUpdate->bind_param("iiii", $kilometraje, $kmRecorridos, $nuevoRestante, $idVehiculo)
               && $stmtUpdate->execute();
    }

    /**
     * Obtiene el kilometraje actual registrado para un vehículo.
     *
     * @param int $idVehiculo ID del vehículo
     * @return int Kilometraje actual (0 si no existe o está vacío)
     */
    public function obtenerKilometrajeVehiculo($idVehiculo)
    {
        $sql = "SELECT veh_kilactual FROM vehiculos WHERE idvehiculos = ?";
        $row = $this->executeQuery($sql, "i", [$idVehiculo]);

        if (!$row || empty($row['veh_kilactual'])) {
            return 0;
        }

        return (int) $row['veh_kilactual'];
    }

    // ==================== GESTIÓN DE IMÁGENES ====================

    /**
     * Obtiene la ruta base para almacenar imágenes de preoperacional.
     */
    private function getUploadPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
             . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pre-operacional' . DIRECTORY_SEPARATOR;
    }

    /**
     * Guarda una imagen asociada al preoperacional
     *
     * @param array $file Archivo subido ($_FILES)
     * @param int $idPreoperacional ID del registro
     * @param int $version Versión del documento
     * @return int|false ID del documento insertado o false en caso de error
     */
    public function guardarImagen($file, $idPreoperacional, $version)
    {
        if (empty($file['tmp_name'])) {
            error_log("PreoperacionalModel: guardarImagen - tmp_name vacío");
            return false;
        }

        $nombreArchivo = date("Y-m-d-H-i-s") . "_" . $file['name'];
        $rutaBase = $this->getUploadPath();
        $ruta = $rutaBase . $nombreArchivo;

        if (!is_dir($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $ruta)) {
            $sql = "INSERT INTO documentos (doc_fecha, doc_nombre, doc_ruta, doc_tabla, doc_idviene, doc_version)
                    VALUES (NOW(), ?, ?, 'pre-operacional', ?, ?)";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->bind_param("ssii", $file['name'], $ruta, $idPreoperacional, $version)
                   && $stmt->execute();

            if ($result) {
                return $this->db->insert_id;
            }

            return false;
        } else {
            error_log("PreoperacionalModel: guardarImagen - ERROR al mover archivo");
        }

        return false;
    }

    /**
     * Guarda una imagen desde una ruta de archivo existente
     * (Para imágenes generadas como firmas base64 convertidas a archivo)
     *
     * @param string $rutaArchivo Ruta completa al archivo temporal
     * @param string $nombreOriginal Nombre original del archivo
     * @param int $idPreoperacional ID del registro
     * @param int $version Versión del documento
     * @return int|false ID del documento insertado o false en caso de error
     */
    public function guardarImagenDesdeRuta($rutaArchivo, $nombreOriginal, $idPreoperacional, $version)
    {
        if (!file_exists($rutaArchivo)) {
            error_log("PreoperacionalModel: guardarImagenDesdeRuta - ERROR: Archivo temporal no existe: " . $rutaArchivo);
            return false;
        }

        $nombreArchivo = date("Y-m-d-H-i-s") . "_" . $nombreOriginal;
        $rutaBase = $this->getUploadPath();
        $rutaDestino = $rutaBase . $nombreArchivo;

        if (!is_dir($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        if (copy($rutaArchivo, $rutaDestino)) {
            $sql = "INSERT INTO documentos (doc_fecha, doc_nombre, doc_ruta, doc_tabla, doc_idviene, doc_version)
                    VALUES (NOW(), ?, ?, 'pre-operacional', ?, ?)";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->bind_param("ssii", $nombreOriginal, $rutaDestino, $idPreoperacional, $version)
                   && $stmt->execute();

            if (!$result) {
                error_log("PreoperacionalModel: guardarImagenDesdeRuta - Error SQL: " . $stmt->error);
                return false;
            }

            return $this->db->insert_id;
        } else {
            error_log("PreoperacionalModel: guardarImagenDesdeRuta - ERROR al copiar archivo");
        }

        return false;
    }

    /**
     * Actualiza la imagen de kilometraje de un registro
     * 
     * @param int $idPreoperacional ID del registro
     * @param string $rutaImagen Ruta de la imagen
     * @return bool True si la actualización fue exitosa, false en caso contrario
     */
    public function actualizarImagenKilo($idPreoperacional, $rutaImagen)
    {
        $sql = "UPDATE `pre-operacional` SET pre_img_kilo = ?
                WHERE idpreoperacinal = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->bind_param("si", $rutaImagen, $idPreoperacional) 
               && $stmt->execute();
    }

    /**
     * Obtiene el documento de firma asociado a un preoperacional
     *
     * @param int $idPreoperacional ID del registro preoperacional
     * @return array|null Datos del documento (doc_ruta, doc_nombre) o null si no existe
     */
    public function obtenerDocumentoFirma($idPreoperacional)
    {
        $sql = "SELECT doc_ruta, doc_nombre FROM documentos
                WHERE doc_idviene = ? AND doc_version = 4 AND doc_tabla = 'pre-operacional'
                LIMIT 1";
        return $this->executeQuery($sql, "i", [$idPreoperacional]);
    }

    // ==================== ESQUEMA RELACIONAL: VERSIONES Y PLANTILLAS ====================

    /**
     * Resuelve la versión activa de una plantilla por tipo de vehículo.
     * Busca plantillas VEHICULO que apliquen al tipo_vehiculo dado.
     *
     * @param string $tipoVehiculo CARRO o MOTO
     * @return array|null {id_version, id_plantilla, nombre_base, numero_version}
     */
    public function obtenerVersionActivaVehiculo($tipoVehiculo)
    {
        $sql = "SELECT v.id_version, v.id_plantilla, p.nombre_base, v.numero_version
                FROM preop_versiones v
                INNER JOIN preop_plantillas p ON v.id_plantilla = p.id_plantilla
                WHERE p.tipo_destinatario = 'VEHICULO'
                  AND p.aplica_a_tipo_vehiculo = ?
                  AND v.estado = 'ACTIVA'
                  AND v.fecha_vigencia_fin IS NULL
                LIMIT 1";
        return $this->executeQuery($sql, "s", [$tipoVehiculo]);
    }

    /**
     * Resuelve la versión activa de una plantilla de USUARIO por rol + tipo_vehiculo.
     *
     * PRIORIDAD DE RESOLUCIÓN:
     *   1. Match por tipo_vehiculo (la vista ya determinó el contexto: conductor, admin, etc.)
     *   2. Match por rol específico (FIND_IN_SET)
     *   3. Plantilla marcada como es_default = 1 (catch-all para roles no mapeados)
     *
     * @param int $rolUsuario Rol del usuario (idroles)
     * @param string $tipoVehiculo CARRO, MOTO o vacío
     * @return array|null {id_version, id_plantilla, nombre_base, numero_version}
     */
    public function obtenerVersionActivaUsuario($rolUsuario, $tipoVehiculo)
    {
        $rolStr = (string) $rolUsuario;
        $sql = "SELECT v.id_version, v.id_plantilla, p.nombre_base, v.numero_version
                FROM preop_versiones v
                INNER JOIN preop_plantillas p ON v.id_plantilla = p.id_plantilla
                WHERE p.tipo_destinatario = 'USUARIO'
                  AND v.estado = 'ACTIVA'
                  AND v.fecha_vigencia_fin IS NULL
                  AND (
                      (p.aplica_a_tipo_vehiculo IS NOT NULL AND p.aplica_a_tipo_vehiculo = ?)
                      OR FIND_IN_SET(?, p.aplica_a_roles)
                      OR p.es_default = 1
                  )
                ORDER BY
                  CASE WHEN (p.aplica_a_tipo_vehiculo IS NOT NULL AND p.aplica_a_tipo_vehiculo = ?) THEN 0 ELSE 1 END,
                  CASE WHEN FIND_IN_SET(?, p.aplica_a_roles) THEN 0 ELSE 1 END,
                  CASE WHEN p.es_default = 1 THEN 0 ELSE 1 END
                LIMIT 1";
        return $this->executeQuery($sql, "ssss", [$tipoVehiculo, $rolStr, $tipoVehiculo, $rolStr]);
    }

    /**
     * Carga secciones y preguntas de una versión específica.
     *
     * @param int $idVersion
     * @return array Secciones con sus preguntas anidadas
     */
    public function obtenerSeccionesYPreguntas($idVersion)
    {
        $sql = "SELECT s.id_seccion, s.nombre, s.css_clase, s.orden AS sec_orden,
                       p.id_pregunta, p.codigo_interno, p.texto_pregunta, p.tipo_respuesta,
                       p.respuesta_esperada, p.requiere_foto_si_negativa, p.genera_bloqueo, p.orden AS preg_orden
                FROM preop_secciones s
                INNER JOIN preop_preguntas p ON s.id_seccion = p.id_seccion
                WHERE s.id_version = ?
                ORDER BY s.orden, p.orden";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVersion);
        $stmt->execute();
        $result = $stmt->get_result();

        $secciones = [];
        while ($row = $result->fetch_assoc()) {
            $idSec = $row['id_seccion'];
            if (!isset($secciones[$idSec])) {
                $secciones[$idSec] = [
                    'id_seccion' => $row['id_seccion'],
                    'nombre' => $row['nombre'],
                    'css_clase' => $row['css_clase'],
                    'orden' => $row['sec_orden'],
                    'preguntas' => []
                ];
            }
            $secciones[$idSec]['preguntas'][] = [
                'id_pregunta' => $row['id_pregunta'],
                'codigo_interno' => $row['codigo_interno'],
                'texto_pregunta' => $row['texto_pregunta'],
                'tipo_respuesta' => $row['tipo_respuesta'],
                'respuesta_esperada' => $row['respuesta_esperada'],
                'requiere_foto_si_negativa' => $row['requiere_foto_si_negativa'],
                'genera_bloqueo' => $row['genera_bloqueo'],
                'orden' => $row['preg_orden']
            ];
        }

        return array_values($secciones);
    }

    /**
     * Obtiene el mapping codigo_interno => id_pregunta para una versión dada.
     * Usado para resolver respuestas del formulario a IDs de pregunta.
     *
     * @param int $idVersion
     * @return array [codigo_interno => id_pregunta]
     */
    public function obtenerMappingCodigosAPreguntas($idVersion)
    {
        $sql = "SELECT p.codigo_interno, p.id_pregunta
                FROM preop_preguntas p
                INNER JOIN preop_secciones s ON p.id_seccion = s.id_seccion
                WHERE s.id_version = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVersion);
        $stmt->execute();
        $result = $stmt->get_result();

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $map[$row['codigo_interno']] = $row['id_pregunta'];
        }
        return $map;
    }

    /**
     * Obtiene el texto legible de preguntas dado un conjunto de códigos internos.
     * Usado para generar observaciones automáticas desde las respuestas del preoperacional.
     *
     * @param array $codigos Lista de códigos internos (ej: ['inspec_1', 'seguridad_3'])
     * @return array [codigo_interno => texto_pregunta]
     */
    public function obtenerTextoPorCodigos(array $codigos): array
    {
        if (empty($codigos)) return [];
        $placeholders = implode(',', array_fill(0, count($codigos), '?'));
        $sql = "SELECT codigo_interno, texto_pregunta
                FROM preop_preguntas
                WHERE codigo_interno IN ($placeholders)
                GROUP BY codigo_interno";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("obtenerTextoPorCodigos prepare error: " . $this->db->error);
            return [];
        }
        $types = str_repeat('s', count($codigos));
        $stmt->bind_param($types, ...$codigos);
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        while ($row = $result->fetch_assoc()) {
            $map[$row['codigo_interno']] = $row['texto_pregunta'];
        }
        return $map;
    }

    // ==================== ESQUEMA RELACIONAL: RESPUESTAS ====================

    /**
     * Inserta múltiples respuestas en una sola operación (multi-row INSERT).
     * MITIGACIÓN DE PERFORMANCE: Evita N INSERTs individuales.
     *
     * @param array $respuestas Array de arrays: [['id_preop' => X, 'id_pregunta' => Y, 'respuesta' => '1', 'foto' => null], ...]
     * @return bool True si se insertó exitosamente
     */
    public function insertarRespuestasBatch($respuestas)
    {
        if (empty($respuestas)) {
            return false;
        }

        $values = [];
        $params = [];
        $types = '';

        foreach ($respuestas as $r) {
            $values[] = "(?, ?, ?, ?)";
            $params[] = $r['id_preoperacional'];
            $types .= 'i';
            $params[] = $r['id_pregunta'];
            $types .= 'i';
            $params[] = $r['respuesta_dada'];
            $types .= 's';
            $params[] = $r['ruta_foto'] ?? null;
            $types .= 's';
        }

        $sql = "INSERT INTO preop_respuestas (id_preoperacional, id_pregunta, respuesta_dada, ruta_foto)
                VALUES " . implode(', ', $values);

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("PreoperacionalModel: insertarRespuestasBatch - Error SQL: " . $this->db->error);
            return false;
        }

        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    /**
     * Obtiene TODAS las respuestas de un preoperacional, sin filtrar por versión.
     * Crucial para el read path: el registro almacena solo un id_version (el primario
     * de vehículo), pero las respuestas existen en múltiples versiones (vehículo + usuario).
     *
     * @param int $idPreoperacional
     * @return array [codigo_interno => respuesta_dada]
     */
    public function obtenerTodasRespuestas($idPreoperacional)
    {
        $sql = "SELECT p.codigo_interno, r.respuesta_dada, r.ruta_foto
                FROM preop_respuestas r
                INNER JOIN preop_preguntas p ON r.id_pregunta = p.id_pregunta
                WHERE r.id_preoperacional = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idPreoperacional);
        $stmt->execute();
        $result = $stmt->get_result();

        $respuestas = [];
        while ($row = $result->fetch_assoc()) {
            $respuestas[$row['codigo_interno']] = $row['respuesta_dada'];
            if (!empty($row['ruta_foto'])) {
                $respuestas[$row['codigo_interno'] . '_foto'] = $row['ruta_foto'];
            }
        }
        return $respuestas;
    }

    /**
     * Obtiene los IDs de documentos asociados a un preoperacional, indexados
     * por su clave semántica (firma_documento_id, inspeccion_documento_id,
     * temperatura_documento_id). Sustituye la dependencia del JSON de
     * preencuesta para registros del esquema relacional.
     *
     * @param int $idPreoperacional
     * @return array [clave_semantica => iddocumentos]
     */
    public function obtenerDocumentosPorPreoperacional($idPreoperacional)
    {
        $sql = "SELECT iddocumentos, doc_version
                FROM documentos
                WHERE doc_idviene = ? AND doc_tabla = 'pre-operacional'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idPreoperacional);
        $stmt->execute();
        $result = $stmt->get_result();

        $docs = [];
        while ($row = $result->fetch_assoc()) {
            switch ((int) $row['doc_version']) {
                case 2: $docs['temperatura_documento_id'] = (int) $row['iddocumentos']; break;
                case 3: $docs['inspeccion_documento_id']  = (int) $row['iddocumentos']; break;
                case 4: $docs['firma_documento_id']       = (int) $row['iddocumentos']; break;
                // otros tipos de documento no se mapean automáticamente
            }
        }
        return $docs;
    }

    // ==================== SEGUIMIENTO VEHICULAR ====================

    /**
     * Inserta un registro en seguimiento_vehiculo (libro de vida del vehículo)
     *
     * @param array $datos Datos del seguimiento
     * @return int|false ID del registro insertado o false en caso de error
     */
    public function insertarSeguimientoVehiculo($datos)
    {
        $sql = "INSERT INTO seguimiento_vehiculo
                (tipo_evento, metadata_evento, id_preoperacional, id_seguimiento_user,
                 id_vehiculo, id_conductor, id_responsable, kilometraje, ubicacion,
                 estado_general, foto_evidencia, img_kilometraje, observaciones,
                 entrega_final_usuario, entrega_inicial_usuario)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $metadataJson = !empty($datos['metadata_evento']) ? json_encode($datos['metadata_evento']) : null;

        // Extraer a variables: bind_param requiere referencias, no expresiones
        $tipoEvento         = $datos['tipo_evento'] ?? '';
        $idPreoperacional   = $datos['id_preoperacional'] ?? null;
        $idSeguimientoUser  = $datos['id_seguimiento_user'] ?? null;
        $idVehiculo         = $datos['id_vehiculo'] ?? null;
        $idConductor        = $datos['id_conductor'] ?? null;
        $idResponsable      = $datos['id_responsable'] ?? null;
        $kilometraje        = $datos['kilometraje'] ?? null;
        $ubicacion          = $datos['ubicacion'] ?? null;
        $estadoGeneral      = $datos['estado_general'] ?? '';
        $fotoEvidencia      = $datos['foto_evidencia'] ?? null;
        $imgKilometraje     = $datos['img_kilometraje'] ?? null;
        $observaciones      = $datos['observaciones'] ?? null;
        $entregaFinal       = $datos['entrega_final_usuario'] ?? null;
        $entregaInicial     = $datos['entrega_inicial_usuario'] ?? null;

        $stmt->bind_param(
            "ssiiiiiisssssii",
            $tipoEvento,
            $metadataJson,
            $idPreoperacional,
            $idSeguimientoUser,
            $idVehiculo,
            $idConductor,
            $idResponsable,
            $kilometraje,
            $ubicacion,
            $estadoGeneral,
            $fotoEvidencia,
            $imgKilometraje,
            $observaciones,
            $entregaFinal,
            $entregaInicial
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Obtiene el último registro de seguimiento de un vehículo
     *
     * @param int $idVehiculo ID del vehículo
     * @return array|null Último registro de seguimiento o null
     */
    public function obtenerUltimoSeguimientoPorVehiculo($idVehiculo)
    {
        $sql = "SELECT * FROM seguimiento_vehiculo
                WHERE id_vehiculo = ?
                ORDER BY fecha_registro DESC
                LIMIT 1";
        return $this->executeQuery($sql, "i", [$idVehiculo]);
    }

    /**
     * Obtiene el último registro preoperacional de un vehículo específico.
     *
     * @param int $idVehiculo ID del vehículo
     * @return array|null Registro con idpreoperacinal y prefechaingreso, o null
     */
    public function obtenerUltimoPreoperacionalPorVehiculo($idVehiculo)
    {
        $sql = "SELECT idpreoperacinal, prefechaingreso, prevehiculo
                FROM `pre-operacional`
                WHERE prevehiculo = ?
                ORDER BY prefechaingreso DESC
                LIMIT 1";
        return $this->executeQuery($sql, "i", [$idVehiculo]);
    }

    /**
     * Obtiene el último registro de tipo REVISION_SST para un vehículo.
     * REVISION_SST es la fuente de verdad autoritativa sobre el estado del vehículo,
     * ya que representa una inspección de seguridad, no una validación diaria.
     *
     * @param int $idVehiculo ID del vehículo
     * @return array|null Último registro REVISION_SST o null
     */
    public function obtenerUltimoSeguimientoRevisionSST($idVehiculo)
    {
        $sql = "SELECT * FROM seguimiento_vehiculo
                WHERE id_vehiculo = ? AND tipo_evento = 'REVISION_SST'
                ORDER BY fecha_registro DESC
                LIMIT 1";
        return $this->executeQuery($sql, "i", [$idVehiculo]);
    }

    /**
     * Actualiza el estado_general de un registro de seguimiento vinculado a un preoperacional
     *
     * @param int $idPreoperacional ID del preoperacional
     * @param string $estadoGeneral OPTIMO, CON_NOVEDADES o FUERA_DE_SERVICIO
     * @return bool True si se actualizó correctamente
     */
    public function actualizarSeguimientoVehiculoEstado($idPreoperacional, $estadoGeneral)
    {
        $sql = "UPDATE seguimiento_vehiculo SET estado_general = ?
                WHERE id_preoperacional = ? AND tipo_evento = 'PREOPERACIONAL'
                ORDER BY fecha_registro DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $estadoGeneral, $idPreoperacional);
        return $stmt->execute();
    }

    /**
     * Obtiene vehículos activos disponibles para preoperacional.
     *
     * REGLAS DE DISPONIBILIDAD:
     * 1. veh_propiedad = 'empresa' — solo vehículos de la compañía.
     *    veh_propiedad vacío o 'propio' = personal (excluidos).
     * 2. veh_estado = 1 — solo vehículos activos
     * 3. Último seguimiento NO es FUERA_DE_SERVICIO
     * 4. Sin PREOPERACIONAL hoy de OTRO usuario — un vehículo solo puede
     *    tener un preoperacional por día (el mismo usuario sí puede volver
     *    a seleccionar su vehículo si ya lo tenía hoy)
     *
     * NOTA: No se filtra por usu_vehiculo (conductor asignado). Un vehículo
     * con conductor asignado sigue disponible si ese conductor no ha registrado
     * preoperacional hoy. Al asignarlo, se libera automáticamente del conductor
     * anterior vía liberarVehiculoDeCualquierUsuario().
     *
     * @param int|null $idUsuarioActual ID del usuario actual (para excluir
     *                                   vehículos ya usados por otros hoy)
     * @return array Lista de vehículos disponibles
     */
    public function obtenerVehiculosDisponibles($idUsuarioActual = null)
    {
        $sql = "SELECT v.idvehiculos, v.veh_tipo, v.veh_placa, v.veh_marca, v.veh_modelo,
                       v.veh_kilactual, v.veh_estado, v.veh_propiedad
                FROM vehiculos v
                WHERE v.veh_estado = 1
                  AND v.veh_propiedad = 'empresa'
                  AND v.idvehiculos NOT IN (
                      SELECT sv.id_vehiculo
                      FROM seguimiento_vehiculo sv
                      WHERE sv.estado_general = 'FUERA_DE_SERVICIO'
                        AND sv.id_seguimiento_vehiculo = (
                            SELECT MAX(sv2.id_seguimiento_vehiculo)
                            FROM seguimiento_vehiculo sv2
                            WHERE sv2.id_vehiculo = sv.id_vehiculo
                        )
                  )";

        // REGLA 4: Excluir vehículos que ya tuvieron un PREOPERACIONAL hoy
        // de otro conductor. El mismo usuario sí puede volver a seleccionar
        // su vehículo (ej: si recargó la página o va a editar su registro).
        if ($idUsuarioActual !== null && $idUsuarioActual > 0) {
            $sql .= " AND v.idvehiculos NOT IN (
                          SELECT sv3.id_vehiculo
                          FROM seguimiento_vehiculo sv3
                          WHERE sv3.tipo_evento = 'PREOPERACIONAL'
                            AND DATE(sv3.fecha_registro) = CURDATE()
                            AND sv3.id_conductor IS NOT NULL
                            AND sv3.id_conductor != ?
                      )";
        }

        $sql .= " ORDER BY v.veh_tipo, v.veh_placa";

        $stmt = $this->db->prepare($sql);
        if ($idUsuarioActual !== null && $idUsuarioActual > 0) {
            $stmt->bind_param("i", $idUsuarioActual);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $vehiculos = [];
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        return $vehiculos;
    }

    /**
     * Asigna un vehículo a un usuario (actualiza usu_vehiculo)
     *
     * @param int $idVehiculo ID del vehículo
     * @param int $idUsuario ID del usuario
     * @return bool True si se actualizó correctamente
     */
    public function asignarVehiculoAUsuario($idVehiculo, $idUsuario)
    {
        $sql = "UPDATE usuarios SET usu_vehiculo = ? WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $idVehiculo, $idUsuario);
        return $stmt->execute();
    }

    /**
     * Libera un vehículo de cualquier usuario que lo tenga asignado.
     *
     * Se usa antes de reasignar un vehículo a un nuevo conductor para evitar
     * que el conductor anterior conserve una referencia huérfana (usu_vehiculo
     * apuntando a un vehículo que ya no le pertenece).
     *
     * @param int $idVehiculo ID del vehículo a liberar
     * @return bool True si se actualizó (o no había nadie que liberar)
     */
    public function liberarVehiculoDeCualquierUsuario($idVehiculo)
    {
        $sql = "UPDATE usuarios SET usu_vehiculo = NULL WHERE usu_vehiculo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVehiculo);
        return $stmt->execute();
    }

    /**
     * Busca las entregas de vehículo pendientes de validación para un usuario
     * (conductor). Busca el último REVISION_SST que tenga entregas vinculadas
     * para cualquier vehículo que haya usado este conductor.
     *
     * @param int $idUsuario ID del conductor
     * @return array ['final' => entrega|null, 'inicial' => entrega|null, 'seguimiento' => array|null]
     */
    public function obtenerEntregasPendientesPorUsuario($idUsuario)
    {
        // Buscar el último REVISION_SST que tenga entregas vinculadas y
        // cuyo conductor sea este usuario.
        $sql = "SELECT sv.*
                FROM seguimiento_vehiculo sv
                WHERE sv.id_conductor = ?
                  AND sv.tipo_evento = 'REVISION_SST'
                  AND (sv.entrega_final_usuario IS NOT NULL OR sv.entrega_inicial_usuario IS NOT NULL)
                ORDER BY sv.fecha_registro DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $seguimiento = $result->fetch_assoc();

        if (!$seguimiento) {
            return ['final' => null, 'inicial' => null, 'seguimiento' => null];
        }

        $entregaFinal = null;
        $entregaInicial = null;

        if (!empty($seguimiento['entrega_final_usuario'])) {
            $entregaFinal = $this->obtenerEntregaVehiculoPorId((int) $seguimiento['entrega_final_usuario']);
        }
        if (!empty($seguimiento['entrega_inicial_usuario'])) {
            $entregaInicial = $this->obtenerEntregaVehiculoPorId((int) $seguimiento['entrega_inicial_usuario']);
        }

        return [
            'final' => $entregaFinal,
            'inicial' => $entregaInicial,
            'seguimiento' => $seguimiento
        ];
    }

    /**
     * Desasigna el vehículo de un usuario (pone usu_vehiculo a NULL)
     *
     * @param int $idUsuario ID del usuario
     * @return bool True si se actualizó correctamente
     */
    public function desasignarVehiculoDeUsuario($idUsuario)
    {
        $sql = "UPDATE usuarios SET usu_vehiculo = NULL WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        return $stmt->execute();
    }

    // ==================== ENTREGA DE VEHÍCULO ====================

    /**
     * Inserta un registro en entregavehiculo con imágenes ya procesadas.
     * Versión simplificada de VehiculosModel::guardarEntregaVehiculo() que no
     * depende de $_FILES (las rutas de imágenes ya vienen procesadas).
     *
     * @param array $datos Datos de la entrega (con rutas de imagen ya guardadas)
     * @return int|array ID del registro insertado o array con error
     */
    public function insertarEntregaVehiculo($datos)
    {
        $sql = "INSERT INTO entregavehiculo (
                    ent_fechaentrega, ent_vehiculo, ent_idvehiculo, ent_userregistra, ent_idusuario,
                    ent_idusuarioencargado, ent_tipoentrega, ent_fecharegistra, ent_idhojadevida, ent_sede,
                    ent_img_frente, ent_img_trasera, ent_equipo_carretera,
                    ent_observaciones, ent_firma
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['error' => 'Prepare falló: ' . $this->db->error];
        }

        $stmt->bind_param(
            "ssisiisssisssss",
            $datos['ent_fechaentrega'],
            $datos['ent_vehiculo'],
            $datos['ent_idvehiculo'],
            $datos['ent_userregistra'],
            $datos['ent_idusuario'],
            $datos['ent_idusuarioencargado'],
            $datos['ent_tipoentrega'],
            $datos['ent_fecharegistra'],
            $datos['ent_idhojadevida'],
            $datos['ent_sede'],
            $datos['ent_img_frente'],
            $datos['ent_img_trasera'],
            $datos['ent_equipo_carretera'],
            $datos['ent_observaciones'],
            $datos['ent_firma']
        );

        $resultado = $stmt->execute();
        if (!$resultado) {
            return ['error' => 'Execute falló: ' . $stmt->error];
        }

        $idInsertado = $this->db->insert_id;
        $stmt->close();
        return $idInsertado;
    }

    /**
     * Obtiene un registro de entrega de vehículo por su ID
     *
     * @param int $id ID del registro en entregavehiculo
     * @return array|null Registro como array asociativo o null
     */
    public function obtenerEntregaVehiculoPorId($id)
    {
        $sql = "SELECT * FROM entregavehiculo WHERE identregavehiculo = ? LIMIT 1";
        return $this->executeQuery($sql, "i", [$id]);
    }

    /**
     * Actualiza un registro existente en entregavehiculo.
     * Solo actualiza los campos que se proveen en $datos.
     *
     * @param int $id ID del registro en entregavehiculo
     * @param array $datos Campos a actualizar (ent_firma, ent_observaciones,
     *                     ent_img_frente, ent_img_trasera, ent_equipo_carretera,
     *                     ent_idvehiculo, ent_idusuarioencargado)
     * @return bool True si se actualizó correctamente
     */
    public function actualizarEntregaVehiculo($id, $datos)
    {
        $permitidos = [
            'ent_firma'               => 's',
            'ent_observaciones'       => 's',
            'ent_img_frente'          => 's',
            'ent_img_trasera'         => 's',
            'ent_equipo_carretera'    => 's',
            'ent_idvehiculo'          => 'i',
            'ent_idusuarioencargado'  => 'i',
        ];

        $sets = [];
        $params = [];
        $types = '';

        foreach ($permitidos as $columna => $tipo) {
            if (array_key_exists($columna, $datos)) {
                $sets[] = "$columna = ?";
                $params[] = $datos[$columna];
                $types .= $tipo;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $types .= 'i';
        $params[] = $id;

        $sql = "UPDATE entregavehiculo SET " . implode(', ', $sets) . " WHERE identregavehiculo = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$params);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Ejecuta una consulta con parámetros y devuelve un resultado
     * 
     * @param string $sql Consulta SQL
     * @param string $types Tipos de parámetros para bind_param
     * @param array $params Parámetros de la consulta
     * @return array|null Resultado como array asociativo o null
     */
    private function executeQuery($sql, $types, $params)
    {
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>