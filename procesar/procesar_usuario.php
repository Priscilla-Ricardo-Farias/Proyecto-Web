<?php
session_start();
require __DIR__ . '/../includes/auth.php';

requiereAdmin();

require __DIR__ . '/../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin-usuarios.php');
    exit;
}

$accion = $_POST['accion'] ?? '';
$rolesValidos = ['usuario', 'editor', 'admin'];

function volverConError(string $codigo, array $extra = []): void
{
    $query = array_merge(['error' => $codigo], $extra);
    header('Location: ../admin/admin-usuario-form.php?' . http_build_query($query));
    exit;
}

if ($accion === 'crear') {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmacion = $_POST['password_confirmacion'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';

    if ($nombre === '' || $email === '' || $password === '' || $passwordConfirmacion === '' || !in_array($rol, $rolesValidos, true)) {
        volverConError('campos_incompletos', ['nombre' => $nombre, 'email' => $email, 'rol' => $rol]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        volverConError('email_invalido', ['nombre' => $nombre, 'email' => $email, 'rol' => $rol]);
    }

    if (strlen($password) < 6) {
        volverConError('password_corta', ['nombre' => $nombre, 'email' => $email, 'rol' => $rol]);
    }

    if (!hash_equals($password, $passwordConfirmacion)) {
        volverConError('password_no_coincide', ['nombre' => $nombre, 'email' => $email, 'rol' => $rol]);
    }

    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $yaExiste = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($yaExiste) {
        volverConError('email_en_uso', ['nombre' => $nombre, 'email' => $email, 'rol' => $rol]);
    }

    $passwordHasheada = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $nombre, $email, $passwordHasheada, $rol);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-usuarios.php?ok=creada');
    exit;
}

if ($accion === 'cambiar_rol') {

    $id = (int) ($_POST['id'] ?? 0);
    $nuevoRol = $_POST['rol'] ?? '';

    if (!in_array($nuevoRol, $rolesValidos, true)) {
        header('Location: ../admin/admin-usuarios.php?error=faltan_datos');
        exit;
    }

    if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
        header('Location: ../admin/admin-usuarios.php?error=propia_cuenta');
        exit;
    }

    // Si esta cuenta es admin y la estamos bajando de rol, nos aseguramos
    // de que quede al menos un administrador en el sistema.
    $stmt = $conexion->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cuenta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cuenta && $cuenta['rol'] === 'admin' && $nuevoRol !== 'admin') {
        $totalAdmins = (int) contarFilas($conexion, "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
        if ($totalAdmins <= 1) {
            header('Location: ../admin/admin-usuarios.php?error=ultimo_admin');
            exit;
        }
    }

    $stmt = $conexion->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
    $stmt->bind_param('si', $nuevoRol, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-usuarios.php?ok=rol');
    exit;
}

if ($accion === 'eliminar') {

    $id = (int) ($_POST['id'] ?? 0);

    if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
        header('Location: ../admin/admin-usuarios.php?error=propia_cuenta');
        exit;
    }

    $stmt = $conexion->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cuenta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cuenta && $cuenta['rol'] === 'admin') {
        $totalAdmins = (int) contarFilas($conexion, "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
        if ($totalAdmins <= 1) {
            header('Location: ../admin/admin-usuarios.php?error=ultimo_admin');
            exit;
        }
    }

    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    header('Location: ../admin/admin-usuarios.php?ok=eliminada');
    exit;
}

header('Location: ../admin/admin-usuarios.php');
exit;
