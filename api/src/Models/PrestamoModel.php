<?php
namespace App\Models;

class PrestamoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listarActivos($idRol) {
        $sql = 'SELECT p.id_prestamo, p.fecha_prestamo, p.fecha_devolucion, p.estado_prestamo,
                       p.renovaciones, p.esperando_autorizacion,
                       a.id_articulo, a.titulo, a.descripcion, a.imagen, a.estado,
                       c.nombre AS categoria, au.nombre AS autor
                FROM prestamos p
                INNER JOIN articulos a ON a.id_articulo = p.id_articulo
                INNER JOIN categorias c ON c.id_categoria = a.id_categoria
                INNER JOIN autores au ON au.id_autor = a.id_autor
                WHERE p.id_rol = :id_rol AND (p.estado_prestamo = \'activo\' OR p.esperando_autorizacion = 1)
                ORDER BY p.fecha_prestamo DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_rol' => $idRol]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function prestar($idRol, $idArticulo) {
        $this->db->beginTransaction();
        try {
            $stock = $this->db->prepare('SELECT cantidad_disponible FROM articulos WHERE id_articulo = :id FOR UPDATE');
            $stock->execute(['id' => $idArticulo]);
            $fila = $stock->fetch(\PDO::FETCH_ASSOC);
            if (!$fila) {
                $this->db->rollBack();
                return 'no_encontrado';
            }
            if ((int) $fila['cantidad_disponible'] < 1) {
                $this->db->rollBack();
                return 'sin_stock';
            }

            $ya = $this->db->prepare('SELECT esperando_autorizacion FROM prestamos WHERE id_rol = :id_rol AND id_articulo = :id_articulo AND (estado_prestamo = \'activo\' OR esperando_autorizacion = 1) LIMIT 1');
            $ya->execute(['id_rol' => $idRol, 'id_articulo' => $idArticulo]);
            $filaYa = $ya->fetch(\PDO::FETCH_ASSOC);
            if ($filaYa) {
                $this->db->rollBack();
                return (int) $filaYa['esperando_autorizacion'] === 1 ? 'esperando' : 'ya_prestado';
            }

            $alta = $this->db->prepare('INSERT INTO prestamos (id_rol, id_articulo, fecha_prestamo, fecha_devolucion, estado_prestamo)
                VALUES (:id_rol, :id_articulo, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), \'activo\')');
            $alta->execute(['id_rol' => $idRol, 'id_articulo' => $idArticulo]);

            $nueva = (int) $fila['cantidad_disponible'] - 1;
            $estado = $nueva > 0 ? 'disponible' : 'prestado';
            $baja = $this->db->prepare('UPDATE articulos SET cantidad_disponible = :cantidad, estado = :estado WHERE id_articulo = :id');
            $baja->execute(['cantidad' => $nueva, 'estado' => $estado, 'id' => $idArticulo]);

            $this->db->commit();
            return 'ok';
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function renovar($idPrestamo, $idRol) {
        $this->db->beginTransaction();
        try {
            $buscar = $this->db->prepare('SELECT id_articulo, renovaciones, esperando_autorizacion, estado_prestamo FROM prestamos WHERE id_prestamo = :id AND id_rol = :id_rol LIMIT 1 FOR UPDATE');
            $buscar->execute(['id' => $idPrestamo, 'id_rol' => $idRol]);
            $fila = $buscar->fetch(\PDO::FETCH_ASSOC);
            if (!$fila || ($fila['estado_prestamo'] !== 'activo' && (int) $fila['esperando_autorizacion'] !== 1)) {
                $this->db->rollBack();
                return 'no_encontrado';
            }
            if ((int) $fila['esperando_autorizacion'] === 1) {
                $this->db->commit();
                return 'esperando';
            }
            if ((int) $fila['renovaciones'] >= 3) {
                if ($fila['estado_prestamo'] === 'activo') {
                    $sumar = $this->db->prepare('UPDATE articulos SET cantidad_disponible = cantidad_disponible + 1, estado = \'disponible\' WHERE id_articulo = :id');
                    $sumar->execute(['id' => $fila['id_articulo']]);
                }
                $espera = $this->db->prepare('UPDATE prestamos SET estado_prestamo = \'devuelto\', esperando_autorizacion = 1 WHERE id_prestamo = :id');
                $espera->execute(['id' => $idPrestamo]);
                $this->db->commit();
                return 'esperando';
            }

            $stmt = $this->db->prepare('UPDATE prestamos
                SET fecha_devolucion = DATE_ADD(fecha_devolucion, INTERVAL 7 DAY),
                    renovaciones = renovaciones + 1
                WHERE id_prestamo = :id AND estado_prestamo = \'activo\'');
            $stmt->execute(['id' => $idPrestamo]);
            $this->db->commit();
            return $stmt->rowCount() > 0 ? 'ok' : 'no_encontrado';
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function listarPendientes() {
        $sql = 'SELECT p.id_prestamo, p.fecha_prestamo, p.fecha_devolucion, p.renovaciones,
                       r.nombre, r.apellido, r.correo,
                       a.titulo
                FROM prestamos p
                INNER JOIN rol r ON r.id_rol = p.id_rol
                INNER JOIN articulos a ON a.id_articulo = p.id_articulo
                WHERE p.esperando_autorizacion = 1
                ORDER BY p.fecha_devolucion ASC';
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function autorizar($idPrestamo) {
        $this->db->beginTransaction();
        try {
            $buscar = $this->db->prepare('SELECT p.id_articulo, a.cantidad_disponible
                FROM prestamos p
                INNER JOIN articulos a ON a.id_articulo = p.id_articulo
                WHERE p.id_prestamo = :id AND p.esperando_autorizacion = 1
                LIMIT 1 FOR UPDATE');
            $buscar->execute(['id' => $idPrestamo]);
            $fila = $buscar->fetch(\PDO::FETCH_ASSOC);
            if (!$fila) {
                $this->db->rollBack();
                return 'no_encontrado';
            }
            if ((int) $fila['cantidad_disponible'] < 1) {
                $this->db->rollBack();
                return 'sin_stock';
            }
            $nueva = (int) $fila['cantidad_disponible'] - 1;
            $estado = $nueva > 0 ? 'disponible' : 'prestado';
            $baja = $this->db->prepare('UPDATE articulos SET cantidad_disponible = :cantidad, estado = :estado WHERE id_articulo = :id');
            $baja->execute(['cantidad' => $nueva, 'estado' => $estado, 'id' => $fila['id_articulo']]);
            $alta = $this->db->prepare('UPDATE prestamos
                SET estado_prestamo = \'activo\',
                    esperando_autorizacion = 0,
                    fecha_prestamo = CURDATE(),
                    fecha_devolucion = DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                WHERE id_prestamo = :id');
            $alta->execute(['id' => $idPrestamo]);
            $this->db->commit();
            return 'ok';
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function devolver($idPrestamo, $idRol) {
        $this->db->beginTransaction();
        try {
            $buscar = $this->db->prepare('SELECT id_articulo FROM prestamos WHERE id_prestamo = :id AND id_rol = :id_rol AND estado_prestamo = \'activo\' FOR UPDATE');
            $buscar->execute(['id' => $idPrestamo, 'id_rol' => $idRol]);
            $fila = $buscar->fetch(\PDO::FETCH_ASSOC);
            if (!$fila) {
                $this->db->rollBack();
                return false;
            }

            $cerrar = $this->db->prepare('UPDATE prestamos SET estado_prestamo = \'devuelto\' WHERE id_prestamo = :id');
            $cerrar->execute(['id' => $idPrestamo]);

            $sumar = $this->db->prepare('UPDATE articulos SET cantidad_disponible = cantidad_disponible + 1, estado = \'disponible\' WHERE id_articulo = :id');
            $sumar->execute(['id' => $fila['id_articulo']]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
