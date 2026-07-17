<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CineSystem</title>

<link rel="stylesheet" href="css/styles.css">

</head>

<body>

<?php include 'includes/header.php'; ?>

<main class="login-container">

    <div class="login-card">

        <div class="login-icon">
            🎬
        </div>

        <h1>Bienvenido</h1>

        <?php if (isset($_GET['error'])): ?>
    <p style="
        text-align:center;
        margin-bottom:20px;
    ">
        Usuario o contraseña incorrectos
    </p>
<?php endif; ?>

        <p class="login-subtitle">
            Inicia sesión para acceder al sistema.
        </p>

        <form action="procesar-login.php" method="POST">

            <label>

                Usuario

                <input
                    type="text"
                    name="usuario"
                    placeholder="Ingresa tu usuario"
                    required>

            </label>

            <label>

                Contraseña

                <input
                    type="password"
                    name="clave"
                    placeholder="Ingresa tu contraseña"
                    required>

            </label>

            <button type="submit">

                Iniciar sesión

            </button>

        </form>

    </div>

</main>

<?php include 'includes/footer.php'; ?>

</body>

</html>