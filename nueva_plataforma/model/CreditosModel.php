<?php
require_once "../config/database.php";

class CreditosModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function contarCreditos($search = '') {
        $sql = "SELECT COUNT(*) AS total FROM creditos WHERE idcreditos > 0";
        $params = [];
        $types = "";

        if ($search !== '') {
            $sql .= " AND cre_nombre LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }

    public function obtenerCreditosPaginado($start, $length, $search = '') {
        $sql = "SELECT idcreditos, cre_nombre, cre_estado, cre_estado_final
                FROM creditos
                WHERE idcreditos > 0";
        $params = [];
        $types = "";

        if ($search !== '') {
            $sql .= " AND cre_nombre LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }

        $sql .= " ORDER BY idcreditos DESC LIMIT ?, ?";
        $params[] = intval($start);
        $params[] = intval($length);
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function crearCredito($nombre) {
        $nombre = trim($nombre);

        if ($nombre === '') {
            return [
                'success' => false,
                'message' => 'El nombre del credito es obligatorio.'
            ];
        }

        $sqlExiste = "SELECT idcreditos FROM creditos WHERE cre_nombre = ? LIMIT 1";
        $stmtExiste = $this->db->prepare($sqlExiste);
        $stmtExiste->bind_param("s", $nombre);
        $stmtExiste->execute();
        $existe = $stmtExiste->get_result()->fetch_assoc();
        $stmtExiste->close();

        if ($existe) {
            return [
                'success' => false,
                'message' => 'Ya existe un credito con ese nombre.'
            ];
        }

        $estado = 'Activo';
        $sql = "INSERT INTO creditos (cre_nombre, cre_estado, cre_estado_final) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'No se pudo preparar el registro del credito.'
            ];
        }

        $stmt->bind_param("sss", $nombre, $estado, $estado);
        $ok = $stmt->execute();
        $stmt->close();

        return [
            'success' => $ok,
            'message' => $ok ? 'Credito creado correctamente.' : 'No se pudo crear el credito.'
        ];
    }

    public function actualizarEstadoCredito($idCredito, $estado) {
        $idCredito = intval($idCredito);
        $estadosPermitidos = ['Activo', 'Inactivo'];

        if ($idCredito <= 0 || !in_array($estado, $estadosPermitidos, true)) {
            return [
                'success' => false,
                'message' => 'Datos invalidos para actualizar el estado.'
            ];
        }

        $sql = "UPDATE creditos SET cre_estado = ?, cre_estado_final = ? WHERE idcreditos = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'No se pudo preparar la actualizacion del estado.'
            ];
        }

        $stmt->bind_param("ssi", $estado, $estado, $idCredito);
        $ok = $stmt->execute();
        $stmt->close();

        return [
            'success' => $ok,
            'message' => $ok ? 'Estado actualizado correctamente.' : 'No se pudo actualizar el estado.'
        ];
    }
}
