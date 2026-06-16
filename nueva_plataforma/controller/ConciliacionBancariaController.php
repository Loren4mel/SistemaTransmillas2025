<?php
session_name("projecst2344fsdfd");
session_start();

require_once "../model/ConciliacionBancariaModel.php";

$modelo = new ConciliacionBancariaModel();

function responderJson(array $respuesta): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizarLista($valor): array
{
    if (is_array($valor)) {
        return array_values(array_filter($valor, static fn($item) => $item !== ''));
    }

    if (!is_string($valor) || trim($valor) === '') {
        return [];
    }

    $decodificado = json_decode($valor, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodificado)) {
        return array_values(array_filter($decodificado, static fn($item) => $item !== ''));
    }

    return [$valor];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $banco = $_POST['banco'] ?? 'davivienda';
    $usuario = $_SESSION['usuario_nombre'] ?? ($_POST['id_nombre'] ?? '');

    try {
        if ($accion === 'listar_movimientos') {
            responderJson([
                'success' => true,
                'data' => $modelo->listarMovimientos($banco, $_POST['estado'] ?? 'pendientes'),
            ]);
        }

        if ($accion === 'listar_guias') {
            responderJson([
                'success' => true,
                'data' => $modelo->listarGuiasPendientes($banco),
            ]);
        }

        if ($accion === 'listar_facturas') {
            responderJson([
                'success' => true,
                'data' => $modelo->listarFacturasPendientes($banco),
            ]);
        }

        if ($accion === 'asignar_facturas') {
            $movimientos = normalizarLista($_POST['movimientos'] ?? []);
            $facturas = normalizarLista($_POST['facturas'] ?? []);
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (!$movimientos || !$facturas || $descripcion === '') {
                responderJson(['success' => false, 'mensaje' => 'Seleccione movimientos, facturas y escriba un comentario.']);
            }

            $ok = $modelo->asignarFacturas($banco, $movimientos, $facturas, $usuario, $descripcion);
            responderJson([
                'success' => $ok,
                'mensaje' => $ok ? 'Facturas actualizadas correctamente.' : 'No se pudieron actualizar las facturas.',
            ]);
        }

        if ($accion === 'asignar_guias') {
            $movimientos = normalizarLista($_POST['movimientos'] ?? []);
            $guias = normalizarLista($_POST['guias'] ?? []);

            if (!$movimientos || !$guias) {
                responderJson(['success' => false, 'mensaje' => 'Seleccione movimientos y guias.']);
            }

            $ok = $modelo->asignarGuias($banco, $movimientos, $guias, $usuario);
            responderJson([
                'success' => $ok,
                'mensaje' => $ok ? 'Guias actualizadas correctamente.' : 'No se pudieron actualizar las guias.',
            ]);
        }

        if ($accion === 'remover') {
            $id = (int) ($_POST['id'] ?? 0);
            $ok = $id > 0 && $modelo->removerMovimiento($banco, $id);
            responderJson([
                'success' => $ok,
                'mensaje' => $ok ? 'Registro removido correctamente.' : 'No se pudo remover el registro.',
            ]);
        }

        if ($accion === 'eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
            $ok = $id > 0 && $modelo->eliminarMovimiento($banco, $id);
            responderJson([
                'success' => $ok,
                'mensaje' => $ok ? 'Registro eliminado correctamente.' : 'No se pudo eliminar el registro.',
            ]);
        }

        if ($accion === 'confirmar_pago_guia') {
            $guias = normalizarLista($_POST['guias'] ?? []);
            $numero = trim($_POST['numero_transaccion'] ?? '');
            $valor = trim($_POST['valor_confirmado'] ?? '');

            if (!$guias || $numero === '' || $valor === '') {
                responderJson(['success' => false, 'mensaje' => 'Complete guia, numero de transaccion y valor confirmado.']);
            }

            $ok = $modelo->confirmarPagoGuia($guias, $numero, $valor, $_FILES['comprobante'] ?? null);
            responderJson([
                'success' => $ok,
                'mensaje' => $ok ? 'Pago confirmado correctamente.' : 'No se pudo confirmar el pago.',
            ]);
        }

        responderJson(['success' => false, 'mensaje' => 'Accion no valida.']);
    } catch (Throwable $e) {
        responderJson(['success' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
    }
}

$bancos = $modelo->obtenerBancos();
include "../view/ConciliacionBancaria/index.php";
