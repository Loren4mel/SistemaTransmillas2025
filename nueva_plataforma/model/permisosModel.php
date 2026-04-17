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

    public function crearPermiso($menuId, $rolId, $crear, $editar, $eliminar, $consultar) {
        $menuId = (int) $menuId;
        $rolId = (int) $rolId;
        $crear = ((int) $crear) === 1 ? 1 : 0;
        $editar = ((int) $editar) === 1 ? 1 : 0;
        $eliminar = ((int) $eliminar) === 1 ? 1 : 0;
        $consultar = ((int) $consultar) === 1 ? 1 : 0;

        if ($menuId <= 0 || $rolId <= 0) {
            return ['ok' => false, 'message' => 'Debe seleccionar un rol y un item de menu.'];
        }

        $stmt = $this->db->prepare("SELECT idpermisos FROM permisos WHERE menu_idmenu = ? AND roles_idroles = ? LIMIT 1");
        if (!$stmt) {
            return ['ok' => false, 'message' => 'No se pudo validar el permiso existente.'];
        }

        $stmt->bind_param("ii", $menuId, $rolId);
        $stmt->execute();
        $res = $stmt->get_result();
        $existe = $res && $res->num_rows > 0;
        $stmt->close();

        if ($existe) {
            return ['ok' => false, 'message' => 'Ya existe un permiso para ese rol y item de menu.'];
        }

        $sql = "INSERT INTO permisos (menu_idmenu, roles_idroles, per_crear, per_editar, per_eliminar, per_consultar)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['ok' => false, 'message' => 'No se pudo preparar el registro del permiso.'];
        }

        $stmt->bind_param("iiiiii", $menuId, $rolId, $crear, $editar, $eliminar, $consultar);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo crear el permiso.'];
        }

        return ['ok' => true, 'message' => 'Permiso creado correctamente.'];
    }

    public function obtenerPermisoPorId($id) {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $sql = "SELECT idpermisos, menu_idmenu, roles_idroles, per_crear, per_editar, per_eliminar, per_consultar
                FROM permisos
                WHERE idpermisos = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $permiso = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $permiso ?: null;
    }

    public function actualizarPermiso($id, $menuId, $rolId, $crear, $editar, $eliminar, $consultar) {
        $id = (int) $id;
        $menuId = (int) $menuId;
        $rolId = (int) $rolId;
        $crear = ((int) $crear) === 1 ? 1 : 0;
        $editar = ((int) $editar) === 1 ? 1 : 0;
        $eliminar = ((int) $eliminar) === 1 ? 1 : 0;
        $consultar = ((int) $consultar) === 1 ? 1 : 0;

        if ($id <= 0 || $menuId <= 0 || $rolId <= 0) {
            return ['ok' => false, 'message' => 'Debe seleccionar un rol y un item de menu.'];
        }

        $stmt = $this->db->prepare("SELECT idpermisos FROM permisos WHERE menu_idmenu = ? AND roles_idroles = ? AND idpermisos != ? LIMIT 1");
        if (!$stmt) {
            return ['ok' => false, 'message' => 'No se pudo validar el permiso existente.'];
        }

        $stmt->bind_param("iii", $menuId, $rolId, $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $existe = $res && $res->num_rows > 0;
        $stmt->close();

        if ($existe) {
            return ['ok' => false, 'message' => 'Ya existe un permiso para ese rol y item de menu.'];
        }

        $sql = "UPDATE permisos
                SET menu_idmenu = ?, roles_idroles = ?, per_crear = ?, per_editar = ?, per_eliminar = ?, per_consultar = ?
                WHERE idpermisos = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['ok' => false, 'message' => 'No se pudo preparar la actualizacion del permiso.'];
        }

        $stmt->bind_param("iiiiiii", $menuId, $rolId, $crear, $editar, $eliminar, $consultar, $id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo actualizar el permiso.'];
        }

        return ['ok' => true, 'message' => 'Permiso actualizado correctamente.'];
    }

    public function eliminarPermiso($id) {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $sql = "DELETE FROM permisos WHERE idpermisos = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
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
