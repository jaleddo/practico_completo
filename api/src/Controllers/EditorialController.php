<?php
namespace App\Controllers;

use App\Models\EditorialModel;
use App\Utils\Respuesta;

class EditorialController {
    private $editoriales;

    public function __construct($db) {
        $this->editoriales = new EditorialModel($db);
    }

    public function listar() {
        Respuesta::ok($this->editoriales->listar());
    }
}
