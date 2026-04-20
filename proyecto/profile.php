<?php
declare(strict_types=1);

session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'order_flow';
const DB_USER = 'root';
const DB_PASS = '';

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function findCartIdByUser(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();

    return $row ? (int) $row['id'] : null;
}

function mergeCartRecords(PDO $pdo, int $fromCartId, int $toCartId): void
{
    $itemsStmt = $pdo->prepare('SELECT product_id, product_name, price, image, quantity FROM cart_items WHERE cart_id = :cart_id');
    $itemsStmt->execute(['cart_id' => $fromCartId]);
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
            'cart_id' => $toCartId,
            'product_id' => (int) $item['product_id'],
            'product_name' => $item['product_name'],
            'price' => (float) $item['price'],
            'image' => $item['image'],
            'quantity' => (int) $item['quantity'],
        ]);
    }

    $deleteStmt = $pdo->prepare('DELETE FROM carts WHERE id = :id');
    $deleteStmt->execute(['id' => $fromCartId]);
}

// Detect whether the current session is a seller or a buyer
$sellerPhone = isset($_SESSION['vendedor_phone']) ? (int) $_SESSION['vendedor_phone'] : 0;
$buyerPhone = isset($_SESSION['comprador_phone']) ? (int) $_SESSION['comprador_phone'] : 0;

$isSeller = $sellerPhone > 0;
$identifier = $isSeller ? $sellerPhone : $buyerPhone;

if ($identifier <= 0) {
    header('Location: login.php?redirect=profile');
    exit;
}

$profile = null;
$errorMessage = '';
$successMessage = '';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    if ($isSeller) {
        // Use SELECT * to avoid errors if the DB schema hasn't been migrated yet
        $stmt = $pdo->prepare(
            'SELECT *
             FROM vendedor
             WHERE numero_telefono = :numero_telefono
             LIMIT 1'
        );
        $stmt->execute(['numero_telefono' => $identifier]);
        $row = $stmt->fetch();

        if (!$row) {
            unset($_SESSION['vendedor_phone']);
            header('Location: login.php?redirect=profile');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $storeName = isset($_POST['store_name']) ? trim((string) $_POST['store_name']) : '';
            $storeAddress = isset($_POST['store_address']) ? trim((string) $_POST['store_address']) : '';
            // hours will be an array: hours[day][closed|open|close]
            $hoursInput = isset($_POST['hours']) && is_array($_POST['hours']) ? $_POST['hours'] : null;
            $storeType = isset($_POST['store_type']) ? trim((string) $_POST['store_type']) : '';
            $taxId = isset($_POST['tax_id']) ? trim((string) $_POST['tax_id']) : '';

                if ($storeName === '' || $storeAddress === '') {
                    $errorMessage = 'El nombre y la dirección de la tienda son obligatorios.';
                } else {
                // Normalize hours input into a JSON-serializable structure
                $normalizedHours = null;
                if (is_array($hoursInput)) {
                    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                    $normalizedHours = [];
                    foreach ($days as $day) {
                        $d = $hoursInput[$day] ?? [];
                        $closed = isset($d['closed']) && ($d['closed'] === '1' || $d['closed'] === 'on' || $d['closed'] === 'true');
                        $open = isset($d['open']) ? trim((string) $d['open']) : '';
                        $close = isset($d['close']) ? trim((string) $d['close']) : '';

                        // If closed, ignore open/close values
                        if ($closed) {
                            $normalizedHours[$day] = [
                                'closed' => true,
                                'open' => null,
                                'close' => null,
                            ];
                        } else {
                            // Ensure times are in HH:MM or empty
                            $normalizedHours[$day] = [
                                'closed' => false,
                                'open' => $open !== '' ? $open : null,
                                'close' => $close !== '' ? $close : null,
                            ];
                        }
                    }
                }

                // handle store image upload (optional)
                $storeImagePath = null;
                if (isset($_FILES['store_image']) && is_uploaded_file($_FILES['store_image']['tmp_name'])) {
                    $uploadDir = __DIR__ . '/uploads/stores';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $origName = basename($_FILES['store_image']['name']);
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','webp','gif'];
                    if (in_array($ext, $allowed, true)) {
                        $newName = uniqid('store_') . '.' . $ext;
                        $dest = $uploadDir . '/' . $newName;
                        if (move_uploaded_file($_FILES['store_image']['tmp_name'], $dest)) {
                            // store relative path
                            $storeImagePath = 'uploads/stores/' . $newName;
                        }
                    }
                }
                // if no new image uploaded, preserve existing image path
                if ($storeImagePath === null) {
                    $storeImagePath = isset($row['store_image']) ? $row['store_image'] : null;
                }

                // normalize products list (simple: name, price, image_url)
                $productsInput = isset($_POST['products']) && is_array($_POST['products']) ? $_POST['products'] : [];
                $normalizedProducts = [];
                foreach ($productsInput as $p) {
                    $pname = isset($p['name']) ? trim((string) $p['name']) : '';
                    $pprice = isset($p['price']) ? trim((string) $p['price']) : '';
                    $pimage = isset($p['image']) ? trim((string) $p['image']) : '';
                    if ($pname === '') continue;
                    $normalizedProducts[] = [
                        'name' => $pname,
                        'price' => $pprice !== '' ? $pprice : null,
                        'image' => $pimage !== '' ? $pimage : null,
                    ];
                }

                try {
                    // Determine which columns exist in the vendedor table so we only update available columns
                    $colStmt = $pdo->prepare(
                        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
                    );
                    $colStmt->execute(['schema' => DB_NAME, 'table' => 'vendedor']);
                    $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
                    $cols = is_array($cols) ? array_map('strtolower', $cols) : [];

                    $allowed = [
                        'store_name' => $storeName,
                        'store_address' => $storeAddress,
                        'store_hours' => $normalizedHours !== null ? json_encode($normalizedHours, JSON_UNESCAPED_UNICODE) : null,
                        'store_type' => $storeType,
                        'tax_id' => $taxId,
                        'store_image' => $storeImagePath,
                        'store_products' => !empty($normalizedProducts) ? json_encode($normalizedProducts, JSON_UNESCAPED_UNICODE) : null,
                    ];

                    $setParts = [];
                    $params = [];
                    foreach ($allowed as $col => $val) {
                        if (in_array($col, $cols, true)) {
                            $setParts[] = "$col = :$col";
                            $params[$col] = $val;
                        }
                    }

                    if (empty($setParts)) {
                        // nothing to update on this DB schema
                        $errorMessage = 'No hay campos de tienda disponibles para actualizar en la base de datos.';
                    } else {
                        $sql = 'UPDATE vendedor SET ' . implode(', ', $setParts) . ' WHERE numero_telefono = :numero_telefono';
                        $params['numero_telefono'] = $identifier;
                        $updateStmt = $pdo->prepare($sql);
                        $updateStmt->execute($params);

                        $successMessage = 'Datos de la tienda actualizados correctamente.';

                        // If a dedicated productos table exists, sync normalized products into it
                        $colCheck = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'productos'");
                        $colCheck->execute(['schema' => DB_NAME]);
                        $hasProductos = (bool) $colCheck->fetchColumn();
                        if ($hasProductos) {
                            // replace vendor products: delete existing and insert new
                            $pdo->beginTransaction();
                            try {
                                $del = $pdo->prepare('DELETE FROM productos WHERE vendedor_phone = :vendedor_phone');
                                $del->execute(['vendedor_phone' => $identifier]);

                                if (!empty($normalizedProducts)) {
                                    $ins = $pdo->prepare('INSERT INTO productos (vendedor_phone, nombre, imagen, precio, descripcion, categoria, descuento_activo, precio_descuento) VALUES (:vendedor_phone, :nombre, :imagen, :precio, :descripcion, :categoria, :descuento_activo, :precio_descuento)');
                                    foreach ($normalizedProducts as $p) {
                                        $ins->execute([
                                            'vendedor_phone' => $identifier,
                                            'nombre' => $p['name'],
                                            'imagen' => $p['image'] ?? null,
                                            'precio' => $p['price'] !== null ? (float) $p['price'] : 0.0,
                                            'descripcion' => null,
                                            'categoria' => null,
                                            'descuento_activo' => 0,
                                            'precio_descuento' => null,
                                        ]);
                                    }
                                }

                                $pdo->commit();
                            } catch (Throwable $txEx) {
                                if ($pdo->inTransaction()) $pdo->rollBack();
                                // ignore product sync errors but keep user update result
                            }
                        }

                        // reload using SELECT * for compatibility with DB schema
                        $reloadStmt = $pdo->prepare('SELECT * FROM vendedor WHERE numero_telefono = :numero_telefono LIMIT 1');
                        $reloadStmt->execute(['numero_telefono' => $identifier]);
                        $reloadedRow = $reloadStmt->fetch();
                        if ($reloadedRow) {
                            $row = $reloadedRow;
                        }
                    }
                } catch (Throwable $ex) {
                    $errorMessage = 'No fue posible actualizar los datos de la tienda: ' . $ex->getMessage();
                }
            }
        }

        $fullName = trim((string) $row['nombres'] . ' ' . (string) $row['apellidos']);
        // Attempt to decode store_hours JSON; if decode fails keep original string
        $decodedHours = null;
        if (!empty($row['store_hours'])) {
            $tmp = json_decode((string) $row['store_hours'], true);
            if (is_array($tmp)) {
                $decodedHours = $tmp;
            }
        }

        // decode store_products
        $decodedProducts = null;
        if (!empty($row['store_products'])) {
            $tmpP = json_decode((string) $row['store_products'], true);
            if (is_array($tmpP)) {
                $decodedProducts = $tmpP;
            }
        }

        $profile = [
            'role' => 'seller',
            'name' => $fullName !== '' ? $fullName : 'Vendedor',
            'email' => (string) $row['correo_electronico'],
            'phone' => (string) $row['numero_telefono'],
            'storeName' => (string) ($row['store_name'] ?? ''),
            'storeAddress' => (string) ($row['store_address'] ?? ''),
            // storeHours will be an array (decoded) or null
            'storeHours' => $decodedHours,
            'storeImage' => (string) ($row['store_image'] ?? ''),
            'storeProducts' => $decodedProducts,
            'storeType' => (string) ($row['store_type'] ?? ''),
            'taxId' => (string) ($row['tax_id'] ?? ''),
            'status' => (string) ($row['status'] ?? 'pending'),
        ];
    } else {
        $stmt = $pdo->prepare(
            'SELECT numero_telefono, nombres, apellidos, `correo_electrónico`, direccion_entrega
             FROM comprador
             WHERE numero_telefono = :numero_telefono
             LIMIT 1'
        );
        $stmt->execute(['numero_telefono' => $identifier]);
        $row = $stmt->fetch();

        if (!$row) {
            unset($_SESSION['comprador_phone']);
            header('Location: login.php?redirect=profile');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $phoneRaw = isset($_POST['phone']) ? trim((string) $_POST['phone']) : '';
            $address = isset($_POST['address']) ? trim((string) $_POST['address']) : '';

            if ($phoneRaw === '' || $address === '') {
                $errorMessage = 'Debes completar teléfono y dirección.';
            } else {
                $phoneDigits = preg_replace('/\D+/', '', $phoneRaw);
                $newPhone = $phoneDigits !== null && $phoneDigits !== '' ? (int) $phoneDigits : 0;

                if ($newPhone <= 0) {
                    $errorMessage = 'El teléfono no es válido.';
                } else {
                    $currentPhone = (int) $row['numero_telefono'];

                    $pdo->beginTransaction();

                    try {
                        if ($newPhone !== $currentPhone) {
                            $existsStmt = $pdo->prepare(
                                'SELECT numero_telefono
                                 FROM comprador
                                 WHERE numero_telefono = :numero_telefono
                                 LIMIT 1'
                            );
                            $existsStmt->execute(['numero_telefono' => $newPhone]);

                            if ($existsStmt->fetch()) {
                                throw new RuntimeException('El teléfono ya está registrado en otra cuenta.');
                            }
                        }

                        $updateStmt = $pdo->prepare(
                            'UPDATE comprador
                             SET numero_telefono = :nuevo_telefono,
                                 direccion_entrega = :direccion_entrega
                             WHERE numero_telefono = :telefono_actual'
                        );
                        $updateStmt->execute([
                            'nuevo_telefono' => $newPhone,
                            'direccion_entrega' => $address,
                            'telefono_actual' => $currentPhone,
                        ]);

                        if ($newPhone !== $currentPhone) {
                            $oldCartId = findCartIdByUser($pdo, $currentPhone);
                            $newCartId = findCartIdByUser($pdo, $newPhone);

                            if ($oldCartId !== null) {
                                if ($newCartId === null) {
                                    $moveCartStmt = $pdo->prepare('UPDATE carts SET user_id = :new_user_id WHERE id = :cart_id');
                                    $moveCartStmt->execute([
                                        'new_user_id' => $newPhone,
                                        'cart_id' => $oldCartId,
                                    ]);
                                } elseif ($newCartId !== $oldCartId) {
                                    mergeCartRecords($pdo, $oldCartId, $newCartId);
                                }
                            }
                        }

                        $pdo->commit();

                        $_SESSION['comprador_phone'] = $newPhone;
                        $identifier = $newPhone;
                        $successMessage = 'Perfil actualizado correctamente.';

                        $reloadStmt = $pdo->prepare(
                            'SELECT numero_telefono, nombres, apellidos, `correo_electrónico`, direccion_entrega
                             FROM comprador
                             WHERE numero_telefono = :numero_telefono
                             LIMIT 1'
                        );
                        $reloadStmt->execute(['numero_telefono' => $identifier]);
                        $reloadedRow = $reloadStmt->fetch();
                        if ($reloadedRow) {
                            $row = $reloadedRow;
                        }
                    } catch (Throwable $exception) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        $errorMessage = $exception instanceof RuntimeException
                            ? $exception->getMessage()
                            : 'No fue posible actualizar tu perfil.';
                    }
                }
            }
        }

        $fullName = trim((string) $row['nombres'] . ' ' . (string) $row['apellidos']);
        $profile = [
            'role' => 'buyer',
            'name' => $fullName !== '' ? $fullName : 'Comprador',
            'email' => (string) $row['correo_electrónico'],
            'phone' => (string) $row['numero_telefono'],
            'address' => (string) $row['direccion_entrega'],
        ];
    }
} catch (Throwable $exception) {
    $errorMessage = 'No fue posible cargar tu perfil en este momento.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - RapiMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="profile-fixes.css">
    <style>
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin: 30px auto;
            max-width: 700px;
        }

        .profile-item {
            padding: 14px 0;
            border-bottom: 1px solid #ececec;
        }

        .profile-item:last-child {
            border-bottom: none;
        }

        .profile-label {
            display: block;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .profile-value {
            color: #1f2937;
            font-size: 1rem;
            font-weight: 500;
        }

        .profile-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .profile-title i {
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        .profile-form {
            margin-top: 22px;
            border-top: 1px solid #ececec;
            padding-top: 20px;
        }

        .profile-actions {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
        }

        .message-error {
            color: #dc3545;
            margin-bottom: 12px;
        }

        .message-success {
            color: #15803d;
            margin-bottom: 12px;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 8px;
        }
        @media (max-width: 700px) {
            .schedule-grid { grid-template-columns: 1fr; }
        }
        .schedule-day {
            background: #fafafa;
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 8px;
            display: grid;
            grid-template-columns: 1fr auto;
            grid-template-rows: auto auto;
            gap: 8px;
            min-height: 96px; /* keep uniform height to align rows */
        }
        .schedule-day .schedule-row.header {
            grid-column: 1 / -1;
            display:flex;
            align-items:center;
        }
        .schedule-day .schedule-row.times {
            grid-column: 1 / -1;
            display:flex;
            gap:8px;
            align-items:center;
            justify-content: flex-start;
            flex-wrap: wrap;
        }
        /* make labels and time inputs align and prevent .form-control full-width from expanding */
        .schedule-day .schedule-row.times .profile-label { min-width: 64px; color: #0891b2; margin: 0; }
        .schedule-day .form-control.time-input { width: 110px !important; max-width: 110px !important; }
        /* ensure the time inputs render consistently inside the card */
        .schedule-day .time-input { box-sizing: border-box; }
        .schedule-day strong { font-size: 0.95rem; }
        .schedule-day label { margin-left: auto; font-weight: normal; }
        .time-input { width: 110px; }
        /* hide header filters on profile page */
        .profile-page .header-bottom { display: none; }
        .profile-page .header-top { margin-bottom: 0; }
    </style>
</head>
<body class="profile-page">
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
        <div class="profile-card">
            <div class="profile-title">
                <i class="fas fa-user-circle"></i>
                <h2>Mi Perfil</h2>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <p class="message-error"><?= escapeHtml($errorMessage); ?></p>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <p class="message-success"><?= escapeHtml($successMessage); ?></p>
            <?php endif; ?>

            <?php if ($profile !== null): ?>
                <div class="profile-item">
                    <span class="profile-label">Nombre completo</span>
                    <span class="profile-value"><?= escapeHtml($profile['name']); ?></span>
                </div>
                <div class="profile-item">
                    <span class="profile-label">Correo</span>
                    <span class="profile-value"><?= escapeHtml($profile['email']); ?></span>
                </div>

                <?php if (($profile['role'] ?? '') === 'buyer'): ?>
                <form method="post" class="profile-form">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-control"
                            required
                            value="<?= escapeHtml($profile['phone']); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="address">Dirección de entrega</label>
                        <textarea
                            id="address"
                            name="address"
                            class="form-control"
                            rows="3"
                            required
                        ><?= escapeHtml($profile['address']); ?></textarea>
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="btn">Guardar cambios</button>
                    </div>
                </form>
                <?php else: ?>
                <form method="post" class="profile-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <div class="profile-value"><?= escapeHtml($profile['phone']); ?></div>
                    </div>

                    <div class="form-group">
                        <label>Foto de la tienda</label>
                        <?php if (!empty($profile['storeImage'])): ?>
                            <div style="margin-bottom:8px;">
                                <img src="<?= escapeHtml($profile['storeImage']); ?>" alt="Foto tienda" style="max-width:160px; border-radius:8px; border:1px solid #eee;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="store_image" accept="image/*">
                        <small class="form-help">Sube una imagen representativa de tu tienda (jpg, png, webp).</small>
                    </div>

                    <div class="form-group">
                        <label for="store_name">Nombre de la tienda</label>
                        <input type="text" id="store_name" name="store_name" class="form-control" required value="<?= escapeHtml($profile['storeName']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="store_address">Dirección de la tienda</label>
                        <textarea id="store_address" name="store_address" class="form-control" rows="3" required><?= escapeHtml($profile['storeAddress']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Horarios (por día)</label>
                        <div class="schedule-grid">
                            <?php
                                $days = [
                                    'monday' => 'Lunes',
                                    'tuesday' => 'Martes',
                                    'wednesday' => 'Miércoles',
                                    'thursday' => 'Jueves',
                                    'friday' => 'Viernes',
                                    'saturday' => 'Sábado',
                                    'sunday' => 'Domingo',
                                ];

                                $hours = is_array($profile['storeHours']) ? $profile['storeHours'] : [];

                                foreach ($days as $key => $label) {
                                    $d = $hours[$key] ?? null;
                                    $closed = $d && !empty($d['closed']);
                                    $open = $d && !empty($d['open']) ? $d['open'] : '';
                                    $close = $d && !empty($d['close']) ? $d['close'] : '';
                            ?>
                            <div class="schedule-day">
                                <div class="schedule-row header">
                                    <strong><?= escapeHtml($label); ?></strong>
                                    <label>
                                        <input type="checkbox" name="hours[<?= $key; ?>][closed]" value="1" <?= $closed ? 'checked' : ''; ?>> Cerrado
                                    </label>
                                </div>
                                <div class="schedule-row times">
                                    <label class="profile-label">Apertura</label>
                                    <input type="time" name="hours[<?= $key; ?>][open]" class="form-control time-input" value="<?= escapeHtml($open); ?>">
                                    <label class="profile-label">Cierre</label>
                                    <input type="time" name="hours[<?= $key; ?>][close]" class="form-control time-input" value="<?= escapeHtml($close); ?>">
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="store_type">Tipo de tienda</label>
                        <input type="text" id="store_type" name="store_type" class="form-control" value="<?= escapeHtml($profile['storeType']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="tax_id">RUC / Identificación fiscal</label>
                        <input type="text" id="tax_id" name="tax_id" class="form-control" value="<?= escapeHtml($profile['taxId']); ?>">
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="btn">Guardar cambios</button>
                    </div>
                </form>

                <!-- Product manager: separado del formulario de la tienda. -->
                <div class="form-group" id="product-manager-root">
                    <label class="section-label">Productos de la tienda</label>
                    <div id="product-manager">
                        <div id="pm-list"></div>

                        <div id="pm-create" class="pm-create">
                            <h4 class="pm-title">Agregar producto</h4>
                            <div class="pm-grid">
                                <div class="pm-left">
                                    <input type="text" id="pm-name" placeholder="Nombre" class="form-control" />
                                    <input type="text" id="pm-image" placeholder="URL imagen (opcional)" class="form-control" />
                                    <input type="file" id="pm-image-file" accept="image/*">
                                    <img id="pm-image-preview" src="" alt="" />
                                        <textarea id="pm-desc" placeholder="Descripción" class="form-control" rows="3"></textarea>
                                        <label for="pm-category" style="margin-top:8px; display:block;">Categoría</label>
                                        <select id="pm-category" class="form-control">
                                            <option value="">-- Seleccionar categoría --</option>
                                            <option value="supermercado">Supermercado</option>
                                            <option value="bebidas">Bebidas</option>
                                            <option value="lacteos">Lácteos</option>
                                            <option value="snacks">Snacks</option>
                                            <option value="otros">Otros</option>
                                        </select>
                                </div>
                                <div class="pm-right">
                                    <input type="number" id="pm-price" placeholder="Precio" class="form-control pm-price" step="0.01">
                                        <div style="margin-top:8px;">
                                            <label><input type="checkbox" id="pm-discount-active"> Tiene descuento</label>
                                            <input type="number" id="pm-discount-price" placeholder="Precio descuento" class="form-control" step="0.01" style="margin-top:6px; display:none;" />
                                        </div>
                                    <div class="pm-actions">
                                        <button type="button" id="pm-create-btn" class="btn pm-create">Crear producto</button>
                                        <button type="button" id="pm-clear-btn" class="btn pm-clear">Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="message-error">No fue posible cargar tu perfil.</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script>
    (function(){
        const sellerPhone = <?= json_encode($profile['phone'] ?? ''); ?>;
        const legacyProducts = <?= json_encode(is_array($profile['storeProducts']) ? $profile['storeProducts'] : []); ?>;
        let pmProducts = [];

        function createElementFromHTML(html) {
            const div = document.createElement('div');
            div.innerHTML = html.trim();
            return div.firstChild;
        }

        async function fetchProducts() {
            try {
                const resp = await fetch(`api.php?action=products.list&vendedor_phone=${encodeURIComponent(sellerPhone)}`);
                const data = await resp.json();
                if (data && data.success && Array.isArray(data.products) && data.products.length > 0) {
                    pmProducts = data.products;
                } else {
                    // fallback to legacy products if any
                    pmProducts = Array.isArray(legacyProducts) && legacyProducts.length > 0 ? legacyProducts.map((p, i) => ({
                        id: null,
                        nombre: p.name ?? p.nombre ?? '',
                        imagen: p.image ?? p.imagen ?? '',
                        descripcion: p.description ?? p.descripcion ?? p.desc ?? '',
                        precio: p.price ?? p.precio ?? 0,
                        descuento_activo: 0,
                        precio_descuento: null,
                    })) : [];
                }
            } catch (err) {
                pmProducts = Array.isArray(legacyProducts) ? legacyProducts : [];
            }
            renderProductsList();
        }

        function renderProductsList() {
            const list = document.getElementById('pm-list');
            list.innerHTML = '';
            if (!pmProducts || pmProducts.length === 0) {
                list.innerHTML = '<p style="color:#6b7280;">No hay productos aún. Usa el formulario "Agregar producto" para crear uno.</p>';
                return;
            }

                pmProducts.forEach((p) => {
                const idAttr = p.id ? `data-id=\"${p.id}\"` : '';
                const html = `
                    <div class="product-card" ${idAttr}>
                        <div class="pm-thumb-wrap">
                            <img class="pm-thumb" src="${escapeHtml(p.imagen || p.seller_image || '')}" alt="">
                        </div>
                        <div class="pm-main">
                            <div class="pm-row">
                                <input type="text" class="pm-input pm-name" placeholder="Nombre" value="${escapeHtml(p.nombre || p.name || '')}">
                                <input type="number" class="pm-input pm-price" placeholder="Precio" value="${escapeHtml(String(p.precio || p.price || 0))}" step="0.01">
                            </div>
                            <div class="pm-row">
                                <input type="text" class="pm-input pm-image" placeholder="URL imagen" value="${escapeHtml(p.imagen || p.image || '')}">
                                <input type="file" class="pm-input pm-image-file" accept="image/*">
                            </div>
                            <div class="pm-row">
                                <label style="display:block; margin-bottom:6px;">Categoría</label>
                                <select class="pm-input pm-category">
                                    <option value="">-- Seleccionar --</option>
                                    <option value="supermercado">Supermercado</option>
                                    <option value="bebidas">Bebidas</option>
                                    <option value="lacteos">Lácteos</option>
                                    <option value="snacks">Snacks</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>
                            <div class="pm-row">
                                <label><input type="checkbox" class="pm-discount-active"> Tiene descuento</label>
                                <input type="number" class="pm-input pm-discount-price" placeholder="Precio descuento" step="0.01" style="display:none; margin-left:8px;" />
                            </div>
                            <div class="pm-row">
                                <textarea class="pm-input pm-desc" placeholder="Descripción" rows="2">${escapeHtml(p.descripcion || p.description || '')}</textarea>
                            </div>
                            <div class="pm-actions">
                                <button class="btn btn-outline pm-delete">Eliminar</button>
                                <button class="btn pm-save">Guardar</button>
                            </div>
                        </div>
                    </div>
                `;

                const node = createElementFromHTML(html);
                if (p.id) node.setAttribute('data-id', p.id);

                // wire image file preview
                const fileInput = node.querySelector('.pm-image-file');
                const urlInput = node.querySelector('.pm-image');
                const thumb = node.querySelector('.pm-thumb');
                if (fileInput) {
                    fileInput.addEventListener('change', function(e){
                        const f = fileInput.files && fileInput.files[0];
                        if (f) {
                            const reader = new FileReader();
                            reader.onload = function(ev){ thumb.src = ev.target.result; };
                            reader.readAsDataURL(f);
                        } else {
                            // restore to url value
                            thumb.src = urlInput.value || '';
                        }
                    });
                }

                // set category + discount fields initial state
                const catSelect = node.querySelector('.pm-category');
                if (catSelect) {
                    const raw = (p.categoria || p.categoria_name || p.category || '') + '';
                    const val = String(raw || '').toLowerCase();
                    for (let i = 0; i < catSelect.options.length; i++) {
                        const opt = (catSelect.options[i].value || '').toLowerCase();
                        if (opt === val) { catSelect.selectedIndex = i; break; }
                    }
                }
                const discountChk = node.querySelector('.pm-discount-active');
                const discountPriceInput = node.querySelector('.pm-discount-price');
                if (discountChk) {
                    const active = Number(p.descuento_activo || p.discount_active || 0) === 1;
                    discountChk.checked = active;
                    if (discountPriceInput) {
                        discountPriceInput.style.display = active ? 'inline-block' : 'none';
                        discountPriceInput.value = p.precio_descuento != null ? String(p.precio_descuento) : '';
                    }
                    discountChk.addEventListener('change', function(){
                        if (discountPriceInput) discountPriceInput.style.display = discountChk.checked ? 'inline-block' : 'none';
                    });
                }

                // wire buttons
                node.querySelector('.pm-save').addEventListener('click', async () => {
                    const name = node.querySelector('.pm-name').value.trim();
                    const price = node.querySelector('.pm-price').value;
                    const image = node.querySelector('.pm-image').value.trim();
                    const newFile = node.querySelector('.pm-image-file') && node.querySelector('.pm-image-file').files && node.querySelector('.pm-image-file').files[0];
                    const desc = node.querySelector('.pm-desc').value.trim();
                    const categoria = node.querySelector('.pm-category') ? node.querySelector('.pm-category').value : '';
                    const descuentoActivo = node.querySelector('.pm-discount-active') ? (node.querySelector('.pm-discount-active').checked ? 1 : 0) : 0;
                    const precioDescuento = node.querySelector('.pm-discount-price') ? node.querySelector('.pm-discount-price').value : '';

                    if (!name) { showNotification('Nombre requerido', 'warning'); return; }
                    if (price === '' || isNaN(Number(price))) { showNotification('Precio inválido', 'warning'); return; }

                    try {
                        let finalImage = image;
                        if (newFile) {
                            // upload image first
                            const uploaded = await uploadImage(newFile);
                            finalImage = uploaded;
                        }

                        if (p.id) {
                            // update
                            const body = { id: p.id, nombre: name, precio: price, imagen: finalImage, descripcion: desc, categoria: categoria, descuento_activo: descuentoActivo, precio_descuento: precioDescuento };
                            const res = await apiRequest('products.update', body, 'POST');
                            if (res.success) {
                                showNotification('Producto actualizado');
                                fetchProducts();
                            } else {
                                showNotification(res.message || 'No se pudo actualizar', 'error');
                            }
                        } else {
                            // create
                            const body = { nombre: name, precio: price, imagen: finalImage, descripcion: desc, categoria: categoria, descuento_activo: descuentoActivo, precio_descuento: precioDescuento };
                            const res = await apiRequest('products.create', body, 'POST');
                            if (res.success) {
                                showNotification('Producto creado');
                                fetchProducts();
                            } else {
                                showNotification(res.message || 'No se pudo crear', 'error');
                            }
                        }
                    } catch (err) {
                        showNotification(err.message || 'Error al subir imagen', 'error');
                    }
                });

                node.querySelector('.pm-delete').addEventListener('click', async () => {
                    if (!p.id) {
                        // remove local-only (legacy) item
                        pmProducts = pmProducts.filter(x => x !== p);
                        renderProductsList();
                        return;
                    }
                    if (!confirm('Eliminar producto?')) return;
                    const res = await apiRequest('products.delete', { id: p.id }, 'POST');
                    if (res.success) {
                        showNotification('Producto eliminado');
                        fetchProducts();
                    } else {
                        showNotification(res.message || 'No se pudo eliminar', 'error');
                    }
                });

                list.appendChild(node);
            });
        }

        // escape helper for HTML inserted values
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Upload an image file using FormData to the API upload endpoint
        async function uploadImage(file) {
            const fd = new FormData();
            fd.append('image', file);

            const resp = await fetch('api.php?action=products.upload', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });

            const data = await resp.json();
            if (!resp.ok || !data.success) {
                throw new Error(data.message || 'Error al subir imagen');
            }

            return data.path;
        }

        document.getElementById('pm-create-btn').addEventListener('click', async function(){
            const name = document.getElementById('pm-name').value.trim();
            const price = document.getElementById('pm-price').value;
            const image = document.getElementById('pm-image').value.trim();
            const fileInput = document.getElementById('pm-image-file');
            const file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            const desc = document.getElementById('pm-desc').value.trim();
            const categoria = document.getElementById('pm-category') ? document.getElementById('pm-category').value : '';
            const descuentoActivo = document.getElementById('pm-discount-active') ? (document.getElementById('pm-discount-active').checked ? 1 : 0) : 0;
            const precioDescuento = document.getElementById('pm-discount-price') ? document.getElementById('pm-discount-price').value : '';

            if (!name) { showNotification('Nombre requerido', 'warning'); return; }
            if (price === '' || isNaN(Number(price))) { showNotification('Precio inválido', 'warning'); return; }
            try {
                let finalImage = image;
                if (file) {
                    finalImage = await uploadImage(file);
                }

                const body = { nombre: name, precio: price, imagen: finalImage, descripcion: desc, categoria: categoria, descuento_activo: descuentoActivo, precio_descuento: precioDescuento };
                const res = await apiRequest('products.create', body, 'POST');
                if (res.success) {
                    document.getElementById('pm-name').value = '';
                    document.getElementById('pm-price').value = '';
                    document.getElementById('pm-image').value = '';
                    document.getElementById('pm-image-file').value = '';
                    document.getElementById('pm-desc').value = '';
                    if (document.getElementById('pm-category')) document.getElementById('pm-category').selectedIndex = 0;
                    if (document.getElementById('pm-discount-active')) { document.getElementById('pm-discount-active').checked = false; }
                    if (document.getElementById('pm-discount-price')) { document.getElementById('pm-discount-price').value = ''; document.getElementById('pm-discount-price').style.display = 'none'; }
                    const preview = document.getElementById('pm-image-preview');
                    if (preview) { preview.style.display = 'none'; preview.src = ''; }
                    showNotification('Producto creado');
                    fetchProducts();
                } else {
                    showNotification(res.message || 'No se pudo crear', 'error');
                }
            } catch (err) {
                showNotification(err.message || 'Error al subir imagen', 'error');
            }
        });

        // toggle discount price input visibility on create form
        const pmDiscountChk = document.getElementById('pm-discount-active');
        const pmDiscountPrice = document.getElementById('pm-discount-price');
        if (pmDiscountChk && pmDiscountPrice) {
            pmDiscountChk.addEventListener('change', function(){
                pmDiscountPrice.style.display = pmDiscountChk.checked ? 'inline-block' : 'none';
            });
            // initialize visibility
            pmDiscountPrice.style.display = pmDiscountChk.checked ? 'inline-block' : 'none';
        }

        document.getElementById('pm-clear-btn').addEventListener('click', function(){
            document.getElementById('pm-name').value = '';
            document.getElementById('pm-price').value = '';
            document.getElementById('pm-image').value = '';
            document.getElementById('pm-image-file').value = '';
            const preview = document.getElementById('pm-image-preview');
            if (preview) { preview.style.display = 'none'; preview.src = ''; }
            document.getElementById('pm-desc').value = '';
        });

        // initial load
        fetchProducts();
    })();
    </script>
</body>
</html>
