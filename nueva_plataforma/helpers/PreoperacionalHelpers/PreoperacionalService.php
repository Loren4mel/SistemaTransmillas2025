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
        // Mapeo retrocompatible: si se envían nombres nuevos del formato actualizado,
        // se usan directamente; si aún se envían paramX (legado/formato anterior), se migran.
        $legacyToNew = [
            'param1'  => 'idvehiculo',
            'param2'  => 'tipo_vehiculo',
            'param7'  => 'observaciones',
            'param8'  => 'accion_correctiva',
            'param9'  => 'responsable',
            'param10' => 'desc_validacion',
            'param11' => 'id_preoperacional',
            'param12' => 'kilometraje',
            'param13' => 'observaciones_validacion',
            'param30' => 'imagen_kilometraje',
        ];
        foreach ($legacyToNew as $legacy => $new) {
            if (!isset($postData[$new]) && isset($postData[$legacy])) {
                $postData[$new] = $postData[$legacy];
            }
        }

        $estado = $postData['estado'] ?? '';
        $dataJson = $postData['data'] ?? '';
        $idVehiculo = !empty($postData['idvehiculo']) ? (int) $postData['idvehiculo'] : null;
        $tipoVehiculo = $postData['tipo_vehiculo'] ?? '';
        $idUsuario = (int) ($postData['user'] ?? $_SESSION['usuario_id']);
        $fechaHora = date('Y-m-d H:i:s');
        $observaciones = $postData['observaciones'] ?? '';
        $accionCorrectiva = $postData['accion_correctiva'] ?? '';
        $responsable = !empty($postData['responsable']) ? $postData['responsable'] : ($_SESSION['usuario_nombre'] ?? '');
        $temperatura = $postData['param19'] ?? ''; // legacy: solo existe en formato legado
        $kilometraje = (int) ($postData['kilometraje'] ?? 0);
        $limpiomaleta = $postData['param21'] ?? ''; // legacy: solo existe en formato legado
        $descValidada = $postData['desc_validacion'] ?? '';
        $observacionesValidacion = $postData['observaciones_validacion'] ?? '';
        if (!empty($observacionesValidacion)) {
            $descValidada .= "\n\nObservaciones: " . $observacionesValidacion;
        }
        $idPre = (int) ($postData['id_preoperacional'] ?? 0);

        // Al actualizar (validación), preservar la ubicación GPS original
        if ($idPre > 0 && !empty($dataJson)) {
            $registroOriginal = $this->model->obtenerRegistroPorId($idPre);
            if ($registroOriginal && !empty($registroOriginal['preencuesta'])) {
                $originalData = json_decode($registroOriginal['preencuesta'], true);
                $newData = json_decode($dataJson, true);
                if (isset($originalData['ubicacion']) && !isset($newData['ubicacion'])) {
                    $newData['ubicacion'] = $originalData['ubicacion'];
                    $dataJson = json_encode($newData, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        // Procesar imágenes
        $imagenKilo = $this->procesarImagenKilometraje($files);
        $imagenInspeccion = $this->procesarImagenInspeccionInicial($files, $dataJson);

        // Validar que la imagen de kilometraje sea obligatoria (solo para vehículos CARRO/MOTO)
        $requiereImagenKilo = in_array(strtoupper($tipoVehiculo), ['CARRO', 'MOTO']);
        if ($requiereImagenKilo && empty($imagenKilo)) {
            // Si es actualización, verificar si ya existe imagen previa
            if ($idPre > 0) {
                $registroExistente = $this->model->obtenerRegistroPorId($idPre);
                if ($registroExistente && empty($registroExistente['pre_img_kilo'])) {
                    return ['success' => false, 'message' => 'Debe subir una foto del kilometraje del vehículo.'];
                }
            } else {
                // Nuevo registro: imagen obligatoria
                return ['success' => false, 'message' => 'Debe subir una foto del kilometraje del vehículo.'];
            }
        }

        // Procesar firma base64
        $firmaBase64 = $postData['firma_preoperacional'] ?? '';
        $firmaProcesada = $this->procesarFirmaBase64($firmaBase64, $idUsuario);

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

    /**
     * Obtiene el documento de firma asociado a un preoperacional
     */
    public function obtenerDocumentoFirma($idPreoperacional)
    {
        return $this->model->obtenerDocumentoFirma($idPreoperacional);
    }

    // ==================== DETECCIÓN DE FORMATO ====================

    /**
     * Detecta el formato de encuesta basado en los datos almacenados
     *
     * @param string $dataJson JSON de datos de la encuesta
     * @return string 'nuevo' o 'legado'
     */
    public function detectarFormato($dataJson)
    {
        $data = json_decode($dataJson, true);

        if (!$data) {
            return 'legado';
        }

        $clavesNuevas = ['admin_', 'conductor_', 'inspec_', 'luces_', 'cabina_', 'seguridad_', 'indicador_', 'moto_personal_', 'moto_llanta_', 'moto_trans_', 'auxiliar_'];
        foreach ($clavesNuevas as $clave) {
            foreach (array_keys($data) as $key) {
                if (strpos($key, $clave) !== false) {
                    return 'nuevo';
                }
            }
        }

        $clavesLegado = ['llantas1', 'transmision1', 'Luces1', 'direccionales1', 'cabina1'];
        foreach ($clavesLegado as $clave) {
            if (isset($data[$clave])) {
                return 'legado';
            }
        }

        return 'legado';
    }

    // ==================== VALIDACIÓN DE DOCUMENTOS DEL VEHÍCULO ====================

    /**
     * Clasifica días restantes en nivel de alerta.
     * severity: 3=expirado, 2=crítico(≤7d), 1=advertencia(≤30d), 0=normal.
     */
    private function clasificarDias(int $dias): array
    {
        if ($dias < 0)      return ['color' => '#F44336', 'severity' => 3];
        if ($dias <= 7)     return ['color' => '#F44336', 'severity' => 2];
        if ($dias <= 30)    return ['color' => '#FF9800', 'severity' => 1];
        return              ['color' => '#555',    'severity' => 0];
    }

    /**
     * Calcula los días entre hoy y una fecha dada.
     */
    private function diasHasta(string $hoy, ?string $fecha): ?int
    {
        if (!$fecha || $fecha === '0000-00-00')
            return null;
        $hoyTs = strtotime($hoy);
        $fechaTs = strtotime($fecha);
        return (int) round(($fechaTs - $hoyTs) / 86400);
    }

    /**
     * Obtiene el estado de los documentos del vehículo (licencia, seguro, tecnicomecánica).
     * Retorna array con info de alerta y si alguno está expirado.
     */
    public function getEstadoDocumentosVehiculo(array $datosVehiculo): array
    {
        $hoy = date('Y-m-d');

        $diasLic = $this->diasHasta($hoy, $datosVehiculo['usu_fechalicencia'] ?? null);
        $diasSeguro = $this->diasHasta($hoy, $datosVehiculo['veh_fechaseguro'] ?? null);
        $diasTecno = $this->diasHasta($hoy, $datosVehiculo['veh_fechategnomecanica'] ?? null);

        $tipos = [
            'licencia' => [
                'nombre' => 'Licencia',
                'dias' => $diasLic,
                'fecha' => $datosVehiculo['usu_fechalicencia'] ?? null
            ],
            'seguro' => [
                'nombre' => 'Seguro',
                'dias' => $diasSeguro,
                'fecha' => $datosVehiculo['veh_fechaseguro'] ?? null
            ],
            'tecno' => [
                'nombre' => 'Tecnicomecánica',
                'dias' => $diasTecno,
                'fecha' => $datosVehiculo['veh_fechategnomecanica'] ?? null
            ]
        ];

        $expired = false;
        $maxSeverity = 0;
        $alertas = [];

        foreach ($tipos as $key => $info) {
            if ($info['dias'] === null) continue;

            $clasif = $this->clasificarDias($info['dias']);
            $clasif['tipo'] = $key;
            $clasif['nombre'] = $info['nombre'];
            $clasif['dias'] = $info['dias'];
            $clasif['fecha'] = $info['fecha'];

            if ($clasif['severity'] > $maxSeverity) {
                $maxSeverity = $clasif['severity'];
            }
            if ($clasif['severity'] >= 3) {
                $expired = true;
            }

            $alertas[] = $clasif;
        }

        return [
            'alertas' => $alertas,
            'expired' => $expired,
            'max_severity' => $maxSeverity,
            'bloquear' => false
        ];
    }

    /**
     * Genera el HTML del panel de alerta de documentos para mostrar debajo de la tarjeta del vehículo.
     * El panel lista todos los documentos con alertas y su severidad.
     */
    public function generarHtmlAlertasVehiculo(array $estadoDocumentos): string
    {
        $alertas = $estadoDocumentos['alertas'] ?? [];
        if (empty($alertas))
            return '';

        $alertasVisibles = array_filter($alertas, function ($a) {
            return $a['severity'] >= 1;
        });
        if (empty($alertasVisibles))
            return '';

        $maxSeverity = $estadoDocumentos['max_severity'] ?? 0;

        $severityLabels = [
            1 => 'warning',
            2 => 'critical',
            3 => 'expired'
        ];
        $severityClass = $severityLabels[$maxSeverity] ?? 'warning';

        $iconMap = [
            1 => 'fa-clock',
            2 => 'fa-exclamation-circle',
            3 => 'fa-exclamation-triangle'
        ];
        $titleMap = [
            1 => 'Documentos próximos a vencer',
            2 => 'Documentos críticos por vencer',
            3 => 'Documentos vencidos'
        ];

        $icon = $iconMap[$maxSeverity] ?? 'fa-exclamation-circle';
        $title = $titleMap[$maxSeverity] ?? 'Alertas de documentos';

        $html = '<div class="vehiculo-docs-panel severity-' . $severityClass . '">';
        $html .= '<div class="vehiculo-docs-panel-header">';
        $html .= '<i class="fas ' . $icon . '"></i> ';
        $html .= '<strong>' . $title . '</strong>';
        $html .= '</div>';
        $html .= '<ul class="vehiculo-docs-panel-list">';

        foreach ($alertas as $a) {
            if ($a['severity'] < 1)
                continue;

            $itemClass = $severityLabels[$a['severity']] ?? '';
            $d = $a['dias'];

            if ($d < 0) {
                $badge = '<span class="doc-badge badge-expired">EXPIRADA</span>';
                $detail = 'hace ' . abs($d) . ' días';
            } elseif ($d <= 7) {
                $badge = '<span class="doc-badge badge-critical">CRÍTICO</span>';
                $detail = 'expira en ' . $d . ' días';
            } else {
                $badge = '<span class="doc-badge badge-warning">POR VENCER</span>';
                $detail = 'expira en ' . $d . ' días';
            }

            $html .= '<li class="doc-item ' . $itemClass . '">';
            $html .= '<span class="doc-name">' . htmlspecialchars($a['nombre']) . '</span>';
            $html .= '<span class="doc-status">' . $badge . ' ' . $detail;
            $html .= ' <small>(' . htmlspecialchars($a['fecha']) . ')</small></span>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        if ($maxSeverity >= 3) {
            $html .= '<div class="vehiculo-docs-panel-footer">';
            $html .= '<i class="fas fa-info-circle"></i> ';
            $html .= 'Comuníquese con el jefe de operaciones para actualizar los documentos.';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Procesa la imagen de kilometraje
     */
    private function procesarImagenKilometraje($files)
    {
        // Aceptar tanto el nombre nuevo (imagen_kilometraje) como el legacy (param30)
        $fileKey = isset($files['imagen_kilometraje']) ? 'imagen_kilometraje' : 'param30';
        if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $nombreArchivo = date("Y-m-d-H-i-s") . "_" . $files[$fileKey]['name'];
            $rutaBase = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pre-operacional' . DIRECTORY_SEPARATOR;
            $ruta = $rutaBase . $nombreArchivo;

            if (move_uploaded_file($files[$fileKey]['tmp_name'], $ruta)) {
                return $ruta;
            } else {
                error_log("PreoperacionalService: ERROR al mover imagen de kilometraje");
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
        if (empty($firmaBase64)) {
            return false;
        }

        // Verificar si comienza con 'data:image' (firma válida de canvas)
        if (strpos($firmaBase64, 'data:image') !== 0) {
            return false;
        }

        // Extraer datos base64 (después de la coma)
        $commaPos = strpos($firmaBase64, ',');
        if ($commaPos === false) {
            return false;
        }

        $base64Data = substr($firmaBase64, $commaPos + 1);
        $decodedData = base64_decode($base64Data);

        if ($decodedData === false) {
            return false;
        }

        // Crear nombre de archivo único
        $nombreArchivo = "firma_" . $idUsuario . "_" . date("Y-m-d-H-i-s") . ".png";
        $rutaTemporal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nombreArchivo;

        // Guardar archivo temporal
        $bytesEscritos = file_put_contents($rutaTemporal, $decodedData);
        if ($bytesEscritos !== false) {
            return [
                'ruta' => $rutaTemporal,
                'nombre' => $nombreArchivo
            ];
        }

        error_log("PreoperacionalService: Error al guardar archivo temporal de firma");
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
            'pre_descvalidada' => $_SESSION['usuario_nombre'] . " - " . $descValidada,
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
        }

        // Actualizar JSON con IDs de documentos si existen
        $jsonModificado = $this->agregarIdsDocumentosAlJson($dataJson, [
            'firma_documento_id' => $firmaDocId
        ]);

        // Si el JSON fue modificado, actualizar el registro
        if ($jsonModificado !== $dataJson) {
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

        if ($idInsertado) {
            $idsDocumentos = [];

            // Imagen de temperatura
            $temperaturaDocId = false;
            if (isset($files['param20']) && $files['param20']['error'] === UPLOAD_ERR_OK) {
                $temperaturaDocId = $this->model->guardarImagen($files['param20'], $idInsertado, 2);
                if ($temperaturaDocId) {
                    $idsDocumentos['temperatura_documento_id'] = $temperaturaDocId;
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
                }
            }

            // Guardar firma si existe
            $firmaDocId = false;
            if ($firmaProcesada && is_array($firmaProcesada) && isset($firmaProcesada['ruta']) && isset($firmaProcesada['nombre'])) {
                $firmaDocId = $this->model->guardarImagenDesdeRuta($firmaProcesada['ruta'], $firmaProcesada['nombre'], $idInsertado, 4); // Tipo 4 = firma
                if ($firmaDocId) {
                    $idsDocumentos['firma_documento_id'] = $firmaDocId;
                }
            }

            // Actualizar JSON con IDs de documentos si existen
            if (!empty($idsDocumentos)) {
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
        if (empty($dataJson) || $dataJson === '{}') {
            $dataArray = [];
        } else {
            $dataArray = json_decode($dataJson, true);
            if ($dataArray === null) {
                return $dataJson;
            }
        }

        foreach ($idsDocumentos as $key => $value) {
            if ($value !== false && $value !== null && $value !== '') {
                $dataArray[$key] = $value;
            }
        }

        return json_encode($dataArray, JSON_UNESCAPED_UNICODE);
    }
}
