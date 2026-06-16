<?php 
require("login_autentica.php");
include("cabezote3.php"); 


$asc="ASC";
$conde=" ";
$conde0=" ";
$conde2=" ";
$conde3=" ";
$conde4=" ";
$conde44=" ";
if($param34!=''){ $fechaactual=$param34; }
$fechaactualBase = (isset($fechaactual) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaactual)) ? $fechaactual : date("Y-m-d");
$fechaactualInicio = $fechaactualBase . " 00:00:00";
$fechaactualFin = date("Y-m-d", strtotime($fechaactualBase . " +1 day")) . " 00:00:00";
//if($param36!=''){ $fechainicial=$param36." 23:59:59";  }
if($param33!=''){ $conde="and `idusuarios`= '$param33' ";  
}
if($param35!=''){ $id_sedes=$param35; 

	$conde4=" and usu_idsede=$id_sedes "; 	
	$conde44="  and asi_idciudad=$id_sedes "; 	
}


	
$FB->titulo_azul1("Operador",1,0,7); 
$FB->titulo_azul1("Asignadas",1,0,0); 
$FB->titulo_azul1("Recogidas",1,'5%',0); 
$FB->titulo_azul1("Efectivas",1,'5%',0); 
$FB->titulo_azul1("Faltantes",1,'5%',0); 
$FB->titulo_azul1("Entregas",1,0,0); 
$FB->titulo_azul1("Efectivas",1,'5%',0); 
$FB->titulo_azul1("Faltantes",1,'5%',0); 
$FB->titulo_azul1("Remesas",1,0,0); 
$FB->titulo_azul1("Efectivas",1,'5%',0); 
$FB->titulo_azul1("Faltantes",1,'5%',0); 
/* $FB->titulo_azul1("PXC Asignadas",1,'5%',0); 
$FB->titulo_azul1("PXC Cobradas",1,'5%',0);  */
$FB->titulo_azul1("Total E.",1,'5%',0); 
$FB->titulo_azul1("Ultima G.",1,'5%',0); 
$FB->titulo_azul1("Dirije",1,'5%',0); 

$FB->titulo_azul1("Demora",1,'5%',0); 
$FB->titulo_azul1("Max Minutos",1,'5%',0); 
$FB->titulo_azul1("Zona",1,'5%',0); 
$FB->titulo_azul1("Dinero X Confirmar",1,'5%',0); 
$FB->titulo_azul1("Estado O",1,'5%',0); 

$conde1=""; 
$conde3=""; 


/* $sql0="SELECT COUNT(idcuentaspromotor),`cue_idoperpendiente`,cue_pendientecobrar FROM `cuentaspromotor` WHERE cue_estado>=3 and cue_fechapcobrar like '$fechaactual%' and cue_pendientecobrar in (1,2) and cue_idoperpendiente>0 group by cue_idoperpendiente,cue_pendientecobrar ORDER BY cue_idoperpendiente";
$DB1->Execute($sql0);
 $va=0; 
 $idoperp=0;
	while($rw4=mysqli_fetch_row($DB1->Consulta_ID))
	{
		if($idoperp==$rw4[1]){
			$xpcasignadas[$rw4[1]]=$xpcasignadas[$rw4[1]]+$rw4[0];
		}else{
			$xpcasignadas[$rw4[1]]=$rw4[0];
		
		}
		if($rw4[2]==2){
			$xpcobradas[$rw4[1]]=$rw4[0];
		}
		$idoperp=$rw4[1];
	} */


 $sql1="SELECT COUNT(idservicios),ser_idresponsable FROM `servicios`  inner join usuarios on idusuarios=ser_idresponsable where ser_estado>=2 and ser_estado<=11 and ser_fechaasignacion like '$fechaactual%' $conde4  group by ser_idresponsable";
$DB->Execute($sql1);
$recogidas=array();
//$llamadas=$DB1->recogedato(0);
while($rw=mysqli_fetch_row($DB->Consulta_ID))
{
	$recogidas[$rw[1]]=$rw[0];
}

 $sql2="SELECT COUNT(gui_idservicio),idusuarios,max(gui_fecharecogio) FROM `guias`  inner join usuarios on usu_nombre=gui_recogio inner join servicios on idservicios=gui_idservicio where gui_fecharecogio   like  '$fechaactual%' and ser_estado!=100 $conde4 group by idusuarios ";
$DB->Execute($sql2);
$efectivasre=array();
$fechaultima=array();
//$llamadascon=$DB1->recogedato(0);
while($rw2=mysqli_fetch_row($DB->Consulta_ID))
{
	$efectivasre[$rw2[1]]=$rw2[0];
	$fechaultima[$rw2[1]]=$rw2[2];
}

 $sql2="SELECT sum(asi_valor) as pendiente,asi_idpromotor FROM `asignaciondinero` where asi_fecha='$fechaactual' and asi_tipo='Asignar Dinero' and asi_usercom is NUll $conde44 group by asi_idpromotor ";
$DB->Execute($sql2);
$penconfirmar=array();
while($rw27=mysqli_fetch_row($DB->Consulta_ID))
{

	$penconfirmar[$rw27[1]]=$rw27[0];

}


/* SELECT count(idgastos),gas_idusuario, 'entraga' as entrega FROM `gastos` where gas_fecharegistro like '2020-11-13%'  GROUP by gas_idusuario
union 
SELECT count(idgastos),gas_idusuario, 'recogida' as entrega FROM `gastos` where gas_fecharegistro like '2020-11-13%'  GROUP by gas_idusuario
 */
if($nivel_acceso==1 or $nivel_acceso==12){

	$cond0=	" gas_fecharegistro >= '$fechaactual' and gas_fecharegistro <= '$fechaactual 23:59:59'";
	$cond01=	" gas_fecrecogida >= '$fechaactual' and gas_fecrecogida <= '$fechaactual 23:59:59'";

}else{

$cond0=	" gas_fecharegistro like '$fechaactual%'";
$cond01=	" gas_fecrecogida >= '$fechaactual%' and gas_fecrecogida <= '$fechaactual%'";

}
 $sql2="SELECT count(idgastos),gas_iduserremesa, 'entrega' as tipo, gas_entrego as efectivas  FROM `gastos` where $cond0 GROUP by gas_iduserremesa,gas_entrego
 union 
 SELECT count(idgastos),gas_iduserrecoge, 'recogida' as tipo, gas_recogio as efectivas FROM `gastos` where $cond0 GROUP by gas_iduserrecoge,gas_recogio";
$DB->Execute($sql2);
$remesasentrega=array();
$remesasrecogida=array();
$remesasrecogidaefec=array();
$remesasentregaefec=array();
while($rw28=mysqli_fetch_row($DB->Consulta_ID))
{

	if($rw28[2]=='entrega' and $rw28[3]==1){

		$remesasentregaefec[$rw28[1]]=$rw28[0];

	}elseif($rw28[2]=='entrega' and $rw28[3]==0){
		
		$remesasentrega[$rw28[1]]=$rw28[0];

	}elseif($rw28[2]=='recogida' and $rw28[3]==1){

		$remesasrecogidaefec[$rw28[1]]=$rw28[0];
		
	}elseif($rw28[2]=='recogida' and $rw28[3]==0){

		$remesasrecogida[$rw28[1]]=$rw28[0];
	}
		
}


 $sql3="SELECT COUNT(idservicios),ser_idusuarioguia FROM `servicios`  where ser_estado>=9 and ser_estado<=11 and ser_fechaguia like '$fechaactual%'  group by ser_idusuarioguia";
$DB->Execute($sql3);
$entregas=array();
//$llamadas=$DB1->recogedato(0);
while($rw3=mysqli_fetch_row($DB->Consulta_ID))
{
	$entregas[$rw3[1]]=$rw3[0];
}

$sql4="SELECT COUNT(gui_idservicio),idusuarios,max(gui_fechaentrega) FROM `guias`  inner join usuarios on usu_nombre=gui_userecomienda where gui_fechaentrega  like  '$fechaactual%'  $conde4 group by idusuarios ";
$DB->Execute($sql4);
$efectivasent=array();
//$llamadascon=$DB1->recogedato(0);
while($rw4=mysqli_fetch_row($DB->Consulta_ID))
{
	$efectivasent[$rw4[1]]=$rw4[0];
	if($fechaultima[$rw4[1]]==''or $fechaultima[$rw4[1]]==null){
		$fechaultima[$rw4[1]]=$rw4[2];
	}elseif($rw4[2]>$fechaultima[$rw4[1]]){
		$fechaultima[$rw4[1]]=$rw4[2];
	}
}

$sql="SELECT idusuarios,usu_nombre FROM `usuarios` inner join seguimiento_user on seg_idusuario=idusuarios where  (usu_estado=1 or usu_filtro=1) and seg_motivo='Ingreso' and seg_fechaalcohol='$fechaactual' and roles_idroles in (3)  $conde  $conde2  $conde4 ORDER BY usu_nombre  asc ";

$DB->Execute($sql); 
$va=0; 
$totalasignadas=0;
$totalrecogidas=0;
$totalefectivasr=0;
$totalfaltantesr=0;
$totalentregas=0;
$totalefectivase=0;
$totalfaltantese=0;
$totalefectivas=0;
$totalremesas=0;
$asignadas1=0;
$recogidas1=0;
$efectivasr1=0;
$entregas1=0;
$efectivase1=0;

	while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	{
		$id_p=$rw1[0];
		

		$totalremesas=@$remesasentrega[$id_p]+@$remesasrecogida[$id_p]+@$remesasrecogidaefec[$id_p]+@$remesasentregaefec[$id_p];
		$totalremesasefec=@$remesasrecogidaefec[$id_p]+@$remesasentregaefec[$id_p];
		$totalremesasfaltaltes=@$remesasentrega[$id_p]+@$remesasrecogida[$id_p];

		$fecha='';
		$faltantesre1=0;
		$faltantesen1=0;
		$totalefectivas=0;
		@$recogidas1=$recogidas[$id_p];
		@$entregas1=$entregas[$id_p];
		$asignadas1=$recogidas1+$entregas1+$totalremesas;
		$fecha=$fechaultima[$id_p];
		@$efectivasr1=$efectivasre[$id_p];
		$faltantesre1=$recogidas1-$efectivasr1;

		@$efectivase1=$efectivasent[$id_p];
		$faltantesen1=$entregas1-$efectivase1;

		$dineroconfirmar=$penconfirmar[$id_p];

		$totalefectivas=$efectivase1+$efectivasr1+$totalremesasefec;
		if($totalefectivas>=$asignadas1){
			$estado='Acabo';
			$color2="#FFFF33";
		}else{
			$color2="#FF3C33";
			$estado='Ocupado';
		}


		 $idestadoguia="SELECT  seg_idservicio,seg_direccion   FROM seguimientoruta where `seg_idusuario`='$id_p' and seg_fecha >= '$fechaactualInicio' and seg_fecha < '$fechaactualFin' and seg_estado!='Cambioruta' order by `seg_fechaestado` desc limit 1";
		$DB1->Execute($idestadoguia);
		$seguimiento=mysqli_fetch_row($DB1->Consulta_ID);	
		$direccion=$seguimiento[1];

		$sql6="SELECT seg_motivo,zon_nombre,seg_fechaingreso,seg_horaalmuerzo,seg_horaregreso,seg_horaoficina,seg_fechafinalizo,zon_tiempo FROM `seguimiento_user` inner join zonatrabajo on seg_idzona=idzonatrabajo where seg_idusuario='$id_p' and seg_fechaingreso like '$fechaactual%' and seg_motivo='Ingreso'";
		$DB1->Execute($sql6);
		$rw3=mysqli_fetch_row($DB1->Consulta_ID);	

		if($rw3[3]!=null and $rw3[4]==null){
			$estado='Almorzando';
		}
		if($rw3[5]!=null){
			$estado='Regreso Oficina';
		}
		$zona=$rw3[1];
		//$totalevaluacion=$llamadas1+$llamadascon1+$llamadasver1+$llamadasexistosas;
	//	if($recogidas1=='' and $entregas1==''){}else{

		// remesas


			$va++; $p=$va%2;
			if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
	
			if($fecha!='') {
				$fechados = date('Y-m-d');

				$fecha1 = date("Y-m-d", strtotime($fecha));
				$fecha2 = $fechados;

				if($fecha1 == $fecha2){

					$restafecha = (strtotime($fecha) - strtotime($fechatiempo));
					$minutos = $restafecha / 60;
					$minutos = abs($minutos); 
					$minutos = floor($minutos);

				}else{
					$minutos = 0;
				}
			}else{
			   $minutos =0;
		   }
		   if($minutos>=$rw3[7] and $estado=='Ocupado'){
			   $color23='#FF3C33';
            
			   $check="<input type='checkbox'  onchange='selecionado($id_p)' class='checkbox' id='".$id_p."s' value='$id_p'>";
		   }else{
			   $color23='';
			   $check="";

		   }

		   if($direccion=='Disponible'){$color="#99FF99";}elseif($direccion=='Regreso de Oficina'){ $color="#FFFF99";}elseif($minutos>=$rw3[7] and $estado=='Ocupado'){ $color="#FF6666";}
		   echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
		   //$direc1=str_replace("&"," ", $rw1[4]);

	echo "<td colspan='1' width='0' align='center' ><a id='link' onclick='pop_dis36($id_p,\"recorridooperadorruta\",\"$fechaactual\")';  style='cursor: pointer;' title='Detalle Recorrido ruta' >$rw1[1]</td>";
		
	   echo "
			<td>".$asignadas1."</td>
			<td>".$recogidas1."</td>
			<td>".$efectivasr1."</td>
			<td>".$faltantesre1."</td>
			<td>".$entregas1."</td>
			<td>".$efectivase1."</td>
			<td>".$faltantesen1."</td>";

	// remesas 
			echo "<td>".$totalremesas."</td>
			<td>".$totalremesasefec."</td>
			<td>".$totalremesasfaltaltes."</td>";

			echo "<td colspan='1' width='0' align='center' ><a id='link' onclick='pop_dis6($id_p,\"recorridooperadorruta\",\"$fechaactual\")';  style='cursor: pointer;' title='Detalle Recorrido' >$totalefectivas</td>";

			echo "<td>".$fecha."</td>";

		

			echo "<td>".$direccion."</td>";
			echo "<td bgcolor='$color23'>".$minutos." Minutos $check</td>";
			echo "<td bgcolor='$color23'>".$rw3[7]." Minutos</td>";
			
			echo "<td  bgcolor='$color2' >".$zona."</td>";

			if($dineroconfirmar>0){
				$color22='#FF3C33';
			}else{
				$color22='';
			}
			echo "<td  bgcolor='$color22' >".$dineroconfirmar."</td>";


			echo "<td  bgcolor='$color2' >".$estado."</td>";

			$totalasignadas=$asignadas1+$totalasignadas;
			$totalrecogidas=$recogidas1+$totalrecogidas;
			$totalefectivasr=$efectivasr1+$totalefectivasr;
			$totalfaltantesr=$faltantesre1+$totalfaltantesr;

			$totalentregas=$entregas1+$totalentregas;
			$totalefectivase=$efectivase1+$totalefectivase;
			$totalfaltantese=$faltantesen1+$totalfaltantese;

			$totalefectivas=$totalefectivas+$totalefectivas;
		//}

	}
	


	$FB->titulo_azul1(" Totales :",1,0,10); 
	$FB->titulo_azul1(" $totalasignadas",1,0,0); 
	$FB->titulo_azul1(" $totalrecogidas",1,0,0); 
	$FB->titulo_azul1(" $totalefectivasr",1,0,0); 
	$FB->titulo_azul1(" $totalfaltantesr",1,0,0); 
	$FB->titulo_azul1(" $totalentregas",1,0,0); 
	$FB->titulo_azul1(" $totalefectivase",1,0,0); 
	$FB->titulo_azul1(" $totalfaltantese",1,0,0); 
	$FB->titulo_azul1(" $totalefectivas",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	$FB->titulo_azul1(" ------",1,0,0); 
	/* $FB->titulo_azul1("$ $totalalcobro",1,0,0); 
	$FB->titulo_azul1("$ $totalprestamos",1,0,0);  */

include("footer.php");
?>
