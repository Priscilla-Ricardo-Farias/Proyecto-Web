<?php
// Cabecera compartida por todo el panel (admin y editor).
// El menú ya no va arriba: es una barra lateral fija a la izquierda.

$paginaActual = basename($_SERVER['SCRIPT_NAME']);

// Las páginas internas (formularios, etc.) resaltan la sección a la que pertenecen
$seccionDe = [
    'admin-pelicula-form.php'    => 'admin-peliculas.php',
    'admin-sala-form.php'        => 'admin-salas.php',
    'admin-funcion-form.php'     => 'admin-funciones.php',
    'admin-evento-form.php'      => 'admin-eventos.php',
    'admin-evento-funciones.php' => 'admin-eventos.php',
    'admin-usuario-form.php'     => 'admin-usuarios.php',
];
$seccionActual = $seccionDe[$paginaActual] ?? $paginaActual;

// Cada item: [archivo, ruta, ícono, texto]
$menuCatalogo = [
    ['panel-admin.php',       '/workspace/panel-admin.php',                 '🏠', 'Panel'],
    ['admin-peliculas.php',   '/workspace/admin/admin-peliculas.php',       '🎞️', 'Películas'],
    ['admin-salas.php',       '/workspace/admin/admin-salas.php',           '🪑', 'Salas'],
    ['admin-funciones.php',   '/workspace/admin/admin-funciones.php',       '🕒', 'Funciones'],
    ['admin-eventos.php',     '/workspace/admin/admin-eventos.php',         '⭐', 'Eventos'],
];

$menuAdmin = [
    ['admin-entradas.php',     '/workspace/admin/admin-entradas.php',      '🎟️', 'Entradas y pagos'],
    ['admin-devoluciones.php', '/workspace/admin/admin-devoluciones.php',  '↩️', 'Devoluciones'],
    ['admin-reportes.php',     '/workspace/admin/admin-reportes.php',      '📊', 'Reportes'],
    ['admin-usuarios.php',     '/workspace/admin/admin-usuarios.php',      '👥', 'Usuarios'],
];

if (!function_exists('itemSidebarAdmin')) {
function itemSidebarAdmin(array $item, string $seccionActual): void
{
    [$archivo, $ruta, $icono, $texto] = $item;
    $activo = $archivo === $seccionActual;
    ?>
    <a href="<?= $ruta ?>" class="sidebar-link<?= $activo ? ' activo' : '' ?>"<?= $activo ? ' aria-current="page"' : '' ?>>
        <span class="sidebar-icono" aria-hidden="true"><?= $icono ?></span>
        <span><?= $texto ?></span>
    </a>
    <?php
}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineSystem · Admin</title>
    <link rel="stylesheet" href="/workspace/css/styles.css">
</head>

<body class="admin-con-sidebar">

<!-- En pantallas chicas el menú se abre con este botón (sin JavaScript) -->
<input type="checkbox" id="sidebar-toggle" class="sidebar-toggle-input" aria-label="Abrir menú">
<label for="sidebar-toggle" class="sidebar-toggle-btn">
    <span class="sidebar-toggle-icono" aria-hidden="true">☰</span>
    <span>CINE<b>SYSTEM</b></span>
</label>

<aside class="sidebar-admin">

    <a href="/workspace/panel-admin.php" class="sidebar-brand">
        <span class="brand-icono">🎬</span>
        <span>CINE<b>SYSTEM</b></span>
    </a>

    <nav class="sidebar-nav" aria-label="Menú del panel">

        <p class="sidebar-grupo">Catálogo</p>
        <?php foreach ($menuCatalogo as $item) { itemSidebarAdmin($item, $seccionActual); } ?>

        <?php if (esAdmin()): ?>
            <p class="sidebar-grupo">Administración</p>
            <?php foreach ($menuAdmin as $item) { itemSidebarAdmin($item, $seccionActual); } ?>
        <?php endif; ?>

    </nav>

    <div class="sidebar-pie">
        <span class="navbar-admin-badge"><?= esAdmin() ? 'Admin' : 'Editor' ?></span>
        <a href="/workspace/logout.php" class="sidebar-salir">Salir</a>
    </div>

</aside>
