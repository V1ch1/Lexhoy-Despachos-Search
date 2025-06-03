<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "Autoloader cargado correctamente\n";

if (class_exists('\Algolia\AlgoliaSearch\Client')) {
    echo "La clase de Algolia está disponible\n";
} else {
    echo "La clase de Algolia NO está disponible\n";
}

echo "Directorio actual: " . __DIR__ . "\n";
echo "Ruta del autoloader: " . __DIR__ . '/vendor/autoload.php' . "\n";
echo "¿Existe el autoloader? " . (file_exists(__DIR__ . '/vendor/autoload.php') ? 'Sí' : 'No') . "\n"; 