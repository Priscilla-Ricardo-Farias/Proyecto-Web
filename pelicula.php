<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

$peliculas = require 'datos.php';

$id = $_GET['id'] ?? null;
$peli = $peliculas[$id] ?? null;

if ($peli === null || empty($peli['activa'])) {
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

            <?php if (!empty($peli['descripcion'])): ?>

                <p class="sinopsis">

                    <?= htmlspecialchars($peli['descripcion']) ?>

                </p>

            <?php else: ?>

                <p class="sinopsis">

                    No hay una descripción disponible para esta película.

                </p>

            <?php endif; ?>

            <div class="detalle-acciones">

                <a href="seleccionar-funcion.php?id=<?= urlencode($id) ?>" class="btn-comprar">

                    🎟️ Comprar entrada

                </a>

                <a href="cartelera.php" class="btn-volver">

                    ← Volver a la cartelera

                </a>

            </div>

        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>