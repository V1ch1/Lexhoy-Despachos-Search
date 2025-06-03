// Verificar que el script se está cargando
console.log("Script de búsqueda cargado");

// Función para navegar al despacho
function navigateToDespacho(nombre) {
  const url =
    window.location.origin +
    "/" +
    encodeURIComponent(nombre.toLowerCase().replace(/\s+/g, "-"));
  window.location.href = url;
}

// Función para verificar si los scripts están cargados
function checkScriptsLoaded() {
  return (
    typeof instantsearch !== "undefined" && typeof algoliasearch !== "undefined"
  );
}

// Función para inicializar la búsqueda
function initializeSearch() {
  console.log("Iniciando configuración de búsqueda...");

  // Verificar que los scripts de Algolia están cargados
  if (!checkScriptsLoaded()) {
    console.error("Los scripts de Algolia no están cargados correctamente");
    document.querySelector(".lexhoy-search-container").innerHTML =
      '<div class="error">Error: Los scripts de búsqueda no se cargaron correctamente. Por favor, recarga la página.</div>';
    return;
  }

  // Verificar que tenemos los datos de configuración
  if (
    !lexhoyDespachosData ||
    !lexhoyDespachosData.appId ||
    !lexhoyDespachosData.searchApiKey
  ) {
    console.error("No se encontraron los datos de configuración de Algolia");
    document.querySelector(".lexhoy-search-container").innerHTML =
      '<div class="error">Error: No se encontró la configuración de búsqueda. Por favor, contacta al administrador.</div>';
    return;
  }

  try {
    // Configuración de Algolia
    const searchClient = algoliasearch(
      lexhoyDespachosData.appId,
      lexhoyDespachosData.searchApiKey
    );

    // Inicializar InstantSearch
    const search = instantsearch({
      indexName: lexhoyDespachosData.indexName,
      searchClient,
      routing: true,
    });

    console.log(
      "InstantSearch inicializado con:",
      lexhoyDespachosData.indexName
    );

    // Añadir widgets
    search.addWidgets([
      instantsearch.widgets.searchBox({
        container: "#searchbox",
        placeholder: "Buscar despachos...",
        cssClasses: {
          root: "ais-SearchBox",
          input: "ais-SearchBox-input",
        },
      }),

      instantsearch.widgets.refinementList({
        container: "#city-list",
        attribute: "localidad",
        searchable: true,
        searchablePlaceholder: "Buscar ciudad...",
        limit: 8,
        showMore: true,
        showMoreLimit: 15,
        templates: {
          showMoreText: ({ isShowingMore }) =>
            isShowingMore ? "Mostrar menos" : "Mostrar más",
        },
        cssClasses: {
          root: "ais-RefinementList",
          label: "ais-RefinementList-label",
          searchableInput: "ais-SearchBox-input",
        },
      }),

      instantsearch.widgets.refinementList({
        container: "#province-list",
        attribute: "provincia",
        searchable: true,
        searchablePlaceholder: "Buscar provincia...",
        limit: 8,
        showMore: true,
        showMoreLimit: 15,
        templates: {
          showMoreText: ({ isShowingMore }) =>
            isShowingMore ? "Mostrar menos" : "Mostrar más",
        },
        cssClasses: {
          searchableInput: "ais-SearchBox-input",
        },
      }),

      instantsearch.widgets.refinementList({
        container: "#practice-area-list",
        attribute: "areas_practica",
        searchable: true,
        searchablePlaceholder: "Buscar área de práctica...",
        limit: 8,
        showMore: true,
        showMoreLimit: 1000,
        templates: {
          showMoreText: ({ isShowingMore }) =>
            isShowingMore ? "Mostrar menos" : "Mostrar más",
        },
        cssClasses: {
          searchableInput: "ais-SearchBox-input",
        },
      }),

      instantsearch.widgets.hits({
        container: "#hits",
        templates: {
          empty: `
            <div style="padding: 20px; text-align: center;">
              <p>No se encontraron resultados</p>
              <p>Prueba a modificar los filtros o la búsqueda</p>
            </div>
          `,
          item: `
            <div class="hit" style="padding: 20px; border-bottom: 1px solid #eee; cursor: pointer;" data-nombre="{{nombre}}">
              <h2 style="margin: 0 0 15px 0; font-size: 1.8em; color: #333;">{{nombre}}</h2>
              <p style="margin: 0 0 8px 0; color: #666;"><strong>Localidad:</strong> {{localidad}}</p>
              <p style="margin: 0 0 8px 0; color: #666;"><strong>Provincia:</strong> {{provincia}}</p>
              <p style="margin: 0; color: #666;"><strong>Áreas de Práctica:</strong> {{areas_practica}}</p>
            </div>
          `,
        },
        cssClasses: {
          root: "ais-Hits",
          list: "ais-Hits-list",
          item: "ais-Hits-item",
        },
      }),

      instantsearch.widgets.pagination({
        container: "#pagination",
        cssClasses: {
          root: "ais-Pagination",
          list: "ais-Pagination-list",
          item: "ais-Pagination-item",
          selectedItem: "ais-Pagination-item--selected",
        },
      }),
    ]);

    console.log("Widgets añadidos, iniciando búsqueda...");

    // Iniciar la búsqueda
    search.start();

    // Añadir event listeners a las tarjetas después de que se rendericen
    search.on("render", function () {
      const hits = document.querySelectorAll(".hit");
      hits.forEach((hit) => {
        hit.addEventListener("click", function () {
          const nombre = this.getAttribute("data-nombre");
          navigateToDespacho(nombre);
        });
      });
    });

    console.log("Búsqueda iniciada correctamente");
  } catch (error) {
    console.error("Error al inicializar la búsqueda:", error);
    document.querySelector(
      ".lexhoy-search-container"
    ).innerHTML = `<div class="error">Error al inicializar la búsqueda: ${error.message}</div>`;
  }
}

// Esperar a que el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM cargado");

  // Verificar si los scripts están disponibles
  console.log(
    "Algolia Search disponible:",
    typeof algoliasearch !== "undefined"
  );
  console.log(
    "InstantSearch disponible:",
    typeof instantsearch !== "undefined"
  );
  console.log(
    "Datos de configuración:",
    typeof lexhoyDespachosData !== "undefined"
      ? lexhoyDespachosData
      : "No disponible"
  );

  // Verificar si estamos en la página de búsqueda
  if (document.querySelector(".lexhoy-search-container")) {
    // Inicializar la búsqueda
    initializeSearch();
  }
});

document
  .getElementById("contact-form")
  .addEventListener("submit", function (e) {
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
