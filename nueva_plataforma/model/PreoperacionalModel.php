<?php
// model/PreoperacionalModel.php
require_once "../config/database.php";

class PreoperacionalModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /**
     * Obtiene los datos del vehículo y usuario. Si $idVehiculo es null,
     * devuelve el primer vehículo asignado al usuario.
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
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    /**
     * Devuelve los datos guardados de preencuesta decodificados (array asociativo).
     */
    public function obtenerDatosParaPrecarga($idUsuario, $fecha, $campo)
    {
        if ($campo !== 'preencuesta')
            return null;
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

    public function obtenerRegistroPorFecha($idUsuario, $fecha)
    {
        $sql = "SELECT * FROM `pre-operacional` 
                WHERE preidusuario = ? 
                AND DATE(prefechaingreso) = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $idUsuario, $fecha);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function obtenerRegistroPorId($idPreoperacional)
    {
        $sql = "SELECT * FROM `pre-operacional` 
                WHERE idpreoperacinal = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idPreoperacional);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

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

    public function actualizarKilometrajeVehiculo($idVehiculo, $kilometraje)
    {
        // Obtener kilometraje anterior y faltante para cambio de aceite
        $sql = "SELECT veh_kilactual, veh_faltaparacambioaceite FROM vehiculos WHERE idvehiculos = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVehiculo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if (!$row)
            return false;

        $kmAnterior = (int) $row['veh_kilactual'];
        $kmRestanteAceite = (int) $row['veh_faltaparacambioaceite'];

        $kmRecorridos = $kilometraje - $kmAnterior;
        $nuevoRestante = $kmRestanteAceite - $kmRecorridos;

        $sqlUpdate = "UPDATE vehiculos SET veh_kilactual = ?, veh_restankmaceite = ?, veh_faltaparacambioaceite = ? WHERE idvehiculos = ?";
        $stmtUpdate = $this->db->prepare($sqlUpdate);
        $stmtUpdate->bind_param("iiii", $kilometraje, $kmRecorridos, $nuevoRestante, $idVehiculo);
        return $stmtUpdate->execute();
    }

    public function guardarImagen($file, $idPreoperacional, $version)
    {
        if (empty($file['tmp_name'])) {
            return false;
        }
        $nombreArchivo = date("Y-m-d-H-i-s") . "_" . $file['name'];
        $ruta = "./preoperacional/" . $nombreArchivo;
        if (move_uploaded_file($file['tmp_name'], $ruta)) {
            $sql = "INSERT INTO documentos (doc_fecha, doc_nombre, doc_ruta, doc_tabla, doc_idviene, doc_version)
                    VALUES (NOW(), ?, ?, 'pre-operacional', ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssii", $file['name'], $ruta, $idPreoperacional, $version);
            return $stmt->execute();
        }
        return false;
    }

    public function actualizarImagenKilo($idPreoperacional, $rutaImagen)
    {
        $sql = "UPDATE `pre-operacional` SET pre_img_kilo = ? 
                WHERE idpreoperacinal = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $rutaImagen, $idPreoperacional);
        return $stmt->execute();
    }
}