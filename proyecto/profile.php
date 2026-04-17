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

$phone = isset($_SESSION['comprador_phone']) ? (int) $_SESSION['comprador_phone'] : 0;
if ($phone <= 0) {
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

    $stmt = $pdo->prepare(
        'SELECT numero_telefono, nombres, apellidos, `correo_electrónico`, direccion_entrega
         FROM comprador
         WHERE numero_telefono = :numero_telefono
         LIMIT 1'
    );
    $stmt->execute(['numero_telefono' => $phone]);
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
                    $phone = $newPhone;
                    $successMessage = 'Perfil actualizado correctamente.';

                    $reloadStmt = $pdo->prepare(
                        'SELECT numero_telefono, nombres, apellidos, `correo_electrónico`, direccion_entrega
                         FROM comprador
                         WHERE numero_telefono = :numero_telefono
                         LIMIT 1'
                    );
                    $reloadStmt->execute(['numero_telefono' => $phone]);
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
        'name' => $fullName !== '' ? $fullName : 'Comprador',
        'email' => (string) $row['correo_electrónico'],
        'phone' => (string) $row['numero_telefono'],
        'address' => (string) $row['direccion_entrega'],
    ];
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
    </style>
</head>
<body>
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
                <p class="message-error">No fue posible cargar tu perfil.</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/main.js"></script>
</body>
</html>
