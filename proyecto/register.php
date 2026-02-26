<?php
// Página convertida desde register.html
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - RapiMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header simplificado -->
    <header>
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-bolt logo-icon"></i>
                        <h1>RapiMarket</h1>
                    </a>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline">Volver al inicio</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Selector de tipo de registro -->
    <section class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Crear Cuenta</h2>
                <p>Selecciona el tipo de cuenta que deseas crear</p>
            </div>
            
            <div class="role-selector">
                <div class="role-card" id="buyer-card">
                    <div class="role-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Comprador</h3>
                    <p>Quiero comprar productos</p>
                </div>
                
                <div class="role-card" id="seller-card">
                    <div class="role-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>Vendedor</h3>
                    <p>Quiero vender mis productos</p>
                </div>
            </div>
            
            <div class="auth-footer">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h4>RapiMarket</h4>
                    <p>Tu supermercado online de confianza con entrega rápida y productos de calidad.</p>
                </div>
                <div class="footer-column">
                    <h4>Enlaces Rápidos</h4>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="login.php">Iniciar sesión</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 RapiMarket. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buyerCard = document.getElementById('buyer-card');
            const sellerCard = document.getElementById('seller-card');
            
            buyerCard.addEventListener('click', function() {
                buyerCard.classList.add('selected');
                sellerCard.classList.remove('selected');
                
                // Redirigir después de 1 segundo para dar feedback visual
                setTimeout(() => {
                    window.location.href = 'register-buyer.php';
                }, 300);
            });
            
            sellerCard.addEventListener('click', function() {
                sellerCard.classList.add('selected');
                buyerCard.classList.remove('selected');
                
                // Redirigir después de 1 segundo para dar feedback visual
                setTimeout(() => {
                    window.location.href = 'register-seller.php';
                }, 300);
            });
        });
    </script>
</body>
</html>
