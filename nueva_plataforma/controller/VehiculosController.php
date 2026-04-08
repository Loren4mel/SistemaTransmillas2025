<?php
require_once "../model/VehiculosModel.php";

$modelo = new VehiculosModel();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_campo'])) {
    $id = $_POST['id'];
    $campo = $_POST['campo'];
    $valor = $_POST['valor'];

    $modelo->actualizarCampo($id, $campo, $valor);
    echo json_encode(['ok' => true]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_vehiculo'])) {
    $id = $_POST['id'];
    $modelo->eliminarVehiculo($id);
    echo json_encode(['ok' => true]);
    exit;
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $tipodevehiculo = $_POST['tipodevehiculo'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $vehiculos = $modelo->obtenerVehiculos($tipodevehiculo, $estado);
    echo json_encode($vehiculos);
    exit;
}

include "../view/Vehiculos/index.php";

