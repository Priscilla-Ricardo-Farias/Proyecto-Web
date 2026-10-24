# Plan de pruebas · CineSystem

- **Fecha del plan:** 24/10/2026
- **Alcance:** guardias de las páginas que escriben en la base, validación en PHP (campo vacío, tipo equivocado, valor fuera de rango), reglas `NOT NULL` / `UNIQUE` de la base, avisos en castellano y validación del navegador con los mismos textos.
- **Cómo se prueba:** abrir el sistema en XAMPP (`http://localhost/workspace/`), cargar `db.sql` y entrar con las cuentas de prueba:

| rol | email | contraseña |
|---|---|---|
| usuario | `juan@test.com` | `1234` |
| editor | `editor@test.com` | `editor123` |
| admin | `admin@test.com` | `admin123` |

- **Capa:** **NAV** = lo detecta el navegador antes de enviar (con JavaScript) · **PHP** = lo rechaza el servidor (se prueba desactivando JavaScript, o mandando el formulario a mano) · **BD** = lo rechaza MySQL (se prueba en phpMyAdmin, pestaña SQL) · **GUARDIA** = lo frena el control de rol/método al principio del archivo.
- **Resultado:** `Pasó` / `Falló` + fecha de ejecución. Los casos NAV marcados con 🤖 están cubiertos por `node tests/validar-formulario.test.js` y **se ejecutaron el 24/10/2026: 22 de 22 pasaron**. El resto figura **Pendiente** hasta correrlo en el servidor local; se completa la última columna con la fecha en que se hace.
- **Regla de aviso:** todos los rechazos muestran el aviso en castellano indicado, y los de PHP y NAV usan **el mismo texto**.

## 1. Guardias (casos negativos)

| N° | Qué se hace | Esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| G1 | Sin iniciar sesión, abrir `/workspace/procesar/procesar_pelicula.php` | Redirige al login; no cambia nada | GUARDIA | Pendiente |
| G2 | Como `juan` (usuario), enviar un POST a `procesar_sala.php` | Redirige fuera del panel; no se crea la sala | GUARDIA | Pendiente |
| G3 | Como `editor`, enviar un POST a `procesar_usuario.php` | Rechazado: el editor no administra cuentas | GUARDIA | Pendiente |
| G4 | Como `admin`, abrir cada `procesar/*.php` escribiendo la dirección (GET) | Vuelve a la lista correspondiente sin cambios | GUARDIA | Pendiente |
| G5 | Abrir `compra-exitosa.php` por la barra de direcciones (GET) | Vuelve a la cartelera; no se crea ninguna entrada | GUARDIA | Pendiente |
| G6 | Abrir `devolver-entrada.php` por GET | Vuelve a "Mis entradas"; no se devuelve nada | GUARDIA | Pendiente |
| G7 | Como `admin`, intentar comprar (abrir `seleccionar-funcion.php`) | Bloqueado: admin/editor no compran | GUARDIA | Pendiente |

## 2. Login y registro

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| L1 | Login con email y contraseña vacíos | «Completá el campo «Email».» y «Completá el campo «Contraseña».» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| L2 | Login con email `juan.com` | «El campo «Email» tiene que ser un email válido, por ejemplo nombre@dominio.com.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| L3 | Login con email correcto y clave equivocada | «El email o la contraseña no son correctos.» | PHP | Pendiente |
| R1 | Registro con todo vacío | Un aviso «Completá el campo «…».» por cada campo | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| R2 | Registro con contraseña de 5 caracteres | «El campo «Contraseña» tiene que tener al menos 6 caracteres.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| R3 | Registro con contraseñas distintas | «Las contraseñas no coinciden.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| R4 | Registro con nombre de 101 caracteres | «El campo «Nombre» no puede tener más de 100 caracteres.» | NAV / PHP | Pendiente |
| R5 | Registro con un email que ya existe (`juan@test.com`) | «Ya existe una cuenta registrada con ese email.» | PHP | Pendiente |
| R6 | Registro correcto | Entra logueado a la cartelera | — (positivo) | Pendiente |

## 3. Usuarios (admin)

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| U1 | Crear cuenta con rol manipulado (`rol=superadmin`) | «El valor elegido en «Rol» no es válido.» | PHP | Pendiente |
| U2 | Crear cuenta con email repetido | «Ya existe una cuenta registrada con ese email.» | PHP + BD (`UNIQUE email`) | Pendiente |
| U3 | Cambiar el rol de la propia cuenta | «No podés cambiar el rol ni eliminar tu propia cuenta desde acá.» | PHP | Pendiente |
| U4 | Bajar de rol al único administrador | «Tiene que quedar al menos un administrador: no se puede dejar el sistema sin ninguno.» | PHP | Pendiente |
| U5 | Cambiar rol con `id=abc` (enviado a mano) | «El elemento indicado no es válido.» | PHP | Pendiente |
| U6 | Eliminar una cuenta con `id=9999` | «La cuenta que querés eliminar no existe.» | PHP | Pendiente |

## 4. Películas (admin / editor)

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| P1 | Título vacío | «Completá el campo «Título».» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| P2 | Título con solo espacios | «Completá el campo «Título».» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| P3 | Título de 101 caracteres | «El campo «Título» no puede tener más de 100 caracteres.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| P4 | Año de lanzamiento `dos mil` | «El campo «Año de lanzamiento» tiene que ser un número entero.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| P5 | Año `1500` (y `2101`) | «El campo «Año de lanzamiento» tiene que estar entre 1888 y 2100.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| P6 | Duración `0` (y `601`) | «El campo «Duración (minutos)» tiene que estar entre 1 y 600.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| P7 | Duración `90.5` | «El campo «Duración (minutos)» tiene que ser un número entero.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| P8 | Sinopsis de 151 caracteres | «El campo «Descripción / sinopsis» no puede tener más de 150 caracteres.» | NAV / PHP | Pendiente |
| P9 | Crear «Coco» 2017 otra vez | «Ya existe una película con ese título y año de lanzamiento.» | PHP + BD (`UNIQUE nombre, lanzamiento`) | Pendiente |
| P10 | Eliminar una película que ya tiene entradas vendidas | «No se puede eliminar: esta película ya tiene entradas vendidas. Desactivala para sacarla de la cartelera.» | PHP | Pendiente |
| P11 | Activar/desactivar con `id=9999` | «La película indicada no existe.» | PHP | Pendiente |
| P12 | Datos correctos (título, año 2017, duración 105) | Se crea y aparece en la lista | — (positivo) | Pendiente |

## 5. Salas

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| S1 | Nombre vacío | «Completá el campo «Nombre / número de sala».» | NAV / PHP | Pendiente |
| S2 | Filas `0` (y `51`) | «El campo «Cantidad de filas» tiene que estar entre 1 y 50.» | NAV / PHP | Pendiente |
| S3 | Butacas por fila `abc` | «El campo «Butacas por fila» tiene que ser un número entero.» | NAV / PHP | Pendiente |
| S4 | Capacidad `2001` | «El campo «Capacidad total» tiene que estar entre 1 y 2000.» | NAV / PHP | Pendiente |
| S5 | Tipo manipulado (`tipo=imax`) | «El valor elegido en «Tipo de sala» no es válido.» | PHP | Pendiente |
| S6 | Crear otra sala llamada «Sala 1» | «Ya existe una sala con ese nombre.» | PHP + BD (`UNIQUE nombre`) | Pendiente |
| S7 | Eliminar una sala que tiene funciones | «No se puede eliminar: esta sala tiene funciones programadas. Marcala en mantenimiento en su lugar.» | PHP | Pendiente |

## 6. Funciones y ciclos

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| F1 | Sin elegir sala | «Elegí una opción en «Sala».» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| F2 | Película/sala con `id=0` (enviado a mano) | «El valor elegido en «Sala» no es válido.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| F3 | Hora de fin igual o anterior a la de inicio | «La hora de finalización debe ser posterior a la hora de inicio.» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| F4 | Hora de inicio `25:00` | «El campo «Hora de inicio» tiene que ser una hora válida (HH:MM).» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| F5 | Fecha `2026-02-30` | «El campo «Fecha» tiene que ser una fecha válida (AAAA-MM-DD).» | PHP | Pendiente |
| F6 | Precio `-100` | «El campo «Precio de la entrada» tiene que estar entre 0 y 1000000.» | NAV / PHP | Pendiente |
| F7 | Estado manipulado (`estado=borrada`) | «El valor elegido en «Estado de la función» no es válido.» | PHP | Pendiente |
| F8 | Película inexistente (`pelicula_id=9999`) | «La película elegida no existe.» | PHP | Pendiente |
| F9 | Misma sala, fecha y hora de inicio que otra función | «Esa sala ya tiene una función ese día a esa hora de inicio.» | PHP + BD (`UNIQUE sala, fecha, hora_inicio`) | Pendiente |
| F10 | Datos correctos | Se crea y aparece en la lista | — (positivo) | Pendiente |
| C1 | Ciclo sin «Horario 1» | «Completá el campo «Horario 1».» | NAV / PHP | Pendiente |
| C2 | Ciclo con «Hasta el día» anterior a «Desde el día» | «La fecha «Hasta el día» no puede ser anterior a «Desde el día».» | NAV / PHP | Pendiente |
| C3 | Ciclo de más de 31 días | «El rango de fechas no puede pasar de 31 días.» | PHP | Pendiente |
| C4 | Ciclo en una sala en mantenimiento (enviado a mano) | «Elegí una sala disponible.» | PHP | Pendiente |

## 7. Eventos

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| E1 | Título vacío | «Completá el campo «Título».» | NAV / PHP | Pendiente |
| E2 | Tipo manipulado (`tipo=fiesta`) | «El valor elegido en «Tipo de evento» no es válido.» | PHP | Pendiente |
| E3 | Fecha de fin anterior a la de inicio | «La fecha de fin no puede ser anterior a la fecha de inicio.» | NAV / PHP | Pendiente |
| E4 | Lista de películas con un id inexistente (`peliculas_ids[]=9999`) | «Alguna de las películas elegidas no existe.» | PHP | Pendiente |
| E5 | Descripción de 501 caracteres | «El campo «Descripción» no puede tener más de 500 caracteres.» | NAV / PHP | Pendiente |

## 8. Compra y pago

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| Pg1 | Confirmar compra sin butacas (POST sin `asientos`) | «Elegí al menos una butaca para continuar.» | PHP | Pendiente |
| Pg2 | Butaca inexistente (`asientos=A11`) | «Alguno de los asientos elegidos no es válido.» | PHP | Pendiente |
| Pg3 | Número de tarjeta de 15 dígitos | «El número de tarjeta tiene que tener entre 16 y 19 dígitos (podés usar espacios).» | NAV / PHP | Pendiente |
| Pg4 | Vencimiento `13/30` | «El vencimiento tiene que tener el formato MM/AA, por ejemplo 08/29.» | NAV / PHP | Pendiente |
| Pg5 | Vencimiento `01/20` (tarjeta vencida) | «La tarjeta está vencida.» | PHP | Pendiente |
| Pg6 | CVV de 2 números | «El código de seguridad (CVV) tiene que tener 3 o 4 números.» | NAV / PHP | Pendiente |
| Pg7 | Nombre en la tarjeta vacío | «Completá el campo «Nombre en la tarjeta».» | NAV / PHP | Pendiente |
| Pg8 | Dos usuarios eligen la misma butaca; el segundo paga después | «Uno de los asientos que elegiste (…) se acaba de ocupar. Elegí otro.» | PHP | Pendiente |
| Pg9 | Función inexistente (`funcion=9999`) | «La función elegida ya no está disponible. Elegí otra para continuar.» | PHP | Pendiente |
| Pg10 | Compra correcta | Aparece el código de 6 caracteres y las butacas quedan ocupadas | — (positivo) | Pendiente |
| Pg11 | Antes de comprar, mirar el mapa de una función nueva | Las 50 butacas aparecen libres | — (positivo) | Pendiente |

## 9. Devoluciones

| N° | Entrada | Aviso esperado | Capa | Resultado / fecha |
|---|---|---|---|---|
| D1 | «Mis entradas»: código vacío | «Completá el campo «Código de la entrada».» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| D2 | Código `AB-12!` | «El código de la entrada tiene entre 4 y 10 caracteres (solo letras y números).» | NAV 🤖 / PHP | NAV: Pasó 24/10/2026 · PHP: Pendiente |
| D3 | Código en minúsculas (`4f92a1`) de la entrada correcta | Se acepta (se pasa a mayúsculas) | NAV 🤖 | Pasó 24/10/2026 |
| D4 | Código de otra entrada propia | «El código ingresado no coincide con el de la entrada. Revisá el código que te dieron al comprar.» | PHP | Pendiente |
| D5 | Enviar a mano el `entrada_id` de una entrada de otro usuario | «Esa entrada no existe o no es tuya.» | PHP | Pendiente |
| D6 | Devolver una entrada cuya función ya empezó | «La entrada … no se puede devolver porque la función ya empezó.» (y no aparece el botón) | PHP | Pendiente |
| D7 | Devolver dos veces la misma entrada | «Esa entrada ya fue devuelta» | PHP | Pendiente |
| D8 | Devolver antes de la función con el código correcto | «Entrada devuelta»; las butacas quedan libres | — (positivo) | Pendiente |
| D9 | Panel admin: código vacío | «Completá el campo «Código de la entrada».» | NAV / PHP | Pendiente |
| D10 | Panel admin: código que no existe | «Código no encontrado» | PHP | Pendiente |
| D11 | Panel admin: entrada de una función que ya pasó | «La función ya pasó» | PHP | Pendiente |

## 10. Reglas de la base (probar en phpMyAdmin, pestaña SQL)

| N° | Sentencia | Error esperado de MySQL | Regla | Resultado / fecha |
|---|---|---|---|---|
| B1 | `INSERT INTO usuarios (nombre,email,password) VALUES ('X','juan@test.com','1')` | 1062 Duplicate entry | `UNIQUE email` | Pendiente |
| B2 | `INSERT INTO peliculas (nombre) VALUES (NULL)` | 1048 Column 'nombre' cannot be null | `NOT NULL nombre` | Pendiente |
| B3 | `INSERT INTO peliculas (nombre,lanzamiento) VALUES ('Coco',2017)` | 1062 Duplicate entry | `UNIQUE nombre, lanzamiento` | Pendiente |
| B4 | `INSERT INTO salas (nombre) VALUES ('Sala 1')` | 1062 Duplicate entry | `UNIQUE nombre` | Pendiente |
| B5 | Dos `INSERT INTO funciones` con la misma sala, fecha y `hora_inicio` | 1062 Duplicate entry en el segundo | `UNIQUE sala, fecha, hora_inicio` | Pendiente |
| B6 | Dos `INSERT INTO entradas` con el mismo `codigo` | 1062 Duplicate entry en el segundo | `UNIQUE codigo` | Pendiente |
| B7 | `INSERT INTO entradas (...,usuario,...) VALUES (...,NULL,...)` | 1048 Column 'usuario' cannot be null | `NOT NULL usuario` | Pendiente |
| B8 | `INSERT INTO directores (nombre) VALUES ('Lee Unkrich')` | 1062 Duplicate entry | `UNIQUE nombre` | Pendiente |

## 11. Cómo repetir las pruebas automáticas
```
node tests/validar-formulario.test.js
```
Salida esperada: `Todos los casos pasaron (22).` Ejecutado el 24/10/2026: 22 de 22.
