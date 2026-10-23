<?php
// Funciones auxiliares reutilizadas por las páginas de Salas y Funciones del panel de admin.

// ---------- SALAS ----------

function tiposDeSala(): array
{
    return [
        'estandar' => 'Estándar',
        '3d'       => '3D',
        '4dx'      => '4DX',
        'vip'      => 'VIP',
    ];
}

function etiquetaTipoSala(string $tipo): string
{
    return tiposDeSala()[$tipo] ?? 'Estándar';
}

function esTipoSalaValido(string $tipo): bool
{
    return array_key_exists($tipo, tiposDeSala());
}

function estadosDeSala(): array
{
    return [
        'disponible'   => 'Disponible',
        'mantenimiento'=> 'En mantenimiento',
    ];
}

function etiquetaEstadoSala(string $estado): string
{
    return estadosDeSala()[$estado] ?? 'Disponible';
}

function obtenerTodasLasSalas(mysqli $conexion): array
{
    $sql = "SELECT * FROM salas ORDER BY nombre ASC";
    $resultado = $conexion->query($sql);

    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerSalaPorId(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare("SELECT * FROM salas WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $sala = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $sala ?: null;
}

function salaTieneFunciones(mysqli $conexion, int $id): bool
{
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM funciones WHERE sala_id = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    return $total > 0;
}

// ---------- FUNCIONES ----------

function estadosDeFuncion(): array
{
    return [
        'programada' => 'Programada',
        'en_curso'   => 'En curso',
        'finalizada' => 'Finalizada',
        'cancelada'  => 'Cancelada',
    ];
}

function etiquetaEstadoFuncion(string $estado): string
{
    return estadosDeFuncion()[$estado] ?? 'Programada';
}

function esEstadoFuncionValido(string $estado): bool
{
    return array_key_exists($estado, estadosDeFuncion());
}

function obtenerTodasLasFunciones(mysqli $conexion, array $filtros = []): array
{
    $condiciones = [];
    $parametros = [];
    $tipos = '';

    if (!empty($filtros['pelicula_id'])) {
        $condiciones[] = 'f.pelicula_id = ?';
        $parametros[] = (int) $filtros['pelicula_id'];
        $tipos .= 'i';
    }

    if (!empty($filtros['sala_id'])) {
        $condiciones[] = 'f.sala_id = ?';
        $parametros[] = (int) $filtros['sala_id'];
        $tipos .= 'i';
    }

    if (!empty($filtros['fecha'])) {
        $condiciones[] = 'f.fecha = ?';
        $parametros[] = $filtros['fecha'];
        $tipos .= 's';
    }

    if (!empty($filtros['estado'])) {
        $condiciones[] = 'f.estado = ?';
        $parametros[] = $filtros['estado'];
        $tipos .= 's';
    }

    $sql = "SELECT f.*, p.nombre AS pelicula_nombre, s.nombre AS sala_nombre, s.tipo AS sala_tipo
            FROM funciones f
            JOIN peliculas p ON p.id = f.pelicula_id
            JOIN salas s ON s.id = f.sala_id";

    if (!empty($condiciones)) {
        $sql .= " WHERE " . implode(' AND ', $condiciones);
    }

    $sql .= " ORDER BY f.fecha DESC, f.hora_inicio DESC";

    $stmt = $conexion->prepare($sql);
    if (!empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }
    $stmt->execute();
    $funciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $funciones;
}

function obtenerFuncionPorId(mysqli $conexion, int $id): ?array
{
    $stmt = $conexion->prepare(
        "SELECT f.*, p.nombre AS pelicula_nombre, s.nombre AS sala_nombre
         FROM funciones f
         JOIN peliculas p ON p.id = f.pelicula_id
         JOIN salas s ON s.id = f.sala_id
         WHERE f.id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $funcion = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $funcion ?: null;
}
