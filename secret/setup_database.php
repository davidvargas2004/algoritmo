<?php
// Archivo: setup_database.php

// --- Configuración del servidor MySQL (NO de la base de datos específica todavía) ---
// Necesitas un usuario con permisos para CREAR BASES DE DATOS y CREAR TABLAS
$server_host = 'localhost:3306'; // Puerto estándar de MySQL. Verifica si tu XAMPP usa este.
$server_user = 'root';           // Usuario común en XAMPP
$server_password = '';           // Contraseña por defecto vacía en XAMPP para root. ¡Cambiar en producción!

// --- Nombre de la Base de Datos y Tabla a crear ---
$dbname = 'phpdb';
$tablename = 'empleado';

// --- Sentencia SQL para CREAR LA BASE DE DATOS (si no existe) ---
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

// --- Sentencia SQL para CREAR LA TABLA EMPLEADO (si no existe) ---
$sql_create_table = "CREATE TABLE IF NOT EXISTS `$tablename` (
    documento VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    sexo CHAR(1) NOT NULL,
    domicilio VARCHAR(255),
    fechaingreso DATE,
    fechanacimiento DATE,
    sueldobasico DECIMAL(10, 2),
    estado_civil VARCHAR(50),
    tipo_sangre VARCHAR(5),
    usuario_red_social VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// --- Datos para insertar ---
$empleados_data = [
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000001', 'Ana Sofía Rojas Vega', 'F', 'Calle Falsa 123, Ciudad Capital', '2023-01-15', '1990-05-20', 2500000.00, 'Soltero/a', 'O+', '@anasofia.rojas');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000002', 'Carlos Alberto Pérez Gómez', 'M', 'Avenida Siempre Viva 742, Metrópolis', '2022-11-01', '1985-11-10', 3200000.50, 'Casado/a', 'A+', NULL);",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000003', 'Beatriz Elena Cadena Soler', 'F', 'Carrera 8 No. 10-25, Villa Esperanza', '2024-02-01', '1995-02-14', 1800000.75, 'Soltero/a', 'B-', '@bea.cadena');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000004', 'David Fernando López Mora', 'M', 'Transversal 5 Este No. 45-67, Pueblo Nuevo', '2021-07-20', '1978-12-03', 4500000.00, 'Divorciado/a', 'AB+', '@davidlopez');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000005', 'Laura Valentina Sierra Díaz', 'F', 'Calle 100 No. 15-30, Apartamento 501, Norte Grande', '2023-05-10', '1998-08-25', 2200000.00, 'Unión Libre', 'O-', NULL);",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000006', 'Juan Sebastián Vargas Ruiz', 'M', 'Manzana A Casa 1, Barrio El Progreso', '2020-03-01', '1992-03-17', 3800000.25, 'Casado/a', 'A-', '@juanse.vargas');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000007', 'Gabriela Lucía Méndez Cruz', 'F', 'Kilómetro 5 Vía Antigua, Finca El Recuerdo', '2024-01-05', '2000-01-30', 1600000.00, 'Soltero/a', 'B+', '@gaby_mendez');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000008', 'Andrés Mauricio Herrera Niño', 'M', 'Diagonal 22 No. 3-14, Sector Central', '2019-09-15', '1980-06-05', 5200000.00, 'Viudo/a', 'O+', NULL);",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000009', 'Paula Andrea Camacho Soto', 'F', 'Avenida Libertadores No. 78-02, Ciudad Jardín', '2022-06-01', '1993-07-11', 2900000.50, 'Soltero/a', 'AB-', '@paucamacho_s');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000010', 'Santiago José Quintero Lara', 'M', 'Callejón Oscuro Lote 3, Villa Serena', '2023-08-20', '1996-04-22', 2000000.00, 'Unión Libre', 'A+', '@santi.quintero');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000011', 'Isabella Rincón Paez', 'F', 'Urbanización Las Palmas, Torre B Apto 101', '2021-01-10', '1988-09-09', 3400000.00, 'Casado/a', 'B-', NULL);",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000012', 'Miguel Ángel Torres Pinto', 'M', 'Vereda El Carmen, Sector La Montaña', '2024-03-12', '1999-10-15', 1950000.80, 'Soltero/a', 'O-', '@migue.torres');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000013', 'Sofía Alejandra Gil Flórez', 'F', 'Carrera 15 No. 85-12, Oficina 302, Centro Empresarial', '2020-10-01', '1983-02-28', 4800000.00, 'Divorciado/a', 'A-', '@sofiagilflorez');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000014', 'Diego Armando Castro Silva', 'M', 'Calle Real No. 1-01, Pueblo Viejo', '2023-03-25', '1991-11-05', 2750000.20, 'Casado/a', 'AB+', NULL);",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000015', 'Valentina Ortiz Bravo', 'F', 'Conjunto Residencial El Roble, Casa 25', '2022-09-08', '1997-06-18', 2100000.00, 'Soltero/a', 'B+', '@vale_ortizb');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000016', 'Javier Alonso Becerra Leal', 'M', 'Avenida El Dorado No. 68C-61, Edificio Prisma', '2018-05-14', '1975-03-23', 6000000.00, 'Casado/a', 'O+', '@javier.becerra.l');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000017', 'Mariana Fajardo Cárdenas', 'F', 'Calle 4 Sur No. 30-11, Barrio Colombia', '2024-04-01', '2001-05-01', 1750000.50, 'Soltero/a', 'A+', NULL);",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000018', 'Ricardo Esteban Pardo Luna', 'M', 'Autopista Norte Km 18, Parque Industrial', '2019-02-20', '1986-07-07', 4200000.00, 'Unión Libre', 'AB-', '@ricardo.pardo86');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000019', 'Daniela Patricia Muñoz Beltrán', 'F', 'Circular 75 No. 39B-10, Laureles', '2021-11-11', '1994-01-19', 3100000.70, 'Casado/a', 'B-', '@danimunozb');",
    "INSERT INTO empleado (documento, nombre, sexo, domicilio, fechaingreso, fechanacimiento, sueldobasico, estado_civil, tipo_sangre, usuario_red_social) VALUES ('10000020', 'Luis Felipe Sarmiento Neira', 'M', 'Plaza Mayor No. 1, Centro Histórico', '2023-10-30', '1982-12-12', 3900000.00, 'Divorciado/a', 'O-', '@luisf.sarmiento');"
];

$pdo_db = null; // Inicializar para asegurar que existe fuera del try si la conexión inicial falla

try {
    // 1. Conectar al servidor MySQL (SIN especificar la base de datos inicialmente)
    $pdo_server = new PDO("mysql:host=$server_host", $server_user, $server_password);
    $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión al servidor MySQL exitosa.<br>\n";

    // 2. Ejecutar la sentencia para crear la base de datos
    $pdo_server->exec($sql_create_db);
    echo "Base de datos `$dbname` verificada/creada exitosamente.<br>\n";

    // Cerrar la conexión al servidor (ya no la necesitamos para crear la BD)
    $pdo_server = null;

    // 3. Ahora conectar a la base de datos específica que acabamos de crear/verificar
    $pdo_db = new PDO("mysql:host=$server_host;dbname=$dbname;charset=utf8mb4", $server_user, $server_password);
    $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "Conexión a la base de datos `$dbname` exitosa.<br>\n";

    // 4. Ejecutar la sentencia para crear la tabla dentro de esa base de datos
    $pdo_db->exec($sql_create_table);
    echo "Tabla `$tablename` verificada/creada exitosamente dentro de `$dbname`.<br>\n";

    // 5. Insertar los datos de los empleados
    echo "Iniciando inserción de datos de empleados...<br>\n";
    $registros_insertados = 0;
    foreach ($empleados_data as $insert_query) {
        try {
            $pdo_db->exec($insert_query);
            $registros_insertados++;
        } catch (\PDOException $e) {
            // Si un registro ya existe (basado en PRIMARY KEY 'documento'), podría fallar.
            // Puedes decidir si ignorar el error, loggearlo, o detener el script.
            // Por ahora, solo mostraremos un aviso y continuaremos con los demás.
            echo "Aviso: No se pudo insertar un registro (posiblemente ya existe o hay un error de datos): " . $e->getMessage() . "<br>\n";
            echo "Query problemática: " . htmlentities($insert_query) . "<br>\n";
        }
    }
    echo "$registros_insertados registros de empleados procesados/insertados exitosamente en la tabla `$tablename`.<br>\n";

    echo "<br><strong>¡Configuración de base de datos e inserción de datos completada!</strong><br>\n";

} catch (\PDOException $e) {
    // Capturar y mostrar errores
    die("Error durante la configuración o inserción en la base de datos: " . $e->getMessage() . "<br>\n");
    // En un entorno de producción, deberías loggear el error en lugar de usar die()
} finally {
    // Cerrar la conexión a la base de datos al finalizar, si está abierta
    if ($pdo_db !== null) {
        $pdo_db = null;
        echo "Conexión a la base de datos `$dbname` cerrada.<br>\n";
    }
}

?>


