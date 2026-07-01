<?php
/**
 * AdminCuestionarios - Vista principal
 *
 * Variables disponibles:
 *   $modulo  - 'preop' | 'sst' (desde GET ?modo=)
 */
$moduloActual = $modulo ?? 'preop';
$appPath = dirname(dirname($_SERVER['SCRIPT_NAME']));
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administracion de Cuestionarios</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="<?= $appPath ?>/assets/css/admin_cuestionarios.css">
</head>
<body>
<div class="container-fluid py-3">
    <div class="admin-cuestionarios-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Administracion de Cuestionarios</h4>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="cuestionariosTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $moduloActual === 'preop' ? 'active' : '' ?>"
                        id="tab-preop" data-bs-toggle="tab"
                        data-bs-target="#pane-preop" type="button" role="tab"
                        data-modulo="preop">
                    <i class="fas fa-truck me-1"></i> Preoperacional
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $moduloActual === 'sst' ? 'active' : '' ?>"
                        id="tab-sst" data-bs-toggle="tab"
                        data-bs-target="#pane-sst" type="button" role="tab"
                        data-modulo="sst">
                    <i class="fas fa-shield-alt me-1"></i> SST
                </button>
            </li>
        </ul>

        <div class="tab-content" id="cuestionariosTabsContent">
            <?php foreach (['preop' => 'Preoperacional', 'sst' => 'SST'] as $slug => $label): ?>
            <div class="tab-pane fade <?= $moduloActual === $slug ? 'show active' : '' ?>"
                 id="pane-<?= $slug ?>" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 text-muted">Plantillas de <?= $label ?></h5>
                    <button class="btn btn-sm btn-success btn-nueva-plantilla"
                            data-modulo="<?= $slug ?>">
                        <i class="fas fa-plus"></i> Nueva Plantilla
                    </button>
                </div>
                <div id="plantillas-list-<?= $slug ?>" class="plantillas-list">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contenedores de popups -->
    <div id="popup-plantilla-container"></div>
    <div id="popup-version-container"></div>
    <div id="popup-seccion-container"></div>
    <div id="popup-pregunta-container"></div>
</div>

<script src="<?= $appPath ?>/assets/js/admin_cuestionarios.js"></script>
</body>
</html>
