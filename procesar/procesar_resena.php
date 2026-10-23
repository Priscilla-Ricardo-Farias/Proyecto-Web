<?php
session_start();
require '../includes/auth.php';

requiereLogin();
bloquearAdmin();

require_once '../conexion.php';
require_once '../resenas-funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cartelera.php');
    exit;
}

$peliculaId = (int) ($_POST['pelicula_id'] ?? 0);
$accion = $_POST['accion'] ?? '';

// Nos aseguramos de que la película exista y siga activa antes de guardar nada
$stmt = $conexion->prepare("SELECT id FROM peliculas WHERE id = ? AND activa = 1");
$stmt->bind_param('i', $peliculaId);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$existe) {
    header('Location: ../cartelera.php');
    exit;
}

if ($accion === 'guardar') {
    $calificacion = (int) ($_POST['calificacion'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    $comentario = $comentario !== '' ? mb_substr($comentario, 0, 500) : null;

    if ($calificacion < 1 || $calificacion > 5) {
        header('Location: ../pelicula.php?id=' . urlencode($peliculaId) . '&error=resena_invalida#resenas');
        exit;
    }

    guardarResena(
        $conexion,
        $peliculaId,
        (int) $_SESSION['usuario_id'],
        $_SESSION['usuario'],
        $calificacion,
        $comentario
    );

    header('Location: ../pelicula.php?id=' . urlencode($peliculaId) . '#resenas');
    exit;
}

if ($accion === 'eliminar') {
    eliminarResena($conexion, $peliculaId, (int) $_SESSION['usuario_id']);

    header('Location: ../pelicula.php?id=' . urlencode($peliculaId) . '#resenas');
    exit;
}

header('Location: ../pelicula.php?id=' . urlencode($peliculaId));
exit;
