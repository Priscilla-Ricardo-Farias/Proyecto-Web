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
        break;

    case 'contacto':
        $titulo = 'Contacto';
        $contenido = 'Escribinos a contacto@ejemplo.com';
        $imagen = 'https://picsum.photos/seed/contacto/600/300';
        break;

    case 'nosotros':
        $titulo = 'Nosotros';
        $contenido = 'Somos un equipo de 7° año aprendiendo PHP.';
        $imagen = 'https://picsum.photos/seed/nosotros/600/300';
        break;

    default:
        $titulo = 'Inicio';
        $contenido = 'Bienvenido a nuestro sitio web.';
        $imagen = 'https://picsum.photos/seed/inicio/600/300';
        break;
}

include 'includes/header.php';
?>

<main style="text-align:center; padding:40px;">

    <h1><?= $titulo ?></h1>

    <img src="<?= $imagen ?>" style="max-width:500px; border-radius:15px;">

    <p style="margin-top:20px; color:#94a3b8;">
        <?= $contenido ?>
    </p>

</main>

<?php include 'includes/footer.php'; ?>