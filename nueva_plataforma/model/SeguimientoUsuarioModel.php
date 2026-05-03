<?php

require_once "../config/database.php";

/**
 * Modelo para la gestión de seguimiento de usuarios.
 * 
 * Maneja todas las operaciones de base de datos relacionadas con el seguimiento diario
 * de operarios, incluyendo ingresos, preoperacionales, zonas, compañeros, vacaciones,
 * licencias y festivos.
 */
class SeguimientoUsuarioModel
{
    /** @var mysqli Conexión a la base de datos */
    private $db;

    /** @var array Caché para datos de vehículos */
    private $vehiculosCache = [];

    /** @var array Caché para nombres de zonas */
    private $zonasCache = [];

    /** @var array Caché para nombres de compañeros */
    private $companerosCache = [];

    /** @var string Ruta base para subida de archivos */
    private $uploadPath;

    /**
     * Constructor. Inicializa la conexión a la base de datos y la ruta de uploads.
     */
    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->uploadPath = __DIR__ . '/../../uploads/';
    }

    /**
     * Obtiene la instancia de conexión a la base de datos.
     *
     * @return mysqli
     */
    public function getDB(): mysqli
    {
        return $this->db;
    }

    // ==================== MÉTODOS AUXILIARES Y DE CONSULTA ====================

    /**
     * Obtiene todas las sedes que tienen al menos un operario activo (excluyendo rol 6).
     *
     * @return array Lista de sedes con id y nombre.
     */
    public function getSedes(): array
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
     * Devuelve el listado completo de motivos de ingreso (clave => valor).
     *
     * @return array
     */
    public function getMotivosIngresoArray(): array
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
     * Obtiene los motivos de ingreso (para filtros y selects).
     *
     * @param string $tipo (no utilizado actualmente)
     * @return array
     */
    public function getMotivosIngreso(string $tipo = 'todos'): array
    {
        return $this->getMotivosIngresoArray();
    }

    /**
     * Obtiene los tipos de contrato disponibles.
     *
     * @return array
     */
    public function getTiposContrato(): array
    {
        return [
            ['id' => 'Empresa', 'nombre' => 'Empresa'],
            ['id' => 'Prestacion de servicios', 'nombre' => 'Prestación de servicios']
        ];
    }

    /**
     * Obtiene los operarios activos filtrados por sede.
     *
     * @param int $idsede ID de la sede
     * @return array Lista de operarios (id, nombre)
     */
    public function getOperariosPorSede(int $idsede): array
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

    /**
     * Obtiene todos los operarios activos (sin filtrar por sede).
     *
     * @return array
     */
    public function getTodosOperarios(): array
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

    /**
     * Obtiene las zonas de trabajo asociadas a una sede.
     *
     * @param int $idsede
     * @return array
     */
    public function getZonasPorSede(int $idsede): array
    {
        $sql = "SELECT idzonatrabajo, zon_nombre FROM zonatrabajo WHERE inner_sedes = ? ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idsede);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Calcula la deuda total de un operario (préstamos + descuadre - pagos).
     *
     * @param int $idoperario
     * @return float
     */
    public function getDeudaOperario(int $idoperario): float
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
        $prestamoTotal = (float) ($row['prestamos'] ?? 0) + (float) ($row['descuadre'] ?? 0);
        return $prestamoTotal - (float) ($row['pagos'] ?? 0);
    }

    /**
     * Obtiene los motivos de licencia desde la base de datos.
     *
     * @return array
     */
    public function getMotivosLicencia(): array
    {
        $sql = "SELECT idmotivo_ingreso, mot_nombre FROM motivo_ingreso ORDER BY mot_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Obtiene la ruta de un documento por ID (movido desde el controlador).
     *
     * @param int $id
     * @return string|null
     */
    public function getRutaDocumento(int $id): ?string
    {
        $sql = "SELECT doc_ruta FROM documentos WHERE iddocumentos = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row && !empty($row['doc_ruta'])) ? $row['doc_ruta'] : null;
    }

    // ==================== DATATABLE SERVER-SIDE (OPTIMIZADO) ====================

    /**
     * Obtiene el total de registros SIN aplicar filtros (para DataTable).
     * En este contexto, es el número total de usuarios activos.
     *
     * @return int
     */
    public function getTotalRegistros(): int
    {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE usu_estado = 1 AND usu_filtro = 1 AND roles_idroles != 6";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Obtiene el número total de registros después de aplicar filtros (para paginación server-side).
     *
     * @param array $filtros Filtros aplicados (fecha_inicio, fecha_fin, sede, operario, motivo, tipo_contrato)
     * @param string $search Búsqueda global
     * @return int
     */
    public function getTotalFiltrados(array $filtros, string $search = ''): int
    {
        if (!empty($filtros['motivo'])) {
            return $this->getTotalFiltradosConMotivo($filtros, $search);
        }
        return $this->getTotalFiltradosSinMotivo($filtros, $search);
    }

    /**
     * Obtiene los registros paginados para DataTable.
     *
     * @param int $start Inicio
     * @param int $length Cantidad
     * @param array $filtros Filtros
     * @param string $search Búsqueda
     * @param string|null $orderColumn Columna de ordenamiento (no se usa directamente, se ordena por prioridad)
     * @param string $orderDir Dirección (ASC/DESC) (no se usa directamente)
     * @return array
     */
    public function getRegistrosDataTable(int $start, int $length, array $filtros, string $search = '', ?string $orderColumn = null, string $orderDir = 'ASC'): array
    {
        if (!empty($filtros['motivo'])) {
            return $this->getRegistrosConMotivo($start, $length, $filtros, $search);
        } else {
            return $this->getRegistrosSinMotivo($start, $length, $filtros, $search);
        }
    }

    // ==================== MÉTODOS PRIVADOS PARA DATATABLE ====================

    /**
     * Construye la cláusula WHERE común para filtros (sede, operario, tipo_contrato, search).
     * Usa el alias "u" para la tabla usuarios.
     *
     * @param string $sql      SQL por referencia — se le añade la cláusula
     * @param array  $params   Parámetros por referencia
     * @param string $types    Tipos por referencia
     * @param array  $filtros  Filtros del request
     * @param string $search   Búsqueda global
     */
    private function buildFiltroWhere(string &$sql, array &$params, string &$types, array $filtros, string $search): void
    {
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
        if (!empty($filtros['tipo_contrato'])) {
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
    }

    /**
     * Total filtrado cuando NO hay filtro de motivo.
     *
     * @param array $filtros
     * @param string $search
     * @return int
     */
    private function getTotalFiltradosSinMotivo(array $filtros, string $search): int
    {
        $fechaInicio = $filtros['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $filtros['fecha_fin'] ?? date('Y-m-d');

        $sql = "SELECT COUNT(DISTINCT u.idusuarios) as total
                FROM usuarios u
                WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6";
        $params = [];
        $types = "";

        $this->buildFiltroWhere($sql, $params, $types, $filtros, $search);

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $totalUsuarios = (int) ($row['total'] ?? 0);

        $dias = $this->calcularDiasRango($fechaInicio, $fechaFin);
        return $totalUsuarios * $dias;
    }

    /**
     * Total filtrado cuando SÍ hay filtro de motivo.
     *
     * @param array $filtros
     * @param string $search
     * @return int
     */
    private function getTotalFiltradosConMotivo(array $filtros, string $search): int
    {
        $fechaInicio = $filtros['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $filtros['fecha_fin'] ?? date('Y-m-d');
        $motivo = $filtros['motivo'];

        $sql = "SELECT COUNT(*) as total
                FROM seguimiento_user s
                INNER JOIN usuarios u ON u.idusuarios = s.seg_idusuario
                WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6
                  AND s.seg_motivo = ?
                  AND DATE(s.seg_fechaalcohol) BETWEEN ? AND ?";

        $params = [$motivo, $fechaInicio, $fechaFin];
        $types = "sss";

        $this->buildFiltroWhere($sql, $params, $types, $filtros, $search);

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Obtiene registros cuando NO hay filtro de motivo (combinación usuario-día).
     *
     * @param int $start
     * @param int $length
     * @param array $filtros
     * @param string $search
     * @return array
     */
    private function getRegistrosSinMotivo(int $start, int $length, array $filtros, string $search): array
    {
        $fechaInicio = $filtros['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $filtros['fecha_fin'] ?? date('Y-m-d');

        // 1. Obtener todos los usuarios que cumplen filtros
        $sqlUsuarios = "SELECT u.idusuarios, u.usu_nombre, u.usu_identificacion, u.usu_tipocontrato,
                           u.usu_fechalicencia, u.usu_idsede
                    FROM usuarios u
                    WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6";
        $params = [];
        $types = "";

        $this->buildFiltroWhere($sqlUsuarios, $params, $types, $filtros, $search);

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
        $diasList = [];
        $periodo = new DatePeriod(
            new DateTime($fechaInicio),
            new DateInterval('P1D'),
            (new DateTime($fechaFin))->modify('+1 day')
        );
        foreach ($periodo as $fecha) {
            $diasList[] = $fecha->format('Y-m-d');
        }

        // 3. Obtener IDs de usuarios
        $userIds = array_column($usuarios, 'idusuarios');
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        // 4. Consultar pre-operacional
        $sqlPre = "SELECT p.idpreoperacinal, p.preidusuario, p.prefechaingreso, p.preestado, p.prevehiculo,
                      DATE(p.prefechaingreso) as fecha
               FROM `pre-operacional` p
               WHERE p.preidusuario IN ($placeholders)
                 AND DATE(p.prefechaingreso) BETWEEN ? AND ?";
        $preParams = array_merge($userIds, [$fechaInicio, $fechaFin]);
        $preTypes = str_repeat('i', count($userIds)) . 'ss';
        $stmt = $this->db->prepare($sqlPre);
        $stmt->bind_param($preTypes, ...$preParams);
        $stmt->execute();
        $preRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $preIndex = [];
        foreach ($preRows as $row) {
            $preIndex[$row['preidusuario']][$row['fecha']] = $row;
        }

        // 5. Consultar seguimiento_user
        $sqlSeg = "SELECT s.idseguimiento_user, s.seg_idusuario, s.seg_fechaingreso, s.seg_motivo, s.seg_descr,
                      s.seg_alcohol, s.seg_horaalmuerzo, s.seg_horaregreso, s.seg_horaoficina, s.seg_fechafinalizo,
                      s.seg_compañero, s.seg_idzona, DATE(s.seg_fechaalcohol) as fecha
               FROM seguimiento_user s
               WHERE s.seg_idusuario IN ($placeholders)
                 AND DATE(s.seg_fechaalcohol) BETWEEN ? AND ?";
        $segParams = array_merge($userIds, [$fechaInicio, $fechaFin]);
        $stmt = $this->db->prepare($sqlSeg);
        $stmt->bind_param($preTypes, ...$segParams);
        $stmt->execute();
        $segRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $segIndex = [];
        foreach ($segRows as $row) {
            $segIndex[$row['seg_idusuario']][$row['fecha']] = $row;
        }

        // 6. Consultar hojadevida
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
            foreach ($diasList as $dia) {
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

                $allData[] = $row;
            }
        }

        // 8. Precargar relaciones en caché
        $this->cargarRelacionesEnLote($allData);

        // 9. Enriquecer filas
        foreach ($allData as &$row) {
            $row = $this->enriquecerFila($row);
        }

        // 10. Ordenar por prioridad y nombre
        usort($allData, function ($a, $b) {
            $prioridadA = $this->getPrioridadOrden($a);
            $prioridadB = $this->getPrioridadOrden($b);
            if ($prioridadA != $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }
            return strcmp($a['usu_nombre'], $b['usu_nombre']);
        });

        // 11. Paginar
        return array_slice($allData, $start, $length);
    }

    /**
     * Obtiene registros cuando SÍ hay filtro de motivo.
     *
     * @param int $start
     * @param int $length
     * @param array $filtros
     * @param string $search
     * @return array
     */
    private function getRegistrosConMotivo(int $start, int $length, array $filtros, string $search): array
    {
        $fechaInicio = $filtros['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $filtros['fecha_fin'] ?? date('Y-m-d');
        $motivo = $filtros['motivo'];

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
                h.hoj_cuen IS NULL OR h.hoj_cuen = '' AS falta_cuenta,
                h.hoj_arl IS NULL OR h.hoj_arl = '' AS falta_arl
            FROM seguimiento_user s
            INNER JOIN usuarios u ON u.idusuarios = s.seg_idusuario
            LEFT JOIN `pre-operacional` p ON p.preidusuario = u.idusuarios AND DATE(p.prefechaingreso) = DATE(s.seg_fechaalcohol)
            LEFT JOIN hojadevida h ON h.hoj_cedula = u.usu_identificacion AND h.hoj_estado = 'Activo'
            WHERE u.usu_estado = 1 AND u.usu_filtro = 1 AND u.roles_idroles != 6
              AND s.seg_motivo = ?
              AND DATE(s.seg_fechaalcohol) BETWEEN ? AND ?";

        $params = [$motivo, $fechaInicio, $fechaFin];
        $types = "sss";

        $this->buildFiltroWhere($sql, $params, $types, $filtros, $search);

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Precargar relaciones en caché
        $this->cargarRelacionesEnLote($rows);

        // Enriquecer
        foreach ($rows as &$row) {
            $row = $this->enriquecerFila($row);
        }

        // Ordenar por prioridad
        usort($rows, function ($a, $b) {
            $prioridadA = $this->getPrioridadOrden($a);
            $prioridadB = $this->getPrioridadOrden($b);
            if ($prioridadA != $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }
            return strcmp($a['usu_nombre'], $b['usu_nombre']);
        });

        // Paginar
        return array_slice($rows, $start, $length);
    }

    /**
     * Calcula la cantidad de días entre dos fechas.
     *
     * @param string $inicio
     * @param string $fin
     * @return int
     */
    private function calcularDiasRango(string $inicio, string $fin): int
    {
        try {
            $fechaInicio = new DateTime($inicio);
            $fechaFin = new DateTime($fin);
            return $fechaInicio->diff($fechaFin)->days + 1;
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Carga en lote los datos de vehículos, zonas y compañeros para evitar N+1.
     *
     * @param array $rows Referencia a las filas
     */
    private function cargarRelacionesEnLote(array &$rows): void
    {
        $vehiculoIds = [];
        $zonaIds = [];
        $companeroIds = [];

        foreach ($rows as $row) {
            if (!empty($row['prevehiculo'])) {
                $vehiculoIds[] = $row['prevehiculo'];
            }
            if (!empty($row['seg_idzona'])) {
                $zonaIds[] = $row['seg_idzona'];
            }
            if (!empty($row['seg_compañero'])) {
                $companeroIds[] = $row['seg_compañero'];
            }
        }

        if (!empty($vehiculoIds)) {
            $this->cargarVehiculosCache(array_unique($vehiculoIds));
        }
        if (!empty($zonaIds)) {
            $this->cargarZonasCache(array_unique($zonaIds));
        }
        if (!empty($companeroIds)) {
            $this->cargarCompanerosCache(array_unique($companeroIds));
        }
    }

    /**
     * Precarga vehículos en caché.
     *
     * @param array $ids
     */
    private function cargarVehiculosCache(array $ids): void
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT idvehiculos, veh_placa, veh_fechaseguro, veh_fechategnomecanica, veh_aceitekil, veh_kmalcambaceite, veh_kilactual
                FROM vehiculos WHERE idvehiculos IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $this->vehiculosCache[$row['idvehiculos']] = $row;
        }
    }

    /**
     * Precarga zonas en caché.
     *
     * @param array $ids
     */
    private function cargarZonasCache(array $ids): void
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT idzonatrabajo, zon_nombre FROM zonatrabajo WHERE idzonatrabajo IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $this->zonasCache[$row['idzonatrabajo']] = $row['zon_nombre'];
        }
    }

    /**
     * Precarga compañeros (usuarios) en caché.
     *
     * @param array $ids
     */
    private function cargarCompanerosCache(array $ids): void
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT idusuarios, usu_nombre FROM usuarios WHERE idusuarios IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $this->companerosCache[$row['idusuarios']] = $row['usu_nombre'];
        }
    }

    // ==================== ENRIQUECIMIENTO DE FILAS ====================

    /**
     * Enriquece una fila con datos adicionales y HTML para la vista.
     *
     * @param array $row
     * @return array
     */
    private function enriquecerFila(array $row): array
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

        // Datos de vehículo desde caché
        $vehiculo = isset($row['prevehiculo']) && isset($this->vehiculosCache[$row['prevehiculo']])
            ? $this->vehiculosCache[$row['prevehiculo']] : null;

        // Alerta de expiración (licencia, tecnicomecánica y seguro)
        $fechaLicencia = $row['usu_fechalicencia'] ?? null;
        $fechaTecno = $vehiculo['veh_fechategnomecanica'] ?? null;
        $fechaSeguro = $vehiculo['veh_fechaseguro'] ?? null;
        $alertaExpiracion = $this->generarAlertaExpiracion($fechaLicencia, $fechaTecno, $fechaSeguro, $fechaActual);
        $row['alerta_expiracion'] = $alertaExpiracion;
        $row['alerta_izquierda_html'] = $alertaExpiracion['html'];

        // Color de fila
        $row['row_color'] = $this->determinarColorFila($row);

        // Enlaces
        $row['preoperacional_link'] = $this->linkPreoperacional($row);
        $row['validacion_link'] = $this->linkValidacion($row);
        $row['imagen_link'] = $this->linkImagen($row);
        $row['ingreso_link'] = $this->linkIngreso($row);
        $row['zona_link'] = $this->linkZona($row);
        $row['companero_link'] = $this->linkCompanero($row);
        $row['hora_almuerzo_link'] = $this->linkHoraAlmuerzo($row);
        $row['retorno_almuerzo_link'] = $this->linkRetornoAlmuerzo($row);
        $row['retorno_oficina_link'] = $this->linkRetornoOficina($row);

        // Fechas con formato
        $row['fecha_seguro_html'] = $this->formatoFechaConDocumento($vehiculo['veh_fechaseguro'] ?? null, $row['prevehiculo'] ?? null, 3, $fechaActual);
        $row['fecha_tecno_html'] = $this->formatoFechaConDocumento($vehiculo['veh_fechategnomecanica'] ?? null, $row['prevehiculo'] ?? null, 4, $fechaActual);
        $row['fecha_licencia_html'] = $this->formatoFechaConAlerta($row['usu_fechalicencia'], $fechaActual);
        $row['cambio_aceite_html'] = $this->formatoCambioAceite($row, $vehiculo);

        // Eliminar
        $row['eliminar_html'] = $this->linkEliminar($row);

        // Datos de vehículo
        $row['veh_placa'] = $vehiculo['veh_placa'] ?? null;
        $row['veh_fechaseguro'] = $vehiculo['veh_fechaseguro'] ?? null;
        $row['veh_fechategnomecanica'] = $vehiculo['veh_fechategnomecanica'] ?? null;
        $row['veh_aceitekil'] = $vehiculo['veh_aceitekil'] ?? null;
        $row['veh_kmalcambaceite'] = $vehiculo['veh_kmalcambaceite'] ?? null;
        $row['veh_kilactual'] = $vehiculo['veh_kilactual'] ?? null;

        // Nombre de zona desde caché
        $row['zona_nombre'] = isset($row['seg_idzona']) ? ($this->zonasCache[$row['seg_idzona']] ?? null) : null;

        return $row;
    }

    /**
     * Determina la prioridad de ordenamiento de una fila.
     *
     * @param array $row
     * @return int
     */
    private function getPrioridadOrden(array $row): int
    {
        $alerta = $row['alerta_expiracion']['urgency'] ?? 0;
        $tieneAlerta = $alerta > 0;

        if (!empty($row['idpreoperacinal'])) {
            $estado = $row['preestado'];
            if ($estado !== 'Validado' && $estado !== 'Validado Covid19') {
                if ($estado === 'No aplica')
                    return 5;
                if ($estado === 'vacaciones')
                    return 7;
                if ($estado === 'descanso')
                    return 8;
                return $tieneAlerta ? 1 : 2;
            } else {
                if (empty($row['idseguimiento_user'])) {
                    return $tieneAlerta ? 1 : 2;
                }
                return $tieneAlerta ? 4 : 6;
            }
        }
        if (empty($row['idseguimiento_user']))
            return 3;
        return 6;
    }

    /**
     * Genera el HTML de la burbuja de alertas.
     *
     * @param int $idUsuario
     * @param array $alertas
     * @return string
     */
    private function generarBurbujaAlertas(int $idUsuario, array $alertas): string
    {
        if (empty($alertas))
            return '';
        $items = '';
        foreach ($alertas as $a) {
            $items .= "<li>" . htmlspecialchars($a) . "</li>";
        }
        return "<div class='noti_bubble' data-id='$idUsuario'>" . count($alertas) . "</div>
                <div class='noti_options' data-id='$idUsuario' style='display:none;'><ul>$items</ul></div>";
    }

    /**
     * Determina el color de fondo y texto de la fila según su estado.
     *
     * @param array $row
     * @return string Color de fondo
     */
    private function determinarColorFila(array &$row): string
    {
        $bg = '#FFFFFF';
        $text = '#000000';

        if (!empty($row['alerta_expiracion']['expired'])) {
            $bg = '#922B21';
            $text = '#FFFFFF';
        } elseif (($row['seg_motivo'] ?? '') === 'descanso') {
            $bg = '#fff3cd';
            $text = '#664d03';
        } elseif (($row['seg_motivo'] ?? '') === 'Vacaciones') {
            $bg = '#d1e7dd';
            $text = '#0f5132';
        } elseif (!empty($row['idpreoperacinal'])) {
            if ($row['preestado'] !== 'Validado' && $row['preestado'] !== 'Validado Covid19') {
                if ($row['preestado'] === 'No aplica') {
                    $bg = ($row['idpreoperacinal'] % 2 == 0) ? '#FFFFFF' : '#EFEFEF';
                } else {
                    $bg = '#FEF5E7';
                    $text = '#F39C12';
                }
            } else {
                if (empty($row['idseguimiento_user'])) {
                    $bg = '#FEF5E7';
                    $text = '#F39C12';
                } else {
                    $bg = ($row['idpreoperacinal'] % 2 == 0) ? '#FFFFFF' : '#EFEFEF';
                }
            }
        } elseif (!empty($row['idseguimiento_user'])) {
            $bg = ($row['idseguimiento_user'] % 2 == 0) ? '#FFFFFF' : '#EFEFEF';
        } else {
            $bg = '#f8d7da';
            $text = '#721c24';
        }

        $row['row_bg_color'] = $bg;
        $row['row_text_color'] = $text;
        return $bg;
    }

    // ==================== GENERADORES DE ENLACES HTML ====================

    private function linkPreoperacional(array $row): string
    {
        if (empty($row['idpreoperacinal']))
            return '';
        $estado = $row['preestado'];
        if (in_array($estado, ['No aplica', 'descanso', 'vacaciones']))
            return $estado;
        $url = "../controller/PreoperacionalController.php?preoperacional=validarpreoperacional&idpre={$row['idpreoperacinal']}&iduser={$row['idusuarios']}&fecha={$row['fecha']}&idvehiculo={$row['prevehiculo']}";
        return "<a href='#' onclick='abrirValidacionPreoperacional(\"$url\")'>$estado</a>";
    }

    private function linkValidacion(array $row): string
    {
        if (empty($row['idpreoperacinal']))
            return '';
        if (in_array($row['preestado'], ['Validado', 'Validado Covid19'])) {
            $url = "../controller/PreoperacionalController.php?preoperacional=validarpreoperacional&idpre={$row['idpreoperacinal']}&iduser={$row['idusuarios']}&fecha={$row['fecha']}&idvehiculo={$row['prevehiculo']}";
            return "<a href='#' onclick='abrirValidacionPreoperacional(\"$url\")'>Validado</a>";
        }
        if (in_array($row['preestado'], ['No aplica', 'descanso', 'vacaciones', 'Vacaciones'])) {
            return $row['preestado'];
        }
        return 'Sin Validar';
    }

    private function linkImagen(array $row): string
    {
        if (empty($row['idseguimiento_user']))
            return '';
        return $this->generarLinkDocumento('seguimiento_user', $row['idseguimiento_user'], 1, 'Ver');
    }

    private function linkIngreso(array $row): string
    {
        $param1 = $row['idseguimiento_user'] ?: $row['idusuarios'];
        $texto = $row['idseguimiento_user'] ? 'Ingreso+' : 'Sin Ingreso';
        $paramExtra = $row['fecha'] ?? date('Y-m-d');
        return "<a href='#' onclick='abrirPopup(\"ingreso\", $param1, \"$paramExtra\")'>$texto</a>";
    }

    private function linkZona(array $row): string
    {
        $idSeg = $row['idseguimiento_user'] ?? null;
        if (empty($idSeg))
            return 'Faltante';
        $texto = $row['zona_nombre'] ?? 'Faltante';
        $fecha = $row['prefechaingreso'] ?? $row['fecha'];
        return "<a href='#' onclick='abrirPopup(\"zona\", $idSeg, \"$fecha\")'>$texto</a>";
    }

    private function linkCompanero(array $row): string
    {
        $companeroId = $row['seg_compañero'] ?? null;
        $idSeg = $row['idseguimiento_user'] ?? null;
        if (empty($idSeg))
            return 'Sin compañero';
        $texto = empty($companeroId) ? 'Sin compañero' : ($this->companerosCache[$companeroId] ?? 'Compañero desconocido');
        $fecha = date('Y-m-d', strtotime($row['fecha']));
        return "<a href='#' onclick='abrirPopup(\"trabaja_con\", $idSeg, \"{$fecha} _ {$row['idusuarios']}\")'>$texto</a>";
    }

    private function linkHoraAlmuerzo(array $row): string
    {
        if (empty($row['idseguimiento_user']))
            return '';
        $hora = $row['seg_horaalmuerzo'] ?: 'Sin Ingresar';
        return "<a href='#' onclick='abrirPopup(\"hora_almuerzo\", {$row['idseguimiento_user']}, \"{$row['prefechaingreso']}\")'>$hora</a>";
    }

    private function linkRetornoAlmuerzo(array $row): string
    {
        if (empty($row['idseguimiento_user']))
            return '';
        $hora = $row['seg_horaregreso'] ?: 'Sin Ingresar';
        return "<a href='#' onclick='abrirPopup(\"retorno_almuerzo\", {$row['idseguimiento_user']}, \"{$row['prefechaingreso']}\")'>$hora</a>";
    }

    private function linkRetornoOficina(array $row): string
    {
        if (empty($row['idseguimiento_user']))
            return '';
        $hora = $row['seg_horaoficina'] ?: 'Sin Ingresar';
        return "<a href='#' onclick='abrirPopup(\"retorno_oficina\", {$row['idseguimiento_user']}, \"{$row['prefechaingreso']}\")'>$hora</a>";
    }

    private function linkEliminar(array $row): string
    {
        if (empty($row['idpreoperacinal']) && empty($row['idseguimiento_user']))
            return '';
        $id = $row['idpreoperacinal'] . '_' . $row['idseguimiento_user'];
        return "<a href='#' onclick='eliminarRegistro(\"$id\", \"borraseguser\")'><i class='fa fa-trash'></i></a>";
    }

    // ==================== FORMATO DE FECHAS Y ALERTAS ====================

    private function formatoFechaConAlerta(?string $fecha, string $hoy): string
    {
        if (!$fecha || $fecha === '0000-00-00')
            return '';
        $dias = $this->diasHasta($hoy, $fecha);
        if ($dias <= 3 && $dias >= 0) {
            return "<span style='background-color:#F39C12'>$fecha</span>";
        }
        return $fecha;
    }

    private function formatoFechaConDocumento(?string $fecha, ?int $vehiculoId, int $version, string $hoy): string
    {
        if (!$fecha || $fecha === '0000-00-00')
            return '';
        $documentoId = $this->obtenerDocumentoId($vehiculoId, 'vehiculos', $version);
        $dias = $this->diasHasta($hoy, $fecha);
        $styledFecha = ($dias <= 3 && $dias >= 0) ? "<span style='background-color:#F39C12'>$fecha</span>" : $fecha;
        if ($documentoId) {
            $url = "?accion=ver_documento&id=$documentoId";
            return "<a href='#' onclick='window.open(\"$url\", \"_blank\")'>$styledFecha</a>";
        }
        return $styledFecha;
    }

    private function formatoCambioAceite(array $row, ?array $vehiculo): string
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

    /**
     * Clasifica un conteo de días restantes en nivel de alerta.
     * Retorna [color, severity] — severity: 3=expirado, 2=crítico(≤7d), 1=advertencia(≤30d), 0=normal.
     */
    private function clasificarDias(int $dias): array
    {
        if ($dias < 0)      return ['color' => '#F44336', 'severity' => 3];
        if ($dias <= 7)     return ['color' => '#F44336', 'severity' => 2];
        if ($dias <= 30)    return ['color' => '#FF9800', 'severity' => 1];
        return              ['color' => '#555',    'severity' => 0];
    }

    private function generarAlertaExpiracion(?string $fechaLicencia, ?string $fechaTecno, ?string $fechaSeguro = null, string $hoy = ''): array
    {
        if (empty($hoy)) {
            $hoy = date('Y-m-d');
        }
        $diasLic = $this->diasHasta($hoy, $fechaLicencia);
        $diasTecno = $this->diasHasta($hoy, $fechaTecno);
        $diasSeguro = $this->diasHasta($hoy, $fechaSeguro);

        $nombres = [
            'licencia' => 'Licencia',
            'tecno' => 'Tecnicomecánica',
            'seguro' => 'Seguro'
        ];

        $fechas = [
            'licencia' => ['dias' => $diasLic, 'fecha' => $fechaLicencia],
            'tecno' => ['dias' => $diasTecno, 'fecha' => $fechaTecno],
            'seguro' => ['dias' => $diasSeguro, 'fecha' => $fechaSeguro],
        ];

        // Construir dropdown con todos los tipos que tengan fecha
        $dropdownItems = [];
        $candidatos = [];

        foreach ($fechas as $tipo => $info) {
            if ($info['dias'] === null) continue;
            $d = $info['dias'];
            $candidatos[$tipo] = $d;

            $clasif = $this->clasificarDias($d);
            $liStyle = 'color:' . $clasif['color'] . ';' . ($d < 0 ? ' font-weight:bold;' : '');

            if ($d < 0) {
                $texto = $nombres[$tipo] . ': expirada hace ' . abs($d) . ' días';
            } elseif ($d <= 7) {
                $texto = $nombres[$tipo] . ': expira en ' . $d . ' días';
            } elseif ($d <= 30) {
                $texto = $nombres[$tipo] . ': expira en ' . $d . ' días';
            } else {
                $texto = $nombres[$tipo] . ': ' . $info['fecha'] . ' (' . $d . ' días)';
            }

            $dropdownItems[] = "<li style='$liStyle; padding:2px 0;'>" . htmlspecialchars($texto) . "</li>";
        }

        if (empty($candidatos)) {
            return ['html' => '', 'expired' => false, 'urgency' => 0];
        }

        // El más urgente determina el color del dot
        $tipo = array_key_first($candidatos);
        $dias = reset($candidatos);
        foreach ($candidatos as $tp => $d) {
            if ($d < $dias) { $dias = $d; $tipo = $tp; }
        }

        $clasif = $this->clasificarDias($dias);
        $expired = $dias < 0;
        $nombre = $nombres[$tipo] ?? $tipo;

        $colorClass = '';
        $tooltip = '';
        if ($clasif['severity'] >= 3) {
            $colorClass = 'warning-red expired';
            $tooltip = $nombre . ' expirada hace ' . abs($dias) . ' días';
        } elseif ($clasif['severity'] >= 2) {
            $colorClass = 'warning-red';
            $tooltip = $nombre . ' expira en ' . $dias . ' días';
        } elseif ($clasif['severity'] >= 1) {
            $colorClass = 'warning-orange';
            $tooltip = $nombre . ' expira en ' . $dias . ' días';
        }

        $urgency = $clasif['severity'];
        if ($urgency === 0) {
            return ['html' => '', 'expired' => false, 'urgency' => 0];
        }

        $dot = '<span class="warning-dot ' . $colorClass . '" title="' . htmlspecialchars($tooltip) . '"></span>';
        $dropdown = '<div class="alerta-dropdown"><ul style="list-style:none; margin:0; padding:0;">'
            . implode('', $dropdownItems) . '</ul></div>';
        $html = '<div class="alerta-wrapper">' . $dot . $dropdown . '</div>';

        return ['html' => $html, 'expired' => $expired, 'urgency' => $urgency, 'dias' => $dias, 'tipo' => $tipo];
    }

    private function diasHasta(string $hoy, ?string $fecha): ?int
    {
        if (!$fecha || $fecha === '0000-00-00')
            return null;
        $hoyTs = strtotime($hoy);
        $fechaTs = strtotime($fecha);
        return round(($fechaTs - $hoyTs) / 86400);
    }

    /**
     * Calcula la fecha de finalización sumando horas a la fecha de ingreso.
     *
     * @param string $fechaCompleta Fecha y hora de ingreso (Y-m-d H:i:s)
     * @param string $motivo Motivo del ingreso
     * @param int $horas Cantidad de horas (0 si no aplica)
     * @return string|null Fecha de finalización o NULL si no corresponde
     */
    private function calcularFechaFinalizo(string $fechaCompleta, string $motivo, int $horas): ?string
    {
        if ($motivo === 'IngresoHoras' && $horas > 0 && !empty($fechaCompleta)) {
            try {
                $fecha = new DateTime($fechaCompleta);
                $fecha->add(new DateInterval('PT' . $horas . 'H'));
                return $fecha->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                error_log("Error calculando fecha finalizo: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }

    // ==================== OPERACIONES CRUD ====================

    /**
     * Inserta un nuevo registro de ingreso.
     *
     * @param array $data Datos del ingreso
     * @param array|null $imagen Archivo subido
     * @param int $idUsuario ID del usuario que registra
     * @return bool
     */
    public function insertarIngreso(array $data, ?array $imagen, int $idUsuario): bool
    {
        $fechaCompleta = $data['fecha'] . ' ' . date('H:i:s');
        $horas = isset($data['horas']) && $data['horas'] !== '' ? intval($data['horas']) : 0;
        $fechaFinalizo = $this->calcularFechaFinalizo($fechaCompleta, $data['motivo'], $horas);
        $sql = "INSERT INTO seguimiento_user
                (seg_idusuario, seg_fechaingreso, seg_motivo, seg_descr, seg_idzona, seg_alcohol, seg_horas_trabajadas, seg_fechaalcohol, seg_iduserregistro, seg_fechafinalizo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "isssssisis",
            $data['operario'],
            $fechaCompleta,
            $data['motivo'],
            $data['descripcion'],
            $data['zona'],
            $data['prueba'],
            $horas,
            $data['fecha'],
            $idUsuario,
            $fechaFinalizo
        );
        $ok = $stmt->execute();
        $idSeguimiento = $this->db->insert_id;

        if ($ok && $imagen && $imagen['tmp_name']) {
            $this->guardarImagen($imagen, 'seguimiento_user', $idSeguimiento);
        }

        $this->asegurarPreoperacional($data['operario'], $data['fecha'], 'No aplica');
        return $ok;
    }

    /**
     * Actualiza un registro de ingreso existente.
     *
     * @param int $idSeguimiento
     * @param array $data
     * @param array|null $imagen
     * @param int $idUsuario
     * @return bool
     */
    public function actualizarIngreso(int $idSeguimiento, array $data, ?array $imagen, int $idUsuario): bool
    {
        $horas = isset($data['horas']) && $data['horas'] !== '' ? intval($data['horas']) : 0;

        // Obtener la fecha de ingreso actual para calcular fecha final
        $fechaIngreso = null;
        $sqlSelect = "SELECT seg_fechaingreso FROM seguimiento_user WHERE idseguimiento_user = ?";
        $stmtSelect = $this->db->prepare($sqlSelect);
        $stmtSelect->bind_param("i", $idSeguimiento);
        $stmtSelect->execute();
        $result = $stmtSelect->get_result();
        if ($row = $result->fetch_assoc()) {
            $fechaIngreso = $row['seg_fechaingreso'];
        }
        $fechaFinalizo = $this->calcularFechaFinalizo($fechaIngreso ?? '', $data['motivo'], $horas);

        $sql = "UPDATE seguimiento_user SET
                seg_motivo = ?, seg_descr = ?, seg_idzona = ?, seg_alcohol = ?, seg_horas_trabajadas = ?, seg_fechafinalizo = ?
                WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "ssisisi",
            $data['motivo'],
            $data['descripcion'],
            $data['zona'],
            $data['prueba'],
            $horas,
            $fechaFinalizo,
            $idSeguimiento
        );
        $ok = $stmt->execute();
        if ($ok && $imagen && $imagen['tmp_name']) {
            $this->guardarImagen($imagen, 'seguimiento_user', $idSeguimiento);
        }
        return $ok;
    }

    /**
     * Actualiza la zona de un seguimiento.
     *
     * @param int $idSeguimiento
     * @param int $zona
     * @return bool
     */
    public function actualizarZona(int $idSeguimiento, int $zona): bool
    {
        $sql = "UPDATE seguimiento_user SET seg_idzona = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $zona, $idSeguimiento);
        return $stmt->execute();
    }

    /**
     * Actualiza la hora de almuerzo.
     *
     * @param int $idSeguimiento
     * @param string $hora
     * @return bool
     */
    public function actualizarHoraAlmuerzo(int $idSeguimiento, string $hora): bool
    {
        $sql = "UPDATE seguimiento_user SET seg_horaalmuerzo = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $hora, $idSeguimiento);
        return $stmt->execute();
    }

    /**
     * Actualiza la hora de retorno de almuerzo.
     *
     * @param int $idSeguimiento
     * @param string $hora
     * @return bool
     */
    public function actualizarRetornoAlmuerzo(int $idSeguimiento, string $hora): bool
    {
        $sql = "UPDATE seguimiento_user SET seg_horaregreso = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $hora, $idSeguimiento);
        return $stmt->execute();
    }

    /**
     * Actualiza la hora de retorno a oficina.
     *
     * @param int $idSeguimiento
     * @param string $hora
     * @return bool
     */
    public function actualizarRetornoOficina(int $idSeguimiento, string $hora): bool
    {
        $sql = "UPDATE seguimiento_user SET seg_horaoficina = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $hora, $idSeguimiento);
        return $stmt->execute();
    }

    /**
     * Actualiza el compañero de trabajo.
     *
     * @param int $idSeguimiento
     * @param int $companero
     * @return bool
     */
    public function actualizarCompanero(int $idSeguimiento, int $companero): bool
    {
        $sql = "UPDATE seguimiento_user SET seg_compañero = ? WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        // Se usa "s" para el compañero porque puede ser NULL (no compatible con bind_param "i")
        $valor = $companero > 0 ? (string) $companero : null;
        $stmt->bind_param("si", $valor, $idSeguimiento);
        return $stmt->execute();
    }

    /**
     * Inserta un registro en seguimiento_user y pre-operacional (operación atómica compartida).
     */
    private function insertarSegYPre(int $idOperario, string $fechaStr, string $motivo, string $descripcion, string $estadoPre, int $idUsuario): void
    {
        $fechaHora = $fechaStr . ' 00:00:00';
        $sql1 = "INSERT INTO seguimiento_user (seg_idusuario, seg_fechaingreso, seg_motivo, seg_descr, seg_alcohol, seg_fechaalcohol, seg_iduserregistro)
                 VALUES (?, ?, ?, ?, 'No aplica', ?, ?)";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->bind_param("issssi", $idOperario, $fechaHora, $motivo, $descripcion, $fechaStr, $idUsuario);
        $stmt1->execute();

        $sql2 = "INSERT INTO `pre-operacional` (prefechaingreso, preidusuario, preestado) VALUES (?, ?, ?)";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bind_param("sis", $fechaHora, $idOperario, $estadoPre);
        $stmt2->execute();
    }

    /**
     * Inserta días festivos (descanso) para todos los operarios de una sede.
     *
     * @param string $fecha
     * @param int $sede
     * @param int $idUsuario
     * @return bool
     */
    public function insertarFestivos(string $fecha, int $sede, int $idUsuario): bool
    {
        $sql = "SELECT u.idusuarios
                FROM usuarios u
                LEFT JOIN hojadevida h ON h.hoj_cedula = u.usu_identificacion
                WHERE u.usu_estado = 1
                  AND u.usu_filtro = 1
                  AND u.usu_tipocontrato = 'Empresa'
                  AND (h.hoj_fechatermino IS NULL OR h.hoj_fechatermino = '0000-00-00')
                  AND u.roles_idroles != 6";
        $params = [];
        $types = "";
        if ($sede > 0) {
            $sql .= " AND u.usu_idsede = ?";
            $params[] = $sede;
            $types .= "i";
        }
        $stmt = $this->db->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $this->db->begin_transaction();
        try {
            while ($row = $result->fetch_assoc()) {
                $iduser = $row['idusuarios'];
                $check = "SELECT idseguimiento_user FROM seguimiento_user WHERE seg_idusuario = ? AND DATE(seg_fechaingreso) = ?";
                $stmtCheck = $this->db->prepare($check);
                $stmtCheck->bind_param("is", $iduser, $fecha);
                $stmtCheck->execute();
                $existe = $stmtCheck->get_result()->fetch_assoc();
                if (!$existe) {
                    $this->insertarSegYPre($iduser, $fecha, 'descanso', 'descanso', 'descanso', $idUsuario);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error en insertarFestivos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra vacaciones para un operario en un rango de fechas.
     *
     * @param array $data (operario, fecha_ini, fecha_fin)
     * @param int $idUsuario
     * @return bool
     */
    public function insertarVacaciones(array $data, int $idUsuario): bool
    {
        return $this->insertarRangoFechas($data['operario'], $data['fecha_ini'], $data['fecha_fin'], 'Vacaciones', 'Vacaciones', 'vacaciones', $idUsuario);
    }

    /**
     * Registra licencias/permisos para un operario en un rango de fechas.
     *
     * @param array $data (operario, fecha_ini, fecha_fin, motivo, descripcion)
     * @param int $idUsuario
     * @return bool
     */
    public function insertarLicencia(array $data, int $idUsuario): bool
    {
        return $this->insertarRangoFechas($data['operario'], $data['fecha_ini'], $data['fecha_fin'], $data['motivo'], $data['descripcion'], $data['motivo'], $idUsuario);
    }

    /**
     * Método genérico para insertar registros en un rango de fechas (vacaciones, licencias).
     *
     * @param int $idOperario
     * @param string $fechaIni
     * @param string $fechaFin
     * @param string $motivo
     * @param string $descripcion
     * @param string $estadoPre
     * @param int $idUsuario
     * @return bool
     */
    private function insertarRangoFechas(int $idOperario, string $fechaIni, string $fechaFin, string $motivo, string $descripcion, string $estadoPre, int $idUsuario): bool
    {
        $inicio = new DateTime($fechaIni);
        $fin = new DateTime($fechaFin);
        $interval = new DateInterval('P1D');
        $periodo = new DatePeriod($inicio, $interval, $fin->modify('+1 day'));

        $this->db->begin_transaction();
        try {
            foreach ($periodo as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');
                $check = "SELECT idseguimiento_user FROM seguimiento_user WHERE seg_idusuario = ? AND DATE(seg_fechaingreso) = ?";
                $stmtCheck = $this->db->prepare($check);
                $stmtCheck->bind_param("is", $idOperario, $fechaStr);
                $stmtCheck->execute();
                $existe = $stmtCheck->get_result()->fetch_assoc();

                if (!$existe) {
                    $this->insertarSegYPre($idOperario, $fechaStr, $motivo, $descripcion, $estadoPre, $idUsuario);
                } else {
                    $sqlUp = "UPDATE seguimiento_user SET seg_motivo = ?, seg_descr = ? WHERE idseguimiento_user = ?";
                    $stmtUp = $this->db->prepare($sqlUp);
                    $stmtUp->bind_param("ssi", $motivo, $descripcion, $existe['idseguimiento_user']);
                    $stmtUp->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error en insertarRangoFechas: " . $e->getMessage());
            return false;
        }
    }

    // ==================== ELIMINACIÓN ====================

    /**
     * Obtiene los detalles de un registro antes de eliminarlo.
     *
     * @param string $idCombinado formato "idPre_idSeg"
     * @return array
     */
    public function getDetallesParaEliminar(string $idCombinado): array
    {
        $partes = explode('_', $idCombinado);
        $idPre = (int) ($partes[0] ?? 0);
        $idSeg = (int) ($partes[1] ?? 0);

        $detalles = ['preoperacional' => null, 'seguimiento' => null, 'usuario' => null, 'fecha' => null, 'motivo' => null];

        if ($idPre > 0) {
            $sql = "SELECT p.idpreoperacinal, p.preidusuario, p.prefechaingreso, p.preestado, u.usu_nombre
                    FROM `pre-operacional` p
                    LEFT JOIN usuarios u ON u.idusuarios = p.preidusuario
                    WHERE p.idpreoperacinal = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $idPre);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                $detalles['preoperacional'] = $row;
                $detalles['usuario'] = $row['usu_nombre'];
                $detalles['fecha'] = $row['prefechaingreso'];
            }
        }

        if ($idSeg > 0) {
            $sql = "SELECT s.idseguimiento_user, s.seg_idusuario, s.seg_fechaingreso, s.seg_motivo, s.seg_descr, u.usu_nombre
                    FROM seguimiento_user s
                    LEFT JOIN usuarios u ON u.idusuarios = s.seg_idusuario
                    WHERE s.idseguimiento_user = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $idSeg);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                $detalles['seguimiento'] = $row;
                $detalles['usuario'] = $detalles['usuario'] ?: $row['usu_nombre'];
                $detalles['fecha'] = $detalles['fecha'] ?: $row['seg_fechaingreso'];
                $detalles['motivo'] = $row['seg_motivo'];
            }
        }

        return $detalles;
    }

    /**
     * Elimina un registro de seguimiento y su preoperacional asociado, incluyendo documentos.
     *
     * @param string $idCombinado
     * @return bool
     */
    public function eliminarSeguimiento(string $idCombinado): bool
    {
        $partes = explode('_', $idCombinado);
        $idPre = (int) ($partes[0] ?? 0);
        $idSeg = (int) ($partes[1] ?? 0);

        $this->db->begin_transaction();
        try {
            if ($idSeg > 0) {
                $this->eliminarDocumentos('seguimiento_user', $idSeg);
                $sql = "DELETE FROM seguimiento_user WHERE idseguimiento_user = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $idSeg);
                $stmt->execute();
            }

            if ($idPre > 0) {
                $this->eliminarDocumentos('pre-operacional', $idPre);
                $sql = "DELETE FROM `pre-operacional` WHERE idpreoperacinal = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $idPre);
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

    /**
     * Elimina los registros de la tabla documentos y los archivos físicos asociados.
     *
     * @param string $tabla
     * @param int $idViene
     */
    private function eliminarDocumentos(string $tabla, int $idViene): void
    {
        $sql = "SELECT iddocumentos, doc_ruta FROM documentos WHERE doc_tabla = ? AND doc_idviene = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $tabla, $idViene);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($doc = $result->fetch_assoc()) {
            $ruta = $doc['doc_ruta'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
            $sqlDel = "DELETE FROM documentos WHERE iddocumentos = ?";
            $stmtDel = $this->db->prepare($sqlDel);
            $stmtDel->bind_param('i', $doc['iddocumentos']);
            $stmtDel->execute();
        }
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Obtiene un seguimiento por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getSeguimientoById(int $id): ?array
    {
        $sql = "SELECT * FROM seguimiento_user WHERE idseguimiento_user = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }

    /**
     * Obtiene un operario por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getOperarioById(int $id): ?array
    {
        $sql = "SELECT idusuarios, usu_nombre, usu_idsede FROM usuarios WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Obtiene la sede de un usuario.
     *
     * @param int $idUsuario
     * @return int
     */
    public function getSedeByUsuario(int $idUsuario): int
    {
        $sql = "SELECT usu_idsede FROM usuarios WHERE idusuarios = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['usu_idsede'] ?? 0);
    }

    /**
     * Obtiene información de una sede por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getSedeById(int $id): ?array
    {
        $sql = "SELECT idsedes, sed_nombre FROM sedes WHERE idsedes = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Verifica si un operario tiene preoperacional validado en una fecha.
     *
     * @param int $idUsuario
     * @param string $fecha
     * @return bool
     */
    public function tienePreoperacionalValidado(int $idUsuario, string $fecha): bool
    {
        $sql = "SELECT idpreoperacinal FROM `pre-operacional`
                WHERE preidusuario = ? AND DATE(prefechaingreso) = ?
                AND preestado IN ('Validado', 'Validado Covid19')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $idUsuario, $fecha);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Asegura que exista un registro en pre-operacional para un usuario en una fecha.
     *
     * @param int $idUsuario
     * @param string $fecha
     * @param string $estado
     */
    private function asegurarPreoperacional(int $idUsuario, string $fecha, string $estado): void
    {
        $check = "SELECT idpreoperacinal FROM `pre-operacional` WHERE preidusuario = ? AND DATE(prefechaingreso) = ?";
        $stmt = $this->db->prepare($check);
        $stmt->bind_param("is", $idUsuario, $fecha);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $fechaHora = $fecha . ' 00:00:00';
            $sql = "INSERT INTO `pre-operacional` (prefechaingreso, preidusuario, preestado) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sis", $fechaHora, $idUsuario, $estado);
            $stmt->execute();
        }
    }

    /**
     * Obtiene el ID del documento más reciente para un registro (consulta base compartida).
     *
     * @param int $idViene
     * @param string $tabla
     * @param int $version
     * @return int|null
     */
    private function getDocumentoId(int $idViene, string $tabla, int $version): ?int
    {
        $sql = "SELECT iddocumentos FROM documentos
                WHERE doc_idviene = ? AND doc_tabla = ? AND doc_version = ?
                ORDER BY doc_fecha DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $idViene, $tabla, $version);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['iddocumentos'] : null;
    }

    /**
     * Genera un enlace para ver un documento.
     */
    private function generarLinkDocumento(string $tabla, int $idViene, int $version, string $texto): string
    {
        $id = $this->getDocumentoId($idViene, $tabla, $version);
        if ($id) {
            $url = "?accion=ver_documento&id=" . $id;
            return "<a href='#' onclick='window.open(\"$url\", \"_blank\")'><img src='img/icono_documento.png' width='35'> $texto</a>";
        }
        return '';
    }

    /**
     * Obtiene el ID del documento más reciente que tenga archivo físico (doc_ruta no vacío).
     */
    private function obtenerDocumentoId(?int $idViene, string $tabla, int $version): ?int
    {
        if (!$idViene) return null;
        $sql = "SELECT iddocumentos FROM documentos
                WHERE doc_idviene = ? AND doc_tabla = ? AND doc_version = ? AND doc_ruta != ''
                ORDER BY doc_fecha DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $idViene, $tabla, $version);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['iddocumentos'] : null;
    }

    /**
     * Guarda una imagen en el sistema de archivos y registra en la tabla documentos.
     *
     * @param array $archivo
     * @param string $tabla
     * @param int $idViene
     * @param int $version
     * @return bool
     */
    private function guardarImagen(array $archivo, string $tabla, int $idViene, int $version = 1): bool
    {
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre = uniqid() . '_' . time() . '.' . $extension;
        $carpeta = $this->uploadPath . $tabla . '/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $ruta = $carpeta . $nombre;
        if (move_uploaded_file($archivo['tmp_name'], $ruta)) {
            $sql = "INSERT INTO documentos (doc_fecha, doc_nombre, doc_ruta, doc_tabla, doc_idviene, doc_version) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $fecha = date('Y-m-d');
            $nombreOriginal = basename($archivo['name']);
            $stmt->bind_param("ssssii", $fecha, $nombreOriginal, $ruta, $tabla, $idViene, $version);
            return $stmt->execute();
        }
        return false;
    }
}