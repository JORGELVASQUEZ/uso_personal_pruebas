<?php
// Product detail: intenta cargar desde la tabla `productos` si existe, sino deja el contenedor vacío y main.js lo manejará.
$product = null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=order_flow;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $stmt = $pdo->prepare('SELECT p.*, v.store_name AS seller_name, v.store_image AS seller_image FROM productos p LEFT JOIN vendedor v ON p.vendedor_phone = v.numero_telefono WHERE p.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
    } catch (Throwable $e) {
        $product = null;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle del Producto</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Agregar Font Awesome para los íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header (unificado con index.php) -->
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-bolt logo-icon"></i>
                        <h1>RapiMarket</h1>
                    </a>
                </div>

                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar productos...">
                </div>

                <div class="header-actions">
                    <div id="auth-buttons"></div>
                    <div class="cart-icon" id="open-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </div>
                    <div class="hamburger">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </div>
            

            <div class="header-bottom">
                <ul class="categories">
                    <li><a href="products.php" class="active">Todos</a></li>
                    <li><a href="products.php?category=supermercado">Supermercado</a></li>
                    <li><a href="products.php?category=bebidas">Bebidas</a></li>
                    <li><a href="products.php?category=lacteos">Lácteos</a></li>
                    <li><a href="products.php?category=snacks">Snacks</a></li>
                </ul>
            </div>
        </div>
    </header>

    <main class="container">
        <div id="product-detail" class="product-detail">
            <?php if ($product): ?>
                <div class="product-detail-card">
                    <div class="detail-image"><img src="<?php echo htmlspecialchars($product['imagen'] ?: $product['seller_image'] ?: ''); ?>" alt="<?php echo htmlspecialchars($product['nombre']); ?>"></div>
                    <div class="detail-info">
                        <h2><?php echo htmlspecialchars($product['nombre']); ?></h2>
                        <div class="detail-price">
                            <?php if (!empty($product['descuento_activo'])): ?>
                                <span class="price-discount">$<?php echo number_format((float)$product['precio_descuento'], 2); ?></span>
                                <span class="price-original">$<?php echo number_format((float)$product['precio'], 2); ?></span>
                            <?php else: ?>
                                <span class="price">$<?php echo number_format((float)$product['precio'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="detail-description"><?php echo nl2br(htmlspecialchars($product['descripcion'] ?? '')); ?></p>
                        <?php if (!empty($product['seller_name'])): ?>
                            <div class="detail-seller">Vendido por: <?php echo htmlspecialchars($product['seller_name']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Si no se encontró producto en BD, main.js puede manejar la renderización desde su array local -->
            <?php endif; ?>
        </div>
    </main>

    <!-- Carrito de compras (shared) -->
    <div class="overlay" id="overlay"></div>
    <aside id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h3>Tu Carrito</h3>
            <button id="close-cart" class="btn">Cerrar</button>
        </div>
        <div id="cart-items" class="cart-items"></div>
        <div class="cart-footer">
            <div>Total: <span id="cart-total">$0.00</span></div>
            <button class="checkout-btn btn">Pagar</button>
        </div>
    </aside>

    <script src="js/main.js"></script>
</body>
</html>