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

<div class="wrap lexhoy-despachos-admin">
    <h1>Editar Despacho</h1>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Los cambios se han guardado correctamente.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="despacho-form">
        <?php wp_nonce_field('lexhoy_despachos_edit', 'lexhoy_despachos_nonce'); ?>
        <input type="hidden" name="action" value="lexhoy_despachos_update">
        <input type="hidden" name="objectID" value="<?php echo esc_attr($despacho['objectID']); ?>">
        <input type="hidden" name="current_tab" id="current_tab" value="<?php echo esc_attr(isset($_GET['tab']) ? $_GET['tab'] : 'info-basica'); ?>">

        <div class="nav-tab-wrapper">
            <a href="#info-basica" class="nav-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'info-basica') ? 'nav-tab-active' : ''; ?>">Información Básica</a>
            <a href="#areas-practica" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'areas-practica') ? 'nav-tab-active' : ''; ?>">Áreas de Práctica</a>
            <a href="#horario" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'horario') ? 'nav-tab-active' : ''; ?>">Horario</a>
            <a href="#redes-sociales" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'redes-sociales') ? 'nav-tab-active' : ''; ?>">Redes Sociales</a>
            <a href="#info-adicional" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'info-adicional') ? 'nav-tab-active' : ''; ?>">Información Adicional</a>
            <a href="#estado" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'estado') ? 'nav-tab-active' : ''; ?>">Estado</a>
            <a href="#equipo" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'equipo') ? 'nav-tab-active' : ''; ?>">Equipo</a>
        </div>

        <div id="info-basica" class="tab-content <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'info-basica') ? 'active' : ''; ?>">
            <div class="card">
                <h2>Información Básica</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="nombre">Nombre del Despacho</label></th>
                        <td><input type="text" id="nombre" name="nombre" value="<?php echo esc_attr($despacho['nombre']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slug">URL Amigable (Slug)</label></th>
                        <td>
                            <input type="text" id="slug" name="slug" value="<?php echo esc_attr($despacho['slug'] ?? strtolower(str_replace(' ', '-', $despacho['nombre']))); ?>" class="regular-text">
                            <p class="description">La URL amigable para este despacho. Si lo dejas vacío, se generará automáticamente a partir del nombre.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="direccion">Dirección</label></th>
                        <td><input type="text" id="direccion" name="direccion" value="<?php echo esc_attr($despacho['direccion']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="localidad">Localidad</label></th>
                        <td><input type="text" id="localidad" name="localidad" value="<?php echo esc_attr($despacho['localidad']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="provincia">Provincia</label></th>
                        <td><input type="text" id="provincia" name="provincia" value="<?php echo esc_attr($despacho['provincia']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="codigo_postal">Código Postal</label></th>
                        <td><input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo esc_attr($despacho['codigo_postal']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="telefono">Teléfono</label></th>
                        <td><input type="tel" id="telefono" name="telefono" value="<?php echo esc_attr($despacho['telefono']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email">Email</label></th>
                        <td><input type="email" id="email" name="email" value="<?php echo esc_attr($despacho['email']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="web">Sitio Web</label></th>
                        <td><input type="url" id="web" name="web" value="<?php echo esc_attr($despacho['web']); ?>" class="regular-text"></td>
                    </tr>
                </table>
            </div>
        </div>

        <div id="areas-practica" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'areas-practica') ? 'active' : ''; ?>">
            <div class="card">
                <h2>Áreas de Práctica y Especialidades</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="areas_practica">Áreas de Práctica</label></th>
                        <td>
                            <div class="form-group">
                                <label for="areas_practica">Áreas de Práctica:</label>
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
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="especialidades">Especialidades</label></th>
                        <td>
                            <textarea id="especialidades" name="especialidades" rows="4" class="large-text"><?php echo esc_textarea(implode("\n", $despacho['especialidades'] ?? [])); ?></textarea>
                            <p class="description">Introduce cada especialidad en una línea nueva.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div id="horario" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'horario') ? 'active' : ''; ?>">
            <div class="card">
                <h2>Horario</h2>
                <table class="form-table">
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
                        echo '<tr>';
                        echo '<th scope="row"><label for="horario_' . $key . '">' . $label . '</label></th>';
                        echo '<td><input type="text" id="horario_' . $key . '" name="horario[' . $key . ']" value="' . esc_attr($despacho['horario'][$key] ?? '') . '" class="regular-text"></td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
        </div>

        <div id="redes-sociales" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'redes-sociales') ? 'active' : ''; ?>">
            <div class="card">
                <h2>Redes Sociales</h2>
                <table class="form-table">
                    <?php
                    $redes = [
                        'facebook' => 'Facebook',
                        'twitter' => 'Twitter',
                        'linkedin' => 'LinkedIn',
                        'instagram' => 'Instagram'
                    ];
                    foreach ($redes as $key => $label) {
                        echo '<tr>';
                        echo '<th scope="row"><label for="redes_sociales_' . $key . '">' . $label . '</label></th>';
                        echo '<td><input type="url" id="redes_sociales_' . $key . '" name="redes_sociales[' . $key . ']" value="' . esc_attr($despacho['redes_sociales'][$key] ?? '') . '" class="regular-text"></td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
        </div>

        <div id="info-adicional" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'info-adicional') ? 'active' : ''; ?>">
            <div class="card">
                <h2>Información Adicional</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="descripcion">Descripción</label></th>
                        <td>
                            <textarea id="descripcion" name="descripcion" rows="5" class="large-text"><?php echo esc_textarea($despacho['descripcion']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="experiencia">Experiencia</label></th>
                        <td>
                            <textarea id="experiencia" name="experiencia" rows="3" class="large-text"><?php echo esc_textarea($despacho['experiencia']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tamaño_despacho">Tamaño del Despacho</label></th>
                        <td><input type="text" id="tamaño_despacho" name="tamaño_despacho" value="<?php echo esc_attr($despacho['tamaño_despacho']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="año_fundacion">Año de Fundación</label></th>
                        <td><input type="number" id="año_fundacion" name="año_fundacion" value="<?php echo esc_attr($despacho['año_fundacion']); ?>" class="regular-text"></td>
                    </tr>
                </table>
            </div>
        </div>

        <div id="estado" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'estado') ? 'active' : ''; ?>">
            <div class="card">
                <h2>Estado y Verificación</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="nivel">Nivel del Despacho</label></th>
                        <td>
                            <select id="nivel" name="nivel">
                                <option value="basic" <?php selected($despacho['nivel'] ?? '', 'basic'); ?>>Básico</option>
                                <option value="verificado" <?php selected($despacho['nivel'] ?? '', 'verificado'); ?>>Verificado</option>
                                <option value="premium" <?php selected($despacho['nivel'] ?? '', 'premium'); ?>>Premium</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="estado_verificacion">Estado de Verificación</label></th>
                        <td>
                            <select id="estado_verificacion" name="estado_verificacion">
                                <option value="pendiente" <?php selected($despacho['estado_verificacion'], 'pendiente'); ?>>Pendiente</option>
                                <option value="verificado" <?php selected($despacho['estado_verificacion'], 'verificado'); ?>>Verificado</option>
                                <option value="rechazado" <?php selected($despacho['estado_verificacion'], 'rechazado'); ?>>Rechazado</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="estado_registro">Estado del Registro</label></th>
                        <td>
                            <select id="estado_registro" name="estado_registro">
                                <option value="activo" <?php selected($despacho['estado_registro'], 'activo'); ?>>Activo</option>
                                <option value="inactivo" <?php selected($despacho['estado_registro'], 'inactivo'); ?>>Inactivo</option>
                                <option value="suspendido" <?php selected($despacho['estado_registro'], 'suspendido'); ?>>Suspendido</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div id="equipo" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'equipo') ? 'active' : ''; ?>">
            <div class="card">
                <h2>Equipo</h2>
                <div id="equipo-container">
                    <?php 
                    $equipo = $despacho['equipo'] ?? [];
                    if (!empty($equipo)) {
                        foreach ($equipo as $index => $miembro) {
                            ?>
                            <div class="miembro-equipo">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><label>Nombre</label></th>
                                        <td>
                                            <input type="text" name="equipo[<?php echo $index; ?>][nombre]" value="<?php echo esc_attr($miembro['nombre']); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Cargo/Departamento</label></th>
                                        <td>
                                            <input type="text" name="equipo[<?php echo $index; ?>][cargo]" value="<?php echo esc_attr($miembro['cargo']); ?>" class="regular-text">
                                        </td>
                                    </tr>
                                </table>
                                <div class="miembro-actions">
                                    <button type="button" class="button remove-miembro">Eliminar miembro</button>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                <button type="button" class="button" id="add-miembro">Añadir miembro del equipo</button>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Cambios">
            <span class="spinner" style="float: none; margin-top: 4px;"></span>
            <a href="<?php echo admin_url('admin.php?page=lexhoy-despachos'); ?>" class="button">Cancelar</a>
        </p>
    </form>
</div>

<style>
.despacho-form .card {
    margin-bottom: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    width: 100%;
    max-width: 1200px;
    box-sizing: border-box;
}
.despacho-form .card h2 {
    margin-top: 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}
.despacho-form .form-table {
    width: 100%;
    max-width: 1200px;
}
.despacho-form .form-table th {
    width: 200px;
    padding: 15px 10px 15px 0;
}
.despacho-form .form-table td {
    padding: 15px 10px;
    width: calc(100% - 200px);
}
.despacho-form select[multiple] {
    height: auto;
    min-height: 150px;
    width: 100%;
    padding: 8px;
}
.despacho-form select[multiple] option {
    padding: 8px;
    margin: 2px 0;
    border-radius: 3px;
}
.despacho-form select[multiple] option:checked {
    background: #2271b1 linear-gradient(0deg, #2271b1 0%, #2271b1 100%);
    color: #fff;
}
.despacho-form input[type="text"],
.despacho-form input[type="email"],
.despacho-form input[type="url"],
.despacho-form input[type="tel"],
.despacho-form input[type="number"],
.despacho-form textarea,
.despacho-form select {
    width: 100%;
    max-width: 100%;
}
.despacho-form .submit {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.miembro-equipo {
    background: #f9f9f9;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}
.miembro-equipo .remove-miembro {
    margin-top: 10px;
    color: #dc3232;
}
.miembro-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}
.miembro-actions .button {
    margin: 0;
}
#add-miembro {
    margin-top: 15px;
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
    padding: 5px 15px;
    height: auto;
    line-height: 2.15384615;
    min-height: 30px;
    display: inline-flex;
    align-items: center;
}
#add-miembro:hover {
    background: #135e96;
    border-color: #135e96;
    color: #fff;
}
#add-miembro:before {
    content: "+";
    margin-right: 5px;
    font-size: 18px;
    line-height: 1;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.nav-tab-wrapper {
    margin-bottom: 20px;
}
.areas-selector {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 10px;
    width: 100%;
    max-width: 1200px;
}
.areas-column {
    flex: 1;
    min-width: 300px;
}
.areas-column h4 {
    margin: 0 0 8px 0;
    padding: 0;
    font-size: 14px;
    font-weight: 600;
}
.areas-column select {
    width: 100%;
    height: 300px;
    padding: 8px;
    font-size: 14px;
}
.areas-column select option {
    padding: 10px;
    margin: 2px 0;
    border-radius: 3px;
    font-size: 14px;
}
.areas-column select option:checked {
    background: #2271b1 linear-gradient(0deg, #2271b1 0%, #2271b1 100%);
    color: #fff;
}
.areas-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-top: 50px;
}
.areas-buttons .button {
    padding: 8px 15px;
    min-width: 50px;
    text-align: center;
    font-size: 16px;
}
.spinner {
    float: none;
    margin: 4px 10px 0 0;
    visibility: hidden;
}
.spinner.is-active {
    visibility: visible;
}
#loading-message {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin: 10px 0;
    padding: 12px;
    position: relative;
}
#loading-message p {
    margin: 0;
    padding: 0;
    font-size: 14px;
    line-height: 1.5;
}
.button-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Manejo de pestañas
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Actualizar pestañas
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mostrar contenido
        $('.tab-content').removeClass('active');
        $(target).addClass('active');

        // Actualizar el campo oculto con la pestaña actual
        $('#current_tab').val(target.replace('#', ''));
    });

    // Manejo del envío del formulario
    $('.despacho-form').on('submit', function(e) {
        // Guardar la pestaña actual
        var currentTab = $('.nav-tab-active').attr('href').replace('#', '');
        $('#current_tab').val(currentTab);

        // Mostrar spinner y mensaje de carga
        $('.spinner').addClass('is-active');
        $('.submit .button-primary').prop('disabled', true);
        
        // Crear y mostrar el mensaje de carga si no existe
        if (!$('#loading-message').length) {
            $('<div id="loading-message" class="notice notice-info" style="margin: 10px 0; padding: 10px;"><p>Guardando cambios... Por favor, espere.</p></div>')
                .insertAfter('.despacho-form');
        }
    });

    // Manejo del equipo
    var equipoIndex = <?php echo count($equipo); ?>;
    
    $('#add-miembro').on('click', function() {
        var template = `
            <div class="miembro-equipo">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>Nombre</label></th>
                        <td>
                            <input type="text" name="equipo[${equipoIndex}][nombre]" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Cargo/Departamento</label></th>
                        <td>
                            <input type="text" name="equipo[${equipoIndex}][cargo]" class="regular-text">
                        </td>
                    </tr>
                </table>
                <div class="miembro-actions">
                    <button type="button" class="button button-primary add-miembro-confirm">Añadir</button>
                    <button type="button" class="button remove-miembro">Cancelar</button>
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
        $miembro.find('.miembro-actions').html('<button type="button" class="button remove-miembro">Eliminar miembro</button>');
    });

    // Manejo de áreas de práctica
    $('#add-area').on('click', function() {
        $('#areas_disponibles option:selected').each(function() {
            var value = $(this).val();
            var text = $(this).text();
            $('#areas_seleccionadas').append(new Option(text, value));
            $(this).remove();
        });
    });

    $('#remove-area').on('click', function() {
        $('#areas_seleccionadas option:selected').each(function() {
            var value = $(this).val();
            var text = $(this).text();
            $('#areas_disponibles').append(new Option(text, value));
            $(this).remove();
        });
    });

    // Doble clic para mover áreas
    $('#areas_disponibles').on('dblclick', 'option', function() {
        var value = $(this).val();
        var text = $(this).text();
        $('#areas_seleccionadas').append(new Option(text, value));
        $(this).remove();
    });

    $('#areas_seleccionadas').on('dblclick', 'option', function() {
        var value = $(this).val();
        var text = $(this).text();
        $('#areas_disponibles').append(new Option(text, value));
        $(this).remove();
    });
});
</script> 