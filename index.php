<?php
// config.php
define('BASE_URL', '/catamap'); // la ruta desde la raíz de localhost
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catamap</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="hero">
    <!-- Contenedor de botones en esquina superior derecha -->
    <div class="botones">
        <button type="button"><a href="/pages/inicio-sesion.html">Iniciar Sesión</a></button>
        <button type="button"><a href="/pages/registro-usuario.html">Registrarme</a></button>
    </div>
    <div>
        <img src="./img/CATAMAP.png" alt="">
    </div>
    <div class="location">📍 Catamarca, Argentina</div>
    <h1>Es hora de tu <span>próxima aventura</span></h1>
    <p>Permítenos mostrarte el lugar ideal para ti</p>
    <button><a href="./pages/mapa-catamarca.php">BUSCAR AHORA</a></button>
</div>

<?php include 'includes/footer.php'; ?>