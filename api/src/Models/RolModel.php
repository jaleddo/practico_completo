<?php
namespace App\Models;

use App\Utils\Consulta;

class RolModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function buscarPorCorreo($correo) {
        $stmt = $this->db->prepare('SELECT id_rol, administrador, usuario, nombre, apellido, telefono, ci, correo, password_hash FROM rol WHERE correo = :correo LIMIT 1');
        $stmt->execute(['correo' => $correo]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function buscarPorCorreoYCi($correo, $ci) {
        $stmt = $this->db->prepare('SELECT id_rol, correo, ci FROM rol WHERE correo = :correo AND ci = :ci LIMIT 1');
        $stmt->execute(['correo' => $correo, 'ci' => $ci]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function existeCorreo($correo) {
        $stmt = $this->db->prepare('SELECT id_rol FROM rol WHERE correo = :correo LIMIT 1');
        $stmt->execute(['correo' => $correo]);
        return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function buscarPorCi($ci) {
        $stmt = $this->db->prepare('SELECT id_rol, administrador, nombre, apellido FROM rol WHERE ci = :ci LIMIT 1');
        $stmt->execute(['ci' => $ci]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function existeCi($ci) {
        $stmt = $this->db->prepare('SELECT id_rol FROM rol WHERE ci = :ci LIMIT 1');
        $stmt->execute(['ci' => $ci]);
        return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function crear($datos) {
        Consulta::ejecutar(
            $this->db,
            'INSERT INTO rol (administrador, usuario, nombre, apellido, telefono, ci, correo, password_hash)
             VALUES (:administrador, :usuario, :nombre, :apellido, :telefono, :ci, :correo, :password_hash)',
            $datos
        );
        return (int) $this->db->lastInsertId();
    }

    public function listar() {
        $personas = $this->db->query('SELECT id_rol, administrador, usuario, nombre, apellido, telefono, ci, correo FROM rol ORDER BY apellido, nombre')->fetchAll(\PDO::FETCH_ASSOC);
        $prestamos = $this->db->query('SELECT p.id_rol, a.titulo, p.fecha_prestamo, p.fecha_devolucion
            FROM prestamos p
            INNER JOIN articulos a ON a.id_articulo = p.id_articulo
            WHERE p.estado_prestamo = \'activo\'
            ORDER BY a.titulo')->fetchAll(\PDO::FETCH_ASSOC);
        $porPersona = [];
        foreach ($prestamos as $prestamo) {
            $porPersona[$prestamo['id_rol']][] = [
                'titulo' => $prestamo['titulo'],
                'fecha_prestamo' => $prestamo['fecha_prestamo'],
                'fecha_devolucion' => $prestamo['fecha_devolucion'],
            ];
        }
        foreach ($personas as &$persona) {
            $persona['id_rol'] = (int) $persona['id_rol'];
            $persona['administrador'] = (int) $persona['administrador'];
            $persona['usuario'] = (int) $persona['usuario'];
            $persona['libros'] = $porPersona[$persona['id_rol']] ?? [];
        }
        return $personas;
    }

    public function eliminar($idRol) {
        $this->db->beginTransaction();
        try {
            $activos = $this->db->prepare('SELECT id_articulo FROM prestamos WHERE id_rol = :id AND estado_prestamo = \'activo\' FOR UPDATE');
            $activos->execute(['id' => $idRol]);
            $filas = $activos->fetchAll(\PDO::FETCH_ASSOC);
            $sumar = $this->db->prepare('UPDATE articulos SET cantidad_disponible = cantidad_disponible + 1, estado = \'disponible\' WHERE id_articulo = :id');
            foreach ($filas as $fila) {
                $sumar->execute(['id' => $fila['id_articulo']]);
            }
            $borrar = $this->db->prepare('DELETE FROM rol WHERE id_rol = :id');
            $borrar->execute(['id' => $idRol]);
            $this->db->commit();
            return $borrar->rowCount() > 0;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function actualizarClave($idRol, $passwordHash) {
        $stmt = $this->db->prepare('UPDATE rol SET password_hash = :password_hash WHERE id_rol = :id_rol');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id_rol' => $idRol,
        ]);
    }
}
