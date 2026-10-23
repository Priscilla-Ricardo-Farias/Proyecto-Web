<?php
// Funciones de sesión/rol reutilizadas en todas las páginas.
// Se asume que quien incluye este archivo ya llamó a session_start().

// Cualquier página que requiera estar logueado (usuario o admin)
function requiereLogin(): void
{
    if (!isset($_SESSION['usuario'])) {
        header('Location: index.php');
        exit;
    }
}

// Páginas exclusivas del admin: si no es admin, se lo manda a la cartelera
function requiereAdmin(): void
{
    requiereLogin();

    if (($_SESSION['rol'] ?? '') !== 'admin') {
        header('Location: cartelera.php');
        exit;
    }
}

// Páginas de "gestión de contenido" (películas, salas, funciones, eventos):
// las puede usar tanto el admin como el editor. Todo lo demás (entradas y
// pagos, devoluciones, reportes y usuarios) sigue siendo exclusivo del admin.
function requiereGestion(): void
{
    requiereLogin();

    if (!in_array($_SESSION['rol'] ?? '', ['admin', 'editor'], true)) {
        header('Location: cartelera.php');
        exit;
    }
}

// Páginas exclusivas del usuario "normal": si el que está logueado es admin
// o editor, lo mandamos a su panel en vez de mostrarle la vista de comprador.
function bloquearAdmin(): void
{
    if (isset($_SESSION['usuario']) && in_array($_SESSION['rol'] ?? '', ['admin', 'editor'], true)) {
        header('Location: panel-admin.php');
        exit;
    }
}

function esAdmin(): bool
{
    return ($_SESSION['rol'] ?? '') === 'admin';
}

function esEditor(): bool
{
    return ($_SESSION['rol'] ?? '') === 'editor';
}

// Si ya hay una sesión iniciada, lo mandamos a su vista correspondiente
// (útil para que un usuario logueado no vea el login o el registro de nuevo).
function redirigirSiYaLogueado(): void
{
    if (isset($_SESSION['usuario'])) {
        $vaAlPanel = in_array($_SESSION['rol'] ?? '', ['admin', 'editor'], true);
        header('Location: ' . ($vaAlPanel ? 'panel-admin.php' : 'cartelera.php'));
        exit;
    }
}

// Compara una contraseña ingresada contra la guardada en la base.
// Las cuentas nuevas se guardan hasheadas (password_hash); las de demo
// que ya existían en la base están en texto plano, así que si la
// guardada no tiene pinta de hash, se compara directo.
function passwordCoincide(string $ingresada, string $guardada): bool
{
    $pareceHash = password_get_info($guardada)['algo'] !== null;

    return $pareceHash
        ? password_verify($ingresada, $guardada)
        : hash_equals($guardada, $ingresada);
}
