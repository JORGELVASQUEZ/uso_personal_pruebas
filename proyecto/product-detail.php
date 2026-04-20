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

    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = :id LIMIT 1');
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

    <!-- var_dump eliminado: depuración finalizada -->
    <main class="container">
        <div id="product-detail" class="product-detail">
            <?php if ($product): ?>
                <div class="product-detail-card detail-grid">
                    <div class="detail-image">
                        <img src="<?php echo htmlspecialchars($product['imagen'] ?: $product['seller_image'] ?: ''); ?>" alt="<?php echo htmlspecialchars($product['nombre']); ?>">
                    </div>
                    <div class="detail-info">
                        <h2 class="detail-title" style="color:#2196f3;font-size:2rem;margin-bottom:10px;"><?php echo htmlspecialchars($product['nombre']); ?></h2>
                        <div class="detail-rating" style="margin-bottom:8px;">
                            <i class="fas fa-star"></i> 4.5
                        </div>
                        <div class="detail-price" style="margin-bottom:8px;">
                            <?php if (!empty($product['descuento_activo']) && $product['descuento_activo']): ?>
                                <span class="price-discount">$<?php echo number_format((float)$product['precio_descuento'], 2); ?></span>
                                <span class="price-original">$<?php echo number_format((float)$product['precio'], 2); ?></span>
                                <span class="discount-label">Descuento</span>
                            <?php else: ?>
                                <span class="price">$<?php echo number_format((float)$product['precio'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="detail-category" style="margin-bottom:8px;">
                            <span class="category-label">Categoría: <?php echo isset($product['categoria']) ? htmlspecialchars(ucfirst($product['categoria'])) : 'No definida'; ?></span>
                        </div>
                        <p class="detail-description" style="margin-bottom:18px; color:#6C757D;">
                            <?php echo nl2br(htmlspecialchars($product['descripcion'] ?? '')); ?>
                        </p>
                        <button id="add-to-cart-btn" class="btn" style="background:#2196f3;color:#fff;padding:12px 18px;font-weight:600;font-size:1.1em;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-shopping-cart"></i> Agregar al carrito
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding:40px;text-align:center;color:#888;">No se encontró el producto.</div>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('add-to-cart-btn');
        if (btn) {
            btn.addEventListener('click', function() {
                if (typeof addToCart === 'function') {
                    addToCart(<?php echo json_encode($product['id']); ?>);
                } else {
                    alert('No se pudo agregar al carrito.');
                }
            });
        }
    });
    </script>
</body>
</html>