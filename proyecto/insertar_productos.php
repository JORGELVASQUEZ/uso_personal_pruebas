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


$vendedor_phone = '529811352906';
$productos = [
    [1, 'Leche Entera 1L', 2.50, null, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Lácteos', 'Leche entera pasteurizada de alta calidad, rica en calcio y vitaminas.', $vendedor_phone],
    [2, 'Pan Integral 500g', 1.80, null, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Panadería', 'Pan integral elaborado con harina de trigo integral, rico en fibra.', $vendedor_phone],
    [3, 'Huevos Blancos 12 unid.', 3.20, null, 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Huevos', 'Huevos blancos frescos de gallinas criadas en libertad.', $vendedor_phone],
    [4, 'Arroz Extra 1kg', 2.10, null, 'https://www.vegaucero.com/multimedia/web/vega-ucero/catalogo/pro/5188/16008810781383125803.jpg', 'Granos', 'Arroz extra de grano largo, ideal para todo tipo de preparaciones.', $vendedor_phone],
    [5, 'Aceite de Girasol 1L', 3.50, null, 'https://aceitesandua.com/wp-content/uploads/2021/05/211fb1d06f9479a7650fc3bb47b93c8b.jpg', 'Aceites', 'Aceite de girasol 100% puro, ideal para freír y aderezar.', $vendedor_phone],
    [6, 'Yogurt Natural 1kg', 2.80, null, 'https://s1.abcstatics.com/media/bienestar/2019/07/26/yogur-ktEF--1248x698@abc.jpg', 'Lácteos', 'Yogurt natural sin azúcar añadido, rico en probióticos.', $vendedor_phone],
    [7, 'Pasta Spaghetti 500g', 1.60, null, 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Pastas', 'Pasta spaghetti de trigo duro, de cocción perfecta.', $vendedor_phone],
    [8, 'Tomates 1kg', 2.40, null, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Verduras', 'Tomates frescos y jugosos, cultivados localmente.', $vendedor_phone],
    // Ofertas
    [9, 'Coca-Cola 2L', 2.80, 2.20, 'https://images.unsplash.com/photo-1554866585-cd94860890b7?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Bebidas', 'Refresco Coca-Cola original en presentación familiar.', $vendedor_phone],
    [10, 'Papas Fritas 200g', 2.50, 1.90, 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Snacks', 'Papas fritas crujientes con sabor natural.', $vendedor_phone],
    [11, 'Chocolate 100g', 2.00, 1.50, 'https://perfectdailygrind.com/es/wp-content/uploads/sites/2/2020/04/Hs_5Ce8ecmXodh-AdEVHyT07irPaZ-zAAhYkKYRJgS5CVzHKs0cAAdyeAF9TIgyh4KI5gqYmyuIDwJnf2f9wCdNvJ5WbQOlSoRr5zmmzMalyR1-RQxvlOtTZkJq9G_GPUiVZ6_WX-1.jpeg', 'Snacks', 'Chocolate con leche de alta calidad.', $vendedor_phone],
    [12, 'Galletas Integrales', 1.80, 1.30, 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60', 'Snacks', 'Galletas integrales con avena y miel.', $vendedor_phone],
];


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
