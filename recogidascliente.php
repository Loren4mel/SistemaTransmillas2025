<?php 
require("login_autentica.php"); 
include("layout.php");
$id_usuario=$_SESSION['usuario_id'];

$sqlcliente="SELECT `idclientes`,`idclientesdir`, `cli_iddocumento`,  `cli_telefono`, `cli_nombre`, `cli_email`, `cli_direccion`, `cli_idciudad`,`cli_clasificacion`, `cli_tipo`, `cli_fecharegistro`,`cli_valoraprobado`,`cli_valorprestado`,usu_idcredito,cre_nombre,idcreditos  FROM `clientes`  inner join clientesdir on cli_idclientes=idclientes inner join usuarios on usu_telefono=cli_telefono inner join creditos on idcreditos=usu_idcredito WHERE  idusuarios='$id_usuario' ";		
$DB1->Execute($sqlcliente);
$datoscliente=mysqli_fetch_row($DB1->Consulta_ID);

$sqlhvcli="SELECT hoj_nom_id  FROM `hojadevidacliente`  WHERE  hoj_clientecredito='$datoscliente[15]' ";		
$DB1->Execute($sqlhvcli);
$datohvc=mysqli_fetch_row($DB1->Consulta_ID);


?>
 <script>

buscar('param2');
timer =0;
function testtimeout(nombres){
clearTimeout(timer);
timer =setTimeout(buscar(nombres),2000);
}


 function cambiar() {
	  if(document.getElementById("param16").value==''){
		 document.getElementById("param17").required=false; 
	  }
	 else {
		 document.getElementById("param17").required=true;
	 } 
	 
 }


 function buscar(nombre) {

 if(nombre=='param1'){
	var documento = $("input#param1").val();
	variable = documento.length; 
	var telefono = '<?php echo $datoscliente[3];?>';

	variable2 = telefono.length; 

	if (variable >= 4) {
		datos = {"vlores":documento,"tipo":"documento"};
		
			
		$.ajax({
				url: "buscarclientesotros.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				
				if (respuesta === null) {
					
					//console.log(JSON.stringify(respuesta));
					//document.getElementById("param1").value='';
					cambio_ajax2(1, 14, 'clientesdir', 'telefono', 1, '');
					
					document.getElementById("id_param1").value=0;
					document.getElementById("param2").value='';
					document.getElementById("param3").value='';
					document.getElementById("param4").value='';
					document.getElementById("param5").value='0';
					document.getElementById("param51").value='';
					document.getElementById("param19").value='0';
					document.getElementById("param20").value='';
					document.getElementById("param23").value='';
					document.getElementById("param6").value='';
					document.getElementById("0").checked = true;
					document.getElementById("id_param").value=0;
					document.getElementById("id_param1").value=0;
					document.getElementById("id_param2").value=0;
					
				}
				else {
					
					cambio_ajax2(documento, 14, 'clientesdir', 'documento', 1, respuesta.cli_nombre);
					
					//document.getElementById("param1").value=respuesta.cli_iddocumento;
					document.getElementById("param2").value=respuesta.cli_telefono;
					document.getElementById("param3").value=respuesta.cli_email;
					document.getElementById("param4").value=respuesta.cli_idciudad;
					var res = respuesta.cli_direccion.split("&");
					if (typeof res[4] === 'undefined') {
						res[4]='';
						
					}
					document.getElementById("param5").value=res[0];
					document.getElementById("param51").value=res[1];
					document.getElementById("param19").value=res[2];
					document.getElementById("param20").value=res[3];
					document.getElementById("param23").value=res[4];
					document.getElementById("param6").value=respuesta.cli_nombre;
					document.getElementById(respuesta.cli_clasificacion).checked = true;
					document.getElementById("id_param2").value=respuesta.idclientesdir;					
					document.getElementById("id_param").value=respuesta.idclientes;
					document.getElementById("id_param1").value=1;
					 //document.getElementById('id_del_input').checked  document.form1.param7[7].value
				}
			});
					
	}	
	
 } else  if(nombre=='param2'){
	 
/* 	 var telefono = $("input#param2").val();
	variable2 = telefono.length;  */

	var telefono = '<?php echo $datoscliente[3];?>';
	console.log(telefono);
	variable2 = telefono.length; 
	

	if (variable2 >= 7) {
		datos = {"vlores":telefono,"tipo":"telefono"};
		
		$.ajax({
				url: "buscarclientesotros.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				//alert('josee');
				if (respuesta === null) {
					
					if(document.getElementById("id_param1").value!=1){
					cambio_ajax2(1, 14, 'clientesdir', 'telefono', 1,'');
					}
					document.getElementById("id_param").value=0;
					//alert('mm');
					//document.getElementById("param2").value='';
					//document.getElementById("param1").value='';
					document.getElementById("param3").value='';
					document.getElementById("param6").value='';
					document.getElementById("param4").value='';
					document.getElementById("param5").value=0;
					document.getElementById("param51").value='';
					document.getElementById("0").checked = true;
					//document.getElementById("param7").checked = false; 
					document.getElementById("param19").value=0; 
					document.getElementById("param20").value=''; 
					document.getElementById("param23").value=''; 
					
					document.getElementById("id_param1").value=0;
					document.getElementById("id_param2").value=0;
				}
				else {

					cambio_ajax2(documento, 14, 'clientesdir', 'telefono', 1, respuesta.cli_nombre);
					
					document.getElementById("param3").value=respuesta.cli_email;
					document.getElementById("param4").value=respuesta.cli_idciudad;
					var res = respuesta.cli_direccion.split("&");
					if (typeof res[4] === 'undefined') {
						res[4]='';
						
					}
					//alert(res[0]);
					document.getElementById("param5").value=res[0];
					document.getElementById("param51").value=res[1];
					document.getElementById("param19").value=res[2];
					document.getElementById("param20").value=res[3];
					document.getElementById("param23").value=res[4];
					document.getElementById("id_param").value=respuesta.idclientes;
					document.getElementById("id_param2").value=respuesta.idclientesdir;	
					document.getElementById("param2").value=telefono;			
					if(respuesta.cli_clasificacion!=null){
						//document. getElementById(respuesta.cli_clasificacion).checked = true;
					}
				
					
				
					
				}
			});
					
	}
 } else  if(nombre=='param6'){	
 
		var idclinte = document.getElementById("param61").value;

		datos = {"vlores":idclinte,"tipo":"cliente"};
	
		$.ajax({
				url: "buscarclientesotros.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				
					
					
					var res = respuesta.cli_direccion.split("&");
					if (typeof res[4] === 'undefined') { res[4]=''; }
					document.getElementById("param4").value=respuesta.cli_idciudad;
					document.getElementById("param5").value=res[0];
					document.getElementById("param51").value=res[1];
					document.getElementById("param19").value=res[2];
					document.getElementById("param20").value=res[3];
					document.getElementById("param23").value=res[4];
					document.getElementById("param6").value=respuesta.cli_nombre;
					document.getElementById("id_param2").value=respuesta.idclientesdir;
	
				
			});
  
 } else  if(nombre=='param8'){
	 
	 var telefono = $("input#param8").val();
	variable2 = telefono.length; 
	

	if (variable2 >= 7) {
		datos = {"vlores":telefono,"tipo":"telefono"};
		
		$.ajax({
				url: "buscarclientesotros.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				
				if (respuesta === null) {
	
					document. getElementById("param9").value='';
					document. getElementById("param10").value=0;
					document. getElementById("param101").value='';
					document. getElementById("param21").value=0;
					document. getElementById("param22").value='';
					document. getElementById("param24").value='';
					document. getElementById("param11").value='';
					
					}
				else {
					document.getElementById("param9").value=respuesta.cli_nombre;
					
					var res = respuesta.cli_direccion.split("&");
					if (typeof res[4] === 'undefined') {
						res[4]='';
						
					}
					document.getElementById("param10").value=res[0];
					document.getElementById("param101").value=res[1];
					document.getElementById("param21").value=res[2];
					document.getElementById("param22").value=res[3];
					document.getElementById("param24").value=res[4];
					document.getElementById("param11").value=respuesta.cli_idciudad;
					//document.getElementById("id_param2").value=respuesta.idclientesdir;
					
				}
			});
					
	}
	 
 }else  if(nombre=='param9'){	

var idclinte = document.getElementById("param71").value;
datos = {"vlores":idclinte,"tipo":"cliente"};
$.ajax({

		url: "buscarclientesotros.php",
		type: "POST",
		data: datos
	}).done(function(respuesta){

					cambio_ajax2(documento, 19, 'clientesdir2', 'documento', 1, respuesta.cli_nombre);
					var res = respuesta.cli_direccion.split("&");
					if (typeof res[4] === 'undefined') { res[4]=''; }
					document. getElementById("param10").value=res[0];
					document. getElementById("param101").value=res[1];
					document. getElementById("param21").value=res[2];
					document. getElementById("param22").value=res[3];
					document. getElementById("param24").value=res[4];
					document. getElementById("param11").value=respuesta.cli_idciudad;
					//document. getElementById("param11").value='';

					document. getElementById("id_param0").value=respuesta.idclientesdir;
				
	});

} 
 else  if(nombre=='param7'){

	var documento = $("input#param7").val();
	variable = documento.length; 
	var telefono = $("input#param8").val();
	variable2 = telefono.length; 

	if (variable >= 7) {
		datos = {"vlores":documento,"tipo":"documento"};

	 $.ajax({

			 url: "buscarclientesotros.php",
			 type: "POST",
			 data: datos

		 }).done(function(respuesta){

			 if (respuesta === null) {
				//cambio_ajax2(1, 19, 'clientesdir2', 'telefono', 1, '');
				 document. getElementById("param9").value='';
				 document. getElementById("param10").value=0;
				 document. getElementById("param101").value='';
				 document. getElementById("param21").value=0;
				 document. getElementById("param22").value='';
				 document. getElementById("param24").value='';
				 document. getElementById("param11").value='';
				 document. getElementById("id_param0").value=0;
			 }
			 else {
				cambio_ajax2(documento, 19, 'clientesdir2', 'documento', 1, respuesta.cli_nombre);
				document. getElementById("param8").value=respuesta.cli_telefono;
				 document. getElementById("param9").value=respuesta.cli_nombre;
				 var res = respuesta.cli_direccion.split("&");
				 if (typeof res[4] === 'undefined') { res[4]=''; }
				 document. getElementById("param10").value=res[0];
				 document. getElementById("param101").value=res[1];
				 document. getElementById("param21").value=res[2];
				 document. getElementById("param22").value=res[3];
				 document. getElementById("param24").value=res[4];
				 document. getElementById("param11").value=respuesta.cli_idciudad;
				 document. getElementById("id_param0").value=respuesta.idclientesdir;
			 }

		 });

 }

}
				
}; 

</script>

<?php 

	
$tcampod=117;
$tcampot=120;	

@$param4=$rw[4];
if($nivel_acceso!=1){  $cond6=" WHERE inner_sedes='$id_sedes'"; }  else { $cond6=""; }
//$FB->abre_form("form1","recoleccionok.php","post");
echo '<form action="recoleccionclientesok.php" method="post" enctype="multipart/form-data" name="form1" id="form1" onsubmit="return validar_repuesta();" >';



$FB->titulo_azul1("Remitente / Lugar de Recogida",10,0, 5);  

$FB->llena_texto("CC / Nit:",1, $tcampod, $DB, "", "", $rw[1], 1, 2);
$FB->llena_texto("Tel&eacute;fonos :",2, $tcampot, $DB, "", "", $rw[2], 4, 2);
//$FB->llena_texto("Nombre Del Cliente:", 6, 1, $DB, "", "", $rw[6], 1, 0);

	echo  "<tr bgcolor='#F3F3F3' ><td>Cliente:</td><td colspan=1><div id='clientesdir'>";
	echo " <input name='param6' id='param6' class='trans'  type='text' value='$rw[6]' >
	</div></td>";
	

$FB->llena_texto("Ciudad:",4,2,$DB,"(SELECT `idciudades`,`ciu_nombre` FROM `ciudades` )", "", "$param4", 4, 1);
//$FB->llena_texto("Direccion:",5, 1, $DB, "", "", $rw[5], 1, 0);

@$direcc=explode("&",$rw[5]);
@$param5=$direcc[0];
@$param51=$direcc[1];
@$param19=$direcc[2];
@$param20=$direcc[3];
@$param23=$direcc[4];

	echo "<tr bgcolor='#FFFFFF' ><td>Lugar de Recogida:</td>
	<td align='left' ><select class='trans'  name='param5' id='param5' >";
	echo "<option  value=''>Seleccione...</option>";
    $sql="SELECT `iddireccion`, `dir_nombre` FROM `direccion` ";
    $LT->llenaselect($sql,1,1, $param5, $DB);
    echo "</select>
	<input name='param51' id='param51' class='trans'  type='text' value='$param51' onkeypress='return noenter();'>
	</td>";

	echo "<td></td>
	<td align='left' ><select class='trans'  name='param19' id='param19' >";
	echo "<option  value='0'>Seleccione...</option>";
    $sql="SELECT `idlugar`, `lug_nombre` FROM `lugar`  ";
    $LT->llenaselect($sql,1,1, $param19, $DB);
    echo "</select>
	<input name='param20' id='param20' class='trans'  type='text' value='$param20' onkeypress='return noenter();'>
	</td>
	
	</tr>";

$FB->llena_texto("Barrio:", 23, 1, $DB, "", "", $param23, 17, 0);	
$FB->llena_texto("Hora de Recogida:", 14, 1, $DB, "", "", $rw[14], 4, 0);
$FB->llena_texto("Email:", 3, 111, $DB, "", "", $rw[3], 1, 0);	
//$FB->llena_texto("&iquest;Credito?:", 7, 212, $DB, "", "3",$rw[7], 4, 2);	
echo  "<td>Credito:</td><td>$datoscliente[14]</td></tr>";


$FB->titulo_azul1("Destinatario",9,0,5); 

$FB->llena_texto("CC / Nit:",7, $tcampod, $DB, "", "", $rw[7], 1, 0);
$FB->llena_texto("Tel&eacute;fono De Contacto:",8, $tcampot, $DB, "", "", $rw[8], 4, 1);
echo  "<tr bgcolor='#F3F3F3' ><td>Nombre:</td><td colspan=1><div id='clientesdir2'>";
echo ' <select class="trans"  name="param71"  id="param71" ><option  value=0>--Clientes--</option></select>';
echo " <input name='param9' id='param9' class='trans'  type='text' value='$rw[9]' onkeypress='return noenter();'>
</div></td>";

$FB->llena_texto("Ciudad:",11,2,$DB,"(SELECT `idciudades`,`ciu_nombre` FROM `ciudades`  where inner_estados=1)", "", "$rw[11]", 4, 1);

//$FB->llena_texto("Direccion del Contacto:",10, 1, $DB, "", "", $rw[10], 4, 1);

@$direcc2=explode("&",$rw[10]);
@$param10=$direcc2[0];
@$param101=$direcc2[1];
@$param21=$direcc2[2];
@$param22=$direcc2[3];
@$param24=$direcc2[4];

	echo "<tr bgcolor='#FFFFFF' ><td>Direcci&oacute;n del Contacto:</td>
	<td align='left' ><select class='trans'  name='param10' id='param10' >";
	echo "<option  value=''>Seleccione...</option>";
    $sql="SELECT `iddireccion`, `dir_nombre` FROM `direccion` ";
    $LT->llenaselect($sql,1,1, $param10, $DB);

    echo "</select>
	<input name='param101' id='param101' class='trans'  type='text' value='$param101' onkeypress='return noenter();'>
	</td>";

	echo "<td>Lugar de Entrega:</td>
	<td align='left' ><select class='trans'  name='param21' id='param21' >";
	echo "<option  value='0'>Seleccione...</option>";
    $sql="SELECT `idlugar`, `lug_nombre` FROM `lugar`  ";
    $LT->llenaselect($sql,1,1, $param21, $DB);
    echo "</select>
	<input name='param22' id='param22' class='trans'  type='text' value='$param22' onkeypress='return noenter();'>
	</td></tr>
	";

$FB->llena_texto("Barrio:", 24, 1, $DB, "", "", $param24, 1, 0);	


$FB->titulo_azul1("Servicio",9,0,5); 

$FB->llena_texto("Tipo de paquete:",12,82, $DB, $paquete, "",@$rw[12], 1, 0);
$FB->llena_texto("Dice contener:",13, 1, $DB, "", "", $rw[13], 4, 0);
//$FB->llena_texto("Servicio:",15,82, $DB, $Servicio, "",@$rw[15],1, 1);
$FB->llena_texto("# Piezas:",30, 123, $DB, "", "", "", 1, 'min=1');
//$FB->llena_texto("Valor de Prestamo:",16, 118, $DB, "", "", $rw[16],17, 0);


if(@$rw[18]==''){
	$sql12="SELECT seg_nombre FROM `seguro`  order by idseguro desc limit 1";
	$DB1->Execute($sql12);
	$porcentaje=mysqli_fetch_array($DB1->Consulta_ID);
	$seguro=$porcentaje['0'];
} else{
	$seguro=$rw[18];
}

$FB->llena_texto("Seguro:",18, 126, $DB, "", "$seguro", $seguro,4, 0);
if ($datohvc[0]=="") {
$Identificador="Id <mark>Cambie el nombre de este campo en hojas de vida cliente</mark>";	
}else {
	$Identificador=$datohvc[0];	
}
$esCroydonFormulario = (strtoupper(trim($datoscliente[14])) === "CROYDON");
if ($esCroydonFormulario) {
	echo "<tr bgcolor='#FFFFFF' class='text'><td>$Identificador</td><td align='right' class='text'>";
	echo "<div style='display:flex; gap:8px; align-items:center;'>";
	echo "<select id='param45_prefijo' class='form-control' style='max-width:140px;'>
		<option value=''>Siglas...</option>
		<option value='S1'>S1</option>
		<option value='OL'>OL</option>
		<option value='S8'>S8</option>
		<option value='SK'>SK</option>
	</select>";
	echo "<input id='param45_codigo' class='form-control' type='text' value='' placeholder='Codigo' autocomplete='off' onkeypress='return noenter();'>";
	echo "</div>";
	echo "<input name='param45' id='param45' type='hidden' value=''>";
	echo "</td></tr>";
} else {
	$FB->llena_texto("$Identificador",45, 1, $DB, "", "", "", 1, 0);
}
$valortservicio=0;
if($id_param!=''){
	 $sql21="select gui_tiposervicio from guias where gui_idservicio=$id_param";
	$DB->Execute($sql21);
	$valortservicio=$DB->recogedato(0);

	 $sql5="SELECT `rel_nom_credito` FROM `rel_sercre` WHERE `idservicio`=$id_param";
			$DB->Execute($sql5);
			$nombrecredito=$DB->recogedato(0);
}
/* $FB->llena_texto("&iquest;Servicio con Retorno?:", 25, 212, $DB, "", "3","$rw[20]",17, 0);	
$FB->llena_texto("Tipo de Servicio:",27,279, $DB, "(SELECT `idtiposervicio`,`tip_nom` FROM `tiposervicio`)", "","$valortservicio", 4, 2); */
if($rw[24]=='' or $rw[24]==NULL or $rw[24]==0){ $creditos=3; } else { $creditos=1; }
// $FB->titulo_azul1("Datos quien recibe",14,0, 5); 
// // $FB->llena_texto("Foto :",87, 6, $DB, "", "","", 1, 1);
// echo"<tr class='text'><td><label>Foto (*)</label></td><td><input type='file' accept='image/*' id='param91' name='param91' required  capture='environment'></td></tr>";

// $FB->llena_texto("Nombre:",92, 1, $DB, "", "","", 1, 1);
// $FB->llena_texto("Telefono Whatsapp:",93, 1, $DB, "", "","", 1, 1);
echo '</table></div></td></tr>';

echo$rw[0];
$FB->llena_texto("param15", 1, 13, $DB, "", "", "Recogida", 5, 0); 
$FB->llena_texto("id_param", 1, 13, $DB, "", "", "$rw[0]", 5, 0); //idclientes
$FB->llena_texto("id_param0", 1, 13, $DB, "", "", "$rw[19]", 5, 0); //idservicio
$FB->llena_texto("id_param1", 1, 13, $DB, "", "", "0", 5, 0);
$FB->llena_texto("id_param2", 1, 13, $DB, "", "", "$rw[21]", 5, 0); //idclientesdir

$FB->llena_texto("param41", 1, 13, $DB, "", "", "$datoscliente[2]", 5, 0); //documento
$FB->llena_texto("param42", 1, 13, $DB, "", "", "$datoscliente[3]", 5, 0); //telefono
$FB->llena_texto("param43", 1, 13, $DB, "", "", "$datoscliente[7]", 5, 0); //ciudad
$FB->llena_texto("param44", 1, 13, $DB, "", "", "$datoscliente[14]", 5, 0); //credito

$FB->llena_texto("", 1, 142, $DB, "Guardar", "", 0, 12, 0);
?> 
<script>

function validar_repuesta()
{
	var campoIdentificador = document.getElementById("param45");
	var campoSiglasCroydon = document.getElementById("param45_prefijo");
	var campoCodigoCroydon = document.getElementById("param45_codigo");
	var nombreCredito = document.getElementById("param44");
	var esCroydon = nombreCredito && nombreCredito.value.trim().toUpperCase() === "CROYDON";

	if (esCroydon) {
		sincronizarIdentificadorCroydon();
		var valorIdentificador = campoIdentificador ? campoIdentificador.value.trim() : "";
		var valorSiglasCroydon = campoSiglasCroydon ? campoSiglasCroydon.value.trim() : "";
		var valorCodigoCroydon = campoCodigoCroydon ? campoCodigoCroydon.value.trim() : "";

		if (valorSiglasCroydon === "" || valorCodigoCroydon === "") {
			alert("Para CROYDON debes diligenciar las siglas y el codigo.");
			if (campoSiglasCroydon && valorSiglasCroydon === "") {
				campoSiglasCroydon.focus();
			} else if (campoCodigoCroydon) {
				campoCodigoCroydon.focus();
			}
			return false;
		}

		if (valorIdentificador === "") {
			alert("No fue posible armar el identificador para CROYDON.");
			if (campoCodigoCroydon) {
				campoCodigoCroydon.focus();
			}
			return false;
		}
	}

	var carga=0;
	var credito=document.getElementsByName("param28");
	var memory=credito[0].checked;
	//alert(credito);
	if(memory==true){
		var nomcredito=document.getElementById("param113").value;
		var tipocredito=document.getElementById("param27").value;
		var ciudadori=document.getElementById("param4").value;
		var ciudaddes=document.getElementById("param11").value;

		datos = {"tipoguia":"validaprecios","nomcredito":nomcredito,"tipocredito":tipocredito,"ciudadori":ciudadori,"ciudaddes":ciudaddes};
		$.ajax({
			url: "guiasok.php",
			type: "POST",
			data: datos
			}).done(function(respuesta){
				
				if(respuesta==2){
					alert("No Tiene Precios de Creditos  configurados para estas opciones.");
					// my_callback(1); 
				}else{
					var submitFormFunction = Object.getPrototypeOf(form1).submit;
					submitFormFunction.call(form1);
					}
		});
		

	}else{
			var submitFormFunction = Object.getPrototypeOf(form1).submit;
			submitFormFunction.call(form1);
	}
	return false;

 }

function actualizarValidacionIdentificadorCroydon() {
	var campoIdentificador = document.getElementById("param45");
	var campoSiglasCroydon = document.getElementById("param45_prefijo");
	var campoCodigoCroydon = document.getElementById("param45_codigo");
	var nombreCredito = document.getElementById("param44");

	if (!nombreCredito) {
		return;
	}

	var esCroydon = nombreCredito.value.trim().toUpperCase() === "CROYDON";
	if (campoIdentificador) {
		campoIdentificador.required = esCroydon;
	}
	if (campoSiglasCroydon) {
		campoSiglasCroydon.required = esCroydon;
	}
	if (campoCodigoCroydon) {
		campoCodigoCroydon.required = esCroydon;
	}
 }
 
 function sincronizarIdentificadorCroydon() {
	var campoIdentificador = document.getElementById("param45");
	var campoSiglas = document.getElementById("param45_prefijo");
	var campoCodigo = document.getElementById("param45_codigo");

	if (!campoIdentificador || !campoSiglas || !campoCodigo) {
		return;
	}

	var equivalencias = {
		"10": "S1",
		"15": "OL",
		"40": "S8",
		"60": "SK"
	};

	var codigoLimpio = campoCodigo.value.replace(/\s+/g, " ").trimStart();
	var codigoSoloDigitos = codigoLimpio.replace(/\D+/g, "");
	var prefijoDetectado = equivalencias[codigoSoloDigitos.substring(0, 2)];

	campoCodigo.value = codigoLimpio;

	if (prefijoDetectado) {
		campoSiglas.value = prefijoDetectado;
	}

	if (campoSiglas.value !== "" && codigoLimpio !== "") {
		campoIdentificador.value = campoSiglas.value + " " + codigoLimpio;
	} else {
		campoIdentificador.value = "";
	}
 }

 function inicializarIdentificadorCroydon() {
	var campoSiglas = document.getElementById("param45_prefijo");
	var campoCodigo = document.getElementById("param45_codigo");

	if (!campoSiglas || !campoCodigo) {
		return;
	}

	campoCodigo.addEventListener("input", sincronizarIdentificadorCroydon);
	campoSiglas.addEventListener("change", sincronizarIdentificadorCroydon);
	sincronizarIdentificadorCroydon();
 }

 document.addEventListener("DOMContentLoaded", function () {
	inicializarIdentificadorCroydon();
	actualizarValidacionIdentificadorCroydon();
 });
 
</script>
