<?php
require("../../../login_autentica.php");

$datos = [];
if (isset($_POST['datos_pago'])) {
    $datos = json_decode((string) $_POST['datos_pago'], true);
    if (!is_array($datos)) {
        $datos = [];
    }
}

$semestre = $_POST['semestre'] ?? '';
$anio = preg_replace('/[^0-9]/', '', (string) ($_POST['anio'] ?? date('Y')));
$nombreExcel = 'Pago de Primas ' . ($semestre !== '' ? $semestre : 'Semestre') . ' del Ano ' . $anio . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreExcel . '"');
header('Pragma: no-cache');
header('Expires: 0');

function valorExcel($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 8px; text-align: center; }
        th { background: #074F91; color: #fff; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Tipo de identificacion</th>
                <th>Numero de identificacion</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Codigo del Banco</th>
                <th>Tipo de producto o servicio</th>
                <th>Numero del Producto o Servicio</th>
                <th>Valor del Pago o de la Recarga</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($datos as $fila): ?>
                <tr>
                    <td>1</td>
                    <td><?= valorExcel($fila['cedula'] ?? '') ?></td>
                    <td><?= valorExcel($fila['nombre'] ?? '') ?></td>
                    <td><?= valorExcel($fila['apellido'] ?? '') ?></td>
                    <td><?= valorExcel($fila['codigo_banco'] ?? '') ?></td>
                    <td><?= valorExcel($fila['tipo_cuenta'] ?? '') ?></td>
                    <td><?= valorExcel($fila['cuenta'] ?? '') ?></td>
                    <td><?= valorExcel($fila['valor_pago'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
