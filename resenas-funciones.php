<?php
// Funciones para las reseñas de los usuarios sobre las películas:
// calificación de 1 a 5 estrellas + comentario opcional.
// Un usuario tiene como máximo una reseña por película (si vuelve a
// opinar, se actualiza la que ya tenía en vez de crear una nueva).

// Todas las reseñas de una película, la más reciente primero.
function obtenerResenasDePelicula(mysqli $conexion, int $peliculaId): array
{
    $stmt = $conexion->prepare(
        "SELECT * FROM resenas WHERE pelicula_id = ? ORDER BY creado_en DESC"
    );
    $stmt->bind_param('i', $peliculaId);
    $stmt->execute();
    $resenas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $resenas;
}

// Promedio de estrellas y cantidad de reseñas de una película.
function obtenerResumenResenas(mysqli $conexion, int $peliculaId): array
{
    $stmt = $conexion->prepare(
        "SELECT COUNT(*) AS cantidad, AVG(calificacion) AS promedio
         FROM resenas WHERE pelicula_id = ?"
    );
    $stmt->bind_param('i', $peliculaId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [
        'cantidad' => (int) ($fila['cantidad'] ?? 0),
        'promedio' => $fila['promedio'] !== null ? round((float) $fila['promedio'], 1) : 0.0,
    ];
}

// La reseña que dejó (si dejó alguna) un usuario puntual sobre una película.
function obtenerResenaDeUsuario(mysqli $conexion, int $peliculaId, int $usuarioId): ?array
{
    $stmt = $conexion->prepare(
        "SELECT * FROM resenas WHERE pelicula_id = ? AND usuario_id = ?"
    );
    $stmt->bind_param('ii', $peliculaId, $usuarioId);
    $stmt->execute();
    $resena = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $resena ?: null;
}

// Crea la reseña de un usuario para una película, o la actualiza si ya
// tenía una cargada (gracias a la UNIQUE KEY pelicula_id+usuario_id).
function guardarResena(
    mysqli $conexion,
    int $peliculaId,
    int $usuarioId,
    string $usuarioNombre,
    int $calificacion,
    ?string $comentario
): void {
    $stmt = $conexion->prepare(
        "INSERT INTO resenas (pelicula_id, usuario_id, usuario_nombre, calificacion, comentario)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            calificacion = VALUES(calificacion),
            comentario = VALUES(comentario),
            creado_en = CURRENT_TIMESTAMP"
    );
    $stmt->bind_param('iisis', $peliculaId, $usuarioId, $usuarioNombre, $calificacion, $comentario);
    $stmt->execute();
    $stmt->close();
}

// Borra la reseña de un usuario sobre una película (solo la propia: el id
// de usuario siempre sale de la sesión, nunca del formulario).
function eliminarResena(mysqli $conexion, int $peliculaId, int $usuarioId): void
{
    $stmt = $conexion->prepare(
        "DELETE FROM resenas WHERE pelicula_id = ? AND usuario_id = ?"
    );
    $stmt->bind_param('ii', $peliculaId, $usuarioId);
    $stmt->execute();
    $stmt->close();
}

// Cadena de estrellas llenas/vacías para mostrar un promedio (redondeado
// al entero más cercano), sin depender de imágenes ni JS.
function renderEstrellas(float $calificacion): string
{
    $llenas = (int) round($calificacion);
    $llenas = max(0, min(5, $llenas));

    return str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
}
