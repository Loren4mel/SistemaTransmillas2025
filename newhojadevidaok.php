<?php
require("login_autentica.php");
include("declara.php");
require_once __DIR__ . "/nueva_plataforma/model/PendientesModel.php";

@$accion=$_REQUEST["accion"];
$fechatiempo=date("Y-m-d H:i:s");
$fecha=date("Y-m-d");

function sincronizarPendientesFijosHojaVida(string $cedula, string $origen): void
{
	try {
		$modeloPendientes = new PendientesModel();
		$resultado = $modeloPendientes->sincronizarPendientesFijosPorCedula($cedula);
		$linea = '[' . date('Y-m-d H:i:s') . '] ' . $origen . ' | cedula=' . $cedula . ' | ' . json_encode($resultado, JSON_UNESCAPED_UNICODE) . PHP_EOL;
		@file_put_contents(__DIR__ . "/nueva_plataforma/logs/pendientes_hojadevida.log", $linea, FILE_APPEND);
	} catch (Throwable $e) {
		$linea = '[' . date('Y-m-d H:i:s') . '] ' . $origen . ' | cedula=' . $cedula . ' | ERROR: ' . $e->getMessage() . PHP_EOL;
		@file_put_contents(__DIR__ . "/nueva_plataforma/logs/pendientes_hojadevida.log", $linea, FILE_APPEND);
	}
}


if($accion==1){

   if (is_uploaded_file($_FILES['param101']['tmp_name'])){
		$imagen = md5(date("Y-m-d-H-i-s").rand(0,9999).$_SESSION['usuario_id']).".jpg";
  
		move_uploaded_file($_FILES['param101']['tmp_name'], "./imgUsuarios/".$imagen);
	 }else{
	 	
	 }  
		 $sql1="INSERT INTO `hojadevida`(`hoj_fechaingreso`,`hoj_nombre`, `hoj_apellido`, `hoj_fechanacimiento`, `hoj_cedula`,`hoj_celular`, `hoj_sede`, `hoj_cargo`,`hoj_estado`,`hoj_pep`,`hoj_pas`,`hoj_cuen`,`hoj_banco`,`hoj_tcuenta`,`hoj_foto`) 
		VALUES ('$param1','$param2','$param3','$param4','$param5','$param6','$param7','$param35','Activo','$param36','$param37','$param38','$param39','$param40','$imagen')";
		$vinculo=$DB->Executeid($sql1);

	

		// if($_FILES["param101"]!=''){
		
		// 	$QL->addDocumento1($_FILES["param101"], 1, "hojadevida", $vinculo, "hojadevida", $DB);// foto
		// }
		if($_FILES["param102"]!=''){
		$QL->addDocumento1($_FILES["param102"], 2, "hojadevida", $vinculo, "hojadevida", $DB);//hoja de vida
		}
		if($_FILES["param103"]!=''){
		$QL->addDocumento1($_FILES["param103"], 3, "hojadevida", $vinculo, "hojadevida", $DB); //celular
		}
		
		if($_FILES["param109"]!=''){
		$QL->addDocumento1($_FILES["param109"], 15, "hojadevida", $vinculo, "hojadevida", $DB); //pep
		}

		if($_FILES["param110"]!=''){
		$QL->addDocumento1($_FILES["param110"], 22, "hojadevida", $vinculo, "hojadevida", $DB); //pep
		}

		

		 $caso='datosvehiculo';	
		 $idhojadevida=$vinculo;
		 sincronizarPendientesFijosHojaVida((string) $param5, 'crear_hoja_vida');


}else{
	
@$idhojadevida=$_REQUEST["idhojadevida"];

switch ($condecion)
{
      
		case "datospersonales":

		//   if (is_uploaded_file($_FILES['param101']['tmp_name'])){
		

		// 	move_uploaded_file($_FILES["param101"]["tmp_name"],"./imgUsuarios/".$_FILES["param101"]["name"]);

		// $imagen = md5(date("Y-m-d-H-i-s").rand(0,9999).$_SESSION['usuario_id']).".jpg";
		// 	// echo$imagen = $_FILES["param101"]["name"]; 

	$sql="SELECT `hoj_foto`,hoj_cerCuen,hoj_firma   FROM `hojadevida` where idhojadevida='$idhojadevida'";		
	$DB1->Execute($sql);
	$rw1=mysqli_fetch_row($DB1->Consulta_ID);
	
          if (is_uploaded_file($_FILES['param101']['tmp_name'])){
			
		$imagen = md5(date("Y-m-d-H-i-s").rand(0,9999).$_SESSION['usuario_id']).".jpg";
  
		move_uploaded_file($_FILES['param101']['tmp_name'], "./imgUsuarios/".$imagen);
	 }else{
	 	$imagen=$rw1[0];
	 } 

	 if (is_uploaded_file($_FILES['param115']['tmp_name'])){
		
		$imagen1 = date("Y-m-d-H-i-s").$_FILES["param115"]["name"];
  
		move_uploaded_file($_FILES['param115']['tmp_name'], "./imgHojasDeVida/".$imagen1);
	 }else{
		  $imagen1=$rw1[1];
	 } 

	 if (is_uploaded_file($_FILES['param116']['tmp_name'])){
		
		$imagen2 = date("Y-m-d-H-i-s").$_FILES["param116"]["name"];
  
		move_uploaded_file($_FILES['param116']['tmp_name'], "./imgHojasDeVida/".$imagen2);
	 }else{
		  $imagen2=$rw1[2];
	 } 
	
		$sql1="UPDATE hojadevida set `hoj_fechaingreso`='$param1',`hoj_nombre`='$param2', `hoj_apellido`='$param3', `hoj_fechanacimiento`='$param4', `hoj_cedula`='$param5',`hoj_celular`='$param6', `hoj_sede`='$param7', `hoj_cargo`='$param35', `hoj_pep`='$param36', `hoj_pas`='$param37', `hoj_cuen`='$param38', `hoj_banco`='$param39', `hoj_tcuenta`='$param40', `hoj_foto`='$imagen',hoj_cerCuen='$imagen1',hoj_firma='$imagen2',hoj_confibanco='$param81',hoj_confiNumCuenta='$param80', `hoj_confiCedula`='$param82', `hoj_conTipoCuenta`='$param83'   where idhojadevida='$idhojadevida' ";
		$DB1->Execute($sql1);
		sincronizarPendientesFijosHojaVida((string) $param5, 'actualizar_datos_personales');

		// if($_FILES["param101"]!=''){
	
		// 	$QL->addDocumento1($_FILES["param101"], 1, "hojadevida", $idhojadevida, "hojadevida", $DB);// foto
		
		// }
		if($_FILES["param102"]!=''){
		$QL->addDocumento1($_FILES["param102"], 2, "hojadevida", $idhojadevida, "hojadevida", $DB);//hoja de vida
		}
		if($_FILES["param103"]!=''){
		$QL->addDocumento1($_FILES["param103"], 3, "hojadevida", $idhojadevida, "hojadevida", $DB); //celular
		}
		if($_FILES["param108"]!=''){
			$QL->addDocumento1($_FILES["param108"], 8, "hojadevida", $idhojadevida, "hojadevida", $DB); //libreta militar
		}
		if($_FILES["param109"]!=''){
			$QL->addDocumento1($_FILES["param109"], 15, "hojadevida", $idhojadevida, "hojadevida", $DB); //pep
		}

		if($_FILES["param110"]!=''){
			$QL->addDocumento1($_FILES["param110"], 22, "hojadevida", $idhojadevida, "hojadevida", $DB); //pep
		}

		 $caso='datoscontrato';	
	break;
	case "datoscontrato":
	
			 $sql1="UPDATE hojadevida set `hoj_fechacontrato`='$param40',`hoj_tipocontrato`='$param39',`hoj_area`='$param41',`hoj_turnos`='$param43',`hoj_retegarantia`='$param45',`hoj_valorRetegarantia`='$param46',hoj_fech_año_act='$param100',hoj_tipo_pago='$param112' where idhojadevida='$idhojadevida' ";
			$DB1->Execute($sql1);
			$sqlCedulaPendientes="SELECT hoj_cedula FROM hojadevida WHERE idhojadevida='$idhojadevida' LIMIT 1";
			$DB1->Execute($sqlCedulaPendientes);
			$rwCedulaPendientes=mysqli_fetch_row($DB1->Consulta_ID);
			sincronizarPendientesFijosHojaVida((string) ($rwCedulaPendientes[0] ?? ''), 'actualizar_datos_contrato');
		
		
		if($_FILES["param109"]!=''){
	
			$QL->addDocumento1($_FILES["param109"], 9, "hojadevida", $idhojadevida, "hojadevida", $DB);// tipocontrato
		
		}
		if($_FILES["param105"]!=''){
		$QL->addDocumento1($_FILES["param105"], 5, "hojadevida", $idhojadevida, "hojadevida", $DB);//pagare
		}
		if($_FILES["param110"]!=''){
		$QL->addDocumento1($_FILES["param110"], 10, "hojadevida", $idhojadevida, "hojadevida", $DB); //funciones
		}
		if($_FILES["param111"]!=''){
			$QL->addDocumento1($_FILES["param111"], 10, "hojadevida", $idhojadevida, "hojadevida", $DB); //funciones
			}
		 $caso='datosvehiculo';	
	break;

		case "datosvehiculo":

			$sql1="UPDATE hojadevida set `hoj_licencia`='$param10', `hoj_tipolicencia`='$param9' where idhojadevida='$idhojadevida' ";
			$DB->Execute($sql1);

			if($_FILES["param104"]!=''){
			$QL->addDocumento1($_FILES["param104"], 4, "hojadevida", $idhojadevida, "hojadevida", $DB); //licencia
			}
			/* if($_FILES["param105"]!=''){
			$QL->addDocumento1($_FILES["param105"], 5, "hojadevida", $idhojadevida, "hojadevida", $DB); //pagare
			} */
			if($_FILES["param106"]!=''){
			$QL->addDocumento1($_FILES["param106"], 6, "hojadevida", $idhojadevida, "hojadevida", $DB); //estado del vehiculo
			}
			if($_FILES["param107"]!=''){
			$QL->addDocumento1($_FILES["param107"], 7, "hojadevida", $idhojadevida, "hojadevida", $DB); //documentos del vehiculo
			}
			$caso='datosvivienda';	
		
	
	break;
	case "datosvivienda":
		$sql1="UPDATE hojadevida set  `hoj_tipovivienda`='$param10', `hoj_arrendador`='$param11', `hoj_direccion`='$param12',`hoj_telefono`='$param13' where idhojadevida='$idhojadevida' ";
		$DB->Execute($sql1);

		if($_FILES["param113"]!=''){

			$QL->addDocumento1($_FILES["param113"], 13, "hojadevida", $idhojadevida, "hojadevida", $DB);// recibo

		}
		if($_FILES["param114"]!=''){

			$QL->addDocumento1($_FILES["param114"], 14, "hojadevida", $idhojadevida, "hojadevida", $DB);// arriendo

		}
		$caso='datosconyuge';	
	break;
	case "datosconyuge":
		$sql1="UPDATE hojadevida set  `hoj_conyuge`='$param14', `hoj_profesion`='$param15',`hoj_celularconyuge`='$param16'  where idhojadevida='$idhojadevida' ";
		$DB->Execute($sql1);
		$caso='datosfamiliares';	
	break;
/* 	case "datosfamiliares":
		$sql1="UPDATE hojadevida set `hoj_namepadre`='$param17', `hoj_ocupacionp`='$param18', `hoj_telp`='$param19', `hoj_namemadre`='$param20', `hoj_ocupacionm`='$param21', `hoj_telm`='$param22'  where idhojadevida='$idhojadevida' ";
		$DB->Execute($sql1);
		$caso='datosestudios';	
	break; */
	case "datosfamiliares":

		
		if($param8==2){
			$sql2="INSERT INTO `referenciasfamiliares`(`ref_nombre`, `ref_parentesco`,`ref_ocupacion`, `ref_telefono`, `ref_idhojavida`, `ref_usuregistra`, `ref_fecharegistra`)
			VALUES ('$param1','$param2','$param3','$param4','$param7','$id_nombre','$fechatiempo')";
			$vinculo=$DB->Executeid($sql2);
			if($_FILES["param109"]!=''){

				$QL->addDocumento1($_FILES["param109"], 1, "referenciasfamiliares", $vinculo, "referenciasfamiliares", $DB);// referencias
		
			}
			$caso='datosfamiliares';
			

		}else{
			$caso='datosestudios';
		}
			
	break;
	case "datosestudios":
		/* $sql1="UPDATE hojadevida set  `hoj_tipoestudio`='$param23', `hoj_institucion`='$param24', `hoj_ciudades`='$param25', `hoj_fechagrado`='$param26'  where idhojadevida='$idhojadevida' ";
		$DB->Execute($sql1);
 */
if($param8==2){
		if($param1=='Otros Cursos'){
			$param1=$param9;
		}
		$sql2="INSERT INTO `referenciasestudio`(`ref_grado`, `ref_institucion`, `ref_ciudad`, `ref_fehcainicio`, `ref_fechaterminacion`, `ref_userregistra`, `ref_fechaingreso`, `ref_idhojavida`) VALUES ('$param1','$param2','$param3','$param4','$param5','$id_nombre','$fechatiempo','$param7')";
		$vinculo=$DB->Executeid($sql2);
		if($_FILES["param110"]!=''){

			$QL->addDocumento1($_FILES["param110"], 1, "referenciasestudio", $vinculo, "referenciasestudio", $DB);// referencias

		}
		$caso='datosestudios';
			
		}else{
			$caso='datossalud';	
		}

	break;
	case "datossalud":
	/* 	$sql1="UPDATE hojadevida set `hoj_eps`='$param27' , `hoj_fechaeps`='$param28' ,  `hoj_arl`='$param29', `hoj_fechaafi`='$param30' , `hoj_pension`='$param31', `hoj_fechapen`='$param32', `hoj_cajacompensacion`='$param37', `hoj_fechacaja`='$param38'   where idhojadevida='$idhojadevida' ";
		$DB->Execute($sql1); */
		if($param8==2){
			$sql2="INSERT INTO `seguridadsocial`(`seg_nombre`,`seg_entidad`,`seg_fechaentrega`, `seg_tipodocumento`,  `seg_idhojavida`,`seg_useringresa`,`seg_fechaingreso`) 
			VALUES ('$param2','$param3','$param4','$param5','$param7','$id_nombre','$fechatiempo')";
			$vinculo=$DB->Executeid($sql2);

			if($_FILES["param112"]!=''){

				$QL->addDocumento1($_FILES["param112"], 1, "seguridadsocial", $vinculo, "seguridadsocial", $DB);// seguridadsocial
		
			}
			$caso='datossalud';	
		}else{

			$caso='saludafiliaciones';	
		}


		
	break;
	case "saludafiliaciones":
		//SELECT `idrefenciassalud`, `ref_nombre`, `ref_parentesco`, `ref_vinculadoa`,`ref_ocupacion`, `ref_telefono`,  `ref_fechavinculacion`, `ref_idhojavida`, `ref_usuregistra`, `ref_fecharegistra` FROM `referenciassalud`
	if($param8==2){
		$sql2="INSERT INTO `referenciassalud`(`ref_nombre`, `ref_parentesco`,`ref_vinculadoa`,`ref_ocupacion`, `ref_telefono`,`ref_fechavinculacion`, `ref_idhojavida`, `ref_usuregistra`, `ref_fecharegistra`,`ref_tipodocumento`)
		VALUES ('$param1','$param2','$param3','$param4','$param5','$param6','$param7','$id_nombre','$fechatiempo','$param9')";
		$vinculo=$DB->Executeid($sql2);
		if($_FILES["param110"]!=''){

			$QL->addDocumento1($_FILES["param110"], 1, "referenciassalud", $vinculo, "referenciassalud", $DB);// referencias
	
		}
		$caso='saludafiliaciones';
			
		}else{
			$caso='datoslaborales';	
		}
			
	break;	
	case "datoslaborales":

	if($param8==2){
		$sql2="INSERT INTO `referenciaslaborales`(`ref_empresa`,`ref_telefono`,  `ref_fehcainicio`, `ref_fechaterminacion`, `ref_idhojavida` ,`ref_userregistra`, `ref_fechaingreso`,`ref_validado`,`ref_referenciado`,`ref_fechavalidacion`) 
		VALUES ('$param1','$param2','$param3','$param4','$param7','$id_nombre','$fechatiempo','$param9','$param10','$param11')";
		$vinculo=$DB->Executeid($sql2);
		if($_FILES["param111"]!=''){

			$QL->addDocumento1($_FILES["param111"], 1, "referenciaslaborales", $vinculo, "referenciaslaborales", $DB);// referenciaslaborales
	
		}
		$caso='datoslaborales';
		
			
		}else{
			
			$caso='examenesmedicos';	
		}


	break;
	case "examenesmedicos":

	if($param8==2){
		$sql2="INSERT INTO `examenesmedicos`(`exa_nombre`,`exa_serie`, `exa_idhojavida`,`exa_fechaentrega`, `exa_useringresa`,`exa_fechaingreso`) 
		VALUES ('$param1','$param2','$param7','$param3','$id_nombre','$fechatiempo')";
		$vinculo=$DB->Executeid($sql2);

		if($_FILES["param112"]!=''){

			$QL->addDocumento1($_FILES["param112"], 1, "examenesmedicos", $vinculo, "examenesmedicos", $DB);// examenesmedicos
	
		}
	
		$caso='examenesmedicos';	
			
		}else{
			$caso='dotacion';	
			
			
		}


	break;
	case "dotacion":
		if($param8==2){
				$sql2="INSERT INTO `elementostrabajo`(`ele_nombre`,`ele_serie`, `ele_idhojavida`,`ele_fechaentrega`, `ele_useringresa`,`ele_fechaingreso`) 
				VALUES ('$param1','$param2','$param7','$param3','$id_nombre','$fechatiempo')";
				$vinculo=$DB->Executeid($sql2);
			
				if($_FILES["param112"]!=''){
		
					$QL->addDocumento1($_FILES["param112"], 1, "elementostrabajo", $vinculo, "referenciaslaborales", $DB);// referenciaslaborales
			
				}
				
				$caso='dotacion';
					
					
				}else{
					
					$caso='Incapacidades';	
				}
		
		
			break;
			case "Incapacidades":
				if($param8==2){
					$sql2="INSERT INTO `incapacidades`(`ref_fehcainicio`, `ref_fechaterminacion`, `ref_dias`, `ref_tipodeincapacidad`, `ref_userregistra`, `ref_fechaingreso`, `ref_idhojavida`)
					VALUES ('$param1','$param2','$param3','$param4','$id_nombre','$fechatiempo','$param7')";
					$vinculo=$DB->Executeid($sql2);
				
					if($_FILES["param5"]!=''){
			
						$QL->addDocumento1($_FILES["param5"], 1, "incapacidades", $vinculo, "incapacidades", $DB);// incapacidades
				
					}
					
					$caso='Incapacidades';
						
						
					}else{
						
						$caso='memorandos';	
					}
			break;		
			case "RegistrarPago":
				$idincapacidades=$_REQUEST["idincapacidades"];
				$sql="UPDATE `incapacidades` SET `ref_fechapagoincapacidad`='$param1',`ref_valorpagado`='$param2',`ref_validadopago`='$id_nombre',`ref_fechavalidacion`='$date'  WHERE `idincapacidades`='$idincapacidades' ";
				$DB->Execute($sql);


				if($_FILES["param112"]!=''){

					$QL->addDocumento1($_FILES["param112"], 1, "RegistrarPago", $idincapacidades, "RegistrarPago", $DB);// examenesmedicos
			
				}

				$caso='Incapacidades';
			break;				
			case "memorandos":
				if($param8==2){
					$sql2="INSERT INTO `memorandos`(`mem_fecha`, `mem_tipomemorando`,  `mem_descripcion`, `mem_tipodocumento`,`mem_idhojavida`, `mem_userregistra`, `mem_fecharegistro`) 
					VALUES ('$param1','$param2','$param3','$param4','$param7','$id_nombre','$fechatiempo')";
					$vinculo=$DB->Executeid($sql2);
				
					if($_FILES["param110"]!=''){
			
						$QL->addDocumento1($_FILES["param110"], 1, "memorandos", $vinculo, "memorandos", $DB);// memorandos
				
					}
					
					$caso='memorandos';
						
						
					}else{
						
						$caso='terminacioncontrato';	
					}
			break;	
				case "terminacioncontrato":

					$sql="SELECT hoj_retiroEps, hoj_liquidacion,hoj_retiroarl  FROM `hojadevida` where idhojadevida='$idhojadevida'";		
					$DB1->Execute($sql);
					$rw1=mysqli_fetch_row($DB1->Consulta_ID);

					if (is_uploaded_file($_FILES['param12']['tmp_name'])){
		
						$imagen1 = date("Y-m-d-H-i-s").$_FILES["param12"]["name"];
				  
						move_uploaded_file($_FILES['param12']['tmp_name'], "./imgHojasDeVida/".$imagen1);
					 }else{
						  $imagen1=$rw1[0];
					 } 
				
					 if (is_uploaded_file($_FILES['param13']['tmp_name'])){
						
						$imagen2 = date("Y-m-d-H-i-s").$_FILES["param13"]["name"];
				  
						move_uploaded_file($_FILES['param13']['tmp_name'], "./imgHojasDeVida/".$imagen2);
					 }else{
						  $imagen2=$rw1[1];
					 } 

					 if (is_uploaded_file($_FILES['param14']['tmp_name'])){
						
						$imagen3 = date("Y-m-d-H-i-s").$_FILES["param14"]["name"];
				  
						move_uploaded_file($_FILES['param14']['tmp_name'], "./imgHojasDeVida/".$imagen3);
					 }else{
						  $imagen3=$rw1[2];
					 } 
					
					

			$sql1="UPDATE hojadevida set `hoj_fechatermino`='$param1',`hoj_entregapuesto`='$param2',`hoj_pazysalvo`='$param3', `hoj_estado`='$param4', hoj_retiroEps='$imagen1', hoj_liquidacion='$imagen2', hoj_retiroarl='$imagen3'  where idhojadevida='$idhojadevida' ";
			$DB->Execute($sql1);

				if($_FILES["param111"]!=''){
					$QL->addDocumento1($_FILES["param111"], 11, "hojadevida", $idhojadevida, "hojadevida", $DB);// Foto Paz y Salvo
				}

                if($_FILES["param113"]!=''){
				$QL->addDocumento1($_FILES["param113"], 23, "hojadevida", $idhojadevida, "hojadevida", $DB); //pep
		}


					$caso='final';	
					
			
			break;
	default:

	break;
	}


}


if($caso=='final'){
	header ("Location:hojadevida.php");
}else{

	header ("Location:new_hojadevida.php?bandera=1&condecion=$caso&idhojadevida=$idhojadevida");
}



?>
