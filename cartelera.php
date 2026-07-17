<?php
$peliculas = require 'datos.php';

$busqueda = $_GET['id'] ?? '';
$resultado = $peliculas[$busqueda] ?? null;
?>

<?php include 'includes/header.php'; ?>

<main class="cartelera-page">

    <section class="hero">
        <h1>Cartelera</h1>
        <p>Descubre todas las películas disponibles en nuestro catálogo.</p>

        <form method="GET">
            <input
                type="text"
                name="id"
                placeholder="Ingrese el titulo de la película"
                value="<?= htmlspecialchars($busqueda) ?>"
            >
            <button type="submit">Buscar</button>
        </form>
    </section>

    <section class="cartelera">

        <?php if ($busqueda !== ''): ?>

            <?php if ($resultado === null): ?>

                <p>No esta en nuestra cartelera. </p>

            <?php else: ?>

                <article class="pelicula">

                    <div class="poster">
                        <img src="<?= $resultado['imagen'] ?>" alt="<?= $resultado['titulo'] ?>">
                    </div>

                    <div class="pelicula-info">
                        <h3><?= $resultado['titulo'] ?></h3>
                        <span class="anio"><?= $resultado['anio'] ?></span>

                        <a href="pelicula.php?id=<?= $busqueda ?>" class="btn-pelicula">
                            Ver ficha
                        </a>
                    </div>

                </article>

            <?php endif; ?>

        <?php else: ?>

            <?php foreach ($peliculas as $id => $peli): ?>

                <article class="pelicula">

                    <div class="poster">
                        <img src="<?= $peli['imagen'] ?>" alt="<?= $peli['titulo'] ?>">
                    </div>

                    <div class="pelicula-info">
                        <h3><?= $peli['titulo'] ?></h3>
                        <span class="anio"><?= $peli['anio'] ?></span>

                        <a href="pelicula.php?id=<?= $id ?>" class="btn-pelicula">
                            Ver ficha
                        </a>
                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<?php include 'includes/footer.php'; ?>