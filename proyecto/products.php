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
