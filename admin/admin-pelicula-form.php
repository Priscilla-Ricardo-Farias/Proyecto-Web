<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$pelicula = null;

if ($id !== null) {
    $stmt = $conexion->prepare("SELECT * FROM peliculas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $pelicula = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pelicula === null) {
        header('Location: admin-peliculas.php');
        exit;
    }
}

$directores = mysqli_query($conexion, "SELECT * FROM directores ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);

$esEdicion = $pelicula !== null;

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow"><?= $esEdicion ? 'Editar película' : 'Nueva película' ?></p>
        <h1><?= $esEdicion ? htmlspecialchars($pelicula['nombre']) : 'Agregar película' ?></h1>
        <p class="salas-subtitulo">Los campos con * son obligatorios; el resto podés dejarlos vacíos.</p>
    </section>

    <form action="../procesar/procesar_pelicula.php" method="POST" class="admin-form">

        <input type="hidden" name="accion" value="<?= $esEdicion ? 'editar' : 'crear' ?>">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id" value="<?= $pelicula['id'] ?>">
        <?php endif; ?>

        <label>
            Título *
            <input type="text" name="nombre" required maxlength="100"
                   value="<?= htmlspecialchars($pelicula['nombre'] ?? '') ?>">
        </label>

        <div class="admin-form-fila">
            <label>
                Año de lanzamiento
                <input type="number" name="lanzamiento" min="1888" max="2100"
                       value="<?= htmlspecialchars((string) ($pelicula['lanzamiento'] ?? '')) ?>">
            </label>

            <label>
                Duración (minutos)
                <input type="number" name="duracion" min="1" max="600"
                       value="<?= htmlspecialchars((string) ($pelicula['duracion'] ?? '')) ?>">
            </label>
        </div>

        <label>
            Género
            <input type="text" name="genero" maxlength="50" placeholder="Ej: Animación, Ciencia ficción"
                   value="<?= htmlspecialchars($pelicula['genero'] ?? '') ?>">
        </label>

        <label>
            Director
            <input type="text" name="director" maxlength="100" list="lista-directores"
                   placeholder="Escribí el nombre (si no existe, se crea)"
                   value="<?= htmlspecialchars(($pelicula['director_id'] ?? null) ? array_reduce($directores, fn($c, $d) => $d['id'] == $pelicula['director_id'] ? $d['nombre'] : $c, '') : '') ?>">
            <datalist id="lista-directores">
                <?php foreach ($directores as $d): ?>
                    <option value="<?= htmlspecialchars($d['nombre']) ?>">
                <?php endforeach; ?>
            </datalist>
        </label>

        <label>
            Descripción / sinopsis
            <textarea name="descripcion" maxlength="150" rows="3"><?= htmlspecialchars($pelicula['descripcion'] ?? '') ?></textarea>
        </label>

        <label>
            URL de la imagen (póster)
            <input type="text" name="imagen" maxlength="255" placeholder="https://... o img/archivo.jpg"
                   value="<?= htmlspecialchars($pelicula['imagen'] ?? '') ?>">
        </label>

        <?php if ($esEdicion): ?>
            <label class="admin-checkbox">
                <input type="checkbox" name="activa" value="1" <?= $pelicula['activa'] ? 'checked' : '' ?>>
                En cartelera (visible para los usuarios)
            </label>
        <?php endif; ?>

        <div class="admin-form-acciones">
            <button type="submit" class="btn-comprar"><?= $esEdicion ? 'Guardar cambios' : 'Crear película' ?></button>
            <a href="admin-peliculas.php" class="btn-volver">← Cancelar</a>
        </div>

    </form>

</main>

<?php include '../includes/footer.php'; ?>
