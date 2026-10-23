<?php
session_start();
require __DIR__ . '/../includes/auth.php';

requiereGestion();

require __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../salas-funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin-salas.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'crear' || $accion === 'editar') {

    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $estado = $_POST['estado'] ?? 'disponible';
    $filas = (int) ($_POST['filas'] ?? 0);
    $butacasPorFila = (int) ($_POST['butacas_por_fila'] ?? 0);
    $capacidad = (int) ($_POST['capacidad'] ?? 0);

    if ($nombre === '' || !esTipoSalaValido($tipo) || $filas < 1 || $butacasPorFila < 1 || $capacidad < 1) {
        header('Location: ../admin/admin-sala-form.php?error=faltan_datos');
        exit;
    }

    if (!array_key_exists($estado, estadosDeSala())) {
        $estado = 'disponible';
    }

    if ($accion === 'crear') {
        $stmt = $conexion->prepare(
            "INSERT INTO salas (nombre, tipo, estado, filas, butacas_por_fila, capacidad)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssiii', $nombre, $tipo, $estado, $filas, $butacasPorFila, $capacidad);
        $stmt->execute();
        $stmt->close();

        header('Location: ../admin/admin-salas.php?ok=creada');
        exit;
    }

    // editar
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conexion->prepare(
        "UPDATE salas
         SET nombre = ?, tipo = ?, estado = ?, filas = ?, butacas_por_fila = ?, capacidad = ?
         WHERE id = ?"
    );
    $stmt->bind_param('sssiiii', $nombre, $tipo, $estado, $filas, $butacasPorFila, $capacidad, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-salas.php?ok=editada');
    exit;
}

if ($accion === 'toggle') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conexion->prepare(
        "UPDATE salas SET estado = IF(estado = 'disponible', 'mantenimiento', 'disponible') WHERE id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-salas.php?ok=estado');
    exit;
}

if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);

    // Si la sala tiene funciones cargadas, no la borramos (rompería el historial de funciones).
    if (salaTieneFunciones($conexion, $id)) {
        header('Location: ../admin/admin-salas.php?error=en_uso');
        exit;
    }

    $stmt = $conexion->prepare("DELETE FROM salas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-salas.php?ok=eliminada');
    exit;
}

header('Location: ../admin/admin-salas.php');
exit;
