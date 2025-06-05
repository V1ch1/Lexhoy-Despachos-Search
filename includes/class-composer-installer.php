<?php
/**
 * Clase para manejar la instalación automática de Composer
 */
class Lexhoy_Composer_Installer {
    private $composer_path;
    private $plugin_dir;
    private $composer_installer_url = 'https://getcomposer.org/installer';
    private $composer_installer_path;
    private $composer_phar_path;
    private $vendor_path;

    public function __construct() {
        $this->plugin_dir = plugin_dir_path(dirname(__FILE__));
        $this->composer_installer_path = $this->plugin_dir . 'composer-setup.php';
        $this->composer_phar_path = $this->plugin_dir . 'composer.phar';
        $this->vendor_path = $this->plugin_dir . 'vendor';
    }

    /**
     * Verifica y prepara el entorno de Composer
     */
    public function check_composer() {
        try {
            // Verificar si ya existe el autoloader
            if (file_exists($this->vendor_path . '/autoload.php')) {
                return true;
            }

            // Verificar si composer.phar existe
            if (!file_exists($this->composer_phar_path)) {
                $this->install_composer();
            }

            // Verificar si el directorio vendor existe
            if (!file_exists($this->vendor_path)) {
                $this->run_composer_install();
            }

            return true;
        } catch (Exception $e) {
            error_log('Error al verificar Composer: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica los requisitos del sistema
     */
    public function check_requirements() {
        $requirements = array(
            'php_version' => version_compare(PHP_VERSION, '7.2', '>='),
            'curl_enabled' => function_exists('curl_init'),
            'exec_enabled' => function_exists('exec'),
            'writable_dir' => is_writable($this->plugin_dir),
            'composer_exists' => file_exists($this->composer_phar_path),
            'vendor_exists' => file_exists($this->vendor_path . '/autoload.php')
        );

        // Añadir información de diagnóstico
        $diagnostic_info = array(
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'plugin_dir' => $this->plugin_dir,
            'plugin_dir_writable' => is_writable($this->plugin_dir),
            'plugin_dir_permissions' => substr(sprintf('%o', fileperms($this->plugin_dir)), -4)
        );

        // Registrar información de diagnóstico
        error_log('Lexhoy Despachos - Información de diagnóstico: ' . print_r($diagnostic_info, true));

        return array(
            'requirements' => $requirements,
            'diagnostic' => $diagnostic_info
        );
    }

    /**
     * Instala Composer
     */
    private function install_composer() {
        try {
            // Intentar descargar el instalador
            $installer = $this->download_composer_installer();
            if (!$installer) {
                throw new Exception('No se pudo descargar el instalador de Composer');
            }

            // Guardar el instalador
            if (!file_put_contents($this->composer_installer_path, $installer)) {
                throw new Exception('No se pudo guardar el instalador de Composer');
            }

            // Ejecutar el instalador
            include $this->composer_installer_path;

            // Mover composer.phar a la ubicación correcta
            if (!rename('composer.phar', $this->composer_phar_path)) {
                throw new Exception('No se pudo mover composer.phar');
            }

            // Eliminar el instalador
            unlink($this->composer_installer_path);

            return true;
        } catch (Exception $e) {
            error_log('Error al instalar Composer: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Descarga el instalador de Composer
     */
    private function download_composer_installer() {
        // Intentar con file_get_contents
        $installer = @file_get_contents($this->composer_installer_url);
        if ($installer !== false) {
            return $installer;
        }

        // Intentar con cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->composer_installer_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $installer = curl_exec($ch);
            curl_close($ch);
            if ($installer !== false) {
                return $installer;
            }
        }

        // Intentar con wget
        if (function_exists('exec')) {
            $command = 'wget -q -O - ' . escapeshellarg($this->composer_installer_url);
            $installer = @exec($command);
            if ($installer !== false) {
                return $installer;
            }
        }

        return false;
    }

    /**
     * Ejecuta composer install
     */
    private function run_composer_install() {
        try {
            // Verificar permisos del directorio
            if (!is_writable($this->plugin_dir)) {
                throw new Exception('El directorio del plugin no tiene permisos de escritura');
            }

            // Intentar con PHP
            $command = 'php ' . escapeshellarg($this->composer_phar_path) . ' install --no-dev --optimize-autoloader';
            exec($command, $output, $return_var);

            if ($return_var !== 0) {
                // Intentar con composer global
                $command = 'composer install --no-dev --optimize-autoloader';
                exec($command, $output, $return_var);

                if ($return_var !== 0) {
                    throw new Exception('No se pudo ejecutar composer install');
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('Error al ejecutar composer install: ' . $e->getMessage());
            throw $e;
        }
    }
} 