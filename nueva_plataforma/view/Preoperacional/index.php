<?php
/**
 * Vista del formulario de preoperacional
 *
 * NUEVO FORMATO: Sin COVID, sin fatiga
 * Solo secciones basadas en rol: Administrativo, Conductor, Vehículo Propio, Auxiliar de Carga
 * + Preoperacional de vehículo (carro/moto)
 *
 * FORMATO LEGADO: Mantiene COVID, fatiga, implementos de trabajo
 */

require_once __DIR__ . '/../../helpers/PreoperacionalHelpers/PreoperacionalEncuestaLegadoViewHelper.php';
require_once __DIR__ . '/../../helpers/PreoperacionalHelpers/PreoperacionalNuevaEncuestaViewHelper.php';

$color = "#EFEFEF";
$usarNuevoFormato = ($formatoEncuesta === 'nuevo');

/**
 * Convierte una ruta absoluta del servidor a una URL accesible desde el navegador
 */
function rutaAbsolutaAUrl($rutaAbsoluta)
{
    if (empty($rutaAbsoluta)) return '';
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $ruta = str_replace('\\', '/', $rutaAbsoluta);
    if (strpos($ruta, $docRoot) === 0) {
        return substr($ruta, strlen($docRoot));
    }
    return $rutaAbsoluta; // fallback: devolver tal cual
}
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
    <link rel="stylesheet" href="../assets/css/preoperacional.css?<?= filemtime(__DIR__ . '/../../assets/css/preoperacional.css') ?>">
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

        /* En modo validación, los controles deshabilitados no deben verse opacados */
        #formPreoperacional .obtener:disabled,
        #formPreoperacional input:disabled,
        #formPreoperacional select:disabled {
            opacity: 1 !important;
            cursor: default;
        }

        /* En modo validación, controles con clase validation-disabled (sin disabled real) */
        #formPreoperacional .validation-disabled {
            opacity: 1 !important;
            pointer-events: none;
            cursor: default;
        }

        .text {
            font-size: 14px;
        }

        /* .tittle3 ahora se maneja en preoperacional.css para colores pasteles */
        .tittle3 {
            color: white;
            font-weight: bold;
        }

        .table-hover tbody tr:hover {
            background-color: #C8C6F9 !important;
        }

        /* Estilos para la firma a trazo */
        .signature-container {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }

        .signature-canvas {
            border: 2px solid #074F91;
            border-radius: 4px;
            background-color: #ffffff;
            cursor: crosshair;
            touch-action: none;
            display: block;
            margin: 0 auto 10px auto;
            max-width: 100%;
        }

        .signature-controls {
            margin-top: 10px;
        }

        .signature-row {
            background-color: #f8f9fa !important;
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
                    <?php if (!$esCovid): ?>
                        <span class="format-indicator <?= $usarNuevoFormato ? 'nuevo' : 'legado' ?>">
                            <?= $usarNuevoFormato ? 'NUEVO FORMATO' : 'FORMATO LEGADO' ?>
                        </span>
                    <?php endif; ?>
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
                    <input type="hidden" name="id_preoperacional" value="<?= $registroExistente['idpreoperacinal'] ?? '' ?>">
                    <input type="hidden" name="idvehiculo" value="<?= $datosVehiculo['idvehiculos'] ?? 0 ?>">
                    <input type="hidden" name="tipo_vehiculo" value="<?= htmlspecialchars($tipovehiculo) ?>">
                    <input type="hidden" name="param3" value="<?= htmlspecialchars($tipovehiculo) ?>">
                    <input type="hidden" name="formato_encuesta" id="formato_encuesta" value="<?= $usarNuevoFormato ? 'nuevo' : 'legado' ?>">

                    <div id="contenedor1" style="display:flex;">
                        <div id="primero" style="width: 100%; float:left;">
                            <div class="table-responsive table-responsive-custom">
                                <table class="table table-hover">

                                <!-- ==================== NUEVO FORMATO: SOLO SECCIONES BASADAS EN ROL (SIN COVID, SIN FATIGA) ==================== -->
                                <?php if ($usarNuevoFormato): ?>

                                    <?php
                                    // Extraer valores existentes de la encuesta JSON para precargar el formulario
                                    $valoresEncuesta = [];
                                    if ($registroExistente && !empty($registroExistente['preencuesta'])) {
                                        $valoresEncuesta = json_decode($registroExistente['preencuesta'], true) ?? [];
                                    }
                                    ?>

                                    <!-- SECCIÓN ADMINISTRATIVO (solo roles administrativos) -->
                                    <?php if ($mostrarSecciones['administrativo'] ?? false): ?>
                                        <tr class="tittle3 section-header administrativo">
                                            <td colspan="4">👤 EVALUACIÓN PERSONAL ADMINISTRATIVO</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasAdministrativo(),
                                            $color,
                                            '',
                                            'administrativo',
                                            $valoresEncuesta
                                        ) ?>
                                    <?php endif; ?>

                                    <!-- SECCIÓN CONDUCTOR (solo conductores de carro) -->
                                    <?php if ($mostrarSecciones['conductor'] ?? false): ?>
                                        <tr class="tittle3 section-header conductor">
                                            <td colspan="4">🚗 EVALUACIÓN PERSONAL CONDUCTOR</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasConductor(),
                                            $color,
                                            '',
                                            'conductor',
                                            $valoresEncuesta
                                        ) ?>
                                    <?php endif; ?>

                                    <!-- SECCIÓN VEHÍCULO PROPIO (solo motos) -->
                                    <?php if ($mostrarSecciones['vehiculo_propio'] ?? false): ?>
                                        <tr class="tittle3 section-header vehiculo-propio">
                                            <td colspan="4">🏍️ EVALUACIÓN VEHÍCULO PROPIO (MOTO)</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasVehiculoPropio(),
                                            $color,
                                            '',
                                            'vehiculo-propio',
                                            $valoresEncuesta
                                        ) ?>
                                    <?php endif; ?>

                                    <!-- SECCIÓN AUXILIAR DE CARGA -->
                                    <?php if ($mostrarSecciones['auxiliar_carga'] ?? false): ?>
                                        <tr class="tittle3 section-header auxiliar-carga">
                                            <td colspan="4">📦 EVALUACIÓN AUXILIAR DE CARGA</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasAuxiliarCarga(),
                                            $color,
                                            '',
                                            'auxiliar-carga',
                                            $valoresEncuesta
                                        ) ?>
                                    <?php endif; ?>

                                    <!-- PREOPERACIONAL VEHÍCULO CARRO (solo nuevo formato) -->
                                    <?php if ($mostrarSecciones['preoperacional_vehiculo'] ?? false): ?>
                                        
                                        <!-- Datos del vehículo -->
                                        <?php if (!empty($datosVehiculo)): ?>
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

                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoCarroSections($color, 'preoperacional-carro', $valoresEncuesta) ?>
                                        
                                        <!-- Kilometraje actual con imagen -->
                                        <tr bgcolor='$color' class='text' id='klmactual'>
                                            <td colspan='4'>KILOMETRAJE ACTUAL: <input name='kilometraje' id='kilometraje'
                                                    value='<?= htmlspecialchars($registroExistente['pre_kilrecorridos'] ?? NULL) ?>'
                                                    style='width:395px' class='form-control'></td>
                                        </tr>
                                        <tr bgcolor='$color'>
                                            <td colspan='4'>Imagen Kilometraje: <input type="file" name="imagen_kilometraje"
                                                    class="form-control">
                                                <?php if (!empty($registroExistente['pre_img_kilo'])): ?>
                                                    <br><a href="<?= htmlspecialchars(rutaAbsolutaAUrl($registroExistente['pre_img_kilo'])) ?>"
                                                        target="_blank">Ver imagen actual</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Observaciones, acciones, responsable -->
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">OBSERVACIONES/ CONDICIONES REPORTADAS</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='observacionesc'>
                                            <td colspan='4'><textarea name='observaciones' id='observaciones' style='width:395px'
                                                    class='form-control'><?= htmlspecialchars($registroExistente['pre_obsevaciones'] ?? '') ?></textarea>
                                            </td>
                                        </tr>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">ACCIÓN CORRECTIVA</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='accioncorrectiva'>
                                            <td colspan='4'><textarea name='accion_correctiva' id='accion_correctiva' style='width:395px'
                                                    class='form-control'><?= htmlspecialchars($registroExistente['pre_correctiva'] ?? '') ?></textarea>
                                            </td>
                                        </tr>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">RESPONSABLE</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='responsable'>
                                            <td colspan='4'><input name='responsable' id='responsable'
                                                    value='<?= htmlspecialchars($registroExistente['pre_responsable'] ?? '') ?>'
                                                    style='width:395px' class='form-control'></td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- PREOPERACIONAL MOTO (solo nuevo formato) -->
                                    <?php if ($mostrarSecciones['preoperacional_moto'] ?? false): ?>
                                        
                                        <!-- Datos del vehículo -->
                                        <?php if (!empty($datosVehiculo)): ?>
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

                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoMotoSections($color, 'preoperacional-moto', $valoresEncuesta) ?>
                                        
                                        <!-- Kilometraje actual con imagen -->
                                        <tr bgcolor='$color' class='text' id='klmactual'>
                                            <td colspan='4'>KILOMETRAJE ACTUAL: <input name='kilometraje' id='kilometraje'
                                                    value='<?= htmlspecialchars($registroExistente['pre_kilrecorridos'] ?? NULL) ?>'
                                                    style='width:395px' class='form-control'></td>
                                        </tr>
                                        <tr bgcolor='$color'>
                                            <td colspan='4'>Imagen Kilometraje: <input type="file" name="imagen_kilometraje"
                                                    class="form-control">
                                                <?php if (!empty($registroExistente['pre_img_kilo'])): ?>
                                                    <br><a href="<?= htmlspecialchars(rutaAbsolutaAUrl($registroExistente['pre_img_kilo'])) ?>"
                                                        target="_blank">Ver imagen actual</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Observaciones, acciones, responsable -->
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">OBSERVACIONES/ CONDICIONES REPORTADAS</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='observacionesc'>
                                            <td colspan='4'><textarea name='observaciones' id='observaciones' style='width:395px'
                                                    class='form-control'><?= htmlspecialchars($registroExistente['pre_obsevaciones'] ?? '') ?></textarea>
                                            </td>
                                        </tr>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">ACCIÓN CORRECTIVA</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='accioncorrectiva'>
                                            <td colspan='4'><textarea name='accion_correctiva' id='accion_correctiva' style='width:395px'
                                                    class='form-control'><?= htmlspecialchars($registroExistente['pre_correctiva'] ?? '') ?></textarea>
                                            </td>
                                        </tr>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">RESPONSABLE</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='responsable'>
                                            <td colspan='4'><input name='responsable' id='responsable'
                                                    value='<?= htmlspecialchars($registroExistente['pre_responsable'] ?? '') ?>'
                                                    style='width:395px' class='form-control'></td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- COMPROMISO Y DECLARACIÓN -->
                                    <tr bgcolor="#ff0000" class="tittle3">
                                        <td colspan="4">Declaro que toda la información suministrada en el test anterior es verídica.</td>
                                    </tr>

                                    <!-- FIRMA A TRAZO (solo nuevos formularios) -->
                                    <?php if ($esValidacion && !empty($firmaDataUri)): ?>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">FIRMA DEL RESPONSABLE</td>
                                        </tr>
                                        <tr class='signature-row'>
                                            <td colspan='4'>
                                                <div class='signature-container'>
                                                    <img src="<?= $firmaDataUri ?>" alt="Firma del responsable"
                                                         style="max-width:400px; max-height:200px; border:2px solid #074F91; border-radius:4px; background-color:#fff; display:block;">
                                                    <small class="text-muted d-block mt-2">
                                                        <i class='fas fa-lock'></i> Firma del operario registrada
                                                    </small>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderSeccionFirma() ?>
                                    <?php endif; ?>

                                    <!-- VALIDACIÓN (solo si es modo validación) -->
                                    <?php if ($esValidacion): ?>
                                        <tr bgcolor="#868A08" class="tittle3">
                                            <td colspan="4" align="center">VALIDA PREOPERACIONAL</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='validapreopera'>
                                            <td colspan='4'>
                                                <strong>Descripción de la validación:</strong>
                                                <textarea name='desc_validacion' id='desc_validacion' style='width:395px'
                                                    class='form-control'><?= htmlspecialchars($registroExistente['pre_descvalidada'] ?? '') ?></textarea>
                                                <br>
                                                <strong>Observaciones adicionales:</strong>
                                                <textarea name='observaciones_validacion' id='observaciones_validacion' style='width:395px'
                                                    class='form-control' placeholder="Observaciones para la validación del preoperacional"></textarea>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                <?php else: ?>

                                    <!-- ==================== FORMATO LEGADO (CON COVID, FATIGA Y RADIO BUTTONS) ==================== -->

                                    <?php
                                    // Extraer valores existentes de la encuesta JSON
                                    $valoresEncuesta = [];
                                    if ($registroExistente && !empty($registroExistente['preencuesta'])) {
                                        $valoresEncuesta = json_decode($registroExistente['preencuesta'], true) ?? [];
                                    }

                                    // En modo validación, detectar qué secciones legacy tienen datos para mostrar solo lo relevante
                                    $mostrarCovid = true;
                                    $mostrarVehiculo = true;
                                    $mostrarFatiga = true;
                                    $mostrarImplementos = true;
                                    if ($esValidacion && !empty($valoresEncuesta)) {
                                        $keysEncuesta = array_keys($valoresEncuesta);
                                        $mostrarCovid = (bool) array_intersect(
                                            ['covid191','covid192','covid193','covid194','covid195','covid196','covid197','covid198','covid199'],
                                            $keysEncuesta
                                        );
                                        $mostrarVehiculo = (bool) array_intersect(
                                            ['llantas1','llantas2','llantas3','llantas4','llantas5','llantas6',
                                             'transmision1','transmision2','Luces1','Luces2','Luces3',
                                             'fugas1','fugas2','fugas3','fugas4','fugas5','fugas6',
                                             'mandos1','mandos2','entorno1','entorno2','entorno3',
                                             'elementos1','elementos2','elementos3','elementos4','elementos5','elementos6',
                                             'direccionales1','direccionales2','direccionales3','direccionales4',
                                             'cabina1','cabina2','cabina3','cabina4','cabina5','cabina6','cabina7','cabina8',
                                             'dispositivos1','dispositivos2','dispositivos3','dispositivos4','dispositivos5',
                                             'dispositivos6','dispositivos7','dispositivos8','dispositivos9','dispositivos10','dispositivos11',
                                             'indicadores1','indicadores2','indicadores3',
                                             'Herramientas1','Herramientas2','Herramientas3','Herramientas4','Herramientas5','Herramientas6','Herramientas7'],
                                            $keysEncuesta
                                        );
                                        $mostrarFatiga = (bool) array_intersect(
                                            ['elementosp1','elementosp2','elementosp3','elementosp4','elementosp5','elementosp6','elementosp7'],
                                            $keysEncuesta
                                        );
                                        $mostrarImplementos = (bool) array_intersect(
                                            ['implementos1','implementos2','implementos3','implementos4',
                                             'implementos10','implementos11','implementos12','implementos13',
                                             'implementos14','implementos18','implementos19'],
                                            $keysEncuesta
                                        );
                                    }
                                    ?>

                                    <!-- BLOQUE COVID-19 -->
                                    <?php if ($mostrarCovid): ?>
                                    <tr bgcolor="#074F91" class="tittle3">
                                        <td colspan="2" width="4" align="center">TEST DE REPORTE DIARIO DE SINTOMATOLOGIA</td>
                                        <td colspan="1" width="4" align="center">SI</td>
                                        <td colspan="1" width="4" align="center">NO</td>
                                    </tr>
                                    <?= PreoperacionalEncuestaLegadoViewHelper::renderPreguntasCovid($color, $valoresEncuesta) ?>
                                    <tr bgcolor='$color' class='text' id='temperatura'>
                                        <td colspan='4'>Temperatura: <input name='param19' id='param19'
                                                value='<?= htmlspecialchars($registroExistente['pre_temperatura'] ?? '') ?>'
                                                style='width:395px' class='form-control'></td>
                                    </tr>
                                    <tr bgcolor='$color'>
                                        <td colspan='4'>Imagen Temperatura: <input type="file" name="param20"
                                                class="form-control"></td>
                                    </tr>
                                    <?php endif; ?>

                                    <!-- Datos del vehículo -->
                                    <?php if (!empty($datosVehiculo)): ?>
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

                                    <!-- PREOPERACIONAL VEHÍCULO (RADIO BUTTONS - SIN CHECKBOXES) -->
                                    <?php if ($mostrarVehiculo && $tipovehiculo == 'MOTO'): ?>
                                        <?= PreoperacionalEncuestaLegadoViewHelper::renderMotoSections($color, $valoresEncuesta) ?>
                                    <?php elseif ($mostrarVehiculo && $tipovehiculo == 'CARRO'): ?>
                                        <?= PreoperacionalEncuestaLegadoViewHelper::renderCarroSections($color, $valoresEncuesta) ?>
                                    <?php endif; ?>

                                    <!-- FATIGA -->
                                    <?php if ($mostrarFatiga): ?>
                                    <?= PreoperacionalEncuestaLegadoViewHelper::renderFatigaSection($color, $valoresEncuesta) ?>
                                    <?php endif; ?>

                                    <!-- Implementos de trabajo -->
                                    <?php if ($mostrarImplementos && ($nivel_acceso == 3 || $param5 == 'valida')): ?>
                                        <?= PreoperacionalEncuestaLegadoViewHelper::renderImplementosTrabajo($color, $registroExistente['pre_limpiomaleta'] ?? null, $valoresEncuesta) ?>
                                    <?php endif; ?>

                                    <!-- Kilometraje -->
                                    <tr bgcolor='$color' class='text' id='klmactual'>
                                        <td colspan='4'>KILOMETRAJE ACTUAL: <input name='param12' id='param12'
                                                value='<?= htmlspecialchars($registroExistente['pre_kilrecorridos'] ?? NULL) ?>'
                                                style='width:395px' class='form-control'></td>
                                    </tr>
                                    <tr bgcolor='$color'>
                                        <td colspan='4'>Imagen Kilometraje: <input type="file" name="param30"
                                                class="form-control">
                                            <?php if (!empty($registroExistente['pre_img_kilo'])): ?>
                                                <br><a href="<?= htmlspecialchars(rutaAbsolutaAUrl($registroExistente['pre_img_kilo'])) ?>"
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

                                    <!-- COMPROMISO Y DECLARACIÓN (legado) -->
                                    <tr bgcolor="#878787" class="tittle3">
                                        <td colspan="4">
                                            <p align="center">YO COMO TRABAJADOR DE LA EMPRESA TRANSMILLAS ME COMPROMETO A...</p>
                                        </td>
                                    </tr>
                                    <tr bgcolor="#ff0000" class="tittle3">
                                        <td colspan="4">Declaro que toda la información suministrada en el test anterior es verídica, de caso contrario puede acarrear sanciones disciplinarias y su correspondiente aviso a las autoridades competentes que haya lugar.</td>
                                    </tr>

                                    <!-- VALIDACIÓN (solo si es modo validación) -->
                                    <?php if ($esValidacion): ?>
                                        <tr bgcolor="#868A08" class="tittle3">
                                            <td colspan="4" align="center">VALIDA PREOPERACIONAL Y COVID 19</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='validapreopera'>
                                            <td colspan='4'><textarea name='param10' id='param10' style='width:395px'
                                                    class='form-control'><?= htmlspecialchars($registroExistente['pre_descvalidada'] ?? '') ?></textarea>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </table>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pasar variables de PHP a JavaScript -->
    <script>
        var ES_VALIDACION = <?= json_encode($esValidacion) ?>;
        var URL_REDIRECT = <?= json_encode($esValidacion ? '' : '../../inicio.php?bandera=1') ?>;
    </script>

    <!-- JavaScript del formulario preoperacional -->
    <script src="../assets/js/preoperacional.js?v=<?= filemtime(__DIR__ . '/../../assets/js/preoperacional.js') ?>"></script>

</body>

</html>
