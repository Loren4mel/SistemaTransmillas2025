<?php
/**
 * AdminCuestionariosController - Controlador de administracion de cuestionarios
 *
 * Gestiona peticiones CRUD para plantillas, versiones, secciones y preguntas
 * del esquema unificado cuestionarios_*.
 */

ob_start();

require_once __DIR__ . '/../helpers/ErrorHandler.php';
ErrorHandler::setup();

require("../../login_autentica.php");

require_once __DIR__ . '/../model/AdminCuestionariosModel.php';

$model = new AdminCuestionariosModel();
handleRequest($model);

/**
 * Determina el tipo de peticion y la maneja apropiadamente.
 */
function handleRequest($model)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
        handleAjaxRequest($model);
        return;
    }

    // Peticion GET: popups via AJAX
    if (isset($_GET['popup'])) {
        handleGetPopup($model);
        return;
    }

    $modulo = $_GET['modo'] ?? 'preop';
    ob_clean();
    require_once __DIR__ . '/../view/AdminCuestionarios/index.php';
}

/**
 * Maneja peticiones GET para cargar popups.
 */
function handleGetPopup($model)
{
    $popup = $_GET['popup'];
    $data = [];

    switch ($popup) {
        case 'plantilla':
            $data['slug_modulo'] = $_GET['modulo'] ?? ($_GET['modo'] ?? 'preop');
            $idPlantilla = (int) ($_GET['id_plantilla'] ?? 0);
            if ($idPlantilla > 0) {
                $p = $model->getPlantilla($idPlantilla);
                if ($p) $data = array_merge($data, $p);
            }
            $view = 'editar_plantilla.php';
            break;

        case 'version':
            $data['id_plantilla'] = (int) ($_GET['id_plantilla'] ?? 0);
            $view = 'editar_version.php';
            break;

        case 'seccion':
            $data['id_version'] = (int) ($_GET['id_version'] ?? 0);
            $idSeccion = (int) ($_GET['id_seccion'] ?? 0);
            if ($idSeccion > 0) {
                $sec = $model->getSeccionPorId($idSeccion);
                if ($sec) {
                    $data['seccion'] = $sec;
                    $data['id_version'] = (int) $sec['id_version'];
                }
            }
            $view = 'editar_seccion.php';
            break;

        case 'pregunta':
            $idSeccion = (int) ($_GET['id_seccion'] ?? 0);
            $data['id_seccion'] = $idSeccion;
            $view = 'editar_pregunta.php';
            // Determinar modulo (preop/sst) desde la seccion
            $data['slug_modulo'] = 'preop';
            if ($idSeccion > 0) {
                $modSec = $model->getModuloPorSeccion($idSeccion);
                if ($modSec) $data['slug_modulo'] = $modSec;
                $data['padres_disponibles'] = $model->getPreguntasDisponiblesParaPadre($idSeccion);
            }
            break;

        case 'preview':
            $idSeccion = (int) ($_GET['id_seccion'] ?? 0);
            $idVersion = (int) ($_GET['id_version'] ?? 0);
            $view = 'vista_previa.php';
            if ($idSeccion > 0) {
                $data['preview'] = $model->getPreviewSeccion($idSeccion);
            } elseif ($idVersion > 0) {
                $data['preview'] = $model->getPreviewVersion($idVersion);
            } else {
                $data['error'] = 'Debe especificar una seccion o version';
            }
            break;

        default:
            header('HTTP/1.0 404 Not Found');
            echo 'Popup no encontrado';
            return;
    }

    ob_clean();
    $viewPath = __DIR__ . '/../view/AdminCuestionarios/popups/' . $view;
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado: ' . $view;
    }
}

/**
 * Maneja peticiones AJAX.
 */
function handleAjaxRequest($model)
{
    try {
        $accion = $_POST['accion'];
        $response = ['success' => false, 'message' => 'Accion no valida'];

        switch ($accion) {
            // === PLANTILLAS ===
            case 'listar_plantillas':
                $modulo = $_POST['slug_modulo'] ?? null;
                $response = ['success' => true, 'data' => $model->getPlantillas($modulo)];
                break;

            case 'guardar_plantilla':
                $id = (int) ($_POST['id_plantilla'] ?? 0);
                // Si edita plantilla existente, verificar que no tenga versiones activas/historicas
                if ($id > 0 && $model->plantillaTieneVersionesBloqueadas($id)) {
                    $response = ['success' => false, 'message' => 'No se puede modificar una plantilla que tiene versiones ACTIVAS o HISTORICAS'];
                    break;
                }
                $id = $model->savePlantilla($_POST);
                $response = ['success' => true, 'message' => 'Plantilla guardada', 'id' => $id];
                break;

            case 'eliminar_plantilla':
                $id = (int) ($_POST['id_plantilla'] ?? 0);
                if ($id > 0 && $model->plantillaTieneVersionesBloqueadas($id)) {
                    $response = ['success' => false, 'message' => 'No se puede eliminar una plantilla con versiones ACTIVAS o HISTORICAS'];
                    break;
                }
                $response = ['success' => $model->deletePlantilla($id), 'message' => 'Plantilla eliminada'];
                break;

            // === VERSIONES ===
            case 'listar_versiones':
                $idPlantilla = (int) ($_POST['id_plantilla'] ?? 0);
                $response = ['success' => true, 'data' => $model->getVersiones($idPlantilla)];
                break;

            case 'guardar_version':
                $id = $model->saveVersion($_POST);
                $response = ['success' => true, 'message' => 'Version guardada', 'id' => $id];
                break;

            case 'activar_version':
                $idVersion = (int) ($_POST['id_version'] ?? 0);
                $model->activarVersion($idVersion);
                $response = ['success' => true, 'message' => 'Version activada'];
                break;

            // === SECCIONES ===
            case 'listar_secciones':
                $idVersion = (int) ($_POST['id_version'] ?? 0);
                $response = ['success' => true, 'data' => $model->getSecciones($idVersion)];
                break;

            case 'guardar_seccion':
                $id = (int) ($_POST['id_seccion'] ?? 0);
                // Si edita existente, verificar que la version sea BORRADOR
                if ($id > 0) {
                    $estado = $model->getEstadoVersionPorSeccion($id);
                    if ($estado && $estado !== 'BORRADOR') {
                        $response = ['success' => false, 'message' => 'No se puede modificar una seccion de una version ' . strtolower($estado) . '. Cree una nueva version primero.'];
                        break;
                    }
                } else {
                    // Si es nueva, verificar que la version destino sea BORRADOR
                    $idVersion = (int) ($_POST['id_version'] ?? 0);
                    if ($idVersion > 0) {
                        $estado = $model->getEstadoVersion($idVersion);
                        if ($estado && $estado !== 'BORRADOR') {
                            $response = ['success' => false, 'message' => 'No se pueden agregar secciones a una version ' . strtolower($estado) . '. Cree una nueva version primero.'];
                            break;
                        }
                    }
                }
                $id = $model->saveSeccion($_POST);
                $response = ['success' => true, 'message' => 'Seccion guardada', 'id' => $id];
                break;

            case 'eliminar_seccion':
                $id = (int) ($_POST['id_seccion'] ?? 0);
                if ($id > 0) {
                    $estado = $model->getEstadoVersionPorSeccion($id);
                    if ($estado && $estado !== 'BORRADOR') {
                        $response = ['success' => false, 'message' => 'No se puede eliminar una seccion de una version ' . strtolower($estado) . '.'];
                        break;
                    }
                }
                $response = ['success' => $model->deleteSeccion($id), 'message' => 'Seccion eliminada'];
                break;

            case 'reordenar_secciones':
                $idVersion = (int) ($_POST['id_version'] ?? 0);
                if ($idVersion > 0) {
                    $estado = $model->getEstadoVersion($idVersion);
                    if ($estado && $estado !== 'BORRADOR') {
                        $response = ['success' => false, 'message' => 'No se puede reordenar secciones de una version ' . strtolower($estado)];
                        break;
                    }
                }
                $ordenes = $_POST['ordenes'] ?? [];
                $model->reordenarSecciones($idVersion, $ordenes);
                $response = ['success' => true, 'message' => 'Orden actualizado'];
                break;

            // === PREGUNTAS ===
            case 'listar_preguntas':
                $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
                $response = ['success' => true, 'data' => $model->getPreguntas($idSeccion)];
                break;

            case 'guardar_pregunta':
                $id = (int) ($_POST['id_pregunta'] ?? 0);
                // Si edita existente, verificar que la version sea BORRADOR
                if ($id > 0) {
                    $estado = $model->getEstadoVersionPorPregunta($id);
                    if ($estado && $estado !== 'BORRADOR') {
                        $response = ['success' => false, 'message' => 'No se puede modificar una pregunta de una version ' . strtolower($estado) . '. Cree una nueva version primero.'];
                        break;
                    }
                }
                $id = $model->savePregunta($_POST);
                $response = ['success' => true, 'message' => 'Pregunta guardada', 'id' => $id];
                break;

            case 'eliminar_pregunta':
                $id = (int) ($_POST['id_pregunta'] ?? 0);
                if ($id > 0) {
                    $estado = $model->getEstadoVersionPorPregunta($id);
                    if ($estado && $estado !== 'BORRADOR') {
                        $response = ['success' => false, 'message' => 'No se puede eliminar una pregunta de una version ' . strtolower($estado) . '.'];
                        break;
                    }
                }
                $response = ['success' => $model->deletePregunta($id), 'message' => 'Pregunta eliminada'];
                break;

            case 'reordenar_preguntas':
                $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
                if ($idSeccion > 0) {
                    $estado = $model->getEstadoVersionPorSeccion($idSeccion);
                    if ($estado && $estado !== 'BORRADOR') {
                        $response = ['success' => false, 'message' => 'No se puede reordenar preguntas de una version ' . strtolower($estado)];
                        break;
                    }
                }
                $ordenes = $_POST['ordenes'] ?? [];
                $model->reordenarPreguntas($idSeccion, $ordenes);
                $response = ['success' => true, 'message' => 'Orden actualizado'];
                break;

            case 'listar_padres_disponibles':
                $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
                $response = ['success' => true, 'data' => $model->getPreguntasDisponiblesParaPadre($idSeccion)];
                break;
        }

        ErrorHandler::sendJsonResponse($response);
    } catch (Exception $e) {
        error_log("AdminCuestionariosController: " . $e->getMessage());
        ErrorHandler::sendJsonResponse([
            'success' => false,
            'message' => 'Error interno: ' . $e->getMessage()
        ], 500);
    }
}
