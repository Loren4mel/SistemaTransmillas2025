<?php
require_once "../config/database.php";

class ValidarGuiaModel{
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function obtenerSerProgramados($filtroFecha = '', $filtroCiudad = '') {

        // Establecer zona horaria de Bogotá
        date_default_timezone_set('America/Bogota');
        // Si no se pasa una fecha, usa la fecha actual
        
        $conde2='';
        $hoy= date("Y-m-d");

        if ($filtroCiudad != '') {
            $conde2=$filtroCiudad;
        }
        

        $sql = "SELECT s.idservicios,
        s.ser_consecutivo,
        s.ser_tipopaquete,
        s.ser_piezas,
        p.numeropieza,
        s.ser_fechaguia, 
        s.ser_paquetedescripcion, 
        s.cli_idciudad,
        p.transporta,
        s.ser_direccioncontacto AS ser_direccioncontacto, 
        CASE WHEN pl.numeroguia IS NULL THEN 0 ELSE 1 END AS tiene_piezas_llegadas,
        CASE WHEN spa.seg_idservicio IS NULL THEN 0 ELSE 1 END AS esta_preasignada
        FROM serviciosdia s 
        INNER JOIN piezasguia p ON s.ser_consecutivo = p.numeroguia 
        LEFT JOIN (
            SELECT DISTINCT numeroguia
            FROM piezasguia
            WHERE guiallega = 1
        ) pl ON pl.numeroguia = s.ser_consecutivo
        LEFT JOIN (
            SELECT DISTINCT seg_idservicio
            FROM seguimientoruta
            WHERE seg_tipo = 'Entrega'
            AND seg_estado = 'Pre-asignada'
        ) spa ON spa.seg_idservicio = s.idservicios
        WHERE s.ser_estado IN ('6','7') 
        AND p.guiallega = 0 
        $conde2 
        ORDER BY s.ser_fechaguia DESC, s.ser_consecutivo ASC, p.numeropieza ASC";

        

        //✅ Guardar consulta en log para depuración
        $logPath = __DIR__ . '/log_obtenerSerProgramados.txt'; // puedes cambiar el nombre/ruta si quieres
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        // file_put_contents($logPath, $logMessage, FILE_APPEND);
        
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerRoles() {
        $sql = "SELECT idroles, rol_nombre FROM roles ORDER BY rol_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }



    public function obtenerCiudades($conde='') {


        $sql = "SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $conde ";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function sedes($idsede) {
        $sql = "SELECT idciudades, ciu_nombre 
                FROM ciudades 
                WHERE inner_sedes = '$idsede' 
                AND inner_estados = 1";

        $result = $this->db->query($sql);

        $valor = "(";
        $va = 0;

        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $valor .= $row[0] . ",";
                $va++;
            }
            $result->free();
        }

        if ($va > 0) {
            $valor = rtrim($valor, ",") . ")";
        } else {
            $valor = 0;
        }

        return $valor;
    }
    public function obtenerOperadores($ciudad="") {
        $cond="";
        if ($ciudad!="") {
            $cond="and `usu_idsede`='$ciudad'"; 
        }
        $sql = "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) $cond AND roles_idroles IN (2,3,5,8) ";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function obtenerOperadoresTrabajandoHoy($ciudad="") {
        $cond="";
        if ($ciudad!="") {
            $ciudad = $this->db->real_escape_string($ciudad);
            $cond="and u.usu_idsede='$ciudad'"; 
        }

        $sql = "SELECT DISTINCT u.idusuarios, u.usu_nombre
                FROM usuarios u
                INNER JOIN seguimiento_user s ON s.seg_idusuario = u.idusuarios
                WHERE (u.usu_estado=1 or u.usu_filtro=1)
                $cond
                AND u.roles_idroles IN (2,3,5,8)
                AND s.seg_motivo = 'Ingreso'
                AND DATE(s.seg_fechaingreso) = CURDATE()
                AND (s.seg_fechafinalizo IS NULL OR s.seg_fechafinalizo = '')
                ORDER BY u.usu_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function preAsignarGuias($operador, $guias, $idUsuario = 0, $nombreUsuario = '') {
        $operador = (int)$operador;
        $idUsuario = (int)$idUsuario;
        $nombreUsuario = trim((string)$nombreUsuario);
        $guiasLimpias = [];

        foreach ($guias as $guia) {
            $guia = trim((string)$guia);
            if ($guia !== '') {
                $guiasLimpias[] = "'" . $this->db->real_escape_string($guia) . "'";
            }
        }

        $guiasLimpias = array_values(array_unique($guiasLimpias));
        if ($operador <= 0 || count($guiasLimpias) === 0) {
            return ['success' => false, 'message' => 'Datos incompletos para pre-asignar.'];
        }

        date_default_timezone_set('America/Bogota');
        $fecha = date('Y-m-d H:i:s');
        $nombreUsuarioSql = $this->db->real_escape_string($nombreUsuario);
        $listaGuias = implode(',', $guiasLimpias);

        $sqlServicios = "SELECT idservicios, ser_consecutivo, ser_direccioncontacto, ser_fechaguia
                         FROM servicios
                         WHERE ser_consecutivo IN ($listaGuias)";
        $resultServicios = $this->db->query($sqlServicios);

        if (!$resultServicios || $resultServicios->num_rows === 0) {
            return ['success' => false, 'message' => 'No se encontraron guias para pre-asignar.'];
        }

        $servicios = [];
        $idsServicios = [];
        while ($row = $resultServicios->fetch_assoc()) {
            $servicios[] = $row;
            $idsServicios[] = (int)$row['idservicios'];
        }

        $idsServicios = array_values(array_unique($idsServicios));
        $idsSql = implode(',', $idsServicios);

        $this->db->begin_transaction();

        try {
            $sqlCuentas = "UPDATE cuentaspromotor
                           SET cue_idoperentrega = $operador,
                               cue_fecha = '$fecha'
                           WHERE cue_idservicio IN ($idsSql)";
            if (!$this->db->query($sqlCuentas)) {
                throw new Exception($this->db->error);
            }

            $sqlServiciosUpdate = "UPDATE servicios
                                   SET ser_idusuarioguia = $operador,
                                       ser_idusuarioregistro = $idUsuario,
                                       ser_visto = 0
                                   WHERE idservicios IN ($idsSql)";
            if (!$this->db->query($sqlServiciosUpdate)) {
                throw new Exception($this->db->error);
            }

            $sqlGuias = "UPDATE guias
                         SET gui_encomienda = '$nombreUsuarioSql',
                             gui_fechaencomienda = '$fecha'
                         WHERE gui_idservicio IN ($idsSql)";
            if (!$this->db->query($sqlGuias)) {
                throw new Exception($this->db->error);
            }

            $stmtSeguimiento = $this->db->prepare(
                "INSERT INTO seguimientoruta
                    (seg_fecha, seg_idservicio, seg_direccion, seg_tipo, seg_estado, seg_idusuario, seg_guia, seg_descripcion)
                 VALUES (?, ?, ?, 'Entrega', 'Pre-asignada', ?, ?, ?)"
            );

            if (!$stmtSeguimiento) {
                throw new Exception($this->db->error);
            }

            foreach ($servicios as $servicio) {
                $idServicio = (int)$servicio['idservicios'];
                $direccion = 'Entrega ' . str_replace('&', ' ', (string)$servicio['ser_direccioncontacto']);
                $guia = (string)$servicio['ser_consecutivo'];
                $descripcion = 'Pre-asignada desde validacion de guias|ser_fechaguia_anterior=' . (string)($servicio['ser_fechaguia'] ?? '');
                $stmtSeguimiento->bind_param('sisiss', $fecha, $idServicio, $direccion, $operador, $guia, $descripcion);

                if (!$stmtSeguimiento->execute()) {
                    throw new Exception($stmtSeguimiento->error);
                }
            }

            $stmtSeguimiento->close();
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Guias pre-asignadas correctamente.',
                'actualizadas' => count($idsServicios)
            ];
        } catch (Exception $e) {
            $this->db->rollback();

            return [
                'success' => false,
                'message' => 'No fue posible pre-asignar las guias.',
                'error' => $e->getMessage()
            ];
        }
    }

    public function deshacerPreAsignacionGuias($guias) {
        $guiasLimpias = [];

        foreach ($guias as $guia) {
            $guia = trim((string)$guia);
            if ($guia !== '') {
                $guiasLimpias[] = "'" . $this->db->real_escape_string($guia) . "'";
            }
        }

        $guiasLimpias = array_values(array_unique($guiasLimpias));
        if (count($guiasLimpias) === 0) {
            return ['success' => false, 'message' => 'Seleccione al menos una guia pre-asignada.'];
        }

        $listaGuias = implode(',', $guiasLimpias);
        $sqlServicios = "SELECT s.idservicios, sr.seg_descripcion
                         FROM servicios s
                         INNER JOIN seguimientoruta sr
                            ON sr.seg_idservicio = s.idservicios
                            AND sr.seg_tipo = 'Entrega'
                            AND sr.seg_estado = 'Pre-asignada'
                         WHERE s.ser_consecutivo IN ($listaGuias)
                         GROUP BY s.idservicios, sr.seg_descripcion";
        $resultServicios = $this->db->query($sqlServicios);

        if (!$resultServicios || $resultServicios->num_rows === 0) {
            return ['success' => false, 'message' => 'No se encontraron guias pre-asignadas para deshacer.'];
        }

        $idsServicios = [];
        $fechasAnteriores = [];
        while ($row = $resultServicios->fetch_assoc()) {
            $idServicio = (int)$row['idservicios'];
            $idsServicios[] = $idServicio;

            if (preg_match('/ser_fechaguia_anterior=([^|]*)/', (string)$row['seg_descripcion'], $matches)) {
                $fechasAnteriores[$idServicio] = $matches[1];
            }
        }

        $idsSql = implode(',', array_values(array_unique($idsServicios)));
        date_default_timezone_set('America/Bogota');
        $fecha = date('Y-m-d H:i:s');
        $fechaDia = date('Y-m-d');

        $this->db->begin_transaction();

        try {
            $sqlCuentas = "UPDATE cuentaspromotor
                           SET cue_idoperentrega = 0
                           WHERE cue_idservicio IN ($idsSql)";
            if (!$this->db->query($sqlCuentas)) {
                throw new Exception($this->db->error);
            }

            $sqlServiciosUpdate = "UPDATE servicios
                                   SET ser_idusuarioguia = 0,
                                       ser_visto = 0
                                   WHERE idservicios IN ($idsSql)";
            if (!$this->db->query($sqlServiciosUpdate)) {
                throw new Exception($this->db->error);
            }

            $stmtFechaAnterior = $this->db->prepare("UPDATE servicios SET ser_fechaguia=? WHERE idservicios=?");
            if (!$stmtFechaAnterior) {
                throw new Exception($this->db->error);
            }

            foreach ($fechasAnteriores as $idServicio => $fechaAnterior) {
                $stmtFechaAnterior->bind_param("si", $fechaAnterior, $idServicio);

                if (!$stmtFechaAnterior->execute()) {
                    throw new Exception($stmtFechaAnterior->error);
                }
            }

            $stmtFechaAnterior->close();

            $sqlGuias = "UPDATE guias
                         SET gui_encomienda = '',
                             gui_fechaencomienda = ''
                         WHERE gui_idservicio IN ($idsSql)";
            if (!$this->db->query($sqlGuias)) {
                throw new Exception($this->db->error);
            }

            $sqlSeguimiento = "UPDATE seguimientoruta
                               SET seg_estado = 'Pre-asignacion cancelada',
                                   seg_fechafinalizo = '$fecha',
                                   seg_descripcion = 'Pre-asignacion cancelada desde validacion de guias'
                               WHERE seg_idservicio IN ($idsSql)
                               AND seg_tipo = 'Entrega'
                               AND seg_estado = 'Pre-asignada'";
            if (!$this->db->query($sqlSeguimiento)) {
                throw new Exception($this->db->error);
            }

            $sqlOrden = "DELETE FROM ord_recoentregas
                         WHERE orden_idservicio IN ($idsSql)
                         AND orden_fechadiaejecucion = '$fechaDia'
                         AND ord_tipo = 'Entrega'";
            if (!$this->db->query($sqlOrden)) {
                throw new Exception($this->db->error);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Pre-asignacion deshecha correctamente.',
                'actualizadas' => count($idsServicios)
            ];
        } catch (Exception $e) {
            $this->db->rollback();

            return [
                'success' => false,
                'message' => 'No fue posible deshacer la pre-asignacion.',
                'error' => $e->getMessage()
            ];
        }
    }
    public function obtenerCreditos() {
        $cond="";

        $sql = "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) AND roles_idroles IN (6) ";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function buscarServicio($id) {

        $sql = "SELECT  
            s.ser_peso,
            s.ser_valor,
            s.ser_pendientecobrar,
            s.ser_clasificacion,
            s.ser_volumen,
            s.ser_descripcion,
            s.ser_guiare,
            s.ser_ciudadentrega,
            s.ser_telefonocontacto,
            s.cli_telefono,
            s.cli_idciudad,
            i.idimagenguias,
            i.ima_ruta,
            i.ima_tipo,
            i.ima_fecha,
            s.ser_piezas,
            sv.ser_img_recog,
            sv.ser_img_entre,
            s.ser_idverificadopeso,
            s.idservicios
        FROM serviciosdia s
        LEFT JOIN imagenguias i 
            ON s.idservicios = i.ima_idservicio 
            AND i.ima_tipo = 'Recogida'
        LEFT JOIN servicios sv
            ON s.idservicios = sv.idservicios
        WHERE s.idservicios = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }

    
    public function buscarServicioConGuia($guia, $pieza) {
        $sql = "SELECT  
                    s.idservicios,
                    s.ser_img_recog,
                    s.ser_img_recog1,
                    p.guiallega,
                    s.ser_piezas,
                    (
                        SELECT COUNT(*) 
                        FROM piezasguia 
                        WHERE numeroguia = s.ser_consecutivo 
                        AND guiallega = 1
                    ) AS piezasEscaneadas
                FROM servicios s
                INNER JOIN piezasguia p 
                    ON s.ser_consecutivo = p.numeroguia
                WHERE s.ser_consecutivo = ? 
                AND p.numeropieza = ?";

        //✅ Guardar consulta en log para depuración
        $logPath = __DIR__ . '/log_BuscaConGuia.txt';
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        // file_put_contents($logPath, $logMessage, FILE_APPEND);

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $guia, $pieza); // seguridad contra inyección SQL
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }
    public function buscarAsignacion($guia) {
 

                $sql="SELECT 
                    s.idservicios, 
                    s.ser_consecutivo, 
                    s.ser_destinatario, 
                    u.usu_nombre,
                    s.ser_estado
                FROM serviciosdia s
                LEFT JOIN usuarios u 
                    ON s.ser_idusuarioguia = u.idusuarios
                WHERE s.ser_consecutivo = ?
                ";


        //✅ Guardar consulta en log para depuración
        $logPath = __DIR__ . '/log_BuscaConGuia.txt';
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL aqui: $sql\n";
        // file_put_contents($logPath, $logMessage, FILE_APPEND);

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $guia); // seguridad contra inyección SQL
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }


    

    public function enviarAlertaWhat($telefono, $tipo) {
        $url = "https://bot.transmillas.com/whatsapp/Alertas/alertas.php";

        $data = array(
            "telefono" => $telefono,
            "tipo_alerta" => $tipo,

        );

        $data_json = json_encode($data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data_json,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer MiSuperToken123'
            ),
        ));

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            error_log("Error en alerta WhatsApp: $error");
        } else {
            $response_data = json_decode($response, true);
            error_log("WhatsApp enviado: " . print_r($response_data, true));
        }

        curl_close($curl);
    }
    private function registrarIncautacionGuia($idser, $guia, $pieza, $nombreArchivo, $id_usuario, $id_nombre, $datosIncautacion) {
        $fecha = trim((string)($datosIncautacion['fecha'] ?? ''));
        $idsede = (int)($datosIncautacion['idsede'] ?? 0);
        $comentario = trim((string)($datosIncautacion['comentario'] ?? ''));

        if ($fecha === '' || $idsede <= 0) {
            throw new Exception("Faltan datos de incautacion.");
        }

        $stmt = $this->db->prepare(
            "INSERT INTO incautaciones_guias
                (inc_idservicio, inc_guia, inc_pieza, inc_fecha, inc_idsede, inc_archivo, inc_comentario, inc_idusuario, inc_nombreusuario)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $idser = (int)$idser;
        $pieza = (int)$pieza;
        $id_usuario = (int)$id_usuario;
        $stmt->bind_param(
            "isisissis",
            $idser,
            $guia,
            $pieza,
            $fecha,
            $idsede,
            $nombreArchivo,
            $comentario,
            $id_usuario,
            $id_nombre
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $stmt->close();
    }

    public function actualizarServicio($descr, $llego, $piezasg, $guia, $id_usuario, $id_nombre,$idguia,$nombreArchivo,$pieza, $datosIncautacion = []) {
        date_default_timezone_set('America/Bogota');
        $fechatiempo = date("Y-m-d H:i:s");

        $inser = 1;
        if ($nombreArchivo === null) {
            $nombreArchivo = "";
        }
        // 1️⃣ Buscar idservicios desde la guía
        // $servicio = $this->buscarServicioPorGuia($guia);
        // if (!$servicio || !isset($servicio['idservicios'])) {
        //     // Si no existe el servicio relacionado con la guía
        //     return [
        //         "success" => false,
        //         "message" => "No se encontró un servicio asociado a la guía $guia"
        //     ];
        // }

        $idser = $idguia;

        // 2️⃣ Definir estado según valor de $llego
        switch ($llego) {
            case 'SI':          $estadog = 8;  break;
            case 'NO':          $estadog = 12; break;
            case 'Incompleto':  $estadog = 13; break;
            case 'Perdida':     $estadog = 16; break;
            case 'Incautada':   $estadog = 17; break;
            default:            $estadog = 0;  break;
        }

        try {
            if ($llego === 'Incautada') {
                $this->registrarIncautacionGuia($idser, $guia, $pieza, $nombreArchivo, $id_usuario, $id_nombre, $datosIncautacion);
            }

            // 3️⃣ Si hay más de una pieza
            if ($piezasg > 1) {
                // Marcar piezas que llegaron
                $stmt = $this->db->prepare("UPDATE piezasguia SET guiallega=1, img_pieza_llega=?, desc_valida_guia_llega=? WHERE numeroguia=? and numeropieza =?");
                $stmt->bind_param("sssi", $nombreArchivo,$descr,$guia,$pieza);
                $stmt->execute();

                // Contar piezas ya marcadas
                $stmt = $this->db->prepare("SELECT COUNT(numeropieza) as total FROM piezasguia WHERE numeroguia=? AND guiallega=1");
                $stmt->bind_param("s", $guia);
                $stmt->execute();
                $res = $stmt->get_result();
                $rw2 = $res ? $res->fetch_assoc() : ['total' => 0];

                if ($rw2['total'] != $piezasg) {
                    $inser = 0;
                    // Actualizar solo fecha en servicios
                    $stmt = $this->db->prepare("UPDATE servicios SET ser_fechaguia=? WHERE idservicios=?");
                    $stmt->bind_param("si", $fechatiempo, $idser);
                    $stmt->execute();
                }
            } else {
                // Una sola pieza → marcar llegada
                $stmt = $this->db->prepare("UPDATE piezasguia SET guiallega=1, img_pieza_llega=?, desc_valida_guia_llega=? WHERE numeroguia=?");
                $stmt->bind_param("sss", $nombreArchivo,$descr,$guia);
                $stmt->execute();
            }

            // 4️⃣ Si todas llegaron, actualizar en cascada
            if ($inser == 1) {
                $estadoFinal = $estadog;
                $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM seguimientoruta WHERE seg_idservicio=? AND seg_tipo='Entrega' AND seg_estado='Pre-asignada'");
                $stmt->bind_param("i", $idser);
                $stmt->execute();
                $res = $stmt->get_result();
                $preAsignada = $res ? $res->fetch_assoc() : ['total' => 0];

                if ($llego === 'SI' && (int)$preAsignada['total'] > 0) {
                    $estadoFinal = 9;
                }

                // cuentaspromotor
                $stmt = $this->db->prepare("UPDATE cuentaspromotor SET cue_fecha=?, cue_estado=? WHERE cue_idservicio=?");
                $stmt->bind_param("sii", $fechatiempo, $estadoFinal, $idser);
                $stmt->execute();

                // servicios
                $stmt = $this->db->prepare("UPDATE servicios SET ser_idusuarioregistro=?, ser_fechaguia=?, ser_estado=?, ser_llego=? WHERE idservicios=?");
                $stmt->bind_param("isisi", $id_usuario, $fechatiempo, $estadoFinal, $llego, $idser);
                $stmt->execute();

                // guias
                $stmt = $this->db->prepare("UPDATE guias SET gui_validasede=?, gui_fechavalidasede=? WHERE gui_idservicio=?");
                $stmt->bind_param("ssi", $id_nombre, $fechatiempo, $idser);
                $stmt->execute();

                // Si la guia estaba pre-asignada, al validar la ultima pieza pasa al flujo normal de entrega.
                $stmt = $this->db->prepare("UPDATE seguimientoruta SET seg_estado='Asignada' WHERE seg_idservicio=? AND seg_tipo='Entrega' AND seg_estado='Pre-asignada'");
                $stmt->bind_param("i", $idser);
                $stmt->execute();
            }

            return [
                "success" => true,
                "message" => $inser == 1 ? "Servicio escaneada completo con exito" : "Pieza Escaneada con exito"
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error de escaneo: " . $e->getMessage()
            ];
        }
    }


    public function buscarValidadas($filtroFecha = '', $filtroCiudad = '') {

        // Establecer zona horaria de Bogotá
        date_default_timezone_set('America/Bogota');
        // Si no se pasa una fecha, usa la fecha actual
        
        $conde2='';
        $hoy= date("Y-m-d");
        $conde = "and ser_fechaguia like '$hoy%' ";
        if ($filtroCiudad != '') {
            $conde2=$filtroCiudad;
        }
        
        $sql = "SELECT 
        idservicios, 
        ser_consecutivo,
        ser_tipopaquete, 
        ser_piezas,
        numeropieza
		FROM serviciosdia 
        inner join piezasguia on ser_consecutivo=numeroguia  
        where ser_estado in ('8') 
        and guiallega=1 
        $conde2 $conde
        ORDER BY ser_fechaguia desc ";

        //✅ Guardar consulta en log para depuración
        $logPath = __DIR__ . '/log_obtenerSerProgramados.txt'; // puedes cambiar el nombre/ruta si quieres
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        // file_put_contents($logPath, $logMessage, FILE_APPEND);
        
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


// funcion para traer guias por pieza que no se escanearon 
    public function registrarEscaneo($guia, $pieza, $ciudado, $idser, $tipoVehiculo, $id_nombre, $piezasg, $id_usuario) {
    date_default_timezone_set('America/Bogota');
    $fechatiempo = date("Y-m-d H:i:s");

    $inser = 1;
    $idpieza = null;

    try {
        // 🚚 Verificar tipo de vehículo
        if ($tipoVehiculo == "Bus" || $tipoVehiculo == "Jurgon") {
            if ($piezasg > 1) {
                // 1️⃣ Insertar pieza escaneada
                $stmt = $this->db->prepare(
                    "INSERT INTO piezasguia (numeroguia, numeropieza, quien_escanea, fecha_escanea) 
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("siss", $guia, $pieza, $id_nombre, $fechatiempo);
                $stmt->execute();
                $idpieza = $this->db->insert_id;

                if (!$idpieza) {
                    return ["success" => false, "message" => "Error al insertar en piezasguia"];
                }

                // 2️⃣ Verificar si ya se escanearon todas
                $stmt = $this->db->prepare(
                    "SELECT COUNT(numeropieza) as total FROM piezasguia WHERE numeroguia=?"
                );
                $stmt->bind_param("s", $guia);
                $stmt->execute();
                $res = $stmt->get_result();
                $rw2 = $res->fetch_assoc();

                if ($rw2['total'] != $piezasg) {
                    $inser = 0;

                    // Solo actualizar fecha en servicios
                    $stmt = $this->db->prepare(
                        "UPDATE servicios SET ser_fechaguia=? WHERE idservicios=?"
                    );
                    $stmt->bind_param("si", $fechatiempo, $idser);
                    $stmt->execute();
                }

                // 3️⃣ Si todas llegaron → actualizar en cascada
                if ($inser == 1) {
                    // cuentaspromotor
                    $stmt = $this->db->prepare(
                        "UPDATE cuentaspromotor 
                         SET cue_fecha=?, cue_estado=7 
                         WHERE cue_idservicio=?"
                    );
                    $stmt->bind_param("si", $fechatiempo, $idser);
                    $stmt->execute();

                    // servicios
                    $stmt = $this->db->prepare(
                        "UPDATE servicios 
                         SET ser_idusuarioregistro=?, ser_fechaguia=?, ser_estado=7 
                         WHERE idservicios=?"
                    );
                    $stmt->bind_param("isi", $id_usuario, $fechatiempo, $idser);
                    $stmt->execute();

                    // guias
                    $stmt = $this->db->prepare(
                        "UPDATE guias 
                         SET gui_ensede=?, gui_fechaensede=? 
                         WHERE gui_idservicio=?"
                    );
                    $stmt->bind_param("ssi", $id_nombre, $fechatiempo, $idser);
                    $stmt->execute();
                }
            } else {
                // 📦 Solo una pieza → insertar directo
                $stmt = $this->db->prepare(
                    "INSERT INTO piezasguia (numeroguia, numeropieza, quien_escanea, fecha_escanea) 
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("siss", $guia, $pieza, $id_nombre, $fechatiempo);
                $stmt->execute();
                $idpieza = $this->db->insert_id;

                if (!$idpieza) {
                    return ["success" => false, "message" => "Error al insertar pieza única"];
                }
            }

            // 🚛 Actualizar datos de transporte en piezasguia
            $stmt = $this->db->prepare(
                "UPDATE piezasguia 
                 SET transporta=?, quien_escanea=?, fecha_escanea=? 
                 WHERE idpiezasguia=?"
            );
            $stmt->bind_param("sssi", $tipoVehiculo, $id_nombre, $fechatiempo, $idpieza);
            $stmt->execute();
        }

        // 🚛 Actualizar transporte en servicios
        $stmt = $this->db->prepare(
            "UPDATE servicios 
             SET ser_transporta=?, ser_quien_escanea=?, ser_fecha_escanea=? 
             WHERE idservicios=?"
        );
        $stmt->bind_param("sssi", $tipoVehiculo, $id_nombre, $fechatiempo, $idser);
        $stmt->execute();

        return ["success" => true, "message" => "OK"];

    } catch (Exception $e) {
        return ["success" => false, "message" => "Error: " . $e->getMessage()];
    }
}


public function validarGuiaYPiezas($guia, $id_usuario, $id_nombre, $tipoVehiculo = null) {
    // ✅ 1. Buscar la guía
    $sql = "SELECT ser_piezas, idservicios, ser_estado, ser_desvaliguia, 
                   ser_ciudadentrega, ser_idverificadopeso, ciu_nombre
            FROM servicios 
            INNER JOIN ciudades ON idciudades = ser_ciudadentrega 
            WHERE ser_consecutivo = '$guia'";

    // Log para depuración
    $logPath = __DIR__ . '/log_validarGuiaYPiezas.txt';
    $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
    // file_put_contents($logPath, $logMessage, FILE_APPEND);

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $rw1 = $stmt->get_result()->fetch_assoc();

    if (!$rw1) {
        return ["status" => false, "msg" => "❌ El número de guía no existe, verifique."];
    }

    $idser     = $rw1["idservicios"];
    $piezasg   = $rw1["ser_piezas"];
    $estado    = $rw1["ser_estado"];
    $ciudad    = $rw1["ciu_nombre"];
    $pesoOk    = $rw1["ser_idverificadopeso"];

    // ✅ 2. Recorrer piezas y validar cada una
    for ($pieza = 1; $pieza <= $piezasg; $pieza++) {
        $sql2 = "SELECT idpiezasguia 
                 FROM piezasguia 
                 WHERE numeroguia = '$guia' AND numeropieza = '$pieza'";

        file_put_contents($logPath, "[" . date("Y-m-d H:i:s") . "] SQL: $sql2\n", FILE_APPEND);

        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute();
        $rw2 = $stmt2->get_result()->fetch_assoc();

        // 👉 Si la pieza NO está escaneada, la registramos
        if (!$rw2) {
            if ($estado == 6 && $pesoOk == 1) {
                return $this->registrarEscaneo($guia, $pieza, $ciudad, $idser, $tipoVehiculo, $id_nombre, $piezasg, $id_usuario);
            } elseif ($estado == 7) {
                return ["status" => false, "msg" => "⚠️ La guía ya fue enviada. Verifique la guía."];
            } else {
                return ["status" => false, "msg" => "❌ La guía no está en estado de envío, verifique."];
            }
        }
    }

    // ✅ Si todas las piezas ya estaban escaneadas
    return ["status" => false, "msg" => "❌ Todas las piezas de la guía ya fueron escaneadas."];
}

public function buscarRemesas( $filtroCiudad = '', $filtroOperador= '') {
    date_default_timezone_set('America/Bogota');

    $conde1 = '';
    $conde2 = '';
    $hoy = date("Y-m-d"); // Fecha actual
    $primerDia = date("Y-m-01"); // Primer día del mes actual

    $conde = "and date(gas_fecharegistro)>='$primerDia'               
            and date(gas_fecharegistro)<='$hoy'";
    if($filtroCiudad==''){   $conde2=""; } 
    else{
        $conde2="and (gas_idciudaddes=$filtroCiudad)";
    }
    if ($filtroOperador == '') {   
        $conde3 = ""; 
    } else {
        $conde3 = "and gas_iduserrecoge=$filtroOperador";
    }

    $sql = "SELECT g.idgastos, 
                   g.gas_fecharegistro, 
                   u.usu_nombre AS usuario_registro, 
                   g.gas_idciudadori, 
                   s.sed_nombre AS sede_destino, 
                   g.gas_empresa, 
                   g.gas_bus,
                   g.gas_telconductor,
                   g.gas_pagar,
                   g.gas_iduserremesa, 
                   g.gas_nomremesa,
                   g.gas_descripcion,
                   g.gas_peso,
                   g.gas_piezas,
                   g.gas_valor,
                   g.gas_usucom,
                   g.gas_cantcom,
                   g.gas_feccom,
                   g.gas_idciudaddes,
                   g.gas_iduserrecoge,
                   g.gas_recogio,
                   g.gas_entrego,
                   g.gas_fecrecogida, 
                   g.gas_descrecogio,
                   g.gas_nomvalida, 
                   g.gas_fechavalida,

                   so.sed_nombre AS sede_origen,
                   ur.usu_nombre AS usuario_recoge

            FROM gastos g
            INNER JOIN usuarios u ON g.gas_idusuario = u.idusuarios
            INNER JOIN sedes s ON g.gas_idciudaddes = s.idsedes
            LEFT JOIN sedes so ON g.gas_idciudadori = so.idsedes
            LEFT JOIN usuarios ur ON g.gas_iduserrecoge = ur.idusuarios
            WHERE idgastos>0 $conde2
            AND g.gas_nomvalida = ''
            $conde
            ORDER BY idgastos asc";
        //✅ Guardar consulta en log para depuración
        $logPath = __DIR__ . '/log_buscarRemesas.txt'; // puedes cambiar el nombre/ruta si quieres
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    $result = $this->db->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
public function validarRemesa($id_param, $descripcion, $id_nombre) {
        // Configurar zona horaria a Bogotá
        date_default_timezone_set('America/Bogota');
        $fechatiempo = date('Y-m-d H:i:s'); // formato datetime
        // Armamos la consulta
        $sql = "UPDATE `gastos` 
                SET `gas_descrecogio` = '$descripcion',
                    `gas_nomvalida`  = '$id_nombre',
                    `gas_fechavalida`= '$fechatiempo'
                WHERE `idgastos` = '$id_param'";
        //✅ valida remesa
        $logPath = __DIR__ . '/log_consultasRemesas.txt'; // puedes cambiar el nombre/ruta si quieres
        $logMessage = "[" . date("Y-m-d H:i:s") . "] SQL: $sql\n";
        // file_put_contents($logPath, $logMessage, FILE_APPEND);
        // Ejecutamos
        $result = $this->db->query($sql);
        $result = true;

        // Retornamos true o false según el resultado
        return $result ? true : false;
    }

}
