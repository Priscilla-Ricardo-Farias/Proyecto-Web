<?php
$peliculas = require 'datos.php';
?>

<?php include 'includes/header.php'; ?>

<div class="cartelera">
    <?php foreach ($peliculas as $id => $peli): ?>
        <article class="pelicula">
            <img src="<?= $peli['imagen'] ?>" alt="<?= $peli['titulo'] ?>">
            <h3><?= $peli['titulo'] ?></h3>
            <p><?= $peli['anio'] ?></p>
            <a href="pelicula.php?id=<?= $id ?>">Ver ficha</a>
        </article>
    <?php endforeach; ?>
</div>
<?php include 'includes/footer.php'; ?>