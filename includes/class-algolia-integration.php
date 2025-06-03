<?php
if (!defined('ABSPATH')) {
    exit;
}

class LexhoyDespachos_Algolia_Integration {
    private $client;
    private $index;

    public function __construct() {
        $settings = get_option('lexhoy_despachos_settings');
        if (empty($settings['algolia_app_id']) || empty($settings['algolia_api_key'])) {
            return;
        }

        require_once LEXHOY_DESPACHOS_PLUGIN_DIR . 'vendor/autoload.php';

        try {
            $this->client = \Algolia\AlgoliaSearch\SearchClient::create(
                $settings['algolia_app_id'],
                $settings['algolia_api_key']
            );
            $this->index = $this->client->initIndex('lexhoy_despachos');
        } catch (\Exception $e) {
            error_log('Error al inicializar Algolia: ' . $e->getMessage());
        }
    }

    public function sync_data() {
        if (!$this->client || !$this->index) {
            return false;
        }

        $json_path = wp_upload_dir()['basedir'] . '/lexhoy-despachos/data/lexhoy_despachos_formatted.json';
        if (!file_exists($json_path)) {
            return false;
        }

        try {
            $json_data = json_decode(file_get_contents($json_path), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar el JSON');
            }

            // Preparar los datos para Algolia
            $records = array_map(function($despacho) {
                return array(
                    'objectID' => $despacho['id'] ?? uniqid(),
                    'nombre' => $despacho['nombre'] ?? '',
                    'direccion' => $despacho['direccion'] ?? '',
                    'ciudad' => $despacho['ciudad'] ?? '',
                    'provincia' => $despacho['provincia'] ?? '',
                    'telefono' => $despacho['telefono'] ?? '',
                    'email' => $despacho['email'] ?? '',
                    'web' => $despacho['web'] ?? '',
                    'especialidades' => $despacho['especialidades'] ?? array(),
                    'descripcion' => $despacho['descripcion'] ?? '',
                    'coordenadas' => array(
                        'lat' => $despacho['latitud'] ?? 0,
                        'lng' => $despacho['longitud'] ?? 0
                    )
                );
            }, $json_data);

            // Sincronizar con Algolia
            $this->index->saveObjects($records);
            return true;
        } catch (\Exception $e) {
            error_log('Error al sincronizar con Algolia: ' . $e->getMessage());
            return false;
        }
    }

    public function search($query, $filters = array()) {
        if (!$this->client || !$this->index) {
            return array();
        }

        try {
            $search_params = array(
                'query' => $query,
                'filters' => $this->build_filters($filters),
                'hitsPerPage' => 20
            );

            $results = $this->index->search($query, $search_params);
            return $results;
        } catch (\Exception $e) {
            error_log('Error en la búsqueda de Algolia: ' . $e->getMessage());
            return array();
        }
    }

    private function build_filters($filters) {
        $filter_strings = array();
        
        if (!empty($filters['ciudad'])) {
            $filter_strings[] = 'ciudad:' . $filters['ciudad'];
        }
        
        if (!empty($filters['provincia'])) {
            $filter_strings[] = 'provincia:' . $filters['provincia'];
        }
        
        if (!empty($filters['especialidades'])) {
            $filter_strings[] = 'especialidades:' . $filters['especialidades'];
        }

        return implode(' AND ', $filter_strings);
    }
} 