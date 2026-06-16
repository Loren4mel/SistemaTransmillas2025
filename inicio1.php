<?php
require("login_autentica.php"); 
include("layout.php");  
?>

<?php
$DB2 = new DB_mssql;
$DB2->conectar(); 
$nivel_acceso=$_SESSION['usuario_rol'];
$id_usuario=$_SESSION['usuario_id'];
$estadofactura='recoleccion';
if($param15==""){ $param15='Recogida'; } 
$FB->llena_texto("Servicio:",15,82, $DB, $Servicio, "cambio22(this.value,\"inicio1.php\",\"Oficina\",\"param15\")","$param15",1, 1);
$actuliza="si";
if($param15=="Envio Oficina"){
	
	echo '<iframe src="oficina.php" style="width:100%; height:80vh; border:0;" loading="lazy"></iframe>';
   
	// if($nivel_acceso!=3){  
	// 	$FB->llena_texto("", 1, 142, $DB, "Guardar e Imprimir", "", 0, 12, 0);
	// }else {
	// 	 $FB->llena_texto("", 1, 142, $DB, "Guardar", "", 0, 12, 0);
	// }
	
	
}else if($param15=="Envio Oficina1"){
	
	include("oficina1.php");
   
	if($nivel_acceso!=3){  
		$FB->llena_texto("", 1, 142, $DB, "Guardar e Imprimir", "", 0, 12, 0);
	}else {
		 $FB->llena_texto("", 1, 142, $DB, "Guardar", "", 0, 12, 0);
	}
	
	
}
else if($param15=="Compra"){
	$boton='si';
	include("recoleccion_compra.php");

	// $FB->llena_texto("", 1, 151, $DB, "Guardar", "", 0, 12, 0);


	
} else {
	
	include("recoleccion_datos.php");	
	$FB->llena_texto("", 1, 142, $DB, "", "", 0, 12, 0);
}
 

require("footer.php");

?>




