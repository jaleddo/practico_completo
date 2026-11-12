<?php
// api/routes.php
// Cuando pidan esta URL, se ejecuta este controlador y este método.
// El SQL queda en los modelos. El controlador decide y responde JSON.

$router->get('/estado', 'EstadoController@ver');

$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/registro', 'AuthController@registro');
$router->post('/auth/recuperar', 'AuthController@recuperar');
$router->post('/auth/logout', 'AuthController@logout');
$router->get('/auth/me', 'AuthController@me');

$router->get('/prestamos/mios', 'PrestamoController@mios');
$router->get('/prestamos/pendientes', 'PrestamoController@pendientes');
$router->post('/prestamos', 'PrestamoController@crear');
$router->put('/prestamos/:id/renovar', 'PrestamoController@renovar');
$router->put('/prestamos/:id/autorizar', 'PrestamoController@autorizar');
$router->put('/prestamos/:id/devolver', 'PrestamoController@devolver');

$router->get('/roles', 'RolController@listar');
$router->post('/roles', 'RolController@crear');
$router->post('/roles/baja', 'RolController@eliminarPorCi');
$router->delete('/roles/:id', 'RolController@eliminar');

$router->get('/categorias', 'CategoriaController@listar');
$router->get('/autores', 'AutorController@listar');
$router->get('/editoriales', 'EditorialController@listar');

// Rutas literales antes de /articulos/:id, porque "gestion" e "imagen" también coincidirían con un id.
$router->get('/imagenes', 'ArticuloController@listarImagenes');
$router->post('/articulos/imagen', 'ArticuloController@subirImagen');
$router->get('/articulos/gestion', 'ArticuloController@listarGestion');
$router->get('/articulos', 'ArticuloController@listar');
$router->get('/articulos/:id', 'ArticuloController@ver');
$router->post('/articulos', 'ArticuloController@crear');
$router->put('/articulos/:id', 'ArticuloController@actualizar');
$router->delete('/articulos/:id', 'ArticuloController@eliminar');
