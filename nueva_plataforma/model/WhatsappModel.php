<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/databaseWhatsapp.php";

class WhatsappModel {
    private $db;
    private $dbMain;

    public function __construct() {
        $this->db = (new DatabaseWhatsapp())->connect();
        $this->dbMain = (new Database())->connect();
    }

    public function obtenerMensajes($filtroFecha = '', $filtroTipoMensaje = '') {
        date_default_timezone_set('America/Bogota');

        if ($filtroFecha === '') {
            $filtroFecha = date('Y-m-d');
        }

        if ($filtroTipoMensaje === "ServiciosHechos") {
            return $this->obtenerServiciosHechos($filtroFecha);
        }

        $sql = "SELECT `id`, `fecha_hora`, `mensaje_recibido`,
        `mensaje_enviado`, `id_wa`, `timestamp_wa`,
        `telefono_wa`,id_servicio 
        FROM `registro` 
        where id>0   
        ";

        $sql .= "AND DATE(fecha_hora) = '" . $this->db->real_escape_string($filtroFecha) . "'";

        if ($filtroTipoMensaje !== '') {
            if ($filtroTipoMensaje=="Alertas") {
                $sql .="AND tipo ='Alerta'";
            } else if ($filtroTipoMensaje=="ChatBot") {
                $sql .="AND CHAR_LENGTH(mensaje_enviado) > 2 ";
            } else if ($filtroTipoMensaje=="Cotizaciones") {
                $sql .="AND mensaje_enviado like '%Cotizaci�n registrada%'";
            } else if ($filtroTipoMensaje=="cotizaMinima") {
                $sql .="AND mensaje_enviado like '%El valor estimado%'";
            }
        } else {
            $sql .="AND CHAR_LENGTH(mensaje_enviado) > 2 ";
        }

        $sql .= "ORDER BY fecha_hora ASC";

        $logPath = __DIR__ . '/log_consultas.txt';
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function obtenerServiciosHechos(string $filtroFecha): array
    {
        $fecha = $this->dbMain->real_escape_string($filtroFecha);
        $sql = "SELECT 
                    s.idservicios AS id,
                    s.ser_fecharegistro AS fecha_hora,
                    '' AS mensaje_recibido,
                    '' AS mensaje_enviado,
                    s.idservicios AS id_wa,
                    '' AS timestamp_wa,
                    s.cli_telefono AS telefono_wa
                    
                FROM serviciosdia s
                INNER JOIN guias g ON g.gui_idservicio = s.idservicios
                WHERE g.gui_usucreado = 'whatsapp'
                AND DATE(s.ser_fecharegistro) = '$fecha'
                ORDER BY s.ser_fecharegistro ASC";

        $logPath = __DIR__ . '/log_consultas.txt';
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL_SERVICIOS_HECHOS: $sql\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);

        $result = $this->dbMain->query($sql);
        if (!$result) {
            file_put_contents(
                $logPath,
                "[" . date("Y-m-d H:i:s") . "] ERROR_SERVICIOS_HECHOS: " . $this->dbMain->error . "\n",
                FILE_APPEND
            );
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'id' => $row['id'] ?? '',
                'fecha_hora' => $row['fecha_hora'] ?? '',
                'mensaje_recibido' => $row['mensaje_recibido'] ?? '',
                'mensaje_enviado' => $row['mensaje_enviado'] ?? '',
                'id_wa' => $row['id_wa'] ?? '',
                'timestamp_wa' => $row['timestamp_wa'] ?? '',
                'telefono_wa' => $row['telefono_wa'] ?? '',
                'id_servicio' => $row['id_servicio'] ?? '',
            ];
        }

        file_put_contents(
            $logPath,
            "[" . date("Y-m-d H:i:s") . "] FILAS_SERVICIOS_HECHOS: " . count($rows) . "\n",
            FILE_APPEND
        );

        return $rows;
    }

    public function obtenerRoles() {
        $sql = "SELECT idroles, rol_nombre FROM roles ORDER BY rol_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function actualizarCampo($id, $campo, $valor) {
        $permitidos = ['usu_filtro', 'usu_ver_nomina', 'usu_estado'];
        if (!in_array($campo, $permitidos)) return;

        $sql = "UPDATE usuarios SET $campo = ? WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $valor, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function eliminarUsuario($id) {
        $sql = "DELETE FROM usuarios WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function yaSeEnvioMensajeTipo37($telefono, $fecha = '')
    {
        date_default_timezone_set('America/Bogota');

        if ($fecha == '') {
            $fecha = date('Y-m-d');
        }

        $telefono = $this->db->real_escape_string($telefono);
        $fecha    = $this->db->real_escape_string($fecha);

        $sql = "
            SELECT id
            FROM registro
            WHERE telefono_wa = '$telefono'
            AND id_servicio = 37
            LIMIT 1
        ";

        $logPath = __DIR__ . '/log_consultas.txt';
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);

        $result = $this->db->query($sql);

        return ($result && $result->num_rows > 0);
    }
}
