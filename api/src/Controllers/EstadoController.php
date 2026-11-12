<?php
namespace App\Controllers;

use App\Models\ArticuloModel;

class EstadoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ver() {
        $respuesta = [
            'status' => 'ok',
            'message' => 'La API de Librostore responde',
            'base_datos' => 'conectada',
            'tabla_articulos' => 'no disponible',
            'cantidad_articulos' => 0,
        ];

        $articulos = new ArticuloModel($this->db);
        $cantidad = $articulos->contar();

        if ($cantidad !== false) {
            $respuesta['tabla_articulos'] = 'ok';
            $respuesta['cantidad_articulos'] = $cantidad;
        } else {
            $respuesta['tabla_articulos'] = 'Falta crear las tablas de la biblioteca';
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }
}
