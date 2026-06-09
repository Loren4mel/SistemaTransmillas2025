 <?php 
require("login_autentica.php");
include("declara.php");

@$accion=$_REQUEST["accion"];
@$condecion=$_REQUEST["condecion"];
@$condecion=$_REQUEST["condecion"];
$fechatiempo=date("Y-m-d H:i:s");
$fecha=date("Y-m-d");
@$idhojadevida=$_REQUEST["idhojadevida"];

if (!function_exists('link_entrega_vehiculo_detalle')) {
	function link_entrega_vehiculo_detalle($ruta) {
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

if (!function_exists('img_entrega_vehiculo_detalle')) {
	function img_entrega_vehiculo_detalle($ruta, $texto = 'Ver') {
		$url = link_entrega_vehiculo_detalle($ruta);
		if ($url == '') {
			return "<span class='text-muted'>Sin archivo</span>";
		}
		return "<a href='".htmlspecialchars($url, ENT_QUOTES, 'UTF-8')."' target='_blank'>".$texto."</a>";
	}
}

if (!function_exists('equipo_entrega_vehiculo_detalle')) {
	function equipo_entrega_vehiculo_detalle($json) {
		$equipo = json_decode((string)$json, true);
		if (!is_array($equipo) || count($equipo) == 0) {
			return "<span class='text-muted'>Sin equipo</span>";
		}

		$html = "<ul style='padding-left:16px;margin:0;text-align:left'>";
		foreach ($equipo as $herramienta) {
			$nombre = htmlspecialchars($herramienta['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
			$existe = (($herramienta['existe'] ?? '') == 'si') ? 'Si' : 'No';
			$foto = !empty($herramienta['foto']) ? ' '.img_entrega_vehiculo_detalle($herramienta['foto'], 'Foto') : '';
			$html .= "<li>".$nombre." (".$existe.")".$foto."</li>";
		}
		$html .= "</ul>";

		return $html;
	}
}

switch ($condecion)
{
	case "entregadevehiculo":
	
		$sql1="INSERT INTO `entregavehiculo`(`ent_fechaentrega`, `ent_vehiculo`, `ent_userregistra`, `ent_idhojadevida`, `ent_fecharegistra`) 
		VALUES('$param1','$param2','$id_nombre','$idhojadevida','$fecha')";
		 $vinculo=$DB->Executeid($sql1);

$sql="SELECT e.`identregavehiculo`, e.`ent_fechaentrega`, e.`ent_vehiculo`, e.`ent_userregistra`,
	e.`ent_idhojadevida`, e.`ent_fecharegistra`, e.`ent_idusuario`, e.`ent_tipoentrega`, e.`ent_sede`,
	e.`ent_img_frente`, e.`ent_img_trasera`, e.`ent_equipo_carretera`, e.`ent_observaciones`,
	e.`ent_firma`, u.`usu_nombre` AS conductor_nombre
	FROM `entregavehiculo` e
	LEFT JOIN `usuarios` u ON e.`ent_idusuario`=u.`idusuarios`
	WHERE e.`ent_idhojadevida`=$idhojadevida and e.`identregavehiculo`='$vinculo'";

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
			<td>".img_entrega_vehiculo_detalle($rw1[9], 'Ver')."</td>
			<td>".img_entrega_vehiculo_detalle($rw1[10], 'Ver')."</td>
			<td>".img_entrega_vehiculo_detalle($rw1[13], 'Ver')."</td>
			<td>".equipo_entrega_vehiculo_detalle($rw1[11])."</td>
			<td style='max-width:220px;text-align:left'>".$observaciones."</td>";		

			//echo $LT->llenadocs3($DB1, "entregavehiculo",$id_p, 1, 35, 'Ver');
		if($nivel_acceso==1 or $nivel_acceso==12){
			$DB->edites($id_p, "entregavehiculo", 2,"$idhojadevida");
		}
}


			
break;
	case "tecnopreventiva":

		if (is_uploaded_file($_FILES['param82']['tmp_name'])) {
			$imagen = md5(date("Y-m-d-H-i-s").rand(0,9999).$_SESSION['usuario_id']).".jpg";
			move_uploaded_file($_FILES['param82']['tmp_name'],"./imagenrevision/".$imagen);
		}

		 $sql1="INSERT INTO `revisionvehiculo`(`rev_fecha`, `rev_idvehiculo`, `rev_ingreso`, `rev_usuvehiculo`, `rev_usuregistra`,`rev_identifiuser`,`rev_foto`) 
		VALUES('$param80','$param81','$param12','$idhojadevida',' $param11',' $param13','$imagen')";

		$vinculo=$DB->Executeid($sql1);

$sql="SELECT `idrevisionvehiculo`,`rev_fecha`,`rev_idvehiculo`,`rev_usuregistra`,`rev_usuvehiculo`, `rev_ingreso`, `rev_foto`FROM `revisionvehiculo` WHERE  rev_usuvehiculo=$idhojadevida and idrevisionvehiculo='$vinculo'";

$DB->Execute($sql); 
$va=0; 

while($rw2=mysqli_fetch_row($DB->Consulta_ID))
{
			$id_p=$rw2[0];
			$va++; $p=$va%2;
			if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
			echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
			echo "<td>".$rw2[1]."</td>
			<td>".$rw2[2]."</td>
			<td>".$rw2[3]."</td>		
			<td>".$rw2[5]."</td>	
			<td><a href='imagenrevision/".$rw2[6]."' target='_blank'><img src='imagenrevision/".$rw2[6]."' width='20'>ver</a></td>";

		if($nivel_acceso==1 or $nivel_acceso==12){
			$DB->edites($id_p, "revisionvehiculo", 2,"$idhojadevida");
		}
}

	break;
}

?> 

