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
        `veh_aceitekil`, `veh_propiedad`, `veh_dueño`, `veh_estado`, `veh_chasis`, `veh_tipov`, `veh_cilidraje`, 
        `veh_motor`, `veh_color`, `veh_usuve`, `veh_observaciones`, `veh_calkmcambioaceite`, 
        `veh_restankmaceite`, `veh_faltaparacambioaceite`, `veh_kmalcambaceite`, `veh_img_anverso`, `veh_img_reverso`,

        usu_nombre
        FROM `vehiculos` INNER JOIN usuarios ON veh_dueño = idusuarios";

        
    if ($filtroTipovehiculo !== '') {
        $sql .= " AND veh_tipo = '" . $this->db->real_escape_string($filtroTipovehiculo) . "'";
    }

    if ($filtroEstado !== '') {
        $sql .= " AND veh_estado = '" . $this->db->real_escape_string($filtroEstado) . "'";
    }

    $result = $this->db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
    $fechas = ['veh_fechaseguro', 'veh_fechategnomecanica', 'veh_fechamantenimiento'];
    foreach ($fechas as $f) {
        if (empty($datos[$f])) $datos[$f] = null;
    }

    $veh_img_anverso       = $this->guardarImagen($_FILES['veh_img_anverso'],       "uploads/vehiculos");
    $veh_img_reverso       = $this->guardarImagen($_FILES['veh_img_reverso'],       "uploads/vehiculos");
    $veh_img_soat          = $this->guardarImagen($_FILES['veh_img_soat'],          "uploads/vehiculos");
    $veh_img_tecnomecanica = $this->guardarImagen($_FILES['veh_img_tecnomecanica'], "uploads/vehiculos");

    $sql = "INSERT INTO vehiculos (
        veh_tipo, veh_marca, veh_placa, veh_modelo, veh_color,
        veh_tipov, veh_propiedad, veh_dueño, veh_fechaseguro, veh_img_soat,
        veh_fechategnomecanica, veh_img_tecnomecanica, veh_fechamantenimiento,
        veh_kilactual, veh_calkmcambioaceite, veh_chasis, veh_motor,
        veh_cilidraje, veh_usuve, veh_estado, veh_equipo_carretera, veh_observaciones,
        veh_img_anverso, veh_img_reverso
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);

    if (!$stmt) {
        return ['error' => 'Prepare falló: ' . $this->db->error];
    }

    $stmt->bind_param(
        "sssssssissssssssssssssss", 
        $datos['veh_tipo'],
        $datos['veh_marca'],
        $datos['veh_placa'],
        $datos['veh_modelo'],
        $datos['veh_color'],
        $datos['veh_tipov'],
        $datos['veh_propiedad'],
        $datos['veh_dueno'],
        $datos['veh_fechaseguro'],
        $veh_img_soat,
        $datos['veh_fechategnomecanica'],
        $veh_img_tecnomecanica,
        $datos['veh_fechamantenimiento'],
        $datos['veh_kilactual'],
        $datos['veh_calkmcambioaceite'],
        $datos['veh_chasis'],
        $datos['veh_motor'],
        $datos['veh_cilidraje'],
        $datos['veh_usuve'],
        $datos['veh_estado'],
        $datos['veh_equipo_carretera'],
        $datos['veh_observaciones'],
        $veh_img_anverso,
        $veh_img_reverso
    );

    $resultado = $stmt->execute();

    if (!$resultado) {
        //TAMBIÉN VER ESTE ERROR
        return ['error' => 'Execute falló: ' . $stmt->error];
    }

    $stmt->close();
    return true;
}

private function guardarImagen(array $file, string $carpetaRelativa)
{
    // Validar que exista archivo
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return "";
    }

    // Ruta ABSOLUTA en el servidor
    $rutaBase = $_SERVER['DOCUMENT_ROOT'] . "/SistemaTransmillas2025/nueva_plataforma/" . $carpetaRelativa;
    // Crear carpeta si no existe (CORRECTO)
    if (!is_dir($rutaBase)) {
        mkdir($rutaBase, 0777, true);
    }

    // Nombre único
    $nombre = date("Y-m-d-H-i-s") . "-" . basename($file["name"]);

    // Ruta donde se guarda físicamente
    $destino = $rutaBase . "/" . $nombre;

    // Validar que sea imagen
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

    // Guardar imagen
    imagejpeg($imagen, $destino, 70);
    imagedestroy($imagen);

    //Ruta que usará el navegador
    return "uploads/vehiculos/" . $nombre;
}    

// Obtener lista de dueños activos para el dropdown
public function obtenerDueños() {
    $sql = "SELECT idusuarios AS iddueños, usu_nombre AS due_nombre 
            FROM usuarios 
            WHERE usu_estado = 1
            ORDER BY usu_nombre ASC";
    $result = $this->db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

public function obtenerVehiculoPorId($id) {
    $id = intval($id);
    $sql = "SELECT * FROM vehiculos WHERE idvehiculos = $id LIMIT 1";
    $result = $this->db->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

public function actualizarVehiculo($datos) {
    $veh_img_soat          = $this->guardarImagen($_FILES['veh_img_soat'],          "uploads/vehiculos");
    $veh_img_tecnomecanica = $this->guardarImagen($_FILES['veh_img_tecnomecanica'], "uploads/vehiculos");
    $veh_img_anverso       = $this->guardarImagen($_FILES['veh_img_anverso'],       "uploads/vehiculos");
    $veh_img_reverso       = $this->guardarImagen($_FILES['veh_img_reverso'],       "uploads/vehiculos");

    // Solo reemplaza imagen si subieron una nueva
    $sqlImgs = "";
    if ($veh_img_soat)          $sqlImgs .= ", veh_img_soat = '$veh_img_soat'";
    if ($veh_img_tecnomecanica) $sqlImgs .= ", veh_img_tecnomecanica = '$veh_img_tecnomecanica'";
    if ($veh_img_anverso)       $sqlImgs .= ", veh_img_anverso = '$veh_img_anverso'";
    if ($veh_img_reverso)       $sqlImgs .= ", veh_img_reverso = '$veh_img_reverso'";

    $id = intval($datos['veh_id']);

    $sql = "UPDATE vehiculos SET
        veh_tipo               = ?,
        veh_marca              = ?,
        veh_placa              = ?,
        veh_modelo             = ?,
        veh_color              = ?,
        veh_tipov              = ?,
        veh_propiedad          = ?,
        veh_dueño              = ?,
        veh_fechaseguro        = ?,
        veh_fechategnomecanica = ?,
        veh_fechamantenimiento = ?,
        veh_kilactual          = ?,
        veh_calkmcambioaceite  = ?,
        veh_chasis             = ?,
        veh_motor              = ?,
        veh_cilidraje          = ?,
        veh_usuve              = ?,
        veh_estado             = ?,
        veh_equipo_carretera   = ?,
        veh_observaciones      = ?
        $sqlImgs
        WHERE idvehiculos = $id";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssssss",
        $datos['veh_tipo'],
        $datos['veh_marca'],
        $datos['veh_placa'],
        $datos['veh_modelo'],
        $datos['veh_color'],
        $datos['veh_tipov'],
        $datos['veh_propiedad'],
        $datos['veh_dueno'],
        $datos['veh_fechaseguro'],
        $datos['veh_fechategnomecanica'],
        $datos['veh_fechamantenimiento'],
        $datos['veh_kilactual'],
        $datos['veh_calkmcambioaceite'],
        $datos['veh_chasis'],
        $datos['veh_motor'],
        $datos['veh_cilidraje'],
        $datos['veh_usuve'],
        $datos['veh_estado'],
        $datos['veh_equipo_carretera'],
        $datos['veh_observaciones']
    );

    $resultado = $stmt->execute();
    $stmt->close();
    return $resultado;
}

/*
public function obtenerUsuariosActivos() {
    $sql = "SELECT idusuarios, usu_nombre 
            FROM usuarios 
            WHERE usu_estado = 1
            ORDER BY usu_nombre ASC";
    $result = $this->db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
*/
/*
public function obtenerConductoresActivos() {
    $sql = "SELECT idhojadevida, 
                   CONCAT(hoj_nombre, ' ', hoj_apellido) AS conductor_nombre
            FROM hojadevida 
            WHERE hoj_estado = 'Activo'
            ORDER BY hoj_nombre ASC";
    $result = $this->db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
    */
public function obtenerOperadoresActivos() {
    $sql = "SELECT idusuarios, usu_nombre 
            FROM usuarios 
            WHERE usu_estado = 1 
              AND roles_idroles IN (2, 3)
            ORDER BY usu_nombre ASC";
    $result = $this->db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

public function guardarEntregaVehiculo($datos) {

    //Obtener la cédula del usuario (operador que entrega)
    $idUsuario = intval($datos['ent_idusuario']);
    $sqlCedula = "SELECT usu_identificacion FROM usuarios WHERE idusuarios = ? LIMIT 1";
    $stmtCedula = $this->db->prepare($sqlCedula);
    $stmtCedula->bind_param("i", $idUsuario);
    $stmtCedula->execute();
    $resultCedula = $stmtCedula->get_result();
    $usuarioData = $resultCedula->fetch_assoc();
    $stmtCedula->close();

    //Buscar en hojadevida por cédula y que esté Activo
    $idHojaDeVida = null;

    if ($usuarioData && !empty($usuarioData['usu_identificacion'])) {
        $cedula = $usuarioData['usu_identificacion'];

        $sqlHoja = "SELECT idhojadevida FROM hojadevida 
                    WHERE hoj_cedula = ? AND hoj_estado = 'Activo' LIMIT 1";
        $stmtHoja = $this->db->prepare($sqlHoja);
        $stmtHoja->bind_param("s", $cedula);
        $stmtHoja->execute();
        $resultHoja = $stmtHoja->get_result();
        $hojaData = $resultHoja->fetch_assoc();
        $stmtHoja->close();

        if ($hojaData) {
            $idHojaDeVida = $hojaData['idhojadevida'];
        }
    }

    //Guardar la entrega incluyendo ent_idhojadevida
    $sql = "INSERT INTO entregavehiculo (
                ent_fechaentrega,
                ent_vehiculo,
                ent_userregistra,
                ent_idusuario,
                ent_tipoentrega,
                ent_fecharegistra,
                ent_idhojadevida
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);

    if (!$stmt) {
        return ['error' => 'Prepare falló: ' . $this->db->error];
    }

    $stmt->bind_param(
        "sssissi",
        $datos['ent_fechaentrega'],
        $datos['ent_vehiculo'],
        $datos['ent_userregistra'],
        $datos['ent_idusuario'],
        $datos['ent_tipoentrega'],
        $datos['ent_fecharegistra'],
        $idHojaDeVida
    );

    $resultado = $stmt->execute();

    if (!$resultado) {
        return ['error' => 'Execute falló: ' . $stmt->error];
    }

    $stmt->close();
    return true;
}
}
