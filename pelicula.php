<?php
$peliculas = require 'datos.php';

$id = $_GET['id'] ?? 'matrix';
$peli = $peliculas[$id] ?? null;

if ($peli === null) {
    echo "Esa película no existe.";
    exit;
}

include 'includes/header.php';
?>

<main class="detalle-page">

    <section class="detalle-pelicula">

        <div class="detalle-imagen">

            <img
                src="<?= $peli['imagen'] ?>"
                alt="<?= htmlspecialchars($peli['titulo']) ?>">

        </div>

        <div class="detalle-info">

            <h1><?= htmlspecialchars($peli['titulo']) ?></h1>

            <div class="detalle-meta">

            <div class="detalle-meta">

    <span><?= $peli['anio'] ?></span>

    <span>•</span>

    <span><?= $peli['genero'] ?></span>

    <span>•</span>

    <span><?= $peli['duracion'] ?></span>

</div>

<p class="director">

    <strong>Director:</strong>

    <?= htmlspecialchars($peli['director']) ?>

</p>

                <span><?= htmlspecialchars($peli['anio']) ?></span>

                <span>•</span>

                <span><?= htmlspecialchars($peli['genero']) ?></span>

            </div>

            <?php if (!empty($peli['descripcion'])): ?>

                <p class="sinopsis">

                    <?= htmlspecialchars($peli['descripcion']) ?>

                </p>

            <?php else: ?>

                <p class="sinopsis">

                    No hay una descripción disponible para esta película.

                </p>

            <?php endif; ?>

            <a href="cartelera.php" class="btn-volver">

                ← Volver a la cartelera

            </a>

        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>