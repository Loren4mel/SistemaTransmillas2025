<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reporte Conductor SST</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Estilos propios -->
    <link rel="stylesheet" href="<?= $appBasePath ?>/assets/css/reporte_conductor_sst.css?<?= filemtime(__DIR__ . '/../../assets/css/reporte_conductor_sst.css') ?>">
</head>
<body>

<div class="rcsst-container">

    <!-- ===== HEADER ===== -->
    <div class="rcsst-header">
        <div class="rcsst-header-label">Reporte Conductor SST</div>
        <div class="rcsst-header-title">
            <?php if ($modo === 'semanal'): ?>
                Test de Fin de Semana
            <?php else: ?>
                Reporte de Incidente
            <?php endif; ?>
        </div>
        <div class="rcsst-header-week">
            Semana <?php echo date('d', strtotime($semana['inicio'])); ?> —
            <?php echo date('d M', strtotime($semana['fin'])); ?> ·
            <?php echo $modo === 'semanal' ? 'Reporte semanal obligatorio' : 'Reporte espontáneo'; ?>
        </div>
    </div>

    <!-- ===== MAPA DE UBICACIÓN (OBLIGATORIO) ===== -->
    <div class="rcsst-ubicacion-section">
        <div class="rcsst-ubicacion-header">
            <span>📍</span>
            <span>Ubicación actual</span>
            <span class="rcsst-badge-obligatorio">Obligatorio</span>
        </div>
        <div class="rcsst-mapa-container">
            <div id="rcsst_mapa_container" class="rcsst-mapa">
                <div class="rcsst-mapa-status rcsst-mapa-status--cargando">
                    <i class="fas fa-spinner fa-spin"></i> Obteniendo ubicación...
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PREGUNTAS ===== -->
    <?php
    $preguntas = [
        'accidente' => [
            'emoji'   => '🚨',
            'titulo'  => '¿Sufrió algún accidente durante la semana?',
            'desc'    => 'Reporte cualquier incidente de tránsito, así no haya tenido lesiones.',
            'placeholder_obs' => 'Describa detalladamente: fecha, hora, lugar, cómo ocurrió, personas involucradas, daños materiales, lesiones, intervención de autoridades...',
        ],
        'comparendo' => [
            'emoji'   => '🚔',
            'titulo'  => '¿Tuvo algún comparendo o llamado por parte de la Policía de Tránsito durante la semana?',
            'desc'    => 'Comparendos, multas, retenes o cualquier interacción con autoridades de tránsito.',
            'placeholder_obs' => 'Describa detalladamente: fecha, hora, lugar, motivo del comparendo, autoridad que lo expidió, número de comparendo (si lo tiene)...',
        ],
    ];
    ?>

    <?php foreach ($tiposAMostrar as $tipo): ?>
        <?php $p = $preguntas[$tipo]; ?>
        <!-- Question card -->
        <div class="rcsst-pregunta-card">
            <div class="rcsst-pregunta-header">
                <span class="rcsst-pregunta-emoji"><?php echo $p['emoji']; ?></span>
                <div>
                    <div class="rcsst-pregunta-titulo"><?php echo $p['titulo']; ?></div>
                    <div class="rcsst-pregunta-desc"><?php echo $p['desc']; ?></div>
                </div>
            </div>

            <!-- Radio toggles -->
            <div class="rcsst-radios">
                <label class="rcsst-radio-label rcsst-radio-label--si rcsst-radio-<?php echo $tipo; ?>" data-valor="si">
                    <input type="radio" name="<?php echo $tipo; ?>" value="si"> ✅ Sí
                </label>
                <label class="rcsst-radio-label rcsst-radio-label--no rcsst-radio-<?php echo $tipo; ?> rcsst-active" data-valor="no">
                    <input type="radio" name="<?php echo $tipo; ?>" value="no" checked> ✗ No
                </label>
            </div>
            <input type="hidden" id="rcsst_respuesta_<?php echo $tipo; ?>" value="0">

            <!-- Panel: Sí -->
            <div id="rcsst_panel_<?php echo $tipo; ?>" class="rcsst-panel-si">
                <div class="rcsst-panel-required-label">⚠️ Campos requeridos al responder SÍ</div>

                <label class="rcsst-field-label" for="rcsst_obs_<?php echo $tipo; ?>">Relato de lo sucedido</label>
                <textarea id="rcsst_obs_<?php echo $tipo; ?>" class="rcsst-textarea"
                    placeholder="<?php echo $p['placeholder_obs']; ?>"></textarea>

                <div class="rcsst-upload-section">
                    <label class="rcsst-field-label">Adjuntar evidencia</label>

                    <!-- FUTURE VIDEO UPLOAD (commented out):
                    <div id="rcsst_dropzone_video_<?php echo $tipo; ?>" class="rcsst-upload-dropzone rcsst-upload-dropzone--video" style="margin-top: 8px;">
                        <div class="rcsst-upload-icon">🎥</div>
                        <div class="rcsst-upload-text">Grabar o seleccionar video</div>
                        <div class="rcsst-upload-hint">MP4, WebM — máx. 10 MB</div>
                    </div>
                    <input type="file" id="rcsst_input_video_<?php echo $tipo; ?>" class="rcsst-upload-input"
                           accept="video/*" capture="environment" multiple>
                    -->

                    <div id="rcsst_dropzone_<?php echo $tipo; ?>" class="rcsst-upload-dropzone">
                        <div class="rcsst-upload-icon">📸</div>
                        <div class="rcsst-upload-text">Tomar foto o seleccionar archivo</div>
                        <div class="rcsst-upload-hint">JPG, PNG, PDF — máx. 10 MB por archivo</div>
                    </div>
                    <input type="file" id="rcsst_input_<?php echo $tipo; ?>"
                           class="rcsst-upload-input"
                           accept="image/*,.pdf" capture="environment" multiple>

                    <div id="rcsst_preview_<?php echo $tipo; ?>" class="rcsst-preview-list"></div>
                </div>
            </div>

            <!-- Sin novedad -->
            <div id="rcsst_sin_novedad_<?php echo $tipo; ?>" class="rcsst-sin-novedad rcsst-visible">
                Sin novedad registrada
            </div>
        </div>
    <?php endforeach; ?>

    <!-- ===== FOOTER / SUBMIT ===== -->
    <div class="rcsst-footer">
        <input type="hidden" id="rcsst_tipo_evento" value="<?php echo $modo; ?>">
        <button id="rcsst_btn_submit" class="rcsst-btn-submit">
            Enviar Reporte SST →
        </button>
        <div class="rcsst-footer-hint">
            <?php if ($modo === 'semanal'): ?>
                Al enviar, pasará al preoperacional del día
            <?php else: ?>
                El reporte quedará registrado para seguimiento SST
            <?php endif; ?>
        </div>
    </div>

</div><!-- /rcsst-container -->

<!-- Dependencias JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $appBasePath ?>/assets/js/reporte_conductor_sst.js?v=<?= filemtime(__DIR__ . '/../../assets/js/reporte_conductor_sst.js') ?>"></script>

</body>
</html>
