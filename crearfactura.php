
<?php 
require("login_autentica.php"); 
include("layout.php");
//include("cabezote4.php"); 
$metodo=$_REQUEST["metodo"];

$FB->titulo_azul1("Crear Factura Externos",9,0,5);  
$FB->abre_form("form1","","post");

$fechados= date("d-m-Y",strtotime($fechaactual."- 2 week"));

?>
<script>



var genviar2=[];

function cargarGuiasExistentes(){
	const campoGuias = document.getElementById("param39");
	if (!campoGuias) {
		return;
	}

	const valorGuias = campoGuias.value.trim();
	if (valorGuias === "") {
		genviar2 = [];
		return;
	}

	genviar2 = valorGuias.split(",").map(function(guia){
		return guia.trim();
	}).filter(function(guia){
		return guia !== "" && guia !== "0";
	});

	genviar2 = [...new Set(genviar2)];
	campoGuias.value = genviar2.join(",");
}

function buscarclientes(){
			p1=0;
			p2=document.getElementById('param32').value;
			p3=document.getElementById('param33').value;	
			p4=document.getElementById('param34').value;
			p5=document.getElementById('param35').value;
			p6=document.getElementById('param36').value;
			metodo=document.getElementById('param40').value;
			pagina='detalle_crearfacturaexterna.php';
			destino=pagina+"?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&asc="+asc;
			MostrarConsulta4(destino, "destino_vesr");
			setTimeout(cargarGuiasExistentes, 200);
		}

function bucarguiaexterna(idguia,tipo)
{
	idservicio=document.getElementById('param2').value;
	tipo=document.getElementById('param3').value;
	
	datos = {"tipoguia":"buscarguia","idservicio":idservicio,"tipo":tipo};
		$.ajax({
				url: "guiasok.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				console.log(respuesta);
				if(respuesta==null){
					alert('El numero de guia no existe');
				}else{
					idguia=respuesta.ser_guiare;
					prestamo=respuesta.cue_pordeclarado;
					flete=respuesta.ser_valor;
					idservicio=respuesta.idservicios;
					estado_guia=respuesta.ser_estado;
					facturan=respuesta.ser_numerofactura;
					manifiesto=respuesta.ser_manifiesto;
					pendiente=respuesta.ser_pendientecobrar
						if (pendiente==1) {
							const input3 = document.getElementById("param35");
							input3.required = true;
							const input4 = document.getElementById("param50");
							input4.required = true;
							alert("Guia pendiente por pago, recuerde colocar fecha de vencimiento y correo");
							
						
						}		
					var pago="";

					console.log(facturan);
					if(estado_guia==100) {
						alert('La Guia esta cancelada');
					}else if(facturan!='' && facturan!= null){
						alert('La Guia ya esta agregada En la factura # '+facturan);
					}else{
						console.log('enviar:',genviar2);
						console.log('idservicio:',idservicio);
						if (genviar2 === undefined) {
							
							var estado='-1';
						}else{
							var estado= genviar2.indexOf(idservicio);
						}
						
						console.log(idservicio);
						console.log('manifiesto:',manifiesto);
							if(estado>=0){  
								alert('La Guia ya fue agregada en esta factura'); }
							else{
								agregarguia(idguia,prestamo,flete,manifiesto,idservicio,pago);
							}
					}
				
				}
				
			});
	

		
}



function borrarguia(idguia,prestamo,flete,idservicio)
{

	cargarGuiasExistentes();
	
	var guia=idguia;
	var testData = document.getElementById(idguia);
	
	if(testData!=null){
		document.getElementById(idguia).style.display='';
	}
	$("#"+guia+"2").remove();

	total=document.getElementById('param31').value;
	valortotal=parseInt(total)-parseInt(prestamo)-parseInt(flete);
	document.getElementById("param31").value=valortotal;
	//var genviar2=document.getElementById("param9").value;
	//genviar2=genviar.split(",");
	var paramguia=idservicio.toString();
	var index = genviar2.indexOf(paramguia);
	if (index > -1) {
		genviar2.splice(index,1);
	}
	console.log(genviar2);
	document.getElementById("param39").value=genviar2;
}

function guardarfactura(dato)
{
	cargarGuiasExistentes();
 	p1=document.getElementById('param31').value;
	p2=document.getElementById('param32').value;
	//p3=document.getElementById('param3').value;
	p4=document.getElementById('param34').value;
	p5=document.getElementById('param35').value;
	p8=document.getElementById('param38').value;
	p9=document.getElementById('param39').value;
	p10=document.getElementById('param40').value;
	p11=document.getElementById('param41').value;
	p12=document.getElementById('param42').value;
	p13=document.getElementById('param43').value;
	p50=document.getElementById('param50').value;
	if(p2==''){
		event.preventDefault();
		alert('Digite un Numero de Factura');
		return false;
	}else if(p13==''){
		event.preventDefault();
		alert('Se requiere el Nit de la empresa');
		return false;
	}{

	if(dato==2){

		datos = {"tipoguia":"buscarfactura","idfactura":p2};
	
		$.ajax({
				url: "guiasok.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				console.log(respuesta);
	
				if(respuesta=='duplicada'){
					alert('El Numero de Factura ya Existe');
				}else{
					p10=(p10=="EditarPrefa") ? "EditarPrefa" : "crear";
					destino="nuevo_adminok.php?param1="+p1+"&param2="+p2+"&param4="+p4+"&param5="+p5+"&param8="+p8+"&param9="+genviar2+"&param10="+p10+"&param11="+p11+"&param12="+p12+"&tabla=crearfacturaexterna&param13="+p13+"&param50="+p50;
					console.log(destino);
					document.location.href=destino;
				}
			}); 

	}else{
		p10="Editar";
		destino="nuevo_adminok.php?param1="+p1+"&param2="+p2+"&param4="+p4+"&param5="+p5+"&param8="+p8+"&param9="+genviar2+"&param10="+p10+"&param11="+p11+"&param12="+p12+"&tabla=crearfacturaexterna&param13="+p13+"&param50="+p50;
		console.log(destino);
		document.location.href=destino;

	}
 		
		//window.location=destino; 
	}


	
}

</script>
<body onload="setTimeout('myFunction()',4000);">
<?php 

	if($param34!=''){  $fechaactual1=$param34;}else{ $fechaactual1=$fechaactual; }
	if($param35!=''){  $fechaactual=$param35;}
	$FB->llena_texto("Busqueda por:", 3, 82, $DB, $busqueda3, "", $param1, 1, 1);
	$FB->llena_texto("Numero", 2, 1, $DB, "", "", "", 4, 1);
	echo "<td><button type='button' class='btn btn-primary btn-lg' onclick='bucarguiaexterna();'  >Agregar guia </button></td>
	</tr>";
	$FB->llena_texto("Cliente:",33, 2, $DB, "(SELECT `idclientes`,`cli_nombre` FROM  `clientes` inner join clientesservicios on cli_idclientes=idclientes  
	inner join rel_sercli on idclientesdir=ser_idclientes inner join servicios on  ser_idservicio=idservicios where ser_pendientecobrar=1 and ser_estado!=100 and (ser_numerofactura IS NULL or ser_numerofactura='') group by idclientes)", "buscarclientes();", "$param33",1,0);
	$FB->llena_texto("Fecha Factura:", 34, 10, $DB, "", "", "$fechaactual1", 4, 0);
	$FB->llena_texto("Fecha de Vencimiento:", 35, 10, $DB, "", "", "$fechaactual", 17, 0);
	$FB->llena_texto("N&#176; Factura:", 32, 1, $DB, "", "","$param32", 4,1);
	$FB->llena_texto("Fecha de Ingreso:", 42, 10, $DB, "", "", "$fechaactual", 17, 0);
	$FB->llena_texto("Valor de la Factura:", 31, 1, $DB, "", "","0",4,1);
	$FB->llena_texto("Nit:", 43, 1, $DB, "", "","",17,1);
	$FB->llena_texto("Correo:", 50, 1, $DB, "", "","",4,1);
	$param36factura = ($param36 != '') ? $param36 : $param6;
	$FB->llena_texto("param36", 4, 13, $DB, "", "", $param36factura, 5,2);
	$FB->llena_texto("tabla", 4, 13, $DB, "", "", "crearfactura", 5,2);
	$FB->llena_texto("param40", 4, 13, $DB, "", "", "$metodo", 5,2);
	$FB->llena_texto("param41", 4, 13, $DB, "", "", "$param32", 5,2);


	//	$FB->llena_texto("", 3, 142, $DB, "Crear Factura", "","", 1, 0);

	
$FB->div_valores("destino_vesr",12); 

$FB->cierra_form(); 

include("footer.php");


?>
<script>
	//p1=document.getElementById('param1').value;
	p1=0;
	p2=document.getElementById('param32').value;
	p3=document.getElementById('param33').value;	
	p4=document.getElementById('param34').value;
	p5=document.getElementById('param35').value;
	p6=document.getElementById('param36').value;
	metodo=document.getElementById('param40').value;

	//alert(p3);
	var pagina=0; 
	var asc="ASC";
	
	if(metodo=='Editar'){
		pagina='detalle_crearfacturaexterna.php';
	}else{
		pagina='detalle_crearfacturaexterna.php';
	}

		destino=pagina+"?param1="+p1+"&param2="+p2+"&param3="+p3+"&param4="+p4+"&param5="+p5+"&param6="+p6+"&pagina="+pagina+"&metodo="+metodo;
		MostrarConsulta4(destino, "destino_vesr");
		setTimeout(cargarGuiasExistentes, 200);



 	function myFunction(){
			//alert('jose');
			/* 	total=document.getElementById('param37').value;
			valortotal=parseInt(total);
			document.getElementById("param31").value=valortotal;
			var  genviar=document.getElementById("param39").value;
			genviar2=genviar.split(",");
			document.getElementById("param39").value=genviar2; 
			*/
		} 
	//	sleep(2000);

</script>

<script type="text/javascript">
    $( document ).ready(function() {
		//myFunction();
    });


// 	function copyColumnToClipboard(columnIndex) {
//             const table = document.getElementById('agregar');
//             let columnText = '';

//             // Recorrer las filas de la tabla y obtener el texto de la columna especificada
//             for (let i = 1; i < table.rows.length; i++) {
//                 columnText += table.rows[i].cells[columnIndex].innerText + '\n';
//             }

//             // Crear un elemento textarea para copiar al portapapeles
//             const textarea = document.createElement('textarea');
//             textarea.value = columnText;
//             document.body.appendChild(textarea);
//             textarea.select();
//             document.execCommand('copy');
//             document.body.removeChild(textarea);

//             alert('Columna copiada al portapapeles');
// }
function copyColumnToClipboard(columnIndex){

            const table = document.getElementById('agregar');
            let columnText = '';

            // Recorrer las filas de la tabla y obtener el texto de la columna especificada, omitiendo la primera fila (índice 0)
            for (let i = 1; i < table.rows.length; i++) { // Comenzar desde índice 1
                const cellText = table.rows[i].cells[columnIndex].innerText;
                const parts = cellText.split('-');
                if (parts.length === 2) {
                    columnText += parts[0] + '\t' + parts[1] + '\n';
                } else {
                    columnText += cellText + '\n'; // Manejar el caso donde no hay guion
                }
            }

            // Crear un elemento textarea para copiar al portapapeles
            const textarea = document.createElement('textarea');
            textarea.value = columnText;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            alert('Columna copiada al portapapeles');
        }
function agregarguia(idguia,prestamo,flete,manifiesto,idservicio,pago)
{
	cargarGuiasExistentes();
	
	if (pago==1) {
		const input = document.getElementById("param35");
    	input.required = true;
		const input1 = document.getElementById("param50");
    	input1.required = true;
    	alert("Guia pendiente por pago, recuerde colocar fecha de vencimiento y correo");
		
	
	 }
	total=document.getElementById('param31').value;
	valortotal=parseInt(total)+parseInt(prestamo)+parseInt(flete);
	document.getElementById("param31").value=valortotal;
	if (genviar2 === undefined) {
		genviar2 = [];
	}
	console.log(genviar2);
	var paramguia=idservicio.toString();
	console.log(paramguia);

	if (genviar2.indexOf(paramguia) === -1) {
		genviar2.push(paramguia);
	}

	var testData = !!document.getElementById(idguia);
	if(testData==true){
		document.getElementById(idguia).style.display='none';
	}

	html2= "<td align='center' ><a  onclick='pop_dis5("+idservicio+",\"Recogidas\")';  style='cursor: pointer;' title='Recogidas' >"+idguia+"</a></td>";

	document.getElementById("agregar").innerHTML +="<tr class='text' id='"+idguia+"2'>"+html2+"<td>"+prestamo+"</td><td>"+flete+"</td><td>"+manifiesto+"</td><td>"+
	"<button type='button' class='btn btn-danger' "+
	"onclick='borrarguia(\""+idguia+"\",\""+prestamo+"\",\""+flete+"\",\""+idservicio+"\")'>Quitar</button></td></tr>";
	document.getElementById("param39").value=genviar2;
}
</script>
