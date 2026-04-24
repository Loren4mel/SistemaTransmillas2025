<?php
// controller/RecogerController.php
require("../../login_autentica.php"); // ESTE archivo debe tener session_start()
require_once "../model/RecogerModel.php";

$modelo = new RecogerModel();




/* ============================================================
   ACCIÓN: MOSTRAR VISTA
   ============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'vista') {

    $idServicio = $_GET['idServicio'] ?? 0;

    // En caso de que luego envíes sede, acceso, usuario desde otro módulo
    $sede    = $_POST['sede']    ?? '';
    $acceso  = $_POST['acceso']  ?? '';
    $usuario = $_POST['usuario'] ?? '';

    include "../view/Recoger/index.php";
    exit;
}

/* ============================================================
   ACCIÓN: BUSCAR DATOS DEL SERVICIO
   ============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'buscarRecogida') {

    $id = intval($_GET['id'] ?? 0);
    $datos = $modelo->buscarRecogida($id);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($datos);
    exit;
}

/* ============================================================
   ACCIÓN: GUARDAR RECOGIDO
   ============================================================ */
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarRecogido') {

    $resp = $modelo->guardarRecogido($_POST, $_FILES);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($resp);
    exit;
}

/* ============================================================
   ACCIÓN: GUARDAR NO RECOGIDO
   ============================================================ */
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarNoRecogido') {

    $resp = $modelo->guardarNoRecogido($_POST, $_FILES);

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($resp);
    exit;
}

/* ============================================================
   ACCIÓN: LISTAR TIPOS DE PAQUETE (param21)
   ============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'listarTipos') {

    $tipos = $modelo->listarTipos();

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($tipos);
    exit;
}

/* ============================================================
   ACCIÓN: LISTAR MÉTODOS DE PAGO (param30)
   ============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'listarMetodosPago') {

    $metodos = $modelo->listarMetodosPago();

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($metodos);
    exit;
}


/* ============================================================
   ACCIÓN: LISTAR MÉTODOS DE Cretitos (creditos)
   ============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'listarCreditos') {

    $creditos = $modelo->listarCreditos();

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($creditos);
    exit;
}
/* ============================================================
   ACCIÓN: Calcular valor 
   ============================================================ */

if (isset($_POST['accion']) && $_POST['accion'] === 'calcularValorTotal') {

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

/* ============================================================
   ACCIÓN: Cargar sello
   ============================================================ */

if (isset($_POST['accion']) && $_POST['accion'] === 'guardarSello') {

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    $session = $_SESSION ?? [];
    $usuario = $session['usuario_id'] ?? null;

    if (empty($usuario)) {
        // logController('ACCESO DENEGADO guardarSello', [
        //     'motivo' => 'Sesión no válida'
        // ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sesión expirada'
        ]);
        exit;
    }

    // logController('REQUEST guardarSello', [
    //     'post_keys' => array_keys($_POST),
    //     'usuario'   => $usuario
    // ]);

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

        if (strlen($firmaBase64) > 12 * 1024 * 1024) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'La imagen del sello es demasiado pesada'
            ]);
            exit;
        }

        if (strpos($firmaBase64, 'data:image') !== 0) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Formato de imagen inválido'
            ]);
            exit;
        }

        // 🔥 LLAMAR AL MODELO
        $guardado = $modelo->guardarFirmaRecogida($idservicio, $firmaBase64, 'sello');

        if (!$guardado) {
            // logController('ERROR guardarSello modelo', [
            //     'idservicio' => $idservicio
            // ]);

            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se pudo guardar el sello'
            ]);
            exit;
        }

        // logController('SELLO GUARDADO OK', [
        //     'idservicio' => $idservicio
        // ]);

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Sello guardado correctamente'
        ]);
        exit;

    } catch (Throwable $e) {

        // logController('EXCEPCIÓN guardarSello', [
        //     'mensaje' => $e->getMessage(),
        //     'linea'   => $e->getLine()
        // ]);

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error interno al guardar sello'
        ]);
        exit;
    }
}
if (isset($_POST['accion']) && $_POST['accion'] === 'enviarLinkFirma') {

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
        $tipopago       = trim($_POST['tipopago'] ?? '');

        $link="$idservicio&accion=guardarFirmaRecogida&tipo_pago=$tipopago";

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
/* ============================================================
   ACCIÓN: CONSULTAR ESTADO DE FIRMA
   ============================================================ */
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


// Ninguna acción coincide
echo "Acción no válida en RecogerController.";
exit;
