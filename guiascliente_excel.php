<?php
header('Content-type: application/vnd.ms-excel; charset=utf-8');
header("Content-Disposition: attachment; filename=reporte_creditos.xls; charset=utf-8");
header("Pragma: no-cache");
header("Expires: 0");

require("login_autentica.php");

$DB = new DB_mssql;
$DB->conectar();
$DB1 = new DB_mssql;
$DB1->conectar();

$id_usuario = $_SESSION['usuario_id'];

$conde1 = "";
$totalcontado = 0;
$totalpiezas = 0;

if ($param2 != "" && $param1 != "") {
    $conde1 = " AND $param1 LIKE '%$param2%'";
}

if ($param1 == "") {
    $param1 = "ser_prioridad";
}

if ($param4 != '') {
    $fechaactual = $param4;
} else {
    $param4 = $fechaactual;
}

if ($param5 != '') {
    $fechaactualfinal = $param5;
} else {
    $param5 = $fechaactualfinal;
}

$fechaactual = date($fechaactual . ' 01:00:00');
$fechaactualfinal = date($fechaactualfinal . ' 23:59:59');

$idtelefono = "SELECT usu_telefono, usu_idcredito FROM usuarios WHERE idusuarios='$id_usuario'";
$DB->Execute($idtelefono);
$telefono = mysqli_fetch_row($DB->Consulta_ID);

$sqlhvcli = "SELECT hoj_nom_id FROM hojadevidacliente WHERE hoj_clientecredito='" . $telefono[1] . "'";
$DB1->Execute($sqlhvcli);
$datohvc = mysqli_fetch_row($DB1->Consulta_ID);

$idCredito = $telefono[1];
$Identificador = ($datohvc[0] == "") ? "Id" : $datohvc[0];
$esCroydon = ($idCredito == 5);
$sql = "SELECT
    a.idservicios,
    a.ser_fechaentrega,
    a.cli_nombre,
    a.cli_telefono,
    a.cli_direccion,
    a.ser_destinatario,
    a.ser_telefonocontacto,
    a.ser_direccioncontacto,
    a.ciu_nombre,
    a.ser_prioridad,
    a.ser_fecharegistro,
    a.ser_consecutivo,
    a.ser_guiare,
    a.cli_idciudad,
    a.ser_estado,
    '1' AS tipoc,
    a.ser_peso,
    a.ser_volumen,
    a.ser_valor,
    a.ser_valorseguro,
    a.ser_piezas,
    b.ser_codeCliente,
    b.ser_correo_auto,
    c.gui_fechaentrega
FROM serviciosdia AS a
INNER JOIN servicios AS b ON a.idservicios = b.idservicios
LEFT JOIN guias AS c ON c.gui_idservicio = a.idservicios
WHERE
    a.cli_telefono = '$telefono[0]'
    AND a.ser_fecharegistro BETWEEN '$fechaactual' AND '$fechaactualfinal'
    AND a.ser_estado != 100
    $conde1
ORDER BY $param1 $asc";

$sql = str_replace(
    "ORDER BY $param1 $asc",
    "ORDER BY CASE WHEN a.ser_estado = 10 THEN 1 ELSE 0 END ASC, $param1 $asc",
    $sql
);

$DB->Execute($sql);

echo "<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #ffffff;
            color: #2c2c2c;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .column-header th {
            background-color: #215C98;
            color: #f8fafc;
            font-weight: 700;
            text-align: center;
            padding: 8px;
            border: 1px solid #cbd5e1;
        }

        td {
            padding: 7px;
            border: 1px solid #e5e7eb;
            text-align: center;
            color: #374151;
        }

        .row-even td {
            background-color: #ffffff;
        }

        .row-odd td {
            background-color: #f8fafc;
        }

        .money {
            background-color: #ddfedb;
            color: #153D64;
            font-weight: 700;
        }

        .text-code {
            mso-number-format: '\\@';
            white-space: nowrap;
        }

        .status-ok {
            color: #166534;
            font-weight: 700;
        }

        .status-pending {
            color: #9a3412;
            font-weight: 700;
        }

        .total-row td {
            background-color: #215C98;
            color: #ffffff;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <table>";

if ($esCroydon) {
    echo "<tr class='column-header'>
        <th>Fecha</th>
        <th>Tipo de orden</th>
        <th>{$Identificador}</th>
        <th>Cliente</th>
        <th>Dir. Envio Destino</th>
        <th>Ciudad Destino</th>
        <th>Departamento</th>
        <th>Telefono</th>
        <th>Remesa</th>
        <th>Estado</th>
        <th>Fecha Entrega</th>
        <th>Piezas</th>
    </tr>";
} else {
    echo "<tr class='column-header'>
        <th>#Idservicio</th>
        <th>#Guia</th>
        <th>{$Identificador}</th>
        <th>Destinatario</th>
        <th>Ciudad D</th>
        <th>Fecha Ingreso</th>
        <th>Telefono</th>
        <th>Direccion</th>
        <th>Piezas</th>
        <th>Volumen</th>
        <th>Peso</th>
        <th>Valor</th>
    </tr>";
}

$va = 0;
while ($rw1 = mysqli_fetch_row($DB->Consulta_ID)) {
    $va++;
    $rowClass = ($va % 2 === 0) ? "row-even" : "row-odd";
    $direct2 = str_replace("&", " ", $rw1[7]);
    $pordeclarado = (intval($rw1[19]) * 1) / 100;
    $valorTotal = $rw1[18] + $pordeclarado;

    $totalcontado += $valorTotal;
    $totalpiezas += (int) $rw1[20];

    if ($esCroydon) {
        $tipoOrden = "";
        $identificadorValor = trim($rw1[21]);
        $valorOrden = trim($rw1[21]);
        $partesOrden = preg_split('/\s+/', $valorOrden, 2);

        if (count($partesOrden) > 1) {
            $tipoOrden = $partesOrden[0];
            $identificadorValor = $partesOrden[1];
        } elseif (preg_match('/^[A-Za-z]/', $valorOrden)) {
            $tipoOrden = substr($valorOrden, 0, 2);
            $identificadorValor = substr($valorOrden, 2);
        }

        $identificadorExcel = '-' . ltrim($identificadorValor, '-');

        $estadoTexto = ($rw1[14] == 10) ? "ENTREGADO" : "EN RUTA";
        $estadoClase = ($rw1[14] == 10) ? "status-ok" : "status-pending";
        $fechaEntrega = !empty($rw1[23]) ? $rw1[23] : $rw1[1];

        echo "<tr class='{$rowClass}'>
            <td>{$rw1[10]}</td>
            <td>{$tipoOrden}</td>
            <td class='text-code'>{$identificadorExcel}</td>
            <td>{$rw1[5]}</td>
            <td>{$direct2}</td>
            <td>{$rw1[8]}</td>
            <td></td>
            <td>{$rw1[6]}</td>
            <td>{$rw1[12]}</td>
            <td class='{$estadoClase}'>{$estadoTexto}</td>
            <td>{$fechaEntrega}</td>
            <td>{$rw1[20]}</td>
        </tr>";
    } else {
        echo "<tr class='{$rowClass}'>
            <td>{$rw1[0]}</td>
            <td>{$rw1[12]}</td>
            <td>{$rw1[21]}</td>
            <td>{$rw1[5]}</td>
            <td>{$rw1[8]}</td>
            <td>{$rw1[10]}</td>
            <td>{$rw1[6]}</td>
            <td>{$direct2}</td>
            <td>{$rw1[20]}</td>
            <td>{$rw1[17]}</td>
            <td>{$rw1[16]}</td>
            <td class='money'>{$valorTotal}</td>
        </tr>";
    }
}

if ($esCroydon) {
    echo "<tr class='total-row'>
        <td colspan='10'>Total Piezas</td>
        <td>{$totalpiezas}</td>
        <td></td>
    </tr>";
} else {
    echo "<tr class='total-row'>
        <td colspan='7'></td>
        <td>Total Piezas</td>
        <td>{$totalpiezas}</td>
        <td>Total Factura</td>
        <td colspan='2'>{$totalcontado}</td>
    </tr>";
}

echo "    </table>
</body>
</html>";
?>
