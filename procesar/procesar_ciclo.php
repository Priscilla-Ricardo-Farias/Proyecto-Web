<?php
// Programa de una sola vez las funciones de un evento o ciclo (ej: "Semana todo acción").
// Para cada día del rango y cada horario elige una película del evento, rotando:
// película = lista[(número de día + número de horario) % cantidad de películas].
// Así, cada día la grilla se corre un lugar y todas las películas pasan por todos los horarios.
session_start();
require __DIR__ . '/../includes/auth.php';

requiereGestion();

require __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin-eventos.php');
    exit;
}

$eventoId = (int) ($_POST['evento_id'] ?? 0);
$volver = '../admin/admin-evento-funciones.php?id=' . $eventoId;

// ---- Evento ----
$stmt = $conexion->prepare("SELECT id FROM eventos WHERE id = ?");
$stmt->bind_param('i', $eventoId);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$existe) {
    header('Location: ../admin/admin-eventos.php');
    exit;
}

// ---- Datos del formulario ----
$salaId = (int) ($_POST['sala_id'] ?? 0);
$desde = trim($_POST['desde'] ?? '');
$hasta = trim($_POST['hasta'] ?? '');
$precio = (int) ($_POST['precio_entrada'] ?? -1);

$horarios = [];
foreach ((array) ($_POST['horarios'] ?? []) as $h) {
    $h = trim((string) $h);
    if ($h === '') {
        continue;
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $h)) {
        header('Location: ' . $volver . '&error=horario_invalido');
        exit;
    }
    $horarios[] = $h;
}
$horarios = array_values(array_unique($horarios));
sort($horarios);

if ($salaId < 1 || $precio < 0 || empty($horarios)) {
    header('Location: ' . $volver . '&error=faltan_datos');
    exit;
}

// ---- Sala disponible ----
$stmt = $conexion->prepare("SELECT id FROM salas WHERE id = ? AND estado = 'disponible'");
$stmt->bind_param('i', $salaId);
$stmt->execute();
$salaOk = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$salaOk) {
    header('Location: ' . $volver . '&error=sala_invalida');
    exit;
}

// ---- Fechas ----
$fechaDesde = DateTime::createFromFormat('Y-m-d', $desde);
$fechaHasta = DateTime::createFromFormat('Y-m-d', $hasta);

if (!$fechaDesde || !$fechaHasta || $fechaDesde->format('Y-m-d') !== $desde || $fechaHasta->format('Y-m-d') !== $hasta) {
    header('Location: ' . $volver . '&error=fechas_invalidas');
    exit;
}

$fechaDesde->setTime(0, 0);
$fechaHasta->setTime(0, 0);

if ($fechaHasta < $fechaDesde || $fechaDesde->diff($fechaHasta)->days > 30) {
    header('Location: ' . $volver . '&error=fechas_invalidas');
    exit;
}

// ---- Películas del evento (con su duración) ----
$stmt = $conexion->prepare(
    "SELECT p.id, p.duracion
     FROM evento_peliculas ep
     JOIN peliculas p ON p.id = ep.pelicula_id
     WHERE ep.evento_id = ?
     ORDER BY p.nombre ASC"
);
$stmt->bind_param('i', $eventoId);
$stmt->execute();
$peliculas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($peliculas)) {
    header('Location: ' . $volver . '&error=sin_peliculas');
    exit;
}

// ---- Creación de las funciones ----
$cantidad = count($peliculas);
$creadas = 0;
$omitidas = 0;

$ahora = date('Y-m-d H:i:s');

$stmtSolapa = $conexion->prepare(
    "SELECT COUNT(*) FROM funciones
     WHERE sala_id = ? AND fecha = ? AND estado <> 'cancelada'
       AND hora_inicio < ? AND hora_fin > ?"
);
$stmtInsert = $conexion->prepare(
    "INSERT INTO funciones (pelicula_id, sala_id, fecha, hora_inicio, hora_fin, precio_entrada, estado, evento_id)
     VALUES (?, ?, ?, ?, ?, ?, 'programada', ?)"
);

$dia = clone $fechaDesde;
$numeroDia = 0;

while ($dia <= $fechaHasta) {
    $fecha = $dia->format('Y-m-d');

    foreach ($horarios as $numeroHorario => $hora) {
        $pelicula = $peliculas[($numeroDia + $numeroHorario) % $cantidad];
        $peliculaId = (int) $pelicula['id'];

        $horaInicio = $hora . ':00';
        $minutosInicio = ((int) substr($hora, 0, 2)) * 60 + (int) substr($hora, 3, 2);
        $duracion = (int) ($pelicula['duracion'] ?: 120);
        $minutosFin = min($minutosInicio + max($duracion, 30), 1439); // la columna TIME no pasa de las 23:59
        $horaFin = sprintf('%02d:%02d:00', intdiv($minutosFin, 60), $minutosFin % 60);

        // No creamos funciones que ya empezaron
        if ($fecha . ' ' . $horaInicio <= $ahora || $minutosFin <= $minutosInicio) {
            $omitidas++;
            continue;
        }

        // Ni funciones que se pisen con otra de la misma sala
        $stmtSolapa->bind_param('isss', $salaId, $fecha, $horaFin, $horaInicio);
        $stmtSolapa->execute();
        $solapadas = (int) $stmtSolapa->get_result()->fetch_row()[0];

        if ($solapadas > 0) {
            $omitidas++;
            continue;
        }

        $stmtInsert->bind_param('iisssii', $peliculaId, $salaId, $fecha, $horaInicio, $horaFin, $precio, $eventoId);
        if ($stmtInsert->execute()) {
            $creadas++;
        } else {
            $omitidas++;
        }
    }

    $dia->modify('+1 day');
    $numeroDia++;
}

$stmtSolapa->close();
$stmtInsert->close();

header('Location: ../admin/admin-funciones.php?ok=ciclo&creadas=' . $creadas . '&omitidas=' . $omitidas);
exit;
