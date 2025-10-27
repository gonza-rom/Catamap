<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Usuario.php';

// Verificar sesión
if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    die("Error de conexión a la base de datos");
}

$usuario = new Usuario($db);
if(!$usuario->verificarToken($_SESSION['token'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$usuario_data = [
    'id' => $usuario->id,
    'nombre' => $usuario->nombre,
    'email' => $usuario->email,
    'tipo_usuario' => $usuario->tipo_usuario,
    'telefono' => $usuario->telefono ?? '',
    'imagen_perfil' => $usuario->imagen_perfil ?? '',
    'fecha_registro' => $usuario->fecha_registro ?? ''
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - CataMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e07b38 0%, #a84300ff 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #e07b38 0%, #a84300ff 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .profile-body {
            padding: 30px;
        }
        .info-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #e07b38;
            min-width: 120px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-value {
            color: #333;
            flex: 1;
        }
        .badge-tipo {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-usuario {
            background: #17a2b8;
            color: white;
        }
        .badge-emprendedor {
            background: #28a745;
            color: white;
        }
        .badge-administrador {
            background: #dc3545;
            color: white;
        }
        .btn-action {
            margin: 5px;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="profile-card">
                    <!-- Header -->
                    <div class="profile-header">
                        <img src="<?php echo $usuario_data['imagen_perfil'] ? '../uploads/'.$usuario_data['imagen_perfil'] : 'https://ui-avatars.com/api/?name='.urlencode($usuario_data['nombre']).'&size=120&background=667eea&color=fff'; ?>" 
                             class="profile-avatar" 
                             alt="Avatar">
                        <h3 class="mb-1"><?php echo htmlspecialchars($usuario_data['nombre']); ?></h3>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($usuario_data['email']); ?></p>
                    </div>

                    <!-- Body -->
                    <div class="profile-body">
                        <h5 class="mb-4">
                            <i class="bi bi-person-badge"></i> Información de la cuenta
                        </h5>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="bi bi-person-circle"></i>
                                <span>Nombre:</span>
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario_data['nombre']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="bi bi-envelope"></i>
                                <span>Email:</span>
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario_data['email']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                <i class="bi bi-shield-check"></i>
                                <span>Tipo de cuenta:</span>
                            </div>
                            <div class="info-value">
                                <span class="badge-tipo badge-<?php echo $usuario_data['tipo_usuario']; ?>">
                                    <?php echo ucfirst($usuario_data['tipo_usuario']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if(!empty($usuario_data['telefono'])): ?>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="bi bi-telephone"></i>
                                <span>Teléfono:</span>
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario_data['telefono']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($usuario_data['fecha_registro'])): ?>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="bi bi-calendar-check"></i>
                                <span>Miembro desde:</span>
                            </div>
                            <div class="info-value">
                                <?php 
                                    $fecha = new DateTime($usuario_data['fecha_registro']);
                                    echo $fecha->format('d/m/Y'); 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <hr class="my-4">

                        <!-- Acciones -->
                        <div class="text-center">
                            <a href="../index.php" class="btn btn-secondary btn-action">
                                <i class="bi bi-house"></i> Volver al Inicio
                            </a>
                            <a href="mapa-catamarca.php" class="btn btn-primary btn-action">
                                <i class="bi bi-map"></i> Ver Mapa
                            </a>
                            <?php if($usuario_data['tipo_usuario'] === 'emprendedor'): ?>
                            <a href="mis-emprendimientos.php" class="btn btn-success btn-action">
                                <i class="bi bi-briefcase"></i> Mis Emprendimientos
                            </a>
                            <?php endif; ?>
                            <button id="btnLogout" class="btn btn-danger btn-action">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/auth.js"></script>
    <script>
        $(document).ready(function() {
            $('#btnLogout').click(async function() {
                if(!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                    return;
                }

                try {
                    await Auth.logout();
                    window.location.href = '../index.php';
                } catch (error) {
                    console.error('Error al cerrar sesión:', error);
                    // Forzar cierre de sesión
                    window.location.href = '../index.php';
                }
            });
        });
    </script>
</body>
</html>