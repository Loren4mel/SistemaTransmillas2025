<?php
require_once "../model/permisosModel.php";

$modelo = new Permisos();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_permiso'])) {
    header('Content-Type: application/json');

    $respuesta = $modelo->crearPermiso(
        $_POST['menu_idmenu'] ?? 0,
        $_POST['roles_idroles'] ?? 0,
        $_POST['per_crear'] ?? 0,
        $_POST['per_editar'] ?? 0,
        $_POST['per_eliminar'] ?? 0,
        $_POST['per_consultar'] ?? 0
    );

    echo json_encode($respuesta);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_campo'])) {
    $id = $_POST['id'];
    $campo = $_POST['campo'];
    $valor = $_POST['valor'];

    header('Content-Type: application/json');
    $ok = $modelo->actualizarCampo($id, $campo, $valor);
    echo json_encode(['ok' => $ok]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['obtener_permiso'])) {
    header('Content-Type: application/json');
    $permiso = $modelo->obtenerPermisoPorId($_POST['id'] ?? 0);
    echo json_encode([
        'ok' => (bool) $permiso,
        'data' => $permiso
    ]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_edicion_permiso'])) {
    header('Content-Type: application/json');

    $respuesta = $modelo->actualizarPermiso(
        $_POST['idpermisos'] ?? 0,
        $_POST['menu_idmenu'] ?? 0,
        $_POST['roles_idroles'] ?? 0,
        $_POST['per_crear'] ?? 0,
        $_POST['per_editar'] ?? 0,
        $_POST['per_eliminar'] ?? 0,
        $_POST['per_consultar'] ?? 0
    );

    echo json_encode($respuesta);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_permiso'])) {
    $id = $_POST['id'];
    header('Content-Type: application/json');
    $ok = $modelo->eliminarPermiso($id);
    echo json_encode(['ok' => $ok]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['listar_dispositivos'])) {
    $idUsuario = $_POST['idusuario'];
    $dispositivos = $modelo->listarDispositivos($idUsuario);
    echo json_encode($dispositivos);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autorizar_dispositivo'])) {
    $modelo->autorizarDispositivo($_POST['id']);
    echo json_encode(['ok' => true]);
    exit;
}

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bloquear_dispositivo'])) {
//     $modelo->bloquearDispositivo($_POST['id']);
//     echo json_encode(['ok' => true]);
//     exit;
// }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $rol = $_POST['rol'] ?? '';
    $principal = $_POST['principal'] ?? '';
    $secundario = $_POST['secundario'] ?? '';

    $usuarios = $modelo->obtenerPermisos($rol, $principal, $secundario);
    echo json_encode($usuarios);
    exit;
}

$roles = $modelo->obtenerRoles();
$principales = $modelo->obtenerMenuPrincipal();
$secundarios = $modelo->obtenerMenuSecundario();
include "../view/permisos/index.php";
