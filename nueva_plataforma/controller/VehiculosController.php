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

// OBTENER VEHÍCULOS (DataTable)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $tipodevehiculo = $_POST['tipodevehiculo'] ?? '';
    $estado         = $_POST['estado'] ?? '';
    $propiedad      = $_POST['propiedadvehiculo'] ?? '';
    $vehiculos      = $modelo->obtenerVehiculos($tipodevehiculo, $estado, $propiedad);
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
        'ent_sede'         => $_POST['ent_sede'] ?? '', 
        'ent_equipo_carretera'=> $_POST['ent_equipo_carretera'] ?? '[]',
        'ent_observaciones'   => $_POST['ent_observaciones'] ?? '',
    ];

    $resultado = $modelo->guardarEntregaVehiculo($datos);
    echo json_encode($resultado === true
        ? ['success' => true,  'mensaje' => 'Entrega registrada correctamente']
        : ['success' => false, 'mensaje' => $resultado['error']]
    );
    exit;
}

// OBTENER EQUIPO DE UN VEHÍCULO (para modal entrega)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obtener_equipo_vehiculo'])) {
    $id     = intval($_POST['id']);
    $equipo = $modelo->obtenerEquipoVehiculo($id);
    echo json_encode(['equipo' => $equipo]);
    exit;
}

// GUARDAR COMPARENDO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_comparendo'])) {
    $datos = [
        'com_operador_id' => intval($_POST['com_operador_id']),
        'com_vehiculo_id' => intval($_POST['com_vehiculo_id']),
        'com_estado'      => $_POST['com_estado'],
        'com_fecha'       => $_POST['com_fecha'],
        'com_valor' => str_replace(['.', ','], ['', '.'], $_POST['com_valor']),
        'com_numerocompa' => $_POST['com_numerocompa'],
        'com_titularcompa'=> $_POST['com_titularcompa'],
    ];
    
    $resultado = $modelo->guardarComparendo($datos);
    if (ob_get_length()) ob_clean();
    echo json_encode($resultado === true
        ? ['success' => true,  'mensaje' => 'Comparendo registrado correctamente']
        : ['success' => false, 'mensaje' => $resultado['error'] ?? 'Error al guardar']
    );
    exit;
}

// ACTUALIZAR COMPARENDO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_comparendo'])) {
    $datos = [
        'com_id'     => intval($_POST['com_id']),
        'com_estado' => $_POST['com_estado'],
        'com_valor' => str_replace(['.', ','], ['', '.'], $_POST['com_valor']),
        'com_numerocompa' => $_POST['com_numerocompa'],
        'com_titularcompa' => $_POST['com_titularcompa'],
    ];
    $resultado = $modelo->actualizarComparendo($datos);
    if (ob_get_length()) ob_clean();
    echo json_encode($resultado === true
        ? ['success' => true,  'mensaje' => 'Comparendo actualizado correctamente']
        : ['success' => false, 'mensaje' => $resultado['error'] ?? 'Error al actualizar']
    );
    exit;
}

// OBTENER COMPARENDOS POR VEHÍCULO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obtener_comparendos'])) {
    $id   = intval($_POST['id']);
    $data = $modelo->obtenerComparendosPorVehiculo($id);
    echo json_encode($data);
    exit;
}

// OBTENER CONTEO DE COMPARENDOS DE UN OPERADOR (info hoja de vida)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obtener_comparendos_operador'])) {
    $idOperador = intval($_POST['id_operador']);
    $total      = $modelo->contarComparendosPorOperador($idOperador);
    echo json_encode(['total' => $total]);
    exit;
}

// CARGAR VISTA (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $Dueños      = $modelo->obtenerDueños();
    $Vehiculos   = $modelo->obtenerVehiculos();
    $Sedes       = $modelo->obtenerSedes();
    $operadoresActivos = $modelo->obtenerOperadoresActivos();
    // Calcular ruta base de la aplicación desde el servidor
    // Ej: /SistemaTransmillas2025/nueva_plataforma
    $appBasePath = dirname(dirname($_SERVER['SCRIPT_NAME']));

    include "../view/Vehiculos/index.php";
}   
