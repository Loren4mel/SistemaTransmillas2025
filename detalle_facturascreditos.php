<?php 
require("login_autentica.php");
include("cabezote3.php"); 

$asc="ASC";
$conde1=""; 
$conde3=""; 
$conde2=""; 
$conde5="";

if($param4!=''){  $fechainicio=$param4;}
if($param5!=''){  $fechaactual=$param5;}

if($param2!=''){ $conde2 ="  fac_numeroref like '%$param2%'";  } else { 
	$conde2 =" date(fac_fechafactura)>='$fechainicio' and  date(fac_fechafactura)<='$fechaactual'";
	if($param3!=''){ $conde3 =" and fac_credito like '%$param3%'";  }
}

if($param1=='Pre-Facturado' or $param1=='Facturado'){
	$conde1=" and fac_estado='$param1'";
}elseif($param1=='Sin Radicar'){
	$conde1=" and fac_fecharadicado='0000-00-00'";
}elseif($param1=='Radicado'){
	$conde1=" and fac_fecharadicado>='1990-01-01'";
}
elseif($param1=='Pagadas'){
	$conde1=" and (fac_tipopago is not null and fac_tipopago!='Pendiente')";
}elseif($param1=='Sin pagar'){
	$conde1=" and  (fac_tipopago='Pendiente' or fac_tipopago is  null)";
}elseif($param1=='Anulado'){
	$conde1=" and  fac_tipopago='Anulado' ";
}elseif($param1=='Pago Incompleto'){
	$conde1=" and  fac_valorpendiente>'0' ";
}elseif($param1=='Excedentes'){
	$conde1=" and  (fac_valorpendiente<'0' and fac_valorpendiente!='')";
}elseif($param1=='Completas'){
	$conde1=" and  fac_valorpendiente='0' and  fac_pagoconfir>0 ";
}
else{
	$conde1='';	
}

if($param9!=''){  $conde4=" and  fac_nit='$param9' ";}
if($param10!=''){  $conde5=" and  fac_numerofactura='$param10' ";}


$sql="SELECT `idfacturascreditos`, `fac_fechafactura`,`fac_credito`, `fac_numerofactura`, `fac_fechaprefac`,`fac_idservicios`, `fac_iduserpre`,`fac_numeroref`, `fac_fechafacturado`, `fac_fechavencimiento`, `fac_estado`,`fac_tipopago`,`fac_iduserfac`,fac_precio,`fac_fecharadicado`,fac_fechapago,fac_notacredito,fac_fecharafacturado,fac_pagoconfir,fac_userconfirmo,fac_fechacomfir,fac_valorpendiente,fac_preciofinal,fac_correoven, fac_nit,fac_correofac FROM `facturascreditos` WHERE   $conde2 $conde1 $conde3 $conde4 $conde5 ORDER BY fac_numeroref $asc ";
$html="";
$DB->Execute($sql); $va=0; 
$guias=0;
	while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	{
		$id_p=$rw1[0];
		$va++; $p=$va%2;
		if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
		$direc1=str_replace("&"," ", $rw1[4]);
		$direct2=str_replace("&"," ", $rw1[7]);
		if($rw1[17]=='' or $rw1[17]==null){ $rw1[17]='Sin Facturar'; }else{
			$guias=$guias+1;
		}
		//$rw1[5]
		$numero=$rw1[7];
		if($rw1[7]==''){
			$rw1[7]='Facturar';
		}else{
			$nufactura=$rw1[7];
			$rw1[7]='Factura #:'.$rw1[7];	
			
			$color='#6a1407';
		}
		if($rw1[11]=='Nota Credito'){
			$color='#F1EE82';
		}elseif($rw1[11]!='Pendiente' and $rw1[11]!=''){
			$color='#27F581';
		}
		$nompromotor='';
		if($rw1[12]!=''){
			$sql5="SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE `idusuarios`='$rw1[12]'";
			$DB1->Execute($sql5);
			$nompromotor=$DB1->recogedato(1);
		}
		if($rw1[23]>0){
			$colortd='#F1C40F';
			$texto="$rw1[23] correos enviados";
		}else{
			$colortd='';
			$texto="";
		}
		if($rw1[25]>0){
			$colorfac='#ec7063';
			$textofac="$rw1[25] correo enviado";
		}else{
			$colorfac='';
			$textofac="";
		}

		if($rw1[2]=='EXTERNOS' and $rw1[11]=='Pendiente'){
			
			$sql7="SELECT count(*) as total FROM `servicios` WHERE `idservicios`in ($rw1[5]) and ser_pendientecobrar=6";
			$DB1->Execute($sql7);
			$totalxcobrar=$DB1->recogedato(0);

			if($totalxcobrar>=1){
				$color='#0A3F7B';
			}

		} 

		$html.= "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";

		$html.=  "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"editarfecha\",\"$rw1[1]\")';  title='Factura' >$rw1[1]</td>";

		$archivo = 'pre_facturas/'.$rw1[3].'.xls';

		if (file_exists($archivo)) {
			$verArchivo="<a href='pre_facturas/$rw1[3].xls' target='_blank' >Ver</a>";
		} else {
			$verArchivo="";
		}
		$html.=  "
		<td>".$rw1[2]."</td>
		<td>".$rw1[24]."</td>
		<td><a id='link'  onclick='pop_dis16($id_p,\"doc_Prefactura\",\"$rw1[3]\")';  title='Envia Factura vencida' >$rw1[3]</a><br>$verArchivo</td>
		<td>".$rw1[4]."</td>
		<td colspan='1' width='0' align='center' ><a id='link' onclick='llena_datos(4, $nivel_acceso, \"$id_p\", \"ASC\");' title='Descargar' >Descargar<a> <br> </td>
		<td>".$rw1[6]."</td>";
		//<td>".$rw1[7]."</td>
		//echo "<td align='center' ><a  onclick='pop_dis5($id_p,\"facturarcreditos\")';  style='cursor: pointer;' title='Recogidas' >$rw1[7]</a></td>";
		if($rw1[2]=='EXTERNOS'){

			$html.=  "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16(\"$id_p\",\"cambiarfactura\",\"$nufactura\")';  title='cambiar Factura' >$rw1[7]
			<a id='link'  onclick='editarfactura(\"param36=$id_p&param32=$numero&metodo=Editar\")';  title='Facturar' >Editar</td>";

		}else{

			
				if($rw1[7]=='Facturar'){
					$html.=  "<td colspan='1' width='0' align='center' ><a id='link'  onclick='window.open(\"crearfacturacredito.php?param6=$id_p&param3=$rw1[2]&metodo=Crear\",\"_self\")';  title='Facturar' >$rw1[7]</td>";
				}else{			
					$html.=  "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16(\"$id_p\",\"cambiarfactura\",\"$nufactura\")';  title='cambiar Factura' >$rw1[7]
					<a id='link'  onclick='window.open(\"crearfacturacredito.php?param6=$id_p&param3=$rw1[2]&param2=$numero&metodo=Editar\",\"_self\")';  title='Facturar' >Editar</td>";

				}

		}
	if($rw1[17]=='0000-00-00'){
		$html.=  "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"subirFactura\",\"$rw1[3]\")';  title='Factura' >Subir Factura</td>";
	}else{
		$radicado="Facturado:".$rw1[17];
		$imagen=  $LT->llenadocs31($DB1,"facturascreditos",$id_p, 3, 15,"$radicado");
		$html.=  "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"subirFactura\",\"$rw1[3]\")';  title='Factura' >Actualizar Factura $imagen</td>";

	}
	$html.=  "<td>".$rw1[8]."</td>
		<td bgcolor='$colortd'><a id='link'  onclick='pop_dis16($id_p,\"Enviar correo factura vencida\",\"$rw1[2]\")';  title='Envia Factura vencida' >".$rw1[9]."<br> $texto</a></td>
		<td bgcolor='$colorfac'><a id='link'  onclick='pop_dis16($id_p,\"Enviar correo factura\",\"$rw1[2]\")';  title='Enviar Factura' >Enviar Correo <br> $textofac</a></td>
		<td>".$rw1[10]."</td>
		<td>".$rw1[13]."</td>
		<td>".$rw1[22]."</td>
		";

		$valor1=$valor1+$rw1[13];
		$valor2=$valor2+$rw1[22];

		if($rw1[14]=='0000-00-00'){
			$html.= "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"fecharadicado\",\"$rw1[3]\")';  title='fecharadicado' >Sin Radicar</td>";

		}else{
			$radicado="Radicado:".$rw1[14];
			$imagenradicado= $LT->llenadocs31($DB1,"facturascreditos",$id_p, 1, 15,"$radicado");
			$html.= "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"fecharadicado\",\"$rw1[3]\")';  title='fecharadicado' >Actualizar Radicado $imagenradicado</td>";

		} 

		if($rw1[11]=='Pendiente'){

			$rw1[5];
			$pagas=0;
			$nopagas=0;
			if($rw1[2]=='EXTERNOS' and $color=='#6a1407' ){

			// $pagadas="";
			if ($rw1[5]!="") {
				# code...
			
			// echo$servi="SELECT idservicios,ser_clasificacion  FROM `servicios` WHERE `idservicios`in ($rw1[5]) ";
			$servi="SELECT s.idservicios, s.ser_clasificacion, g.gui_userecomienda, g.gui_fechaentrega, g.gui_recogio, g.gui_fecharecogio
				 FROM servicios s
				 LEFT JOIN guias g ON s.idservicios = g.gui_idservicio
				 WHERE s.idservicios IN ($rw1[5])";
			$DB1->Execute($servi);
				while($sevi=mysqli_fetch_row($DB1->Consulta_ID))
				{
					$idservi=$sevi[0];

							if ($sevi[1]==1) {
									if ($sevi[4]!="") {
									$pagadas[] = array(
										'idguiab' => $sevi[0],
										'usuario' => $sevi[4],
										'fecha' => $sevi[5],
										'tipoPago' => $sevi[1],
										'Pagado' => $sevi[1]
									);
									$pagas=$pagas+1;
								}
								
							}else if($sevi[1]==3){
								if ($sevi[2]!="") {


								$pagas=$pagas+1;
									

									$pagadas[] = array(
										'idguiab' => $sevi[0],
										'usuario' => $sevi[2],
										'fecha' => $sevi[3],
										'tipoPago' => $sevi[1],
										'pagado' => $sevi[1]
									);
								}	
							}else{

								$nopagas=$nopagas+1;
							}
						// }
				 }

				// if ($nopagas<=0 and $pagas>=1) {
				// 	$td="#F1C40F";

				// }
				//  print_r($pagadas);
				$html.= "<td colspan='1' bgcolor='$td' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"TipoPago\",\"$rw1[3]\")';  title='Tipopago' >$rw1[11].$pagas.$nopagas</td>";
			}else{

				$html.= "<td colspan='1' bgcolor='$td' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"TipoPago\",\"$rw1[3]\")';  title='Tipopago' >$rw1[11]</td>";

			}













	
				// if($totalxcobrar>=1){
				// 	$color='#0A3F7B';
				// }
				

	
			}else {
				$html.= "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"TipoPago\",\"$rw1[3]\")';  title='Tipopago' >$rw1[11]</td>";

			}

		}elseif($rw1[11]!=null or $rw1[11]!=''){
			
			$pago=$rw1[11]." \n Ver Imagen";
			$imagenpago= $LT->llenadocs31($DB1,"facturascreditos",$id_p, 2, 15,"$pago");
			$html.= "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"TipoPago\",\"$rw1[3]\")';  title='Tipopago' >Actualizar Pago $imagenpago</td>";

			//echo "<td>".$rw1[11]."</td>";
		}else{
			$html.= "<td>".$rw1[11]."</td>";
		}
		$html.= "<td>".$rw1[16]."</td>";
		$html.= "<td>".$rw1[15]."</td>";
		$html.= "<td>".$nompromotor."</td>
		";

	if($rw1[21]==0){

		$colorexcedente='green';
	}elseif($rw1[21]>0){

		$colorexcedente='red';

	}else{
		$colorexcedente='blue';
	}	
		
	if($rw1[18]=='' and  $nivel_acceso==1){	
		$html.= "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"pagoconfirmado\",\"$rw1[3]\")';  title='pagoconfirmado' >Validar pago</td>";
	}elseif($rw1[18]!='' and  $nivel_acceso==1){
		// Variables de PHP
		$usuarioconfir = $rw1[19];
		$fechaconf = $rw1[20];
		$valorconf = $rw1[18];
		$excedente = $rw1[21];

		// Construir el array asociativo
		$pago = array(
		"usuario" => $usuarioconfir,
		"fecha" => $fechaconf,
		"valor" => $valorconf,
		"excedente" => $excedente
		);

		// Convertir el array a formato JSON
		$pagoJSON = json_encode($pago);
		$html.= "<td colspan='1' width='0' align='center'  style='background-color: $colorexcedente;' ><a id='link'  onclick='pop_dis59($pagoJSON,\"verpagoconfirmadogerente\",\"$id_p\")';  title='pagoconfirmado' >Ver Validado</td>";
	}elseif($rw1[18]!=''){
		// Variables de PHP
		$usuarioconfir = $rw1[19];
		$fechaconf = $rw1[20];
		$valorconf = $rw1[18];
		$excedente = $rw1[21];

		// Construir el array asociativo
		$pago = array(
		"usuario" => $usuarioconfir,
		"fecha" => $fechaconf,
		"valor" => $valorconf,
		"excedente" => $excedente
		);

		// Convertir el array a formato JSON
		$pagoJSON = json_encode($pago);
		$html.= "<td colspan='1' width='0' align='center'  style='background-color: $colorexcedente;' ><a id='link'  onclick='pop_dis59($pagoJSON,\"verpagoconfirmado\",0)';  title='pagoconfirmado' >Ver Validado</td>";
	}else{
		$html.= "<td colspan='1' width='0' align='center' style='background-color: $colorexcedente;'>Sin Validar pago</td>";

	}

	$html.= "<td colspan='1' width='0' align='center' ><a id='link'  onclick='pop_dis16($id_p,\"Editar Factura Externa\",\"$rw1[3]\")';  title='pagoconfirmado' >Editar</td>";

		if($nivel_acceso==1 or $rw1[7]=='Facturar'){

			$html.= "<td align='center' class='Intabla'><a href='del_admin.php?id_param=$id_p&tabla=facturascreditos&condecion=$nufactura' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";

		//	$DB->edites($id_p, "facturascreditos", 2, $nufactura);
		}else{
			$html.= "<td></td>";
		}
		$html.= "</tr>"; 
	}
	$html.= "<tr><td align='center' ><input name='guiasfacturadas' id='guiasfacturadas' type='hidden'  value='$guias'> Total Datos:$va</td>"; 
	
	$html.= "</tr>"; 

	$FB->titulo_azul1("Valor",1,0,7); 
	$FB->titulo_azul1("Valor Final",1,0,0); 

	$valor1=number_format($valor1, 2, ',', '.');
	$valor2=number_format($valor2, 2, ',', '.');

	echo "<tr><td align='center'><font size='4'>$valor1</font></td><td align='center'><font size='4'>$valor2</font></td></tr>";



	$FB->titulo_azul1("Fecha",1,0,7); 
	$FB->titulo_azul1("Credito",1,0,0); 
	$FB->titulo_azul1("Nit",1,0,0); 
	$FB->titulo_azul1("# Pre-Factura",1,0,0); 
	$FB->titulo_azul1("Fechas Pre-Factura",1,0,0); 
	$FB->titulo_azul1("Excel",1,0,0); 
	$FB->titulo_azul1("Usuario P",1,0,0); 
	$FB->titulo_azul1("No Factura",1,0,0); 
	$FB->titulo_azul1("Factura Aprobada",1,0,0); 
	$FB->titulo_azul1("Fecha Factura",1,0,0); 
	$FB->titulo_azul1("Fecha Vencimiento",1,0,0); 
	$FB->titulo_azul1("Enviar",1,0,0); 	
	$FB->titulo_azul1("Estado",1,0,0); 
	$FB->titulo_azul1("Valor",1,0,0); 
	$FB->titulo_azul1("Valor Final",1,0,0); 
	$FB->titulo_azul1("Fecha Radicado",1,0,0); 
	$FB->titulo_azul1("Tipo Pago",1,0,0); 
	$FB->titulo_azul1("Nota Credito",1,0,0); 
	$FB->titulo_azul1("Fecha Pago",1,0,0); 
	$FB->titulo_azul1("Usuario F",1,0,0); 
	$FB->titulo_azul1("Confirmar",1,0,0); 
	$FB->titulo_azul1("Editar externos",1,0,0); 
	$FB->titulo_azul1("Eliminar",1,0,0); 
echo $html;

$FB->titulo_azul1("Valor",1,0,7); 
$FB->titulo_azul1("Valor Final",1,0,0); 

echo "<tr><td align='center'><font size='4'>$valor1</font></td><td align='center'><font size='4'>$valor2</font></td></tr>";

include("footer.php");
?>
