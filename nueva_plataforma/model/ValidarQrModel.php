<?php
require_once "../config/database.php";

class ValidarQrModel{
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

public function buscarServicioPorGuia($guia) {

        $sql = "SELECT   
            s.ser_ciudadentrega,
            ce.ciu_nombre AS nombre_ciudad_entrega,
            s.ser_telefonocontacto,
            s.cli_telefono,
            s.cli_idciudad,
            cc.ciu_nombre AS nombre_ciudad_cliente,
            s.ser_piezas,     
            sv.ser_manifiesto,
            s.cli_nombre,
            s.ser_destinatario,
            s.ser_direccioncontacto,
            s.cli_direccion
        FROM serviciosdia s   
        LEFT JOIN servicios sv
            ON s.idservicios = sv.idservicios
        LEFT JOIN ciudades ce
            ON s.ser_ciudadentrega = ce.idciudades
        LEFT JOIN ciudades cc
            ON s.cli_idciudad = cc.idciudades    
        WHERE s.ser_guiare = ?";


        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $guia);
        $stmt->execute();
        $result = $stmt->get_result();
        //✅ Guardar consulta en log para depuración
        $logPath = __DIR__ . '/log_buscarGuiaQr.txt'; // puedes cambiar el nombre/ruta si quieres
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);
        return $result ? $result->fetch_assoc() : null;
    }

}
