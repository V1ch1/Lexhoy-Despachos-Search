<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="lexhoy-despachos-search">
    <!-- Barra de búsqueda -->
    <div id="searchbox"></div>

    <!-- Filtros activos -->
    <div id="current-refinements"></div>

    <!-- Contenedor de resultados -->
    <div class="lexhoy-despachos-layout">
        <!-- Panel de filtros -->
        <div class="lexhoy-despachos-filters">
            <div class="filter-tabs">
                <button class="filter-tab-btn active" data-tab="province">Provincias</button>
                <button class="filter-tab-btn" data-tab="location">Localidades</button>
            </div>
            <div class="filter-tab-panes">
                <div id="province-list" class="filter-tab-pane active"></div>
                <div id="location-list" class="filter-tab-pane"></div>
            </div>
        </div>

        <!-- Resultados -->
        <div class="lexhoy-despachos-main">
            <div id="hits"></div>
            <div id="pagination"></div>
        </div>
    </div>
</div>

<!-- Template para mostrar los resultados -->
<script type="text/html" id="hit-template">
    <div class="hit">
        <div class="hit-content">
            <h2 class="hit-name">
                <a href="/despacho/{{objectID}}">{{{_highlightResult.nombre.value}}}</a>
            </h2>
            <div class="hit-address">
                <p>{{{_highlightResult.direccion.value}}}</p>
                <p>{{{_highlightResult.localidad.value}}}, {{{_highlightResult.provincia.value}}}</p>
            </div>
            <div class="hit-contact">
                {{#telefono}}
                <p><strong>Teléfono:</strong> {{telefono}}</p>
                {{/telefono}}
                {{#email}}
                <p><strong>Email:</strong> {{email}}</p>
                {{/email}}
            </div>
            {{#especialidades}}
            <div class="hit-specialties">
                <strong>Especialidades:</strong>
                <ul>
                    {{#especialidades}}
                    <li>{{.}}</li>
                    {{/especialidades}}
                </ul>
            </div>
            {{/especialidades}}
            <a href="/despacho/{{objectID}}" class="despacho-link">Ver más detalles</a>
        </div>
    </div>
</script>

<!-- Template para cuando no hay resultados -->
<script type="text/html" id="no-results-template">
    <div class="no-results">
        <p>No se encontraron resultados para <q>{{query}}</q>.</p>
        <p>Intenta con otros términos de búsqueda o elimina los filtros.</p>
    </div>
</script>

<?php
// Añadir los datos necesarios para JavaScript
wp_localize_script('lexhoy-despachos-script', 'lexhoy_despachos', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('lexhoy_despachos_search'),
    'i18n' => array(
        'searching' => 'Buscando...',
        'no_results' => 'No se encontraron resultados',
        'error' => 'Error en la búsqueda',
        'connection_error' => 'Error en la conexión'
    )
)); 