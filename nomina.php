

<?php 
require("login_autentica.php"); 
include("layout.php");
//include("cabezote4.php"); 
$fechaactual=date("Y-m-d");
?>
<head>
<style>
        .container{
            float: left;
            margin-right: 10px;
			display: flex;
            align-items: center;
        }
		.archivo-button {
         margin-right: 10px; /* Espacio entre el input y el botón */
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
			margin-right: 10px; /* Espacio entre el input y el botón */
        }

        .file-button {
            display: inline-flex;
            align-items: center;
            background-color: #4CAF50; /* Color de fondo */
            color: white; /* Color del texto */
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
			margin-right: 10px; /* Espacio entre el input y el botón */
        }
        .email-button i {
            margin-right: 8px; /* Espacio entre el icono y el texto */
        }

        .file-button i {
            margin-right: 8px; /* Espacio entre el icono y el texto */
        }



        .file-button {
            background-color: #4CAF50;
        }
	
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

	</head>
<body onLoad="llena_datos(0,<?php echo $nivel_acceso;?> , '', 'ASC'); 
 cambio_ajax2(<?php echo $id_sedes;?>, 16, 'llega_sub1', 'param33', 1, 0); 
">

<?php 





$FB->titulo_azul1("Nomina de Empleados",9,0,5);  

// $FB->abre_form("form1","","post");
if($nivel_acceso==1 or $nivel_acceso==12){
	if($param35!=''){   $conde2=""; }  

}
else {	
	$param35=$id_sedes;
	  $conde2.=" and idsedes='$id_sedes' "; 	
	
}
$fechacompleta=date('Y-m-d');
$mes=date('m');
$dia=date('d');
if($dia>=5 and $dia<=20){
	$mes=date('m');
	$añoA=date('Y');
$quincena1='Primera';
}elseif($dia<=5 ){
	
	// $mes=date('m');
	if ($mes==01) {
		$añoA=date('Y', strtotime('-1 year', strtotime($fechacompleta)));

	}else {
		$añoA=$añoA=date('Y');
	}
	$mes = date('m', strtotime('-1 month', strtotime($fechacompleta)));

	$quincena1='Segunda';
}else{
	$añoA=date('Y');
	$mes=date('m');
	// echo$mes = date('m', strtotime('-1 month', strtotime($fechacompleta)));
	$quincena1='Segunda';
}
$startYear = 2024; // Año inicial
$currentYear = 2027; // Año actual
for ($year = $startYear; $year <= $currentYear; $year++){
	
	$años["$year"]="$year";
	
	
}




$FB->llena_texto("A&ntildeo:", 39, 82, $DB, $años, "", "$añoA", 1, 0);
$FB->llena_texto("Mes:", 34, 82, $DB, $mesd, "", "$mes", 4, 0);
$FB->llena_texto("Quincena", 36, 82, $DB, $quincena, "", "$quincena1", 1, 0);
$FB->llena_texto("Sede :",35,2,$DB,"(SELECT `idsedes`,`sed_nombre` FROM sedes where idsedes>0 and sed_principal='si' $conde2  order by sed_nombre asc  )", "cambio_ajax2(this.value, 16, \"llega_sub1\", \"param33\", 1, 0)", "$param35",4, 0);
$FB->llena_texto("Operario:", 33, 444, $DB, "llega_sub1", "", "",1,0);
$FB->llena_texto("Tipo de Contrato:",37,82, $DB, $tipocontrato, "", "", 4, 0);
$FB->llena_texto("", 37, 277, $DB, "", "", "llena_datos(0, $nivel_acceso, \"id_nombre\", \"ASC\");",1,0);
echo "<tr><td colspan='4'>
<div  class='container'>
<input type='file' id='comproPago' class='archivo-button'>
<button type='button' id='enviarIds' class='email-button' ><i class='fas fa-envelope'></i>Cargar comprobante</button>
<button type='button' class='icon-button file-button' onclick='descargarExcel()'><i class='fas fa-file'></i>Excel parafiscales</button>
<button type='button' class='icon-button file-button' onclick='descargarExcelPagoNomina()'><i class='fas fa-file'></i>Excel para pagos</button>
<button type='button' class='email-button' onclick='reporteNomina()' >Reporte de nomina</button>
<button type='button' class='email-button' onclick='operadoresQuincena(\"$id_p\",\"Operadores Quincena\")' >Operadores quincena </button>
</div></td></tr>";

echo "</table><table>";
$FB->div_valores("destino_vesr",12); 

$FB->cierra_form(); 


include("footer.php");

?>


<script>
function escaparHtmlNomina(valor) {
	return String(valor || '').replace(/[&<>"']/g, function (caracter) {
		return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[caracter];
	});
}

function archivoComparendoNomina(ruta) {
	if (!ruta) {
		return '<span class="text-muted">Sin archivo</span>';
	}

	var rutaSegura = escaparHtmlNomina(ruta);
	var url = 'nueva_plataforma/' + rutaSegura;
	return '<a href="' + url + '" target="_blank">Ver</a>';
}

function crearModalComparendosNomina() {
	if (document.getElementById('modalComparendosNomina')) {
		return;
	}

	var modal = document.createElement('div');
	modal.id = 'modalComparendosNomina';
	modal.style.cssText = 'display:none;position:fixed;top:0;right:0;bottom:0;left:0;z-index:999999;background:rgba(0,0,0,.45);padding:24px;overflow:auto;';
	modal.onclick = function (event) {
		if (event.target === modal) {
			cerrarComparendosNomina();
		}
	};

	var dialog = document.createElement('div');
	dialog.style.cssText = 'width:min(1100px,96vw);margin:40px auto;background:#fff;border-radius:6px;box-shadow:0 12px 35px rgba(0,0,0,.25);';
	dialog.onclick = function (event) {
		event.stopPropagation();
	};

	dialog.innerHTML = ''
		+ '<div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #ddd;">'
		+ '<h4 id="modalComparendosNominaTitulo" style="margin:0;">Comparendos</h4>'
		+ '<button type="button" onclick="cerrarComparendosNomina()" aria-label="Cerrar" style="border:0;background:transparent;font-size:24px;line-height:1;cursor:pointer;">&times;</button>'
		+ '</div>'
		+ '<div style="padding:16px;overflow-x:auto;">'
		+ '<table class="table table-bordered table-hover">'
		+ '<thead><tr>'
		+ '<th>#</th><th>Fecha</th><th>Estado</th><th>Valor</th><th>Numero</th><th>Titular</th><th>Vehiculo</th><th>Foto</th><th>Curso</th>'
		+ '</tr></thead>'
		+ '<tbody id="tablaComparendosNomina"><tr><td colspan="9" class="text-center">Cargando...</td></tr></tbody>'
		+ '</table>'
		+ '</div>'
		+ '<div style="display:flex;justify-content:flex-end;padding:12px 16px;border-top:1px solid #ddd;">'
		+ '<button type="button" class="btn btn-default" onclick="cerrarComparendosNomina()">Cerrar</button>'
		+ '</div>';

	modal.appendChild(dialog);
	document.body.appendChild(modal);
}

function cerrarComparendosNomina(event) {
	var modal = document.getElementById('modalComparendosNomina');
	if (modal) {
		modal.style.display = 'none';
	}
}

function verComparendosNomina(idHojaVida, idUsuario, nombreTrabajador) {
	crearModalComparendosNomina();
	var cuerpo = document.getElementById('tablaComparendosNomina');
	document.getElementById('modalComparendosNominaTitulo').innerHTML = 'Comparendos - ' + escaparHtmlNomina(nombreTrabajador);
	cuerpo.innerHTML = '<tr><td colspan="9" class="text-center">Cargando...</td></tr>';
	document.getElementById('modalComparendosNomina').style.display = 'block';

	jQuery.ajax({
		url: 'ajax_comparendos_nomina.php',
		type: 'GET',
		data: { id_hoja_vida: idHojaVida, id_usuario: idUsuario },
		success: function (respuesta) {
			var lista = respuesta;
			if (typeof respuesta === 'string') {
				try {
					lista = JSON.parse(respuesta);
				} catch (error) {
					cuerpo.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Respuesta no valida al cargar comparendos</td></tr>';
					return;
				}
			}

			if (!lista || lista.length === 0) {
				cuerpo.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Sin comparendos registrados</td></tr>';
				return;
			}

			var filas = '';
			lista.forEach(function (comparendo, indice) {
				var vehiculo = [comparendo.veh_placa, comparendo.veh_marca, comparendo.veh_modelo].filter(Boolean).join(' ');
				var valor = comparendo.com_valor ? '$' + Number(comparendo.com_valor).toLocaleString('es-CO') : '';
				var estado = escaparHtmlNomina(comparendo.com_estado);
				var estadoHtml = String(comparendo.com_estado || '').toLowerCase() === 'pendiente'
					? '<span style="display:inline-block;padding:3px 8px;border-radius:999px;background:#c62828;color:#fff;font-weight:bold;">Pendiente</span>'
					: estado;

				filas += '<tr>'
					+ '<td>' + (indice + 1) + '</td>'
					+ '<td>' + escaparHtmlNomina(comparendo.com_fecha) + '</td>'
					+ '<td>' + estadoHtml + '</td>'
					+ '<td>' + valor + '</td>'
					+ '<td>' + escaparHtmlNomina(comparendo.com_numerocompa) + '</td>'
					+ '<td>' + escaparHtmlNomina(comparendo.com_titularcompa) + '</td>'
					+ '<td>' + escaparHtmlNomina(vehiculo) + '</td>'
					+ '<td>' + archivoComparendoNomina(comparendo.com_foto) + '</td>'
					+ '<td>' + archivoComparendoNomina(comparendo.com_foto_curso) + '</td>'
					+ '</tr>';
			});

			cuerpo.innerHTML = filas;
		},
		error: function () {
			cuerpo.innerHTML = '<tr><td colspan="9" class="text-center text-danger">No se pudieron cargar los comparendos</td></tr>';
		}
	});
}


function confirmarPago(idusuario,fechaactual,fechafinal,confirmarPago,confirma,tipo){
	
	// var valorAbono=document.getElementById(valor1,fechaini,fechafin).value;
	// var valorAbono = document.getElementById(valor1).value;
	
	var select = document.getElementById(idusuario+tipo);
	var enlace = document.getElementById(tipo+idusuario);


datos = {"idUsuario":idusuario,"fechaini":fechaactual,"fechafin":fechafinal,"funcion":confirmarPago,"confirma":confirma,"tipo":tipo};
		$.ajax({
				url: "guardaNomina.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				


				if (respuesta.trim().toLowerCase() === "ok") {
					// La respuesta es "ok", realiza las acciones correspondientes
					console.log("OK");
					// Cambia el color de fondo
		
					if (confirma=="Si") {
					select.style.backgroundColor = "#28B463"; // Cambia "red" por el color que desees
					enlace.style.pointerEvents = "auto";
					}else{

						select.style.backgroundColor = "#8B0000"; // Cambia "red" por el color que desees
						enlace.style.pointerEvents = "none";
					}
				} else {
					// La respuesta no es "ok", maneja el caso de otra manera si es necesario
					console.log("error al guardar cambio");
				}
			});
			
}



timer2 =0;
function llena_datos(ex, nivel, ordby, asc)
{
//	p1=document.getElementById('param31').value;
//	
	p1=0;
	// p2=document.getElementById('param32').value;
	p3=document.getElementById('param33').value;
	p4=document.getElementById('param34').value;
	p5=document.getElementById('param35').value;
	p6=document.getElementById('param36').value;
	p7=document.getElementById('param37').value;
	p9=document.getElementById('param39').value;

	//p7=document.getElementById('param37').value;
	var pagina=0; 
	if(ordby=="undefined"){ ordby=""; }
	if(asc=="undefined" || asc=="" ){ asc="ASC"; }
	if(ex==1){
		destino="detalle_nominaxl.php?p1="+p1+"&p4="+p4;
		location.href=destino;
	}
	else {
		destino="detalle_nomina.php?param31="+p1+"&param33="+p3+"&param34="+p4+"&param35="+p5+"&param36="+p6+"&param37="+p7+"&pagina="+pagina+"&ordby="+ordby+"&asc="+asc+"&param39="+p9;
		MostrarConsulta4(destino, "destino_vesr")
	}
	clearTimeout(timer2);
	timer2=setTimeout(function(){llena_datos(0,nivel,'','ASC')},600000); // 3000ms = 3s
}



function descargarExcel(){

alert("descargar excel");

var idusuario="ok";

    // p2=document.getElementById('param32').value;
	 p3=document.getElementById('param33').value;
	 p4=document.getElementById('param34').value;
	 p5=document.getElementById('param35').value;
	 p6=document.getElementById('param36').value;
     p7=document.getElementById('param37').value;
	p9=document.getElementById('param39').value;




destino="excelDeNomina.php?param33="+p3+"&param34="+p4+"&param35="+p5+"&param36="+p6+"&param37="+p7+"&param39="+p9;
		// location.href=destino;
		window.open(destino, '_blank');

}
function descargarExcelPagoNomina(){

alert("descargar excel");

var idusuario="ok";

    // p2=document.getElementById('param32').value;
	 p3=document.getElementById('param33').value;
	 p4=document.getElementById('param34').value;
	 p5=document.getElementById('param35').value;
	 p6=document.getElementById('param36').value;
     p7=document.getElementById('param37').value;
		p9=document.getElementById('param39').value;



destino="excelPagoNomina.php?param33="+p3+"&param34="+p4+"&param35="+p5+"&param36="+p6+"&param37="+p7+"&param39="+p9;
		// location.href=destino;
		window.open(destino, '_blank');

}



function enviarDesprendible(desprendible,idusuario,fechaactual,fechafinal,funcion,tipo){

	var boton = document.getElementById(tipo+idusuario+funcion);
	


datos = {"desprendible":desprendible,"idUsuario":idusuario,"fechaini":fechaactual,"fechafin":fechafinal,"funcion":funcion,"tipo":tipo};
		$.ajax({
				url: "guardaNomina.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				


				 if (respuesta.trim().toLowerCase() === "ok") {
					boton.textContent = 'Reenviar';

					boton.style.backgroundColor = "#28B463"; // Cambia "red" por el color que desees

				 }
			});
}

function confirmaAdmin(idusuario,fechaactual,fechafinal,funcion,tipo,aux1) {
    var checkbox = document.getElementById(tipo+idusuario+funcion+aux1);
    var boton = document.getElementById(tipo+idusuario+"guardarCuenCobro");


				if (checkbox.checked) {
					var confirma ="si";
				} else {
					var confirma ="no";
				}

	alert("deseas confirmar este documento?");
	

	datos = {"idUsuario":idusuario,"fechaini":fechaactual,"fechafin":fechafinal,"funcion":funcion,"tipo":tipo,"confirma":confirma};
		$.ajax({
				url: "guardaNomina.php",
				type: "POST",
				data: datos
			}).done(function(respuesta){
				


				if (checkbox.checked) {
					boton.style.display = "inline-block"; // Muestra el botón si el checkbox está marcado
				} else {
					boton.style.display = "none"; // Oculta el botón si el checkbox no está marcado
				}
			});
}

let idsSeleccionados = [];

    // Función para manejar los clics en los checkboxes
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


	function operadoresQuincena(id_param, tabla) {
		// Toma el array desde el componente HTML
		const div = document.getElementById('datosUsuarios');
		const idsUsu = JSON.parse(div.dataset.usuarios);

		console.log(idsUsu); // ya es un array JS

		// Tomar los demás parámetros del DOM
		var p9 = document.getElementById('param39').value;
		var p4 = document.getElementById('param34').value;
		var p6 = document.getElementById('param36').value;
		var p5 = document.getElementById('param35').value;
		var p7 = document.getElementById('param37').value;

		let arrayParam = encodeURIComponent(JSON.stringify(idsUsu));
		$("#myModal").modal("show");

		var destino = `detalle_pop.php?ide=${arrayParam}&id_param=${encodeURIComponent(id_param)}&tabla=${encodeURIComponent(tabla)}&año=${encodeURIComponent(p9)}&mes=${encodeURIComponent(p4)}&dia=${encodeURIComponent(p6)}&sede=${encodeURIComponent(p5)}&contrato=${encodeURIComponent(p7)}`;
		
		MostrarConsulta(destino, "llena_sub1");
	}




	let datosusertabla = [];

	// Función para manejar los clics en los checkboxes
	function selecionado1(id,
			trabajador,
			cedula,
			dias,
			descanso,
			valortdiasbase,
			valorDiasAux,
			diasNoTrabajo,
			diasIncapEmpresa,
			valorIncapempresa,
			valorDiasIncapa,
			diasVacacion,
			diasLicePermisos,
			valorLice,
			descuentoSalud,
			descueentoPensi,
			prestamosDescuadres,
			abonoDeudas,
			valorQuincena,
			valoHorasDomFes,
			otrosValoresMes,
			valorTotalOtros,
			recogidas,
			valorRecogidas,
			abonosDeudas,
			valorTotalOtros,
			TotalTodo) {
		var checkbox = document.getElementById(id + "s1");
		var contrato = "Prestacion";
		const data = {
			id: id,
			trabajador: trabajador,
			cedula: cedula,
			dias: dias,
			descanso: descanso,
			valortdiasbase: valortdiasbase,
			valorDiasAux: valorDiasAux,
			diasNoTrabajo: diasNoTrabajo,
			diasIncapEmpresa: diasIncapEmpresa,
			valorIncapempresa: valorIncapempresa,
			valorDiasIncapa: valorDiasIncapa,
			diasVacacion: diasVacacion,
			diasLicePermisos: diasLicePermisos,
			valorLice: valorLice,
			descuentoSalud: descuentoSalud,
			descueentoPensi: descueentoPensi,
			prestamosDescuadres: prestamosDescuadres,
			abonoDeudas: abonoDeudas,
			valorQuincena: valorQuincena,
			valoHorasDomFes: valoHorasDomFes,
			otrosValoresMes: otrosValoresMes,
			valorTotalOtros: valorTotalOtros,
			recogidas: recogidas,
			valorRecogidas: valorRecogidas,
			abonosDeudas: abonosDeudas,
			valorTotalOtros: valorTotalOtros,
			TotalTodo: TotalTodo,
			contrato: contrato
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


	let datosusertablaP = [];

	// Función para manejar los clics en los checkboxes
	function selecionado2(
			id,
			trabajador,
			cedula,
			dias,
			valortdiasbase,
			valorHoras,
			valorRecogidas,
			otropordia,
			valorOtros,
			prestamosDescuadres,
			abonoDeudas,
			retegarantias,
			valorquicena,
			contrato
		){
		var checkbox = document.getElementById(id + "s1");
		var contrato = "Prestacion";
		const data = {
			id: id,
			trabajador: trabajador,
			cedula: cedula,
			dias: dias,
			valortdiasbase: valortdiasbase,
			valorHoras: valorHoras,
			valorRecogidas: valorRecogidas,
			otropordia: otropordia,
			valorOtros: valorOtros,
			prestamosDescuadres: prestamosDescuadres,
			abonoDeudas: abonoDeudas,
			retegarantias: retegarantias,
			valorquicena: valorquicena,
			contrato: contrato
		};

		if (checkbox.checked) {
			// Agregar el objeto con los parámetros al array
			datosusertablaP.push(data);
		} else {
			// Buscar el objeto en el array y eliminarlo
			datosusertablaP = datosusertablaP.filter(item => item.id !== id);
		}

		console.log("Datos User tablaP:", datosusertablaP);
	}





	document.getElementById('enviarIds').addEventListener('click', function() {
		const imagenInput = document.getElementById('comproPago').files[0];
        const fechaHoraini = document.getElementById('fechaactual').value;
        const fechaHorafin = document.getElementById('fechafin').value;

            const formData = new FormData();
            formData.append('imagen', imagenInput);
            formData.append('ids', JSON.stringify(idsSeleccionados));
            formData.append('fechaHora1', fechaHoraini);
            formData.append('fechaHora2', fechaHorafin);

            $.ajax({
                url: 'comproPago.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
					idsSeleccionados = [];
					document.getElementById('comproPago').value = '';
                    console.log('Respuesta del servidor:', data);
                    // Aquí puedes manejar la respuesta del servidor si es necesario
					alert("Imagen cargada satisfactoriamente");
					
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
    });


	function pop_Retegarantia(idusuario, tabla, fechaactual, aux1){
		var funcion = "devolverRetegarantia";

		alert("Devolver "+aux1+" ?");
		

		datos = {"idUsuario":idusuario,"fechaini":fechaactual,"funcion":funcion,"valor":aux1};
			$.ajax({
					url: "guardaNomina.php",
					type: "POST",
					data: datos
				}).done(function(respuesta){
					
				});
     }
	 function pop_Retegarantiaresta(idusuario, tabla, fechaactual, aux1){
		var funcion = "restarRetegarantia";


		
  // Selecciona el div por su id
  var div = document.getElementById(idusuario);
  

		datos = {"idUsuario":idusuario,"fechaini":fechaactual,"funcion":funcion,"valor":aux1,"idUsuNom":tabla};
			$.ajax({
					url: "guardaNomina.php",
					type: "POST",
					data: datos
				}).done(function(respuesta){
					

						alert("Valor descontado");

					div.innerHTML = "$"+respuesta;

				});
     }

	 function reporteNomina() { 
		// Captura de valores adicionales
		
		var p4 = document.getElementById('param34').value;
		var p5 = document.getElementById('param35').value;
		var p6 = document.getElementById('param36').value;
		var p7 = document.getElementById('param39').value;
		
		const idusuario = "<?= $fechaactualSinTiempo ?>";
		// Crear formulario dinámico
		var form = document.createElement("form");
		form.method = "POST";
		form.action = "nominaReporte.php";
		form.target = "_blank";

		// Agregar inputs normales
		const campos = {
			param33: idusuario,
			param34: p4,
			param35: p5,
			param36: p6,
			param37: p7
		};

		for (let key in campos) {
			let input = document.createElement("input");
			input.type = "hidden";
			input.name = key;
			input.value = campos[key];
			form.appendChild(input);
		}

		// Convertir el arreglo completo a JSON
		let inputDatos = document.createElement("input");
		inputDatos.type = "hidden";
		inputDatos.name = "datosusertabla";
		inputDatos.value = JSON.stringify(datosusertabla); // Enviar como JSON
		form.appendChild(inputDatos);

				// Convertir el arreglo completo a JSON
		let inputDatos2 = document.createElement("input");
		inputDatos2.type = "hidden";
		inputDatos2.name = "datosusertablaP";
		inputDatos2.value = JSON.stringify(datosusertablaP); // Enviar como JSON
		form.appendChild(inputDatos2);

		document.body.appendChild(form);
		form.submit();
		form.remove();
		
		datosusertabla = [];
		datosusertablaP = [];
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

</script>
