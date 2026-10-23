<?php
// 1. Los errores de la base los manejamos nosotros, acá abajo
mysqli_report(MYSQLI_REPORT_OFF);

// Hora de Argentina: las funciones se comparan contra "ahora"
date_default_timezone_set('America/Argentina/Buenos_Aires');



// 2. Los cuatro datos, cada uno en una variable
$host      = 'localhost';
$usuario   = 'root';
$clave     = '';
$base      = 'cinesystem';

// 3. Abrir el canal: el resultado queda en $conexion
$conexion = mysqli_connect($host, $usuario, $clave, $base);

// 4. ¿Se abrió bien? Si no, detenerse y decir el motivo
if (!$conexion) {
    die('Error de conexión: ' . mysqli_connect_error());
}

// 5. Nos aseguramos de que exista la tabla de entradas compradas
//    (guarda cada compra para poder validar butacas ocupadas y devoluciones por código)
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS `entradas` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `pelicula_id` INT NOT NULL,
        `pelicula_titulo` VARCHAR(150) NOT NULL,
        `sala` TINYINT NOT NULL,
        `asientos` VARCHAR(150) NOT NULL,
        `precio_total` INT NOT NULL,
        `codigo` VARCHAR(10) NOT NULL,
        `estado` ENUM('activa','devuelta') NOT NULL DEFAULT 'activa',
        `usuario` VARCHAR(100) DEFAULT NULL,
        `fecha_compra` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `fecha_devolucion` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `codigo` (`codigo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 5-bis. Cada entrada queda atada a una función concreta (fecha + hora + sala).
//        Las entradas viejas, de antes de elegir horario, quedan con estos campos en NULL.
mysqli_query($conexion, "ALTER TABLE `entradas` ADD COLUMN IF NOT EXISTS `funcion_id` INT DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE `entradas` ADD COLUMN IF NOT EXISTS `fecha_funcion` DATE DEFAULT NULL");
mysqli_query($conexion, "ALTER TABLE `entradas` ADD COLUMN IF NOT EXISTS `hora_funcion` TIME DEFAULT NULL");

// 6. Columna para poder "retirar" una película de la cartelera sin borrarla
//    (así el admin puede sacarla de la vista del usuario, por ej. para un evento de último adiós)
mysqli_query($conexion, "
    ALTER TABLE `peliculas`
    ADD COLUMN IF NOT EXISTS `activa` TINYINT(1) NOT NULL DEFAULT 1
");

// 7. Tabla de eventos especiales (reestrenos, funciones de último adiós, u otros)
//    Ningún campo salvo el título y el tipo es obligatorio: un evento puede o no
//    estar atado a una película puntual ni tener fechas definidas.
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS `eventos` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `tipo` ENUM('reestreno','ultimo_adios','otro') NOT NULL DEFAULT 'otro',
        `titulo` VARCHAR(150) NOT NULL,
        `descripcion` VARCHAR(500) DEFAULT NULL,
        `pelicula_id` INT DEFAULT NULL,
        `fecha_inicio` DATE DEFAULT NULL,
        `fecha_fin` DATE DEFAULT NULL,
        `imagen` VARCHAR(255) DEFAULT NULL,
        `activo` TINYINT(1) NOT NULL DEFAULT 1,
        `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `pelicula_id` (`pelicula_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
// (se ejecuta siempre; si ya existe, mysqli_report(OFF) hace que el error se ignore en silencio)
mysqli_query($conexion, "
    ALTER TABLE `eventos`
    ADD CONSTRAINT `eventos_ibfk_1`
    FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas` (`id`) ON DELETE SET NULL
");

// 8. Nos aseguramos de que `usuarios` tenga la columna `rol`
//    (por si alguien corre este proyecto sobre una base vieja
//    que todavía tenía `usuarios` y `admins` por separado).
mysqli_query($conexion, "
    ALTER TABLE `usuarios`
    ADD COLUMN IF NOT EXISTS `rol` VARCHAR(10) NOT NULL DEFAULT 'usuario'
");

// 9. Tabla de salas: dónde se proyectan las películas
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS `salas` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `nombre` VARCHAR(100) NOT NULL,
        `tipo` ENUM('estandar','3d','4dx','vip') NOT NULL DEFAULT 'estandar',
        `estado` ENUM('disponible','mantenimiento') NOT NULL DEFAULT 'disponible',
        `filas` INT NOT NULL DEFAULT 5,
        `butacas_por_fila` INT NOT NULL DEFAULT 10,
        `capacidad` INT NOT NULL DEFAULT 50,
        `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 9-bis. Si `salas` ya existía de una base vieja (con otras columnas, por
//        ejemplo `numero` en vez de `nombre`), le agregamos las columnas
//        que le faltan para que coincida con lo que espera el código.
mysqli_query($conexion, "ALTER TABLE `salas` ADD COLUMN IF NOT EXISTS `nombre` VARCHAR(100) NOT NULL DEFAULT ''");
mysqli_query($conexion, "ALTER TABLE `salas` ADD COLUMN IF NOT EXISTS `tipo` ENUM('estandar','3d','4dx','vip') NOT NULL DEFAULT 'estandar'");
mysqli_query($conexion, "ALTER TABLE `salas` ADD COLUMN IF NOT EXISTS `estado` ENUM('disponible','mantenimiento') NOT NULL DEFAULT 'disponible'");
mysqli_query($conexion, "ALTER TABLE `salas` ADD COLUMN IF NOT EXISTS `filas` INT NOT NULL DEFAULT 5");
mysqli_query($conexion, "ALTER TABLE `salas` ADD COLUMN IF NOT EXISTS `butacas_por_fila` INT NOT NULL DEFAULT 10");
mysqli_query($conexion, "ALTER TABLE `salas` ADD COLUMN IF NOT EXISTS `capacidad` INT NOT NULL DEFAULT 50");
mysqli_query($conexion, "ALTER TABLE `salas` ADD COLUMN IF NOT EXISTS `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

// Semilla: si todavía no hay ninguna sala cargada, arrancamos con las dos
// que ya usa el flujo de compra (Sala 1 y Sala 2, 50 butacas cada una).
$haySalas = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM salas"))[0];
if ((int) $haySalas === 0) {
    mysqli_query($conexion, "
        INSERT INTO `salas` (`nombre`, `tipo`, `estado`, `filas`, `butacas_por_fila`, `capacidad`) VALUES
        ('Sala 1', 'estandar', 'disponible', 5, 10, 50),
        ('Sala 2', 'estandar', 'disponible', 5, 10, 50)
    ");
}

// 10. Tabla de funciones: cuándo y dónde se proyecta cada película
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS `funciones` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `pelicula_id` INT NOT NULL,
        `sala_id` INT NOT NULL,
        `fecha` DATE NOT NULL,
        `hora_inicio` TIME NOT NULL,
        `hora_fin` TIME NOT NULL,
        `precio_entrada` INT NOT NULL DEFAULT 3500,
        `estado` ENUM('programada','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'programada',
        `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `pelicula_id` (`pelicula_id`),
        KEY `sala_id` (`sala_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
// 10-bis. Igual que con `salas`: si `funciones` ya existía de una base
//         vieja (con `hora` en vez de `hora_inicio`/`hora_fin`, sin
//         `precio_entrada` ni `estado`), le agregamos lo que le falta.
mysqli_query($conexion, "ALTER TABLE `funciones` ADD COLUMN IF NOT EXISTS `hora_inicio` TIME NOT NULL DEFAULT '00:00:00'");
mysqli_query($conexion, "ALTER TABLE `funciones` ADD COLUMN IF NOT EXISTS `hora_fin` TIME NOT NULL DEFAULT '00:00:00'");
mysqli_query($conexion, "ALTER TABLE `funciones` ADD COLUMN IF NOT EXISTS `precio_entrada` INT NOT NULL DEFAULT 3500");
mysqli_query($conexion, "ALTER TABLE `funciones` ADD COLUMN IF NOT EXISTS `estado` ENUM('programada','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'programada'");
mysqli_query($conexion, "ALTER TABLE `funciones` ADD COLUMN IF NOT EXISTS `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

mysqli_query($conexion, "
    ALTER TABLE `funciones`
    ADD CONSTRAINT `funciones_ibfk_1`
    FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas` (`id`) ON DELETE CASCADE
");
mysqli_query($conexion, "
    ALTER TABLE `funciones`
    ADD CONSTRAINT `funciones_ibfk_2`
    FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON DELETE RESTRICT
");

// 11. Tabla puente para eventos con más de una película
//     (ej: una maratón de superhéroes con 3 o 4 títulos incluidos)
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS `evento_peliculas` (
        `evento_id` INT NOT NULL,
        `pelicula_id` INT NOT NULL,
        PRIMARY KEY (`evento_id`, `pelicula_id`),
        KEY `pelicula_id` (`pelicula_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
mysqli_query($conexion, "
    ALTER TABLE `evento_peliculas`
    ADD CONSTRAINT `evento_peliculas_ibfk_1`
    FOREIGN KEY (`evento_id`) REFERENCES `eventos` (`id`) ON DELETE CASCADE
");
mysqli_query($conexion, "
    ALTER TABLE `evento_peliculas`
    ADD CONSTRAINT `evento_peliculas_ibfk_2`
    FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas` (`id`) ON DELETE CASCADE
");

// 13. Tabla de reseñas: calificación (1 a 5 estrellas) + comentario opcional
//     que cada usuario deja sobre una película. Un usuario solo puede tener
//     una reseña por película (si vuelve a opinar, se actualiza la que ya tenía).
mysqli_query($conexion, "
    CREATE TABLE IF NOT EXISTS `resenas` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `pelicula_id` INT NOT NULL,
        `usuario_id` INT NOT NULL,
        `usuario_nombre` VARCHAR(100) NOT NULL,
        `calificacion` TINYINT NOT NULL,
        `comentario` VARCHAR(500) DEFAULT NULL,
        `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `pelicula_usuario` (`pelicula_id`, `usuario_id`),
        KEY `pelicula_id` (`pelicula_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
mysqli_query($conexion, "
    ALTER TABLE `resenas`
    ADD CONSTRAINT `resenas_ibfk_1`
    FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas` (`id`) ON DELETE CASCADE
");

// 12. Verificación de que las tablas nuevas realmente existan.
//     Si por algún motivo la creación falló (por ejemplo, si se actualizaron
//     otros archivos .php pero este conexion.php quedó desactualizado, o si
//     el usuario de MySQL no tiene permisos para crear tablas), lo dejamos
//     anotado en el log de errores de PHP en vez de romper la página con un
//     error fatal.
foreach (['salas', 'funciones', 'evento_peliculas', 'resenas'] as $tablaNueva) {
    $existe = mysqli_query($conexion, "SHOW TABLES LIKE '{$tablaNueva}'");
    if (!$existe || mysqli_num_rows($existe) === 0) {
        error_log("CineSystem: no se pudo crear/verificar la tabla `{$tablaNueva}`. Último error de MySQL: " . mysqli_error($conexion));
    }
}

// Cuenta filas de un SELECT COUNT(*) de forma segura: si la consulta falla
// (por ejemplo porque la tabla todavía no existe), devuelve 0 en vez de
// provocar un error fatal de PHP.
function contarFilas(mysqli $conexion, string $sql)
{
    $resultado = mysqli_query($conexion, $sql);
    if (!$resultado) {
        return 0;
    }
    $fila = mysqli_fetch_row($resultado);
    return $fila[0] ?? 0;
}
?>