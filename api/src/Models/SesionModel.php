<?php
namespace App\Models;

class SesionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function crear($idRol, $tokenHash) {
        $sql = 'INSERT INTO sesiones (id_rol, token_hash, expira_en)
                VALUES (:id_rol, :token_hash, DATE_ADD(NOW(), INTERVAL 12 HOUR))';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_rol' => $idRol,
            'token_hash' => $tokenHash,
        ]);
    }

    public function buscarPorToken($tokenHash) {
        $sql = 'SELECT r.id_rol, r.administrador, r.usuario, r.nombre, r.apellido, r.telefono, r.ci, r.correo
                FROM sesiones s
                INNER JOIN rol r ON r.id_rol = s.id_rol
                WHERE s.token_hash = :token_hash
                  AND s.expira_en > NOW()
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token_hash' => $tokenHash]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function eliminarPorToken($tokenHash) {
        $stmt = $this->db->prepare('DELETE FROM sesiones WHERE token_hash = :token_hash');
        $stmt->execute(['token_hash' => $tokenHash]);
    }
}
