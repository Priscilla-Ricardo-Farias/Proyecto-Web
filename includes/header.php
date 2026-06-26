<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi sistema</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php">Inicio</a>

    <div class="dropdown">
        <a href="#">Ayuda ▾</a>

        <div class="dropdown-menu">
            <a href="info.php?seccion=ayuda">Ayuda</a>
            <a href="info.php?seccion=contacto">Contacto</a>
            <a href="info.php?seccion=nosotros">Nosotros</a>
        </div>
    </div>

    <a href="logout.php">Salir</a>
    <a href="cartelera.php">Cartelera</a>
</nav>
</body>
</html>