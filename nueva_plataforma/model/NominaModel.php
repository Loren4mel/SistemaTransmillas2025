<?php
require_once "../config/database.php";

class NominaModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    public function getSedes(): array
    {
        $sql = "SELECT idsedes, sed_nombre FROM sedes WHERE idsedes > 0 ORDER BY sed_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTiposContrato(): array
    {
        return [
            ['id' => 'Empresa', 'nombre' => 'Empresa'],
            ['id' => 'Prestacion de Servicios', 'nombre' => 'Prestacion de Servicios'],
            ['id' => 'Prestacion de servicios', 'nombre' => 'Prestacion de servicios'],
        ];
    }

    public function getUsuariosNomina(): array
    {
        $sql = "SELECT idusuarios, usu_nombre, usu_identificacion
                FROM usuarios
                WHERE usu_ver_nomina = '1'
                ORDER BY usu_nombre";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function calcularPeriodo(int $anio, int $mes, string $quincena): array
    {
        $mes = max(1, min(12, $mes));
        $ultimoDia = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $mes)));

        if ($quincena === 'Primera') {
            $inicio = sprintf('%04d-%02d-01', $anio, $mes);
            $fin = sprintf('%04d-%02d-15', $anio, $mes);
        } elseif ($quincena === 'Segunda') {
            $inicio = sprintf('%04d-%02d-16', $anio, $mes);
            $fin = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia);
        } else {
            $inicio = sprintf('%04d-%02d-01', $anio, $mes);
            $fin = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia);
            $quincena = 'Completo';
        }

        return [
            'anio' => $anio,
            'mes' => $mes,
            'quincena' => $quincena,
            'inicio' => $inicio . ' 00:00:00',
            'fin' => $fin . ' 23:59:59',
            'inicio_sin_tiempo' => $inicio,
            'fin_sin_tiempo' => $fin,
            'dias' => ((strtotime($fin) - strtotime($inicio)) / 86400) + 1,
        ];
    }

    public function contarEmpleados(array $filtros, string $search = ''): int
    {
        [$sql, $params, $types] = $this->buildEmpleadosQuery($filtros, $search, true);
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    public function obtenerEmpleadosPaginados(
        int $start,
        int $length,
        array $filtros,
        string $search = '',
        string $orderColumn = 'trabajador',
        string $orderDir = 'ASC'
    ): array {
        [$sql, $params, $types] = $this->buildEmpleadosQuery($filtros, $search, false);

        $columnasPermitidas = [
            'idusuario' => 'u.idusuarios',
            'trabajador' => 'h.hoj_nombre',
            'tipo_contrato' => 'h.hoj_tipocontrato',
            'cedula' => 'h.hoj_cedula',
            'cargo' => 'c.car_Cargo',
            'sede' => 's.sed_nombre',
            'inicio_contrato' => 'h.hoj_fechaingreso',
            'termina_contrato' => 'h.hoj_fechatermino',
        ];
        $orderBy = $columnasPermitidas[$orderColumn] ?? 'h.hoj_nombre';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql .= " ORDER BY {$orderBy} {$orderDir} LIMIT ?, ?";
        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function buildEmpleadosQuery(array $filtros, string $search, bool $count): array
    {
        $anio = (int) ($filtros['anio'] ?? date('Y'));
        $periodo = $filtros['periodo'];

        $select = $count
            ? "SELECT COUNT(*) AS total"
            : "SELECT
                h.idhojadevida,
                u.idusuarios AS idusuario,
                CONCAT(h.hoj_nombre, ' ', h.hoj_apellido) AS trabajador,
                h.hoj_tipocontrato AS tipo_contrato,
                h.hoj_cedula AS cedula,
                c.car_Cargo AS cargo,
                COALESCE(sc.salario, 0) AS salario,
                COALESCE(sc.auxilio, 0) AS auxilio,
                COALESCE(sc.otros, 0) AS otros,
                s.sed_nombre AS sede,
                h.hoj_fechaingreso AS inicio_contrato,
                h.hoj_fechatermino AS termina_contrato,
                h.hoj_estado AS estado,
                COALESCE(asistencia.dias_trabajados, 0) AS dias_trabajados,
                COALESCE(asistencia.descansos, 0) AS descansos,
                COALESCE(asistencia.dias_no_trabajados, 0) AS dias_no_trabajados,
                COALESCE(asistencia.incapacidades, 0) AS incapacidades,
                COALESCE(horas.horas_normales, 0) AS horas_normales,
                COALESCE(horas.horas_festivas, 0) AS horas_festivas";

        $sql = "{$select}
                FROM hojadevida h
                INNER JOIN sedes s ON h.hoj_sede = s.idsedes
                INNER JOIN usuarios u
                    ON u.usu_identificacion = h.hoj_cedula
                    AND u.usu_ver_nomina = '1'
                LEFT JOIN cargo c ON c.idcargo = h.hoj_cargo
                LEFT JOIN salarios_cargos sc
                    ON sc.id_relCargo = h.hoj_cargo
                    AND sc.anio = ?
                LEFT JOIN (
                    SELECT
                        seg_idusuario,
                        SUM(CASE WHEN seg_motivo = 'Ingreso' THEN 1 ELSE 0 END) AS dias_trabajados,
                        SUM(CASE WHEN seg_motivo = 'descanso' THEN 1 ELSE 0 END) AS descansos,
                        SUM(CASE
                            WHEN seg_motivo IN ('Se devolvio', 'Sancionado', 'No trabajo', 'descanso no remunerado', 'Reposicion por falla')
                            THEN 1 ELSE 0
                        END) AS dias_no_trabajados,
                        SUM(CASE WHEN seg_motivo = 'Incapacidad' THEN 1 ELSE 0 END) AS incapacidades
                    FROM seguimiento_user
                    WHERE seg_fechaingreso >= ?
                        AND seg_fechaingreso <= ?
                    GROUP BY seg_idusuario
                ) asistencia ON asistencia.seg_idusuario = u.idusuarios
                LEFT JOIN (
                    SELECT
                        s.seg_idusuario,
                        SUM(CASE
                            WHEN DAYOFWEEK(s.seg_fechaingreso) != 1
                                AND NOT EXISTS (
                                    SELECT 1
                                    FROM seguimiento_user f
                                    WHERE f.seg_idusuario = s.seg_idusuario
                                        AND DATE(f.seg_fechaingreso) = DATE(s.seg_fechaingreso)
                                        AND f.seg_motivo IN ('descanso', 'Festivo en vacaciones')
                                )
                            THEN s.seg_horas_trabajadas ELSE 0
                        END) AS horas_normales,
                        SUM(CASE
                            WHEN DAYOFWEEK(s.seg_fechaingreso) = 1
                                OR EXISTS (
                                    SELECT 1
                                    FROM seguimiento_user f
                                    WHERE f.seg_idusuario = s.seg_idusuario
                                        AND DATE(f.seg_fechaingreso) = DATE(s.seg_fechaingreso)
                                        AND f.seg_motivo IN ('descanso', 'Festivo en vacaciones')
                                )
                            THEN s.seg_horas_trabajadas ELSE 0
                        END) AS horas_festivas
                    FROM seguimiento_user s
                    WHERE s.seg_motivo = 'IngresoHoras'
                        AND s.seg_fechaingreso >= ?
                        AND s.seg_fechaingreso <= ?
                    GROUP BY s.seg_idusuario
                ) horas ON horas.seg_idusuario = u.idusuarios";

        $params = [
            $anio,
            $periodo['inicio'],
            $periodo['fin'],
            $periodo['inicio'],
            $periodo['fin'],
        ];
        $types = 'issss';
        $where = ["h.idhojadevida > 0"];

        if (($periodo['quincena'] ?? '') !== 'Completo') {
            $where[] = "(h.hoj_fechatermino IS NULL
                OR h.hoj_fechatermino = ''
                OR (? BETWEEN h.hoj_fechaingreso AND h.hoj_fechatermino)
                OR (h.hoj_fechaingreso BETWEEN ? AND ?
                    AND h.hoj_fechatermino BETWEEN ? AND ?))";
            $params[] = $periodo['inicio_sin_tiempo'];
            $params[] = $periodo['inicio_sin_tiempo'];
            $params[] = $periodo['fin_sin_tiempo'];
            $params[] = $periodo['inicio_sin_tiempo'];
            $params[] = $periodo['fin_sin_tiempo'];
            $types .= 'sssss';
        }

        if (!empty($filtros['sede'])) {
            $where[] = "h.hoj_sede = ?";
            $params[] = (int) $filtros['sede'];
            $types .= 'i';
        }

        if (!empty($filtros['usuario'])) {
            $where[] = "u.idusuarios = ?";
            $params[] = (int) $filtros['usuario'];
            $types .= 'i';
        }

        if (!empty($filtros['tipo_contrato'])) {
            $where[] = "h.hoj_tipocontrato = ?";
            $params[] = $filtros['tipo_contrato'];
            $types .= 's';
        }

        if ($search !== '') {
            $where[] = "(h.hoj_nombre LIKE ?
                OR h.hoj_apellido LIKE ?
                OR h.hoj_cedula LIKE ?
                OR c.car_Cargo LIKE ?
                OR s.sed_nombre LIKE ?)";
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
            $types .= 'sssss';
        }

        $sql .= " WHERE " . implode(' AND ', $where);

        return [$sql, $params, $types];
    }
}
