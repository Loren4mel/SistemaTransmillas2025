<?php 
include("cabezote1.php"); 
include("cabezote4.php"); 
$id_sedes=$_SESSION['usu_idsede'];
$conde1="";
if(isset($_REQUEST["ord"])){ $ord=$_REQUEST["ord"]; } else { $ord="1"; } 
if(isset($_REQUEST["asc"])){ $asc=$_REQUEST["asc"]; } else {$asc="ASC"; } $asc2="ASC"; if($asc=="ASC"){ $asc2="DESC";}

$layout_profile = false;
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

            .alerta-flotante {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 320px;
            background-color: #f44336; /* rojo tipo alerta */
            color: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-family: Arial, sans-serif;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: aparecer 0.5s ease-out;
            z-index: 9999;
            }

            .alerta-flotante.oculta {
            animation: desaparecer 0.4s ease-in forwards;
            }

            .alerta-cerrar {
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            margin-left: 10px;
            }

            @keyframes aparecer {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
            }

            @keyframes desaparecer {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-15px); }
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
// function buscarnotificaciones(tipo){

//     $.ajax({
//         url: "notificaciones.php",
//         type: "POST",
//         data: { tipo: tipo },
//         dataType: "json",
//         async: false, // 🔥 YA NO BLOQUEA
//         success: function(result) {

//             if(!result) return;

//             const asignar = (id, valor) => {
//                 if(valor && valor > 0){
//                     document.getElementById(id).innerHTML =
//                         '<div class="noti_bubble">'+valor+'</div>';
//                 }
//             };

//             asignar("notif28", result.alertassede);
//             asignar("notif27", result.faltantes);
//             asignar("notif26", result.pendientes);
//             asignar("notif25", result.seguimiento);
//             asignar("notif",   result.gastossede);
//             asignar("notif22", result.gastosoperador);
//             asignar("notif23", result.gastosremesas);
//             asignar("notif24", result.cierrecaja);

//         },
//         error: function(){
//             console.warn("Error cargando notificaciones");
//         }
//     });

//     clearTimeout(timer2);
//     timer2 = setTimeout(function(){
//         buscarnotificaciones(tipo)
//     }, 1200000); // cada 20 min
// }
let notificacionesEnCurso = false;
function buscarnotificaciones(tipo){
    if (document.hidden || notificacionesEnCurso) {
        return;
    }
    notificacionesEnCurso = true;

    $.ajax({
        url: "notificaciones.php",
        type: "POST",
        data: { tipo: tipo },
        dataType: "json",
        success: function(result) {

            if(!result) return;

            const asignar = (id, valor) => {
                const el = document.getElementById(id);
                if(!el) return;
                if(valor && valor > 0){
                    el.innerHTML = '<div class="noti_bubble">'+valor+'</div>';
                    return;
                }
                el.innerHTML = '';
            };

            asignar("notif28", result.alertassede);
            asignar("notif27", result.faltantes);
            asignar("notif26", result.pendientes);
            asignar("notif25", result.seguimiento);
            asignar("notif",   result.gastossede);
            asignar("notif22", result.gastosoperador);
            asignar("notif23", result.gastosremesas);
            asignar("notif24", result.cierrecaja);
        },
        error: function(){
            console.warn("Error cargando notificaciones");
        },
        complete: function(){
            notificacionesEnCurso = false;
        }
    });

}

let notificacionesTimer = null;
function iniciarNotificaciones(tipo){
    buscarnotificaciones(tipo);
    if(notificacionesTimer){
        clearInterval(notificacionesTimer);
    }
    notificacionesTimer = setInterval(function(){
        buscarnotificaciones(tipo);
    }, 1200000);
}

// function buscarnotificaciones(tipo){

//     // 🔥 Simulación sin consultar nada
//     const result = {
//         alertassede: 0,
//         faltantes: 0,
//         pendientes: 0,
//         seguimiento: 0,
//         gastossede: 0,
//         gastosoperador: 0,
//         gastosremesas: 0,
//         cierrecaja: 0
//     };

//     const asignar = (id, valor) => {
//         // Limpia siempre el contenido
//         document.getElementById(id).innerHTML = '';
//     };

//     asignar("notif28", result.alertassede);
//     asignar("notif27", result.faltantes);
//     asignar("notif26", result.pendientes);
//     asignar("notif25", result.seguimiento);
//     asignar("notif",   result.gastossede);
//     asignar("notif22", result.gastosoperador);
//     asignar("notif23", result.gastosremesas);
//     asignar("notif24", result.cierrecaja);

//     clearTimeout(timer2);
//     timer2 = setTimeout(function(){
//         buscarnotificaciones(tipo)
//     }, 1200000);
// }


</script>
<?php
$activo=true; 
       if ($nivel_acceso==1 or $nivel_acceso==6) {
        $controlDeUso="";
        $controlUsoMensaje='';

       } else{

        
        
            $sql="SELECT seg_motivo,seg_fechafinalizo from seguimiento_user where  seg_idusuario='$id_usuario' and seg_fechaingreso BETWEEN '$fechaactual 00:00:00' AND '$fechaactual 23:59:59' order by seg_fechaingreso asc";
            $DB->Execute($sql); 
            $ingresoU=mysqli_fetch_row($DB->Consulta_ID);

            if ($ingresoU!="") {
                if ($ingresoU[0]=="Ingreso") {
                    if ( is_null($ingresoU[1])) {
                        // echo"No ha salido";
                        $controlDeUso="";
                        $controlUsoMensaje='';

                    }else{
                        if ($nivel_acceso==5 ) {
                            $controlDeUso="";
                            $controlUsoMensaje='';
                        }else{
                            $controlDeUso='style="pointer-events: none; opacity: 0.4;"';
                            $controlUsoMensaje='pointer-events: none; opacity: 0.4;';
                            echo'<script language="javascript">mostrarAlerta();</script>';
                            echo'<script language="javascript">bloquearClickDerecho();</script>'; 
                            echo'<script language="javascript">bloquearDobleClic();</script>';
                            $activo=false; 

                        }
                    }
                    

                }else{
                    $controlDeUso='style="pointer-events: none; opacity: 0.4;"';
                    echo'<script language="javascript">mostrarAlerta();</script>';
                    $controlUsoMensaje='pointer-events: none; opacity: 0.4;';
                    echo'<script language="javascript">bloquearClickDerecho();</script>'; 
                    echo'<script language="javascript">bloquearDobleClic();</script>'; 
                    $activo=false; 



                    // echo"___si__".$ingresoU[0];
                }
            }else{
                $controlDeUso='style="pointer-events: none; opacity: 0.4;"';
                echo'<script language="javascript">mostrarAlerta();</script>';
                $controlUsoMensaje='pointer-events: none; opacity: 0.4;';
                echo'<script language="javascript">bloquearClickDerecho();</script>'; 
                echo'<script language="javascript">bloquearDobleClic();</script>'; 
                $activo=false; 


                // echo"_____".$ingresoU[0];
            }




       }


       if($layout_profile){ $layout_marks['control_uso'] = microtime(true); }
        
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
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div>
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

                        $sqlcompa = "SELECT seg_compañero,idseguimiento_user  from seguimiento_user where seg_fechaalcohol BETWEEN '$fechaactual 00:00:00' AND '$fechaactual 23:59:59'  and seg_idusuario='$id_usuario' and (seg_conf_compañero = '' or seg_conf_compañero IS NULL)";
                        $DB1->Execute($sqlcompa); 
                        $compa=mysqli_fetch_row($DB1->Consulta_ID);
                        // $nominaspendientes=$DB1->recogedato(0);

                        if ($compa[0]!="") {
                            $idCompa=$compa[0];
                            $idSeg=$compa[1];
                            echo"<script>aceptaCompañero($idCompa,$idSeg);</script>";
                        }
                        ?>

                        <li >
                            <a href="nueva_plataforma/controller/PendientesController.php" ><i class="glyphicon glyphicon-usd"></i><span>Mis pagos
                                <i id='mispagos' >
                                
                                        <div class="noti_bubble"><i ><?=$totalnomina?></i></div>
                                </i>
                                </span>
                            </a>
                        </li> 
                        <?php 
                    if($nivel_acceso==1){

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
                        $sql1 = "SELECT count(*) FROM usuarios WHERE  (usu_estado=1 and usu_filtro=1) and idusuarios NOT IN (SELECT  preidusuario FROM `pre-operacional` where  prefechaingreso>='$fechaIni' and prefechaingreso<='$fechaFin' ) and roles_idroles!='6' ";
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

                            $resultado = procesarSeguimiento($id_usuario, $DB, $DB1);
                            $mensaje   = $resultado['mensaje'];
                            $opcion    = $resultado['opcion'];
                            $accion    = $resultado['accion'];

                            ?>
                            <li>
                                <a href="javascript:void(0);"onclick="seguimientoruta2('<?= $mensaje ?>','<?= $opcion ?>','<?= $accion ?>')">
                                    
                                    <span>
                                        Me dirijo a
                                        <i id="notif23"><?= $remesascomfirmar ?></i>
                                    </span>
                                </a>
                            </li>
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
$DB_m = new DB_mssql;
$DB_m->conectar();
$DB_m1 = new DB_mssql;
$DB_m1->conectar();
$DB_m2 = new DB_mssql;
$DB_m2->conectar();
$sles="SELECT doc_ruta FROM documentos WHERE doc_tabla='Usuario' AND doc_idviene='$id_usuario' ORDER BY doc_fecha DESC ";
$DB_m->Execute($sles); 
$imagenusu=$DB_m->recogedato(0);
$nombre=explode(" ",$id_nombre);


$hoy = date('Y-m-d');
$tarea = "SELECT t.nombre
                FROM asignaciones a
                INNER JOIN tareasDiarias t ON a.tarea_id = t.id
                WHERE a.operador_id = '$id_usuario' 
                AND a.fecha = '$hoy'
                ORDER BY a.created_at DESC
                LIMIT 1";

$DB_m->Execute($tarea);
$tareaHoy = $DB_m->recogedato(0);
if($layout_profile){ $layout_marks['perfil_tarea'] = microtime(true); }
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
                <?php if($layout_profile){ $layout_marks['navbar'] = microtime(true); } ?>
            </nav>
        </header>

        <!-- Alerta de tareas asignadas  -->
         <?php if ($tareaHoy) {
                
            ?>
            <div id="alertaFlotante" class="alerta-flotante">
                <div class="alerta-contenido">
                    <span class="alerta-texto">📅 ¡Tienes una tarea asignada para hoy!</span>
                    <span class="alerta-texto">¡¡<?php echo$tareaHoy;?>!!</span>

                     
                    <button class="alerta-cerrar" onclick="cerrarAlerta()">×</button>
                </div>
            </div>
         <?php } ?>
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

 $submenusPorPadre = array();
 $sqlSubmenus = "SELECT m.men_nombre, m.men_url, m.idmenu, m.men_descripcion, m.men_predecesor
                 FROM menu m
                 INNER JOIN permisos p
                     ON m.idmenu=p.menu_idmenu
                     AND p.roles_idroles='$nivel_acceso'
                     AND p.per_consultar=1
                 WHERE m.men_predecesor!=0
                 AND m.men_orden!=0
                 ORDER BY m.men_predecesor, m.men_orden";
 $DB_m1->Execute($sqlSubmenus);
 while($rw_sub=mysqli_fetch_row($DB_m1->Consulta_ID)){
     $idPadre = (int)$rw_sub[4];
     if(!isset($submenusPorPadre[$idPadre])){
         $submenusPorPadre[$idPadre] = array();
     }
     $submenusPorPadre[$idPadre][] = $rw_sub;
 }

 $menuIconById = array();
 $menuIconCacheKey = "menu_icons_rol_" . $nivel_acceso;
 if(isset($_SESSION[$menuIconCacheKey]) && is_array($_SESSION[$menuIconCacheKey])){
     $menuIconById = $_SESSION[$menuIconCacheKey];
 }else{
     $sqlMenuIcons = "SELECT d.doc_idviene, d.doc_ruta
                      FROM documentos d
                      INNER JOIN (
                          SELECT doc_idviene, MAX(doc_fecha) AS doc_fecha
                          FROM documentos
                          WHERE doc_tabla='Menu' AND doc_version=1
                          GROUP BY doc_idviene
                      ) t
                      ON t.doc_idviene = d.doc_idviene
                      AND t.doc_fecha = d.doc_fecha
                      WHERE d.doc_tabla='Menu' AND d.doc_version=1";
     $DB_m2->Execute($sqlMenuIcons);
     while($rw_icon=mysqli_fetch_row($DB_m2->Consulta_ID)){
         $menuIconById[(int)$rw_icon[0]] = $rw_icon[1];
     }
     $_SESSION[$menuIconCacheKey] = $menuIconById;
 }

 $sql="SELECT men_nombre, men_url, idmenu, men_descripcion FROM menu INNER JOIN permisos ON idmenu=menu_idmenu AND men_predecesor=0 AND roles_idroles='$nivel_acceso' AND men_orden!=0 AND per_consultar=1 $condeno ORDER BY men_orden ";
$DB_m->Execute($sql); $va=0;
while($rw_m=mysqli_fetch_row($DB_m->Consulta_ID))
{
	$id_menu=$rw_m[2]; if($rw_m[1]=="configuracion.php") { $link="#"; $class="treeview"; } else { $link=$rw_m[1];  $class="sidebar-menu"; } 
    $menuIconHtml = "<td align='center'></td>";
    if(isset($menuIconById[(int)$id_menu]) && $menuIconById[(int)$id_menu]!=""){
        $menuIconRuta = htmlspecialchars($menuIconById[(int)$id_menu], ENT_QUOTES, 'UTF-8');
        $menuIconHtml = "<td align='center'><img src='{$menuIconRuta}' width='15'></td>";
    }
	
 if ($activo!= false) {
    # code...
   
            if($link=="telefonosweb.php" ){
                $sql2="SELECT count(*) FROM `telefonospagina` WHERE  tel_estado = 'Sin validar' ";
                $DB_m2->Execute($sql2);
                $ntele=mysqli_fetch_row($DB_m2->Consulta_ID);

                echo "<li class='$class'><a href='$link' title='$rw_m[3]'>";
                echo $menuIconHtml;
                echo "<span> $rw_m[0] ";
            //  echo '<font color="Red" face="Comic Sans MS,arial">';
                echo ' <div class="noti_bubble">'.$ntele[0].'</div>';
                // echo '<i class="img-circle" style="background-color:#FF0000;width:160px;height:80px" > '.$ntele[0].' </i>';
                echo "</span> </a>";
            }else if($link=="vertareas.php" ){  
                if($nivel_acceso==1 or $nivel_acceso==12 ){
                    $sql1="SELECT count(*)  FROM `tareas` LEFT JOIN (select `pro_comentario`, `pro_fecha`, `usu_nombre`,(CASE WHEN pro_estado IS Null THEN 'Por Realizar' else pro_estado END) as estado,pro_idtareas from programartareas left join usuarios on idusuarios=pro_idusuario where pro_fecha BETWEEN '$fechaactual 00:00:00' AND '$fechaactual 23:59:59' ) t1 ON t1.pro_idtareas=idtareas WHERE idtareas>=0 and tar_diassemana like '%$dia%' and  tar_idsede='$id_sedes' and tar_estado='Activo'  and (CASE WHEN t1.estado IS Null THEN 'Por Realizar' else t1.estado END)='Por Realizar'";

                }else{
                    $sql1="SELECT count(*)  FROM `tareas` LEFT JOIN (select `pro_comentario`, `pro_fecha`, `usu_nombre`,(CASE WHEN pro_estado IS Null THEN 'Por Realizar' else pro_estado END) as estado,pro_idtareas from programartareas left join usuarios on idusuarios=pro_idusuario where pro_fecha BETWEEN '$fechaactual 00:00:00' AND '$fechaactual 23:59:59'  ) t1 ON t1.pro_idtareas=idtareas WHERE idtareas>=0 and tar_diassemana like '%$dia%' and ((tar_idoperario='$id_usuario') or (tar_idoperario='0' and tar_idsede is NUll and tar_idrol='$nivel_acceso') or (tar_idoperario='0' and tar_idrol is NUll and tar_idsede='$id_sedes') or (tar_idoperario='0' and tar_idrol='$nivel_acceso' and tar_idsede='$id_sedes')) and tar_estado='Activo'  and (CASE WHEN t1.estado IS Null THEN 'Por Realizar' else t1.estado END)='Por Realizar'";
                }
                $DB1->Execute($sql1); 
                $cantAlertas=$DB1->recogedato(0);
                        echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                        echo $menuIconHtml;
                        echo "<span > $rw_m[0] ";
                echo ' <div class="noti_bubble">'.$cantAlertas.'</div>';
                echo "</span>
                </a>";
            }
            else if($link=="reportealertas.php" ){  
                $sql1 = "SELECT count(*) as sede FROM `reportealertas` inner join sedes on rep_idsede=idsedes WHERE idreportealertas>=0 and idsedes=$id_sedes  and rep_fechavencimiento<='$fechaactual 23:59:59' ";
                $DB1->Execute($sql1); 
                $cantAlertas=$DB1->recogedato(0);
                        echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                        echo $menuIconHtml;
                        echo "<span > $rw_m[0] ";
                echo ' <div class="noti_bubble">'.$cantAlertas.'</div>';
                echo "</span>
                </a>";
            }else if($link=="reclamos.php" ){  
                $sql1 = "SELECT count(*) FROM `reclamos` inner join servicios on rec_idservicio=idservicios WHERE idreclamos>0 and `rec_estado`= 'Confirmar'";

                // $sql1 = "SELECT count(rec_tipo) FROM `reclamos` WHERE rec_estado='confirmar' ";
                $DB1->Execute($sql1); 
                $reclamos=$DB1->recogedato(0);
                        echo "<li $controlDeUso class='$class'><a href='$link?param34=Confirmar' title='$rw_m[3]'>";
                        echo $menuIconHtml;
                        echo "<span > $rw_m[0] ";
                echo ' <div class="noti_bubble">'.$reclamos.'</div>';
                echo "</span>
                </a>";
            }
 
        else if($link=="confirmacioncambios.php" && $id_sedes==1){
            $sql1 = "SELECT count(*) FROM `modificaciones` WHERE mod_userverificado='' ";
            $DB1->Execute($sql1); 
            $canttrancambios=$DB1->recogedato(0);
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    echo $menuIconHtml;
                    echo "<span > $rw_m[0] ";
            echo ' <div class="noti_bubble">'.$canttrancambios.'</div>';
            echo "</span>
            </a>";

        }else if($link=="confirmacionpagos.php" ){
            $sql1 = "SELECT count(*) FROM `pagoscuentas` WHERE pag_userverifica='' and pag_tipopago!=''";
            $DB1->Execute($sql1); 
            $canttranferencias=$DB1->recogedato(0);
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    echo $menuIconHtml;
                    echo "<span > $rw_m[0] ";
            echo ' <div class="noti_bubble">'.$canttranferencias.'</div>';
            echo "</span>
            </a>";

        }else if($link=="cotizaciones.php" ){
            $sqlc = "SELECT COUNT(*) FROM `cotozaciones` where cot_id_ingresa='1919' and cot_validada='';";
            $DB1->Execute($sqlc); 
            $cotizacionesW=$DB1->recogedato(0);
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    echo $menuIconHtml;
                    echo "<span > $rw_m[0] ";
            if($nivel_acceso==1){
                echo ' <div class="noti_bubble">'.$cotizacionesW.'</div>';
            }
            echo "</span>
            </a>";

        }
        else{

                if($link=="confirmacioncambios.php" ){}
                elseif($link=="confirmacioncambios.php" and $nivel_acceso==3){
                    
                }
                else{
                    echo "<li $controlDeUso class='$class'><a href='$link' title='$rw_m[3]'>";
                    echo $menuIconHtml;
                    echo "<span> $rw_m[0]</span></a>";
                }
            }


        

            echo "<ul class='treeview-menu'>";
            if(isset($submenusPorPadre[$id_menu])){
                foreach($submenusPorPadre[$id_menu] as $rw_m1){
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
            }
            echo "</ul></li>";
            $va++;
    } 
     if($link=="EnviarGuiasaSede.php" and $activo== false){

                    echo "<li  class='$class'><a href='$link' title='$rw_m[3]'>";
                    echo $menuIconHtml;
                    echo "<span > $rw_m[0] ";
            
            echo "</span>
            </a>";
            

        }

} 

if($layout_profile){ $layout_marks['menu'] = microtime(true); }

function procesarSeguimiento($id_usuario, $DB, $DB1) {
    // Fecha actual
    $fechaactual = date('Y-m-d');
    $inicioDia = $fechaactual . " 00:00:00";
    $finDia = $fechaactual . " 23:59:59";
    
    // --- Consultar compañero ---
    $compañero = "SELECT seg_compañero 
                  FROM seguimiento_user 
                  WHERE seg_fechaalcohol BETWEEN '$inicioDia' AND '$finDia'  
                  AND seg_idusuario = '$id_usuario'";

    $DB1->Execute($compañero); 
    $rwcom = mysqli_fetch_row($DB1->Consulta_ID);
    $compa = $rwcom[0] ?? "";

    if ($compa != "") {
        $condeCom = "(seg_idusuario ='$id_usuario' OR seg_idusuario ='$compa')";
    } else {
        $condeCom = "seg_idusuario ='$id_usuario'"; 
    }

    // --- Consultar pre-operacional ---
    $preoper = "SELECT idpreoperacinal 
                FROM `pre-operacional` 
                WHERE prefechaingreso BETWEEN '$inicioDia' AND '$finDia' 
                AND preidusuario = $id_usuario";

    $DB->Execute($preoper);
    $preop = $DB->recogedato(0);

    // --- Consultar estado de guía ---
    $idestadoguia = "SELECT CONCAT(seg_estado,'|',seg_direccion,'|',seg_tipo,'|',seg_idservicio) AS id 
                     FROM seguimientoruta 
                     WHERE $condeCom 
                     AND seg_fecha BETWEEN '$inicioDia' AND '$finDia' 
                     AND seg_estado != 'Cambioruta' 
                     ORDER BY seg_fechaestado DESC 
                     LIMIT 1";

    $DB->Execute($idestadoguia);
    $estadoguia = $DB->recogedato(0);

    // --- Procesar resultado ---
    $datos = explode("|", $estadoguia);
    $estadoguia = $datos[0] ?? "";
    $direccion = $datos[1] ?? "";
    $tipo = $datos[2] ?? "";
    $idservicioruta = $datos[3] ?? "";

    // Quitar comillas dobles de $direccion
    $direccion = str_replace('"', '', $direccion);

    // --- Preparar retorno ---
    $direccion = addslashes($direccion); // Escapar comillas
    $mensaje = $direccion;

    if ($estadoguia == 'completado' || $tipo == 'opcionruta') {
        $opcion = 1;
        $accion = "seguimientoruta";
    } else if ($estadoguia == "En ruta") {
        $opcion = 2;
        $accion = "cambiarruta";
    } else {
        $opcion = 1;
        $accion = "seguimientoruta";
    }

    // Devolver array con valores
    return [
        "mensaje"        => $mensaje,
        "opcion"         => $opcion,
        "accion"         => $accion,
        "estado"         => $estadoguia,
        "direccion"      => $direccion,
        "tipo"           => $tipo,
        "idservicioruta" => $idservicioruta,
        "preoper"        => $preop,
        "compa"          => $compa
    ];
}

?>

</ul></section></aside>
<aside class="right-side" style="min-height:550px;"  > 
<div id="loader" style="
    display:none;
    position: fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    text-align:center;
    padding-top:20%;
">
    <img src="img/loading.gif" width="120">
</div>

<?php if($nivel_acceso==1 or $nivel_acceso==2 or $nivel_acceso==10 or $nivel_acceso==12 or $nivel_acceso==5){ ?>
<script>
// window.addEventListener("load", function() {
//     setTimeout(function(){
//         iniciarNotificaciones(1);
//     }, 2000);
// });
</script>
<?php } ?>


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
    
    function aceptarCompa(idCompa,idseg) {

        
            var ruta = "id="+idCompa+"&tabla=ConfirmaCompañero&idSeg="+idseg;
            $.ajax({

                url: 'nuevo_adminok.php',
                type: 'Get',
                data: ruta,
            }).done(function(res) {

                $('#myModal44').modal('hide');
            });

    }

    function cerrarAlerta() {
    const alerta = document.getElementById('alertaFlotante');
    if (alerta) { // Verifica que exista antes de manipularla
        alerta.classList.add('oculta');
        setTimeout(() => {
        if (alerta.parentNode) alerta.remove(); // Solo la elimina si sigue en el DOM
        }, 400); // la elimina después de la animación
    }
    }

    // Mostrarla automáticamente (por ejemplo, al cargar la página)
    window.addEventListener('load', () => {
    const alerta = document.getElementById('alertaFlotante');
    if (alerta) { // Solo ejecuta el cierre si la alerta existe
        setTimeout(() => cerrarAlerta(), 7000); // se cierra automáticamente
    }
    });

function guardarEntregar() {

    const form = document.getElementById("form223");

    // 1️⃣ Validar campos requeridos
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // 2️⃣ Validar nombre + apellido
    const nombre = document.getElementById("param82").value.trim();

    if (!nombre.includes(" ")) {
        alert("Debe ingresar nombre y apellido.");
        document.getElementById("param82").focus();
        return;
    }

    // 3️⃣ Mostrar loader
    mostrarLoader();

    // 4️⃣ Enviar AJAX
    const datos = new FormData(form);

    fetch("entregaRecoge.php", {
        method: "POST",
        body: datos
    })
    .then(response => response.text())
    .then(data => {

        // Ocultar loader
        ocultarLoader();

        const texto = data.trim().toUpperCase();

        if (texto.includes("OK")) {

            alert("Guardado exitosamente 😊");

            // Recargar la página
            // location.reload();

        } else if (texto.includes("NO HAY FIRMA")) {

            alert("Debe firmar o subir el sello");

        } else {

            alert("Ocurrió un error: " + data);
        }
    })
    .catch(error => {
        ocultarLoader();
        console.error('Error:', error);
    });
}


function guardarRecoger() {

    const form = document.getElementById("form114");

    // 1️⃣ Validar campos requeridos (HTML5)
    if (!form.checkValidity()) {
        form.reportValidity();
        return; // ❌ No continúa si falta algo
    }

    // 2️⃣ Validar que param82 tenga nombre + apellido (mínimo 1 espacio)
    const nombre = document.getElementById("param82").value.trim();

    if (!nombre.includes(" ")) {
        alert("Debe ingresar nombre y apellido.");
        document.getElementById("param82").focus();
        return; // ❌ No enviar si no cumple
    }

        // 3️⃣ Mostrar loader
    mostrarLoader();
    // 3️⃣ Si todo está bien, enviamos por AJAX
    const datos = new FormData(form);

    fetch("entregaRecoge.php", {
        method: "POST",
        body: datos
    })
    .then(response => response.text())
    .then(data => {

        ocultarLoader();
        const texto = data.trim().toUpperCase();

        if (texto.includes("OK")) {
            alert("Guardado exitosamente 😊");
            // $('#myModa114').modal('hide');
            location.reload();

        } else if (texto.includes("NO HAY FIRMA")) {
            alert("Debe firmar o subir el sello");

        } else {
            ocultarLoader();
            alert("Ocurrió un error: " + data);
        }
    })
    .catch(error => console.error('Error:', error));
}

function guardarNoEntregar() {

    const form = document.getElementById("form223");

    // 3️⃣ Mostrar loader
    mostrarLoader();
    // 3️⃣ Si todo está bien, se envía por AJAX
    const datos = new FormData(form);

    fetch("entregaRecoge.php", {
        method: "POST",
        body: datos
    })
    .then(response => response.text())
    .then(data => {

        ocultarLoader();
        const texto = data.trim().toUpperCase();

        if (texto.includes("OK")) {
            
            alert("Guardado exitosamente 😊");
            // window.location.href = window.location.href;
            // $('#myModa223').modal('hide');
            location.reload();
        } else if (texto.includes("NO HAY FIRMA")) {
            
            alert("Debe firmar o subir el sello");

        } else {
           
            alert("Ocurrió un error: " + data);
        }
    })
    .catch(error => console.error('Error:', error));
}

function guardarNoRecoger() {

    const form = document.getElementById("form114");

    // 3️⃣ Mostrar loader
    mostrarLoader();
    // 3️⃣ Si todo está bien, enviamos por AJAX
    const datos = new FormData(form);

    fetch("entregaRecoge.php", {
        method: "POST",
        body: datos
    })
    .then(response => response.text())
    .then(data => {
        ocultarLoader();
        const texto = data.trim().toUpperCase();

        if (texto.includes("OK")) {

            alert("Guardado exitosamente 😊");
            // $('#myModa114').modal('hide');
            location.reload();

        } else if (texto.includes("NO HAY FIRMA")) {
            
            alert("Debe firmar o subir el sello");

        } else {
            
            alert("Ocurrió un error: " + data);
        }
    })
    .catch(error => console.error('Error:', error));
}

function mostrarLoader() {
    document.getElementById("loader").style.display = "block";
}
function ocultarLoader() {
    document.getElementById("loader").style.display = "none";
}

  (function () {

    function getDeviceId() {
      if (!localStorage.getItem('device_id')) {
        localStorage.setItem('device_id', crypto.randomUUID());
      }
      return localStorage.getItem('device_id');
    }

    // Cuando cargue la página, asignamos el device_id al hidden
    document.addEventListener('DOMContentLoaded', function () {
      var input = document.getElementById('device_id');
      if (input) {
        input.value = getDeviceId();
      }
    });

  })();
</script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
</body>
</html>



