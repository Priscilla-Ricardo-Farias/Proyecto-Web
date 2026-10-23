<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

include 'includes/header.php';
?>

<main class="home-cine">

    <div class="claqueta" aria-hidden="true">
        <div class="claqueta-palo"></div>
        <div class="claqueta-cuerpo">
            <div><span>Escena</span><b>1</b></div>
            <div><span>Toma</span><b>1</b></div>
            <div><span>Función</span><b>Hoy</b></div>
        </div>
    </div>

    <p class="home-eyebrow">Luces, cámara, acción</p>

    <h1>Hola, <?= htmlspecialchars($_SESSION['usuario']) ?></h1>

    <p class="home-lead">
        Bienvenido/a a nuestra página. <br>
        Somos un espacio dedicado a los amantes del cine clásico, donde podés descubrir y disfrutar películas de distintas épocas que dejaron su huella en la historia del cine. <br>
        ¡Esperamos que disfrutes de la experiencia y encuentres tu próxima película favorita!
    </p>

    <a href="cartelera.php" class="btn-pelicula home-cta">🎟️ Ver la cartelera</a>

    <section class="carteles">

        <a href="cartelera.php" class="cartel">
            <span class="cartel-icono">🎬</span>
            <h2>Cartelera</h2>
            <p>Películas clásicas de todos los géneros, listas para la gran pantalla.</p>
            <span class="cartel-sello">Ver películas</span>
        </a>

        <a href="eventos.php" class="cartel">
            <span class="cartel-icono">🍿</span>
            <h2>Eventos</h2>
            <p>Reestrenos y funciones especiales. ¡No te los pierdas!</p>
            <span class="cartel-sello">Ver eventos</span>
        </a>

        <a href="info.php" class="cartel">
            <span class="cartel-icono">🎟️</span>
            <h2>Mis entradas</h2>
            <p>Tus entradas, funciones y códigos en un solo lugar.</p>
            <span class="cartel-sello">Mis entradas</span>
        </a>

    </section>

</main>

<?php include 'includes/footer.php'; ?>