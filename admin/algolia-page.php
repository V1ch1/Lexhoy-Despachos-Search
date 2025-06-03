<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap lexhoy-despachos-admin">
    <h1>Configuración de Algolia</h1>

    <div>
        <form method="post" action="options.php">
            <?php
            settings_fields('lexhoy_despachos_options');
            do_settings_sections('lexhoy_despachos_options');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="algolia_app_id"><?php _e('Application ID', 'lexhoy-despachos'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="algolia_app_id" 
                               name="lexhoy_despachos_settings[algolia_app_id]" 
                               value="<?php echo esc_attr($this->settings['algolia_app_id'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Tu identificador único de aplicación. Se usa para identificar tu aplicación cuando usas la API de Algolia.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="algolia_search_api_key"><?php _e('Search API Key', 'lexhoy-despachos'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="algolia_search_api_key" 
                               name="lexhoy_despachos_settings[algolia_search_api_key]" 
                               value="<?php echo esc_attr($this->settings['algolia_search_api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Clave pública que puede usarse de forma segura en el frontend. Permite realizar búsquedas y listar los índices a los que tienes acceso.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="algolia_write_api_key"><?php _e('Write API Key', 'lexhoy-despachos'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="algolia_write_api_key" 
                               name="lexhoy_despachos_settings[algolia_write_api_key]" 
                               value="<?php echo esc_attr($this->settings['algolia_write_api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Clave privada. Mantenla en secreto y úsala SOLO desde tu backend. Se usa para crear, actualizar y eliminar índices.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="algolia_admin_api_key"><?php _e('Admin API Key', 'lexhoy-despachos'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="algolia_admin_api_key" 
                               name="lexhoy_despachos_settings[algolia_admin_api_key]" 
                               value="<?php echo esc_attr($this->settings['algolia_admin_api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Clave de administrador. Mantenla en secreto y úsala SOLO desde tu backend. Permite gestionar índices y claves API.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="algolia_usage_api_key"><?php _e('Usage API Key', 'lexhoy-despachos'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="algolia_usage_api_key" 
                               name="lexhoy_despachos_settings[algolia_usage_api_key]" 
                               value="<?php echo esc_attr($this->settings['algolia_usage_api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Clave para acceder a la API de Uso y el endpoint de Logs.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="algolia_monitoring_api_key"><?php _e('Monitoring API Key', 'lexhoy-despachos'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="algolia_monitoring_api_key" 
                               name="lexhoy_despachos_settings[algolia_monitoring_api_key]" 
                               value="<?php echo esc_attr($this->settings['algolia_monitoring_api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Clave para acceder a la API de Monitoreo.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Guardar cambios'); ?>
        </form>
    </div>
</div> 