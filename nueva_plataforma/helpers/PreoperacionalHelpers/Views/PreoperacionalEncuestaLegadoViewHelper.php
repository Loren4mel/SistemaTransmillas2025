<?php
/**
 * PreoperacionalEncuestaLegadoViewHelper - Helper para la vista de preoperacional legado
 * DISEÑO DE TARJETAS: Cada sección es una tarjeta independiente con colores pastel.
 */

class PreoperacionalEncuestaLegadoViewHelper
{
    /**
     * Obtiene las preguntas COVID-19
     */
    public static function getPreguntasCovid()
    {
        return [
            ['covid191', 'Ha sentido fatiga los últimos dos días?'],
            ['covid192', 'Ha tenido fiebre mayor a 37,3?'],
            ['covid193', 'Ha presentado tos seca?'],
            ['covid194', 'Ha presentado dificultad para respirar?'],
            ['covid195', 'Tiene dolor o molestia?'],
            ['covid196', 'Tiene abundante secreción nasal?'],
            ['covid197', 'Ha presentado dolor de garganta?'],
            ['covid198', 'Realizo cambio de ropa de trabajo y esta se encuentra limpia?'],
            ['covid199', 'realizo cambio de tapabocas convencional lavable suministrado por la empresa y este se encuentra limpio?']
        ];
    }

    /**
     * Renderiza tarjeta COVID-19
     */
    public static function renderCovidCard($valoresExistentes = null, $registroExistente = null)
    {
        $preguntas = self::getPreguntasCovid();
        $html = '<div class="preop-card subsection-inspeccion">';
        $html .= '<div class="preop-card-header"><i class="fas fa-thermometer-half"></i> TEST DE REPORTE DIARIO DE SINTOMATOLOGIA</div>';
        $html .= '<div class="preop-card-body">';

        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $valorExistente = $valoresExistentes[$name] ?? null;
            $claseExtra = in_array($name, ['covid191', 'covid192', 'covid193', 'covid194', 'covid195', 'covid196', 'covid197']) ? 'optionCovid' . substr($name, -1) : '';

            $html .= '<div class="question-item" id="' . $name . '0">';
            $html .= '<div class="question-text">' . htmlspecialchars($texto) . '</div>';
            $html .= '<div class="question-options">';
            // SI
            $html .= '<label class="radio-label">';
            $html .= '<input type="radio" name="' . $name . '" class="obtener ' . $claseExtra . '" value="1" ' . ($valorExistente == '1' ? 'checked' : '') . ' required>';
            $html .= '<span class="radio-text">SI</span></label>';
            // NO
            $html .= '<label class="radio-label">';
            $html .= '<input type="radio" name="' . $name . '" class="obtener" value="2" ' . ($valorExistente == '2' ? 'checked' : '') . '>';
            $html .= '<span class="radio-text">NO</span></label>';
            $html .= '</div></div>';
        }

        // Temperatura
        $html .= '<div class="question-item" id="temperatura">';
        $html .= '<div class="question-text">Temperatura:</div>';
        $html .= '<div style="flex:1;min-width:200px;">';
        $html .= '<input name="param19" id="param19" value="' . htmlspecialchars($registroExistente['pre_temperatura'] ?? '') . '" class="form-input" placeholder="Ej: 36.5">';
        $html .= '</div></div>';

        // Imagen temperatura
        $html .= '<div class="question-item">';
        $html .= '<div class="question-text">Imagen Temperatura:</div>';
        $html .= '<div style="flex:1;min-width:200px;">';
        $html .= '<input type="file" name="param20" class="photo-input">';
        $html .= '</div></div>';

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Obtiene las preguntas para motos
     */
    public static function getPreguntasMoto()
    {
        return [
            'llantas' => [
                'titulo' => '🛞 LLANTAS Y RINES',
                'subsection_css' => 'subsection-llantas',
                'preguntas' => [
                    ['llantas1', 'Tienen rajaduras por un objeto condundecte?'],
                    ['llantas2', 'Tienen grietas finas en los laterales?'],
                    ['llantas3', 'Las llantas estan desinfladas?'],
                    ['llantas4', 'Las llantas estan sobreinfladas?'],
                    ['llantas5', 'Los rines y guardabarros estan en buen estado?'],
                    ['llantas6', 'Funcionan correctamente la suspensión y frenos de llantas?']
                ]
            ],
            'transmision' => [
                'titulo' => '⚙️ TRANSMISIÓN',
                'subsection_css' => 'subsection-transmision',
                'preguntas' => [
                    ['transmision1', 'La cadena brilla? (Necesita engrase)'],
                    ['transmision2', 'La cadena esta mal tensionada? (se oye al rodar)']
                ]
            ],
            'luces' => [
                'titulo' => '💡 LUCES Y ESPEJOS',
                'subsection_css' => 'subsection-luces',
                'preguntas' => [
                    ['Luces1', 'Funcionan correctamente las Luces de cruce y de frenado?'],
                    ['Luces2', 'Los espejos estan en perfecto estado?'],
                    ['Luces3', 'El manubrio está en óptimas condiciones?']
                ]
            ],
            'fugas' => [
                'titulo' => '💧 FUGAS',
                'subsection_css' => 'subsection-fugas',
                'preguntas' => [
                    ['fugas1', 'Fugas en el liquido de suspensión y de frenos?'],
                    ['fugas2', 'Fugas en el sistema de trasmisión (cardán, diferencial)?'],
                    ['fugas3', 'fugas en el aceite del motor y liquido de refrigeración?'],
                    ['fugas4', 'Fugas en el fluido que pasa por la caja de cambios?'],
                    ['fugas5', 'Fugas en el tanque de combustible?'],
                    ['fugas6', 'Fugas de gases en el mofle (deterioro)?']
                ]
            ],
            'mandos' => [
                'titulo' => '🎮 MANDOS (CAMBIOS, FRENOS)',
                'subsection_css' => 'subsection-mandos',
                'preguntas' => [
                    ['mandos1', 'El embrague esta endurecido?'],
                    ['mandos2', 'El cable de acelerador vuelve del todo a su punto inical?']
                ]
            ],
            'entorno' => [
                'titulo' => '🔧 ENTORNO GENERAL',
                'subsection_css' => 'subsection-entorno',
                'preguntas' => [
                    ['entorno1', 'La moto esta en buenas condiciones de limpieza?'],
                    ['entorno2', 'Esta deteriorado el chasis de la moto? (oxidación, abolladuras, partes faltantes)?'],
                    ['entorno3', 'Caja de Herramientas']
                ]
            ],
            'elementos' => [
                'titulo' => '🦺 ELEMENTOS DE PROTECCIÓN',
                'subsection_css' => 'subsection-proteccion',
                'preguntas' => [
                    ['elementos1', 'Se dispone de casco para moto en buen estado?'],
                    ['elementos2', 'Se dispone de guantes para moto?'],
                    ['elementos3', 'Se dispone de gafas protectoras?'],
                    ['elementos4', 'Se dispone de chaleco reflectivo para las horas de la noche?'],
                    ['elementos5', 'Se dispone de impermeable para temporadas de lluvias?'],
                    ['elementos6', 'SE REALIZÒ LA LIMPIEZA Y DESINFECCION DE LA MOTO?']
                ]
            ]
        ];
    }

    /**
     * Obtiene las preguntas para carros
     */
    public static function getPreguntasCarro()
    {
        return [
            'direccionales' => [
                'titulo' => '💡 DIRECCIONALES',
                'subsection_css' => 'subsection-direccionales',
                'preguntas' => [
                    ['direccionales1', 'Frontales Plenas altas y/o bajas'],
                    ['direccionales2', 'Direccionales delanteras de parqueo'],
                    ['direccionales3', 'Direccionales traseras de parqueo'],
                    ['direccionales4', 'De Stop y señal trasera']
                ]
            ],
            'cabina' => [
                'titulo' => '🚗 CABINA',
                'subsection_css' => 'subsection-cabina',
                'preguntas' => [
                    ['cabina1', 'Espejo central o retrovisor'],
                    ['cabina2', 'Espejos laterales'],
                    ['cabina3', 'Alarma de retroceso'],
                    ['cabina4', 'Cojineria'],
                    ['cabina5', 'Vidrio frontal'],
                    ['cabina6', 'Nivel de agua del parabrisas'],
                    ['cabina7', 'Vidrios Laterales o cortabrisas'],
                    ['cabina8', 'Vidrio trasero']
                ]
            ],
            'dispositivos' => [
                'titulo' => '🛡️ DISPOSITIVOS DE SEGURIDAD',
                'subsection_css' => 'subsection-seguridad',
                'preguntas' => [
                    ['dispositivos1', 'Pito'],
                    ['dispositivos2', 'Pito de reversa'],
                    ['dispositivos3', 'Freno de servicio'],
                    ['dispositivos4', 'Freno de emergencia'],
                    ['dispositivos5', 'Dirección/suspensión delantera'],
                    ['dispositivos6', 'Cinturón de seguridad'],
                    ['dispositivos7', 'Estado general de puertas'],
                    ['dispositivos8', 'Limpia brisas y plumillas'],
                    ['dispositivos9', 'Extintor (indique fecha de vencimiento en observaciones)'],
                    ['dispositivos10', 'Botiquin'],
                    ['dispositivos11', 'Asientos en buena condición']
                ]
            ],
            'indicadores' => [
                'titulo' => '📊 INDICADORES',
                'subsection_css' => 'subsection-indicadores',
                'preguntas' => [
                    ['indicadores1', 'Panel de Indicadores'],
                    ['indicadores2', 'Aceite'],
                    ['indicadores3', 'Agua']
                ]
            ],
            'llantas' => [
                'titulo' => '⚙️ LLANTAS',
                'subsection_css' => 'subsection-llantas',
                'preguntas' => [
                    ['llantas1', 'Estado General de llantas'],
                    ['llantas2', 'Llanta de repuesto']
                ]
            ],
            'herramientas' => [
                'titulo' => '🔧 HERRAMIENTAS MINIMAS',
                'subsection_css' => 'subsection-herramientas',
                'preguntas' => [
                    ['Herramientas1', 'Gato'],
                    ['Herramientas2', 'Cruceta'],
                    ['Herramientas3', 'Cinta de seguridad'],
                    ['Herramientas4', 'Conos'],
                    ['Herramientas5', 'Linterna'],
                    ['Herramientas6', 'Caja de Herramientas'],
                    ['Herramientas7', 'SE REALIZÒ LA LIMPIEZA Y DESINFECCION DEL VEHÌCULO?']
                ]
            ]
        ];
    }

    /**
     * Obtiene las preguntas de fatiga
     */
    public static function getPreguntasFatiga()
    {
        return [
            ['elementosp1', 'TENGO SUEÑO?'],
            ['elementosp2', 'SIENTO LA VISTA CANSADA?'],
            ['elementosp3', 'ME ENCUENTRO TOMANDO MEDICAMENTOS QUE ME IMPIDAN OPERAR O ALTERE MI CONCENTRACIÓN?'],
            ['elementosp4', 'ME CUESTA ENFOCAR LA VISTA (VISIÓN BORROSA) O MANTENER LOS OJOS ABIERTOS?'],
            ['elementosp5', 'SIENTO DIFICULTADES PARA CONCENTRARME O PERMANECER ALERTA?'],
            ['elementosp6', 'ME SIENTO EN MALAS CONDICIONES (FISICAS Y/O ANIMICAS) PARA REALIZAR MIS TAREAS?'],
            ['elementosp7', 'SE ENCUENTRA BAJO ALGÚN EFECTO DE ALCHOHOL O DROGAS?']
        ];
    }

    /**
     * Obtiene las preguntas de implementos de trabajo
     */
    public static function getImplementosTrabajo()
    {
        return [
            'celular' => [
                'titulo' => '📱 CELULAR',
                'subsection_css' => 'subsection-mandos',
                'preguntas' => [
                    ['implementos1', 'Cuenta con celular con acceso a Internet?'],
                    ['implementos2', 'La bateria de su Celular se encuentra Cargada?'],
                    ['implementos3', 'Su celular cuenta con datos y minutos?'],
                    ['implementos4', 'Tiene usted el cargador de su Celular?']
                ]
            ],
            'pesa' => [
                'titulo' => '⚖️ PESA',
                'subsection_css' => 'subsection-indicadores',
                'preguntas' => [
                    ['implementos10', 'Cuenta con Pesa?'],
                    ['implementos11', 'Su Pesa cuenta con Bateria?'],
                    ['implementos12', 'Verifico que su Pesa cuente con bateria?'],
                    ['implementos13', 'Verifico que su Pesa este funcionando Perfectamente?']
                ]
            ],
            'maleta' => [
                'titulo' => '🎒 MALETA',
                'subsection_css' => 'subsection-cabina',
                'preguntas' => [
                    ['implementos14', 'Cuenta con Maleta?']
                ]
            ],
            'parafiscales' => [
                'titulo' => '📄 PARAFISCALES O COPIA DE AFILIACION DE ARL',
                'subsection_css' => 'subsection-seguridad',
                'preguntas' => [
                    ['implementos18', 'Tiene copia de pago de parafiscales?'],
                    ['implementos19', 'Tiene copia de Afiliacion ARL(Peronal Nuevo)?']
                ]
            ]
        ];
    }

    /**
     * Renderiza una pregunta individual con radio buttons (SI/NO/NA)
     */
    private static function renderRadioQuestionItem($name, $texto, $valoresExistentes = null, $opciones = null)
    {
        if ($opciones === null) {
            $opciones = [
                ['value' => '1', 'label' => 'SI'],
                ['value' => '2', 'label' => 'NO'],
                ['value' => '3', 'label' => 'N.A']
            ];
        }

        $valorExistente = $valoresExistentes[$name] ?? null;

        $html = '<div class="question-item" id="' . $name . '0">';
        $html .= '<div class="question-text">' . htmlspecialchars($texto) . '</div>';
        $html .= '<div class="question-options">';
        foreach ($opciones as $opcion) {
            $checked = ($valorExistente == $opcion['value']) ? 'checked' : '';
            $html .= '<label class="radio-label">';
            $html .= '<input type="radio" name="' . $name . '" class="obtener" value="' . $opcion['value'] . '" ' . $checked . ' required>';
            $html .= '<span class="radio-text">' . $opcion['label'] . '</span></label>';
        }
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Renderiza tarjeta para una subsección de vehículo (radio buttons)
     */
    private static function renderSubsectionCard($titulo, $preguntas, $subsectionCss, $valoresExistentes = null, $opciones = null)
    {
        $html = '<div class="preop-card ' . $subsectionCss . '">';
        $html .= '<div class="preop-card-header">' . $titulo . '</div>';
        $html .= '<div class="preop-card-body">';
        foreach ($preguntas as $preg) {
            $html .= self::renderRadioQuestionItem($preg[0], $preg[1], $valoresExistentes, $opciones);
        }
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Renderiza las secciones de moto como tarjetas individuales
     */
    public static function renderMotoSections($valoresExistentes = null)
    {
        $preguntas = self::getPreguntasMoto();
        $html = '';

        foreach ($preguntas as $seccion) {
            $subsectionCss = isset($seccion['subsection_css']) ? $seccion['subsection_css'] : '';
            $html .= self::renderSubsectionCard(
                $seccion['titulo'],
                $seccion['preguntas'],
                $subsectionCss,
                $valoresExistentes,
                [
                    ['value' => '1', 'label' => 'SI'],
                    ['value' => '2', 'label' => 'NO'],
                    ['value' => '3', 'label' => 'N.A']
                ]
            );
        }

        $html .= '<div class="preop-card warning-card">';
        $html .= '<div class="preop-card-header">⚠️ AVISO IMPORTANTE</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<p style="margin:0;font-size:14px;">SI alguno de estos puntos tiene al menos como respuesta un SI, el trabajador debe de manera inmediata dar aviso de sus condiciones al Jefe de operaciones de la empresa TRANSMILLAS EMPRESA DE CARGA Y LOGISTICA.</p>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Renderiza las secciones de carro como tarjetas individuales
     */
    public static function renderCarroSections($valoresExistentes = null)
    {
        $preguntas = self::getPreguntasCarro();
        $html = '';

        foreach ($preguntas as $seccion) {
            $subsectionCss = isset($seccion['subsection_css']) ? $seccion['subsection_css'] : '';
            $html .= self::renderSubsectionCard(
                $seccion['titulo'],
                $seccion['preguntas'],
                $subsectionCss,
                $valoresExistentes,
                [
                    ['value' => '1', 'label' => 'B'],
                    ['value' => '2', 'label' => 'M'],
                    ['value' => '3', 'label' => 'N.A']
                ]
            );
        }

        $html .= '<div class="preop-card warning-card">';
        $html .= '<div class="preop-card-header">⚠️ AVISO IMPORTANTE</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<p style="margin:0;font-size:14px;">SI alguno de estos puntos tiene al menos como respuesta un SI, el trabajador debe de manera inmediata dar aviso de sus condiciones al Jefe de operaciones de la empresa TRANSMILLAS EMPRESA DE CARGA Y LOGISTICA.</p>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Renderiza tarjeta de fatiga
     */
    public static function renderFatigaCard($valoresExistentes = null)
    {
        $preguntas = self::getPreguntasFatiga();
        $html = '<div class="preop-card subsection-entorno">';
        $html .= '<div class="preop-card-header"><i class="fas fa-bed"></i> CHECK LIST FATIGA</div>';
        $html .= '<div class="preop-card-body">';

        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $valorExistente = $valoresExistentes[$name] ?? null;

            $html .= '<div class="question-item" id="' . $name . '0">';
            $html .= '<div class="question-text">' . htmlspecialchars($texto) . '</div>';
            $html .= '<div class="question-options">';
            // SI
            $html .= '<label class="radio-label">';
            $html .= '<input type="radio" name="' . $name . '" class="obtener" value="1" ' . ($valorExistente == '1' ? 'checked' : '') . ' required>';
            $html .= '<span class="radio-text">SI</span></label>';
            // NO
            $html .= '<label class="radio-label">';
            $html .= '<input type="radio" name="' . $name . '" class="obtener" value="2" ' . ($valorExistente == '2' ? 'checked' : '') . '>';
            $html .= '<span class="radio-text">NO</span></label>';
            $html .= '</div></div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Renderiza tarjetas de implementos de trabajo
     */
    public static function renderImplementosCards($valoresExistentes = null, $ultimaLimpieza = null)
    {
        $implementos = self::getImplementosTrabajo();
        $html = '';

        foreach ($implementos as $key => $seccion) {
            $subsectionCss = isset($seccion['subsection_css']) ? $seccion['subsection_css'] : '';

            $html .= '<div class="preop-card ' . $subsectionCss . '">';
            $html .= '<div class="preop-card-header">' . $seccion['titulo'] . '</div>';
            $html .= '<div class="preop-card-body">';

            foreach ($seccion['preguntas'] as $preg) {
                $name = $preg[0];
                $texto = $preg[1];
                $html .= self::renderRadioQuestionItem($name, $texto, $valoresExistentes, [
                    ['value' => '1', 'label' => 'SI'],
                    ['value' => '2', 'label' => 'NO']
                ]);
            }

            // Campo de última limpieza para maleta
            if ($key === 'maleta' && $ultimaLimpieza !== null) {
                $html .= '<div class="question-item" id="maleta">';
                $html .= '<div class="question-text">Ultima vez que desinfecto la maleta:</div>';
                $html .= '<div style="flex:1;min-width:200px;">';
                $html .= '<input name="param21" id="param21" value="' . htmlspecialchars($ultimaLimpieza) . '" class="form-input">';
                $html .= '</div></div>';
            }

            $html .= '</div></div>';
        }

        return $html;
    }

    /**
     * Tarjeta de validación para formato legado
     */
    public static function renderValidacionLegadoCard($registroExistente)
    {
        $html = '<div class="preop-card validation-card">';
        $html .= '<div class="preop-card-header"><i class="fas fa-clipboard-check"></i> VALIDA PREOPERACIONAL Y COVID 19</div>';
        $html .= '<div class="preop-card-body">';
        $html .= '<textarea name="param10" id="param10" class="form-textarea" placeholder="Descripción de la validación...">' . htmlspecialchars($registroExistente['pre_descvalidada'] ?? '') . '</textarea>';
        $html .= '</div></div>';
        return $html;
    }
}
