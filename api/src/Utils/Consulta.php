<?php
namespace App\Utils;

class Consulta {
    public static function ejecutar($db, $sql, $datos) {
        $stmt = $db->prepare($sql);
        foreach ($datos as $clave => $valor) {
            $tipo = $valor === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $clave, $valor, $tipo);
        }
        $stmt->execute();
        return $stmt;
    }
}
