<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

$peliculas = require_once 'datos.php';
require_once 'entradas.php';
require_once 'salas-funciones.php';

$id = $_GET['id'] ?? null;
$peli = $peliculas[$id] ?? null;

if ($peli === null || empty($peli['activa'])) {
    echo "Esa película no existe.";
    exit;
}

// Si la película todavía no tiene horarios cargados, le armamos una programación
// por defecto (próximos 7 días) para que se pueda comprar.
sembrarFuncionesPorDefecto($conexion, (int) $id, (int) $peli['duracion']);

$funciones = obtenerFuncionesDePelicula($conexion, (int) $id);

// Agrupamos por día: ['2026-10-16' => [funcion, funcion, ...], ...]
$porDia = [];
foreach ($funciones as $f) {
    $porDia[$f['fecha']][] = $f;
}

// Día elegido (por defecto, el primero con funciones)
$diaElegido = $_GET['dia'] ?? '';
if (!isset($porDia[$diaElegido])) {
    $diaElegido = array_key_first($porDia);
}

include 'includes/header.php';
?>

<main class="salas-page funciones-page">

    <nav class="paso-indicador">
        <span class="paso activo"><span class="paso-num">1</span> Función</span>
        <span class="paso-sep"></span>
        <span class="paso"><span class="paso-num">2</span> Butaca</span>
        <span class="paso-sep"></span>
        <span class="paso"><span class="paso-num">3</span> Pago</span>
    </nav>

    <section class="salas-hero">

        <p class="salas-eyebrow">Comprar entrada para</p>
        <h1><?= htmlspecialchars($peli['titulo']) ?></h1>
        <p class="salas-subtitulo">Elegí el día y el horario de la función.</p>

    </section>

    <?php if (empty($porDia)): ?>

        <div class="mis-entradas-vacio">
            <p>Por ahora no hay funciones disponibles para esta película.</p>
            <a href="pelicula.php?id=<?= urlencode($id) ?>" class="btn-elegir-sala">Volver a la ficha</a>
        </div>

    <?php else: ?>

        <nav class="dias-barra" aria-label="Elegir día">
            <?php foreach ($porDia as $fecha => $lista):
                $esHoy = $fecha === date('Y-m-d');
            ?>
                <a href="?id=<?= urlencode($id) ?>&dia=<?= $fecha ?>"
                   class="dia-chip <?= $fecha === $diaElegido ? 'activo' : '' ?>">
                    <span class="dia-nombre"><?= $esHoy ? 'Hoy' : nombreDiaSemana($fecha, true) ?></span>
                    <span class="dia-numero"><?= (int) date('j', strtotime($fecha)) ?></span>
                    <span class="dia-mes"><?= mb_substr(nombreMes($fecha), 0, 3) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <p class="funciones-fecha"><?= htmlspecialchars(fechaLarga($diaElegido)) ?></p>

        <section class="funciones-lista">
            <?php foreach ($porDia[$diaElegido] as $f):
                $ocupados = obtenerAsientosOcupados($conexion, (int) $id, (string) $f['sala_id'], (int) $f['id']);
                $libres = max(0, BUTACAS_POR_SALA - count($ocupados));
            ?>
                <article class="funcion-card <?= $libres === 0 ? 'agotada' : '' ?>">

                    <div class="funcion-hora">
                        <?= horaCorta($f['hora_inicio']) ?>
                        <small>hasta <?= horaCorta($f['hora_fin']) ?></small>
                    </div>

                    <div class="funcion-datos">
                        <h3>
                            <?= htmlspecialchars($f['sala_nombre']) ?>
                            <span class="funcion-tipo"><?= htmlspecialchars(etiquetaTipoSala($f['sala_tipo'])) ?></span>
                        </h3>
                        <p>
                            $<?= number_format($f['precio_entrada'], 0, ',', '.') ?> por entrada ·
                            <?= $libres === 0 ? 'sin butacas libres' : $libres . ' butacas libres' ?>
                        </p>
                    </div>

                    <?php if ($libres > 0): ?>
                        <a href="asientos.php?funcion=<?= (int) $f['id'] ?>" class="btn-elegir-sala">Elegir</a>
                    <?php else: ?>
                        <span class="funcion-agotada">Agotada</span>
                    <?php endif; ?>

                </article>
            <?php endforeach; ?>
        </section>

    <?php endif; ?>

    <a href="pelicula.php?id=<?= urlencode($id) ?>" class="btn-volver btn-volver-centrado">
        ← Volver a la ficha
    </a>

</main>

<?php include 'includes/footer.php'; ?>
