<?php
// require_once "../config/database.php";
require_once __DIR__ . '/../config/database.php';

class RecogerEnOficinaModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function obtenerCiudadesRemitente($id_sedes,$nivel_acceso) {
        $cond = "";
        if($nivel_acceso!=1){
            $cond = " WHERE inner_sedes='" . (int)$id_sedes . "' and inner_estados=1";
        }
        
        $sql = "SELECT `idciudades`, `ciu_nombre` FROM `ciudades`  $cond";
        
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function obtenerCiudades() {
        $sql = "SELECT `idciudades`, `ciu_nombre` FROM `ciudades`  where inner_estados=1 ";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    public function obtenerDirecciones() {
        $sql = "SELECT `iddireccion`, `dir_nombre` FROM `direccion` ";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function obtenerLugar() {
        $sql = "SELECT `idlugar`, `lug_nombre` FROM `lugar` where estado='1'";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerTiposPaqueteOficina() {
        $sql = "SELECT `tip_nombre` FROM `tipo` ORDER BY `tip_nombre`";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerCreditosPorTelefonos($telefonos)
    {
        $sql = "
            SELECT idcreditos, cre_nombre
            FROM creditos
            INNER JOIN rel_crecli ON rel_idcredito = idcreditos
            INNER JOIN clientesdir ON idclientesdir = rel_idcliente
            WHERE cli_telefono IN ($telefonos)
            GROUP BY idcreditos
        ";

        $res = $this->db->query($sql);

        if (!$res || $res->num_rows === 0) {
            return false;
        }

        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }
    public function obtenerTipoServicio($ori, $des, $tipoPago, $credito = null)
    {
        if ($tipoPago === 'credito') {

            if (!$credito) return [];

            $sql = "
                SELECT 
                    pre_tiposervicio AS id,
                    tip_nom          AS nombre
                FROM precios_credito
                INNER JOIN tiposervicio
                    ON idtiposervicio = pre_tiposervicio
                WHERE pre_idciudadori = $ori
                AND pre_idciudades  = $des
                AND pre_idcredito   = '$credito'
                AND pre_estado         =  1
                
            ";

        } else {

            $sql = "
                SELECT 
                    pre_tiposervicio AS id,
                    tip_nom          AS nombre
                FROM precios
                INNER JOIN tiposervicio
                    ON idtiposervicio = pre_tiposervicio
                WHERE pre_idciudadori = $ori
                AND pre_idciudaddes = $des
                AND pre_estado         =  1
                
                ORDER BY tip_nom
            ";
        }

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    private function logCalculo($mensaje)
    {
        return;
        $ruta = __DIR__ . "/../logs/log_calculos.txt";
        $fecha = date("[Y-m-d H:i:s] ");

        file_put_contents($ruta, $fecha . $mensaje . PHP_EOL, FILE_APPEND);
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
            if (isset($Origen) && !empty($Origen)) {
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
            // AND pre_estado         =  1

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

//GUARDAR SERVICIO
/* ==========================================================
   GUARDAR RECOGIDA (MISMA LOGICA VIEJA, mysqli NUEVO)
   ========================================================== */
    public function guardarRecogidaConLogicaVieja(array $data, array $files, array $session)
    {
                try {

                        // $this->logServicio('INICIO guardarRecogida', [
                        //     'variableunica' => $data['variableunica'] ?? null,
                        //     'usuario' => $session['usuario_id'] ?? null
                        // ]);
                    // --------------------------------------------------
                    // 0) Datos base
                    // --------------------------------------------------
                    $fechatiempo      = date("Y-m-d H:i:s");
                    $id_usuario       = (int)($data['id_usuario'] ?? ($session['usuario_id'] ?? 0));
                    $id_nombre        = $session['usuario_nombre'] ?? '';
                    $nivel_acceso     = (int)($session['usuario_rol'] ?? 0);
                    $id_sedes         = (int)($session['usu_idsede'] ?? 0);
                    $precioinicialkilos = (float)($session['precioinicial'] ?? 5);

                    $variableunica = $data['variableunica'] ?? '';
                    if (!$variableunica) {
                        return ['ok' => false, 'mensaje' => 'variableunica es requerida'];
                    }

                    // params del form (mismos nombres)
                    $param1   = $data['param1']   ?? '';   // doc remitente
                    $param2   = $data['param2']   ?? '';   // tel remitente
                    $param3   = $data['param3']   ?? '';   // email remitente
                    $param4   = $data['param4']   ?? '';   // ciudad origen
                    $param5   = $data['param5']   ?? '';   // dir (select)
                    $param51  = $data['param51']  ?? '';   // dir texto
                    $param19  = $data['selectComplemento']  ?? '';   // lugar (select)
                    $param20  = $data['param20']  ?? '';   // lugar texto
                    $param23  = $data['param23']  ?? '';   // barrio
                    $param6   = $data['param6']   ?? '';   // nombre remitente
                    $param61  = $data['param61']  ?? '';   // select remitente
                    $dir1R     = $data['dir1R']  ?? '';   // #
                    $dir2R     = $data['dir2R']  ?? '';   // -
                    $dir3R     = $data['dir3R']  ?? '';   // 
                    $dir_complementoR   = $data['dir_complemento_detalle']  ?? '';   //especifico 
                    $complemento_detalle_final = $data['complemento_detalle_final']  ?? '';




                    $param7   = $data['param7']   ?? '';   // doc destinatario
                    $param8   = $data['param8']   ?? '';   // tel destinatario
                    $param9   = $data['param9']   ?? '';   // nombre destinatario
                    $param11  = $data['param11']  ?? '';   // ciudad destino
                    $param10  = $data['param10']  ?? '';   // dir contacto (select)
                    $param101 = $data['param101'] ?? '';   // dir contacto texto
                    $param21  = $data['param21']  ?? '';   // lugar entrega (select)
                    $param22  = $data['param22']  ?? '';   // lugar entrega texto
                    $param24  = $data['param24']  ?? '';   // barrio destinatario

                    $dir1D    = $data['dir1D']  ?? '';   // #
                    $dir2D    = $data['dir2D']  ?? '';   // -
                    $dir3D    = $data['dir3D']  ?? '';   // 
                    $dir_complementoD  = $data['dir_complemento_detalleD']  ?? '';   //especifico 
                    $complemento_detalle_finalD = $data['complemento_detalle_finalD']  ?? '';


                    $param12  = $data['param12']  ?? '';   // tipo paquete
                    $param13  = $data['param13']  ?? '';   // contiene
                    $param16  = $data['param16']  ?? '';   // guia (puede venir vacía)
                    $param29  = (int)($data['param29'] ?? 1); // piezas
                    $param31  = $data['param31']  ?? '';   // estado paquete
                    $param32  = $data['param32']  ?? '';   // verificado
                    $param33  = $data['param33']  ?? '';   // tipo

                    $param17  = $data['param17']  ?? '0';  // abono
                    $param18  = $data['param18']  ?? '0';  // seguro
                    $param26  = (float)($data['param26'] ?? 0); // peso
                    $param27  = (float)($data['param27'] ?? 0); // volumen
                    $param25  = $data['param25']  ?? 0;    // retorno
                    $param28  = $data['param28']  ?? '';   // tipo pago (contado/credito/cobro)
                    $param113 = $data['param113'] ?? '';   // tipo servicio (o id servicio) / o credito segun tu flujo
                    $param15  = $data['param15']  ?? 'Envio Oficina';

                    $param91  = $files['param91'] ?? null; // foto
                    $param40  = $files['imagen_transaccion'] ?? null; // foto transaccion

                    $param92  = $data['param92']  ?? '';
                    $param93  = $data['param93']  ?? '';

                    // hidden ids
                    $id_param  = (int)($data['id_param'] ?? 0);   // idclientes remitente
                    $id_param2 = (int)($data['id_param2'] ?? 0);  // idclientesdir remitente
                    $id_param0 = (int)($data['id_param0'] ?? 0);  // idclientesdir destinatario

                    $metodo_pago  = $data['metodo_pago']  ?? '';

                    if (in_array($metodo_pago, ['DV', 'NQ'], true) && (
                        !$param40 ||
                        !isset($param40['tmp_name']) ||
                        !is_uploaded_file($param40['tmp_name']) ||
                        (($param40['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
                    )) {
                        return [
                            'ok' => false,
                            'mensaje' => 'Debe adjuntar la imagen de la transaccion para Davivienda o Bancolombia.'
                        ];
                    }


                    

                    // --------------------------------------------------
                    // 1) Evitar duplicado por variableunica (IGUAL)
                    // --------------------------------------------------
                    $sqlDup = "SELECT ser_guiare FROM servicios WHERE ser_idregistro='" . $this->escape($variableunica) . "' LIMIT 1";
                    $ya = $this->fetchValue($sqlDup);
                    if (!empty($ya)) {
                        return ['ok' => false, 'mensaje' => 'Ya existe un registro con esa variableunica', 'guia' => $ya];
                    }

                    if (!empty($ya)) {
                        // $this->logServicio('DUPLICADO variableunica', [
                        //     'variableunica' => $variableunica,
                        //     'guia' => $ya
                        // ]);
                        return ['ok' => false, 'mensaje' => 'Ya existe un registro', 'guia' => $ya];
                    }
                    // --------------------------------------------------
                    // 2) Normalizaciones como viejo
                    // --------------------------------------------------
                    $param17 = str_replace(".", "", (string)$param17);
                    $param18 = str_replace(".", "", (string)$param18);
                    $pordeclarado = ((int)$param18 * 1) / 100;

                    if ($param27 === '' || $param27 === null) $param27 = 0;

                    // Direcciones con &
                    // $dirRem = $param5 . "&" . $param51 . "&" . $param19 . "&" . $param20 . "&" . $param23 . "&";
                    $dirRem = $param5 . "&".$dir1R."#".$dir2R."-".$dir3R. "&" . $param19 . "&" . $dir_complementoR . "&" . $param23 . "&".$complemento_detalle_final;
                    // $dirDes = $param10 . "&" . $param101 . "&" . $param21 . "&" . $param22 . "&" . $param24 . "&";
                    $dirDes = $param10 . "&".$dir1D."#".$dir2D."-".$dir3D. "&" . $param21 . "&" . $dir_complementoD . "&" . $param24 . "&".$complemento_detalle_finalD;

                    $dirRem = str_replace('&0&','&&', $dirRem);
                    $dirDes = str_replace('&0&','&&', $dirDes);

                    // --------------------------------------------------
                    // 3) Estado (mismo criterio)
                    // --------------------------------------------------
                    $estado = 6;
                    if ($nivel_acceso == 3) {
                        $estado = 4;
                        $param15 = "Recogida Operador";
                    }

                    // --------------------------------------------------
                    // 4) Guardar/Actualizar remitente (clientes + clientesdir)
                    // --------------------------------------------------
                    // NOTA: en viejo usas if($id_param==0 and $id_param==0) (parece typo),
                    // aquí lo interpreto como: si NO existe remitente.
                    if ($id_param <= 0) {
                        // Insert clientes
                        $sqlInsCli = "INSERT INTO clientes (cli_iddocumento, cli_email, cli_clasificacion, cli_retorno, cli_tipo, cli_fecharegistro)
                                    VALUES (
                                        '" . $this->escape($param1) . "',
                                        '" . $this->escape($param3) . "',
                                        '0',
                                        '" . $this->escape($param25) . "',
                                        2,
                                        '" . $this->escape($fechatiempo) . "'
                                    )";
                        $idclientes = $this->insert($sqlInsCli);

                        // Insert clientesdir
                        $sqlInsDir = "INSERT INTO clientesdir (cli_nombre, cli_telefono, cli_idciudad, cli_direccion, cli_idclientes, cli_principal)
                                    VALUES (
                                        '" . $this->escape($param6) . "',
                                        '" . $this->escape($param2) . "',
                                        '" . $this->escape($param4) . "',
                                        '" . $this->escape($dirRem) . "',
                                        '" . (int)$idclientes . "',
                                        1
                                    )";
                        $idclientesdir = $this->insert($sqlInsDir);

                        $id_param  = (int)$idclientes;
                        $id_param2 = (int)$idclientesdir;

                    } else {
                        // Update clientes
                        $sqlUpCli = "UPDATE clientes SET
                                        cli_iddocumento='" . $this->escape($param1) . "',
                                        cli_email='" . $this->escape($param3) . "',
                                        cli_clasificacion='0',
                                        cli_tipo=2,
                                        cli_fecharegistro='" . $this->escape($fechatiempo) . "',
                                        cli_retorno='" . $this->escape($param25) . "'
                                    WHERE idclientes='" . (int)$id_param . "'";
                        $this->db->query($sqlUpCli);

                        // Update clientesdir
                        $sqlUpDir = "UPDATE clientesdir SET
                                        cli_nombre='" . $this->escape($param6) . "',
                                        cli_telefono='" . $this->escape($param2) . "',
                                        cli_idciudad='" . $this->escape($param4) . "',
                                        cli_direccion='" . $this->escape($dirRem) . "',
                                        cli_idclientes='" . (int)$id_param . "',
                                        cli_principal=1
                                    WHERE idclientesdir='" . (int)$id_param2 . "'";
                        $this->db->query($sqlUpDir);
                    }
                    // $this->logServicio('CLIENTE REMITENTE', [
                    //     'idclientes' => $id_param,
                    //     'idclientesdir' => $id_param2
                    // ]);

                    // Insert clientesservicios (igual)
                    $sqlCliServ = "INSERT INTO clientesservicios (cli_nombre, cli_telefono, cli_idciudad, cli_direccion, cli_idclientes, cli_principal)
                                VALUES (
                                    '" . $this->escape($param6) . "',
                                    '" . $this->escape($param2) . "',
                                    '" . $this->escape($param4) . "',
                                    '" . $this->escape($dirRem) . "',
                                    '" . (int)$id_param . "',
                                    1
                                )";
                    $idcli2 = $this->insert($sqlCliServ);

                    // --------------------------------------------------
                    // 5) Guardar/Actualizar destinatario si hay teléfono (igual)
                    // --------------------------------------------------
                    if ($param8 !== '') {
                        $sqlExiste = "SELECT idclientes
                                    FROM clientes
                                    INNER JOIN clientesdir ON cli_idclientes=idclientes
                                    WHERE cli_telefono='" . $this->escape($param8) . "'
                                    LIMIT 1";
                        $valorinser = (int)($this->fetchValue($sqlExiste) ?? 0);

                        if ($valorinser <= 0) {
                            // Inserta cliente "tipo 0"
                            $sqlInsCliD = "INSERT INTO clientes (cli_tipo, cli_iddocumento, cli_email, cli_fecharegistro)
                                        VALUES (0, '" . $this->escape($param7) . "', '', '" . $this->escape($fechatiempo) . "')";
                            $idcliDest = $this->insert($sqlInsCliD);

                            $sqlInsDirD = "INSERT INTO clientesdir (cli_nombre, cli_telefono, cli_idciudad, cli_direccion, cli_idclientes, cli_principal)
                                        VALUES (
                                            '" . $this->escape($param9) . "',
                                            '" . $this->escape($param8) . "',
                                            '" . $this->escape($param11) . "',
                                            '" . $this->escape($dirDes) . "',
                                            '" . (int)$idcliDest . "',
                                            0
                                        )";
                            $this->insert($sqlInsDirD);

                        } else {
                            // Update cliente
                            $sqlUpCliD = "UPDATE clientes SET
                                            cli_tipo=0,
                                            cli_fecharegistro='" . $this->escape($fechatiempo) . "',
                                            cli_iddocumento='" . $this->escape($param7) . "'
                                        WHERE idclientes='" . (int)$valorinser . "'";
                            $this->db->query($sqlUpCliD);

                            // Update clientesdir
                            $sqlUpDirD = "UPDATE clientesdir SET
                                            cli_nombre='" . $this->escape($param9) . "',
                                            cli_telefono='" . $this->escape($param8) . "',
                                            cli_idciudad='" . $this->escape($param11) . "',
                                            cli_direccion='" . $this->escape($dirDes) . "',
                                            cli_principal=0
                                        WHERE cli_idclientes='" . (int)$valorinser . "'";
                            $this->db->query($sqlUpDirD);
                        }
                    }
                    // $this->logServicio('DESTINATARIO PROCESADO', [
                    //     'telefono' => $param8,
                    //     'ciudad' => $param11
                    // ]);

                    // --------------------------------------------------
                    // 6) Consecutivo / planilla (igual)
                    // --------------------------------------------------
                    $planilla = "";
                    $idconsecutivo = 0;

                    $sqlConf = "SELECT idconfac, idconsecutivo, idresolucion, prefijo
                                FROM conf_fac
                                INNER JOIN ciudades ON idciudad=inner_sedes
                                WHERE idciudades='" . $this->escape($param4) . "'
                                LIMIT 1";
                    $rw1 = $this->fetchOne($sqlConf);

                    

                    if ($rw1) {
                        $planilla = ($rw1['prefijo'] ?? '') . ($rw1['idconsecutivo'] ?? '');
                        $idconsecutivo = ((int)($rw1['idconsecutivo'] ?? 0)) + 1;

                        if ($idconsecutivo >= 10) {
                            $sqlUpConf = "UPDATE conf_fac c
                                        INNER JOIN ciudades cc ON c.idciudad=cc.inner_sedes
                                        SET c.idconsecutivo=" . (int)$idconsecutivo . "
                                        WHERE cc.idciudades='" . $this->escape($param4) . "'";
                            $this->db->query($sqlUpConf);
                        } else {
                            $planilla = ""; // igual que viejo
                        }
                    }

                    if ($param16 == '') {
                        $param16 = $planilla; // igual
                    }

                    // $this->logServicio('PLANILLA', [
                    //     'planilla' => $planilla,
                    //     'consecutivo' => $idconsecutivo
                    // ]);

                    // --------------------------------------------------
                    // 7) Precio (aquí tienes 2 opciones)
                    //    A) Usar param111 que ya calculaste en front
                    //    B) Recalcular con tu método calcularValorConLogicaVieja()
                    // --------------------------------------------------
                    $precio = (float)str_replace(".", "", (string)($data['param111'] ?? 0));
                    $valorsinseguro = (float)str_replace(".", "", (string)($data['valorSinSeguro'] ?? 0));
                    // Si quieres recalcular SIEMPRE (más parecido al viejo), descomenta y adapta:
                    /*
                    $tipoPagoNum = ($param28 === 'credito') ? 2 : 0; // si tu viejo usaba 2
                    $esCredito   = ($param28 === 'credito') ? 1 : 0;
                    $calc = $this->calcularValorConLogicaVieja(
                        $param26, $param27, $param4, $param11,
                        $param113, $param18,
                        $param28 === 'credito' ? $param113 : 0, // ojo: aquí define bien idcredito
                        $esCredito,
                        0,0,$precioinicialkilos,$tipoPagoNum
                    );
                    $precio = (float)($calc['valorsinseguro'] ?? 0);
                    */

                    // --------------------------------------------------
                    // 8) Subir foto param91 (igual)
                    // --------------------------------------------------
                    $foto = "";
                    if ($param91 && isset($param91['tmp_name']) && is_uploaded_file($param91['tmp_name'])) {
                        $foto = $this->uploadFile($param91, __DIR__ . "/../../imgServicios/");
                    }

                    // --------------------------------------------------
                    // 9) Insert servicios (igual estructura)
                    // --------------------------------------------------
                    $sqlServ = "INSERT INTO servicios (
                        ser_iddocumento, ser_telefonocontacto, ser_destinatario, ser_direccioncontacto, ser_ciudadentrega,
                        ser_tipopaquete, ser_paquetedescripcion, ser_fechaentrega, ser_prioridad,
                        ser_guiare, ser_valorabono, ser_valorseguro, ser_fecharegistro, ser_peso, ser_volumen,
                        ser_idverificado, ser_idresponsable, ser_valor, ser_estado, ser_visto, ser_consecutivo,
                        ser_pendientecobrar, ser_fechafinal, ser_clasificacion, ser_idverificadopeso, ser_piezas,
                        ser_descripcion, ser_verificado, ser_tipopaq, ser_idregistro, ser_devolverreci,
                        ser_fechaasignacion, ser_img_recog
                    ) VALUES (
                        '" . $this->escape($param7) . "', '" . $this->escape($param8) . "', '" . $this->escape($param9) . "', '" . $this->escape($dirDes) . "', '" . $this->escape($param11) . "',
                        '" . $this->escape($param12) . "', '" . $this->escape($param13) . "', '', '" . $this->escape($param15) . "',
                        '" . $this->escape($param16) . "', '" . $this->escape($param17) . "', '" . $this->escape($param18) . "', '" . $this->escape($fechatiempo) . "',
                        '" . $this->escape($param26) . "', '" . $this->escape($param27) . "',
                        '" . (int)$id_usuario . "', '" . (int)$id_usuario . "', '" . $this->escape($valorsinseguro) . "', '" . (int)$estado . "', 0, '" . $this->escape($planilla) . "',
                        0, '" . $this->escape($fechatiempo) . "', '" . $this->escape($param28) . "', 0, '" . (int)$param29 . "',
                        '" . $this->escape($param31) . "', '" . $this->escape($param32) . "', '" . $this->escape($param33) . "', '" . $this->escape($variableunica) . "', '" . $this->escape($param25) . "',
                        '" . $this->escape($fechatiempo) . "', '" . $this->escape($foto) . "'
                    )";

                
                    $idser = $this->insert($sqlServ);
                    // 📍 Guardar ubicación GPS de la recogida
                    $this->guardarUbicacionServicio($idser, $id_usuario, "RECOGIDA",$data);

                    // $this->logServicio('SERVICIO INSERTADO', [
                    //     'idservicio' => $idser,
                    //     'guia' => $param16,
                    //     'precio' => $precio,
                    //     'estado' => $estado
                    // ]);
                    // --------------------------------------------------
                    // 10) rel_sercli (igual)
                    // --------------------------------------------------
                    $sqlRel = "INSERT INTO rel_sercli (ser_idclientes, ser_idservicio, ser_fechaingreso)
                            VALUES (" . (int)$idcli2 . ", " . (int)$idser . ", '" . $this->escape($fechatiempo) . "')";
                    $this->db->query($sqlRel);

                    // --------------------------------------------------
                    // 11) Insert guias (igual)
                    // --------------------------------------------------
                    $sqlGuia = "INSERT INTO guias (
                        gui_idservicio, gui_idusuario, gui_usucreado, gui_fechacreacion,
                        gui_recogio, gui_fecharecogio, gui_tiposervicio,
                        gui_usuvalida, gui_fechavalidacion, gui_usurecogida, gui_fecharecogida
                    ) VALUES (
                        " . (int)$idser . ", '" . (int)$id_usuario . "', '" . $this->escape($id_nombre) . "', '" . $this->escape($fechatiempo) . "',
                        '" . $this->escape($id_nombre) . "', '" . $this->escape($fechatiempo) . "', '" . $this->escape($param113) . "',
                        '" . $this->escape($id_nombre) . "', '" . $this->escape($fechatiempo) . "',
                        '" . $this->escape($id_nombre) . "', '" . $this->escape($fechatiempo) . "'
                    )";
                    $this->db->query($sqlGuia);

                    // --------------------------------------------------
                    // 12) Abono (igual)
                    // --------------------------------------------------
                    if ((float)$param17 > 0) {
                        $sqlAbo = "INSERT INTO abonosguias (abo_fecha, abo_valor, abo_idservicio, abo_iduser, abo_idsede, abo_estado)
                                VALUES (
                                    '" . $this->escape($fechatiempo) . "',
                                    '" . $this->escape($param17) . "',
                                    " . (int)$idser . ",
                                    " . (int)$id_usuario . ",
                                    " . (int)$id_sedes . ",
                                    'abono'
                                )";
                        $this->db->query($sqlAbo);
                    }

                    // --------------------------------------------------
                    // 13) Crédito -> rel_sercre (igual concepto)
                    // --------------------------------------------------
                    // En tu nuevo flujo: si tipo pago = credito, param113 debería ser el ID del crédito o el crédito viene en cliente_credito
                    if ($param28 === '2') {
                        // $data['cliente_credito'] debería venir con idcreditos
                        $idcredito = $data['cliente_credito'] ?? $param113;

                        // Buscar nombre crédito (igual a viejo)
                        $sqlNom = "SELECT cre_nombre FROM creditos WHERE idcreditos='" . $this->escape($idcredito) . "' LIMIT 1";
                        $nombrecredito = $this->fetchValue($sqlNom);

                        // borrar previos (igual)
                        $this->db->query("DELETE FROM rel_sercre WHERE idservicio=" . (int)$idser);

                        if (!empty($nombrecredito)) {
                            $sqlRelCre = "INSERT INTO rel_sercre (idservicio, rel_nom_credito)
                                        VALUES (" . (int)$idser . ", '" . $this->escape($nombrecredito) . "')";
                            $this->db->query($sqlRelCre);
                        }
                        if ($param28 === '2') {
                            // $this->logServicio('SERVICIO A CRÉDITO', [
                            //         'idservicio' => $idser,
                            //         'idcredito' => $idcredito
                            //     ]);
                            }
                    }

                    // --------------------------------------------------
                    // 14) cuentaspromotor (igual idea, aquí mínimo)
                    //     (En viejo hay muchos campos; si los necesitas exactos me dices
                    //      y lo dejo 1:1 con tus nombres reales del form)
                    // --------------------------------------------------
                    $pagot="";
                    if ($metodo_pago=="DV") {
                            $pagot="DAVIVIENDA  AHORROS DAVIPLATA";
                            
                    }elseif ($metodo_pago=="NQ") {
                            $pagot="BANCOLOMBIA CORRIENTE  NEQUI";
                            
                    }elseif ($metodo_pago=="efectivo") {
                            $pagot="Efectivo";
                    }



                    $sqlCuenta = "INSERT INTO cuentaspromotor (
                        cue_idservicio, cue_idoperador, cue_abono,
                        cue_vrdeclarado, cue_pordeclarado,
                        cue_valorflete, cue_tipopago, cue_fecha,
                        cue_idciudadori, cue_idciudaddes, cue_tipoevento,
                        cue_numeroguia,cue_estado,cue_valpagar,cue_fecharecogida,cue_transferencia
                    ) VALUES (
                        " . (int)$idser . ",
                        " . (int)$id_usuario . ",
                        '" . $this->escape($param17) . "',
                        '" . $this->escape($param18) . "',
                        '" . $this->escape($pordeclarado) . "',
                        '" . $this->escape($precio) . "',
                        '" . $this->escape($param15) . "',
                        '" . $this->escape($fechatiempo) . "',
                        '" . $this->escape($param4) . "',
                        '" . $this->escape($param11) . "',
                        '" . $this->escape($param28) . "',
                        '" . $this->escape($planilla) . "',
                        '4',
                        '" . $this->escape($param26) . "',
                        '" . $this->escape($fechatiempo) . "',
                        '$pagot'
                    )";



                    $this->db->query($sqlCuenta);

                    if ($metodo_pago=="DV" or $metodo_pago=="NQ") {
                        $fotoTrans = "";
                        if ($param40 && isset($param40['tmp_name']) && is_uploaded_file($param40['tmp_name'])) {
                            $fotoTrans = $this->uploadFile($param40, __DIR__ . "/../../img_transacciones/");
                        }

                        if ($metodo_pago=="DV") {
                            $cuenta="457800098420";
                            $tipoMedio="2";
                        }elseif ($metodo_pago=="NQ") {
                            $cuenta="26400000710";
                            $tipoMedio="4";
                        }

                        // 🔹 INSERTAR TAMBIÉN EN pagoscuentas
                        $sqlPagoCuenta = "INSERT INTO pagoscuentas (
                                pag_tipopago,
                                pag_cuenta,
                                pag_valor,
                                pag_idoperario,
                                pag_idservicio,
                                pag_guia,
                                pag_estado,
                                pag_fecha,
                                pag_img_transaccion
                            ) VALUES (
                                '" . $this->escape($tipoMedio) . "',
                                '" . $this->escape($cuenta) . "',
                                '" . $this->escape($precio) . "',
                                '" . (int)$id_usuario . "',
                                '" . (int)$idser . "',
                                '" . $this->escape($planilla) . "',
                                'Contado',
                                '" . $this->escape($fechatiempo) . "',
                                '" . $this->escape($fotoTrans) . "'
                            )";

                        $this->db->query($sqlPagoCuenta);
                    }

                    // --------------------------------------------------
                    // 15) firma_clientes (igual)
                    // --------------------------------------------------
                    $sqlFirma = "INSERT INTO firma_clientes (id_guia, tipo_firma, nombre, numero_documento, correo_electronico, telefono, enviar_whatsapp, foto,activo_para_firmar,id_oper_responsable)
                                VALUES (
                                    '" . (int)$idser . "',
                                    'Recogida',
                                    '" . $this->escape($param92) . "',
                                    '',
                                    '',
                                    '" . $this->escape($param93) . "',
                                    '',
                                    '',
                                    1,
                                    '" . (int)$id_usuario . "'
                                )";
                    $this->db->query($sqlFirma);


                    
                    $link="$idser&accion=guardarFirmaRecogida&tipo_pago=$param28";
                    
                    // $this->reEnviarFirmaWhat($this->escape($param93), 44, (int)$idser ,$link);


                    $numguia    = $planilla;
                    $idservicio = $idser;

                    $resLink  = $this->guardarLinkServicio(
                        $idservicio,
                        "Recogida",
                        $numguia,
                        $this->escape($param26),
                        $this->escape($param27),
                        $param18,
                        $valorsinseguro
                    );
                    $linkGuia = $resLink['url'] ?? '';

                    $this->logRecogido("Link generado: $linkGuia");
                    // 📸 Procesar imagen del servicio
                    // if (!empty($param91['name'])) {
                    //     $this->encolarProceso('procesar_imagen_servicio', $idser, [
                    //         'tmp_name' => $param91['tmp_name'],
                    //         'nombre'   => $param91['name']
                    //     ]);
                    // }

                    // 📸 Procesar imagen de transacción
                    if (!empty($param40['name'])) {
                        $this->encolarProceso('procesar_imagen_pago', $idser, [
                            'tmp_name' => $param40['tmp_name'],
                            'nombre'   => $param40['name']
                        ]);
                    }

                    // 🔗 El link de guía ya se inserta en línea para este flujo.

                    // 📲 Enviar WhatsApp firma
                    $this->encolarProceso('whatsapp_firma', $idser, [
                        'telefono' => $param93,
                        'link' => "$idser&accion=guardarFirmaRecogida&tipo_pago=$param28"
                    ]);

                    // 📲 Enviar WhatsApp guía
                    // $this->encolarProceso('whatsapp_guia', $idser, [
                    //     'telefono' => $param93,
                    //     'guia' => $planilla
                    // ]);

                    // ===============================
                    // RESPUESTA RÁPIDA
                    // ===============================
                    return [
                        'ok' => true,
                        'idservicio' => (int)$idser,
                        'guia' => $param16,
                        'planilla' => $planilla,
                        'link' => $link
                    ];
                    // --------------------------------------------------
                    // 16) DEVOLVER GUIA
                    // --------------------------------------------------
                    // $this->logServicio('FIN guardarRecogida OK', [
                    //     'idservicio' => $idser,
                    //     'guia' => $param16,
                    //     'planilla' => $planilla,
                    //     'link' => $link
                    // ]);
                    // return [
                    //     'ok' => true,
                    //     'idservicio' => (int)$idser,
                    //     'guia' => $param16,
                    //     'planilla' => $planilla,
                    //     'link' => $link
                    // ];

            } catch (\Throwable $e) {

            // $this->logServicio('ERROR CRÍTICO guardarRecogida', [
            //     'mensaje' => $e->getMessage(),
            //     'archivo' => $e->getFile(),
            //     'linea' => $e->getLine(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return [
                'ok' => false,
                'mensaje' => 'Error interno. Revisa logs.'
            ];
        }
    }

    public function logRecogido($msg) {
        return;
        $logFile = __DIR__ . "/logs_recogido.log";
        $fecha = date("Y-m-d H:i:s");
        @file_put_contents($logFile, "[$fecha] $msg\n", FILE_APPEND);
    }

/* =========================
   Guardar ubicacion 
========================= */

    private function guardarUbicacionServicio(
        int $idservicios,
        int $idusuario,
        string $tipoEvento,
        array $data
    ): void {
        try {
            $latitud   = isset($data['latitud']) ? (float)$data['latitud'] : 0;
            $longitud  = isset($data['longitud']) ? (float)$data['longitud'] : 0;
            $precision = isset($data['precision_gps']) ? (float)$data['precision_gps'] : 0;

            if ($latitud == 0 || $longitud == 0) {
                // $this->logServicio('GPS NO ENVIADO', ['idservicio' => $idservicios]);
                return;
            }

            $fecha = date("Y-m-d H:i:s");

            $sql = "INSERT INTO servicios_ubicaciones (
                        idservicios, idusuario, tipo_evento,
                        latitud, longitud, precision_metros, fecha_registro
                    ) VALUES (
                        '".(int)$idservicios."',
                        '".(int)$idusuario."',
                        '".$this->escape($tipoEvento)."',
                        '".$this->escape($latitud)."',
                        '".$this->escape($longitud)."',
                        '".$this->escape($precision)."',
                        '".$this->escape($fecha)."'
                    )";

            $this->db->query($sql);

            // $this->logServicio('GPS GUARDADO', [
            //     'servicio' => $idservicios,
            //     'evento' => $tipoEvento,
            //     'lat' => $latitud,
            //     'lng' => $longitud
            // ]);

        } catch (\Throwable $e) {
            $this->logServicio('ERROR GPS', ['mensaje' => $e->getMessage()]);
        }
    }

/* =========================
   HELPERS internos
========================= */
    private function escape($str)
    {
        return $this->db->real_escape_string((string)$str);
    }

    private function fetchOne($sql)
    {
        $res = $this->db->query($sql);
        if (!$res) return null;
        return $res->fetch_assoc() ?: null;
    }

    private function fetchValue($sql)
    {
        $res = $this->db->query($sql);
        if (!$res) return null;
        $row = $res->fetch_row();
        return $row[0] ?? null;
    }

    private function insert($sql)
    {
        $ok = $this->db->query($sql);
        if (!$ok) {
            // si quieres: throw new Exception($this->db->error);
            return 0;
        }
        return (int)$this->db->insert_id;
    }

    private function uploadFile(array $file, $destDir)
    {
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }
        $original = $file['name'] ?? 'file';
        $nombre = date("Y-m-d-H-i-s") . "_" . preg_replace('/[^a-zA-Z0-9\.\-\_]/', '_', $original);
        $dest = rtrim($destDir, "/") . "/" . $nombre;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // guarda solo el nombre, como tu lógica vieja
            return $nombre;
        }
        return "";
    }
//LOGS 
    private function logServicio(string $mensaje, array $contexto = [])
    {
        return;
        $logDir  = __DIR__ . '/../logs';
        $logFile = $logDir . '/servicios_' . date('Y-m-d') . '.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;

        if (!empty($contexto)) {
            $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($logFile, $linea . PHP_EOL, FILE_APPEND);
        //https://historico.transmillas.com/nueva_plataforma/view/recogerEntregar/firmar.php?para=370596&accion=guardarFirmaRecogida
    }
//GUardar datos img servicio
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
        $sqls = "SELECT idimagenguias  FROM imagenguias 
                WHERE ima_idservicio = '$id_param2'
                AND ima_tipo = '$imprimir'
                LIMIT 1";

        $res = $this->db->query($sqls);

        if (!$res) {
            $this->logEntrega("ERROR SQL buscar imagenguias: ".$sqls . $this->db->error);
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
    public function logEntrega($msg) {
        return;
        $logFile = __DIR__ . "/logs/logs_WF.log";
        $fecha = date("Y-m-d H:i:s");
        file_put_contents($logFile, "[$fecha] $msg\n", FILE_APPEND);
    }
    

    public function guardarFirmaRecogida($idservicio, $firmaBase64)
    {
        $stmtCheck = null;
        $stmtUpdate = null;
        $stmtInsert = null;
        $stmtTel = null;
        $stmtGuia = null;
        date_default_timezone_set('America/Bogota');
        $fechaHoraColombia = date('Y-m-d H:i:s');
        $logFile = 'php://temp';
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] === INICIO guardarFirmaRecogida() ===\n", FILE_APPEND);

        try {
            file_put_contents($logFile, "🟡 Paso 1: Recibido idservicio=$idservicio\n", FILE_APPEND);

            // 1️⃣ Convertir base64 a imagen (compatible con varios formatos)
            if (preg_match('/^data:image\/(\w+);base64,/', $firmaBase64, $type)) {
                $firmaData = substr($firmaBase64, strpos($firmaBase64, ',') + 1);
                $type = strtolower($type[1]); // jpg, jpeg, png, webp...

                $firmaData = str_replace(' ', '+', $firmaData);
                $imagen = base64_decode($firmaData);

                if ($imagen === false) {
                    file_put_contents($logFile, "❌ Error al decodificar base64\n", FILE_APPEND);
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

            // 2️⃣ Crear carpeta si no existe
            $rutaCarpeta = __DIR__ . '/../../firmas_clientes/';
            if (!file_exists($rutaCarpeta)) {
                if (!mkdir($rutaCarpeta, 0777, true)) {
                    file_put_contents($logFile, "❌ Error al crear carpeta\n", FILE_APPEND);
                    return false;
                }
            }

            // 3️⃣ Guardar archivo
            $nombreArchivo = 'firma_' . $idservicio . '_' . time() . '.png';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            $rutaArchivoGuardar = "firmas_clientes/" . $nombreArchivo;

            if (file_put_contents($rutaArchivo, $imagen) === false) {
                file_put_contents($logFile, "❌ Error al guardar imagen\n", FILE_APPEND);
                return false;
            }

            file_put_contents($logFile, "✅ Imagen guardada: $rutaArchivoGuardar\n", FILE_APPEND);

            $tipoFirma = 'Recogida';


            // 🔎 4️⃣ Verificar si ya existe firma para ese servicio y tipo
            $sqlCheck = "SELECT id FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bind_param('is', $idservicio, $tipoFirma);
            $stmtCheck->execute();
            $result = $stmtCheck->get_result();

            if ($result->num_rows > 0) {
                // ✏️ YA EXISTE → HACER UPDATE
                $row = $result->fetch_assoc();
                $idFirma = $row['id'];

                file_put_contents($logFile, "🟠 Firma existente encontrada (ID=$idFirma), actualizando...\n", FILE_APPEND);
                $activoParaFirma=0;
                $sqlUpdate = "UPDATE firma_clientes 
                            SET firma_clientes = ?, fecha_registro = ? , activo_para_firmar = ?
                            WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                
                $stmtUpdate->bind_param('ssii', $rutaArchivoGuardar, $fechaHoraColombia,$activoParaFirma, $idFirma);


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
                $sqlGuia = "SELECT ser_consecutivo,ser_telefonocontacto FROM servicios WHERE idservicios = ? LIMIT 1";
                $stmtGuia = $this->db->prepare($sqlGuia);
                $stmtGuia->bind_param('i', $idservicio);
                $stmtGuia->execute();
                $resGuia = $stmtGuia->get_result();
                $rowGuia = ($resGuia->num_rows > 0) ? $resGuia->fetch_assoc() : null;
                $numguia = $rowGuia['ser_consecutivo'] ?? null;
                $numDestinatario = $rowGuia['ser_telefonocontacto'] ?? null;

                if (!empty($telefono) && !empty($numguia)) {
                    $this->enviarGuiaWhat($telefono, 42, $numguia . "R");
                }
                if (!empty($numDestinatario) && !empty($numguia)) {
                    $this->enviarGuiaWhat($numDestinatario, 42, $numguia . "R");
                }


                file_put_contents($logFile, "✅ Firma actualizada correctamente\n", FILE_APPEND);
                return true;

            } else {
                // ➕ NO EXISTE → INSERTAR
                $activoParaFirma=0;
                file_put_contents($logFile, "🟢 No existe firma previa, insertando nueva...\n", FILE_APPEND);

                $sqlInsert = "INSERT INTO firma_clientes (id_guia, tipo_firma, firma_clientes, fecha_registro,activo_para_firmar) 
                            VALUES (?, ?, ?, ?,?)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                
                $stmtInsert->bind_param('isssi', $idservicio, $tipoFirma, $rutaArchivoGuardar, $fechaHoraColombia,$activoParaFirma);


                if (!$stmtInsert->execute()) {
                    file_put_contents($logFile, "❌ Error en INSERT: " . $stmtInsert->error . "\n", FILE_APPEND);
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
                $sqlGuia = "SELECT ser_consecutivo,ser_telefonocontacto FROM servicios WHERE idservicios = ? LIMIT 1";
                $stmtGuia = $this->db->prepare($sqlGuia);
                $stmtGuia->bind_param('i', $idservicio);
                $stmtGuia->execute();
                $resGuia = $stmtGuia->get_result();
                $rowGuia = ($resGuia->num_rows > 0) ? $resGuia->fetch_assoc() : null;
                $numguia = $rowGuia['ser_consecutivo'] ?? null;
                $numDestinatario = $rowGuia['ser_telefonocontacto'] ?? null;

                if (!empty($telefono) && !empty($numguia)) {
                    $this->enviarGuiaWhat($telefono, 42, $numguia . "R");
                }
                if (!empty($numDestinatario) && !empty($numguia)) {
                    $this->enviarGuiaWhat($numDestinatario, 42, $numguia . "R");
                }

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
    public function existeFirmaEntregaPublica($idservicio) {
        $id = (int)$idservicio;

        $sql = "SELECT activo_para_firmar, firma_clientes
                FROM firma_clientes
                WHERE tipo_firma='Recogida' AND id_guia='$id'
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
    public function enviarGuiaWhat($telefono, $tipo, $idservi)
        
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

    private function encolarProceso($tipo, $idservicio, $datos = [])
        {
            $json = $this->escape(json_encode($datos, JSON_UNESCAPED_UNICODE));
            $sql = "INSERT INTO cola_procesos (tipo, idservicio, datos, fecha_creado)
                    VALUES ('{$this->escape($tipo)}', '".(int)$idservicio."', '$json', NOW())";
            $this->db->query($sql);
        }
        public function getDB() {
            return $this->db;
        }
}

