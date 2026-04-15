<?php

require_once "../model/FirmarGuiaModel.php";

$modelo = new recogerEntregarModel();

// 🖊️ Guardar firma del trabajador
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarFirmaEntrega') {
    $idServicio = $_POST['idServicio'] ?? '';
    $firmaBase64 = $_POST['firma'] ?? '';

    if (empty($idServicio) || empty($firmaBase64)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
        exit;
    }

    // Llamamos al modelo
    $resultado = $modelo->guardarFirmaEntrega($idServicio, $firmaBase64);

    echo json_encode(['success' => $resultado]);
    exit;
}


// 🖊️ Guardar firma del trabajador
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarFirmaRecogida') {
    $idServicio = $_POST['idServicio'] ?? '';
    $firmaBase64 = $_POST['firma'] ?? '';

    if (empty($idServicio) || empty($firmaBase64)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
        exit;
    }

    // Llamamos al modelo
    $resultado = $modelo->guardarFirmaRecogida($idServicio, $firmaBase64);

    echo json_encode(['success' => $resultado]);
    exit;
}


// =========================
// CARGA NORMAL DE LA VISTA (GET)
// =========================

$idServicio = $_GET['para'] ?? null;
$accionFirma = $_GET['accion'] ?? null;
$tipoPago = $_GET['tipo_pago'] ?? null;

if (!$idServicio || !$accionFirma) {
    die("Parámetros incompletos en la URL");
}
// =========================
// VALIDAR SI AÚN PUEDE FIRMAR
// =========================

$puedeFirmar = $modelo->servicioPuedeFirmar($idServicio, $accionFirma);
// Preparar mensaje segun tipo de pago para mostrarlo de forma clara al abrir la guia
$tipoPagoNormalizado = strtolower(trim((string) $tipoPago));
$textoPago = "";
$colorPago = "#0b4a8b";
$tituloAvisoPago = "Informacion del pago";
$mensajeAvisoPago = "Revise la informacion de pago antes de firmar.";
$claseAvisoPago = "info";

switch ($tipoPagoNormalizado) {
    case "1":
    case "contado":
        $textoPago = "Guia pagada al momento de la firma";
        $colorPago = "#198754";
        $tituloAvisoPago = "Guia pagada en este momento";
        $mensajeAvisoPago = "Esta guia esta siendo pagada en este momento por la persona que va a firmar.";
        $claseAvisoPago = "success";
        break;

    case "2":
    case "al cobro":
        $textoPago = "Guia pendiente de pago en destino";
        $colorPago = "#dc3545";
        $tituloAvisoPago = "Guia pendiente por pago en destino";
        $mensajeAvisoPago = "Esta guia no ha sido pagada. El pago lo realizara el destinatario en la ciudad de destino.";
        $claseAvisoPago = "warning";
        break;

    case "3":
    case "credito":
        $textoPago = "Guia pendiente por credito";
        $colorPago = "#dc3545";
        $tituloAvisoPago = "Guia pendiente por credito";
        $mensajeAvisoPago = "Esta guia no ha sido pagada porque corresponde a un servicio a credito.";
        $claseAvisoPago = "warning";
        break;
}

include "../view/FirmarGuias/index.php";
