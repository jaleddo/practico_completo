-- Librostore. La entidad rol guarda a la persona.
-- administrador y usuario son atributos: un administrador también puede ser usuario.
-- La contraseña se guarda hasheada.

CREATE DATABASE IF NOT EXISTS biblioteca_virtual
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE biblioteca_virtual;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS sesiones;
DROP TABLE IF EXISTS prestamos;
DROP TABLE IF EXISTS articulos;
DROP TABLE IF EXISTS autores;
DROP TABLE IF EXISTS editoriales;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS rol;

CREATE TABLE rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    administrador TINYINT(1) NOT NULL DEFAULT 0,
    usuario TINYINT(1) NOT NULL DEFAULT 1,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    ci VARCHAR(20) NOT NULL UNIQUE,
    correo VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sesiones (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expira_en DATETIME NOT NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sesiones_rol FOREIGN KEY (id_rol) REFERENCES rol (id_rol) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE autores (
    id_autor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE editoriales (
    id_editorial INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL UNIQUE,
    pais VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articulos (
    id_articulo INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    id_categoria INT NOT NULL,
    id_autor INT NOT NULL,
    id_editorial INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    precio DECIMAL(10, 2) NULL,
    cantidad_disponible INT NOT NULL DEFAULT 1,
    estado VARCHAR(20) NOT NULL DEFAULT 'disponible',
    destacado TINYINT(1) NOT NULL DEFAULT 0,
    anio SMALLINT NULL,
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_articulos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria),
    CONSTRAINT fk_articulos_autor FOREIGN KEY (id_autor) REFERENCES autores (id_autor),
    CONSTRAINT fk_articulos_editorial FOREIGN KEY (id_editorial) REFERENCES editoriales (id_editorial),
    CONSTRAINT chk_articulos_estado CHECK (estado IN ('disponible', 'prestado')),
    CONSTRAINT chk_articulos_cantidad CHECK (cantidad_disponible >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un rol retira varios artículos. Un artículo se presta a varios roles si queda stock.
CREATE TABLE prestamos (
    id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    id_articulo INT NOT NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_devolucion DATE NOT NULL,
    renovaciones TINYINT NOT NULL DEFAULT 0,
    esperando_autorizacion TINYINT(1) NOT NULL DEFAULT 0,
    estado_prestamo VARCHAR(20) NOT NULL DEFAULT 'activo',
    CONSTRAINT fk_prestamos_rol FOREIGN KEY (id_rol) REFERENCES rol (id_rol) ON DELETE CASCADE,
    CONSTRAINT fk_prestamos_articulo FOREIGN KEY (id_articulo) REFERENCES articulos (id_articulo) ON DELETE CASCADE,
    CONSTRAINT chk_prestamos_estado CHECK (estado_prestamo IN ('activo', 'devuelto'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ana es administradora y también usuaria. Lucas es solo usuario.
-- Contraseñas hasheadas: admin123 y usuario123.
INSERT INTO rol (administrador, usuario, nombre, apellido, telefono, ci, correo, password_hash) VALUES
    (1, 1, 'Ana', 'Ribeiro', '099123001', '41112223', 'admin@librostore.edu', '$2y$10$ypzk2xNbUN89f50K3qMNQu0mHfgtdWxHkd2vEogvISpBInGtuUhPK'),
    (0, 1, 'Lucas', 'Pereyra', '098765002', '52223334', 'usuario@librostore.edu', '$2y$10$YuorPjBUYdIYD4RaGHfQu.ND9y8icLuurSXJxNkhPjbk2zZ1Bfyae');

INSERT INTO categorias (id_categoria, nombre, descripcion) VALUES
    (1, 'Libros', 'Obras para leer'),
    (2, 'Revistas', 'Publicaciones periódicas'),
    (3, 'Audiolibros', 'Obras para escuchar'),
    (4, 'Best sellers', 'Títulos más pedidos');

INSERT INTO autores (id_autor, nombre) VALUES
    (1, 'Marta Iribarne'),
    (2, 'Lucía Ferrari'),
    (3, 'Diego Alcaraz'),
    (4, 'Carmen Ortiz'),
    (5, 'Redacción Perspectivas'),
    (6, 'Nerea Solís'),
    (7, 'Instituto Cognos'),
    (8, 'A. M. Vargas'),
    (9, 'Daniel Reyes');

INSERT INTO editoriales (id_editorial, nombre, pais) VALUES
    (1, 'Senderos', 'Uruguay'),
    (2, 'Casa Vidrio', 'Argentina'),
    (3, 'Bruma Norte', 'Chile'),
    (4, 'Audio Abierta', 'España'),
    (5, 'Perspectivas Globales', 'México'),
    (6, 'Horizonte', 'Uruguay'),
    (7, 'Cognos', 'Argentina'),
    (8, 'Revista Futuro', 'España'),
    (9, 'Umbral', 'Chile');

INSERT INTO articulos
    (titulo, descripcion, id_categoria, id_autor, id_editorial, imagen, precio, cantidad_disponible, estado, destacado, anio)
VALUES
    ('Cien Caminos', 'Novela. Una viajera recorre caminos que se cruzan en un cañón. 312 páginas.', 1, 1, 1, '/front/dist/media/libro1.png', NULL, 4, 'disponible', 0, 2021),
    ('El Jardín de Vidrio', 'Novela. Un invernadero abandonado guarda los secretos de una familia. 248 páginas.', 1, 2, 2, '/front/dist/media/libro2.png', NULL, 3, 'disponible', 0, 2022),
    ('Norte Profundo', 'Ensayo. Una travesía por el hielo. 190 páginas. En este momento no quedan ejemplares.', 1, 3, 3, '/front/dist/media/libro3.png', NULL, 0, 'prestado', 0, 2023),
    ('La Melodía del Viento', 'Audiolibro narrado por Carmen Ortiz. Duración: 10 h 15 min.', 3, 4, 4, '/front/dist/media/audiolibro1.png', NULL, 2, 'disponible', 0, 2020),
    ('Perspectivas Globales N.º 142', 'Revista de mayo de 2024. Dossier: La arquitectura de lo efímero.', 2, 5, 5, '/front/dist/media/revista1.png', NULL, 5, 'disponible', 0, 2024),
    ('Eclipse de Silicio', 'La saga del horizonte, libro I. Una ciudad espera bajo un eclipse.', 1, 6, 6, '/front/dist/media/1.png', NULL, 3, 'disponible', 0, 2024),
    ('Cómo aprenden las ideas', 'Fundamentos de cognición, educación y pensamiento crítico.', 1, 7, 7, '/front/dist/media/2.png', NULL, 2, 'disponible', 0, 2023),
    ('Futuro N.º 128', 'Revista de ciencia, tecnología y sociedad. Mayo 2024.', 2, 8, 8, '/front/dist/media/3.png', NULL, 3, 'disponible', 0, 2024),
    ('La última verdad', 'Best seller. A veces la verdad no te hace libre: te cambia para siempre.', 4, 9, 9, '/front/dist/media/4.png', NULL, 2, 'disponible', 1, 2022);
