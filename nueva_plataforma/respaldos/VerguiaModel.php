<?php
// ../model/VerguiaModel.php
require_once "../config/database.php";

class VerguiaModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /**
     * Trae la información principal de la guía por código.
     * El código puede ser ser_consecutivo o ser_guiare (ajusta según tu realidad).
     */
    public function obtenerGuiaPorCodigo($codigo)
    {
        $sql = "SELECT 
                    s.idservicios,
                    s.idclientes,
                    s.ser_consecutivo,
                    s.cli_nombre,
                    s.ser_destinatario,
                    s.ser_telefonocontacto,
                    ciu_dest.ciu_nombre   AS ciudad_destino,
                    s.ser_direccioncontacto,
                    s.ser_paquetedescripcion,
                    s.ser_piezas,
                    s.ser_clasificacion,
                    s.ser_valorprestamo,
                    s.ser_valorabono,
                    s.ser_valorseguro,
                    s.ser_resolucion,
                    s.ser_pendientecobrar,
                    s.ser_valor,
                    s.ser_peso,
                    s.cli_idciudad,
                    s.ser_ciudadentrega,
                    s.ser_tipopaq,
                    s.cli_telefono,
                    s.cli_direccion,
                    s.ser_volumen,
                    s.ser_verificado,
                    s.ser_prioridad,
                    s.ser_guiare,
                    s.ser_estado,
                    s.ser_devolverreci,
                    s.ser_fecharegistro,
                    s.ser_descripcion,
                    s.cli_iddocumento,
                    ciu_ori.ciu_nombre    AS ciudad_origen
                FROM serviciosdia s
                LEFT JOIN ciudades ciu_dest ON ciu_dest.idciudades = s.ser_ciudadentrega
                LEFT JOIN ciudades ciu_ori  ON ciu_ori.idciudades  = s.cli_idciudad
                WHERE s.ser_consecutivo = ?
                   OR s.ser_guiare      = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $codigo, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $guia   = $result ? $result->fetch_assoc() : null;

        if (!$guia) {
            return null;
        }

        // Cargar más datos relacionados
        $idServicio = $guia['idservicios'];

        $guia['tipo_servicio']   = $this->obtenerTipoServicio($idServicio);
        $guia['pago_info']       = $this->obtenerPagoServicio($idServicio);
        $guia['credito_detalle'] = $this->obtenerCreditoServicio($idServicio);
        $guia['firmas']          = $this->obtenerFirmas($idServicio);
        $guia['ubicaciones'] = $this->obtenerUbicacionesServicio($idServicio);
        $guia['totales']         = $this->calcularTotalesGuia($guia, $guia['tipo_servicio']);

        return $guia;
    }

    /**
     * Tipo de servicio desde tabla tiposervicio / guias
     */
    public function obtenerTipoServicio($idServicio)
    {
        $sql = "SELECT t.tip_nom, g.gui_tiposervicio
                FROM tiposervicio t
                RIGHT JOIN guias g ON g.gui_tiposervicio = t.idtiposervicio
                WHERE g.gui_idservicio = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idServicio);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res) {
            return [
                'nombre' => 'Carga vía terrestre', // valor por defecto, ajusta a tu gusto
                'codigo' => null
            ];
        }

        $tiposervicio = '';

        if ($res['gui_tiposervicio'] == '1000') {
            $tiposervicio = 'A Convenir';
        } elseif ($res['tip_nom'] == '' && ($res['gui_tiposervicio'] == '' || $res['gui_tiposervicio'] == '0')) {
            $tiposervicio = 'Carga vía terrestre';
        } else {
            $tiposervicio = $res['tip_nom'];
        }

        return [
            'nombre' => $tiposervicio,
            'codigo' => $res['gui_tiposervicio']
        ];
    }

    /**
     * Información de pago / cuentas (pagoscuentas + tipospagos)
     */
    public function obtenerPagoServicio($idServicio)
    {
        $sql = "SELECT pag_cuenta, pag_nombre, pag_tipopago
                FROM pagoscuentas p
                INNER JOIN tipospagos t ON t.idtipospagos = p.pag_tipopago
                WHERE p.pag_idservicio = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idServicio);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res) {
            return null;
        }

        return [
            'cuenta'    => $res['pag_cuenta'],
            'nombre'    => $res['pag_nombre'],
            'tipopago'  => $res['pag_tipopago'],
            'pago_text' => $res['pag_nombre'] . ' / ' . $res['pag_cuenta']
        ];
    }

    /**
     * Información de crédito si aplica
     */
    public function obtenerCreditoServicio($idServicio)
    {
        $sql = "SELECT rel_nom_credito 
                FROM rel_sercre
                WHERE idservicio = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idServicio);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        return $res ? $res['rel_nom_credito'] : null;
    }

    /**
     * Firmas de Recogida y Entrega (si existen)
     */
    public function obtenerFirmas($idServicio)
    {
        $firmas = [
            'Recogida' => null,
            'Entrega'  => null
        ];

        foreach (['Recogida', 'Entrega'] as $tipo) {
            $sql  = "SELECT firma, nombre, numero_documento, correo_electronico, telefono, tipo,firma_clientes
                     FROM firma_clientes
                     WHERE tipo_firma = ?
                       AND id_guia = ?
                     ORDER BY id DESC
                     LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $tipo, $idServicio);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();

            if ($res) {
                $firmas[$tipo] = [
                    'blob'    => $res['firma'],
                    'nombre'  => $res['nombre'],
                    'numero'  => $res['numero_documento'],
                    'tipo'    => $res['tipo'],           // 'imagen' o base64
                    'telefono'=> $res['telefono'],
                    'correo'  => $res['correo_electronico'],
                    'firmaImg'  => $res['firma_clientes']
                ];
            }
        }

        return $firmas;
    }

    /**
     * Mapear clasificación numérica a texto (AJUSTA a tu realidad).
     * Ejemplo:
     * 1 = Contado
     * 2 = Crédito
     * 3 = Al Cobro
     */
    private function mapearClasificacion($clasificacion)
    {
        switch ((int)$clasificacion) {
            case 1: return 'Contado';
            case 2: return 'Credito';
            case 3: return 'Al Cobro';
            default: return 'Desconocido';
        }
    }

    /**
     * Calcula totales de flete, préstamo, seguro, etc.
     * Basado en la lógica original de ticket_renovado.php
     */
    public function calcularTotalesGuia(array $guia, array $tipoServicio)
    {
        // Valores base crudos
        $valorPrestamo = (float)str_replace('.', '', $guia['ser_valorprestamo'] ?? 0);
        $abono         = (float)str_replace('.', '', $guia['ser_valorabono'] ?? 0);
        $valorSeguro   = (float)str_replace('.', '', $guia['ser_valorseguro'] ?? 0);
        $valorFlete    = (float)str_replace('.', '', $guia['ser_valor'] ?? 0);
        $peso          = (float)$guia['ser_peso'];
        $estado        = (int)$guia['ser_estado'];
        $clasifTexto   = $this->mapearClasificacion($guia['ser_clasificacion']);

        // Seguro = % del valor declarado
        $seguro = ($valorSeguro * 1) / 100;

        // Buscar porcentaje de préstamo
        $porPrestamo = 0;
        if ($valorPrestamo > 0) {
            $sql = "SELECT pre_porcentaje 
                    FROM prestamo 
                    WHERE pre_inicio < ? AND pre_final >= ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("dd", $valorPrestamo, $valorPrestamo);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();

            if ($res && !empty($res['pre_porcentaje'])) {
                // Puede venir algo como "3 %" → separo
                $partes = explode(" ", trim($res['pre_porcentaje']));
                if (isset($partes[1]) && $partes[1] == '%') {
                    $porPrestamo = ($valorPrestamo * (float)$partes[0]) / 100;
                } else {
                    $porPrestamo = (float)$res['pre_porcentaje'];
                }
            }
        }

        $totalPrestamo = $valorPrestamo + $porPrestamo - $abono;
        $totalFlete    = $valorFlete + $seguro;
        $totalFinal    = 0;

        // Lógica de total final (similar a ticket_renovado.php)
        if ($estado >= 9 && $clasifTexto == 'Contado') {
            $totalFinal = $totalPrestamo;
        } else {
            if ($peso >= 1 || $tipoServicio['codigo'] == '1000') {
                $totalFinal = $totalFlete + $totalPrestamo;
            }
        }

        return [
            'clasificacion_texto' => $clasifTexto,
            'seguro'              => $seguro,
            'por_prestamo'        => $porPrestamo,
            'total_prestamo'      => $totalPrestamo,
            'total_flete'         => $totalFlete,
            'total_final'         => $totalFinal,
            // Versiones formateadas para la vista
            'seguro_fmt'          => number_format($seguro, 0, ".", "."),
            'por_prestamo_fmt'    => number_format($porPrestamo, 0, ".", "."),
            'total_prestamo_fmt'  => number_format($totalPrestamo, 0, ".", "."),
            'total_flete_fmt'     => number_format($totalFlete, 0, ".", "."),
            'total_final_fmt'     => number_format($totalFinal, 0, ".", "."),
            'valor_declarado_fmt' => number_format($valorSeguro, 0, ".", "."),
            'valor_flete_fmt'     => number_format($valorFlete, 0, ".", "."),
        ];
    }
    public function obtenerUbicacionesServicio($idServicio)
    {
        $sql = "SELECT tipo_evento, latitud, longitud, fecha_registro
                FROM servicios_ubicaciones
                WHERE idservicios = ?
                ORDER BY fecha_registro DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $idServicio);
        $stmt->execute();
        $result = $stmt->get_result();

        $ubicaciones = [
            'RECOGIDA' => null,
            'ENTREGA'  => null
        ];

        while ($row = $result->fetch_assoc()) {
            if ($row['tipo_evento'] === 'RECOGIDA' && !$ubicaciones['RECOGIDA']) {
                $ubicaciones['RECOGIDA'] = $row;
            }
            if ($row['tipo_evento'] === 'ENTREGA' && !$ubicaciones['ENTREGA']) {
                $ubicaciones['ENTREGA'] = $row;
            }
        }

        return $ubicaciones;
    }

}
