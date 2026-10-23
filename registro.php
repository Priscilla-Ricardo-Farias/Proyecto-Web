<?php
session_start();
require 'includes/auth.php';

redirigirSiYaLogueado();

$valores = [
    'nombre' => $_GET['nombre'] ?? '',
    'email'  => $_GET['email'] ?? '',
];

$errores = [
    'campos_incompletos' => 'Completá todos los campos para registrarte.',
    'email_invalido'     => 'Ingresá un email válido.',
    'password_corta'     => 'La contraseña tiene que tener al menos 6 caracteres.',
    'password_no_coincide' => 'Las contraseñas no coinciden.',
    'email_en_uso'       => 'Ya existe una cuenta registrada con ese email.',
];
$error = $_GET['error'] ?? null;

include 'includes/header.php';
?>

<main class="login-container">

    <div class="login-card">

        <div class="login-icon">
            🎬
        </div>

        <h1>Crear cuenta</h1>

        <?php if ($error): ?>
            <p class="login-error">
                <?= htmlspecialchars($errores[$error] ?? 'No se pudo completar el registro.') ?>
            </p>
        <?php endif; ?>

        <p class="login-subtitle">
            Registrate para poder comprar tus entradas.
        </p>

        <form action="procesar/procesar-registro.php" method="POST">

            <label>

                Nombre

                <input
                    type="text"
                    name="nombre"
                    placeholder="Como querés que te llamemos"
                    maxlength="100"
                    value="<?= htmlspecialchars($valores['nombre']) ?>"
                    required>

            </label>

            <label>

                Email

                <input
                    type="email"
                    name="email"
                    placeholder="tu@email.com"
                    maxlength="100"
                    value="<?= htmlspecialchars($valores['email']) ?>"
                    required>

            </label>

            <label>

                Contraseña

                <input
                    type="password"
                    name="password"
                    placeholder="Mínimo 6 caracteres"
                    minlength="6"
                    required>

            </label>

            <label>

                Repetir contraseña

                <input
                    type="password"
                    name="password_confirmacion"
                    placeholder="Repetí tu contraseña"
                    minlength="6"
                    required>

            </label>

            <button type="submit">

                Crear cuenta

            </button>

        </form>

        <p class="login-subtitle" style="margin-top:25px;">
            ¿Ya tenés cuenta? <a href="index.php">Iniciá sesión</a>
        </p>

    </div>

</main>

<?php include 'includes/footer.php'; ?>
