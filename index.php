<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

<h1>Iniciar sesión</h1>

<form action="procesar-login.php" method="POST">
  <label>Usuario:
    <input type="text" name="usuario">
  </label>
  <label>Contraseña:
    <input type="password" name="clave">
  </label>
  <button type="submit">Entrar</button>
</form>

<?php include 'includes/footer.php'; ?>
</body>
</html>
