<?php
// Página convertida desde login.html
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - RapiMarket</title>
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

    <!-- Formulario de login -->
    <section class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Iniciar Sesión</h2>
                <p>Ingresa a tu cuenta para continuar</p>
            </div>
            
            <form id="login-form">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" class="form-control" placeholder="Tu contraseña" required>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="margin-bottom: 0;">
                            <input type="checkbox" id="remember"> Recordarme
                        </label>
                        <a href="forgot-password.php" style="font-size: 0.9rem;">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-block">Iniciar Sesión</button>
            </form>
            
            <div class="auth-footer">
                <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
                <p>¿Quieres vender en RapiMarket? <a href="register-seller.php">Regístrate como vendedor</a></p>
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
                        <li><a href="register.php">Registrarse</a></li>
                        <li><a href="forgot-password.php">Recuperar contraseña</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 RapiMarket. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                // Simulación de login
                if (login(email, password)) {
                    // Redirigir después de login exitoso
                    const urlParams = new URLSearchParams(window.location.search);
                    const redirect = urlParams.get('redirect');
                    
                    if (redirect) {
                        window.location.href = redirect + '.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                }
            });
        });
    </script>
</body>
</html>
