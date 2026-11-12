<?php
namespace App\Models;

use App\Utils\Consulta;

class EditorialModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $stmt = $this->db->query('SELECT id_editorial, nombre, pais FROM editoriales ORDER BY nombre');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare('SELECT id_editorial, nombre, pais FROM editoriales WHERE id_editorial = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function buscarPorNombre($nombre) {
        $stmt = $this->db->prepare('SELECT id_editorial, nombre, pais FROM editoriales WHERE nombre = :nombre');
        $stmt->execute(['nombre' => $nombre]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear($nombre, $pais = null) {
        Consulta::ejecutar($this->db, 'INSERT INTO editoriales (nombre, pais) VALUES (:nombre, :pais)', [
            'nombre' => $nombre,
            'pais' => $pais,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
