<?php
/**
 * PreoperacionalDBHelper - Carga preguntas desde la DB relacional
 *
 * Esta clase consulta las tablas cuestionarios_plantillas, cuestionarios_versiones,
 * cuestionarios_secciones y cuestionarios_preguntas, y devuelve los datos en el MISMO
 * formato de array que esperan los métodos render* de los ViewHelpers.
 *
 * USO: Invocado por los ViewHelpers para cargar preguntas desde la BD relacional.
 * Si no hay datos en BD (tablas vacías), se usa fallback a arrays hardcodeados.
 */

require_once __DIR__ . '/../../model/PreoperacionalModel.php';

class PreoperacionalDBHelper
{
    private static $model = null;
    private static $cacheVersiones = [];
    private static $cacheSecciones = [];

    private static function getModel()
    {
        if (self::$model === null) {
            self::$model = new PreoperacionalModel();
        }
        return self::$model;
    }

    /**
     * Resuelve la versión activa para un tipo de vehículo.
     */
    private static function resolverVersionVehiculo($tipoVehiculo)
    {
        $key = 'veh_' . $tipoVehiculo;
        if (!isset(self::$cacheVersiones[$key])) {
            self::$cacheVersiones[$key] = self::getModel()->obtenerVersionActivaVehiculo($tipoVehiculo);
        }
        return self::$cacheVersiones[$key];
    }

    /**
     * Resuelve la versión activa para un usuario por rol + tipo_vehiculo.
     */
    private static function resolverVersionUsuario($rolUsuario, $tipoVehiculo)
    {
        $key = 'usr_' . $rolUsuario . '_' . $tipoVehiculo;
        if (!isset(self::$cacheVersiones[$key])) {
            self::$cacheVersiones[$key] = self::getModel()->obtenerVersionActivaUsuario($rolUsuario, $tipoVehiculo);
        }
        return self::$cacheVersiones[$key];
    }

    /**
     * Carga secciones y preguntas desde DB (con caché por id_version).
     *
     * @param int $idVersion
     * @return array Secciones con preguntas anidadas
     */
    private static function cargarDesdeDB($idVersion)
    {
        if ($idVersion === null) {
            return [];
        }
        if (!isset(self::$cacheSecciones[$idVersion])) {
            self::$cacheSecciones[$idVersion] = self::getModel()->obtenerSeccionesYPreguntas($idVersion);
        }
        return self::$cacheSecciones[$idVersion];
    }

    // ==================== API PÚBLICA ====================

    /**
     * Carga preguntas en formato de array plano.
     * Usado por: Administrativo, Conductor, Vehículo Propio, Auxiliar Carga.
     *
     * Formato de retorno (compatible con renderPreguntasPersonales):
     *   [[codigo, texto], [codigo, texto, expected], ...]
     *
     * @param int $rolUsuario
     * @param string $tipoVehiculo CARRO, MOTO o vacío
     * @return array
     */
    public static function cargarPreguntasUsuario($rolUsuario, $tipoVehiculo)
    {
        $version = self::resolverVersionUsuario($rolUsuario, $tipoVehiculo);
        if (!$version) {
            return [];
        }

        $secciones = self::cargarDesdeDB($version['id_version']);
        $preguntas = [];

        foreach ($secciones as $seccion) {
            foreach ($seccion['preguntas'] as $preg) {
                $item = [$preg['codigo_interno'], $preg['texto_pregunta']];
                // Si tiene genera_bloqueo, agregar expected_answer como 3er elemento
                if ($preg['genera_bloqueo'] == 1) {
                    $item[] = (int) $preg['respuesta_esperada'];
                }
                $preguntas[] = $item;
            }
        }

        return $preguntas;
    }

    /**
     * Carga preguntas de vehículo en formato anidado (secciones con sub-arrays).
     * Usado por: VehiculoCarro, VehiculoMoto.
     *
     * Formato de retorno (compatible con renderVehiculoCarroSections / renderVehiculoMotoSections):
     *   [ 'key' => ['titulo' => '...', 'subsection_css' => '...', 'preguntas' => [[codigo, texto], ...]] ]
     *
     * @param string $tipoVehiculo CARRO o MOTO
     * @return array
     */
    public static function cargarPreguntasVehiculo($tipoVehiculo)
    {
        $version = self::resolverVersionVehiculo($tipoVehiculo);
        if (!$version) {
            return [];
        }

        $secciones = self::cargarDesdeDB($version['id_version']);
        $resultado = [];

        foreach ($secciones as $seccion) {
            $key = $seccion['css_clase'] ?? 'subsection';
            // Eliminar prefijo 'subsection-' para clave más limpia
            if (strpos($key, 'subsection-') === 0) {
                $key = substr($key, 11);
            }

            $preguntas = [];
            foreach ($seccion['preguntas'] as $preg) {
                $item = [$preg['codigo_interno'], $preg['texto_pregunta']];
                // Si requiere foto, agregar clave 'require_photo'
                if ($preg['requiere_foto_si_negativa'] == 1) {
                    $item['require_photo'] = true;
                }
                $preguntas[] = $item;
            }

            $resultado[$key] = [
                'titulo' => $seccion['nombre'],
                'subsection_css' => $seccion['css_clase'] ?? ('subsection-' . $key),
                'preguntas' => $preguntas
            ];
        }

        return $resultado;
    }

    /**
     * Resuelve qué cuestionarios aplican para un usuario (rol + tipo_vehiculo).
     * Retorna los IDs de versión activos para este usuario.
     *
     * @param int $rolUsuario
     * @param string $tipoVehiculo CARRO, MOTO o vacío
     * @return array [version_vehiculo, version_usuario, ids_versiones]
     */
    public static function resolverCuestionariosUsuario($rolUsuario, $tipoVehiculo)
    {
        $resultado = [
            'version_vehiculo' => null,
            'version_usuario' => null,
            'ids_versiones' => []
        ];

        if (!empty($tipoVehiculo)) {
            $ver = self::resolverVersionVehiculo($tipoVehiculo);
            if ($ver) {
                $resultado['version_vehiculo'] = $ver;
                $resultado['ids_versiones'][] = $ver['id_version'];
            }
        }

        $verUsr = self::resolverVersionUsuario($rolUsuario, $tipoVehiculo);
        if ($verUsr) {
            $resultado['version_usuario'] = $verUsr;
            if (!in_array($verUsr['id_version'], $resultado['ids_versiones'])) {
                $resultado['ids_versiones'][] = $verUsr['id_version'];
            }
        }

        return $resultado;
    }

    /**
     * Retorna el ID de la versión de vehículo activa. Útil como valor unificado
     * para el hidden field id_version en el formulario.
     *
     * @param string $tipoVehiculo
     * @return int|null
     */
    public static function obtenerIdVersionVehiculo($tipoVehiculo)
    {
        $ver = self::resolverVersionVehiculo($tipoVehiculo);
        return $ver ? $ver['id_version'] : null;
    }
}
