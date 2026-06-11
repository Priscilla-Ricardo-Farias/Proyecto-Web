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
?>
<?php include 'includes/header.php'; ?>

<h1>Hola, <?= $_SESSION['usuario'] ?></h1>
<p>Estás dentro del sistema.</p>

<?php include 'includes/footer.php'; ?>
</body>
</html>