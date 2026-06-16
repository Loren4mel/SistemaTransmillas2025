<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Precios transporte</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background: #f5f7fb; }
    .titulo { background: #074f91; color: #fff; }
    .tabla-precios thead th { background: #074f91; color: #fff; }
  </style>
</head>
<body>
<div class="container-fluid py-3">
  <div class="card shadow-sm mb-3">
    <div class="card-header titulo">
      <h5 class="mb-0"><i class="fa-solid fa-truck me-2"></i>Precios transporte por pieza</h5>
    </div>
    <div class="card-body">
      <form id="formPrecioTransporte" class="row g-3 align-items-end">
        <input type="hidden" id="precio_id" name="id">
        <div class="col-md-3">
          <label for="precio_tipo" class="form-label">Tipo de transporte</label>
          <select id="precio_tipo" name="tipo" class="form-select" required>
            <option value="">Seleccione...</option>
            <option value="Bus">Bus</option>
            <option value="Furgon">Furgon</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="precio_origen" class="form-label">Ciudad origen</label>
          <select id="precio_origen" name="origen" class="form-select" required>
            <option value="">Seleccione...</option>
            <?php foreach ($Ciudades as $ciudad): ?>
              <option value="<?= (int) $ciudad["idciudades"] ?>"><?= htmlspecialchars($ciudad["ciu_nombre"]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="precio_destino" class="form-label">Ciudad destino</label>
          <select id="precio_destino" name="destino" class="form-select" required>
            <option value="">Seleccione...</option>
            <?php foreach ($Ciudades as $ciudad): ?>
              <option value="<?= (int) $ciudad["idciudades"] ?>"><?= htmlspecialchars($ciudad["ciu_nombre"]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="precio_valor" class="form-label">Valor por pieza</label>
          <input type="number" min="1" step="1" id="precio_valor" name="valor" class="form-control" required>
        </div>
        <div class="col-12">
          <button type="submit" id="btnGuardar" class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar precio</button>
          <button type="button" class="btn btn-outline-secondary" onclick="limpiarFormulario()">Cancelar edicion</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <strong>Precios configurados</strong>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0 tabla-precios">
        <thead>
          <tr>
            <th>Transporte</th>
            <th>Origen</th>
            <th>Destino</th>
            <th>Valor por pieza</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tablaPrecios">
          <tr><td colspan="5" class="text-center text-muted py-4">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const endpoint = 'PreciosTransporteController.php';
let precios = [];

document.getElementById('formPrecioTransporte').addEventListener('submit', function (event) {
  event.preventDefault();
  const datos = new FormData(this);
  datos.append('guardar', '1');

  fetch(endpoint, { method: 'POST', body: datos })
    .then(respuesta => respuesta.json())
    .then(resultado => {
      if (!resultado.success) {
        throw new Error(resultado.mensaje);
      }
      Swal.fire('Listo', resultado.mensaje, 'success');
      limpiarFormulario();
      listarPrecios();
    })
    .catch(error => Swal.fire('Atencion', error.message, 'error'));
});

function listarPrecios() {
  const datos = new FormData();
  datos.append('listar', '1');
  fetch(endpoint, { method: 'POST', body: datos })
    .then(respuesta => respuesta.json())
    .then(resultado => {
      if (!resultado.success) {
        throw new Error(resultado.mensaje);
      }
      precios = resultado.datos || [];
      dibujarTabla();
    })
    .catch(error => {
      document.getElementById('tablaPrecios').innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">' + error.message + '</td></tr>';
    });
}

function dibujarTabla() {
  const tabla = document.getElementById('tablaPrecios');
  if (!precios.length) {
    tabla.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay precios configurados.</td></tr>';
    return;
  }

  tabla.innerHTML = precios.map(precio => `
    <tr>
      <td>${precio.ptr_tipo}</td>
      <td>${precio.ciudad_origen}</td>
      <td>${precio.ciudad_destino}</td>
      <td>$ ${Number(precio.ptr_valorpieza).toLocaleString('es-CO')}</td>
      <td>
        <button class="btn btn-sm btn-outline-primary" onclick="editarPrecio(${precio.idpreciotransporte})">Editar</button>
        <button class="btn btn-sm btn-outline-danger" onclick="eliminarPrecio(${precio.idpreciotransporte})">Eliminar</button>
      </td>
    </tr>
  `).join('');
}

function editarPrecio(id) {
  const precio = precios.find(item => Number(item.idpreciotransporte) === Number(id));
  if (!precio) return;
  document.getElementById('precio_id').value = precio.idpreciotransporte;
  document.getElementById('precio_tipo').value = precio.ptr_tipo;
  document.getElementById('precio_origen').value = precio.ptr_idciudadori;
  document.getElementById('precio_destino').value = precio.ptr_idciudaddes;
  document.getElementById('precio_valor').value = Number(precio.ptr_valorpieza);
  document.getElementById('btnGuardar').innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Actualizar precio';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function eliminarPrecio(id) {
  Swal.fire({
    title: 'Eliminar precio',
    text: 'Este precio ya no estara disponible para la ruta.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Eliminar',
    cancelButtonText: 'Cancelar'
  }).then(resultado => {
    if (!resultado.isConfirmed) return;
    const datos = new FormData();
    datos.append('eliminar', '1');
    datos.append('id', id);
    fetch(endpoint, { method: 'POST', body: datos })
      .then(respuesta => respuesta.json())
      .then(respuesta => {
        if (!respuesta.success) throw new Error(respuesta.mensaje);
        Swal.fire('Listo', respuesta.mensaje, 'success');
        limpiarFormulario();
        listarPrecios();
      })
      .catch(error => Swal.fire('Atencion', error.message, 'error'));
  });
}

function limpiarFormulario() {
  document.getElementById('formPrecioTransporte').reset();
  document.getElementById('precio_id').value = '';
  document.getElementById('btnGuardar').innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Guardar precio';
}

listarPrecios();
</script>
</body>
</html>
