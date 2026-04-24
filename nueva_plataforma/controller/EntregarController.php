<?php
require_once "../model/EntregarModel.php";

$modelo = new EntregarModel();

/* ============================================================
   ACCION: CARGAR SELLO COMO FIRMA DE ENTREGA
   ============================================================ */
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarSello') {

    header('Content-Type: application/json; charset=utf-8');

    try {
        if (empty($_POST['idservicio']) || !is_numeric($_POST['idservicio'])) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'ID de servicio invalido'
            ]);
            exit;
        }

        if (empty($_POST['firmaBase64'])) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se recibio la imagen del sello'
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
                'mensaje' => 'Formato de imagen invalido'
            ]);
            exit;
        }

        $guardado = $modelo->guardarFirmaEntrega((int)$_POST['idservicio'], $firmaBase64, 'sello');

        echo json_encode([
            'ok' => (bool)$guardado,
            'mensaje' => $guardado ? 'Sello guardado correctamente' : 'No se pudo guardar el sello'
        ]);
        exit;

    } catch (Throwable $e) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error interno al guardar sello'
        ]);
        exit;
    }
}

/* ============================================================
   ACCION: ENVIAR LINK DE FIRMA DE ENTREGA
   ============================================================ */
if (isset($_POST['accion']) && $_POST['accion'] === 'enviarLinkFirma') {

    header('Content-Type: application/json; charset=utf-8');

    try {
        $idservicio = $_POST['idservicio'] ?? null;
        $telefono   = trim($_POST['telefono'] ?? '');
        $link       = trim($_POST['link'] ?? '');

        if (empty($idservicio) || !is_numeric($idservicio)) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'ID de servicio invalido'
            ]);
            exit;
        }

        if (empty($telefono)) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Telefono requerido'
            ]);
            exit;
        }

        if ($link === '') {
            $link = $idservicio . '&accion=guardarFirmaEntrega&tipo_pago=';
        }

        $resp = $modelo->reEnviarFirmaWhat($telefono, 44, (int)$idservicio, $link);

        if (empty($resp['ok'])) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se pudo enviar el mensaje de WhatsApp'
            ]);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Link enviado correctamente'
        ]);
        exit;

    } catch (Throwable $e) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error interno al enviar link'
        ]);
        exit;
    }
}

/* ============================================================
   ACCION: CONSULTAR ESTADO DE FIRMA
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




/* ============================================================
   ACCIÓN: GUARDAR ENTREGA
   ============================================================ */
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarEntrega') {

    $resp = $modelo->guardarEntrega($_POST, $_FILES);

    header("Content-Type: application/json");
    echo json_encode($resp);
    exit;
}


/* ============================================================
   ACCIÓN: GUARDAR NO ENTREGADO
   ============================================================ */
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarNoEntregar') {

    ob_start();
    $resp = $modelo->guardarNoEntregar($_POST, $_FILES);
    $debug = ob_get_clean();

    if ($debug !== "") {
        file_put_contents(__DIR__ . "/debug_output.log", $debug, FILE_APPEND);
    }

    file_put_contents(__DIR__ . "/RESPUESTA_DEBUG.txt", json_encode($resp));

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($resp);
    exit;
}

/* ============================================================
   ACCIÓN: MOSTRAR VISTA
   ============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'vista') {

    // Recibir idServicio por GET
    $idServicio = isset($_GET['idServicio']) ? $_GET['idServicio'] : 0;

    // Recibir datos POST si vienen desde otro módulo
    $sede    = $_POST['sede']    ?? '';
    $acceso  = $_POST['acceso']  ?? '';
    $usuario = $_POST['usuario'] ?? '';

    // Cargar vista principal
    include "../view/Entregar/index.php";
    exit;
}


/* ============================================================
   ACCIÓN: BUSCAR DATOS DEL SERVICIO
   ============================================================ */
if (isset($_GET['accion']) && $_GET['accion'] === 'buscarEntrega') {

    $id = isset($_GET['id']) ? $_GET['id'] : 0;

    $datos = $modelo->buscarEntrega($id);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos);
    exit;
}


// Si no coincide ninguna acción
echo "Acción no válida.";
exit;
