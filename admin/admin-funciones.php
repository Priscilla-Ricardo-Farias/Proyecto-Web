<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';
require_once '../salas-funciones.php';

$filtroPelicula = isset($_GET['pelicula_id']) && $_GET['pelicula_id'] !== '' ? (int) $_GET['pelicula_id'] : null;
$filtroSala = isset($_GET['sala_id']) && $_GET['sala_id'] !== '' ? (int) $_GET['sala_id'] : null;
$filtroFecha = trim($_GET['fecha'] ?? '');
$filtroEstado = $_GET['estado'] ?? '';
if (!array_key_exists($filtroEstado, estadosDeFuncion())) {
    $filtroEstado = '';
}

$funciones = obtenerTodasLasFunciones($conexion, [
    'pelicula_id' => $filtroPelicula,
    'sala_id'     => $filtroSala,
    'fecha'       => $filtroFecha,
    'estado'      => $filtroEstado,
]);

$peliculas = mysqli_query($conexion, "SELECT id, nombre FROM peliculas ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
$salas = obtenerTodasLasSalas($conexion);

$hayFiltros = $filtroPelicula || $filtroSala || $filtroFecha !== '' || $filtroEstado !== '';

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Programación</p>
        <h1>Funciones</h1>
        <p class="salas-subtitulo">Administrá cuándo y en qué sala se proyecta cada película.</p>
    </section>

    <?php if (isset($_GET['ok'])): ?>
        <div class="admin-aviso admin-aviso-exito">
            <?php
                $mensajes = [
                    'creada'   => 'La función se creó correctamente.',
                    'editada'  => 'Los cambios se guardaron correctamente.',
                    'eliminada'=> 'La función se eliminó.',
                    'estado'   => 'Se actualizó el estado de la función.',
                ];
                echo htmlspecialchars($mensajes[$_GET['ok']] ?? 'Listo.');
            ?>
        </div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <a href="admin-funcion-form.php" class="btn-comprar">+ Programar función</a>
    </div>

    <form method="GET" class="admin-filtros">
        <select name="pelicula_id">
            <option value="">Todas las películas</option>
            <?php foreach ($peliculas as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $filtroPelicula === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sala_id">
            <option value="">Todas las salas</option>
            <?php foreach ($salas as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filtroSala === (int) $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="date" name="fecha" value="<?= htmlspecialchars($filtroFecha) ?>">

        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (estadosDeFuncion() as $valor => $etiqueta): ?>
                <option value="<?= $valor ?>" <?= $filtroEstado === $valor ? 'selected' : '' ?>>
                    <?= htmlspecialchars($etiqueta) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Filtrar</button>
        <?php if ($hayFiltros): ?>
            <a href="admin-funciones.php" class="limpiar-genero">✕ Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="admin-tabla-wrap">
        <table class="admin-tabla">
            <thead>
                <tr>
                    <th>Película</th>
                    <th>Sala</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($funciones)): ?>
                    <tr><td colspan="7">No hay funciones que coincidan con este filtro.</td></tr>
                <?php endif; ?>

                <?php foreach ($funciones as $f): ?>
                    <tr class="<?= $f['estado'] === 'cancelada' ? 'fila-inactiva' : '' ?>">
                        <td><?= htmlspecialchars($f['pelicula_nombre']) ?></td>
                        <td><?= htmlspecialchars($f['sala_nombre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                        <td><?= date('H:i', strtotime($f['hora_inicio'])) ?> — <?= date('H:i', strtotime($f['hora_fin'])) ?></td>
                        <td>$<?= number_format((float) $f['precio_entrada'], 0, ',', '.') ?></td>
                        <td>
                            <span class="admin-estado-pill <?= $f['estado'] === 'cancelada' ? 'estado-inactivo' : 'estado-activo' ?>">
                                <?= htmlspecialchars(etiquetaEstadoFuncion($f['estado'])) ?>
                            </span>
                        </td>
                        <td class="admin-tabla-acciones">
                            <a href="admin-funcion-form.php?id=<?= $f['id'] ?>">Editar</a>

                            <form action="../procesar/procesar_funcion.php" method="POST" class="admin-form-inline"
                                  onsubmit="return confirm('¿Eliminar esta función?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <button type="submit" class="admin-link-btn admin-link-btn-peligro">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<?php include '../includes/footer.php'; ?>
