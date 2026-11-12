<?php
namespace App\Models;

use App\Utils\Consulta;

class ArticuloModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function contar() {
        try {
            $stmt = $this->db->query('SELECT COUNT(*) AS total FROM articulos');
            $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int) $fila['total'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listarPublico($idRol = null) {
        $sql = $this->selectBase($idRol) . ' ORDER BY a.titulo ASC';
        if ($idRol === null) {
            return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_rol' => $idRol, 'id_rol_espera' => $idRol]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listarTodos() {
        $sql = $this->selectBase(null) . ' ORDER BY a.fecha_modificacion DESC, a.titulo ASC';
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $sql = $this->selectBase(null) . ' WHERE a.id_articulo = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public function crear($datos) {
        $sql = 'INSERT INTO articulos
                    (titulo, descripcion, id_categoria, id_autor, id_editorial, imagen, precio, cantidad_disponible, estado, destacado, anio)
                VALUES
                    (:titulo, :descripcion, :id_categoria, :id_autor, :id_editorial, :imagen, :precio, :cantidad_disponible, :estado, :destacado, :anio)';
        Consulta::ejecutar($this->db, $sql, $datos);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar($id, $datos) {
        $datos['id'] = $id;
        $sql = 'UPDATE articulos SET
                    titulo = :titulo,
                    descripcion = :descripcion,
                    id_categoria = :id_categoria,
                    id_autor = :id_autor,
                    id_editorial = :id_editorial,
                    imagen = :imagen,
                    precio = :precio,
                    cantidad_disponible = :cantidad_disponible,
                    estado = :estado,
                    destacado = :destacado,
                    anio = :anio
                WHERE id_articulo = :id';
        Consulta::ejecutar($this->db, $sql, $datos);
        return true;
    }

    public function eliminar($id) {
        $stmt = $this->db->prepare('DELETE FROM articulos WHERE id_articulo = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function selectBase($idRol) {
        $marca = '0 AS en_biblioteca, 0 AS esperando_autorizacion';
        $join = '';
        if ($idRol !== null) {
            $marca = 'CASE WHEN EXISTS (
                        SELECT 1 FROM prestamos pb
                        WHERE pb.id_articulo = a.id_articulo AND pb.id_rol = :id_rol AND pb.estado_prestamo = \'activo\'
                    ) THEN 1 ELSE 0 END AS en_biblioteca,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM prestamos pe
                        WHERE pe.id_articulo = a.id_articulo AND pe.id_rol = :id_rol_espera AND pe.esperando_autorizacion = 1
                    ) THEN 1 ELSE 0 END AS esperando_autorizacion';
        }
        return 'SELECT a.id_articulo, a.titulo, a.descripcion, a.imagen, a.precio, a.cantidad_disponible, a.estado,
                       a.destacado, a.anio, a.fecha_alta, a.fecha_modificacion,
                       a.id_categoria, a.id_autor, a.id_editorial,
                       c.nombre AS categoria,
                       au.nombre AS autor,
                       e.nombre AS editorial,
                       e.pais AS pais_editorial,
                       ' . $marca . '
                FROM articulos a
                INNER JOIN categorias c ON c.id_categoria = a.id_categoria
                INNER JOIN autores au ON au.id_autor = a.id_autor
                INNER JOIN editoriales e ON e.id_editorial = a.id_editorial' . $join;
    }
}
