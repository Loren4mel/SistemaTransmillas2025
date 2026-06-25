<?php
/**
 * ReporteConductorSSTModel - Modelo para gestion de reportes SST de conductores
 *
 * Maneja todas las operaciones de base de datos para el modulo de reportes
 * de accidentes y comparendos, incluyendo archivos en tabla documentos.
 */

require_once __DIR__ . "/../config/database.php";

class ReporteConductorSSTModel
{
    private $db;
    private const UPLOAD_DIR = __DIR__ . "/../uploads/sst_reporte_conductor/";

    // === CONSTANTES ===
    const TIPOS_VALIDOS = ['accidente', 'comparendo'];
    const TIPOS_EVENTO_VALIDOS = ['semanal', 'momento'];
    const ESTADOS_VALIDOS = ['pendiente', 'en_proceso', 'finalizado'];
    const ROLES_VALIDADOR = [1, 12];
    const TIPO_TO_VERSION = [
        'accidente'  => 1,
        'comparendo' => 2,
    ];
    const VERSION_FIRMA_SST = 6; // doc_version para firma del conductor SST
    const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
    ];
    const MAX_FILE_SIZE = 10485760; // 10 MB

    // Niveles de gravedad válidos por tipo (escala simplificada — conductor)
    const GRAVEDAD_ACCIDENTE  = [1, 2, 3, 4];
    const GRAVEDAD_COMPARENDO = [1, 2, 3];

    // Niveles de gravedad para validador (escala original completa)
    const GRAVEDAD_VAL_ACCIDENTE  = [1, 2, 3, 4];
    const GRAVEDAD_VAL_COMPARENDO = [1, 2, 3];

    // Etiquetas de gravedad completas por tipo (conductor y validador)
    const GRAVEDAD_LABELS = [
        'accidente' => [
            1 => ['etiqueta' => 'Leve',       'desc' => 'Daños materiales, ningún tipo de afectación a persona.'],
            2 => ['etiqueta' => 'Moderado',    'desc' => 'Lesiones leves que no requieren hospitalización.'],
            3 => ['etiqueta' => 'Grave',       'desc' => 'Lesiones que requieren atención médica u hospitalización.'],
            4 => ['etiqueta' => 'Crítico',     'desc' => 'Víctimas fatales o lesiones permanentes graves.'],
        ],
        'comparendo' => [
            1 => ['etiqueta' => 'Normal', 'desc' => 'Multa sin inmovilización del vehículo.'],
            2 => ['etiqueta' => 'Media',  'desc' => 'Multa con inmovilización, sin afectación a la licencia de conducción.'],
            3 => ['etiqueta' => 'Alta',   'desc' => 'Multa con inmovilización y/o afectación a la licencia de conducción.'],
        ],
    ];

    /**
     * Constructor - Inicializa la conexion a base de datos
     */
    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    // ==================== METODOS PRIVADOS ====================

    /**
     * Ejecuta una consulta con parametros y devuelve una sola fila
     *
     * @param string $sql Consulta SQL
     * @param string $types Tipos de parametros para bind_param
     * @param array $params Parametros de la consulta
     * @return array|null Resultado como array asociativo o null
     */
    private function executeQuery($sql, $types, $params)
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $errorMsg = "ReporteConductorSSTModel: executeQuery - Error preparando SQL: " . $this->db->error;
            error_log($errorMsg);
            throw new \RuntimeException($errorMsg);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Ejecuta una consulta con parametros y devuelve multiples filas
     *
     * @param string $sql Consulta SQL
     * @param string $types Tipos de parametros para bind_param
     * @param array $params Parametros de la consulta
     * @return array Resultados como array de arrays asociativos
     */
    private function executeAll($sql, $types, $params)
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $errorMsg = "ReporteConductorSSTModel: executeAll - Error preparando SQL: " . $this->db->error;
            error_log($errorMsg);
            throw new \RuntimeException($errorMsg);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Asegura que el directorio de subida de archivos exista
     */
    private function ensureUploadDir()
    {
        $dir = self::UPLOAD_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * Verifica si un vehiculo existe en la base de datos
     *
     * @param int $idVehiculo ID del vehiculo
     * @return bool True si existe
     */
    public function verificarVehiculoExiste($idVehiculo)
    {
        $sql = "SELECT COUNT(*) as total FROM vehiculos WHERE idvehiculos = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $idVehiculo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row && $row['total'] > 0;
    }

    // ==================== CALCULO DE SEMANA ====================

    /**
     * Calcula el inicio (lunes) y fin (domingo) de la semana para una fecha dada
     *
     * @param string $fecha Fecha en formato Y-m-d
     * @return array ['inicio' => 'Y-m-d', 'fin' => 'Y-m-d']
     */
    public function calcularSemana($fecha)
    {
        $timestamp = strtotime($fecha);
        $dayOfWeek = (int) date('N', $timestamp); // 1 = lunes, 7 = domingo

        // Lunes de esta semana
        $lunes = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days', $timestamp));
        // Domingo de esta semana
        $domingo = date('Y-m-d', strtotime('+' . (7 - $dayOfWeek) . ' days', $timestamp));

        return [
            'inicio' => $lunes,
            'fin'    => $domingo,
        ];
    }

    // ==================== INFO DEL CONDUCTOR ====================

    /**
     * Obtiene los datos basicos del conductor: nombre, cedula y placa del vehiculo asignado
     *
     * @param int $idUsuario ID del usuario
     * @return array|null Datos del conductor o null
     */
    public function obtenerInfoConductor($idUsuario)
    {
        $sql = "SELECT u.usu_nombre, u.usu_identificacion, u.usu_vehiculo,
                       v.veh_placa
                FROM usuarios u
                LEFT JOIN vehiculos v ON u.usu_vehiculo = v.idvehiculos
                WHERE u.idusuarios = ?
                LIMIT 1";
        return $this->executeQuery($sql, "i", [$idUsuario]);
    }

    // ==================== CONSULTAS ====================

    /**
     * Obtiene el ultimo reporte semanal de un usuario
     *
     * @param int $idUsuario ID del usuario
     * @return array|null Ultimo reporte semanal o null
     */
    public function obtenerUltimoReporteSemanal($idUsuario)
    {
        $sql = "SELECT * FROM sst_reporte_conductor
                WHERE id_usuario = ? AND tipo_evento = 'semanal'
                ORDER BY creado_en DESC
                LIMIT 1";
        return $this->executeQuery($sql, "i", [$idUsuario]);
    }

    /**
     * Obtiene el ultimo reporte de un usuario para un tipo especifico
     *
     * @param int $idUsuario ID del usuario
     * @param string $tipo Tipo de reporte ('accidente' o 'comparendo')
     * @return array|null Ultimo reporte del tipo o null
     */
    public function obtenerUltimoReporte($idUsuario, $tipo)
    {
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            error_log("ReporteConductorSSTModel: obtenerUltimoReporte - Tipo invalido: $tipo");
            return null;
        }
        $sql = "SELECT * FROM sst_reporte_conductor
                WHERE id_usuario = ? AND tipo = ?
                ORDER BY creado_en DESC
                LIMIT 1";
        return $this->executeQuery($sql, "is", [$idUsuario, $tipo]);
    }

    /**
     * Determina si se debe mostrar el formulario semanal al usuario
     * Logica de ventana semanal:
     * - Si no existe reporte semanal previo, mostrar (true)
     * - Calcular el viernes de la semana actual
     * - Si la fecha del ultimo reporte es anterior al viernes, mostrar (true)
     * - Si ya reporto esta semana (desde el viernes), no mostrar (false)
     *
     * @param int $idUsuario ID del usuario
     * @return bool True si se debe mostrar el formulario
     */
    public function debeMostrarFormularioSemanal($idUsuario)
    {
        $ultimo = $this->obtenerUltimoReporteSemanal($idUsuario);

        // Sin reporte previo: se debe mostrar
        if (!$ultimo) {
            return true;
        }

        $ultimaFecha = $ultimo['fecha'];

        // Calcular el viernes de la semana actual a las 00:00:00
        $hoy = new DateTime();
        $dayOfWeek = (int) $hoy->format('N'); // 1 = lunes, 7 = domingo

        if ($dayOfWeek <= 5) {
            // De lunes a viernes: viernes de esta semana
            $viernesStr = 'friday this week';
        } else {
            // Sabado o domingo: viernes de la semana pasada
            $viernesStr = 'friday last week';
        }

        $viernes = new DateTime($viernesStr);
        $viernes->setTime(0, 0, 0);

        // Si la fecha del ultimo reporte es anterior al viernes, debe mostrarse
        return $ultimaFecha < $viernes->format('Y-m-d');
    }

    // ==================== INSERCION ====================

    /**
     * Inserta un nuevo reporte SST
     *
     * @param array $datos Datos del reporte:
     *   - id_usuario (int)
     *   - id_vehiculo (int|null)
     *   - fecha (string Y-m-d)
     *   - tipo (string: 'accidente' o 'comparendo')
     *   - tipo_evento (string: 'semanal' o 'momento')
     *   - respuesta (string: 'si' o 'no')
     *   - observacion (string|null)
     *   - ubicacion (string|null)
     *   - gravedad (int|null) 1-4 para accidente, 1-3 para comparendo
     * @return int|false ID del registro insertado o false en caso de error
     */
    public function insertarReporte($datos)
    {
        // Validar tipo
        if (!in_array($datos['tipo'], self::TIPOS_VALIDOS, true)) {
            error_log("ReporteConductorSSTModel: insertarReporte - Tipo invalido: " . ($datos['tipo'] ?? 'null'));
            return false;
        }

        // Validar tipo_evento
        if (!in_array($datos['tipo_evento'], self::TIPOS_EVENTO_VALIDOS, true)) {
            error_log("ReporteConductorSSTModel: insertarReporte - Tipo evento invalido: " . ($datos['tipo_evento'] ?? 'null'));
            return false;
        }

        $sql = "INSERT INTO sst_reporte_conductor
                (id_usuario, id_vehiculo, fecha, tipo, tipo_evento, respuesta, observacion, ubicacion, gravedad)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("ReporteConductorSSTModel: insertarReporte - Error preparando SQL: " . $this->db->error);
            return false;
        }

        $idVehiculo = $datos['id_vehiculo'] ?? null;
        $observacion = $datos['observacion'] ?? null;
        $ubicacion = $datos['ubicacion'] ?? null;
        $gravedad = $datos['gravedad'] ?? null;

        $stmt->bind_param(
            "iisssissi",
            $datos['id_usuario'],
            $idVehiculo,
            $datos['fecha'],
            $datos['tipo'],
            $datos['tipo_evento'],
            $datos['respuesta'],
            $observacion,
            $ubicacion,
            $gravedad
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }

        error_log("ReporteConductorSSTModel: insertarReporte - Error ejecutando: " . $stmt->error);
        return false;
    }

    /**
     * Inserta multiples reportes en una transaccion
     *
     * @param array $reportes Array de arrays con datos de cada reporte
     * @return array|false Array de IDs insertados o false en caso de error
     */
    public function guardarReportesBatch($reportes)
    {
        if (empty($reportes)) {
            return [];
        }

        $this->db->begin_transaction();

        try {
            $ids = [];
            foreach ($reportes as $reporte) {
                $id = $this->insertarReporte($reporte);
                if ($id === false) {
                    throw new Exception("Error insertando reporte");
                }
                $ids[] = $id;
            }
            $this->db->commit();
            return $ids;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("ReporteConductorSSTModel: guardarReportesBatch - Error: " . $e->getMessage());
            return false;
        }
    }

    // ==================== LISTADO Y BUSQUEDA ====================

    /**
     * Obtiene reportes con filtros opcionales
     *
     * @param array $filtros Filtros opcionales:
     *   - id_vehiculo (int|null)
     *   - id_usuario (int|null)
     *   - fecha_desde (string|null Y-m-d)
     *   - fecha_hasta (string|null Y-m-d)
     *   - tipo (string|null: 'accidente' o 'comparendo')
     *   - estado (string|null: 'pendiente' o 'revisado')
     * @return array Lista de reportes
     */
    public function obtenerReportes($filtros)
    {
        $sql = "SELECT rcs.*, u.usu_nombre, v.veh_placa,
                       sg.gravedad_validacion, sg.comentario AS comentario_validacion,
                       sg.creado_en AS fecha_validacion, val.usu_nombre AS validador_nombre
                FROM sst_reporte_conductor rcs
                LEFT JOIN usuarios u ON u.idusuarios = rcs.id_usuario
                LEFT JOIN vehiculos v ON v.idvehiculos = rcs.id_vehiculo
                LEFT JOIN sst_seguimiento sg ON sg.id_seguimiento = (
                    SELECT sg2.id_seguimiento FROM sst_seguimiento sg2
                    WHERE sg2.id_reporte = rcs.id
                    ORDER BY sg2.creado_en DESC LIMIT 1
                )
                LEFT JOIN usuarios val ON val.idusuarios = sg.id_validador
                WHERE 1=1";
        $types = '';
        $params = [];

        // Filtro por vehiculo
        if (!empty($filtros['id_vehiculo'])) {
            $sql .= " AND rcs.id_vehiculo = ?";
            $types .= 'i';
            $params[] = $filtros['id_vehiculo'];
        }

        // Filtro por usuario
        if (!empty($filtros['id_usuario'])) {
            $sql .= " AND rcs.id_usuario = ?";
            $types .= 'i';
            $params[] = $filtros['id_usuario'];
        }

        // Filtro por fecha desde
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND rcs.fecha >= ?";
            $types .= 's';
            $params[] = $filtros['fecha_desde'];
        }

        // Filtro por fecha hasta
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND rcs.fecha <= ?";
            $types .= 's';
            $params[] = $filtros['fecha_hasta'];
        }

        // Filtro por tipo
        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], self::TIPOS_VALIDOS, true)) {
            $sql .= " AND rcs.tipo = ?";
            $types .= 's';
            $params[] = $filtros['tipo'];
        }

        // Filtro por estado
        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS_VALIDOS, true)) {
            $sql .= " AND rcs.estado = ?";
            $types .= 's';
            $params[] = $filtros['estado'];
        }

        $sql .= " ORDER BY rcs.creado_en DESC LIMIT 500";

        if (empty($params)) {
            // Sin filtros: ejecutar consulta directa sin bind_param
            $result = $this->db->query($sql);
            if (!$result) {
                error_log("ReporteConductorSSTModel: obtenerReportes - Error en consulta: " . $this->db->error);
                return [];
            }
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }

        return $this->executeAll($sql, $types, $params);
    }

    // ==================== RESUMEN SEMANAL ====================

    /**
     * Obtiene el resumen semanal de cuestionarios SST completados por conductor
     *
     * Para cada conductor activo con vehiculo asignado, indica si completo o no
     * el cuestionario de accidente y comparendo en la semana dada.
     *
     * @param string $fechaInicio Fecha inicio de la semana (Y-m-d, lunes)
     * @param string $fechaFin    Fecha fin de la semana (Y-m-d, domingo)
     * @param int|null $idConductor (opcional) Filtrar por conductor especifico
     * @return array Lista de conductores con indicadores de completitud
     */
    public function obtenerResumenSemanal($fechaInicio, $fechaFin, $idConductor = null)
    {
        $sql = "SELECT u.idusuarios, u.usu_nombre, u.usu_identificacion,
                       v.idvehiculos, v.veh_placa,
                       MAX(CASE WHEN rcs.tipo = 'accidente'
                           AND rcs.tipo_evento = 'semanal'
                           AND rcs.fecha BETWEEN ? AND ? THEN 1 ELSE 0 END) as accidente_completado,
                       MAX(CASE WHEN rcs.tipo = 'comparendo'
                           AND rcs.tipo_evento = 'semanal'
                           AND rcs.fecha BETWEEN ? AND ? THEN 1 ELSE 0 END) as comparendo_completado
                FROM usuarios u
                INNER JOIN vehiculos v ON u.usu_vehiculo = v.idvehiculos
                LEFT JOIN sst_reporte_conductor rcs ON rcs.id_usuario = u.idusuarios
                    AND rcs.tipo_evento = 'semanal'
                    AND rcs.fecha BETWEEN ? AND ?
                WHERE u.usu_estado = 1 AND u.usu_filtro = 1";

        // Los parametros se repiten: fechaInicio, fechaFin para cada CASE + el LEFT JOIN
        // CASE accidente: ?, ?
        // CASE comparendo: ?, ?
        // LEFT JOIN: ?, ?
        // Total: 6 parametros de fecha (3 pares de fechaInicio, fechaFin)
        $params = [
            $fechaInicio, $fechaFin,  // CASE accidente
            $fechaInicio, $fechaFin,  // CASE comparendo
            $fechaInicio, $fechaFin,  // LEFT JOIN
        ];
        $types = 'ssssss';

        if ($idConductor !== null) {
            $sql .= " AND u.idusuarios = ?";
            $params[] = $idConductor;
            $types .= 'i';
        }

        $sql .= " GROUP BY u.idusuarios, v.idvehiculos, v.veh_placa
                  ORDER BY u.usu_nombre";

        return $this->executeAll($sql, $types, $params);
    }

    /**
     * Obtiene un reporte por su ID
     *
     * @param int $id ID del reporte
     * @return array|null Datos del reporte o null
     */
    public function obtenerReportePorId($id)
    {
        $sql = "SELECT * FROM sst_reporte_conductor WHERE id = ? LIMIT 1";
        return $this->executeQuery($sql, "i", [$id]);
    }

    // ==================== ACTUALIZACION ====================

    /**
     * Cambia el estado de un reporte y registra el seguimiento en sst_seguimiento
     *
     * Realiza una transaccion: inserta en sst_seguimiento y actualiza
     * sst_reporte_conductor en una sola operacion atomica.
     *
     * @param int         $id                 ID del reporte
     * @param string      $estadoNuevo        Nuevo estado ('pendiente', 'en_proceso', 'finalizado')
     * @param int|null    $idValidador        ID del usuario que valida (opcional)
     * @param string|null $comentario         Comentario del validador (opcional)
     * @param int|null    $gravedadValidacion Nivel de gravedad segun validador (opcional)
     * @return bool True si la operacion fue exitosa
     */
    public function cambiarEstado($id, $estadoNuevo, $idValidador = null, $comentario = null, $gravedadValidacion = null)
    {
        if (!in_array($estadoNuevo, self::ESTADOS_VALIDOS, true)) {
            error_log("ReporteConductorSSTModel: cambiarEstado - Estado invalido: $estadoNuevo");
            return false;
        }

        $this->db->begin_transaction();
        try {
            // Obtener estado actual del reporte
            $sqlActual = "SELECT estado FROM sst_reporte_conductor WHERE id = ? LIMIT 1";
            $actual = $this->executeQuery($sqlActual, "i", [$id]);
            if (!$actual) {
                throw new \RuntimeException("Reporte no encontrado: $id");
            }
            $estadoAnterior = $actual['estado'];

            // Insertar registro en sst_seguimiento
            $sqlSeguimiento = "INSERT INTO sst_seguimiento
                               (id_reporte, id_validador, estado_anterior, estado_nuevo, gravedad_validacion, comentario)
                               VALUES (?, ?, ?, ?, ?, ?)";
            $stmtSeg = $this->db->prepare($sqlSeguimiento);
            if (!$stmtSeg) {
                throw new \RuntimeException("Error preparando INSERT sst_seguimiento: " . $this->db->error);
            }
            $stmtSeg->bind_param("iisiss", $id, $idValidador, $estadoAnterior, $estadoNuevo, $gravedadValidacion, $comentario);
            if (!$stmtSeg->execute()) {
                throw new \RuntimeException("Error insertando en sst_seguimiento: " . $stmtSeg->error);
            }
            $stmtSeg->close();

            // Actualizar el reporte en sst_reporte_conductor
            $sqlUpdate = "UPDATE sst_reporte_conductor SET estado = ? WHERE id = ?";
            $stmtUpd = $this->db->prepare($sqlUpdate);
            if (!$stmtUpd) {
                throw new \RuntimeException("Error preparando UPDATE sst_reporte_conductor: " . $this->db->error);
            }
            $stmtUpd->bind_param("si", $estadoNuevo, $id);
            if (!$stmtUpd->execute()) {
                throw new \RuntimeException("Error actualizando sst_reporte_conductor: " . $stmtUpd->error);
            }
            $stmtUpd->close();

            $this->db->commit();
            return true;
        } catch (\RuntimeException $e) {
            $this->db->rollback();
            error_log("ReporteConductorSSTModel: cambiarEstado - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene un reporte completo con datos de conductor, vehículo, validador y archivos
     *
     * @param int $id ID del reporte
     * @return array|null Datos completos del reporte o null
     */
    public function obtenerReporteCompleto($id)
    {
        $sql = "SELECT rcs.*,
                       u.usu_nombre AS conductor_nombre,
                       u.usu_identificacion AS conductor_cedula,
                       v.veh_placa
                FROM sst_reporte_conductor rcs
                LEFT JOIN usuarios u ON u.idusuarios = rcs.id_usuario
                LEFT JOIN vehiculos v ON v.idvehiculos = rcs.id_vehiculo
                WHERE rcs.id = ?
                LIMIT 1";

        $reporte = $this->executeQuery($sql, "i", [$id]);

        if ($reporte) {
            $reporte['archivos'] = $this->obtenerArchivos($id);
        }

        return $reporte;
    }

    // ==================== SEGUIMIENTO ====================

    /**
     * Obtiene el historial de seguimiento de cambios de estado de un reporte
     *
     * @param int $idReporte ID del reporte
     * @return array Lista de registros de seguimiento ordenados cronologicamente
     */
    public function obtenerSeguimiento($idReporte)
    {
        $sql = "SELECT sg.*, u.usu_nombre AS validador_nombre
                FROM sst_seguimiento sg
                LEFT JOIN usuarios u ON u.idusuarios = sg.id_validador
                WHERE sg.id_reporte = ?
                ORDER BY sg.creado_en ASC";
        return $this->executeAll($sql, "i", [$idReporte]);
    }

    // ==================== PREGUNTAS PERSONALIZABLES ====================

    /**
     * Obtiene categorias y campos para un tipo de reporte, agrupados por categoria
     *
     * @param string $tipo Tipo de reporte ('accidente' o 'comparendo')
     * @return array Lista de categorias con sus campos internos
     */
    public function obtenerCategoriasYCampos($tipo)
    {
        $sql = "SELECT c.id_categoria, c.nombre AS categoria_nombre,
                       c.descripcion AS categoria_descripcion,
                       cm.id_campo, cm.codigo, cm.etiqueta, cm.tipo_respuesta,
                       cm.requerido, cm.orden, cm.placeholder, cm.ayuda,
                       cm.id_campo_padre, cm.valor_padre
                FROM sst_categorias c
                INNER JOIN sst_campos cm ON cm.id_categoria = c.id_categoria
                WHERE c.tipo = ? AND c.estado = 1 AND cm.estado = 1
                ORDER BY c.orden, cm.orden";
        $rows = $this->executeAll($sql, "s", [$tipo]);

        $categorias = [];
        foreach ($rows as $row) {
            $catId = $row['id_categoria'];
            if (!isset($categorias[$catId])) {
                $categorias[$catId] = [
                    'id_categoria' => $catId,
                    'nombre'       => $row['categoria_nombre'],
                    'descripcion'  => $row['categoria_descripcion'],
                    'campos'       => [],
                ];
            }
            $categorias[$catId]['campos'][] = [
                'id_campo'       => $row['id_campo'],
                'codigo'         => $row['codigo'],
                'etiqueta'       => $row['etiqueta'],
                'tipo_respuesta' => $row['tipo_respuesta'],
                'requerido'      => $row['requerido'],
                'orden'          => $row['orden'],
                'placeholder'    => $row['placeholder'],
                'ayuda'          => $row['ayuda'],
                'id_campo_padre' => $row['id_campo_padre'],
                'valor_padre'    => $row['valor_padre'],
            ];
        }

        return array_values($categorias);
    }

    /**
     * Guarda las respuestas de un reporte en sst_respuestas
     *
     * Inserta o actualiza (ON DUPLICATE KEY UPDATE) cada respuesta
     * usando la unique key (id_reporte, id_campo).
     *
     * @param int   $idReporte  ID del reporte
     * @param array $respuestas Array asociativo [id_campo => valor]
     * @return bool True si todas las respuestas se guardaron correctamente
     */
    public function guardarRespuestas($idReporte, $respuestas)
    {
        if (empty($respuestas)) {
            return true;
        }

        // Mapear codigos de campos a IDs numericos
        $camposMap = $this->mapearCodigosACampos();
        if (empty($camposMap)) {
            error_log("ReporteConductorSSTModel: guardarRespuestas - No se pudieron mapear los codigos de campos");
            return false;
        }

        $this->db->begin_transaction();
        try {
            $sql = "INSERT INTO sst_respuestas (id_reporte, id_campo, valor)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new \RuntimeException("Error preparando INSERT sst_respuestas: " . $this->db->error);
            }

            foreach ($respuestas as $codigo => $valor) {
                if (!isset($camposMap[$codigo])) {
                    continue;
                }
                $idCampo = $camposMap[$codigo];
                $stmt->bind_param("iis", $idReporte, $idCampo, $valor);
                if (!$stmt->execute()) {
                    throw new \RuntimeException("Error insertando respuesta (campo $codigo): " . $stmt->error);
                }
            }

            $stmt->close();
            $this->db->commit();
            return true;
        } catch (\RuntimeException $e) {
            $this->db->rollback();
            error_log("ReporteConductorSSTModel: guardarRespuestas - Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las respuestas de un reporte, agrupadas por categoria
     *
     * @param int $idReporte ID del reporte
     * @return array Lista de categorias con sus respuestas
     */
    public function obtenerRespuestas($idReporte)
    {
        $sql = "SELECT r.id_respuesta, r.id_campo, r.valor,
                       cm.codigo, cm.etiqueta, cm.tipo_respuesta,
                       c.id_categoria, c.nombre AS categoria_nombre
                FROM sst_respuestas r
                INNER JOIN sst_campos cm ON cm.id_campo = r.id_campo
                INNER JOIN sst_categorias c ON c.id_categoria = cm.id_categoria
                WHERE r.id_reporte = ?
                ORDER BY c.orden, cm.orden";
        $rows = $this->executeAll($sql, "i", [$idReporte]);

        $categorias = [];
        foreach ($rows as $row) {
            $catId = $row['id_categoria'];
            if (!isset($categorias[$catId])) {
                $categorias[$catId] = [
                    'id_categoria' => $catId,
                    'nombre'       => $row['categoria_nombre'],
                    'respuestas'   => [],
                ];
            }
            $categorias[$catId]['respuestas'][] = [
                'id_respuesta'  => $row['id_respuesta'],
                'id_campo'      => $row['id_campo'],
                'codigo'        => $row['codigo'],
                'etiqueta'      => $row['etiqueta'],
                'tipo_respuesta' => $row['tipo_respuesta'],
                'valor'         => $row['valor'],
            ];
        }

        return array_values($categorias);
    }

    /**
     * Mapea los codigos de campo a su id_campo
     *
     * Util para convertir un array con keys por codigo
     * a un array con keys por id_campo antes de guardar.
     *
     * @param string|null $tipo Filtrar por tipo ('accidente' o 'comparendo'), null = todos
     * @return array Array asociativo [codigo => id_campo]
     */
    public function mapearCodigosACampos($tipo = null)
    {
        $sql = "SELECT cm.codigo, cm.id_campo
                FROM sst_campos cm
                INNER JOIN sst_categorias c ON c.id_categoria = cm.id_categoria
                WHERE cm.estado = 1";
        $types = '';
        $params = [];

        if ($tipo !== null) {
            $sql .= " AND c.tipo = ?";
            $types = 's';
            $params[] = $tipo;
        }

        $rows = $this->executeAll($sql, $types, $params);
        $mapa = [];
        foreach ($rows as $row) {
            $mapa[$row['codigo']] = (int) $row['id_campo'];
        }
        return $mapa;
    }

    // ==================== GESTION DE FIRMA ====================

    /**
     * Guarda la firma del conductor en la tabla documentos y actualiza el reporte
     *
     * @param string $firmaBase64 Data URI base64 de la firma (data:image/png;base64,...)
     * @param int    $idReporte   ID del reporte al que asociar la firma
     * @param int    $idUsuario   ID del usuario conductor
     * @return int|false ID del documento insertado o false en caso de error
     */
    public function guardarFirma($firmaBase64, $idReporte, $idUsuario)
    {
        if (empty($firmaBase64)) {
            error_log("ReporteConductorSSTModel: guardarFirma - firma vacía");
            return false;
        }

        // Validar que comience con data:image (formato canvas.toDataURL)
        if (strpos($firmaBase64, 'data:image') !== 0) {
            error_log("ReporteConductorSSTModel: guardarFirma - formato inválido");
            return false;
        }

        // Extraer datos base64
        $commaPos = strpos($firmaBase64, ',');
        if ($commaPos === false) {
            return false;
        }
        $base64Data = substr($firmaBase64, $commaPos + 1);
        $decodedData = base64_decode($base64Data);
        if ($decodedData === false) {
            return false;
        }

        // Crear archivo temporal
        $nombreArchivo = "firma_sst_" . $idUsuario . "_" . date("Y-m-d-H-i-s") . ".png";
        $rutaTemporal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nombreArchivo;
        $bytesEscritos = file_put_contents($rutaTemporal, $decodedData);
        if ($bytesEscritos === false) {
            error_log("ReporteConductorSSTModel: guardarFirma - error escribiendo temporal");
            return false;
        }

        // Asegurar directorio de subida
        $this->ensureUploadDir();

        // Generar nombre destino único
        $nombreDestino = date("Y-m-d-H-i-s") . "_" . uniqid() . ".png";
        $rutaDestino = self::UPLOAD_DIR . $nombreDestino;

        // Mover del temporal al directorio de uploads
        if (!rename($rutaTemporal, $rutaDestino)) {
            error_log("ReporteConductorSSTModel: guardarFirma - error moviendo archivo");
            @unlink($rutaTemporal);
            return false;
        }

        // Insertar en documentos
        $sql = "INSERT INTO documentos (doc_fecha, doc_nombre, doc_ruta, doc_tabla, doc_idviene, doc_version)
                VALUES (NOW(), ?, ?, 'sst_reporte_conductor', ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("ReporteConductorSSTModel: guardarFirma - error preparando SQL: " . $this->db->error);
            return false;
        }

        $stmt->bind_param("ssii", $nombreArchivo, $rutaDestino, $idReporte, self::VERSION_FIRMA_SST);
        if (!$stmt->execute()) {
            error_log("ReporteConductorSSTModel: guardarFirma - error insertando: " . $stmt->error);
            return false;
        }

        $idDocumento = $this->db->insert_id;
        $stmt->close();

        // Actualizar firma_documento_id en el reporte
        $sqlUpdate = "UPDATE sst_reporte_conductor SET firma_documento_id = ? WHERE id = ?";
        $stmtUpd = $this->db->prepare($sqlUpdate);
        if ($stmtUpd) {
            $stmtUpd->bind_param("ii", $idDocumento, $idReporte);
            $stmtUpd->execute();
            $stmtUpd->close();
        }

        return $idDocumento;
    }

    // ==================== GESTION DE ARCHIVOS ====================

    /**
     * Obtiene los archivos asociados a un reporte
     *
     * @param int $idReporte ID del reporte
     * @return array Lista de documentos asociados
     */
    public function obtenerArchivos($idReporte)
    {
        $sql = "SELECT * FROM documentos
                WHERE doc_tabla = 'sst_reporte_conductor' AND doc_idviene = ?
                ORDER BY doc_fecha DESC";
        return $this->executeAll($sql, "i", [$idReporte]);
    }

    /**
     * Guarda un archivo asociado a un reporte SST
     *
     * @param array $file Archivo subido ($_FILES)
     * @param int $idReporte ID del reporte
     * @param int $version Version del documento (segun TIPO_TO_VERSION)
     * @return int|false ID del documento insertado o false en caso de error
     */
    public function guardarArchivo($file, $idReporte, $version)
    {
        if (empty($file['tmp_name'])) {
            error_log("ReporteConductorSSTModel: guardarArchivo - tmp_name vacio");
            return false;
        }

        // Validar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            error_log("ReporteConductorSSTModel: guardarArchivo - Tipo MIME no permitido: $mimeType");
            return false;
        }

        // Validar tamano
        if ($file['size'] > self::MAX_FILE_SIZE) {
            error_log("ReporteConductorSSTModel: guardarArchivo - Archivo excede el tamano maximo: " . $file['size']);
            return false;
        }

        // Asegurar directorio de subida
        $this->ensureUploadDir();

        // Generar nombre de archivo unico
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombreArchivo = date("Y-m-d-H-i-s") . "_" . uniqid() . "." . $extension;
        $ruta = self::UPLOAD_DIR . $nombreArchivo;

        if (move_uploaded_file($file['tmp_name'], $ruta)) {
            $sql = "INSERT INTO documentos (doc_fecha, doc_nombre, doc_ruta, doc_tabla, doc_idviene, doc_version)
                    VALUES (NOW(), ?, ?, 'sst_reporte_conductor', ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("ReporteConductorSSTModel: guardarArchivo - Error preparando SQL: " . $this->db->error);
                return false;
            }

            $result = $stmt->bind_param("ssii", $file['name'], $ruta, $idReporte, $version)
                   && $stmt->execute();

            if ($result) {
                return $this->db->insert_id;
            }

            error_log("ReporteConductorSSTModel: guardarArchivo - Error insertando en documentos: " . $stmt->error);
            return false;
        } else {
            error_log("ReporteConductorSSTModel: guardarArchivo - ERROR al mover archivo subido");
        }

        return false;
    }

    /**
     * Elimina un archivo de documentos y del sistema de archivos
     *
     * @param int $idDocumento ID del documento a eliminar
     * @return bool True si se elimino correctamente
     */
    public function eliminarArchivo($idDocumento)
    {
        // Obtener la ruta del archivo antes de eliminar
        $sql = "SELECT doc_ruta FROM documentos
                WHERE iddocumentos = ? AND doc_tabla = 'sst_reporte_conductor'
                LIMIT 1";
        $row = $this->executeQuery($sql, "i", [$idDocumento]);

        if (!$row) {
            error_log("ReporteConductorSSTModel: eliminarArchivo - Documento no encontrado: $idDocumento");
            return false;
        }

        $ruta = $row['doc_ruta'];

        // Eliminar archivo del sistema de archivos
        if (file_exists($ruta)) {
            if (!unlink($ruta)) {
                error_log("ReporteConductorSSTModel: eliminarArchivo - No se pudo eliminar el archivo: $ruta");
            }
        }

        // Eliminar registro de la base de datos
        $sqlDelete = "DELETE FROM documentos WHERE iddocumentos = ? AND doc_tabla = 'sst_reporte_conductor'";
        $stmt = $this->db->prepare($sqlDelete);
        if (!$stmt) {
            error_log("ReporteConductorSSTModel: eliminarArchivo - Error preparando SQL: " . $this->db->error);
            return false;
        }

        $stmt->bind_param("i", $idDocumento);
        $result = $stmt->execute();

        if (!$result) {
            error_log("ReporteConductorSSTModel: eliminarArchivo - Error ejecutando DELETE: " . $stmt->error);
        }

        return $result;
    }

    /**
     * Obtiene la firma del conductor asociada a un reporte
     *
     * @param int $idReporte ID del reporte
     * @return array|null Datos del documento de firma o null
     */
    public function obtenerFirmaReporte($idReporte)
    {
        $sql = "SELECT d.* FROM documentos d
                INNER JOIN sst_reporte_conductor r ON r.firma_documento_id = d.iddocumentos
                WHERE r.id = ?
                LIMIT 1";
        return $this->executeQuery($sql, "i", [$idReporte]);
    }
}
