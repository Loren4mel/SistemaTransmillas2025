<?php
/**
 * PreoperacionalService - Maneja la lógica de negocio del preoperacional
 * 
 * Esta clase centraliza todas las operaciones de negocio relacionadas
 * con el preoperacional, separándolas del controlador.
 */

require_once __DIR__ . '/../../../model/PreoperacionalModel.php';
require_once __DIR__ . '/../../../model/VehiculosModel.php';
require_once __DIR__ . '/../Views/PreoperacionalNuevaEncuestaViewHelper.php';

class PreoperacionalService
{
    private $model;

    public function __construct()
    {
        $this->model = new PreoperacionalModel();
    }

    /**
     * Códigos internos de todas las preguntas de la sección VEHÍCULO (carro y moto).
     * Se usa para identificar qué respuestas requieren foto+observación al ser NO.
     */
    const CODIGOS_VEHICULO = [
        'inspec_1', 'luces_1', 'cabina_1', 'cabina_2',
        'seguridad_1', 'seguridad_2', 'seguridad_3', 'seguridad_4', 'seguridad_5',
        'seguridad_6', 'seguridad_7', 'seguridad_8', 'seguridad_9', 'seguridad_10',
        'indicador_1', 'indicador_2', 'indicador_3', 'indicador_4',
        'llanta_1', 'llanta_2',
        'moto_llanta_1', 'moto_trans_1', 'moto_trans_2', 'moto_luz_1', 'moto_luz_2',
        'moto_luz_3', 'moto_fuga_1', 'moto_mando_1', 'moto_mando_2',
        'moto_entorno_1', 'moto_entorno_2', 'moto_entorno_3', 'moto_epp_1'
    ];

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

        // Detectar si el vehículo está FUERA_DE_SERVICIO (edge case)
        $vehiculoFueraDeServicio = false;
        if ($idVehiculo > 0) {
            $estadoVehiculo = $this->obtenerEstadoVehiculo($idVehiculo);
            $vehiculoFueraDeServicio = ($estadoVehiculo['estado_general'] ?? '') === 'FUERA_DE_SERVICIO';
            if ($vehiculoFueraDeServicio) {
                // Agregar nota informativa a las observaciones
                $observaciones = trim($observaciones . "\n[ATENCIÓN: El vehículo se encontraba FUERA DE SERVICIO al momento del registro.]");
            }
        }

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

        // Validar que la imagen de kilometraje sea obligatoria (solo para vehículos CARRO/MOTO).
        // Además, debe existir un vehículo asignado — después de reportar FUERA_DE_SERVICIO
        // sin alternativa, el tipo de vehículo del perfil puede ser CARRO/MOTO pero no hay
        // vehículo que inspeccionar, y el formulario no incluye el campo de kilometraje.
        // EXCEPCIÓN: vehículo FUERA_DE_SERVICIO — el conductor será redirigido a otro vehículo.
        if (!$vehiculoFueraDeServicio && $idVehiculo > 0) {
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
        }

        // Procesar firma base64
        $firmaBase64 = $postData['firma_preoperacional'] ?? '';
        $firmaProcesada = $this->procesarFirmaBase64($firmaBase64, $idUsuario);

        // ==================== VALIDACIÓN DE KILOMETRAJE ====================
        // Verifica que el kilometraje enviado no sea menor al actual del vehículo.
        // FASE 1 (actual): solo warning no bloqueante — se permite guardar pero se notifica.
        // FASE 2 (futura): descomentar el return con error para bloquear kilometrajes retroactivos
        //                  una vez migrada completamente la infraestructura de vehículos.
        $warningKilometraje = null;
        if ($kilometraje > 0 && $idVehiculo > 0) {
            $kmActual = $this->model->obtenerKilometrajeVehiculo($idVehiculo);
            if ($kmActual > 0 && $kilometraje < $kmActual) {
                $warningKilometraje = "El kilometraje ingresado ({$kilometraje} km) es menor al kilometraje "
                    . "actual del vehículo ({$kmActual} km). Verifique que el valor sea correcto.";
                error_log("PreoperacionalService: KM_ADVERTENCIA — Vehículo #{$idVehiculo}: "
                    . "enviado={$kilometraje} < actual={$kmActual} (usuario={$idUsuario})");

                // ===== BLOQUEO DURO (FASE 2 — descomentar cuando la infraestructura esté migrada) =====
                // return ['success' => false, 'message' => $warningKilometraje];
                // ====================================================================================
            }
        }

        if ($idPre > 0) {
            // Actualización (validación)
            return $this->actualizarRegistro(
                $idPre, $dataJson, $descValidada,
                $accionCorrectiva, $responsable, $temperatura,
                $kilometraje, $idVehiculo, $imagenKilo, $imagenInspeccion,
                $firmaProcesada,
                $files,
                $vehiculoFueraDeServicio,
                $warningKilometraje
            );
        } else {
            // Nuevo registro
            return $this->insertarRegistro(
                $idVehiculo, $tipoVehiculo, $fechaHora, $idUsuario,
                $dataJson, $observaciones, $accionCorrectiva,
                $responsable, $temperatura, $kilometraje,
                $limpiomaleta, $imagenKilo, $files, $imagenInspeccion,
                $firmaProcesada,
                $vehiculoFueraDeServicio,
                $warningKilometraje
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
     * Obtiene el tipo de vehículo del perfil del usuario.
     * Fallback cuando el usuario no tiene vehículo asignado.
     *
     * @param int $idUsuario
     * @return string|null 'Carro', 'Moto' o null
     */
    public function obtenerTipoVehiculoUsuario($idUsuario)
    {
        return $this->model->obtenerTipoVehiculoUsuario($idUsuario);
    }

    /**
     * Obtiene el rol de un usuario por ID.
     *
     * @param int $idUsuario
     * @return int|null
     */
    public function obtenerRolUsuario($idUsuario)
    {
        return $this->model->obtenerRolUsuario($idUsuario);
    }

    // [ELIMINADO] obtenerEntregasPendientesPorUsuario() — se eliminó la integración con entregavehiculo

    /**
     * Obtiene el documento de firma asociado a un preoperacional
     */
    public function obtenerDocumentoFirma($idPreoperacional)
    {
        return $this->model->obtenerDocumentoFirma($idPreoperacional);
    }

    /**
     * Obtiene TODAS las respuestas de un preoperacional (todas las versiones).
     * Necesario porque el registro almacena solo un id_version pero las respuestas
     * existen en múltiples versiones (vehículo + usuario).
     *
     * @param int $idPreoperacional
     * @return array [codigo_interno => respuesta_dada]
     */
    public function obtenerTodasRespuestas($idPreoperacional)
    {
        return $this->model->obtenerTodasRespuestas($idPreoperacional);
    }

    /**
     * Obtiene los IDs de documentos asociados a un preoperacional, indexados
     * por clave semántica. Sustituye la dependencia del JSON de preencuesta.
     *
     * @param int $idPreoperacional
     * @return array [firma_documento_id, inspeccion_documento_id, temperatura_documento_id]
     */
    public function obtenerDocumentosPorPreoperacional($idPreoperacional)
    {
        return $this->model->obtenerDocumentosPorPreoperacional($idPreoperacional);
    }

    /**
     * Obtiene los documentos de novedad (doc_version=5) asociados a un seguimiento vehiculo.
     * Recorre: seguimiento_vehiculo -> pre-operacional -> documentos
     *
     * @param int $idSeguimiento ID del registro en seguimiento_vehiculo
     * @return array Lista de documentos
     */
    public function obtenerDocumentosNovedadPorSeguimiento($idSeguimiento)
    {
        return $this->model->obtenerDocumentosNovedadPorSeguimiento($idSeguimiento);
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

    // ==================== ESQUEMA RELACIONAL: RESOLUCIÓN DE VERSIONES ====================

    /**
     * Resuelve las versiones activas aplicables al usuario según su rol y tipo de vehículo.
     * Busca tanto plantillas USUARIO como VEHICULO.
     *
     * @param string $tipoVehiculo CARRO, MOTO o vacío
     * @param int $rolUsuario ID del rol del usuario
     * @return array [id_version_vehiculo, id_version_usuario, ids_plantillas]
     */
    public function resolverVersionesAplicables($tipoVehiculo, $rolUsuario)
    {
        $resultado = [
            'version_vehiculo' => null,
            'version_usuario' => null,
            'ids_versiones' => []
        ];

        if (!empty($tipoVehiculo)) {
            $ver = $this->model->obtenerVersionActivaVehiculo($tipoVehiculo);
            if ($ver) {
                $resultado['version_vehiculo'] = $ver;
                $resultado['ids_versiones'][] = $ver['id_version'];
            }
        }

        $verUsr = $this->model->obtenerVersionActivaUsuario($rolUsuario, $tipoVehiculo);
        if ($verUsr) {
            $resultado['version_usuario'] = $verUsr;
            // Evitar duplicado si casualmente es la misma versión
            if (!in_array($verUsr['id_version'], $resultado['ids_versiones'])) {
                $resultado['ids_versiones'][] = $verUsr['id_version'];
            }
        }

        return $resultado;
    }

    /**
     * Guarda las respuestas individuales usando multi-row INSERT (optimizado).
     *
     * @param int $idPreoperacional
     * @param array $respuestasFormulario [codigo_interno => valor]
     * @param int $idVersion
     * @return bool
     */
    public function guardarRespuestasRelacionales($idPreoperacional, $respuestasFormulario, $idVersion, $files = [])
    {
        // Cargar mapping codigo_interno => id_pregunta desde DB
        $mapping = $this->model->obtenerMappingCodigosAPreguntas($idVersion);
        if (empty($mapping)) {
            error_log("PreoperacionalService: No se encontró mapping para id_version=$idVersion");
            return false;
        }

        // Construir batch de respuestas
        $batch = [];
        foreach ($respuestasFormulario as $codigo => $valor) {
            // Saltar campos que no son preguntas (ubicacion, IDs de documentos, etc.)
            if (!isset($mapping[$codigo])) {
                continue;
            }
            // Saltar valores vacíos
            if ($valor === null || $valor === '') {
                continue;
            }
            // Saltar claves de observación (terminan en _obs)
            if (substr($codigo, -4) === '_obs') {
                continue;
            }

            $rutaFoto = null;

            // Si es una pregunta vehicular con NO, procesar la foto
            if ($valor == '2' && in_array($codigo, self::CODIGOS_VEHICULO)) {
                // Buscar foto subida para esta pregunta
                $fileKey = $codigo . '_foto';
                if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $rutaFoto = $this->procesarImagenPreguntaVehiculo($files[$fileKey], $codigo, $idPreoperacional);
                }
            }

            // Obtener observación del formulario (enviada como {codigo}_obs)
            $observacion = $respuestasFormulario[$codigo . '_obs'] ?? null;

            $batch[] = [
                'id_preoperacional' => $idPreoperacional,
                'id_pregunta' => $mapping[$codigo],
                'respuesta_dada' => (string) $valor,
                'ruta_foto' => $rutaFoto,
                'observacion' => $observacion
            ];
        }

        if (empty($batch)) {
            return false;
        }

        return $this->model->insertarRespuestasBatch($batch);
    }

    /**
     * Calcula el estado general del vehículo basado en las respuestas.
     *
     * @param array $respuestas [codigo_interno => valor]
     * @return string 'OPTIMO', 'CON_NOVEDADES' o 'FUERA_DE_SERVICIO'
     */
    public function calcularEstadoGeneral($respuestas)
    {
        // Si inspec_1 es NO (valor '2'), el vehículo está FUERA_DE_SERVICIO
        if (isset($respuestas['inspec_1']) && $respuestas['inspec_1'] == '2') {
            return 'FUERA_DE_SERVICIO';
        }

        // Si alguna respuesta es NO (valor '2') en preguntas de seguridad críticas
        $criticasSeguridad = ['seguridad_3', 'seguridad_4', 'seguridad_5', 'seguridad_7'];
        foreach ($criticasSeguridad as $codigo) {
            if (isset($respuestas[$codigo]) && $respuestas[$codigo] == '2') {
                return 'CON_NOVEDADES';
            }
        }

        // Si cualquier otra pregunta es NO
        foreach ($respuestas as $key => $valor) {
            // Saltar campos no-pregunta
            if (in_array($key, ['ubicacion', 'firma_documento_id', 'inspeccion_documento_id', 'temperatura_documento_id'])) {
                continue;
            }
            if ($valor == '2') {
                return 'CON_NOVEDADES';
            }
        }

        return 'OPTIMO';
    }

    // SEGUIMIENTO VEHICULO — ACTIVO
    public function guardarSeguimientoVehiculo($datos)
    {
        return $this->model->insertarSeguimientoVehiculo($datos);
    }

    /**
     * Obtiene el estado actual del vehículo basado en su último seguimiento.
     * Retorna información de novedad si el vehículo está FUERA_DE_SERVICIO o CON_NOVEDADES.
     *
     * @param int $idVehiculo ID del vehículo
     * @return array [tieneNovedad, estado_general, observaciones, ultimoSeguimiento]
     */
    public function obtenerEstadoVehiculo($idVehiculo)
    {
        // PRIORIDAD 1: REVISION_SST es la fuente autoritativa del estado del vehículo.
        // metadata_evento es exclusivo de REVISION_SST (único tipo que requiere datos de contexto).
        // PREOPERACIONAL usa solo las columnas dedicadas de la tabla (estado_general, observaciones, etc.).
        $revision = $this->model->obtenerUltimoSeguimientoRevisionSST($idVehiculo);

        if ($revision) {
            $estado = $revision['estado_general'] ?? 'OPTIMO';
            $tieneNovedad = in_array($estado, ['FUERA_DE_SERVICIO', 'CON_NOVEDADES']);
            return [
                'tieneNovedad' => $tieneNovedad,
                'esNovedadReportada' => $tieneNovedad,   // REVISION_SST = novedad real
                'estado_general' => $estado,
                'observaciones' => $revision['observaciones'] ?? '',
                'ultimoSeguimiento' => $revision
            ];
        }

        // PRIORIDAD 2: No existe REVISION_SST; usar el registro más reciente (cualquier tipo).
        // Los PREOPERACIONAL con CON_NOVEDADES (por respuestas "NO" en checkboxes)
        // NO constituyen una novedad reportada que requiera fotos de entrega.
        $ultimo = $this->model->obtenerUltimoSeguimientoPorVehiculo($idVehiculo);

        if (!$ultimo) {
            return [
                'tieneNovedad' => false,
                'esNovedadReportada' => false,
                'estado_general' => 'OPTIMO',
                'observaciones' => '',
                'ultimoSeguimiento' => null
            ];
        }

        $estado = $ultimo['estado_general'] ?? 'OPTIMO';
        $tieneNovedad = in_array($estado, ['FUERA_DE_SERVICIO', 'CON_NOVEDADES']);

        return [
            'tieneNovedad' => $tieneNovedad,
            'esNovedadReportada' => false,   // sin REVISION_SST no es novedad reportada
            'estado_general' => $estado,
            'observaciones' => $ultimo['observaciones'] ?? '',
            'ultimoSeguimiento' => $ultimo
        ];
    }

    /**
     * Obtiene la lista de vehículos disponibles para preoperacional.
     *
     * REGLAS DE DISPONIBILIDAD (aplicadas en el modelo):
     * 1. veh_propiedad = 'empresa' — solo vehículos de la compañía.
     *    veh_propiedad vacío o 'propio' = personal (excluidos).
     * 2. veh_estado = 1 — solo vehículos activos
     * 3. Último seguimiento NO es FUERA_DE_SERVICIO
     * 4. Sin PREOPERACIONAL hoy de otro usuario — un vehículo solo puede
     *    tener un preoperacional por día
     *
     * NOTA: Ya no se filtra por usu_vehiculo. Vehículos con conductor asignado
     * se muestran si ese conductor no ha hecho preoperacional hoy.
     *
     * @param int|null $idUsuarioActual ID del usuario actual para filtrar
     *                                   vehículos ya usados por otros hoy
     * @return array Lista de vehículos disponibles
     */
    public function obtenerVehiculosDisponibles($idUsuarioActual = null)
    {
        return $this->model->obtenerVehiculosDisponibles($idUsuarioActual);
    }

    /**
     * Asigna un vehículo a un usuario en el flujo inicial
     * (cuando el usuario no tiene vehículo asignado).
     *
     * VALIDACIONES SERVIDOR:
     * 1. veh_propiedad = 'empresa' — no se permiten vehículos personales
     * 2. veh_estado = 1 — el vehículo debe estar activo
     * 3. Sin PREOPERACIONAL hoy de otro usuario — un vehículo solo puede
     *    tener un preoperacional por día
     *
     * Si el vehículo estaba asignado a otro conductor, se libera automáticamente
     * antes de asignarlo al nuevo usuario.
     *
     * @param int $idVehiculo ID del vehículo a asignar
     * @param int $idUsuario  ID del usuario
     * @return array Resultado de la operación
     */
    public function asignarVehiculoInicial($idVehiculo, $idUsuario)
    {
        if ($idVehiculo <= 0 || $idUsuario <= 0) {
            return ['success' => false, 'message' => 'Vehículo o usuario no válido.'];
        }

        // SEGURIDAD: verificar que el rol del usuario esté autorizado para asignar vehículos
        $rol = $_SESSION['usuario_rol'] ?? 0;
        if (!PreoperacionalNuevaEncuestaViewHelper::esRolVehicularAutorizado($rol)) {
            return ['success' => false, 'message' => 'No tiene permisos para asignar vehículos.'];
        }

        // --- VALIDACIÓN 1: El vehículo debe ser de propiedad de la empresa ---
        // Verificamos directamente contra la tabla vehiculos. No usamos
        // obtenerDatosVehiculoYUsuario() porque hace JOIN con usuarios.usu_vehiculo
        // y para un vehículo aún no asignado no devolvería fila.
        // NOTA: VehiculosModel ya está cargado vía require_once global (línea 10)
        $vehiculoModel = new VehiculosModel();
        $infoVehiculo = $vehiculoModel->obtenerVehiculoPorId($idVehiculo);

        if (!$infoVehiculo) {
            return ['success' => false, 'message' => 'El vehículo seleccionado no existe.'];
        }

        // REGLA 1: Solo vehículos de la empresa.
        // veh_propiedad debe ser 'empresa' explícitamente.
        // Valores vacíos ('') o 'propio' se tratan como vehículos personales (rechazados).
        $propiedad = $infoVehiculo['veh_propiedad'] ?? '';
        if ($propiedad !== 'empresa') {
            return ['success' => false, 'message' => 'No se pueden asignar vehículos personales.'];
        }

        // REGLA 2: Solo vehículos activos
        if ((int) ($infoVehiculo['veh_estado'] ?? 0) !== 1) {
            return ['success' => false, 'message' => 'El vehículo seleccionado no está activo.'];
        }

        // REGLA 3: El vehículo no debe tener un PREOPERACIONAL hoy de otro usuario
        try {
            $this->verificarDisponibilidadDiaria($idVehiculo, $idUsuario);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        // Verificar que el vehículo esté en la lista de disponibles
        $disponibles = $this->model->obtenerVehiculosDisponibles($idUsuario);
        $encontrado = false;
        foreach ($disponibles as $v) {
            if ((int) $v['idvehiculos'] === $idVehiculo) {
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            return ['success' => false, 'message' => 'El vehículo seleccionado no está disponible.'];
        }

        // Liberar el vehículo de cualquier conductor anterior antes de asignarlo
        $this->model->liberarVehiculoDeCualquierUsuario($idVehiculo);
        $this->model->asignarVehiculoAUsuario($idVehiculo, $idUsuario);

        return [
            'success' => true,
            'message' => 'Vehículo asignado correctamente.',
            'redirect' => true
        ];
    }

    /**
     * Procesa la selección diaria de un vehículo distinto al permanente.
     * Crea un registro en seguimiento_vehiculo (tipo ASIGNACION_VEHICULO)
     * y guarda las fotos del estado del vehículo en documentos.
     *
     * VALIDACIONES:
     * 1. El vehículo debe ser de la empresa (veh_propiedad = 'empresa')
     * 2. El vehículo debe estar activo (veh_estado = 1)
     * 3. No debe tener PREOPERACIONAL hoy de otro conductor
     * 4. El vehículo no debe estar FUERA_DE_SERVICIO
     *
     * @param int   $idUsuario  ID del usuario que selecciona
     * @param int   $idVehiculo ID del vehículo seleccionado
     * @param array $files      Array $_FILES con fotos del vehículo
     * @return array Resultado de la operación
     */
    public function procesarSeleccionVehiculoDiario($idUsuario, $idVehiculo, $files)
    {
        if ($idVehiculo <= 0 || $idUsuario <= 0) {
            return ['success' => false, 'message' => 'Vehículo o usuario no válido.'];
        }

        // SEGURIDAD: verificar que el rol del usuario esté autorizado
        $rol = $_SESSION['usuario_rol'] ?? 0;
        if (!PreoperacionalNuevaEncuestaViewHelper::esRolVehicularAutorizado($rol)) {
            return ['success' => false, 'message' => 'No tiene permisos para seleccionar vehículos.'];
        }

        // VALIDACIÓN 1: El vehículo debe ser de la empresa
        // NOTA: VehiculosModel ya está cargado vía require_once global (línea 10)
        $vehiculoModel = new VehiculosModel();
        $infoVehiculo = $vehiculoModel->obtenerVehiculoPorId($idVehiculo);
        if (!$infoVehiculo) {
            return ['success' => false, 'message' => 'El vehículo seleccionado no existe.'];
        }
        $propiedad = $infoVehiculo['veh_propiedad'] ?? '';
        if ($propiedad !== 'empresa') {
            return ['success' => false, 'message' => 'No se pueden seleccionar vehículos personales.'];
        }
        if ((int) ($infoVehiculo['veh_estado'] ?? 0) !== 1) {
            return ['success' => false, 'message' => 'El vehículo seleccionado no está activo.'];
        }

        // VALIDACIÓN 2: No debe tener PREOPERACIONAL hoy de otro conductor
        try {
            $this->verificarDisponibilidadDiaria($idVehiculo, $idUsuario);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        // VALIDACIÓN 3: El vehículo debe estar disponible
        $disponibles = $this->model->obtenerVehiculosDisponibles($idUsuario);
        $encontrado = false;
        foreach ($disponibles as $v) {
            if ((int) $v['idvehiculos'] === $idVehiculo) {
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            return ['success' => false, 'message' => 'El vehículo seleccionado no está disponible.'];
        }

        // Obtener vehículo permanente para metadata
        $vehiculoPermanente = $this->model->obtenerVehiculoPermanenteUsuario($idUsuario) ?: 0;

        // Crear seguimiento_vehiculo tipo ASIGNACION_VEHICULO
        $idSeg = $this->guardarSeguimientoVehiculo([
            'tipo_evento' => 'ASIGNACION_VEHICULO',
            'metadata_evento' => [
                'origen' => 'seleccion_diaria',
                'vehiculo_permanente' => $vehiculoPermanente
            ],
            'id_preoperacional' => null,
            'id_seguimiento_user' => null,
            'id_vehiculo' => $idVehiculo,
            'id_conductor' => $idUsuario,
            'id_responsable' => $idUsuario,
            'kilometraje' => 0,
            'ubicacion' => null,
            'estado_general' => 'OPTIMO',
            'foto_evidencia' => null,
            'observaciones' => 'Selección diaria de vehículo',
            'entrega_final_usuario' => null,
            'entrega_inicial_usuario' => null
        ]);

        if (!$idSeg) {
            return ['success' => false, 'message' => 'Error al registrar la selección del vehículo.'];
        }

        // Guardar fotos del estado del vehículo en documentos
        $camposFoto = [
            'diaria_frente'       => 'foto_frente',
            'diaria_trasera'      => 'foto_trasera',
            'diaria_lateral_izq'  => 'foto_lateral_izq',
            'diaria_lateral_der'  => 'foto_lateral_der',
        ];

        $fotosGuardadas = 0;
        foreach ($camposFoto as $fileKey => $tipo) {
            if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $docId = $this->model->guardarDocumentoGenerico(
                    $files[$fileKey],
                    $idSeg,
                    10,
                    'seguimiento_vehiculo'
                );
                if ($docId) {
                    $fotosGuardadas++;
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Vehículo seleccionado para hoy correctamente.',
            'idvehiculo' => $idVehiculo,
            'id_seguimiento' => $idSeg,
            'fotos_guardadas' => $fotosGuardadas
        ];
    }

    /**
     * Resuelve qué vehículo debe usar el usuario HOY.
     *
     * Prioridad:
     * 1. Si existe ASIGNACION_VEHICULO para hoy → usar ese
     * 2. Si no, verificar si usu_vehiculo puede ser usado:
     *    a. ¿Está FUERA_DE_SERVICIO? → null (sin vehículo)
     *    b. ¿Tiene PREOPERACIONAL hoy de OTRO conductor? → null
     *    c. Si pasa ambas → usar usu_vehiculo
     * 3. Si usu_vehiculo no está definido → null
     *
     * @param int    $idUsuario ID del usuario
     * @param string $fecha     Fecha en formato Y-m-d
     * @return int|null ID del vehículo a usar, o null si no aplica
     */
    public function obtenerVehiculoParaUsuarioHoy($idUsuario, $fecha)
    {
        // PRIORIDAD 1: ¿Hay una asignación diaria ya registrada?
        $asignacionDiaria = $this->model->obtenerAsignacionDiaria($idUsuario, $fecha);
        if ($asignacionDiaria && !empty($asignacionDiaria['id_vehiculo'])) {
            return (int) $asignacionDiaria['id_vehiculo'];
        }

        // PRIORIDAD 2: Verificar el vehículo permanente
        $idVehiculoPerm = $this->model->obtenerVehiculoPermanenteUsuario($idUsuario);
        if ($idVehiculoPerm === null || $idVehiculoPerm <= 0) {
            return null;
        }

        // REGLA A: ¿Está FUERA_DE_SERVICIO?
        $estado = $this->obtenerEstadoVehiculo($idVehiculoPerm);
        if (($estado['estado_general'] ?? '') === 'FUERA_DE_SERVICIO') {
            return null;
        }

        // REGLA B: ¿Tiene PREOPERACIONAL hoy de OTRO conductor?
        try {
            $this->verificarDisponibilidadDiaria($idVehiculoPerm, $idUsuario);
        } catch (\RuntimeException $e) {
            return null;
        }

        // Pasa todas las reglas → usar vehículo permanente
        return $idVehiculoPerm;
    }

    /**
     * Verifica que un vehículo no tenga un PREOPERACIONAL registrado hoy
     * por OTRO usuario. Lanza una RuntimeException si el vehículo ya fue
     * usado por otro conductor en el día.
     *
     * REGLA: Un vehículo solo puede tener un preoperacional por día.
     *        El mismo conductor sí puede volver a usar su vehículo.
     *
     * @param int $idVehiculo ID del vehículo
     * @param int $idUsuario  ID del usuario que intenta asignarlo
     * @throws RuntimeException si el vehículo ya fue usado hoy por otro usuario
     */
    private function verificarDisponibilidadDiaria($idVehiculo, $idUsuario)
    {
        $sql = "SELECT sv.id_conductor, sv.fecha_registro
                FROM seguimiento_vehiculo sv
                WHERE sv.id_vehiculo = ?
                  AND sv.tipo_evento = 'PREOPERACIONAL'
                  AND DATE(sv.fecha_registro) = CURDATE()
                  AND sv.id_conductor IS NOT NULL
                ORDER BY sv.fecha_registro DESC
                LIMIT 1";
        $db = (new Database())->connect();
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $idVehiculo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && (int) $row['id_conductor'] !== $idUsuario) {
            throw new \RuntimeException(
                'Este vehículo ya fue registrado hoy por otro conductor.'
            );
        }
        // Si no hay registro o el registro es del mismo usuario, OK
    }

    /**
     * Procesa el reporte de novedad del vehículo.
     * Si el vehículo puede ser operado, crea un registro OPTIMO.
     * Si no, crea un registro FUERA_DE_SERVICIO y opcionalmente cambia el vehículo.
     *
     * @param array $datos Datos del reporte de novedad
     * @return array Resultado de la operación
     */
    public function procesarReporteNovedad($datos, $files = [])
    {
        $idVehiculoActual = (int) ($datos['idvehiculo_actual'] ?? 0);
        $idUsuario = (int) ($datos['id_usuario'] ?? 0);
        $puedeSerOperado = ($datos['puede_ser_operado'] ?? '') === 'si';
        $observaciones = $datos['observaciones'] ?? '';
        $idVehiculoNuevo = (int) ($datos['idvehiculo_nuevo'] ?? 0);

        // SEGURIDAD: verificar que el rol del usuario esté autorizado para reportar novedades vehiculares
        $rol = $_SESSION['usuario_rol'] ?? 0;
        if (!PreoperacionalNuevaEncuestaViewHelper::esRolVehicularAutorizado($rol)) {
            return ['success' => false, 'message' => 'No tiene permisos para reportar novedades de vehículos.'];
        }

        // Procesar fotos enviadas desde el formulario
        $fotos = $this->procesarFotosNovedad($files);
        $fotoEvidencia = $fotos['foto_evidencia'] ?? null;

        // Vincular con el último preoperacional del vehículo para trazabilidad.
        // El conductor típicamente completa el preoperacional antes de reportar una novedad.
        $ultimoPreop = $this->model->obtenerUltimoPreoperacionalPorVehiculo($idVehiculoActual);
        $idPreopVinculado = ($ultimoPreop && !empty($ultimoPreop['idpreoperacinal']))
            ? (int) $ultimoPreop['idpreoperacinal']
            : null;

        if ($puedeSerOperado) {
            // REGLA 1: El vehículo SÍ puede ser operado pero mantiene CON_NOVEDADES
            // OPTIMO solo se asigna desde otro módulo (admin/SST)
            $this->guardarSeguimientoVehiculo([
                'tipo_evento' => 'REVISION_SST',
                'metadata_evento' => [
                    'origen' => 'reporte_novedad',
                    'reportado_por' => $_SESSION['usuario_nombre'] ?? 'Sistema',
                    'flujo' => 'preoperacional'
                ],
                'id_preoperacional' => $idPreopVinculado,
                'id_seguimiento_user' => null,
                'id_vehiculo' => $idVehiculoActual,
                'id_conductor' => $idUsuario,
                'id_responsable' => $_SESSION['usuario_id'] ?? $idUsuario,
                'kilometraje' => 0,
                'ubicacion' => null,
                'estado_general' => 'CON_NOVEDADES',
                'foto_evidencia' => $fotoEvidencia,
                'observaciones' => 'El vehículo puede ser operado. ' . $observaciones,
                'entrega_final_usuario' => null,
                'entrega_inicial_usuario' => null
            ]);

            // Guardar fotos múltiples de evidencia en documentos
            if (!empty($fotos['evidencia_fotos'])) {
                foreach ($fotos['evidencia_fotos'] as $rutaFoto) {
                    $this->model->guardarImagenDesdeRutaSimple(
                        $rutaFoto,
                        basename($rutaFoto),
                        $idPreopVinculado ?? 0,
                        5 // doc_version = 5 (novedad_evidencia)
                    );
                }
            }

            return [
                'success' => true,
                'message' => 'Novedad registrada. El vehículo puede ser operado.',
                'idvehiculo_final' => $idVehiculoActual
            ];
        } else {
            // El vehículo NO puede ser operado — registrar FUERA_DE_SERVICIO

            // [ELIMINADO] Bloque entregavehiculo (trazabilidad final/inicial)
            // La selección diaria reemplaza la asignación permanente.

            $idVehiculoFinal = $idVehiculoActual;
            $cambioVehiculo = false;

            if ($idVehiculoNuevo > 0) {
                // Selección diaria — no se toca usu_vehiculo
                $disponibles = $this->model->obtenerVehiculosDisponibles($idUsuario);
                $encontrado = false;
                foreach ($disponibles as $v) {
                    if ((int) $v['idvehiculos'] === $idVehiculoNuevo) {
                        $encontrado = true;
                        break;
                    }
                }
                if ($encontrado) {
                    $this->guardarSeguimientoVehiculo([
                        'tipo_evento' => 'ASIGNACION_VEHICULO',
                        'metadata_evento' => [
                            'origen' => 'reporte_novedad',
                            'vehiculo_permanente' => $this->model->obtenerVehiculoPermanenteUsuario($idUsuario) ?: 0
                        ],
                        'id_preoperacional' => $idPreopVinculado,
                        'id_seguimiento_user' => null,
                        'id_vehiculo' => $idVehiculoNuevo,
                        'id_conductor' => $idUsuario,
                        'id_responsable' => $_SESSION['usuario_id'] ?? $idUsuario,
                        'kilometraje' => 0,
                        'ubicacion' => null,
                        'estado_general' => 'OPTIMO',
                        'foto_evidencia' => null,
                        'observaciones' => 'Vehículo asignado tras reporte de novedad: ' . $observaciones,
                        'entrega_final_usuario' => null,
                        'entrega_inicial_usuario' => null
                    ]);
                    $idVehiculoFinal = $idVehiculoNuevo;
                    $cambioVehiculo = true;
                } else {
                    $idVehiculoFinal = 0;
                }
            } else {
                $idVehiculoFinal = 0;
            }

            $this->guardarSeguimientoVehiculo([
                'tipo_evento' => 'REVISION_SST',
                'metadata_evento' => [
                    'origen' => 'reporte_novedad',
                    'reportado_por' => $_SESSION['usuario_nombre'] ?? 'Sistema',
                    'flujo' => 'preoperacional'
                ],
                'id_preoperacional' => $idPreopVinculado,
                'id_seguimiento_user' => null,
                'id_vehiculo' => $idVehiculoActual,
                'id_conductor' => $idUsuario,
                'id_responsable' => $_SESSION['usuario_id'] ?? $idUsuario,
                'kilometraje' => 0,
                'ubicacion' => null,
                'estado_general' => 'FUERA_DE_SERVICIO',
                'foto_evidencia' => $fotoEvidencia,
                'observaciones' => $observaciones,
                'entrega_final_usuario' => null,
                'entrega_inicial_usuario' => null
            ]);

            // Guardar fotos múltiples de evidencia en documentos
            if (!empty($fotos['evidencia_fotos'])) {
                foreach ($fotos['evidencia_fotos'] as $rutaFoto) {
                    $this->model->guardarImagenDesdeRutaSimple(
                        $rutaFoto,
                        basename($rutaFoto),
                        $idPreopVinculado ?? 0,
                        5 // doc_version = 5 (novedad_evidencia)
                    );
                }
            }

            return [
                'success' => true,
                'message' => $cambioVehiculo
                    ? 'Novedad registrada. Se ha asignado el nuevo vehículo.'
                    : 'Novedad registrada. No se ha asignado un vehículo alternativo.',
                'idvehiculo_final' => $idVehiculoFinal,
                'cambio_vehiculo' => $cambioVehiculo
            ];
        }
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
            $rutaBase = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pre-operacional' . DIRECTORY_SEPARATOR;

            // Crear el directorio si no existe
            if (!is_dir($rutaBase)) {
                mkdir($rutaBase, 0777, true);
            }

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
     * Procesa la foto de una pregunta vehicular respondida con NO.
     * Guarda en uploads/pre-operacional/ y retorna la ruta absoluta.
     *
     * @param array $file Archivo de $_FILES
     * @param string $codigo Código de la pregunta (ej: luces_1)
     * @param int $idPreoperacional ID del preoperacional
     * @return string|null Ruta absoluta del archivo guardado, o null si falló
     */
    private function procesarImagenPreguntaVehiculo($file, $codigo, $idPreoperacional)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $nombreArchivo = $codigo . '_' . date("Y-m-d-H-i-s") . '_' . $file['name'];
        $rutaBase = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pre-operacional' . DIRECTORY_SEPARATOR;

        if (!is_dir($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        $rutaDestino = $rutaBase . $nombreArchivo;

        if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
            return $rutaDestino;
        }

        error_log("PreoperacionalService: Error al mover imagen de pregunta vehicular: $codigo");
        return null;
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
        $idPre, $dataJson, $descValidada,
        $accionCorrectiva, $responsable, $temperatura,
        $kilometraje, $idVehiculo, $imagenKilo, $imagenInspeccion,
        $firmaProcesada = false,
        $files = [],
        $vehiculoFueraDeServicio = false,
        $warningKilometraje = null
    ) {
        global $_SESSION;

        $datosActualizar = [
            'prefechavalidacion' => date('Y-m-d H:i:s'),
            'predatosvalidados' => $dataJson,
            'pre_descvalidada' => $_SESSION['usuario_nombre'] . " - " . $descValidada,
            'pre_iduservalida' => $_SESSION['usuario_id'],
            'preestado' => 'Validado',
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

        // === ESQUEMA RELACIONAL: Si el registro tiene id_version, actualizar preop_respuestas ===
        $registroActual = $this->model->obtenerRegistroPorId($idPre);
        if (!empty($registroActual['id_version'])) {
            $respuestas = json_decode($dataJson, true);
            if (is_array($respuestas)) {
                $this->guardarRespuestasRelacionales($idPre, $respuestas, $registroActual['id_version']);
            }
            // @TODO: Actualizar seguimiento_vehiculo.estado_general cuando se implemente
            $nuevoEstado = $this->calcularEstadoGeneral($respuestas);
            $this->model->actualizarSeguimientoVehiculoEstado($idPre, $nuevoEstado);
        }

        // === ENTREGA DE VEHÍCULO: DESHABILITADO (desarrollo activo) ===
        // NO ELIMINAR: Este bloque procesa las fotos de entrega y crea registros en
        // entregavehiculo durante la validación del preoperacional.
        //
        // $fotosProcesadas = $this->procesarFotosEntrega($files);
        // $tieneFotos = !empty($fotosProcesadas['final_frente']) || ...
        //
        // if ($tieneFotos && $idVehiculo > 0) {
        //     $resultadoEntrega = $this->crearEntregasVehiculoEnValidacion(...);
        // }
        // ========== FIN DESHABILITADO ==========

        $resultado = ['success' => true, 'message' => 'Preoperacional actualizado correctamente'];
        if ($warningKilometraje !== null) {
            $resultado['warning'] = $warningKilometraje;
        }
        return $resultado;
    }

    /**
     * Inserta un nuevo registro
     */
    private function insertarRegistro(
        $idVehiculo, $tipoVehiculo, $fechaHora, $idUsuario,
        $dataJson, $observaciones, $accionCorrectiva,
        $responsable, $temperatura, $kilometraje,
        $limpiomaleta, $imagenKilo, $files, $imagenInspeccion,
        $firmaProcesada = false,
        $vehiculoFueraDeServicio = false,
        $warningKilometraje = null
    ) {

        // Parsear el JSON del formulario para separar metadata de respuestas
        $datosEncuesta = json_decode($dataJson, true);
        if (!is_array($datosEncuesta)) {
            $datosEncuesta = [];
        }
        // preencuesta solo almacena metadata (ubicacion, doc IDs). Las respuestas van a preop_respuestas.
        $metadatosJson = !empty($datosEncuesta)
            ? json_encode($this->filtrarMetadatosDelJson($datosEncuesta), JSON_UNESCAPED_UNICODE)
            : '';

        $datosInsert = [
            'prevehiculo' => $idVehiculo,
            'pretipovehiculo' => $tipoVehiculo,
            'prefechaingreso' => $fechaHora,
            'preidusuario' => $idUsuario,
            'preencuesta' => $metadatosJson,
            'pre_obsevaciones' => $observaciones,
            'pre_correctiva' => $accionCorrectiva,
            'pre_responsable' => $responsable,
            'pre_temperatura' => $temperatura,
            'pre_kilrecorridos' => $kilometraje,
            'pre_limpiomaleta' => $limpiomaleta,
            'pre_img_kilo' => $imagenKilo,
            'preestado' => 'pendiente',
            'pre_ubicacion' => $datosEncuesta['ubicacion'] ?? null,
            'pre_firma' => null  // Se actualizará después de guardar la firma
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
                if (is_array($imagenInspeccion) && isset($imagenInspeccion['tmp_name']) && isset($imagenInspeccion['name'])) {
                    $inspeccionDocId = $this->model->guardarImagenDesdeRuta($imagenInspeccion['tmp_name'], $imagenInspeccion['name'], $idInsertado, 3);
                } else {
                    $inspeccionDocId = $this->model->guardarImagen($imagenInspeccion, $idInsertado, 3);
                }
                if ($inspeccionDocId) {
                    $idsDocumentos['inspeccion_documento_id'] = $inspeccionDocId;
                }
            }

            // Guardar firma si existe
            $firmaDocId = false;
            if ($firmaProcesada && is_array($firmaProcesada) && isset($firmaProcesada['ruta']) && isset($firmaProcesada['nombre'])) {
                $firmaDocId = $this->model->guardarImagenDesdeRuta($firmaProcesada['ruta'], $firmaProcesada['nombre'], $idInsertado, 4);
                if ($firmaDocId) {
                    $idsDocumentos['firma_documento_id'] = $firmaDocId;
                }
            }

            // Actualizar preencuesta con metadata + IDs de documentos
            // y pre_firma con el ID del documento de firma
            $camposUpdate = [];
            if (!empty($idsDocumentos)) {
                $metadatosConDocs = $this->agregarIdsDocumentosAlJson($metadatosJson, $idsDocumentos);
                $camposUpdate['preencuesta'] = $metadatosConDocs;
            }
            if ($firmaDocId) {
                $camposUpdate['pre_firma'] = $firmaDocId;
            }
            if (!empty($camposUpdate)) {
                $this->model->actualizarCamposPreoperacional($idInsertado, $camposUpdate);
            }

            if ($kilometraje > 0 && $idVehiculo > 0) {
                $this->model->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
            }

            // === ESQUEMA RELACIONAL: Siempre activo ===
            $versiones = $this->resolverVersionesAplicables($tipoVehiculo, $_SESSION['usuario_rol'] ?? 0);
            if (is_array($datosEncuesta)) {
                // Extraer observaciones de preguntas vehiculares del POST original
                // (vienen como campos separados, no dentro de dataJson)
                $observacionesVehiculo = [];
                foreach ($_POST as $key => $value) {
                    if (substr($key, -4) === '_obs') {
                        $observacionesVehiculo[$key] = $value;
                    }
                }
                // Fusionar observaciones en datosEncuesta para que estén disponibles en guardarRespuestasRelacionales
                foreach ($observacionesVehiculo as $key => $value) {
                    $datosEncuesta[$key] = $value;
                }
                // Reconstruir dataJson incluyendo observaciones
                $dataJson = json_encode($datosEncuesta, JSON_UNESCAPED_UNICODE);

                foreach ($versiones['ids_versiones'] as $idVersion) {
                    $this->guardarRespuestasRelacionales($idInsertado, $datosEncuesta, $idVersion, $files);
                }
                // Marcar el registro con la versión principal (vehículo tiene prioridad)
                $versionPrincipal = $versiones['version_vehiculo'] ?? $versiones['version_usuario'] ?? null;
                if ($versionPrincipal) {
                    $this->model->actualizarCamposPreoperacional($idInsertado, [
                        'id_version' => $versionPrincipal['id_version']
                    ]);
                }
            }

            // Seguimiento vehiculo — registro del libro de vida del vehículo
            // Si el vehículo tiene una novedad activa (REVISION_SST CON_NOVEDADES o FUERA_DE_SERVICIO),
            // se conservan la observación y el estado de la novedad en lugar de recalcularlos
            // desde las respuestas de la encuesta. Así el historial refleja la realidad del vehículo.
            $observacionSeguimiento = $observaciones;
            $estadoGeneralSeguimiento = $this->calcularEstadoGeneral($datosEncuesta);

            // Defensa: si el vehículo está FUERA_DE_SERVICIO, forzar el estado en el seguimiento
            // independientemente de lo que diga la encuesta (el conductor debió cambiar de vehículo)
            if ($vehiculoFueraDeServicio) {
                $estadoGeneralSeguimiento = 'FUERA_DE_SERVICIO';
            }

            // NOTA: metadata_evento es exclusivo de REVISION_SST (que requiere más datos de contexto).
            // PREOPERACIONAL usa las columnas dedicadas de la tabla seguimiento_vehiculo
            // (id_preoperacional, estado_general, observaciones, kilometraje, ubicacion, foto_evidencia)
            // para registrar los datos del preoperacional, sin duplicarlos en un JSON de metadata.
            $revisionSST = $this->model->obtenerUltimoSeguimientoRevisionSST($idVehiculo);
            if ($revisionSST
                && in_array($revisionSST['estado_general'] ?? '', ['CON_NOVEDADES', 'FUERA_DE_SERVICIO'])) {
                // El vehículo tiene una REVISION_SST activa con estado no-OPTIMO.
                // El PREOPERACIONAL hereda este estado — se usa la columna estado_general
                // y observaciones para trazabilidad, sin necesidad de metadata JSON.
                $estadoGeneralSeguimiento = $revisionSST['estado_general'];
                if (!empty($revisionSST['observaciones'])) {
                    $observacionSeguimiento = $revisionSST['observaciones'];
                }
            }

            $datosSeg = [
                'tipo_evento' => 'PREOPERACIONAL',
                'metadata_evento' => null,
                'id_preoperacional' => $idInsertado,
                'id_seguimiento_user' => null,
                'id_vehiculo' => $idVehiculo,
                'id_conductor' => (in_array(strtoupper($tipoVehiculo), ['CARRO', 'MOTO'])) ? $idUsuario : null,
                'id_responsable' => $idUsuario,
                'kilometraje' => $kilometraje,
                'ubicacion' => $datosEncuesta['ubicacion'] ?? null,
                'estado_general' => $estadoGeneralSeguimiento,
                'foto_evidencia' => $imagenKilo,
                'img_kilometraje' => $imagenKilo,
                'observaciones' => $observacionSeguimiento
            ];
            $this->guardarSeguimientoVehiculo($datosSeg);
        }

        $resultado = ['success' => true, 'message' => 'Preoperacional guardado correctamente'];
        if ($warningKilometraje !== null) {
            $resultado['warning'] = $warningKilometraje;
        }
        return $resultado;
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

    /**
     * Filtra el JSON del formulario para extraer solo metadatos (no respuestas a preguntas).
     *
     * Las respuestas a preguntas (codigo_interno) van a preop_respuestas.
     * Los metadatos (ubicacion, IDs de documentos) se guardan en preencuesta
     * por compatibilidad con archivos legacy que los lean desde ahí.
     *
     * @param array $data Datos crudos del formulario
     * @return array Solo claves de metadata
     */
    private function filtrarMetadatosDelJson(array $data): array
    {
        $clavesMetadata = ['ubicacion', 'firma_documento_id', 'inspeccion_documento_id', 'temperatura_documento_id'];
        $resultado = [];
        foreach ($clavesMetadata as $clave) {
            if (array_key_exists($clave, $data) && $data[$clave] !== null && $data[$clave] !== '') {
                $resultado[$clave] = $data[$clave];
            }
        }
        return $resultado;
    }

    // [ELIMINADO] crearEntregasVehiculoEnValidacion() — reemplazado por selección diaria

    /**
     * Procesa las fotos del reporte de novedad vehicular.
     * Mapea hasta 5 archivos: 1 foto de evidencia, 2 de salida (final), 2 de entrada (inicial).
     *
     * @param array $files Array $_FILES
     * @return array Rutas guardadas ['foto_evidencia', 'salida_frente', 'salida_trasera', 'entrada_frente', 'entrada_trasera']
     */
    private function procesarFotosNovedad($files)
    {
        $resultado = [
            'foto_evidencia'  => '',
            'salida_frente'   => '',
            'salida_trasera'  => '',
            'entrada_frente'  => '',
            'entrada_trasera' => ''
        ];

        $mapeo = [
            'novedad_salida_frente'   => 'salida_frente',
            'novedad_salida_trasera'  => 'salida_trasera',
            'novedad_entrada_frente'  => 'entrada_frente',
            'novedad_entrada_trasera' => 'entrada_trasera'
        ];

        foreach ($mapeo as $fileKey => $destKey) {
            if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $ruta = $this->procesarImagenEntrega($files[$fileKey], $destKey);
                if ($ruta) {
                    $resultado[$destKey] = $ruta;
                }
            }
        }

        // Procesar múltiples fotos de evidencia (novedad_fotos[])
        $resultado['evidencia_fotos'] = [];
        if (isset($files['novedad_fotos'])) {
            foreach ($files['novedad_fotos']['name'] as $i => $name) {
                if ($files['novedad_fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name'     => $name,
                        'type'     => $files['novedad_fotos']['type'][$i] ?? '',
                        'tmp_name' => $files['novedad_fotos']['tmp_name'][$i],
                        'error'    => $files['novedad_fotos']['error'][$i],
                        'size'     => $files['novedad_fotos']['size'][$i] ?? 0
                    ];
                    $ruta = $this->procesarImagenEntrega($file, 'novedad_evidencia_' . $i);
                    if ($ruta) {
                        $resultado['evidencia_fotos'][] = $ruta;
                    }
                }
            }
        }

        return $resultado;
    }

    // [ELIMINADO] crearEntregaVehiculoNovedad(), procesarFotosEntrega() -- reemplazado por seleccion diaria

    /**
     * Resuelve el ID de la hoja de vida activa de un usuario a partir de su cédula.
     *
     * @param int $idUsuario ID del usuario
     * @return int|null ID de la hoja de vida o null
     */
    private function resolverHojaDeVida($idUsuario)
    {
        $sqlCedula = "SELECT usu_identificacion FROM usuarios WHERE idusuarios = ? LIMIT 1";
        $db = (new Database())->connect();
        $stmt = $db->prepare($sqlCedula);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuarioData = $result->fetch_assoc();
        $stmt->close();

        if ($usuarioData && !empty($usuarioData['usu_identificacion'])) {
            $cedula = $usuarioData['usu_identificacion'];
            $sqlHoja = "SELECT idhojadevida FROM hojadevida
                         WHERE hoj_cedula = ? AND hoj_estado = 'Activo' LIMIT 1";
            $stmtHoja = $db->prepare($sqlHoja);
            $stmtHoja->bind_param("s", $cedula);
            $stmtHoja->execute();
            $resultHoja = $stmtHoja->get_result();
            $hojaData = $resultHoja->fetch_assoc();
            $stmtHoja->close();
            if ($hojaData) {
                return $hojaData['idhojadevida'];
            }
        }

        return null;
    }

    /**
     * Procesa una imagen de entrega de vehículo y retorna la ruta guardada.
     *
     * @param array $file Archivo de $_FILES
     * @param string $prefijo Prefijo para el nombre del archivo ('final' o 'inicial')
     * @return string Ruta de la imagen guardada o cadena vacía
     */
    public function procesarImagenEntrega($file, $prefijo = 'entrega')
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return '';
        }

        $rutaBase = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vehiculos' . DIRECTORY_SEPARATOR;
        if (!is_dir($rutaBase)) {
            mkdir($rutaBase, 0777, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($extension, $extensionesPermitidas)) {
            return '';
        }

        $nombre = $prefijo . '_' . date("Y-m-d-H-i-s") . '_' . uniqid() . '.' . $extension;
        $destino = $rutaBase . $nombre;

        if ($extension === 'pdf') {
            if (move_uploaded_file($file['tmp_name'], $destino)) {
                return 'uploads/vehiculos/' . $nombre;
            }
            return '';
        }

        // Procesar imagen
        $info = getimagesize($file['tmp_name']);
        if (!$info) return '';

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $imagen = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imagen = imagecreatefrompng($file['tmp_name']);
                break;
            default:
                return '';
        }

        // Redimensionar si es necesario
        $maxW = 1280;
        $maxH = 1280;
        $w = imagesx($imagen);
        $h = imagesy($imagen);

        if ($w > $maxW || $h > $maxH) {
            $ratio = min($maxW / $w, $maxH / $h);
            $nw = (int)($w * $ratio);
            $nh = (int)($h * $ratio);
            $tmp = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($tmp, $imagen, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $imagen = $tmp;
        }

        imagejpeg($imagen, $destino, 70);
        imagedestroy($imagen);

        return 'uploads/vehiculos/' . $nombre;
    }
}
