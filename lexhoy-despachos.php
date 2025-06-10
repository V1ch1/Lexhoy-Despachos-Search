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
                add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            }

            // Hooks públicos
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_shortcode('lexhoy_despachos_search', array($this, 'render_search_form'));
            add_shortcode('lexhoy_despachos', array($this, 'render_search_form'));

            // Registrar el tipo de post y las reglas de reescritura
            add_action('init', array($this, 'register_despacho_post_type'));
            add_action('init', array($this, 'add_rewrite_rules'));
            
            // Manejar la plantilla personalizada
            add_filter('single_template', array($this, 'handle_despacho_template'));

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
                'Despachos',
                'Despachos',
                'manage_options',
                'lexhoy-despachos',
                'lexhoy_despachos_page',
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
                'Áreas',
                'Áreas',
                'manage_options',
                'lexhoy-areas',
                'lexhoy_areas_page'
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

    public function enqueue_scripts() {
        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );

        // Bootstrap JS
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );
        
        // Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Estilos del plugin
        wp_enqueue_style(
            'lexhoy-despachos',
            plugins_url('css/lexhoy-despachos.css', __FILE__),
            array('bootstrap'),
            filemtime(plugin_dir_path(__FILE__) . 'css/lexhoy-despachos.css')
        );

        // Algolia InstantSearch.js
        wp_enqueue_script(
            'algoliasearch',
            'https://cdn.jsdelivr.net/npm/algoliasearch@4.22.1/dist/algoliasearch-lite.umd.js',
            array(),
            '4.22.1',
            true
        );

        wp_enqueue_script(
            'instantsearch',
            'https://cdn.jsdelivr.net/npm/instantsearch.js@4.65.0/dist/instantsearch.production.min.js',
            array('algoliasearch'),
            '4.65.0',
            true
        );

        // Script del plugin
        wp_enqueue_script(
            'lexhoy-despachos-script',
            plugins_url('js/lexhoy-despachos.js', __FILE__),
            array('jquery', 'algoliasearch', 'instantsearch'),
            filemtime(plugin_dir_path(__FILE__) . 'js/lexhoy-despachos.js'),
            true
        );

        // Pasar los datos de Algolia al JavaScript
        $settings = get_option('lexhoy_despachos_settings');
        wp_localize_script('lexhoy-despachos-script', 'lexhoyDespachosData', array(
            'appId' => $settings['algolia_app_id'] ?? '',
            'searchApiKey' => $settings['algolia_search_api_key'] ?? '',
            'indexName' => 'lexhoy_despachos_formatted',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lexhoy_despachos_search')
        ));
    }

    public function enqueue_admin_scripts() {
        // Solo cargar en las páginas de nuestro plugin
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'lexhoy-despachos') === false) {
            return;
        }

        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );

        // Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Bootstrap JS y Popper.js
        wp_enqueue_script(
            'popper',
            'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js',
            array(),
            '2.11.6',
            true
        );
        
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js',
            array('jquery', 'popper'),
            '5.3.0',
            true
        );

        // Estilos del admin
        wp_enqueue_style(
            'lexhoy-despachos-admin',
            plugins_url('css/lexhoy-despachos.css', __FILE__),
            array('bootstrap'),
            filemtime(plugin_dir_path(__FILE__) . 'css/lexhoy-despachos.css')
        );
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
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array(
                'slug' => '',
                'with_front' => false
            ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail')
        );

        register_post_type('despacho', $args);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            'despacho/([^/]+)/?$',
            'index.php?post_type=despacho&despacho_id=$matches[1]',
            'top'
        );
        add_rewrite_tag('%despacho_id%', '([^&]+)');
    }

    public function handle_despacho_template($template) {
        global $post;

        // Si no estamos en una página de despacho, devolver la plantilla original
        if (!is_singular('despacho')) {
            return $template;
        }

        try {
            // Obtener el ID del despacho desde la URL
            $despacho_id = get_query_var('despacho_id');
            if (empty($despacho_id)) {
                return $template;
            }

            // Obtener los datos de Algolia
            $settings = get_option('lexhoy_despachos_settings');
            if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
                error_log('Lexhoy Despachos Error: Credenciales de Algolia no configuradas');
                return $template;
            }

            // Incluir el autoloader de Composer
            $autoloader = dirname(__FILE__) . '/vendor/autoload.php';
            if (!file_exists($autoloader)) {
                error_log('Lexhoy Despachos Error: No se encontró el autoloader de Composer');
                return $template;
            }
            require_once $autoloader;

            // Crear el cliente de Algolia
            $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_admin_api_key']
            );

            // Buscar el despacho en Algolia
            $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                'filters' => 'objectID:' . $despacho_id,
                'hitsPerPage' => 1
            ]);

            if (empty($results['hits'])) {
                error_log('Lexhoy Despachos Error: No se encontró el despacho en Algolia');
                return $template;
            }

            // Guardar los datos del despacho en una variable global
            $GLOBALS['despacho'] = $results['hits'][0];

            // Buscar la plantilla en el tema primero
            $theme_template = locate_template('single-despacho.php');
            if ($theme_template) {
                return $theme_template;
            }

            // Si no está en el tema, usar la plantilla del plugin
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/despacho-single.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }

        } catch (Exception $e) {
            error_log('Lexhoy Despachos Error: ' . $e->getMessage());
        }

        return $template;
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
    // Verificar si Composer está instalado
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        // Verificar si composer.phar existe
        if (!file_exists(__DIR__ . '/composer.phar')) {
            // Descargar Composer
            $composer_installer = file_get_contents('https://getcomposer.org/installer');
            if ($composer_installer === false) {
                error_log('Lexhoy Despachos Error: No se pudo descargar el instalador de Composer');
                return;
            }
            file_put_contents(__DIR__ . '/composer.phar', $composer_installer);
        }

        // Ejecutar Composer install
        $command = 'php ' . __DIR__ . '/composer.phar install --no-interaction --no-dev';
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            error_log('Lexhoy Despachos Error: Error al ejecutar Composer install');
            error_log('Output: ' . print_r($output, true));
            return;
        }
    }

    // Crear la página de búsqueda si no existe
    $search_page = get_page_by_path('buscador-de-despachos');
    if (!$search_page) {
        $page_id = wp_insert_post(array(
            'post_title' => 'Buscar Despachos',
            'post_content' => '[lexhoy_despachos_search]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'buscar-despachos'
        ));

        if (!is_wp_error($page_id)) {
            update_option('lexhoy_despachos_search_page_id', $page_id);
        }
    }

    // Registrar el Custom Post Type
    $plugin = LexhoyDespachos::get_instance();
    $plugin->register_despacho_post_type();

    // Actualizar las reglas de reescritura
    add_rewrite_rule(
        'despacho/([^/]+)/?$',
        'index.php?post_type=despacho&despacho_id=$matches[1]',
        'top'
    );
    add_rewrite_tag('%despacho_id%', '([^&]+)');
    flush_rewrite_rules();
}

// Registrar la función de activación
register_activation_hook(__FILE__, 'lexhoy_despachos_activate');

add_action('plugins_loaded', 'lexhoy_despachos_init'); 

// Función para mostrar la página de áreas
function lexhoy_areas_page() {
    include plugin_dir_path(__FILE__) . 'admin/areas-practica.php';
}

// Inicializar el sistema de actualizaciones
function lexhoy_despachos_updater_init() {
    $updater = new LexhoyDespachosUpdater(__FILE__);
    $updater->init();
}
add_action('admin_init', 'lexhoy_despachos_updater_init'); 