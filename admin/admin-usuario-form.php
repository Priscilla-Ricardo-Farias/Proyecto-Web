<?php
session_start();
require '../includes/auth.php';

requiereAdmin();

$valores = [
    'nombre' => $_GET['nombre'] ?? '',
    'email'  => $_GET['email'] ?? '',
    'rol'    => $_GET['rol'] ?? 'usuario',
];

$errores = [
    'campos_incompletos'   => 'Completá todos los campos para crear la cuenta.',
    'email_invalido'       => 'Ingresá un email válido.',
    'password_corta'       => 'La contraseña tiene que tener al menos 6 caracteres.',
    'password_no_coincide' => 'Las contraseñas no coinciden.',
    'email_en_uso'         => 'Ya existe una cuenta registrada con ese email.',
];
$error = $_GET['error'] ?? null;

include '../includes/header-admin.php';
?>

<main class="admin-page">

    <section class="salas-hero">
        <p class="salas-eyebrow">Cuentas del sistema</p>
        <h1>Crear cuenta</h1>
        <p class="salas-subtitulo">Elegí el rol correspondiente: usuario (compra entradas), editor (gestiona el catálogo) o administrador (acceso total).</p>
    </section>

    <?php if ($error): ?>
        <div class="admin-aviso admin-aviso-error">
            <?= htmlspecialchars($errores[$error] ?? 'No se pudo completar el registro.') ?>
        </div>
    <?php endif; ?>

    <form action="../procesar/procesar_usuario.php" method="POST" class="admin-form">

        <input type="hidden" name="accion" value="crear">

        <label>
            Nombre *
            <input type="text" name="nombre" required maxlength="100"
                   value="<?= htmlspecialchars($valores['nombre']) ?>">
        </label>

        <label>
            Email *
            <input type="email" name="email" required maxlength="100"
                   value="<?= htmlspecialchars($valores['email']) ?>">
        </label>

        <div class="admin-form-fila">
            <label>
                Contraseña *
                <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
            </label>

            <label>
                Repetir contraseña *
                <input type="password" name="password_confirmacion" required minlength="6">
            </label>
        </div>

        <label>
            Rol *
            <select name="rol" required>
                <option value="usuario" <?= $valores['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario (compra entradas)</option>
                <option value="editor" <?= $valores['rol'] === 'editor' ? 'selected' : '' ?>>Editor (gestiona el catálogo)</option>
                <option value="admin" <?= $valores['rol'] === 'admin' ? 'selected' : '' ?>>Administrador (acceso total)</option>
            </select>
        </label>

        <div class="admin-form-acciones">
            <button type="submit" class="btn-comprar">Crear cuenta</button>
            <a href="admin-usuarios.php" class="btn-volver">← Cancelar</a>
        </div>

    </form>

</main>

<?php include '../includes/footer.php'; ?>
