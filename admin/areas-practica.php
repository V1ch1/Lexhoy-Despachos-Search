<?php
if (!defined('ABSPATH')) {
    exit;
}

// Función para obtener todas las áreas de práctica
function get_areas_practica() {
    $areas = get_option('lexhoy_areas_practica', [
        'Familia',
        'Administrativo',
        'Penal',
        'Civil',
        'Bancario',
        'Laboral',
        'Sucesiones',
        'Vivienda',
        'Tráfico',
        'Mercantil',
        'Concursal',
        'Fiscal',
        'Protección de Datos',
        'Seguros',
        'Propiedad Intelect y Salud'
    ]);
    return $areas;
}

// Función para guardar las áreas de práctica
function save_areas_practica($areas) {
    update_option('lexhoy_areas_practica', $areas);
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'lexhoy_areas_practica')) {
        wp_die('Acción no autorizada');
    }

    switch ($_POST['action']) {
        case 'add_area':
            $areas = get_areas_practica();
            $new_area = sanitize_text_field($_POST['new_area']);
            if (!empty($new_area) && !in_array($new_area, $areas)) {
                $areas[] = $new_area;
                save_areas_practica($areas);
            }
            break;

        case 'delete_area':
            $areas = get_areas_practica();
            $area_to_delete = sanitize_text_field($_POST['area_to_delete']);
            if (($key = array_search($area_to_delete, $areas)) !== false) {
                unset($areas[$key]);
                save_areas_practica(array_values($areas));
            }
            break;

        case 'edit_area':
            $areas = get_areas_practica();
            $old_area = sanitize_text_field($_POST['old_area']);
            $new_area = sanitize_text_field($_POST['new_area']);
            if (($key = array_search($old_area, $areas)) !== false) {
                $areas[$key] = $new_area;
                save_areas_practica($areas);
            }
            break;
    }
}

$areas = get_areas_practica();
?>

<div class="wrap">
    <h1>Gestión de Áreas de Práctica</h1>

    <!-- Formulario para añadir nueva área -->
    <div class="card">
        <h2>Añadir Nueva Área</h2>
        <form method="post" action="">
            <?php wp_nonce_field('lexhoy_areas_practica'); ?>
            <input type="hidden" name="action" value="add_area">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="new_area">Nombre de la Área</label></th>
                    <td>
                        <input type="text" id="new_area" name="new_area" class="regular-text" required>
                        <p class="description">Introduce el nombre de la nueva área de práctica.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" value="Añadir Área">
            </p>
        </form>
    </div>

    <!-- Lista de áreas existentes -->
    <div class="card">
        <h2>Áreas de Práctica Existentes</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Área de Práctica</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($areas as $area): ?>
                <tr>
                    <td>
                        <span class="area-name"><?php echo esc_html($area); ?></span>
                        <form method="post" action="" class="edit-form" style="display: none;">
                            <?php wp_nonce_field('lexhoy_areas_practica'); ?>
                            <input type="hidden" name="action" value="edit_area">
                            <input type="hidden" name="old_area" value="<?php echo esc_attr($area); ?>">
                            <input type="text" name="new_area" value="<?php echo esc_attr($area); ?>" class="regular-text">
                            <button type="submit" class="button button-small">Guardar</button>
                            <button type="button" class="button button-small cancel-edit">Cancelar</button>
                        </form>
                    </td>
                    <td>
                        <button type="button" class="button button-small edit-area">Editar</button>
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field('lexhoy_areas_practica'); ?>
                            <input type="hidden" name="action" value="delete_area">
                            <input type="hidden" name="area_to_delete" value="<?php echo esc_attr($area); ?>">
                            <button type="submit" class="button button-small" onclick="return confirm('¿Estás seguro de que deseas eliminar esta área?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.card {
    margin-bottom: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.edit-form {
    margin-top: 10px;
}
.edit-form input[type="text"] {
    margin-right: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.edit-area').on('click', function() {
        var $row = $(this).closest('tr');
        $row.find('.area-name').hide();
        $row.find('.edit-form').show();
    });

    $('.cancel-edit').on('click', function() {
        var $row = $(this).closest('tr');
        $row.find('.area-name').show();
        $row.find('.edit-form').hide();
    });
});
</script> 