<?php
session_start();
require '../includes/auth.php';

requiereAdmin();

require_once '../conexion.php';
require_once '../entradas.php';

$resultado = null;
$entrada = null;
$codigoPrecargado = strtoupper(trim($_GET['codigo'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));

    if ($codigo === '') {
        $resultado = 'vacio';
    } else {
        // Se verifica contra la base real: solo existe si es un código de reserva verdadero.
        $stmt = $conexion->prepare("SELECT * FROM entradas WHERE codigo = ?");
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila === null) {
            $resultado = 'no_existe';
        } elseif ($fila['estado'] === 'devuelta') {
            $resultado = 'ya_devuelta';
            $entrada = $fila;
        } elseif (funcionDeEntradaYaEmpezo($fila)) {
            // La función ya empezó o ya pasó: no se puede devolver
            $resultado = 'funcion_pasada';
            $entrada = $fila;
        } else {
            $stmtUpdate = $conexion->prepare(
                "UPDATE entradas SET estado = 'devuelta', fecha_devolucion = NOW() WHERE codigo = ?"
            );
            $stmtUpdate->bind_param('s', $codigo);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            $resultado = 'devuelta';
            $entrada = $fila;
        }
    }
}

// Últimas devoluciones procesadas, para tener trazabilidad
$ultimasDevoluciones = mysqli_query(
    $conexion,
    "SELECT * FROM entradas WHERE estado = 'devuelta' ORDER BY fecha_devolucion DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Gestión de entradas</p>
        <h1>Devolver una entrada</h1>
        <p class="salas-subtitulo">Ingresá el código de reserva del cliente para verificarlo y devolver la entrada. Solo se puede devolver antes de que empiece la función.</p>
    </section>

    <?php if ($resultado === 'devuelta'): ?>
        <div class="devolucion-resultado exito">
            <div class="devolucion-icono">✅</div>
            <h2>Entrada devuelta</h2>
            <p>Se liberaron <?= count(normalizarAsientos($entrada['asientos'])) === 1 ? 'la butaca' : 'las butacas' ?>
                <strong><?= htmlspecialchars($entrada['asientos']) ?></strong>
                de <strong><?= htmlspecialchars($entrada['pelicula_titulo']) ?></strong> (Sala <?= htmlspecialchars($entrada['sala']) ?>),
                comprada por <strong><?= htmlspecialchars($entrada['usuario'] ?? 'usuario desconocido') ?></strong>.
            </p>
            <p class="devolucion-monto">Se reintegran $<?= number_format($entrada['precio_total'], 0, ',', '.') ?>.</p>
        </div>
    <?php elseif ($resultado === 'ya_devuelta'): ?>
        <div class="devolucion-resultado advertencia">
            <div class="devolucion-icono">⚠️</div>
            <h2>Esta entrada ya fue devuelta</h2>
            <p>El código <strong><?= htmlspecialchars($entrada['codigo']) ?></strong> ya se había devuelto el
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($entrada['fecha_devolucion']))) ?>.</p>
        </div>
    <?php elseif ($resultado === 'funcion_pasada'): ?>
        <div class="devolucion-resultado error">
            <div class="devolucion-icono">✕</div>
            <h2>La función ya pasó</h2>
            <p>La entrada <strong><?= htmlspecialchars($entrada['codigo']) ?></strong> era para
                <strong><?= htmlspecialchars($entrada['pelicula_titulo']) ?></strong> el
                <?= htmlspecialchars(date('d/m/Y', strtotime($entrada['fecha_funcion']))) ?> a las
                <?= htmlspecialchars(horaCorta($entrada['hora_funcion'])) ?> hs.
                Una vez que empieza la función, la entrada ya no se puede devolver.</p>
        </div>
    <?php elseif ($resultado === 'no_existe'): ?>
        <div class="devolucion-resultado error">
            <div class="devolucion-icono">✕</div>
            <h2>Código no encontrado</h2>
            <p>Ese código no corresponde a ninguna entrada real del sistema.</p>
        </div>
    <?php elseif ($resultado === 'vacio'): ?>
        <div class="devolucion-resultado error">
            <div class="devolucion-icono">✕</div>
            <h2>Falta el código</h2>
            <p>Ingresá el código de la entrada para poder devolverla.</p>
        </div>
    <?php endif; ?>

    <form action="admin-devoluciones.php" method="POST" class="form-devolucion">
        <label>
            Código de la entrada
            <input
                type="text"
                name="codigo"
                placeholder="Ej: 4F92A1"
                maxlength="10"
                style="text-transform:uppercase"
                autocomplete="off"
                value="<?= htmlspecialchars($codigoPrecargado) ?>"
                required>
        </label>
        <button type="submit">Verificar y devolver</button>
    </form>

    <section class="admin-subseccion">
        <h2>Últimas devoluciones</h2>
        <div class="admin-tabla-wrap">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Película</th>
                        <th>Usuario</th>
                        <th>Total</th>
                        <th>Fecha de devolución</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimasDevoluciones)): ?>
                        <tr><td colspan="5">Todavía no se procesó ninguna devolución.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($ultimasDevoluciones as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['codigo']) ?></strong></td>
                            <td><?= htmlspecialchars($d['pelicula_titulo']) ?></td>
                            <td><?= htmlspecialchars($d['usuario'] ?? '—') ?></td>
                            <td>$<?= number_format($d['precio_total'], 0, ',', '.') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($d['fecha_devolucion'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<?php include '../includes/footer.php'; ?>
