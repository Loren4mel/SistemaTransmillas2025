<style>
  .disabled-link {
    pointer-events: none;
    cursor: default;
    text-decoration: none;
    color: inherit;
  }
  .whatsapp-button {
            display: flex;
            align-items: center;
            background-color: #25D366;
            color: white;
            font-size: 14px;
            font-weight: bold;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
            transition: background 0.3s, transform 0.2s;
        }

        .whatsapp-button:hover {
            background-color: #1ebe5d;
            transform: scale(1.05);
        }

        .whatsapp-button img {
            width: 18px;
            height: 18px;
            margin-right: 6px;
        }
		.email-button {
            display: inline-flex;
            align-items: center;
            background-color: #2196F3; /* Color de fondo */
            color: white; /* Color del texto */
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }
</style>
<?php 
require("login_autentica.php"); 
include("layout.php");
$DB2 = new DB_mssql;
$DB2->conectar(); 

 if($_SESSION['inicio']==1 and $nivel_acceso!=3){
?>
<script type="text/javascript">
$("#myModalinicio").modal("show"); 
</script>
<?php 
$_SESSION['inicio']='2';
}
?>
<script>
function llena_datos(ex, nivel, ordby, asc)
{
	p1=document.getElementById('param1').value;
	p2=document.getElementById('param2').value;
	p4=document.getElementById('param4').value;
	p5=document.getElementById('param5').value;

	id_usuario=document.getElementById('id_usuario').value;

	destino="guiascliente_excel.php?param1="+p1+"&param2="+p2+"&param4="+p4+"&param5="+p5+"&id_usuario="+id_usuario;
	location.href=destino;
}

</script>
<?php 
if($nivel_acceso==6){


	$FB->titulo_azul1("Busqueda de Guias",9,0,5);  
	$FB->abre_form("form1","","post");

	$fechaactual=date('Y-m-d');
	$fechaactualfinal=date('Y-m-d');
	if($param4!=''){  $fechaactual=$param4; } else { $param4=$fechaactual; }
	if($param5!=''){  $fechaactualfinal=$param5;  } else { $param5=$fechaactualfinal; }


	$idtelefono="SELECT `usu_telefono`,`usu_idcredito` FROM `usuarios` WHERE  `idusuarios`='$id_usuario'";
	$DB->Execute($idtelefono);
	$telefono=mysqli_fetch_row($DB->Consulta_ID);

	$sqlhvcli="SELECT hoj_nom_id  FROM `hojadevidacliente`  WHERE  hoj_clientecredito='".$telefono[1]."' ";		
	$DB1->Execute($sqlhvcli);
	$datohvc=mysqli_fetch_row($DB1->Consulta_ID);
	if ($datohvc[0]=="") {
		$Identificador="Id";	
		}else {
			$Identificador=$datohvc[0];	
		}	
	$FB->llena_texto("Fecha inicial:", 4, 10, $DB, "", "", "$fechaactual", 1, 0);
	$FB->llena_texto("Fecha final:", 5, 10, $DB, "", "", "$fechaactualfinal", 4, 0);
	$FB->llena_texto("Busqueda por:",1,82,$DB,$busqueda,"",$param1,1,0);
	$FB->llena_texto("Dato:", 2, 1, $DB, "", "","$param2", 4,0);
	echo '<td align="right">Exportar a :<a href="#" onclick="llena_datos(1, 1, &quot;id_nombre&quot;, &quot;ASC&quot;);" target=""><img src="img/excel.jpg" width="30"></a></td>';
	echo"<td align='right'><div id='loading' style='display: none;'>
    <img src='img/loading_gif.gif' alt='Cargando...' />
    
	</div>
	<button type='button' class='email-button' onclick='pop_dis16(\"$id_usuario\",\"Archivo Drive\",\"\")' ><i class='fas fa-envelope'></i>Archivo drive</button>
	<button type='button' class='email-button' onclick='enviarEmail(1, 1, &quot;id_nombre&quot;, &quot;ASC&quot;)' ><i class='fas fa-envelope'></i>Email</button></td></tr>";
	$FB->llena_texto("", 3, 142, $DB, "BUSCAR", "","", 1, 0);
	$fechaactual=date($fechaactual.' 01:00:00');
	$fechaactualfinal=date($fechaactualfinal.' 23:59:59'); 
	$FB->cierra_form(); 	
	$FB->titulo_azul1("#Idservicio",1,0,7); 
	$FB->titulo_azul1("#Guia",1,0,0); 
	$FB->titulo_azul1("$Identificador",1,0,0); 
	$FB->titulo_azul1("Destinatario",1,0,0); 
	$FB->titulo_azul1("Ciudad",1,0,0); 
	$FB->titulo_azul1("Fecha Ingreso",1,0,0); 
	$FB->titulo_azul1("Tel&eacute;fono",1,0,0); 
	$FB->titulo_azul1("Direcci&oacute;n",1,0,0); 
	$FB->titulo_azul1("Piezas",1,0,0); 
	$FB->titulo_azul1("Volumen",1,0,0); 
	$FB->titulo_azul1("Peso",1,0,0); 
	$FB->titulo_azul1("Valor",1,0,0); 
	$FB->titulo_azul1("Guia",1,0,0); 
	$FB->titulo_azul1("Imprimir",1,0,0); 

	$conde1=""; 
	$conde3=""; 

	if($param2!="" and $param1!=""){ 
	$conde1=" and $param1 like '%$param2%' "; 
	}else { $conde1=""; } 

	if($param1==""){ $param1="ser_prioridad"; } 


	$fehcaactual=date('Y-m-d');	 


	$sql="SELECT 
    a.idservicios, a.ser_fechaentrega, a.cli_nombre, a.cli_telefono, a.cli_direccion, 
    a.ser_destinatario, a.ser_telefonocontacto, a.ser_direccioncontacto, a.ciu_nombre, 
    a.ser_prioridad, a.ser_fecharegistro, a.ser_consecutivo, a.ser_guiare, a.cli_idciudad, 
    a.ser_estado, '1' as tipoc, a.ser_peso, a.ser_volumen, a.ser_valor, 
    a.ser_valorseguro, a.ser_piezas, b.ser_codeCliente  
	FROM serviciosdia AS a  
	INNER JOIN servicios AS b ON a.idservicios = b.idservicios  
	WHERE 
		a.cli_telefono = '$telefono[0]'  
		AND a.ser_fecharegistro BETWEEN '$fechaactual' AND '$fechaactualfinal'  
		AND a.ser_estado != 100  
	$conde1  ORDER BY $param1 $asc ";

		//  $sql="SELECT `idservicios`,`ser_fechaentrega`,`cli_nombre`, `cli_telefono`,`cli_direccion`, `ser_destinatario`, `ser_telefonocontacto`,`ser_direccioncontacto`,`ciu_nombre`,`ser_prioridad`,ser_fecharegistro,ser_consecutivo,ser_guiare,cli_idciudad,ser_estado,'1' as tipoc,ser_peso,ser_volumen,ser_valor,ser_valorseguro,ser_piezas
		// FROM serviciosdia where cli_telefono='$telefono' and ser_fecharegistro BETWEEN '$fechaactual' AND '$fechaactualfinal'  and ser_estado!=100  $conde1  ORDER BY $param1 $asc ";
	
		$DB->Execute($sql); $va=0; 
		while($rw1=mysqli_fetch_row($DB->Consulta_ID))
		{
			$id_p=$rw1[0];
			$va++; $p=$va%2;
			if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
			echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
			
			$direct2=str_replace("&"," ", $rw1[7]);
			$pordeclarado=(intval($rw1[19])*1)/100;
			$rw1[18]=$rw1[18]+$pordeclarado;
			echo "
			<td>".$rw1[0]."</td>
			<td>".$rw1[12]."</td>
			<td>".$rw1[21]."</td>
			<td>".$rw1[5]."</td>
			<td>".$rw1[8]."</td>
			<td>".$rw1[10]."</td>
			<td>".$rw1[6]."</td>
			<td>".$direct2."</td>
			<td>".$rw1[20]."</td>
			<td>".$rw1[17]."</td>
			<td>".$rw1[16]."</td>
			<td>".$rw1[18]."</td>
			";
	
			echo "<td align='center' ><a  onclick='pop_dis5($id_p,\"Recogidas\")';  style='cursor: pointer;' title='Recogidas' ><img src='img/recogidas.png'></a></td>";
		//	echo "<td align='center' ><a  onclick='pop_dis24($id_p,\"Asignar Paquete\",$rw1[13])';  style='cursor: pointer;' title='Asignar Paquete' ><img src='img/paquete.png'></a></td>";
			//echo "<td align='center' >	<a  onclick='pop_dis1($id_p, \"Seguimiento Datos\")';  style='cursor: pointer;' title='Editar Datos' ><img src='img/informacion.jpg'></a></td>";		
		echo "<td align='center' >";
					if ($nivel_acceso==1) {
					echo "<a href='ticketfactura.php?id_param=$id_p&pagina2=imprimirasignar.php' target='_blank'><img src='img/imprimir.png'></a>";	

					}
			echo "</td>";	
					
/* 			echo "<td align='center'  data-toggle='tooltip' data-placement='top'  title='' ><a  onclick='generarcodigo($id_p)';  target='_blank'  style='cursor: pointer;' title='Imprimir Codigo' >
			<img src='img/codigo.png'></a>";
			echo '</td>';  */
		
			echo "</tr>"; 
		
		}
		echo "<tr><td align='center' > Total Datos:$va</td>"; 				
		echo "</tr>"; 

}elseif($nivel_acceso!=3){

		$fechaactual=date('Y-m-d');	 
		$preoper="SELECT `idpreoperacinal` FROM `pre-operacional` WHERE `prefechaingreso` like '$fechaactual%' and `preidusuario`='$id_usuario'";
		$DB->Execute($preoper);
		$preop=$DB->recogedato(0);
		if($preop>=1){
			//echo $_SESSION['usuario_rol'];
			$FB->titulo_azul1("Busqueda de Guias",9,0,5);  
			$FB->abre_form("form1","","post");
			$fechainicial=date('Y-m-01');
			if ($param4 !="" and  $param5 !="") {
				$fechaIni=$param4." 00:00:00";
				$fechafin=$param5." 23:59:59";
				$condefecha="and ser_fecharegistro >='$fechaIni' and ser_fecharegistro <= '$fechafin'";
			}

			$FB->llena_texto("Fecha del :", 4, 10, $DB, "", "", "$param4", 1, 0);
			$FB->llena_texto("Fecha al :", 5, 10, $DB, "", "", "$param5", 4, 0);
			$FB->llena_texto("Busqueda por:",1,82,$DB,$busqueda,"",$param1,1,0);
			$FB->llena_texto("Dato:", 2, 1, $DB, "", "","$param2", 4,0);
			if($nivel_acceso==1){
				$FB->llena_texto("Estado Factura:",6,82,$DB,$estadocreditos,"",$param1,1,0);
				echo "<td><button type='button' class='btn btn-success' onclick='enviarDatosPrefactura();'>Crear PRE-Factura</button></td></tr>";
			}
			$FB->llena_texto("", 3, 142, $DB, "BUSCAR", "","", 1, 0);




			$FB->cierra_form(); 
			$FB->titulo_azul1("Fecha Ingreso",1,0,7); 
			$FB->titulo_azul1("#Idservicio",1,0,0); 
			$FB->titulo_azul1("#Guia",1,0,0); 
			$FB->titulo_azul1("#Relacionado",1,0,0); 
			$FB->titulo_azul1("Remitente",1,0,0); 
			$FB->titulo_azul1("Tel&eacute;fono",1,0,0); 
			$FB->titulo_azul1("Direcci&oacute;n",1,0,0); 
			$FB->titulo_azul1("Destinatario",1,0,0); 
			$FB->titulo_azul1("Tel&eacute;fono",1,0,0); 
			$FB->titulo_azul1("Direcci&oacute;n",1,0,0); 
			$FB->titulo_azul1("Ciudad",1,0,0); 
			$FB->titulo_azul1("Servicio",1,0,0); 
			$FB->titulo_azul1("Recogida",1,0,0); 
			$FB->titulo_azul1("Imagen",1,0,0); 

			$FB->titulo_azul1("Entrega",1,0,0); 
			$FB->titulo_azul1("Imagen",1,0,0); 

			$FB->titulo_azul1("Guia",1,0,0); 
			$FB->titulo_azul1("Imprimir",1,0,0); 
			$FB->titulo_azul1("Codigo",1,0,0); 
			$FB->titulo_azul1("Editar",1,0,0); 


			$conde1=""; 
			$conde3=""; 

			if($param2!="" and $param1!=""){ 
			$conde1=" $param1 like '%$param2%' "; 
			}else { $conde1=" idservicios=0 "; } 

			if($param1==""){ $param1="ser_prioridad"; } 
			$fehcaactual=date('Y-m-d');	 
			
			if($param6=="Sin Facturar"){ $conde2="and (ser_numerofactura ='' or ser_numerofactura is null)";}elseif ($param6=="Facturados") {
				$conde2="and (ser_numerofactura !='' or ser_numerofactura IS NOT NULL)";	
			}else{$conde2="";	} 
			$idguias='';
			
		$sql="SELECT `idservicios`,`ser_fechaentrega`,`cli_nombre`, `cli_telefono`,`cli_direccion`, `ser_destinatario`, `ser_telefonocontacto`,`ser_direccioncontacto`,`ciu_nombre`,`ser_prioridad`,ser_fecharegistro,ser_consecutivo,ser_guiare,cli_idciudad,ser_estado,'1' as tipoc,ser_clasificacion	FROM serviciosdia where $conde1 $condefecha $conde2
		union 
		SELECT `idservicios`,`ser_fechaentrega`,`cli_nombre`, `cli_telefono`,`cli_direccion`, `ser_destinatario`, `ser_telefonocontacto`,`ser_direccioncontacto`,`ciu_nombre`,`ser_prioridad`,ser_fecharegistro,ser_consecutivo,ser_guiare,cli_idciudad,ser_estado,'2' as tipoc,ser_clasificacion
		FROM servicios2 inner join rel_sercli  on idservicios=ser_idservicio  inner join clientesservicios on idclientesdir=ser_idclientes inner join clientes on idclientes=cli_idclientes  inner join ciudades on idciudades=ser_ciudadentrega  where $conde1 $condefecha $conde2
		ORDER BY $param1 $asc "; 		
				$DB->Execute($sql); $va=0; 
				while($rw1=mysqli_fetch_row($DB->Consulta_ID))
				{
					$id_p=$rw1[0];
					
					$idguias=$id_p.','.$idguias;
					$va++; $p=$va%2;
					if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
					echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
					$direc1=str_replace("&"," ", $rw1[4]);
					$direct2=str_replace("&"," ", $rw1[7]);
					echo "<td>".$rw1[10]."</td>
					<td>".$rw1[0]."</td>
					<td>".$rw1[11]."</td>
					<td>".$rw1[12]."</td>
					<td>".$rw1[2]."</td>
					<td>".$rw1[3]."</td>
					<td>".$direc1."</td>
					<td>".$rw1[5]."</td>
					<td>".$rw1[6]."</td>
					<td>".$direct2."</td>
					<td>".$rw1[8]."</td>
					<td>".$rw1[9]."</td>
					";
					$recogidasg='';
					$entrgasg='';
					$idimagenent=0;
					$idimagenre=0;

					$sqlrecogida="SELECT ima_ruta,ima_tipo,idimagenguias,ima_fecha from imagenguias where ima_idservicio=$id_p ";
					$DB1->Execute($sqlrecogida); 
					while($guiasi=mysqli_fetch_row($DB1->Consulta_ID))
					{

						if($guiasi[1]=='Recogida'){
							$fechareco=$guiasi[3];
							$recogidasg=$guiasi[0];
							$idimagenre=$guiasi[2];
						}elseif($guiasi[1]=='Entrega'){
							$entrgasg=$guiasi[0];
							$idimagenent=$guiasi[2];
							$fechaent=$guiasi[3];
						}

					}
					
					$firma="SELECT firma,tipo_firma,id  FROM firma_clientes WHERE  id_guia='$id_p' ";
					$DB1->Execute($firma); 
					while($firmaid=mysqli_fetch_row($DB1->Consulta_ID))
					{

						if($firmaid[1]=='Recogida'){
							$firmaidR=$firmaid[2];

						}elseif($firmaid[1]=='Entrega'){
							$firmaidE=$firmaid[2];

						}

					}
					
					if($recogidasg!=''){

						if($nivel_acceso==2){
						echo "<td align='center' >";
						echo "<p style='color: #808080;' >&nbsp;<i></i>&nbsp;Ver Foto Guia </p>";
						echo"<button onclick='pop_dis5($id_p,\"Enviar Guia R\")' class='whatsapp-button'>Whatsapp</button>";
						}else {
						echo "<td align='center' >";
						
						
						if ($fechareco<"2024-12-01") {
							$colorfoto="";
							echo"<a href=' https://8b55-190-24-125-143.ngrok-free.app/SistemaTransmillas/$recogidasg' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
							
						}elseif ($fechareco<="2025-02-13") {
							$colorfoto="";
							echo"<a href='$recogidasg' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia hh</a>";
							
						}else{
							$rutar=$entrgasg;
							if (strpos($recogidasg, 'ticketfacturacorreoimprimir') !== false) {
								$recogidasg="$recogidasg&vis=adm";
								$rutar="";
							} 
							$colorfoto="";
							// $confotor="<a href='$recogidag&vis=adm'' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";enviarAlertaWhat(\"".$rw1[12]."\",\"".$rw1[3]."\",24,\"".$rw1[0]."\",\"".$recogidasg."\")
							
							//  echo "<a href='$recogidasg' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
							 echo "<a href='https://sistema.transmillas.com/nueva_plataforma/controller/VerguiaController.php?guia=".$rw1[11]."R' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";

							 
							 echo"<button onclick='pop_dis5($id_p,\"Enviar Guia R\")' class='whatsapp-button'>Whatsapp</button>";
							 
						}
					}


						if($nivel_acceso==1){
						echo "<br><a href='del_admin.php?id_param=$idimagenre&tabla=Elimina Archivo2&ruta=$rutar&idFirma=$firmaidR' title='Eliminar' 
						onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a>";
						
						// echo "<br><a href='$recogidasg'><i class='fa fa-trash-o'></i></a>";
						
						
					}
						echo "</td>";	
	
					}else{
						$guiatipo=$rw1[11]."_Recogida";
						echo "<td align='center' >";
						echo "<a  onclick='pop_dis16($id_p,\"Fotoguia\",\"$guiatipo\")';  style='cursor: pointer;' title='foto Guia' >Subir Foto Guia</a>";
						echo"<button onclick='abrirPopup(\"".$rw1[11]."\",\"Recogida\",$id_p)'>Ratificar</button></td>";

					}

					$sqlimg="SELECT ser_img_recog,ser_img_entre from servicios where idservicios=$id_p ";
					$DB1->Execute($sqlimg); 
					$img=mysqli_fetch_row($DB1->Consulta_ID);
					if ($img[0]!="") {
						echo "<td align='center' >";
								echo "<a href='imgServicios/$img[0]' target='_blank'>Ver</td>";
					}else {
						echo "<td align='center' >";
						echo "</td>";
					}

					if($entrgasg!=''){
						
						if($nivel_acceso==2){
							echo "<td align='center' >";
							echo "<p style='color: #808080;' >&nbsp;<i></i>&nbsp;Ver Foto Guia </p>";
							echo"<button onclick='pop_dis5($id_p,\"Enviar Guia E\")' class='whatsapp-button'>Whatsapp</button>";

							}else {
								echo "<td align='center' >";
								if ($fechaent<"2024-12-01") {
									$colorfoto="";
									echo"<a href='  https://8b55-190-24-125-143.ngrok-free.app/SistemaTransmillas/$recogidag' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
									
								}elseif ($fechaent<="2025-02-13") {
									$colorfoto="";
									echo"<a href='$entrgasg' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
									
								}else{
									$rutae=$entrgasg;
									if (strpos($entrgasg, 'ticketfacturacorreoimprimir') !== false) {
										$entrgasg="$entrgasg&vis=adm";
										$rutae="";
									} 
									$colorfoto="";
									// $confotor="<a href='$entrgasg&vis=adm'' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
									// echo "<a href='$entrgasg&vis=adm' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia E</a></td>";
									
									// echo "<a href='$entrgasg' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
									echo "<a href='https://sistema.transmillas.com/nueva_plataforma/controller/VerguiaController.php?guia=".$rw1[11]."E' target='_blank'>&nbsp;<i class='fa fa-camera-retro fa-lg'></i>&nbsp;Ver Foto Guia </a>";
									
									echo"<button onclick='pop_dis5($id_p,\"Enviar Guia E\")' class='whatsapp-button'>Whatsapp</button>";
									
								}
								
								// echo "<br><a href='$entrgasg&vis=adm'><i class='fa fa-trash-o'></i></a>";
							}
						
						if($nivel_acceso==1){
						echo "<br><a href='del_admin.php?id_param=$idimagenent&tabla=Elimina Archivo2&ruta=$rutae&idFirma=$firmaidE' title='Eliminar' 
						onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a>";
						}
						echo "</td>";		
	
					}else{
						$guiatipo=$rw1[11]."_Entrega";
						echo "<td align='center' >";
						echo "<a  onclick='pop_dis16($id_p,\"Fotoguia\",\"$guiatipo\")';  style='cursor: pointer;' title='foto Guia' >Subir Foto Guia</a>";
						echo"<button onclick='abrirPopup(\"".$rw1[11]."\",\"Entrega\",$id_p)'>Ratificar</button></td>";

					}
					if ($img[1]!="") {
						echo "<td align='center' >";
								echo "<a href='imgServicios/$img[1]' target='_blank'>Ver</td>";
					}else {
						echo "<td align='center' >";
						echo "</td>";
					}


					echo "<td align='center' ><a  onclick='pop_dis5($id_p,\"Recogidas\")';  style='cursor: pointer;' title='Recogidas' ><img src='img/recogidas.png'></a></td>";
				//	echo "<td align='center' ><a  onclick='pop_dis24($id_p,\"Asignar Paquete\",$rw1[13])';  style='cursor: pointer;' title='Asignar Paquete' ><img src='img/paquete.png'></a></td>";
					//echo "<td align='center' >	<a  onclick='pop_dis1($id_p, \"Seguimiento Datos\")';  style='cursor: pointer;' title='Editar Datos' ><img src='img/informacion.jpg'></a></td>";		
					echo "<td align='center' >";
					if ($nivel_acceso==1) {
						echo "<a href='ticketfactura.php?id_param=$id_p&pagina2=imprimirasignar.php' target='_blank'><img src='img/imprimir.png'></a>";	

					}
						echo "</td>";	
			
					echo "<td align='center'  data-toggle='tooltip' data-placement='top'  title='' ><a  onclick='generarcodigo($id_p)';  target='_blank'  style='cursor: pointer;' title='Imprimir Codigo' >
					<img src='img/codigo.png'></a>";
					echo '</td>'; 
			
					if($nivel_acceso==1 or $nivel_acceso==10){

					echo "<td align='center'  data-toggle='tooltip' data-placement='top'  title='' ><a  onclick='pop_dis11($id_p, \"Editar Datos Guia\", \"inicio.php\",\"editarguiacompleta.php\",$rw1[16]);  ';  style='cursor: pointer;' title='Verificar Datos' >
					<img src='img/informacion.jpg'></a>";
					echo '</td>';
					
					} else {
						echo "<td align='center'  data-toggle='tooltip' data-placement='top'  title='' ></td>";
					}	
					echo "</tr>"; 
				
				}
				echo "<tr><td align='center' > Total Datos:$va</td>"; 				
				echo "</tr>"; 
				echo"<input type='hidden' id='idguias' value='$idguias'>";
			}else{

				$param4='covid19';
				$campo='preencuesta';
				$preoperacional='preoperacional';
				// if (in_array((int)$nivel_acceso, [1, 2], true)) {
					$preoperacionalUrl = "nueva_plataforma/controller/PreoperacionalController.php"
						. "?param4=" . urlencode($param4)
						. "&param5=nuevo"
						. "&campo=" . urlencode($campo)
						. "&preoperacional=" . urlencode($preoperacional)
						. "&iduser=" . urlencode($id_usuario)
						. "&fecha=" . urlencode($fechaactual);

					echo "<iframe src='$preoperacionalUrl' style='width:100%; min-height:900px; border:0;' loading='lazy'></iframe>";
					
				// }else{
				// 	include("preoperacional.php");
				// }
				
			
			}

}else {

	$fechaactual=date('Y-m-d');	 
	/*  $Querydrag_drop = ("SELECT  orden_idservicio FROM ord_recoentregas WHERE orden_iduserencargado =$id_usuario and orden_fechadiaejecucion ='$fechaactual' and ord_estado='Ordenado' order by orden asc ");
	$DB2->Execute($Querydrag_drop);
	$orden=$DB->recogedato(0); */

		$compañero="SELECT seg_compañero from seguimiento_user where seg_fechaalcohol like '$fechaactual%'  and seg_idusuario='$id_usuario'  ";
		$DB1->Execute($compañero); 
		$rwcom=mysqli_fetch_row($DB1->Consulta_ID);
		$compa=$rwcom[0];
		if ($compa!="") {
			$condeCom="(seg_idusuario ='$id_usuario' or seg_idusuario ='$compa') ";
		}else{
	
			$condeCom="seg_idusuario ='$id_usuario'"; 
		}

		$preoper="SELECT `idpreoperacinal` FROM `pre-operacional` WHERE `prefechaingreso` like '$fechaactual%' and `preidusuario`=$id_usuario";
		$DB->Execute($preoper);
		$preop=$DB->recogedato(0);

		 $idestadoguia="SELECT  CONCAT(seg_estado,'|',seg_direccion,'|',seg_tipo,'|',seg_idservicio)  as id FROM seguimientoruta where $condeCom and seg_fecha like '%$fechaactual%' and seg_estado!='Cambioruta' order by `seg_fechaestado` desc limit 1";
		$DB->Execute($idestadoguia);
		$estadoguia=$DB->recogedato(0);

		$datos=explode("|",$estadoguia);
		$estadoguia=$datos[0];
		$direccion=$datos[1];
		$tipo=$datos[2];
		$idservicioruta=$datos[3];

		
		if($preop>=1 and $estadoguia==''){
		include("reordenar.php");
	
		}elseif($preop>=1 and $estadoguia!=''){
			include("reordenar.php");
		
		}else{

			 $vehiculo="SELECT usu_vehiculo FROM `usuarios` where  `idusuarios`=$id_usuario";
			$DB->Execute($vehiculo);
			$valorvehiculo=$DB->recogedato(0);
			if($valorvehiculo>0){

				$param4='nuevo';
			}else{
				echo '<table><tr bgcolor="#ff0000" class="tittle3"><td colspan="4" >SI USTED ES CONDUCTOR Y NO LE APARECE EL PREOPERACIONAL POR FAVOR COMUNIQUELO AL ADMINISTRADOR</td></tr></table>';

				$param4='covid19';
			}
			$param5='nuevo';
			$campo='preencuesta';
			$preoperacional='preoperacional';
			include("preoperacional.php");
		}  
	//include("recogidasentregas.php");	
}
  $FB->llena_texto("id_usuario", 1, 13, $DB, "", "", "$id_usuario", 5, 0);
	 ?>

	<?php

if($nivel_acceso==3 and $preop>=1){
	
	if($estadoguia=='completado' or $tipo=='opcionruta'){

		$direccion = addslashes($direccion); // Escapar comillas dobles
		$mensaje=$direccion;
		$opcion=1;
		seguimientoruta($mensaje,$opcion,"seguimientoruta");

	}else if($estadoguia=="En ruta"  ){
		$mensaje=$direccion;
		$opcion=2;
		seguimientoruta($mensaje,$opcion,"cambiarruta");
	}else{

		/* 	 $Querydrag_drop = ("SELECT  orden_idservicio FROM ord_recoentregas WHERE orden_iduserencargado =$id_usuario and orden_fechadiaejecucion ='$fechaactual'  order by orden asc limit 1");
			$DB2->Execute($Querydrag_drop);
			$orden=$DB2->recogedato(0); 
			if($orden>0){ 
				 $sql = "INSERT INTO `seguimientoruta`( `seg_fecha`, `seg_idservicio`, `seg_direccion`, `seg_tipo`, `seg_estado`,`seg_idusuario`,`seg_fechaestado`,seg_fechafinalizo) values('$fechatiempo','1000','Disponible Automatico','opcionruta','Disponible','$id_usuario','$fechatiempo','$fechatiempo')";
				$DB->Execute($sql);
			}else{ */
				$mensaje=$direccion;
				$opcion=1;
				seguimientoruta($mensaje,$opcion,"seguimientoruta");
			//}
			

		}

		

	?>


	<?php 	
		
}	

include("footer.php");
?>
<script>
	function abrirPopup(idguia,imprimir,id_param) {

		window.open("ratificafirmadigital.php?idguia="+idguia+"&imprimir="+imprimir+"&id_param="+id_param+"", "popup", "width=600,height=400");
	}

	async function enviarAlertaWhat(numguia, tipo, idservi,imagen1,id) {
		var telefono = document.getElementById('tele').value;
    // URL de la API
    const url = "https://www.transmillas.com/ChatbotTransmillas/alertas.php";

    // Datos a enviar en la solicitud
    const data = {
        numero_guia: numguia, // Número de guía
        telefono: telefono,    // Número de teléfono
        tipo_alerta: tipo,     // Tipo de alerta
        id_guia: idservi,
		imagen1: imagen1,
		id: id     
    };

    try {
        // Realizar la solicitud POST con fetch
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer MiSuperToken123" // Si la API requiere autenticación
            },
            body: JSON.stringify(data) // Convertir los datos a JSON
        });

        // Verificar si la respuesta fue exitosa
        if (!response.ok) {
            throw new Error(`Error en la solicitud: ${response.statusText}`);
        }

        // Decodificar la respuesta
        const responseData = await response.json();
        
        // Mostrar la respuesta
        console.log("Respuesta de la API:", responseData);
		    // Muestra solo el mensaje de éxito (o el campo específico que necesites)
			// if (responseData.message) {
			// 	alert(responseData.message); // Muestra solo el mensaje
			// } else {
			 	alert("Mensaje enviado con exito");
			// }
    } catch (error) {
        // Manejar errores
        console.error("Error en la solicitud:", error);
    }
}
function enviarEmail() {
    let p1 = document.getElementById('param1').value;
    let p2 = document.getElementById('param2').value;
    let p4 = document.getElementById('param4').value;
    let p5 = document.getElementById('param5').value;
    let id_usuario = document.getElementById('id_usuario').value;

    let formData = new FormData();
    formData.append("param1", p1);
    formData.append("param2", p2);
    formData.append("param4", p4);
    formData.append("param5", p5);
    formData.append("id_usuario", id_usuario);
	let loadingDiv = document.getElementById('loading'); // Obtener el div del GIF

     // Mostrar el GIF antes de enviar la solicitud
	 loadingDiv.style.display = "block";

	fetch("emailguiascliente_excel.php", {
		method: "POST",
		body: formData
	})
	.then(response => response.json())
	.then(data => {
		// Ocultar el GIF
		loadingDiv.style.display = "none";

		if (data.status === "success") {
			alert("Correo enviado correctamente.");
		} else {
			alert("Error: " + data.message);
		}
	})
	.catch(error => {
		// Ocultar el GIF en caso de error
		loadingDiv.style.display = "none";
		alert("Error en la solicitud: " + error);
	});
}

function enviarDatosPrefactura() {

    // let p1 = document.getElementById('param1').value;
    let p4 = document.getElementById('param4').value;
    let p5 = document.getElementById('param5').value;
	let guias = document.getElementById('idguias').value;


	let errores = [];

	// if (!p1) errores.push("param1 (por ejemplo, fecha o identificador)");
	if (!p4) errores.push("Fecha inicial");
	if (!p5) errores.push("Fecha final");
	if (!guias) errores.push("No hay guias para pre-facturar");

	if (errores.length > 0) {
		alert("⚠️ Faltan los siguientes datos obligatorios:\n\n- " + errores.join("\n- "));
		return; // Detiene la ejecución
	}

    const formData = new FormData();
	// formData.append('param1', p1);
    formData.append('param4', p4);
    formData.append('param5', p5);
	formData.append('guias', guias);
    // Si guías es un array, lo conviertes a una cadena separada por comas (o como esperes en PHP)
    // formData.append('guias', guiasArray.join(','));

    fetch('crear_prefactura.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        console.log('Respuesta del servidor:', result);
        // Aquí puedes mostrar un mensaje o hacer otra acción
    })
    .catch(error => {
        console.error('Error al enviar datos:', error);
    });
}

</script>

