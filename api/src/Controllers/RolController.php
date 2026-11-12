<?php
namespace App\Controllers;

use App\Models\RolModel;
use App\Utils\Auth;
use App\Utils\Request;
use App\Utils\Respuesta;

class RolController {
    private $db;
    private $roles;

    public function __construct($db) {
        $this->db = $db;
        $this->roles = new RolModel($db);
    }

    public function listar() {
        Auth::exigirAdmin($this->db);
        $personas = $this->roles->listar();
        Respuesta::ok([
            'cantidad' => count($personas),
            'usuarios' => $personas,
        ]);
    }

    public function crear() {
        $admin = Auth::exigirAdmin($this->db);
        $body = Request::json();
        $nombre = trim($body['nombre'] ?? '');
        $apellido = trim($body['apellido'] ?? '');
        $telefono = trim($body['telefono'] ?? '');
        $ci = trim($body['ci'] ?? '');
        $correo = strtolower(trim($body['correo'] ?? ''));
        $password = $body['password'] ?? '';

        if ($nombre === '' || $apellido === '' || $telefono === '' || $ci === '' || $correo === '') {
            Respuesta::error('Completá nombre, apellido, teléfono, cédula y correo', 400);
            return;
        }
        if ($password === '') {
            $password = bin2hex(random_bytes(8));
        }
        if (strlen($password) < 6) {
            Respuesta::error('La contraseña necesita al menos 6 caracteres', 400);
            return;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Respuesta::error('El correo no es válido', 400);
            return;
        }
        if ($this->roles->existeCorreo($correo)) {
            Respuesta::error('Ese correo ya está registrado', 400);
            return;
        }
        if ($this->roles->existeCi($ci)) {
            Respuesta::error('Esa cédula ya está registrada', 400);
            return;
        }

        $id = $this->roles->crear([
            'administrador' => !empty($body['administrador']) ? 1 : 0,
            'usuario' => 1,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'ci' => $ci,
            'correo' => $correo,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        unset($admin);
        Respuesta::ok(['id_rol' => $id], !empty($body['administrador']) ? 'Administrador agregado' : 'Usuario agregado', 201);
    }

    public function eliminarPorCi() {
        $admin = Auth::exigirAdmin($this->db);
        $body = Request::json();
        $ci = trim($body['ci'] ?? '');
        if ($ci === '') {
            Respuesta::error('Ingresá la cédula', 400);
            return;
        }
        $persona = $this->roles->buscarPorCi($ci);
        if (!$persona) {
            Respuesta::error('No encontramos esa cédula', 404);
            return;
        }
        if (!empty($body['solo_administrador']) && (int) $persona['administrador'] !== 1) {
            Respuesta::error('Esa cédula no es de un administrador', 400);
            return;
        }
        if ((int) $persona['id_rol'] === (int) $admin['id_rol']) {
            Respuesta::error('No podés eliminar tu propia cuenta', 400);
            return;
        }
        if (!$this->roles->eliminar((int) $persona['id_rol'])) {
            Respuesta::error('No se pudo eliminar', 400);
            return;
        }
        Respuesta::ok(null, 'Cuenta eliminada');
    }

    public function eliminar($id) {
        $admin = Auth::exigirAdmin($this->db);
        $idRol = (int) $id;
        if ($idRol === (int) $admin['id_rol']) {
            Respuesta::error('No podés eliminar tu propia cuenta', 400);
            return;
        }
        if (!$this->roles->eliminar($idRol)) {
            Respuesta::error('No encontramos ese usuario', 404);
            return;
        }
        Respuesta::ok(null, 'Usuario eliminado');
    }
}
