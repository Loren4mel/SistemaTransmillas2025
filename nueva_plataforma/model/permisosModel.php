<?php
require_once "../config/database.php";

class Permisos {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function obtenerPermisos($filtroRol = '', $principal = '', $secundario = '') {
        $sql = "SELECT permisos.idpermisos,
        UPPER(roles.rol_nombre) AS rol_nombre,
        COALESCE(menu_padre.men_nombre, 'Menu principal') AS men_predecesor,
        UPPER(menu.men_nombre) AS men_nombre,
        permisos.per_crear,
        permisos.per_editar,
        permisos.per_eliminar,
        permisos.per_consultar,
        menu.men_url,
        menu.idmenu
        FROM permisos
        INNER JOIN roles ON permisos.roles_idroles = roles.idroles
        INNER JOIN menu ON permisos.menu_idmenu = menu.idmenu
        LEFT JOIN menu AS menu_padre ON menu.men_predecesor = menu_padre.idmenu";

        $where = [];

        if ($filtroRol !== '') {
            $where[] = "roles.idroles = '" . $this->db->real_escape_string($filtroRol) . "'";
        }

        if ($principal !== '') {
            $where[] = "permisos.menu_idmenu = '" . $this->db->real_escape_string($principal) . "'";
        }
        // if ($secundario !== '') {
        //     $where[] = "menu.idmenu = '" . $this->db->real_escape_string($secundario) . "'";
        // }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY menu.men_nombre ASC";
        
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerRoles() {
        $sql = "SELECT idroles, rol_nombre FROM roles ORDER BY rol_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function obtenerMenuPrincipal() {
        $sql = "SELECT idmenu, men_nombre FROM menu  ORDER BY men_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function obtenerMenuSecundario($param2='') {
        $sql = "SELECT idmenu, men_nombre FROM menu WHERE (men_predecesor='$param2' AND men_principal=1) ORDER BY men_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    

    public function actualizarCampo($id, $campo, $valor) {
        $permitidos = ['per_crear', 'per_editar', 'per_eliminar', 'per_consultar'];
        if (!in_array($campo, $permitidos)) {
            return false;
        }

        $id = (int) $id;
        $valor = ((int) $valor) === 1 ? 1 : 0;

        $sql = "UPDATE permisos SET $campo = ? WHERE idpermisos = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $valor, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
    public function eliminarUsuario($id) {
        $sql = "DELETE FROM usuarios WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    public function listarDispositivos($idUsuario) {
        $sql = "SELECT id, device_name, device_type, last_login, ip_last, authorized
                FROM user_devices
                WHERE user_id = ? AND active = 1
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function autorizarDispositivo($id) {
        $stmt = $this->db->prepare("UPDATE user_devices SET authorized = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    // public function bloquearDispositivo($id) {
    //     $stmt = $this->db->prepare("UPDATE user_devices SET active = 0 WHERE id = ?");
    //     $stmt->bind_param("i", $id);
    //     $stmt->execute();
    // }
}
