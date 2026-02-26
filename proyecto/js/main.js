// Datos de productos
const products = [
    { id: 1, name: "Leche Entera 1L", price: 2.50, image: "https://images.unsplash.com/photo-1550583724-b2692b85b150?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Lácteos", rating: 4.5, description: "Leche entera pasteurizada de alta calidad, rica en calcio y vitaminas." },
    { id: 2, name: "Pan Integral 500g", price: 1.80, image: "https://images.unsplash.com/photo-1509440159596-0249088772ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Panadería", rating: 4.2, description: "Pan integral elaborado con harina de trigo integral, rico en fibra." },
    { id: 3, name: "Huevos Blancos 12 unid.", price: 3.20, image: "https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Lácteos", rating: 4.7, description: "Huevos blancos frescos de gallinas criadas en libertad." },
    { id: 4, name: "Arroz Extra 1kg", price: 2.10, image: "https://images.unsplash.com/photo-1542442822-1b1f6c4b59e7?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Granos", rating: 4.4, description: "Arroz extra de grano largo, ideal para todo tipo de preparaciones." },
    { id: 5, name: "Aceite de Girasol 1L", price: 3.50, image: "https://images.unsplash.com/photo-1533050487297-09b450131914?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Aceites", rating: 4.0, description: "Aceite de girasol 100% puro, ideal para freír y aderezar." },
    { id: 6, name: "Yogurt Natural 1kg", price: 2.80, image: "https://images.unsplash.com/photo-1565958011703-44f9829ba187?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Lácteos", rating: 4.6, description: "Yogurt natural sin azúcar añadido, rico en probióticos." },
    { id: 7, name: "Pasta Spaghetti 500g", price: 1.60, image: "https://images.unsplash.com/photo-1551183053-bf91a1d81141?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Pastas", rating: 4.3, description: "Pasta spaghetti de trigo duro, de cocción perfecta." },
    { id: 8, name: "Tomates 1kg", price: 2.40, image: "https://images.unsplash.com/photo-1592924357228-91a4daadcfea?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Verduras", rating: 4.1, description: "Tomates frescos y jugosos, cultivados localmente." }
];

// Datos de ofertas
const offers = [
    { id: 9, name: "Coca-Cola 2L", price: 2.20, originalPrice: 2.80, image: "https://images.unsplash.com/photo-1554866585-cd94860890b7?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Bebidas", rating: 4.8, description: "Refresco Coca-Cola original en presentación familiar." },
    { id: 10, name: "Papas Fritas 200g", price: 1.90, originalPrice: 2.50, image: "https://images.unsplash.com/photo-1566478989037-eec170784d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Snacks", rating: 4.5, description: "Papas fritas crujientes con sabor natural." },
    { id: 11, name: "Chocolate 100g", price: 1.50, originalPrice: 2.00, image: "https://images.unsplash.com/photo-1541783245831-57d6fb0926d3?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Dulces", rating: 4.7, description: "Chocolate con leche de alta calidad." },
    { id: 12, name: "Galletas Integrales", price: 1.30, originalPrice: 1.80, image: "https://images.unsplash.com/photo-1558961363-fa8fdf82db35?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Galletas", rating: 4.3, description: "Galletas integrales con avena y miel." }
];

// Carrito de compras
let cart = [];

// Usuario actual (simulación)
let currentUser = null;

// Función para guardar en localStorage
function saveToLocalStorage(key, data) {
    localStorage.setItem(key, JSON.stringify(data));
}

// Función para cargar desde localStorage
function loadFromLocalStorage(key) {
    const data = localStorage.getItem(key);
    return data ? JSON.parse(data) : null;
}

// Inicializar datos
function initData() {
    // Cargar carrito desde localStorage
    const savedCart = loadFromLocalStorage('cart');
    if (savedCart) {
        cart = savedCart;
    }
    
    // Cargar usuario desde localStorage
    const savedUser = loadFromLocalStorage('currentUser');
    if (savedUser) {
        currentUser = savedUser;
        updateUserUI();
    }
}

// Generar estrellas de calificación
function generateRatingStars(rating) {
    let stars = '';
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    
    if (halfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    
    const emptyStars = 5 - Math.ceil(rating);
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    
    return stars;
}

// Mostrar notificación
function showNotification(message, type = 'success') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    if (type === 'error') {
        notification.style.backgroundColor = '#dc3545';
    } else if (type === 'warning') {
        notification.style.backgroundColor = '#ffc107';
        notification.style.color = '#333';
    }
    
    document.body.appendChild(notification);
    
    // Mostrar notificación
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Ocultar y eliminar notificación después de 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Actualizar carrito
function updateCart() {
    const cartItemsContainer = document.getElementById('cart-items');
    const cartCountElement = document.querySelector('.cart-count');
    const cartTotalElement = document.getElementById('cart-total');
    
    if (!cartItemsContainer || !cartCountElement || !cartTotalElement) return;
    
    // Calcular total y contar items
    let total = 0;
    let itemCount = 0;
    
    cartItemsContainer.innerHTML = '';
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        itemCount += item.quantity;
        
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div class="cart-item-img">
                <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="cart-item-details">
                <div class="cart-item-title">${item.name}</div>
                <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                <div class="cart-item-actions">
                    <button class="quantity-btn decrease" data-id="${item.id}">-</button>
                    <span class="quantity">${item.quantity}</span>
                    <button class="quantity-btn increase" data-id="${item.id}">+</button>
                    <button class="remove-item" data-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        cartItemsContainer.appendChild(cartItem);
    });
    
    // Actualizar contador y total
    cartCountElement.textContent = itemCount;
    cartTotalElement.textContent = `$${total.toFixed(2)}`;
    
    // Guardar carrito en localStorage
    saveToLocalStorage('cart', cart);
    
    // Agregar eventos a los botones del carrito
    document.querySelectorAll('.decrease').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = parseInt(e.target.dataset.id);
            decreaseQuantity(id);
        });
    });
    
    document.querySelectorAll('.increase').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = parseInt(e.target.dataset.id);
            increaseQuantity(id);
        });
    });
    
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = parseInt(e.target.closest('.remove-item').dataset.id);
            removeFromCart(id);
        });
    });
}

// Agregar producto al carrito
function addToCart(productId) {
    // Buscar el producto en productos o ofertas
    let product = products.find(p => p.id === productId);
    if (!product) {
        product = offers.find(p => p.id === productId);
    }
    
    if (!product) return;
    
    // Verificar si el producto ya está en el carrito
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: 1
        });
    }
    
    updateCart();
    showNotification(`${product.name} agregado al carrito`);
}

// Disminuir cantidad de producto en el carrito
function decreaseQuantity(productId) {
    const item = cart.find(item => item.id === productId);
    
    if (item) {
        if (item.quantity > 1) {
            item.quantity--;
        } else {
            // Eliminar si la cantidad llega a 0
            cart = cart.filter(item => item.id !== productId);
        }
        
        updateCart();
    }
}

// Aumentar cantidad de producto en el carrito
function increaseQuantity(productId) {
    const item = cart.find(item => item.id === productId);
    
    if (item) {
        item.quantity++;
        updateCart();
    }
}

// Eliminar producto del carrito
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
    showNotification('Producto eliminado del carrito');
}

// Inicializar carrito
function initCart() {
    const openCartButton = document.getElementById('open-cart');
    const closeCartButton = document.getElementById('close-cart');
    const overlay = document.getElementById('overlay');
    const cartSidebar = document.getElementById('cart-sidebar');
    
    if (!openCartButton || !closeCartButton || !overlay || !cartSidebar) return;
    
    openCartButton.addEventListener('click', () => {
        cartSidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    closeCartButton.addEventListener('click', () => {
        cartSidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    });
    
    overlay.addEventListener('click', () => {
        cartSidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    });
    
    // Agregar eventos a los botones "Agregar al carrito"
    document.addEventListener('click', (e) => {
        if (e.target.closest('.add-to-cart')) {
            const productId = parseInt(e.target.closest('.add-to-cart').dataset.id);
            addToCart(productId);
        }
    });
    
    // Cargar carrito inicial
    updateCart();
}

// Actualizar UI según el usuario
function updateUserUI() {
    const userIcon = document.querySelector('.user-icon');
    const authButtons = document.getElementById('auth-buttons');
    
    if (currentUser) {
        // Si hay usuario logueado
            if (userIcon) {
            userIcon.innerHTML = `
                <a href="profile.php" style="color: inherit;">
                    <i class="fas fa-user-circle"></i>
                </a>
            `;
        }
        
        if (authButtons) {
            authButtons.innerHTML = `
                <span style="margin-right: 10px;">Hola, ${currentUser.name.split(' ')[0]}</span>
                <a href="profile.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;">Mi Perfil</a>
                <button id="logout-btn" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.9rem;">Salir</button>
            `;
            
            document.getElementById('logout-btn').addEventListener('click', logout);
        }
    } else {
        // Si no hay usuario logueado
        if (authButtons) {
                authButtons.innerHTML = `
                    <a href="login.php" class="btn" style="padding: 8px 15px; font-size: 0.9rem;">Iniciar Sesión</a>
                    <a href="register.php" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.9rem;">Registrarse</a>
                `;
        }
    }
}

// Función de login
function login(email, password) {
    // Simulación de login
    const users = loadFromLocalStorage('users') || [];
    const user = users.find(u => u.email === email && u.password === password);
    
    if (user) {
        currentUser = user;
        saveToLocalStorage('currentUser', currentUser);
        updateUserUI();
        showNotification(`¡Bienvenido ${user.name}!`);
        return true;
    } else {
        showNotification('Email o contraseña incorrectos', 'error');
        return false;
    }
}

// Función de registro
function register(userData) {
    const users = loadFromLocalStorage('users') || [];
    
    // Verificar si el usuario ya existe
    const existingUser = users.find(u => u.email === userData.email);
    if (existingUser) {
        showNotification('Este email ya está registrado', 'error');
        return false;
    }
    
    // Agregar nuevo usuario
    users.push(userData);
    saveToLocalStorage('users', users);
    
    // Iniciar sesión automáticamente
    currentUser = userData;
    saveToLocalStorage('currentUser', currentUser);
    
    showNotification('¡Registro exitoso! Bienvenido a RapiMarket');
    updateUserUI();
    return true;
}

// Función de logout
function logout() {
    currentUser = null;
    localStorage.removeItem('currentUser');
    updateUserUI();
    showNotification('Sesión cerrada correctamente');
}

// Inicializar la aplicación
document.addEventListener('DOMContentLoaded', () => {
    initData();
    initCart();
    updateUserUI();
    
    // Agregar evento al botón de checkout si existe
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            if (cart.length === 0) {
                showNotification('Tu carrito está vacío', 'warning');
                return;
            }
            
            if (!currentUser) {
                showNotification('Debes iniciar sesión para continuar', 'warning');
                window.location.href = 'login.php?redirect=checkout';
                return;
            }
            
            showNotification('¡Compra realizada con éxito! Gracias por tu pedido.');
            
            // Vaciar carrito después de comprar
            cart = [];
            updateCart();
            
            // Cerrar carrito
            const cartSidebar = document.getElementById('cart-sidebar');
            const overlay = document.getElementById('overlay');
            if (cartSidebar && overlay) {
                cartSidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Agregar funcionalidad a las categorías
    document.querySelectorAll('.categories a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Remover clase active de todos los enlaces
            document.querySelectorAll('.categories a').forEach(item => {
                item.classList.remove('active');
            });
            
            // Agregar clase active al enlace clickeado
            link.classList.add('active');
            
            // Mostrar notificación de filtro aplicado
            const category = link.textContent;
            showNotification(`Filtrado por: ${category}`);
        });
    });
    
    // Cargar productos si estamos en la página de productos
    if (window.location.pathname.includes('products.php') || window.location.pathname.includes('index.php') || window.location.pathname.endsWith('/')) {
        loadProducts();
        loadOffers();
    }
});

// Cargar productos destacados
function loadProducts() {
    const productsContainer = document.getElementById('products-container');
    if (!productsContainer) return;
    
    productsContainer.innerHTML = '';
    
    products.forEach(product => {
        const productCard = document.createElement('a');
        productCard.className = 'product-card';
    productCard.href = `product-detail.php?id=${product.id}`;
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}">
            </div>
            <div class="product-info">
                <h4 class="product-title">${product.name}</h4>
                <div class="product-rating">
                    ${generateRatingStars(product.rating)}
                    <span style="color: var(--gray-color); font-size: 0.9rem;">${product.rating}</span>
                </div>
                <div class="product-price">$${product.price.toFixed(2)}</div>
                <button class="add-to-cart" data-id="${product.id}">
                    <i class="fas fa-cart-plus"></i> Agregar
                </button>
            </div>
        `;
        productsContainer.appendChild(productCard);
    });
}

// Cargar ofertas
function loadOffers() {
    const offersContainer = document.getElementById('offers-container');
    if (!offersContainer) return;
    
    offersContainer.innerHTML = '';
    
    offers.forEach(product => {
        const discount = Math.round(((product.originalPrice - product.price) / product.originalPrice) * 100);
        
        const productCard = document.createElement('a');
        productCard.className = 'product-card';
    productCard.href = `product-detail.php?id=${product.id}`;
        productCard.innerHTML = `
            <div style="position: absolute; top: 10px; left: 10px; background-color: var(--primary-color); color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold; z-index: 1;">
                -${discount}%
            </div>
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}">
            </div>
            <div class="product-info">
                <h4 class="product-title">${product.name}</h4>
                <div class="product-rating">
                    ${generateRatingStars(product.rating)}
                    <span style="color: var(--gray-color); font-size: 0.9rem;">${product.rating}</span>
                </div>
                <div class="product-price">
                    <span style="color: var(--primary-color); font-size: 1.3rem; font-weight: 700;">$${product.price.toFixed(2)}</span>
                    <span style="color: var(--gray-color); text-decoration: line-through; margin-left: 5px; font-size: 1rem;">$${product.originalPrice.toFixed(2)}</span>
                </div>
                <button class="add-to-cart" data-id="${product.id}">
                    <i class="fas fa-cart-plus"></i> Agregar
                </button>
            </div>
        `;
        offersContainer.appendChild(productCard);
    });
}