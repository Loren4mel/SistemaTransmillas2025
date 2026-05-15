<?php
/**
 * Endpoint ligero para consultar preoperacionales pendientes de validacion.
 * Solo devuelve el conteo de registros sin validar del dia actual.
 * Uso exclusivo para roles autorizados a validar (1, 12).
 */
ob_start();
require("login_autentica.php");
ob_clean();

$rol = $_SESSION['usuario_rol'] ?? 0;

if (!in_array($rol, [1, 12])) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0, 'authorized' => false]);
    exit;
}

$fechaactual = date('Y-m-d');
$inicioDia = $fechaactual . " 00:00:00";
$finDia    = $fechaactual . " 23:59:59";

$DB = new DB_mssql;
$DB->conectar();

$sql = "SELECT COUNT(*)
        FROM `pre-operacional`
        WHERE prefechavalidacion IS NULL
        AND preestado IN ('pendiente', 'covid19', 'ingresado')
        AND prefechaingreso BETWEEN '$inicioDia' AND '$finDia'";
$DB->Execute($sql);
$count = (int) $DB->recogedato(0);

header('Content-Type: application/json');
echo json_encode(['count' => $count, 'authorized' => true]);
