<?php
// Verificación de admin directamente en este archivo
session_start();

if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header("Location: ../index.php");
    exit();
}

include_once '../config/database.php';
include_once '../classes/Usuario.php';
include_once '../includes/conexion.php';

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    die("Error de conexión a la base de datos");
}

$usuario = new Usuario($db);

// Verificar token y cargar datos del usuario
if(!$usuario->verificarToken($_SESSION['token'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Verificar que sea administrador
if(!$usuario->esAdmin()) {
    header("Location: ../index.php?error=acceso_denegado");
    exit();
}

// Obtener datos adicionales del usuario admin
$user_id = intval($usuario->id);
$sql_admin = "SELECT nombre, email, imagen_perfil FROM usuarios WHERE id = ?";
$stmt = $conexion->prepare($sql_admin);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();

if(!$admin_data) {
    die("Error: No se pudieron cargar los datos del administrador.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Catamap</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Admin Styles -->
    <link rel="stylesheet" href="../styles/admin.css">
    
    <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body class="admin-page">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2><i class="bi bi-shield-check"></i> Admin Panel</h2>
            </div>
            <nav class="admin-nav">
                <a href="#" class="admin-nav-item active" data-section="dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="admin-nav-item" data-section="usuarios">
                    <i class="bi bi-people"></i>
                    <span>Usuarios</span>
                </a>
                <a href="#" class="admin-nav-item" data-section="lugares">
                    <i class="bi bi-geo-alt"></i>
                    <span>Lugares Turísticos</span>
                </a>
                <a href="#" class="admin-nav-item" data-section="sugerencias">
                    <i class="bi bi-lightbulb"></i>
                    <span>Lugares Sugeridos</span>
                </a>
                <a href="#" class="admin-nav-item" data-section="comentarios">
                    <i class="bi bi-chat-dots"></i>
                    <span>Comentarios</span>
                </a>
                <a href="#" class="admin-nav-item" data-section="categorias">
                    <i class="bi bi-tags"></i>
                    <span>Categorías</span>
                </a>
                <a href="#" class="admin-nav-item" data-section="departamentos">
                    <i class="bi bi-map"></i>
                    <span>Departamentos</span>
                </a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 1rem 0;">
                <a href="../index.php" class="admin-nav-item">
                    <i class="bi bi-house"></i>
                    <span>Volver al Sitio</span>
                </a>
                <a href="../api/logout.php" class="admin-nav-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <h1>Panel de Administración</h1>
                <div class="admin-user-info">
                    <span><?php echo htmlspecialchars($admin_data['nombre']); ?></span>
                    <img src="<?php echo $admin_data['imagen_perfil'] ?? '../img/default-avatar.png'; ?>" 
                         alt="Avatar" class="admin-user-avatar">
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="dashboard-section" class="admin-section active">
                <div class="stats-grid" id="stats-container">
                    <div class="admin-loading">
                        <div class="admin-spinner"></div>
                        <p>Cargando estadísticas...</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3 class="admin-card-title">Actividad Reciente</h3>
                            </div>
                            <div id="actividad-reciente">
                                <div class="admin-loading">
                                    <div class="admin-spinner"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <h3 class="admin-card-title">Usuarios Activos</h3>
                            </div>
                            <div id="usuarios-activos">
                                <div class="admin-loading">
                                    <div class="admin-spinner"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Usuarios Section -->
            <section id="usuarios-section" class="admin-section">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Gestión de Usuarios</h3>
                        <button class="btn-admin btn-admin-primary" onclick="refreshUsuarios()">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="admin-filters">
                        <div class="admin-filter-item">
                            <input type="text" class="admin-form-control" id="filter-usuario-busqueda" 
                                   placeholder="Buscar por nombre o email...">
                        </div>
                        <div class="admin-filter-item">
                            <select class="admin-form-control" id="filter-usuario-rol">
                                <option value="">Todos los roles</option>
                                <option value="usuario">Usuario</option>
                                <option value="emprendedor">Emprendedor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="admin-filter-item">
                            <select class="admin-form-control" id="filter-usuario-estado">
                                <option value="">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="suspendido">Suspendido</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div id="usuarios-table-container">
                        <div class="admin-loading">
                            <div class="admin-spinner"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Lugares Section -->
            <section id="lugares-section" class="admin-section">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Gestión de Lugares Turísticos</h3>
                        <button class="btn-admin btn-admin-primary" onclick="refreshLugares()">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </div>

                    <div class="admin-filters">
                        <div class="admin-filter-item">
                            <input type="text" class="admin-form-control" id="filter-lugar-busqueda" 
                                   placeholder="Buscar lugares...">
                        </div>
                        <div class="admin-filter-item">
                            <select class="admin-form-control" id="filter-lugar-estado">
                                <option value="">Todos los estados</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>
                    </div>

                    <div id="lugares-table-container">
                        <div class="admin-loading">
                            <div class="admin-spinner"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sugerencias Section -->
            <section id="sugerencias-section" class="admin-section">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Lugares Sugeridos Pendientes</h3>
                        <button class="btn-admin btn-admin-primary" onclick="refreshSugerencias()">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </div>

                    <div id="sugerencias-table-container">
                        <div class="admin-loading">
                            <div class="admin-spinner"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Comentarios Section -->
            <section id="comentarios-section" class="admin-section">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Moderación de Comentarios</h3>
                        <button class="btn-admin btn-admin-primary" onclick="refreshComentarios()">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </div>

                    <div class="admin-filters">
                        <div class="admin-filter-item">
                            <select class="admin-form-control" id="filter-comentario-estado">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendientes</option>
                                <option value="aprobado">Aprobados</option>
                                <option value="rechazado">Rechazados</option>
                            </select>
                        </div>
                    </div>

                    <div id="comentarios-table-container">
                        <div class="admin-loading">
                            <div class="admin-spinner"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Categorías Section -->
            <section id="categorias-section" class="admin-section">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Gestión de Categorías</h3>
                        <button class="btn-admin btn-admin-success" onclick="showCrearCategoriaModal()">
                            <i class="bi bi-plus-circle"></i> Nueva Categoría
                        </button>
                    </div>

                    <div id="categorias-table-container">
                        <div class="admin-loading">
                            <div class="admin-spinner"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Departamentos Section -->
            <section id="departamentos-section" class="admin-section">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Gestión de Departamentos</h3>
                        <!-- Botón eliminado por solicitud -->
                    </div>

                    <div id="departamentos-table-container">
                        <div class="admin-loading">
                            <div class="admin-spinner"></div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modals will be added dynamically by JavaScript -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Panel JS -->
    <script src="../js/admin-panel.js"></script>
</body>
</html>
