<?php
session_start();
require '../includes/auth.php';

requiereAdmin();

require_once '../conexion.php';

$busqueda = trim($_GET['q'] ?? '');
$rolFiltro = $_GET['rol'] ?? 'todos';
if (!in_array($rolFiltro, ['todos', 'usuario', 'editor', 'admin'], true)) {
    $rolFiltro = 'todos';
}

// `usuarios` ya tiene la columna `rol`, así que no hace falta
// combinar nada: es una sola tabla con cuentas de los tres tipos.
$condiciones = [];
$parametros = [];
$tipos = '';

if ($rolFiltro !== 'todos') {
    $condiciones[] = 'rol = ?';
    $parametros[] = $rolFiltro;
    $tipos .= 's';
}

if ($busqueda !== '') {
    $condiciones[] = '(nombre LIKE ? OR email LIKE ?)';
    $like = '%' . $busqueda . '%';
    $parametros[] = $like;
    $parametros[] = $like;
    $tipos .= 'ss';
}

$sql = "SELECT id, nombre, email, rol FROM usuarios";
if (!empty($condiciones)) {
    $sql .= " WHERE " . implode(' AND ', $condiciones);
}
$sql .= " ORDER BY FIELD(rol, 'admin', 'editor', 'usuario'), nombre";

$stmt = $conexion->prepare($sql);
if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$personas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalUsuarios = contarFilas($conexion, "SELECT COUNT(*) FROM usuarios WHERE rol = 'usuario'");
$totalEditores = contarFilas($conexion, "SELECT COUNT(*) FROM usuarios WHERE rol = 'editor'");
$totalAdmins = contarFilas($conexion, "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");

$etiquetasRol = [
    'usuario' => 'Usuario',
    'editor'  => 'Editor',
    'admin'   => 'Administrador',
];

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Cuentas del sistema</p>
        <h1>Usuarios y administradores</h1>
        <p class="salas-subtitulo">Todas las cuentas registradas, con su rol correspondiente. El editor puede gestionar el catálogo (películas, salas, funciones y eventos) pero no ve pagos, devoluciones, reportes ni otras cuentas.</p>
    </section>

    <?php if (isset($_GET['ok'])): ?>
        <div class="admin-aviso admin-aviso-exito">
            <?php
                $mensajes = [
                    'creada' => 'La cuenta se creó correctamente.',
                    'rol'    => 'Se actualizó el rol de la cuenta.',
                    'eliminada' => 'La cuenta se eliminó.',
                ];
                echo htmlspecialchars($mensajes[$_GET['ok']] ?? 'Listo.');
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="admin-aviso admin-aviso-error">
            <?php
                $errores = [
                    'email_en_uso'   => 'Ya existe una cuenta registrada con ese email.',
                    'faltan_datos'   => 'Completá todos los campos obligatorios.',
                    'propia_cuenta'  => 'No podés cambiar el rol ni eliminar tu propia cuenta desde acá.',
                    'ultimo_admin'   => 'Tiene que quedar al menos un administrador: no se puede dejar el sistema sin ninguno.',
                ];
                echo htmlspecialchars($errores[$_GET['error']] ?? 'Ocurrió un error.');
            ?>
        </div>
    <?php endif; ?>

    <section class="admin-stats">
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= (int) $totalUsuarios ?></span>
            <span class="admin-stat-label">Usuarios</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= (int) $totalEditores ?></span>
            <span class="admin-stat-label">Editores</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= (int) $totalAdmins ?></span>
            <span class="admin-stat-label">Administradores</span>
        </div>
        <div class="admin-stat-card">
            <span class="admin-stat-num"><?= (int) $totalUsuarios + (int) $totalEditores + (int) $totalAdmins ?></span>
            <span class="admin-stat-label">Total de cuentas</span>
        </div>
    </section>

    <div class="admin-toolbar">
        <a href="admin-usuario-form.php" class="btn-comprar">+ Crear cuenta</a>
    </div>

    <form method="GET" class="admin-filtros">
        <input
            type="text"
            name="q"
            placeholder="Buscar por nombre o email..."
            value="<?= htmlspecialchars($busqueda) ?>">

        <select name="rol">
            <option value="todos" <?= $rolFiltro === 'todos' ? 'selected' : '' ?>>Todos los roles</option>
            <option value="usuario" <?= $rolFiltro === 'usuario' ? 'selected' : '' ?>>Solo usuarios</option>
            <option value="editor" <?= $rolFiltro === 'editor' ? 'selected' : '' ?>>Solo editores</option>
            <option value="admin" <?= $rolFiltro === 'admin' ? 'selected' : '' ?>>Solo administradores</option>
        </select>

        <button type="submit">Filtrar</button>
        <?php if ($busqueda !== '' || $rolFiltro !== 'todos'): ?>
            <a href="admin-usuarios.php" class="limpiar-genero">✕ Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="admin-tabla-wrap">
        <table class="admin-tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personas)): ?>
                    <tr><td colspan="4">No se encontraron cuentas con ese filtro.</td></tr>
                <?php endif; ?>

                <?php foreach ($personas as $p): ?>
                    <?php $esUnoMismo = (int) $p['id'] === (int) ($_SESSION['usuario_id'] ?? 0); ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nombre'] ?? '—') ?> <?= $esUnoMismo ? '<span class="admin-estado-pill estado-activo">Vos</span>' : '' ?></td>
                        <td><?= htmlspecialchars($p['email'] ?? '—') ?></td>
                        <td>
                            <span class="admin-estado-pill <?= $p['rol'] === 'admin' ? 'estado-activo' : ($p['rol'] === 'editor' ? 'estado-editor' : 'estado-inactivo') ?>">
                                <?= htmlspecialchars($etiquetasRol[$p['rol']] ?? 'Usuario') ?>
                            </span>
                        </td>
                        <td class="admin-tabla-acciones">
                            <?php if ($esUnoMismo): ?>
                                —
                            <?php else: ?>
                                <form action="../procesar/procesar_usuario.php" method="POST" class="admin-form-inline admin-rol-form">
                                    <input type="hidden" name="accion" value="cambiar_rol">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <select name="rol" class="admin-rol-select">
                                        <?php foreach ($etiquetasRol as $valor => $etiqueta): ?>
                                            <option value="<?= $valor ?>" <?= $p['rol'] === $valor ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($etiqueta) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-guardar-rol">Guardar</button>
                                </form>

                                <form action="../procesar/procesar_usuario.php" method="POST" class="admin-form-inline"
                                      onsubmit="return confirm('¿Eliminar esta cuenta definitivamente?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-eliminar-cuenta">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</main>

<?php include '../includes/footer.php'; ?>
