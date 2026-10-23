<?php
session_start();
require 'includes/auth.php';

requiereGestion();

require 'conexion.php';
require_once 'eventos-funciones.php';

// Estadísticas rápidas para el dashboard
$totalPeliculas = (int) contarFilas($conexion, "SELECT COUNT(*) FROM peliculas WHERE activa = 1");
$eventosActivos = (int) contarFilas($conexion, "SELECT COUNT(*) FROM eventos WHERE activo = 1");
$totalSalasDisponibles = (int) contarFilas($conexion, "SELECT COUNT(*) FROM salas WHERE estado = 'disponible'");
$funcionesProgramadas = (int) contarFilas($conexion, "SELECT COUNT(*) FROM funciones WHERE estado = 'programada' AND fecha >= CURDATE()");

// Estas estadísticas tocan pagos/devoluciones: son de uso exclusivo del admin
if (esAdmin()) {
    $totalEntradasActivas = (int) contarFilas($conexion, "SELECT COUNT(*) FROM entradas WHERE estado = 'activa'");
    $totalDevueltas = (int) contarFilas($conexion, "SELECT COUNT(*) FROM entradas WHERE estado = 'devuelta'");
    $totalRecaudado = contarFilas($conexion, "SELECT COALESCE(SUM(precio_total),0) FROM entradas WHERE estado = 'activa'");
}

// Búsqueda rápida de películas desde el propio panel (para admin y editor)
$busquedaPeliculas = trim($_GET['buscar_pelicula'] ?? '');
$resultadosBusqueda = [];
if ($busquedaPeliculas !== '') {
    // Búsqueda por prefijo: solo películas que empiecen con lo que se escribió
    // (así "a" no trae todas las que contienen una "a" en cualquier parte del título)
    $like = $busquedaPeliculas . '%';
    $stmt = $conexion->prepare(
        "SELECT id, nombre, lanzamiento, activa
         FROM peliculas
         WHERE nombre LIKE ?
         ORDER BY activa DESC, nombre ASC
         LIMIT 15"
    );
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $resultadosBusqueda = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

include 'includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow"><?= esAdmin() ? 'Panel de administración' : 'Panel de editor' ?></p>
        <h1>Hola, <?= htmlspecialchars($_SESSION['usuario']) ?></h1>
        <p class="salas-subtitulo">
            <?= esAdmin()
                ? 'Desde acá manejás el catálogo, los pagos, las devoluciones y los eventos.'
                : 'Desde acá manejás el catálogo: películas, salas, funciones y eventos.' ?>
        </p>
    </section>

    <section class="admin-buscador-peliculas">
        <form method="GET" class="admin-filtros">
            <input
                type="text"
                name="buscar_pelicula"
                placeholder="Buscar película por la primera letra del título..."
                value="<?= htmlspecialchars($busquedaPeliculas) ?>">
            <button type="submit">Buscar</button>
            <?php if ($busquedaPeliculas !== ''): ?>
                <a href="panel-admin.php" class="limpiar-genero">✕ Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if ($busquedaPeliculas !== ''): ?>
            <div class="admin-tabla-wrap">
                <table class="admin-tabla">
                    <thead>
                        <tr>
                            <th>Película</th>
                            <th>Año</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($resultadosBusqueda)): ?>
                            <tr><td colspan="4">No se encontró ninguna película con ese nombre.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($resultadosBusqueda as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><?= htmlspecialchars((string) $p['lanzamiento']) ?></td>
                                <td>
                                    <span class="admin-estado-pill <?= $p['activa'] ? 'estado-activo' : 'estado-inactivo' ?>">
                                        <?= $p['activa'] ? 'En cartelera' : 'Retirada' ?>
                                    </span>
                                </td>
                                <td class="admin-tabla-acciones">
                                    <a href="admin-pelicula-form.php?id=<?= $p['id'] ?>">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-stats">
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $totalPeliculas ?></span>
            <span class="admin-stat-label">Películas en cartelera</span>
        </div>

        <?php if (esAdmin()): ?>
            <div class="admin-stat-card">
                <span class="admin-stat-num"><?= $totalEntradasActivas ?></span>
                <span class="admin-stat-label">Entradas activas</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num"><?= $totalDevueltas ?></span>
                <span class="admin-stat-label">Entradas devueltas</span>
            </div>
            <div class="admin-stat-card">
                <span class="admin-stat-num">$<?= number_format((float) $totalRecaudado, 0, ',', '.') ?></span>
                <span class="admin-stat-label">Recaudado (vigente)</span>
            </div>
        <?php endif; ?>

        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $eventosActivos ?></span>
            <span class="admin-stat-label">Eventos activos</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $totalSalasDisponibles ?></span>
            <span class="admin-stat-label">Salas disponibles</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $funcionesProgramadas ?></span>
            <span class="admin-stat-label">Funciones programadas</span>
        </div>
    </section>

    <section class="admin-accesos">

        <a href="admin-peliculas.php" class="admin-acceso-card">
            <span class="admin-acceso-icono">🎞️</span>
            <h2>Películas</h2>
            <p>Agregá, editá o retirá películas de la cartelera.</p>
        </a>

        <a href="admin-salas.php" class="admin-acceso-card">
            <span class="admin-acceso-icono">🏛️</span>
            <h2>Salas</h2>
            <p>Administrá dónde se proyectan las películas: capacidad, tipo y estado.</p>
        </a>

        <a href="admin-funciones.php" class="admin-acceso-card">
            <span class="admin-acceso-icono">🕒</span>
            <h2>Funciones</h2>
            <p>Programá cuándo y en qué sala se proyecta cada película.</p>
        </a>

        <a href="admin-eventos.php" class="admin-acceso-card">
            <span class="admin-acceso-icono">🎉</span>
            <h2>Eventos</h2>
            <p>Creá reestrenos, funciones de último adiós u otros eventos, con una o varias películas.</p>
        </a>

        <?php if (esAdmin()): ?>

            <a href="admin-entradas.php" class="admin-acceso-card">
                <span class="admin-acceso-icono">🧾</span>
                <h2>Entradas y pagos</h2>
                <p>Revisá todas las compras: película, butacas, monto y estado.</p>
            </a>

            <a href="admin-devoluciones.php" class="admin-acceso-card">
                <span class="admin-acceso-icono">↩️</span>
                <h2>Devoluciones</h2>
                <p>Verificá un código de reserva real y procesá su devolución.</p>
            </a>

            <a href="admin-reportes.php" class="admin-acceso-card">
                <span class="admin-acceso-icono">📊</span>
                <h2>Reportes / Estadísticas</h2>
                <p>Consultá ventas, recaudación, películas más vistas y devoluciones.</p>
            </a>

            <a href="admin-usuarios.php" class="admin-acceso-card">
                <span class="admin-acceso-icono">👥</span>
                <h2>Usuarios</h2>
                <p>Gestioná las cuentas registradas y sus roles: usuario, editor o admin.</p>
            </a>

        <?php endif; ?>

    </section>

</main>

<?php include 'includes/footer.php'; ?>
