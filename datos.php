<?php
require_once 'conexion.php';

$peliculas = [];

$sql = "SELECT p.*, d.nombre AS director_nombre
        FROM peliculas p
        LEFT JOIN directores d ON d.id = p.director_id";

$resultado = mysqli_query($conexion, $sql);
if (!$resultado) {
    die('Error en la consulta (peliculas): ' . mysqli_error($conexion));
}

while ($fila = mysqli_fetch_assoc($resultado)) {
    $peliculas[$fila['id']] = [
        'titulo'      => $fila['nombre'],
        'anio'        => $fila['lanzamiento'],
        'genero'      => $fila['genero'],
        'duracion'    => $fila['duracion'] !== null ? $fila['duracion'] . ' min' : '',
        'director'    => $fila['director_nombre'] ?? '',
        'descripcion' => $fila['descripcion'] ?? '',
        'imagen'      => $fila['imagen'],
        'activa'      => isset($fila['activa']) ? (bool) $fila['activa'] : true,
    ];
}

return $peliculas;