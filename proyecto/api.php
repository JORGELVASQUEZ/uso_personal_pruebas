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
}

/**
 * Mapea una fila de la tabla 'comprador' a un array de usuario estandarizado usado por la API.
 * @param array $row Fila de la tabla comprador.
 * @return array Datos de usuario normalizados.
 */
function mapUser(array $row): array
{
    $nombres = isset($row['nombres']) ? trim((string) $row['nombres']) : '';
    $apellidos = isset($row['apellidos']) ? trim((string) $row['apellidos']) : '';
    $fullName = trim($nombres . ' ' . $apellidos);

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

    return mapUser($user);
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

        $stmt = $pdo->prepare('SELECT * FROM comprador WHERE `correo_electrónico` = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!$row || !isset($row['contraseña']) || (string) $row['contraseña'] !== $password) {
            jsonResponse(['success' => false, 'message' => 'Email o contraseña incorrectos'], 401);
        }

        $userId = (int) $row['numero_telefono'];
        $_SESSION['comprador_phone'] = $userId;

        mergeSessionCartIntoUser($pdo, $userId, session_id());
        $cart = loadCart($pdo, $userId, session_id());

        jsonResponse([
            'success' => true,
            'message' => 'Login exitoso',
            'user' => mapUser($row),
            'cart' => $cart,
        ]);
    }

    if ($action === 'register' && $method === 'POST') {
        $body = getRequestData();

        $name = isset($body['name']) ? trim((string) $body['name']) : '';
        $email = isset($body['email']) ? trim((string) $body['email']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';
        $phoneRaw = isset($body['phone']) ? (string) $body['phone'] : '';
        $address = isset($body['address']) ? trim((string) $body['address']) : '';

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
        $_SESSION['comprador_phone'] = $userId;

        $stmt = $pdo->prepare('SELECT * FROM comprador WHERE numero_telefono = :numero_telefono LIMIT 1');
        $stmt->execute(['numero_telefono' => $userId]);
        $newUser = $stmt->fetch();

        $cart = loadCart($pdo, $userId, session_id());

        jsonResponse([
            'success' => true,
            'message' => 'Registro exitoso',
            'user' => $newUser ? mapUser($newUser) : null,
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
        unset($_SESSION['comprador_phone']);

        jsonResponse([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    jsonResponse([
        'success' => false,
        'message' => 'Ruta no válida',
    ], 404);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error de servidor',
        'details' => $e->getMessage(),
    ], 500);
}

