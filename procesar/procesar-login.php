<?php
session_start();
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../conexion.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: ../index.php?error=1");
    exit();
}

// Usuarios y admins viven en una sola tabla; el rol de cada
// cuenta lo dice la columna `rol` ('admin' o 'usuario').
$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $cuenta = $resultado->fetch_assoc();

    if (passwordCoincide($password, $cuenta['password'])) {
        $_SESSION['usuario_id'] = $cuenta['id'];
        $_SESSION['usuario'] = $cuenta['nombre'];
        $_SESSION['rol'] = $cuenta['rol'];

        header("Location: " . (in_array($cuenta['rol'], ['admin', 'editor'], true) ? '../panel-admin.php' : '../cartelera.php'));
        exit();
    }

    header("Location: ../index.php?error=1");
    exit();
}

// Si no encontró nada
header("Location: ../index.php?error=1");
exit();
