jQuery(document).ready(function ($) {
  // Manejar el envío del formulario de contacto
  $("#despacho-contact-form").on("submit", function (e) {
    e.preventDefault();

    const $form = $(this);
    const $submitButton = $form.find('button[type="submit"]');
    const originalButtonText = $submitButton.html();

    // Deshabilitar el botón y mostrar loading
    $submitButton
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

    // Obtener los datos del formulario
    const formData = new FormData(this);
    formData.append("action", "despacho_contact_form");
    formData.append("nonce", lexhoyDespachos.nonce);

    // Enviar la petición AJAX
    $.ajax({
      url: lexhoyDespachos.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          // Mostrar mensaje de éxito
          showNotification("success", response.data.message);
          // Limpiar el formulario
          $form[0].reset();
        } else {
          // Mostrar mensaje de error
          showNotification("error", response.data.message);
        }
      },
      error: function () {
        // Mostrar mensaje de error genérico
        showNotification(
          "error",
          "Error al enviar el mensaje. Por favor, intente nuevamente."
        );
      },
      complete: function () {
        // Restaurar el botón
        $submitButton.prop("disabled", false).html(originalButtonText);
      },
    });
  });

  // Función para mostrar notificaciones
  function showNotification(type, message) {
    // Crear el elemento de notificación
    const $notification = $("<div>", {
      class: "despacho-notification " + type,
      html: `
                <div class="notification-content">
                    <i class="fas ${
                      type === "success"
                        ? "fa-check-circle"
                        : "fa-exclamation-circle"
                    }"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            `,
    });

    // Añadir la notificación al DOM
    $("body").append($notification);

    // Mostrar la notificación con animación
    setTimeout(() => {
      $notification.addClass("show");
    }, 100);

    // Configurar el botón de cerrar
    $notification.find(".notification-close").on("click", function () {
      $notification.removeClass("show");
      setTimeout(() => {
        $notification.remove();
      }, 300);
    });

    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
      if ($notification.hasClass("show")) {
        $notification.removeClass("show");
        setTimeout(() => {
          $notification.remove();
        }, 300);
      }
    }, 5000);
  }
});
