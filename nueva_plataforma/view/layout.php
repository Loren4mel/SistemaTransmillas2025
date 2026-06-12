<?php
error_reporting(0);
ini_set('display_errors', 0);

// Assuming 'config' is at the same level as 'view' or accessible from it
require_once(__DIR__ . '/../config/database.php');

// Instantiate the Database class and get the connection
$db = new Database();
$conn = $db->connect();

// You'll need to replace these global variables or pass them through a controller
// For now, I'm defining them as they appear to be used in the original layout.php
// In a true MVC pattern, these would likely come from a controller.
session_start(); // Ensure session is started if $_SESSION is used

$id_sedes = isset($_SESSION['usu_idsede']) ? $_SESSION['usu_idsede'] : null;
$id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
$nivel_acceso = isset($_SESSION['nivel_acceso']) ? $_SESSION['nivel_acceso'] : null;
$fechaactual = date("Y-m-d"); // Example, replace with your actual date variable if different
$dia = date("N"); // 1 (for Monday) through 7 (for Sunday)
$id_nombre = isset($_SESSION['id_nombre']) ? $_SESSION['id_nombre'] : 'User'; // Example, replace with your actual name variable if different
$rol_nombre = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : 'Role'; // Example, replace with your actual role variable if different
$estado = 'Conectado'; // Example, replace with your actual status variable if different

// Placeholder for your DB class if it's still being used for specific methods like Execute, recogedato
// You might need to refactor these to use direct mysqli methods ($conn->query, $conn->prepare, etc.)
// Or create a wrapper around your new $conn object that mimics the old DB class methods.
// For demonstration, I'm creating a very basic mock of your old DB class structure.
class OldDBMock {
    public $Consulta_ID;
    private $mysqli_conn;

    public function __construct($conn) {
        $this->mysqli_conn = $conn;
    }

    public function Execute($sql) {
        $this->Consulta_ID = $this->mysqli_conn->query($sql);
        if (!$this->Consulta_ID) {
            error_log("SQL Error: " . $this->mysqli_conn->error . " in query: " . $sql);
        }
    }

    public function recogedato($index) {
        if ($this->Consulta_ID && $this->Consulta_ID->num_rows > 0) {
            $row = $this->Consulta_ID->fetch_row();
            return isset($row[$index]) ? $row[$index] : null;
        }
        return null;
    }
}

$DB = new OldDBMock($conn);
$DB1 = new OldDBMock($conn); // Assuming DB1 is a separate instance or just another reference to the same connection
$DB_m = new OldDBMock($conn);
$DB_m1 = new OldDBMock($conn);
$DB_m2 = new OldDBMock($conn);

// Placeholder for $FB and $LT objects if they are used elsewhere in your application.
// You would need to ensure these classes are defined or included.
// For now, I'll create dummy classes to avoid errors.
class FBMock {
    public function llena_texto($table, $id1, $id2, $db_obj, $param1, $param2, $text, $param3, $param4) {
        // Dummy implementation
        // echo "";
    }
}

class LTMock {
    public function llenadocs1($db_obj, $param1, $param2, $param3, $param4, $param5) {
        // Dummy implementation
        // echo "";
    }
}

$FB = new FBMock();
$LT = new LTMock();

$conde1 = "";
if (isset($_REQUEST["ord"])) {
    $ord = $_REQUEST["ord"];
} else {
    $ord = "1";
}
if (isset($_REQUEST["asc"])) {
    $asc = $_REQUEST["asc"];
} else {
    $asc = "ASC";
}
$asc2 = "ASC";
if ($asc == "ASC") {
    $asc2 = "DESC";
}
?>
	<style type="text/css">



            .chat2 {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #f1f1f1;
            width: 200px;
            height: 200px;
            cursor: move;
            }


            @media (min-width: 768px) {



                .chat {

                    position: fixed;;
                    bottom: 0;
                    right: 0;
                    background-color: #f1f1f1;
                    width: 400px;
                    height: 500px;
                    cursor: move;


                background-color: #fff;
                border: 1px solid #ccc;
                z-index: 9999;
                margin-bottom: 20px;
               background-color:  rgb(7, 79, 145);
                }


                #contenido{
                width: 400px;
                height: 500px;


                }


            #agrandar{
                background-color: #fff;
                /* float: right;   */
                display: none;
            }

            #minimizar{

                background-color: #fff;
                display: inline;
                float: right;
            }
            }


            @media (max-width: 768px) {


                .chat {

           position: fixed;
           bottom: 0;
           right: 0;
           width: 150px;
           height: 50px;
           background-color: #fff;
           border: 1px solid #ccc;
           z-index: 9999;
           margin-bottom: 20px;
           background-color:  rgb(7, 79, 145);
           }
           #contenido{
           width: 150px;
           height: 1px;

           }


           #agrandar{
                background-color: #fff;
                /* float: right;   */

            }

            #minimizar{

                background-color: #fff;
                display: none;
                float: right;
            }
            }


            #botones{
                background-color: rgb(7, 79, 145);
                /* float: right; */

            }

            .barra {
            cursor: move;
            position: absolute;
            top: 676px;
            left: 629;
            width: 150px;
            height: 50px;
            background-color: rgb(7, 79, 145);
            padding: 10px;
            }
            .barra2 {

            background-color: rgb(7, 79, 145);
            padding: 10px;

            }

            .bubble_chat {
                float: right;
                padding:2px 4px 2px 4px;
                background-color: rgb(7, 79, 145);
                color:white;
                font-weight:bold;
                font-size:0.80 em;
                border-radius:60px;
                box-shadow: 1px 1px 1px gray;
            }
            .notichat {
                float: right;
                padding:2px 4px 2px 4px;
                background-color: rgb(7, 79, 145);
                color:white;
                font-weight:bold;
                font-size:0.80 em;
                border-radius:60px;
                box-shadow: 1px 1px 1px gray;
            }
            #volver{

                float: right;
            }


			#header {
				margin:auto;
				width:500px;
				font-family:Arial, Helvetica, sans-serif;
			}

			ul, ol {
				list-style:none;
			}

			.nav > li {
				float:left;
			}

			.nav li a {
				background-color:#ecedef;
				text-decoration:none;
				padding:10px 12px;
				display:block;
			}
            .nav li a:hover {
				background-color:#f0f0f0;
			}

			.nav li ul {
				display:none;
				position:absolute;
				min-width:140px;
			}

			.nav li:hover > ul {
				display:block;
			}

			.nav li ul li {
				position:relative;
			}

			.nav li ul li ul {
				right:-140px;
				top:0px;
			}

            .noti_bubble {
                float: right;
                padding:2px 4px 2px 4px;
                background-color:red;
                color:white;
                font-weight:bold;
                font-size:0.80 em;
                border-radius:60px;
                box-shadow: 1px 1px 1px gray;
            }

		</style>
<script language="javascript">
// Función para bloquear el clic derecho
function bloquearClickDerecho() {
    document.addEventListener('contextmenu', bloquearEvento);
}

        // Función para desbloquear el clic derecho
function desbloquearClickDerecho() {
    document.removeEventListener('contextmenu', bloquearEvento);
}
        // Función que bloquea el evento predeterminado
        function bloquearEvento(event) {
            event.preventDefault();
            alert('El clic derecho está bloqueado.');
        }


 let dobleClicBloqueado = false;

        // Función para bloquear el doble clic
        function bloquearDobleClic() {
            if (!dobleClicBloqueado) {
                document.addEventListener('dblclick', prevenirDobleClic);
                dobleClicBloqueado = true;
                alert('El doble clic ha sido bloqueado.');
            }
        }

        // Función para desbloquear el doble clic
        function desbloquearDobleClic() {
            if (dobleClicBloqueado) {
                document.removeEventListener('dblclick', prevenirDobleClic);
                dobleClicBloqueado = false;
                alert('El doble clic ha sido desbloqueado.');
            }
        }

        // Función que previene la acción del doble clic
        function prevenirDobleClic(event) {
            event.preventDefault();
            alert('El doble clic está bloqueado.');
        }





function mostrarAlerta() {
    alert("¡Realice el preoperacional y espere un momento a que se habilite su sesion, si no se habilita Solicite ingreso al sistema!");
}

function llena_datosord(ord, asc)
{
	destino="<?php echo $_SERVER['PHP_SELF']; ?>?ord="+ord+"&asc="+asc;
	location.href=destino;
}
function llena_datosord2(ord, asc,tabla)
{
	destino="<?php echo $_SERVER['PHP_SELF']; ?>?ord="+ord+"&asc="+asc+"&tabla="+tabla;
	location.href=destino;
}
function  buscarnotificaciones(tipo){
    datos = {"tipo":tipo};
    var numerogastos;
		$.ajax({
				url: "notificaciones.php",
				type: "POST",
				data: datos,
				async: false,
				success: function(result) {
				//	alert(result);
					if(result!=null){

						numerogastos= result.gastossede;
						numeroperador= result.gastosoperador;
						numeroremesas= result.gastosremesas;
						cierrecaja= result.cierrecaja;
						seguimiento= result.seguimiento;
						pendientes= result.pendientes;
						faltantes= result.faltantes;
						alertassede= result.alertassede;
					//	alert(numerogastos);
                    if(alertassede!='' && alertassede>0 ){
							var divvalor= document.getElementById("notif28");
                            divvalor.innerHTML='<div class="noti_bubble">'+alertassede+'</div>';

						}
                    if(faltantes!='' && faltantes>0 ){
							var divvalor= document.getElementById("notif27");
                            divvalor.innerHTML='<div class="noti_bubble">'+faltantes+'</div>';

						}
                    if(pendientes!='' && pendientes>0 ){
							var divvalor= document.getElementById("notif26");
                            divvalor.innerHTML='<div class="noti_bubble">'+pendientes+'</div>';
						}
                    if(seguimiento!='' && seguimiento>0 ){
							var divvalor= document.getElementById("notif25");
                            divvalor.innerHTML='<div class="noti_bubble">'+seguimiento+'</div>';
						}
						if(numerogastos!='' && numerogastos>0 ){
							var divvalor= document.getElementById("notif");
                            divvalor.innerHTML='<div class="noti_bubble">'+numerogastos+'</div>';
						}

                        if(numeroperador!='' && numeroperador>0 ){
							var divvalor2= document.getElementById("notif22");
                            divvalor2.innerHTML='<div class="noti_bubble">'+numeroperador+'</div>';
                        }

                        if(numeroremesas!='' && numeroremesas>0 ){
							var divvalor3= document.getElementById("notif23");
                            divvalor3.innerHTML='<div class="noti_bubble">'+numeroremesas+'</div>';
						}
                        console.log(cierrecaja);
                        if(cierrecaja!='' && cierrecaja>0 ){
							var divvalor4= document.getElementById("notif24");
                            divvalor4.innerHTML='<div class="noti_bubble">'+cierrecaja+'</div>';
						}

                        trueorfalse=false;

					}else {
						trueorfalse=true;
					}
				}
            });
           // console.log('josee111');
            clearTimeout(timer2);
    // Polling desactivado para reducir carga recurrente en MySQL.
    // timer2=setTimeout(function(){buscarnotificaciones(tipo)},1200000);

}



</script>
<?php
$activo = true;
if ($nivel_acceso == 1 || $nivel_acceso == 6) {
    $controlDeUso = "";
    $controlUsoMensaje = '';
} else {
    $sql = "SELECT seg_motivo, seg_fechafinalizo from seguimiento_user where seg_idusuario='$id_usuario' and seg_fechaingreso like '%$fechaactual%' order by seg_fechaingreso asc";
    $DB->Execute($sql);
    $ingresoU = mysqli_fetch_row($DB->Consulta_ID);

    if ($ingresoU != "") {
        if ($ingresoU[0] == "Ingreso") {
            if (is_null($ingresoU[1])) {
                $controlDeUso = "";
                $controlUsoMensaje = '';
            } else {
                if ($nivel_acceso == 5) {
                    $controlDeUso = "";
                    $controlUsoMensaje = '';
                } else {
                    $controlDeUso = 'style="pointer-events: none; opacity: 0.4;"';
                    $controlUsoMensaje = 'pointer-events: none; opacity: 0.4;';
                    echo '<script language="javascript">mostrarAlerta();</script>';
                    echo '<script language="javascript">bloquearClickDerecho();</script>';
                    echo '<script language="javascript">bloquearDobleClic();</script>';
                    $activo = false;
                }
            }
        } else {
            $controlDeUso = 'style="pointer-events: none; opacity: 0.4;"';
            echo '<script language="javascript">mostrarAlerta();</script>';
            $controlUsoMensaje = 'pointer-events: none; opacity: 0.4;';
            echo '<script language="javascript">bloquearClickDerecho();</script>';
            echo '<script language="javascript">bloquearDobleClic();</script>';
            $activo = false;
        }
    } else {
        $controlDeUso = 'style="pointer-events: none; opacity: 0.4;"';
        echo '<script language="javascript">mostrarAlerta();</script>';
        $controlUsoMensaje = 'pointer-events: none; opacity: 0.4;';
        echo '<script language="javascript">bloquearClickDerecho();</script>';
        echo '<script language="javascript">bloquearDobleClic();</script>';
        $activo = false;
    }
}
?>
        <div class="modal fade" id="compose-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <?php  if($nivel_acceso==1){
                                echo '<h4 class="modal-title"><i class="fa fa-user"></i> Edita tu perfil</h4>';
                        }
                        ?>
                    </div>
                   <?php
					$sql="SELECT usu_nombre, usu_mail FROM usuarios WHERE idusuarios='$id_usuario' ";
					$DB->Execute($sql);
					$rw1=mysqli_fetch_row($DB->Consulta_ID);
					?>
					<form name='form2' id='form2' method='post' action='nuevo_adminok.php' enctype='multipart/form-data'>
                        <div class="modal-body">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">Nombre:</span>
                                    <input name="paramc1" id="paramc1" type="text" class="form-control" placeholder="Nombre" value="<?php echo $rw1[0]; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">Email:</span>
                                    <input name="paramc2" id="paramc2" type="email" class="form-control" placeholder="Ingrese email" value="<?php echo $rw1[1]; ?>">
                                </div>
                            </div>
                            <div class="form-group"><p>Si quiere modificar su contrase&ntilde;a llene los siguientes campos.</p></div>
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">Contrase&ntilde;a Actual:</span>
                                    <input name="paramc33" id="paramc33" type="password" class="form-control" placeholder="*****">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">Contrase&ntilde;a Nueva:</span>
                                    <input name="paramc3" id="paramc3" type="password" class="form-control" placeholder="******">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="btn btn-success btn-file">
                                    <i class="fa fa-paperclip"></i> Foto de perf&iacute;l
                                    <input type="file" name="paramc4" />
                                </div>
                                <p class="help-block">Tama&ntilde;o: 215px x 215px</p>
                            </div>
                        </div>
                        <div class="modal-footer clearfix">
                           <?php $FB->llena_texto("tabla", 1, 13, $DB, "", "", "Edita tu perfil", 5, 0); ?>
                           <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> Descartar</button>
                           <button type="submit" class="btn btn-primary pull-left"><i class="fa fa-check"></i> Guardar</button>
                        </div>
                    </form>
                </div></div></div>
    <body class="skin-blue">

        <header  class="header">
            <a href="inicio.php" class="logo">Inicio</a>
            <nav class="navbar navbar-static-top" role="navigation">
                <a href="#" class="navbar-btn sidebar-toggle" data-toggle="offcanvas" role="button">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </a>
                <div   class="navbar-right">
                    <ul class="nav navbar-nav">
                    <?php

                        $sqlnomina = "SELECT  count(*) from nomina where nom_id_usu='$id_usuario' and (nom_confirmaUsu='' or nom_confirmaUsu='no' ) and nom_cuentaCobro IS NOT NULL ";
                        $DB1->Execute($sqlnomina);
                        $nominaspendientes=$DB1->recogedato(0);
                        $prima = "SELECT   count(*) from primas where  pri_idusu='$id_usuario'  and (pri_confirmaUsus='' or pri_confirmaUsus='no' ) ";
                        $DB1->Execute($prima);
                        $primareco=$DB1->recogedato(0);
                        $totalnomina=$nominaspendientes+$primareco;
                        if ($totalnomina>0) {
                            echo"<script>pagosPendientes();</script>";
                        }
                        ?>

                        <li >
                            <a href="/nueva_plataforma/controller/PendientesController.php" ><i class="glyphicon glyphicon-time"></i><span>Pendientes
                                <i id='pendientes_modulo' >

                                        <div class="noti_bubble"><i ><?=$totalnomina?></i></div>
                                </i>
                                </span>
                            </a>
                        </li>
                        <?php
                    if($nivel_acceso==1){
                        // These DB_mssql connections seem to be redundant or for a different database type.
                        // Given your new Database class is for mysqli, I'm commenting them out.
                        // If you need MSSQL connections, you'll have to manage them separately.
                        // $DB_m = new DB_mssql;
                        // $DB_m->conectar();
                        // $DB_m1 = new DB_mssql;
                        // $DB_m1->conectar();
                        // $DB_m2 = new DB_mssql;
                        // $DB_m2->conectar();

                       $numerocomfirmar=0;
                        $gatoscomfirmar=0;
                        $remesascomfirmar=0;
                        $cierrecaja=0;
                        if($gatoscomfirmar>=1){
                            $colornoti2='background-color:#FF0000';
                        }else{
                            $colornoti2='';
                        }


                        $fechaIni = $fechaactual." 00:00:00";
                        $fechaFin = $fechaactual." 23:59:59";
                        $sql1 = "SELECT count(*) FROM usuarios WHERE  (usu_estado=1 and usu_filtro=1) and idusuarios NOT IN (SELECT  preidusuario FROM `pre-operacional` where  prefechaingreso>='$fechaIni' and prefechaingreso<='$fechaFin' ) and roles_idroles!='6' ORDER BY `idusuarios`  DESC ";
                        $DB1->Execute($sql1);
                        $sinIngreso=$DB1->recogedato(0);
                        // if($sinIngreso>=1){
                        //     $colornot='background-color:#FF0000';
                        // }else{
                        //     $colornot='';
                        // }


                        ?>

                        <li <?php echo$controlDeUso; ?>>
                            <a href="seguimientouser.php" ><i class="glyphicon glyphicon-bell"></i><span>👥 Sin ingreso
                                <i id='notif230' >

                                        <div class="noti_bubble"><i ><?=$sinIngreso?></i></div>
                                </i>
                                </span>
                            </a>
                         </li>
                        <li <?php echo$controlDeUso; ?>>
                            <a href="faltantes.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Faltantes
                                <i id='notif27'>
                                        <?=$flatantes?>
                                </i>
                                </span>
                            </a>
                         </li>
                          <li <?php echo$controlDeUso; ?>>
                            <a href="pesopendiente.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Pendientes
                                <i id='notif26'>
                                        <?=$pendientes?>
                                </i>
                                </span>
                            </a>
                         </li>
                         <li <?php echo$controlDeUso; ?>>
                            <a href="reporteoper.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Seguimiento
                                <i id='notif25'>
                                        <?=$seguimiento?>
                                </i>
                                </span>
                            </a>
                         </li>
                          <li <?php echo$controlDeUso; ?>>
                                <a href="cierrecaja.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Cierre
                                    <i id='notif24'>
                                            <?=$cierrecaja?>
                                    </i>
                                    </span>
                                </a>
                             </li>
                           <li <?php echo$controlDeUso; ?>>
                                <a href="gastos.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Remesas
                                    <i id='notif23'>
                                            <?=$remesascomfirmar?>
                                    </i>
                                    </span>
                                </a>
                             </li>
                            <li <?php echo$controlDeUso; ?>>
                                <a href="gastosoperador.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Gatos
                                    <i id='notif22'>
                                            <?=$gatoscomfirmar?>
                                    </i>
                                    </span>
                                </a>
                             </li>

                                <li <?php echo$controlDeUso; ?>>
                                    <a href="cajamenor.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Confirmar
                                        <i id='notif' >
                                                        <?=$numerocomfirmar?>
                                        </i>
                                        </span>
                                    </a>
                                </li>
                                <li <?php echo$controlDeUso; ?>>
                                    <a href="guias_no_identi.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Guias-
                                        <i id='notif' >
                                                        <?=$numerocomfirmar?>
                                        </i>
                                        </span>
                                    </a>
                                </li>

                        <?php
                        }elseif($nivel_acceso==2 or $nivel_acceso==5){

                            ?>

                            <li <?php echo$controlDeUso; ?>>
                            <a href="pesopendiente.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Pendientes
                                <i id='notif26'>
                                        <?=$pendientes?>
                                </i>
                                </span>
                            </a>
                         </li>
                            <li <?php echo$controlDeUso; ?>>
                            <a href="reporteoper.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Seguimiento
                                <i id='notif25'>
                                        <?=$seguimiento?>
                                </i>
                                </span>
                            </a>
                         </li>

                         <?php
                         } elseif($nivel_acceso==9){


                            ?>

                             <li id="noti_Container" <?php echo$controlDeUso; ?>>
                                <a href="faltantes.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Faltantes
                                    <i id='notif27'>
                                            <?=$faltantes?>
                                    </i>
                                    </span>
                                </a>
                              </li>
                              <?php
                         }elseif($nivel_acceso==3){


                            ?>

                             <li >
                                 <a href="gastos.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Remesas
                                     <i id='notif23'>
                                             <?=$remesascomfirmar?>
                                     </i>
                                     </span>
                                 </a>
                              </li>
                              <?php
                         } elseif($nivel_acceso==10){

                            ?>

                            <li <?php echo$controlDeUso; ?>>
                                <a href="reportealertas.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Alertas
                                    <i id='notif28'>
                                            <?=$alertassede?>
                                    </i>
                                    </span>
                                </a>
                             </li>
                            <li <?php echo$controlDeUso; ?>>
                                <a href="faltantes.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Faltantes
                                    <i id='notif27'>
                                            <?=$faltantes?>
                                    </i>
                                    </span>
                                </a>
                             </li>
                             <li <?php echo$controlDeUso; ?>>
                            <a href="pesopendiente.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Pendientes
                                <i id='notif26'>
                                        <?=$pendientes?>
                                </i>
                                </span>
                            </a>
                         </li>
                             <li <?php echo$controlDeUso; ?>>
                                <a href="cierrecaja.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Cierre
                                    <i id='notif24'>
                                            <?=$cierrecaja?>
                                    </i>
                                    </span>
                                </a>
                             </li>
                             <?php
                         } elseif($nivel_acceso==12){


                            ?>

                            <li <?php echo$controlDeUso; ?>>
                                    <a href="seguimientouser.php" ><i class="glyphicon glyphicon-bell"></i><span>👥 Sin ingreso
                                        <i id='notif230' >

                                                <div class="noti_bubble"><i ><?=$sinIngreso?></i></div>
                                        </i>
                                        </span>
                                    </a>
                            </li>
                              <li <?php echo$controlDeUso; ?> >
                                <a href="reportealertas.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Alertas
                                    <i id='notif28'>
                                            <?=$alertassede?>
                                    </i>
                                    </span>
                                </a>
                             </li>
                             <li <?php echo$controlDeUso; ?> >
                            <a href="pesopendiente.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Pendientes
                                <i id='notif26'>
                                        <?=$pendientes?>
                                </i>
                                </span>
                            </a>
                         </li>
                            <li <?php echo$controlDeUso; ?> >
                            <a href="reporteoper.php?idmen=194" ><i class="glyphicon glyphicon-bell"></i><span>Seguimiento
                                <i id='notif25'>
                                        <?=$seguimiento?>
                                </i>
                                </span>
                            </a>
                         </li>
                         <?php
                         }


                           echo '<li '.$controlDeUso.' ><a >Estado '.$estado.'<i class="caret"></a></i>
                                  <ul>

                                      <li><a href="cambio_adminok.php?tabla=cambioestado&condecion=almuerzo">Almorzando</a></li>
                                      <li><a href="cambio_adminok.php?tabla=cambioestado&condecion=regreso">Regreso Almuerzo</a></li>
                                      <li><a href="cambio_adminok.php?tabla=cambioestado&condecion=regresooficina">Regreso Oficina</a></li>';
                                    echo "<li><a onclick='pop_dis56(1, \"Temperatura\")'; >Temperatura</a></li>";
                                    echo "<li><a onclick='pop_dis56(1,\"Salida\")'; >Salida</a></li>";
                               echo '</ul>
                               </li>';
                          echo "<li $controlDeUso >";
                          echo "<a  onclick='pop_dis55(1, \"Cotizar\")'; > Cotizar</a>";
                           echo "</li>";
                           if($nivel_acceso==1 or $nivel_acceso==5 or $nivel_acceso==10){

                               echo "<li $controlDeUso >";
                                   echo "<a  onclick='pop_dis57(1, \"Cuentas\")'; > Cuentas</a>";
                               echo "</li>";

                           }elseif($id_sedes==1 and $nivel_acceso==2){

                              echo "<li $controlDeUso>";
                                   echo "<a  onclick='pop_dis57(1, \"Cuentas\")'; > Cuentas</a>";
                              echo "</li>";
                           }

                        ?>

                        <li  class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="glyphicon glyphicon-user"></i><span>Perfil <i class="caret"></i></span></a>
                            <ul class="dropdown-menu">
                                <li class="user-header bg-light-blue">
<?php
// These DB_mssql connections seem to be redundant or for a different database type.
// Given your new Database class is for mysqli, I'm commenting them out.
// If you need MSSQL connections, you'll have to manage them separately.
// $DB_m = new DB_mssql;
// $DB_m->conectar();
// $DB_m1 = new DB_mssql;
// $DB_m1->conectar();
// $DB_m2 = new DB_mssql;
// $DB_m2->conectar();
$sles="SELECT doc_ruta FROM documentos WHERE doc_tabla='Usuario' AND doc_idviene='$id_usuario' ORDER BY doc_fecha DESC ";
$DB_m->Execute($sles);
$imagenusu=$DB_m->recogedato(0);
$nombre=explode(" ",$id_nombre);
?>
<img src="<?php echo $imagenusu; ?>" class="img-circle" alt="User Image" />
<p><?php print $id_nombre; ?><small><?php echo $rol_nombre; ?></small></p>

                                </li>
                                <li class="user-footer">
                                    <div class="pull-left">
                                    <?php  if($nivel_acceso==1){
                                    	echo '<a class="btn btn-default btn-flat" data-toggle="modal" data-target="#compose-modal"><i class="fa fa-pencil"></i> Editar Perfil</a>';
                                    }
                                    ?>
                                    </div>
                                    <div class="pull-right">
                                        <a href="salir.php" class="btn btn-default btn-flat">Cerrar Sesi&oacute;n</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

            <aside class="left-side sidebar-offcanvas" style="min-height:550px;">
                <section  class="sidebar" >
                    <div class="user-panel">
                        <div class="pull-left image"><img src="<?php echo $imagenusu; ?>" class="img-circle" alt="User Image" /></div>
                        <div class="pull-left info"><p>Hola, <?php print $nombre[0]." ".$nombre[1];   ?></p>
						 <a href="#"><i class="fa fa-circle text-success"></i> Conectado</a></div>
                    </div>
                    <ul class="sidebar-menu">
<?php
if($id_sedes==1){
$condeno='';
}else{
    $condeno=" and men_url not in ('telefonosweb.php')";
}

 $sql="SELECT men_nombre, men_url, idmenu, men_descripcion FROM menu INNER JOIN permisos ON idmenu=menu_idmenu AND men_predecesor=0 AND roles_idroles='$nivel_acceso' AND men_orden!=0 AND per_consultar=1 $condeno ORDER BY men_orden ";
$DB_m->Execute($sql); $va=0;
while($rw_m=mysqli_fetch_row($DB_m->Consulta_ID))
{
	$id_menu=$rw_m[2]; if($rw_m[1]=="configuracion.php") { $link="#"; $class="treeview"; } else { $link=$rw_m[1];  $class="sidebar-menu"; }

 if ($activo!= false) {
    # code...

            if($link=="telefonosweb.php" ){
                $sql2="SELECT count(*) FROM `telefonospagina` WHERE  tel_estado = 'Sin validar' ";
                $DB_m2->Execute($sql2);
                $ntele=mysqli_fetch_row($DB_m2->Consulta_ID);

                echo "<li class='$class'><a href='$link' title='$rw_m[3]'>";
                $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                echo "<span> $rw_m[0] ";
            //  echo '<font color="Red" face="Comic Sans MS,arial">';
                echo ' <div class="noti_bubble">'.$ntele[0].'</div>';
                // echo '<i class="img-circle" style="background-color:#FF0000;width:160px;height:80px" > '.$ntele[0].' </i>';
                echo "</span> </a>";
            }else if($link=="vertareas.php" ){
                if($nivel_acceso==1 or $nivel_acceso==12 ){
                    $sql1="SELECT count(*)  FROM `tareas` LEFT JOIN (select `pro_comentario`, `pro_fecha`, `usu_nombre`,(CASE WHEN pro_estado IS Null THEN 'Por Realizar' else pro_estado END) as estado,pro_idtareas from programartareas left join usuarios on idusuarios=pro_idusuario where pro_fecha like '%$fechaactual%' ) t1 ON t1.pro_idtareas=idtareas WHERE idtareas>=0 and tar_diassemana like '%$dia%' and  tar_idsede='$id_sedes' and tar_estado='Activo'  and (CASE WHEN t1.estado IS Null THEN 'Por Realizar' else t1.estado END)='Por Realizar' ORDER BY idtareas asc";

                }else{
                    $sql1="SELECT count(*)  FROM `tareas` LEFT JOIN (select `pro_comentario`, `pro_fecha`, `usu_nombre`,(CASE WHEN pro_estado IS Null THEN 'Por Realizar' else pro_estado END) as estado,pro_idtareas from programartareas left join usuarios on idusuarios=pro_idusuario where pro_fecha like '%$fechaactual%'  ) t1 ON t1.pro_idtareas=idtareas WHERE idtareas>=0 and tar_diassemana like '%$dia%' and ((tar_idoperario='$id_usuario') or (tar_idoperario='0' and tar_idsede is NUll and tar_idrol='$nivel_acceso') or (tar_idoperario='0' and tar_idrol is NUll and tar_idsede='$id_sedes') or (tar_idoperario='0' and tar_idrol='$nivel_acceso' and tar_idsede='$id_sedes')) and tar_estado='Activo'  and (CASE WHEN t1.estado IS Null THEN 'Por Realizar' else t1.estado END)='Por Realizar' ORDER BY idtareas asc";
                }
                $DB1->Execute($sql1);
                $cantAlertas=$DB1->recogedato(0);
                        echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                        $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                        echo "<span > $rw_m[0] ";
                echo ' <div class="noti_bubble">'.$cantAlertas.'</div>';
                echo "</span>
                </a>";
            }
            else if($link=="reportealertas.php" ){
                $sql1 = "SELECT count(*) as sede FROM `reportealertas` inner join sedes on rep_idsede=idsedes WHERE idreportealertas>=0 and idsedes=$id_sedes  and rep_fechavencimiento<='$fechaactual' ";
                $DB1->Execute($sql1);
                $cantAlertas=$DB1->recogedato(0);
                        echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                        $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                        echo "<span > $rw_m[0] ";
                echo ' <div class="noti_bubble">'.$cantAlertas.'</div>';
                echo "</span>
                </a>";
            }else if($link=="reclamos.php" ){
                $sql1 = "SELECT count(*) FROM `reclamos` inner join servicios on rec_idservicio=idservicios WHERE idreclamos>0 and `rec_estado`= 'Confirmar' ORDER BY idreclamos";

                // $sql1 = "SELECT count(rec_tipo) FROM `reclamos` WHERE rec_estado='confirmar' ";
                $DB1->Execute($sql1);
                $reclamos=$DB1->recogedato(0);
                        echo "<li $controlDeUso class='$class'><a href='$link?param34=Confirmar' title='$rw_m[3]'>";
                        $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                        echo "<span > $rw_m[0] ";
                echo ' <div class="noti_bubble">'.$reclamos.'</div>';
                echo "</span>
                </a>";
            }
        //      else if($link=="salaChats3.php" ){
        //         $sql1 = "SELECT count(*) FROM `noticia` WHERE not_idusuario='$id_usuario' and  not_visto='no' and not_deChat = '' ";
        //         $DB1->Execute($sql1);
        //         $cantMensajes=$DB1->recogedato(0);
        //                 echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
        //                 $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
        //                 echo "<span > $rw_m[0] ";
        //           echo ' <div class="noti_bubble"><i id = "span">'.$cantMensajes.'</i></div>';
        //          echo "</span>
        //          </a>";

        //  }
        else if($link=="chatTransmillas.php" ){
            $sql1 = "SELECT COUNT(*)FROM `salasChatTransmillas` WHERE sl_id_deReceptor ='$id_usuario'and sl_numMensajes='1' ";
            $DB1->Execute($sql1);
            $numMensajes=$DB1->recogedato(0);
            echo'<input type="hidden"  id="mensajesN" value="'.$numMensajes.'">';
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                    echo "<span > $rw_m[0] ";
            echo ' <div class="notichat" id="notichat">'.$numMensajes.'</div>';
            echo "</span>
            </a>";


        }else if($link=="confirmacioncambios.php" && $id_sedes==1){
            $sql1 = "SELECT count(*) FROM `modificaciones` WHERE mod_userverificado='' ";
            $DB1->Execute($sql1);
            $canttrancambios=$DB1->recogedato(0);
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                    echo "<span > $rw_m[0] ";
            echo ' <div class="noti_bubble">'.$canttrancambios.'</div>';
            echo "</span>
            </a>";

        }else if($link=="confirmacionpagos.php" ){
            $sql1 = "SELECT count(*) FROM `pagoscuentas` WHERE pag_userverifica='' and pag_tipopago!=''";
            $DB1->Execute($sql1);
            $canttranferencias=$DB1->recogedato(0);
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                    echo "<span > $rw_m[0] ";
            echo ' <div class="noti_bubble">'.$canttranferencias.'</div>';
            echo "</span>
            </a>";

        }
        else{

                if($link=="confirmacioncambios.php" ){}
                elseif($link=="confirmacioncambios.php" and $nivel_acceso==3){

                }
                else{
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    $LT->llenadocs1($DB_m1, "Menu", $id_menu, 1, 15, 1);
                    echo "<span> $rw_m[0]</span></a>";
                }
            }


            echo "<ul class='treeview-menu'>";
            $sql="SELECT men_nombre, men_url, idmenu, men_descripcion FROM menu INNER JOIN permisos ON idmenu=menu_idmenu AND men_predecesor='$id_menu' AND
            roles_idroles='$nivel_acceso' AND men_orden!=0 AND per_consultar=1 ORDER BY men_orden ";
            $DB_m1->Execute($sql);
            while($rw_m1=mysqli_fetch_row($DB_m1->Consulta_ID))
            {
                if(strlen($rw_m1[0])>22){ $texts=substr($rw_m1[0],0,22)."...";  } else { $texts=$rw_m1[0]; }

                $general = explode('?',$rw_m1[1]);
                if($general[0] =='adm_general.php'){

                    if ($general[1] == '') {
                        echo "<li><a href='$rw_m1[1]?idmen=$rw_m1[2]&tabla=$rw_m1[0]' title='$rw_m1[0]'><i class='fa fa-angle-double-right'></i>$texts</a></li>";
                    } else {
                        echo "<li><a href='$rw_m1[1]&idmen=$rw_m1[2]&tabla=$rw_m1[0]' title='$rw_m1[0]'><i class='fa fa-angle-double-right'></i>$texts</a></li>";
                    }
            }
                else{
                    echo "<li><a href='$rw_m1[1]?idmen=$rw_m1[2]' title='$rw_m1[0]'><i class='fa fa-angle-double-right'></i>$texts</a></li>";
                }

            }
            echo "</ul></li>";
            $va++;
    }

}




?>

</ul></section></aside>
<aside class="right-side" style="min-height:550px;"  >



<?php
    if($nivel_acceso==1 or $nivel_acceso==2 or $nivel_acceso==10  or $nivel_acceso==12 or $nivel_acceso==5 ){
    ?>
<script language="javascript">
    timer2=0;
    clearTimeout(timer2);
     buscarnotificaciones(1); // 3000ms = 3s
    </script>
    <?php
    }

    ?>

<script type="text/javascript">



	function buscarservicio(valor, valor2, valores3, valor4, valor5=null) {
      //  alert(valores3);
		var ruta = "param20="+valor+"&param21="+valor2+"&paramtipser="+valores3+"&cro="+valor4+"&idservicio="+valor5;
		$.ajax({

			url: 'detalle_recoleccioncomprarecogida.php',
			type: 'Get',
			data: ruta,
		}).done(function(res) {

			$('#respuesta').html(res)
		});
	}

    function precioconvenir(valor,valor1,valor2,valor3) {

        if(valor1==1000){
            var ruta = "cond="+valor+"&param1="+valor1+"&para="+valor2+"&nombre="+valor3;
            $.ajax({

                url: 'resultados1.php',
                type: 'Get',
                data: ruta,
            }).done(function(res) {

                $('#convenir').html(res)
            });
        }else{
            $('#convenir').html('');
        }
    }



    </script>


<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
</body>
</html>
