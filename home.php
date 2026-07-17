<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

include 'includes/header.php';
?>

<h1>Hola, <?= $_SESSION['usuario'] ?></h1>

<p style="text-align:center; color:#94a3b8;">
    Estás dentro del sistema.
</p>

<?php include 'includes/footer.php'; ?>