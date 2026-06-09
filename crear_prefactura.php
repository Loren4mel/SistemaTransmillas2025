<?php
require("login_autentica.php");
include("declara.php");
$idUserActual=$_SESSION['usuario_id'];
$fechainicio=$_POST['param4'];
$fechaactual=$_POST['param5'];
$idguias=isset($_POST['guias']) ? trim($_POST['guias']) : '';

$param3="EXTERNOS";

		$idguias = trim($idguias, ',');
		if ($idguias=='') {
			echo "No hay guias para pre-facturar.";
			exit;
		}
		 $variable=date("Y").date("m").date("d").date("h").date("i").date("s");
		  $variableunica=$variable;
		$fechafactura='DE: '.$fechainicio.' Hasta '.$fechaactual;
		$sqll1="INSERT INTO `facturascreditos`(`fac_numerofactura`,`fac_fechafactura`,`fac_fechaprefac`,`fac_idservicios`, `fac_estado`,`fac_credito`, `fac_iduserpre`) 
		values ('$variableunica','$fechaactual','$fechafactura','$idguias','Pre-Facturado','$param3','$id_nombre')";
		// $DB1->Execute($sqll1);
		
        $resultado = $DB1->Execute($sqll1);

        if ($resultado) {
            echo "✅ Inserción exitosa.";
        } else {
            echo "❌ Error al insertar la prefactura.";
            // También puedes mostrar el error de MySQL si tu clase lo soporta
            // por ejemplo: echo $DB1->ErrorMsg(); 
        }
		// echo 'Se agrego la Pre-Factura con existo, Con '.$total.' Guias ';

