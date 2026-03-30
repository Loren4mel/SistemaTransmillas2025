<?php
require("login_autentica.php");
include("declara.php");
$idUserActual=$_SESSION['usuario_id'];
@$tabla=$_REQUEST["tabla"];
if(isset($_POST["condecion"])) {$condecion=$_POST["condecion"]; } else { $condecion=""; }  
if(isset($_POST["nivel"])) {$nivel=$_POST["nivel"]; } else { $nivel=""; } 
if(isset($_POST["id_param"])) {$id_param=$_POST["id_param"]; } else { $id_param=""; } 
 $tabla1=$tabla;
if($condecion=='general'){  $tabla='General'; }
$id_sedes=$_SESSION['usu_idsede'];
$id_nombre=$_SESSION['usuario_nombre'];
switch($tabla)
{

case "General": 

$tabla1=strtolower($tabla1);

$sql1="SHOW COLUMNS FROM `$tabla1` ";
$DB->Execute($sql1); $va=1; $va2=0; 
$sql="INSERT INTO $tabla1 (";
	while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	{
		$sql.="$rw1[0],";
	}
	$sql = substr ($sql, 0, -1);
	$sql.=") VALUES ('',";
	
	foreach($_REQUEST as $nombre_campo => $valor){
				if(substr($nombre_campo,0,5)=="param"){
				$sql.="'$valor',";
				}
			}
	$sql = substr ($sql, 0, -1);
	$sql.=");";	
	$imagen="";
	if($_FILES["param100"]!=''){
		$vinculo=$DB->Executeid($sql);
		$QL->addDocumento1($_FILES["param100"], 1, "$tabla1", $vinculo, "$tabla1", $DB);
		$sql="SELECT * from documentos where doc_tabla='$tabla1' and doc_idviene='$vinculo'  limit 1";
		$imagen="imagen=$param101";
	}

	$valores[7]=$sql; $valores[4]="adm_general.php?tabla=$tabla1&$imagen"; $valores[8]=1; 
	$tabla=$tabla1;
break;
	case "Dependencia":
	if($_POST["param1"]==0){ 
		$saa="INSERT INTO dependencias (iddependencias, dep_predecesor, dep_nombre) VALUES ('', '0', '".$_POST["param4"]."')";
		$DB->Execute($saa);
		$sel="SELECT iddependencias FROM dependencias ORDER BY iddependencias DESC ";
		$DB->Execute($sel);		
		$iddepe=$DB->recogedato(0);		
	}
	else {$iddepe=$_POST["param1"];} 
	$valores[4]="adm_organigramas.php"; $valores[8]=1; 
	$valores[7]="INSERT INTO dependencias (iddependencias, dep_predecesor, dep_nombre, dep_responsable) VALUES ('', '$iddepe', '".$_POST["param2"]."', '".$_POST["param3"]."')";
	break;
	case "Codigo-Ciudad":
	if($param==2){
	$sql="update `conf_fac` set idconsecutivo='$param3',idresolucion='$param2',prefijo='$param4' where idciudad=$param1";
	}else if($param==0){
		
		$sql="INSERT INTO `conf_fac`(`idconfac`,`idciudad`,`idresolucion`, `idconsecutivo`, `prefijo`) VALUES ('','$param1','$param2','$param3','$param4')";
	}

	$valores[7]=$sql; $valores[4]="conf_fac.php"; $valores[8]=1; 
	break;
	case "asignardinero":
	$param4=str_replace(".","", $param4);
	 $sql="INSERT INTO `asignaciondinero`(`asi_idpromotor`,`asi_fecha`,`asi_fechaingreso`, `asi_valor`,  `asi_idautoriza`,  `asi_idciudad`,asi_tipo,asi_descripcion)
	VALUES ('$param2','$param3','$fechaactual','$param4','$id_usuario','$param1','$param5','$param6')";
	$valores[7]=$sql; $valores[4]="asignardinero.php"; $valores[8]=1; 
	break;		
	case "deudaspro":
	$param5=str_replace(".","", $param5);
	 $sql="INSERT INTO `duedapromotor`(`iddeudapromotor`,`deu_idciudad`, `deu_idpromotor`, `deu_fecha`,`deu_tipo`,  `deu_valor`, `due_descripcion`, `deu_idautoriza`)
	VALUES ('','$param1','$param2','$param3','$param4','$param5','$param6','$id_usuario')";
	$vinculo=$DB->Executeid($sql);

	$QL->addDocumento1($_FILES["param7"], 1, "duedapromotor", $vinculo, "duedapromotor", $DB);
	$sql="SELECT * from documentos where doc_tabla='duedapromotor' and doc_idviene='$vinculo' limit 1";
	$valores[7]=$sql; $valores[4]="deudaspro.php"; $valores[8]=1; 
	
	break;	
	case "Abono_A_Deuda":
		$param5=str_replace(".","", $param5);
		 $sql="INSERT INTO `duedapromotor`(`iddeudapromotor`,`deu_idciudad`, `deu_idpromotor`, `deu_fecha`,`deu_tipo`,  `deu_valor`, `due_descripcion`, `deu_idautoriza`,`deu_pago_de`)
		VALUES ('','$param1','$param2','$param3','$param4','$param5','$param6','$id_usuario','$param8')";
		$vinculo=$DB->Executeid($sql);
	
		$QL->addDocumento1($_FILES["param7"], 1, "duedapromotor", $vinculo, "duedapromotor", $DB);
		$sql="SELECT * from documentos where doc_tabla='duedapromotor' and doc_idviene='$vinculo' limit 1";
		$valores[7]=$sql; $valores[4]="deudaspro.php"; $valores[8]=1; 
		
	break;	
	case "Ajustes_nomina":

		$param5=str_replace(".","", $param5);

            $fechaini=$_REQUEST["ide"];

			$sql1="SELECT  count(*) from nomina where nom_id_usu='$param2' and nom_fecha_inicio='$param7'  ";		
            $DB1->Execute($sql1);
            $rw1=mysqli_fetch_row($DB1->Consulta_ID);
        
			if ($rw1[0]>0) {
                // echo$sql="UPDATE `nomina` SET `nom_confirma`='$estado' WHERE nom_id_usu='$idusuario' and nom_fecha_inicio='$fechaini' ";
                // $DB1->Execute($sql);
				echo$sql="UPDATE `nomina` SET `nom_valor_ajuste1`='$param5', nom_tipo_ajuste1='$param4', nom_ajuste_descripcion1='$param6' WHERE  nom_fecha_inicio='$param7' and nom_id_usu='$param2' and nom_tipo_pago='$param8'";
				$DB->Execute($sql);
                // and nom_tipo_pago='$tipo'
            }else{
                $sql="INSERT INTO `nomina`(`nom_id_usu`,`nom_fecha_inicio`, `nom_tipo_pago`,nom_valor_ajuste1,nom_tipo_ajuste1,nom_ajuste_descripcion1) VALUES ('$param2','$param7','$param8','$param5','$param4','$param6')";
                // $DB1->Execute($sql);
            }


			// $QL->addDocumento1($_FILES["param7"], 1, "duedapromotor", $vinculo, "duedapromotor", $DB);
			// $sql="SELECT * from documentos where doc_tabla='duedapromotor' and doc_idviene='$vinculo' limit 1";
			$valores[7]=$sql; $valores[4]="nomina.php"; $valores[8]=1; 
			
	break;	
	case "Devolver Retegarantia":
			$param5=str_replace(".","", $param5);

			// $sql="UPDATE `NominasEmpresa` SET `nom_devolRetegara`='$param8' WHERE  nome_fechaIni='$param4' and nom_id_usu='$idusuario' and nom_tipo_pago='Basico'";
			// $DB->Execute($sql);
		
			// $QL->addDocumento1($_FILES["param7"], 1, "duedapromotor", $vinculo, "duedapromotor", $DB);
			// $sql="SELECT * from documentos where doc_tabla='duedapromotor' and doc_idviene='$vinculo' limit 1";
			 $sql="INSERT INTO `nomina`(`nom_id_usu`,`nom_fecha_inicio`, `nom_tipo_pago`,nom_valor_ajuste1,nom_tipo_ajuste1,nom_ajuste_descripcion1) VALUES ('$param2','$param7','$param8','$param5','$param4','$param6')";
			$valores[7]=$sql; $valores[4]="nomina.php"; $valores[8]=1; 
			
	break;
	
	case "Agregar vehiculo":
			

			if (is_uploaded_file($_FILES['param5']['tmp_name'])){
				// $imagen1 = md5(date("Y-m-d-H-i-s").$param3).".jpg";
				$nombreArchivo1 = $_FILES["param5"]["name"];
				$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
			   
				move_uploaded_file($_FILES['param5']['tmp_name'], "./img_manifiestos/vehiculos/".$imagen1);
			 }else{
				 $imagen1 = "";
			 }

			 if (is_uploaded_file($_FILES['param6']['tmp_name'])){
				// $imagen2 = md5(date("Y-m-d-H-i-s").$param3).".jpg";
				$nombreArchivo2 = $_FILES["param6"]["name"];
				$imagen2 = date("Y-m-d-H-i-s").$nombreArchivo2;
		  
				move_uploaded_file($_FILES['param6']['tmp_name'], "./img_manifiestos/vehiculos/".$imagen2);
			 }else{
				 $imagen2 = "";
			 }
			 
			 if (is_uploaded_file($_FILES['param7']['tmp_name'])){
				// $imagen3 = md5(date("Y-m-d-H-i-s").$param3).".jpg";
				$nombreArchivo3 = $_FILES["param7"]["name"];
				$imagen3 = date("Y-m-d-H-i-s").$nombreArchivo3;
				move_uploaded_file($_FILES['param7']['tmp_name'], "./img_manifiestos/vehiculos/".$imagen3);
			 }else{
				 $imagen3 = "";
			 }

			$sql="INSERT INTO `vehiculo_manif`(`vehim_nombre_prop`, `vehim_num_cel_prop`, `vehim_placas`, `vehim_num_Poliza`, `vehim_foto_poli`, `vehim_foto_tarjeta`, `vehim_foto_vehiculo`) VALUES ('$param1','$param2','$param3','$param4','$imagen1','$imagen2','$imagen3')";

			$valores[7]=$sql; $valores[4]="adm_manifiestos.php"; $valores[8]=1; 
			
	break;
	case "Agregar conductor":
			
		if (is_uploaded_file($_FILES['param5']['tmp_name'])){
			// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
	  
			$nombreArchivo1 = $_FILES["param5"]["name"];
			$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
			move_uploaded_file($_FILES['param5']['tmp_name'], "./img_manifiestos/conductores/".$imagen1);
		 }else{
			 $imagen1 = "";
		 }

		 if (is_uploaded_file($_FILES['param7']['tmp_name'])){
			// $imagen2 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo2 = $_FILES["param7"]["name"];
			$imagen2 = date("Y-m-d-H-i-s").$nombreArchivo2;
	  
			move_uploaded_file($_FILES['param7']['tmp_name'], "./img_manifiestos/conductores/".$imagen2);
		 }else{
			 $imagen2 = "";
		 }
		 
		 if (is_uploaded_file($_FILES['param8']['tmp_name'])){
			// $imagen3 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo3 = $_FILES["param8"]["name"];
			$imagen3 = date("Y-m-d-H-i-s").$nombreArchivo3;
	  
			move_uploaded_file($_FILES['param8']['tmp_name'], "./img_manifiestos/conductores/".$imagen3);
		 }else{
			 $imagen3 = "";
		 }
		 if (is_uploaded_file($_FILES['param9']['tmp_name'])){
			// $imagen4 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo4 = $_FILES["param9"]["name"];
			$imagen4 = date("Y-m-d-H-i-s").$nombreArchivo4;
	  
			move_uploaded_file($_FILES['param9']['tmp_name'], "./img_manifiestos/conductores/".$imagen4);
		 }else{
			 $imagen4 = "";
		 }
		 if (is_uploaded_file($_FILES['param10']['tmp_name'])){
			// $imagen4 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo5 = $_FILES["param10"]["name"];
			$imagen5 = date("Y-m-d-H-i-s").$nombreArchivo5;
	  
			move_uploaded_file($_FILES['param10']['tmp_name'], "./img_manifiestos/conductores/".$imagen5);
		 }else{
			$imagen5 = "";
			
		 }
			$sql="INSERT INTO `conductor_mani`( `cond_nombre`, `cond_celular`, `cond_whatsapp`, `cond_cedula`, `cond_foto_celula`, `cond_num_licen`, `cond_foto_licen`, `cond_foto_conductor`, `cond_firma`, `cond_lugar_expedi`,con_antec) VALUES ('$param1','$param2','$param3','$param4','$imagen1','$param6','$imagen2','$imagen3','$imagen4','$param30','$imagen5')";

			$valores[7]=$sql; $valores[4]="adm_manifiestos.php"; $valores[8]=1; 
			
	break;
	case "Agregar viaje":
	
		if (is_uploaded_file($_FILES['param7']['tmp_name'])){
			// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo1 = $_FILES["param7"]["name"];
			$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
	  
			move_uploaded_file($_FILES['param7']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen1);
		 }else{
			 $imagen1 = "";
		 }

		 if (is_uploaded_file($_FILES['param10']['tmp_name'])){
			// $imagen2 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo2 = $_FILES["param10"]["name"];
			$imagen2 = date("Y-m-d-H-i-s").$nombreArchivo2;
	  
			move_uploaded_file($_FILES['param10']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen2);
		 }else{
			 $imagen2 = "";
		 }
		 
		 if (is_uploaded_file($_FILES['param11']['tmp_name'])){
			// $imagen3 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo3 = $_FILES["param11"]["name"];
			$imagen3 = date("Y-m-d-H-i-s").$nombreArchivo3;
	  
			move_uploaded_file($_FILES['param11']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen3);
		 }else{
			 $imagen3 = "";
		 }
		 if (is_uploaded_file($_FILES['param14']['tmp_name'])){
			// $imagen3 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
			$nombreArchivo4 = $_FILES["param14"]["name"];
			$imagen4 = date("Y-m-d-H-i-s").$nombreArchivo4;
	  
			move_uploaded_file($_FILES['param14']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen4);
		 }else{
			 $imagen4 = "";
		 }
		$conductor = "SELECT `cond_nombre`,cond_cedula,cond_celular,cond_firma,cond_lugar_expedi FROM  `conductor_mani` where condid=$param1 ";
		$DB1->Execute($conductor);
		$rw2 = mysqli_fetch_array($DB1->Consulta_ID);

		

		$vehiculo = "SELECT `vehim_placas` FROM  `vehiculo_manif` where vehimid=$param2 ";
		$DB1->Execute($vehiculo);
		$rw3 = mysqli_fetch_array($DB1->Consulta_ID);


		$nombrecon=$rw2[0];
		$cedulacon=$rw2[1];
		$placasvehi=$rw3[0];
		$valorcont=$param3;
		$fechaini=$param4;
		$fechafin=$param5;
		$piezascont=$param6;
		$telefonocon=$rw2[2];
		$firmacon=$rw2[3];
		$expedida=$rw2[4];
		$ciudaddes=$param8;
		$num_mani=$param12;
		$num_remesa=$param13;

		$contrato="contratoMani.php?nombrecon=$nombrecon&cedulacon=$cedulacon&placasvehi=$placasvehi&valorcont=$valorcont&fechaini=$fechaini&fechafin=$fechafin&piezascont=$piezascont&telefonocon=$telefonocon&firmacon=$firmacon&expedida=$expedida&ciudaddes=$ciudaddes&ciudadori=$param9&num_mani=$num_mani&num_remesa=$num_remesa";
        $sql="INSERT INTO manifiestos_viajes (mani_idConduc,mani_idVehiculo,mani_valor_cont,mani_fecha_ini,mani_fecha_fin,mani_piezas,mani_Contrato,mani_manifiesto,mani_fecha,mani_idusuIngreso,mani_ciudad_destino,mani_ciudad_origen,mani_remesa_carga,mani_guias,mani_num_mani,mani_num_remesa,mani_seguridad) VALUES ('$param1','$param2','$param3','$param4','$param5','$param6','$contrato','$imagen1','$param4','$id_usuario','$param8','$param9','$imagen2','$imagen3','$param12','$param13','$imagen4')";
			
		
			// $QL->addDocumento1($_FILES["param7"], 1, "duedapromotor", $vinculo, "duedapromotor", $DB);
			// $sql="SELECT * from documentos where doc_tabla='duedapromotor' and doc_idviene='$vinculo' limit 1";
			$valores[7]=$sql; $valores[4]="manifiesto.php"; $valores[8]=1; 
			
	break;
	case "Editar vehiculo":
			
		$vehiculo = "SELECT  `vehim_foto_poli`, `vehim_foto_tarjeta`, `vehim_foto_vehiculo` FROM `vehiculo_manif` where vehimid=$id_param ";
		$DB1->Execute($vehiculo);
		$rw3 = mysqli_fetch_array($DB1->Consulta_ID);
	
		if (is_uploaded_file($_FILES['param5']['tmp_name'])){
			// $imagen1 = md5(date("Y-m-d-H-i-s").$param3).".jpg";
			$nombreArchivo1 = $_FILES["param5"]["name"];
			$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
		   
			move_uploaded_file($_FILES['param5']['tmp_name'], "./img_manifiestos/vehiculos/".$imagen1);
		 }else{
			 $imagen1 = $rw3[0];
		 }

		 if (is_uploaded_file($_FILES['param6']['tmp_name'])){
			// $imagen2 = md5(date("Y-m-d-H-i-s").$param3).".jpg";
			$nombreArchivo2 = $_FILES["param6"]["name"];
			$imagen2 = date("Y-m-d-H-i-s").$nombreArchivo2;
	  
			move_uploaded_file($_FILES['param6']['tmp_name'], "./img_manifiestos/vehiculos/".$imagen2);
		 }else{
			 $imagen2 = $rw3[1];
		 }
		 
		 if (is_uploaded_file($_FILES['param7']['tmp_name'])){
			// $imagen3 = md5(date("Y-m-d-H-i-s").$param3).".jpg";
			$nombreArchivo3 = $_FILES["param7"]["name"];
			$imagen3 = date("Y-m-d-H-i-s").$nombreArchivo3;
			move_uploaded_file($_FILES['param7']['tmp_name'], "./img_manifiestos/vehiculos/".$imagen3);
		 }else{
			 $imagen3 = $rw3[2];
		 }

		$sql="UPDATE `vehiculo_manif` SET `vehim_nombre_prop`='$param1',`vehim_num_cel_prop`='$param2',`vehim_placas`='$param3',`vehim_num_Poliza`='$param4',`vehim_foto_poli`='$imagen1',`vehim_foto_tarjeta`='$imagen2',`vehim_foto_vehiculo`='$imagen3' WHERE vehimid='$id_param'";

		$valores[7]=$sql; $valores[4]="adm_manifiestos.php"; $valores[8]=1; 
		
break;
case "Editar conductor":
	$conductor = "SELECT  `cond_foto_celula`, `cond_foto_licen`, `cond_foto_conductor`, `cond_firma`,con_antec FROM `conductor_mani` where condid=$id_param ";
    $DB1->Execute($conductor);
    $rw2 = mysqli_fetch_array($DB1->Consulta_ID);
		
	if (is_uploaded_file($_FILES['param5']['tmp_name'])){
		// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
  
		$nombreArchivo1 = $_FILES["param5"]["name"];
		$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
		move_uploaded_file($_FILES['param5']['tmp_name'], "./img_manifiestos/conductores/".$imagen1);
	 }else{
		 $imagen1 = $rw2[0];
	 }

	 if (is_uploaded_file($_FILES['param7']['tmp_name'])){
		// $imagen2 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo2 = $_FILES["param7"]["name"];
		$imagen2 = date("Y-m-d-H-i-s").$nombreArchivo2;
  
		move_uploaded_file($_FILES['param7']['tmp_name'], "./img_manifiestos/conductores/".$imagen2);
	 }else{
		 $imagen2 = $rw2[1];
	 }
	 
	 if (is_uploaded_file($_FILES['param8']['tmp_name'])){
		// $imagen3 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo3 = $_FILES["param8"]["name"];
		$imagen3 = date("Y-m-d-H-i-s").$nombreArchivo3;
  
		move_uploaded_file($_FILES['param8']['tmp_name'], "./img_manifiestos/conductores/".$imagen3);
	 }else{
		 $imagen3 = $rw2[2];
	 }
	 if (is_uploaded_file($_FILES['param9']['tmp_name'])){
		// $imagen4 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo4 = $_FILES["param9"]["name"];
		$imagen4 = date("Y-m-d-H-i-s").$nombreArchivo4;
  
		move_uploaded_file($_FILES['param9']['tmp_name'], "./img_manifiestos/conductores/".$imagen4);
	 }else{
		 $imagen4 = $rw2[3];
	 }
	 if (is_uploaded_file($_FILES['param10']['tmp_name'])){
		// $imagen4 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo5 = $_FILES["param10"]["name"];
		$imagen5 = date("Y-m-d-H-i-s").$nombreArchivo5;
  
		move_uploaded_file($_FILES['param10']['tmp_name'], "./img_manifiestos/conductores/".$imagen5);
	 }else{
		$imagen5 = $rw2[4];
		
	 }
	 

		$sql="UPDATE `conductor_mani` SET `cond_nombre`='$param1',`cond_celular`='$param2',`cond_whatsapp`='$param3',`cond_cedula`='$param4',`cond_foto_celula`='$imagen1',`cond_num_licen`='$param6',`cond_foto_licen`='$imagen2',`cond_foto_conductor`='$imagen3',`cond_firma`='$imagen4', `cond_lugar_expedi`='$param30',con_antec='$imagen5' WHERE condid='$id_param' ";

		$valores[7]=$sql; $valores[4]="adm_manifiestos.php"; $valores[8]=1; 
		
break;
case "Editar viaje":
    $conductor = "SELECT `mani_manifiesto`,mani_remesa_carga,mani_guias,mani_seguridad FROM `manifiestos_viajes` WHERE  idmani=$id_param ";
    $DB1->Execute($conductor);
    $rw2 = mysqli_fetch_array($DB1->Consulta_ID);

	if (is_uploaded_file($_FILES['param7']['tmp_name'])){
		// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo1 = $_FILES["param7"]["name"];
		$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
  
		move_uploaded_file($_FILES['param7']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen1);
	 }else{
		 $imagen1 = $rw2[0];
	 }

	 if (is_uploaded_file($_FILES['param10']['tmp_name'])){
		// $imagen2 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo2 = $_FILES["param10"]["name"];
		$imagen2 = date("Y-m-d-H-i-s").$nombreArchivo2;
  
		move_uploaded_file($_FILES['param10']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen2);
	 }else{
		 $imagen2 = $rw2[1];
	 }
	 
	 if (is_uploaded_file($_FILES['param11']['tmp_name'])){
		// $imagen3 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo3 = $_FILES["param11"]["name"];
		$imagen3 = date("Y-m-d-H-i-s").$nombreArchivo3;
  
		move_uploaded_file($_FILES['param11']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen3);
	 }else{
		 $imagen3 = $rw2[2];
	 }
	 if (is_uploaded_file($_FILES['param14']['tmp_name'])){
		// $imagen3 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo4 = $_FILES["param14"]["name"];
		$imagen4 = date("Y-m-d-H-i-s").$nombreArchivo4;
  
		move_uploaded_file($_FILES['param14']['tmp_name'], "./img_manifiestos/manifiestos/".$imagen4);
	 }else{
		 $imagen4 = $rw2[3];
	 }
	$conductor = "SELECT `cond_nombre`,cond_cedula,cond_celular,cond_firma,cond_lugar_expedi FROM  `conductor_mani` where condid=$param1 ";
	$DB1->Execute($conductor);
	$rw2 = mysqli_fetch_array($DB1->Consulta_ID);

	

	$vehiculo = "SELECT `vehim_placas` FROM  `vehiculo_manif` where vehimid=$param2 ";
	$DB1->Execute($vehiculo);
	$rw3 = mysqli_fetch_array($DB1->Consulta_ID);


	$nombrecon=$rw2[0];
	$cedulacon=$rw2[1];
	$placasvehi=$rw3[0];
	$valorcont=$param3;
	$fechaini=$param4;
	$fechafin=$param5;
	$piezascont=$param6;
	$telefonocon=$rw2[2];
	$firmacon=$rw2[3];
	$expedida=$rw2[4];
	$ciudaddes=$param8;
	$num_mani=$param12;
	$num_remesa=$param13;

	$contrato="contratoMani.php?nombrecon=$nombrecon&cedulacon=$cedulacon&placasvehi=$placasvehi&valorcont=$valorcont&fechaini=$fechaini&fechafin=$fechafin&piezascont=$piezascont&telefonocon=$telefonocon&firmacon=$firmacon&expedida=$expedida&ciudaddes=$ciudaddes&ciudadori=$param9&num_mani=$num_mani&num_remesa=$num_remesa";
	$sql="UPDATE `manifiestos_viajes` SET `mani_idConduc`='$param1',`mani_idVehiculo`='$param2',`mani_valor_cont`='$param3',`mani_fecha_ini`='$param4',`mani_fecha_fin`='$param5',`mani_piezas`='$param6',`mani_Contrato`='$contrato',`mani_manifiesto`='$imagen1',`mani_fecha`='$param4',`mani_idusuIngreso`='$id_usuario',`mani_ciudad_destino`='$param8',mani_ciudad_origen='$param9',mani_remesa_carga='$imagen2',mani_guias='$imagen3',mani_num_mani='$param12',mani_num_remesa='$param13',mani_seguridad='$imagen4' WHERE idmani= $id_param";
		
	
		// $QL->addDocumento1($_FILES["param7"], 1, "duedapromotor", $vinculo, "duedapromotor", $DB);
		// $sql="SELECT * from documentos where doc_tabla='duedapromotor' and doc_idviene='$vinculo' limit 1";
		$valores[7]=$sql; $valores[4]="manifiesto.php"; $valores[8]=1; 
		
break;
case "Agregar cotizacion":

	$fecha=date("Y-m-d");
	echo$sql="INSERT INTO `cotozaciones`(`cot_clirente`, `cot_nit`, `cot_origen`, `cot_destino`, `cot_direc_origen`, `cot_direc_destino`, `cot_desc_merc`, `cot_tipo_servi`, `cot_peso`, `cot_val_minima`, `cot_kilo_adi`, `cot_vol`, `cot_val_asegurado`, `cot_val_seguro`, `cot_val_kilos_adi`, `cot_val_servicio`, `cot__val_total`,cot_fecha,`cot_correo`,cot_Whatsapp,cot_id_ingresa,cot_observaciones) VALUES ('$param1','$param2','$param3','$param44','$param5','$param6','$param7','$param88','$param9','$param10','$param11','$param12','$param13','$param14','$param15','$param166','$param30','$fecha','$param188','$param19','$id_usuario','$param21')";
	$valores[7]=$sql; $valores[4]="cotizaciones.php"; $valores[8]=1; 
	
break;
case "Editar cotizacion":

		$fecha=date("Y-m-d");
	    $sql="UPDATE `cotozaciones` SET `cot_clirente`='$param1',`cot_nit`='$param2',`cot_origen`='$param3',`cot_destino`='$param44',`cot_direc_origen`='$param5',`cot_direc_destino`='$param6',`cot_desc_merc`='$param7',`cot_tipo_servi`='$param88',`cot_peso`='$param9',`cot_val_minima`='$param10',`cot_kilo_adi`='$param111',`cot_vol`='$param12',`cot_val_asegurado`='$param13',`cot_val_seguro`='$param14',`cot_val_kilos_adi`='$param15',`cot_val_servicio`='$param166',`cot__val_total`='$param30',`cot_correo`='$param188',cot_Whatsapp='$param19',cot_piezas='$param20',cot_observaciones='$param21',cot_validada='$param22' WHERE cot_id='$id_param'";
		        

		$valores[7]=$sql; $valores[4]="cotizaciones.php"; $valores[8]=1; 
		
break;
case "Camara comercio":

	if (is_uploaded_file($_FILES['param3']['tmp_name'])){
		// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo1 = $_FILES["param3"]["name"];
		$imagen1 ="CamaraComercio.pdf";
  
		move_uploaded_file($_FILES['param3']['tmp_name'], "./cotizaciones/descargables/".$imagen1);
	 }else{
		 $imagen1 = "";
	 }
	 $sql=true;
	// $fecha=date("Y-m-d");
	// $sql="UPDATE `cotozaciones` SET `cot_clirente`='$param1',`cot_nit`='$param2',`cot_origen`='$param',`cot_destino`='$param3',`cot_direc_origen`='$param4',`cot_direc_destino`='$param5',`cot_desc_merc`='$param6',`cot_tipo_servi`='$param7',`cot_peso`='$param8',`cot_val_minima`='$param9',`cot_kilo_adi`='$param11',`cot_vol`='$param12',`cot_val_asegurado`='$param13',`cot_val_seguro`='$param14',`cot_val_kilos_adi`='$param15',`cot_val_servicio`='$param16',`cot__val_total`='$param17',`cot_correo`='$param18',cot_Whatsapp='$param19' WHERE cot_id='$id_param'";
	 $valores[7]=$sql; $valores[4]="cotizaciones.php"; $valores[8]=1; 
	
break;
case "Rut":

	if (is_uploaded_file($_FILES['param3']['tmp_name'])){
		// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo1 = $_FILES["param3"]["name"];
		$imagen1 ="Rut.pdf";
  
		move_uploaded_file($_FILES['param3']['tmp_name'], "./cotizaciones/descargables/".$imagen1);
	 }else{
		 $imagen1 = "";
	 }
	 $sql=true;
	// $fecha=date("Y-m-d");
	// $sql="UPDATE `cotozaciones` SET `cot_clirente`='$param1',`cot_nit`='$param2',`cot_origen`='$param',`cot_destino`='$param3',`cot_direc_origen`='$param4',`cot_direc_destino`='$param5',`cot_desc_merc`='$param6',`cot_tipo_servi`='$param7',`cot_peso`='$param8',`cot_val_minima`='$param9',`cot_kilo_adi`='$param11',`cot_vol`='$param12',`cot_val_asegurado`='$param13',`cot_val_seguro`='$param14',`cot_val_kilos_adi`='$param15',`cot_val_servicio`='$param16',`cot__val_total`='$param17',`cot_correo`='$param18',cot_Whatsapp='$param19' WHERE cot_id='$id_param'";
	 $valores[7]=$sql; $valores[4]="cotizaciones.php"; $valores[8]=1; 
	
break;
case "Certificacion bancaria":

	if (is_uploaded_file($_FILES['param3']['tmp_name'])){
		// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
		$nombreArchivo1 = $_FILES["param3"]["name"];
		$imagen1 ="CertificacionBancaria.pdf";
  
		move_uploaded_file($_FILES['param3']['tmp_name'], "./cotizaciones/descargables/".$imagen1);
	 }else{
		 $imagen1 = "";
	 }
    $sql=true;
	// $fecha=date("Y-m-d");
	// $sql="UPDATE `cotozaciones` SET `cot_clirente`='$param1',`cot_nit`='$param2',`cot_origen`='$param',`cot_destino`='$param3',`cot_direc_origen`='$param4',`cot_direc_destino`='$param5',`cot_desc_merc`='$param6',`cot_tipo_servi`='$param7',`cot_peso`='$param8',`cot_val_minima`='$param9',`cot_kilo_adi`='$param11',`cot_vol`='$param12',`cot_val_asegurado`='$param13',`cot_val_seguro`='$param14',`cot_val_kilos_adi`='$param15',`cot_val_servicio`='$param16',`cot__val_total`='$param17',`cot_correo`='$param18',cot_Whatsapp='$param19' WHERE cot_id='$id_param'";
	 $valores[7]=$sql; $valores[4]="cotizaciones.php"; $valores[8]=1; 
	
break;
case "Entregar valor":
	$param3=str_replace(".","", $param3);
	
/* 	$sqldelect="DELETE FROM `asignaciondinero` WHERE asi_idpromotor='$param2' and asi_fecha='$param1' and asi_tipo='entregado'";
	$DB->Execute($sqldelect); */
	$hora=date("H:i:s");
	 $sql2="UPDATE `seguimiento_user` SET seg_fechafinalizo='$hora'  WHERE `seg_idusuario`='$param2'  and seg_fechaingreso like '$param1%'";
	$DB->Execute($sql2);

	$sql="INSERT INTO `asignaciondinero`(`asi_idpromotor`,`asi_fecha`, `asi_valor`,  `asi_idautoriza`,  `asi_idciudad`,asi_tipo)
	VALUES ('$param2','$param1','$param3','$id_usuario','$param4','entregado')";
	
/* 	$valorapagar=$_REQUEST["valorapagar"]-$param3;
		
	$saa="INSERT INTO `duedapromotor`(`iddeudapromotor`, `deu_idpromotor`, `deu_fecha`, `deu_valor`) VALUES  ('', '$param2', '$param1',$valorapagar)";
	$DB->Execute($saa); */
	
	
	$valores[7]=$sql; $valores[4]="cuentasoper.php"; $valores[8]=1; 
	
	break;
	case "Trabaja con:":
		echo$sql0="SELECT idseguimiento_user from seguimiento_user where seg_fechaalcohol like '$param3%'  and seg_idusuario='$param33'";
		$DB1->Execute($sql0); 
		$rw1=mysqli_fetch_row($DB1->Consulta_ID);

		$hora=date("H:i:s");
		echo$sql1="UPDATE `seguimiento_user` SET `seg_compañero`='$param4'  WHERE `idseguimiento_user`='".$rw1[0]."' ";
		$vinculo=$DB1->Executeid($sql1);	
		echo$sql="UPDATE `seguimiento_user` SET `seg_compañero`='$param33'  WHERE `idseguimiento_user`='$param2' ";
		
		
		$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
		
	break;	
	case "cajamenor":
	$param5=str_replace(".","", $param5);
	$sql="INSERT INTO `cajamenor`( `caj_idciudadori`, `caj_idciudaddes`, `caj_tipotransacion`, `caj_descripcion`,`caj_valor`,`caj_idusuario`, `caj_fecharegistro`)
	VALUES ('$param1','$param2','$param3','$param4','$param5','$id_usuario','$fechatiempo')";
	$vinculo=$DB->Executeid($sql);	
	$QL->addDocumento1($_FILES["param6"], 1, "cajamenor", $vinculo, "cajamenor", $DB);
	$sql="SELECT * from documentos where doc_tabla='cajamenor' and doc_idviene='$vinculo' limit 1";
	$valores[7]=$sql; $valores[4]="cajamenor.php"; $valores[8]=1; 
	
	break;
	case "FotoRemesa":
		if($_FILES["param10"]!=''){
			$QL->addDocumento1($_FILES["param10"], 2, "gastos", $id_param, "remesas", $DB);
			$sql="SELECT * from documentos where doc_tabla='gastos' and doc_idviene='$vinculo' and doc_version=2 limit 1";
		}
		
		$valores[7]=$sql;  $valores[4]=$_SERVER["HTTP_REFERER"]; $valores[8]=1; 
	break;
	case "Fotoguia":
		if($_FILES["param10"]!=''){
			$QL->addimagenguia($_FILES["param10"],$param6,$param7, $id_param,$DB);
		}
		$sql="Select * from imagenguias where ima_idservicio=$id_param and ima_tipo='$param7'";
		$valores[7]=$sql;  $valores[4]=$_SERVER["HTTP_REFERER"]; $valores[8]=1; 
	break;
	case "addfotoguias":
		if($_FILES["param10"]!=''){
			$QL->addfotosguia($_FILES["param10"],$param6,$param7, $id_param,$DB);
		}
		$sql="Select * from fotosguias where fot_idservicio=$id_param and fot_tipo='$param7'";
		$valores[7]=$sql;  $valores[4]=$_SERVER["HTTP_REFERER"]; $valores[8]=1; 
	break;
	case "transpasodinero":
	if($nivel_acceso==1){

		$param5=str_replace(".","", $param5);
		$sql="INSERT INTO `asignaciondinero`( `asi_idpromotor`,`asi_fecha`,`asi_fechaingreso`, `asi_valor`,  `asi_idautoriza`,  `asi_idciudad`,asi_tipo,asi_descripcion)
		VALUES ('$param2','$param3','$fechaactual','$param5','$id_usuario','$param1','Transpaso Dinero','Gastos Promotor')";

	}else{
		$param5=str_replace(".","", $param5);
		$sql="INSERT INTO `asignaciondinero`( `asi_idpromotor`,`asi_fecha`,`asi_fechaingreso`, `asi_valor`,  `asi_idautoriza`,  `asi_idciudad`,asi_tipo,asi_descripcion)
		VALUES ('$param2','$fechaactual','$fechaactual','$param5','$id_usuario','$id_sedes','Transpaso Dinero','Gastos Promotor')";

	}

/* 	$vinculo=$DB->Executeid($sql);	
	$QL->addDocumento1($_FILES["param6"], 1, "asignaciondinero", $vinculo, "asignaciondinero", $DB);
	$sql="SELECT * from documentos where doc_tabla='cajamenor' and doc_idviene='$vinculo' limit 1"; */

	$valores[7]=$sql; $valores[4]="transpasodinero.php"; $valores[8]=1; 
	
	break;

	case "Llamar Remesas":

		$sql="UPDATE `gastos` SET  `gas_estadollamada`='$param7',`gas_llamodesc`='$param8',gas_fechallamo='$fechatiempo',gas_telconductor='$param1' WHERE `idgastos`='$id_param' ";			
		$valores[7]=$sql; $valores[4]="adm_validardatos.php"; $valores[8]=1; 

	break;
	case "LlamarReclamos":
		$sql3="update  `servicios` set ser_estado='18' where idservicios='$param10'";
		$DB->Execute($sql3);
		//echoLog($sql3);
		 $sql="update  `cuentaspromotor` set cue_estado='18' where cue_idservicio='$param10'";
		 $DB->Execute($sql);

		$sql="UPDATE `reclamos` SET `rec_fechaconfirmacion`='$fechatiempo',`fec_descricomf`='$param2',rec_estado='Abierto' WHERE `idreclamos`='$id_param' ";			
		$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 

	break;


	case "derechopeticion":
		

		 if($_FILES["param1000"]!=''){
			$QL->addDocumento1($_FILES["param1000"], 1, "documentor", $id_param, "documentor", $DB);

			

		}
        if($_FILES["param11"]!=''){
		$QL->addDocumento1($_FILES["param11"], 1, "requerimiento", $id_param, "requerimiento", $DB);
	}


		$sql="UPDATE `reclamos` SET `rec_fechaacuerdo`='$fechatiempo',`rec_acuerdo`='$param1',`rec_valoracuerdo`='$param2',`rec_numerocuenta`='$param4',`rec_tipocuenta`='$param5',`rec_banco`='$param6',rec_estado='Conciliacion',rec_fechapago='$param8' WHERE `idreclamos`='$id_param' ";					
		$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 

	break;

case "agregadocumentos":
		

		 if($_FILES["param12"]!=''){
			$QL->addDocumento1($_FILES["param12"], 1, "plantilla1", $id_param, "plantilla1", $DB);

			

		}
        if($_FILES["param13"]!=''){
		$QL->addDocumento1($_FILES["param13"], 1, "plantilla2", $id_param, "plantilla2", $DB);
	}


       $sql="UPDATE `reclamos` SET `rec_estado`='$param7' WHERE `idreclamos`='$id_param' ";			
		$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 
		
		

	break;

	case "cliente/p":


	
		$sql="UPDATE `reclamos` SET `rec_tipoCliente`='$param20' WHERE `idreclamos`='$id_param' ";		

		$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 

	break;
	case "mensaje":


	
		$sql="UPDATE `noticia` SET `not_visto`='$param25' WHERE `idnoticia`='$id_param' ";		

		$valores[7]=$sql; $valores[4]="mensajes.php"; $valores[8]=1; 

	break;
case "Respuesta":

if (is_uploaded_file($_FILES['param501']['tmp_name'])){
		$imagen = md5(date("Y-m-d-H-i-s").rand(0,9999).$_SESSION['idc']).".jpg";
  
		move_uploaded_file($_FILES['param501']['tmp_name'], "./imgMensajes/".$imagen);
	 }else{
	 	$imagen = "default.png";
	 }

	
		$sql="UPDATE `noticia` SET `not_respuesta`='$param26',`not_imagenResp`='$imagen'WHERE `idnoticia`='$id_param' ";		

		$valores[7]=$sql; $valores[4]="mensajes.php"; $valores[8]=1; 

	break;

	case "acuerdo":
		$sql3="update  `servicios` set ser_estado='19' where idservicios='$param10'";
		$DB->Execute($sql3);
		//echoLog($sql3);
		 $sql="update  `cuentaspromotor` set cue_estado='19' where cue_idservicio='$param10'";
		 $DB->Execute($sql);

		 if($_FILES["param3"]!=''){
			$QL->addDocumento1($_FILES["param3"], 1, "conciliacion", $id_param, "conciliacion", $DB);
		}
		$sql="UPDATE `reclamos` SET `rec_fechaacuerdo`='$fechatiempo',`rec_acuerdo`='$param1',`rec_valoracuerdo`='$param2',`rec_numerocuenta`='$param4',`rec_tipocuenta`='$param5',`rec_banco`='$param6',rec_estado='Conciliacion',rec_fechapago='$param8' WHERE `idreclamos`='$id_param' ";					
		$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 

	break;

	case "Pagoacuerdo":
		$sql3="update  `servicios` set ser_estado='20' where idservicios='$param10'";
		$DB->Execute($sql3);
		//echoLog($sql3);
		 $sql="update  `cuentaspromotor` set cue_estado='20' where cue_idservicio='$param10'";
		 $DB->Execute($sql);
		//`rec_numerocuenta`=[value-21],`rec_tipocuenta`=[value-22],`rec_banco`=[value-23],`rec_valorconsignado`=[value-24],`rec_fechacancelo`=[value-25]
		if($_FILES["param5"]!=''){
			$QL->addDocumento1($_FILES["param5"], 1, "cancelarpago", $id_param, "cancelarpago", $DB);
			
		}
		$sql="UPDATE `reclamos` SET `rec_estado`='$param7' WHERE `idreclamos`='$id_param' ";			
		$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 
		

	break;
	case "Comprobante_nomina_basico":
		// $sql3="update  `servicios` set ser_estado='20' where idservicios='$param10'";
		// $DB->Execute($sql3);
		// //echoLog($sql3);
		//  $sql="update  `cuentaspromotor` set cue_estado='20' where cue_idservicio='$param10'";
		//  $DB->Execute($sql);
		//`rec_numerocuenta`=[value-21],`rec_tipocuenta`=[value-22],`rec_banco`=[value-23],`rec_valorconsignado`=[value-24],`rec_fechacancelo`=[value-25]
		$fechaini = $param4;

		if (is_uploaded_file($_FILES['param5']['tmp_name'])){
			$imagen = md5(date("".$fechaini."-H-i-s").$id_param).".jpg";
	  
			move_uploaded_file($_FILES['param5']['tmp_name'], "./img_nomina/".$imagen);
		 }else{
			 $imagen = "";
		 }



		// if($_FILES["param5"]!=''){
		// 	$QL->addDocumento1($_FILES["param5"], 1, "cancelarpago", $id_param, "cancelarpago", $DB);
			
		// }
		 
		$sql="UPDATE `nomina` SET `nom_img_compro`='$imagen' WHERE nom_fecha_inicio ='$fechaini' and nom_id_usu = '$id_param' and nom_tipo_pago='Basico'";			
		$valores[7]=$sql; $valores[4]="nomina.php"; $valores[8]=1; 
		

	break;
	case "Comprobante_nomina_otros":
		// $sql3="update  `servicios` set ser_estado='20' where idservicios='$param10'";
		// $DB->Execute($sql3);
		// //echoLog($sql3);
		//  $sql="update  `cuentaspromotor` set cue_estado='20' where cue_idservicio='$param10'";
		//  $DB->Execute($sql);
		//`rec_numerocuenta`=[value-21],`rec_tipocuenta`=[value-22],`rec_banco`=[value-23],`rec_valorconsignado`=[value-24],`rec_fechacancelo`=[value-25]
		$fechaini = $param4;

		if (is_uploaded_file($_FILES['param5']['tmp_name'])){
			$imagen = md5(date("".$fechaini."-H-i-s").$id_param).".jpg";
	  
			move_uploaded_file($_FILES['param5']['tmp_name'], "./img_nomina/".$imagen);
		 }else{
			 $imagen = "";
		 }



		// if($_FILES["param5"]!=''){
		// 	$QL->addDocumento1($_FILES["param5"], 1, "cancelarpago", $id_param, "cancelarpago", $DB);
			
		// }
		 
		$sql="UPDATE `nomina` SET `nom_img_compro`='$imagen' WHERE nom_fecha_inicio ='$fechaini' and nom_id_usu = '$id_param' and nom_tipo_pago='Otros'";			
		$valores[7]=$sql; $valores[4]="nomina.php"; $valores[8]=1; 
		

	break;
	case "GastosOperador":
	$param4=str_replace(".","", $param4);
	if($nivel_acceso==1){

		if ($param40==1) {
			$sql3="SELECT veh_kilactual, veh_aceitekil FROM `vehiculos` WHERE idvehiculos='$param19' ";
        $DB1->Execute($sql3);
        $rw3=mysqli_fetch_array($DB1->Consulta_ID);

			// $sql1="UPDATE vehiculos SET `veh_faltaparacambioaceite`='$rw3[1]'  WHERE `idvehiculos`='$param19'";
			// $DB->Execute($sql1);

			$sql2="UPDATE vehiculos SET `veh_faltaparacambioaceite`='$rw3[1]',`veh_calkmcambioaceite`='$rw3[0]'  WHERE `idvehiculos`='$param19'";
			$DB2->Execute($sql2);
		}
		
		$sql1="INSERT INTO `asignaciondinero`(`asi_idpromotor`,`asi_fecha`, `asi_valor`,  `asi_idautoriza`,  `asi_idciudad`,asi_tipo,asi_descripcion,asi_idvehiculo,asi_tipogasvehi)
		VALUES ('$param2','$param3','$param4','$id_usuario','$param1','Gastos','$param6','$param19','$param40')";
		
	}else{
		if ($param40==1) {
            $sql3="SELECT veh_kilactual, veh_aceitekil FROM `vehiculos` WHERE idvehiculos='$param19' ";
            $DB1->Execute($sql3);
            $rw3=mysqli_fetch_array($DB1->Consulta_ID);

			// $sql1="UPDATE vehiculos SET `veh_faltaparacambioaceite`='$rw3[1]'  WHERE `idvehiculos`='$param19'";
			// $DB->Execute($sql1);
			$sql2="UPDATE vehiculos SET `veh_faltaparacambioaceite`='$rw3[1]',`veh_calkmcambioaceite`='$rw3[0]'   WHERE `idvehiculos`='$param19'";
			$DB2->Execute($sql2);
		}
		$sql1="INSERT INTO `asignaciondinero`(`asi_idpromotor`,`asi_fecha`, `asi_valor`,  `asi_idautoriza`,  `asi_idciudad`,asi_tipo,asi_descripcion,asi_idvehiculo,asi_tipogasvehi)
		VALUES ('$id_usuario','$fechaactual','$param4','$id_usuario','$id_sedes','Gastos','$param6','$param19','$param40')";
	}
	
	$vinculo=$DB->Executeid($sql1);	
	$QL->addDocumento1($_FILES["param7"], 1, "asignaciondinero", $vinculo, "asignaciondinero", $DB);
	$sql="SELECT * from documentos where doc_tabla='asignaciondinero' and doc_idviene='$vinculo' limit 1";
	$valores[7]=$sql; $valores[4]="gastosoperador.php"; $valores[8]=1; 
	break;
	case "horaalmuerzo":
		$sql="UPDATE `seguimiento_user` SET seg_horaalmuerzo='$param3'  WHERE `idseguimiento_user`='$param2' ";

		$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;
	case "horaretorno":
		$sql="UPDATE `seguimiento_user` SET seg_horaregreso='$param3'  WHERE `idseguimiento_user`='$param2' ";

		$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;
	case "horaoficina":
		$sql="UPDATE `seguimiento_user` SET seg_horaoficina='$param3'  WHERE `idseguimiento_user`='$param2' ";

		$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;
	case "zonatrabajo":
		$fecha=$param3;
		$param3=date("$param3 H:i:s");	

		$sql="UPDATE `seguimiento_user` SET seg_idzona='$param6'  WHERE `idseguimiento_user`='$param2' ";

		$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;	
	case "ingresousuario":
		
		$fecha=substr($param3,0,10);
		$param3=date("$fecha H:i:s");	

		$sql="UPDATE `seguimiento_user` SET seg_fechaingreso='$param3',seg_motivo='$param4',seg_descr='$param5',seg_idzona='$param6', seg_horas_trabajadas='$param9'  WHERE `idseguimiento_user`='$param2' ";
		$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $param2, "seguimientouser", $DB);
		$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;
	case "Agregar festivos":
		// error_reporting(E_ALL);
		// ini_set('display_errors', 1);
		$fecha=$param6;

		$fechaCompleta=date("$param6 H:i:s");	
		// $fecha=date("Y-m-d");
		// $param3=date("Y-m-d H:i:s");

		$sql2="SELECT idusuarios,usu_nombre,usu_identificacion,usu_tipocontrato,usu_fechalicencia,usu_idsede FROM usuarios inner join hojadevida on hoj_cedula=usu_identificacion WHERE usu_estado = '1'and usu_filtro='1' and usu_tipocontrato='Empresa'   ORDER BY usu_nombre  asc ";

		
		
		$DB->Execute($sql2); 
		$va=0; 
		
		
		
		while($rw0=mysqli_fetch_row($DB->Consulta_ID))
		{

			$sql4="SELECT idhojadevida,hoj_fechatermino FROM `hojadevida` WHERE hoj_cedula = '$rw0[2]' and hoj_estado='Activo' and hoj_fechatermino IS NULL ORDER BY idhojadevida DESC LIMIT 1";
            $DB1->Execute($sql4);
            $rw4=mysqli_fetch_array($DB1->Consulta_ID);

			if ($rw4[1]==NULL or $rw4[1]=="00-00-00") {
				
				$sql3="SELECT `idseguimiento_user`, `seg_idusuario`, `seg_fechaingreso` FROM `seguimiento_user` WHERE seg_fechaingreso like '%$fecha%' and seg_idusuario ='$rw0[0]' ";
				$DB1->Execute($sql3);
				$rw3=mysqli_fetch_array($DB1->Consulta_ID);

				if (count($rw3) == 0) {
						$sql1="INSERT INTO `seguimiento_user`(`seg_idusuario`, `seg_fechaingreso`,seg_motivo,seg_descr,seg_idzona,seg_alcohol,seg_fechaalcohol,`seg_iduserregistro`)
						VALUES ('$rw0[0]','$fechaCompleta','descanso','descanso','','No aplica','$fecha','$id_usuario')";
						$vinculo=$DB1->Executeid($sql1);
						$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);

						$sql="INSERT INTO `pre-operacional`( `prefechaingreso`, `preidusuario`, `preestado`) VALUES ('$param6','$rw0[0]','descanso')";
						
						$inser=$DB2->Executeid($sql);
				} else {
					
				}
			}

			

			
		}





		$valores[7]=$sql; 
		$valores[4]="seguimientouser.php"; 
		$valores[8]=1; 
	break;

	case "Vacaciones":
		// $fecha=$param6;
		$fechaini = $param3;
		$fechafin = $param4;
		$fechaCompleta=date("$param6 H:i:s");	



		// Establecer la fecha inicial y final
		$fechaInicial = new DateTime($fechaini);
		$fechaFinal = new DateTime($fechafin);

		while ($fechaInicial <= $fechaFinal) {
			// Obtener la fecha actual con hora
			$fechaConHora = $fechaInicial->format('Y-m-d H:i:s');
			$fecha=$fechaInicial->format('Y-m-d');


			$sql3="SELECT `idseguimiento_user`, `seg_idusuario`, `seg_fechaingreso` FROM `seguimiento_user` WHERE seg_fechaingreso like '%$fecha%' and seg_idusuario ='$param2' ";
            $DB1->Execute($sql3);
            $rw3=mysqli_fetch_array($DB1->Consulta_ID);

			if (count($rw3) == 0) {
			// Preparar la consulta SQL e insertar en la base de datos
			$sql1="INSERT INTO `seguimiento_user`(`seg_idusuario`, `seg_fechaingreso`,seg_motivo,seg_descr,seg_idzona,seg_alcohol,seg_fechaalcohol,`seg_iduserregistro`)
			VALUES ('$param2','$fechaConHora','Vacaciones','Vacaciones','','No aplica','$fecha','$id_usuario')";
			$vinculo=$DB1->Executeid($sql1);
			$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);
	
			$sql="INSERT INTO `pre-operacional`( `prefechaingreso`, `preidusuario`, `preestado`) VALUES ('$fecha','$param2','vacaciones')";
			
			$inser=$DB2->Executeid($sql);
			}else {
				
			}
			// Incrementar la fecha para la próxima iteración
			$fechaInicial->modify('+1 day');
		}
		

		$valores[7]= true; 
		$valores[4]="seguimientouser.php"; 
		$valores[8]=1; 

	break;
	case "Licencias_y_permisos":
		// $fecha=$param6;
		$fechaini = $param3;
		$fechafin = $param4;
		$fechaCompleta=date("$param6 H:i:s");	



		// Establecer la fecha inicial y final
		$fechaInicial = new DateTime($fechaini);
		$fechaFinal = new DateTime($fechafin);

		while ($fechaInicial <= $fechaFinal) {
			// Obtener la fecha actual con hora
			$fechaConHora = $fechaInicial->format('Y-m-d H:i:s');
			$fecha=$fechaInicial->format('Y-m-d');


			$sql3="SELECT `idseguimiento_user`, `seg_idusuario`, `seg_fechaingreso` FROM `seguimiento_user` WHERE seg_fechaingreso like '%$fecha%' and seg_idusuario ='$param2' ";
            $DB1->Execute($sql3);
            $rw3=mysqli_fetch_array($DB1->Consulta_ID);

			if (count($rw3) == 0) {
			// Preparar la consulta SQL e insertar en la base de datos
			$sql1="INSERT INTO `seguimiento_user`(`seg_idusuario`, `seg_fechaingreso`,seg_motivo,seg_descr,seg_idzona,seg_alcohol,seg_fechaalcohol,`seg_iduserregistro`)
			VALUES ('$param2','$fechaConHora','$param6','$param5','','No aplica','$fecha','$id_usuario')";
			$vinculo=$DB1->Executeid($sql1);
			$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);
	
			$sql="INSERT INTO `pre-operacional`( `prefechaingreso`, `preidusuario`, `preestado`) VALUES ('$fecha','$param2','$param6')";
			
			$inser=$DB2->Executeid($sql);
			}else {

				$sql1="UPDATE `seguimiento_user` SET `seg_motivo`='$param6',`seg_descr`='$param5',`seg_iduserregistro`='$id_usuario',seg_fechaalcohol='$fecha' WHERE seg_fechaingreso like '%$fecha%' and seg_idusuario ='$param2' ";
				
				$vinculo=$DB1->Executeid($sql1);
				
			}
			// Incrementar la fecha para la próxima iteración
			$fechaInicial->modify('+1 day');
		}
		

		$valores[7]= true; 
		$valores[4]="seguimientouser.php"; 
		$valores[8]=1; 

	break;
	case "validartarea":
		
		$estado=$param3;

		 $sql="INSERT INTO `programartareas`(`pro_idtareas`, `pro_comentario`, `pro_estado`, `pro_idusuario`, `pro_fecha`) values ('$param3','$param5','$param4','$idUserActual','$fechatiempo') ";

		$valores[7]=$sql; $valores[4]="vertareas.php"; $valores[8]=1; 
	break;
	case "SeguimientoUser":
		
		$fecha=$param3;
		$param3=date("$param3 H:i:s");	


		$sql2="SELECT count(*), idpreoperacinal 
		FROM `pre-operacional`   
		WHERE  preidusuario='$param2'  
		AND DATE(prefechaingreso) = '$fecha'";
		$DB1->Execute($sql2); 
		$rw0=mysqli_fetch_row($DB1->Consulta_ID);

		if ($rw0[0]==0) {
		$sql="INSERT 
		INTO `pre-operacional`( `prefechaingreso`,
		 `preidusuario`, `preestado`) 
		 VALUES ('$param3','$param2','No aplica')";
		$idviene=$DB->Executeid($sql);
		}

		$sql3="SELECT count(*),idseguimiento_user  
		FROM `seguimiento_user`   
		WHERE  seg_idusuario='$param2'  
		AND DATE(seg_fechaingreso)='$fecha'";
		$DB1->Execute($sql3); 
		$rw1=mysqli_fetch_row($DB1->Consulta_ID);

		if ($rw1[0]==0) {
			$sql1="INSERT 
			INTO `seguimiento_user`(`seg_idusuario`,
			`seg_fechaingreso`,seg_motivo,seg_descr,
			seg_idzona,seg_alcohol,seg_fechaalcohol,
			`seg_iduserregistro`)
			VALUES ('$param2','$param3','$param4',
			'$param5','$param6','$param7',
			'$fecha','$id_usuario')";
		}else {
			$sql1 = "UPDATE `seguimiento_user` 
         SET 
             `seg_fechaingreso` = '$param3',
             `seg_motivo` = '$param4',
             `seg_descr` = '$param5',
             `seg_idzona` = '$param6',
             `seg_alcohol` = '$param7',
             `seg_fechaalcohol` = '$fecha',
             `seg_iduserregistro` = '$id_usuario'
         WHERE idseguimiento_user= '$rw1[1]'";
		}


		$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);
	

		$valores[7]=$sql1; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;

	case "Cambio_seguimientoUser":


		$sql1="UPDATE `seguimiento_user` SET `seg_motivo`='$param4',`seg_descr`='$param5',`seg_idzona`='$param6',`seg_iduserregistro`='$id_usuario',seg_horas_trabajadas='$param9' WHERE idseguimiento_user='$param13'";
		$vinculo=$DB->Executeid($sql1);
		$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);

		// $sql="INSERT INTO `pre-operacional`( `prefechaingreso`, `preidusuario`, `preestado`) VALUES ('$param3','$param2','No aplica')";
		$valores[7]=$sql1; $valores[4]="seguimientouser.php"; $valores[8]=1; 


	break;
	case "crearalarma":

		 $sql1="INSERT INTO `reportealertas`(`rep_idsede`,`rep_alerta`, `rep_fechavencimiento`, `rep_fechareporte`, `rep_emails`, `rep_useractualiza`) VALUES 
		  ('$param1','$param2','$param3','$fechaactual','$param4','$id_nombre')";
		$vinculo=$DB->Executeid($sql1);
		if($_FILES["param5"]!=''){
			$QL->addDocumento1($_FILES["param5"], 1, "reportealarmas", $vinculo, "reportealarmas", $DB);
		}
		$sql='Select 1';
		$valores[7]=$sql; $valores[4]="reportealertas.php"; $valores[8]=1; 
	break;	
	
	case "ingresopruebacovid":
		$fecha=date("Y-m-d");
		$param3=date("Y-m-d H:i:s");
	
		if($param7!='0'){
			 $sql1="INSERT INTO `seguimiento_user`(`seg_idusuario`,seg_alcohol,seg_fechaalcohol)
			VALUES ('$id_usuario','$param7','$fecha')";
			$vinculo=$DB->Executeid($sql1);
			$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);
		
			$sql="Select idseguimiento_user from seguimiento_user where idseguimiento_user=$vinculo";
	
		}
		$valores[7]=$sql; $valores[4]="inicio.php"; $valores[8]=1; 
	break;
	case "ingresoprueba":
		$fecha=date("Y-m-d");
		$param3=date("Y-m-d H:i:s");	

		 $sql1="INSERT INTO `seguimiento_user`(`seg_idusuario`,seg_alcohol,seg_fechaalcohol)
		VALUES ('$param2','$param7','$fecha')";
		$vinculo=$DB->Executeid($sql1);
		$QL->addDocumento1($_FILES["param8"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);

		$sql="INSERT INTO `pre-operacional`( `prefechaingreso`, `preidusuario`, `preestado`) VALUES ('$param3','$param2','No aplica')";

		$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;
	case "Vehiculos":

		  $sql1="INSERT INTO `vehiculos`(`veh_tipo`, `veh_marca`, `veh_placa`, `veh_modelo`,`veh_dueño`, `veh_fechaseguro`, `veh_fechategnomecanica`, `veh_fechamantenimiento`, `veh_kilactual`, `veh_aceitekil`, `veh_estado`,`veh_chasis`, `veh_tipov`, `veh_cilidraje`, `veh_motor`, `veh_color`, `veh_usuve`, `veh_observaciones`,`veh_calkmcambioaceite`) 	
		 VALUES ('$param1','$param2','$param3','$param4','$param5','$param6','$param7','$param8','$param9','$param10','$param11','$param12','$param13','$param14','$param15','$param16','$param17','$param18','$param23')";
		$vinculo=$DB->Executeid($sql1);

		if($_FILES["param19"]!=''){
			$QL->addDocumento1($_FILES["param19"], 1, "Vehiculos", $vinculo, "vehiculo", $DB);

		}
	if($_FILES["param20"]!=''){
		$QL->addDocumento1($_FILES["param20"], 2, "Vehiculos", $vinculo, "vehiculo", $DB);
	} if($_FILES["param21"]!=''){
		$QL->addDocumento1($_FILES["param21"], 2, "Vehiculos", $vinculo, "vehiculo", $DB);
	} if($_FILES["param22"]!=''){

		$QL->addDocumento1($_FILES["param22"], 2, "Vehiculos", $vinculo, "vehiculo", $DB);
	}
		$sql="Select * from vehiculos where idvehiculos='$vinculo' ";
		$valores[7]=$sql; $valores[4]="adm_vehiculos.php"; $valores[8]=1; 
	break;
	case "diligencia":

		$sql1="INSERT INTO `diligencias`(`dil_ruta`, `dil_user`, `dil_fecha`, `dil_estado`) VALUES ('$param1',$id_usuario,'$fechatiempo','Activo')";
		$inser=$DB->Executeid($sql1);

		$sql = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,`seg_guia`) values ('$fechatiempo','$inser','$param1','diligencias','Asignada','$id_usuario','Diligencia# $inser')";
		//$DB1->Execute($sqlse);

		$valores[7]=$sql; $valores[4]="reordenar.php"; $valores[8]=1; 
		
		break;
	case "CrearViaje":

		 $sql1="INSERT INTO `seguimiento_remesas`(`seg_fechaini`, `seg_ciudadori`, `seg_ciudadofinal`, `seg_operador`, `seg_idvehiculo`, `seg_userprogra`) 
		 VALUES ('$fechaactual','$param1','$param2','$param3','$param4','$id_nombre')";
		$inser=$DB->Executeid($sql1);	

		$valores[7]=1; $valores[4]="remesasprogramadas.php"; $valores[8]=1; 
		break;
	
case "Remesas":
	$param5=str_replace(".","", $param5);
	$user="SELECT usu_nombre FROM usuarios  where  idusuarios='$param7' ";
	$DB->Execute($user);
	$nomuser=$DB->recogedato(0);

	$sql1="INSERT INTO `gastos`(`idgastos`, `gas_idciudadori`, `gas_idciudaddes`, 
            `gas_empresa`, `gas_bus`, `gas_telconductor`,`gas_pagar`,`gas_iduserremesa`, 
            `gas_nomremesa`,`gas_descripcion`,`gas_peso`,`gas_piezas`,`gas_valor`,
            `gas_idusuario`, `gas_fecharegistro`)
	VALUES ('','$param1','$param2','$param3','$param4','$param5','$param6','$param7',
            '$nomuser','$param8','$param9','$param10','$param11','$id_usuario','$fechatiempo')";
	$vinculo=$DB->Executeid($sql1);	
	$QL->addDocumento1($_FILES["param12"], 2, "gastos", $vinculo, "remesas", $DB);
	$sql="SELECT * from documentos where doc_tabla='gastos' and doc_idviene='$vinculo' and doc_version=2 limit 1";


	 $sqlse = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,`seg_guia`,seg_fechaestado,seg_fechafinalizo) values ('$fechatiempo','$vinculo',CONCAT('Empresa TR: $param3',' - # BUS: $param4','-Tel Conductor: $param5'),'Remesa Entrega','completado','$id_usuario','Remesa# $vinculo','$fechatiempo','$fechatiempo')";
	$DB1->Execute($sqlse);

	$valores[7]=$sql; $valores[4]="gastos.php"; $valores[8]=1; 
	
	break;

// Incluido por Alejandro Morales
case "addremesas":
	$param11 = str_replace(".", "", $param11);
	$param14 = str_replace(".", "", $param14);
        $user="SELECT usu_nombre,usu_telefono,usu_vehiculo FROM usuarios  where  idusuarios='$param7'";
	
	$DB->Execute($user);
        $datosUsuario=mysqli_fetch_row($DB->Consulta_ID);
		$nomuser=$datosUsuario[0];
        $teluser=$datosUsuario[1];
        $vehuser=$datosUsuario[2];
        
        $estado = $param4 == 'Sede Origen' ? 'Pagado' : 'Pendiente';

		if($param15!=='' && $param15!='0'){ //abonos
			$abonos=explode("/",$param15);			
		}
	
		if($param5!=='' && $param5!='0'){ //Viajes
				$viaje=explode("/",$param5);	
		}
        
	$sql1="INSERT INTO `viajesremesas`(`idgastos`, `gas_idciudadori`, `gas_idciudaddes`, 
            `gas_empresa`, `gas_bus`, `gas_telconductor`,`gas_pagar`,`gas_iduserremesa`, 
            `gas_nomremesa`,`gas_descripcion`,`gas_peso`,`gas_piezas`,`gas_valor`,`gas_idusuario`, 
            `gas_fecharegistro`, `gas_metodopago`, `gas_idseguimientoremesas`,gas_abono,gas_abonopago, `gas_estado`)
	VALUES ('','$param1','$param2','$param3','{$vehuser}','{$teluser}','$param4','$param7',
            '$nomuser','$param8','$param9','$param10','$param11','$id_usuario','$fechatiempo', 
            '$viaje[1]','$param13','$param14', '$abonos[1]',  '$estado')";

	$vinculo=$DB->Executeid($sql1);	
	
	if($param15!=='' && $param15!='0'){ //abonos

		$planilla2='Abono#'.$vinculo;
		$idservicio2=$vinculo;

		$sql4="INSERT INTO `pagoscuentas`(`pag_tipopago`,`pag_cuenta`, `pag_valor`, `pag_idoperario`, `pag_idservicio`,`pag_guia`, `pag_estado`, `pag_fecha`) 
		VALUES ('$abonos[0]','$abonos[2]','$param14','$id_usuario','$idservicio2','$planilla2','Abono Viaje','$fechatiempo')";
		$DB1->Execute($sql4); 
		
	}

	if($param5!=='' && $param5!='0'){ //viajes

		$planilla='Viaje#'.$vinculo;
		$idservicio=$vinculo;

		$sql5="INSERT INTO `pagoscuentas`(`pag_tipopago`,`pag_cuenta`, `pag_valor`, `pag_idoperario`, `pag_idservicio`,`pag_guia`, `pag_estado`, `pag_fecha`) 
		VALUES ('$viaje[0]','$viaje[2]','$param11','$id_usuario','$idservicio','$planilla','Pago Viaje','$fechatiempo')";
		$DB1->Execute($sql5);
		
	}
	
	$QL->addDocumento1($_FILES["param12"], 1, "viajesremesas", $vinculo, "viajesremesas", $DB);
	$sql="SELECT * from documentos where doc_tabla='viajesremesas' and doc_idviene='$vinculo' and doc_version=2 limit 1";

	$valores[7]=$sql; $valores[4]="remesasprogramadas.php"; $valores[8]=1; 
	
	break;
    
case "addgastos":
    
    $param4 = str_replace(".", "", $param4);
    $param5 = 'Gastos viaje';
    
    $sql1="INSERT INTO `asignaciondinero`(`asi_idpromotor`,`asi_fecha`,`asi_fechaingreso`, 
        `asi_valor`,  `asi_idautoriza`,  `asi_idciudad`,asi_tipo,asi_descripcion, `asi_idseguimientoremesas`)
	VALUES ('$param2','$param3','$fechaactual','$param4','$id_usuario','$param1','$param5','$param6','$param8')";
    
    $vinculo=$DB->Executeid($sql1);	
    $QL->addDocumento1($_FILES["param7"], 1, "asignaciondinero", $vinculo, "asignaciondinero", $DB);
    $sql="SELECT * from documentos where doc_tabla='asignaciondinero' and doc_idviene='$vinculo' and doc_version=2 limit 1";

    $valores[7]=$sql; $valores[4]="remesasprogramadas.php"; $valores[8]=1; 
        
    
    break;
    
	case "validarpreoperacional":

	$estado=$_POST["estado"]; 
	$data=$_POST["data"]; 
	if($estado=='covid19'){
		$estado='Validado Covid19';
	}else{
		$estado='Validado';
	}
	$sql="UPDATE `pre-operacional` SET `prefechavalidacion`='$fechatiempo',`predatosvalidados`='$data',`pre_descvalidada`='$param10',pre_iduservalida='$id_usuario',`preestado`='$estado', `pre_correctiva`='$param9', `pre_responsable`='$param9', `pre_temperatura`='$param19', `pre_kilrecorridos`='$param12' WHERE idpreoperacinal=$param11";
	//$QL->addDocumento1($_FILES["param20"], 1, "pre-operacional", $param11, "preoperacional", $DB);
	if($param12>0){
		$sql2="UPDATE vehiculos set veh_kilactual='$param12' where idvehiculos='$param1'";
		$DB->Execute($sql2);
	}
	$valores[7]=$sql; $valores[4]="seguimientouser.php"; $valores[8]=1; 
	break;
	case "Temperatura":

		$QL->addDocumento1($_FILES["param2"], 2, "pre-operacional", $id_param, "preoperacional", $DB);
		$sql="SELECT * from `pre-operacional` where idpreoperacinal=$id_param";
		$valores[7]=$sql; $valores[4]="inicio.php"; $valores[8]=1; 
	break;
	case "Salida":
		$fechaactual=date('Y-m-d');
		if (is_uploaded_file($_FILES['param2']['tmp_name'])){
		
			$imagen2 = date("Y-m-d-H-i-s").$_FILES["param2"]["name"];
	  
			move_uploaded_file($_FILES['param2']['tmp_name'], "./preoperacional/".$imagen2);
		 }else{
			  $imagen2="";
		 } 
		echo$sql="UPDATE `pre-operacional` SET pre_img_kilo_sal='$imagen2', pre_kilom_sal='$param9'  WHERE 	prefechaingreso like '%$fechaactual%' and preidusuario='$id_usuario'";


		$valores[7]=$sql; $valores[4]="inicio.php"; $valores[8]=1; 
	break;
	case "preoperacional":
		
		$data=$_POST["data"]; 
		$estado=$_POST["estado"]; 
		if (is_uploaded_file($_FILES['param30']['tmp_name'])){
		
			$imagen2 = date("Y-m-d-H-i-s").$_FILES["param30"]["name"];
	  
			move_uploaded_file($_FILES['param30']['tmp_name'], "./preoperacional/".$imagen2);
		 }else{
			  $imagen2="";
		 } 
		$sql1="INSERT INTO `pre-operacional`(`prevehiculo`, `pretipovehiculo`, `prefechaingreso`, `preidusuario`, `preencuesta`,`preestado`,`pre_obsevaciones`, `pre_correctiva`, `pre_responsable`,`pre_temperatura`,`pre_kilrecorridos`,`pre_limpiomaleta`,	pre_img_kilo)
		VALUES ('$param1','$param2','$fechatiempo','$id_usuario','$data','$estado','$param7','$param8','$param9','$param19','$param12','$param21','$imagen2')";
		$vinculo=$DB->Executeid($sql1);
	
		$QL->addDocumento1($_FILES["param20"], 1, "pre-operacional", $vinculo, "preoperacional", $DB);
        
        $sql="SELECT veh_kilactual, veh_faltaparacambioaceite FROM `vehiculos` WHERE idvehiculos='$param1' ";
        $DB1->Execute($sql);
        $rw3=mysqli_fetch_array($DB1->Consulta_ID);
			 // $kmayer=$DB1->recogedato(0);

		//para calcular el kilometraje restante para cambio de aceite 
		if($param12>0){
			$restakmaceite=$param12-$rw3[0];
			$descuentakmaceite=$rw3[1]-$restakmaceite;



			$sql2="UPDATE vehiculos set veh_kilactual='$param12',veh_restankmaceite='$restakmaceite',veh_faltaparacambioaceite='$descuentakmaceite' where idvehiculos='$param1'";
			$DB->Execute($sql2);
		}

		$sql="SELECT * from `pre-operacional` where idpreoperacinal=$vinculo";
		$valores[7]=$sql; $valores[4]="inicio.php"; $valores[8]=1; 
	break;
	case "RegistrarPago":

		$sql="UPDATE `incapacidades` SET `ref_fechapagoincapacidad`='',`ref_valorpagado`='',`ref_validadopago`='',`ref_fechavalidacion`=''  WHERE `idincapacidades`='$param4' ";
		$valores[7]=$sql; $valores[4]="new_hojadevida.php"; $valores[8]=1; 

	break;
	case "pruebaalcohol":
		//$fecha=$_POST["fecha"]; 
		$fecha=$param3;
		

		if($param5=='update'){
			 $sql="UPDATE `seguimiento_user` SET  `seg_alcohol` ='$param1'  WHERE `idseguimiento_user`='$param4' ";
			$vinculo=$param4;

		}else{


			if($param1=='Positivo'){
				$sql2="INSERT INTO `seguimiento_user`(`seg_idusuario`, `seg_fechaingreso`,seg_descr,seg_motivo,seg_idzona,`seg_iduserregistro`)
				VALUES ('$param2','$param3','Positivo alcoholemia','Sancionado','Sin zona','$id_usuario')";
				$alcolemia=$DB->Executeid($sql2);	
			}			

			$sql1="INSERT INTO  `seguimiento_user`(seg_alcohol,seg_idusuario,seg_fechaalcohol) VALUES ('$param1','$param4','$fecha'); ";
			$vinculo=$DB->Executeid($sql1);	
			$sql="SELECT seg_alcohol FROM `seguimiento_user`   WHERE `idseguimiento_user`='$vinculo' ";
		}

		$QL->addDocumento1($_FILES["param2"], 1, "seguimiento_user", $vinculo, "seguimientouser", $DB);
	
		if($nivel_acceso!=1 and $nivel_acceso!=5 and $nivel_acceso!=12){
			$pagina="nuevo_admin.php?tabla=ingresoprueba";
		}else{
			$pagina="seguimientouser.php?param34=$fecha";
		}
		$valores[7]=$sql; $valores[4]="$pagina"; $valores[8]=1; 

	break;
	case "Cierre del dia":

	$param5=str_replace(".","", $param5);
	
	$dinero=$_POST["dinero"];
	$valoresjson=$_POST["valoresjs"];
	$valores2json=$_POST["valores2js"];
	
	//$idciudad=$_REQUEST["idciudad"];
	$fechacierre=$_REQUEST["fecharecierre"];
	$sel="DELETE FROM cuentassede WHERE cus_idsede='$id_param' and  cus_fecha like '$fechacierre%'";
	$DB->Execute($sel);	

	$fechacierre= date("$fechacierre H:i:s"); 
	$valoresjson=json_decode($valoresjson);
	$valores2json=json_decode($valores2json);
	
	$sql="INSERT INTO `cuentassede`(`cus_idsede`, `cus_fecha`, `cus_dinerosede`, `cus_datos`,`cue_caja`) VALUES  ('$id_param','$fechacierre','$dinero','$valoresjson','$valores2json')";
	
	$valores[7]=$sql; $valores[4]="cajasciudades.php"; $valores[8]=1; 

	break;
	case "Clientes":
	//$param5=$param5."&".$param51."&".$param19."&".$param20;  
	$param5=$param5."&".$param51."&".$param19."&".$param20."&".$param23."&";  
	$param5 = str_replace('&0&','&&', $param5);
	
	$sql="INSERT INTO `clientes`(`cli_iddocumento`, `cli_nombre`, `cli_telefono`, `cli_idciudad`, `cli_direccion`, `cli_email`, `cli_clasificacion`, `cli_tipo`, `cli_fecharegistro`) 
		VALUES ('$param1','$param2','$param3','$param4','$param5','$param6','$param7',2,'$fechatiempo')";
	$valores[7]=$sql; $valores[4]="clientes.php"; $valores[8]=1; 
	break;
	case "Usuario":
		$param4=md5($param4);
	 	 $sql="INSERT INTO `usuarios`(roles_idroles, usu_nombre, usu_usuario,usu_pass,usu_mail,usu_idtipodocumento,usu_identificacion, usu_genero, usu_fechanacimiento, usu_idsede, usu_telefono, usu_celular,usu_nivelacademico,usu_tipovehiculo,usu_vehiculo,usu_licencia,usu_fechalicencia,usu_tipocontrato,usu_estado,usu_idcredito) VALUES 
		('$param1','$param2','$param3','$param4','$param5','$param6','$param7','$param8','$param9','$param10','$param11','$param12','$param13','$param18','$param19','$param20','$param21','$param22','$param14','$param23')";
		
		//PAra agregar hoja de vida de una vez al crear el usuario 
		if (is_uploaded_file($_FILES['param101']['tmp_name'])){
			$imagen = md5(date("Y-m-d-H-i-s").rand(0,9999).$_SESSION['usuario_id']).".jpg";
	  
			move_uploaded_file($_FILES['param101']['tmp_name'], "./imgUsuarios/".$imagen);
		 }else{
			$imagen ="";
		 }
			 $sql1="INSERT INTO `hojadevida`(`hoj_fechaingreso`,`hoj_nombre`, `hoj_apellido`, `hoj_fechanacimiento`, `hoj_cedula`,`hoj_celular`, `hoj_sede`,`hoj_estado`,`hoj_cuen`,`hoj_banco`,`hoj_foto`,hoj_cargo,`hoj_tipocontrato`,`hoj_fechacontrato`) 
			 VALUES                                 ('$param34','$param2','$param30','$param9','$param7','$param11','$param10','Activo','$param32','$param33','$imagen','$param36','$param22','$param34')";
			 $vinculo=$DB->Executeid($sql1);

			 if($_FILES["param21"]!=''){
				$QL->addDocumento1($_FILES["param21"], 3, "hojadevida", $vinculo, "hojadevida", $DB); //cedula
				}
			if($_FILES["param21"]!=''){
					$QL->addDocumento1($_FILES["param35"], 16, "hojadevida", $vinculo, "hojadevida", $DB); //cedula
				}
				
	$valores[7]=$sql; $valores[4]="adm_usuarios.php"; $valores[8]=1; 

	break;
	case "rel_crecli": 
		if(@$param5==''){
			 $sql="SELECT `idclientesdir` FROM `clientes` inner join clientesdir on cli_idclientes=idclientes  where cli_telefono=$param2";
			$DB->Execute($sql);
			 $param5=$DB->recogedato(0);
		}

		$sql="INSERT INTO `rel_crecli`( `rel_idcredito`, `rel_idcliente`) VALUES ('$param1','$param5')";

		if (isset($_POST['recargar']) and $_POST['recargar']=="no") {
			// La variable recargar viene en el POST
			if($DB->Execute($sql)){
				echo "ok";
			}else{
				echo "Error al relacionar";
			}
			exit;
			
		} 
			// No viene en el POST	
			$valores[7]=$sql; $valores[4]="relacioncreditos.php"; $valores[8]=1; 
		

	break;
	case "TipoPago": 
		$fechat=date("$param5 H:i:s");
		$sql="UPDATE `facturascreditos` SET `fac_fechapago`='$fechat',`fac_tipopago`='$param4',fac_userpago='$id_nombre',fac_descripcion='$param6' WHERE idfacturascreditos='$param2'";
		$QL->addDocumento1($_FILES["param3"], 2, "facturascreditos", $param2, "facturascreditos", $DB);
		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 
	break;
	case "tipocontrato": 
		$sql="UPDATE `usuarios` SET `usu_tipocontrato`='$param22' WHERE idusuarios='$param2'";
		$valores[7]=$sql; $valores[4]="adm_usuarios.php"; $valores[8]=1; 
	break;
	case "Buzon": 

     echo  $sql="SELECT idusuarios FROM usuarios  where roles_idroles= '$param4' and  usu_idsede='$param5'";
 
		$DB->Execute($sql); 
  while($rw2=mysqli_fetch_row($DB->Consulta_ID))
  {


if (is_uploaded_file($_FILES['param500']['tmp_name'])){
		if ($_FILES['param500']['type']=='image/png' || $_FILES['param500']['type']=='image/jpeg'){

			move_uploaded_file($_FILES["param500"]["tmp_name"],"./imgMensajes/".$_FILES["param500"]["name"]);

		// $imagen = md5(date("Y-m-d-H-i-s").rand(0,9999).$_SESSION['idc']).".jpg";
			$imagen = $_FILES["param500"]["name"]; 

	}elseif($_FILES['param500']['type']=='application/pdf'){
            move_uploaded_file($_FILES["param500"]["tmp_name"],"./imgMensajes/".$_FILES["param500"]["name"]);
            $imagen = $_FILES["param500"]["name"]; 
            $tipoD="pdf";

            }else{$tipoD='';}
   
		// move_uploaded_file($_FILES['param500']['tmp_name'], "./imgMensajes/".$imagen);
	 }else{
	 	
	 }


$sql=" INSERT INTO noticia (not_fecha, not_titulo, not_descripcion, not_expiracion,not_idrol,not_idusuario,not_userinsert,not_imagen,not_idDe,not_ciudad,not_visto,not_menMas,not_respuesta) VALUES ('$fechatiempo', '$param1', '$param2', '$param3','$param4','$param6','$id_nombre','$imagen','$idUserActual','$param5','no','si','$tipoD')";
echo$rw2[0];

  }
	$valores[7]=$sql; $valores[4]="salaChats.php";
		  $valores[8]=1; 	
		
		//$QL->delete('duedapromotor', $DB, 'doc_idviene', $id_param,'duedapromotor');
		// $QL->addDocumento1($_FILES["param500"], 1, "buzon", $vinculo, "buzon", $DB);
	
		
		break;
		
	case "Tareas": 
	 $diassemana=implode(",",$_REQUEST["diassemana"]);
	 
	 $sql="INSERT INTO `tareas`(`tar_descripcion`, `tar_diassemana`,tar_hora, `tar_fecha`, `tar_idrol`, `tar_idsede`, `tar_idoperario`,`tar_usuario`,`tar_estado`)   VALUES ('$param1','$diassemana','$param7','$param3','$param4','$param5','$param6','$id_nombre','Activo')";	
	
	$valores[7]=$sql; $valores[4]="programartareas.php"; $valores[8]=1; 
	break;
		
	case "Abonos": 
		if($param5!=''){
			$param1=str_replace(".","", $param1);
			$sql="INSERT INTO `abonosguias`(`abo_fecha`, `abo_valor`, `abo_idservicio`, `abo_iduser`, `abo_idsede`, `abo_estado`)  VALUES ('$fechatiempo','$param1','$param5','$id_usuario','$id_sedes','abono')";
		
			 $sql3="update  `servicios` set ser_valorabono=ser_valorabono+$param1 where idservicios='$param5'";
			$DB->Execute($sql3);
			//echoLog($sql3);
			 $sql4="update  `cuentaspromotor` set cue_abono=cue_abono+$param1 where cue_idservicio='$param5'";
			 $DB->Execute($sql4);
			//echoLog($sql3);
			$valores[7]=$sql; $valores[4]="asignar_abonos.php"; $valores[8]=1; 
			
		}else{
			$sql="";
		}
		$valores[7]=$sql; $valores[4]="asignar_abonos.php"; $valores[8]=1; 
	
	break;
	case "reclamos": 
		if($param10!=''){
		
			$variableunica=date("Y").date("m").date("d").date("h").date("i").date("s").$id_usuario;
			$sql="INSERT INTO `reclamos`(`rec_numero`, `rec_fechaingreso`, `rec_fechaenvio`, `rec_tipo`, `rec_nombre`, `rec_telefono`, `rec_correo`,`rec_descripcion`, `rec_guia`, `rec_idservicio`, `rec_ciudadenvio`, `rec_direccion`, `rec_userregistra`,rec_estado) 
			values ('$variableunica','$fechaactual','$param8','$param9','$param4','$param5','$param6','$param7','$param2','$param10','$param1','$param11','$id_nombre','Abierto')";
			$vinculo=$DB->Executeid($sql);
			 $sql3="update  `servicios` set ser_estado='18' where idservicios='$param10'";
			$DB->Execute($sql3);
			//echoLog($sql3);
			 $sql="update  `cuentaspromotor` set cue_estado='18' where cue_idservicio='$param10'";
			 //$DB->Execute($sql);
			//echoLog($sql3);
			if($_FILES["param8"]!=''){
				$QL->addDocumento1($_FILES["param8"], 1, "reclamos", $vinculo, "reclamos", $DB);
			}
			
			$mensaje = "
			<html>
			<head>
			<title>HTML</title>
			</head>
			<body>
			<h1>Reclamo de Guia</h1>
			<p>
			Hola $param4. <br>Hemos recibido su solicitud de reclamo, de la guia $param2 ha sido recibida. 
			<br> nos estaremos comunicando con usted para seguir con el proceso de reclamo. 
			<br> por favor estar pendiente del correo y telefono.
			<br> Su numero de reclamo es: $variableunica
			</p>
			</body>
			</html>";
			$corEmpresa='pqrtransmillas@gmail.com';
			$paramEnv=$param6;



			$emails = array($corEmpresa, $param6);

			for($i = 0; $i < count($emails); $i++) {

			enviar_mail2($emails[$i],'',$mensaje,$param4,'Reclamo',1);
    		}



			
			// enviar_mail2($corEmpresa,'',$mensaje,$param4,'Reclamo',1);

			$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 
			
		}else{
			$sql="";
		}
		$valores[7]=$sql; $valores[4]="reclamos.php"; $valores[8]=1; 
	
	break;
	case "crearfactura2": 
		
		$sql="UPDATE `facturascreditos` SET `fac_fechafacturado`='$param4',`fac_fechavencimiento`='$param5',`fac_estado`='Facturado',`fac_iduserfac`='$id_usuario',`fac_idfacturados`='$param9',`fac_precio`='$param1',`fac_numeroref`='$param2',fac_tipopago='Pendiente' WHERE idfacturascreditos='$param8'";
			
		if($DB->Execute($sql)){
			$sel="UPDATE `servicios` SET `ser_numerofactura`='$param2' where `idservicios` in ($param9)";
			$DB->Execute($sel);	
		}
		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 

	break;
	case "crearfacturaexterna": 
		
		$primera = substr($param9,0,1);
		$ultima = substr($param9,-1,1);
	   if($primera==','){
		   $param9=substr($param9,1);
	   }
	   if($ultima==','){
		   $param9 = substr($param9,-1);
	   }
	   //echo $param10;
	   if($param10!='Editar'){
		   $cond=",`fac_estado`='Facturado',`fac_iduserfac`='$id_usuario',fac_tipopago='Pendiente'";
	   }else{
		   $cond="";
	   }

	   $sql0="DELETE from  `facturascreditos` where  fac_numeroref='$param2'";
		$DB->Execute($sql0); 

	 //  $sql="UPDATE `facturascreditos` SET `fac_fechafacturado`='$param4',`fac_fechavencimiento`='$param5',`fac_idfacturados`='$param9',`fac_precio`='$param1',`fac_numeroref`='$param2' $cond WHERE idfacturascreditos='$param8'";
	 $variable=date("Y").date("m").date("d").date("h").date("i").date("s");
	 $variableunica=$variable;
	 if($param10!='Editar'){
		echo$sqll1="INSERT INTO `facturascreditos`(`fac_numerofactura`,`fac_fechafactura`,`fac_fechaprefac`,`fac_fechafacturado`,`fac_idservicios`,`fac_idfacturados`,`fac_estado`,`fac_fechavencimiento`,`fac_credito`,`fac_precio`, `fac_numeroref`,`fac_iduserpre`,fac_iduserfac,fac_tipopago,fac_nit,fac_correo_auto) 
		values ('$variableunica','$param12','$fechaactual','$param4','$param9','$param9','Facturado','$param5','EXTERNOS','$param1','$param2','$id_nombre','$id_usuario','Pendiente','$param13','$param50')";
	 }else{
		$sqll1="INSERT INTO `facturascreditos`(`fac_numerofactura`,`fac_fechafactura`,`fac_fechaprefac`,`fac_fechafacturado`,`fac_idservicios`,`fac_idfacturados`,`fac_estado`,`fac_fechavencimiento`,`fac_credito`,`fac_precio`, `fac_numeroref`,`fac_iduserpre`,fac_iduserfac,fac_tipopago) 
		values ('$variableunica','$param12','$fechaactual','$param4','$param9','$param9','Facturado','$param5','EXTERNOS','$param1','$param2','$id_nombre','$id_usuario','Pendiente')";
	 }

	   if($DB->Execute($sqll1)){
		$idservicios1 = explode(',', $param9);
			
				/*	$sql0="DELETE from  `rel_sercre` where  idservicio in (select idservicios from servicios `ser_numerofactura`='$param2')";
				$DB->Execute($sql0); 
				foreach($idservicios1 as $id)
				{
					$sql0="INSERT INTO `rel_sercre`( `idservicio`, `rel_nom_credito`) VALUES ('$id','EXTERNOS')";
					$DB->Execute($sql0); 
				} */

				$sql0="UPDATE  `rel_sercre` set rel_nom_credito='EXTERNOS' where  `idservicio` in ($param9)";
				$DB->Execute($sql0); 

				$param9==''?$param9=0:$param9;

				$sql2="UPDATE `servicios` SET `ser_numerofactura`='',ser_pendientecobrar= CASE
				WHEN ser_pendientecobrar = 6 THEN 1
				WHEN ser_pendientecobrar = 7 THEN 2
				END  where  `ser_numerofactura`='$param2' and `idservicios` not in ($param9)";
				
				$DB->Execute($sql2);
				if($param9!='' and $param9!=0){
					$sel="UPDATE `servicios` SET `ser_numerofactura`='$param2',ser_pendientecobrar=CASE 
					WHEN ser_pendientecobrar = 1 THEN 6
					WHEN ser_pendientecobrar = 2 THEN 7 END where `idservicios` in ($param9)";
					$DB->Execute($sel);	 

				}

				 $sql="Select fac_numerofactura from facturascreditos where fac_numerofactura='$variableunica' ";
		
	   }
	  
	   $valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 
   break;
	case "crearfactura": 
		
		 $primera = substr($param9,0,1);
		 $ultima = substr($param9,-1,1);
		if($primera==','){
			$param9=substr($param9,1);
		}
		if($ultima==','){
			$param9 = substr($param9,-1);
		}
		//echo $param10;
		if($param10!='Editar'){
			$cond=",`fac_estado`='Facturado',`fac_iduserfac`='$id_usuario',fac_tipopago='Pendiente'";
		}else{
			$cond="";
		}

		$sql="UPDATE `facturascreditos` SET `fac_fechafacturado`='$param4',`fac_fechavencimiento`='$param5',`fac_idfacturados`='$param9',`fac_precio`='$param1',`fac_numeroref`='$param2',fac_correofac='' $cond WHERE idfacturascreditos='$param8'";
			
		if($DB->Execute($sql)){
			if($param9==''){
				 $sel="UPDATE `servicios` SET `ser_numerofactura`='' where `ser_numerofactura`='$param11'";
				$DB->Execute($sel);	
			}else{

				$sql1="UPDATE `servicios` SET `ser_numerofactura`='' where  `ser_numerofactura`='$param11' and `idservicios` not in ($param9)";
				$DB->Execute($sql1);

				 $sel="UPDATE `servicios` SET `ser_numerofactura`='$param2' where `idservicios` in ($param9)";
				$DB->Execute($sel);	
			}

		}
		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 
	break;
	case "reportealarmas": 
		
		$sql="UPDATE `reportealertas` SET `rep_fechavencimiento`='$param1',`rep_fechareporte`='$fechaactual',`rep_useractualiza`='$id_nombre',rep_link_pago='$param6' WHERE idreportealertas='$id_param'";
		$DB->Execute($sql);	
		if($_FILES["param5"]!=''){
			$QL->addDocumento1($_FILES["param5"], 1, "reportealarmas", $id_param, "reportealarmas", $DB);
		}
		$valores[7]=$sql; $valores[4]="reportealertas.php"; $valores[8]=1; 
		
	break;
	case "pagoconfirmado": 


		$sql="UPDATE `facturascreditos` SET   fac_pagoconfir='$param1',fac_userconfirmo='$id_nombre',fac_fechacomfir='$fechatiempo',fac_valorpendiente=fac_preciofinal-$param1  WHERE idfacturascreditos='$param9'";
		
		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 

	break;
	case "cambiarfactura": 

		$sql="UPDATE `facturascreditos` SET `fac_numeroref`='$param1' WHERE idfacturascreditos='$param2'";
		
		if($DB->Execute($sql)){
			$sel="UPDATE `servicios` SET `ser_numerofactura`='$param1' where `ser_numerofactura`='$valor'";
			$DB->Execute($sel);	
		}
		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 

	break;
	case "editarprefactura": 

		$primera = substr($param9,0,1);
		$ultima = substr($param9,-1,1);
	   if($primera==','){
		   $param9=substr($param9,1);
	   }
	   if($ultima==','){
		   $param9 = substr($param9,-1);
	   }
	   
	   $sql="UPDATE `facturascreditos` SET  `fac_idservicios`='$param9' WHERE idfacturascreditos='$param8'";
		   
	   $valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 

	break;
	case "fecharadicado": 

		$sql="UPDATE `facturascreditos` SET  fac_userradicado='$id_nombre', `fac_fecharadicado`='$param1' WHERE idfacturascreditos='$param2'";				
		$QL->addDocumento1($_FILES["param3"], 1, "facturascreditos", $param2, "facturascreditos", $DB);

		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 
	break;
	case "subirFactura": 

		$sql="UPDATE `facturascreditos` SET   `fac_fecharafacturado`='$param1',fac_preciofinal='$param4',fac_precio='$param4' WHERE idfacturascreditos='$param2'";				
		$QL->addDocumento1($_FILES["param3"], 3, "facturascreditos", $param2, "facturascreditos", $DB);

		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 
	break;
	case "doc_Prefactura": 

		if (is_uploaded_file($_FILES['param3']['tmp_name'])){

			$doc = $param2.".xls";
			$ruta = "./pre_facturas/".$doc;

			// 🔴 Si existe el archivo anterior lo borramos
			if(file_exists($ruta)){
				unlink($ruta);
			}

			// 🟢 Guardamos el nuevo
			if(move_uploaded_file($_FILES['param3']['tmp_name'], $ruta)){
				$sql = true;
			}else{
				$sql = false;
			}

		}else{
			$doc ="";
			$sql = false;	
		}

		$valores[7]=$sql; 
		$valores[4]="informecreditos.php"; 
		$valores[8]=1; 

	break;
	

	case "devolucion": 
		if($param5!=''){
			$param1=str_replace(".","", $param1);
			$sql="INSERT INTO `abonosguias`(`abo_fecha`, `abo_valor`, `abo_idservicio`, `abo_iduser`, `abo_idsede`, `abo_estado`)  VALUES ('$fechatiempo','$param1','$param5','$id_usuario','$id_sedes','devolucion')";
		
	/* 		$sql3="update  `servicios` set ser_valorabono=ser_valorabono-$param1 where idservicios='$param5' and ser_estado<=3";
			$DB->Execute($sql3);
			$sql4="update  `cuentaspromotor` set cue_abono=cue_abono-$param1 where cue_idservicio='$param5' and cue_estado<=3";
			$DB->Execute($sql4); */

			$valores[7]=$sql; $valores[4]="asignar_abonos.php"; $valores[8]=1; 

		}else{
			$sql="";
		}
		$valores[7]=$sql; $valores[4]="asignar_abonos.php"; $valores[8]=1; 
	
	break;
	case "Usuarios-Roles":
	$sel="SELECT idusuarios, roles_idroles, usu_nombre, usu_mail, usu_pass, usu_identificacion FROM usuarios WHERE usu_mail='$id_param'";
	$DB->Execute($sel);		
	$rw=mysql_fetch_row($DB->Consulta_ID);
	$sel="DELETE FROM usuarios WHERE usu_mail='$id_param'";
	$DB->Execute($sel);		
	foreach ($_POST['roles'] as $checkbox){ 
		$saa="INSERT INTO usuarios (idusuarios, roles_idroles, usu_nombre, usu_mail, usu_pass, usu_token, usu_identificacion, usu_estado) 
		VALUES ('', '$checkbox', '$rw[2]', '$rw[3]', '$rw[4]', '', '$rw[5]', '1')";
		$DB->Execute($saa);
	}	  
	$valores[7]="SELECT 0"; $valores[4]="adm_usuarios.php"; $valores[8]=1; 
	break;

	case "Asignar Permisos":
	$condel="";
	if($_POST["permi"]!="0"){  $condel=" AND frp_permiso='".$_POST["permi"]."'";  }
	
	$sel="DELETE FROM formroles WHERE actindgener_idactindgener='$id_param' $condel";
	$DB->Execute($sel);	
	if($_POST["permi"]=="0"){ 
		$sel="DELETE FROM formvisitas WHERE actindgener_idactindgener='$id_param' ";
		$DB->Execute($sel);
	}
	foreach ($_POST['roles'] as $checkbox){ 
		$saa="INSERT INTO formroles (idformroles, actindgener_idactindgener, roles_idroles, frp_permiso) VALUES ('', '$id_param', '$checkbox', '1' )";
		$DB->Execute($saa);
	}	  
	foreach ($_POST['roler'] as $checkbox){ 
		$saa="INSERT INTO formroles (idformroles, actindgener_idactindgener, roles_idroles, frp_permiso) VALUES ('', '$id_param', '$checkbox', '2' )";
		$DB->Execute($saa);
	}	  
	foreach ($_POST['rolea'] as $checkbox){ 
		$saa="INSERT INTO formroles (idformroles, actindgener_idactindgener, roles_idroles, frp_permiso) VALUES ('', '$id_param', '$checkbox', '3' )";
		$DB->Execute($saa);
	}	  
	foreach ($_POST['visitas'] as $checkbox){ 
		$saa="INSERT INTO formvisitas (idformvisitas, actindgener_idactindgener, vis_visita) VALUES ('', '$id_param', '$checkbox' )";
		$DB->Execute($saa);
	}
	$valores[7]="SELECT 0"; $valores[4]=$_SERVER['HTTP_REFERER']."&activo1=2"; $valores[8]=1; 
	break;
	case "Edita tu perfil":
	$saa="UPDATE usuarios SET usu_nombre='".$_POST["paramc1"]."', usu_mail='".$_POST["paramc2"]."', usu_pass='".md5($_POST["paramc3"])."' WHERE idusuarios='$id_usuario'";
	$DB->Execute($saa);
	$QL->addDocumento1($_FILES["paramc4"], 1, "Usuario", $id_usuario, "", $DB);
	$valores[7]="SELECT 0"; $valores[4]=$_SERVER['HTTP_REFERER']; $valores[8]=1; 
	break;
	case "seguimientoruta":
		
		$datos=explode("|",$param1);
		$idseguimiento=$datos[0];
		$tipo=$datos[1];
		$direccion=$datos[2];

		$estado='En ruta';

		if($param1!="" and $param1!="0"){
			if($tipo=='opcionruta'){
				$sql = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,seg_fechaestado,seg_fechafinalizo) values('$fechatiempo','$idseguimiento','$direccion','$tipo','$direccion','$id_usuario','$fechatiempo','$fechatiempo')";
				$idSer="";
			}elseif($tipo=='PENDIENTE X COBRAR'){
				$sql = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,seg_fechaestado,seg_fechafinalizo) values('$fechatiempo','$idseguimiento','$direccion','$tipo','$estado','$id_usuario','$fechatiempo','$fechatiempo')";
				$idSer="";
			}else{
				$sql = "UPDATE  `seguimientoruta`  SET seg_fechaestado='$fechatiempo',seg_estado='$estado' where idseguimientoruta='$idseguimiento'";
				
				$sql3 = "SELECT seg_idservicio FROM `seguimientoruta` WHERE  idseguimientoruta='$idseguimiento'";			
				$DB1->Execute($sql3);
				$rw3 = mysqli_fetch_array($DB1->Consulta_ID);
				$idSer=$rw3[0];
			}
		
			if ($idSer!="") {
					# code...
				
				// $sql = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`) values('$fechatiempo','$idservicio','$direccion','$tipo','$estado','$id_usuario')";
				// $sql1 = "SELECT cli_telefono,ser_idservicio,ser_estado FROM rel_sercli INNER JOIN clientesdir on cli_idclientes=ser_idclientes INNER JOIN servicios on ser_idservicio=idservicios WHERE ser_idservicio='$idseguimiento'";
				$sql1 = "SELECT idservicios,ser_estado,	ser_telefonocontacto,ser_consecutivo FROM `servicios` WHERE  idservicios = '$idSer'";			
				$DB1->Execute($sql1);
				$rw1 = mysqli_fetch_array($DB1->Consulta_ID);
				
				
				if ($rw1 === false) {
					# code...
				}else {


					if ($rw1[1] == 3) {
						$sql2 = "SELECT cli_telefono FROM serviciosdia   WHERE idservicios='$idSer'";			
						$DB1->Execute($sql2);
						$rw2 = mysqli_fetch_array($DB1->Consulta_ID);

						if ($rw2 === false) {
							# code...
						}else {
							$numguia="";
							$telefono=$rw2[0];//
							$idservicio=$idSer;
							$tipo = 'Recogida';

							 enviarAlertaWhat($numguia,$telefono,1,$idservicio);
							// enviarAlertaWhat($numguia,"3160490959",1,$idservicio);
						}
					
				
						
					
					} else {
						$numguia=$rw1[3];
						$telefono=$rw1[2];
						$idservicio=$idSer;
						// $numguia="add123";
						// $telefono=$rw1[2];
						// $idservicio="$idseguimiento";
						$tipo = 'Entrega';
						 enviarAlertaWhat($numguia,$telefono,4,$idservicio);
						// enviarAlertaWhat($numguia,"3160490959",4,$idservicio);
				
					}




					
				}
			}
			
		}else{
			$sql = "";
		}
		 $valores[7]=$sql; $valores[4]="inicio.php"; $valores[8]=1; 
	break;
	case "cambiarruta":
		$datos=explode("|",$param1);
		$idservicioanterior=$param3;

		$idseguimiento=$datos[0];
		$tipo=$datos[1];
		$direccion=$datos[2];

		$estado='En ruta';
		
		if($param1!="" and $param1!="0"){
		

			 $sqlcambio = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,seg_descripcion,seg_fechaestado,seg_fechafinalizo) select seg_fecha,seg_idservicio,seg_direccion,seg_tipo,'Cambioruta','$id_usuario','$param2',seg_fechaestado,'$fechatiempo' from seguimientoruta  where idseguimientoruta=$idservicioanterior";
			$DB->Execute($sqlcambio);

			 $sqlsqlupdate = "UPDATE seguimientoruta SET seg_estado='Asignada',seg_fechaestado='' where idseguimientoruta=$idservicioanterior ";
			 $DB->Execute($sqlsqlupdate);

		
			$QL->addDocumento1($_FILES["param4"], 1, "seguimientoruta", $idservicioanterior, "", $DB);
			if($tipo=='opcionruta'){
				$estado='completado';
				$sql = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,seg_fechaestado,seg_fechafinalizo) values('$fechatiempo','$idseguimiento','$direccion','$tipo','$direccion','$id_usuario','$fechatiempo','$fechatiempo')";
			}else{
				//$sql = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,seg_fechaestado) values('$fechatiempo','$idservicio','$direccion','$tipo','$direccion','$id_usuario','$fechatiempo')";
				$sql = "UPDATE  `seguimientoruta`  SET seg_fechaestado='$fechatiempo',seg_estado='$estado' where idseguimientoruta='$idseguimiento'";

			}
		}else{
			$sql = "";
		}
		 $valores[7]=$sql; $valores[4]="inicio.php"; $valores[8]=1; 
	break;
	case "Agregar carpeta":
		$saa="INSERT INTO `carpetasTransmillas`(`car_nombre`,car_sede,car_rol) VALUES ('$param2','$param3','$param4')";
		$DB->Execute($saa);
		// $QL->addDocumento1($_FILES["paramc4"], 1, "Usuario", $id_usuario, "", $DB);
		$valores[7]="SELECT 0"; $valores[4]=$_SERVER['HTTP_REFERER']; $valores[8]=1; 
	break;
	case "Agregar un documento":
		if (is_uploaded_file($_FILES['param3']['tmp_name'])){
			// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
	  
			$nombreArchivo1 = $_FILES["param3"]["name"];
			$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
			move_uploaded_file($_FILES['param3']['tmp_name'], "./imgDocTransmi/".$imagen1);
		 }else{
			 $imagen1 = "";
		 }
		$saa="INSERT INTO `documentosTransmillas`( `doct_id_carpeta`,`doct_nombre` , `doct_archivo`, `doct_fechavence`) VALUES ('$param1','$param2','$imagen1','$param4')";
		$DB->Execute($saa);
		// $QL->addDocumento1($_FILES["paramc4"], 1, "Usuario", $id_usuario, "", $DB);
		$valores[7]="SELECT 0"; $valores[4]=$_SERVER['HTTP_REFERER']; $valores[8]=1; 
	break;
	
	case "Precios": case "Precios credito":

		if($param100=='normal'){
			$sqlkilos = "INSERT INTO `precios`(`pre_idciudadori`, `pre_idciudaddes`,  `pre_kilo`, `pre_tiposervicio`, `pre_fechaingreso`) VALUES ('$param1','$param2','$param3','$param5','$fechaactual');";
			$idprecioskilos=$DB->Executeid($sqlkilos);
			$valores[4]="precios.php";
		}else{
			$sqlkilos = "INSERT INTO `precios_credito`(`pre_idcredito`,`pre_idciudadori`, `pre_idciudades`,  `pre_preciokilo`, `pre_tiposervicio`, `pre_fechaingreso`) VALUES ('$param1','$param2','$param3','$param4','$param6','$fechaactual');";
			$idprecioskilos=$DB->Executeid($sqlkilos);
			$valores[4]="precios_creditos.php";
		}

		$sqldelete="DELETE FROM configuracionkilos WHERE con_idprecioskilos='$id_param' and `con_tipo`='$param100'";
		$DB->Execute($sqldelete);
		
		$sql = "SELECT `idprecioskilos` FROM `precioskilos`";
		$DB1->Execute($sql);
		$aumento=6;
		while ($rw2 = mysqli_fetch_row($DB1->Consulta_ID)) {

			$aumento++;
			$precio=$_POST['param'.$aumento];
			$sqlprecios = "INSERT INTO `configuracionkilos`(`con_idprecioskilos`, `con_idprecios`,`con_precios`, `con_tipo`) VALUES ('$idprecioskilos','$rw2[0]','$precio','$param100');";
			$DB->Execute($sqlprecios);
		}

		
		$valores[7]=$sql;  $valores[8]=1; 

	break;
	case "editarfecha": 

		 $sql="UPDATE facturascreditos SET  `fac_fechafactura`='$param1' WHERE idfacturascreditos='$param2'";			
		$valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 

	break;
	case "Editar Factura Externa": 

		echo$sql="UPDATE facturascreditos SET  fac_fechavencimiento='$param5',fac_correo_auto='$param4'  WHERE idfacturascreditos =$param2";			
	   $valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 

   break;
	case "Archivo Drive":
	if (is_uploaded_file($_FILES['param1']['tmp_name'])){
		// $imagen1 = md5(date("Y-m-d-H-i-s").$param4).".jpg";
	
		$nombreArchivo1 = $_FILES["param1"]["name"];
		$imagen1 = date("Y-m-d-H-i-s").$nombreArchivo1;
		move_uploaded_file($_FILES['param1']['tmp_name'], "./imgDocTransmi/".$imagen1);
		}else{
			$imagen1 = "";
		}
	$sql="UPDATE usuarios SET  `usu_doc_credito`='$imagen1' WHERE idusuarios='$param2'";			
	$valores[7]=$sql; $valores[4]="inicio.php"; $valores[8]=1; 
	break;
   	case "ConfirmaCompañero": 
		$id=$_GET['id'];
		$idSeg=$_GET['idSeg'];
		date_default_timezone_set('America/Bogota'); // Zona horaria Colombia
		$fechaActual = date("Y-m-d"); // Formato: Año-Mes-Día Hora:Minuto:Segundo
		echo$sql1="UPDATE seguimiento_user SET  seg_conf_compañero='1'  WHERE idseguimiento_user ='$idSeg' ";			
	    $DB1->Execute($sql1);
		exit; // ← Detiene la ejecución aquí
		// $valores[7]=$sql; $valores[4]="informecreditos.php"; $valores[8]=1; 

   

	default:
		$valores=$LT->devuelvecampos($tabla, 1, "");

	break;
}

if($valores[8]==1) {
	
	if($valores[7]==1){
		$bandera=1;
	}else{
		if($DB->Execute($valores[7])){$bandera=1;} else {$bandera=4;} ;
	}
	 
}

else {$bandera=$QL->insert($valores[0], $valores[1], $valores[6], $valores[5], $DB, $tabla, 1); //funcion insert 

 }

if($bandera==1){
	//echo $tabla;
	switch($tabla){

		case "Formulario":
		$sel="SELECT idactindgener FROM actindgener ORDER BY idactindgener DESC";
		$DB->Execute($sel);
		$id_param=$DB->recogedato(0);
		if($_POST["param5"]!="Consulta"){
			$sql_create="CREATE TABLE IF NOT EXISTS respuesta_$idencuesta (idrespuesta_$idencuesta MEDIUMINT NOT NULL AUTO_INCREMENT,
			actindgener_idactindgener INT, preguntas1_idpreguntas1 INT, res_respuesta VARCHAR(1500) NOT NULL, res_justificacion VARCHAR(1500) NOT NULL, 
			res_fecha DATETIME, res_tipopreg VARCHAR(50), res_idunico VARCHAR(50), res_orden INT, res_estado INT, PRIMARY KEY (idrespuesta_$idencuesta) );";
			$DB->Execute($sql_create);
			$va2=$_POST["va2"];
			for($j=1; $j<=$va2; $j++)
			{
				if(isset($_POST["prg$j"])) { $param3=$_POST["prg$j"]; } else { $param3=""; } 
				if($param3!=""){  
					if(isset($_POST["obe$j"])) { $obe=$_POST["obe$j"]; if($obe=="on"){$obe=1;} else {$obe=0;} } else { $obe=""; } 
					if(isset($_POST["tru$j"])) { $tru=$_POST["tru$j"]; } else { $tru=""; } 
					if(isset($_POST["urd$j"])) { $urd=$_POST["urd$j"]; } else { $urd=""; } 
					if(isset($_POST["tpi$j"])) { $tpi=$_POST["tpi$j"]; } else { $tpi=""; } 
					if(isset($_POST["arr$j"])) { $arr=$_POST["arr$j"]; } else { $arr=""; } 
					if(isset($_POST["par$j"])) { $par=$_POST["par$j"]; if($par=="on"){$par=1;} else {$par=0;}  } else { $par=""; } 
					if(isset($_POST["jus$j"])) { $jus=$_POST["jus$j"]; } else { $jus=""; } 
					if(isset($_POST["dep$j"])) { $dep=$_POST["dep$j"]; } else { $dep=""; } 
					if(isset($_POST["con$j"])) { $con=$_POST["con$j"]; } else { $con=""; } 
					if(isset($_POST["met$j"])) { $met=$_POST["met$j"]; if($met=="on"){$met=1;} else {$met=0;}  } else { $met=""; } 
					if(isset($_POST["con$j"])) { $con=$_POST["con$j"]; } else { $con=""; } 
					if(isset($_POST["vmetas$j"])) { $pre=$_POST["vmetas$j"]; } else { $pre=""; } 
					if(isset($_POST["agr$j"])) { $agr=$_POST["agr$j"]; } else { $agr=""; } 
					$sql3="INSERT INTO preguntas1 (idpreguntas1, actindgener_idactindgener, pre_pregunta, pre_tipo, pre_array, pre_parametrizacion, pre_orden, pre_obligatoria, 
					pre_componente, pre_justifica, pre_depende, pre_condicion, pre_areages, pre_proceso) 
					VALUES ('', '$id_param', '$param3', '$tpi', '$arr', '$par', '$urd', '$obe', '$tru', '$jus', '$dep', '$con', '$pre', '$agr') ";
					$DB->Execute($sql3);
				}	
			}
		}
		else {
			$sel="UPDATE actindgener SET aci_array='".$_POST["para-12"]."' WHERE idactindgener='$id_param'";
			$DB->Execute($sel);
		}
		break;
		case "cajamenor":

		break;


	}
}


	header ("Location: $valores[4]?bandera=$bandera&id_param=$id_param&condecion=$condecion&tabla=$tabla");

function enviarAlertaWhat($numguia,$telefono,$tipo,$idservi){

	if (preg_match('/^\d{10}$/', $telefono)) {
		// echo "La variable tiene exactamente 10 números.";

			// URL de la API
		$url = "https://www.transmillas.com/ChatbotTransmillas/alertas.php";

		// Datos que enviarás en la solicitud
		$data = array(
			"numero_guia" => "$numguia", // Número de guía
			"telefono" => "$telefono",  // Número de teléfono 3160490959
			// "telefono" => "3107781913",  // Número de teléfono 3160490959
			"tipo_alerta" => "$tipo",
			"id_guia" => "$idservi"
		);


		// Convertir los datos a formato JSON
		$data_json = json_encode($data);

		// Iniciar una sesión cURL
		$curl = curl_init();

		// Configurar las opciones cURL
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url, // URL de la API
			CURLOPT_RETURNTRANSFER => true, // Retorna el resultado como cadena
			CURLOPT_POST => true, // Indica que la solicitud será POST
			CURLOPT_POSTFIELDS => $data_json, // Los datos que se envían en la solicitud
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json', // Tipo de contenido
				'Authorization: Bearer MiSuperToken123' // Si la API requiere autenticación
			),
		));

		// Ejecutar la solicitud y obtener la respuesta
		$response = curl_exec($curl);

		// Manejar errores cURL
		if($response === false) {
			$error = curl_error($curl);
			echo "Error en la solicitud: $error";
		} else {
			// Decodificar la respuesta (si es JSON)
			$response_data = json_decode($response, true);
			
			// Mostrar la respuesta
			echo "Respuesta de la API: ";
			print_r($response_data);
		}

		// Cerrar la sesión cURL
		curl_close($curl);
	} else {
		echo "La variable no cumple con el formato.";
	}





}



?>