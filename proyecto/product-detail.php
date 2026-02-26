<?php
// Product detail placeholder to match main.js links
// We'll read the product id from GET and let main.js render details from its product array
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle del Producto</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <a class="logo" href="index.php">PedidoReserve</a>
            <div id="auth-buttons"></div>
        </nav>
    </header>

    <main class="container">
        <div id="product-detail" class="product-detail"></div>
    </main>

    <script src="js/main.js"></script>
</body>
</html>
