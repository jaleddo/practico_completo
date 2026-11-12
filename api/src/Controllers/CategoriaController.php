<?php
namespace App\Controllers;

use App\Models\CategoriaModel;
use App\Utils\Respuesta;

class CategoriaController {
    private $categorias;

    public function __construct($db) {
        $this->categorias = new CategoriaModel($db);
    }

    public function listar() {
        Respuesta::ok($this->categorias->listar());
    }
}
