<?php
session_start();
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../registro.php');
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirmacion = $_POST['password_confirmacion'] ?? '';

$volverConError = function (string $codigo) use ($nombre, $email): void {
    header(
        'Location: ../registro.php?error=' . urlencode($codigo)
        . '&nombre=' . urlencode($nombre)
        . '&email=' . urlencode($email)
    );
    exit;
};

if ($nombre === '' || $email === '' || $password === '' || $passwordConfirmacion === '') {
    $volverConError('campos_incompletos');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $volverConError('email_invalido');
}

if (strlen($password) < 6) {
    $volverConError('password_corta');
}

if (!hash_equals($password, $passwordConfirmacion)) {
    $volverConError('password_no_coincide');
}

// El email tiene que ser único en la tabla usuarios (ahí viven
// tanto las cuentas normales como las de admin).
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$yaExisteUsuario = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($yaExisteUsuario) {
    $volverConError('email_en_uso');
}

$passwordHasheada = password_hash($password, PASSWORD_DEFAULT);

// El registro público nunca crea admins: se deja el rol por defecto ('usuario').
$stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $nombre, $email, $passwordHasheada);
$stmt->execute();
$nuevoId = (int) $conexion->insert_id;
$stmt->close();

// Lo dejamos directamente logueado, como un usuario normal (el registro nunca crea admins)
$_SESSION['usuario_id'] = $nuevoId;
$_SESSION['usuario'] = $nombre;
$_SESSION['rol'] = 'usuario';

header('Location: ../cartelera.php');
exit;
