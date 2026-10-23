<?php
session_start();
require __DIR__ . '/../includes/auth.php';

requiereGestion();

require __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../salas-funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin-funciones.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'crear' || $accion === 'editar') {

    $peliculaId = (int) ($_POST['pelicula_id'] ?? 0);
    $salaId = (int) ($_POST['sala_id'] ?? 0);
    $fecha = trim($_POST['fecha'] ?? '');
    $horaInicio = trim($_POST['hora_inicio'] ?? '');
    $horaFin = trim($_POST['hora_fin'] ?? '');
    $precio = (int) ($_POST['precio_entrada'] ?? -1);
    $estado = $_POST['estado'] ?? 'programada';

    if ($peliculaId < 1 || $salaId < 1 || $fecha === '' || $horaInicio === '' || $horaFin === '' || $precio < 0) {
        $redirFaltantes = $accion === 'editar' ? '?id=' . (int) ($_POST['id'] ?? 0) . '&error=faltan_datos' : '?error=faltan_datos';
        header('Location: ../admin/admin-funcion-form.php' . $redirFaltantes);
        exit;
    }

    if (!esEstadoFuncionValido($estado)) {
        $estado = 'programada';
    }

    if (strtotime($horaFin) <= strtotime($horaInicio)) {
        $redir = $accion === 'editar' ? '?id=' . (int) ($_POST['id'] ?? 0) . '&error=horario_invalido' : '?error=horario_invalido';
        header('Location: ../admin/admin-funcion-form.php' . $redir);
        exit;
    }

    if ($accion === 'crear') {
        $stmt = $conexion->prepare(
            "INSERT INTO funciones (pelicula_id, sala_id, fecha, hora_inicio, hora_fin, precio_entrada, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iisssis', $peliculaId, $salaId, $fecha, $horaInicio, $horaFin, $precio, $estado);
        $stmt->execute();
        $stmt->close();

        header('Location: ../admin/admin-funciones.php?ok=creada');
        exit;
    }

    // editar
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conexion->prepare(
        "UPDATE funciones
         SET pelicula_id = ?, sala_id = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, precio_entrada = ?, estado = ?
         WHERE id = ?"
    );
    $stmt->bind_param('iisssisi', $peliculaId, $salaId, $fecha, $horaInicio, $horaFin, $precio, $estado, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-funciones.php?ok=editada');
    exit;
}

if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $conexion->prepare("DELETE FROM funciones WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-funciones.php?ok=eliminada');
    exit;
}

header('Location: ../admin/admin-funciones.php');
exit;
