<?php
namespace App\Models;

use App\Utils\Consulta;

class CategoriaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $stmt = $this->db->query('SELECT id_categoria, nombre, descripcion FROM categorias ORDER BY nombre');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare('SELECT id_categoria, nombre, descripcion FROM categorias WHERE id_categoria = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function buscarPorNombre($nombre) {
        $stmt = $this->db->prepare('SELECT id_categoria, nombre FROM categorias WHERE nombre = :nombre');
        $stmt->execute(['nombre' => $nombre]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear($nombre, $descripcion = null) {
        Consulta::ejecutar($this->db, 'INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)', [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
