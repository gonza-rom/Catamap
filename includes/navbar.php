<?php include 'config.php'; ?>

<!-- Navbar -->
<header class="navbar">
    <div class="navbar-container">
        <a href="<?php echo BASE_URL; ?>/index.php" class="logo">
            <img src="<?php echo BASE_URL; ?>/img/CATAMAP.png" alt="Catamap Logo">
        </a>
        <nav class="menu">
            <a href="<?php echo BASE_URL; ?>/index.php">Inicio</a>
            <a href="<?php echo BASE_URL; ?>/pages/mapa-catamarca.php">Mapa</a>
            <a href="<?php echo BASE_URL; ?>/pages/contacto.html">Contacto</a>
            <a href="<?php echo BASE_URL; ?>/pages/inicio-sesion.html">Iniciar Sesión</a>
            <a href="<?php echo BASE_URL; ?>/pages/registro-usuario.html">Registrarme</a>
        </nav>
    </div>
</header>

<style>
    /* Navbar */
.navbar {
  background-color: #3AA6A6;
  padding: 10px 20px;
  color: white;
}

.navbar-container {
  max-width: 1200px;
  margin: auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.navbar .logo img {
  height: 50px;
}

.navbar .menu a {
  margin-left: 20px;
  text-decoration: none;
  color: white;
  font-weight: bold;
  transition: color 0.3s ease;
}

.navbar .menu a:hover {
  color: #E07B39;
}

</style>