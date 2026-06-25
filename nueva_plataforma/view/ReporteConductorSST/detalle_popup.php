<?php
/**
 * Popup: Detalle de Reporte SST con panel de validación
 *
 * Se renderiza vía AJAX. Las variables se pasan desde el controller
 * o se reciben como JSON y se renderizan en el frontend.
 *
 * Estructura esperada del array $reporte:
 *   id, fecha, tipo, tipo_evento, respuesta, observacion, ubicacion,
 *   gravedad, estado, creado_en,
 *   conductor_nombre, conductor_cedula, veh_placa,
 *   validador_nombre, comentario_validador, fecha_validacion,
 *   archivos[] => [iddocumentos, doc_nombre, doc_ruta, doc_fecha]
 *
 * Variables de control:
 *   $puedeValidar (bool) — true si el usuario actual es admin (rol 1 o 12)
 *   $usuarioNombre (string) — nombre del usuario actual
 */
?>
<div class="sst-detalle-container">

    <!-- ====== CABECERA: Tipo + Estado ====== -->
    <div class="sst-detalle-header">
        <div class="sst-detalle-tipo">
            <?php if (($reporte['tipo'] ?? '') === 'accidente'): ?>
                <span class="sst-badge sst-badge--accidente">🚨 Accidente</span>
            <?php else: ?>
                <span class="sst-badge sst-badge--comparendo">📋 Comparendo</span>
            <?php endif; ?>
            <span class="sst-detalle-fecha">
                <i class="far fa-calendar-alt"></i>
                <?= htmlspecialchars(date('d/m/Y', strtotime($reporte['fecha'] ?? 'now'))) ?>
            </span>
        </div>
        <div class="sst-detalle-estado">
            <?php
            $estado = $reporte['estado'] ?? 'pendiente';
            $estadoClase = [
                'pendiente' => 'sst-estado--pendiente',
                'revisado'  => 'sst-estado--revisado',
                'resuelto'  => 'sst-estado--resuelto',
            ][$estado] ?? 'sst-estado--pendiente';
            $estadoLabel = [
                'pendiente' => '⏳ Pendiente',
                'revisado'  => '✅ Revisado',
                'resuelto'  => '✔️ Resuelto',
            ][$estado] ?? 'Pendiente';
            ?>
            <span class="sst-estado <?= $estadoClase ?>"><?= $estadoLabel ?></span>
        </div>
    </div>

    <!-- ====== DATOS DEL CONDUCTOR ====== -->
    <div class="sst-detalle-section">
        <h4 class="sst-section-title"><i class="fas fa-user"></i> Conductor</h4>
        <div class="sst-info-grid">
            <div class="sst-info-item">
                <span class="sst-info-label">Nombre</span>
                <span class="sst-info-value"><?= htmlspecialchars($reporte['conductor_nombre'] ?? '—') ?></span>
            </div>
            <div class="sst-info-item">
                <span class="sst-info-label">Cédula</span>
                <span class="sst-info-value"><?= htmlspecialchars($reporte['conductor_cedula'] ?? '—') ?></span>
            </div>
            <div class="sst-info-item">
                <span class="sst-info-label">Vehículo</span>
                <span class="sst-info-value sst-placa-badge"><?= htmlspecialchars($reporte['veh_placa'] ?? '—') ?></span>
            </div>
        </div>
    </div>

    <!-- ====== DATOS DEL REPORTE ====== -->
    <div class="sst-detalle-section">
        <h4 class="sst-section-title"><i class="fas fa-clipboard-list"></i> Detalle del Reporte</h4>
        <div class="sst-info-grid sst-info-grid--4col">
            <div class="sst-info-item">
                <span class="sst-info-label">Tipo de Evento</span>
                <span class="sst-info-value">
                    <?= ($reporte['tipo_evento'] ?? '') === 'semanal' ? '📅 Semanal' : '⚡ En el momento' ?>
                </span>
            </div>
            <div class="sst-info-item">
                <span class="sst-info-label">¿Ocurrió?</span>
                <span class="sst-info-value">
                    <?php if (($reporte['respuesta'] ?? 0) == 1): ?>
                        <span style="color:#c62828;font-weight:700;">⚠️ Sí</span>
                    <?php else: ?>
                        <span style="color:#2e7d32;">✅ No</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="sst-info-item">
                <span class="sst-info-label">Gravedad</span>
                <span class="sst-info-value">
                    <?php
                    $tipoReporte = $reporte['tipo'] ?? '';
                    $gravConductor = $reporte['gravedad'] ?? null;
                    $gravValidador = $reporte['gravedad_validacion'] ?? null;

                    // Labels simplificados (conductor) por tipo
                    $gravSimpleAcc = [1 => '1 - Baja', 2 => '2 - Alta'];
                    $gravSimpleComp = [1 => '1 - Normal', 2 => '2 - Media', 3 => '3 - Alta'];
                    $gravColorsSimpleAcc = [1 => '#1e7f4f', 2 => '#b42318'];
                    $gravColorsSimpleComp = [1 => '#1e7f4f', 2 => '#b54708', 3 => '#b42318'];

                    // Labels originales (validador) por tipo
                    $gravValAcc = [1 => '1 - Leve', 2 => '2 - Moderado', 3 => '3 - Grave', 4 => '4 - Crítico'];
                    $gravValComp = [1 => '1 - Normal', 2 => '2 - Media', 3 => '3 - Alta'];
                    $gravColorsValAcc = [1 => '#1e7f4f', 2 => '#b54708', 3 => '#b42318', 4 => '#7f1d1d'];
                    $gravColorsValComp = [1 => '#1e7f4f', 2 => '#b54708', 3 => '#b42318'];

                    if ($gravValidador !== null && $gravValidador !== '') {
                        // Mostrar gravedad del validador (escala original)
                        $labels = ($tipoReporte === 'accidente') ? $gravValAcc : $gravValComp;
                        $colors = ($tipoReporte === 'accidente') ? $gravColorsValAcc : $gravColorsValComp;
                        $texto = $labels[$gravValidador] ?? $gravValidador;
                        $color = $colors[$gravValidador] ?? '#888';
                        $esEstimada = false;
                    } elseif ($gravConductor !== null && $gravConductor !== '') {
                        // Mostrar gravedad del conductor (escala simplificada)
                        $labels = ($tipoReporte === 'accidente') ? $gravSimpleAcc : $gravSimpleComp;
                        $colors = ($tipoReporte === 'accidente') ? $gravColorsSimpleAcc : $gravColorsSimpleComp;
                        $texto = $labels[$gravConductor] ?? $gravConductor;
                        $color = $colors[$gravConductor] ?? '#888';
                        $esEstimada = true;
                    } else {
                        $texto = '—';
                        $color = '#888';
                        $esEstimada = false;
                    }
                    ?>
                    <span style="color:<?= $color ?>;font-weight:600;"><?= htmlspecialchars($texto) ?></span>
                    <?php if ($esEstimada): ?>
                        <br><small style="color:#888;font-size:10px;"><i class="fas fa-info-circle"></i> Gravedad estimada del conductor</small>
                    <?php elseif ($gravValidador !== null && $gravValidador !== ''): ?>
                        <br><small style="color:#1e7f4f;font-size:10px;"><i class="fas fa-check-circle"></i> Gravedad verificada por validador</small>
                    <?php endif; ?>
                </span>
            </div>
            <div class="sst-info-item">
                <span class="sst-info-label">Ubicación GPS</span>
                <span class="sst-info-value" style="font-family:monospace;font-size:12px;">
                    <?= htmlspecialchars($reporte['ubicacion'] ?? '—') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ====== OBSERVACIÓN DEL CONDUCTOR ====== -->
    <?php if (!empty(trim($reporte['observacion'] ?? ''))): ?>
    <div class="sst-detalle-section">
        <h4 class="sst-section-title"><i class="fas fa-comment-alt"></i> Observación del Conductor</h4>
        <div class="sst-observacion-box">
            <?= nl2br(htmlspecialchars($reporte['observacion'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== ARCHIVOS DE EVIDENCIA ====== -->
    <?php $archivos = $reporte['archivos'] ?? []; ?>
    <div class="sst-detalle-section">
        <h4 class="sst-section-title">
            <i class="fas fa-paperclip"></i> Evidencia
            (<?= count($archivos) ?> archivo<?= count($archivos) !== 1 ? 's' : '' ?>)
        </h4>
        <?php if (empty($archivos)): ?>
            <p class="text-muted small">Sin archivos adjuntos.</p>
        <?php else: ?>
            <div class="sst-evidencia-grid">
                <?php foreach ($archivos as $arch):
                    $ruta = $arch['doc_ruta'] ?? '';
                    $nombre = $arch['doc_nombre'] ?? 'Archivo';
                    $esImagen = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $ruta);
                    $esPDF = preg_match('/\.pdf$/i', $ruta);
                    $urlArchivo = $ruta;
                ?>
                    <div class="sst-evidencia-item">
                        <?php if ($esImagen): ?>
                            <a href="<?= htmlspecialchars($urlArchivo) ?>" target="_blank"
                               class="sst-evidencia-link" title="<?= htmlspecialchars($nombre) ?>">
                                <img src="<?= htmlspecialchars($urlArchivo) ?>"
                                     alt="<?= htmlspecialchars($nombre) ?>"
                                     class="sst-evidencia-thumb" loading="lazy">
                            </a>
                        <?php elseif ($esPDF): ?>
                            <a href="<?= htmlspecialchars($urlArchivo) ?>" target="_blank"
                               class="sst-evidencia-link sst-evidencia-link--pdf"
                               title="<?= htmlspecialchars($nombre) ?>">
                                <div class="sst-evidencia-pdf-icon">📄</div>
                                <span class="sst-evidencia-pdf-label">PDF</span>
                            </a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($urlArchivo) ?>" target="_blank"
                               class="sst-evidencia-link sst-evidencia-link--generic"
                               title="<?= htmlspecialchars($nombre) ?>">
                                <div class="sst-evidencia-generic-icon">📎</div>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ====== FIRMA DEL CONDUCTOR ====== -->
    <?php $firma = $reporte['firma'] ?? null; ?>
    <?php if ($firma && !empty($firma['doc_ruta'])): ?>
    <div class="sst-detalle-section">
        <h4 class="sst-section-title"><i class="fas fa-signature"></i> Firma del Conductor</h4>
        <div style="text-align: center; padding: 12px;">
            <img src="<?= htmlspecialchars($firma['doc_ruta']) ?>"
                 alt="Firma del conductor"
                 style="max-width: 400px; max-height: 200px; border: 2px solid rgba(7,79,145,0.5); border-radius: 8px; background-color: #fff;">
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== HISTORIAL DE VALIDACIÓN ====== -->
    <?php if (!empty($reporte['comentario_validador']) || !empty($reporte['validador_nombre'])): ?>
    <div class="sst-detalle-section">
        <h4 class="sst-section-title"><i class="fas fa-history"></i> Historial de Validación</h4>
        <div class="sst-validacion-historial">
            <div class="sst-validacion-entry">
                <div class="sst-validacion-header">
                    <span class="sst-validacion-user">
                        <i class="fas fa-user-check"></i>
                        <?= htmlspecialchars($reporte['validador_nombre'] ?? 'Sistema') ?>
                    </span>
                    <?php if (!empty($reporte['fecha_validacion'])): ?>
                    <span class="sst-validacion-fecha">
                        <i class="far fa-clock"></i>
                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($reporte['fecha_validacion']))) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty(trim($reporte['comentario_validador'] ?? ''))): ?>
                <div class="sst-validacion-comentario">
                    <?= nl2br(htmlspecialchars($reporte['comentario_validador'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== PANEL DE VALIDACIÓN (solo admin) ====== -->
    <?php if ($puedeValidar): ?>
    <div class="sst-detalle-section sst-validacion-panel">
        <h4 class="sst-section-title"><i class="fas fa-check-double"></i> Validar Reporte</h4>

        <div class="sst-validacion-form">
            <!-- Selector de estado -->
            <div class="sst-form-group">
                <label class="sst-form-label">Cambiar estado a:</label>
                <select id="sstValidarEstado" class="sst-form-select">
                    <option value="pendiente" <?= ($estado === 'pendiente') ? 'selected' : '' ?>>⏳ Pendiente</option>
                    <option value="revisado" <?= ($estado === 'revisado') ? 'selected' : '' ?>>✅ Revisado</option>
                    <option value="resuelto" <?= ($estado === 'resuelto') ? 'selected' : '' ?>>✔️ Resuelto</option>
                </select>
            </div>

            <!-- Selector de gravedad (escala original para validador) -->
            <div class="sst-form-group">
                <label class="sst-form-label">
                    Nivel de gravedad (según validador):
                    <small class="text-muted">(Escala original detallada)</small>
                </label>
                <?php
                $tipoR = $reporte['tipo'] ?? '';
                $gravValActual = $reporte['gravedad_validacion'] ?? $reporte['gravedad'] ?? 0;
                if ($tipoR === 'accidente'):
                    $niveles = [
                        1 => '1 - Leve: Daños materiales, sin afectación a persona',
                        2 => '2 - Moderado: Lesiones leves sin hospitalización',
                        3 => '3 - Grave: Lesiones con atención médica u hospitalización',
                        4 => '4 - Crítico: Víctimas fatales o lesiones permanentes graves',
                    ];
                else:
                    $niveles = [
                        1 => '1 - Normal: Multa sin inmovilización',
                        2 => '2 - Media: Multa con inmovilización, sin afectación a licencia',
                        3 => '3 - Alta: Multa con inmovilización y/o afectación a licencia',
                    ];
                endif;
                ?>
                <select id="sstValidarGravedad" class="sst-form-select">
                    <option value="">— Sin cambios —</option>
                    <?php foreach ($niveles as $v => $label): ?>
                        <option value="<?= $v ?>" <?= ((int)$gravValActual === $v) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Comentario -->
            <div class="sst-form-group">
                <label class="sst-form-label">
                    Comentario de validación:
                    <small class="text-muted">(Tu nombre <strong><?= htmlspecialchars($usuarioNombre) ?></strong> se añadirá automáticamente)</small>
                </label>
                <textarea id="sstValidarComentario" class="sst-form-textarea" rows="3"
                    placeholder="Describa el resultado de la revisión..."></textarea>
            </div>

            <button type="button" id="sstValidarBtn" class="sst-btn-validar">
                <i class="fas fa-save"></i> Guardar Validación
            </button>
        </div>
    </div>
    <?php endif; ?>

</div>
