// Función global para navegar al despacho
function navigateToDespacho(slug) {
  console.log("Navegando a despacho:", slug);
  window.location.href = "/" + slug;
}

// Función para verificar si los scripts están cargados
function checkScriptsLoaded() {
  return (
    typeof instantsearch !== "undefined" && typeof algoliasearch !== "undefined"
  );
}

// Función para inicializar la búsqueda
function initializeSearch() {
  try {
    console.log("Inicializando búsqueda...");
    const searchClient = algoliasearch(
      lexhoyDespachosData.appId,
      lexhoyDespachosData.searchApiKey
    );

    const search = instantsearch({
      indexName: lexhoyDespachosData.indexName,
      searchClient,
      routing: true,
    });

    // Widget de búsqueda
    search.addWidgets([
      instantsearch.widgets.searchBox({
        container: "#searchbox",
        placeholder: "Buscar...",
        showReset: false,
        showSubmit: true,
        submitTitle: "Buscar",
        resetTitle: "Limpiar",
      }),
    ]);

    // Widget de resultados
    search.addWidgets([
      instantsearch.widgets.hits({
        container: "#hits",
        templates: {
          item: `
            <div class="despacho-card hit-card" data-hit='{{{json this}}}'>
              {{#estado_verificacion}}
                {{#isVerified}}
                  <div class="verification-badge">
                    <i class="fas fa-check-circle"></i>
                  </div>
                {{/isVerified}}
              {{/estado_verificacion}}
              <div class="despacho-name">{{nombre}}</div>
              <div class="despacho-location">{{localidad}}, {{provincia}}</div>
              <div class="despacho-areas">{{areas_practica}}</div>
              <button class="despacho-link" onclick="window.navigateToDespacho('{{slug}}')">Ver más</button>
            </div>
          `,
          empty: `
            <div class="no-results">
              No se encontraron resultados para esta búsqueda.
            </div>
          `,
        },
      }),
    ]);

    // Widget de paginación
    search.addWidgets([
      instantsearch.widgets.pagination({
        container: "#pagination",
        padding: 2,
        showFirst: false,
        showLast: false,
      }),
    ]);

    // Iniciar la búsqueda
    search.start();
    console.log("Búsqueda inicializada correctamente");
  } catch (error) {
    console.error("Error al inicializar la búsqueda:", error);
    const searchContainer = document.querySelector(".lexhoy-despachos-search");
    if (searchContainer) {
      searchContainer.innerHTML = `<div class="error">Error al inicializar la búsqueda: ${error.message}</div>`;
    }
  }
}

// Esperar a que el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM cargado, verificando contenedor de búsqueda...");
  const searchContainer = document.querySelector(".lexhoy-despachos-search");
  console.log("Contenedor encontrado:", searchContainer);

  if (searchContainer) {
    console.log("Inicializando búsqueda...");
    initializeSearch();
  } else {
    console.log("No se encontró el contenedor de búsqueda");
  }

  // Verificar si existe el formulario de contacto antes de añadir el event listener
  const contactForm = document.getElementById("contact-form");
  if (contactForm) {
    contactForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append("action", "contact_despacho");
      formData.append("_wpnonce", lexhoyDespachos.nonce);

      fetch("/wp-admin/admin-ajax.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Mensaje enviado correctamente");
            this.reset();
          } else {
            alert("Error al enviar el mensaje: " + data.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Error al enviar el mensaje");
        });
    });
  }
});
