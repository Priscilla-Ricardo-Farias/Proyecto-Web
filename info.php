<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php
  session_start();
  if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
  }
  $seccion = $_GET['seccion'] ?? 'ayuda';
?>
<?php include 'includes/header.php'; ?>

<?php if ($seccion == 'ayuda'): ?>
  <h1>Ayuda</h1>
  <p>Esto es un sistema de ejemplo. Logueate con admin / 1234.</p>
<?php elseif ($seccion == 'contacto'): ?>
  <h1>Contacto</h1>
  <p>Escribinos a contacto@ejemplo.com</p>
<?php elseif ($seccion == 'nosotros'): ?>
  <h1>Nosotros</h1>
  <p>Somos un equipo de 7° año aprendiendo PHP.</p>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
</body>
</html>