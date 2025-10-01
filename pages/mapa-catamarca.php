<?php
include '../includes/conexion.php'; 
include '../includes/config.php';

$conexion->set_charset("utf8mb4");

// Consulta todos los lugares turísticos
$sql = "SELECT l.*, c.nombre AS categoria_nombre, c.icono AS categoria_icono
        FROM lugares_turisticos l
        LEFT JOIN categorias c ON l.id_categoria = c.id_categoria";
$result = $conexion->query($sql);

$lugares = [];
while ($row = $result->fetch_assoc()) {
    $depto = strtoupper($row['departamento']); // poner el departamento en mayúscula
    if (!isset($lugares[$depto])) {
        $lugares[$depto] = [];
    }
    $lugares[$depto][] = [
    'id' => $row['id'],
    'nombre' => $row['nombre'],
    'lat' => floatval($row['lat']),
    'lng' => floatval($row['lng']),
    'descripcion' => $row['descripcion'],
    'imagen' => $row['imagen'],
    'categoria' => $row['categoria_nombre'],   // ✅ nombre de la categoría
    'icono' => $row['categoria_icono']         // ✅ ícono de la categoría
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
      <a href="<?php echo BASE_URL; ?>/index.php">Inicio</a>
            <a href="<?php echo BASE_URL; ?>/pages/mapa-catamarca.php">Mapa</a>
            <a href="<?php echo BASE_URL; ?>/pages/contacto.html">Contacto</a>
            <a href="<?php echo BASE_URL; ?>/pages/inicio-sesion.html">Iniciar Sesión</a>
            <a href="<?php echo BASE_URL; ?>/pages/registro-usuario.html">Registrarme</a>
    </div>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
  <script>
  const lugaresTuristicos = <?php echo json_encode($lugares, JSON_UNESCAPED_UNICODE); ?>;
  console.log(lugaresTuristicos); // comprobación en consola
  </script>
  <script src="../scripts/mapa-catamarca.js"></script>
</body>
</html>