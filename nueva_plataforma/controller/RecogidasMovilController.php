<?php
 require("../../login_autentica.php"); // ESTE archivo debe tener session_start()
 require_once "../model/RecogidasMovilModel.php";

$modelo = new RecogidasMovilModel();
file_put_contents(
    __DIR__ . 'logs/debug_controller.log',
    "[" . date('Y-m-d H:i:s') . "] POST=" . json_encode($_POST) . PHP_EOL,
    FILE_APPEND
);
$isAjax = isset($_POST['accion']);

if ($isAjax && ($_POST['accion'] ?? '') === 'guardarRecogida') {

    $session = $_SESSION ?? [];

    $sede               = $session['usu_idsede']     ?? null;
    $usuario            = $session['usuario_id']     ?? null;
    $nombre             = $session['usuario_nombre'] ?? null;
    $acceso             = $session['usuario_rol']    ?? null;
    $precioinicialkilos = $session['precioinicial']  ?? null;

    if (empty($usuario)) {
        logController('ACCESO DENEGADO guardarRecogida', [
            'motivo' => 'Sesión no válida'
        ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sesión expirada, inicia sesión de nuevo'
        ]);
        exit;
    }

    logController('REQUEST guardarRecogida', [
        'post_keys' => array_keys($_POST),
        'files'     => array_keys($_FILES),
        'usuario'   => $usuario
    ]);

    try {
        $variableunica = date('YmdHis') . $usuario . mt_rand(1000, 9999);
        $_POST['variableunica'] = $variableunica;

        $resultado = $modelo->guardarRecogidaConLogicaVieja(
            $_POST,
            $_FILES,
            $session
        );

        logController('RESPUESTA modelo guardarRecogida', [
            'resultado' => $resultado
        ]);

        if (!$resultado['ok']) {
            logController('FALLO guardarRecogida', $resultado);
            echo json_encode($resultado);
            exit;
        }

        logController('GUARDADO OK (controller)', [
            'idservicio' => $resultado['idservicio'],
            'guia'       => $resultado['guia']
        ]);

        echo json_encode([
            'ok'         => true,
            'idservicio' => $resultado['idservicio'],
            'guia'       => $resultado['guia'],
            'planilla'   => $resultado['planilla'],
            'link'       => $resultado['link']

        ]);
        exit;

    } catch (Throwable $e) {

        logController('ERROR EXCEPCIÓN controller guardarRecogida', [
            'mensaje' => $e->getMessage(),
            'linea'   => $e->getLine()
        ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error interno al guardar'
        ]);
        exit;
    }
}

if ($isAjax && $_POST['accion'] === 'obtenerCreditos') {

    $telRem = $_POST['telremitente'] ?? '';
    $telDes = $_POST['teldestino'] ?? '';

    $telefonos = [];

    if ($telRem) $telefonos[] = $telRem;
    if ($telDes) $telefonos[] = $telDes;

    if (empty($telefonos)) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sin teléfonos'
        ]);
        exit;
    }

    $telefonosStr = "'" . implode("','", $telefonos) . "'";

    $creditos = $modelo->obtenerCreditosPorTelefonos($telefonosStr);

    if (!$creditos) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No hay créditos'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'creditos' => $creditos
    ]);
    exit;
}

if ($isAjax && $_POST['accion'] === 'obtenerTipoServicio') {

    $servicios = $modelo->obtenerTipoServicio(
        $_POST['ciudad_origen'],
        $_POST['ciudad_destino'],
        $_POST['tipo_pago'],
        $_POST['credito_id'] ?? null
    );

    if (empty($servicios)) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No hay servicios configurados'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'servicios' => $servicios
    ]);
    exit;
}

if ($isAjax && $_POST['accion'] === 'calcularValorTotal') {

    // ===============================
    // LOG DE ARRANQUE
    // ===============================
    $rutaLog = __DIR__ . "/log_calculos.txt";
    $fecha = date("[Y-m-d H:i:s] ");
    file_put_contents($rutaLog, $fecha . "---- INICIO REQUEST calcularValorTotal ----" . PHP_EOL, FILE_APPEND);
    file_put_contents($rutaLog, $fecha . "POST: " . json_encode($_POST) . PHP_EOL, FILE_APPEND);

    // ===============================
    // RECIBIR VALORES
    // ===============================
    $peso        = $_POST['peso']        ?? 0;
    $volumen     = $_POST['volumen']     ?? 0;
    $ciudadOri   = $_POST['ciudadOri']   ?? 0;
    $ciudadDes   = $_POST['ciudadDes']   ?? 0;
    $tipoServ    = $_POST['tipoServ']    ?? 0;
    $pordeclarado = $_POST['pordeclarado'] ?? 0;
    $tipoCliente = $_POST['tipocliente'] ?? 0; // 1 si es crédito
    $credito     = $_POST['rel_nom_credito'] ?? 0; // nombre del cliente, usado para buscar ID
    $tipoPago    = $_POST['tipoPago'] ?? 0;
    // ===============================
    // BUSCAR ID DEL CRÉDITO
    // ===============================
    $resultado = $modelo->idCredito($credito);

    if (!empty($resultado)) {
        $idcredito = $resultado[0]["idcreditos"];
        file_put_contents($rutaLog, $fecha . "ID de crédito encontrado: $idcredito" . PHP_EOL, FILE_APPEND);
    } else {
        $idcredito = 0;
        file_put_contents($rutaLog, $fecha . "No se encontró crédito, se usa ID=0" . PHP_EOL, FILE_APPEND);
    }

    // ===============================
    // EJECUTAR CÁLCULO
    // ===============================
    $resp = $modelo->calcularValorConLogicaVieja(
        $peso,            // kilos
        $volumen,         // volumen
        $ciudadOri,       // ciudad origen
        $ciudadDes,       // ciudad destino
        $tipoServ,        // tipo servicio
        $pordeclarado,    // valor declarado
        $idcredito,       // ID crédito CORREGIDO
        $tipoCliente,     // 1 si es crédito
        0,                // valor préstamo
        0,                // param5
        5,                 // kilos iniciales
        $tipoPago=0
    );

    file_put_contents($rutaLog, $fecha . "RESPUESTA FINAL: " . json_encode($resp) . PHP_EOL, FILE_APPEND);
    file_put_contents($rutaLog, $fecha . "---- FIN REQUEST ----" . PHP_EOL . PHP_EOL, FILE_APPEND);

    // ===============================
    // RESPUESTA JSON
    // ===============================
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($resp);
    exit;
}
if ($isAjax && ($_POST['accion'] ?? '') === 'guardarSello') {

    header('Content-Type: application/json');

    $session = $_SESSION ?? [];
    $usuario = $session['usuario_id'] ?? null;

    if (empty($usuario)) {
        logController('ACCESO DENEGADO guardarSello', [
            'motivo' => 'Sesión no válida'
        ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sesión expirada'
        ]);
        exit;
    }

    logController('REQUEST guardarSello', [
        'post_keys' => array_keys($_POST),
        'usuario'   => $usuario
    ]);

    try {

        // 🔎 VALIDAR ID SERVICIO
        if (empty($_POST['idservicio']) || !is_numeric($_POST['idservicio'])) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'ID de servicio inválido'
            ]);
            exit;
        }

        $idservicio = intval($_POST['idservicio']);

        // 🔎 VALIDAR IMAGEN BASE64
        if (empty($_POST['firmaBase64'])) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se recibió la imagen del sello'
            ]);
            exit;
        }

        $firmaBase64 = $_POST['firmaBase64'];

        if (strpos($firmaBase64, 'data:image') !== 0) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Formato de imagen inválido'
            ]);
            exit;
        }

        // 🔥 LLAMAR AL MODELO
        $guardado = $modelo->guardarFirmaRecogida($idservicio, $firmaBase64);

        if (!$guardado) {
            logController('ERROR guardarSello modelo', [
                'idservicio' => $idservicio
            ]);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se pudo guardar el sello'
            ]);
            exit;
        }

        logController('SELLO GUARDADO OK', [
            'idservicio' => $idservicio
        ]);

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Sello guardado correctamente'
        ]);
        exit;

    } catch (Throwable $e) {

        logController('EXCEPCIÓN guardarSello', [
            'mensaje' => $e->getMessage(),
            'linea'   => $e->getLine()
        ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error interno al guardar sello'
        ]);
        exit;
    }
}
if ($isAjax && ($_POST['accion'] ?? '') === 'enviarLinkFirma') {

    header('Content-Type: application/json');

    $session = $_SESSION ?? [];
    $usuario = $session['usuario_id'] ?? null;

    if (empty($usuario)) {
        logController('ACCESO DENEGADO enviarLinkFirma', [
            'motivo' => 'Sesión no válida'
        ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sesión expirada'
        ]);
        exit;
    }

    logController('REQUEST enviarLinkFirma', [
        'post' => $_POST,
        'usuario' => $usuario
    ]);

    try {

        // 🔎 Validaciones básicas
        $idservicio = $_POST['idservicio'] ?? null;
        $nombre     = trim($_POST['nombre'] ?? '');
        $telefono   = trim($_POST['telefono'] ?? '');
        $link       = trim($_POST['link'] ?? '');

        if (empty($idservicio) || !is_numeric($idservicio)) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'ID de servicio inválido'
            ]);
            exit;
        }

        if (empty($telefono)) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Teléfono requerido'
            ]);
            exit;
        }

        // if (empty($link)) {
        //     echo json_encode([
        //         'ok' => false,
        //         'mensaje' => 'Link de firma requerido'
        //     ]);
        //     exit;
        // }

        $idservicio = intval($idservicio);

        // 🔥 Tipo de alerta (usa el mismo que tu sistema ya maneje)
        $tipoAlerta = 44; // 👈 AJUSTA SI TU SISTEMA USA OTRO CÓDIGO

        // 🔥 Llamar al modelo
        $resp = $modelo->reEnviarFirmaWhat($telefono, $tipoAlerta, $idservicio, $link);

        if (!$resp['ok']) {
            logController('ERROR API reenviar firma', [
                'respuesta' => $resp
            ]);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se pudo enviar el mensaje de WhatsApp'
            ]);
            exit;
        }

        logController('REENVÍO FIRMA OK', [
            'idservicio' => $idservicio,
            'telefono'   => $telefono
        ]);

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Link enviado correctamente'
        ]);
        exit;

    } catch (Throwable $e) {

        logController('EXCEPCIÓN enviarLinkFirma', [
            'mensaje' => $e->getMessage(),
            'linea'   => $e->getLine()
        ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error interno al reenviar link'
        ]);
        exit;
    }
}

if (isset($_GET['accion']) && $_GET['accion'] === 'consultarEstadoFirma') {

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $firmada = ($id > 0) ? $modelo->existeFirmaEntregaPublica($id) : false;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'firmada' => (bool)$firmada
    ]);
    exit;
}

function logController(string $mensaje, array $contexto = [])
{
    $logDir  = __DIR__ . 'logs';
    $logFile = $logDir . '/controller_' . date('Y-m-d') . '.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;

    if (!empty($contexto)) {
        $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
    }

    file_put_contents($logFile, $linea . PHP_EOL, FILE_APPEND);
}



if (!$isAjax) {
    $session = $_SESSION ?? [];
    $sede               = $session['usu_idsede']     ?? null;
    $acceso             = $session['usuario_rol']    ?? null;

    $ciudades = $modelo->obtenerCiudades();
    $ciudadesR = $ciudades;
    
    $direcciones = $modelo->obtenerDirecciones();
    $lugares = $modelo->obtenerLugar();
    include "../view/RecogidasMovil/index.php";
}
