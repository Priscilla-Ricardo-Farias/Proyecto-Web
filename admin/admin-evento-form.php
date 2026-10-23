<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';
require_once '../eventos-funciones.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$evento = null;

if ($id !== null) {
    $evento = obtenerEventoPorId($conexion, $id);
    if ($evento === null) {
        header('Location: admin-eventos.php');
        exit;
    }
}

$esEdicion = $evento !== null;

// Para el selector de película (opcional)
$peliculas = mysqli_query($conexion, "SELECT id, nombre FROM peliculas ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow"><?= $esEdicion ? 'Editar evento' : 'Nuevo evento' ?></p>
        <h1><?= $esEdicion ? htmlspecialchars($evento['titulo']) : 'Crear evento' ?></h1>
        <p class="salas-subtitulo">
            Solo el título y el tipo son obligatorios. No hace falta asociarlo a ninguna película
            ni definir fechas si todavía no las tenés. Podés elegir más de una película
            (por ejemplo, una maratón de superhéroes).
        </p>
    </section>

    <form action="../procesar/procesar_evento.php" method="POST" class="admin-form">

        <input type="hidden" name="accion" value="<?= $esEdicion ? 'editar' : 'crear' ?>">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id" value="<?= $evento['id'] ?>">
        <?php endif; ?>

        <label>
            Título *
            <input type="text" name="titulo" required maxlength="150"
                   placeholder="Ej: Vuelve a la pantalla grande"
                   value="<?= htmlspecialchars($evento['titulo'] ?? '') ?>">
        </label>

        <label>
            Tipo de evento *
            <select name="tipo" required>
                <?php foreach (tiposDeEvento() as $valor => $etiqueta): ?>
                    <option value="<?= $valor ?>" <?= ($evento['tipo'] ?? 'reestreno') === $valor ? 'selected' : '' ?>>
                        <?= htmlspecialchars($etiqueta) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <fieldset class="admin-checkbox-grupo">
            <legend>Películas incluidas en el evento (opcional, podés elegir varias)</legend>
            <div class="admin-checkbox-lista">
                <?php $peliculasSeleccionadas = $evento['peliculas_ids'] ?? []; ?>
                <?php foreach ($peliculas as $p): ?>
                    <label class="admin-checkbox">
                        <input type="checkbox" name="peliculas_ids[]" value="<?= $p['id'] ?>"
                               <?= in_array((int) $p['id'], $peliculasSeleccionadas, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="admin-form-fila">
            <label>
                Fecha de inicio (opcional)
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($evento['fecha_inicio'] ?? '') ?>">
            </label>

            <label>
                Fecha de fin (opcional)
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($evento['fecha_fin'] ?? '') ?>">
            </label>
        </div>

        <label>
            Descripción (opcional)
            <textarea name="descripcion" maxlength="500" rows="3"
                      placeholder="Contales a los usuarios de qué se trata..."><?= htmlspecialchars($evento['descripcion'] ?? '') ?></textarea>
        </label>

        <label>
            URL de imagen (opcional)
            <input type="text" name="imagen" maxlength="255" placeholder="https://..."
                   value="<?= htmlspecialchars($evento['imagen'] ?? '') ?>">
        </label>

        <?php if ($esEdicion): ?>
            <label class="admin-checkbox">
                <input type="checkbox" name="activo" value="1" <?= $evento['activo'] ? 'checked' : '' ?>>
                Evento activo (visible para los usuarios)
            </label>
        <?php endif; ?>

        <div class="admin-form-acciones">
            <button type="submit" class="btn-comprar"><?= $esEdicion ? 'Guardar cambios' : 'Crear evento' ?></button>
            <a href="admin-eventos.php" class="btn-volver">← Cancelar</a>
        </div>

    </form>

</main>

<?php include '../includes/footer.php'; ?>
