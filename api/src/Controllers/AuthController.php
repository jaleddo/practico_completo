<?php
namespace App\Controllers;

use App\Models\RolModel;
use App\Models\SesionModel;
use App\Utils\Auth;
use App\Utils\Request;
use App\Utils\Respuesta;

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login() {
        $body = Request::json();
        $correo = strtolower(trim($body['email'] ?? $body['correo'] ?? ''));
        $password = $body['password'] ?? '';

        if ($correo === '' || $password === '') {
            Respuesta::error('Ingresá correo y contraseña', 400);
            return;
        }

        $roles = new RolModel($this->db);
        $persona = $roles->buscarPorCorreo($correo);
        $claveOk = $persona && password_verify($password, $persona['password_hash']);

        if (!$persona || !$claveOk) {
            Respuesta::error('Credenciales incorrectas', 401);
            return;
        }

        Respuesta::ok($this->abrirSesion($persona), 'Sesión iniciada');
    }

    public function registro() {
        $body = Request::json();
        $nombre = trim($body['nombre'] ?? '');
        $apellido = trim($body['apellido'] ?? '');
        $telefono = trim($body['telefono'] ?? '');
        $ci = trim($body['ci'] ?? '');
        $correo = strtolower(trim($body['correo'] ?? $body['email'] ?? ''));
        $password = $body['password'] ?? '';

        if ($nombre === '' || $apellido === '' || $telefono === '' || $ci === '' || $correo === '' || $password === '') {
            Respuesta::error('Completá nombre, apellido, teléfono, cédula, correo y contraseña', 400);
            return;
        }
        if (strlen($password) < 6) {
            Respuesta::error('La contraseña necesita al menos 6 caracteres', 400);
            return;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Respuesta::error('El correo no es válido', 400);
            return;
        }

        $roles = new RolModel($this->db);
        if ($roles->existeCorreo($correo)) {
            Respuesta::error('Ese correo ya está registrado', 400);
            return;
        }
        if ($roles->existeCi($ci)) {
            Respuesta::error('Esa cédula ya está registrada', 400);
            return;
        }

        $id = $roles->crear([
            'administrador' => 0,
            'usuario' => 1,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'ci' => $ci,
            'correo' => $correo,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $persona = $roles->buscarPorCorreo($correo);
        $persona['id_rol'] = $id;
        Respuesta::ok($this->abrirSesion($persona), 'Cuenta creada', 201);
    }

    public function recuperar() {
        $body = Request::json();
        $correo = strtolower(trim($body['correo'] ?? $body['email'] ?? ''));
        $ci = trim($body['ci'] ?? '');
        $password = $body['password'] ?? '';

        if ($correo === '' || $ci === '' || $password === '') {
            Respuesta::error('Completá correo, cédula y la nueva contraseña', 400);
            return;
        }
        if (strlen($password) < 6) {
            Respuesta::error('La contraseña necesita al menos 6 caracteres', 400);
            return;
        }

        $roles = new RolModel($this->db);
        $persona = $roles->buscarPorCorreoYCi($correo, $ci);
        if (!$persona) {
            Respuesta::error('No encontramos una cuenta con ese correo y esa cédula', 404);
            return;
        }

        $roles->actualizarClave((int) $persona['id_rol'], password_hash($password, PASSWORD_DEFAULT));
        Respuesta::ok(null, 'Contraseña actualizada');
    }

    public function logout() {
        $token = Auth::token();
        if ($token !== null) {
            $sesiones = new SesionModel($this->db);
            $sesiones->eliminarPorToken(hash('sha256', $token));
        }
        Respuesta::ok(null, 'Sesión cerrada');
    }

    public function me() {
        Respuesta::ok(Auth::exigirSesion($this->db));
    }

    private function abrirSesion($persona) {
        $token = bin2hex(random_bytes(32));
        $sesiones = new SesionModel($this->db);
        $sesiones->crear((int) $persona['id_rol'], hash('sha256', $token));
        return [
            'token' => $token,
            'usuario' => $this->publico($persona),
        ];
    }

    private function publico($persona) {
        return [
            'id_rol' => (int) $persona['id_rol'],
            'nombre' => $persona['nombre'],
            'apellido' => $persona['apellido'],
            'correo' => $persona['correo'],
            'telefono' => $persona['telefono'],
            'ci' => $persona['ci'],
            'administrador' => (int) $persona['administrador'],
            'usuario' => (int) $persona['usuario'],
        ];
    }
}
