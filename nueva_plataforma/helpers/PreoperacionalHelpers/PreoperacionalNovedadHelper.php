<?php
/**
 * PreoperacionalNovedadHelper - Maneja la lógica de novedades vehiculares
 * en el flujo preoperacional.
 *
 * Verifica el estado del vehículo asignado, muestra alertas de novedad,
 * y permite al usuario reportar cambios de estado.
 */

require_once __DIR__ . '/Services/PreoperacionalService.php';

class PreoperacionalNovedadHelper
{
    private $service;

    /**
     * @param PreoperacionalService|null $service Si no se provee, se crea uno por defecto.
     */
    public function __construct($service = null)
    {
        if ($service === null) {
            $service = new PreoperacionalService();
        }
        $this->service = $service;
    }

    /**
     * Verifica si el vehículo tiene una novedad activa
     * (estado FUERA_DE_SERVICIO o CON_NOVEDADES en el último seguimiento).
     *
     * Delega en PreoperacionalService::obtenerEstadoVehiculo() (fuente única de verdad)
     * y agrega fecha_registro como alias de conveniencia para las plantillas.
     *
     * @param int $idVehiculo ID del vehículo
     * @return array [tieneNovedad, esNovedadReportada, estado_general, observaciones, fecha_registro, ultimoSeguimiento]
     */
    public function verificarNovedadVehiculo($idVehiculo)
    {
        if ($idVehiculo <= 0) {
            return [
                'tieneNovedad' => false,
                'esNovedadReportada' => false,
                'estado_general' => 'OPTIMO',
                'observaciones' => '',
                'fecha_registro' => null,
                'ultimoSeguimiento' => null
            ];
        }

        // Delegar en el Service — fuente única de verdad para el estado del vehículo
        $estado = $this->service->obtenerEstadoVehiculo($idVehiculo);

        // Agregar fecha_registro como alias de conveniencia para las plantillas
        $estado['fecha_registro'] = $estado['ultimoSeguimiento']['fecha_registro'] ?? null;

        return $estado;
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
     * Genera el HTML del panel de alerta de novedad con formulario inline.
     * Ya no usa SweetAlert2 — el formulario se expande dentro del panel.
     *
     * @param array $novedad Resultado de verificarNovedadVehiculo()
     * @param array $datosVehiculo Datos del vehículo actual
     * @param array $vehiculosDisponibles Lista de vehículos alternativos
     * @return string HTML del panel de novedad
     */
    public function renderNovedadPanel($novedad, $datosVehiculo, $vehiculosDisponibles = [], $esValidacion = false)
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

        // Para FUERA_DE_SERVICIO el formulario se expande automáticamente.
        // En validación, nunca se expande ni se muestra el formulario interactivo.
        $expandirFormulario = ($estado === 'FUERA_DE_SERVICIO' && !$esValidacion);

        // Botón de reportar novedad — oculto en FUERA_DE_SERVICIO y en validación
        if (!$esValidacion) {
            $html .= '<div class="novedad-actions" id="novedadActions"' . ($expandirFormulario ? ' style="display:none;"' : '') . '>';
            $html .= '<button type="button" class="btn btn-novedad-reportar" id="btnReportarNovedad">';
            $html .= '<i class="fas fa-clipboard-list"></i> Reportar Novedad';
            $html .= '</button>';
            $html .= '</div>';
        }

        // --- FORMULARIO INLINE (expandido automáticamente en FUERA_DE_SERVICIO) ---
        // En validación, el formulario interactivo no se muestra — el validador
        // no reporta novedades; solo revisa las respuestas del operario.
        if (!$esValidacion) {
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
        $html .= '<i class="fas fa-camera"></i> FOTOS DE EVIDENCIA';
        $html .= '</h5>';
        $html .= '<p class="entrega-section-desc">Capture una o más fotos que evidencien el estado actual del vehículo. La primera foto es obligatoria.</p>';
        $html .= '<div class="novedad-multi-photo" id="novedadMultiPhotoContainer">';
        $html .= '<div class="row photo-item">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="photo-upload-container">';
        $html .= '<label class="photo-label"><i class="fas fa-image"></i> Foto evidencia 1 <span class="required-star">*</span></label>';
        $html .= '<input type="file" name="novedad_fotos[]" class="photo-input novedad-photo-input" accept="image/*" data-required-photo="true">';
        $html .= '<div class="photo-preview" style="margin-top:6px;"></div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarFotoNovedad" style="margin-top:8px;">';
        $html .= '<i class="fas fa-plus"></i> Agregar otra foto';
        $html .= '</button>';
        $html .= '<small class="photo-hint d-block" style="margin-top:4px;">Fotos de evidencia de la novedad del vehículo</small>';
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
            $html .= $this->renderVehiculoOptionHTML($v);
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
        } // Fin if (!$esValidacion) — formulario interactivo

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
                $html .= $this->renderVehiculoOptionHTML($v);
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
     * Genera el HTML de la sección de entrega de vehículo para el modo validación.
     *
     * Muestra las entregas (FINAL e INICIAL) creadas durante el flujo de novedad,
     * con sus fotos ya cargadas por el conductor. El validador puede ver las fotos
     * existentes o subir las faltantes.
     *
     * @param array $entregasPendientes ['final' => array|null, 'inicial' => array|null, 'seguimiento' => array|null]
     * @return string HTML de la sección de fotos de entrega
     */
    public function renderSeccionEntregaValidacion($entregasPendientes)
    {
        $entregaFinal = $entregasPendientes['final'] ?? null;
        $entregaInicial = $entregasPendientes['inicial'] ?? null;
        $nombreValidador = htmlspecialchars($_SESSION['usuario_nombre'] ?? '');
        $nombreConductor = htmlspecialchars($entregaFinal['ent_userregistra'] ?? $entregaInicial['ent_userregistra'] ?? '');

        // Cargar fotos de evidencia desde el seguimiento vehicular
        $fotosEvidencia = [];
        $seguimiento = $entregasPendientes['seguimiento'] ?? null;
        if ($seguimiento && !empty($seguimiento['id_seguimiento'])) {
            $fotosDocs = $this->service->obtenerDocumentosNovedadPorSeguimiento($seguimiento['id_seguimiento']);
            if (!empty($fotosDocs)) {
                foreach ($fotosDocs as $doc) {
                    if (!empty($doc['doc_ruta'])) {
                        $fotosEvidencia[] = $doc['doc_ruta'];
                    }
                }
            }
        }

        // --- Info pre-poblada compacta ---
        $html = '<div class="entrega-info-bar">';
        $html .= '<span><i class="fas fa-user"></i> <strong>Conductor:</strong> ' . $nombreConductor . '</span>';
        $html .= '<span class="entrega-info-sep">|</span>';
        $html .= '<span><i class="fas fa-user-check"></i> <strong>Validador:</strong> ' . $nombreValidador . '</span>';
        $html .= '</div>';

        // Observaciones de la novedad (común a ambas entregas)
        $obsNovedad = htmlspecialchars($entregaFinal['ent_observaciones'] ?? $entregaInicial['ent_observaciones'] ?? '');
        if ($obsNovedad) {
            $html .= '<div class="novedad-vehicle-info" style="margin-bottom:16px;">';
            $html .= '<p><strong>Motivo de la novedad:</strong> ' . $obsNovedad . '</p>';
            $html .= '</div>';
        }

        // --- Galería de fotos de evidencia ---
        if (!empty($fotosEvidencia)) {
            $html .= '<div class="novedad-photo-section" style="margin-bottom:16px;">';
            $html .= '<h5 class="entrega-section-label"><i class="fas fa-images"></i> EVIDENCIA FOTOGRÁFICA DE LA NOVEDAD</h5>';
            $html .= '<div class="row">';
            foreach ($fotosEvidencia as $ruta) {
                $url = '../' . ltrim(str_replace('\\', '/', $ruta), '/');
                $html .= '<div class="col-md-4 col-sm-6 mb-3">';
                $html .= '<a href="' . htmlspecialchars($url) . '" target="_blank">';
                $html .= '<img src="' . htmlspecialchars($url) . '" alt="Foto evidencia" style="width:100%; max-height:200px; object-fit:cover; border-radius:8px; border:2px solid rgba(0,0,0,0.1);">';
                $html .= '</a>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        // --- SECCIÓN 1: ENTREGA FINAL (conductor → empresa) ---
        $html .= $this->renderEntregaCard(
            'final',
            'ENTREGA FINAL — Conductor → Empresa',
            'El conductor <strong>' . $nombreConductor . '</strong> devuelve el vehículo. Recibe: <strong>' . $nombreValidador . '</strong>',
            $entregaFinal
        );

        // --- SECCIÓN 2: ENTREGA INICIAL (empresa → conductor) ---
        $html .= $this->renderEntregaCard(
            'inicial',
            'ENTREGA INICIAL — Empresa → Conductor',
            'La empresa entrega el vehículo al conductor <strong>' . $nombreConductor . '</strong>. Entrega: <strong>' . $nombreValidador . '</strong>',
            $entregaInicial
        );

        return $html;
    }

    /**
     * Renderiza una tarjeta individual de entrega (FINAL o INICIAL).
     * Si la entrega ya tiene fotos, las muestra como imágenes.
     * Si no, muestra campos de upload.
     */
    private function renderEntregaCard($tipo, $titulo, $descripcion, $entrega)
    {
        $vehiculo = htmlspecialchars($entrega['ent_vehiculo'] ?? 'Sin datos');
        $fotoFrente = $entrega['ent_img_frente'] ?? '';
        $fotoTrasera = $entrega['ent_img_trasera'] ?? '';
        $tieneFirma = !empty($entrega['ent_firma']);

        $html = '<div class="entrega-photo-section">';
        $html .= '<h5 class="entrega-section-label">';
        $html .= '<i class="fas ' . ($tipo === 'final' ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket') . '"></i> ';
        $html .= $titulo;
        $html .= '</h5>';
        $html .= '<p class="entrega-section-desc">' . $descripcion . '</p>';
        $html .= '<p style="font-weight:600; color:#074F91; margin-bottom:12px;">';
        $html .= '<i class="fas fa-car"></i> Vehículo: ' . $vehiculo;
        $html .= '</p>';

        $html .= '<div class="row">';
        // Foto frontal
        $html .= '<div class="col-md-6">';
        $html .= $this->renderFotoEntrega(
            'entrega_' . $tipo . '_frente',
            'Foto FRENTE del vehículo',
            'Foto frontal — ' . ($tipo === 'final' ? 'salida' : 'entrada'),
            $fotoFrente
        );
        $html .= '</div>';
        // Foto trasera
        $html .= '<div class="col-md-6">';
        $html .= $this->renderFotoEntrega(
            'entrega_' . $tipo . '_trasera',
            'Foto TRASERA del vehículo',
            'Foto trasera — ' . ($tipo === 'final' ? 'salida' : 'entrada'),
            $fotoTrasera
        );
        $html .= '</div>';
        $html .= '</div>'; // /row

        // Si la entrega ya tiene firma, mostrar indicador
        if ($tieneFirma) {
            $html .= '<div style="margin-top:10px; padding:8px 12px; background:rgba(40,167,69,0.08); border-radius:8px; font-size:13px;">';
            $html .= '<i class="fas fa-check-circle" style="color:#28a745;"></i> Firma registrada';
            $html .= '</div>';
        }

        $html .= '</div>'; // /entrega-photo-section
        return $html;
    }

    /**
     * Renderiza una celda de foto: si ya existe, muestra la imagen.
     * Si no, muestra el input de upload.
     */
    private function renderFotoEntrega($inputId, $label, $hint, $rutaRelativa)
    {
        $html = '<div class="photo-upload-container">';
        $html .= '<label class="photo-label" for="' . $inputId . '">';
        $html .= '<i class="fas fa-camera"></i> ' . $label;
        if (empty($rutaRelativa)) {
            $html .= ' <span class="required-star">*</span>';
        }
        $html .= '</label>';

        if (!empty($rutaRelativa)) {
            // La ruta es relativa a nueva_plataforma (ej: uploads/vehiculos/xxx.png)
            // Consistente con VehiculosModel::guardarImagen() y PreoperacionalService::procesarImagenEntrega()
            $rutaAbsoluta = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
            if (file_exists($rutaAbsoluta)) {
                $url = '../' . $rutaRelativa;
                $html .= '<a href="' . htmlspecialchars($url) . '" target="_blank" style="display:block;">';
                $html .= '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($label) . '" ';
                $html .= 'style="max-width:100%; max-height:200px; border-radius:8px; border:2px solid rgba(0,0,0,0.1);">';
                $html .= '</a>';
                $html .= '<small class="photo-hint">' . htmlspecialchars($hint) . ' — <a href="' . htmlspecialchars($url) . '" target="_blank">ver ampliada</a></small>';
                // Marcador para el JS: esta foto ya existe, no requerir re-subida
                $html .= '<input type="hidden" name="' . $inputId . '_existente" value="1" data-foto-existente="true">';
            } else {
                $html .= '<div style="padding:12px; background:#fff3cd; border-radius:6px; font-size:12px; color:#856404;">';
                $html .= '<i class="fas fa-exclamation-triangle"></i> Archivo no encontrado: ' . htmlspecialchars($rutaRelativa);
                $html .= '</div>';
                $html .= '<input type="file" name="' . $inputId . '" id="' . $inputId . '" ';
                $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
                $html .= '<small class="photo-hint">' . htmlspecialchars($hint) . '</small>';
            }
        } else {
            // Sin foto previa: campo de upload
            $html .= '<input type="file" name="' . $inputId . '" id="' . $inputId . '" ';
            $html .= 'class="photo-input" accept="image/*" data-required-photo="true">';
            $html .= '<small class="photo-hint">' . htmlspecialchars($hint) . '</small>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza una etiqueta <option> para un vehículo disponible.
     * Extraído como helper privado para eliminar 3 copias idénticas del mismo bloque
     * en renderNovedadPanel, renderNoVehiclePanel y (el ya eliminado) renderDropdownVehiculos.
     */
    private function renderVehiculoOptionHTML($v)
    {
        $tipo   = htmlspecialchars($v['veh_tipo'] ?? '');
        $placa  = htmlspecialchars($v['veh_placa'] ?? '');
        $marca  = htmlspecialchars($v['veh_marca'] ?? '');
        $modelo = htmlspecialchars($v['veh_modelo'] ?? '');
        $id     = (int) ($v['idvehiculos'] ?? 0);
        $label  = $tipo . ' - ' . $placa . ' - ' . $marca . ' ' . $modelo;
        return '<option value="' . $id . '">' . htmlspecialchars($label) . '</option>';
    }
}
