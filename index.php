<?php
session_start();
require 'includes/auth.php';

redirigirSiYaLogueado();

include 'includes/header.php';
?>

<main class="login-container">

    <div class="login-card">

        <div class="login-icon">
            🎬
        </div>

        <h1>Bienvenido</h1>

        <?php if (isset($_GET['error'])): ?>
    <p class="login-error">
        Usuario o contraseña incorrectos
    </p>
<?php endif; ?>

        <p class="login-subtitle">
            Inicia sesión para acceder al sistema.
        </p>

        <form action="procesar/procesar-login.php" method="POST">

            <label>

                Email

                <input
                    type="text"
                    name="email"
                    placeholder="Ingresa tu email"
                    required>

            </label>

            <label>

                Contraseña

                <input
                    type="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    required>

            </label>

            <button type="submit">

                Iniciar sesión

            </button>

        </form>

        <p class="login-subtitle" style="margin-top:25px;">
            ¿No tenés cuenta? <a href="registro.php">Registrate</a>
        </p>

    </div>

</main>

<?php include 'includes/footer.php'; ?>