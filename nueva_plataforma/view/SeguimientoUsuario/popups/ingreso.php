<?php
// Variables esperadas:
// $idUsuario, $idSeguimiento, $fecha, $motivos (array), $zonas, $sedePredeterminada, $sedes (para manual)
// $motivoSeleccionado, $descripcion, $zonaSeleccionada, $pruebaSeleccionada, $usuario (opcional)
$fecha = $fecha ?? date('Y-m-d');
$motivoSeleccionado = $motivoSeleccionado ?? '';
$descripcion = $descripcion ?? '';
$zonaSeleccionada = $zonaSeleccionada ?? 0;
$pruebaSeleccionada = $pruebaSeleccionada ?? 'No aplica';
$horasSeleccionada = $horasSeleccionada ?? '';
?>
<form id="popupForm" method="post" enctype="multipart/form-data">
    <input type="hidden" name="accion" value="guardar_ingreso_popup">
    <input type="hidden" name="id_seguimiento" value="<?= $idSeguimiento ?? 0 ?>">

    <?php if (isset($idUsuario) && $idUsuario > 0): ?>
        <!-- Modo edición: operario fijo -->
        <input type="hidden" name="operario" value="<?= $idUsuario ?>">
        <div class="mb-3">
            <label class="form-label">Operario</label>
            <p class="form-control-plaintext"><?= htmlspecialchars($usuario['usu_nombre'] ?? '') ?></p>
        </div>
        <div class="mb-3">
            <label class="form-label">Sede</label>
            <p class="form-control-plaintext"><?= htmlspecialchars($sedeNombre) ?></p>
        </div>
    <?php else: ?>
        <!-- Modo manual: seleccionar sede y operario -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="ing_sede" class="form-label">Sede</label>
                <select name="sede" id="ing_sede" class="form-select" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($sedes as $s): ?>
                        <option value="<?= $s['idsedes'] ?>" <?= $s['idsedes'] == $sedePredeterminada ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['sed_nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="ing_operario" class="form-label">Operario</label>
                <select name="operario" id="ing_operario" class="form-select" required>
                    <option value="">Primero seleccione sede</option>
                </select>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="fecha" class="form-label">Fecha de ingreso</label>
            <input type="date" name="fecha" id="fecha" class="form-control" value="<?= $fecha ?>" required>
        </div>
        <div class="col-md-6">
            <label for="motivo" class="form-label">Motivo</label>
            <select name="motivo" id="motivo" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($motivos as $key => $value): ?>
                    <option value="<?= $key ?>" <?= $key == $motivoSeleccionado ? 'selected' : '' ?>><?= $value ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="zona" class="form-label">Zona de trabajo</label>
            <select name="zona" id="ing_zona" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach ($zonas as $z): ?>
                    <option value="<?= $z['idzonatrabajo'] ?>" <?= $z['idzonatrabajo'] == $zonaSeleccionada ? 'selected' : '' ?>>
                        <?= htmlspecialchars($z['zon_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="prueba" class="form-label">Prueba de alcohol</label>
            <select name="prueba" id="prueba" class="form-select" required>
                <option value="No aplica" <?= $pruebaSeleccionada == 'No aplica' ? 'selected' : '' ?>>No aplica</option>
                <option value="Negativo" <?= $pruebaSeleccionada == 'Negativo' ? 'selected' : '' ?>>Negativo</option>
                <option value="Positivo" <?= $pruebaSeleccionada == 'Positivo' ? 'selected' : '' ?>>Positivo</option>
            </select>
        </div>
    </div>

    <div class="row mb-3 d-none" id="horas_container">
        <div class="col-md-6">
            <label for="horas" class="form-label">Horas</label>
            <select name="horas" id="horas" class="form-select">
                <option value="">Seleccione horas</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= ($horasSeleccionada == $i) ? 'selected' : '' ?>><?= $i ?> hora(s)</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-6">
            <!-- Espacio vacío para alineación -->
        </div>
    </div>

    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" id="descripcion" class="form-control"
            rows="2"><?= htmlspecialchars($descripcion) ?></textarea>
    </div>

    <div class="mb-3">
        <label for="imagen" class="form-label">Imagen (opcional)</label>
        <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
</form>

<script>
// Clean version without console logs
(function($) {
    function initHorasField() {
        try {
            const popupForm = document.getElementById('popupForm');
            if (!popupForm) return;

            const motivoSelect = popupForm.querySelector('#motivo');
            const horasContainer = popupForm.querySelector('#horas_container');
            const horasSelect = popupForm.querySelector('#horas');

            if (!motivoSelect || !horasContainer || !horasSelect) return;

            // Function to get motivo value (compatible with Select2)
            function getMotivoValue() {
                let resultado = '';
                try {
                    if ($ && motivoSelect) {
                        const $motivo = $(motivoSelect);
                        const hasSelect2 = $motivo.data('select2');
                        if (hasSelect2) {
                            resultado = $motivo.val() || '';
                        } else {
                            resultado = motivoSelect.value || '';
                        }
                    } else {
                        resultado = motivoSelect.value || '';
                    }
                } catch (err) {
                    resultado = '';
                }
                return resultado;
            }

            // Toggle horas field based on motivo value
            const toggleHorasFieldImproved = function() {
                try {
                    const motivoValue = getMotivoValue();
                    if (motivoValue === 'IngresoHoras') {
                        horasContainer.classList.remove('d-none');
                        horasSelect.required = true;
                    } else {
                        horasContainer.classList.add('d-none');
                        horasSelect.required = false;
                        horasSelect.value = '';
                    }
                } catch (err) {
                    // Silent fail
                }
            };

            // Execute on load
            toggleHorasFieldImproved();

            // Set up events based on jQuery and Select2 availability
            if ($) {
                const $motivo = $(motivoSelect);
                const $form = $('#popupForm');
                const hasSelect2 = $motivo.data('select2');

                if (hasSelect2) {
                    $motivo.off('select2:select.horas select2:unselect.horas change.horas');
                    $motivo.on('select2:select.horas', toggleHorasFieldImproved);
                    $motivo.on('select2:unselect.horas', toggleHorasFieldImproved);
                    $motivo.on('change.horas', toggleHorasFieldImproved);
                } else {
                    if ($form.length) {
                        $form.off('change.horas', '#motivo');
                        $form.on('change.horas', '#motivo', toggleHorasFieldImproved);
                    }
                }
            } else {
                // Vanilla JS
                motivoSelect.removeEventListener('change', toggleHorasFieldImproved);
                motivoSelect.addEventListener('change', toggleHorasFieldImproved);
                motivoSelect.onchange = toggleHorasFieldImproved;

                const form = document.getElementById('popupForm');
                if (form) {
                    form.removeEventListener('change', toggleHorasFieldImproved);
                    form.addEventListener('change', function(event) {
                        if (event.target && event.target.id === 'motivo') {
                            toggleHorasFieldImproved();
                        }
                    });
                }
            }

            // Backup check interval (safety net)
            let backupCheckInterval = null;
            function startBackupCheck() {
                if (backupCheckInterval) return;
                backupCheckInterval = setInterval(function() {
                    if (!motivoSelect || !horasContainer || !horasSelect) return;
                    const motivoValue = getMotivoValue();
                    const shouldShow = motivoValue === 'IngresoHoras';
                    const isHidden = horasContainer.classList.contains('d-none');
                    if (shouldShow && isHidden) {
                        horasContainer.classList.remove('d-none');
                        horasSelect.required = true;
                    } else if (!shouldShow && !isHidden) {
                        horasContainer.classList.add('d-none');
                        horasSelect.required = false;
                        horasSelect.value = '';
                    }
                }, 1000);
            }

            // Cleanup backup check when modal closes
            function cleanupBackupCheck() {
                if (backupCheckInterval) {
                    clearInterval(backupCheckInterval);
                    backupCheckInterval = null;
                }
            }

            // Find modal and attach close event
            const form = document.getElementById('popupForm');
            if (form) {
                const modal = form.closest('.modal');
                if (modal) {
                    if ($ && $.fn.modal) {
                        $(modal).on('hidden.bs.modal', cleanupBackupCheck);
                    } else {
                        modal.addEventListener('hidden.bs.modal', cleanupBackupCheck);
                    }
                }
            }

            // Start backup check after a delay
            setTimeout(startBackupCheck, 2000);
        } catch (error) {
            // Silent error
        }
    }

    // Wait for DOM ready
    if (typeof jQuery !== 'undefined') {
        $(function() {
            initHorasField();
        });
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initHorasField();
            });
        } else {
            setTimeout(initHorasField, 10);
        }
    }
})(typeof jQuery !== 'undefined' ? jQuery : null);
</script>