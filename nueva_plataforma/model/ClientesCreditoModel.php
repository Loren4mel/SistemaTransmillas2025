<?php
require_once "../config/database.php";

class ClientesCreditoModel{
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function contarClientes($search = '', $fecha = '', $tipo = '') {
        $sql = "SELECT COUNT(*) AS total
                FROM creditos cd
                -- INNER JOIN clientes c ON cd.cli_idclientes = c.idclientes
                -- INNER JOIN ciudades ciu ON cd.cli_idciudad = ciu.idciudades
                WHERE cd.idcreditos > 0";

        $params = [];
        $types = "";

        // if (!empty($search)) {
        //     if (is_numeric($search)) {
        //     $sql .= " AND (cd.cli_telefono LIKE ?)";
        //     $like = "%$search%";
        //     $params = [$like];
        //     $types = "s";

        //     }else {
        //     $sql .= " AND (cd.cli_nombre LIKE ? OR c.cli_iddocumento LIKE ? )";
        //     $like = "%$search%";
        //     $params = [$like, $like];
        //     $types = "ss";
        //     }

        // }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }
    public function obtenerClientesPaginado($start, $length, $search = '', $fecha = '', $ciudad = '') {
        $sql = "SELECT 
            cd.idcreditos,
            cd.cre_nombre,
            cd.cre_estado,
            cd.cre_estado_final,
            (
                SELECT cdir.cli_telefono
                FROM rel_crecli rc
                INNER JOIN clientesdir cdir ON cdir.idclientesdir = rc.rel_idcliente
                WHERE rc.rel_idcredito = cd.idcreditos
                ORDER BY cdir.idclientesdir DESC
                LIMIT 1
            ) AS cli_telefono,
            (
                SELECT cdir.idclientesdir
                FROM rel_crecli rc
                INNER JOIN clientesdir cdir ON cdir.idclientesdir = rc.rel_idcliente
                WHERE rc.rel_idcredito = cd.idcreditos
                ORDER BY cdir.idclientesdir DESC
                LIMIT 1
            ) AS idclientes
        FROM creditos cd ";

        $params = [];
        $types = "";

        // if (!empty($search)) {
        //     if (is_numeric($search)) {
        //     $sql .= " AND ( cd.cli_telefono LIKE ?)";
        //     $like = "%$search%";
        //     $params = [$like];
        //     $types = "s";

        //     }else {
        //     $sql .= " AND (cd.cli_nombre LIKE ? OR c.cli_iddocumento LIKE ? )";
        //     $like = "%$search%";
        //     $params = [$like, $like];
        //     $types = "ss";
        //     }

        // }

        // if ($ciudad!="") {
        //     $sql .= " AND cd.cli_idciudad = ?)";
        //     $params = [$ciudad];
        //     $types = "s";
        // }

        $sql .= " ORDER BY cd.idcreditos DESC LIMIT ?, ?";
        $params[] = intval($start);
        $params[] = intval($length);
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function obtenerRoles() {
        $sql = "SELECT idroles, rol_nombre FROM roles ORDER BY rol_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }



    public function obtenerCiudades() {
        $sql = "SELECT `idciudades`, `ciu_nombre` FROM `ciudades`  where inner_estados=1 ";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function obtenerCreditos() {
        $sql = "SELECT `idcreditos`, `cre_nombre` FROM `creditos` ";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    // public function buscarClientePorTelefono($telefono) {
    //     $sql = "SELECT * FROM clientesdir WHERE cli_telefono = ? LIMIT 1";
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->bind_param("s", $telefono); // "s" = string
    //     $stmt->execute();
    //     $result = $stmt->get_result();

    //     return $result ? $result->fetch_assoc() : null;
    // }
    public function buscarClientePorTelefono($telefono) {
        $sql = "SELECT 
                cd.*, 
                COUNT(c.idrelcrecli) AS total_creditos, 
                GROUP_CONCAT(c.idrelcrecli) AS creditos_asociados,
                GROUP_CONCAT(cr.cre_nombre SEPARATOR ', ') AS nombres_creditos
            FROM clientesdir cd
            LEFT JOIN rel_crecli c ON cd.idclientesdir = c.rel_idcliente
            LEFT JOIN creditos cr ON c.rel_idcredito = cr.idcreditos
            WHERE cd.cli_telefono = ?
            GROUP BY cd.idclientesdir;";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $telefono);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }
    public function actualizarCliente($data) {
        $sql = "UPDATE clientesdir SET 
                    
                    cli_telefono = ?, 
                    cli_whatsap = ?, 
                    cli_idciudad = ?, 
                    cli_nombre = ?, 
                    cli_direccion = ?, 
                    cli_correo = ?
                WHERE cli_telefono = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            // Error al preparar la sentencia
            $error = "Error al preparar UPDATE: " . $this->db->error;
            error_log($error, 3, __DIR__ . '/error.log'); // Guarda en logs/error.log
            return false;
        }

        $direccion = $data['direccion'] . '&' . $data['restodireccion'] . '&' . $data['lugar_recogida'] . '&' . $data['barrio'];

        if (!$stmt->bind_param(
            "sssssss",
            
            $data['telefonos'],
            $data['whatsapp'],
            $data['ciudad'],
            $data['nombre_cliente'],
            $direccion,
            $data['email'],
            $data['telefonos']
        )) {
            // Error al bindear parámetros
            $error = "Error al bindear parámetros: " . $stmt->error;
            error_log($error, 3, __DIR__ . '/error.log');
            return false;
        }

        if (!$stmt->execute()) {
            // Error al ejecutar la sentencia
            $error = "Error al ejecutar UPDATE: " . $stmt->error;
            error_log($error, 3, __DIR__ . '/error.log');
            return false;
        }
        
        // Obtener id del cliente actualizado
        $sqlId = "SELECT idclientesdir FROM clientesdir WHERE cli_telefono = ?";
        $stmtId = $this->db->prepare($sqlId);
        $stmtId->bind_param("s", $data['telefonos']);
        $stmtId->execute();
        $result = $stmtId->get_result();
        $row = $result->fetch_assoc();
        $idCliente = $row ? $row['idclientesdir'] : null;
        $stmtId->close();
        if (!empty($data['creditos_asignados']) && $idCliente) {
            $creditos = explode(",", $data['creditos_asignados']); // array de IDs

            $sqlInsert = "INSERT INTO rel_crecli (rel_idcredito, rel_idcliente) VALUES (?, ?)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            foreach ($creditos as $idCredito) {
                $idCredito = trim($idCredito); // limpiar espacios
                if ($idCredito !== "") {
                    $stmtInsert->bind_param("ii", $idCredito, $idCliente);
                    $stmtInsert->execute();
                }
            }

            $stmtInsert->close();
        }

        return true; // Si todo salió bien
    }

    // Función para registrar logs
 
    
}
