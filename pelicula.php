<?php include 'includes/header.php'; ?>
<?php
  $peliculas = require 'datos.php';
  $id   = $_GET['id'] ?? 'matrix';
  $peli = $peliculas[$id] ?? null;

  if ($peli === null) {
    echo "Esa pelicula no existe.";
    exit;
  }
  ?>

  <h1><?= $peli['titulo']?></h1>
  <p> Año: <?= $peli['anio'] ?> - <?= $peli['genero'] ?></p>

  <?php include 'includes/footer.php'; ?>