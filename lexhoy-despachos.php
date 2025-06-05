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
 * Update URI: https://github.com/V1ch1/Lexhoy-Despachos-Search
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Log de prueba directo
error_log('=== PRUEBA DE LOG DIRECTO ===');
error_log('Plugin Lexhoy Despachos iniciado');
error_log('Ruta del plugin: ' . __FILE__);
error_log('Ruta del debug.log: C:/Users/blanc/Local Sites/lexhoy/app/public/debug.log');

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
define('LEXHOY_DESPACHOS_GITHUB_REPO', 'V1ch1/Lexhoy-Despachos-Search');

// Clase para manejar las actualizaciones
class LexhoyDespachosUpdater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $github_response;
    private $access_token;

    public function __construct($file) {
        $this->file = $file;
        $this->plugin = plugin_basename($file);
        $this->basename = plugin_basename($file);
        $this->active = is_plugin_active($this->basename);
    }

    public function init() {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    public function modify_transient($transient) {
        if (!$transient) return $transient;

        // Obtener la última versión de GitHub
        $response = wp_remote_get('https://api.github.com/repos/' . LEXHOY_DESPACHOS_GITHUB_REPO . '/releases/latest');
        
        if (is_wp_error($response)) {
            return $transient;
        }

        $data = json_decode(wp_remote_retrieve_body($response));
        
        if (!$data) {
            return $transient;
        }

        // Comparar versiones
        if (version_compare(LEXHOY_DESPACHOS_VERSION, $data->tag_name, '<')) {
            $plugin_data = get_plugin_data($this->file);
            
            $transient->response[$this->basename] = (object) array(
                'slug' => dirname($this->basename),
                'new_version' => $data->tag_name,
                'url' => $data->html_url,
                'package' => $data->zipball_url,
                'requires' => $plugin_data['RequiresWP'],
                'tested' => $plugin_data['TestedUpTo'],
                'readme' => $data->body
            );
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') return $result;
        if (!isset($args->slug) || $args->slug !== dirname($this->basename)) return $result;

        // Obtener información del plugin desde GitHub
        $response = wp_remote_get('https://api.github.com/repos/' . LEXHOY_DESPACHOS_GITHUB_REPO . '/releases/latest');
        
        if (is_wp_error($response)) {
            return $result;
        }

        $data = json_decode(wp_remote_retrieve_body($response));
        
        if (!$data) {
            return $result;
        }

        $plugin_data = get_plugin_data($this->file);

        return (object) array(
            'name' => $plugin_data['Name'],
            'slug' => dirname($this->basename),
            'version' => $data->tag_name,
            'author' => $plugin_data['Author'],
            'author_profile' => $plugin_data['AuthorURI'],
            'last_updated' => $data->published_at,
            'homepage' => $plugin_data['PluginURI'],
            'short_description' => $plugin_data['Description'],
            'sections' => array(
                'description' => $data->body,
                'changelog' => $data->body
            ),
            'download_link' => $data->zipball_url
        );
    }

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->active) {
            activate_plugin($this->basename);
        }

        return $result;
    }
}

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

    public function __construct() {
        try {
            $this->settings = get_option('lexhoy_despachos_settings', array());
            $this->init_hooks();
            
            // Solo actualizar slugs si estamos en el admin y es la primera carga
            if (is_admin() && !get_option('lexhoy_despachos_slugs_updated')) {
                $this->update_existing_despachos_slugs();
                update_option('lexhoy_despachos_slugs_updated', true);
            }

            // Copiar la plantilla al tema si no existe
            $this->copy_template_to_theme();

            // Registrar el endpoint AJAX para el formulario de contacto
            add_action('wp_ajax_despacho_contact_form', array($this, 'handle_contact_form'));
            add_action('wp_ajax_nopriv_despacho_contact_form', array($this, 'handle_contact_form'));
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }
    }

    private function copy_template_to_theme() {
        try {
            // Ruta de la plantilla en el plugin
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/despacho-single.php';
            
            // Ruta de la plantilla en el tema Twenty Twenty-Five
            $theme_template = get_template_directory() . '/single-despacho.php';
            
            error_log('Lexhoy Despachos - Ruta de la plantilla del plugin: ' . $plugin_template);
            error_log('Lexhoy Despachos - Ruta de la plantilla del tema: ' . $theme_template);
            
            // Verificar si la plantilla del plugin existe
            if (!file_exists($plugin_template)) {
                error_log('Lexhoy Despachos Error: No se encontró la plantilla en el plugin');
                return;
            }
            
            // Si la plantilla no existe en el tema o es más antigua que la del plugin, copiarla
            if (!file_exists($theme_template) || filemtime($plugin_template) > filemtime($theme_template)) {
                error_log('Lexhoy Despachos - Copiando plantilla al tema Twenty Twenty-Five');
                
                // Intentar copiar el archivo
                if (copy($plugin_template, $theme_template)) {
                    error_log('Lexhoy Despachos - Plantilla copiada exitosamente');
                    
                    // Asegurarse de que los permisos sean correctos
                    chmod($theme_template, 0644);
                    
                    // Limpiar la caché de WordPress
                    wp_cache_flush();
                    
                    // Forzar la recarga de las reglas de reescritura
                    flush_rewrite_rules();
                } else {
                    error_log('Lexhoy Despachos Error: No se pudo copiar la plantilla');
                }
            } else {
                error_log('Lexhoy Despachos - La plantilla del tema está actualizada');
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error al copiar plantilla: ' . $e->getMessage());
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

            // Crear la página de búsqueda si no existe
            add_action('init', array($this, 'create_search_page'));

            // Registrar el endpoint AJAX para el formulario de contacto
            add_action('wp_ajax_sync_despachos', array($this, 'sync_despachos'));
            add_action('wp_ajax_nopriv_sync_despachos', array($this, 'sync_despachos'));
            add_action('wp_ajax_update_despacho_slug', array($this, 'update_despacho_slug'));
            add_action('wp_ajax_nopriv_update_despacho_slug', array($this, 'update_despacho_slug'));
            add_action('wp_ajax_get_despacho_details', array($this, 'get_despacho_details'));
            add_action('wp_ajax_nopriv_get_despacho_details', array($this, 'get_despacho_details'));
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
            
            // Enqueue Font Awesome
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
                array(),
                '5.15.4'
            );
            
            // Enqueue estilos del plugin
            wp_enqueue_style(
                'lexhoy-despachos',
                $plugin_url . 'css/lexhoy-despachos.css',
                array('font-awesome'),
                LEXHOY_DESPACHOS_VERSION
            );
            
            // Enqueue scripts del plugin
            wp_enqueue_script(
                'lexhoy-despachos',
                $plugin_url . 'js/lexhoy-despachos.js',
                array('jquery'),
                LEXHOY_DESPACHOS_VERSION,
                true
            );
            
            // Localizar el script
            wp_localize_script('lexhoy-despachos', 'lexhoyDespachos', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lexhoy_despachos_nonce')
            ));

            // Solo cargar los scripts de Algolia en la página de búsqueda
            if (is_page('buscador-de-despachos')) {
            // Estilos de Algolia InstantSearch
            wp_enqueue_style(
                'algolia-instantsearch',
                'https://cdn.jsdelivr.net/npm/instantsearch.css@8.1.0/themes/satellite-min.css',
                array(),
                '8.1.0'
            );

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

                // Script personalizado de búsqueda
                wp_enqueue_script(
                    'lexhoy-despachos-search',
                    $plugin_url . 'assets/js/script.js',
                    array('jquery', 'algolia-search', 'algolia-instantsearch'),
                    LEXHOY_DESPACHOS_VERSION,
                    false
                );

                // Obtener las credenciales de Algolia desde la configuración
                $settings = get_option('lexhoy_despachos_settings');

                // Añadir datos para el script
                wp_localize_script('lexhoy-despachos-search', 'lexhoyDespachosData', array(
                    'appId' => $settings['algolia_app_id'] ?? '',
                    'searchApiKey' => $settings['algolia_search_api_key'] ?? '',
                    'indexName' => 'lexhoy_despachos_formatted'
                ));
            }
        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }
    }

    public function render_search_form() {
        try {
            ob_start();
            include plugin_dir_path(__FILE__) . 'templates/search-template.php';
            return ob_get_clean();
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
        error_log('Lexhoy Despachos - Registrando Custom Post Type');
        
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
            'show_ui'             => false,
            'show_in_menu'        => false,
            'query_var'           => true,
            'rewrite'             => array(
                'slug' => '',
                'with_front' => false,
                'pages' => true,
                'feeds' => false
            ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor', 'thumbnail')
        );

        register_post_type('despacho', $args);
        error_log('Lexhoy Despachos - Custom Post Type registrado');
    }

    public function add_rewrite_rules() {
        error_log('Lexhoy Despachos - Añadiendo reglas de reescritura');
        
        // Regla específica para Sorriba (debe ir primero)
        add_rewrite_rule(
            '^sorribasasociados/?$',
            'index.php?post_type=despacho&name=sorribasasociados',
            'top'
        );

        // Regla para la página de búsqueda
        add_rewrite_rule(
            '^buscador-de-despachos/?$',
            'index.php?pagename=buscador-de-despachos',
            'top'
        );

        // Regla general para otros despachos (debe ir al final)
        add_rewrite_rule(
            '^([^/]+)/?$',
            'index.php?post_type=despacho&name=$matches[1]',
            'top'
        );

        // Añadir query vars personalizados
        add_rewrite_tag('%despacho_slug%', '([^&]+)');

        // Limpiar las reglas de reescritura
        flush_rewrite_rules();
        
        error_log('Lexhoy Despachos - Reglas de reescritura añadidas');
    }

    public function handle_despacho_template() {
        global $wp_query;
        
        // Obtener el slug de la URL actual
        $current_url = $_SERVER['REQUEST_URI'];
        $slug = trim($current_url, '/');

        error_log('Lexhoy Despachos - URL actual: ' . $current_url);
        error_log('Lexhoy Despachos - Slug: ' . $slug);

        // No procesar si estamos en la página del buscador o en la home
        if ($slug === 'buscador-de-despachos' || $slug === '') {
            return;
        }

        // Verificar si la URL actual coincide con el patrón de un despacho
        // Solo procesar si la URL no coincide con páginas o posts existentes
        $existing_page = get_page_by_path($slug);
        if ($existing_page) {
            return;
        }

        // Obtener los datos de Algolia
        $settings = get_option('lexhoy_despachos_settings');
        
        if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
            return; // Silenciosamente retornar si no hay configuración
        }

        try {
            // Incluir el autoloader de Composer
            $autoloader = dirname(__FILE__) . '/vendor/autoload.php';
            if (!file_exists($autoloader)) {
                return;
            }
            require_once $autoloader;

            // Crear el cliente de Algolia
            $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_admin_api_key']
            );
            
            // Buscar el despacho por nombre (más flexible que por slug)
            $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                'query' => $slug,
                'hitsPerPage' => 1
            ]);

            if (empty($results['hits'])) {
                return; // Silenciosamente retornar si no se encuentra el despacho
            }

            $despacho = $results['hits'][0];

            // Establecer variables globales para que estén disponibles en la plantilla
            global $post;
            $post = (object) array(
                'ID' => 0,
                'post_title' => $despacho['nombre'],
                'post_type' => 'despacho',
                'post_status' => 'publish'
            );
            setup_postdata($post);

            // Buscar la plantilla en el tema
            $template = locate_template('single-despacho.php');

            if (!$template) {
                // Si no existe en el tema, usar la del plugin
                $template = plugin_dir_path(__FILE__) . 'templates/despacho-single.php';
            }

            // Verificar que la plantilla existe
            if (!file_exists($template)) {
                return;
            }

            // Hacer disponible la variable global para la plantilla
            $GLOBALS['despacho'] = $despacho;

            // Incluir la plantilla
            include $template;

            // Restaurar los datos originales
            wp_reset_postdata();

            // Detener la ejecución para evitar que WordPress cargue su propia plantilla
            exit;

        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            return;
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

    public function create_search_page() {
        // Verificar si la página ya existe
        $search_page = get_page_by_path('buscador-de-despachos');
        
        if (!$search_page) {
            // Crear la página
            $page_data = array(
                'post_title'    => 'Buscador de Despachos',
                'post_name'     => 'buscador-de-despachos',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_content'  => '[lexhoy_despachos_search]',
                'post_author'   => 1
            );
            
            $page_id = wp_insert_post($page_data);
            
            if (is_wp_error($page_id)) {
                error_log('Error al crear la página de búsqueda: ' . $page_id->get_error_message());
            }
        }
    }

    public function handle_contact_form() {
        try {
            // Verificar nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lexhoy_despachos_nonce')) {
                throw new Exception('Error de seguridad');
            }

            // Validar campos requeridos
            $required_fields = array('nombre', 'email', 'mensaje', 'despacho_id');
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception('Por favor complete todos los campos requeridos');
                }
            }

            // Obtener datos del formulario
            $nombre = sanitize_text_field($_POST['nombre']);
            $email = sanitize_email($_POST['email']);
            $telefono = isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '';
            $mensaje = sanitize_textarea_field($_POST['mensaje']);
            $despacho_id = sanitize_text_field($_POST['despacho_id']);

            // Obtener datos del despacho
            $despacho = $this->get_despacho_by_id($despacho_id);
            if (!$despacho) {
                throw new Exception('Despacho no encontrado');
            }

            // Preparar el correo
            $to = $despacho['email'];
            $subject = 'Nuevo mensaje de contacto - ' . $despacho['nombre'];
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $nombre . ' <' . $email . '>'
            );

            // Preparar el cuerpo del mensaje
            $message = '
                <html>
                <body>
                    <h2>Nuevo mensaje de contacto</h2>
                    <p><strong>Nombre:</strong> ' . $nombre . '</p>
                    <p><strong>Email:</strong> ' . $email . '</p>
                    ' . ($telefono ? '<p><strong>Teléfono:</strong> ' . $telefono . '</p>' : '') . '
                    <p><strong>Mensaje:</strong></p>
                    <p>' . nl2br($mensaje) . '</p>
                </body>
                </html>
            ';

            // Enviar el correo
            $sent = wp_mail($to, $subject, $message, $headers);

            if (!$sent) {
                throw new Exception('Error al enviar el mensaje');
            }

            // Enviar respuesta de éxito
            wp_send_json_success(array(
                'message' => 'Mensaje enviado correctamente'
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    private function get_despacho_by_id($id) {
        try {
            $settings = get_option('lexhoy_despachos_settings');
            
            if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
                throw new Exception('Credenciales de Algolia no configuradas');
            }

            // Incluir el autoloader de Composer
            $autoloader = dirname(__FILE__) . '/vendor/autoload.php';
            if (!file_exists($autoloader)) {
                throw new Exception('No se encontró el autoloader de Composer');
            }
            require_once $autoloader;

            // Crear el cliente de Algolia
            $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_admin_api_key']
            );
            
            // Buscar el despacho por ID
            $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                'query' => $id,
                'filters' => 'objectID:' . $id,
                'hitsPerPage' => 1
            ]);

            if (empty($results['hits'])) {
                return null;
            }

            return $results['hits'][0];

        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
            return null;
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

// Función para ejecutar composer install
function lexhoy_despachos_activate() {
    try {
        $plugin_dir = dirname(__FILE__);
        $composer_path = $plugin_dir . '/vendor/autoload.php';
        
        // Si no existe el autoloader, ejecutar composer install
        if (!file_exists($composer_path)) {
            // Verificar si composer está instalado
            exec('composer --version', $output, $return_var);
            
            if ($return_var === 0) {
                // Ejecutar composer install
                $command = 'cd ' . escapeshellarg($plugin_dir) . ' && composer install --no-dev --optimize-autoloader';
                exec($command, $output, $return_var);
                
                if ($return_var !== 0) {
                    error_log('Error al ejecutar composer install: ' . implode("\n", $output));
                    wp_die('Error al instalar las dependencias del plugin. Por favor, ejecuta manualmente "composer install" en la carpeta del plugin.');
                }
            } else {
                error_log('Composer no está instalado en el servidor');
                wp_die('Composer no está instalado en el servidor. Por favor, instala Composer y ejecuta "composer install" en la carpeta del plugin.');
            }
        }
    } catch (Exception $e) {
        error_log('Error en la activación del plugin: ' . $e->getMessage());
        wp_die('Error al activar el plugin: ' . $e->getMessage());
    }
}

// Registrar la función de activación
register_activation_hook(__FILE__, 'lexhoy_despachos_activate');

add_action('plugins_loaded', 'lexhoy_despachos_init'); 

// Función para mostrar la página de áreas de práctica
function lexhoy_areas_practica_page() {
    require_once plugin_dir_path(__FILE__) . 'admin/areas-practica.php';
}

// Inicializar el sistema de actualizaciones
function lexhoy_despachos_updater_init() {
    $updater = new LexhoyDespachosUpdater(__FILE__);
    $updater->init();
}
add_action('admin_init', 'lexhoy_despachos_updater_init'); 