
<?php 
require("login_autentica.php"); 
include("layout.php");
//include("cabezote4.php"); 

// function contarCorreosNoLeidos() {
//     $resultado = [
//         'ok' => false,
//         'total' => 0,
//         'error' => null
//     ];

//     $correo = 'facturaciontransmillas@gmail.com';
//     $password = 'qxlh uxsh ilgp xojp'; // contraseña de aplicación

//     if (!$password) {
//         $resultado['error'] = 'La variable MAIL_PASSWORD no existe o está vacía';
//         return $resultado;
//     }

//     $inbox = imap_open(
//         '{imap.gmail.com:993/imap/ssl}INBOX',
//         $correo,
//         $password
//     );

//     if (!$inbox) {
//         $resultado['error'] = imap_last_error();
//         return $resultado;
//     }

//     $emails = imap_search($inbox, 'UNSEEN');

//     if ($emails === false) {
//         $resultado['error'] = 'No se encontraron correos no leídos (o búsqueda fallida)';
//         imap_close($inbox);
//         return $resultado;
//     }

//     $resultado['ok'] = true;
//     $resultado['total'] = count($emails);

//     imap_close($inbox);
//     return $resultado;
// }

// $respuesta = "ok";

// if (!$respuesta['ok']) {
//     // SOLO PARA DEPURACIÓN
//     // echo "❌ Error IMAP: " . $respuesta['error'];
    $cantidad=0;

// } else {
//     // echo "📩 Correos no leídos: " . $respuesta['total'];
//     $cantidad=$respuesta['total'];
// }



$FB->titulo_azul1("Creditos",9,0,5);  
$FB->abre_form("form1","","post");
// $fechainicial=date("01/m/Y");
$fechados= date("d-m-Y",strtotime($fechaactual."- 2 week"));
?>
<link href="css/informecreditos.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<script>

function buscardatoscredito()
{

    // Obtener el elemento select por su ID
    var selectElement = document.getElementById('param3');

    // Obtener el valor seleccionado
    var valorSeleccionado = selectElement.value;
    if(valorSeleccionado=='' || valorSeleccionado=='0'){

        alert('Debe seleccionar un Credito');

    }else{
        
        // Llamar a la función con el valor seleccionado
        pop_dis16(valorSeleccionado, "datosdefactura", "");

    }
    

}

function crearfaactura()
{
	destino="crearfactura.php?metodo=crear";
	location.href=destino;

}

function editarfactura(datos)
{
	destino="crearfactura.php?"+datos;
	location.href=destino;

}
function desvincularfacturas()
{
	//p1=document.getElementById('param1').value;
	p2=document.getElementById('param2').value;
	p3=document.getElementById('param3').value;	
	p4=document.getElementById('param4').value;
	p5=document.getElementById('param5').value;
	p6=document.getElementById('param6').value;
	p1=document.getElementById('param7').value;
    
	alert("Esta seguro que desea desvincular las guias");

/* 	destino="detalle_creditos_desvincular.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6;
	MostrarConsulta4(destino, "destino_vesr"); */

 // Realizar la petición AJAX
 $.ajax({
      type: 'POST',
      url: 'detalle_creditos_desvincular.php',
      data: {
        param2: p2,
        param3: p3,
        param4: p4,
        param5: p5,
        param6: p6,
        param1: p1
      },
      success: function(response) {
        // Manejar la respuesta del servidor
        console.log(response);
		alert(response);
        // Puedes mostrar la respuesta en un elemento HTML
      //  $('#result').html(response);
      },
      error: function(xhr, status, error) {
        // Manejar errores de la petición AJAX
        console.error(error);
      }
    });

	destino="detalle_creditos.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6;
		MostrarConsulta4(destino, "destino_vesr");

}
function llena_datos(ex, nivel, ordby, asc)
{
	//p1=document.getElementById('param1').value;
	p2=document.getElementById('param2').value;
	p3=document.getElementById('param3').value;	
	p4=document.getElementById('param4').value;
	p5=document.getElementById('param5').value;
	p6=document.getElementById('param6').value;
	p1=document.getElementById('param7').value;
    p9=document.getElementById('param9').value;
    p10=document.getElementById('param10').value;
    p13=document.getElementById('param13').value;
	if(ex==3){
		if(p3=='' || p3==null){
			alert('Por favor Seleccione un Cliente');
			exit;
		}
	}
	
	var pagina=0; 
	if(ordby=="undefined"){ ordby=""; }
	if(asc=="undefined" || asc=="" ){ asc="ASC"; }
	if(ex==1){
		destino="creditos_excel.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&idfactura="+ordby+"&preguia="+ex;
		location.href=destino;
	}
	else if(ex==2){
		destino="detalle_facturascreditos.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&idfactura="+ordby+"&preguia="+ex+"&param9="+p9+"&param10="+p10;
		MostrarConsulta4(destino, "destino_vesr");
	}
	else if(ex==4){
		
		destino="creditos_excel.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&idfactura="+ordby+"&preguia="+ex;
		location.href=destino;
	}else if(ex==5){
		destino="pdfcredit.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&idfactura="+ordby+"&preguia="+ex;
		location.href=destino;

	}
    else if(ex==6){
		destino="pruebas_software.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&idfactura="+ordby+"&preguia="+ex+"&ver=si&prefac="+asc;
		// location.href=destino;
        window.open(destino, '_blank');

	}
    else if(ex==7){


        destino="detalle_facturascreditos.php?param1=Pre-Facturado&param2="+p2+"&param3="+p3+"&param4=2024-01-01&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&idfactura="+ordby+"&preguia="+ex+"&param9="+p9+"&param10="+p10;
		MostrarConsulta4(destino, "destino_vesr");
        
	}
	else {

		destino="detalle_creditos.php?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&param13="+p13+"&pagina="+pagina+"&idfactura="+ordby+"&preguia="+ex;
		MostrarConsulta4(destino, "destino_vesr");
	}
}

</script>
<?php 

echo "<tr>
    <td>
        <div class='form-group'>
            <label class='btn btn-success btn-file'>
                <i class='fa fa-paperclip'></i> Adjuntar Archivo
                <input type='file' name='excelFile' id='excelFile' accept='.xls, .xlsx' style='display: none;'>
            </label>
        </div>
        <button type='button' class='btn btn-primary' onclick=\"enviarFormulario('Davivienda')\">Cargar Davivienda</button>
        <button type='button' class='btn btn-success' onclick=\"enviarFormulario('Bancolombia')\">Cargar Bancolombia</button>
    </td>
    <td>
        <button type='button' class='btn btn-primary btn-with-bancolombia' onclick=\"abrirPopup('bancolombia.php','BANCOLOMBIA')\"></button>
    </td>
    <td>
        <button type='button' class='btn btn-primary btn-with-davivienda' onclick=\"abrirPopup('davivienda.php','DAVIVIENDA')\"></button>
    </td>
    <td><button type='button' class='btn btn-danger' onclick=\"location.href='nueva_plataforma/controller/CorreoFacturasController.php'\">Correo Factutracion";
     if ($cantidad > 0): 
        echo"<span class='badge'>$cantidad </span>";
     endif;
    echo"</button></td>";
echo"</tr>";

    // Fecha actual
    $hoy = new DateTime('now');
    $dia = (int)$hoy->format('d');

    if ($dia < 21) {
        // Usar mes anterior
        $fechaInicio = new DateTime('first day of last month');
        $fechaFin = new DateTime('last day of last month');
    } else {
        // Usar mes actual
        $fechaInicio = new DateTime('first day of this month');
        $fechaFin = new DateTime('last day of this month');
    }

    $inicio = $fechaInicio->format('Y-m-d');
    $fin = $fechaFin->format('Y-m-d');

  

	if($param4!=''){  $fechainicio=$param4;}
	if($param5!=''){  $fechaactual=$param5;}
	//echo $fechainicial;
	$FB->llena_texto("Fecha de Inicial:", 4, 10, $DB, "", "", "$fechainicio", 17, 0);
	$FB->llena_texto("Fecha de Final:", 5, 10, $DB, "", "", "$fechaactual", 4, 0);
	$FB->llena_texto("Cliente:",3, 280, $DB, "(SELECT `cre_nombre`,`cre_nombre` FROM `creditos` inner join `hojadevidacliente` on hoj_clientecredito =idcreditos where hoj_estado='Activo' )", "", "$param9",17,1);
	$FB->llena_texto("Estado Facturas:",7,82,$DB,$estadofac,"","",4,0);
	$FB->llena_texto("Estado Creditos:",6,82,$DB,$estadocreditos,"","",17,0);
	$FB->llena_texto("# Factura:", 2, 1, $DB, "", "","$param2", 4,0);
    $FB->llena_texto("# Pre-Factura:", 10, 1, $DB, "", "","$param2", 4,0);
	$FB->llena_texto("Cliente sin facturar:",13, 2, $DB, "(SELECT DISTINCT rs.rel_nom_credito,rs.rel_nom_credito  FROM servicios s INNER JOIN rel_sercli rsc ON s.idservicios = rsc.ser_idservicio INNER JOIN clientesservicios cs ON cs.idclientesdir = rsc.ser_idclientes INNER JOIN ciudades c ON c.idciudades = cs.cli_idciudad INNER JOIN rel_sercre rs ON rs.idservicio = s.idservicios WHERE DATE(s.ser_fecharegistro) BETWEEN '$inicio' AND '$fin' AND s.ser_clasificacion = 2 AND s.ser_estado >= 3 AND s.ser_estado != 100 AND (s.ser_numerofactura IS NULL ) ORDER BY rs.rel_nom_credito)", "", "$param9",17,1);
	$FB->llena_texto("# Nit:", 9, 1, $DB, "", "","$param2", 4,0);
		

echo '<td align="right">';
if($nivel_acceso==1){

    echo "<a href='#' onclick='buscardatoscredito()'>Ver informacion Credito </a>";
}
echo 'Exportar a :<a href="#" onclick="llena_datos(1, 1, &quot;id_nombre&quot;, &quot;ASC&quot;);" target=""><img src="img/excel.jpg" width="30"></a></td>';
echo '<td align="right">Exportar a pdf :<a href="#" onclick="llena_datos(5, 1, &quot;id_nombre&quot;, &quot;ASC&quot;);" target=""><img src="img/pdfimagen.png" width="30"></a></td>';
echo "<td align='right'><div id='loading' style='display: none;'>
<img src='img/loading_gif.gif' alt='Cargando...' />
</div>
<button type='button' class='email-button' onclick='enviarEmail(1, 1, &quot;id_nombre&quot;, &quot;ASC&quot;)' >
<i class='fas fa-envelope'></i>Email</button></td>";
echo "</tr>";

echo "<tr><td><button type='button' class='btn btn-info' onclick='llena_datos(0, $nivel_acceso, \"id_nombre\", \"ASC\");'>Consultas Creditos</button></td>";
if($nivel_acceso==1){
	echo "<td><button type='button' class='btn btn-danger' onclick='desvincularfacturas();'>Desvincular  Guias</button></td>";

}
echo "<td><button type='button' class='btn btn-warning' onclick='llena_datos(2, $nivel_acceso, \"id_nombre\", \"ASC\");'>Consultar Facturas</button></td>
<td><button type='button' class='btn btn-primary' onclick='crearfaactura();'>Crear Factura Externa</button>
<button type='button' class='btn btn-success' onclick='llena_datos(3, $nivel_acceso, \"id_nombre\", \"ASC\");'>Crear PRE-Factura</button></td></tr>";
$aumento=0;
$sqlalert="SELECT DISTINCT rs.rel_nom_credito FROM servicios s INNER JOIN rel_sercli rsc ON s.idservicios = rsc.ser_idservicio INNER JOIN clientesservicios cs ON cs.idclientesdir = rsc.ser_idclientes INNER JOIN ciudades c ON c.idciudades = cs.cli_idciudad INNER JOIN rel_sercre rs ON rs.idservicio = s.idservicios WHERE s.ser_fecharegistro BETWEEN '$inicio 00:00:00' AND '$fin 23:59:59' AND s.ser_clasificacion = 2 AND s.ser_estado >= 3 AND s.ser_estado != 100 AND (s.ser_numerofactura IS NULL ) ORDER BY rs.rel_nom_credito";
$DB1->Execute($sqlalert);
while ($rw1 = mysqli_fetch_row($DB1->Consulta_ID)) {
                $sinfactura.=" ".$rw1[0];
				$aumento++;
}
if ($aumento>0) {
        echo '<div style="display: flex; gap: 10px;">';
        echo '<div class="alert alert-danger" role="alert">
                ¡Hay '.$aumento.' Creditos Pendientes por facturar !
            </div>';
    }
$sqlPre="SELECT `idfacturascreditos` FROM `facturascreditos` WHERE date(fac_fechafactura)>='2024-01-01' and date(fac_fechafactura)<='$fin' and fac_estado='Pre-Facturado' ORDER BY fac_numeroref ASC";
$DB1->Execute($sqlPre);
while ($rw2 = mysqli_fetch_row($DB1->Consulta_ID)) {
                    $preFacturasN.=" ".$rw2[0];
                    $aumentoPre++;
}
    if ($aumentoPre>0) {
echo "<div class='alert alert-warning' role='alert' style='cursor:pointer;' onclick='llena_datos(7, $nivel_acceso, \"id_nombre\", \"ASC\");'>
        ¡Hay ".$aumentoPre." Pre facturas del 2024-01-01 a la fecha !
      </div>";
    }

echo '</div>';

$FB->div_valores("destino_vesr",12); 

$FB->cierra_form(); 


include("footer.php");
?>
<script>
let idsSeleccionados = [];

function enviarFormulario(direccion) {
    try {
        var formData = new FormData();
        var inputFile = document.getElementById('excelFile').files[0];

        if (inputFile) {
            formData.append('excelFile', inputFile);

            var xhr = new XMLHttpRequest();
            var url = (direccion === 'Davivienda') ? 'procesar_excel.php' : 'procesar_excel_bancolombia.php';
            xhr.open('POST', url, true);

            // Evento que se ejecutará cuando la solicitud sea completada
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Éxito: mostrar respuesta del servidor
                    alert(xhr.responseText);
                } else {
                    // Error: mostrar mensaje de error
                    alert('Error al procesar el archivo.' + xhr.status);
                }
            };

            // Enviar solo el archivo
            xhr.send(formData);
        } else {
            throw new Error('Por favor, seleccione un archivo antes de enviar.');
        }
    } catch (error) {
        alert('Excepción capturada: ' + error.message);
    }
}


function enviarFormulario2($direccion) {
    var formData = new FormData();
    var inputFile = document.getElementById('excelFile').files[0];

    if (inputFile) {
        formData.append('excelFile', inputFile);

        var xhr = new XMLHttpRequest();
		if($direccion=='Davivienda'){
			xhr.open('POST', 'procesar_excel.php', true);
		}else{
			xhr.open('POST', 'procesar_excel_bancolombia.php', true);
		}
       

        // Evento que se ejecutará cuando la solicitud sea completada
        xhr.onload = function () {
            if (xhr.status === 200) {
                // Éxito: mostrar respuesta del servidor
                alert(xhr.responseText);
            } else {
                // Error: mostrar mensaje de error
                alert('Error al procesar el archivo.'+xhr.status);
            }
        };

        // Enviar solo el archivo
        xhr.send(formData);
    } else {
        alert('Por favor, seleccione un archivo antes de enviar.');
    }
}



    function abrirPopupiframe(url,tipo) {

		// Abre una ventana emergente con un iframe
		var iframeUrl = 'datatable-master/'+url;
		var iframeHtml = '<iframe src="' + iframeUrl + '" frameborder="0" style="width:100%; height:100vh;"></iframe>';

		// Crea un modal de Bootstrap
		var modal = $('<div class="modal" style="width:90%" tabindex="-1" role="dialog"></div>');
		modal.html('<div class="modal-dialog" role="document" style="width:100%"><div class="modal-content" style="width:100%">' +
			'<div class="modal-header">' +
				'<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
					'<span aria-hidden="true">&times;</span>' +
				'</button>' +
				'<h4 class="modal-title">INFORME DE '+tipo+'</h4>' +
			'</div>' +
			'<div class="modal-body" style="width:100%">' + iframeHtml + '</div>' +
			'</div></div>');

		// Agregar el modal al cuerpo del documento
		$('body').append(modal);

		// Mostrar el modal
		modal.modal('show');

        // Maneja el evento de cierre del modal para eliminar el iframe del DOM al cerrar el modal
        modal.on('hidden.bs.modal', function () {
            modal.remove();
        });
    }


	function abrirPopup(url, tipo) {
    // Abre una nueva pestaña del navegador
    window.open('datatable-master/' + url, '_blank');
}

function sendEmail(idfac){


            const email = document.getElementById('param2');
            const body = document.getElementById('param5');
            const formData = new FormData();

            //agregar correo
            formData.append('correo', email.value);
            //agregar correo
            formData.append('body', body.value);
            formData.append('idfac', idfac);

            const loadingElement = document.getElementById('loading');

            // Mostrar el GIF de carga
            loadingElement.style.display = 'block';
            // Enviar datos al servidor
            fetch('email_facvencida.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                console.log(result);
                alert(result);
                // idsSeleccionados = [];
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar el correo');
            }).finally(() => {
                // Ocultar el GIF de carga
                loadingElement.style.display = 'none';
            });

}

function sendEmailfac (idfac){

    var checkboxf = document.getElementById('param10');
    var param11   = document.getElementById('param11');
    var numero   = document.getElementById('param12');

    const email = document.getElementById('param2');
    const body = document.getElementById('param5');
    var inputFile0 = document.getElementById('param3').files[0];
    var inputFile1 = document.getElementById('param6').files[0];

    const formData = new FormData();

    //agregar correo
    formData.append('correo', email.value);
    // formData.append('correos', idsSeleccionados.value);
    formData.append('correos', JSON.stringify(idsSeleccionados));

    if (checkboxf.checked) {
        console.log('chequeado');
        
                var linkFac = document.getElementById('linkfac');
                formData.append('linkFac', linkFac.value);
                // console.log("El checkbox está marcado.");
            } else {
                // console.log("El checkbox no está marcado.");
            }
    if (param11.checked) {
        console.log('chequeado');
        
                var linkFac1 = document.getElementById('linkfac1');
                formData.append('linkfac1', linkFac1.value);
                // console.log("El checkbox está marcado.");
    } else {
                // console.log("El checkbox no está marcado.");
    }
    //agregar correo linkfac1
    formData.append('body', body.value);
    formData.append('idfac', idfac);
    formData.append('File0', inputFile0);
    formData.append('File1', inputFile1);
    const numeroWhatsapp = numero ? numero.value.replace(/\D/g, '') : '';
    if (numeroWhatsapp.length >= 10) {
        formData.append('numero', numeroWhatsapp);
    }

    const loadingElement = document.getElementById('loading');

    // Mostrar el GIF de carga
    loadingElement.style.display = 'block';
    // Enviar datos al servidor
    fetch('email_fac.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        console.log(result);
        alert(result);
        // idsSeleccionados = [];
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al enviar el correo');
    }).finally(() => {
        // Ocultar el GIF de carga
        loadingElement.style.display = 'none';
    });

}




// Función para manejar los clics en los checkboxes
function selecionado(iduser,correo) {
    var checkbox = document.getElementById(iduser+"s");
    // const checkbox = event.target;
    const id = correo;

    if (checkbox.checked) {
        // Si el checkbox está marcado, agregar el ID al array
        idsSeleccionados.push(correo);
    } else {
        // Si el checkbox está desmarcado, eliminar el ID del array
        const index = idsSeleccionados.indexOf(correo);
        if (index !== -1) {
            idsSeleccionados.splice(index, 1);
        }
    }

    console.log("IDs seleccionados:", idsSeleccionados);
}

function enviarEmail() {
    let p3 = document.getElementById('param3').value;
    let p2 = document.getElementById('param2').value;
    let p4 = document.getElementById('param4').value;
    let p5 = document.getElementById('param5').value;
        
    if(p3==""){
        alert('Debe seleccionar un cliente');
    }else{
        let formData = new FormData();
        formData.append("param3", p3);
        formData.append("param2", p2);
        formData.append("param4", p4);
        formData.append("param5", p5);
        
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
                alert("Ok: " + data.message);
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
    
}
</script>
