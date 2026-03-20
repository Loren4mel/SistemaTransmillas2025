<?php
require("login_autentica.php"); 
include("layout.php");
$nivel_acceso=$_SESSION['usuario_rol'];
$id_usuario=$_SESSION['usuario_id'];
if(isset($_REQUEST["param1"])){ if($param1!=""){  $conde1="and (gas_idciudadori='$param1' or gas_idciudaddes='$param1') ";  $id_sedes=$param1; } } 
else {$param1="";  
	if($nivel_acceso==1){
		$conde1="";
		if($param1==0){ $id_sedes=0; }
		
	}else{
		$conde1="and (gas_idciudadori='$id_sedes' or gas_idciudaddes='$id_sedes') "; 

	}

}
$conde3='';
if(isset($_REQUEST["param2"])){  if($param2!=""){ $conde1.=" and asi_idpromotor='$param2'"; } } else {$param2="";  }
if($param4!=''){ $conde1.="and date(gas_fecharegistro)>='$param5' and date(gas_fecharegistro)<='$param4'";  $fechaactual=$param4;    $fechainicio=$param5;    } 
else { $conde1.=" and date(gas_fecharegistro)>='$fechainicio' and date(gas_fecharegistro)<='$fechaactual'";  }
$param8=isset($_REQUEST["param8"])?trim($_REQUEST["param8"]):"";
$bus_buscado=preg_replace("/[^A-Za-z0-9\\- ]/", "", $param8);

if(isset($_REQUEST["param3"]) and $_REQUEST["param3"]>0){ 

	if($param3=="1" or $param3==""){
		$conde3="and gas_usucom='' and gas_cantcom='' ";

	}elseif($param3=="2")
	{
		$conde3="and gas_usucom!='' and gas_cantcom!='' and gas_iduserrecoge<=0 ";
	}elseif($param3=="3")
	{
		$conde3="and gas_iduserrecoge>0  and gas_recogio<=0";

	}elseif($param3=="4")
	{
		$conde3="and gas_iduserrecoge>0  and gas_recogio=1";
	}elseif($param3=="5")
	{
		$conde3="and gas_usucom!='' and gas_iduserrecoge>0  and gas_recogio=2";
	}
	elseif($param3=="6")
	{
		$conde3="and gas_usucom!='' and gas_iduserrecoge>0  and gas_recogio=1 and gas_nomvalida!=''";
	}
	elseif($param3=="7")
	{
		$conde3=" ";
	}
	
}else{
	$conde3="and gas_usucom='' and gas_cantcom='' ";
}

$FB->titulo_azul1("Remesas Autorizados",9,0,7);  
$FB->abre_form("form1","","post");


$conde="";
$conde="gas_fecharegistro";

$FB->llena_texto("Fecha de inicio:", 5, 10, $DB, "", "", "$fechainicio", 1, 0);
$FB->llena_texto("Fecha fin:", 4, 10, $DB, "", "", "$fechaactual", 4, 0);

$FB->llena_texto("Sede:",1,2,$DB,"(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 )", "", "$id_sedes", 1, 0);
$FB->llena_texto("Estado:", 3, 82, $DB, $estado_remesa, "", "$param3", 4, 1);

$FB->llena_texto("", 3, 142, $DB, "BUSCAR", "","", 1, 0);
$FB->cierra_form(); 

echo "<form method='post' action='' style='margin:12px 0 6px 0;'>";
echo "<input type='hidden' name='param1' value='$param1'>";
echo "<input type='hidden' name='param2' value='$param2'>";
echo "<input type='hidden' name='param3' value='$param3'>";
echo "<input type='hidden' name='param4' value='$param4'>";
echo "<input type='hidden' name='param5' value='$param5'>";
echo "<table width='100%' class='text'><tr bgcolor='#F3F6FB'>";
echo "<td style='padding:8px; width:180px; font-weight:bold;'>Buscar telefonos por placa</td>";
echo "<td style='padding:8px; width:260px;'><input type='text' name='param8' id='param8' value='$param8' class='form-control' placeholder='Escriba la placa'></td>";
echo "<td style='padding:8px; width:160px;'><input type='submit' value='Traer telefonos' class='btn btn-primary'></td>";
echo "</tr></table>";
echo "</form>";

if($bus_buscado!=""){
	$telefonos_bus=array();
	$telefonos_vistos=array();
	$sqltelefonos="SELECT `gas_telconductor`
		FROM `gastos`
		WHERE `idgastos`>0
		and `gas_bus` like '%$bus_buscado%'
		and `gas_telconductor`!=''
		ORDER BY `gas_fecharegistro` asc, `idgastos` asc";
	$DB->Execute($sqltelefonos);
	while($rwtelefono=mysqli_fetch_row($DB->Consulta_ID)){
		$telefono_limpio=trim($rwtelefono[0]);
		$telefono_key=preg_replace("/[^0-9]/", "", $telefono_limpio);
		if($telefono_key==""){
			$telefono_key=$telefono_limpio;
		}
		if(!isset($telefonos_vistos[$telefono_key])){
			$telefonos_vistos[$telefono_key]=1;
			$telefonos_bus[]=$telefono_limpio;
		}
	}
	$FB->titulo_azul1("Telefonos del bus $bus_buscado", 9, 0, 7);
	if(count($telefonos_bus)>0){
		echo "<table width='100%' class='text'><tr><td>";
		echo "<div style='display:flex; flex-wrap:wrap; gap:8px; padding:6px 0;'>";
		foreach($telefonos_bus as $telefono_bus){
			echo "<span style='display:inline-block; background:#F3F6FB; border:1px solid #D7E0EC; border-radius:16px; padding:6px 12px; font-weight:bold;'>".$telefono_bus."</span>";
		}
		echo "</div>";
		echo "</td></tr></table>";
	}else{
		echo "<table width='100%' class='text'><tr><td>No se encontraron telefonos registrados para ese bus.</td></tr></table>";
	}
}

if($rcrear==1) { $FB->nuevo("Remesas", $condecion, ""); } 

$FB->titulo_azul1("Fecha",1,0,5); 
$FB->titulo_azul1("Consecutivo",1,0,0); 
$FB->titulo_azul1("Usuario",1,0,0); 
$FB->titulo_azul1("Sede Origen",1,0,0); 
$FB->titulo_azul1("Sede Destino",1,0,0); 	
$FB->titulo_azul1("Empresa TR",1,0,0); 
$FB->titulo_azul1("# BUS",1,0,0); 
$FB->titulo_azul1("Tel Conductor",1,0,0); 
$FB->titulo_azul1("Pagar en?",1,0,0); 
$FB->titulo_azul1("Operario Remesa",1,0,0); 
$FB->titulo_azul1("Entrego?",1,0,0); 
$FB->titulo_azul1("Descripcion",1,0,0); 
$FB->titulo_azul1("Peso",1,0,0); 
$FB->titulo_azul1("Piezas",1,0,0); 
$FB->titulo_azul1("Pagar",1,0,0); 
$FB->titulo_azul1("confirmar",1,0,0); 
$FB->titulo_azul1("Confirmo",1,0,0); 
$FB->titulo_azul1("Valor Aprobado",1,0,0); 
$FB->titulo_azul1("Fecha Confirmacion",1,0,0);

if($nivel_acceso!=3){  
	$FB->titulo_azul1("Asignar Recogida",1,0,0);  
	$FB->titulo_azul1("Fecha Recogida",1,0,0);  
	$FB->titulo_azul1("Operario Recoge",1,0,0); 
	$FB->titulo_azul1("Recogio?",1,0,0); 
	$FB->titulo_azul1("Valido",1,0,0); 
	$FB->titulo_azul1("Descripcion",1,0,0); 
	$FB->titulo_azul1("Fecha Va",1,0,0); 
	$FB->titulo_azul1("Imagen",1,0,0); 
	$FB->titulo_azul1("Agregar",1,0,0); 

	$conde2="";
}else {
	$conde2=" and (gas_iduserrecoge='$id_usuario' or gas_iduserremesa='$id_usuario'  )"; 
}
if($nivel_acceso==1){
	$FB->titulo_azul3("Acciones",2,0,2,$param_edicion);
	}
$sql="SELECT `idgastos`,
		`gas_fecharegistro`, 
		`usu_nombre`, 
		`gas_idciudadori`, 
		`sed_nombre`, 
		`gas_empresa`, 
		`gas_bus`, 
		`gas_telconductor`,
		`gas_pagar`,
		`gas_iduserremesa`, 
		`gas_nomremesa`,
		`gas_descripcion`,
		`gas_peso`,
		`gas_piezas`,
		`gas_valor`,
		gas_usucom,
		gas_cantcom,
		gas_feccom ,
		gas_idciudaddes,
		gas_iduserrecoge,
		gas_recogio,
		gas_entrego,
		gas_fecrecogida,
		`gas_descrecogio`, 
		`gas_nomvalida`, 
		`gas_fechavalida`
		FROM `gastos` 
		inner join usuarios on gas_idusuario=idusuarios 
		inner join sedes on idsedes=gas_idciudaddes
		WHERE idgastos>0  
		$conde1 $conde2 $conde3 
		ORDER BY idgastos asc";
$DB1->Execute($sql); $va=0;
while($rw1=mysqli_fetch_row($DB1->Consulta_ID))
{
	$id_p=$rw1[0];
	$va++; $p=$va%2;
	if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
		if($rw1[16]!='' or $rw1[16]>0){
			$rw1[16]=number_format($rw1[16],0,".",".");
		}
		$rw1[16]=number_format($rw1[16],0,".",".");

		$sql2="SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes='$rw1[3]'";
		$DB->Execute($sql2);
		$rw=mysqli_fetch_row($DB->Consulta_ID);
		if($rw1[21]==0){
			$entrego='Sin Recoger';

		} else if($rw1[21]==1){
			$entrego='SI';

		} else if($rw1[21]==2){
			$entrego='Devuelto';
		}

	echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
		echo "<td>".$rw1[1]."</td>
		<td>Remesa#".$rw1[0]."</td>
		<td>".$rw1[2]."</td>
		<td>".$rw[1]."</td>
		<td>".$rw1[4]."</td>
		<td>".$rw1[5]."</td>
		<td>".$rw1[6]."</td>
		<td>".$rw1[7]."</td>
		
		<td>".$rw1[8]."</td>
		<td>".$rw1[10]."</td>
		<td>".$entrego."</td>
		<td>".$rw1[11]."</td>
		<td>".$rw1[12]."</td>
		<td>".$rw1[13]."</td>
		<td>".$rw1[14]."</td>
		

		";
		
		if($nivel_acceso==1){
			echo "<td align='center' >
			<a  onclick='pop_dis10($id_p,\"Aprobar\",1)';  style='cursor: pointer;' title='Aprobar' ><img src='img/Confirmar1.png'></a></td>";
		}else if($rw1[16]<=0){
			echo "<td align='center' >Pendiente por Aprobar
			</td>";
		}else {
			echo "<td align='center' >Solicitud  Aprobada
			</td>";
		}
		
		echo "<td>".$rw1[15]."</td>
		<td>".$rw1[16]."</td>
		<td>".$rw1[17]."</td>
		";
		if($nivel_acceso!=3){  

			if($rw1[15]!=''){
				echo "<td align='center' >";
				echo "<a  onclick='pop_dis24($id_p,\"asignar remesa\",$rw1[18])';  style='cursor: pointer;' title='Asignar Remesa' ><img src='img/paquete.png'></a></td>";
			}else{
				echo "<td align='center' >Pendiente por Aprobar
			</td>";
			}

				$sql5="SELECT `idusuarios`,`usu_nombre` FROM `usuarios` WHERE `idusuarios`='$rw1[19]' ";
				$DB->Execute($sql5);
				$nombreuser=$DB->recogedato(1);
				
				echo "<td>".$rw1[22]."</td>";
				echo "<td>".$nombreuser."</td>";
				if($rw1[20]==0){
					$recogio='Sin Recoger';

				}else if($rw1[20]==1){
					$recogio='Si';

				} else if($rw1[20]==2){
					$recogio='Devuelto';
				}
		}
		echo "<td>".$recogio."</td>
		<td>".$rw1[24]."</td>
		<td>".$rw1[23]."</td>
		<td>".$rw1[25]."</td>
		";
		$LT->llenadocs2($DB, "gastos", $id_p, 2, 35, 'Historico');
		$guiatipo="Remesa";
		echo "<td align='center' >";
		echo "<a  onclick='pop_dis16($id_p,\"FotoRemesa\",\"$guiatipo\")';  style='cursor: pointer;' title='foto Remesa' >Subir Remesa</a></td>";

	if($nivel_acceso==1){
		$DB->edites($id_p, "remesas", 2, $condecion);
	}
	echo "</tr>";
}
include("footer.php"); ?>

<script>
// Obtener el campo de entrada


function ejecutarFuncion(valor) {
  // Obtener el valor ingresado en el input
  

  // Llamar a la función y pasarle el valor ingresado
  var campoValor = document.getElementById('param6');
  var valorIngresado = campoValor.value;
  var numCaracter= valor.length;
  

  if (valorIngresado.length >= valor.length) {
		if (valor === valorIngresado) {
		//Llamar a la función y pasarle el valor ingresado

		}else{
			setTimeout(function() {
			alert("El Monto no coincide, debe ser "+valor);
			campoValor.value = '';
		}, 500); // 1000 milisegundos = 1 segundo
		
		}
		
	}

	// Verificar si la longitud del valor ingresado es igual a 4
	// if (valorIngresado.length === 4) {
	// // Llamar a la función y pasarle el valor ingresado
	// alert("ok"+valor);
	// }
}
</script>
