<?php
require_once __DIR__ . '/../config/database.php';

class PrimasModel
{
    private mysqli $db;

    private array $motivosPrima = [
        'Ingreso',
        'No trabajo',
        'Sancionado',
        'Incapacidad',
        'Se devolvio',
        'Positivo Covid',
        'Cancelacion contrato',
        'Abandono de puesto',
        'Vacaciones',
        'Descanso',
        'descanso',
        'Ingreso por horas',
        'Descanso no remunerado',
        'descanso no remunerado',
        'Dia de sancion Ps',
        'Reposicion por falla',
        'Festivo en vacaciones',
        'licencia de maternidad',
        'LICENCIA POR LUTO',
        'PERMISO NO REMUNERADO',
        'PAGO DE INCAPACIDAD AL 66',
        'incapasidad al 50 porciento',
        'dia salario minimo',
        'IngresoHoras',
        'Incapasidad paga por la EPS',
    ];

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->asegurarColumnasLiquidacion();
    }

    private function asegurarColumnasLiquidacion(): void
    {
        $result = $this->db->query('DESCRIBE primas');
        if (!$result) {
            return;
        }

        $columnas = [];
        while ($row = $result->fetch_assoc()) {
            $columnas[$row['Field']] = true;
        }

        $definiciones = [
            'pri_dias_prima' => "ADD COLUMN pri_dias_prima DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER pri_img_compro",
            'pri_dias_reales' => "ADD COLUMN pri_dias_reales DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER pri_dias_prima",
            'pri_dias_proyectados' => "ADD COLUMN pri_dias_proyectados DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER pri_dias_reales",
            'pri_valor_prima' => "ADD COLUMN pri_valor_prima DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pri_dias_proyectados",
            'pri_salario' => "ADD COLUMN pri_salario DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pri_valor_prima",
            'pri_auxilio' => "ADD COLUMN pri_auxilio DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pri_salario",
            'pri_es_proyectada' => "ADD COLUMN pri_es_proyectada TINYINT(1) NOT NULL DEFAULT 0 AFTER pri_auxilio",
            'pri_fecha_liquidacion' => "ADD COLUMN pri_fecha_liquidacion DATETIME NULL AFTER pri_es_proyectada",
            'pri_id_liquida' => "ADD COLUMN pri_id_liquida VARCHAR(10) NOT NULL DEFAULT '' AFTER pri_fecha_liquidacion",
            'pri_firma_ruta' => "ADD COLUMN pri_firma_ruta VARCHAR(255) NULL AFTER pri_id_liquida",
            'pri_fecha_firma' => "ADD COLUMN pri_fecha_firma DATETIME NULL AFTER pri_firma_ruta",
            'pri_pdf_firmado_ruta' => "ADD COLUMN pri_pdf_firmado_ruta VARCHAR(255) NULL AFTER pri_fecha_firma",
            'pri_fecha_pdf_firmado' => "ADD COLUMN pri_fecha_pdf_firmado DATETIME NULL AFTER pri_pdf_firmado_ruta",
        ];

        $faltantes = [];
        foreach ($definiciones as $columna => $definicion) {
            if (!isset($columnas[$columna])) {
                $faltantes[] = $definicion;
            }
        }

        if (!empty($faltantes)) {
            $this->db->query('ALTER TABLE primas ' . implode(', ', $faltantes));
        }
    }

    public function getSedes(int $rolUsuario, int $sedeUsuario): array
    {
        $sql = "SELECT idsedes, sed_nombre
                FROM sedes
                WHERE idsedes > 0 AND sed_principal = 'si'";
        $params = [];
        $types = '';

        if (!in_array($rolUsuario, [1, 12], true) && $sedeUsuario > 0) {
            $sql .= " AND idsedes = ?";
            $params[] = $sedeUsuario;
            $types .= 'i';
        }

        $sql .= " ORDER BY sed_nombre ASC";

        return $this->fetchAll($sql, $types, $params);
    }

    public function getUsuariosNomina(int $rolUsuario, int $sedeUsuario): array
    {
        $sql = "SELECT u.idusuarios, u.usu_nombre, u.usu_identificacion
                FROM usuarios u
                INNER JOIN hojadevida h ON h.hoj_cedula = u.usu_identificacion
                WHERE u.usu_ver_nomina = '1'";
        $params = [];
        $types = '';

        if (!in_array($rolUsuario, [1, 12], true) && $sedeUsuario > 0) {
            $sql .= " AND h.hoj_sede = ?";
            $params[] = $sedeUsuario;
            $types .= 'i';
        }

        $sql .= " GROUP BY u.idusuarios, u.usu_nombre, u.usu_identificacion
                  ORDER BY u.usu_nombre ASC";

        return $this->fetchAll($sql, $types, $params);
    }

    public function calcularPeriodoPrima(int $anio, string $semestre): array
    {
        $anio = max(2020, min(2100, $anio));
        $semestre = $semestre === 'Segunda' ? 'Segunda' : 'Primera';

        if ($semestre === 'Segunda') {
            $inicio = sprintf('%04d-07-01', $anio);
            $fin = sprintf('%04d-12-31', $anio);
        } else {
            $inicio = sprintf('%04d-01-01', $anio);
            $fin = sprintf('%04d-06-30', $anio);
        }

        return [
            'anio' => $anio,
            'semestre' => $semestre,
            'inicio' => $inicio . ' 00:00:00',
            'fin' => $fin . ' 23:59:59',
            'inicio_sin_tiempo' => $inicio,
            'fin_sin_tiempo' => $fin,
        ];
    }

    public function obtenerPrimas(array $filtros, string $search = '', string $orderColumn = 'trabajador', string $orderDir = 'ASC'): array
    {
        $periodo = $filtros['periodo'];
        $anio = (int) $filtros['anio'];
        $params = [$anio];
        $types = 'i';

        $where = [
            "h.idhojadevida > 0",
            "h.hoj_tipocontrato = 'Empresa'",
            "h.hoj_fechaingreso <= ?",
            "(h.hoj_fechatermino IS NULL
                OR h.hoj_fechatermino = '0000-00-00'
                OR h.hoj_fechatermino = ''
                OR h.hoj_fechatermino >= ?)",
        ];
        $params[] = $periodo['fin_sin_tiempo'];
        $params[] = $periodo['inicio_sin_tiempo'];
        $types .= 'ss';

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

        if (($filtros['estado'] ?? '') === 'Retirado') {
            $where[] = "h.hoj_fechatermino IS NOT NULL AND h.hoj_fechatermino <> '0000-00-00' AND h.hoj_fechatermino <> ''";
        } elseif (($filtros['estado'] ?? '') === 'Trabajando') {
            $where[] = "(h.hoj_fechatermino IS NULL OR h.hoj_fechatermino = '0000-00-00' OR h.hoj_fechatermino = '')";
            $where[] = "u.usu_estado = '1'";
            $where[] = "u.usu_filtro = '1'";
            $where[] = "h.hoj_estado = 'Activo'";
        }

        if ($search !== '') {
            $where[] = "(h.hoj_nombre LIKE ? OR h.hoj_apellido LIKE ? OR h.hoj_cedula LIKE ? OR c.car_Cargo LIKE ? OR s.sed_nombre LIKE ?)";
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like, $like);
            $types .= 'sssss';
        }

        $sql = "SELECT
                    h.idhojadevida,
                    h.hoj_nombre,
                    h.hoj_apellido,
                    h.hoj_tipocontrato,
                    h.hoj_cedula,
                    h.hoj_fechaingreso,
                    h.hoj_fechatermino,
                    h.hoj_fech_año_act,
                    h.hoj_firma,
                    h.hoj_cuen,
                    h.hoj_tcuenta,
                    h.hoj_banco,
                    h.hoj_sede,
                    s.sed_nombre,
                    u.idusuarios AS idusuario,
                    COALESCE(c.car_Cargo, '') AS cargo,
                    COALESCE(sc.salario, 0) AS salario,
                    COALESCE(sc.auxilio, 0) AS auxilio
                FROM hojadevida h
                INNER JOIN sedes s ON h.hoj_sede = s.idsedes
                INNER JOIN usuarios u
                    ON u.usu_identificacion = h.hoj_cedula
                    AND u.usu_ver_nomina = '1'
                LEFT JOIN cargo c ON c.idcargo = h.hoj_cargo
                LEFT JOIN salarios_cargos sc
                    ON sc.id_relCargo = h.hoj_cargo
                    AND sc.anio = ?
                WHERE " . implode(' AND ', $where);

        $columnasPermitidas = [
            'idusuario' => 'u.idusuarios',
            'trabajador' => 'h.hoj_nombre',
            'tipo_contrato' => 'h.hoj_tipocontrato',
            'cedula' => 'h.hoj_cedula',
            'cargo' => 'c.car_Cargo',
            'salario' => 'sc.salario',
            'auxilio' => 'sc.auxilio',
            'total_dias_prima' => 'h.hoj_nombre',
            'total_prima' => 'h.hoj_nombre',
            'inicio_contrato' => 'h.hoj_fechaingreso',
            'termina_contrato' => 'h.hoj_fechatermino',
        ];
        $orderBy = $columnasPermitidas[$orderColumn] ?? 'h.hoj_nombre';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$orderBy} {$orderDir}";

        $empleados = $this->fetchAll($sql, $types, $params);
        $filas = [];

        $completarPeriodo = !empty($filtros['completar_periodo']);

        foreach ($empleados as $empleado) {
            $fila = $this->armarFilaPrima($empleado, $periodo, $completarPeriodo);
            if ($fila !== null) {
                $filas[] = $fila;
            }
        }

        return $filas;
    }

    public function obtenerRegistroPrima(int $idUsuario, string $fechaInicio): ?array
    {
        $sql = "SELECT p.*, u.usu_nombre AS admin_nombre
                FROM primas p
                LEFT JOIN usuarios u ON u.idusuarios = p.pri_idadminconfi
                WHERE p.pri_idusu = ? AND p.pri_fecha_inicio = ?
                ORDER BY p.idprimas DESC
                LIMIT 1";
        $rows = $this->fetchAll($sql, 'is', [$idUsuario, $fechaInicio]);
        return $rows[0] ?? null;
    }

    public function confirmarPago(int $idUsuario, string $fechaInicio, string $fechaFin, string $semestre, string $confirma): bool
    {
        $stmt = $this->db->prepare("UPDATE primas SET pri_confirma = ? WHERE pri_idusu = ? AND pri_fecha_inicio = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sis', $confirma, $idUsuario, $fechaInicio);
        return $stmt->execute() && $stmt->affected_rows >= 0;
    }

    public function confirmarAdmin(int $idUsuario, string $fechaInicio, string $fechaFin, string $semestre, string $confirma, int $idAdmin): bool
    {
        $fecha = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE primas
            SET pri_confiAdmin = ?, pri_fechaadminconfi = ?, pri_idadminconfi = ?, pri_fecha_fin = ?, pri_semestre = ?
            WHERE pri_idusu = ? AND pri_fecha_inicio = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ssissis', $confirma, $fecha, $idAdmin, $fechaFin, $semestre, $idUsuario, $fechaInicio);
        return $stmt->execute();
    }

    public function liquidarPrima(array $datos, int $idLiquida, bool $reliquidar = false): array
    {
        $idUsuario = (int) ($datos['id_usuario'] ?? 0);
        $fechaInicio = (string) ($datos['fecha_inicio'] ?? '');
        $fechaFin = (string) ($datos['fecha_fin'] ?? '');
        $semestre = (string) ($datos['semestre'] ?? '');

        if ($idUsuario <= 0 || $fechaInicio === '' || $fechaFin === '' || $semestre === '') {
            return ['success' => false, 'message' => 'Datos incompletos para liquidar la prima.'];
        }

        $registro = $this->obtenerRegistroPrima($idUsuario, $fechaInicio);
        if ($registro && !$reliquidar) {
            return ['success' => false, 'message' => 'Esta prima ya esta liquidada. Usa Reliquidar para actualizarla.'];
        }

        $ruta = (string) ($datos['ruta_desprendible'] ?? '');
        $diasPrima = (float) ($datos['total_dias_prima'] ?? 0);
        $diasReales = (float) ($datos['total_dias_prima_real'] ?? 0);
        $diasProyectados = (float) ($datos['dias_proyectados'] ?? 0);
        $valorPrima = (float) ($datos['total_prima'] ?? 0);
        $salario = (float) ($datos['salario'] ?? 0);
        $auxilio = (float) ($datos['auxilio'] ?? 0);
        $esProyectada = $diasProyectados > 0 ? 1 : 0;
        $fechaLiquidacion = date('Y-m-d H:i:s');

        if ($registro) {
            $stmt = $this->db->prepare("UPDATE primas
                SET pri_fecha_fin = ?,
                    pri_semestre = ?,
                    pri_docprima = ?,
                    pri_dias_prima = ?,
                    pri_dias_reales = ?,
                    pri_dias_proyectados = ?,
                    pri_valor_prima = ?,
                    pri_salario = ?,
                    pri_auxilio = ?,
                    pri_es_proyectada = ?,
                    pri_fecha_liquidacion = ?,
                    pri_id_liquida = ?,
                    pri_firma_ruta = NULL,
                    pri_fecha_firma = NULL,
                    pri_pdf_firmado_ruta = NULL,
                    pri_fecha_pdf_firmado = NULL,
                    pri_fechaconfirmausu = '0000-00-00'
                WHERE pri_idusu = ? AND pri_fecha_inicio = ?");
            if (!$stmt) {
                return ['success' => false, 'message' => 'No fue posible preparar la reliquidacion.'];
            }
            $idLiquidaTexto = (string) $idLiquida;
            $stmt->bind_param(
                'sssddddddissis',
                $fechaFin,
                $semestre,
                $ruta,
                $diasPrima,
                $diasReales,
                $diasProyectados,
                $valorPrima,
                $salario,
                $auxilio,
                $esProyectada,
                $fechaLiquidacion,
                $idLiquidaTexto,
                $idUsuario,
                $fechaInicio
            );
            $ok = $stmt->execute();
            return [
                'success' => $ok,
                'message' => $ok ? 'Prima reliquidada correctamente.' : 'No fue posible reliquidar la prima.',
            ];
        }

        $confirmaPago = 'no';
        $confirmaUsuario = '';
        $fechaConfirmacionUsuario = '0000-00-00';
        $idAdminConfirma = '';
        $fechaAdminConfirma = '0000-00-00 00:00:00';
        $confirmaAdmin = '';
        $imagenComprobante = '';
        $idLiquidaTexto = (string) $idLiquida;
        $stmt = $this->db->prepare("INSERT INTO primas
            (pri_confirma, pri_fecha_inicio, pri_fecha_fin, pri_idusu, pri_semestre, pri_fechaconfirmausu,
             pri_idadminconfi, pri_fechaadminconfi, pri_docprima, pri_confirmaUsus, pri_confiAdmin, pri_img_compro,
             pri_dias_prima, pri_dias_reales, pri_dias_proyectados, pri_valor_prima, pri_salario, pri_auxilio,
             pri_es_proyectada, pri_fecha_liquidacion, pri_id_liquida)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'message' => 'No fue posible preparar la liquidacion.'];
        }
        $stmt->bind_param(
            'sssissssssssddddddiss',
            $confirmaPago,
            $fechaInicio,
            $fechaFin,
            $idUsuario,
            $semestre,
            $fechaConfirmacionUsuario,
            $idAdminConfirma,
            $fechaAdminConfirma,
            $ruta,
            $confirmaUsuario,
            $confirmaAdmin,
            $imagenComprobante,
            $diasPrima,
            $diasReales,
            $diasProyectados,
            $valorPrima,
            $salario,
            $auxilio,
            $esProyectada,
            $fechaLiquidacion,
            $idLiquidaTexto
        );
        $ok = $stmt->execute();
        return [
            'success' => $ok,
            'message' => $ok ? 'Prima liquidada correctamente.' : 'No fue posible liquidar la prima.',
        ];
    }

    public function eliminarLiquidacionPrima(int $idUsuario, string $fechaInicio): array
    {
        if ($idUsuario <= 0 || $fechaInicio === '') {
            return ['success' => false, 'message' => 'Datos incompletos para eliminar la liquidacion.'];
        }

        $stmt = $this->db->prepare("DELETE FROM primas WHERE pri_idusu = ? AND pri_fecha_inicio = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'No fue posible preparar la eliminacion.'];
        }

        $stmt->bind_param('is', $idUsuario, $fechaInicio);
        $ok = $stmt->execute();

        return [
            'success' => $ok,
            'message' => $ok ? 'Liquidacion eliminada correctamente.' : 'No fue posible eliminar la liquidacion.',
        ];
    }

    public function enviarPrimaPendiente(int $idUsuario, string $fechaInicio, string $fechaFin, string $semestre, string $ruta): array
    {
        if ($idUsuario <= 0 || $fechaInicio === '' || $ruta === '') {
            return ['success' => false, 'message' => 'Datos incompletos para enviar la prima.'];
        }

        $registro = $this->obtenerRegistroPrima($idUsuario, $fechaInicio);
        if (!$this->primaEstaLiquidada($registro)) {
            return ['success' => false, 'message' => 'Primero debes liquidar la prima antes de enviarla.'];
        }

        $estadoUsuario = 'no';
        $stmt = $this->db->prepare("UPDATE primas
            SET pri_docprima = ?,
                pri_confirmaUsus = ?,
                pri_fecha_fin = ?,
                pri_semestre = ?
            WHERE pri_idusu = ? AND pri_fecha_inicio = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'No fue posible preparar el envio.'];
        }

        $stmt->bind_param('ssssis', $ruta, $estadoUsuario, $fechaFin, $semestre, $idUsuario, $fechaInicio);
        $ok = $stmt->execute();

        return [
            'success' => $ok,
            'message' => $ok ? 'Prima enviada a pendientes correctamente.' : 'No fue posible enviar la prima a pendientes.',
        ];
    }

    public function guardarComprobante(array $ids, array $archivo, string $fechaInicio, string $fechaFin, string $semestre): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Selecciona al menos un empleado.'];
        }
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Selecciona un comprobante valido.'];
        }

        $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        if (!in_array($extension, $permitidas, true)) {
            return ['success' => false, 'message' => 'Formato no permitido. Usa imagen o PDF.'];
        }

        $directorio = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'img_nomina' . DIRECTORY_SEPARATOR . 'primas';
        if (!is_dir($directorio) && !@mkdir($directorio, 0777, true)) {
            return ['success' => false, 'message' => 'No fue posible crear la carpeta de comprobantes.'];
        }

        $nombreArchivo = 'prima_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = $directorio . DIRECTORY_SEPARATOR . $nombreArchivo;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['success' => false, 'message' => 'No fue posible guardar el archivo.'];
        }

        foreach ($ids as $idUsuario) {
            $idUsuario = (int) $idUsuario;
            if ($idUsuario <= 0) {
                continue;
            }
            $stmt = $this->db->prepare("UPDATE primas SET pri_img_compro = ? WHERE pri_idusu = ? AND pri_fecha_inicio = ?");
            if ($stmt) {
                $stmt->bind_param('sis', $nombreArchivo, $idUsuario, $fechaInicio);
                $stmt->execute();
            }
        }

        return ['success' => true, 'message' => 'Comprobante cargado correctamente.'];
    }

    public function obtenerDetalleDias(int $idUsuario, string $fechaInicio, string $fechaFin, string $grupo, bool $completarPeriodo = false): array
    {
        if ($grupo === 'sin_registro') {
            return $this->obtenerDiasSinRegistro($idUsuario, $fechaInicio, $fechaFin);
        }

        $grupos = $this->motivosPorGrupo($grupo);
        if (empty($grupos) && $grupo !== 'total') {
            return [];
        }

        $motivos = $grupo === 'total'
            ? array_values(array_unique(array_merge(
                $this->motivosPorGrupo('ingresos'),
                $this->motivosPorGrupo('descanso'),
                $this->motivosPorGrupo('incapacidad_empresa'),
                $this->motivosPorGrupo('vacaciones'),
                $this->motivosPorGrupo('licencias'),
                $this->motivosPorGrupo('incapacidad_eps')
            )))
            : $grupos;

        $placeholders = implode(',', array_fill(0, count($motivos), '?'));
        $sql = "SELECT
                    DATE(seg_fechaingreso) AS fecha,
                    seg_motivo AS motivo,
                    COALESCE(seg_descr, '') AS descripcion,
                    COALESCE(seg_horas_trabajadas, '') AS horas
                FROM seguimiento_user
                WHERE seg_idusuario = ?
                    AND seg_fechaingreso BETWEEN ? AND ?
                    AND seg_motivo IN ($placeholders)
                ORDER BY seg_fechaingreso ASC, idseguimiento_user ASC";

        $params = array_merge([$idUsuario, $fechaInicio, $fechaFin], $motivos);
        $types = 'iss' . str_repeat('s', count($motivos));
        $filas = $this->fetchAll($sql, $types, $params);

        if ($grupo === 'total' && $completarPeriodo) {
            $diasProyectados = $this->calcularDiasProyectados(substr($fechaInicio, 0, 10), substr($fechaFin, 0, 10));
            if ($diasProyectados > 0) {
                $hoy = new DateTimeImmutable(date('Y-m-d'));
                $fin = new DateTimeImmutable(substr($fechaFin, 0, 10));
                $temp = $hoy;
                while ($temp <= $fin) {
                    if ((int) $temp->format('d') !== 31) {
                        $filas[] = [
                            'fecha' => $temp->format('Y-m-d'),
                            'motivo' => 'Proyectado',
                            'descripcion' => 'Dia proyectado hasta fin del periodo de prima',
                            'horas' => '',
                        ];
                    }
                    $temp = $temp->modify('+1 day');
                }
            }
        }

        return $filas;
    }

    private function armarFilaPrima(array $empleado, array $periodo, bool $completarPeriodo): ?array
    {
        $fechaInicioContrato = $this->fechaValida($empleado['hoj_fech_año_act'] ?? '')
            ? substr((string) $empleado['hoj_fech_año_act'], 0, 10)
            : substr((string) $empleado['hoj_fechaingreso'], 0, 10);
        $fechaFinContrato = $this->fechaValida($empleado['hoj_fechatermino'] ?? '')
            ? substr((string) $empleado['hoj_fechatermino'], 0, 10)
            : '';

        $inicioPeriodo = substr($periodo['inicio'], 0, 10);
        $finPeriodo = substr($periodo['fin'], 0, 10);
        $fechaInicia = max($fechaInicioContrato, $inicioPeriodo);
        $fechaFinaliza = $fechaFinContrato !== '' && $fechaFinContrato < $finPeriodo ? $fechaFinContrato : $finPeriodo;

        if ($fechaFinaliza < $inicioPeriodo || $fechaInicia > $finPeriodo || $fechaFinaliza < $fechaInicia) {
            return null;
        }

        $conteo = $this->obtenerConteoPorMotivo($fechaInicia . ' 00:00:00', $fechaFinaliza . ' 23:59:59', (int) $empleado['idusuario']);
        $diasSinRegistro = count($this->obtenerDiasSinRegistro((int) $empleado['idusuario'], $fechaInicia . ' 00:00:00', $fechaFinaliza . ' 23:59:59'));
        $ingreso = $conteo['Ingreso'] + $conteo['Reposicion por falla'] + $conteo['Ingreso por horas'] + $conteo['IngresoHoras'];
        $descanso = $conteo['descanso'] + $conteo['Descanso'];
        $noTrabajo = $conteo['No trabajo'] + $conteo['Sancionado'];
        $incapacidad = $conteo['Incapacidad'];
        $vacaciones = $conteo['Vacaciones'];
        $licencias = $conteo['licencia de maternidad'] + $conteo['LICENCIA POR LUTO'];
        $incapacidadEps = $conteo['Incapasidad paga por la EPS'];
        $totalDiasPrimaReal = $ingreso + $descanso + $incapacidad + $vacaciones + $licencias + $incapacidadEps;
        $diasProyectados = $completarPeriodo ? $this->calcularDiasProyectados($fechaInicia, $fechaFinaliza) : 0;
        $totalDiasPrima = $totalDiasPrimaReal + $diasProyectados;

        $salario = (float) ($empleado['salario'] ?? 0);
        $auxilio = (float) ($empleado['auxilio'] ?? 0);
        $totalPrima = round($totalDiasPrima * (($salario + $auxilio) / 360));
        $registroPrima = $this->obtenerRegistroPrima((int) $empleado['idusuario'], $periodo['inicio']);

        $liquidada = $this->primaEstaLiquidada($registroPrima);
        $diasDesprendible = $liquidada ? (float) ($registroPrima['pri_dias_prima'] ?? $totalDiasPrima) : $totalDiasPrima;
        $valorDesprendible = $liquidada ? (float) ($registroPrima['pri_valor_prima'] ?? $totalPrima) : $totalPrima;
        $salarioDesprendible = $liquidada ? (float) ($registroPrima['pri_salario'] ?? $salario) : $salario;
        $auxilioDesprendible = $liquidada ? (float) ($registroPrima['pri_auxilio'] ?? $auxilio) : $auxilio;

        $rutaDesprendible = '../view/Primas/desprendible.php?' . http_build_query([
            'cedula' => $empleado['hoj_cedula'],
            'nombre' => trim($empleado['hoj_nombre'] . ' ' . $empleado['hoj_apellido']),
            'cargo' => $empleado['cargo'],
            'fechaini' => $fechaInicia,
            'fechafin' => $periodo['fin'],
            'diastrabajados' => $diasDesprendible,
            'sueldo' => $valorDesprendible,
            'totaldeveng' => $valorDesprendible,
            'firma' => $empleado['hoj_firma'],
            'sede' => $empleado['sed_nombre'],
            'transporte' => $auxilioDesprendible,
            'sueldobasico' => $salarioDesprendible,
            'semestre' => $periodo['semestre'],
        ]);

        $confirmadoTexto = 'Pendiente';
        if (($registroPrima['pri_confirmaUsus'] ?? '') === 'Si') {
            $confirmadoTexto = 'Firmado el ' . ($registroPrima['pri_fechaconfirmausu'] ?? '');
        } elseif (($registroPrima['pri_confirmaUsus'] ?? '') === 'no') {
            $confirmadoTexto = 'Enviado a pendientes';
        }

        return [
            'idusuario' => (int) $empleado['idusuario'],
            'trabajador' => trim($empleado['hoj_nombre'] . ' ' . $empleado['hoj_apellido']),
            'tipo_contrato' => $empleado['hoj_tipocontrato'],
            'cedula' => $empleado['hoj_cedula'],
            'cargo' => $empleado['cargo'],
            'salario' => $salario,
            'auxilio' => $auxilio,
            'ingresos' => $ingreso,
            'descanso' => $descanso,
            'dias_no_trabajados' => $noTrabajo,
            'dias_sin_registro' => $diasSinRegistro,
            'incapacidad_empresa' => $incapacidad,
            'vacaciones' => $vacaciones,
            'licencias' => $licencias,
            'total_dias_prima' => $totalDiasPrima,
            'total_dias_prima_real' => $totalDiasPrimaReal,
            'dias_proyectados' => $diasProyectados,
            'prima_proyectada' => $diasProyectados > 0,
            'total_prima' => $totalPrima,
            'inicio_contrato' => $fechaInicioContrato,
            'termina_contrato' => $fechaFinContrato,
            'fecha_inicio_prima' => $periodo['inicio'],
            'fecha_fin_prima' => $periodo['fin'],
            'fecha_inicio_calculo' => $fechaInicia . ' 00:00:00',
            'fecha_fin_calculo' => $fechaFinaliza . ' 23:59:59',
            'semestre' => $periodo['semestre'],
            'ruta_desprendible' => ($registroPrima['pri_pdf_firmado_ruta'] ?? '') !== ''
                ? $registroPrima['pri_pdf_firmado_ruta']
                : $rutaDesprendible,
            'ruta_desprendible_generada' => $rutaDesprendible,
            'liquidada' => $liquidada,
            'fecha_liquidacion' => $registroPrima['pri_fecha_liquidacion'] ?? '',
            'dias_liquidados' => (float) ($registroPrima['pri_dias_prima'] ?? 0),
            'valor_liquidado' => (float) ($registroPrima['pri_valor_prima'] ?? 0),
            'liquidacion_proyectada' => ((int) ($registroPrima['pri_es_proyectada'] ?? 0)) === 1,
            'firma_ruta' => $registroPrima['pri_firma_ruta'] ?? '',
            'pdf_firmado_ruta' => $registroPrima['pri_pdf_firmado_ruta'] ?? '',
            'pago_confirmado' => $registroPrima['pri_confirma'] ?? 'no',
            'confirmado_usuario' => $confirmadoTexto,
            'comprobante' => $registroPrima['pri_img_compro'] ?? '',
            'cuenta' => $empleado['hoj_cuen'] ?? '',
            'tipo_cuenta' => $this->mapTipoCuenta($empleado['hoj_tcuenta'] ?? ''),
            'codigo_banco' => $this->mapBanco($empleado['hoj_banco'] ?? ''),
        ];
    }

    private function obtenerConteoPorMotivo(string $fechaInicio, string $fechaFin, int $idUsuario): array
    {
        $conteo = array_fill_keys($this->motivosPrima, 0);
        $placeholders = implode(',', array_fill(0, count($this->motivosPrima), '?'));
        $sql = "SELECT seg_motivo, COUNT(*) AS cantidad
                FROM seguimiento_user
                WHERE seg_fechaingreso BETWEEN ? AND ?
                    AND seg_idusuario = ?
                    AND seg_motivo IN ($placeholders)
                GROUP BY seg_motivo";

        $params = array_merge([$fechaInicio, $fechaFin, $idUsuario], $this->motivosPrima);
        $types = 'ssi' . str_repeat('s', count($this->motivosPrima));
        $rows = $this->fetchAll($sql, $types, $params);
        foreach ($rows as $row) {
            $conteo[$row['seg_motivo']] = (int) $row['cantidad'];
        }
        return $conteo;
    }

    private function obtenerDiasSinRegistro(int $idUsuario, string $fechaInicio, string $fechaFin): array
    {
        $inicioTexto = substr($fechaInicio, 0, 10);
        $finTexto = substr($fechaFin, 0, 10);
        $hoyTexto = date('Y-m-d');
        if ($finTexto > $hoyTexto) {
            $finTexto = $hoyTexto;
        }
        if ($idUsuario <= 0 || !$this->fechaValida($inicioTexto) || !$this->fechaValida($finTexto) || $finTexto < $inicioTexto) {
            return [];
        }

        $sql = "SELECT DISTINCT DATE(seg_fechaingreso) AS fecha
                FROM seguimiento_user
                WHERE seg_idusuario = ?
                    AND seg_fechaingreso BETWEEN ? AND ?";
        $fechasRegistradas = [];
        foreach ($this->fetchAll($sql, 'iss', [$idUsuario, $inicioTexto . ' 00:00:00', $finTexto . ' 23:59:59']) as $row) {
            $fecha = (string) ($row['fecha'] ?? '');
            if ($fecha !== '') {
                $fechasRegistradas[$fecha] = true;
            }
        }

        $dias = [];
        $fecha = new DateTimeImmutable($inicioTexto);
        $fin = new DateTimeImmutable($finTexto);
        while ($fecha <= $fin) {
            $fechaDia = $fecha->format('Y-m-d');
            if ((int) $fecha->format('d') !== 31 && !isset($fechasRegistradas[$fechaDia])) {
                $dias[] = [
                    'fecha' => $fechaDia,
                    'motivo' => 'Sin registro',
                    'descripcion' => 'No tiene registros en seguimiento_user para esta fecha',
                    'horas' => '',
                ];
            }
            $fecha = $fecha->modify('+1 day');
        }

        return $dias;
    }

    private function motivosPorGrupo(string $grupo): array
    {
        $mapa = [
            'ingresos' => ['Ingreso', 'Reposicion por falla', 'Ingreso por horas', 'IngresoHoras'],
            'descanso' => ['descanso', 'Descanso'],
            'dias_no_trabajados' => ['No trabajo', 'Sancionado'],
            'incapacidad_empresa' => ['Incapacidad'],
            'vacaciones' => ['Vacaciones'],
            'licencias' => ['licencia de maternidad', 'LICENCIA POR LUTO'],
            'incapacidad_eps' => ['Incapasidad paga por la EPS'],
        ];

        return $mapa[$grupo] ?? [];
    }

    private function primaEstaLiquidada(?array $registroPrima): bool
    {
        if (!$registroPrima) {
            return false;
        }

        return $this->fechaValida($registroPrima['pri_fecha_liquidacion'] ?? '')
            || (float) ($registroPrima['pri_dias_prima'] ?? 0) > 0
            || (float) ($registroPrima['pri_valor_prima'] ?? 0) > 0;
    }

    private function calcularDiasProyectados(string $fechaInicia, string $fechaFinaliza): int
    {
        $hoy = new DateTimeImmutable(date('Y-m-d'));
        $inicio = new DateTimeImmutable($fechaInicia);
        $fin = new DateTimeImmutable($fechaFinaliza);

        if ($hoy < $inicio || $hoy >= $fin) {
            return 0;
        }

        $temp = $hoy;
        $dias = 0;

        while ($temp <= $fin) {
            if ((int) $temp->format('d') !== 31) {
                $dias++;
            }
            $temp = $temp->modify('+1 day');
        }

        return $dias;
    }

    private function fechaValida(?string $fecha): bool
    {
        $fecha = trim((string) $fecha);
        return $fecha !== '' && $fecha !== '0000-00-00' && $fecha !== '0000-00-00 00:00:00';
    }

    private function mapTipoCuenta(string $tipoCuenta): string
    {
        $tipoCuenta = strtoupper(trim($tipoCuenta));
        if ($tipoCuenta === 'DAVIPLATA') {
            return 'DP';
        }
        if ($tipoCuenta === 'AHORROS') {
            return 'CA';
        }
        return $tipoCuenta;
    }

    private function mapBanco(string $banco): string
    {
        $banco = strtoupper(trim($banco));
        if ($banco === 'DAVIVIENDA' || $banco === 'DAVIPLATA') {
            return '51';
        }
        if ($banco === 'BBVA') {
            return '13';
        }
        return '';
    }

    private function fetchAll(string $sql, string $types = '', array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
