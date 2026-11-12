<?php
namespace App\Controllers;

use App\Models\PrestamoModel;
use App\Utils\Auth;
use App\Utils\Request;
use App\Utils\Respuesta;

class PrestamoController {
    private $db;
    private $prestamos;

    public function __construct($db) {
        $this->db = $db;
        $this->prestamos = new PrestamoModel($db);
    }

    public function mios() {
        $persona = Auth::exigirSesion($this->db);
        Respuesta::ok($this->presentarLista($this->prestamos->listarActivos($persona['id_rol'])));
    }

    public function crear() {
        $persona = Auth::exigirSesion($this->db);
        $body = Request::json();
        $idArticulo = (int) ($body['id_articulo'] ?? 0);
        if ($idArticulo <= 0) {
            Respuesta::error('Elegí un artículo', 400);
            return;
        }

        try {
            $resultado = $this->prestamos->prestar($persona['id_rol'], $idArticulo);
        } catch (\Exception $e) {
            error_log('No se pudo prestar: ' . $e->getMessage());
            Respuesta::error('No se pudo registrar el préstamo', 400);
            return;
        }

        if ($resultado === 'sin_stock') {
            Respuesta::error('No hay ejemplares disponibles', 409);
            return;
        }
        if ($resultado === 'esperando') {
            Respuesta::error('esperando autorización', 409);
            return;
        }
        if ($resultado === 'ya_prestado') {
            Respuesta::ok(null, 'Ese título ya está en tu biblioteca');
            return;
        }
        if ($resultado !== 'ok') {
            Respuesta::error('Artículo no encontrado', 404);
            return;
        }
        Respuesta::ok(null, 'Reservado', 201);
    }

    public function pendientes() {
        Auth::exigirAdmin($this->db);
        $filas = $this->prestamos->listarPendientes();
        $salida = [];
        foreach ($filas as $fila) {
            $salida[] = [
                'id_prestamo' => (int) $fila['id_prestamo'],
                'fecha_prestamo' => $fila['fecha_prestamo'],
                'fecha_devolucion' => $fila['fecha_devolucion'],
                'renovaciones' => (int) $fila['renovaciones'],
                'nombre' => $fila['nombre'],
                'apellido' => $fila['apellido'],
                'correo' => $fila['correo'],
                'titulo' => $fila['titulo'],
            ];
        }
        Respuesta::ok($salida);
    }

    public function autorizar($id) {
        Auth::exigirAdmin($this->db);
        $resultado = $this->prestamos->autorizar((int) $id);
        if ($resultado === 'sin_stock') {
            Respuesta::error('No hay ejemplares para volver a prestar', 409);
            return;
        }
        if ($resultado !== 'ok') {
            Respuesta::error('Esa renovación ya no está pendiente', 404);
            return;
        }
        Respuesta::ok(null, 'Renovación autorizada');
    }

    public function renovar($id) {
        $persona = Auth::exigirSesion($this->db);
        $resultado = $this->prestamos->renovar((int) $id, $persona['id_rol']);
        if ($resultado === 'esperando') {
            Respuesta::ok(['esperando_autorizacion' => 1], 'esperando autorización');
            return;
        }
        if ($resultado !== 'ok') {
            Respuesta::error('No encontramos ese préstamo', 404);
            return;
        }
        Respuesta::ok(null, 'Préstamo renovado');
    }

    public function devolver($id) {
        $persona = Auth::exigirSesion($this->db);
        $ok = $this->prestamos->devolver((int) $id, $persona['id_rol']);
        if (!$ok) {
            Respuesta::error('No encontramos ese préstamo', 404);
            return;
        }
        Respuesta::ok(null, 'Devolución registrada');
    }

    private function presentarLista($filas) {
        $salida = [];
        foreach ($filas as $fila) {
            $salida[] = [
                'id_prestamo' => (int) $fila['id_prestamo'],
                'fecha_prestamo' => $fila['fecha_prestamo'],
                'fecha_devolucion' => $fila['fecha_devolucion'],
                'renovaciones' => (int) ($fila['renovaciones'] ?? 0),
                'esperando_autorizacion' => (int) ($fila['esperando_autorizacion'] ?? 0),
                'id_articulo' => (int) $fila['id_articulo'],
                'titulo' => $fila['titulo'],
                'descripcion' => $fila['descripcion'],
                'imagen' => $fila['imagen'],
                'categoria' => $fila['categoria'],
                'autor' => $fila['autor'],
            ];
        }
        return $salida;
    }
}
