<?php
/**
 * PreoperacionalNuevaEncuestaViewHelper - Helper para la nueva encuesta de preoperacional
 *
 * Esta clase centraliza la lógica de presentación del NUEVO formato de encuesta
 * que se cargará por defecto al ingresar a la página.
 * 
 * Estructura basada en roles:
 * - Administrativo (Call center, líder de sede, jefe de operación)
 * - Conductor (Carro)
 * - Vehículo propio (Moto)
 * - Auxiliar de carga
 * 
 * El formato legado se mantiene para validación de preoperacionales anteriores.
 */

class PreoperacionalNuevaEncuestaViewHelper
{
    // ==================== PREGUNTAS ADMINISTRATIVO ====================
    
    /**
     * Preguntas para personal administrativo (Call center, líder de sede, jefe de operación)
     * Se valida con rol del usuario, no se presentan a otros tipos de usuarios
     *
     * @return array Array de preguntas con [nombre, texto]
     */
    public static function getPreguntasAdministrativo()
    {
        return [
            ['admin_1', '¿Se siente físicamente en capacidad de desarrollar sus labores hoy?'],
            ['admin_2', '¿Presenta alguna molestia osteomuscular (dolor en espalda, cuello, hombros o muñecas) derivada de su jornada anterior?'],
            ['admin_3', '¿Ha experimentado fatiga visual, mareos o dolores de cabeza intensos en las últimas 24 horas?'],
            ['admin_4', '¿Se compromete a realizar sus pausas activas (mínimo 5 minutos por cada 2 horas de trabajo continuo)?'],
            ['admin_5', '¿Confirma que se encuentra libre de efectos de alcohol o sustancias psicoactivas?']
        ];
    }

    // ==================== PREGUNTAS CONDUCTOR (CARRO) ====================
    
    /**
     * Preguntas de ingreso personal para cargo conductor (carro)
     * Validado antes de la carga, no se muestran a personal administrativo
     * Se responden con SÍ o NO
     *
     * @return array Array de preguntas con [nombre, texto]
     */
    public static function getPreguntasConductor()
    {
        return [
            ['conductor_1', '¿Presenta signos de somnolencia o fatiga acumulada?'],
            ['conductor_2', '¿Reporta fatiga visual o irritación ocular o visión borrosa o dificultad para mantener la apertura ocular?'],
            ['conductor_3', '¿Presenta disminución en su nivel de alerta o concentración?'],
            ['conductor_4', '¿Se declara apto física y mentalmente para la tarea?'],
            ['conductor_5', '¿Se encuentra libre de influencia de sustancias psicoactivas y/o alcohol y/o medicamentos?']
        ];
    }

    // ==================== PREOPERACIONAL VEHÍCULO (CARRO) ====================
    
    /**
     * Preguntas de preoperacional del vehículo (carro)
     * Chequeo simple de cada pregunta en cada sección
     *
     * @return array Array de secciones con sus preguntas
     */
    public static function getPreguntasVehiculoCarro()
    {
        return [
            'inspeccion_inicial' => [
                'titulo' => '🔍 INSPECCIÓN INICIAL',
                'preguntas' => [
                    ['inspec_1', 'El vehículo retirado del parqueadero se encuentra en óptimas condiciones', 'require_photo' => true]
                ],
                'especial' => true
            ],
            'luces' => [
                'titulo' => '💡 LUCES',
                'preguntas' => [
                    ['luces_1', 'Estado de luces (altas, bajas, estacionarias, direccionales) en buen estado']
                ]
            ],
            'cabina' => [
                'titulo' => '🚗 CABINA',
                'preguntas' => [
                    ['cabina_1', 'Espejo central y laterales en buen estado'],
                    ['cabina_2', 'Cojinería en buen estado']
                ]
            ],
            'dispositivos_seguridad' => [
                'titulo' => '🛡️ DISPOSITIVOS DE SEGURIDAD',
                'preguntas' => [
                    ['seguridad_1', 'Pito funcionando correctamente'],
                    ['seguridad_2', 'Pito de reversa funcionando'],
                    ['seguridad_3', 'Freno de servicio funcionando correctamente'],
                    ['seguridad_4', 'Freno de mano/emergencia funcionando'],
                    ['seguridad_5', 'Cinturón de seguridad en buen estado'],
                    ['seguridad_6', 'Estado general de puertas'],
                    ['seguridad_7', 'Limpia brisas y plumillas funcionando'],
                    ['seguridad_8', 'Extintor vigente (Indicar fecha de vencimiento en observaciones)'],
                    ['seguridad_9', 'Botiquín completo'],
                    ['seguridad_10', 'Herramientas completas']
                ]
            ],
            'indicadores' => [
                'titulo' => '📊 INDICADORES',
                'preguntas' => [
                    ['indicador_1', 'Nivel de aceite adecuado'],
                    ['indicador_2', 'Nivel de agua/refrigerante adecuado'],
                    ['indicador_3', 'Nivel de frenos adecuado'],
                    ['indicador_4', 'Sin fugas: No hay goteos visibles']
                ]
            ],
            'llantas' => [
                'titulo' => '⚙️ LLANTAS',
                'preguntas' => [
                    ['llanta_1', 'Estado general de llantas (labrado, presión, pernos)'],
                    ['llanta_2', 'Llanta de repuesto en buen estado y con presión adecuada']
                ]
            ]
        ];
    }

    // ==================== PREGUNTAS VEHÍCULO PROPIO (MOTO) ====================
    
    /**
     * Preguntas para personal con vehículo propio (moto)
     * Exclusivo de usuarios que tengan vehículo propio registrado
     * Se responden con SÍ o NO
     *
     * @return array Array de preguntas con [nombre, texto]
     */
    public static function getPreguntasVehiculoPropio()
    {
        return [
            ['moto_personal_1', '¿Presenta signos de somnolencia o fatiga acumulada?'],
            ['moto_personal_2', '¿Reporta fatiga visual o irritación ocular o visión borrosa o dificultad para mantener la apertura ocular?'],
            ['moto_personal_3', '¿Presenta disminución en su nivel de alerta o concentración?'],
            ['moto_personal_4', '¿Se declara apto física y mentalmente para la tarea?'],
            ['moto_personal_5', '¿Se encuentra libre de influencia de sustancias psicoactivas y/o alcohol y/o medicamentos?'],
            ['moto_personal_6', '¿Presenta actualmente algún dolor, inflamación o molestia en la zona lumbar?'],
            ['moto_personal_7', '¿Siente debilidad, hormigueo o dolor en hombros, brazos o muñecas que dificulte el agarre de objetos?'],
            ['moto_personal_8', '¿Ha tenido alguna lesión o molestia en rodillas o tobillos en las últimas 24 horas?'],
            ['moto_personal_9', '¿Presenta fatiga inusual, dificultad para respirar o mareos al realizar esfuerzos físicos leves?']
        ];
    }

    // ==================== PREOPERACIONAL MOTOS ====================
    
    /**
     * Preguntas de preoperacional para motos
     * Chequeo simple de cada pregunta en cada sección
     *
     * @return array Array de secciones con sus preguntas
     */
    public static function getPreguntasVehiculoMoto()
    {
        return [
            'llantas_rines' => [
                'titulo' => '🛞 LLANTAS Y RINES',
                'preguntas' => [
                    ['moto_llanta_1', 'Llantas y rines se encuentran en buen estado']
                ]
            ],
            'transmision' => [
                'titulo' => '⚙️ TRANSMISIÓN',
                'preguntas' => [
                    ['moto_trans_1', 'La cadena se encuentra bien lubricada, con la tensión adecuada, y sin ruidos extraños'],
                    ['moto_trans_2', 'Guardacadena presente']
                ]
            ],
            'luces_espejos' => [
                'titulo' => '💡 LUCES Y ESPEJOS',
                'preguntas' => [
                    ['moto_luz_1', 'Estado de las luces (delanteras, traseras, freno, direccionales)'],
                    ['moto_luz_2', 'Estado de los espejos retrovisores'],
                    ['moto_luz_3', 'Estado del manubrio']
                ]
            ],
            'fugas' => [
                'titulo' => '💧 FUGAS',
                'preguntas' => [
                    ['moto_fuga_1', 'Moto libre de goteos, manchas de aceite y relativos']
                ]
            ],
            'mandos' => [
                'titulo' => '🎮 MANDOS',
                'preguntas' => [
                    ['moto_mando_1', 'El embrague está en buenas condiciones'],
                    ['moto_mando_2', 'Acelerador está en buenas condiciones']
                ]
            ],
            'entorno_general' => [
                'titulo' => '🔧 ENTORNO GENERAL',
                'preguntas' => [
                    ['moto_entorno_1', 'La moto está en buenas condiciones de limpieza'],
                    ['moto_entorno_2', 'El chasis se encuentra deteriorado'],
                    ['moto_entorno_3', 'Herramientas y elementos requeridos por ley']
                ]
            ],
            'elementos_proteccion' => [
                'titulo' => '🦺 ELEMENTOS DE PROTECCIÓN',
                'preguntas' => [
                    ['moto_epp_1', 'Dispone de casco, guantes, gafas, chaleco reflectivo en buen estado']
                ]
            ]
        ];
    }

    // ==================== PREGUNTAS AUXILIAR DE CARGA ====================
    
    /**
     * Preguntas para ingreso de personal cargo auxiliar de carga
     * Misma lógica anterior de los vehículos, preguntas de sí y no
     *
     * @return array Array de preguntas con [nombre, texto]
     */
    public static function getPreguntasAuxiliarCarga()
    {
        return [
            ['auxiliar_1', '¿Presenta actualmente algún dolor, inflamación o molestia en la zona lumbar (espalda baja)?'],
            ['auxiliar_2', '¿Siente debilidad, hormigueo o dolor en hombros, brazos o muñecas que dificulte el agarre de objetos?'],
            ['auxiliar_3', '¿Ha tenido alguna lesión o molestia en rodillas o tobillos en las últimas 24 horas?'],
            ['auxiliar_4', '¿Presenta fatiga inusual, dificultad para respirar o mareos al realizar esfuerzos físicos leves?'],
            ['auxiliar_5', '¿Cuenta con alguna restricción médica o tratamiento vigente emitida por su EPS que le impida cargar peso o realizar movimientos repetitivos?'],
            ['auxiliar_6', '¿Confirma que se encuentra libre de efectos de alcohol o sustancias psicoactivas que comprometan su equilibrio y fuerza?']
        ];
    }

    // ==================== RENDERIZADO DE SECCIONES ====================
    
    /**
     * Genera el HTML para una sección de preguntas con radio buttons
     *
     * @param string $titulo Título de la sección
     * @param array $preguntas Array de preguntas
     * @param string $color Color de fondo
     * @param array $opciones Opciones de radio buttons (por defecto SÍ/NO/N/A)
     * @param bool $requerido Si las preguntas son requeridas
     * @return string HTML generado
     */
    public static function renderSeccionPreguntas($titulo, $preguntas, $color = '#EFEFEF',
                                                    $opciones = null, $requerido = true)
    {
        if ($opciones === null) {
            $opciones = [
                ['value' => '1', 'label' => 'SÍ'],
                ['value' => '2', 'label' => 'NO'],
                ['value' => '3', 'label' => 'N/A']
            ];
        }

        $html = '';

        // Header de la sección
        $colspan = count($opciones) + 1;
        $html .= "<tr class=\"section-header\">\n";
        $html .= "    <td colspan=\"{$colspan}\">{$titulo}</td>\n";
        $html .= "</tr>\n";

        // Preguntas
        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $requirePhoto = isset($preg['require_photo']) && $preg['require_photo'];

            $html .= "<tr class='question-row' id='{$name}_row'>\n";
            $html .= "    <td class='question-text'>{$texto}</td>\n";

            foreach ($opciones as $opcion) {
                $html .= "    <td class='option-cell'>\n";
                $html .= "        <label class='radio-label'>\n";
                $requiredAttr = $requerido ? 'required' : '';
                $photoAttr = $requirePhoto ? 'data-photo-required="true"' : '';
                $html .= "            <input type='radio' name='{$name}' class='obtener' value='{$opcion['value']}' {$requiredAttr} {$photoAttr}>\n";
                $html .= "            <span class='radio-text'>{$opcion['label']}</span>\n";
                $html .= "        </label>\n";
                $html .= "    </td>\n";
            }

            // Campo de foto si es requerido (para inspección inicial)
            if ($requirePhoto) {
                $html .= "<tr class='photo-row' id='{$name}_photo_row' style='display:none;'>\n";
                $html .= "    <td colspan='{$colspan}' class='photo-cell'>\n";
                $html .= "        <div class='photo-upload-container'>\n";
                $html .= "            <label class='photo-label'>\n";
                $html .= "                <i class='fas fa-camera'></i> Subir fotografía del problema\n";
                $html .= "            </label>\n";
                $html .= "            <input type='file' name='{$name}_foto' id='{$name}_foto' class='photo-input' accept='image/*' data-trigger='{$name}'>\n";
                $html .= "            <div class='photo-alert'>\n";
                $html .= "                <i class='fas fa-exclamation-triangle'></i> <strong>Atención:</strong> Debe subir una fotografía del problema encontrado y contactar al personal administrativo.\n";
                $html .= "            </div>\n";
                $html .= "        </div>\n";
                $html .= "    </td>\n";
                $html .= "</tr>\n";
            }

            $html .= "</tr>\n";
        }

        return $html;
    }

    /**
     * Genera el HTML para una sección de preguntas de vehículo con checkboxes binarios (SÍ/NO)
     * Formato: Dos checkboxes exclusivos (SÍ y NO) por cada pregunta
     * Las observaciones van al final de la sección
     *
     * @param string $titulo Título de la sección
     * @param array $preguntas Array de preguntas
     * @param string $color Color de fondo
     * @param string $sectionType Tipo de sección para clases CSS
     * @param string $subsectionKey Clave de subsección
     * @return string HTML generado
     */
    public static function renderSeccionVehiculoCheckboxes($titulo, $preguntas, $color = '#EFEFEF', $sectionType = '', $subsectionKey = '')
    {
        $html = '';

        // Header de la sección - combinar clase general de sección con subclase específica
        $sectionClass = !empty($sectionType) ? " {$sectionType}" : '';
        $subsectionClass = !empty($subsectionKey) ? " subsection-{$subsectionKey}" : '';
        $html .= "<tr class=\"section-header{$sectionClass}{$subsectionClass}\">\n";
        $html .= "    <td colspan=\"3\">{$titulo}</td>\n";
        $html .= "</tr>\n";

        // Preguntas con checkboxes binarios (SÍ y NO)
        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $requirePhoto = isset($preg['require_photo']) && $preg['require_photo'];

            // Fila principal con pregunta y checkboxes binarios
            $rowClass = !empty($sectionType) ? " {$sectionType}" : '';
            $rowSubclass = !empty($subsectionKey) ? " subsection-{$subsectionKey}" : '';
            $html .= "<tr class='question-row{$rowClass}{$rowSubclass}' id='{$name}_row'>\n";
            $html .= "    <td class='question-text'>{$texto}</td>\n";

            // Checkbox SÍ (valor 1)
            $html .= "    <td class='option-cell'>\n";
            $html .= "        <label class='checkbox-label checkbox-si'>\n";
            $html .= "            <input type='checkbox' name='{$name}' class='obtener checkbox-binary checkbox-si-input' value='1' data-name='{$name}' data-binary-group='{$name}'>\n";
            $html .= "            <span class='checkbox-text'>SÍ</span>\n";
            $html .= "        </label>\n";
            $html .= "    </td>\n";

            // Checkbox NO (valor 2)
            $html .= "    <td class='option-cell'>\n";
            $html .= "        <label class='checkbox-label checkbox-no'>\n";
            $html .= "            <input type='checkbox' name='{$name}' class='obtener checkbox-binary checkbox-no-input' value='2' data-name='{$name}' data-binary-group='{$name}'>\n";
            $html .= "            <span class='checkbox-text'>NO</span>\n";
            $html .= "        </label>\n";
            $html .= "    </td>\n";

            $html .= "</tr>\n";

            // Campo de foto si es requerido (para inspección inicial)
            if ($requirePhoto) {
                $photoRowClass = !empty($sectionType) ? " {$sectionType}" : '';
                $photoRowSubclass = !empty($subsectionKey) ? " subsection-{$subsectionKey}" : '';

                // Mensaje personalizado para inspección inicial
                $alertMessage = "Debe subir una fotografía del problema encontrado y contactar al personal administrativo.";
                $alertClass = "photo-alert";
                if ($subsectionKey === 'inspeccion_inicial') {
                    $alertMessage = "⚠️ <strong>INSPECCIÓN INICIAL REQUERIDA:</strong> El vehículo no se encuentra en óptimas condiciones. Debe subir una fotografía del problema y contactar inmediatamente al personal administrativo antes de continuar.";
                    $alertClass = "photo-alert photo-alert-inspeccion-inicial";
                }

                $html .= "<tr class='photo-row{$photoRowClass}{$photoRowSubclass}' id='{$name}_photo_row' style='display:none;'>\n";
                $html .= "    <td colspan='3' class='photo-cell'>\n";
                $html .= "        <div class='photo-upload-container'>\n";
                $html .= "            <label class='photo-label'>\n";
                $html .= "                <i class='fas fa-camera'></i> Subir fotografía del problema\n";
                $html .= "            </label>\n";
                $html .= "            <input type='file' name='{$name}_foto' id='{$name}_foto' class='photo-input' accept='image/*' data-trigger='{$name}' data-required-photo='true'>\n";
                $html .= "            <div class='{$alertClass}'>\n";
                $html .= "                <i class='fas fa-exclamation-triangle'></i> {$alertMessage}\n";
                $html .= "            </div>\n";
                $html .= "        </div>\n";
                $html .= "    </td>\n";
                $html .= "</tr>\n";
            }
        }

        return $html;
    }

    /**
     * Renderiza preguntas simples con dos checkboxes binarios (SÍ y NO) para preguntas personales
     * Formato: Dos checkboxes exclusivos (SÍ y NO) por cada pregunta
     *
     * @param array $preguntas Array de preguntas
     * @param string $color Color de fondo
     * @param string $seccionId ID de la sección (opcional, para encabezado)
     * @param string $sectionType Tipo de sección para clases CSS
     * @return string HTML generado
     */
    public static function renderPreguntasPersonales($preguntas, $color = '#EFEFEF', $seccionId = '', $sectionType = '')
    {
        $html = '';

        // Encabezado de sección si se proporciona
        if (!empty($seccionId)) {
            $sectionClass = !empty($sectionType) ? " {$sectionType}" : '';
            $html .= "<tr class=\"section-header personal-section{$sectionClass}\">\n";
            $html .= "    <td colspan='3'>{$seccionId}</td>\n";
            $html .= "</tr>\n";
        }

        // Preguntas con dos checkboxes binarios (SÍ y NO)
        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $rowClass = !empty($sectionType) ? " {$sectionType}" : '';

            $html .= "<tr class='question-row personal-question{$rowClass}' id='{$name}_row'>\n";
            $html .= "    <td class='question-text'>{$texto}</td>\n";

            // Checkbox SÍ (valor 1)
            $html .= "    <td class='option-cell'>\n";
            $html .= "        <label class='checkbox-label checkbox-si'>\n";
            $html .= "            <input type='checkbox' name='{$name}' class='obtener checkbox-binary checkbox-si-input' value='1' data-name='{$name}' data-binary-group='{$name}'>\n";
            $html .= "            <span class='checkbox-text'>SÍ</span>\n";
            $html .= "        </label>\n";
            $html .= "    </td>\n";

            // Checkbox NO (valor 2)
            $html .= "    <td class='option-cell'>\n";
            $html .= "        <label class='checkbox-label checkbox-no'>\n";
            $html .= "            <input type='checkbox' name='{$name}' class='obtener checkbox-binary checkbox-no-input' value='2' data-name='{$name}' data-binary-group='{$name}'>\n";
            $html .= "            <span class='checkbox-text'>NO</span>\n";
            $html .= "        </label>\n";
            $html .= "    </td>\n";

            $html .= "</tr>\n";
        }

        return $html;
    }

    /**
     * Renderiza las secciones de preguntas para vehículo carro usando checkboxes
     *
     * @param string $color Color de fondo
     * @return string HTML generado
     */
    public static function renderVehiculoCarroSections($color = '#EFEFEF', $sectionType = 'preoperacional-carro')
    {
        $preguntas = self::getPreguntasVehiculoCarro();
        $html = '';

        foreach ($preguntas as $key => $seccion) {
            $html .= self::renderSeccionVehiculoCheckboxes(
                $seccion['titulo'],
                $seccion['preguntas'],
                $color,
                $sectionType,
                $key  // Pasar el key de la subsección
            );
        }

        // Mensaje de advertencia
        $html .= '<tr class="warning-message">';
        $html .= '<td colspan="3">⚠️ Si marca "NO" en cualquier pregunta, debe reportar inmediatamente al Jefe de Operaciones de TRANSMILLAS. Para la inspección inicial, debe subir fotografía y contactar a administrativos.</td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Renderiza las secciones de preguntas para vehículo moto usando checkboxes
     *
     * @param string $color Color de fondo
     * @return string HTML generado
     */
    public static function renderVehiculoMotoSections($color = '#EFEFEF', $sectionType = 'preoperacional-moto')
    {
        $preguntas = self::getPreguntasVehiculoMoto();
        $html = '';

        foreach ($preguntas as $key => $seccion) {
            $html .= self::renderSeccionVehiculoCheckboxes(
                $seccion['titulo'],
                $seccion['preguntas'],
                $color,
                $sectionType,
                $key  // Pasar el key de la subsección
            );
        }

        // Mensaje de advertencia
        $html .= '<tr class="warning-message">';
        $html .= '<td colspan="3">⚠️ Si marca "NO" en cualquier pregunta, debe reportar inmediatamente al Jefe de Operaciones de TRANSMILLAS.</td>';
        $html .= '</tr>';

        return $html;
    }

    // ==================== FIRMA A TRAZO ====================

    /**
     * Genera el HTML para la sección de firma a trazo (solo formularios nuevos)
     * Incluye canvas para dibujo con mouse/dedo y campo oculto para guardar la firma
     *
     * @param string $nombreCampo Nombre del campo hidden que almacenará la firma en base64
     * @param bool $requerido Si la firma es obligatoria
     * @return string HTML generado
     */
    public static function renderSeccionFirma($nombreCampo = 'firma_preoperacional', $requerido = true)
    {
        $requiredAttr = $requerido ? 'required' : '';
        $html = '';

        // Sección de firma con canvas
        $html .= "<tr bgcolor=\"#074F91\" class=\"tittle3\">\n";
        $html .= "    <td colspan=\"4\">✍️ FIRMA DEL RESPONSABLE</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr class='signature-row'>\n";
        $html .= "    <td colspan='4'>\n";
        $html .= "        <div class='signature-container'>\n";

        // Canvas para la firma
        $html .= "            <canvas id='signatureCanvas' width='400' height='200' class='signature-canvas'></canvas>\n";

        // Botones de control
        $html .= "            <div class='signature-controls'>\n";
        $html .= "                <button type='button' class='btn btn-sm btn-outline-danger' id='btnClearSignature'>\n";
        $html .= "                    <i class='fas fa-eraser'></i> Limpiar Firma\n";
        $html .= "                </button>\n";
        $html .= "            </div>\n";

        // Campo oculto para almacenar la firma en base64
        $html .= "            <input type='hidden' name='{$nombreCampo}' id='{$nombreCampo}' value='' {$requiredAttr} data-signature-field='true'>\n";

        // Mensaje informativo
        $html .= "            <small class='text-muted d-block mt-2'>\n";
        $html .= "                <i class='fas fa-info-circle'></i> Firme con el mouse o el dedo (en dispositivos táctiles)\n";
        $html .= "            </small>\n";

        $html .= "        </div>\n";
        $html .= "    </td>\n";
        $html .= "</tr>\n";

        return $html;
    }

    // ==================== DETECCIÓN DE FORMATO ====================

    /**
     * Detecta el formato de encuesta basado en los datos almacenados
     *
     * @param string $dataJson JSON de datos de la encuesta
     * @return string 'nuevo' o 'legado'
     */
    public static function detectarFormato($dataJson)
    {
        $data = json_decode($dataJson, true);
        
        if (!$data) {
            return 'legado'; // Por defecto, asumir legado si no hay datos
        }

        // Verificar si contiene claves del nuevo formato
        $clavesNuevas = ['admin_', 'conductor_', 'inspec_', 'luces_', 'cabina_', 'seguridad_', 'indicador_', 'moto_personal_', 'moto_llanta_', 'moto_trans_', 'auxiliar_'];
        foreach ($clavesNuevas as $clave) {
            foreach (array_keys($data) as $key) {
                if (strpos($key, $clave) !== false) {
                    return 'nuevo';
                }
            }
        }

        // Verificar si contiene claves del formato legado
        $clavesLegado = ['llantas1', 'transmision1', 'Luces1', 'direccionales1', 'cabina1'];
        foreach ($clavesLegado as $clave) {
            if (isset($data[$clave])) {
                return 'legado';
            }
        }

        return 'legado'; // Por defecto, asumir legado
    }

    // ==================== VALIDACIÓN DE ROLES ====================
    
    /**
     * Determina si el usuario debe ver preguntas de administrativo
     * Roles: Call center, líder de sede, jefe de operación
     *
     * @param int $rol ID del rol del usuario
     * @return bool
     */
    public static function esPersonalAdministrativo($rol)
    {
        // Ajustar estos IDs según tu base de datos real
        // Ejemplo: 1=Admin, 2=Call Center, 3=Líder de sede, 4=Jefe de operación
        $rolesAdministrativos = [1, 2, 3, 4]; // Ajustar según corresponda
        return in_array($rol, $rolesAdministrativos);
    }

    /**
     * Determina si el usuario es conductor de carro
     *
     * @param int $rol ID del rol del usuario
     * @param string $tipoVehiculo Tipo de vehículo
     * @return bool
     */
    public static function esConductor($rol, $tipoVehiculo)
    {
        // Rol de conductor y vehículo tipo CARRO
        return ($tipoVehiculo === 'CARRO');
    }

    /**
     * Determina si el usuario tiene vehículo propio (moto)
     *
     * @param string $tipoVehiculo Tipo de vehículo
     * @return bool
     */
    public static function tieneVehiculoPropio($tipoVehiculo)
    {
        return ($tipoVehiculo === 'MOTO');
    }

    /**
     * Determina si el usuario es auxiliar de carga
     *
     * @param int $rol ID del rol del usuario
     * @return bool
     */
    public static function esAuxiliarCarga($rol)
    {
        // Ajustar según tu base de datos
        $rolAuxiliarCarga = 5; // Ejemplo
        return ($rol == $rolAuxiliarCarga);
    }
}
