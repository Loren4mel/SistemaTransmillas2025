<!DOCTYPE html>
<html>

<head>
<script>


function enviar_formulario(id){

	if(param1.value=='' || param2.value=='' || param2.value=='0' ){
		alert('Ingrese la fecha y Vehiculo');
	}else{
	
	//	destino="detalle_entregavehiculo.php?condecion=entregadevehiculo"+"&idhojadevida="+id+"&param1="+param1.value+"&param2="+param2.value;
		//MostrarConsulta4(destino, "entregavehiculos");

		datos = {"condecion":"entregadevehiculo","idhojadevida":id,"param1":param1.value,"param2":param2.value};
		$.ajax({
				url: "detalle_entregavehiculo.php",
				type: "POST",
				data: datos,
				async: false,
				success: function(result) {
					//$('#entregavehiculos').load();	
					document.getElementById("entregavehiculos").innerHTML +=result;
				}
			}); 
			
	}


	
}
</script>
</head>
<body>

 <?php 

if (!function_exists('link_entrega_vehiculo')) {
	function link_entrega_vehiculo($ruta) {
		$ruta = trim((string)$ruta);
		if ($ruta == '') {
			return '';
		}
		if (preg_match('/^https?:\/\//i', $ruta) || strpos($ruta, 'nueva_plataforma/') === 0) {
			return $ruta;
		}
		return 'nueva_plataforma/'.$ruta;
	}
}

if (!function_exists('img_entrega_vehiculo')) {
	function img_entrega_vehiculo($ruta, $texto = 'Ver') {
		$url = link_entrega_vehiculo($ruta);
		if ($url == '') {
			return "<span class='text-muted'>Sin archivo</span>";
		}
		return "<a href='".htmlspecialchars($url, ENT_QUOTES, 'UTF-8')."' target='_blank'>".$texto."</a>";
	}
}

if (!function_exists('equipo_entrega_vehiculo')) {
	function equipo_entrega_vehiculo($json) {
		$equipo = json_decode((string)$json, true);
		if (!is_array($equipo) || count($equipo) == 0) {
			return "<span class='text-muted'>Sin equipo</span>";
		}

		$html = "<ul style='padding-left:16px;margin:0;text-align:left'>";
		foreach ($equipo as $herramienta) {
			$nombre = htmlspecialchars($herramienta['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
			$existe = (($herramienta['existe'] ?? '') == 'si') ? 'Si' : 'No';
			$foto = !empty($herramienta['foto']) ? ' '.img_entrega_vehiculo($herramienta['foto'], 'Foto') : '';
			$html .= "<li>".$nombre." (".$existe.")".$foto."</li>";
		}
		$html .= "</ul>";

		return $html;
	}
}

 $fechaactual=date("Y-m-d");
 $nivel_acceso=$_SESSION['usuario_rol'];
 $id_sedes=$_SESSION['usu_idsede'];

 if($nivel_acceso==1){
	if($param35!=''){   $conde2=""; }  

}
else {	
	$param35=$id_sedes;
	  $conde2.=" and idsedes='$id_sedes' "; 	
	
}

echo "</tr>";
$FB->titulo_azul1("Registrar Entrega de Vehiculo",9,0,7);  
echo "</tr>";


$FB->llena_texto("Fecha de Entrega de Vehiculo:", 1, 10, $DB, "", "", "", 1, 0);
$FB->llena_texto("Vehiculo:",2,2,$DB,"(SELECT concat_ws(' ',`veh_tipo`,' ',`veh_placa`,' ',`veh_marca`,' ',`veh_modelo`) as id_vehiculo, concat_ws(' ',`veh_tipo`,' ',`veh_placa`,' ',`veh_marca`,' ',`veh_modelo`) as vehiculo FROM vehiculos where veh_estado=1)", "", "", 4, 0);

echo '<input type="hidden" name="param7" id="param7" value="'.$idhojadevida.'">';
echo '<input type="hidden" name="param8" id="param8" value="1">';

//echo "<tr><td><button type='submit' class='btn btn-success' formaction='newhojadevidaok.php?condecion=datosfamiliares2&idhojadevida=$idhojadevida'>Gurdar</button></td></tr>";
echo "<td><button type='button' class='btn btn-success' onclick='enviar_formulario(".$idhojadevida.")' >Agregar</button></td></tr>";

echo "<tr><td colspan='13'><div id='vehiculos'>";

echo '<table  id="entregavehiculos" class="table table-hover"><tr bgcolor="#074F91" class="tittle3">';
$FB->titulo_azul1("Fecha Entrega",1,0,0); 
$FB->titulo_azul1("Tipo",1,0,0); 
$FB->titulo_azul1("Vehiculo",1,0,0);  
$FB->titulo_azul1("Conductor",1,0,0);  
$FB->titulo_azul1("Usuario Registro",1,0,0);  
$FB->titulo_azul1("Sede",1,0,0); 
$FB->titulo_azul1("Fecha Registro",1,0,0); 
$FB->titulo_azul1("Foto Frente",1,0,0); 
$FB->titulo_azul1("Foto Respaldo",1,0,0); 
$FB->titulo_azul1("Firma",1,0,0); 
$FB->titulo_azul1("Equipo",1,0,0); 
$FB->titulo_azul1("Observaciones",1,0,0); 
 
if($nivel_acceso==1 or $nivel_acceso==12){
	$FB->titulo_azul1("Eliminar",1,0,0); 
}

$cedula_hojadevida = isset($rw[6]) ? $rw[6] : '';

$sql="SELECT e.`identregavehiculo`, e.`ent_fechaentrega`, e.`ent_vehiculo`, e.`ent_userregistra`,
	e.`ent_idhojadevida`, e.`ent_fecharegistra`, e.`ent_idusuario`, e.`ent_tipoentrega`, e.`ent_sede`,
	e.`ent_img_frente`, e.`ent_img_trasera`, e.`ent_equipo_carretera`, e.`ent_observaciones`,
	e.`ent_firma`, u.`usu_nombre` AS conductor_nombre
	FROM `entregavehiculo` e
	LEFT JOIN `usuarios` u ON e.`ent_idusuario`=u.`idusuarios`
	WHERE (
		e.`ent_idhojadevida`=$idhojadevida
		OR u.`usu_identificacion`='$cedula_hojadevida'
		OR EXISTS (
			SELECT 1
			FROM `usuarios` ux
			INNER JOIN `vehiculos` vx ON ux.`usu_vehiculo`=vx.`idvehiculos`
			WHERE ux.`usu_identificacion`='$cedula_hojadevida'
			AND e.`ent_vehiculo` LIKE CONCAT('%', vx.`veh_placa`, '%')
		)
	)
	ORDER BY e.`ent_fechaentrega` DESC, e.`identregavehiculo` DESC";

$DB->Execute($sql); 
$va=0; 

	while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	{
				$id_p=$rw1[0];
				$tipoentrega = $rw1[7] != '' ? ucfirst($rw1[7]) : '---';
				$conductor = $rw1[14] != '' ? $rw1[14] : '---';
				$sede = $rw1[8] != '' ? $rw1[8] : '---';
				$observaciones = $rw1[12] != '' ? nl2br(htmlspecialchars($rw1[12], ENT_QUOTES, 'UTF-8')) : '---';
				$va++; $p=$va%2;
				if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
				echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
				echo "<td>".$rw1[1]."</td>
				<td>".$tipoentrega."</td>
				<td>".$rw1[2]."</td>
				<td>".$conductor."</td>
				<td>".$rw1[3]."</td>		
				<td>".$sede."</td>
				<td>".$rw1[5]."</td>
				<td>".img_entrega_vehiculo($rw1[9], 'Ver')."</td>
				<td>".img_entrega_vehiculo($rw1[10], 'Ver')."</td>
				<td>".img_entrega_vehiculo($rw1[13], 'Ver')."</td>
				<td>".equipo_entrega_vehiculo($rw1[11])."</td>
				<td style='max-width:220px;text-align:left'>".$observaciones."</td>";		

				//echo $LT->llenadocs3($DB1, "entregavehiculo",$id_p, 1, 35, 'Ver');
			if($nivel_acceso==1 or $nivel_acceso==12){
				$DB->edites($id_p, "entregavehiculo", 2,"$idhojadevida");
			}else{
				//$DB->edites($id_p, "entregavehiculo", 2,"$idhojadevida");

			}
	}
	

	echo "</table><table class='table table-hover'>";
	$FB->titulo_azul1(" ------ ",1,0,10); 
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
	if($nivel_acceso==1 or $nivel_acceso==12){
		$FB->titulo_azul1(" ------",1,0,0); 
	}

	echo "</div></td></tr>";

?> 
</body>
</html>
