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

// Obtener categorías para el filtro
$sql_categorias = "SELECT id_categoria, nombre FROM categorias ORDER BY nombre";
$result_categorias = $conexion->query($sql_categorias);

// Obtener departamentos para el filtro
$sql_departamentos = "SELECT DISTINCT nombre FROM departamentos ORDER BY nombre";
$result_departamentos = $conexion->query($sql_departamentos);

// Obtener favoritos si hay usuario logueado
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

// Consultar todos los lugares
$sql = "SELECT l.id, l.nombre, l.descripcion, l.direccion, l.lat, l.lng, 
               l.imagen, l.id_categoria, l.id_departamento,
               c.nombre AS categoria_nombre, c.icono AS categoria_icono,
               d.nombre AS departamento_nombre
        FROM lugares_turisticos l
        LEFT JOIN categorias c ON l.id_categoria = c.id_categoria
        LEFT JOIN departamentos d ON l.id_departamento = d.id
        ORDER BY l.nombre";
$result_lugares = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lugares Turísticos - CataMap</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Font Icons -->
    <link rel="stylesheet" href="../assets/vendors/themify-icons/css/themify-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendors/animate/animate.css">
    
    <!-- Bootstrap + Styles -->
    <link rel="stylesheet" href="../assets/css/foodhut.css">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    
    <style>
        .package-item {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .package-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .package-item img {
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .package-item:hover img {
            transform: scale(1.1);
        }
        
        .favorito-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .favorito-badge:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }
        
        .favorito-badge i {
            font-size: 20px;
            color: #ddd;
            transition: color 0.3s;
        }
        
        .favorito-badge.active i {
            color: #ffc107;
        }
        
        .badge-categoria {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .no-resultados {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-resultados i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .user-menu {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Navbar -->
    <nav class="custom-navbar navbar navbar-expand-lg navbar-dark fixed-top" data-spy="affix" data-offset-top="10">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="../index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="mapa-catamarca.php">Mapa</a></li>
                <li class="nav-item"><a class="nav-link active" href="lugares.php">Lugares</a></li>
            </ul>
            <a class="navbar-brand m-auto" href="../index.php">
                <img src="../assets/imgs/CATAMAP.png" class="brand-img" alt="">
                <span class="brand-txt">CATAMAP</span>
            </a>
            <ul class="navbar-nav">
                <?php if($usuario_logueado): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($usuario_logueado['nombre']); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a>
                            <a class="dropdown-item" href="#" id="btnFavoritosNav"><i class="bi bi-star"></i> Mis Favoritos</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="btnLogout"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-primary ml-xl-4" href="../index.php">Iniciar Sesión</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Header -->
    <div class="container-fluid page-header">
        <div class="container">
            <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 400px">
                <h3 class="display-4 text-white text-uppercase">Lugares Turísticos de Catamarca</h3>
                <div class="d-inline-flex text-white">
                    <p class="m-0 text-uppercase"><a class="text-white" href="../index.php">Inicio</a></p>
                    <i class="fa fa-angle-double-right pt-1 px-3"></i>
                    <p class="m-0 text-uppercase">Lugares</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="container-fluid booking mt-5">
        <div class="container pb-3">
            <div class="bg-light shadow p-4 rounded">
                <div class="row align-items-center">
                    <!-- Filtro Categoría -->
                    <div class="col-md-3 mb-3 mb-md-0">
                        <select id="filtro-categoria" class="custom-select px-4" style="height: 47px;">
                            <option value="">Todas las categorías</option>
                            <?php while($cat = $result_categorias->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Filtro Departamento -->
                    <div class="col-md-3 mb-3 mb-md-0">
                        <select id="filtro-departamento" class="custom-select px-4" style="height: 47px;">
                            <option value="">Todos los departamentos</option>
                            <?php while($depto = $result_departamentos->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($depto['nombre']); ?>">
                                    <?php echo htmlspecialchars($depto['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Filtro por nombre -->
                    <div class="col-md-4 mb-3 mb-md-0">
                        <input type="text" id="filtro-nombre" class="form-control px-4" 
                               placeholder="Buscar por nombre..." style="height: 47px;">
                    </div>

                    <!-- Botones -->
                    <div class="col-md-2 text-center">
                        <button id="btn-buscar" class="btn btn-primary btn-block" style="height: 47px;">
                            <i class="fa fa-search"></i> Buscar
                        </button>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col text-center">
                        <button id="btn-limpiar" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-times"></i> Limpiar filtros
                        </button>
                        <span id="contador-resultados" class="ml-3 text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lugares -->
    <div class="container-fluid py-5">
        <div class="container pt-5 pb-3">
            <div id="lugares-container" class="row">
                <?php 
                $count = 0;
                while ($lugar = $result_lugares->fetch_assoc()): 
                    $count++;
                    $esFavorito = in_array($lugar['id'], $favoritos);
                    $imagenUrl = $lugar['imagen'] ? '../uploads/'.$lugar['imagen'] : '../img/placeholder.jpg';
                ?>
                    <div class="col-lg-4 col-md-6 mb-4 lugar-item" 
                         data-categoria="<?php echo $lugar['id_categoria']; ?>"
                         data-departamento="<?php echo htmlspecialchars($lugar['departamento_nombre']); ?>"
                         data-nombre="<?php echo htmlspecialchars(strtolower($lugar['nombre'])); ?>">
                        <div class="package-item bg-white mb-2 position-relative">
                            <?php if($usuario_logueado): ?>
                                <button class="favorito-badge <?php echo $esFavorito ? 'active' : ''; ?>" 
                                        data-lugar-id="<?php echo $lugar['id']; ?>"
                                        onclick="toggleFavorito(<?php echo $lugar['id']; ?>, this)">
                                    <i class="bi <?php echo $esFavorito ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                </button>
                            <?php endif; ?>
                            
                            <span class="badge-categoria">
                                <?php echo htmlspecialchars($lugar['categoria_nombre']); ?>
                            </span>
                            
                            <img class="img-fluid" src="<?php echo $imagenUrl; ?>" alt="<?php echo htmlspecialchars($lugar['nombre']); ?>" onerror="this.src='../img/placeholder.jpg'">
                            
                            <div class="p-4">
                                <div class="d-flex justify-content-between mb-3">
                                    <small class="m-0">
                                        <i class="fa fa-map-marker-alt text-primary mr-2"></i>
                                        <?php echo htmlspecialchars($lugar['departamento_nombre']); ?>
                                    </small>
                                    <small class="m-0">
                                        <i class="bi bi-tag text-primary mr-2"></i>
                                        <?php echo htmlspecialchars($lugar['categoria_nombre']); ?>
                                    </small>
                                </div>
                                
                                <h5 class="text-decoration-none mb-3">
                                    <?php echo htmlspecialchars($lugar['nombre']); ?>
                                </h5>
                                
                                <p class="text-muted" style="height: 60px; overflow: hidden;">
                                    <?php echo htmlspecialchars(substr($lugar['descripcion'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <div class="border-top mt-4 pt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="detalle-lugar.php?id=<?php echo $lugar['id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fa fa-info-circle"></i> Ver detalles
                                        </a>
                                        <a href="mapa-catamarca.php?lat=<?php echo $lugar['lat']; ?>&lng=<?php echo $lugar['lng']; ?>&id=<?php echo $lugar['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fa fa-map-marked-alt"></i> Ver en mapa
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Mensaje sin resultados -->
            <div id="no-resultados" class="no-resultados" style="display: none;">
                <i class="bi bi-search"></i>
                <h3>No se encontraron resultados</h3>
                <p>Intenta ajustar tus filtros de búsqueda</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container-fluid bg-dark text-white-50 py-5 px-sm-3 px-lg-5" style="margin-top: 90px;">
            <div class="row pt-5">
                <div class="col-lg-3 col-md-6 mb-5">
                    <a href="" class="navbar-brand">
                        <h1 class="text-primary"><span class="text-white">CATA</span>MAP</h1>
                    </a>
                    <p>Descubre los mejores lugares turísticos de Catamarca</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-5">
                    <h5 class="text-white text-uppercase mb-4" style="letter-spacing: 5px;">Enlaces</h5>
                    <div class="d-flex flex-column justify-content-start">
                        <a class="text-white-50 mb-2" href="../index.php"><i class="fa fa-angle-right mr-2"></i>Inicio</a>
                        <a class="text-white-50 mb-2" href="mapa-catamarca.php"><i class="fa fa-angle-right mr-2"></i>Mapa</a>
                        <a class="text-white-50 mb-2" href="lugares.php"><i class="fa fa-angle-right mr-2"></i>Lugares</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>                       
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/easing/easing.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="../lib/tempusdominus/js/moment.min.js"></script>
    <script src="../lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="../lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- bootstrap affix -->
    <script src="../assets/vendors/bootstrap/bootstrap.affix.js"></script>
        <!-- wow.js -->
    <script src="../.assets/vendors/wow/wow.js"></script>
    
    <!-- FoodHut js -->
    <script src="../assets/js/foodhut.js"></script>

    <!-- Template Javascript -->
    <script src="../js/main.js"></script>

    <script>
        // Variables globales
        const usuarioLogueado = <?php echo $usuario_logueado ? json_encode($usuario_logueado) : 'null'; ?>;
        let favoritosUsuario = <?php echo json_encode($favoritos); ?>;
        
        // Función de filtrado
        function filtrarLugares() {
            const categoria = $('#filtro-categoria').val();
            const departamento = $('#filtro-departamento').val().toLowerCase();
            const nombre = $('#filtro-nombre').val().toLowerCase();
            
            let visibles = 0;
            
            $('.lugar-item').each(function() {
                const item = $(this);
                const itemCategoria = item.data('categoria').toString();
                const itemDepartamento = item.data('departamento').toLowerCase();
                const itemNombre = item.data('nombre');
                
                let mostrar = true;
                
                if (categoria && itemCategoria !== categoria) {
                    mostrar = false;
                }
                
                if (departamento && itemDepartamento !== departamento) {
                    mostrar = false;
                }
                
                if (nombre && !itemNombre.includes(nombre)) {
                    mostrar = false;
                }
                
                if (mostrar) {
                    item.fadeIn();
                    visibles++;
                } else {
                    item.fadeOut();
                }
            });
            
            // Mostrar/ocultar mensaje de sin resultados
            if (visibles === 0) {
                $('#no-resultados').fadeIn();
            } else {
                $('#no-resultados').fadeOut();
            }
            
            // Actualizar contador
            $('#contador-resultados').text(`Mostrando ${visibles} lugar${visibles !== 1 ? 'es' : ''}`);
        }
        
        // Event listeners para filtros
        $('#btn-buscar').click(filtrarLugares);
        
        $('#filtro-categoria, #filtro-departamento').change(filtrarLugares);
        
        $('#filtro-nombre').on('keyup', function(e) {
            if (e.key === 'Enter') {
                filtrarLugares();
            }
        });
        
        $('#btn-limpiar').click(function() {
            $('#filtro-categoria').val('');
            $('#filtro-departamento').val('');
            $('#filtro-nombre').val('');
            filtrarLugares();
        });
        
        // Toggle favorito
        async function toggleFavorito(idLugar, btn) {
            if (!usuarioLogueado) {
                alert('Debes iniciar sesión para agregar favoritos');
                window.location.href = '../index.php';
                return;
            }
            
            const $btn = $(btn);
            const esFavorito = $btn.hasClass('active');
            
            $('#loadingOverlay').addClass('active');
            
            try {
                const response = await fetch('../api/favoritos.php', {
                    method: esFavorito ? 'DELETE' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id_lugar: idLugar })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (esFavorito) {
                        $btn.removeClass('active');
                        $btn.find('i').removeClass('bi-star-fill').addClass('bi-star');
                        const index = favoritosUsuario.indexOf(idLugar);
                        if (index > -1) favoritosUsuario.splice(index, 1);
                    } else {
                        $btn.addClass('active');
                        $btn.find('i').removeClass('bi-star').addClass('bi-star-fill');
                        favoritosUsuario.push(idLugar);
                    }
                } else {
                    alert(data.message || 'Error al procesar la solicitud');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            } finally {
                $('#loadingOverlay').removeClass('active');
            }
        }
        
        // Logout
        $('#btnLogout').click(async function(e) {
            e.preventDefault();
            if(!confirm('¿Cerrar sesión?')) return;
            
            try {
                const response = await fetch('../api/logout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                window.location.href = '../index.php';
            } catch (error) {
                window.location.href = '../index.php';
            }
        });
        
        // Inicializar contador
        $(document).ready(function() {
            filtrarLugares();
        });
    </script>
</body>
</html>