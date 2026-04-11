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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_vehiculo'])) {
    $datos = $_POST;
    $resultado = $modelo->guardarVehiculo($datos);
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => $resultado ? true : false,
        'mensaje' => $resultado ? 'Vehículo guardado correctamente' : 'Error al guardar'
    ]);
    exit;
}

// OBTENER vehículo por ID 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obtener_vehiculo'])) {
    $id = $_POST['id'];
    $vehiculo = $modelo->obtenerVehiculoPorId($id);
    echo json_encode($vehiculo);
    exit;
}

// ACTUALIZAR vehículo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_vehiculo'])) {
    $resultado = $modelo->actualizarVehiculo($_POST);
    echo json_encode([
        'success' => $resultado,
        'mensaje' => $resultado ? 'Vehículo actualizado correctamente' : 'Error al actualizar'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $Dueños = $modelo->obtenerDueños();
    include "../view/Vehiculos/index.php";
}