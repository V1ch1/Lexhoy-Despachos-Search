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
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
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
    
    // Debug para verificar los datos
    error_log('Todas las áreas disponibles: ' . print_r($todas_areas, true));
    error_log('Áreas seleccionadas actualmente: ' . print_r($areas_seleccionadas, true));
    
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
                
                // Debug para verificar los datos actualizados
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

<!-- Contenido del formulario -->
<div class="despacho-edit-container">
    <form method="post" action="" class="despacho-edit-form">
        <?php wp_nonce_field('lexhoy_despachos_edit', 'lexhoy_despachos_nonce'); ?>
        <input type="hidden" name="action" value="edit_despacho">
        <input type="hidden" name="objectID" value="<?php echo esc_attr($results['hits'][0]['objectID']); ?>">
        <input type="hidden" name="current_tab" id="current_tab" value="<?php echo isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'info-basica'; ?>">

        <!-- Información Básica -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i> Información Básica del Despacho
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre del Despacho *</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo esc_attr($results['hits'][0]['nombre']); ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="slug" class="form-label">URL Amigable (Slug)</label>
                    <input type="text" id="slug" name="slug" value="<?php echo esc_attr($results['hits'][0]['slug'] ?? strtolower(str_replace(' ', '-', $results['hits'][0]['nombre']))); ?>" class="form-control">
                    <small class="form-text">La URL amigable para este despacho. Si lo dejas vacío, se generará automáticamente a partir del nombre.</small>
                </div>
                <div class="form-group full-width">
                    <label for="direccion" class="form-label">Dirección</label>
                    <input type="text" id="direccion" name="direccion" value="<?php echo esc_attr($results['hits'][0]['direccion']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="localidad" class="form-label">Localidad</label>
                    <input type="text" id="localidad" name="localidad" value="<?php echo esc_attr($results['hits'][0]['localidad']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="provincia" class="form-label">Provincia</label>
                    <input type="text" id="provincia" name="provincia" value="<?php echo esc_attr($results['hits'][0]['provincia']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="codigo_postal" class="form-label">Código Postal</label>
                    <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo esc_attr($results['hits'][0]['codigo_postal']); ?>" class="form-control">
                </div>
            </div>
        </div>

        <!-- Contacto -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-address-card"></i> Información de Contacto
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?php echo esc_attr($results['hits'][0]['telefono']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($results['hits'][0]['email']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="sitio_web" class="form-label">Sitio Web</label>
                    <input type="url" id="sitio_web" name="sitio_web" value="<?php echo esc_attr($results['hits'][0]['web']); ?>" class="form-control">
                </div>
            </div>

            <h3 class="subsection-title">Redes Sociales</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                    <input type="url" name="redes_sociales[facebook]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['facebook'] ?? ''); ?>" class="form-control" placeholder="URL de Facebook">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-twitter"></i> Twitter</label>
                    <input type="url" name="redes_sociales[twitter]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['twitter'] ?? ''); ?>" class="form-control" placeholder="URL de Twitter">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-linkedin"></i> LinkedIn</label>
                    <input type="url" name="redes_sociales[linkedin]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['linkedin'] ?? ''); ?>" class="form-control" placeholder="URL de LinkedIn">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-instagram"></i> Instagram</label>
                    <input type="url" name="redes_sociales[instagram]" value="<?php echo esc_attr($results['hits'][0]['redes_sociales']['instagram'] ?? ''); ?>" class="form-control" placeholder="URL de Instagram">
                </div>
            </div>

            <h3 class="subsection-title">Horario de Atención</h3>
            <div class="form-grid">
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
                <div class="form-group">
                    <label class="form-label"><?php echo $dia; ?></label>
                    <input type="text" name="horario[<?php echo $key; ?>]" value="<?php echo esc_attr($results['hits'][0]['horario'][$key] ?? ''); ?>" class="form-control" placeholder="Ej: 9:00 - 18:00">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Áreas y Especialidades -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-briefcase"></i> Áreas y Especialidades
            </h2>
            <div class="form-group">
                <label class="form-label">Áreas</label>
                <div class="areas-list">
                    <?php 
                    $areas = get_option('lexhoy_areas_practica', array());
                    if (is_array($areas)) {
                        foreach ($areas as $area): 
                            if (is_array($area) && isset($area['nombre'])) {
                                $area_nombre = $area['nombre'];
                            } else {
                                $area_nombre = $area;
                            }
                            // Debug para verificar cada área
                            error_log('Verificando área: ' . $area_nombre);
                            error_log('¿Está seleccionada?: ' . (in_array($area_nombre, $areas_seleccionadas) ? 'Sí' : 'No'));
                    ?>
                        <div class="area-item">
                            <label class="area-checkbox">
                                <input type="checkbox" 
                                       name="areas_practica[]" 
                                       value="<?php echo esc_attr($area_nombre); ?>"
                                       <?php checked(in_array($area_nombre, $areas_seleccionadas)); ?>>
                                <span class="area-name"><?php echo esc_html($area_nombre); ?></span>
                            </label>
                        </div>
                    <?php 
                        endforeach;
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i> Información Adicional
            </h2>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="4"><?php echo esc_textarea($results['hits'][0]['descripcion']); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label for="experiencia" class="form-label">Experiencia</label>
                    <textarea id="experiencia" name="experiencia" class="form-control" rows="3"><?php echo esc_textarea($results['hits'][0]['experiencia']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="tamaño_despacho" class="form-label">Tamaño del Despacho</label>
                    <input type="text" id="tamaño_despacho" name="tamaño_despacho" value="<?php echo esc_attr($results['hits'][0]['tamaño_despacho']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="año_fundacion" class="form-label">Año de Fundación</label>
                    <input type="number" id="año_fundacion" name="año_fundacion" value="<?php echo esc_attr($results['hits'][0]['año_fundacion']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="estado_verificacion" class="form-label">Estado de Verificación</label>
                    <select id="estado_verificacion" name="estado_verificacion" class="form-control">
                        <option value="pendiente" <?php selected($results['hits'][0]['estado_verificacion'], 'pendiente'); ?>>Pendiente</option>
                        <option value="verificado" <?php selected($results['hits'][0]['estado_verificacion'], 'verificado'); ?>>Verificado</option>
                        <option value="premium" <?php selected($results['hits'][0]['estado_verificacion'], 'premium'); ?>>Premium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estado_registro" class="form-label">Estado del Registro</label>
                    <select id="estado_registro" name="estado_registro" class="form-control">
                        <option value="activo" <?php selected($results['hits'][0]['estado_registro'], 'activo'); ?>>Activo</option>
                        <option value="inactivo" <?php selected($results['hits'][0]['estado_registro'], 'inactivo'); ?>>Inactivo</option>
                        <option value="pendiente" <?php selected($results['hits'][0]['estado_registro'], 'pendiente'); ?>>Pendiente</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="button button-primary">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="<?php echo admin_url('admin.php?page=lexhoy-despachos'); ?>" class="button">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
        </div>
    </form>
</div>

<style>
/* Contenedor principal */
.despacho-edit-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Secciones del formulario */
.form-section {
    margin-bottom: 40px;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.section-title {
    font-size: 1.4em;
    color: #1d2327;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #2271b1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: #2271b1;
}

/* Grid de formulario */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

/* Campos del formulario */
.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #1d2327;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

/* Lista de áreas */
.areas-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.area-item {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.area-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.area-checkbox input[type="checkbox"] {
    margin: 0;
}

.area-name {
    font-size: 14px;
    color: #1d2327;
}

/* Botones de acción */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
}

.button-primary {
    background: #2271b1;
    color: #fff;
    border: none;
    cursor: pointer;
}

.button-primary:hover {
    background: #135e96;
}

.button:not(.button-primary) {
    background: #f0f0f0;
    color: #1d2327;
    border: 1px solid #ccd0d4;
}

.button:not(.button-primary):hover {
    background: #e5e5e5;
}

/* Responsive */
@media (max-width: 782px) {
    .despacho-edit-container {
        padding: 15px;
    }

    .form-section {
        padding: 15px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .button {
        width: 100%;
        justify-content: center;
    }
}

/* Estilos adicionales para las subsecciones */
.subsection-title {
    font-size: 1.2em;
    color: #1d2327;
    margin: 30px 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

/* Ajustes para los iconos de redes sociales */
.form-label i {
    margin-right: 8px;
    color: #2271b1;
}

/* Ajustes para el grid de horarios */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

/* Estilos adicionales */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.loading-spinner i {
    font-size: 24px;
    color: #0073aa;
    margin-bottom: 10px;
}

.area-item {
    position: relative;
}

.area-item.saving {
    opacity: 0.7;
}

.area-item.saving::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>

<!-- Overlay de carga -->
<div class="loading-overlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Guardando cambios...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script cargado');
    
    // Obtener todos los botones de pestaña
    const tabButtons = document.querySelectorAll('.tab-button');
    console.log('Botones encontrados:', tabButtons.length);
    
    // Obtener todos los paneles de pestaña
    const tabPanels = document.querySelectorAll('.tab-panel');
    console.log('Paneles encontrados:', tabPanels.length);
    
    // Función para cambiar de pestaña
    function switchTab(tabId) {
        console.log('Cambiando a pestaña:', tabId);
        
        // Ocultar todos los paneles
        tabPanels.forEach(panel => {
            panel.classList.remove('active');
        });
        
        // Desactivar todos los botones
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });
        
        // Mostrar el panel seleccionado
        const selectedPanel = document.getElementById(tabId);
        if (selectedPanel) {
            selectedPanel.classList.add('active');
            console.log('Panel activado:', tabId);
        }
        
        // Activar el botón correspondiente
        const selectedButton = document.querySelector(`[data-tab="${tabId}"]`);
        if (selectedButton) {
            selectedButton.classList.add('active');
            console.log('Botón activado:', tabId);
        }
        
        // Actualizar el campo oculto
        const currentTabInput = document.getElementById('current_tab');
        if (currentTabInput) {
            currentTabInput.value = tabId;
        }
    }
    
    // Añadir event listeners a los botones
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            console.log('Botón clickeado:', tabId);
            switchTab(tabId);
        });
    });
    
    // Activar la primera pestaña por defecto
    const defaultTab = document.getElementById('current_tab')?.value || 'info-basica';
    console.log('Activando pestaña por defecto:', defaultTab);
    switchTab(defaultTab);

    const form = document.querySelector('form');
    const loadingOverlay = document.querySelector('.loading-overlay');
    const areaItems = document.querySelectorAll('.area-item');

    // Función para mostrar el overlay de carga
    function showLoading() {
        loadingOverlay.style.display = 'flex';
    }

    // Función para ocultar el overlay de carga
    function hideLoading() {
        loadingOverlay.style.display = 'none';
    }

    // Función para marcar un área como guardando
    function markAreaAsSaving(areaItem) {
        areaItem.classList.add('saving');
    }

    // Función para desmarcar un área como guardando
    function unmarkAreaAsSaving(areaItem) {
        areaItem.classList.remove('saving');
    }

    // Manejar el envío del formulario
    let isSubmitting = false;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting) {
            console.log('Ya hay un envío en proceso, ignorando...');
            return;
        }
        showLoading();

        const formData = new FormData(form);
        formData.append('action', 'update_despacho');
        formData.append('lexhoy_despachos_nonce', '<?php echo wp_create_nonce("lexhoy_despachos_edit"); ?>');

        fetch(ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página para mostrar los cambios actualizados
                window.location.reload();
            } else {
                alert('Error al guardar los cambios: ' + data.data);
                hideLoading();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar los cambios. Por favor, intente nuevamente.');
            hideLoading();
        });
    });

    // Manejar cambios en las áreas
    areaItems.forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', function() {
            markAreaAsSaving(item);
            form.dispatchEvent(new Event('submit'));
        });
    });
});
</script> 