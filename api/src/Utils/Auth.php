<?php
namespace App\Utils;

use App\Models\SesionModel;

class Auth {
    public static function usuarioActual($db) {
        $token = self::token();
        if ($token === null) {
            return null;
        }

        $sesiones = new SesionModel($db);
        $persona = $sesiones->buscarPorToken(hash('sha256', $token));
        if (!$persona) {
            return null;
        }

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

    public static function exigirSesion($db) {
        $persona = self::usuarioActual($db);
        if (!$persona) {
            Respuesta::error('Tenés que iniciar sesión', 401);
            exit;
        }
        return $persona;
    }

    public static function exigirAdmin($db) {
        $persona = self::exigirSesion($db);
        if ((int) $persona['administrador'] !== 1) {
            Respuesta::error('No tenés permiso para esta sección', 403);
            exit;
        }
        return $persona;
    }

    public static function token() {
        $header = self::headerAutorizacion();
        if (preg_match('/Bearer\s+(\S+)/i', $header, $coincidencia)) {
            return $coincidencia[1];
        }
        return null;
    }

    private static function headerAutorizacion() {
        $candidatos = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? '',
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
        ];

        $desdeEntorno = getenv('HTTP_AUTHORIZATION');
        if (is_string($desdeEntorno)) {
            $candidatos[] = $desdeEntorno;
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $clave => $valor) {
                if (strcasecmp($clave, 'Authorization') === 0) {
                    $candidatos[] = $valor;
                }
            }
        }

        foreach ($candidatos as $valor) {
            if (is_string($valor) && $valor !== '') {
                return $valor;
            }
        }
        return '';
    }
}
