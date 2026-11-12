<?php
namespace App\Utils;

class Respuesta {
    public static function ok($data = null, $message = 'ok', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'status' => 'ok',
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function error($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
    }
}
