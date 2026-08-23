<?php

$peliculas = require 'datos.php';

$busqueda = trim($_GET['id'] ?? '');
// Ahora genero es un array (puede venir vacío, o con uno o varios géneros)
$generosSeleccionados = $_GET['genero'] ?? [];
if (!is_array($generosSeleccionados)) {
    $generosSeleccionados = [$generosSeleccionados]; // por si acaso llega como string suelto
}
$generosSeleccionados = array_map('trim', $generosSeleccionados);

// Separar géneros compuestos (ej: "Animación y Musical") en géneros individuales
function separarGeneros(string $genero): array {
    $genero = trim($genero);
    $genero = str_replace(' y ', ',', $genero);
    $partes = explode(',', $genero);
    return array_map('trim', $partes);
}

// Lista de géneros únicos para el <select>
$generosDisponibles = [];
foreach ($peliculas as $pelicula) {
    foreach (separarGeneros($pelicula['genero']) as $g) {
        if ($g !== '' && !in_array($g, $generosDisponibles)) {
            $generosDisponibles[] = $g;
        }
    }
}
sort($generosDisponibles);

// Filtrado: título y género se evalúan por separado, pero ambos aplican sobre el mismo catálogo
$resultados = [];
foreach ($peliculas as $clave => $pelicula) {
    $coincideTitulo = $busqueda === '' || str_contains(mb_strtolower($pelicula['titulo']), mb_strtolower($busqueda));

    $coincideGenero = empty($generosSeleccionados);
    if (!$coincideGenero) {
        foreach ($generosSeleccionados as $gSel) {
            if (str_contains(mb_strtolower($pelicula['genero']), mb_strtolower($gSel))) {
                $coincideGenero = true;
                break;
            }
        }
    }

    if ($coincideTitulo && $coincideGenero) {
        $pelicula['id'] = $clave;
        $resultados[] = $pelicula;
    }
}
?>

<?php include 'includes/header.php'; ?>

<main class="cartelera-page">

    <section class="hero">
    <?php $totalGeneros = count($generosDisponibles); ?>
<div class="genero-bubbles">
    <?php foreach ($generosDisponibles as $i => $g):
        $lado = $i % 2 === 0 ? 'izq' : 'der';
        $indiceEnLado = intdiv($i, 2);
        $totalEnLado = intdiv($totalGeneros, 2) + ($totalGeneros % 2);

        $top = $totalEnLado > 1
            ? 4 + ($indiceEnLado * (90 / ($totalEnLado - 1)))
            : 45;

        $horizontal = 2 + ($indiceEnLado % 3) * 5;

        $estiloPos = $lado === 'izq'
            ? "top:{$top}%; left:{$horizontal}%;"
            : "top:{$top}%; right:{$horizontal}%;";

        $delay = $i * 0.4;

        $estaSeleccionado = in_array($g, $generosSeleccionados);

        // Armamos el array resultante si el usuario clickea esta burbuja (toggle)
        if ($estaSeleccionado) {
            $nuevosGeneros = array_diff($generosSeleccionados, [$g]); // la sacamos
        } else {
            $nuevosGeneros = array_merge($generosSeleccionados, [$g]); // la agregamos
        }

        // Armamos la URL con genero[]=X&genero[]=Y ...
        $paramsGenero = '';
        foreach ($nuevosGeneros as $ng) {
            $paramsGenero .= '&genero[]=' . urlencode($ng);
        }
    ?>
        <a href="?id=<?= urlencode($busqueda) ?><?= $paramsGenero ?>"
           class="bubble <?= $estaSeleccionado ? 'activa' : '' ?>"
           style="<?= $estiloPos ?> animation-delay: <?= $delay ?>s;">
            <?= htmlspecialchars($g) ?>
        </a>
    <?php endforeach; ?>
</div>

    <h1>Cartelera</h1>
    <p>Descubre todas las películas disponibles en nuestro catálogo.</p>

    <form method="GET">
        <input
            type="text"
            name="id"
            placeholder="Ingrese el titulo de la película"
            value="<?= htmlspecialchars($busqueda) ?>"
        >

    <?php foreach ($generosSeleccionados as $gSel): ?>
        <input type="hidden" name="genero[]" value="<?= htmlspecialchars($gSel) ?>">
    <?php endforeach; ?>
        <button type="submit">Buscar</button>
    <?php if (!empty($generosSeleccionados)): ?>
        <a href="?id=<?= urlencode($busqueda) ?>" class="limpiar-genero">✕ Quitar filtros (<?= count($generosSeleccionados) ?>)</a>
    <?php endif; ?>
  </form>
</section>

<section class="cartelera">
    <?php if (empty($resultados)): ?>
        <p>No esta en nuestra cartelera.</p>
    <?php else: ?>
        <?php foreach ($resultados as $resultado): ?>
            <article class="pelicula">
                <div class="poster">
                    <img src="<?= $resultado['imagen'] ?>" alt="<?= $resultado['titulo'] ?>">
                </div>
                <div class="pelicula-info">
                    <h3><?= $resultado['titulo'] ?></h3>
                    <span class="anio"><?= $resultado['anio'] ?></span>
                    <a href="pelicula.php?id=<?= urlencode($resultado['id']) ?>" class="btn-pelicula">
                        Ver ficha
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
</main>

<?php include 'includes/footer.php'; ?>