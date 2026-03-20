<?php
// ../view/Verguia/index.php
// Variables esperadas desde el controlador VerguiaController.php:
// $guia, $totales, $tipoServicio, $pagoEn, $tipoPagoTexto, $textoTP, $colorTP, $firmas, $error
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>
        Guía Digital
        <?= isset($guia['ser_consecutivo']) ? ' - '.$guia['ser_consecutivo'] : '' ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Fuente limpia (opcional, no rompe nada si no carga) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --tm-azul: #013A86;
            --tm-azul-claro: #0A5EC4;
            --tm-rojo: #D71920;
            --tm-bg: #F4F7FB;
        }

        body {
            background: var(--tm-bg);
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        /* Cabecera corporativa */
        .guia-header {
            background: linear-gradient(135deg, var(--tm-azul), var(--tm-azul-claro));
            color: #fff;
            border-bottom: 3px solid var(--tm-rojo);
        }
        .guia-header-logo {
            height: 42px;
        }
        .guia-header-title {
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-size: .85rem;
        }

        /* Contenedor principal */
        .ticket-wrapper {
            max-width: 960px;
            margin: 20px auto;
            padding: 0 10px;
        }

        .card-guia {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.03);
            overflow: hidden;
        }

        /* Título de bloque */
        .section-title {
            font-weight: 600;
            color: var(--tm-azul);
            border-left: 4px solid var(--tm-rojo);
            padding-left: 10px;
            margin-bottom: 12px;
            font-size: .95rem;
        }

        .small-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6C7A91;
        }

        .value-text {
            font-size: .98rem;
            font-weight: 500;
            color: #1A1A1A;
        }

        .divider-dashed {
            border-top: 1px dashed #dee2e6;
            margin: 1rem 0;
        }

        .badge-soft {
            background: rgba(255,255,255,.15);
            border-radius: 999px;
            padding: .2rem .75rem;
            font-size: .7rem;
        }

        .signature-box {
            border: 1px dashed #ced4da;
            border-radius: 12px;
            padding: .75rem;
            background: #fff;
            min-height: 110px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .chip-estado {
            font-size: .8rem;
            border-radius: 999px;
            padding: .2rem .65rem;
            background: #EFF3FA;
            color: #2C3E50;
        }

        .resumen-valor {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--tm-azul);
        }

        @media (max-width: 576px) {
            .guia-header-logo {
                height: 34px;
            }
            .ticket-wrapper {
                margin-top: 12px;
            }
        }

        @media print {
            body {
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
            .ticket-wrapper {
                margin: 0;
                max-width: 100%;
            }
            .card-guia {
                box-shadow: none;
                border: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

<!-- CABECERA -->
<div class="guia-header no-print">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center py-2 px-2 px-md-3">
            <div class="d-flex align-items-center gap-2">
                <!-- Ajusta la ruta del logo según tu estructura -->
                <img src="../../img/logofactura.png" alt="Transmillas" class="guia-header-logo">
                <div class="d-flex flex-column">
                    <span class="guia-header-title">Guía digital</span>
                    <?php if (!empty($guia['ser_consecutivo']) || !empty($guia['ser_guiare'])): ?>
                        <small class="text-white-50">
                            #<?= htmlspecialchars($guia['ser_consecutivo'] ?? $guia['ser_guiare']) ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<div class="ticket-wrapper">
    <?php if (isset($guia['ser_estado']) && (int)$guia['ser_estado'] === 100): ?>
<style>
.sello-cancelada {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-25deg);
    font-weight: 800;
    color: rgba(220, 0, 0, 0.20);
    text-transform: uppercase;
    border: 0.8vw solid rgba(220, 0, 0, 0.25);
    padding: 1vw 4vw;
    border-radius: 1.5vw;
    z-index: 9999;
    pointer-events: none;
    user-select: none;
    white-space: nowrap;

    /* Tamaño de fuente responsive */
    font-size: 12vw;
}

/* Tablets */
@media (min-width: 576px) {
    .sello-cancelada {
        font-size: 10vw;
        border-width: 0.6vw;
        padding: 1vw 3vw;
    }
}

/* Escritorio */
@media (min-width: 992px) {
    .sello-cancelada {
        font-size: 7vw;
        border-width: 0.4vw;
        padding: 0.7vw 2vw;
    }
}

/* Impresión */
@media print {
    .sello-cancelada {
        position: absolute;
        font-size: 14vw;
        opacity: 0.25;
        border-width: 0.5vw;
    }
}
</style>

<div class="sello-cancelada">Guía Cancelada</div>
<?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-3">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif (!isset($guia) || !$guia): ?>
        <div class="alert alert-warning mt-3">
            No se encontraron datos para la guía solicitada.
        </div>
    <?php else: ?>

    <div class="card-guia mt-2" id="ticket-guia">
        <div class="p-3 p-md-4">

            <!-- Encabezado interno de la guía -->
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <div>
                    <div class="small-label mb-1">Remesa / Guía</div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h4 class="mb-0">
                            #<?= htmlspecialchars($guia['ser_consecutivo'] ?? $guia['ser_guiare']) ?>
                        </h4>
                        <span class="chip-estado">
                            <?= htmlspecialchars($guia['ser_descripcion'] ?? 'En tránsito') ?>
                        </span>
                    </div>
                    <div class="small text-muted mt-1">
                        Registrada:
                        <?= htmlspecialchars(date('Y-m-d H:i', strtotime($guia['ser_fecharegistro'] ?? 'now'))) ?>
                    </div>
                    <div class="small text-muted">
                        Origen:
                        <?= htmlspecialchars($guia['ciudad_origen'] ?? 'N/D') ?>
                        · Destino:
                        <?= htmlspecialchars($guia['ciudad_destino'] ?? 'N/D') ?>
                    </div>
                </div>
                <div class="text-md-end">
                    <div class="small-label mb-1">Servicio</div>
                    <div class="value-text">
                        <?= htmlspecialchars($tipoServicio['nombre'] ?? '') ?>
                        <?php if (!empty($tipoServicio['codigo']) && $tipoServicio['codigo'] == '1000'): ?>
                            <span class="badge bg-warning text-dark ms-1">A convenir</span>
                        <?php endif; ?>/ Entrega de 24-48 horas
                    </div>
                    <div class="small text-muted mt-1">
                        Tipo de pago:
                        <span class="fw-semibold"><?= htmlspecialchars($tipoPagoTexto ?? '') ?></span>
                    </div>
                </div>
            </div>

            <hr class="divider-dashed">

            <!-- Remitente / Destinatario -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="section-title">Remitente</div>
                    <div class="mb-1">
                        <div class="small-label">Nombre</div>
                        <div class="value-text"><?= htmlspecialchars($guia['cli_nombre']) ?></div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Documento</div>
                        <div class="value-text"><?= htmlspecialchars($guia['cli_iddocumento'] ?? 'N/D') ?></div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Teléfono</div>
                        
                        <div class="value-text">**********</div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Dirección</div>
                        
                        <div class="value-text">
                            <?= htmlspecialchars(str_replace('&', ' ', $guia['cli_direccion'] ?? '')) ?>
                        </div>
                    </div>
                    <div>
                        <div class="small-label">Ciudad</div>
                        <div class="value-text"><?= htmlspecialchars($guia['ciudad_origen'] ?? 'N/D') ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-title">Destinatario</div>
                    <div class="mb-1">
                        <div class="small-label">Nombre</div>
                        <div class="value-text"><?= htmlspecialchars($guia['ser_destinatario']) ?></div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Teléfono</div>
                        <div class="value-text">**********</div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Dirección</div>
                        
                        <div class="value-text">
                            <?= htmlspecialchars(str_replace('&', ' ', $guia['ser_direccioncontacto'] ?? '')) ?>
                        </div>
                    </div>
                    <div>
                        <div class="small-label">Ciudad</div>
                        <div class="value-text"><?= htmlspecialchars($guia['ciudad_destino'] ?? 'N/D') ?></div>
                    </div>
                </div>
            </div>

            <hr class="divider-dashed">

            <!-- Detalle del envío -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="section-title">Detalle del envío</div>
                    <div class="mb-1">
                        <div class="small-label">Contenido</div>
                        <div class="value-text"><?= htmlspecialchars($guia['ser_paquetedescripcion'] ?? '') ?></div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Piezas</div>
                        <div class="value-text"><?= (int)$guia['ser_piezas'] ?></div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Prioridad</div>
                        <div class="value-text">
                            <?= !empty($guia['ser_prioridad']) ? htmlspecialchars($guia['ser_prioridad']) : 'Normal' ?>
                        </div>
                    </div>
                    <div>
                        <div class="small-label">Observaciones</div>
                        <div class="value-text">
                            <?= htmlspecialchars($guia['ser_descripcion'] ?? 'Sin observaciones adicionales.') ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-title">Peso y Volumen</div>
                    <div class="mb-1">
                        <div class="small-label">Peso (Kg)</div>
                        <div class="value-text">
                            <?php if ((float)$guia['ser_peso'] <= 30): ?>
                                <!-- <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    No ha sido pesado
                                </span> -->
                            <?php else: ?>
                                <?= htmlspecialchars($guia['ser_peso']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Volumen</div>
                        <div class="value-text"><?= htmlspecialchars($guia['ser_volumen'] ?? '0') ?></div>
                    </div>
                    <div>
                        <div class="small-label">Verificado</div>
                        <div class="value-text">
                            <?php if ((int)$guia['ser_verificado'] === 1): ?>
                                <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Verificado</span>
                            <?php else: ?>
                                <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>No verificado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="divider-dashed">

            <!-- Pago y totales -->
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <div class="section-title">Condiciones de pago</div>
                    <div class="mb-1">
                        <div class="small-label">Tipo de pago</div>
                        <div>
                            <span class="badge <?= $colorTP ?? 'bg-secondary text-white' ?>">
                                <?= htmlspecialchars($tipoPagoTexto ?? '') ?>
                                <?php if (!empty($textoTP)): ?>
                                    · <?= htmlspecialchars($textoTP) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-1">
                        <div class="small-label">Pago en</div>
                        <div class="value-text"><?= htmlspecialchars($pagoEn ?? 'Por definir') ?></div>
                    </div>
                    <div>
                        <div class="small-label">Resolución</div>
                        <div class="value-text">
                            <?= htmlspecialchars($guia['ser_resolucion'] ?? 'N/D') ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-title">Resumen de Pago</div>
                    <div class="mb-1 d-flex justify-content-between">
                        <span class="small-label">Valor declarado</span>
                        <span class="value-text">$ <?= $totales['valor_declarado_fmt'] ?? '0' ?></span>
                    </div>
                    <div class="mb-1 d-flex justify-content-between">
                        <span class="small-label">Seguro</span>
                        <span class="value-text">$ <?= $totales['seguro_fmt'] ?? '0' ?></span>
                    </div>
                    <div class="mb-1 d-flex justify-content-between">
                        <span class="small-label">Flete base</span>
                        <span class="value-text">$ <?= $totales['valor_flete_fmt'] ?? '0' ?></span>
                    </div>
                    <div class="mb-1 d-flex justify-content-between">
                        <span class="small-label">Préstamo / Interés</span>
                        <span class="value-text">$ <?= $totales['por_prestamo_fmt'] ?? '0' ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="mb-1 d-flex justify-content-between">
                        <span class="small-label">Total flete</span>
                        <span class="resumen-valor">$ <?= $totales['total_flete_fmt'] ?? '0' ?></span>
                    </div>
                    <div class="mb-1 d-flex justify-content-between">
                        <span class="small-label">Total préstamo</span>
                        <span class="value-text">$ <?= $totales['total_prestamo_fmt'] ?? '0' ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small-label">Total a cobrar</span>
                        <span class="value-text fw-semibold">$ <?= $totales['total_final_fmt'] ?? '0' ?></span>
                    </div>
                </div>
            </div>

            <hr class="divider-dashed">
            <!-- Notas -->
            <div class="mt-4">
                <div class="small text-danger mt-2">
                    <strong>Aviso:</strong>Transmillas no se hace responsable por daños o suciedad en mercancías 
                    sin embalaje adecuado (colchones, vidrios, objetos frágiles, etc.). 
                    <strong>El embalaje es responsabilidad del cliente.</strong>
                    Asimismo, los productos comestibles deben ir debidamente embalados y 
                    protegidos, utilizando empaques apropiados para evitar daños, contaminación, derrames o deterioro durante el transporte.
                </div>
            </div>
            <!-- Firmas -->
            <div class="row g-3">
                <?php if ($letra === "R"): ?>    
                    <div class="col-md-6">
                        <div class="section-title">Firma recogida</div>
                        <div class="signature-box">
                            <?php if (!empty($firmas['Recogida'])): ?>
                                <?php
                                $firmaEnt = $firmas['Recogida'];
                                // if ($firmaEnt['tipo'] === 'imagen') {
                                //     $imgData2 = base64_encode($firmaEnt['blob']);
                                //     $srcEnt   = "data:image/png;base64,".$imgData2;
                                // } else {
                                    $srcEnt = $firmaEnt['firmaImg'];
                                // }
                                ?>
                                <img src="../../<?= $srcEnt ?>" alt="Firma Entrega" style="max-height:110px; max-width:100%;">
                            <?php else: ?>
                                <span class="text-muted small">Sin registro de firma de Recogida.</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($firmas['Recogida'])): ?>
                        <div class="small text-muted mt-1 d-flex align-items-center gap-2 flex-wrap">
                            <span>
                                <?= htmlspecialchars($firmas['Recogida']['nombre']) ?> ·
                                Tel: <?= htmlspecialchars($firmas['Recogida']['telefono']) ?>
                            </span>

                            <?php if (!empty($ubicaciones['RECOGIDA'])): 
                                $lat = $ubicaciones['RECOGIDA']['latitud'];
                                $lng = $ubicaciones['RECOGIDA']['longitud'];
                                $mapUrl = "https://www.google.com/maps?q={$lat},{$lng}";
                            ?>
                                <a href="<?= $mapUrl ?>" target="_blank" class="text-danger" title="Ver ubicación de recogida en mapa">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($letra === "E"): ?>  
                    <div class="col-md-6">
                        <div class="section-title">Firma entrega</div>
                        <div class="signature-box">
                            <?php if (!empty($firmas['Entrega'])): ?>
                                <?php
                                $firmaEnt = $firmas['Entrega'];
                                // if ($firmaEnt['tipo'] === 'imagen') {
                                //     $imgData2 = base64_encode($firmaEnt['blob']);
                                //     $srcEnt   = "data:image/png;base64,".$imgData2;
                                // } else {
                                    $srcEnt = $firmaEnt['firmaImg'];
                                // }
                                ?>
                                <img src="../../<?= $srcEnt ?>" alt="Firma Entrega" style="max-height:110px; max-width:100%;">
                            <?php else: ?>
                                <span class="text-muted small">Sin registro de firma de entrega.</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($firmas['Entrega'])): ?>
                        <div class="small text-muted mt-1 d-flex align-items-center gap-2 flex-wrap">
                            <span>
                                <?= htmlspecialchars($firmas['Entrega']['nombre']) ?> ·
                                Tel: <?= htmlspecialchars($firmas['Entrega']['telefono']) ?>
                            </span>

                            <?php if (!empty($ubicaciones['ENTREGA'])): 
                                $lat = $ubicaciones['ENTREGA']['latitud'];
                                $lng = $ubicaciones['ENTREGA']['longitud'];
                                $mapUrl = "https://www.google.com/maps?q={$lat},{$lng}";
                            ?>
                                <a href="<?= $mapUrl ?>" target="_blank" class="text-primary" title="Ver ubicación de entrega en mapa">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?> 
            </div>

            <!-- Notas -->
            <div class="mt-4">
                <div class="small text-muted">
                    Al firmar, el cliente declara que la información es correcta y acepta las
                    condiciones de transporte de Transmillas. Consulte la política completa en
                    <a href="https://www.transmillas.com/images/CONDICIONES.pdf" target="_blank">
                        transmillas.com/politica.php
                    </a>.
                </div>
            </div>

        </div>
    </div>

    <?php endif; ?>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
