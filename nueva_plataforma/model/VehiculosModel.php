<?php
require_once "../config/database.php";

class VehiculosModel {
    private $dbname;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function obtenerVehiculos($filtroTipovehiculo = '', $filtroEstado = '') {
        $sql = "SELECT `idvehiculos`, `veh_tipo`, `veh_placa`, `veh_marca`, `veh_modelo`, 
        `veh_fechaseguro`, `veh_fechategnomecanica`, `veh_fechamantenimiento`, `veh_kilactual`, 
        `veh_aceitekil`, `veh_dueño`, `veh_estado`, `veh_chasis`, `veh_tipov`, `veh_cilidraje`, 
        `veh_motor`, `veh_color`, `veh_usuve`, `veh_observaciones`, `veh_calkmcambioaceite`, 
        `veh_restankmaceite`, `veh_faltaparacambioaceite`, `veh_kmalcambaceite`,

        usu_nombre
        FROM `vehiculos` INNER JOIN usuarios ON veh_dueño = idusuarios";

        


        // if ($filtroTipovehiculo !== '') {
        //     $sql .= " AND tipodevehiculo = '" . $this->db->real_escape_string($filtroTipovehiculo) . "'";
        // }

        // if ($filtroEstado !== '') {
        //     $sql .= " AND estado = '" . $this->db->real_escape_string($filtroEstado) . "'";
        // }

        // $sql .= " ORDER BY marca ASC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $sql .= " AND idroles = '" . $this->db->real_escape_string($filtroRol) . "'";
    }
    
    public function actualizarCampo($id, $campo, $valor) {
        // Solo permitir actualizar el campo 'estado'
        $permitidos = ['estado'];
        if (!in_array($campo, $permitidos)) return;

        $sql = "UPDATE usuarios SET $campo = ? WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $valor, $id);
        $stmt->execute();
        $stmt->close();
    }
    public function eliminarVehiculo($id) {
        $sql = "DELETE FROM vehiculos WHERE idvehiculos = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    
    public function guardarVehiculo($datos) {
    $sql = "INSERT INTO vehiculos (
        veh_tipo, 
        veh_marca, 
        veh_placa, 
        veh_modelo, 
        veh_color,
        veh_tipov, 
        veh_dueño, 
        veh_fechaseguro, 
        veh_foto_soat, 
        veh_fechategnomecanica, 
        veh_foto_tecnomecanica, 
        veh_observaciones, 
        veh_img_anverso, 
        veh_img_reverso,
        veh_estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo')";

    $stmt = $this->db->prepare($sql);
    
    // "sssssisssssss" indica el tipo de dato: s = string, i = entero
    $stmt->bind_param(
        "ssssssisssssss", 
        $datos['veh_tipo'], 
        $datos['veh_marca'], 
        $datos['veh_placa'], 
        $datos['veh_modelo'], 
        $datos['veh_color'], 
        $datos['veh_tipov'],
        $datos['veh_dueño'], 
        $datos['veh_fechaseguro'], 
        $datos['veh_foto_seguro'], 
        $datos['veh_fechategnomecanica'], 
        $datos['veh_foto_tecnomecanica'], 
        $datos['veh_observaciones'], 
        $datos['veh_img_anverso'], 
        $datos['veh_img_reverso']
    );

    $resultado = $stmt->execute();
    $stmt->close();
    
    return $resultado;
}
    
}
