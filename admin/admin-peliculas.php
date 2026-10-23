<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';

$busqueda = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, d.nombre AS director_nombre
        FROM peliculas p
        LEFT JOIN directores d ON d.id = p.director_id";
$parametros = [];
$tipos = '';

if ($busqueda !== '') {
    // Búsqueda por prefijo: solo películas cuyo título empiece con lo escrito
    // (evita que buscar "a" traiga todas las películas que tengan una "a" en cualquier parte)
    $sql .= " WHERE p.nombre LIKE ?";
    $like = $busqueda . '%';
    $parametros = [$like];
    $tipos = 's';
}

$sql .= " ORDER BY p.activa DESC, p.nombre ASC";

$stmt = $conexion->prepare($sql);
if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$peliculas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Catálogo</p>
        <h1>Películas</h1>
        <p class="salas-subtitulo">Agregá nuevas películas o gestioná las que ya están cargadas.</p>
    </section>

    <?php if (isset($_GET['ok'])): ?>
        <div class="admin-aviso admin-aviso-exito">
            <?php
                $mensajes = [
                    'creada'   => 'La película se creó correctamente.',
                    'editada'  => 'Los cambios se guardaron correctamente.',
                    'eliminada'=> 'La película se eliminó del catálogo.',
                    'estado'   => 'Se actualizó el estado de la película.',
                ];
                echo htmlspecialchars($mensajes[$_GET['ok']] ?? 'Listo.');
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="admin-aviso admin-aviso-error">
            <?php
                $errores = [
                    'en_uso' => 'No se puede eliminar: esta película tiene entradas vendidas. Podés desactivarla en su lugar.',
                ];
                echo htmlspecialchars($errores[$_GET['error']] ?? 'Ocurrió un error.');
            ?>
        </div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <a href="admin-pelicula-form.php" class="btn-comprar">+ Agregar película</a>
    </div>

    <form method="GET" class="admin-filtros">
        <input
            type="text"
            name="q"
            placeholder="Buscar por la primera letra del título..."
            value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit">Buscar</button>
        <?php if ($busqueda !== ''): ?>
            <a href="admin-peliculas.php" class="limpiar-genero">✕ Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="admin-tabla-wrap">
        <table class="admin-tabla">
            <thead>
                <tr>
                    <th>Película</th>
                    <th>Año</th>
                    <th>Género</th>
                    <th>Director</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($peliculas)): ?>
                    <tr><td colspan="6"><?= $busqueda !== '' ? 'No se encontró ninguna película con ese filtro.' : 'Todavía no hay películas cargadas.' ?></td></tr>
                <?php endif; ?>

                <?php foreach ($peliculas as $p): ?>
                    <tr class="<?= $p['activa'] ? '' : 'fila-inactiva' ?>">
                        <td class="admin-tabla-titulo">
                            <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" class="admin-tabla-miniatura">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </td>
                        <td><?= htmlspecialchars((string) $p['lanzamiento']) ?></td>
                        <td><?= htmlspecialchars($p['genero'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['director_nombre'] ?? '—') ?></td>
                        <td>
                            <span class="admin-estado-pill <?= $p['activa'] ? 'estado-activo' : 'estado-inactivo' ?>">
                                <?= $p['activa'] ? 'En cartelera' : 'Retirada' ?>
                            </span>
                        </td>
                        <td class="admin-tabla-acciones">
                            <a href="admin-pelicula-form.php?id=<?= $p['id'] ?>">Editar</a>

                            <form action="../procesar/procesar_pelicula.php" method="POST" class="admin-form-inline">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="admin-link-btn">
                                    <?= $p['activa'] ? 'Retirar' : 'Reactivar' ?>
                                </button>
                            </form>

                            <form action="../procesar/procesar_pelicula.php" method="POST" class="admin-form-inline"
                                  onsubmit="return confirm('¿Eliminar esta película definitivamente? Esta acción no se puede deshacer.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
