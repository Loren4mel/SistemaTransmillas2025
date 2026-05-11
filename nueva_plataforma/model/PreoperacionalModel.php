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
     * Obtiene los datos del vehículo y usuario.
     * Si $idVehiculo es null, devuelve el primer vehículo asignado al usuario.
     * 
     * @param int $idUsuario ID del usuario
     * @param int|null $idVehiculo ID del vehículo (opcional)
     * @return array|null Datos del vehículo y usuario o null si no existe
     */
    public function obtenerDatosVehiculoYUsuario($idUsuario, $idVehiculo = null)
    {
        $sql = "SELECT v.idvehiculos, v.veh_tipo, v.veh_placa, v.veh_marca, v.veh_modelo, v.veh_kilactual,
                       u.usu_nombre, u.usu_identificacion, u.usu_licencia, u.usu_fechalicencia
                FROM vehiculos v
                INNER JOIN usuarios u ON u.usu_vehiculo = v.idvehiculos
                WHERE u.idusuarios = ?";
        $params = [$idUsuario];
        $types = "i";
        
        if ($idVehiculo !== null) {
            $sql .= " AND v.idvehiculos = ?";
            $params[] = $idVehiculo;
            $types .= "i";
        }
        
        $sql .= " LIMIT 1";
        
        return $this->executeQuery($sql, $types, $params);
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
                 pre_limpiomaleta, pre_img_kilo, preestado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $vehiculo = $datos['prevehiculo'] ?? 0;
        
        $stmt->bind_param(
            "ississsssiiss",
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
            $datos['preestado']
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
            'prefechavalidacion', 'pre_iduservalida'
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