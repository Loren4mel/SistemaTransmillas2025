<?php

require_once "../config/database.php";

class EntregarModel {

    private $db;

    public function __construct() {
        $this->db = (new Database())->connect(); // nueva conexión
    }

    /* =====================================================
       UTILIDAD PARA EJECUTAR CONSULTAS Y DEVOLVER RESULTADOS
       ===================================================== */
    private function query($sql) {
        return $this->db->query($sql);
    }

    private function escape($v) {
        return $this->db->real_escape_string($v);
    }

    /* =====================================================
       VALIDAR EXISTENCIA DE FIRMA
       ===================================================== */
    private function existeFirmaEntrega($idservicio) {
        $id = (int)$idservicio;

        $sql = "SELECT * FROM firma_clientes 
                WHERE tipo_firma='Entrega' AND id_guia='$id' 
                LIMIT 1";

        $res = $this->query($sql);
        return ($res && $res->num_rows > 0);
    }

    

    /* =====================================================
       GUARDAR FOTO (Entrega o No Entregado)
       ===================================================== */
    private function guardarImagen($file, $carpeta) {

        // Validar si existe archivo
        if (!isset($file["tmp_name"]) || $file["error"] !== UPLOAD_ERR_OK) {
            return "";
        }

        // Validar si se subió
        if (!is_uploaded_file($file["tmp_name"])) {
            return "";
        }

        // Validar MIME de forma segura
        $info = @getimagesize($file['tmp_name']);
        if (!$info) return "";

        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $imagen = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imagen = @imagecreatefrompng($file['tmp_name']);
                break;
            default:
                return "";
        }

        if (!$imagen) return "";

        // Crear carpeta si no existe
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $nombre = date("Y-m-d-H-i-s") . "-" . basename($file["name"]);
        $destino = $carpeta . $nombre;

        // Redimensionar
        $max = 1280;
        $w = imagesx($imagen);
        $h = imagesy($imagen);

        if ($w > $max || $h > $max) {
            $ratio = min($max / $w, $max / $h);
            $nw = intval($w * $ratio);
            $nh = intval($h * $ratio);

            $nuevo = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($nuevo, $imagen, 0, 0, 0, 0, $nw, $nh, $w, $h);

            $imagen = $nuevo;
        }

        imagejpeg($imagen, $destino, 70);
        imagedestroy($imagen);

        return $nombre;
    }

    public function logEntrega($msg) {
        $logFile = __DIR__ . "/logs_entrega.log";
        $fecha = date("Y-m-d H:i:s");
        file_put_contents($logFile, "[$fecha] $msg\n", FILE_APPEND);
    }
    /* =====================================================
       GUARDAR ENTREGA (ENTREGADO)
       ===================================================== */
    public function guardarEntrega($data, $files, $ctx = []) {
        date_default_timezone_set('America/Bogota');
        $this->logEntrega("=== INICIO guardarEntrega() ===");
        $this->logEntrega("POST recibido: " . json_encode($data));
        $this->logEntrega("FILES recibido: " . json_encode($files));

        /* ===============================
        VARIABLES DEL FORMULARIO
        =============================== */
        $idservicio  = (int)$data["idservicio"];
        $this->logEntrega("ID Servicio: $idservicio");

        $param30 = $data["param30"] ?? "0";
        $param10 = $data["param10"] ?? "";
        $param11 = $data["param11"] ?? "";
        $param12 = $data["param12"] ?? "0";
        $param19 = $data["param19"] ?? "0";
        $param20 = $data["param20"] ?? "0";
        $param21 = $data["param21"] ?? "";
        $param82 = $data["param82"] ?? "";
        $param83 = $data["param83"] ?? "";
        $param84 = $data["param84"] ?? "";
        $param85 = $data["param85"] ?? "";
        $param86 = $data["param86"] ?? "";

        $this->logEntrega("Variables recibidas: metodoPago=$param30, totalFinal=$param12, devolucion=$param19");

        /* ===============================
        CONTEXTO
        =============================== */
        $id_usuario   = $data["id_usuario"]     ?? 0;
        $id_sedes     = $data["id_sedes"]       ?? 0;
        $id_nombre    = $data["id_nombre"]      ?? "";
        $fechaactual  = $ctx["fechaactual"]    ?? date("Y-m-d");
        $fechatiempo  = $ctx["fechatiempo"]    ?? date("Y-m-d H:i:s");
        // $cambios      = $data["cambios"]        ?? "";
         $sqlcambio=$data["cambios"];
         $cambios= $sqlcambio[0];
        $param114     = $data["param114"]       ?? 0;

        $this->logEntrega("Contexto: usuario=$id_usuario, nombre=$id_nombre, fecha=$fechatiempo");

        /* ===============================
        VALIDAR FIRMA
        =============================== */
        if (!$this->existeFirmaEntrega($idservicio)) {
            $this->logEntrega("ERROR: No existe firma para el servicio $idservicio");
            return ["ok" => false, "msg" => "NO HAY FIRMA"];
        }
        elseif (empty($_POST['id_usuario'])) {
            // No viene o viene vacío
            return ["ok" => false, "msg" => "Error faltan datos recargue e intente nuevamente"];
        }

        $this->logEntrega("Firma validada correctamente.");

        /* ===============================
        PROCESAR MÉTODO PAGO
        =============================== */
        if ($param30 != "0") {
            $arr = explode("|", $param30);
            $tipopago = $arr[0] ?? 0;
            $cuenta   = $arr[1] ?? "";
            $namePago = $arr[2] ?? "";
            $tranf = ",cue_transferencia='$namePago' ";
            $this->logEntrega("Método pago: tipopago=$tipopago, cuenta=$cuenta, transferencia=$namePago");
        } else {
            $tipopago = 0;
            $cuenta = "";
            $tranf = "";
            $this->logEntrega("Método pago: CONTADO");
        }

        /* ===============================
        CALCULAR PORCENTAJES
        =============================== */
        $this->logEntrega("Calculando porcentajes...");

        $sqlPor = "SELECT por_porcentaje, por_porcentajeempresa 
                FROM porcentajespaquetes 
                WHERE por_idsede='{$data["param9"]}'
                    AND por_idsededestino='{$data["param22"]}'
                    AND por_tiposervicio='$param10'
                    AND '$param20'>=por_kilosgramosmin
                    AND '$param20'<=por_kilogramosmaximo
                    AND (por_idpaquete='$param21' OR por_idpaquete='')
                ORDER BY idporcentajespaquetes DESC LIMIT 1";

        $resPor = $this->query($sqlPor);

        if (!$resPor) {
            $this->logEntrega("ERROR SQL porcentajes: " . $this->error());
        }

        $rw5 = ($resPor ? $resPor->fetch_row() : [0,0]);

        $this->logEntrega("Porcentajes: empresa={$rw5[1]}, usuario={$rw5[0]}");

        $porcentaje = $rw5[0];
        $porcentajeempresa = $rw5[1];

        $valorporcentaje = ($param114 * $porcentaje) / 100;
        $valorporempresa = ($param114 * $porcentajeempresa) / 100;

        $cond0 = ",cue_porcentaje='$porcentaje',
                cue_porempresa='$porcentajeempresa',
                cue_valorporcantaje='$valorporcentaje',
                cue_valorporempresa='$valorporempresa'";

        /* ===============================
        GUARDAR FOTO ENTREGA
        =============================== */
        $this->logEntrega("Intentando guardar foto de entrega...");

        $fotoEntrega = "";
        if (isset($files["param87"]) && $files["param87"]["error"] === UPLOAD_ERR_OK) {
            $fotoEntrega = $this->guardarImagen($files["param87"], "./../../imgServicios/");
            $this->logEntrega("Foto guardada: $fotoEntrega");
        } else {
            $this->logEntrega("No llegó la foto de entrega.");
        }

        /* ===============================
        PROCESAR SEGÚN COBRAR
        =============================== */
        $porcobrar = $data["cambios"] ?? 0;
        $this->logEntrega("porcobrar = $porcobrar");

        if ($porcobrar == 0) {

            $this->logEntrega("Procesando como **NO POR COBRAR**...");

            /* ACTUALIZAR CUENTAS */
            $sql2 = "UPDATE cuentaspromotor
                    SET cue_idoperentrega='$id_usuario',
                        cue_fecha='$fechatiempo',
                        cue_estado='10'
                        $cond0
                        $tranf
                    WHERE cue_idservicio='$idservicio'";
            $this->query($sql2);
            $this->logEntrega("SQL2 ejecutado.");

            /* GUIA */
            $sql3 = "UPDATE guias
                    SET gui_userecomienda='$id_nombre',
                        gui_fechaentrega='$fechatiempo'
                    WHERE gui_idservicio='$idservicio'";
            $this->query($sql3);
            $this->logEntrega("SQL3 ejecutado.");

            /* DEVOLUCIÓN */
            if ($param19 >= 1) {
                $valDev = str_replace(".", "", $param19);
                $sql4 = "INSERT INTO abonosguias
                        (abo_fecha, abo_valor, abo_idservicio, abo_iduser, abo_idsede, abo_estado)
                        VALUES
                        ('$fechatiempo','$valDev','$idservicio','$id_usuario','$id_sedes','devolucion')";
                $this->query($sql4);
                $this->logEntrega("DEVOLUCIÓN registrada.");
            }

            /* SERVICIOS */
            $sql1 = "UPDATE servicios
                    SET ser_estentrega='ENTREGADO',
                        ser_fechafinal='$fechatiempo',
                        ser_fechaguia='$fechatiempo',
                        ser_estado='10',
                        ser_img_entre='$fotoEntrega'
                    WHERE idservicios='$idservicio'";
            $this->query($sql1);
            $this->logEntrega("SQL1 ejecutado.");

            /* IMG TRANSACCIÓN */
            if ($tipopago > 1 && ($cambios == "" or $cambios == 0)) {

                $this->logEntrega("Guardando imagen de transacción...");

                $imgTrans = $this->guardarImagen($files["img_transaccion"], "./../../img_transacciones/");

                $this->query("DELETE FROM pagoscuentas WHERE pag_idservicio='$idservicio'");
                $this->logEntrega("Pagos anteriores eliminados.");

                $sqlPay = "INSERT INTO pagoscuentas
                        (pag_tipopago,pag_cuenta,pag_valor,pag_idoperario,pag_idservicio,pag_guia,pag_estado,pag_fecha,pag_img_transaccion)
                        VALUES
                        ('$tipopago','$cuenta','$param12','$id_usuario','$idservicio','$param11','Al cobro','$fechatiempo','$imgTrans')";
                $this->query($sqlPay);
                $this->logEntrega("Pago registrado.");
            }else {
                $this->logEntrega("Error al guardarel metodo de pago y comprobante". $tipopago."> 1 && ".$cambios."==".$cambios." == 0");
                // return ["ok" => false, "msg" => "Error al guardarel metodo de pago y comprobante"];
            }

        } else {

            $this->logEntrega("Procesando como **POR COBRAR**...");

            if ($tipopago > 1) {
                $imgTrans = $this->guardarImagen($files["img_transaccion"], "./../../img_transacciones/");
                $this->logEntrega("Foto de transacción guardada: $imgTrans");

                $this->query("DELETE FROM pagoscuentas WHERE pag_idservicio='$idservicio'");
                $this->logEntrega("Pagos eliminados.");

                $sqlPay = "INSERT INTO pagoscuentas
                        (pag_tipopago,pag_cuenta,pag_valor,pag_idoperario,pag_idservicio,pag_guia,pag_estado,pag_fecha,pag_img_transaccion)
                        VALUES
                        ('$tipopago','$cuenta','$param12','$id_usuario','$idservicio','$param11','Pendiente X cobrar','$fechatiempo','$imgTrans')";
                $this->query($sqlPay);
                $this->logEntrega("Pago pendiente registrado.");
            }

            $this->query("UPDATE servicios SET ser_pendientecobrar=2 WHERE idservicios='$idservicio'");
            $this->logEntrega("Servicio marcado como pendiente por cobrar.");

            $this->query("UPDATE cuentaspromotor SET cue_pendientecobrar=2, cue_idoperpendiente='$id_usuario' $tranf WHERE cue_idservicio='$idservicio'");
            $this->logEntrega("Cuenta actualizada.");
        }

        /* ===============================
        SEGUIMIENTO
        =============================== */
        $sqlSeg = "UPDATE seguimientoruta
                SET seg_estado='completado',
                    seg_fechafinalizo='$fechatiempo',
                    seg_guia='$param11'
                WHERE seg_idservicio='$idservicio'
                    AND seg_tipo='Entrega'
                    AND seg_estado!='Cambioruta'
                    AND seg_fecha LIKE '%$fechaactual%'";
        $this->query($sqlSeg);
        $this->logEntrega("Seguimiento actualizado.");

        /* ===============================
        FIRMA
        =============================== */
        $sqlFirma = "UPDATE firma_clientes
                    SET nombre='$param82',
                        numero_documento='$param83',
                        correo_electronico='$param84',
                        telefono='$param85',
                        enviar_whatsapp='$param86'
                    WHERE tipo_firma='Entrega'
                    AND id_guia='$idservicio'";
        $this->query($sqlFirma);
        $this->logEntrega("Firma actualizada.");

        /* ===============================
        WHATSAPP
        =============================== */
        $sql12 = "SELECT idservicios,ser_estado,ser_telefonocontacto,ser_consecutivo,cli_telefono,ser_peso,ser_volumen,ser_valorseguro,ser_valor
                FROM serviciosdia
                WHERE idservicios='$idservicio'";

        $res12 = $this->query($sql12);
        $rw12 = ($res12 ? $res12->fetch_row() : null);

        if ($rw12) {
            $this->logEntrega("Datos WhatsApp encontrados. Enviando notificaciones...");
            $numguia = $rw12[3];
            $telefono = $rw12[2];
            $telefonoremi = $rw12[4];
            $numguiaEnviar=$rw12[3]."E";

            $link = $this->guardarLinkServicio($idservicio, "Entrega", $param11, $rw12[5], $rw12[6], $rw12[7], $rw12[8]);

            $this->enviarAlertaWhat($numguia, $telefono, 42, $idservicio, $numguiaEnviar);
            if ($telefonoremi != $param85) {
                $this->enviarAlertaWhat($numguia, $param85, 42, $idservicio, $numguiaEnviar);
            }
            $this->enviarAlertaWhat($numguia, $telefonoremi, 42, $idservicio, $numguiaEnviar);
        } else {
            $this->logEntrega("No se encontraron datos para WhatsApp.");
        }

        $this->logEntrega("=== FIN guardarEntrega() ===");

        return ["ok" => true];
    }


    /* =====================================================
       GUARDAR NO ENTREGADO
       ===================================================== */
public function logErrorLocal($msg) { 
    $logFile = __DIR__ . "/logs_noentregar.log"; 
    $fecha = date("Y-m-d H:i:s"); 
    file_put_contents($logFile, "[$fecha] $msg\n", FILE_APPEND);
 }
public function guardarNoEntregar($data, $files, $ctx = [])
{
    date_default_timezone_set('America/Bogota');
    // --- LOG INICIAL ---
    $this->logErrorLocal("=== INICIO guardarNoEntregar ===");

    try {

        // ===============================
        // VARIABLES
        // ===============================
        $this->logErrorLocal("Cargando variables iniciales...");

        $idservicio   = (int)$data["idservicio"];
        $motivo       = $this->escape($data["motivo"]);
        $this->logErrorLocal("idservicio=$idservicio, motivo=$motivo");

        $id_usuario   = $data["id_usuario"]     ?? 0;
        $id_nombre    = $data["id_nombre"]      ?? "";
        $nivel_acceso = $data["nivel_acceso"]   ?? 1;
        $fechaactual  = $ctx["fechaactual"]    ?? date("Y-m-d");
        $fechatiempo  = $ctx["fechatiempo"]    ?? date("Y-m-d H:i:s");

        $this->logErrorLocal("ctx: usuario=$id_usuario, nombre=$id_nombre, fecha=$fechatiempo");

        // ===============================
        // SUBIR FOTO
        // ===============================
        $this->logErrorLocal("Validando imagen...");

        if (!isset($files["foto_evidencia"])) {
            $this->logErrorLocal("foto_evidencia NO existe en \$_FILES");
        } else {
            $this->logErrorLocal("foto_evidencia existe: " . json_encode($files["foto_evidencia"]));
        }

        if (!isset($files["foto_evidencia"]) || $files["foto_evidencia"]["error"] !== UPLOAD_ERR_OK) {
            $this->logErrorLocal("Imagen inválida o con error.");
            $img_evidencia = "";
        } else {
            $this->logErrorLocal("Intentando guardar imagen en ./imgNoEntregas/ ...");
            $img_evidencia = $this->guardarImagen($files["foto_evidencia"], "./../../imgNoEntregas/");
            $this->logErrorLocal("Resultado guardarImagen: $img_evidencia");
        }

        // ===============================
        // ACTUALIZAR BASES
        // ===============================
        $porcobrar = $data["porcobrar"] ?? 0;
        $this->logErrorLocal("porcobrar=$porcobrar");

        if ($porcobrar == 0) {

            // ---------- SQL2 ----------
            $this->logErrorLocal("Ejecutando SQL2...");
            $sql2 = "UPDATE cuentaspromotor
                    SET cue_idoperentrega='0', 
                        cue_fecha='$fechatiempo',
                        cue_estado='11'
                    WHERE cue_idservicio='$idservicio'";
            $this->logErrorLocal("SQL2: $sql2");

            if (!$this->query($sql2)) {
                $this->logErrorLocal("ERROR SQL2: " . $this->db->error);
            } else {
                $this->logErrorLocal("SQL2 OK");
            }

            // ---------- SQL3 ----------
            $this->logErrorLocal("Ejecutando SQL3...");
            $sql3 = "UPDATE guias
                    SET gui_userecomienda='$id_nombre',
                        gui_fechaentrega='$fechatiempo'
                    WHERE gui_idservicio='$idservicio'";
            $this->logErrorLocal("SQL3: $sql3");

            if (!$this->query($sql3)) {
                $this->logErrorLocal("ERROR SQL3: " . $this->db->error);
            } else {
                $this->logErrorLocal("SQL3 OK");
            }

            // ---------- SQL1 ----------
            $this->logErrorLocal("Ejecutando SQL1...");
            $sql1 = "UPDATE servicios
                    SET ser_estentrega='NO ENTREGADO',
                        ser_fechafinal='$fechatiempo',
                        ser_fechaguia='$fechatiempo',
                        ser_descentrega='$motivo',
                        ser_estado='11',
                        ser_descllamada='No entregado',
                        ser_esatdollamando='',
                        ser_img_evidencia='$img_evidencia',
                        ser_fecha_evidencia='$fechatiempo'
                    WHERE idservicios='$idservicio'";
            $this->logErrorLocal("SQL1: $sql1");

            if (!$this->query($sql1)) {
                $this->logErrorLocal("ERROR SQL1: " . $this->db->error);
            } else {
                $this->logErrorLocal("SQL1 OK");
            }

        } else {
            $this->logErrorLocal("CASO POR COBRAR");
            // (Tu bloque de POR COBRAR lo dejo igual)
        }

        // ===============================
        // SEGUIMIENTO
        // ===============================
        $this->logErrorLocal("Ejecutando SQL SEGUIMIENTO...");
        $sqlSeg = "UPDATE seguimientoruta
                SET seg_estado='NO entregado',
                    seg_fechafinalizo='$fechatiempo',
                    seg_descripcion='No entregado'
                WHERE seg_idservicio='$idservicio'
                    AND seg_tipo='Entrega'
                    AND seg_estado!='Cambioruta'
                    AND seg_fecha LIKE '%$fechaactual%'";
        $this->logErrorLocal("SQL SEGUIMIENTO: $sqlSeg");

        if (!$this->query($sqlSeg)) {
            $this->logErrorLocal("ERROR SQL SEGUIMIENTO: " . $this->db->error);
        } else {
            $this->logErrorLocal("SQL SEGUIMIENTO OK");
        }

        // ===============================
        // CONSULTAR WHATSAPP
        // ===============================
        $this->logErrorLocal("Ejecutando SQL WHATSAPP...");
        $sql12 = "SELECT idservicios,ser_estado,ser_telefonocontacto,ser_consecutivo
                FROM servicios
                WHERE idservicios='$idservicio'";
        $this->logErrorLocal("SQL12: $sql12");

        $res12 = $this->query($sql12);

        if (!$res12) {
            $this->logErrorLocal("ERROR SQL12: " . $this->db->error);
        } else {
            $this->logErrorLocal("SQL12 OK, fetch_row...");
        }

        $rw12 = ($res12 ? @$res12->fetch_row() : null);

        if ($rw12) {
            $this->logErrorLocal("WhatsApp datos encontrados OK");
            $numguia  = $rw12[3];
            $telefono = $rw12[2];
            $this->logErrorLocal("numguia=$numguia, telefono=$telefono");

            try {
                $this->logErrorLocal("Intentando enviarAlertaWhat...");
                enviarAlertaWhat($numguia, $telefono, 5, $idservicio);
                $this->logErrorLocal("WhatsApp enviado correctamente");
            } catch (Throwable $e) {
                $this->logErrorLocal("ERROR enviarAlertaWhat: " . $e->getMessage());
            }

        } else {
            $this->logErrorLocal("No hay datos para WhatsApp");
        }

        $this->logErrorLocal("=== FIN SIN ERROR ===");
        return ["ok" => true];

    } catch (Throwable $e) {

        $this->logErrorLocal("EXCEPCIÓN FATAL: " . $e->getMessage());
        return ["ok" => false, "error" => $e->getMessage()];
    }
}
    /**
     * Buscar datos de un servicio y calcular totales igual que en el código viejo
     */
    public function buscarEntrega($idservicio) {
        $idservicio = (int)$idservicio;

        // 1) Traer datos base del servicio + guía (tu SELECT original)
        $sql = "SELECT 
                    s.ser_paquetedescripcion,
                    s.ser_valorprestamo,
                    s.ser_valorabono,
                    s.ser_valorseguro,
                    s.ser_clasificacion,
                    s.ser_ciudadentrega,
                    s.ser_devolverreci,
                    s.ser_piezas,
                    s.ser_peso,
                    s.ser_valor,
                    s.cli_idciudad,
                    s.ser_idusuarioguia,
                    s.ser_guiare,
                    s.ser_pendientecobrar,
                    s.ser_volumen,
                    g.gui_tiposervicio,
                    s.idservicios
                FROM serviciosdia s 
                INNER JOIN guias g ON s.idservicios = g.gui_idservicio
                WHERE s.idservicios = $idservicio
                LIMIT 1";

        $res = $this->db->query($sql);

        if (!$res || $res->num_rows === 0) {
            return null;
        }

        $rw = $res->fetch_row();
        // Índices según tu código original:
        // 0=ser_paquetedescripcion
        // 1=ser_valorprestamo
        // 2=ser_valorabono
        // 3=ser_valorseguro
        // 4=ser_clasificacion
        // 5=ser_ciudadentrega
        // 6=ser_devolverreci
        // 7=ser_piezas
        // 8=ser_peso
        // 9=ser_valor
        // 10=cli_idciudad
        // 11=ser_idusuarioguia
        // 12=ser_guiare
        // 13=ser_pendientecobrar
        // 14=ser_volumen
        // 15=gui_tiposervicio
        // 16=idservicios (agregado)

        // -----------------------------
        // 2) Normalizar y preparar datos
        // -----------------------------

        // Map de tipo de pago (AJÚSTALO a tu real)
        $tipopago = [
            1 => 'Contado',
            2 => 'Crédito',
            3 => 'Al Cobro',
            4 => 'Otro'
        ];

        // Valor préstamo vacío = 0
        if ($rw[1] == '' || $rw[1] === null) {
            $rw[1] = 0;
        }

        // mapear clasificación a texto tipo pago
        $tipoPago = isset($tipopago[$rw[4]]) ? $tipopago[$rw[4]] : $rw[4];

        // devolver recibido a texto (aunque en el front tú ya lo manejas, igual lo mandamos)
        $devolverRecibidoTexto = ($rw[6] == 1) ? 'SI' : 'NO';

        // -----------------------------
        // 3) Cálculo de seguro (1% del declarado)
        // -----------------------------

        // quitar puntos para operar
        $valorPrestamoRaw = str_replace('.', '', $rw[1]);
        $vrDeclaradoRaw   = str_replace('.', '', $rw[3]);

        $valorPrestamo = (float)$valorPrestamoRaw;
        $vrDeclarado   = (float)$vrDeclaradoRaw;

        $seguro = (intval($vrDeclarado) * 1) / 100; // 1% del declarado

        // -----------------------------
        // 4) Buscar porcentaje de préstamo en tabla prestamo
        // -----------------------------

        $porprestamo = 0;

        $sqlPor = "SELECT pre_porcentaje 
                   FROM prestamo 
                   WHERE pre_inicio < '$valorPrestamo' 
                     AND pre_final  >= '$valorPrestamo'
                   LIMIT 1";
        $resPor = $this->db->query($sqlPor);
        if ($resPor && $rowPor = $resPor->fetch_row()) {
            $pre_porcentaje = $rowPor[0]; // puede ser "3 %" según tu código

            $dosporcentaje = explode(" ", $pre_porcentaje);
            if (isset($dosporcentaje[1]) && $dosporcentaje[1] == '%') {
                $porprestamo = ($valorPrestamo * (float)$dosporcentaje[0]) / 100;
            }
        }

        // -----------------------------
        // 5) Cálculo de totales como en tu código
        // -----------------------------

        $totalprestamo = $valorPrestamo + $porprestamo;
        $totalflete    = (float)str_replace('.', '', $rw[9]) + $seguro;

        // ahora aplicas el abono
        $abonoRaw = str_replace('.', '', $rw[2]);
        $abono    = (float)$abonoRaw;

        $totalprestamo = $totalprestamo - $abono;
        $totalfinal    = $totalflete + $totalprestamo;
        $devolucion    = $totalfinal * -1;

        // -----------------------------
        // 6) Formateos como en el código viejo
        // -----------------------------
        $porprestamoFormat  = number_format($porprestamo, 0, ".", ".");
        $valorPrestamoFormat= number_format($valorPrestamo, 0, ".", ".");
        $vrFleteFormat      = number_format($rw[9], 0, ".", ".");
        $seguroFormat       = number_format($seguro, 0, ".", ".");
        $totalfleteFormat   = number_format($totalflete, 0, ".", ".");
        $totalprestamoFormat= number_format($totalprestamo, 0, ".", ".");
        $totalfinalFormat   = number_format($totalfinal, 0, ".", ".");
        $devolucionFormat   = number_format($devolucion, 0, ".", ".");
        $abonoFormat        = number_format($abono, 0, ".", ".");

        // Kilos total = peso + volumen
        $kiliostotal = (float)$rw[8] + (float)$rw[14];

        // -----------------------------
        // 7) Armar respuesta para el front (JSON)
        // -----------------------------
        $data = [
            'idservicios'          => $rw[16],
            'ser_paquetedescripcion'=> $rw[0],
            'ser_valorprestamo'    => $valorPrestamo,
            'ser_valorprestamo_format' => $valorPrestamoFormat,
            'ser_valorabono'       => $abono,
            'ser_valorabono_format'=> $abonoFormat,
            'ser_valorseguro'      => $rw[3],
            'ser_clasificacion'    => $rw[4],
            'ser_ciudadentrega'    => $rw[5],
            'ser_devolverreci'     => $rw[6],
            'ser_devolverreci_text'=> $devolverRecibidoTexto,
            'ser_piezas'           => $rw[7],
            'ser_peso'             => $rw[8],
            'ser_valor'            => $rw[9],
            'cli_idciudad'         => $rw[10],
            'ser_idusuarioguia'    => $rw[11],
            'ser_guiare'           => $rw[12],
            'ser_pendientecobrar'  => $rw[13],
            'ser_volumen'          => $rw[14],
            'gui_tiposervicio'     => $rw[15],

            // Campos calculados
            'seguro'               => $seguroFormat,
            'porprestamo'          => $porprestamoFormat,
            'total_prestamo'       => $totalprestamoFormat,
            'total_flete'          => $totalfleteFormat,
            'total_final'          => $totalfinalFormat,
            'devolucion'           => $devolucionFormat,
            'vr_flete'             => $vrFleteFormat,
            'kiliostotal'          => $kiliostotal,

            // Texto de tipo de pago
            'tipo_pago'            => $tipoPago
        ];

        return $data;
    }



    public function guardarLinkServicio($id_param2, $imprimir, $idguia, $ser_peso, $ser_volumen, $ser_valorseguro, $ser_valor)
    {
        $this->logEntrega("=== guardarLinkServicio() ===");
        $this->logEntrega("Parametros: id=$id_param2, imprimir=$imprimir, guia=$idguia");

        $guiaruta = "https://sistema.transmillas.com/ticketfacturacorreoimprimir.php?imprimir=$imprimir&id_param=$id_param2";
        
        $guiarutauser = "https://sistema.transmillas.com/ticketfacturacorreoimprimir.php?imprimir=$imprimir&id_param=$id_param2" .
                        "&peso={$ser_peso}&volumen={$ser_volumen}&seguro={$ser_valorseguro}&valorf={$ser_valor}";

        /* =============================
        BUSCAR SI YA EXISTE REGISTRO
        ============================= */
        $sqls = "SELECT id FROM imagenguias 
                WHERE ima_idservicio = '$id_param2'
                AND ima_tipo = '$imprimir'
                LIMIT 1";

        $res = $this->query($sqls);

        if (!$res) {
            $this->logEntrega("ERROR SQL buscar imagenguias: " . $this->db->error);
        }

        $row = ($res ? $res->fetch_row() : null);
        $exists = !empty($row);

        if ($exists) {
            $this->logEntrega("Registro existente. Actualizando...");

            $sqlUpd = "UPDATE imagenguias 
                    SET ima_ruta='$guiaruta',
                        ima_dir='$guiarutauser',
                        ima_nombre='$idguia'
                    WHERE ima_idservicio='$id_param2'
                        AND ima_tipo='$imprimir'";

            if (!$this->query($sqlUpd)) {
                $this->logEntrega("ERROR SQL update imagenguias: " . $this->db->error);
            } else {
                $this->logEntrega("Registro actualizado correctamente.");
            }

        } else {
            $this->logEntrega("Insertando nuevo registro en imagenguias...");

            $sqlIns = "INSERT INTO imagenguias 
                    (ima_nombre, ima_ruta, ima_tipo, ima_fecha, ima_idservicio, ima_dir)
                    VALUES (
                        '$idguia',
                        '$guiaruta',
                        '$imprimir',
                        '".date("Y-m-d")."',
                        '$id_param2',
                        '$guiarutauser'
                    )";

            if (!$this->query($sqlIns)) {
                $this->logEntrega("ERROR SQL insert imagenguias: " . $this->db->error);
            } else {
                $this->logEntrega("Registro insertado correctamente.");
            }
        }

        return [
            "status" => "ok",
            "url" => $guiarutauser
        ];
    }

    public function enviarAlertaWhat($numguia, $telefono, $tipo, $idservi, $guiacode = "")
    {
        $this->logEntrega("=== enviarAlertaWhat() ===");
        $this->logEntrega("Datos: guia=$numguia, tel=$telefono, tipo=$tipo, id=$idservi");

        $url = "https://bot.transmillas.com/whatsapp/Alertas/alertas.php";

        $payload = [
            "numero_guia"  => "$numguia",
            "telefono"     => "$telefono",
            "tipo_alerta"  => "$tipo",
            "id"  => "$guiacode",
            "id_guia"      => "$idservi"
            // "imagen1"      => "$imagen1"
        ];

        $jsonData = json_encode($payload);

        $this->logEntrega("Payload enviado: $jsonData");

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer MiSuperToken123'
            ],
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            $this->logEntrega("ERROR cURL: $error");

            return [
                "ok" => false,
                "error" => $error
            ];
        }

        curl_close($curl);

        $this->logEntrega("Respuesta API: $response");

        $respDecoded = json_decode($response, true);

        return [
            "ok" => true,
            "response" => $respDecoded
        ];
    }

    public function guardarFirmaEntrega($idservicio, $firmaBase64)
    {
        $stmtCheck = null;
        $stmtUpdate = null;
        $stmtInsert = null;
        date_default_timezone_set('America/Bogota');
        $fechaHoraColombia = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/debug_guardar_firma_entrega.log';
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] === INICIO guardarFirmaEntrega() ===\n", FILE_APPEND);

        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $firmaBase64, $type)) {
                $firmaData = substr($firmaBase64, strpos($firmaBase64, ',') + 1);
                $type = strtolower($type[1]);
                $firmaData = str_replace(' ', '+', $firmaData);
                $imagen = base64_decode($firmaData);

                if ($imagen === false) {
                    file_put_contents($logFile, "Error al decodificar base64\n", FILE_APPEND);
                    return false;
                }

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                    file_put_contents($logFile, "Tipo de imagen no permitido: $type\n", FILE_APPEND);
                    return false;
                }
            } else {
                file_put_contents($logFile, "Base64 invalido\n", FILE_APPEND);
                return false;
            }

            $rutaCarpeta = __DIR__ . '/../../firmas_clientes/';
            if (!file_exists($rutaCarpeta) && !mkdir($rutaCarpeta, 0777, true)) {
                file_put_contents($logFile, "Error al crear carpeta\n", FILE_APPEND);
                return false;
            }

            $nombreArchivo = 'firma_' . (int)$idservicio . '_' . time() . '.png';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            $rutaArchivoGuardar = "firmas_clientes/" . $nombreArchivo;

            if (file_put_contents($rutaArchivo, $imagen) === false) {
                file_put_contents($logFile, "Error al guardar imagen\n", FILE_APPEND);
                return false;
            }

            $tipoFirma = 'Entrega';
            $activoParaFirmar = 0;

            $sqlCheck = "SELECT id FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            if (!$stmtCheck) {
                file_put_contents($logFile, "Error preparando SELECT: " . $this->db->error . "\n", FILE_APPEND);
                return false;
            }

            $stmtCheck->bind_param('is', $idservicio, $tipoFirma);
            $stmtCheck->execute();
            $result = $stmtCheck->get_result();

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $idFirma = (int)$row['id'];

                $sqlUpdate = "UPDATE firma_clientes
                              SET firma_clientes = ?, fecha_registro = ?, activo_para_firmar = ?
                              WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                if (!$stmtUpdate) {
                    file_put_contents($logFile, "Error preparando UPDATE: " . $this->db->error . "\n", FILE_APPEND);
                    return false;
                }

                $stmtUpdate->bind_param('ssii', $rutaArchivoGuardar, $fechaHoraColombia, $activoParaFirmar, $idFirma);
                if (!$stmtUpdate->execute()) {
                    file_put_contents($logFile, "Error en UPDATE: " . $stmtUpdate->error . "\n", FILE_APPEND);
                    return false;
                }

                return true;
            }

            $sqlInsert = "INSERT INTO firma_clientes (id_guia, tipo_firma, firma_clientes, fecha_registro, activo_para_firmar)
                          VALUES (?, ?, ?, ?, ?)";
            $stmtInsert = $this->db->prepare($sqlInsert);
            if (!$stmtInsert) {
                file_put_contents($logFile, "Error preparando INSERT: " . $this->db->error . "\n", FILE_APPEND);
                return false;
            }

            $stmtInsert->bind_param('isssi', $idservicio, $tipoFirma, $rutaArchivoGuardar, $fechaHoraColombia, $activoParaFirmar);
            if (!$stmtInsert->execute()) {
                file_put_contents($logFile, "Error en INSERT: " . $stmtInsert->error . "\n", FILE_APPEND);
                return false;
            }

            return true;
        } catch (Exception $e) {
            file_put_contents($logFile, "Excepcion: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        } finally {
            if ($stmtCheck instanceof mysqli_stmt) { $stmtCheck->close(); }
            if ($stmtUpdate instanceof mysqli_stmt) { $stmtUpdate->close(); }
            if ($stmtInsert instanceof mysqli_stmt) { $stmtInsert->close(); }
            file_put_contents($logFile, "=== FIN guardarFirmaEntrega() ===\n\n", FILE_APPEND);
        }
    }

    public function reEnviarFirmaWhat($telefono, $tipo, $idservi, $link)
    {
        $this->logEntrega("=== reEnviarFirmaWhat() ===");
        $this->logEntrega("Datos: tel=$telefono, tipo=$tipo, id=$idservi");

        $url = "https://bot.transmillas.com/whatsapp/Alertas/alertas.php";

        $payload = [
            "telefono"     => "$telefono",
            "id"           => "$idservi",
            "tipo_alerta"  => "$tipo",
            "texto1"       => "$idservi",
            "texto2"       => "$link",
            "texto3"       => "Entrega"
        ];

        $jsonData = json_encode($payload);
        $this->logEntrega("Payload enviado: $jsonData");

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer MiSuperToken123'
            ],
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            $this->logEntrega("ERROR cURL: $error");

            return [
                "ok" => false,
                "error" => $error
            ];
        }

        curl_close($curl);
        $this->logEntrega("Respuesta API: $response");

        return [
            "ok" => true,
            "response" => json_decode($response, true)
        ];
    }

    public function existeFirmaEntregaPublica($idservicio)
    {
        $id = (int)$idservicio;

        $sql = "SELECT activo_para_firmar, firma_clientes
                FROM firma_clientes
                WHERE tipo_firma='Entrega' AND id_guia='$id'
                ORDER BY id DESC
                LIMIT 1";

        $res = $this->db->query($sql);

        if (!$res || $res->num_rows === 0) {
            return false;
        }

        $row = $res->fetch_assoc();

        if (isset($row['activo_para_firmar']) && (int)$row['activo_para_firmar'] === 0) {
            return true;
        }

        return !empty($row['firma_clientes']);
    }


}
