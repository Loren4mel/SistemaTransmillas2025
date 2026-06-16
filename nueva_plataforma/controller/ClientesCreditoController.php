<?php

// error_reporting(E_ALL); // Mostrar todos los errores
// ini_set('display_errors', 1); // Habilitar la visualización
// ini_set('display_startup_errors', 1); // Errores en el arranque
require_once "../model/ClientesCreditoModel.php";

$modelo = new ClientesCreditoModel();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Parámetros enviados por DataTables
    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';

    // Obtener total de registros sin filtrar
    $totalRegistros = $modelo->contarClientes();

    // Obtener total de registros filtrados
    $totalFiltrados = $modelo->contarClientes($searchValue, $fecha, $ciudad);

    // Obtener solo los registros necesarios para esta página
    $clientes = $modelo->obtenerClientesPaginado($start, $length, $searchValue, $fecha, $ciudad);

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRegistros,
        "recordsFiltered" => $totalFiltrados,
        "data" => $clientes
    ]);
    exit;
}
// Acción para buscar cliente por teléfono
if (isset($_GET['accion']) && $_GET['accion'] === 'buscar_por_telefono') {
    $telefono = $_GET['telefono'] ?? '';
    $cliente = $modelo->buscarClientePorTelefono($telefono);

    header('Content-Type: application/json');
    echo json_encode($cliente ?: null);
    exit;
}
if (isset($_POST['accion']) && $_POST['accion'] === 'editar_cliente') {
    $data = $_POST; // Aquí vienen todos los campos del formulario
    
    // Procesar la actualización en el modelo
    $resultado = $modelo->actualizarCliente($data);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $resultado,
        'message' => $resultado ? 'Cliente editado con éxito.' : 'Error al editar cliente.'
    ]);
    exit;
}
$roles = $modelo->obtenerRoles();
$ciudades = $modelo->obtenerCiudades();
$creditos = $modelo->obtenerCreditos();

include "../view/ClientesCredito/index.php";




