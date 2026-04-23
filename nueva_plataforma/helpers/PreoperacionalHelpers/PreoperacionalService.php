<?php
/**
 * PreoperacionalService - Maneja la lógica de negocio del preoperacional
 * 
 * Esta clase centraliza todas las operaciones de negocio relacionadas
 * con el preoperacional, separándolas del controlador.
 */

require_once __DIR__ . '/../../model/PreoperacionalModel.php';

class PreoperacionalService
{
    private $model;

    public function __construct()
    {
        $this->model = new PreoperacionalModel();
    }

    /**
     * Procesa el guardado de un registro preoperacional (nuevo o actualización)
     */
    public function guardarRegistro($postData, $files)
    {
        error_log("PreoperacionalService: guardarRegistro - Iniciando guardado");
        error_log("PreoperacionalService: guardarRegistro - Keys en postData: " . implode(', ', array_keys($postData)));
        error_log("PreoperacionalService: guardarRegistro - firma_preoperacional presente: " . (isset($postData['firma_preoperacional']) ? 'SI' : 'NO'));
        if (isset($postData['firma_preoperacional'])) {
            $firmaVal = $postData['firma_preoperacional'];
            error_log("PreoperacionalService: guardarRegistro - Longitud firma_preoperacional: " . strlen($firmaVal));
            error_log("PreoperacionalService: guardarRegistro - Primeros 50 chars firma: " . substr($firmaVal, 0, 50) . "...");
        }

        $estado = $postData['estado'] ?? '';
        $dataJson = $postData['data'] ?? '';
        $idVehiculo = !empty($postData['param1']) ? (int) $postData['param1'] : null;
        $tipoVehiculo = $postData['param2'] ?? '';
        $idUsuario = (int) ($postData['user'] ?? $_SESSION['usuario_id']);
        $fechaHora = date('Y-m-d H:i:s');
        $observaciones = $postData['param7'] ?? '';
        $accionCorrectiva = $postData['param8'] ?? '';
        $responsable = $postData['param9'] ?? '';
        $temperatura = $postData['param19'] ?? '';
        $kilometraje = (int) ($postData['param12'] ?? 0);
        $limpiomaleta = $postData['param21'] ?? '';
        $descValidada = $postData['param10'] ?? '';
        $idPre = (int) ($postData['param11'] ?? 0);

        // Procesar imágenes
        $imagenKilo = $this->procesarImagenKilometraje($files);
        $imagenInspeccion = $this->procesarImagenInspeccionInicial($files, $dataJson);

        // Procesar firma base64
        $firmaBase64 = $postData['firma_preoperacional'] ?? '';
        error_log("PreoperacionalService: guardarRegistro - Antes de procesarFirmaBase64, firmaBase64 vacío: " . (empty($firmaBase64) ? 'SI' : 'NO'));
        $firmaProcesada = $this->procesarFirmaBase64($firmaBase64, $idUsuario);
        error_log("PreoperacionalService: guardarRegistro - firmaProcesada: " . ($firmaProcesada ? 'EXITOSO' : 'FALLIDO'));

        if ($idPre > 0) {
            // Actualización (validación)
            return $this->actualizarRegistro(
                $idPre, $dataJson, $descValidada, $estado,
                $accionCorrectiva, $responsable, $temperatura,
                $kilometraje, $idVehiculo, $imagenKilo, $imagenInspeccion,
                $firmaProcesada
            );
        } else {
            // Nuevo registro
            return $this->insertarRegistro(
                $idVehiculo, $tipoVehiculo, $fechaHora, $idUsuario,
                $dataJson, $observaciones, $accionCorrectiva,
                $responsable, $temperatura, $kilometraje,
                $limpiomaleta, $imagenKilo, $estado, $files, $imagenInspeccion,
                $firmaProcesada
            );
        }
    }

    /**
     * Busca datos de precarga para un usuario en una fecha específica
     */
    public function buscarDatosPrecarga($idUser, $fecha, $campo)
    {
        if ($campo !== 'preencuesta') {
            return null;
        }
        return $this->model->obtenerDatosParaPrecarga($idUser, $fecha, $campo);
    }

    /**
     * Obtiene los datos del vehículo y usuario
     */
    public function obtenerDatosVehiculoYUsuario($idUsuario, $idVehiculo = null)
    {
        return $this->model->obtenerDatosVehiculoYUsuario($idUsuario, $idVehiculo);
    }

    /**
     * Obtiene un registro por fecha
     */
    public function obtenerRegistroPorFecha($idUsuario, $fecha)
    {
        return $this->model->obtenerRegistroPorFecha($idUsuario, $fecha);
    }

    /**
     * Obtiene un registro por ID
     */
    public function obtenerRegistroPorId($idPre)
    {
        return $this->model->obtenerRegistroPorId($idPre);
    }

    /**
     * Obtiene el último registro de un usuario
     */
    public function obtenerUltimoRegistro($idUsuario)
    {
        return $this->model->obtenerUltimoRegistro($idUsuario);
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Procesa la imagen de kilometraje
     */
    private function procesarImagenKilometraje($files)
    {
        if (isset($files['param30']) && $files['param30']['error'] === UPLOAD_ERR_OK) {
            $nombreArchivo = date("Y-m-d-H-i-s") . "_" . $files['param30']['name'];
            $rutaBase = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'preoperacional' . DIRECTORY_SEPARATOR;
            $ruta = $rutaBase . $nombreArchivo;

            // Debug: registrar ruta
            error_log("PreoperacionalService: Intentando mover imagen de kilometraje a: " . $ruta);
            error_log("PreoperacionalService: tmp_name: " . $files['param30']['tmp_name']);
            error_log("PreoperacionalService: Directorio existe: " . (is_dir($rutaBase) ? 'SI' : 'NO'));
            error_log("PreoperacionalService: Directorio escribible: " . (is_writable($rutaBase) ? 'SI' : 'NO'));

            if (move_uploaded_file($files['param30']['tmp_name'], $ruta)) {
                error_log("PreoperacionalService: Imagen de kilometraje guardada en: " . $ruta);
                return $ruta;
            } else {
                error_log("PreoperacionalService: ERROR al mover imagen de kilometraje");
                error_log("PreoperacionalService: error_get_last: " . print_r(error_get_last(), true));
            }
        }
        return '';
    }

    /**
     * Procesa la imagen de inspección inicial cuando se desmarca el checkbox
     * Devuelve array con estructura para guardarImagenDesdeRuta si hay imagen, o false si no
     */
    private function procesarImagenInspeccionInicial($files, $dataJson)
    {
        // Verificar si hay datos de encuesta
        $data = json_decode($dataJson, true);
        if (!$data) return false;

        // Buscar si inspec_1 NO está marcado (checkbox desmarcado = valor no presente o null)
        if (!isset($data['inspec_1']) || $data['inspec_1'] === null || $data['inspec_1'] === '0') {
            // Buscar archivo de foto con nombre inspec_1_foto
            if (isset($files['inspec_1_foto']) && $files['inspec_1_foto']['error'] === UPLOAD_ERR_OK) {
                $nombreArchivo = "inspeccion_" . date("Y-m-d-H-i-s") . "_" . $files['inspec_1_foto']['name'];
                $rutaTemporal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nombreArchivo;
                if (move_uploaded_file($files['inspec_1_foto']['tmp_name'], $rutaTemporal)) {
                    // Devolver array con estructura para guardarImagenDesdeRuta
                    return [
                        'tmp_name' => $rutaTemporal,
                        'name' => $files['inspec_1_foto']['name']
                    ];
                }
            }
        }
        return false;
    }

    /**
     * Procesa la firma en base64 y la guarda como archivo temporal
     * Devuelve array con estructura para guardarImagenDesdeRuta o false si no hay firma
     */
    private function procesarFirmaBase64($firmaBase64, $idUsuario)
    {
        error_log("PreoperacionalService: procesarFirmaBase64 - Iniciando procesamiento de firma");
        error_log("PreoperacionalService: procesarFirmaBase64 - ID Usuario: " . $idUsuario);
        error_log("PreoperacionalService: procesarFirmaBase64 - Longitud firmaBase64: " . strlen($firmaBase64));

        if (empty($firmaBase64)) {
            error_log("PreoperacionalService: procesarFirmaBase64 - firmaBase64 está vacío");
            return false;
        }

        // Verificar si comienza con 'data:image' (firma válida de canvas)
        if (strpos($firmaBase64, 'data:image') !== 0) {
            error_log("PreoperacionalService: procesarFirmaBase64 - No comienza con 'data:image'. Valor: " . substr($firmaBase64, 0, 50) . "...");
            return false;
        }

        // Extraer datos base64 (después de la coma)
        $commaPos = strpos($firmaBase64, ',');
        if ($commaPos === false) {
            error_log("PreoperacionalService: procesarFirmaBase64 - No se encontró coma en datos base64");
            return false;
        }

        $base64Data = substr($firmaBase64, $commaPos + 1);
        error_log("PreoperacionalService: procesarFirmaBase64 - Longitud datos base64: " . strlen($base64Data));

        $decodedData = base64_decode($base64Data);

        if ($decodedData === false) {
            error_log("PreoperacionalService: procesarFirmaBase64 - Error al decodificar base64");
            return false;
        }

        // Crear nombre de archivo único
        $nombreArchivo = "firma_" . $idUsuario . "_" . date("Y-m-d-H-i-s") . ".png";
        $rutaTemporal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nombreArchivo;

        error_log("PreoperacionalService: procesarFirmaBase64 - Ruta temporal: " . $rutaTemporal);
        error_log("PreoperacionalService: procesarFirmaBase64 - Directorio temporal escribible: " . (is_writable(sys_get_temp_dir()) ? 'SI' : 'NO'));

        // Guardar archivo temporal
        $bytesEscritos = file_put_contents($rutaTemporal, $decodedData);
        if ($bytesEscritos !== false) {
            error_log("PreoperacionalService: procesarFirmaBase64 - Archivo guardado exitosamente. Bytes: " . $bytesEscritos);
            error_log("PreoperacionalService: procesarFirmaBase64 - Archivo existe: " . (file_exists($rutaTemporal) ? 'SI' : 'NO'));
            error_log("PreoperacionalService: procesarFirmaBase64 - Tamaño archivo: " . filesize($rutaTemporal));

            return [
                'ruta' => $rutaTemporal,
                'nombre' => $nombreArchivo
            ];
        } else {
            error_log("PreoperacionalService: procesarFirmaBase64 - ERROR al guardar archivo temporal");
            error_log("PreoperacionalService: procesarFirmaBase64 - error_get_last: " . print_r(error_get_last(), true));
        }

        return false;
    }

    /**
     * Actualiza un registro existente
     */
    private function actualizarRegistro(
        $idPre, $dataJson, $descValidada, $estado,
        $accionCorrectiva, $responsable, $temperatura,
        $kilometraje, $idVehiculo, $imagenKilo, $imagenInspeccion,
        $firmaProcesada = false
    ) {
        global $_SESSION;

        $datosActualizar = [
            'prefechavalidacion' => date('Y-m-d H:i:s'),
            'predatosvalidados' => $dataJson,
            'pre_descvalidada' => $descValidada,
            'pre_iduservalida' => $_SESSION['usuario_id'],
            'preestado' => ($estado == 'covid19') ? 'Validado Covid19' : 'Validado',
            'pre_correctiva' => $accionCorrectiva,
            'pre_responsable' => $responsable,
            'pre_temperatura' => $temperatura,
            'pre_kilrecorridos' => $kilometraje
        ];

        $ok = $this->model->actualizarPreoperacional($idPre, $datosActualizar);

        if ($ok && $kilometraje > 0 && $idVehiculo > 0) {
            $this->model->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
        }

        if ($imagenKilo) {
            $this->model->actualizarImagenKilo($idPre, $imagenKilo);
        }

        // Guardar imagen de inspección si existe
        if ($imagenInspeccion) {
            // $imagenInspeccion es array con estructura fake de $_FILES (ya movido)
            if (is_array($imagenInspeccion) && isset($imagenInspeccion['tmp_name']) && isset($imagenInspeccion['name'])) {
                $this->model->guardarImagenDesdeRuta($imagenInspeccion['tmp_name'], $imagenInspeccion['name'], $idPre, 3); // Tipo 3 = inspección
            } else {
                // Fallback por compatibilidad
                $this->model->guardarImagen($imagenInspeccion, $idPre, 3);
            }
        }

        // Guardar firma si existe
        $firmaDocId = false;
        if ($firmaProcesada && is_array($firmaProcesada) && isset($firmaProcesada['ruta']) && isset($firmaProcesada['nombre'])) {
            $firmaDocId = $this->model->guardarImagenDesdeRuta($firmaProcesada['ruta'], $firmaProcesada['nombre'], $idPre, 4); // Tipo 4 = firma
            error_log("PreoperacionalService: actualizarRegistro - ID documento firma: " . ($firmaDocId ? $firmaDocId : 'ERROR'));
        }

        // Actualizar JSON con IDs de documentos si existen
        $jsonModificado = $this->agregarIdsDocumentosAlJson($dataJson, [
            'firma_documento_id' => $firmaDocId
        ]);

        // Si el JSON fue modificado, actualizar el registro
        if ($jsonModificado !== $dataJson) {
            error_log("PreoperacionalService: actualizarRegistro - Actualizando JSON con IDs de documentos");
            $datosJsonActualizar = [
                'predatosvalidados' => $jsonModificado
            ];
            $this->model->actualizarCamposPreoperacional($idPre, $datosJsonActualizar);
        }

        return ['success' => true, 'message' => 'Preoperacional actualizado correctamente'];
    }

    /**
     * Inserta un nuevo registro
     */
    private function insertarRegistro(
        $idVehiculo, $tipoVehiculo, $fechaHora, $idUsuario,
        $dataJson, $observaciones, $accionCorrectiva,
        $responsable, $temperatura, $kilometraje,
        $limpiomaleta, $imagenKilo, $estado, $files, $imagenInspeccion,
        $firmaProcesada = false
    ) {
        error_log("PreoperacionalService: insertarRegistro - Iniciando");
        error_log("PreoperacionalService: insertarRegistro - dataJson: " . ($dataJson ? substr($dataJson, 0, 200) . "..." : "VACÍO"));
        error_log("PreoperacionalService: insertarRegistro - firmaProcesada: " . ($firmaProcesada ? "SÍ" : "NO"));

        $datosInsert = [
            'prevehiculo' => $idVehiculo,
            'pretipovehiculo' => $tipoVehiculo,
            'prefechaingreso' => $fechaHora,
            'preidusuario' => $idUsuario,
            'preencuesta' => $dataJson,
            'pre_obsevaciones' => $observaciones,
            'pre_correctiva' => $accionCorrectiva,
            'pre_responsable' => $responsable,
            'pre_temperatura' => $temperatura,
            'pre_kilrecorridos' => $kilometraje,
            'pre_limpiomaleta' => $limpiomaleta,
            'pre_img_kilo' => $imagenKilo,
            'preestado' => ($estado == 'covid19') ? 'covid19' : 'pendiente'
        ];

        // Si hay imagen de inspección, agregarla a observaciones
        if ($imagenInspeccion) {
            $rutaInspeccion = is_array($imagenInspeccion) && isset($imagenInspeccion['tmp_name'])
                ? $imagenInspeccion['tmp_name']
                : (string) $imagenInspeccion;
            $datosInsert['pre_obsevaciones'] = $observaciones . "\n[FOTO INSPECCIÓN: " . $rutaInspeccion . "]";
        }

        $idInsertado = $this->model->insertarPreoperacional($datosInsert);
        error_log("PreoperacionalService: insertarRegistro - ID insertado: " . ($idInsertado ? $idInsertado : 'FALLO'));

        if ($idInsertado) {
            $idsDocumentos = [];

            // Imagen de temperatura
            $temperaturaDocId = false;
            if (isset($files['param20']) && $files['param20']['error'] === UPLOAD_ERR_OK) {
                $temperaturaDocId = $this->model->guardarImagen($files['param20'], $idInsertado, 2);
                if ($temperaturaDocId) {
                    $idsDocumentos['temperatura_documento_id'] = $temperaturaDocId;
                    error_log("PreoperacionalService: insertarRegistro - ID documento temperatura: " . $temperaturaDocId);
                }
            }

            // Imagen de kilometraje
            if ($imagenKilo) {
                $this->model->actualizarImagenKilo($idInsertado, $imagenKilo);
            }

            // Imagen de inspección inicial
            $inspeccionDocId = false;
            if ($imagenInspeccion) {
                // $imagenInspeccion es array con estructura fake de $_FILES (ya movido)
                if (is_array($imagenInspeccion) && isset($imagenInspeccion['tmp_name']) && isset($imagenInspeccion['name'])) {
                    $inspeccionDocId = $this->model->guardarImagenDesdeRuta($imagenInspeccion['tmp_name'], $imagenInspeccion['name'], $idInsertado, 3); // Tipo 3 = inspección
                } else {
                    // Fallback por compatibilidad
                    $inspeccionDocId = $this->model->guardarImagen($imagenInspeccion, $idInsertado, 3);
                }
                if ($inspeccionDocId) {
                    $idsDocumentos['inspeccion_documento_id'] = $inspeccionDocId;
                    error_log("PreoperacionalService: insertarRegistro - ID documento inspección: " . $inspeccionDocId);
                }
            }

            // Guardar firma si existe
            $firmaDocId = false;
            if ($firmaProcesada && is_array($firmaProcesada) && isset($firmaProcesada['ruta']) && isset($firmaProcesada['nombre'])) {
                $firmaDocId = $this->model->guardarImagenDesdeRuta($firmaProcesada['ruta'], $firmaProcesada['nombre'], $idInsertado, 4); // Tipo 4 = firma
                if ($firmaDocId) {
                    $idsDocumentos['firma_documento_id'] = $firmaDocId;
                    error_log("PreoperacionalService: insertarRegistro - ID documento firma: " . $firmaDocId);
                }
            }

            // Actualizar JSON con IDs de documentos si existen
            if (!empty($idsDocumentos)) {
                error_log("PreoperacionalService: insertarRegistro - Actualizando JSON con IDs de documentos");
                $jsonModificado = $this->agregarIdsDocumentosAlJson($dataJson, $idsDocumentos);

                // Actualizar el registro con el JSON modificado
                $datosJsonActualizar = [
                    'preencuesta' => $jsonModificado
                ];
                $this->model->actualizarCamposPreoperacional($idInsertado, $datosJsonActualizar);
            }

            if ($kilometraje > 0 && $idVehiculo > 0) {
                $this->model->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
            }
        }

        return ['success' => true, 'message' => 'Preoperacional guardado correctamente'];
    }

    /**
     * Agrega IDs de documentos al JSON de datos del preoperacional
     *
     * @param string $dataJson JSON original de datos
     * @param array $idsDocumentos Array asociativo con IDs de documentos (ej: ['firma_documento_id' => 123])
     * @return string JSON modificado con IDs de documentos
     */
    private function agregarIdsDocumentosAlJson(string $dataJson, array $idsDocumentos): string
    {
        error_log("PreoperacionalService: agregarIdsDocumentosAlJson - Iniciando");
        error_log("PreoperacionalService: agregarIdsDocumentosAlJson - JSON original: " . substr($dataJson, 0, 200) . "...");
        error_log("PreoperacionalService: agregarIdsDocumentosAlJson - IDs documentos: " . print_r($idsDocumentos, true));

        if (empty($dataJson) || $dataJson === '{}') {
            $dataArray = [];
        } else {
            $dataArray = json_decode($dataJson, true);
            if ($dataArray === null) {
                error_log("PreoperacionalService: agregarIdsDocumentosAlJson - ERROR: JSON inválido");
                return $dataJson;
            }
        }

        // Agregar IDs de documentos al array
        foreach ($idsDocumentos as $key => $value) {
            if ($value !== false && $value !== null && $value !== '') {
                $dataArray[$key] = $value;
                error_log("PreoperacionalService: agregarIdsDocumentosAlJson - Agregado: $key = $value");
            }
        }

        $jsonModificado = json_encode($dataArray, JSON_UNESCAPED_UNICODE);
        error_log("PreoperacionalService: agregarIdsDocumentosAlJson - JSON modificado: " . substr($jsonModificado, 0, 200) . "...");

        return $jsonModificado;
    }
}
