<?php
session_start();
require 'includes/auth.php';

requiereLogin();
bloquearAdmin();

require 'conexion.php';
require_once 'entradas.php';

// Este archivo solo procesa la devolución que el usuario pide desde "Mis entradas".
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: info.php');
    exit;
}

$entradaId = (int) ($_POST['entrada_id'] ?? 0);
$codigoIngresado = strtoupper(trim($_POST['codigo'] ?? ''));

// Solo se puede devolver una entrada propia
$stmt = $conexion->prepare("SELECT * FROM entradas WHERE id = ? AND usuario = ?");
$stmt->bind_param('is', $entradaId, $_SESSION['usuario']);
$stmt->execute();
$entrada = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($entrada === null) {
    $resultado = 'no_existe';
} elseif ($codigoIngresado === '' || !hash_equals(strtoupper($entrada['codigo']), $codigoIngresado)) {
    // Hay que escribir el código que se entregó al comprar
    $resultado = 'codigo_incorrecto';
} elseif ($entrada['estado'] === 'devuelta') {
    $resultado = 'ya_devuelta';
} elseif (funcionDeEntradaYaEmpezo($entrada)) {
    // Una vez que empieza la función, ya no se puede devolver
    $resultado = 'funcion_pasada';
} else {
    $stmt = $conexion->prepare(
        "UPDATE entradas SET estado = 'devuelta', fecha_devolucion = NOW()
         WHERE id = ? AND usuario = ? AND estado = 'activa'"
    );
    $stmt->bind_param('is', $entradaId, $_SESSION['usuario']);
    $stmt->execute();
    $resultado = $stmt->affected_rows === 1 ? 'ok' : 'ya_devuelta';
    $stmt->close();
}

$codigoMostrar = $resultado === 'codigo_incorrecto' ? '' : $entrada['codigo'] ?? '';
header('Location: info.php?devolucion=' . $resultado . '&codigo=' . urlencode($codigoMostrar));
exit;
