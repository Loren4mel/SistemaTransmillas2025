<?php
require_once "../config/database.php";
require_once '../../PHPMailer/src/PHPMailer.php';
require_once '../../PHPMailer/src/SMTP.php';
require_once '../../PHPMailer/src/Exception.php';

// Importar clases
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class recogerEntregarModel{
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }
    public function guardarFirmaEntrega($idservicio, $firmaBase64)
    {
        date_default_timezone_set('America/Bogota');
        $fechaHoraColombia = date('Y-m-d H:i:s');
        try {
            // Convertir base64 a imagen
            $firmaData = str_replace('data:image/png;base64,', '', $firmaBase64);
            $firmaData = str_replace(' ', '+', $firmaData);
            $imagen = base64_decode($firmaData);

            // Crear carpeta si no existe
            // $rutaCarpeta = __DIR__ . '../../uploads/firmas_clientes/';
            $rutaCarpeta = __DIR__ . '/../../firmas_clientes/';

            if (!file_exists($rutaCarpeta)) {
                mkdir($rutaCarpeta, 0777, true);
            }

            // Nombre único para la imagen
            $nombreArchivo = 'firma_' . $idservicio . '_' . time() . '.png';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            $rutaArchivoGuardar="firmas_clientes/".$nombreArchivo;

            // Guardar archivo en el servidor
            file_put_contents($rutaArchivo, $imagen);


            $tipoFirma = 'Entrega';

            // Verificar si ya existe firma para ese servicio y tipo.
            $sqlCheck = "SELECT id FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            if (!$stmtCheck) {
                error_log("❌ Error al preparar consulta de verificación: " . $this->db->error);
                return false;
            }

            $stmtCheck->bind_param('is', $idservicio, $tipoFirma);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            $activoFirma=0;
            if ($resultCheck && $resultCheck->num_rows > 0) {
                // Ya existe -> actualizar.
                $rowCheck = $resultCheck->fetch_assoc();
                $idFirma = (int)$rowCheck['id'];

                $sqlUpdate = "UPDATE firma_clientes
                              SET firma_clientes = ?, fecha_registro = ?, activo_para_firmar = ?
                              WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                if (!$stmtUpdate) {
                    error_log("❌ Error al preparar UPDATE: " . $this->db->error);
                    return false;
                }

                $stmtUpdate->bind_param('ssii', $rutaArchivoGuardar, $fechaHoraColombia,$activoFirma, $idFirma);
                $stmtUpdate->execute();
                return ($stmtUpdate->affected_rows >= 0);
            }

            // No existe -> insertar.
            $sqlInsert = "INSERT INTO firma_clientes (id_guia, tipo_firma, firma_clientes,fecha_registro,activo_para_firmar)
                          VALUES (?, ?, ?, ?,?)";
            $stmtInsert = $this->db->prepare($sqlInsert);
            
            if (!$stmtInsert) {
                error_log("❌ Error al preparar INSERT: " . $this->db->error);
                return false;
            }

            $stmtInsert->bind_param('isssi', $idservicio, $tipoFirma, $rutaArchivoGuardar, $fechaHoraColombia,$activoFirma);
            $stmtInsert->execute();
            return ($stmtInsert->affected_rows > 0);

        } catch (Exception $e) {
            error_log("❌ Error al guardar la firma: " . $e->getMessage());
            return false;
        }
    }
    public function guardarFirmaRecogida($idservicio, $firmaBase64)
    {
        date_default_timezone_set('America/Bogota');
        $fechaHoraColombia = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/debug_guardar_firma.log';
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] === INICIO guardarFirmaRecogida() ===\n", FILE_APPEND);

        try {
            file_put_contents($logFile, "🟡 Paso 1: Recibido idservicio=$idservicio\n", FILE_APPEND);

            // 1️⃣ Convertir base64 a imagen
            $firmaData = str_replace('data:image/png;base64,', '', $firmaBase64);
            $firmaData = str_replace(' ', '+', $firmaData);
            $imagen = base64_decode($firmaData);

            if ($imagen === false) {
                file_put_contents($logFile, "❌ Error al decodificar base64\n", FILE_APPEND);
                return false;
            }

            // 2️⃣ Crear carpeta si no existe
            $rutaCarpeta = __DIR__ . '/../../firmas_clientes/';
            if (!file_exists($rutaCarpeta)) {
                if (!mkdir($rutaCarpeta, 0777, true)) {
                    file_put_contents($logFile, "❌ Error al crear carpeta\n", FILE_APPEND);
                    return false;
                }
            }

            // 3️⃣ Guardar archivo
            $nombreArchivo = 'firma_' . $idservicio . '_' . time() . '.png';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            $rutaArchivoGuardar = "firmas_clientes/" . $nombreArchivo;

            if (file_put_contents($rutaArchivo, $imagen) === false) {
                file_put_contents($logFile, "❌ Error al guardar imagen\n", FILE_APPEND);
                return false;
            }

            file_put_contents($logFile, "✅ Imagen guardada: $rutaArchivoGuardar\n", FILE_APPEND);

            $tipoFirma = 'Recogida';


            // 🔎 4️⃣ Verificar si ya existe firma para ese servicio y tipo
            $sqlCheck = "SELECT id FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bind_param('is', $idservicio, $tipoFirma);
            $stmtCheck->execute();
            $result = $stmtCheck->get_result();

            if ($result->num_rows > 0) {
                // ✏️ YA EXISTE → HACER UPDATE
                $row = $result->fetch_assoc();
                $idFirma = $row['id'];

                file_put_contents($logFile, "🟠 Firma existente encontrada (ID=$idFirma), actualizando...\n", FILE_APPEND);

                $activoFirma=0;
                $sqlUpdate = "UPDATE firma_clientes 
                            SET firma_clientes = ?, fecha_registro = ? , activo_para_firmar = ?
                            WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->bind_param('ssii', $rutaArchivoGuardar, $fechaHoraColombia,$activoFirma, $idFirma);


                if (!$stmtUpdate->execute()) {
                    file_put_contents($logFile, "❌ Error en UPDATE: " . $stmtUpdate->error . "\n", FILE_APPEND);
                    return false;
                }
                                // 🔎 Obtener teléfono desde firma_clientes
                $sqlTel = "SELECT telefono,id_oper_responsable FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
                $stmtTel = $this->db->prepare($sqlTel);
                $stmtTel->bind_param('is', $idservicio, $tipoFirma);
                $stmtTel->execute();
                $resTel = $stmtTel->get_result();
                // $telefono = ($resTel->num_rows > 0) ? $resTel->fetch_assoc()['telefono'] : null;

                $id_oper = null;
                if ($resTel->num_rows > 0) {
                    $row1 = $resTel->fetch_assoc();
                    $telefono = $row1['telefono'];
                    $id_oper = $row1['id_oper_responsable'];
                }



                // 🔎 Obtener número de guía y teléfono del cliente desde servicios
                $sqlGuia = "SELECT ser_consecutivo, cli_telefono,ser_telefonocontacto FROM serviciosdia WHERE idservicios = ? LIMIT 1";
                $stmtGuia = $this->db->prepare($sqlGuia);
                $stmtGuia->bind_param('i', $idservicio);
                $stmtGuia->execute();
                $resGuia = $stmtGuia->get_result();

                $numguia = null;
                $cli_telefono = null;
                


                if ($resGuia->num_rows > 0) {
                    $row = $resGuia->fetch_assoc();
                    $numguia = $row['ser_consecutivo'];
                    $cli_telefono = $row['cli_telefono'];
                    $numDestinatario = $row['ser_telefonocontacto'];
                    
                }

                $oper_telefono = null;

                if (!empty($id_oper)) {

                    $sqlOper = "SELECT usu_celular FROM usuarios WHERE idusuarios = ? LIMIT 1";
                    $stmtOper = $this->db->prepare($sqlOper);

                    if ($stmtOper) {
                        $stmtOper->bind_param('i', $id_oper);
                        $stmtOper->execute();
                        $resOper = $stmtOper->get_result();

                        if ($resOper->num_rows > 0) {
                            $rowOper = $resOper->fetch_assoc();
                            $oper_telefono = $rowOper['usu_celular'];
                        }

                        $stmtOper->close();
                    }
                }

                // Normalizar teléfonos (quitar espacios, guiones, etc.)
                $telefono      = preg_replace('/\D/', '', $telefono);
                $cli_telefono  = preg_replace('/\D/', '', $cli_telefono);

                // 📲 Envío de alertas
                if (!empty($numguia)) {

                    // Si el teléfono del cliente contiene o es igual al principal
                    if (!empty($cli_telefono) && strpos($cli_telefono, $telefono) !== false) {

                        // Solo un envío
                        $this->enviarAlertaWhat($telefono, 42, $numguia . "R");

                    } else {

                        // Enviar al teléfono principal
                        $this->enviarAlertaWhat($telefono, 42, $numguia . "R");

                        // Y también al teléfono del cliente si existe y es diferente
                        if (!empty($cli_telefono)) {
                         $this->enviarAlertaWhat($cli_telefono, 42, $numguia . "R");
                        }
                    }
                    $this->enviarAlertaWhat($numDestinatario, 42, $numguia . "R");
                }
                //ALERTA AL OPERADOR
                // $this->enviarAlertaWhat($oper_telefono, 45, $numguia);
                file_put_contents($logFile, "✅ Firma insertada correctamente\n", FILE_APPEND);
                return true;

            } else {
                // ➕ NO EXISTE → INSERTAR
                file_put_contents($logFile, "🟢 No existe firma previa, insertando nueva...\n", FILE_APPEND);
                $activoFirma=0;
                $sqlInsert = "INSERT INTO firma_clientes (id_guia, tipo_firma, firma_clientes, fecha_registro,activo_para_firmar) 
                            VALUES (?, ?, ?, ?,?)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                $stmtInsert->bind_param('isssi', $idservicio, $tipoFirma, $rutaArchivoGuardar, $fechaHoraColombia,$activoFirma);


                if (!$stmtInsert->execute()) {
                    file_put_contents($logFile, "❌ Error en INSERT: " . $stmtInsert->error . "\n", FILE_APPEND);
                    return false;
                }

                // 🔎 Obtener teléfono desde firma_clientes
                $sqlTel = "SELECT telefono,id_oper_responsable FROM firma_clientes WHERE id_guia = ? AND tipo_firma = ? LIMIT 1";
                $stmtTel = $this->db->prepare($sqlTel);
                $stmtTel->bind_param('is', $idservicio, $tipoFirma);
                $stmtTel->execute();
                $resTel = $stmtTel->get_result();
                // $telefono = ($resTel->num_rows > 0) ? $resTel->fetch_assoc()['telefono'] : null;
                $id_oper = null;
                
                if ($resTel->num_rows > 0) {
                    $row1 = $resTel->fetch_assoc();
                    $telefono = $row1['telefono'];
                    $id_oper = $row1['id_oper_responsable'];
                }

            
               // 🔎 Obtener número de guía y teléfono del cliente desde servicios
                $sqlGuia = "SELECT ser_consecutivo, cli_telefono, ser_telefonocontacto FROM serviciosdia WHERE idservicios = ? LIMIT 1";
                $stmtGuia = $this->db->prepare($sqlGuia);
                $stmtGuia->bind_param('i', $idservicio);
                $stmtGuia->execute();
                $resGuia = $stmtGuia->get_result();

                $numguia = null;
                $cli_telefono = null;
              

                if ($resGuia->num_rows > 0) {
                    $row = $resGuia->fetch_assoc();
                    $numguia = $row['ser_consecutivo'];
                    $cli_telefono = $row['cli_telefono'];
                    $numDestinatario = $row['ser_telefonocontacto'];

                    
                }

                $oper_telefono = null;

                if (!empty($id_oper)) {

                    $sqlOper = "SELECT usu_celular FROM usuarios WHERE idusuarios = ? LIMIT 1";
                    $stmtOper = $this->db->prepare($sqlOper);

                    if ($stmtOper) {
                        $stmtOper->bind_param('i', $id_oper);
                        $stmtOper->execute();
                        $resOper = $stmtOper->get_result();

                        if ($resOper->num_rows > 0) {
                            $rowOper = $resOper->fetch_assoc();
                            $oper_telefono = $rowOper['usu_celular'];
                        }

                        $stmtOper->close();
                    }
                }

                // Normalizar teléfonos (quitar espacios, guiones, etc.)
                $telefono      = preg_replace('/\D/', '', $telefono);
                $cli_telefono  = preg_replace('/\D/', '', $cli_telefono);

                // 📲 Envío de alertas
                if (!empty($numguia)) {

                    // Si el teléfono del cliente contiene o es igual al principal
                    if (!empty($cli_telefono) && strpos($cli_telefono, $telefono) !== false) {

                        // Solo un envío
                        $this->enviarAlertaWhat($telefono, 42, $numguia . "R");

                    } else {

                        // Enviar al teléfono principal
                        $this->enviarAlertaWhat($telefono, 42, $numguia . "R");

                        // Y también al teléfono del cliente si existe y es diferente
                        if (!empty($cli_telefono)) {
                            $this->enviarAlertaWhat($cli_telefono, 42, $numguia . "R");
                        }
                    }

                    $this->enviarAlertaWhat($numDestinatario, 42, $numguia . "R");

                }
                //ALERTA AL OPERADOR
                // $this->enviarAlertaWhat($oper_telefono, 45, $numguia);

                file_put_contents($logFile, "✅ Firma insertada correctamente\n", FILE_APPEND);
                return true;
            }

            
            
        } catch (Exception $e) {
            file_put_contents($logFile, "❌ Excepción: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        } finally {
            file_put_contents($logFile, "=== FIN guardarFirmaRecogida() ===\n\n", FILE_APPEND);
        }
    }

        private function escribirLogFirma($mensaje)
    {
        $rutaLog = __DIR__ . '/logs_firma.txt'; // archivo de log
        $fecha = date('Y-m-d H:i:s');
        $linea = "[$fecha] $mensaje" . PHP_EOL;
        file_put_contents($rutaLog, $linea, FILE_APPEND);
    }
    public function servicioPuedeFirmar($idservicio, $accionFirma)
{
    try {
        $this->escribirLogFirma("=== INICIO validación firma ===");
        $this->escribirLogFirma("ID Servicio: $idservicio");
        $this->escribirLogFirma("Acción recibida: $accionFirma");

        $tipo = ($accionFirma == "guardarFirmaRecogida") ? "Recogida" : "Entrega";
        $this->escribirLogFirma("Tipo de firma interpretado: $tipo");

        $sql = "SELECT activo_para_firmar 
                FROM firma_clientes 
                WHERE id_guia = ? AND tipo_firma = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            $this->escribirLogFirma("ERROR preparando consulta: " . $this->db->error);
            return false;
        }

        $stmt->bind_param('is', $idservicio, $tipo);
        $this->escribirLogFirma("Ejecutando consulta con → id_guia=$idservicio, tipo_firma=$tipo");

        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();

        $this->escribirLogFirma("Resultado BD: " . json_encode($resultado));

        if (!$resultado) {
            $this->escribirLogFirma("No existe registro previo → PUEDE FIRMAR");
            return true;
        }

        $estado = $resultado['activo_para_firmar'];
        $this->escribirLogFirma("Valor activo_para_firmar: " . var_export($estado, true));

        if ($estado === null || $estado == 1) {
            $this->escribirLogFirma("Estado permite firma → PUEDE FIRMAR");
            return true;
        }

        $this->escribirLogFirma("Estado = 0 → YA FIRMADO, NO PUEDE FIRMAR");
        return false;

    } catch (Exception $e) {
        $this->escribirLogFirma("EXCEPCIÓN: " . $e->getMessage());
        return false;
    }
}
    

    public function enviarAlertaWhat($telefono, $tipo, $idservi)
        
    {
        $this->logEntrega("=== enviarAlertaWhat() ===");
        $this->logEntrega("Datos: tel=$telefono, tipo=$tipo, id=$idservi");

        $url = "https://bot.transmillas.com/whatsapp/Alertas/alertas.php";

        $payload = [
            "telefono"     => "$telefono",
            "id"     => "$idservi",
            "tipo_alerta"  => "$tipo"
            // "id_guia"      => "$idservi",
            // "imagen1"      => "$imagen1"
        ];

        $jsonData = json_encode($payload);

        $this->logEntrega("Payload enviado: $jsonData");

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer MiSuperToken123'
            ],
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            $this->logEntrega("ERROR cURL: $error");

            return [
                "ok" => false,
                "error" => $error
            ];
        }

        curl_close($curl);

        $this->logEntrega("Respuesta API: $response");

        $respDecoded = json_decode($response, true);

        return [
            "ok" => true,
            "response" => $respDecoded
        ];
    }
    public function logEntrega($msg) {
        $logFile = __DIR__ . "/../logs/logs_WF.log";
        $fecha = date("Y-m-d H:i:s");
        file_put_contents($logFile, "[$fecha] $msg\n", FILE_APPEND);
    }
}
