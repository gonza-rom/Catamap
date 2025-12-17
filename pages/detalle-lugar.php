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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Traemos el lugar + su categoría + su departamento
$sql = "SELECT l.*, 
               c.nombre AS categoria_nombre, 
               c.icono AS categoria_icono,
               d.nombre AS departamento_nombre
        FROM lugares_turisticos l
        LEFT JOIN categorias c ON l.id_categoria = c.id_categoria
        LEFT JOIN departamentos d ON l.id_departamento = d.id
        WHERE l.id = $id";

$result = $conexion->query($sql);

if ($result->num_rows === 0) {
    die("Lugar no encontrado.");
}

$lugar = $result->fetch_assoc();

// Verificar si está en favoritos
$esFavorito = false;
if($usuario_logueado) {
    $sql_fav = "SELECT id FROM favoritos WHERE id_usuario = ? AND id_lugar = ?";
    $stmt_fav = $conexion->prepare($sql_fav);
    
    if ($stmt_fav === false) {
        die("Error en favoritos: " . $conexion->error);
    }
    
    $stmt_fav->bind_param("ii", $usuario_logueado['id'], $id);
    $stmt_fav->execute();
    $esFavorito = $stmt_fav->get_result()->num_rows > 0;
    $stmt_fav->close();
}

// Obtener comentarios del lugar
$sql_comentarios = "SELECT c.id, c.id_lugar, c.id_usuario, c.calificacion, c.comentario, 
                           c.estado, c.fecha_creacion, c.fecha_modificacion,
                           u.nombre as usuario_nombre, u.imagen_perfil
                    FROM comentarios c
                    INNER JOIN usuarios u ON c.id_usuario = u.id
                    WHERE c.id_lugar = ? AND c.estado = 'aprobado'
                    ORDER BY c.fecha_creacion DESC";

$stmt_com = $conexion->prepare($sql_comentarios);

if ($stmt_com === false) {
    die("Error preparando consulta de comentarios: " . $conexion->error);
}

$stmt_com->bind_param("i", $id);

if (!$stmt_com->execute()) {
    die("Error ejecutando consulta de comentarios: " . $stmt_com->error);
}

$result_comentarios = $stmt_com->get_result();

// Calcular promedio de calificaciones
$sql_promedio = "SELECT AVG(c.calificacion) as promedio, COUNT(*) as total
                 FROM comentarios c
                 WHERE c.id_lugar = ? AND c.estado = 'aprobado'";

$stmt_prom = $conexion->prepare($sql_promedio);

if ($stmt_prom === false) {
    die("Error preparando consulta de promedio: " . $conexion->error);
}

$stmt_prom->bind_param("i", $id);

if (!$stmt_prom->execute()) {
    die("Error ejecutando consulta de promedio: " . $stmt_prom->error);
}

$resultado_prom = $stmt_prom->get_result()->fetch_assoc();
$promedio_calificacion = $resultado_prom['promedio'] ? round($resultado_prom['promedio'], 1) : 0;
$total_comentarios = $resultado_prom['total'];

$imagenUrl = $lugar['imagen'] ? '../uploads/'.$lugar['imagen'] : '../img/placeholder.webp';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lugar['nombre']); ?> - CataMap</title>
    
    <!-- Font Icons -->
    <link rel="stylesheet" href="../assets/vendors/themify-icons/css/themify-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/vendors/animate/animate.css">
    
    <!-- Bootstrap + Styles -->
    <link rel="stylesheet" href="../assets/css/foodhut.css">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <!-- CSS -->
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/detalle-lugar.css">
    <!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                <li class="nav-item"><a class="nav-link" href="lugares.php">Lugares</a></li>
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

    <!-- Hero Section -->
    <div class="hero-detalle" style="background-image: url('<?php echo $imagenUrl; ?>');">
        <?php if($usuario_logueado): ?>
            <button class="btn-favorito-hero <?php echo $esFavorito ? 'active' : ''; ?>" 
                    id="btnFavoritoHero"
                    onclick="toggleFavorito(<?php echo $id; ?>)">
                <i class="bi <?php echo $esFavorito ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
            </button>
        <?php endif; ?>
        
        <div class="hero-content container">
            <h1><?php echo htmlspecialchars($lugar['nombre']); ?></h1>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <i class="fa fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($lugar['departamento_nombre']); ?></span>
                </div>
                <div class="hero-meta-item">
                    <i class="fa fa-tag"></i>
                    <span><?php echo htmlspecialchars($lugar['categoria_nombre']); ?></span>
                </div>
                <?php if($promedio_calificacion > 0): ?>
                <div class="hero-meta-item">
                    <i class="fa fa-star" style="color: #ffc107;"></i>
                    <span><?php echo $promedio_calificacion; ?> (<?php echo $total_comentarios; ?> opiniones)</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detalle Section -->
    <div class="detalle-section">
        <div class="container">
            <div class="row">
                <!-- Columna izquierda - Información -->
                <div class="col-lg-8">
                    <div class="info-card">
                        <h3><i class="bi bi-info-circle"></i> Descripción</h3>
                        <p style="font-size: 1.1rem; line-height: 1.8; color: #666;">
                            <?php echo nl2br(htmlspecialchars($lugar['descripcion'])); ?>
                        </p>
                    </div>

                    <div class="info-card">
                        <h3><i class="bi bi-geo-alt"></i> Ubicación</h3>
                        <div class="info-item">
                            <i class="bi bi-pin-map"></i>
                            <div>
                                <strong>Dirección:</strong><br>
                                <?php echo htmlspecialchars($lugar['direccion']); ?>
                            </div>
                        </div>
                        <!-- Reemplaza la sección de coordenadas con esto -->
                        <div class="info-item" style="position: relative; overflow: hidden;">
                            <i class="bi bi-map"></i>
                            <div style="flex: 1;">
                                <strong>Ubicación en el mapa:</strong><br>
                                <div id="miniMapa" style="width: 100%; height: 250px; border-radius: 8px; margin-top: 10px; cursor: pointer; position: relative;" 
                                    onclick="irAlMapaPrincipal()"
                                    title="Haz clic para ver en el mapa principal">
                                    <!-- El mapa se cargará aquí -->
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: rgba(255,255,255,0.9); padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); pointer-events: none;">
                                        <i class="bi bi-cursor-fill" style="font-size: 1.5rem; color: #E07B39;"></i>
                                        <p style="margin: 5px 0 0 0; font-weight: 600; color: #333;">Click para ver en mapa completo</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Comentarios -->
                    <div class="info-card" id="comentarios-section">
                        <h3><i class="bi bi-chat-dots"></i> Opiniones (<?php echo $total_comentarios; ?>)</h3>
                        
                        <?php if($promedio_calificacion > 0): ?>
                        <div class="rating-display">
                            <span class="rating-number"><?php echo $promedio_calificacion; ?></span>
                            <div>
                                <div class="stars">
                                    <?php 
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= floor($promedio_calificacion)) {
                                            echo '<i class="fa fa-star"></i>';
                                        } elseif($i - $promedio_calificacion < 1) {
                                            echo '<i class="fa fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="rating-count">Basado en <?php echo $total_comentarios; ?> opinión<?php echo $total_comentarios != 1 ? 'es' : ''; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Formulario de comentario -->
                        <?php if($usuario_logueado): ?>
                        <div class="comment-form">
                            <h4>Deja tu opinión</h4>
                            <form id="formComentario">
                                <div class="form-group">
                                    <label>Tu calificación:</label>
                                    <div class="star-rating" id="starRating">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" id="calificacion" name="calificacion" required>
                                </div>
                                <div class="form-group">
                                    <label for="comentario">Tu opinión:</label>
                                    <textarea class="form-control" id="comentario" name="comentario" rows="4" 
                                              placeholder="Comparte tu experiencia en este lugar..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fa fa-paper-plane"></i> Publicar opinión
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <a href="../index.php">Inicia sesión</a> para dejar tu opinión sobre este lugar
                        </div>
                        <?php endif; ?>

                        <!-- Lista de comentarios -->
                                                <!-- Lista de comentarios -->
                        <div id="comentarios-lista">
                            <?php if($result_comentarios->num_rows > 0): ?>
                                <?php while($comentario = $result_comentarios->fetch_assoc()): ?>
                                    <div class="comment-item">
                                        <div class="comment-header">
                                            <!-- Avatar clickeable -->
                                            <a href="perfil-publico.php?user=<?php echo $comentario['id_usuario']; ?>" 
                                               class="comment-avatar-link" 
                                               title="Ver perfil de <?php echo htmlspecialchars($comentario['usuario_nombre']); ?>">
                                                <img src="<?php echo $comentario['imagen_perfil'] ? '../uploads/'.$comentario['imagen_perfil'] : 'https://ui-avatars.com/api/?name='.urlencode($comentario['usuario_nombre']).'&size=50&background=667eea&color=fff'; ?>" 
                                                     class="comment-avatar" 
                                                     alt="Avatar">
                                            </a>
                                            <div class="comment-user">
                                                <!-- Nombre clickeable -->
                                                <h5>
                                                    <a href="perfil-publico.php?user=<?php echo $comentario['id_usuario']; ?>" 
                                                       class="comment-user-link"
                                                       title="Ver perfil de <?php echo htmlspecialchars($comentario['usuario_nombre']); ?>">
                                                        <?php echo htmlspecialchars($comentario['usuario_nombre']); ?>
                                                    </a>
                                                </h5>
                                                <div class="comment-date">
                                                    <?php 
                                                    $fecha = new DateTime($comentario['fecha_creacion']);
                                                    echo $fecha->format('d/m/Y H:i'); 
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="comment-stars">
                                                <?php 
                                                for($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $comentario['calificacion'] ? '<i class="fa fa-star"></i>' : '<i class="far fa-star"></i>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-muted py-4">
                                    <i class="bi bi-chat" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                                    Aún no hay opiniones. ¡Sé el primero en opinar!
                                </p>
                            <?php endif; ?>
                        </div>
                    </div> 
</div> 
                <!-- Columna derecha - Acciones -->
                <div class="col-lg-4">
                    <div class="info-card sticky-top" style="top: 90px;">
                        <h3><i class="bi bi-map"></i> Acciones</h3>
                        <div class="action-buttons">
                            <button class="btn-action btn-primary-custom" onclick="window.location.href='mapa-catamarca.php?lat=<?php echo $lugar['lat']; ?>&lng=<?php echo $lugar['lng']; ?>&id=<?php echo $id; ?>'">
                                <i class="fa fa-map-marked-alt"></i> Ver en mapa
                            </button>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-action btn-outline-custom" onclick="window.location.href='lugares.php'">
                                <i class="fa fa-arrow-left"></i> Volver a lugares
                            </button>
                        </div>
                        
                        <?php if($usuario_logueado): ?>
                        <div class="action-buttons">
                            <button class="btn-action <?php echo $esFavorito ? 'btn-primary-custom' : 'btn-outline-custom'; ?>" 
                                    id="btnFavoritoCard"
                                    onclick="toggleFavorito(<?php echo $id; ?>)">
                                <i class="bi <?php echo $esFavorito ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                <?php echo $esFavorito ? 'En favoritos' : 'Guardar en favoritos'; ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="container-fluid bg-dark text-white-50 py-5 px-sm-3 px-lg-5">
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>                        
    <script>
        const idLugar = <?php echo $id; ?>;
        const usuarioLogueado = <?php echo $usuario_logueado ? 'true' : 'false'; ?>;
        let calificacionSeleccionada = 0;
        
        // Sistema de estrellas
        $(document).ready(function() {
            $('.star-rating i').click(function() {
                calificacionSeleccionada = $(this).data('rating');
                $('#calificacion').val(calificacionSeleccionada);
                
                $('.star-rating i').each(function() {
                    const rating = $(this).data('rating');
                    if(rating <= calificacionSeleccionada) {
                        $(this).removeClass('far').addClass('fas active');
                    } else {
                        $(this).removeClass('fas active').addClass('far');
                    }
                });
            });
            
            // Hover effect
            $('.star-rating i').hover(function() {
                const rating = $(this).data('rating');
                $('.star-rating i').each(function() {
                    if($(this).data('rating') <= rating) {
                        $(this).removeClass('far').addClass('fas');
                    } else {
                        $(this).removeClass('fas').addClass('far');
                    }
                });
            }, function() {
                $('.star-rating i').each(function() {
                    const rating = $(this).data('rating');
                    if(rating <= calificacionSeleccionada) {
                        $(this).removeClass('far').addClass('fas active');
                    } else {
                        $(this).removeClass('fas active').addClass('far');
                    }
                });
            });
        });
        
        // Enviar comentario
        $('#formComentario').submit(async function(e) {
            e.preventDefault();
            
            if(calificacionSeleccionada === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Calificación requerida',
                    text: 'Por favor, selecciona una calificación con las estrellas',
                    confirmButtonColor: '#E07B39'
                });
                return;
            }
            
            const comentario = $('#comentario').val().trim();
            if(!comentario) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Comentario vacío',
                    text: 'Por favor, escribe tu opinión',
                    confirmButtonColor: '#E07B39'
                });
                return;
            }
            
            if(comentario.length < 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Comentario muy corto',
                    text: 'El comentario debe tener al menos 10 caracteres',
                    confirmButtonColor: '#E07B39'
                });
                return;
            }
            
            // Mostrar loading
            Swal.fire({
                title: 'Enviando opinión...',
                html: 'Por favor espera un momento',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                const response = await fetch('../api/comentarios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_lugar: idLugar,
                        calificacion: calificacionSeleccionada,
                        comentario: comentario
                    })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    if(data.pending) {
                        // Comentario pendiente de aprobación
                        await Swal.fire({
                            icon: 'info',
                            title: '¡Comentario Enviado!',
                            html: '<p>Tu comentario ha sido enviado correctamente.</p>' +
                                '<p><strong>Está pendiente de aprobación</strong> por un administrador.</p>' +
                                '<p class="text-muted" style="font-size: 0.9em; margin-top: 10px;">Será visible una vez que sea revisado y aprobado.</p>',
                            confirmButtonColor: '#E07B39',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        // Comentario aprobado automáticamente (por si cambias la lógica después)
                        await Swal.fire({
                            icon: 'success',
                            title: '¡Opinión publicada!',
                            text: 'Gracias por compartir tu experiencia',
                            confirmButtonColor: '#E07B39',
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                    
                    // Limpiar formulario
                    $('#comentario').val('');
                    calificacionSeleccionada = 0;
                    $('.star').removeClass('active');
                    
                    // Recargar la página después de 1 segundo
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo publicar tu opinión',
                        confirmButtonColor: '#E07B39'
                    });
                }
            } catch(error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
                    confirmButtonColor: '#E07B39'
                });
            }
        });
                
                // Toggle favorito
        async function toggleFavorito(idLugar) {
            if(!usuarioLogueado) {
                Swal.fire({
                    icon: 'info',
                    title: 'Inicia sesión',
                    text: 'Debes iniciar sesión para agregar lugares a favoritos',
                    confirmButtonColor: '#E07B39',
                    confirmButtonText: 'Ir a login'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../index.php';
                    }
                });
                return;
            }
            
            const btnHero = $('#btnFavoritoHero');
            const btnCard = $('#btnFavoritoCard');
            const esFavorito = btnHero.hasClass('active');
            
            // Mostrar loading
            Swal.fire({
                title: esFavorito ? 'Quitando de favoritos...' : 'Agregando a favoritos...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                const response = await fetch('../api/favoritos.php', {
                    method: esFavorito ? 'DELETE' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id_lugar: idLugar })
                });
                
                const data = await response.json();
                
                if(data.success) {
                    if(esFavorito) {
                        // Quitar de favoritos
                        btnHero.removeClass('active');
                        btnHero.find('i').removeClass('bi-heart-fill').addClass('bi-heart');
                        btnCard.removeClass('btn-primary-custom').addClass('btn-outline-custom');
                        btnCard.html('<i class="bi bi-heart"></i> Guardar en favoritos');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'Se quitó de tus favoritos',
                            confirmButtonColor: '#E07B39',
                            timer: 1500,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    } else {
                        // Agregar a favoritos
                        btnHero.addClass('active');
                        btnHero.find('i').removeClass('bi-heart').addClass('bi-heart-fill');
                        btnCard.removeClass('btn-outline-custom').addClass('btn-primary-custom');
                        btnCard.html('<i class="bi bi-heart-fill"></i> En favoritos');
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Agregado!',
                            text: 'Se guardó en tus favoritos',
                            confirmButtonColor: '#E07B39',
                            timer: 1500,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo procesar la solicitud',
                        confirmButtonColor: '#E07B39'
                    });
                }
            } catch(error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#E07B39'
                });
            }
        }
                
                // Logout
        $('#btnLogout').click(async function(e) {
            e.preventDefault();
            
            const result = await Swal.fire({
                icon: 'question',
                title: '¿Cerrar sesión?',
                text: '¿Estás seguro que deseas salir?',
                showCancelButton: true,
                confirmButtonColor: '#E07B39',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar'
            });
            
            if(result.isConfirmed) {
                Swal.fire({
                    title: 'Cerrando sesión...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                try {
                    await fetch('../api/logout.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    window.location.href = '../index.php';
                } catch(error) {
                    window.location.href = '../index.php';
                }
            }
        });

        const lugarLat = <?php echo $lugar['lat']; ?>;
        const lugarLng = <?php echo $lugar['lng']; ?>;
        const lugarNombre = "<?php echo addslashes($lugar['nombre']); ?>";

        document.addEventListener('DOMContentLoaded', function() {
            // Crear el mini mapa
            const miniMap = L.map('miniMapa', {
                center: [lugarLat, lugarLng],
                zoom: 14,
                zoomControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                touchZoom: false,
                boxZoom: false,
                keyboard: false
            });

            // Agregar capa base
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(miniMap);

            // Crear un ícono personalizado
            const customIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            // Agregar marcador
            const marker = L.marker([lugarLat, lugarLng], {
                icon: customIcon
            }).addTo(miniMap);

            // Agregar popup al marcador
            marker.bindPopup(`
                <div style="text-align: center; padding: 5px;">
                    <strong>${lugarNombre}</strong><br>
                    <small style="color: #666;">Click en el mapa para más opciones</small>
                </div>
            `).openPopup();

            // Agregar círculo de área
            L.circle([lugarLat, lugarLng], {
                color: '#E07B39',
                fillColor: '#E07B39',
                fillOpacity: 0.2,
                radius: 200
            }).addTo(miniMap);
        });

        // Función para redirigir al mapa principal
        function irAlMapaPrincipal() {
            window.location.href = `mapa-catamarca.php?lat=${lugarLat}&lng=${lugarLng}&id=<?php echo $id; ?>`;
        }
    </script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>