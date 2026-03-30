<?php
date_default_timezone_set("America/Bogota");

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$carpeta = "excelclientesemail/";

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
$DB2 = new DB_mssql;
$DB2->conectar();
$FB = new funciones_varias;
$QL = new sql_transact;
$LT = new llenatablas;
$id_usuario = $_SESSION['usuario_id'];
$resultado = '';

$param1 = $_POST['param1'];
$param2 = $_POST['param2'];
$param3 = $_POST['param3'];
$param4 = $_POST['param4'];
$param5 = $_POST['param5'];

$conde1 = "";

if ($param2 != "" && $param1 != "") {
    $conde1 = " AND $param1 LIKE '%$param2%'";
}

if ($param1 == "") {
    $param1 = "ser_prioridad";
}

if ($param4 != '') {
    $fechaactual = $param4;
} else {
    $param4 = $fechaactual;
}

if ($param5 != '') {
    $fechaactualfinal = $param5;
} else {
    $param5 = $fechaactualfinal;
}

$fechaactual = date($fechaactual . ' 01:00:00');
$fechaactualfinal = date($fechaactualfinal . ' 23:59:59');

if ($param3 != "") {
    $conde0 = "cre_nombre='$param3'";
} else {
    $conde0 = "cre_correo_automatico='si'";
}

if ($param4 != "" && $param5 != "") {
    $conde11 = "AND a.ser_fecharegistro BETWEEN '$fechaactual' AND '$fechaactualfinal'";
} else {
    $fechaactual = date('Y') . '-01-01 01:00:00';
    $conde11 = "AND a.ser_fecharegistro > '$fechaactual'";
}

$creditos = "SELECT idcreditos, cre_nombre FROM creditos WHERE $conde0";
$DB->Execute($creditos);

while ($idcliente = mysqli_fetch_row($DB->Consulta_ID)) {
    $archivo_excel = $carpeta . "reporte_creditos_" . date("Ymd_His") . ".xls";

    $idtelefono = "SELECT usu_telefono, usu_idcredito FROM usuarios WHERE usu_idcredito='" . $idcliente[0] . "'";
    $DB2->Execute($idtelefono);
    $usuario = mysqli_fetch_row($DB2->Consulta_ID);

    $sqlhvcli = "SELECT hoj_nom_id FROM hojadevidacliente WHERE hoj_clientecredito='" . $usuario[1] . "'";
    $DB1->Execute($sqlhvcli);
    $datohvc = mysqli_fetch_row($DB1->Consulta_ID);

    $Identificador = ($datohvc[0] == "") ? "Id" : $datohvc[0];
    $esCroydon = ($idcliente[1] == "CROYDON");

    $sql = "SELECT
        a.idservicios,
        a.ser_fechaentrega,
        a.cli_nombre,
        a.cli_telefono,
        a.cli_direccion,
        a.ser_destinatario,
        a.ser_telefonocontacto,
        a.ser_direccioncontacto,
        a.ciu_nombre,
        a.ser_prioridad,
        a.ser_fecharegistro,
        a.ser_consecutivo,
        a.ser_guiare,
        a.cli_idciudad,
        a.ser_estado,
        '1' AS tipoc,
        a.ser_peso,
        a.ser_volumen,
        a.ser_valor,
        a.ser_valorseguro,
        a.ser_piezas,
        b.ser_codeCliente,
        b.ser_correo_auto,
        c.gui_fechaentrega
    FROM serviciosdia AS a
    INNER JOIN servicios AS b ON a.idservicios = b.idservicios
    LEFT JOIN guias AS c ON c.gui_idservicio = a.idservicios
    WHERE
        a.cli_telefono = '$usuario[0]'
        AND a.ser_estado != 100
        $conde11
        $conde1
    ORDER BY CASE WHEN a.ser_estado = 10 THEN 1 ELSE 0 END ASC, a.$param1 $asc";

    $DB2->Execute($sql);

    $totalcontado = 0;
    $totalpiezas = 0;
    $va = 0;

    ob_start();

    echo "<html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                background: #ffffff;
                color: #2c2c2c;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            .column-header th {
                background-color: #215C98;
                color: #f8fafc;
                font-weight: 700;
                text-align: center;
                padding: 8px;
                border: 1px solid #cbd5e1;
            }

            td {
                padding: 7px;
                border: 1px solid #e5e7eb;
                text-align: center;
                color: #374151;
            }

            .row-even td {
                background-color: #ffffff;
            }

            .row-odd td {
                background-color: #f8fafc;
            }

            .money {
                background-color: #ddfedb;
                color: #153D64;
                font-weight: 700;
            }

            .text-code {
                mso-number-format: '\\@';
                white-space: nowrap;
            }

            .status-ok {
                color: #166534;
                font-weight: 700;
            }

            .status-pending {
                color: #9a3412;
                font-weight: 700;
            }

            .total-row td {
                background-color: #215C98;
                color: #ffffff;
                font-weight: 700;
            }
        </style>
    </head>
    <body>
        <table>";

    if ($esCroydon) {
        echo "<tr class='column-header'>
            <th>Fecha</th>
            <th>Tipo de orden</th>
            <th>{$Identificador}</th>
            <th>Cliente</th>
            <th>Dir. Envio Destino</th>
            <th>Ciudad Destino</th>
            <th>Departamento</th>
            <th>Telefono</th>
            <th>Remesa</th>
            <th>Estado</th>
            <th>Fecha Entrega</th>
            <th>Piezas</th>
        </tr>";
    } else {
        echo "<tr class='column-header'>
            <th>#Idservicio</th>
            <th>#Guia</th>
            <th>{$Identificador}</th>
            <th>Destinatario</th>
            <th>Ciudad D</th>
            <th>Fecha Ingreso</th>
            <th>Telefono</th>
            <th>Direccion</th>
            <th>Piezas</th>
            <th>Volumen</th>
            <th>Peso</th>
            <th>Valor</th>
        </tr>";
    }

    while ($rw1 = mysqli_fetch_row($DB2->Consulta_ID)) {
        $va++;
        $rowClass = ($va % 2 === 0) ? "row-even" : "row-odd";
        $direct2 = str_replace("&", " ", $rw1[7]);
        $pordeclarado = (intval($rw1[19]) * 1) / 100;
        $valorTotal = $rw1[18] + $pordeclarado;

        $totalcontado += $valorTotal;
        $totalpiezas += (int) $rw1[20];

        if ($esCroydon) {
            $tipoOrden = "";
            $identificadorValor = trim($rw1[21]);
            $valorOrden = trim($rw1[21]);
            $partesOrden = preg_split('/\s+/', $valorOrden, 2);

            if (count($partesOrden) > 1) {
                $tipoOrden = $partesOrden[0];
                $identificadorValor = $partesOrden[1];
            } elseif (preg_match('/^[A-Za-z]/', $valorOrden)) {
                $tipoOrden = substr($valorOrden, 0, 2);
                $identificadorValor = substr($valorOrden, 2);
            }

            $identificadorExcel = '-' . ltrim($identificadorValor, '-');

            $estadoTexto = ($rw1[14] == 10) ? "ENTREGADO" : "EN RUTA";
            $estadoClase = ($rw1[14] == 10) ? "status-ok" : "status-pending";
            $fechaEntrega = !empty($rw1[23]) ? $rw1[23] : $rw1[1];

            echo "<tr class='{$rowClass}'>
                <td>{$rw1[10]}</td>
                <td>{$tipoOrden}</td>
                <td class='text-code'>{$identificadorExcel}</td>
                <td>{$rw1[5]}</td>
                <td>{$direct2}</td>
                <td>{$rw1[8]}</td>
                <td></td>
                <td>{$rw1[6]}</td>
                <td>{$rw1[12]}</td>
                <td class='{$estadoClase}'>{$estadoTexto}</td>
                <td>{$fechaEntrega}</td>
                <td>{$rw1[20]}</td>
            </tr>";
        } else {
            echo "<tr class='{$rowClass}'>
                <td>{$rw1[0]}</td>
                <td>{$rw1[12]}</td>
                <td>{$rw1[21]}</td>
                <td>{$rw1[5]}</td>
                <td>{$rw1[8]}</td>
                <td>{$rw1[10]}</td>
                <td>{$rw1[6]}</td>
                <td>{$direct2}</td>
                <td>{$rw1[20]}</td>
                <td>{$rw1[17]}</td>
                <td>{$rw1[16]}</td>
                <td class='money'>{$valorTotal}</td>
            </tr>";
        }
    }

    if ($esCroydon) {
        echo "<tr class='total-row'>
            <td colspan='10'>Total Piezas</td>
            <td>{$totalpiezas}</td>
            <td></td>
        </tr>";
    } else {
        echo "<tr class='total-row'>
            <td colspan='7'></td>
            <td>Total Piezas</td>
            <td>{$totalpiezas}</td>
            <td>Total Factura</td>
            <td colspan='2'>{$totalcontado}</td>
        </tr>";
    }

    echo "    </table>
    </body>
    </html>";

    $contenido = ob_get_clean();
    file_put_contents($archivo_excel, $contenido);

    $correos = "SELECT cont_correo FROM contactofacturacion
        INNER JOIN hojadevidacliente ON idhojadevida = cont_idhojavida
        WHERE con_correo_automatico='si' AND hoj_clientecredito='" . $usuario[1] . "'";
    $DB2->Execute($correos);

    while ($rw2 = mysqli_fetch_row($DB2->Consulta_ID)) {
        enviarCorreo($archivo_excel, $rw2[0]);
        $resultado .= 'Mail enviado ';
    }
}

$DB->cerrarconsulta();
$DB1->cerrarconsulta();
$DB2->cerrarconsulta();

function enviarCorreo($archivo, $destinatario)
{   

    echo "Correo se envio a " . $archivo . $destinatario;
    echo "<script>console.log('Se envio al correo');</script>";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'facturaciontransmillas@gmail.com';
        $mail->Password = 'qxlh uxsh ilgp xojp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('facturaciontransmillas@gmail.com', 'TRANSMILLAS LOGISTICA Y TRANSPORTADORA S.A.S.');
        $mail->addAddress($destinatario);
        // $mail->addAddress("jose523a@gmail.com");


        if (file_exists($archivo)) {
            $mail->addAttachment($archivo, basename($archivo));
        } else {
            throw new Exception("El archivo no existe: $archivo");
        }

        $mail->AddEmbeddedImage('images/logoCorreo.jpg', 'empresa_logo');

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
                    <p>Gracias por su atencion.</p>
                    <p>TRANSMILLAS LOGISTICA Y TRANSPORTADORA S.A.S.</p>
                    <p>Carrera 20 # 56-26 Galerias</p>
                    <p>PBX:3103122</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->isHTML(true);
        $mail->Subject = 'Reporte guias Creditos_' . $archivo . $destinatario;
        $mail->Body = $contenidoHTML;
        $mail->AltBody = strip_tags($mensaje);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar el correo: {$mail->ErrorInfo}");
        return false;
    }
}

$fechaActual = date('Y-m-d H:i:s');
$resultado .= "_" . $fechaActual;
echo $resultado;

$archivo = fopen("emailsEnviados.txt", "a");
fwrite($archivo, $resultado . "\n");
fclose($archivo);

?>
