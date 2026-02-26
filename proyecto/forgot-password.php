<?php
// Página convertida desde forgot-password.html
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - RapiMarket</title>
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
                    <a href="login.php" class="btn btn-outline">Volver al login</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Formulario de recuperación -->
    <section class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Recuperar Contraseña</h2>
                <p>Ingresa tu correo electrónico para restablecer tu contraseña</p>
            </div>
            
            <form id="forgot-password-form">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
                </div>
                
                <button type="submit" class="btn btn-block">Enviar Instrucciones</button>
            </form>
            
            <div class="auth-footer">
                <p>¿Recordaste tu contraseña? <a href="login.php">Inicia sesión aquí</a></p>
                <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
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
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 RapiMarket. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forgotForm = document.getElementById('forgot-password-form');
            
            forgotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = document.getElementById('email').value;
                
                // Simular envío de instrucciones
                alert(`Se han enviado instrucciones para restablecer tu contraseña a: ${email}\n\n(En un sistema real, se enviaría un email con un enlace para restablecer la contraseña)`);
                
                // Redirigir a la página de login
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            });
        });
    </script>
</body>
</html>
