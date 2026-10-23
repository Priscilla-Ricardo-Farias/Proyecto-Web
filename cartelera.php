<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

$peliculas = require_once 'datos.php';
require_once 'eventos-funciones.php';

// Eventos activos y vigentes para mostrar como banner arriba de la cartelera
$eventosDestacados = obtenerEventosVigentes($conexion, 6);

$busqueda = trim($_GET['id'] ?? '');
// Ahora genero es un array (puede venir vacío, o con uno o varios géneros)
$generosSeleccionados = $_GET['genero'] ?? [];
if (!is_array($generosSeleccionados)) {
    $generosSeleccionados = [$generosSeleccionados]; // por si acaso llega como string suelto
}
$generosSeleccionados = array_map('trim', $generosSeleccionados);

// Separa géneros compuestos (ej: "Animación y Musical", "Drama, Romance",
// "Terror/Suspenso") en géneros individuales, y los normaliza (mayúscula
// inicial, sin espacios de más) para que no aparezcan como bubbles
// duplicadas por simples diferencias de mayúsculas o espacios.
function separarGeneros(string $genero): array
{
    $genero = trim($genero);
    if ($genero === '') {
        return [];
    }

    // Separadores admitidos: coma, "/", "&", y las conjunciones "y"/"e"
    // como palabra completa (sin partir palabras como "Comedy" o "Bailey").
    $partes = preg_split('/\s*(?:,|\/|&|\by\b|\be\b)\s*/ui', $genero);

    $generos = [];
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if ($parte === '') {
            continue;
        }
        // Normalizamos may/min: "Primera Letra De Cada Palabra"
        $normalizado = mb_convert_case(mb_strtolower($parte, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $generos[] = $normalizado;
    }

    return $generos;
}

// Lista de géneros únicos para las bubbles (cada una es un solo género)
$generosDisponibles = [];
foreach ($peliculas as $pelicula) {
    foreach (separarGeneros($pelicula['genero']) as $g) {
        if (!in_array($g, $generosDisponibles, true)) {
            $generosDisponibles[] = $g;
        }
    }
}
sort($generosDisponibles);

// Filtrado: título y género se evalúan por separado, pero ambos aplican sobre el mismo catálogo
$resultados = [];
foreach ($peliculas as $clave => $pelicula) {
    if (empty($pelicula['activa'])) {
        continue; // el admin la retiró de la cartelera
    }

    // Búsqueda por prefijo: solo películas cuyo título empiece con lo escrito
    // (así buscar "a" no trae todas las que tienen una "a" en cualquier parte del título)
    $coincideTitulo = $busqueda === '' || str_starts_with(mb_strtolower($pelicula['titulo']), mb_strtolower($busqueda));

    // Comparamos por los géneros ya separados y normalizados de la película,
    // no por el texto completo: así "Drama" nunca matchea por accidente
    // dentro de otra palabra, y el filtro respeta la misma separación que las bubbles.
    $generosDeLaPelicula = separarGeneros($pelicula['genero']);
    $coincideGenero = empty($generosSeleccionados)
        || !empty(array_intersect($generosSeleccionados, $generosDeLaPelicula));

    if ($coincideTitulo && $coincideGenero) {
        $pelicula['id'] = $clave;
        $resultados[] = $pelicula;
    }
}
?>

<?php include 'includes/header.php'; ?>

<main class="cartelera-page">

    <section class="hero">
        <h1>Cartelera</h1>
    <p>Descubre todas las películas disponibles en nuestro catálogo.</p>

    <?php if (!empty($eventosDestacados)): ?>
        <div class="eventos-banner">
            <?php foreach ($eventosDestacados as $evento): ?>
                <?php
                    // Funciones que se pueden comprar ahora mismo para este evento
                    $funcionesEvento = obtenerFuncionesDisponiblesDeEvento($conexion, $evento);
                ?>
                <div class="evento-bloque">
                <?php
                    // Si el evento tiene una sola película, el chip lleva directo a su ficha.
                    // Si tiene varias (ej: una maratón), mandamos a la página de eventos,
                    // que las lista todas con su propio link.
                    $unaSolaPelicula = count($evento['peliculas_lista'] ?? []) === 1 && $evento['pelicula_id'];
                    $href = $unaSolaPelicula
                        ? 'pelicula.php?id=' . urlencode($evento['pelicula_id'])
                        : 'eventos.php';
                ?>
                <a
                    href="<?= $href ?>"
                    class="evento-chip evento-<?= htmlspecialchars($evento['tipo']) ?>"
                >
                    <span class="evento-chip-tag"><?= htmlspecialchars(etiquetaEvento($evento['tipo'])) ?></span>
                    <span class="evento-chip-titulo"><?= htmlspecialchars($evento['titulo']) ?></span>
                    <?php if (!empty($evento['peliculas_lista'])): ?>
                        <span class="evento-chip-peliculas">
                            🎬 <?= htmlspecialchars(implode(' · ', $evento['peliculas_lista'])) ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="evento-funciones">
                    <?php if (empty($funcionesEvento)): ?>
                        <p class="evento-funciones-vacio">Sin funciones disponibles por ahora.</p>
                    <?php else: ?>
                        <p class="evento-funciones-label">Funciones disponibles</p>
                        <?php foreach ($funcionesEvento as $grupo): ?>
                            <div class="evento-funciones-pelicula">
                                <?php if (count($funcionesEvento) > 1): ?>
                                    <span class="evento-funciones-titulo"><?= htmlspecialchars($grupo['pelicula_nombre']) ?></span>
                                <?php endif; ?>
                                <div class="evento-funciones-chips">
                                    <?php foreach ($grupo['funciones'] as $f):
                                        $esHoy = $f['fecha'] === date('Y-m-d');
                                    ?>
                                        <a href="asientos.php?funcion=<?= (int) $f['id'] ?>" class="evento-funcion-chip">
                                            <span class="ef-dia"><?= $esHoy ? 'Hoy' : nombreDiaSemana($f['fecha'], true) . ' ' . date('d/m', strtotime($f['fecha'])) ?></span>
                                            <span class="ef-hora"><?= horaCorta($f['hora_inicio']) ?></span>
                                            <span class="ef-sala"><?= htmlspecialchars($f['sala_nombre']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($grupo['hay_mas']): ?>
                                    <a href="seleccionar-funcion.php?id=<?= (int) $grupo['pelicula_id'] ?>" class="evento-funciones-mas">Ver todas las funciones →</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="GET">
        <input
            type="text"
            name="id"
            placeholder="Buscar por la primera letra del título"
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

    <nav class="generos-barra" aria-label="Filtrar por género">
        <a href="?id=<?= urlencode($busqueda) ?>" class="bubble <?= empty($generosSeleccionados) ? 'activa' : '' ?>">Todas</a>
        <?php foreach ($generosDisponibles as $g):
            $estaSeleccionado = in_array($g, $generosSeleccionados);
            $nuevosGeneros = $estaSeleccionado
                ? array_diff($generosSeleccionados, [$g])
                : array_merge($generosSeleccionados, [$g]);
            $paramsGenero = '';
            foreach ($nuevosGeneros as $ng) {
                $paramsGenero .= '&genero[]=' . urlencode($ng);
            }
        ?>
            <a href="?id=<?= urlencode($busqueda) ?><?= $paramsGenero ?>"
               class="bubble <?= $estaSeleccionado ? 'activa' : '' ?>">
                <?= htmlspecialchars($g) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</section>

<?php
// Agrupamos como Netflix: una fila por género. Una película aparece en cada
// género al que pertenece. Si hay filtros, sólo se muestran esas filas.
$filas = [];
$generosAMostrar = empty($generosSeleccionados) ? $generosDisponibles : $generosSeleccionados;
foreach ($generosAMostrar as $g) {
    foreach ($resultados as $r) {
        if (in_array($g, separarGeneros($r['genero']), true)) {
            $filas[$g][] = $r;
        }
    }
}
?>

<?php if (empty($filas)): ?>
    <p class="cartelera-vacia">No esta en nuestra cartelera.</p>
<?php else: ?>
    <?php foreach ($filas as $genero => $pelis): ?>
        <section class="fila-genero">
            <div class="fila-genero-cabecera">
                <h2><?= htmlspecialchars($genero) ?> <span class="fila-genero-cant"><?= count($pelis) ?></span></h2>
                <div class="fila-genero-flechas">
                    <button type="button" class="flecha" data-dir="-1" aria-label="Anterior">‹</button>
                    <button type="button" class="flecha" data-dir="1" aria-label="Siguiente">›</button>
                </div>
            </div>
            <div class="fila-scroll">
                <?php foreach ($pelis as $resultado): ?>
                    <article class="pelicula">
                        <div class="poster">
                            <img src="<?= $resultado['imagen'] ?>" alt="<?= $resultado['titulo'] ?>" loading="lazy">
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
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

</main>

<script>
document.querySelectorAll('.fila-genero').forEach(function (fila) {
    const scroll = fila.querySelector('.fila-scroll');
    fila.querySelectorAll('.flecha').forEach(function (btn) {
        btn.addEventListener('click', function () {
            scroll.scrollBy({ left: btn.dataset.dir * scroll.clientWidth * 0.85, behavior: 'smooth' });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>