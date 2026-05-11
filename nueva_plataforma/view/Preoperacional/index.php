<?php
/**
 * Vista del formulario de preoperacional
 * DISEÑO DE TARJETAS: Cada sección y subsección es una tarjeta independiente
 * con colores pastel semitransparentes.
 *
 * NUEVO FORMATO: Sin COVID, sin fatiga
 * Solo secciones basadas en rol: Administrativo, Conductor, Vehículo Propio, Auxiliar de Carga
 * + Preoperacional de vehículo (carro/moto)
 *
 * FORMATO LEGADO: Mantiene COVID, fatiga, implementos de trabajo
 */

require_once __DIR__ . '/../../helpers/PreoperacionalHelpers/PreoperacionalEncuestaLegadoViewHelper.php';
require_once __DIR__ . '/../../helpers/PreoperacionalHelpers/PreoperacionalNuevaEncuestaViewHelper.php';

$usarNuevoFormato = ($formatoEncuesta === 'nuevo');

// Extraer valores existentes de la encuesta JSON para precargar el formulario
$valoresEncuesta = [];
if ($registroExistente && !empty($registroExistente['preencuesta'])) {
    $valoresEncuesta = json_decode($registroExistente['preencuesta'], true) ?? [];
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
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../assets/css/preoperacional.css?<?= filemtime(__DIR__ . '/../../assets/css/preoperacional.css') ?>">
    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8edf2 50%, #f5f0f0 100%);
            min-height: 100vh;
        }

        .btn-guardar {
            background: linear-gradient(135deg, #074F91, #053A6E);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            padding: 14px 40px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(7, 79, 145, 0.3);
            transition: all 0.25s ease;
            width: 100%;
            max-width: 400px;
            display: block;
            margin: 0 auto;
        }

        .btn-guardar:hover {
            background: linear-gradient(135deg, #0A5FB0, #074F91);
            box-shadow: 0 6px 20px rgba(7, 79, 145, 0.45);
            transform: translateY(-2px);
        }

        .btn-guardar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Botones de volver */
        .btn-volver {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .btn-volver:hover {
            background: rgba(255,255,255,0.35);
            color: #fff;
        }

        /* Modo validación: controles deshabilitados sin opacidad */
        #formPreoperacional .obtener:disabled,
        #formPreoperacional input:disabled,
        #formPreoperacional select:disabled {
            opacity: 1 !important;
            cursor: default;
        }

        #formPreoperacional .validation-disabled {
            opacity: 1 !important;
            pointer-events: none;
            cursor: default;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="preop-main-container">
            <div class="main-form-card">
                <!-- Header -->
                <div class="card-header mi-header d-flex align-items-center justify-content-between">
                    <button class="btn btn-volver" onclick="history.back()">
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
                        <!-- Campos ocultos -->
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
                        <!-- ==================== CONTENEDOR DE TARJETAS ==================== -->
                        <div class="preop-sections-wrapper">

                        <!-- ==================== NUEVO FORMATO: TARJETAS POR SECCIÓN ==================== -->
                        <?php if ($usarNuevoFormato): ?>

                            <!-- SECCIÓN PERSONAL: ADMINISTRATIVO -->
                            <?php if ($mostrarSecciones['administrativo'] ?? false): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                    PreoperacionalNuevaEncuestaViewHelper::getPreguntasAdministrativo(),
                                    '👤 EVALUACIÓN PERSONAL ADMINISTRATIVO',
                                    'administrativo',
                                    $valoresEncuesta
                                ) ?>
                            <?php endif; ?>

                            <!-- SECCIÓN PERSONAL: CONDUCTOR -->
                            <?php if ($mostrarSecciones['conductor'] ?? false): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                    PreoperacionalNuevaEncuestaViewHelper::getPreguntasConductor(),
                                    '🚗 EVALUACIÓN PERSONAL CONDUCTOR',
                                    'conductor',
                                    $valoresEncuesta
                                ) ?>
                            <?php endif; ?>

                            <!-- SECCIÓN PERSONAL: VEHÍCULO PROPIO (MOTO) -->
                            <?php if ($mostrarSecciones['vehiculo_propio'] ?? false): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                    PreoperacionalNuevaEncuestaViewHelper::getPreguntasVehiculoPropio(),
                                    '🏍️ EVALUACIÓN VEHÍCULO PROPIO (MOTO)',
                                    'vehiculo-propio',
                                    $valoresEncuesta
                                ) ?>
                            <?php endif; ?>

                            <!-- SECCIÓN PERSONAL: AUXILIAR DE CARGA -->
                            <?php if ($mostrarSecciones['auxiliar_carga'] ?? false): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                    PreoperacionalNuevaEncuestaViewHelper::getPreguntasAuxiliarCarga(),
                                    '📦 EVALUACIÓN AUXILIAR DE CARGA',
                                    'auxiliar-carga',
                                    $valoresEncuesta
                                ) ?>
                            <?php endif; ?>

                            <!-- ==================== SECCIÓN VEHÍCULO CARRO ==================== -->
                            <?php if ($mostrarSecciones['preoperacional_vehiculo'] ?? false): ?>

                                <!-- Datos del vehículo -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderVehicleInfoCard($datosVehiculo) ?>

                                <!-- Subsecciones del vehículo como tarjetas individuales -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoCarroSections($valoresEncuesta) ?>

                                <!-- Kilometraje -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderKilometrajeCard($registroExistente, $esValidacion) ?>

                                <!-- Observaciones -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderObservacionesCard($registroExistente, $esValidacion) ?>

                                <?php if ($esValidacion): ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderAccionCorrectivaCard($registroExistente) ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderResponsableCard($registroExistente) ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- ==================== SECCIÓN VEHÍCULO MOTO ==================== -->
                            <?php if ($mostrarSecciones['preoperacional_moto'] ?? false): ?>

                                <!-- Datos del vehículo -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderVehicleInfoCard($datosVehiculo) ?>

                                <!-- Subsecciones de la moto como tarjetas individuales -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoMotoSections($valoresEncuesta) ?>

                                <!-- Kilometraje -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderKilometrajeCard($registroExistente, $esValidacion) ?>

                                <!-- Observaciones -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderObservacionesCard($registroExistente, $esValidacion) ?>

                                <?php if ($esValidacion): ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderAccionCorrectivaCard($registroExistente) ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderResponsableCard($registroExistente) ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- DECLARACIÓN -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderDeclaracionCard() ?>

                            <!-- FIRMA -->
                            <?php if ($esValidacion && !empty($firmaDataUri)): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderFirmaRegistradaCard($firmaDataUri) ?>
                            <?php else: ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderSeccionFirma() ?>
                            <?php endif; ?>

                            <!-- VALIDACIÓN -->
                            <?php if ($esValidacion): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderValidacionCard($registroExistente, true) ?>
                            <?php endif; ?>

                        <?php else: ?>

                            <!-- ==================== FORMATO LEGADO ==================== -->
                            <?php
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

                            <!-- COVID-19 -->
                            <?php if ($mostrarCovid): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderCovidCard($valoresEncuesta, $registroExistente) ?>
                            <?php endif; ?>

                            <!-- Datos del vehículo -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderVehicleInfoCard($datosVehiculo) ?>

                            <!-- Preoperacional vehículo legado -->
                            <?php if ($mostrarVehiculo && $tipovehiculo == 'MOTO'): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderMotoSections($valoresEncuesta) ?>
                            <?php elseif ($mostrarVehiculo && $tipovehiculo == 'CARRO'): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderCarroSections($valoresEncuesta) ?>
                            <?php endif; ?>

                            <!-- Fatiga -->
                            <?php if ($mostrarFatiga): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderFatigaCard($valoresEncuesta) ?>
                            <?php endif; ?>

                            <!-- Implementos de trabajo -->
                            <?php if ($mostrarImplementos && ($nivel_acceso == 3 || $param5 == 'valida')): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderImplementosCards($valoresEncuesta, $registroExistente['pre_limpiomaleta'] ?? null) ?>
                            <?php endif; ?>

                            <!-- Kilometraje -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderKilometrajeCard($registroExistente, $esValidacion) ?>

                            <!-- Observaciones -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderObservacionesCard($registroExistente, $esValidacion) ?>

                            <?php if ($esValidacion): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderAccionCorrectivaCard($registroExistente) ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderResponsableCard($registroExistente) ?>
                            <?php endif; ?>

                            <!-- Compromiso y declaración (legado) -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderCompromisoCard() ?>
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderDeclaracionCard() ?>

                            <!-- Validación (legado) -->
                            <?php if ($esValidacion): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderValidacionLegadoCard($registroExistente) ?>
                            <?php endif; ?>

                        <?php endif; ?>

                        </div><!-- /preop-sections-wrapper -->

                        <!-- Ubicación GPS con mapa -->
                        <?php $ubicacionGuardada = $valoresEncuesta['ubicacion'] ?? null; ?>
                        <div id="ubicacion_container" class="ubicacion-container">
                            <?php if ($esValidacion && $ubicacionGuardada): ?>
                                <div id="mapa_ubicacion" class="mapa-ubicacion"></div>
                                <div class="ubicacion-coords-text">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <strong>Coordenadas registradas:</strong> <?= htmlspecialchars($ubicacionGuardada) ?>
                                </div>
                            <?php else: ?>
                                <div class="ubicacion-status ubicacion-cargando">
                                    <i class="fas fa-spinner fa-spin"></i> Obteniendo ubicación...
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Botón guardar -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-guardar" id="btnGuardar">
                                <i class="fas fa-save me-2"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div><!-- /card-body -->
            </div><!-- /main-form-card -->
        </div><!-- /preop-main-container -->
    </div><!-- /container-fluid -->

    <!-- Variables PHP a JavaScript -->
    <script>
        var ES_VALIDACION = <?= json_encode($esValidacion) ?>;
        var URL_REDIRECT = <?= json_encode($esValidacion ? '' : '../../inicio.php?bandera=1') ?>;
        var UBICACION_GUARDADA = <?= json_encode($ubicacionGuardada) ?>;
    </script>

    <!-- JavaScript del formulario -->
    <script src="../assets/js/preoperacional.js?v=<?= filemtime(__DIR__ . '/../../assets/js/preoperacional.js') ?>"></script>

</body>
</html>
