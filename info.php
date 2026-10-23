<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

require 'conexion.php';
require_once 'entradas.php';

$peliculas = require 'datos.php';

$stmt = $conexion->prepare(
    "SELECT * FROM entradas WHERE usuario = ? ORDER BY fecha_compra DESC"
);
$stmt->bind_param('s', $_SESSION['usuario']);
$stmt->execute();
$misEntradas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Resultado de una devolución recién pedida (lo manda devolver-entrada.php)
$codigoDevuelto = strtoupper(trim((string) ($_GET['codigo'] ?? '')));
$mensajesDevolucion = [
    'ok'             => ['exito', '✅', 'Entrada devuelta', 'Devolvimos la entrada ' . $codigoDevuelto . ' y las butacas quedaron libres. Se reintegra el total de la compra.'],
    'funcion_pasada' => ['error', '✕', 'La función ya pasó', 'La entrada ' . $codigoDevuelto . ' no se puede devolver porque la función ya empezó.'],
    'ya_devuelta'    => ['advertencia', '⚠️', 'Esa entrada ya fue devuelta', 'La entrada ' . $codigoDevuelto . ' ya se había devuelto antes.'],
    'codigo_incorrecto' => ['error', '✕', 'Código incorrecto', 'El código ingresado no coincide con el de la entrada. Revisá el código que te dieron al comprar.'],
    'no_existe'      => ['error', '✕', 'No encontramos la entrada', 'Esa entrada no existe o no es tuya.'],
];
$mensajeDevolucion = null;
$claveDevolucion = (string) ($_GET['devolucion'] ?? '');
if (isset($mensajesDevolucion[$claveDevolucion])) {
    [$clase, $icono, $titulo, $texto] = $mensajesDevolucion[$claveDevolucion];
    $mensajeDevolucion = compact('clase', 'icono', 'titulo', 'texto');
}

include 'includes/header.php';
?>

<main class="mis-entradas-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Tu cuenta</p>
        <h1>Mis entradas</h1>
        <p class="salas-subtitulo">Estas son las entradas que compraste con tu usuario.</p>
    </section>

    <?php if ($mensajeDevolucion !== null): ?>
        <div class="devolucion-resultado <?= $mensajeDevolucion['clase'] ?>">
            <div class="devolucion-icono"><?= $mensajeDevolucion['icono'] ?></div>
            <h2><?= htmlspecialchars($mensajeDevolucion['titulo']) ?></h2>
            <p><?= htmlspecialchars($mensajeDevolucion['texto']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($misEntradas)): ?>

        <div class="mis-entradas-vacio">
            <p>Todavía no compraste ninguna entrada.</p>
            <a href="cartelera.php" class="btn-elegir-sala">Ver cartelera</a>
        </div>

    <?php else: ?>

        <div class="mis-entradas-lista">
            <?php foreach ($misEntradas as $entrada):
                $asientos = normalizarAsientos($entrada['asientos']);
                $imagen = $peliculas[$entrada['pelicula_id']]['imagen'] ?? null;
                $esActiva = $entrada['estado'] === 'activa';
            ?>
                <article class="entrada-card <?= $esActiva ? '' : 'devuelta' ?>">

                    <?php if ($imagen): ?>
                        <div class="entrada-poster">
                            <img src="<?= htmlspecialchars($imagen) ?>" alt="<?= htmlspecialchars($entrada['pelicula_titulo']) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="entrada-info">

                        <div class="entrada-info-cabecera">
                            <h2><?= htmlspecialchars($entrada['pelicula_titulo']) ?></h2>
                            <span class="entrada-estado <?= $esActiva ? 'activa' : 'devuelta' ?>">
                                <?= $esActiva ? 'Activa' : 'Devuelta' ?>
                            </span>
                        </div>

                        <ul class="entrada-datos">
                            <?php if (!empty($entrada['fecha_funcion'])): ?>
                                <li><span>Función</span><strong><?= date('d/m/Y', strtotime($entrada['fecha_funcion'])) ?> · <?= horaCorta($entrada['hora_funcion']) ?> hs</strong></li>
                            <?php endif; ?>
                            <li><span>Sala</span><strong><?= htmlspecialchars($entrada['sala']) ?></strong></li>
                            <li><span><?= count($asientos) === 1 ? 'Butaca' : 'Butacas' ?></span><strong><?= htmlspecialchars(implode(', ', $asientos)) ?></strong></li>
                            <li><span>Total</span><strong>$<?= number_format($entrada['precio_total'], 0, ',', '.') ?></strong></li>
                            <li><span>Código</span><strong><?= htmlspecialchars($entrada['codigo']) ?></strong></li>
                            <li><span>Comprada</span><strong><?= date('d/m/Y H:i', strtotime($entrada['fecha_compra'])) ?></strong></li>
                        </ul>

                        <?php if ($esActiva): ?>
                            <?php if (entradaEsDevolvible($entrada)): ?>
                                <form action="devolver-entrada.php" method="POST" class="entrada-acciones">
                                    <input type="hidden" name="entrada_id" value="<?= (int) $entrada['id'] ?>">
                                    <input
                                        type="text"
                                        name="codigo"
                                        class="input-codigo-devolucion"
                                        placeholder="Código de tu entrada"
                                        maxlength="10"
                                        autocomplete="off"
                                        style="text-transform:uppercase"
                                        required>
                                    <button type="submit" class="btn-devolver-entrada">Devolver entrada</button>
                                    <span class="entrada-nota-devolucion">Ingresá el código que te dieron al comprar. Podés devolverla hasta que empiece la función.</span>
                                </form>
                            <?php else: ?>
                                <p class="entrada-nota-devolucion cerrada">La función ya pasó: esta entrada ya no admite devolución.</p>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>

                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>