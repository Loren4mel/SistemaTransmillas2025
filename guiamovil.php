

<?php
require("login_autentica.php"); 
$id_usuario= $_SESSION['usuario_id'];

$id_sedes= $_SESSION['usu_idsede'];
$id_usuario= $_SESSION['usuario_id'];
$id_nombre=$_SESSION['usuario_nombre'];
$nivel_acceso=$_SESSION['usuario_rol'];
$precioinicialkilos=$_SESSION['precioinicial'];
$estadofactura='recoleccion';

?>

<form id="redirectForm" action="https://sistema.transmillas.com/nueva_plataforma/controller/RecogidasMovilController.php" method="post">
  <input type="hidden" name="acceso" value="<?= $nivel_acceso ?>">
  <input type="hidden" name="sede" value="<?= $id_sedes ?>">
  <input type="hidden" name="usuario" value="<?= $id_nombre ?>">
</form>

<script>
  // Enviar automáticamente el formulario al cargar la página
  document.getElementById("redirectForm").submit();
</script>



<?php

