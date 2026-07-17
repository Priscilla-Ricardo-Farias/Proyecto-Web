<?php
session_start();

// Simulación de usuario (puedes cambiarlo luego por BD)
$usuario_valido = "admin";
$clave_valida = "1234";

// Tomamos datos del form
$usuario = $_POST['usuario'] ?? '';
$clave = $_POST['clave'] ?? '';

// Validación simple
if ($usuario === $usuario_valido && $clave === $clave_valida) {

    $_SESSION['usuario'] = $usuario;

    header('Location: home.php');
    exit;

} else {

    // si falla login
    header('Location: index.php?error=1');
    exit;
}