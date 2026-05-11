<?php
require("login_autentica.php");
include("cabezote3.php");
include("cabezote1.php");
if (isset($_REQUEST["id_param"])) {
    $id_param = $_REQUEST["id_param"];
} else {
    $id_param = "";
}
if (isset($_REQUEST["tabla"])) {
    $tabla = $_REQUEST["tabla"];
} else {
    $tabla = "";
}
if (isset($_REQUEST["dir"])) {
    $dir = $_REQUEST["dir"];
} else {
    $dir = "";
}
$fechatiempo = date("Y-m-d H:i:s");
$id_sedes = $_SESSION['usu_idsede'];
?>	

<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button><h4 class="modal-title"><?php echo $tabla; ?></h4></div>
<?php
if ($tabla == "Verificar Datos") {

    $estadofactura = 'verificacion';
    $nombre = explode(" ", $id_nombre);
    $descllamada = $nombre[0] . " " . $nombre[1] . '<br>';
    $descllamada .= "$fechatiempo";
    $dir = $_REQUEST["dir"];

    $sql = "SELECT `idclientes`, `cli_iddocumento`, `cli_telefono`,
    `cli_email`, `cli_idciudad`, `cli_direccion`, `cli_nombre`,
    `ser_iddocumento`,`ser_telefonocontacto`, `ser_destinatario`,
    `ser_direccioncontacto`,`ser_ciudadentrega`,`ser_tipopaquete`,
    `ser_paquetedescripcion`, `ser_fechaentrega`,`ser_prioridad`,
    `ser_valorprestamo`, `ser_valorabono`, `ser_valorseguro`, 
    `idservicios`,cli_retorno,idclientesdir,ser_descllamada,
    date(ser_fecharegistro),ser_clasificacion,ser_tipopaq,ser_valor,ser_piezas,ser_cotizacion,gui_tiposervicio
    FROM servicios 
    inner join rel_sercli  on idservicios=ser_idservicio  
    inner join clientesservicios on idclientesdir=ser_idclientes 
    inner join clientes on idclientes=cli_idclientes  
    inner join guias on idservicios=gui_idservicio
    where idservicios=$id_param ";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    
//$descllamada.=@$rw[22];
    $fecharegistro = @$rw[23];
    $sql2 = "UPDATE `servicios` SET  `ser_esatdollamando`='Ocupado',`ser_descllamada`='$descllamada' WHERE `idservicios`='$id_param' ";
    $DB->Execute($sql2);

    $actuliza = "no";
    $param15 = $rw[15];

    if ($rw[28]!="") {
        echo "<div style=\"
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            text-align: center;
            background-color: #28a745; 
            border-radius: 6px;
        \">
            <a href=\"#\" 
            onclick=\"window.open(' https://sistema.transmillas.com/cotizaciones/Cotizacion".$rw[28].".pdf', 'popup', 'width=800,height=600'); return false;\"
            style=\"color: white; text-decoration: none; font-weight: bold; font-size: 16px;\">
                Con Cotización 👉Ver
            </a>
        </div>";

    }

    if ($param15 == "Envio Oficina") {

        include("oficina.php");
    } else if ($param15 == "Compra") {
        $boton = 'no';
        include("recoleccion_compra.php");
    } else {
        include("recoleccion_datos.php");
    }
    if ($rw[28]!="") {
        $sqlC="SELECT `cot_id`,`cot_peso`, `cot_vol`,`cot_val_seguro`, `cot_val_servicio`,cot_piezas FROM cotozaciones WHERE cot_id='$rw[28]'";
        $DB1->Execute($sqlC);
        $rwC=mysqli_fetch_row($DB1->Consulta_ID);
        $FB->llena_texto("Peso:", 110, 1, $DB, "", "", "$rwC[1]", 1, 0);
        $FB->llena_texto("Numero de piezas:", 111, 1, $DB, "", "", "$rwC[5]", 4, 0);
        $FB->llena_texto("Seguro:", 112, 1, $DB, "", "", "$rwC[3]", 4, 0);
        $FB->llena_texto("Valor servicio:", 113, 1, $DB, "", "", "$rwC[4]", 4, 0);
    
    }
    if ($dir == "adm_validardatos.php") {
        $FB->llena_texto("LLAMAR DESPUES:", 99, 5, $DB, "", "", "", 1, 0);
        $FB->llena_texto("MOTIVO:", 100, 1, $DB, "", "", "", 4, 0);
        $FB->llena_texto("Reasignar Fecha:", 105, 10, $DB, "", "", "$fecharegistro", 4, 0);
    } else {
        $FB->llena_texto("param99", 1, 13, $DB, "", "", "", 5, 0);
        $FB->llena_texto("param100", 1, 13, $DB, "", "", "", 5, 0);
        $FB->llena_texto("param105", 1, 13, $DB, "", "", "$fecharegistro", 4, 0);
    }


    $FB->llena_texto("param106", 1, 13, $DB, "", "", "$fecharegistro", 4, 0);
    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("dir", 1, 13, $DB, "", "", $dir, 5, 0);
    $FB->llena_texto("descllamada", 1, 13, $DB, "", "", $descllamada, 5, 0);
//$FB->llena_texto("id_param0", 1, 13, $DB, "", "", $id_usuario, 5, 0);
} elseif ($tabla == "derechopeticion") {

    $idservicio = $_REQUEST["dir"];
    $sql = "SELECT `idreclamos`,`rec_estado` FROM  `reclamos` where idreclamos=$id_param ";

    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    // $FB->llena_texto("Valor a Pagar:",4, 1, $DB, "", "", "$rw[4]", 1, 0);


    $FB->llena_texto("Requerimiento1 ", 1000, 6, $DB, "", "", "", 1, 0);

    $FB->llena_texto("Respuesta 1 ", 11, 6, $DB, "", "", "", 1, 0);

    // echo $LT->llenadocs3($DB, "reclamos", $rw1[0], 1, 35, 'Ver');
    // $FB->llena_texto("Respuesta 1", 5, 6, $DB, "", "", "",1,0);


    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);

    $sql = "UPDATE `reclamos` SET `rec_estado`='$param7' WHERE `idreclamos`='$id_param' ";
} elseif ($tabla == "acuerdo") {
    $idservicio = $_REQUEST["dir"];
    $sql = "SELECT `idreclamos`,`rec_valoracuerdo`, `rec_acuerdo`,`fec_descricomf`,`rec_banco`,`rec_tipocuenta`,`rec_numerocuenta`,`rec_fechapago` FROM  `reclamos` where idreclamos=$id_param ";

    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $FB->llena_texto("Descripcion de LLamada:", 1, 9, $DB, "", "", "$rw[2]", 1, 1);
    $FB->llena_texto("Valor a Pagar:", 2, 1, $DB, "", "", "$rw[1]", 1, 0);

    $FB->llena_texto("Tipo Cuenta:", 5, 82, $DB, $tipocuenta, "", "$rw[5]", 1, 0);
    $FB->llena_texto("# Cuenta:", 4, 1, $DB, "", "", "$rw[6]", 1, 0);
    $FB->llena_texto("Banco:", 6, 1, $DB, "", "", "$rw[4]", 1, 0);
    $FB->llena_texto("Fecha de pago:", 8, 10, $DB, "", "", "$rw[7]", 17, $habi);

    $FB->llena_texto("Soporte acuerdo de pago", 3, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);
} elseif ($tabla == "historialvehiculo") {
    $idservicio = $_REQUEST["ide"];

    $FB->titulo_azul1("Fecha de revision", 1, 0, 7);
    $FB->titulo_azul1("Valor", 1, 0, 0);
    $FB->titulo_azul1("Descripcion", 1, 0, 0);

    $FB->titulo_azul1("Operador", 1, 0, 0);
// $FB->titulo_azul1("Fecha Registro",1,0,0); 
    $FB->titulo_azul1("Foto", 1, 0, 0);

    $sql = "SELECT `idasignaciondinero`,`asi_fecha`, `asi_valor`,  `asi_descripcion`, `asi_idautoriza`, `asi_idpromotor`,`asi_valorcom`, `asi_fechaconf`,asi_idgastos FROM `asignaciondinero` WHERE  asi_idvehiculo=$idservicio ";
    $DB->Execute($sql);

    while ($rw2 = mysqli_fetch_row($DB->Consulta_ID)) {
        $sql1 = "SELECT idusuarios, usu_nombre FROM `usuarios` WHERE idusuarios='$rw2[5]' ";
        $DB1->Execute($sql1);

        $rw3 = mysqli_fetch_array($DB1->Consulta_ID);

        $id_p = $rw2[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }
        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw2[1] . "</td>
				<td>" . $rw2[2] . "</td>
				<td>" . $rw2[3] . "</td>		
				<td>" . $rw3[1] . "</td>";
    }
} elseif ($tabla == "Pagoacuerdo") {

    $idservicio = $_REQUEST["dir"];
    $sql = "SELECT `idreclamos`,`rec_estado` FROM  `reclamos` where idreclamos=$id_param ";

    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    // $FB->llena_texto("Valor a Pagar:",4, 1, $DB, "", "", "$rw[4]", 1, 0);


    $FB->llena_texto("Formato de pago ", 5, 6, $DB, "", "", "", 1, 0);

    $FB->llena_texto("Nuevo estado del reclamo", 7, 82, $DB, $estadoreclamo, "", "$rw[1]", 1, 0);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);

    $sql = "UPDATE `reclamos` SET `rec_estado`='$param7' WHERE `idreclamos`='$id_param' ";
}elseif ($tabla == "Comprobante_nomina_basico") {

    // $idservicio = $_REQUEST["dir"];
    // $sql = "SELECT `idreclamos`,`rec_estado` FROM  `reclamos` where idreclamos=$id_param ";

    // $DB->Execute($sql);
    // $rw = mysqli_fetch_array($DB->Consulta_ID);

    // $FB->llena_texto("Valor a Pagar:",4, 1, $DB, "", "", "$rw[4]", 1, 0);
    $fechaini = $_REQUEST["ide"];

    $FB->llena_texto("param4", 4, 13, $DB, "", "", "$fechaini", 5, 0);
    $FB->llena_texto("Formato de pago ", 5, 6, $DB, "", "", "", 1, 0);

    // $FB->llena_texto("Nuevo estado del reclamo", 7, 82, $DB, $estadoreclamo, "", "$rw[1]", 1, 0);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);

    $sql = "UPDATE `reclamos` SET `rec_estado`='$param7' WHERE `idreclamos`='$id_param' ";
}elseif ($tabla == "Comprobante_nomina_otros") {

    // $idservicio = $_REQUEST["dir"];
    // $sql = "SELECT `idreclamos`,`rec_estado` FROM  `reclamos` where idreclamos=$id_param ";

    // $DB->Execute($sql);
    // $rw = mysqli_fetch_array($DB->Consulta_ID);

    // $FB->llena_texto("Valor a Pagar:",4, 1, $DB, "", "", "$rw[4]", 1, 0);
    $fechaini = $_REQUEST["ide"];

    $FB->llena_texto("param4", 4, 13, $DB, "", "", "$fechaini", 5, 0);
    $FB->llena_texto("Formato de pago ", 5, 6, $DB, "", "", "", 1, 0);

    // $FB->llena_texto("Nuevo estado del reclamo", 7, 82, $DB, $estadoreclamo, "", "$rw[1]", 1, 0);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);

    $sql = "UPDATE `reclamos` SET `rec_estado`='$param7' WHERE `idreclamos`='$id_param' ";
}elseif ($tabla == "documento_encautacion") {

    $idservicio = $_REQUEST["dir"];
    // $sql = "SELECT `idreclamos`,`rec_estado` FROM  `reclamos` where idreclamos=$id_param ";

    // $DB->Execute($sql);
    // $rw = mysqli_fetch_array($DB->Consulta_ID);

    // $FB->llena_texto("Valor a Pagar:",4, 1, $DB, "", "", "$rw[4]", 1, 0);
    

    $FB->llena_texto("Documento encautacion ", 5, 6, $DB, "", "", "", 1, 0);

    // $FB->llena_texto("Nuevo estado del reclamo", 7, 82, $DB, $estadoreclamo, "", "$rw[1]", 1, 0);
    echo $id_param;
    // echo $idservicio;
    
    $FB->llena_texto("id_param", 2, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);

    // $sql = "UPDATE `reclamos` SET `rec_estado`='$param7' WHERE `idreclamos`='$id_param' ";
} elseif ($tabla == "autorizacion") {




    $FB->llena_texto(" Mediante la presente autorizo a DBS GRUPO DE INGENIERIA  S.A.S ., para que consulte las listas establecidas para el control de Lavado de Activos y Financiación del Terrorismo, así como todas las bases de datos públicas para consultar información relacionada con la empresa que represento, los representantes legales, sus accionistas, revisor fiscal y miembros de la junta directiva. Para el caso de personas jurídicas, autorizo la consulta tanto de la persona jurídica como de los representantes legales, accionistas con participación igual o superior al 5% del capital social y miembros de la junta directiva. Manifiesto que cualquier variación en la información suministrada será puesta en conocimiento de DBS GRUPO DE INGENIERIA  S.A.S., y de igual forma se procederá cuando DBS GRUPO DE INGENIERIA  S.A.S.,lo requiera durante la ejecución del proceso de contratación.", "", "", $DB, $estadoreclamo, "", "", 1, 0);
} elseif ($tabla == "autorizacion1") {




    $FB->llena_texto(" de conformidad con lo dispuesto por la Ley 1581 de 2012 y el Decreto 1377 de 2013, autorizo para que los datos personales del representante legal  y los datos juridicos de la entidad sean tratados, recolectados, almacenados, usados y procesados en un base de datos propiedad de DBS GRUPO DE INGENIERIA  S.A.S., y cuya finalidad sea la de gestionar la relacion comercial entre las partes; ademas del envio de nuestra parte de los servicios, ofertas comerciales y publicaciones, que creemos puedan resultar de su interes, garantizando el ejercicio de los derechos de acceso rectificacion, cancelacion y oposicion de los datos facilitados.
Si desea consultar o eliminar sus datos, puede hacerlo mediante comunicacion escrita dirigida a la siguiente direccion: Cra.7 # 156-10 Of.1807 TORRE KRYSTAL, BOGOTA, todo lo anterior acorde con el aviso de privacidad y politica de proteccion de datos que podra consultar en nuestra  sede.  
AVISO DE PRIVACIDAD: El uso y tratamiento de los datos personales (nombres y apellidos, Cedula de Ciudadania- Genero- Direccion de residencia, barrio, municipio y departamento, direccion electronica, correo electronico, números de telefono y celular – fecha y lugar de nacimiento, etc.), y demas datos personales a los cuales tenga acceso DBS GRUPO DE INGENIERIA  S.A.S.. en razon al vinculo comercial (razon social – Nit – Direccion – Correo Electronico- Telefono, Estrato Social, etc), recolectados, almacenados, se mantendran usaran y trasferiran aun cuando estos datos sean sensibles con la finalidad única y exclusiva de  adelantar las actividades de registros en base de datos de DBS GRUPO DE INGENIERIA  S.A.S., para gestionar la relacion comercial, para  los procedimientos internos, legales y normativos que sean necesarios para el cumplimiento del objeto social de la empresa, asi mismo estos datos podran ser entregados a autoridades policiales, judiciales, o administrativas del estado,  que sean necesarios para aclaracion de procedimientos legales, asi como para todas aquellas finalidades especificas señaladas en la politica de proteccion de datos de DBS GRUPO DE INGENIERIA  S.A.S.. ", "", "", $DB, $estadoreclamo, "", "", 1, 0);
} elseif ($tabla == "prevencion") {




    $FB->llena_texto(" Las Partes se obligan a implementar las medidas tendientes a evitar que sus operaciones puedan ser utilizadas, sin su conocimiento y consentimiento, como instrumentos para el ocultamiento, manejo, inversión o aprovechamiento en cualquier forma de dinero u otros bienes provenientes de actividades delictivas o para dar apariencia de legalidad a estas actividades. En tal sentido, las Partes aceptan que quien incumpla esta obligación está sujeta a que la parte cumplida pueda terminar de manera unilateral e inmediata la relación contractual que se genere, sin lugar al pago de indemnización alguna, en caso de que quien incumpla sea alguno de sus representantes legales, administradores o accionistas, y que llegue a ser: (i) condenado por parte de las autoridades competentes por delitos de narcotráfico,
terrorismo, secuestro, lavado de activos, financiación del terrorismo, administración de recursos relacionados con dichas actividades o en cualquier tipo de proceso judicial relacionado con la comisión de los anteriores delitos. (ii) incluido en listas para el control de lavado de activos y financiación del terrorismo, administradas por cualquier autoridad nacional o extranjera, tales como la lista de la Oficina de Control de Activos en el Exterior – OFAC emitida por la Oficina del Tesoro de los Estados Unidos de Norte América, la lista de la Organización de las Naciones Unidas – ONU y otras listas públicas relacionadas con el tema de lavado de activos y financiación del terrorismo. El abajo firmante declara que no se encuentra en las Listas OFAC y ONU, así mismo, se responsabiliza ante  DBS GRUPO DE INGENIERIA  S.A.S., porque sus miembros de Junta Directiva, sus Representantes Legales, Accionistas o su Revisor Fiscal, tampoco se encuentran en dichas listas. De igual manera  DBS GRUPO DE INGENIERIA  S.A.S., declara que no se encuentra en las Listas OFAC y ONU, así mismo, se responsabiliza ante la contraparte porque sus miembros de Junta Directiva, sus Representantes Legales, Accionistas o su Revisor Fiscal, tampoco se encuentren en dichas listas. ", "", "", $DB, $estadoreclamo, "", "", 1, 0);
} elseif ($tabla == "cliente/p") {

    $sql = "SELECT `idreclamos`,`rec_tipoCliente` FROM  `reclamos` where idreclamos=$id_param ";

    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $FB->llena_texto("Cliente dificil?", 20, 82, $DB, $clienteP, "", "$rw[1]", 1, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);
} elseif ($tabla == "mensajes/visto") {


    $sql = "SELECT `idnoticia`,`not_visto` FROM  `noticia` where idnoticia=$id_param ";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $FB->llena_texto("Confirmar visto", 25, 82, $DB, $visto, "", "$rw[1]", 1, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);
} elseif ($tabla == "Respuesta") {


    $sql = "SELECT `idnoticia`,`not_respuesta` FROM  `noticia` where idnoticia=$id_param ";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $FB->llena_texto("Escribe tu respuesta", 26, 9, $DB, "", "", "$rw[1]", 1, 1);
    $FB->llena_texto("Imagen  ", 501, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);
}elseif ($tabla == "SeguimientoUser") {

    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    $fecha=date('Y-m-d');
    $idsede = $_REQUEST["ide"];
    $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) ", "", $id_param, 2, 1);
    $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
   
    $FB->llena_texto("Fecha de Ingreso:", 3, 10, $DB, "", "", "$fecha", 2, 1);
    $FB->llena_texto("Motivo Ingreso:", 4, 82, $DB, $motivoingreso, "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT idzonatrabajo, zon_nombre FROM zonatrabajo WHERE inner_sedes = '$idsede')", "", "", 2, 0);
    $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, $pruebaalcohol, "", "", 2, 1);
    $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);
}elseif($tabla == "Cambio_seguimientoUser"){
    $idsede = $_REQUEST["ide"];
    // if ($nivel_acceso != '1' and $nivel_acceso != '12') {
    //     $cond = "and idsedes=$id_sedes";
    // }
    // echo$idsede = $_REQUEST["ide"];
    // $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) ", "", $id_param, 2, 1);
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
   
    // $FB->llena_texto("Fecha de Ingreso:", 3, 10, $DB, "", "", "", 2, 1);
    $FB->llena_texto("param13", 1, 13, $DB, "", "", $id_param, 5, 0);
   
    $FB->llena_texto("Motivo Ingreso:", 4, 82, $DB, $motivoingreso, "", "", 2, 1);
    $FB->llena_texto("Horas trabajadas", 9, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT `idzonatrabajo`,`zon_nombre` FROM zonatrabajo where idzonatrabajo>0 and inner_sedes = '$idsede' )", "", "", 2, 0);
    $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, $pruebaalcohol, "", "", 2, 1);
    $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);
}elseif($tabla == "Agregar festivos") {


    // $parametro = $_GET['parametro'];
    // $miArray = json_decode(urldecode($parametro));
    // print_r($miArray);
    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    echo$idsede = $_REQUEST["ide"];

    $FB->llena_texto("Fecha dia festivo:", 6, 10, $DB, "", "", "$fechaactual", 2, 0);

}elseif ($tabla == "Vacaciones") {


    // $parametro = $_GET['parametro'];
    // $miArray = json_decode(urldecode($parametro));
    // print_r($miArray);
    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    echo$idsede = $_REQUEST["ide"];
    $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) ", "", $id_param, 2, 1);
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
   
    $FB->llena_texto("Fecha de inicio:", 3, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Fecha de fin:", 4, 10, $DB, "", "", "$fechaactual", 2, 0);
    // $FB->llena_texto("Motivo Ingreso:", 4, 82, $DB, $motivoingreso, "", "", 2, 1);
    // $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    // $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT `idzonatrabajo`,`zon_nombre` FROM zonatrabajo where idzonatrabajo>0 )", "", "", 2, 0);
    // $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, $pruebaalcohol, "", "", 2, 1);
    // $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);
} elseif ($tabla == "Licencias_y_permisos") {


    // $parametro = $_GET['parametro'];
    // $miArray = json_decode(urldecode($parametro));
    // print_r($miArray);
    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    echo$idsede = $_REQUEST["ide"];
    $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) ", "", $id_param, 2, 1);
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
   
    $FB->llena_texto("Fecha de inicio:", 3, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Fecha de fin:", 4, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Motivo Ingreso:", 6, 2, $DB, "(SELECT `mot_nombre`, `mot_nombre` FROM `motivo_ingreso` )", "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    // $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT `idzonatrabajo`,`zon_nombre` FROM zonatrabajo where idzonatrabajo>0 )", "", "", 2, 0);
    // $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, "", "", "", 2, 1);
    // $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);
}elseif ($tabla == "Agregar vehiculo") {


    // $parametro = $_GET['parametro'];
    // $miArray = json_decode(urldecode($parametro));
    // print_r($miArray);
    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    echo$idsede = $_REQUEST["ide"];
    // $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) ", "", $id_param, 2, 1);
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
   
    // $FB->llena_texto("Fecha de inicio:", 3, 10, $DB, "", "", "$fechaactual", 2, 0);
    // $FB->llena_texto("Fecha de fin:", 4, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Nombre propietario:", 1, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Numero celular propietario:", 2, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Placas del vehiculo:", 3, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Numero poliza del vehiculo:", 4, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Foto Poliza", 5, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Tarjeta de propiedad ambos lados", 6, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Foto vehiculo", 7, 6, $DB, "", "", "", 1, 0);

    
    
    
    // $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    // $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT `idzonatrabajo`,`zon_nombre` FROM zonatrabajo where idzonatrabajo>0 )", "", "", 2, 0);
    // $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, "", "", "", 2, 1);
    // $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);
}elseif ($tabla == "Agregar conductor") {


    // $parametro = $_GET['parametro'];
    // $miArray = json_decode(urldecode($parametro));
    // print_r($miArray);
    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    echo$idsede = $_REQUEST["ide"];
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
    $FB->llena_texto("Nombres y apellidos :", 1, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Celular:", 2, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Whatsapp:", 3, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Numero cedula:", 4, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Lugar de expedicion:", 30, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Cedula por ambos lados", 5, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Numero licencia:", 6, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Licencia por ambos lados", 7, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Foto conductor", 8, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Firma", 9, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Antecedentes", 10, 6, $DB, "", "", "", 1, 0);
    
    // $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT `idzonatrabajo`,`zon_nombre` FROM zonatrabajo where idzonatrabajo>0 )", "", "", 2, 0);
    // $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, "", "", "", 2, 1);
    // $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);
}elseif ($tabla == "Agregar viaje") {



    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    echo$idsede = $_REQUEST["ide"];
   
    $FB->llena_texto("Conductor:", 1, 2, $DB, "SELECT `condid`, `cond_nombre` FROM `conductor_mani` ", "","", 2, 1);
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
    $FB->llena_texto("Vehiculo:", 2, 2, $DB, "SELECT `vehimid`, `vehim_placas`FROM `vehiculo_manif`", "", "", 2, 1);
    $FB->llena_texto("Valor del contrato:", 3, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Fecha inicial:", 4, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Fecha final:", 5, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Piezas:", 6, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Ciudad origen:", 9, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Ciudad destino:", 8, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("Num remesa:", 13, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Num manifiesto:", 12, 1, $DB, "", "", "", 2, 0);
    // $FB->llena_texto("Contrato", 8, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Manifiesto", 7, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Remesa de carga", 10, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Guias", 11, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Seguridad", 14, 6, $DB, "", "", "", 1, 0);
   
   

}elseif ($tabla == "Editar vehiculo"){


    // $parametro = $_GET['parametro'];
    // $miArray = json_decode(urldecode($parametro));
    // print_r($miArray);


    $vehiculo = "SELECT `vehimid`, `vehim_nombre_prop`, `vehim_num_cel_prop`, `vehim_placas`, `vehim_num_Poliza`, `vehim_foto_poli`, `vehim_foto_tarjeta`, `vehim_foto_vehiculo` FROM `vehiculo_manif` where vehimid=$id_param ";
    $DB1->Execute($vehiculo);
    $rw3 = mysqli_fetch_array($DB1->Consulta_ID);



    // if ($nivel_acceso != '1' and $nivel_acceso != '12') {
    //     $cond = "and idsedes=$id_sedes";
    // }
    // echo$idsede = $_REQUEST["ide"];
    // $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) ", "", $id_param, 2, 1);
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
   
    // $FB->llena_texto("Fecha de inicio:", 3, 10, $DB, "", "", "$fechaactual", 2, 0);
    // $FB->llena_texto("Fecha de fin:", 4, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Nombre propietario:", 1, 1, $DB, "", "", "$rw3[1]", 2, 0);
    $FB->llena_texto("Numero celular propietario:", 2, 1, $DB, "", "", "$rw3[2]", 2, 0);
    $FB->llena_texto("Placas del vehiculo:", 3, 1, $DB, "", "", "$rw3[3]", 2, 0);
    $FB->llena_texto("Numero poliza del vehiculo:", 4, 1, $DB, "", "", "$rw3[4]", 2, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("Foto Poliza", 5, 6, $DB, "", "", "img_manifiestos/vehiculos/$rw3[5]", 1, 0);
    $FB->llena_texto("Tarjeta de propiedad ambos lados", 6, 6, $DB, "", "", "img_manifiestos/vehiculos/$rw3[6]", 1, 0); 
    $FB->llena_texto("Foto vehiculo", 7, 6, $DB, "", "", "img_manifiestos/vehiculos/$rw3[7]", 1, 0);
    
    

    
    
    
    // $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    // $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT `idzonatrabajo`,`zon_nombre` FROM zonatrabajo where idzonatrabajo>0 )", "", "", 2, 0);
    // $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, "", "", "", 2, 1);
    // $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);
}elseif ($tabla == "Editar conductor") {

    $conductor = "SELECT `condid`, `cond_nombre`, `cond_celular`, `cond_whatsapp`, `cond_cedula`, `cond_foto_celula`, `cond_num_licen`, `cond_foto_licen`, `cond_foto_conductor`, `cond_firma`,cond_lugar_expedi,con_antec FROM `conductor_mani` where condid=$id_param ";
    $DB1->Execute($conductor);
    $rw2 = mysqli_fetch_array($DB1->Consulta_ID);

    // echo "<td>".$rw2[0]."</td>";
    // $parametro = $_GET['parametro'];
    // $miArray = json_decode(urldecode($parametro));
    // print_r($miArray);
    // if ($nivel_acceso != '1' and $nivel_acceso != '12') {
    //     $cond = "and idsedes=$id_sedes";
    // }
    // echo$idsede = $_REQUEST["ide"];
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
    $FB->llena_texto("Nombres y apellidos :", 1, 1, $DB, "", "", "$rw2[1]", 2, 0);
    $FB->llena_texto("Celular:", 2, 1, $DB, "", "", "$rw2[2]", 2, 0);
    $FB->llena_texto("Whatsapp:", 3, 1, $DB, "", "", "$rw2[3]", 2, 0);
    $FB->llena_texto("Numero cedula:", 4, 1, $DB, "", "", "$rw2[4]", 2, 0);
    $FB->llena_texto("Lugar de expedicion:", 30, 1, $DB, "", "", "$rw2[10]", 2, 0);
    $FB->llena_texto("Numero licencia:", 6, 1, $DB, "", "", "$rw2[6]", 1, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("Cedula por ambos lados", 5, 6, $DB, "", "", "img_manifiestos/conductores/$rw2[5]", 1, 0);
    $FB->llena_texto("Licencia por ambos lados", 7, 6, $DB, "", "", "img_manifiestos/conductores/$rw2[7]", 1, 0);
    $FB->llena_texto("Foto conductor", 8, 6, $DB, "", "", "img_manifiestos/conductores/$rw2[8]", 1, 0);
    $FB->llena_texto("Firma", 9, 6, $DB, "", "", "img_manifiestos/conductores/$rw2[9]", 1, 0);
    $FB->llena_texto("Antecedentes", 10, 6, $DB, "", "", "img_manifiestos/conductores/$rw2[11]", 1, 0);

}elseif ($tabla == "Editar viaje") {


    $conductor = "SELECT `idmani`, `mani_idConduc`, `mani_idVehiculo`, `mani_valor_cont`, `mani_fecha_ini`, `mani_fecha_fin`, `mani_piezas`, `mani_Contrato`, `mani_manifiesto`, `mani_fecha`, `mani_idusuIngreso`, `mani_ciudad_destino`,mani_ciudad_origen,mani_remesa_carga,mani_guias, mani_num_mani,mani_num_remesa,mani_seguridad FROM `manifiestos_viajes` WHERE  idmani=$id_param ";
    $DB1->Execute($conductor);
    $rw2 = mysqli_fetch_array($DB1->Consulta_ID);

    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    echo$idsede = $_REQUEST["ide"];
   
    $FB->llena_texto("Conductor:", 1, 2, $DB, "SELECT `condid`, `cond_nombre` FROM `conductor_mani` ", "", "$rw2[1]", 2, 1);
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $cond  )", "", "$idsede", 2, 1);
    $FB->llena_texto("Vehiculo:", 2, 2, $DB, "SELECT `vehimid`, `vehim_placas`FROM `vehiculo_manif`", "", "$rw2[2]", 2, 1);
    $FB->llena_texto("Valor del contrato:", 3, 1, $DB, "", "", "$rw2[3]", 2, 0);
    $FB->llena_texto("Fecha inicial:", 4, 10, $DB, "", "", "$rw2[4]", 2, 0);
    $FB->llena_texto("Fecha final:", 5, 10, $DB, "", "", "$rw2[5]", 2, 0);
    $FB->llena_texto("Piezas:", 6, 1, $DB, "", "", "$rw2[6]", 2, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("Ciudad origen:", 9, 1, $DB, "", "", "$rw2[12]", 2, 0);
    $FB->llena_texto("Ciudad destino:", 8, 1, $DB, "", "", "$rw2[11]", 2, 0);
    $FB->llena_texto("Num remesa:", 13, 1, $DB, "", "", "$rw2[16]", 2, 0);
    $FB->llena_texto("Num manifiesto:", 12, 1, $DB, "", "", "$rw2[15]", 2, 0);
    // $FB->llena_texto("Contrato", 8, 6, $DB, "", "", "img_manifiestos/manifiestos/$rw2[8]", 1, 0);
    $FB->llena_texto("Manifiesto", 7, 6, $DB, "", "", "img_manifiestos/manifiestos/$rw2[8]", 1, 0);
    $FB->llena_texto("Remesa de carga", 10, 6, $DB, "", "", "$rw2[13]", 1, 0);
    $FB->llena_texto("Guias", 11, 6, $DB, "", "", "$rw2[14]", 1, 0);
    $FB->llena_texto("Seguridad", 14, 6, $DB, "", "", "img_manifiestos/manifiestos/$rw2[17]", 1, 0);
    

} elseif ($tabla == "Agregar cotizacion") {


    // $conductor = "SELECT `idmani`, `mani_idConduc`, `mani_idVehiculo`, `mani_valor_cont`, `mani_fecha_ini`, `mani_fecha_fin`, `mani_piezas`, `mani_Contrato`, `mani_manifiesto`, `mani_fecha`, `mani_idusuIngreso`, `mani_ciudad_destino`,mani_ciudad_origen,mani_remesa_carga,mani_guias, mani_num_mani,mani_num_remesa FROM `manifiestos_viajes` WHERE  idmani=$id_param ";
    // $DB1->Execute($conductor);
    // $rw2 = mysqli_fetch_array($DB1->Consulta_ID);

    // if ($nivel_acceso != '1' and $nivel_acceso != '12') {
    //     $cond = "and idsedes=$id_sedes";
    // }
    $idsede = $_REQUEST["ide"];
    $FB->titulo_azul1("Agregar cotizacion",10,0, 7);  
   
    $FB->llena_texto("Cliente:", 1, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Nit:", 2, 1, $DB, "", "", "", 4, 0);

    $FB->llena_texto("Ciudad origen:", 3, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Ciudad destino:", 44, 1, $DB, "", "", "", 4, 0);
    $FB->llena_texto("Direccion Origen:", 5, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Direccion destino:", 6, 1, $DB, "", "", "", 4, 0);
    // $FB->llena_texto("Fecha inicial:", 4, 10, $DB, "", "", "$rw2[4]", 2, 0);
    // $FB->llena_texto("Fecha final:", 5, 10, $DB, "", "", "$rw2[5]", 2, 0);
    $FB->llena_texto("Descripcion mercancia:", 7, 1, $DB, "", "", "", 1, 0);
    // $FB->llena_texto("id_param", 8, 13, $DB, "", "", $id_param, 4, 0);
   
    $FB->llena_texto("Tipo de servicio:", 88, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Peso en kilos:", 9, 1, $DB, "", "", "", 4, 0);


    $FB->llena_texto("Valor Carga Mínima:", 10, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Valor Kilo adicional:", 111, 1, $DB, "", "", "", 4, 0);
    
    $FB->llena_texto("Volumen:", 12, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Valor Asegurado:", 13, 1, $DB, "", "", "", 4, 0);
    $FB->llena_texto("Valor seguro:", 14, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Valor kilos adicionales:", 15, 1, $DB, "", "", "", 4, 0);
    $FB->llena_texto("Valor servicio:", 166, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Valor Total (servicio + seguro):", 30, 1, $DB, "", "", "", 4, 0);
    $FB->llena_texto("Correo :", 188, 1, $DB, "", "", "", 4, 0);
    $FB->llena_texto("Numero Whatsapp:", 19, 1, $DB, "", "", "", 4, 0);
    $FB->llena_texto("Cantidad de piezas:", 20, 1, $DB, "", "", "$rw2[21]", 1, 0);
    $FB->llena_texto("Observaciones:", 21, 1, $DB, "", "", "$rw2[22]", 1, 0);
    
    $FB->titulo_azul1("Cotizar",9,0,7);  
    echo "</tr>";


    echo"<input  type='hidden' class='form-control'  name='nrespuestas' id='nrespuestas'  size='20' value=''>";
    $FB->llena_texto("Ciudad Origen:",4,2,$DB,"(SELECT `idciudades`, `ciu_nombre` FROM `ciudades`  where inner_estados=1)", "calculoo(this.value,param11.value,param8.value, \"cotizar.php\", 1);", "$valor20", 1, 0);
    $FB->llena_texto("Ciudad Destino:",11,2,$DB,"(SELECT `idciudades`, `ciu_nombre` FROM `ciudades`  where inner_estados=1 )","calculoo(param4.value,this.value ,param8.value, \"cotizar.php\", 1);","$valor21",1,0);

    // $FB->llena_texto("Ciudad Origen:",4,2,$DB,"(SELECT `idciudades`,`ciu_nombre` FROM `ciudades` where inner_estados=1 )", "", "$param4", 1, 1);
    // $FB->llena_texto("Ciudad Destino:",11,2,$DB,"(SELECT `idciudades`,`ciu_nombre` FROM `ciudades`  where inner_estados=1)", "verificar(this.value)", "$rw[11]", 1, 1);

    //echo "<div id='mensaje2'></div>";
    $FB->llena_texto("Valor de Prestamo:",16, 118, $DB, "", "", $rw[16],17, 0);
    //$FB->llena_texto("Abono:",17, 118, $DB, "", "", $rw[17], 4, 0);
    $FB->llena_texto("param17", 1, 13, $DB, "", "", "0", 5, 0); 

    $sql12="SELECT seg_nombre FROM `seguro`  order by idseguro desc limit 1";
    $DB1->Execute($sql12);
    $porcentaje=mysqli_fetch_array($DB1->Consulta_ID);
    $seguro=$porcentaje['0'];

    $FB->llena_texto("Seguro:",18, 126, $DB, "", "$seguro", $seguro, 17, 0);
    $FB->llena_texto("Peso KG:",26,1, $DB, "", "","$rw[24]" ,1, 0);	
    $FB->llena_texto("Volumen:",27,1, $DB, "", "","$rw[26]",17, 0);
    $valorapagar=0;
    //$FB->llena_texto("&iquest;Credito?:",7, 212, $DB, "3", "habilitarcredito(this.value,24,\"llega_sub3\",\"total valor\",1,0)","", 1,0);	
    $FB->llena_texto("&iquest;Credito?:", 28, 216, $DB, "3", "habilitarcredito(this.value,85,\"llega_sub3\",\"total valor\",1,0)", "$creditos", 1, 0);

    echo "</br>";
    //$FB->llena_texto("Credito:",8,2, $DB, "(SELECT `idcreditos`,`cre_nombre` FROM `creditos`)", "", "", 17, 1);
    echo "<tr><td colspan=2><div id='llega_sub3' >";
    $FB->llena_texto("param8", 1, 13, $DB, "", "", "0", 5, 0);
    echo '</div></td></tr>';

    echo"<tr><td>Tipo de servicio</td><td id='respuesta'>";

    echo"</td></tr>";

    echo "<tr><td><button type='button' class='btn btn-success' onclick='cotizarguia(23,\"llega_sub2\")'>Consultar</button></td></tr>";

    $FB->div_valores("mensaje2",6); 
    $FB->div_valores("destino_vesr",6); 

} elseif ($tabla == "Editar cotizacion"){

    $conductor = "SELECT `cot_id`, `cot_clirente`, `cot_nit`, `cot_origen`, `cot_destino`, `cot_direc_origen`, `cot_direc_destino`, `cot_desc_merc`, `cot_tipo_servi`, `cot_peso`, `cot_val_minima`, `cot_kilo_adi`, `cot_vol`, `cot_val_asegurado`, `cot_val_seguro`, `cot_val_kilos_adi`, `cot_val_servicio`, `cot__val_total`,cot_fecha,`cot_correo`,cot_Whatsapp,cot_piezas,cot_observaciones,cot_validada FROM `cotozaciones` where cot_id='$id_param'";
    $DB1->Execute($conductor);
    $rw2 = mysqli_fetch_array($DB1->Consulta_ID);

    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "and idsedes=$id_sedes";
    }
    $idsede = $_REQUEST["ide"];
    $FB->titulo_azul1("Agregar cotizacion",10,0, 7);  
    $FB->llena_texto("Cliente:", 1, 1, $DB, "", "", "$rw2[1]", 1, 0);
    $FB->llena_texto("Nit:", 2, 1, $DB, "", "", "$rw2[2]", 4, 0);
    $FB->llena_texto("Ciudad origen:", 3, 1, $DB, "", "", "$rw2[3]", 1, 0);
    $FB->llena_texto("Ciudad destino:", 44, 1, $DB, "", "", "$rw2[4]", 4, 0);
    $FB->llena_texto("Direccion Origen:", 5, 1, $DB, "", "", "$rw2[5]", 1, 0);
    $FB->llena_texto("Direccion destino:", 6, 1, $DB, "", "", "$rw2[6]", 4, 0);
    $FB->llena_texto("Descripcion mercancia:", 7, 1, $DB, "", "", "$rw2[7]", 1, 0);
    $FB->llena_texto("Tipo de servicio:", 88, 1, $DB, "", "", "$rw2[8]", 4, 0);
    $FB->llena_texto("Peso en kilos:", 9, 1, $DB, "", "", "$rw2[9]", 1, 0);
    $FB->llena_texto("Valor Carga Mínima:", 10, 1, $DB, "", "", "$rw2[10]", 4, 0);
    $FB->llena_texto("Valor Kilo adicional:", 111, 1, $DB, "", "", "$rw2[11]", 1, 0);
    $FB->llena_texto("Volumen:", 12, 1, $DB, "", "", "$rw2[12]", 4, 0);
    $FB->llena_texto("Cantidad de piezas:", 20, 1, $DB, "", "", "$rw2[21]", 1, 0);
    $FB->llena_texto("Valor Asegurado:", 13, 1, $DB, "", "", "$rw2[13]", 4, 0);
    $FB->llena_texto("Valor seguro:", 14, 1, $DB, "", "", "$rw2[14]", 1, 0);
    $FB->llena_texto("Valor kilos adicionales:", 15, 1, $DB, "", "", "$rw2[15]", 4, 0);
    $FB->llena_texto("Valor servicio:", 166, 1, $DB, "", "", "$rw2[16]", 1, 0);
    $FB->llena_texto("Valor Total (servicio + seguro):", 30, 1, $DB, "", "", "$rw2[17]", 4, 0);
    $FB->llena_texto("id_param", 20, 13, $DB, "", "", $id_param, 1, 0);
    $FB->llena_texto("Correo :", 188, 1, $DB, "", "", "$rw2[19]", 4, 0);
    $FB->llena_texto("Numero Whatsapp:", 19, 1, $DB, "", "", "$rw2[20]", 1, 0);
    $FB->llena_texto("Observaciones:", 21, 1, $DB, "", "", "$rw2[22]", 1, 0);
    $FB->llena_texto("Validado:", 22, 5, $DB, "", "", $rw2[23], 1, 0);
    $FB->titulo_azul1("Cotizar",9,0,7);  
    echo "</tr>";


    echo"<input  type='hidden' class='form-control'  name='nrespuestas' id='nrespuestas'  size='20' value=''>";
    $FB->llena_texto("Ciudad Origen:",4,2,$DB,"(SELECT `idciudades`, `ciu_nombre` FROM `ciudades`  where inner_estados=1)", "calculoo(this.value,param11.value,param8.value, \"cotizar.php\", 1);", "$valor20", 1, 0);
    $FB->llena_texto("Ciudad Destino:",11,2,$DB,"(SELECT `idciudades`, `ciu_nombre` FROM `ciudades`  where inner_estados=1 )","calculoo(param4.value,this.value ,param8.value, \"cotizar.php\", 1);","$valor21",1,0);

    // $FB->llena_texto("Ciudad Origen:",4,2,$DB,"(SELECT `idciudades`,`ciu_nombre` FROM `ciudades` where inner_estados=1 )", "", "$param4", 1, 1);
    // $FB->llena_texto("Ciudad Destino:",11,2,$DB,"(SELECT `idciudades`,`ciu_nombre` FROM `ciudades`  where inner_estados=1)", "verificar(this.value)", "$rw[11]", 1, 1);

    //echo "<div id='mensaje2'></div>";
    $FB->llena_texto("Valor de Prestamo:",16, 118, $DB, "", "", $rw[16],17, 0);
    //$FB->llena_texto("Abono:",17, 118, $DB, "", "", $rw[17], 4, 0);
    $FB->llena_texto("param17", 1, 13, $DB, "", "", "0", 5, 0); 

    $sql12="SELECT seg_nombre FROM `seguro`  order by idseguro desc limit 1";
    $DB1->Execute($sql12);
    $porcentaje=mysqli_fetch_array($DB1->Consulta_ID);
    $seguro=$porcentaje['0'];

    $FB->llena_texto("Seguro:",18, 126, $DB, "", "$seguro", $seguro, 17, 0);
    $FB->llena_texto("Peso KG:",26,1, $DB, "", "","$rw[24]" ,1, 0);	
    $FB->llena_texto("Volumen:",27,1, $DB, "", "","$rw[26]",17, 0);
    $valorapagar=0;
    //$FB->llena_texto("&iquest;Credito?:",7, 212, $DB, "3", "habilitarcredito(this.value,24,\"llega_sub3\",\"total valor\",1,0)","", 1,0);	
    $FB->llena_texto("&iquest;Credito?:", 28, 216, $DB, "3", "habilitarcredito(this.value,85,\"llega_sub3\",\"total valor\",1,0)", "$creditos", 1, 0);

    echo "</br>";
    //$FB->llena_texto("Credito:",8,2, $DB, "(SELECT `idcreditos`,`cre_nombre` FROM `creditos`)", "", "", 17, 1);
    echo "<tr><td colspan=2><div id='llega_sub3' >";
    $FB->llena_texto("param8", 1, 13, $DB, "", "", "0", 5, 0);
    echo '</div></td></tr>';

    echo"<tr><td>Tipo de servicio</td><td id='respuesta'>";

    echo"</td></tr>";

    echo "<tr><td><button type='button' class='btn btn-success' onclick='cotizarguia(23,\"llega_sub2\")'>Consultar</button></td></tr>";

    $FB->div_valores("mensaje2",6); 
    $FB->div_valores("destino_vesr",6); 
    
    echo'</table>';
    $link="https://sistema.transmillas.com/cotizaciones/Cotizacion$rw2[0].pdf";
    if ($rw2[20]!="") {
        $FB->titulo_azul1("Enviar guia",1,0,7); 
        echo'<tr><td class="text"><textarea type="tel" disabled placeholder="Numero de whatsapp" id="Mensaje" name="Mensaje" style="width: 943px; height: 135px;">📄 En respuesta a tu solicitud de la cotización número #'.$rw2[0].':

        Hemos generado el documento correspondiente, donde encontrarás el valor estimado del servicio, así como los detalles relacionados con el envío.

        🔗 Puedes consultarlo en el siguiente enlace:
        '.$link.'

        📦 ¿Deseas crear el servicio con esta cotización?

        ✅ Responde con SÍ para continuar con la creación del servicio.
        ❌ Responde con NO si deseas hacer cambios o cancelar el proceso.

        Gracias por confiar en Transmillas Logística y Transportadora S.A.S.
        Estamos atentos a tu respuesta.</textarea></td></tr>';
    
    
        $telefono=$rw2[20];
        $texto1=$rw2[0];
        $texto2=$link;
        echo '<tr><td class="text"><a class="btn btn-primary btn-lg" href="#" onclick="enviarAlertaWhat(\''.$telefono.'\', 35, \''.$texto1.'\', \''.$texto2.'\');">Enviar</a></td></tr>';
        // echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'enviarAlertaWhat(telefono, tipo,texto1,texto2) Enviar</a><td></tr>';
        echo'<div id="loading"  style="display: none;">
        <img src="images/loading.gif" alt="Cargando..."></div>';

        # code...
    }
   
} elseif ($tabla == "agregadocumentos") {

    // $FB->llena_texto("Valor a Pagar:",4, 1, $DB, "", "", "$rw[4]", 1, 0);


    $FB->llena_texto("Formato de pago ", 12, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Formato de pago ", 13, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Formato de pago ", 14, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Formato de pago ", 15, 6, $DB, "", "", "", 1, 0);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);
} elseif ($tabla == "LlamarReclamos") {

    $idservicio = $_REQUEST["dir"];
    $sql = "SELECT `idreclamos`, `fec_descricomf` FROM `reclamos` where idreclamos=$id_param ";
    $DB->Execute($sql);

    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $FB->llena_texto("Descripcion de LLamada:", 2, 9, $DB, "", "", "$rw[1]", 1, 1); //aqui voy ........................	
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);
} elseif ($tabla == "Llamar Remesas") {

    $estadofactura = '2';
    $nombre = explode(" ", $id_nombre);
    $descllamada = $nombre[0] . " " . $nombre[1] . '<br>';
    $descllamada .= "$fechatiempo";
    $dir = $_REQUEST["ide"];

    $sql = "SELECT `idgastos`,  `gas_descripcion`, `gas_peso`, `gas_piezas`,  `gas_cantcom`, `gas_empresa`, `gas_bus`, `gas_telconductor`, `gas_iduserremesa`, `gas_nomremesa` from gastos  where idgastos=$id_param ";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $sql2 = "UPDATE `gastos` SET  `gas_estadollamada`='2',`gas_userllamo`='$descllamada',gas_fechallamo='$fechatiempo' WHERE `idgastos`='$id_param' ";
    $DB->Execute($sql2);

    echo "<p  align='left'>TELEFONO: $rw[7]<br></p>";
    $FB->llena_texto("Descripcion:", 1, 1, $DB, "", "", "$rw[1]", 1, 0);
    $FB->llena_texto("Peso:", 2, 1, $DB, "", "", "$rw[2]", 1, 0);
    $FB->llena_texto("Piezas:", 3, 1, $DB, "", "", "$rw[3]", 1, 0);
    $FB->llena_texto("Valor a Pagar:", 4, 1, $DB, "", "", "$rw[4]", 1, 0);
    $FB->llena_texto("Empresa:", 5, 1, $DB, "", "", "$rw[5]", 1, 0);
    $FB->llena_texto("Bus:", 6, 1, $DB, "", "", "$rw[6]", 1, 0);
    $FB->llena_texto("Telefono:", 1, 1, $DB, "", "", "$rw[7]", 1, 0);
    $FB->llena_texto("Pasar a Asignar:", 7, 5, $DB, "", "", "", 1, 0);
    $FB->llena_texto("MOTIVO:", 8, 1, $DB, "", "", "", 4, 0);
    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "Seguimiento Datos") {

    $estadofactura = 'verificacion';

    $sql = "SELECT `idclientes`, `cli_iddocumento`, `cli_telefono`, `cli_email`, `cli_idciudad`, `cli_direccion`, `cli_nombre`, `cli_clasificacion`,`ser_telefonocontacto`, `ser_destinatario`, `ser_direccioncontacto`,`ser_ciudadentrega`, `ser_tipopaquete`, `ser_paquetedescripcion`, `ser_fechaentrega`,`ser_prioridad`,  `ser_valorprestamo`, `ser_valorabono`, `ser_valorseguro`, `idservicios`,cli_retorno,idclientesdir FROM 
serviciosdia  where idservicios=$id_param ";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);
    $actuliza = "no";
    $param15 = $rw[15];
    if ($param15 == "Envio Oficina") {

        include("oficina.php");
    } else if ($param15 == "Compra") {

        include("recoleccion_compra.php");
    } else {
        include("recoleccion_datos.php");
    }

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
//$FB->llena_texto("id_param0", 1, 13, $DB, "", "", $id_usuario, 5, 0);
} else if ($tabla == "Reaccionar") {
    $rw[4] = 0;

    $idciudad = $_REQUEST["idciudad"];
    $FB->llena_texto("Tipo de Operador:", 1, 82, $DB, $vehiculo, "cambio_ajax2(this.value, 9, \"llega_sub1\", \"param2\", 1, $idciudad)", @$rw[1], 17, 1);
    $FB->llena_texto("OPerador:", 2, 444, $DB, "llega_sub1", "", "", 4, 0);

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("condicion", 1, 13, $DB, "", "", "2", 5, 0);
} else if ($tabla == "Reaccionarsaldos") {
    $rw[4] = 0;

    $idciudad = $_REQUEST["idciudad"];
    $FB->llena_texto("Tipo de Operador:", 1, 82, $DB, $vehiculo, "cambio_ajax2(this.value, 9, \"llega_sub1\", \"param2\", 1, $idciudad)", @$rw[1], 17, 1);
    $FB->llena_texto("OPerador:", 2, 444, $DB, "llega_sub1", "", "", 4, 0);

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("condicion", 1, 13, $DB, "", "", "4", 5, 0);
} else if ($tabla == "Reaccionardos") {
    $rw[4] = 0;

    $idciudad = $_REQUEST["idciudad"];
    $FB->llena_texto("Tipo de Operador:", 1, 82, $DB, $vehiculo, "cambio_ajax2(this.value, 9, \"llega_sub1\", \"param2\", 1, $idciudad)", @$rw[1], 17, 1);
    $FB->llena_texto("OPerador:", 2, 444, $DB, "llega_sub1", "", "", 4, 0);

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("condicion", 1, 13, $DB, "", "", "3", 5, 0);
} else if ($tabla == "Reasignar") {

    $idciudad = $_REQUEST["idciudad"];
    $FB->llena_texto("Tipo de Operador:", 1, 82, $DB, $vehiculo, "cambio_ajax2(this.value, 8, \"llega_sub1\", \"param2\", 1, $idciudad)", @$rw[1], 17, 1);
    $FB->llena_texto("OPerador:", 2, 444, $DB, "llega_sub1", "", "", 4, 1);

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "addremesas") {

    if ($nivel_acceso == 1) {
        $cond = "";
    } else {
        $cond = "and idsedes=$id_sedes";
    }

    $operador = $_REQUEST["ide"];

    $FB->llena_texto("Sede Origen:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0  $cond )", "cambio_ajax2(this.value, 15, \"llega_sub1\", \"param7\", 1, 0)", "$id_sedes", 2, 1);
    $FB->llena_texto("Sede Destino:", 2, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0  )", "", "", 2, 1);
    $FB->llena_texto("Empresa Remesa:", 3, 85, $DB, $empresa, "", "", 2, 1);
//	$FB->llena_texto("# BUS:", 4, 1, $DB, "", "", "", 1, 0);
//	$FB->llena_texto("Tel Conductor:", 5, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Pagar en:", 4, 82, $DB, $sedecobra, "", "", 2, 1);
   
    $FB->llena_texto("Abono:", 14, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Metodo de Abono:", 15, 2, $DB, "(SELECT CONCAT(idtipospagos, '/', pag_nombre,'/',pag_numerocuenta) As id,pag_nombre from tipospagos where pag_estado like '%Activo%' order by idtipospagos )", "", "1", 1, 1);
   
    $FB->llena_texto("param7", 7, 13, $DB, "", "", "$operador", 5, 0);
    $FB->llena_texto("Descripcion:", 8, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Peso:", 9, 1, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Piezas:", 10, 1, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Metodo de Pago:", 5, 2, $DB, "(SELECT CONCAT(idtipospagos, '/', pag_nombre,'/',pag_numerocuenta) As id,pag_nombre from tipospagos where pag_estado like '%Activo%' order by idtipospagos )", "", "1", 1, 1);
    $FB->llena_texto("Valor:", 11, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Imagen", 12, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param13", 13, 13, $DB, "", "", $id_param, 5, 0);

} else if ($tabla == "addgastos") {
    if ($nivel_acceso == 1) {
        $cond = "";
    } else {
        $cond = "and idsedes=$id_sedes";
    }

    $operador = $_REQUEST["ide"];

    $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0  $cond )", "cambio_ajax2(this.value, 15, \"llega_sub1\", \"param2\", 1, 0)", "", 2, 1);
    $FB->llena_texto("Operario:", 2, 4, $DB, "llega_sub1", "", "", 2, 1);
    $FB->llena_texto("fechaingreso", 3, 10, $DB, "", "", "$fechaactual", 2, 0);
//        $FB->llena_texto("Tipo de transaccion:", 5, 82, $DB, $transaccionoper, "", "", 2, 1);
    $FB->llena_texto("gastos", 5, 13, $DB, "", "", "Gastos", 5, 0);
    $FB->llena_texto("Valor:", 4, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 6, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Imagen", 7, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param8", 8, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "dineroremesas") {

    $FB->titulo_azul1("Aprobar", 1, 0, 5);
    $FB->titulo_azul1("Fecha", 1, 0, 0);
    $FB->titulo_azul1("Usuario", 1, 0, 0);
    $FB->titulo_azul1("Sede Origen", 1, 0, 0);
    $FB->titulo_azul1("Sede Destino", 1, 0, 0);
    $FB->titulo_azul1("Empresa TR", 1, 0, 0);
    $FB->titulo_azul1("# BUS", 1, 0, 0);
    $FB->titulo_azul1("Tel Conductor", 1, 0, 0);
    $FB->titulo_azul1("Pagar en?", 1, 0, 0);
    $FB->titulo_azul1("Abono", 1, 0, 0);
    $FB->titulo_azul1("Abono en", 1, 0, 0);
    $FB->titulo_azul1("Valor Aprobado", 1, 0, 0);
    $FB->titulo_azul1("Pago en", 1, 0, 0);
    $FB->titulo_azul1("Operador", 1, 0, 0);
    $FB->titulo_azul1("Descripcion", 1, 0, 0);
    $FB->titulo_azul1("Peso", 1, 0, 0);
    $FB->titulo_azul1("Piezas", 1, 0, 0);


    $estadoPagar = $_REQUEST["ide"];
    
    if ($estadoPagar == 'Pendiente') {
        $FB->titulo_azul1("Pagar", 1, 0, 0);
    }

    $sql = "SELECT `idgastos`, `gas_fecharegistro`, `usu_nombre`, `gas_idciudadori`, ori.`sed_nombre` AS sede_ori,
            des.`sed_nombre` AS sede_des, `gas_empresa`, `gas_bus`, `gas_telconductor`,`gas_pagar`,gas_metodopago,`gas_iduserremesa`, 
            `gas_nomremesa`,`gas_descripcion`,`gas_peso`,`gas_piezas`,`gas_valor`,gas_usucom,
            gas_cantcom,gas_feccom ,gas_idciudaddes,gas_iduserrecoge,gas_recogio,gas_entrego,
            gas_fecrecogida,gas_abonopago,gas_abono,gas_nomvalida 
        FROM `viajesremesas` 
            inner join usuarios on gas_idusuario=idusuarios 
            inner join sedes ori on ori.idsedes=gas_idciudadori
            inner join sedes des on des.idsedes=gas_idciudaddes
        WHERE gas_idseguimientoremesas=$id_param AND gas_estado = '$estadoPagar'";
    $DB1->Execute($sql);
    $va = 0;
    
    $total = 0;

    while ($rw1 = mysqli_fetch_assoc($DB1->Consulta_ID)) {
        $id_p = $rw1['idgastos'];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        $total += $rw1['gas_valor'];
        $rw1['gas_valor'] = number_format($rw1['gas_valor'], 0, ".", ".");
//        $sql2 = "SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes='{$rw1['gas_idciudadori']}'";
//        $DB->Execute($sql2);
//        $rw = mysqli_fetch_row($DB->Consulta_ID);

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
       
        if ($nivel_acceso == 1 or $nivel_acceso == 5 or $nivel_acceso == 11) {
            if (empty($rw1['gas_nomvalida'])) {
                echo "<td align='center'><div id='campo$va'><a   onclick='cambio_ajax2($id_p, 87, \"campo$va\", \"$va\", 1, $id_p)'; style='cursor: pointer;' title='Confirmar' ><img src='img/Confirmar1.png'></a></div></td>";
            } else {
                echo "<td align='center'><div id='campo$va'><img src='img/Confirmar.png'></a></div></td>";
            }
        } else {
            echo "<td align='center'><div id='campo$va'><img src='img/Confirmar.png'></a></div></td>";
        }
       
        echo "<td>{$rw1['gas_fecharegistro']}</td>
            <td>{$rw1['usu_nombre']}</td>
            <td>{$rw1['sede_ori']}</td>
            <td>{$rw1['sede_des']}</td>
            <td>{$rw1['gas_empresa']}</td>
            <td>{$rw1['gas_bus']}</td>
            <td>{$rw1['gas_telconductor']}</td>
            <td>{$rw1['gas_pagar']}</td>
            <td>{$rw1['gas_abono']}</td>
            <td>{$rw1['gas_abonopago']}</td>
            <td style='text-align: right;'>{$rw1['gas_valor']}</td>
            <td>{$rw1['gas_metodopago']}</td>
            <td>{$rw1['gas_nomremesa']}</td>
            <td>{$rw1['gas_descripcion']}</td>
            <td style='text-align: right;'>{$rw1['gas_peso']}</td>
            <td style='text-align: right;'>{$rw1['gas_piezas']}</td>";
       

        if ($estadoPagar == 'Pendiente') {
            echo "<td align='center'><a href='confirmarok.php?id_param={$rw1['idgastos']}&id_param2=CambiarEstadoRemesaPagada'  style='cursor: pointer;' title='Confirmar' ><img src='img/Confirmar1.png'></a></td>";
        }

        echo "</tr>";
    }
    
    $FB->titulo_azul1(" ------", 1, 0, 10);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);

    $FB->titulo_azul1("Total:", 1, 0, 0);
    $FB->titulo_azul1(number_format($total, 0, ".", "."), 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    
} else if ($tabla == "dinerogastos") {

    $FB->titulo_azul1("Confirmar", 1, 0, 5);
    $FB->titulo_azul1("Sede", 1, 0, 0);
    $FB->titulo_azul1("Operario", 1, 0, 0);
    $FB->titulo_azul1("Fecha de Ingreso", 1, 0, 0);
    $FB->titulo_azul1("Valor", 1, 0, 0);
    $FB->titulo_azul1("Descripción", 1, 0, 0);
    $FB->titulo_azul1("Imagen", 1, 0, 0);

    $sql = "SELECT `asi_idpromotor`, `asi_fecha`, `asi_fechaingreso`, `asi_valor`,  
            `asi_idautoriza`, `asi_idciudad`, asi_tipo, asi_descripcion, 
            `asi_idseguimientoremesas`, ori.sed_nombre, usu_nombre,asi_usercom,idasignaciondinero
        FROM `asignaciondinero` 
            inner join usuarios on asi_idpromotor=idusuarios 
            inner join sedes ori on ori.idsedes=asi_idciudad
        WHERE asi_idseguimientoremesas=$id_param";
    $DB1->Execute($sql);
    $va = 0;
    
    $total = 0;

    while ($rw1 = mysqli_fetch_assoc($DB1->Consulta_ID)) {
        $id_asi = $rw1['idasignaciondinero'];
        $id_p = $rw1['asi_idpromotor'];
        $asi_usercom = $rw1['asi_usercom'];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        $total += $rw1['asi_valor'];
        $rw1['asi_valor'] = number_format($rw1['asi_valor'], 0, ".", ".");
//        $sql2 = "SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes='{$rw1['asi_idciudad']}'";
//        $DB->Execute($sql2);
//        $rw = mysqli_fetch_row($DB->Consulta_ID);
        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";

        if($id_p==$id_usuario and $asi_usercom!=''){

            echo "<td>CONFIRMADO</td>";

        }else if($asi_usercom=='' and $id_p==$id_usuario ){
            echo "<td>POR CONFIRMAR</td>";
        }else if($nivel_acceso==1){

            echo "<td align='center' >
            <a  onclick='pop_dis10($id_asi,\"Confirmargastos\",\"Gastos\")';  style='cursor: pointer;' title='Confirmar' ><img src='img/Confirmar1.png'></a></td>";

        }else if($asi_usercom==''){
            echo "<td>POR CONFIRMAR</td>";
        }else if($asi_usercom!=''){
            echo "<td>CONFIRMADO</td>";
        }
        echo "<td>{$rw1['sed_nombre']}</td>
            <td>{$rw1['usu_nombre']}</td>
            <td>{$rw1['asi_fechaingreso']}</td>
            <td style='text-align: right;'>{$rw1['asi_valor']}</td>
            <td>{$rw1['asi_descripcion']}</td>";
            $LT->llenadocs2($DB, "asignaciondinero",$id_asi, 1, 35, 1);
        echo "</tr>";
    }
    
    $FB->titulo_azul1(" ------", 1, 0, 10);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1("Total:", 1, 0, 0);
    $FB->titulo_azul1(number_format($total, 0, ".", "."), 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    
} else if ($tabla == "ConfirmarSeguimientoRemesa") {

    $FB->llena_texto("Valor Confirmado:", 1, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Foto Comprobante", 2, 6, $DB, "", "", "", 1, 0);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "ConfirmarSeguimientoRemesa", 5, 0);
    
} else if ($tabla == "remesas") {

    $FB->titulo_azul1("Fecha", 1, 0, 5);
    $FB->titulo_azul1("Usuario", 1, 0, 0);
    $FB->titulo_azul1("Sede Origen", 1, 0, 0);
    $FB->titulo_azul1("Sede Destino", 1, 0, 0);
    $FB->titulo_azul1("Empresa TR", 1, 0, 0);
    $FB->titulo_azul1("# BUS", 1, 0, 0);

    $sql = "SELECT `idgastos`, `gas_fecharegistro`, `usu_nombre`, `gas_idciudadori`, `sed_nombre`, `gas_empresa`, `gas_bus`, `gas_telconductor`,`gas_pagar`,`gas_iduserremesa`, `gas_nomremesa`,`gas_descripcion`,`gas_peso`,`gas_piezas`,`gas_valor`,gas_usucom,gas_cantcom,gas_feccom ,gas_idciudaddes,gas_iduserrecoge,gas_recogio,gas_entrego,gas_fecrecogida FROM `gastos` inner join usuarios on gas_idusuario=idusuarios inner join sedes on idsedes=gas_idciudaddes WHERE idgastos=$id_param ";
    $DB1->Execute($sql);
    $va = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        $rw1[16] = number_format($rw1[16], 0, ".", ".");
        $sql2 = "SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes='$rw1[3]'";
        $DB->Execute($sql2);
        $rw = mysqli_fetch_row($DB->Consulta_ID);

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[1] . "</td>
			<td>" . $rw1[2] . "</td>
			<td>" . $rw[1] . "</td>
			<td>" . $rw1[4] . "</td>
			<td>" . $rw1[5] . "</td>
			<td>" . $rw1[6] . "</td>";
        echo "</tr>";
        $FB->titulo_azul1("Tel Conductor", 1, 0, 5);
        $FB->titulo_azul1("Pagar en?", 1, 0, 0);
        $FB->titulo_azul1("Operario Remesa", 1, 0, 0);
        $FB->titulo_azul1("Descripcion", 1, 0, 0);
        $FB->titulo_azul1("Peso", 1, 0, 0);
        $FB->titulo_azul1("Piezas", 1, 0, 0);
        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";

        echo "<td>" . $rw1[7] . "</td>
			<td>" . $rw1[8] . "</td>
			<td>" . $rw1[10] . "</td>
			<td>" . $rw1[11] . "</td>
			<td>" . $rw1[12] . "</td>
			<td>" . $rw1[13] . "</td>";
        echo "</tr>";

        $FB->titulo_azul1("Pagar", 1, 0, 5);
        $FB->titulo_azul1("Confirmo", 1, 0, 0);
        $FB->titulo_azul1("Valor Aprobado", 1, 0, 0);
        $FB->titulo_azul1("Fecha Confirmacion", 1, 0, 0);
        $FB->titulo_azul1("Fecha Recogida", 1, 0, 0);
        $FB->titulo_azul1("Operario Recoge", 1, 0, 0);
        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";

        echo "
<td>" . $rw1[14] . "</td>
<td>" . $rw1[15] . "</td>
<td>" . $rw1[16] . "</td>
<td>" . $rw1[17] . "</td>
";
        $sql5 = "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE `idusuarios`='$rw1[19]' ";
        $DB->Execute($sql5);
        $nombreuser = $DB->recogedato(1);
        echo "<td>" . $rw1[22] . "</td>";
        echo "<td>" . $nombreuser . "</td>";

        echo "</tr>";
    }
} else if ($tabla == "asignar remesa") {

    $idciudad = $_REQUEST["idciudad"];
    /* $urls=$_SERVER['PHP_SELF'];
      $obtenerurl=explode('?',$urls,1); */

    $FB->llena_texto("Tipo de Operador:", 1, 82, $DB, $vehiculo, "cambio_ajax2(this.value, 8, \"llega_sub1\", \"param2\", 1, $idciudad)", @$rw[1], 17, 1);
    $FB->llena_texto("OPerador:", 2, 444, $DB, "llega_sub1", "", "", 4, 1);

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("condicion", 1, 13, $DB, "", "", "3", 5, 0);
    $FB->llena_texto("url", 1, 13, $DB, "", "", "$url", 5, 0);
} else if ($tabla == "asignar dinero") {

    $FB->titulo_azul1("Fecha", 1, 0, 5);
    $FB->titulo_azul1("Operador", 1, 0, 0);
    $FB->titulo_azul1("Tipo", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Descripcion ", 1, 0, 0);
    $FB->titulo_azul1("Asigno ", 1, 0, 0);

    $sql = "SELECT `idasignaciondinero`,`asi_fecha`, usu_nombre, `asi_tipo`, `asi_valor`,  `asi_descripcion`, `asi_idautoriza`, `asi_idpromotor` FROM `asignaciondinero` inner join usuarios on asi_idpromotor=idusuarios WHERE idasignaciondinero>0  and idasignaciondinero=$id_param  ";
    $DB1->Execute($sql);
    $va = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[1] . "</td>
		<td>" . $rw1[2] . "</td>
		<td>" . $rw1[3] . "</td>
		<td>" . $rw1[4] . "</td>
		<td>" . $rw1[5] . "</td>
		";
        $slqs = "SELECT usu_nombre FROM usuarios WHERE idusuarios='$rw1[6]' ";
        $DB->Execute($slqs);
        $asigno = $DB->recogedato(0);
        echo "<td>" . $asigno . "</td>
		";
        echo "</tr>";
    }
} else if ($tabla == "detalleprestamos") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("VALOR", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT ser_guiare,ser_valorprestamo FROM `servicios` inner join cuentaspromotor on idservicios=cue_idservicio WHERE cue_fecharecogida  like '$fechaab%' and  ser_estado!=100   and cue_idciudadori=$id_param and ser_valorprestamo>0 order by cue_fecharecogida";

    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				";
        $rw1[1] = str_replace(".", "", $rw1[1]);
        $sumatotal = $rw1[1] + $sumatotal;
        echo "</tr>";
    }
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detalleexcedente") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("%PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("PRESTAMO", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT cue_numeroguia,cue_porprestamo,cue_prestamo FROM `cuentaspromotor`  inner join ciudades on idciudades=cue_idciudaddes WHERE `cue_fecha` like '$fechaab%'  and cue_estado=10  and cue_tipoevento=1 and  cue_prestamo>0 and inner_sedes=$id_param  order by `cue_fecha` ";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				";
        $rw1[1] = str_replace(".", "", $rw1[1]);
        $rw1[2] = str_replace(".", "", $rw1[2]);

        $sumatotal = $rw1[1] + $rw1[2] + $sumatotal;
        echo "</tr>";
    }
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detalletranferido") {

    $FB->titulo_azul1("ID", 1, 0, 5);
    $FB->titulo_azul1("GUIA", 1, 0, 0);
    $FB->titulo_azul1("VALOR", 1, 0, 0);
    $FB->titulo_azul1("USUARIO VERIFICADOR", 1, 0, 0);
    $fechaab = $_REQUEST["ide"];

    $slq101 = "SELECT pag_idservicio,pag_guia,pag_valor,pag_userverifica FROM `pagoscuentas` inner join usuarios on idusuarios=pag_idoperario where `pag_fecha` like '$fechaab%' and usu_idsede='$id_param'";
    $DB->Execute($slq101);
    while ($rw1 = mysqli_fetch_row($DB->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
			<td>" . $rw1[1] . "</td>
			<td>" . $rw1[2] . "</td>
			<td>" . $rw1[3] . "</td>
			";
    }
} else if ($tabla == "detallecontado") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("%PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("FLETE", 1, 0, 0);
    $FB->titulo_azul1("%SEGURO", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT cue_numeroguia,cue_porprestamo,cue_prestamo,cue_valorflete,cue_pordeclarado FROM `cuentaspromotor`  inner join ciudades on idciudades=cue_idciudadori WHERE `cue_fecharecogida` like '$fechaab%'  and cue_estado<=14  and cue_tipoevento=1 and  cue_pendientecobrar=0 and inner_sedes=$id_param and cue_idservicio not in (SELECT pag_idservicio FROM `pagoscuentas` inner join usuarios on idusuarios=pag_idoperario where `pag_fecha` like '$fechaab%' and usu_idsede='$id_param')  order by `cue_fecha` ";
    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				";
        $rw1[1] = str_replace(".", "", $rw1[1]);
        $rw1[2] = str_replace(".", "", $rw1[2]);
        $rw1[3] = str_replace(".", "", $rw1[3]);
        $rw1[4] = str_replace(".", "", $rw1[4]);

        $sumatotal = $rw1[3] + $rw1[4] + $sumatotal;
        echo "</tr>";
    }
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallepxc") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("%PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("FLETE", 1, 0, 0);
    $FB->titulo_azul1("%SEGURO", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    //$sql="SELECT cue_numeroguia,cue_porprestamo,cue_prestamo,cue_valorflete,cue_pordeclarado FROM `cuentaspromotor`  inner join ciudades on idciudades=cue_idciudadori WHERE `cue_fechapcobrar` like '$fechaab%'  and cue_estado<=14  and cue_tipoevento=1 and  cue_pendientecobrar=2 and inner_sedes=$id_param  order by `cue_fecha` ";
    $sql = "SELECT cue_numeroguia,cue_porprestamo,cue_prestamo,cue_valorflete,cue_pordeclarado FROM `cuentaspromotor`  inner join usuarios on idusuarios=cue_idoperpendiente WHERE `cue_fechapcobrar` like '$fechaab%'  and cue_estado<=14  and cue_tipoevento=1 and cue_pendientecobrar=2 and usu_idsede=$id_param  order by `cue_fecha` ";

    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				";
        $rw1[1] = str_replace(".", "", $rw1[1]);
        $rw1[2] = str_replace(".", "", $rw1[2]);
        $rw1[3] = str_replace(".", "", $rw1[3]);
        $rw1[4] = str_replace(".", "", $rw1[4]);

        $sumatotal = $rw1[1] + $rw1[2] + $rw1[3] + $rw1[4] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallealcobro") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("%PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("FLETE", 1, 0, 0);
    $FB->titulo_azul1("%SEGURO", 1, 0, 0);
    $FB->titulo_azul1("- ABONO", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT cue_numeroguia,cue_porprestamo,cue_prestamo,cue_valorflete,cue_pordeclarado,cue_abono FROM `cuentaspromotor`  inner join ciudades on idciudades=cue_idciudaddes WHERE `cue_fecha` like '$fechaab%' and  cue_estado>=8  and cue_estado<=14  and cue_tipoevento=3 and inner_sedes=$id_param  and cue_idservicio not in (SELECT pag_idservicio FROM `pagoscuentas` inner join usuarios on idusuarios=pag_idoperario where `pag_fecha` like '$fechaab%' and usu_idsede='$id_param') order by `cue_fecha` ";
    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				<td>" . $rw1[5] . "</td>
				";
        $rw1[1] = str_replace(".", "", $rw1[1]);
        $rw1[2] = str_replace(".", "", $rw1[2]);
        $rw1[3] = str_replace(".", "", $rw1[3]);
        $rw1[4] = str_replace(".", "", $rw1[4]);
        $rw1[5] = str_replace(".", "", $rw1[5]);

        $sumatotal = $rw1[1] + $rw1[2] + $rw1[3] + $rw1[4] - $rw1[5] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallpagasalcobro") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("%PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("FLETE", 1, 0, 0);
    $FB->titulo_azul1("%SEGURO", 1, 0, 0);
    $FB->titulo_azul1("- ABONO", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT cue_numeroguia,cue_porprestamo,cue_prestamo,cue_valorflete,cue_pordeclarado,cue_abono FROM `cuentaspromotor`  inner join ciudades on idciudades=cue_idciudaddes WHERE `cue_fecha` like '$fechaab%'  and cue_estado=10  and cue_tipoevento=3 and inner_sedes=$id_param  and cue_idservicio not in (SELECT pag_idservicio FROM `pagoscuentas` inner join usuarios on idusuarios=pag_idoperario where `pag_fecha` like '$fechaab%' and usu_idsede='$id_param')  order by `cue_fecha` ";
    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				<td>" . $rw1[5] . "</td>
				";
        $rw1[1] = str_replace(".", "", $rw1[1]);
        $rw1[2] = str_replace(".", "", $rw1[2]);
        $rw1[3] = str_replace(".", "", $rw1[3]);
        $rw1[4] = str_replace(".", "", $rw1[4]);
        $rw1[5] = str_replace(".", "", $rw1[5]);

        $sumatotal = $rw1[1] + $rw1[2] + $rw1[3] + $rw1[4] - $rw1[5] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallependiente") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("%PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("PRESTAMO", 1, 0, 0);
    $FB->titulo_azul1("FLETE", 1, 0, 0);
    $FB->titulo_azul1("%SEGURO", 1, 0, 0);
    $FB->titulo_azul1("- ABONO", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT cue_numeroguia,cue_porprestamo,cue_prestamo,cue_valorflete,cue_pordeclarado,cue_abono FROM `cuentaspromotor`  inner join ciudades on idciudades=cue_idciudaddes WHERE `cue_fecha` like '$fechaab%' and  cue_estado>=8  and cue_estado!=10  and  cue_estado<=14  and cue_tipoevento=3 and inner_sedes=$id_param  order by `cue_fecha` ";
    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				<td>" . $rw1[5] . "</td>
				";
        $rw1[1] = str_replace(".", "", $rw1[1]);
        $rw1[2] = str_replace(".", "", $rw1[2]);
        $rw1[3] = str_replace(".", "", $rw1[3]);
        $rw1[4] = str_replace(".", "", $rw1[4]);
        $rw1[5] = str_replace(".", "", $rw1[5]);

        $sumatotal = $rw1[1] + $rw1[2] + $rw1[3] + $rw1[4] - $rw1[5] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallecompras") {


    $FB->titulo_azul1("ID", 1, 0, 5);
    $FB->titulo_azul1("Fecha", 1, 0, 0);
    $FB->titulo_azul1("Operador", 1, 0, 0);
    $FB->titulo_azul1("Tipo", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Descripcion ", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT `idasignaciondinero`,`asi_fecha`, usu_nombre, `asi_tipo`, `asi_valor`,  `asi_descripcion`, `asi_idautoriza`, `asi_idpromotor` FROM `asignaciondinero` inner join usuarios on asi_idpromotor=idusuarios WHERE idasignaciondinero>0  and `asi_fecha` like '$fechaab%'  and asi_idciudad=$id_param and asi_tipo='Asignar Dinero' order by `asi_fecha` ";
    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				<td>" . $rw1[5] . "</td>
				";

        $rw1[4] = str_replace(".", "", $rw1[4]);

        $sumatotal = $rw1[4] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detalletranspaso") {


    $FB->titulo_azul1("ID", 1, 0, 5);
    $FB->titulo_azul1("Fecha", 1, 0, 0);
    $FB->titulo_azul1("Operador", 1, 0, 0);
    $FB->titulo_azul1("Tipo", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Descripcion ", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT `idasignaciondinero`,`asi_fecha`, usu_nombre, `asi_tipo`, `asi_valor`,  `asi_descripcion`, `asi_idautoriza`, `asi_idpromotor` FROM `asignaciondinero` inner join usuarios on asi_idpromotor=idusuarios WHERE idasignaciondinero>0  and `asi_fecha` like '$fechaab%'  and asi_idciudad=$id_param and asi_tipo='Transpaso Dinero' order by `asi_fecha` ";
    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				<td>" . $rw1[5] . "</td>
				";

        $rw1[4] = str_replace(".", "", $rw1[4]);

        $sumatotal = $rw1[4] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallegastos") {


    $FB->titulo_azul1("ID", 1, 0, 5);
    $FB->titulo_azul1("Fecha", 1, 0, 0);
    $FB->titulo_azul1("Operador", 1, 0, 0);
    $FB->titulo_azul1("Tipo", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Descripcion ", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT `idasignaciondinero`,`asi_fecha`, usu_nombre, `asi_tipo`, `asi_valor`,  `asi_descripcion`, `asi_idautoriza`, `asi_idpromotor` FROM `asignaciondinero` inner join usuarios on asi_idpromotor=idusuarios WHERE idasignaciondinero>0  and `asi_fecha` like '$fechaab%'  and usu_idsede=$id_param and asi_tipo='Gastos' order by `asi_fecha` ";
    //	$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[3] . "</td>
				<td>" . $rw1[4] . "</td>
				<td>" . $rw1[5] . "</td>
				";

        $rw1[4] = str_replace(".", "", $rw1[4]);

        $sumatotal = $rw1[4] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detalleenviados") {

    $FB->titulo_azul1("Fecha", 1, 0, 5);
    $FB->titulo_azul1("Usuario", 1, 0, 0);
    $FB->titulo_azul1("Sede Origen / Destino", 1, 0, 0);
    $FB->titulo_azul1("Transaccion", 1, 0, 0);
    $FB->titulo_azul1("Descripcion", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Valor Aprobado", 1, 0, 0);
    $fechaab = $_REQUEST["ide"];

    $sql = "SELECT `idcajamenor`, `caj_fecharegistro`, `usu_nombre`,`sed_nombre`, `caj_tipotransacion`, `caj_descripcion`, `caj_valor`,`caj_usucom`, 
`caj_cantcom`, `caj_feccom`,caj_idciudaddes,caj_idciudadori  
FROM `cajamenor` inner join usuarios on caj_idusuario=idusuarios 
inner join sedes on idsedes=caj_idciudaddes and `caj_fecharegistro` like '$fechaab%'  and caj_tipotransacion in ('Consignacion','Envio de Dinero Efectivo') WHERE idcajamenor>0 and caj_idciudadori='$id_param'  ORDER BY caj_fecharegistro  ASC ";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        $sql2 = "SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes='$rw1[11]'";
        $DB->Execute($sql2);
        $rw = mysqli_fetch_row($DB->Consulta_ID);

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "
			<td>" . $rw1[1] . "</td>
			<td>" . $rw1[2] . "</td>
			<td>" . $rw[1] . " / " . $rw1[3] . "</td>
			<td>" . $rw1[4] . "</td>
			<td>" . $rw1[5] . "</td>
			<td>" . $rw1[6] . "</td>

			<td>" . $rw1[8] . "</td>

			";

        //	$rw1[6]=str_replace(".","", $rw1[6]);
        $rw1[8] = str_replace(".", "", $rw1[8]);

        $sumatotal = $rw1[8] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detalleprestamosoper") {

    $FB->titulo_azul1("Fecha", 1, 0, 5);
    $FB->titulo_azul1("Operador", 1, 0, 0);
    $FB->titulo_azul1("Tipo", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Descripcion ", 1, 0, 0);
    $fechaab = $_REQUEST["ide"];

    $sql = "SELECT `iddeudapromotor`, `deu_fecha`, usu_nombre,   `deu_tipo` , `deu_valor`, `due_descripcion`, `deu_idautoriza`, `deu_idpromotor` FROM `duedapromotor`  inner join usuarios on deu_idpromotor=idusuarios WHERE iddeudapromotor>0  and `deu_fecha`='$fechaab' and deu_idciudad='$id_param'  and deu_tipo='Prestamos' order by `deu_fecha`";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "
			<td>" . $rw1[1] . "</td>
			<td>" . $rw1[2] . "</td>
			<td>" . $rw1[3] . "</td>
			<td>" . $rw1[4] . "</td>
			<td>" . $rw1[5] . "</td>
			";

        //	$rw1[6]=str_replace(".","", $rw1[6]);
        $rw1[4] = str_replace(".", "", $rw1[4]);

        $sumatotal = $rw1[4] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallegastosper") {

    $FB->titulo_azul1("Fecha", 1, 0, 5);
    $FB->titulo_azul1("Operador", 1, 0, 0);
    $FB->titulo_azul1("Tipo", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Descripcion ", 1, 0, 0);
    $fechaab = $_REQUEST["ide"];

    $sql = "SELECT `iddeudapromotor`, `deu_fecha`, usu_nombre,   `deu_tipo` , `deu_valor`, `due_descripcion`, `deu_idautoriza`, `deu_idpromotor` FROM `duedapromotor`  inner join usuarios on deu_idpromotor=idusuarios WHERE iddeudapromotor>0  and `deu_fecha`='$fechaab' and deu_idciudad='$id_param'  and deu_tipo='Pagos' order by `deu_fecha`";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "
			<td>" . $rw1[1] . "</td>
			<td>" . $rw1[2] . "</td>
			<td>" . $rw1[3] . "</td>
			<td>" . $rw1[4] . "</td>
			<td>" . $rw1[5] . "</td>
			";

        //	$rw1[6]=str_replace(".","", $rw1[6]);
        $rw1[4] = str_replace(".", "", $rw1[4]);

        $sumatotal = $rw1[4] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detallegastossede") {

    $FB->titulo_azul1("Fecha", 1, 0, 5);
    $FB->titulo_azul1("Usuario", 1, 0, 0);
    $FB->titulo_azul1("Sede Origen / Destino", 1, 0, 0);
    $FB->titulo_azul1("Transaccion", 1, 0, 0);
    $FB->titulo_azul1("Descripcion", 1, 0, 0);
    $FB->titulo_azul1("Valor ", 1, 0, 0);
    $FB->titulo_azul1("Valor Aprobado", 1, 0, 0);
    $fechaab = $_REQUEST["ide"];

    $sql = "SELECT `idcajamenor`, `caj_fecharegistro`, `usu_nombre`,`sed_nombre`, `caj_tipotransacion`, `caj_descripcion`, `caj_valor`,`caj_usucom`, 
`caj_cantcom`, `caj_feccom`,caj_idciudaddes,caj_idciudadori  
FROM `cajamenor` inner join usuarios on caj_idusuario=idusuarios 
inner join sedes on idsedes=caj_idciudaddes and `caj_feccom` like '$fechaab%'  and caj_tipotransacion in ('Gastos') WHERE idcajamenor>0 and caj_idciudadori='$id_param'  ORDER BY caj_fecharegistro  ASC ";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        $sql2 = "SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes='$rw1[11]'";
        $DB->Execute($sql2);
        $rw = mysqli_fetch_row($DB->Consulta_ID);

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "
			<td>" . $rw1[1] . "</td>
			<td>" . $rw1[2] . "</td>
			<td>" . $rw[1] . " / " . $rw1[3] . "</td>
			<td>" . $rw1[4] . "</td>
			<td>" . $rw1[5] . "</td>
			<td>" . $rw1[6] . "</td>

			<td>" . $rw1[8] . "</td>

			";

        //	$rw1[6]=str_replace(".","", $rw1[6]);
        $rw1[8] = str_replace(".", "", $rw1[8]);

        $sumatotal = $rw1[8] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "detalleremesas") {

    $FB->titulo_azul1("ID", 1, 0, 5);
    $FB->titulo_azul1("Fecha", 1, 0, 0);
    $FB->titulo_azul1("Sede Origen", 1, 0, 0);
    $FB->titulo_azul1("Sede Destino", 1, 0, 0);
    $FB->titulo_azul1("Pago en?", 1, 0, 0);
    $FB->titulo_azul1("Operario Remesa / Recoge ", 1, 0, 0);
    $FB->titulo_azul1("Valor Aprobado", 1, 0, 0);

    $fechaab = $_REQUEST["ide"];
    //	$sql="SELECT `idasignaciondinero`,`asi_fecha`, usu_nombre, `asi_tipo`, `asi_valor`,  `asi_descripcion`, `asi_idautoriza`, `asi_idpromotor` FROM `asignaciondinero` inner join usuarios on asi_idpromotor=idusuarios WHERE idasignaciondinero>0  and `asi_fecha` like '$fechaab%'  and usu_idsede=$id_param and asi_tipo='Gastos' order by `asi_fecha` ";
    $sql = "SELECT idgastos,`gas_fecharegistro`,gas_feccom ,`gas_fecrecogida`,`gas_idciudadori`,`sed_nombre`,`gas_bus`,`gas_pagar`,`gas_nomremesa`,gas_iduserrecoge,gas_cantcom FROM `gastos` inner join usuarios on gas_idusuario=idusuarios and gas_cantcom>0 inner join sedes on idsedes=gas_idciudaddes
		WHERE idgastos>0  and (gas_idciudadori='$id_param' and gas_pagar='Sede Origen' and gas_feccom like '$fechaab%')  or (gas_idciudaddes='$id_param' and gas_pagar='Sede Destino' and  gas_fechavalida like '$fechaab%' and gas_nomvalida!='' )  ORDER BY idgastos";

    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>";
        if ($rw1[7] == 'Sede Origen') {
            echo "<td>" . $rw1[2] . "</td>";
        } elseif ($rw1[7] == 'Sede Destino') {
            echo "<td>" . $rw1[3] . "</td>";
        }
        $sql2 = "SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes='$rw1[4]'";
        $DB->Execute($sql2);
        $rw = mysqli_fetch_row($DB->Consulta_ID);

        $sql5 = "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE `idusuarios`='$rw1[9]' ";
        $DB->Execute($sql5);
        $nombreuser = $DB->recogedato(1);

        echo "	<td>" . $rw[1] . "</td>
				<td>" . $rw1[5] . "</td>
				<td>" . $rw1[7] . "</td>
				<td>" . $rw1[8] . " /
				" . $nombreuser . "</td>
				<td>" . $rw1[10] . "</td>
				";

        $rw1[10] = str_replace(".", "", $rw1[10]);

        $sumatotal = $rw1[10] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "facturarcreditos") {
    $param4 = 'ingresado';
    $fecha = $_REQUEST["ide"];

    /* 	$slq="SELECT `pre_obsevaciones`,`pre_correctiva`,`pre_responsable` FROM `pre-operacional` where preidusuario='$iduser' and prefechaingreso like '$fecha%'";	
      $DB->Execute($slq);
      $rw1=mysqli_fetch_row($DB->Consulta_ID); */

    include_once("detalle_crearfacturacreditos.php");
} else if ($tabla == "preoperacional") {
    $param4 = 'ingresado';
    $fecha = $_REQUEST["ide"];

    $slq = "SELECT `pre_obsevaciones`,`pre_correctiva`,`pre_responsable` FROM `pre-operacional` where preidusuario='$iduser' and prefechaingreso like '$fecha%'";
    $DB->Execute($slq);
    $rw1 = mysqli_fetch_row($DB->Consulta_ID);
    include_once("preoperacional.php");
} else if ($tabla == "tipocontrato") {
    $contrato = $_REQUEST["ide"];
    $FB->llena_texto("Tipo de Contrato:", 22, 82, $DB, $tipocontrato, "", "$contrato", 2, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "zonatrabajo") {
    $fecha = $_REQUEST["ide"];
    $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT `idzonatrabajo`,`zon_nombre` FROM zonatrabajo where idzonatrabajo>0 )", "", "", 2, 1);

    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
} else if ($tabla == "horaoficina") {
    $hora = date("H:i:s");
    $FB->llena_texto("Retorno de Oficina:", 3, 102, $DB, "", "", "$hora", 2, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "horaretorno") {
    $hora = date("H:i:s");
    $FB->llena_texto("Retorno de Almuerzo:", 3, 102, $DB, "", "", "$hora", 2, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "horaalmuerzo") {
    $hora = date("H:i:s");
    $FB->llena_texto("Hora de Almuerzo:", 3, 102, $DB, "", "", "$hora", 2, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
}else if ($tabla == "ingresousuario") {
    $fecha = $_REQUEST["ide"];
    $id_sedes = isset($_REQUEST["idsede"]) ? $_REQUEST["idsede"] : "";

    if ($nivel_acceso != '1' and $nivel_acceso != '12') {
        $cond = "AND idsedes = $id_sedes";
    } else {
        $cond = "";
    }

    $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT idsedes, sed_nombre FROM sedes WHERE idsedes > 0 $cond)", "", "$id_sedes", 2, 1);
    $FB->llena_texto("Motivo Ingreso:", 4, 82, $DB, $motivoingreso, "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Zona:", 6, 2, $DB, "(SELECT idzonatrabajo, zon_nombre FROM zonatrabajo WHERE inner_sedes = '$id_sedes')", "", "", 2, 1);
    $FB->llena_texto("Prueba de Alcohol:", 7, 82, $DB, $pruebaalcohol, "", "", 2, 1);
    $FB->llena_texto("Imagen", 8, 6, $DB, "", "", "", 1, 0);

    // ocultos o auxiliares
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
}


else if ($tabla == "validartarea") {
    $id = $_REQUEST["ide"];
    $FB->llena_texto("Estado:", 4, 82, $DB, $estadotarea, "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 5, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param3", 1, 13, $DB, "", "", $id, 5, 0);
} 
else if ($tabla == "revisionpreventiva") {

    $id_user = $_REQUEST["ide"];

    $FB->titulo_azul1("Fecha de revision", 1, 0, 7);
    $FB->titulo_azul1("Vehiculo", 1, 0, 0);
    $FB->titulo_azul1("Usuario Registro", 1, 0, 0);
    $FB->titulo_azul1("Fecha Registro", 1, 0, 0);
    $FB->titulo_azul1("Foto", 1, 0, 0);

    $sql = "SELECT `idrevisionvehiculo`,`rev_fecha`,`rev_idvehiculo`,`rev_usuregistra`,`rev_usuvehiculo`, `rev_ingreso`, `rev_foto`FROM `revisionvehiculo` WHERE  rev_identifiuser like'%$id_user%'";

    $DB->Execute($sql);
    $va = 0;

    while ($rw2 = mysqli_fetch_row($DB->Consulta_ID)) {
        $id_p = $rw2[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }
        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw2[1] . "</td>
				<td>" . $rw2[2] . "</td>
				<td>" . $rw2[3] . "</td>		
				<td>" . $rw2[5] . "</td>
				<td><a href='imagenrevision/" . $rw2[6] . "' target='_blank'>ver</a></td>";

        //echo $LT->llenadocs3($DB1, "entregavehiculo",$id_p, 1, 35, 'Ver');
    }



    $FB->titulo_azul1(" ------ ", 1, 0, 10);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
    $FB->titulo_azul1(" ------", 1, 0, 0);
} else if ($tabla == "Abonos") {

    $idservicio = $_REQUEST["ide"];
    $FB->llena_texto("Valor:", 1, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("param5", 1, 13, $DB, "", "", "$idservicio", 5, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "Camara comercio") {

    $idservicio = $_REQUEST["ide"];
    // $FB->llena_texto("Valor:", 1, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Imagen", 3, 6, $DB, "", "", "cotizaciones/descargables/CamaraComercio.pdf", 1, 0);

    // $FB->llena_texto("param5", 1, 13, $DB, "", "", "$idservicio", 5, 0);
    // $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
}else if ($tabla == "Rut") {

    $idservicio = $_REQUEST["ide"];
    // $FB->llena_texto("Valor:", 1, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Imagen", 3, 6, $DB, "", "", "cotizaciones/descargables/Rut.pdf", 1, 0);

    // $FB->llena_texto("param5", 1, 13, $DB, "", "", "$idservicio", 5, 0);
    // $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "Certificacion bancaria") {

    $idservicio = $_REQUEST["ide"];
    // $FB->llena_texto("Valor:", 1, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Imagen", 3, 6, $DB, "", "", "cotizaciones/descargables/CertificacionBancaria.pdf", 1, 0);

    // $FB->llena_texto("param5", 1, 13, $DB, "", "", "$idservicio", 5, 0);
    // $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
}else if ($tabla == "Abono_A_Deuda") {



    $idsedeUsu=$_REQUEST["ide"];
    
    $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 )", "", "$idsedeUsu", 2, 1);
    $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1)", "", "$id_param", 2, 1);
    $FB->llena_texto("Fecha de Ingreso:", 3, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Tipo de transaccion:", 4, 82, $DB, $deudaoper, "", "Pagos", 2, 1);
    $FB->llena_texto("Valor:", 5, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Descontar de:", 8, 82, $DB, $DescontarDe, "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 6, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Imagen", 7, 6, $DB, "", "", "", 1, 0);
    
    
    
    
    
    
}else if ($tabla == "Ajustes_nomina") {



    $fechaini=$_REQUEST["ide"];
    
    // $FB->llena_texto("Sede:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 )", "", "$idsedeUsu", 2, 1);
    // $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1)", "", "$id_param", 2, 1);
    // $FB->llena_texto("Fecha de Ingreso:", 3, 10, $DB, "", "", "$fechaactual", 2, 0);
    $FB->llena_texto("Tipo de transaccion:", 4, 82, $DB, $tipoAjustes, "", "Pagos", 2, 1);
    $FB->llena_texto("Valor:", 5, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Descontar de:", 8, 82, $DB, $DescontarDe, "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 6, 1, $DB, "", "", "", 1, 0);
    // $FB->llena_texto("Imagen", 7, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param7", 1, 13, $DB, "", "", $fechaini, 5, 0);
    
    
    
    
}else if ($tabla == "TipoPago") {

    //$idservicio=$_REQUEST["ide"];
    $factu = "Select fac_descripcion from facturascreditos where idfacturascreditos='$id_param'";
    $DB1->Execute($factu);
    $factudes = $DB1->recogedato(0);

    $FB->llena_texto("Tipo de Pago:", 4, 82, $DB, $tipopagos, "", "", 2, 1);
    $FB->llena_texto("Fecha de Pago:", 5, 10, $DB, "", "", "$fechaactual", 1, 0);
    $FB->llena_texto("Descripcion:", 6, 9, $DB, "", "", "$factudes", 1, 1);
    $FB->llena_texto("Imagen Pago", 3, 6, $DB, "", "", "", 1, 0);
//	$FB->llena_texto("param5", 1, 13, $DB, "", "", "$idservicio", 5, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);


    $FB->titulo_azul1("Guia",1,0,7); 
    $FB->titulo_azul1("Pagada a ",1,0,0); 
    $FB->titulo_azul1("Fecha de pago",1,0,0); 
    $FB->titulo_azul1("Total Pago",1,0,0);
    $FB->titulo_azul1("Medio de pago",1,0,0); 
     

    // $FB->titulo_azul1("Prueba Alcohol",1,0,0);
$sql="SELECT `idfacturascreditos`, `fac_fechafactura`,`fac_credito`, `fac_numerofactura`, `fac_fechaprefac`,`fac_idservicios`, `fac_iduserpre`,`fac_numeroref`, `fac_fechafacturado`, `fac_fechavencimiento`, `fac_estado`,`fac_tipopago`,`fac_iduserfac`,fac_precio,`fac_fecharadicado`,fac_fechapago,fac_notacredito,fac_fecharafacturado,fac_pagoconfir,fac_userconfirmo,fac_fechacomfir,fac_valorpendiente,fac_preciofinal,fac_correoven, fac_nit FROM `facturascreditos` WHERE   idfacturascreditos='$id_param'  ";
$html="";
$DB->Execute($sql); $va=0; 
$guias=0;
while($rw1=mysqli_fetch_row($DB->Consulta_ID))
{


        
    $servi="SELECT s.idservicios, s.ser_clasificacion, g.gui_userecomienda, g.gui_fechaentrega, g.gui_recogio, g.gui_fecharecogio, s.ser_consecutivo,s.ser_valorseguro,s.ser_valor
    FROM servicios s
    LEFT JOIN guias g ON s.idservicios = g.gui_idservicio
    WHERE s.idservicios IN ($rw1[5])";
    $DB1->Execute($servi);




    
    // echo$rw1[5];
        while($sevi=mysqli_fetch_row($DB1->Consulta_ID))
        {
            // echo$sevi[0].",";
            // $guias=$guias.$sevi[0].",";
            echo"<tr>";
            
            $idservi=$sevi[0];

                    if ($sevi[1]==1) {
                            if ($sevi[4]!="") {
                                echo"<td >$sevi[6]</td>";
                                echo"<td>$sevi[4]</td>";
                                echo"<td>$sevi[5]</td>";
                                $sevi[7]=($sevi[7]*1)/100;

                                $total=$sevi[7]+$sevi[8];
                                $total= number_format($total, 0, ".", ".");
                                echo"<td>$total</td>";


                            $pagas=$pagas+1;
                        }
                        
                    }else if($sevi[1]==3){
                        if ($sevi[2]!="") {

                            echo"<td>$sevi[6]</td>";
                            echo"<td>$sevi[2]</td>";
                            echo"<td>$sevi[3]</td>";
                                                            
                                $sevi[7]=($sevi[7]*1)/100;

                                $total=$sevi[7]+$sevi[8];
                                $total= number_format($total, 0, ".", ".");
                                echo"<td>$total</td>";
                        $pagas=$pagas+1;
                            


                        }	

                    }else{

                        $nopagas=$nopagas+1;
                        echo"<td style='background-color: red;'>$sevi[6]</td>";
                        echo"<td style='background-color: red;'>$sevi[2]</td>";
                        echo"<td style='background-color: red;'>$sevi[3]</td>";
                    }

                    $Pagada = "SELECT `idcuentaspromotor`,`cue_idoperador`, `cue_pordeclarado`, `cue_vrdeclarado`, `cue_valorflete`, `cue_abono`, `cue_tipopago`, `cue_fecha`, `cue_pendientecobrar`,cue_idoperentrega,cue_transferencia,ser_pendientecobrar,ser_numerofactura,cue_idservicio FROM `cuentaspromotor` inner join servicios on cue_idservicio=idservicios WHERE  cue_numeroguia='$sevi[6]' order by ser_guiare ASC;";
                    $DB->Execute($Pagada);
                    while ($pagada1 = mysqli_fetch_row($DB->Consulta_ID)) {
                        $idcobro= $pagada1[9];
                        $transfe= $pagada1[10];
                
                
                        if($pagada1[10]!='' and $pagada1[10]!='Efectivo' and $pagada1[11]!=6){
                
                           $sqlv = "Select pag_userverifica from pagoscuentas where pag_idservicio='$pagada1[13]'";
                           $DB->Execute($sqlv);
                           $verificado = $DB->recogedato(0);
                           if($verificado ==''){
                               $Pagotrans=$pagada1[10]."<br>"."Sin Verificar";
                               $color7="style=background-color:yellow";
                           }else{
                           $Pagotrans=$pagada1[10]."<br>"."Verificado";
                   
                           }

                       }elseif($pagada1[11]==6){
                   

                   
                           $Pagotrans="Pago Pendiente por cobrar";
                        //    ."<br> #".$pagada1[12];
                   
                       }else{
                   
                           $Pagotrans='Efectivo';
                   
                   
                   
                       }
                    }
                    echo"<td>$Pagotrans</td>";
                
                echo"</tr>";
            }
            // echo$guias;
}

print_r($pagadas);

} else if ($tabla == "devolucion") {

    $idservicio = $_REQUEST["ide"];
    $FB->llena_texto("Valor:", 1, 118, $DB, "", "", "$id_param", 2, 1);
    $FB->llena_texto("param5", 1, 13, $DB, "", "", "$idservicio", 5, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "cambiarfactura") {

    $valor = $_REQUEST["ide"];
    $FB->llena_texto("# Factura:", 1, 1, $DB, "", "", "$valor", 2, 1);
    $FB->llena_texto("param5", 1, 13, $DB, "", "", "$valor", 5, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "fecharadicado") {
    $FB->llena_texto("Fecha de Pago:", 1, 10, $DB, "", "", "$fechaactual", 1, 0);
    $FB->llena_texto("Imagen", 3, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
}else if ($tabla == "pagoconfirmado") {

    //$idservicio=$_REQUEST["ide"];
    $FB->llena_texto("confirmar pago $:", 1, 1, $DB, "", "", "", 2, 1);

    $FB->llena_texto("param9", 1, 13, $DB, "", "", $id_param, 5, 0);  

}else if ($tabla == "verpagoconfirmado") {

 $datospago = $_REQUEST["pago"];
 $pago = json_decode(urldecode($datospago), true);

   // Acceder a los campos del JSON
   $userconfir = $pago['usuario'];
   $fechaPago = $pago['fecha'];
   $valorconfirmado = $pago['valor'];
   $excedente = $pago['excedente'];

  echo "<p>Detalles del Pago:</p>
    <ul>
      <li>Fecha de confirmacion del Pago: $fechaPago</li>
      <li>Valor Confirmado: $valorconfirmado</li>
      <li>Exedente: $excedente</li>
      <li>Confirmado por: $userconfir</li>
    </ul>";

  
    
}else if ($tabla == "verpagoconfirmadogerente") {

    $datospago = $_REQUEST["pago"];
    $pago = json_decode(urldecode($datospago), true);
   
      // Acceder a los campos del JSON
      $userconfir = $pago['usuario'];
      $fechaPago = $pago['fecha'];
      $valorconfirmado = $pago['valor'];
      $excedente = $pago['excedente'];
   
     echo "<p>Detalles del Pago:</p>
        <ul>
         <li>Fecha de confirmacion del Pago: $fechaPago</li>
         <li>Valor Confirmado: $valorconfirmado</li>
         <li>Exedente: $excedente</li>
         <li>Confirmado por: $userconfir</li>
        </ul>";

       $FB->llena_texto("confirmar pago $:", 1, 1, $DB, "", "", "$valorconfirmado", 2, 1);
       $FB->llena_texto("param9", 1, 13, $DB, "", "", $id_param, 5, 0);  

       $tabla ="pagoconfirmado";
       
}else if ($tabla == "subirFactura") {

    $FB->llena_texto("Fecha de Aprobacion:", 1, 10, $DB, "", "", "$fechaactual", 1, 0);
    $FB->llena_texto("Valor Final :", 4, 1, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Imagen", 3, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} else if ($tabla == "editarfecha") {

    $FB->llena_texto("Fecha de Aprobacion:", 1, 10, $DB, "", "", "$ide", 1, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
} elseif ($tabla == "pruebaalcohol") {

    $fecha = $_REQUEST["ide"];
    $FB->llena_texto("Prueba de Alcohol:", 1, 82, $DB, $pruebaalcohol, "", "", 2, 1);
    $FB->llena_texto("Imagen", 2, 6, $DB, "", "", "", 1, 0);

    $opcion = explode(' ', $id_param);
    if ($opcion[0] == 'update') {
        $metodo = 'update';
    } else {
        $metodo = 'insert';
    }

    $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
    $FB->llena_texto("param4", 1, 13, $DB, "", "", $opcion[1], 5, 0);
    $FB->llena_texto("param5", 1, 13, $DB, "", "", $metodo, 5, 0);
} elseif ($tabla == "RegistrarPago") {
    $id_param2 = $_REQUEST["ide"];
    $FB->llena_texto("Fecha de Pago:", 1, 10, $DB, "", "", "$fechaactual", 1, 0);
    $FB->llena_texto("Valor Pagado:", 2, 118, $DB, "", "", "", 1, 1);
    $FB->llena_texto("Documento:", 112, 6, $DB, "", "", "", 4, 0);

    $FB->llena_texto("idhojadevida", 1, 13, $DB, "", "", $id_param2, 5, 0);
    $FB->llena_texto("idincapacidades", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("condecion", 1, 13, $DB, "", "", "RegistrarPago", 5, 0);
} elseif ($tabla == "reportealarmas") {

    $sql2 = "SELECT  `rep_fechavencimiento`,rep_link_pago FROM `reportealertas`  WHERE idreportealertas='$id_param' ";
    $DB->Execute($sql2);
    $rw = mysqli_fetch_row($DB->Consulta_ID);

    $id_param2 = $_REQUEST["ide"];
    $FB->llena_texto("Fecha de Vencimiento:", 1, 10, $DB, "", "", "$id_param2", 1, 0);
    $FB->llena_texto("Documento:", 5, 6, $DB, "", "", "", 4, 0);
    $FB->llena_texto("Link de Pago:", 6, 1, $DB, "", "", "$rw[1]", 1, 0);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
}elseif ($tabla == "Resumen_Quincena") {


    $id_param;
    $_REQUEST["ide"];

    $cadena = $_REQUEST["ide"];
    $partes = explode("/", $cadena);

    $FechaInc = $partes[0]; // texto1
    $FechaFn = $partes[1]; // texto2


    $FB->titulo_azul1("Operador",1,0,7); 
    $FB->titulo_azul1("Preoperacional",1,0,0); 
    $FB->titulo_azul1("Validacion",1,0,0); 
    $FB->titulo_azul1("Prueba Alcohol",1,0,0); 
    $FB->titulo_azul1("Imagen",1,0,0); 
    $FB->titulo_azul1("Ingreso?",1,0,0); 
    $FB->titulo_azul1("Descripcion",1,0,0); 
    $FB->titulo_azul1("Fecha Ingreso",1,0,0); 
    $FB->titulo_azul1("Zona Trabajo",1,'5%',0); 
    $FB->titulo_azul1("Hora Almuerzo",1,'5%',0); 
    $FB->titulo_azul1("Retorno Almuerzo",1,'5%',0); 
    $FB->titulo_azul1("Retorno Oficina",1,'5%',0); 
    $FB->titulo_azul1("Hora Salida",1,'5%',0); 
    $FB->titulo_azul1("TEM ENTRADA",1,'5%',0); 
    $FB->titulo_azul1("TEM SALIDA",1,'5%',0); 
    $FB->titulo_azul1("Tipo Contrato",1,'5%',0); 
    $FB->titulo_azul1("PLACA",1,'5%',0); 
    $FB->titulo_azul1("Fecha Seguro",1,'5%',0); 
    $FB->titulo_azul1("Fecha Tecnomecánica",1,'5%',0); 
    $FB->titulo_azul1("Fecha licencia de conduccion",1,'5%',0); 
    $FB->titulo_azul1("Cambio de aceite",1,'5%',0); 

	// $sql4="SELECT usu_tipocontrato FROM `usuarios` WHERE idusuarios >0 $conde";
	// $DB2->Execute($sql4);
	// $rw4=mysqli_fetch_row($DB2->Consulta_ID);
	// $tipoContrato ="$rw4[0]"; 

	// if ($tipoContrato == $param37 or $porempresa == false) {
		





		// $fechaini = $param34;
		// $fechafin = $param36;
		// $fechaCompleta=date("$param6 H:i:s");	
		
        // $sql4="SELECT usu_tipocontrato FROM `usuarios` WHERE idusuarios >0 $conde";
        // $DB2->Execute($sql4);
        // $rw4=mysqli_fetch_row($DB2->Consulta_ID);
        // $tipoContrato ="$rw4[0]"; 
    
        // if ($tipoContrato == $param37 or $porempresa == false) {
            
        //     $fechaini = $param34;
        //     $fechafin = $param36;
        //     $fechaCompleta=date("$param6 H:i:s");	
            
        
        
        
            
            
		// Establecer la fecha inicial y final
		$fechaInicial = new DateTime($FechaInc);
		$fechaFinal = new DateTime($FechaFn);
            
            while ($fechaInicial <= $fechaFinal) {
    
                $va++;
                // Obtener la fecha actual con hora
                $fechaConHora = $fechaInicial->format('Y-m-d H:i:s');
                $fechaSola=$fechaInicial->format('Y-m-d');
                // Preparar la consulta SQL e insertar en la base de datos
               
                // Incrementar la fecha para la próxima iteración
        $sql="SELECT idusuarios,usu_nombre,preestado,prefechaingreso,idpreoperacinal,usu_tipocontrato,prevehiculo,usu_fechalicencia FROM `pre-operacional` inner join usuarios on idusuarios=preidusuario  where  (usu_ver_nomina=1)  and prefechaingreso like '%$fechaSola%'   and `idusuarios`= '$id_param' and roles_idroles!='6' ORDER BY prefechaingreso  asc ";
        
            $DB1->Execute($sql); 
            
            $totalasignadas=0;
            // $color1='';
            // $color2='';
            
            $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                 
            if (empty($rw1)) {

    
                
                        $sql3="SELECT idusuarios,usu_nombre,usu_idsede FROM usuarios WHERE idusuarios >0 `idusuarios`= '$id_param'  ORDER BY usu_nombre  asc ";
                        $DB1->Execute($sql3); 
                        $rw3=mysqli_fetch_row($DB1->Consulta_ID);
                         
        
                        $color="#922B21";
                        $colorletra='style="color: #FFFFFF;"';
                        $fechaHora= "";
                        echo "<tr class='text' bgcolor='$color' $colorletra onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                        echo "<td>".$rw3[1]."</td>";
                        echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td colspan='1' width='0' align='center' >
                                    <a id='link' onclick='pop_dis16($rw3[0], \"SeguimientoUser\", \"$rw3[2]\", document.getElementById(\"param35\").value)' title='Ingreso de Usuario'>Sin ngreso</a>
                                    </td>";
                                    echo "<td></td>";
                                    echo "<td>$fechaConHora</td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                                    echo "<td></td>";
                        
            } else {
                       
                        $id_p=$rw1[0];
                        //echo $rw1[3];
                         $fechabusqueda=substr("$rw1[3]",0,10);
                         $imprimir=1;
                         $sql1="SELECT seg_alcohol,`seg_fechaingreso`, `seg_horaalmuerzo`, `seg_horaregreso`, `seg_idzona`,seg_motivo,seg_descr,seg_fechafinalizo,idseguimiento_user,seg_horaoficina from seguimiento_user where seg_fechaalcohol like '$fechabusqueda%'  and seg_idusuario='$id_p' $conde1 order by seg_fechaingreso asc";
                        $DB->Execute($sql1); 
                        $rw2=mysqli_fetch_row($DB->Consulta_ID);
                         $compara=$rw2[5];
                
                        // if($param32!=$compara && $param32!='0' && $param32!=null){  $imprimir=0;}
                        
                        
                
                        if($imprimir==1){
                            
                            // echo"Motivo".$rw1[2];
                                
                                
                                if($rw2[5]=="Vacaciones"){ 
    
                                    if ($motivoingresoo == false ) {
                                        if($param32="Vacaciones"){
                                            $color="#FFC300";
                                            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                                            echo "<td>".$rw1[1]."</td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($rw2[8],\"ingresousuario\",\"$rw1[3]\")';  title='Ingreso de Usuario' >$rw2[5]</td>"; //Ingreso?
                                            echo "<td></td>";
                                            echo "<td colspan='1' width='0' align='center' >$rw2[1]</td>";
                                            echo "<td>V</td>";
                                            echo "<td>A</td>";
                                            echo "<td>C</td>";
                                            echo "<td>A</td>";
                                            echo "<td>C</td>";
                                            echo "<td>I</td>";
                                            echo "<td>O</td>";
                                            echo "<td>N</td>";
                                            echo "<td>E</td>";
                                            echo "<td>S</td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                        
                        
                                        }else{
    
    
                                        }
                        
                                    }else{
                        
                                                    $color="#FFC300";
                                                    echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                                                    echo "<td>".$rw1[1]."</td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($rw2[8],\"ingresousuario\",\"$rw1[3]\")';  title='Ingreso de Usuario' >$rw2[5]</td>"; //Ingreso
                                                    echo "<td></td>";
                                                    echo "<td colspan='1' width='0' align='center' >$rw2[1]</td>";
                                                    echo "<td>V</td>";
                                                    echo "<td>A</td>";
                                                    echo "<td>C</td>";
                                                    echo "<td>A</td>";
                                                    echo "<td>C</td>";
                                                    echo "<td>I</td>";
                                                    echo "<td>O</td>";
                                                    echo "<td>N</td>";
                                                    echo "<td>E</td>";
                                                    echo "<td>S</td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                                    echo "<td></td>";
                                    }
                                    
                                }
                                else{
        
        
                                    
                                    if($rw2[5]=="descanso"){
                                        if ($motivoingresoo == false ) {
                                            if($param32="Vacaciones"){
                                            $color="#82E0AA";
                                            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                                            echo "<td>".$rw1[1]."</td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
    
                                            echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($rw2[8],\"ingresousuario\",\"$rw1[3]\")';  title='Ingreso de Usuario' >$rw2[5]</td>"; //Ingreso?
                                            echo "<td></td>";
                                            echo "<td colspan='1' width='0' align='center' >$rw1[3]</td>";
                                            echo "<td>D</td>";
                                            echo "<td>E</td>";
                                            echo "<td>S</td>";
                                            echo "<td>C</td>";
                                            echo "<td>A</td>";
                                            echo "<td>N</td>";
                                            echo "<td>S</td>";
                                            echo "<td>O</td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            
                                            
                                            
                                            }
                                        }else{
    
                                            $color="#82E0AA";
                                            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                                            echo "<td>".$rw1[1]."</td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($rw2[8],\"ingresousuario\",\"$rw1[3]\")';  title='Ingreso de Usuario' >$rw2[5]</td>"; //Ingreso?
                                            echo "<td></td>";
                                            echo "<td colspan='1' width='0' align='center' >$rw1[3]</td>";
                                            echo "<td>D</td>";
                                            echo "<td>E</td>";
                                            echo "<td>S</td>";
                                            echo "<td>C</td>";
                                            echo "<td>A</td>";
                                            echo "<td>N</td>";
                                            echo "<td>S</td>";
                                            echo "<td>O</td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
                                            echo "<td></td>";
    
    
                                        }
                                    }else{
    
    
                                // if ($motivoingresoo == false ) {
                                    
                                // }else{
                                     $p=$va%2;
                                    if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
                                    if($rw1[2]=='Validado' or $rw1[2]=='Validado Covid19'){}else{ $color="#ff5f42"; }
                                    echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                                    echo "<td>".$rw1[1]."</td>";
                                    $fecha=substr($rw1[3], 0, -8);
                                    if($rw2[0]=='' or $rw2[0]==null){ 
                                        $rw2[0]='Faltante'; 
                                        $ver="<td colspan='1' width='0' align='center' ></td>";
                                    }
                                    else{
                                        if($rw2[5]=='' or $rw2[5]==null){ $rw2[5]='Sin Ingresar'; 
                                            
                                        }
                                        else{
                                        if($rw2[2]=='' or $rw2[2]==null){ $rw2[2]='Sin Ingresar'; }
                                        if($rw2[3]=='' or $rw2[3]==null){ $rw2[3]='Sin Ingresar'; }
                                        
                                        }
                                        $ver=$LT->llenadocs3($DB, "seguimiento_user", $rw2[8], 1, 35, 'Ver');
                                    }
                                    $idingresouser=$rw2[8];
                                    if($rw2[8]==''){
                                        
                                        $rw2[8]='insert '.$id_p;
                                    }else{
                                        $rw2[8]='update '.$rw2[8];
                                        
                                    }
                                    if($rw1[2]!='No aplica'){
                                            echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='window.open(\"validaoperacional.php?iduser=$id_p&fecha=$fecha&idvehiculo=$rw1[6]&campo=preencuesta\",\"_self\")';  title='Pre operacional' >$rw1[2]</td>";
                        
                                    }else {
                                        echo	"<td colspan='1' width='0' align='center' >$rw1[2]</td>";
                                    }
                                    if($rw1[2]=='Validado' or $rw1[2]=='Validado Covid19'){
                                        echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='window.open(\"validaoperacional.php?iduser=$id_p&fecha=$fecha&idvehiculo=$rw1[6]&campo=predatosvalidados\")';  title='Pre operacional' >$rw1[2]</td>";
                        
                                    }else {
                                        echo	"<td colspan='1' width='0' align='center' >Sin Validar</td>";
                                    }
                        
                                    echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16(\"$rw2[8]\",\"pruebaalcohol\",\"y($rw1[3]\")';  title='Prueba de alcohol' >$rw2[0]   </td>";
                                    echo $ver ;
                                    echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($idingresouser,\"ingresousuario\",\"$rw1[3]\")';  title='Ingreso de Usuario' >$rw2[5]</td>";
                                    echo "<td colspan='1' width='0' align='center' >$rw2[6]</td>";
                                    echo "<td colspan='1' width='0' align='center' >$rw2[1]</td>";
                        
                        
                                    $sql2="SELECT `idzonatrabajo`,`zon_nombre` FROM `zonatrabajo` WHERE `idzonatrabajo`='$rw2[4]'";
                                    $DB2->Execute($sql2);
                                    $rw3=mysqli_fetch_row($DB2->Consulta_ID);
                                    $zona ="$rw3[1]"; 
                                    if($zona=='' or $zona==null){ $zona='Faltante'; }
                                    echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($idingresouser,\"zonatrabajo\",\"$rw1[3]\")';  title='Zona' >$zona</td>";
                                    echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($idingresouser,\"horaalmuerzo\",\"$rw1[3]\")';  title='Hora almuerzo' >$rw2[2]</td>";
                                    echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($idingresouser,\"horaretorno\",\"$rw1[3]\")';  title='Retorno almuerzo' >$rw2[3]</td>";
                                    echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($idingresouser,\"horaoficina\",\"$rw1[3]\")';  title='Retorno Oficina' >$rw2[9]</td>";
                                    echo "<td colspan='1' width='0' align='center'  title='Hora Salida' >$rw2[7]</td>";
                                
                                    echo $LT->llenadocs3($DB, "pre-operacional",$rw1[4], 1, 35, 'Ver');
                                    echo $LT->llenadocs3($DB, "pre-operacional", $rw1[4], 2, 35, 'Ver');
                        
                                    echo "<td colspan='1' width='0' align='center'  title='Contrato' >$rw1[5]</td>";
                                    $color1='';
                                    $color2='';
                        
                                        $fechaInicialC = 0;
                                        $fechaFinalC = 0;
                                        // Las convertimos a segundos
                                        $fechaInicialSegundos = 0;
                                        $fechaFinalSegundos = 0;
                                        // Hacemos las operaciones para calcular los dias entre las dos fechas y mostramos el resultado
                                        $dias = 0;
                                        $diasparasoat=0;
                        
                        
                                        $fechaInicial11 = 0;
                                        $fechaFinal1 = 0;
                                        // Las convertimos a segundos
                                        $fechaInicialSegundos1 = 0;
                                        $fechaFinalSegundos1 = 0;
                                        // Hacemos las operaciones para calcular los dias entre las dos fechas y mostramos el resultado
                                        $dias1 =0;
                                        $diasparatecno=0;
                        
                        
                                    if($rw1[6]!=0){
                        
                                            $sql3="SELECT `idvehiculos`,  `veh_fechaseguro`, `veh_fechategnomecanica`, `veh_fechamantenimiento`,veh_placa,veh_kilactual,veh_aceitekil,veh_kmalcambaceite FROM `vehiculos` WHERE idvehiculos='$rw1[6]'";
                                            $DB2->Execute($sql3);
                                            $rw4=mysqli_fetch_row($DB2->Consulta_ID);
                                            $kilactual=$rw4[5];
                                            $kilparacamaceite=$rw4[6];
                                            $kmalcambaceite=$rw4[7];
                        
                        
                                        $fechaActual = date('Y-m-d');
                        
                                            $fechas=strtotime(date("d-m-Y",strtotime($rw4[1]. "- 3 days")));
                                            $fechat=strtotime(date("d-m-Y",strtotime($rw4[2]. "- 3 days")));
                                            $fehcacomparar=strtotime(date($fechaActual));
                        
                                                // Declaramos nuestras fechas inicial y final
                                                $fechaInicialC = date($fechaActual);
                                                $fechaFinalC = date($rw4[1]);
                                                // Las convertimos a segundos
                                                $fechaInicialSegundos = strtotime($fechaInicial);
                                                $fechaFinalSegundos = strtotime($fechaFinal);
                                                // Hacemos las operaciones para calcular los dias entre las dos fechas y mostramos el resultado
                                                $dias = ($fechaFinalSegundos - $fechaInicialSegundos) / 86400;
                                                $diasparasoat=round($dias, 0, PHP_ROUND_HALF_UP);
                        
                        
                                                $fechaInicial1 = date($fechaActual);
                                                $fechaFinal1 = date($rw4[2]);
                                                // Las convertimos a segundos
                                                $fechaInicialSegundos1 = strtotime($fechaInicial1);
                                                $fechaFinalSegundos1 = strtotime($fechaFinal1);
                                                // Hacemos las operaciones para calcular los dias entre las dos fechas y mostramos el resultado
                                                $dias1 = ($fechaFinalSegundos1 - $fechaInicialSegundos1) / 86400;
                                                $diasparatecno=round($dias1, 0, PHP_ROUND_HALF_UP);
                        
                                            //   if ($rw4[4]=="YNA73F") {
                                            //   	echo"DIAS DE DIFERENCIA soat".$diasparasoat;
                                                    // echo"DIAS DE DIFERENCIA TCNO".$diasparatecno;  
                                            //   }
                        
                                                $fechaInicial2 = date($fechaActual);
                                                $fechaFinal2 = date($rw1[7]);
                                                // Las convertimos a segundos
                                                $fechaInicialSegundos2 = strtotime($fechaInicial2);
                                                $fechaFinalSegundos2 = strtotime($fechaFinal2);
                                                // Hacemos las operaciones para calcular los dias entre las dos fechas y mostramos el resultado
                                                $dias2 = ($fechaFinalSegundos2 - $fechaInicialSegundos2) / 86400;
                                                 $diasparalice=round($dias2, 0, PHP_ROUND_HALF_UP);
                        
                        
                        
                                            if($diasparasoat<=3 or $diasparasoat<0){
                                                $color1='#F39C12';
                                            }
                        
                        
                                            if($diasparatecno<=3 or $diasparatecno<0){
                                                $color2='#F39C12';
                                            }
                        
                                            echo "<td colspan='1' width='0' align='center' >$rw4[4]</td>";
                                            echo	$LT->llenadocs4($DB2, "vehiculos", $rw1[6], 3, 35, "$rw4[1]",$color1);
                                            echo	$LT->llenadocs4($DB2, "vehiculos", $rw1[6], 4, 35, "$rw4[2]",$color2);	
                                    }else{
                                            echo "<td colspan='1' width='0' align='center' ></td>";
                                            echo "<td colspan='1' width='0' align='center' ></td>";
                                            echo "<td colspan='1' width='0' align='center' ></td>";
                                        }
                                    
                        
                                    //  $sql4="SELECT `usu_licencia`FROM `usiarios` WHERE usu_identificacions='$rw1[6]'";
                                    // $DB2->Execute($sql4);
                                    // $rw5=mysqli_fetch_row($DB2->Consulta_ID);
                                    if($diasparalice<=3 or $diasparalice<0){
                                        // $color3='#F39C12';
                        
                                        $color3="bgcolor='#F39C12'";
                                    }else{
                                        $color3="";
                        
                                    }
                        
                        
                        
                        
                                    $fechacero="0000-00-00";
                        
                                    if ($rw1[7]==$fechacero) {
                                        echo "<td colspan='1' width='0' align='center' ></td>";
                                    }else{
                        
                                        echo "<td colspan='1' width='0' align='center' ".$color3." >".$rw1[7]."</td>";
                                    }
                        
                        
                        
                        
                        
                                    if($rw1[6]!=0){
                                        $kilactual=$rw4[5];
                                        $kilparacamaceite=$rw4[6];
                                        $kmalcambaceite=$rw4[7];
                        
                                        $faltaparacamaceite=$kilactual-$kmalcambaceite;
                        
                                        if($kmalcambaceite!=0 or $kmalcambaceite!=""){
                                            if ($faltaparacamaceite>=$kilparacamaceite) {
                                                echo "<td colspan='1' width='0' bgcolor='#F39C12'  align='center' >Cambie el aceite, ".$faltaparacamaceite."km exede el limite </td>";
                                            }elseif($faltaparacamaceite<$kilparacamaceite){
                                                echo "<td colspan='1' width='0' align='center' > ".$faltaparacamaceite."km de ".$kilparacamaceite."km para cambio aceite</td>";
                                            }
                                        }else{
                        
                                            echo "<td colspan='1' width='0' align='center' >-</td>";
                                        }
                                    }
                                    echo "<td colspan='1' width='0' align='center' ></td>";
                        
                                    if($nivel_acceso==1 or $nivel_acceso==12){
                                        $DB->edites($rw1[4], "pre-operacional", 2,0);
                                    }
                                    
    
                                // }
                                }
                            }
                        }
            }
        
                $fechaInicial->modify('+1 day');
            }
} else if ($tabla == "detallerecogido") {

    $FB->titulo_azul1("ID", 1, 0, 5);
    $FB->titulo_azul1("VALOR", 1, 0, 0);
    $FB->titulo_azul1("OPERADOR", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    //$sql="SELECT ser_guiare ,`ser_valorabono` FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $sql = "SELECT  idasignaciondinero,`asi_valor`,asi_idpromotor FROM `asignaciondinero` inner join usuarios on idusuarios=asi_idautoriza WHERE `asi_fecha` like '$fechaab%'  and asi_idciudad=$id_param and roles_idroles not in (2,3) and asi_tipo='entregado'";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				";
        $sql5 = "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE `idusuarios`='$rw1[2]' ";
        $DB->Execute($sql5);
        $nombreuser = $DB->recogedato(1);
        echo "<td>" . $nombreuser . "</td>";
        $sumatotal = $rw1[1] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $sumatotal = number_format($sumatotal, 0, ".", ".");
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "Devolucionescuentas") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("IDSERVICIO", 1, 0, 0);
    $FB->titulo_azul1("VALOR", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    //$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $sql = "SELECT ser_guiare ,abo_valor,abo_idservicio FROM `abonosguias` inner join servicios on  idservicios=abo_idservicio WHERE abo_iduser='$id_param' and abo_fecha like '$fechaab%' and `abo_estado`='devolucion'";

    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[2] . "</td>
				<td>" . $rw1[1] . "</td>
				";
        $sumatotal = $rw1[1] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "Abonoscuentas") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("VALOR", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    //$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $sql = "SELECT ser_guiare ,abo_valor FROM `abonosguias` inner join servicios on  idservicios=abo_idservicio WHERE abo_iduser='$id_param' and abo_fecha like '$fechaab%' and `abo_estado`='abono'";

    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				";
        $sumatotal = $rw1[1] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "Abonossedes") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("VALOR", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    //$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $sql = "SELECT ser_guiare ,abo_valor FROM `abonosguias` inner join servicios on  idservicios=abo_idservicio WHERE abo_idsede='$id_param' and abo_fecha like '$fechaab%' and `abo_estado`='abono'";

    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				";
        $sumatotal = $rw1[1] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "Devolucionsedes") {

    $FB->titulo_azul1("GUIA", 1, 0, 5);
    $FB->titulo_azul1("VALOR", 1, 0, 0);
    //$idusuarioab=$id_param['idusuario'];
    //$fechaab=$id_param['fecha'];
    $fechaab = $_REQUEST["ide"];
    //$sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
    $sql = "SELECT ser_guiare ,abo_valor FROM `abonosguias` inner join servicios on  idservicios=abo_idservicio WHERE abo_idsede='$id_param' and abo_fecha like '$fechaab%' and `abo_estado`='devolucion'";

    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td>" . $rw1[0] . "</td>
				<td>" . $rw1[1] . "</td>
				";
        $sumatotal = $rw1[1] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
}
/* else if($tabla=="Abonoscuentas"){

  $FB->titulo_azul1("GUIA",1,0,5);
  $FB->titulo_azul1("VALOR",1,0,0);
  //$idusuarioab=$id_param['idusuario'];
  //$fechaab=$id_param['fecha'];
  $fechaab=$_REQUEST["ide"];
  $sql="SELECT ser_guiare ,`ser_valorabono`FROM `servicios` INNER JOIN guias ON idservicios=gui_idservicio where gui_idusuario='$id_param' and gui_fechacreacion like '$fechaab%' and ser_estado<100 and ser_valorabono>0";
  $DB1->Execute($sql); $va=0;
  $sumatotal=0;
  while($rw1=mysqli_fetch_row($DB1->Consulta_ID))
  {
  $id_p=$rw1[0];
  $va++; $p=$va%2;
  if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}

  echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
  echo "<td>".$rw1[0]."</td>
  <td>".$rw1[1]."</td>
  ";
  $sumatotal=$rw1[1]+$sumatotal;
  echo "</tr>";
  }
  $FB->titulo_azul1("TOTAL",1,0,5);
  $FB->titulo_azul1("$ $sumatotal",1,0,0);
  } */ else if ($tabla == "dineroentregado") {

    $FB->titulo_azul1("Valor", 1, 0, 5);
    $FB->titulo_azul1("Eliminar", 1, 0, 0);

    $fechaab = $_REQUEST["ide"];
    $sql = "SELECT idasignaciondinero ,`asi_valor` FROM `asignaciondinero`  WHERE  `asi_fecha`='$fechaab' and `asi_idpromotor`='$id_param' and asi_tipo='entregado'";
    $DB1->Execute($sql);
    $va = 0;
    $sumatotal = 0;
    while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
        $id_p = $rw1[0];
        $va++;
        $p = $va % 2;
        if ($p == 0) {
            $color = "#FFFFFF";
        } else {
            $color = "#EFEFEF";
        }

        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "
				<td>" . $rw1[1] . "</td>
				";
        $DB->edites($id_p, "cuentasentrega", 2, 0);
        $sumatotal = $rw1[1] + $sumatotal;
        echo "</tr>";
    }
    $FB->titulo_azul1("TOTAL", 1, 0, 5);
    $FB->titulo_azul1("$ $sumatotal", 1, 0, 0);
} else if ($tabla == "Buzonmovil") {

    $FB->llena_texto("Titulo:", 1, 1, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Mensaje:", 2, 9, $DB, "", "", "", 1, 0);
    $sql = "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) and usu_idsede='$id_sedes'";
    //  $sql="SELECT `idusuarios`,`usu_nombre`,zon_nombre FROM  seguimiento_user inner join zonatrabajo on seg_idzona=idzonatrabajo  inner join  `usuarios` on idusuarios=seg_idusuario inner join ciudades on inner_sedes=usu_idsede WHERE  seg_fechaalcohol='$fechaactual' and (usu_estado=1 or usu_filtro=1)  and `seg_motivo`='Ingreso'  and usu_idsede='$id_sedes'";
    $FB->llena_texto("Para Operario:", 3, 2, $DB, "($sql)", "", "", 2, 1);
} else if ($tabla == "Asignar Paquete") {

    $rw[4] = 0;
    $idsede = $_REQUEST["idciudad"];

    $FB->llena_texto("Tipo de Operador:", 1, 82, $DB, $vehiculo, "cambio_ajax2(this.value,27, \"llega_sub1\", \"param2\", 1,  $idsede)", @$rw[1], 17, 1);
    $FB->llena_texto("Operador:", 2, 444, $DB, "llega_sub1", "", "", 4, 1);

    $sql = "SELECT ser_estado,ser_motivo FROM servicios where idservicios=$id_param";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);
    if ($rw[0] == 5) {
        $FB->llena_texto("MOTIVO DE NO RECOGIDA:", 3, 9, $DB, "", "", @$rw[1], 1, 0);
    }

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);

    $FB->llena_texto("condicion", 1, 13, $DB, "", "", "1", 5, 0);


} else if ($tabla == "Entregar valor") {

    $idciudad = $_REQUEST["idciudad"];
    $valorapagar = $_REQUEST["valordos"];
    $valorEntregar = $_REQUEST["valorEntregar"];

    $conde2 = "and usu_idsede=$idciudad";
    $FB->llena_texto("Fecha de Busqueda:", 1, 10, $DB, "", "", "$fechaactual", 4, 0);
    $FB->llena_texto("Operario:", 2, 2, $DB, "SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE  (usu_estado=1 or usu_filtro=1) $conde2", "", $id_param, 1, 1);
    // $FB->llena_texto("Valor:", 3, 118, $DB, "", "", "$valorEntregar", 2, 1);
    echo"<td class='text'><label>Valor:</label></td><td><input type='number' class='form-control' id='param3' name='param3' onchange='verifica(this,$valorEntregar)' value='$valorEntregar'></td>";
    $FB->llena_texto("param4", 4, 13, $DB, "", "", $idciudad, 5, 0);

    $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    $FB->llena_texto("valorapagar", 1, 13, $DB, "", "", $valorapagar, 5, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    echo'<p id="error-message" style="color: red; display: none;">El valor no puede ser menor que el valor total a entregar.</p>';
} else if ($tabla == "Confirmar") {
    $idciudad = $_REQUEST["idciudad"];
    $FB->llena_texto("Dinero que Llego:", 6, 118, $DB, "", "", "", 2, 1);
    if ($idciudad == 'Gastos') {
        $FB->llena_texto("Gastos de:", 9, 2, $DB, "SELECT * FROM `clasificacion_gastos` ", "cambio_ajax2(this.value, 21, \"llega_sub1\", \"param10\", 1,$id_param)", "", 17, 1);
        $FB->llena_texto("Tipo:", 10, 4, $DB, "llega_sub1", "", "", 4, 1);
    }
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "confirmar", 5, 0);
    $FB->llena_texto("tipo_gastos", 1, 13, $DB, "", "", "$idciudad", 5, 0);
} else if ($tabla == "Confirmartransferencia") {
//	$idciudad=$_REQUEST["idciudad"];
    $FB->llena_texto("Numero de transacion:", 6, 1, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Valor Confirmado:", 8, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Foto Comprobante", 7, 6, $DB, "", "", "", 1, 0);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "Confirmartransferencia", 5, 0);
    //	$FB->llena_texto("tipo_gastos", 1, 13, $DB, "", "", "$idciudad", 5, 0);
}else if($tabla == "Confirmarcambios"){

	$FB->llena_texto("Descripcion:",6,9, $DB, "", "","" ,1, 1);			
	  $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
	  $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "Confirmarcambios", 5, 0);
  //	$FB->llena_texto("tipo_gastos", 1, 13, $DB, "", "", "$idciudad", 5, 0);

}
 else if ($tabla == "Confirmargastos") {
    $idciudad = $_REQUEST["idciudad"];
    $FB->llena_texto("Valor:", 8, 118, $DB, "", "", "", 2, 1);
    if ($idciudad == 'Gastos') {
        $FB->llena_texto("Gastos de:", 9, 2, $DB, "SELECT * FROM `clasificacion_gastos` ", "cambio_ajax2(this.value, 21, \"llega_sub1\", \"param10\", 1,$id_param)", "", 17, 1);
        $FB->llena_texto("Tipo:", 10, 4, $DB, "llega_sub1", "", "", 4, 1);
    }
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "Confirmargastos", 5, 0);
    $FB->llena_texto("tipo_gastos", 1, 13, $DB, "", "", "$idciudad", 5, 0);
} else if ($tabla == "Aprobar") {

    $FB->llena_texto("Fecha de aprobacion:", 7, 10, $DB, "", "", "$fechaactual", 4, 0);
    $FB->llena_texto("Dinero Aprobado para el gasto:", 6, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "aprobar", 5, 0);
} else if ($tabla == "Verificar Remesa") {

    //$FB->llena_texto("Dinero Aprobado para el gasto:",6, 118, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Descripcion:", 2, 9, $DB, "", "", "", 1, 1);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "Verificar Remesa", 5, 0);
} else if ($tabla == "Cierre del dia") {

    $valorjson = $_REQUEST["valordos"];
    $valor2json = $_REQUEST["varcal"];
    $fecharecierre = $_REQUEST["fecharecierre"];
    $idciudad = $_REQUEST["idciudad"];
    $valorjson = json_encode($valorjson, JSON_FORCE_OBJECT);
    // $valor2json=json_encode($valor2json, JSON_FORCE_OBJECT);
    if ($nivel_acceso == 1) {
        echo "<div class='alert alert-danger'>
		<strong> CIERRE DIARIO </strong> ¿DESEA HACER EL CIERRE DE ESTE DIA  POR ESTE VALOR?
	  ";
        echo " <input name='dinero' id='dinero'   type='text' value='$id_param' >
		</div>";
    } else {
        echo "<div class='alert alert-danger'>
		<strong> CIERRE DIARIO </strong> ¿DESEA HACER EL CIERRE DE ESTE DIA  POR ESTE VALOR $id_param?
	  </div>";
        $FB->llena_texto("dinero", 1, 13, $DB, "", "", $id_param, 5, 0);
    }
    //print_r($valor2json);

    $FB->llena_texto("fecharecierre", 1, 13, $DB, "", "", $fecharecierre, 5, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $idciudad, 5, 0);
    $FB->llena_texto("valoresjs", 1, 13, $DB, "", "", $valorjson, 5, 0);
    $FB->llena_texto("valores2js", 1, 13, $DB, "", "", $valor2json, 5, 0);
} else if ($tabla == "Recoger Paquete") {



    // if ($id_usuario==2068) {
    // if ($id_usuario==523) {
    
        echo '
		<div style="width: 100%; height: 100vh;">
		<iframe 
			src="/nueva_plataforma/controller/RecogerController.php?accion=vista&idServicio='.$id_param.'&sede='.$id_sedes.'&acceso='.$nivel_acceso.'&nombre='.$id_nombre.'&porcobrar='.$cambio.'&usuario='.$id_usuario.'"
			style="width: 100%; height: 100%; border: 0;"
			allowfullscreen
			loading="lazy">
		</iframe>
		</div>';
    //  }else{
    // $FB->llena_texto("¿Paquete Recogido?:", 1, 82, $DB, $recogido, "cambio_ajax2(this.value, 11, \"llega_sub1\", \"param2\", 1,$id_param)", @$rw[1], 17, 1);
    // $FB->llena_texto("", 2, 4, $DB, "llega_sub1", "", "", 4, 0);
    // $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);

    // $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    // $FB->llena_texto("id_param1", 1, 13, $DB, "", "", "", 5, 0);
    // $FB->llena_texto("encomiendas", 1, 13, $DB, "", "", "0", 5, 0);

    //  }

} else if ($tabla == "Recoger") {



    // if ($id_usuario==2068) {
    // if ($id_usuario==523) {
    
        // echo '
		// <div style="width: 100%; height: 100vh;">
		// <iframe 
		// 	src="/nueva_plataforma/controller/RecogerController.php?accion=vista&idServicio='.$id_param.'&sede='.$id_sedes.'&acceso='.$nivel_acceso.'&nombre='.$id_nombre.'&porcobrar='.$cambio.'&usuario='.$id_usuario.'"
		// 	style="width: 100%; height: 100%; border: 0;"
		// 	allowfullscreen
		// 	loading="lazy">
		// </iframe>
		// </div>';
    //  }else{
    $FB->llena_texto("¿Paquete Recogido?:", 1, 82, $DB, $recogido, "cambio_ajax2(this.value, 11, \"llega_sub1\", \"param2\", 1,$id_param)", @$rw[1], 17, 1);
    $FB->llena_texto("", 2, 4, $DB, "llega_sub1", "", "", 4, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);

    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param1", 1, 13, $DB, "", "", "", 5, 0);
    $FB->llena_texto("encomiendas", 1, 13, $DB, "", "", "0", 5, 0);

    //  }

} else if ($tabla == "Recoger oficina") {

    $FB->llena_texto("¿Paquete Recogido?:", 1, 82, $DB, $recogido, "cambio_ajax2(this.value, 11, \"llega_sub1\", \"param2\", 1,$id_param)", @$rw[1], 17, 1);
    $FB->llena_texto("", 2, 4, $DB, "llega_sub1", "", "", 4, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);

    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param1", 1, 13, $DB, "", "", "operadoroficina", 5, 0);
    $FB->llena_texto("encomiendas", 1, 13, $DB, "", "", "0", 5, 0);

    
} else if ($tabla == "Entregar Guias") {

	
    if (isset($_REQUEST["cambio"])) {
        $cambio=$_REQUEST["cambio"];
    } else {
        $cambio=0;
    }

	// $FB->llena_texto("¿Paquete Entregado?:",1,82, $DB, $entregado, "cambio_ajax2(this.value, 12, \"llega_sub1\",\"$cambio\", 1,$id_param)",@$rw[1], 17, 1);
	// $FB->llena_texto("", 2, 4, $DB, "llega_sub1", "", "",4,0);
	// $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
	// $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
	// $FB->llena_texto("porcobrar", 1, 13, $DB, "", "",$cambio, 5, 0);
		echo '
		<div style="width: 100%; height: 100vh;">
		<iframe 
			src="/nueva_plataforma/controller/EntregarController.php?accion=vista&idServicio='.$id_param.'&sede='.$id_sedes.'&acceso='.$nivel_acceso.'&nombre='.$id_nombre.'&porcobrar='.$cambio.'&usuario='.$id_usuario.'"
			style="width: 100%; height: 100%; border: 0;"
			allowfullscreen
			loading="lazy">
		</iframe>
		</div>';

 } 
 else if ($tabla == "seguimientoruta") {

        $mensaje=$_REQUEST["mensaje"];
        echo '<div class="containerruta2">';
        echo '<div class="headingruta">¡Direccion anterior: </div>';
        echo '<div class="messageruta">'. $mensaje.'</div>';
      echo '</div>';
        echo "<h1>¿A Donde se dirije?</h1>";
       $idestadoguia="SELECT  seg_idservicio  as id FROM seguimientoruta where `seg_idusuario`='$id_usuario' and seg_fecha like '%$fechaactual%'  and (seg_estado='En ruta' or seg_tipo='opcionruta')  order by `seg_fechaestado` desc limit 1";
        $DB->Execute($idestadoguia);
        $idservicioguia=$DB->recogedato(0);
        if($idservicioguia=='6'){ $idservicioguia=0; }
       
        // echo"SELECT  CONCAT(id,'|',tipo,'|',nombre) as iddatos,nombre FROM (SELECT idseguimientoruta as id, seg_direccion as nombre,seg_tipo as tipo,orden FROM seguimientoruta inner join ord_recoentregas on orden_idservicio=seg_idservicio WHERE seg_idusuario =$id_usuario and seg_fecha like '$fechaactual%' and seg_idservicio!='$idservicioguia' and seg_estado!='completado' and seg_estado!='Reasignada'  and seg_estado!='Cambioruta' and seg_estado!='NO Recogida' and seg_estado!='NO entregado' and orden_fechadiaejecucion='$fechaactual'   UNION SELECT idopcionruta as id, `opc_nombre` as nombre,'opcionruta' as tipo,(1000+idopcionruta) as orden FROM `opcionruta` where  idopcionruta!='$idservicioguia' order by orden) as datos UNION (SELECT CONCAT(idservicios,'|', UPPER('pendiente x cobrar'), '|',cli_direccion) as iddatos, CONCAT( UPPER('pendiente x cobrar'), '|',cli_direccion) as nombre FROM `clientes` inner join clientesservicios on cli_idclientes=idclientes inner join rel_sercli on idclientesdir=ser_idclientes inner join servicios on ser_idservicio=idservicios inner join cuentaspromotor on cue_idservicio=idservicios where ser_pendientecobrar=1 and ser_estado!=100 and cue_idoperpendiente=$id_usuario ORDER BY ser_fechaentrega ASC)";
       $FB->llena_texto("Seleccione", 1, 2, $DB, "SELECT  CONCAT(id,'|',tipo,'|',nombre) as iddatos,nombre FROM (SELECT idseguimientoruta as id, seg_direccion as nombre,seg_tipo as tipo,orden FROM seguimientoruta inner join ord_recoentregas on orden_idservicio=seg_idservicio WHERE seg_idusuario =$id_usuario and seg_fecha like '$fechaactual%' and seg_idservicio!='$idservicioguia' and seg_estado!='completado' and seg_estado!='Reasignada'  and seg_estado!='Cambioruta' and seg_estado!='NO Recogida' and seg_estado!='NO entregado' and orden_fechadiaejecucion='$fechaactual'   UNION SELECT idopcionruta as id, `opc_nombre` as nombre,'opcionruta' as tipo,(1000+idopcionruta) as orden FROM `opcionruta` where  idopcionruta!='$idservicioguia' order by orden) as datos UNION (SELECT CONCAT(idservicios,'|', UPPER('pendiente x cobrar'), '|',cli_direccion) as iddatos, CONCAT( UPPER('pendiente x cobrar'), '|',cli_direccion) as nombre FROM `clientes` inner join clientesservicios on cli_idclientes=idclientes inner join rel_sercli on idclientesdir=ser_idclientes inner join servicios on ser_idservicio=idservicios inner join cuentaspromotor on cue_idservicio=idservicios where ser_pendientecobrar=1 and ser_estado!=100 and cue_idoperpendiente=$id_usuario ORDER BY ser_fechaentrega ASC)", "", "", 17, 1);   

} 
 else if ($tabla == "cambiarruta") {
         $mensaje=$_REQUEST["mensaje"];
         $idestadoguia="SELECT  seg_idservicio as id, idseguimientoruta ,seg_tipo  FROM seguimientoruta where `seg_idusuario`='$id_usuario' and seg_fecha like '%$fechaactual%' and seg_estado!='Cambioruta' order by `seg_fechaestado` desc limit 1";
         $DB->Execute($idestadoguia);
         $rw1=mysqli_fetch_row($DB->Consulta_ID);
         $idservicioguia=$rw1[0];
         $idseguimiento=$rw1[1];   
         if($idservicioguia=='6'){ $idservicioguia=0; }  


         

        // Expresión regular para eliminar todo antes de "calle" o "carrera"
        $direccion = preg_replace('/^.*?(calle|carrera)\s*/i', '$1 ', $mensaje);

        
         $url = "https://www.google.com/maps/dir/?api=1&destination=$direccion&travelmode=driving";

         echo '<div class="containerruta">';
         echo '<div class="headingruta">¡Usted se dirige a: </div>';
         echo '<div class="headingruta">'. $mensaje.'</div>';
         echo '<div class="messageruta">¡Que tenga un buen viaje! <a href="'.$url.'"  target="_blank">📍ir</a> </div>';
                                                    
         
            $trans = "SELECT ser_visto,ser_estado,ser_consecutivo,ser_destinatario,ser_piezas,cli_idciudad,cli_nombre,ciu_nombre,ser_valor,ser_piezas FROM serviciosdia  where idservicios =$idservicioguia";
            $DB1->Execute($trans);
            $transp = mysqli_fetch_array($DB1->Consulta_ID);
            $va=1;
            $sel = "SELECT ciu_nombre FROM ciudades where idciudades=$transp[5]";
            $DB1->Execute($sel);
            $idciudad = $DB1->recogedato(0);
         

         echo '<div class="messageruta">Remitente: '.$transp[6].'  Ciudad: '.$idciudad.'</div>';
         echo '<div class="messageruta">Destinatario: '.$transp[3].'  Ciudad: '.$transp[7].'</div>';
         if ($rw1[2]=="PENDIENTE X COBRAR"){

            echo "<div id='campo$va'>";
            echo "VALOR A COBRAR: $transp[8]<br>";
            echo "<a  onclick='pop_dis13($idservicioguia,\"Entregar Guias\",1)';  style='cursor: pointer;' class='btn btn-primary btn-lg' title='Cobrar Guias' role='button' >Cobrar</a>";
            echo "</div>";
         }else{
            if ($transp[1] == 3) {
                echo "<div id='campo$va'>"; if($transp[0]==1){ $st="SI"; $colorfondo="#074f91"; } else { $st="NO"; $colorfondo="#941727"; } 
                echo " ¿EN RUTA?<select  style='width:100px;border:1px solid #f9f9f9;background-color:$colorfondo;color:#f9f9f9;font-size:12px'  name='param$va' id='param$va'  onChange='cambio_ajax2(this.value, 71, \"campo$va\", \"$va\", 1, $idservicioguia)'  required>";
                    $LT->llenaselect_ar($st,$estado_rec);
                echo "</select>";
            
                if($transp[0]==1){
                echo "<a  onclick='pop_dis114($idservicioguia,\"Recoger Paquete\")';  style='cursor: pointer;' class='btn btn-primary btn-lg' title='Recoger Paquete' role='button' >Recoger</a>";
                }	
                echo "</div>";
                
        
                echo '</div>';
            }else if ($transp[1] == 9) {
                echo '<div class="messageruta">#GUIA:  '.$transp[2].' </div>';
        		echo "<p><strong style='font-size:18px; color:#941727; font-weight:bold;'>PIEZAS: $transp[9]</strong></p>";

                if ($transp[0] == 1) {
                    $st = "SI";
                    $colorfondo = "#074f91";

                    $estado_rec2[0] = "SI";
                    echo "<select name='param14' id='param14' class='form-control'  style='width:100px;border:1px solid #f9f9f9;background-color:$colorfondo;color:#f9f9f9;font-size:18px'  required>";
                    $LT->llenaselect_ar($st, $estado_rec2);
                    echo "</select>";
                } else {
                    $st = "NO";
                    $colorfondo = "#941727";
                    echo "<div id='campo$va'>";
                    
                    echo " ¿EN RUTA?<select  style='width:100px;border:1px solid #f9f9f9;background-color:$colorfondo;color:#f9f9f9;font-size:12px'  name='param$va' id='param$va'  onChange='cambio_ajax2(this.value, 70, \"campo$va\", \"$va\", 1, $idservicioguia)'  required>";
                    $LT->llenaselect_ar($st, $estado_rec);
                    echo "</select>";
                    
                }


                echo "<a  onclick='pop_dis113($idservicioguia,\"Entregar Guias\")';  style='cursor: pointer;' class='btn btn-primary btn-lg' title='Entregar Guias' role='button' >Entregar</a>";
                echo "</div>";
                echo "</div>";
            }
        }
    //    echo"SELECT  CONCAT(id,'|',tipo,'|',nombre) as iddatos,nombre FROM (SELECT idseguimientoruta as id, seg_direccion as nombre,seg_tipo as tipo,orden FROM seguimientoruta inner join ord_recoentregas on orden_idservicio=seg_idservicio WHERE seg_idusuario =$id_usuario and seg_fecha like '$fechaactual%' and seg_idservicio!='$idservicioguia' and seg_estado!='completado' and seg_estado!='Cambioruta'  and seg_estado!='Reasignada' and seg_estado!='NO Recogida' and seg_estado!='NO entregado' and orden_fechadiaejecucion='$fechaactual'   UNION SELECT idopcionruta as id, `opc_nombre` as nombre,'opcionruta' as tipo,(1000+idopcionruta) as orden FROM `opcionruta` where  idopcionruta!='$idservicioguia' order by orden) as datos ";
         $FB->llena_texto("CAMBIAR RUTA",1, 2, $DB, "SELECT  CONCAT(id,'|',tipo,'|',nombre) as iddatos,nombre FROM (SELECT idseguimientoruta as id, seg_direccion as nombre,seg_tipo as tipo,orden FROM seguimientoruta inner join ord_recoentregas on orden_idservicio=seg_idservicio WHERE seg_idusuario =$id_usuario and seg_fecha like '$fechaactual%' and seg_idservicio!='$idservicioguia' and seg_estado!='completado' and seg_estado!='Cambioruta'  and seg_estado!='Reasignada' and seg_estado!='NO Recogida' and seg_estado!='NO entregado' and orden_fechadiaejecucion='$fechaactual'   UNION SELECT idopcionruta as id, `opc_nombre` as nombre,'opcionruta' as tipo,(1000+idopcionruta) as orden FROM `opcionruta` where  idopcionruta!='$idservicioguia' order by orden) as datos UNION (SELECT CONCAT(idservicios,'|', UPPER('pendiente x cobrar'), '|',cli_direccion) as iddatos, CONCAT( UPPER('pendiente x cobrar'), '|',cli_direccion) as nombre FROM `clientes` inner join clientesservicios on cli_idclientes=idclientes inner join rel_sercli on idclientesdir=ser_idclientes inner join servicios on ser_idservicio=idservicios inner join cuentaspromotor on cue_idservicio=idservicios where ser_pendientecobrar=1 and ser_estado!=100 and cue_idoperpendiente=$id_usuario ORDER BY ser_fechaentrega ASC)", "", "", 17, 1);
         $FB->llena_texto("MOTIVO :",2,9, $DB, "", "","" ,1, 0);
         $FB->llena_texto("Imagen", 4, 6, $DB, "", "", "", 1, 0);
         $FB->llena_texto("param3", 1, 13, $DB, "", "", "$idseguimiento", 5, 0);


 } 
 else if ($tabla == "Factura") {   

    $sql = "SELECT `idclientes`,`ser_consecutivo`, `ser_resolucion`,`cli_nombre`,  `ser_destinatario`, `ser_telefonocontacto`,`ciu_nombre`,
 `ser_direccioncontacto`, `ser_paquetedescripcion`, `ser_piezas`,`ser_clasificacion`, `ser_valorprestamo`, 
 `ser_valorabono`, `ser_valorseguro`, `ser_tipopaquete`,`cli_iddocumento`, `cli_telefono`, `cli_email`, 
 `cli_idciudad`, `cli_direccion`,  `ser_fechaentrega`,`ser_prioridad`,  `idservicios` FROM `clientes` inner join clientesdir on cli_idclientes=idclientes  inner join rel_sercli on idclientes=ser_idclientes 
 inner join servicios on  ser_idservicio=idservicios  inner join ciudades on ser_ciudadentrega=idciudades where idservicios=$id_param ";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $planillas = explode("/", $rw[1]);
    include("imprimir.php");
    $rw[9] = $tipopago[$rw[9]];
} elseif ($tabla == "Reclamo_Descripcion") {

    $idservicio = $_REQUEST["dir"];
    $sql = "SELECT `idreclamos`, `rec_descripcion` FROM `reclamos` where idreclamos=$id_param ";
    $DB->Execute($sql);

    $rw = mysqli_fetch_array($DB->Consulta_ID);

    $FB->llena_texto("Descripcion de LLamada:", 2, 9, $DB, "", "", "$rw[1]", 1, 1); //aqui voy ........................ 
    // $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    // $FB->llena_texto("param10", 1, 13, $DB, "", "", $idservicio, 5, 0);
}
else if ($tabla == "Editar datos") {

    $sql = "SELECT 
        `idclientes`,
        `cli_iddocumento`,
        `cli_telefono`,
        `cli_email`, 
        `cli_idciudad`, 
        `cli_direccion`, 
        `cli_nombre`, 
        `cli_clasificacion`,
        `ser_telefonocontacto`, 
        `ser_destinatario`, 
        `ser_direccioncontacto`,
        `ser_ciudadentrega`, 
        `ser_tipopaquete`, 
        `ser_paquetedescripcion`, 
        `ser_fechaentrega`,
        `ser_prioridad`,  
        `ser_valorprestamo`, 
        `ser_valorabono`, 
        `ser_valorseguro`, 
        `idservicios`,
        cli_retorno,
        idclientesdir,
        ser_idusuarioguia 
    FROM serviciosdia  
    where idservicios=$id_param";

    $DB1->Execute($sql);
    $rw = mysqli_fetch_array($DB1->Consulta_ID);
    $blo = 0;
    $blo2 = "";
    $bloC = 1; 
    @$param4 = $rw[4];
    if ($nivel_acceso != 1) {
        $cond6 = " WHERE inner_sedes='$id_sedes' and inner_estados=1";
    } else {
        $cond6 = "  WHERE  inner_estados=1";
    }
    if ($rw[22] != 0) {
        $blo = 2;
        $blo2 = "disabled";
        $bloC = 2; 
    }

    $FB->titulo_azul1("Remitente", 10, 0, 5);

    //$FB->llena_texto("CC / Nit:",1, 117, $DB, "", "", $rw[1], 1, 0);
    $FB->llena_texto("Tel&eacute;fonos :", 32, 120, $DB, "", "", "****", 1, $blo);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $rw[2], 5, 0);

    echo "<tr bgcolor='#FFFFFF' ><td>Remitente:</td><td colspan=1><div id='clientesdir'>";
    echo " <input name='param6' id='param6' class='trans'  type='text' value='$rw[6]' onkeypress='return noenter();' $blo2>
		</div></td>";

    if ($bloC == 2) {
        $FB->llena_texto("Ciudad:", 00, 2, $DB, "(SELECT `idciudades`,`ciu_nombre` FROM `ciudades` )", "", "$param4", 1, $bloC);
        echo'<input type="hidden" id="param4" name="param4" value="'.$param4.'">';
    }else {
        $FB->llena_texto("Ciudad:", 4, 2, $DB, "(SELECT `idciudades`,`ciu_nombre` FROM `ciudades` )", "", "$param4", 1, 0);

    }
   

    @$direcc = explode("&", $rw[5]);
    @$param5 = $direcc[0];
    @$param51 = $direcc[1];
    @$param19 = $direcc[2];
    @$param20 = $direcc[3];
    @$param23 = $direcc[4];
    echo "<tr bgcolor='#FFFFFF' ><td>Lugar de Recogida:</td>
	<td align='left' ><select class='trans'  name='param5' id='param5'  $blo2 >";
    echo "<option  value='0'>Seleccione...</option>";
    $sql = "SELECT `iddireccion`, `dir_nombre` FROM `direccion` ";
    $LT->llenaselect($sql, 1, 1, $param5, $DB);
    echo "</select>
	<input name='param51' id='param51' class='trans'  type='text' value='$param51' onkeypress='return noenter();' $blo2>
	</td>";

    echo "</tr><tr bgcolor='#F3F3F3' ><td></td>
	<td align='left' ><select class='trans'  name='param19' id='param19' $blo2 >";
    echo "<option  value='0'>Seleccione...</option>";
    $sql = "SELECT `idlugar`, `lug_nombre` FROM `lugar`  ";
    $LT->llenaselect($sql, 1, 1, $param19, $DB);
    echo "</select>
	<input name='param20' id='param20' class='trans'  type='text' value='$param20' onkeypress='return noenter();' $blo2>
	</td>
	
	</tr>";

    $FB->llena_texto("Barrio:", 23, 1, $DB, "", "", $param23, 1, $blo);
    $FB->llena_texto("Email:", 3, 111, $DB, "", "", $rw[3], 17, $blo);
    $FB->titulo_azul1("Destinatario", 9, 0, 5);

    $FB->llena_texto("Tel&eacute;fono:", 28, 120, $DB, "", "", "*****", 17, 1);
    $FB->llena_texto("param8", 1, 13, $DB, "", "", $rw[8], 5, 0);
    $FB->llena_texto("Nombre:", 9, 1, $DB, "", "", $rw[9], 17, 1);
    if ($nivel_acceso!=1) {
        $FB->llena_texto("Ciudad:", 0, 2, $DB, "(SELECT `idciudades`,`ciu_nombre` FROM `ciudades`  where inner_estados=1)", "", "$rw[11]", 1, "disabled");
         echo'<input type="hidden" id="param11" name="param11" value="'.$rw[11].'">';
    }else{
        $FB->llena_texto("Ciudad:", 11, 2, $DB, "(SELECT `idciudades`,`ciu_nombre` FROM `ciudades`  where inner_estados=1)", "", "$rw[11]", 1, 0);
    }
   
    @$direcc2 = explode("&", $rw[10]);
    @$param10 = $direcc2[0];
    @$param101 = $direcc2[1];
    @$param21 = $direcc2[2];
    @$param22 = $direcc2[3];
    @$param24 = $direcc2[4];

    echo "</tr><tr bgcolor='#F3F3F3' ><td>Direcci&oacute;n del Contacto:</td>
	<td align='left' ><select class='trans'  name='param10' id='param10' >";
    echo "<option  value='0'>Seleccione...</option>";
    $sql = "SELECT `iddireccion`, `dir_nombre` FROM `direccion` ";
    $LT->llenaselect($sql, 1, 1, $param10, $DB);

    echo "</select>
	<input name='param101' id='param101' class='trans'  type='text' value='$param101' onkeypress='return noenter();'>
	</td></tr>";

    echo "<tr bgcolor='#FFFFFF' ><td>Lugar de Entrega:</td>
	<td align='left' ><select class='trans'  name='param21' id='param21' >";
    echo "<option  value='0'>Seleccione...</option>";
    $sql = "SELECT `idlugar`, `lug_nombre` FROM `lugar`  ";
    $LT->llenaselect($sql, 1, 1, $param21, $DB);
    echo "</select>
	<input name='param22' id='param22' class='trans'  type='text' value='$param22' onkeypress='return noenter();'>
	</td>
	";
    $FB->llena_texto("Barrio:", 24, 1, $DB, "", "", $param24, 1, 0);

    $FB->llena_texto("id_param0", 1, 13, $DB, "", "", "$rw[21]", 5, 0); //idclientes
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", "$rw[19]", 5, 0);  // idservicio

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("param1", 1, 13, $DB, "", "", "EDITAR DATOS", 5, 0);
    $FB->llena_texto("id_param1", 1, 13, $DB, "", "", "recogidas", 5, 0);
    $FB->llena_texto("encomiendas", 1, 13, $DB, "", "", "$blo", 5, 0);
    $FB->llena_texto("dir", 1, 13, $DB, "", "", $dir, 5, 0);
} else if ($tabla == "Editar Datos Guia") {

    $dir = $_REQUEST["dir"];
    $sql = "SELECT `idclientes`, `cli_iddocumento`, `cli_telefono`, `cli_email`, `cli_idciudad`, `cli_direccion`, `cli_nombre`, `ser_iddocumento`,`ser_telefonocontacto`, `ser_destinatario`, `ser_direccioncontacto`,`ser_ciudadentrega`,
 `ser_tipopaquete`, `ser_paquetedescripcion`, `ser_fechaentrega`,`ser_prioridad`,  `ser_valorprestamo`, `ser_valorabono`, `ser_valorseguro`, `idservicios`,cli_retorno,idclientesdir,ser_descllamada,date(ser_fecharegistro) 
 ,`ser_peso`,`ser_guiare`,ser_volumen,`ser_piezas`,ser_descripcion,ser_verificado,ser_tipopaq,ser_clasificacion,`ser_valor`, `ser_estado`,`ser_fechafinal`, `ser_fechaentrega`, `ser_prioridad`,  `ser_recogida`, `ser_motivo`,
 `ser_fechaconfirmacion`, `ser_fechaasignacion`, `ser_estado`,ser_devolverreci,ser_idverificadopeso,ser_descentrega,ser_pendientecobrar
 FROM  serviciosdia  where idservicios=$id_param ";
 //van 30 datos consultados
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);
    $FB->llena_texto("dir", 1, 13, $DB, "", "", $dir, 5, 0);
    echo"Precio kilo".$precioinicialkilos=$_SESSION['precioinicial'];
    include("editardatos.php");
    
    
} else if ($tabla == "Cuentas") {

    include("cuentas.php");
} else if ($tabla == "Cotizar") {

    include("cotizar.php");
} else if ($tabla == "Temperatura") {

    /* echo	'<div class="form-group">
      <div class="btn btn-success btn-file">
      <i class="fa fa-paperclip"></i>  Temperatura
      <input type="file" name="paramc4" />
      </div>
      <p class="help-block">Tama&ntilde;o: 215px x 215px</p>
      </div>'; */
    $slq = "SELECT idpreoperacinal FROM `pre-operacional` where preidusuario='$id_usuario' and prefechaingreso like '$fechaactual%'";
    $DB->Execute($slq);
    $rw2 = mysqli_fetch_row($DB->Consulta_ID);

    $FB->llena_texto("Imagen Temperatura:", 2, 6, $DB, "", "", "", 2, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $rw2[0], 5, 0);
} else if ($tabla == "Salida") {

    /* echo	'<div class="form-group">
      <div class="btn btn-success btn-file">
      <i class="fa fa-paperclip"></i>  Temperatura
      <input type="file" name="paramc4" />
      </div>
      <p class="help-block">Tama&ntilde;o: 215px x 215px</p>
      </div>'; */
    $slq = "SELECT idpreoperacinal FROM `pre-operacional` where preidusuario='$id_usuario' and prefechaingreso like '$fechaactual%'";
    $DB->Execute($slq);
    $rw2 = mysqli_fetch_row($DB->Consulta_ID);
    $FB->llena_texto("Kilometraje actual:", 9, 1, $DB, "", "", $rw[9], 17, 1);
    $FB->llena_texto("Imagen Kilometraje:", 2, 6, $DB, "", "", "", 2, 0);
    
    
} else if ($tabla == "recorridooperador") {

    $param33 = $id_param;
    $fechaactual = $_REQUEST["ide"];
    include("detalle_recorrido.php");
} else if($tabla=="recorridooperadorruta"){ 
	
	$param33=$id_param;
	$fechaactual=$_REQUEST["ide"];
   include("detalle_recorrido_ruta.php");

}else if ($tabla == "Recogidas"){

$sql = "SELECT `idclientes`, `cli_iddocumento`, `cli_nombre`,  `cli_telefono`, `cli_email`, `ciu_nombre`, `cli_direccion`, `cli_clasificacion`, `cli_idciudad`,  `cli_tipo`,
`idservicios`, `ser_destinatario`, `ser_telefonocontacto`,`ser_ciudadentrega`,`ser_direccioncontacto`, `ser_tipopaquete`,`ser_piezas`, `ser_paquetedescripcion`, 
`ser_horaentrega`,`ser_clasificacion`,`ser_valorprestamo`, `ser_valorseguro`,`ser_valorabono`, `ser_consecutivo`,`ser_idresponsable`, `ser_iduserverific`,
`ser_idasignacion`,`ser_peso`,`ser_guiare`,`ser_fechafinal`,`ser_valor`,  `ser_fechaentrega`, `ser_prioridad`,  `ser_recogida`, `ser_motivo`,  `ser_fecharegistro`,
`ser_fechaconfirmacion`, `ser_fechaasignacion`, `ser_estado`,ser_devolverreci,ser_volumen,ser_idverificadopeso,ser_descentrega,ser_pendientecobrar FROM serviciosdia  where idservicios=$id_param ";
$DB->Execute($sql);
$rw = mysqli_fetch_array($DB->Consulta_ID);
    //Van 42 datos consultados

    if ($rw[0] == "" or $rw[0] == 0) {

    $sql = "SELECT `idclientes`, `cli_iddocumento`, `cli_nombre`,  `cli_telefono`, `cli_email`, `ciu_nombre`, `cli_direccion`, `cli_clasificacion`, `cli_idciudad`,  `cli_tipo`,
    `idservicios`, `ser_destinatario`, `ser_telefonocontacto`,`ser_ciudadentrega`,`ser_direccioncontacto`, `ser_tipopaquete`,`ser_piezas`, `ser_paquetedescripcion`, 
    `ser_horaentrega`,`ser_clasificacion`,`ser_valorprestamo`, `ser_valorseguro`,`ser_valorabono`, `ser_consecutivo`,`ser_idresponsable`, `ser_iduserverific`,
    `ser_idasignacion`,`ser_peso`,`ser_guiare`,`ser_fechafinal`,`ser_valor`,  `ser_fechaentrega`, `ser_prioridad`,  `ser_recogida`, `ser_motivo`,  `ser_fecharegistro`,
    `ser_fechaconfirmacion`, `ser_fechaasignacion`, `ser_estado`,ser_devolverreci,ser_volumen,ser_idverificadopeso,ser_descentrega FROM servicios2 inner join rel_sercli  on idservicios=ser_idservicio  inner join clientesservicios on idclientesdir=ser_idclientes inner join clientes on idclientes=cli_idclientes  inner join ciudades on idciudades=ser_ciudadentrega  where idservicios=$id_param ";
        $DB->Execute($sql);
        $rw = mysqli_fetch_array($DB->Consulta_ID);
    }

    if ($rw[38] == 6 and $rw[41] == 1) {
        $rw[38] = 14;
    }
//echo $rw[38];
    $estadoguia = $estado_guia["$rw[38]"];

    $FB->titulo_azul1("Estado de la GUIA: $estadoguia  ", 10, 0, 5);

    $FB->titulo_azul1("Datos Cliente", 10, 0, 5);
    $rw[7] = $clasificacion[$rw[7]];
    $rw[19] = $tipopago[$rw[19]];
    $rw[6] = str_replace("&", " ", $rw[6]);
    $rw[14] = str_replace("&", " ", $rw[14]);

    echo "<tr bgcolor='#FFFFFF' >
          <td>CC / Nit:</td><td >$rw[1]</td>
		  <td>Nombre Del Cliente:</td><td >$rw[2]</td>
          <td>Tel&eacute;fonos:</td><td >$rw[3]</td>
          
     </tr>";
    $sel = "SELECT ciu_nombre FROM ciudades where idciudades=$rw[8]";
    $DB->Execute($sel);
    $idciudad = $DB->recogedato(0);

    echo "<tr bgcolor='#F3F3F3' >
		  <td>Email:</td><td >$rw[4]</td>
          <td>Ciudad :</td><td >$idciudad</td>
          <td>Direccion:</td><td >$rw[6]</td>     
     </tr>";
    echo "<tr bgcolor='#FFFFFF' >
          <td colspan='2'>Clasificaci&oacute;n:</td><td colspan='4'>$rw[7]</td>
     </tr>";

    $FB->titulo_azul1("Datos Destinatario", 10, 0, 5);

    $rw[15] = utf8_encode($rw[15]);
    echo "<tr bgcolor='#FFFFFF' >
          <td>Nombre Destinatario:</td><td >$rw[11]</td>
		  <td>Tel&eacute;fono:</td><td >$rw[12]</td>
          <td>Ciudad Destino:</td><td >$rw[5]</td>
          
     </tr>";
    echo "<tr bgcolor='#F3F3F3' >
		  <td>Direccion del Contacto:</td><td >$rw[14]</td>
            <td>Hora Recogida:</td><td >$rw[18]</td>
          <td></td><td ></td>  
     </tr>";

    $FB->titulo_azul1("Servicio", 10, 0, 5);
    if ($rw[39] == 1) {
        $rw[39] = 'SI';
    } else {
        $rw[39] = 'NO';
    }

    echo "<tr bgcolor='#FFFFFF' >
	  <td># Guia/Pre Guia:</td><td >$rw[23] / $rw[28]</td>
	  
	  <td>Devolver Recibido:</td><td> $rw[39]</td>
	  <td>Tipo de paquete:</td><td >$rw[15]</td>
	       
 </tr>";
 if ($rw[43]==1) {
    $pendiente="<br> <mark> Pago pendiente </mark>";
 }else {
    $pendiente="";

 }

    echo "<tr bgcolor='#F3F3F3' >
		<td>Piezas:</td><td ><a  onclick='pop_dis5(\"$rw[23]\",\"Seguimiento piezas\")';  style='cursor: pointer;' title='Recogidas' >$rw[16]</a></td> 
        <td>Dice contener:</td><td >$rw[17]</td>
         <td>Tipo Pago:</td><td >$rw[19]    $pendiente </td>
     </tr>";

    $planillas = explode("/", $rw[23]);

    $sql2 = "SELECT idusuarios,usu_nombre FROM usuarios where idusuarios in ($rw[24],$rw[25],$rw[26])";
    $DB->Execute($sql2);
    while ($rw2 = mysqli_fetch_row($DB->Consulta_ID)) {
        $dato[$rw2[0]] = $rw2[1];
    }

//	echo $rw[23];
    $rw[20] = str_replace(".", "", $rw[20]);
    echo "<tr bgcolor='#F3F3F3' >
          <td>Peso:</td><td >$rw[27]</td>
          <td>Volumen:</td><td > $rw[40]</td>
		  <td>Valor de Prestamo:</td><td>$ $rw[20]</td>
     </tr>";

    $sql = "SELECT `pre_porcentaje` FROM `prestamo` WHERE `pre_inicio`<'$rw[20]' and `pre_final`>='$rw[20]'";
    $DB->Execute($sql);
    $porprestamo = $DB->recogedato(0);
    $dosporcentaje = explode(" ", $porprestamo);
    if (@$dosporcentaje[1] == '%') {

        $porprestamo = ($rw[20] * @$dosporcentaje[0]) / 100;
    }
    $rw[21] = str_replace(".", "", $rw[21]);
    $seguro = (intval($rw[21]) * 1) / 100;
    $rw[21] = number_format($rw[21], 0, ".", ".");
    echo "<tr bgcolor='#FFFFFF' >
		  
		  <td>Cobro x Prestamo:</td><td>$ $porprestamo</td>
		<td>Abono:</td><td>$ $rw[22]</td>
		<td>Valor Asegurado:</td><td >$ $rw[21]</td>
      </tr>";
    $rw[9] = number_format($rw[9], 0, ".", ".");

    $seguro = number_format($seguro, 0, ".", ".");
    $rw[30] = number_format($rw[30], 0, ".", ".");

    $sql = "SELECT sum(abo_valor) FROM `abonosguias` WHERE `abo_idservicio`='$id_param' and `abo_estado`='devolucion'";
    $DB->Execute($sql);
    $devoluciong = $DB->recogedato(0);

    echo "<tr bgcolor='#F3F3F3' >
	 
		   <td>Valor Seguro:</td><td>$ $seguro</td>
		  <td>Vr Flete:</td><td>$ $rw[30]</td>
		  <td>Devoluciones:</td><td>$ $devoluciong</td>
     </tr>";





    $FB->titulo_azul1("Seguimiento Guia", 10, 0, 5);

    $sql = "SELECT `idguias`,`gui_usucreado`, `gui_fechacreacion`, `gui_usuvalida`, `gui_fechavalidacion`, `gui_usurecogida`, `gui_fecharecogida`, 
`gui_usupeso`, `gui_fechapeso`, `gui_usuvalpeso`, `gui_fechavalpeso`, `gui_ensede`, `gui_fechaensede`, `gui_validasede`, `gui_fechavalidasede`,
 `gui_encomienda`, `gui_fechaencomienda`,`gui_userecomienda`, `gui_fechaentrega`,`gui_recogio`, `gui_fecharecogio`, `gui_useredita`, `gui_fechaedita`,`gui_userdevolucion`,`gui_fechadevolucion` 
 
 FROM `guias` WHERE  `gui_idservicio`=$id_param";
    $DB->Execute($sql);
    $rw2 = mysqli_fetch_array($DB->Consulta_ID);

    echo "<tr bgcolor='#F3F3F3' >
		 <td>Creada Por:</td><td >" . $rw2[1] . "</td>
		 <td>Fecha:</td><td >" . $rw2[2] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#FFFFFF' >
		 <td>Validada Por:</td><td >" . $rw2[3] . "</td>
		 <td>Fecha:</td><td >" . $rw2[4] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#F3F3F3' >
		 <td>Asigno Recogida:</td><td >" . $rw2[5] . "</td>
		 <td>Fecha:</td><td >" . $rw2[6] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#FFFFFF' >
		 <td>Pesada Por:</td><td >" . $rw2[7] . "</td>
		 <td>Fecha:</td><td >" . $rw2[8] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#F3F3F3' >
		 <td>Peso validado Por:</td><td >" . $rw2[9] . "</td>
		 <td>Fecha:</td><td >" . $rw2[10] . "</td>
		 <td></td><td ></td>  
      </tr>";
        $trans = "SELECT ser_transporta,ser_quien_escanea,ser_fecha_escanea FROM servicios where idservicios =$id_param";
        $DB->Execute($trans);
        $transp = mysqli_fetch_array($DB->Consulta_ID);
        if ($transp[0]!="") {
            echo "<tr bgcolor='#FFFFFF' >
            <td><mark>En $transp[0]</mark></td><td ><mark>" . $transp[1] . "</mark></td>
            <td>Fecha:</td><td >$transp[2]</td>
            <td></td><td ></td>  
            </tr>";
        }

    echo "<tr bgcolor='#FFFFFF' >
		 <td>Asigno otra sede:</td><td >" . $rw2[11] . "</td>
		 <td>Fecha:</td><td >" . $rw2[12] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#F3F3F3' >
		 <td>Valido llegada sede:</td><td >" . $rw2[13] . "</td>
		 <td>Fecha:</td><td >" . $rw2[14] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#FFFFFF' >
		 <td>Asigno Operario:</td><td >" . $rw2[15] . "</td>
		 <td>Fecha:</td><td >" . $rw2[16] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#F3F3F3' >
		 <td>Edito Información:</td><td >" . $rw2[21] . "</td>
		 <td>Fecha:</td><td >" . $rw2[22] . "</td>
		 <td></td><td ></td>  
      </tr>";

    echo "<tr bgcolor='#FFFFFF' >
		 <td>Recogio Paquete:</td><td >" . $rw2[19] . "</td>
		 <td>Fecha:</td><td >" . $rw2[20] . "</td>
		 <td></td><td ></td>  
      </tr>";

    if ($rw[38] == 11) {
        echo "<tr bgcolor='#F3F3F3' >
			<td>Guia Devuelta:</td><td >" . $rw2[17] . "</td>
			<td>Fecha:</td><td >" . $rw2[18] . "</td>
			<td></td><td ></td>  
			 </tr>";
    } else {
        echo "<tr bgcolor='#F3F3F3' >
		 <td>Entrego Encomienda:</td><td >" . $rw2[17] . "</td>
		 <td>Fecha:</td><td >" . $rw2[18] . "</td>
		 <td></td><td ></td>  
			</tr>";
    }

    echo "<tr bgcolor='#FFFFFF' >
		 <td>Recibio Devolucion:</td><td >" . $rw2[23] . "</td>
		 <td>Fecha:</td><td >" . $rw2[24] . "</td>
		 <td></td><td ></td>  
      </tr>";

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);

    //echo$Pagada = "SELECT `idcuentaspromotor`,`cue_idoperador`, `cue_pordeclarado`, `cue_vrdeclarado`, `cue_valorflete`, `cue_abono`, `cue_tipopago`, `cue_fecha`, `cue_pendientecobrar`,cue_idoperentrega,cue_transferencia,ser_pendientecobrar,ser_numerofactura,cue_idservicio FROM `cuentaspromotor` inner join servicios on cue_idservicio=idservicios WHERE (cue_validadoentrega='1' or cue_validado='1') and cue_numeroguia='$rw[23]' order by ser_guiare ASC;";

    $Pagada = "SELECT `idcuentaspromotor`,`cue_idoperador`, `cue_pordeclarado`, `cue_vrdeclarado`, `cue_valorflete`, `cue_abono`, `cue_tipopago`, `cue_fecha`, `cue_pendientecobrar`,cue_idoperentrega,cue_transferencia,ser_pendientecobrar,ser_numerofactura,cue_idservicio FROM `cuentaspromotor` inner join servicios on cue_idservicio=idservicios WHERE  cue_numeroguia='$rw[23]' order by ser_guiare ASC;";
    $DB->Execute($Pagada);
    while ($pagada1 = mysqli_fetch_row($DB->Consulta_ID)) {
        $idcobro= $pagada1[9];
        $transfe= $pagada1[10];


        if($pagada1[10]!='' and $pagada1[10]!='Efectivo' and $pagada1[11]!=6){

           $sql = "Select pag_userverifica from pagoscuentas where pag_idservicio='$pagada1[13]'";
           $DB1->Execute($sql);
           $verificado = $DB1->recogedato(0);
           if($verificado ==''){
               $Pagotrans=$pagada1[10]."<br>"."Sin Verificar";
               $color7="style=background-color:yellow";
           }else{
           $Pagotrans=$pagada1[10]."<br>"."Verificado";
   
           }
   
           // $recogida='';
           // $entregas='';
           // $xcobrar=0;
           // $entregas3=$entregas3+$faltante;
           // $faltante=0;
       }elseif($pagada1[11]==6){
   
           // $recogida='';
           // $entregas='';
           // $xcobrar=0;
           // $entregas3=$entregas3+$faltante;
           // $faltante=0;
   
           $Pagotrans="Pago con Factura Externa"
           ."<br> #".$pagada1[12];
   
       }else{
   
           $Pagotrans='Efectivo';
   
   
   
       }
    }

    $Pagada1 = "SELECT usu_nombre FROM `usuarios`  WHERE idusuarios='$idcobro'";
    $DB->Execute($Pagada1);
    while ($pagada2 = mysqli_fetch_row($DB->Consulta_ID)) {
        $nomcobro= $pagada2[0];
    }

    $FB->titulo_azul1("Comentarios", 10, 0, 5);

    $sqlP = "SELECT desc_valida_guia_llega,img_pieza_llega,numeropieza FROM piezasguia WHERE numeroguia='$rw[23]'";
    $DB->Execute($sqlP);
    while ($infPiez = mysqli_fetch_row($DB->Consulta_ID)){
        echo "<tr bgcolor='#FFFFFF' >";
        if ($infPiez[1]!="") {
           echo" <td>Pieza: " . $infPiez[2] . "</td>
            <td>" . $infPiez[0] . "</td>";
            if ($infPiez[1]!="") {
                echo "<td><a href='imgServicios/" . $infPiez[1] . "' target='_blank'>ver</a></td>";
            }else {
                echo "<td></td>";
            }
        
        }
        echo "</tr>";

    }



    $pagoconta="";
    $pagoalco="";
    $FB->titulo_azul1("Pago", 10, 0, 5);
    if ($idcobro != "") {
        if ($rw[19]=="Contado") {

            if ($rw2[19]!="") {
                $pagoconta="✅";
            }
            echo "<tr bgcolor='#F3F3F3' >
            
            <td>Medio pago</td><td $color7>$Pagotrans</td>
            <td>Estado del pago</td><td>$pagoconta</td>
            <td>Recibe dinero::</td><td style='Background-color:#0FF165'> $rw2[19]</td>

            </tr>";
            
        }else if($rw[19]=="Al Cobro"){

            if ($rw2[17]!="") {
                $pagoalco="✅";
            }
            echo "<tr bgcolor='#F3F3F3' >
            
            <td>Medio pago</td><td $color7>$Pagotrans</td>
            <td>Estado del pago</td><td>$pagoalco</td>
            <td>Recibe dinero:</td><td style='Background-color:#0FF165'> $rw2[17]</td>

            </tr>";
        }
        

    }


} else if ($tabla == "Peso") {
    $sql = "SELECT `ser_peso`,`ser_valor`,`ser_pendientecobrar`,`ser_clasificacion`,ser_volumen,ser_guiare,ser_descripcion,ser_ciudadentrega FROM `servicios` WHERE `idservicios`=$id_param";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);
    $clasificacion = 0;
    if ($rw[3] == 1 and $rw[2] == 0) {

        $clasificacion = 1;
    } else if ($rw[3] == 2) {
        $clasificacion = 2;
    }
    if ($nivel_acceso != 1) {
        if ($rw[0] != '') {
            $valor = "min=$rw[0]";
        } else {
            $valor = "";
        }
        if ($rw[4] != '') {
            $valor2 = "min=$rw[4]";
        } else {
            $valor2 = "";
        }
    } else {
        $valor = "";
        $valor2 = "";
    }

    $FB->llena_texto("PESO KG:", 1, 123, $DB, "", "$valor", $rw[0], 1, 1);
    $FB->llena_texto("VOLUMEN:", 4, 125, $DB, "", "$valor2", $rw[4], 1, 0);
    $FB->llena_texto("# GUIA:", 6, 1, $DB, "", "", "$rw[5]", 1, 0);
    $FB->llena_texto("FOTO GUIA", 10, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("ESTADO PAQUETE:", 2, 9, $DB, "", "", "$rw[6]", 1, 2);

    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("clasificacion", 1, 13, $DB, "", "", $clasificacion, 5, 0);
    $FB->llena_texto("caso", 1, 13, $DB, "", "", 1, 5, 0);
    $FB->llena_texto("param5", 1, 13, $DB, "", "", $id_param2, 5, 0);
    $FB->llena_texto("param16", 1, 13, $DB, "", "", $rw[7], 5, 0);
} else if ($tabla == "Fotoguia") {
    $dato = explode("_", $_REQUEST["ide"]);
    $guia = $dato[0];
    $tipo = $dato[1];

    $FB->llena_texto("FOTO GUIA", 10, 60, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param6", 1, 13, $DB, "", "", "$guia", 5, 0);
    $FB->llena_texto("param7", 1, 13, $DB, "", "", "$tipo", 5, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
}else if ($tabla == "FotoRemesa") {

    $tipo = $_REQUEST["ide"];

    $FB->llena_texto("Foto Remesa", 10, 60, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param37", 1, 13, $DB, "", "", "$tipo", 5, 0);
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    
} else if ($tabla == "validapeso"){

    $sql = "SELECT `ser_peso`,`ser_valor`,`ser_pendientecobrar`,`ser_clasificacion`,ser_volumen,ser_descripcion,ser_guiare,ser_ciudadentrega,ser_telefonocontacto,cli_telefono FROM `serviciosdia` WHERE `idservicios`=$id_param";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);
    if ($nivel_acceso != 1) {
        if ($rw[0] != '') {
            $valor = "min=$rw[0]";
        } else {
            $valor = "";
        }
        if ($rw[4] != '') {
            $valor2 = "min=$rw[4]";
        } else {
            $valor2 = "";
        }
    } else {
        $valor = "";
        $valor2 = "";
    }



    $slqs = "SELECT idimagenguias,ima_ruta,ima_tipo,ima_fecha FROM imagenguias WHERE ima_idservicio=$id_param and ima_tipo='Recogida'  ";
    $DB1->Execute($slqs);
    $imgu=mysqli_fetch_row($DB1->Consulta_ID);



    $FB->llena_texto("PESO KG:", 1, 123, $DB, "", "$valor", $rw[0], 1, 1);
    $FB->llena_texto("VOLUMEN:", 4, 125, $DB, "", "$valor2", $rw[4], 1, 0);
    $FB->llena_texto("ESTADO PAQUETE:", 2, 82, $DB, $estadopaquete, "", $rw[5], 1, 0);
    $FB->llena_texto("# GUIA:", 6, 1, $DB, "", "", $rw[6], 1, 0);
    $FB->llena_texto("FOTO GUIA", 10, 60, $DB, "", "", "$idmagen", 1, 0);
    $FB->llena_texto("VERIFICADO:", 3, 5, $DB, "", "", "", 1, 1);

    if ($rw[3]==2) {
        $slqc = "SELECT `idrelsercre`, `idservicio`, `rel_nom_credito`  FROM `rel_sercre`  WHERE idservicio=$id_param";
        $DB1->Execute($slqc);
        $cre=mysqli_fetch_row($DB1->Consulta_ID);
        
        $FB->titulo_azul1("Credito	", 8, 0, 5);
        echo "<tr><td>Cedito</td><td><input type='radio' name='credito' id='credito' align='left'  value='1' checked  disabled >SI  $cre[2]</td></tr>";
		
    }
    

    if ($rw[3] == 1 and $rw[2] == 0) {
        $clasificacion = 1;
    } else if ($rw[3] == 2) {
        $clasificacion = 2;
    } else {
        $clasificacion = 0;
    }





    $sqlimg="SELECT ser_img_recog,ser_img_entre from servicios where idservicios=$id_param ";
    $DB1->Execute($sqlimg); 
    $img=mysqli_fetch_row($DB1->Consulta_ID);
    if ($img[0]!="") {
    //  echo "<td align='center' >";
    //  		echo "<a href='imgServicios/$img[0]' target='_blank'>Ver</td>";
    }else {
    // echo "<td align='center' >";
    // echo "</td>";
    }
    $dirguia="$imgu[1]&vis=adm";
    echo'<div class="popup-content">
    <h2>Galería de imágenes</h2>';
        echo '<div class="thumbnail-container">';
        if ($imgu[3]<"2024-05-30") {
            $colorfoto="";
            // $confotor="<a href='https://b9e4-190-25-33-50.ngrok-free.app/SistemaTransmillas/$recogidag' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
            echo'<img src="imagesguias/_Recogida.jpg" alt="Miniatura 1" class="thumbnail" onclick="abrirVentana(\''.$imgu[1].'\')">';
            
        }elseif ($imgu[3]<="2025-02-13") {
            $colorfoto="";
            // $confotor="<a href='$recogidag' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
            echo'<img src="imagesguias/_Recogida.jpg" alt="Miniatura 1" class="thumbnail" onclick="abrirVentana(\''.$imgu[1].'\')">';
            
        }else{
            $colorfoto="";
            // $confotor="<a href='$recogidag&vis=adm'' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
            echo'<img src="imagesguias/_Recogida.jpg" alt="Miniatura 1" class="thumbnail" onclick="abrirVentana(\''.$dirguia.'\')">';
            
        }
        echo'<img src="imgServicios/'.$img[0].'" alt="Miniatura 2" class="thumbnail" onclick="ampliarImagen(this)">
        </div>';
    echo"</div>";
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $id_param, 5, 0);
    $FB->llena_texto("clasificacion", 1, 13, $DB, "", "", $clasificacion, 5, 0);
    $FB->llena_texto("caso", 1, 13, $DB, "", "", 2, 5, 0);
    $FB->llena_texto("param5", 1, 13, $DB, "", "", $id_param2, 5, 0);
    $FB->llena_texto("param16", 1, 13, $DB, "", "", $rw[7], 5, 0);
} else if ($tabla == "descargaoficina") {
    $sql = "SELECT `ser_peso`,`ser_valor`,`ser_pendientecobrar`,`ser_clasificacion`,ser_volumen,ser_descripcion,ser_guiare,ser_ciudadentrega,ser_destinatario,ser_direccioncontacto,cli_idciudad,ser_idservicio,ser_estentrega FROM `servicios`
	inner join rel_sercli  on idservicios=ser_idservicio  inner join clientesservicios on idclientesdir=ser_idclientes
	 WHERE `ser_guiare`='$id_param'";
    $DB->Execute($sql);
    $rw = mysqli_fetch_array($DB->Consulta_ID);
    if ($nivel_acceso != 1) {
        if ($rw[0] != '') {
            $valor = "min=$rw[0]";
        } else {
            $valor = "";
        }
        if ($rw[4] != '') {
            $valor2 = "min=$rw[4]";
        } else {
            $valor2 = "";
        }
    } else {
        $valor = "";
        $valor2 = "";
    }
    $rw[9] = str_replace("&", " ", $rw[9]);
    $FB->llena_texto("CIUDAD:", 17, 2, $DB, "(SELECT `idciudades`,`ciu_nombre` FROM ciudades where idciudades=$rw[7])", "", "$rw[7]", 1, 0);
    $FB->llena_texto("DESTINATARIO:", 18, 1, $DB, "", "", "$rw[8]", 4, 0);
    $FB->llena_texto("DIRECCION:", 19, 1, $DB, "", "", "$rw[9]", 4, 0);
    $FB->llena_texto("PESO KG:", 1, 123, $DB, "", "$valor", $rw[0], 1, 'min=1');
    $FB->llena_texto("VOLUMEN:", 4, 125, $DB, "", "$valor2", $rw[4], 1, 0);
    $FB->llena_texto("ESTADO PAQUETE:", 12, 82, $DB, $estadopaquete, "", @$rw[5], 1, 1);
    $FB->llena_texto("# GUIA:", 6, 1, $DB, "", "", $rw[6], 1, 0);

    $slqs = "SELECT idimagenguias,ima_ruta,ima_tipo FROM imagenguias WHERE ima_idservicio=$rw[11] and ima_tipo='Recogida'  ";
    $DB1->Execute($slqs);
    $imgu=mysqli_fetch_row($DB1->Consulta_ID);
    echo$sqlimg="SELECT ser_img_recog,ser_img_entre from servicios where idservicios=$rw[11] ";
    $DB1->Execute($sqlimg); 
    $img=mysqli_fetch_row($DB1->Consulta_ID);
    if ($img[0]!="") {
    //  echo "<td align='center' >";
    //  		echo "<a href='imgServicios/$img[0]' target='_blank'>Ver</td>";
    }else {
    // echo "<td align='center' >";
    // echo "</td>";
    }
    $dirguia="$imgu[1]&vis=adm";
    echo'<div class="popup-content">
    <h2>Galería de imágenes</h2>';
        echo '<div class="thumbnail-container">
        <img src="imagesguias/_Recogida.jpg" alt="Miniatura 1" class="thumbnail" onclick="abrirVentana(\''.$dirguia.'\')">
        <img src="imgServicios/'.$img[0].'" alt="Miniatura 2" class="thumbnail" onclick="ampliarImagen(this)">
        </div>';
    echo"</div>";

    if ($rw[12] == "ENTREGADO") {
        echo "<div class='alert alert-danger'>
		<strong> ¡ESTA GUIA YA FUE ENTREGADA! </strong>
	  ";
      echo"</div>";
    }
    if ($rw[3] == 1 and $rw[2] == 0) {
        $clasificacion = 1;
    } else if ($rw[3] == 2) {
        $clasificacion = 2;
    } else {
        $clasificacion = 0;
    }
    $FB->llena_texto("id_param", 1, 13, $DB, "", "", $rw[11], 5, 0);
    $FB->llena_texto("id_param2", 1, 13, $DB, "", "", $rw[11], 5, 0);
    $FB->llena_texto("clasificacion", 1, 13, $DB, "", "", $clasificacion, 5, 0);
    $FB->llena_texto("caso", 1, 13, $DB, "", "", 2, 5, 0);
    $FB->llena_texto("param5", 1, 13, $DB, "", "", $rw[10], 5, 0);
    $FB->llena_texto("param3", 1, 13, $DB, "", "", 1, 5, 0);
    $FB->llena_texto("param16", 1, 13, $DB, "", "", $rw[7], 5, 0);
} else if ($tabla == "Usuarios-Roles") {
    $FB->abre_form("form1", "nuevo_adminok.php", "post");
    $slqs = "SELECT usu_nombre FROM usuarios WHERE usu_mail='$id_param' ";
    $DB1->Execute($slqs);
    $eventos = $DB1->recogedato(0);
    ?>
    <div class="modal-body"><div class="form-group">
            <div class="input-group"><h4><?php echo utf8_encode($eventos); ?></h4></div>
                    <?php
                    $sql = "SELECT idroles, rol_nombre FROM roles ORDER BY rol_nombre ";
                    $DB->Execute($sql);
                    echo "<table width='100%' class='Intabla'><tr>";
                    $va = 0;
                    while ($rw1 = mysqli_fetch_row($DB->Consulta_ID)) {
                        if ($va == 5) {
                            $va = 0;
                            echo "</tr><tr>";
                        } $va++;
                        $slqs = "SELECT COUNT(*) FROM usuarios WHERE usu_mail='$id_param' AND roles_idroles='$rw1[0]'";
                        $DB1->Execute($slqs);
                        if ($DB1->recogedato(0) > 0) {
                            $conss1 = "checked";
                        } else {
                            $conss1 = "";
                        }
                        echo "<td width='2%'><input type='checkbox' id='roles' name='roles[]' style='width:35px;' value='$rw1[0]' $conss1></td><td width='18%'>" . utf8_encode($rw1[1]) . "</td>";
                    }
                    echo "</table>";
                    ?>
        </div>
        <?php
        $FB->llena_texto("id_param", 1, 13, $DB, "", "", $id_param, 5, 0);

}else if ($tabla == "detallesinfacturados") {

        $FB->titulo_azul1("GUIA", 1, 0, 5);
        $FB->titulo_azul1("CREDITO", 1, 0, 0);

        $cadena = $_REQUEST["ide"];
        
        $datos = explode("|", $cadena);
        $conde3 ="";
        $conde4 ="";
        $fechainicio=$datos[0]; 
        $fechaactual=$datos[1]; 
        $param3=$datos[2]; 
        $param6=$datos[3]; 
        if($param3!=''){ $conde3 =" and (rel_nom_credito like '%$param3%')";  }

        if($param6=='Sin Facturar'){
            $conde4=' and ser_numerofactura is null';
        }elseif($param6=='Facturados'){
            $conde4=' and ser_numerofactura>=1';
        }else{
            $conde4='';	
        }

        $sql = "SELECT idservicios,ser_guiare FROM servicios s inner join rel_sercli on idservicios=ser_idservicio inner join clientesservicios on idclientesdir=ser_idclientes inner join ciudades on idciudades=cli_idciudad inner join rel_sercre rs on rs.idservicio=idservicios left join facturascreditos on fac_numeroref=ser_numerofactura 
        WHERE ser_clasificacion=2 and ser_estado>=3 and ser_estado!=100 and (ser_numerofactura='' or ser_numerofactura is null ) and (rel_nom_credito like '%$id_param%') and  date(ser_fecharegistro)>='$fechainicio' and  date(ser_fecharegistro)<='$fechaactual'  $conde3 $conde4 ORDER BY rel_nom_credito ASC;";
    
        $DB1->Execute($sql);
        $va = 0;
        $sumatotal = 0;
        while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
            $id_p = $rw1[0];
            $va++;
            $p = $va % 2;
            if ($p == 0) {
                $color = "#FFFFFF";
            } else {
                $color = "#EFEFEF";
            }
    
            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
            echo "<td>" . $rw1[0] . "</td>
                  <td>" . $rw1[1] . "</td>
                ";
            $sumatotal++;
            echo "</tr>";
        }
        $FB->titulo_azul1("TOTAL", 1, 0, 5);
        $FB->titulo_azul1(" $sumatotal", 1, 0, 0);
    }else if ($tabla == "detallesinfacturados") {

        $FB->titulo_azul1("GUIA", 1, 0, 5);
        $FB->titulo_azul1("CREDITO", 1, 0, 0);

        $cadena = $_REQUEST["ide"];
        
        $datos = explode("|", $cadena);
        $conde3 ="";
        $conde4 ="";
        $fechainicio=$datos[0]; 
        $fechaactual=$datos[1]; 
        $param3=$datos[2]; 
        $param6=$datos[3]; 
        if($param3!=''){ $conde3 =" and (rel_nom_credito like '%$param3%')";  }

        if($param6=='Sin Facturar'){
            $conde4=' and ser_numerofactura is null';
        }elseif($param6=='Facturados'){
            $conde4=' and ser_numerofactura>=1';
        }else{
            $conde4='';	
        }

        $sql = "SELECT idservicios,ser_guiare FROM servicios s inner join rel_sercli on idservicios=ser_idservicio inner join clientesservicios on idclientesdir=ser_idclientes inner join ciudades on idciudades=cli_idciudad inner join rel_sercre rs on rs.idservicio=idservicios left join facturascreditos on fac_numeroref=ser_numerofactura 
        WHERE ser_clasificacion=2 and ser_estado>=3 and ser_estado!=100 and (ser_numerofactura='' or ser_numerofactura is null ) and (rel_nom_credito like '%$id_param%') and  date(ser_fecharegistro)>='$fechainicio' and  date(ser_fecharegistro)<='$fechaactual'  $conde3 $conde4 ORDER BY rel_nom_credito ASC;";
    
        $DB1->Execute($sql);
        $va = 0;
        $sumatotal = 0;
        while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
            $id_p = $rw1[0];
            $va++;
            $p = $va % 2;
            if ($p == 0) {
                $color = "#FFFFFF";
            } else {
                $color = "#EFEFEF";
            }
    
            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
            echo "<td>" . $rw1[0] . "</td>
                  <td>" . $rw1[1] . "</td>
                ";
            $sumatotal++;
            echo "</tr>";
        }
        $FB->titulo_azul1("TOTAL", 1, 0, 5);
        $FB->titulo_azul1(" $sumatotal", 1, 0, 0);

    } else if ($tabla == "datosdefactura") {

                    
            $FB->titulo_azul1("#",1,0,7); 
            $FB->titulo_azul1("Nit",1,0,0); 
            $FB->titulo_azul1("Cedula",1,0,0); 
            $FB->titulo_azul1("Razon Social",1,0,0); 
            $FB->titulo_azul1("Telefonos",1,0,0); 
            $FB->titulo_azul1("Correo",1,0,0); 
            $FB->titulo_azul1("Direcci&oacute;n Radicacion",1,0,0);
            $FB->titulo_azul1("Fecha  Facturacion:",1,0,0);
            $FB->titulo_azul1("Fecha  Corte:",1,0,0);
            $FB->titulo_azul1("Novedades de Factura:",1,0,0);


           $sql="SELECT `idhojadevida`,hoj_nit,`hoj_telefono1`, `hoj_telefono2`,   `hoj_email`,hoj_cedula,hoj_razonsocial,hoj_direccionrf,hoj_fechanaradicacion,hoj_fechanacorte,hoj_novedadesfactura FROM `hojadevidacliente` left join creditos on idcreditos=hoj_clientecredito where cre_nombre='$id_param' and hoj_estado='Activo' ORDER BY  hoj_nombre asc ";
            // echo $sql="SELECT   `cont_celular`, `cont_correo`FROM `contactofacturacion` WHERE  cont_idhojavida=$idhojadevida";

            $DB->Execute($sql); $va=(($compag-1)*$CantidadMostrar); 
                while($rw1=mysqli_fetch_row($DB->Consulta_ID))
                {
                    $id_p=$rw1[0];
                    $va++; $p=$va%2;
                    if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
                    echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                    //if($rw1[1]==0){$rw1[1]='NO';}
                    $telefonos=$rw1[2]." - ".$rw1[3];
                    echo "<td>".$va."</td>
                    <td>".$rw1[1]."</td>
                    <td>".$rw1[5]."</td>
                    <td>".$rw1[6]."</td>
                    <td>".$telefonos."</td>
                    <td>".$rw1[4]."</td>
                    <td>".$rw1[7]."</td>
                    <td>".$rw1[8]."</td>
                    <td>".$rw1[9]."</td>
                    <td>".$rw1[10]."</td>
                    ";

                    $idhojadevida=$rw1[0];
                }

            $FB->titulo_azul1("CONTACTO FACTURACION",9,0,7);  
                
            $FB->titulo_azul1("Nombre del Contacto",1,0,7); 
            $FB->titulo_azul1("Telefono 1",1,0,0); 
            $FB->titulo_azul1("Ext 1",1,0,0); 
            $FB->titulo_azul1("Telefono 2",1,0,0); 
            $FB->titulo_azul1("Ext 2",1,0,0); 
            $FB->titulo_azul1("Celular ",1,0,0); 
            $FB->titulo_azul1("Correo",1,0,0); 

            $sql="SELECT `idcontactofacturacion`, `con_nombre`, `cont_telefono1`, `cont_ext1`, `cont_telefono2`, `cont_ext2`, `cont_celular`, `cont_correo`, `cont_fecharegistra` FROM `contactofacturacion` WHERE  cont_idhojavida=$idhojadevida";

            $DB->Execute($sql); 
            $va=0; 

                while($rw1=mysqli_fetch_row($DB->Consulta_ID))
                {
                            $id_p=$rw1[0];
                            $va++; $p=$va%2;
                            if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
                            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
                            echo "<td>".$rw1[1]."</td>
                            <td>".$rw1[2]."</td>
                            <td>".$rw1[3]."</td>
                            <td>".$rw1[4]."</td>
                            <td>".$rw1[5]."</td>
                            <td>".$rw1[6]."</td>
                            <td>".$rw1[7]."</td>";		
                }
                
                $FB->titulo_azul1(" ------ ",1,0,10); 
                $FB->titulo_azul1(" ------",1,0,0); 
                $FB->titulo_azul1(" ------",1,0,0); 
                $FB->titulo_azul1(" ------",1,0,0); 
                $FB->titulo_azul1(" ------",1,0,0); 
                $FB->titulo_azul1(" ------",1,0,0); 
                $FB->titulo_azul1(" ------",1,0,0); 

    }else if ($tabla == "Trabaja con:") {
        $fecha = $_REQUEST["ide"];
        $fechaactual = date("Y-m-d");
        // Usando explode
        $partes = explode(" _ ", $fecha);

        // Antes del guion bajo
        $fecha = $partes[0];

        // Después del guion bajo
        $id_usuario = $partes[1];


        $FB->llena_texto("Sede:", 35, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 )", "cambio_ajax2(this.value, 100, \"llega_sub1\", \"param33\", 1, $idciudad)", "", 17, 1);
        $FB->llena_texto("Operario:", 33, 444, $DB, "llega_sub1", "", "", 4, 1);
    

        $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
        $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
        $FB->llena_texto("param4", 1, 13, $DB, "", "", $id_usuario, 5, 0);
    
    }else if ($tabla == "Brochur") {
        $fecha = $_REQUEST["ide"];
        $fechaactual = date("Y-m-d");

        echo'<div class="input-container">';
        echo'<input disabled type="text" class="link-input" id="linkInput" value="https://www.transmillas.com/brochure">';
        echo'<a href="#" class="copy-button" onclick="copyLink(event)"><i class="mdi mdi-content-copy"></i>Copiar</a>';
        echo'</div>';
        echo'<div class="container">';
        // echo'<i class="mdi mdi-image image-icon"></i><br>';
        echo'<a href="images/Brochur transmillas.png" class="download-button" download="BrochurTransmillas.jpg">';
        echo'<i class="mdi mdi-download"></i>Descargar QR </a>';
        echo'</div>';

        $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
        $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
    
    }else if ($tabla == "Agregar carpeta") {
        $fecha = $_REQUEST["ide"];
        $fechaactual = date("Y-m-d");

        $FB->llena_texto("Nombre", 2, 1, $DB, "", "", $rw[5], 1, 0);
        $FB->llena_texto("Sede :",3,2,$DB,"(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 and sed_principal='si' $conde2  order by sed_nombre asc  )", "cambio_ajax2(this.value, 16, \"llega_sub1\", \"param33\", 1, 0)", "$param35",1, 0);
        if ($nivel_acceso==1) {
            $FB->llena_texto("Rol:",4,2,$DB,"SELECT idroles, rol_nombre FROM roles ORDER BY rol_nombre","","",2,0);

        }else{
            $FB->llena_texto("Rol:",4,2,$DB,"SELECT idroles, rol_nombre FROM roles where idroles='$nivel_acceso' ORDER BY rol_nombre","","$nivel_acceso",2,0);

        }
        // $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
        // $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
    
    }else if ($tabla == "Agregar un documento") {
        $fecha = $_REQUEST["ide"];
        $fechaactual = date("Y-m-d");

        $FB->llena_texto("Sede :",3,2,$DB,"(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 and sed_principal='si' $conde2  order by sed_nombre asc  )", "cambio_ajax2(this.value, 106, \"llega_sub1\", \"param1\", 1, 0)", "$param3",1, 0);
         $FB->llena_texto("Carpeta:", 1, 444, $DB, "llega_sub1", "", "", 17, 1);
         $FB->llena_texto("Nombre del archivo:", 2, 1, $DB, "", "", "", 2, 0);
         $FB->llena_texto("Cargar archivo", 3, 6, $DB, "", "", "", 1, 0);
         $FB->llena_texto("Fecha Renovacion:", 4, 10, $DB, "", "", "$fecharegistro", 4, 0);
         
        // $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
        // $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
    
    }else if ($tabla == "Ver carpeta") {
        $fecha = $_REQUEST["ide"];
        $fechaactual = date("Y-m-d");
        $FB->titulo_azul1("",1,0,7); 
        $FB->titulo_azul1("Nombre",1,0,0);
        $FB->titulo_azul1("Fecha renovacion",1,0,0); 
        $FB->titulo_azul1("Estado",1,0,0); 
        $FB->titulo_azul1("Eliminar",1,0,0); 
        // $FB->titulo_azul1("Conductor",1,0,0); 
        $sql="SELECT `doctid`, `doct_nombre`, `doct_id_carpeta`, `doct_archivo`, `doct_fechavence`,doct_renovado FROM `documentosTransmillas` WHERE doct_id_carpeta='$id_param'";

        $DB->Execute($sql);  
        while($rw1=mysqli_fetch_row($DB->Consulta_ID))
        {
            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
        echo "<td><input type='checkbox'  onchange='selecionado($rw1[0])' class='checkbox' id='".$rw1[0]."s' value='$rw1[0]'></td>";
            
            echo "<td><a href='imgDocTransmi/$rw1[3]' target='_blank'><img src='images/archivo.png'   style='width: 25px; height: 30px;'>$rw1[1]</a></td>
            <td>$rw1[4]</td>";
            echo "

            <td align='left' ><select class='trans'  name='param21' id='param21' onchange='renovar(this.value,$rw1[0]);' >";
            if ($rw1[5]=="Renovado") {
                echo "<option  value='No renovado'>No renovado</option>";
                echo "<option  value='Renovado' selected>Renovado</option>";
            }else {
                echo "<option  value='No renovado' selected>No renovado</option>";
                echo "<option  value='Renovado'>Renovado</option>";
            }


            echo "</select></td>";    
            if($nivel_acceso==1){
                $DB->edites($rw1[0], "DochvTrans", 2, $condecion);
            }
            echo"</tr>";
        }
        //  $FB->llena_texto("Carpeta:", 1, 2, $DB, "(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 )", "cambio_ajax2(this.value, 100, \"llega_sub1\", \"param33\", 1, $idciudad)", "", 17, 1);
        //  $FB->llena_texto("Nombre del archivo:", 2, 1, $DB, "", "", "", 2, 0);
        //  $FB->llena_texto("Cargar archivo", 3, 6, $DB, "", "", "", 1, 0);
        // //  $FB->llena_texto("Renovacion:", 9, 1, $DB, "", "", "", 2, 0);
        //  $FB->llena_texto("Fecha Renovacion:", 4, 10, $DB, "", "", "$fecharegistro", 4, 0);
        // // $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
        // // $FB->llena_texto("param3", 1, 13, $DB, "", "", $fecha, 5, 0);
    
    }else if ($tabla == "Enviar correo"){
        // $myArray = $_REQUEST["ide"];
        print_r($myArray);
        $fechaactual = date("Y-m-d");
        $FB->titulo_azul1("Archivos que se enviaran",1,0,7); 

                // Verifica si 'ide' está presente en la solicitud
        if (isset($_GET['ide'])) {
            // Decodifica el parámetro 'ide' de JSON a un array PHP
            $myArray = json_decode(urldecode($_GET['ide']), true);

            // Verifica que $myArray es realmente un array
            if (is_array($myArray)) {
                // Imprime la estructura del array para depuración
                // echo '<pre>';
                // var_dump($myArray);
                // echo '</pre>';

                // Genera la tabla
                // echo '<table border="1">';
                foreach ($myArray as $dato) {
                    $sql2="SELECT `doctid`, `doct_nombre`, `doct_id_carpeta`, `doct_archivo`, `doct_fechavence` FROM `documentosTransmillas` WHERE `doctid` = '$dato'";
                    $DB1->Execute($sql2); 
                    // $iddoc=$DB1->recogedato(0);
                    $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                    echo "<tr><td><img src='images/archivo.png'   style='width: 25px; height: 30px;'>$rw1[1]</td></tr>";
                    if ($rw1) {
                        $fileNames[] = $rw1[3]; // Agrega el resultado al nuevo array
                    }
                }
                echo '</table>';
                
            } else {
                // echo "El dato recibido no es un array.";
            }
        } else {
            // echo "No se recibió ningún dato.";
        }
        $FB->llena_texto("Email Destinatario:", 2, 1, $DB, "", "", "", 2, 0);
        $FB->llena_texto("Cargar formato", 3, 6, $DB, "", "", "", 1, 0);
        
        echo '<a class="icon-button file-button" href="#" onclick=\'sendEmail(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';
        echo '<div id="loading">
        <img src="images/loading.gif" alt="Cargando..."></div>';
// <button type='button' class='icon-button file-button' onclick='pop_dis16(\"$id_p\",\"Agregar un documento\",\"$rw1[5]\")'><i class='fas fa-file'></i>Excel</button>
        








    
    }else if ($tabla == "Enviar correo"){
        // $myArray = $_REQUEST["ide"];
        print_r($myArray);
        $fechaactual = date("Y-m-d");
        $FB->titulo_azul1("Archivos que se enviaran",1,0,7); 

                // Verifica si 'ide' está presente en la solicitud
        if (isset($_GET['ide'])) {
            // Decodifica el parámetro 'ide' de JSON a un array PHP
            $myArray = json_decode(urldecode($_GET['ide']), true);

            // Verifica que $myArray es realmente un array
            if (is_array($myArray)) {
                // Imprime la estructura del array para depuración
                // echo '<pre>';
                // var_dump($myArray);
                // echo '</pre>';

                // Genera la tabla
                // echo '<table border="1">';
                foreach ($myArray as $dato) {
                    $sql2="SELECT `doctid`, `doct_nombre`, `doct_id_carpeta`, `doct_archivo`, `doct_fechavence` FROM `documentosTransmillas` WHERE `doctid` = '$dato'";
                    $DB1->Execute($sql2); 
                    // $iddoc=$DB1->recogedato(0);
                    $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                    echo "<tr><td><img src='images/archivo.png'   style='width: 25px; height: 30px;'>$rw1[1]</td></tr>";
                    if ($rw1) {
                        $fileNames[] = $rw1[3]; // Agrega el resultado al nuevo array
                    }
                }
                echo '</table>';
                
            } else {
                // echo "El dato recibido no es un array.";
            }
        } else {
            // echo "No se recibió ningún dato.";
        }
        $FB->llena_texto("Email Destinatario:", 2, 1, $DB, "", "", "", 2, 0);
        $FB->llena_texto("Cargar formato", 3, 6, $DB, "", "", "", 1, 0);
        
        echo '<a class="icon-button file-button" href="#" onclick=\'sendEmail(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';
        echo '<div id="loading">
        <img src="images/loading.gif" alt="Cargando..."></div>';
// <button type='button' class='icon-button file-button' onclick='pop_dis16(\"$id_p\",\"Agregar un documento\",\"$rw1[5]\")'><i class='fas fa-file'></i>Excel</button>
        








    
    }else if ($tabla == "Preguntas y respuestas"){
        // $myArray = $_REQUEST["ide"];
        print_r($myArray);
        $fechaactual = date("Y-m-d");
        $FB->titulo_azul1("Formuario",10,0, 5);  
        $FB->llena_texto("Pregunta:", 2, 1, $DB, "", "", "", 1, 0);
        $FB->llena_texto("Respuesta:", 3, 1, $DB, "", "", "", 4, 0);
        // $FB->llena_texto("Cargar formato", 3, 6, $DB, "", "", "", 1, 0); 
        echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'guardarPregunta(); return false;\'>Guardar</a></td></tr>';
        echo '<div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="buscador" placeholder="Buscar...">
        </div>';
        // $FB->titulo_azul1("Pregunta",1,0,7);
        // $FB->titulo_azul1("Respuesta",1,0,0);

        echo'<table id="tablaPreguntas" class="tabla-preguntas">
        <thead>
                <tr>
                    <th>Pregunta</th>
                    <th>Respuesta</th>
                    <th>Eliminar</th>
                </tr>
        </thead>
            <tbody>';
            // $FB->titulo_azul1("Conductor",1,0,0); 
        $sql="SELECT  `pregunta`, `respuesta`,idpreres FROM `preguntas_transmillas` WHERE pre_tipodato=1";

        $DB->Execute($sql);  
        while($rw1=mysqli_fetch_row($DB->Consulta_ID))
        {
        echo "<tr>";
            echo "<td>$rw1[0]</td>";
            echo "<td>$rw1[1]</td>";
            // echo "<td>🗑️</td>";
            if($nivel_acceso==1){
                $DB->edites($rw1[2], "preres", 2, $condecion);
            }
        echo"</tr>";
        }
            
        echo'   </tbody>
        </table>';

        
        // echo '<a class="icon-button file-button" href="#" onclick=\'sendEmail(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';
        // echo '<div id="loading">
        // <img src="images/loading.gif" alt="Cargando..."></div>';
// <button type='button' class='icon-button file-button' onclick='pop_dis16(\"$id_p\",\"Agregar un documento\",\"$rw1[5]\")'><i class='fas fa-file'></i>Excel</button>
        








    
    }else if ($tabla == "Informacion Transmillas"){
        // $myArray = $_REQUEST["ide"];
        print_r($myArray);
        $fechaactual = date("Y-m-d");
        $FB->titulo_azul1("Formuario",10,0, 5);  
        $FB->llena_texto("Nombre:", 2, 1, $DB, "", "", "", 1, 0);
        $FB->llena_texto("Dato:", 3, 1, $DB, "", "", "", 4, 0);
        // $FB->llena_texto("Cargar formato", 3, 6, $DB, "", "", "", 1, 0); 
        echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'guardarPregunta(); return false;\'>Guardar</a></td></tr>';
        echo '<div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="buscador" placeholder="Buscar...">
        </div>';
        // $FB->titulo_azul1("Pregunta",1,0,7);
        // $FB->titulo_azul1("Respuesta",1,0,0);

        echo'<table id="tablaPreguntas" class="tabla-preguntas">
        <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Dato</th>
                    <th>Eliminar</th>
                </tr>
        </thead>
            <tbody>';
            // $FB->titulo_azul1("Conductor",1,0,0); 
        $sql="SELECT  `pregunta`, `respuesta`,idpreres FROM `preguntas_transmillas` WHERE pre_tipodato=2";

        $DB->Execute($sql);  
        while($rw1=mysqli_fetch_row($DB->Consulta_ID))
        {
        echo "<tr>";
            echo "<td>$rw1[0]</td>";
            echo "<td>$rw1[1]</td>";
            // echo "<td>🗑️</td>";
            if($nivel_acceso==1){
                $DB->edites($rw1[2], "preres", 2, $condecion);
            }
        echo"</tr>";
        }
            
        echo'   </tbody>
        </table>';

        
        // echo '<a class="icon-button file-button" href="#" onclick=\'sendEmail(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';
        // echo '<div id="loading">
        // <img src="images/loading.gif" alt="Cargando..."></div>';
// <button type='button' class='icon-button file-button' onclick='pop_dis16(\"$id_p\",\"Agregar un documento\",\"$rw1[5]\")'><i class='fas fa-file'></i>Excel</button>
        








    
}elseif ($tabla == "Enviar correo factura vencida") {
        $credito = $_REQUEST["ide"];
        //    print_r($myArray);
        // $FB->titulo_azul1("Archivos que se enviaran",1,0,7); 
        if ($credito=="EXTERNOS") {
            $FB->llena_texto("Email Destinatario:", 2, 1, $DB, "", "", "", 2, 0);
        }else{
            $sql2="SELECT `idcreditos`, `cre_nombre`,idhojadevida FROM `creditos` INNER JOIN hojadevidacliente on hoj_clientecredito=idcreditos WHERE cre_nombre='$credito'";
            $DB1->Execute($sql2); 
            $rw1=mysqli_fetch_row($DB1->Consulta_ID);
            $FB->llena_texto("Email Destinatario:", 2, 2, $DB, "(SELECT `cont_correo`,cont_correo FROM `contactofacturacion` WHERE cont_idhojavida ='$rw1[2]')", "", "", 17, 1);
        }
        $sql3="SELECT `idfacturascreditos`, `fac_fechafactura`,`fac_credito`, `fac_numerofactura`, `fac_fechaprefac`,`fac_idservicios`, `fac_iduserpre`,`fac_numeroref`, `fac_fechafacturado`, `fac_fechavencimiento`, `fac_estado`,`fac_tipopago`,`fac_iduserfac`,fac_precio,`fac_fecharadicado`,fac_fechapago,fac_notacredito,fac_fecharafacturado,fac_pagoconfir,fac_userconfirmo,fac_fechacomfir,fac_valorpendiente,fac_preciofinal FROM `facturascreditos` WHERE idfacturascreditos='$id_param'";
        $DB1->Execute($sql3); 
        $rw3=mysqli_fetch_row($DB1->Consulta_ID);
           $fechaactual = date("Y-m-d");
           echo'<tr><td><label>Contenido del correo</label><br><textarea id="param5" name="param5" rows="4" cols="50" placeholder="">Estimado cliente le recordamos que la factura #'.$rw3[7].' se encuentra vencida,si ya realizó su pago por favor enviar el soporte a  esté correo</textarea><td></tr><br>';
           echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'sendEmail('.$id_param.'); return false;\'>Enviar</a><td></tr>';
           echo '<div id="loading">
           <img src="images/loading.gif" alt="Cargando..."></div>';
}elseif ($tabla == "Enviar correo factura") {

    $credito = $_REQUEST["ide"];

    if ($credito == "EXTERNOS") {
        $FB->llena_texto("Email Destinatario:", 2, 1, $DB, "", "", "", 2, 0);
    } else {
        $sql2 = "SELECT idcreditos, cre_nombre, idhojadevida 
                FROM creditos 
                INNER JOIN hojadevidacliente ON hoj_clientecredito = idcreditos 
                WHERE cre_nombre = '$credito' AND hoj_estado = 'Activo'";
        $DB1->Execute($sql2); 
        $rw1 = mysqli_fetch_row($DB1->Consulta_ID);

        $sql3 = "SELECT idcontactofacturacion, cont_correo 
                FROM contactofacturacion 
                WHERE cont_idhojavida = '$rw1[2]'";
        $DB1->Execute($sql3);  

        echo '<div class="table-responsive mb-3">';
        echo '<table class="table table-bordered table-hover">';
        echo '<thead class="table-light"><tr><th colspan="2">Correos</th></tr></thead>';
        echo '<tbody>';

        while ($rw3 = mysqli_fetch_row($DB1->Consulta_ID)) {
            if ($rw3[1] != "") {
                echo "<tr >
                        <td colspan='2'>
                            <input type='checkbox' class='form-check-input' id='{$rw3[0]}s' value='{$rw3[0]}'
                                onchange='selecionado({$rw3[0]}, \"{$rw3[1]}\")'>
                                
                                <label><strong>{$rw3[1]}</strong></label>
                        </td>
                        
                    </tr>";
            }
        }

        
    }

    $FB->llena_texto("Otro Email :", 2, 1, $DB, "", "", "", 2, 0);
    $FB->llena_texto("Documento1", 3, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Documento2", 6, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("Whatsapp", 12, 1, $DB, "", "", "57", 1, 0);

    $sql3 = "SELECT idfacturascreditos, fac_fechafactura, fac_credito, fac_numerofactura, fac_fechaprefac, fac_idservicios,
                    fac_iduserpre, fac_numeroref, fac_fechafacturado, fac_fechavencimiento, fac_estado, fac_tipopago,
                    fac_iduserfac, fac_precio, fac_fecharadicado, fac_fechapago, fac_notacredito, fac_fecharafacturado,
                    fac_pagoconfir, fac_userconfirmo, fac_fechacomfir, fac_valorpendiente, fac_preciofinal 
            FROM facturascreditos 
            WHERE idfacturascreditos = '$id_param'";
    $DB1->Execute($sql3); 
    $rw3 = mysqli_fetch_row($DB1->Consulta_ID);

    $fechaactual = date("Y-m-d");

    // Mensajes
    echo '<tr  class="text"><td class="text" >';
    echo '<label for="param5" class="form-label"><strong>Mensaje</strong></label></td>';
    echo '<td class="text" ><select class="form-select" id="param5" name="param5">';
    echo "<option value='Estimado cliente estos son los documentos correspondientes a la factura #{$rw3[7]}'>
            Estimado cliente estos son los documentos correspondientes a la factura #{$rw3[7]}
        </option>";
    echo "<option value='Estimado cliente estos son los documentos correspondientes a la factura #{$rw3[7]} enviamos factura vencida si ya realizó el pago por favor enviar soporte al correo ventastransmillas@gmail.com gracias'>
            Estimado cliente estos son los documentos correspondientes a la factura #{$rw3[7]} (factura vencida, enviar soporte)
        </option>";
    echo "<option value='Queremos recordarte que tenemos varias facturas pendientes por pago.Haganos saber si hay algún problema o si necesitas información adicional para realizar el pago ventastransmillas@gmail.com'>
            Recordatorio de facturas pendientes - Contacto: ventastransmillas@gmail.com
        </option>";
    echo "<option value='Estimado cliente envío archivo en excel con la relación de los servicios prestados para su respectiva aprobación esperando respuesta para generar la factura correspondiente'>
            Envío de relación de servicios prestados para aprobación
        </option>";
    echo '</select>';
    echo '</td></tr>';

    // Documentos adjuntos
    $radicado = "Facturado:" . $rw3[17];
    $linkfak1 = $LT->llenadocs31($DB1, "facturascreditos", $rw3[0], 3, 15, $radicado);

    if (preg_match("/href='([^']+)'/", $linkfak1, $matches)) {
        $link = $matches[1];
    }

    $chek0 = file_exists("pre_facturas/{$rw3[3]}.xls") ? "" : "disabled";
    $chek1 = file_exists($link) ? "" : "disabled";

    echo '<tr  class="text"><td class="text" colspan="2">';
    echo "<input class='form-check-input' type='checkbox' id='param10' name='param10' $chek0>";
    echo "<label class='form-check-label' for='param10'>Pre-factura</label>";
    echo "<input class='form-check-input' type='checkbox' id='param11' name='param11' $chek1>";
    echo "<label class='form-check-label' for='param11'>Factura</label>";
    echo '</td></tr>';



    echo "<input type='hidden' id='linkfac' name='linkfac' value='pre_facturas/{$rw3[3]}.xls'>";
    echo "<input type='hidden' id='linkfac1' name='linkfac1' value='{$link}'>";

    // Botón enviar
    echo '<tr  class="text"><td class="text" colspan="2">';
    echo "<button class='btn btn-primary' onclick='sendEmailfac($id_param); return false;'>Enviar</button>";
    echo '</td></tr>';

    echo '</tbody></table></div>';
    echo '<div id="loading" class="mt-2">';
    echo '<img src="images/loading.gif" alt="Cargando...">';
    echo '</div>';
        
}else if ($tabla == "Enviar Whatsapp"){
    // $myArray = $_REQUEST["ide"];
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Mensajes para clientes",1,0,7); 
    echo'<tr><td class="text"><label >Plantillas</label></td></tr><tr><td class="text"><select class="form-control"  id="param5" name="param5" >
    <option value="">Seleccione....</option>
    <option value="6">Hola😄Tu servicio con numero de guia BGT0001 ya se encuentra en nuestras oficinas🏢 de Transmillas, puedes pasar por el.</option>
    <option value="7">🖐️Hola transmillas te informa que tu envío lleva más de 8 días⏰ y no lo has retirado si no pasas por tu envío se hará devolución a la bodega en Bogotá.</option>
    <option value="a_entregar_4">Queremos informarte que nuestro servicio de transporte 🚚 de carga Transmillas se dirige a ENTREGAR tu servicio BGT0001. Estamos en camino y llegaremos pronto. ¡Gracias por confiar en nosotros!</option>
    <option value="9">Tu Servicio con  número de guía: BGT0001. ha sido entregado 🙌 con ¡Exito!🎉 Gracias por confiar en Transmillas 🤗.🟢Recuerda que por este medio puedes solicitar tu 🚚servicio, solo escribe "HOLA" y con gusto te ayudaremos.🟢</option>
    <option value="recoger_oficina7 ">Tu servicio esta listo Hola😄Tu servicio con numero de guia BGT0001 ya se encuentra en nuestras oficinas🏢 de Transmillas, puedes pasar por el.</option>
    <option value="se_devolvera8">🖐️Hola transmillas te informa que tu envío con numero de guia BGT0001 lleva más de 8 días⏰ y no lo has retirado si no pasas por tu envío se hará devolución a la bodega en Bogotá.🟢Recuerda que por este medio puedes solicitar tu 🚚servicio, solo escribe "HOLA" y con gusto te ayudaremos.🟢</option>
    <option value="servicio_recogido">¡Tu servicio se recogió con éxito! El número de seguimiento es BGT0001.🟢Recuerda que por este medio puedes solicitar tus 🚚servicios, solo escribe "HOLA" y con gusto te ayudaremos.🟢</option>
    <option value="no_recogido_3">Transmillas pasó a recoger tu servicio, a la direccion calle 0 #00-00  pero lamentablemente 😢 el servicio no pudo ser efectivo . 🚨Timbramos 3 veces pero nadie salio Por favor, comunícate con nosotros.Comunícate con la sede donde se recogera el servicio</option>
    <option value="10">No recogido Transmillas no alcanza a recoger tu servicio por la hora se programa para el día de mañana.</option>
    <option value="11">No recogido Transmillas te informa que el remitente no tiene listo tu envío por favor comunícate con el.</option>
    <option value="12">Transmillas té informa que el remitente no contesta para poder agendar el servicio por favor comunícate con nosotros</option>
    <option value="13">No recogido ¡Hola! Transmillas paso a recoger tu servicio pero la dirección no existe por favor comunícate con el remitente y transmillas.</option>
    <option value="9">No recogido ¡Hola! Transmillas te informa que tu servicio no pudo ser recogido el remitente cerró antes de tiempo queda programado para el día de mañana</option>
    <option value="14">Extracto disponible ¡Hola! Transmillas pasó a recoger tu servicio pero el remitente no entregó tu envío comunícate con el.</option>
    <option value="15">Llegamos Hola Transmillas se encuentra en tu dirección🏠 para recoger tu servicio por favor nos puedes atender gracias.</option>
    <option value="16">Llegamos Hola Transmillas se encuentra en tu dirección🏠 para hacer la entrega de tu servicio📦 por favor nos puedes atender gracias.</option>
    <option value="statement_available_1">No entregado Hola Transmillas pasó a entregar tu servicio BGT0001 pero no fue posible  viene para pagar no dejaron el dinero💵.</option>
    <option value="18">No entregado Hola Transmillas pasó a entregar tu servicio BGT0001 pero no sé encuentra nadie en dirección de programa la entrega pará el día de mañana.</option>
    <option value="19">No entregado Hola Transmillas no entregó tu servicio BGT0001 por qué la dirección no existe por favor comunícate con nosotros.</option>
    <option value="21">¡Transmillas Informa! Recuerda que debes pasar por tu encomienda antes del ⏰31 ya que no se prestara servicio desde el dia 31 hasta el dia 7.</option>

    </select>
    </td></td>';
    echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'sendEmailfac('.$id_param.'); return false;\'>Enviar</a><td></tr>';


    echo '<div id="loading">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}else if ($tabla == "Whatsapp operador"){
    // $myArray = $_REQUEST["ide"];
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Mensajes para clientes",1,0,7); 
    echo'<tr><td class="text"><label >Plantillas</label></td></tr><tr><td class="text"><select class="form-control"  id="chekWhatsapp" name="chekWhatsapp" >
    <option value="">Seleccione....</option>
    <option value="7">Hola😄Tu servicio con numero de guia BGT0001 ya se encuentra en nuestras oficinas🏢 de Transmillas, puedes pasar por el.</option>
    <option value="8">🖐️Hola transmillas te informa que tu envío lleva más de 8 días⏰ y no lo has retirado si no pasas por tu envío se hará devolución a la bodega en Bogotá.</option>
    <option value="17">Transmillas pasó a entregar tu servicio BGT0001 pero no fue posible  viene para pagar no dejaron el dinero💵.</option>
    <option value="18">Transmillas pasó a entregar tu servicio BGT0001 pero no sé encuentra nadie en dirección de programa la entrega pará el día de mañana.</option>
    <option value="19">Transmillas no entregó tu servicio BGT0001 por qué la dirección no existe por favor comunícate con nosotros.</option>
    <option value="21">Transmillas recuerda que debes pasar por tu encomienda antes del ⏰31 ya que no se prestara servicio desde el dia 31 hasta el dia 7 .</option>
    </select>
    </td></td>';
   
    if (isset($_GET['ide'])) {
        // Decodifica el parámetro 'ide' de JSON a un array PHP
        $myArray = json_decode(urldecode($_GET['ide']), true);

        // Verifica que $myArray es realmente un array
        if (is_array($myArray)) {
            // Imprime la estructura del array para depuración
            // echo '<pre>';
            // var_dump($myArray);
            // echo '</pre>';

            // Genera la tabla
             echo '<table border="1">';
             echo '<th>Id servicio</th><th>Consecutivo</th><th>Remi</th><th>Des</th>';
            foreach ($myArray as $dato) {
                $sql2="SELECT idservicios,ser_estado,ser_telefonocontacto,ser_consecutivo,cli_telefono FROM `serviciosdia` WHERE  idservicios = '$dato'";
                $DB1->Execute($sql2); 
                // $iddoc=$DB1->recogedato(0);
                $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                echo "<tr><td>$rw1[0]</td><td>$rw1[3]</td><td>$rw1[2]</td><td>$rw1[4]</td></tr>";
                if ($rw1) {
                    $fileNames[] = [$rw1[0],$rw1[2],$rw1[3],$rw1[4]]; // Agrega el resultado al nuevo array
                }
            }
            echo '</table>';
            
        } else {
            // echo "El dato recibido no es un array.";
        }
    } else {
        // echo "No se recibió ningún dato.";
    }
    echo '<a class="btn btn-primary btn-lg" href="#" onclick=\'sendWhatsapp(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';

    // echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'sendEmailfac('.$_GET['ide'].'); return false;\'>Enviar</a><td></tr>';
    echo'<div id="loading"  style="display: none;">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}else if ($tabla == "Enviar Whatsapp Servicios"){
    // $myArray = $_REQUEST["ide"];
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Mensajes para clientes",1,0,7); 
    echo'<tr><td class="text"><label >Plantillas</label></td></tr><tr><td class="text"><select class="form-control"  id="chekWhatsapp" name="chekWhatsapp" >
    <option value="">Seleccione....</option>
    <option value="9">Transmillas te informa que tu servicio no pudo ser recogido el remitente cerró antes de tiempo queda programado para el día de mañana</option>
    <option value="10">Transmillas no alcanza a recoger tu servicio por la hora se programa para el día de mañana.</option>
    <option value="11">Transmillas te informa que el remitente no tiene listo tu envío por favor comunícate con el.</option>
    <option value="12">Transmillas té informa que el remitente no contesta para poder agendar el servicio por favor comunícate con nosotros.</option>
    <option value="13">Transmillas paso a recoger tu servicio pero la dirección no existe por favor comunícate con el remitente y transmillas.</option>
    <option value="14">Transmillas pasó a recoger tu servicio pero el remitente no entregó tu envío comunícate con el.</option>
    </select>
    </td></td>';
   
    if (isset($_GET['ide'])) {
        // Decodifica el parámetro 'ide' de JSON a un array PHP
        $myArray = json_decode(urldecode($_GET['ide']), true);

        // Verifica que $myArray es realmente un array
        if (is_array($myArray)) {
            // Imprime la estructura del array para depuración
            // echo '<pre>';
            // var_dump($myArray);
            // echo '</pre>';

            // Genera la tabla
             echo '<table border="1">';
             echo '<th>Id servicio</th><th>Consecutivo</th><th>Remi</th><th>Des</th>';
            foreach ($myArray as $dato) {
                $sql2="SELECT idservicios,ser_estado,ser_telefonocontacto,ser_consecutivo,cli_telefono FROM `serviciosdia` WHERE  idservicios = '$dato'";
                $DB1->Execute($sql2); 
                // $iddoc=$DB1->recogedato(0);
                $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                echo "<tr><td>$rw1[0]</td><td>$rw1[3]</td><td>$rw1[2]</td><td>$rw1[4]</td></tr>";
                if ($rw1) {
                    $fileNames[] = [$rw1[0],$rw1[2],$rw1[3],$rw1[4]]; // Agrega el resultado al nuevo array
                }
            }
            echo '</table>';
            
        } else {
            // echo "El dato recibido no es un array.";
        }
    } else {
        // echo "No se recibió ningún dato.";
    }
    echo '<a class="btn btn-primary btn-lg" href="#" onclick=\'sendWhatsapp(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';

    // echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'sendEmailfac('.$_GET['ide'].'); return false;\'>Enviar</a><td></tr>';
    echo'<div id="loading"  style="display: none;">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}else if ($tabla == "Whatsapp 3"){
    // $myArray = $_REQUEST["ide"];
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Mensajes para clientes",1,0,7); 
    echo'<tr><td class="text"><label >Plantillas</label></td></tr><tr><td class="text"><select class="form-control"  id="chekWhatsapp" name="chekWhatsapp" >
    <option value="">Seleccione....</option>
    <option value="15">Transmillas se encuentra en tu dirección🏠 para recoger tu servicio por favor nos puedes atender gracias.</option>
    <option value="16">Transmillas se encuentra en tu dirección🏠 para hacer la entrega de tu servicio📦 por favor nos puedes atender gracias.</option>
    </select>
    </td></td>';
   
    if (isset($_GET['ide'])) {
        // Decodifica el parámetro 'ide' de JSON a un array PHP
        $myArray = json_decode(urldecode($_GET['ide']), true);

        // Verifica que $myArray es realmente un array
        if (is_array($myArray)) {
            // Imprime la estructura del array para depuración
            // echo '<pre>';
            // var_dump($myArray);
            // echo '</pre>';

            // Genera la tabla
             echo '<table border="1">';
             echo '<th>Id servicio</th><th>Consecutivo</th><th>Remi</th><th>Des</th>';
            foreach ($myArray as $dato) {
                $sql2="SELECT idservicios,ser_estado,ser_telefonocontacto,ser_consecutivo,cli_telefono FROM `serviciosdia` WHERE  idservicios = '$dato'";
                $DB1->Execute($sql2); 
                // $iddoc=$DB1->recogedato(0);
                $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                echo "<tr><td>$rw1[0]</td><td>$rw1[3]</td><td>$rw1[2]</td><td>$rw1[4]</td></tr>";
                if ($rw1) {
                    $fileNames[] = [$rw1[0],$rw1[2],$rw1[3],$rw1[4]]; // Agrega el resultado al nuevo array
                }
            }
            echo '</table>';
            
        } else {
            // echo "El dato recibido no es un array.";
        }
    } else {
        // echo "No se recibió ningún dato.";
    }
    echo '<a class="btn btn-primary btn-lg" href="#" onclick=\'sendWhatsapp(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';

    // echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'sendEmailfac('.$_GET['ide'].'); return false;\'>Enviar</a><td></tr>';
    echo'<div id="loading"  style="display: none;">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}else if ($tabla == "doc_Prefactura") {
    $prefac = $_REQUEST["ide"];
    // $FB->llena_texto("Fecha de Aprobacion:", 1, 10, $DB, "", "", "$fechaactual", 1, 0);
    // $FB->llena_texto("Valor Final :", 4, 1, $DB, "", "", "", 2, 1);
    $FB->llena_texto("Documento", 3, 6, $DB, "", "", "", 1, 0);
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $prefac, 5, 0);
}else if ($tabla == "Enviar Guia R" or $tabla == "Enviar Guia E"){
    // $myArray = $_REQUEST["ide"];
    // print_r($myArray);
    if ($tabla == "Enviar Guia R") {
        $tipo="Recogida";
        $tipo2="R";
    }else {
        $tipo="Entrega";
        $tipo2="E";

    }
        $sql2="SELECT ima_ruta,ima_tipo,idimagenguias,ima_fecha,ser_consecutivo from imagenguias INNER JOIN servicios on  ima_idservicio=idservicios  where ima_idservicio=$id_param and ima_tipo='$tipo' ";
        $DB1->Execute($sql2); 
        $rw1=mysqli_fetch_row($DB1->Consulta_ID);
        $idEnvio=$rw1[4].$tipo2;

    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Enviar guia",1,0,7); 
    echo'<tr><td class="text"><input type="tel" placeholder="Numero de whatsapp" id="tele" name="tele"></td></tr>';
   
   

    echo '<tr><td class="text"><a class="btn btn-primary btn-lg" href="#" onclick="enviarAlertaWhat(\''.$id_param.'\', 42, \''.$id_param.'\', \''.$rw1[0].'\', \''.$idEnvio.'\');">Enviar</a></td></tr>';
    // echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'sendEmailfac('.$_GET['ide'].'); return false;\'>Enviar</a><td></tr>';
    echo'<div id="loading"  style="display: none;">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}else if ($tabla == "Seguimiento piezas"){


    $FB->titulo_azul1("#Guia",1,0,7); 
    $FB->titulo_azul1("En?",1,0,0); 
    $FB->titulo_azul1("Quien?",1,0,0);
    $FB->titulo_azul1("Fecha",1,0,0);  
    $FB->titulo_azul1("# pieza",1,0,0);  
    $sql3="SELECT  `transporta`,`quien_escanea`,fecha_escanea,numeropieza FROM piezasguia  where  numeroguia like '%$id_param%' ORDER BY numeropieza ASC";

    $DB1->Execute($sql3);  
   

    while($rw3=mysqli_fetch_row($DB1->Consulta_ID))
    {
        echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";

            echo "<td>$id_param</td>";
            echo "<td>".$rw3[0]."</td>";
            echo "<td>$rw3[1]</td>";
            echo "<td>$rw3[2]</td>";
            echo "<td>$rw3[3]</td>";
            echo "</tr>";
     } 

}else if ($tabla == "Reporte_de_nomina"){


 
    $FB->llena_texto("Soporte de pago pago", 1, 6, $DB, "", "", "", 1, 0);


}else if ($tabla == "Editar Factura Externa") {
    $sql2="SELECT fac_fechavencimiento,fac_correo_auto from facturascreditos where idfacturascreditos =$id_param  ";
    $DB1->Execute($sql2); 
    $rw1=mysqli_fetch_row($DB1->Consulta_ID);
    
     $FB->llena_texto("Correo:", 4, 1, $DB, "", "", "$rw1[1]", 2, 0);
     $FB->llena_texto("Fecha Vencimiento:", 5, 10, $DB, "", "", "$rw1[0]", 4, 0);
     
    $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);
   

}else if ($tabla == "Enviar Whatsapp Conductores"){
    
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Mensajes para clientes",1,0,7); 
    echo'<tr><td class="text"><label >Plantillas</label></td></tr><tr><td class="text"><select class="form-control"  id="chekWhatsapp" name="chekWhatsapp" >
    <option value="">Seleccione....</option>
    <option value="29">"Transmillas requiere transporte desde _____ hacia la ciudad de _____. Por favor, comunícate al número 3160490959. ¡Gracias!" </option>
    <option value="30">""Gracias por tu disposición. Por hoy ya contamos con el transporte necesario. Sin embargo, es posible que mañana volvamos a requerir servicio, así que estaremos en contacto. ¡Gracias nuevamente!"" </option>
    </select>
    </td>
    <tr><td class="text"><label>Origen</label></td></tr><tr><td class="text"><input class="form-control" type="text" id="ciudadOrigenWhatsapp" name="ciudadOrigenWhatsapp" placeholder="Escribe la ciudad de origen"></td></tr>
    <tr><td class="text"><label>Destino</label></td></tr><tr><td class="text"><input class="form-control" type="text" id="ciudadDestinoWhatsapp" name="ciudadDestinoWhatsapp" placeholder="Escribe la ciudad de destino"></td></tr>
    </td>';
   
    if (isset($_GET['ide'])) {
        // Decodifica el parámetro 'ide' de JSON a un array PHP
        $myArray = json_decode(urldecode($_GET['ide']), true);

        // Verifica que $myArray es realmente un array
        if (is_array($myArray)) {
            // Genera la tabla
             echo '<table border="1">';
             echo '<th>Conductor</th><th>Numero Whatsapp</th>';
             $zona="Bogota";
            foreach ($myArray as $dato) {
               
                $sql2="SELECT `condid`, `cond_nombre`, `cond_celular`, `cond_whatsapp`, `cond_cedula`, `cond_foto_celula`, `cond_num_licen`, `cond_foto_licen`, `cond_foto_conductor`, `cond_firma`,con_antec FROM `conductor_mani` where condid='$dato'  ";
                
                $DB1->Execute($sql2); 
                // $iddoc=$DB1->recogedato(0);
                $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                echo "<tr><td>$rw1[1]</td><td>$rw1[2]</td></tr>";
                if ($rw1) {
                    // id, contacto, consecutivo, telefono
                    $fileNames[] = [$rw1[0],"",$zona,$rw1[3]]; // Agrega el resultado al nuevo array
                }
            }
            echo '</table>';
            
        } else {
            // echo "El dato recibido no es un array.";
        }
    } else {
        // echo "No se recibió ningún dato.";
    }
    echo '<a class="btn btn-primary btn-lg" href="#" onclick=\'sendWhatsapp(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';

    
    echo'<div id="loading"  style="display: none;">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}else if ($tabla == "Enviar Whatsapp Operadores"){
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Mensajes para clientes",1,0,7); 
    echo'<tr><td class="text"><label >Plantillas</label></td></tr><tr><td class="text"><select class="form-control"  id="chekWhatsapp" name="chekWhatsapp" >
    <option value="">Seleccione....</option>
    <option value="31">"Hola,🚨🚨Queremos recordarte que has superado el tiempo estimado para realizar el servicio asignado . Te agradecemos agilizar 🚚⏪  para continuar y evitar retrasos.</option>
    
    
    </select>
    </td></td>';
   
    if (isset($_GET['ide'])) {
        // Decodifica el parámetro 'ide' de JSON a un array PHP
        $myArray = json_decode(urldecode($_GET['ide']), true);

        // Verifica que $myArray es realmente un array
        if (is_array($myArray)) {


            // Genera la tabla
             echo '<table border="1">';
             echo '<th>Operador</th><th>Numero Whatsapp</th>';
             $zona="Bogota";
            foreach ($myArray as $dato) {
               
                $sql2="SELECT idusuarios, usu_nombre,usu_telefono FROM `usuarios` WHERE idusuarios='$dato'  ";
                
                $DB1->Execute($sql2); 
                // $iddoc=$DB1->recogedato(0);
                $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                echo "<tr><td>$rw1[1]</td><td>$rw1[2]</td></tr>";
                if ($rw1) {
                    // id, contacto, consecutivo, telefono
                    $fileNames[] = [$rw1[0],"",$zona,$rw1[2]]; // Agrega el resultado al nuevo array
                }
            }
            echo '</table>';
            
        } else {
            // echo "El dato recibido no es un array.";
        }
    } else {
        // echo "No se recibió ningún dato.";
    }
    echo '<a class="btn btn-primary btn-lg" href="#" onclick=\'sendWhatsapp(' . htmlspecialchars(json_encode($fileNames), ENT_QUOTES, 'UTF-8') . '); return false;\'>Enviar</a>';

    // echo '<tr><td><a class="icon-button file-button" href="#" onclick=\'sendEmailfac('.$_GET['ide'].'); return false;\'>Enviar</a><td></tr>';
    echo'<div id="loading"  style="display: none;">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}elseif ($tabla == "Operadores Quincena") {
   
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Quienes trabajaron en la quincena",1,0,7); 
    $conde1="";
    $conde2="";
    $año=$_GET['año'];
    $mes=$_GET['mes'];
    $dia=$_GET['dia'];
    $sede=$_GET['sede'];
    if ($sede!="") {
        $conde1="AND usu_idsede='$sede'";
    }
    $contrato=$_GET['contrato'];
    if ($contrato!="0") {
        $conde2=" AND usu_tipocontrato='$contrato'";
    }else {
        $conde2=" AND usu_tipocontrato='Empresa'";
    }

        if ($dia=="Segunda") {
            $dia1='16';
            $dia2='30';
        }else{
            $dia1='01';
            $dia2='15';
        }
    $fechainicial=date($año.'-'.$mes.'-'.$dia1. ' 00:00:00');
    $fechafinal=date($año.'-'.$mes.'-'.$dia2. ' 23:59:00');

         $myArray = json_decode(urldecode($_GET['ide']), true);
 
         // Verifica que $myArray es realmente un array
         if (is_array($myArray)) {
 


            echo '<div class="container mt-4">';
            echo '<table class="table table-bordered">';
            echo '<thead class="table-dark">';
            echo '<tr><th>Id</th><th>Trabajador</th><th>Identificacion</th><th>Dias</th></tr>';
            echo '</thead>';
            echo '<tbody>';

           $sql2 = "SELECT seg_idusuario, COUNT(*) AS cantidad_ingresos, usu_nombre, usu_identificacion
                    FROM seguimiento_user 
                    INNER JOIN usuarios ON idusuarios = seg_idusuario 
                    WHERE seg_motivo in ('Ingreso','descanso','IngresoHoras','licencia de maternidad','LICENCIA POR LUTO','PAGO DE INCAPACIDAD AL 66','Incapacidad','Vacaciones')
                    AND seg_fechaingreso >= '$fechainicial'
                    AND seg_fechaingreso <= '$fechafinal' $conde1 $conde2
                    GROUP BY seg_idusuario order by usu_nombre asc ";

            $DB1->Execute($sql2);

            while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
                $idUsuario = $rw1[0];
                $nombreUsuario = $rw1[2];
                $identificacion = $rw1[3];
                $dias = $rw1[1];
                // Verificamos si el ID está en el array
                $color = in_array($idUsuario, $myArray) ? '#a3e4d7' : '#f5b7b1';

                echo "<tr style='background-color:$color;'><td>$idUsuario</td><td>$nombreUsuario</td><td>$identificacion</td><td>$dias</td></tr>";
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
         } else {
             // echo "El dato recibido no es un array.";
         }

}elseif ($tabla == "Detalles Alarma") {
    
	$sql = "SELECT `rep_banco`,
	`rep_cuenta`,`rep_nombre_consi`,`rep_valor` 
	FROM `reportealertas` 
	WHERE idreportealertas='$id_param' ";
	$DB->Execute($sql);
	$rw1 = mysqli_fetch_row($DB->Consulta_ID);

    echo '<div class="container mt-4">';
        echo '<table class="table table-bordered">';
            echo '<thead class="table-dark">';
            echo '<tr><th>Nombre</th><th>Dato</th></tr>';
            echo '</thead>';
            echo '<tr><td>Banco</td><td>'.$rw1[0].'</td></tr>';
            echo '<tr><td>Numero de cuenta</td><td>'.$rw1[1].'</td></tr>';
            echo '<tr><td>Nombre</td><td>'.$rw1[2].'</td></tr>';
            echo '<tr><td>Valor</td><td>'.$rw1[3].'</td></tr>';
        echo '</tbody>';
        echo '</table>';
    echo '</div>';
    

}elseif ($tabla == "pagosPendientes") {
    echo "Tienje pagos pendientes";
}elseif ($tabla == "aceptaCompañero") {
        $idCompa=$_GET['mensaje'];
        $idSeg=$_GET['idSeg'];
                   $sql2 = "SELECT usu_nombre FROM usuarios WHERE idusuarios = '$idCompa'";
                    $DB1->Execute($sql2);
                    $rw1 = mysqli_fetch_row($DB1->Consulta_ID);

    
      echo'<div class="modal-body text-center">
        <p class="mb-3 fs-5"><h1>'.$rw1[0].'.</h1></p>
        <p class="text-muted"><strong>🔔 Es su compañero asignado para el dia de hoy <br>Confirme por favor</p>
        <a class="btn btn-primary btn-lg" href="#" onclick=\'aceptarCompa(' .$idCompa. ',' .$idSeg. '); return false;\'>Confirmar</a>
        </div>';
        
}elseif ($tabla=="Notificacion_Whatsapp") {
    print_r($myArray);
    $fechaactual = date("Y-m-d");
    $FB->titulo_azul1("Mensajes para clientes",1,0,7); 
        echo '
        <tr>
        <td class="text"><label>Mensaje </label></td>
        </tr>
        <tr>
        <td class="text">
            <select id="mensajeFijo" style="width: 100%;">
                <option value="1">
                Transmillas le informa Queremos informarle que su 🚚 encomienda #_____ se encuentra 
                </option>
                <option value="2">
                    Transmillas les desea una feliz navidad🎄 y un prospero año nuevo✨. Gracias por preferirnos😊.
                </option>
            </select>
        </td>
        </tr>

        <tr>
        <td class="text"><label>Continuación del mensaje</label></td>
        </tr>
        <tr>
        <td class="text">
            <textarea id="mensajeExtra" rows="4" cols="40" placeholder="Escriba aquí la continuación..."></textarea>
        </td>
        </tr>
        ';
   
    if (isset($_GET['ide'])) {
        // Decodifica el parámetro 'ide' de JSON a un array PHP
        $myArray = json_decode(urldecode($_GET['ide']), true);

        // Verifica que $myArray es realmente un array
        if (is_array($myArray)) {
            // Imprime la estructura del array para depuración
            // Genera la tabla
             echo '<table border="1">';
             echo '<th>Conductor</th><th>Numero Whatsapp</th>';
             $zona="Bogota";
            foreach ($myArray as $dato) {
               
                $sql2="SELECT `condid`, `cond_nombre`, `cond_celular`, `cond_whatsapp`, `cond_cedula`, `cond_foto_celula`, `cond_num_licen`, `cond_foto_licen`, `cond_foto_conductor`, `cond_firma`,con_antec FROM `conductor_mani` where condid='$dato'  ";
                
                $DB1->Execute($sql2); 
                // $iddoc=$DB1->recogedato(0);
                $rw1=mysqli_fetch_row($DB1->Consulta_ID);
                echo "<tr><td>$rw1[1]</td><td>$rw1[2]</td></tr>";
                if ($rw1) {
                    // id, contacto, consecutivo, telefono
                    $fileNames[] = [$rw1[0],"",$zona,$rw1[3]]; // Agrega el resultado al nuevo array
                }
            }
            echo '</table>';
            
        } else {
            // echo "El dato recibido no es un array.";
        }
    } else {
        // echo "No se recibió ningún dato.";
    }
    echo '<tr><td><a class="btn btn-primary btn-lg" href="#" onclick=\'sendWhatsapp(); return false;\'>Enviar</a></td></tr>';

    
    echo'<div id="loading"  style="display: none;">
    <img src="images/loading.gif" alt="Cargando..."></div>';
}else if ($tabla == "Archivo Drive") {
        $fecha = $_REQUEST["ide"];
        $fechaactual = date("Y-m-d");
        $sql2="SELECT `usu_doc_credito`FROM `usuarios` where idusuarios='$id_param'  ";
        
        $DB1->Execute($sql2); 
        // $iddoc=$DB1->recogedato(0);
        $rw1=mysqli_fetch_row($DB1->Consulta_ID);
        $archivoExistente = isset($rw1[0]) ? trim($rw1[0]) : "";
        $nombreArchivoExistente = $archivoExistente != "" ? basename($archivoExistente) : "Sin archivo cargado";

        echo "<tr>";
        echo "<td class='text' style='vertical-align:top; padding-top:8px;'><strong>Archivo actual:</strong></td>";
        echo "<td colspan='3' style='padding:4px 0 10px 0;'>";
        if ($archivoExistente != "") {
            $archivoSeguro = htmlspecialchars($archivoExistente, ENT_QUOTES, 'UTF-8');
            $nombreSeguro = htmlspecialchars($nombreArchivoExistente, ENT_QUOTES, 'UTF-8');
            echo "<div style='display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border:1px solid #d9d9d9; border-radius:6px; background:#f7f7f7;'>";
            echo "<span class='text' style='word-break:break-word;'>".$nombreSeguro."</span>";
            echo "<a href='imgDocTransmi/".$archivoSeguro."' target='_blank' class='btn btn-primary btn-sm'>Abrir</a>";
            echo "</div>";
        } else {
            echo "<div class='text' style='padding:10px 12px; border:1px solid #e5e5e5; border-radius:6px; background:#fafafa; color:#777;'>Sin archivo cargado</div>";
        }
        echo "</td>";
        echo "</tr>";

        $FB->llena_texto("Cargar archivo", 1, 6, $DB, "", "", "", 1, 0);
        $FB->llena_texto("param2", 1, 13, $DB, "", "", $id_param, 5, 0);


    
}



    $FB->llena_texto("tabla", 1, 13, $DB, "", "", $tabla, 5, 0);
    $FB->llena_texto("dir", 1, 13, $DB, "", "", $dir, 5, 0);
    $FB->cierra_form();
    $DB->cerrarconsulta();
    ?>
