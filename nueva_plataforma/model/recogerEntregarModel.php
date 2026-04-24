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
    public function guardarFirmaEntrega($idservicio, $firmaBase64, $origenFirma = 'firma')
    {
        date_default_timezone_set('America/Bogota');
        $fechaHoraColombia = date('Y-m-d H:i:s');
        try {
            $origenFirma = strtolower((string)$origenFirma);
            if (!in_array($origenFirma, ['firma', 'sello'], true)) {
                $origenFirma = 'firma';
            }

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
            $prefijoArchivo = ($origenFirma === 'sello') ? 'sello' : 'firma';
            $nombreArchivo = $prefijoArchivo . '_' . $idservicio . '_' . time() . '.png';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            $rutaArchivoGuardar="firmas_clientes/".$nombreArchivo;

            // Guardar archivo en el servidor
            file_put_contents($rutaArchivo, $imagen);


            // 🔹 Guardar firma del cliente (INSERT seguro con bind_param)
            $sqlInsert = "INSERT INTO firma_clientes (id_guia, tipo_firma, firma_clientes,fecha_registro,tipo)
                        VALUES (?, ?, ?,?,?)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            // Verificamos que la preparación no falle
            if (!$stmtInsert) {
                error_log("❌ Error al preparar la consulta: " . $this->db->error);
                return false;
            }

            // Asignamos los valores
            $tipoFirma = 'Entrega';

            // Vinculamos los parámetros: id_guia (int), tipo_firma (string), firma_clientes (string)
            $stmtInsert->bind_param('issss', $idservicio, $tipoFirma, $rutaArchivoGuardar,$fechaHoraColombia,$origenFirma);



            // Ejecutamos la sentencia
            $stmtInsert->execute();

            // Verificamos si se insertó correctamente
            if ($stmtInsert->affected_rows > 0) {
                return true;
            } else {
                error_log("⚠️ No se insertó ninguna fila para id_guia $idservicio");
                return false;
            }

        } catch (Exception $e) {
            error_log("❌ Error al guardar la firma: " . $e->getMessage());
            return false;
        }
    }
    public function guardarFirmaRecogida($idservicio, $firmaBase64, $origenFirma = 'firma')
    {
        date_default_timezone_set('America/Bogota');
        $fechaHoraColombia = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/debug_guardar_firma.log';
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] === INICIO guardarFirmaRecogida() ===\n", FILE_APPEND);

        try {
            $origenFirma = strtolower((string)$origenFirma);
            if (!in_array($origenFirma, ['firma', 'sello'], true)) {
                $origenFirma = 'firma';
            }

            file_put_contents($logFile, "🟡 Paso 1: Recibido idservicio=$idservicio\n", FILE_APPEND);

            // 🔹 1️⃣ Convertir base64 a imagen
            $firmaData = str_replace('data:image/png;base64,', '', $firmaBase64);
            $firmaData = str_replace(' ', '+', $firmaData);
            $imagen = base64_decode($firmaData);

            if ($imagen === false) {
                file_put_contents($logFile, "❌ Error al decodificar base64\n", FILE_APPEND);
                return false;
            }

            // 🔹 2️⃣ Crear carpeta si no existe
            // $rutaCarpeta = __DIR__ . '/../../uploads/firmas_clientes/';
            $rutaCarpeta = __DIR__ . '/../../firmas_clientes/';

            file_put_contents($logFile, "🟡 Paso 2: Verificando carpeta $rutaCarpeta\n", FILE_APPEND);

            if (!file_exists($rutaCarpeta)) {
                if (!mkdir($rutaCarpeta, 0777, true)) {
                    file_put_contents($logFile, "❌ Error al crear la carpeta $rutaCarpeta\n", FILE_APPEND);
                    return false;
                }
                file_put_contents($logFile, "✅ Carpeta creada correctamente\n", FILE_APPEND);
            }

            // 🔹 3️⃣ Guardar archivo
            $prefijoArchivo = ($origenFirma === 'sello') ? 'sello' : 'firma';
            $nombreArchivo = $prefijoArchivo . '_' . $idservicio . '_' . time() . '.png';
            $rutaArchivo = $rutaCarpeta . $nombreArchivo;
            $rutaArchivoGuardar="firmas_clientes/".$nombreArchivo;
            file_put_contents($logFile, "🟡 Paso 3: Guardando archivo $rutaArchivo\n", FILE_APPEND);

            $bytesEscritos = file_put_contents($rutaArchivo, $imagen);

            if ($bytesEscritos === false) {
                file_put_contents($logFile, "❌ Error al escribir la imagen en $rutaArchivo\n", FILE_APPEND);
                return false;
            }

            file_put_contents($logFile, "✅ Archivo guardado correctamente ($bytesEscritos bytes)\n", FILE_APPEND);

            // 🔹 4️⃣ Preparar consulta SQL
            $sqlInsert = "INSERT INTO firma_clientes (id_guia, tipo_firma, firma_clientes,fecha_registro,tipo) VALUES (?, ?, ?,?,?)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            if (!$stmtInsert) {
                file_put_contents($logFile, "❌ Error al preparar la consulta SQL: " . $this->db->error . "\n", FILE_APPEND);
                return false;
            }

            $tipoFirma = 'Recogida';
            file_put_contents($logFile, "🟡 Paso 4: Vinculando parámetros (id=$idservicio, tipo=$tipoFirma, archivo=$rutaArchivo)\n", FILE_APPEND);

            $stmtInsert->bind_param('issss', $idservicio, $tipoFirma, $rutaArchivoGuardar,$fechaHoraColombia,$origenFirma);

            // 🔹 5️⃣ Ejecutar consulta
            $resultado = $stmtInsert->execute();
            file_put_contents($logFile, "🟡 Paso 5: Ejecutando consulta...\n", FILE_APPEND);

            if (!$resultado) {
                file_put_contents($logFile, "❌ Error en execute(): " . $stmtInsert->error . "\n", FILE_APPEND);
                return false;
            }

            // 🔹 6️⃣ Verificar resultado
            if ($stmtInsert->affected_rows > 0) {
                file_put_contents($logFile, "✅ Firma insertada correctamente para id_guia=$idservicio\n", FILE_APPEND);
                return true;
            } else {
                file_put_contents($logFile, "⚠️ No se insertó ninguna fila (affected_rows=0)\n", FILE_APPEND);
                return false;
            }

        } catch (Exception $e) {
            file_put_contents($logFile, "❌ Excepción capturada: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        } finally {
            file_put_contents($logFile, "=== FIN guardarFirmaRecogida() ===\n\n", FILE_APPEND);
        }
    }
}
