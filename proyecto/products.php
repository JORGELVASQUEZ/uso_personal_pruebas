<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=order_flow;charset=utf8mb4', 'root', '', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
));
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

// Build base SQL and conditions
$conds = [];
$binds = [];

if ($category === 'descuentos') {
    $conds[] = 'descuento_activo = 1';
} elseif ($category) {
    $conds[] = 'categoria = :categoria';
    $binds['categoria'] = $category;
}

if ($search !== '') {
    // search across nombre, descripcion and categoria
    // Use distinct named placeholders because some PDO drivers/native prepares
    // require a separate parameter for each occurrence.
    $conds[] = '(nombre LIKE :s_nombre OR descripcion LIKE :s_descripcion OR categoria LIKE :s_categoria)';
    $binds['s_nombre'] = '%' . $search . '%';
    $binds['s_descripcion'] = '%' . $search . '%';
    $binds['s_categoria'] = '%' . $search . '%';
}

$sql = 'SELECT * FROM productos';
if (!empty($conds)) {
    $sql .= ' WHERE ' . implode(' AND ', $conds);
}
$sql .= ' ORDER BY created_at DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($binds);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
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
                    <li><a href="products.php" class="<?php echo empty($category) ? 'active' : ''; ?>">Todos</a></li>
                    <li><a href="products.php?category=supermercado" class="<?php echo ($category === 'supermercado') ? 'active' : ''; ?>">Supermercado</a></li>
                    <li><a href="products.php?category=bebidas" class="<?php echo ($category === 'bebidas') ? 'active' : ''; ?>">Bebidas</a></li>
                    <li><a href="products.php?category=lacteos" class="<?php echo ($category === 'lacteos') ? 'active' : ''; ?>">Lácteos</a></li>
                    <li><a href="products.php?category=snacks" class="<?php echo ($category === 'snacks') ? 'active' : ''; ?>">Snacks</a></li>
                    <li><a href="products.php?category=descuentos" class="<?php echo ($category === 'descuentos') ? 'active' : ''; ?>">Descuentos</a></li>
                </ul>
            </div>
        </div>
    </header>
    <main class="container">
        <section class="products-section">
            <h2>Productos</h2>
            <div id="products-container" class="products-container">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <a class="product-card" href="product-detail.php?id=<?php echo urlencode($p['id']); ?>">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($p['imagen'] ?? ''); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($p['nombre']); ?></h3>
                                <div class="product-price">
                                    <?php if (!empty($p['descuento_activo']) && $p['descuento_activo']): ?>
                                        <span class="price-discount">$<?php echo number_format((float)$p['precio_descuento'], 2); ?></span>
                                        <span class="price-original">$<?php echo number_format((float)$p['precio'], 2); ?></span>
                                        <span class="discount-label">Descuento</span>
                                    <?php else: ?>
                                        <span class="price">$<?php echo number_format((float)$p['precio'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($p['categoria'])): ?>
                                    <div class="product-category">Categoría: <?php echo htmlspecialchars($p['categoria']); ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>No hay productos.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
