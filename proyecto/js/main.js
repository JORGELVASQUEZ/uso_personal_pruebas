// Datos de productos
const products = [
    { id: 1, name: "Leche Entera 1L", price: 2.50, image: "https://images.unsplash.com/photo-1550583724-b2692b85b150?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Lácteos", rating: 4.5, description: "Leche entera pasteurizada de alta calidad, rica en calcio y vitaminas." },
    { id: 2, name: "Pan Integral 500g", price: 1.80, image: "https://images.unsplash.com/photo-1509440159596-0249088772ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Panadería", rating: 4.2, description: "Pan integral elaborado con harina de trigo integral, rico en fibra." },
    { id: 3, name: "Huevos Blancos 12 unid.", price: 3.20, image: "https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Huevos", rating: 4.7, description: "Huevos blancos frescos de gallinas criadas en libertad." },
    { id: 4, name: "Arroz Extra 1kg", price: 2.10, image: "https://www.vegaucero.com/multimedia/web/vega-ucero/catalogo/pro/5188/16008810781383125803.jpg", category: "Granos", rating: 4.4, description: "Arroz extra de grano largo, ideal para todo tipo de preparaciones." },
    { id: 5, name: "Aceite de Girasol 1L", price: 3.50, image: "https://aceitesandua.com/wp-content/uploads/2021/05/211fb1d06f9479a7650fc3bb47b93c8b.jpg", category: "Aceites", rating: 4.0, description: "Aceite de girasol 100% puro, ideal para freír y aderezar." },
    { id: 6, name: "Yogurt Natural 1kg", price: 2.80, image: "https://s1.abcstatics.com/media/bienestar/2019/07/26/yogur-ktEF--1248x698@abc.jpg", category: "Lácteos", rating: 4.6, description: "Yogurt natural sin azúcar añadido, rico en probióticos." },
    { id: 7, name: "Pasta Spaghetti 500g", price: 1.60, image: "https://images.unsplash.com/photo-1551183053-bf91a1d81141?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Pastas", rating: 4.3, description: "Pasta spaghetti de trigo duro, de cocción perfecta." },
    { id: 8, name: "Tomates 1kg", price: 2.40, image: "https://images.unsplash.com/photo-1592924357228-91a4daadcfea?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60", category: "Verduras", rating: 4.1, description: "Tomates frescos y jugosos, cultivados localmente." }
];

let remoteProducts = [];



// Carrito de compras
let cart = [];

// Nota: el render del detalle de producto se maneja por JS (renderProductDetail)
// Anteriormente había un override aquí para desactivar el render durante depuración PHP;
// lo hemos eliminado para mantener el comportamiento consistente.

// Usuario actual
let currentUser = null;

/**
 * Añade un valor a un objeto URLSearchParams soportando arrays y objetos anidados.
 * @param {URLSearchParams} params El objeto donde se agregan los pares clave/valor.
 * @param {string} key La clave actual.
 * @param {*} value El valor (puede ser primitivo, array u objeto).
 */
function appendFormValue(params, key, value) {
    if (Array.isArray(value)) {
        value.forEach((item, index) => {
            appendFormValue(params, `${key}[${index}]`, item);
        });
        return;
    }

    if (value !== null && typeof value === 'object') {
        Object.entries(value).forEach(([subKey, subValue]) => {
            appendFormValue(params, `${key}[${subKey}]`, subValue);
        });
        return;
    }

    params.append(key, value ?? '');

}

/**
 * Valida que un teléfono contenga exactamente 10 dígitos (ignorando otros caracteres).
 * @param {string} phone Texto del teléfono.
 * @returns {boolean} True si tiene 10 dígitos.
 */
function isValidTenDigits(phone) {
    const digits = String(phone).replace(/\D/g, '');
    return digits.length === 10;

}

/**
 * Hace una petición a la API interna (`api.php?action=...`) y devuelve la respuesta JSON.
 * @param {string} action Acción a solicitar en la API.
 * @param {object|null} body Cuerpo a enviar (se serializa como application/x-www-form-urlencoded).
 * @param {string} method Método HTTP (por defecto POST).
 */
async function apiRequest(action, body = null, method = 'POST') {
    const options = { method };

    if (body !== null) {
        const params = new URLSearchParams();
        Object.entries(body).forEach(([key, value]) => {
            appendFormValue(params, key, value);
        });

        options.headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        };
        options.body = params.toString();
    }

    const response = await fetch(`api.php?action=${encodeURIComponent(action)}`, options);
    const data = await response.json();

    if (!response.ok) {
        return {
            success: false,
            message: data.message || 'Error en la petición'
        };
    }

    return data;

}

/**
 * Persiste el carrito actual en el servidor llamando a la API 'save_cart'.
 */
async function persistCart() {
    const response = await apiRequest('save_cart', { cart });
    if (!response.success) {
        showNotification('No se pudo sincronizar el carrito', 'warning');
    }

}

// Inicializar datos
/**
 * Inicializa datos de sesión desde la API ('session_state'), carga carrito y usuario.
 */
async function initData() {
    const response = await apiRequest('session_state', null, 'GET');

    if (!response.success) {
        showNotification('No se pudo cargar la sesión', 'warning');
        return;
    }

    cart = Array.isArray(response.cart) ? response.cart : [];
    currentUser = response.user || null;
    updateUserUI();

}

/**
 * Obtiene productos desde la API pública `products.list` y guarda en `remoteProducts`.
 * Si falla, deja `remoteProducts` vacío.
 */
async function fetchRemoteProducts(limit = 100) {
    try {
        const res = await fetch(`api.php?action=products.list&limit=${encodeURIComponent(limit)}`);
        const data = await res.json();
        if (res.ok && data && data.success && Array.isArray(data.products)) {
            // Mapear a la forma esperada por el render (name, image, id, price, description, descuento_activo, precio_descuento)
                // Normalizar cada fila con el helper centralizado
                remoteProducts = data.products.map(mapApiProduct);
        } else {
            remoteProducts = [];
        }
    } catch (e) {
        // Fallo silencioso al cargar remotos — usar fallback local
        remoteProducts = [];
    }
    }
/**
 * Devuelve el conjunto de productos que deben renderizarse, combinando remotos + locales sin duplicados.
 */
function getAllProductsCombined() {
    const map = new Map();
    // Preferir remoteProducts when ids clash
    remoteProducts.forEach(p => {
        if (p && typeof p.id !== 'undefined') map.set(Number(p.id), p);
    });
    products.forEach(p => {
        if (p && typeof p.id !== 'undefined') {
            if (!map.has(Number(p.id))) map.set(Number(p.id), p);
        }
    });
    return Array.from(map.values());
}

/**
 * Map an API product row to the normalized shape used by the front-end.
 * @param {object} p API product row
 */
function mapApiProduct(p) {
    return {
        id: Number(p.id),
        nombre: p.nombre || p.name || '',
        name: p.nombre || p.name || '',
        imagen: p.imagen || p.image || p.seller_image || '',
        image: p.imagen || p.image || p.seller_image || '',
        precio: typeof p.precio !== 'undefined' ? Number(p.precio) : (p.price ? Number(p.price) : 0),
        price: typeof p.precio !== 'undefined' ? Number(p.precio) : (p.price ? Number(p.price) : 0),
        descripcion: p.descripcion || p.description || '',
        description: p.descripcion || p.description || '',
        rating: p.rating ? Number(p.rating) : 4.5,
        categoria: p.categoria || p.category || '',
        descuento_activo: p.descuento_activo === 1 || p.descuento_activo === '1' || p.descuento_activo === true || p.descuento_activo === 'true',
        precio_descuento: typeof p.precio_descuento !== 'undefined' && p.precio_descuento !== null ? Number(p.precio_descuento) : null
    };
}

/**
 * Fetch products from the API using a search term and render results client-side.
 * This allows searching from any page without navigating to products.php.
 * @param {string} term
 */
async function apiSearchAndDisplay(term) {
    if (!term || term.trim() === '') {
        clearSearch();
        return;
    }

    showLoader();
    try {
        const res = await fetch(`api.php?action=products.list&search=${encodeURIComponent(term)}&limit=100`);
        const data = await res.json();
        if (!res.ok || !data || !data.success || !Array.isArray(data.products)) {
            showNotification('No se pudieron obtener resultados del servidor', 'warning');
            hideLoader();
            return;
        }

        const mapped = data.products.map(mapApiProduct);
        const productsResult = mapped.filter(p => !p.descuento_activo);
        const offersResult = mapped.filter(p => p.descuento_activo);

        displaySearchResults(productsResult, offersResult, term);
    } catch (e) {
        // Registro mínimo en consola para depuración; no exponemos objetos grandes en prod
        console.error('Search API error:', e && e.message ? e.message : String(e));
        showNotification('Error al buscar productos', 'error');
    } finally {
        hideLoader();
    }
}

// Generar estrellas de calificación
/**
 * Genera el HTML de las estrellas de valoración (completas, medias y vacías).
 * @param {number} rating Puntuación entre 0 y 5.
 * @returns {string} HTML con los iconos de estrellas.
 */
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
/**
 * Muestra una notificación temporal en pantalla.
 * @param {string} message Texto a mostrar.
 * @param {string} [type='success'] Tipo de notificación: 'success'|'error'|'warning'.
 */
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
/**
 * Renderiza el contenido del carrito en la UI, calcula totales y registra eventos de los botones.
 */
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
    
    // Sincronizar carrito con base de datos
    persistCart();
    
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
/**
 * Añade un producto al carrito (aumenta cantidad si ya existe).
 * @param {number} productId Id del producto a añadir.
 */
function addToCart(productId) {
    // Buscar primero en remoteProducts (productos de la base de datos)
    let product = null;
    if (typeof remoteProducts !== 'undefined' && Array.isArray(remoteProducts)) {
        product = remoteProducts.find(p => p.id === productId);
    }
    // Si no está en remoteProducts, buscar en products/ofertas
    if (!product) {
        product = products.find(p => p.id === productId);
    }
    if (!product) {
        // Buscar en el conjunto combinado (remote + local) en lugar de usar una variable global `offers` que
        // no siempre existe. Esto evita errores cuando `offers` no está definida.
        product = getAllProductsCombined().find(p => Number(p.id) === productId);
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
            price: (typeof product.precio_descuento !== 'undefined' && product.precio_descuento && product.descuento_activo) ? Number(product.precio_descuento) : Number(product.price),
            image: product.image,
            quantity: 1
        });
    }
    updateCart();
    showNotification(`${product.name} agregado al carrito`);
}

// Disminuir cantidad de producto en el carrito
/**
 * Disminuye la cantidad de un item en el carrito; lo elimina si llega a 0.
 * @param {number} productId Id del producto.
 */
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
/**
 * Aumenta la cantidad de un item en el carrito.
 * @param {number} productId Id del producto.
 */
function increaseQuantity(productId) {
    const item = cart.find(item => item.id === productId);
    
    if (item) {
        item.quantity++;
        updateCart();
    }
}

// Eliminar producto del carrito
/**
 * Elimina un producto del carrito por su id.
 * @param {number} productId Id del producto a eliminar.
 */
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
    showNotification('Producto eliminado del carrito');
}

// Inicializar carrito
/**
 * Inicializa la lógica del sidebar del carrito (abrir/cerrar).
 * Los eventos de agregar al carrito ahora se manejan individualmente en cada botón.
 */
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
    
    // Eliminamos el evento global que escuchaba clics en '.add-to-cart'
    // porque ahora cada botón maneja su propio evento
    
    // Cargar carrito inicial
    updateCart();
}

// Actualizar UI según el usuario
/**
 * Actualiza la interfaz (botones de login/registro y avatar) según el estado de `currentUser`.
 */
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
/**
 * Intenta autenticar al usuario usando la API y actualiza UI/cart si es exitoso.
 * @param {string} email Email del usuario.
 * @param {string} password Contraseña.
 * @returns {Promise<boolean>} True si el login fue exitoso.
 */
async function login(email, password) {
    const response = await apiRequest('login', { email, password });

    if (response.success) {
        currentUser = response.user;
        cart = Array.isArray(response.cart) ? response.cart : cart;
        updateUserUI();
        updateCart();
        showNotification(`¡Bienvenido ${currentUser.name}!`);
        return true;
    }

    showNotification(response.message || 'Email o contraseña incorrectos', 'error');
    return false;
}

// Función de registro
/**
 * Registra un nuevo usuario mediante la API y actualiza la sesión local si tuvo éxito.
 * @param {object} userData Objeto con campos esperados por la API (name, email, password, phone, address).
 * @returns {Promise<boolean>} True si el registro fue exitoso.
 */
async function register(userData) {
    const response = await apiRequest('register', userData);

    if (!response.success) {
        showNotification(response.message || 'No se pudo completar el registro', 'error');
        return false;
    }

    currentUser = response.user;
    cart = Array.isArray(response.cart) ? response.cart : cart;
    showNotification(response.message || '¡Registro exitoso! Bienvenido a RapiMarket');
    updateUserUI();
    updateCart();
    return true;
}

// Función de logout
/**
 * Cierra la sesión del usuario en el servidor y actualiza la UI localmente.
 */
async function logout() {
    await apiRequest('logout', {});
    currentUser = null;

    const response = await apiRequest('session_state', null, 'GET');
    cart = response.success && Array.isArray(response.cart) ? response.cart : [];

    updateUserUI();
    updateCart();
    showNotification('Sesión cerrada correctamente');
}

// Inicializar la aplicación                                
document.addEventListener('DOMContentLoaded', async () => {
    await initData();
    // intentar cargar productos remotos antes de renderizar la lista
    await fetchRemoteProducts();
    initCart();
    initSearch(); // <-- Agrega esta línea
    updateUserUI();
    
    // Agregar evento al botón de checkout si existe
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', async () => {
            if (cart.length === 0) {
                showNotification('Tu carrito está vacío', 'warning');
                return;
            }
            
            if (!currentUser) {
                showNotification('Debes iniciar sesión para continuar', 'warning');
                window.location.href = 'login.php?redirect=checkout';
                return;
            }

            const checkoutResponse = await apiRequest('checkout', {});
            if (!checkoutResponse.success) {
                showNotification(checkoutResponse.message || 'No se pudo procesar la compra', 'error');
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
            // Allow full navigation when clicking categories from the index (home).
            // Only intercept clicks and do AJAX filtering when we're on products.php.
            const href = link.getAttribute('href') || '';
            const params = new URLSearchParams(href.split('?')[1] || '');
            let categoryParam = params.get('category');

            if (!window.location.pathname.includes('products.php')) {
                // Not on products page — let the link navigate to products.php?category=...
                return; // default browser navigation
            }

            e.preventDefault();

            // Remover clase active de todos los enlaces
            document.querySelectorAll('.categories a').forEach(item => {
                item.classList.remove('active');
            });

            // Agregar clase active al enlace clickeado
            link.classList.add('active');

            // si el enlace apuntaba a una subcategoría que ahora forma parte de supermercado,
            // normalizamos y la redirigimos a 'supermercado'
            const merged = ['huevos','granos','aceites','pastas','verduras','farmacia','farmacias'];
            if (categoryParam && merged.includes(categoryParam.toLowerCase())) {
                categoryParam = 'supermercado';
            }

            // update URL using history API so users can bookmark
            const newPath = categoryParam ? `products.php?category=${encodeURIComponent(categoryParam)}` : 'products.php';
            history.pushState({ category: categoryParam }, '', newPath);

            // Recargar la lista filtrada (productos y ofertas) en un único contenedor sin duplicados
            loadCombined(categoryParam);

            // No mostrar notificación al aplicar filtro (mostramos productos y ofertas juntos)
        });
    });
    
    // Helper: normalizar nombres de categoría (quita acentos y lower-case)
    function normalizeCategoryName(str) {
        if (!str) return '';
        try {
            return String(str).toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();
        } catch (e) {
            // Fallback for older browsers without \p{Diacritic}
            return String(str).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        }
    }

    // Map specific categories into 'supermercado' (handles legacy links/bookmarks)
    function mapToSupermercado(cat) {
        if (!cat) return cat;
        const m = normalizeCategoryName(cat);
        const merged = new Set(['huevos','granos','aceites','pastas','verduras','farmacia','farmacias']);
        if (merged.has(m)) return 'supermercado';
        return cat;
    }

    // Cargar productos si estamos en la página de productos o en la portada (index)
    if (window.location.pathname.includes('products.php')) {
        const urlParams = new URLSearchParams(window.location.search);
        const initialCategory = mapToSupermercado(urlParams.get('category'));

        // Marcar link activo según query param
        if (initialCategory) {
            document.querySelectorAll('.categories a').forEach(link => {
                const href = link.getAttribute('href') || '';
                const params = new URLSearchParams(href.split('?')[1] || '');
                const cat = params.get('category');
                if (cat && normalizeCategoryName(cat) === normalizeCategoryName(initialCategory)) {
                    document.querySelectorAll('.categories a').forEach(i => i.classList.remove('active'));
                    link.classList.add('active');
                }
            });
        }

        loadCombined(initialCategory);
    } else if (window.location.pathname.includes('index.php') || window.location.pathname.endsWith('/')) {
        // On index page show featured products and separate discounts
        const urlParams = new URLSearchParams(window.location.search);
        const initialCategory = mapToSupermercado(urlParams.get('category'));

        // mark active link if present
        if (initialCategory) {
            document.querySelectorAll('.categories a').forEach(link => {
                const href = link.getAttribute('href') || '';
                const params = new URLSearchParams(href.split('?')[1] || '');
                const cat = params.get('category');
                if (cat && normalizeCategoryName(cat) === normalizeCategoryName(initialCategory)) {
                    document.querySelectorAll('.categories a').forEach(i => i.classList.remove('active'));
                    link.classList.add('active');
                }
            });
        }

        // Load featured products into products-container
        loadProducts(initialCategory);
        // Load discounted offers into offers-container and ensure it's visible
        const offersContainer = document.getElementById('offers-container');
        if (offersContainer) offersContainer.style.display = '';
        loadOffers(initialCategory);
    }

    // Handle back/forward navigation to update category filter
    window.addEventListener('popstate', (e) => {
        const urlParams = new URLSearchParams(window.location.search);
        const cat = mapToSupermercado(urlParams.get('category'));
        // update active link
        document.querySelectorAll('.categories a').forEach(link => {
            const href = link.getAttribute('href') || '';
            const params = new URLSearchParams(href.split('?')[1] || '');
            const catLink = params.get('category');
            if (cat && catLink && normalizeCategoryName(catLink) === normalizeCategoryName(cat)) {
                document.querySelectorAll('.categories a').forEach(i => i.classList.remove('active'));
                link.classList.add('active');
            } else if (!cat && !catLink) {
                document.querySelectorAll('.categories a').forEach(i => i.classList.remove('active'));
                link.classList.add('active');
            }
        });

    loadCombined(cat);
    });

    // Cargar detalle de producto si estamos en la página de detalle
    if (window.location.pathname.includes('product-detail.php') || document.getElementById('product-detail')) {
        renderProductDetail();
    }

});

// Cargar productos destacados
/**
 * Renderiza la lista de productos destacados en la página principal o de productos.
 */
function loadProducts(category = null) {
    const productsContainer = document.getElementById('products-container');
    if (!productsContainer) return;

    const normalize = (s) => {
        if (!s) return '';
        try {
            return String(s).toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();
        } catch (e) {
            return String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        }
    };

    const selected = category ? normalize(category) : null;

    showLoader();

    setTimeout(() => {
        productsContainer.innerHTML = '';

        const supermercadoGroup = new Set(['supermercado', 'huevos', 'granos', 'aceites', 'pastas', 'verduras', 'farmacia', 'farmacias']);

        // Usar remoteProducts si está disponible, si no, fallback a getAllProductsCombined
        let all = [];
        if (typeof remoteProducts !== 'undefined' && Array.isArray(remoteProducts) && remoteProducts.length > 0) {
            all = remoteProducts;
        } else {
            all = getAllProductsCombined();
        }



        // Filtrar SOLO productos que tengan descuento_activo = 0 (no están en oferta)
        let filtered = all.filter(p => {
            // Considerar 0, false, '0', 'false' como NO descuento
            if (p.descuento_activo === 1 || p.descuento_activo === '1' || p.descuento_activo === true || p.descuento_activo === 'true') return false;
            if (!selected) return true;
            const cat = normalize(p.categoria || p.category);
            if (selected === 'supermercado') {
                return supermercadoGroup.has(cat) || cat === selected;
            }
            return cat === selected;
        });

        // Si no hay productos destacados, usar productos locales de demo SIEMPRE que existan
        if (filtered.length === 0 && products.length > 0) {
            filtered = products.filter(p => {
                if (!selected) return true;
                const cat = normalize(p.category);
                if (selected === 'supermercado') {
                    return supermercadoGroup.has(cat) || cat === selected;
                }
                return cat === selected;
            });
        }

        if (filtered.length === 0) {
            productsContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 40px;">No hay productos destacados disponibles.</p>';
            hideLoader();
            return;
        }

        // Renderizar productos filtrados
        filtered.forEach(product => {
            let priceHtml = `<div class="product-price">$${Number(product.precio || product.price).toFixed(2)}</div>`;
            const productCard = document.createElement('a');
            productCard.className = 'product-card';
            productCard.href = `product-detail.php?id=${product.id}`;
            productCard.innerHTML = `
                <div class="product-image">
                    <img src="${product.imagen || product.image}" alt="${product.nombre || product.name}">
                </div>
                <div class="product-info">
                    <h4 class="product-title">${product.nombre || product.name}</h4>
                    <div class="product-rating">
                        ${generateRatingStars(product.rating || 4.5)}
                        <span style="color: var(--gray-color); font-size: 0.9rem;">${product.rating || 4.5}</span>
                    </div>
                    ${priceHtml}
                    <button class="add-to-cart" data-id="${product.id}">
                        <i class="fas fa-cart-plus"></i> Agregar
                    </button>
                </div>
            `;
            // IMPORTANTE: Evitar que el clic en el botón navegue al enlace padre
            const addButton = productCard.querySelector('.add-to-cart');
            addButton.addEventListener('click', (e) => {
                e.preventDefault();  // Evita comportamiento por defecto
                e.stopPropagation();  // Detiene la propagación al elemento padre <a>
                const productId = parseInt(e.currentTarget.dataset.id);
                addToCart(productId);
            });
            productsContainer.appendChild(productCard);
        });
        hideLoader();
    }, 120);

}

// Cargar productos y ofertas juntos sin duplicados
/**
 * Renderiza en `#products-container` los productos y ofertas que coincidan con la categoría
 * evitando duplicados por `id`.
 */
function loadCombined(category = null) {
    const productsContainer = document.getElementById('products-container');
    const offersContainer = document.getElementById('offers-container');
    if (!productsContainer) return;

    const normalize = (s) => {
        if (!s) return '';
        try {
            return String(s).toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();
        } catch (e) {
            return String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        }
    };

    const selected = category ? normalize(category) : null;
    const supermercadoGroup = new Set(['supermercado', 'huevos', 'granos', 'aceites', 'pastas', 'verduras', 'farmacia', 'farmacias']);

    showLoader();

    setTimeout(() => {
        productsContainer.innerHTML = '';
        if (offersContainer) offersContainer.style.display = 'none';

        // Filtrar productos y ofertas por categoría
        // Productos normales (sin descuento activo)
        let filteredProducts = [];
        let filteredOffers = [];
        if (typeof remoteProducts !== 'undefined' && Array.isArray(remoteProducts)) {
            filteredProducts = remoteProducts.filter(p => {
                if (p.descuento_activo) return false;
                if (!selected) return true;
                const cat = normalize(p.categoria || p.category);
                if (selected === 'supermercado') return supermercadoGroup.has(cat) || cat === selected;
                return cat === selected;
            });
            filteredOffers = remoteProducts.filter(p => {
                if (!p.descuento_activo) return false;
                if (!selected) return true;
                const cat = normalize(p.categoria || p.category);
                if (selected === 'supermercado') return supermercadoGroup.has(cat) || cat === selected;
                return cat === selected;
            });
        } else {
            filteredProducts = getAllProductsCombined().filter(p => {
                if (p.descuento_activo) return false;
                if (!selected) return true;
                const cat = normalize(p.categoria || p.category);
                if (selected === 'supermercado') return supermercadoGroup.has(cat) || cat === selected;
                return cat === selected;
            });
            filteredOffers = [];
        }

        // Unir manteniendo productos primero y evitando duplicados por id
        const seen = new Set();
        const combined = [];

        filteredProducts.forEach(p => {
            if (!seen.has(p.id)) {
                seen.add(p.id);
                combined.push({ item: p, isOffer: false });
            }
        });

        filteredOffers.forEach(o => {
            if (!seen.has(o.id)) {
                seen.add(o.id);
                combined.push({ item: o, isOffer: true });
            }
        });

        // Si no hay resultados en ninguno de los dos, mostrar mensaje
        if (combined.length === 0) {
            productsContainer.innerHTML = `<p>No se encontraron productos ni ofertas para la categoría seleccionada.</p>`;
            hideLoader();
            return;
        }

        // Renderizar elementos combinados
        combined.forEach(entry => {
            const card = createProductCard(entry.item, entry.isOffer);
            productsContainer.appendChild(card);
        });

        setTimeout(hideLoader, 120);
    }, 120);
}

// Cargar ofertas
/**
 * Renderiza la sección de ofertas/descubiertos (productos con descuento).
 */
function loadOffers(category = null) {
    const offersContainer = document.getElementById('offers-container');
    if (!offersContainer) return;

    const normalize = (s) => {
        if (!s) return '';
        try {
            return String(s).toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();
        } catch (e) {
            return String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        }
    };

    const selected = category ? normalize(category) : null;

    // show loader while filtering offers
    showLoader();

    setTimeout(() => {
        offersContainer.innerHTML = '';

        // Same supermercado grouping for offers
        const supermercadoGroup = new Set(['supermercado', 'huevos', 'granos', 'aceites', 'pastas', 'verduras', 'farmacia', 'farmacias']);


        // Usar productos de la base de datos (remoteProducts) con descuento_activo = 1
        let filtered = [];
        if (typeof remoteProducts !== 'undefined' && Array.isArray(remoteProducts)) {
            filtered = remoteProducts.filter(p => {
                // Considerar 1, true, '1', 'true' como descuento activo
                if (!(p.descuento_activo === 1 || p.descuento_activo === '1' || p.descuento_activo === true || p.descuento_activo === 'true')) return false;
                if (!selected) return true;
                const cat = normalize(p.categoria || p.category);
                if (selected === 'supermercado') {
                    return supermercadoGroup.has(cat) || cat === selected;
                }
                return cat === selected;
            });
        }

        filtered.forEach(product => {
            const precio = Number(product.precio_descuento) || Number(product.precio) || Number(product.price);
            const original = Number(product.precio) || Number(product.originalPrice) || precio;
            const discount = original && precio && original > precio ? Math.round(((original - precio) / original) * 100) : 0;

            const productCard = document.createElement('a');
            productCard.className = 'product-card';
            productCard.href = `product-detail.php?id=${product.id}`;
            productCard.innerHTML = `
                <div style="position: absolute; top: 10px; left: 10px; background-color: var(--primary-color); color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold; z-index: 1;">
                    -${discount}%
                </div>
                <div class="product-image">
                    <img src="${product.imagen || product.image}" alt="${product.nombre || product.name}">
                </div>
                <div class="product-info">
                    <h4 class="product-title">${product.nombre || product.name}</h4>
                    <div class="product-rating">
                        ${generateRatingStars(product.rating || 4.5)}
                        <span style="color: var(--gray-color); font-size: 0.9rem;">${product.rating || 4.5}</span>
                    </div>
                    <div class="product-price">
                        <span style="color: var(--primary-color); font-size: 1.3rem; font-weight: 700;">$${precio.toFixed(2)}</span>
                        <span style="color: var(--gray-color); text-decoration: line-through; margin-left: 5px; font-size: 1rem;">$${original.toFixed(2)}</span>
                    </div>
                    <button class="add-to-cart" data-id="${product.id}">
                        <i class="fas fa-cart-plus"></i> Agregar
                    </button>
                </div>
            `;
            offersContainer.appendChild(productCard);
        });

        setTimeout(hideLoader, 120);
    }, 120);
}

// Renderizar detalle de producto según el id en la URL
/**
 * Busca el parámetro `id` en la URL, obtiene el producto (de `products` o `offers`) y renderiza
 * un detalle completo con imagen, nombre, precio, descripción y botón para agregar al carrito.
 */
function renderProductDetail() {
    const container = document.getElementById('product-detail');
    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const idParam = params.get('id');

    if (!idParam) {
        container.innerHTML = `
            <div class="not-found">
                <h3>Producto no especificado</h3>
                <p>No se indicó el id del producto. <a href="products.php">Volver a productos</a></p>
            </div>
        `;
        return;
    }

    const id = parseInt(idParam, 10);
    if (isNaN(id)) {
        container.innerHTML = `
            <div class="not-found">
                <h3>Id inválido</h3>
                <p>El identificador del producto no es válido. <a href="products.php">Ver productos</a></p>
            </div>
        `;
        return;
    }

    let product = getAllProductsCombined().find(p => Number(p.id) === id);

    if (!product) {
        container.innerHTML = `
            <div class="not-found">
                <h3>Producto no encontrado</h3>
                <p>No existe un producto con id ${id}. <a href="products.php">Ver productos</a></p>
            </div>
        `;
        return;
    }

    const priceHtml = (product.originalPrice && product.originalPrice > product.price)
        ? `<div class="product-price"><span class="price">$${product.price.toFixed(2)}</span> <span class="original">$${product.originalPrice.toFixed(2)}</span></div>`
        : `<div class="product-price"><span class="price">$${product.price.toFixed(2)}</span></div>`;

    container.innerHTML = `
        <div class="product-detail-card">
            <div class="detail-grid">
                <div class="detail-image">
                    <img src="${product.image}" alt="${product.name}">
                </div>
                <div class="detail-info">
                    <h2 class="detail-title">${product.name}</h2>
                    <div class="detail-rating">${generateRatingStars(product.rating)} <span class="rating-number">${product.rating ?? ''}</span></div>
                    ${priceHtml}
                    <p class="detail-description">${product.description ?? ''}</p>
                    <div class="detail-actions">
                        <button class="add-to-cart btn" data-id="${product.id}"><i class="fas fa-cart-plus"></i> Agregar al carrito</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Permitir que el botón use la misma lógica que la lista para agregar al carrito
    const addBtn = container.querySelector('.add-to-cart');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            const pid = parseInt(addBtn.dataset.id, 10);
            addToCart(pid);
            // Pequeña animación/feedback
            showNotification(`${product.name} agregado al carrito`);
        });
    }

    // Safety: force layout styles in case CSS is being overridden in the environment
    const grid = container.querySelector('.detail-grid');
    const imgWrap = container.querySelector('.detail-image');
    const info = container.querySelector('.detail-info');
    const imgEl = container.querySelector('.detail-image img');

    if (grid) {
        grid.style.display = 'flex';
        grid.style.gap = '24px';
        grid.style.alignItems = 'flex-start';
    }

    if (imgWrap) {
        imgWrap.style.flex = '0 0 45%';
        imgWrap.style.maxWidth = '45%';
    }

    if (info) {
        info.style.flex = '1 1 55%';
    }

    if (imgEl) {
        imgEl.style.width = '100%';
        imgEl.style.height = '100%';
        imgEl.style.objectFit = 'cover';
        imgEl.style.display = 'block';
        imgEl.style.maxHeight = '420px';
    }

    // Ensure the .detail-info column matches the rendered image height so content can be centered
    const infoCol = container.querySelector('.detail-info');
    if (imgEl && infoCol) {
        const applyHeight = () => {
            // offsetHeight includes padding/border; use that to match visual height
            const h = imgEl.offsetHeight;
            if (h && h > 0) {
                infoCol.style.minHeight = h + 'px';
                // make sure vertical centering applies
                infoCol.style.display = 'flex';
                infoCol.style.flexDirection = 'column';
                infoCol.style.justifyContent = 'center';
            }
        };

        if (imgEl.complete) {
            // image already loaded
            applyHeight();
        } else {
            imgEl.addEventListener('load', applyHeight);
            // also guard against slow layout changes
            setTimeout(applyHeight, 300);
        }
    }
}




// Función para buscar productos
/**
 * Busca productos por nombre en productos y ofertas
 * @param {string} searchTerm Término de búsqueda
 */
function searchProducts(searchTerm) {
    if (!searchTerm || searchTerm.trim() === '') {
        // Si el término está vacío, mostrar todos los productos y ofertas juntos sin duplicados
        loadCombined();
        return;
    }

    const term = searchTerm.toLowerCase().trim();
    
    // Obtener todos los productos combinados (remotos + locales) y filtrar
    const all = typeof getAllProductsCombined === 'function' ? getAllProductsCombined() : ([]);

    const filteredProducts = all.filter(product => {
        // excluir ofertas (descuento activo)
        if (product.descuento_activo === 1 || product.descuento_activo === '1' || product.descuento_activo === true || product.descuento_activo === 'true') return false;
        const name = (product.nombre || product.name || '').toString().toLowerCase();
        const cat = (product.categoria || product.category || '').toString().toLowerCase();
        const desc = (product.descripcion || product.description || '').toString().toLowerCase();
        return name.includes(term) || cat.includes(term) || desc.includes(term);
    });

    // Filtrar ofertas (productos con descuento activo)
    const filteredOffers = all.filter(product => {
        if (!(product.descuento_activo === 1 || product.descuento_activo === '1' || product.descuento_activo === true || product.descuento_activo === 'true')) return false;
        const name = (product.nombre || product.name || '').toString().toLowerCase();
        const cat = (product.categoria || product.category || '').toString().toLowerCase();
        const desc = (product.descripcion || product.description || '').toString().toLowerCase();
        return name.includes(term) || cat.includes(term) || desc.includes(term);
    });
    
    // Mostrar resultados
    displaySearchResults(filteredProducts, filteredOffers, term);
}

// Mostrar resultados de búsqueda
/**
 * Muestra los resultados de búsqueda en la interfaz
 * @param {Array} productsResult Productos encontrados
 * @param {Array} offersResult Ofertas encontradas
 * @param {string} searchTerm Término buscado
 */
function displaySearchResults(productsResult, offersResult, searchTerm) {
    const productsContainer = document.getElementById('products-container');
    const offersContainer = document.getElementById('offers-container');
    const sectionTitles = document.querySelectorAll('.section-title');
    
    if (!productsContainer) return;
    
    // Mostrar loader
    showLoader();
    
    setTimeout(() => {
        // Limpiar contenedores
        productsContainer.innerHTML = '';
        if (offersContainer) offersContainer.innerHTML = '';
        
        const totalResults = productsResult.length + offersResult.length;
        
        if (totalResults === 0) {
            // Mostrar mensaje de no resultados
            productsContainer.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                    <i class="fas fa-search" style="font-size: 48px; color: var(--gray-color); margin-bottom: 20px;"></i>
                    <h3>No se encontraron productos para "${searchTerm}"</h3>
                    <p>Intenta con otros términos o revisa nuestra tienda</p>
                    <button onclick="clearSearch()" class="btn" style="margin-top: 20px;">Ver todos los productos</button>
                </div>
            `;
            if (offersContainer) offersContainer.style.display = 'none';
            if (sectionTitles.length) sectionTitles.forEach(title => title.style.display = 'none');
            hideLoader();
            return;
        }
        
        // Mostrar resultados de productos
        if (productsResult.length > 0) {
            if (sectionTitles.length && sectionTitles[0]) sectionTitles[0].style.display = 'flex';
            productsResult.forEach(product => {
                const productCard = createProductCard(product, false);
                productsContainer.appendChild(productCard);
            });
        } else {
            if (sectionTitles.length && sectionTitles[0]) sectionTitles[0].style.display = 'none';
        }
        
        // Mostrar resultados de ofertas
        if (offersContainer && offersResult.length > 0) {
            offersContainer.style.display = 'grid';
            if (sectionTitles.length && sectionTitles[1]) sectionTitles[1].style.display = 'flex';
            offersResult.forEach(offer => {
                const offerCard = createProductCard(offer, true);
                offersContainer.appendChild(offerCard);
            });
        } else if (offersContainer) {
            offersContainer.style.display = 'none';
            if (sectionTitles.length && sectionTitles[1]) sectionTitles[1].style.display = 'none';
        }
        
        hideLoader();
    }, 120);
}

// Crear tarjeta de producto reutilizable
/**
 * Crea una tarjeta de producto para mostrar en la interfaz
 * @param {Object} product Datos del producto
 * @param {boolean} isOffer Indica si es una oferta
 * @returns {HTMLElement} Elemento de la tarjeta
 */
function createProductCard(product, isOffer = false) {
    const productCard = document.createElement('a');
    productCard.className = 'product-card';
    productCard.href = `product-detail.php?id=${product.id}`;
    
    let priceHtml = '';
    // Si el producto tiene descuento_activo y precio_descuento, mostrar como oferta
    const isDiscounted = (product.descuento_activo && Number(product.precio_descuento) < Number(product.precio));
    if (isDiscounted) {
        const discount = Math.round(((Number(product.precio) - Number(product.precio_descuento)) / Number(product.precio)) * 100);
        priceHtml = `
            <div class="product-price">
                <span style="color: var(--primary-color); font-size: 1.3rem; font-weight: 700;">$${Number(product.precio_descuento).toFixed(2)}</span>
                <span style="color: var(--gray-color); text-decoration: line-through; margin-left: 5px; font-size: 1rem;">$${Number(product.precio).toFixed(2)}</span>
            </div>
        `;
        productCard.innerHTML = `
            <div style="position: absolute; top: 10px; left: 10px; background-color: var(--primary-color); color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold; z-index: 1;">
                -${discount}%
            </div>
            <div class="product-image">
                <img src="${product.imagen || product.image}" alt="${product.nombre || product.name}">
            </div>
            <div class="product-info">
                <h4 class="product-title">${product.nombre || product.name}</h4>
                <div class="product-rating">
                    ${generateRatingStars(product.rating || 4.5)}
                    <span style="color: var(--gray-color); font-size: 0.9rem;">${product.rating || 4.5}</span>
                </div>
                ${priceHtml}
                <button class="add-to-cart" data-id="${product.id}">
                    <i class="fas fa-cart-plus"></i> Agregar
                </button>
            </div>
        `;
    } else {
        priceHtml = `<div class="product-price">$${Number(product.precio || product.price).toFixed(2)}</div>`;
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.imagen || product.image}" alt="${product.nombre || product.name}">
            </div>
            <div class="product-info">
                <h4 class="product-title">${product.nombre || product.name}</h4>
                <div class="product-rating">
                    ${generateRatingStars(product.rating || 4.5)}
                    <span style="color: var(--gray-color); font-size: 0.9rem;">${product.rating || 4.5}</span>
                </div>
                ${priceHtml}
                <button class="add-to-cart" data-id="${product.id}">
                    <i class="fas fa-cart-plus"></i> Agregar
                </button>
            </div>
        `;
    }
    
    // Agregar evento al botón
    const addButton = productCard.querySelector('.add-to-cart');
    addButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const productId = parseInt(e.currentTarget.dataset.id);
        addToCart(productId);
    });
    
    return productCard;
}

// Limpiar búsqueda y mostrar todos los productos
/**
 * Limpia la búsqueda y restablece la vista normal de productos
 */
function clearSearch() {
    const searchInput = document.querySelector('.search-bar input');
    if (searchInput) {
        searchInput.value = '';
    }
    loadCombined();
    
    // Restablecer visibilidad de secciones
    const offersContainer = document.getElementById('offers-container');
    const sectionTitles = document.querySelectorAll('.section-title');
    if (offersContainer) offersContainer.style.display = 'grid';
    if (sectionTitles.length) {
        sectionTitles.forEach(title => title.style.display = 'flex');
    }
    
    showNotification('Mostrando todos los productos');
}

// Inicializar buscador
/**
 * Configura el evento de búsqueda en el input
 */
function initSearch() {
    const searchInput = document.querySelector('.search-bar input');
    const searchIcon = document.querySelector('.search-bar i');
    
    if (!searchInput) return;
    
    // Buscar al escribir (con debounce para mejor rendimiento)
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = e.target.value;
            // If we're not on products.php, call the API and render results client-side
            if (!window.location.pathname.includes('products.php')) {
                apiSearchAndDisplay(searchTerm);
                return;
            }
            searchProducts(searchTerm);
        }, 300); // Esperar 300ms después de que el usuario deje de escribir
    });
    
    // Buscar al hacer clic en el ícono de búsqueda
    if (searchIcon) {
        searchIcon.addEventListener('click', () => {
            const searchTerm = searchInput.value;
            if (!window.location.pathname.includes('products.php')) {
                apiSearchAndDisplay(searchTerm);
                return;
            }
            searchProducts(searchTerm);
        });
    }
    
    // Buscar al presionar Enter
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const searchTerm = searchInput.value;
            if (!window.location.pathname.includes('products.php')) {
                apiSearchAndDisplay(searchTerm);
                return;
            }
            searchProducts(searchTerm);
        }
    });
}





// Loader helpers (subtle spinner when switching categories)
function ensureLoaderExists() {
    if (document.getElementById('global-loader')) return;
    const overlay = document.createElement('div');
    overlay.id = 'global-loader';
    overlay.className = 'loader-overlay';
    overlay.innerHTML = '<div class="loader" aria-hidden="true"></div>';
    document.body.appendChild(overlay);
}

function showLoader() {
    ensureLoaderExists();
    const el = document.getElementById('global-loader');
    if (el) el.classList.add('active');
}

function hideLoader() {
    const el = document.getElementById('global-loader');
    if (el) el.classList.remove('active');
}

// Preselect country-code based on browser locale (no flag icons)
document.addEventListener('DOMContentLoaded', () => {
    const countrySelect = document.getElementById('country-code');
    if (!countrySelect) return;

    const lang = (navigator.language || navigator.userLanguage || '').toLowerCase();
    const localeMap = {
        'es-pe': '+51', 'es-mx': '+52', 'es-es': '+34', 'en-us': '+1', 'en-gb': '+44',
        'es-co': '+57', 'es-ar': '+54', 'es-cl': '+56', 'es-ve': '+58', 'hi-in': '+91',
        'fr-fr': '+33', 'de-de': '+49'
    };

    let selected = null;
    Object.keys(localeMap).forEach(k => { if (lang.startsWith(k)) selected = localeMap[k]; });

    if (!selected) {
        const base = lang.split('-')[0];
        const baseMap = { 'es': '+51', 'en': '+1', 'fr': '+33', 'de': '+49', 'hi': '+91' };
        if (baseMap[base]) selected = baseMap[base];
    }

    if (selected && [...countrySelect.options].some(o => o.value === selected)) {
        countrySelect.value = selected;
    }
});
