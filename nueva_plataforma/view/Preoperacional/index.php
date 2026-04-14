<?php
/**
 * Vista del formulario de preoperacional
 * 
 * Variables esperadas:
 * - $datosVehiculo: array con datos del vehículo y usuario
 * - $registroExistente: array con datos del registro previo (si existe)
 * - $esCovid: bool true si es solo modo COVID
 * - $esValidacion: bool true si es validación
 * - $tipovehiculo: string 'MOTO' o 'CARRO'
 * - $nivel_acceso: int rol del usuario
 * - $param4, $param5, $iduser, $fecha, $preoperacional: parámetros de la URL
 */

require_once __DIR__ . '/../../helpers/PreoperacionalHelpers/PreoperacionalEncuestaLegadoViewHelper.php';

$color = "#EFEFEF";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Preoperacional - Transmillas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery (para precarga AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../assets/css/preoperacional.css">
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
                                $preguntasCovid = PreoperacionalEncuestaLegadoViewHelper::getPreguntasCovid();
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
                                        <?= PreoperacionalEncuestaLegadoViewHelper::renderMotoSections($color) ?>
                                    <?php elseif ($tipovehiculo == 'CARRO'): ?>
                                        <?= PreoperacionalEncuestaLegadoViewHelper::renderCarroSections($color) ?>
                                    <?php endif; ?>

                                    <!-- CHECK LIST FATIGA (común) -->
                                    <?= PreoperacionalEncuestaLegadoViewHelper::renderFatigaSection($color) ?>

                                    <!-- Kilometraje actual con imagen -->
                                    <tr bgcolor='$color' class='text' id='klmactual'>
                                        <td colspan='4'>KILOMETRAJE ACTUAL: <input name='param12' id='param12'
                                                value='<?= htmlspecialchars($registroExistente['pre_kilrecorridos'] ?? NULL) ?>'
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
                                <?php if (($nivel_acceso == 3 || $param5 == 'valida') && !$esCovid): ?>
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="2">IMPLEMENTOS DE TRABAJO</td>
                                        <td>SI</td>
                                        <td>NO</td>
                                    </tr>
                                    <?= PreoperacionalEncuestaLegadoViewHelper::renderImplementosTrabajo($color, $registroExistente['pre_limpiomaleta'] ?? null) ?>
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

    <!-- JavaScript del formulario preoperacional -->
    <script src="../assets/js/preoperacional.js?v=<?= filemtime(__DIR__ . '/../../assets/js/preoperacional.js') ?>"></script>
</body>

</html>