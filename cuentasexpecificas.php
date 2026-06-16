<?php 
require("login_autentica.php"); 
include("layout.php");
//include("cabezote4.php"); 
?>
<head>
<style>
	.whatsapp-button {
		display: inline-flex;  /* se comporta en línea */
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
		margin-left: 10px; /* espacio con lo que esté antes */
	}

	.whatsapp-button:hover {
		background-color: #1ebe5d;
		transform: scale(1.05);
	}

	.prefactura-header {
		display: flex;
		align-items: center;
		gap: 10px;
		width: 100%;
		background: #074F91;
		border-radius: 4px;
		padding: 6px 8px 6px 12px;
	}

	.prefactura-toggle {
		flex: 0 0 auto;
		border: 0;
		background: transparent;
		color: #fff;
		font-weight: bold;
		text-align: left;
		cursor: pointer;
		padding: 5px 0;
	}

	.prefactura-title {
		display: inline-block;
		margin-right: 10px;
	}

	.prefactura-arrow-toggle {
		margin-left: auto;
		border: 0;
		background: transparent;
		color: #fff;
		cursor: pointer;
		padding: 5px 8px;
	}

	.prefactura-flecha {
		font-size: 16px;
		line-height: 18px;
	}

	.prefactura-action {
		white-space: nowrap;
	}

	.prefactura-filtros {
		background: #F3F3F3;
		border: 1px solid #d9d9d9;
		border-top: 0;
		padding: 12px;
	}

	.prefactura-filtros table {
		margin-bottom: 0;
		width: 100%;
	}

	.sedes-destino-field {
		background: #fff;
		border: 1px solid #d9e2ec;
		border-radius: 6px;
		padding: 10px;
	}

	.sedes-destino-select {
		margin-bottom: 8px;
	}

	.sedes-destino-meta {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 10px;
		color: #555;
		font-size: 12px;
		margin-bottom: 6px;
	}

	.sedes-destino-clear {
		border: 0;
		background: transparent;
		color: #074F91;
		cursor: pointer;
		font-weight: bold;
		padding: 0;
	}

	.sedes-destino-clear[disabled] {
		color: #999;
		cursor: default;
	}

	.sedes-destino-list {
		min-height: 38px;
		border: 1px dashed #c8d2dc;
		border-radius: 6px;
		background: #f8fafc;
		padding: 6px;
	}

	.sede-chip {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		margin: 4px;
		padding: 5px 8px 5px 10px;
		border: 1px solid #b9c9d8;
		border-radius: 16px;
		background: #eef6ff;
		color: #123;
		font-size: 13px;
	}

	.sede-chip button {
		border: 0;
		background: #dbeafe;
		color: #074F91;
		border-radius: 50%;
		cursor: pointer;
		font-weight: bold;
		line-height: 16px;
		width: 18px;
		height: 18px;
		padding: 0;
	}

	.sedes-destino-empty {
		display: inline-block;
		color: #777;
		font-size: 12px;
		padding: 5px;
	}

	.precio-transporte-button {
		margin-top: 5px;
	}

	.precio-transporte-modal .modal-dialog {
		width: 96%;
		max-width: 1180px;
	}

	.precio-transporte-frame {
		width: 100%;
		height: 650px;
		border: 0;
	}
</style>
	</head>
<body onLoad="llena_datos(0,<?php echo $nivel_acceso;?> , '', 'ASC');">
<script>
  let seleccionados = [];

timer2 =0;
function llena_datos(ex, nivel, ordby, asc)
{
	p1=document.getElementById('param31').value;
	p6=document.getElementById('param36').value;
	p2=document.getElementById('param32').value;
	p3=document.getElementById('param33').value;
	p7=0;
	p4=document.getElementById('param34').value;
	p5=document.getElementById('param35').value;
	p8=document.getElementById('param38').value;
	p9=document.getElementById('param40').value;
	p10=document.getElementById('param41').value;
	p11=document.getElementById('param42').value;
	p43=document.getElementById('param43').value;
	p44=document.getElementById('param44').value;
	p45=document.getElementById('param45').value;
	

	
	var pagina=0; 
	if(ordby=="undefined"){ ordby=""; }
	if(asc=="undefined" || asc=="" ){ asc="ASC"; }
	if(ex==1){

		destino="detalle_cuentasgexcel.php?param31="+p1+"&param32="+p2+"&param33="+p3+"&param34="+p4+"&param35="+p5+"&param36="+p6+"&param37="+p7+"&param38="+p8;
		location.href=destino;

	}else if(ex==2){

		destino="detalle_cuentasespecificas.php?param31="+p1+"&param32="+p2+"&param33="+p3+"&param34="+p4+"&param35="+p5+"&param36="+p6+"&param37="+p7+"&param38="+p8+"&param40="+p9+"&param41="+p10+"&accion=actualizar&param42="+p11+"&param43="+p43+"&param44="+p44+"&param45="+p45;
		MostrarConsulta4(destino, "destino_vesr")
	//	destino1="actualizarcampos.php?param31="+p1+"&param32="+p2+"&param33="+p3+"&param34="+p4+"&param35="+p5+"&param36="+p6+"&param37="+p7+"&param38="+p8+"&param40="+p9+"&param41="+p10+"&pagina="+pagina+"&ordby="+ordby+"&asc="+asc;

	}
	else {

			destino="detalle_cuentasespecificas.php?param31="+p1+"&param32="+p2+"&param33="+p3+"&param34="+p4+"&param35="+p5+"&param36="+p6+"&param37="+p7+"&param38="+seleccionados+"&param42="+p11+"&param43="+p43+"&param44="+p44+"&param45="+p45;
			MostrarConsulta4(destino, "destino_vesr")
	
	}
	clearTimeout(timer2);
	timer2=setTimeout(function(){llena_datos(0,nivel,'','ASC')},600000); // 3000ms = 3s
}

function toggleFiltrosPrefactura()
{
	var filtros = document.getElementById('filtros_prefactura');
	var flecha = document.getElementById('prefactura_flecha');
	var boton = document.getElementById('prefactura_toggle');

	if (!filtros || !flecha || !boton) {
		return;
	}

	if (filtros.style.display === 'none') {
		filtros.style.display = 'block';
		flecha.innerHTML = '&#9650;';
		boton.setAttribute('aria-expanded', 'true');
	} else {
		filtros.style.display = 'none';
		flecha.innerHTML = '&#9660;';
		boton.setAttribute('aria-expanded', 'false');
	}
}


</script>
<?php 

//echo $_SESSION['usuario_rol'];
$FB->abre_form("form1","","post");
$FB->titulo_azul1("Reporte Especifico Sedes",9,0,5);  
$FB->abre_form("form1","","post");


if($nivel_acceso==1 or $nivel_acceso==10 or $nivel_acceso==12){
	if($param35!=''){   $conde2=""; }  

}
else {	

	  $conde2.=" and idsedes='$id_sedes' "; 	
	
}
$estado['']='Seleccione...'; 
$estado['NoRecibe']='Sin recibir'; 

$transporte['']="Seleccione...";
$transporte['Bus']="Bus";
$transporte['Jurgon']="Jurgon";

$FB->llena_texto("Fecha Inicinio:", 34, 10, $DB, "", "", "$fechaactual", 1, 0);
$FB->llena_texto("Fecha Final:", 36, 10, $DB, "", "", "$fechaactual", 4, 0);
$FB->llena_texto("Sede :",35,2,$DB,"(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $conde2 )", "", "$param35",1, 0);
//$FB->llena_texto("Sede Destino:",38,2,$DB,"(SELECT  `idsedes`,`sed_nombre` FROM sedes)", "", "$param38", 4,0);
$sqlC="SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $conde2 ";
$DB1->Execute($sqlC); 

?>

<!-- Select con multiple -->
 <td><label>Sede destino</label></td>
 <td>
<div class="sedes-destino-field">
<select id="param38" name="param38" class="form-control sedes-destino-select">
  <option value="" disabled selected>-- Selecciona una opción --</option>
  <?php
  	while($rwC=mysqli_fetch_row($DB1->Consulta_ID))
	{
		echo'<option value="'.$rwC[0].'">'.$rwC[1].'</option>';
	}
  ?>

</select>
<div class="sedes-destino-meta">
	<span id="sedes_destino_count">0 sedes seleccionadas</span>
	<button type="button" id="limpiar_sedes_destino" class="sedes-destino-clear" onclick="limpiarSedesDestino();" disabled>Limpiar todo</button>
</div>
<div id="seleccionados" class="sedes-destino-list">
	<span id="sedes_destino_empty" class="sedes-destino-empty">Seleccione una o varias sedes destino.</span>
</div>
</div>
</td></tr>
<?php
echo "<input type='hidden' name='param33' id='param33' value=''>";
echo "<input type='hidden' name='param31' id='param31' value=''>";
echo "<input type='hidden' name='param32' id='param32' value=''>";
echo "<tr bgcolor='#FFFFFF' class='text'><td>Precios transporte:</td><td><button type='button' class='btn btn-primary precio-transporte-button' onclick='abrirModalPreciosTransporte();'><i class='fa fa-usd'></i> Precios transporte</button></td>";
$FB->llena_texto("Transportado en:",44,82,$DB,$transporte,"","$param44",4,0);


$facturado[0]="Seleccione...";
$facturado[2]="No facturado";
$facturado[1]="Facturado";

$valorM[0]="Seleccione...";
$valorM[1]="Minima";
$valorM[2]="Mayor a la minima";

$FB->llena_texto("Manifiesto:", 40, 1, $DB, "", "","$param40", 4,0);
$FB->llena_texto("Codigo:", 41, 1, $DB, "", "","$param41", 1,0);
$FB->llena_texto("Excel", 39, 150, $DB, "Exportar", "","llena_datos(1, $nivel_acceso, \"id_nombre\", \"ASC\");", 4, 0);
$FB->llena_texto( "Estado:", 42, 82, $DB, $estado, "","$param42", 1,0);

//filtros para prefactura 
$prefacturaAbierta = ($param43!='' or $param45!='');
$displayPrefactura = $prefacturaAbierta ? "block" : "none";
$ariaPrefactura = $prefacturaAbierta ? "true" : "false";
$flechaPrefactura = $prefacturaAbierta ? "&#9650;" : "&#9660;";

echo "<td></td><td></td></tr>";
echo "<tr><td colspan='12'>";
echo "<div class='prefactura-header'>";
echo "<button type='button' id='prefactura_toggle' class='prefactura-toggle' onclick='toggleFiltrosPrefactura();' aria-expanded='$ariaPrefactura' aria-controls='filtros_prefactura'><span class='prefactura-title'>Filtros para prefactura</span></button>";
echo "<button type='button' class='btn btn-success prefactura-action' onclick='enviarDatosPrefacturaSeleccionadas();'>Crear PRE-Factura</button>";
echo "<button type='button' class='prefactura-arrow-toggle' onclick='toggleFiltrosPrefactura();' aria-label='Plegar filtros de prefactura'><span id='prefactura_flecha' class='prefactura-flecha'>$flechaPrefactura</span></button>";
echo "</div>";
echo "<div id='filtros_prefactura' class='prefactura-filtros' style='display:$displayPrefactura;'>";
echo "<table class='table table-hover'><tr>";

echo "<td class='text'>Factura:</td><td align='right'><select name='param43' id='param43' class='form-control'>";
foreach($facturado as $valor => $texto){
	$selected = ((string)$param43 === (string)$valor) ? "selected" : "";
	echo "<option value='$valor' $selected>$texto</option>";
}
echo "</select></td>";

echo "<td class='text'>Valor :</td><td align='right'><select name='param45' id='param45' class='form-control'>";
foreach($valorM as $valor => $texto){
	$selected = ((string)$param45 === (string)$valor) ? "selected" : "";
	echo "<option value='$valor' $selected>$texto</option>";
}
echo "</select></td></tr></table>";
echo "</div>";
echo "</td></tr>";

$FB->llena_texto("", 37, 277, $DB, "", "", "llena_datos(0, $nivel_acceso, \"id_nombre\", \"ASC\");",1,0);
echo "<td><button type='button' class='btn btn-info' onclick='llena_datos(2, $nivel_acceso, \"id_nombre\", \"ASC\");'>Guardar</button><button type='button' onclick='pop_dis5(0,\"Notificacion_Whatsapp\")' class='whatsapp-button'>Whatsapp</button></td></tr>";

$FB->div_valores("destino_vesr",12); 

$FB->cierra_form(); 
?>
<div class="modal fade precio-transporte-modal" id="modal_precios_transporte" tabindex="-1" role="dialog" aria-labelledby="titulo_precios_transporte" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="titulo_precios_transporte"><i class="fa fa-truck"></i> Precios transporte por pieza</h4>
			</div>
			<div class="modal-body">
				<iframe id="frame_precios_transporte" class="precio-transporte-frame" title="Administracion de precios de transporte"></iframe>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
			</div>
		</div>
	</div>
</div>
<?php

include("footer.php");
?>
<script>
function abrirModalPreciosTransporte() {
	var frame = document.getElementById('frame_precios_transporte');
	if (frame && !frame.getAttribute('src')) {
		frame.setAttribute('src', 'nueva_plataforma/controller/PreciosTransporteController.php');
	}
	$('#modal_precios_transporte').modal('show');
}

function alertaGuias(){

alert("¡Hay guias sin pesar verifique1");


}
function seleccionarTodos() {
  const estado = document.getElementById('check_todos').checked;
  const checkboxes = document.querySelectorAll('.check_hijo');

  checkboxes.forEach(chk => {
    chk.checked = estado;

    // Forzar que se dispare el evento onchange original
    chk.dispatchEvent(new Event('change'));
  });
}
function obtenerGuiasSeleccionadas() {
	const guias = [];
	document.querySelectorAll('.check_hijo:checked').forEach(chk => {
		if (chk.value) {
			guias.push(chk.value);
		}
	});

	return guias;
}

function enviarDatosPrefacturaSeleccionadas() {
	const p4 = document.getElementById('param34').value;
	const p5 = document.getElementById('param36').value;
	const guias = obtenerGuiasSeleccionadas();
	const errores = [];

	if (!p4) errores.push("Fecha inicial");
	if (!p5) errores.push("Fecha final");
	if (guias.length === 0) errores.push("Debe seleccionar al menos una guia");

	if (errores.length > 0) {
		alert("Faltan los siguientes datos obligatorios:\n\n- " + errores.join("\n- "));
		return;
	}

	const formData = new FormData();
	formData.append('param4', p4);
	formData.append('param5', p5);
	formData.append('guias', guias.join(','));

	fetch('crear_prefactura.php', {
		method: 'POST',
		body: formData
	})
	.then(response => response.text())
	.then(result => {
		alert(result);
		llena_datos(0, <?php echo $nivel_acceso;?>, '', 'ASC');
	})
	.catch(error => {
		console.error('Error al enviar datos:', error);
		alert("Error al crear la pre-factura.");
	});
}
function bloquearTextoArea(area) {
    const fijo = " Transmillas le informa Queremos informarle que su 🚚encomienda #_____ se encuentra ";
    if (!area.value.startsWith(fijo)) {
        area.value = fijo;
    }
}
let datosusertabla = [];
	function selecionado1(id,telefono,consecutivo,telefonoRemi) {
			var checkbox = document.getElementById(id + "s1");
			var contrato = "Prestacion";
			const data = {
				id: id,
				telefono: telefono,
				consecutivo: consecutivo,
				telefonoRemi: telefonoRemi
			};

			if (checkbox.checked) {
				// Agregar el objeto con los parámetros al array
				datosusertabla.push(data);
			} else {
				// Buscar el objeto en el array y eliminarlo
				datosusertabla = datosusertabla.filter(item => item.id !== id);
			}

			console.log("Datos User tabla:", datosusertabla);
	}

	async function sendWhatsapp() {

		const mensaje = document.getElementById("mensajeExtra").value;
		const seleccion = document.getElementById("mensajeFijo").value;

		for (const { id, telefono, consecutivo, telefonoRemi } of datosusertabla) {

			console.log(`Procesando ID: ${id}, Teléfono: ${telefono}`);

			var telefonoPrueba = "3125215864";
			// 🟢 OPCIÓN 1 — Encomienda (NO valida Navidad)
			if (seleccion === "1") {

				enviarAlertaWhat(telefono, 37, consecutivo, mensaje);
				continue;
			}

			// 🎄 OPCIÓN 2 — MENSAJE NAVIDAD
			if (seleccion === "2") {

				// 🔍 Verificar destinatario
				const respDestino = await verificarMensaje37(telefonoPrueba);

				if (!respDestino.ya_enviado) {
					enviarAlertaWhat(
						telefonoPrueba,
						37,
						"Siempre sera nuestra prioridad, Le deseamos una feliz navidad🎄 y un prospero año nuevo✨, transmillas",
						"a su servicio"
					);
				} else {
					console.log(`🎄 Mensaje Navidad YA enviado a ${telefonoPrueba}`);
				}

				// 🔍 Verificar remitente
				const respRemi = await verificarMensaje37(telefonoPrueba);

				if (!respRemi.ya_enviado) {
					enviarAlertaWhat(
						telefonoPrueba,
						37,
						"Siempre sera nuestra prioridad, Le deseamos una feliz navidad🎄 y un prospero año nuevo✨, transmillas",
						"a su servicio"
					);
				} else {
					console.log(`🎄 Mensaje Navidad YA enviado a ${telefonoPrueba}`);
				}
			}
		}

		datosusertabla = [];
		alert('Proceso de envío finalizado');
	}

	async function enviarAlertaWhat( telefono, tipo, texto1,texto2) {
		// URL de la API
		const url = "https://www.transmillas.com/ChatbotTransmillas/alertas.php";

		// Datos a enviar en la solicitud
		const data = {
			texto1: texto1, // Número de guía
			telefono: telefono,    // Número de teléfono
			tipo_alerta: tipo,     // Tipo de alerta
			texto2: texto2       // ID de la guía
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
				// 	alert("Operación realizada con éxito");
				// }
		} catch (error) {
			// Manejar errores
			console.error("Error en la solicitud:", error);
		}
	}


  const select = document.getElementById("param38");
  const divSeleccionados = document.getElementById("seleccionados");
  const contadorSedesDestino = document.getElementById("sedes_destino_count");
  const vacioSedesDestino = document.getElementById("sedes_destino_empty");
  const limpiarSedesDestinoBtn = document.getElementById("limpiar_sedes_destino");

  if (select && select.options.length > 0) {
    select.options[0].text = "Agregar sede destino...";
  }

  select.addEventListener("change", function () {
    const valor = this.value;
    const texto = this.options[this.selectedIndex].text;

    // Verificar que no esté repetido
    if (!seleccionados.includes(valor)) {
      seleccionados.push(valor);
      mostrarSeleccionado(valor, texto);
    }

    // Volver a dejar el select en el placeholder
    this.value = "";
    console.log("Array actual:", seleccionados);
  });

  function mostrarSeleccionadoAnterior(valor, texto) {
    const item = document.createElement("div");
    item.setAttribute("data-id", valor);
    item.className = "sede-chip";

    item.innerHTML = `
      ${texto} <span style="cursor:pointer;color:red;font-weight:bold;">❌</span>
    `;

    // Evento para eliminar
    item.querySelector("span").addEventListener("click", function () {
      seleccionados = seleccionados.filter(v => v !== valor);
      item.remove();
      console.log("Array actualizado:", seleccionados);
    });

    divSeleccionados.appendChild(item);
  }

  function mostrarSeleccionado(valor, texto) {
    const item = document.createElement("div");
    item.setAttribute("data-id", valor);
    item.className = "sede-chip";

    const nombre = document.createElement("span");
    nombre.textContent = texto;

    const quitar = document.createElement("button");
    quitar.type = "button";
    quitar.textContent = "x";
    quitar.title = "Quitar sede";

    item.appendChild(nombre);
    item.appendChild(quitar);

    quitar.addEventListener("click", function () {
      seleccionados = seleccionados.filter(v => v !== valor);
      item.remove();
      actualizarVistaSedesDestino();
      console.log("Array actualizado:", seleccionados);
    });

    divSeleccionados.appendChild(item);
    actualizarVistaSedesDestino();
  }

  function actualizarVistaSedesDestino() {
    const total = seleccionados.length;

    if (contadorSedesDestino) {
      contadorSedesDestino.textContent = total === 1 ? "1 sede seleccionada" : total + " sedes seleccionadas";
    }

    if (vacioSedesDestino) {
      vacioSedesDestino.style.display = total === 0 ? "inline-block" : "none";
    }

    if (limpiarSedesDestinoBtn) {
      limpiarSedesDestinoBtn.disabled = total === 0;
    }
  }

  function limpiarSedesDestino() {
    seleccionados = [];
    document.querySelectorAll("#seleccionados .sede-chip").forEach(item => item.remove());
    actualizarVistaSedesDestino();
    console.log("Array actualizado:", seleccionados);
  }

  actualizarVistaSedesDestino();
	async function verificarMensaje37(telefono) {

		const formData = new FormData();
		formData.append('verificar_mensaje_37', true);
		formData.append('telefono', telefono);

		const response = await fetch('/nueva_plataforma/controller/WhatsappController.php', {
			method: 'POST',
			body: formData
		});

		return await response.json();
	}

</script>
