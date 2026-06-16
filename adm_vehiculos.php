<?php 
require("login_autentica.php"); 
include("layout.php");

$FB->titulo_azul1("Vehiculos",9,0,7);  
echo "<div style='margin: 10px 0; text-align: right;'>
		<a href='nueva_plataforma/controller/VehiculosController.php' class='btn btn-primary' style='color: #fff; text-decoration: none;'>
			Ir al nuevo modulo de vehiculos
		</a>
	</div>";
$FB->abre_form("form1","","post");

$FB->llena_texto("Tipo Vehiculo:",1,82,$DB,$tipovehiculo,"cambio3(this.value,param2.value,0,\"adm_vehiculos.php\", 1);",$param1,1,0);
$FB->llena_texto("Estado:",2,8,$DB,$estado_pro,"cambio3(param1.value,this.value,0,\"adm_vehiculos.php\", 1);",$param2,4,0);
$FB->cierra_form(); 
if($rcrear==1) { $FB->nuevo("Vehiculos", $condecion, "configuracion.php?idmen=138"); } 

$FB->titulo_azul6("Tipo Vehiculo",1,0,5,"veh_tipo",$asc2); 
$FB->titulo_azul6("Marca",1,0,0,"veh_marca",$asc2); 
$FB->titulo_azul6("Placa",1,0,0,"veh_placa",$asc2); 
$FB->titulo_azul1("Modelo",1,100,0); 
$FB->titulo_azul1("Dueño",1,0,0); 
$FB->titulo_azul1("Fecha Seguro",1,0,0); 
$FB->titulo_azul1("Fecha Tecnomecánica",1,0,0); 
$FB->titulo_azul1("Fecha CambioAceite",1,0,0); 
$FB->titulo_azul1("Km actual",1,0,0); 
$FB->titulo_azul1("Km CambioAceite",1,0,0); 
$FB->titulo_azul1("CARA SUPERIOR",1,0,0); 
$FB->titulo_azul1("CARA INFERIOR",1,0,0); 
$FB->titulo_azul1("Estado",1,0,0); 
$FB->titulo_azul3("Acciones",2,0,2,$param_edicion);
//echo $param2;
$conde1=""; if($param2!=""){ if($param2=="Activo") { $conde1=" AND veh_estado='1' "; } else { $conde1=" AND veh_estado='0' "; } }  
if($param1!="0" and $param1!=""){ $conde=" AND veh_tipo='$param1' "; } else { $conde="";}  

 $sql="SELECT `idvehiculos`, `veh_tipo`, veh_marca, `veh_placa`, `veh_modelo`,`veh_dueño`,`veh_fechaseguro`, `veh_fechategnomecanica`, `veh_fechamantenimiento`,`veh_kilactual`, `veh_aceitekil`,`veh_estado`,`veh_color`, `veh_tipov`, `veh_chasis`, `veh_motor`, `veh_cilidraje`, `veh_usuve`, `veh_observaciones` FROM `vehiculos` WHERE idvehiculos>0 $conde $conde1 $conde2 ORDER BY $ord $asc ";
$DB->Execute($sql); $va=0; 
while($rw1=mysqli_fetch_row($DB->Consulta_ID)){
	$va++; $p=$va%2; $id_p=$rw1[0];
	if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
	echo "<tr bgcolor='$color' class='text' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";

	if($rw1[5]!='' and $rw1[5]!=0){
		$sql1="SELECT usu_nombre FROM usuarios where idusuarios='$rw1[5]'";
		$DB1->Execute($sql1); 
		$rw2=mysqli_fetch_row($DB1->Consulta_ID); 
		$nombredueño=$rw2[0];
	}else{
		$nombredueño=$rw1[5];
	}
	echo "<td>".$rw1[1]."</td>
			<td>".$rw1[2]."</td>
			<td>".$rw1[3]."</td>
			<td>".$rw1[4]."</td>
			<td>".$nombredueño."</td>";
			echo	$LT->llenadocs3($DB1, "vehiculos", $id_p, 3, 35, "$rw1[6]");
			echo	$LT->llenadocs3($DB1, "vehiculos", $id_p, 4, 35, "$rw1[7]");		
			echo "<td>".$rw1[8]."</td>			
			<td>".$rw1[9]."</td>			
			<td>".$rw1[10]."</td>					
			";

		echo	$LT->llenadocs3($DB1, "vehiculos", $id_p, 1, 35, 'CARA SUPERIOR');
		echo	$LT->llenadocs3($DB1, "vehiculos", $id_p, 2, 35, 'CARA INFERIOR');

		echo "<td><div id='inactivo$va'>"; if($rw1[11]==1){ $st='Activo'; } else { $st='Inactivo'; } 
		echo "<select name='param11' id='param11' class='form-control' onChange='cambio_ajax2(this.value, 63, \"inactivo$va\", \"$va\", 1, $id_p)' required>";		
			
		$LT->llenaselect_ar($st,$estado_pro);
			echo "</select></div></td>";
			
	$DB->edites($id_p, "Vehiculos", $param_edicion, $condecion);
	echo "</tr>";
}
include("footer.php");
?>
