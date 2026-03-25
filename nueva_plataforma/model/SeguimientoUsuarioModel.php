<?php
require_once "../config/database.php";

class SeguimientoUsuarioModel
{
    private $db;
    private $vehiculosCache = [];
    private $zonasCache = [];
    private $companerosCache = [];

    private $uploadPath = __DIR__ . '/../../uploads/'; // Ajusta según tu estructura

    public function getDB()
    {
        return $this->db;
    }

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    // ==================== FILTROS Y DATOS AUXILIARES ====================

    // Obtener sedes con operarios activos (excluyendo rol 6)
    public function getSedes()
    {
        $sql = "SELECT DISTINCT s.idsedes, s.sed_nombre 
            FROM sedes s
            INNER JOIN usuarios u ON u.usu_idsede = s.idsedes
            WHERE u.usu_estado = 1 
              AND u.usu_filtro = 1 
              AND u.roles_idroles != 6
            ORDER BY s.sed_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Devuelve el array de motivos de ingreso (valores planos)
     */
    public function getMotivosIngresoArray()
    {
        return [
            'Ingreso' => 'Ingreso',
            'No trabajo' => 'No trabajo',
            'Sancionado' => 'Sancionado',
            'Incapacidad' => 'Incapacidad',
            'Se devolvio' => 'Se devolvio',
            'Positivo Covid' => 'Positivo Covid',
            'Cancelacion contrato' => 'Cancelacion contrato',
            'Abandono de puesto' => 'Abandono de puesto',
            'Vacaciones' => 'Vacaciones',
            'descanso' => 'Descanso',
            'IngresoHoras' => 'Ingreso por horas',
            'descanso no remunerado' => 'Descanso no remunerado',
            'dia con sancion' => 'Dia de sancion Ps',
            'Reposicion por falla' => 'Reposicion por falla',
            'Festivo en vacaciones' => 'Festivo en vacaciones'
        ];
    }

    /**
     * Para el filtro de motivos (todos) también podemos usar el mismo array
     */
    public function getMotivosIngreso($tipo = 'todos')
    {
        $todos = $this->getMotivosIngresoArray();
        return $todos;
    }

    public function getMotivosLicencia()
    {
        // Primero intentamos obtener de la base de datos
        $sql = "SELECT idmotivo_ingreso, mot_nombre FROM motivo_ingreso ORDER BY mot_nombre";
        error_log('SQL: ' . $sql);
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTiposContrato()
    {
        return [
            ['id' => 'Empresa', 'nombre' => 'Empresa'],
            ['id' => 'Prestacion de servicios', 'nombre' => 'Prestación de servicios']
        ];
    }

    public function getOperariosPorSede($idsede)
    {
        $sql = "SELECT idusuarios, usu_nombre 
                FROM usuarios 
                WHERE usu_estado = 1 
                  AND usu_filtro = 1 
                  AND roles_idroles != 6
                  AND usu_idsede = ? 
                ORDER BY usu_nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idsede);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function getTodosOperarios()
    {
        $sql = "SELECT idusuarios, usu_nombre 
            FROM usuarios 
            WHERE usu_estado = 1 
              AND usu_filtro = 1 
              AND roles_idroles != 6 
            ORDER BY usu_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getZonasPorSede($idsede)
    {
        $sql = "SELECT idzonatrabajo, zon_nombre FROM zonatrabajo WHERE inner_sedes = ? ORDER BY zon_nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idsede);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getDeudaOperario($idoperario)
    {
        $sql = "SELECT 
                    SUM(CASE WHEN deu_tipo = 'Prestamos' THEN deu_valor ELSE 0 END) as prestamos,
                    SUM(CASE WHEN deu_tipo = 'Descuadre' THEN deu_valor ELSE 0 END) as descuadre,
                    SUM(CASE WHEN deu_tipo = 'Pagos' THEN deu_valor ELSE 0 END) as pagos
                FROM duedapromotor 
                WHERE deu_idpromotor = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idoperario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $prestamoTotal = ($row['prestamos'] ?? 0) + ($row['descuadre'] ?? 0);
        return $prestamoTotal - ($row['pagos'] ?? 0);
    }

    // ==================== DATATABLE SERVER-SIDE (OPTIMIZADO) ====================
    /**
     * Cuenta el total de USUARIOS que cumplen los filtros (sin paginación).
     */
    public function getTotalRegistros($filtros, $search = '')
    {
        $sql = "SELECT COUNT(DISTINCT u.idusuarios) as total
                FROM usuarios u
                WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6";
        $params = [];
        $types = "";

        if (!empty($filtros['sede']) && $filtros['sede'] > 0) {
            $sql .= " AND u.usu_idsede = ?";
            $params[] = $filtros['sede'];
            $types .= "i";
        }
        if (!empty($filtros['operario']) && $filtros['operario'] > 0) {
            $sql .= " AND u.idusuarios = ?";
            $params[] = $filtros['operario'];
            $types .= "i";
        }
        if (!empty($filtros['tipo_contrato']) && $filtros['tipo_contrato'] !== '0') {
            $sql .= " AND u.usu_tipocontrato = ?";
            $params[] = $filtros['tipo_contrato'];
            $types .= "s";
        }
        if (!empty($search)) {
            $sql .= " AND (u.usu_nombre LIKE ? OR u.usu_identificacion LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $types .= "ss";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Obtiene las filas para la página actual de DataTable.
     */
    public function getRegistrosDataTable($start, $length, $filtros, $search = '', $orderColumn = null, $orderDir = 'ASC')
    {
        if (!empty($filtros['motivo']) && $filtros['motivo'] !== '0') {
            return $this->getRegistrosConMotivo($start, $length, $filtros, $search, $orderColumn, $orderDir);
        } else {
            return $this->getRegistrosSinMotivo($start, $length, $filtros, $search, $orderColumn, $orderDir);
        }
    }

    // ==================== FUNCIONES DE ENRIQUECIMIENTO CON CACHÉ ====================
    private function enriquecerFila($row)
    {
        $fechaActual = date('Y-m-d');

        // Alertas de hoja de vida
        $alertas = [];
        if ($row['falta_cuenta'] > 0)
            $alertas[] = 'Falta cuenta bancaria';
        if ($row['falta_arl'] > 0 && $row['usu_tipocontrato'] === 'Empresa')
            $alertas[] = 'Falta ARL';
        $row['alertas'] = $alertas;
        $row['alerta_count'] = count($alertas);
        $row['alerta_html'] = $this->generarBurbujaAlertas($row['idusuarios'], $alertas);

        // Color de la fila
        $row['row_color'] = $this->determinarColorFila($row); // también define row_bg_color y row_text_color

        // Obtener datos de vehículo si existe
        $vehiculo = null;
        if (!empty($row['prevehiculo'])) {
            $vehiculo = $this->getVehiculo($row['prevehiculo']);
        }

        // Obtener nombre de zona
        $zonaNombre = null;
        if (!empty($row['seg_idzona'])) {
            $zonaNombre = $this->getZonaNombre($row['seg_idzona']);
        }

        // Enlaces
        $row['preoperacional_link'] = $this->linkPreoperacional($row);
        $row['validacion_link'] = $this->linkValidacion($row);
        $row['imagen_link'] = $this->linkImagen($row);
        $row['ingreso_link'] = $this->linkIngreso($row);
        $row['zona_link'] = $this->linkZona($row, $row['zona_nombre'] ?? null);
        $row['companero_link'] = $this->linkCompanero($row);
        $row['hora_almuerzo_link'] = $this->linkHoraAlmuerzo($row);
        $row['retorno_almuerzo_link'] = $this->linkRetornoAlmuerzo($row);
        $row['retorno_oficina_link'] = $this->linkRetornoOficina($row);

        // Fechas con alerta
        $row['fecha_seguro_html'] = $this->formatoFechaConAlerta($vehiculo['veh_fechaseguro'] ?? null, $fechaActual);
        $row['fecha_tecno_html'] = $this->formatoFechaConAlerta($vehiculo['veh_fechategnomecanica'] ?? null, $fechaActual);
        $row['fecha_licencia_html'] = $this->formatoFechaConAlerta($row['usu_fechalicencia'], $fechaActual);

        // Cambio de aceite
        $row['cambio_aceite_html'] = $this->formatoCambioAceite($row, $vehiculo);

        // Botón eliminar
        $row['eliminar_html'] = $this->linkEliminar($row);

        // Añadir datos de vehículo para usar en otros lugares si es necesario
        $row['veh_placa'] = $vehiculo['veh_placa'] ?? null;
        $row['veh_fechaseguro'] = $vehiculo['veh_fechaseguro'] ?? null;
        $row['veh_fechategnomecanica'] = $vehiculo['veh_fechategnomecanica'] ?? null;
        $row['veh_aceitekil'] = $vehiculo['veh_aceitekil'] ?? null;
        $row['veh_kmalcambaceite'] = $vehiculo['veh_kmalcambaceite'] ?? null;
        $row['veh_kilactual'] = $vehiculo['veh_kilactual'] ?? null;

        return $row;
    }

    private function generarBurbujaAlertas($idUsuario, $alertas)
    {
        if (empty($alertas))
            return '';
        $items = '';
        foreach ($alertas as $a) {
            $items .= "<li>$a</li>";
        }
        return "<div class='noti_bubble' data-id='$idUsuario'>" . count($alertas) . "</div>
                <div class='noti_options' data-id='$idUsuario' style='display:none;'><ul>$items</ul></div>";
    }

    private function determinarColorFila(&$row)
    {
        // Inicializar colores por defecto (fondo blanco, texto negro)
        $bg = '#FFFFFF';
        $text = '#000000';

        if ($row['seg_motivo'] === 'descanso') {
            // Amarillo suave con texto marrón oscuro 
            $bg = '#fff3cd';
            $text = '#664d03';
        } elseif ($row['seg_motivo'] === 'Vacaciones') {
            // Verde agua con texto verde oscuro 
            $bg = '#d1e7dd';
            $text = '#0f5132';
        } elseif (!empty($row['idpreoperacinal'])) {
            if ($row['preestado'] !== 'Validado' && $row['preestado'] !== 'Validado Covid19') {
                if ($row['preestado'] === 'No aplica') {
                    $bg = ($row['idpreoperacinal'] % 2 == 0) ? '#FFFFFF' : '#EFEFEF';
                    $text = '#000000';
                } else {
                    // Rojo pálido con texto rojo oscuro (preoperacional no validado)
                    $bg = '#fde2e1';
                    $text = '#7b1913';
                }
            } else {
                // Preoperacional validado: alternar blanco/gris claro, texto negro
                $bg = ($row['idpreoperacinal'] % 2 == 0) ? '#FFFFFF' : '#EFEFEF';
                $text = '#000000';
            }
        } elseif (!empty($row['idseguimiento_user'])) {
            // Seguimiento existente (sin preoperacional no validado): alternar blanco/gris claro
            $bg = ($row['idseguimiento_user'] % 2 == 0) ? '#FFFFFF' : '#EFEFEF';
            $text = '#000000';
        } else {
            // Sin ingreso ni preoperacional: rosa claro con texto rojo oscuro
            $bg = '#f8d7da';
            $text = '#721c24';
        }

        // Guardar colores en el array $row (se pasará por referencia)
        $row['row_bg_color'] = $bg;
        $row['row_text_color'] = $text;
        return $bg; // mantener compatibilidad con row_color
    }

    private function getVehiculo($id)
    {
        if (!$id)
            return null;
        if (!isset($this->vehiculosCache[$id])) {
            $sql = "SELECT veh_placa, veh_fechaseguro, veh_fechategnomecanica, veh_aceitekil, veh_kmalcambaceite, veh_kilactual 
                    FROM vehiculos WHERE idvehiculos = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $this->vehiculosCache[$id] = $result->fetch_assoc() ?: null;
        }
        return $this->vehiculosCache[$id];
    }

    private function getZonaNombre($id)
    {
        if (!$id)
            return null;
        if (!isset($this->zonasCache[$id])) {
            $sql = "SELECT zon_nombre FROM zonatrabajo WHERE idzonatrabajo = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $this->zonasCache[$id] = $row ? $row['zon_nombre'] : null;
        }
        return $this->zonasCache[$id];
    }

    private function getNombreCompanero($id)
    {
        if (!$id || $id <= 0)
            return 'Desconocido';
        if (!isset($this->companerosCache[$id])) {
            $sql = "SELECT usu_nombre FROM usuarios WHERE idusuarios = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $this->companerosCache[$id] = $row ? $row['usu_nombre'] : 'Desconocido';
        }
        return $this->companerosCache[$id];
    }

    public function getSeguimientoById($id)
    {
        $sql = "SELECT * FROM seguimiento_user WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Obtiene los datos de un operario para precargar en el modal de ingreso
     */
    public function getOperarioById($id)
    {
        $sql = "SELECT idusuarios, usu_nombre, usu_idsede FROM usuarios WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getSedeByUsuario($idUsuario)
    {
        $sql = "SELECT usu_idsede FROM usuarios WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['usu_idsede'] ?? 0;
    }

    public function getSedeById($id)
    {
        $sql = "SELECT idsedes, sed_nombre FROM sedes WHERE idsedes = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function linkPreoperacional($row)
    {
        if (empty($row['idpreoperacinal']))
            return '';
        if ($row['preestado'] === 'No aplica' || $row['preestado'] === 'descanso' || $row['preestado'] === 'Vacaciones')
            return $row['preestado'];
        $url = "../../validaoperacional.php?iduser={$row['idusuarios']}&fecha={$row['fecha']}&idvehiculo={$row['prevehiculo']}&campo=preencuesta";
        return "<a href='#' onclick='window.open(\"$url\",\"_blank\",\"width=800,height=600,scrollbars=yes\")'>{$row['preestado']}</a>";
    }

    private function linkValidacion($row)
    {
        if (empty($row['idpreoperacinal']))
            return '';
        if ($row['preestado'] === 'Validado' || $row['preestado'] === 'Validado Covid19') {
            $url = "../../validaoperacional.php?iduser={$row['idusuarios']}&fecha={$row['fecha']}&idvehiculo={$row['prevehiculo']}&campo=predatosvalidados";
            return "<a href='#' onclick='window.open(\"$url\",\"_blank\",\"width=800,height=600,scrollbars=yes\")'>Validado</a>";
        }
        return 'Sin Validar';
    }

    private function linkImagen($row)
    {
        if (empty($row['idseguimiento_user']))
            return '';
        return $this->llenadocs3('seguimiento_user', $row['idseguimiento_user'], 1, 35, 'Ver');
    }

    private function linkIngreso($row)
    {
        $param1 = $row['idseguimiento_user'] ?: $row['idusuarios'];
        $texto = $row['idseguimiento_user'] ? 'Ingreso+' : 'Sin Ingreso';
        // El parámetro extra puede ser la sede o lo que necesites; aquí paso un string con caso y sede (si quieres)
        // Si no necesitas más, puedes pasar un parámetro vacío o la fecha.
        $paramExtra = $row['usu_idsede'] ?? 0;
        return "<a href='#' onclick='abrirPopup(\"ingreso\", $param1, \"$paramExtra\")'>$texto</a>";
    }

    private function linkZona($row, $zonaNombre = null)
    {
        $idSeg = $row['idseguimiento_user'] ?? null;

        // Si no hay seguimiento, no se puede asignar zona
        if (empty($idSeg)) {
            return 'Faltante'; // plain text
        }

        if (empty($row['seg_idzona'])) {
            $texto = 'Faltante';
        } else {
            $texto = htmlspecialchars($zonaNombre ?: $this->getZonaNombre($row['seg_idzona']) ?: '');
        }

        $fecha = $row['prefechaingreso'] ?? $row['fecha'];
        return "<a href='#' onclick='abrirPopup(\"zona\", $idSeg, \"{$fecha}\")'>$texto</a>";
    }

    private function linkCompanero($row)
    {
        $companeroId = $row['seg_compañero'] ?? null;
        $idSeg = $row['idseguimiento_user'] ?? null;

        // Si no hay seguimiento, no se puede asignar compañero
        if (empty($idSeg)) {
            return 'Sin compañero'; // plain text
        }

        // Determinar texto del enlace
        if (empty($companeroId) || $companeroId <= 0) {
            $texto = 'Sin compañero';
        } else {
            $texto = $this->getNombreCompanero($companeroId) ?: 'Compañero desconocido';
        }

        $fecha = date('Y-m-d', strtotime($row['fecha']));
        return "<a href='#' onclick='abrirPopup(\"trabaja_con\", $idSeg, \"{$fecha} _ {$row['idusuarios']}\")'>$texto</a>";
    }

    private function linkHoraAlmuerzo($row)
    {
        if (empty($row['idseguimiento_user']))
            return '';
        $hora = $row['seg_horaalmuerzo'] ?: 'Sin Ingresar';
        return "<a href='#' onclick='abrirPopup(\"hora_almuerzo\", {$row['idseguimiento_user']}, \"{$row['prefechaingreso']}\")'>$hora</a>";
    }

    private function linkRetornoAlmuerzo($row)
    {
        if (empty($row['idseguimiento_user']))
            return '';
        $hora = $row['seg_horaregreso'] ?: 'Sin Ingresar';
        return "<a href='#' onclick='abrirPopup(\"retorno_almuerzo\", {$row['idseguimiento_user']}, \"{$row['prefechaingreso']}\")'>$hora</a>";
    }

    private function linkRetornoOficina($row)
    {
        if (empty($row['idseguimiento_user']))
            return '';
        $hora = $row['seg_horaoficina'] ?: 'Sin Ingresar';
        return "<a href='#' onclick='abrirPopup(\"retorno_oficina\", {$row['idseguimiento_user']}, \"{$row['prefechaingreso']}\")'>$hora</a>";
    }


    private function formatoFechaConAlerta($fecha, $hoy)
    {
        if (!$fecha || $fecha === '0000-00-00')
            return '';
        $dias = $this->diasHasta($hoy, $fecha);
        if ($dias <= 3 && $dias >= 0) {
            return "<span style='background-color:#F39C12'>$fecha</span>";
        }
        return $fecha;
    }

    private function formatoCambioAceite($row, $vehiculo)
    {
        if (empty($vehiculo) || empty($vehiculo['veh_aceitekil']) || empty($vehiculo['veh_kmalcambaceite'])) {
            return '-';
        }
        $kmRecorridos = $vehiculo['veh_kilactual'] - $vehiculo['veh_kmalcambaceite'];
        $restantes = $vehiculo['veh_aceitekil'] - $kmRecorridos;
        if ($restantes <= 0) {
            return "<span style='background-color:#F39C12'>Cambie aceite, excede {$kmRecorridos}km</span>";
        }
        return "{$restantes}km de {$vehiculo['veh_aceitekil']}km";
    }

    private function linkEliminar($row)
    {
        if (empty($row['idpreoperacinal']) && empty($row['idseguimiento_user']))
            return '';
        return $this->edites($row['idpreoperacinal'] . '_' . $row['idseguimiento_user'], 'borraseguser', 2, 0);
    }

    public function actualizarZona($id_seguimiento, $zona)
    {
        $sql = "UPDATE seguimiento_user SET seg_idzona = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $zona, $id_seguimiento);
        return $stmt->execute();
    }

    public function actualizarHoraAlmuerzo($id_seguimiento, $hora)
    {
        $sql = "UPDATE seguimiento_user SET seg_horaalmuerzo = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $hora, $id_seguimiento);
        return $stmt->execute();
    }

    public function actualizarRetornoAlmuerzo($id_seguimiento, $hora)
    {
        $sql = "UPDATE seguimiento_user SET seg_horaregreso = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $hora, $id_seguimiento);
        return $stmt->execute();
    }

    public function actualizarRetornoOficina($id_seguimiento, $hora)
    {
        $sql = "UPDATE seguimiento_user SET seg_horaoficina = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $hora, $id_seguimiento);
        return $stmt->execute();
    }

    public function actualizarCompanero($id_seguimiento, $companero)
    {
        // Si el compañero es 0 o negativo, establecer a NULL
        if ($companero <= 0) {
            $sql = "UPDATE seguimiento_user SET seg_compañero = NULL WHERE idseguimiento_user = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $id_seguimiento);
        } else {
            $sql = "UPDATE seguimiento_user SET seg_compañero = ? WHERE idseguimiento_user = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $companero, $id_seguimiento);
        }
        return $stmt->execute();
    }
    public function getNombreOperario($id)
    {
        $sql = "SELECT usu_nombre FROM usuarios WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['usu_nombre'] : '';
    }


    // Funciones que originalmente estaban en $LT y que ahora se han movido aquí para mantener la lógica de negocio en el modelo
    private function llenadocs3($tabla, $idviene, $version, $ancho, $texto)
    {
        if (!$idviene)
            return '';
        $sql = "SELECT iddocumentos, doc_ruta, doc_fecha FROM documentos 
            WHERE doc_idviene = ? AND doc_tabla = ? AND doc_version = ? 
            ORDER BY doc_fecha DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $idviene, $tabla, $version);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Usar el endpoint para servir el archivo (por seguridad)
            $url = "?accion=ver_documento&id=" . $row['iddocumentos'];
            return "<a href='#' onclick='window.open(\"$url\", \"_blank\")'><img src='img/icono_documento.png' width='$ancho'> $texto</a>";
        }
        return '';
    }

    private function edites($id, $accion, $tipo, $param)
    {
        // Implementa según tu lógica de eliminación
        return "<a href='#' onclick='eliminarRegistro(\"$id\", \"$accion\")'><i class='fa fa-trash'></i></a>";
    }

    private function diasHasta($hoy, $fecha)
    {
        if (!$fecha || $fecha === '0000-00-00')
            return null;
        $hoyTs = strtotime($hoy);
        $fechaTs = strtotime($fecha);
        return round(($fechaTs - $hoyTs) / 86400);
    }

    public function insertarIngreso($data, $imagen, $id_usuario)
    {
        $fecha_completa = $data['fecha'] . ' ' . date('H:i:s');
        $sql = "INSERT INTO seguimiento_user 
                (seg_idusuario, seg_fechaingreso, seg_motivo, seg_descr, seg_idzona, seg_alcohol, seg_fechaalcohol, seg_iduserregistro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en prepare: " . $this->db->error);
        }
        $stmt->bind_param(
            "isssisss",
            $data['operario'],
            $fecha_completa,
            $data['motivo'],
            $data['descripcion'],
            $data['zona'],
            $data['prueba'],
            $data['fecha'],
            $id_usuario
        );
        $ok = $stmt->execute();
        $id_seguimiento = $this->db->insert_id;

        // Si hay imagen, procesar (depende de tu sistema de documentos)
        if ($ok && $imagen && $imagen['tmp_name']) {
            $this->guardarImagen($imagen, 'seguimiento_user', $id_seguimiento);
        }

        // Verificar si existe pre-operacional para esa fecha, si no, insertar uno
        $sql2 = "SELECT idpreoperacinal FROM `pre-operacional` WHERE preidusuario = ? AND DATE(prefechaingreso) = ?";
        $stmt2 = $this->db->prepare($sql2);
        if (!$stmt2) {
            throw new Exception("Error en prepare: " . $this->db->error);
        }
        $stmt2->bind_param("is", $data['operario'], $data['fecha']);
        $stmt2->execute();
        $existe = $stmt2->get_result()->fetch_assoc();
        if (!$existe) {
            $sql3 = "INSERT INTO `pre-operacional` (prefechaingreso, preidusuario, preestado) VALUES (?, ?, 'No aplica')";
            $stmt3 = $this->db->prepare($sql3);
            $fecha_con_hora = $data['fecha'] . ' 00:00:00';
            $stmt3->bind_param("si", $fecha_con_hora, $data['operario']);
            $stmt3->execute();
        }

        return $ok;
    }
    public function actualizarIngreso($id_seguimiento, $data, $imagen, $id_usuario)
    {
        $sql = "UPDATE seguimiento_user SET 
                seg_motivo = ?, seg_descr = ?, seg_idzona = ?, seg_alcohol = ?, seg_horas_trabajadas = ?
                WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "ssissi",
            $data['motivo'],
            $data['descripcion'],
            $data['zona'],
            $data['prueba'],
            $data['horas'],
            $id_seguimiento
        );
        $ok = $stmt->execute();
        if ($ok && $imagen && $imagen['tmp_name']) {
            $this->guardarImagen($imagen, 'seguimiento_user', $id_seguimiento);
        }
        return $ok;
    }
    public function insertarFestivos($fecha, $sede, $id_usuario)
    {
        // Obtener todos los operarios activos con contrato Empresa y sin fecha de terminación en hoja de vida
        $sql = "SELECT u.idusuarios 
                FROM usuarios u
                INNER JOIN hojadevida h ON h.hoj_cedula = u.usu_identificacion
                WHERE u.usu_estado = 1 
                  AND u.usu_filtro = 1 
                  AND u.usu_tipocontrato = 'Empresa'
                  AND (h.hoj_fechatermino IS NULL OR h.hoj_fechatermino = '0000-00-00')
                  AND u.roles_idroles != 6";
        if ($sede > 0) {
            $sql .= " AND u.usu_idsede = $sede";
        }
        $result = $this->db->query($sql);
        $this->db->begin_transaction();
        try {
            while ($row = $result->fetch_assoc()) {
                $iduser = $row['idusuarios'];
                // Verificar si ya tiene ingreso ese día
                $check = "SELECT idseguimiento_user FROM seguimiento_user WHERE seg_idusuario = ? AND DATE(seg_fechaingreso) = ?";
                $stmt = $this->db->prepare($check);
                $stmt->bind_param("is", $iduser, $fecha);
                $stmt->execute();
                $existe = $stmt->get_result()->fetch_assoc();
                if (!$existe) {
                    // Insertar en seguimiento_user
                    $fecha_hora = $fecha . ' 00:00:00';
                    $sql1 = "INSERT INTO seguimiento_user (seg_idusuario, seg_fechaingreso, seg_motivo, seg_descr, seg_alcohol, seg_fechaalcohol, seg_iduserregistro)
                             VALUES (?, ?, 'descanso', 'descanso', 'No aplica', ?, ?)";
                    $stmt1 = $this->db->prepare($sql1);
                    $stmt1->bind_param("issi", $iduser, $fecha_hora, $fecha, $id_usuario);
                    $stmt1->execute();

                    // Insertar en pre-operacional
                    $sql2 = "INSERT INTO `pre-operacional` (prefechaingreso, preidusuario, preestado) VALUES (?, ?, 'descanso')";
                    $stmt2 = $this->db->prepare($sql2);
                    $stmt2->bind_param("si", $fecha_hora, $iduser);
                    $stmt2->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
    public function insertarVacaciones($data, $id_usuario)
    {
        $iduser = $data['operario'];
        $inicio = new DateTime($data['fecha_ini']);
        $fin = new DateTime($data['fecha_fin']);
        $interval = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $interval, $fin->modify('+1 day'));

        $this->db->begin_transaction();
        try {
            foreach ($periodo as $fecha) {
                $fecha_str = $fecha->format('Y-m-d');
                $check = "SELECT idseguimiento_user FROM seguimiento_user WHERE seg_idusuario = ? AND DATE(seg_fechaingreso) = ?";
                $stmt = $this->db->prepare($check);
                $stmt->bind_param("is", $iduser, $fecha_str);
                $stmt->execute();
                $existe = $stmt->get_result()->fetch_assoc();
                if (!$existe) {
                    $fecha_hora = $fecha_str . ' 00:00:00';
                    $sql1 = "INSERT INTO seguimiento_user (seg_idusuario, seg_fechaingreso, seg_motivo, seg_descr, seg_alcohol, seg_fechaalcohol, seg_iduserregistro)
                             VALUES (?, ?, 'Vacaciones', 'Vacaciones', 'No aplica', ?, ?)";
                    $stmt1 = $this->db->prepare($sql1);
                    $stmt1->bind_param("issi", $iduser, $fecha_hora, $fecha_str, $id_usuario);
                    $stmt1->execute();

                    $sql2 = "INSERT INTO `pre-operacional` (prefechaingreso, preidusuario, preestado) VALUES (?, ?, 'vacaciones')";
                    $stmt2 = $this->db->prepare($sql2);
                    $stmt2->bind_param("si", $fecha_hora, $iduser);
                    $stmt2->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function insertarLicencia($data, $id_usuario)
    {
        $iduser = $data['operario'];
        $inicio = new DateTime($data['fecha_ini']);
        $fin = new DateTime($data['fecha_fin']);
        $interval = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $interval, $fin->modify('+1 day'));

        $this->db->begin_transaction();
        try {
            foreach ($periodo as $fecha) {
                $fecha_str = $fecha->format('Y-m-d');
                $check = "SELECT idseguimiento_user FROM seguimiento_user WHERE seg_idusuario = ? AND DATE(seg_fechaingreso) = ?";
                $stmt = $this->db->prepare($check);
                $stmt->bind_param("is", $iduser, $fecha_str);
                $stmt->execute();
                $existe = $stmt->get_result()->fetch_assoc();
                if (!$existe) {
                    $fecha_hora = $fecha_str . ' 00:00:00';
                    $sql1 = "INSERT INTO seguimiento_user (seg_idusuario, seg_fechaingreso, seg_motivo, seg_descr, seg_alcohol, seg_fechaalcohol, seg_iduserregistro)
                             VALUES (?, ?, ?, ?, 'No aplica', ?, ?)";
                    $stmt1 = $this->db->prepare($sql1);
                    $stmt1->bind_param("issssi", $iduser, $fecha_hora, $data['motivo'], $data['descripcion'], $fecha_str, $id_usuario);
                    $stmt1->execute();

                    $sql2 = "INSERT INTO `pre-operacional` (prefechaingreso, preidusuario, preestado) VALUES (?, ?, ?)";
                    $stmt2 = $this->db->prepare($sql2);
                    $stmt2->bind_param("sis", $fecha_hora, $iduser, $data['motivo']);
                    $stmt2->execute();
                } else {
                    // Si ya existe, actualizar motivo y descripción
                    $sql_up = "UPDATE seguimiento_user SET seg_motivo = ?, seg_descr = ? WHERE idseguimiento_user = ?";
                    $stmt_up = $this->db->prepare($sql_up);
                    $stmt_up->bind_param("ssi", $data['motivo'], $data['descripcion'], $existe['idseguimiento_user']);
                    $stmt_up->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Obtiene datos cuando NO hay filtro de motivo (genera combinaciones usuario-día)
     */
    private function getRegistrosSinMotivo($start, $length, $filtros, $search, $orderColumn, $orderDir)
    {
        // 1. Obtener TODOS los usuarios que cumplen filtros (sin paginar)
        $sqlUsuarios = "SELECT u.idusuarios, u.usu_nombre, u.usu_identificacion, u.usu_tipocontrato,
                           u.usu_fechalicencia, u.usu_idsede
                    FROM usuarios u
                    WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6";
        $params = [];
        $types = "";

        // Aplicar filtros de sede, operario, tipo_contrato y búsqueda
        if (!empty($filtros['sede']) && $filtros['sede'] > 0) {
            $sqlUsuarios .= " AND u.usu_idsede = ?";
            $params[] = $filtros['sede'];
            $types .= "i";
        }
        if (!empty($filtros['operario']) && $filtros['operario'] > 0) {
            $sqlUsuarios .= " AND u.idusuarios = ?";
            $params[] = $filtros['operario'];
            $types .= "i";
        }
        if (!empty($filtros['tipo_contrato']) && $filtros['tipo_contrato'] !== '0') {
            $sqlUsuarios .= " AND u.usu_tipocontrato = ?";
            $params[] = $filtros['tipo_contrato'];
            $types .= "s";
        }
        if (!empty($search)) {
            $sqlUsuarios .= " AND (u.usu_nombre LIKE ? OR u.usu_identificacion LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $types .= "ss";
        }

        $stmt = $this->db->prepare($sqlUsuarios);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($usuarios)) {
            return [];
        }

        // 2. Generar array de días
        $fecha_inicio = $filtros['fecha_inicio'] ?? date('Y-m-d');
        $fecha_fin = $filtros['fecha_fin'] ?? date('Y-m-d');
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        $interval = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $interval, $fin->modify('+1 day'));
        $dias = [];
        foreach ($periodo as $fecha) {
            $dias[] = $fecha->format('Y-m-d');
        }

        // 3. Obtener IDs de usuarios
        $userIds = array_column($usuarios, 'idusuarios');
        $userIdsPlaceholder = implode(',', array_fill(0, count($userIds), '?'));

        // 4. Consultar pre-operacional para esos usuarios en el rango
        $sqlPre = "SELECT p.idpreoperacinal, p.preidusuario, p.prefechaingreso, p.preestado, p.prevehiculo,
                      DATE(p.prefechaingreso) as fecha
               FROM `pre-operacional` p
               WHERE p.preidusuario IN ($userIdsPlaceholder)
                 AND DATE(p.prefechaingreso) BETWEEN ? AND ?";
        $preParams = array_merge($userIds, [$fecha_inicio, $fecha_fin]);
        $preTypes = str_repeat('i', count($userIds)) . 'ss';
        $stmt = $this->db->prepare($sqlPre);
        $stmt->bind_param($preTypes, ...$preParams);
        $stmt->execute();
        $preRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $preIndex = [];
        foreach ($preRows as $row) {
            $preIndex[$row['preidusuario']][$row['fecha']] = $row;
        }

        // 5. Consultar seguimiento_user (sin filtro de motivo)
        $sqlSeg = "SELECT s.idseguimiento_user, s.seg_idusuario, s.seg_fechaingreso, s.seg_motivo, s.seg_descr,
                      s.seg_alcohol, s.seg_horaalmuerzo, s.seg_horaregreso, s.seg_horaoficina, s.seg_fechafinalizo,
                      s.seg_compañero, s.seg_idzona, DATE(s.seg_fechaalcohol) as fecha
               FROM seguimiento_user s
               WHERE s.seg_idusuario IN ($userIdsPlaceholder)
                 AND DATE(s.seg_fechaalcohol) BETWEEN ? AND ?";
        $segParams = array_merge($userIds, [$fecha_inicio, $fecha_fin]);
        $stmt = $this->db->prepare($sqlSeg);
        $stmt->bind_param($preTypes, ...$segParams);
        $stmt->execute();
        $segRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $segIndex = [];
        foreach ($segRows as $row) {
            $segIndex[$row['seg_idusuario']][$row['fecha']] = $row;
        }

        // 6. Consultar hojadevida para alertas
        $identificaciones = array_column($usuarios, 'usu_identificacion');
        $idPlaceholder = implode(',', array_fill(0, count($identificaciones), '?'));
        $sqlHv = "SELECT hoj_cedula,
                     (hoj_cuen IS NULL OR hoj_cuen = '') as falta_cuenta,
                     (hoj_arl IS NULL OR hoj_arl = '') as falta_arl
              FROM hojadevida
              WHERE hoj_cedula IN ($idPlaceholder) AND hoj_estado = 'Activo'";
        $stmt = $this->db->prepare($sqlHv);
        $stmt->bind_param(str_repeat('s', count($identificaciones)), ...$identificaciones);
        $stmt->execute();
        $hvRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $hvIndex = [];
        foreach ($hvRows as $row) {
            $hvIndex[$row['hoj_cedula']] = $row;
        }

        // 7. Construir array completo (todos los usuarios × todos los días)
        $allData = [];
        foreach ($usuarios as $u) {
            $id = $u['idusuarios'];
            $cedula = $u['usu_identificacion'];
            $hv = $hvIndex[$cedula] ?? ['falta_cuenta' => 0, 'falta_arl' => 0];
            foreach ($dias as $dia) {
                $pre = $preIndex[$id][$dia] ?? null;
                $seg = $segIndex[$id][$dia] ?? null;

                $row = [
                    'idusuarios' => $id,
                    'usu_nombre' => $u['usu_nombre'],
                    'usu_identificacion' => $cedula,
                    'usu_tipocontrato' => $u['usu_tipocontrato'],
                    'usu_fechalicencia' => $u['usu_fechalicencia'],
                    'usu_idsede' => $u['usu_idsede'],
                    'falta_cuenta' => $hv['falta_cuenta'],
                    'falta_arl' => $hv['falta_arl'],
                    'fecha' => $dia,
                    'idpreoperacinal' => $pre['idpreoperacinal'] ?? null,
                    'prefechaingreso' => $pre['prefechaingreso'] ?? null,
                    'preestado' => $pre['preestado'] ?? null,
                    'prevehiculo' => $pre['prevehiculo'] ?? null,
                    'idseguimiento_user' => $seg['idseguimiento_user'] ?? null,
                    'seg_fechaingreso' => $seg['seg_fechaingreso'] ?? null,
                    'seg_motivo' => $seg['seg_motivo'] ?? null,
                    'seg_descr' => $seg['seg_descr'] ?? null,
                    'seg_alcohol' => $seg['seg_alcohol'] ?? null,
                    'seg_horaalmuerzo' => $seg['seg_horaalmuerzo'] ?? null,
                    'seg_horaregreso' => $seg['seg_horaregreso'] ?? null,
                    'seg_horaoficina' => $seg['seg_horaoficina'] ?? null,
                    'seg_fechafinalizo' => $seg['seg_fechafinalizo'] ?? null,
                    'seg_compañero' => $seg['seg_compañero'] ?? null,
                    'seg_idzona' => $seg['seg_idzona'] ?? null,
                ];

                $row = $this->enriquecerFila($row);
                $allData[] = $row;
            }
        }

        // 8. Ordenar globalmente según prioridad y nombre
        usort($allData, function ($a, $b) {
            $prioridadA = $this->getPrioridadOrden($a);
            $prioridadB = $this->getPrioridadOrden($b);
            if ($prioridadA != $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }
            // Si misma prioridad, ordenar por nombre
            return strcmp($a['usu_nombre'], $b['usu_nombre']);
        });

        // 9. Aplicar paginación
        $paginated = array_slice($allData, $start, $length);
        return $paginated;
    }

    private function getPrioridadOrden($row)
    {
        // 1 = preoperacional sin validar (más prioridad)
        // 2 = no ha ingresado
        // 3 = preoperacional no necesario
        // 4 = preoperacional validado
        // 5 = vacaciones
        // 6 = descanso
        if (!empty($row['idpreoperacinal'])) {
            $estado = $row['preestado'];
            if ($estado !== 'Validado' && $estado !== 'Validado Covid19') {
                if ($estado === 'No aplica') {
                    return 3;
                } else if ($estado === 'vacaciones') {
                    return 5;
                } else if ($estado === 'descanso') {
                    return 6;
                } else {
                    return 1;
                }
            }

        }
        if (empty($row['idseguimiento_user'])) {
            return 2;
        }
        return 6;
    }

    public function getTotalFiltrados($filtros, $search = '')
    {
        if (!empty($filtros['motivo']) && $filtros['motivo'] !== '0') {
            return $this->getTotalFiltradosConMotivo($filtros, $search);
        } else {
            return $this->getTotalFiltradosSinMotivo($filtros, $search);
        }
    }

    private function getTotalFiltradosSinMotivo($filtros, $search)
    {
        // Cuando no hay motivo, el total filtrado es igual al total de registros (usuarios * días)
        // porque asumimos que el filtro de motivo no está presente. Si hay otros filtros (sede, etc.),
        // ya se aplicaron en getTotalRegistros (que cuenta usuarios), pero aquí necesitamos usuarios*días.
        $totalUsuarios = $this->getTotalRegistros($filtros, $search);
        $fecha_inicio = new DateTime($filtros['fecha_inicio']);
        $fecha_fin = new DateTime($filtros['fecha_fin']);
        $dias = $fecha_inicio->diff($fecha_fin)->days + 1;
        return $totalUsuarios * $dias;
    }

    private function getTotalFiltradosConMotivo($filtros, $search)
    {
        $fecha_inicio = $filtros['fecha_inicio'] ?? date('Y-m-d');
        $fecha_fin = $filtros['fecha_fin'] ?? date('Y-m-d');
        $motivo = $filtros['motivo'];

        $sql = "SELECT COUNT(*) as total
            FROM seguimiento_user s
            INNER JOIN usuarios u ON u.idusuarios = s.seg_idusuario
            WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6
              AND s.seg_motivo = ?
              AND DATE(s.seg_fechaalcohol) BETWEEN ? AND ?";

        $params = [$motivo, $fecha_inicio, $fecha_fin];
        $types = "sss";

        if (!empty($filtros['sede']) && $filtros['sede'] > 0) {
            $sql .= " AND u.usu_idsede = ?";
            $params[] = $filtros['sede'];
            $types .= "i";
        }
        if (!empty($filtros['operario']) && $filtros['operario'] > 0) {
            $sql .= " AND u.idusuarios = ?";
            $params[] = $filtros['operario'];
            $types .= "i";
        }
        if (!empty($filtros['tipo_contrato']) && $filtros['tipo_contrato'] !== '0') {
            $sql .= " AND u.usu_tipocontrato = ?";
            $params[] = $filtros['tipo_contrato'];
            $types .= "s";
        }
        if (!empty($search)) {
            $sql .= " AND (u.usu_nombre LIKE ? OR u.usu_identificacion LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $types .= "ss";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Obtiene datos cuando HAY filtro de motivo (solo registros de seguimiento que cumplen el motivo)
     */
    private function getRegistrosConMotivo($start, $length, $filtros, $search, $orderColumn, $orderDir)
    {
        $fecha_inicio = $filtros['fecha_inicio'] ?? date('Y-m-d');
        $fecha_fin = $filtros['fecha_fin'] ?? date('Y-m-d');
        $motivo = $filtros['motivo'];

        // Consulta sin LIMIT, obtener todos los registros
        $sql = "SELECT
                u.idusuarios,
                u.usu_nombre,
                u.usu_identificacion,
                u.usu_tipocontrato,
                u.usu_fechalicencia,
                u.usu_idsede,
                s.idseguimiento_user,
                s.seg_fechaingreso,
                s.seg_motivo,
                s.seg_descr,
                s.seg_alcohol,
                s.seg_horaalmuerzo,
                s.seg_horaregreso,
                s.seg_horaoficina,
                s.seg_fechafinalizo,
                s.seg_compañero,
                s.seg_idzona,
                DATE(s.seg_fechaalcohol) as fecha,
                p.idpreoperacinal,
                p.prefechaingreso,
                p.preestado,
                p.prevehiculo,
                h.hoj_cuen,
                h.hoj_arl,
                v.veh_placa,
                v.veh_fechaseguro,
                v.veh_fechategnomecanica,
                v.veh_aceitekil,
                v.veh_kmalcambaceite,
                v.veh_kilactual,
                z.zon_nombre as zona_nombre,
                comp.usu_nombre as companero_nombre
            FROM seguimiento_user s
            INNER JOIN usuarios u ON u.idusuarios = s.seg_idusuario
            LEFT JOIN `pre-operacional` p ON p.preidusuario = u.idusuarios AND DATE(p.prefechaingreso) = DATE(s.seg_fechaalcohol)
            LEFT JOIN hojadevida h ON h.hoj_cedula = u.usu_identificacion AND h.hoj_estado = 'Activo'
            LEFT JOIN vehiculos v ON v.idvehiculos = p.prevehiculo
            LEFT JOIN zonatrabajo z ON z.idzonatrabajo = s.seg_idzona
            LEFT JOIN usuarios comp ON comp.idusuarios = s.seg_compañero
            WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6
              AND s.seg_motivo = ?
              AND DATE(s.seg_fechaalcohol) BETWEEN ? AND ?";

        $params = [$motivo, $fecha_inicio, $fecha_fin];
        $types = "sss";

        if (!empty($filtros['sede']) && $filtros['sede'] > 0) {
            $sql .= " AND u.usu_idsede = ?";
            $params[] = $filtros['sede'];
            $types .= "i";
        }
        if (!empty($filtros['operario']) && $filtros['operario'] > 0) {
            $sql .= " AND u.idusuarios = ?";
            $params[] = $filtros['operario'];
            $types .= "i";
        }
        if (!empty($filtros['tipo_contrato']) && $filtros['tipo_contrato'] !== '0') {
            $sql .= " AND u.usu_tipocontrato = ?";
            $params[] = $filtros['tipo_contrato'];
            $types .= "s";
        }
        if (!empty($search)) {
            $sql .= " AND (u.usu_nombre LIKE ? OR u.usu_identificacion LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $types .= "ss";
        }

        // Sin ORDER BY aún, lo haremos en PHP
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Enriquecer cada fila
        foreach ($rows as &$row) {
            $row = $this->enriquecerFila($row);
        }

        // Ordenar globalmente
        usort($rows, function ($a, $b) {
            $prioridadA = $this->getPrioridadOrden($a);
            $prioridadB = $this->getPrioridadOrden($b);
            if ($prioridadA != $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }
            return strcmp($a['usu_nombre'], $b['usu_nombre']);
        });

        // Paginar
        $paginated = array_slice($rows, $start, $length);
        return $paginated;
    }

    // Función para guardar imagen (debes adaptarla a tu sistema)
    private function guardarImagen($archivo, $tabla, $idviene, $version = 1)
    {
        return $this->subirArchivo($archivo, $tabla, $idviene, $version);
    }

    private function subirArchivo($archivo, $tabla, $idviene, $version = 1)
    {
        if (!$archivo || $archivo['error'] != UPLOAD_ERR_OK) {
            return false;
        }
        // Crear nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre = uniqid() . '_' . time() . '.' . $extension;
        // Carpeta específica para la tabla
        $carpeta = $this->uploadPath . $tabla . '/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $ruta = $carpeta . $nombre;
        if (move_uploaded_file($archivo['tmp_name'], $ruta)) {
            // Guardar en la tabla documentos
            $sql = "INSERT INTO documentos (doc_fecha, doc_nombre, doc_ruta, doc_tabla, doc_idviene, doc_version) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $fecha = date('Y-m-d');
            $stmt->bind_param("ssssii", $fecha, $archivo['name'], $ruta, $tabla, $idviene, $version);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Obtiene detalles del seguimiento y preoperacional para mostrar antes de eliminar
     */
    public function getDetallesParaEliminar($idCombinado)
    {
        $partes = explode('_', $idCombinado);
        $idPre = $partes[0] ?? 0;
        $idSeg = $partes[1] ?? 0;

        $detalles = [
            'preoperacional' => null,
            'seguimiento' => null,
            'usuario' => null,
            'fecha' => null,
            'motivo' => null
        ];

        if ($idPre > 0) {
            $sql = "SELECT p.idpreoperacinal, p.preidusuario, p.prefechaingreso, p.preestado,
                           u.usu_nombre
                    FROM `pre-operacional` p
                    LEFT JOIN usuarios u ON u.idusuarios = p.preidusuario
                    WHERE p.idpreoperacinal = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $idPre);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $detalles['preoperacional'] = $row;
                $detalles['usuario'] = $row['usu_nombre'];
                $detalles['fecha'] = $row['prefechaingreso'];
            }
        }

        if ($idSeg > 0) {
            $sql = "SELECT s.idseguimiento_user, s.seg_idusuario, s.seg_fechaingreso, s.seg_motivo, s.seg_descr,
                           u.usu_nombre
                    FROM seguimiento_user s
                    LEFT JOIN usuarios u ON u.idusuarios = s.seg_idusuario
                    WHERE s.idseguimiento_user = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $idSeg);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $detalles['seguimiento'] = $row;
                if (empty($detalles['usuario'])) {
                    $detalles['usuario'] = $row['usu_nombre'];
                }
                if (empty($detalles['fecha'])) {
                    $detalles['fecha'] = $row['seg_fechaingreso'];
                }
                $detalles['motivo'] = $row['seg_motivo'];
            }
        }

        return $detalles;
    }

    /**
     * Elimina un registro de seguimiento y su preoperacional asociado
     */
    public function eliminarSeguimiento($idCombinado)
    {
        $partes = explode('_', $idCombinado);
        $idPre = $partes[0] ?? 0;
        $idSeg = $partes[1] ?? 0;

        $this->db->begin_transaction();
        try {
            if ($idPre > 0) {
                $sql = "DELETE FROM `pre-operacional` WHERE idpreoperacinal = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $idPre);
                $stmt->execute();
            }
            if ($idSeg > 0) {
                $sql = "DELETE FROM seguimiento_user WHERE idseguimiento_user = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $idSeg);
                $stmt->execute();
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error al eliminar seguimiento: " . $e->getMessage());
            return false;
        }
    }
}