<?php
// La elección de sala ahora va junto con fecha y hora en "seleccionar-funcion.php".
// Este archivo queda sólo para que los links viejos sigan funcionando.
$id = $_GET['id'] ?? '';
header('Location: seleccionar-funcion.php?id=' . urlencode($id));
exit;
