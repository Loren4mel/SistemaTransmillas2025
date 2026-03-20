<style>
        .container-left {
            float: left;
            margin-right: 10px; /* Espacio entre botones */
        }

        .container-right {
            float: right;
            margin-left: 10px; /* Espacio entre botones */
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
</style>
<?php 
require("login_autentica.php"); 
include("layout.php");

//include("cabezote4.php"); 
$fechaactual=date("Y-m-d");
if($nivel_acceso==1 or $nivel_acceso==12){
	if($param35!=''){  
		 $conde2=""; 
		
	 }  else {
		 $param35=$id_sedes;
	 }
}
else {	
	$param35=$id_sedes;
	  $conde2.=" and idsedes='$id_sedes' "; 		
}
?>
<head>

	</head>
<body onLoad="llena_datos(0,<?php echo $nivel_acceso;?> , '', 'ASC'); 
 cambio_ajax2(<?php echo $param35;?>, 16, 'llega_sub1', 'param33', 1, <?php echo $param33;?>);
">
<script>


timer2 =0;
function llena_datos(ex, nivel, ordby, asc)
{
//	p1=document.getElementById('param31').value;
	p1=0;
	p2=document.getElementById('param32').value;
	p3=document.getElementById('param33').value;
	p4=document.getElementById('param34').value;
	p5=document.getElementById('param35').value;
	//p7=document.getElementById('param37').value;
	p7=0;
	var pagina=0; 
	if(ordby=="undefined"){ ordby=""; }
	if(asc=="undefined" || asc=="" ){ asc="ASC"; }
	if(ex==1){
		destino="detalle_reporoperxl.php?p1="+p1+"&p2="+p2+"&p4="+p4;
		location.href=destino;
	}
	else {
		destino="detalle_reporoper.php?param31="+p1+"&param32="+p2+"&param33="+p3+"&param34="+p4+"&param35="+p5+"&param37="+p7+"&pagina="+pagina+"&ordby="+ordby+"&asc="+asc;
		MostrarConsulta4(destino, "destino_vesr")
	}
	clearTimeout(timer2);
	timer2=setTimeout(function(){llena_datos(0,nivel,'','ASC')},600000); // 3000ms = 3s
}



</script>
<?php 

//echo $_SESSION['usuario_rol'];
$FB->abre_form("form1","","post");
$FB->titulo_azul1("Seguimiento Operadores",9,0,5);  
$FB->abre_form("form1","","post");



$FB->llena_texto("Fecha de Inicial:", 34, 10, $DB, "", "", "$fechaactual", 1, 0);
if($nivel_acceso==1 or $nivel_acceso==12){
$FB->llena_texto("Fecha de Final:", 32, 10, $DB, "", "", "$fechaactual", 4, 0);
}else{
	$FB->llena_texto("param32", 1, 13, $DB, "", "", "0", 5, 0);

}
$FB->llena_texto("Sede :",35,2,$DB,"(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 $conde2 )", "cambio_ajax2(this.value, 16, \"llega_sub1\", \"param33\", 1, 0)", "$param35",1, 0);
$FB->llena_texto("Operario:", 33, 444, $DB, "llega_sub1", "", "",4,0);
//$FB->llena_texto("Estado:",37,82,$DB,$estadosguias,"","$param37",4,0); 
//echo "<td><button type='button' class='btn btn-primary btn-lg' onclick='buscarsede(4);'>Imprimir Codigos</button></td>";
echo "<tr>";
echo "<td><button type='button' class='whatsapp-button' onclick='enviarids(\"$id_p\",\"Enviar Whatsapp Operadores\")' >Mensaje Whatsapp</button></td><tr>";    

$FB->llena_texto("", 37, 277, $DB, "", "", "llena_datos(0, $nivel_acceso, \"id_nombre\", \"ASC\");",4,0);
$FB->div_valores("destino_vesr",12); 

$FB->cierra_form(); 

include("footer.php");
?>
<script>
	 let idsSeleccionados = [];
	function enviarids(id_param,tabla) {
	let arrayParam = encodeURIComponent(JSON.stringify(idsSeleccionados));
	$("#myModal").modal("show");
	var destino=`detalle_pop.php?ide=${arrayParam}&id_param=${encodeURIComponent(id_param)}&tabla=${encodeURIComponent(tabla)}`;
	MostrarConsulta(destino, "llena_sub1");
	}

	function selecionado(iduser) {
		var checkbox = document.getElementById(iduser+"s");
        // const checkbox = event.target;
        const id = iduser;

        if (checkbox.checked) {
            // Si el checkbox está marcado, agregar el ID al array
            idsSeleccionados.push(id);
        } else {
            // Si el checkbox está desmarcado, eliminar el ID del array
            const index = idsSeleccionados.indexOf(id);
            if (index !== -1) {
                idsSeleccionados.splice(index, 1);
            }
        }

        console.log("IDs seleccionados:", idsSeleccionados);
    }
	function sendWhatsapp(fileNames) {
		const loadingElement = document.getElementById('loading');
		// Mostrar el GIF de carga
		loadingElement.style.display = 'block';
		const mensaje = document.getElementById('chekWhatsapp').value;
    // Recorre cada elemento en fileNames y ejecuta una función para cada uno
    fileNames.forEach(function (service) {
        // Cada `service` contiene [idservicios, ser_telefonocontacto, ser_consecutivo, cli_telefono]
        const [id, contacto, consecutivo, telefono] = service;
        
        // Llama a una función o envía un mensaje por cada servicio
        // console.log(`Procesando servicio ID: ${id}, Contacto: ${contacto}, Consecutivo: ${consecutivo}, Teléfono: ${telefono}, Mensaje: ${mensaje}`);
        
		//  enviarAlertaWhat(consecutivo,contacto,mensaje,id);
		 enviarAlertaWhat(consecutivo,telefono,mensaje,id);
        // Aquí puedes ejecutar la función deseada para cada servicio
        // Por ejemplo, podrías enviar un mensaje por WhatsApp usando una API o una integración adicional
    });
	alert('Todos las alertas han sido enviadas');
    // Ocultar el GIF de carga
    loadingElement.style.display = 'none';
}

async function enviarAlertaWhat(numguia, telefono, tipo, idservi) {
    // URL de la API
    const url = "https://www.transmillas.com/ChatbotTransmillas/alertas.php";

    // Datos a enviar en la solicitud
    const data = {
        numero_guia: numguia, // Número de guía
        telefono: telefono,    // Número de teléfono
        tipo_alerta: tipo,     // Tipo de alerta
        id_guia: idservi       // ID de la guía
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



</script>