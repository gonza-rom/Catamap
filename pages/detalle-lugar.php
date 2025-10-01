<?php
include '../includes/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT * FROM lugares_turisticos WHERE id = $id";
$result = $conexion->query($sql);

if ($result->num_rows === 0) {
    die("Lugar no encontrado.");
}

$lugar = $result->fetch_assoc();


?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo $lugar['nombre']; ?> - Catamap</title>
  <link rel="stylesheet" href="../styles/detalle-lugar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
  <?php include '../includes/navbar.php'; ?>

  <main class="detalle-container">
      <div class="detalle-img">
          <img src="<?php echo $lugar['imagen']; ?>" alt="<?php echo $lugar['nombre']; ?>">
      </div>
      <div class="detalle-info">
          <h1><?php echo $lugar['nombre']; ?></h1>
          <p><strong>Descripción:</strong> <?php echo $lugar['descripcion']; ?></p>
          <p><strong>Departamento:</strong> <?php echo $lugar['departamento']; ?></p>
          <p><strong>Dirección:</strong> <?php echo $lugar['direccion']; ?></p>
          <p><strong>Categoría:</strong> <?php echo ucfirst($lugar['categoria']); ?></p>
          <p><strong>Coordenadas:</strong> <?php echo $lugar['lat']; ?>, <?php echo $lugar['lng']; ?></p>
          <p><a href="<?php echo BASE_URL; ?>/pages/mapa-catamarca.php" class="btn-volver">Volver al mapa</a></p>
      </div>
  </main>

  <?php include '../includes/footer.php'; ?>
</body>
</html>
