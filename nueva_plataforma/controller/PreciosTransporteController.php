<?php
session_name("projecst2344fsdfd");
session_start();

require_once "../model/PreciosTransporteModel.php";

if (empty($_SESSION["usuario_id"])) {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["success" => false, "mensaje" => "La sesion ha finalizado."]);
        exit;
    }
    header("Location: ../../index.php?error_login=8");
    exit;
}

try {
    $modelo = new PreciosTransporteModel();
} catch (RuntimeException $error) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "mensaje" => $error->getMessage()]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json; charset=utf-8");

    if (isset($_POST["listar"])) {
        echo json_encode(["success" => true, "datos" => $modelo->listar()]);
        exit;
    }

    if (isset($_POST["guardar"])) {
        echo json_encode($modelo->guardar($_POST, (int) $_SESSION["usuario_id"]));
        exit;
    }

    if (isset($_POST["eliminar"])) {
        $ok = $modelo->eliminar((int) ($_POST["id"] ?? 0));
        echo json_encode([
            "success" => $ok,
            "mensaje" => $ok ? "Precio eliminado correctamente." : "No fue posible eliminar el precio."
        ]);
        exit;
    }

    echo json_encode(["success" => false, "mensaje" => "Solicitud no valida."]);
    exit;
}

$Ciudades = $modelo->obtenerCiudades();
include "../view/PreciosTransporte/index.php";
