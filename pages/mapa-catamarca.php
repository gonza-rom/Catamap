<?php
include '../includes/conexion.php'; 

// Consulta todos los lugares turísticos
$sql = "SELECT nombre, lat, lng, descripcion, imagen, departamento FROM lugares_turisticos";
$result = $conexion->query($sql);

$lugares = [];
while ($row = $result->fetch_assoc()) {
    $depto = strtoupper($row['departamento']); // poner el departamento en mayúscula
    if (!isset($lugares[$depto])) {
        $lugares[$depto] = [];
    }
    $lugares[$depto][] = [
        'nombre' => $row['nombre'],
        'lat' => floatval($row['lat']),
        'lng' => floatval($row['lng']),
        'descripcion' => $row['descripcion'],
        'imagen' => $row['imagen']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Mapa interactivo Catamarca</title>
  <!-- estilos -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
  <link rel="stylesheet" href="../styles/mapa-catamaca.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  <!-- SCRIPTS: orden estrictamente importante -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
  <!-- 🔹 Navbar -->
  <div id="map"></div>
    <div id="sidebar">
      <div class="sidebar-header">
        <h3>Menú</h3>
        <span id="closeSidebar">&times;</span> <!-- Botón de cerrar -->
      </div>
      <a href="/index.html">Inicio</a>
      <a href="/pages/mapa-catamarca.html">Mapa</a>
      <a href="/pages/contacto.html">Contacto</a>
      <a href="/pages/inicio-sesion.html">Iniciar Sesión</a>
    </div>
   <footer class="footer">
    <div class="footer-container">
        <div class="footer-logo">
        <img src="../img/CATAMAP.png" alt="Catamap Logo">
        </div>
        <div class="footer-links">
        <a href="/index.html">Inicio</a>
        <a href="/pages/mapa-catamarca.html">Mapa</a>
        <a href="/pages/contacto.html">Contacto</a>
        </div>
        <div class="footer-social">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-x-twitter"></i></a>
        </div>
        <p class="footer-copy">© 2025 CATAMAP - Todos los derechos reservados</p>
    </div>
    </footer>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
  <script>
  const lugaresTuristicos = <?php echo json_encode($lugares); ?>;
  console.log(lugaresTuristicos); // comprobación en consola
  </script>
  <script src="../scripts/mapa-catamarca.js"></script>
</body>
</html>