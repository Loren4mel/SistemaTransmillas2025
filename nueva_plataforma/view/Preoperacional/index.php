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
                    <?php if (!$esCovid): ?>
                        <span class="format-indicator <?= $usarNuevoFormato ? 'nuevo' : 'legado' ?>">
                            <?= $usarNuevoFormato ? 'NUEVO FORMATO' : 'FORMATO LEGADO' ?>
                        </span>
                    <?php endif; ?>
                </h3>
            </div>

            <!-- ==================== BOTONES DE PRUEBA ==================== -->
            <?php if (!$esCovid): ?>
            <div class="card mt-3 mb-3 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <strong>🧪 MODO PRUEBA - Cambiar entre casos</strong>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Caso actual:</strong>
                        <span class="badge bg-primary">
                            <?php
                            if (!empty($casoPrueba) && $casoPrueba === 'legado') {
                                echo 'Formato Legado (' . $tipovehiculo . ')';
                            } elseif ($mostrarSecciones['administrativo'] ?? false) {
                                echo 'Administrativo';
                            } elseif ($mostrarSecciones['conductor'] ?? false) {
                                echo 'Conductor (Carro)';
                            } elseif ($mostrarSecciones['auxiliar_carga'] ?? false) {
                                echo 'Auxiliar de Carga';
                            } elseif ($mostrarSecciones['vehiculo_propio'] ?? false) {
                                echo 'Vehículo Propio (Moto)';
                            } else {
                                echo 'Por defecto';
                            }
                            ?>
                        </span>
                    </p>
                    <div class="btn-group flex-wrap" role="group">
                        <a href="?caso_prueba=administrativo&tipo_vehiculo=NONE" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-tie"></i> Caso 1: Administrativo
                        </a>
                        <a href="?caso_prueba=conductor&tipo_vehiculo=CARRO" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-car"></i> Caso 2: Conductor (Carro)
                        </a>
                        <a href="?caso_prueba=moto&tipo_vehiculo=MOTO" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-motorcycle"></i> Caso 3: Moto Propia
                        </a>
                        <a href="?caso_prueba=auxiliar&tipo_vehiculo=NONE" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-box"></i> Caso 4: Auxiliar de Carga
                        </a>
                        <a href="?caso_prueba=legado&tipo_vehiculo=CARRO" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-history"></i> Caso 5: Legado Carro
                        </a>
                        <a href="?caso_prueba=legado&tipo_vehiculo=MOTO" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-history"></i> Caso 6: Legado Moto
                        </a>
                    </div>
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Estos botones recargan la página con parámetros de prueba para verificar cada caso.
                    </small>
                </div>
            </div>
            <?php endif; ?>

            <!-- ==================== PANEL DE INFORMACIÓN DE SECCIONES ==================== -->
            <?php if ($usarNuevoFormato && !$esCovid && empty($casoPrueba) || ($usarNuevoFormato && !empty($casoPrueba) && $casoPrueba !== 'legado')): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-info text-white">
                    <strong>📋 Secciones Activas en este Caso</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($mostrarSecciones['administrativo']): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert alert-primary mb-0">
                                <i class="fas fa-user-tie"></i> Administrativo
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mostrarSecciones['conductor']): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-car"></i> Conductor
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mostrarSecciones['vehiculo_propio']): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-motorcycle"></i> Vehículo Propio
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mostrarSecciones['auxiliar_carga']): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-box"></i> Auxiliar de Carga
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mostrarSecciones['preoperacional_vehiculo']): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert alert-dark mb-0">
                                <i class="fas fa-truck"></i> Preoperacional Carro
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mostrarSecciones['preoperacional_moto']): ?>
                        <div class="col-md-3 mb-2">
                            <div class="alert alert-dark mb-0">
                                <i class="fas fa-motorcycle"></i> Preoperacional Moto
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                    <input type="hidden" name="formato_encuesta" id="formato_encuesta" value="<?= $usarNuevoFormato ? 'nuevo' : 'legado' ?>">
                    <?php if (!empty($casoPrueba)): ?>
                    <input type="hidden" name="caso_prueba" value="<?= htmlspecialchars($casoPrueba) ?>">
                    <?php endif; ?>

                    <div id="contenedor1" style="display:flex;">
                        <div id="primero" style="width: 100%; float:left;">
                            <table class="table table-hover">

                                <!-- ==================== NUEVO FORMATO: SOLO SECCIONES BASADAS EN ROL (SIN COVID, SIN FATIGA) ==================== -->
                                <?php if ($usarNuevoFormato): ?>
                                    
                                    <!-- SECCIÓN ADMINISTRATIVO (solo roles administrativos) -->
                                    <?php if ($mostrarSecciones['administrativo'] ?? false): ?>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">👤 EVALUACIÓN PERSONAL ADMINISTRATIVO</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasAdministrativo(),
                                            $color
                                        ) ?>
                                    <?php endif; ?>

                                    <!-- SECCIÓN CONDUCTOR (solo conductores de carro) -->
                                    <?php if ($mostrarSecciones['conductor'] ?? false): ?>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">🚗 EVALUACIÓN PERSONAL CONDUCTOR</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasConductor(),
                                            $color
                                        ) ?>
                                    <?php endif; ?>

                                    <!-- SECCIÓN VEHÍCULO PROPIO (solo motos) -->
                                    <?php if ($mostrarSecciones['vehiculo_propio'] ?? false): ?>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">🏍️ EVALUACIÓN VEHÍCULO PROPIO (MOTO)</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasVehiculoPropio(),
                                            $color
                                        ) ?>
                                    <?php endif; ?>

                                    <!-- SECCIÓN AUXILIAR DE CARGA -->
                                    <?php if ($mostrarSecciones['auxiliar_carga'] ?? false): ?>
                                        <tr bgcolor="#074F91" class="tittle3">
                                            <td colspan="4">📦 EVALUACIÓN AUXILIAR DE CARGA</td>
                                        </tr>
                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                            PreoperacionalNuevaEncuestaViewHelper::getPreguntasAuxiliarCarga(),
                                            $color
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

                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoCarroSections($color) ?>
                                        
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

                                        <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoMotoSections($color) ?>
                                        
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

                                    <!-- COMPROMISO Y DECLARACIÓN -->
                                    <tr bgcolor="#ff0000" class="tittle3">
                                        <td colspan="4">Declaro que toda la información suministrada en el test anterior es verídica.</td>
                                    </tr>

                                    <!-- VALIDACIÓN (solo si es modo validación) -->
                                    <?php if ($esValidacion): ?>
                                        <tr bgcolor="#868A08" class="tittle3">
                                            <td colspan="4" align="center">VALIDA PREOPERACIONAL</td>
                                        </tr>
                                        <tr bgcolor='$color' class='text' id='validapreopera'>
                                            <td colspan='4'><textarea name='param10' id='param10' style='width:395px'
                                                    class='form-control'><?= htmlspecialchars($registroExistente['pre_descvalidada'] ?? '') ?></textarea>
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
                                    ?>

                                    <!-- BLOQUE COVID-19 -->
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
                                    <?php if ($tipovehiculo == 'MOTO'): ?>
                                        <?= PreoperacionalEncuestaLegadoViewHelper::renderMotoSections($color, $valoresEncuesta) ?>
                                    <?php elseif ($tipovehiculo == 'CARRO'): ?>
                                        <?= PreoperacionalEncuestaLegadoViewHelper::renderCarroSections($color, $valoresEncuesta) ?>
                                    <?php endif; ?>

                                    <!-- FATIGA -->
                                    <?= PreoperacionalEncuestaLegadoViewHelper::renderFatigaSection($color, $valoresEncuesta) ?>

                                    <!-- Implementos de trabajo -->
                                    <?php if (($nivel_acceso == 3 || $param5 == 'valida')): ?>
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
