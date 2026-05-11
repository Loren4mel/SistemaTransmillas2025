<?php

// Incluir los archivos de PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Crear una nueva instancia de PHPMailer
$mail = new PHPMailer(true);

$numCorreos = 0;
$numWhatsApp = 0;
$solicitoWhatsApp = false;
$erroresWhatsApp = [];

try {

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'facturaciontransmillas@gmail.com';
    $mail->Password   = 'qxlh uxsh ilgp xojp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $destinatario = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $correos = isset($_POST['correos']) ? json_decode($_POST['correos'], true) : [];

    // Remitente y destinatarios
    $mail->setFrom('ventastransmillas@gmail.com', 'TRANSMILLAS LOGISTICA Y TRANSPORTADORA S.A.S.');

    $hayCorreoParaEnviar = false;
    if ($destinatario != '') {
        $mail->addAddress($destinatario, '');
        $hayCorreoParaEnviar = true;
    }

    if (is_array($correos)) {
        foreach ($correos as $destinatarios) {
            if (!empty(trim($destinatarios))) {
                $mail->addAddress($destinatarios);
                $hayCorreoParaEnviar = true;
            }
        }
    }

    $contenido = $_POST['body'];
    $idFactura = $_POST['idfac'];

    // Adjuntar imagen embebida
    $mail->AddEmbeddedImage('images/logoCorreo.jpg', 'empresa_logo');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Documentos de Facturacion | Transmillas';

    $contenidoHTML = '
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="color-scheme" content="light">
        <meta name="supported-color-schemes" content="light">
        <title>Documentos de Facturacion</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                background: #f3f5f8;
                font-family: "Segoe UI", Tahoma, Arial, sans-serif;
                color: #1f2937;
            }
            .wrapper {
                width: 100%;
                padding: 28px 12px;
                box-sizing: border-box;
            }
            .card {
                max-width: 700px;
                margin: 0 auto;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 14px;
                overflow: hidden;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            }
            .header {
                background: linear-gradient(120deg, #0a2f63 0%, #11468f 100%);
                padding: 26px 26px 18px;
                text-align: left;
            }
            .logo {
                width: 300px;
                max-width: 100%;
                height: auto;
                display: block;
            }
            .header-subtitle {
                margin: 14px 0 0;
                color: #ffffff;
                font-size: 13px;
                letter-spacing: 0.2px;
            }
            .content {
                padding: 28px 26px 20px;
                font-size: 15px;
                line-height: 1.7;
            }
            .content p {
                margin: 0 0 12px;
            }
            .message-box {
                border-left: 4px solid #11468f;
                background: #f8fafc;
                padding: 14px 16px;
                border-radius: 6px;
            }
            .footer {
                margin-top: 22px;
                padding: 18px 26px 24px;
                border-top: 1px solid #e5e7eb;
                font-size: 12px;
                color: #6b7280;
                line-height: 1.6;
            }
            .brand {
                color: #0a2f63;
                font-weight: 700;
                font-size: 12px;
                letter-spacing: 0.3px;
            }
            @media only screen and (max-width: 600px) {
                .wrapper { padding: 14px 8px; }
                .header { padding: 20px 16px 14px; }
                .content { padding: 20px 16px 14px; }
                .footer { padding: 16px; }
                .logo { width: 230px; }
            }
            @media (prefers-color-scheme: dark) {
                body, .wrapper { background: #f3f5f8 !important; color: #1f2937 !important; }
                .card { background: #ffffff !important; border-color: #e5e7eb !important; }
                .header { background: linear-gradient(120deg, #0a2f63 0%, #11468f 100%) !important; }
                .header-subtitle { color: #ffffff !important; }
                .content, .content p, .message-box { color: #1f2937 !important; }
                .message-box { background: #f8fafc !important; border-left-color: #11468f !important; }
                .footer { color: #6b7280 !important; border-top-color: #e5e7eb !important; }
                .brand { color: #0a2f63 !important; }
            }
            [data-ogsc] body, [data-ogsc] .wrapper { background: #f3f5f8 !important; color: #1f2937 !important; }
            [data-ogsc] .card { background: #ffffff !important; border-color: #e5e7eb !important; }
            [data-ogsc] .header-subtitle { color: #ffffff !important; }
        </style>
    </head>
    <body style="margin:0; padding:0; background:#f3f5f8; color:#1f2937;">
        <div class="wrapper" style="background:#f3f5f8;">
            <div class="card" style="background:#ffffff;">
                <div class="header" style="background:#0a2f63;">
                    <img src="cid:empresa_logo" alt="Logo Transmillas" class="logo">
                    <p class="header-subtitle" style="color:#ffffff !important;">Comunicacion oficial de documentos de facturacion</p>
                </div>
                <div class="content">
                    <div class="message-box">
                        <p>' . $contenido . '</p>
                    </div>
                </div>
                <div class="footer">
                    <p>Gracias por su atencion.</p>
                    <p class="brand">TRANSMILLAS LOGISTICA Y TRANSPORTADORA S.A.S.</p>
                    <p>Carrera 20 # 56-26 Galerias</p>
                    <p>PBX: 3103122</p>
                </div>
            </div>
        </div>
    </body>
    </html>';

    $mail->Body    = $contenidoHTML;
    $mail->AltBody = strip_tags($contenido);

    if (isset($_FILES['File0']) && $_FILES['File0']['error'] == UPLOAD_ERR_OK) {
        $uploadFile0 = $_FILES['File0']['tmp_name'];
        $uploadFileName0 = $_FILES['File0']['name'];
        $mail->addAttachment($uploadFile0, $uploadFileName0);
    }

    if (isset($_FILES['File1']) && $_FILES['File1']['error'] == UPLOAD_ERR_OK) {
        $uploadFile1 = $_FILES['File1']['tmp_name'];
        $uploadFileName1 = $_FILES['File1']['name'];
        $mail->addAttachment($uploadFile1, $uploadFileName1);
    }

    if (isset($_POST['linkFac'])) {
        $existingFilePath = $_POST['linkFac'];
        $existingFileName = $_POST['linkFac'];
        $mail->addAttachment($existingFilePath, $existingFileName);
    }

    if (isset($_POST['linkfac1'])) {
        $existingFilePath = $_POST['linkfac1'];
        $existingFileName = $_POST['linkfac1'];
        $mail->addAttachment($existingFilePath, $existingFileName);
    }

    // Enviar el correo solo si hay destinatarios
    if ($hayCorreoParaEnviar) {
        $mail->send();
        $numCorreos++;
    }

    $existingFileName = isset($existingFileName) ? $existingFileName : '';
    $numeroWhatsApp = isset($_POST['numero']) ? preg_replace('/\D/', '', $_POST['numero']) : '';
    if (strlen($numeroWhatsApp) == 10 && substr($numeroWhatsApp, 0, 1) == '3') {
        $numeroWhatsApp = '57' . $numeroWhatsApp;
    }
    $solicitoWhatsApp = strlen($numeroWhatsApp) >= 10;
    if ($solicitoWhatsApp) {
        $link = 'https://sistema.transmillas.com/' . $existingFileName;
        $resultado = enviarAlertaWhat($contenido, $numeroWhatsApp, '33', $link);
        if ($resultado['status']) {
            $numWhatsApp++;
        } else {
            $erroresWhatsApp[] = $resultado['mensaje'];
        }
    }

    // Directorio para guardar archivos
    $uploadDir = __DIR__ . '/img_facturas/';
    $baseUrl = 'https://sistema.transmillas.com/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedLinks = [];

    for ($i = 0; $i <= 1; $i++) {
        $key = 'File' . $i;
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES[$key]['tmp_name'];
            $name = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '', $_FILES[$key]['name']);
            $dest = $uploadDir . $name;

            if (move_uploaded_file($tmpName, $dest)) {
                $uploadedLinks[] = $baseUrl . $name;

                if ($solicitoWhatsApp) {
                    $link = 'https://sistema.transmillas.com/' . $existingFileName;
                    $resultado = enviarAlertaWhat($contenido, $numeroWhatsApp, '34', $link);
                    if ($resultado['status']) {
                        $numWhatsApp++;
                    } else {
                        $erroresWhatsApp[] = $resultado['mensaje'];
                    }
                }
            }
        }
    }

    // Incluir la clase de conexion
    require_once 'nueva_plataforma/config/database.php';

    $db = new Database();
    $conn = $db->connect();

    $idFactura = isset($_POST['idfac']) ? intval($_POST['idfac']) : 0;

    if ($idFactura > 0) {
        $sql = 'SELECT fac_correofac FROM facturascreditos WHERE idfacturascreditos = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $idFactura);
        $stmt->execute();
        $stmt->bind_result($fac_correofac);

        if ($stmt->fetch()) {
            $stmt->close();

            $nummensajes = $fac_correofac + 1;

            $sqlUpdate = 'UPDATE facturascreditos SET fac_correofac = ? WHERE idfacturascreditos = ?';
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param('ii', $nummensajes, $idFactura);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }
    }

    $conn->close();

} catch (Exception $e) {
    echo "El mensaje no pudo ser enviado. Error de correo: {$mail->ErrorInfo}";
}

$estadoCorreo = ($numCorreos > 0)
    ? 'Correos enviados'
    : 'No se enviaron correos (sin destinatarios)';

$mensajeFinal = $estadoCorreo;

if ($solicitoWhatsApp) {
    if ($numWhatsApp > 0) {
        $mensajeFinal .= ' y WhatsApp enviado';
    } else {
        $mensajeFinal .= ' y no se pudo enviar WhatsApp';
        if (!empty($erroresWhatsApp)) {
            $mensajeFinal .= ': ' . implode(' | ', array_unique($erroresWhatsApp));
        }
    }
}

echo $mensajeFinal . '.';

function enviarAlertaWhat($numguia, $telefono, $tipo, $text2) {
    $url = 'https://bot.transmillas.com/whatsapp/Alertas/alertas.php';

    $data = array(
        'numero_guia' => $numguia,
        'telefono'    => $telefono,
        'tipo_alerta' => $tipo,
        'texto2'      => $text2
    );

    $data_json = json_encode($data);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data_json,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer MiSuperToken123'
        ),
    ));

    $response = curl_exec($curl);

    if ($response === false) {
        $error = curl_error($curl);
        curl_close($curl);
        return [
            'status' => false,
            'mensaje' => "Error en la solicitud: $error"
        ];
    }

    curl_close($curl);

    $response_data = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($response_data['error'])) {
            return [
                'status' => false,
                'mensaje' => 'Error API: ' . $response_data['error'] .
                             ' (Codigo: ' . ($response_data['status_code'] ?? 'N/A') . ')'
            ];
        }

        if (isset($response_data['success']) && $response_data['success']) {
            return [
                'status' => true,
                'mensaje' => 'WhatsApp enviado correctamente'
            ];
        }
    }

    return [
        'status' => false,
        'mensaje' => 'Respuesta inesperada de la API: ' . $response
    ];
}

?>
