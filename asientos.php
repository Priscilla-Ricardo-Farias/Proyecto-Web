<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

$peliculas = require 'datos.php';
require_once 'entradas.php';

// Ahora se compra para una FUNCIÓN concreta (fecha + hora + sala)
$funcion = obtenerFuncionParaCompra($conexion, (int) ($_GET['funcion'] ?? 0));
$id = $funcion['pelicula_id'] ?? null;
$sala = isset($funcion['sala_id']) ? (string) $funcion['sala_id'] : null;
$peli = $peliculas[$id] ?? null;

if ($funcion === null || $peli === null) {
    echo "Esa función no está disponible. <a href=\"cartelera.php\">Volver a la cartelera</a>.";
    exit;
}

// Mapa fijo de la sala: 5 filas (A-E) x 10 asientos = 50 butacas
$filas = ['A', 'B', 'C', 'D', 'E'];
$asientosPorFila = 10;

$ocupados = obtenerAsientosOcupados($conexion, (int) $id, $sala, (int) $funcion['id']);
$precioEntrada = (int) $funcion['precio_entrada'];
?>

<?php include 'includes/header.php'; ?>

<main class="asientos-page">

    <nav class="paso-indicador">
        <span class="paso"><span class="paso-num">1</span> Función</span>
        <span class="paso-sep"></span>
        <span class="paso activo"><span class="paso-num">2</span> Butaca</span>
        <span class="paso-sep"></span>
        <span class="paso"><span class="paso-num">3</span> Pago</span>
    </nav>

    <section class="asientos-hero">
        <p class="salas-eyebrow"><?= htmlspecialchars($peli['titulo']) ?></p>
        <h1>Elegí tus butacas</h1>
        <p class="funcion-resumen">
            <span>📅 <?= htmlspecialchars(fechaLarga($funcion['fecha'])) ?></span>
            <span>🕒 <?= horaCorta($funcion['hora_inicio']) ?> hs</span>
            <span>🎬 <?= htmlspecialchars($funcion['sala_nombre']) ?></span>
        </p>
        <p class="salas-subtitulo">Tocá uno o más asientos disponibles. Podés deseleccionar tocándolos de nuevo.</p>
    </section>

    <div class="pantalla-wrap">
        <div class="pantalla"></div>
        <p class="pantalla-label">PANTALLA</p>
    </div>

    <form id="formAsiento" action="pago.php" method="POST" class="form-asientos">

        <input type="hidden" name="funcion" value="<?= (int) $funcion['id'] ?>">
        <input type="hidden" name="asientos" id="asientosElegidos" value="">

        <div class="sala-mapa">
            <?php foreach ($filas as $fila): ?>
                <div class="fila-asientos">
                    <span class="etiqueta-fila"><?= $fila ?></span>
                    <div class="butacas">
                        <?php for ($n = 1; $n <= $asientosPorFila; $n++):
                            $codigo = $fila . $n;
                            $estaOcupado = in_array($codigo, $ocupados, true);
                        ?>
                            <button
                                type="button"
                                class="butaca <?= $estaOcupado ? 'ocupada' : 'libre' ?>"
                                data-asiento="<?= $codigo ?>"
                                <?= $estaOcupado ? 'disabled' : '' ?>
                                title="Asiento <?= $codigo ?><?= $estaOcupado ? ' (ocupado)' : '' ?>">
                                <?= $n ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="leyenda">
            <span><i class="muestra libre"></i> Disponible</span>
            <span><i class="muestra seleccionada"></i> Seleccionado</span>
            <span><i class="muestra ocupada"></i> Ocupado</span>
        </div>

        <div class="barra-continuar">
            <p id="resumenAsiento">Ningún asiento seleccionado todavía.</p>
            <button type="submit" id="btnContinuar" class="btn-continuar-pago" disabled>
                Continuar a pagar →
            </button>
        </div>

    </form>

    <a href="seleccionar-funcion.php?id=<?= urlencode($id) ?>&dia=<?= $funcion['fecha'] ?>" class="btn-volver btn-volver-centrado">
        ← Elegir otro horario
    </a>

</main>

<script>
(function () {
    const precioEntrada = <?= (int) $precioEntrada ?>;
    const butacas = document.querySelectorAll('.butaca.libre');
    const inputAsientos = document.getElementById('asientosElegidos');
    const resumen = document.getElementById('resumenAsiento');
    const btnContinuar = document.getElementById('btnContinuar');

    const seleccionados = new Set();

    function actualizarResumen() {
        const lista = Array.from(seleccionados).sort();
        inputAsientos.value = lista.join(',');

        if (lista.length === 0) {
            resumen.textContent = 'Ningún asiento seleccionado todavía.';
            resumen.classList.remove('con-asiento');
            btnContinuar.disabled = true;
            btnContinuar.textContent = 'Continuar a pagar →';
            return;
        }

        const total = lista.length * precioEntrada;
        const totalTexto = total.toLocaleString('es-AR');
        const plural = lista.length === 1 ? 'asiento' : 'asientos';

        resumen.textContent = lista.length + ' ' + plural + ' seleccionados: ' + lista.join(', ') + ' · Total $' + totalTexto;
        resumen.classList.add('con-asiento');
        btnContinuar.disabled = false;
        btnContinuar.textContent = 'Continuar a pagar ($' + totalTexto + ') →';
    }

    butacas.forEach(function (butaca) {
        butaca.addEventListener('click', function () {
            const asiento = butaca.dataset.asiento;

            if (seleccionados.has(asiento)) {
                seleccionados.delete(asiento);
                butaca.classList.remove('seleccionada');
            } else {
                seleccionados.add(asiento);
                butaca.classList.add('seleccionada');
            }

            actualizarResumen();
        });
    });
})();
</script>

<?php include 'includes/footer.php'; ?>