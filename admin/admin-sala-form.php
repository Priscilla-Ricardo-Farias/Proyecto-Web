<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';
require_once '../salas-funciones.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$sala = null;

if ($id !== null) {
    $sala = obtenerSalaPorId($conexion, $id);
    if ($sala === null) {
        header('Location: admin-salas.php');
        exit;
    }
}

$esEdicion = $sala !== null;

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow"><?= $esEdicion ? 'Editar sala' : 'Nueva sala' ?></p>
        <h1><?= $esEdicion ? htmlspecialchars($sala['nombre']) : 'Agregar sala' ?></h1>
        <p class="salas-subtitulo">Los campos con * son obligatorios.</p>
    </section>

    <?php if (isset($_GET['error'])): ?>
        <div class="admin-aviso admin-aviso-error">
            <?php
                $errores = [
                    'faltan_datos' => 'Completá el nombre y el tipo de sala.',
                ];
                echo htmlspecialchars($errores[$_GET['error']] ?? 'Ocurrió un error.');
            ?>
        </div>
    <?php endif; ?>

    <form action="../procesar/procesar_sala.php" method="POST" class="admin-form">

        <input type="hidden" name="accion" value="<?= $esEdicion ? 'editar' : 'crear' ?>">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id" value="<?= $sala['id'] ?>">
        <?php endif; ?>

        <label>
            Nombre / número de sala *
            <input type="text" name="nombre" required maxlength="100"
                   placeholder="Ej: Sala 3"
                   value="<?= htmlspecialchars($sala['nombre'] ?? '') ?>">
        </label>

        <div class="admin-form-fila">
            <label>
                Tipo de sala *
                <select name="tipo" required>
                    <?php foreach (tiposDeSala() as $valor => $etiqueta): ?>
                        <option value="<?= $valor ?>" <?= ($sala['tipo'] ?? 'estandar') === $valor ? 'selected' : '' ?>>
                            <?= htmlspecialchars($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Estado *
                <select name="estado" required>
                    <?php foreach (estadosDeSala() as $valor => $etiqueta): ?>
                        <option value="<?= $valor ?>" <?= ($sala['estado'] ?? 'disponible') === $valor ? 'selected' : '' ?>>
                            <?= htmlspecialchars($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="admin-form-fila">
            <label>
                Cantidad de filas *
                <input type="number" name="filas" id="campo-filas" required min="1" max="50"
                       value="<?= htmlspecialchars((string) ($sala['filas'] ?? 5)) ?>">
            </label>

            <label>
                Butacas por fila *
                <input type="number" name="butacas_por_fila" id="campo-butacas" required min="1" max="50"
                       value="<?= htmlspecialchars((string) ($sala['butacas_por_fila'] ?? 10)) ?>">
            </label>
        </div>

        <label>
            Capacidad total (cantidad de butacas) *
            <input type="number" name="capacidad" id="campo-capacidad" required min="1" max="2000"
                   value="<?= htmlspecialchars((string) ($sala['capacidad'] ?? 50)) ?>">
        </label>

        <div class="admin-form-acciones">
            <button type="submit" class="btn-comprar"><?= $esEdicion ? 'Guardar cambios' : 'Crear sala' ?></button>
            <a href="admin-salas.php" class="btn-volver">← Cancelar</a>
        </div>

    </form>

</main>

<script>
// Sugiere la capacidad automáticamente (filas x butacas), pero el admin puede sobrescribirla.
(function () {
    const filas = document.getElementById('campo-filas');
    const butacas = document.getElementById('campo-butacas');
    const capacidad = document.getElementById('campo-capacidad');
    let capacidadTocadaAMano = false;

    capacidad.addEventListener('input', function () {
        capacidadTocadaAMano = true;
    });

    function sugerirCapacidad() {
        if (capacidadTocadaAMano) return;
        const f = parseInt(filas.value, 10) || 0;
        const b = parseInt(butacas.value, 10) || 0;
        if (f > 0 && b > 0) {
            capacidad.value = f * b;
        }
    }

    filas.addEventListener('input', sugerirCapacidad);
    butacas.addEventListener('input', sugerirCapacidad);
})();
</script>

<?php include '../includes/footer.php'; ?>
