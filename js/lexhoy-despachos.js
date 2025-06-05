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

  // Inicializar Algolia si estamos en la página de búsqueda
  if ($(".lexhoy-despachos-search").length) {
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
            <div class="despacho-card">
              <div class="despacho-name">{{nombre}}</div>
              <div class="despacho-location">{{localidad}}, {{provincia}}</div>
              <div class="despacho-areas">{{areas_practica}}</div>
              <a href="/{{slug}}" class="despacho-link">Ver más</a>
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

    // Widget de filtros
    search.addWidgets([
      instantsearch.widgets.refinementList({
        container: "#province-list",
        attribute: "provincia",
        limit: 20,
        searchable: true,
        searchablePlaceholder: "Buscar provincia...",
        cssClasses: {
          root: "filter-select",
          list: "filter-list",
          item: "filter-item",
          label: "filter-label",
          checkbox: "filter-checkbox",
          count: "filter-count",
        },
      }),
      instantsearch.widgets.refinementList({
        container: "#location-list",
        attribute: "localidad",
        limit: 20,
        searchable: true,
        searchablePlaceholder: "Buscar localidad...",
        cssClasses: {
          root: "filter-select",
          list: "filter-list",
          item: "filter-item",
          label: "filter-label",
          checkbox: "filter-checkbox",
          count: "filter-count",
        },
      }),
    ]);

    // Crear filtro personalizado para áreas de práctica
    const practiceAreaFilter = {
      init: function (params) {
        this.container = document.querySelector("#practice-area-list");
        this.helper = params.helper;
        this.render();
      },
      render: function () {
        const results = this.helper.lastResults;
        if (!results) return;

        // Obtener todas las áreas de práctica únicas
        const areas = new Set();
        results.hits.forEach((hit) => {
          if (hit.areas_practica && Array.isArray(hit.areas_practica)) {
            hit.areas_practica.forEach((area) => areas.add(area));
          }
        });

        // Crear el HTML del filtro
        const html = Array.from(areas)
          .map(
            (area) => `
          <div class="filter-item">
            <label class="filter-label">
              <input type="checkbox" class="filter-checkbox" value="${area}">
              ${area}
              <span class="filter-count">(${this.getCount(
                area,
                results.hits
              )})</span>
            </label>
          </div>
        `
          )
          .join("");

        const fullHtml = `
          <div class="filter-select">
            <div class="filter-list">
              ${html}
            </div>
          </div>
        `;

        if (this.container) {
          this.container.innerHTML = fullHtml;
        }

        // Añadir event listeners
        this.container
          .querySelectorAll(".filter-checkbox")
          .forEach((checkbox) => {
            checkbox.addEventListener("change", (e) => {
              const area = e.target.value;
              if (e.target.checked) {
                this.helper.addDisjunctiveFacetRefinement(
                  "areas_practica",
                  area
                );
              } else {
                this.helper.removeDisjunctiveFacetRefinement(
                  "areas_practica",
                  area
                );
              }
              this.helper.search();
            });
          });
      },
      getCount: function (area, hits) {
        return hits.filter(
          (hit) =>
            hit.areas_practica &&
            Array.isArray(hit.areas_practica) &&
            hit.areas_practica.includes(area)
        ).length;
      },
    };

    // Añadir el filtro personalizado
    search.addWidgets([practiceAreaFilter]);

    // Widget para mostrar refinamientos actuales
    search.addWidgets([
      instantsearch.widgets.currentRefinements({
        container: "#current-refinements",
      }),
    ]);

    // Añadir listener para depuración
    search.on("render", function () {
      console.log("Estado actual de la búsqueda:", search.helper.state);
      console.log("Resultados:", search.helper.lastResults?.hits);
      console.log("Facets:", search.helper.lastResults?.facets);
      console.log("DisjunctiveFacets:", search.helper.state.disjunctiveFacets);
      console.log("Refinements:", search.helper.state.refinementList);
    });

    // Iniciar la búsqueda
    search.start();

    // Búsqueda por letra
    $(".alphabet-letter").on("click", function () {
      const letter = $(this).data("letter");
      const searchInput = $(".ais-SearchBox-input");
      searchInput.val(letter);

      // Búsqueda simplificada
      search.helper.setQuery(letter).search();

      // Añadir clase activa a la letra seleccionada
      $(".alphabet-letter").removeClass("active");
      $(this).addClass("active");
    });

    // Manejo de pestañas de filtros
    $(".filter-tab-btn").on("click", function () {
      const tabId = $(this).data("tab");

      // Actualizar botones
      $(".filter-tab-btn").removeClass("active");
      $(this).addClass("active");

      // Actualizar contenido
      $(".filter-tab-pane").removeClass("active");
      $(`#${tabId}-list`).addClass("active");
    });
  }
});
