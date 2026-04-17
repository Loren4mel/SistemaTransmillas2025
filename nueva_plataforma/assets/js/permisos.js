const redi = "/nueva_plataforma/controller/permisosController.php";

function renderPermisoSelect(data, row, campo) {
  const valor = String(data) === "1" ? "1" : "0";

  return `
    <select class="form-select form-select-sm cambiar-campo"
            data-id="${row.idpermisos}"
            data-campo="${campo}">
      <option value="1" ${valor === "1" ? "selected" : ""}>Si</option>
      <option value="0" ${valor === "0" ? "selected" : ""}>No</option>
    </select>
  `;
}

function mostrarAlerta(icon, title, text = "") {
  return Swal.fire({
    icon: icon,
    title: title,
    text: text,
    confirmButtonText: "Aceptar"
  });
}

$(document).ready(function () {
  const modalCrearPermiso = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCrearPermiso"));
  const modalEditarPermiso = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEditarPermiso"));

  const tabla = $("#tablaUsuarios").DataTable({
    ajax: {
      url: redi,
      type: "POST",
      data: function (d) {
        d.ajax = true;
        d.rol = $("#filtroRol").val();
        d.principal = $("#filtroprincipal").val();
        d.secundario = $("#filtrosecundario").val();
      },
      dataSrc: ""
    },
    columns: [
      { data: "rol_nombre" },
      {
        data: "men_nombre",
        render: function (data, type, row) {
          if (!row.men_url) {
            return data;
          }

          return `
            <a href="../../${row.men_url}" target="_blank" rel="noopener noreferrer">
              ${data}
            </a>
          `;
        }
      },
      { data: "men_predecesor" },
      {
        data: "per_crear",
        render: function (data, type, row) {
          return renderPermisoSelect(data, row, "per_crear");
        }
      },
      {
        data: "per_editar",
        render: function (data, type, row) {
          return renderPermisoSelect(data, row, "per_editar");
        }
      },
      {
        data: "per_eliminar",
        render: function (data, type, row) {
          return renderPermisoSelect(data, row, "per_eliminar");
        }
      },
      {
        data: "per_consultar",
        render: function (data, type, row) {
          return renderPermisoSelect(data, row, "per_consultar");
        }
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `
            <button class="btn btn-sm btn-outline-primary editar-permiso"
                    title="Editar"
                    data-id="${row.idpermisos}">
              <i class="fas fa-edit"></i>
            </button>
          `;
        }
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `
            <button class="btn btn-sm btn-danger eliminar-permiso"
                    title="Eliminar"
                    data-id="${row.idpermisos}">
              <i class="fas fa-trash-alt"></i>
            </button>
          `;
        }
      }
    ]
  });

  $("#formCrearPermiso").on("submit", function (e) {
    e.preventDefault();

    $.ajax({
      url: redi,
      type: "POST",
      dataType: "json",
      data: $(this).serialize() + "&crear_permiso=true",
      success: function (res) {
        if (!res || !res.ok) {
          mostrarAlerta("error", "No se pudo crear el permiso", res && res.message ? res.message : "");
          return;
        }

        $("#formCrearPermiso")[0].reset();
        $("#crearPermConsultar").val("1");
        modalCrearPermiso.hide();
        tabla.ajax.reload(null, false);
        mostrarAlerta("success", "Permiso creado", res.message || "");
      },
      error: function () {
        mostrarAlerta("error", "Error", "Hubo un error al crear el permiso.");
      }
    });
  });

  $("#formEditarPermiso").on("submit", function (e) {
    e.preventDefault();

    $.ajax({
      url: redi,
      type: "POST",
      dataType: "json",
      data: $(this).serialize() + "&guardar_edicion_permiso=true",
      success: function (res) {
        if (!res || !res.ok) {
          mostrarAlerta("error", "No se pudo actualizar el permiso", res && res.message ? res.message : "");
          return;
        }

        modalEditarPermiso.hide();
        tabla.ajax.reload(null, false);
        mostrarAlerta("success", "Permiso actualizado", res.message || "");
      },
      error: function () {
        mostrarAlerta("error", "Error", "Hubo un error al actualizar el permiso.");
      }
    });
  });

  $("#filtroRol, #filtroprincipal, #filtrosecundario").on("change", function () {
    tabla.ajax.reload();
  });

  $("#tablaUsuarios tbody").on("click", ".editar-permiso", function () {
    const id = $(this).data("id");

    $.ajax({
      url: redi,
      type: "POST",
      dataType: "json",
      data: {
        obtener_permiso: true,
        id: id
      },
      success: function (res) {
        if (!res || !res.ok || !res.data) {
          mostrarAlerta("error", "No se pudo cargar el permiso");
          return;
        }

        $("#editarIdPermiso").val(res.data.idpermisos);
        $("#editarRol").val(res.data.roles_idroles);
        $("#editarMenu").val(res.data.menu_idmenu);
        $("#editarPermCrear").val(String(res.data.per_crear));
        $("#editarPermEditar").val(String(res.data.per_editar));
        $("#editarPermEliminar").val(String(res.data.per_eliminar));
        $("#editarPermConsultar").val(String(res.data.per_consultar));
        modalEditarPermiso.show();
      },
      error: function () {
        mostrarAlerta("error", "Error", "No se pudo cargar la informacion del permiso.");
      }
    });
  });
});

$("#tablaUsuarios tbody").on("change", ".cambiar-campo", function () {
  const id = $(this).data("id");
  const campo = $(this).data("campo");
  const valor = $(this).val();

  $.ajax({
    url: redi,
    type: "POST",
    dataType: "json",
    data: {
      actualizar_campo: true,
      id: id,
      campo: campo,
      valor: valor
    },
    success: function (res) {
      if (!res || !res.ok) {
        mostrarAlerta("error", "No se pudo actualizar el permiso");
        return;
      }

      $("#tablaUsuarios").DataTable().ajax.reload(null, false);
      mostrarAlerta("success", "Permiso actualizado");
    },
    error: function () {
      mostrarAlerta("error", "Error", "Hubo un error al actualizar.");
    }
  });
});

$("#tablaUsuarios tbody").on("click", ".eliminar-permiso", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Eliminar permiso",
    text: "Esta accion no se puede deshacer.",
    showCancelButton: true,
    confirmButtonText: "Si, eliminar",
    cancelButtonText: "Cancelar"
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    $.ajax({
      url: redi,
      type: "POST",
      dataType: "json",
      data: {
        eliminar_permiso: true,
        id: id
      },
      success: function (res) {
        if (!res || !res.ok) {
          mostrarAlerta("error", "No se pudo eliminar el permiso");
          return;
        }

        $("#tablaUsuarios").DataTable().ajax.reload(null, false);
        mostrarAlerta("success", "Permiso eliminado");
      },
      error: function () {
        mostrarAlerta("error", "Error", "Error al eliminar el permiso.");
      }
    });
  });
});

function verDispositivos(idUsuario) {
  window.usuarioDispositivoActual = idUsuario;

  $.ajax({
    url: redi,
    type: "POST",
    dataType: "json",
    data: {
      listar_dispositivos: true,
      idusuario: idUsuario
    },
    success: function (data) {
      let tbody = $("#tablaDispositivos tbody");
      tbody.html("");

      if (data.length === 0) {
        tbody.append(`
          <tr>
            <td colspan="6" class="text-muted">
              No hay dispositivos asociados
            </td>
          </tr>
        `);
      }

      data.forEach(d => {
        const estado = d.authorized == 1
          ? '<span class="badge bg-success">Autorizado</span>'
          : '<span class="badge bg-warning text-dark">Pendiente</span>';

        const accion = d.authorized == 1
          ? `<button class="btn btn-sm btn-danger" onclick="bloquearDispositivo(${d.id})">
               <i class="fas fa-ban"></i>
             </button>`
          : `<button class="btn btn-sm btn-success" onclick="autorizarDispositivo(${d.id})">
               <i class="fas fa-check"></i>
             </button>`;

        tbody.append(`
          <tr>
            <td>${d.device_name ?? "Sin nombre"}</td>
            <td>${d.device_type ?? "-"}</td>
            <td>${d.last_login ?? "-"}</td>
            <td>${d.ip_last ?? "-"}</td>
            <td>${estado}</td>
            <td>${accion}</td>
          </tr>
        `);
      });

      $("#modalDispositivos").modal("show");
    }
  });
}

function autorizarDispositivo(idDispositivo) {
  Swal.fire({
    icon: "question",
    title: "Autorizar dispositivo",
    showCancelButton: true,
    confirmButtonText: "Autorizar",
    cancelButtonText: "Cancelar"
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.ajax({
      url: redi,
      type: "POST",
      dataType: "json",
      data: {
        autorizar_dispositivo: true,
        id: idDispositivo
      },
      success: function (res) {
        if (res.ok) {
          verDispositivos(window.usuarioDispositivoActual);
          mostrarAlerta("success", "Dispositivo autorizado");
        }
      }
    });
  });
}

function bloquearDispositivo(idDispositivo) {
  Swal.fire({
    icon: "warning",
    title: "Bloquear dispositivo",
    showCancelButton: true,
    confirmButtonText: "Bloquear",
    cancelButtonText: "Cancelar"
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.ajax({
      url: redi,
      type: "POST",
      dataType: "json",
      data: {
        bloquear_dispositivo: true,
        id: idDispositivo
      },
      success: function (res) {
        if (res.ok) {
          verDispositivos(window.usuarioDispositivoActual);
          mostrarAlerta("success", "Dispositivo bloqueado");
        }
      }
    });
  });
}
