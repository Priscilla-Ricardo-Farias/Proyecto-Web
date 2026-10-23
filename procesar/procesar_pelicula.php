<?php
session_start();
require __DIR__ . '/../includes/auth.php';

requiereGestion();

require __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin-peliculas.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

// Busca el director por nombre; si no existe, lo crea. Devuelve su id (o null si vino vacío).
function obtenerOCrearDirector(mysqli $conexion, string $nombre): ?int
{
    $nombre = trim($nombre);
    if ($nombre === '') {
        return null;
    }

    $stmt = $conexion->prepare("SELECT id FROM directores WHERE nombre = ?");
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($fila) {
        return (int) $fila['id'];
    }

    $stmt = $conexion->prepare("INSERT INTO directores (nombre) VALUES (?)");
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $nuevoId = (int) $conexion->insert_id;
    $stmt->close();

    return $nuevoId;
}

if ($accion === 'crear' || $accion === 'editar') {

    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') {
        header('Location: ../admin/admin-pelicula-form.php?error=falta_nombre');
        exit;
    }

    $lanzamiento = $_POST['lanzamiento'] !== '' ? (int) $_POST['lanzamiento'] : null;
    $duracion    = $_POST['duracion'] !== '' ? (int) $_POST['duracion'] : null;
    $genero      = trim($_POST['genero'] ?? '') ?: null;
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $imagen      = trim($_POST['imagen'] ?? '') ?: 'https://picsum.photos/seed/' . urlencode($nombre) . '/400/300';
    $directorId  = obtenerOCrearDirector($conexion, $_POST['director'] ?? '');

    if ($accion === 'crear') {
        $stmt = $conexion->prepare(
            "INSERT INTO peliculas (nombre, lanzamiento, genero, duracion, descripcion, director_id, imagen, activa)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->bind_param('sisisis', $nombre, $lanzamiento, $genero, $duracion, $descripcion, $directorId, $imagen);
        $stmt->execute();
        $stmt->close();

        header('Location: ../admin/admin-peliculas.php?ok=creada');
        exit;
    }

    // editar
    $id = (int) ($_POST['id'] ?? 0);
    $activa = isset($_POST['activa']) ? 1 : 0;

    $stmt = $conexion->prepare(
        "UPDATE peliculas
         SET nombre = ?, lanzamiento = ?, genero = ?, duracion = ?, descripcion = ?, director_id = ?, imagen = ?, activa = ?
         WHERE id = ?"
    );
    $stmt->bind_param('sisisisii', $nombre, $lanzamiento, $genero, $duracion, $descripcion, $directorId, $imagen, $activa, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-peliculas.php?ok=editada');
    exit;
}

if ($accion === 'toggle') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conexion->prepare("UPDATE peliculas SET activa = NOT activa WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-peliculas.php?ok=estado');
    exit;
}

if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);

    // Si ya tiene entradas vendidas, no la borramos (rompería el historial): solo se puede desactivar.
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM entradas WHERE pelicula_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $tieneEntradas = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
    $stmt->close();

    if ($tieneEntradas) {
        header('Location: ../admin/admin-peliculas.php?error=en_uso');
        exit;
    }

    // También borramos los eventos que apuntaban a esta película
    $stmt = $conexion->prepare("DELETE FROM eventos WHERE pelicula_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("DELETE FROM peliculas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-peliculas.php?ok=eliminada');
    exit;
}

header('Location: ../admin/admin-peliculas.php');
exit;
