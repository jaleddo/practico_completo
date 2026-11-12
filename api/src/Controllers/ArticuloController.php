<?php
namespace App\Controllers;

use App\Models\ArticuloModel;
use App\Models\AutorModel;
use App\Models\CategoriaModel;
use App\Models\EditorialModel;
use App\Utils\Auth;
use App\Utils\Request;
use App\Utils\Respuesta;

class ArticuloController {
    private $db;
    private $articulos;
    private $categorias;
    private $autores;
    private $editoriales;

    public function __construct($db) {
        $this->db = $db;
        $this->articulos = new ArticuloModel($db);
        $this->categorias = new CategoriaModel($db);
        $this->autores = new AutorModel($db);
        $this->editoriales = new EditorialModel($db);
    }

    public function listar() {
        $persona = Auth::exigirSesion($this->db);
        Respuesta::ok($this->presentarLista($this->articulos->listarPublico($persona['id_rol'])));
    }

    public function listarGestion() {
        Auth::exigirAdmin($this->db);
        Respuesta::ok($this->presentarLista($this->articulos->listarTodos()));
    }

    public function ver($id) {
        $articulo = $this->articulos->buscarPorId((int) $id);
        if (!$articulo) {
            Respuesta::error('Artículo no encontrado', 404);
            return;
        }

        Respuesta::ok($this->presentar($articulo));
    }

    public function crear() {
        Auth::exigirAdmin($this->db);
        $resultado = $this->datosDesdeCuerpo();
        if (isset($resultado['error'])) {
            Respuesta::error($resultado['error'], 400);
            return;
        }

        try {
            $id = $this->articulos->crear($resultado['datos']);
        } catch (\PDOException $e) {
            error_log('No se pudo crear el artículo: ' . $e->getMessage());
            Respuesta::error('No se pudo guardar el artículo', 400);
            return;
        }

        Respuesta::ok($this->presentar($this->articulos->buscarPorId($id)), 'Artículo creado', 201);
    }

    public function actualizar($id) {
        Auth::exigirAdmin($this->db);
        $id = (int) $id;
        if (!$this->articulos->buscarPorId($id)) {
            Respuesta::error('Artículo no encontrado', 404);
            return;
        }

        $resultado = $this->datosDesdeCuerpo();
        if (isset($resultado['error'])) {
            Respuesta::error($resultado['error'], 400);
            return;
        }

        try {
            $this->articulos->actualizar($id, $resultado['datos']);
        } catch (\PDOException $e) {
            error_log('No se pudo modificar el artículo: ' . $e->getMessage());
            Respuesta::error('No se pudo guardar el artículo', 400);
            return;
        }

        Respuesta::ok($this->presentar($this->articulos->buscarPorId($id)), 'Artículo actualizado');
    }

    public function eliminar($id) {
        Auth::exigirAdmin($this->db);
        $borrado = $this->articulos->eliminar((int) $id);
        if (!$borrado) {
            Respuesta::error('Artículo no encontrado', 404);
            return;
        }
        Respuesta::ok(null, 'Artículo eliminado');
    }

    public function listarImagenes() {
        Auth::exigirAdmin($this->db);
        $raiz = dirname(__DIR__, 3) . '/front/dist/media';
        $excluir = ['imagen_portada.png', 'sobresonotros.png'];
        $encontradas = [];
        $this->recolectarImagenes($raiz, '/front/dist/media', $excluir, $encontradas);
        sort($encontradas);
        Respuesta::ok($encontradas);
    }

    public function subirImagen() {
        Auth::exigirAdmin($this->db);

        if (!isset($_FILES['imagen']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
            Respuesta::error('Elegí un archivo de imagen', 400);
            return;
        }

        $archivo = $_FILES['imagen'];
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            Respuesta::error('No se pudo leer la imagen', 400);
            return;
        }
        if ($archivo['size'] > 5 * 1024 * 1024) {
            Respuesta::error('La imagen supera los 5 MB', 400);
            return;
        }

        $mapa = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!isset($mapa[$mime])) {
            Respuesta::error('El archivo tiene que ser JPG, PNG, WEBP o GIF', 400);
            return;
        }

        $directorio = dirname(__DIR__, 3) . '/front/dist/media/uploads';
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
            Respuesta::error('No se pudo preparar la carpeta de imágenes', 500);
            return;
        }

        $nombre = 'articulo_' . bin2hex(random_bytes(8)) . '.' . $mapa[$mime];
        $destino = $directorio . '/' . $nombre;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            Respuesta::error('No se pudo guardar la imagen', 500);
            return;
        }

        Respuesta::ok(['imagen' => '/front/dist/media/uploads/' . $nombre], 'Imagen guardada', 201);
    }

    private function datosDesdeCuerpo() {
        $body = Request::json();
        $titulo = trim($body['titulo'] ?? '');
        $descripcion = trim($body['descripcion'] ?? '');

        if ($titulo === '' || $this->largo($titulo) > 150) {
            return ['error' => 'El título es obligatorio y admite hasta 150 caracteres'];
        }
        if ($descripcion === '' || $this->largo($descripcion) > 4000) {
            return ['error' => 'La descripción es obligatoria y admite hasta 4000 caracteres'];
        }

        $categoria = $this->resolverCategoria($body);
        if (isset($categoria['error'])) {
            return $categoria;
        }
        $autor = $this->resolverAutor($body);
        if (isset($autor['error'])) {
            return $autor;
        }
        $editorial = $this->resolverEditorial($body);
        if (isset($editorial['error'])) {
            return $editorial;
        }

        $imagen = trim($body['imagen'] ?? '');
        if ($imagen !== '' && !$this->imagenValida($imagen)) {
            return ['error' => 'Elegí una imagen de la biblioteca'];
        }

        $estados = ['disponible', 'prestado'];
        $estado = $body['estado'] ?? 'disponible';
        if (!in_array($estado, $estados, true)) {
            return ['error' => 'El estado tiene que ser disponible o prestado'];
        }

        $cantidad = $body['cantidad_disponible'] ?? 1;
        if ($cantidad === '' || !is_numeric($cantidad) || (int) $cantidad < 0) {
            return ['error' => 'La cantidad disponible tiene que ser un número mayor o igual a cero'];
        }
        $cantidad = (int) $cantidad;
        if ($cantidad === 0) {
            $estado = 'prestado';
        } elseif ($estado === 'prestado' && $cantidad > 0) {
            $estado = 'disponible';
        }

        $precio = $body['precio'] ?? null;
        if ($precio === '' || $precio === null) {
            $precio = null;
        } elseif (!is_numeric($precio) || (float) $precio < 0) {
            return ['error' => 'El precio tiene que ser un número mayor o igual a cero'];
        } else {
            $precio = number_format((float) $precio, 2, '.', '');
        }

        $anio = $body['anio'] ?? null;
        if ($anio === '' || $anio === null) {
            $anio = null;
        } else {
            $anio = (int) $anio;
            if ($anio < 1000 || $anio > 2100) {
                return ['error' => 'El año no es válido'];
            }
        }

        return ['datos' => [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'id_categoria' => $categoria['id'],
            'id_autor' => $autor['id'],
            'id_editorial' => $editorial['id'],
            'imagen' => $imagen,
            'precio' => $precio,
            'cantidad_disponible' => $cantidad,
            'estado' => $estado,
            'destacado' => !empty($body['destacado']) ? 1 : 0,
            'anio' => $anio,
        ]];
    }

    private function resolverCategoria($body) {
        $nuevo = trim($body['nueva_categoria'] ?? '');
        if ($nuevo !== '') {
            if ($this->largo($nuevo) < 2 || $this->largo($nuevo) > 60) {
                return ['error' => 'La categoría nueva debe tener entre 2 y 60 caracteres'];
            }
            $existe = $this->categorias->buscarPorNombre($nuevo);
            $id = $existe ? (int) $existe['id_categoria'] : $this->categorias->crear($nuevo);
            return ['id' => $id];
        }

        $id = (int) ($body['id_categoria'] ?? 0);
        if ($id <= 0 || !$this->categorias->buscarPorId($id)) {
            return ['error' => 'Elegí una categoría'];
        }
        return ['id' => $id];
    }

    private function resolverAutor($body) {
        $nuevo = trim($body['nuevo_autor'] ?? '');
        if ($nuevo !== '') {
            if ($this->largo($nuevo) < 2 || $this->largo($nuevo) > 120) {
                return ['error' => 'El autor nuevo debe tener entre 2 y 120 caracteres'];
            }
            $existe = $this->autores->buscarPorNombre($nuevo);
            $id = $existe ? (int) $existe['id_autor'] : $this->autores->crear($nuevo);
            return ['id' => $id];
        }

        $id = (int) ($body['id_autor'] ?? 0);
        if ($id <= 0 || !$this->autores->buscarPorId($id)) {
            return ['error' => 'Elegí un autor'];
        }
        return ['id' => $id];
    }

    private function resolverEditorial($body) {
        $nuevo = trim($body['nueva_editorial'] ?? '');
        if ($nuevo !== '') {
            if ($this->largo($nuevo) < 2 || $this->largo($nuevo) > 120) {
                return ['error' => 'La editorial nueva debe tener entre 2 y 120 caracteres'];
            }
            $existe = $this->editoriales->buscarPorNombre($nuevo);
            $id = $existe ? (int) $existe['id_editorial'] : $this->editoriales->crear($nuevo, null);
            return ['id' => $id];
        }

        $id = (int) ($body['id_editorial'] ?? 0);
        if ($id <= 0 || !$this->editoriales->buscarPorId($id)) {
            return ['error' => 'Elegí una editorial'];
        }
        return ['id' => $id];
    }

    private function imagenValida($imagen) {
        if (!is_string($imagen) || strpos($imagen, '..') !== false) {
            return false;
        }
        return (bool) preg_match('#^/front/dist/media/[A-Za-z0-9._/ -]+$#', $imagen);
    }

    private function presentarLista($filas) {
        $salida = [];
        foreach ($filas as $fila) {
            $salida[] = $this->presentar($fila);
        }
        return $salida;
    }

    private function presentar($fila) {
        return [
            'id_articulo' => (int) $fila['id_articulo'],
            'titulo' => $fila['titulo'],
            'descripcion' => $fila['descripcion'],
            'imagen' => $fila['imagen'],
            'precio' => $fila['precio'] !== null ? (float) $fila['precio'] : null,
            'cantidad_disponible' => (int) $fila['cantidad_disponible'],
            'estado' => $fila['estado'],
            'en_biblioteca' => (int) ($fila['en_biblioteca'] ?? 0),
            'esperando_autorizacion' => (int) ($fila['esperando_autorizacion'] ?? 0),
            'destacado' => (int) $fila['destacado'],
            'anio' => $fila['anio'] !== null ? (int) $fila['anio'] : null,
            'fecha_alta' => $fila['fecha_alta'],
            'fecha_modificacion' => $fila['fecha_modificacion'],
            'id_categoria' => (int) $fila['id_categoria'],
            'categoria' => $fila['categoria'],
            'id_autor' => (int) $fila['id_autor'],
            'autor' => $fila['autor'],
            'id_editorial' => (int) $fila['id_editorial'],
            'editorial' => $fila['editorial'],
            'pais_editorial' => $fila['pais_editorial'],
        ];
    }

    private function recolectarImagenes($directorio, $prefijoWeb, $excluir, &$salida) {
        if (!is_dir($directorio)) {
            return;
        }
        $items = scandir($directorio);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $ruta = $directorio . DIRECTORY_SEPARATOR . $item;
            if (is_dir($ruta)) {
                $this->recolectarImagenes($ruta, $prefijoWeb . '/' . $item, $excluir, $salida);
                continue;
            }
            if (in_array($item, $excluir, true)) {
                continue;
            }
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
                continue;
            }
            $salida[] = $prefijoWeb . '/' . $item;
        }
    }

    private function largo($texto) {
        return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
    }
}
