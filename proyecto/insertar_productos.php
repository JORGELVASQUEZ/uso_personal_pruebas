<?php
// Script para insertar productos y ofertas en la tabla productos
// Ajusta los datos de conexión según tu entorno
$host = 'localhost';
$db   = 'order_flow';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

$sql = "INSERT INTO productos (id, nombre, precio, precio_descuento, imagen, categoria, descripcion, vendedor_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ";
$stmt = $pdo->prepare($sql);

foreach ($productos as $p) {
    try {
        $stmt->execute($p);
        echo "Insertado: {$p[1]}<br>";
    } catch (PDOException $e) {
        echo "Error con {$p[1]}: " . $e->getMessage() . "<br>";
    }
}

echo "<br>Proceso finalizado.";
