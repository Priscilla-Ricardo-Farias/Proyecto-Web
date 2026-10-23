<?php
session_start();
require __DIR__ . '/../includes/auth.php';

requiereGestion();

require __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../eventos-funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin-eventos.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'crear' || $accion === 'editar') {

    $titulo = trim($_POST['titulo'] ?? '');
    $tipo = $_POST['tipo'] ?? '';

    if ($titulo === '' || !esTipoEventoValido($tipo)) {
        header('Location: ../admin/admin-evento-form.php?error=faltan_datos');
        exit;
    }

    // Todo lo demás es opcional a propósito: un evento no tiene por qué
    // depender de una película puntual ni de fechas definidas.
    $fechaInicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fechaFin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $imagen = trim($_POST['imagen'] ?? '') ?: null;

    // Un evento puede incluir 0, 1 o varias películas (ej: maratón de superhéroes).
    $peliculaIds = array_map('intval', $_POST['peliculas_ids'] ?? []);

    if ($accion === 'crear') {
        $stmt = $conexion->prepare(
            "INSERT INTO eventos (tipo, titulo, descripcion, pelicula_id, fecha_inicio, fecha_fin, imagen, activo)
             VALUES (?, ?, ?, NULL, ?, ?, ?, 1)"
        );
        $stmt->bind_param('ssssss', $tipo, $titulo, $descripcion, $fechaInicio, $fechaFin, $imagen);
        $stmt->execute();
        $nuevoId = (int) $conexion->insert_id;
        $stmt->close();

        guardarPeliculasDeEvento($conexion, $nuevoId, $peliculaIds);

        header('Location: ../admin/admin-eventos.php?ok=creado');
        exit;
    }

    // editar
    $id = (int) ($_POST['id'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    $stmt = $conexion->prepare(
        "UPDATE eventos
         SET tipo = ?, titulo = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, imagen = ?, activo = ?
         WHERE id = ?"
    );
    $stmt->bind_param('ssssssii', $tipo, $titulo, $descripcion, $fechaInicio, $fechaFin, $imagen, $activo, $id);
    $stmt->execute();
    $stmt->close();

    guardarPeliculasDeEvento($conexion, $id, $peliculaIds);

    header('Location: ../admin/admin-eventos.php?ok=editado');
    exit;
}

if ($accion === 'toggle') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conexion->prepare("UPDATE eventos SET activo = NOT activo WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-eventos.php?ok=estado');
    exit;
}

if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conexion->prepare("DELETE FROM eventos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-eventos.php?ok=eliminado');
    exit;
}

header('Location: ../admin/admin-eventos.php');
exit;
