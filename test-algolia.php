<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = \Algolia\AlgoliaSearch\Client::create(
        'GA06AGLT12',
        'dcec9a6a746edae820a86f53e57e60e4'
    );
    echo "Cliente de Algolia creado correctamente\n";
    
    $index = $client->initIndex('lexhoy_despachos_formatted');
    echo "Índice inicializado correctamente\n";
    
    $results = $index->search('');
    echo "Búsqueda realizada correctamente. Encontrados " . count($results['hits']) . " resultados\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 