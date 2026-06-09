<?php
require_once "../config/database.php";

class PreciosTransporteModel
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->crearTabla();
    }

    private function crearTabla(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS precios_transporte_pieza (
            idpreciotransporte INT NOT NULL AUTO_INCREMENT,
            ptr_tipo VARCHAR(20) NOT NULL,
            ptr_idciudadori INT NOT NULL,
            ptr_idciudaddes INT NOT NULL,
            ptr_valorpieza DECIMAL(12,2) NOT NULL DEFAULT 0,
            ptr_idusuario INT NOT NULL DEFAULT 0,
            ptr_fechacreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ptr_fechaactualiza DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (idpreciotransporte),
            UNIQUE KEY uk_precio_transporte_ruta (ptr_tipo, ptr_idciudadori, ptr_idciudaddes)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$this->db->query($sql)) {
            throw new RuntimeException("No fue posible preparar la tabla de precios de transporte.");
        }
    }

    public function obtenerCiudades(): array
    {
        $resultado = $this->db->query("SELECT idciudades, ciu_nombre FROM ciudades WHERE inner_estados=1 ORDER BY ciu_nombre");
        return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function listar(): array
    {
        $sql = "SELECT p.idpreciotransporte, p.ptr_tipo, p.ptr_idciudadori,
                ori.ciu_nombre AS ciudad_origen, p.ptr_idciudaddes,
                des.ciu_nombre AS ciudad_destino, p.ptr_valorpieza
            FROM precios_transporte_pieza p
            INNER JOIN ciudades ori ON ori.idciudades = p.ptr_idciudadori
            INNER JOIN ciudades des ON des.idciudades = p.ptr_idciudaddes
            ORDER BY p.ptr_tipo, ori.ciu_nombre, des.ciu_nombre";
        $resultado = $this->db->query($sql);
        return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function guardar(array $datos, int $usuario): array
    {
        $id = (int) ($datos["id"] ?? 0);
        $tipo = trim((string) ($datos["tipo"] ?? ""));
        $origen = (int) ($datos["origen"] ?? 0);
        $destino = (int) ($datos["destino"] ?? 0);
        $valor = (float) ($datos["valor"] ?? 0);

        if (!in_array($tipo, ["Bus", "Furgon"], true) || $origen <= 0 || $destino <= 0 || $valor <= 0) {
            return ["success" => false, "mensaje" => "Complete todos los datos y registre un valor mayor que cero."];
        }

        if ($origen === $destino) {
            return ["success" => false, "mensaje" => "La ciudad de origen y destino deben ser diferentes."];
        }

        $stmt = $this->db->prepare("SELECT idpreciotransporte FROM precios_transporte_pieza
            WHERE ptr_tipo = ? AND ptr_idciudadori = ? AND ptr_idciudaddes = ? AND idpreciotransporte <> ? LIMIT 1");
        $stmt->bind_param("siii", $tipo, $origen, $destino, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            return ["success" => false, "mensaje" => "Ya existe un precio configurado para ese transporte y ruta."];
        }
        $stmt->close();

        if ($id > 0) {
            $stmt = $this->db->prepare("UPDATE precios_transporte_pieza SET
                ptr_tipo = ?, ptr_idciudadori = ?, ptr_idciudaddes = ?, ptr_valorpieza = ?, ptr_idusuario = ?
                WHERE idpreciotransporte = ?");
            $stmt->bind_param("siidii", $tipo, $origen, $destino, $valor, $usuario, $id);
            $mensaje = "Precio actualizado correctamente.";
        } else {
            $stmt = $this->db->prepare("INSERT INTO precios_transporte_pieza
                (ptr_tipo, ptr_idciudadori, ptr_idciudaddes, ptr_valorpieza, ptr_idusuario)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siidi", $tipo, $origen, $destino, $valor, $usuario);
            $mensaje = "Precio registrado correctamente.";
        }

        $ok = $stmt->execute();
        $stmt->close();

        return ["success" => $ok, "mensaje" => $ok ? $mensaje : "No fue posible guardar el precio."];
    }

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM precios_transporte_pieza WHERE idpreciotransporte = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
