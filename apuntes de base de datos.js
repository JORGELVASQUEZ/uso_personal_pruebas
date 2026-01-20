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




