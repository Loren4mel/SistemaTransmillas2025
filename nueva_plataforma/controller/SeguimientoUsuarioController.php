<?php
ini_set('display_errors', 0);
error_reporting(E_ALL); // Keep logging errors, but don't display them
require("../../login_autentica.php"); // Inicia sesión y define $id_usuario, $nivel_acceso, etc.
require_once "../model/SeguimientoUsuarioModel.php";
ob_start(); // Inicia buffer de salida
$captured_errors = []; // Almacenará los errores

set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$captured_errors) {
    $captured_errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    return true; // Evita que PHP muestre el error
});

$modelo = new SeguimientoUsuarioModel();

// --------------------------------------------------------------------
// PETICIONES AJAX PARA DATATABLE (server-side)
// --------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Asegurar que la respuesta siempre sea JSON
    header('Content-Type: application/json');

    try {
        // Validar y sanitizar entradas
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $search = $_POST['search']['value'] ?? '';
        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = $_POST['order'][0]['dir'] ?? 'ASC';
        $columns = $_POST['columns'] ?? [];
        $orderColumn = $columns[$orderColumnIndex]['data'] ?? 'usu_nombre';

        // Filtros personalizados
        $filtros = [
            'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
            'fecha_fin' => $_POST['fecha_fin'] ?? date('Y-m-d'),
            'sede' => $_POST['sede'] ?? '',
            'operario' => $_POST['operario'] ?? '',
            'motivo' => $_POST['motivo'] ?? '',
            'tipo_contrato' => $_POST['tipo_contrato'] ?? ''
        ];

        // Obtener datos del modelo
        $result = $modelo->getRegistrosDataTable($start, $length, $filtros, $search, $orderColumn, $orderDir);
        $totalFiltrados = $modelo->getTotalFiltrados($filtros, $search);
        // Calcular total sin filtrar (usuarios * días)
        $fecha_inicio = new DateTime($filtros['fecha_inicio']);
        $fecha_fin = new DateTime($filtros['fecha_fin']);
        $dias = $fecha_inicio->diff($fecha_fin)->days + 1;
        $totalUsuarios = $modelo->getTotalRegistros($filtros, $search);
        $totalRegistros = $totalUsuarios * $dias;

        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $totalRegistros,
            "recordsFiltered" => $totalFiltrados,
            "data" => $result
        ]);
    } catch (Exception $e) {
        // En producción, registra el error en un log en lugar de mostrarlo
        error_log("Error en AJAX seguimiento: " . $e->getMessage());
        echo json_encode([
            "draw" => $draw ?? 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => "Ocurrió un error interno"
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    $response = ['success' => false, 'message' => 'Acción no válida'];

    switch ($accion) {
        case 'guardar_ingreso_popup':
            $idSeguimiento = intval($_POST['id_seguimiento'] ?? 0);
            $idUsuario = intval($_POST['operario'] ?? 0);
            $fecha = $_POST['fecha'] ?? date('Y-m-d');
            $motivo = $_POST['motivo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $zona = intval($_POST['zona'] ?? 0);
            $prueba = $_POST['prueba'] ?? 'No aplica';
            $imagen = $_FILES['imagen'] ?? null;

            $data = [
                'operario' => $idUsuario,
                'fecha' => $fecha,
                'motivo' => $motivo,
                'descripcion' => $descripcion,
                'zona' => $zona,
                'prueba' => $prueba
            ];

            if ($idSeguimiento > 0) {
                // Actualizar registro existente
                $ok = $modelo->actualizarIngreso($idSeguimiento, $data, $imagen, $_SESSION['usuario_id']);
            } else {
                // Insertar nuevo
                $ok = $modelo->insertarIngreso($data, $imagen, $_SESSION['usuario_id']);
            }

            if ($ok) {
                $response = ['success' => true, 'message' => 'Ingreso guardado correctamente'];
            } else {
                $response = ['message' => 'Error al guardar en la base de datos'];
            }
            break;

        case 'guardar_zona':
            $id = intval($_POST['id']);
            $zona = intval($_POST['zona']);
            $ok = $modelo->actualizarZona($id, $zona);
            $response = ['success' => $ok, 'message' => $ok ? 'Zona actualizada' : 'Error'];
            break;

        case 'guardar_hora_almuerzo':
            $id = intval($_POST['id']);
            $hora = $_POST['hora'];
            $ok = $modelo->actualizarHoraAlmuerzo($id, $hora);
            $response = ['success' => $ok, 'message' => $ok ? 'Hora guardada' : 'Error'];
            break;

        case 'guardar_retorno_almuerzo':
            $id = intval($_POST['id']);
            $hora = $_POST['hora'];
            $ok = $modelo->actualizarRetornoAlmuerzo($id, $hora);
            $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
            break;

        case 'guardar_retorno_oficina':
            $id = intval($_POST['id']);
            $hora = $_POST['hora'];
            $ok = $modelo->actualizarRetornoOficina($id, $hora);
            $response = ['success' => $ok, 'message' => $ok ? 'Retorno guardado' : 'Error'];
            break;

        case 'guardar_companero':
            $id = intval($_POST['id']);
            $companero = intval($_POST['companero']);
            $ok = $modelo->actualizarCompanero($id, $companero);
            $response = ['success' => $ok, 'message' => $ok ? 'Compañero actualizado' : 'Error'];
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// --------------------------------------------------------------------
// OBTENER OPERARIOS POR SEDE (para dropdown dependiente)
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_operarios') {
    $idsede = intval($_GET['idsede'] ?? 0);
    $operarios = $modelo->getOperariosPorSede($idsede);
    header('Content-Type: application/json');
    echo json_encode($operarios);
    exit;
}

// --------------------------------------------------------------------
// OBTENER ZONAS POR SEDE (para modal de ingreso)
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_zonas') {
    $idsede = intval($_GET['idsede'] ?? 0);
    $zonas = $modelo->getZonasPorSede($idsede);
    header('Content-Type: application/json');
    echo json_encode($zonas);
    exit;
}

// --------------------------------------------------------------------
// OBTENER DEUDA DE UN OPERARIO
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_deuda') {
    $idoperario = intval($_GET['idoperario'] ?? 0);
    $deuda = $modelo->getDeudaOperario($idoperario);
    header('Content-Type: application/json');
    echo json_encode(['deuda' => $deuda]);
    exit;
}

// --------------------------------------------------------------------
// GUARDAR NUEVO INGRESO (SeguimientoUser)
// --------------------------------------------------------------------
if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_ingreso') {
    $data = [
        'operario' => intval($_POST['operario']),
        'sede' => intval($_POST['sede']),
        'fecha' => $_POST['fecha'],
        'motivo' => $_POST['motivo'],
        'descripcion' => $_POST['descripcion'],
        'zona' => intval($_POST['zona']),
        'prueba' => $_POST['prueba']
    ];
    $imagen = $_FILES['imagen'] ?? null;

    $ok = $modelo->insertarIngreso($data, $imagen, $_SESSION['usuario_id']);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Ingreso registrado correctamente' : 'Error al registrar ingreso'
    ]);
    exit;
}

// --------------------------------------------------------------------
// GUARDAR CAMBIO EN SEGUIMIENTO (Cambio_seguimientoUser)
// --------------------------------------------------------------------
if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_cambio') {
    $id_seguimiento = intval($_POST['id_seguimiento']);
    $data = [
        'motivo' => $_POST['motivo'],
        'descripcion' => $_POST['descripcion'],
        'zona' => intval($_POST['zona']),
        'prueba' => $_POST['prueba'],
        'horas' => $_POST['horas']
    ];
    $imagen = $_FILES['imagen'] ?? null;

    $ok = $modelo->actualizarIngreso($id_seguimiento, $data, $imagen, $_SESSION['usuario_id']);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Cambios guardados' : 'Error al guardar cambios'
    ]);
    exit;
}

// --------------------------------------------------------------------
// AGREGAR DÍA FESTIVO (para todos los operarios de empresa)
// --------------------------------------------------------------------
if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_festivos') {
    $fecha = $_POST['fecha'];
    $sede = intval($_POST['sede'] ?? 0);
    $ok = $modelo->insertarFestivos($fecha, $sede, $_SESSION['usuario_id']);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Días festivos agregados' : 'Error al agregar festivos'
    ]);
    exit;
}

// --------------------------------------------------------------------
// AGREGAR VACACIONES (para un operario en un rango)
// --------------------------------------------------------------------
if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_vacaciones') {
    $data = [
        'operario' => intval($_POST['operario']),
        'fecha_ini' => $_POST['fecha_ini'],
        'fecha_fin' => $_POST['fecha_fin']
    ];
    $ok = $modelo->insertarVacaciones($data, $_SESSION['usuario_id']);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Vacaciones registradas' : 'Error al registrar vacaciones'
    ]);
    exit;
}

// --------------------------------------------------------------------
// AGREGAR LICENCIA / PERMISO
// --------------------------------------------------------------------
if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_licencia') {
    $data = [
        'operario' => intval($_POST['operario']),
        'fecha_ini' => $_POST['fecha_ini'],
        'fecha_fin' => $_POST['fecha_fin'],
        'motivo' => $_POST['motivo'],
        'descripcion' => $_POST['descripcion']
    ];
    $ok = $modelo->insertarLicencia($data, $_SESSION['usuario_id']);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Licencia registrada' : 'Error al registrar licencia'
    ]);
    exit;
}

// --------------------------------------------------------------------
// AGREGAR LICENCIA / PERMISO
// --------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'get_all_operarios') {
    $operarios = $modelo->getTodosOperarios();
    header('Content-Type: application/json');
    echo json_encode($operarios);
    exit;
}

// --------------------------------------------------------------------
// OBTENER DATOS PARA FORMULARIO EN POPUP (según tipo: zona, horaalmuerzo, horaretorno, horaoficina, trabaja_con, etc.)
// --------------------------------------------------------------------
// Obtener formulario popup
if (isset($_GET['accion']) && $_GET['accion'] === 'form_popup') {
    $tipo = $_GET['tipo'] ?? '';
    $id = intval($_GET['id'] ?? 0);
    $param = $_GET['param'] ?? '';

    // Inicializar variables para la vista
    $idUsuario = 0;
    $idSeguimiento = 0;
    $fecha = date('Y-m-d');
    $sedePredeterminada = $_SESSION['usu_idsede'] ?? 0;

    $data = [];
    switch ($tipo) {
        case 'ingreso_manual':
            // Modo manual: sin operario preseleccionado
            $motivos = $modelo->getMotivosIngreso('ingreso');
            $sedes = $modelo->getSedes(); // para el selector de sede    
            $zonas = $modelo->getZonasPorSede($sedePredeterminada);

            // Variables vacías para el formulario
            $idUsuario = 0;
            $sedePredeterminada = 0; // No preseleccionamos ninguna sede
            $idSeguimiento = 0;
            $fecha = date('Y-m-d');
            $motivoSeleccionado = '';
            $descripcion = '';
            $zonaSeleccionada = 0;
            $pruebaSeleccionada = 'No aplica';
            $usuario = null;

            include "../view/SeguimientoUsuario/popups/ingreso.php";
            break;
        case 'ingreso':
            $motivos = $modelo->getMotivosIngreso('ingreso');
            $sedePredeterminada = $_SESSION['usu_idsede'] ?? 0;
            $idUsuario = 0;
            $idSeguimiento = 0;
            $fecha = date('Y-m-d');
            $motivoSeleccionado = '';
            $descripcion = '';
            $zonaSeleccionada = 0;
            $pruebaSeleccionada = 'No aplica';
            $usuario = null;
            $sedeUsuario = 0;
            $sedeNombre = '';

            if ($id > 0) {
                $seguimiento = $modelo->getSeguimientoById($id);
                if ($seguimiento) {
                    $idSeguimiento = $seguimiento['idseguimiento_user'];
                    $idUsuario = $seguimiento['seg_idusuario'];
                    $fecha = date('Y-m-d', strtotime($seguimiento['seg_fechaingreso']));
                    $motivoSeleccionado = $seguimiento['seg_motivo'];
                    $descripcion = $seguimiento['seg_descr'];
                    $zonaSeleccionada = $seguimiento['seg_idzona'];
                    $pruebaSeleccionada = $seguimiento['seg_alcohol'];
                    $usuario = $modelo->getOperarioById($idUsuario);
                    $sedeUsuario = $usuario['usu_idsede'] ?? 0;
                } else {
                    $usuario = $modelo->getOperarioById($id);
                    if ($usuario) {
                        $idUsuario = $usuario['idusuarios'];
                        $sedeUsuario = $usuario['usu_idsede'];
                    } else {
                        echo "Usuario no encontrado";
                        exit;
                    }
                }
            } else {
                echo "<div class='alert alert-danger'>No se proporcionó un ID válido.</div>";
                exit;
            }

            // Obtener nombre de la sede 
            $sedeInfo = $modelo->getSedeById($sedeUsuario);
            $sedeNombre = $sedeInfo['sed_nombre'] ?? '';

            // Cargar zonas según la sede del operario
            $zonas = $modelo->getZonasPorSede($sedeUsuario);

            // Incluir la vista
            ob_start();
            $vista = __DIR__ . "/../view/SeguimientoUsuario/popups/ingreso.php";
            if (!file_exists($vista)) {
                echo "<div class='alert alert-danger'>La vista no existe.</div>";
                exit;
            }
            include $vista;
            $html = ob_get_clean();
            echo $html;
            exit;
            break;
        case 'zona':
            $data['id'] = $id;
            $data['fecha'] = $param;
            // Obtener zonas de la sede del usuario actual (necesitas el id_sede)
            // Podrías obtener la sede del usuario logueado desde la sesión
            $id_sede_usuario = $_SESSION['usu_idsede'] ?? 0;
            $data['zonas'] = $modelo->getZonasPorSede($id_sede_usuario);
            break;
        case 'hora_almuerzo':
        case 'retorno_almuerzo':
        case 'retorno_oficina':
            $data['id'] = $id;
            $data['fecha'] = $param;
            break;
        case 'companero':
            $data['id'] = $id;
            $data['param'] = $param;
            $data['operarios'] = $modelo->getTodosOperarios();
            break;
    }

    // Pasar variables a la vista parcial
    extract($data);
    ob_start();
    include "../view/SeguimientoUsuario/popups/$tipo.php";
    $html = ob_get_clean();
    echo $html;
    exit;
}

// --------------------------------------------------------------------
// MOSTRAR LA VISTA (index)
// --------------------------------------------------------------------
// Obtener datos para los filtros
$sedes = $modelo->getSedes();
$motivos = $modelo->getMotivosIngreso();
$motivosLicencia = $modelo->getMotivosLicencia();
$tiposContrato = $modelo->getTiposContrato();

include "../view/SeguimientoUsuario/index.php";