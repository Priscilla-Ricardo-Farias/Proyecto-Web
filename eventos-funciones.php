<?php
// Para listar funciones comprables de las películas de un evento
require_once __DIR__ . '/entradas.php';

// Tipos de evento válidos y su etiqueta para mostrar
function tiposDeEvento(): array
{
    return [
        'reestreno'    => 'Reestreno',
        'ultimo_adios' => 'Último adiós',
        'otro'         => 'Evento especial',
    ];
}

function etiquetaEvento(string $tipo): string
{
    return tiposDeEvento()[$tipo] ?? 'Evento especial';
}

function esTipoEventoValido(string $tipo): bool
{
    return array_key_exists($tipo, tiposDeEvento());
}

// Devuelve las películas asociadas a un evento a través de la tabla puente
// `evento_peliculas` (usada cuando el evento incluye más de un título,
// por ejemplo una maratón de superhéroes).
function obtenerPeliculasDeEvento(mysqli $conexion, int $eventoId): array
{
    $stmt = $conexion->prepare(
        "SELECT p.id, p.nombre, p.imagen
         FROM evento_peliculas ep
         JOIN peliculas p ON p.id = ep.pelicula_id
         WHERE ep.evento_id = ?
         ORDER BY p.nombre ASC"
    );
    $stmt->bind_param('i', $eventoId);
    $stmt->execute();
    $peliculas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $peliculas;
}

// Reemplaza el listado de películas de un evento por el array de ids recibido.
// También deja `eventos.pelicula_id` apuntando a la primera película elegida
// (se sigue usando en algunos lugares como "película principal" del evento).
function guardarPeliculasDeEvento(mysqli $conexion, int $eventoId, array $peliculaIds): void
{
    $peliculaIds = array_values(array_unique(array_map('intval', $peliculaIds)));
    $peliculaIds = array_filter($peliculaIds, fn($id) => $id > 0);

    $stmt = $conexion->prepare("DELETE FROM evento_peliculas WHERE evento_id = ?");
    $stmt->bind_param('i', $eventoId);
    $stmt->execute();
    $stmt->close();

    if (!empty($peliculaIds)) {
        $stmt = $conexion->prepare("INSERT INTO evento_peliculas (evento_id, pelicula_id) VALUES (?, ?)");
        foreach ($peliculaIds as $pid) {
            $stmt->bind_param('ii', $eventoId, $pid);
            $stmt->execute();
        }
        $stmt->close();
    }

    $principal = $peliculaIds[0] ?? null;
    $stmt = $conexion->prepare("UPDATE eventos SET pelicula_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $principal, $eventoId);
    $stmt->execute();
    $stmt->close();
}

// Eventos activos y todavía vigentes (sin fecha_fin, o con fecha_fin >= hoy),
// pensados para mostrarse a los usuarios en la cartelera.
function obtenerEventosVigentes(mysqli $conexion, int $limite = 10): array
{
    $sql = "SELECT e.*, p.nombre AS pelicula_nombre,
                   GROUP_CONCAT(DISTINCT pe.nombre ORDER BY pe.nombre SEPARATOR '|') AS peliculas_nombres
            FROM eventos e
            LEFT JOIN peliculas p ON p.id = e.pelicula_id
            LEFT JOIN evento_peliculas ep ON ep.evento_id = e.id
            LEFT JOIN peliculas pe ON pe.id = ep.pelicula_id
            WHERE e.activo = 1
              AND (e.fecha_fin IS NULL OR e.fecha_fin >= CURDATE())
            GROUP BY e.id
            ORDER BY (e.fecha_inicio IS NULL), e.fecha_inicio ASC, e.creado_en DESC
            LIMIT ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $limite);
    $stmt->execute();
    $eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($eventos as &$evento) {
        $evento['peliculas_lista'] = $evento['peliculas_nombres'] ? explode('|', $evento['peliculas_nombres']) : [];
    }
    unset($evento);

    return $eventos;
}

// Todos los eventos (para el panel de admin), sin importar si están activos o vencidos.
function obtenerTodosLosEventos(mysqli $conexion): array
{
    $sql = "SELECT e.*, p.nombre AS pelicula_nombre,
                   GROUP_CONCAT(DISTINCT pe.nombre ORDER BY pe.nombre SEPARATOR '|') AS peliculas_nombres
            FROM eventos e
            LEFT JOIN peliculas p ON p.id = e.pelicula_id
            LEFT JOIN evento_peliculas ep ON ep.evento_id = e.id
            LEFT JOIN peliculas pe ON pe.id = ep.pelicula_id
            GROUP BY e.id
            ORDER BY e.creado_en DESC";
    $resultado = $conexion->query($sql);
    $eventos = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];

    foreach ($eventos as &$evento) {
        $evento['peliculas_lista'] = $evento['peliculas_nombres'] ? explode('|', $evento['peliculas_nombres']) : [];
    }
    unset($evento);

    return $eventos;
}

function obtenerEventoPorId(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare(
        "SELECT e.*, p.nombre AS pelicula_nombre
         FROM eventos e
         LEFT JOIN peliculas p ON p.id = e.pelicula_id
         WHERE e.id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $evento = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($evento !== null) {
        $peliculas = obtenerPeliculasDeEvento($conexion, $id);
        $evento['peliculas_ids'] = array_map(fn($p) => (int) $p['id'], $peliculas);
        $evento['peliculas_lista'] = array_map(fn($p) => $p['nombre'], $peliculas);
    }

    return $evento ?: null;
}

// Funciones que se pueden comprar ahora mismo para las películas de un evento,
// agrupadas por película. Sólo cuentan las funciones futuras, programadas, en una
// sala disponible y con butacas libres; si el evento tiene fechas, además tienen
// que caer dentro de ese rango. Devuelve, por película, hasta $limite funciones.
//
//   [ ['pelicula_id' => 5, 'pelicula_nombre' => 'Matrix', 'funciones' => [...], 'hay_mas' => bool], ... ]
//
// Las películas sin ninguna función disponible no aparecen en el resultado.
function obtenerFuncionesDisponiblesDeEvento(mysqli $conexion, array $evento, int $limite = 6): array
{
    $eventoId = (int) $evento['id'];
    $peliculaPrincipal = (int) ($evento['pelicula_id'] ?? 0);

    $stmt = $conexion->prepare(
        "SELECT p.id, p.nombre
         FROM peliculas p
         WHERE p.activa = 1
           AND (p.id IN (SELECT pelicula_id FROM evento_peliculas WHERE evento_id = ?) OR p.id = ?)
         ORDER BY p.nombre ASC"
    );
    $stmt->bind_param('ii', $eventoId, $peliculaPrincipal);
    $stmt->execute();
    $peliculas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $desde = $evento['fecha_inicio'] ?? null;
    $hasta = $evento['fecha_fin'] ?? null;

    $grupos = [];
    foreach ($peliculas as $pelicula) {
        $disponibles = [];
        $hayMas = false;

        foreach (obtenerFuncionesDePelicula($conexion, (int) $pelicula['id']) as $f) {
            if (($desde && $f['fecha'] < $desde) || ($hasta && $f['fecha'] > $hasta)) {
                continue;
            }

            $ocupados = obtenerAsientosOcupados($conexion, (int) $pelicula['id'], (string) $f['sala_id'], (int) $f['id']);
            if (BUTACAS_POR_SALA - count($ocupados) <= 0) {
                continue; // agotada
            }

            if (count($disponibles) >= $limite) {
                $hayMas = true;
                break;
            }
            $disponibles[] = $f;
        }

        if (!empty($disponibles)) {
            $grupos[] = [
                'pelicula_id'     => (int) $pelicula['id'],
                'pelicula_nombre' => $pelicula['nombre'],
                'funciones'       => $disponibles,
                'hay_mas'         => $hayMas,
            ];
        }
    }

    return $grupos;
}
