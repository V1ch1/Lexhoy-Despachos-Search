<?php
// Obtener la ruta base de WordPress
$wp_root = dirname(dirname(dirname(dirname(__FILE__))));
$log_file = $wp_root . '/debug.log';

// Forzar la escritura de logs
ini_set('log_errors', 1);
ini_set('error_log', $log_file);
error_reporting(E_ALL);

// Intentar escribir en el log
error_log('=== PRUEBA DE ESCRITURA DIRECTA ===');
error_log('Fecha y hora: ' . date('Y-m-d H:i:s'));
error_log('Ruta del script: ' . __FILE__);
error_log('Ruta del log: ' . $log_file);

// Intentar escribir en el archivo directamente
file_put_contents($log_file, "=== PRUEBA DE ESCRITURA DIRECTA CON file_put_contents ===\n", FILE_APPEND);
file_put_contents($log_file, "Fecha y hora: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($log_file, "Ruta del script: " . __FILE__ . "\n", FILE_APPEND);
file_put_contents($log_file, "Ruta del log: " . $log_file . "\n", FILE_APPEND);

echo "Prueba de log completada. Verifica el archivo debug.log en: " . $log_file; 