<?php
/**
 * PreoperacionalService - Maneja la lógica de negocio del preoperacional
 * 
 * Esta clase centraliza todas las operaciones de negocio relacionadas
 * con el preoperacional, separándolas del controlador.
 */

require_once __DIR__ . '/../../../model/PreoperacionalModel.php';
require_once __DIR__ . '/../../../model/VehiculosModel.php';

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

        // Validar que la imagen de kilometraje sea obligatoria (solo para vehículos CARRO/MOTO)
        // EXCEPCIÓN: vehículo FUERA_DE_SERVICIO — el conductor será redirigido a otro vehículo
        if (!$vehiculoFueraDeServicio) {
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
    public function guardarRespuestasRelacionales($idPreoperacional, $respuestasFormulario, $idVersion)
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

            $batch[] = [
                'id_preoperacional' => $idPreoperacional,
                'id_pregunta' => $mapping[$codigo],
                'respuesta_dada' => (string) $valor,
                'ruta_foto' => null
            ];
        }

        if (empty($batch)) {
            return false;
        }

        return $this->model->insertarRespuestasBatch($batch);
    }

    /**
     * Obtiene las respuestas de una versión específica, formateadas como array
     * compatible con $valoresEncuesta del ViewHelper.
     *
     * @param int $idPreoperacional
     * @param int $idVersion
     * @return array
     */
    public function obtenerRespuestasVersion($idPreoperacional, $idVersion)
    {
        return $this->model->obtenerRespuestasVersion($idPreoperacional, $idVersion);
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
        $ultimo = $this->model->obtenerUltimoSeguimientoPorVehiculo($idVehiculo);

        if (!$ultimo) {
            return [
                'tieneNovedad' => false,
                'estado_general' => 'OPTIMO',
                'observaciones' => '',
                'ultimoSeguimiento' => null
            ];
        }

        $estado = $ultimo['estado_general'] ?? 'OPTIMO';
        $tieneNovedad = in_array($estado, ['FUERA_DE_SERVICIO', 'CON_NOVEDADES']);

        return [
            'tieneNovedad' => $tieneNovedad,
            'estado_general' => $estado,
            'observaciones' => $ultimo['observaciones'] ?? '',
            'ultimoSeguimiento' => $ultimo
        ];
    }

    /**
     * Obtiene la lista de vehículos disponibles (sin conductor asignado)
     *
     * @return array Lista de vehículos
     */
    public function obtenerVehiculosDisponibles()
    {
        return $this->model->obtenerVehiculosSinConductor();
    }

    /**
     * Asigna un vehículo a un usuario en el flujo inicial
     * (cuando el usuario no tiene vehículo asignado).
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

        // Verificar que el vehículo esté disponible (sin conductor asignado)
        $disponibles = $this->model->obtenerVehiculosSinConductor();
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

        $this->model->asignarVehiculoAUsuario($idVehiculo, $idUsuario);

        return [
            'success' => true,
            'message' => 'Vehículo asignado correctamente.',
            'redirect' => true
        ];
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

        // Procesar fotos enviadas desde el formulario
        $fotos = $this->procesarFotosNovedad($files);
        $fotoEvidencia = $fotos['foto_evidencia'] ?? null;

        if ($puedeSerOperado) {
            // REGLA 1: El vehículo SÍ puede ser operado pero mantiene CON_NOVEDADES
            // OPTIMO solo se asigna desde otro módulo (admin/SST)
            $this->guardarSeguimientoVehiculo([
                'tipo_evento' => 'REVISION_SST',
                'metadata_evento' => ['origen' => 'reporte_novedad', 'resuelto_por' => $_SESSION['usuario_nombre'] ?? 'Sistema'],
                'id_preoperacional' => null,
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

            return [
                'success' => true,
                'message' => 'Novedad registrada. El vehículo puede ser operado.',
                'idvehiculo_final' => $idVehiculoActual
            ];
        } else {
            // El vehículo NO puede ser operado — registrar FUERA_DE_SERVICIO
            // Crear entrega FINAL (salida) para el vehículo actual
            $idEntregaFinal = null;
            if (!empty($fotos['salida_frente']) || !empty($fotos['salida_trasera'])) {
                $idEntregaFinal = $this->crearEntregaVehiculoNovedad(
                    $idVehiculoActual, $idUsuario,
                    $fotos['salida_frente'], $fotos['salida_trasera'],
                    $observaciones, 'final'
                );
            }

            $idEntregaInicial = null;
            $idVehiculoFinal = $idVehiculoActual;
            $cambioVehiculo = false;

            if ($idVehiculoNuevo > 0) {
                // Asignar vehículo nuevo
                $this->model->asignarVehiculoAUsuario($idVehiculoNuevo, $idUsuario);
                $idVehiculoFinal = $idVehiculoNuevo;
                $cambioVehiculo = true;

                // Crear entrega INICIAL (entrada) para el vehículo nuevo
                if (!empty($fotos['entrada_frente']) || !empty($fotos['entrada_trasera'])) {
                    $idEntregaInicial = $this->crearEntregaVehiculoNovedad(
                        $idVehiculoNuevo, $idUsuario,
                        $fotos['entrada_frente'], $fotos['entrada_trasera'],
                        $observaciones, 'inicial'
                    );
                }
            } else {
                // No seleccionó vehículo — desasignar el actual
                $this->model->desasignarVehiculoDeUsuario($idUsuario);
                $idVehiculoFinal = 0;
            }

            $this->guardarSeguimientoVehiculo([
                'tipo_evento' => 'REVISION_SST',
                'metadata_evento' => ['origen' => 'reporte_novedad', 'reportado_por' => $_SESSION['usuario_nombre'] ?? 'Sistema'],
                'id_preoperacional' => null,
                'id_seguimiento_user' => null,
                'id_vehiculo' => $idVehiculoActual,
                'id_conductor' => $idUsuario,
                'id_responsable' => $_SESSION['usuario_id'] ?? $idUsuario,
                'kilometraje' => 0,
                'ubicacion' => null,
                'estado_general' => 'FUERA_DE_SERVICIO',
                'foto_evidencia' => $fotoEvidencia,
                'observaciones' => $observaciones,
                'entrega_final_usuario' => $idEntregaFinal,
                'entrega_inicial_usuario' => $idEntregaInicial
            ]);

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

        // === ENTREGA DE VEHÍCULO: Procesar fotos y crear registros de entrega ===
        $fotosProcesadas = $this->procesarFotosEntrega($files);
        $tieneFotos = !empty($fotosProcesadas['final_frente']) || !empty($fotosProcesadas['final_trasera'])
                      || !empty($fotosProcesadas['inicial_frente']) || !empty($fotosProcesadas['inicial_trasera']);

        if ($tieneFotos && $idVehiculo > 0) {
            // Obtener firma del conductor desde documentos
            $firmaRuta = '';
            $firmaDoc = $this->model->obtenerDocumentoFirma($idPre);
            if ($firmaDoc && !empty($firmaDoc['doc_ruta'])) {
                $firmaRuta = $firmaDoc['doc_ruta'];
            }

            $resultadoEntrega = $this->crearEntregasVehiculoEnValidacion(
                $idPre,
                $idVehiculo,
                $_SESSION['usuario_id'] ?? 0,
                $fotosProcesadas,
                $descValidada,
                $firmaRuta
            );

            if (!$resultadoEntrega['success']) {
                error_log("PreoperacionalService: Error en entregas - " . $resultadoEntrega['message']);
            }
        }

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
            'preestado' => 'pendiente'
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
            if (!empty($idsDocumentos)) {
                $metadatosConDocs = $this->agregarIdsDocumentosAlJson($metadatosJson, $idsDocumentos);
                $this->model->actualizarCamposPreoperacional($idInsertado, [
                    'preencuesta' => $metadatosConDocs
                ]);
            }

            if ($kilometraje > 0 && $idVehiculo > 0) {
                $this->model->actualizarKilometrajeVehiculo($idVehiculo, $kilometraje);
            }

            // === ESQUEMA RELACIONAL: Siempre activo ===
            $versiones = $this->resolverVersionesAplicables($tipoVehiculo, $_SESSION['usuario_rol'] ?? 0);
            if (is_array($datosEncuesta)) {
                foreach ($versiones['ids_versiones'] as $idVersion) {
                    $this->guardarRespuestasRelacionales($idInsertado, $datosEncuesta, $idVersion);
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

            $ultimoSeguimiento = $this->model->obtenerUltimoSeguimientoPorVehiculo($idVehiculo);
            if ($ultimoSeguimiento
                && ($ultimoSeguimiento['tipo_evento'] ?? '') === 'REVISION_SST'
                && in_array($ultimoSeguimiento['estado_general'] ?? '', ['CON_NOVEDADES', 'FUERA_DE_SERVICIO'])) {
                $estadoGeneralSeguimiento = $ultimoSeguimiento['estado_general'];
                if (!empty($ultimoSeguimiento['observaciones'])) {
                    $observacionSeguimiento = $ultimoSeguimiento['observaciones'];
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

    /**
     * Crea los dos registros de entrega de vehículo al validar un preoperacional:
     * 1. ENTREGA FINAL (conductor → empresa)
     * 2. ENTREGA INICIAL (empresa → conductor)
     *
     * Se requieren 4 fotos: 2 para cada registro.
     *
     * @param int $idPreoperacional ID del registro preoperacional
     * @param int $idVehiculo ID del vehículo
     * @param int $idUsuario ID del conductor
     * @param array $fotos Array con las 4 rutas de fotos procesadas:
     *                      ['final_frente', 'final_trasera', 'inicial_frente', 'inicial_trasera']
     * @param string $observaciones Observaciones de la validación
     * @param string $firmaRuta Ruta de la firma del conductor (del preoperacional)
     * @return array Resultado de la operación
     */
    public function crearEntregasVehiculoEnValidacion($idPreoperacional, $idVehiculo, $idUsuario, $fotos, $observaciones = '', $firmaRuta = '')
    {
        // Obtener datos del vehículo para el texto descriptivo
        $vehiculoModel = new VehiculosModel();
        $datosVehiculo = $vehiculoModel->obtenerVehiculoPorId($idVehiculo);
        if (!$datosVehiculo) {
            return ['success' => false, 'message' => 'Vehículo no encontrado.'];
        }

        $entVehiculo = trim($datosVehiculo['veh_placa'] . ' ' . $datosVehiculo['veh_marca'] . ' ' . $datosVehiculo['veh_modelo']);
        $nombreValidador = $_SESSION['usuario_nombre'] ?? 'Sistema';
        $fechaHoy = date('Y-m-d');

        // Resolver idhojadevida desde la cédula del conductor
        $idHojaDeVida = $this->resolverHojaDeVida($idUsuario);

        // Obtener equipo de carretera del vehículo
        $equipoCarretera = $vehiculoModel->obtenerEquipoVehiculo($idVehiculo);
        $equipoJson = !empty($equipoCarretera) ? json_encode($equipoCarretera) : '[]';

        // Buscar el último seguimiento_vehiculo para ver si ya hay entregas vinculadas
        $seguimientoExistente = $this->model->obtenerUltimoSeguimientoPorVehiculo($idVehiculo);

        $errores = [];
        $idNuevoFinal = null;
        $idNuevoInicial = null;

        // 1. ENTREGA FINAL (conductor → empresa)
        $idEntregaFinalExistente = $seguimientoExistente['entrega_final_usuario'] ?? null;

        if ($idEntregaFinalExistente) {
            // UPDATE: el registro ya existe (creado desde la novedad), completar con datos de validación
            $datosUpdate = [
                'ent_firma' => $firmaRuta,
                'ent_observaciones' => $observaciones ?: ($seguimientoExistente['observaciones'] ?? ''),
            ];
            // Solo actualizar fotos si se enviaron nuevas
            if (!empty($fotos['final_frente'])) {
                $datosUpdate['ent_img_frente'] = $fotos['final_frente'];
            }
            if (!empty($fotos['final_trasera'])) {
                $datosUpdate['ent_img_trasera'] = $fotos['final_trasera'];
            }
            if (!empty($equipoJson)) {
                $datosUpdate['ent_equipo_carretera'] = $equipoJson;
            }
            $this->model->actualizarEntregaVehiculo($idEntregaFinalExistente, $datosUpdate);
        } else {
            // INSERT: crear nuevo registro de entrega
            $datosFinal = [
                'ent_fechaentrega' => $fechaHoy,
                'ent_vehiculo' => $entVehiculo,
                'ent_userregistra' => $nombreValidador,
                'ent_idusuario' => $idUsuario,
                'ent_tipoentrega' => 'final',
                'ent_fecharegistra' => $fechaHoy,
                'ent_idhojadevida' => $idHojaDeVida,
                'ent_sede' => '',
                'ent_img_frente' => $fotos['final_frente'] ?? '',
                'ent_img_trasera' => $fotos['final_trasera'] ?? '',
                'ent_equipo_carretera' => $equipoJson,
                'ent_observaciones' => $observaciones,
                'ent_firma' => $firmaRuta,
                'ent_firma_base64' => '' // Ya procesada
            ];

            $resultadoFinal = $this->model->insertarEntregaVehiculo($datosFinal);
            if (is_array($resultadoFinal) && isset($resultadoFinal['error'])) {
                $errores[] = 'Entrega FINAL: ' . $resultadoFinal['error'];
            } else {
                $idNuevoFinal = $resultadoFinal;
            }
        }

        // 2. ENTREGA INICIAL (empresa → conductor)
        $idEntregaInicialExistente = $seguimientoExistente['entrega_inicial_usuario'] ?? null;

        if ($idEntregaInicialExistente) {
            // UPDATE: el registro ya existe
            $datosUpdateInicial = [
                'ent_firma' => $firmaRuta,
                'ent_observaciones' => $observaciones ?: ($seguimientoExistente['observaciones'] ?? ''),
            ];
            if (!empty($fotos['inicial_frente'])) {
                $datosUpdateInicial['ent_img_frente'] = $fotos['inicial_frente'];
            }
            if (!empty($fotos['inicial_trasera'])) {
                $datosUpdateInicial['ent_img_trasera'] = $fotos['inicial_trasera'];
            }
            if (!empty($equipoJson)) {
                $datosUpdateInicial['ent_equipo_carretera'] = $equipoJson;
            }
            $this->model->actualizarEntregaVehiculo($idEntregaInicialExistente, $datosUpdateInicial);
        } else {
            // INSERT: crear nuevo registro
            $datosInicial = [
                'ent_fechaentrega' => $fechaHoy,
                'ent_vehiculo' => $entVehiculo,
                'ent_userregistra' => $nombreValidador,
                'ent_idusuario' => $idUsuario,
                'ent_tipoentrega' => 'inicial',
                'ent_fecharegistra' => $fechaHoy,
                'ent_idhojadevida' => $idHojaDeVida,
                'ent_sede' => '',
                'ent_img_frente' => $fotos['inicial_frente'] ?? '',
                'ent_img_trasera' => $fotos['inicial_trasera'] ?? '',
                'ent_equipo_carretera' => $equipoJson,
                'ent_observaciones' => $observaciones,
                'ent_firma' => $firmaRuta,
                'ent_firma_base64' => '' // Ya procesada
            ];

            $resultadoInicial = $this->model->insertarEntregaVehiculo($datosInicial);
            if (is_array($resultadoInicial) && isset($resultadoInicial['error'])) {
                $errores[] = 'Entrega INICIAL: ' . $resultadoInicial['error'];
            } else {
                $idNuevoInicial = $resultadoInicial;
            }
        }

        // Si se crearon nuevos registros Y hay un seguimiento existente, actualizar los FKs
        if (($idNuevoFinal || $idNuevoInicial) && $seguimientoExistente && isset($seguimientoExistente['id_seguimiento_vehiculo'])) {
            $camposUpdate = [];
            if ($idNuevoFinal) {
                $camposUpdate[] = 'entrega_final_usuario = ' . (int)$idNuevoFinal;
            }
            if ($idNuevoInicial) {
                $camposUpdate[] = 'entrega_inicial_usuario = ' . (int)$idNuevoInicial;
            }
            if (!empty($camposUpdate)) {
                $db = (new Database())->connect();
                $sql = "UPDATE seguimiento_vehiculo SET " . implode(', ', $camposUpdate)
                     . " WHERE id_seguimiento_vehiculo = " . (int)$seguimientoExistente['id_seguimiento_vehiculo'];
                $db->query($sql);
            }
        }

        if (!empty($errores)) {
            return ['success' => false, 'message' => implode(' | ', $errores)];
        }

        return ['success' => true, 'message' => 'Entregas de vehículo registradas correctamente.'];
    }

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
            'novedad_foto_evidencia'  => 'foto_evidencia',
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

        return $resultado;
    }

    /**
     * Crea un registro de entrega de vehículo para el flujo de novedad.
     * Similar a crearEntregasVehiculoEnValidacion pero para UN solo registro
     * y sin firma (se completará en la validación posterior).
     *
     * @param int $idVehiculo ID del vehículo
     * @param int $idUsuario ID del conductor/usuario
     * @param string $fotoFrente Ruta de la foto frontal
     * @param string $fotoTrasera Ruta de la foto trasera
     * @param string $observaciones Observaciones de la entrega
     * @param string $tipoEntrega 'final' o 'inicial'
     * @return int|null ID del registro insertado o null si falló
     */
    private function crearEntregaVehiculoNovedad($idVehiculo, $idUsuario, $fotoFrente, $fotoTrasera, $observaciones, $tipoEntrega)
    {
        $vehiculoModel = new VehiculosModel();
        $datosVehiculo = $vehiculoModel->obtenerVehiculoPorId($idVehiculo);
        if (!$datosVehiculo) {
            error_log("PreoperacionalService: Vehiculo $idVehiculo no encontrado para entrega novedad.");
            return null;
        }

        $entVehiculo = trim($datosVehiculo['veh_placa'] . ' ' . $datosVehiculo['veh_marca'] . ' ' . $datosVehiculo['veh_modelo']);
        $nombreValidador = $_SESSION['usuario_nombre'] ?? 'Sistema';
        $fechaHoy = date('Y-m-d');

        $idHojaDeVida = $this->resolverHojaDeVida($idUsuario);
        $equipoCarretera = $vehiculoModel->obtenerEquipoVehiculo($idVehiculo);
        $equipoJson = !empty($equipoCarretera) ? json_encode($equipoCarretera) : '[]';

        $datos = [
            'ent_fechaentrega'    => $fechaHoy,
            'ent_vehiculo'        => $entVehiculo,
            'ent_userregistra'    => $nombreValidador,
            'ent_idusuario'       => $idUsuario,
            'ent_tipoentrega'     => $tipoEntrega,
            'ent_fecharegistra'   => $fechaHoy,
            'ent_idhojadevida'    => $idHojaDeVida,
            'ent_sede'            => '',
            'ent_img_frente'      => $fotoFrente,
            'ent_img_trasera'     => $fotoTrasera,
            'ent_equipo_carretera'=> $equipoJson,
            'ent_observaciones'   => $observaciones,
            'ent_firma'           => '' // Se llenará en validación
        ];

        $resultado = $this->model->insertarEntregaVehiculo($datos);
        if (is_array($resultado) && isset($resultado['error'])) {
            error_log("PreoperacionalService: Error en entrega novedad ($tipoEntrega) - " . $resultado['error']);
            return null;
        }

        return $resultado; // Es el insert_id
    }

    /**
     * Procesa las 4 fotos de entrega del formulario de validación.
     *
     * @param array $files Array $_FILES
     * @return array Rutas guardadas ['final_frente', 'final_trasera', 'inicial_frente', 'inicial_trasera']
     */
    private function procesarFotosEntrega($files)
    {
        $resultado = [
            'final_frente' => '',
            'final_trasera' => '',
            'inicial_frente' => '',
            'inicial_trasera' => ''
        ];

        $mapeo = [
            'entrega_final_frente' => 'final_frente',
            'entrega_final_trasera' => 'final_trasera',
            'entrega_inicial_frente' => 'inicial_frente',
            'entrega_inicial_trasera' => 'inicial_trasera'
        ];

        foreach ($mapeo as $fileKey => $destKey) {
            if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $ruta = $this->procesarImagenEntrega($files[$fileKey], $fileKey);
                if ($ruta) {
                    $resultado[$destKey] = $ruta;
                }
            }
        }

        return $resultado;
    }

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
