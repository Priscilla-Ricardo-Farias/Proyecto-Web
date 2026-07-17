# Documentación del sistema

## 1. El sistema
- Nombre: CineSystem
- Qué problema resuelve: mostrar la cartelera de películas y la ficha de cada una.
- Usuarios: personas que ingresan (con login) a consultar qué películas hay, su director, duracion y sinopsis.

## 2. Entidades y datos
Entidad principal: peliculas

| campo    | tipo (MySQL)          |             ejemplo              |
|----------|-----------------------|----------------------------------|
| id       | INT PK AUTO_INCREMENT |                 1                |
| titulo   | VARCHAR(100)          | Matrix                           |
| anio     | INT                   | 1999                             |
| genero   | VARCHAR(50)           | Ciencia ficción                  |
| director | VARCHAR(50)           | Lana y Lilly Wachowski           |
| sinopsis | VARCHAR(150)          | Un programador descubre que      |
|          |                       | el mundo que conoce es una       |
|          |                       | simulación creada por máquinas y |
|          |                       | decide unirse a la resistencia   |
|          |                       | para liberar a la humanidad.     |

(En datos.php cada película es un array: las claves 'titulo', 'anio'... = estas columnas.)

## 3. Archivos del proyecto
| archivo       | para qué sirve                                |
|---------------|-----------------------------------------------|
| index.php     | login: pide usuario y clave                   |
| cabecera.php  | menú y encabezado, incluido en cada página    |
| pie.php       | pie de página, incluido en cada página        |
| datos.php     | array con todas las películas                 |
| cartelera.php | recorre datos.php con foreach y arma la lista |
| ficha.php     | muestra una película por su id (lookup)       |
| db.sql        | script para crear la tabla y cargar datos     |

## 4. Mapa del flujo
login -> sesion -> pagina protegida -> include cabecera ->
cartelera (foreach sobre datos.php) -> click -> ficha (lookup)

## 5. Estado actual
- [x] Login + sesion + proteccion
- [x] Includes + navegacion
- [x] Cartelera (foreach)
- [x] Ficha (lookup)
- [x] Tabla MySQL (CREATE + INSERT + SELECT)
- [x] Repositorio actualizado
- [x] Segunda entidad: director (relacion pelicula -> director)
- [x] Buscador de peliculas (NULL)

## 6. Proximas etapas
- [ ] Conexion PHP-MySQL: que la cartelera lea la tabla
- [ ] Login real (usuarios en la base) y validaciones
- [ ] Simulación de cine (salas y funciones)
- [ ] Simulación de compra de entradas
- [ ] Simulación de reserva de butacas
- [ ] Simulación de funciones por horario
- [ ] Pruebas y documentacion final + defensa