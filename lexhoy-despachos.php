<?php
/**
 * Plugin Name: Lexhoy Despachos Search
 * Plugin URI: https://lexhoy.com
 * Description: Plugin para gestionar y buscar despachos de abogados integrado con Algolia
 * Version: 2.2.0
 * Author: José Blanco
 * Author URI: https://blancoyenbatea.com
 * Text Domain: lexhoy-despachos-search
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar que WordPress está cargado
if (!function_exists('add_action')) {
    exit;
}

// Cargar funciones de WordPress
require_once(ABSPATH . 'wp-includes/plugin.php');
require_once(ABSPATH . 'wp-includes/link-template.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');

// Definir constantes
define('LEXHOY_DESPACHOS_VERSION', '2.2.0');
define('LEXHOY_DESPACHOS_PLUGIN_DIR', dirname(__FILE__));
define('LEXHOY_DESPACHOS_PLUGIN_URL', plugins_url('', __FILE__));
define('LEXHOY_DESPACHOS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Clase principal del plugin
class LexhoyDespachos {
    private static $instance = null;
    private $settings;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        try {
            $this->settings = get_option('lexhoy_despachos_settings', array());
            $this->init_hooks();
            
            // Solo actualizar slugs si estamos en el admin y es la primera carga
            if (is_admin() && !get_option('lexhoy_despachos_slugs_updated')) {
                $this->update_existing_despachos_slugs();
                update_option('lexhoy_despachos_slugs_updated', true);
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }
    }

    private function init_hooks() {
        try {
            // Hooks de administración
            if (is_admin()) {
                add_action('admin_menu', array($this, 'add_admin_menu'));
                add_action('admin_init', array($this, 'register_settings'));
                add_action('admin_post_lexhoy_despachos_update', array($this, 'handle_despacho_update'));
            }

            // Hooks públicos
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_shortcode('lexhoy_despachos_search', array($this, 'render_search_form'));
            add_shortcode('lexhoy_despachos', array($this, 'render_search_form'));

            // Registrar el Custom Post Type para despachos
            add_action('init', array($this, 'register_despacho_post_type'));
            
            // Añadir reglas de reescritura
            add_action('init', array($this, 'add_rewrite_rules'));
            
            // Manejar la visualización de despachos
            add_action('template_redirect', array($this, 'handle_despacho_template'));
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }
    }

    public function add_admin_menu() {
        try {
            add_menu_page(
                'Lexhoy Despachos',
                'Despachos',
                'manage_options',
                'lexhoy-despachos',
                array($this, 'despachos_list_page'),
                'dashicons-building',
                30
            );

            add_submenu_page(
                'lexhoy-despachos',
                'Listado de Despachos',
                'Listado',
                'manage_options',
                'lexhoy-despachos',
                array($this, 'despachos_list_page')
            );

            // Página de edición (oculta en el menú)
            add_submenu_page(
                null, // Parent slug null para ocultar del menú
                'Editar Despacho',
                'Editar Despacho',
                'manage_options',
                'lexhoy-despachos-edit',
                array($this, 'despachos_edit_page')
            );

            add_submenu_page(
                'lexhoy-despachos',
                'Áreas de Práctica',
                'Áreas de Práctica',
                'manage_options',
                'lexhoy-areas-practica',
                'lexhoy_areas_practica_page'
            );

            add_submenu_page(
                'lexhoy-despachos',
                'Configuración de Algolia',
                'Algolia',
                'manage_options',
                'lexhoy-despachos-algolia',
                array($this, 'algolia_page')
            );

            add_submenu_page(
                'lexhoy-despachos',
                'Shortcode',
                'Shortcode',
                'manage_options',
                'lexhoy-despachos-shortcode',
                array($this, 'shortcode_page')
            );
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }
    }

    public function despachos_list_page() {
        try {
            if (!current_user_can('manage_options')) {
                return;
            }
            
            // Verificar slugs
            $this->verify_slugs();
            
            $admin_page_path = dirname(__FILE__) . '/admin/despachos-list.php';
            if (file_exists($admin_page_path)) {
                require_once $admin_page_path;
            } else {
                echo '<div class="error"><p>Error: No se encontró la página de listado de despachos en: ' . esc_html($admin_page_path) . '</p></div>';
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            echo '<div class="error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public function despachos_edit_page() {
        try {
            if (!current_user_can('manage_options')) {
                return;
            }
            
            $admin_page_path = dirname(__FILE__) . '/admin/despachos-edit.php';
            if (file_exists($admin_page_path)) {
                require_once $admin_page_path;
            } else {
                echo '<div class="error"><p>Error: No se encontró la página de edición de despachos en: ' . esc_html($admin_page_path) . '</p></div>';
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            echo '<div class="error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public function shortcode_page() {
        try {
            if (!current_user_can('manage_options')) {
                return;
            }
            
            $admin_page_path = dirname(__FILE__) . '/admin/shortcode-page.php';
            if (file_exists($admin_page_path)) {
                require_once $admin_page_path;
            } else {
                echo '<div class="error"><p>Error: No se encontró la página de shortcode en: ' . esc_html($admin_page_path) . '</p></div>';
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            echo '<div class="error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public function algolia_page() {
        try {
            if (!current_user_can('manage_options')) {
                return;
            }
            
            // Mostrar mensajes de error/éxito
            settings_errors('lexhoy_despachos_messages');
            
            $admin_page_path = dirname(__FILE__) . '/admin/algolia-page.php';
            if (file_exists($admin_page_path)) {
                require_once $admin_page_path;
            } else {
                echo '<div class="error"><p>Error: No se encontró la página de administración de Algolia en: ' . esc_html($admin_page_path) . '</p></div>';
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            echo '<div class="error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public function register_settings() {
        try {
            register_setting('lexhoy_despachos_options', 'lexhoy_despachos_settings', array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            ));
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['algolia_app_id'])) {
            $sanitized['algolia_app_id'] = sanitize_text_field($input['algolia_app_id']);
        }
        
        if (isset($input['algolia_search_api_key'])) {
            $sanitized['algolia_search_api_key'] = sanitize_text_field($input['algolia_search_api_key']);
        }

        if (isset($input['algolia_write_api_key'])) {
            $sanitized['algolia_write_api_key'] = sanitize_text_field($input['algolia_write_api_key']);
        }

        if (isset($input['algolia_admin_api_key'])) {
            $sanitized['algolia_admin_api_key'] = sanitize_text_field($input['algolia_admin_api_key']);
        }

        if (isset($input['algolia_usage_api_key'])) {
            $sanitized['algolia_usage_api_key'] = sanitize_text_field($input['algolia_usage_api_key']);
        }

        if (isset($input['algolia_monitoring_api_key'])) {
            $sanitized['algolia_monitoring_api_key'] = sanitize_text_field($input['algolia_monitoring_api_key']);
        }

        // Añadir mensaje de éxito
        add_settings_error(
            'lexhoy_despachos_messages',
            'lexhoy_despachos_message',
            'Configuración guardada correctamente',
            'updated'
        );

        return $sanitized;
    }

    public function admin_page() {
        try {
            if (!current_user_can('manage_options')) {
                return;
            }
            
            // Mostrar mensajes de error/éxito
            settings_errors('lexhoy_despachos_messages');
            
            $admin_page_path = dirname(__FILE__) . '/admin/admin-page.php';
            if (file_exists($admin_page_path)) {
                require_once $admin_page_path;
            } else {
                echo '<div class="error"><p>Error: No se encontró la página de administración en: ' . esc_html($admin_page_path) . '</p></div>';
                // Mostrar el formulario directamente si no se encuentra el archivo
                ?>
                <div class="wrap lexhoy-despachos-admin">
                    <h1>Lexhoy Despachos</h1>
                    <div class="card">
                        <h2>Configuración de Algolia</h2>
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('lexhoy_despachos_options');
                            do_settings_sections('lexhoy_despachos_options');
                            ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="algolia_app_id">Algolia Application ID</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="algolia_app_id" 
                                               name="lexhoy_despachos_settings[algolia_app_id]" 
                                               value="<?php echo esc_attr($this->settings['algolia_app_id'] ?? ''); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="algolia_api_key">Algolia API Key</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="algolia_api_key" 
                                               name="lexhoy_despachos_settings[algolia_api_key]" 
                                               value="<?php echo esc_attr($this->settings['algolia_api_key'] ?? ''); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button('Guardar configuración'); ?>
                        </form>
                    </div>

                    <div class="card">
                        <h2>Uso del Plugin</h2>
                        <p>Para mostrar el buscador de despachos, usa el siguiente shortcode en cualquier página o post:</p>
                        <code>[lexhoy_despachos_search]</code>
                    </div>
                </div>
                <?php
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            echo '<div class="error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public function enqueue_scripts() {
        try {
            // Obtener la URL base del plugin
            $plugin_url = plugin_dir_url(__FILE__);
            
            // Estilos de Algolia InstantSearch
            wp_enqueue_style(
                'algolia-instantsearch',
                'https://cdn.jsdelivr.net/npm/instantsearch.css@8.1.0/themes/satellite-min.css',
                array(),
                '8.1.0'
            );

            // Script de jQuery (asegurarnos de que está cargado)
            wp_enqueue_script('jquery');

            // Script de Algolia Search
            wp_enqueue_script(
                'algolia-search',
                'https://cdn.jsdelivr.net/npm/algoliasearch@4.22.1/dist/algoliasearch-lite.umd.js',
                array('jquery'),
                '4.22.1',
                false
            );

            // Script de Algolia InstantSearch
            wp_enqueue_script(
                'algolia-instantsearch',
                'https://cdn.jsdelivr.net/npm/instantsearch.js@4.60.0/dist/instantsearch.production.min.js',
                array('jquery', 'algolia-search'),
                '4.60.0',
                false
            );

            // Nuestro script personalizado
            $script_path = LEXHOY_DESPACHOS_PLUGIN_DIR . '/assets/js/script.js';
            $script_url = LEXHOY_DESPACHOS_PLUGIN_URL . '/assets/js/script.js';
            
            if (file_exists($script_path)) {
                wp_enqueue_script(
                    'lexhoy-despachos-script',
                    $script_url,
                    array('jquery', 'algolia-search', 'algolia-instantsearch'),
                    LEXHOY_DESPACHOS_VERSION,
                    false
                );

                // Obtener las credenciales de Algolia desde la configuración
                $settings = get_option('lexhoy_despachos_settings');
                
                // Añadir datos para el script
                wp_localize_script('lexhoy-despachos-script', 'lexhoyDespachosData', array(
                    'appId' => $settings['algolia_app_id'] ?? '',
                    'searchApiKey' => $settings['algolia_search_api_key'] ?? '',
                    'indexName' => 'lexhoy_despachos_formatted'
                ));
            } else {
                error_log('Lexhoy Despachos Error: No se encontró el archivo script.js en: ' . $script_path);
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }
    }

    public function render_search_form() {
        try {
            ob_start();
            ?>
            <style>
                .lexhoy-search-container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                }
                
                .lexhoy-search-layout {
                    display: flex;
                    gap: 20px;
                }
                
                .lexhoy-filters-panel {
                    flex: 0 0 300px;
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                }
                
                .lexhoy-results-panel {
                    flex: 1;
                }

                .filter-section {
                    margin-bottom: 25px;
                }

                .filter-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .filter-content {
                    padding: 10px 0;
                }

                .filter-toggle {
                    display: none; /* Oculto por defecto en escritorio */
                }
                
                /* Estilos para móvil */
                @media (max-width: 768px) {
                    .lexhoy-search-layout {
                        flex-direction: column;
                    }
                    
                    .lexhoy-filters-panel {
                        flex: none;
                        width: 100%;
                        margin-bottom: 20px;
                    }
                    
                    .lexhoy-results-panel {
                        width: 100%;
                    }

                    .filter-header {
                        cursor: pointer;
                        padding: 10px;
                        background: #fff;
                        border-radius: 4px;
                        transition: background-color 0.3s;
                    }

                    .filter-header:hover {
                        background: #f0f0f0;
                    }

                    .filter-toggle {
                        display: block;
                        width: 20px;
                        height: 20px;
                        position: relative;
                        transition: transform 0.3s;
                    }

                    .filter-toggle::before,
                    .filter-toggle::after {
                        content: '';
                        position: absolute;
                        background: #666;
                        transition: transform 0.3s;
                    }

                    .filter-toggle::before {
                        width: 2px;
                        height: 12px;
                        top: 4px;
                        left: 9px;
                    }

                    .filter-toggle::after {
                        width: 12px;
                        height: 2px;
                        top: 9px;
                        left: 4px;
                    }

                    .filter-section.active .filter-toggle::before {
                        transform: rotate(90deg);
                    }

                    .filter-content {
                        display: none;
                        padding: 10px;
                        background: #fff;
                        border-radius: 0 0 4px 4px;
                    }

                    .filter-section.active .filter-content {
                        display: block;
                    }

                    .filter-header {
                        margin-bottom: 0;
                        border-radius: 4px 4px 0 0;
                    }

                    .filter-section.active .filter-header {
                        border-radius: 4px 4px 0 0;
                    }
                }
            </style>

            <div class="ais-InstantSearch lexhoy-search-container">
                <!-- Barra de búsqueda -->
                <div id="searchbox" style="margin-bottom: 20px; min-height: 50px;"></div>

                <!-- Layout principal -->
                <div class="lexhoy-search-layout">
                    <!-- Panel de filtros -->
                    <div class="lexhoy-filters-panel">
                        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333; font-size: 1.4em; font-weight: 600; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">Filtros</h3>
                        
                        <!-- Filtro de Provincia -->
                        <div class="filter-section">
                            <div class="filter-header">
                                <h4 style="margin: 0; color: #555; font-size: 1em;">Provincia</h4>
                                <div class="filter-toggle"></div>
                            </div>
                            <div class="filter-content">
                                <div id="province-list"></div>
                            </div>
                        </div>

                        <!-- Filtro de Localidad -->
                        <div class="filter-section">
                            <div class="filter-header">
                                <h4 style="margin: 0; color: #555; font-size: 1em;">Localidad</h4>
                                <div class="filter-toggle"></div>
                            </div>
                            <div class="filter-content">
                                <div id="city-list"></div>
                            </div>
                        </div>

                        <!-- Filtro de Área de Práctica -->
                        <div class="filter-section">
                            <div class="filter-header">
                                <h4 style="margin: 0; color: #555; font-size: 1em;">Área de Práctica</h4>
                                <div class="filter-toggle"></div>
                            </div>
                            <div class="filter-content">
                                <div id="practice-area-list"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido principal -->
                    <div class="lexhoy-results-panel">
                        <div id="hits" style="background: #fff; border-radius: 8px;"></div>
                        <div id="pagination" style="margin-top: 20px;"></div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Solo añadir el evento click en móvil
                    if (window.innerWidth <= 768) {
                        const filterHeaders = document.querySelectorAll('.filter-header');
                        
                        filterHeaders.forEach(header => {
                            header.addEventListener('click', function() {
                                const section = this.parentElement;
                                section.classList.toggle('active');
                            });
                        });
                    }
                });
            </script>
            <?php
            $html = ob_get_clean();
            error_log('Lexhoy Despachos - HTML generado: ' . $html);
            return $html;
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            return '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }

    public function handle_despacho_update() {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tienes permisos para realizar esta acción.');
                return;
            }

            if (!isset($_POST['lexhoy_despachos_nonce']) || !wp_verify_nonce($_POST['lexhoy_despachos_nonce'], 'lexhoy_despachos_edit')) {
                wp_send_json_error('Verificación de seguridad fallida.');
                return;
            }

            $objectID = sanitize_text_field($_POST['objectID']);
            if (empty($objectID)) {
                wp_send_json_error('ID de despacho no válido.');
                return;
            }

            // Obtener la pestaña actual
            $current_tab = isset($_POST['current_tab']) ? sanitize_text_field($_POST['current_tab']) : 'info-basica';

            // Obtener los datos de Algolia
            $settings = get_option('lexhoy_despachos_settings');
            if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
                wp_send_json_error('Por favor, configure las credenciales de Algolia en la página de configuración.');
                return;
            }

            // Incluir el autoloader de Composer
            $autoloader = dirname(__FILE__) . '/vendor/autoload.php';
            if (!file_exists($autoloader)) {
                wp_send_json_error('Error: No se encontró el autoloader de Composer.');
                return;
            }
            require_once $autoloader;

            $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_admin_api_key']
            );

            // Obtener el objeto actual primero
            $despacho_actual = $client->getObject('lexhoy_despachos_formatted', $objectID);

            // Preparar los datos actualizados
            $despacho_data = array(
                'objectID' => $objectID,
                'nombre' => sanitize_text_field($_POST['nombre']),
                'slug' => !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($_POST['nombre']),
                'direccion' => sanitize_text_field($_POST['direccion']),
                'localidad' => sanitize_text_field($_POST['localidad']),
                'provincia' => sanitize_text_field($_POST['provincia']),
                'codigo_postal' => sanitize_text_field($_POST['codigo_postal']),
                'telefono' => sanitize_text_field($_POST['telefono']),
                'email' => sanitize_email($_POST['email']),
                'web' => esc_url_raw($_POST['web']),
                'areas_practica' => isset($_POST['areas_practica']) ? (array)$_POST['areas_practica'] : [],
                'especialidades' => array_filter(array_map('trim', explode("\n", sanitize_textarea_field($_POST['especialidades'])))),
                'horario' => array_map('sanitize_text_field', $_POST['horario']),
                'redes_sociales' => array_map('esc_url_raw', $_POST['redes_sociales']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'experiencia' => sanitize_textarea_field($_POST['experiencia']),
                'tamaño_despacho' => sanitize_text_field($_POST['tamaño_despacho']),
                'año_fundacion' => intval($_POST['año_fundacion']),
                'estado_verificacion' => sanitize_text_field($_POST['estado_verificacion']),
                'estado_registro' => sanitize_text_field($_POST['estado_registro']),
                'ultima_actualizacion' => date('d-m-Y')
            );

            // Asegurarse de que todos los campos existentes se mantengan
            foreach ($despacho_actual as $key => $value) {
                if (!isset($despacho_data[$key])) {
                    $despacho_data[$key] = $value;
                }
            }

            // Asegurarse de que el slug siempre esté presente
            if (empty($despacho_data['slug'])) {
                $despacho_data['slug'] = sanitize_title($despacho_data['nombre']);
            }

            // Actualizar el objeto en Algolia
            $client->saveObject('lexhoy_despachos_formatted', $despacho_data);

            // Esperar a que Algolia se actualice
            sleep(2);

            // Verificar que los datos se actualizaron correctamente
            $despacho_actualizado = $client->getObject('lexhoy_despachos_formatted', $objectID);
            if (!$despacho_actualizado) {
                throw new Exception('Error al verificar la actualización en Algolia');
            }

            // Redirigir a la página de edición con la pestaña activa
            wp_redirect(add_query_arg(
                array(
                    'page' => 'lexhoy-despachos-edit',
                    'id' => $objectID,
                    'tab' => $current_tab,
                    'updated' => '1'
                ),
                admin_url('admin.php')
            ));
            exit;

        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            wp_send_json_error('Error al actualizar el despacho: ' . $e->getMessage());
        }
    }

    public function get_despacho($objectID) {
        try {
            $client = $this->get_algolia_client();
            $index = $client->initIndex('lexhoy_despachos_formatted');
            
            // Intentar obtener el objeto con reintentos
            $maxRetries = 3;
            $retryDelay = 1; // segundos
            $despacho = null;
            
            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    $despacho = $index->getObject($objectID);
                    if ($despacho) {
                        break;
                    }
                } catch (Exception $e) {
                    if ($i < $maxRetries - 1) {
                        sleep($retryDelay);
                        continue;
                    }
                    throw $e;
                }
            }
            
            if (!$despacho) {
                throw new Exception('No se pudo obtener el objeto después de varios intentos');
            }
            
            return $despacho;
        } catch (Exception $e) {
            error_log('Error al obtener despacho de Algolia: ' . $e->getMessage());
            return null;
        }
    }

    public function get_despacho_by_slug($slug) {
        try {
            $index = $this->client->initIndex($this->index_name);
            $response = $index->search('', [
                'filters' => 'slug:' . $slug,
                'hitsPerPage' => 1
            ]);

            if (!empty($response['hits'])) {
                return $response['hits'][0];
            }

            return null;
        } catch (Exception $e) {
            error_log('Error al buscar despacho por slug: ' . $e->getMessage());
            return null;
        }
    }

    public function register_despacho_post_type() {
        $labels = array(
            'name'               => 'Despachos',
            'singular_name'      => 'Despacho',
            'menu_name'          => 'Despachos',
            'add_new'            => 'Añadir Nuevo',
            'add_new_item'       => 'Añadir Nuevo Despacho',
            'edit_item'          => 'Editar Despacho',
            'new_item'           => 'Nuevo Despacho',
            'view_item'          => 'Ver Despacho',
            'search_items'       => 'Buscar Despachos',
            'not_found'          => 'No se encontraron despachos',
            'not_found_in_trash' => 'No se encontraron despachos en la papelera'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => false, // Ocultamos la UI de WordPress ya que usamos nuestra propia
            'show_in_menu'        => false,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'despacho'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor', 'thumbnail')
        );

        register_post_type('despacho', $args);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^despacho/([^/]+)/?$',
            'index.php?despacho_slug=$matches[1]',
            'top'
        );
        add_rewrite_tag('%despacho_slug%', '([^&]+)');
    }

    public function handle_despacho_template() {
        global $wp_query;

        // Verificar si estamos en una página de despacho usando el query var personalizado
        $despacho_slug = get_query_var('despacho_slug');
        if (!empty($despacho_slug)) {
            // Obtener los datos de Algolia
            $settings = get_option('lexhoy_despachos_settings');
            if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
                wp_die('Error: Por favor, configure las credenciales de Algolia en la página de configuración.');
            }

            try {
                // Incluir el autoloader de Composer
                $autoloader = dirname(__FILE__) . '/vendor/autoload.php';
                if (!file_exists($autoloader)) {
                    wp_die('Error: No se encontró el autoloader de Composer.');
                }
                require_once $autoloader;

                // Crear el cliente de Algolia
                $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                    $settings['algolia_app_id'],
                    $settings['algolia_admin_api_key']
                );

                // Buscar el despacho por slug
                $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                    'filters' => 'slug:' . $despacho_slug,
                    'hitsPerPage' => 1
                ]);

                if (empty($results['hits'])) {
                    wp_die('Despacho no encontrado');
                }

                $despacho = $results['hits'][0];

                // Cargar la plantilla del despacho
                include LEXHOY_DESPACHOS_PLUGIN_DIR . '/templates/despacho-single.php';
                exit;

            } catch (Exception $e) {
                wp_die('Error al obtener los datos del despacho: ' . $e->getMessage());
            }
        }
    }

    public function update_existing_despachos_slugs() {
        try {
            // Incluir el autoloader de Composer
            $autoloader = dirname(__FILE__) . '/vendor/autoload.php';
            if (!file_exists($autoloader)) {
                error_log('Error: No se encontró el autoloader de Composer.');
                return false;
            }
            require_once $autoloader;

            $settings = get_option('lexhoy_despachos_settings');
            if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
                return false;
            }

            $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_admin_api_key']
            );

            // Obtener todos los despachos
            $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                'hitsPerPage' => 1000
            ]);

            $updates = [];
            foreach ($results['hits'] as $despacho) {
                if (empty($despacho['slug'])) {
                    $despacho['slug'] = sanitize_title($despacho['nombre']);
                    $updates[] = $despacho;
                }
            }

            if (!empty($updates)) {
                $client->saveObjects('lexhoy_despachos_formatted', $updates);
            }

            return true;
        } catch (Exception $e) {
            error_log('Error al actualizar slugs: ' . $e->getMessage());
            return false;
        }
    }

    public function verify_slugs() {
        try {
            // Incluir el autoloader de Composer
            $autoloader = dirname(__FILE__) . '/vendor/autoload.php';
            if (!file_exists($autoloader)) {
                return false;
            }
            require_once $autoloader;

            $settings = get_option('lexhoy_despachos_settings');
            if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
                return false;
            }

            $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_admin_api_key']
            );

            // Obtener todos los despachos
            $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                'hitsPerPage' => 1000
            ]);

            $despachos_sin_slug = [];
            foreach ($results['hits'] as $despacho) {
                if (empty($despacho['slug'])) {
                    $despachos_sin_slug[] = [
                        'objectID' => $despacho['objectID'],
                        'nombre' => $despacho['nombre']
                    ];
                }
            }

            if (!empty($despachos_sin_slug)) {
                echo '<div class="notice notice-warning">';
                echo '<p>Se encontraron ' . count($despachos_sin_slug) . ' despachos sin slug:</p>';
                echo '<ul>';
                foreach ($despachos_sin_slug as $despacho) {
                    echo '<li>ID: ' . esc_html($despacho['objectID']) . ' - Nombre: ' . esc_html($despacho['nombre']) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-success">';
                echo '<p>Todos los despachos tienen slug asignado.</p>';
                echo '</div>';
            }

            return true;
        } catch (Exception $e) {
            error_log('Error al verificar slugs: ' . $e->getMessage());
            return false;
        }
    }
}

// Inicializar el plugin
function lexhoy_despachos_init() {
    try {
        return LexhoyDespachos::get_instance();
    } catch (Exception $e) {
        error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        return null;
    }
}

add_action('plugins_loaded', 'lexhoy_despachos_init');

// Función para mostrar la página de áreas de práctica
function lexhoy_areas_practica_page() {
    require_once plugin_dir_path(__FILE__) . 'admin/areas-practica.php';
} 