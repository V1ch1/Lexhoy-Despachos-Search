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
    $despacho = $client->getObject('lexhoy_despachos_formatted', $despacho_id);

    if (!$despacho) {
        echo '<div class="error"><p>Error: No se encontró el despacho.</p></div>';
        return;
    }

    // Obtener las áreas de práctica del despacho actual
    $todas_areas = get_option('lexhoy_areas_practica', []);
    $areas_seleccionadas = isset($despacho['areas_practica']) ? (array)$despacho['areas_practica'] : [];
    
    // Si hay un mensaje de actualización, recargar los datos
    if (isset($_GET['updated']) && $_GET['updated'] == '1') {
        // Esperar un momento para que Algolia se actualice
        sleep(2);
        
        try {
            // Obtener los datos actualizados
            $despacho = $client->getObject('lexhoy_despachos_formatted', $despacho_id);
            if ($despacho) {
                $areas_seleccionadas = isset($despacho['areas_practica']) ? (array)$despacho['areas_practica'] : [];
                
                // Debug para verificar los datos
                error_log('Despacho actualizado: ' . print_r($despacho, true));
                error_log('Áreas seleccionadas actualizadas: ' . print_r($areas_seleccionadas, true));
            }
        } catch (Exception $e) {
            error_log('Error al obtener datos actualizados: ' . $e->getMessage());
        }
    }
    
    sort($todas_areas);

} catch (Exception $e) {
    echo '<div class="error"><p>Error al conectar con Algolia: ' . esc_html($e->getMessage()) . '</p></div>';
    return;
}
?>

<div class="lexhoy-despachos-search">
    <div class="search-header container-fluid px-0">
        <h1 class="mb-4">Editar Despacho</h1>
    </div>
    <div class="search-content container">
        <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Los cambios se han guardado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="despacho-form">
            <?php wp_nonce_field('lexhoy_despachos_edit', 'lexhoy_despachos_nonce'); ?>
            <input type="hidden" name="action" value="lexhoy_despachos_update">
            <input type="hidden" name="objectID" value="<?php echo esc_attr($despacho['objectID']); ?>">
            <input type="hidden" name="current_tab" id="current_tab" value="<?php echo esc_attr(isset($_GET['tab']) ? $_GET['tab'] : 'info-basica'); ?>">

            <ul class="nav nav-tabs mb-4" id="despachoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'info-basica') ? 'active' : ''; ?>" 
                            id="info-basica-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#info-basica" 
                            type="button" 
                            role="tab">Información Básica</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'areas-practica') ? 'active' : ''; ?>" 
                            id="areas-practica-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#areas-practica" 
                            type="button" 
                            role="tab">Áreas de Práctica</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'horario') ? 'active' : ''; ?>" 
                            id="horario-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#horario" 
                            type="button" 
                            role="tab">Horario</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'redes-sociales') ? 'active' : ''; ?>" 
                            id="redes-sociales-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#redes-sociales" 
                            type="button" 
                            role="tab">Redes Sociales</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'info-adicional') ? 'active' : ''; ?>" 
                            id="info-adicional-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#info-adicional" 
                            type="button" 
                            role="tab">Información Adicional</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'estado') ? 'active' : ''; ?>" 
                            id="estado-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#estado" 
                            type="button" 
                            role="tab">Estado</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'equipo') ? 'active' : ''; ?>" 
                            id="equipo-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#equipo" 
                            type="button" 
                            role="tab">Equipo</button>
                </li>
            </ul>

            <div class="tab-content" id="despachoTabsContent">
                <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'info-basica') ? 'show active' : ''; ?>" 
                     id="info-basica" 
                     role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Información Básica</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre" class="form-label">Nombre del Despacho</label>
                                        <input type="text" id="nombre" name="nombre" value="<?php echo esc_attr($despacho['nombre']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="slug" class="form-label">URL Amigable (Slug)</label>
                                        <input type="text" id="slug" name="slug" value="<?php echo esc_attr($despacho['slug'] ?? strtolower(str_replace(' ', '-', $despacho['nombre']))); ?>" class="form-control">
                                        <div class="form-text">La URL amigable para este despacho. Si lo dejas vacío, se generará automáticamente a partir del nombre.</div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <input type="text" id="direccion" name="direccion" value="<?php echo esc_attr($despacho['direccion']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="localidad" class="form-label">Localidad</label>
                                        <input type="text" id="localidad" name="localidad" value="<?php echo esc_attr($despacho['localidad']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="provincia" class="form-label">Provincia</label>
                                        <input type="text" id="provincia" name="provincia" value="<?php echo esc_attr($despacho['provincia']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="codigo_postal" class="form-label">Código Postal</label>
                                        <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo esc_attr($despacho['codigo_postal']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" id="telefono" name="telefono" value="<?php echo esc_attr($despacho['telefono']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" id="email" name="email" value="<?php echo esc_attr($despacho['email']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="web" class="form-label">Sitio Web</label>
                                        <input type="url" id="web" name="web" value="<?php echo esc_attr($despacho['web']); ?>" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'areas-practica') ? 'show active' : ''; ?>" 
                     id="areas-practica" 
                     role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Áreas de Práctica y Especialidades</h2>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="areas_practica" class="form-label">Áreas de Práctica</label>
                                        <div class="areas-container">
                                            <div class="areas-available">
                                                <h4>Áreas Disponibles</h4>
                                                <?php
                                                // Mostrar las áreas disponibles
                                                foreach ($todas_areas as $area) {
                                                    $checked = in_array($area, $areas_seleccionadas) ? 'checked' : '';
                                                    echo '<div class="area-item">';
                                                    echo '<input type="checkbox" id="area_' . esc_attr($area) . '" name="areas_practica[]" value="' . esc_attr($area) . '" ' . $checked . '>';
                                                    echo '<label for="area_' . esc_attr($area) . '">' . esc_html($area) . '</label>';
                                                    echo '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="especialidades" class="form-label">Especialidades</label>
                                        <textarea id="especialidades" name="especialidades" rows="4" class="form-control"><?php echo esc_textarea(implode("\n", $despacho['especialidades'] ?? [])); ?></textarea>
                                        <div class="form-text">Introduce cada especialidad en una línea nueva.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'horario') ? 'show active' : ''; ?>" 
                     id="horario" 
                     role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Horario</h2>
                            <div class="row g-3">
                                <?php
                                $dias = [
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miercoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sabado' => 'Sábado',
                                    'domingo' => 'Domingo'
                                ];
                                foreach ($dias as $key => $label) {
                                    echo '<div class="col-md-4">';
                                    echo '<div class="form-group">';
                                    echo '<label for="horario_' . $key . '" class="form-label">' . $label . '</label>';
                                    echo '<input type="text" id="horario_' . $key . '" name="horario[' . $key . ']" value="' . esc_attr($despacho['horario'][$key] ?? '') . '" class="form-control">';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'redes-sociales') ? 'show active' : ''; ?>" 
                     id="redes-sociales" 
                     role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Redes Sociales</h2>
                            <div class="row g-3">
                                <?php
                                $redes = [
                                    'facebook' => 'Facebook',
                                    'twitter' => 'Twitter',
                                    'linkedin' => 'LinkedIn',
                                    'instagram' => 'Instagram'
                                ];
                                foreach ($redes as $key => $label) {
                                    echo '<div class="col-md-6">';
                                    echo '<div class="form-group">';
                                    echo '<label for="redes_sociales_' . $key . '" class="form-label">' . $label . '</label>';
                                    echo '<input type="url" id="redes_sociales_' . $key . '" name="redes_sociales[' . $key . ']" value="' . esc_attr($despacho['redes_sociales'][$key] ?? '') . '" class="form-control">';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'info-adicional') ? 'show active' : ''; ?>" 
                     id="info-adicional" 
                     role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Información Adicional</h2>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="descripcion" class="form-label">Descripción</label>
                                        <textarea id="descripcion" name="descripcion" rows="5" class="form-control"><?php echo esc_textarea($despacho['descripcion']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="experiencia" class="form-label">Experiencia</label>
                                        <textarea id="experiencia" name="experiencia" rows="3" class="form-control"><?php echo esc_textarea($despacho['experiencia']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tamaño_despacho" class="form-label">Tamaño del Despacho</label>
                                        <input type="text" id="tamaño_despacho" name="tamaño_despacho" value="<?php echo esc_attr($despacho['tamaño_despacho']); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="año_fundacion" class="form-label">Año de Fundación</label>
                                        <input type="number" id="año_fundacion" name="año_fundacion" value="<?php echo esc_attr($despacho['año_fundacion']); ?>" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'estado') ? 'show active' : ''; ?>" 
                     id="estado" 
                     role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Estado y Verificación</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nivel" class="form-label">Nivel del Despacho</label>
                                        <select id="nivel" name="nivel" class="form-select">
                                            <option value="basic" <?php selected($despacho['nivel'] ?? '', 'basic'); ?>>Básico</option>
                                            <option value="verificado" <?php selected($despacho['nivel'] ?? '', 'verificado'); ?>>Verificado</option>
                                            <option value="premium" <?php selected($despacho['nivel'] ?? '', 'premium'); ?>>Premium</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado_verificacion" class="form-label">Estado de Verificación</label>
                                        <select id="estado_verificacion" name="estado_verificacion" class="form-select">
                                            <option value="pendiente" <?php selected($despacho['estado_verificacion'], 'pendiente'); ?>>Pendiente</option>
                                            <option value="verificado" <?php selected($despacho['estado_verificacion'], 'verificado'); ?>>Verificado</option>
                                            <option value="rechazado" <?php selected($despacho['estado_verificacion'], 'rechazado'); ?>>Rechazado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado_registro" class="form-label">Estado del Registro</label>
                                        <select id="estado_registro" name="estado_registro" class="form-select">
                                            <option value="activo" <?php selected($despacho['estado_registro'], 'activo'); ?>>Activo</option>
                                            <option value="inactivo" <?php selected($despacho['estado_registro'], 'inactivo'); ?>>Inactivo</option>
                                            <option value="suspendido" <?php selected($despacho['estado_registro'], 'suspendido'); ?>>Suspendido</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'equipo') ? 'show active' : ''; ?>" 
                     id="equipo" 
                     role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Equipo</h2>
                            <div id="equipo-container">
                                <?php 
                                $equipo = $despacho['equipo'] ?? [];
                                if (!empty($equipo)) {
                                    foreach ($equipo as $index => $miembro) {
                                        ?>
                                        <div class="miembro-equipo card mb-3">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Nombre</label>
                                                            <input type="text" name="equipo[<?php echo $index; ?>][nombre]" value="<?php echo esc_attr($miembro['nombre']); ?>" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Cargo/Departamento</label>
                                                            <input type="text" name="equipo[<?php echo $index; ?>][cargo]" value="<?php echo esc_attr($miembro['cargo']); ?>" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-danger btn-sm remove-miembro">Eliminar miembro</button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <button type="button" class="btn btn-primary" id="add-miembro">Añadir miembro del equipo</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" name="submit" id="submit" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Guardar Cambios
                </button>
                <a href="<?php echo admin_url('admin.php?page=lexhoy-despachos'); ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<style>
/* Estilos personalizados que complementan Bootstrap */
.lexhoy-despachos-search {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
}

.search-header {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

.search-content {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
}

/* Estilos para el alfabeto */
.alfabeto-container {
    display: flex;
    justify-content: space-between;
    width: 100%;
    flex-wrap: nowrap;
    margin-bottom: 1rem;
}

.letra-item {
    flex: 1;
    text-align: center;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.letra-item:hover {
    background-color: #f8f9fa;
}

.letra-item.active {
    background-color: #0d6efd;
    color: white;
}

/* Para pantallas muy anchas */
@media (min-width: 1400px) {
    .search-header {
        max-width: 100%;
        margin: 0;
        padding: 0;
    }
    
    .search-content {
        max-width: 1320px; /* Ancho máximo de Bootstrap xl */
        margin: 0 auto;
        padding: 0 15px;
    }
}

.lexhoy-despachos-admin .card {
    margin-bottom: 1.5rem;
}

.lexhoy-despachos-admin .form-label {
    font-weight: 500;
}

.lexhoy-despachos-admin .nav-tabs {
    border-bottom: 1px solid #dee2e6;
}

.lexhoy-despachos-admin .nav-tabs .nav-link {
    color: #495057;
}

.lexhoy-despachos-admin .nav-tabs .nav-link.active {
    color: #0d6efd;
    font-weight: 500;
}

.lexhoy-despachos-admin .form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.lexhoy-despachos-admin .btn-primary {
    padding: 0.5rem 1.5rem;
}

.lexhoy-despachos-admin .btn-secondary {
    padding: 0.5rem 1.5rem;
    margin-left: 0.5rem;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Inicializar los tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Manejo del envío del formulario
    $('.despacho-form').on('submit', function(e) {
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $spinner = $submitBtn.find('.spinner-border');

        // Mostrar spinner y deshabilitar botón
        $spinner.removeClass('d-none');
        $submitBtn.prop('disabled', true);

        // Crear y mostrar el mensaje de carga
        if (!$('#loading-message').length) {
            $('<div id="loading-message" class="alert alert-info alert-dismissible fade show mt-3" role="alert">' +
              '<div class="d-flex align-items-center">' +
              '<div class="spinner-border spinner-border-sm me-2" role="status"></div>' +
              '<div>Guardando cambios... Por favor, espere.</div>' +
              '</div>' +
              '</div>').insertAfter($form);
        }
    });

    // Manejo del equipo
    var equipoIndex = <?php echo count($equipo); ?>;
    
    $('#add-miembro').on('click', function() {
        var template = `
            <div class="miembro-equipo card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="equipo[${equipoIndex}][nombre]" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Cargo/Departamento</label>
                                <input type="text" name="equipo[${equipoIndex}][cargo]" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-success btn-sm add-miembro-confirm">Añadir</button>
                        <button type="button" class="btn btn-danger btn-sm remove-miembro">Cancelar</button>
                    </div>
                </div>
            </div>
        `;
        $('#equipo-container').append(template);
        equipoIndex++;
    });

    $(document).on('click', '.remove-miembro', function() {
        $(this).closest('.miembro-equipo').remove();
    });

    $(document).on('click', '.add-miembro-confirm', function() {
        var $miembro = $(this).closest('.miembro-equipo');
        var nombre = $miembro.find('input[name$="[nombre]"]').val();
        var cargo = $miembro.find('input[name$="[cargo]"]').val();
        
        if (!nombre || !cargo) {
            alert('Por favor, completa todos los campos');
            return;
        }
        
        // Cambiar los botones después de añadir
        $miembro.find('.mt-3').html('<button type="button" class="btn btn-danger btn-sm remove-miembro">Eliminar miembro</button>');
    });
});
</script> 