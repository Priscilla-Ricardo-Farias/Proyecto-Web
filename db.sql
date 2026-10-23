-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-07-2026 a las 02:32:08
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
--
-- Base de datos: `cinesystem`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peliculas`
--

--
-- Estructura de tabla para la tabla `directores`
--

CREATE TABLE `directores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `directores`
--

INSERT INTO `directores` (`id`, `nombre`) VALUES
(1, 'Lee Unkrich'),
(2, 'Denis Villeneuve'),
(3, 'Lana y Lilly Wachowski'),
(4, 'Francis Ford Coppola'),
(5, 'Roger Allers y Rob Minkoff');

CREATE TABLE `peliculas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `lanzamiento` int(11) DEFAULT NULL,
  `genero` varchar(50) DEFAULT NULL,
  `duracion` int(11) DEFAULT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `director_id` int(11) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `peliculas`
--

INSERT INTO `peliculas` (`id`, `nombre`, `lanzamiento`, `genero`, `duracion`, `descripcion`, `director_id`, `imagen`) VALUES
(2, 'Coco', 2017, 'Animación', 105, 'Un niño mexicano viaja a la Tierra de los Muertos para descubrir la verdadera historia de su familia.', 1, 'https://picsum.photos/seed/coco/400/300'),
(3, 'Duna', 2021, 'Ciencia ficción', 155, 'El heredero de una familia noble debe proteger el recurso más valioso de la galaxia en un planeta hostil.', 2, 'https://picsum.photos/seed/duna/400/300'),
(5, 'Matrix', 1999, 'Ciencia Ficción ', 136, 'Un programador descubre que el mundo que conoce es una simulación creada por máquinas y decide unirse a la resistencia.', 3, 'https://picsum.photos/seed/matrix/400/300'),
(11, 'El Padrino', 1972, 'Drama', 175, 'El patriarca de una familia de la mafia neoyorquina cede el control de su imperio criminal a su reacio hijo.', 4, 'https://picsum.photos/seed/padrino/400/300'),
(12, 'El Rey Leon', 1994, 'Animación ', 88, 'Un joven león debe reclamar su lugar como rey tras la muerte de su padre a manos de su tío.', 5, 'https://picsum.photos/seed/elreyleon/400/300');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `peliculas`
--
ALTER TABLE `peliculas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `director_id` (`director_id`);

ALTER TABLE `peliculas`
  ADD CONSTRAINT `peliculas_ibfk_1` FOREIGN KEY (`director_id`) REFERENCES `directores` (`id`) ON DELETE SET NULL;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `peliculas`
--
ALTER TABLE `peliculas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Un usuario y un admin de prueba
INSERT INTO usuarios (nombre, email, password) VALUES ('Juan Pérez', 'juan@test.com', '1234');
INSERT INTO admins (nombre, email, password) VALUES ('Admin', 'admin@test.com', 'admin123');

-- --------------------------------------------------------

--
-- Vista `vista_cuentas`
-- Junta `usuarios` y `admins` en una sola tabla, agregando una
-- columna `rol` para saber de qué tabla viene cada fila.
--
CREATE OR REPLACE VIEW `vista_cuentas` AS
    SELECT id, nombre, email, 'usuario' AS rol FROM usuarios
    UNION ALL
    SELECT id, nombre, email, 'admin' AS rol FROM admins;

-- Ejemplo de uso:
-- SELECT * FROM vista_cuentas ORDER BY nombre;
-- SELECT * FROM vista_cuentas WHERE rol = 'admin';
-- SELECT * FROM vista_cuentas WHERE nombre LIKE '%juan%' OR email LIKE '%juan%';
