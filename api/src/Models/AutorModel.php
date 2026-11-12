<?php
namespace App\Models;

class AutorModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $stmt = $this->db->query('SELECT id_autor, nombre FROM autores ORDER BY nombre');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare('SELECT id_autor, nombre FROM autores WHERE id_autor = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function buscarPorNombre($nombre) {
        $stmt = $this->db->prepare('SELECT id_autor, nombre FROM autores WHERE nombre = :nombre');
        $stmt->execute(['nombre' => $nombre]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear($nombre) {
        $stmt = $this->db->prepare('INSERT INTO autores (nombre) VALUES (:nombre)');
        $stmt->execute(['nombre' => $nombre]);
        return (int) $this->db->lastInsertId();
    }
}
