<?php

require_once "../model/recogerEntregarModel.php";

$modelo = new recogerEntregarModel();

// 🖊️ Guardar firma del trabajador
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarFirmaEntrega') {
    $idServicio = $_POST['idServicio'] ?? '';
    $firmaBase64 = $_POST['firma'] ?? '';
    $origenFirma = $_POST['origenFirma'] ?? 'firma';

    if (empty($idServicio) || empty($firmaBase64)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
        exit;
    }

    // Llamamos al modelo
    $resultado = $modelo->guardarFirmaEntrega($idServicio, $firmaBase64, $origenFirma);

    echo json_encode(['success' => $resultado]);
    exit;
}


// 🖊️ Guardar firma del trabajador
if (isset($_POST['accion']) && $_POST['accion'] === 'guardarFirmaRecogida') {
    $idServicio = $_POST['idServicio'] ?? '';
    $firmaBase64 = $_POST['firma'] ?? '';
    $origenFirma = $_POST['origenFirma'] ?? 'firma';

    if (empty($idServicio) || empty($firmaBase64)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
        exit;
    }

    // Llamamos al modelo
    $resultado = $modelo->guardarFirmaRecogida($idServicio, $firmaBase64, $origenFirma);

    echo json_encode(['success' => $resultado]);
    exit;
}




include "../view/recogerEntregar/firmar.php";
