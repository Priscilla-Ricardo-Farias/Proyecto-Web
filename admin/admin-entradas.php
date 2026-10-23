<?php
session_start();
require '../includes/auth.php';

requiereAdmin();

require_once '../conexion.php';
require_once '../entradas.php';

$estado = $_GET['estado'] ?? 'todas';
if (!in_array($estado, ['todas', 'activa', 'devuelta'], true)) {
    $estado = 'todas';
}

$busqueda = trim($_GET['q'] ?? '');

$condiciones = [];
$parametros = [];
$tipos = '';

if ($estado !== 'todas') {
    $condiciones[] = 'estado = ?';
    $parametros[] = $estado;
    $tipos .= 's';
}

if ($busqueda !== '') {
    $condiciones[] = '(codigo LIKE ? OR usuario LIKE ? OR pelicula_titulo LIKE ?)';
    $like = '%' . $busqueda . '%';
    $parametros[] = $like;
    $parametros[] = $like;
    $parametros[] = $like;
    $tipos .= 'sss';
}

$sql = "SELECT * FROM entradas";
if (!empty($condiciones)) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY fecha_compra DESC LIMIT 300";

$stmt = $conexion->prepare($sql);
if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$entradas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Totales generales (sin filtrar) para el resumen de arriba
$totalActivas = (int) mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM entradas WHERE estado='activa'"))[0];
$totalDevueltas = (int) mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM entradas WHERE estado='devuelta'"))[0];
$totalRecaudado = mysqli_fetch_row(mysqli_query($conexion, "SELECT COALESCE(SUM(precio_total),0) FROM entradas WHERE estado='activa'"))[0];

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Pagos y entradas</p>
        <h1>Entradas vendidas</h1>
        <p class="salas-subtitulo">Todas las compras hechas en el sistema, con su código de reserva.</p>
    </section>

    <section class="admin-stats">
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $totalActivas ?></span>
            <span class="admin-stat-label">Activas</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= $totalDevueltas ?></span>
            <span class="admin-stat-label">Devueltas</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num">$<?= number_format((float) $totalRecaudado, 0, ',', '.') ?></span>
            <span class="admin-stat-label">Recaudado (vigente)</span>
        </div>
    </section>

    <form method="GET" class="admin-filtros">
        <input
            type="text"
            name="q"
            placeholder="Buscar por código, usuario o película..."
            value="<?= htmlspecialchars($busqueda) ?>">

        <select name="estado">
            <option value="todas" <?= $estado === 'todas' ? 'selected' : '' ?>>Todos los estados</option>
            <option value="activa" <?= $estado === 'activa' ? 'selected' : '' ?>>Solo activas</option>
            <option value="devuelta" <?= $estado === 'devuelta' ? 'selected' : '' ?>>Solo devueltas</option>
        </select>

        <button type="submit">Filtrar</button>
        <?php if ($busqueda !== '' || $estado !== 'todas'): ?>
            <a href="admin-entradas.php" class="limpiar-genero">✕ Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="admin-tabla-wrap">
        <table class="admin-tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Película</th>
                    <th>Función</th>
                    <th>Sala</th>
                    <th>Butacas</th>
                    <th>Usuario</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Compra</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entradas)): ?>
                    <tr><td colspan="10">No se encontraron entradas con ese filtro.</td></tr>
                <?php endif; ?>

                <?php foreach ($entradas as $e): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($e['codigo']) ?></strong></td>
                        <td><?= htmlspecialchars($e['pelicula_titulo']) ?></td>
                        <td><?= !empty($e['fecha_funcion']) ? date('d/m/Y', strtotime($e['fecha_funcion'])) . ' · ' . substr($e['hora_funcion'], 0, 5) : '—' ?></td>
                        <td><?= htmlspecialchars((string) $e['sala']) ?></td>
                        <td><?= htmlspecialchars(implode(', ', normalizarAsientos($e['asientos']))) ?></td>
                        <td><?= htmlspecialchars($e['usuario'] ?? '—') ?></td>
                        <td>$<?= number_format($e['precio_total'], 0, ',', '.') ?></td>
                        <td>
                            <span class="admin-estado-pill <?= $e['estado'] === 'activa' ? 'estado-activo' : 'estado-inactivo' ?>">
                                <?= $e['estado'] === 'activa' ? 'Activa' : 'Devuelta' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($e['fecha_compra'])) ?></td>
                        <td>
                            <?php if (entradaEsDevolvible($e)): ?>
                                <a href="admin-devoluciones.php?codigo=<?= urlencode($e['codigo']) ?>">Devolver</a>
                            <?php elseif ($e['estado'] === 'activa'): ?>
                                <span class="admin-sin-devolucion">Función pasada</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<?php include '../includes/footer.php'; ?>
