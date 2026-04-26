<?php

session_name("projecst2344fsdfd");
session_start();

require_once "../model/VehiculosModel.php";

$modelo = new VehiculosModel();

// ACTUALIZAR CAMPO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_campo'])) {
    $id    = $_POST['id'];
    $campo = $_POST['campo'];
    $valor = $_POST['valor'];
    $modelo->actualizarCampo($id, $campo, $valor);
    echo json_encode(['ok' => true]);
    exit;
}

// ELIMINAR VEHÍCULO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_vehiculo'])) {
    $id = $_POST['id'];
    $modelo->eliminarVehiculo($id);
    echo json_encode(['ok' => true]);
    exit;
}

// AJAX — OBTENER VEHÍCULOS (DataTable)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $tipodevehiculo = $_POST['tipodevehiculo'] ?? '';
    $estado         = $_POST['estado'] ?? '';
    $vehiculos      = $modelo->obtenerVehiculos($tipodevehiculo, $estado);
    echo json_encode($vehiculos);
    exit;
}

// GUARDAR VEHÍCULO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_vehiculo'])) {
    $datos     = $_POST;
    $resultado = $modelo->guardarVehiculo($datos);
    if (ob_get_length()) ob_clean();
    if (is_array($resultado) && isset($resultado['error'])) {
        echo json_encode(['success' => false, 'mensaje' => $resultado['error']]);
    } else {
        echo json_encode([
            'success' => $resultado ? true : false,
            'mensaje' => $resultado ? 'Vehículo guardado correctamente' : 'Error al guardar'
        ]);
    }
    exit;
}

// OBTENER VEHÍCULO POR ID (modal editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obtener_vehiculo'])) {
    $id       = $_POST['id'];
    $vehiculo = $modelo->obtenerVehiculoPorId($id);
    echo json_encode($vehiculo);
    exit;
}

// ACTUALIZAR VEHÍCULO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_vehiculo'])) {
    $resultado = $modelo->actualizarVehiculo($_POST);
    echo json_encode([
        'success' => $resultado,
        'mensaje' => $resultado ? 'Vehículo actualizado correctamente' : 'Error al actualizar'
    ]);
    exit;
}

// GUARDAR ENTREGA DE VEHÍCULO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_entrega'])) {
    $datos = [
        'ent_fechaentrega' => $_POST['ent_fechaentrega'],
        'ent_vehiculo'     => $_POST['ent_vehiculo'],
        'ent_userregistra' => $_SESSION['usuario_nombre'],
        'ent_idusuario'    => intval($_POST['ent_idusuario']),
        'ent_tipoentrega'  => $_POST['ent_tipoentrega'], 
        'ent_fecharegista' => date('Y-m-d'),
    ];

    $resultado = $modelo->guardarEntregaVehiculo($datos);
    echo json_encode($resultado === true
        ? ['success' => true,  'mensaje' => 'Entrega registrada correctamente']
        : ['success' => false, 'mensaje' => $resultado['error']]
    );
    exit;
}

// CARGAR VISTA (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $Dueños      = $modelo->obtenerDueños();
    $Vehiculos   = $modelo->obtenerVehiculos();
    /*
    $Usuarios    = $modelo->obtenerUsuariosActivos(); 
    $conductoresActivos = $modelo->obtenerConductoresActivos();
    */
    $operadoresActivos = $modelo->obtenerOperadoresActivos();
    include "../view/Vehiculos/index.php";
}   