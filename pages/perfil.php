<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Usuario.php';
require_once '../includes/conexion.php';

// Verificar sesión
if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);
if(!$usuario->verificarToken($_SESSION['token'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener datos del usuario
$sql_usuario = "SELECT * FROM usuarios WHERE id = ?";
$stmt_usuario = $conexion->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $user_id);
$stmt_usuario->execute();
$usuario_data = $stmt_usuario->get_result()->fetch_assoc();

// Obtener estadísticas completas
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM favoritos WHERE id_usuario = ?) as total_favoritos,
    (SELECT COUNT(*) FROM comentarios WHERE id_usuario = ? AND aprobado = 1) as total_resenas,
    (SELECT COUNT(*) FROM lugares_sugeridos WHERE id_usuario = ?) as total_sugerencias,
    (SELECT COUNT(*) FROM seguidores WHERE id_seguido = ?) as total_seguidores,
    (SELECT COUNT(*) FROM seguidores WHERE id_seguidor = ?) as total_siguiendo";
$stmt_stats = $conexion->prepare($sql_stats);
$stmt_stats->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// ========== AGREGAR ESTA SECCIÓN (LO QUE FALTA) ==========
// Obtener configuración de privacidad
$sql_config = "SELECT * FROM configuracion_privacidad WHERE id_usuario = ?";
$stmt_config = $conexion->prepare($sql_config);
$stmt_config->bind_param("i", $user_id);
$stmt_config->execute();
$result_config = $stmt_config->get_result();

// Si no existe configuración, usar valores por defecto
if($result_config->num_rows > 0) {
    $configuracion = $result_config->fetch_assoc();
} else {
    // Valores por defecto si no existe configuración
    $configuracion = [
        'perfil_publico' => 1,
        'favoritos_publicos' => 1,
        'comentarios_publicos' => 1,
        'mostrar_estadisticas' => 1
    ];
    
    // Opcional: Crear registro con valores por defecto
    $sql_insert = "INSERT INTO configuracion_privacidad 
                   (id_usuario, perfil_publico, favoritos_publicos, comentarios_publicos, mostrar_estadisticas) 
                   VALUES (?, 1, 1, 1, 1)";
    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bind_param("i", $user_id);
    $stmt_insert->execute();
}
// ========== FIN DE LA SECCIÓN A AGREGAR ==========

// Avatar URL
if(!empty($usuario_data['imagen_perfil']) && file_exists('../uploads/' . $usuario_data['imagen_perfil'])) {
    $avatar_url = '../uploads/' . $usuario_data['imagen_perfil'] . '?v=' . time();
} else {
    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($usuario_data['nombre']) . '&size=150&background=e67e22&color=fff';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - CataMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../styles/perfil.css">
</head>
<body>
    <div class="container profile-container">
        <div class="profile-card">
            <!-- Header del Perfil -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <div class="position-relative d-inline-block">
                            <img id="profileAvatar" src="<?php echo $avatar_url; ?>" class="profile-avatar" alt="Avatar">
                            <button class="edit-avatar-btn" onclick="cambiarAvatar()">
                                <i class="bi bi-camera-fill"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h3 class="mb-1"><?php echo htmlspecialchars($usuario_data['nombre']); ?></h3>
                        <p class="mb-1 opacity-75"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($usuario_data['email']); ?></p>
                        <span class="badge badge-light"><i class="bi bi-person-badge"></i> <?php echo ucfirst($usuario_data['tipo_usuario']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-3 stats-box">
                                <div class="stats-number"><?php echo $stats['total_favoritos']; ?></div>
                                <div class="stats-label">Favoritos</div>
                            </div>
                            <div class="col-3 stats-box">
                                <div class="stats-number"><?php echo $stats['total_resenas']; ?></div>
                                <div class="stats-label">Reseñas</div>
                            </div>
                            <div class="col-3 stats-box">
                                <div class="stats-number"><?php echo $stats['total_seguidores']; ?></div>
                                <div class="stats-label">Seguidores</div>
                            </div>
                            <div class="col-3 stats-box">
                                <div class="stats-number"><?php echo $stats['total_siguiendo']; ?></div>
                                <div class="stats-label">Siguiendo</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de Navegación -->
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#info"><i class="bi bi-person"></i> Mi Información</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#favoritos"><i class="bi bi-heart-fill"></i> Favoritos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#resenas"><i class="bi bi-star-fill"></i> Reseñas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#sugerencias"><i class="bi bi-file-text"></i> Sugerencias</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#sugerir"><i class="bi bi-plus-circle"></i> Sugerir Lugar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#seguidores"><i class="bi bi-people"></i> Social</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#privacidad"><i class="bi bi-shield-lock"></i> Privacidad</a>
                </li>
                <li class="nav-item ml-auto">
                    <a class="nav-link text-danger" href="../index.php"><i class="bi bi-house"></i> Inicio</a>
                </li>
            </ul>

            <!-- Contenido de los Tabs -->
            <div class="tab-content">
                <!-- Tab: Mi Información -->
                <div class="tab-pane fade show active" id="info">
                    <h4 class="mb-4"><i class="bi bi-person-circle text-primary"></i> Información Personal</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="font-weight-bold"><i class="bi bi-person"></i> Nombre Completo</label>
                            <div class="info-value">
                                <span id="displayNombre"><?php echo htmlspecialchars($usuario_data['nombre']); ?></span>
                                <button class="btn btn-sm btn-primary" onclick="editarNombre()">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="font-weight-bold"><i class="bi bi-envelope"></i> Email</label>
                            <div class="info-value">
                                <span id="displayEmail"><?php echo htmlspecialchars($usuario_data['email']); ?></span>
                                <button class="btn btn-sm btn-primary" onclick="editarEmail()">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="font-weight-bold"><i class="bi bi-phone"></i> Teléfono</label>
                            <div class="info-value">
                                <span id="displayTelefono"><?php echo !empty($usuario_data['telefono']) ? htmlspecialchars($usuario_data['telefono']) : 'No especificado'; ?></span>
                                <button class="btn btn-sm btn-primary" onclick="editarTelefono()">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="font-weight-bold"><i class="bi bi-calendar"></i> Miembro desde</label>
                            <div class="info-value">
                                <span><?php echo date('d/m/Y', strtotime($usuario_data['fecha_registro'])); ?></span>
                                <i class="bi bi-check-circle text-success"></i>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-warning btn-lg btn-block" onclick="cambiarPassword()">
                                <i class="bi bi-key"></i> Cambiar Contraseña
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-danger btn-lg btn-block" onclick="cerrarSesion()">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab: Favoritos -->
                <div class="tab-pane fade" id="favoritos">
                    <h4 class="mb-4"><i class="bi bi-heart-fill text-danger"></i> Mis Lugares Favoritos</h4>
                    <div class="row" id="favoritosContainer">
                        <div class="col-12 text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-3">Cargando favoritos...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab: Reseñas -->
                <div class="tab-pane fade" id="resenas">
                    <h4 class="mb-4"><i class="bi bi-star-fill text-warning"></i> Mis Reseñas</h4>
                    <div id="resenasContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-3">Cargando reseñas...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab: Sugerencias -->
                <div class="tab-pane fade" id="sugerencias">
                    <h4 class="mb-4"><i class="bi bi-file-text text-info"></i> Mis Sugerencias de Lugares</h4>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>Estado de tus sugerencias:</strong>
                        <span class="badge badge-pendiente ml-2">Pendiente</span>
                        <span class="badge badge-aprobado ml-2">Aprobado</span>
                        <span class="badge badge-rechazado ml-2">Rechazado</span>
                    </div>
                    <div id="sugerenciasContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-3">Cargando sugerencias...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab: Sugerir Lugar -->
                <div class="tab-pane fade" id="sugerir">
                    <h4 class="mb-4"><i class="bi bi-plus-circle text-success"></i> Sugerir Nuevo Lugar</h4>
                    <p class="text-muted">Ayúdanos a crecer el mapa de Catamarca compartiendo lugares increíbles</p>
                    
                    <!-- Reemplaza la sección del formulario en perfil.php -->
                    <form id="formSugerirLugar">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="bi bi-geo-alt"></i> Nombre del Lugar *</label>
                                    <input type="text" class="form-control" name="nombre" required placeholder="Ej: Cuesta del Portezuelo">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="bi bi-pin-map"></i> Dirección</label>
                                    <input type="text" class="form-control" name="direccion" placeholder="Ej: Ruta 4, km 15">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label><i class="bi bi-card-text"></i> Descripción * (mínimo 50 caracteres)</label>
                                    <textarea class="form-control" name="descripcion" rows="4" required placeholder="Describe el lugar, qué lo hace especial, qué actividades se pueden hacer..."></textarea>
                                    <small class="text-muted">Caracteres: <span id="charCount">0</span>/50</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="bi bi-tag"></i> Categoría *</label>
                                    <select class="form-control" name="id_categoria" required>
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="bi bi-map"></i> Departamento *</label>
                                    <select class="form-control" name="id_departamento" required>
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="bi bi-image"></i> Imagen</label>
                                    <input type="file" class="form-control-file" name="imagen" accept="image/*">
                                    <small class="text-muted">Máximo 5MB</small>
                                </div>
                            </div>
                            
                            <!-- Coordenadas ocultas pero funcionales -->
                            <input type="hidden" name="lat" id="inputLat" required>
                            <input type="hidden" name="lng" id="inputLng" required>
                            
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="bi bi-cursor-fill" style="font-size: 1.5rem; margin-right: 10px;"></i>
                                    <div class="flex-grow-1">
                                        <strong>Ubica el lugar en el mapa</strong>
                                        <p class="mb-0 small">Haz clic en el mapa para marcar la ubicación exacta del lugar que deseas sugerir</p>
                                        <small id="coordenadasMarcadas" class="text-success d-none">
                                            <i class="bi bi-check-circle-fill"></i> Ubicación marcada correctamente
                                        </small>
                                    </div>
                                </div>
                                <div id="map" style="position: relative;">
                                    <!-- Indicador de ubicación marcada -->
                                    <div id="ubicacionIndicador" class="ubicacion-marcada d-none">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <span>Ubicación seleccionada</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-success btn-lg btn-block">
                                    <i class="bi bi-send"></i> Enviar Sugerencia para Revisión
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tab: Seguidores/Social -->
                <div class="tab-pane fade" id="seguidores">
                    <h4 class="mb-4"><i class="bi bi-people text-primary"></i> Red Social</h4>
                    <ul class="nav nav-pills mb-4">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="pill" href="#misSeguidores">
                                <i class="bi bi-person-check"></i> Mis Seguidores (<?php echo $stats['total_seguidores']; ?>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#siguiendo">
                                <i class="bi bi-person-plus"></i> Siguiendo (<?php echo $stats['total_siguiendo']; ?>)
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="misSeguidores">
                            <div id="seguidoresContainer">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="siguiendo">
                            <div id="siguiendoContainer">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Privacidad (CORREGIDO) -->
                <div class="tab-pane fade" id="privacidad">
                    <h4 class="mb-4">
                        <i class="bi bi-shield-lock text-danger"></i> Configuración de Privacidad
                    </h4>
                    
                    <form id="formPrivacidad">
                        <!-- Perfil Público -->
                        <div class="card mb-3 border-primary">
                            <div class="card-body">
                                <h6><i class="bi bi-eye"></i> Visibilidad del Perfil</h6>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                        class="custom-control-input" 
                                        id="perfilPublico" 
                                        name="perfil_publico" 
                                        <?php echo $configuracion['perfil_publico'] ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="perfilPublico">
                                        Permitir que otros usuarios vean mi perfil
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Cuando está activado, otros usuarios pueden ver tu perfil público
                                </small>
                            </div>
                        </div>

                        <!-- Favoritos Públicos -->
                        <div class="card mb-3 border-info">
                            <div class="card-body">
                                <h6><i class="bi bi-heart"></i> Favoritos</h6>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                        class="custom-control-input" 
                                        id="favoritosPublicos" 
                                        name="favoritos_publicos"
                                        <?php echo $configuracion['favoritos_publicos'] ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="favoritosPublicos">
                                        Mostrar mis lugares favoritos a otros usuarios
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Otros usuarios podrán ver qué lugares has guardado como favoritos
                                </small>
                            </div>
                        </div>

                        <!-- Comentarios Públicos -->
                        <div class="card mb-3 border-warning">
                            <div class="card-body">
                                <h6><i class="bi bi-chat-dots"></i> Comentarios</h6>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                        class="custom-control-input" 
                                        id="comentariosPublicos" 
                                        name="comentarios_publicos"
                                        <?php echo $configuracion['comentarios_publicos'] ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="comentariosPublicos">
                                        Mis comentarios son visibles para todos
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Tus reseñas y comentarios aparecerán con tu nombre
                                </small>
                            </div>
                        </div>

                        <!-- Estadísticas -->
                        <div class="card mb-3 border-success">
                            <div class="card-body">
                                <h6><i class="bi bi-bar-chart"></i> Estadísticas</h6>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                        class="custom-control-input" 
                                        id="mostrarEstadisticas" 
                                        name="mostrar_estadisticas"
                                        <?php echo $configuracion['mostrar_estadisticas'] ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="mostrarEstadisticas">
                                        Mostrar mis estadísticas en mi perfil público
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Mostrar cuántos favoritos, comentarios y seguidores tienes
                                </small>
                            </div>
                        </div>

                        <!-- Link del perfil público -->
                        <div class="card mb-3 border-secondary">
                            <div class="card-body">
                                <h6><i class="bi bi-link-45deg"></i> Enlace de tu perfil público</h6>
                                <div class="input-group">
                                    <input type="text" 
                                        class="form-control" 
                                        id="enlacePerfilPublico" 
                                        readonly 
                                        value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/catamap/pages/perfil-publico.php?user=' . $user_id; ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" 
                                                type="button" 
                                                onclick="copiarEnlacePerfil()">
                                            <i class="bi bi-clipboard"></i> Copiar
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Comparte este enlace con otros para que vean tu perfil público
                                </small>
                            </div>
                        </div>

                        <!-- Botón de guardar -->
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="bi bi-check-circle"></i> Guardar Configuración de Privacidad
                        </button>

                        <!-- Botón de debug (temporal) -->
                        <button type="button" 
                                class="btn btn-info btn-sm btn-block mt-2" 
                                onclick="debugPrivacidad()">
                            <i class="bi bi-bug"></i> Debug (Ver valores)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Variables globales
        let map;
        let marker;
        const userId = <?php echo $user_id; ?>;

        // Inicializar al cargar
        $(document).ready(function() {
            // Cargar datos cuando se activen los tabs
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                const target = $(e.target).attr("href");
                if(target === '#favoritos') cargarFavoritos();
                else if(target === '#resenas') cargarResenas();
                else if(target === '#sugerencias') cargarSugerencias();
                else if(target === '#sugerir') inicializarMapa();
                else if(target === '#seguidores') cargarSeguidores();
            });

            // Contador de caracteres en descripción
            $('textarea[name="descripcion"]').on('input', function() {
                $('#charCount').text($(this).val().length);
            });

            // Cargar categorías y departamentos
            cargarSelectores();
        });

        // ========== FUNCIONES DE EDICIÓN ==========
function editarNombre() {
    Swal.fire({
        title: 'Editar Nombre',
        input: 'text',
        inputValue: $('#displayNombre').text().trim(),
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        preConfirm: (nombre) => {
            if (!nombre) {
                Swal.showValidationMessage('El nombre no puede estar vacío');
                return false;
            }
            return $.ajax({
                url: '../api/actualizar_perfil.php',
                type: 'POST',
                data: { campo: 'nombre', valor: nombre },
                dataType: 'json'
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.success) {
            $('#displayNombre').text(result.value.nombre);
            Swal.fire('¡Actualizado!', 'Nombre actualizado correctamente', 'success');
        } else if(result.isConfirmed) {
            Swal.fire('Error', result.value.message || 'Error al actualizar', 'error');
        }
    });
}

function editarTelefono() {
    Swal.fire({
        title: 'Editar Teléfono',
        input: 'text',
        inputValue: $('#displayTelefono').text() === 'No especificado' ? '' : $('#displayTelefono').text().trim(),
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        preConfirm: (telefono) => {
            return $.ajax({
                url: '../api/actualizar_perfil.php',
                type: 'POST',
                data: { campo: 'telefono', valor: telefono },
                dataType: 'json'
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.success) {
            $('#displayTelefono').text(result.value.telefono || 'No especificado');
            Swal.fire('¡Actualizado!', 'Teléfono actualizado correctamente', 'success');
        }
    });
}

function cambiarPassword() {
    Swal.fire({
        title: 'Cambiar Contraseña',
        html: `
            <input type="password" id="oldPassword" class="swal2-input" placeholder="Contraseña actual">
            <input type="password" id="newPassword" class="swal2-input" placeholder="Nueva contraseña">
            <input type="password" id="confirmPassword" class="swal2-input" placeholder="Confirmar contraseña">
        `,
        showCancelButton: true,
        confirmButtonText: 'Cambiar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const oldPass = $('#oldPassword').val();
            const newPass = $('#newPassword').val();
            const confirmPass = $('#confirmPassword').val();
            
            if (!oldPass || !newPass || !confirmPass) {
                Swal.showValidationMessage('Todos los campos son obligatorios');
                return false;
            }
            if (newPass.length < 6) {
                Swal.showValidationMessage('La nueva contraseña debe tener al menos 6 caracteres');
                return false;
            }
            if (newPass !== confirmPass) {
                Swal.showValidationMessage('Las contraseñas no coinciden');
                return false;
            }
            return $.ajax({
                url: '../api/cambiar_password.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ 
                    old_password: oldPass,
                    new_password: newPass 
                }),
                dataType: 'json'
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.success) {
            Swal.fire('¡Actualizado!', 'Contraseña cambiada correctamente', 'success');
        } else if(result.isConfirmed) {
            Swal.fire('Error', result.value.message || 'Error al cambiar contraseña', 'error');
        }
    })
    }

        function cambiarAvatar() {
            Swal.fire({
                title: 'Cambiar Foto de Perfil',
                html: '<input type="file" id="avatarFile" accept="image/*" class="swal2-file">',
                showCancelButton: true,
                confirmButtonText: 'Subir',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const file = $('#avatarFile')[0].files[0];
                    if (!file) {
                        Swal.showValidationMessage('Debes seleccionar una imagen');
                        return false;
                    }
                    if (file.size > 5242880) {
                        Swal.showValidationMessage('La imagen no puede superar 5MB');
                        return false;
                    }
                    
                    const formData = new FormData();
                    formData.append('avatar', file);
                    
                    return $.ajax({
                        url: '../api/actualizar_avatar.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    $('#profileAvatar').attr('src', result.value.avatar_url);
                    Swal.fire('¡Actualizado!', 'Foto de perfil actualizada', 'success');
                }
            });
        }

        function cerrarSesion() {
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: 'Tendrás que volver a iniciar sesión',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../api/logout.php';
                }
            });
        }

// ========== CARGAR FAVORITOS CORREGIDO ==========
function cargarFavoritos() {
    $('#favoritosContainer').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Cargando favoritos...</p></div>');
    
    $.ajax({
        url: '../api/obtener_favoritos.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Respuesta favoritos:', data); // Para debug
            
            if (data.success && data.favoritos && data.favoritos.length > 0) {
                let html = '';
                data.favoritos.forEach(fav => {
                    const imagenUrl = fav.imagen ? `../uploads/${fav.imagen}` : '../img/placeholder.jpg';
                    
                    html += `
                        <div class="col-md-4 mb-4" data-favorito-id="${fav.id_lugar}">
                            <div class="favorito-card">
                                <img src="${imagenUrl}" class="lugar-imagen" 
                                     alt="${fav.nombre}"
                                     onerror="this.src='../img/placeholder.jpg'">
                                <h5><i class="bi bi-geo-alt-fill text-danger"></i> ${fav.nombre}</h5>
                                <p class="text-muted mb-2">
                                    <small>
                                        <i class="bi bi-tag"></i> ${fav.categoria} | 
                                        <i class="bi bi-pin-map"></i> ${fav.departamento}
                                    </small>
                                </p>
                                <p class="text-truncate">${fav.descripcion ? fav.descripcion.substring(0, 100) + '...' : 'Sin descripción'}</p>
                                <div class="d-flex gap-2" style="gap: 8px;">
                                    <button class="btn btn-success btn-sm flex-fill" 
                                            onclick="window.location.href='mapa-catamarca.php?lat=${fav.lat}&lng=${fav.lng}&id=${fav.id_lugar}'">
                                        <i class="bi bi-map"></i> Ver en Mapa
                                    </button>
                                    <button class="btn btn-danger btn-sm flex-fill" 
                                            onclick="quitarFavorito(${fav.id_lugar})">
                                        <i class="bi bi-heart-fill"></i> Quitar
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-calendar"></i> Agregado: ${fav.fecha_agregado}
                                </small>
                            </div>
                        </div>
                    `;
                });
                $('#favoritosContainer').html(html);
            } else {
                $('#favoritosContainer').html(`
                    <div class="col-12 empty-state">
                        <i class="bi bi-heart"></i>
                        <h5>No tienes lugares favoritos aún</h5>
                        <p>Explora el mapa y guarda tus lugares favoritos</p>
                        <a href="mapa-catamarca.php" class="btn btn-primary mt-3">
                            <i class="bi bi-map"></i> Explorar Mapa
                        </a>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar favoritos:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            $('#favoritosContainer').html(`
                <div class="col-12 alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Error al cargar favoritos</strong><br>
                    <small>${error || 'Error desconocido'}</small>
                </div>
            `);
        }
    });
}

function quitarFavorito(idLugar) {
    Swal.fire({
        title: '¿Quitar de favoritos?',
        text: 'Este lugar se eliminará de tu lista de favoritos',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Sí, quitar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/favoritos.php',
                type: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id_lugar: idLugar }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        // Eliminar visualmente con animación
                        $(`[data-favorito-id="${idLugar}"]`).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Si no quedan favoritos, mostrar mensaje
                            if ($('#favoritosContainer .col-md-4').length === 0) {
                                $('#favoritosContainer').html(`
                                    <div class="col-12 empty-state">
                                        <i class="bi bi-heart"></i>
                                        <h5>No tienes lugares favoritos aún</h5>
                                        <p>Explora el mapa y guarda tus lugares favoritos</p>
                                        <a href="mapa-catamarca.php" class="btn btn-primary mt-3">
                                            <i class="bi bi-map"></i> Explorar Mapa
                                        </a>
                                    </div>
                                `);
                            }
                        });
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado!',
                            text: 'Lugar quitado de favoritos',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Error al eliminar');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo eliminar de favoritos'
                    });
                }
            });
        }
    });
}
        // ========== CARGAR RESEÑAS ==========
        function cargarResenas() {
            $('#resenasContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
            
            $.get('../api/obtener_resenas_usuario.php', function(data) {
                if (data.success && data.resenas.length > 0) {
                    let html = '';
                    data.resenas.forEach(resena => {
                        const estrellas = '★'.repeat(resena.calificacion) + '☆'.repeat(5 - resena.calificacion);
                        html += `
                            <div class="resena-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5><i class="bi bi-geo-alt"></i> ${resena.lugar_nombre}</h5>
                                        <div class="rating-stars">${estrellas}</div>
                                        <p class="mt-2">${resena.comentario}</p>
                                        <small class="text-muted"><i class="bi bi-calendar"></i> ${resena.fecha_creacion}</small>
                                        ${resena.aprobado ? '<span class="badge badge-aprobado ml-2">Aprobada</span>' : '<span class="badge badge-pendiente ml-2">Pendiente de aprobación</span>'}
                                    </div>
                                    <div class="ml-3">
                                        <button class="btn btn-sm btn-warning btn-action" onclick="editarResena(${resena.id})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-action" onclick="eliminarResena(${resena.id})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#resenasContainer').html(html);
                } else {
                    $('#resenasContainer').html(`
                        <div class="empty-state">
                            <i class="bi bi-star"></i>
                            <h5>No has dejado reseñas aún</h5>
                            <p>Comparte tu experiencia en los lugares que visites</p>
                        </div>
                    `);
                }
            }).fail(function() {
                $('#resenasContainer').html('<div class="alert alert-danger">Error al cargar reseñas</div>');
            });
        }

        function editarResena(idResena) {
    $.ajax({
        url: '../api/obtener_resena.php',
        type: 'GET',
        data: { id: idResena },
        dataType: 'json',
        success: function(resena) {
            Swal.fire({
                title: 'Editar Reseña',
                html: `
                    <select id="editCalificacion" class="swal2-select">
                        <option value="1" ${resena.calificacion == 1 ? 'selected' : ''}>1 ★</option>
                        <option value="2" ${resena.calificacion == 2 ? 'selected' : ''}>2 ★★</option>
                        <option value="3" ${resena.calificacion == 3 ? 'selected' : ''}>3 ★★★</option>
                        <option value="4" ${resena.calificacion == 4 ? 'selected' : ''}>4 ★★★★</option>
                        <option value="5" ${resena.calificacion == 5 ? 'selected' : ''}>5 ★★★★★</option>
                    </select>
                    <textarea id="editComentario" class="swal2-textarea">${resena.comentario}</textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return $.ajax({
                        url: '../api/editar_resena.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            id: idResena,
                            calificacion: $('#editCalificacion').val(),
                            comentario: $('#editComentario').val()
                        }),
                        dataType: 'json'
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    Swal.fire('¡Actualizado!', 'Reseña actualizada correctamente', 'success');
                    cargarResenas();
                } else if(result.isConfirmed) {
                    Swal.fire('Error', result.value.message || 'Error al actualizar', 'error');
                }
            });
        },
        error: function() {
            Swal.fire('Error', 'No se pudo cargar la reseña', 'error');
        }
    });
}

// ========== FUNCIONES DE RESEÑAS CORREGIDAS ==========
function eliminarResena(idResena) {
    Swal.fire({
        title: '¿Eliminar reseña?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/eliminar_resena.php',
                type: 'POST',
                data: { id: idResena },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire('¡Eliminado!', 'Reseña eliminada', 'success');
                        cargarResenas();
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo eliminar', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error al eliminar la reseña', 'error');
                }
            });
        }
    });
}

        // ========== CARGAR SUGERENCIAS ==========
        function cargarSugerencias() {
            $('#sugerenciasContainer').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
            
            $.get('../api/obtener_sugerencias_usuario.php', function(data) {
                if (data.success && data.sugerencias.length > 0) {
                    let html = '';
                    data.sugerencias.forEach(sug => {
                        let estadoBadge = '';
                        if (sug.estado === 'pendiente') estadoBadge = '<span class="badge badge-pendiente">Pendiente</span>';
                        else if (sug.estado === 'aprobado') estadoBadge = '<span class="badge badge-aprobado">Aprobado</span>';
                        else estadoBadge = '<span class="badge badge-rechazado">Rechazado</span>';
                        
                        html += `
                            <div class="sugerencia-card">
                                <div class="row">
                                    <div class="col-md-3">
                                        ${sug.imagen ? `<img src="../uploads/${sug.imagen}" class="lugar-imagen">` : '<div class="lugar-imagen bg-secondary d-flex align-items-center justify-content-center"><i class="bi bi-image text-white" style="font-size: 3rem;"></i></div>'}
                                    </div>
                                    <div class="col-md-9">
                                        <div class="d-flex justify-content-between">
                                            <h5><i class="bi bi-geo-alt-fill text-primary"></i> ${sug.nombre}</h5>
                                            ${estadoBadge}
                                        </div>
                                        <p class="text-muted"><small><i class="bi bi-pin-map"></i> ${sug.direccion || 'Sin dirección'}</small></p>
                                        <p>${sug.descripcion.substring(0, 150)}...</p>
                                        ${sug.motivo_rechazo ? `<div class="alert alert-danger"><strong>Motivo de rechazo:</strong> ${sug.motivo_rechazo}</div>` : ''}
                                        <small class="text-muted"><i class="bi bi-calendar"></i> Sugerido el ${sug.fecha_sugerido}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#sugerenciasContainer').html(html);
                } else {
                    $('#sugerenciasContainer').html(`
                        <div class="empty-state">
                            <i class="bi bi-file-text"></i>
                            <h5>No has sugerido lugares aún</h5>
                            <p>Ve a la pestaña "Sugerir Lugar" para agregar nuevos sitios</p>
                        </div>
                    `);
                }
            }).fail(function() {
                $('#sugerenciasContainer').html('<div class="alert alert-danger">Error al cargar sugerencias</div>');
            });
        }

        // Actualizar la función del mapa en perfil.php
function inicializarMapa() {
    if (map) return; // Ya está inicializado
    
    setTimeout(() => {
        map = L.map('map').setView([-28.4696, -65.7795], 8); // Centro de Catamarca
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Crear un ícono personalizado para el marcador
        const customIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        map.on('click', function(e) {
            // Remover marcador anterior si existe
            if (marker) {
                map.removeLayer(marker);
            }
            
            // Crear nuevo marcador con icono personalizado
            marker = L.marker(e.latlng, { icon: customIcon }).addTo(map);
            
            // Agregar popup al marcador
            marker.bindPopup(`
                <div style="text-align: center; padding: 5px;">
                    <strong style="color: #4CAF50;">📍 Ubicación seleccionada</strong><br>
                    <small style="color: #666;">Lat: ${e.latlng.lat.toFixed(6)}<br>Lng: ${e.latlng.lng.toFixed(6)}</small>
                </div>
            `).openPopup();
            
            // Guardar coordenadas en campos ocultos
            $('#inputLat').val(e.latlng.lat.toFixed(6));
            $('#inputLng').val(e.latlng.lng.toFixed(6));
            
            // Mostrar indicadores visuales
            $('#coordenadasMarcadas').removeClass('d-none');
            $('#ubicacionIndicador').removeClass('d-none');
            $('#map').addClass('ubicacion-seleccionada');
            
            // Animación sutil del mapa
            setTimeout(() => {
                map.panTo(e.latlng);
            }, 100);
        });
        
        // Ajustar el mapa cuando se muestre
        setTimeout(() => {
            map.invalidateSize();
        }, 100);
    }, 300);
}

// Actualizar el submit del formulario para validar coordenadas
$('#formSugerirLugar').on('submit', function(e) {
    e.preventDefault();
    
    const descripcion = $('textarea[name="descripcion"]').val();
    if (descripcion.length < 50) {
        Swal.fire({
            icon: 'error',
            title: 'Descripción muy corta',
            text: 'La descripción debe tener al menos 50 caracteres',
            confirmButtonColor: '#e74c3c'
        });
        return;
    }
    
    if (!$('#inputLat').val() || !$('#inputLng').val()) {
        Swal.fire({
            icon: 'warning',
            title: 'Ubicación requerida',
            html: '<i class="bi bi-cursor-fill" style="font-size: 2rem; color: #f39c12;"></i><br><br>Debes marcar la ubicación del lugar en el mapa haciendo clic sobre él',
            confirmButtonColor: '#f39c12'
        });
        
        // Hacer scroll hasta el mapa
        $('html, body').animate({
            scrollTop: $("#map").offset().top - 100
        }, 500);
        
        // Efecto visual en el mapa
        $('#map').css('border-color', '#f39c12');
        setTimeout(() => {
            $('#map').css('border-color', '#e0e0e0');
        }, 2000);
        
        return;
    }
    
    const formData = new FormData(this);
    
    // Mostrar loading
    Swal.fire({
        title: 'Enviando sugerencia...',
        html: '<div class="spinner-border text-primary" role="status"></div>',
        showConfirmButton: false,
        allowOutsideClick: false
    });
    
    $.ajax({
        url: '../api/sugerir_lugar.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Sugerencia enviada!',
                    html: '<p>Tu sugerencia será revisada por un administrador.</p><p class="text-muted small">Recibirás una notificación cuando sea aprobada.</p>',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#4CAF50'
                });
                
                // Limpiar formulario
                $('#formSugerirLugar')[0].reset();
                $('#inputLat, #inputLng').val('');
                $('#coordenadasMarcadas').addClass('d-none');
                $('#ubicacionIndicador').addClass('d-none');
                $('#map').removeClass('ubicacion-seleccionada');
                if (marker) map.removeLayer(marker);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo enviar la sugerencia',
                    confirmButtonColor: '#e74c3c'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                confirmButtonColor: '#e74c3c'
            });
        }
    });
});

 // ========== CARGAR SEGUIDORES CON ESTADO CORRECTO ==========
function cargarSeguidores() {
    // Cargar seguidores (personas que me siguen)
    $.get('../api/obtener_seguidores.php', function(data) {
        if (data.success && data.seguidores.length > 0) {
            // También obtener a quiénes sigo para verificar el estado
            $.get('../api/obtener_siguiendo.php', function(dataSiguiendo) {
                const siguiendoIds = dataSiguiendo.success ? dataSiguiendo.siguiendo.map(s => s.id) : [];
                
                let html = '';
                data.seguidores.forEach(seg => {
                    const yaSigo = siguiendoIds.includes(seg.id);
                    
                    html += `
                        <div class="user-card" data-user-id="${seg.id}">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <img src="${seg.imagen_perfil || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(seg.nombre)}" class="user-avatar-small">
                                </div>
                                <div class="col">
                                    <h6 class="mb-0">${seg.nombre}</h6>
                                    <small class="text-muted">${seg.email}</small>
                                </div>
                                <div class="col-auto">
                                    ${yaSigo ? 
                                        `<button class="btn btn-sm btn-success" disabled>
                                            <i class="bi bi-check-circle"></i> Siguiendo
                                        </button>` :
                                        `<button class="btn btn-sm btn-primary" onclick="seguirUsuario(${seg.id})">
                                            <i class="bi bi-person-plus"></i> Seguir de vuelta
                                        </button>`
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#seguidoresContainer').html(html);
            });
        } else {
            $('#seguidoresContainer').html('<div class="empty-state"><i class="bi bi-people"></i><h5>Aún no tienes seguidores</h5></div>');
        }
    }).fail(function() {
        $('#seguidoresContainer').html('<div class="alert alert-danger">Error al cargar seguidores</div>');
    });
    
    // Cargar siguiendo
    $.get('../api/obtener_siguiendo.php', function(data) {
        if (data.success && data.siguiendo.length > 0) {
            let html = '';
            data.siguiendo.forEach(seg => {
                html += `
                    <div class="user-card" data-user-id="${seg.id}">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <img src="${seg.imagen_perfil || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(seg.nombre)}" class="user-avatar-small">
                            </div>
                            <div class="col">
                                <h6 class="mb-0">${seg.nombre}</h6>
                                <small class="text-muted">${seg.email}</small>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-sm btn-danger" onclick="dejarDeSeguir(${seg.id})">
                                    <i class="bi bi-person-dash"></i> Dejar de seguir
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#siguiendoContainer').html(html);
        } else {
            $('#siguiendoContainer').html('<div class="empty-state"><i class="bi bi-people"></i><h5>No sigues a nadie aún</h5></div>');
        }
    }).fail(function() {
        $('#siguiendoContainer').html('<div class="alert alert-danger">Error al cargar siguiendo</div>');
    });
}

function seguirUsuario(idUsuario) {
    $.ajax({
        url: '../api/seguir_usuario.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id_usuario: idUsuario }),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: 'Ahora sigues a este usuario',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Recargar lista para actualizar el estado
                cargarSeguidores();
            } else {
                Swal.fire('Error', data.message || 'No se pudo seguir', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Error al seguir usuario', 'error');
        }
    });
}

function dejarDeSeguir(idUsuario) {
    Swal.fire({
        title: '¿Dejar de seguir?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, dejar de seguir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../api/dejar_seguir.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id_usuario: idUsuario }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Listo',
                            text: 'Has dejado de seguir a este usuario',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        // Recargar lista
                        cargarSeguidores();
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo dejar de seguir', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error al dejar de seguir', 'error');
                }
            });
        }
    });
}
       // ========== GUARDAR PRIVACIDAD (CORREGIDO) ==========
$('#formPrivacidad').on('submit', function(e) {
    e.preventDefault();
    
    // Obtener valores de los checkboxes
    const config = {
        perfil_publico: $('#perfilPublico').is(':checked') ? 'true' : 'false',
        favoritos_publicos: $('#favoritosPublicos').is(':checked') ? 'true' : 'false',
        comentarios_publicos: $('#comentariosPublicos').is(':checked') ? 'true' : 'false',
        mostrar_estadisticas: $('#mostrarEstadisticas').is(':checked') ? 'true' : 'false'
    };
    
    console.log('Enviando configuración:', config); // Para debug
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando...',
        text: 'Actualizando configuración de privacidad',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '../api/guardar_privacidad.php',
        type: 'POST',
        data: config,
        dataType: 'json',
        success: function(data) {
            console.log('Respuesta del servidor:', data); // Para debug
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: 'Configuración de privacidad actualizada correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo guardar la configuración'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Verifica la consola para más detalles.'
            });
        }
    });
});

// Función alternativa si el formulario no funciona
function guardarPrivacidadManual() {
    const config = {
        perfil_publico: $('#perfilPublico').is(':checked') ? 'true' : 'false',
        favoritos_publicos: $('#favoritosPublicos').is(':checked') ? 'true' : 'false',
        comentarios_publicos: $('#comentariosPublicos').is(':checked') ? 'true' : 'false',
        mostrar_estadisticas: $('#mostrarEstadisticas').is(':checked') ? 'true' : 'false'
    };
    
    console.log('Guardando configuración:', config);
    
    Swal.fire({
        title: 'Guardando...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    $.post('../api/guardar_privacidad.php', config)
        .done(function(data) {
            console.log('Respuesta:', data);
            if (data.success) {
                Swal.fire('¡Guardado!', 'Configuración actualizada', 'success');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .fail(function(xhr) {
            console.error('Error:', xhr.responseText);
            Swal.fire('Error', 'No se pudo guardar', 'error');
        });
}
        // ========== CARGAR SELECTORES ==========
        function cargarSelectores() {
            // Cargar categorías
            $.get('../api/obtener_categorias.php', function(data) {
                if (data.success) {
                    let options = '<option value="">Seleccionar...</option>';
                    data.categorias.forEach(cat => {
                        options += `<option value="${cat.id}">${cat.nombre}</option>`;
                    });
                    $('select[name="id_categoria"]').html(options);
                }
            });
            
            // Cargar departamentos
            $.get('../api/obtener_departamentos.php', function(data) {
                if (data.success) {
                    let options = '<option value="">Seleccionar...</option>';
                    data.departamentos.forEach(dep => {
                        options += `<option value="${dep.id}">${dep.nombre}</option>`;
                    });
                    $('select[name="id_departamento"]').html(options);
                }
            });
        }

        // Función mejorada para copiar enlace de perfil público
        function copiarEnlacePerfil() {
            const input = document.getElementById('enlacePerfilPublico');
            
            if (!input) {
                Swal.fire('Error', 'No se encontró el enlace', 'error');
                return;
            }
            
            // Seleccionar el texto
            input.select();
            input.setSelectionRange(0, 99999); // Para dispositivos móviles
            
            // Intentar copiar con la API moderna
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(input.value)
                    .then(function() {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Copiado!',
                            text: 'Enlace copiado al portapapeles',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    })
                    .catch(function(err) {
                        console.error('Error al copiar:', err);
                        // Intentar método fallback
                        copiarConFallback(input);
                    });
            } else {
                // Usar método fallback directamente
                copiarConFallback(input);
            }
        }

        // Método fallback para copiar
        function copiarConFallback(input) {
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Copiado!',
                        text: 'Enlace copiado al portapapeles',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    throw new Error('No se pudo copiar');
                }
            } catch (err) {
                Swal.fire({
                    icon: 'info',
                    title: 'Copia manual',
                    html: 'Presiona Ctrl+C (o Cmd+C en Mac) para copiar',
                    confirmButtonText: 'Entendido'
                });
            }
        }
    </script>
</body>
</html>