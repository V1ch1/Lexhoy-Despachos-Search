<?php
/**
 * Desinstalación del plugin Lexhoy Despachos
 */

// Si WordPress no llama este archivo, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Función para eliminar directorios recursivamente
function lexhoy_despachos_remove_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!lexhoy_despachos_remove_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    // Intentar eliminar el directorio
    $attempts = 0;
    $max_attempts = 3;
    $success = false;

    while ($attempts < $max_attempts && !$success) {
        $success = rmdir($dir);
        if (!$success) {
            $attempts++;
            sleep(1); // Esperar 1 segundo entre intentos
        }
    }

    return $success;
}

// Obtener la ruta del plugin
$plugin_dir = plugin_dir_path(__FILE__);

// Eliminar archivos y directorios específicos
$directories_to_remove = array(
    $plugin_dir . 'vendor',
    $plugin_dir . 'node_modules',
    $plugin_dir . 'dist'
);

$files_to_remove = array(
    $plugin_dir . 'composer.phar',
    $plugin_dir . 'composer-setup.php',
    $plugin_dir . 'composer.lock',
    $plugin_dir . 'package-lock.json'
);

// Eliminar directorios
foreach ($directories_to_remove as $dir) {
    if (file_exists($dir)) {
        lexhoy_despachos_remove_directory($dir);
    }
}

// Eliminar archivos
foreach ($files_to_remove as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

// Eliminar opciones de WordPress
delete_option('lexhoy_despachos_settings');
delete_option('lexhoy_despachos_slugs_updated');

// Eliminar la página de búsqueda
$search_page = get_page_by_path('buscador-de-despachos');
if ($search_page) {
    wp_delete_post($search_page->ID, true);
}

// Limpiar las reglas de reescritura
flush_rewrite_rules(); 