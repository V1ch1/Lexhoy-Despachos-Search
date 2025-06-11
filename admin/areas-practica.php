<?php
if (!defined('ABSPATH')) {
    exit;
}

// Función para obtener todas las áreas de práctica
function get_areas_practica() {
    $areas = get_option('lexhoy_areas_practica', []);
    
    // Si las áreas están almacenadas como strings, convertirlas al nuevo formato
    if (!empty($areas) && is_string($areas[0])) {
        $new_areas = [];
        foreach ($areas as $area) {
            $new_areas[] = [
                'nombre' => $area,
                'slug' => sanitize_title($area)
            ];
        }
        update_option('lexhoy_areas_practica', $new_areas);
        return $new_areas;
    }
    
    if (empty($areas)) {
        // Áreas por defecto
        $areas = [
            ['nombre' => 'Derecho Civil', 'slug' => 'derecho-civil'],
            ['nombre' => 'Derecho Penal', 'slug' => 'derecho-penal'],
            ['nombre' => 'Derecho Laboral', 'slug' => 'derecho-laboral'],
            ['nombre' => 'Derecho Mercantil', 'slug' => 'derecho-mercantil'],
            ['nombre' => 'Derecho Administrativo', 'slug' => 'derecho-administrativo'],
            ['nombre' => 'Derecho Internacional', 'slug' => 'derecho-internacional'],
            ['nombre' => 'Derecho de Familia', 'slug' => 'derecho-de-familia'],
            ['nombre' => 'Derecho Inmobiliario', 'slug' => 'derecho-inmobiliario'],
            ['nombre' => 'Derecho Tributario', 'slug' => 'derecho-tributario'],
            ['nombre' => 'Derecho de Seguros', 'slug' => 'derecho-de-seguros'],
            ['nombre' => 'Derecho de la Propiedad Intelectual', 'slug' => 'derecho-de-la-propiedad-intelectual'],
            ['nombre' => 'Derecho de la Competencia', 'slug' => 'derecho-de-la-competencia'],
            ['nombre' => 'Derecho de la Unión Europea', 'slug' => 'derecho-de-la-union-europea'],
            ['nombre' => 'Derecho de la Tecnología', 'slug' => 'derecho-de-la-tecnologia'],
            ['nombre' => 'Derecho de la Energía', 'slug' => 'derecho-de-la-energia']
        ];
        update_option('lexhoy_areas_practica', $areas);
    }
    return $areas;
}

// Función para guardar las áreas de práctica
function save_areas_practica($areas) {
    update_option('lexhoy_areas_practica', $areas);
    
    // Sincronizar con Algolia
    try {
        $settings = get_option('lexhoy_despachos_settings');
        if (!empty($settings['algolia_app_id']) && !empty($settings['algolia_admin_api_key'])) {
            $client = \Algolia\AlgoliaSearch\Api\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_admin_api_key']
            );
            
            // Obtener todos los despachos
            $results = $client->searchSingleIndex('lexhoy_despachos_formatted', [
                'hitsPerPage' => 1000
            ]);
            
            if (isset($results['hits'])) {
                foreach ($results['hits'] as $despacho) {
                    // Actualizar las áreas en cada despacho
                    $despacho['areas_practica'] = array_map(function($area) {
                        return $area['nombre'];
                    }, $areas);
                    
                    // Actualizar en Algolia
                    $client->saveObject('lexhoy_despachos_formatted', $despacho);
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error al sincronizar áreas con Algolia: ' . $e->getMessage());
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'lexhoy_areas_practica')) {
        wp_die('Acción no autorizada');
    }

    if (isset($_POST['add_area'])) {
        $areas = get_areas_practica();
        $new_area = [
            'nombre' => sanitize_text_field($_POST['new_area']),
            'slug' => sanitize_title($_POST['new_area'])
        ];
        if (!empty($new_area['nombre']) && !in_array($new_area['nombre'], array_column($areas, 'nombre'))) {
            $areas[] = $new_area;
            save_areas_practica($areas);
        }
    }

    if (isset($_POST['edit_area'])) {
        $areas = get_areas_practica();
        $index = intval($_POST['area_index']);
        if (isset($areas[$index])) {
            $areas[$index]['nombre'] = sanitize_text_field($_POST['edit_area']);
            $areas[$index]['slug'] = sanitize_title($_POST['edit_area']);
            save_areas_practica($areas);
        }
    }

    if (isset($_POST['delete_area'])) {
        $areas = get_areas_practica();
        $area_to_delete = sanitize_text_field($_POST['delete_area']);
        $areas = array_filter($areas, function($area) use ($area_to_delete) {
            return $area['nombre'] !== $area_to_delete;
        });
        save_areas_practica(array_values($areas));
    }

    if (isset($_POST['reorder_areas'])) {
        $areas = get_areas_practica();
        $new_order = array_map('sanitize_text_field', $_POST['areas_order']);
        $areas = array_intersect_key($areas, array_flip($new_order));
        save_areas_practica($areas);
    }
}

$areas = get_areas_practica();
?>

<div class="wrap">
    <h1>Gestión de Áreas</h1>

    <!-- Formulario para añadir nueva área -->
    <form method="post" action="">
        <?php wp_nonce_field('lexhoy_areas_practica'); ?>
        <div class="form-group">
            <label for="new_area">Añadir nueva área:</label>
            <input type="text" id="new_area" name="new_area" class="regular-text">
            <input type="submit" name="add_area" class="button button-primary" value="Añadir">
        </div>
    </form>

    <!-- Lista de áreas -->
    <form method="post" action="" id="areas-form">
        <?php wp_nonce_field('lexhoy_areas_practica'); ?>
        <div class="areas-list">
            <?php foreach ($areas as $index => $area): ?>
                <div class="area-item">
                    <div class="area-content">
                        <span class="area-name"><?php echo esc_html($area['nombre']); ?></span>
                        <span class="area-slug">(<?php echo esc_html($area['slug']); ?>)</span>
                    </div>
                    <div class="area-actions">
                        <button type="button" class="button button-small edit-area" data-index="<?php echo $index; ?>" data-nombre="<?php echo esc_attr($area['nombre']); ?>">Editar</button>
                        <button type="submit" name="delete_area" value="<?php echo esc_attr($area['nombre']); ?>" class="button button-small button-link-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar esta área?');">Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<!-- Modal de edición -->
<div id="edit-area-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>Editar Área</h2>
        <form method="post" action="">
            <?php wp_nonce_field('lexhoy_areas_practica'); ?>
            <input type="hidden" name="area_index" id="edit_area_index">
            <div class="form-group">
                <label for="edit_area">Nombre del área:</label>
                <input type="text" id="edit_area" name="edit_area" class="regular-text">
            </div>
            <div class="form-actions">
                <button type="submit" name="edit_area" class="button button-primary">Guardar</button>
                <button type="button" class="button cancel-edit">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<style>
.areas-list {
    margin-top: 20px;
}

.area-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.area-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.area-name {
    font-weight: 500;
}

.area-slug {
    color: #666;
    font-size: 0.9em;
}

.area-actions {
    display: flex;
    gap: 5px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    margin-right: 10px;
}

/* Estilos del modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    min-width: 400px;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Mostrar modal de edición
    $('.edit-area').on('click', function() {
        var index = $(this).data('index');
        var nombre = $(this).data('nombre');
        $('#edit_area_index').val(index);
        $('#edit_area').val(nombre);
        $('#edit-area-modal').show();
    });

    // Cerrar modal
    $('.cancel-edit').on('click', function() {
        $('#edit-area-modal').hide();
    });

    // Cerrar modal al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).is('#edit-area-modal')) {
            $('#edit-area-modal').hide();
        }
    });
});
</script> 