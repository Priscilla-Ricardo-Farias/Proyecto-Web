<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

$peliculas = require 'datos.php';
require_once 'entradas.php';

$funcion = obtenerFuncionParaCompra($conexion, (int) ($_POST['funcion'] ?? 0));
$id = $funcion['pelicula_id'] ?? null;
$sala = isset($funcion['sala_id']) ? (string) $funcion['sala_id'] : null;
$peli = $peliculas[$id] ?? null;

$asientos = normalizarAsientos($_POST['asientos'] ?? null);

if ($funcion === null || $peli === null || empty($asientos)) {
    echo "No hay ninguna compra para confirmar.";
    exit;
}

// Último chequeo antes de guardar: que ninguna butaca se haya ocupado en el ínterin
$ocupados = obtenerAsientosOcupados($conexion, (int) $id, $sala, (int) $funcion['id']);
$conflicto = array_intersect($asientos, $ocupados);

if (!empty($conflicto)) {
    echo "Uno de los asientos que elegiste (" . htmlspecialchars(implode(', ', $conflicto)) . ") se acaba de ocupar. ";
    echo "<a href=\"asientos.php?funcion=" . (int) $funcion['id'] . "\">Volví a elegir</a>.";
    exit;
}

$precioEntrada = (int) $funcion['precio_entrada'];
$precioTotal = $precioEntrada * count($asientos);
$asientosTexto = implode(',', $asientos);
$codigoReserva = generarCodigoEntrada($conexion);
$peliculaId = (int) $id;
$salaInt = (int) $sala;
$funcionId = (int) $funcion['id'];
$fechaFuncion = $funcion['fecha'];
$horaFuncion = $funcion['hora_inicio'];
$usuario = $_SESSION['usuario'] ?? null;

$stmt = $conexion->prepare(
    "INSERT INTO entradas (pelicula_id, pelicula_titulo, sala, asientos, precio_total, codigo, usuario, funcion_id, fecha_funcion, hora_funcion)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'isisississ',
    $peliculaId,
    $peli['titulo'],
    $salaInt,
    $asientosTexto,
    $precioTotal,
    $codigoReserva,
    $usuario,
    $funcionId,
    $fechaFuncion,
    $horaFuncion
);
$stmt->execute();
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<main class="confirmacion-page">

    <section class="ticket-final">

        <div class="check-icono">✅</div>

        <h1>¡Compra confirmada!</h1>
        <p class="pago-aviso">Pago exitoso.</p>

        <div class="ticket-final-detalle">
            <h2><?= htmlspecialchars($peli['titulo']) ?></h2>

            <ul class="ticket-detalle">
                <li><span>Fecha</span><strong><?= htmlspecialchars(fechaLarga($funcion['fecha'])) ?></strong></li>
                <li><span>Horario</span><strong><?= horaCorta($funcion['hora_inicio']) ?> hs</strong></li>
                <li><span>Sala</span><strong><?= htmlspecialchars($funcion['sala_nombre']) ?></strong></li>
                <li><span><?= count($asientos) === 1 ? 'Butaca' : 'Butacas' ?></span><strong><?= htmlspecialchars(implode(', ', $asientos)) ?></strong></li>
                <li class="precio-total"><span>Total pagado</span><strong>$<?= number_format($precioTotal, 0, ',', '.') ?></strong></li>
            </ul>
        </div>

        <div class="codigo-devolucion">
            <p class="codigo-devolucion-label">Tu código de entrada</p>
            <p class="codigo-devolucion-valor"><?= htmlspecialchars($codigoReserva) ?></p>
            <p class="codigo-devolucion-nota">
                Presentá este código en la boletería junto con tu documento.
            </p>
        </div>

        <div class="ticket-final-acciones">
            <a href="cartelera.php" class="btn-volver">← Volver a la cartelera</a>
        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>