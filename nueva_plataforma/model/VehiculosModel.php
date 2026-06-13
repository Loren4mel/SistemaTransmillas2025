<?php
require_once "../config/database.php";

class VehiculosModel {
    private $dbname;

    public function __construct() {
        $this->dbname = (new Database())->connect();
    }

    public function obtenerVehiculos($filtroTipovehiculo = '', $filtroEstado = '', $filtroPropiedad = '') {
    $sql = "SELECT `idvehiculos`, `veh_tipo`, `veh_placa`, `veh_marca`, `veh_modelo`,
    `veh_fechaseguro`, `veh_fechategnomecanica`, `veh_fechamantenimiento`, `veh_kilactual`,
    `veh_kmactual_cambioaceite`, `veh_aceitekil`, `veh_propiedad`, `veh_dueño`, `veh_estado`, 
    `veh_chasis`, `veh_tipov`, `veh_cilidraje`, `veh_motor`, `veh_color`, `veh_usuve`, 
    `veh_observaciones`, `veh_calkmcambioaceite`, `veh_restankmaceite`, 
    `veh_faltaparacambioaceite`, `veh_kmalcambaceite`, 
    `veh_img_anverso`, `veh_img_reverso`, `veh_img_actual_frente`, `veh_img_actual_trasera`,
    `veh_img_soat`, `veh_img_tecnomecanica`,
     IF(idusuarios = 14, 'Transmillas', usu_nombre) AS usu_nombre,
    (SELECT COUNT(*) FROM comparendos 
     WHERE com_vehiculo_id = idvehiculos) AS total_comparendos,
    (SELECT COUNT(*) FROM comparendos 
     WHERE com_vehiculo_id = idvehiculos 
     AND com_estado = 'Pendiente') AS comparendos_pendientes,
     (SELECT rev_fecha_consulta FROM revision_comparendos 
     WHERE rev_vehiculo_id = idvehiculos 
     ORDER BY rev_fecha_consulta DESC LIMIT 1) AS ultima_revision
    FROM `vehiculos` LEFT JOIN usuarios ON veh_dueño = idusuarios
     WHERE 1=1";
    if ($filtroTipovehiculo !== '') {
        $sql .= " AND veh_tipo = '" . $this->dbname->real_escape_string($filtroTipovehiculo) . "'";
    }

    if ($filtroEstado !== '') {
        $sql .= " AND veh_estado = '" . $this->dbname->real_escape_string($filtroEstado) . "'";
    }

     if ($filtroPropiedad !== '') {
        $sql .= " AND veh_propiedad = '" . $this->dbname->real_escape_string($filtroPropiedad) . "'";
    }

    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
    
//Funcion para actualizar campos específicos (como estado) sin afectar otros datos del vehículo
    public function actualizarCampo($id, $campo, $valor) {
    $permitidos = ['veh_estado'];
    if (!in_array($campo, $permitidos)) return;

    $sql = "UPDATE vehiculos SET $campo = ? WHERE idvehiculos = ?";
    $stmt = $this->dbname->prepare($sql);
    $stmt->bind_param("ii", $valor, $id);
    $stmt->execute();
    $stmt->close();
}

// Función para eliminar un vehículo por su ID
    public function eliminarVehiculo($id) {
        $sql = "DELETE FROM vehiculos WHERE idvehiculos = ?";
        $stmt = $this->dbname->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

//Funcion para guardar vehiculo nuevo con validación de fechas y manejo de imágenes
    public function guardarVehiculo($datos) {
    $fechas = ['veh_fechaseguro', 'veh_fechategnomecanica', 'veh_fechamantenimiento'];
    foreach ($fechas as $f) {
        if (empty($datos[$f])) $datos[$f] = null;
    }

    $veh_img_anverso        = $this->guardarImagen($_FILES['veh_img_anverso'],        "uploads/vehiculos");
    $veh_img_reverso        = $this->guardarImagen($_FILES['veh_img_reverso'],        "uploads/vehiculos");
    $veh_img_soat           = $this->guardarImagen($_FILES['veh_img_soat'],           "uploads/vehiculos");
    $veh_img_tecnomecanica  = $this->guardarImagen($_FILES['veh_img_tecnomecanica'],  "uploads/vehiculos");
    $veh_img_actual_frente  = $this->guardarImagen($_FILES['veh_img_actual_frente'],  "uploads/vehiculos");
    $veh_img_actual_trasera = $this->guardarImagen($_FILES['veh_img_actual_trasera'], "uploads/vehiculos");

// Procesar fotos de herramientas
$equipoJson = $datos['veh_equipo_carretera'] ?? '[]';
$equipo = json_decode($equipoJson, true) ?? [];

foreach ($equipo as $idx => &$herramienta) {
    unset($herramienta['foto_key']);
    $key = "veh_herramienta_foto_{$idx}";
    if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
        $rutaFoto = $this->guardarImagen($_FILES[$key], 'uploads/vehiculos/herramientas');
        if ($rutaFoto) $herramienta['foto'] = $rutaFoto;
    }
}
unset($herramienta);
$datos['veh_equipo_carretera'] = json_encode($equipo, JSON_UNESCAPED_UNICODE);





    $sql = "INSERT INTO vehiculos (
        veh_tipo, veh_marca, veh_placa, veh_modelo, veh_color,
        veh_tipov, veh_propiedad, veh_dueño, veh_fechaseguro, veh_img_soat,
        veh_fechategnomecanica, veh_img_tecnomecanica, veh_fechamantenimiento,
        veh_kilactual, veh_kmactual_cambioaceite, veh_calkmcambioaceite,
        veh_chasis, veh_motor, veh_cilidraje, veh_usuve, veh_estado,
        veh_equipo_carretera, veh_observaciones,
        veh_img_anverso, veh_img_reverso, veh_img_actual_frente, veh_img_actual_trasera
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->dbname->prepare($sql);

    if (!$stmt) {
        return ['error' => 'Prepare falló: ' . $this->dbname->error];
    }

    $stmt->bind_param(
        "sssssssisssssssssssssssssss",
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
        $datos['veh_kmactual_cambioaceite'], 
        $datos['veh_calkmcambioaceite'],
        $datos['veh_chasis'],
        $datos['veh_motor'],
        $datos['veh_cilidraje'],
        $datos['veh_usuve'],
        $datos['veh_estado'],
        $datos['veh_equipo_carretera'],
        $datos['veh_observaciones'],
        $veh_img_anverso,
        $veh_img_reverso,
        $veh_img_actual_frente,
        $veh_img_actual_trasera
    );

    $resultado = $stmt->execute();

    if (!$resultado) {
        return ['error' => 'Execute falló: ' . $stmt->error];
    }

    $stmt->close();
    return true;
}

//Funcion para guardar imagenes de entrega de vehículo con validación de usuario y hoja de vida
private function guardarImagen(array $file, string $carpetaRelativa)
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return "";
    }

    $rutaBase = dirname(__DIR__) . DIRECTORY_SEPARATOR . $carpetaRelativa;
    if (!is_dir($rutaBase)) {
        mkdir($rutaBase, 0777, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];

    if (!in_array($extension, $extensionesPermitidas)) {
        return "";
    }

    $nombre  = date("Y-m-d-H-i-s") . "-" . uniqid() . "." . $extension;
    $destino = $rutaBase . DIRECTORY_SEPARATOR . $nombre;

    // Si es PDF, mover directamente sin procesar
    if ($extension === 'pdf') {
        if (move_uploaded_file($file['tmp_name'], $destino)) {
            return $carpetaRelativa . "/" . $nombre;
        }
        return "";
    }

    // Si es imagen, redimensionar como antes
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

    $maxW = 1280;
    $maxH = 1280;
    $w    = imagesx($imagen);
    $h    = imagesy($imagen);

    if ($w > $maxW || $h > $maxH) {
        $ratio = min($maxW / $w, $maxH / $h);
        $nw    = (int)($w * $ratio);
        $nh    = (int)($h * $ratio);
        $tmp   = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($tmp, $imagen, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $imagen = $tmp;
    }

    imagejpeg($imagen, $destino, 70);
    imagedestroy($imagen);

    return $carpetaRelativa . "/" . $nombre;
}
// Funcion para obtener lista de dueños activos para el dropdown
public function obtenerDueños() {
    $sql = "SELECT idusuarios AS iddueños, usu_nombre AS due_nombre 
            FROM usuarios 
            WHERE usu_estado = 1
              AND (roles_idroles IN (2, 3) OR idusuarios = 14)
            ORDER BY usu_nombre ASC";
    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Funcion para obtener un vehículo por su ID (para modal editar)
public function obtenerVehiculoPorId($id) {
    $id    = intval($id);
    $sql   = "SELECT * FROM vehiculos WHERE idvehiculos = $id LIMIT 1";
    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

//Funcion para actualizar vehículo con validación de fechas y manejo de imágenes
public function actualizarVehiculo($datos) {
    $veh_img_anverso        = $this->guardarImagen($_FILES['veh_img_anverso'],        "uploads/vehiculos");
    $veh_img_reverso        = $this->guardarImagen($_FILES['veh_img_reverso'],        "uploads/vehiculos");
    $veh_img_actual_frente  = $this->guardarImagen($_FILES['veh_img_actual_frente'],  "uploads/vehiculos");
    $veh_img_actual_trasera = $this->guardarImagen($_FILES['veh_img_actual_trasera'], "uploads/vehiculos");

// Procesar fotos de herramientas
$equipoJson = $datos['veh_equipo_carretera'] ?? '[]';
$equipo = json_decode($equipoJson, true) ?? [];

foreach ($equipo as $idx => &$herramienta) {
    $key = "veh_herramienta_foto_edit_{$idx}";
    if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
        $rutaFoto = $this->guardarImagen($_FILES[$key], 'uploads/vehiculos/herramientas');
        if ($rutaFoto) $herramienta['foto'] = $rutaFoto;
    }
    unset($herramienta['foto_key']);
}
unset($herramienta);
$datos['veh_equipo_carretera'] = json_encode($equipo, JSON_UNESCAPED_UNICODE);

    $sqlImgs = "";
    if ($veh_img_anverso)        $sqlImgs .= ", veh_img_anverso = '$veh_img_anverso'";
    if ($veh_img_reverso)        $sqlImgs .= ", veh_img_reverso = '$veh_img_reverso'";
    if ($veh_img_actual_frente)  $sqlImgs .= ", veh_img_actual_frente = '$veh_img_actual_frente'";
    if ($veh_img_actual_trasera) $sqlImgs .= ", veh_img_actual_trasera = '$veh_img_actual_trasera'";

    $id = intval($datos['veh_id']);

    $sql = "UPDATE vehiculos SET
        veh_tipo                  = ?,
        veh_marca                 = ?,
        veh_placa                 = ?,
        veh_modelo                = ?,
        veh_color                 = ?,
        veh_tipov                 = ?,
        veh_propiedad             = ?,
        veh_dueño                 = ?,
        veh_chasis                = ?,
        veh_motor                 = ?,
        veh_cilidraje             = ?,
        veh_usuve                 = ?,
        veh_estado                = ?,
        veh_equipo_carretera      = ?,
        veh_observaciones         = ?
        $sqlImgs
        WHERE idvehiculos = $id";

    $stmt = $this->dbname->prepare($sql);

    $stmt->bind_param(
        "sssssssssssssss",
        $datos['veh_tipo'],
        $datos['veh_marca'],
        $datos['veh_placa'],
        $datos['veh_modelo'],
        $datos['veh_color'],
        $datos['veh_tipov'],
        $datos['veh_propiedad'],
        $datos['veh_dueno'],
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

//Funcion para obtener operadores activos para el dropdown de entrega de vehículo
public function obtenerOperadoresActivos() {
    $sql = "SELECT idusuarios, usu_nombre 
            FROM usuarios 
            WHERE usu_estado = 1 
              AND roles_idroles IN (2, 3)
            ORDER BY usu_nombre ASC";
    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

//Funcion para guardar entrega de vehículo con validación de usuario, hoja de vida y manejo de imágenes
public function guardarEntregaVehiculo($datos) {

    $ent_img_frente  = $this->guardarImagen($_FILES['ent_img_frente'],  "uploads/vehiculos");
    $ent_img_trasera = $this->guardarImagen($_FILES['ent_img_trasera'], "uploads/vehiculos");

// Procesar fotos individuales de herramientas
$equipoJson = $datos['ent_equipo_carretera'] ?? '[]';
$equipo = json_decode($equipoJson, true) ?? [];

foreach ($equipo as $idx => &$herramienta) {
    // Limpiar foto_key — no se guarda en BD
    unset($herramienta['foto_key']);
    
    $key = "ent_herramienta_foto_{$idx}";
    if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
        $rutaFoto = $this->guardarImagen($_FILES[$key], 'uploads/vehiculos/herramientas');
        if ($rutaFoto) {
            $herramienta['foto'] = $rutaFoto;
        }
    }
}
unset($herramienta);

$datos['ent_equipo_carretera'] = json_encode($equipo, JSON_UNESCAPED_UNICODE);

    $idUsuario = intval($datos['ent_idusuario']);
    $sqlCedula = "SELECT usu_identificacion FROM usuarios WHERE idusuarios = ? LIMIT 1";
    $stmtCedula = $this->dbname->prepare($sqlCedula);
    $stmtCedula->bind_param("i", $idUsuario);
    $stmtCedula->execute();
    $resultCedula = $stmtCedula->get_result();
    $usuarioData  = $resultCedula->fetch_assoc();
    $stmtCedula->close();

    $idHojaDeVida = null;

    if ($usuarioData && !empty($usuarioData['usu_identificacion'])) {
        $cedula   = $usuarioData['usu_identificacion'];
        $sqlHoja  = "SELECT idhojadevida FROM hojadevida 
                     WHERE hoj_cedula = ? AND hoj_estado = 'Activo' LIMIT 1";
        $stmtHoja = $this->dbname->prepare($sqlHoja);
        $stmtHoja->bind_param("s", $cedula);
        $stmtHoja->execute();
        $resultHoja = $stmtHoja->get_result();
        $hojaData   = $resultHoja->fetch_assoc();
        $stmtHoja->close();
        if ($hojaData) $idHojaDeVida = $hojaData['idhojadevida'];
    }

    $ent_firma_path = '';
    if (!empty($datos['ent_firma_base64'])) {
        $firmaData    = $datos['ent_firma_base64'];
        $firmaData    = str_replace('data:image/png;base64,', '', $firmaData);
        $firmaData    = str_replace(' ', '+', $firmaData);
        $firmaDecoded = base64_decode($firmaData);
        $rutaFirmas = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'firmas_entrega' . DIRECTORY_SEPARATOR;
        if (!is_dir($rutaFirmas)) mkdir($rutaFirmas, 0777, true);
        $nombreFirma    = 'firma_entrega_' . date('Y-m-d-H-i-s') . '_' . uniqid() . '.png';
        file_put_contents($rutaFirmas . $nombreFirma, $firmaDecoded);
        $ent_firma_path = 'uploads/firmas_entrega/' . $nombreFirma;
    }

    $ent_video_url = $this->dbname->real_escape_string($datos['ent_video_url'] ?? '');

    $sql = "INSERT INTO entregavehiculo (
            ent_fechaentrega, ent_vehiculo, ent_userregistra, ent_idusuario,
            ent_tipoentrega, ent_fecharegistra, ent_idhojadevida, ent_sede,
            ent_img_frente, ent_img_trasera, ent_equipo_carretera,
            ent_observaciones, ent_firma, ent_video_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->dbname->prepare($sql);
    if (!$stmt) return ['error' => 'Prepare falló: ' . $this->dbname->error];

    $stmt->bind_param(
        "sssississsssss",
        $datos['ent_fechaentrega'],
        $datos['ent_vehiculo'],
        $datos['ent_userregistra'],
        $datos['ent_idusuario'],
        $datos['ent_tipoentrega'],
        $datos['ent_fecharegistra'],
        $idHojaDeVida,
        $datos['ent_sede'],
        $ent_img_frente,
        $ent_img_trasera,
        $datos['ent_equipo_carretera'],
        $datos['ent_observaciones'],
        $ent_firma_path,
        $ent_video_url
    );

    $resultado = $stmt->execute();
    if (!$resultado) return ['error' => 'Execute falló: ' . $stmt->error];
    $stmt->close();

    if ($datos['ent_tipoentrega'] === 'inicial' && !empty($datos['ent_vehiculo_id']) && !empty($datos['ent_idusuario'])) {
    $idVehiculo  = intval($datos['ent_vehiculo_id']);
    $idConductor = intval($datos['ent_idusuario']);

    // Obtener tipo de vehículo para guardarlo también en el usuario
    $sqlTipo  = "SELECT veh_tipo FROM vehiculos WHERE idvehiculos = ? LIMIT 1";
    $stmtTipo = $this->dbname->prepare($sqlTipo);
    $stmtTipo->bind_param("i", $idVehiculo);
    $stmtTipo->execute();
    $rowTipo = $stmtTipo->get_result()->fetch_assoc();
    $stmtTipo->close();

    $tipoVehiculo = $rowTipo['veh_tipo'] ?? '';

    $sqlUpd  = "UPDATE usuarios SET 
                    usu_vehiculo     = ?,
                    usu_tipoVehiculo = ?
                WHERE idusuarios = ?";
    $stmtUpd = $this->dbname->prepare($sqlUpd);
    $stmtUpd->bind_param("isi", $idVehiculo, $tipoVehiculo, $idConductor);
    $stmtUpd->execute();
    $stmtUpd->close();
}

    if (!empty($datos['ent_vehiculo_id']) && !empty($datos['ent_equipo_carretera'])) {
        $idVehiculo     = intval($datos['ent_vehiculo_id']);
        $equipoEscapado = $this->dbname->real_escape_string($datos['ent_equipo_carretera']);

        $sqlEquipo = "UPDATE vehiculos SET veh_equipo_carretera = '$equipoEscapado' 
                      WHERE idvehiculos = $idVehiculo";
        $this->dbname->query($sqlEquipo);
    }

    return true;
}

//Funcion para obtener equipo de carretera de un vehículo por su ID (para mostrar en modal entrega)
public function obtenerEquipoVehiculo($id) {
    $id     = intval($id);
    $sql    = "SELECT veh_equipo_carretera FROM vehiculos WHERE idvehiculos = $id LIMIT 1";
    $result = $this->dbname->query($sql);
    if ($result) {
        $row    = $result->fetch_assoc();
        $equipo = [];
        if (!empty($row['veh_equipo_carretera'])) {
            $equipo = json_decode($row['veh_equipo_carretera'], true) ?? [];
        }
        return $equipo;
    }
    return [];
}

//Funcion para guardar comparendo con validación de usuario, hoja de vida y manejo de imágenes
public function guardarComparendo($datos) {
    $com_foto = '';
    if (isset($_FILES['com_foto']) && is_uploaded_file($_FILES['com_foto']['tmp_name'])) {
        $com_foto = $this->guardarImagen($_FILES['com_foto'], 'uploads/comparendos');
    }

    $com_foto_curso = '';
    if (isset($_FILES['com_foto_curso']) && is_uploaded_file($_FILES['com_foto_curso']['tmp_name'])) {
        $com_foto_curso = $this->guardarImagen($_FILES['com_foto_curso'], 'uploads/comparendos');
    }

    $idHoja = null;
    $idOp   = intval($datos['com_operador_id']);

    $sqlC  = "SELECT usu_identificacion FROM usuarios WHERE idusuarios = ? LIMIT 1";
    $stmtC = $this->dbname->prepare($sqlC);
    $stmtC->bind_param("i", $idOp);
    $stmtC->execute();
    $rowC = $stmtC->get_result()->fetch_assoc();
    $stmtC->close();

    if ($rowC && !empty($rowC['usu_identificacion'])) {
        $cedula = $rowC['usu_identificacion'];
        $sqlH   = "SELECT idhojadevida FROM hojadevida
                   WHERE hoj_cedula = ? AND hoj_estado = 'Activo' LIMIT 1";
        $stmtH  = $this->dbname->prepare($sqlH);
        $stmtH->bind_param("s", $cedula);
        $stmtH->execute();
        $rowH = $stmtH->get_result()->fetch_assoc();
        $stmtH->close();
        if ($rowH) $idHoja = $rowH['idhojadevida'];
    }

    $datos['com_valor'] = str_replace(['.', ','], ['', '.'], $datos['com_valor']);

   $sql  = "INSERT INTO comparendos
             (com_operador_id, com_vehiculo_id, com_estado, com_foto, com_fecha,
             com_hojadevida_id, com_valor, com_numerocompa, com_titularcompa, com_foto_curso)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $this->dbname->prepare($sql);
    if (!$stmt) return ['error' => $this->dbname->error];

    $stmt->bind_param(
        "iisssissss",
        $datos['com_operador_id'],
        $datos['com_vehiculo_id'],
        $datos['com_estado'],
        $com_foto,
        $datos['com_fecha'],
        $idHoja,
        $datos['com_valor'],
        $datos['com_numerocompa'],
        $datos['com_titularcompa'],
        $com_foto_curso
    );

    $ok = $stmt->execute();
    if (!$ok) return ['error' => $stmt->error];
    $stmt->close();
    return true;
}

//Funcion para obtener comparendos de un vehículo por su ID (para mostrar en modal comparendos)
public function obtenerComparendosPorVehiculo($idVehiculo) {
    $id  = intval($idVehiculo);
    $sql = "SELECT c.*,
                   u.usu_nombre AS operador_nombre,
                   v.veh_placa  AS vehiculo_placa,
                   v.veh_marca  AS vehiculo_marca,
                   v.veh_modelo AS vehiculo_modelo
            FROM comparendos c
            LEFT JOIN usuarios  u ON c.com_operador_id = u.idusuarios
            LEFT JOIN vehiculos v ON c.com_vehiculo_id = v.idvehiculos
            WHERE c.com_vehiculo_id = $id
            ORDER BY c.com_fecha DESC";
    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

//Funcion para contar el número de comparendos de un operador por su ID (para mostrar en info hoja de vida)
public function contarComparendosPorOperador($idOperador) {
    $id     = intval($idOperador);
    $sql    = "SELECT COUNT(*) AS total FROM comparendos WHERE com_operador_id = $id";
    $result = $this->dbname->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }
    return 0;
}

//Funcion para obtener lista de sedes para el dropdown en entrega de vehículo (y posibles filtros futuros)
public function obtenerSedes() {
    $sql = "SELECT idsedes, sed_nombre FROM sedes ORDER BY sed_nombre ASC";
    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

//Funcion para actualizar comparendo con validación de usuario, hoja de vida y manejo de imágenes
public function actualizarComparendo($datos) {
    $com_foto = '';
    if (isset($_FILES['com_foto']) && is_uploaded_file($_FILES['com_foto']['tmp_name'])) {
        $com_foto = $this->guardarImagen($_FILES['com_foto'], 'uploads/comparendos');
    }

    $com_foto_curso = '';
    if (isset($_FILES['com_foto_curso']) && is_uploaded_file($_FILES['com_foto_curso']['tmp_name'])) {
        $com_foto_curso = $this->guardarImagen($_FILES['com_foto_curso'], 'uploads/comparendos');
    }

    $id      = intval($datos['com_id']);
    $estado  = $this->dbname->real_escape_string($datos['com_estado']);
    $valor = str_replace(['.', ','], ['', '.'], $datos['com_valor']);
    $valor = floatval($valor);
    $numero  = $this->dbname->real_escape_string($datos['com_numerocompa']);
    $titular = $this->dbname->real_escape_string($datos['com_titularcompa']);

    $sqlFotos = '';
    if ($com_foto)       $sqlFotos .= ", com_foto = '" . $this->dbname->real_escape_string($com_foto) . "'";
    if ($com_foto_curso) $sqlFotos .= ", com_foto_curso = '" . $this->dbname->real_escape_string($com_foto_curso) . "'";

    $sql = "UPDATE comparendos SET
                com_estado       = '$estado',
                com_valor        = '$valor',
                com_numerocompa  = '$numero',
                com_titularcompa = '$titular'
                {$sqlFotos}
            WHERE idcomparendos = $id";

    $resultado = $this->dbname->query($sql);
    if (!$resultado) return ['error' => $this->dbname->error];
    return true;
}

public function obtenerHistorialConductoresPorVehiculo($idVehiculo) {
    $sqlPlaca = "SELECT veh_placa FROM vehiculos WHERE idvehiculos = ? LIMIT 1";
    $stmtPlaca = $this->dbname->prepare($sqlPlaca);
    $stmtPlaca->bind_param("i", $idVehiculo);
    $stmtPlaca->execute();
    $rowPlaca = $stmtPlaca->get_result()->fetch_assoc();
    $stmtPlaca->close();

    if (!$rowPlaca) return [];
    $placa = $this->dbname->real_escape_string($rowPlaca['veh_placa']);

    $sql = "SELECT 
            e.identregavehiculo,
            e.ent_tipoentrega,
            e.ent_fechaentrega,
            e.ent_fecharegistra,        
            e.ent_userregistra,
            e.ent_sede, 
            e.ent_observaciones,
            e.ent_equipo_carretera,
            e.ent_img_frente,
            e.ent_img_trasera,
            e.ent_firma,
            e.ent_video_url,
            u.usu_nombre AS conductor_nombre
        FROM entregavehiculo e
        LEFT JOIN usuarios u ON e.ent_idusuario = u.idusuarios
        WHERE e.ent_vehiculo LIKE '%$placa%'
        AND e.ent_fechaentrega >= '2026-06-01'
        ORDER BY e.ent_fechaentrega DESC, e.identregavehiculo DESC";

    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

public function actualizarDatosAceite($datos) {
    $id      = intval($datos['id']);
    $fecha   = $this->dbname->real_escape_string($datos['veh_fechamantenimiento']);
    $kilact  = $this->dbname->real_escape_string($datos['veh_kilactual']);
    $kmcambio = $this->dbname->real_escape_string($datos['veh_kmactual_cambioaceite']);
    $limite  = $this->dbname->real_escape_string($datos['veh_calkmcambioaceite']);

    $sql = "UPDATE vehiculos SET 
                veh_fechamantenimiento    = '$fecha',
                veh_kilactual             = '$kilact',
                veh_kmactual_cambioaceite = '$kmcambio',
                veh_calkmcambioaceite     = '$limite'
            WHERE idvehiculos = $id";

    return $this->dbname->query($sql) ? true : false;
}

public function actualizarSoat($datos) {
    $id    = intval($datos['id']);
    $fecha = $this->dbname->real_escape_string($datos['veh_fechaseguro']);

    $veh_img_soat = '';
    if (isset($_FILES['veh_img_soat']) && is_uploaded_file($_FILES['veh_img_soat']['tmp_name'])) {
        $veh_img_soat = $this->guardarImagen($_FILES['veh_img_soat'], 'uploads/vehiculos');
    }

    $sqlImg = $veh_img_soat ? ", veh_img_soat = '" . $this->dbname->real_escape_string($veh_img_soat) . "'" : '';

    $sql = "UPDATE vehiculos SET veh_fechaseguro = '$fecha' $sqlImg WHERE idvehiculos = $id";
    return $this->dbname->query($sql) ? true : false;
}

public function actualizarTecnomecanica($datos) {
    $id    = intval($datos['id']);
    $fecha = $this->dbname->real_escape_string($datos['veh_fechategnomecanica']);

    $veh_img_tecnomecanica = '';
    if (isset($_FILES['veh_img_tecnomecanica']) && is_uploaded_file($_FILES['veh_img_tecnomecanica']['tmp_name'])) {
        $veh_img_tecnomecanica = $this->guardarImagen($_FILES['veh_img_tecnomecanica'], 'uploads/vehiculos');
    }

    $sqlImg = $veh_img_tecnomecanica ? ", veh_img_tecnomecanica = '" . $this->dbname->real_escape_string($veh_img_tecnomecanica) . "'" : '';

    $sql = "UPDATE vehiculos SET veh_fechategnomecanica = '$fecha' $sqlImg WHERE idvehiculos = $id";
    return $this->dbname->query($sql) ? true : false;

}

public function actualizarEntrega($datos) {
    $id     = intval($datos['ent_id']);
    $fecha  = $this->dbname->real_escape_string($datos['ent_fechaentrega']);
    $fecharegist = $this->dbname->real_escape_string($datos['ent_fecharegistra'] ?? '');
    $sede   = $this->dbname->real_escape_string($datos['ent_sede']);
    $obs    = $this->dbname->real_escape_string($datos['ent_observaciones'] ?? '');
    
    // ✅ Procesar fotos de herramientas antes de serializar
    $equipoJson = $datos['ent_equipo_carretera'] ?? '[]';
    $equipo = json_decode($equipoJson, true) ?? [];

    foreach ($equipo as $idx => &$herramienta) {
        $key = "ent_herramienta_foto_{$idx}";
        if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
            $rutaFoto = $this->guardarImagen($_FILES[$key], 'uploads/vehiculos/herramientas');
            if ($rutaFoto) $herramienta['foto'] = $rutaFoto;
        }
    }
    unset($herramienta);

    $equipo = $this->dbname->real_escape_string(
        json_encode($equipo, JSON_UNESCAPED_UNICODE)
    );

    $sqlImgs = '';
    if (isset($_FILES['ent_img_frente']) && 
        is_uploaded_file($_FILES['ent_img_frente']['tmp_name'])) {
        $ruta = $this->guardarImagen($_FILES['ent_img_frente'], 'uploads/vehiculos');
        if ($ruta) $sqlImgs .= ", ent_img_frente = '" . 
                               $this->dbname->real_escape_string($ruta) . "'";
    }
    if (isset($_FILES['ent_img_respaldo']) && 
        is_uploaded_file($_FILES['ent_img_respaldo']['tmp_name'])) {
        $ruta = $this->guardarImagen($_FILES['ent_img_respaldo'], 'uploads/vehiculos');
        if ($ruta) $sqlImgs .= ", ent_img_trasera = '" . 
                               $this->dbname->real_escape_string($ruta) . "'";
    }

    $sql = "UPDATE entregavehiculo SET
                ent_fechaentrega      = '$fecha',
                ent_fecharegistra     = '$fecharegist',
                ent_sede              = '$sede',
                ent_observaciones     = '$obs',
                ent_equipo_carretera  = '$equipo'
                {$sqlImgs}
            WHERE identregavehiculo = $id";

    return $this->dbname->query($sql) ? true : ['error' => $this->dbname->error];
}

public function eliminarEntrega($id) {
    $id  = intval($id);
    $sql = "DELETE FROM entregavehiculo WHERE identregavehiculo = $id";
    return $this->dbname->query($sql) ? true : ['error' => $this->dbname->error];
}

// Guardar revisión de comparendos
public function guardarRevisionComparendo($datos) {
    $evidencia = '';
    if (isset($_FILES['rev_evidencia']) && is_uploaded_file($_FILES['rev_evidencia']['tmp_name'])) {
        $evidencia = $this->guardarImagen($_FILES['rev_evidencia'], 'uploads/revisiones_comparendos');
    }

    $idVehiculo = intval($datos['rev_vehiculo_id']);
    $fecha      = $this->dbname->real_escape_string($datos['rev_fecha_consulta']);
    $usuario    = $this->dbname->real_escape_string($datos['rev_usuario']);
    $evidencia  = $this->dbname->real_escape_string($evidencia);

    $sql = "INSERT INTO revision_comparendos 
                (rev_vehiculo_id, rev_fecha_consulta, rev_evidencia, rev_usuario)
            VALUES ($idVehiculo, '$fecha', '$evidencia', '$usuario')";

    return $this->dbname->query($sql) ? true : ['error' => $this->dbname->error];
}

// Obtener revisiones de un vehículo
public function obtenerRevisionesPorVehiculo($idVehiculo) {
    $id  = intval($idVehiculo);
    $sql = "SELECT * FROM revision_comparendos 
            WHERE rev_vehiculo_id = $id 
            ORDER BY rev_fecha_creacion DESC";
    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

public function eliminarRevision($id) {
    $id  = intval($id);
    $sql = "DELETE FROM revision_comparendos WHERE idrevision = $id";
    return $this->dbname->query($sql) ? true : ['error' => $this->dbname->error];
}

}