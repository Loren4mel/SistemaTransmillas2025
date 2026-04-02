<?php
/**
 * Vista del formulario de preoperacional
 * Variables esperadas:
 * - $datosVehiculo: array con datos del vehículo y usuario
 * - $registroExistente: array con datos del registro previo (si existe)
 * - $esCovid: bool true si es solo modo COVID
 * - $esValidacion: bool true si es validación
 * - $tipovehiculo: string 'MOTO' o 'CARRO'
 * - $nivel_acceso: int rol del usuario
 * - $param4, $param5, $iduser, $fecha, $preoperacional: parámetros de la URL
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Preoperacional - Transmillas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 (asumimos que ya está en el layout, pero lo incluimos por si acaso) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery (para precarga AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Estilos para los radio buttons y tablas */
        .table td,
        .table th {
            vertical-align: middle;
        }

        .noti_bubble {
            float: right;
            padding: 2px 6px;
            background-color: red;
            color: white;
            font-weight: bold;
            border-radius: 60px;
            box-shadow: 1px 1px 3px gray;
            cursor: pointer;
        }

        .btnSaveCovid:disabled {
            opacity: 0.5;
        }

        .text {
            font-size: 14px;
        }

        .tittle3 {
            background-color: #074F91;
            color: white;
            font-weight: bold;
        }

        .table-hover tbody tr:hover {
            background-color: #C8C6F9 !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="card shadow p-3 mb-4 bg-body rounded">
            <div class="card-header mi-header d-flex align-items-center justify-content-between">
                <button class="btn btn-light" onclick="history.back()">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </button>
                <h3 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    <?= $esCovid ? 'Test COVID-19' : 'Preoperacional de Vehículo' ?>
                </h3>
            </div>

            <div class="card-body">
                <form id="formPreoperacional" enctype="multipart/form-data">
                    <!-- Campos ocultos necesarios -->
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="estado" id="estado" value="<?= htmlspecialchars($param4) ?>">
                    <input type="hidden" name="fecha" id="fecha" value="<?= htmlspecialchars($fecha) ?>">
                    <input type="hidden" name="user" id="user" value="<?= htmlspecialchars($iduser) ?>">
                    <input type="hidden" name="campo" id="campo" value="<?= $registroExistente ? 'preencuesta' : '' ?>">
                    <input type="hidden" name="data" id="data" value="">
                    <input type="hidden" name="tabla" value="<?= htmlspecialchars($preoperacional) ?>">
                    <input type="hidden" name="param11" value="<?= $registroExistente['idpreoperacinal'] ?? '' ?>">
                    <input type="hidden" name="idvehiculo" value="<?= $datosVehiculo['idvehiculos'] ?? 0 ?>">
                    <input type="hidden" name="param1" value="<?= $datosVehiculo['idvehiculos'] ?? 0 ?>">
                    <input type="hidden" name="param2" value="<?= htmlspecialchars($tipovehiculo) ?>">
                    <input type="hidden" name="param3" value="<?= htmlspecialchars($tipovehiculo) ?>">

                    <div id="contenedor1" style="display:flex;">
                        <div id="primero" style="width: 100%; float:left;">
                            <table class="table table-hover">

                                <!-- ==================== BLOQUE COVID-19 (siempre visible) ==================== -->
                                <tr bgcolor="#074F91" class="tittle3">
                                    <td colspan="2" width="4" align="center">TEST DE REPORTE DIARIO DE SINTOMATOLOGIA
                                    </td>
                                    <td colspan="1" width="4" align="center">SI</td>
                                    <td colspan="1" width="4" align="center">NO</td>
                                </tr>
                                <?php
                                $preguntasCovid = [
                                    ['covid191', 'Ha sentido fatiga los últimos dos días?'],
                                    ['covid192', 'Ha tenido fiebre mayor a 37,3?'],
                                    ['covid193', 'Ha presentado tos seca?'],
                                    ['covid194', 'Ha presentado dificultad para respirar?'],
                                    ['covid195', 'Tiene dolor o molestia?'],
                                    ['covid196', 'Tiene abundante secreción nasal?'],
                                    ['covid197', 'Ha presentado dolor de garganta?'],
                                    ['covid198', 'Realizo cambio de ropa de trabajo y esta se encuentra limpia?'],
                                    ['covid199', 'realizo cambio de tapabocas convencional lavable suministrado por la empresa y este se encuentra limpio?']
                                ];
                                $color = "#EFEFEF";
                                foreach ($preguntasCovid as $preg) {
                                    $name = $preg[0];
                                    $texto = $preg[1];
                                    $claseExtra = in_array($name, ['covid191', 'covid192', 'covid193', 'covid194', 'covid195', 'covid196', 'covid197']) ? 'optionCovid' . substr($name, -1) : '';
                                    echo "<tr bgcolor='$color' class='text' id='{$name}0'>";
                                    echo "<td colspan='2'>$texto</td>";
                                    echo "<td><input type='radio' name='$name' class='obtener $claseExtra' value='1' required></td>";
                                    echo "<td><input type='radio' name='$name' class='obtener' value='2'></td>";
                                    echo "</tr>";
                                }
                                ?>
                                <tr bgcolor='$color' class='text' id='temperatura'>
                                    <td colspan='4'>Temperatura: <input name='param19' id='param19'
                                            value='<?= htmlspecialchars($registroExistente['pre_temperatura'] ?? '') ?>'
                                            style='width:395px' class='form-control'></td>
                                </tr>
                                <?php if (!$esCovid): ?>
                                    <tr bgcolor='$color'>
                                        <td colspan='4'>Imagen Temperatura: <input type="file" name="param20"
                                                class="form-control"></td>
                                    </tr>
                                <?php endif; ?>

                                <!-- ==================== BLOQUE VEHÍCULO (solo si no es COVID) ==================== -->
                                <?php if (!$esCovid && !empty($datosVehiculo)): ?>
                                    <!-- Datos del vehículo y conductor -->
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="1" align="center">PLACA</td>
                                        <td colspan="1" align="center">MARCA</td>
                                        <td colspan="1" align="center">MODELO</td>
                                        <td colspan="1" align="center">KM</td>
                                    </tr>
                                    <tr bgcolor='$color' class='text'>
                                        <td><?= htmlspecialchars($datosVehiculo['veh_placa']) ?></td>
                                        <td><?= htmlspecialchars($datosVehiculo['veh_marca']) ?></td>
                                        <td><?= htmlspecialchars($datosVehiculo['veh_modelo']) ?></td>
                                        <td><?= htmlspecialchars($datosVehiculo['veh_kilactual']) ?></td>
                                    </tr>
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="1" align="center">CONDUCTOR</td>
                                        <td colspan="1" align="center">CEDULA</td>
                                        <td colspan="1" align="center">LICENCIA</td>
                                        <td colspan="1" align="center">FECHA VENC</td>
                                    </tr>
                                    <tr bgcolor='$color' class='text'>
                                        <td><?= htmlspecialchars($datosVehiculo['usu_nombre']) ?></td>
                                        <td><?= htmlspecialchars($datosVehiculo['usu_identificacion']) ?></td>
                                        <td><?= htmlspecialchars($datosVehiculo['usu_licencia']) ?></td>
                                        <td><?= htmlspecialchars($datosVehiculo['usu_fechalicencia']) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!$esCovid && !empty($tipovehiculo)): ?>

                                    <!-- ==================== SECCIONES SEGÚN TIPO DE VEHÍCULO ==================== -->
                                    <?php if ($tipovehiculo == 'MOTO'): ?>
                                        <!-- LLANTAS Y RINES -->
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="1" align="center">LLANTAS Y RINES</td>
                                            <td colspan="1">SI</td>
                                            <td colspan="1">NO</td>
                                            <td colspan="1">N.A</td>
                                        </tr>
                                        <?php
                                        $preguntasMoto = [
                                            ['llantas1', 'Tienen rajaduras por un objeto condundecte?'],
                                            ['llantas2', 'Tienen grietas finas en los laterales?'],
                                            ['llantas3', 'Las llantas estan desinfladas?'],
                                            ['llantas4', 'Las llantas estan sobreinfladas?'],
                                            ['llantas5', 'Los rines y guardabarros estan en buen estado?'],
                                            ['llantas6', 'Funcionan correctamente la suspensión y frenos de llantas?']
                                        ];
                                        foreach ($preguntasMoto as $p) {
                                            echo "<tr bgcolor='$color' class='text' id='{$p[0]}0'>";
                                            echo "<td colspan='1'>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td>";
                                            echo "</tr>";
                                        }
                                        // TRANSMISIÓN
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">TRANSMISIÓN</td><td>SI</td><td>NO</td><td>N.A</td></tr>';
                                        $transmision = [['transmision1', 'La cadena brilla? (Necesita engrase)'], ['transmision2', 'La cadena esta mal tensionada? (se oye al rodar)']];
                                        foreach ($transmision as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // LUCES Y ESPEJOS
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">LUCES Y ESPEJOS</td><td>SI</td><td>NO</td><td>N.A</td></tr>';
                                        $luces = [['Luces1', 'Funcionan correctamente las Luces de cruce y de frenado?'], ['Luces2', 'Los espejos estan en perfecto estado?'], ['Luces3', 'El manubrio está en óptimas condiciones?']];
                                        foreach ($luces as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // FUGAS
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">FUGAS</td><td>SI</td><td>NO</td><td>N.A</td></tr>';
                                        $fugas = [
                                            ['fugas1', 'Fugas en el liquido de suspensión y de frenos?'],
                                            ['fugas2', 'Fugas en el sistema de trasmisión (cardán, diferencial)?'],
                                            ['fugas3', 'fugas en el aceite del motor y liquido de refrigeración?'],
                                            ['fugas4', 'Fugas en el fluido que pasa por la caja de cambios?'],
                                            ['fugas5', 'Fugas en el tanque de combustible?'],
                                            ['fugas6', 'Fugas de gases en el mofle (deterioro)?']
                                        ];
                                        foreach ($fugas as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // MANDOS
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">MANDOS (CAMBIOS, FRENOS)</td><td>SI</td><td>NO</td><td>N.A</td></tr>';
                                        $mandos = [['mandos1', 'El embrague esta endurecido?'], ['mandos2', 'El cable de acelerador vuelve del todo a su punto inical?']];
                                        foreach ($mandos as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // ENTORNO GENERAL
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">ENTORNO GENERAL</td><td>SI</td><td>NO</td><td>N.A</td></tr>';
                                        $entorno = [
                                            ['entorno1', 'La moto esta en buenas condiciones de limpieza?'],
                                            ['entorno2', 'Esta deteriorado el chasis de la moto? (oxidación, abolladuras, partes faltantes)?'],
                                            ['entorno3', 'Caja de Herramientas']
                                        ];
                                        foreach ($entorno as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // ELEMENTOS DE PROTECCIÓN
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">ELEMENTOS DE PROTECCIÓN</td><td>SI</td><td>NO</td><td>N.A</td></tr>';
                                        $elementos = [
                                            ['elementos1', 'Se dispone de casco para moto en buen estado?'],
                                            ['elementos2', 'Se dispone de guantes para moto?'],
                                            ['elementos3', 'Se dispone de gafas protectoras?'],
                                            ['elementos4', 'Se dispone de chaleco reflectivo para las horas de la noche?'],
                                            ['elementos5', 'Se dispone de impermeable para temporadas de lluvias?'],
                                            ['elementos6', 'SE REALIZÒ LA LIMPIEZA Y DESINFECCION DE LA MOTO?']
                                        ];
                                        foreach ($elementos as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        echo '<tr bgcolor="#868A08" class="tittle3"><td colspan="4">SI alguno de estos puntos tiene al menos como respuesta un SI, el trabajador debe de manera inmediata dar aviso de sus condciones al Jefe de operaciones de la empresa TRANSMILLAS EMPRESA DE CARGA Y LOGISTICA.</td></tr>';
                                        ?>
                                    <?php elseif ($tipovehiculo == 'CARRO'): ?>
                                        <!-- DIRECCIONALES -->
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="1">DIRECCIONALES</td>
                                            <td>B</td>
                                            <td>M</td>
                                            <td>N.A</td>
                                        </tr>
                                        <?php
                                        $direccionales = [
                                            ['direccionales1', 'Frontales Plenas altas y/o bajas'],
                                            ['direccionales2', 'Direccionales delanteras de parqueo'],
                                            ['direccionales3', 'Direccionales traseras de parqueo'],
                                            ['direccionales4', 'De Stop y señal trasera']
                                        ];
                                        foreach ($direccionales as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // CABINA
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">CABINA</td><td>B</td><td>M</td><td>N.A</td></tr>';
                                        $cabina = [
                                            ['cabina1', 'Espejo central o retrovisor'],
                                            ['cabina2', 'Espejos laterales'],
                                            ['cabina3', 'Alarma de retroceso'],
                                            ['cabina4', 'Cojineria'],
                                            ['cabina5', 'Vidrio frontal'],
                                            ['cabina6', 'Nivel de agua del parabrisas'],
                                            ['cabina7', 'Vidrios Laterales o cortabrisas'],
                                            ['cabina8', 'Vidrio trasero']
                                        ];
                                        foreach ($cabina as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // DISPOSITIVOS DE SEGURIDAD
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">DISPOSITIVOS DE SEGURIDAD</td><td>B</td><td>M</td><td>N.A</td></tr>';
                                        $dispositivos = [
                                            ['dispositivos1', 'Pito'],
                                            ['dispositivos2', 'Pito de reversa'],
                                            ['dispositivos3', 'Freno de servicio'],
                                            ['dispositivos4', 'Freno de emergencia'],
                                            ['dispositivos5', 'Dirección/suspensión delantera'],
                                            ['dispositivos6', 'Cinturón de seguridad'],
                                            ['dispositivos7', 'Estado general de puertas'],
                                            ['dispositivos8', 'Limpia brisas y plumillas'],
                                            ['dispositivos9', 'Extintor (indique fecha de vencimiento en observaciones)'],
                                            ['dispositivos10', 'Botiquin'],
                                            ['dispositivos11', 'Asientos en buena condición']
                                        ];
                                        foreach ($dispositivos as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // INDICADORES
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">INDICADORES</td><td>B</td><td>M</td><td>N.A</td></tr>';
                                        $indicadores = [
                                            ['indicadores1', 'Panel de Indicadores'],
                                            ['indicadores2', 'Aceite'],
                                            ['indicadores3', 'Agua']
                                        ];
                                        foreach ($indicadores as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // LLANTAS
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">LLANTAS</td><td>B</td><td>M</td><td>N.A</td></tr>';
                                        $llantas = [
                                            ['llantas1', 'Estado General de llantas'],
                                            ['llantas2', 'Llanta de repuesto']
                                        ];
                                        foreach ($llantas as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        // HERRAMIENTAS MÍNIMAS
                                        echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="1">HERRAMIENTAS MINIMAS</td><td>B</td><td>M</td><td>N.A</td></tr>';
                                        $herramientas = [
                                            ['Herramientas1', 'Gato'],
                                            ['Herramientas2', 'Cruceta'],
                                            ['Herramientas3', 'Cinta de seguridad'],
                                            ['Herramientas4', 'Conos'],
                                            ['Herramientas5', 'Linterna'],
                                            ['Herramientas6', 'Caja de Herramientas'],
                                            ['Herramientas7', 'SE REALIZÒ LA LIMPIEZA Y DESINFECCION DEL VEHÌCULO?']
                                        ];
                                        foreach ($herramientas as $p) {
                                            echo "<tr bgcolor='$color' class='text'><td>{$p[1]}</td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='1' required></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='2'></td>";
                                            echo "<td><input type='radio' name='{$p[0]}' class='obtener' value='3'></td></tr>";
                                        }
                                        echo '<tr bgcolor="#868A08" class="tittle3"><td colspan="4">SI alguno de estos puntos tiene al menos como respuesta un SI, el trabajador debe de manera inmediata dar aviso de sus condciones al Jefe de operaciones de la empresa TRANSMILLAS EMPRESA DE CARGA Y LOGISTICA.</td></tr>';
                                        ?>
                                    <?php endif; ?>

                                    <!-- CHECK LIST FATIGA (común) -->
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="2" align="center">CHECK LIST FATIGA</td>
                                        <td colspan="1">SI</td>
                                        <td colspan="1">NO</td>
                                    </tr>
                                    <?php
                                    $preguntasFatiga = [
                                        ['elementosp1', 'TENGO SUEÑO?'],
                                        ['elementosp2', 'SIENTO LA VISTA CANSADA?'],
                                        ['elementosp3', 'ME ENCUENTRO TOMANDO MEDICAMENTOS QUE ME IMPIDAN OPERAR O ALTERE MI CONCENTRACIÓN?'],
                                        ['elementosp4', 'ME CUESTA ENFOCAR LA VISTA (VISIÓN BORROSA) O MANTENER LOS OJOS ABIERTOS?'],
                                        ['elementosp5', 'SIENTO DIFICULTADES PARA CONCENTRARME O PERMANECER ALERTA?'],
                                        ['elementosp6', 'ME SIENTO EN MALAS CONDICIONES (FISICAS Y/O ANIMICAS) PARA REALIZAR MIS TAREAS?'],
                                        ['elementosp7', 'SE ENCUENTRA BAJO ALGÚN EFECTO DE ALCHOHOL O DROGAS?']
                                    ];
                                    foreach ($preguntasFatiga as $fat) {
                                        echo "<tr bgcolor='$color' class='text' id='{$fat[0]}0'>";
                                        echo "<td colspan='2'>{$fat[1]}</td>";
                                        echo "<td><input type='radio' name='{$fat[0]}' class='obtener' value='1' required></td>";
                                        echo "<td><input type='radio' name='{$fat[0]}' class='obtener' value='2'></td>";
                                        echo "</tr>";
                                    }
                                    ?>

                                    <!-- Kilometraje actual con imagen -->
                                    <tr bgcolor='$color' class='text' id='klmactual'>
                                        <td colspan='4'>KILOMETRAJE ACTUAL: <input name='param12' id='param12'
                                                value='<?= htmlspecialchars($registroExistente['pre_kilrecorridos'] ?? '') ?>'
                                                style='width:395px' class='form-control'></td>
                                    </tr>
                                    <tr bgcolor='$color'>
                                        <td colspan='4'>Imagen Kilometraje: <input type="file" name="param30"
                                                class="form-control">
                                            <?php if (!empty($registroExistente['pre_img_kilo'])): ?>
                                                <br><a href="<?= htmlspecialchars($registroExistente['pre_img_kilo']) ?>"
                                                    target="_blank">Ver imagen actual</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Observaciones, acciones, responsable -->
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="4">OBSERVACIONES/ CONDICIONES REPORTADAS</td>
                                    </tr>
                                    <tr bgcolor='$color' class='text' id='observacionesc'>
                                        <td colspan='4'><textarea name='param7' id='param7' style='width:395px'
                                                class='form-control'><?= htmlspecialchars($registroExistente['pre_obsevaciones'] ?? '') ?></textarea>
                                        </td>
                                    </tr>
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="4">ACCIÓN CORRECTIVA</td>
                                    </tr>
                                    <tr bgcolor='$color' class='text' id='accioncorrectiva'>
                                        <td colspan='4'><textarea name='param8' id='param8' style='width:395px'
                                                class='form-control'><?= htmlspecialchars($registroExistente['pre_correctiva'] ?? '') ?></textarea>
                                        </td>
                                    </tr>
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="4">RESPONSABLE</td>
                                    </tr>
                                    <tr bgcolor='$color' class='text' id='responsable'>
                                        <td colspan='4'><input name='param9' id='param9'
                                                value='<?= htmlspecialchars($registroExistente['pre_responsable'] ?? '') ?>'
                                                style='width:395px' class='form-control'></td>
                                    </tr>
                                <?php endif; ?>

                                <!-- ==================== IMPLEMENTOS DE TRABAJO (solo rol 3 o validación) ==================== -->
                                <?php if ($nivel_acceso == 3 || $param5 == 'valida'): ?>
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="2">IMPLEMENTOS DE TRABAJO</td>
                                        <td>SI</td>
                                        <td>NO</td>
                                    </tr>
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="2">CELULAR</td>
                                        <td>SI</td>
                                        <td>NO</td>
                                    </tr>
                                    <?php
                                    $implementos = [
                                        ['implementos1', 'Cuenta con celular con acceso a Internet?'],
                                        ['implementos2', 'La bateria de su Celular se encuentra Cargada?'],
                                        ['implementos3', 'Su celular cuenta con datos y minutos?'],
                                        ['implementos4', 'Tiene usted el cargador de su Celular?']
                                    ];
                                    foreach ($implementos as $imp) {
                                        echo "<tr bgcolor='$color' class='text'><td colspan='2'>{$imp[1]}</td>";
                                        echo "<td><input type='radio' name='{$imp[0]}' class='obtener' value='1' required></td>";
                                        echo "<td><input type='radio' name='{$imp[0]}' class='obtener' value='2'></td></tr>";
                                    }
                                    echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="2">PESA</td><td>SI</td><td>NO</td></tr>';
                                    $pesa = [
                                        ['implementos10', 'Cuenta con Pesa?'],
                                        ['implementos11', 'Su Pesa cuenta con Bateria?'],
                                        ['implementos12', 'Verifico que su Pesa cuente con bateria?'],
                                        ['implementos13', 'Verifico que su Pesa este funcionando Perfectamente?']
                                    ];
                                    foreach ($pesa as $imp) {
                                        echo "<tr bgcolor='$color' class='text'><td colspan='2'>{$imp[1]}</td>";
                                        echo "<td><input type='radio' name='{$imp[0]}' class='obtener' value='1' required></td>";
                                        echo "<td><input type='radio' name='{$imp[0]}' class='obtener' value='2'></td></tr>";
                                    }
                                    echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="2">MALETA</td><td>SI</td><td>NO</td></tr>';
                                    echo "<tr bgcolor='$color' class='text'><td colspan='2'>Cuenta con Maleta?</td>";
                                    echo "<td><input type='radio' name='implementos14' class='obtener' value='1' required></td>";
                                    echo "<td><input type='radio' name='implementos14' class='obtener' value='2'></td></tr>";
                                    echo "<tr bgcolor='$color' class='text'><td colspan='4'>Ultima vez que desinfecto la maleta:<input name='param21' id='param21' value='" . htmlspecialchars($registroExistente['pre_limpiomaleta'] ?? '') . "' style='width:395px' class='form-control'></td></tr>";
                                    echo '<tr bgcolor="#074F91" class="tittle3"><td colspan="2">PARAFISCALES O COPIA DE AFILIACION DE ARL</td><td>SI</td><td>NO</td></tr>';
                                    $parafiscales = [
                                        ['implementos18', 'Tiene copia de pago de parafiscales?'],
                                        ['implementos19', 'Tiene copia de Afiliacion ARL(Peronal Nuevo)?']
                                    ];
                                    foreach ($parafiscales as $imp) {
                                        echo "<tr bgcolor='$color' class='text'><td colspan='2'>{$imp[1]}</td>";
                                        echo "<td><input type='radio' name='{$imp[0]}' class='obtener' value='1' required></td>";
                                        echo "<td><input type='radio' name='{$imp[0]}' class='obtener' value='2'></td></tr>";
                                    }
                                    ?>
                                <?php endif; ?>

                                <!-- ==================== COMPROMISO Y DECLARACIÓN ==================== -->
                                <tr bgcolor="#878787" class="tittle3">
                                    <td colspan="4">
                                        <p align="center">YO COMO TRABAJADOR DE LA EMPRESA TRANSMILLAS ME COMPROMETO A:
                                            **Reportar mis síntomas en caso de presentar y asi mismo a las Entidades a
                                            las que haya lugar. </p>
                                        <p align="left">
                                            Al salir de la vivienda<br>
                                            1. Estar atento a las indicaciones de la autoridad local sobre restricciones
                                            a la movilidad y acceso a lugares públicos. <br>
                                            2. Visitar solamente aquellos lugares estrictamente necesarios y evitar
                                            conglomeraciones de personas. <br>
                                            3. Asignar un adulto para hacer las compras, que no pertenezca a ningún
                                            grupo de alto riesgo. <br>
                                            4. Restringir las visitas a familiares y amigos y si alguno presenta cuadro
                                            respiratorio. <br>
                                            5. Evitar saludar con besos, abrazos o de mano. <br>
                                            6. Utilizar tapabocas en áreas de afluencia masiva de personas, en el
                                            transporte público, supermercados, bancos, entre otros, así como en los
                                            casos de sintomatología respiratoria o si es persona en grupo de riesgo.
                                            <br>Al regresar a la vivienda<br>
                                            1. Retirar los zapatos a la entrada y lavar la suela con agua y jabón. <br>
                                            2. Lavar las manos de acuerdo a los protocolos del Ministerio de Salud y
                                            Protección Social. <br>
                                            3. Evitar saludar con beso, abrazo y dar la mano y buscar mantener siempre
                                            la distancia de más de dos metros entre personas. <br>
                                            4. Antes de tener contacto con los miembros de familia, cambiarse de ropa.
                                            <br>
                                            5. Mantener separada la ropa de trabajo de las prendas personales. <br>
                                            6. La ropa debe lavarse en la lavadora a más de 60 grados centígrados o a
                                            mano con agua caliente que no queme las manos y jabón, y secar por completo.
                                            No reutilizar ropa sin antes lavarla. <br>
                                            7. Bañarse con abundante agua y jabón. <br>
                                            8. Desinfectar con alcohol o lavar con agua y jabón los elementos que han
                                            sido manipulados al exterior de la vivienda. <br>
                                            9. Mantener la casa ventilada, limpiar y desinfectar áreas, superficies y
                                            objetos de manera regular. <br>
                                            10. Si hay alguna persona con síntomas de gripa en la casa, tanto la persona
                                            con síntomas de gripa como quienes cuidan de ella deben utilizar tapabocas
                                            de manera constante en el hogar. <br>
                                            <br>Al convivir con una persona de alto riesgo
                                            Si el trabajador convive con personas mayores de 60 años, con enfermedades
                                            preexistentes de alto riesgo para el COVID-19, o con personal de servicios
                                            de salud, debe: <br>
                                            1. Mantener la distancia siempre mayor a dos metros. <br>
                                            2. Utilizar tapabocas en casa, especialmente al encontrarse en un mismo
                                            espacio que la persona a riesgo y al cocinar y servir la comida. <br>
                                            3. Aumentar la ventilación del hogar. <br>
                                            4. Asignar un baño y habitación individual para la persona que tiene riesgo,
                                            si es posible. Si no lo es, aumentar ventilación, limpieza y desinfección de
                                            superficies. <br>
                                            5. Cumplir a cabalidad con las recomendaciones de lavado de manos e higiene
                                            respiratoria impartidas por el Ministerio de Salud y Protección Social
                                        </p>
                                    </td>
                                </tr>
                                <tr bgcolor="#ff0000" class="tittle3">
                                    <td colspan="4">Declaro que toda la información suministrada en el test anterior es
                                        verídica, de caso contrario puede acarrear sanciones disciplinarias y su
                                        correspondiente aviso a las autoridades competentes que haya lugar.</td>
                                </tr>

                                <!-- ==================== VALIDACIÓN (solo si es modo validación) ==================== -->
                                <?php if ($esValidacion): ?>
                                    <tr bgcolor="#868A08" class="tittle3">
                                        <td colspan="4" align="center">VALIDA
                                            <?= $esCovid ? 'COVID 19' : 'PREOPERACIONAL Y COVID 19' ?>
                                        </td>
                                    </tr>
                                    <tr bgcolor='$color' class='text' id='validapreopera'>
                                        <td colspan='4'><textarea name='param10' id='param10' style='width:395px'
                                                class='form-control'><?= htmlspecialchars($registroExistente['pre_descvalidada'] ?? '') ?></textarea>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function asignar() {
            var conver = '';
            var chkdatos = document.getElementsByClassName("obtener");
            for (i = 0; i < chkdatos.length; i++) {
                if (chkdatos[i].checked) {
                    conver = conver + '"' + chkdatos[i].name + '":' + '"' + chkdatos[i].value + '",';
                }
            }
            var data = conver.substring(0, conver.length - 1);
            data = '{' + data + '}';
            document.getElementById("data").value = data;
            return true;
        }

        document.getElementById('formPreoperacional').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!asignar()) return;

            const formData = new FormData(this);
            formData.append('accion', 'guardar');
            formData.append('ajax', '1');

            const btn = document.getElementById('btnGuardar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch(window.location.pathname, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = 'Guardar';
                    if (data.success) {
                        Swal.fire({
                            title: 'Éxito',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            // Redirigir a inicio.php como se hacía originalmente
                            window.location.href = 'inicio.php?bandera=1';
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Ocurrió un error al guardar.',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                        if (data.debug_errors) console.warn(data.debug_errors);
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = 'Guardar';
                    Swal.fire({
                        title: 'Error',
                        text: 'Error de comunicación con el servidor.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    console.error(error);
                });
        });

        // ==================== PRECARGA DE DATOS (AJAX) ====================
        var valida = document.getElementById("estado").value;
        var iduser = document.getElementById("user").value;
        var fecha = document.getElementById("fecha").value;
        var campo = document.getElementById("campo").value;
        var tipovehiculo = document.getElementById("param3").value;

        if (valida == 'ingresado' || valida == 'covid19') {
            if (campo) {
                // Definir valores esperados para moto (se puede extender para carro)
                var valoresmoto = new Array();
                valoresmoto['llantas1'] = '2';
                valoresmoto['llantas2'] = '2';
                valoresmoto['llantas3'] = '2';
                valoresmoto['llantas4'] = '2';
                valoresmoto['llantas5'] = '1';
                valoresmoto['llantas6'] = '1';
                valoresmoto['transmision1'] = '2';
                valoresmoto['transmision2'] = '2';
                valoresmoto['Luces1'] = '1';
                valoresmoto['Luces2'] = '1';
                valoresmoto['Luces3'] = '1';
                valoresmoto['fugas1'] = '2';
                valoresmoto['fugas2'] = '2';
                valoresmoto['fugas3'] = '2';
                valoresmoto['fugas4'] = '2';
                valoresmoto['fugas5'] = '2';
                valoresmoto['fugas6'] = '2';
                valoresmoto['mandos1'] = '2';
                valoresmoto['mandos2'] = '1';
                valoresmoto['entorno1'] = '1';
                valoresmoto['entorno2'] = '2';
                valoresmoto['entorno3'] = '1';
                valoresmoto['elementos1'] = '1';
                valoresmoto['elementos2'] = '1';
                valoresmoto['elementos3'] = '1';
                valoresmoto['elementos4'] = '1';
                valoresmoto['elementos5'] = '1';
                valoresmoto['elementos6'] = '1';

                $.ajax({
                    url: window.location.pathname,
                    type: "POST",
                    data: { user: iduser, fecha: fecha, campo: campo, accion: "buscarDatos" },
                    dataType: "json"
                }).done(function (respuesta) {
                    if (respuesta != null && respuesta !== '') {
                        // respuesta es el JSON con los datos guardados
                        for (var i in respuesta) {
                            var value = respuesta[i];
                            var tamano = document.getElementsByName(i);
                            for (b = 0; b < tamano.length; b++) {
                                var valor = tamano[b].value;
                                if (valor == value) {
                                    tamano[b].checked = true;
                                    // Colorear fila si la respuesta no es la esperada
                                    if (tipovehiculo == 'MOTO') {
                                        // Solo si es de moto y la respuesta es diferente al valor esperado
                                        if (value != valoresmoto[i]) {
                                            document.getElementById(i + '0').style.backgroundColor = "#e4605e";
                                        }
                                    } else if (tipovehiculo == 'CARRO') {
                                        // Lógica para carro: si es opción M (malo) pintar rojo
                                        if (value != '1') {
                                            document.getElementById(i + '0').style.backgroundColor = "#e4605e";
                                        }
                                    } else {
                                        // Modo COVID: pintar rojo si respuesta es SI (1) en primeras 7
                                        if (valida == 'covid19' && i.substr(0, 6) == 'covid1' && value == '1') {
                                            document.getElementById(i + '0').style.backgroundColor = "#e4605e";
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>

</html>