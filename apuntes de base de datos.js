// // Desarrollo de base de datos

// que es una base de datos? recopilación organizada de datos e información, almacenada de forma electrónica y sistemática en un sistema informático, que permite a los usuarios almacenar, gestionar, acceder y recuperar información de manera rápida y segura.

// dato: Es una representación simbólica (numérica, alfabética, alfanumérica, etc.) de un hecho, evento o valor aislado que por si solo ¿, no tiene un significado claro o útil. Es la unidad mas pequeña de información.

// Campos: Es una ubicación especifica en una base de datos (columna) donde se almacena un tipo particular de dato (agrupa datos del mismo tipo o categoría). Representa una característica o atributo de una entidad.
//    Características:
//       1. tiene un nombre (por ejemplo "nombre", "edad", "correo").
//       2. tiene un tipo de dato (texto, numero, fecha, etc.).
//       3. almacena un solo dato por registro.

// Registro: Es un conjunto de campos relacionados que describen a una entidad especifica (una persona, producto, evento, etc.). También se conoce como fila o tupia en una tabla de base de datos.
//    Características:
//       1. agrupa varios campos.
//       2. representa un objeto o individuo real.
//       3. es una unidad completa de información sobre una entidad.
// Necesidades:
//       1.Redundancia
//       2.inconsistencia
//       3.dificultad para acceder
//       4.falta de integridad
//       5.problemas de seguridad

// Ventajas:
//      1.Control sobre la redundancia de datos
//      2.vonsistencia de datos
//      3.acceso y recuperación eficientes
//      4.integridad de datos
//      5.seguridad
//      6.compartir datos
//      7.eficiencia y rapidez en búsquedas
//      8.copias de seguridad (backups) y recuperación


// sistemas gestores de base de datos

// Los sistemas gestores de base de datos (SGBD) son software que permiten la creación, manipulación y administración eficiente de las base de datos. Estos sistemas proporcionan herramientas y funcionalidades avanzadas para el almacenamiento, organización, acceso y seguridad de los datos, lo que los convierte en una pieza fundamental de los sistemas de información modernos.

// Sistemas de información empresarial 
//    Las bases de datos son fundamentales para la gestión integrada de los recursos y procesos clave de una organización, como finanzas, recursos humanos, inventario y ventas.

// Aplicaciones web y mo0viles
//    Los SGBD permiten almacenar y acceder a los datos que respaldan el funcionamiento de sitios web, aplicaciones móviles y servicios en línea. proporcionan escalabilidad y alta disponibilidad

// Comercios electrónicos
//    Las bases de datos cruciales para gestionar el catalogo de productos, los pedidos de clientes, el inventario y la información de pago en plataformas de correo electrónicos.

// Entidades: es cualquier objeto, concepto, persona, lugar, evento o cosa real o abstracta sobre la que se necesita almacenar información en una base de datos.

// cada entidad posee una existencia independiente y se puede distinguir de otras entidades

// Ejemplos de entidades

// -persona: un individuo con atributos como nombre, edad, dirección.
// -producto: un articulo con atributos como nombre, precio, descripción.
// -evento: una ocasión con atributos como fecha, ubicación, participantes.

// caracteristicas de las entidades
// 1. representa algo unico y distinguible.
// 2. tiene atributos que describen sus propiedades.
// 3. puede tener relaciones con otras entidades.
// 4. En los modelos conceptuales, las entidades se representan gráficamente mediante rectángulos.

// Tipos de entidades

// 1. Entidades fuertes (o entidad independiente): son aquellas que existen de manera independiente y tienen una clave primaria que las identifica de forma única. Ejemplo: un empleado en una base de datos de recursos humanos o (cliente, producto, empleado).

// 2. Entidades débiles (o entidad dependiente): son aquellas que no pueden existir por sí solas y dependen de una entidad fuerte para su identificación. No tienen una clave primaria propia y se identifican mediante una clave parcial junto con la clave primaria de la entidad fuerte. Ejemplo: un detalle de pedido en una base de datos de ventas.


// ejemplo analogico:

// biblioteca

// libro (entidad fuerte)

// lector (entidad fuerte)

// ATRIBUTOS
// Es una característica o propiedad que describe a  una entidad.
// Cada atributo almacena un tipo especifico de información (nombre, edad, fecha, etc.) y puede tomar diferentes valores para distintas instancias de la entidad.
// En el modelo entidad-relación, los atributos se representan mediante óvalos conectados a la entidad correspondiente.

// EJEMPLOS DE ATRIBUTOS
// La entidad alumno puede tener atributos como, nombre, edad, dirección y numero de estudiante.

// TIPOS DE ATRIBUTO
// 1.-Atributo simple
// No se puede dividir en subcomponentes mas pequeños.
// Ejemplo: nombre, edad, matricula.
// 2-.Atributo compuesto
// Se puede dividir en subcomponente mas pequeños.
// Ejemplo: dirección (calle, ciudad, código postal).
// 3.-Atributo inivaluado
// Solo puede tener un valor para una instancia de la entidad.
// Ejemplo: numero de identificación, fecha de nacimiento.
// 4.-Atributo multivaluado
// Puede tener múltiples valores para una instancia de la entidad.
// Ejemplo: números de teléfono, direcciones de correo electrónico.
// 5.-Atributo derivado
// Su valor se puede calcular o derivar de otros atributos.
// Ejemplo: edad(derivado de la fecha de nacimiento).
// 6.-Atributo nulo
// No tiene ningún valor asignado.
// Ejemplo: un campo de dirección que no se ha completado.
// 7.-Atributo clave
// Es un atributo o un conjunto de atributos que identifican de manera única a una entidad dentro de un conjunto de entidades.
// Debe ser único y no nulo.
// Ejemplo: numero de identificación, matricula de estudiante.

// EJEMPLOS DE ATRIBUTOS

// Piensa en una tarjeta de identidad:
 
// la persona es una entidad
// su nombre, fecha de nacimiento, dirección son atributos.
// el numero de identificación es un atributo clave.
// Si la tarjeta incluye varios números de teléfono, ese es un atributo multivaluado.

// Ejemplo practico:

// Entidad: profesor
// Atributo simple: nombre, etc.
// Atributo compuesto: dirección (calle, ciudad, código postal). nivel academico (licensiatura, maestria, doctorado).
// Atributo univaluado: numero de empleado.
// Atributo multivaluado: numero de teléfono, correos institucional.
// Atributo derivado: antiguedad (calculada apartir de la fecha de contratación).
// clave primaria: RFC (registro federal de contribuyentes).


// relaciones

// es una asociación entre dos o mas entidades y representa como interactúan o se conectan las entidades dentro del dominio del problema que se esta modelando

// en el modelo entidad-relación, las relaciones se representan mediante rombos conectados a las entidades paerticipantes
// ejemplos de relaciones

// en una escuela, eexiste unarelación entre las entidades "estudiante" y "inscripción", por que los estudiantes se inscriben en materias.

// en una tienda, existe una relación entre las entidad "cliente" y "pedido", por que los clientes realizan pedidos de productos.

// En una empresa, existe una relación entre las entidades "empleado" y "proyecto", por que los empleados trabajan en proyectos específicos.

// cardinalidades

// Deefine cuantas instancias de una entidad pueden estar asociadas con instancias de otra entidad los tipos mas comunes son:

// 1.-uno a uno (1:1)
// una instancia de la entidad A se asocia con una sola instancia de la entidad B, y viceversa.
// Maestro <--> Cubiculo (si cada maestro tiene un cubiculo asignado y cada cubiculo pertenece a un solo maestro).

// 2.-uno a muchos (1:N)
// una instancia de la entidad A se asocia con múltiples instancias de la entidad B, pero una instancia de la entidad B se asocia con una sola instancia de la entidad A.
// departamento --> empleados (un departamento tiene muchos empleados, pero cada empleado pertenece a un solo departamento).
//profesor --> imparte --> materias (un profesor puede impartir varias materias, pero cada materia es impartida por un solo profesor).

// 3.-muchos a muchos (M:N)
// múltiples instancias de la entidad A se asocian con múltiples instancias de la entidad B, y viceversa.
// estudiantes <--> cursos (un estudiante puede inscribirse en varios cursos, y un curso puede tener varios estudiantes inscritos).
// autores <--> libros (un autor puede escribir varios libros, y un libro puede tener varios autores).
// importante: en el modelo relacional, las relaciones , no se implementan directamente en una entidad asociativa o tabla intermedia que contiene claves foráneas de ambas entidades y puede incluir atributos adicionales si es necesario.

// la forma mas precisa de expresar cardinalidades es mediante un par (min, max) que indica el numero minimo y maximo de instancias que pueden participar en la relación.

// (0,1) cero o uno
// (1,1) exactamente uno
// (0,N) cero o muchos
// (1,N) uno o muchos

// ejemplos analogicos
//tarjeta de biblioteca
//cada lector puede tener una sola tarjeta activa (1:1)<<
// pero esa tarjeta solo pertenece a ese lector.

//prestamos
// un lector puede tener varios prestamos activos (1:N)
// pero cada prestamo solo pertenece a un lector.

// Ejemplos de cardinalidades en la vida cotidiana
// 1.- Uno a uno (1:1) — cuatro ejemplos cotidianos
// 1) Persona — Documento de identidad (DNI / pasaporte): cada persona tiene un documento de identidad único y ese documento identifica a una sola persona.
// 2) Vehículo — Placa de matrícula: cada vehículo tiene asignada una placa única; esa placa pertenece a un solo vehículo.
// 3) Empleado — Número de seguridad social: cada empleado recibe un número único que lo identifica en el sistema de seguridad social, sin que ese número pertenezca a otra persona.
// 4) Usuario — Perfil personal en una aplicación (cuando la política de la app limita a un perfil por usuario): un usuario tiene un perfil único y ese perfil corresponde a ese único usuario.

// 2.- Uno a muchos (1:N) — cuatro ejemplos cotidianos
// 1) Padre/Madre — Hijos: un padre puede tener varios hijos; cada hijo tiene en general un solo padre biológico (en el contexto de este ejemplo simplificado) o una referencia principal.
// 2) Cliente — Pedidos: un cliente puede hacer múltiples pedidos; cada pedido pertenece a un único cliente.
// 3) Tienda — Productos: una tienda vende muchos productos; cada producto registrado en esa tienda pertenece a una sola tienda (en el contexto de su catálogo propio).
// 4) Profesor — Estudiantes: un profesor puede dar clase a muchos estudiantes; cada estudiante puede tener asignado un profesor principal para una materia concreta.

// 3.- Muchos a muchos (M:N) — cuatro ejemplos cotidianos
// 1) Estudiante — Cursos: un estudiante puede matricularse en varios cursos; cada curso puede tener muchos estudiantes.
// 2) Usuario — Grupos de chat: un usuario puede ser miembro de varios grupos (WhatsApp, Telegram, etc.) y cada grupo tiene muchos usuarios.
// 3) Película — Actores: una película puede tener varios actores; un actor puede participar en muchas películas.
// 4) Pedido — Productos (carrito de compra): un pedido puede incluir varios productos, y cada producto puede aparecer en muchos pedidos distintos.

// Nota: en el modelo relacional, las relaciones M:N se implementan con una tabla intermedia (entidad asociativa) que contiene las claves foráneas de ambas tablas y posibles atributos adicionales (como cantidad, fecha, rol, etc.).
// autores y libros: un autor puede escribir varios libros, y un libro puede tener varios autores.

//



// creacion de base de datos
// CREATE DATABASE nombre_de_la_base_de_datos;

// uso de base de datos
// USE nombre_de_la_base_de_datos;

// creacion de tabla
// CREATE TABLE nombre_de_la_tabla (
//     nombre_del_campo tipo_de_dato restricciones,
//     nombre_del_campo tipo_de_dato restricciones,
//     ...
// );

// ejemplo de creacion de tabla
// CREATE TABLE estudiantes (
//     id INT PRIMARY KEY AUTO_INCREMENT,
//     nombre VARCHAR(100) NOT NULL,
//     edad INT,
//     correo VARCHAR(100) UNIQUE
// );

// insercion de datos
// INSERT INTO nombre_de_la_tabla (campo1, campo2, campo3, ...)
// VALUES (valor1, valor2, valor3, ...);

// ejemplo de insercion de datos
// INSERT INTO estudiantes (nombre, edad, correo)
// VALUES ('Juan Perez', 20, 'juan.perez@example.com');

// consulta de datos
// SELECT campo1, campo2, ...
// FROM nombre_de_la_tabla
// WHERE condiciones;

// ejemplo de consulta de datos
// SELECT nombre, edad
// FROM estudiantes
// WHERE edad > 18;

// Ver base de datos creadas
// SHOW DATABASES;

// Ver tablas creadas
// SHOW TABLES;

// Describir la estructura de una tabla
// DESCRIBE nombre_de_la_tabla;

// Actualizar datos
// UPDATE nombre_de_la_tabla
// SET campo1 = valor1, campo2 = valor2, ...
// WHERE condiciones;

// ejemplo de actualización de datos
// UPDATE estudiantes
// SET edad = 21
// WHERE nombre = 'Juan Perez';

// Eliminar datos
// DELETE FROM nombre_de_la_tabla
// WHERE condiciones;

// ejemplo de eliminación de datos
// DELETE FROM estudiantes
// WHERE nombre = 'Juan Perez';

// Eliminar una tabla
// DROP TABLE nombre_de_la_tabla;

// Eliminar una base de datos
// DROP DATABASE nombre_de_la_base_de_datos;

// tipo de datos
// TIPOS DE DATOS
// 1. Numéricos
//    - INT: para números enteros.
//    - FLOAT: para números con decimales.
//    - DOUBLE: para números con mayor precisión que FLOAT.
//    - BIGINT: para números enteros muy grandes.
//    - SMALLINT: para números enteros pequeños.
//    - TINYINT: para números enteros muy pequeños (0 a 255).
//    - DECIMAL: para números con precisión fija (útil para valores monetarios).
// 2. Cadenas de texto
//    - VARCHAR(n): para cadenas de texto de longitud variable, donde n es el número máximo de caracteres.
//    - CHAR(n): para cadenas de texto de longitud fija, donde n es el número exacto de caracteres.
//    - TEXT: para cadenas de texto largas.
//    - ENUM: para valores predefinidos (por ejemplo, 'masculino', 'femenino').
//     - BLOB: para datos binarios grandes (imágenes, archivos, etc.).
// 3. Fechas y horas
//    - DATE: para fechas (año-mes-día).
//    - DATETIME: para fechas y horas (año-mes-día hora:minuto:segundo).
//    - TIMESTAMP: para marcas de tiempo (fecha y hora con zona horaria).
// 4. Booleanos
//    - BOOLEAN: para valores de verdadero o falso (1 o 0).
// 5. Otros tipos
//    - ENUM: para valores predefinidos (por ejemplo, 'masculino', 'femenino').
//    - BLOB: para datos binarios grandes (imágenes, archivos, etc.).
