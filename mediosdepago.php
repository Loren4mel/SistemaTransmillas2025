<link rel="stylesheet" href="https://cdn.materialdesignicons.com/5.4.55/css/materialdesignicons.min.css">
<style>
    /* Estilos para la franja azul con el titulo */
    .titulo-barra {
        background-color: #007bff;
        color: white;
        text-align: center;
        padding: 15px;
        font-size: 24px;
        font-weight: bold;
    }

    /* Contenedor de medios de pago */
    .qr-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 24px;
        margin: 20px 0;
    }

    .qr-card {
        text-align: center;
    }

    .qr-title {
        margin-bottom: 12px;
        font-size: 20px;
        font-weight: bold;
    }

    .qr-card img {
        max-width: 100%;
        height: auto;
        width: 300px;
        transition: transform 0.3s ease-in-out;
        cursor: pointer;
    }

    .qr-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 300px;
        min-height: 300px;
        padding: 20px;
        border: 2px dashed #007bff;
        border-radius: 12px;
        background-color: #f8f9fa;
        color: #555;
    }

    .qr-expanded {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .qr-expanded img {
        max-width: 80%;
        max-height: 80%;
        width: auto;
        height: auto;
    }
</style>

<script>
    function toggleQR(overlayId) {
        var qrOverlay = document.getElementById(overlayId);
        qrOverlay.style.display = qrOverlay.style.display === "flex" ? "none" : "flex";
    }
</script>

<?php
require("login_autentica.php");
include("layout.php");

echo '<div class="titulo-barra">Medios de Pago</div>';

$mediosPago = [
    [
        "nombre" => "Bancolombia Llave",
        "imagen" => "images/PagoBancolombiaLlave.png",
        "overlay" => "qrOverlayBancolombia"
    ],
    [
        "nombre" => "Daviplata",
        "imagen" => "images/daviplata.png",
        "overlay" => "qrOverlayDaviplata"
    ]
];
?>

<div class="qr-container">
    <?php foreach ($mediosPago as $medio): ?>
        <div class="qr-card">
            <div class="qr-title"><?php echo htmlspecialchars($medio["nombre"]); ?></div>
            <?php if (file_exists($medio["imagen"])): ?>
                <img src="<?php echo htmlspecialchars($medio["imagen"]); ?>" alt="<?php echo htmlspecialchars($medio["nombre"]); ?>" onclick="toggleQR('<?php echo $medio["overlay"]; ?>')">
                <div id="<?php echo htmlspecialchars($medio["overlay"]); ?>" class="qr-expanded" style="display: none;" onclick="toggleQR('<?php echo $medio["overlay"]; ?>')">
                    <img src="<?php echo htmlspecialchars($medio["imagen"]); ?>" alt="<?php echo htmlspecialchars($medio["nombre"]); ?>">
                </div>
            <?php else: ?>
                <div class="qr-placeholder">
                    No se encontro la imagen de <?php echo htmlspecialchars($medio["nombre"]); ?>.
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
include("footer.php");
?>
