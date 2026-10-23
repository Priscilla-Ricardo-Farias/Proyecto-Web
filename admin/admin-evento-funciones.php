<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';
require_once '../eventos-funciones.php';
require_once '../salas-funciones.php';

$id = (int) ($_GET['id'] ?? 0);
$evento = $id > 0 ? obtenerEventoPorId($conexion, $id) : null;

if ($evento === null) {
    header('Location: admin-eventos.php');
    exit;
}

$peliculasEvento = obtenerPeliculasDeEvento($conexion, $id);
$salas = array_values(array_filter(
    obtenerTodasLasSalas($conexion),
    fn($s) => $s['estado'] === 'disponible'
));

// Fechas sugeridas: las del evento si las tiene (sin ir al pasado); si no, la próxima semana
$hoy = date('Y-m-d');
$desde = (!empty($evento['fecha_inicio']) && $evento['fecha_inicio'] > $hoy) ? $evento['fecha_inicio'] : $hoy;
$hasta = !empty($evento['fecha_fin']) ? $evento['fecha_fin'] : date('Y-m-d', strtotime($desde . ' +6 days'));
if ($hasta < $desde) {
    $hasta = $desde;
}

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Programar funciones del evento</p>
        <h1><?= htmlspecialchars($evento['titulo']) ?></h1>
        <p class="salas-subtitulo">
            Elegí la sala, los días y los horarios. Las películas del evento se van rotando entre los horarios
            cada día, así todas pasan por todos los horarios.
        </p>
    </section>

    <?php if (isset($_GET['error'])): ?>
        <div class="admin-aviso admin-aviso-error">
            <?php
                $errores = [
                    'faltan_datos'    => 'Completá la sala, las fechas, el precio y al menos un horario.',
                    'fechas_invalidas'=> 'Revisá las fechas: la última no puede ser anterior a la primera (máximo 31 días).',
                    'horario_invalido'=> 'Alguno de los horarios no es válido.',
                    'sin_peliculas'   => 'El evento no tiene películas. Editá el evento y elegí al menos una.',
                    'sala_invalida'   => 'Elegí una sala disponible.',
                ];
                echo htmlspecialchars($errores[$_GET['error']] ?? 'Ocurrió un error.');
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($peliculasEvento)): ?>

        <div class="admin-aviso admin-aviso-error">
            Este evento todavía no tiene películas.
            <a href="admin-evento-form.php?id=<?= $evento['id'] ?>">Editar evento</a>
        </div>

    <?php elseif (empty($salas)): ?>

        <div class="admin-aviso admin-aviso-error">
            No hay ninguna sala disponible para programar funciones.
        </div>

    <?php else: ?>

        <form action="../procesar/procesar_ciclo.php" method="POST" class="admin-form">

            <input type="hidden" name="evento_id" value="<?= $evento['id'] ?>">

            <p class="salas-subtitulo">
                Películas que se van a rotar:
                <strong><?= htmlspecialchars(implode(', ', array_map(fn($p) => $p['nombre'], $peliculasEvento))) ?></strong>
            </p>

            <label>
                Sala *
                <select name="sala_id" required>
                    <?php foreach ($salas as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?> (<?= htmlspecialchars(etiquetaTipoSala($s['tipo'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="admin-form-fila">
                <label>
                    Desde el día *
                    <input type="date" name="desde" required value="<?= htmlspecialchars($desde) ?>">
                </label>

                <label>
                    Hasta el día *
                    <input type="date" name="hasta" required value="<?= htmlspecialchars($hasta) ?>">
                </label>
            </div>

            <fieldset class="admin-checkbox-grupo">
                <legend>Horarios de cada día (el primero es obligatorio, podés dejar vacíos los otros)</legend>
                <div class="admin-form-fila">
                    <label>Horario 1 <input type="time" name="horarios[]" required value="16:00"></label>
                    <label>Horario 2 <input type="time" name="horarios[]" value="19:00"></label>
                </div>
                <div class="admin-form-fila">
                    <label>Horario 3 <input type="time" name="horarios[]" value="22:00"></label>
                    <label>Horario 4 <input type="time" name="horarios[]"></label>
                </div>
            </fieldset>

            <label>
                Precio de la entrada *
                <input type="number" name="precio_entrada" required min="0" step="1" value="3500">
            </label>

            <p class="salas-subtitulo">
                La hora de fin se calcula con la duración de cada película. Si un horario se superpone con otra función
                de la misma sala, se omite.
            </p>

            <div class="admin-form-acciones">
                <button type="submit" class="btn-comprar">Programar funciones</button>
                <a href="admin-eventos.php" class="btn-volver">← Cancelar</a>
            </div>

        </form>

    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>
