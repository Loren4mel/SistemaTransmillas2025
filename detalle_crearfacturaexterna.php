<?php 
require("login_autentica.php");
include("cabezote3.php"); 


?>
<style type="text/css">
#segundo {
	width:628px;
     height:415px;
     overflow:auto;
}

#tercero {
     width:628px;
     height:415px;
     overflow:auto;
}
</style>
<?php 
if($param3!=''){ $conde3 =" and (rel_nom_credito like '%$param3%')";  }

$idfactura=$param6;
$sql1="Select fac_idservicios from facturascreditos where idfacturascreditos=$idfactura ";
$DB->Execute($sql1); 
 $prefac=$DB->recogedato(0); 
$prefactura=explode(",",$prefac);
$metodo=$_REQUEST["metodo"];

if (!function_exists('guiaEstaPaga')) {
	function guiaEstaPaga($DB, $idservicio)
	{
		$sqlPago = "SELECT sd.ser_clasificacion, g.gui_recogio, g.gui_userecomienda, cp.cue_idoperentrega
		FROM serviciosdia sd
		LEFT JOIN guias g ON g.gui_idservicio = sd.idservicios
		LEFT JOIN cuentaspromotor cp ON cp.cue_idservicio = sd.idservicios
		WHERE sd.idservicios = '$idservicio'
		LIMIT 1";
		$DB->Execute($sqlPago);
		$rwPago = mysqli_fetch_row($DB->Consulta_ID);

		if (!$rwPago || $rwPago[3] == '') {
			return false;
		}

		if (($rwPago[0] == 0 || $rwPago[0] == 1) && $rwPago[1] != '') {
			return true;
		}

		if (($rwPago[0] == 2 || $rwPago[0] == 3) && $rwPago[2] != '') {
			return true;
		}

		return false;
	}
}

//echo 'jose'.$clave = array_search('111', $prefactura); 
if($metodo=='Editar') {
	echo "<tr><td colspan=2 align=left><button type='button' class='btn btn-success'  onclick='guardarfactura(1)' >Editar Factura</button></td></tr>";	
}else{
	echo "<tr><td colspan=2 align=left><button type='button' class='btn btn-success'  onclick='guardarfactura(2)' >Crear Factura</button></td></tr>";	
}

  echo '
  <div id="contenedor" style="display:flex;">
   <div id="segundo" style="width: 50%; float:left;" >';
   echo '<table class="table table-hover"><tr bgcolor="#E40826" class="tittle3"><td>Guias Pendientes X Cobrar</td></tr><tr><td>';
	   $FB->titulo_azul1("Fecha",1,0,7); 
	   $FB->titulo_azul1("Cliente",1,0,0); 
	   $FB->titulo_azul1("Guia",1,0,0); 
	   $FB->titulo_azul1("%Seguro",1,0,0); 
	   $FB->titulo_azul1("Flete",1,0,0); 
	   $FB->titulo_azul1("Manifiesto",1,0,0); 
	  $FB->titulo_azul1("Agregar",1,0,0); 

	  if($param3!=0 and $param3!=''){ $cond0=" and idclientes='$param3'"; } else { $cond0=""; }
	 $sql="SELECT `idservicios`,`cue_fecharecogida`,ser_guiare,ser_valorseguro,ser_valor,rel_nom_credito,
	 ser_numerofactura,cli_nombre,ser_manifiesto,ser_pendientecobrar
	FROM  `clientes` inner join clientesservicios on cli_idclientes=idclientes  
		inner join rel_sercli on idclientesdir=ser_idclientes 
		inner join servicios on  ser_idservicio=idservicios 
		inner join cuentaspromotor on cue_idservicio=idservicios 
		inner join rel_sercre on idservicio=idservicios
	 where  ser_pendientecobrar=1 and ser_estado!=100  and (ser_numerofactura IS NULL or ser_numerofactura='') $cond0
	 group by idservicios ORDER BY ser_fechaentrega desc ";

	$DB->Execute($sql); $va=0; 
	 while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	 {
		 $id_p=$rw1[0];
		 
		 $va++; $p=$va%2;
		 if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
		 $idguia=$rw1[2];
		 $pordeclarado=(intval($rw1[3])*1)/100;
		 $clave = array_search($id_p, $prefactura); 
		 $displaynone='';
		if($clave!=''){
			$displaynone='display:none';
		}
		 echo "<tr class='text' style='$displaynone' id='$rw1[2]' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
		 echo "
		 <td>".$rw1[1]."</td>
		 <td>".$rw1[7]."</td>";
		 echo "<td align='center' ><a  onclick='pop_dis5($id_p,\"Recogidas\")';  style='cursor: pointer;' title='Recogidas' >$rw1[2]</a></td>";

		 echo "
		 <td>".$pordeclarado."</td>
		 <td>".$rw1[4]."</td>
		 <td>".$rw1[8]."</td>
		 ";

		 echo "<td><button type='button' class='btn btn-success' onclick='agregarguia(\"$idguia\",\"$pordeclarado\",\"$rw1[4]\",\"$rw1[8]\",\"$id_p\",\"$rw1[9]\")'>Agregar</button></td></tr>";

	 } 

	echo '</table></td></tr></table></div>

   <div id="tercero" style="width: 50%; float:left;">';
   echo '<table class="table table-hover"><tr bgcolor="#04B404" class="tittle3"><td>Guias x Cobrar</td></tr><tr><td>';

  echo '<table  id="agregar" class="table table-hover"><tbody>
  <tr bgcolor="#074F91" class="tittle3">
  <td colspan="1" width="0" align="center">accion</td>
  <td colspan="1" width="0" align="center">Guia</td>
  <td colspan="1" width="0" align="center">%Seguro</td>
  <td colspan="1" width="0" align="center">Flete</td>
  <td colspan="1" width="0" align="center">Manifiesto <button onclick="copyColumnToClipboard(4)">📋</button></td>
  <td colspan="1" width="0" align="center">Eliminar</td>
  </tr></tbody>';


	$sql="SELECT `idservicios`,`cue_fecharecogida`,ser_guiare,ser_valorseguro,ser_valor,rel_nom_credito,ser_numerofactura,ser_manifiesto
	FROM  servicios 
	inner join cuentaspromotor on cue_idservicio=idservicios 
	inner join rel_sercre on idservicio=idservicios
	where   idservicios in ($prefac) 
	ORDER BY ser_fechaentrega desc";

	$valor2=0;
	$DB->Execute($sql); $va=0; 
	while($rw1=mysqli_fetch_row($DB->Consulta_ID))
	{
		$id_p=$rw1[0];
		
		$va++; $p=$va%2;
		if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
		$idguia=$rw1[2];
		$idguia2=$rw1[2].'2';
		$pordeclarado=(intval($rw1[3])*1)/100;
		$valor2=$rw1[4]+$pordeclarado+$valor2; 
		$guiaPaga = guiaEstaPaga($DB1, $id_p);
		$colorPago = $guiaPaga ? "#0FF165" : "#ff6b6b";
		$tituloPago = $guiaPaga ? "Guia paga" : "Guia sin pago";

		echo "<tr class='text' id='$idguia2' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
		echo "<td><button type='button' class='btn btn-danger' onclick='borrarguia(\"$idguia\",\"$pordeclarado\",\"$rw1[4]\",\"$id_p\")'>Quitar</button></td>";

		echo "<td align='center' style='background-color:$colorPago' title='$tituloPago'><a  onclick='pop_dis5($id_p,\"Recogidas\")';  style='cursor: pointer;' title='Recogidas' >$rw1[2]</a></td>";

		echo "
		<td>".$pordeclarado."</td>
		<td>".$rw1[4]."</td>
		<td>".$rw1[7]."</td>
		</tr>
		";
		
	//	echo "<td><button type='button' class='btn btn-danger' onclick='borrarguia(\"$idguia\",\"$pordeclarado\",\"$rw1[4]\",\"$id_p\")'>Quitar</button></td></tr>";
	
	} 

   echo '</table></td></tr></table></div>

</div>';
//$prefactura=json_encode($prefactura);
$FB->llena_texto("param37", 4, 13, $DB, "", "", $valor2, 5,2);
$FB->llena_texto("param38", 4, 13, $DB, "", "", $idfactura, 5,2);
$FB->llena_texto("param39", 4, 13, $DB, "", "", $prefac, 5,2);
include("footer.php");

?>
