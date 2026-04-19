<?php
// Página de listado de productos: renderiza los productos desde la tabla `productos` si existe.
$products = [];
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=order_flow;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Intentamos leer productos y la info del vendedor
    $stmt = $pdo->query('SELECT p.*, v.store_name AS seller_name, v.store_image AS seller_image FROM productos p LEFT JOIN vendedor v ON p.vendedor_phone = v.numero_telefono ORDER BY p.created_at DESC LIMIT 100');
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    // Si algo falla (BD no existe o tabla no creada), dejamos el array vacío y la página seguirá funcionando con los datos de JS
    $products = [];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos - PedidoReserve</title>
    <link rel="stylesheet" href="styles.css">
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
                    <div class="user-icon"></div>
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
        <section class="products-section">
            <h2>Productos</h2>
            <!-- Duplicate category selector removed (header contains category links) -->

            <div id="offers-container" class="offers-container"></div>

            <div id="products-container" class="products-container">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <div class="product-card">
                            <a href="product-detail.php?id=<?php echo htmlspecialchars($p['id']); ?>">
                                <div class="product-image"><img src="<?php echo htmlspecialchars($p['imagen'] ?: $p['seller_image'] ?: ''); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>"></div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($p['nombre']); ?></h3>
                                    <div class="product-price">
                                        <?php if (!empty($p['descuento_activo']) && $p['descuento_activo']): ?>
                                            <span class="price-discount">$<?php echo number_format((float)$p['precio_descuento'], 2); ?></span>
                                            <span class="price-original">$<?php echo number_format((float)$p['precio'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="price">$<?php echo number_format((float)$p['precio'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-seller"><?php echo htmlspecialchars($p['seller_name'] ?? ''); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Si no hay productos en la BD, dejamos que main.js muestre los productos de ejemplo -->
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Carrito sidebar (utilizado por main.js) -->
    <div id="overlay" class="overlay"></div>
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
