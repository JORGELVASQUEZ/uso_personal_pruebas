<?php
// Página de listado de productos para que main.js la use
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
    <header>
        <nav class="navbar">
            <a class="logo" href="index.php">PedidoReserve</a>
            <div class="nav-actions">
                <div class="user-icon"></div>
                <div id="auth-buttons"></div>
                <button id="open-cart" class="btn">Carrito (<span class="cart-count">0</span>)</button>
            </div>
        </nav>
    </header>

    <main class="container">
        <section class="products-section">
            <h2>Productos</h2>
            <div class="categories">
                <a href="products.php" class="active">Todos</a>
                <a href="products.php?category=supermercado">Supermercado</a>
                <a href="products.php?category=farmacia">Farmacia</a>
                <a href="products.php?category=bebidas">Bebidas</a>
                <a href="products.php?category=lácteos">Lácteos</a>
            </div>

            <div id="offers-container" class="offers-container"></div>

            <div id="products-container" class="products-container"></div>
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
