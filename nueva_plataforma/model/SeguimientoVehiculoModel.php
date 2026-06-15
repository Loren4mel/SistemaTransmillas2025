<?php

require_once "../config/database.php";

/**
 * Modelo para la gestión de seguimiento de vehículos.
 *
 * Maneja todas las operaciones de base de datos relacionadas con el seguimiento
 * de vehículos: consulta, estado general, kilometraje, eventos SST/mantenimiento,
 * comparendos, y preoperacionales vinculados.
 */
class SeguimientoVehiculoModel
{
    /** @var mysqli Conexión a la base de datos */
    private $db;

    /**
     * Constructor. Inicializa la conexión a la base de datos.
     */
    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Obtiene todas las sedes que tienen vehículos asociados.
     *
     * @return array Lista de sedes con id y nombre.
     */
    public function getSedes(): array
    {
        $sql = "SELECT DISTINCT s.idsedes, s.sed_nombre
                FROM sedes s
                INNER JOIN usuarios u ON u.usu_idsede = s.idsedes
                INNER JOIN vehiculos v ON v.idvehiculos = u.usu_vehiculo
                WHERE u.usu_estado = 1 AND u.usu_filtro = 1
                ORDER BY s.sed_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Obtiene conductores activos (con vehículo asignado) para filtros/selects.
     *
     * @param int $idsede (opcional) Filtrar por sede
     * @return array
     */
    public function getConductores(int $idsede = 0): array
    {
        $sql = "SELECT DISTINCT u.idusuarios, u.usu_nombre
                FROM usuarios u
                INNER JOIN vehiculos v ON v.idvehiculos = u.usu_vehiculo
                WHERE u.usu_estado = 1 AND u.usu_filtro = 1";
        $params = [];
        $types = "";
        if ($idsede > 0) {
            $sql .= " AND u.usu_idsede = ?";
            $params[] = $idsede;
            $types .= "i";
        }
        $sql .= " ORDER BY u.usu_nombre";
        $stmt = $this->db->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obtiene los tipos de propiedad de vehículo disponibles.
     *
     * @return array
     */
    public function getTiposPropiedad(): array
    {
        $sql = "SELECT DISTINCT veh_propiedad FROM vehiculos WHERE veh_propiedad IS NOT NULL AND veh_propiedad != '' ORDER BY veh_propiedad";
        $result = $this->db->query($sql);
        if (!$result) return [];
        $tipos = [];
        while ($row = $result->fetch_assoc()) {
            $tipos[] = ['id' => $row['veh_propiedad'], 'nombre' => $row['veh_propiedad']];
        }
        return $tipos;
    }

    // ==================== DATATABLE SERVER-SIDE ====================

    /**
     * Total de vehículos (sin filtros).
     *
     * @return int
     */
    public function getTotalVehiculos(): int
    {
        $sql = "SELECT COUNT(*) as total FROM vehiculos";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Total de vehículos después de aplicar filtros.
     *
     * @param array $filtros
     * @param string $search
     * @return int
     */
    public function getTotalFiltrados(array $filtros, string $search = ''): int
    {
        $sql = "SELECT COUNT(DISTINCT v.idvehiculos) as total
                FROM vehiculos v
                LEFT JOIN usuarios u ON u.usu_vehiculo = v.idvehiculos AND u.usu_estado = 1
                LEFT JOIN sedes s ON s.idsedes = u.usu_idsede
                WHERE 1=1";
        $params = [];
        $types = "";
        $this->buildFiltroWhere($sql, $params, $types, $filtros, $search);
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Obtiene los registros de vehículos para DataTable (server-side).
     *
     * @param int $start
     * @param int $length
     * @param array $filtros
     * @param string $search
     * @return array
     */
    public function getVehiculosDataTable(int $start, int $length, array $filtros, string $search = ''): array
    {
        $sql = "SELECT v.idvehiculos, v.veh_tipo, v.veh_placa, v.veh_marca, v.veh_modelo,
                    v.veh_fechaseguro, v.veh_fechategnomecanica, v.veh_fechamantenimiento,
                    v.veh_kilactual, v.veh_aceitekil, v.veh_dueño, v.veh_estado,
                    v.veh_chasis, v.veh_tipov, v.veh_cilidraje, v.veh_motor, v.veh_color,
                    v.veh_usuve, v.veh_observaciones, v.veh_calkmcambioaceite,
                    v.veh_restankmaceite, v.veh_faltaparacambioaceite, v.veh_kmalcambaceite,
                    v.veh_propiedad, v.veh_equipo_carretera,
                    u.idusuarios as conductor_id, u.usu_nombre as conductor_nombre,
                    u.usu_identificacion as conductor_identificacion,
                    u.usu_idsede as conductor_sede,
                    s.sed_nombre as sede_nombre
                FROM vehiculos v
                LEFT JOIN usuarios u ON u.usu_vehiculo = v.idvehiculos AND u.usu_estado = 1
                LEFT JOIN sedes s ON s.idsedes = u.usu_idsede
                WHERE 1=1";
        $params = [];
        $types = "";
        $this->buildFiltroWhere($sql, $params, $types, $filtros, $search);

        $sql .= " ORDER BY FIELD(v.idvehiculos IN (
                    SELECT sv.id_vehiculo FROM seguimiento_vehiculo sv
                    INNER JOIN (
                        SELECT sv2.id_vehiculo, MAX(sv2.fecha_registro) as max_fecha
                        FROM seguimiento_vehiculo sv2 GROUP BY sv2.id_vehiculo
                    ) sv3 ON sv.id_vehiculo = sv3.id_vehiculo AND sv.fecha_registro = sv3.max_fecha
                    WHERE sv.estado_general = 'FUERA_DE_SERVICIO'
                ), 1, 0) DESC,
                FIELD(v.idvehiculos IN (
                    SELECT sv.id_vehiculo FROM seguimiento_vehiculo sv
                    INNER JOIN (
                        SELECT sv2.id_vehiculo, MAX(sv2.fecha_registro) as max_fecha
                        FROM seguimiento_vehiculo sv2 GROUP BY sv2.id_vehiculo
                    ) sv3 ON sv.id_vehiculo = sv3.id_vehiculo AND sv.fecha_registro = sv3.max_fecha
                    WHERE sv.estado_general = 'CON_NOVEDADES'
                ), 1, 0) DESC,
                v.veh_placa ASC";
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $length;
        $params[] = $start;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $vehiculos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Obtener IDs de vehículos para precargas
        $ids = array_column($vehiculos, 'idvehiculos');
        if (empty($ids)) return [];

        // Precargar datos relacionados
        $eventosIndex = $this->cargarUltimosEventos($ids);
        $preopsIndex = $this->cargarUltimosPreoperacionales($ids);
        $aceitesIndex = $this->cargarUltimosAceites($ids);
        $comparendosIndex = $this->cargarComparendosPendientes($ids);
        $hojavidaIndex = $this->cargarHojavidaConductores($vehiculos);

        // Enriquecer y procesar filas
        foreach ($vehiculos as &$row) {
            $id = $row['idvehiculos'];
            $evento = $eventosIndex[$id] ?? null;
            $preop = $preopsIndex[$id] ?? null;
            $aceite = $aceitesIndex[(string)$id] ?? null;
            $comparendos = $comparendosIndex[$id] ?? 0;

            $row = $this->enriquecerFila($row, $evento, $preop, $aceite, $comparendos, $hojavidaIndex);
        }

        return $vehiculos;
    }

    /**
     * Construye la cláusula WHERE para filtros de vehículos.
     */
    private function buildFiltroWhere(string &$sql, array &$params, string &$types, array $filtros, string $search): void
    {
        // Filtro de propiedad (default: Empresa si no se especifica)
        $propiedad = $filtros['propiedad'] ?? 'Empresa';
        if ($propiedad !== 'Todos' && $propiedad !== '') {
            $sql .= " AND v.veh_propiedad = ?";
            $params[] = $propiedad;
            $types .= "s";
        }

        // Filtro de estado general
        if (!empty($filtros['estado_general'])) {
            $sql .= " AND v.idvehiculos IN (
                SELECT sv.id_vehiculo FROM seguimiento_vehiculo sv
                INNER JOIN (
                    SELECT id_vehiculo, MAX(fecha_registro) as max_fecha
                    FROM seguimiento_vehiculo GROUP BY id_vehiculo
                ) sv2 ON sv.id_vehiculo = sv2.id_vehiculo AND sv.fecha_registro = sv2.max_fecha
                WHERE sv.estado_general = ?
            )";
            $params[] = $filtros['estado_general'];
            $types .= "s";
        }

        // Filtro de sede
        if (!empty($filtros['sede']) && $filtros['sede'] > 0) {
            $sql .= " AND u.usu_idsede = ?";
            $params[] = $filtros['sede'];
            $types .= "i";
        }

        // Filtro de conductor
        if (!empty($filtros['conductor']) && $filtros['conductor'] > 0) {
            $sql .= " AND u.idusuarios = ?";
            $params[] = $filtros['conductor'];
            $types .= "i";
        }

        // Búsqueda por placa
        if (!empty($search)) {
            $sql .= " AND (v.veh_placa LIKE ? OR v.veh_marca LIKE ? OR v.veh_tipo LIKE ?)";
            $like = "%$search%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= "sss";
        }
    }

    /**
     * Carga los últimos eventos de seguimiento_vehiculo para un conjunto de IDs.
     */
    private function cargarUltimosEventos(array $ids): array
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT sv.id_vehiculo, sv.estado_general, sv.tipo_evento,
                       sv.kilometraje, sv.fecha_registro, sv.observaciones
                FROM seguimiento_vehiculo sv
                INNER JOIN (
                    SELECT id_vehiculo, MAX(fecha_registro) as max_fecha
                    FROM seguimiento_vehiculo
                    WHERE id_vehiculo IN ($placeholders)
                    GROUP BY id_vehiculo
                ) sv2 ON sv.id_vehiculo = sv2.id_vehiculo AND sv.fecha_registro = sv2.max_fecha";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("cargarUltimosEventos prepare error: " . $this->db->error);
            return [];
        }
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("cargarUltimosEventos execute error: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        $index = [];
        while ($row = $result->fetch_assoc()) {
            $index[$row['id_vehiculo']] = $row;
        }
        return $index;
    }

    /**
     * Carga los últimos preoperacionales para un conjunto de IDs de vehículo.
     */
    private function cargarUltimosPreoperacionales(array $ids): array
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT po1.prevehiculo, po1.idpreoperacinal, po1.preestado,
                       po1.prefechaingreso, po1.preidusuario, po1.pre_kilrecorridos
                FROM `pre-operacional` po1
                INNER JOIN (
                    SELECT prevehiculo, MAX(prefechaingreso) as max_fecha
                    FROM `pre-operacional`
                    WHERE prevehiculo IN ($placeholders) AND prevehiculo IS NOT NULL AND prevehiculo > 0
                    GROUP BY prevehiculo
                ) po2 ON po1.prevehiculo = po2.prevehiculo AND po1.prefechaingreso = po2.max_fecha";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("cargarUltimosPreoperacionales prepare error: " . $this->db->error);
            return [];
        }
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("cargarUltimosPreoperacionales execute error: " . $stmt->error . " — SQL: " . $sql . " — IDs: " . json_encode($ids));
            return [];
        }
        $result = $stmt->get_result();
        $index = [];
        while ($row = $result->fetch_assoc()) {
            $index[$row['prevehiculo']] = $row;
        }
        return $index;
    }

    /**
     * Carga los últimos cambios de aceite para un conjunto de IDs de vehículo.
     */
    private function cargarUltimosAceites(array $ids): array
    {
        if (empty($ids)) return [];
        $strIds = array_map('strval', $ids);
        $placeholders = implode(',', array_fill(0, count($strIds), '?'));
        $sql = "SELECT a1.ace_idvehiculo, a1.ace_fechacambio, a1.ace_kiloalcambio
                FROM aceite a1
                INNER JOIN (
                    SELECT ace_idvehiculo, MAX(ace_fechacambio) as max_fecha
                    FROM aceite
                    WHERE ace_idvehiculo IN ($placeholders)
                    GROUP BY ace_idvehiculo
                ) a2 ON a1.ace_idvehiculo = a2.ace_idvehiculo AND a1.ace_fechacambio = a2.max_fecha";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("cargarUltimosAceites prepare error: " . $this->db->error);
            return [];
        }
        $stmt->bind_param(str_repeat('s', count($strIds)), ...$strIds);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("cargarUltimosAceites execute error: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        $index = [];
        while ($row = $result->fetch_assoc()) {
            $index[$row['ace_idvehiculo']] = $row;
        }
        return $index;
    }

    /**
     * Carga el conteo de comparendos pendientes por vehículo.
     */
    private function cargarComparendosPendientes(array $ids): array
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT com_vehiculo_id, COUNT(*) as cnt
                FROM comparendos
                WHERE com_vehiculo_id IN ($placeholders) AND com_estado = 'Pendiente'
                GROUP BY com_vehiculo_id";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("cargarComparendosPendientes prepare error: " . $this->db->error);
            return [];
        }
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("cargarComparendosPendientes execute error: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        $index = [];
        while ($row = $result->fetch_assoc()) {
            $index[$row['com_vehiculo_id']] = $row['cnt'];
        }
        return $index;
    }

    /**
     * Carga datos de hoja de vida de los conductores.
     */
    private function cargarHojavidaConductores(array $vehiculos): array
    {
        $cedulas = [];
        foreach ($vehiculos as $v) {
            if (!empty($v['conductor_identificacion'])) {
                $cedulas[] = $v['conductor_identificacion'];
            }
        }
        if (empty($cedulas)) return [];
        $cedulasUnicas = array_unique($cedulas);
        $placeholders = implode(',', array_fill(0, count($cedulasUnicas), '?'));
        $sql = "SELECT hoj_cedula,
                       (hoj_cuen IS NULL OR hoj_cuen = '') as falta_cuenta,
                       (hoj_arl IS NULL OR hoj_arl = '') as falta_arl
                FROM hojadevida
                WHERE hoj_cedula IN ($placeholders) AND hoj_estado = 'Activo'";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("cargarHojavidaConductores prepare error: " . $this->db->error);
            return [];
        }
        $stmt->bind_param(str_repeat('s', count($cedulasUnicas)), ...$cedulasUnicas);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log("cargarHojavidaConductores execute error: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        $index = [];
        while ($row = $result->fetch_assoc()) {
            $index[$row['hoj_cedula']] = $row;
        }
        return $index;
    }

    // ==================== ENRIQUECIMIENTO DE FILAS ====================

    /**
     * Enriquece una fila de vehículo con datos calculados y HTML para la vista.
     */
    private function enriquecerFila(array $row, ?array $evento, ?array $preop, ?array $aceite, int $comparendos, array $hojavidaIndex): array
    {
        $hoy = date('Y-m-d');

        // Estado general: desde el último evento, o 'OPTIMO' por defecto
        $estadoGeneral = $evento['estado_general'] ?? 'OPTIMO';
        $row['estado_general'] = $estadoGeneral;
        $row['estado_general_badge'] = $this->badgeEstadoGeneral($estadoGeneral);

        // Último evento
        $row['ultimo_evento_tipo'] = $evento['tipo_evento'] ?? 'Sin eventos';
        $row['ultimo_evento_fecha'] = isset($evento['fecha_registro'])
            ? date('d-m-Y H:i', strtotime($evento['fecha_registro'])) : '-';
        $row['ultimo_evento_obs'] = $evento['observaciones'] ?? '';

        // Kilometraje actual
        $kmActual = !empty($evento['kilometraje']) ? $evento['kilometraje'] : ($row['veh_kilactual'] ?? '0');
        $row['kilometraje_actual'] = $kmActual;
        $row['kilometraje_actual_fmt'] = number_format((int)$kmActual, 0, ',', '.') . ' km';

        // Último preoperacional
        $row['ultimo_preop_id'] = $preop['idpreoperacinal'] ?? null;
        $row['ultimo_preop_estado'] = $preop['preestado'] ?? 'Sin registro';
        $row['ultimo_preop_fecha'] = isset($preop['prefechaingreso'])
            ? date('d-m-Y H:i', strtotime($preop['prefechaingreso'])) : '-';
        $row['ultimo_preop_link'] = $this->linkPreoperacional($row, $preop);

        // Alertas de vencimiento
        $row['alerta_soat'] = $this->formatoFechaConAlerta($row['veh_fechaseguro'] ?? null, $hoy);
        $row['alerta_tecno'] = $this->formatoFechaConAlerta($row['veh_fechategnomecanica'] ?? null, $hoy);
        $row['alerta_mantenimiento'] = $this->formatoFechaConAlerta($row['veh_fechamantenimiento'] ?? null, $hoy);

        // Alerta combinada (dot)
        $row['alerta_html'] = $this->generarAlertaCombinada($row, $hoy);

        // Cambio de aceite
        $row['cambio_aceite_html'] = $this->formatoCambioAceite($row, $aceite);

        // Comparendos
        $row['comparendos_pendientes'] = $comparendos;
        $row['comparendos_html'] = $comparendos > 0
            ? "<span class='badge bg-danger'>$comparendos pend.</span>"
            : "<span class='badge bg-success'>0</span>";

        // Conductor
        $row['conductor_nombre'] = $row['conductor_nombre'] ?? 'Sin asignar';

        // Hoja de vida del conductor
        $cedula = $row['conductor_identificacion'] ?? '';
        $hv = $hojavidaIndex[$cedula] ?? null;
        $row['conductor_falta_arl'] = $hv['falta_arl'] ?? 0;
        $row['conductor_falta_cuenta'] = $hv['falta_cuenta'] ?? 0;

        // Color de fila
        $row['row_color'] = $this->determinarColorFila($row, $estadoGeneral);
        $row['row_text_color'] = ($row['row_color'] === '#922B21') ? '#FFFFFF' : '#000000';

        // Acciones
        $row['acciones_html'] = $this->generarAccionesHTML($row);

        // Propiedad
        $row['propiedad'] = $row['veh_propiedad'] ?? 'Sin definir';

        return $row;
    }

    /**
     * Genera badge HTML para el estado general.
     */
    private function badgeEstadoGeneral(string $estado): string
    {
        switch ($estado) {
            case 'OPTIMO':
                return '<span class="badge bg-success">ÓPTIMO</span>';
            case 'CON_NOVEDADES':
                return '<span class="badge bg-warning text-dark">CON NOVEDADES</span>';
            case 'FUERA_DE_SERVICIO':
                return '<span class="badge bg-danger">FUERA DE SERVICIO</span>';
            default:
                return '<span class="badge bg-secondary">' . htmlspecialchars($estado) . '</span>';
        }
    }

    /**
     * Genera el link al preoperacional.
     */
    private function linkPreoperacional(array $row, ?array $preop): string
    {
        if (empty($preop) || empty($preop['idpreoperacinal'])) {
            return 'Sin registro';
        }
        $estado = $preop['preestado'];
        $idPre = $preop['idpreoperacinal'];
        $fechaPreop = !empty($preop['prefechaingreso']) ? date('Y-m-d', strtotime($preop['prefechaingreso'])) : date('Y-m-d');
        $url = "../controller/PreoperacionalController.php?preoperacional=validarpreoperacional&idpre=$idPre&iduser=" . ($preop['preidusuario'] ?? 0) . "&fecha=$fechaPreop&idvehiculo=" . $row['idvehiculos'] . "&param4=ingresado&param5=valida";
        return "<a href='#' onclick='abrirValidacionPreopVehiculo(\"$url\")'>$estado</a>";
    }

    /**
     * Formatea una fecha con alerta visual de vencimiento.
     */
    private function formatoFechaConAlerta(?string $fecha, string $hoy): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') return '<span class="text-muted">-</span>';
        $fechaFmt = date('d-m-Y', strtotime($fecha));
        $dias = $this->diasHasta($hoy, $fecha);
        if ($dias === null) return $fechaFmt;
        if ($dias < 0) {
            return "<span style='background-color:#F44336; color:white; padding:2px 6px; border-radius:3px;'>$fechaFmt (vencido)</span>";
        }
        if ($dias <= 7) {
            return "<span style='background-color:#F44336; color:white; padding:2px 6px; border-radius:3px;'>$fechaFmt ($dias d)</span>";
        }
        if ($dias <= 30) {
            return "<span style='background-color:#FF9800; color:white; padding:2px 6px; border-radius:3px;'>$fechaFmt ($dias d)</span>";
        }
        return $fechaFmt;
    }

    /**
     * Genera el dot de alerta combinada (SOAT, Tecno, Mantenimiento).
     */
    private function generarAlertaCombinada(array $row, string $hoy): string
    {
        $fechas = [
            'SOAT' => $row['veh_fechaseguro'] ?? null,
            'Tecno' => $row['veh_fechategnomecanica'] ?? null,
            'Mantto' => $row['veh_fechamantenimiento'] ?? null,
        ];

        $dropdownItems = [];
        $maxSeverity = 0;
        $maxNombre = '';

        foreach ($fechas as $nombre => $fecha) {
            if (empty($fecha) || $fecha === '0000-00-00') continue;
            $dias = $this->diasHasta($hoy, $fecha);
            if ($dias === null) continue;

            $severity = 0;
            $color = '#555';
            if ($dias < 0) { $severity = 3; $color = '#F44336'; $texto = "$nombre: expirado hace " . abs($dias) . "d"; }
            elseif ($dias <= 7) { $severity = 2; $color = '#F44336'; $texto = "$nombre: expira en $dias d"; }
            elseif ($dias <= 30) { $severity = 1; $color = '#FF9800'; $texto = "$nombre: expira en $dias d"; }
            else { $texto = "$nombre: " . date('d-m-Y', strtotime($fecha)) . " ($dias d)"; }

            $dropdownItems[] = "<li style='color:$color; padding:2px 0;'>" . htmlspecialchars($texto) . "</li>";

            if ($severity > $maxSeverity) {
                $maxSeverity = $severity;
                $maxNombre = $nombre;
            }
        }

        if (empty($dropdownItems)) return '';

        $colorClass = '';
        if ($maxSeverity >= 3) $colorClass = 'warning-red';
        elseif ($maxSeverity >= 2) $colorClass = 'warning-red';
        elseif ($maxSeverity >= 1) $colorClass = 'warning-orange';
        else return ''; // Sin alertas, no mostrar dot

        $dot = '<span class="warning-dot ' . $colorClass . '" title="' . htmlspecialchars($maxNombre) . '"></span>';
        $dropdown = '<div class="alerta-dropdown"><ul style="list-style:none; margin:0; padding:0;">'
            . implode('', $dropdownItems) . '</ul></div>';
        return '<div class="alerta-wrapper">' . $dot . $dropdown . '</div>';
    }

    /**
     * Formatea el estado del cambio de aceite.
     */
    private function formatoCambioAceite(array $row, ?array $aceite): string
    {
        $kmActual = (int)($row['kilometraje_actual'] ?? $row['veh_kilactual'] ?? 0);
        $aceiteKil = (int)($row['veh_aceitekil'] ?? 0);
        $kmCambioAceite = (int)($row['veh_kmalcambaceite'] ?? 0);

        if ($aceiteKil <= 0) return '<span class="text-muted">Sin datos</span>';

        if ($kmCambioAceite <= 0) {
            return '<span class="text-muted">' . number_format($kmActual, 0, ',', '.') . ' km actuales</span>';
        }

        $kmRecorridos = $kmActual - $kmCambioAceite;
        $restantes = $aceiteKil - $kmRecorridos;

        if ($restantes <= 0) {
            return "<span style='background-color:#FF9800; color:white; padding:2px 6px; border-radius:3px;'>Cambie aceite, excede " . number_format(abs($restantes), 0, ',', '.') . " km</span>";
        }
        return number_format($restantes, 0, ',', '.') . " km restantes de " . number_format($aceiteKil, 0, ',', '.');
    }

    /**
     * Determina el color de fondo de la fila según estado y alertas.
     */
    private function determinarColorFila(array $row, string $estadoGeneral): string
    {
        if ($estadoGeneral === 'FUERA_DE_SERVICIO') return '#922B21';
        if ($estadoGeneral === 'CON_NOVEDADES') return '#FEF5E7';

        // Verificar vencimientos
        $vencido = false;
        foreach (['veh_fechaseguro', 'veh_fechategnomecanica', 'veh_fechamantenimiento'] as $campo) {
            $fecha = $row[$campo] ?? null;
            if (!empty($fecha) && $fecha !== '0000-00-00') {
                $dias = $this->diasHasta(date('Y-m-d'), $fecha);
                if ($dias !== null && $dias < 0) { $vencido = true; break; }
            }
        }
        if ($vencido) return '#922B21';

        // Alternar blanco/gris
        return ($row['idvehiculos'] % 2 == 0) ? '#FFFFFF' : '#EFEFEF';
    }

    /**
     * Genera los botones de acción para cada fila.
     */
    private function generarAccionesHTML(array $row): string
    {
        $id = $row['idvehiculos'];
        $html = '';

        // Cambiar estado (solo admin)
        $html .= "<button class='btn btn-sm btn-outline-warning me-1' onclick='abrirCambiarEstado($id)' title='Cambiar estado'><i class='fas fa-edit'></i></button>";

        // Registrar evento
        $html .= "<button class='btn btn-sm btn-outline-info me-1' onclick='abrirRegistroEvento($id)' title='Registrar evento'><i class='fas fa-plus-circle'></i></button>";

        // Historial kilómetros
        $html .= "<button class='btn btn-sm btn-outline-secondary me-1' onclick='abrirHistorialKm($id)' title='Historial km'><i class='fas fa-tachometer-alt'></i></button>";

        // Comparendos
        $html .= "<button class='btn btn-sm btn-outline-danger me-1' onclick='abrirComparendos($id)' title='Comparendos'><i class='fas fa-ticket-alt'></i></button>";

        // Detalle
        $html .= "<button class='btn btn-sm btn-outline-primary' onclick='abrirDetalleVehiculo($id)' title='Ver detalle'><i class='fas fa-info-circle'></i></button>";

        return $html;
    }

    /**
     * Calcula días entre dos fechas.
     */
    private function diasHasta(string $hoy, ?string $fecha): ?int
    {
        if (!$fecha || $fecha === '0000-00-00') return null;
        $hoyTs = strtotime($hoy);
        $fechaTs = strtotime($fecha);
        return (int) round(($fechaTs - $hoyTs) / 86400);
    }

    // ==================== OPERACIONES CRUD / ACCIONES ====================

    /**
     * Obtiene un vehículo por ID con todos sus datos.
     *
     * @param int $id
     * @return array|null
     */
    public function getVehiculoById(int $id): ?array
    {
        $sql = "SELECT v.*, u.idusuarios as conductor_id, u.usu_nombre as conductor_nombre,
                       s.sed_nombre as sede_conductor
                FROM vehiculos v
                LEFT JOIN usuarios u ON u.usu_vehiculo = v.idvehiculos AND u.usu_estado = 1
                LEFT JOIN sedes s ON s.idsedes = u.usu_idsede
                WHERE v.idvehiculos = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Obtiene el historial de kilometraje de un vehículo a lo largo del tiempo.
     *
     * @param int $idVehiculo
     * @return array
     */
    public function getHistorialKilometraje(int $idVehiculo): array
    {
        $historial = [];

        // 1. Eventos de seguimiento_vehiculo
        $sql = "SELECT fecha_registro as fecha, 'Evento' as fuente, kilometraje,
                       tipo_evento as detalle
                FROM seguimiento_vehiculo
                WHERE id_vehiculo = ? AND kilometraje > 0
                ORDER BY fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVehiculo);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['kilometraje'] = (int) $row['kilometraje'];
            $historial[] = $row;
        }

        // 2. Preoperacionales con kilometraje
        $sql = "SELECT prefechaingreso as fecha, 'Preoperacional' as fuente,
                       CAST(pre_kilrecorridos AS UNSIGNED) as kilometraje,
                       preestado as detalle
                FROM `pre-operacional`
                WHERE prevehiculo = ? AND pre_kilrecorridos IS NOT NULL AND pre_kilrecorridos != ''
                ORDER BY prefechaingreso DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVehiculo);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['kilometraje'] = (int) $row['kilometraje'];
            if ($row['kilometraje'] > 0) {
                $historial[] = $row;
            }
        }

        // 3. Cambios de aceite
        $sql = "SELECT ace_fechacambio as fecha, 'Cambio Aceite' as fuente,
                       CAST(ace_kiloalcambio AS UNSIGNED) as kilometraje,
                       '' as detalle
                FROM aceite
                WHERE ace_idvehiculo = ?
                ORDER BY ace_fechacambio DESC";
        $stmt = $this->db->prepare($sql);
        $idStr = (string) $idVehiculo;
        $stmt->bind_param("s", $idStr);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['kilometraje'] = (int) $row['kilometraje'];
            if ($row['kilometraje'] > 0) {
                $historial[] = $row;
            }
        }

        // Ordenar por fecha descendente
        usort($historial, function ($a, $b) {
            return strtotime($b['fecha'] ?? '') <=> strtotime($a['fecha'] ?? '');
        });

        return $historial;
    }

    /**
     * Obtiene los comparendos de un vehículo.
     *
     * @param int $idVehiculo
     * @return array
     */
    public function getComparendos(int $idVehiculo): array
    {
        $sql = "SELECT c.*, u.usu_nombre as operador_nombre
                FROM comparendos c
                LEFT JOIN usuarios u ON u.idusuarios = c.com_operador_id
                WHERE c.com_vehiculo_id = ?
                ORDER BY c.com_fecha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVehiculo);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cambia el estado general de un vehículo (registra un nuevo evento).
     *
     * @param int $idVehiculo
     * @param string $nuevoEstado OPTIMO, CON_NOVEDADES, FUERA_DE_SERVICIO
     * @param string $observaciones
     * @param int $idResponsable Usuario que registra
     * @param int $kilometraje
     * @return bool
     */
    public function cambiarEstado(int $idVehiculo, string $nuevoEstado, string $observaciones, int $idResponsable, int $kilometraje = 0): bool
    {
        $sql = "INSERT INTO seguimiento_vehiculo
                (tipo_evento, id_vehiculo, id_responsable, estado_general, kilometraje, observaciones, fecha_registro)
                VALUES ('REVISION_SST', ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiiss", $idVehiculo, $idResponsable, $nuevoEstado, $kilometraje, $observaciones);
        return $stmt->execute();
    }

    /**
     * Registra un nuevo evento en el libro de vida del vehículo.
     *
     * @param array $data
     * @return bool
     */
    public function registrarEvento(array $data): bool
    {
        $sql = "INSERT INTO seguimiento_vehiculo
                (tipo_evento, id_vehiculo, id_conductor, id_responsable, estado_general, kilometraje, observaciones, ubicacion, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "siiisiss",
            $data['tipo_evento'],
            $data['id_vehiculo'],
            $data['id_conductor'],
            $data['id_responsable'],
            $data['estado_general'],
            $data['kilometraje'],
            $data['observaciones'],
            $data['ubicacion']
        );
        return $stmt->execute();
    }

    /**
     * Obtiene el detalle completo de un vehículo para el popup de ficha.
     *
     * @param int $idVehiculo
     * @return array
     */
    public function getDetalleVehiculo(int $idVehiculo): array
    {
        $vehiculo = $this->getVehiculoById($idVehiculo);
        if (!$vehiculo) return [];

        $historialKm = $this->getHistorialKilometraje($idVehiculo);
        $comparendos = $this->getComparendos($idVehiculo);

        // Últimas entregas
        $sql = "SELECT * FROM entregavehiculo WHERE ent_idvehiculo = ? ORDER BY ent_fechaentrega DESC LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idVehiculo);
        $stmt->execute();
        $entregas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Últimas revisiones
        $idStr = (string) $idVehiculo;
        $sqlRev = "SELECT * FROM revisionvehiculo WHERE rev_idvehiculo = ? ORDER BY rev_fecha DESC LIMIT 5";
        $stmtRev = $this->db->prepare($sqlRev);
        $stmtRev->bind_param("s", $idStr);
        $stmtRev->execute();
        $revisiones = $stmtRev->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'vehiculo' => $vehiculo,
            'historial_km' => $historialKm,
            'comparendos' => $comparendos,
            'entregas' => $entregas,
            'revisiones' => $revisiones,
        ];
    }
}
