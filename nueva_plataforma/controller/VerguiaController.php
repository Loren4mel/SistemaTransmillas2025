<?php
// /nueva_plataforma/controller/VerguiaController.php
require_once "../model/VerguiaModel.php";

date_default_timezone_set('America/Bogota');

$modelo = new VerguiaModel();

// ✅ Entrada principal por URL tipo:
// /nueva_plataforma/controller/VerguiaController.php?guia=BGT283634
$guiallega = $_GET['guia']  ?? null;
$vis        = $_GET['vis']   ?? null; // por si quieres usarlo como en ticket_renovado


// Obtener la última letra
$letra = substr($guiallega, -1);

// Obtener el resto del texto sin la última letra
$codigoGuia = substr($guiallega, 0, -1);

// Determinar el tipo
if ($letra === "R") {
    $tipo = "Recogida";
} elseif ($letra === "E") {
    $tipo = "Entrega";
} else {
    $tipo = "Desconocido";
}



if (!$codigoGuia) {
    // Si no viene la guía, puedes mostrar mensaje simple o redirigir
    $error = "No se recibió el parámetro de guía.";
    include "../view/Verguia/index.php";
    exit;
}

// Traer guía completa desde el modelo
$guia = $modelo->obtenerGuiaPorCodigo($codigoGuia);

if (!$guia) {
    $error = "No se encontró información para la guía: " . htmlspecialchars($codigoGuia);
    include "../view/Verguia/index.php";
    exit;
}

/**
 * Preparar variables que usará la vista
 */

// Alias cortos para no escribir tanto en la vista
$totales       = $guia['totales'];
$tipoServicio  = $guia['tipo_servicio'];
$pagoInfo      = $guia['pago_info'];
$creditoNombre = $guia['credito_detalle'];
$firmas        = $guia['firmas'];
$ubicaciones = $guia['ubicaciones'];

// Tipo de pago / estado visual
$colorTP = "";
$textoTP = "";
$tipoPagoTexto = $totales['clasificacion_texto']; // Contado, Credito, etc.
$esEntregaAlCobro = ($tipo === 'Entrega' && $tipoPagoTexto == 'Al Cobro');

if ($esEntregaAlCobro) {
    $colorTP = "bg-success text-white";
    $textoTP = "Guia paga en destino";
} elseif ($tipoPagoTexto == 'Credito' || $tipoPagoTexto == 'Al Cobro' || (int)$guia['ser_pendientecobrar'] === 1) {
    $colorTP = "bg-danger text-white";
    $textoTP = "Falta pago";
} elseif ($tipoPagoTexto == 'Contado') {
    $colorTP = "bg-success text-white";
    $textoTP = "Pagada";
} else {
    $colorTP = "bg-secondary text-white";
    $textoTP = "Pendiente";
}

// Si tiene crédito asociado, concatenar
if ($tipoPagoTexto == 'Credito' && $creditoNombre) {
    $tipoPagoTexto .= " / " . $creditoNombre;
}

// Pago en dónde
if ($pagoInfo) {
    $pagoEn = $pagoInfo['pago_text'];
} else {
    if ($esEntregaAlCobro) {
        $pagoEn = "Destino";
    } elseif ($tipoPagoTexto == 'Contado') {
        $pagoEn = "Efectivo";
    } else {
        $pagoEn = "Por Definir";
    }
}

include "../view/Verguia/index.php";
