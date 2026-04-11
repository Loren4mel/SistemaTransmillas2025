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
    $veh_img_anverso       = $this->guardarImagen($_FILES['veh_img_anverso'],       "uploads/vehiculos");
    $veh_img_reverso       = $this->guardarImagen($_FILES['veh_img_reverso'],       "uploads/vehiculos");
    $veh_img_soat          = $this->guardarImagen($_FILES['veh_img_soat'],          "uploads/vehiculos");
    $veh_img_tecnomecanica = $this->guardarImagen($_FILES['veh_img_tecnomecanica'], "uploads/vehiculos");

    $sql = "INSERT INTO vehiculos (
        veh_tipo, veh_marca, veh_placa, veh_modelo, veh_color,
        veh_tipov, veh_dueño, veh_fechaseguro, veh_img_soat,
        veh_fechategnomecanica, veh_img_tecnomecanica, veh_fechamantenimiento,
        veh_kilactual, veh_calkmcambioaceite, veh_chasis, veh_motor,
        veh_cilidraje, veh_usuve, veh_estado, veh_observaciones,
        veh_img_anverso, veh_img_reverso
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);

    // ✅ 22 valores = 22 tipos: 6s + i + 15s
    $stmt->bind_param(
        "ssssssississssssssssss",
        $datos['veh_tipo'],
        $datos['veh_marca'],
        $datos['veh_placa'],
        $datos['veh_modelo'],
        $datos['veh_color'],
        $datos['veh_tipov'],
        $datos['veh_dueno'],        // ✅ sin tilde, coincide con name del select
        $datos['veh_fecha_soat'],   
        $veh_img_soat,
        $datos['veh_fechategnomecanica'], 
        $veh_img_tecnomecanica,
        $datos['veh_fecha_aceite'], // ✅ nombre corregido
        $datos['veh_kilactual'],
        $datos['veh_calkmcambioaceite'],
        $datos['veh_chasis'],
        $datos['veh_motor'],
        $datos['veh_cilidraje'],
        $datos['veh_usuve'],
        $datos['veh_estado'],
        $datos['veh_especificaciones'],
        $veh_img_anverso,
        $veh_img_reverso
    );

    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
}

private function guardarImagen(array $file, string $carpetaRelativa)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return "";
        }

        $raiz = $_SERVER['DOCUMENT_ROOT'] . "/SistemaTransmillas2025/nueva_plataforma/";
        $carpetaAbsoluta = dirname(__DIR__) . "/" . $carpetaRelativa;

        if (!is_dir($carpetaRelativa)) {
            @mkdir($carpetaRelativa, 0777, true);
        }

        $nombre = date("Y-m-d-H-i-s") . "-" . basename($file["name"]);
        $destino = rtrim($carpetaRelativa, "/") . "/" . $nombre;

        $info = getimagesize($file['tmp_name']);
        if (!$info) return "";

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $imagen = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imagen = imagecreatefrompng($file['tmp_name']);
                break;
            default:
                return "";
        }

        // Redimensionar si es muy grande
        $maxW = 1280;
        $maxH = 1280;
        $w = imagesx($imagen);
        $h = imagesy($imagen);

        if ($w > $maxW || $h > $maxH) {
            $ratio = min($maxW / $w, $maxH / $h);
            $nw = (int)($w * $ratio);
            $nh = (int)($h * $ratio);

            $tmp = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($tmp, $imagen, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $imagen = $tmp;
        }

        imagejpeg($imagen, $destino, 70);
        imagedestroy($imagen);

        return "uploads/vehiculos/" . $nombre;
    }
    
public function actualizarVehiculo($datos) {
    // Aquí haces el UPDATE usando el ID
    $id = $datos['id'];
    $sql = "UPDATE vehiculos SET 
            veh_tipo = '{$datos['tipo']}', 
            veh_marca = '{$datos['marca']}', 
            veh_placa = '{$datos['placa']}', 
            veh_modelo = '{$datos['modelo']}', 
            iddueños = '{$datos['iddueños']}', 
            veh_estado = '{$datos['estado']}' 
            WHERE idvehiculos = '$id'";
    
    return $this->db->query($sql); // O el método que uses para ejecutar SQL
}

public function obtenerDueños() {
    $sql = "SELECT idusuarios AS iddueños, usu_nombre AS due_nombre 
            FROM usuarios 
            ORDER BY usu_nombre ASC";
    $result = $this->db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

}
