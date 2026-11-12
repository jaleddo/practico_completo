<?php
namespace App\Controllers;

use App\Models\AutorModel;
use App\Utils\Respuesta;

class AutorController {
    private $autores;

    public function __construct($db) {
        $this->autores = new AutorModel($db);
    }

    public function listar() {
        Respuesta::ok($this->autores->listar());
    }
}
