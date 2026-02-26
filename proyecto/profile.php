<?php
// Perfil placeholder para que main.js pueda redirigir sin 404
session_start();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi Perfil</title>
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
        <h1>Mi Perfil</h1>
        <p>Aquí se mostrará la información del usuario. (Placeholder)</p>
        <div id="profile-content"></div>
    </main>

    <script src="js/main.js"></script>
</body>
</html>
