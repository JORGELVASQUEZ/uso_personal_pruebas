<?php
// Página convertida desde register-seller.html
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Vendedor - RapiMarket</title>
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
                    <a href="register.php" class="btn btn-outline">Volver</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Formulario de registro vendedor -->
    <section class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Registro como Vendedor</h2>
                <p>Crea tu cuenta para comenzar a vender</p>
            </div>
            
            <form id="register-seller-form">
                <div class="form-group">
                    <label for="store-name">Nombre del Negocio</label>
                    <input type="text" id="store-name" class="form-control" placeholder="Ej: Mi Supermercado" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nombre del Responsable</label>
                        <input type="text" id="name" class="form-control" placeholder="Tu nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastname">Apellido del Responsable</label>
                        <input type="text" id="lastname" class="form-control" placeholder="Tu apellido" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Teléfono</label>
                    <input type="tel" id="phone" class="form-control" placeholder="Tu número de teléfono" required>
                </div>
                
                <div class="form-group">
                    <label for="store-address">Dirección del Negocio</label>
                    <textarea id="store-address" class="form-control" placeholder="Calle, número, ciudad, código postal" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="tax-id">RFC o Identificación Fiscal</label>
                    <input type="text" id="tax-id" class="form-control" placeholder="Tu identificación fiscal" required>
                </div>
                
                <div class="form-group">
                    <label for="store-type">Tipo de Negocio</label>
                    <select id="store-type" class="form-control" required>
                        <option value="">Selecciona una opción</option>
                        <option value="supermarket">Supermercado</option>
                        <option value="pharmacy">Farmacia</option>
                        <option value="bakery">Panadería</option>
                        <option value="butcher">Carnicería</option>
                        <option value="greengrocer">Verdulería</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" class="form-control" placeholder="Mínimo 8 caracteres" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirmar Contraseña</label>
                        <input type="password" id="confirm-password" class="form-control" placeholder="Repite tu contraseña" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="terms" required>
                        Acepto los <a href="#" style="color: var(--primary-color);">Términos y Condiciones</a> para vendedores
                    </label>
                </div>
                
                <button type="submit" class="btn btn-block">Crear Cuenta de Vendedor</button>
            </form>
            
            <div class="auth-footer">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
                <p>¿Quieres registrarte como comprador? <a href="register-buyer.php">Regístrate aquí</a></p>
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

    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.getElementById('register-seller-form');
            
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const storeName = document.getElementById('store-name').value;
                const name = document.getElementById('name').value;
                const lastname = document.getElementById('lastname').value;
                const email = document.getElementById('email').value;
                const phone = document.getElementById('phone').value;
                const storeAddress = document.getElementById('store-address').value;
                const taxId = document.getElementById('tax-id').value;
                const storeType = document.getElementById('store-type').value;
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                // Validar que las contraseñas coincidan
                if (password !== confirmPassword) {
                    alert('Las contraseñas no coinciden');
                    return;
                }
                
                // Crear objeto de usuario vendedor
                const userData = {
                    id: Date.now(),
                    name: `${name} ${lastname}`,
                    storeName: storeName,
                    email: email,
                    phone: phone,
                    address: storeAddress,
                    taxId: taxId,
                    storeType: storeType,
                    password: password,
                    role: 'seller',
                    createdAt: new Date().toISOString(),
                    status: 'pending'
                };
                
                // Registrar usuario vendedor
                if (register(userData)) {
                    // Redirigir al inicio
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                }
            });
        });
    </script>
</body>
</html>
