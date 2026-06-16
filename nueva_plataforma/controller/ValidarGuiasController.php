<?php

require_once "../model/ValidarGuiasModel.php";

$modelo = new ValidarGuiaModel();

$acceso=$_POST['acceso'] ?? '';
$sede=$_POST['sede'] ?? '';
$usuario=$_POST['usuario'] ?? '';
$idUsuario=$_POST['id_usuario'] ?? '';


    // if($acceso!=1 and $acceso!=10){
    //     $_POST['ciudad'] = $sede;
    // }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $fecha = $_POST['fecha'] ?? '';
    $ciudadO = $_POST['ciudadO'] ?? '';
    $ciudadD = $_POST['ciudadD'] ?? '';
    $conde2 = '';




    $sedesD = $modelo->sedes($ciudadD);
    if($sedesD=='0'){
        $conde2.="";

    }else {
           
        $conde2.="and ser_ciudadentrega in $sedesD "; 	
    }

    $sedesO = $modelo->sedes($ciudadO);
    if($sedesO=='0'){
        $conde2.="";

    }else {
           
        $conde2.="and cli_idciudad in $sedesO "; 	
    }

    $servicios = $modelo->obtenerSerProgramados($fecha, $conde2);
    echo json_encode($servicios);
    exit;
}

// Acción para buscar Validadas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'obtenerOperadoresTrabajando') {
    $ciudad = $_POST['ciudad'] ?? '';
    $operadoresTrabajando = $modelo->obtenerOperadoresTrabajandoHoy($ciudad);

    header('Content-Type: application/json');
    echo json_encode($operadoresTrabajando ?: []);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'preAsignarGuias') {
    $operador = $_POST['operador'] ?? 0;
    $guias = $_POST['guias'] ?? [];
    $idUsuarioPreasigna = $_POST['id_usuario'] ?? $idUsuario;
    $nombrePreasigna = $_POST['id_nombre'] ?? $usuario;
    $respuesta = $modelo->preAsignarGuias($operador, is_array($guias) ? $guias : [], $idUsuarioPreasigna, $nombrePreasigna);

    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'deshacerPreAsignacionGuias') {
    $guias = $_POST['guias'] ?? [];
    $respuesta = $modelo->deshacerPreAsignacionGuias(is_array($guias) ? $guias : []);

    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'buscarValidadas') {
    $fecha = $_POST['fecha'] ?? '';
    $ciudadO = $_POST['ciudadO'] ?? '';
    $ciudadD = $_POST['ciudadD'] ?? '';
    $conde2 = '';

        $sedesD = $modelo->sedes($ciudadD);
    if($sedesD=='0'){
        $conde2.="";

    }else {
           
        $conde2.="and ser_ciudadentrega in $sedesD "; 	
    }

    $sedesO = $modelo->sedes($ciudadO);
    if($sedesO=='0'){
        $conde2.="";

    }else {
           
        $conde2.="and cli_idciudad in $sedesO "; 	
    }

    $validadas = $modelo->buscarValidadas($fecha, $conde2);

    header('Content-Type: application/json');
    echo json_encode($validadas ?: []);
    exit;
}



// Acción para actualizar servicio
if (isset($_POST['accion']) && $_POST['accion'] === 'actualizarServicio') {
    $descr      = $_POST['descripcion'] ?? '';
    $llego      = $_POST['llego'] ?? '';
    $piezasg    = $_POST['piezasg'] ?? 0;
    $pieza   = $_POST['pieza'] ?? 0;
    $guia       = $_POST['guia'] ?? '';
    $id_usuario = $_POST['id_usuario'] ?? 0;
    $id_nombre  = $_POST['id_nombre'] ?? '';
    $idguia     = $_POST['idguia'] ?? '';
    $datosIncautacion = [
        'fecha' => $_POST['inc_fecha'] ?? '',
        'idsede' => $_POST['inc_idsede'] ?? '',
        'comentario' => $_POST['inc_comentario'] ?? ''
    ];

    // 👇 Manejo de la imagen comprimida
    $nombreArchivo = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $nombreTmp = $_FILES['imagen']['tmp_name'];
        $nombreArchivo = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['imagen']['name'])); 
        $rutaDestino = __DIR__ . "/../../imgServicios/" . $nombreArchivo;

        // --- función para comprimir ---
        function comprimirImagen($origen, $destino, $calidad = 70) {
            $info = getimagesize($origen);
            if (!$info || empty($info['mime'])) {
                return false;
            }

            if ($info['mime'] == 'image/jpeg') {
                $imagen = imagecreatefromjpeg($origen);
                imagejpeg($imagen, $destino, $calidad);
            } elseif ($info['mime'] == 'image/png') {
                $imagen = imagecreatefrompng($origen);
                // PNG usa compresión de 0 (sin compresión) a 9 (máxima)
                imagepng($imagen, $destino, 9);
            } elseif ($info['mime'] == 'image/webp') {
                $imagen = imagecreatefromwebp($origen);
                imagewebp($imagen, $destino, $calidad);
            } else {
                return false; // formato no soportado
            }

            return $destino;
        }

        // 👇 Comprimir en vez de mover directamente
        if (!comprimirImagen($nombreTmp, $rutaDestino, 70)) {
            if (!move_uploaded_file($nombreTmp, $rutaDestino)) {
                $nombreArchivo = null; // si falla, no se guarda
            }
        }
    }

    // ✅ Llamar al modelo y pasar también la foto
    $respuesta = $modelo->actualizarServicio(
        $descr, 
        $llego, 
        $piezasg, 
        $guia, 
        $id_usuario, 
        $id_nombre,
        $idguia,
        $nombreArchivo,// 👈 imagen ya comprimida
        $pieza,
        $datosIncautacion
    );

    // Devolver respuesta en JSON
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}



// Acción para buscar servicio
if ( isset($_GET['accion']) && $_GET['accion'] === 'buscarServicioConGuia') {
    $id = $_GET['id'] ?? '';
    $pieza = $_GET['pieza'] ?? '';
    $servicio = $modelo->buscarServicioConGuia($id,$pieza);

    header('Content-Type: application/json');
    echo json_encode($servicio ?: null);
    exit;
}

// Acción para buscar servicio
if ( isset($_GET['accion']) && $_GET['accion'] === 'buscarAsignacion') {
    $id = $_GET['guia'] ?? '';
    
    $servicio = $modelo->buscarAsignacion($id);

    header('Content-Type: application/json');
    echo json_encode($servicio ?: null);
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] === 'validarGuiaYPiezas') {
    $guia        = $_GET['guia']        ?? '';
    $id_usuario  = $_GET['id_usuario']  ?? 0;
    $id_nombre   = $_GET['id_nombre']   ?? '';
    $tipoVehiculo = $_GET['tipoVehiculo'] ?? null;

    $resultado = $modelo->validarGuiaYPiezas($guia, $id_usuario, $id_nombre, $tipoVehiculo);

    header('Content-Type: application/json');
    echo json_encode($resultado);
    exit;
}

// Acción para buscar remesas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'buscarRemesas') {
    // $fecha = $_POST['fecha'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $operador = $_POST['operador'] ?? '';

    $remesas = $modelo->buscarRemesas( $ciudad);

    header('Content-Type: application/json');
    echo json_encode($remesas ?: []);
    exit;
}
// Acción para validar Remesas
if (isset($_POST['accion']) && $_POST['accion'] === 'Verificar Remesa') {
    $id = $_POST['id_param'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $resultado = $modelo->validarRemesa($id, $descripcion,$usuario);

    header('Content-Type: application/json');
    echo json_encode($resultado ?: null);
    exit;
}

if($acceso!=1 and $acceso!=10){
    $conde=" and idsedes='$sede' ";
}

$ciudades = $modelo->obtenerCiudades($conde);
$ciudador = $modelo->obtenerCiudades();
$operadores = $modelo->obtenerOperadores($sede);
$creditos = $modelo->obtenerCreditos();
include "../view/validarGuias/index.php";
