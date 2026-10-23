<?php
// Asientos ocupados: sólo los que realmente están comprados (entradas activas).
// Las butacas arrancan todas libres y se ocupan cuando alguien compra; si una
// entrada se devuelve, sus butacas vuelven a quedar libres.
//
// Si se pasa $funcionId, sólo cuentan las entradas de ESA función (cada horario
// tiene su propia sala llena o vacía). Sin $funcionId queda el comportamiento viejo.
function obtenerAsientosOcupados(mysqli $conexion, $peliculaId, string $sala, ?int $funcionId = null): array
{
    $ocupados = [];

    if ($funcionId !== null) {
        $stmt = $conexion->prepare(
            "SELECT asientos FROM entradas WHERE funcion_id = ? AND estado = 'activa'"
        );
        $stmt->bind_param('i', $funcionId);
    } else {
        $stmt = $conexion->prepare(
            "SELECT asientos FROM entradas WHERE pelicula_id = ? AND sala = ? AND estado = 'activa'"
        );
        $stmt->bind_param('is', $peliculaId, $sala);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        foreach (explode(',', $fila['asientos']) as $asiento) {
            $ocupados[] = trim($asiento);
        }
    }
    $stmt->close();

    return array_values(array_unique($ocupados));
}

// Genera un código de entrada alfanumérico y se asegura de que no exista ya en la base
function generarCodigoEntrada(mysqli $conexion): string
{
    do {
        $codigo = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = $conexion->prepare("SELECT id FROM entradas WHERE codigo = ?");
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($existe);

    return $codigo;
}

// Valida y normaliza la lista de asientos que viene del formulario (ej: "A1,B5,B6")
function normalizarAsientos(?string $crudo): array
{
    if ($crudo === null || $crudo === '') {
        return [];
    }

    $asientos = array_map('trim', explode(',', $crudo));
    $asientos = array_filter($asientos, fn($a) => preg_match('/^[A-E](10|[1-9])$/', $a));
    $asientos = array_values(array_unique($asientos));
    sort($asientos);

    return $asientos;
}


// ---------------------------------------------------------------------
//  FUNCIONES (fecha + hora + sala) PARA COMPRAR
// ---------------------------------------------------------------------

const BUTACAS_POR_SALA = 50; // mapa fijo: 5 filas (A-E) x 10 asientos

function nombreDiaSemana(string $fecha, bool $corto = false): string
{
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $nombre = $dias[(int) date('w', strtotime($fecha))];
    return $corto ? mb_substr($nombre, 0, 3) : $nombre;
}

function nombreMes(string $fecha): string
{
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
              'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $meses[(int) date('n', strtotime($fecha)) - 1];
}

// "Viernes 16 de octubre"
function fechaLarga(string $fecha): string
{
    return nombreDiaSemana($fecha) . ' ' . (int) date('j', strtotime($fecha)) . ' de ' . nombreMes($fecha);
}

// "19:30"
function horaCorta(string $hora): string
{
    return substr($hora, 0, 5);
}

// ¿La función todavía se puede comprar? (programada y que no haya empezado)
function funcionEsComprable(array $f): bool
{
    if (($f['estado'] ?? '') !== 'programada') {
        return false;
    }
    return strtotime($f['fecha'] . ' ' . $f['hora_inicio']) > time();
}

// Funciones futuras y programadas de una película, ordenadas por día y hora
function obtenerFuncionesDePelicula(mysqli $conexion, int $peliculaId): array
{
    $hoy = date('Y-m-d');
    $ahora = date('H:i:s');

    $stmt = $conexion->prepare(
        "SELECT f.*, s.nombre AS sala_nombre, s.tipo AS sala_tipo
         FROM funciones f
         JOIN salas s ON s.id = f.sala_id
         WHERE f.pelicula_id = ?
           AND f.estado = 'programada'
           AND s.estado = 'disponible'
           AND (f.fecha > ? OR (f.fecha = ? AND f.hora_inicio > ?))
         ORDER BY f.fecha ASC, f.hora_inicio ASC"
    );
    $stmt->bind_param('isss', $peliculaId, $hoy, $hoy, $ahora);
    $stmt->execute();
    $funciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $funciones;
}

// Una función puntual lista para comprar (con datos de sala y película), o null
function obtenerFuncionParaCompra(mysqli $conexion, int $funcionId): ?array
{
    $stmt = $conexion->prepare(
        "SELECT f.*, s.nombre AS sala_nombre, s.tipo AS sala_tipo
         FROM funciones f
         JOIN salas s ON s.id = f.sala_id
         WHERE f.id = ?"
    );
    $stmt->bind_param('i', $funcionId);
    $stmt->execute();
    $f = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$f || !funcionEsComprable($f)) {
        return null;
    }
    return $f;
}

// Arma (sin tocar la base) la grilla de horarios por defecto: 7 días x 3 horarios.
// Devuelve filas [fecha, hora_inicio, hora_fin, sala_id].
function generarHorariosPorDefecto(int $ahoraTs, array $salaIds, int $duracionMin): array
{
    if (empty($salaIds)) {
        return [];
    }

    $horarios = ['16:00', '19:00', '22:00'];
    $duracion = $duracionMin > 0 ? $duracionMin : 120;
    $filas = [];

    for ($dia = 0; $dia < 7; $dia++) {
        $fecha = date('Y-m-d', $ahoraTs + $dia * 86400);

        foreach ($horarios as $i => $hora) {
            $inicioTs = strtotime($fecha . ' ' . $hora);
            if ($inicioTs <= $ahoraTs) {
                continue; // ese horario de hoy ya pasó
            }

            // hora_fin = inicio + duración + 15 min de limpieza (tope 23:59)
            $finTs = $inicioTs + ($duracion + 15) * 60;
            $fin = date('Y-m-d', $finTs) === $fecha ? date('H:i:s', $finTs) : '23:59:00';

            $filas[] = [
                $fecha,
                $hora . ':00',
                $fin,
                (int) $salaIds[($dia + $i) % count($salaIds)],
            ];
        }
    }

    return $filas;
}

// Si la película todavía no tiene NINGUNA función de hoy en adelante, le carga
// una programación por defecto para que se pueda comprar. El admin después puede
// editarla, cancelarla o agregar más desde "Funciones".
function sembrarFuncionesPorDefecto(mysqli $conexion, int $peliculaId, int $duracionMin): void
{
    $hoy = date('Y-m-d');

    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM funciones WHERE pelicula_id = ? AND fecha >= ?");
    $stmt->bind_param('is', $peliculaId, $hoy);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total > 0) {
        return;
    }

    $salaIds = [];
    $res = mysqli_query($conexion, "SELECT id FROM salas WHERE estado = 'disponible' ORDER BY id LIMIT 2");
    while ($res && ($fila = mysqli_fetch_assoc($res))) {
        $salaIds[] = (int) $fila['id'];
    }

    $stmt = $conexion->prepare(
        "INSERT INTO funciones (pelicula_id, sala_id, fecha, hora_inicio, hora_fin, precio_entrada, estado)
         VALUES (?, ?, ?, ?, ?, 3500, 'programada')"
    );
    foreach (generarHorariosPorDefecto(time(), $salaIds, $duracionMin) as [$fecha, $inicio, $fin, $salaId]) {
        $stmt->bind_param('iisss', $peliculaId, $salaId, $fecha, $inicio, $fin);
        $stmt->execute();
    }
    $stmt->close();
}


// ---------------------------------------------------------------------
//  DEVOLUCIONES: sólo se pueden hacer ANTES de que empiece la función
// ---------------------------------------------------------------------

// ¿Ya empezó (o ya pasó) la función de esta entrada?
// Las entradas viejas, de antes de elegir horario, no tienen fecha/hora de
// función guardada: en ese caso no hay forma de saberlo y se devuelve false.
function funcionDeEntradaYaEmpezo(array $entrada): bool
{
    if (empty($entrada['fecha_funcion']) || empty($entrada['hora_funcion'])) {
        return false;
    }
    return strtotime($entrada['fecha_funcion'] . ' ' . $entrada['hora_funcion']) <= time();
}

// ¿Se puede devolver? Tiene que estar activa y la función todavía no haber empezado.
function entradaEsDevolvible(array $entrada): bool
{
    return ($entrada['estado'] ?? '') === 'activa' && !funcionDeEntradaYaEmpezo($entrada);
}
