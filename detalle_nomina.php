<script language="javascript">







</script>
<style>
    table {
        position: relative;
    }

    thead tr {
        position: sticky;
        top: 0;
        background-color: #ffffff;
    }
</style>

<?php
require("login_autentica.php");
include("cabezote3.php");

$asc="ASC";
$conde=" ";
$conde2=" ";
$conde3=" ";
$conde4=" ";
$conde5=" ";

function obtenerHorasNormalesYFestivas($DB1, $idusuario, $fechaAhora, $fechafin){
	$sql = "
		SELECT
			COALESCE(SUM(CASE 
				WHEN DAYOFWEEK(s.seg_fechaingreso) != 1
				AND NOT EXISTS (
					SELECT 1
					FROM seguimiento_user f
					WHERE f.seg_idusuario = s.seg_idusuario
					AND DATE(f.seg_fechaingreso) = DATE(s.seg_fechaingreso)
					AND f.seg_motivo IN ('descanso', 'Festivo en vacaciones')
				)
				THEN s.seg_horas_trabajadas 
				ELSE 0 
			END), 0) AS horas_normales,

			COALESCE(SUM(CASE 
				WHEN DAYOFWEEK(s.seg_fechaingreso) = 1
				OR EXISTS (
					SELECT 1
					FROM seguimiento_user f
					WHERE f.seg_idusuario = s.seg_idusuario
					AND DATE(f.seg_fechaingreso) = DATE(s.seg_fechaingreso)
					AND f.seg_motivo IN ('descanso', 'Festivo en vacaciones')
				)
				THEN s.seg_horas_trabajadas 
				ELSE 0 
			END), 0) AS horas_festivas

		FROM seguimiento_user s
		WHERE s.seg_motivo = 'IngresoHoras'
		AND s.seg_fechaingreso >= '$fechaAhora'
		AND s.seg_fechaingreso <= '$fechafin'
		AND s.seg_idusuario = '$idusuario'
	";

	$DB1->Execute($sql);
	if (!$DB1->Consulta_ID) {
		return [
			'horas_normales' => 0,
			'horas_festivas' => 0,
		];
	}

	$rw = mysqli_fetch_assoc($DB1->Consulta_ID);

	return [
		'horas_normales' => (float)($rw['horas_normales'] ?? 0),
		'horas_festivas' => (float)($rw['horas_festivas'] ?? 0),
	];
}

function obtenerCondicionDomingos($campoFecha, $fechaInicio, $fechaFin){
	$inicio = new DateTime($fechaInicio);
	$fin = new DateTime($fechaFin);
	$condiciones = [];

	if ((int)$inicio->format('w') !== 0) {
		$inicio->modify('next sunday');
	}

	while ($inicio <= $fin) {
		$domingo = $inicio->format('Y-m-d');
		$condiciones[] = "($campoFecha >= '$domingo 00:00:00' AND $campoFecha <= '$domingo 23:59:59')";
		$inicio->modify('+7 days');
	}

	return empty($condiciones) ? '0=1' : implode(' OR ', $condiciones);
}

// echo$fechacompleta=date('Y-m-d');
// echo$mes=date('m');
if($param34!=''){ $fechaactual=$param34; }

if($param35!=''){ $id_sedes=$param35;

	$conde4=" and hoj_sede=$id_sedes ";
}
if($param33!=''){
	        $cedula="SELECT `usu_identificacion` FROM `usuarios` WHERE `idusuarios`='$param33' ";
			$DB1->Execute($cedula);
			$CedulaUser=$DB1->recogedato(0);

	$conde="and `hoj_cedula`= '$CedulaUser' ";  }
if($param32!='' and $param32!=0){ $conde1="and `seg_motivo`= '$param33' ";  }




$conde3="";
// $ano=date('Y');
$ano=$param39;

if($param34!=''){ $fechaactual=$param34." 00:00:00";  }
if($param36!=''){ $fechafinal=$param36." 23:59:59";  }


if($param36=='Primera'){
	$fechaactual=date($ano.'-'.$param34.'-01'.' 00:00:00');
	$fechafinal=date($ano.'-'.$param34.'-15'.' 23:59:59');
	$diasDeLaQuincena=15;
	$fechaactualSinTiempo=date($ano.'-'.$param34.'-01');
	$fechafinalSinTiempo=date($ano.'-'.$param34.'-15');

}elseif($param36=='Segunda'){
	// $fin = date("t");
	// echo$mes."MES";
	// echo"AQUI ESTA EL MES ".$param34;
	$fecha_aux = date('Y-'.$param34.'-d'); // Obtener la fecha actual en formato 'YYYY-MM-DD'
	$fin = date('t', strtotime($fecha_aux));

	$fechaactual=date($ano.'-'.$param34.'-16'.' 00:00:00');
	$fechafinal=date($ano.'-'.$param34.'-'.$fin.' 23:59:59');
	$diasDeLaQuincena=$fin-15;
	// echo "Aquincena   tiene $diasDeLaQuincena días.";

	$fechaactualSinTiempo=date($ano.'-'.$param34.'-16');
	$fechafinalSinTiempo=date($ano.'-'.$param34.'-'.$fin);
}elseif($param36=='Completo'){

	// $fin = date("t");

	$fecha_aux = date('Y-'.$param34.'-d'); // Obtener la fecha actual en formato 'YYYY-MM-DD'
	$fin = date('t', strtotime($fecha_aux));

	$fechaactual=date($ano.'-'.$param34.'-01'.' 00:00:00');
	$fechafinal=date($ano.'-'.$param34.'-'.$fin.' 23:59:59');
	$diasDeLaQuincena=$fin-15;
	// echo "Aquincena   tiene $diasDeLaQuincena días.";

	$fechaactualSinTiempo=date($ano.'-'.$param34.'-16');
	$fechafinalSinTiempo=date($ano.'-'.$param34.'-'.$fin);



}

//cuantos dias tiene la quincena



echo'<input type="hidden" value="'.$fechaactual.'" id="fechaactual">';
echo'<input type="hidden" value="'.$fechafinal.'" id="fechafin">';




$fechas=$fechaactual."/".$fechafinal;
// echo $fechafinal;
// echo $fechaactual;

if($param37!=''){
	if($param37=='0') {
		$conde5="and hoj_tipocontrato='Empresa'";
	}else{
		$conde5=" and hoj_tipocontrato='$param37'";
	}
}else{$conde5=" and hoj_tipocontrato='Empresa'";}


if($param37=="Prestacion de Servicios"){

	echo "<table class='table table-hover'><tr bgcolor='#074F91' class='tittle3' >";
	$FB->titulo_azul1("Id",1,0,0);
	echo "<td colspan='0' width='0' align='center'>Trabajador <br>Todo<input type='checkbox' id='check_todos' onclick='seleccionarTodos()' ></td>";

	$FB->titulo_azul1("Tipo Contrato",1,0,0);
	$FB->titulo_azul1("Cedula",1,0,0);
	$FB->titulo_azul1("Cargo",1,0,0);
	$FB->titulo_azul1("Salario por dia",1,0,0);
	// $FB->titulo_azul1("Auxilio",1,0,0);

	// $FB->titulo_azul1("Valor por dia",1,0,0);
	$FB->titulo_azul1("Dias Trabajados",1,0,0);

	$FB->titulo_azul1("Valor dias trabajados",1,0,0);



	$FB->titulo_azul1("Horas trabajadas",1,0,0);
	$FB->titulo_azul1("Valor Horas trabajadas",1,0,0);
	$FB->titulo_azul1("Recogidas",1,0,0);
    $FB->titulo_azul1("Valor recogidas",1,0,0);
	$FB->titulo_azul1("Otros por dia",1,0,0);
    $FB->titulo_azul1("Valor otros",1,0,0);
	$FB->titulo_azul1("Prestamos y Descuadres",1,0,0);
	$FB->titulo_azul1("Abonos a deudas",1,0,0);
	$FB->titulo_azul1("Retegarantia",1,0,0);
	$FB->titulo_azul1("Devolver retegarantia",1,0,0);
	$FB->titulo_azul1("Valor quincena",1,'5%',0);
	$FB->titulo_azul1("Cuenta de cobro",1,'5%',0);
	$FB->titulo_azul1("Confirmacion Usuario",1,'5%',0);
	$FB->titulo_azul1("Ajustes",1,'5%',0);
	$FB->titulo_azul1("Pagado",1,'5%',0);
	$FB->titulo_azul1("Comprobante",1,'5%',0);
	// $FB->titulo_azul1("Imprimir",1,'5%',0);

	$FB->titulo_azul1("Inicio contrato",1,'5%',0);
	$FB->titulo_azul1("Termina contrato",1,'5%',0);
	$FB->titulo_azul1("Seleccion",1,'5%',0);




























 
$sql="SELECT `idhojadevida`,  `hoj_nombre`, `hoj_apellido`,hoj_cargo, `hoj_tipocontrato`,`hoj_cedula`,`hoj_fechaingreso`, `sed_nombre`,`hoj_fechanacimiento`, `hoj_cedula`,`hoj_direccion`, `hoj_celular`, `hoj_estado`,hoj_fechatermino,hoj_sede,hoj_retegarantia,hoj_firma,hoj_valorRetegarantia FROM hojadevida
  INNER JOIN sedes ON hoj_sede = idsedes
  WHERE  idhojadevida > 0  and(hoj_fechatermino IS NULL OR hoj_fechatermino = '' OR ('$fechaactualSinTiempo' BETWEEN hoj_fechaingreso AND hoj_fechatermino ) OR (hoj_fechaingreso BETWEEN '$fechaactualSinTiempo' AND '$fechafinalSinTiempo'AND hoj_fechatermino BETWEEN '$fechaactualSinTiempo' AND '$fechafinalSinTiempo')) $conde5 $conde4 $conde
  ORDER BY hoj_nombre ASC";
	$DB->Execute($sql); $va=(($compag-1)*$CantidadMostrar);
		while($rw1=mysqli_fetch_row($DB->Consulta_ID))
		{


			$fechafin=$fechafinal;
			if($rw1[6]>=$fechaactual and $rw1[6]<=$fechafinal){
				
				$mesdeingreso=true;
				$fechaAhora=$rw1[6];
			}else{
				$mesdeingreso=false;
				$fechaAhora=$fechaactual;
			}



			$id_p=$rw1[0];
			$va++; $p=$va%2;

			$user="SELECT `idusuarios` FROM `usuarios` WHERE `usu_identificacion`='$rw1[5]' and 	usu_ver_nomina='1'";
			$DB1->Execute($user);
			$idusuario=$DB1->recogedato(0);

			$idsUsu[] = $idusuario;

			if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}

			if(empty($idusuario)){


			}else{
				
				echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
				echo "<td>".$idusuario."</td>";
				
				$nombreCompleto=$rw1[1]." ".$rw1[2];
				echo "<td>".$rw1[1]." ".$rw1[2]."</td>";//Trabajador
				echo "<td>".$rw1[4]."</td>";//Tipo Contrato

				
				echo "<td>".$rw1[5]."</td>";//Cedula
				$valordediastrabajados=0;
				$sql2="SELECT  `idcargo`, `car_Cargo`, `salario`, `auxilio`, `otros`,car_Recogida,car_ValorRecogida,des_salud,des_pension FROM `cargo`INNER JOIN salarios_cargos on idcargo=id_relCargo  WHERE idcargo='$rw1[3]' and anio='$ano'";
				$DB1->Execute($sql2);
				$cargosaldo=mysqli_fetch_row($DB1->Consulta_ID);
				if($idusuario>=1){
				$prestamo=0;
				$descuadre=0;
				$pago=0;
				$slq3="SELECT deu_tipo, deu_valor FROM `duedapromotor` WHERE  deu_idpromotor='$idusuario'  ";
				$DB1->Execute($slq3);
				while($prestamostotal=mysqli_fetch_row($DB1->Consulta_ID))
				{
					if ($prestamostotal[0]=="Prestamos") {
						$prestamo =$prestamo+$prestamostotal[1];
					}elseif ($prestamostotal[0]=="Descuadre") {
						$descuadre =$descuadre+$prestamostotal[1];
					}elseif ($prestamostotal[0]=="Pagos") {
						$pago =$pago+$prestamostotal[1];
					}

				}



				//Prestamos
				$prestamoTotal=0;
				$prestamo=0;
				$descuadre=0;
				$pago=0;
				$malenviados=0;
				$slq3="SELECT deu_tipo, deu_valor FROM `duedapromotor` WHERE  deu_idpromotor='$idusuario'  ";
				$DB1->Execute($slq3);

				while($prestamostotal=mysqli_fetch_row($DB1->Consulta_ID))
				{
					if ($prestamostotal[0]=="Prestamos") {
						$prestamo =$prestamo+$prestamostotal[1];
					}elseif ($prestamostotal[0]=="Descuadre") {
						$descuadre =$descuadre+$prestamostotal[1];
					}elseif ($prestamostotal[0]=="Pagos") {
						$pago =$pago+$prestamostotal[1];
					}elseif ($prestamostotal[0]=="MalEnviados") {
						$malenviados =$malenviados+$prestamostotal[1];
					}

				}


				
				$prestamoTotal= $prestamo+$descuadre+$malenviados;
				$TotalDebe = $prestamoTotal-$pago;

				//Pagos a la 15na, de prestamos
				$pago1=0;
				$restaAOtros=0;
				$restaABasico=0;
				$descripcionBasico="";
				$slq7="SELECT deu_tipo, deu_valor,deu_pago_de,due_descripcion  FROM `duedapromotor` WHERE  deu_idpromotor='$idusuario' and deu_fecha>='$fechaAhora' and deu_fecha<='$fechafin' and deu_pago_de in ('Basico','otros') ";
				$DB1->Execute($slq7);

				while($prestamostotal7=mysqli_fetch_row($DB1->Consulta_ID))
				{
					if ($prestamostotal7[0]=="Pagos") {
						$pago1 =$pago1+$prestamostotal7[1];
						$descripcionBasico=$descripcionBasico.", $prestamostotal7[3]";


					}

				}


				$diassitrabajo=0;
				$TotalPagoQuincena = $pago1;
				$TotalPagoQuincena_formateado = number_format($TotalPagoQuincena, 0, ',', '.');

				$sitrabajo="SELECT COUNT(*) FROM `seguimiento_user`  where seg_motivo ='Ingreso' and seg_fechaingreso>='$fechaactual' and seg_fechaingreso<='$fechafinal'  and seg_idusuario='$idusuario' ";
				$DB1->Execute($sitrabajo);
				while($rw4=mysqli_fetch_row($DB1->Consulta_ID))
				{
						if ($rw1[0]==807) {
							$rw4[0]=2;
							echo'aqui';
						}
						$diassitrabajo=$rw4[0];
						$diassitrabajoParaMostrar=$rw4[0];

				}


			  $diasvalor=($cargosaldo[2]/30);
			  $valordediastrabajados=$cargosaldo[2]*$diassitrabajo;


			  $horasTrabajadas="SELECT SUM(seg_horas_trabajadas) FROM `seguimiento_user`  WHERE  seg_motivo ='IngresoHoras' and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'  and seg_idusuario='$idusuario' ";
			  $DB1->Execute($horasTrabajadas);
			  $rw6=mysqli_fetch_row($DB1->Consulta_ID);


				//Total horas domfest
				if (strpos($rw6[0], ".") !== false) {

					$partes = explode(".", $rw6[0]);
					$numeroAntesDelPunto = $partes[0];

					$valorHoras=$numeroAntesDelPunto*($cargosaldo[2]/9);
					$valorMitadDomini=($cargosaldo[2]/9)/2;

					$valorTotalHora=$valorHoras+$valorMitadDomini;
				} else {

					$valorHoras=$rw6[0]*($cargosaldo[2]/9);
					$valorTotalHora=$valorHoras;

				}


				$valorTotalHora_formateado = number_format($valorTotalHora, 0, ',', '.');





			  $incapacidad="SELECT count(*) FROM `seguimiento_user`  where seg_motivo ='Incapacidad' and seg_fechaingreso>='$fechaactual' and seg_fechaingreso<='$fechafinal'  and seg_idusuario='$idusuario' group by seg_alcohol";
			  $DB1->Execute($incapacidad);
			  $rw5=mysqli_fetch_row($DB1->Consulta_ID);
			  if(empty($rw5)){

				  $diasincapacidad=0;
			  }else{
				  $diasincapacidad=$rw5[0];
			  }

			  }else{
				  $diasnotrabajo=0;
				  $prestamostotal=0;
				  $diasvalor=0;
			  }
			  $sedess="SELECT `usu_idsede` FROM `usuarios` WHERE `idusuarios`='$idusuario' ";
			  $DB1->Execute($sedess);
			  $id_sedes=$DB1->recogedato(0);

			  $idcidades=ciudadesedes($id_sedes,$DB1);

			  $valorRecogidas=0;
			  $cantRecogidas=0;
			  $entregas="SELECT count(*)FROM servicios inner join cuentaspromotor on cue_idservicio=idservicios inner join ciudades on ser_ciudadentrega=idciudades  where date(cue_fecharecogida)>='$fechaactual' and  date(cue_fecharecogida)<='$fechafinal' and (`cue_idoperador`= '$idusuario' or  `cue_idoperentrega`= '$idusuario' ) and (cue_idciudadori in $idcidades )  ORDER BY ser_guiare  $asc ";
			  $DB1->Execute($entregas);
			  $rw10=mysqli_fetch_row($DB1->Consulta_ID);
			  if ($cargosaldo[5]=="SI" or $cargosaldo[5]=="Si" or $cargosaldo[5]=="si") {
				  $valorRecogidas=$rw10[0]*$cargosaldo[6];
				  $cantRecogidas=$rw10[0];
				}else {
				  $valorRecogidas=0;
				  $cantRecogidas=0;
				}

				$valorRecogidas_formateado = number_format($valorRecogidas, 0, ',', '.');
//Otros

			$totalOtromes_formateado = number_format($cargosaldo[4], 0, ',', '.');

			 	$diasparaotros=$diassitrabajo;

			$totalOtrosDias=($cargosaldo[4]*($diasparaotros));

			$totalOtrosDias_formateado = number_format($totalOtrosDias, 0, ',', '.');

//SACIONES

$valordiasConSancion=0;
$sancion="SELECT count(*) FROM `seguimiento_user`  where seg_motivo ='dia con sancion' and seg_fechaingreso>='$fechaactual' and seg_fechaingreso<='$fechafinal'  and seg_idusuario='$idusuario' group by seg_alcohol";
$DB1->Execute($sancion);
$rw7=mysqli_fetch_row($DB1->Consulta_ID);
if(empty($rw7)){

	$diasConSancion=0;
}else{
	$diasConSancion=$rw7[0];
}


$valordiasConSancion=($diasConSancion*$cargosaldo[2]);



//RETEGARANTIA

$ReteGarantiaMostrar="";
$retegarantiaTotal=0;
$DiasreteGarantia=0;
$descuentoReteGarantia=0;
$diasParaCalcular=0;
if ($rw1[14]==1 and $rw1[15]=="si" and $diassitrabajo >=5 ) {
	$DiasreteGarantia=300000/$cargosaldo[2];
	$diasParaCalcular=$DiasreteGarantia*15;




	




	// Sumar 1 día
	$ultimafechaReteG=date("Y-m-d", strtotime($rw1[6]."+ ".$diasParaCalcular." days"));
	$findeRetegar = date('t', strtotime($ultimafechaReteG));




	$dia_deseado = 01; // Día deseado

	$mes = date("m", strtotime($ultimafechaReteG)); // Obtener el mes de la fecha específica
	$año = date("Y", strtotime($ultimafechaReteG)); // Obtener el año de la fecha específica

	$mesinicio = date("m", strtotime($rw1[6])); // Obtener el mes de la fecha específica
	$añoinicio = date("Y", strtotime($rw1[6])); // Obtener el año de la fecha específica
	$diainicio = date("d", strtotime($rw1[6])); // Obtener el año de la fecha específica


	$fecha_resultante = date("$año-$mes-$findeRetegar"); // Crear la fecha resultante



	$descRete="SELECT nom_retedes from nomina where nom_id_usu='$idusuario'  and nom_tipo_pago='Basico'  and 	nom_fecha_inicio='$fechaactual' ";
	$DB1->Execute($descRete);

	$descRete1=mysqli_fetch_row($DB1->Consulta_ID);




	if($rw1[17]==300000){

		$descuentoReteGarantia=0;

	}else{

		$descuentoReteGarantia=$cargosaldo[2];

	}
	if ($descRete1!="") {
		$descuentoReteGarantia=$descRete1[0];
	}else {
		$descuentoReteGarantia=0;
	}

	$fecha_inicio = new DateTime("$añoinicio-$mesinicio-$diainicio"); // Fecha de inicio en formato "YYYY-MM-DD"
	$fecha_fin = new DateTime("$fechafinalSinTiempo"); // Fecha de fin en formato "YYYY-MM-DD"

	$intervalo = date_diff($fecha_inicio, $fecha_fin); // Calcula la diferencia entre las dos fechas

	$total_dias = $intervalo->days; // Obtiene el total de días entre las dos fechas

	$total_quincenas = floor($total_dias / 14); // Calcula el total de quincenas redondeando hacia abajo

	$retegarantiaTotal=$total_quincenas*$cargosaldo[2];

	

	if ($rw1[17]==$retegarantiaTotal) {
		# code...
	}else {
		
	}


	
	$descuentoReteGarantia_formateado = number_format($descuentoReteGarantia, 0, ',', '.');
	$ReteGarantiaMostrar=" $descuentoReteGarantia_formateado <br> <button class='btn btn-primary' onclick='pop_Retegarantiaresta($rw1[5],$idusuario,\"$fechaactual\",$rw1[17])' id='enviarIds'>Descontar</button>";
}


//TOTAL QUINCENA
			$totalQuincenaParaCuenta=$valordediastrabajados;
			

			$totalQuincena=($valordediastrabajados+$valorTotalHora+$valorRecogidas+$totalOtrosDias)-($TotalPagoQuincena+$descuentoReteGarantia);

			//Cargo
			echo "<td>".$cargosaldo[1]."</td>
			<td>".$cargosaldo[2]."</td>";//Salario por dia
			echo "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($idusuario,\"Resumen_Quincena\",\"$fechas\")';  title='Ingreso de Usuario' >$diassitrabajo Dias </td>"; //Dias Trabajados

			echo "<td>".$valordediastrabajados."</td>";//Valor dias trabajados
			echo "<td>".$rw6[0]."</td>";//Horas trabajadas
			echo "<td>".$valorTotalHora_formateado."</td>";//Valor Horas trabajadas

			  echo "<td  >$cantRecogidas </td>";//Recogidas
			  echo "<td  >$ $valorRecogidas_formateado</td>";//Valor recogidas

			  echo "<td>$".$totalOtromes_formateado."</td>"; //Otros por dia
			  echo "<td>$".$totalOtrosDias_formateado."</td>"; //Valor otros hasta la fecha


			echo "<td>$".$TotalDebe."</td>"; //Prestamos y Descuadres

		    echo "<td> <a id='link'  onclick='pop_dis16($idusuario,\"Abono_A_Deuda\",\"$rw1[13]\")';  title='Click para agregar un abono' >$TotalPagoQuincena_formateado +</a></td>";//abonos a deudas
		
			$TotalDevengado_formateado=$TotalPagoQuincena_formateado;

			$seledevolvioR="SELECT nom_devolRetegara,nom_retedes from nomina where nom_id_usu='$idusuario'  and nom_tipo_pago='Basico'  and nom_devolRetegara>0 ";
			$DB1->Execute($seledevolvioR);
			
			$devuelR=mysqli_fetch_row($DB1->Consulta_ID);
			if(empty($devuelR)){

				$devolver=false;
			}else{
				$devolver=true;
			}





			// if () {
			// 	# code...


			$colorselect="#8B0000";
			$si="";
			$no="";
			$imagencompr="";
			$linkbasico="";
			$textEnviar="Enviar";
			$colorEnviar="rgb(7, 79, 145)";
			$validado="";
			$Observacion="";
			$cheked1="";
			$botonEnviar1="none";
			$confirmado1="";
			$validadoDesprendible="";
			$devretegarantia=0;

			$tablaNomina="SELECT nom_confirma,nom_img_compro,nom_cuentaCobro,nom_confirmaUsu,nom_motivoObser,nom_fechaconfirmaUsus,`nom_confiAdmi`,`nom_fechaConfiAdmi`,nom_confirmaAdmin,nom_devolRetegara,nom_valor_ajuste1,nom_tipo_ajuste1,nom_ajuste_descripcion1,nom_id from nomina where nom_id_usu='$idusuario' and nom_fecha_inicio='$fechaactual' and nom_tipo_pago='Basico'  ";
			$DB1->Execute($tablaNomina);
			
			while($Nomina=mysqli_fetch_row($DB1->Consulta_ID))
			{
				$Nomina[13];
				$valorAjusteB=$Nomina[10];
				$tipoAjusteB=$Nomina[11];
				$descripcionAjusteB=$Nomina[12];
				$devretegarantia=$Nomina[9];
			 
			  $imagencompr=$Nomina[1];
			  if ($Nomina[0]=="Si") {
				  $colorselect="#28B463";
				  $si="selected";
				  $no="";
				  $linkbasico="auto";
			  }else if($Nomina[0]=="No"){
				  $si="";
				  $no="selected";
				  $colorselect="#8B0000";
				  $linkbasico="none";
			  }else{
				  $si="";
				  $no="selected";
				  $colorselect="#8B0000";
				  $linkbasico="none";
			  }
			  if($Nomina[2]==""){

				$textEnviar="Enviar";
				$colorEnviar="rgb(7, 79, 145)";
			}else{

				$colorEnviar="#28B463";
				$textEnviar="Reenviar";
			}

			if ($Nomina[3]=="Si") {

				$validadoDesprendible="Validado el $Nomina[5]  Por ".$rw1[1]." ".$rw1[2]." ";
				$validado="Validado✅";
			}else {
				$validado="Pendiente";
			}

			if ($Nomina[4]!="") {
				$Observacion="<textarea readonly name='' rows='' cols=''>$Nomina[4]</textarea>";
			}else {
				$Observacion="";
			}
			if ($Nomina[8]=="si") {
				$cheked1="checked";
				$botonEnviar1="inline-block";
				$user1="SELECT `usu_nombre` FROM `usuarios` WHERE `idusuarios`='$Nomina[6]' ";
				$DB1->Execute($user1);
				$nombre1=$DB1->recogedato(0);
				$confirmado1="Por $nombre1 <br> $Nomina[7]";
			}else {
				$cheked1="";
				$botonEnviar1="none";
				$confirmado1="";
			}

			}
			$ajustessumB=0;
			$ajustesresB=0;
			if ($tipoAjusteB=="suma") {
				$ajustessumB=$valorAjusteB;
				$ajustesresB=0;
			}else if($tipoAjusteB=="descuento"){
				$ajustessumB=0;
				$ajustesresB=$valorAjusteB;
			}
						
						echo "<td  >$ReteGarantiaMostrar  </td>";//ReteGarantia

						echo "<td>";
						if($devolver==true){


						}else if($devolver==false){

						echo "<div id='$rw1[5]'>$rw1[17]</div> <br> <button class='btn btn-primary' onclick='pop_Retegarantia($idusuario,\"Devolver Retegarantia\",\"$fechaactual\",$rw1[17])' id='enviarIds'>Devolver</button></a>";

						}

						echo "</td>";//Devolver retegarantia


$totalQuincena=($totalQuincena+$devretegarantia+$ajustessumB)-($ajustesresB+$valordiasConSancion);
						$totalQuincena_formateado = number_format($totalQuincena, 0, ',', '.');
						echo "<td style='background-color:#F4D03F' >$totalQuincena_formateado</td>";//Valor quincena
						$valorAjusteB=$Nomina[10];
						$tipoAjusteB=$Nomina[11];
						$descripcionAjusteB=$Nomina[12];
			$rutaDeComp="desprendibleOtros.php?cedula=".$rw1[5]."&nombre=".$rw1[1].$rw1[2]."&valor=".$totalQuincenaParaCuenta."&deudas=".$TotalPagoQuincena."&fechaini=".$fechaactualSinTiempo."&fechafin=".$fechafinalSinTiempo."&recogidas=".$cantRecogidas."&valorRecogidas=".$valorRecogidas."&otrosdeve=".$totalOtrosDias."&valorHoras=".$valorTotalHora."&confirmado=".$validadoDesprendible."&reteGarantiaLleva=".$retegarantiaTotal."&reteGarantiaDesc=".$descuentoReteGarantia."&firma=".$rw1[16]."&llevaRetegarantia=".$rw1[17]."&devolretegarantia=".$devretegarantia."&valorAjuste=".$valorAjusteB."&tipoAjuste=".$tipoAjusteB."&descripcionAjuste=".$descripcionAjusteB."&diassancion=".$diasConSancion."&valorSancion=".$valordiasConSancion."&descriprestamos=".$descripcionBasico;
			echo "<td><a  target='_blank' href='$rutaDeComp'>ver</a>
			<button style='display: $botonEnviar1; width:120px;border:1px solid #f9f9f9;background-color:".$colorEnviar.";color:#f9f9f9;font-size:15px' id='Basico".$idusuario."guardarCuenCobro' onclick='enviarDesprendible(\"$rutaDeComp\",$idusuario,\"$fechaactual\",\"$fechafinal\",\"guardarCuenCobro\",\"Basico\")'>$textEnviar</button>
			<input  type='checkbox' id='Basico".$idusuario."confirmaAdmin1' onchange='confirmaAdmin($idusuario,\"$fechaactual\",\"$fechafinal\",\"confirmaAdmin\",\"Basico\",1)' $cheked1>
			<label for='miCheckbox'>
			<details>
			<summary>Confirmado</summary>
				<p>$confirmado1<p/>
	        </details>
			</label>
			</td>";//Cuenta de cobro

			echo "<td>".$validado.$Observacion."</td>";//Confirmacion Usuario

		    echo "<td> <a id='link'  onclick='pop_dis16($idusuario,\"Ajustes_nomina\",\"$rw1[13]\")';  title='Click para agregar un abono' >Agregar +</a></td>";//ajustes

			echo "<td><div id='campo'>";
			echo "<select  style='width:120px;border:1px solid #f9f9f9;background-color:".$colorselect.";color:#f9f9f9;font-size:15px'  name='$va' id='".$idusuario."Basico' onchange='confirmarPago($idusuario,\"$fechaactual\",\"$fechafinal\",\"confirmarPago\",this.value,\"Basico\")' class='borrar' required>";

			echo"<option value='no' $no>NO</option>";
			echo"<option value='Si'$si>SI</option>";


			echo"</select>";
		  
			//Comprobante
			  if ($imagencompr=="") {
				  echo "<td><a id='Basico".$idusuario."' style='pointer-events: ".$linkbasico.";' onclick='pop_dis16($idusuario,\"Comprobante_nomina_basico\",\"$fechaactual\")';  title='Ingreso de Usuario' >Cargar</td>";
				}else{
				  echo "<td><a href='https://sistema.transmillas.com/img_nomina/$imagencompr' style='display: block;' target='_blank' title='Ver comprovante de pago de nomina' >Ver</a>";
				  echo "<a id='Basico".$idusuario."' style=' display: block; pointer-events: ".$linkbasico.";' onclick='pop_dis16($idusuario,\"Comprobante_nomina_basico\",\"$fechaactual\")';  title='Ingreso de Usuario' >Cambiar</a></td>";

				}


			


			echo "<td>$rw1[6]</td>";//Inicio contrato
			echo "<td>$rw1[13]</td>";//Termina contrato
			$totalDevengPres=$totalDevengPres+$totalQuincena;
			echo"<td><input type='checkbox'  onchange='selecionado2(
				$idusuario,
				\"$nombreCompleto\",
				\"$rw1[5]\",
				\"$diassitrabajo\",
				\"$valordediastrabajados\",
				\"$valorTotalHora_formateado\",
				\"$valorRecogidas_formateado\",
				\"$otropordia\",
				\"$totalOtrosDias_formateado\",
				\"$TotalDebe\",
				\"$TotalPagoQuincena_formateado\",
				\"$ReteGarantiaMostrar \",
				\"$totalQuincena_formateado\")' class='check_hijo' id='".$idusuario."s1' value='$idusuario'></td>";
		  }

		}
		?>
		<div id="datosUsuarios" data-usuarios='<?= json_encode(array_values($idsUsu)) ?>'style="display:none;"></div>

		<?php
		$totalDevengPres_formateado = number_format($totalDevengPres, 0, ',', '.');

		$FB->titulo_azul1(" Totales :",1,0,10);
		$FB->titulo_azul1(" $va",1,0,0);

		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);

		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" $totalDevengPres_formateado ",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);


}else{






	function diasSegundaQuincena($year, $month) {
		// Obtener el último día del mes
		$ultimoDiaMes = date("t", strtotime("$year-$month-01"));

		// La segunda quincena empieza el día 16
		$inicioSegundaQuincena = 16;

		// Calcular el número de días en la segunda quincena
		$diasSegundaQuincena = $ultimoDiaMes - $inicioSegundaQuincena + 1;

		return $diasSegundaQuincena;
	}






// $FB->titulo_azul1("#",1,0,7);
// $FB->titulo_azul1("",1,0,7);

// $FB->titulo_azul1("Trabajador",1,0,7);
echo "<table class='table table-hover'><tr bgcolor='#074F91' class='tittle3' >";
$FB->titulo_azul1("Id",1,0,0);
echo "<td colspan='0' width='0' align='center'>Trabajador <br>Todo<input type='checkbox' id='check_todos' onclick='seleccionarTodos()' ></td>";

$FB->titulo_azul1("Tipo Contrato",1,0,0);
$FB->titulo_azul1("Cedula",1,0,0);
$FB->titulo_azul1("Cargo",1,0,0);
$FB->titulo_azul1("Salario por mes",1,0,0);
$FB->titulo_azul1("Auxilio",1,0,0);

$FB->titulo_azul1("Valor por dia",1,0,0);
$FB->titulo_azul1("Dias ",1,0,0);
$FB->titulo_azul1("Descansó",1,0,0);
$FB->titulo_azul1("Valor total dias basico",1,0,0);
// $FB->titulo_azul1("Valor dias descanso",1,0,0);



$FB->titulo_azul1("Valor dias con auxilio",1,0,0);




$FB->titulo_azul1("Dias No Trabajados",1,0,0);

$FB->titulo_azul1("Dias de Incapacidad Empresa",1,'5%',0);
$FB->titulo_azul1("Dias de Incapacidad EPS",1,'5%',0);

$FB->titulo_azul1("Valor dias de incapacidad",1,'5%',0);
// $FB->titulo_azul1("Dias de Incapacidad %",1,'5%',0);
// $FB->titulo_azul1("Valor dias de incapacidad %",1,'5%',0);
$FB->titulo_azul1("Dias de vacaciones",1,'5%',0);
$FB->titulo_azul1("Valor",1,'5%',0);
$FB->titulo_azul1("Licencias y permisos",1,'5%',0);
$FB->titulo_azul1("Valor",1,'5%',0);
$FB->titulo_azul1("Descuento Salud",1,'5%',0);
$FB->titulo_azul1("Descuento Pension",1,'5%',0);
$FB->titulo_azul1("Prestamos y Descuadres",1,0,0);
$FB->titulo_azul1("Abonos a deudas",1,0,0);
$FB->titulo_azul1("Valor quincena",1,'5%',0);

// $FB->titulo_azul1("Pagado",1,'5%',0);
// $FB->titulo_azul1("Comprobante",1,'5%',0);
$FB->titulo_azul1("Desprendible de nomina",1,'5%',0);
$FB->titulo_azul1("Confirmado",1,'5%',0);
$FB->titulo_azul1("Horas ",1,'5%',0);
$FB->titulo_azul1("Valor horas trabajadas",1,'5%',0);
$FB->titulo_azul1("Horas dom/fest",1,'5%',0);
$FB->titulo_azul1("Valor horas dom/fest",1,'5%',0);
$FB->titulo_azul1("Otros valor mes",1,0,0);
$FB->titulo_azul1("Valor total dias otros",1,0,0);
$FB->titulo_azul1("Recogidas",1,0,0);
$FB->titulo_azul1("Valor recogidas",1,0,0);
$FB->titulo_azul1("Abonos a deudas",1,0,0);
$FB->titulo_azul1("Valor total a pagar Otros",1,0,0);

$FB->titulo_azul1("Cuenta de cobro",1,'5%',0);
$FB->titulo_azul1("Confirmacion Usuario",1,'5%',0);
// $FB->titulo_azul1("Imprimir",1,'5%',0);
$FB->titulo_azul1("Total Todo",1,'5%',0);
$FB->titulo_azul1("Ajustes",1,'5%',0);
$FB->titulo_azul1("Pagado",1,'5%',0);
$FB->titulo_azul1("Comprobante",1,'5%',0);
// $FB->titulo_azul1("Prima",1,'5%',0);
// $FB->titulo_azul1("Cesantia",1,'5%',0);
// $FB->titulo_azul1("Desripcion Paz y Salvo",1,'5%',0);
// $FB->titulo_azul1("Paz y Salvo Vehiculo",1,'5%',0);
$FB->titulo_azul1("Inicio contrato",1,'5%',0);
$FB->titulo_azul1("Termina contrato",1,'5%',0);
	$FB->titulo_azul1("Seleccion",1,'5%',0);



	if($param34 == 2 and $param36=='Segunda'){
		if($fin==29){
			$diasParaSumar=1;

		}else{
			$diasParaSumar=2;
		}


	}else{

		$diasParaSumar=0;
	}



// $idsUsu[] = "";
$tabla="";
$conde0="and(hoj_fechatermino IS NULL OR hoj_fechatermino = '' OR ('$fechaactualSinTiempo' BETWEEN hoj_fechaingreso AND hoj_fechatermino ) OR (hoj_fechaingreso BETWEEN '$fechaactualSinTiempo' AND '$fechafinalSinTiempo'AND hoj_fechatermino BETWEEN '$fechaactualSinTiempo' AND '$fechafinalSinTiempo'))";

if($param36=='Completo') {
	$conde0="";
}
$sql="SELECT `idhojadevida`,  `hoj_nombre`, `hoj_apellido`,hoj_cargo, `hoj_tipocontrato`,`hoj_cedula`,`hoj_fechaingreso`, `sed_nombre`,`hoj_fechanacimiento`, `hoj_cedula`,`hoj_direccion`, `hoj_celular`, `hoj_estado`,hoj_sede,hoj_fechatermino,hoj_cuen,hoj_tcuenta,hoj_firma,hoj_estado,hoj_fech_año_act,hoj_fechacontrato,hoj_fech_año_act FROM hojadevida
INNER JOIN sedes ON hoj_sede = idsedes
WHERE idhojadevida > 0  $conde0 $conde5 $conde4 $conde
ORDER BY hoj_nombre ASC";
  $DB->Execute($sql);
//   $va=(($compag-1)*$CantidadMostrar);
  $va=0;
	while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	{
        $va++;


		$totaldevengado=0;
		$totaldeduccion=0;
		$fechafin=$fechafinal;
		if($rw1[6]>=$fechaactual and $rw1[6]<=$fechafinal){
            // echo$rw1[6].$rw1[1];
			$mesdeingreso=true;
			$fechaAhora=$rw1[6];
		}else{
			$mesdeingreso=false;
			$fechaAhora=$fechaactual;
		}

		// and  usu_estado = '1' and usu_filtro='1'
		  $user="SELECT `idusuarios` FROM `usuarios` WHERE `usu_identificacion`='$rw1[5]' and usu_ver_nomina='1'";
		  $DB1->Execute($user);
		  $idusuario=$DB1->recogedato(0);

		  $idsUsu[] = $idusuario;




		if ($rw1[14]==null or $rw1[14]=="0000-00-00") {

			$mesiniciocontrato=date("m", strtotime($rw1[6]));
			$añoiniciocontrato=date("Y", strtotime($rw1[6]));

			// echo"MES ".$sigmesiniciocontrato=date("m", strtotime($mesiniciocontrato. " +1 month"));

			$priemrdiadestemes=date("Y-m-d H:i:s", strtotime($añoiniciocontrato.'-'.$mesiniciocontrato.'-01'.' 00:00:00'."+1 month"));

			if($param36=='Completo'){
				$diaveinte=date("Y-m-d H:i:s",strtotime($añoiniciocontrato.'-'.$mesiniciocontrato.'-30'.' 23:59:59'."+31 days"));

			}else{

				$diaveinte=date("Y-m-d H:i:s",strtotime($añoiniciocontrato.'-'.$mesiniciocontrato.'-15'.' 23:59:59'."+1 month"));
			}

			if (($fechaactual>=$priemrdiadestemes and $fechafinal<=$diaveinte)) {
				$color="#00bf19";
			}else{

				if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
			}


			$terminaContrato="";
			$InicioContrato=$rw1[6];
			if($InicioContrato<=$fechafinal and $rw1[18] == 'Activo') {
				$activoEnNomina=true;
			}else{
				$activoEnNomina=false;

			}

		}else{

			$terminaContrato=$rw1[14];
			$mesterminocontrato=date("m", strtotime($rw1[14]));
			$añoterminocontrato=date("Y", strtotime($rw1[14]));
			$diaterminocontrato=date("d", strtotime($rw1[14]));


			$priemrDiaQuinceTermina=date("Y-m-d H:i:s", strtotime($añoterminocontrato.'-'.$mesterminocontrato.'-01'.' 00:00:00'));


			if ($fechaactual>= $priemrDiaQuinceTermina ) {

				$color="#D35400";

			}else{

				if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}

			}


			$nueva_fechaTerminaContrato = date("Y-m-d", strtotime($terminaContrato . "+30 days"));

			if ( $fechafinal<= $nueva_fechaTerminaContrato ) {
				if ($rw1[6]>=$fechafinal) {

					$activoEnNomina=false;
				}else{
					$activoEnNomina=true;
					
				}

			}else{
				$activoEnNomina=false;


			}


		
		}

		if ($activoEnNomina) {

			$id_p=$rw1[0];



		  	if(empty($idusuario)){


		  	}else{



					// echo "<td><input type='checkbox'  onchange='selecionado($idusuario)' class='checkbox' id='".$idusuario."s' value='$idusuario'></td>";
					$tabla.="<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
					$tabla.="<td>".$idusuario."</td>";
					$tabla.="<td>".$rw1[1]." ".$rw1[2]."</td>";
					// <input type='checkbox'  onchange='selecionado($idusuario)' class='checkbox' id='".$idusuario."s' value='$idusuario'>
					$tabla.="<td>".$rw1[4]."</td>";
					$tabla.="<td>".$rw1[5]."</td>";


					$va++; $p=$va%2;

					$valordediastrabajados=0;
					$sql2="SELECT  `idcargo`, `car_Cargo`, `salario`, `auxilio`, `otros`,car_Recogida,car_ValorRecogida,des_salud,des_pension FROM `cargo`INNER JOIN salarios_cargos on idcargo=id_relCargo  WHERE idcargo='$rw1[3]' and anio='$ano'";
					$DB1->Execute($sql2);
					$cargosaldo=mysqli_fetch_row($DB1->Consulta_ID);
					if($idusuario>=1){
						$prestamo=0;
						$descuadre=0;
						$pago=0;
						$malenviados=0;
						
						//Prestamos
						$slq3="SELECT deu_tipo, deu_valor FROM `duedapromotor` WHERE  deu_idpromotor='$idusuario'  ";
						$DB1->Execute($slq3);
						while($prestamostotal=mysqli_fetch_row($DB1->Consulta_ID))
						{
							if ($prestamostotal[0]=="Prestamos") {
								$prestamo =$prestamo+$prestamostotal[1];
							}elseif ($prestamostotal[0]=="Descuadre") {
								$descuadre =$descuadre+$prestamostotal[1];
							}elseif ($prestamostotal[0]=="Pagos") {
								$pago =$pago+$prestamostotal[1];
							}elseif ($prestamostotal[0]=="MalEnviados") {
								$malenviados =$malenviados+$prestamostotal[1];
							}

						}


						// echo"Pagado".$pago;
						$prestamoTotal= $prestamo+$descuadre+$malenviados;
						$TotalDebe = $prestamoTotal-$pago;

						//Pagos a la 15na, de prestamos
						$pago1=0;
						$restaAOtros=0;
						$restaABasico=0;
						$descripcionBasico="";
						$descripcionOtros="";
						$slq7="SELECT deu_tipo, deu_valor,deu_pago_de,due_descripcion  FROM `duedapromotor` WHERE  deu_idpromotor='$idusuario' and deu_fecha>='$fechaAhora' and deu_fecha<='$fechafin' and deu_pago_de in ('Basico','otros') ";
						$DB1->Execute($slq7);
						while($prestamostotal7=mysqli_fetch_row($DB1->Consulta_ID))
						{
							if ($prestamostotal7[0]=="Pagos") {
								$pago1 =$pago1+$prestamostotal7[1];

								if ($prestamostotal7[2]=="Basico") {
									$restaABasico=$restaABasico+$prestamostotal7[1];
									$descripcionBasico=$descripcionBasico.", $prestamostotal7[3]";
								}elseif($prestamostotal7[2]=="Otros"){

									$restaAOtros=$restaAOtros+$prestamostotal7[1];
									$descripcionOtros=$descripcionOtros.", $prestamostotal7[3]";
								}
							}

						}



						$TotalPagoQuincena = $pago1;
						$TotalPagoQuincena_formateado = number_format($TotalPagoQuincena, 0, ',', '.');

						//Dias no trabajados
						$diasnotrabajo=0;
						$notrabajo="SELECT seg_fechaingreso FROM `seguimiento_user`  where seg_motivo in ('Se devolvio','Sancionado','No trabajo','descanso no remunerado','Reposicion por falla') and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'   and seg_idusuario='$idusuario' ";
						$DB1->Execute($notrabajo);


						while($rw2=mysqli_fetch_row($DB1->Consulta_ID))
						{

							$diasnotrabajo=$diasnotrabajo+1;
							// $fechasNoTrabajo_array[] = $rw2[0];

						}






						//Dias de Descanso
						$condicionDomingos = obtenerCondicionDomingos('seg_fechaingreso', $fechaAhora, $fechafin);
						$descansopago = "
						SELECT SUM(total)
						FROM (
							SELECT COUNT(*) AS total
							FROM seguimiento_user
							WHERE seg_motivo = 'descanso'
							AND seg_fechaingreso >= '$fechaAhora'
							AND seg_fechaingreso <= '$fechafin'
							AND seg_idusuario = '$idusuario'

							UNION ALL

							SELECT COUNT(*) AS total
							FROM seguimiento_user
							WHERE seg_motivo = 'IngresoHoras'
							AND seg_fechaingreso >= '$fechaAhora'
							AND seg_fechaingreso <= '$fechafin'
							AND seg_idusuario = '$idusuario'
							AND ($condicionDomingos)
						) descansos
						";

						$DB1->Execute($descansopago);
						$rw6 = mysqli_fetch_row($DB1->Consulta_ID);

						$diasDescanso = !empty($rw6) ? $rw6[0] : 0;

						$valorDeDiasDeDescanso=$diasvalor*$diasDescanso;
						$valorDeDiasDeDescanso_formateado = number_format($valorDeDiasDeDescanso, 0, ',', '.');


						//Dias trabajados


						// Ejemplo de uso
						$year=date("Y", strtotime($fechaAhora));
						$month=date("m", strtotime($fechaAhora));
						$day=date("d", strtotime($fechaAhora));
						$diasSegundaQuincena = diasSegundaQuincena($year, $month);

						//  echo "La segunda quincena de $year-$month tiene $diasSegundaQuincena días.";






						$diassitrabajo=0;
						$sitrabajo="SELECT count(*) FROM `seguimiento_user`  where seg_motivo ='Ingreso' and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'  and seg_idusuario='$idusuario' ";
						$DB1->Execute($sitrabajo);
						$rw4=mysqli_fetch_row($DB1->Consulta_ID);
						if(empty($rw4)){

							$diassitrabajo=0;
						}else{
							if ($rw1[0]==770) {
                                $rw4[0]=9;
								
                            }


							if($fin==31 and $param36=='Segunda' or $fin==31 and $param36=='Completo' ){


									if ($rw4[0]<=1 ) {

										$dia31=0;

										# code...
									}else{
										if ($param36=='Segunda') {

											if ($diasSegundaQuincena=="16" ) {

												$mesiniciocontrato=date("m", strtotime($rw1[6]));
												$añoiniciocontrato=date("Y", strtotime($rw1[6]));
												$diainiciocontrato=date("d", strtotime($rw1[6]));



											
												if ($rw1[14]==null or $rw1[14]=="0000-00-00") {


												}
												if(($mesiniciocontrato==$month and $añoiniciocontrato==$year and $diainiciocontrato>=16 and $diasnotrabajo<=0)){
													if ($diainiciocontrato==16 and $diainiciocontrato!=31) {
														$dia31=1;
														echo"1";
													}else {
														$dia31=0;
													}

												}else if($rw1[14]!=null or $rw1[14]!="0000-00-00") {
													$mesfincontrato=date("m", strtotime($rw1[14]));
													$añofincontrato=date("Y", strtotime($rw1[14]));
													$diafincontrato=date("d", strtotime($rw1[14]));

													// echo"FIN if ($mesfincontrato==$month and $añofincontrato==$year and $diafincontrato>=16 )";
													if ($mesfincontrato==$month and $añofincontrato==$year and $diafincontrato>=16  ) {
														$dia31=0;
													}else {
														$dia31=1;
														
													}

												}else{
													$dia31=1;
													
												}



											}else{

												// echo"segunda quince con 16 dias";
												$dia31=0;
											}
											// echo"segunda quince con 16 dias";
											// $dia31=1;	# code...
										}else if($param36=='Completo') {

											if (($rw4[0]+$diasDescanso)==31) {
												$dia31=1;
												// echo"es 16 dias";
												# code...
											}else{

												// echo"segunda quince con 16 dias";
												$dia31=0;
											}
											// $dia31=1;# code...
											// $Tuno="Si";
										}

									}
									if (($rw4[0]+$diasDescanso)==0) {
										$diasnotrabajo=0;
									}else if (($rw4[0]+$diasDescanso)==0) {
										# code...
									}


									$diassitrabajo=$rw4[0]+$diasDescanso-($dia31);
									$diassitrabajoParaSumar=$rw4[0]+$diasDescanso-($dia31);
									$diassitrabajoConAuxilio=$rw4[0];
									$diassitrabajoParaMostrar=$rw4[0]+$diasDescanso-($dia31);
									// echo"$diassitrabajoParaMostrar=$rw4[0]+$diasDescanso-($dia31)";

								// }

							}elseif($fin==29 and $param36=='Segunda' or $fin==29 and $param36=='Completo' ){


									if ($rw4[0]<=0 or $mesdeingreso==true){
										$dia29=0;

										# code...
									}else{


										$dia29=1;
									}

									$diassitrabajo=$rw4[0]+$diasDescanso+$dia29;
									$diassitrabajoParaSumar=$rw4[0]+$diasDescanso+$dia29;
									$diassitrabajoConAuxilio=$rw4[0]+$dia29;
									$diassitrabajoParaMostrar=$rw4[0]+$diasDescanso;
								// }

							}elseif($fin==28 and $param36=='Segunda' or $fin==28 and $param36=='Completo' ){

								if ($rw4[0]<=0 or $mesdeingreso==true){
									$dia29=0;
								}else{
									$dia29=2;
								}

								$diassitrabajo=$rw4[0]+$diasDescanso+$dia29;
								$diassitrabajoParaSumar=$rw4[0]+$diasDescanso+$dia29;
								$diassitrabajoConAuxilio=$rw4[0]+$dia29;
								$diassitrabajoParaMostrar=$rw4[0]+$diasDescanso;

							}else{

										$diassitrabajo=$rw4[0]+$diasDescanso;
										$diassitrabajoParaSumar=$rw4[0]+$diasDescanso;
										$diassitrabajoConAuxilio=$rw4[0];
										$diassitrabajoParaMostrar=$rw4[0]+$diasDescanso;
							}

							if ($rw1[1]=="EDGAR EDUARDO CONDUC") {
								$diassitrabajoParaSumar=$diassitrabajoParaSumar-1;
								$diassitrabajoParaMostrar=$diassitrabajoParaMostrar-1;
							}

				}


				$diasvalor=($cargosaldo[2]/30);
				$valordediastrabajados=$diasvalor*$diassitrabajoParaSumar;
				$valordediastrabajados_formateado = number_format($valordediastrabajados, 0, ',', '.');

				//Permisos y licencias
				$permisosLic=0;
				$nombreMotivo="";
				$valorPermisosLicBasico=0;
				$diasPerLicBas=0;
				$diasvalorporcentaje=0;
				$diasPerLicBasValor=0;
				$valorMitaddiasPerLi=0;
				$diasPerLicBasValortotal=0;
				$diasPerLicBasValortotalfinal=0;
				$valorPermisosLicSalud=0;
				$valorPermisosLicPension=0;
				$permisoLicencia="SELECT `seg_motivo`, `seg_descr`, `mot_salud`, `mot_pension`, `mot_auxtransporte`, `mot_porcbasico`, `mot_otrosDevengos`  FROM `seguimiento_user` INNER JOIN motivo_ingreso on mot_nombre=seg_motivo  where seg_motivo in('licencia de maternidad','LICENCIA POR LUTO','PERMISO NO REMUNERADO','PAGO DE INCAPACIDAD AL 66') and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'  and seg_idusuario='$idusuario' ";
				$DB1->Execute($permisoLicencia);

				$con=0;
				while($rw9=mysqli_fetch_row($DB1->Consulta_ID))
				{

					if(empty($rw9)){

						$permisosLic=0;
					}else{

						$permisosLic=$permisosLic+1;
						$nombreMotivo=$rw9[0];

						if ($rw9[2]=="si" or $rw9[2]=="SI" ) {
							$valorPermisosLicSalud=$valorPermisosLicSalud+1;
						}
						if ($rw9[3]=="si" or $rw9[3]=="SI") {
							$valorPermisosLicPension=$valorPermisosLicPension+1;
						}
						if($rw9[4]=="si" or $rw9[4]=="SI"){
							$valorPermisosLicAux=$valorPermisosLicAux+1;

						}
						if($rw9[6]=="si" or $rw9[6]=="SI"){
							$valorPermisosLicOtros=$valorPermisosLicOtros+1;
						}

						if($rw9[5]!="0"){
							$diasPerLicBas=$diasPerLicBas+1;




								$rw9[5];
								$valorporcentaje=($rw9[5]/100)*$diasvalor;


							if ($rw9[0]=="PAGO DE INCAPACIDAD AL 66 ") {
								// echo"si es";
								if ($con==2) {
									$diasPerLicBasValortotalfinal=$diasPerLicBasValortotalfinal;
								}else {
									$diasPerLicBasValortotalfinal=$diasPerLicBasValortotalfinal+$valorporcentaje;

								}
							}else {
								// echo"no es";
								$diasPerLicBasValortotalfinal=$diasPerLicBasValortotalfinal+$valorporcentaje;

							}


						}


					}

					$con=$con+1;

				}





				$diasPerLicBasValortotalfinal_formateado = number_format($diasPerLicBasValortotalfinal, 0, ',', '.');

				//Incapacidades

				$incapacidad="SELECT count(*) FROM `seguimiento_user`  where seg_motivo ='Incapacidad' and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'  and seg_idusuario='$idusuario' ";
				$DB1->Execute($incapacidad);
				$rw5=mysqli_fetch_row($DB1->Consulta_ID);
				if(empty($rw5)){

						$diasincapacidad=0;

				}else{
					$diasincapacidad=$rw5[0];
					// excepcion con usuario 423 andres
					if ($idusuario=="1718" and $rw5[0]==0 and $fin ==29) {


						// echo"OKKK";
						$diasincapacidad=1;
					}
				}


				//Incapacidades EPS

				$inceps="SELECT count(*) FROM `seguimiento_user`  where seg_motivo ='Incapasidad paga por la EPS' and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'  and seg_idusuario='$idusuario' ";
				$DB1->Execute($inceps);
				$ineps=mysqli_fetch_row($DB1->Consulta_ID);
				if(empty($ineps)){

						$diasincapacidadEPS=0;

				}else{
					$diasincapacidadEPS=$ineps[0];
					// excepcion con usuario 423 andres
				}

				// if($diasincapacidad>=2){
				// 	$valorDiasIncapadidad=($diasvalor)*(2*0.6667);

				// 	$valorDiasIncapadidad_formateado = number_format($valorDiasIncapadidad, 0, ',', '.');

				// }else{

					$valorDiasIncapadidad=($diasvalor)*($diasincapacidad*0.6667);

					$valorDiasIncapadidad_formateado = number_format($valorDiasIncapadidad, 0, ',', '.');
				// }



				//dias vacaBasicos
				$diasVacaBasic="SELECT count(*) FROM `seguimiento_user`  where seg_motivo ='Festivo en vacaciones' and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'  and seg_idusuario='$idusuario' ";
				$DB1->Execute($diasVacaBasic);
				$rw11=mysqli_fetch_row($DB1->Consulta_ID);
				$valorDiasBasico=$rw11[0]*$cargosaldo[2];



				//VACACIONES

				$Vacaciones="SELECT count(*) FROM `seguimiento_user`  where seg_motivo ='Vacaciones' and seg_fechaingreso>='$fechaAhora' and seg_fechaingreso<='$fechafin'  and seg_idusuario='$idusuario' ";
				$DB1->Execute($Vacaciones);
				$rw8=mysqli_fetch_row($DB1->Consulta_ID);
				if(empty($rw8)){

					$diasVacaciones=0;
				}else{
					$diasVacaciones=$rw8[0];
				}



				$valorDiasVacaciones=($diasvalor)*($diasVacaciones);

				$valorDiasVacaciones_formateado = number_format($valorDiasVacaciones, 0, ',', '.');



				$horasNormales = 0;
				$horasDomini = 0;
				$resultadoHoras = obtenerHorasNormalesYFestivas($DB1, $idusuario, $fechaAhora, $fechafin);

				$horasNormales = $resultadoHoras['horas_normales'];
				$horasDomini = $resultadoHoras['horas_festivas'];





					}else{
						$diasnotrabajo=0;
						$prestamostotal=0;
						$diasvalor=0;
					}




					//SALUD Y PENSION

					
					$Salud=$cargosaldo[7];
					$Pension=$cargosaldo[8];
					
					

					$saludPorDia=$Salud/30;
					$pensionPorDia=$Pension/30;

					if ($terminaContrato=="" and $mesdeingreso==false) {

						if ($diasVacaciones>0) {
							$valorSalud=$saludPorDia*($diassitrabajoParaSumar+$diasVacaciones+$valorPermisosLicSalud+$diasincapacidad+$diasincapacidadEPS);
							$valorPension=$pensionPorDia*($diassitrabajoParaSumar+$diasVacaciones+$valorPermisosLicPension+$diasincapacidad+$diasincapacidadEPS);
						}else {
							$valorSalud=$saludPorDia*($diassitrabajoParaSumar+$valorPermisosLicSalud+$diasincapacidad+$diasnotrabajo+$diasincapacidadEPS);
							$valorPension=$pensionPorDia*($diassitrabajoParaSumar+$valorPermisosLicPension+$diasincapacidad+$diasnotrabajo+$diasincapacidadEPS);
						}

					}else{
						if ($diasVacaciones>0) {
							$valorSalud=$saludPorDia*($diassitrabajoParaSumar+$diasVacaciones+$valorPermisosLicSalud+$diasincapacidad+$diasincapacidadEPS);
							$valorPension=$pensionPorDia*($diassitrabajoParaSumar+$diasVacaciones+$valorPermisosLicPension+$diasincapacidad+$diasincapacidadEPS);
						}else {
							$valorSalud=$saludPorDia*($diassitrabajoParaSumar+$valorPermisosLicSalud+$diasincapacidad+$diasnotrabajo+$diasincapacidadEPS);
							$valorPension=$pensionPorDia*($diassitrabajoParaSumar+$valorPermisosLicPension+$diasincapacidad+$diasnotrabajo+$diasincapacidadEPS);
						}


					}



					$valorSalud_formateado = number_format($valorSalud, 0, ',', '.');


					$valorPension_formateado = number_format($valorPension, 0, ',', '.');
					//Pago por dias

					//auxilio
					$auxilioPorDia=$cargosaldo[3]/30;
					//Total auxilio 15na
					$totalauxilio=$auxilioPorDia*($diassitrabajoParaSumar);

					//Total horas
					$valorHora=11140;
					$valorTotalHorasNormales=(float)$horasNormales*$valorHora;
					$valorTotalHorasDomini=(float)$horasDomini*$valorHora;



					$sedess="SELECT `usu_idsede` FROM `usuarios` WHERE `idusuarios`='$idusuario' ";
					$DB1->Execute($sedess);
					$id_sedes=$DB1->recogedato(0);

					$idcidades=ciudadesedes($id_sedes,$DB1);

					$cantRecogidas=0;
					$valorRecogidas=0;
					$entregas="SELECT count(*)FROM servicios inner join cuentaspromotor on cue_idservicio=idservicios inner join ciudades on ser_ciudadentrega=idciudades  where date(cue_fecharecogida)>='$fechaactual' and  date(cue_fecharecogida)<='$fechafinal' and (`cue_idoperador`= '$idusuario' or  `cue_idoperentrega`= '$idusuario' )and (cue_idciudadori in $idcidades )  ORDER BY ser_guiare  $asc ";
					$DB1->Execute($entregas);
					$rw10=mysqli_fetch_row($DB1->Consulta_ID);
					if ($cargosaldo[5]=="SI" or $cargosaldo[5]=="Si" or $cargosaldo[5]=="si") {
						$valorRecogidas=$rw10[0]*$cargosaldo[6];
						$cantRecogidas=$rw10[0];
					}else {
						$valorRecogidas=0;
						$cantRecogidas=0;
					}
					$valorRecogidas=$rw10[0]*$cargosaldo[6];
					$valorRecogidas_formateado = number_format($valorRecogidas, 0, ',', '.');
					//otros
					$otrosPorDia=$cargosaldo[4]/30;
					//Total auxilio 15na
					$totalOtrosDias=($otrosPorDia*($diassitrabajoParaSumar));

					$totalOtrosDias_formateado = number_format($totalOtrosDias, 0, ',', '.');



					$sueldo_formateado = number_format($cargosaldo[2], 0, ',', '.');
					$tabla.="<td>".$cargosaldo[1]." </td>";//Nombre del cargo

					$tabla.="<td>$".$sueldo_formateado."</td>";//Salario basico por mes
					$tabla.="<td>$".$cargosaldo[3]."</td>";//Auxilio por mes


					$diasvalor_formateado = number_format($diasvalor, 0, ',', '.');
					$tabla.="<td>$".$diasvalor_formateado."</td>";//Valor por dias


					$tabla.="<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($idusuario,\"Resumen_Quincena\",\"$fechas\")';  title='Ingreso de Usuario' >$diassitrabajoParaMostrar Dias  $suamdedias </td>"; //Ingreso?
					$tabla.="<td>".$diasDescanso."</td>";// dias de descanso
					$tabla.="<td>$".$valordediastrabajados_formateado."</td>";//Dias trabajados con dias de descanso




					$tabla.="<td>$".$totalauxilio."</td>";//total auxilio segun dias

					$tabla.="<td>".$diasnotrabajo."</td>";


					$tabla.="<td>".$diasincapacidad."</td>";
					$tabla.="<td>".$diasincapacidadEPS."</td>";
					$tabla.="<td>$valorDiasIncapadidad_formateado</td>";

		

					$tabla.="<td>$diasVacaciones</td>"; //VACACIONES

					$tabla.="<td>$valorDiasVacaciones_formateado</td>";//VACACIONES

					$tabla.="<td>".$nombreMotivo."<br>".$permisosLic." Dias</td>";
					$tabla.="<td>$diasPerLicBasValortotalfinal_formateado</td>";

					$tabla.="<td>$valorSalud_formateado</td>";

					$tabla.="<td>$valorPension_formateado</td>";

					$tabla.="<td>$".$TotalDebe."</td>"; //DEUDAS
					$tabla.="<td> <a id='link'  onclick='pop_dis16($idusuario,\"Abono_A_Deuda\",\"$rw1[13]\")';  title='Click para agregar un abono' >$$restaABasico +</a></td>";//abonos a deudas









					$colorselect="#8B0000";
					$si="";
					$no="";
					$imagencompr="";
					$linkbasico="";
					$textEnviar="Enviar";
					$colorEnviar="rgb(7, 79, 145)";
					$validado="";
					$Observacion="";
					$cheked1="";
					$botonEnviar1="none";
					$confirmado1="";
					$validadoDesprendible="";


					$tablaNomina="SELECT  nom_confirma,nom_img_compro,nom_cuentaCobro,nom_confirmaUsu,nom_motivoObser,nom_fechaconfirmaUsus,`nom_confiAdmi`,`nom_fechaConfiAdmi`,nom_confirmaAdmin,nom_valor_ajuste1,nom_tipo_ajuste1,nom_ajuste_descripcion1 from nomina where nom_id_usu='$idusuario' and nom_fecha_inicio='$fechaactual' and nom_tipo_pago='Basico'  ";
					$DB1->Execute($tablaNomina);

					while($Nomina=mysqli_fetch_row($DB1->Consulta_ID))
					{

					$valorAjusteB=$Nomina[9];
					$tipoAjusteB=$Nomina[10];
					$descripcionAjusteB=$Nomina[11];

						$imagencompr=$Nomina[1];


					if ($Nomina[0]=="Si") {
						$colorselect="#28B463";
						$si="selected";
						$no="";
						$linkbasico="auto";
					}else if($Nomina[0]=="No"){
						$si="";
						$no="selected";
						$colorselect="#8B0000";
						$linkbasico="none";
					}else{
						$si="";
						$no="selected";
						$colorselect="#8B0000";
						$linkbasico="none";
					}

					if($Nomina[2]==""){

						$textEnviar="Enviar";
						$colorEnviar="rgb(7, 79, 145)";
					}else{

						$colorEnviar="#28B463";
						$textEnviar="Reenviar";
					}

					if ($Nomina[3]=="Si") {

						$validadoDesprendible="Validado el $Nomina[5]  Por ".$rw1[1]." ".$rw1[2]." ";
						$validado="Validado✅ <br> $Nomina[5]";
					}elseif($Nomina[3]=="no"){
						$validado="Rechazada❌ <br> $Nomina[5]";
					}else{
						$validado="Pendiente";

					}
					if ($Nomina[4]!="") {
						$Observacion="<textarea readonly name='' rows='' cols=''>$Nomina[4]</textarea>";
					}else {
						$Observacion="";
					}

					if ($Nomina[8]=="si") {
						$cheked1="checked";
						$botonEnviar1="inline-block";
						$user1="SELECT `usu_nombre` FROM `usuarios` WHERE `idusuarios`='$Nomina[6]' ";
						$DB1->Execute($user1);
						$nombre1=$DB1->recogedato(0);
						$confirmado1="Por $nombre1 <br> en la fecha: $Nomina[7]";
					}else {
						$cheked1="";
						$botonEnviar1="none";
						$confirmado1="";
					}




					}


					$ajustessumB=0;
					$ajustesresB=0;
					if ($tipoAjusteB=="suma") {
						$ajustessumB=$valorAjusteB;
						$ajustesresB=0;
					}else if($tipoAjusteB=="descuento"){
						$ajustessumB=0;
						$ajustesresB=$valorAjusteB;
					}
					$TotalDevengado= ($totalauxilio + $valordediastrabajados+$valorDiasIncapadidad+$valorDiasVacaciones+$diasPerLicBasValortotalfinal+$ajustessumB)-($valorSalud+$valorPension+$restaABasico+$ajustesresB);
					$TotalDevengado_formateado = number_format($TotalDevengado, 0, ',', '.');
					$tabla.="<td style='background-color:#F4D03F'>$TotalDevengado_formateado</td>";//Valor quincena

					if ($nombreMotivo="PAGO DE INCAPACIDAD AL 66") {
						
						// $valorDiasIncapadidad=$valorDiasIncapadidad+$diasPerLicBasValortotalfinal;
					}
					$totaldevengado=$valordediastrabajados+$totalauxilio+$valorDiasIncapadidad+$valorDiasVacaciones;
					$totaldeduccion=$valorSalud+$valorPension+$restaABasico+$totaldeduccion;
					if ($rw1[5]=="1073711329") {
						$prov="&otros=$totalOtrosDias";
					}else {
						$prov="";
					}
					$rutaDeComproBas="desprendibleBasico.php?cedula=".$rw1[5]."&nombre=".$rw1[1]." ".$rw1[2]."&cargo=$cargosaldo[1]&fechaini=$fechaactual&fechafin=$fechafinal&cuenta=&diastrabajados=$diassitrabajoParaMostrar&sueldo=$valordediastrabajados&auxilitrans=$totalauxilio&pagdiasinca=$valorDiasIncapadidad&totaldeveng=$totaldevengado&salud=$valorSalud&pension=$valorPension&prestamos=$restaABasico&totaldeduccion=$totaldeduccion&confirmado=$validadoDesprendible&diasIncapacidad=$diasincapacidad&firma=$rw1[17]&vacaciones=$valorDiasVacaciones&diasvacaciones=$diasVacaciones&sede=$rw1[7]&valorAjuste=$valorAjusteB&tipoAjuste=$tipoAjusteB&descripcionAjuste=$descripcionAjusteB&descriprestamos=$descripcionBasico".$prov."&licencias=$permisosLic&valorlicencias=$diasPerLicBasValortotalfinal";
					$tabla.="<td><a href='$rutaDeComproBas' target='_blank'>ver</a>
					<button style='display: $botonEnviar1;  width:120px;border:1px solid #f9f9f9;background-color: ".$colorEnviar.";color:#f9f9f9; font-size:15px' onclick='enviarDesprendible(\"$rutaDeComproBas\",$idusuario,\"$fechaactual\",\"$fechafinal\",\"guardarCuenCobro\",\"Basico\")' id='Basico".$idusuario."guardarCuenCobro'>$textEnviar</button>
					<input  type='checkbox' id='Basico".$idusuario."confirmaAdmin1' onchange='confirmaAdmin($idusuario,\"$fechaactual\",\"$fechafinal\",\"confirmaAdmin\",\"Basico\",1)' $cheked1>
					<label for='miCheckbox'>
					<details>
					<summary>Confirmado</summary>
						<p>$confirmado1<p/>
					</details>
					</label></td>";//IMPRIMIR
					$tabla.="<td>".$validado.$Observacion."</td>";//Confirmado?

					$valorHorasNormales_formateado = number_format($valorTotalHorasNormales, 0, ',', '.');
					$tabla.="<td>$horasNormales</td>";//Horas normales
					$tabla.="<td>$valorHorasNormales_formateado</td>";//Valor Horas normales

					$valorHorasDomini_formateado = number_format($valorTotalHorasDomini, 0, ',', '.');
					$tabla.="<td>$horasDomini</td>";//Horas dominicales
					$tabla.="<td>$valorHorasDomini_formateado</td>";//Valor Horas dominicales

					$tabla.="<td>$".$cargosaldo[4]."</td>";//Otros
					$tabla.="<td  >$".$totalOtrosDias_formateado."</td>";//total otros segun dias

					$tabla.="<td  >$rw10[0]</td>";//Recogidas

					$tabla.="<td  >$ $valorRecogidas_formateado</td>";//Valor recogidas
					$tabla.="<td  >$".$restaAOtros."</td>";//Deudas descontadas a otros

					$colorselect1="#8B0000";
					$si1="";
					$no1="";
					$imagencompr1="";
					$linkotros="";
					$textEnviar1="Enviar";
					$colorEnviar1="rgb(7, 79, 145)";
					$validado1="";
					$Observacion1="";
					$cheked2="";
					$botonEnviar2="none";
					$confirmado="";
					$validadoDesprendible1="";

					$tablaNomina1="SELECT  nom_confirma,nom_img_compro,nom_cuentaCobro,nom_confirmaUsu,nom_motivoObser, nom_fechaconfirmaUsus,`nom_confiAdmi`,`nom_fechaConfiAdmi`,nom_confirmaAdmin,nom_valor_ajuste1,nom_tipo_ajuste1,nom_ajuste_descripcion1  from nomina where nom_id_usu='$idusuario' and nom_fecha_inicio='$fechaactual' and nom_tipo_pago='Otros'  ";
					$DB1->Execute($tablaNomina1);

					while($Nomina1=mysqli_fetch_row($DB1->Consulta_ID))
				{
					// echo"AAAAAAAAAAAAAA".$Nomina[0];

					$valorAjusteO=$Nomina1[9];
					$tipoAjusteO=$Nomina1[10];
					$descripcionAjusteO=$Nomina1[11];

						$imagencompr1=$Nomina1[1];




					if ($Nomina1[0]=="Si") {
						$colorselect1="#28B463";
						$si1="selected";
						$no1="";
						$linkotros="auto";
					}else if($Nomina1[0]=="No"){
						$si1="";
						$no1="selected";
						$colorselect1="#8B0000";
						$linkotros="none";
					}else{
						$si1="";
						$no1="selected";
						$colorselect1="#8B0000";
						$linkotros="none";
					}

					if($Nomina1[2]==""){

						$textEnviar1="Enviar";
						$colorEnviar1="rgb(7, 79, 145)";
					}else{

						$colorEnviar1="#28B463";
						$textEnviar1="Reenviar";
					}

					if ($Nomina1[3]=="Si") {

						$validadoDesprendible1="Validado el $Nomina1[5]  Por ".$rw1[1]." ".$rw1[2]." ";
						$validado1="Validado✅<br>  $Nomina1[5]";
					}elseif($Nomina1[3]=="no"){
						$validado1="Rechazada❌ <br> $Nomina1[5]";
					}else{
						$validado1="Pendiente";

					}

					if ($Nomina1[4]!="") {
						$Observacion1="<textarea readonly name='' rows='' cols=''>$Nomina1[4]</textarea>";
					}else {
						$Observacion1="";
					}
					if ($Nomina1[8]=="si") {


						$cheked2="checked";
						$botonEnviar2="inline-block";
						$user2="SELECT `usu_nombre` FROM `usuarios` WHERE `idusuarios`='$Nomina1[6]' ";
						$DB1->Execute($user2);
						$nombre2=$DB1->recogedato(0);
						$confirmado="Por $nombre2 <br> $Nomina1[7]";
					}else {
						$cheked1="";
						$botonEnviar2="none";
						$confirmado="";
					}
					}


					$ajustessumO=0;
					$ajustesresO=0;
					if ($tipoAjusteO=="suma") {
						$ajustessumO=$valorAjusteO;
						$ajustesresO=0;
					}else if($tipoAjusteO=="descuento"){
						$ajustessumO=0;
						$ajustesresO=$valorAjusteO;
					}

					$totalOtrosAPagar=($totalOtrosDias+$valorTotalHorasDomini+$valorRecogidas+$ajustessumO+$valorTotalHorasNormales)-($restaAOtros+$ajustesresO);

					$totalOtrosAPagar_formateado = number_format($totalOtrosAPagar, 0, ',', '.');
					$tabla.="<td  style='background-color:#F4D03F'>$".$totalOtrosAPagar_formateado."</td>";//total otros

					$totalOtrosConHD=$totalOtrosDias+$valorTotalHorasDomini;

					$totalOtrosConHD_formateado = number_format($totalOtrosConHD, 0, ',', '.');
					$rutaDeComp="desprendibleOtros.php?cedula=".$rw1[5]."&nombre=".$rw1[1].$rw1[2]."&valor=".$totalOtrosConHD."&deudas=".$restaAOtros."&fechaini=".$fechaactualSinTiempo."&fechafin=".$fechafinalSinTiempo."&recogidas=".$cantRecogidas."&valorRecogidas=".$valorRecogidas."&otrosdeve=0&confirmado=$validadoDesprendible1&firma=$rw1[17]&valorAjuste=$valorAjusteO&tipoAjuste=$tipoAjusteO&descripcionAjuste=$descripcionAjusteO&descripcionOtros=$descripcionOtros";
					$tabla.="<td><a  target='_blank' href='desprendibleOtros.php?cedula=".$rw1[5]."&nombre=".$rw1[1].$rw1[2]."&valor=".$totalOtrosConHD."&deudas=".$restaAOtros."&fechaini=".$fechaactualSinTiempo."&fechafin=".$fechafinalSinTiempo."&recogidas=".$cantRecogidas."&valorRecogidas=".$valorRecogidas."&otrosdeve=0&confirmado=$validadoDesprendible1&firma=$rw1[17]&valorAjuste=$valorAjusteO&tipoAjuste=$tipoAjusteO&descripcionAjuste=$descripcionAjusteO&descriprestamos=$descripcionOtros'>ver</a>
						<button style='display: $botonEnviar2; width:120px;border:1px solid #f9f9f9;background-color:".$colorEnviar1.";color:#f9f9f9;font-size:15px' id='Otros".$idusuario."guardarCuenCobro' onclick='enviarDesprendible(\"$rutaDeComp\",$idusuario,\"$fechaactual\",\"$fechafinal\",\"guardarCuenCobro\",\"Otros\")'>$textEnviar1</button>
						<input  type='checkbox' id='Otros".$idusuario."confirmaAdmin2' onchange='confirmaAdmin($idusuario,\"$fechaactual\",\"$fechafinal\",\"confirmaAdmin\",\"Otros\",2)' $cheked2>
						<label for='miCheckbox'>
						<details>
						<summary>Confirmado</summary>
							<p>$confirmado<p/>
						</details>
						</label><br>

						</td>";//IMPRIMIR

					$tabla.="<td>".$validado1.$Observacion1."</td>";//Confirmado?
					// $tabla.="<td><a href='desprendibleOtros.php?cedula=".$rw1[5]."&nombre=".$rw1[1].$rw1[2]."&valor=".$totalOtrosConHD."&deudas=".$restaAOtros."&fechaini=".$fechaactualSinTiempo."&fechafin=".$fechafinalSinTiempo."' target='_blank'>Cuenta de cobro</a></td>";//IMPRIMIR

					$TotalDevengoyOtros=$TotalDevengado+$totalOtrosAPagar;
					$TotalDevengoyOtros_formateado = number_format($TotalDevengoyOtros, 0, ',', '.');
					// $tabla.="<td>Agregar</td>";

					$tabla.="<td>".$TotalDevengoyOtros_formateado."</td>";
					$tabla.="<td> <a id='link'  onclick='pop_dis16($idusuario,\"Ajustes_nomina\",\"$fechaactual\")';  title='Click para agregar un abono' >Agregar +</a></td>";//abonos a deudas

					$tabla.="<td><div id='campo'>";
					$tabla.="<select  style='width:120px;border:1px solid #f9f9f9;background-color:".$colorselect.";color:#f9f9f9;font-size:15px'  name='$va' id='".$idusuario."Otros'  onchange='confirmarPago($idusuario,\"$fechaactual\",\"$fechafinal\",\"confirmarPago\",this.value,\"Otros\")' class='borrar' required>
					<option value='no' $no>NO</option>
					<option value='Si'$si>SI</option>";


					$tabla."</select>";








					//   if ($si1=="selected") {
					if ($imagencompr1=="" and $imagencompr=="") {
						$tabla.="<td><a id='Otros".$idusuario."' style='pointer-events: ".$linkotros."; color: gray;' onclick='pop_dis16($idusuario,\"Comprobante_nomina_otros\",\"$fechaactual\")';  title='Ingreso de Usuario' >Cargar</td>";



					}elseif ($imagencompr1!=""){
						$tabla.="<td><a href='https://sistema.transmillas.com/img_nomina/$imagencompr1' style='display: block;' target='_blank' title='Ver comprovante de pago de nomina' >Ver</a>";
						$tabla.="<a id='Otros".$idusuario."' style='display: block;  pointer-events: ".$linkotros."; ' onclick='pop_dis16($idusuario,\"Comprobante_nomina_otros\",\"$fechaactual\")';  title='Ingreso de Usuario' >Cambiar</a></td>";

					}elseif ($imagencompr!=""){
							$tabla.="<td><a href='https://sistema.transmillas.com/img_nomina/$imagencompr' style='display: block;' target='_blank' title='Ver comprovante de pago de nomina' >Ver</a>";
							$tabla.="<a id='Otros".$idusuario."' style='display: block;  pointer-events: ".$linkotros."; ' onclick='pop_dis16($idusuario,\"Comprobante_nomina_otros\",\"$fechaactual\")';  title='Ingreso de Usuario' >Cambiar</a></td>";

					}

					$tabla.="<td>$rw1[20]</td>";
					$tabla.="<td>$terminaContrato</td>";

					$totalDevengTodos=$totalDevengTodos+$TotalDevengado;

					$valorAPagarotrosTodos= $valorAPagarotrosTodos+$totalOtrosAPagar;
					$TotalDevengoyOtrosTodos= $TotalDevengoyOtrosTodos+$TotalDevengoyOtros;
					$nombreCompleto=$rw1[1]." ".$rw1[2];






					$tabla.="<td><input type='checkbox'  onchange='selecionado1(
					$idusuario,
					\"$nombreCompleto\",
					\"$rw1[5]\",
					\"$diassitrabajoParaMostrar\",
					\"$diasDescanso\",
					\"$valordediastrabajados_formateado\",
					\"$totalauxilio\",
					\"$diasnotrabajo\",
					\"$diasincapacidad\",
					\"$valorDiasIncapadidad_formateado\",
					\"$diasVacaciones\",
					\"$valorDiasVacaciones_formateado\",
					\"$permisosLic\",
					\"$diasPerLicBasValortotalfinal_formateado\",
					\"$valorSalud_formateado\",
					\"$valorPension_formateado\",
					\"$TotalDebe\",
					\"$restaABasico\",
					\"$TotalDevengado_formateado\",
					\"$valorHorasDomini_formateado\",
					\"$cargosaldo[4]\",
					\"$totalOtrosDias_formateado\",
					\"$rw10[0]\",
					\"$valorRecogidas_formateado\",
					\"$restaAOtros\",
					\"$totalOtrosAPagar_formateado\",
					\"$TotalDevengoyOtros_formateado\")' class='check_hijo' id='".$idusuario."s1' value='$idusuario'></td>";

					
			 }
			 
		}
		$tabla.="</tr>";


	}

?>

	<div id="datosUsuarios" data-usuarios='<?= json_encode(array_values($idsUsu)) ?>'style="display:none;"></div>
<?php	
		echo$tabla;
		// print_r($idsUsu);
		$totalDevengTodos_formateado = number_format($totalDevengTodos, 0, ',', '.');
		$valorAPagarotrosTodos_formateado = number_format($valorAPagarotrosTodos, 0, ',', '.');
		$TotalDevengoyOtrosTodos_formateado = number_format($TotalDevengoyOtrosTodos, 0, ',', '.');

		$FB->titulo_azul1(" Totales :",1,0,10);
		$FB->titulo_azul1(" $va",1,0,0);

		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		echo "<td>$totalDevengTodos_formateado </td>";
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		echo "<td>$valorAPagarotrosTodos_formateado</td>";
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);

		$FB->titulo_azul1("$TotalDevengoyOtrosTodos_formateado",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
		$FB->titulo_azul1(" ------",1,0,0);
	}


	/* $FB->titulo_azul1("$ $totalalcobro",1,0,0);
	$FB->titulo_azul1("$ $totalprestamos",1,0,0);  */

include("footer.php");



?>
