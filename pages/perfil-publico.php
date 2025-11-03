<?php
// REEMPLAZAR la sección desde el inicio hasta después de obtener datos del usuario

session_start();
require_once '../includes/conexion.php';

// Obtener ID del usuario del perfil
$id_usuario_perfil = isset($_GET['user']) ? intval($_GET['user']) : 0;

if($id_usuario_perfil <= 0) {
    header('Location: ../index.php');
    exit();
}

// Obtener datos del usuario CON configuración de privacidad
$sql_usuario = "SELECT u.id, u.nombre, u.email, u.tipo_usuario, u.imagen_perfil, u.fecha_registro,
                COALESCE(cp.perfil_publico, 1) as perfil_publico, 
                COALESCE(cp.favoritos_publicos, 1) as favoritos_publicos, 
                COALESCE(cp.comentarios_publicos, 1) as comentarios_publicos, 
                COALESCE(cp.mostrar_estadisticas, 1) as mostrar_estadisticas
                FROM usuarios u
                LEFT JOIN configuracion_privacidad cp ON u.id = cp.id_usuario
                WHERE u.id = ? AND u.estado = 'activo'";
$stmt = $conexion->prepare($sql_usuario);
$stmt->bind_param("i", $id_usuario_perfil);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    echo "<script>alert('Usuario no encontrado'); window.location.href='../index.php';</script>";
    exit();
}

$usuario_perfil = $result->fetch_assoc();

// Verificar si es el dueño del perfil
$es_dueno = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id_usuario_perfil;

// Debug: Mostrar configuración (TEMPORAL - puedes eliminar después)
error_log("Perfil público: " . $usuario_perfil['perfil_publico']);
error_log("Favoritos públicos: " . $usuario_perfil['favoritos_publicos']);
error_log("Comentarios públicos: " . $usuario_perfil['comentarios_publicos']);
error_log("Mostrar estadísticas: " . $usuario_perfil['mostrar_estadisticas']);
error_log("Es dueño: " . ($es_dueno ? 'SI' : 'NO'));

// Si el perfil NO es público y NO es el dueño, mostrar mensaje y redirigir
if(!$usuario_perfil['perfil_publico'] && !$es_dueno) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Perfil Privado - CataMap</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="text-center text-white">
            <i class="bi bi-lock-fill" style="font-size: 5rem;"></i>
            <h2 class="mt-4">Perfil Privado</h2>
            <p class="lead">Este usuario ha configurado su perfil como privado</p>
            <a href="../index.php" class="btn btn-light btn-lg mt-3">
                <i class="bi bi-house"></i> Volver al Inicio
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Resto del código continúa igual...
// Estadísticas del usuario
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM favoritos WHERE id_usuario = ?) as total_favoritos,
    (SELECT COUNT(*) FROM comentarios WHERE id_usuario = ?) as total_comentarios,
    (SELECT AVG(calificacion) FROM comentarios WHERE id_usuario = ?) as promedio_calificaciones,
    (SELECT COUNT(*) FROM lugares_sugeridos WHERE id_usuario = ? AND estado = 'aprobado') as lugares_aprobados,
    (SELECT COUNT(*) FROM lugares_sugeridos WHERE id_usuario = ?) as total_sugerencias,
    (SELECT COUNT(*) FROM seguidores WHERE id_seguido = ?) as seguidores,
    (SELECT COUNT(*) FROM seguidores WHERE id_seguidor = ?) as siguiendo";
$stmt_stats = $conexion->prepare($sql_stats);
$stmt_stats->bind_param("iiiiiii", $id_usuario_perfil, $id_usuario_perfil, $id_usuario_perfil, 
                         $id_usuario_perfil, $id_usuario_perfil, $id_usuario_perfil, $id_usuario_perfil);
$stmt_stats->execute();
$estadisticas = $stmt_stats->get_result()->fetch_assoc();

// Verificar si el usuario actual sigue a este perfil
$siguiendo = false;
if(isset($_SESSION['user_id'])) {
    $sql_sigue = "SELECT id FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
    $stmt_sigue = $conexion->prepare($sql_sigue);
    $stmt_sigue->bind_param("ii", $_SESSION['user_id'], $id_usuario_perfil);
    $stmt_sigue->execute();
    $siguiendo = $stmt_sigue->get_result()->num_rows > 0;
}

// Obtener favoritos SOLO si son públicos o es el dueño
$favoritos = [];
if(($usuario_perfil['favoritos_publicos'] == 1) || $es_dueno) {
    $sql_fav = "SELECT l.*, c.nombre AS categoria_nombre, d.nombre AS departamento_nombre
                FROM favoritos f
                INNER JOIN lugares_turisticos l ON f.id_lugar = l.id
                LEFT JOIN categorias c ON l.id_categoria = c.id_categoria
                LEFT JOIN departamentos d ON l.id_departamento = d.id
                WHERE f.id_usuario = ?
                ORDER BY f.fecha_agregado DESC LIMIT 6";
    $stmt_fav = $conexion->prepare($sql_fav);
    $stmt_fav->bind_param("i", $id_usuario_perfil);
    $stmt_fav->execute();
    $result_fav = $stmt_fav->get_result();
    while($row = $result_fav->fetch_assoc()) {
        $favoritos[] = $row;
    }
}

// Obtener lugares sugeridos aprobados
$lugares_sugeridos = [];
$sql_sug = "SELECT ls.*, d.nombre AS departamento_nombre
            FROM lugares_sugeridos ls
            LEFT JOIN departamentos d ON ls.id_departamento = d.id
            WHERE ls.id_usuario = ? AND ls.estado = 'aprobado'
            ORDER BY ls.fecha_revision DESC LIMIT 6";
$stmt_sug = $conexion->prepare($sql_sug);
$stmt_sug->bind_param("i", $id_usuario_perfil);
$stmt_sug->execute();
$result_sug = $stmt_sug->get_result();
while($row = $result_sug->fetch_assoc()) {
    $lugares_sugeridos[] = $row;
}

// Obtener insignias
$sql_insignias = "SELECT i.nombre, i.descripcion, i.icono
                  FROM usuarios_insignias ui
                  INNER JOIN insignias i ON ui.id_insignia = i.id
                  WHERE ui.id_usuario = ?
                  ORDER BY ui.fecha_obtencion DESC";
$stmt_insignias = $conexion->prepare($sql_insignias);
$stmt_insignias->bind_param("i", $id_usuario_perfil);
$stmt_insignias->execute();
$result_insignias = $stmt_insignias->get_result();
$insignias = [];
while($row = $result_insignias->fetch_assoc()) {
    $insignias[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($usuario_perfil['nombre']); ?> - CataMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 30px 30px;
            position: relative;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-box {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .lugar-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .lugar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .lugar-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .lugar-content {
            padding: 15px;
        }
        .insignia-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            margin: 5px;
            font-size: 0.9rem;
        }
        .btn-seguir {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-seguir:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(56, 239, 125, 0.4);
        }
        .btn-siguiendo {
            background: #95a5a6;
        }
    </style>
</head>
<body>
    <div class="container profile-container">
        <!-- Header del Perfil -->
        <div class="profile-card">
            <div class="profile-header text-center">
                <?php 
                $avatar_url = !empty($usuario_perfil['imagen_perfil']) && file_exists('../uploads/' . $usuario_perfil['imagen_perfil'])
                    ? '../uploads/' . $usuario_perfil['imagen_perfil']
                    : 'https://ui-avatars.com/api/?name=' . urlencode($usuario_perfil['nombre']) . '&size=120&background=667eea&color=fff';
                ?>
                <img src="<?php echo $avatar_url; ?>" class="profile-avatar" alt="Avatar">
                
                <h2><?php echo htmlspecialchars($usuario_perfil['nombre']); ?></h2>
                <p class="mb-3">
                    <span class="badge badge-light px-3 py-2">
                        <i class="bi bi-shield-check"></i> <?php echo ucfirst($usuario_perfil['tipo_usuario']); ?>
                    </span>
                </p>
                
                <?php if(!$es_dueno && isset($_SESSION['user_id'])): ?>
                    <button class="btn btn-seguir <?php echo $siguiendo ? 'btn-siguiendo' : ''; ?>" 
                            id="btnSeguir" 
                            onclick="toggleSeguir(<?php echo $id_usuario_perfil; ?>)">
                        <i class="bi <?php echo $siguiendo ? 'bi-check-circle' : 'bi-plus-circle'; ?>"></i>
                        <?php echo $siguiendo ? 'Siguiendo' : 'Seguir'; ?>
                    </button>
                <?php elseif($es_dueno): ?>
                    <a href="perfil.php" class="btn btn-light">
                        <i class="bi bi-gear"></i> Editar Perfil
                    </a>
                <?php endif; ?>
                
                        <!-- Estadisticas -->
        <?php if($usuario_perfil['mostrar_estadisticas'] == 1 || $es_dueno): ?>
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $estadisticas['total_favoritos']; ?></span>
                    <span class="stat-label">Favoritos</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $estadisticas['total_comentarios']; ?></span>
                    <span class="stat-label">Opiniones</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $estadisticas['lugares_aprobados']; ?></span>
                    <span class="stat-label">Lugares Aportados</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $estadisticas['seguidores']; ?></span>
                    <span class="stat-label">Seguidores</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $estadisticas['siguiendo']; ?></span>
                    <span class="stat-label">Siguiendo</span>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle"></i> Este usuario ha ocultado sus estadísticas
            </div>
        <?php endif; ?>
        </div>    
            <div class="p-4">
                <p class="text-muted">
                    <i class="bi bi-calendar"></i> 
                    Miembro desde <?php echo date('F Y', strtotime($usuario_perfil['fecha_registro'])); ?>
                </p>
            </div>
        </div>

        <!-- Insignias -->
        <?php if(count($insignias) > 0): ?>
        <div class="profile-card">
            <div class="p-4">
                <h4><i class="bi bi-trophy"></i> Insignias y Logros</h4>
                <div class="mt-3">
                    <?php foreach($insignias as $insignia): ?>
                        <span class="insignia-badge" title="<?php echo htmlspecialchars($insignia['descripcion']); ?>">
                            <i class="bi <?php echo $insignia['icono']; ?>"></i>
                            <?php echo htmlspecialchars($insignia['nombre']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- REEMPLAZAR la sección de Lugares Favoritos -->
        <?php if(($usuario_perfil['favoritos_publicos'] == 1 || $es_dueno) && count($favoritos) > 0): ?>
        <div class="profile-card">
            <div class="p-4">
                <h4><i class="bi bi-heart-fill text-danger"></i> Lugares Favoritos</h4>
                <div class="row mt-3">
                    <?php foreach($favoritos as $lugar): ?>
                        <div class="col-md-4 mb-3">
                            <div class="lugar-card">
                                <img src="<?php echo $lugar['imagen'] ? '../uploads/'.$lugar['imagen'] : '../img/placeholder.jpg'; ?>" 
                                    class="lugar-img" 
                                    alt="<?php echo htmlspecialchars($lugar['nombre']); ?>"
                                    onerror="this.src=' ../img/placeholder.jpg'">
                                <div class="lugar-content">
                                    <h6 class="font-weight-bold"><?php echo htmlspecialchars($lugar['nombre']); ?></h6>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($lugar['departamento_nombre']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if($estadisticas['total_favoritos'] > 6): ?>
                    <div class="text-center">
                        <span class="badge badge-secondary">Y <?php echo ($estadisticas['total_favoritos'] - 6); ?> lugares más...</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif($usuario_perfil['favoritos_publicos'] == 0 && !$es_dueno): ?>
        <div class="profile-card">
            <div class="p-4 text-center">
                <i class="bi bi-lock" style="font-size: 3rem; color: #95a5a6;"></i>
                <h5 class="mt-3 text-muted">Favoritos Privados</h5>
                <p class="text-muted">Este usuario ha configurado sus favoritos como privados</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lugares Sugeridos -->
        <?php if(count($lugares_sugeridos) > 0): ?>
        <div class="profile-card">
            <div class="p-4">
                <h4><i class="bi bi-plus-circle text-success"></i> Lugares Aportados a CataMap</h4>
                <div class="row mt-3">
                    <?php foreach($lugares_sugeridos as $lugar): ?>
                        <div class="col-md-4 mb-3">
                            <div class="lugar-card">
                                <img src="<?php echo $lugar['imagen'] ? '../uploads/'.$lugar['imagen'] : '../img/placeholder.jpg'; ?>" 
                                     class="lugar-img" 
                                     alt="<?php echo htmlspecialchars($lugar['nombre']); ?>">
                                <div class="lugar-content">
                                    <h6 class="font-weight-bold"><?php echo htmlspecialchars($lugar['nombre']); ?></h6>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($lugar['departamento_nombre']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center mb-4">
            <a href="../index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Inicio
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function toggleSeguir(idUsuario) {
            try {
                const response = await fetch('../api/seguir-usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id_usuario: idUsuario })
                });

                const data = await response.json();

                if (data.success) {
                    const btn = document.getElementById('btnSeguir');
                    if (data.siguiendo) {
                        btn.classList.add('btn-siguiendo');
                        btn.innerHTML = '<i class="bi bi-check-circle"></i> Siguiendo';
                    } else {
                        btn.classList.remove('btn-siguiendo');
                        btn.innerHTML = '<i class="bi bi-plus-circle"></i> Seguir';
                    }
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    </script>
</body>
</html>