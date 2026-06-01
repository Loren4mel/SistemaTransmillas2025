<?php
/**
 * PreoperacionalNovedadHelper - Maneja la lógica de novedades vehiculares
 * en el flujo preoperacional.
 *
 * Verifica el estado del vehículo asignado, muestra alertas de novedad,
 * y permite al usuario reportar cambios de estado.
 */

require_once __DIR__ . '/../../model/PreoperacionalModel.php';

class PreoperacionalNovedadHelper
{
    private $model;

    public function __construct()
    {
        $this->model = new PreoperacionalModel();
    }

    /**
     * Verifica si el vehículo tiene una novedad activa
     * (estado FUERA_DE_SERVICIO o CON_NOVEDADES en el último seguimiento).
     *
     * @param int $idVehiculo ID del vehículo
     * @return array [tieneNovedad => bool, estado_general => string, observaciones => string, ultimoSeguimiento => array|null]
     */
    public function verificarNovedadVehiculo($idVehiculo)
    {
        if ($idVehiculo <= 0) {
            return [
                'tieneNovedad' => false,
                'estado_general' => 'OPTIMO',
                'observaciones' => '',
                'ultimoSeguimiento' => null
            ];
        }

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
            'fecha_registro' => $ultimo['fecha_registro'] ?? '',
            'ultimoSeguimiento' => $ultimo
        ];
    }

    /**
     * Determina si la novedad es de tipo FUERA_DE_SERVICIO.
     *
     * @param array $novedad Resultado de verificarNovedadVehiculo()
     * @return bool
     */
    public function esFueraDeServicio($novedad)
    {
        return ($novedad['estado_general'] ?? '') === 'FUERA_DE_SERVICIO';
    }

    /**
     * Obtiene la lista de vehículos disponibles (activos y sin conductor asignado).
     *
     * @return array Lista de vehículos [idvehiculos, veh_tipo, veh_placa, veh_marca, veh_modelo]
     */
    public function obtenerVehiculosDisponibles()
    {
        return $this->model->obtenerVehiculosSinConductor();
    }

    /**
     * Genera el HTML del panel de alerta de novedad con formulario inline.
     * Ya no usa SweetAlert2 — el formulario se expande dentro del panel.
     *
     * @param array $novedad Resultado de verificarNovedadVehiculo()
     * @param array $datosVehiculo Datos del vehículo actual
     * @param array $vehiculosDisponibles Lista de vehículos alternativos
     * @return string HTML del panel de novedad
     */
    public function renderNovedadPanel($novedad, $datosVehiculo, $vehiculosDisponibles = [])
    {
        $placa = htmlspecialchars($datosVehiculo['veh_placa'] ?? 'N/A');
        $marca = htmlspecialchars($datosVehiculo['veh_marca'] ?? '');
        $modelo = htmlspecialchars($datosVehiculo['veh_modelo'] ?? '');
        $idVehiculo = (int)($datosVehiculo['idvehiculos'] ?? 0);
        $estado = $novedad['estado_general'] ?? 'OPTIMO';
        $tieneNovedad = $novedad['tieneNovedad'] ?? false;
        $fechaNovedad = $novedad['fecha_registro'] ?? '';
        $observaciones = htmlspecialchars($novedad['observaciones'] ?? '');

        // Determinar clase, badge e ícono según el estado
        if ($estado === 'FUERA_DE_SERVICIO') {
            $estadoClass = 'novedad-fuera-servicio';
            $estadoLabel = 'FUERA DE SERVICIO';
            $estadoIcon = 'fa-times-circle';
        } elseif ($estado === 'CON_NOVEDADES') {
            $estadoClass = 'novedad-con-novedades';
            $estadoLabel = 'CON NOVEDADES';
            $estadoIcon = 'fa-exclamation-triangle';
        } else {
            $estadoClass = 'novedad-optimo';
            $estadoLabel = 'SIN NOVEDADES';
            $estadoIcon = 'fa-check-circle';
        }

        $html = '<div class="preop-card novedad-panel ' . $estadoClass . '" id="novedadPanel" '
              . 'data-idvehiculo="' . $idVehiculo . '" '
              . 'data-placa="' . $placa . '" '
              . 'data-marca="' . $marca . '" '
              . 'data-modelo="' . $modelo . '" '
              . 'data-estado="' . $estado . '">';
        $tituloPanel = $tieneNovedad ? 'NOVEDAD VEHICULAR' : 'ESTADO DEL VEHÍCULO';
        $html .= '<div class="preop-card-header">';
        $html .= '<i class="fas ' . $estadoIcon . '"></i> ';
        $html .= '<strong>' . $tituloPanel . '</strong>';
        $html .= '</div>';
        $html .= '<div class="preop-card-body">';

        // Info del vehículo
        $html .= '<div class="novedad-vehicle-info">';
        $html .= '<p><strong>Vehículo asignado:</strong> ' . $placa . ' - ' . $marca . ' ' . $modelo . '</p>';
        $html .= '<p><strong>Estado:</strong> <span class="novedad-badge badge-' . $estadoClass . '">' . $estadoLabel . '</span></p>';
        if ($tieneNovedad && $fechaNovedad) {
            $html .= '<p><strong>Fecha de la novedad:</strong> ' . htmlspecialchars($fechaNovedad) . '</p>';
        }
        if ($tieneNovedad && $observaciones) {
            $html .= '<p><strong>Última observación:</strong> ' . $observaciones . '</p>';
        }
        // Alerta específica para FUERA_DE_SERVICIO
        if ($estado === 'FUERA_DE_SERVICIO') {
            $html .= '<div class="novedad-fuera-servicio-alert" style="background:#fff5f5; border:2px solid #dc3545; border-radius:10px; padding:16px; margin-top:14px;">';
            $html .= '<p style="margin:0 0 8px 0; color:#dc3545; font-size:1.1em; font-weight:bold;">';
            $html .= '<i class="fas fa-ban"></i> Este vehículo está FUERA DE SERVICIO</p>';
            $html .= '<p style="margin:0; font-size:0.95em;">El vehículo no se encuentra en condiciones de operar. ';
            $html .= 'Debe <strong>seleccionar otro vehículo</strong> del listado o continuar sin vehículo asignado.</p>';
            $html .= '</div>';
        }

        $html .= '</div>';

        // Para FUERA_DE_SERVICIO el formulario se expande automáticamente
        $expandirFormulario = ($estado === 'FUERA_DE_SERVICIO');

        // Botón de reportar novedad (oculto en FUERA_DE_SERVICIO)
        $html .= '<div class="novedad-actions" id="novedadActions"' . ($expandirFormulario ? ' style="display:none;"' : '') . '>';
        $html .= '<button type="button" class="btn btn-novedad-reportar" id="btnReportarNovedad">';
        $html .= '<i class="fas fa-clipboard-list"></i> Reportar Novedad';
        $html .= '</button>';
        $html .= '</div>';

        // --- FORMULARIO INLINE (expandido automáticamente en FUERA_DE_SERVICIO) ---
        $html .= '<div class="novedad-inline-form" id="novedadInlineForm"' . ($expandirFormulario ? '' : ' style="display:none;"') . '>';
        $html .= '<hr class="novedad-divider">';
        $html .= '<div class="novedad-form-body">';

        // Pregunta: ¿Puede ser operado?
        $html .= '<div class="novedad-form-group">';
        $html .= '<label class="novedad-form-label"><strong>¿El vehículo puede ser operado?</strong></label>';
        $html .= '<div class="novedad-radio-group">';
        if ($estado === 'FUERA_DE_SERVICIO') {
            // FUERA_DE_SERVICIO: solo opción "No" disponible
            $html .= '<label class="novedad-radio-label">';
            $html .= '<input type="radio" name="puede_operar" value="no" class="novedad-radio-input" checked> ';
            $html .= '<span class="novedad-radio-text">No — El vehículo está fuera de servicio</span>';
            $html .= '</label>';
            $html .= '<input type="hidden" name="puede_operar_fijo" value="no">';
        } else {
            $html .= '<label class="novedad-radio-label">';
            $html .= '<input type="radio" name="puede_operar" value="si" class="novedad-radio-input"> ';
            $html .= '<span class="novedad-radio-text">Sí — El vehículo está en condiciones</span>';
            $html .= '</label>';
            $html .= '<label class="novedad-radio-label">';
            $html .= '<input type="radio" name="puede_operar" value="no" class="novedad-radio-input"> ';
            $html .= '<span class="novedad-radio-text">No — El vehículo tiene problemas</span>';
            $html .= '</label>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // --- Foto de evidencia general (visible al seleccionar cualquier radio, o siempre en FUERA_DE_SERVICIO) ---
        $html .= '<div class="novedad-photo-section" id="novedadFotoGeneralGroup"' . ($expandirFormulario ? '' : ' style="display:none;"') . '>';
        $html .= '<h5 class="entrega-section-label">';
        $html .= '<i class="fas fa-camera"></i> FOTO DE EVIDENCIA GENERAL';
        $html .= '</h5>';
        $html .= '<p class="entrega-section-desc">Capture una foto que evidencie el estado actual del vehículo.</p>';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="novedad_foto_evidencia">';
        $html .= '<i class="fas fa-image"></i> Foto evidencia <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="novedad_foto_evidencia" id="novedad_foto_evidencia" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto general de la novedad del vehículo</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // --- Fotos de salida / entrega FINAL (visible con NO, o siempre en FUERA_DE_SERVICIO) ---
        $html .= '<div class="novedad-photo-section novedad-condicional" id="novedadSalidaPhotosGroup"' . ($expandirFormulario ? '' : ' style="display:none;"') . '>';
        $html .= '<hr class="novedad-divider">';
        $html .= '<h5 class="entrega-section-label">';
        $html .= '<i class="fas fa-arrow-right-to-bracket"></i> ';
        $html .= 'FLUJO DE SALIDA — Conductor &rarr; Empresa';
        $html .= '</h5>';
        $html .= '<p class="entrega-section-desc">Fotos del vehículo actual (<strong>' . $placa . ' - ' . $marca . ' ' . $modelo . '</strong>) al momento de la entrega.</p>';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="novedad_salida_frente">';
        $html .= '<i class="fas fa-camera"></i> Foto FRENTE <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="novedad_salida_frente" id="novedad_salida_frente" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto frontal — salida del vehículo</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="novedad_salida_trasera">';
        $html .= '<i class="fas fa-camera"></i> Foto TRASERA <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="novedad_salida_trasera" id="novedad_salida_trasera" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto trasera — salida del vehículo</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Descripción del problema (visible con NO, o siempre en FUERA_DE_SERVICIO)
        $html .= '<div class="novedad-form-group novedad-condicional" id="novedadObservacionGroup"' . ($expandirFormulario ? '' : ' style="display:none;"') . '>';
        $html .= '<label class="novedad-form-label" for="novedadObservacion"><strong>Descripción del problema:</strong></label>';
        $html .= '<textarea id="novedadObservacion" class="novedad-textarea" rows="3" placeholder="Describa el problema encontrado..."></textarea>';
        $html .= '</div>';

        // Vehículo alternativo (visible con NO, o siempre en FUERA_DE_SERVICIO)
        $html .= '<div class="novedad-form-group novedad-condicional" id="novedadVehiculoGroup"' . ($expandirFormulario ? '' : ' style="display:none;"') . '>';
        if ($estado === 'FUERA_DE_SERVICIO') {
            $html .= '<label class="novedad-form-label" for="novedadVehiculoSelect"><strong>Seleccione un vehículo alternativo:</strong> <span style="color:#dc3545;">(requerido — el vehículo actual está fuera de servicio)</span></label>';
        } else {
            $html .= '<label class="novedad-form-label" for="novedadVehiculoSelect"><strong>Vehículo alternativo (opcional):</strong></label>';
        }
        $html .= '<select id="novedadVehiculoSelect" class="novedad-select">';
        $html .= '<option value="">-- No seleccionar vehículo --</option>';
        foreach ($vehiculosDisponibles as $v) {
            $tipo = htmlspecialchars($v['veh_tipo'] ?? '');
            $vPlaca = htmlspecialchars($v['veh_placa'] ?? '');
            $vMarca = htmlspecialchars($v['veh_marca'] ?? '');
            $vModelo = htmlspecialchars($v['veh_modelo'] ?? '');
            $vid = (int)($v['idvehiculos'] ?? 0);
            $label = $tipo . ' - ' . $vPlaca . ' - ' . $vMarca . ' ' . $vModelo;
            $html .= '<option value="' . $vid . '">' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select>';
        $html .= '<small class="novedad-form-hint">Si no selecciona un vehículo, quedará sin vehículo asignado.</small>';
        $html .= '</div>';

        // --- Fotos de entrada / entrega INICIAL (visible con NO + vehículo nuevo, o siempre en FUERA_DE_SERVICIO) ---
        $html .= '<div class="novedad-photo-section novedad-condicional" id="novedadEntradaPhotosGroup"' . ($expandirFormulario ? '' : ' style="display:none;"') . '>';
        $html .= '<hr class="novedad-divider">';
        $html .= '<h5 class="entrega-section-label">';
        $html .= '<i class="fas fa-arrow-right-from-bracket"></i> ';
        $html .= 'FLUJO DE ENTRADA — Empresa &rarr; Conductor';
        $html .= '</h5>';
        $html .= '<p class="entrega-section-desc">Fotos del nuevo vehículo asignado al momento de la recepción.</p>';
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="novedad_entrada_frente">';
        $html .= '<i class="fas fa-camera"></i> Foto FRENTE <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="novedad_entrada_frente" id="novedad_entrada_frente" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto frontal — entrada del nuevo vehículo</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="novedad_entrada_trasera">';
        $html .= '<i class="fas fa-camera"></i> Foto TRASERA <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="novedad_entrada_trasera" id="novedad_entrada_trasera" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto trasera — entrada del nuevo vehículo</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Botones de acción
        $html .= '<div class="novedad-form-buttons">';
        $html .= '<button type="button" class="btn btn-novedad-guardar" id="btnGuardarNovedad">';
        $html .= '<i class="fas fa-save"></i> Guardar Novedad';
        $html .= '</button>';
        $html .= '<button type="button" class="btn btn-novedad-cancelar" id="btnCancelarNovedad">';
        $html .= '<i class="fas fa-times"></i> Cancelar';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '</div>'; // /novedad-form-body
        $html .= '</div>'; // /novedad-inline-form

        $html .= '</div>'; // /preop-card-body
        $html .= '</div>'; // /novedad-panel

        return $html;
    }

    /**
     * Genera el HTML del panel de asignación de vehículo cuando el usuario
     * no tiene un vehículo asignado.
     *
     * @param array $vehiculosDisponibles Lista de vehículos disponibles
     * @return string HTML del panel
     */
    public function renderNoVehiclePanel($vehiculosDisponibles)
    {
        $html = '<div class="preop-card novedad-panel novedad-no-vehiculo" id="novedadPanel" '
              . 'data-idvehiculo="0" '
              . 'data-estado="SIN_VEHICULO">';
        $html .= '<div class="preop-card-header">';
        $html .= '<i class="fas fa-car-side"></i> ';
        $html .= '<strong>ASIGNACIÓN DE VEHÍCULO</strong>';
        $html .= '</div>';
        $html .= '<div class="preop-card-body">';

        // Mensaje informativo
        $html .= '<div class="novedad-vehicle-info">';
        $html .= '<p><strong>No tiene un vehículo asignado.</strong></p>';
        $html .= '<p>Seleccione un vehículo de la lista para iniciar el preoperacional.</p>';
        $html .= '</div>';

        if (empty($vehiculosDisponibles)) {
            // Sin vehículos disponibles
            $html .= '<div class="novedad-fuera-servicio-alert" style="background:#fffbf0; border:2px solid #ffc107; border-radius:10px; padding:16px; margin-top:14px;">';
            $html .= '<p style="margin:0; color:#856404; font-size:1em; font-weight:bold;">';
            $html .= '<i class="fas fa-exclamation-circle"></i> No hay vehículos disponibles</p>';
            $html .= '<p style="margin:4px 0 0 0; font-size:0.9em; color:#856404;">';
            $html .= 'Por favor contacte al administrador para que le asigne un vehículo.';
            $html .= '</p>';
            $html .= '</div>';
        } else {
            // Dropdown de vehículos disponibles
            $html .= '<div class="novedad-form-group">';
            $html .= '<label class="novedad-form-label" for="asignarVehiculoSelect">';
            $html .= '<strong>Vehículos disponibles:</strong>';
            $html .= '</label>';
            $html .= '<select id="asignarVehiculoSelect" class="novedad-select">';
            $html .= '<option value="">-- Seleccione un vehículo --</option>';
            foreach ($vehiculosDisponibles as $v) {
                $tipo = htmlspecialchars($v['veh_tipo'] ?? '');
                $vPlaca = htmlspecialchars($v['veh_placa'] ?? '');
                $vMarca = htmlspecialchars($v['veh_marca'] ?? '');
                $vModelo = htmlspecialchars($v['veh_modelo'] ?? '');
                $vid = (int) ($v['idvehiculos'] ?? 0);
                $label = $tipo . ' - ' . $vPlaca . ' - ' . $vMarca . ' ' . $vModelo;
                $html .= '<option value="' . $vid . '">' . htmlspecialchars($label) . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';

            // Botón de asignar
            $html .= '<div class="novedad-form-buttons">';
            $html .= '<button type="button" class="btn btn-novedad-guardar" id="btnAsignarVehiculo">';
            $html .= '<i class="fas fa-check"></i> Asignar Vehículo';
            $html .= '</button>';
            $html .= '</div>';
        }

        $html .= '</div>'; // /preop-card-body
        $html .= '</div>'; // /novedad-panel

        return $html;
    }

    /**
     * Genera el HTML de la sección de fotos de entrega para el modo validación.
     * Usa el mismo patrón de photo-upload-container que el resto del formulario
     * para mantener consistencia visual y buen funcionamiento en dispositivos móviles.
     *
     * @param array $datosVehiculo Datos del vehículo
     * @param array $datosConductor Datos del conductor (nombre, identificacion)
     * @return string HTML de la sección de fotos de entrega
     */
    public function renderSeccionEntregaValidacion($datosVehiculo, $datosConductor)
    {
        $placa = htmlspecialchars($datosVehiculo['veh_placa'] ?? 'N/A');
        $marca = htmlspecialchars($datosVehiculo['veh_marca'] ?? '');
        $modelo = htmlspecialchars($datosVehiculo['veh_modelo'] ?? '');
        $nombreConductor = htmlspecialchars($datosConductor['usu_nombre'] ?? '');
        $idConductor = htmlspecialchars($datosConductor['usu_identificacion'] ?? '');
        $nombreValidador = htmlspecialchars($_SESSION['usuario_nombre'] ?? '');

        // --- Info pre-poblada compacta ---
        $html = '<div class="entrega-info-bar">';
        $html .= '<span><i class="fas fa-user"></i> <strong>Conductor:</strong> ' . $nombreConductor . ' (' . $idConductor . ')</span>';
        $html .= '<span class="entrega-info-sep">|</span>';
        $html .= '<span><i class="fas fa-car"></i> <strong>Vehículo:</strong> ' . $placa . ' - ' . $marca . ' ' . $modelo . '</span>';
        $html .= '<span class="entrega-info-sep">|</span>';
        $html .= '<span><i class="fas fa-user-check"></i> <strong>Validador:</strong> ' . $nombreValidador . '</span>';
        $html .= '</div>';

        // --- SECCIÓN 1: ENTREGA FINAL (conductor → empresa) ---
        $html .= '<div class="entrega-photo-section">';
        $html .= '<h5 class="entrega-section-label">';
        $html .= '<i class="fas fa-arrow-right-to-bracket"></i> ';
        $html .= 'ENTREGA FINAL — Conductor → Empresa';
        $html .= '</h5>';
        $html .= '<p class="entrega-section-desc">';
        $html .= 'El conductor <strong>' . $nombreConductor . '</strong> devuelve el vehículo. ';
        $html .= 'Recibe: <strong>' . $nombreValidador . '</strong>';
        $html .= '</p>';

        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="entrega_final_frente">';
        $html .= '<i class="fas fa-camera"></i> Foto FRENTE del vehículo <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="entrega_final_frente" id="entrega_final_frente" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto frontal — entrega final</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="entrega_final_trasera">';
        $html .= '<i class="fas fa-camera"></i> Foto TRASERA del vehículo <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="entrega_final_trasera" id="entrega_final_trasera" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto trasera — entrega final</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>'; // /row
        $html .= '</div>'; // /entrega-photo-section

        // --- SECCIÓN 2: ENTREGA INICIAL (empresa → conductor) ---
        $html .= '<div class="entrega-photo-section">';
        $html .= '<h5 class="entrega-section-label">';
        $html .= '<i class="fas fa-arrow-right-from-bracket"></i> ';
        $html .= 'ENTREGA INICIAL — Empresa → Conductor';
        $html .= '</h5>';
        $html .= '<p class="entrega-section-desc">';
        $html .= 'La empresa entrega el vehículo al conductor <strong>' . $nombreConductor . '</strong>. ';
        $html .= 'Entrega: <strong>' . $nombreValidador . '</strong>';
        $html .= '</p>';

        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="entrega_inicial_frente">';
        $html .= '<i class="fas fa-camera"></i> Foto FRENTE del vehículo <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="entrega_inicial_frente" id="entrega_inicial_frente" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto frontal — entrega inicial</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="col-md-6">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="entrega_inicial_trasera">';
        $html .= '<i class="fas fa-camera"></i> Foto TRASERA del vehículo <span class="required-star">*</span>';
        $html .= '</label>';
        $html .= '<input type="file" name="entrega_inicial_trasera" id="entrega_inicial_trasera" ';
        $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<small class="photo-hint">Foto trasera — entrega inicial</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>'; // /row
        $html .= '</div>'; // /entrega-photo-section

        return $html;
    }

    /**
     * Genera el HTML del dropdown de vehículos disponibles para el modal de novedad.
     *
     * @param array $vehiculos Lista de vehículos disponibles
     * @return string HTML del dropdown
     */
    public function renderDropdownVehiculos($vehiculos)
    {
        if (empty($vehiculos)) {
            return '<div class="alert alert-warning">No hay vehículos disponibles en este momento.</div>';
        }

        $html = '<div class="form-group">';
        $html .= '<label for="selectVehiculoNuevo"><strong>Seleccionar vehículo alternativo (opcional):</strong></label>';
        $html .= '<select class="form-control" id="selectVehiculoNuevo" name="idvehiculo_nuevo">';
        $html .= '<option value="">-- No seleccionar vehículo --</option>';

        foreach ($vehiculos as $v) {
            $tipo = htmlspecialchars($v['veh_tipo'] ?? '');
            $placa = htmlspecialchars($v['veh_placa'] ?? '');
            $marca = htmlspecialchars($v['veh_marca'] ?? '');
            $modelo = htmlspecialchars($v['veh_modelo'] ?? '');
            $id = (int) ($v['idvehiculos'] ?? 0);

            $label = $tipo . ' - ' . $placa . ' - ' . $marca . ' ' . $modelo;
            $html .= '<option value="' . $id . '">' . htmlspecialchars($label) . '</option>';
        }

        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }
}
