// Función para navegar al despacho
function navigateToDespacho(slug) {
  const url = window.location.origin + "/" + slug;
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
    // Añadir estilos CSS
    const style = document.createElement("style");
    style.textContent = `
      .hits-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        padding: 20px 0;
      }

      .hit-card {
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 20px;
        border-radius: 12px;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 0;
        height: 180px;
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border: 1px solid #eee;
        overflow: hidden;
      }
      
      .hit-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        border-color: #ddd;
      }
      
      .hit-card h3 {
        margin: 0 0 12px 0;
        color: #2c3e50;
        font-size: 1.3em;
        font-weight: 600;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
      }
      
      .hit-card p {
        margin: 6px 0;
        color: #666;
        font-size: 1em;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .hit-card p i {
        color: #3498db;
        flex-shrink: 0;
      }
      
      .areas-practica {
        margin-top: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        overflow: visible;
        justify-content: flex-start;
    }

      .area-tag {
        display: inline-block;
        background: #f8f9fa;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.85em;
        color: #555;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
        white-space: nowrap;
      }

      .area-tag:hover {
        background: #e9ecef;
      }

      .search-input {
        width: 100%;
        padding: 15px 20px;
        font-size: 1.1em;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.3s ease;
      }

      .search-input:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        outline: none;
      }

      .pagination-list {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
        padding: 0;
        list-style: none;
      }

      .pagination-item {
        padding: 8px 16px;
        border-radius: 6px;
        background: white;
        border: 1px solid #e9ecef;
        cursor: pointer;
        transition: all 0.2s ease;
      }

      .pagination-item:hover {
        background: #f8f9fa;
      }

      .pagination-item--selected {
        background: #3498db;
        color: white;
        border-color: #3498db;
      }

      .pagination-item--disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }

      @media (max-width: 768px) {
        .hits-list {
          grid-template-columns: 1fr;
    }
        
        .hit-card {
          height: 160px;
        }
      }
    `;
    document.head.appendChild(style);

    // Configuración de Algolia
    const searchClient = algoliasearch(
      lexhoyDespachosData.appId,
      lexhoyDespachosData.searchApiKey
    );

    // Crear la instancia de InstantSearch
    const search = instantsearch({
      indexName: lexhoyDespachosData.indexName,
      searchClient,
      routing: true,
    });

    // Widget de búsqueda
    search.addWidgets([
      instantsearch.widgets.searchBox({
        container: "#searchbox",
        placeholder: "Buscar despachos...",
        showReset: false,
        showSubmit: false,
        cssClasses: {
          input: "search-input",
        },
      }),
    ]);

    // Widget de hits (resultados)
    search.addWidgets([
      instantsearch.widgets.hits({
        container: "#hits",
        templates: {
          item: function (hit) {
            return `
              <div class="hit-card" data-hit='${JSON.stringify(hit)}'>
                <h3>${hit._highlightResult.nombre.value}</h3>
                ${
                  hit.localidad
                    ? `<p><i class="fas fa-city"></i> ${hit.localidad}</p>`
                    : ""
                }
                ${
                  hit.areas_practica
                    ? `
                  <div class="areas-practica">
                    ${hit.areas_practica
                      .map((area) => `<span class="area-tag">${area}</span>`)
                      .join("")}
                  </div>
                `
                    : ""
                }
              </div>
            `;
          },
          empty: `
            <div class="no-results">
              <p>No se encontraron resultados para tu búsqueda.</p>
            </div>
          `,
        },
        cssClasses: {
          list: "hits-list",
          item: "hit-item",
        },
      }),
    ]);

    // Widget de paginación
    search.addWidgets([
      instantsearch.widgets.pagination({
        container: "#pagination",
        cssClasses: {
          list: "pagination-list",
          item: "pagination-item",
          selectedItem: "pagination-item--selected",
          disabledItem: "pagination-item--disabled",
        },
      }),
    ]);

    // Widget de refinamiento por provincia
    search.addWidgets([
      instantsearch.widgets.refinementList({
        container: "#province-list",
        attribute: "provincia",
        searchable: true,
        searchablePlaceholder: "Buscar provincia...",
        cssClasses: {
          list: "refinement-list",
          item: "refinement-item",
          selectedItem: "refinement-item--selected",
          label: "refinement-label",
          checkbox: "refinement-checkbox",
          count: "refinement-count",
        },
      }),
    ]);

    // Widget de refinamiento por localidad
    search.addWidgets([
      instantsearch.widgets.refinementList({
        container: "#city-list",
        attribute: "localidad",
        searchable: true,
        searchablePlaceholder: "Buscar localidad...",
        cssClasses: {
          list: "refinement-list",
          item: "refinement-item",
          selectedItem: "refinement-item--selected",
          label: "refinement-label",
          checkbox: "refinement-checkbox",
          count: "refinement-count",
        },
      }),
    ]);

    // Widget de refinamiento por área de práctica
    search.addWidgets([
      instantsearch.widgets.refinementList({
        container: "#practice-area-list",
        attribute: "areas_practica",
        searchable: true,
        searchablePlaceholder: "Buscar área de práctica...",
        cssClasses: {
          list: "refinement-list",
          item: "refinement-item",
          selectedItem: "refinement-item--selected",
          label: "refinement-label",
          checkbox: "refinement-checkbox",
          count: "refinement-count",
        },
      }),
    ]);

    // Iniciar la búsqueda
    search.start();

    // Añadir evento de clic a las tarjetas
    document.addEventListener("click", function (e) {
      const hitCard = e.target.closest(".hit-card");
      if (hitCard) {
        const hit = JSON.parse(hitCard.dataset.hit);
        // Guardar el nombre del despacho en localStorage
        localStorage.setItem(
          "selected_despacho",
          JSON.stringify({
            nombre: hit.nombre,
            slug: hit.slug,
          })
        );
        // Navegar al despacho
        navigateToDespacho(hit.slug);
      }
    });
  } catch (error) {
    console.error("Error al inicializar la búsqueda:", error);
    document.querySelector(
      ".lexhoy-search-container"
    ).innerHTML = `<div class="error">Error al inicializar la búsqueda: ${error.message}</div>`;
  }
}

// Esperar a que el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  // Verificar si estamos en la página de búsqueda
  if (document.querySelector(".lexhoy-search-container")) {
    // Inicializar la búsqueda
    initializeSearch();
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
