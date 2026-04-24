<?php
// model/RecogerModel.php

require_once "../config/database.php";

class RecogerModel
{
    /** @var mysqli */
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect(); // tu conexión nueva (mysqli)
    }

    private function esc($v)
    {
        return $this->db->real_escape_string($v);
    }

    /* ==========================================================
       UTILIDAD: GUARDAR IMAGEN (con compresión)
       ========================================================== */
    private function guardarImagen(array $file, string $carpetaRelativa)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return "";
        }

        if (!is_dir($carpetaRelativa)) {
            @mkdir($carpetaRelativa, 0777, true);
        }

        $nombre = date("Y-m-d-H-i-s") . "-" . basename($file["name"]);
        $destino = rtrim($carpetaRelativa, "/") . "/" . $nombre;

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

        // Redimensionar si es muy grande
        $maxW = 1280;
        $maxH = 1280;
        $w = imagesx($imagen);
        $h = imagesy($imagen);

        if ($w > $maxW || $h > $maxH) {
            $ratio = min($maxW / $w, $maxH / $h);
            $nw = (int)($w * $ratio);
            $nh = (int)($h * $ratio);

            $tmp = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($tmp, $imagen, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $imagen = $tmp;
        }

        imagejpeg($imagen, $destino, 70);
        imagedestroy($imagen);

        return $nombre;
    }

    /* ==========================================================
       BUSCAR DATOS DEL SERVICIO PARA RECOGER
       ========================================================== */
    public function buscarRecogida(int $idservicio)
    {
        if ($idservicio <= 0) {
            return null;
        }

        $sql = "SELECT 
            ser_paquetedescripcion,
            ser_valorprestamo,
            ser_valorabono,
            ser_valorseguro,
            ser_clasificacion,
            ser_ciudadentrega,
            ser_devolverreci,
            cli_idciudad,
            ser_prioridad,
            ser_idresponsable,
            ser_tipopaq,
            ser_volumen,
            ser_verificado,
            ser_piezas,
            ser_guiare,
            ser_consecutivo,
            gui_tiposervicio,
            ser_valor,
            ser_peso,
            ser_estado,
            ser_pendientecobrar,
            ser_recogida,
            ser_cotizacion,
            rel_nom_credito,
            cli_nombre,
            cli_telefono
        FROM servicios
        INNER JOIN rel_sercli        ON idservicios = ser_idservicio
        INNER JOIN clientesservicios ON idclientesdir = ser_idclientes
        INNER JOIN guias             ON idservicios = gui_idservicio
        INNER JOIN rel_sercre rs     ON rs.idservicio=idservicios 
        WHERE idservicios = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idservicio);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }


    public function logRecogido($msg) {
        $logFile = __DIR__ . "/logs_recogido.log";
        $fecha = date("Y-m-d H:i:s");
        @file_put_contents($logFile, "[$fecha] $msg\n", FILE_APPEND);
    }


    /* =====================================================
       VALIDAR EXISTENCIA DE FIRMA
       ===================================================== */
    private function existeFirmaEntrega($idservicio) {

        $conn = $this->db;
        $id = (int)$idservicio;

        $sql = "SELECT * FROM firma_clientes 
                WHERE tipo_firma='Recogida' AND id_guia='$id' AND firma_clientes!=''
                LIMIT 1";

        $res = $conn->query($sql);
        return ($res && $res->num_rows > 0);
    }

    /* ==========================================================
       GUARDAR RECOGIDO  (lógica vieja adaptada)
       ========================================================== */
 public function guardarRecogido(array $POST, array $FILES)
{
    $this->logRecogido("=== INICIO guardarRecogido() ===");
    $this->logRecogido("POST recibido: " . json_encode($POST));
    $this->logRecogido("FILES recibido: " . json_encode($FILES));

    $conn = $this->db;

    $id_param2 = isset($POST['idservicio']) ? (int)$POST['idservicio'] : 0;
    $this->logRecogido("ID servicio recibido: $id_param2");

    if ($id_param2 <= 0) {
        $this->logRecogido("ERROR: ID de servicio inválido");
        return ["ok" => false, "msg" => "ID de servicio inválido"];
    }

            /* ===============================
        VALIDAR FIRMA
        =============================== */
        if (!$this->existeFirmaEntrega($id_param2)) {
            $this->logEntrega("ERROR: No existe firma para el servicio $id_param2");
            return ["ok" => false, "msg" => "NO HAY FIRMA"];
        }
        elseif (empty($_POST['usuario'])) {
            // No viene o viene vacío
            return ["ok" => false, "msg" => "Error faltan datos recargue e intente nuevamente"];
        }

    // Aquí siempre es RECOGIDO (este método solo se llama para eso)
    $param1   = "RECOGIDO";
    $imprimir = "Recogida";

    // =========================
    // Parámetros del formulario
    // =========================
    $this->logRecogido("Cargando parámetros del formulario...");

    $param2  = $POST['param2']  ?? '';                            // piezas
    $param3  = $this->esc($POST['param3'] ?? '');                 // dice contener
    $param6  = str_replace(".", "", ($POST['param6'] ?? '0'));    // seguro
    $param7  = $POST['param7']  ?? '';                            // hora
    $param8  = isset($POST['param8']) ? (int)$POST['param8'] : 0; // tipo pago / clasificación
    $param9  = $POST['param9']  ?? '';                            // ciudad destino
    $param10 = (float)($POST['param10'] ?? 0);                    // peso
    $param11 = str_replace(".", "", ($POST['param11'] ?? '0'));   // valor total / a convenir
    $param12 = isset($POST['param12']) ? (int)$POST['param12'] : 0; // ser_pendientecobrar
    $param13 = $POST['param13'] ?? '';                            // ciudad origen
    $param14 = str_replace(".", "", ($POST['param54'] ?? '0'));   // abono
    $param15 = $POST['param15'] ?? '';                            // servicio (texto)
    $param16 = str_replace(".", "", ($POST['param16'] ?? '0'));   // valor préstamo
    $param17 = str_replace(".", "", ($POST['param17'] ?? '0'));   // pendiente cancelar
    $param18 = $POST['param18'] ?? '';                            // responsable
    $param19 = $POST['param19'] ?? '';                            // verificado
    $param20 = (float)($POST['param20'] ?? 0);                    // volumen
    $param21 = $POST['param21'] ?? '';                            // tipo paquete
    $param25 = $POST['param25'] ?? '';                            // # guia
    $param26 = $this->esc($POST['param26'] ?? '');                // estado paquete / descripción
    $param27 = $POST['param27'] ?? '';                            // planilla previa
    $param28 = $POST['param28'] ?? '';                            // fecha recogida (si admin)
    $param29 = isset($POST['param29']) ? (int)$POST['param29'] : 0; // devolver recibido
    $param30 = $POST['param30'] ?? '';                            // método de pago (id|cuenta|nombre)
    $param34 = $POST['param34'] ?? '';                            // tipoidservicio
    $param82 = $this->esc($POST['param82'] ?? '');                // nombre quien entrega
    $param83 = $this->esc($POST['param83'] ?? '');                // documento
    $param84 = $this->esc($POST['param84'] ?? '');                // correo
    $param85 = $this->esc($POST['param85'] ?? '');                // teléfono
    $param86 = $this->esc($POST['param86'] ?? '');                // enviar_whatsapp

    if ($param2 === '' || !is_numeric($param2) || (float)$param2 <= 0) {
        $this->logRecogido("ERROR: Numero de piezas invalido");
        return ["ok" => false, "msg" => "El numero de piezas es obligatorio"];
    }

    if ($param8 === 1 && $param10 <= 0) {
        $this->logRecogido("ERROR: Peso obligatorio para contado");
        return ["ok" => false, "msg" => "El peso es obligatorio cuando el tipo de pago es Contado"];
    }
    

    // Contexto
    $id_usuario         = isset($POST['usuario']) ? (int)$POST['usuario'] : 0;
    $id_nombre          = $POST['nombre'] ?? '';
    $nivel_acceso       = isset($POST['acceso']) ? (int)$POST['acceso'] : 1;
    $precioinicialkilos = isset($POST['precioinicialkilos']) ? (float)$POST['precioinicialkilos'] : 0;
    $idguia             = $POST['idservicio'] ?? '';

    $this->logRecogido("Contexto: id_usuario=$id_usuario, id_nombre=$id_nombre, nivel_acceso=$nivel_acceso, precioinicialkilos=$precioinicialkilos, idguia=$idguia");
    date_default_timezone_set('America/Bogota');
    $fechaactual = date("Y-m-d");
    $fechatiempo = date("Y-m-d H:i:s");

    $rw1 = [0, 0, 0, '', 0];

    try {
        $this->logRecogido("Iniciando transacción...");
        $conn->begin_transaction();

        // =========================
        // PLANILLA (conf_fac)
        // =========================
        $kilos = $param10 + $param20;
        $this->logRecogido("Kilos calculados para planilla: $kilos");

        // if ($param25 != '') {
        //     $planilla = $param25;
        //     $this->logRecogido("Usando planilla previa enviada en param27: $planilla");
        // } else {
        //     $this->logRecogido("Buscando conf_fac según ciudad origen: $param13");

        //     $sql = "SELECT conf.idconfac, conf.idconsecutivo, conf.idresolucion, conf.prefijo, c.inner_sedes
        //             FROM conf_fac conf
        //             INNER JOIN ciudades c ON conf.idciudad = c.inner_sedes
        //             WHERE c.idciudades = '" . $this->esc($param13) . "'
        //             LIMIT 1";
        //     $this->logRecogido("SQL conf_fac: $sql");

        //     $res = $conn->query($sql);
        //     if (!$res) {
        //         $this->logRecogido("ERROR SQL conf_fac: " . $conn->error);
        //     }
        //     if ($res) {
        //         $rw1 = $res->fetch_row() ?: [0, 0, 0, '', 0];
        //     }

        //     $planilla      = $rw1[3] . $rw1[1];
        //     $idconsecutivo = (int)$rw1[1] + 1;

        //     $this->logRecogido("Planilla generada: $planilla, idconsecutivo nuevo: $idconsecutivo");

        //     if ($idconsecutivo >= 10) {
        //         $sql2 = "UPDATE conf_fac conf
        //                  INNER JOIN ciudades c ON conf.idciudad = c.inner_sedes
        //                  SET conf.idconsecutivo = '$idconsecutivo'
        //                  WHERE c.idciudades = '" . $this->esc($param13) . "'";
        //         $this->logRecogido("SQL UPDATE conf_fac: $sql2");

        //         if (!$conn->query($sql2)) {
        //             $this->logRecogido("ERROR actualizando conf_fac: " . $conn->error);
        //             throw new Exception("Error actualizando conf_fac: " . $conn->error);
        //         }
        //     } else {
        //         $this->logRecogido("idconsecutivo < 10, se limpia planilla");
        //         $planilla = "";
        //     }
        // }

        if ($param25 != '') {

            // Si viene una planilla en el parámetro, úsala
            $planilla = $param25;
            $this->logRecogido("Usando planilla previa enviada en param25: $planilla");

        } else {

            $this->logRecogido("Buscando conf_fac según ciudad origen: $param13");

            // Iniciar transacción con la misma conexión
            $conn->begin_transaction();

            try {

                // Bloqueo seguro para evitar duplicados de planilla
                $sql = "SELECT conf.idconfac, conf.idconsecutivo, conf.idresolucion, conf.prefijo, c.inner_sedes
                        FROM conf_fac conf
                        INNER JOIN ciudades c ON conf.idciudad = c.inner_sedes
                        WHERE c.idciudades = '" . $this->esc($param13) . "'
                        FOR UPDATE";

                $this->logRecogido("SQL conf_fac FOR UPDATE: $sql");

                $res = $conn->query($sql);
                if (!$res) {
                    throw new Exception("ERROR SQL conf_fac: " . $conn->error);
                }

                $rw1 = $res->fetch_row();
                if (!$rw1) {
                    throw new Exception("No se encontró configuración conf_fac para la ciudad: " . $param13);
                }

                // rw1:
                // 0 = idconfac
                // 1 = idconsecutivo
                // 2 = idresolucion
                // 3 = prefijo
                // 4 = inner_sedes

                // ================================
                //  GENERAR PLANILLA (NUNCA VACÍA)
                // ================================
                $prefijo = $rw1[3];
                $consecutivoActual = (int)$rw1[1];

                // Si por alguna razón vienen valores raros (null/vacío), se garantiza no romper
                if ($prefijo === null || $prefijo === "") {
                    throw new Exception("El prefijo de la planilla está vacío. Revisar conf_fac.");
                }

                // Generar la planilla como prefijo + consecutivo actual
                $planilla = $prefijo . $consecutivoActual;

                if ($planilla === "") {
                    throw new Exception("Error inesperado: planilla generada vacía");
                }

                $this->logRecogido("Planilla generada: $planilla");

                // Nuevo consecutivo
                $idconsecutivo = $consecutivoActual + 1;

                // Actualizar consecutivo de manera segura
                $sql2 = "UPDATE conf_fac
                        SET idconsecutivo = '$idconsecutivo'
                        WHERE idconfac = '" . $rw1[0] . "'";

                $this->logRecogido("SQL UPDATE conf_fac: $sql2");

                if (!$conn->query($sql2)) {
                    throw new Exception("ERROR actualizando conf_fac: " . $conn->error);
                }

                // Confirmar cambios
                $conn->commit();
            }
            catch (Exception $e) {

                // Revertir si hay error
                $conn->rollback();

                $this->logRecogido("ERROR EN TRANSACCIÓN: " . $e->getMessage());

                throw $e;
            }
        }

        if ($param25 == '') {
            $param25 = $planilla;
            $this->logRecogido("Guía vacía, se usa planilla como guía: $param25");
        }

        // =========================
        // Condiciones de pendiente / crédito
        // =========================
        $this->logRecogido("Procesando condiciones de pendiente/crédito...");

        $cond       = "";
        $cond1      = "";
        $valortotal = 0;

        if ($param12 == '') $param12 = 0;
        if ($param20 == '') $param20 = 0;

        if ($param8 == 1) { // dejar como pendiente por cobrar
            $cond = ",ser_peso='$param10', ser_pendientecobrar='$param12'";
        } elseif ($param8 == 2) { // crédito
            $cond = ",ser_pendientecobrar=2, ser_peso='$param10'";
            $param12 = 2;
        } elseif ($param8 == 3) { // pendientes x cobrar
            $cond = ",ser_peso='$param10'";
        } else {
            $cond = "";
        }

        $this->logRecogido("Cond inicial: cond='$cond', param12=$param12, param17=$param17");

        if ($param17 > 0 && $param12 == 1) {
            $cond    = ",ser_pendientecobrar=4, ser_valorpendiente='$param17'";
            $param12 = 4;
        } elseif ($param17 > 0) {
            $cond    = ",ser_pendientecobrar=5, ser_valorpendiente='$param17'";
            $param12 = 5;
        }

        $this->logRecogido("Cond final: cond='$cond', param12=$param12");

        // =========================
        // Cálculo de kilos y precios
        // =========================
        $this->logRecogido("Calculando precios por kilos...");

        $kilos     = $param10;
        $idprecios = 0;

        if ($kilos > 0) {
            $sqlprecios = "SELECT idprecioskilos
                           FROM precioskilos
                           WHERE '$kilos' BETWEEN pre_inicial AND prec_final
                           LIMIT 1";
            $this->logRecogido("SQL precioskilos: $sqlprecios");

            $rPre = $conn->query($sqlprecios);
            if (!$rPre) {
                $this->logRecogido("ERROR SQL precioskilos: " . $conn->error);
            }
            $rowP = $rPre ? $rPre->fetch_row() : [0];
            $idprecios = (int)$rowP[0];
        }

        if ($idprecios == 0 || $idprecios == '') {
            $idprecios = 1;
        }
        $this->logRecogido("idprecios resultante: $idprecios");

        // Tipo de servicio de la guía
        $sql32 = "SELECT gui_tiposervicio FROM guias WHERE gui_idservicio = '$id_param2' LIMIT 1";
        $this->logRecogido("SQL tipo servicio guias: $sql32");
        $r32   = $conn->query($sql32);
        if (!$r32) {
            $this->logRecogido("ERROR SQL guias: " . $conn->error);
        }
        $rw6   = $r32 ? $r32->fetch_row() : [0];

        $sql33 = "SELECT tip_preciokilo, tip_precioadicional
                  FROM tiposervicio
                  WHERE idtiposervicio = '{$this->esc($rw6[0])}'
                  LIMIT 1";
        $this->logRecogido("SQL tiposervicio: $sql33");
        $r33 = $conn->query($sql33);
        if (!$r33) {
            $this->logRecogido("ERROR SQL tiposervicio: " . $conn->error);
        }
        $rw7 = $r33 ? $r33->fetch_row() : [0, 0];
        $this->logRecogido("Datos tiposervicio: precioKilo={$rw7[0]}, precioAdicional={$rw7[1]}");

        // if ($param11<1 or $param11=="") {// si tiene precio ya 
            # code...
            
            if ($param34 == '1000') { 
                // Valor a convenir tpio de servicio se deja el valor que viene o sea param 11
                $cond1      = "";
                $valortotal = (float)str_replace(".", "", $param11);
                $this->logRecogido("Tipo 1000 (valor a convenir). valortotal=$valortotal");
            } elseif ($param8 != 2) {// si tipo de pago es diferente de crédito y a convenir 
                
                // if ($rw7[0] >= 1) { //Si tiene kilos configurados 
                //     // Precios generales
                //     if ($rw7[0] != '') {
                //         if ($param10 > $precioinicialkilos) {
                //             $varor1     = 0; // En tu código original esto venía de rw2[0], aquí no se tiene
                //             $valor2     = ($param10 + $param20 - $precioinicialkilos) * $rw7[1];
                //             $valortotal = $varor1 + $valor2;
                //         } else {
                //             $varor1     = 0;
                //             $valor2     = $param20 * $rw7[1];
                //             $valortotal = $varor1 + $valor2;
                //         }
                //     } else {
                //         $valortotal = 0;
                //     }
                //     $this->logRecogido("Precios generales: valortotal=$valortotal");
                // } elseif ($param11 >= 1 && $kilos > 0) { //Si tiene precio y tiene kilos 
                    

                    //  $resp = $modelo->calcularValorTotal($param10, $param20, $param13, $param9, $param34,$param6);

                    // $credito     = $_POST['rel_nom_credito'] ?? 0; // nombre del cliente, usado para buscar ID

                    // // ===============================
                    // // BUSCAR ID DEL CRÉDITO
                    // // ===============================
                    // $resultado = $modelo->idCredito($credito);

                    // if (!empty($resultado)) {
                    //     $idcredito = $resultado[0]["idcreditos"];
                    //     file_put_contents($rutaLog, $fecha . "ID de crédito encontrado: $idcredito" . PHP_EOL, FILE_APPEND);
                    // } else {
                    //     $idcredito = 0;
                    //     file_put_contents($rutaLog, $fecha . "No se encontró crédito, se usa ID=0" . PHP_EOL, FILE_APPEND);
                    // }

                    // // ===============================
                    // // EJECUTAR CÁLCULO
                    // // ===============================
                    // $resp = $modelo->calcularValorConLogicaVieja(
                    //     $param10,            // kilos
                    //     $param20,         // volumen
                    //     $param13,       // ciudad origen
                    //     $param9,       // ciudad destino
                    //     $param34,        // tipo servicio
                    //     $param6,    // valor declarado
                    //     $idcredito,       // ID crédito CORREGIDO
                    //     $tipoCliente,     // 1 si es crédito
                    //     0,                // valor préstamo
                    //     0,                // param5
                    //     5                 // kilos iniciales
                    // );



                    //  $valortotal=$resp['total'];
                    
                    // // configuraron precios y la opcion es contado
                    // $sql3 = "SELECT p.idprecios, p.pre_kilo, c.con_precios
                    //         FROM precios p
                    //         INNER JOIN configuracionkilos c ON c.con_idprecioskilos = p.idprecios
                    //         WHERE c.con_tipo = 'normal'
                    //         AND p.pre_idciudadori = '" . $this->esc($param13) . "'
                    //         AND p.pre_idciudaddes = '" . $this->esc($param9) . "'
                    //         AND p.pre_tiposervicio = '" . $this->esc($rw6[0]) . "'
                    //         AND c.con_idprecios = '$idprecios'
                    //         LIMIT 1";


                    // $this->logRecogido("SQL precios configurados: $sql3");

                    // $r3  = $conn->query($sql3);
                    // if (!$r3) {
                    //     $this->logRecogido("ERROR SQL precios/configuracionkilos: " . $conn->error);
                    // }
                    // $rw2 = $r3 ? $r3->fetch_row() : [0, 0, 0];

                    // $kilos = $param10 + $param20;
                    // if ($param10 > $precioinicialkilos) {
                    //     $varor1     = $rw2[1];
                    //     $valor2     = ($param10 + $param20 - $precioinicialkilos) * $rw2[2];
                    //     $valortotal = $varor1 + $valor2;
                    // } else {
                    //     $varor1     = $rw2[1];
                    //     $valor2     = $param20 * $rw2[2];
                    //     $valortotal = $varor1 + $valor2;
                    // }

                //     $this->logRecogido("Precios configurados: valortotal=$valortotal");
                // } else { //Si es credito
                //     $kilos      = 0;
                //     $valortotal = 0;
                //     $this->logRecogido("Sin configuración de precios válida, valortotal=0");
                // }
                $valortotal = $_POST['param11'] ?? 0;
                $cond1 = ",ser_valor='$valortotal', ser_piezas='$param2'";
                $this->logRecogido("Sin configuración de precios válida, valortotal=0 ,ser_valor='$valortotal', ser_piezas='$param2'");
            } elseif ($param8 == 2) {

                if($_POST['param112']!=0 or $_POST['param112']!=""){ // si es credito y pusieron peso y/o volumen va a ser igual al calculado que viene 

                    $kilos      = 0;
                    $valortotal = $_POST['param112'];
                }else{
                    // Crédito, no cobra flete aquí
                    $kilos      = 0;
                    $valortotal = 0;


                }
                    $cond1      = ",ser_valor='$valortotal', ser_piezas='$param2'";
                    $this->logRecogido("Tipo crédito: se deja valortotal=0, kilos=0");

            }

        // =========================
        // Préstamos / declarado
        // =========================
        $this->logRecogido("Calculando préstamo y valor declarado...");

        if ($param16 != '') {
            $sqlPor = "SELECT pre_porcentaje
                       FROM prestamo
                       WHERE pre_inicio < '$param16'
                         AND pre_final  >= '$param16'
                       LIMIT 1";
            $this->logRecogido("SQL prestamo: $sqlPor");

            $rPor = $conn->query($sqlPor);
            if (!$rPor) {
                $this->logRecogido("ERROR SQL prestamo: " . $conn->error);
            }
            $rowPor     = $rPor ? $rPor->fetch_row() : [0];
            $porprestamo = 0;

            if ($rowPor[0]) {
                $dos = explode(" ", $rowPor[0]);
                if (isset($dos[1]) && $dos[1] == '%') {
                    $porprestamo = ($param16 * (float)$dos[0]) / 100;
                }
            }
        } else {
            $porprestamo = 0;
        }

        $param6_num    = (int)$param6;
        $pordeclarado  = ($param6_num * 1) / 100;

        $this->logRecogido("porprestamo=$porprestamo, pordeclarado=$pordeclarado");

        // =========================
        // Borrar cuentaspromotor anterior
        // =========================
        $this->logRecogido("Eliminando cuentaspromotor anteriores...");
        $sql21 = "DELETE FROM cuentaspromotor WHERE cue_idservicio = '$id_param2'";
        if (!$conn->query($sql21)) {
            $this->logRecogido("ERROR borrando cuentaspromotor: " . $conn->error);
            throw new Exception("Error borrando cuentaspromotor: " . $conn->error);
        }

        // =========================
        // Nivel de acceso / responsable
        // =========================
        $this->logRecogido("Determinando responsable según nivel de acceso...");

        if ($nivel_acceso == 1) {
            // if (!empty($param28)) {
            //     $fechatiempo = $param28;
            // }
            $sql5 = "SELECT idusuarios, usu_nombre
                     FROM usuarios
                     WHERE idusuarios = '" . $this->esc($param18) . "'
                       AND (usu_estado = 1 OR usu_filtro = 1)
                     LIMIT 1";
            $this->logRecogido("SQL usuarios (nivel 1): $sql5");
            $rU = $conn->query($sql5);
            if (!$rU) {
                $this->logRecogido("ERROR SQL usuarios: " . $conn->error);
            }
            $rowU = $rU ? $rU->fetch_row() : [0, ''];
            $id_nombre = $rowU[1];

            $this->logRecogido("Responsable encontrado: $id_nombre");
        } elseif ($nivel_acceso != 3 && $nivel_acceso != 1) {
            $param18 = $id_usuario;
            $this->logRecogido("Responsable seteado al usuario logueado: $param18");
        }

        // =========================
        // Método de pago (param30)
        // =========================
        $this->logRecogido("Procesando método de pago...");

        $pagos    = explode('|', $param30 . '||');
        $tipopago = $pagos[0] ?? 0;
        $cuenta   = $pagos[1] ?? '';
        $namepago = $pagos[2] ?? '';

        if ($param8 == 1) {
            $estadop = 'Contado';
        } elseif ($param8 == 3) {
            $estadop = 'Al Cobro';
        } elseif ($param8 == 2) {
            $estadop = 'Credito';
        } else {
            $estadop = 'Otro';
        }

        $this->logRecogido("Método de pago parseado: tipopago=$tipopago, cuenta=$cuenta, namepago=$namepago, estadop=$estadop");

        // =========================
        // Insert en cuentaspromotor
        // =========================
        $this->logRecogido("Insertando en cuentaspromotor...");

        $sql2 = "INSERT INTO cuentaspromotor
                (cue_idservicio, cue_idoperador, cue_abono, cue_porprestamo, cue_prestamo,
                 cue_vrdeclarado, cue_pordeclarado, cue_valorflete, cue_tipopago,
                 cue_pendientecobrar, cue_fecha, cue_valpagar, cue_estado,
                 cue_idciudadori, cue_idciudaddes, cue_tipoevento, cue_numeroguia,
                 cue_fecharecogida, cue_transferencia, cue_kilostotal)
                 VALUES
                ('$id_param2', '" . $this->esc($param18) . "', '$param14', '$porprestamo', '$param16',
                 '$param6', '$pordeclarado', '$valortotal', '" . $this->esc($param15) . "',
                 '$param12', '$fechatiempo', '$param17', '4',
                 '" . $this->esc($param13) . "', '" . $this->esc($param9) . "',
                 '$param8', '" . $this->esc($planilla) . "', '$fechatiempo',
                 '" . $this->esc($namepago) . "', '$kilos')";
        $this->logRecogido("SQL INSERT cuentaspromotor: $sql2");

        if (!$conn->query($sql2)) {
            $this->logRecogido("ERROR insertando cuentaspromotor: " . $conn->error);
            throw new Exception("Error insertando cuentaspromotor: " . $conn->error);
        }

        // =========================
        // Actualizar guías (quién recogió)
        // =========================
        $this->logRecogido("Actualizando tabla guias (quien recogió)...");

        $sql3 = "UPDATE guias
                 SET gui_recogio = '" . $this->esc($id_nombre) . "',
                     gui_fecharecogio = '$fechatiempo'
                 WHERE gui_idservicio = '$id_param2'";
        $this->logRecogido("SQL UPDATE guias: $sql3");

        if (!$conn->query($sql3)) {
            $this->logRecogido("ERROR actualizando guias: " . $conn->error);
            throw new Exception("Error actualizando guias: " . $conn->error);
        }

        if ($param29 == '') {
            $param29 = 0;
        }

        // =========================
        // Pagoscuentas (si contado y tipopago > 1)
        // =========================
        if ($param8 == 1 && $tipopago > 1) {
            $this->logRecogido("Procesando pagoscuentas (Contado + tipopago>1)...");

            $img_transaccion = "";
            if (isset($FILES['param40']) && $FILES['param40']['error'] === UPLOAD_ERR_OK) {
                $img_transaccion = $this->guardarImagen($FILES['param40'], "./../../img_transacciones");
                $this->logRecogido("Imagen de transacción guardada: $img_transaccion");
            } else {
                $this->logRecogido("No llegó imagen de transacción para param40.");
            }

            $sql51 = "DELETE FROM pagoscuentas WHERE pag_idservicio = '$id_param2'";
            $this->logRecogido("SQL DELETE pagoscuentas: $sql51");
            if (!$conn->query($sql51)) {
                $this->logRecogido("ERROR borrando pagoscuentas: " . $conn->error);
                throw new Exception("Error borrando pagoscuentas: " . $conn->error);
            }

            $sql5 = "INSERT INTO pagoscuentas
                    (pag_tipopago, pag_cuenta, pag_valor, pag_idoperario,
                     pag_idservicio, pag_guia, pag_estado, pag_fecha, pag_img_transaccion)
                    VALUES
                    ('$tipopago', '" . $this->esc($cuenta) . "', '$param11', '$id_usuario',
                     '$id_param2', '" . $this->esc($planilla) . "', '$estadop',
                     '$fechatiempo', '" . $this->esc($img_transaccion) . "')";
            $this->logRecogido("SQL INSERT pagoscuentas: $sql5");

            if (!$conn->query($sql5)) {
                $this->logRecogido("ERROR insertando pagoscuentas: " . $conn->error);
                throw new Exception("Error insertando pagoscuentas: " . $conn->error);
            }
        } else {
            $this->logRecogido("No aplica inserción en pagoscuentas (param8=$param8, tipopago=$tipopago).");
        }

        // =========================
        // Foto del servicio (param87)
        // =========================
        $this->logRecogido("Procesando foto del servicio (param87)...");
        $foto = "";
        if (isset($FILES['param87'])) {
            $foto = $this->guardarImagen($FILES['param87'], "./../../imgServicios");
            $this->logRecogido("Foto de servicio guardada: $foto");
        } else {
            $this->logRecogido("No llegó foto en param87.");
        }

        // =========================
        // Actualizar SERVICIOS
        // =========================
        $this->logRecogido("Actualizando tabla servicios...");

        $sql1 = "UPDATE servicios SET
                    ser_consecutivo   = '" . $this->esc($planilla) . "',
                    ser_resolucion    = '" . $this->esc($rw1[2]) . "',
                    ser_recogida      = '" . $this->esc($param1) . "',
                    ser_paquetedescripcion = '$param3',
                    ser_valorseguro   = '$param6',
                    ser_horaentrega   = '$param7',
                    ser_clasificacion = '$param8',
                    ser_fechafinal    = '$fechatiempo',
                    ser_fechaasignacion = '$fechatiempo',
                    ser_estado        = '4',
                    ser_devolverreci  = '$param29',
                    ser_tipopaq       = '$param21',
                    ser_verificado    = '$param19',
                    ser_volumen       = '$param20',
                    ser_guiare        = '" . $this->esc($param25) . "',
                    ser_descripcion   = '$param26',
                    ser_idresponsable = '" . $this->esc($param18) . "',
                    ser_img_recog     = '" . $this->esc($foto) . "'
                    $cond1
                    $cond
                 WHERE idservicios = '$id_param2'";
        $this->logRecogido("SQL UPDATE servicios: $sql1");

        if (!$conn->query($sql1)) {
            $this->logRecogido("ERROR actualizando servicios: " . $conn->error);
            throw new Exception("Error actualizando servicios: " . $conn->error);
        }

        $this->guardarUbicacionServicio($id_param2, $id_usuario, "RECOGIDA", $POST);
        // =========================
        // Actualizar firma_clientes
        // =========================
        $this->logRecogido("Actualizando firma_clientes (Recogida)...");

        $sql8 = "UPDATE firma_clientes SET
                    nombre           = '$param82',
                    numero_documento = '$param83',
                    correo_electronico = '$param84',
                    telefono         = '$param85',
                    enviar_whatsapp  = '$param86'
                 WHERE tipo_firma = 'Recogida'
                   AND id_guia    = '$id_param2'";
        $this->logRecogido("SQL UPDATE firma_clientes: $sql8");

        if (!$conn->query($sql8)) {
            $this->logRecogido("ERROR actualizando firma_clientes: " . $conn->error);
            throw new Exception("Error actualizando firma_clientes: " . $conn->error);
        }

        // =========================
        // Seguimiento de ruta
        // =========================
        $this->logRecogido("Actualizando seguimientoruta...");

        $sql7 = "UPDATE seguimientoruta
                 SET seg_guia        = '" . $this->esc($planilla) . "',
                     seg_estado      = 'completado',
                     seg_fechafinalizo = '$fechatiempo'
                 WHERE seg_idservicio = '$id_param2'
                   AND seg_tipo       = 'Recogida'
                   AND seg_estado    != 'Cambioruta'
                   AND seg_fecha LIKE '%$fechaactual%'";
        $this->logRecogido("SQL UPDATE seguimientoruta: $sql7");

        if (!$conn->query($sql7)) {
            $this->logRecogido("ERROR actualizando seguimientoruta: " . $conn->error);
            throw new Exception("Error actualizando seguimientoruta: " . $conn->error);
        }

        // =========================
        // serviciosdia + link + WhatsApp
        // =========================
        $this->logRecogido("Consultando serviciosdia para envío de WhatsApp...");

        $sql12 = "SELECT idservicios, ser_estado, cli_telefono, ser_consecutivo,
                         ser_peso, ser_volumen, ser_valorseguro, ser_valor
                  FROM serviciosdia
                  WHERE idservicios = '$id_param2'";
        $this->logRecogido("SQL SELECT serviciosdia: $sql12");

        $r12 = $conn->query($sql12);
        if (!$r12) {
            $this->logRecogido("ERROR SQL serviciosdia: " . $conn->error);
        }
        $rw12 = $r12 ? $r12->fetch_row() : null;

        if ($rw12) {
            $this->logRecogido("Datos serviciosdia encontrados, preparando envío de WhatsApp...");
            $numguia    = $planilla;
            $telefono   = $rw12[2];
            $idservicio = $id_param2;

            $resLink  = $this->guardarLinkServicio(
                $id_param2,
                $imprimir,
                $numguia,
                $rw12[4],
                $rw12[5],
                $rw12[6],
                $rw12[7]
            );
            $linkGuia = $resLink['url'] ?? '';

            $this->logRecogido("Link generado: $linkGuia");

            if ($telefono == $param85) {
                $this->logRecogido("Enviando WhatsApp solo a telefono=$telefono");
                $this->enviarAlertaWhat( $telefono, 42, $numguia."R");
            } else {
                $this->logRecogido("Enviando WhatsApp a remitente ($telefono) y quien entrega ($param85)");
                $this->enviarAlertaWhat( $telefono, 42, $numguia."R");
                $this->enviarAlertaWhat( $param85, 42, $numguia."R");
            }
        } else {
            $this->logRecogido("No se encontraron datos en serviciosdia, no se envía WhatsApp.");
        }

        if ($param8 == 2) {
            $estadop = 'Credito';
        }

        $conn->commit();
        $this->logRecogido("Transacción commit OK. === FIN guardarRecogido() ===");
        return ["ok" => true,"numeroGuia" => $numguia];

    } catch (\Throwable $e) {
        $this->logRecogido("ERROR en guardarRecogido(): " . $e->getMessage());
        if ($conn && $conn->errno === 0) {
            // si el error no viene de MySQL, igual intentamos rollback
        }
        $conn->rollback();
        $this->logRecogido("Rollback ejecutado.");
        return ["ok" => false, "msg" => $e->getMessage(),"numeroGuia" => $numguia ];

    }
}


    /* ==========================================================
       GUARDAR NO RECOGIDO
       ========================================================== */
    public function guardarNoRecogido(array $POST, array $FILES)
    {
        date_default_timezone_set('America/Bogota');
        $idservicio = isset($POST['idservicio']) ? (int)$POST['idservicio'] : 0;
        if ($idservicio <= 0) {
            return ["ok" => false, "msg" => "ID de servicio inválido"];
        }
        $id_nombre          = $POST['nombre'] ?? '';

        $motivo = $this->esc($POST['motivo'] ?? '');
        $ahora  = date("Y-m-d H:i:s");

        $this->db->begin_transaction();

        try {
            $evidencia = "";
            if (isset($FILES['foto_evidencia'])) {
                $evidencia = $this->guardarImagen($FILES['foto_evidencia'], "./../../imgNoRecogidas");
            }
            $descripcion=$id_nombre.": ".$motivo;
            // Actualiza servicios
            $sqlSer = "UPDATE servicios SET
                ser_recogida='NO RECOGIDO',
                ser_motivo='$motivo',
                ser_descllamada='$descripcion',
                ser_estado='5',
                ser_img_evidencia='$evidencia',
                ser_fechafinal='$ahora'
            WHERE idservicios='$idservicio'";
            if (!$this->db->query($sqlSer)) {
                throw new Exception("Error actualizando servicios: " . $this->db->error);
            }

            // Seguimiento
            $sqlSeg = "UPDATE seguimientoruta
                       SET seg_estado='NO Recogida',
                           seg_tipo='NO Recogida',
                           seg_fechafinalizo='$ahora',
                           seg_descripcion='$motivo'
                       WHERE seg_idservicio='$idservicio'
                         AND seg_tipo='Recogida'
                         AND seg_estado!='Cambioruta'";
            if (!$this->db->query($sqlSeg)) {
                throw new Exception("Error actualizando seguimientoruta: " . $this->db->error);
            }

            $this->db->commit();
            return ["ok" => true];

        } catch (\Throwable $e) {
            $this->db->rollback();
            return ["ok" => false, "msg" => $e->getMessage()];
        }
    }

    /* ==========================================================
       NUEVO ✔ listarTipos()
       ========================================================== */
    public function listarTipos()
    {
        $sql = "SELECT tip_nombre 
                FROM tipo 
                ";

        $res = $this->db->query($sql);

        $arr = [];
        while ($row = $res->fetch_assoc()) {
            $arr[] = $row;
        }
        return $arr;
    }

    /* ==========================================================
       NUEVO ✔ listarMetodosPago()
       ========================================================== */
    public function listarMetodosPago()
    {
        $sql = "SELECT 
                    CONCAT(idtipospagos,'|',pag_numerocuenta,'|',pag_nombre) AS id,
                    pag_nombre
                FROM tipospagos
                WHERE pag_estado LIKE '%Activo%'
                ORDER BY idtipospagos";

        $res = $this->db->query($sql);

        $arr = [];
        while ($row = $res->fetch_assoc()) {
            $arr[] = $row;
        }
        return $arr;
    }

        /* ==========================================================
       NUEVO ✔ listarCreditos()
       ========================================================== */
    public function listarCreditos()
    {
        $sql = "SELECT `cre_nombre`,`cre_nombre` 
        FROM `creditos` 

        where cre_estado='Activo'";

        $res = $this->db->query($sql);

        $arr = [];
        while ($row = $res->fetch_assoc()) {
            $arr[] = $row;
        }
        return $arr;
    }

     /* ==========================================================
       NUEVO ✔ listarCreditos()
       ========================================================== */
    public function idCredito($nomCredito)
    {
        $sql = "SELECT `cre_nombre`,`idcreditos` 
        FROM `creditos` 
        -- inner join `hojadevidacliente` 
        -- on hoj_clientecredito =idcreditos 
        where cre_nombre='".$nomCredito."'";

        $res = $this->db->query($sql);

        $arr = [];
        while ($row = $res->fetch_assoc()) {
            $arr[] = $row;
        }
        return $arr;
    }



        public function logEntrega($msg) {
            $logFile = __DIR__ . "logs_WF.log";
            $fecha = date("Y-m-d H:i:s");
            file_put_contents($logFile, "[$fecha] $msg\n", FILE_APPEND);
        }
    /* ==========================================================
       DUMMIES para luego reemplazar por tus funciones reales
       ========================================================== */
    public function guardarLinkServicio($id_param2, $imprimir, $idguia, $ser_peso, $ser_volumen, $ser_valorseguro, $ser_valor)
    {
        $this->logEntrega("=== guardarLinkServicio() ===");
        $this->logEntrega("Parametros: id=$id_param2, imprimir=$imprimir, guia=$idguia");

        $guiaruta = "https://sistema.transmillas.com/ticketfacturacorreoimprimir.php?imprimir=$imprimir&id_param=$id_param2";
        
        $guiarutauser = "https://sistema.transmillas.com/ticketfacturacorreoimprimir.php?imprimir=$imprimir&id_param=$id_param2" .
                        "&peso={$ser_peso}&volumen={$ser_volumen}&seguro={$ser_valorseguro}&valorf={$ser_valor}";

        $dataJson = [
            "imprimir" => $imprimir,
            "id_param" => $id_param2,
            "peso" => $ser_peso,
            "volumen" => $ser_volumen,
            "seguro" => $ser_valorseguro,
            "valorf" => $ser_valor
        ];

        // convertirlo a JSON en texto
        $jsonString = json_encode($dataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        /* =============================
        BUSCAR SI YA EXISTE REGISTRO
        ============================= */
        $sqls = "SELECT id FROM imagenguias 
                WHERE ima_idservicio = '$id_param2'
                AND ima_tipo = '$imprimir'
                LIMIT 1";

        $res = $this->db->query($sqls);

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
                        ima_nombre='$idguia',
                        ima_datos_ticket='$jsonString'
                    WHERE ima_idservicio='$id_param2'
                        AND ima_tipo='$imprimir'";

            if (!$this->db->query($sqlUpd)) {
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

            if (!$this->db->query($sqlIns)) {
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

    public function enviarAlertaWhat($telefono, $tipo, $idservi)
    {
        $this->logEntrega("=== enviarAlertaWhat() ===");
        $this->logEntrega("Datos: tel=$telefono, tipo=$tipo, id=$idservi");

        $url = "https://bot.transmillas.com/whatsapp/Alertas/alertas.php";

        $payload = [
            "telefono"     => "$telefono",
            "id"     => "$idservi",
            "tipo_alerta"  => "$tipo"
            // "id_guia"      => "$idservi",
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

    public function calcularValorTotal($peso, $volumen, $ciudadOri, $ciudadDes, $tipoServ,$pordeclarado)
{
    // 1. buscar rango de precios según kilos
    $sql = "SELECT idprecioskilos 
            FROM precioskilos 
            WHERE '$peso' BETWEEN pre_inicial AND prec_final";

    $res = $this->db->query($sql);
    $row = ($res ? $res->fetch_row() : [1]);
    $idPrecios = $row[0] ?: 1;

    // 2. consultar precio base
    $sql2 = "SELECT p.pre_kilo, c.con_precios
             FROM precios p
             INNER JOIN configuracionkilos c ON c.con_idprecioskilos = p.idprecios
             WHERE c.con_tipo='normal'
               AND p.pre_idciudadori='$ciudadOri'
               AND p.pre_idciudaddes='$ciudadDes'
               AND p.pre_tiposervicio='$tipoServ'
               AND c.con_idprecios='$idPrecios'
             LIMIT 1";

    $res2 = $this->db->query($sql2);
    $row2 = ($res2 ? $res2->fetch_row() : [0,0]);

    $prekilo = (float)$row2[0];
    $preadicional = (float)$row2[1];

    // // 3. cálculo final EXACTO a tu lógica vieja
    // $kilos = $peso + $volumen;

    // if ($peso > 1) {
    //     $valorBase = $prekilo;
    //     $valorAdicional = ($kilos - 1) * $preadicional;
    // } else {
    //     $valorBase = $prekilo;
    //     $valorAdicional = $volumen * $preadicional;
    // }
			if($pordeclarado=='') { $pordeclarado=0; }else{
				$pordeclarado=($pordeclarado)*1/100;
			}
    // $total = $valorBase + $valorAdicional;
    		if($peso>5){
				$varor1=$prekilo;
				$valor2=($peso+$volumen-5)*$preadicional;
				$valortotal=$varor1+$valor2;
			}else {
				$varor1=$prekilo;
				$valor2=$volumen*$preadicional;
				$valortotal=$varor1+$valor2;
			}

            $valortotal=$valortotal+$pordeclarado;
    return [
        "ok" => true,
        "total" => $valortotal,
        "prekilo" => $prekilo,
        "adicional" => $preadicional
    ];
}
    private function logCalculo($mensaje)
    {
        $ruta = __DIR__ . "/log_calculos.txt";
        $fecha = date("[Y-m-d H:i:s] ");

        file_put_contents($ruta, $fecha . $mensaje . PHP_EOL, FILE_APPEND);
    }



    public function calcularValorConLogicaVieja(
        $param7,      // kilos
        $param8,      // kilos adicionales / volumen
        $param2,      // ciudad origen
        $param3,      // ciudad destino
        $valortservicio,//tipo de servicio
        $param6,      // valor declarado (con puntos)
        $idcredito,   //Cliente
        $param1,      // crédito? (1 = crédito)
        $param4=0,
        $param5=0,
        $precioinicialkilos=5,
        $tipoPago=0
    ) {
        // =========================
        // 1. LOG inicial
        // =========================
        $this->logCalculo("======== INICIO CÁLCULO ========");
        $this->logCalculo("PARAMETROS: kilos=$param7, vol=$param8, origen=$param2, destino=$param3, servicio=$valortservicio, credito=$idcredito, tipoCredito=$param1");

        // =========================
        // 2. Buscar rango de precios
        // =========================
        $kilos = $param7;

        $sqlPrecios = "
            SELECT idprecioskilos 
            FROM precioskilos 
            WHERE '$kilos' BETWEEN pre_inicial AND prec_final
        ";

        $this->logCalculo("SQL precios: $sqlPrecios");

        $resPrecios = $this->db->query($sqlPrecios);

        if (!$resPrecios) {
            $this->logCalculo("ERROR ejecutando SQL precios: " . $this->db->error);
        }

        $confipre = ($resPrecios ? $resPrecios->fetch_row() : [0]);
        $idprecios = isset($confipre[0]) ? $confipre[0] : 0;

        $this->logCalculo("ID precios encontrado: $idprecios");

        if ($idprecios == 0 || $idprecios == '') {
            $idprecios = 1;
            $this->logCalculo("ID precios forzado a 1");
        }

        // =========================
        // 3. Tipo de servicio
        // =========================
        $sql33 = "
            SELECT tip_preciokilo, tip_precioadicional 
            FROM tiposervicio 
            WHERE idtiposervicio = '$valortservicio'
        ";

        $this->logCalculo("SQL tipo servicio: $sql33");

        $res33 = $this->db->query($sql33);
        if (!$res33) {
            $this->logCalculo("ERROR ejecutando SQL tipo servicio: " . $this->db->error);
        }

        $rw7 = ($res33 ? $res33->fetch_row() : [0, 0]);

        $this->logCalculo("Precios tipo servicio: kilo={$rw7[0]}, adicional={$rw7[1]}");

        $preciokilo = 0;
        $precioadicional = 0;

        // =========================
        // 4. Condiciones de lógica
        // =========================
        $this->logCalculo("Condiciónes ".$valortservicio."== 0 && ".$param1." != 1 && ".$tipoPago."!=2");
        if ($valortservicio == 0 && $param1 != 1 && $tipoPago!=2) {
            $this->logCalculo("Condición: servicio normal SIN crédito");

            $sql = "
                SELECT p.idprecios, p.pre_kilo, c.con_precios 
                FROM precios p 
                INNER JOIN configuracionkilos c 
                    ON c.con_idprecioskilos = p.idprecios 
                WHERE c.con_tipo = 'normal' 
                AND p.pre_idciudadori  = '$param2' 
                AND p.pre_idciudaddes  = '$param3' 
                AND p.pre_tiposervicio = '$valortservicio' 
                AND c.con_idprecios    = '$idprecios'
                AND pre_estado         =  1
            ";

            $this->logCalculo("SQL normal: $sql");

            $res = $this->db->query($sql);
            if (!$res) {
                $this->logCalculo("ERROR SQL normal: " . $this->db->error);
            }

            $rw = ($res ? $res->fetch_row() : [0, 0, 0]);

            $preciokilo      = $rw[1];
            $precioadicional = $rw[2];

        } else if ($rw7[0] >= 10 && $param1 != 1 && $tipoPago!=2) {
            $this->logCalculo("Condición: carga especial SIN crédito");
            $preciokilo      = $rw7[0];
            $precioadicional = $rw7[1];

        } else if ($param1 == 1 && $tipoPago==2) {
            $this->logCalculo("Condición: CON crédito");

            $sql3 = "
                SELECT pc.pre_preciokilo, c.con_precios 
                FROM precios_credito pc
                INNER JOIN configuracionkilos c 
                    ON c.con_idprecioskilos = pc.idprecioscredito 
                WHERE c.con_tipo = 'Credito' 
                AND pc.pre_idciudadori   = '$param2' 
                AND pc.pre_idciudades    = '$param3' 
                AND pc.pre_tiposervicio  = '$valortservicio' 
                AND pc.pre_idcredito     = '$idcredito' 
                AND c.con_idprecios      = '$idprecios'
                AND pre_estado         =  1

            ";
            if (!empty($Origen)) {
                $sql3 .= " AND pc.pre_idciudadori = '$Origen'";
            }

            $this->logCalculo("SQL crédito: $sql3");

            $res3 = $this->db->query($sql3);
            if (!$res3) {
                $this->logCalculo("ERROR SQL crédito: " . $this->db->error);
            }

            $rw2 = ($res3 ? $res3->fetch_row() : [0, 0]);

            $preciokilo      = $rw2[0];
            $precioadicional = $rw2[1];

        } else {
            $this->logCalculo("Condición: caso por defecto");
            $this->logCalculo("Condición: servicio normal SIN crédito");

            $sql = "
                SELECT p.idprecios, p.pre_kilo, c.con_precios 
                FROM precios p 
                INNER JOIN configuracionkilos c 
                    ON c.con_idprecioskilos = p.idprecios 
                WHERE c.con_tipo = 'normal' 
                AND p.pre_idciudadori  = '$param2' 
                AND p.pre_idciudaddes  = '$param3' 
                AND p.pre_tiposervicio = '$valortservicio' 
                AND c.con_idprecios    = '$idprecios'
                AND pre_estado         =  1
            ";

            $this->logCalculo("SQL normal: $sql");

            $res = $this->db->query($sql);
            if (!$res) {
                $this->logCalculo("ERROR SQL normal: " . $this->db->error);
            }

            $rw = ($res ? $res->fetch_row() : [0, 0, 0]);

            $preciokilo      = $rw[1];
            $precioadicional = $rw[2];
            // misma consulta que arriba
        }

        $this->logCalculo("Precio kilo final: $preciokilo");
        $this->logCalculo("Precio adicional final: $precioadicional");

        // =========================
        // 5. Cálculos intermedios
        // =========================
        $kilosvolumen = $param7 + $param8;
        $this->logCalculo("Kilos + Volumen = $kilosvolumen");

        if ($param7 > $precioinicialkilos) {
            $this->logCalculo("Cálculo: excede kilos iniciales");
            $precio1 = ($kilosvolumen - $precioinicialkilos) * $precioadicional;
            $precio  = $preciokilo + $precio1;
        } else {
            $this->logCalculo("Cálculo: dentro de kilos iniciales");
            $precio1 = $param8 * $precioadicional;
            $precio  = $preciokilo + $precio1;
        }

        $this->logCalculo("Subtotal precio = $precio");

        // =========================
        // 6. Limpieza
        // =========================
        $param4 = str_replace(".", "", $param4);
        $param5 = str_replace(".", "", $param5);
        $param6 = str_replace(".", "", $param6);

        // =========================
        // 7. Préstamo
        // =========================
        $sqlPrestamo = "
            SELECT pre_porcentaje 
            FROM prestamo 
            WHERE pre_inicio < '$param4' 
            AND pre_final >= '$param4'
        ";

        $this->logCalculo("SQL préstamo: $sqlPrestamo");

        $resPrestamo = $this->db->query($sqlPrestamo);

        if (!$resPrestamo) {
            $this->logCalculo("ERROR SQL préstamo: " . $this->db->error);
        }

        $rowPrestamo = ($resPrestamo ? $resPrestamo->fetch_row() : [0]);
        $porprestamo = $rowPrestamo[0];

        $this->logCalculo("Porcentaje préstamo: $porprestamo");

        $dosporcentaje = explode(" ", $porprestamo);

        if (isset($dosporcentaje[1]) && $dosporcentaje[1] == '%') {
            $porprestamo = ($param4 * $dosporcentaje[0]) / 100;
        }

        $pordeclarado = (intval($param6) * 1) / 100;

        $this->logCalculo("Por prestamo: $porprestamo");
        $this->logCalculo("Por declarado: $pordeclarado");

        // =========================
        // 8. Total
        // =========================
        $valorapagar = $precio + $pordeclarado + $porprestamo;

        $this->logCalculo("TOTAL = $valorapagar");
        $this->logCalculo("======== FIN CÁLCULO ========");

        return [
            "ok" => true,
            "prekilo" => (float)$preciokilo,
            "adicional" => (float)$precioadicional,
            "pordeclarado" => (float)$pordeclarado,
            "porprestamo" => (float)$porprestamo,
            "total" => (float)$valorapagar,
            "kilosvolumen" => (float)$kilosvolumen,
            "idprecios" => (int)$idprecios,
            "valorsinseguro" => (float)$precio,


        ];
    }
    public function guardarFirmaRecogida($idservicio, $firmaBase64, $origenFirma = 'firma')
    {
        $stmtCheck = null;
        $stmtUpdate = null;
        $stmtInsert = null;
        $stmtTel = null;
        $stmtGuia = null;
        date_default_timezone_set('America/Bogota');
        $fechaHoraColombia = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/debug_guardar_firma.log';
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] === INICIO guardarFirmaRecogida() ===\n", FILE_APPEND);

        try {
            $origenFirma = strtolower((string)$origenFirma);
            if (!in_array($origenFirma, ['firma', 'sello'], true)) {
                $origenFirma = 'firma';
            }

            file_put_contents($logFile, "🟡 Paso 1: Recibido idservicio=$idservicio\n", FILE_APPEND);

            // 1️⃣ Convertir base64 a imagen (compatible con varios formatos)
            if (preg_match('/^data:image\/([a-zA-Z0-9.+-]+);base64,/', $firmaBase64, $type)) {
                $firmaData = substr($firmaBase64, strpos($firmaBase64, ',') + 1);
                $type = strtolower($type[1]); // jpg, jpeg, png, webp...

                $firmaData = str_replace(' ', '+', $firmaData);
                $imagen = base64_decode($firmaData, true);

                if ($imagen === false) {
                    file_put_contents($logFile, "❌ Error al decodificar base64\n", FILE_APPEND);
                    return false;
                }

                if (strlen($imagen) > 9 * 1024 * 1024) {
                    file_put_contents($logFile, "Imagen demasiado pesada\n", FILE_APPEND);
                    return false;
                }

                // Validar extensión permitida
                if (!in_array($type, ['jpg','jpeg','png','webp'])) {
                    file_put_contents($logFile, "❌ Tipo de imagen no permitido: $type\n", FILE_APPEND);
                    return false;
                }

            } else {
                file_put_contents($logFile, "❌ Base64 inválido\n", FILE_APPEND);
                return false;
            }

            if ($imagen === false) {
                file_put_contents($logFile, "❌ Error al decodificar base64\n", FILE_APPEND);
                return false;
            }

            $infoImagen = @getimagesizefromstring($imagen);
            if (!$infoImagen || empty($infoImagen['mime'])) {
                file_put_contents($logFile, "Contenido de imagen invalido\n", FILE_APPEND);
                return false;
            }

            $extensionesPorMime = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($extensionesPorMime[$infoImagen['mime']])) {
                file_put_contents($logFile, "MIME no permitido: " . $infoImagen['mime'] . "\n", FILE_APPEND);
                return false;
            }

            $extensionArchivo = $extensionesPorMime[$infoImagen['mime']];

            // 2️⃣ Crear carpeta si no existe
            $rutaCarpeta = __DIR__ . '/../../firmas_clientes/';
            if (!file_exists($rutaCarpeta)) {
                if (!mkdir($rutaCarpeta, 0777, true)) {
                    file_put_contents($logFile, "❌ Error al crear carpeta\n", FILE_APPEND);
                    return false;
                }
            }

            // 3️⃣ Guardar archivo
            $prefijoArchivo = ($origenFirma === 'sello') ? 'sello' : 'firma';
            $nombreArchivo = $prefijoArchivo . '_' . (int)$idservicio . '_' . time() . '.' . $extensionArchivo;
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            $rutaArchivoGuardar = "firmas_clientes/" . $nombreArchivo;

            if (file_put_contents($rutaArchivo, $imagen) === false) {
                file_put_contents($logFile, "❌ Error al guardar imagen\n", FILE_APPEND);
                return false;
            }

            file_put_contents($logFile, "✅ Imagen guardada: $rutaArchivoGuardar\n", FILE_APPEND);

            $tipoFirma = 'Recogida';


            // 🔎 4️⃣ Verificar si ya existe firma para ese servicio y tipo
            $sqlCheck = "SELECT id, firma_clientes FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bind_param('is', $idservicio, $tipoFirma);
            $stmtCheck->execute();
            $result = $stmtCheck->get_result();

            if ($result->num_rows > 0) {
                // ✏️ YA EXISTE → HACER UPDATE
                $row = $result->fetch_assoc();
                $idFirma = $row['id'];
                $rutaAnterior = $row['firma_clientes'] ?? '';

                file_put_contents($logFile, "🟠 Firma existente encontrada (ID=$idFirma), actualizando...\n", FILE_APPEND);
                $activoParaFirma=0;
                $sqlUpdate = "UPDATE firma_clientes 
                            SET firma_clientes = ?, fecha_registro = ? , activo_para_firmar = ?, tipo = ?
                            WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                
                $stmtUpdate->bind_param('ssisi', $rutaArchivoGuardar, $fechaHoraColombia,$activoParaFirma, $origenFirma, $idFirma);


                if (!$stmtUpdate->execute()) {
                    file_put_contents($logFile, "❌ Error en UPDATE: " . $stmtUpdate->error . "\n", FILE_APPEND);
                    return false;
                }
                                // 🔎 Obtener teléfono desde firma_clientes
                $sqlTel = "SELECT telefono FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
                $stmtTel = $this->db->prepare($sqlTel);
                $stmtTel->bind_param('is', $idservicio, $tipoFirma);
                $stmtTel->execute();
                $resTel = $stmtTel->get_result();
                $telefono = ($resTel->num_rows > 0) ? $resTel->fetch_assoc()['telefono'] : null;

                // 🔎 Obtener número de guía desde servicios
                $sqlGuia = "SELECT ser_consecutivo FROM servicios WHERE idservicios = ? LIMIT 1";
                $stmtGuia = $this->db->prepare($sqlGuia);
                $stmtGuia->bind_param('i', $idservicio);
                $stmtGuia->execute();
                $resGuia = $stmtGuia->get_result();
                $numguia = ($resGuia->num_rows > 0) ? $resGuia->fetch_assoc()['ser_consecutivo'] : null;


                // $this->enviarGuiaWhat( $telefono, 42, $numguia."R");

                file_put_contents($logFile, "✅ Firma actualizada correctamente\n", FILE_APPEND);
                $this->eliminarArchivoFirmaAnterior($rutaAnterior, $rutaArchivoGuardar, $logFile);
                return true;

            } else {
                // ➕ NO EXISTE → INSERTAR
                $activoParaFirma=0;
                file_put_contents($logFile, "🟢 No existe firma previa, insertando nueva...\n", FILE_APPEND);

                $sqlInsert = "INSERT INTO firma_clientes (id_guia, tipo_firma, firma_clientes, fecha_registro, activo_para_firmar, tipo) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                
                $stmtInsert->bind_param('isssis', $idservicio, $tipoFirma, $rutaArchivoGuardar, $fechaHoraColombia,$activoParaFirma, $origenFirma);


                if (!$stmtInsert->execute()) {
                    file_put_contents($logFile, "❌ Error en INSERT: " . $stmtInsert->error . "\n", FILE_APPEND);
                    return false;
                }

                // 🔎 Obtener teléfono desde firma_clientes
                // $sqlTel = "SELECT telefono FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
                // $stmtTel = $this->db->prepare($sqlTel);
                // $stmtTel->bind_param('is', $idservicio, $tipoFirma);
                // $stmtTel->execute();
                // $resTel = $stmtTel->get_result();
                // $telefono = ($resTel->num_rows > 0) ? $resTel->fetch_assoc()['telefono'] : null;

                // 🔎 Obtener número de guía desde servicios
                // $sqlGuia = "SELECT ser_consecutivo FROM servicios WHERE idservicios = ? LIMIT 1";
                // $stmtGuia = $this->db->prepare($sqlGuia);
                // $stmtGuia->bind_param('i', $idservicio);
                // $stmtGuia->execute();
                // $resGuia = $stmtGuia->get_result();
                // $numguia = ($resGuia->num_rows > 0) ? $resGuia->fetch_assoc()['ser_consecutivo'] : null;
                // $this->enviarGuiaWhat( $telefono, 42, $numguia."R");
                file_put_contents($logFile, "✅ Firma insertada correctamente\n", FILE_APPEND);
                return true;
            }

            
            
        } catch (Exception $e) {
            file_put_contents($logFile, "❌ Excepción: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        } finally {
            // Cerrar statements abiertos
            if ($stmtCheck instanceof mysqli_stmt) { $stmtCheck->close(); }
            if ($stmtUpdate instanceof mysqli_stmt) { $stmtUpdate->close(); }
            if ($stmtInsert instanceof mysqli_stmt) { $stmtInsert->close(); }
            if ($stmtTel instanceof mysqli_stmt) { $stmtTel->close(); }
            if ($stmtGuia instanceof mysqli_stmt) { $stmtGuia->close(); }

            file_put_contents($logFile, "=== FIN guardarFirmaRecogida() ===\n\n", FILE_APPEND);
        }
    }

    private function eliminarArchivoFirmaAnterior($rutaAnterior, $rutaNueva, $logFile)
    {
        if (empty($rutaAnterior) || $rutaAnterior === $rutaNueva) {
            return;
        }

        $rutaAnteriorNormalizada = str_replace('\\', '/', $rutaAnterior);
        if (strpos($rutaAnteriorNormalizada, 'firmas_clientes/') !== 0) {
            return;
        }

        $rutaBase = realpath(__DIR__ . '/../../firmas_clientes/');
        if (!$rutaBase) {
            return;
        }

        $nombreArchivo = basename($rutaAnteriorNormalizada);
        $rutaCompleta = realpath($rutaBase . DIRECTORY_SEPARATOR . $nombreArchivo);

        if (!$rutaCompleta || strpos($rutaCompleta, $rutaBase) !== 0 || !is_file($rutaCompleta)) {
            return;
        }

        if (!@unlink($rutaCompleta)) {
            file_put_contents($logFile, "No se pudo eliminar archivo anterior: $rutaAnterior\n", FILE_APPEND);
        }
    }

    public function reEnviarFirmaWhat($telefono, $tipo, $idservi,$link)
    {
        $this->logEntrega("=== enviarAlertaWhat() ===");
        $this->logEntrega("Datos: tel=$telefono, tipo=$tipo, id=$idservi");

        $url = "https://bot.transmillas.com/whatsapp/Alertas/alertas.php";

        $payload = [
            "telefono"     => "$telefono",
            "id"     => "$idservi",
            "tipo_alerta"  => "$tipo",
            "texto1"      => "$idservi",
            "texto2"      => "$link",
            "texto3"      => "Recogido"

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


    private function guardarUbicacionServicio(
        int $idservicios,
        int $idusuario,
        string $tipoEvento,
        ?array $data = []
    ): void {
        try {
            $data = is_array($data) ? $data : [];
            $latitud   = isset($data['latitud']) ? (float)$data['latitud'] : 0;
            $longitud  = isset($data['longitud']) ? (float)$data['longitud'] : 0;
            $precision = isset($data['precision_gps']) ? (float)$data['precision_gps'] : 0;

            if ($latitud == 0 || $longitud == 0) {
                // $this->logServicio('GPS NO ENVIADO', ['idservicio' => $idservicios]);
                return;
            }

            $fecha = date("Y-m-d H:i:s");
            $tipoEventoEsc = $this->esc($tipoEvento);
            $fechaEsc = $this->esc($fecha);

            $sqlExiste = "SELECT id
                          FROM servicios_ubicaciones
                          WHERE idservicios = '".(int)$idservicios."'
                            AND tipo_evento = '".$tipoEventoEsc."'
                          LIMIT 1";

            $resExiste = $this->db->query($sqlExiste);
            $registroExistente = $resExiste ? $resExiste->fetch_assoc() : null;

            if ($registroExistente && !empty($registroExistente['id'])) {
                $sql = "UPDATE servicios_ubicaciones
                        SET idusuario = '".(int)$idusuario."',
                            latitud = '".$this->esc((string)$latitud)."',
                            longitud = '".$this->esc((string)$longitud)."',
                            precision_metros = '".$this->esc((string)$precision)."',
                            fecha_registro = '".$fechaEsc."'
                        WHERE id = '".(int)$registroExistente['id']."'";
            } else {
                $sql = "INSERT INTO servicios_ubicaciones (
                            idservicios, idusuario, tipo_evento,
                            latitud, longitud, precision_metros, fecha_registro
                        ) VALUES (
                            '".(int)$idservicios."',
                            '".(int)$idusuario."',
                            '".$tipoEventoEsc."',
                            '".$this->esc((string)$latitud)."',
                            '".$this->esc((string)$longitud)."',
                            '".$this->esc((string)$precision)."',
                            '".$fechaEsc."'
                        )";
            }

            $this->db->query($sql);

            // $this->logServicio('GPS GUARDADO', [
            //     'servicio' => $idservicios,
            //     'evento' => $tipoEvento,
            //     'lat' => $latitud,
            //     'lng' => $longitud
            // ]);

        } catch (\Throwable $e) {
            // $this->logServicio('ERROR GPS', ['mensaje' => $e->getMessage()]);
        }
    }

    public function existeFirmaEntregaPublica($idservicio) {
        $id = (int)$idservicio;

        $sql = "SELECT activo_para_firmar, firma_clientes
                FROM firma_clientes
                WHERE tipo_firma='Recogida' AND id_guia='$id' AND firma_clientes!=''
                ORDER BY id DESC
                LIMIT 1";

        $res = $this->db->query($sql);

        if (!$res || $res->num_rows === 0) {
            return false;
        }

        $row = $res->fetch_assoc();

        // Si ya quedó en 0, se considera firmada.
        if (isset($row['activo_para_firmar']) && (int)$row['activo_para_firmar'] === 0) {
            return true;
        }

        // Respaldo: si hay ruta/valor de firma guardada.
        return !empty($row['firma_clientes']);
    }
}
