<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/RecogidasMovilModel.php';

class SolicitudWhatsAppModel
{
    private $db;
    private $recogidas;

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->recogidas = new RecogidasMovilModel();
    }

    public function obtenerCiudadesRemitentePublico(): array
    {
        $sql = "SELECT idciudades, ciu_nombre FROM ciudades WHERE inner_estados = 1";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerCiudades(): array
    {
        $sql = "SELECT idciudades, ciu_nombre FROM ciudades WHERE inner_estados = 1";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerDirecciones(): array
    {
        $sql = "SELECT iddireccion, dir_nombre FROM direccion";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerLugar(): array
    {
        $sql = "SELECT idlugar, lug_nombre FROM lugar WHERE estado = '1'";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function obtenerCreditosAsociados(string $telRemitente, string $telDestinatario): array
    {
        $telefonos = [];

        foreach ([$telRemitente, $telDestinatario] as $tel) {
            $raw = trim($tel);
            if ($raw === '') {
                continue;
            }

            $telefonos[] = $raw;
            $soloDigitos = preg_replace('/\D+/', '', $raw);

            if ($soloDigitos !== '' && $soloDigitos !== $raw) {
                $telefonos[] = $soloDigitos;
            }
        }

        $telefonos = array_values(array_unique(array_filter($telefonos)));
        if (empty($telefonos)) {
            return [];
        }

        $in = [];
        foreach ($telefonos as $telefono) {
            $in[] = "'" . $this->db->real_escape_string($telefono) . "'";
        }

        $sql = "
            SELECT c.idcreditos, c.cre_nombre
            FROM creditos c
            INNER JOIN rel_crecli rc ON rc.rel_idcredito = c.idcreditos
            INNER JOIN clientesdir cd ON cd.idclientesdir = rc.rel_idcliente
            WHERE cd.cli_telefono IN (" . implode(',', $in) . ")
            GROUP BY c.idcreditos, c.cre_nombre
            ORDER BY c.cre_nombre ASC
        ";

        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function guardarSolicitud(array $post, array $files): array
    {
        // $token = trim((string)($post['token'] ?? ''));
        // if ($token === '') {
        //     return [
        //         'ok' => false,
        //         'mensaje' => 'Token invalido'
        //     ];
        // }

        $data = $post;

        // Valores por defecto del flujo publico
        $data['variableunica'] = date('YmdHis') . 'WA' . mt_rand(1000, 9999);
        $data['id_usuario'] = 1919;
        $data['param12'] = $data['param12'] ?? 'PAQUETE';
        $data['param15'] = 'Solicitud WhatsApp';
        $data['param16'] = $data['param16'] ?? '';
        $data['param17'] = $data['param17'] ?? '0';
        $data['param18'] = $data['param18'] ?? '100000';
        $data['param25'] = $data['param25'] ?? '0';
        $data['param26'] = $data['param26'] ?? '0';
        $data['param27'] = $data['param27'] ?? '0';
        $creditoSeleccionado = trim((string)($data['cliente_credito'] ?? ''));
        $data['param28'] = ($creditoSeleccionado !== '') ? '2' : '3';
        $data['param29'] = $data['param29'] ?? '1';
        $data['param92'] = $data['param92'] ?? ($data['param6'] ?? 'CLIENTE');
        $data['param93'] = $data['param93'] ?? ($data['param2'] ?? '');
        $data['param111'] = '0';
        $data['valorSinSeguro'] = '0';

        // Para este flujo publico no calculamos tipo de servicio en frontend
        if (!isset($data['param113']) || $data['param113'] === '') {
            $data['param113'] = '0';
        }

        if ($creditoSeleccionado !== '') {
            $data['cliente_credito'] = $creditoSeleccionado;
        }

        // Sesion sintetica para no depender de login en este modulo
        $session = [
            'usuario_id' => 1919,
            'usuario_nombre' => 'WHATSAPP_FORM',
            'usuario_rol' => 1,
            'usu_idsede' => 1,
            'precioinicial' => 5
        ];

        $this->log('REQUEST guardarSolicitudWhatsApp', [
            'post_keys' => array_keys($post),
            'files' => array_keys($files)
        ]);

        $resp = $this->guardarSolicituddelWhatsapp($data, $files, $session);

        if (!$resp['ok']) {
            $this->log('FALLO guardarSolicitudWhatsApp', $resp);
            return $resp;
        }

        $this->log('OK guardarSolicitudWhatsApp', [
            'idservicio' => $resp['idservicio'] ?? null,
            'guia' => $resp['guia'] ?? null
        ]);

        return [
            'ok' => true,
            'mensaje' => 'Solicitud registrada correctamente',
            'idservicio' => $resp['idservicio'] ?? null,
            'guia' => $resp['guia'] ?? '',
            'planilla' => $resp['planilla'] ?? '',
            'link' => $resp['link'] ?? ''
        ];
    }

    public function log(string $mensaje, array $contexto = []): void
    {
        $logDir = __DIR__ . '/logs';
        $logFile = $logDir . '/solicitud_whatsapp_' . date('Y-m-d') . '.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;
        if (!empty($contexto)) {
            $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($logFile, $linea . PHP_EOL, FILE_APPEND);
    }
    public function guardarSolicituddelWhatsapp($data, $files, $session)
    {
        date_default_timezone_set('America/Bogota');
        try {
            $fechatiempo = date('Y-m-d H:i:s');

            $remitente = $this->upsertRemitente($data, $fechatiempo);
            if (!$remitente['ok']) {
                return $remitente;
            }

            $idClienteServicio = $this->insertClienteServicioRemitente(
                (string)($data['param6'] ?? ''),
                (string)($data['param2'] ?? ''),
                (string)($data['param4'] ?? ''),
                (string)($remitente['direccion'] ?? ''),
                (int)($remitente['idclientes'] ?? 0)
            );
            if ($idClienteServicio <= 0) {
                return [
                    'ok' => false,
                    'mensaje' => 'No se pudo insertar clientesservicios para remitente'
                ];
            }

            $destinatario = $this->upsertDestinatario($data, $fechatiempo);
            if (!$destinatario['ok']) {
                return $destinatario;
            }

            $imagenWhatsApp = $this->guardarImagenWhatsApp($files);
            $numeroCreador = trim((string)($data['numeroCreador'] ?? ''));
            if ($numeroCreador === '') {
                $numeroCreador = trim((string)($data['token'] ?? ''));
            }
            if ($numeroCreador === '') {
                $numeroCreador = trim((string)($data['param2'] ?? ''));
            }
            
            $diceContener = (string)($data['param13'] ?? '');

            $idServicio = $this->insertServicioWhatsApp(
                (string)($destinatario['telefono'] ?? ''),
                (string)($destinatario['nombre'] ?? ''),
                (string)($destinatario['direccion'] ?? ''),
                (string)($destinatario['idciudad'] ?? ''),
                $fechatiempo,
                $imagenWhatsApp,
                $numeroCreador,
                $diceContener

            );
            if ($idServicio <= 0) {
                return [
                    'ok' => false,
                    'mensaje' => 'No se pudo insertar el servicio'
                ];
            }

            $okRel = $this->insertRelacionServicioCliente(
                (int)$idClienteServicio,
                (int)$idServicio,
                $fechatiempo
            );
            if (!$okRel) {
                return [
                    'ok' => false,
                    'mensaje' => 'No se pudo insertar rel_sercli'
                ];
            }

            $creditoSeleccionado = trim((string)($data['cliente_credito'] ?? ''));
            if ($creditoSeleccionado !== '') {
                $nombreCredito = $this->obtenerNombreCredito($creditoSeleccionado);
                if ($nombreCredito === '') {
                    return [
                        'ok' => false,
                        'mensaje' => 'No se encontro el nombre del credito seleccionado'
                    ];
                }

                $okRelCre = $this->insertRelacionServicioCredito((int)$idServicio, $nombreCredito);
                if (!$okRelCre) {
                    return [
                        'ok' => false,
                        'mensaje' => 'No se pudo insertar rel_sercre'
                    ];
                }
            }else{
                $nombreCredito="";
                $okRelCre = $this->insertRelacionServicioCredito((int)$idServicio, $nombreCredito);
                if (!$okRelCre) {
                    return [
                        'ok' => false,
                        'mensaje' => 'No se pudo insertar rel_sercre'
                    ];
                }

            }

            $okGuia = $this->insertGuiaWhatsApp((int)$idServicio, $fechatiempo);
            if (!$okGuia) {
                return [
                    'ok' => false,
                    'mensaje' => 'No se pudo insertar la guia'
                ];
            }

            return [
                'ok' => true,
                'mensaje' => 'Solicitud registrada correctamente',
                'idservicio' => $idServicio,
                'guia' => '',
                'planilla' => '',
                'link' => '',
                'id_remitente' => $remitente['idclientes'] ?? 0,
                'id_remitente_dir' => $remitente['idclientesdir'] ?? 0,
                'id_cliente_servicio' => $idClienteServicio,
                'id_destinatario' => $destinatario['idclientes'] ?? 0,
                'id_destinatario_dir' => $destinatario['idclientesdir'] ?? 0
            ];
        } catch (Throwable $e) {
            $this->log('ERROR guardarSolicituddelWhatsapp', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine()
            ]);

            return [
                'ok' => false,
                'mensaje' => 'Error interno al actualizar clientes'
            ];
        }
    }

    private function upsertRemitente(array $data, string $fechatiempo): array
    {
        $telefono = trim((string)($data['param2'] ?? ''));
        if ($telefono === '') {
            return ['ok' => false, 'mensaje' => 'Telefono de remitente requerido'];
        }

        $dirRem = $this->construirDireccion(
            (string)($data['param5'] ?? ''),
            (string)($data['dir1R'] ?? ''),
            (string)($data['dir2R'] ?? ''),
            (string)($data['dir3R'] ?? ''),
            (string)($data['selectComplemento'] ?? ''),
            (string)($data['complemento_detalle_final'] ?? ''),
            (string)($data['param23'] ?? ''),
            (string)($data['complemento_detalle_final'] ?? '')
        );

        $existente = $this->buscarClientePorTelefono($telefono);
        $idclientes = (int)($existente['idclientes'] ?? 0);
        $idclientesdir = (int)($existente['idclientesdir'] ?? 0);

        if ($idclientes > 0 && $idclientesdir > 0) {
            $sqlUpCli = "UPDATE clientes SET
                            cli_iddocumento='" . $this->escape($data['param1'] ?? '') . "',
                            cli_email='" . $this->escape($data['param3'] ?? '') . "',
                            cli_clasificacion='0',
                            cli_tipo=2,
                            cli_fecharegistro='" . $this->escape($fechatiempo) . "',
                            cli_retorno='" . $this->escape($data['param25'] ?? '0') . "'
                        WHERE idclientes='" . (int)$idclientes . "'";
            $this->db->query($sqlUpCli);

            $sqlUpDir = "UPDATE clientesdir SET
                            cli_nombre='" . $this->escape($data['param6'] ?? '') . "',
                            cli_telefono='" . $this->escape($telefono) . "',
                            cli_idciudad='" . $this->escape($data['param4'] ?? '') . "',
                            cli_direccion='" . $this->escape($dirRem) . "',
                            cli_idclientes='" . (int)$idclientes . "',
                            cli_principal=1
                        WHERE idclientesdir='" . (int)$idclientesdir . "'";
            $this->db->query($sqlUpDir);
        } else {
            $sqlInsCli = "INSERT INTO clientes (cli_iddocumento, cli_email, cli_clasificacion, cli_retorno, cli_tipo, cli_fecharegistro)
                        VALUES (
                            '" . $this->escape($data['param1'] ?? '') . "',
                            '" . $this->escape($data['param3'] ?? '') . "',
                            '0',
                            '" . $this->escape($data['param25'] ?? '0') . "',
                            2,
                            '" . $this->escape($fechatiempo) . "'
                        )";
            $idclientes = $this->insert($sqlInsCli);

            $sqlInsDir = "INSERT INTO clientesdir (cli_nombre, cli_telefono, cli_idciudad, cli_direccion, cli_idclientes, cli_principal)
                        VALUES (
                            '" . $this->escape($data['param6'] ?? '') . "',
                            '" . $this->escape($telefono) . "',
                            '" . $this->escape($data['param4'] ?? '') . "',
                            '" . $this->escape($dirRem) . "',
                            '" . (int)$idclientes . "',
                            1
                        )";
            $idclientesdir = $this->insert($sqlInsDir);
        }

        $this->log('UPSERT remitente', [
            'telefono' => $telefono,
            'idclientes' => $idclientes,
            'idclientesdir' => $idclientesdir
        ]);

        return [
            'ok' => true,
            'idclientes' => (int)$idclientes,
            'idclientesdir' => (int)$idclientesdir,
            'direccion' => $dirRem
        ];
    }

    private function upsertDestinatario(array $data, string $fechatiempo): array
    {
        $telefono = trim((string)($data['param8'] ?? ''));
        if ($telefono === '') {
            return ['ok' => false, 'mensaje' => 'Telefono de destinatario requerido'];
        }

        $dirDes = $this->construirDireccion(
            (string)($data['param10'] ?? ''),
            (string)($data['dir1D'] ?? ''),
            (string)($data['dir2D'] ?? ''),
            (string)($data['dir3D'] ?? ''),
            (string)($data['param21'] ?? ''),
            (string)($data['complementoFinalD'] ?? ''),
            (string)($data['param24'] ?? ''),
            (string)($data['complemento_detalle_finalD'] ?? '')
        );

        $existente = $this->buscarClientePorTelefono($telefono);
        $idclientes = (int)($existente['idclientes'] ?? 0);
        $idclientesdir = (int)($existente['idclientesdir'] ?? 0);

        if ($idclientes > 0 && $idclientesdir > 0) {
            $sqlUpCliD = "UPDATE clientes SET
                            cli_tipo=0,
                            cli_fecharegistro='" . $this->escape($fechatiempo) . "',
                            cli_iddocumento='',
                            cli_email=''
                        WHERE idclientes='" . (int)$idclientes . "'";
            $this->db->query($sqlUpCliD);

            $sqlUpDirD = "UPDATE clientesdir SET
                            cli_nombre='" . $this->escape($data['param9'] ?? '') . "',
                            cli_telefono='" . $this->escape($telefono) . "',
                            cli_idciudad='" . $this->escape($data['param11'] ?? '') . "',
                            cli_direccion='" . $this->escape($dirDes) . "',
                            cli_principal=0
                        WHERE idclientesdir='" . (int)$idclientesdir . "'";
            $this->db->query($sqlUpDirD);
        } else {
            $sqlInsCliD = "INSERT INTO clientes (cli_tipo, cli_iddocumento, cli_email, cli_fecharegistro)
                        VALUES (0, '', '', '" . $this->escape($fechatiempo) . "')";
            $idclientes = $this->insert($sqlInsCliD);

            $sqlInsDirD = "INSERT INTO clientesdir (cli_nombre, cli_telefono, cli_idciudad, cli_direccion, cli_idclientes, cli_principal)
                        VALUES (
                            '" . $this->escape($data['param9'] ?? '') . "',
                            '" . $this->escape($telefono) . "',
                            '" . $this->escape($data['param11'] ?? '') . "',
                            '" . $this->escape($dirDes) . "',
                            '" . (int)$idclientes . "',
                            0
                        )";
            $idclientesdir = $this->insert($sqlInsDirD);
        }

        $this->log('UPSERT destinatario', [
            'telefono' => $telefono,
            'idclientes' => $idclientes,
            'idclientesdir' => $idclientesdir
        ]);

        return [
            'ok' => true,
            'idclientes' => (int)$idclientes,
            'idclientesdir' => (int)$idclientesdir,
            'telefono' => $telefono,
            'nombre' => (string)($data['param9'] ?? ''),
            'direccion' => $dirDes,
            'idciudad' => (string)($data['param11'] ?? '')
        ];
    }

    private function construirDireccion(
        string $tipoVia,
        string $dir1,
        string $dir2,
        string $dir3,
        string $lugar,
        string $detalleComplemento,
        string $barrio,
        string $complementoFinal
    ): string {
        $direccion = trim($tipoVia) . "&" .
            trim($dir1) . "#" . trim($dir2) . "-" . trim($dir3) . "&" .
            trim($lugar) . "&" .
            trim($detalleComplemento) . "&" .
            trim($barrio) . "&" .
            trim($complementoFinal);

        return str_replace('&0&', '&&', $direccion);
    }

    private function insertClienteServicioRemitente(
        string $nombre,
        string $telefono,
        string $idCiudad,
        string $direccion,
        int $idCliente
    ): int {
        if ($idCliente <= 0) {
            return 0;
        }

        $sqlCliServ = "INSERT INTO clientesservicios (cli_nombre, cli_telefono, cli_idciudad, cli_direccion, cli_idclientes, cli_principal)
                    VALUES (
                        '" . $this->escape($nombre) . "',
                        '" . $this->escape($telefono) . "',
                        '" . $this->escape($idCiudad) . "',
                        '" . $this->escape($direccion) . "',
                        '" . (int)$idCliente . "',
                        1
                    )";

        $id = $this->insert($sqlCliServ);

        $this->log('INSERT clientesservicios remitente', [
            'idclientes' => $idCliente,
            'idclientesservicios' => $id
        ]);

        return $id;
    }

    private function buscarClientePorTelefono(string $telefono): array
    {
        $variantes = [$telefono];
        $soloDigitos = preg_replace('/\D+/', '', $telefono);
        if ($soloDigitos !== '' && $soloDigitos !== $telefono) {
            $variantes[] = $soloDigitos;
        }
        $variantes = array_values(array_unique(array_filter($variantes)));

        if (empty($variantes)) {
            return [];
        }

        $in = [];
        foreach ($variantes as $tel) {
            $in[] = "'" . $this->escape($tel) . "'";
        }

        $sql = "SELECT c.idclientes, cd.idclientesdir
                FROM clientes c
                INNER JOIN clientesdir cd ON cd.cli_idclientes = c.idclientes
                WHERE cd.cli_telefono IN (" . implode(',', $in) . ")
                ORDER BY cd.cli_principal DESC, cd.idclientesdir DESC
                LIMIT 1";

        return $this->fetchOne($sql) ?? [];
    }

    private function insertServicioWhatsApp(
        string $telefonoDestino,
        string $nombreDestino,
        string $direccionDestino,
        string $ciudadDestino,
        string $fechatiempo,
        string $imagen,
        string $numeroCreador,
        string $diceContener
    ): int {
        $sql = "INSERT INTO servicios (
                    ser_iddocumento,
                    ser_telefonocontacto,
                    ser_destinatario,
                    ser_direccioncontacto,
                    ser_ciudadentrega,
                    ser_tipopaquete,
                    ser_paquetedescripcion,
                    ser_fechaentrega,
                    ser_prioridad,
                    ser_valorprestamo,
                    ser_valorabono,
                    ser_valorseguro,
                    ser_fecharegistro,
                    ser_clasificacion,
                    ser_img_whatsapp,
                    ser_num_whatsapp_crea
                ) VALUES (
                    '',
                    ?, ?, ?, ?,
                    '', ?, '', '',
                    0, 0, '100000',
                    ?, '',
                    ?, ?
                )";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->log('ERROR prepare insertServicioWhatsApp', ['db_error' => $this->db->error]);
            return 0;
        }

        $stmt->bind_param(
            'ssssssss',
            $telefonoDestino,
            $nombreDestino,
            $direccionDestino,
            $ciudadDestino,
            $diceContener,
            $fechatiempo,
            $imagen,
            $numeroCreador
        );

        $ok = $stmt->execute();
        if (!$ok) {
            $this->log('ERROR execute insertServicioWhatsApp', ['stmt_error' => $stmt->error]);
            $stmt->close();
            return 0;
        }

        $id = (int)$this->db->insert_id;
        $stmt->close();

        $this->log('INSERT servicio whatsapp', [
            'idservicio' => $id,
            'telefono_destino' => $telefonoDestino,
            'numero_creador' => $numeroCreador
        ]);

        return $id;
    }

    private function insertRelacionServicioCliente(int $idClienteServicio, int $idServicio, string $fechatiempo): bool
    {
        $sql = "INSERT INTO rel_sercli (ser_idclientes, ser_idservicio, ser_fechaingreso) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->log('ERROR prepare rel_sercli', ['db_error' => $this->db->error]);
            return false;
        }

        $stmt->bind_param('iis', $idClienteServicio, $idServicio, $fechatiempo);
        $ok = $stmt->execute();
        if (!$ok) {
            $this->log('ERROR execute rel_sercli', ['stmt_error' => $stmt->error]);
        }
        $stmt->close();

        return $ok;
    }

    private function insertRelacionServicioCredito(int $idServicio, string $nombreCredito): bool
    {
        $sql = "INSERT INTO rel_sercre (idservicio, rel_nom_credito) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->log('ERROR prepare rel_sercre', ['db_error' => $this->db->error]);
            return false;
        }

        $stmt->bind_param('is', $idServicio, $nombreCredito);
        $ok = $stmt->execute();
        if (!$ok) {
            $this->log('ERROR execute rel_sercre', ['stmt_error' => $stmt->error]);
        }
        $stmt->close();

        return $ok;
    }

    private function insertGuiaWhatsApp(int $idServicio, string $fechatiempo): bool
    {
        $sql = "INSERT INTO guias (gui_idservicio, gui_idusuario, gui_usucreado, gui_fechacreacion, gui_tiposervicio)
                VALUES (?, '1919', 'whatsapp', ?, '')";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->log('ERROR prepare guias', ['db_error' => $this->db->error]);
            return false;
        }

        $stmt->bind_param('is', $idServicio, $fechatiempo);
        $ok = $stmt->execute();
        if (!$ok) {
            $this->log('ERROR execute guias', ['stmt_error' => $stmt->error]);
        }
        $stmt->close();

        return $ok;
    }

    private function obtenerNombreCredito(string $creditoSeleccionado): string
    {
        $idCredito = (int)$creditoSeleccionado;
        if ($idCredito > 0) {
            $sql = "SELECT cre_nombre FROM creditos WHERE idcreditos='" . $idCredito . "' LIMIT 1";
            $nombre = (string)($this->fetchValue($sql) ?? '');
            if ($nombre !== '') {
                return $nombre;
            }
        }

        return trim($creditoSeleccionado);
    }

    private function guardarImagenWhatsApp(array $files): string
    {
        $file = $this->obtenerPrimerArchivo($files['fotos_paquete'] ?? null);
        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return '';
        }

        $destDir = __DIR__ . '/../uploads/whatsapp/';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }

        $original = (string)($file['name'] ?? 'whatsapp.jpg');
        $nombre = date('Y-m-d-H-i-s') . '_WA_' . preg_replace('/[^a-zA-Z0-9\.\-\_]/', '_', $original);
        $destino = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $nombre;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            return '';
        }

        return $nombre;
    }

    private function obtenerPrimerArchivo($input): ?array
    {
        if (!is_array($input)) {
            return null;
        }

        if (isset($input['name']) && is_array($input['name'])) {
            if (empty($input['name'][0])) {
                return null;
            }

            return [
                'name' => $input['name'][0] ?? '',
                'type' => $input['type'][0] ?? '',
                'tmp_name' => $input['tmp_name'][0] ?? '',
                'error' => $input['error'][0] ?? UPLOAD_ERR_NO_FILE,
                'size' => $input['size'][0] ?? 0
            ];
        }

        if (!empty($input['name'])) {
            return $input;
        }

        return null;
    }

    private function escape($str): string
    {
        return $this->db->real_escape_string((string)$str);
    }

    private function fetchOne(string $sql): ?array
    {
        $res = $this->db->query($sql);
        if (!$res) {
            return null;
        }
        $row = $res->fetch_assoc();
        return $row ?: null;
    }

    private function fetchValue(string $sql)
    {
        $res = $this->db->query($sql);
        if (!$res) {
            return null;
        }
        $row = $res->fetch_row();
        return $row[0] ?? null;
    }

    private function insert(string $sql): int
    {
        $ok = $this->db->query($sql);
        if (!$ok) {
            return 0;
        }
        return (int)$this->db->insert_id;
    }
}
