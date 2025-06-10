<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir el autoloader de Composer
$autoloader = dirname(dirname(__FILE__)) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    echo '<div class="error"><p>Error: No se encontró el autoloader de Composer.</p></div>';
    return;
}

require_once $autoloader;

// Enqueue de estilos y scripts necesarios
function lexhoy_despachos_admin_scripts() {
    // Bootstrap CSS
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    
    // Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
    
    // Bootstrap JS y Popper.js
    wp_enqueue_script('popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js', array(), null, true);
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js', array('jquery', 'popper'), null, true);
}
add_action('admin_enqueue_scripts', 'lexhoy_despachos_admin_scripts');

try {
    // Obtener el ID del despacho
    $despacho_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
    if (empty($despacho_id)) {
        echo '<div class="error"><p>Error: No se especificó el ID del despacho.</p></div>';
        return;
    }

    // Obtener los datos de Algolia
    $settings = get_option('lexhoy_despachos_settings');
    if (empty($settings['algolia_app_id']) || empty($settings['algolia_admin_api_key'])) {
        echo '<div class="error"><p>Error: Por favor, configure las credenciales de Algolia en la página de configuración.</p></div>';
        return;
    }

    $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
        $settings['algolia_app_id'],
        $settings['algolia_admin_api_key']
    );

    // Obtener los datos del despacho
    $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
        'filters' => 'objectID:' . $despacho_id,
        'hitsPerPage' => 1
    ]);

    if (!$results['hits'][0]) {
        echo '<div class="error"><p>Error: No se encontró el despacho.</p></div>';
        return;
    }

    // Obtener las áreas de práctica del despacho actual
    $todas_areas = get_option('lexhoy_areas_practica', []);
    $areas_seleccionadas = isset($results['hits'][0]['areas_practica']) ? (array)$results['hits'][0]['areas_practica'] : [];
    
    // Si hay un mensaje de actualización, recargar los datos
    if (isset($_GET['updated']) && $_GET['updated'] == '1') {
        // Esperar un momento para que Algolia se actualice
        sleep(2);
        
        try {
            // Obtener los datos actualizados
            $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                'filters' => 'objectID:' . $despacho_id,
                'hitsPerPage' => 1
            ]);
            if ($results['hits'][0]) {
                $areas_seleccionadas = isset($results['hits'][0]['areas_practica']) ? (array)$results['hits'][0]['areas_practica'] : [];
                
                // Debug para verificar los datos
                error_log('Despacho actualizado: ' . print_r($results['hits'][0], true));
                error_log('Áreas seleccionadas actualizadas: ' . print_r($areas_seleccionadas, true));
            }
        } catch (Exception $e) {
            error_log('Error al obtener datos actualizados: ' . $e->getMessage());
        }
    }
    
    sort($todas_areas);

    $slug = $results['hits'][0]['slug'] ?? '';

} catch (Exception $e) {
    echo '<div class="error"><p>Error al conectar con Algolia: ' . esc_html($e->getMessage()) . '</p></div>';
    return;
}
?>

<div class="wrap lexhoy-despachos-admin">
    <h1 class="wp-heading-inline">Editar Despacho</h1>
    <hr class="wp-header-end">

    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Los cambios se han guardado correctamente.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="despacho-form">
        <?php wp_nonce_field('lexhoy_despachos_edit', 'lexhoy_despachos_nonce'); ?>
        <input type="hidden" name="action" value="lexhoy_despachos_update">
        <input type="hidden" name="objectID" value="<?php echo esc_attr($results['hits'][0]['objectID']); ?>">
        <input type="hidden" name="current_tab" id="current_tab" value="<?php echo esc_attr(isset($_GET['tab']) ? $_GET['tab'] : 'info-basica'); ?>">

        <!-- Navegación de pestañas -->
        <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs" id="despachoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'info-basica') ? 'active' : ''; ?>" 
                            id="info-basica-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#info-basica" 
                            type="button" 
                            role="tab"
                            aria-controls="info-basica"
                            aria-selected="<?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'info-basica') ? 'true' : 'false'; ?>">
                        <i class="fas fa-info-circle"></i> Información Básica
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'contacto') ? 'active' : ''; ?>" 
                            id="contacto-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#contacto" 
                            type="button" 
                            role="tab"
                            aria-controls="contacto"
                            aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'contacto') ? 'true' : 'false'; ?>">
                        <i class="fas fa-address-card"></i> Contacto
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'areas') ? 'active' : ''; ?>" 
                            id="areas-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#areas" 
                            type="button" 
                            role="tab"
                            aria-controls="areas"
                            aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'areas') ? 'true' : 'false'; ?>">
                        <i class="fas fa-briefcase"></i> Áreas y Especialidades
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'adicional') ? 'active' : ''; ?>" 
                            id="adicional-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#adicional" 
                            type="button" 
                            role="tab"
                            aria-controls="adicional"
                            aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'adicional') ? 'true' : 'false'; ?>">
                        <i class="fas fa-plus-circle"></i> Información Adicional
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenido de las pestañas -->
        <div class="tab-content-wrapper">
            <div class="tab-content" id="despachoTabsContent">
                <!-- Pestaña Información Básica -->
                <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'info-basica') ? 'show active' : ''; ?>" 
                     id="info-basica" 
                     role="tabpanel"
                     aria-labelledby="info-basica-tab">
                    <div class="tab-pane-content">
                        <div class="form-section">
                            <h2 class="section-title">Información Básica del Despacho</h2>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="nombre" class="form-label">Nombre del Despacho *</label>
                                    <input type="text" id="nombre" name="nombre" value="<?php echo esc_attr($results['hits'][0]['nombre']); ?>" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="slug" class="form-label">URL Amigable (Slug)</label>
                                    <input type="text" id="slug" name="slug" value="<?php echo esc_attr($results['hits'][0]['slug'] ?? strtolower(str_replace(' ', '-', $results['hits'][0]['nombre']))); ?>" class="form-control">
                                    <small class="form-text">La URL amigable para este despacho. Si lo dejas vacío, se generará automáticamente a partir del nombre.</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <input type="text" id="direccion" name="direccion" value="<?php echo esc_attr($results['hits'][0]['direccion']); ?>" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="localidad" class="form-label">Localidad</label>
                                    <input type="text" id="localidad" name="localidad" value="<?php echo esc_attr($results['hits'][0]['localidad']); ?>" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="provincia" class="form-label">Provincia</label>
                                    <input type="text" id="provincia" name="provincia" value="<?php echo esc_attr($results['hits'][0]['provincia']); ?>" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="codigo_postal" class="form-label">Código Postal</label>
                                    <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo esc_attr($results['hits'][0]['codigo_postal']); ?>" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Contacto -->
                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'contacto') ? 'show active' : ''; ?>" 
                     id="contacto" 
                     role="tabpanel"
                     aria-labelledby="contacto-tab">
                    <div class="tab-pane-content">
                        <div class="form-section">
                            <h2 class="section-title">Información de Contacto</h2>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" value="<?php echo esc_attr($results['hits'][0]['telefono']); ?>" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo esc_attr($results['hits'][0]['email']); ?>" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label for="web" class="form-label">Sitio Web</label>
                                    <input type="url" id="web" name="web" value="<?php echo esc_attr($results['hits'][0]['web']); ?>" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2 class="section-title">Redes Sociales</h2>
                            <div class="social-media-grid">
                                <div class="social-media-item">
                                    <i class="fab fa-facebook"></i>
                                    <input type="url" name="redes_sociales[facebook]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['facebook'] ?? ''); ?>" class="form-control" placeholder="URL de Facebook">
                                </div>
                                <div class="social-media-item">
                                    <i class="fab fa-twitter"></i>
                                    <input type="url" name="redes_sociales[twitter]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['twitter'] ?? ''); ?>" class="form-control" placeholder="URL de Twitter">
                                </div>
                                <div class="social-media-item">
                                    <i class="fab fa-linkedin"></i>
                                    <input type="url" name="redes_sociales[linkedin]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['linkedin'] ?? ''); ?>" class="form-control" placeholder="URL de LinkedIn">
                                </div>
                                <div class="social-media-item">
                                    <i class="fab fa-instagram"></i>
                                    <input type="url" name="redes_sociales[instagram]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['instagram'] ?? ''); ?>" class="form-control" placeholder="URL de Instagram">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2 class="section-title">Horario de Atención</h2>
                            <div class="horario-grid">
                                <?php
                                $dias = array(
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miercoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sabado' => 'Sábado',
                                    'domingo' => 'Domingo'
                                );
                                foreach ($dias as $key => $dia):
                                ?>
                                <div class="horario-item">
                                    <label class="form-label"><?php echo $dia; ?></label>
                                    <input type="text" name="horario[<?php echo $key; ?>]" value="<?php echo esc_attr($results['hits'][0]['horario'][$key] ?? ''); ?>" class="form-control" placeholder="Ej: 9:00 - 18:00">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Áreas -->
                <div class="tab-pane fade" id="areas" role="tabpanel" aria-labelledby="areas-tab">
                    <div class="form-group">
                        <label class="form-label">Áreas</label>
                        <div class="areas-grid">
                            <?php foreach ($todas_areas as $area): ?>
                                <div class="area-item">
                                    <input type="checkbox" 
                                           name="areas_practica[]" 
                                           value="<?php echo esc_attr($area); ?>"
                                           id="area_<?php echo esc_attr(sanitize_title($area)); ?>"
                                           <?php checked(in_array($area, $areas_seleccionadas)); ?>>
                                    <label for="area_<?php echo esc_attr(sanitize_title($area)); ?>">
                                        <?php echo esc_html($area); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Información Adicional -->
                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'adicional') ? 'show active' : ''; ?>" 
                     id="adicional" 
                     role="tabpanel"
                     aria-labelledby="adicional-tab">
                    <div class="tab-pane-content">
                        <div class="form-section">
                            <h2 class="section-title">Descripción y Experiencia</h2>
                            <div class="form-group">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea id="descripcion" name="descripcion" class="form-control" rows="5"><?php echo esc_textarea($results['hits'][0]['descripcion']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="experiencia" class="form-label">Experiencia</label>
                                <textarea id="experiencia" name="experiencia" class="form-control" rows="3"><?php echo esc_textarea($results['hits'][0]['experiencia']); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2 class="section-title">Información Adicional</h2>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="tamaño_despacho" class="form-label">Tamaño del Despacho</label>
                                    <input type="text" id="tamaño_despacho" name="tamaño_despacho" value="<?php echo esc_attr($results['hits'][0]['tamaño_despacho']); ?>" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="año_fundacion" class="form-label">Año de Fundación</label>
                                    <input type="number" id="año_fundacion" name="año_fundacion" value="<?php echo esc_attr($results['hits'][0]['año_fundacion']); ?>" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2 class="section-title">Estado y Verificación</h2>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="estado_verificacion" class="form-label">Estado de Verificación</label>
                                    <select id="estado_verificacion" name="estado_verificacion" class="form-control">
                                        <option value="pendiente" <?php selected($results['hits'][0]['estado_verificacion'], 'pendiente'); ?>>Pendiente</option>
                                        <option value="verificado" <?php selected($results['hits'][0]['estado_verificacion'], 'verificado'); ?>>Verificado</option>
                                        <option value="rechazado" <?php selected($results['hits'][0]['estado_verificacion'], 'rechazado'); ?>>Rechazado</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="estado_registro" class="form-label">Estado del Registro</label>
                                    <select id="estado_registro" name="estado_registro" class="form-control">
                                        <option value="activo" <?php selected($results['hits'][0]['estado_registro'], 'activo'); ?>>Activo</option>
                                        <option value="inactivo" <?php selected($results['hits'][0]['estado_registro'], 'inactivo'); ?>>Inactivo</option>
                                        <option value="pendiente" <?php selected($results['hits'][0]['estado_registro'], 'pendiente'); ?>>Pendiente</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="button button-primary">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="<?php echo admin_url('admin.php?page=lexhoy-despachos-list'); ?>" class="button">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
        </div>
    </form>
</div>

<style>
/* Estilos generales del formulario */
.lexhoy-despachos-admin {
    max-width: 1200px;
    margin: 20px auto;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

/* Navegación de pestañas */
.nav-tabs-wrapper {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccd0d4;
}

.nav-tabs {
    display: flex;
    gap: 5px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.nav-tabs .nav-item {
    margin-bottom: -1px;
}

.nav-tabs .nav-link {
    padding: 12px 20px;
    border: 1px solid #ccd0d4;
    border-bottom: none;
    background: #f8f9fa;
    color: #1d2327;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-right: 5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    z-index: 1;
}

.nav-tabs .nav-link:hover {
    background: #f0f0f0;
}

.nav-tabs .nav-link.active {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
    z-index: 2;
}

/* Contenido de las pestañas */
.tab-content-wrapper {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
}

.tab-pane {
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.tab-pane.show {
    display: block;
    opacity: 1;
}

.tab-pane-content {
    display: block !important;
}

/* Secciones del formulario */
.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    font-size: 1.2em;
    margin-bottom: 20px;
    color: #1d2327;
    font-weight: 600;
}

/* Campos del formulario */
.form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.form-group {
    padding: 0 10px;
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #1d2327;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
    line-height: 1.4;
}

.form-control:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

/* Áreas de práctica */
.areas-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.area-item {
    display: flex;
    align-items: center;
}

.area-item input[type="checkbox"] {
    margin-right: 8px;
}

/* Redes sociales */
.social-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.social-media-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.social-media-item i {
    font-size: 20px;
    color: #1d2327;
    width: 24px;
    text-align: center;
}

/* Horario */
.horario-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.horario-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

/* Botones de acción */
.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ccd0d4;
    display: flex;
    gap: 10px;
}

.button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    background: #f8f9fa;
    color: #1d2327;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.button:hover {
    background: #f0f0f0;
}

.button-primary {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.button-primary:hover {
    background: #135e96;
}

/* Responsive */
@media (max-width: 782px) {
    .nav-tabs {
        flex-wrap: wrap;
    }

    .nav-tabs .nav-link {
        flex: 1;
        text-align: center;
        padding: 10px;
    }

    .social-media-grid,
    .horario-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        flex-direction: column;
    }

    .form-group {
        width: 100%;
    }
}

/* Ajustes para las pestañas */
.nav-link {
    cursor: pointer;
}

.nav-link.active {
    background-color: #2271b1 !important;
    color: #fff !important;
    border-color: #2271b1 !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Función para cambiar de pestaña
    function switchTab(tabId) {
        // Ocultar todas las pestañas
        $('.tab-pane').removeClass('show active');
        
        // Desactivar todos los botones
        $('.nav-link').removeClass('active').attr('aria-selected', 'false');
        
        // Mostrar la pestaña seleccionada
        $('#' + tabId).addClass('show active');
        
        // Activar el botón correspondiente
        $('#' + tabId + '-tab').addClass('active').attr('aria-selected', 'true');
        
        // Actualizar el campo oculto
        $('#current_tab').val(tabId);
    }

    // Manejar clics en las pestañas
    $('.nav-link').on('click', function(e) {
        e.preventDefault();
        var targetId = $(this).attr('data-bs-target').replace('#', '');
        switchTab(targetId);
    });

    // Restaurar la pestaña activa al cargar la página
    var currentTab = $('#current_tab').val() || 'info-basica';
    switchTab(currentTab);
});
</script> 