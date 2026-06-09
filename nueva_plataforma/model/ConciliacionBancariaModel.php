<?php
require_once "../config/database.php";

class ConciliacionBancariaModel
{
    private mysqli $db;

    private array $bancos = [
        'davivienda' => [
            'nombre' => 'Davivienda',
            'tabla' => 'transdavivienda',
            'id' => 'Idtransdavivienda',
            'factura' => 'factura',
            'fecha' => 'Fecha_Sistema',
            'select' => "Idtransdavivienda AS id, Fecha_Sistema AS fecha, Documento AS documento,
                Descripcion_Motivo AS descripcion, Oficina_Recaudo AS canal, Valor_Total AS valor,
                Transaccion AS referencia, Nit_Originador AS nit, factura, guia",
        ],
        'bancolombia' => [
            'nombre' => 'Bancolombia',
            'tabla' => 'transbancolombia',
            'id' => 'Idtransbancolombia',
            'factura' => 'Factura',
            'fecha' => 'Fecha',
            'select' => "Idtransbancolombia AS id, Fecha AS fecha, Documento AS documento,
                Descripcion AS descripcion, SucursalCanal AS canal, Valor AS valor,
                Referencia1 AS referencia, Referencia2 AS nit, Factura AS factura, guia",
        ],
    ];

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    public function obtenerBancos(): array
    {
        return [
            'davivienda' => 'Davivienda',
            'bancolombia' => 'Bancolombia',
        ];
    }

    public function listarMovimientos(string $banco, string $estado = 'pendientes'): array
    {
        $config = $this->configBanco($banco);
        $factura = $config['factura'];
        $guia = 'guia';

        if ($estado === 'facturas') {
            $condicion = "$factura <> 'Sin facturar' AND ($guia = '' OR $guia IS NULL)";
        } elseif ($estado === 'guias') {
            $condicion = "$factura = 'Sin facturar' AND $guia <> '' AND $guia IS NOT NULL";
        } else {
            $condicion = "$factura = 'Sin facturar' AND ($guia = '' OR $guia IS NULL)";
        }

        $sql = "SELECT {$config['select']}
                FROM {$config['tabla']}
                WHERE $condicion
                ORDER BY {$config['fecha']} DESC, {$config['id']} DESC";

        return $this->fetchAll($sql);
    }

    public function listarGuiasPendientes(string $banco): array
    {
        $config = $this->configBanco($banco);
        $sql = "SELECT p.idpagoscuentas AS id, p.pag_fecha AS fecha, p.pag_guia AS guia,
                       p.pag_idservicio AS id_servicio, u.usu_nombre AS operador,
                       p.pag_nombre AS nombre, p.pag_cuenta AS cuenta, p.pag_valor AS valor,
                       p.pag_img_transaccion AS imagen
                FROM pagoscuentas p
                INNER JOIN usuarios u ON p.pag_idoperario = u.idusuarios
                INNER JOIN tipospagos t ON p.pag_tipopago = t.idtipospagos
                WHERE p.idpagoscuentas > 0
                  AND (p.pag_userverifica = '' OR p.pag_userverifica IS NULL)
                  AND NOT EXISTS (
                      SELECT 1 FROM {$config['tabla']} b
                      WHERE b.guia LIKE CONCAT('%', p.pag_guia, '%')
                  )
                ORDER BY p.idpagoscuentas ASC";

        return $this->fetchAll($sql);
    }

    public function listarFacturasPendientes(string $banco): array
    {
        $config = $this->configBanco($banco);
        $factura = $config['factura'];

        $sql = "SELECT idfacturascreditos AS id, fac_fechafactura AS fecha,
                       fac_credito AS credito, fac_numeroref AS factura,
                       fac_precio AS valor, fac_nit AS nit
                FROM facturascreditos f
                WHERE (fac_tipopago = 'Pendiente' OR fac_tipopago IS NULL OR fac_tipopago = '')
                  AND fac_fechafactura > '2023-06-01'
                  AND fac_estado = 'Facturado'
                  AND NOT EXISTS (
                      SELECT 1 FROM {$config['tabla']} b
                      WHERE b.$factura LIKE CONCAT('%', f.fac_numeroref, '%')
                  )
                ORDER BY fac_fechafactura DESC";

        return $this->fetchAll($sql);
    }

    public function asignarFacturas(string $banco, array $movimientos, array $facturas, string $usuario, string $descripcion): bool
    {
        $config = $this->configBanco($banco);
        $valorFactura = json_encode(array_values($facturas), JSON_UNESCAPED_UNICODE);

        $this->db->begin_transaction();
        try {
            $sql = "UPDATE {$config['tabla']} SET {$config['factura']} = ? WHERE {$config['id']} = ?";
            $stmt = $this->db->prepare($sql);
            foreach ($movimientos as $id) {
                $id = (int) $id;
                $stmt->bind_param("si", $valorFactura, $id);
                $stmt->execute();
            }
            $stmt->close();

            $fecha = date('Y-m-d H:i:s');
            $sqlFactura = "UPDATE facturascreditos
                           SET fac_fechapago = ?, fac_tipopago = 'Transferencia Bancaria',
                               fac_userpago = ?, fac_descripcion = ?
                           WHERE fac_numeroref = ?";
            $stmtFactura = $this->db->prepare($sqlFactura);
            foreach ($facturas as $factura) {
                $factura = (string) $factura;
                $stmtFactura->bind_param("ssss", $fecha, $usuario, $descripcion, $factura);
                $stmtFactura->execute();
            }
            $stmtFactura->close();

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function asignarGuias(string $banco, array $movimientos, array $guias, string $usuario): bool
    {
        $config = $this->configBanco($banco);
        $valorGuias = json_encode(array_values($guias), JSON_UNESCAPED_UNICODE);

        $this->db->begin_transaction();
        try {
            $sql = "UPDATE {$config['tabla']} SET guia = ? WHERE {$config['id']} = ?";
            $stmt = $this->db->prepare($sql);
            foreach ($movimientos as $id) {
                $id = (int) $id;
                $stmt->bind_param("si", $valorGuias, $id);
                $stmt->execute();
            }
            $stmt->close();

            $fecha = date('Y-m-d H:i:s');
            $sqlGuia = "UPDATE pagoscuentas
                        SET pag_userverifica = ?, pag_fechaverifica = ?,
                            pag_valorconfirmado = '', pag_numerotrans = ''
                        WHERE pag_guia = ?";
            $stmtGuia = $this->db->prepare($sqlGuia);
            foreach ($guias as $guia) {
                $guia = (string) $guia;
                $stmtGuia->bind_param("sss", $usuario, $fecha, $guia);
                $stmtGuia->execute();
            }
            $stmtGuia->close();

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function removerMovimiento(string $banco, int $id): bool
    {
        $config = $this->configBanco($banco);
        $sql = "SELECT {$config['factura']} AS factura, guia
                FROM {$config['tabla']}
                WHERE {$config['id']} = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        $this->db->begin_transaction();
        try {
            if (!empty($row['factura']) && $row['factura'] !== 'Sin facturar') {
                $sqlUpdate = "UPDATE {$config['tabla']} SET {$config['factura']} = 'Sin facturar' WHERE {$config['id']} = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->bind_param("i", $id);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                $stmtFactura = $this->db->prepare("UPDATE facturascreditos
                    SET fac_fechapago = '', fac_tipopago = '', fac_userpago = '', fac_descripcion = ''
                    WHERE fac_numeroref = ?");
                foreach ($this->decodificarLista($row['factura']) as $factura) {
                    $stmtFactura->bind_param("s", $factura);
                    $stmtFactura->execute();
                }
                $stmtFactura->close();
            }

            if (!empty($row['guia'])) {
                $stmtUpdate = $this->db->prepare("UPDATE {$config['tabla']} SET guia = '' WHERE {$config['id']} = ?");
                $stmtUpdate->bind_param("i", $id);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                $stmtGuia = $this->db->prepare("UPDATE pagoscuentas
                    SET pag_userverifica = '', pag_fechaverifica = '',
                        pag_valorconfirmado = '', pag_numerotrans = ''
                    WHERE pag_guia = ?");
                foreach ($this->decodificarLista($row['guia']) as $guia) {
                    $stmtGuia->bind_param("s", $guia);
                    $stmtGuia->execute();
                }
                $stmtGuia->close();
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function eliminarMovimiento(string $banco, int $id): bool
    {
        $config = $this->configBanco($banco);
        $stmt = $this->db->prepare("DELETE FROM {$config['tabla']} WHERE {$config['id']} = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function confirmarPagoGuia(array $guias, string $numeroTransaccion, string $valorConfirmado, ?array $archivo): bool
    {
        $imagen = $this->guardarComprobante($archivo);
        $sql = "UPDATE pagoscuentas
                SET pag_numerotrans = ?, pag_valorconfirmado = ?"
                . ($imagen !== '' ? ", pag_img_transaccion = ?" : "")
                . " WHERE pag_guia = ?";

        $stmt = $this->db->prepare($sql);
        foreach ($guias as $guia) {
            $guia = (string) $guia;
            if ($imagen !== '') {
                $stmt->bind_param("ssss", $numeroTransaccion, $valorConfirmado, $imagen, $guia);
            } else {
                $stmt->bind_param("sss", $numeroTransaccion, $valorConfirmado, $guia);
            }
            $stmt->execute();
        }
        $stmt->close();
        return true;
    }

    public function decodificarLista(?string $valor): array
    {
        if ($valor === null || trim($valor) === '' || $valor === 'Sin facturar') {
            return [];
        }

        $actual = $valor;
        for ($i = 0; $i < 3; $i++) {
            $decodificado = json_decode($actual, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            if (is_array($decodificado)) {
                return array_values(array_filter(array_map('strval', $decodificado)));
            }
            if (is_string($decodificado)) {
                $actual = $decodificado;
                continue;
            }
            break;
        }

        return [$valor];
    }

    private function configBanco(string $banco): array
    {
        $banco = strtolower($banco);
        if (!isset($this->bancos[$banco])) {
            throw new InvalidArgumentException("Banco no valido");
        }
        return $this->bancos[$banco];
    }

    private function fetchAll(string $sql): array
    {
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function guardarComprobante(?array $archivo): string
    {
        if (!$archivo || empty($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return '';
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
            return '';
        }

        $directorio = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'img_transacciones';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $nombre = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destino = $directorio . DIRECTORY_SEPARATOR . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return '';
        }

        return $nombre;
    }
}
