<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';
require_once '../salas-funciones.php';

$salas = obtenerTodasLasSalas($conexion);

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Infraestructura</p>
        <h1>Salas</h1>
        <p class="salas-subtitulo">Administrá dónde se proyectan las películas: capacidad, tipo de sala y estado.</p>
    </section>

    <?php if (isset($_GET['ok'])): ?>
        <div class="admin-aviso admin-aviso-exito">
            <?php
                $mensajes = [
                    'creada'   => 'La sala se creó correctamente.',
                    'editada'  => 'Los cambios se guardaron correctamente.',
                    'eliminada'=> 'La sala se eliminó.',
                    'estado'   => 'Se actualizó el estado de la sala.',
                ];
                echo htmlspecialchars($mensajes[$_GET['ok']] ?? 'Listo.');
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="admin-aviso admin-aviso-error">
            <?php
                $errores = [
                    'en_uso' => 'No se puede eliminar: esta sala tiene funciones programadas. Marcala en mantenimiento en su lugar.',
                ];
                echo htmlspecialchars($errores[$_GET['error']] ?? 'Ocurrió un error.');
            ?>
        </div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <a href="admin-sala-form.php" class="btn-comprar">+ Agregar sala</a>
    </div>

    <div class="admin-tabla-wrap">
        <table class="admin-tabla">
            <thead>
                <tr>
                    <th>Sala</th>
                    <th>Tipo</th>
                    <th>Capacidad</th>
                    <th>Filas x Butacas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($salas)): ?>
                    <tr><td colspan="6">Todavía no hay salas cargadas.</td></tr>
                <?php endif; ?>

                <?php foreach ($salas as $s): ?>
                    <tr class="<?= $s['estado'] === 'disponible' ? '' : 'fila-inactiva' ?>">
                        <td><strong><?= htmlspecialchars($s['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars(etiquetaTipoSala($s['tipo'])) ?></td>
                        <td><?= (int) $s['capacidad'] ?> butacas</td>
                        <td><?= (int) $s['filas'] ?> filas x <?= (int) $s['butacas_por_fila'] ?></td>
                        <td>
                            <span class="admin-estado-pill <?= $s['estado'] === 'disponible' ? 'estado-activo' : 'estado-inactivo' ?>">
                                <?= htmlspecialchars(etiquetaEstadoSala($s['estado'])) ?>
                            </span>
                        </td>
                        <td class="admin-tabla-acciones">
                            <a href="admin-sala-form.php?id=<?= $s['id'] ?>">Editar</a>

                            <form action="../procesar/procesar_sala.php" method="POST" class="admin-form-inline">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="admin-link-btn">
                                    <?= $s['estado'] === 'disponible' ? 'Poner en mantenimiento' : 'Marcar disponible' ?>
                                </button>
                            </form>

                            <form action="../procesar/procesar_sala.php" method="POST" class="admin-form-inline"
                                  onsubmit="return confirm('¿Eliminar esta sala definitivamente?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="admin-link-btn admin-link-btn-peligro">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<?php include '../includes/footer.php'; ?>
