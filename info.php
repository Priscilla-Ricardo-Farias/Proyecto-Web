<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$seccion = $_GET['seccion'] ?? 'ayuda';

switch ($seccion) {

    case 'ayuda':
        $titulo = 'Ayuda';
        $contenido = 'Esto es un sistema de ejemplo. Logueate con admin / 1234.';
        $imagen = 'https://picsum.photos/seed/ayuda/600/300';
        $clase = 'sec-ayuda';
        break;

    case 'contacto':
        $titulo = 'Contacto';
        $contenido = 'Escribinos a contacto@ejemplo.com';
        $imagen = 'https://picsum.photos/seed/contacto/600/300';
        $clase = 'sec-contacto';
        break;

    case 'nosotros':
        $titulo = 'Nosotros';
        $contenido = 'Somos un equipo de 7° año aprendiendo PHP.';
        $imagen = 'https://picsum.photos/seed/nosotros/600/300';
        $clase = 'sec-nosotros';
        break;

    default:
        $titulo = 'Inicio';
        $contenido = 'Bienvenido a nuestro sitio web.';
        $imagen = 'https://picsum.photos/seed/inicio/600/300';
        $clase = 'sec-inicio';
        break;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="<?= $clase ?>">

<?php include 'includes/header.php'; ?>

<h1><?= $titulo ?></h1>

<img src="<?= $imagen ?>" alt="<?= $titulo ?>">

<p><?= $contenido ?></p>

<?php include 'includes/footer.php'; ?>

</body>
</html>