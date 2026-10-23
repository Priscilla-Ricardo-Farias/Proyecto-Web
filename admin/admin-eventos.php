<?php
session_start();
require '../includes/auth.php';

requiereGestion();

require_once '../conexion.php';
require_once '../eventos-funciones.php';

$eventos = obtenerTodosLosEventos($conexion);

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Agenda especial</p>
        <h1>Eventos</h1>
        <p class="salas-subtitulo">Reestrenos, funciones de último adiós o cualquier otro evento. Ningún dato es obligatorio salvo el título y el tipo.</p>
    </section>

    <?php if (isset($_GET['ok'])): ?>
        <div class="admin-aviso admin-aviso-exito">
            <?php
                $mensajes = [
                    'creado'   => 'El evento se creó correctamente.',
                    'editado'  => 'Los cambios se guardaron correctamente.',
                    'eliminado'=> 'El evento se eliminó.',
                    'estado'   => 'Se actualizó el estado del evento.',
                ];
                echo htmlspecialchars($mensajes[$_GET['ok']] ?? 'Listo.');
            ?>
        </div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <a href="admin-evento-form.php" class="btn-comprar">+ Crear evento</a>
    </div>

    <div class="admin-tabla-wrap">
        <table class="admin-tabla">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Películas</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventos)): ?>
                    <tr><td colspan="6">Todavía no creaste ningún evento.</td></tr>
                <?php endif; ?>

                <?php foreach ($eventos as $e): ?>
                    <tr class="<?= $e['activo'] ? '' : 'fila-inactiva' ?>">
                        <td><?= htmlspecialchars($e['titulo']) ?></td>
                        <td><span class="evento-chip-tag evento-chip-tag-<?= htmlspecialchars($e['tipo']) ?>"><?= htmlspecialchars(etiquetaEvento($e['tipo'])) ?></span></td>
                        <td>
                            <?php if (!empty($e['peliculas_lista'])): ?>
                                <?= htmlspecialchars(implode(', ', $e['peliculas_lista'])) ?>
                                <?php if (count($e['peliculas_lista']) > 1): ?>
                                    <span class="admin-estado-pill estado-activo">x<?= count($e['peliculas_lista']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($e['fecha_inicio'] || $e['fecha_fin']): ?>
                                <?= $e['fecha_inicio'] ? date('d/m/Y', strtotime($e['fecha_inicio'])) : '…' ?>
                                &rarr;
                                <?= $e['fecha_fin'] ? date('d/m/Y', strtotime($e['fecha_fin'])) : '…' ?>
                            <?php else: ?>
                                Sin fecha definida
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-estado-pill <?= $e['activo'] ? 'estado-activo' : 'estado-inactivo' ?>">
                                <?= $e['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="admin-tabla-acciones">
                            <a href="admin-evento-form.php?id=<?= $e['id'] ?>">Editar</a>

                            <form action="../procesar/procesar_evento.php" method="POST" class="admin-form-inline">
                                <input type="hidden" name="accion" value="toggle">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                <button type="submit" class="admin-link-btn">
                                    <?= $e['activo'] ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>

                            <form action="../procesar/procesar_evento.php" method="POST" class="admin-form-inline"
                                  onsubmit="return confirm('¿Eliminar este evento?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
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
