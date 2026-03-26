<?php 
if (!isset($_GET['sede']) || !isset($_GET['acceso'])) {
    echo "<script>
            alert('No tiene acceso a esta página');
            window.close(); // cierra la pestaña
          </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transmillas - Web</title>

  
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- jQuery (antes que DataTables) -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }
    .sidebar {
      background-color: #ffffff;
      border-right: 1px solid #dee2e6;
      min-height: 100vh;
    }
    .sidebar .nav-link {
      color: #002f6c;
      font-weight: 500;
    }
    .sidebar .nav-link.active {
      background-color: #002f6c;
      color: white;
    }
    .badge-notify {
      background-color: red;
      color: white;
      border-radius: 50px;
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      margin-left: auto;
    }
    .topbar {
      background-color: #002f6c;
      color: white;
    }
    .bottom-nav {
      background-color: white;
      border-top: 1px solid #dee2e6;
    }
    .bottom-nav .nav-link {
      color: #002f6c;
      font-size: 0.9rem;
    }
    .form-section {
      padding: 2rem;
    }
    .notification-icon {
      position: relative;
    }
    .notification-icon .badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: red;
      font-size: 0.7rem;
    }
    @media (max-width: 991.98px) {
      .sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        left: -250px;
        width: 250px;
        transition: all 0.3s;
        z-index: 1050;
      }
      .sidebar.show {
        left: 0;
      }
      .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        display: none;
      }
      .overlay.show {
        display: block;
      }
      main {
        margin-left: 0 !important;
      }
    }
  </style>
</head>
<body>
  <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
  <div class="container-fluid">
    <div class="row flex-nowrap">
      <!-- Sidebar -->
      <?php
      // Mostrar todos los errores
      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);
      ?>
      <?php
        
        // require_once "../../model/layoutModel.php";

        // $menuClass = new menu();
        // $itemsMenu = $menuClass->obtenerMenu();
      ?>

      <nav class="col-auto col-lg-2 sidebar d-flex flex-column p-3" id="sidebar">
          <h4 class="text-primary">Transmillas</h4>
          <ul class="nav flex-column gap-2">
              <?php foreach ($itemsMenu as $item): ?>
                  <li class="nav-item">
                      <a class="nav-link" href="#" onclick="cargarContenido('../controller/DescargasOficinaController.php?acceso=1&sede=1')">
                          <?php echo htmlspecialchars($item['men_nombre']); ?>
                      </a>
                  </li>
              <?php endforeach; ?>
          </ul>
      </nav>

      <!-- Contenido principal -->
      <div class="col p-0">
        <!-- Barra superior -->
        <nav class="navbar navbar-expand-lg topbar">
          <div class="container-fluid">
            <button class="btn btn-light d-lg-none" onclick="toggleSidebar()">☰</button>
            <a class="navbar-brand text-white fw-bold" href="#">Transmillas</a>
            <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menuSuperior">
              ☰
            </button>
            <div class="collapse navbar-collapse" id="menuSuperior">
              <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link text-white" href="#" onclick="cargarContenido('paginas/inicio.html')">🏠 Inicio</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="/nueva_plataforma/controller/PendientesController.php">Pendientes</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#" onclick="cargarContenido('paginas/sin_ingreso.html')">👥 Sin ingreso</a></li>
              </ul>
            </div>
          </div>
        </nav>

        <!-- Contenedor dinámico -->
        <main id="contenido" class="p-4">
          <h4>Bienvenido a Transmillas</h4>
          <p>Selecciona una opción del menú para cargar el contenido.</p>
        </main>
      </div>
    </div>
  </div>

  <script>
    function toggleSubmenu(event) {
      event.preventDefault();
      const submenu = event.target.closest("li").querySelector(".submenu");
      submenu.style.display = submenu.style.display === "none" ? "block" : "none";
    }
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
    }
    function cargarContenido(ruta) {
      fetch(ruta)
        .then(response => {
          if (!response.ok) throw new Error("No se pudo cargar el contenido.");
          return response.text();
        })
        .then(html => {
          const contenedor = document.getElementById("contenido");
          contenedor.innerHTML = html;

          // Buscar scripts dentro del HTML cargado y ejecutarlos
          contenedor.querySelectorAll("script").forEach(script => {
            const nuevoScript = document.createElement("script");
            if (script.src) {
              nuevoScript.src = script.src;
            } else {
              nuevoScript.textContent = script.textContent;
            }
            document.body.appendChild(nuevoScript);
          });
        })
        .catch(error => {
          document.getElementById("contenido").innerHTML = "<p style='color:red;'>Error al cargar el contenido.</p>";
        });
    }
  </script>
</body>

