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

require_once __DIR__ . '/../../helpers/PreoperacionalHelpers/Views/PreoperacionalEncuestaLegadoViewHelper.php';
require_once __DIR__ . '/../../helpers/PreoperacionalHelpers/Views/PreoperacionalNuevaEncuestaViewHelper.php';

$usarNuevoFormato = ($formatoEncuesta === 'nuevo');

// Extraer valores de la encuesta para precargar el formulario
$valoresEncuesta = [];
if ($esRegistroRelacional) {
    // Esquema relacional: respuestas desde preop_respuestas (fuente primaria).
    // La metadata (ubicacion, firma_documento_id, inspeccion_documento_id,
    // temperatura_documento_id) ya fue cargada en el controlador desde las
    // columnas dedicadas de la tabla, la tabla documentos, y el JSON legacy
    // de preencuesta como último recurso.
    $valoresEncuesta = $valoresEncuestaRelacional;
} else {
    // Esquema legacy: respuestas desde preencuesta JSON
    if ($registroExistente && !empty($registroExistente['preencuesta'])) {
        $valoresEncuesta = json_decode($registroExistente['preencuesta'], true) ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Preoperacional - Transmillas</title>
    <link rel="shortcut icon" href="<?= $appBasePath ?>/../images/Logo Google Nuevo.png">
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
    <script>
        window.APP_BASE_URL = "<?= $appBasePath ?>";
    </script>
    <link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/preoperacional.css?<?= filemtime(__DIR__ . '/../../assets/css/preoperacional.css') ?>">
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

        /* En modo validación, el formulario completo está en modo solo-lectura.
           La clase .modo-validacion en <form> gobierna TODO el comportamiento
           visual; los estilos están en preoperacional.css. */
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
                        <?php
                        if ($esSoloLectura) {
                            echo 'Visualización de Preoperacional';
                        } elseif ($esCovid && !$usarNuevoFormato) {
                            echo 'Test COVID-19';
                        } elseif ($usarNuevoFormato && !($mostrarSecciones['preoperacional_vehiculo'] || $mostrarSecciones['preoperacional_moto'])) {
                            echo 'Preoperacional Transmillas';
                        } else {
                            echo 'Preoperacional de Vehículo';
                        }
                        ?>
                        <?php if (!$esCovid || $usarNuevoFormato): ?>
                            <span class="format-indicator <?= $usarNuevoFormato ? 'nuevo' : 'legado' ?>">
                                <?= $usarNuevoFormato ? 'NUEVO FORMATO' : 'FORMATO LEGADO' ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>

                <div class="card-body">
                    <form id="formPreoperacional" enctype="multipart/form-data" class="<?= $esValidacion ? 'modo-validacion' : '' ?>">
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
                        <input type="hidden" name="idvehiculo" id="idvehiculo" value="<?= $datosVehiculo['idvehiculos'] ?? 0 ?>">
                        <input type="hidden" name="tipo_vehiculo" value="<?= htmlspecialchars($tipovehiculo) ?>">
                        <input type="hidden" name="param3" value="<?= htmlspecialchars($tipovehiculo) ?>">
                        <input type="hidden" name="formato_encuesta" id="formato_encuesta" value="<?= $usarNuevoFormato ? 'nuevo' : 'legado' ?>">
                        <input type="hidden" name="vehiculo_bloquear" id="vehiculo_bloquear" value="<?= $mostrarAlertaVencidos ? '1' : '0' ?>">
                        <input type="hidden" name="vehiculo_alertas" id="vehiculo_alertas" value="<?= htmlspecialchars(json_encode($estadoDocumentos['alertas'] ?? [])) ?>">
                        <input type="hidden" name="id_version" id="id_version" value="<?= htmlspecialchars($idVersionActiva ?? '') ?>">

                        <!-- Campo oculto para vehículo seleccionado (puede cambiar por novedad) -->
                        <input type="hidden" name="idvehiculo_seleccionado" id="idvehiculo_seleccionado" value="<?= $datosVehiculo['idvehiculos'] ?? 0 ?>">

                        <!-- ==================== PANEL DE ESTADO / NOVEDAD VEHICULAR ==================== -->
                        <!-- Solo se muestra a conductores (CARRO) o vehículo propio (MOTO).
                             Roles administrativos y auxiliares de carga no deben ver este panel
                             aunque tengan un vehículo residual asignado en la BD. -->
                        <?php $esRolVehicular = in_array($tipovehiculo, ['CARRO', 'MOTO']) && $esRolVehicularAutorizado; ?>
                        <?php if ($esRolVehicular && $tieneVehiculoAsignado): ?>
                            <?= $novedadHelper->renderNovedadPanel($novedadVehiculo, $datosVehiculo, $vehiculosDisponibles, $esValidacion) ?>

                            <!-- Botón de selección voluntaria de vehículo (solo fuera de validación/vista) -->
                            <?php if (!$esValidacion && !$esSoloLectura): ?>
                            <div style="text-align:center; margin: 12px 0;">
                                <button type="button" class="btn btn-outline-primary" id="btnSeleccionarVehiculoDiario">
                                    <i class="fas fa-exchange-alt"></i> Seleccionar otro vehículo para hoy
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Datos del vehículo + alertas de documentos (centralizado: visible cuando hay vehículo asignado) -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderVehicleInfoCard($datosVehiculo, $alertaSeveridadVehiculo) ?>
                            <?= $alertasVehiculoHtml ?>
                        <?php elseif ($esRolVehicular && !$tieneVehiculoAsignado): ?>
                            <?php if (!$esSoloLectura): ?>
                                <?= $novedadHelper->renderNoVehiclePanel($vehiculosDisponibles) ?>
                            <?php endif; ?>
                        <?php endif; ?>

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
                                    $valoresEncuesta,
                                    $esValidacion
                                ) ?>
                            <?php endif; ?>

                            <!-- SECCIÓN PERSONAL: CONDUCTOR -->
                            <?php if ($mostrarSecciones['conductor'] ?? false): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                    PreoperacionalNuevaEncuestaViewHelper::getPreguntasConductor(),
                                    '🚗 EVALUACIÓN PERSONAL CONDUCTOR',
                                    'conductor',
                                    $valoresEncuesta,
                                    $esValidacion
                                ) ?>
                            <?php endif; ?>

                            <!-- SECCIÓN PERSONAL: VEHÍCULO PROPIO (MOTO) -->
                            <?php if ($mostrarSecciones['vehiculo_propio'] ?? false): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                    PreoperacionalNuevaEncuestaViewHelper::getPreguntasVehiculoPropio(),
                                    '🏍️ EVALUACIÓN VEHÍCULO PROPIO (MOTO)',
                                    'vehiculo-propio',
                                    $valoresEncuesta,
                                    $esValidacion
                                ) ?>
                            <?php endif; ?>

                            <!-- SECCIÓN PERSONAL: AUXILIAR DE CARGA -->
                            <?php if ($mostrarSecciones['auxiliar_carga'] ?? false): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderPreguntasPersonales(
                                    PreoperacionalNuevaEncuestaViewHelper::getPreguntasAuxiliarCarga(),
                                    '📦 EVALUACIÓN AUXILIAR DE CARGA',
                                    'auxiliar-carga',
                                    $valoresEncuesta,
                                    $esValidacion
                                ) ?>
                            <?php endif; ?>

                            <!-- ==================== CONTENEDOR DE SECCIONES DE VEHÍCULO ==================== -->
                            <div id="vehiculoSectionsContainer">

                            <!-- ==================== SECCIÓN VEHÍCULO CARRO ==================== -->
                            <?php if ($mostrarSecciones['preoperacional_vehiculo'] ?? false): ?>

                                <!-- Subsecciones del vehículo como tarjetas individuales -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoCarroSections($valoresEncuesta, $esValidacion) ?>

                                <!-- Kilometraje -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderKilometrajeCard($registroExistente, $esValidacion || $esSoloLectura) ?>

                                <!-- Observaciones -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderObservacionesCard($registroExistente, $esValidacion) ?>

                                <?php if ($esValidacion): ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderAccionCorrectivaCard($registroExistente, $esSoloLectura) ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderResponsableCard($registroExistente, $esSoloLectura) ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- ==================== SECCIÓN VEHÍCULO MOTO ==================== -->
                            <?php if ($mostrarSecciones['preoperacional_moto'] ?? false): ?>

                                <!-- Subsecciones de la moto como tarjetas individuales -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderVehiculoMotoSections($valoresEncuesta, $esValidacion) ?>

                                <!-- Kilometraje -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderKilometrajeCard($registroExistente, $esValidacion || $esSoloLectura) ?>

                                <!-- Observaciones -->
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderObservacionesCard($registroExistente, $esValidacion) ?>

                                <?php if ($esValidacion): ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderAccionCorrectivaCard($registroExistente, $esSoloLectura) ?>
                                    <?= PreoperacionalNuevaEncuestaViewHelper::renderResponsableCard($registroExistente, $esSoloLectura) ?>
                                <?php endif; ?>
                            <?php endif; ?>

                            </div><!-- /vehiculoSectionsContainer -->

                            <!-- Info: No se ha seleccionado vehículo (visible solo cuando aplica) -->
                            <div id="vehiculoNoSeleccionadoInfo" style="display:none;">
                                <div class="preop-card no-vehicle-info-card">
                                    <div class="preop-card-header">
                                        <i class="fas fa-info-circle"></i> INFORMACIÓN DEL VEHÍCULO
                                    </div>
                                    <div class="preop-card-body">
                                        <div class="no-vehicle-message">
                                            <i class="fas fa-car-side no-vehicle-icon"></i>
                                            <p class="no-vehicle-text">
                                                No tiene un vehículo asignado.
                                            </p>
                                            <p class="no-vehicle-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Por favor notifique al jefe de operaciones antes de continuar.</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Info: Novedad vehicular activa (visible cuando hay novedad sin resolver) -->
                            <div id="vehiculoNovedadActivaInfo" style="display:none;">
                                <div class="preop-card novedad-active-info-card">
                                    <div class="preop-card-header">
                                        <i class="fas fa-exclamation-triangle"></i> NOVEDAD VEHICULAR ACTIVA
                                    </div>
                                    <div class="preop-card-body">
                                        <div class="no-vehicle-message">
                                            <i class="fas fa-clipboard-list no-vehicle-icon"></i>
                                            <p class="no-vehicle-text">
                                                Debe finalizar el flujo de observaciones antes de continuar con la inspección del vehículo.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DECLARACIÓN -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderDeclaracionCard() ?>

                            <!-- FIRMA -->
                            <?php if ($esValidacion && !empty($firmaDataUri)): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderFirmaRegistradaCard($firmaDataUri) ?>
                            <?php elseif ($esSoloLectura): ?>
                                <div class="preop-card signature-card">
                                    <div class="preop-card-header"><i class="fas fa-pen"></i> FIRMA DEL RESPONSABLE</div>
                                    <div class="preop-card-body">
                                        <p style="margin:0;color:#999;"><i class="fas fa-minus-circle"></i> Sin firma registrada</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderSeccionFirma() ?>
                            <?php endif; ?>

                            <!-- VALIDACIÓN -->
                            <?php if ($esValidacion): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderValidacionCard($registroExistente, true, $esSoloLectura) ?>
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

                            <!-- ==================== CONTENEDOR DE SECCIONES DE VEHÍCULO ==================== -->
                            <div id="vehiculoSectionsContainer">

                            <!-- Datos del vehículo (solo conductores CARRO/MOTO con rol autorizado) -->
                            <?php if (in_array($tipovehiculo, ['CARRO', 'MOTO']) && $esRolVehicularAutorizado): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderVehicleInfoCard($datosVehiculo, $alertaSeveridadVehiculo) ?>
                                <?= $alertasVehiculoHtml ?>
                            <?php endif; ?>

                            <!-- Preoperacional vehículo legado -->
                            <?php if ($mostrarVehiculo && $tipovehiculo == 'MOTO'): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderMotoSections($valoresEncuesta) ?>
                            <?php elseif ($mostrarVehiculo && $tipovehiculo == 'CARRO'): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderCarroSections($valoresEncuesta) ?>
                            <?php endif; ?>

                            <!-- Kilometraje (solo si hay vehículo asignado de tipo CARRO o MOTO) -->
                            <?php if ($mostrarVehiculo && ($tipovehiculo == 'CARRO' || $tipovehiculo == 'MOTO')): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderKilometrajeCard($registroExistente, $esValidacion || $esSoloLectura) ?>
                            <?php endif; ?>

                            <!-- Observaciones -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderObservacionesCard($registroExistente, $esValidacion) ?>

                            <?php if ($esValidacion): ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderAccionCorrectivaCard($registroExistente) ?>
                                <?= PreoperacionalNuevaEncuestaViewHelper::renderResponsableCard($registroExistente) ?>
                            <?php endif; ?>

                            </div><!-- /vehiculoSectionsContainer -->

                            <!-- Fatiga -->
                            <?php if ($mostrarFatiga): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderFatigaCard($valoresEncuesta) ?>
                            <?php endif; ?>

                            <!-- Implementos de trabajo -->
                            <?php if ($mostrarImplementos && ($nivel_acceso == 3 || $esValidacion)): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderImplementosCards($valoresEncuesta, $registroExistente['pre_limpiomaleta'] ?? null) ?>
                            <?php endif; ?>

                            <!-- Info: No se ha seleccionado vehículo (visible solo cuando aplica) -->
                            <div id="vehiculoNoSeleccionadoInfo" style="display:none;">
                                <div class="preop-card no-vehicle-info-card">
                                    <div class="preop-card-header">
                                        <i class="fas fa-info-circle"></i> INFORMACIÓN DEL VEHÍCULO
                                    </div>
                                    <div class="preop-card-body">
                                        <div class="no-vehicle-message">
                                            <i class="fas fa-car-side no-vehicle-icon"></i>
                                            <p class="no-vehicle-text">
                                                No tiene un vehículo asignado.
                                            </p>
                                            <p class="no-vehicle-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Por favor notifique al jefe de operaciones antes de continuar.</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Info: Novedad vehicular activa (visible cuando hay novedad sin resolver) -->
                            <div id="vehiculoNovedadActivaInfo" style="display:none;">
                                <div class="preop-card novedad-active-info-card">
                                    <div class="preop-card-header">
                                        <i class="fas fa-exclamation-triangle"></i> NOVEDAD VEHICULAR ACTIVA
                                    </div>
                                    <div class="preop-card-body">
                                        <div class="no-vehicle-message">
                                            <i class="fas fa-clipboard-list no-vehicle-icon"></i>
                                            <p class="no-vehicle-text">
                                                Debe finalizar el flujo de observaciones antes de continuar con la inspección del vehículo.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Compromiso y declaración (legado) -->
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderCompromisoCard() ?>
                            <?= PreoperacionalNuevaEncuestaViewHelper::renderDeclaracionCard() ?>

                            <!-- Validación (legado) -->
                            <?php if ($esValidacion): ?>
                                <?= PreoperacionalEncuestaLegadoViewHelper::renderValidacionLegadoCard($registroExistente, $esSoloLectura) ?>
                            <?php endif; ?>

                        <?php endif; ?>

                        </div><!-- /preop-sections-wrapper -->

                        <!-- Ubicación GPS con mapa (solo formato nuevo) -->
                        <?php if ($usarNuevoFormato): ?>
                        <?php $ubicacionGuardada = $valoresEncuesta['ubicacion'] ?? null; ?>
                        <div id="ubicacion_container" class="ubicacion-container">
                            <?php if ($esValidacion && $ubicacionGuardada): ?>
                                <div id="mapa_ubicacion" class="mapa-ubicacion"></div>
                                <div class="ubicacion-coords-text">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <strong>Coordenadas registradas:</strong> <?= htmlspecialchars($ubicacionGuardada) ?>
                                </div>
                            <?php elseif ($esSoloLectura): ?>
                                <div class="ubicacion-status ubicacion-sin-datos">
                                    <i class="fas fa-map-marker-alt"></i> Sin ubicación registrada
                                </div>
                            <?php else: ?>
                                <div class="ubicacion-status ubicacion-cargando">
                                    <i class="fas fa-spinner fa-spin"></i> Obteniendo ubicación...
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- ==================== SECCIÓN DE ENTREGA DE VEHÍCULO ==================== -->
                        <!-- [ELIMINADO] Sección de entrega vehicular en validación. Selección diaria vía ASIGNACION_VEHICULO. -->

                        <?php if (!$esSoloLectura): ?>
                        <!-- Botón guardar -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-guardar" id="btnGuardar">
                                <i class="fas fa-save me-2"></i> Guardar
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div><!-- /card-body -->
            </div><!-- /main-form-card -->
        </div><!-- /preop-main-container -->
    </div><!-- /container-fluid -->

    <!-- Variables PHP a JavaScript -->
    <script>
        var ES_VALIDACION = <?= json_encode($esValidacion) ?>;
        var ES_SOLO_LECTURA = <?= json_encode($esSoloLectura ?? false) ?>;
        var MOSTRAR_SECCION_VEHICULO = <?= json_encode(($mostrarSecciones['preoperacional_vehiculo'] ?? false) || ($mostrarSecciones['preoperacional_moto'] ?? false)) ?>;
        var TIENE_NOVEDAD_VEHICULAR = <?= json_encode($novedadVehiculo['tieneNovedad'] ?? false) ?>;
        var ESTADO_GENERAL_VEHICULO = <?= json_encode($novedadVehiculo['estado_general'] ?? 'OPTIMO') ?>;
        var TIENE_VEHICULO_ASIGNADO = <?= json_encode($tieneVehiculoAsignado) ?>;
        var URL_REDIRECT = <?= json_encode($esValidacion ? '' : '../../inicio.php?bandera=1') ?>;
        var UBICACION_GUARDADA = <?= json_encode($ubicacionGuardada) ?>;
    </script>

    <!-- JavaScript del formulario -->
    <script src="<?= $appBasePath ?>/assets/js/preoperacional.js?v=<?= filemtime(__DIR__ . '/../../assets/js/preoperacional.js') ?>"></script>

</body>
</html>
