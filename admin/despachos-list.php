<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir el autoloader de Composer
$autoloader = dirname(dirname(__FILE__)) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    echo '<div class="error"><p>Error: No se encontró el autoloader de Composer en: ' . esc_html($autoloader) . '</p></div>';
    return;
}

require_once $autoloader;

try {
    // Obtener los datos de Algolia
    $settings = get_option('lexhoy_despachos_settings');
    if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
        echo '<div class="error"><p>Error: Por favor, configure las credenciales de Algolia en la página de configuración.</p></div>';
        return;
    }

    // Verificar que la clase existe
    if (!class_exists('\\Algolia\\AlgoliaSearch\\Api\\SearchClient')) {
        echo '<div class="error"><p>Error: La clase SearchClient de Algolia no está disponible. Por favor, verifique la instalación de Composer.</p></div>';
        echo '<div class="error"><p>Ruta del autoloader: ' . esc_html($autoloader) . '</p></div>';
        echo '<div class="error"><p>¿Existe el autoloader? ' . (file_exists($autoloader) ? 'Sí' : 'No') . '</p></div>';
        echo '<div class="error"><p>Directorio actual: ' . esc_html(__DIR__) . '</p></div>';
        return;
    }

    $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
        $settings['algolia_app_id'],
        $settings['algolia_admin_api_key']
    );

    if (!$client) {
        echo '<div class="error"><p>Error: No se pudo crear el cliente de Algolia.</p></div>';
        return;
    }

    // Obtener parámetros de búsqueda
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $hits_per_page = 20;

    // Configurar parámetros de búsqueda
    $search_params = [
        'query' => $search_query,
        'hitsPerPage' => $hits_per_page,
        'page' => $current_page - 1,
        'attributesToRetrieve' => ['objectID', 'nombre', 'localidad', 'provincia', 'telefono']
    ];

    // Realizar la búsqueda
    $results = $client->searchSingleIndex('lexhoy_despachos_formatted', $search_params);

    if (!isset($results['hits'])) {
        echo '<div class="error"><p>Error: No se pudieron obtener los resultados de Algolia.</p></div>';
        return;
    }

    // Calcular el total de páginas
    $total_hits = $results['nbHits'] ?? 0;
    $total_pages = ceil($total_hits / $hits_per_page);

} catch (Exception $e) {
    echo '<div class="error"><p>Error al conectar con Algolia: ' . esc_html($e->getMessage()) . '</p></div>';
    return;
}
?>

<div class="wrap lexhoy-despachos-admin">
    <h1>Listado de Despachos</h1>
    <!-- Buscador -->
    <form method="get">
        <input type="hidden" name="page" value="lexhoy-despachos">
        <?php wp_nonce_field('lexhoy_despachos_search', 'lexhoy_despachos_nonce'); ?>
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Buscar despachos:</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search_query); ?>">
            <input type="submit" id="search-submit" class="button" value="Buscar">
            <?php if ($search_query): ?>
                <a href="<?php echo admin_url('admin.php?page=lexhoy-despachos'); ?>" class="button">Limpiar búsqueda</a>
            <?php endif; ?>
        </p>
    </form>

    <div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Localidad</th>
                    <th>Provincia</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($results['hits']) && !empty($results['hits'])): ?>
                    <?php foreach ($results['hits'] as $despacho): ?>
                    <tr>
                        <td><?php echo esc_html($despacho['nombre']); ?></td>
                        <td><?php echo esc_html($despacho['localidad']); ?></td>
                        <td><?php echo esc_html($despacho['provincia']); ?></td>
                        <td><?php echo esc_html($despacho['telefono']); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=lexhoy-despachos-edit&id=' . $despacho['objectID']); ?>" class="button button-small">Editar</a>
                            <a href="<?php echo admin_url('admin.php?page=lexhoy-despachos-delete&id=' . $despacho['objectID']); ?>" class="button button-small button-link-delete" onclick="return confirm('¿Estás seguro de que quieres eliminar este despacho?');">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No se encontraron despachos.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_hits, 'lexhoy-despachos'), number_format_i18n($total_hits)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    // Botón "Primera página"
                    if ($current_page > 1) {
                        echo '<a class="first-page button" href="' . esc_url(add_query_arg(['paged' => 1, 's' => $search_query])) . '"><span class="screen-reader-text">Primera página</span><span aria-hidden="true">&laquo;</span></a>';
                    } else {
                        echo '<span class="first-page button disabled" aria-hidden="true">&laquo;</span>';
                    }

                    // Botón "Página anterior"
                    if ($current_page > 1) {
                        echo '<a class="prev-page button" href="' . esc_url(add_query_arg(['paged' => $current_page - 1, 's' => $search_query])) . '"><span class="screen-reader-text">Página anterior</span><span aria-hidden="true">&lsaquo;</span></a>';
                    } else {
                        echo '<span class="prev-page button disabled" aria-hidden="true">&lsaquo;</span>';
                    }

                    // Número de página actual
                    echo '<span class="paging-input">' . $current_page . ' de <span class="total-pages">' . $total_pages . '</span></span>';

                    // Botón "Página siguiente"
                    if ($current_page < $total_pages) {
                        echo '<a class="next-page button" href="' . esc_url(add_query_arg(['paged' => $current_page + 1, 's' => $search_query])) . '"><span class="screen-reader-text">Página siguiente</span><span aria-hidden="true">&rsaquo;</span></a>';
                    } else {
                        echo '<span class="next-page button disabled" aria-hidden="true">&rsaquo;</span>';
                    }

                    // Botón "Última página"
                    if ($current_page < $total_pages) {
                        echo '<a class="last-page button" href="' . esc_url(add_query_arg(['paged' => $total_pages, 's' => $search_query])) . '"><span class="screen-reader-text">Última página</span><span aria-hidden="true">&raquo;</span></a>';
                    } else {
                        echo '<span class="last-page button disabled" aria-hidden="true">&raquo;</span>';
                    }
                    ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-box {
    margin: 1em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.search-box input[type="search"] {
    margin-right: 4px;
    width: 250px;
}
.ais-SearchBox-submit,
.ais-SearchBox-reset {
    display: none !important;
}
</style>

<div class="tabs-navigation">
    <button class="tab-button active" data-tab="info-basica">
        <i class="fas fa-info-circle"></i> Información Básica
    </button>
    <!-- otros botones -->
</div>

<div class="tabs-content">
    <div class="tab-panel active" id="info-basica">
        <!-- contenido -->
    </div>
    <!-- otros paneles -->
</div> 