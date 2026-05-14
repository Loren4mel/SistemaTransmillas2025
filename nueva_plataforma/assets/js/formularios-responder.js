$(function () {
  $('#formResponder').on('submit', function (e) {
    e.preventDefault();

    const gruposRequeridos = {};
    $('.seleccion-multiple[data-required="1"]').each(function () {
      gruposRequeridos[$(this).data('required-group')] = true;
    });

    for (const grupo in gruposRequeridos) {
      if ($('.seleccion-multiple[data-required-group="' + grupo + '"]:checked').length === 0) {
        Swal.fire({ icon: 'warning', title: 'Respuesta requerida', text: 'Debes completar las preguntas obligatorias.' });
        return;
      }
    }

    const formData = new FormData(this);
    formData.append('accion', 'responder_formulario');

    $.ajax({
      url: 'FormularioResponderController.php',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (response) {
        Swal.fire({
          icon: 'success',
          title: 'Respuesta enviada',
          text: response.message || 'Tu respuesta fue guardada correctamente.',
        }).then(function () {
          window.location.href = 'PendientesController.php';
        });
      },
      error: function (xhr) {
        const response = xhr.responseJSON || {};
        Swal.fire({
          icon: 'error',
          title: 'No se pudo enviar',
          text: response.message || 'Revisa tus respuestas e intenta nuevamente.',
        });
      }
    });
  });
});
