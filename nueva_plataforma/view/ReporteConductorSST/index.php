<?php
/**
 * Vista del formulario de Reporte Conductor SST
 *
 * Mobile-first, usando los mismos patrones visuales del preoperacional:
 * tarjetas con cabeceras de colores pastel, sombras suaves, radio buttons
 * estilo toggle, y subida de archivos con vista previa.
 *
 * Variables esperadas desde el controller:
 *   $modo             'semanal' | 'momento'
 *   $tiposAMostrar    array de strings ['accidente', 'comparendo'] o subconjunto
 *   $semana           ['inicio' => 'Y-m-d', 'fin' => 'Y-m-d']
 *   $nombreUsuario    string nombre del conductor
 *   $cedula           string cédula del conductor
 *   $placa            string placa del vehículo asignado
 *   $appBasePath      string ruta base de la app
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Conductor SST - Transmillas</title>
    <link rel="shortcut icon" href="<?= $appBasePath ?>/../images/Logo Google Nuevo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">

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

    <!-- Estilos del preoperacional (comparte diseño de tarjetas, botones, etc.) -->
    <link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/preoperacional.css?<?= filemtime(__DIR__ . '/../../assets/css/preoperacional.css') ?>">
    <!-- Estilos propios (solo overrides y cosas específicas del módulo) -->
    <link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/reporte_conductor_sst.css?<?= filemtime(__DIR__ . '/../../assets/css/reporte_conductor_sst.css') ?>">

    <script>
        window.APP_BASE_URL = "<?= $appBasePath ?>";
    </script>

    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e8edf2 50%, #f5f0f0 100%);
            min-height: 100vh;
        }
    </style>
</head>

<body>

    <div class="container-fluid py-4">
        <div class="preop-main-container">
            <div class="main-form-card">

                <!-- ===== HEADER ===== -->
                <div class="card-header mi-header d-flex align-items-center justify-content-between">
                    <button class="btn btn-volver" onclick="history.back()">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </button>
                    <h3 class="mb-0">
                        <i class="fas fa-shield-haltered me-2"></i>
                        Reporte Conductor SST
                        <span class="format-indicator" style="background: rgba(255,255,255,0.2); color: #fff;">
                            <?= $modo === 'semanal' ? 'TEST SEMANAL' : 'REPORTE ESPONTÁNEO' ?>
                        </span>
                    </h3>
                </div>

                <!-- ===== BODY ===== -->
                <div class="card-body">

                    <form id="formReporteSST" enctype="multipart/form-data">
                        <!-- Hidden inputs -->
                        <input type="hidden" id="rcsst_tipo_evento" value="<?= $modo; ?>">

                        <div class="preop-sections-wrapper">

                            <!-- ===== TARJETA: PANEL INFORMATIVO DEL CONDUCTOR ===== -->
                            <div class="preop-card rcsst-conductor-card" style="border-left: 4px solid #1B5E20;">
                                <div class="preop-card-header" style="background: linear-gradient(135deg, #2E7D32, #1B5E20);">
                                    <i class="fas fa-id-card"></i>
                                    Datos del Conductor
                                </div>
                                <div class="preop-card-body">
                                    <div class="rcsst-conductor-grid">
                                        <div class="rcsst-conductor-item">
                                            <span class="rcsst-conductor-label"><i class="fas fa-user me-1"></i> Nombre</span>
                                            <span class="rcsst-conductor-value"><?= htmlspecialchars($nombreUsuario) ?></span>
                                        </div>
                                        <div class="rcsst-conductor-item">
                                            <span class="rcsst-conductor-label"><i class="fas fa-id-card me-1"></i> Cédula</span>
                                            <span class="rcsst-conductor-value"><?= htmlspecialchars($cedula) ?></span>
                                        </div>
                                        <div class="rcsst-conductor-item">
                                            <span class="rcsst-conductor-label"><i class="fas fa-truck me-1"></i> Placa</span>
                                            <span class="rcsst-conductor-value rcsst-placa"><?= htmlspecialchars($placa) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== TARJETA: INFO DE SEMANA ===== -->
                            <div class="preop-card" style="border-left: 4px solid #074F91;">
                                <div class="preop-card-header" style="background: linear-gradient(135deg, #074F91, #053A6E);">
                                    <i class="fas fa-calendar-week"></i>
                                    <?php if ($modo === 'semanal'): ?>
                                        Test de Fin de Semana
                                    <?php else: ?>
                                        Reporte de Incidente
                                    <?php endif; ?>
                                </div>
                                <div class="preop-card-body">
                                    <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                                        <div style="flex: 1; min-width: 200px;">
                                            <span style="font-size: 13px; color: #666;">
                                                <strong>Semana:</strong>
                                                <?= date('d', strtotime($semana['inicio'])); ?> —
                                                <?= date('d M', strtotime($semana['fin'])); ?>
                                            </span>
                                        </div>
                                        <div style="flex-shrink: 0;">
                                            <?php if ($modo === 'semanal'): ?>
                                                <span style="background: rgba(255,193,7,0.2); color: #7a6200; padding: 4px 12px; border-radius: 10px; font-size: 12px; font-weight: 700;">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> Reporte semanal obligatorio
                                                </span>
                                            <?php else: ?>
                                                <span style="background: rgba(33,150,243,0.15); color: #1565C0; padding: 4px 12px; border-radius: 10px; font-size: 12px; font-weight: 700;">
                                                    <i class="fas fa-clock me-1"></i> Reporte espontáneo
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== TARJETA: MAPA DE UBICACIÓN ===== -->
                            <div class="preop-card ubicacion-card">
                                <div class="preop-card-header" style="background: linear-gradient(135deg, #E05050, #C03030);">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Ubicación actual
                                    <span class="format-indicator" style="background: rgba(255,255,255,0.25); color: #fff; margin-left: auto;">
                                        OBLIGATORIO
                                    </span>
                                </div>
                                <div class="preop-card-body">
                                    <div class="ubicacion-container">
                                        <div id="rcsst_mapa_container" class="mapa-ubicacion" style="display: flex; align-items: center; justify-content: center;">
                                            <div class="ubicacion-status ubicacion-cargando">
                                                <i class="fas fa-spinner fa-spin"></i> Obteniendo ubicación...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== TARJETAS DE PREGUNTAS (una por tipo) ===== -->
                            <?php
                            $preguntasConfig = [
                                'accidente' => [
                                    'emoji'       => '🚨',
                                    'icono'       => 'fa-car-crash',
                                    'titulo'      => '¿Sufrió algún accidente durante la semana?',
                                    'desc'        => 'Reporte cualquier incidente de tránsito, así no haya tenido lesiones.',
                                    'header_color'=> 'linear-gradient(135deg, #E05050, #C03030)',
                                    'border_color'=> '#E05050',
                                ],
                                'comparendo' => [
                                    'emoji'       => '🚔',
                                    'icono'       => 'fa-ticket-alt',
                                    'titulo'      => '¿Tuvo algún comparendo o llamado por parte de la Policía de Tránsito durante la semana?',
                                    'desc'        => 'Comparendos, multas, retenes o cualquier interacción con autoridades de tránsito.',
                                    'header_color'=> 'linear-gradient(135deg, #E8A020, #C88010)',
                                    'border_color'=> '#E8A020',
                                ],
                            ];

                            // Guías de observación por tipo
                            $guiasObservacion = [
                                'accidente' => [
                                    'Descripción detallada del siniestro',
                                    'Hubo afectación a personas: ¿Quiénes?',
                                    'Hubo afectación al medio ambiente: ¿Cuáles?',
                                    'Hubo afectación a la imagen corporativa: ¿Cuál?',
                                    'Daños y consecuencias (material y humana)',
                                    'Consecutivo del informe policial',
                                    'Nombre del organismo de tránsito que elaboró el informe policial',
                                ],
                                'comparendo' => [
                                    'Descripción detallada del comparendo',
                                    'Entidad que impuso el comparendo o la inmovilización',
                                    'Número o consecutivo del comparendo (si aplica)',
                                    '¿Hubo inmovilización del vehículo? Detallar',
                                    '¿Hubo afectación a la licencia de conducción?',
                                ],
                            ];

                            // Información de niveles de gravedad
                            $gravedadInfo = [
                                'accidente' => [
                                    ['nivel' => 1, 'etiqueta' => 'Leve',    'desc' => 'Primeros auxilios, afectación de costos menor a $1.000.000, quejas o reclamos.'],
                                    ['nivel' => 2, 'etiqueta' => 'Moderado', 'desc' => 'Incapacidad hasta 30 días, afectación entre $1.000.000 y $10.000.000, investigaciones y sanciones bajas.'],
                                    ['nivel' => 3, 'etiqueta' => 'Grave',    'desc' => 'Incapacidad > 30 días, afectación entre $10.000.000 y $100.000.000, investigaciones y sanciones medias.'],
                                    ['nivel' => 4, 'etiqueta' => 'Crítico',  'desc' => 'Muerte, afectación > $100.000.000, investigaciones y sanciones críticas.'],
                                ],
                                'comparendo' => [
                                    ['nivel' => 1, 'etiqueta' => 'Normal', 'desc' => 'Multa sin inmovilización del vehículo.'],
                                    ['nivel' => 2, 'etiqueta' => 'Media',  'desc' => 'Multa con inmovilización, sin afectación a la licencia de conducción.'],
                                    ['nivel' => 3, 'etiqueta' => 'Alta',   'desc' => 'Multa con inmovilización y/o afectación a la licencia de conducción.'],
                                ],
                            ];
                            ?>

                            <?php foreach ($tiposAMostrar as $tipo): ?>
                                <?php $cfg = $preguntasConfig[$tipo]; ?>
                                <div class="preop-card rcsst-question-card" style="border-left: 4px solid <?= $cfg['border_color'] ?>;">
                                    <div class="preop-card-header" style="background: <?= $cfg['header_color'] ?>;">
                                        <i class="fas <?= $cfg['icono'] ?>"></i>
                                        <?= $cfg['titulo'] ?>
                                    </div>
                                    <div class="preop-card-body">

                                        <!-- Descripción -->
                                        <p style="font-size: 13px; color: #888; margin: 0 0 14px 0;">
                                            <?= $cfg['desc'] ?>
                                        </p>

                                        <!-- Radio buttons Sí / No -->
                                        <div class="question-item">
                                            <span class="question-text">Respuesta:</span>
                                            <div class="question-options">
                                                <label class="radio-label rcsst-radio-<?= $tipo; ?>" data-valor="si" style="border-color: rgba(40,167,69,0.4); color: #1a7a30;">
                                                    <input type="radio" name="<?= $tipo; ?>" value="si">
                                                    <i class="fas fa-check-circle"></i> Sí
                                                </label>
                                                <label class="radio-label rcsst-radio-<?= $tipo; ?> rcsst-active" data-valor="no" style="border-color: rgba(220,53,69,0.4); color: #a01a24;">
                                                    <input type="radio" name="<?= $tipo; ?>" value="no" checked>
                                                    <i class="fas fa-times-circle"></i> No
                                                </label>
                                            </div>
                                        </div>

                                        <input type="hidden" id="rcsst_respuesta_<?= $tipo; ?>" value="0">

                                        <!-- Panel condicional: responde SÍ -->
                                        <div id="rcsst_panel_<?= $tipo; ?>" class="rcsst-panel-si" style="display: none;">

                                            <!-- ===== SELECTOR DE GRAVEDAD ===== -->
                                            <div class="rcsst-gravedad-section">
                                                <label class="rcsst-gravedad-label">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Nivel de gravedad
                                                </label>

                                                <!-- Radio buttons de gravedad -->
                                                <div class="rcsst-gravedad-radios" id="rcsst_gravedad_radios_<?= $tipo; ?>">
                                                    <?php foreach ($gravedadInfo[$tipo] as $g): ?>
                                                        <label class="rcsst-gravedad-radio rcsst-gravedad-radio--<?= $tipo; ?>" data-gravedad="<?= $g['nivel'] ?>">
                                                            <input type="radio" name="rcsst_gravedad_<?= $tipo; ?>" value="<?= $g['nivel'] ?>">
                                                            <span class="rcsst-gravedad-num"><?= $g['nivel'] ?></span>
                                                            <span class="rcsst-gravedad-tag"><?= $g['etiqueta'] ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                                <input type="hidden" id="rcsst_gravedad_<?= $tipo; ?>" value="">

                                                <!-- Botón para expandir tabla informativa de gravedad -->
                                                <button type="button" class="rcsst-info-toggle" id="rcsst_info_toggle_<?= $tipo; ?>" data-tipo="<?= $tipo; ?>">
                                                    <i class="fas fa-info-circle me-1"></i> ¿Qué significa cada nivel?
                                                    <i class="fas fa-chevron-down rcsst-info-chevron ms-1"></i>
                                                </button>

                                                <!-- Tabla informativa de gravedad (colapsable) -->
                                                <div class="rcsst-info-table-wrapper" id="rcsst_info_table_<?= $tipo; ?>" style="display: none;">
                                                    <table class="rcsst-info-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Nivel</th>
                                                                <th>Gravedad</th>
                                                                <th>Descripción</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($gravedadInfo[$tipo] as $g): ?>
                                                                <tr class="rcsst-info-row--<?= $g['etiqueta'] ?>">
                                                                    <td class="rcsst-info-num"><?= $g['nivel'] ?></td>
                                                                    <td class="rcsst-info-tag"><?= $g['etiqueta'] ?></td>
                                                                    <td class="rcsst-info-desc"><?= $g['desc'] ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- ===== GUÍA DE OBSERVACIÓN ===== -->
                                            <div class="rcsst-guia-obs" style="margin-top: 12px;">
                                                <button type="button" class="rcsst-info-toggle rcsst-guia-toggle" id="rcsst_guia_toggle_<?= $tipo; ?>" data-tipo="<?= $tipo; ?>">
                                                    <i class="fas fa-lightbulb me-1"></i> Guía: ¿Qué debo incluir en la descripción?
                                                    <i class="fas fa-chevron-down rcsst-info-chevron ms-1"></i>
                                                </button>
                                                <ul class="rcsst-guia-list" id="rcsst_guia_list_<?= $tipo; ?>" style="display: none;">
                                                    <?php foreach ($guiasObservacion[$tipo] as $item): ?>
                                                        <li>
                                                            <i class="fas fa-check-circle me-1"></i> <?= $item ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>

                                            <!-- Observación -->
                                            <div style="margin-top: 14px;">
                                                <label class="fw-bold mb-2" style="font-size: 13px; color: #E07000;" for="rcsst_obs_<?= $tipo; ?>">
                                                    <i class="fas fa-pen me-1"></i> Relato de lo sucedido
                                                </label>
                                                <textarea
                                                    id="rcsst_obs_<?= $tipo; ?>"
                                                    class="form-textarea"
                                                    rows="4"
                                                ></textarea>
                                            </div>

                                            <!-- Recordatorio de subir evidencia -->
                                            <div class="rcsst-evidence-reminder" style="margin-top: 12px;">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <strong>Importante:</strong> Suba evidencia del incidente (fotos, documentos, comparendos). Esto es obligatorio.
                                            </div>

                                            <!-- Subida de archivos -->
                                            <div class="photo-upload-container" style="margin-top: 10px;">
                                                <label class="photo-label">
                                                    <i class="fas fa-camera me-1"></i> Adjuntar evidencia (fotos o documentos)
                                                </label>

                                                <!-- Dropzone de archivos -->
                                                <div id="rcsst_dropzone_<?= $tipo; ?>" class="rcsst-upload-dropzone"
                                                     style="border: 2px dashed rgba(7,79,145,0.4); border-radius: 12px; padding: 16px; text-align: center; cursor: pointer; background: rgba(7,79,145,0.03); transition: all 0.2s ease; margin-bottom: 10px;">
                                                    <div style="font-size: 28px;">📸</div>
                                                    <div style="font-size: 13px; color: #074F91; font-weight: 600;">Haga clic para tomar foto o seleccionar archivo</div>
                                                    <div style="font-size: 11px; color: #888; margin-top: 2px;">JPG, PNG, PDF — máx. 10 MB por archivo</div>
                                                </div>
                                                <input type="file" id="rcsst_input_<?= $tipo; ?>"
                                                       class="rcsst-upload-input"
                                                       accept="image/*,.pdf" capture="environment" multiple
                                                       style="display: none;">

                                                <!-- Vista previa de archivos -->
                                                <div id="rcsst_preview_<?= $tipo; ?>" class="rcsst-preview-list"
                                                     style="display: flex; flex-wrap: wrap; gap: 6px;"></div>
                                            </div>
                                        </div>

                                        <!-- Sin novedad (visible cuando responde No — preseleccionado) -->
                                        <div id="rcsst_sin_novedad_<?= $tipo; ?>" class="rcsst-sin-novedad rcsst-visible"
                                             style="text-align: center; color: #999; font-size: 12px; font-style: italic; margin-top: 10px;">
                                            Sin novedad registrada
                                        </div>

                                    </div><!-- /preop-card-body -->
                                </div><!-- /preop-card -->
                            <?php endforeach; ?>

                        </div><!-- /preop-sections-wrapper -->

                        <!-- ===== BOTÓN ENVIAR ===== -->
                        <div class="text-center mt-4">
                            <button type="button" id="rcsst_btn_submit" class="btn btn-guardar">
                                <i class="fas fa-paper-plane me-2"></i> Enviar Reporte SST
                            </button>
                            <div style="font-size: 11px; color: #999; margin-top: 10px;">
                                <?php if ($modo === 'semanal'): ?>
                                    <i class="fas fa-info-circle me-1"></i> Al enviar, pasará al preoperacional del día
                                <?php else: ?>
                                    <i class="fas fa-info-circle me-1"></i> El reporte quedará registrado para seguimiento SST
                                <?php endif; ?>
                            </div>
                        </div>

                    </form>

                </div><!-- /card-body -->
            </div><!-- /main-form-card -->
        </div><!-- /preop-main-container -->
    </div><!-- /container-fluid -->

    <!-- JS del módulo -->
    <script src="<?= $appBasePath ?>/assets/js/reporte_conductor_sst.js?v=<?= filemtime(__DIR__ . '/../../assets/js/reporte_conductor_sst.js') ?>"></script>

</body>
</html>
