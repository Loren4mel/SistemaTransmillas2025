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
$funcion=$_GET["funcion"];
$tabla=$_GET["tabla"];
echo '<div class="container-left">';

echo '<button type="button" class="btn btn-danger btn-lg" onclick="window.location.href = \'manifiesto.php\';" >Volver</button>';

echo'</div>';
echo'<div class="container-right">';


if ($funcion=="Vehiculos" or $tabla=="Editar vehiculo") {
    echo "<button type='button' class='btn btn-danger btn-lg' onclick='pop_dis16(\"$id_p\",\"Agregar vehiculo\",\"$rw1[5]\")' >+Vehiculo</button>";

}elseif ($funcion=="Conductores" or $tabla=="Editar conductor") {
    echo "<button type='button' class='btn btn-danger btn-lg' onclick='pop_dis16(\"$id_p\",\"Agregar conductor\",\"$rw1[5]\")' >+Conductor</button>";

}elseif ($funcion=="viaje" or $tabla=="Editar viaje") {
echo "<button type='button' class='btn btn-danger btn-lg' onclick='pop_dis16(\"$id_p\",\"Agregar viaje\",\"$rw1[5]\")' >+Viaje</button>";
# code...
}
echo'</div>';

$FB->titulo_azul1("$funcion",9,0,5);  
$FB->abre_form("form1","manifiesto.php","post");

 $anioinc = 2020;
 $aniofin = date("Y");






if ($param6 !="" and $param6 !='0') {

	$cond6="and hoj_fechaingreso like '$param6%'";
	
}else{
	$cond6="";
}




// $FB->llena_texto("", 3, 142, $DB, "BUSCAR", "","", 1, 0);
$FB->cierra_form(); 

$conde1=""; 

  if($param2!="" and $param3!=""){ 
   $conde1="and $param3 like '%$param2%' "; 
	}else { $conde1="  "; } 


  if($param4!='0' and $param4!=''){
	  $cond5=" and hoj_estado='$param4'";
  }

  if($param1!='0' and $param1!=''){
	$cond3=" and hoj_tipocontrato='$param1'";
}




if(isset($_REQUEST["ordby"])){ $ordby=$_REQUEST["ordby"]; } else { $ordby="hoj_nombre,hoj_apellido"; } 
if(@$_REQUEST["asc"]!=""){ $asc=$_REQUEST["asc"]; } else {$asc="ASC"; } 	$asc2=""; if($asc=="ASC"){ $asc2="DESC";}



if ($funcion=="Vehiculos" or $tabla=="Editar vehiculo") {
    
$FB->titulo_azul1("Propietario ",1,0,7); 
$FB->titulo_azul1("Celular propietario",1,0,0); 
$FB->titulo_azul1("Placa Vehiculo",1,0,0); 
$FB->titulo_azul1("Numero poliza",1,0,0);
$FB->titulo_azul1("Poliza",1,0,0);
$FB->titulo_azul1("Tarjeta propiedad ",1,0,0); 
$FB->titulo_azul1("Foto ",1,0,0); 
$FB->titulo_azul1("Editar ",1,0,0); 
$FB->titulo_azul1("Eliminar ",1,0,0); 



    $sql="SELECT `vehimid`, `vehim_nombre_prop`, `vehim_num_cel_prop`, `vehim_placas`, `vehim_num_Poliza`, `vehim_foto_poli`, `vehim_foto_tarjeta`, `vehim_foto_vehiculo` FROM `vehiculo_manif` ORDER BY  vehimid asc ";

    $DB->Execute($sql); $va=(($compag-1)*$CantidadMostrar); 
        while($rw1=mysqli_fetch_row($DB->Consulta_ID))
        {
            $id_p=$rw1[0];
            $va++; $p=$va%2;
            if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
            echo "<td>".$rw1[1]."</td>";
            echo "<td>".$rw1[2]."</td>";
            echo "<td>".$rw1[3]."</td>";
            echo "<td>".$rw1[4]."</td>";
            echo "<td><a href='img_manifiestos/vehiculos/$rw1[5]' target='_blank'>ver</a></td>";
            echo "<td><a href='img_manifiestos/vehiculos/$rw1[6]' target='_blank'>ver</a></td>";
            echo "<td><a href='img_manifiestos/vehiculos/$rw1[7]' target='_blank'>ver</a></td>";
            echo "<td>	<a onclick='pop_dis16($id_p, \"Editar vehiculo\",\"$rw1[1]\")';  style='cursor: pointer;' title='editar' >Editar</td>";
        	if($nivel_acceso==1){
                $DB->edites($id_p, "vehiculos", 2, $condecion);
            }
    
    

        }
}elseif ($funcion=="Conductores" or $tabla=="Editar conductor") {
   
    echo "<tr>";
    echo "<td><button type='button' class='whatsapp-button' onclick='enviarids(\"$id_p\",\"Enviar Whatsapp Conductores\")' >Mensaje Whatsapp</button></td>";
    echo "<td colspan='11' style='text-align:left; padding-left:10px;'><label style='display:flex; align-items:center; gap:6px; margin:0;'><input type='checkbox' id='seleccionarTodosConductores' onchange='seleccionarTodosConductores(this)'>Seleccionar todos</label></td></tr>";    
$FB->titulo_azul1("Nombre",1,0,7); 
$FB->titulo_azul1("Celular",1,0,0); 
$FB->titulo_azul1("Whatsapp",1,0,0); 
$FB->titulo_azul1("Numero cedula",1,0,0);
$FB->titulo_azul1("Foto",1,0,0);
$FB->titulo_azul1("Numero licencia ",1,0,0); 
$FB->titulo_azul1("Foto  ",1,0,0); 
$FB->titulo_azul1("Foto conductor ",1,0,0); 
$FB->titulo_azul1("Firma",1,0,0); 
$FB->titulo_azul1("Antecedentes",1,0,0); 

$FB->titulo_azul1("Editar ",1,0,0); 
$FB->titulo_azul1("Eliminar ",1,0,0); 




    $sql="SELECT `condid`, `cond_nombre`, `cond_celular`, `cond_whatsapp`, `cond_cedula`, `cond_foto_celula`, `cond_num_licen`, `cond_foto_licen`, `cond_foto_conductor`, `cond_firma`,con_antec FROM `conductor_mani`  ORDER BY  condid asc ";

    $DB->Execute($sql); $va=(($compag-1)*$CantidadMostrar); 
        while($rw1=mysqli_fetch_row($DB->Consulta_ID))
        {
            $id_p=$rw1[0];
            $va++; $p=$va%2;
            if($p==0){$color="#FFFFFF";} else{$color="#EFEFEF";}
            echo "<tr class='text' bgcolor='$color' onmouseover='this.style.backgroundColor=\"#C8C6F9\"' onmouseout='this.style.backgroundColor=\"$color\"'>";
            echo "<td>".$rw1[1]."</td>";
            echo "<td>".$rw1[2]."</td>";
            echo "<td>".$rw1[3]."<input type='checkbox'  onchange='selecionado($id_p)' class='checkbox' id='".$id_p."s' value='$id_p'></td>";
		

            echo "<td>".$rw1[4]."</td>";
            echo "<td><a href='img_manifiestos/conductores/$rw1[5]' target='_blank'>ver</a></td>";
            echo "<td>".$rw1[6]."</td>";
            echo "<td><a href='img_manifiestos/conductores/$rw1[7]' target='_blank'>ver</a></td>";
            echo "<td> <a href='img_manifiestos/conductores/$rw1[8]' target='_blank'>ver</a></td>";
            echo "<td> <a href='img_manifiestos/conductores/$rw1[9]' target='_blank'>ver</a></td>";
            echo "<td> <a href='img_manifiestos/conductores/$rw1[10]' target='_blank'>ver</a></td>";

            echo "<td><a  onclick='pop_dis16($id_p, \"Editar conductor\",\"$rw1[1]\")';  style='cursor: pointer;' title='editar' >Editar</td>";
            if($nivel_acceso==1){
                $DB->edites($id_p, "conductores", 2, $condecion);
            }
        }

}








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
            if (!idsSeleccionados.includes(id)) {
                idsSeleccionados.push(id);
            }
        } else {
            // Si el checkbox está desmarcado, eliminar el ID del array
            const index = idsSeleccionados.indexOf(id);
            if (index !== -1) {
                idsSeleccionados.splice(index, 1);
            }
        }

        actualizarCheckboxGeneralConductores();
        console.log("IDs seleccionados:", idsSeleccionados);
    }
	function seleccionarTodosConductores(control) {
		var checkboxes = document.querySelectorAll('.checkbox');
		idsSeleccionados = [];

		checkboxes.forEach(function (checkbox) {
			checkbox.checked = control.checked;
			if (control.checked) {
				idsSeleccionados.push(parseInt(checkbox.value, 10));
			}
		});

		console.log("IDs seleccionados:", idsSeleccionados);
	}

	function actualizarCheckboxGeneralConductores() {
		var checkboxGeneral = document.getElementById('seleccionarTodosConductores');
		var checkboxes = document.querySelectorAll('.checkbox');

		if (!checkboxGeneral || checkboxes.length === 0) {
			return;
		}

		checkboxGeneral.checked = Array.from(checkboxes).every(function (checkbox) {
			return checkbox.checked;
		});
	}
	function sendWhatsapp(fileNames) {
		const loadingElement = document.getElementById('loading');
		// Mostrar el GIF de carga
		loadingElement.style.display = 'block';
		const mensaje = document.getElementById('chekWhatsapp').value;
		const origen = document.getElementById('ciudadDestinoWhatsapp').value;
		const destino = document.getElementById('ciudadOrigenWhatsapp').value;
         
        // Recorre cada elemento en fileNames y ejecuta una función para cada uno
        fileNames.forEach(function (service) {
        // Cada `service` contiene [idservicios, ser_telefonocontacto, ser_consecutivo, cli_telefono]
        const [id, contacto, consecutivo, telefono] = service;
        
        // Llama a una función o envía un mensaje por cada servicio
        // console.log(`Procesando servicio ID: ${id}, Contacto: ${contacto}, Consecutivo: ${consecutivo}, Teléfono: ${telefono}, Mensaje: ${mensaje}`);
        
		//  enviarAlertaWhat(consecutivo,contacto,mensaje,id);
		 enviarAlertaWhat(consecutivo,telefono,mensaje,id,origen,destino);
        // Aquí puedes ejecutar la función deseada para cada servicio
        // Por ejemplo, podrías enviar un mensaje por WhatsApp usando una API o una integración adicional
    });
	alert('Todos las alertas han sido enviadas');
    // Ocultar el GIF de carga
    loadingElement.style.display = 'none';
}

async function enviarAlertaWhat(numguia, telefono, tipo, idservi,origen,destino) {
    // URL de la API
    const url = "https://www.transmillas.com/ChatbotTransmillas/alertas.php";

    // Datos a enviar en la solicitud
    const data = {
        numero_guia: numguia, // Número de guía
        telefono: telefono,    // Número de teléfono
        tipo_alerta: tipo,     // Tipo de alerta
        id_guia: idservi,      // ID de la guía
        origen: origen,     // Tipo de alerta
        destino: destino
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
