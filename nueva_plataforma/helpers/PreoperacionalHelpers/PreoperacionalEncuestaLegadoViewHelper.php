<?php
/**
 * PreoperacionalEncuestaLegadoViewHelper - Helper para la vista de preoperacional legado
 * En base a el antiguo formulario de preoperacional, esta clase centraliza la lógica de presentación
 * 
 * Centraliza la lógica de presentación y los datos de las preguntas
 * para mantener la vista limpia y fácil de mantener.
 */

class PreoperacionalEncuestaLegadoViewHelper
{
    /**
     * Obtiene las preguntas COVID-19
     *
     * @return array Array de preguntas con [nombre, texto]
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
     * Renderiza las preguntas COVID-19 con valores existentes marcados
     *
     * @param string $color Color de fondo
     * @param array|null $valoresExistentes Valores existentes para marcar como checked
     * @return string HTML generado
     */
    public static function renderPreguntasCovid($color = '#EFEFEF', $valoresExistentes = null)
    {
        $preguntas = self::getPreguntasCovid();
        $html = '';

        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $valorExistente = $valoresExistentes[$name] ?? null;
            $claseExtra = in_array($name, ['covid191', 'covid192', 'covid193', 'covid194', 'covid195', 'covid196', 'covid197']) ? 'optionCovid' . substr($name, -1) : '';

            $html .= "<tr bgcolor='{$color}' class='text' id='{$name}0'>";
            $html .= "<td colspan='2'>{$texto}</td>";
            $html .= "<td><input type='radio' name='{$name}' class='obtener {$claseExtra}' value='1' " . ($valorExistente == '1' ? 'checked' : '') . " required></td>";
            $html .= "<td><input type='radio' name='{$name}' class='obtener' value='2' " . ($valorExistente == '2' ? 'checked' : '') . "></td>";
            $html .= "</tr>";
        }

        return $html;
    }

    /**
     * Obtiene las preguntas para motos
     * 
     * @return array Array de secciones con sus preguntas
     */
    public static function getPreguntasMoto()
    {
        return [
            'llantas' => [
                'titulo' => 'LLANTAS Y RINES',
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
                'titulo' => 'TRANSMISIÓN',
                'preguntas' => [
                    ['transmision1', 'La cadena brilla? (Necesita engrase)'],
                    ['transmision2', 'La cadena esta mal tensionada? (se oye al rodar)']
                ]
            ],
            'luces' => [
                'titulo' => 'LUCES Y ESPEJOS',
                'preguntas' => [
                    ['Luces1', 'Funcionan correctamente las Luces de cruce y de frenado?'],
                    ['Luces2', 'Los espejos estan en perfecto estado?'],
                    ['Luces3', 'El manubrio está en óptimas condiciones?']
                ]
            ],
            'fugas' => [
                'titulo' => 'FUGAS',
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
                'titulo' => 'MANDOS (CAMBIOS, FRENOS)',
                'preguntas' => [
                    ['mandos1', 'El embrague esta endurecido?'],
                    ['mandos2', 'El cable de acelerador vuelve del todo a su punto inical?']
                ]
            ],
            'entorno' => [
                'titulo' => 'ENTORNO GENERAL',
                'preguntas' => [
                    ['entorno1', 'La moto esta en buenas condiciones de limpieza?'],
                    ['entorno2', 'Esta deteriorado el chasis de la moto? (oxidación, abolladuras, partes faltantes)?'],
                    ['entorno3', 'Caja de Herramientas']
                ]
            ],
            'elementos' => [
                'titulo' => 'ELEMENTOS DE PROTECCIÓN',
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
     * 
     * @return array Array de secciones con sus preguntas
     */
    public static function getPreguntasCarro()
    {
        return [
            'direccionales' => [
                'titulo' => 'DIRECCIONALES',
                'preguntas' => [
                    ['direccionales1', 'Frontales Plenas altas y/o bajas'],
                    ['direccionales2', 'Direccionales delanteras de parqueo'],
                    ['direccionales3', 'Direccionales traseras de parqueo'],
                    ['direccionales4', 'De Stop y señal trasera']
                ]
            ],
            'cabina' => [
                'titulo' => 'CABINA',
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
                'titulo' => 'DISPOSITIVOS DE SEGURIDAD',
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
                'titulo' => 'INDICADORES',
                'preguntas' => [
                    ['indicadores1', 'Panel de Indicadores'],
                    ['indicadores2', 'Aceite'],
                    ['indicadores3', 'Agua']
                ]
            ],
            'llantas' => [
                'titulo' => 'LLANTAS',
                'preguntas' => [
                    ['llantas1', 'Estado General de llantas'],
                    ['llantas2', 'Llanta de repuesto']
                ]
            ],
            'herramientas' => [
                'titulo' => 'HERRAMIENTAS MINIMAS',
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
     * 
     * @return array Array de preguntas con [nombre, texto]
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
     * 
     * @return array Array de secciones con sus preguntas
     */
    public static function getImplementosTrabajo()
    {
        return [
            'celular' => [
                'titulo' => 'CELULAR',
                'preguntas' => [
                    ['implementos1', 'Cuenta con celular con acceso a Internet?'],
                    ['implementos2', 'La bateria de su Celular se encuentra Cargada?'],
                    ['implementos3', 'Su celular cuenta con datos y minutos?'],
                    ['implementos4', 'Tiene usted el cargador de su Celular?']
                ]
            ],
            'pesa' => [
                'titulo' => 'PESA',
                'preguntas' => [
                    ['implementos10', 'Cuenta con Pesa?'],
                    ['implementos11', 'Su Pesa cuenta con Bateria?'],
                    ['implementos12', 'Verifico que su Pesa cuente con bateria?'],
                    ['implementos13', 'Verifico que su Pesa este funcionando Perfectamente?']
                ]
            ],
            'maleta' => [
                'titulo' => 'MALETA',
                'preguntas' => [
                    ['implementos14', 'Cuenta con Maleta?']
                ]
            ],
            'parafiscales' => [
                'titulo' => 'PARAFISCALES O COPIA DE AFILIACION DE ARL',
                'preguntas' => [
                    ['implementos18', 'Tiene copia de pago de parafiscales?'],
                    ['implementos19', 'Tiene copia de Afiliacion ARL(Peronal Nuevo)?']
                ]
            ]
        ];
    }

    /**
     * Genera el HTML para una sección de preguntas con radio buttons
     *
     * @param string $titulo Título de la sección
     * @param array $preguntas Array de preguntas
     * @param string $color Color de fondo
     * @param array $opciones Opciones de radio buttons (por defecto SI/NO/NA)
     * @param string $tipoHeader Tipo de header (default o custom)
     * @param array|null $valoresExistentes Valores existentes para marcar como checked
     * @return string HTML generado
     */
    public static function renderSeccionPreguntas($titulo, $preguntas, $color = '#EFEFEF',
                                                    $opciones = null, $tipoHeader = 'default', $valoresExistentes = null)
    {
        if ($opciones === null) {
            $opciones = [
                ['value' => '1', 'label' => 'SI'],
                ['value' => '2', 'label' => 'NO'],
                ['value' => '3', 'label' => 'N.A']
            ];
        }

        $html = '';

        // Header de la sección
        $colspan = count($opciones) + 1;
        $html .= "<tr bgcolor=\"#074F91\" class=\"tittle3\">\n";
        $html .= "    <td colspan=\"{$colspan}\" align=\"center\">{$titulo}</td>\n";
        $html .= "</tr>\n";

        // Preguntas
        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $valorExistente = $valoresExistentes[$name] ?? null;

            $html .= "<tr bgcolor='{$color}' class='text' id='{$name}0'>\n";
            $html .= "    <td>{$texto}</td>\n";

            foreach ($opciones as $opcion) {
                $checked = ($valorExistente == $opcion['value']) ? 'checked' : '';
                $html .= "    <td><input type='radio' name='{$name}' class='obtener' value='{$opcion['value']}' {$checked} required></td>\n";
            }

            $html .= "</tr>\n";
        }

        return $html;
    }

    /**
     * Renderiza las secciones de preguntas para moto
     *
     * @param string $color Color de fondo
     * @param array|null $valoresExistentes Valores existentes para marcar como checked
     * @return string HTML generado
     */
    public static function renderMotoSections($color = '#EFEFEF', $valoresExistentes = null)
    {
        $preguntas = self::getPreguntasMoto();
        $html = '';

        foreach ($preguntas as $seccion) {
            $opciones = [
                ['value' => '1', 'label' => 'SI'],
                ['value' => '2', 'label' => 'NO'],
                ['value' => '3', 'label' => 'N.A']
            ];
            $html .= self::renderSeccionPreguntas(
                $seccion['titulo'],
                $seccion['preguntas'],
                $color,
                $opciones,
                'default',
                $valoresExistentes
            );
        }

        // Mensaje de advertencia
        $html .= '<tr bgcolor="#868A08" class="tittle3"><td colspan="4">SI alguno de estos puntos tiene al menos como respuesta un SI, el trabajador debe de manera inmediata dar aviso de sus condciones al Jefe de operaciones de la empresa TRANSMILLAS EMPRESA DE CARGA Y LOGISTICA.</td></tr>';

        return $html;
    }

    /**
     * Renderiza las secciones de preguntas para carro
     *
     * @param string $color Color de fondo
     * @param array|null $valoresExistentes Valores existentes para marcar como checked
     * @return string HTML generado
     */
    public static function renderCarroSections($color = '#EFEFEF', $valoresExistentes = null)
    {
        $preguntas = self::getPreguntasCarro();
        $html = '';

        foreach ($preguntas as $seccion) {
            $opciones = [
                ['value' => '1', 'label' => 'B'],
                ['value' => '2', 'label' => 'M'],
                ['value' => '3', 'label' => 'N.A']
            ];
            $html .= self::renderSeccionPreguntas(
                $seccion['titulo'],
                $seccion['preguntas'],
                $color,
                $opciones,
                'default',
                $valoresExistentes
            );
        }

        // Mensaje de advertencia
        $html .= '<tr bgcolor="#868A08" class="tittle3"><td colspan="4">SI alguno de estos puntos tiene al menos como respuesta un SI, el trabajador debe de manera inmediata dar aviso de sus condciones al Jefe de operaciones de la empresa TRANSMILLAS EMPRESA DE CARGA Y LOGISTICA.</td></tr>';

        return $html;
    }

    /**
     * Renderiza la sección de fatiga
     *
     * @param string $color Color de fondo
     * @param array|null $valoresExistentes Valores existentes para marcar como checked
     * @return string HTML generado
     */
    public static function renderFatigaSection($color = '#EFEFEF', $valoresExistentes = null)
    {
        $preguntas = self::getPreguntasFatiga();
        $opciones = [
            ['value' => '1', 'label' => 'SI'],
            ['value' => '2', 'label' => 'NO']
        ];

        $html = '<tr bgcolor="#074F91" class="tittle3">';
        $html .= '<td colspan="2" align="center">CHECK LIST FATIGA</td>';
        $html .= '<td>SI</td><td>NO</td>';
        $html .= '</tr>';

        foreach ($preguntas as $preg) {
            $name = $preg[0];
            $texto = $preg[1];
            $valorExistente = $valoresExistentes[$name] ?? null;

            $html .= "<tr bgcolor='{$color}' class='text' id='{$name}0'>\n";
            $html .= "    <td colspan='2'>{$texto}</td>\n";
            foreach ($opciones as $opcion) {
                $checked = ($valorExistente == $opcion['value']) ? 'checked' : '';
                $html .= "    <td><input type='radio' name='{$name}' class='obtener' value='{$opcion['value']}' {$checked} required></td>\n";
            }
            $html .= "</tr>\n";
        }

        return $html;
    }

    /**
     * Renderiza la sección de implementos de trabajo
     *
     * @param string $color Color de fondo
     * @param string|null $ultimaLimpieza Valor de última limpieza de maleta
     * @param array|null $valoresExistentes Valores existentes para marcar como checked
     * @return string HTML generado
     */
    public static function renderImplementosTrabajo($color = '#EFEFEF', $ultimaLimpieza = null, $valoresExistentes = null)
    {
        $implementos = self::getImplementosTrabajo();
        $html = '';

        foreach ($implementos as $seccion) {
            $opciones = [
                ['value' => '1', 'label' => 'SI'],
                ['value' => '2', 'label' => 'NO']
            ];

            // Header especial para maleta con colspan diferente
            if ($seccion['titulo'] === 'MALETA') {
                $html .= '<tr bgcolor="#074F91" class="tittle3"><td colspan="2" width="4" align="center">MALETA</td><td colspan="1" width="4" align="center">SI</td><td colspan="1" width="4" align="center">NO</td></tr>';

                foreach ($seccion['preguntas'] as $preg) {
                    $name = $preg[0];
                    $texto = $preg[1];
                    $valorExistente = $valoresExistentes[$name] ?? null;

                    $html .= "<tr bgcolor='{$color}' class='text' id='{$name}0'>";
                    $html .= "<td colspan='2'>{$texto}</td>";
                    foreach ($opciones as $opcion) {
                        $checked = ($valorExistente == $opcion['value']) ? 'checked' : '';
                        $html .= "<td><input type='radio' name='{$name}' class='obtener' value='{$opcion['value']}' {$checked} required></td>";
                    }
                    $html .= "</tr>";
                }

                // Campo de última limpieza
                if ($ultimaLimpieza !== null) {
                    $html .= "<tr bgcolor='{$color}' class='text' id='maleta'>";
                    $html .= "<td colspan='4'>Ultima vez que desinfecto la maleta:";
                    $html .= "<input name='param21' id='param21' value='" . htmlspecialchars($ultimaLimpieza) . "' style='width:395px' class='text'></td></tr>";
                }
            } else {
                $html .= '<tr bgcolor="#074F91" class="tittle3">';
                $html .= '<td colspan="2" width="4" align="center">' . $seccion['titulo'] . '</td>';
                $html .= '<td colspan="1" width="4" align="center">SI</td>';
                $html .= '<td colspan="1" width="4" align="center">NO</td>';
                $html .= '</tr>';

                foreach ($seccion['preguntas'] as $preg) {
                    $name = $preg[0];
                    $texto = $preg[1];
                    $valorExistente = $valoresExistentes[$name] ?? null;

                    $html .= "<tr bgcolor='{$color}' class='text' id='{$name}0'>";
                    $html .= "<td colspan='2'>{$texto}</td>";
                    foreach ($opciones as $opcion) {
                        $checked = ($valorExistente == $opcion['value']) ? 'checked' : '';
                        $html .= "<td><input type='radio' name='{$name}' class='obtener' value='{$opcion['value']}' {$checked} required></td>";
                    }
                    $html .= "</tr>";
                }
            }
        }

        return $html;
    }
}
