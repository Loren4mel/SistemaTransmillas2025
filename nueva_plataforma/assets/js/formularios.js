$(function () {
  $('.input-link-formulario').each(function () {
    const relativeLink = $(this).data('relative-link');
    if (relativeLink && String(relativeLink).charAt(0) === '/') {
      $(this).val(window.location.origin + relativeLink);
    }
  });

  function agregarPregunta() {
    const template = document.getElementById('templatePregunta');
    const nodo = template.content.cloneNode(true);
    $('#contenedorPreguntas').append(nodo);
  }

  function agregarOpcion($fila) {
    const template = document.getElementById('templateOpcion');
    const nodo = template.content.cloneNode(true);
    $fila.find('.options-list').append(nodo);
  }

  function actualizarOpciones($fila) {
    const tipo = $fila.find('.select-tipo-pregunta').val();
    const requiereOpciones = tipo === 'seleccion_unica' || tipo === 'seleccion_multiple';
    $fila.find('.options-box').toggle(requiereOpciones);
    if (requiereOpciones && $fila.find('.option-row').length === 0) {
      agregarOpcion($fila);
      agregarOpcion($fila);
    }
  }

  function actualizarContexto() {
    const tipo = $('#contexto_tipo').val();
    $('#contextoImagenBox').toggle(tipo === 'imagen');
    $('#contextoYoutubeBox').toggle(tipo === 'youtube');
    $('#contexto_imagen').prop('required', tipo === 'imagen');
    $('#contexto_youtube_url').prop('required', tipo === 'youtube');
  }

  agregarPregunta();
  actualizarContexto();

  $('#btnAgregarPregunta').on('click', agregarPregunta);
  $('#contexto_tipo').on('change', actualizarContexto);

  $('#contenedorPreguntas').on('change', '.select-tipo-pregunta', function () {
    actualizarOpciones($(this).closest('.question-row'));
  });

  $('#contenedorPreguntas').on('click', '.btn-agregar-opcion', function () {
    agregarOpcion($(this).closest('.question-row'));
  });

  $('#contenedorPreguntas').on('click', '.btn-quitar-opcion', function () {
    const $lista = $(this).closest('.options-list');
    if ($lista.find('.option-row').length <= 1) {
      Swal.fire({ icon: 'info', title: 'Opcion requerida', text: 'Deja al menos una opcion para esta pregunta.' });
      return;
    }
    $(this).closest('.option-row').remove();
  });

  $('#contenedorPreguntas').on('click', '.btn-quitar-pregunta', function () {
    if ($('.question-row').length <= 1) {
      Swal.fire({ icon: 'info', title: 'Pregunta requerida', text: 'El formulario necesita al menos una pregunta.' });
      return;
    }
    $(this).closest('.question-row').remove();
  });

  $('#formCrearFormulario').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('accion', 'crear_formulario');
    formData.append('titulo', $('#titulo').val());
    formData.append('descripcion', $('#descripcion').val());
    formData.append('contexto_tipo', $('#contexto_tipo').val());
    formData.append('contexto_youtube_url', $('#contexto_youtube_url').val());

    const contextoImagen = $('#contexto_imagen').get(0).files[0];
    if (contextoImagen) {
      formData.append('contexto_imagen', contextoImagen);
    }

    $('.question-row').each(function (preguntaIndex) {
      const $fila = $(this);
      formData.append('pregunta_etiqueta[]', $fila.find('input[name="pregunta_etiqueta[]"]').val());
      formData.append('pregunta_tipo[]', $fila.find('select[name="pregunta_tipo[]"]').val());
      formData.append('pregunta_requerida[]', $fila.find('input[name="pregunta_requerida[]"]').is(':checked') ? '1' : '0');

      const imagenPregunta = $fila.find('.pregunta-imagen').get(0).files[0];
      if (imagenPregunta) {
        formData.append('pregunta_imagen[' + preguntaIndex + ']', imagenPregunta);
      }

      $fila.find('.option-row').each(function (opcionIndex) {
        const $opcion = $(this);
        formData.append('pregunta_opciones_texto[' + preguntaIndex + '][]', $opcion.find('.opcion-texto').val());
        const imagenOpcion = $opcion.find('.opcion-imagen').get(0).files[0];
        if (imagenOpcion) {
          formData.append('pregunta_opciones_imagen[' + preguntaIndex + '][' + opcionIndex + ']', imagenOpcion);
        }
      });
    });

    $.ajax({
      url: 'FormulariosController.php',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (response) {
        Swal.fire({
          icon: 'success',
          title: 'Formulario creado',
          text: response.message || 'El formulario fue creado correctamente.',
        }).then(function () {
          window.location.reload();
        });
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        Swal.fire({
          icon: 'error',
          title: 'No se pudo crear',
          text: response.message || 'Revisa los datos e intenta nuevamente.',
        });
      }
    });
  });

  $('.btn-copiar-link').on('click', function () {
    const input = $(this).closest('.input-group').find('.input-link-formulario').get(0);
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    Swal.fire({ icon: 'success', title: 'Link copiado', timer: 1200, showConfirmButton: false });
  });

  $('.btn-eliminar-formulario').on('click', function () {
    const formularioId = $(this).data('formulario-id');
    Swal.fire({
      icon: 'warning',
      title: 'Eliminar formulario',
      text: 'El formulario dejara de estar disponible para nuevos pendientes.',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      $.ajax({
        url: 'FormulariosController.php',
        method: 'POST',
        dataType: 'json',
        data: {
          accion: 'eliminar_formulario',
          formulario_id: formularioId
        },
        success: function (response) {
          Swal.fire({
            icon: 'success',
            title: 'Formulario eliminado',
            text: response.message || 'Formulario eliminado correctamente.'
          }).then(function () {
            window.location.reload();
          });
        },
        error: function (xhr) {
          const response = xhr.responseJSON || {};
          Swal.fire({
            icon: 'error',
            title: 'No se pudo eliminar',
            text: response.message || 'Intenta nuevamente.'
          });
        }
      });
    });
  });
});
