<?php
session_start();
require '../includes/auth.php';

requiereAdmin();

require_once '../conexion.php';
require_once '../entradas.php';

// ---------- Rango de fechas para "Recaudación por período" ----------
$hasta = trim($_GET['hasta'] ?? '') ?: date('Y-m-d');
$desde = trim($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-29 days'));

// Validación básica: si vienen mal formadas, volvemos al rango por defecto
if (!strtotime($desde)) { $desde = date('Y-m-d', strtotime('-29 days')); }
if (!strtotime($hasta)) { $hasta = date('Y-m-d'); }
if ($desde > $hasta) { [$desde, $hasta] = [$hasta, $desde]; }

// ---------- 1. Entradas vendidas y recaudación (totales generales) ----------
$totalEntradasVendidas = (int) contarFilas($conexion, "SELECT COUNT(*) FROM entradas");
$totalActivas = (int) contarFilas($conexion, "SELECT COUNT(*) FROM entradas WHERE estado='activa'");
$totalDevueltas = (int) contarFilas($conexion, "SELECT COUNT(*) FROM entradas WHERE estado='devuelta'");
$totalRecaudado = (float) contarFilas($conexion, "SELECT COALESCE(SUM(precio_total),0) FROM entradas WHERE estado='activa'");
$totalReintegrado = (float) contarFilas($conexion, "SELECT COALESCE(SUM(precio_total),0) FROM entradas WHERE estado='devuelta'");

// ---------- 2. Películas más vistas ----------
$resultadoPeliculas = mysqli_query($conexion, "
    SELECT pelicula_titulo, COUNT(*) AS entradas, COALESCE(SUM(precio_total),0) AS recaudado
    FROM entradas
    WHERE estado = 'activa'
    GROUP BY pelicula_titulo
    ORDER BY entradas DESC
    LIMIT 5
");
$peliculasMasVistas = $resultadoPeliculas ? $resultadoPeliculas->fetch_all(MYSQLI_ASSOC) : [];

// ---------- 3. Funciones con mayor cantidad de espectadores ----------
// Nota: como la compra de entradas no pide elegir una función puntual (solo sala),
// el "espectador estimado" se calcula por cantidad de entradas activas vendidas
// para esa misma película, no por butaca exacta de esa función.
$resultadoFunciones = mysqli_query($conexion, "
    SELECT f.id, f.fecha, f.hora_inicio, p.nombre AS pelicula_nombre, s.nombre AS sala_nombre,
           (SELECT COUNT(*) FROM entradas e WHERE e.pelicula_id = f.pelicula_id AND e.estado = 'activa') AS espectadores_estimados
    FROM funciones f
    JOIN peliculas p ON p.id = f.pelicula_id
    JOIN salas s ON s.id = f.sala_id
    ORDER BY espectadores_estimados DESC, f.fecha DESC
    LIMIT 10
");
$funcionesTop = $resultadoFunciones ? $resultadoFunciones->fetch_all(MYSQLI_ASSOC) : [];

// ---------- 4. Devoluciones realizadas (últimas 10) ----------
$resultadoDevoluciones = mysqli_query($conexion, "
    SELECT * FROM entradas WHERE estado = 'devuelta' ORDER BY fecha_devolucion DESC LIMIT 10
");
$ultimasDevoluciones = $resultadoDevoluciones ? $resultadoDevoluciones->fetch_all(MYSQLI_ASSOC) : [];

// ---------- 5. Recaudación por período (agrupada por día) ----------
$stmt = $conexion->prepare("
    SELECT DATE(fecha_compra) AS dia, COUNT(*) AS entradas, COALESCE(SUM(precio_total),0) AS recaudado
    FROM entradas
    WHERE estado = 'activa' AND DATE(fecha_compra) BETWEEN ? AND ?
    GROUP BY DATE(fecha_compra)
    ORDER BY dia DESC
");
$stmt->bind_param('ss', $desde, $hasta);
$stmt->execute();
$recaudacionPorDia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalPeriodoEntradas = array_sum(array_column($recaudacionPorDia, 'entradas'));
$totalPeriodoRecaudado = array_sum(array_column($recaudacionPorDia, 'recaudado'));

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Panel de administración</p>
        <h1>Reportes / Estadísticas</h1>
        <p class="salas-subtitulo">Consultá entradas vendidas, recaudación, películas más vistas, funciones y devoluciones.</p>
    </section>

    <section class="admin-stats">
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $totalEntradasVendidas ?></span>
            <span class="admin-stat-label">Entradas vendidas (histórico)</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $totalActivas ?></span>
            <span class="admin-stat-label">Entradas activas</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num">$<?= number_format($totalRecaudado, 0, ',', '.') ?></span>
            <span class="admin-stat-label">Recaudación total (vigente)</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $totalDevueltas ?></span>
            <span class="admin-stat-label">Devoluciones realizadas</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num">$<?= number_format($totalReintegrado, 0, ',', '.') ?></span>
            <span class="admin-stat-label">Total reintegrado</span>
        </div>
    </section>

    <div class="admin-reportes-grid">

        <section class="admin-subseccion">
            <h2>Películas más vistas</h2>
            <div class="admin-tabla-wrap">
                <table class="admin-tabla">
                    <thead>
                        <tr>
                            <th>Película</th>
                            <th>Entradas</th>
                            <th>Recaudado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peliculasMasVistas)): ?>
                            <tr><td colspan="3">Todavía no hay entradas vendidas.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($peliculasMasVistas as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['pelicula_titulo']) ?></td>
                                <td><?= (int) $p['entradas'] ?></td>
                                <td>$<?= number_format((float) $p['recaudado'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-subseccion">
            <h2>Devoluciones realizadas</h2>
            <div class="admin-tabla-wrap">
                <table class="admin-tabla">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Película</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimasDevoluciones)): ?>
                            <tr><td colspan="4">Todavía no se procesó ninguna devolución.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($ultimasDevoluciones as $d): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($d['codigo']) ?></strong></td>
                                <td><?= htmlspecialchars($d['pelicula_titulo']) ?></td>
                                <td>$<?= number_format((float) $d['precio_total'], 0, ',', '.') ?></td>
                                <td><?= date('d/m/Y', strtotime($d['fecha_devolucion'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <section class="admin-subseccion">
        <h2>Funciones con mayor cantidad de espectadores</h2>
        <div class="admin-tabla-wrap">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Película</th>
                        <th>Sala</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Espectadores (estimado)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($funcionesTop)): ?>
                        <tr><td colspan="5">Todavía no hay funciones programadas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($funcionesTop as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['pelicula_nombre']) ?></td>
                            <td><?= htmlspecialchars($f['sala_nombre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                            <td><?= date('H:i', strtotime($f['hora_inicio'])) ?></td>
                            <td><?= (int) $f['espectadores_estimados'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="admin-reportes-nota">
            * Como la compra no pide elegir una función puntual (solo la sala), el espectador estimado
            se calcula según el total de entradas activas vendidas para esa misma película.
        </p>
    </section>

    <section class="admin-subseccion">
        <h2>Recaudación por período</h2>

        <form method="GET" class="admin-filtros">
            <label style="display:flex;flex-direction:column;gap:4px;font-size:.8rem;color:var(--muted);">
                Desde
                <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
            </label>
            <label style="display:flex;flex-direction:column;gap:4px;font-size:.8rem;color:var(--muted);">
                Hasta
                <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
            </label>
            <button type="submit" style="align-self:flex-end;">Consultar</button>
        </form>

        <div class="admin-tabla-wrap">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Entradas vendidas</th>
                        <th>Recaudado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recaudacionPorDia)): ?>
                        <tr><td colspan="3">No hubo ventas activas en este período.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recaudacionPorDia as $dia): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($dia['dia'])) ?></td>
                            <td><?= (int) $dia['entradas'] ?></td>
                            <td>$<?= number_format((float) $dia['recaudado'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="admin-reportes-total">
                Total del período (<?= date('d/m/Y', strtotime($desde)) ?> — <?= date('d/m/Y', strtotime($hasta)) ?>):
                <?= $totalPeriodoEntradas ?> entradas · $<?= number_format((float) $totalPeriodoRecaudado, 0, ',', '.') ?>
            </p>
        </div>
    </section>

</main>

<?php include '../includes/footer.php'; ?>
