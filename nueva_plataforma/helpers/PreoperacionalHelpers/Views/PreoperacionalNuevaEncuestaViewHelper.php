<?php
/**
 * PreoperacionalNuevaEncuestaViewHelper - Helper para la nueva encuesta de preoperacional
 *
 * DISEÑO DE TARJETAS: Cada sección y subsección es una tarjeta independiente
 * con colores pastel semitransparentes.
 */

class PreoperacionalNuevaEncuestaViewHelper
{
    // ==================== PREGUNTAS ADMINISTRATIVO ====================

    public static function getPreguntasAdministrativo()
    {
        // DB-driven: siempre consultar primero, fallback a hardcoded
        $rol = $_SESSION['usuario_rol'] ?? 0;
        $tv = $_SESSION['usuario_tipovehiculo'] ?? '';
        require_once __DIR__ . '/../PreoperacionalDBHelper.php';
        $dbPreguntas = PreoperacionalDBHelper::cargarPreguntasUsuario($rol, $tv);
        if (!empty($dbPreguntas)) {
            return $dbPreguntas;
        }
        // Fallback: array hardcodeado
        return [
            ['admin_1', '¿Se siente físicamente en capacidad de desarrollar sus labores hoy?'],
            ['admin_2', '¿Presenta alguna molestia osteomuscular (dolor en espalda, cuello, hombros o muñecas) derivada de su jornada anterior?'],
            ['admin_3', '¿Ha experimentado fatiga visual, mareos o dolores de cabeza intensos en las últimas 24 horas?'],
            ['admin_4', '¿Se compromete a realizar sus pausas activas (mínimo 5 minutos por cada 2 horas de trabajo continuo)?'],
            ['admin_5', '¿Confirma que se encuentra libre de efectos de alcohol o sustancias psicoactivas?']
        ];
    }

    // ==================== PREGUNTAS CONDUCTOR (CARRO) ====================

    public static function getPreguntasConductor()
    {
        // DB-driven: siempre consultar primero, fallback a hardcoded
        $rol = $_SESSION['usuario_rol'] ?? 0;
        $tv = $_SESSION['usuario_tipovehiculo'] ?? 'CARRO';
        require_once __DIR__ . '/../PreoperacionalDBHelper.php';
        $dbPreguntas = PreoperacionalDBHelper::cargarPreguntasUsuario($rol, $tv);
        if (!empty($dbPreguntas)) {
            return $dbPreguntas;
        }
        // Fallback: array hardcodeado
        return [
            ['conductor_1', '¿Presenta signos de somnolencia o fatiga acumulada?', 2],
            ['conductor_2', '¿Reporta fatiga visual o irritación ocular o visión borrosa o dificultad para mantener la apertura ocular?', 2],
            ['conductor_3', '¿Presenta disminución en su nivel de alerta o concentración?', 2],
            ['conductor_4', '¿Se declara apto física y mentalmente para la tarea?', 1],
            ['conductor_5', '¿Se encuentra libre de influencia de sustancias psicoactivas y/o alcohol y/o medicamentos?', 1]
        ];
    }

    // ==================== PREOPERACIONAL VEHÍCULO (CARRO) ====================

    public static function getPreguntasVehiculoCarro()
    {
        // DB-driven: siempre consultar primero, fallback a hardcoded
        require_once __DIR__ . '/../PreoperacionalDBHelper.php';
        $dbPreguntas = PreoperacionalDBHelper::cargarPreguntasVehiculo('CARRO');
        if (!empty($dbPreguntas)) {
            return $dbPreguntas;
        }
        // Fallback: array hardcodeado
        return [
            'inspeccion_inicial' => [
                'titulo' => '🔍 INSPECCIÓN INICIAL',
                'subsection_css' => 'subsection-inspeccion',
                'preguntas' => [
                    ['inspec_1', 'El vehículo retirado del parqueadero se encuentra en óptimas condiciones', 'require_photo' => true]
                ]
            ],
            'luces' => [
                'titulo' => '💡 LUCES',
                'subsection_css' => 'subsection-luces',
                'preguntas' => [
                    ['luces_1', 'Estado de luces (altas, bajas, estacionarias, direccionales) en buen estado']
                ]
            ],
            'cabina' => [
                'titulo' => '🚗 CABINA',
                'subsection_css' => 'subsection-cabina',
                'preguntas' => [
                    ['cabina_1', 'Espejo central y laterales en buen estado'],
                    ['cabina_2', 'Cojinería en buen estado']
                ]
            ],
            'dispositivos_seguridad' => [
                'titulo' => '🛡️ DISPOSITIVOS DE SEGURIDAD',
                'subsection_css' => 'subsection-seguridad',
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
                'subsection_css' => 'subsection-indicadores',
                'preguntas' => [
                    ['indicador_1', 'Nivel de aceite adecuado'],
                    ['indicador_2', 'Nivel de agua/refrigerante adecuado'],
                    ['indicador_3', 'Nivel de frenos adecuado'],
                    ['indicador_4', 'Sin fugas: No hay goteos visibles']
                ]
            ],
            'llantas' => [
                'titulo' => '⚙️ LLANTAS',
                'subsection_css' => 'subsection-llantas',
                'preguntas' => [
                    ['llanta_1', 'Estado general de llantas (labrado, presión, pernos)'],
                    ['llanta_2', 'Llanta de repuesto en buen estado y con presión adecuada']
                ]
            ]
        ];
    }

    // ==================== PREGUNTAS VEHÍCULO PROPIO (MOTO) ====================

    public static function getPreguntasVehiculoPropio()
    {
        // DB-driven: siempre consultar primero, fallback a hardcoded
        $rol = $_SESSION['usuario_rol'] ?? 0;
        $tv = $_SESSION['usuario_tipovehiculo'] ?? 'MOTO';
        require_once __DIR__ . '/../PreoperacionalDBHelper.php';
        $dbPreguntas = PreoperacionalDBHelper::cargarPreguntasUsuario($rol, $tv);
        if (!empty($dbPreguntas)) {
            return $dbPreguntas;
        }
        // Fallback: array hardcodeado
        return [
            ['moto_personal_1', '¿Presenta signos de somnolencia o fatiga acumulada?', 2],
            ['moto_personal_2', '¿Reporta fatiga visual o irritación ocular o visión borrosa o dificultad para mantener la apertura ocular?', 2],
            ['moto_personal_3', '¿Presenta disminución en su nivel de alerta o concentración?', 2],
            ['moto_personal_4', '¿Se declara apto física y mentalmente para la tarea?', 1],
            ['moto_personal_5', '¿Se encuentra libre de influencia de sustancias psicoactivas y/o alcohol y/o medicamentos?', 1],
            ['moto_personal_6', '¿Presenta actualmente algún dolor, inflamación o molestia en la zona lumbar?', 2],
            ['moto_personal_7', '¿Siente debilidad, hormigueo o dolor en hombros, brazos o muñecas que dificulte el agarre de objetos?', 2],
            ['moto_personal_8', '¿Ha tenido alguna lesión o molestia en rodillas o tobillos en las últimas 24 horas?', 2],
            ['moto_personal_9', '¿Presenta fatiga inusual, dificultad para respirar o mareos al realizar esfuerzos físicos leves?', 2]
        ];
    }

    // ==================== PREOPERACIONAL MOTOS ====================

    public static function getPreguntasVehiculoMoto()
    {
        // DB-driven: siempre consultar primero, fallback a hardcoded
        require_once __DIR__ . '/../PreoperacionalDBHelper.php';
        $dbPreguntas = PreoperacionalDBHelper::cargarPreguntasVehiculo('MOTO');
        if (!empty($dbPreguntas)) {
            return $dbPreguntas;
        }
        // Fallback: array hardcodeado
        return [
            'llantas_rines' => [
                'titulo' => '🛞 LLANTAS Y RINES',
                'subsection_css' => 'subsection-llantas',
                'preguntas' => [
                    ['moto_llanta_1', 'Llantas y rines se encuentran en buen estado']
                ]
            ],
            'transmision' => [
                'titulo' => '⚙️ TRANSMISIÓN',
                'subsection_css' => 'subsection-transmision',
                'preguntas' => [
                    ['moto_trans_1', 'La cadena se encuentra bien lubricada, con la tensión adecuada, y sin ruidos extraños'],
                    ['moto_trans_2', 'Guardacadena presente']
                ]
            ],
            'luces_espejos' => [
                'titulo' => '💡 LUCES Y ESPEJOS',
                'subsection_css' => 'subsection-luces',
                'preguntas' => [
                    ['moto_luz_1', 'Estado de las luces (delanteras, traseras, freno, direccionales)'],
                    ['moto_luz_2', 'Estado de los espejos retrovisores'],
                    ['moto_luz_3', 'Estado del manubrio']
                ]
            ],
            'fugas' => [
                'titulo' => '💧 FUGAS',
                'subsection_css' => 'subsection-fugas',
                'preguntas' => [
                    ['moto_fuga_1', 'Moto libre de goteos, manchas de aceite y relativos']
                ]
            ],
            'mandos' => [
                'titulo' => '🎮 MANDOS',
                'subsection_css' => 'subsection-mandos',
                'preguntas' => [
                    ['moto_mando_1', 'El embrague está en buenas condiciones'],
                    ['moto_mando_2', 'Acelerador está en buenas condiciones']
                ]
            ],
            'entorno_general' => [
                'titulo' => '🔧 ENTORNO GENERAL',
                'subsection_css' => 'subsection-entorno',
                'preguntas' => [
                    ['moto_entorno_1', 'La moto está en buenas condiciones de limpieza'],
                    ['moto_entorno_2', 'El chasis se encuentra deteriorado'],
                    ['moto_entorno_3', 'Herramientas y elementos requeridos por ley']
                ]
            ],
            'elementos_proteccion' => [
                'titulo' => '🦺 ELEMENTOS DE PROTECCIÓN',
                'subsection_css' => 'subsection-proteccion',
                'preguntas' => [
                    ['moto_epp_1', 'Dispone de casco, guantes, gafas, chaleco reflectivo en buen estado']
                ]
            ]
        ];
    }

    // ==================== PREGUNTAS AUXILIAR DE CARGA ====================

    public static function getPreguntasAuxiliarCarga()
    {
        // DB-driven: siempre consultar primero, fallback a hardcoded
        $rol = $_SESSION['usuario_rol'] ?? 0;
        $tv = $_SESSION['usuario_tipovehiculo'] ?? '';
        require_once __DIR__ . '/../PreoperacionalDBHelper.php';
        $dbPreguntas = PreoperacionalDBHelper::cargarPreguntasUsuario($rol, $tv);
        if (!empty($dbPreguntas)) {
            return $dbPreguntas;
        }
        // Fallback: array hardcodeado
        return [
            ['auxiliar_1', '¿Presenta actualmente algún dolor, inflamación o molestia en la zona lumbar (espalda baja)?'],
            ['auxiliar_2', '¿Siente debilidad, hormigueo o dolor en hombros, brazos o muñecas que dificulte el agarre de objetos?'],
            ['auxiliar_3', '¿Ha tenido alguna lesión o molestia en rodillas o tobillos en las últimas 24 horas?'],
            ['auxiliar_4', '¿Presenta fatiga inusual, dificultad para respirar o mareos al realizar esfuerzos físicos leves?'],
            ['auxiliar_5', '¿Cuenta con alguna restricción médica o tratamiento vigente emitida por su EPS que le impida cargar peso o realizar movimientos repetitivos?'],
            ['auxiliar_6', '¿Confirma que se encuentra libre de efectos de alcohol o sustancias psicoactivas que comprometan su equilibrio y fuerza?']
        ];
    }

    // ==================== RENDERIZADO DE TARJETAS ====================

    // Mensaje de bloqueo para preguntas personales del conductor
    const MSG_BLOQUEO_CONDUCTOR = 'Por favor, comuníquese con el jefe de operaciones / oficina, actualmente no se puede realizar el preoperacional si presenta complicaciones.';

    /**
     * Renderiza una pregunta individual con checkboxes SÍ/NO
     */
    private static function renderQuestionItem($name, $texto, $valoresExistentes = null, $requirePhoto = false, $expectedAnswer = null, $esValidacion = false)
    {
        $checkedSi = ($valoresExistentes !== null && isset($valoresExistentes[$name]) && $valoresExistentes[$name] == '1') ? 'checked' : '';
        $checkedNo = ($valoresExistentes !== null && isset($valoresExistentes[$name]) && $valoresExistentes[$name] == '2') ? 'checked' : '';
        $disabled = $esValidacion ? 'disabled' : '';

        // Determinar si la respuesta precargada es negativa (contraria a la esperada)
        $respuestaNegativa = false;
        if ($expectedAnswer !== null && $valoresExistentes !== null && isset($valoresExistentes[$name])) {
            $respuestaNegativa = ((int)$valoresExistentes[$name] !== (int)$expectedAnswer);
        }

        $html = '<div class="question-item" id="' . $name . '_row">';
        $html .= '<div class="question-text">' . htmlspecialchars($texto) . '</div>';
        $html .= '<div class="question-options">';
        // SÍ
        $html .= '<label class="checkbox-label checkbox-si">';
        $html .= '<input type="checkbox" name="' . $name . '" class="obtener checkbox-binary checkbox-si-input" value="1" data-name="' . $name . '" data-binary-group="' . $name . '" data-expected="' . ($expectedAnswer ?? '') . '" ' . $checkedSi . ' ' . $disabled . '>';
        $html .= '<span class="checkbox-text">SÍ</span>';
        $html .= '</label>';
        // NO
        $html .= '<label class="checkbox-label checkbox-no">';
        $html .= '<input type="checkbox" name="' . $name . '" class="obtener checkbox-binary checkbox-no-input" value="2" data-name="' . $name . '" data-binary-group="' . $name . '" data-expected="' . ($expectedAnswer ?? '') . '" ' . $checkedNo . ' ' . $disabled . '>';
        $html .= '<span class="checkbox-text">NO</span>';
        $html .= '</label>';
        $html .= '</div>';
        $html .= '</div>';

        // Warning por respuesta negativa en preguntas personales del conductor
        if ($expectedAnswer !== null) {
            $displayStyle = $respuestaNegativa ? '' : 'display:none;';
            $html .= '<div class="driver-warning" id="' . $name . '_warning" style="' . $displayStyle . '">';
            $html .= '<i class="fas fa-exclamation-triangle"></i> ';
            $html .= '<strong>ATENCIÓN:</strong> ' . htmlspecialchars(self::MSG_BLOQUEO_CONDUCTOR);
            $html .= '</div>';
        }

        // Foto si es requerida (solo en modo nuevo, no en validación)
        if ($requirePhoto && !$esValidacion) {
            $html .= '<div class="photo-row" id="' . $name . '_photo_row" style="display:none;">';
            $html .= '<div class="photo-upload-container">';
            $html .= '<label class="photo-label"><i class="fas fa-camera"></i> Subir fotografía del problema</label>';
            $html .= '<input type="file" name="' . $name . '_foto" id="' . $name . '_foto" class="photo-input" accept="image/*" data-trigger="' . $name . '" data-required-photo="true">';
            $html .= '<div class="photo-alert photo-alert-inspeccion-inicial">';
            $html .= '<i class="fas fa-exclamation-triangle"></i> <strong>INSPECCIÓN INICIAL REQUERIDA:</strong> El vehículo no se encuentra en óptimas condiciones. Debe subir una fotografía del problema y contactar inmediatamente al personal administrativo antes de continuar.';
            $html .= '</div></div></div>';
        }

        return $html;
    }

    /**
     * Genera tarjeta para preguntas personales (Administrativo, Conductor, Vehículo Propio, Auxiliar)
     */
    public static function renderPreguntasPersonales($preguntas, $titulo, $cardClass, $valoresExistentes = null, $esValidacion = false)
    {
        // Determinar si esta sección es de conductor (para mostrar banner de bloqueo)
        $esSeccionConductor = ($cardClass === 'conductor' || $cardClass === 'vehiculo-propio');

        $html = '<div class="preop-card ' . $cardClass . '">';
        $html .= '<div class="preop-card-header">' . $titulo . '</div>';
        $html .= '<div class="preop-card-body">';

        // Banner de advertencia global para secciones de conductor
        if ($esSeccionConductor && !$esValidacion) {
            $html .= '<div class="driver-block-banner" id="' . $cardClass . '_block_banner" style="display:none;">';
            $html .= '<i class="fas fa-ban"></i> ';
            $html .= '<strong>PRECAUTELADO:</strong> ' . htmlspecialchars(self::MSG_BLOQUEO_CONDUCTOR);
            $html .= '</div>';
        }

        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $expectedAnswer = $preg[2] ?? null;
            $html .= self::renderQuestionItem($name, $texto, $valoresExistentes, false, $expectedAnswer, $esValidacion);
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Genera tarjeta para una subsección de vehículo (checkboxes SÍ/NO)
     */
    public static function renderSeccionVehiculoCheckboxes($titulo, $preguntas, $subsectionCss, $valoresExistentes = null, $esValidacion = false)
    {
        $html = '<div class="preop-card ' . $subsectionCss . '">';
        $html .= '<div class="preop-card-header">' . $titulo . '</div>';
        $html .= '<div class="preop-card-body">';

        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $requirePhoto = isset($preg['require_photo']) && $preg['require_photo'];
            $html .= self::renderQuestionItem($name, $texto, $valoresExistentes, $requirePhoto, null, $esValidacion);
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Renderiza las secciones de vehículo carro como tarjetas individuales
     */
    public static function renderVehiculoCarroSections($valoresExistentes = null, $esValidacion = false)
    {
        $preguntas = self::getPreguntasVehiculoCarro();
        $html = '';

        foreach ($preguntas as $key => $seccion) {
            $subsectionCss = isset($seccion['subsection_css']) ? $seccion['subsection_css'] : 'subsection-' . $key;
            $html .= self::renderSeccionVehiculoCheckboxes(
                $seccion['titulo'],
                $seccion['preguntas'],
                $subsectionCss,
                $valoresExistentes,
                $esValidacion
            );
        }

        if (!$esValidacion) {
            $html .= '<div class="preop-card warning-card">';
            $html .= '<div class="preop-card-header">⚠️ AVISO IMPORTANTE</div>';
            $html .= '<div class="preop-card-body">';
            $html .= '<p style="margin:0;font-size:14px;">Si marca <strong>NO</strong> en cualquier pregunta, debe reportar inmediatamente al Jefe de Operaciones de TRANSMILLAS. Para la inspección inicial, debe subir fotografía y contactar a administrativos.</p>';
            $html .= '</div></div>';
        }

        return $html;
    }

    /**
     * Renderiza las secciones de vehículo moto como tarjetas individuales
     */
    public static function renderVehiculoMotoSections($valoresExistentes = null, $esValidacion = false)
    {
        $preguntas = self::getPreguntasVehiculoMoto();
        $html = '';

        foreach ($preguntas as $key => $seccion) {
            $subsectionCss = isset($seccion['subsection_css']) ? $seccion['subsection_css'] : 'subsection-' . $key;
            $html .= self::renderSeccionVehiculoCheckboxes(
                $seccion['titulo'],
                $seccion['preguntas'],
                $subsectionCss,
                $valoresExistentes,
                $esValidacion
            );
        }

        if (!$esValidacion) {
            $html .= '<div class="preop-card warning-card">';
            $html .= '<div class="preop-card-header">⚠️ AVISO IMPORTANTE</div>';
            $html .= '<div class="preop-card-body">';
            $html .= '<p style="margin:0;font-size:14px;">Si marca <strong>NO</strong> en cualquier pregunta, debe reportar inmediatamente al Jefe de Operaciones de TRANSMILLAS.</p>';
            $html .= '</div></div>';
        }

        return $html;
    }

    /**
     * Genera tarjeta de información del vehículo
     * @param array $datosVehiculo Datos del vehículo y usuario
     * @param int $maxSeverity Nivel de severidad de documentos (0=normal, 1=warning, 2=critical, 3=expired)
     */
    public static function renderVehicleInfoCard($datosVehiculo, $maxSeverity = 0)
    {
        if (empty($datosVehiculo)) return '';

        $severityClass = '';
        $severityIcon = '';
        if ($maxSeverity >= 3) {
            $severityClass = 'severity-expired';
            $severityIcon = ' <i class="fas fa-exclamation-triangle severity-indicator-icon pulse-warning"></i>';
        } elseif ($maxSeverity >= 2) {
            $severityClass = 'severity-critical';
            $severityIcon = ' <i class="fas fa-exclamation-circle severity-indicator-icon"></i>';
        } elseif ($maxSeverity >= 1) {
            $severityClass = 'severity-warning';
            $severityIcon = ' <i class="fas fa-clock severity-indicator-icon"></i>';
        }

        $campos = [
            'PLACA' => $datosVehiculo['veh_placa'] ?? '',
            'MARCA' => $datosVehiculo['veh_marca'] ?? '',
            'MODELO' => $datosVehiculo['veh_modelo'] ?? '',
            'KM' => $datosVehiculo['veh_kilactual'] ?? '',
            'CONDUCTOR' => $datosVehiculo['usu_nombre'] ?? '',
            'CEDULA' => $datosVehiculo['usu_identificacion'] ?? '',
            'LICENCIA' => $datosVehiculo['usu_licencia'] ?? '',
            'FECHA VENC. LIC.' => $datosVehiculo['usu_fechalicencia'] ?? '',
            'SEGURO VENCE' => $datosVehiculo['veh_fechaseguro'] ?? '',
            'TECNOMECÁNICA VENCE' => $datosVehiculo['veh_fechategnomecanica'] ?? ''
        ];

        $html = '<div class="preop-card vehicle-info ' . $severityClass . '">';
        $html .= '<div class="preop-card-header">';
        $html .= '<i class="fas fa-truck"></i> DATOS DEL VEHÍCULO' . $severityIcon;
        $html .= '</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<div class="vehicle-info-grid">';

        foreach ($campos as $label => $valor) {
            if (!empty($valor)) {
                $html .= '<div class="vehicle-info-item"><strong>' . $label . '</strong><span>' . htmlspecialchars($valor) . '</span></div>';
            }
        }

        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Genera tarjeta de kilometraje
     */
    public static function renderKilometrajeCard($registroExistente, $esValidacion = false)
    {
        $disabled = $esValidacion ? 'disabled' : '';
        $registro = $registroExistente ?? [];
        $tieneImagen = !empty($registro['pre_img_kilo']);
        // Texto: requerido siempre en modo nuevo (no validación)
        $requiredKm = !$esValidacion ? 'required' : '';
        // Foto: requerida solo si no existe imagen previa
        $requiredFoto = (!$esValidacion && !$tieneImagen) ? 'required' : '';

        $html = '<div class="preop-card kilometraje-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-tachometer-alt"></i> KILOMETRAJE ACTUAL</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<div style="margin-bottom:12px;">';
        $html .= '<input name="kilometraje" id="kilometraje" value="' . htmlspecialchars($registro['pre_kilrecorridos'] ?? '') . '" class="form-input" placeholder="Ingrese kilometraje actual" ' . $requiredKm . ' ' . $disabled . '>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<label style="font-weight:600;font-size:14px;color:#555;">Imagen Kilometraje:</label>';
        $html .= '<input type="file" name="imagen_kilometraje" class="photo-input" ' . $requiredFoto . ' ' . $disabled . '>';
        if ($tieneImagen) {
            $url = self::rutaAbsolutaAUrl($registro['pre_img_kilo']);
            $html .= '<br><a href="' . htmlspecialchars($url) . '" target="_blank" style="font-size:13px;">Ver imagen actual</a>';
        }
        // Hidden para indicar al JS si ya existe imagen previa (no exigir re-subida)
        $html .= '<input type="hidden" id="pre_img_kilo_existente" value="' . ($tieneImagen ? '1' : '0') . '">';
        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Genera tarjeta de observaciones
     */
    public static function renderObservacionesCard($registroExistente, $esValidacion = false)
    {
        $disabled = $esValidacion ? 'disabled' : '';
        $html = '<div class="preop-card observations-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-clipboard-list"></i> OBSERVACIONES / CONDICIONES REPORTADAS</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<textarea name="observaciones" id="observaciones" class="form-textarea" placeholder="Describa las observaciones o condiciones encontradas..." ' . $disabled . '>' . htmlspecialchars($registroExistente['pre_obsevaciones'] ?? '') . '</textarea>';
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Genera tarjeta de acción correctiva (solo validación)
     */
    public static function renderAccionCorrectivaCard($registroExistente)
    {
        $html = '<div class="preop-card observations-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-tools"></i> ACCIÓN CORRECTIVA</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<textarea name="accion_correctiva" id="accion_correctiva" class="form-textarea" placeholder="Describa la acción correctiva...">' . htmlspecialchars($registroExistente['pre_correctiva'] ?? '') . '</textarea>';
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Genera tarjeta de responsable (solo validación)
     */
    public static function renderResponsableCard($registroExistente)
    {
        $html = '<div class="preop-card observations-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-user-check"></i> RESPONSABLE</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<input name="responsable" id="responsable" value="' . htmlspecialchars($registroExistente['pre_responsable'] ?? '') . '" class="form-input" placeholder="Nombre del responsable">';
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Genera tarjeta de declaración
     */
    public static function renderDeclaracionCard()
    {
        $html = '<div class="preop-card declaration-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-check-circle"></i> DECLARACIÓN</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<p style="margin:0;font-size:14px;font-weight:500;">Declaro que toda la información suministrada en el test anterior es verídica.</p>';
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Genera tarjeta de compromiso (legado)
     */
    public static function renderCompromisoCard()
    {
        $html = '<div class="preop-card compromiso-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-handshake"></i> COMPROMISO</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<p style="margin:0;font-size:14px;">YO COMO TRABAJADOR DE LA EMPRESA TRANSMILLAS ME COMPROMETO A...</p>';
        $html .= '</div></div>';
        return $html;
    }

    // ==================== FIRMA A TRAZO ====================

    /**
     * Tarjeta de firma con canvas
     */
    public static function renderSeccionFirma($nombreCampo = 'firma_preoperacional')
    {
        $html = '<div class="preop-card signature-card">';
        $html .= '<div class="preop-card-header">✍️ FIRMA DEL RESPONSABLE</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<div class="signature-container">';
        $html .= '<canvas id="signatureCanvas" width="400" height="200" class="signature-canvas"></canvas>';
        $html .= '<div class="signature-controls">';
        $html .= '<button type="button" class="btn btn-sm btn-outline-danger" id="btnClearSignature">';
        $html .= '<i class="fas fa-eraser"></i> Limpiar Firma</button>';
        $html .= '</div>';
        $html .= '<input type="hidden" name="' . $nombreCampo . '" id="' . $nombreCampo . '" value="" data-signature-field="true">';
        $html .= '<small class="text-muted d-block mt-2"><i class="fas fa-info-circle"></i> Firme con el mouse o el dedo (en dispositivos táctiles)</small>';
        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Tarjeta de firma registrada (modo validación)
     */
    public static function renderFirmaRegistradaCard($firmaDataUri)
    {
        $html = '<div class="preop-card signature-card">';
        $html .= '<div class="preop-card-header">✍️ FIRMA DEL RESPONSABLE</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<div class="signature-container">';
        $html .= '<img src="' . $firmaDataUri . '" alt="Firma del responsable" style="max-width:400px; max-height:200px; border:2px solid rgba(7,79,145,0.5); border-radius:8px; background-color:#fff; display:block;">';
        $html .= '<small class="text-muted d-block mt-2"><i class="fas fa-lock"></i> Firma del operario registrada</small>';
        $html .= '</div></div></div>';
        return $html;
    }

    /**
     * Tarjeta de validación (solo modo validación)
     */
    public static function renderValidacionCard($registroExistente, $esNuevoFormato = true)
    {
        $html = '<div class="preop-card validation-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-clipboard-check"></i> VALIDA PREOPERACIONAL</div>';
        $html .= '<div class="preop-card-body">';

        $html .= '<div style="margin-bottom:12px;">';
        $html .= '<label style="font-weight:600;font-size:14px;color:#555;display:block;margin-bottom:4px;">Descripción de la validación:</label>';
        $html .= '<textarea name="desc_validacion" id="desc_validacion" class="form-textarea" placeholder="Describa la validación...">' . htmlspecialchars($registroExistente['pre_descvalidada'] ?? '') . '</textarea>';
        $html .= '</div>';

        if ($esNuevoFormato) {
            $html .= '<div>';
            $html .= '<label style="font-weight:600;font-size:14px;color:#555;display:block;margin-bottom:4px;">Observaciones adicionales:</label>';
            $html .= '<textarea name="observaciones_validacion" id="observaciones_validacion" class="form-textarea" placeholder="Observaciones para la validación del preoperacional"></textarea>';
            $html .= '</div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    // ==================== VALIDACIÓN DE ROLES ====================

    public static function esPersonalAdministrativo($rol)
    {
        $rolesAdministrativos = [1, 2, 3, 4];
        return in_array($rol, $rolesAdministrativos);
    }

    public static function esConductor($tipoVehiculo)
    {
        return $tipoVehiculo === 'CARRO';
    }

    public static function tieneVehiculoPropio($tipoVehiculo)
    {
        return $tipoVehiculo === 'MOTO';
    }

    public static function esAuxiliarCarga($rol)
    {
        $rolAuxiliarCarga = 5;
        return $rol == $rolAuxiliarCarga;
    }

    /**
     * Verifica si el rol del usuario está autorizado para operaciones vehiculares
     * (asignación de vehículos, reporte de novedades, inspección preoperacional).
     *
     * Solo los roles listados aquí pueden ver y usar funciones de vehículos.
     * Para ajustar qué roles tienen acceso, modifique el array $rolesAutorizados.
     *
     * @param int $rol ID del rol del usuario (de $_SESSION['usuario_rol'])
     * @return bool True si el rol está autorizado para operaciones vehiculares
     */
    public static function esRolVehicularAutorizado($rol)
    {
        $rolesAutorizados = [
            3, // Operadores (conductores de carro y moto)
            8, // CONDUCTOR EXTERNO
        ];
        return in_array((int) $rol, $rolesAutorizados, true);
    }

    /**
     * Convierte una ruta absoluta del servidor a una URL accesible desde el navegador
     */
    private static function rutaAbsolutaAUrl($rutaAbsoluta)
    {
        if (empty($rutaAbsoluta)) return '';
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $ruta = str_replace('\\', '/', $rutaAbsoluta);
        if (strpos($ruta, $docRoot) === 0) {
            return substr($ruta, strlen($docRoot));
        }
        return $rutaAbsoluta;
    }
}
