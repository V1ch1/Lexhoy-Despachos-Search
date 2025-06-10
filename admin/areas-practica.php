<?php
if (!defined('ABSPATH')) {
    exit;
}

// Función para obtener todas las áreas de práctica
function get_areas_practica() {
    $areas = get_option('lexhoy_areas_practica', [
        'Derecho Civil',
        'Derecho Penal',
        'Derecho Laboral',
        'Derecho Mercantil',
        'Derecho Administrativo',
        'Derecho Internacional',
        'Derecho de Familia',
        'Derecho Inmobiliario',
        'Derecho Tributario',
        'Derecho de Seguros',
        'Derecho de la Propiedad Intelectual',
        'Derecho de la Competencia',
        'Derecho de la Unión Europea',
        'Derecho de la Tecnología',
        'Derecho de la Energía'
    ]);
    return $areas;
}

// Función para guardar las áreas de práctica
function save_areas_practica($areas) {
    update_option('lexhoy_areas_practica', $areas);
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'lexhoy_areas_practica')) {
        wp_die('Acción no autorizada');
    }

    if (isset($_POST['add_area'])) {
        $areas = get_areas_practica();
        $new_area = sanitize_text_field($_POST['new_area']);
        if (!empty($new_area) && !in_array($new_area, $areas)) {
            $areas[] = $new_area;
            save_areas_practica($areas);
        }
    }

    if (isset($_POST['delete_area'])) {
        $areas = get_areas_practica();
        $area_to_delete = sanitize_text_field($_POST['delete_area']);
        $areas = array_diff($areas, [$area_to_delete]);
        save_areas_practica(array_values($areas));
    }

    if (isset($_POST['reorder_areas'])) {
        $areas = get_areas_practica();
        $new_order = array_map('sanitize_text_field', $_POST['areas_order']);
        $areas = array_intersect($new_order, $areas);
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
            <?php foreach ($areas as $area): ?>
                <div class="area-item">
                    <span class="area-name"><?php echo esc_html($area); ?></span>
                    <button type="submit" name="delete_area" value="<?php echo esc_attr($area); ?>" class="button button-small" onclick="return confirm('¿Estás seguro de que deseas eliminar esta área?');">Eliminar</button>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<style>
.areas-list {
    margin-top: 20px;
}

.area-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    padding: 10px;
    background: #fff;
    border: 1px solid #ddd;
}

.area-name {
    flex-grow: 1;
    margin-right: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    margin-right: 10px;
}
</style> 