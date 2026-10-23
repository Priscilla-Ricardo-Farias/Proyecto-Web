<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';
require_once '../salas-funciones.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$funcion = null;

if ($id !== null) {
    $funcion = obtenerFuncionPorId($conexion, $id);
    if ($funcion === null) {
        header('Location: admin-funciones.php');
        exit;
    }
}

$esEdicion = $funcion !== null;

$peliculas = mysqli_query($conexion, "SELECT id, nombre FROM peliculas ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
$salas = obtenerTodasLasSalas($conexion);

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow"><?= $esEdicion ? 'Editar función' : 'Nueva función' ?></p>
        <h1><?= $esEdicion ? htmlspecialchars($funcion['pelicula_nombre']) : 'Programar función' ?></h1>
        <p class="salas-subtitulo">Elegí la película, la sala, el horario y el precio de la entrada.</p>
    </section>

    <?php if (isset($_GET['error'])): ?>
        <div class="admin-aviso admin-aviso-error">
            <?php
                $errores = [
                    'faltan_datos'  => 'Completá todos los campos obligatorios.',
                    'horario_invalido' => 'La hora de finalización debe ser posterior a la hora de inicio.',
                ];
                echo htmlspecialchars($errores[$_GET['error']] ?? 'Ocurrió un error.');
            ?>
        </div>
    <?php endif; ?>

    <form action="../procesar/procesar_funcion.php" method="POST" class="admin-form">

        <input type="hidden" name="accion" value="<?= $esEdicion ? 'editar' : 'crear' ?>">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id" value="<?= $funcion['id'] ?>">
        <?php endif; ?>

        <label>
            Película *
            <select name="pelicula_id" required>
                <option value="">— Elegir película —</option>
                <?php foreach ($peliculas as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int) ($funcion['pelicula_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Sala *
            <select name="sala_id" required>
                <option value="">— Elegir sala —</option>
                <?php foreach ($salas as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= (int) ($funcion['sala_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nombre']) ?> (<?= htmlspecialchars(etiquetaTipoSala($s['tipo'])) ?>)
                        <?= $s['estado'] === 'mantenimiento' ? ' — en mantenimiento' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="admin-form-fila">
            <label>
                Fecha *
                <input type="date" name="fecha" required
                       value="<?= htmlspecialchars($funcion['fecha'] ?? '') ?>">
            </label>

            <label>
                Precio de la entrada *
                <input type="number" name="precio_entrada" required min="0" step="1"
                       value="<?= htmlspecialchars((string) ($funcion['precio_entrada'] ?? 3500)) ?>">
            </label>
        </div>

        <div class="admin-form-fila">
            <label>
                Hora de inicio *
                <input type="time" name="hora_inicio" required
                       value="<?= htmlspecialchars(substr($funcion['hora_inicio'] ?? '', 0, 5)) ?>">
            </label>

            <label>
                Hora de finalización *
                <input type="time" name="hora_fin" required
                       value="<?= htmlspecialchars(substr($funcion['hora_fin'] ?? '', 0, 5)) ?>">
            </label>
        </div>

        <label>
            Estado de la función *
            <select name="estado" required>
                <?php foreach (estadosDeFuncion() as $valor => $etiqueta): ?>
                    <option value="<?= $valor ?>" <?= ($funcion['estado'] ?? 'programada') === $valor ? 'selected' : '' ?>>
                        <?= htmlspecialchars($etiqueta) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="admin-form-acciones">
            <button type="submit" class="btn-comprar"><?= $esEdicion ? 'Guardar cambios' : 'Crear función' ?></button>
            <a href="admin-funciones.php" class="btn-volver">← Cancelar</a>
        </div>

    </form>

</main>

<?php include '../includes/footer.php'; ?>
