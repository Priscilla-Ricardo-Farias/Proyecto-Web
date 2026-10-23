<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

require 'conexion.php';
require_once 'eventos-funciones.php';

$eventos = obtenerEventosVigentes($conexion, 50);

include 'includes/header.php';
?>

<main class="eventos-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Agenda especial</p>
        <h1>Eventos</h1>
        <p class="salas-subtitulo">Reestrenos, funciones de último adiós y otras sorpresas de la cartelera.</p>
    </section>

    <?php if (empty($eventos)): ?>

        <div class="mis-entradas-vacio">
            <p>Por ahora no hay eventos especiales programados.</p>
            <a href="cartelera.php" class="btn-elegir-sala">Ver cartelera</a>
        </div>

    <?php else: ?>

        <div class="eventos-lista">
            <?php foreach ($eventos as $evento): ?>
                <article class="evento-card evento-card-<?= htmlspecialchars($evento['tipo']) ?>">

                    <div class="evento-card-cabecera">
                        <span class="evento-chip-tag"><?= htmlspecialchars(etiquetaEvento($evento['tipo'])) ?></span>
                        <?php if ($evento['fecha_inicio'] || $evento['fecha_fin']): ?>
                            <span class="evento-fechas">
                                <?= $evento['fecha_inicio'] ? date('d/m/Y', strtotime($evento['fecha_inicio'])) : 'Ahora' ?>
                                <?php if ($evento['fecha_fin']): ?>
                                    &rarr; <?= date('d/m/Y', strtotime($evento['fecha_fin'])) ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h2><?= htmlspecialchars($evento['titulo']) ?></h2>

                    <?php if (!empty($evento['peliculas_lista'])): ?>
                        <p class="evento-pelicula">
                            🎬 <?= htmlspecialchars(implode(' · ', $evento['peliculas_lista'])) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($evento['descripcion'])): ?>
                        <p class="evento-descripcion"><?= htmlspecialchars($evento['descripcion']) ?></p>
                    <?php endif; ?>

                    <?php if (count($evento['peliculas_lista'] ?? []) === 1 && $evento['pelicula_id']): ?>
                        <a href="pelicula.php?id=<?= urlencode($evento['pelicula_id']) ?>" class="btn-pelicula">
                            Ver película
                        </a>
                    <?php elseif (count($evento['peliculas_lista'] ?? []) > 1): ?>
                        <div class="evento-peliculas-links">
                            <?php
                            // Volvemos a traer los ids para poder linkear cada título del evento
                            $idsEvento = obtenerPeliculasDeEvento($conexion, (int) $evento['id']);
                            ?>
                            <?php foreach ($idsEvento as $pe): ?>
                                <a href="pelicula.php?id=<?= urlencode($pe['id']) ?>" class="btn-pelicula btn-pelicula-chico">
                                    <?= htmlspecialchars($pe['nombre']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>
