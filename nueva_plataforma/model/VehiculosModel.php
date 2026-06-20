<?php
require_once "../config/database.php";

class VehiculosModel {
    private $dbname;

    public function __construct() {
        $this->dbname = (new Database())->connect();
    }

    public function obtenerVehiculos($filtroTipovehiculo = '', $filtroEstado = '', $filtroPropiedad = '') {
    $sql = "SELECT v.`idvehiculos`, v.`veh_tipo`, v.`veh_placa`, v.`veh_marca`, v.`veh_modelo`,
    v.`veh_fechaseguro`, v.`veh_fechategnomecanica`, v.`veh_fechamantenimiento`, v.`veh_kilactual`,
    v.`veh_kmactual_cambioaceite`, v.`veh_aceitekil`, v.`veh_propiedad`, v.`veh_dueño`, v.`veh_estado`,
    v.`veh_chasis`, v.`veh_tipov`, v.`veh_cilidraje`, v.`veh_motor`, v.`veh_color`, v.`veh_usuve`,
    v.`veh_observaciones`, v.`veh_calkmcambioaceite`, v.`veh_restankmaceite`,
    v.`veh_faltaparacambioaceite`, v.`veh_kmalcambaceite`,
    v.`veh_img_anverso`, v.`veh_img_reverso`, v.`veh_img_actual_frente`, v.`veh_img_actual_trasera`,
    v.`veh_img_soat`, v.`veh_img_tecnomecanica`, v.`veh_equipo_carretera`,
     -- Columnas calculadas de aceite (fuente de verdad: veh_kilactual, veh_kmactual_cambioaceite, veh_calkmcambioaceite)
     (v.`veh_kilactual` - v.`veh_kmactual_cambioaceite`) AS veh_km_recorridos_aceite,
     (v.`veh_calkmcambioaceite` - (v.`veh_kilactual` - v.`veh_kmactual_cambioaceite`)) AS veh_km_restantes_aceite,
     (v.`veh_kmactual_cambioaceite` + v.`veh_calkmcambioaceite`) AS veh_km_proximo_aceite,
     IF(u.idusuarios = 14, 'Transmillas', u.usu_nombre) AS usu_nombre,
     COALESCE(c.total_comparendos, 0) AS total_comparendos,
     COALESCE(c.comparendos_pendientes, 0) AS comparendos_pendientes,
     r.ultima_revision
    FROM `vehiculos` v
    LEFT JOIN usuarios u ON v.`veh_dueño` = u.idusuarios
    LEFT JOIN (
        SELECT com_vehiculo_id,
               COUNT(*) AS total_comparendos,
               SUM(CASE WHEN com_estado = 'Pendiente' THEN 1 ELSE 0 END) AS comparendos_pendientes
        FROM comparendos
        GROUP BY com_vehiculo_id
    ) c ON c.com_vehiculo_id = v.idvehiculos
    LEFT JOIN (
        SELECT rev_vehiculo_id, MAX(rev_fecha_consulta) AS ultima_revision
        FROM revision_comparendos
        GROUP BY rev_vehiculo_id
    ) r ON r.rev_vehiculo_id = v.idvehiculos
     WHERE 1=1";
    if ($filtroTipovehiculo !== '') {
        $sql .= " AND v.veh_tipo = '" . $this->dbname->real_escape_string($filtroTipovehiculo) . "'";
    }

    if ($filtroEstado !== '') {
        $sql .= " AND v.veh_estado = '" . $this->dbname->real_escape_string($filtroEstado) . "'";
    }

     if ($filtroPropiedad !== '') {
        $sql .= " AND v.veh_propiedad = '" . $this->dbname->real_escape_string($filtroPropiedad) . "'";
    }

    $result = $this->dbname->query($sql);
    $vehiculos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Post-procesar: contar herramientas inactivas (existe = 'no') por vehículo
    foreach ($vehiculos as &$v) {
        $equipo = json_decode($v['veh_equipo_carretera'] ?: '[]', true);
        $inactivas = 0;
        if (is_array($equipo)) {
            foreach ($equipo as $h) {
                if (is_array($h) && ($h['existe'] ?? 'si') === 'no') {
                    $inactivas++;
                }
            }
        }
        $v['herramientas_inactivas'] = $inactivas;
    }
    unset($v);

    return $vehiculos;
}

    public function obtenerVehiculosParaSelect($filtroEstado = '') {
    $sql = "SELECT idvehiculos, veh_tipo, veh_placa, veh_marca, veh_modelo, veh_estado, veh_propiedad
            FROM vehiculos
            WHERE 1=1";

    if ($filtroEstado !== '') {
        $sql .= " AND veh_estado = '" . $this->dbname->real_escape_string($filtroEstado) . "'";
    }

    $sql .= " ORDER BY veh_placa ASC";

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
private function procesarFotosHerramientas($equipoJson, $prefijoArchivo)
{
    $equipo = json_decode($equipoJson ?: '[]', true);
    if (!is_array($equipo)) {
        return '[]';
    }

    foreach ($equipo as $idx => &$herramienta) {
        if (!is_array($herramienta)) {
            continue;
        }

        $key = $herramienta['foto_key'] ?? "{$prefijoArchivo}{$idx}";
        if (isset($_FILES[$key]) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
            $rutaFoto = $this->guardarImagen($_FILES[$key], 'uploads/vehiculos/herramientas');
            if ($rutaFoto) {
                $herramienta['foto'] = $rutaFoto;
            }
        }

        unset($herramienta['foto_key']);
    }
    unset($herramienta);

    return json_encode($equipo, JSON_UNESCAPED_UNICODE);
}

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

$datos['veh_equipo_carretera'] = $this->procesarFotosHerramientas(
    $datos['veh_equipo_carretera'] ?? '[]',
    'veh_herramienta_foto_'
);





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

private function columnaExiste(string $tabla, string $columna): bool
{
    $tabla = $this->dbname->real_escape_string($tabla);
    $columna = $this->dbname->real_escape_string($columna);
    $result = $this->dbname->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
    return $result && $result->num_rows > 0;
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
    $veh_img_anverso        = $this->guardarImagen($_FILES['veh_img_anverso'] ?? [],        "uploads/vehiculos");
    $veh_img_reverso        = $this->guardarImagen($_FILES['veh_img_reverso'] ?? [],        "uploads/vehiculos");
    $veh_img_actual_frente  = $this->guardarImagen($_FILES['veh_img_actual_frente'] ?? [],  "uploads/vehiculos");
    $veh_img_actual_trasera = $this->guardarImagen($_FILES['veh_img_actual_trasera'] ?? [], "uploads/vehiculos");

$datos['veh_equipo_carretera'] = $this->procesarFotosHerramientas(
    $datos['veh_equipo_carretera'] ?? '[]',
    'veh_herramienta_foto_edit_'
);

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
        veh_kilactual             = ?,
        veh_kmactual_cambioaceite = ?,
        veh_calkmcambioaceite     = ?,
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
        "ssssssssssssssssss",
        $datos['veh_tipo'],
        $datos['veh_marca'],
        $datos['veh_placa'],
        $datos['veh_modelo'],
        $datos['veh_color'],
        $datos['veh_tipov'],
        $datos['veh_propiedad'],
        $datos['veh_dueno'],
        $datos['veh_kilactual'],
        $datos['veh_kmactual_cambioaceite'],
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

    $ent_img_frente  = $this->guardarImagen($_FILES['ent_img_frente'] ?? [],  "uploads/vehiculos");
    $ent_img_trasera = $this->guardarImagen($_FILES['ent_img_trasera'] ?? [], "uploads/vehiculos");

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

    $tieneVideoUrl = $this->columnaExiste('entregavehiculo', 'ent_video_url');
    $columnas = [
        'ent_fechaentrega', 'ent_vehiculo', 'ent_idvehiculo', 'ent_userregistra',
        'ent_idusuario', 'ent_idusuarioencargado', 'ent_tipoentrega', 'ent_fecharegistra',
        'ent_idhojadevida', 'ent_sede', 'ent_img_frente', 'ent_img_trasera',
        'ent_equipo_carretera', 'ent_observaciones', 'ent_firma'
    ];
    if ($tieneVideoUrl) {
        $columnas[] = 'ent_video_url';
    }

    $sql = "INSERT INTO entregavehiculo (" . implode(', ', $columnas) . ")
            VALUES (" . implode(', ', array_fill(0, count($columnas), '?')) . ")";

    $stmt = $this->dbname->prepare($sql);
    if (!$stmt) return ['error' => 'Prepare falló: ' . $this->dbname->error];

    $idVehiculo = intval($datos['ent_idvehiculo'] ?? $datos['ent_vehiculo_id'] ?? 0);
    $idUsuarioEncargado = isset($datos['ent_idusuarioencargado']) && $datos['ent_idusuarioencargado'] !== ''
        ? intval($datos['ent_idusuarioencargado'])
        : null;
    $tipos = $tieneVideoUrl ? "ssissississsssss" : "ssissississssss";
    $valores = [
        $datos['ent_fechaentrega'],
        $datos['ent_vehiculo'],
        $idVehiculo,
        $datos['ent_userregistra'],
        (string) $datos['ent_idusuario'],
        $idUsuarioEncargado,
        $datos['ent_tipoentrega'],
        $datos['ent_fecharegistra'],
        $idHojaDeVida,
        $datos['ent_sede'],
        $ent_img_frente,
        $ent_img_trasera,
        $datos['ent_equipo_carretera'],
        $datos['ent_observaciones'],
        $ent_firma_path
    ];
    if ($tieneVideoUrl) {
        $valores[] = $datos['ent_video_url'] ?? '';
    }

    $stmt->bind_param($tipos, ...$valores);

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
             com_hojadevida_id, com_valor, com_numerocompa, com_titularcompa, com_foto_curso, com_observacion)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $this->dbname->prepare($sql);
    if (!$stmt) return ['error' => $this->dbname->error];

    $observacion = isset($datos['com_observacion']) ? $datos['com_observacion'] : null;

    $stmt->bind_param(
        "iisssisssss",
        $datos['com_operador_id'],
        $datos['com_vehiculo_id'],
        $datos['com_estado'],
        $com_foto,
        $datos['com_fecha'],
        $idHoja,
        $datos['com_valor'],
        $datos['com_numerocompa'],
        $datos['com_titularcompa'],
        $com_foto_curso,
        $observacion
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
    $observacion = isset($datos['com_observacion']) ? $this->dbname->real_escape_string($datos['com_observacion']) : '';

    $sqlFotos = '';
    if ($com_foto)       $sqlFotos .= ", com_foto = '" . $this->dbname->real_escape_string($com_foto) . "'";
    if ($com_foto_curso) $sqlFotos .= ", com_foto_curso = '" . $this->dbname->real_escape_string($com_foto_curso) . "'";

    $sql = "UPDATE comparendos SET
                com_estado       = '$estado',
                com_valor        = '$valor',
                com_numerocompa  = '$numero',
                com_titularcompa = '$titular',
                com_observacion  = '$observacion'
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

    $videoSelect = $this->columnaExiste('entregavehiculo', 'ent_video_url')
        ? "e.ent_video_url"
        : "'' AS ent_video_url";

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
            $videoSelect,
            u.usu_nombre AS conductor_nombre
        FROM entregavehiculo e
        LEFT JOIN usuarios u ON e.ent_idusuario = u.idusuarios
        WHERE e.ent_vehiculo LIKE '%$placa%'
        AND e.ent_fechaentrega >= '2026-06-01'
        ORDER BY e.ent_fechaentrega DESC, e.identregavehiculo DESC";

    $result = $this->dbname->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

	/**
	 * Calcula el estado actual del aceite para un vehiculo.
	 * Metodo centralizado — usar SIEMPRE este metodo en lugar de leer las columnas legacy.
	 *
	 * @param int $idVehiculo
	 * @return array|null { km_actual, km_ultimo_cambio, intervalo_km, km_recorridos, km_restantes, km_proximo_cambio, porcentaje_uso, alerta, necesita_cambio, fecha_ultimo_cambio }
	 */
	public function calcularEstadoAceite($idVehiculo) {
	    $v = $this->obtenerVehiculoPorId($idVehiculo);
	    if (!$v) return null;

	    $kmActual    = intval(str_replace('.', '', $v['veh_kilactual'] ?? '0'));
	    $kmUltCambio = intval(str_replace('.', '', $v['veh_kmactual_cambioaceite'] ?? '0'));
	    $intervalo   = intval(str_replace('.', '', $v['veh_calkmcambioaceite'] ?? '0'));

	    $kmRecorridos = $kmActual - $kmUltCambio;
	    $kmRestantes  = $intervalo - $kmRecorridos;
	    $kmProximo    = $kmUltCambio + $intervalo;
	    $porcentaje   = $intervalo > 0 ? round(($kmRecorridos / $intervalo) * 100, 1) : 0;

	    // Alerta: rojo <= 500km, amarillo <= 1000km, verde > 1000km
	    $alerta = $kmRestantes <= 500 ? 'rojo' : ($kmRestantes <= 1000 ? 'amarillo' : 'verde');

	    return [
	        'km_actual'          => $kmActual,
	        'km_ultimo_cambio'   => $kmUltCambio,
	        'intervalo_km'       => $intervalo,
	        'km_recorridos'      => $kmRecorridos,
	        'km_restantes'       => $kmRestantes,
	        'km_proximo_cambio'  => $kmProximo,
	        'porcentaje_uso'     => $porcentaje,
	        'alerta'             => $alerta,
	        'necesita_cambio'    => $kmRestantes <= 0,
	        'fecha_ultimo_cambio'=> $v['veh_fechamantenimiento'] ?? null,
	    ];
	}

	public function actualizarDatosAceite($datos) {
	    $id      = intval($datos['id']);
	    $fecha   = $this->dbname->real_escape_string($datos['veh_fechamantenimiento']);
	    $kmcambio = $this->dbname->real_escape_string($datos['veh_kmactual_cambioaceite']);

	    // 1. Actualizar tabla vehiculos (columnas fuente de verdad)
	    $sql = "UPDATE vehiculos SET
	                veh_fechamantenimiento    = '$fecha',
	                veh_kmactual_cambioaceite = '$kmcambio'
	            WHERE idvehiculos = $id";

	    $resultado = $this->dbname->query($sql);
	    if (!$resultado) return false;

	    // 2. Insertar en tabla aceite para trazabilidad historica
	    //    (el modulo SeguimientoVehiculo lee de esta tabla en su grafico de historial)
	    $sqlAceite = "INSERT INTO aceite (ace_idvehiculo, ace_fechacambio, ace_kiloalcambio)
	                  VALUES ('$id', '$fecha', '$kmcambio')";
	    $this->dbname->query($sqlAceite); // best-effort: no romper si la tabla no existe aun

	    return true;
	}public function actualizarSoat($datos) {
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
    $evidencias = [];
    if (isset($_FILES['rev_evidencia'])) {
        $files = $_FILES['rev_evidencia'];

        if (is_array($files['tmp_name'] ?? null)) {
            foreach ($files['tmp_name'] as $idx => $tmpName) {
                $file = [
                    'name' => $files['name'][$idx] ?? '',
                    'type' => $files['type'][$idx] ?? '',
                    'tmp_name' => $tmpName,
                    'error' => $files['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$idx] ?? 0,
                ];

                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                    $ruta = $this->guardarImagen($file, 'uploads/revisiones_comparendos');
                    if ($ruta !== '') {
                        $evidencias[] = $ruta;
                    }
                }
            }
        } elseif (is_uploaded_file($files['tmp_name'])) {
            $ruta = $this->guardarImagen($files, 'uploads/revisiones_comparendos');
            if ($ruta !== '') {
                $evidencias[] = $ruta;
            }
        }
    }

    $evidencia = count($evidencias) > 1
        ? json_encode($evidencias, JSON_UNESCAPED_SLASHES)
        : ($evidencias[0] ?? '');

    if ($evidencia === '') {
        return ['error' => 'Debe subir al menos una evidencia válida.'];
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

public function confirmarRevision($id, $usuario) {
    $id = intval($id);

    // Verificar si ya está confirmada (evitar doble confirmación)
    $check = "SELECT rev_confirmado FROM revision_comparendos WHERE idrevision = $id";
    $result = $this->dbname->query($check);
    if ($result && $row = $result->fetch_assoc()) {
        if ($row['rev_confirmado'] == 1) {
            return ['error' => 'Esta revisión ya fue confirmada anteriormente.'];
        }
    } else {
        return ['error' => 'La revisión no existe.'];
    }

    $usuario = $this->dbname->real_escape_string($usuario);
    $sql = "UPDATE revision_comparendos
            SET rev_confirmado = 1,
                rev_usuario_confirma = '$usuario',
                rev_fecha_confirmacion = NOW()
            WHERE idrevision = $id";
    return $this->dbname->query($sql) ? true : ['error' => $this->dbname->error];
}

}
