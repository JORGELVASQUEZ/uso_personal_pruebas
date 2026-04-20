<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=order_flow;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$products = $pdo->query('SELECT * FROM productos ORDER BY created_at DESC LIMIT 100')->fetchAll();
?>
<!DOCTYPE html>
<html>
<body>
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <div>
            <h3><?php echo htmlspecialchars($p['nombre']); ?></h3>
            <div>Categoría: <?php echo htmlspecialchars($p['categoria']); ?></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div>No hay productos.</div>
<?php endif; ?>
</body>
</html>
