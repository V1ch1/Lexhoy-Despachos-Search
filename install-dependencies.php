<?php
/**
 * Script para instalar las dependencias necesarias de Algolia
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir las URLs de las dependencias
$dependencies = [
    'psr/http-message' => 'https://github.com/php-fig/http-message/archive/refs/tags/1.1.zip',
    'psr/log' => 'https://github.com/php-fig/log/archive/refs/tags/1.1.4.zip',
    'psr/simple-cache' => 'https://github.com/php-fig/simple-cache/archive/refs/tags/1.0.1.zip'
];

// Directorio base para las dependencias
$vendor_dir = __DIR__ . '/lib/algoliasearch-client-php/vendor';

// Crear el directorio vendor si no existe
if (!file_exists($vendor_dir)) {
    mkdir($vendor_dir, 0755, true);
}

// Función para descargar y extraer un archivo ZIP
function download_and_extract($url, $destination) {
    $temp_file = download_url($url);
    if (is_wp_error($temp_file)) {
        error_log('Error al descargar: ' . $temp_file->get_error_message());
        return false;
    }

    $zip = new ZipArchive;
    if ($zip->open($temp_file) === TRUE) {
        $zip->extractTo($destination);
        $zip->close();
        unlink($temp_file);
        return true;
    } else {
        error_log('Error al extraer el archivo ZIP');
        unlink($temp_file);
        return false;
    }
}

// Instalar cada dependencia
foreach ($dependencies as $package => $url) {
    $package_dir = $vendor_dir . '/' . $package;
    
    // Crear el directorio del paquete si no existe
    if (!file_exists($package_dir)) {
        mkdir($package_dir, 0755, true);
    }
    
    // Descargar y extraer la dependencia
    if (download_and_extract($url, $package_dir)) {
        error_log("Dependencia {$package} instalada correctamente");
    } else {
        error_log("Error al instalar la dependencia {$package}");
    }
}

// Verificar que todas las dependencias estén instaladas
$all_installed = true;
foreach ($dependencies as $package => $url) {
    $package_dir = $vendor_dir . '/' . $package;
    if (!file_exists($package_dir)) {
        $all_installed = false;
        break;
    }
}

if ($all_installed) {
    error_log('Todas las dependencias se han instalado correctamente');
} else {
    error_log('Hubo errores al instalar algunas dependencias');
} 