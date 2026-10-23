<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

$peliculas = require_once 'datos.php';
require_once 'entradas.php';

$funcion = obtenerFuncionParaCompra($conexion, (int) ($_POST['funcion'] ?? 0));
$id = $funcion['pelicula_id'] ?? null;
$sala = isset($funcion['sala_id']) ? (string) $funcion['sala_id'] : null;
$peli = $peliculas[$id] ?? null;

$asientos = normalizarAsientos($_POST['asientos'] ?? null);

if ($funcion === null || $peli === null || empty($asientos)) {
    echo "No hay ninguna compra en curso. Elegí una película para empezar.";
    exit;
}

// Volvemos a chequear contra la base por si alguien más agarró el mismo asiento mientras elegías
$ocupados = obtenerAsientosOcupados($conexion, (int) $id, $sala, (int) $funcion['id']);
$conflicto = array_intersect($asientos, $ocupados);

if (!empty($conflicto)) {
    echo "Uno de los asientos que elegiste (" . htmlspecialchars(implode(', ', $conflicto)) . ") se acaba de ocupar. ";
    echo "<a href=\"asientos.php?funcion=" . (int) $funcion['id'] . "\">Volví a elegir</a>.";
    exit;
}

$precioEntrada = (int) $funcion['precio_entrada'];
$precioTotal = $precioEntrada * count($asientos);
$asientosTexto = implode(', ', $asientos);
?>

<?php include 'includes/header.php'; ?>

<main class="pago-page">

    <nav class="paso-indicador">
        <span class="paso"><span class="paso-num">1</span> Función</span>
        <span class="paso-sep"></span>
        <span class="paso"><span class="paso-num">2</span> Butaca</span>
        <span class="paso-sep"></span>
        <span class="paso activo"><span class="paso-num">3</span> Pago</span>
    </nav>

    <h1>Confirmá y pagá</h1>
    <p class="salas-subtitulo">Revisá los datos de tu entrada antes de pagar.</p>

    <section class="pago-container">

        <div class="ticket-resumen">

            <p class="salas-eyebrow">Resumen de tu compra</p>
            <h2><?= htmlspecialchars($peli['titulo']) ?></h2>

            <ul class="ticket-detalle">
                <li><span>Fecha</span><strong><?= htmlspecialchars(fechaLarga($funcion['fecha'])) ?></strong></li>
                <li><span>Horario</span><strong><?= horaCorta($funcion['hora_inicio']) ?> hs</strong></li>
                <li><span>Sala</span><strong><?= htmlspecialchars($funcion['sala_nombre']) ?></strong></li>
                <li><span><?= count($asientos) === 1 ? 'Butaca' : 'Butacas' ?></span><strong><?= htmlspecialchars($asientosTexto) ?></strong></li>
                <li><span>Cantidad</span><strong><?= count($asientos) ?></strong></li>
                <li class="precio-total"><span>Total</span><strong>$<?= number_format($precioTotal, 0, ',', '.') ?></strong></li>
            </ul>

        </div>

        <div class="pago-form-wrap">

            <h2 class="pago-form-titulo">Datos de pago</h2>
            <p class="pago-aviso">Podés devolver tu entrada desde "Mis entradas" hasta antes de que empiece la función. Una vez iniciada, ya no se puede devolver.</p>

            <form action="compra-exitosa.php" method="POST">

                <input type="hidden" name="funcion" value="<?= (int) $funcion['id'] ?>">
                <input type="hidden" name="asientos" value="<?= htmlspecialchars($asientosTexto) ?>">

                <label>
                    Nombre en la tarjeta
                    <input type="text" name="nombre_tarjeta" placeholder="Como figura en la tarjeta" required>
                </label>

                <label>
                    Número de tarjeta
                    <input
                        type="text"
                        name="numero_tarjeta"
                        placeholder="0000 0000 0000 0000"
                        maxlength="19"
                        pattern="[0-9 ]{16,19}"
                        required>
                </label>

                <div class="pago-fila-doble">
                    <label>
                        Vencimiento
                        <input type="text" name="vencimiento" placeholder="MM/AA" maxlength="5" pattern="\d{2}/\d{2}" required>
                    </label>

                    <label>
                        CVV
                        <input type="text" name="cvv" placeholder="123" maxlength="4" pattern="\d{3,4}" required>
                    </label>
                </div>

                <button type="submit">Pagar $<?= number_format($precioTotal, 0, ',', '.') ?></button>

            </form>

        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>