<?php
// Página convertida desde index.html
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapiMarket - Tu Supermercado Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header -->
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
                    <div id="auth-buttons">
                        <!-- Los botones de auth se cargan con JS -->
                    </div>
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
                    <li><a href="products.php?category=farmacia">Farmacia</a></li>
                    <li><a href="products.php?category=bebidas">Bebidas</a></li>
                    <li><a href="products.php?category=lácteos">Lácteos</a></li>
                    <li><a href="products.php?category=snacks">Snacks</a></li>
                    <li><a href="products.php?category=limpieza">Limpieza</a></li>
                    <li><a href="products.php?category=mascotas">Mascotas</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Banner -->
    <section class="banner">
        <div class="container">
            <div class="banner-content">
                <div class="banner-text">
                    <h2>Entrega en menos de 30 minutos</h2>
                    <p>Compra en tu supermercado favorito y recíbelo en la puerta de tu casa</p>
                    <a href="products.php" class="banner-btn">Ver ofertas</a>
                </div>
                <div class="banner-image">
                    <img src="https://cdn-icons-png.flaticon.com/512/751/751463.png" alt="Delivery rápido">
                </div>
            </div>
        </div>
    </section>

    <!-- Productos destacados -->
    <section class="container">
        <div class="section-title">
            <h3>Productos Destacados</h3>
            <a href="products.php" class="view-all">Ver todos</a>
        </div>
        
        <div class="products-grid" id="products-container">
            <!-- Los productos se cargan con JavaScript -->
        </div>
    </section>

    <!-- Ofertas -->
    <section class="container">
        <div class="section-title">
            <h3>Ofertas Especiales</h3>
            <a href="products.php" class="view-all">Ver todas</a>
        </div>
        
        <div class="products-grid" id="offers-container">
            <!-- Las ofertas se cargan con JavaScript -->
        </div>
    </section>

    <!-- Carrito de compras -->
    <div class="overlay" id="overlay"></div>
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3>Tu Carrito</h3>
            <button class="close-cart" id="close-cart">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="cart-items" id="cart-items">
            <!-- Los productos del carrito se cargan con JavaScript -->
        </div>
        
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cart-total">$0.00</span>
            </div>
            <button class="checkout-btn">Continuar compra</button>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>RapiMarket</h4>
                    <p>Tu supermercado online de confianza con entrega rápida y productos de calidad.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Categorías</h4>
                    <ul>
                        <li><a href="products.php">Supermercado</a></li>
                        <li><a href="products.php">Farmacia</a></li>
                        <li><a href="products.php">Bebidas</a></li>
                        <li><a href="products.php">Lácteos</a></li>
                        <li><a href="products.php">Snacks</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Ayuda</h4>
                    <ul>
                        <li><a href="#">Centro de ayuda</a></li>
                        <li><a href="#">Preguntas frecuentes</a></li>
                        <li><a href="#">Términos y condiciones</a></li>
                        <li><a href="#">Política de privacidad</a></li>
                        <li><a href="#">Contacto</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Descarga nuestra app</h4>
                    <p>Disponible en iOS y Android</p>
                    <div class="app-badges">
                        <div style="background-color: #000; display: inline-block; padding: 8px 15px; border-radius: 5px; margin-top: 10px;">
                            <i class="fab fa-apple" style="color: white; margin-right: 8px;"></i>
                            <span style="color: white;">App Store</span>
                        </div>
                        <div style="background-color: #000; display: inline-block; padding: 8px 15px; border-radius: 5px; margin-top: 10px; margin-left: 10px;">
                            <i class="fab fa-google-play" style="color: white; margin-right: 8px;"></i>
                            <span style="color: white;">Google Play</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 RapiMarket. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
