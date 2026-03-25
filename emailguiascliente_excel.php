<?php
date_default_timezone_set("America/Bogota");
    // Incluir PHPMailer
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    require 'PHPMailer/src/Exception.php';

    // Usar los namespaces
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
// header('Content-type: application/vnd.ms-excel; charset=utf-8');
// header("Content-Disposition: attachment; filename=reporte_creditos.xls; charset=utf-8");
// header("Pragma: no-cache");
// header("Expires: 0");

// Ruta donde se guardará el archivo
$carpeta = "excelclientesemail/";


// Inicia el buffer de salida
ob_start();

require("connection/conectarse.php");
require("connection/funciones.php");
require("connection/arrays.php");
require("connection/funciones_clases.php");
require("connection/sql_transact.php");
require("connection/llenatablas.php");

$DB = new DB_mssql;
$DB->conectar();
$DB1 = new DB_mssql;
$DB1->conectar();
$FB = new funciones_varias;
$QL = new sql_transact;
$LT = new llenatablas;
$id_usuario = $_SESSION['usuario_id'];


$param1 = $_POST['param1'];
$param2 = $_POST['param2'];
$param3 = $_POST['param3'];
// $param3 = "CROYDON";

$param4 = $_POST['param4'];
$param5 = $_POST['param5'];

$conde1=""; 
$conde3=""; 

if($param2!="" and $param1!=""){ 
    $conde1=" and $param1 like '%$param2%' "; 
    }else { $conde1=""; } 
    
    if($param1==""){ $param1="ser_prioridad"; } 
    
    
    if($param4!=''){  $fechaactual=$param4; } else { $param4=$fechaactual; }
    if($param5!=''){  $fechaactualfinal=$param5;  } else { $param5=$fechaactualfinal; }
    
    $fechaactual=date($fechaactual.' 01:00:00');
    $fechaactualfinal=date($fechaactualfinal.' 23:59:59'); 

// $creditos = "SELECT idcreditos FROM `creditos` WHERE cre_nombre='$param3'";
if ($param3!="") {
    $conde0="cre_nombre='$param3'";
}else if($param3==""){
    $conde0="cre_correo_automatico='si'";
}

if ($param4!="" and $param5!="") {
    $conde11 ="AND a.ser_fecharegistro BETWEEN '$fechaactual' AND '$fechaactualfinal'";
}else {
    $fechaactual = date('Y') . '-01-01 01:00:00';
    $conde11 ="AND a.ser_fecharegistro >'$fechaactual'";
}


$creditos = "SELECT idcreditos, cre_nombre FROM `creditos` WHERE $conde0";

$DB->Execute($creditos);
// $idcliente = mysqli_fetch_row($DB->Consulta_ID);
while ($idcliente = mysqli_fetch_row($DB->Consulta_ID)) {
    $archivo_excel = $carpeta . "reporte_creditos_" . date("Ymd_His") . ".xls";
    $idtelefono = "SELECT `usu_telefono`, `usu_idcredito` FROM `usuarios` WHERE usu_idcredito ='" . $idcliente[0] . "'";
    $DB->Execute($idtelefono);
    $usuario = mysqli_fetch_row($DB->Consulta_ID);

    $sqlhvcli = "SELECT hoj_nom_id FROM `hojadevidacliente` WHERE hoj_clientecredito='" . $usuario[1] . "'";		
    $DB1->Execute($sqlhvcli);
    $datohvc = mysqli_fetch_row($DB1->Consulta_ID);
    $Identificador = ($datohvc[0] == "") ? "Id" : $datohvc[0];



    $sql = "SELECT 
        a.idservicios, a.ser_fechaentrega, a.cli_nombre, a.cli_telefono, a.cli_direccion, 
        a.ser_destinatario, a.ser_telefonocontacto, a.ser_direccioncontacto, a.ciu_nombre, 
        a.ser_prioridad, a.ser_fecharegistro, a.ser_consecutivo, a.ser_guiare, a.cli_idciudad, 
        a.ser_estado, '1' as tipoc, a.ser_peso, a.ser_volumen, a.ser_valor, 
        a.ser_valorseguro, a.ser_piezas, b.ser_codeCliente,b.ser_correo_auto,c.gui_fechaentrega  
        FROM serviciosdia AS a  
        INNER JOIN servicios AS b ON a.idservicios = b.idservicios  INNER JOIN `guias` as c on  c.gui_idservicio=a.idservicios
        WHERE 
            a.cli_telefono = '$usuario[0]'  
              
            AND a.ser_estado != 100 $conde11 
        $conde1  ORDER BY a.ser_fecharegistro desc ";
    echo '<table width="99%" border="1" align="center" cellpadding="1" cellspacing="1">';
    $esCroydon = ($idcliente[1] == "CROYDON");

    echo '<tr bgcolor="#F75700">
        <td>#Idservicio</td><td>#Guia</td>';

    if ($esCroydon) {
        echo '<td>Tipo de orden</td>';
    }

    echo '<td>' . $Identificador . '</td>
        <td>Destinatario</td><td>Ciudad D</td><td>Fecha Ingreso</td>
        <td>Telefono</td><td>Direccion</td><td>Piezas</td>
        <td>Volumen</td><td>Peso</td><td>Valor</td><td>Estado</td><td>Motivo Retraso/Novedad</td>
        <td>Fecha de entrega</td>
    </tr>';
    $DB->Execute($sql);
    $totalcontado = 0;
    $totalpiezas = 0;

    while ($rw1 = mysqli_fetch_row($DB->Consulta_ID)) {
        $tipoOrden = "";
        $identificadorValor = $rw1[21];

        if ($esCroydon) {
            $valorOrden = trim($rw1[21]);
            $partesOrden = preg_split('/\s+/', $valorOrden, 2);
            if (count($partesOrden) > 1) {
                $tipoOrden = $partesOrden[0];
                $identificadorValor = $partesOrden[1];
            } else if (preg_match('/^[A-Za-z]/', $valorOrden)) {
                $tipoOrden = substr($valorOrden, 0, 2);
                $identificadorValor = substr($valorOrden, 2);
            }
        }

        if ($rw1[14]==10 and $rw1[22]=="no") {
            $direct2=str_replace("&"," ", $rw1[7]);			
            $pordeclarado=(intval($rw1[19])*1)/100;
            $rw1[18]=$rw1[18]+$pordeclarado;
            $totalcontado=$rw1[18]+$totalcontado;
            $totalpiezas=$rw1[20]+$totalpiezas;

            echo "<tr>
                <td>{$rw1[0]}</td><td>{$rw1[12]}</td>";

                if ($esCroydon) {
                    echo "<td>{$tipoOrden}</td>";
                }

                echo "<td>{$identificadorValor}</td>
                <td>{$rw1[5]}</td><td>{$rw1[8]}</td><td>{$rw1[10]}</td>
                <td>{$rw1[6]}</td><td>{$direct2}</td><td>{$rw1[20]}</td>
                <td>{$rw1[17]}</td><td>{$rw1[16]}</td><td>{$rw1[18]}</td>";

                if ($rw1[14]==10) {
                    echo"<td>ENTREGADO</td>
                    <td>OK</td>
                    <td>{$rw1[23]}</td>";
                }else {
                    echo"<td>EN RUTA</td>
                    <td></td>
                    <td></td>";
                }


            echo"</tr>";
             $serviscorreo="UPDATE servicios SET ser_correo_auto='si' WHERE idservicios='$rw1[0]' ";
             $DB1->Execute($serviscorreo);
           
        }else if ($rw1[14]!=10 and $rw1[22]=="no") {
                # code...
            
            $direct2=str_replace("&"," ", $rw1[7]);			
            $pordeclarado=(intval($rw1[19])*1)/100;
            $rw1[18]=$rw1[18]+$pordeclarado;
            $totalcontado=$rw1[18]+$totalcontado;
            $totalpiezas=$rw1[20]+$totalpiezas;

            echo "<tr>
                <td>{$rw1[0]}</td><td>{$rw1[12]}</td>";

                if ($esCroydon) {
                    echo "<td>{$tipoOrden}</td>";
                }

                echo "<td>{$identificadorValor}</td>
                <td>{$rw1[5]}</td><td>{$rw1[8]}</td><td>{$rw1[10]}</td>
                <td>{$rw1[6]}</td><td>{$direct2}</td><td>{$rw1[20]}</td>
                <td>{$rw1[17]}</td><td>{$rw1[16]}</td><td>{$rw1[18]}</td>";

                if ($rw1[14]==10) {
                    echo"<td>ENTREGADO</td>
                    <td>OK</td>
                    <td>{$rw1[23]}</td>";
                }else {
                    echo"<td>EN RUTA</td>
                    <td></td>
                    <td></td>";
                }


            echo"</tr>";

        }

    }

    $colspanTotales = $esCroydon ? 9 : 8;

    echo "<tr bgcolor='#F75700'>
        <td colspan='$colspanTotales'><strong>Total Piezas</strong></td><td>$totalpiezas</td>
        <td colspan='2'><strong>Total Factura</strong></td><td>$totalcontado</td>
    </tr>";

    echo "</table>";

    // Guardar el contenido en un archivo
    $contenido = ob_get_contents();
    ob_end_clean();

    file_put_contents($archivo_excel, $contenido);
    // Enviar el correo
    $correos="SELECT cont_correo FROM `contactofacturacion` inner join hojadevidacliente on idhojadevida =cont_idhojavida   WHERE con_correo_automatico='si' and hoj_clientecredito='" . $usuario[1] . "' ";
    $DB->Execute($correos);
        while ($rw2 = mysqli_fetch_row($DB->Consulta_ID)) {
            enviarCorreo($archivo_excel,$rw2[0]);
            $resultado.='Mail enviado ';
        }
 
    
}
$DB->cerrarconsulta();
$DB1->cerrarconsulta();
$resultado='';



function enviarCorreo($archivo, $destinatario)
{

    echo"Correo se envio a ".$archivo.$destinatario;
echo"<script>console.log('Se envio al correo');</script>";
    // Crear el objeto PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'facturaciontransmillas@gmail.com';
        $mail->Password   = 'qxlh uxsh ilgp xojp'; // Usa la contraseña de app de Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Configurar remitente y destinatario
        $mail->setFrom('facturaciontransmillas@gmail.com', 'TRANSMILLAS LOGISTICA Y TRANSPORTADORA S.A.S.');
        $mail->addAddress($destinatario);
        

        

        // Adjuntar archivo
        if (file_exists($archivo)) {
            $mail->addAttachment($archivo, basename($archivo));
        } else {
            throw new Exception("El archivo no existe: $archivo");
        }

        // Adjuntar imagen embebida (si lo necesitas)
        $mail->AddEmbeddedImage('images/logoCorreo.jpg', 'empresa_logo');

        // Preparar contenido del mensaje
        $mensaje = "Adjunto se encuentra el reporte de creditos generado.";

        $contenidoHTML = '
        <html>
        <head>
            <style>
                .footer {
                    font-size: 12px;
                    color: #777;
                    margin-top: 20px;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
            </style>
        </head>
        <body>
            <div>
                <img src="cid:empresa_logo" alt="Logo de la empresa" style="width: 400px;">
                <p>' . $mensaje . '</p>
                <div class="footer">
                    <p>Gracias por su atención.</p>
                    <p>TRANSMILLAS LOGISTICA Y TRANSPORTADORA S.A.S.</p>
                    <p>Carrera 20 # 56-26 Galerías</p>
                    <p>PBX:3103122</p>
                </div>
            </div>
        </body>
        </html>';

        // Configurar mensaje
        $mail->isHTML(true);
        $mail->Subject = 'Reporte guias Creditos_'.$archivo.$destinatario.'';
        $mail->Body    = $contenidoHTML;
        $mail->AltBody = strip_tags($mensaje);

        // Enviar
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Error al enviar el correo: {$mail->ErrorInfo}");
        return false;
    }
    
}

$resultado.= "_".$fechaActual;
echo$resultado;

// Abre el archivo en modo "append" para agregar contenido al final del archivo
$archivo = fopen("emailsEnviados.txt", "a");

// Escribe el resultado en el archivo, seguido de un salto de línea
fwrite($archivo, $resultado . "\n");

// Cierra el archivo
fclose($archivo);

?>
