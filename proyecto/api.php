<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

const DB_HOST = '127.0.0.1';
const DB_NAME = 'order_flow';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Envía una respuesta JSON y termina la ejecución.
 * @param array $payload Datos que se enviarán como JSON.
 * @param int $statusCode Código HTTP de la respuesta (por defecto 200).
 */
function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Recupera los datos de la petición POST/RAW y devuelve un array.
 * Intenta usar $_POST primero y, si está vacío, parsea el contenido raw del body.
 * @return array Datos de la petición.
 */
function getRequestData(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $parsed = [];
    parse_str($raw, $parsed);

    return is_array($parsed) ? $parsed : [];
}

/**
 * Crea y devuelve una conexión PDO a la base de datos.
 * Si la base de datos no existe, la crea primero (bootstrap).
 * @return PDO Conexión PDO lista para usarse.
 */
function getConnection(): PDO
{
    $bootstrapDsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $bootstrap = new PDO($bootstrapDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $bootstrap->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/**
 * Crea (si no existen) las tablas necesarias en la base de datos.
 * Define las tablas 'comprador', 'carts' y 'cart_items' y limpia claves foráneas conflictivas.
 * @param PDO $pdo Conexión PDO sobre la cual aplicar el esquema.
 */
function bootstrapSchema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS comprador (
            numero_telefono BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            nombres VARCHAR(100) NOT NULL,
            apellidos VARCHAR(100) NOT NULL,
            `correo_electrónico` VARCHAR(255) NOT NULL UNIQUE,
            `contraseña` VARCHAR(15) NOT NULL,
            direccion_entrega VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec('ALTER TABLE comprador MODIFY COLUMN numero_telefono BIGINT UNSIGNED NOT NULL');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS carts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            session_token VARCHAR(128) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_cart (user_id),
            UNIQUE KEY uniq_session_cart (session_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $foreignKeyQuery = $pdo->query(
        "SELECT CONSTRAINT_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'carts'
           AND REFERENCED_TABLE_NAME IS NOT NULL"
    );

    $foreignKeys = $foreignKeyQuery ? $foreignKeyQuery->fetchAll() : [];
    foreach ($foreignKeys as $foreignKey) {
        if (!isset($foreignKey['CONSTRAINT_NAME'])) {
            continue;
        }

        $constraintName = (string) $foreignKey['CONSTRAINT_NAME'];
        $pdo->exec('ALTER TABLE carts DROP FOREIGN KEY `' . $constraintName . '`');
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cart_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cart_id INT UNSIGNED NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image TEXT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            UNIQUE KEY uniq_cart_product (cart_id, product_id),
            CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Tabla para vendedores
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS vendedor (
            numero_telefono BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            nombres VARCHAR(100) NOT NULL,
            apellidos VARCHAR(100) NOT NULL,
            `correo_electronico` VARCHAR(255) NOT NULL UNIQUE,
            `contrasena` VARCHAR(64) NOT NULL,
            store_name VARCHAR(255) NULL,
            store_address VARCHAR(255) NULL,
            store_hours TEXT NULL,
            store_type VARCHAR(100) NULL,
            tax_id VARCHAR(100) NULL,
            store_image TEXT NULL,
            store_products TEXT NULL,
            status VARCHAR(32) DEFAULT 'pending'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Tabla para productos ofrecidos por vendedores
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS productos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vendedor_phone BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            imagen TEXT NULL,
            precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            descripcion TEXT NULL,
            descuento_activo TINYINT(1) DEFAULT 0,
            precio_descuento DECIMAL(10,2) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_productos_vendedor FOREIGN KEY (vendedor_phone) REFERENCES vendedor(numero_telefono) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

/**
 * Mapea una fila de la tabla 'comprador' a un array de usuario estandarizado usado por la API.
 * @param array $row Fila de la tabla comprador.
 * @return array Datos de usuario normalizados.
 */
/**
 * Mapea una fila de usuario (comprador o vendedor) a la estructura usada por la API.
 * El argumento $role puede ser 'buyer' o 'seller' para mapear campos específicos.
 */
function mapUser(array $row, string $role = 'buyer'): array
{
    $nombres = isset($row['nombres']) ? trim((string) $row['nombres']) : '';
    $apellidos = isset($row['apellidos']) ? trim((string) $row['apellidos']) : '';
    $fullName = trim($nombres . ' ' . $apellidos);

    if ($role === 'seller') {
        return [
            'id' => isset($row['numero_telefono']) ? (int) $row['numero_telefono'] : 0,
            'name' => $fullName !== '' ? $fullName : 'Vendedor',
            'email' => $row['correo_electronico'] ?? '',
            'phone' => isset($row['numero_telefono']) ? (string) $row['numero_telefono'] : '',
            'address' => $row['store_address'] ?? '',
            'role' => 'seller',
            'storeName' => $row['store_name'] ?? null,
            'storeType' => $row['store_type'] ?? null,
            'taxId' => $row['tax_id'] ?? null,
            'storeHours' => $row['store_hours'] ?? null,
            'status' => $row['status'] ?? 'pending',
        ];
    }

    return [
        'id' => isset($row['numero_telefono']) ? (int) $row['numero_telefono'] : 0,
        'name' => $fullName !== '' ? $fullName : 'Comprador',
        'email' => $row['correo_electrónico'] ?? '',
        'phone' => isset($row['numero_telefono']) ? (string) $row['numero_telefono'] : '',
        'address' => $row['direccion_entrega'] ?? '',
        'role' => 'buyer',
        'storeName' => null,
        'storeType' => null,
        'taxId' => null,
        'status' => 'active',
    ];
}

/**
 * Devuelve el usuario actualmente autenticado (si existe) mapeado, o null si no hay sesión.
 * @param PDO $pdo Conexión PDO para buscar el usuario.
 * @return array|null Usuario mapeado o null.
 */
function currentUser(PDO $pdo): ?array
{
    // Prefer seller session if present
    $sellerPhone = isset($_SESSION['vendedor_phone']) ? (int) $_SESSION['vendedor_phone'] : 0;
    if ($sellerPhone > 0) {
        $stmt = $pdo->prepare('SELECT * FROM vendedor WHERE numero_telefono = :numero_telefono LIMIT 1');
        $stmt->execute(['numero_telefono' => $sellerPhone]);
        $seller = $stmt->fetch();
        if ($seller) {
            return mapUser($seller, 'seller');
        }
        unset($_SESSION['vendedor_phone']);
    }

    $buyerPhone = isset($_SESSION['comprador_phone']) ? (int) $_SESSION['comprador_phone'] : 0;
    if ($buyerPhone <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM comprador WHERE numero_telefono = :numero_telefono LIMIT 1');
    $stmt->execute(['numero_telefono' => $buyerPhone]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['comprador_phone']);
        return null;
    }

    return mapUser($user, 'buyer');
}

/**
 * Busca el id del carrito asociado a un usuario registrado.
 * @param PDO $pdo Conexión PDO.
 * @param int $userId Id del usuario.
 * @return int|null Id del carrito o null si no existe.
 */
function findCartIdByUser(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();

    return $row ? (int) $row['id'] : null;
}

/**
 * Busca el id del carrito asociado a un token de sesión (invitado).
 * @param PDO $pdo Conexión PDO.
 * @param string $sessionToken Token de sesión.
 * @return int|null Id del carrito o null si no existe.
 */
function findCartIdBySession(PDO $pdo, string $sessionToken): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE session_token = :session_token LIMIT 1');
    $stmt->execute(['session_token' => $sessionToken]);
    $row = $stmt->fetch();

    return $row ? (int) $row['id'] : null;
}

/**
 * Crea un nuevo carrito y devuelve su id.
 * Puede asociarse a un usuario (user_id) o a un session_token para invitados.
 */
function createCart(PDO $pdo, ?int $userId, ?string $sessionToken): int
{
    $stmt = $pdo->prepare('INSERT INTO carts (user_id, session_token) VALUES (:user_id, :session_token)');
    $stmt->execute([
        'user_id' => $userId,
        'session_token' => $sessionToken,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Fusiona el carrito temporal de sesión de un invitado en el carrito del usuario al iniciar sesión.
 * Si ambos carritos existen, combina cantidades y borra el carrito de sesión.
 */
function mergeSessionCartIntoUser(PDO $pdo, int $userId, string $sessionToken): void
{
    $sessionCartId = findCartIdBySession($pdo, $sessionToken);
    if ($sessionCartId === null) {
        return;
    }

    $userCartId = findCartIdByUser($pdo, $userId);

    if ($userCartId !== null && $userCartId !== $sessionCartId) {
        $itemsStmt = $pdo->prepare('SELECT product_id, product_name, price, image, quantity FROM cart_items WHERE cart_id = :cart_id');
        $itemsStmt->execute(['cart_id' => $sessionCartId]);
        $items = $itemsStmt->fetchAll();

        $insertStmt = $pdo->prepare(
            'INSERT INTO cart_items (cart_id, product_id, product_name, price, image, quantity)
             VALUES (:cart_id, :product_id, :product_name, :price, :image, :quantity)
             ON DUPLICATE KEY UPDATE
             product_name = VALUES(product_name),
             price = VALUES(price),
             image = VALUES(image),
             quantity = quantity + VALUES(quantity)'
        );

        foreach ($items as $item) {
            $insertStmt->execute([
                'cart_id' => $userCartId,
                'product_id' => (int) $item['product_id'],
                'product_name' => $item['product_name'],
                'price' => (float) $item['price'],
                'image' => $item['image'],
                'quantity' => (int) $item['quantity'],
            ]);
        }

        $deleteStmt = $pdo->prepare('DELETE FROM carts WHERE id = :id');
        $deleteStmt->execute(['id' => $sessionCartId]);
        return;
    }

    $updateStmt = $pdo->prepare('UPDATE carts SET user_id = :user_id, session_token = NULL WHERE id = :id');
    $updateStmt->execute([
        'user_id' => $userId,
        'id' => $sessionCartId,
    ]);
}

/**
 * Devuelve el id del carrito existente para un usuario o sesión, o crea uno nuevo si no existe.
 */
function getOrCreateCartId(PDO $pdo, ?int $userId, string $sessionToken): int
{
    if ($userId !== null) {
        $userCartId = findCartIdByUser($pdo, $userId);
        if ($userCartId !== null) {
            return $userCartId;
        }

        $sessionCartId = findCartIdBySession($pdo, $sessionToken);
        if ($sessionCartId !== null) {
            $updateStmt = $pdo->prepare('UPDATE carts SET user_id = :user_id, session_token = NULL WHERE id = :id');
            $updateStmt->execute([
                'user_id' => $userId,
                'id' => $sessionCartId,
            ]);
            return $sessionCartId;
        }

        return createCart($pdo, $userId, null);
    }

    $guestCartId = findCartIdBySession($pdo, $sessionToken);
    if ($guestCartId !== null) {
        return $guestCartId;
    }

    return createCart($pdo, null, $sessionToken);
}

/**
 * Carga los items de un carrito dado (usuario o sesión) y los devuelve como array.
 * @return array Lista de items con id, name, price, image y quantity.
 */
function loadCart(PDO $pdo, ?int $userId, string $sessionToken): array
{
    $cartId = getOrCreateCartId($pdo, $userId, $sessionToken);

    $stmt = $pdo->prepare(
        'SELECT product_id, product_name, price, image, quantity
         FROM cart_items
         WHERE cart_id = :cart_id
         ORDER BY id ASC'
    );
    $stmt->execute(['cart_id' => $cartId]);

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $items[] = [
            'id' => (int) $row['product_id'],
            'name' => $row['product_name'],
            'price' => (float) $row['price'],
            'image' => $row['image'],
            'quantity' => (int) $row['quantity'],
        ];
    }

    return $items;
}

/**
 * Guarda (reemplaza) el contenido del carrito en la base de datos.
 * Recibe un array de items y los inserta en la tabla cart_items dentro de una transacción.
 */
function saveCart(PDO $pdo, ?int $userId, string $sessionToken, array $cart): void
{
    $cartId = getOrCreateCartId($pdo, $userId, $sessionToken);

    $pdo->beginTransaction();

    try {
        $deleteStmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $deleteStmt->execute(['cart_id' => $cartId]);

        $insertStmt = $pdo->prepare(
            'INSERT INTO cart_items (cart_id, product_id, product_name, price, image, quantity)
             VALUES (:cart_id, :product_id, :product_name, :price, :image, :quantity)'
        );

        foreach ($cart as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = isset($item['id']) ? (int) $item['id'] : 0;
            $name = isset($item['name']) ? trim((string) $item['name']) : '';
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

            if ($productId <= 0 || $name === '' || $quantity <= 0) {
                continue;
            }

            $insertStmt->execute([
                'cart_id' => $cartId,
                'product_id' => $productId,
                'product_name' => $name,
                'price' => isset($item['price']) ? (float) $item['price'] : 0.0,
                'image' => isset($item['image']) ? (string) $item['image'] : '',
                'quantity' => $quantity,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$action = isset($_GET['action']) ? (string) $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getConnection();
    bootstrapSchema($pdo);

    if ($action === 'session_state' && $method === 'GET') {
        $user = currentUser($pdo);
        $userId = $user !== null ? (int) $user['id'] : null;
        $cart = loadCart($pdo, $userId, session_id());

        jsonResponse([
            'success' => true,
            'user' => $user,
            'cart' => $cart,
        ]);
    }

    if ($action === 'login' && $method === 'POST') {
        $body = getRequestData();
        $email = isset($body['email']) ? trim((string) $body['email']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';

        if ($email === '' || $password === '') {
            jsonResponse(['success' => false, 'message' => 'Completa email y contraseña'], 422);
        }

        // Try buyer first
        $stmt = $pdo->prepare('SELECT * FROM comprador WHERE `correo_electrónico` = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if ($row && isset($row['contraseña']) && (string) $row['contraseña'] === $password) {
            $userId = (int) $row['numero_telefono'];
            // Ensure any existing seller session is cleared when logging in as buyer
            unset($_SESSION['vendedor_phone']);
            $_SESSION['comprador_phone'] = $userId;
            mergeSessionCartIntoUser($pdo, $userId, session_id());
            $cart = loadCart($pdo, $userId, session_id());

            jsonResponse([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => mapUser($row, 'buyer'),
                'cart' => $cart,
            ]);
        }

        // Try seller
        $stmt2 = $pdo->prepare('SELECT * FROM vendedor WHERE `correo_electronico` = :email LIMIT 1');
        $stmt2->execute(['email' => $email]);
        $sellerRow = $stmt2->fetch();

        if ($sellerRow && isset($sellerRow['contrasena']) && (string) $sellerRow['contrasena'] === $password) {
            $userId = (int) $sellerRow['numero_telefono'];
            // Ensure any existing buyer session is cleared when logging in as seller
            unset($_SESSION['comprador_phone']);
            $_SESSION['vendedor_phone'] = $userId;
            mergeSessionCartIntoUser($pdo, $userId, session_id());
            $cart = loadCart($pdo, $userId, session_id());

            jsonResponse([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => mapUser($sellerRow, 'seller'),
                'cart' => $cart,
            ]);
        }

        jsonResponse(['success' => false, 'message' => 'Email o contraseña incorrectos'], 401);
    }

    if ($action === 'register' && $method === 'POST') {
        $body = getRequestData();

        $name = isset($body['name']) ? trim((string) $body['name']) : '';
        $email = isset($body['email']) ? trim((string) $body['email']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';
        $phoneRaw = isset($body['phone']) ? (string) $body['phone'] : '';
        $address = isset($body['address']) ? trim((string) $body['address']) : '';
        $role = isset($body['role']) ? trim((string)$body['role']) : 'buyer';

        if ($name === '' || $email === '' || $password === '' || $phoneRaw === '' || $address === '') {
            jsonResponse(['success' => false, 'message' => 'Completa los campos obligatorios'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Email inválido'], 422);
        }

        if (strlen($password) < 8) {
            jsonResponse(['success' => false, 'message' => 'La contraseña debe tener mínimo 8 caracteres'], 422);
        }

        if (strlen($password) > 15) {
            jsonResponse(['success' => false, 'message' => 'La contraseña permite máximo 15 caracteres'], 422);
        }

        $phoneDigits = preg_replace('/\D+/', '', $phoneRaw);
        if ($phoneDigits === '') {
            jsonResponse(['success' => false, 'message' => 'Teléfono inválido'], 422);
        }

        $phone = (int) $phoneDigits;
        if ($phone <= 0) {
            jsonResponse(['success' => false, 'message' => 'Teléfono inválido'], 422);
        }

        $names = preg_split('/\s+/', $name);
        if ($names === false || count($names) === 0) {
            jsonResponse(['success' => false, 'message' => 'Nombre inválido'], 422);
        }

        if (count($names) === 1) {
            $firstNames = $names[0];
            $lastNames = '.';
        } else {
            $lastNames = (string) array_pop($names);
            $firstNames = trim(implode(' ', $names));
        }

        // Check email/phone uniqueness across both tables depending on role
        if ($role === 'seller') {
            $existsStmt = $pdo->prepare('SELECT numero_telefono FROM vendedor WHERE `correo_electronico` = :email LIMIT 1');
            $existsStmt->execute(['email' => $email]);
            if ($existsStmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Este email ya está registrado'], 409);
            }

            $phoneExistsStmt = $pdo->prepare('SELECT numero_telefono FROM vendedor WHERE numero_telefono = :numero_telefono LIMIT 1');
            $phoneExistsStmt->execute(['numero_telefono' => $phone]);
            if ($phoneExistsStmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Este teléfono ya está registrado'], 409);
            }

            $storeName = isset($body['storeName']) ? trim((string)$body['storeName']) : '';
            $storeAddress = isset($body['storeAddress']) ? trim((string)$body['storeAddress']) : '';
            $storeType = isset($body['storeType']) ? trim((string)$body['storeType']) : '';
            $taxId = isset($body['taxId']) ? trim((string)$body['taxId']) : '';

            $insertStmt = $pdo->prepare(
                'INSERT INTO vendedor (
                    numero_telefono, nombres, apellidos, `correo_electronico`, `contrasena`, store_name, store_address, store_type, tax_id
                ) VALUES (
                    :numero_telefono, :nombres, :apellidos, :correo_electronico, :contrasena, :store_name, :store_address, :store_type, :tax_id
                )'
            );

            $insertStmt->execute([
                'numero_telefono' => $phone,
                'nombres' => $firstNames,
                'apellidos' => $lastNames,
                'correo_electronico' => $email,
                'contrasena' => $password,
                'store_name' => $storeName,
                'store_address' => $storeAddress,
                'store_type' => $storeType,
                'tax_id' => $taxId,
            ]);

            $userId = $phone;
            // Clear any buyer session when registering a seller
            unset($_SESSION['comprador_phone']);
            $_SESSION['vendedor_phone'] = $userId;

            $stmt = $pdo->prepare('SELECT * FROM vendedor WHERE numero_telefono = :numero_telefono LIMIT 1');
            $stmt->execute(['numero_telefono' => $userId]);
            $newUser = $stmt->fetch();

            $cart = loadCart($pdo, $userId, session_id());

            jsonResponse([
                'success' => true,
                'message' => 'Registro exitoso',
                'user' => $newUser ? mapUser($newUser, 'seller') : null,
                'cart' => $cart,
            ], 201);
        }

        // Default: buyer registration
        $existsStmt = $pdo->prepare('SELECT numero_telefono FROM comprador WHERE `correo_electrónico` = :email LIMIT 1');
        $existsStmt->execute(['email' => $email]);
        if ($existsStmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Este email ya está registrado'], 409);
        }

        $phoneExistsStmt = $pdo->prepare('SELECT numero_telefono FROM comprador WHERE numero_telefono = :numero_telefono LIMIT 1');
        $phoneExistsStmt->execute(['numero_telefono' => $phone]);
        if ($phoneExistsStmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Este teléfono ya está registrado'], 409);
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO comprador (
                numero_telefono, nombres, apellidos, `correo_electrónico`, `contraseña`, direccion_entrega
            ) VALUES (
                :numero_telefono, :nombres, :apellidos, :correo_electronico, :contrasena, :direccion_entrega
            )'
        );

        $insertStmt->execute([
            'numero_telefono' => $phone,
            'nombres' => $firstNames,
            'apellidos' => $lastNames,
            'correo_electronico' => $email,
            'contrasena' => $password,
            'direccion_entrega' => $address,
        ]);

    $userId = $phone;
    // Clear any seller session when registering a buyer
    unset($_SESSION['vendedor_phone']);
    $_SESSION['comprador_phone'] = $userId;

        $stmt = $pdo->prepare('SELECT * FROM comprador WHERE numero_telefono = :numero_telefono LIMIT 1');
        $stmt->execute(['numero_telefono' => $userId]);
        $newUser = $stmt->fetch();

        $cart = loadCart($pdo, $userId, session_id());

        jsonResponse([
            'success' => true,
            'message' => 'Registro exitoso',
            'user' => $newUser ? mapUser($newUser, 'buyer') : null,
            'cart' => $cart,
        ], 201);
    }

    if ($action === 'save_cart' && $method === 'POST') {
        $body = getRequestData();
        $cart = isset($body['cart']) && is_array($body['cart']) ? array_values($body['cart']) : [];

        $user = currentUser($pdo);
        $userId = $user !== null ? (int) $user['id'] : null;
        saveCart($pdo, $userId, session_id(), $cart);

        jsonResponse([
            'success' => true,
            'message' => 'Carrito guardado',
        ]);
    }

    if ($action === 'checkout' && $method === 'POST') {
        $user = currentUser($pdo);
        $userId = $user !== null ? (int) $user['id'] : null;

        $cart = loadCart($pdo, $userId, session_id());
        if (count($cart) === 0) {
            jsonResponse(['success' => false, 'message' => 'Tu carrito está vacío'], 422);
        }

        saveCart($pdo, $userId, session_id(), []);

        jsonResponse([
            'success' => true,
            'message' => 'Compra procesada',
        ]);
    }

    if ($action === 'logout' && $method === 'POST') {
        // Clear both possible session keys
        unset($_SESSION['comprador_phone']);
        unset($_SESSION['vendedor_phone']);

        jsonResponse([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    // Productos: API CRUD
    if ($action === 'products.list' && $method === 'GET') {
        $params = $_GET;
        // Read raw vendedor_phone as string and keep only digits to avoid PHP int overflow on some platforms
        $vendedorPhoneRaw = isset($params['vendedor_phone']) ? (string)$params['vendedor_phone'] : '';
        $vendedorPhone = null;
        if ($vendedorPhoneRaw !== '') {
            $digits = preg_replace('/\D+/', '', $vendedorPhoneRaw);
            if ($digits !== '') {
                $vendedorPhone = $digits; // keep as string (MySQL will cast) to avoid overflow
            }
        }
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;

        // Build vendor fields dynamically to be compatible with older schemas that may lack new columns
        $vendorColsStmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vendedor' AND COLUMN_NAME IN ('store_name','store_image')");
        $vendorColsStmt->execute();
        $vendorCols = $vendorColsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $selectParts = ['p.*'];
        if (in_array('store_name', $vendorCols, true)) {
            $selectParts[] = 'v.store_name AS seller_name';
        }
        if (in_array('store_image', $vendorCols, true)) {
            $selectParts[] = 'v.store_image AS seller_image';
        }
        $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM productos p LEFT JOIN vendedor v ON p.vendedor_phone = v.numero_telefono';
        $conds = [];
        $bind = [];
        if ($vendedorPhone !== null && $vendedorPhone > 0) {
            $conds[] = 'p.vendedor_phone = :vendedor_phone';
            $bind['vendedor_phone'] = $vendedorPhone;
        }

        if (!empty($conds)) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }

        // Some MySQL drivers have issues binding LIMIT as a parameter when native prepares are used.
        // Safely append the integer-cast limit to the query.
        $sql .= ' ORDER BY p.created_at DESC LIMIT ' . (int)$limit;
        $stmt = $pdo->prepare($sql);
        // Execute with the bind array and let PDO infer types; this is more robust for large bigint values
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();

        jsonResponse(['success' => true, 'products' => $rows]);
    }

    if ($action === 'products.get' && $method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID inválido'], 422);
        }

    // Build vendor fields dynamically for compatibility with older schemas
    $vendorColsStmt2 = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vendedor' AND COLUMN_NAME IN ('store_name','store_image')");
    $vendorColsStmt2->execute();
    $vendorCols2 = $vendorColsStmt2->fetchAll(PDO::FETCH_COLUMN, 0);
    $selectParts2 = ['p.*'];
    if (in_array('store_name', $vendorCols2, true)) { $selectParts2[] = 'v.store_name AS seller_name'; }
    if (in_array('store_image', $vendorCols2, true)) { $selectParts2[] = 'v.store_image AS seller_image'; }
    $sqlGet = 'SELECT ' . implode(', ', $selectParts2) . ' FROM productos p LEFT JOIN vendedor v ON p.vendedor_phone = v.numero_telefono WHERE p.id = :id LIMIT 1';
    $stmt = $pdo->prepare($sqlGet);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        jsonResponse(['success' => true, 'product' => $row]);
    }

    if ($action === 'products.create' && $method === 'POST') {
        $user = currentUser($pdo);
        if (!$user || ($user['role'] ?? '') !== 'seller') {
            jsonResponse(['success' => false, 'message' => 'Autenticación de vendedor requerida'], 403);
        }

        $body = getRequestData();
        $nombre = isset($body['nombre']) ? trim((string)$body['nombre']) : '';
        $imagen = isset($body['imagen']) ? trim((string)$body['imagen']) : '';
        $precio = isset($body['precio']) ? (float)$body['precio'] : 0.0;
        $descripcion = isset($body['descripcion']) ? trim((string)$body['descripcion']) : '';
        $descuento_activo = isset($body['descuento_activo']) && ($body['descuento_activo'] === '1' || $body['descuento_activo'] === 'true' || $body['descuento_activo'] === 1) ? 1 : 0;
        $precio_descuento = isset($body['precio_descuento']) ? (float)$body['precio_descuento'] : null;

        if ($nombre === '') {
            jsonResponse(['success' => false, 'message' => 'Nombre requerido'], 422);
        }

        $insert = $pdo->prepare('INSERT INTO productos (vendedor_phone, nombre, imagen, precio, descripcion, descuento_activo, precio_descuento) VALUES (:vendedor_phone, :nombre, :imagen, :precio, :descripcion, :descuento_activo, :precio_descuento)');
        $insert->execute([
            'vendedor_phone' => (int)$user['id'],
            'nombre' => $nombre,
            'imagen' => $imagen,
            'precio' => $precio,
            'descripcion' => $descripcion,
            'descuento_activo' => $descuento_activo,
            'precio_descuento' => $precio_descuento,
        ]);

        $newId = (int)$pdo->lastInsertId();
        jsonResponse(['success' => true, 'id' => $newId], 201);
    }

    if ($action === 'products.update' && $method === 'POST') {
        $user = currentUser($pdo);
        if (!$user || ($user['role'] ?? '') !== 'seller') {
            jsonResponse(['success' => false, 'message' => 'Autenticación de vendedor requerida'], 403);
        }

        $body = getRequestData();
        $id = isset($body['id']) ? (int)$body['id'] : 0;
        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID inválido'], 422);
        }

        $stmt = $pdo->prepare('SELECT vendedor_phone FROM productos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            jsonResponse(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        if ((int)$row['vendedor_phone'] !== (int)$user['id']) {
            jsonResponse(['success' => false, 'message' => 'No tienes permiso para modificar este producto'], 403);
        }

        $fields = [];
        $bind = ['id' => $id];
        if (isset($body['nombre'])) { $fields[] = 'nombre = :nombre'; $bind['nombre'] = trim((string)$body['nombre']); }
        if (isset($body['imagen'])) { $fields[] = 'imagen = :imagen'; $bind['imagen'] = trim((string)$body['imagen']); }
        if (isset($body['precio'])) { $fields[] = 'precio = :precio'; $bind['precio'] = (float)$body['precio']; }
        if (isset($body['descripcion'])) { $fields[] = 'descripcion = :descripcion'; $bind['descripcion'] = trim((string)$body['descripcion']); }
        if (isset($body['descuento_activo'])) { $fields[] = 'descuento_activo = :descuento_activo'; $bind['descuento_activo'] = ($body['descuento_activo'] === '1' || $body['descuento_activo'] === 'true' || $body['descuento_activo'] === 1) ? 1 : 0; }
        if (array_key_exists('precio_descuento', $body)) { $fields[] = 'precio_descuento = :precio_descuento'; $bind['precio_descuento'] = $body['precio_descuento'] === '' ? null : (float)$body['precio_descuento']; }

        if (empty($fields)) {
            jsonResponse(['success' => false, 'message' => 'Nada para actualizar'], 422);
        }

        $sql = 'UPDATE productos SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);

        jsonResponse(['success' => true]);
    }

    if ($action === 'products.delete' && $method === 'POST') {
        $user = currentUser($pdo);
        if (!$user || ($user['role'] ?? '') !== 'seller') {
            jsonResponse(['success' => false, 'message' => 'Autenticación de vendedor requerida'], 403);
        }

        $body = getRequestData();
        $id = isset($body['id']) ? (int)$body['id'] : 0;
        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID inválido'], 422);
        }

        $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id AND vendedor_phone = :vendedor_phone');
        $stmt->execute(['id' => $id, 'vendedor_phone' => (int)$user['id']]);

        jsonResponse(['success' => true]);
    }

    // Upload product image (multipart/form-data)
    if ($action === 'products.upload' && $method === 'POST') {
        $user = currentUser($pdo);
        if (!$user || ($user['role'] ?? '') !== 'seller') {
            jsonResponse(['success' => false, 'message' => 'Autenticación de vendedor requerida'], 403);
        }

        if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
            jsonResponse(['success' => false, 'message' => 'Archivo no recibido'], 422);
        }

        $file = $_FILES['image'];
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed, true)) {
            jsonResponse(['success' => false, 'message' => 'Tipo de archivo no permitido'], 422);
        }

        $uploadDir = __DIR__ . '/uploads/products';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = uniqid('prod_') . '.' . $ext;
        $dest = $uploadDir . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            jsonResponse(['success' => false, 'message' => 'No se pudo mover el archivo'], 500);
        }

        $relative = 'uploads/products/' . $newName;
        jsonResponse(['success' => true, 'path' => $relative], 201);
    }

    jsonResponse([
        'success' => false,
        'message' => 'Ruta no válida',
    ], 404);
} catch (Throwable $e) {
    // Log error to a local file for easier debugging on development environments
    try {
        $logPath = __DIR__ . '/api_errors.log';
        $entry = date('c') . " | ACTION=" . (isset($_GET['action']) ? $_GET['action'] : '(none)') . " | URI=" . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
        $entry .= "GET=" . json_encode($_GET, JSON_UNESCAPED_UNICODE) . "\n";
        $entry .= "POST=" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . "\n";
        $entry .= "ERROR=" . $e->getMessage() . "\n";
        $entry .= $e->getTraceAsString() . "\n\n";
        @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);
    } catch (Throwable $_) {
        // ignore logging errors
    }

    jsonResponse([
        'success' => false,
        'message' => 'Error de servidor',
        'details' => $e->getMessage(),
    ], 500);
}

