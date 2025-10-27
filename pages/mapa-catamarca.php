<?php
session_start();
include '../includes/conexion.php'; 
include '../config/database.php';
include '../classes/Usuario.php';

$conexion->set_charset("utf8mb4");

// Verificar si hay sesión activa
$usuario_logueado = null;
if(isset($_SESSION['user_id']) && isset($_SESSION['token'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        $usuario = new Usuario($db);
        if($usuario->verificarToken($_SESSION['token'])) {
            $usuario_logueado = [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'tipo_usuario' => $usuario->tipo_usuario
            ];
        }
    }
}

// Consulta todos los lugares turísticos
$sql = "SELECT l.id, l.nombre, l.descripcion, l.direccion, l.lat, l.lng, 
               l.imagen, l.id_categoria, l.id_departamento,
               c.nombre AS categoria_nombre, c.icono AS categoria_icono,
               d.nombre AS departamento_nombre
        FROM lugares_turisticos l
        LEFT JOIN categorias c ON l.id_categoria = c.id_categoria
        LEFT JOIN departamentos d ON l.id_departamento = d.id";
$result = $conexion->query($sql);

$lugares = [];  
while ($row = $result->fetch_assoc()) {
    $depto = strtoupper($row['departamento_nombre']);
    
    if (!isset($lugares[$depto])) {
        $lugares[$depto] = [];
    }

    $lugares[$depto][] = [
        'id' => intval($row['id']),
        'nombre' => $row['nombre'],
        'descripcion' => $row['descripcion'],
        'direccion' => $row['direccion'],
        'lat' => floatval($row['lat']),
        'lng' => floatval($row['lng']),
        'imagen' => $row['imagen'],
        'categoria' => $row['categoria_nombre'],
        'icono' => $row['categoria_icono'],
        'departamento' => $row['id_departamento']
    ];
}

// Si hay usuario logueado, obtener sus favoritos
$favoritos = [];
if($usuario_logueado) {
    $sql_favoritos = "SELECT id_lugar FROM favoritos WHERE id_usuario = ?";
    $stmt = $conexion->prepare($sql_favoritos);
    $stmt->bind_param("i", $usuario_logueado['id']);
    $stmt->execute();
    $result_fav = $stmt->get_result();
    while($row = $result_fav->fetch_assoc()) {
        $favoritos[] = intval($row['id_lugar']);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mapa interactivo Catamarca</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
  <link rel="stylesheet" href="../styles/mapa-catamaca.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Estilos para el menú de usuario */
    .user-menu {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      background: white;
      border-radius: 50px;
      padding: 8px 20px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-menu .user-name {
      font-weight: 600;
      color: #333;
    }

    .user-menu .dropdown-toggle::after {
      margin-left: 8px;
    }

    .favorito-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background: white;
      border: none;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
      z-index: 10;
    }

    .favorito-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }

    .favorito-btn i {
      font-size: 18px;
      color: #ffc107;
    }

    .favorito-btn.active i {
      color: #ffc107;
    }

    .favorito-btn:not(.active) i {
      color: #ddd;
    }

    /* Panel de favoritos */
    .favoritos-panel {
      position: fixed;
      right: -400px;
      top: 0;
      width: 400px;
      height: 100vh;
      background: white;
      box-shadow: -5px 0 15px rgba(0,0,0,0.2);
      z-index: 999;
      transition: right 0.3s ease;
      overflow-y: auto;
    }

    /* Mover el panel a la derecha */
    .favoritos-panel.open {
      left: 0;
    }

    .favoritos-panel-header {
      padding: 20px;
      background: linear-gradient(135deg, #e07b38 0%, #df5900ff 100%);
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .favoritos-panel-header h3 {
      margin: 0;
      font-size: 1.5rem;
    }

    .close-favoritos {
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
    }

    .favorito-item {
      padding: 15px;
      border-bottom: 1px solid #eee;
      display: flex;
      gap: 15px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .favorito-item:hover {
      background: #f8f9fa;
    }

    .favorito-item img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
    }

    .favorito-item-info {
      flex: 1;
    }

    .favorito-item-info h4 {
      margin: 0 0 5px 0;
      font-size: 1rem;
      color: #333;
    }

    .favorito-item-info p {
      margin: 0;
      font-size: 0.85rem;
      color: #666;
    }

    .login-required {
      padding: 40px 20px;
      text-align: center;
      color: #666;
    }

    .login-required i {
      font-size: 3rem;
      color: #ddd;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <!-- Menú de Usuario -->
  <?php if($usuario_logueado): ?>
    <div class="user-menu dropdown">
      <button class="btn btn-link dropdown-toggle user-name" type="button" id="userMenuDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($usuario_logueado['nombre']); ?>
      </button>
      <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userMenuDropdown">
        <a class="dropdown-item" href="../index.php"><i class="bi bi-house"></i> Inicio</a>
        <a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a>
        <?php if($usuario_logueado['tipo_usuario'] === 'emprendedor'): ?>
          <a class="dropdown-item" href="mis-emprendimientos.php"><i class="bi bi-briefcase"></i> Mis Emprendimientos</a>
        <?php endif; ?>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="#" id="btnLogoutMapa"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
      </div>
    </div>
  <?php else: ?>
    <div class="user-menu">
      <a href="../index.php" class="btn btn-primary btn-sm">
        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
      </a>
    </div>
  <?php endif; ?>

  <!-- Panel de Favoritos -->
  <div class="favoritos-panel" id="favoritosPanel">
    <div class="favoritos-panel-header">
      <h3><i class="bi bi-star-fill"></i> Mis Favoritos</h3>
      <button class="close-favoritos" id="closeFavoritos">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div id="favoritosContent">
      <?php if($usuario_logueado): ?>
        <div id="favoritosList"></div>
      <?php else: ?>
        <div class="login-required">
          <i class="bi bi-heart"></i>
          <h4>Inicia sesión para guardar favoritos</h4>
          <p class="mb-3">Guarda tus lugares favoritos para acceder a ellos rápidamente</p>
          <a href="../index.php" class="btn btn-primary">Iniciar Sesión</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div id="map"></div>

  <!-- Sidebar moderna -->
  <div class="sidebar" id="sidebar">
    <ul class="nav flex-column text-center">
      <li class="nav-item" title="Inicio">
        <a href="../index.php" class="nav-link">
          <i class="bi bi-house-door-fill"></i>
          <span class="nav-text">Volver al Inicio</span>
        </a>
      </li>

      <li class="nav-item" title="Buscar" id="btnBuscar">
        <a href="#" class="nav-link">
          <i class="bi bi-search"></i>
          <span class="nav-text">Buscar</span>
        </a>
      </li>

      <li class="nav-item" title="Categorías" id="btnCategorias">
        <a href="#" class="nav-link">
          <i class="bi bi-grid-1x2-fill"></i>
          <span class="nav-text">Categorías</span>
        </a>
      </li>

      <li class="nav-item" title="Favoritos" id="btnFavoritos">
        <a href="#" class="nav-link">
          <i class="bi bi-star-fill"></i>
          <span class="nav-text">Mis Favoritos</span>
        </a>
      </li>

      <li class="nav-item" title="Listado">
        <a href="lugares.php" class="nav-link">
          <i class="bi bi-list-ul"></i>
          <span class="nav-text">Listado de lugares</span>
        </a>
      </li>

      <li class="nav-item mt-auto" title="Cuenta">
        <a href="<?php echo $usuario_logueado ? 'perfil.php' : '../index.php'; ?>" class="nav-link">
          <i class="bi bi-person-circle"></i>
          <span class="nav-text">Mi Cuenta</span>
        </a>
      </li>
    </ul>
  </div>

<!-- Al final del body, antes de cerrar </body> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

<!-- Variables globales primero -->
<script>
    const lugaresTuristicos = <?php echo json_encode($lugares, JSON_UNESCAPED_UNICODE); ?>;
    const usuarioLogueado = <?php echo $usuario_logueado ? json_encode($usuario_logueado) : 'null'; ?>;
    let favoritosUsuario = <?php echo json_encode($favoritos); ?>;
</script>

<!-- Luego los scripts de la aplicación -->
<script src="../assets/js/auth.js"></script>
<script src="../js/favoritos.js"></script>
<script src="../js/mapa-catamarca.js"></script>
<script src="../js/sidebar-categorias.js"></script>
<script src="../js/buscar-lugares.js"></script>
<script src="../js/spinner.js"></script>

<!-- Script de logout al final -->
<script>
    $(document).ready(function() {
      $('#btnLogoutMapa').click(async function(e) {
        e.preventDefault();
        
        if(!confirm('¿Cerrar sesión?')) return;
        
        try {
          await Auth.logout();
          window.location.href = '../index.php';
        } catch (error) {
          console.error('Error:', error);
          window.location.href = '../index.php';
        }
      });

      // Panel de favoritos
      $('#btnFavoritos').click(function(e) {
        e.preventDefault();
        if(usuarioLogueado) {
          $('#favoritosPanel').toggleClass('open');
        } else {
          alert('Debes iniciar sesión para ver tus favoritos');
          window.location.href = '../index.php';
        }
      });

      $('#closeFavoritos').click(function() {
        $('#favoritosPanel').removeClass('open');
      });
    });
</script>
</body>
</html>