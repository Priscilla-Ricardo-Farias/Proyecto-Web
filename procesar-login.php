<?php
session_start();

$usuarios = [
    'admin'  => '1234',
    'juan'   => '5678',
];

$usuario = $_POST['usuario'] ?? '';
$clave   = $_POST['clave']   ?? '';

if (isset($usuarios[$usuario]) && $usuarios[$usuario] === $clave) {
    $_SESSION['usuario'] = $usuario;
    header('Location: bienvenido.php');
    exit;
} else {
    header('Location: index.php');
    exit;
}